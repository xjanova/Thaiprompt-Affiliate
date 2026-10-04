# -*- coding: utf-8 -*-
"""
แปลงน้ำหนัก MiniFASNet (Silent-Face-Anti-Spoofing) จาก .pth → .onnx ตอน build image

ที่มา: https://github.com/minivision-ai/Silent-Face-Anti-Spoofing (Apache License 2.0)
      commit b6d5f04ad78778917853b25c778acef6d5626d15
สถาปัตยกรรมโมเดลด้านล่างคัดลอกมาจาก src/model_lib/MiniFASNet.py ของ repo ข้างบน
(Copyright Minivision, Apache-2.0) — ดัดแปลงโดย Thai Prompt: ตัดส่วนที่ไม่ใช้
(MiniFASNetV2SE / L2Norm), จัดรูปแบบโค้ด และเพิ่มขั้นตอน export เป็น ONNX + ตรวจผลเทียบ PyTorch

การใช้งาน:
    python scripts/convert_minifasnet.py --models-dir /opt/ekyc/models

อ่าน:  <models-dir>/minifasnet/2.7_80x80_MiniFASNetV2.pth
       <models-dir>/minifasnet/4_0_0_80x80_MiniFASNetV1SE.pth
เขียน: <models-dir>/minifasnet/*.onnx  (input "input" float32 [1,3,80,80] BGR 0-255, output "logits" [1,3])
       class index 1 = ใบหน้าจริง (real)
"""
from __future__ import annotations

import argparse
import os
import sys
from collections import OrderedDict

import numpy as np
import torch
from torch.nn import (AdaptiveAvgPool2d, BatchNorm1d, BatchNorm2d, Conv2d, Linear, Module,
                      PReLU, ReLU, Sequential, Sigmoid)


# ---------------------------------------------------------------------------
# สถาปัตยกรรม MiniFASNet (Apache-2.0, Minivision) — ต้องตรงกับต้นฉบับทุกชั้น
# ไม่งั้นชื่อ key ของ state_dict จะไม่ตรงและโหลดน้ำหนักไม่ได้
# ---------------------------------------------------------------------------
class Flatten(Module):
    def forward(self, x):
        return x.view(x.size(0), -1)


class Conv_block(Module):
    def __init__(self, in_c, out_c, kernel=(1, 1), stride=(1, 1), padding=(0, 0), groups=1):
        super().__init__()
        self.conv = Conv2d(in_c, out_c, kernel_size=kernel, groups=groups, stride=stride,
                           padding=padding, bias=False)
        self.bn = BatchNorm2d(out_c)
        self.prelu = PReLU(out_c)

    def forward(self, x):
        return self.prelu(self.bn(self.conv(x)))


class Linear_block(Module):
    def __init__(self, in_c, out_c, kernel=(1, 1), stride=(1, 1), padding=(0, 0), groups=1):
        super().__init__()
        self.conv = Conv2d(in_c, out_channels=out_c, kernel_size=kernel, groups=groups,
                           stride=stride, padding=padding, bias=False)
        self.bn = BatchNorm2d(out_c)

    def forward(self, x):
        return self.bn(self.conv(x))


class Depth_Wise(Module):
    def __init__(self, c1, c2, c3, residual=False, kernel=(3, 3), stride=(2, 2), padding=(1, 1), groups=1):
        super().__init__()
        c1_in, c1_out = c1
        c2_in, c2_out = c2
        c3_in, c3_out = c3
        self.conv = Conv_block(c1_in, out_c=c1_out, kernel=(1, 1), padding=(0, 0), stride=(1, 1))
        self.conv_dw = Conv_block(c2_in, c2_out, groups=c2_in, kernel=kernel, padding=padding, stride=stride)
        self.project = Linear_block(c3_in, c3_out, kernel=(1, 1), padding=(0, 0), stride=(1, 1))
        self.residual = residual

    def forward(self, x):
        short_cut = x
        x = self.project(self.conv_dw(self.conv(x)))
        return short_cut + x if self.residual else x


class Residual(Module):
    def __init__(self, c1, c2, c3, num_block, groups, kernel=(3, 3), stride=(1, 1), padding=(1, 1)):
        super().__init__()
        modules = [Depth_Wise(c1[i], c2[i], c3[i], residual=True, kernel=kernel, padding=padding,
                              stride=stride, groups=groups) for i in range(num_block)]
        self.model = Sequential(*modules)

    def forward(self, x):
        return self.model(x)


class SEModule(Module):
    def __init__(self, channels, reduction):
        super().__init__()
        self.avg_pool = AdaptiveAvgPool2d(1)
        self.fc1 = Conv2d(channels, channels // reduction, kernel_size=1, padding=0, bias=False)
        self.bn1 = BatchNorm2d(channels // reduction)
        self.relu = ReLU(inplace=True)
        self.fc2 = Conv2d(channels // reduction, channels, kernel_size=1, padding=0, bias=False)
        self.bn2 = BatchNorm2d(channels)
        self.sigmoid = Sigmoid()

    def forward(self, x):
        module_input = x
        x = self.sigmoid(self.bn2(self.fc2(self.relu(self.bn1(self.fc1(self.avg_pool(x)))))))
        return module_input * x


class Depth_Wise_SE(Module):
    def __init__(self, c1, c2, c3, residual=False, kernel=(3, 3), stride=(2, 2), padding=(1, 1), groups=1,
                 se_reduct=8):
        super().__init__()
        c1_in, c1_out = c1
        c2_in, c2_out = c2
        c3_in, c3_out = c3
        self.conv = Conv_block(c1_in, out_c=c1_out, kernel=(1, 1), padding=(0, 0), stride=(1, 1))
        self.conv_dw = Conv_block(c2_in, c2_out, groups=c2_in, kernel=kernel, padding=padding, stride=stride)
        self.project = Linear_block(c3_in, c3_out, kernel=(1, 1), padding=(0, 0), stride=(1, 1))
        self.residual = residual
        self.se_module = SEModule(c3_out, se_reduct)

    def forward(self, x):
        short_cut = x
        x = self.project(self.conv_dw(self.conv(x)))
        if self.residual:
            return short_cut + self.se_module(x)
        return x


class ResidualSE(Module):
    def __init__(self, c1, c2, c3, num_block, groups, kernel=(3, 3), stride=(1, 1), padding=(1, 1), se_reduct=4):
        super().__init__()
        modules = []
        for i in range(num_block):
            if i == num_block - 1:
                modules.append(Depth_Wise_SE(c1[i], c2[i], c3[i], residual=True, kernel=kernel, padding=padding,
                                             stride=stride, groups=groups, se_reduct=se_reduct))
            else:
                modules.append(Depth_Wise(c1[i], c2[i], c3[i], residual=True, kernel=kernel, padding=padding,
                                          stride=stride, groups=groups))
        self.model = Sequential(*modules)

    def forward(self, x):
        return self.model(x)


def _blocks(keep, idx_pairs):
    c1 = [(keep[a], keep[a + 1]) for a in idx_pairs]
    c2 = [(keep[a + 1], keep[a + 2]) for a in idx_pairs]
    c3 = [(keep[a + 2], keep[a + 3]) for a in idx_pairs]
    return c1, c2, c3


class MiniFASNet(Module):
    def __init__(self, keep, embedding_size, conv6_kernel=(7, 7), drop_p=0.0, num_classes=3, img_channel=3,
                 se=False):
        super().__init__()
        self.embedding_size = embedding_size
        self.conv1 = Conv_block(img_channel, keep[0], kernel=(3, 3), stride=(2, 2), padding=(1, 1))
        self.conv2_dw = Conv_block(keep[0], keep[1], kernel=(3, 3), stride=(1, 1), padding=(1, 1), groups=keep[1])
        self.conv_23 = Depth_Wise((keep[1], keep[2]), (keep[2], keep[3]), (keep[3], keep[4]),
                                  kernel=(3, 3), stride=(2, 2), padding=(1, 1), groups=keep[3])
        res = ResidualSE if se else Residual
        c1, c2, c3 = _blocks(keep, [4, 7, 10, 13])
        self.conv_3 = res(c1, c2, c3, num_block=4, groups=keep[4], kernel=(3, 3), stride=(1, 1), padding=(1, 1))
        self.conv_34 = Depth_Wise((keep[16], keep[17]), (keep[17], keep[18]), (keep[18], keep[19]),
                                  kernel=(3, 3), stride=(2, 2), padding=(1, 1), groups=keep[19])
        c1, c2, c3 = _blocks(keep, [19, 22, 25, 28, 31, 34])
        self.conv_4 = res(c1, c2, c3, num_block=6, groups=keep[19], kernel=(3, 3), stride=(1, 1), padding=(1, 1))
        self.conv_45 = Depth_Wise((keep[37], keep[38]), (keep[38], keep[39]), (keep[39], keep[40]),
                                  kernel=(3, 3), stride=(2, 2), padding=(1, 1), groups=keep[40])
        c1, c2, c3 = _blocks(keep, [40, 43])
        self.conv_5 = res(c1, c2, c3, num_block=2, groups=keep[40], kernel=(3, 3), stride=(1, 1), padding=(1, 1))
        self.conv_6_sep = Conv_block(keep[46], keep[47], kernel=(1, 1), stride=(1, 1), padding=(0, 0))
        self.conv_6_dw = Linear_block(keep[47], keep[48], groups=keep[48], kernel=conv6_kernel, stride=(1, 1),
                                      padding=(0, 0))
        self.conv_6_flatten = Flatten()
        self.linear = Linear(512, embedding_size, bias=False)
        self.bn = BatchNorm1d(embedding_size)
        self.drop = torch.nn.Dropout(p=drop_p)
        self.prob = Linear(embedding_size, num_classes, bias=False)

    def forward(self, x):
        out = self.conv1(x)
        out = self.conv2_dw(out)
        out = self.conv_23(out)
        out = self.conv_3(out)
        out = self.conv_34(out)
        out = self.conv_4(out)
        out = self.conv_45(out)
        out = self.conv_5(out)
        out = self.conv_6_sep(out)
        out = self.conv_6_dw(out)
        out = self.conv_6_flatten(out)
        if self.embedding_size != 512:
            out = self.linear(out)
        out = self.bn(out)
        out = self.drop(out)
        return self.prob(out)


KEEP = {
    '1.8M': [32, 32, 103, 103, 64, 13, 13, 64, 26, 26, 64, 13, 13, 64, 52, 52, 64, 231, 231, 128,
             154, 154, 128, 52, 52, 128, 26, 26, 128, 52, 52, 128, 26, 26, 128, 26, 26, 128, 308, 308,
             128, 26, 26, 128, 26, 26, 128, 512, 512],
    '1.8M_': [32, 32, 103, 103, 64, 13, 13, 64, 13, 13, 64, 13, 13, 64, 13, 13, 64, 231, 231, 128,
              231, 231, 128, 52, 52, 128, 26, 26, 128, 77, 77, 128, 26, 26, 128, 26, 26, 128, 308, 308,
              128, 26, 26, 128, 26, 26, 128, 512, 512],
}

# ชื่อไฟล์ → (คลาสโมเดล, keep, ใช้ SE ไหม)
MODELS = {
    '2.7_80x80_MiniFASNetV2.pth': ('1.8M_', False),
    '4_0_0_80x80_MiniFASNetV1SE.pth': ('1.8M', True),
}


def build(name: str) -> MiniFASNet:
    keep_key, se = MODELS[name]
    # kernel ของ conv_6 = ((80+15)//16, (80+15)//16) = (5, 5) ตาม get_kernel() ของต้นฉบับ
    return MiniFASNet(KEEP[keep_key], embedding_size=128, conv6_kernel=(5, 5),
                      drop_p=0.75 if se else 0.2, num_classes=3, img_channel=3, se=se)


def load_weights(model: Module, path: str) -> None:
    # weights_only=True = ไม่รันโค้ด pickle แปลกปลอม (ไฟล์ตรวจ sha256 แล้วก็จริง แต่กันไว้อีกชั้น)
    state = torch.load(path, map_location='cpu', weights_only=True)
    clean = OrderedDict((k[7:] if k.startswith('module.') else k, v) for k, v in state.items())
    model.load_state_dict(clean, strict=True)


def export(models_dir: str) -> None:
    import onnxruntime as ort

    src_dir = os.path.join(models_dir, 'minifasnet')
    for name in MODELS:
        pth = os.path.join(src_dir, name)
        out = os.path.join(src_dir, name.replace('.pth', '.onnx'))
        model = build(name)
        load_weights(model, pth)
        model.eval()
        dummy = torch.rand(1, 3, 80, 80) * 255.0
        kwargs = dict(input_names=['input'], output_names=['logits'], opset_version=13,
                      do_constant_folding=True)
        try:
            torch.onnx.export(model, (dummy,), out, dynamo=False, **kwargs)
        except TypeError:  # torch รุ่นเก่าไม่มีพารามิเตอร์ dynamo
            torch.onnx.export(model, (dummy,), out, **kwargs)

        # ตรวจว่า ONNX ให้ผลเท่ากับ PyTorch จริง (กันแปลงผิดเงียบๆ)
        sess = ort.InferenceSession(out, providers=['CPUExecutionProvider'])
        rng = np.random.default_rng(0)
        for _ in range(3):
            x = (rng.random((1, 3, 80, 80), dtype=np.float32) * 255.0).astype(np.float32)
            with torch.no_grad():
                ref = model(torch.from_numpy(x)).numpy()
            got = sess.run(None, {'input': x})[0]
            diff = float(np.max(np.abs(ref - got)))
            if diff > 1e-3:
                raise SystemExit(f'ONNX mismatch for {name}: max diff {diff}')
        print(f'[convert] {name} -> {os.path.basename(out)} OK')


if __name__ == '__main__':
    ap = argparse.ArgumentParser()
    ap.add_argument('--models-dir', default=os.environ.get('EKYC_MODEL_DIR', '/opt/ekyc/models'))
    args = ap.parse_args()
    export(args.models_dir)
    sys.exit(0)

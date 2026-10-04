# -*- coding: utf-8 -*-
"""
ดาวน์โหลดโมเดลทั้งหมดของ eKYC AI ตอน build image (runtime ห้ามต่อเน็ต)

- ทุก URL ปักเวอร์ชัน/commit ตายตัว และทุกไฟล์ตรวจ sha256 — ไม่ตรง = build ล้มทันที
- ไฟล์ zip ของ EasyOCR ตรวจ sha256 ทั้งตัว zip และไฟล์ .pth ที่แตกออกมา
- รันซ้ำได้: ไฟล์ที่มีอยู่แล้วและ sha256 ตรงจะข้าม
- เขียน <models-dir>/manifest.json ไว้ให้ /health รายงานเวอร์ชันโมเดล

ใช้ stdlib ล้วน (urllib/hashlib/zipfile) จะได้รันได้ก่อนติดตั้งแพ็กเกจอื่น

    python scripts/download_models.py --models-dir /opt/ekyc/models
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
import shutil
import sys
import tempfile
import time
import urllib.request
import zipfile

OPENCV_ZOO = 'https://github.com/opencv/opencv_zoo/raw/47534e27c9851bb1128ccc0102f1145e27f23f98/models'
SFAS = ('https://raw.githubusercontent.com/minivision-ai/Silent-Face-Anti-Spoofing/'
        'b6d5f04ad78778917853b25c778acef6d5626d15/resources/anti_spoof_models')
EASYOCR = 'https://github.com/JaidedAI/EasyOCR/releases/download/pre-v1.1.6'

# ห้ามแก้ sha256 โดยไม่ได้ตรวจไฟล์ใหม่ด้วยตัวเอง — ดู MODELS.md
MODELS = [
    {
        'key': 'yunet',
        'version': 'face_detection_yunet_2023mar',
        'license': 'MIT',
        'url': f'{OPENCV_ZOO}/face_detection_yunet/face_detection_yunet_2023mar.onnx',
        'sha256': '8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4',
        'dest': 'opencv/face_detection_yunet_2023mar.onnx',
    },
    {
        'key': 'sface',
        'version': 'face_recognition_sface_2021dec',
        'license': 'Apache-2.0',
        'url': f'{OPENCV_ZOO}/face_recognition_sface/face_recognition_sface_2021dec.onnx',
        'sha256': '0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79',
        'dest': 'opencv/face_recognition_sface_2021dec.onnx',
    },
    {
        'key': 'face_landmarker',
        'version': 'face_landmarker/float16/1',
        'license': 'Apache-2.0',
        'url': ('https://storage.googleapis.com/mediapipe-models/face_landmarker/'
                'face_landmarker/float16/1/face_landmarker.task'),
        'sha256': '64184e229b263107bc2b804c6625db1341ff2bb731874b0bcc2fe6544e0bc9ff',
        'dest': 'mediapipe/face_landmarker.task',
    },
    {
        'key': 'easyocr_craft',
        'version': 'craft_mlt_25k',
        'license': 'MIT (CRAFT weights, NAVER) / Apache-2.0 (EasyOCR)',
        'url': f'{EASYOCR}/craft_mlt_25k.zip',
        'sha256': '8dc6a1c703a89ed56308ef742d26ebd45c656248cbbbda6e7fe60e569f873e65',
        'zip_member': 'craft_mlt_25k.pth',
        'member_sha256': '4a5efbfb48b4081100544e75e1e2b57f8de3d84f213004b14b85fd4b3748db17',
        'dest': 'easyocr/craft_mlt_25k.pth',
    },
    {
        'key': 'easyocr_thai',
        'version': 'thai_g1',
        'license': 'Apache-2.0',
        'url': f'{EASYOCR}/thai.zip',
        'sha256': '4f26ffd98dd28cca89ff6fe0bc761ab865c2b38fabd76a3ce2143d1d88c0d7f1',
        'zip_member': 'thai.pth',
        'member_sha256': '3ebbb9321b9c7c694e169cd23cc37f3c0bd2a160f5b539602bbef80e663e0a17',
        'dest': 'easyocr/thai.pth',
    },
    {
        'key': 'minifasnet_v2',
        'version': '2.7_80x80_MiniFASNetV2',
        'license': 'Apache-2.0',
        'url': f'{SFAS}/2.7_80x80_MiniFASNetV2.pth',
        'sha256': 'a5eb02e1843f19b5386b953cc4c9f011c3f985d0ee2bb9819eea9a142099bec0',
        'dest': 'minifasnet/2.7_80x80_MiniFASNetV2.pth',
    },
    {
        'key': 'minifasnet_v1se',
        'version': '4_0_0_80x80_MiniFASNetV1SE',
        'license': 'Apache-2.0',
        'url': f'{SFAS}/4_0_0_80x80_MiniFASNetV1SE.pth',
        'sha256': '84ee1d37d96894d5e82de5a57df044ef80a58be2b218b5ed7cdfd875ec2f5990',
        'dest': 'minifasnet/4_0_0_80x80_MiniFASNetV1SE.pth',
    },
]


def sha256_of(path: str) -> str:
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(1 << 20), b''):
            h.update(chunk)
    return h.hexdigest()


def fetch(url: str, out_path: str, attempts: int = 4) -> None:
    """ดาวน์โหลดแบบ stream ลงไฟล์ชั่วคราวแล้วค่อยย้าย — ลองใหม่สูงสุด 4 ครั้ง (2s, 4s, 8s)"""
    last_err = None
    for i in range(attempts):
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'thaiprompt-ekyc-build/1.0'})
            with urllib.request.urlopen(req, timeout=120) as resp, open(out_path, 'wb') as f:
                shutil.copyfileobj(resp, f, 1 << 20)
            return
        except Exception as e:  # noqa: BLE001 — เครือข่ายล้มได้หลายแบบ
            last_err = e
            if i < attempts - 1:
                time.sleep(2 ** (i + 1))
    raise SystemExit(f'[models] download failed: {url}: {last_err}')


def ensure(model: dict, models_dir: str) -> None:
    dest = os.path.join(models_dir, model['dest'])
    want = model.get('member_sha256') or model['sha256']
    if os.path.exists(dest) and sha256_of(dest) == want:
        print(f"[models] {model['key']}: cached")
        return
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with tempfile.TemporaryDirectory(dir=models_dir) as tmp:
        raw = os.path.join(tmp, 'download')
        fetch(model['url'], raw)
        got = sha256_of(raw)
        if got != model['sha256']:
            raise SystemExit(f"[models] SHA256 MISMATCH {model['key']}: got {got}, want {model['sha256']}")
        if 'zip_member' in model:
            with zipfile.ZipFile(raw) as z:
                # แตกเฉพาะไฟล์ที่ระบุชื่อ (กัน zip-slip / ไฟล์แปลกปลอม)
                with z.open(model['zip_member']) as src, open(dest + '.part', 'wb') as dst:
                    shutil.copyfileobj(src, dst, 1 << 20)
            got = sha256_of(dest + '.part')
            if got != model['member_sha256']:
                os.remove(dest + '.part')
                raise SystemExit(f"[models] SHA256 MISMATCH {model['key']} member: got {got}")
            os.replace(dest + '.part', dest)
        else:
            shutil.move(raw, dest)
    print(f"[models] {model['key']}: ok ({os.path.getsize(dest) // 1024} KiB)")


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--models-dir', default=os.environ.get('EKYC_MODEL_DIR', '/opt/ekyc/models'))
    args = ap.parse_args()
    os.makedirs(args.models_dir, exist_ok=True)
    for m in MODELS:
        ensure(m, args.models_dir)
    manifest = {m['key']: {'version': m['version'], 'sha256': m.get('member_sha256') or m['sha256'],
                           'license': m['license'], 'file': m['dest']} for m in MODELS}
    with open(os.path.join(args.models_dir, 'manifest.json'), 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    print('[models] all models verified')
    return 0


if __name__ == '__main__':
    sys.exit(main())

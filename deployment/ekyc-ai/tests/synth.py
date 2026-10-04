# -*- coding: utf-8 -*-
"""
สร้างภาพ "บัตรประชาชนไทยปลอม" สังเคราะห์สำหรับเทสต์ (ไม่ใช่บัตรจริง ไม่มีข้อมูลบุคคลจริง)

- ฟอนต์: Anuphan / Noto Sans Thai (SIL OFL) จาก repo — ไม่เจอ = เทสต์ที่ต้องใช้จะ skip
- รูปหน้า: skimage.data.astronaut() — ภาพนักบินอวกาศ Eileen Collins ของ NASA (public domain)
  ไม่มี scikit-image = ใช้กรอบสีเทาแทนรูปหน้า
"""
from __future__ import annotations

import os
from dataclasses import dataclass

import cv2
import numpy as np
from PIL import Image, ImageDraw, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
REPO = os.path.abspath(os.path.join(HERE, '..', '..', '..'))
FONT_CANDIDATES = [
    os.environ.get('EKYC_TEST_FONT', ''),
    os.path.join(REPO, 'thaiprompt', 'assets', 'fonts', 'Anuphan_500Medium.ttf'),
    os.path.join(REPO, 'thaiprompt', 'assets', 'fonts', 'Anuphan_400Regular.ttf'),
    os.path.join(REPO, 'resources', 'fonts', 'NotoSansThai-Bold.ttf'),
]
FONT_BOLD_CANDIDATES = [
    os.path.join(REPO, 'thaiprompt', 'assets', 'fonts', 'Anuphan_700Bold.ttf'),
    os.path.join(REPO, 'resources', 'fonts', 'NotoSansThai-Bold.ttf'),
]


def find_font(bold: bool = False) -> str | None:
    for p in (FONT_BOLD_CANDIDATES if bold else []) + FONT_CANDIDATES:
        if p and os.path.isfile(p):
            return p
    return None


def make_id(prefix12: str) -> str:
    total = sum(int(prefix12[i]) * (13 - i) for i in range(12))
    return prefix12 + str((11 - total % 11) % 10)


def fmt_id(id13: str) -> str:
    return f'{id13[0]} {id13[1:5]} {id13[5:10]} {id13[10:12]} {id13[12]}'


def astronaut_bgr() -> np.ndarray | None:
    try:
        import skimage.data
        return cv2.cvtColor(skimage.data.astronaut(), cv2.COLOR_RGB2BGR)
    except Exception:  # noqa: BLE001
        return None


def astronaut_portrait() -> np.ndarray | None:
    """ครอปแบบรูปติดบัตร (หัวไหล่ขึ้นไป) จากภาพ astronaut 512×512"""
    a = astronaut_bgr()
    return None if a is None else a[10:270, 105:305].copy()


@dataclass
class CardSpec:
    id13: str = make_id('110370207181')
    title_th: str = 'นาย'
    first_th: str = 'สมชาย'
    last_th: str = 'ใจดี'
    title_en: str = 'Mr.'
    first_en: str = 'Somchai'
    last_en: str = 'Jaidee'
    birth_th: str = '12 ม.ค. 2538'
    birth_en: str = '12 Jan. 1995'
    issue_th: str = '1 ม.ค. 2563'
    issue_en: str = '1 Jan. 2020'
    expiry_th: str = '11 ม.ค. 2572'
    expiry_en: str = '11 Jan. 2029'
    face: np.ndarray | None = None       # BGR รูปติดบัตร (None = astronaut)


def render_card(spec: CardSpec | None = None, width: int = 1600) -> np.ndarray:
    """วาดบัตรตรงๆ (เหมือน ML Kit ครอป/ปรับมุมมาแล้ว) คืน BGR"""
    spec = spec or CardSpec()
    font_path = find_font()
    bold_path = find_font(bold=True)
    if not font_path:
        raise RuntimeError('no Thai font found')
    W = width
    H = round(W / 1.586)
    # พื้นหลังไล่สีฟ้าอ่อน → ชมพูอ่อน + วงกลมจางๆ (ความถี่ต่ำ ไม่ให้เกิดยอด FFT ปลอม)
    xs = np.linspace(0, 1, W, dtype=np.float32)[None, :]
    ys = np.linspace(0, 1, H, dtype=np.float32)[:, None]
    base = np.zeros((H, W, 3), np.float32)
    base[..., 0] = 236 - 20 * xs + 6 * ys          # B
    base[..., 1] = 226 + 6 * xs - 4 * ys           # G
    base[..., 2] = 214 + 24 * xs                   # R
    yy, xx = np.mgrid[0:H, 0:W]
    for cx, cy, r, col in ((0.30, 0.55, 0.35, (12, -6, -10)), (0.75, 0.30, 0.25, (-8, 4, 14))):
        d = np.sqrt(((xx / W - cx) * 1.586) ** 2 + (yy / H - cy) ** 2)
        mask = np.clip(1 - d / r, 0, 1)[..., None]
        base += mask * np.array(col, np.float32)
    img = Image.fromarray(cv2.cvtColor(np.clip(base, 0, 255).astype(np.uint8), cv2.COLOR_BGR2RGB))
    dr = ImageDraw.Draw(img)

    def F(size_frac: float, bold: bool = False) -> ImageFont.FreeTypeFont:
        return ImageFont.truetype(bold_path if (bold and bold_path) else font_path, max(8, round(H * size_frac)))

    ink = (25, 25, 40)
    blue = (20, 40, 110)

    def T(x, y, text, size, bold=False, color=ink):
        dr.text((round(W * x), round(H * y)), text, font=F(size, bold), fill=color)

    # ตำแหน่งตามแม่แบบบัตรจริงด้านหน้า (สัดส่วนจากแม่แบบ ROI ของ ThaiPersonalCardExtract, Apache-2.0)
    T(0.17, 0.015, 'บัตรประจำตัวประชาชน', 0.062, True, blue)
    T(0.56, 0.025, 'Thai National ID Card', 0.056, True, blue)
    T(0.17, 0.100, 'เลขประจำตัวประชาชน', 0.034, False, blue)
    T(0.17, 0.148, 'Identification Number', 0.032, True, blue)
    T(0.44, 0.110, fmt_id(spec.id13), 0.074, True)
    T(0.05, 0.225, 'ชื่อตัวและชื่อสกุล', 0.038, False, blue)
    T(0.29, 0.200, f'{spec.title_th} {spec.first_th} {spec.last_th}', 0.064, True)
    T(0.31, 0.318, 'Name', 0.034, True, blue)
    T(0.38, 0.305, f'{spec.title_en} {spec.first_en}', 0.050, True)
    T(0.31, 0.390, 'Last name', 0.034, True, blue)
    T(0.45, 0.378, spec.last_en, 0.050, True)
    T(0.34, 0.448, 'เกิดวันที่', 0.040, False, blue)
    T(0.44, 0.438, spec.birth_th, 0.054, True)
    T(0.34, 0.530, 'Date of Birth', 0.034, True, blue)
    T(0.50, 0.518, spec.birth_en, 0.050, True)
    T(0.38, 0.598, 'ศาสนา', 0.038, False, blue)
    T(0.45, 0.592, 'พุทธ', 0.044)
    T(0.05, 0.668, 'ที่อยู่', 0.038, False, blue)
    T(0.12, 0.645, '99/1 หมู่ที่ 1 ต.บางรัก อ.บางรัก', 0.044)
    T(0.12, 0.718, 'จ.กรุงเทพมหานคร', 0.044)
    T(0.08, 0.792, spec.issue_th, 0.044, True)
    T(0.05, 0.848, 'วันออกบัตร', 0.032, False, blue)
    T(0.08, 0.884, spec.issue_en, 0.042, True)
    T(0.09, 0.935, 'Date of Issue', 0.030, True, blue)
    T(0.29, 0.930, 'เจ้าพนักงานออกบัตร', 0.034, False, blue)
    T(0.56, 0.792, spec.expiry_th, 0.044, True)
    T(0.56, 0.848, 'วันบัตรหมดอายุ', 0.032, False, blue)
    T(0.56, 0.884, spec.expiry_en, 0.042, True)
    T(0.56, 0.935, 'Date of Expiry', 0.030, True, blue)
    # ชิปทองคำ
    dr.rounded_rectangle((round(W * 0.09), round(H * 0.33), round(W * 0.25), round(H * 0.55)), radius=round(H * 0.03),
                         fill=(212, 175, 90), outline=(150, 120, 60), width=3)

    out = cv2.cvtColor(np.asarray(img), cv2.COLOR_RGB2BGR)
    # รูปติดบัตรมุมขวาล่าง
    face = spec.face if spec.face is not None else astronaut_portrait()
    x0, y0, x1, y1 = round(W * 0.745), round(H * 0.47), round(W * 0.97), round(H * 0.93)
    if face is not None:
        fh, fw = face.shape[:2]
        # ครอปให้ได้สัดส่วนกรอบรูป (แนวตั้ง) โดยเน้นส่วนบนกลางภาพ
        tw, th = x1 - x0, y1 - y0
        r = tw / th
        if fw / fh > r:
            cw = round(fh * r)
            cx0 = max(0, (fw - cw) // 2 - fw // 10)
            crop = face[:, cx0:cx0 + cw]
        else:
            ch = round(fw / r)
            crop = face[:ch, :]
        out[y0:y1, x0:x1] = cv2.resize(crop, (tw, th), interpolation=cv2.INTER_AREA)
    else:
        out[y0:y1, x0:x1] = 150
    return out


def round_corners(card: np.ndarray, radius_frac: float = 0.04) -> np.ndarray:
    """คืน alpha mask ของบัตรมุมโค้ง"""
    H, W = card.shape[:2]
    r = round(min(H, W) * radius_frac)
    m = np.zeros((H, W), np.uint8)
    cv2.rectangle(m, (r, 0), (W - 1 - r, H - 1), 255, -1)
    cv2.rectangle(m, (0, r), (W - 1, H - 1 - r), 255, -1)
    for cx, cy in ((r, r), (W - 1 - r, r), (r, H - 1 - r), (W - 1 - r, H - 1 - r)):
        cv2.circle(m, (cx, cy), r, 255, -1)
    return m


def place_in_scene(card: np.ndarray, scene_w: int = 1600, scene_h: int = 1200, fill: float = 0.62,
                   tilt: float = 0.05, seed: int = 1) -> tuple[np.ndarray, np.ndarray]:
    """วางบัตรบนโต๊ะไม้สีเข้ม + มุมกล้องเอียงเล็กน้อย คืน (ภาพ, 4 มุมจริง)"""
    rng = np.random.default_rng(seed)
    yy, xx = np.mgrid[0:scene_h, 0:scene_w].astype(np.float32)
    wood = 70 + 18 * np.sin(xx / 37.0 + 3 * np.sin(yy / 90.0)) + rng.normal(0, 4, (scene_h, scene_w))
    scene = np.stack([wood * 0.55, wood * 0.75, wood], axis=-1)
    scene = np.clip(scene, 0, 255).astype(np.uint8)
    H, W = card.shape[:2]
    cw = scene_w * fill
    ch = cw / (W / H)
    cx, cy = scene_w / 2, scene_h / 2
    dst = np.array([[cx - cw / 2 + tilt * cw, cy - ch / 2],
                    [cx + cw / 2 - tilt * cw * 0.5, cy - ch / 2 + tilt * ch],
                    [cx + cw / 2, cy + ch / 2],
                    [cx - cw / 2 - tilt * cw * 0.3, cy + ch / 2 - tilt * ch * 0.5]], np.float32)
    src = np.array([[0, 0], [W - 1, 0], [W - 1, H - 1], [0, H - 1]], np.float32)
    M = cv2.getPerspectiveTransform(src, dst)
    warped = cv2.warpPerspective(card, M, (scene_w, scene_h))
    alpha = cv2.warpPerspective(round_corners(card), M, (scene_w, scene_h)).astype(np.float32)[..., None] / 255
    out = (warped * alpha + scene * (1 - alpha))
    out = out + rng.normal(0, 2.0, out.shape)
    return np.clip(out, 0, 255).astype(np.uint8), dst


def add_glare(img: np.ndarray, frac: float = 0.10) -> np.ndarray:
    out = img.copy()
    H, W = out.shape[:2]
    cv2.ellipse(out, (round(W * 0.45), round(H * 0.45)), (round(W * frac * 1.2), round(H * frac)), 15, 0, 360,
                (255, 255, 255), -1)
    return out


def photocopy(img: np.ndarray) -> np.ndarray:
    g = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    g = cv2.convertScaleAbs(g, alpha=1.3, beta=-20)
    return cv2.cvtColor(g, cv2.COLOR_GRAY2BGR)


def screen_recapture(img: np.ndarray, period: float = 3.1, angle_deg: float = 8.0) -> np.ndarray:
    """จำลองการถ่ายบัตรจากหน้าจอ: ลายตารางพิกเซล + moiré + สีอมฟ้า + คอนทราสต์ตก"""
    H, W = img.shape[:2]
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    a = np.deg2rad(angle_deg)
    u = xx * np.cos(a) + yy * np.sin(a)
    v = -xx * np.sin(a) + yy * np.cos(a)
    grid = 0.80 + 0.10 * np.cos(2 * np.pi * u / period) + 0.10 * np.cos(2 * np.pi * v / period)
    out = img.astype(np.float32) * grid[..., None]
    out = out * 0.85 + 25
    out[..., 0] += 12
    return np.clip(out, 0, 255).astype(np.uint8)


def jpeg(img: np.ndarray, q: int = 90) -> bytes:
    ok, buf = cv2.imencode('.jpg', img, [cv2.IMWRITE_JPEG_QUALITY, q])
    assert ok
    return buf.tobytes()

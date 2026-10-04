# -*- coding: utf-8 -*-
"""
หา/ตัด/ปรับมุมบัตร + วัดคุณภาพภาพ + คะแนน "บัตรจริง" แบบ heuristic

ขั้นตอนหาบัตร
1. ย่อภาพ → Canny (3 ชุด threshold) → dilate → contour → convex hull → approxPolyDP
   ได้ 4 มุม (หรือ minAreaRect ถ้ามุมโค้งมนทำให้ได้เกิน 4 จุด) ที่พื้นที่ ≥ 12% ของภาพ
   และสัดส่วนด้าน 1.25–2.0 (บัตร ID-1 = 85.60×53.98 มม. = 1.586)
2. เลือกสี่เหลี่ยมใหญ่สุด → perspective warp เป็น 1000×630 แนวนอน
3. ไม่เจอสี่เหลี่ยม แต่ภาพทั้งภาพสัดส่วนเหมือนบัตร (1.40–1.80) = มือถือครอปมาแล้ว
   (ML Kit document scanner) → ใช้ทั้งภาพ
4. กลับหัว/หมุน: บัตรไทยด้านหน้ามีรูปหน้าอยู่ "ครึ่งขวา" → ลองหมุน 0°/180° แล้วเลือกมุมที่เจอหน้าครึ่งขวา

card_real_score (1 = บัตรจริง, ต่ำ = น่าจะถ่ายจากจอ/สำเนา) — **heuristic ไม่ใช่โมเดลที่เทรน**
- moire: ถ่ายจากจอมือถือ/คอม จะเกิดลาย moiré/ตารางพิกเซล = ยอดแหลมผิดธรรมชาติในสเปกตรัม FFT
  ช่วงความถี่สูง (0.12–0.45 รอบ/พิกเซล) เทียบกับพื้นหลังสเปกตรัมที่ปรับเรียบแล้ว
- copy: สำเนาขาวดำ/ซีร็อกซ์ สีหาย → colorfulness (Hasler & Süsstrunk 2003) ต่ำ
- glare: จอ/พลาสติกเคลือบสะท้อนแสงเป็นดวงขาวจ้า (V สูง, S ต่ำ)
- card_real = 1 − max(0.9·moire, copy, 0.5·glare)
"""
from __future__ import annotations

import math
from dataclasses import dataclass, field

import cv2
import numpy as np

CARD_W, CARD_H = 1000, 630
CARD_RATIO = 85.60 / 53.98


@dataclass
class CardView:
    image: np.ndarray            # BGR แนวนอน (1000×630 ถ้าเจอบัตร)
    quad_found: bool             # เจอขอบบัตร 4 มุมในภาพ
    precropped: bool             # ภาพทั้งภาพคือบัตรอยู่แล้ว
    complete: bool               # เห็นบัตรครบทั้ง 4 มุม ไม่ตกขอบภาพ
    rotation: int = 0            # องศาที่หมุนเพื่อให้บัตรตั้งตรง
    face: np.ndarray | None = None   # แถวผล YuNet ของรูปหน้าบนบัตร (พิกัดบน image)
    native_gray: np.ndarray | None = None  # ครอปกลางบัตรที่ความละเอียดเดิมของกล้อง (ใช้หา moiré)
    # เมทริกซ์ 3×3 แปลงพิกัดบน image → พิกัดบนภาพต้นฉบับที่ส่งเข้ามา (ใช้คืน card_face_box)
    to_src: np.ndarray = field(default_factory=lambda: np.eye(3))

    def face_box_in_source(self, scale: float = 1.0) -> list[int] | None:
        """กรอบรูปหน้าบนบัตร [x, y, w, h] เป็นพิกเซลของภาพต้นฉบับ (คูณ scale ถ้าภาพถูกย่อก่อนประมวลผล)"""
        if self.face is None:
            return None
        x, y, w, h = [float(v) for v in self.face[:4]]
        pts = np.array([[x, y, 1], [x + w, y, 1], [x + w, y + h, 1], [x, y + h, 1]], dtype=np.float64).T
        src = self.to_src @ pts
        src = src[:2] / src[2]
        x0, y0 = src.min(axis=1) * scale
        x1, y1 = src.max(axis=1) * scale
        x0, y0 = max(0.0, x0), max(0.0, y0)
        return [int(round(x0)), int(round(y0)), int(round(x1 - x0)), int(round(y1 - y0))]


def order_quad(pts: np.ndarray) -> np.ndarray:
    """เรียง 4 จุดเป็น บนซ้าย, บนขวา, ล่างขวา, ล่างซ้าย"""
    pts = np.asarray(pts, dtype=np.float32).reshape(4, 2)
    s = pts.sum(axis=1)
    d = np.diff(pts, axis=1).ravel()
    return np.array([pts[np.argmin(s)], pts[np.argmin(d)], pts[np.argmax(s)], pts[np.argmax(d)]],
                    dtype=np.float32)


def _quad_sides(q: np.ndarray) -> tuple[float, float]:
    tl, tr, br, bl = q
    w = (np.linalg.norm(tr - tl) + np.linalg.norm(br - bl)) / 2
    h = (np.linalg.norm(bl - tl) + np.linalg.norm(br - tr)) / 2
    return float(w), float(h)


def find_card_quad(img: np.ndarray) -> np.ndarray | None:
    """คืน 4 มุมของบัตร (พิกัดภาพต้นฉบับ, เรียงแล้ว) หรือ None"""
    h0, w0 = img.shape[:2]
    scale = min(1.0, 900.0 / max(h0, w0))
    small = cv2.resize(img, (round(w0 * scale), round(h0 * scale)), interpolation=cv2.INTER_AREA) \
        if scale < 1.0 else img
    hs, ws = small.shape[:2]
    area_img = float(hs * ws)
    gray = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)
    gray = cv2.GaussianBlur(gray, (5, 5), 0)
    med = float(np.median(gray))
    kernel = np.ones((3, 3), np.uint8)
    best: tuple[float, np.ndarray] | None = None
    for lo, hi in ((max(10, 0.66 * med), min(255, 1.33 * med)), (30, 90), (60, 180)):
        edges = cv2.Canny(gray, lo, hi)
        edges = cv2.dilate(edges, kernel, iterations=2)
        cnts, _ = cv2.findContours(edges, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
        cnts = sorted(cnts, key=cv2.contourArea, reverse=True)[:12]
        for c in cnts:
            hull = cv2.convexHull(c)
            area = cv2.contourArea(hull)
            if area < 0.12 * area_img:
                break
            if area > 0.985 * area_img:      # ขอบภาพเอง ไม่ใช่บัตร
                continue
            peri = cv2.arcLength(hull, True)
            approx = cv2.approxPolyDP(hull, 0.02 * peri, True)
            if len(approx) == 4 and cv2.isContourConvex(approx):
                quad = order_quad(approx)
            else:
                rect = cv2.minAreaRect(hull)
                rw, rh = rect[1]
                if rw * rh <= 0 or area / (rw * rh) < 0.88:   # ไม่ใช่สี่เหลี่ยม
                    continue
                quad = order_quad(cv2.boxPoints(rect))
            qw, qh = _quad_sides(quad)
            if min(qw, qh) < 1:
                continue
            ratio = max(qw, qh) / min(qw, qh)
            if not (1.25 <= ratio <= 2.0):
                continue
            if best is None or area > best[0]:
                best = (area, quad)
    if best is None:
        return None
    return best[1] / scale


def warp_card(img: np.ndarray, quad: np.ndarray) -> tuple[np.ndarray, np.ndarray]:
    """
    perspective warp → 1000×630 แนวนอน (ถ้าบัตรตั้งแนวตั้งในภาพ จะหมุนให้นอนก่อน)
    คืน (ภาพบัตร, เมทริกซ์แปลงพิกัดบัตร → พิกัดภาพต้นฉบับ)
    """
    q = order_quad(quad)
    w, h = _quad_sides(q)
    # บัตรในภาพใหญ่กว่าปลายทางมาก → ย่อแบบ INTER_AREA ก่อน (warpPerspective ไม่กันรอยหยัก/aliasing)
    k = min(1.0, 1.25 * CARD_W / max(w, h, 1.0))
    if k < 1.0:
        img = cv2.resize(img, None, fx=k, fy=k, interpolation=cv2.INTER_AREA)
        q = q * k
    if h > w:   # บัตรวางแนวตั้ง → ใช้ด้านยาวเป็นความกว้าง (หมุน 90°; ถูกทิศหรือไม่ตัดสินด้วยตำแหน่งหน้าทีหลัง)
        q = np.array([q[3], q[0], q[1], q[2]], dtype=np.float32)
    dst = np.array([[0, 0], [CARD_W - 1, 0], [CARD_W - 1, CARD_H - 1], [0, CARD_H - 1]], dtype=np.float32)
    m = cv2.getPerspectiveTransform(q, dst)
    warped = cv2.warpPerspective(img, m, (CARD_W, CARD_H), flags=cv2.INTER_LINEAR, borderMode=cv2.BORDER_REPLICATE)
    to_src = np.diag([1.0 / k, 1.0 / k, 1.0]) @ np.linalg.inv(m.astype(np.float64))
    return warped, to_src


def quad_complete(quad: np.ndarray, shape: tuple[int, ...]) -> bool:
    """มุมบัตรต้องไม่ชิด/เลยขอบภาพ (ถ้าชิด แปลว่าบัตรอาจโดนตัด)"""
    h, w = shape[:2]
    margin = 0.005 * min(h, w) + 1
    return bool(np.all(quad[:, 0] > margin) and np.all(quad[:, 0] < w - 1 - margin)
                and np.all(quad[:, 1] > margin) and np.all(quad[:, 1] < h - 1 - margin))


def native_center_crop(img: np.ndarray, quad: np.ndarray | None, size: int = 768) -> np.ndarray:
    """ครอปสี่เหลี่ยมจัตุรัสกลางบัตร (ครึ่งในของบัตร) ที่ความละเอียดเดิม — สำหรับวิเคราะห์ความถี่สูง"""
    h, w = img.shape[:2]
    if quad is not None:
        c = quad.mean(axis=0)
        inner = c + (quad - c) * 0.5
        x0, y0 = inner.min(axis=0)
        x1, y1 = inner.max(axis=0)
    else:
        x0, y0, x1, y1 = 0.25 * w, 0.25 * h, 0.75 * w, 0.75 * h
    side = int(max(16, min(size, x1 - x0, y1 - y0)))
    cx, cy = (x0 + x1) / 2, (y0 + y1) / 2
    xa = int(min(max(0, cx - side / 2), max(0, w - side)))
    ya = int(min(max(0, cy - side / 2), max(0, h - side)))
    return cv2.cvtColor(img[ya:ya + side, xa:xa + side], cv2.COLOR_BGR2GRAY)


def locate_card(img: np.ndarray) -> CardView:
    """หาบัตรในภาพ — ยังไม่แก้ทิศหมุน (ดู orient_by_face)"""
    quad = find_card_quad(img)
    if quad is not None:
        warped, to_src = warp_card(img, quad)
        return CardView(warped, True, False, quad_complete(quad, img.shape),
                        native_gray=native_center_crop(img, quad), to_src=to_src)
    h, w = img.shape[:2]
    r = w / float(h)
    native = native_center_crop(img, None)
    if 1.40 <= r <= 1.80:
        return CardView(cv2.resize(img, (CARD_W, CARD_H), interpolation=cv2.INTER_AREA), False, True, True,
                        native_gray=native, to_src=np.diag([w / CARD_W, h / CARD_H, 1.0]))
    if 1 / 1.80 <= r <= 1 / 1.40:
        rot = cv2.rotate(img, cv2.ROTATE_90_CLOCKWISE)
        # (u,v) บนบัตร → (xr,yr) บนภาพที่หมุนแล้ว (กว้าง h สูง w) → ต้นฉบับ x = yr, y = (h−1) − xr
        to_src = np.array([[0.0, w / CARD_H, 0.0], [-h / CARD_W, 0.0, h - 1.0], [0.0, 0.0, 1.0]])
        return CardView(cv2.resize(rot, (CARD_W, CARD_H), interpolation=cv2.INTER_AREA), False, True, True,
                        native_gray=native, to_src=to_src)
    # ไม่เจอบัตร — ส่งภาพเดิม (ย่อ) ไปให้ OCR ลองต่อ
    from .imaging import resize_max
    small = resize_max(img, 1280)
    k = small.shape[1] / float(w)
    return CardView(small, False, False, False, native_gray=native, to_src=np.diag([1.0 / k, 1.0 / k, 1.0]))


def orient_by_face(view: CardView, detect_faces, min_score: float) -> CardView:
    """
    เลือกทิศ 0° หรือ 180° โดยดูว่ารูปหน้าบนบัตรอยู่ครึ่งขวา (ด้านหน้าบัตรประชาชนไทย)
    detect_faces(img) → np.ndarray แถวละหน้า [x,y,w,h, 10 ค่า landmark, score]
    """
    card_like = view.quad_found or view.precropped
    cands = []
    for rot in ((0, 180) if card_like else (0,)):
        im = view.image if rot == 0 else cv2.rotate(view.image, cv2.ROTATE_180)
        faces = detect_faces(im)
        for f in (faces if faces is not None else []):
            if f[14] >= min_score:
                cands.append((rot, im, f))
    if not cands:
        return view
    right = [c for c in cands if card_like and c[2][0] + c[2][2] / 2 > 0.5 * c[1].shape[1]]
    # ไม่มีหน้าครึ่งขวาในทั้งสองทิศ → ไม่หมุน ใช้หน้าที่ใหญ่สุดในทิศเดิม
    pool = right or [c for c in cands if c[0] == 0]
    if not pool:
        return view
    rot, im, f = max(pool, key=lambda c: (float(c[2][2] * c[2][3]), float(c[2][14])))
    to_src = view.to_src
    if rot == 180:   # จุด (u,v) บนภาพที่หมุน 180° = (W−1−u, H−1−v) บนภาพก่อนหมุน
        H, W = view.image.shape[:2]
        to_src = to_src @ np.array([[-1.0, 0.0, W - 1.0], [0.0, -1.0, H - 1.0], [0.0, 0.0, 1.0]])
    return CardView(im, view.quad_found, view.precropped, view.complete, rot, f, view.native_gray, to_src)


# ---------------------------------------------------------------------------
# คุณภาพภาพ
# ---------------------------------------------------------------------------
def blur_score(gray: np.ndarray) -> tuple[float, float]:
    """คืน (blur 0-1 โดย 1 = เบลอมาก, ค่า Laplacian variance ดิบ) — วัดบนภาพบัตร 1000 px"""
    var = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    hi, lo = math.log(400.0), math.log(30.0)
    b = (hi - math.log(var + 1.0)) / (hi - lo)
    return float(min(1.0, max(0.0, b))), var


def glare_score(bgr: np.ndarray) -> tuple[float, float]:
    """คืน (glare 0-1, สัดส่วนพื้นที่แสงสะท้อน) — ดวงขาวจ้า V≥250 และสีซีด S≤40 ที่เป็นก้อน"""
    hsv = cv2.cvtColor(bgr, cv2.COLOR_BGR2HSV)
    mask = ((hsv[..., 2] >= 250) & (hsv[..., 1] <= 40)).astype(np.uint8)
    mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, np.ones((3, 3), np.uint8))
    n, _, stats, _ = cv2.connectedComponentsWithStats(mask, connectivity=8)
    area = float(mask.shape[0] * mask.shape[1])
    # นับเฉพาะดวงแสงเฉพาะที่ (0.1%–40% ของบัตร) — พื้นขาวทั้งใบ (เช่นกระดาษสำเนา) ไม่ใช่แสงสะท้อน
    min_blob, max_blob = 0.001 * area, 0.40 * area
    glare_px = sum(int(stats[i, cv2.CC_STAT_AREA]) for i in range(1, n)
                   if min_blob <= stats[i, cv2.CC_STAT_AREA] <= max_blob)
    ratio = glare_px / area
    return float(min(1.0, ratio / 0.04)), ratio


# ---------------------------------------------------------------------------
# heuristic บัตรจริง
# ---------------------------------------------------------------------------
def moire_score(gray: np.ndarray) -> tuple[float, float]:
    """
    ยอดแหลมในสเปกตรัมความถี่สูง (ลาย moiré จากการถ่ายหน้าจอ / ลายเม็ดสกรีนของสำเนาสี)
    คืน (score 0-1, ความสูงยอด (log-magnitude เหนือพื้นหลัง))
    """
    g = gray.astype(np.float32)
    h, w = g.shape
    # ใช้กลางภาพขนาดคงที่ 512×512 (ถ้าเล็กกว่าใช้เท่าที่มี) — ตัดขอบที่มีรอยต่อ warp
    s = min(512, h - (h % 2), w - (w % 2))
    y0, x0 = (h - s) // 2, (w - s) // 2
    g = g[y0:y0 + s, x0:x0 + s]
    g = g - g.mean()
    win = np.outer(np.hanning(s), np.hanning(s)).astype(np.float32)
    mag = np.log1p(np.abs(np.fft.fftshift(np.fft.fft2(g * win))))
    bg = cv2.GaussianBlur(mag, (0, 0), sigmaX=4)
    resid = mag - bg
    fy = (np.arange(s) - s // 2)[:, None] / float(s)
    fx = (np.arange(s) - s // 2)[None, :] / float(s)
    r = np.sqrt(fx ** 2 + fy ** 2)
    band = (r >= 0.12) & (r <= 0.45) & (np.abs(fx) > 2.0 / s) & (np.abs(fy) > 2.0 / s)
    if not band.any():
        return 0.0, 0.0
    vals = resid[band]
    # ใช้ค่าเฉลี่ยของ 8 ยอดสูงสุด (ทนกว่าค่าสูงสุดค่าเดียว)
    top = np.sort(vals)[-8:]
    peak = float(top.mean())
    return float(min(1.0, max(0.0, (peak - 1.9) / 1.2))), peak


def colorfulness(bgr: np.ndarray) -> float:
    """Hasler & Süsstrunk (2003) colorfulness — สำเนาขาวดำได้ค่า ~0–5, บัตรจริงสีพาสเทล+รูปสี ~15+"""
    b, g, r = [c.astype(np.float32) for c in cv2.split(bgr)]
    rg = r - g
    yb = 0.5 * (r + g) - b
    return float(math.sqrt(rg.std() ** 2 + yb.std() ** 2) + 0.3 * math.sqrt(rg.mean() ** 2 + yb.mean() ** 2))


def card_real_score(bgr: np.ndarray, glare: float, native_gray: np.ndarray | None = None) -> tuple[float, dict]:
    """native_gray = ครอปกลางบัตรความละเอียดเดิม (ลาย moiré ความถี่สูงหายไปหลังย่อภาพ) — ไม่มีใช้ภาพบัตร"""
    gray = native_gray if native_gray is not None and min(native_gray.shape[:2]) >= 128         else cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
    moire, peak = moire_score(gray)
    cf = colorfulness(bgr)
    copy = float(min(1.0, max(0.0, (15.0 - cf) / 10.0)))
    real = 1.0 - max(0.9 * moire, copy, 0.5 * glare)
    return float(min(1.0, max(0.0, real))), {'moire': round(moire, 3), 'moire_peak': round(peak, 3),
                                              'colorfulness': round(cf, 2), 'copy': round(copy, 3)}

# -*- coding: utf-8 -*-
"""
แยกข้อมูลบัตรประจำตัวประชาชนไทยจากผล OCR (โค้ด Python ล้วน — ไม่ต้องใช้โมเดล ทดสอบได้เร็ว)

- เลขบัตร 13 หลัก + checksum: ผลรวม (หลักที่ 1..12 × น้ำหนัก 13..2) mod 11, check = (11 - mod) % 10
- วันที่ไทย พ.ศ. ("12 ม.ค. 2538", "12 มกราคม 2538", "12 มค 2538") → ค.ศ. YYYY-MM-DD
- วันที่อังกฤษบนบัตร ("12 Jan. 1995") ใช้ตรวจไขว้กับวันที่ไทย
- "ตลอดชีพ" → วันหมดอายุ null + เหตุผล EXPIRY_LIFELONG
- ชื่อไทยพร้อมคำนำหน้า (นาย/นาง/นางสาว/ด.ช./ด.ญ./เด็กชาย/เด็กหญิง) และชื่อ/นามสกุลอังกฤษ

ห้าม log ข้อความ OCR ใดๆ ในไฟล์นี้
"""
from __future__ import annotations

import difflib
import re
import unicodedata
from dataclasses import dataclass
from datetime import date

THAI_DIGITS = str.maketrans('๐๑๒๓๔๕๖๗๘๙', '0123456789')

TH_MONTH_ABBR = {
    'มค': 1, 'กพ': 2, 'มีค': 3, 'เมย': 4, 'พค': 5, 'มิย': 6,
    'กค': 7, 'สค': 8, 'กย': 9, 'ตค': 10, 'พย': 11, 'ธค': 12,
}
TH_MONTH_FULL = {
    'มกราคม': 1, 'กุมภาพันธ์': 2, 'มีนาคม': 3, 'เมษายน': 4, 'พฤษภาคม': 5, 'มิถุนายน': 6,
    'กรกฎาคม': 7, 'สิงหาคม': 8, 'กันยายน': 9, 'ตุลาคม': 10, 'พฤศจิกายน': 11, 'ธันวาคม': 12,
}
EN_MONTH = {
    'jan': 1, 'feb': 2, 'mar': 3, 'apr': 4, 'may': 5, 'jun': 6,
    'jul': 7, 'aug': 8, 'sep': 9, 'oct': 10, 'nov': 11, 'dec': 12,
}
# คำนำหน้าชื่อไทย (เรียงตัวยาวก่อน — "นางสาว" ต้องมาก่อน "นาง")
TH_TITLES = [
    ('เด็กหญิง', 'เด็กหญิง'), ('เด็กชาย', 'เด็กชาย'), ('นางสาว', 'นางสาว'), ('น.ส.', 'นางสาว'),
    ('ด.ญ.', 'ด.ญ.'), ('ด.ช.', 'ด.ช.'), ('ดญ.', 'ด.ญ.'), ('ดช.', 'ด.ช.'),
    ('นาง', 'นาง'), ('นาย', 'นาย'),
]
# regex ของคำนำหน้า: ยอมให้มีช่องว่างแทรกระหว่างตัวอักษร (OCR ชอบเว้นวรรคหลังจุด)
_TITLE_RE = {raw: r'\s?'.join(re.escape(ch) for ch in raw) for raw, _ in TH_TITLES}
EN_TITLES = {'mr': 'Mr.', 'mrs': 'Mrs.', 'miss': 'Miss', 'ms': 'Ms.', 'master': 'Master'}

LIFELONG_TH = ('ตลอดชีพ', 'ตลอดชีวิต')
LIFELONG_EN = ('lifelong', 'life long')

_RE_TH_DATE = re.compile(r'(\d{1,2})\s*([ก-๎][ก-๎.\s]{0,14}?)\s*\.?\s*(\d{4})')
_RE_EN_DATE = re.compile(r'(\d{1,2})\s*([A-Za-z]{3,9})\s*[.,]?\s*(\d{4})')
_DIGIT_FIX = str.maketrans({'O': '0', 'o': '0', 'D': '0', 'Q': '0', 'I': '1', 'l': '1', '|': '1', 'i': '1',
                            '!': '1', 'Z': '2', 'z': '2', 'S': '5', 's': '5', 'B': '8', 'G': '6', 'b': '6'})


# ---------------------------------------------------------------------------
# เลขบัตร
# ---------------------------------------------------------------------------
def id_checksum_ok(id13: str | None) -> bool:
    """ตรวจ checksum เลขประจำตัวประชาชน 13 หลัก"""
    if not id13 or len(id13) != 13 or not id13.isdigit():
        return False
    total = sum(int(id13[i]) * (13 - i) for i in range(12))
    return (11 - total % 11) % 10 == int(id13[12])


def normalize_text(text: str) -> str:
    """NFC + เลขไทยเป็นเลขอารบิก + ช่องว่างซ้ำเหลือช่องเดียว"""
    t = unicodedata.normalize('NFC', text or '').translate(THAI_DIGITS)
    return re.sub(r'\s+', ' ', t).strip()


def _fix_digit_token(tok: str) -> str:
    """แก้ตัวอักษรที่ OCR อ่านพลาดเป็นตัวเลข เฉพาะ token ที่เป็นตัวเลขเกือบทั้งหมด"""
    if not tok:
        return tok
    digits = sum(ch.isdigit() for ch in tok)
    if digits >= max(2, int(0.6 * len(tok))):
        return tok.translate(_DIGIT_FIX)
    return tok


def id_candidates(text: str) -> list[str]:
    """ดึงเลข 13 หลักทุกชุดที่เป็นไปได้จากข้อความหนึ่งบรรทัด (ยอมให้มีช่องว่าง/ขีดคั่น)"""
    t = ' '.join(_fix_digit_token(tok) for tok in normalize_text(text).split(' '))
    joined = re.sub(r'(?<=\d)[\s\-–_.]+(?=\d)', '', t)
    out: list[str] = []
    for run in re.findall(r'\d{13,}', joined):
        if len(run) == 13:
            out.append(run)
        else:  # ติดกับเลขอื่น → ไล่หน้าต่าง 13 หลักที่ checksum ผ่าน
            out.extend(run[i:i + 13] for i in range(len(run) - 12) if id_checksum_ok(run[i:i + 13]))
    return out


# ---------------------------------------------------------------------------
# วันที่
# ---------------------------------------------------------------------------
def _year_to_ce(y: int) -> int | None:
    if 2400 <= y <= 2700:      # พ.ศ.
        return y - 543
    if 1880 <= y <= 2200:      # ค.ศ. (บรรทัดภาษาอังกฤษ)
        return y
    return None


def _th_month(token: str) -> int | None:
    tok = re.sub(r'[\s.]', '', token)
    if not tok:
        return None
    if tok in TH_MONTH_ABBR:
        return TH_MONTH_ABBR[tok]
    if tok in TH_MONTH_FULL:
        return TH_MONTH_FULL[tok]
    # ชื่อเต็มที่ OCR อ่านเพี้ยนเล็กน้อย — ใช้ fuzzy เฉพาะ token ยาว (ตัวย่อ 2-3 ตัวเสี่ยงจับผิดเดือน)
    if len(tok) >= 5:
        best = difflib.get_close_matches(tok, list(TH_MONTH_FULL), n=1, cutoff=0.75)
        if best:
            return TH_MONTH_FULL[best[0]]
    return None


def _mk_date(y: int, m: int, d: int) -> date | None:
    try:
        return date(y, m, d)
    except ValueError:
        return None


def parse_thai_date(text: str) -> date | None:
    """'12 ม.ค. 2538' / '12 มกราคม 2538' / '12 มค 2538' → date(1995, 1, 12)"""
    t = normalize_text(text)
    for m in _RE_TH_DATE.finditer(t):
        month = _th_month(m.group(2))
        year = _year_to_ce(int(m.group(3)))
        if month and year:
            d = _mk_date(year, month, int(m.group(1)))
            if d:
                return d
    return None


def parse_english_date(text: str) -> date | None:
    """'12 Jan. 1995' / '12 January 1995' → date(1995, 1, 12)"""
    t = normalize_text(text)
    for m in _RE_EN_DATE.finditer(t):
        month = EN_MONTH.get(m.group(2)[:3].lower())
        year = _year_to_ce(int(m.group(3)))
        if month and year:
            d = _mk_date(year, month, int(m.group(1)))
            if d:
                return d
    return None


def is_lifelong(text: str) -> bool:
    t = normalize_text(text)
    low = t.lower()
    return any(w in t for w in LIFELONG_TH) or any(w in low for w in LIFELONG_EN) or 'ตลอด' in t


# ---------------------------------------------------------------------------
# ชื่อ
# ---------------------------------------------------------------------------
_TH_LABELS = ('ชื่อตัวและชื่อสกุล', 'ชื่อตัวและชื่อ', 'ชื่อสกุล', 'ชื่อ')
# "ชื่อตัวและชื่อสกุล" แบบหลวม: วรรณยุกต์/สระบนล่างหายได้ เว้นวรรคได้ จบที่ "สกุล"
_RE_TH_NAME_LABEL = re.compile(r'^\s*ช[ัิ-ฺ็-๎]*อ\s*ต[ัิ-ฺ็-๎]*ว'
                               r'.{0,14}?ส\s*ก\s*[ุู]?\s*ล')


def parse_thai_name(text: str) -> dict | None:
    """'ชื่อตัวและชื่อสกุล นาย สมชาย ใจดี' → {title:'นาย', first:'สมชาย', last:'ใจดี'}"""
    t = normalize_text(text)
    # ป้าย "ชื่อตัวและชื่อสกุล" ที่ OCR อ่านติดกับคำนำหน้า/ตกวรรณยุกต์ เช่น "ชือตัวและชือสกุลนาย ..."
    m = _RE_TH_NAME_LABEL.match(t)
    if m:
        t = t[m.end():].strip()
    else:
        for lab in _TH_LABELS:
            idx = t.find(lab)
            if idx >= 0:
                t = (t[:idx] + ' ' + t[idx + len(lab):]).strip()
                break
    # เก็บเฉพาะอักษรไทย จุด และช่องว่าง
    t = re.sub(r'[^฀-๿.\s]', ' ', t)
    t = re.sub(r'\s+', ' ', t).strip()
    # รอบแรก: คำนำหน้าอยู่ต้นบรรทัด / รอบสอง: คำนำหน้าตัวแรกสุดที่ตามหลังช่องว่าง
    # (กรณีป้ายกำกับอ่านเพี้ยนจนตัดออกไม่ได้)
    for anchor in (r'^', r'(?:^|\s)'):
        found = []
        for order, (raw, norm) in enumerate(TH_TITLES):
            m = re.search(anchor + _TITLE_RE[raw], t)
            if m:
                found.append((m.start(), order, m.end(), norm))
        for _start, _order, end, norm in sorted(found):
            rest = t[end:].strip(' .')
            parts = [p for p in rest.split(' ') if p and p != '.']
            if parts:
                return {'title': norm, 'first': parts[0], 'last': ' '.join(parts[1:]) or None}
    return None


def _fuzzy_word(tok: str, word: str, cutoff: float = 0.75) -> bool:
    return difflib.SequenceMatcher(None, tok.lower(), word).ratio() >= cutoff


def parse_english_name_line(text: str) -> tuple[str, dict] | None:
    """
    'Name Mr. Somchai' → ('name', {title:'Mr.', first:'Somchai'})
    'Last name Jaidee' → ('last', {last:'Jaidee'})
    """
    t = normalize_text(text)
    toks = [x for x in re.split(r'[\s:]+', t) if x]
    if not toks:
        return None
    # "Last name ..." (หรือ "Lastname ...")
    if _fuzzy_word(toks[0], 'last', 0.7) or _fuzzy_word(toks[0], 'lastname', 0.8):
        rest = toks[1:]
        if rest and _fuzzy_word(rest[0], 'name', 0.7):
            rest = rest[1:]
        words = [w for w in rest if re.fullmatch(r"[A-Za-z][A-Za-z'\-]*", w)]
        return ('last', {'last': ' '.join(words) or None}) if words else None
    if _fuzzy_word(toks[0], 'name', 0.7):
        rest = toks[1:]
        title = None
        if rest:
            key = rest[0].rstrip('.').lower()
            if key in EN_TITLES:
                title = EN_TITLES[key]
                rest = rest[1:]
        words = [w for w in rest if re.fullmatch(r"[A-Za-z][A-Za-z'\-]*", w)]
        if not words:
            return None
        return ('name', {'title': title, 'first': ' '.join(words)})
    return None


# ---------------------------------------------------------------------------
# ประกอบผลทั้งใบ
# ---------------------------------------------------------------------------
@dataclass
class Box:
    text: str
    conf: float
    x0: float
    y0: float
    x1: float
    y1: float

    @property
    def cx(self) -> float:
        return (self.x0 + self.x1) / 2

    @property
    def cy(self) -> float:
        return (self.y0 + self.y1) / 2

    @property
    def h(self) -> float:
        return max(1.0, self.y1 - self.y0)


def _merge(ln: list[Box]) -> Box:
    total = sum(max(1, len(b.text)) for b in ln)
    conf = sum(b.conf * max(1, len(b.text)) for b in ln) / total
    return Box(' '.join(b.text for b in ln), conf, min(b.x0 for b in ln), min(b.y0 for b in ln),
               max(b.x1 for b in ln), max(b.y1 for b in ln))


def group_phrases(boxes: list[Box], gap_factor: float = 1.5) -> list[Box]:
    """
    รวมกล่องที่อยู่บรรทัดเดียวกันและห่างกันไม่เกิน gap_factor × ความสูงตัวอักษร
    (เช่น "12" "Jan." "1995" → "12 Jan. 1995") แต่ไม่รวมข้ามคอลัมน์ (วันออกบัตร | วันหมดอายุ)
    """
    out = []
    for ln in _line_groups(boxes):
        ln.sort(key=lambda b: b.x0)
        cur = [ln[0]]
        for b in ln[1:]:
            hh = (sum(x.h for x in cur) / len(cur) + b.h) / 2
            if b.x0 - max(x.x1 for x in cur) <= gap_factor * hh:
                cur.append(b)
            else:
                out.append(_merge(cur))
                cur = [b]
        out.append(_merge(cur))
    return out


def _line_groups(boxes: list[Box]) -> list[list[Box]]:
    items = sorted(boxes, key=lambda b: b.cy)
    lines: list[list[Box]] = []
    for b in items:
        if lines:
            cur = lines[-1]
            cy = sum(x.cy for x in cur) / len(cur)
            hh = sum(x.h for x in cur) / len(cur)
            if abs(b.cy - cy) <= 0.5 * max(hh, b.h):
                cur.append(b)
                continue
        lines.append([b])
    return lines


def group_lines(boxes: list[Box]) -> list[Box]:
    """รวมกล่อง OCR ที่อยู่บรรทัดเดียวกันเป็นกล่องเดียว (เรียงซ้าย→ขวา)"""
    out = []
    for ln in _line_groups(boxes):
        ln.sort(key=lambda b: b.x0)
        out.append(_merge(ln))
    return out


@dataclass
class _DateHit:
    d: date | None
    kind: str          # 'th' | 'en' | 'life'
    conf: float
    box: Box


def _collect_dates(boxes: list[Box]) -> list[_DateHit]:
    hits: list[_DateHit] = []
    for b in boxes:
        d = parse_thai_date(b.text)
        if d:
            hits.append(_DateHit(d, 'th', b.conf, b))
        e = parse_english_date(b.text)
        if e:
            hits.append(_DateHit(e, 'en', b.conf, b))
        if not d and not e and is_lifelong(b.text):
            hits.append(_DateHit(None, 'life', b.conf, b))
    return hits


def _has(text: str, *needles: str) -> bool:
    low = text.lower()
    return any(n in text or n in low for n in needles)


def _pick_date(hits: list[_DateHit]) -> tuple[date | None, float, bool, bool]:
    """เลือกวันที่จากกลุ่ม hit: คืน (วันที่, ความมั่นใจ OCR, ไทย/อังกฤษตรงกันไหม, ตลอดชีพไหม)"""
    if not hits:
        return None, 0.0, False, False
    life = [h for h in hits if h.kind == 'life']
    th = [h for h in hits if h.kind == 'th']
    en = [h for h in hits if h.kind == 'en']
    if life and not th and not en:
        return None, max(h.conf for h in life), False, True
    if th and en:
        for a in th:
            for b in en:
                if a.d == b.d:
                    return a.d, max(a.conf, b.conf), True, False
    best = max(th or en, key=lambda h: h.conf)
    return best.d, best.conf, False, False


def extract_fields(boxes: list[Box], width: float, height: float, today: date) -> dict:
    """
    boxes: กล่อง OCR บนภาพบัตรที่ตัด/ปรับมุมแล้ว (แนวนอน)
    คืน dict: fields, field_confidence, name_parts, id_checksum_ok, lifelong, reasons
    """
    W, H = float(width), float(height)
    norm = [Box(normalize_text(b.text), float(b.conf), b.x0, b.y0, b.x1, b.y1) for b in boxes if b.text]
    lines = group_lines(norm)

    # ---- เลขบัตร ----
    id_best: tuple[str, float, bool, float] | None = None   # (เลข, conf, checksum, y)
    for src in (norm, lines):
        for b in src:
            for cand in id_candidates(b.text):
                ok = id_checksum_ok(cand)
                score = (2 if ok else 0) + (1 if b.cy < 0.5 * H else 0) + b.conf
                if id_best is None or score > id_best[3]:
                    id_best = (cand, b.conf, ok, score)
    id_number = id_best[0] if id_best else None
    checksum = bool(id_best and id_best[2])
    id_conf = (0.5 * id_best[1] + 0.5 * (1.0 if checksum else 0.0)) if id_best else 0.0

    # ---- ชื่อไทย ----
    th_name = None
    th_conf = 0.0
    for b in sorted(lines, key=lambda x: x.cy):
        if b.cy > 0.75 * H:
            continue
        p = parse_thai_name(b.text)
        if p:
            th_name, th_conf = p, b.conf
            break
    name_th = None
    name_th_conf = 0.0
    if th_name:
        name_th = ' '.join(x for x in (th_name['title'], th_name['first'], th_name['last']) if x)
        structure = 1.0 if (th_name['first'] and th_name['last']) else 0.5
        name_th_conf = 0.6 * th_conf + 0.4 * structure

    # ---- ชื่ออังกฤษ ----
    en_parts: dict = {}
    en_confs: list[float] = []
    for b in lines:
        r = parse_english_name_line(b.text)
        if not r:
            continue
        kind, val = r
        if kind == 'name' and 'first' not in en_parts:
            en_parts.update(val)
            en_confs.append(b.conf)
        elif kind == 'last' and 'last' not in en_parts:
            en_parts.update(val)
            en_confs.append(b.conf)
    name_en = None
    if en_parts.get('first') or en_parts.get('last'):
        name_en = ' '.join(x for x in (en_parts.get('title'), en_parts.get('first'), en_parts.get('last')) if x)
    name_en_conf = (sum(en_confs) / len(en_confs)) if en_confs else 0.0

    # ---- วันที่ ----
    # วันที่: อ่านจากทั้ง "วลี" (กล่องติดกันในบรรทัด — วันที่ที่ถูกตัดเป็นหลายกล่อง) และกล่องเดี่ยว
    # (กรณีวลีเดียวมีสองวันที่) แล้วตัดตัวซ้ำ
    hits = []
    for h in _collect_dates(group_phrases(norm)) + _collect_dates(norm):
        if not any(o.kind == h.kind and o.d == h.d and abs(o.box.cx - h.box.cx) < 0.15 * W
                   and abs(o.box.cy - h.box.cy) < 0.05 * H for o in hits):
            hits.append(h)

    birth_labels = [b for b in norm if _has(b.text, 'เกิด', 'birth')]
    issue_labels = [b for b in norm if _has(b.text, 'ออกบัตร', 'issue')]
    expiry_labels = [b for b in norm if _has(b.text, 'หมดอายุ', 'expir')]

    def same_line(a: Box, b: Box) -> bool:
        return abs(a.cy - b.cy) <= 0.6 * max(a.h, b.h)

    birth_hits = [h for h in hits if h.kind != 'life' and (
        _has(h.box.text, 'เกิด', 'birth') or any(same_line(h.box, lb) and h.box.x0 >= lb.x0 for lb in birth_labels))]
    if not birth_hits:
        birth_hits = [h for h in hits if h.kind != 'life' and 0.30 * H <= h.box.cy <= 0.75 * H
                      and h.box.cx < 0.75 * W]
        # เลือกเฉพาะกลุ่มที่อยู่สูงสุด (วันเกิดอยู่เหนือวันออก/หมดอายุ)
        if birth_hits:
            top = min(h.box.cy for h in birth_hits)
            birth_hits = [h for h in birth_hits if h.box.cy <= top + 0.12 * H]
    birth, birth_ocr, birth_agree, _ = _pick_date(birth_hits)

    used = set(id(h) for h in birth_hits)
    bottom = [h for h in hits if id(h) not in used and h.box.cy >= 0.55 * H
              and (h.d is None or birth is None or h.d != birth)]

    def nearest_col(label_boxes: list[Box]) -> list[_DateHit]:
        if not label_boxes or not bottom:
            return []
        out = []
        for h in bottom:
            dx = min(abs(h.box.cx - lb.cx) for lb in label_boxes)
            if dx <= 0.15 * W:
                out.append(h)
        return out

    issue_hits = nearest_col(issue_labels)
    expiry_hits = [h for h in nearest_col(expiry_labels) if h not in issue_hits]
    if not issue_hits and not expiry_hits and bottom:
        # ไม่เจอป้ายกำกับ → แบ่งตามคอลัมน์ ซ้าย = วันออกบัตร, ขวา = วันหมดอายุ
        xs = sorted(h.box.cx for h in bottom)
        if len(xs) >= 2 and xs[-1] - xs[0] > 0.12 * W:
            mid = (xs[0] + xs[-1]) / 2
            issue_hits = [h for h in bottom if h.box.cx < mid]
            expiry_hits = [h for h in bottom if h.box.cx >= mid]
        else:
            only = bottom
            if any(h.kind == 'life' for h in only):
                expiry_hits = only
            elif only and only[0].d and only[0].d >= today:
                expiry_hits = only
            else:
                issue_hits = only
    issue, issue_ocr, issue_agree, _ = _pick_date(issue_hits)
    expiry, expiry_ocr, expiry_agree, lifelong = _pick_date(expiry_hits)
    if issue and expiry and issue > expiry:   # สลับคอลัมน์ผิด
        issue, expiry = expiry, issue
        issue_ocr, expiry_ocr = expiry_ocr, issue_ocr
        issue_agree, expiry_agree = expiry_agree, issue_agree

    # ---- ความมั่นใจ (OCR + หลักฐานตรวจสอบ) ----
    birth_conf = 0.0
    if birth:
        age = (today - birth).days / 365.25
        plausible = 1.0 if 0 <= age <= 120 else 0.0
        birth_conf = 0.5 * birth_ocr + 0.25 * plausible + 0.25 * (1.0 if birth_agree else 0.0)
    expiry_conf = 0.0
    if expiry or lifelong:
        consistent = 1.0 if (lifelong or (issue is not None and expiry is not None and expiry > issue)) else 0.5
        expiry_conf = 0.5 * expiry_ocr + 0.25 * consistent + 0.25 * (1.0 if (expiry_agree or lifelong) else 0.0)
    issue_conf = 0.0
    if issue:
        issue_conf = 0.5 * issue_ocr + 0.5 * (1.0 if issue_agree else 0.5)

    reasons: list[str] = []
    if id_number and not checksum:
        reasons.append('ID_CHECKSUM_FAIL')
    if lifelong:
        reasons.append('EXPIRY_LIFELONG')
    elif expiry and expiry < today:
        reasons.append('EXPIRED')

    fc = {
        'id_number': round(id_conf, 3),
        'name_th': round(name_th_conf, 3),
        'birth_date': round(birth_conf, 3),
        'expiry_date': round(expiry_conf, 3),
        'name_en': round(name_en_conf, 3),
        'issue_date': round(issue_conf, 3),
    }
    ocr_confidence = round((fc['id_number'] + fc['name_th'] + fc['birth_date'] + fc['expiry_date']) / 4.0, 3)
    return {
        'fields': {
            'id_number': id_number,
            'name_th': name_th,
            'name_en': name_en,
            'birth_date': birth.isoformat() if birth else None,
            'expiry_date': expiry.isoformat() if expiry else None,
            'issue_date': issue.isoformat() if issue else None,
        },
        'field_confidence': fc,
        'ocr_confidence': ocr_confidence,
        'id_checksum_ok': checksum,
        'lifelong': lifelong,
        'name_parts': {
            'th': th_name,
            'en': {'title': en_parts.get('title'), 'first': en_parts.get('first'),
                   'last': en_parts.get('last')} if en_parts else None,
        },
        'reasons': reasons,
    }

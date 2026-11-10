# -*- coding: utf-8 -*-
"""
อ่าน MRZ (Machine Readable Zone) ตามมาตรฐาน ICAO Doc 9303 — โค้ด Python ล้วน ไม่ต้องใช้โมเดล

รองรับ 3 แบบ:
- TD3 (หนังสือเดินทางทุกประเทศ): 2 บรรทัด × 44 ตัว
- TD2 (บัตร/วีซ่าบางประเทศ): 2 บรรทัด × 36 ตัว
- TD1 (บัตรประชาชนแบบ MRZ ด้านหลัง เช่น EU/ASEAN หลายประเทศ): 3 บรรทัด × 30 ตัว

ทุกช่องสำคัญมี check digit (น้ำหนัก 7-3-1) + composite check — ถ้าผ่านครบ = ข้อมูลถูกจริงเกือบแน่นอน
(OCR พลาดหนึ่งตัวในเลขเอกสาร/วันเกิด/วันหมดอายุ แล้ว check digit ยังผ่าน แทบเป็นไปไม่ได้)

ห้าม log ข้อความ MRZ / ชื่อ / เลขเอกสาร
"""
from __future__ import annotations

import re
from dataclasses import dataclass
from datetime import date

MRZ_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<'

# รหัสประเทศ/หน่วยงานที่ออกเอกสาร (ISO 3166-1 alpha-3 + รหัสพิเศษของ ICAO)
_ISO3 = (
    'AFG ALA ALB DZA ASM AND AGO AIA ATA ATG ARG ARM ABW AUS AUT AZE BHS BHR BGD BRB BLR BEL BLZ BEN BMU BTN BOL '
    'BES BIH BWA BVT BRA IOT BRN BGR BFA BDI CPV KHM CMR CAN CYM CAF TCD CHL CHN CXR CCK COL COM COG COD COK CRI '
    'CIV HRV CUB CUW CYP CZE DNK DJI DMA DOM ECU EGY SLV GNQ ERI EST SWZ ETH FLK FRO FJI FIN FRA GUF PYF ATF GAB '
    'GMB GEO DEU GHA GIB GRC GRL GRD GLP GUM GTM GGY GIN GNB GUY HTI HMD VAT HND HKG HUN ISL IND IDN IRN IRQ IRL '
    'IMN ISR ITA JAM JPN JEY JOR KAZ KEN KIR PRK KOR KWT KGZ LAO LVA LBN LSO LBR LBY LIE LTU LUX MAC MDG MWI MYS '
    'MDV MLI MLT MHL MTQ MRT MUS MYT MEX FSM MDA MCO MNG MNE MSR MAR MOZ MMR NAM NRU NPL NLD NCL NZL NIC NER NGA '
    'NIU NFK MKD MNP NOR OMN PAK PLW PSE PAN PNG PRY PER PHL PCN POL PRT PRI QAT REU ROU RUS RWA BLM SHN KNA LCA '
    'MAF SPM VCT WSM SMR STP SAU SEN SRB SYC SLE SGP SXM SVK SVN SLB SOM ZAF SGS SSD ESP LKA SDN SUR SJM SWE CHE '
    'SYR TWN TJK TZA THA TLS TGO TKL TON TTO TUN TUR TKM TCA TUV UGA UKR ARE GBR USA UMI URY UZB VUT VEN VNM VGB '
    'VIR WLF ESH YEM ZMB ZWE RKS'
)
# ICAO: D = เยอรมนี (เอกสารเยอรมันใช้ D<<), GBD/GBN/GBO/GBP/GBS = สัญชาติอังกฤษประเภทต่างๆ, UN* = สหประชาชาติ,
# XOM = Sovereign Order of Malta, XXA/XXB/XXC/XXX = ไร้สัญชาติ/ผู้ลี้ภัย
_ICAO_EXTRA = 'D GBD GBN GBO GBP GBS UNO UNA UNK XOM XXA XXB XXC XXX EUE'
COUNTRY_CODES = frozenset(_ISO3.split()) | frozenset(_ICAO_EXTRA.split())

# ตัวที่ OCR ชอบอ่านสลับกัน — แก้ตามชนิดช่อง (ช่องตัวเลข / ช่องตัวอักษร)
_TO_DIGIT = str.maketrans({'O': '0', 'Q': '0', 'D': '0', 'U': '0', 'I': '1', 'L': '1', 'T': '1', 'Z': '2',
                           'S': '5', 'B': '8', 'G': '6', 'A': '4'})
_TO_ALPHA = str.maketrans({'0': 'O', '1': 'I', '2': 'Z', '5': 'S', '8': 'B', '6': 'G', '4': 'A'})


def char_value(ch: str) -> int:
    if ch.isdigit():
        return int(ch)
    if 'A' <= ch <= 'Z':
        return ord(ch) - 55
    return 0   # '<'


def check_digit(s: str) -> str:
    w = (7, 3, 1)
    return str(sum(char_value(c) * w[i % 3] for i, c in enumerate(s)) % 10)


def _cd_ok(field: str, cd: str) -> bool:
    # ช่องว่างล้วน ('<<<') อนุญาตให้ check digit เป็น '<' หรือ '0'
    if set(field) <= {'<'} and cd in ('<', '0'):
        return True
    return cd.isdigit() and check_digit(field) == cd


def clean_line(text: str) -> str:
    """ตัดช่องว่าง แปลงตัวพิมพ์เล็ก/สัญลักษณ์ที่มักแทน '<' (เช่น « ‹ ( [ { ) แล้วเก็บเฉพาะอักษร MRZ"""
    t = (text or '').upper().replace(' ', '')
    t = re.sub(r'[«‹(\[{]', '<', t)
    return ''.join(ch for ch in t if ch in MRZ_CHARS)


def _digits(s: str) -> str:
    return s.translate(_TO_DIGIT)


def _alpha(s: str) -> str:
    return s.translate(_TO_ALPHA)


def _yymmdd(s: str, kind: str, today: date) -> date | None:
    if not s.isdigit() or len(s) != 6:
        return None
    yy, mm, dd = int(s[:2]), int(s[2:4]), int(s[4:6])
    if kind == 'birth':
        # วันเกิดอยู่ในอดีตเสมอ: ปีสองหลักที่มากกว่าปีปัจจุบัน = 19xx
        year = 2000 + yy if 2000 + yy <= today.year else 1900 + yy
    else:
        # วันหมดอายุ: เอกสารอายุไม่เกิน ~ 50 ปี
        year = 2000 + yy if 2000 + yy <= today.year + 50 else 1900 + yy
    try:
        return date(year, mm, dd)
    except ValueError:
        return None


def _names(field: str) -> tuple[str | None, str | None]:
    """'SURNAME<<GIVEN<NAMES<<<<' → ('SURNAME', 'GIVEN NAMES') — ตัด 'K' ที่ OCR อ่าน '<' เพี้ยนตรงท้าย"""
    f = _alpha(field)
    f = re.sub(r'[<KL]{3,}$', '', f)   # ฟิลเลอร์ท้ายที่ OCR อ่าน '<' เป็น K / L
    parts = f.split('<<', 1)
    sur = parts[0].replace('<', ' ').strip() or None
    giv = parts[1].replace('<', ' ').strip() if len(parts) > 1 else ''
    giv = re.sub(r'\s+', ' ', giv).strip() or None

    def drop_noise(n: str | None) -> str | None:
        # ฟิลเลอร์ '<' ที่ OCR อ่านเป็น K/L เดี่ยวๆ แทรกระหว่างชื่อ — ชื่อจริงใน MRZ ไม่มีคำตัวอักษรเดียว K/L
        if not n:
            return n
        # ฟิลเลอร์ยาวที่อ่านเพี้ยนปน R/E (เช่น 'KKKKKKKKKKKRKRKK') — ชื่อจริงไม่มี K ติดกัน 3 ตัว
        def is_filler(t: str) -> bool:
            return (t in ('K', 'L', 'KK', 'LL', 'KL', 'LK') or 'KKK' in t
                    or (len(t) >= 4 and set(t) <= set('KLRE') and t.count('K') >= 2))
        toks = [t for t in n.split(' ') if not is_filler(t)]
        return ' '.join(toks) or None
    return drop_noise(sur), drop_noise(giv)


@dataclass
class Mrz:
    format: str                  # TD1 / TD2 / TD3
    doc_type: str                # P = หนังสือเดินทาง, I/A/C = บัตร
    issuing_country: str
    nationality: str
    document_number: str
    surname: str | None
    given_names: str | None
    birth_date: date | None
    expiry_date: date | None
    sex: str | None
    optional: str | None
    checks: dict                 # ชื่อช่อง → ผ่าน check digit ไหม

    @property
    def valid(self) -> bool:
        """
        เลขเอกสาร + วันเกิด + วันหมดอายุ + composite ผ่านครบ และรหัสประเทศถูกต้อง
        composite = None คือ OCR ตกตัวสุดท้ายของบรรทัด (อ่านไม่ได้ ไม่ใช่ผิด) — ยอมได้เมื่อสามช่องหลักผ่าน check digit ครบ
        """
        return (all(self.checks.get(k) for k in ('document_number', 'birth_date', 'expiry_date'))
                and self.checks.get('composite') is not False
                and self.issuing_country in COUNTRY_CODES and self.nationality in COUNTRY_CODES
                and self.birth_date is not None and self.expiry_date is not None)

    @property
    def score(self) -> float:
        ok = sum(1 for v in self.checks.values() if v)
        return ok / max(1, len(self.checks))

    def to_dict(self) -> dict:
        return {
            'format': self.format, 'doc_type': self.doc_type, 'issuing_country': self.issuing_country,
            'nationality': self.nationality, 'document_number': self.document_number,
            'surname': self.surname, 'given_names': self.given_names,
            'birth_date': self.birth_date.isoformat() if self.birth_date else None,
            'expiry_date': self.expiry_date.isoformat() if self.expiry_date else None,
            'sex': self.sex, 'checks': self.checks, 'valid': self.valid,
        }


def _fix_country(code: str, other: str) -> str:
    """
    รหัสประเทศไม่มี check digit — ถ้า OCR อ่านเพี้ยน (ไม่อยู่ในรายการ) ซ่อมเฉพาะกรณีที่ชัดเจน:
    ต่างจากอีกช่อง (ประเทศผู้ออก ↔ สัญชาติ) เพียงหนึ่งตัว หรือมีรหัสจริงเพียงหนึ่งเดียวที่ต่างกันหนึ่งตัว
    """
    if code in COUNTRY_CODES or len(code) != 3:
        return code
    def near(a: str, b: str) -> bool:
        return len(a) == len(b) and sum(x != y for x, y in zip(a, b)) == 1
    if other in COUNTRY_CODES and near(code, other):
        return other
    hits = [c for c in COUNTRY_CODES if near(code, c)]
    return hits[0] if len(hits) == 1 else code


def _fix_countries(iss: str, nat: str) -> tuple[str, str]:
    """
    ซ่อมรหัสประเทศผู้ออก + สัญชาติ ทีละช่องแล้ววนกลับ — ช่องที่ซ่อมได้ก่อนช่วยเป็นตัวอ้างอิงให้อีกช่อง
    (เช่น ผู้ออก 'IPN' + สัญชาติ '3PN' → สัญชาติมีรหัสใกล้เพียง 'JPN' → ผู้ออกต่างจาก 'JPN' หนึ่งตัว → 'JPN')
    """
    for _ in range(2):
        nat = _fix_country(nat, iss)
        iss = _fix_country(iss, nat)
    return iss, nat

def _repair_shift(l1: str, nat: str) -> str:
    """
    บรรทัดแรกของ TD3/TD2: OCR แทรกตัวเกินหนึ่งตัวในรหัสประเทศ (เช่น 'JPN' → 'JIPN') ทำให้ชื่อเลื่อนทั้งบรรทัด
    → ลองลบทีละตัวในช่วงรหัสประเทศ ถ้าได้รหัสจริง (และตรงกับสัญชาติเมื่อมี) ใช้บรรทัดนั้น
    """
    if _alpha(l1[2:5]) in COUNTRY_CODES:
        return l1
    for i in range(2, 6):
        t = l1[:i] + l1[i + 1:]
        code = _alpha(t[2:5])
        if code in COUNTRY_CODES and (code == nat or nat not in COUNTRY_CODES):
            return t
    return l1


def _repair_tail(l2: str, core: int, n: int) -> str:
    """
    บรรทัดล่าง (TD3/TD2): ช่วงหลัง "วันหมดอายุ + check digit" คือ ช่องเสริม + check digit + composite
    OCR มักอ่านฟิลเลอร์ '<' เป็น K/L และนับจำนวนเพี้ยน (บรรทัดยาว/สั้นเกิน) → ประกอบช่วงท้ายใหม่ให้ยาว n พอดี:
    [ข้อมูลเสริมจริง][ฟิลเลอร์ '<' เติมให้ครบ][check digit ของช่องเสริม (TD3)][composite]
    """
    if len(l2) <= core + 1:
        return l2
    head, tail = l2[:core], l2[core:]
    comp, mid = tail[-1], tail[:-1]
    opt_len = n - core - 1                      # TD3: 15 (14 + cd), TD2: 7
    cd = ''
    if n == 44:
        m = re.match(r'^(.*?)[<KLC]+(\d)$', mid)
        if m:
            mid, cd = m.group(1), m.group(2)
        opt_len -= 1
    body = re.sub(r'[<KLC]+$', '', mid)
    if len(body) > opt_len:
        return l2
    body = body + '<' * (opt_len - len(body))
    if n == 44:
        body += cd or '<'
    return head + body + comp


def _repair_nat_shift(l2: str) -> str:
    """
    บรรทัดล่าง TD3/TD2: OCR แตกตัวอักษรหนึ่งตัวในช่องสัญชาติเป็นสองตัว (เช่น 'JPN' → 'I3PN') ทำให้วันเกิด/วันหมดอายุเลื่อน
    → ลองลบทีละตัวในช่องสัญชาติ ใช้เมื่อวันเกิดกลับมาผ่าน check digit (ไม่เดาลอยๆ)
    """
    if len(l2) < 21 or _cd_ok(_digits(l2[13:19]), _digits(l2[19])):
        return l2
    for i in range(10, 14):
        t = l2[:i] + l2[i + 1:]
        if _cd_ok(_digits(t[13:19]), _digits(t[19])):
            return t
    return l2


def _comp_check(src: str, raw: str) -> bool | None:
    """composite check digit — ตัวท้ายบรรทัดเป็นฟิลเลอร์ (OCR ตกตัวสุดท้าย) = None (อ่านไม่ได้) แทน False (ผิด)"""
    if raw in '<KL':
        return None
    return _cd_ok(src, _digits(raw))


def _fit(line: str, n: int) -> str:
    """ปรับความยาวบรรทัดให้พอดี n (OCR ตกฟิลเลอร์ท้ายบรรทัด/เกินมาหนึ่งสองตัว)"""
    if len(line) >= n:
        return line[:n]
    return line + '<' * (n - len(line))


def _sex(c: str) -> str | None:
    c = {'H': 'M', 'N': 'M'}.get(c, c)   # อ่านเพี้ยนที่พบบ่อย
    return c if c in ('M', 'F', 'X') else None


def parse_td3(l1: str, l2: str, today: date) -> Mrz:
    l2 = _fit(_repair_tail(_repair_nat_shift(l2), 28, 44), 44)
    l1 = _fit(_repair_shift(l1, _alpha(l2[10:13])), 44)
    doc = l2[0:9]
    doc_cd = _digits(l2[9])
    nat = _alpha(l2[10:13])
    dob = _digits(l2[13:19])
    dob_cd = _digits(l2[19])
    exp = _digits(l2[21:27])
    exp_cd = _digits(l2[27])
    opt = l2[28:42]
    opt_cd = _digits(l2[42]) if l2[42] != '<' else '<'
    comp_src = doc + doc_cd + dob + dob_cd + exp + exp_cd + opt + opt_cd
    sur, giv = _names(l1[5:44])
    checks = {
        'document_number': _cd_ok(doc, doc_cd),
        'birth_date': _cd_ok(dob, dob_cd),
        'expiry_date': _cd_ok(exp, exp_cd),
        'optional': _cd_ok(opt, opt_cd),
        'composite': _comp_check(comp_src, l2[43]),
    }
    iss, nat = _alpha(l1[2:5]).replace('<', ''), nat.replace('<', '')
    iss, nat = _fix_countries(iss, nat)
    return Mrz('TD3', l1[0], iss, nat, doc.replace('<', ''),
               sur, giv, _yymmdd(dob, 'birth', today), _yymmdd(exp, 'expiry', today), _sex(l2[20]),
               opt.strip('<') or None, checks)


def parse_td2(l1: str, l2: str, today: date) -> Mrz:
    l2 = _fit(_repair_tail(_repair_nat_shift(l2), 28, 36), 36)
    l1 = _fit(_repair_shift(l1, _alpha(l2[10:13])), 36)
    doc = l2[0:9]
    doc_cd = _digits(l2[9])
    nat = _alpha(l2[10:13])
    dob = _digits(l2[13:19])
    dob_cd = _digits(l2[19])
    exp = _digits(l2[21:27])
    exp_cd = _digits(l2[27])
    opt = l2[28:35]
    sur, giv = _names(l1[5:36])
    checks = {
        'document_number': _cd_ok(doc, doc_cd),
        'birth_date': _cd_ok(dob, dob_cd),
        'expiry_date': _cd_ok(exp, exp_cd),
        'composite': _comp_check(doc + doc_cd + dob + dob_cd + exp + exp_cd + opt, l2[35]),
    }
    iss, nat = _alpha(l1[2:5]).replace('<', ''), nat.replace('<', '')
    iss, nat = _fix_countries(iss, nat)
    return Mrz('TD2', l1[0], iss, nat, doc.replace('<', ''),
               sur, giv, _yymmdd(dob, 'birth', today), _yymmdd(exp, 'expiry', today), _sex(l2[20]),
               opt.strip('<') or None, checks)


def parse_td1(l1: str, l2: str, l3: str, today: date) -> Mrz:
    l1, l2, l3 = _fit(l1, 30), _fit(l2, 30), _fit(l3, 30)
    doc = l1[5:14]
    doc_cd = l1[14]
    opt1 = l1[15:30]
    full_doc = doc
    if doc_cd == '<':
        # เลขเอกสารยาวเกิน 9 ตัว: ส่วนที่เหลือ + check digit อยู่ในช่อง optional ปิดด้วย '<'
        m = re.match(r'([A-Z0-9]*?)(\d)<', opt1)
        if m:
            full_doc, doc_cd = doc + m.group(1), m.group(2)
    else:
        doc_cd = _digits(doc_cd)
    dob = _digits(l2[0:6])
    dob_cd = _digits(l2[6])
    exp = _digits(l2[8:14])
    exp_cd = _digits(l2[14])
    nat = _alpha(l2[15:18])
    opt2 = l2[18:29]
    comp_src = l1[5:30] + l2[0:7] + l2[8:15] + l2[18:29]
    sur, giv = _names(l3)
    checks = {
        'document_number': _cd_ok(full_doc, doc_cd),
        'birth_date': _cd_ok(dob, dob_cd),
        'expiry_date': _cd_ok(exp, exp_cd),
        'composite': _comp_check(comp_src, l2[29]),
    }
    iss, nat = _alpha(l1[2:5]).replace('<', ''), nat.replace('<', '')
    iss, nat = _fix_countries(iss, nat)
    return Mrz('TD1', l1[0], iss, nat, full_doc.replace('<', ''),
               sur, giv, _yymmdd(dob, 'birth', today), _yymmdd(exp, 'expiry', today), _sex(l2[7]),
               (opt1 + opt2).strip('<') or None, checks)


def find_mrz(lines: list[str], today: date) -> Mrz | None:
    """
    หา MRZ จากรายการบรรทัด OCR (บนลงล่าง) — ลองทุกคู่/สามบรรทัดที่ติดกัน เลือกอันที่ check digit ผ่านมากสุด
    บรรทัด MRZ = ยาว ≥ 25 ตัวหลังทำความสะอาด และมี '<' หรือเป็นอักษร MRZ ล้วน
    """
    cand = [clean_line(t) for t in lines]
    cand = [c for c in cand if len(c) >= 25 and ('<' in c or len(c) >= 28)]
    best: Mrz | None = None

    def take(m: Mrz | None):
        nonlocal best
        if m and (best is None or (m.valid, m.score) > (best.valid, best.score)):
            best = m

    for i in range(len(cand)):
        a = cand[i]
        nxt = cand[i + 1] if i + 1 < len(cand) else None
        c = cand[i + 2] if i + 2 < len(cand) else None
        if nxt and c and len(a) <= 33 and a[:1] in 'IAC':
            take(parse_td1(a, nxt, c, today))
        # บรรทัดล่างของ TD3/TD2 ขึ้นต้นด้วยเลขเอกสาร (ไม่มีตัวนำ) — OCR มักเก็บจุด/ขอบกระดาษมาเป็นตัวขยะ 1-2 ตัวหน้าบรรทัด
        # ทำให้ทุกช่องเลื่อน → ลองตัดตัวหน้า แล้วให้ check digit เป็นตัวตัดสิน (แนวผิด = check digit ไม่ผ่าน)
        for b in _lead_variants(nxt):
            # หนังสือเดินทาง: OCR มักตกฟิลเลอร์ท้ายบรรทัดบน (เหลือ ~30 ตัว) → ลอง TD3 เสมอ ให้ check digit ตัดสิน
            if a[:1] == 'P' and (len(a) >= 40 or len(b) >= 36):
                take(parse_td3(a, b, today))
            if 33 < len(a) < 40 and a[:1] in 'IACPV':
                take(parse_td2(a, b, today))
            if len(a) >= 40 and a[:1] in 'IACV':
                take(parse_td3(a, b, today))   # วีซ่า/เอกสารเดินทางอื่นแบบ 44 ตัว
            if best is not None and best.valid:
                return best
    return best


def _lead_variants(line: str | None) -> list[str]:
    """บรรทัดเดิม + ตัดตัวหน้า 1 และ 2 ตัว (เฉพาะที่ยังยาวพอเป็นบรรทัด MRZ)"""
    if not line:
        return []
    return [line[k:] for k in range(3) if len(line) - k >= 25]

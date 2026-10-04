# -*- coding: utf-8 -*-
"""ตรรกะล้วนของบัตรประชาชน: checksum, วันที่ พ.ศ., ชื่อ, การประกอบฟิลด์จากกล่อง OCR"""
from datetime import date

import pytest

from app.thai_id import (Box, extract_fields, id_candidates, id_checksum_ok, is_lifelong, parse_english_date,
                         parse_english_name_line, parse_thai_date, parse_thai_name)
from synth import make_id

TODAY = date(2026, 10, 4)


# ---------------------------------------------------------------- checksum
def test_checksum_known_valid_and_invalid():
    good = make_id('110370207181')
    assert id_checksum_ok(good)
    bad = good[:-1] + str((int(good[-1]) + 1) % 10)
    assert not id_checksum_ok(bad)


def test_checksum_formula_matches_spec():
    # ผลรวม (หลัก i × (13 − i)) mod 11 → check = (11 − mod) % 10
    s = '310050012345'
    total = sum(int(s[i]) * (13 - i) for i in range(12))
    assert id_checksum_ok(s + str((11 - total % 11) % 10))


@pytest.mark.parametrize('v', [None, '', '123', '12345678901234', '11037020718a1'])
def test_checksum_rejects_malformed(v):
    assert not id_checksum_ok(v)


def test_id_candidates_with_spaces_dashes_thai_digits_and_ocr_confusions():
    good = make_id('110370207181')
    spaced = f'{good[0]} {good[1:5]} {good[5:10]} {good[10:12]} {good[12]}'
    assert id_candidates(f'เลขประจำตัวประชาชน {spaced}') == [good]
    assert id_candidates(spaced.replace(' ', '-')) == [good]
    thai = spaced.translate(str.maketrans('0123456789', '๐๑๒๓๔๕๖๗๘๙'))
    assert id_candidates(thai) == [good]
    # OCR อ่านเลข 0 เป็นตัว O
    assert id_candidates(spaced.replace('0', 'O')) == [good]


# ---------------------------------------------------------------- วันที่
@pytest.mark.parametrize('text,expected', [
    ('12 ม.ค. 2538', date(1995, 1, 12)),
    ('เกิดวันที่ 12 ม.ค. 2538', date(1995, 1, 12)),
    ('12 มค 2538', date(1995, 1, 12)),
    ('12 ม.ค.2538', date(1995, 1, 12)),
    ('๑๒ ม.ค. ๒๕๓๘', date(1995, 1, 12)),
    ('5 มีนาคม 2530', date(1987, 3, 5)),
    ('29 ก.พ. 2543', date(2000, 2, 29)),
    ('1 ธันวาคม 2563', date(2020, 12, 1)),
    ('31 ต.ค. 2570', date(2027, 10, 31)),
    ('3 พฤศจิกายน 2499', date(1956, 11, 3)),
    ('7 มิ.ย. 2545', date(2002, 6, 7)),
    ('7 มี.ค. 2545', date(2002, 3, 7)),
    ('15 กรกฏาคม 2535', date(1992, 7, 15)),     # สะกดผิดทั่วไป (ฏ) → fuzzy
])
def test_parse_thai_date(text, expected):
    assert parse_thai_date(text) == expected


@pytest.mark.parametrize('text', ['31 ก.พ. 2538', '12 ม.ค.', 'ไม่มีวันที่', '12 XYZ 2538', '40 ม.ค. 2538'])
def test_parse_thai_date_rejects(text):
    assert parse_thai_date(text) is None


@pytest.mark.parametrize('text,expected', [
    ('Date of Birth 12 Jan. 1995', date(1995, 1, 12)),
    ('11 Sept 2029', date(2029, 9, 11)),
    ('1 December 2020', date(2020, 12, 1)),
])
def test_parse_english_date(text, expected):
    assert parse_english_date(text) == expected


def test_lifelong_markers():
    assert is_lifelong('ตลอดชีพ')
    assert is_lifelong('LIFE LONG')
    assert not is_lifelong('11 ม.ค. 2572')


# ---------------------------------------------------------------- ชื่อ
@pytest.mark.parametrize('text,title,first,last', [
    ('ชื่อตัวและชื่อสกุล นาย สมชาย ใจดี', 'นาย', 'สมชาย', 'ใจดี'),
    ('นางสาวสมหญิง รักไทย', 'นางสาว', 'สมหญิง', 'รักไทย'),
    ('นาง มาลี ศรีสุข', 'นาง', 'มาลี', 'ศรีสุข'),
    ('ด.ช. ก้อง ภพ', 'ด.ช.', 'ก้อง', 'ภพ'),
    ('ด. ญ. ใบเตย หอมดี', 'ด.ญ.', 'ใบเตย', 'หอมดี'),
    ('เด็กหญิง ฟ้า ใส', 'เด็กหญิง', 'ฟ้า', 'ใส'),
    ('น.ส. แก้ว ใจงาม', 'นางสาว', 'แก้ว', 'ใจงาม'),
    ('ชือตัวและชือสกุล นาย สมชาย นางาม', 'นาย', 'สมชาย', 'นางาม'),   # ป้ายอ่านเพี้ยน + นามสกุลขึ้นต้น "นาง"
    ('ชือตัวและชือสกุลนาย สมชาย ใจดี', 'นาย', 'สมชาย', 'ใจดี'),      # ป้ายติดคำนำหน้า (ผล OCR จริง)
    ('ชื่อตัวและชื่อสกุลนางสาว มาลี สกุลไทย', 'นางสาว', 'มาลี', 'สกุลไทย'),
    ('นาย สมชาย สกุลไทย', 'นาย', 'สมชาย', 'สกุลไทย'),
])
def test_parse_thai_name(text, title, first, last):
    p = parse_thai_name(text)
    assert p == {'title': title, 'first': first, 'last': last}


def test_parse_thai_name_none():
    assert parse_thai_name('ศาสนา พุทธ') is None


def test_parse_english_name_lines():
    assert parse_english_name_line('Name Mr. Somchai') == ('name', {'title': 'Mr.', 'first': 'Somchai'})
    assert parse_english_name_line('Name Miss Malee') == ('name', {'title': 'Miss', 'first': 'Malee'})
    assert parse_english_name_line('Last name Jaidee') == ('last', {'last': 'Jaidee'})
    assert parse_english_name_line('Lastname Jai-dee') == ('last', {'last': 'Jai-dee'})
    assert parse_english_name_line('Date of Birth 12 Jan. 1995') is None


# ---------------------------------------------------------------- ประกอบทั้งใบ
def _card_boxes(id13, expiry_th='11 ม.ค. 2572', expiry_en='11 Jan. 2029', conf=0.9):
    W, H = 1000, 630
    rows = [
        ('บัตรประจำตัวประชาชน Thai National ID Card', 200, 25),
        ('เลขประจำตัวประชาชน', 200, 90),
        (f'{id13[0]} {id13[1:5]} {id13[5:10]} {id13[10:12]} {id13[12]}', 470, 85),
        ('ชื่อตัวและชื่อสกุล', 120, 175),
        ('นาย สมชาย ใจดี', 400, 175),
        ('Name Mr. Somchai', 200, 235),
        ('Last name Jaidee', 200, 285),
        ('เกิดวันที่ 12 ม.ค. 2538', 300, 345),
        ('Date of Birth 12 Jan. 1995', 300, 390),
        ('ศาสนา พุทธ', 300, 430),
        ('1 ม.ค. 2563', 60, 525),
        (expiry_th, 380, 525),
        ('วันออกบัตร', 60, 555),
        ('วันบัตรหมดอายุ', 380, 555),
        ('1 Jan. 2020', 60, 580),
        (expiry_en, 380, 580),
        ('Date of Issue', 60, 605),
        ('Date of Expiry', 380, 605),
    ]
    boxes = []
    for text, x, y in rows:
        w = 14 * len(text)
        boxes.append(Box(text, conf, x, y - 12, x + w, y + 12))
    return boxes, W, H


def test_extract_fields_full_card():
    good = make_id('110370207181')
    boxes, W, H = _card_boxes(good)
    r = extract_fields(boxes, W, H, TODAY)
    f = r['fields']
    assert f['id_number'] == good and r['id_checksum_ok']
    assert f['name_th'] == 'นาย สมชาย ใจดี'
    assert f['name_en'] == 'Mr. Somchai Jaidee'
    assert f['birth_date'] == '1995-01-12'
    assert f['issue_date'] == '2020-01-01'
    assert f['expiry_date'] == '2029-01-11'
    assert r['reasons'] == []
    # ไทย/อังกฤษตรงกัน + checksum ผ่าน → ความมั่นใจสูง
    assert r['ocr_confidence'] >= 0.85
    assert r['name_parts']['th'] == {'title': 'นาย', 'first': 'สมชาย', 'last': 'ใจดี'}


def test_extract_fields_lifelong():
    boxes, W, H = _card_boxes(make_id('110370207181'), expiry_th='ตลอดชีพ', expiry_en='LIFE LONG')
    r = extract_fields(boxes, W, H, TODAY)
    assert r['fields']['expiry_date'] is None
    assert r['lifelong'] and 'EXPIRY_LIFELONG' in r['reasons']
    assert 'EXPIRED' not in r['reasons']
    assert r['field_confidence']['expiry_date'] > 0.5


def test_extract_fields_expired_and_bad_checksum():
    good = make_id('110370207181')
    bad = good[:-1] + str((int(good[-1]) + 3) % 10)
    boxes, W, H = _card_boxes(bad, expiry_th='11 ม.ค. 2565', expiry_en='11 Jan. 2022')
    r = extract_fields(boxes, W, H, TODAY)
    assert r['fields']['expiry_date'] == '2022-01-11'
    assert 'EXPIRED' in r['reasons']
    assert 'ID_CHECKSUM_FAIL' in r['reasons'] and not r['id_checksum_ok']
    assert r['field_confidence']['id_number'] <= 0.5


def test_extract_fields_empty():
    r = extract_fields([], 1000, 630, TODAY)
    assert r['fields'] == {'id_number': None, 'name_th': None, 'name_en': None, 'birth_date': None,
                           'expiry_date': None, 'issue_date': None}
    assert r['ocr_confidence'] == 0.0 and not r['id_checksum_ok']


# ---------------------------------------------------------------- โซนอ่านรอบแรก
def test_ocr_key_zones_follow_real_card_layout():
    """กล่องข้อมูลสำคัญต้องอยู่รอบแรก ป้าย/หัวบัตร/ที่อยู่ เลื่อนไว้ และรูปถ่ายไม่อ่านเลย (พิกัดจากแม่แบบบัตรจริง)"""
    from app.ocr import OcrEngine
    W, H = 1000, 630

    def box(x0, x1, y0, y1):
        return [round(x0 * W), round(x1 * W), round(y0 * H), round(y1 * H)]
    key = {
        'id_number': box(0.43, 0.91, 0.11, 0.21), 'name_th': box(0.28, 0.91, 0.19, 0.31),
        'name_en': box(0.37, 0.70, 0.30, 0.37), 'last_en': box(0.44, 0.75, 0.38, 0.43),
        'name_label': box(0.31, 0.37, 0.32, 0.36), 'birth_th': box(0.43, 0.68, 0.44, 0.52),
        'birth_en': box(0.49, 0.73, 0.51, 0.59),
        # ช่องล่างใช้กรอบข้อความแบบแนบตัวอักษร (อย่างที่ CRAFT ให้) ไม่ใช่ ROI ที่เผื่อขอบ
        'issue_th': box(0.08, 0.23, 0.80, 0.845), 'issue_en': box(0.08, 0.24, 0.89, 0.93),
        'expiry_th': box(0.55, 0.73, 0.80, 0.845), 'expiry_en': box(0.55, 0.73, 0.89, 0.93),
    }
    deferred = {
        'header': box(0.17, 0.98, 0.02, 0.09), 'id_label': box(0.17, 0.42, 0.10, 0.14),
        'id_label_en': box(0.17, 0.42, 0.15, 0.19), 'name_label_th': box(0.05, 0.27, 0.24, 0.29),
        'religion': box(0.38, 0.52, 0.59, 0.66), 'address1': box(0.10, 0.68, 0.63, 0.70),
        'address2': box(0.10, 0.50, 0.71, 0.79), 'officer': box(0.29, 0.53, 0.92, 0.97),
        # ป้ายใต้วันที่ (บรรทัดที่ 2 และ 4 ของแต่ละคอลัมน์ล่าง)
        'issue_label': box(0.05, 0.17, 0.85, 0.88), 'issue_label_en': box(0.09, 0.22, 0.94, 0.97),
        'expiry_label': box(0.56, 0.72, 0.85, 0.88), 'expiry_label_en': box(0.56, 0.70, 0.94, 0.97),
    }
    photo = box(0.76, 0.95, 0.80, 0.86)
    now, later = OcrEngine.split_priority(list(key.values()) + list(deferred.values()) + [photo], W, H, True)
    assert sorted(now) == sorted(key.values())
    assert sorted(later) == sorted(deferred.values())
    # ภาพที่ไม่ใช่บัตร → อ่านทุกกล่อง
    allb = list(key.values()) + [photo]
    assert OcrEngine.split_priority(allb, W, H, False) == (allb, [])
    # ช่องล่างแยกบรรทัดไม่ได้ครบ 4 → อ่านทั้งคอลัมน์ (ไม่เสี่ยงทิ้งวันที่)
    merged = [box(0.55, 0.73, 0.80, 0.88), box(0.55, 0.73, 0.89, 0.97)]
    now, later = OcrEngine.split_priority(merged, W, H, True)
    assert sorted(now) == sorted(merged) and later == []


def test_date_split_into_word_boxes_still_cross_checks():
    """CRAFT อาจตัด "12 Jan. 1995" เป็น 3 กล่อง — ต้องรวมเป็นวลีแล้วเทียบกับวันที่ไทยได้"""
    from app.thai_id import group_phrases
    bs = [Box('เกิดวันที่ 12 ม.ค. 2538', 0.6, 340, 285, 650, 320), Box('date of birth', 0.8, 340, 332, 500, 352),
          Box('12', 0.99, 520, 330, 540, 355), Box('jan.', 0.5, 560, 330, 600, 355), Box('1995', 1.0, 620, 330, 680, 355),
          # คอลัมน์ล่างสองคอลัมน์บรรทัดเดียวกัน ห่างกันมาก → ต้องไม่ถูกรวมเป็นวลีเดียว
          Box('1 ม.ค. 2563', 0.8, 80, 500, 230, 535), Box('11 ม.ค. 2572', 0.8, 550, 500, 730, 535)]
    texts = [b.text for b in group_phrases(bs)]
    assert 'date of birth 12 jan. 1995' in texts and '1 ม.ค. 2563' in texts and '11 ม.ค. 2572' in texts
    r = extract_fields(bs, 1000, 630, TODAY)
    assert r['fields']['birth_date'] == '1995-01-12' and r['field_confidence']['birth_date'] >= 0.85
    assert r['fields']['issue_date'] == '2020-01-01' and r['fields']['expiry_date'] == '2029-01-11'

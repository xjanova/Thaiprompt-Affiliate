# -*- coding: utf-8 -*-
"""MRZ (ICAO 9303) — check digit, TD1/TD2/TD3, แก้ตัวอักษรที่ OCR อ่านเพี้ยน — ไม่ต้องใช้โมเดล"""
from datetime import date

from app import mrz as M

TODAY = date(2026, 11, 10)

# ตัวอย่างจากเอกสาร ICAO 9303 (ประเทศสมมติ UTO — ไม่ใช่รหัสประเทศจริง จึงต้องไม่ valid)
SPEC_TD3 = ('P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<', 'L898902C36UTO7408122F1204159ZE184226B<<<<<10')
SPEC_TD1 = ('I<UTOD231458907<<<<<<<<<<<<<<<', '7408122F1204159UTO<<<<<<<<<<<6', 'ERIKSSON<<ANNA<MARIA<<<<<<<<<<')
SPEC_TD2 = ('I<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<', 'D231458907UTO7408122F1204159<<<<<<<6')


def make_td3(country='THA', doc='AA1234567', dob='950112', exp='310111', sex='M',
             sur='JAIDEE', given='SOMCHAI') -> tuple[str, str]:
    l1 = f'P<{country}{sur}<<{given.replace(" ", "<")}'
    l1 = (l1 + '<' * 44)[:44]
    opt = '<' * 14
    body = doc + M.check_digit(doc) + country + dob + M.check_digit(dob) + sex + exp + M.check_digit(exp) + opt + '<'
    comp = M.check_digit(body[0:10] + body[13:20] + body[21:43])
    return l1, body + comp


def test_check_digit_matches_icao_examples():
    assert M.check_digit('L898902C3') == '6'
    assert M.check_digit('740812') == '2'
    assert M.check_digit('120415') == '9'


def test_icao_specimens_pass_check_digits_but_fictional_country_is_not_valid():
    for m in (M.parse_td3(*SPEC_TD3, TODAY), M.parse_td1(*SPEC_TD1, TODAY), M.parse_td2(*SPEC_TD2, TODAY)):
        assert all(m.checks[k] for k in ('document_number', 'birth_date', 'expiry_date', 'composite')), m
        assert m.surname == 'ERIKSSON' and m.given_names == 'ANNA MARIA'
        assert m.birth_date == date(1974, 8, 12) and m.expiry_date == date(2012, 4, 15) and m.sex == 'F'
        assert not m.valid       # UTO ไม่ใช่ประเทศจริง


def test_real_country_td3_is_valid():
    m = M.find_mrz(['some header text', *make_td3()], TODAY)
    assert m and m.valid and m.format == 'TD3'
    assert m.issuing_country == 'THA' and m.document_number == 'AA1234567'
    assert m.birth_date == date(1995, 1, 12) and m.expiry_date == date(2031, 1, 11)


def test_ocr_noise_is_repaired_or_rejected():
    l1, l2 = make_td3(country='DEU', doc='C01X00T47', given='ANNA MARIA')
    # OCR ใส่ช่องว่าง, อ่าน '<' ท้ายชื่อเป็น K, อ่านเลข 0 ในวันเกิดเป็น O
    noisy1 = l1.replace('<<<<<<<<', 'KKKKKKKK')
    noisy2 = l2[:13] + l2[13:19].replace('0', 'O') + l2[19:]
    m = M.find_mrz([noisy1[:20] + ' ' + noisy1[20:], noisy2], TODAY)
    assert m and m.valid and m.given_names == 'ANNA MARIA'
    # เลขเอกสารผิดหนึ่งตัว → check digit ไม่ผ่าน → ไม่ valid
    bad = l2[:3] + ('7' if l2[3] != '7' else '8') + l2[4:]
    m2 = M.find_mrz([l1, bad], TODAY)
    assert m2 is not None and not m2.valid


def test_birth_century_and_expiry():
    l1, l2 = make_td3(dob='100101', exp='300101')
    m = M.find_mrz([l1, l2], TODAY)
    assert m.birth_date == date(2010, 1, 1) and m.expiry_date == date(2030, 1, 1)
    l1, l2 = make_td3(dob='450101')
    assert M.find_mrz([l1, l2], TODAY).birth_date == date(1945, 1, 1)


def test_no_mrz_in_normal_text():
    assert M.find_mrz(['บัตรประจำตัวประชาชน', 'Name Mr. Somchai', '12 Jan. 1995'], TODAY) is None


def test_prod_tesseract_output_with_stray_lead_char_and_lost_fillers():
    """ข้อความดิบจาก Tesseract 5.3 (Debian) บน prod กับหนังสือเดินทางสังเคราะห์ (2026-11-10):
    ตัวขยะหน้าบรรทัดล่าง + ฟิลเลอร์ '<' หาย + J อ่านเป็น I + ฟิลเลอร์บรรทัดบนอ่านเป็น K"""
    l1, l2 = make_td3(country='JPN', doc='TK1234567', dob='880305', exp='330305', sex='F',
                      sur='TANAKA', given='HANAKO')
    m = M.find_mrz(['P<JPNTANAKA<<HANAKOK<<<<<<<KKKKKKKKKKKKKKLKK', '7 ' + l2[:28] + '<<<<<K' + l2[43]], TODAY)
    assert m is not None and m.valid
    assert (m.issuing_country, m.nationality, m.document_number) == ('JPN', 'JPN', 'TK1234567')
    assert (m.surname, m.given_names, m.sex) == ('TANAKA', 'HANAKO', 'F')


def test_long_misread_filler_is_not_a_given_name():
    sur, giv = M._names('TANAKA<<HANAKO<KKKKKKKKKKKRKRKK')
    assert (sur, giv) == ('TANAKA', 'HANAKO')
    assert M._names('LEE<<KERRY<ANN')[1] == 'KERRY ANN'


def test_prod_tesseract_split_letter_in_nationality_and_short_top_line():
    """J ในสัญชาติแตกเป็น 'I3' / อ่านเป็น '3' + บรรทัดบนเหลือ 30 ตัว (ข้อความดิบจาก prod 2026-11-10, ข้อมูลสังเคราะห์)"""
    m = M.find_mrz(['P<IPNTANAKA<<HANAKO<<<<<<<K<KKKKKKKKKKKRKRKKK', 'TK12345671I3PN8803056F3303056<<<<<<KKKK<'], TODAY)
    assert m is not None and m.valid and (m.issuing_country, m.nationality) == ('JPN', 'JPN')
    assert (m.surname, m.given_names) == ('TANAKA', 'HANAKO')
    m = M.find_mrz(['P<JPNTANAKA<<HANAKO<<<<<<KKKK<', 'TK123456713PN8803056F3303056<<<<<<<<<<O'], TODAY)
    assert m is not None and m.valid and m.nationality == 'JPN' and m.birth_date == date(1988, 3, 5)

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

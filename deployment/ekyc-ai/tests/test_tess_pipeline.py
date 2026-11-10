# -*- coding: utf-8 -*-
"""
อ่านเอกสารด้วย Tesseract ทั้งเส้นทาง (ไม่ต้องโหลดโมเดล EasyOCR/ใบหน้า): บัตรประชาชน, ใบขับขี่ไทย, หนังสือเดินทาง MRZ
ข้ามเมื่อเครื่องไม่มี tesseract + ภาษา tha/eng หรือไม่มีฟอนต์ (ใน image ของเรามีครบ)
"""
import os
from datetime import date

import cv2
import numpy as np
import pytest

import synth
from app import card as C
from app import service as S
from app.config import load_settings
from app.tess import Tesseract

TZ = Tesseract(threads=2, tessdata=os.environ.get('EKYC_TEST_TESSDATA') or None)
needs_tess = pytest.mark.skipif(not TZ.available, reason='tesseract with tha+eng not installed')
needs_font = pytest.mark.skipif(synth.find_font() is None, reason='Thai font not found in repo')


class _NoEasyOcr:
    """EasyOCR ปลอม: บันทึกว่าถูกเรียกไหม (ถ้า Tesseract อ่านครบต้องไม่ถูกเรียก)"""

    def __init__(self):
        self.called = 0

    def detect(self, img):
        self.called += 1
        return []

    split_priority = staticmethod(lambda boxes, W, H, card_view: (boxes, []))

    def recognize(self, img, boxes):
        return []


def engine() -> S.Engine:
    e = S.Engine.__new__(S.Engine)
    e.s = load_settings()
    e.tess = TZ
    e.ocr = _NoEasyOcr()
    return e


def J(img, q=88):
    return cv2.imdecode(np.frombuffer(synth.jpeg(img, q), np.uint8), cv2.IMREAD_COLOR)


def warp(img):
    return cv2.resize(img, (C.CARD_W, C.CARD_H), interpolation=cv2.INTER_AREA)


@needs_tess
@needs_font
def test_thai_id_read_by_tesseract_alone():
    e = engine()
    spec = synth.CardSpec()
    res, stats = e._read_thai(warp(J(synth.render_card(spec))), 'thai_id', True)
    f = res['fields']
    assert f['id_number'] == spec.id13 and res['id_checksum_ok']
    assert f['name_th'] and f['name_th'].startswith('นาย สมชาย')
    assert f['birth_date'] == '1995-01-12' and f['expiry_date'] == '2029-01-11'
    assert res['ocr_confidence'] >= 0.8
    assert stats['engines'] == ['tesseract'] and e.ocr.called == 0   # ครบแล้วไม่ต้องรอ EasyOCR


@needs_tess
@needs_font
def test_thai_driving_licence_layout():
    e = engine()
    spec = synth.CardSpec(id13=synth.make_id('370120034567'), title_th='นางสาว', first_th='มาลี', last_th='สวยงาม',
                          title_en='Miss', first_en='Malee', last_en='Suayngam',
                          issue_th='5 มีนาคม 2566', issue_en='05 March 2023',
                          expiry_th='5 มีนาคม 2571', expiry_en='05 March 2028')
    res, _ = e._read_thai(warp(J(synth.render_dl(spec))), 'thai_dl', True)
    f = res['fields']
    assert f['id_number'] == spec.id13 and res['id_checksum_ok']
    assert f['name_th'] and f['name_th'].startswith('นางสาว มาลี')
    assert f['name_en'] == 'Miss Malee Suayngam'
    assert f['birth_date'] == '1995-01-12'
    assert f['issue_date'] == '2023-03-05' and f['expiry_date'] == '2028-03-05'
    assert res['license_no'] == '62001234'


@needs_tess
@pytest.mark.skipif(synth.find_mono_font() is None, reason='no monospace font')
def test_passport_mrz_any_country_and_upside_down():
    from test_mrz import make_td3
    e = engine()
    l1, l2 = make_td3(country='JPN', doc='TK1234567', dob='880305', exp='330305', sex='F',
                      sur='TANAKA', given='HANAKO')
    page = J(synth.render_passport((l1, l2)))
    for img in (page, cv2.rotate(page, cv2.ROTATE_180)):
        res, mrz = e._read_mrz(warp(img), None)
        assert mrz and mrz['valid'], mrz
        assert mrz['issuing_country'] == 'JPN' and res['fields']['id_number'] == 'TK1234567'
        assert res['fields']['name_en'] == 'HANAKO TANAKA'
        assert res['fields']['birth_date'] == '1988-03-05' and res['fields']['expiry_date'] == '2033-03-05'
        assert res['id_checksum_ok'] and res['ocr_confidence'] == 1.0


def test_unknown_doc_type_falls_back_to_thai_id():
    assert 'thai_id' in S.DOC_TYPES and S.FACE_SIDE['thai_dl'] == 'left'


@needs_tess
def test_tsv_parser_rebuilds_thai_words():
    tsv = ('level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext\n'
           '5\t1\t1\t1\t1\t1\t10\t10\t20\t40\t90\tน\n'
           '5\t1\t1\t1\t1\t2\t32\t10\t20\t40\t90\tา\n'
           '5\t1\t1\t1\t1\t3\t54\t10\t20\t40\t90\tย\n'
           '5\t1\t1\t1\t1\t4\t110\t10\t60\t40\t80\tสมชาย\n')
    rows = Tesseract.parse_tsv(tsv)
    assert rows[0][0] == 'นาย สมชาย'
    assert 0.8 < rows[0][1] < 0.9


def test_today_is_bangkok_date():
    assert isinstance(S.today_bkk(), date)

# -*- coding: utf-8 -*-
"""
OCR ตัวที่สองด้วย Tesseract 5 (Apache-2.0) — เรียก binary `tesseract` ผ่าน subprocess (ไม่มี dependency Python เพิ่ม)

ทำไมต้องมีตัวที่สอง: recognizer ภาษาไทยของ EasyOCR (gen1) อ่านชื่อไทย/วันที่ไทยบนบัตรจริงแทบไม่ได้
(prod 2026-11: name_th = 0 เกือบทุกใบ) ส่วน Tesseract LSTM ภาษาไทยอ่านตัวพิมพ์บนบัตรได้ดีกว่ามาก
และเร็วกว่าบน CPU — service จะอ่านทั้งสองตัวแล้วเลือกค่าที่ "ตรวจสอบได้" (checksum / วันที่ไทย=อังกฤษ)

- ภาพส่งทาง stdin เป็น PNG ไม่เขียนลงดิสก์ / ผลอ่านไม่ log
- ใช้ข้อมูลภาษา tha + eng จากแพ็กเกจ Debian (tesseract-ocr-tha / -eng) ที่ติดตั้งใน image ตอน build
- ไม่มี binary หรือ timeout = คืนรายการว่าง (service ยังใช้ผล EasyOCR ต่อได้)
"""
from __future__ import annotations

import os
import re
import shutil
import subprocess

import cv2
import numpy as np

from .thai_id import Box

_THAI = re.compile('[฀-๿]')


def _binary() -> str | None:
    env = os.environ.get('EKYC_TESSERACT_BIN', '')
    if env and os.path.isfile(env):
        return env
    found = shutil.which('tesseract')
    if found:
        return found
    win = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
    return win if os.path.isfile(win) else None


class Tesseract:
    """ตัวเรียก tesseract แบบ thread-safe (แต่ละครั้งเป็น process แยก)"""

    def __init__(self, threads: int = 2, timeout: float = 12.0, tessdata: str | None = None):
        self.bin = _binary()
        self.timeout = timeout
        self.env = dict(os.environ)
        # tesseract ใช้ OpenMP ภายใน — คุมจำนวน thread ต่อ process ไม่ให้แย่ง CPU กับ torch
        self.env['OMP_THREAD_LIMIT'] = str(max(1, threads))
        if tessdata:
            self.env['TESSDATA_PREFIX'] = tessdata
        self._langs: set[str] | None = None

    @property
    def available(self) -> bool:
        return bool(self.bin) and 'tha' in self.langs() and 'eng' in self.langs()

    def langs(self) -> set[str]:
        if self._langs is None:
            self._langs = set()
            if self.bin:
                try:
                    r = subprocess.run([self.bin, '--list-langs'], capture_output=True, timeout=10, env=self.env)
                    lines = r.stdout.decode('utf-8', 'replace').splitlines()
                    self._langs = {ln.strip() for ln in lines[1:] if ln.strip()}
                except (OSError, subprocess.SubprocessError):
                    self._langs = set()
        return self._langs

    def run_tsv(self, gray: np.ndarray, lang: str, psm: int, extra: list[str]) -> str:
        """เรียก tesseract คืน TSV (ข้อความว่างเมื่อผิดพลาด/หมดเวลา)"""
        if not self.bin:
            return ''
        ok, png = cv2.imencode('.png', gray)
        if not ok:
            return ''
        cmd = [self.bin, 'stdin', 'stdout', '-l', lang, '--psm', str(psm), '--dpi', '300',
               '-c', 'preserve_interword_spaces=1', '-c', 'tessedit_create_tsv=1', *extra]
        try:
            r = subprocess.run(cmd, input=png.tobytes(), capture_output=True, timeout=self.timeout, env=self.env)
        except (OSError, subprocess.SubprocessError):
            return ''
        return r.stdout.decode('utf-8', 'replace') if r.returncode == 0 else ''

    @staticmethod
    def parse_tsv(tsv: str) -> list[tuple[str, float, float, float, float, float]]:
        """
        TSV → บรรทัด (ข้อความ, conf 0..1, x0, y0, x1, y1)
        tesseract แยกภาษาไทยเป็น "คำละตัวอักษร" (ภาษาไทยไม่มีช่องว่างระหว่างคำ) จึงประกอบบรรทัดใหม่เอง:
        ช่องว่างระหว่างกล่องกว้างกว่า 0.6 × ความสูงตัวอักษร = เว้นวรรค ไม่งั้นติดกัน
        (วัดจากบัตรสังเคราะห์: ระหว่างตัวอักษรในคำ ≤ 0.5×, ระหว่างคำ ≥ 0.85×)
        """
        rows: dict[tuple, list] = {}
        for row in tsv.splitlines()[1:]:
            parts = row.split('\t')
            if len(parts) < 12 or parts[0] != '5' or not parts[11].strip():
                continue
            try:
                conf = float(parts[10])
                left, top, w, h = (float(parts[i]) for i in (6, 7, 8, 9))
            except ValueError:
                continue
            if conf < 0:
                continue
            rows.setdefault((parts[2], parts[3], parts[4]), []).append(
                (left, top, left + w, top + h, conf, parts[11].strip()))
        out = []
        for words in rows.values():
            words.sort(key=lambda w: w[0])
            # ความสูงอ้างอิง = มัธยฐานความสูงกล่อง (สระบน/ล่าง/วรรณยุกต์ทำให้บางกล่องเตี้ย)
            hs = sorted(w[3] - w[1] for w in words)
            href = max(1.0, hs[len(hs) // 2])
            text = words[0][5]
            for prev, cur in zip(words, words[1:]):
                gap = cur[0] - prev[2]
                # คำภาษาอังกฤษ/ตัวเลขใน TSV เป็น "คำจริง" อยู่แล้ว → เว้นวรรคเมื่อมีช่องว่างเล็กน้อยก็พอ
                latin = not _THAI.search(prev[5]) and not _THAI.search(cur[5])
                text += (' ' if gap > (0.15 if latin else 0.6) * href else '') + cur[5]
            total = sum(len(w[5]) for w in words)
            conf = sum(w[4] * len(w[5]) for w in words) / max(1, total) / 100.0
            out.append((text, conf, words[0][0], min(w[1] for w in words), max(w[2] for w in words),
                        max(w[3] for w in words)))
        return out

    def lines(self, gray: np.ndarray, lang: str = 'tha+eng', psm: int = 11, x_off: float = 0.0,
              y_off: float = 0.0, scale: float = 1.0) -> list[Box]:
        """
        อ่านทั้งภาพ คืนกล่องระดับ "บรรทัด" (พิกัดในภาพต้นฉบับ: x / scale + off)
        psm 11 = หาข้อความกระจัดกระจาย (เหมาะกับบัตรที่ข้อความวางเป็นช่องๆ)
        """
        return [Box(t, c, a / scale + x_off, b / scale + y_off, d / scale + x_off, e / scale + y_off)
                for t, c, a, b, d, e in self.parse_tsv(self.run_tsv(gray, lang, psm, []))]

    def line_text(self, gray: np.ndarray, lang: str, whitelist: str | None = None, psm: int = 7) -> tuple[str, float]:
        """อ่านหนึ่งบรรทัด (psm 7) คืน (ข้อความ, ความมั่นใจเฉลี่ย 0..1)"""
        extra = ['-c', f'tessedit_char_whitelist={whitelist}'] if whitelist else []
        got = sorted(self.parse_tsv(self.run_tsv(gray, lang, psm, extra)), key=lambda r: r[2])
        if not got:
            return '', 0.0
        total = sum(len(r[0]) for r in got)
        return ' '.join(r[0] for r in got), sum(r[1] * len(r[0]) for r in got) / max(1, total)


def prep(bgr: np.ndarray, target_h: int = 900) -> tuple[np.ndarray, float]:
    """
    เตรียมภาพสำหรับ tesseract: เทา + ขยายให้ตัวอักษรสูงพอ (บัตรสูง ~900 px → ตัวหนังสือหลัก ~40 px)
    + ลดลายพื้นหลังบัตร (กิโยเช่) ด้วย top-hat สีดำ — คืน (ภาพ, scale)
    """
    g = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY) if bgr.ndim == 3 else bgr
    s = target_h / float(g.shape[0])
    if abs(s - 1.0) > 0.05:
        g = cv2.resize(g, None, fx=s, fy=s, interpolation=cv2.INTER_CUBIC if s > 1 else cv2.INTER_AREA)
    else:
        s = 1.0
    # ทำพื้นให้สว่างเรียบ: หารด้วยพื้นหลังที่เบลอมาก (แก้แสงไม่เท่ากัน/เงา)
    bg = cv2.GaussianBlur(g, (0, 0), 25)
    norm = cv2.divide(g, bg, scale=235)
    return norm, s

# -*- coding: utf-8 -*-
"""
(เฉพาะเครื่อง dev) ดาวน์โหลดภาพตัวอย่างใบหน้าจริง/ปลอมจาก repo Silent-Face-Anti-Spoofing (Apache-2.0)
มาไว้ทดสอบ anti-spoof / คนละคน / ทิศ yaw — ไม่ commit ภาพเหล่านี้เข้า repo และไม่ใส่ใน Docker image

    python scripts/fetch_test_faces.py --out ./test-faces
    EKYC_TEST_FACES_DIR=./test-faces python -m pytest -q

image_T1.jpg = ใบหน้าจริง (หันหน้าราว 20°), image_F1/F2.jpg = ภาพถ่ายซ้ำจากรูป/จอ (ปลอม)
"""
from __future__ import annotations

import argparse
import hashlib
import os
import sys
import urllib.request

BASE = ('https://raw.githubusercontent.com/minivision-ai/Silent-Face-Anti-Spoofing/'
        'b6d5f04ad78778917853b25c778acef6d5626d15/images/sample/')
FILES = {
    'image_T1.jpg': 'f4455149f488f76205fdee5499ec5261d08ef6279a1cff7b778ea85405331e94',
    'image_F1.jpg': '4b11b5d7a8a8e4a88f5f16a5426a0a7692e39e5bb45bb03b4ebe5e1606336860',
    'image_F2.jpg': 'fbbea73450ae9d9bb555c8ccac77bf39d234261fe3be4190e3ed2999690c485f',
}


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--out', default='test-faces')
    a = ap.parse_args()
    os.makedirs(a.out, exist_ok=True)
    for name, sha in FILES.items():
        with urllib.request.urlopen(BASE + name, timeout=60) as r:
            data = r.read()
        got = hashlib.sha256(data).hexdigest()
        if got != sha:
            print(f'SHA256 mismatch {name}: {got}', file=sys.stderr)
            return 1
        with open(os.path.join(a.out, name), 'wb') as f:
            f.write(data)
        print('ok', name)
    return 0


if __name__ == '__main__':
    sys.exit(main())

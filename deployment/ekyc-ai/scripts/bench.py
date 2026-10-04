# -*- coding: utf-8 -*-
"""
วัดเวลาแต่ละ endpoint + RAM สูงสุดของโปรเซส (รันในโปรเซสเดียวผ่าน TestClient — ไม่ผ่านเครือข่าย)

    EKYC_AI_KEY=bench-key-0123456789 EKYC_MODEL_DIR=/path/models python scripts/bench.py --runs 3

ต้องมี psutil (requirements-dev.txt) และฟอนต์ไทยของ repo (ใช้สร้างบัตรสังเคราะห์)
รายงาน wall time และ CPU time ของโปรเซส (CPU time ÷ wall ≈ จำนวนคอร์ที่ใช้จริง)
"""
from __future__ import annotations

import argparse
import os
import statistics
import sys
import time

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path[:0] = [ROOT, os.path.join(ROOT, 'tests')]
os.environ.setdefault('EKYC_AI_KEY', 'bench-key-0123456789abcdef')
os.environ.setdefault('EKYC_REQUEST_TIMEOUT', '300')


def rss_mb(p) -> tuple[float, float]:
    mi = p.memory_info()
    peak = getattr(mi, 'peak_wset', None)
    if peak is None:
        import resource
        peak = resource.getrusage(resource.RUSAGE_SELF).ru_maxrss * 1024
    return mi.rss / 2 ** 20, peak / 2 ** 20


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--runs', type=int, default=3)
    ap.add_argument('--above-normal', action='store_true',
                    help='(Windows) ยกลำดับความสำคัญโปรเซสนี้ — ใช้เมื่อเครื่อง dev มีงานอื่นกิน CPU เต็ม')
    a = ap.parse_args()
    import cv2
    import numpy as np
    import psutil
    import synth
    from fastapi.testclient import TestClient

    proc = psutil.Process()
    if a.above_normal and hasattr(psutil, 'ABOVE_NORMAL_PRIORITY_CLASS'):
        proc.nice(psutil.ABOVE_NORMAL_PRIORITY_CLASS)
    key = {'X-Ekyc-Key': os.environ['EKYC_AI_KEY']}
    print('baseline rss=%.0f MB' % rss_mb(proc)[0])
    t = time.perf_counter()
    from app.main import app
    with TestClient(app) as c:
        print('startup (import + model load + warmup) %.1fs, rss=%.0f MB peak=%.0f MB'
              % ((time.perf_counter() - t), *rss_mb(proc)))
        scene, _ = synth.place_in_scene(synth.render_card(width=2000), 3000, 2250)   # ~6.75 MP ภาพจากกล้อง
        card = synth.jpeg(scene, 90)
        face = synth.astronaut_bgr()
        frame = synth.jpeg(cv2.resize(face, (720, 720), interpolation=cv2.INTER_CUBIC), 90)
        frames = [frame] * 4
        labels = ['neutral', 'blink', 'turn_left', 'smile']

        def timed(fn):
            c0, w0 = time.process_time(), time.perf_counter()
            r = fn()
            assert r.status_code == 200, r.text
            return time.perf_counter() - w0, time.process_time() - c0

        results = {}
        results['GET /health'] = [timed(lambda: c.get('/health', headers=key)) for _ in range(10)]
        r = c.post('/v1/id-card', headers=key, files={'image': ('c.jpg', card, 'image/jpeg')}).json()
        print('id-card sample: reasons=%s ocr=%.3f boxes=%s second_pass=%s timing=%s' % (
            r.get('reasons'), r.get('ocr_confidence', 0), r['details'].get('ocr_boxes'),
            r['details'].get('ocr_second_pass'), r.get('timing')))
        results['POST /v1/id-card'] = [timed(lambda: c.post('/v1/id-card', headers=key,
                                                            files={'image': ('c.jpg', card, 'image/jpeg')}))
                                       for _ in range(a.runs)]
        files = [('card_image', ('c.jpg', card, 'image/jpeg'))] + \
                [('frames[]', (f'f{i}.jpg', f, 'image/jpeg')) for i, f in enumerate(frames)]
        results['POST /v1/face/verify (4 frames)'] = [
            timed(lambda: c.post('/v1/face/verify', headers=key, files=files, data={'labels[]': labels}))
            for _ in range(a.runs)]
        files8 = [('card_image', ('c.jpg', card, 'image/jpeg'))] + \
                 [('frames[]', (f'f{i}.jpg', frame, 'image/jpeg')) for i in range(8)]
        results['POST /v1/face/verify (8 frames)'] = [
            timed(lambda: c.post('/v1/face/verify', headers=key, files=files8,
                                 data={'labels[]': ['neutral'] + ['blink', 'smile', 'nod', 'turn_left',
                                                                  'turn_right', 'blink', 'smile']}))
            for _ in range(a.runs)]
        for name, vals in results.items():
            walls = [v[0] for v in vals]
            cpus = [v[1] for v in vals]
            print('%-34s wall median %.2fs (min %.2f max %.2f)  cpu median %.2fs'
                  % (name, statistics.median(walls), min(walls), max(walls), statistics.median(cpus)))
        cur, peak = rss_mb(proc)
        print('rss now=%.0f MB, peak=%.0f MB' % (cur, peak))
        print('machine cpu load during bench ~%.0f%% (logical cpus=%d)' % (psutil.cpu_percent(1.0),
                                                                          psutil.cpu_count()))
    return 0


if __name__ == '__main__':
    sys.exit(main())

# -*- coding: utf-8 -*-
"""
รันบริการ: python -m app
worker เดียวเสมอ (โมเดลโหลดครั้งเดียว ~1 GB RAM) — ความขนานอยู่ที่เธรดพูล EKYC_WORKERS
"""
import os

import uvicorn

if __name__ == '__main__':
    uvicorn.run('app.main:app', host=os.environ.get('EKYC_HOST', '0.0.0.0'),
                port=int(os.environ.get('EKYC_PORT', '8003')), workers=1, server_header=False,
                timeout_keep_alive=5, limit_concurrency=32, proxy_headers=False)

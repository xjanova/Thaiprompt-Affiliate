# -*- coding: utf-8 -*-
"""
eKYC AI service (FastAPI) — รันใน container `ekyc-ai` ผูกพอร์ต 127.0.0.1:8003 บนเครื่อง prod

ความปลอดภัย
- ทุกคำขอ (รวม /health) ต้องมี header X-Ekyc-Key ตรงกับ env EKYC_AI_KEY (เทียบแบบ constant-time)
- ไม่มีคีย์ = ไม่ยอมเริ่มบริการ (ยกเว้น EKYC_AI_DEV=1 บนเครื่อง dev)
- รูปละไม่เกิน 8 MB, ไม่เกิน 8 เฟรม, ไม่เกิน 25 ล้านพิกเซล, body รวมถูกจำกัดตั้งแต่ระดับ ASGI
- ประมวลผลในเธรดพูลขนาดเล็ก + คิวจำกัด (เกิน = 503 BUSY) + timeout ต่อคำขอ (504 TIMEOUT)
- ไม่ log รูป/ข้อความ OCR — log แค่เวลา คะแนน รหัสเหตุผล
- ปิด /docs /openapi.json
"""
from __future__ import annotations

import asyncio
import hmac
import logging
import math
import os
import re
import threading
import time
import traceback
from concurrent.futures import ThreadPoolExecutor
from contextlib import asynccontextmanager

from .config import SERVICE_VERSION, load_settings, validate_settings  # ต้องมาก่อน cv2

from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from starlette.datastructures import UploadFile
from starlette.exceptions import HTTPException as StarletteHTTPException

from .imaging import BadImage
from .liveness import CHALLENGES, VALID_LABELS

logging.basicConfig(level=os.environ.get('EKYC_LOG_LEVEL', 'INFO'),
                    format='%(asctime)s %(levelname)s %(name)s %(message)s')
log = logging.getLogger('ekyc')

settings = load_settings()
validate_settings(settings)          # ไม่มีคีย์ → SystemExit ตั้งแต่ import (uvicorn ไม่เริ่ม)

MAX_BODY = settings.max_image_bytes * (settings.max_frames + 1) + 1024 * 1024


class ApiError(Exception):
    def __init__(self, status: int, code: str, message: str, field: str | None = None):
        super().__init__(code)
        self.status, self.code, self.message, self.field = status, code, message, field


class _BodyTooLarge(Exception):
    pass


def err(status: int, code: str, message: str, field: str | None = None) -> JSONResponse:
    body = {'ok': False, 'error': code, 'message': message}
    if field:
        body['field'] = field
    return JSONResponse(body, status_code=status)


def _clean(o):
    """แทน NaN/Inf ด้วย 0.0 (JSON มาตรฐานไม่รองรับ) และแปลง numpy scalar เป็น Python"""
    if isinstance(o, dict):
        return {k: _clean(v) for k, v in o.items()}
    if isinstance(o, (list, tuple)):
        return [_clean(v) for v in o]
    if hasattr(o, 'item') and not isinstance(o, (str, bytes)):
        try:
            o = o.item()
        except Exception:  # noqa: BLE001
            pass
    if isinstance(o, float) and not math.isfinite(o):
        return 0.0
    return o


class GuardMiddleware:
    """ASGI middleware: ตรวจคีย์ก่อนอ่าน body + จำกัดขนาด body (ทั้ง Content-Length และแบบ chunked)"""

    def __init__(self, app):
        self.app = app
        self.key = settings.api_key.encode('utf-8')

    async def _send_json(self, send, status: int, code: str, message: str):
        resp = err(status, code, message)
        await send({'type': 'http.response.start', 'status': status,
                    'headers': [(b'content-type', b'application/json'),
                                (b'content-length', str(len(resp.body)).encode())]})
        await send({'type': 'http.response.body', 'body': resp.body})

    async def __call__(self, scope, receive, send):
        if scope['type'] != 'http':
            return await self.app(scope, receive, send)
        headers = {k.lower(): v for k, v in scope.get('headers', [])}
        if self.key or not settings.dev_mode:
            got = headers.get(b'x-ekyc-key', b'')
            if not self.key or not hmac.compare_digest(got, self.key):
                return await self._send_json(send, 401, 'UNAUTHORIZED', 'missing or invalid X-Ekyc-Key')
        cl = headers.get(b'content-length')
        if cl is not None:
            try:
                if int(cl) > MAX_BODY:
                    return await self._send_json(send, 413, 'PAYLOAD_TOO_LARGE', 'request body too large')
            except ValueError:
                return await self._send_json(send, 422, 'BAD_REQUEST', 'invalid content-length')
        received = 0
        started = False

        async def limited_receive():
            nonlocal received
            msg = await receive()
            if msg.get('type') == 'http.request':
                received += len(msg.get('body', b''))
                if received > MAX_BODY:
                    raise _BodyTooLarge()
            return msg

        async def tracking_send(msg):
            nonlocal started
            if msg.get('type') == 'http.response.start':
                started = True
            await send(msg)

        try:
            await self.app(scope, limited_receive, tracking_send)
        except _BodyTooLarge:
            if not started:
                await self._send_json(send, 413, 'PAYLOAD_TOO_LARGE', 'request body too large')


@asynccontextmanager
async def lifespan(app: FastAPI):
    from .service import Engine
    t = time.perf_counter()
    engine = Engine(settings)
    if os.environ.get('EKYC_SKIP_WARMUP') != '1':
        engine.warmup()
    app.state.engine = engine
    app.state.pool = ThreadPoolExecutor(max_workers=settings.workers, thread_name_prefix='ekyc')
    app.state.slots = threading.BoundedSemaphore(settings.workers + settings.max_queue)
    app.state.inflight = 0
    app.state.started = time.time()
    log.info('ready version=%s load_ms=%d startup_ms=%d workers=%d queue=%d mirrored=%s',
             SERVICE_VERSION, engine.load_ms, int((time.perf_counter() - t) * 1000), settings.workers,
             settings.max_queue, settings.frames_mirrored)
    yield
    app.state.pool.shutdown(wait=False, cancel_futures=True)
    engine.face.close()


app = FastAPI(title='Thai Prompt eKYC AI', version=SERVICE_VERSION, lifespan=lifespan,
              docs_url=None, redoc_url=None, openapi_url=None)
app.add_middleware(GuardMiddleware)


@app.exception_handler(StarletteHTTPException)
async def _http_exc(request: Request, exc: StarletteHTTPException):
    code = {404: 'NOT_FOUND', 405: 'METHOD_NOT_ALLOWED'}.get(exc.status_code, 'BAD_REQUEST')
    # multipart เสีย (Starlette ตอบ 400) = คำขอผิดรูป → 422 ตามข้อตกลงกับ backend
    status = 422 if exc.status_code == 400 else exc.status_code
    # ไม่สะท้อน detail กลับ (อาจมีข้อมูลจาก input)
    return err(status, code, code.lower().replace('_', ' '))


@app.exception_handler(ApiError)
async def _api_exc(request: Request, exc: ApiError):
    return err(exc.status, exc.code, exc.message, exc.field)


async def _read_upload(item, field: str) -> bytes:
    """
    อ่านไฟล์ที่อัปโหลด — ไม่มี part นี้ = คำขอผิดรูป (422) · ใหญ่เกิน = 413
    ไฟล์ว่าง/เสีย/ไม่ใช่ภาพ ไม่ถือเป็นคำขอผิดรูป: ส่งต่อให้ pipeline ตอบ HTTP 200 + ok=false + reasons
    """
    if not isinstance(item, UploadFile):
        raise ApiError(422, 'MISSING_FILE', f'{field} must be an uploaded file', field)
    data = await item.read(settings.max_image_bytes + 1)
    if len(data) > settings.max_image_bytes:
        raise ApiError(413, 'IMAGE_TOO_LARGE', f'{field} exceeds {settings.max_image_bytes} bytes', field)
    return data


async def _run(request: Request, fn, *args):
    st = request.app.state
    if not st.slots.acquire(blocking=False):
        return err(503, 'BUSY', 'server busy, retry shortly')
    loop = asyncio.get_running_loop()
    st.inflight += 1

    def _done(_):
        st.slots.release()
        st.inflight -= 1

    fut = loop.run_in_executor(st.pool, fn, *args)
    fut.add_done_callback(_done)
    try:
        # shield: ถ้า timeout เธรดยังทำงานต่อจนจบ และจะคืน slot เมื่อเสร็จจริง (กันงานซ้อนเกินเครื่องรับไหว)
        result = await asyncio.wait_for(asyncio.shield(fut), timeout=settings.request_timeout_s)
    except asyncio.TimeoutError:
        log.warning('timeout fn=%s after %.0fs', getattr(fn, '__name__', '?'), settings.request_timeout_s)
        return err(504, 'TIMEOUT', 'processing timeout')
    except BadImage as e:   # กันไว้อีกชั้น (pipeline จัดการเองแล้ว) — ภาพเสียต้องเป็น 200 ไม่ใช่ 4xx
        body = {'ok': False, 'error': 'BAD_IMAGE', 'error_detail': e.code, 'reasons': []}
        if getattr(e, 'field', None):
            body['field'] = e.field
        return JSONResponse(body)
    except Exception as e:  # noqa: BLE001
        # log เฉพาะชนิด exception + ตำแหน่งโค้ด ไม่ log ข้อความ (กันข้อมูลหลุด)
        tb = traceback.extract_tb(e.__traceback__)
        where = ' <- '.join(f'{os.path.basename(f.filename)}:{f.lineno}' for f in reversed(tb[-4:]))
        log.error('internal error type=%s at %s', type(e).__name__, where)
        return err(500, 'INTERNAL', 'internal error')
    return JSONResponse(_clean(result))


@app.get('/health')
async def health(request: Request):
    st = request.app.state
    eng = st.engine
    return {'ok': True, 'service_version': SERVICE_VERSION, 'model_version': eng.model_version,
            'models': eng.model_versions(), 'uptime_s': int(time.time() - st.started),
            'inflight': st.inflight, 'workers': settings.workers, 'frames_mirrored': settings.frames_mirrored}


@app.post('/v1/id-card')
async def id_card(request: Request):
    form = await request.form(max_files=2, max_fields=8)
    try:
        data = await _read_upload(form.get('image'), 'image')
    finally:
        await form.close()
    return await _run(request, _id_card_job, request.app.state.engine, data)


def _id_card_job(engine, data: bytes):
    return engine.id_card(data)


def _collect(form, base: str) -> list:
    """
    อ่านค่าหลายตัวของ field เดียวกันตามลำดับที่ส่งมา รองรับทั้ง `frames[]`, `frames`
    และแบบมีดัชนี `frames[0]`, `frames[1]`, ... (Guzzle/Laravel บางแบบส่งแบบนี้)
    """
    items = form.multi_items()
    vals = [v for k, v in items if k in (base + '[]', base)]
    if vals:
        return vals
    indexed = []
    for k, v in items:
        m = re.fullmatch(re.escape(base) + r'\[(\d{1,2})\]', k)
        if m:
            indexed.append((int(m.group(1)), v))
    return [v for _, v in sorted(indexed, key=lambda t: t[0])]


def _parse_bool(v) -> bool | None:
    if v is None or isinstance(v, UploadFile):
        return None
    s = str(v).strip().lower()
    if s in ('1', 'true', 'yes', 'on'):
        return True
    if s in ('0', 'false', 'no', 'off'):
        return False
    return None


@app.post('/v1/face/verify')
async def face_verify(request: Request):
    form = await request.form(max_files=settings.max_frames + 4, max_fields=settings.max_frames + 12)
    try:
        frames_in = _collect(form, 'frames')
        labels = [str(x).strip() for x in _collect(form, 'labels') if not isinstance(x, UploadFile)]
        if not frames_in:
            raise ApiError(422, 'MISSING_FRAMES', 'frames[] is required', 'frames[]')
        if len(frames_in) > settings.max_frames:
            raise ApiError(422, 'TOO_MANY_FRAMES', f'at most {settings.max_frames} frames', 'frames[]')
        if len(labels) != len(frames_in):
            raise ApiError(422, 'LABELS_MISMATCH', 'labels[] must have the same length as frames[]', 'labels[]')
        bad = [x for x in labels if x not in VALID_LABELS]
        if bad:
            raise ApiError(422, 'BAD_LABEL', f'labels must be one of {",".join(VALID_LABELS)}', 'labels[]')
        if labels[0] != 'neutral':
            raise ApiError(422, 'FIRST_FRAME_NOT_NEUTRAL', 'the first frame must be labelled neutral', 'labels[]')
        if not any(x in CHALLENGES for x in labels):
            raise ApiError(422, 'NO_CHALLENGE_FRAMES', 'at least one challenge frame is required', 'labels[]')
        mirrored = _parse_bool(form.get('mirrored'))
        if mirrored is None:
            mirrored = settings.frames_mirrored
        card = await _read_upload(form.get('card_image'), 'card_image')
        frames = [await _read_upload(f, f'frames[{i}]') for i, f in enumerate(frames_in)]
    finally:
        await form.close()
    return await _run(request, _face_job, request.app.state.engine, card, frames, labels, mirrored)


def _face_job(engine, card: bytes, frames: list[bytes], labels: list[str], mirrored: bool):
    return engine.face_verify(card, frames, labels, mirrored)

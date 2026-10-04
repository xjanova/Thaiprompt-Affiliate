# syntax=docker/dockerfile:1
# =============================================================================
# Thai Prompt eKYC AI service — CPU only, ไม่ต่อเน็ตตอน runtime
#
#   docker build -t thaiprompt/ekyc-ai:1.0.0 deployment/ekyc-ai
#   docker run -d --name ekyc-ai --restart unless-stopped -p 127.0.0.1:8003:8003 \
#       --memory 3g --cpus 2 -e EKYC_AI_KEY=... thaiprompt/ekyc-ai:1.0.0
#
# stage 1 (models): ดาวน์โหลดโมเดลจาก URL ที่ปักเวอร์ชัน + ตรวจ sha256 + แปลง MiniFASNet .pth → .onnx
# stage 2 (runtime): ติดตั้งแพ็กเกจ + คัดลอกโมเดล + ทดลองโหลดทุกโมเดลตอน build (ล้ม = build ล้ม)
# =============================================================================
ARG PYTHON_IMAGE=python:3.11.17-slim-bookworm@sha256:2333bd330d12de02514770b3585cad313644316047cdee24a7acfdece6de6efb

# ----------------------------------------------------------------------------- stage 1
FROM ${PYTHON_IMAGE} AS models
ENV PIP_NO_CACHE_DIR=1 PIP_DISABLE_PIP_VERSION_CHECK=1 PYTHONDONTWRITEBYTECODE=1
WORKDIR /build
COPY requirements-build.txt ./
RUN pip install -r requirements-build.txt
COPY scripts/download_models.py scripts/convert_minifasnet.py ./scripts/
RUN python scripts/download_models.py --models-dir /models \
 && python -W ignore::DeprecationWarning scripts/convert_minifasnet.py --models-dir /models \
 && rm -f /models/minifasnet/*.pth

# ----------------------------------------------------------------------------- stage 2
FROM ${PYTHON_IMAGE}
ENV PIP_NO_CACHE_DIR=1 PIP_DISABLE_PIP_VERSION_CHECK=1 \
    PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 PYTHONUTF8=1 \
    EKYC_MODEL_DIR=/opt/ekyc/models \
    MPLBACKEND=Agg MPLCONFIGDIR=/opt/ekyc/mpl \
    GLOG_minloglevel=2 TF_CPP_MIN_LOG_LEVEL=2 \
    OMP_NUM_THREADS=2 MKL_NUM_THREADS=2

# libEGL/libGLESv2: libmediapipe.so ลิงก์ไว้ (ไม่ได้ใช้ GPU จริง) · libgomp: OpenMP ของ onnxruntime/torch
RUN apt-get update \
 && apt-get install -y --no-install-recommends libegl1 libgles2 libgl1 libglib2.0-0 libgomp1 \
 && rm -rf /var/lib/apt/lists/* \
 && useradd --system --uid 10001 --home-dir /app --shell /usr/sbin/nologin ekyc

WORKDIR /app
COPY requirements.txt requirements-nodeps.txt ./
RUN pip install -r requirements.txt \
 && pip install --no-deps -r requirements-nodeps.txt

COPY --from=models /models /opt/ekyc/models
COPY app ./app

# สร้าง cache ฟอนต์ของ matplotlib (mediapipe import มัน) + ทดลองโหลดและรันทุกโมเดลหนึ่งรอบ
RUN mkdir -p /opt/ekyc/mpl \
 && EKYC_AI_DEV=1 python -W ignore -c "from app.config import load_settings; from app.service import Engine; e = Engine(load_settings()); e.warmup(); print('selfcheck ok', e.model_versions())" \
 && chown -R ekyc:ekyc /opt/ekyc/mpl \
 && chmod -R a-w /opt/ekyc/models

USER ekyc
EXPOSE 8003
HEALTHCHECK --interval=30s --timeout=5s --start-period=180s --retries=3 \
  CMD python -c "import os,sys,urllib.request as u; r=u.Request('http://127.0.0.1:8003/health', headers={'X-Ekyc-Key': os.environ.get('EKYC_AI_KEY','')}); sys.exit(0 if u.urlopen(r, timeout=4).status == 200 else 1)"

# worker เดียว (โมเดลโหลดครั้งเดียว) — ขนานด้วยเธรดพูลภายใน (EKYC_WORKERS)
CMD ["python", "-W", "ignore", "-m", "app"]

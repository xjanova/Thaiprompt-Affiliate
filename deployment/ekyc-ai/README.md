# eKYC AI service (Thai Prompt)

บริการ AI สำหรับยืนยันตัวตน (eKYC) ที่รันบนเซิร์ฟเวอร์ของเราเอง — **ฟรี ไม่ส่งภาพบัตร/ใบหน้าออกนอกเซิร์ฟเวอร์**
Laravel (backend) เรียกผ่าน `http://127.0.0.1:8003` เท่านั้น

| งาน | โมเดล (ไลเซนส์) |
|---|---|
| หาบัตร + ตัดปรับมุม | OpenCV contour + perspective warp |
| อ่านบัตร (OCR ไทย/อังกฤษ) | EasyOCR `['th','en']` (Apache-2.0) |
| หาใบหน้า | YuNet (MIT) |
| เทียบใบหน้า | SFace (Apache-2.0) |
| กะพริบตา/ยิ้ม/หัน/พยักหน้า | MediaPipe Face Landmarker (Apache-2.0) |
| ใบหน้าจริงหรือภาพถ่าย/จอ (passive) | MiniFASNet — Silent-Face-Anti-Spoofing (Apache-2.0) ผ่าน onnxruntime |

**ไม่มี InsightFace หรือโมเดลห้ามใช้เชิงพาณิชย์** — รายละเอียด URL/ไลเซนส์/sha256 ดู [MODELS.md](MODELS.md)

---

## 1. ติดตั้งบน prod (Docker)

ต้องมี Docker บนเครื่อง prod · image ใช้ RAM ราว 1–1.5 GB ตอนทำงาน (จำกัดไว้ 3 GB) · CPU 2 คอร์ · ดิสก์ ~3 GB

### 1.1 สร้างคีย์ลับ (ครั้งแรกครั้งเดียว)

```bash
openssl rand -hex 32          # ได้สตริงยาว 64 ตัว → ใช้เป็น EKYC_AI_KEY
```

ใส่ค่าเดียวกันใน **2 ที่**:

1. `.env` ของ Laravel: `EKYC_AI_KEY=<คีย์>` (และ `EKYC_AI_URL=http://127.0.0.1:8003`) แล้ว **ต้องรัน `php artisan config:cache`** (prod ใช้ config cache — แก้ .env อย่างเดียวไม่มีผล)
2. ส่งให้ container ตอน `docker run` (ด้านล่าง)

### 1.2 build image

รันจากโฟลเดอร์ราก repo บน prod (มี `deployment/ekyc-ai/`) — ครั้งแรกใช้เวลา ~10–20 นาที (ดาวน์โหลด torch CPU + โมเดล ~600 MB)

```bash
cd /home/admin/domains/main.thaiprompt.online/public_html
docker build -t thaiprompt/ekyc-ai:1.0.0 deployment/ekyc-ai
```

ระหว่าง build จะ:
- ดาวน์โหลดโมเดลจาก URL ที่ปักเวอร์ชัน และตรวจ sha256 (ไม่ตรง = build ล้ม)
- แปลง MiniFASNet `.pth → .onnx` แล้วตรวจผลเทียบ PyTorch
- ทดลองโหลดและรันทุกโมเดลหนึ่งรอบ (`selfcheck ok`) — ถ้าโมเดลไหนใช้ไม่ได้ build จะล้มตั้งแต่ตรงนี้

### 1.3 run

```bash
docker run -d --name ekyc-ai --restart unless-stopped \
  -p 127.0.0.1:8003:8003 --memory 3g --cpus 2 \
  -e EKYC_AI_KEY=<คีย์จากข้อ 1.1> \
  thaiprompt/ekyc-ai:1.0.0
```

แนะนำ (ไม่ให้คีย์ค้างใน shell history / `ps`): เก็บคีย์ในไฟล์แล้วใช้ `--env-file`

```bash
umask 077; printf 'EKYC_AI_KEY=%s\n' '<คีย์>' > /home/admin/.ekyc-ai.env
docker run -d --name ekyc-ai --restart unless-stopped \
  -p 127.0.0.1:8003:8003 --memory 3g --cpus 2 \
  --env-file /home/admin/.ekyc-ai.env \
  --security-opt no-new-privileges --cap-drop ALL --pids-limit 256 \
  thaiprompt/ekyc-ai:1.0.0
```

- ผูกพอร์ตกับ `127.0.0.1` เท่านั้น — **ห้ามเปิด `-p 8003:8003` ออกโลก**
- ไม่มี `EKYC_AI_KEY` (หรือสั้นกว่า 16 ตัว) container จะไม่ยอมเริ่ม
- เริ่มครั้งแรกใช้ ~20–60 วินาที (โหลดโมเดล + warmup) — `docker ps` จะขึ้น `healthy` เมื่อพร้อม

### 1.4 ตรวจว่าทำงาน

```bash
docker ps --filter name=ekyc-ai                    # STATUS ต้องเป็น (healthy)
docker logs --tail 50 ekyc-ai                      # ต้องเห็นบรรทัด "ready version=..."
curl -s -H "X-Ekyc-Key: <คีย์>" http://127.0.0.1:8003/health
```

### 1.5 อัปเดตเวอร์ชัน / ย้อนกลับ

```bash
docker build -t thaiprompt/ekyc-ai:1.0.1 deployment/ekyc-ai
docker rm -f ekyc-ai
docker run -d --name ekyc-ai ... thaiprompt/ekyc-ai:1.0.1     # คำสั่งเดียวกับข้อ 1.3 เปลี่ยนแค่ tag
# ย้อนกลับ: docker rm -f ekyc-ai แล้ว run ด้วย tag เดิม (1.0.0)
```

ระหว่างสลับ container (~1 นาที) backend จะได้ connection refused → ต้องส่งเคสเข้าคิวแอดมิน (`review`) ตามสัญญา ไม่ใช่ปฏิเสธผู้ใช้

---

## 2. API

ทุกคำขอ (รวม `/health`) ต้องมี header `X-Ekyc-Key: <EKYC_AI_KEY>` — ไม่มี/ผิด = `401`
ตอบเป็น JSON snake_case เสมอ

**ข้อตกลงกับ backend (`app/Services/Ekyc/EkycAiClient.php`)** — backend ถือว่า HTTP 4xx/5xx ทุกตัว = "AI ล่ม"
แล้วส่งเคสให้แอดมินตรวจ แทนที่จะขอให้ผู้ใช้ถ่ายใหม่ ดังนั้น:
- ภาพอ่านไม่ได้ (ไฟล์เสีย/ไม่ใช่ภาพ/ว่าง/เกิน 25 ล้านพิกเซล/เล็กกว่า 64 px), ไม่เจอบัตร, ไม่เจอหน้า
  → **HTTP 200** + `reasons` (และ `ok: false`, `error: "BAD_IMAGE"` เมื่อมีภาพอ่านไม่ได้)
- `401` = คีย์ผิด · `413` = ภาพ/คำขอใหญ่เกิน · `422` = คำขอผิดรูป (ไม่มี part ที่ต้องมี, label ผิด ฯลฯ) เท่านั้น
- ชื่อ field multipart ตามที่ backend ส่งจริง: `image`, `card_image`, `frames[]`, `labels[]` (แบบ PHP มีวงเล็บ
  หนึ่ง part ต่อหนึ่งเฟรม/label) — รับ `frames`/`labels` (ไม่มีวงเล็บ) และ `frames[0]`, `frames[1]`… ได้ด้วย
- `ok: true` = ประมวลผลครบทุกภาพ (ผ่าน/ไม่ผ่านดูที่คะแนน/`reasons`)

### `GET /health`

```json
{"ok": true, "service_version": "1.0.0", "model_version": "ekyc-ai/1.0.0+yunet2023mar+...",
 "models": {"yunet": "face_detection_yunet_2023mar", "sface": "face_recognition_sface_2021dec",
            "face_landmarker": "face_landmarker/float16/1", "easyocr_craft": "craft_mlt_25k",
            "easyocr_thai": "thai_g1", "minifasnet_v2": "2.7_80x80_MiniFASNetV2",
            "minifasnet_v1se": "4_0_0_80x80_MiniFASNetV1SE", "minifasnet_onnx": "converted-from-pth@build"},
 "uptime_s": 120, "inflight": 0, "workers": 2, "frames_mirrored": true}
```

### `POST /v1/id-card` — multipart `image` (JPEG/PNG/WebP ≤ 8 MB)

รับได้ทั้งภาพถ่ายบัตรวางบนโต๊ะ (จะหาขอบบัตร 4 มุมแล้วปรับมุมเอง) และภาพที่มือถือครอปมาแล้ว
(ML Kit document scanner) · บัตรกลับหัว/แนวตั้งก็ได้ (ดูตำแหน่งรูปหน้าบนบัตรเพื่อหมุนให้ตั้ง)

```json
{
  "ok": true,
  "card_detected": true,
  "quality": {"blur": 0.0, "glare": 0.0, "complete": true},
  "fields": {"id_number": "1103702071811", "name_th": "นาย สมชาย ใจดี", "name_en": "Mr. Somchai Jaidee",
             "birth_date": "1995-01-12", "expiry_date": "2029-01-11", "issue_date": "2020-01-01"},
  "field_confidence": {"id_number": 0.96, "name_th": 0.86, "birth_date": 0.81, "expiry_date": 0.91,
                       "name_en": 0.68, "issue_date": 0.85},
  "ocr_confidence": 0.885,
  "id_checksum_ok": true,
  "card_face_found": true,
  "card_face_box": [1187, 641, 172, 214],
  "card_real_score": 1.0,
  "reasons": [],
  "model_version": "ekyc-ai/1.0.0+...",
  "name_parts": {"th": {"title": "นาย", "first": "สมชาย", "last": "ใจดี"},
                 "en": {"title": "Mr.", "first": "Somchai", "last": "Jaidee"}},
  "details": {"input_hash": "…", "quad_found": true, "precropped": false, "rotation": 0, "laplacian_var": 1026.5,
              "glare_ratio": 0.0, "moire": 0.0, "moire_peak": 1.36, "colorfulness": 22.4, "copy": 0.0,
              "ocr_boxes": 12, "ocr_second_pass": false},
  "timing": {"prep_ms": 319, "ocr_ms": 9079, "total_ms": 9399}
}
```

ความหมายค่า:

| ค่า | ความหมาย |
|---|---|
| วันที่ทุกช่อง | ค.ศ. `YYYY-MM-DD` (แปลงจาก พ.ศ. บนบัตร: `12 ม.ค. 2538` → `1995-01-12`, รองรับชื่อเดือนเต็ม/ไม่มีจุด/เลขไทย) |
| `expiry_date: null` + `EXPIRY_LIFELONG` | บัตร "ตลอดชีพ" |
| `quality.blur` | 0 = คม, 1 = เบลอมาก (จาก Laplacian variance บนภาพบัตร 1000 px: ≥400 → 0, ≤30 → 1, สเกล log) — `≥ 0.7` → `BLURRY` |
| `quality.glare` | 0 = ไม่มี, 1 = แสงสะท้อน ≥ 4% ของบัตร (ดวงขาวจ้า V≥250, S≤40 ขนาด 0.1–40% ของบัตร) — `≥ 0.5` → `GLARE` |
| `quality.complete` | เห็นบัตรครบ 4 มุม ไม่ชิดขอบภาพ (ภาพที่ครอปมาแล้วถือว่าครบ) |
| `field_confidence.*` | ความน่าจะเป็นของ OCR ผสมหลักฐานตรวจสอบ: เลขบัตร = 0.5·OCR + 0.5·(checksum ผ่าน) · วันเกิด = 0.5·OCR + 0.25·(อายุ 0–120 ปี) + 0.25·(วันที่ไทยกับอังกฤษตรงกัน) · วันหมดอายุ = 0.5·OCR + 0.25·(หมดอายุหลังวันออกบัตร) + 0.25·(ไทย/อังกฤษตรงกัน) · ชื่อไทย = 0.6·OCR + 0.4·(มีคำนำหน้า+ชื่อ+นามสกุล) |
| `ocr_confidence` | ค่าเฉลี่ยของ `id_number`, `name_th`, `birth_date`, `expiry_date` (ช่องที่อ่านไม่ได้นับเป็น 0) — `< 0.6` หรือไม่มีเลขบัตร → `OCR_LOW` |
| `card_real_score` | 1 = บัตรจริง, ต่ำ = น่าจะถ่ายจากจอ/สำเนา — **heuristic** (ดูหัวข้อ 4) — `< 0.5` → `SPOOF_SUSPECTED` |
| `card_face_box` | กรอบรูปหน้าบนบัตร `[x, y, w, h]` เป็น **พิกเซลของภาพที่ส่งมา** (แปลงกลับจากภาพที่ปรับมุม/หมุนแล้ว) — backend ใช้ครอปรูปหน้าเก็บให้แอดมิน · `null` = ไม่เจอหน้า |
| `details.input_hash` | 16 ตัวแรกของ sha256 ของไฟล์ภาพ — backend ใช้จับการส่งไฟล์เดิมซ้ำได้ |

### `POST /v1/face/verify` — multipart

| field | ค่า |
|---|---|
| `card_image` | ภาพบัตรใบเดียวกับที่ส่ง `/v1/id-card` (backend ส่งจากที่เก็บไว้ ห้ามรับจากแอปตรงๆ) |
| `frames[]` | JPEG เซลฟี 2–8 ภาพ (≤ 8 MB ต่อภาพ) |
| `labels[]` | จำนวนเท่ากับ `frames[]` แต่ละตัวเป็น `neutral`, `blink`, `turn_left`, `turn_right`, `smile`, `nod` — **ตัวแรกต้องเป็น `neutral`** และต้องมี challenge อย่างน้อย 1 ตัว |
| `mirrored` (ไม่บังคับ) | `1`/`0` — ภาพเป็นแบบกระจกหรือไม่ (ค่าเริ่มต้นจาก env `EKYC_FRAMES_MIRRORED=1`) |

```json
{
  "ok": true,
  "faces_found": 4,
  "same_person_across_frames": true,
  "liveness": {"passed": true, "score": 0.93, "challenges": {"blink": true, "turn_left": true, "smile": true}},
  "anti_spoof": {"real_score": 0.97},
  "match": {"cosine": 0.52, "score": 0.93},
  "best_frame_index": 0,
  "card_face_box": [1187, 641, 172, 214],
  "reasons": [],
  "model_version": "ekyc-ai/1.0.0+...",
  "mirrored": true,
  "details": {"card_detected": true, "input_hashes": {"card_image": "…", "frames": ["…"]}, "neutral_ok": true,
              "challenge_strength": {"blink": 1.0, "turn_left": 0.9, "smile": 0.88},
              "frames": [{"label": "neutral", "faces": 1, "landmarks": true, "yaw": 1.2, "pitch": 12.0, "ear": 0.3,
                          "mouth_w": 0.7, "blink_bs": 0.03, "smile_bs": 0.1, "real": 0.98, "cos_to_neutral": null}]},
  "timing": {"card_ms": 300, "frames_ms": 900, "total_ms": 1200}
}
```

กติกา liveness (`liveness.passed = true` เมื่อ **ครบทุกข้อ**):
1. ทุกเฟรมมีหน้าคนเดียว (YuNet score ≥ 0.8; หน้าที่เล็กกว่า 15% ของหน้าหลักไม่นับ)
2. เฟรมแรก `neutral` หันตรง (|yaw| ≤ 25°) และลืมตา
3. ทุก challenge ผ่าน **เทียบกับเฟรม neutral**:
   - `blink` : EAR ลดลง ≥ 30% หรือ blendshape `eyeBlink` ≥ 0.5 (เพิ่มขึ้น ≥ 0.3)
   - `smile` : ความกว้างปาก/ระยะหางตา เพิ่ม ≥ 10% หรือ blendshape `mouthSmile` ≥ 0.5 (เพิ่มขึ้น ≥ 0.25)
   - `turn_left` / `turn_right` : yaw เปลี่ยน ≥ 15° ไปทาง **ซ้าย/ขวาของผู้ใช้เอง**
   - `nod` : pitch เปลี่ยน ≥ 10° (ก้มหรือเงย)
4. คนเดียวกันทุกเฟรม: SFace cosine กับเฟรม neutral ≥ 0.45 (เฟรมหันหน้า ≥ 0.35)
5. `anti_spoof.real_score` (มัธยฐานของ MiniFASNet ทุกเฟรมที่เจอหน้า) ≥ 0.5

`liveness.score` = ค่าเฉลี่ยความแรงของ challenge (ผ่านพอดีเกณฑ์ = 0.8, ทำได้ 2 เท่าของเกณฑ์ = 1.0) × (1 ถ้าข้อ 1/2/4 ผ่าน ไม่งั้น 0.5)

**การเทียบหน้า**: SFace cosine ระหว่างรูปหน้าบนบัตร (YuNet ครอป+จัดแนว) กับ "เฟรมที่ดีที่สุด" (หน้าตรง ตาเปิด เลือก `neutral` ก่อน) →
`match.score = 1 / (1 + e^-((cosine − 0.363)/0.06))` — 0.363 คือเกณฑ์คนเดียวกันที่ OpenCV แนะนำสำหรับ SFace
(cos 0.15→0.03 · 0.30→0.26 · 0.363→0.50 · 0.45→0.81 · 0.50→0.91 · 0.60→0.98) — backend ตัดสินด้วย `cosine` ดิบ
`faces_found` = จำนวนเฟรมที่เจอหน้า **พอดีหนึ่งคน** · `card_face_box` = กรอบหน้าบน `card_image` (พิกเซลของภาพที่ส่งมา)
ภาพเสีย: `card_image` เสีย → `NO_CARD_FACE` · เฟรมเสีย → นับเป็นเฟรมไม่มีหน้า (`NO_FACE`) · ทั้งสองกรณี `ok: false`,
`error: "BAD_IMAGE"`, `bad_inputs: [{"field": "frames[1]", "code": "CORRUPT_IMAGE"}]` — HTTP 200

### ทิศ ซ้าย/ขวา (กล้องหน้า)

- ค่าเริ่มต้น `EKYC_FRAMES_MIRRORED=1`: ภาพเซลฟีเป็นแบบ **กระจก** (เหมือนพรีวิวที่ผู้ใช้เห็น)
- `turn_left` = ผู้ใช้หันไปทาง **ซ้ายของตัวเอง** → ในภาพแบบกระจก หน้าจะหันไปทาง **ซ้ายของภาพ**
- ถ้าแอปส่งภาพดิบจากเซนเซอร์ (ไม่กลับด้าน) ให้ส่ง `mirrored=0` (หรือตั้ง env เป็น 0) — ทิศจะกลับให้อัตโนมัติ
- ยืนยันแล้วด้วยภาพใบหน้าจริงที่หันหน้า + ภาพพลิกซ้ายขวา (`tests/test_endpoints.py::test_turn_direction_end_to_end_with_mirror_flag`)

### Reason codes

| code | endpoint | ความหมาย |
|---|---|---|
| `NO_CARD` | id-card | ไม่เจอบัตรในภาพ |
| `BLURRY` | id-card | ภาพเบลอ (`quality.blur ≥ 0.7`) |
| `GLARE` | id-card | แสงสะท้อน (`quality.glare ≥ 0.5`) |
| `CARD_INCOMPLETE` | id-card | บัตรตกขอบภาพ ไม่ครบ 4 มุม |
| `OCR_LOW` | id-card | อ่านไม่ชัด (`ocr_confidence < 0.6`) หรือหาเลขบัตรไม่เจอ |
| `ID_CHECKSUM_FAIL` | id-card | อ่านได้เลข 13 หลักแต่ checksum ไม่ผ่าน |
| `EXPIRED` | id-card | บัตรหมดอายุ (เทียบวันที่ปัจจุบันเวลาไทย) |
| `EXPIRY_LIFELONG` | id-card | บัตรตลอดชีพ (`expiry_date = null`) |
| `NO_CARD_FACE` | ทั้งคู่ | ไม่เจอรูปหน้าบนบัตร |
| `SPOOF_SUSPECTED` | ทั้งคู่ | id-card: `card_real_score < 0.5` · face: `real_score < 0.5` |
| `NO_FACE` | face | มีเฟรมที่ไม่เจอหน้า |
| `MULTIPLE_FACES` | face | มีเฟรมที่เจอหลายคน |
| `CHALLENGE_FAILED:<label>` | face | ทำท่าตามที่สั่งไม่ผ่าน |
| `DIFFERENT_PEOPLE` | face | หน้าในเฟรมต่างๆ ไม่ใช่คนเดียวกัน |
| `LOW_MATCH` | face | หน้าบนบัตรกับเซลฟีเหมือนกันน้อยกว่าเกณฑ์ (cosine < 0.363) |

### ภาพอ่านไม่ได้ (HTTP 200)

`/v1/id-card` ตอบโครงเดิมครบทุกคีย์ (ค่าว่าง/0) + `ok: false`, `error: "BAD_IMAGE"`,
`error_detail` = `UNSUPPORTED_FORMAT` / `CORRUPT_IMAGE` / `IMAGE_TOO_MANY_PIXELS` / `IMAGE_TOO_SMALL` / `EMPTY_IMAGE`,
`field: "image"`, `card_detected: false`, `reasons: ["NO_CARD", "NO_CARD_FACE"]` → backend สั่งถ่ายใหม่

### HTTP error (ตอบ `{"ok": false, "error": "<CODE>", "message": "...", "field": "..."}`)

| HTTP | error | เมื่อไร |
|---|---|---|
| 401 | `UNAUTHORIZED` | ไม่มี/ผิด `X-Ekyc-Key` |
| 413 | `IMAGE_TOO_LARGE` / `PAYLOAD_TOO_LARGE` | ภาพเกิน 8 MB (`field` บอกไฟล์) / body รวมเกินขนาด |
| 422 | `MISSING_FILE`, `MISSING_FRAMES`, `TOO_MANY_FRAMES`, `LABELS_MISMATCH`, `BAD_LABEL`, `FIRST_FRAME_NOT_NEUTRAL`, `NO_CHALLENGE_FRAMES`, `BAD_REQUEST` | คำขอผิดรูปแบบ (บั๊กฝั่งผู้เรียก ไม่ใช่ภาพของผู้ใช้) |
| 404 / 405 | `NOT_FOUND` / `METHOD_NOT_ALLOWED` | |
| 503 | `BUSY` | งานค้างเกิน (2 กำลังทำ + 4 รอคิว) |
| 504 | `TIMEOUT` | เกิน `EKYC_REQUEST_TIMEOUT` (23 วิ) |
| 500 | `INTERNAL` | ผิดพลาดภายใน (log มีแค่ชนิด exception + บรรทัดโค้ด) |

5xx / timeout / connection refused → backend ส่ง `review` (แอดมินตรวจ) ตามสัญญา

---

## 3. ตั้งค่า (env)

| env | ค่าเริ่ม | |
|---|---|---|
| `EKYC_AI_KEY` | — | **บังคับ** ≥ 16 ตัว |
| `EKYC_AI_DEV` | `0` | `1` = ยอมรันโดยไม่มีคีย์ (เครื่อง dev เท่านั้น) |
| `EKYC_MODEL_DIR` | `/opt/ekyc/models` | |
| `EKYC_MAX_IMAGE_BYTES` / `EKYC_MAX_FRAMES` / `EKYC_MAX_PIXELS` | 8 MB / 8 / 25,000,000 | |
| `EKYC_WORKERS` / `EKYC_MAX_QUEUE` | 2 / 4 | เธรดประมวลผล / คิวรอ (เกิน = 503) |
| `EKYC_REQUEST_TIMEOUT` | 23 | วินาที (backend timeout 25 — ตอบ 504 ให้ทันก่อน backend ตัด) |
| `EKYC_TORCH_THREADS` | 2 | เธรดของ torch/OpenCV |
| `EKYC_OCR_DET_WIDTH` | 640 | ความกว้างภาพตอนหาตำแหน่งข้อความ (ลด = เร็วขึ้น แต่ตัวเล็กอาจหลุด) |
| `EKYC_FRAMES_MIRRORED` | 1 | ดูหัวข้อทิศซ้าย/ขวา |
| `EKYC_TURN_DEG` / `EKYC_NOD_DEG` | 15 / 10 | องศาขั้นต่ำ |
| `EKYC_BLINK_EAR_RATIO` / `EKYC_BLINK_BLENDSHAPE` | 0.70 / 0.50 | |
| `EKYC_SMILE_WIDTH_RATIO` / `EKYC_SMILE_BLENDSHAPE` | 1.10 / 0.50 | |
| `EKYC_SAME_PERSON_COS` / `EKYC_SAME_PERSON_COS_TURN` | 0.45 / 0.35 | |
| `EKYC_REAL_THRESHOLD` | 0.50 | anti-spoof ขั้นต่ำของ liveness |
| `EKYC_MATCH_COS` | 0.363 | เกณฑ์ `LOW_MATCH` |
| `EKYC_FACE_DET_SCORE` / `EKYC_CARD_FACE_SCORE` | 0.80 / 0.60 | |
| `EKYC_BLUR_REASON` / `EKYC_GLARE_REASON` / `EKYC_OCR_LOW` / `EKYC_CARD_REAL_REASON` | 0.70 / 0.50 / 0.60 / 0.50 | เกณฑ์ออก reason |
| `EKYC_LOG_LEVEL` | INFO | |

---

## 4. ข้อจำกัดที่ต้องรู้ (สำคัญ)

- **`card_real_score` เป็น heuristic ไม่ใช่โมเดลที่เทรน**: (1) ลาย moiré/ตารางพิกเซลจอ = ยอดแหลมในสเปกตรัม FFT ช่วง 0.12–0.45 รอบ/พิกเซล บนครอปกลางบัตรที่ความละเอียดเดิมของกล้อง (2) สำเนาขาวดำ = colorfulness ต่ำ (3) แสงสะท้อน — สูตร `1 − max(0.9·moire, copy, 0.5·glare)` · ทดสอบกับภาพสังเคราะห์เท่านั้น (จอจำลอง/สำเนาจำลอง) **ยังไม่ได้ทดสอบกับบัตรจริงที่มีลายกิโยเช่** ซึ่งอาจทำให้คะแนนต่ำผิดๆ ได้ → backend ใช้เป็นสัญญาณส่งแอดมินตรวจ ไม่ควรใช้ปฏิเสธเด็ดขาด · สำเนาสี/ภาพพิมพ์คุณภาพสูงจับไม่ได้
- **OCR ทดสอบกับบัตรสังเคราะห์** (ฟอนต์ Anuphan บนพื้นไล่สี) ไม่ใช่บัตรจริง — ตำแหน่ง/ฟอนต์บนบัตรจริงต่างไป ควรเก็บสถิติ `reasons`/`ocr_confidence` ช่วงแรกแล้วจูน
- **เกณฑ์ challenge (กะพริบ/ยิ้ม/หัน/พยักหน้า) ยังไม่ได้ปรับกับเฟรมจริงจากแอป** — ทดสอบได้แค่ตรรกะ (ค่าสังเคราะห์) + ทิศ yaw กับภาพหน้าจริงที่หันอยู่แล้ว · ค่าใน `details.frames` ช่วยจูนผ่าน env ได้โดยไม่ต้อง build ใหม่
- MiniFASNet ต้นฉบับครอปด้วยกรอบหน้าจาก RetinaFace เราใช้ YuNet แทน (ทดสอบกับภาพตัวอย่างแล้วผลตรง)
- OCR ใช้ CPU หนัก (โมเดลไทย gen1) — ดูหัวข้อ 6 · งานพร้อมกันได้ 2 + คิว 4
- การอ่านรอบแรกเลือกกล่องตามโซนของแม่แบบบัตรด้านหน้า (อ้างอิงพิกัดจากแม่แบบบัตรจริงของโปรเจกต์
  ThaiPersonalCardExtract, Apache-2.0) — บัตรรุ่น/เลย์เอาต์อื่นจะตกไปอ่านรอบสอง (ครบทุกกล่อง ช้าขึ้น ~2 เท่า แต่ไม่พลาด)

## 5. ความปลอดภัย / PDPA

- คีย์เทียบแบบ constant-time · ไม่มีคีย์ไม่เริ่ม · ปิด `/docs` `/openapi.json`
- ไม่ log รูป ข้อความ OCR ชื่อ หรือเลขบัตร — log แค่เวลา คะแนน รหัสเหตุผล · error ภายใน log แค่ชนิด exception + บรรทัด
- ไม่เขียนภาพลงดิสก์ (ยกเว้นไฟล์ชั่วคราวของ multipart parser ที่ลบเองเมื่อจบคำขอ) · ไม่มีฐานข้อมูล ไม่เก็บอะไรข้ามคำขอ
- ถอดรหัสภาพแบบปลอดภัย: เช็ค magic bytes + ขนาดจาก header ก่อนถอด (กัน decompression bomb) · body จำกัดตั้งแต่ระดับ ASGI
- container รันเป็น user ไม่ใช่ root, โฟลเดอร์โมเดลอ่านอย่างเดียว, ไม่ต่อเน็ตตอน runtime

## 6. ประสิทธิภาพ (วัดบนเครื่อง dev — ดูหมายเหตุ)

วัดด้วย `scripts/bench.py` (ในโปรเซสเดียวผ่าน TestClient, ภาพบัตรวางบนโต๊ะ 3000×2250 ≈ 6.75 MP,
เฟรมเซลฟี 720×720) บน Windows · Python 3.12 · Intel i5-11400 (6C/12T) · torch 2 threads ·
**ระหว่างวัดเครื่องมีงานอื่น (build วิดีโอ 4 โปรเซส) กิน CPU 100% ตลอด** — ตั้ง priority โปรเซสวัดเป็น AboveNormal
ตัวเลขจริงบน prod (2 vCPU ที่ว่าง) ควรใกล้เคียงหรือเร็วกว่านี้ แต่ **ยังไม่ได้วัดบน prod**

| endpoint | wall (median ของ 3 รอบ) | CPU time ของโปรเซส |
|---|---|---|
| `GET /health` | < 5 ms | ~0 |
| `POST /v1/id-card` | **9.0 วินาที** (ขั้นตอนเตรียมภาพ ~0.3 วิ, OCR ~8.7 วิ) | 16.3 วิ |
| `POST /v1/face/verify` (บัตร + 4 เฟรม) | **0.75 วินาที** | 0.97 วิ |
| `POST /v1/face/verify` (บัตร + 8 เฟรม) | **1.19 วินาที** | 1.45 วิ |

RAM (RSS) — เริ่มโปรเซส 73 MB → หลังโหลดโมเดล + warmup ~1.0–1.1 GB → **สูงสุด 1.63 GB** ระหว่างอ่านบัตร
(จำกัด container ไว้ 3 GB เหลือเผื่อ ~1.4 GB) · เริ่มบริการ (โหลดโมเดล + warmup) ~16 วินาที

ทำไม OCR ช้า: โมเดลอ่านภาษาไทยของ EasyOCR (gen1, ResNet+BiLSTM) ใช้ ≈ 76 GFLOP ต่อกล่องข้อความ 64×384 px
และ CRAFT ≈ 190 GFLOP ที่ 640 px (วัดด้วย `torch.utils.flop_counter`) — จึง (1) หาตำแหน่งข้อความบนภาพย่อ 640 px
(2) อ่านรอบแรกเฉพาะโซนข้อมูลสำคัญตามแม่แบบบัตรจริง (~12 กล่องแทน ~26) แล้วอ่านส่วนที่เหลือเฉพาะเมื่อฟิลด์บังคับยังไม่ครบ
(3) จัดกลุ่มกล่องตามความกว้างไม่ให้เสียเวลากับ padding · ลองบีบภาพแนวนอนแล้ว ความแม่นภาษาไทยตกชัดเจน — ไม่ใช้
· งาน OCR ถูก lock ให้รันทีละงาน (ใช้ 2 คอร์เต็ม) ส่วนงานใบหน้ารันคู่ขนานได้ → ถ้ามีคนส่งบัตรพร้อมกัน 2 คน
คนที่สองรอ ~2 เท่า (อาจชน timeout 23 วิ → backend ส่ง `review`)

**ไวต่อ CPU ที่ถูกแย่ง**: รันเซิร์ฟเวอร์จริง (`python -m app`, priority ปกติ) บนเครื่องเดียวกันขณะงานอื่นกิน CPU 100%
คำขอ `/v1/id-card` ใช้ CPU แค่ ~20 วิ แต่ใช้เวลาจริง **148 วิ** (โดนแย่ง CPU) → ได้ 504 แต่ผลถูกต้องเมื่อทำเสร็จ
ส่วน `/v1/face/verify` 2 เฟรมใช้ 6 วิ — บน prod ต้องให้ VM มี CPU ว่างพอ (PHP-FPM/MySQL/queue อยู่ VM เดียวกัน)
ถ้าเจอ 504 บ่อย: เพิ่ม vCPU ให้ VM หรือ `--cpus 3` + `EKYC_TORCH_THREADS=3`

## 7. ทดสอบบนเครื่อง dev

```bash
cd deployment/ekyc-ai
python -m venv .venv && . .venv/bin/activate          # Windows: .venv\Scripts\activate
pip install -r requirements-dev.txt -r requirements-build.txt
pip install --no-deps -r requirements-nodeps.txt
python scripts/download_models.py --models-dir ./models
python scripts/convert_minifasnet.py --models-dir ./models
python scripts/fetch_test_faces.py --out ./test-faces              # ไม่บังคับ (ภาพหน้าจริง Apache-2.0 ไม่ commit)
EKYC_MODEL_DIR=./models EKYC_TEST_FACES_DIR=./test-faces python -m pytest -q
EKYC_MODEL_DIR=./models python scripts/bench.py --runs 3           # วัดเวลา + RAM
EKYC_AI_DEV=1 EKYC_MODEL_DIR=./models python -m app                # รันเซิร์ฟเวอร์ที่ :8003
```

Windows: ตั้ง `PYTHONUTF8=1` ก่อน (locale ไทย cp874 ทำให้อ่านไฟล์ UTF-8 พัง)

# โมเดลและไลเซนส์ — eKYC AI service

> กฎเจ้าของระบบ: **ห้ามใช้ InsightFace หรือโมเดลที่จำกัดการใช้งานเชิงพาณิชย์ทุกชนิด**
> ทุกโมเดลด้านล่างใช้เชิงพาณิชย์ได้ (MIT / Apache-2.0) และดาวน์โหลดตอน `docker build` เท่านั้น
> (URL ปักเวอร์ชัน/commit + ตรวจ sha256 ใน `scripts/download_models.py` — ไม่ตรง = build ล้ม)
> ตอนรันจริง container ไม่ต่ออินเทอร์เน็ต และภาพบัตร/ใบหน้าไม่ออกจากเซิร์ฟเวอร์ของเรา

## โมเดล

| คีย์ | ใช้ทำอะไร | ไลเซนส์ | ที่มา (ปักเวอร์ชัน) | sha256 |
|---|---|---|---|---|
| `yunet` | ตรวจหาใบหน้า (เซลฟี + รูปบนบัตร) ผ่าน `cv2.FaceDetectorYN` | **MIT** (Shiqi Yu, OpenCV Zoo) | `https://github.com/opencv/opencv_zoo/raw/47534e27c9851bb1128ccc0102f1145e27f23f98/models/face_detection_yunet/face_detection_yunet_2023mar.onnx` | `8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4` |
| `sface` | embedding ใบหน้า 128 มิติ เทียบบัตร↔เซลฟี และเฟรม↔เฟรม ผ่าน `cv2.FaceRecognizerSF` | **Apache-2.0** (OpenCV Zoo) | `https://github.com/opencv/opencv_zoo/raw/47534e27c9851bb1128ccc0102f1145e27f23f98/models/face_recognition_sface/face_recognition_sface_2021dec.onnx` | `0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79` |
| `face_landmarker` | MediaPipe Face Landmarker: 478 จุด + 52 blendshape + เมทริกซ์ท่าศีรษะ (กะพริบตา/ยิ้ม/หัน/พยักหน้า) | **Apache-2.0** (model card ของ BlazeFace short-range, Face Mesh V2, Blendshape V2 ระบุ "Licensed under Apache License, Version 2.0") | `https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task` | `64184e229b263107bc2b804c6625db1341ff2bb731874b0bcc2fe6544e0bc9ff` |
| `easyocr_craft` | หาตำแหน่งข้อความบนบัตร (CRAFT) | **MIT** (CRAFT-pytorch, © NAVER Corp.) แจกจ่ายผ่าน EasyOCR (**Apache-2.0**) | `https://github.com/JaidedAI/EasyOCR/releases/download/pre-v1.1.6/craft_mlt_25k.zip` → `craft_mlt_25k.pth` | zip `8dc6a1c703a89ed56308ef742d26ebd45c656248cbbbda6e7fe60e569f873e65` / pth `4a5efbfb48b4081100544e75e1e2b57f8de3d84f213004b14b85fd4b3748db17` (md5 `2f8227d2…9ec1` ตรงกับ easyocr 1.7.2) |
| `easyocr_thai` | อ่านข้อความไทย+อังกฤษ+ตัวเลข (`thai_g1`, ใช้กับ `Reader(['th','en'])`) | **Apache-2.0** (JaidedAI EasyOCR) | `https://github.com/JaidedAI/EasyOCR/releases/download/pre-v1.1.6/thai.zip` → `thai.pth` | zip `4f26ffd98dd28cca89ff6fe0bc761ab865c2b38fabd76a3ce2143d1d88c0d7f1` / pth `3ebbb9321b9c7c694e169cd23cc37f3c0bd2a160f5b539602bbef80e663e0a17` (md5 `40a06b56…c709` ตรงกับ easyocr 1.7.2) |
| `minifasnet_v2` | passive anti-spoof (ครอปหน้า ×2.7, 80×80) | **Apache-2.0** (Minivision Silent-Face-Anti-Spoofing) | `https://raw.githubusercontent.com/minivision-ai/Silent-Face-Anti-Spoofing/b6d5f04ad78778917853b25c778acef6d5626d15/resources/anti_spoof_models/2.7_80x80_MiniFASNetV2.pth` | `a5eb02e1843f19b5386b953cc4c9f011c3f985d0ee2bb9819eea9a142099bec0` |
| `minifasnet_v1se` | passive anti-spoof (ครอปหน้า ×4.0, 80×80) | **Apache-2.0** (Minivision) | `https://raw.githubusercontent.com/minivision-ai/Silent-Face-Anti-Spoofing/b6d5f04ad78778917853b25c778acef6d5626d15/resources/anti_spoof_models/4_0_0_80x80_MiniFASNetV1SE.pth` | `84ee1d37d96894d5e82de5a57df044ef80a58be2b218b5ed7cdfd875ec2f5990` |

### MiniFASNet → ONNX

ไม่มีไฟล์ ONNX ทางการจาก Minivision และไม่ใช้ไฟล์ ONNX ของบุคคลที่สาม (ที่มา/การแก้ไขตรวจไม่ได้)
จึงแปลงเองตอน build ด้วย `scripts/convert_minifasnet.py`:

- สถาปัตยกรรมคัดลอกจาก `src/model_lib/MiniFASNet.py` ของ repo ต้นฉบับ (Apache-2.0, ระบุที่มาและการแก้ไขไว้หัวไฟล์)
- โหลดน้ำหนักด้วย `torch.load(weights_only=True)` + `load_state_dict(strict=True)` (ชื่อ/ขนาดทุกชั้นต้องตรง)
- export ONNX opset 13 แล้ว **ตรวจผลเทียบ PyTorch** บนอินพุตสุ่ม 3 ชุด (ต่างกันเกิน 1e-3 = build ล้ม)
- ตรวจกับภาพตัวอย่างของ repo ต้นฉบับ: `image_T1.jpg` (ใบหน้าจริง) real = **1.00**, `image_F1.jpg` = **0.23**, `image_F2.jpg` = **0.003** (ภาพปลอม) — ตรงกับที่ repo ระบุ
- ไฟล์ `.pth` ถูกลบหลังแปลง เหลือเฉพาะ `.onnx` ใน image (sha256 ของ ONNX ไม่ปักเพราะขึ้นกับเวอร์ชัน torch exporter — ตรวจความถูกต้องด้วยการเทียบผลแทน)
- ความต่างจากต้นฉบับ: ต้นฉบับครอปหน้าด้วยกรอบจาก RetinaFace (Caffe) ส่วนเราใช้กรอบจาก YuNet

## แพ็กเกจ Python (ใน image)

ทั้งหมดไลเซนส์อนุญาตใช้เชิงพาณิชย์ (เวอร์ชันล็อกใน `requirements*.txt`):

| แพ็กเกจ | ไลเซนส์ |
|---|---|
| fastapi / starlette / uvicorn / pydantic | MIT / BSD-3 / BSD-3 / MIT |
| opencv-python-headless 4.14 | Apache-2.0 |
| onnxruntime 1.30 | MIT |
| mediapipe 1.0.1 | Apache-2.0 |
| easyocr 1.7.2 | Apache-2.0 |
| torch 2.14 (CPU) / torchvision 0.29 | BSD-3 (+ Apache-2.0 ส่วนประกอบ) / BSD-3 |
| numpy, scipy, scikit-image, shapely, networkx, sympy, imageio, tifffile | BSD |
| pillow | MIT-CMU (HPND) |
| matplotlib (mediapipe import ตอนโหลด) | Matplotlib License (PSF-based) |
| pyclipper, PyYAML, sounddevice, cffi | MIT / MIT-0 |
| python-multipart, absl-py, flatbuffers, ninja, opentelemetry-api | Apache-2.0 |
| certifi | MPL-2.0 (ใช้แบบไม่แก้ไข) |
| python-bidi (EasyOCR import) | **LGPL-3.0** — ใช้เป็นไลบรารีแบบไม่แก้ไขและไม่ได้แจกจ่าย binary ให้ลูกค้า (รันบนเซิร์ฟเวอร์เราเอง) จึงไม่มีภาระเปิดซอร์ส |

## ข้อมูลทดสอบ (ไม่อยู่ใน image)

- ฟอนต์ไทยสำหรับวาดบัตรสังเคราะห์: Anuphan / Noto Sans Thai จาก repo (SIL OFL 1.1)
- ภาพใบหน้า: `skimage.data.astronaut()` — ภาพนักบินอวกาศ Eileen Collins ของ NASA (**public domain**) มากับ scikit-image
- (ไม่บังคับ, ไม่ commit) `scripts/fetch_test_faces.py` ดึง `image_T1/F1/F2.jpg` จาก Silent-Face-Anti-Spoofing (Apache-2.0, commit ปักไว้ + sha256)

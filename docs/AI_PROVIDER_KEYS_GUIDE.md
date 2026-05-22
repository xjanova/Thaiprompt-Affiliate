# 🔑 คู่มือเอา API Key — ทุก provider ที่ระบบรองรับ

> **Version:** 1.0 | **Last Updated:** 2026-05-22
> ระบบรองรับ 11 providers — บางตัวฟรี บางตัวจ่าย เลือกตามงาน
>
> หน้า admin: `/admin/ai-api-keys` → "+ เพิ่ม Key"

---

## 📋 สารบัญ (เรียงตาม priority แนะนำ)

| # | Provider | Tier | งานที่เหมาะ | ใช้ใน purpose |
|---|----------|------|------------|---------------|
| 1 | [Groq](#1-groq-) | 🟢 ฟรีหลัก | Chat ความเร็วสูง | `chat`, `free_card` |
| 2 | [Google Gemini](#2-google-gemini-) | 🟢 ฟรี + 🟡 จ่าย | Chat + Vision + Deep | `chat`, `prediction_deep`, `free_card` |
| 3 | [OpenAI](#3-openai-) | 🟡 จ่าย | Reasoning + Celtic 99฿ | `prediction_celtic`, `sensitive` |
| 4 | [Anthropic Claude](#4-anthropic-claude-) | 🟡 จ่าย | คุณภาพ long-context | `sensitive`, `prediction_deep` |
| 5 | [xAI Grok](#5-xai-grok-) | 🟡 จ่าย ($150 trial) | General + reasoning | `chat`, `prediction` |
| 6 | [DeepSeek](#6-deepseek-) | 🟡 จ่ายถูกมาก | Reasoning, ภาษาจีน | `prediction_deep` |
| 7 | [OpenRouter](#7-openrouter-) | 🟢 มี `:free` model | Gateway หลาย provider | `any`, `chat` |
| 8 | [Qwen (Alibaba)](#8-qwen-alibaba-) | 🟢 ฟรี 6 เดือน | ภาษาจีน/ไทย | `chat` |
| 9 | [Typhoon (SCB 10X)](#9-typhoon-scb-10x-) | 🟢 ฟรี | **ภาษาไทยดีสุด** | `chat` |
| 10 | [Xiaomi MiMo](#10-xiaomi-mimo-) | 🟡 จ่าย | CN-friendly | `chat` |
| 11 | [MiniMax](#11-minimax-tts-) | 🟡 จ่าย | TTS (สังเคราะห์เสียง) | `tts` |

---

## 1. Groq 🟢

**ทำไมต้องใช้:** ฟรี + เร็วที่สุดในตลาด (LPU chip) + quota เยอะ → เหมาะเป็น **chat primary**

### 🌐 Console
👉 https://console.groq.com/keys

### 📝 ขั้นตอน
1. ไป https://console.groq.com/ → "Login" (Google / GitHub / email)
2. ผ่าน onboarding
3. คลิกเมนู **"API Keys"** ซ้ายมือ
4. **"Create API Key"** → ตั้งชื่อ (เช่น `tp-affiliate-chat-1`) → Submit
5. **Copy ทันที!** Groq แสดงครั้งเดียว — หาย = สร้างใหม่
6. กลับมาที่ระบบ `/admin/ai-api-keys` → "+ เพิ่ม Key" → provider=`Groq`, paste key

### 📊 Free tier (verified 2026-05)
- **RPM:** 30 ทุก model
- **RPD:** 14,400 (Llama 3.3 70B), 500,000 (gpt-oss-120b)
- **TPM:** 6,000-15,000 ขึ้นกับ model

### 🎯 Model แนะนำ
- `llama-3.3-70b-versatile` — เก่ง, general (default ของระบบ)
- `openai/gpt-oss-120b` — ใหม่ + ฉลาด (Aug 2025)
- `openai/gpt-oss-20b` — ถูก + เร็ว
- `meta-llama/llama-4-scout-17b-16e-instruct` — Llama 4

### 💡 Tips
- ตั้ง `purpose=chat` ใน admin UI
- สร้าง **หลาย key** (3-5 บัญชี) — Pool จะ round-robin
- `rate_limit_per_minute=30` ตั้ง explicit (smart default 28 อยู่แล้ว)

---

## 2. Google Gemini 🟢

**ทำไมต้องใช้:** ฟรี + multimodal (รูป/เสียง/วิดีโอ) + Pro รุ่นเก่งสำหรับ Deep reading

### 🌐 Console
👉 https://aistudio.google.com/app/apikey

### 📝 ขั้นตอน
1. ไป https://aistudio.google.com/ → Login ด้วย Gmail
2. กรอก profile (ครั้งแรก) + ยอมรับ ToS
3. **"Get API Key"** มุมบนซ้าย หรือ https://aistudio.google.com/app/apikey
4. **"Create API key"** → เลือก Google Cloud project (หรือ "Create new" ก็ได้)
5. Copy key (`AIzaSy...`)
6. กลับมา `/admin/ai-api-keys` → provider=`Google Gemini`, paste

### 📊 Free tier (verified 2026-05)
| Model | RPM | RPD |
|-------|-----|-----|
| **gemini-2.5-flash-lite** ⭐ | 15 | 1500 |
| gemini-2.5-flash | 10 | 500 |
| gemini-2.5-pro | 5 | 100 |
| gemini-2.0-flash-lite | 30 | 1500 |
| gemini-3.1-pro-preview | 5 | 50 |

### 🎯 Model แนะนำ
- **Chat:** `gemini-2.5-flash-lite` (โควต้าเยอะที่สุดใน 2.5 family) — ระบบเปลี่ยนให้แล้วในรอบ deploy ล่าสุด
- **Deep prediction:** `gemini-3.1-pro-preview` (ฉลาดสุด, agentic)
- **Vision:** `gemini-2.5-flash` (รับรูปได้)

### 💡 Tips
- 1 Google account = สร้างได้หลาย key
- **Paid tier:** ใส่บัตรเครดิตที่ Google Cloud Billing → quota ขึ้น 100x
- ถ้าใส่ paid key: ตั้ง `rate_limit_per_minute=1000` ใน admin UI (ไม่งั้นถูก throttle เป็น free)

---

## 3. OpenAI 🟡

**ทำไมต้องใช้:** Reasoning ดีที่สุด → ใช้ทำ **Celtic 99฿** (purpose=`prediction_celtic`)

### 🌐 Console
👉 https://platform.openai.com/api-keys

### 📝 ขั้นตอน
1. ไป https://platform.openai.com/ → Sign up
2. ⚠️ ต้อง **verify เบอร์โทร** + เพิ่มบัตรเครดิต (Billing)
3. Top up อย่างน้อย $5 — ไม่งั้น key ใช้ไม่ได้
4. ไป **API Keys** เมนูซ้าย → **"Create new secret key"**
5. ตั้งชื่อ + เลือก permissions (Restricted แนะนำ: เฉพาะ "Model capabilities → All")
6. Copy (`sk-proj-...` หรือ `sk-...`) — ดูครั้งเดียว
7. `/admin/ai-api-keys` → provider=`OpenAI`, paste

### 💰 Pricing (Tier 1, per 1M tokens)
| Model | Input | Output |
|-------|-------|--------|
| gpt-5.5-mini | $0.15 | $0.60 |
| gpt-5.5 | $2.50 | $10 |
| gpt-5.5-pro | $15 | $60 |

### 🎯 Model แนะนำ
- **Celtic 99฿:** `gpt-5.5-pro` (reasoning + agentic — ตามสเปคระบบ)
- **Sensitive:** `gpt-5.5` (Pro model — กรณีลูกค้าหัวข้อหนัก)
- **ทดสอบ:** `gpt-5.5-mini` (ถูกสุด)

### 💡 Tips
- ตั้ง **rate_limit_per_minute=500** ใน admin (Tier 1 ~500 RPM)
- หลังใช้ไป $50+ และผ่าน 7 วัน → upgrade Tier 2 อัตโนมัติ → RPM ขึ้น
- ใช้ purpose=`sensitive` หรือ `prediction_celtic` (strict scope — ไม่ fallback)

---

## 4. Anthropic Claude 🟡

**ทำไมต้องใช้:** Long-context ฉลาด + safety ดี → สำหรับงาน sensitive

### 🌐 Console
👉 https://console.anthropic.com/settings/keys

### 📝 ขั้นตอน
1. ไป https://console.anthropic.com/ → Sign up (email)
2. ⚠️ ปัจจุบันต้องมี **invite code** หรือรอ waitlist (สถานะอาจเปลี่ยน)
3. มี **$5 free credit** ตอนสมัครใหม่ (ใช้ทดสอบได้)
4. **Settings → API Keys → "Create Key"**
5. ตั้งชื่อ + workspace
6. Copy (`sk-ant-...`)
7. `/admin/ai-api-keys` → provider=`Anthropic Claude`, paste

### 💰 Pricing (per 1M tokens)
| Model | Input | Output |
|-------|-------|--------|
| claude-haiku-4.5 | $0.25 | $1.25 |
| claude-sonnet-4-6 | $3 | $15 |
| claude-opus-4-7 | $15 | $75 |

### 🎯 Model แนะนำ
- **Chat ทั่วไป:** `claude-haiku-4.5` (ถูก + เร็ว)
- **Mid quality:** `claude-sonnet-4-6`
- **Top tier:** `claude-opus-4-7` (reasoning ลึก)

### 💡 Tips
- ตั้ง `rate_limit_per_minute=50` (Tier 1)
- ถ้าใช้ผ่าน OpenRouter จะไม่ต้องตั้งบัญชี Anthropic ตรง (ดูข้อ 7)

---

## 5. xAI Grok 🟡

**ทำไมต้องใช้:** Grok 4 ใหม่ + มี $150 free trial credits ตอนสมัคร

### 🌐 Console
👉 https://console.x.ai/

### 📝 ขั้นตอน
1. ไป https://console.x.ai/ → Login ด้วย X (Twitter) account หรือ email
2. ผ่าน onboarding
3. **"API Keys"** ในเมนู
4. **"Create API Key"** → เลือก team
5. ✅ Permissions: tick `chat:write` (และ `image:write` ถ้าจะใช้ vision)
6. Copy (`xai-...`)
7. `/admin/ai-api-keys` → provider=`Grok (xAI)`, paste

### 💰 Pricing (per 1M tokens, Tier 1)
| Model | Input | Output |
|-------|-------|--------|
| grok-3-mini | $0.30 | $0.50 |
| grok-3 | $3 | $15 |
| grok-4 | $5 | $25 |

### 🎯 Model แนะนำ
- **Chat:** `grok-3-mini` (ถูกสุดในตระกูล 3)
- **General:** `grok-3-latest`
- **Top:** `grok-4-latest` (newest reasoning)

### 💡 Tips
- $150 free credits ใหม่ — หมดอายุใน 1 เดือน
- Vision: ใช้ `grok-2-vision-1212` (multimodal)
- ตั้ง `rate_limit_per_minute=60` (xAI Tier 1)

---

## 6. DeepSeek 🟡

**ทำไมต้องใช้:** ราคาถูกมาก ($0.07 / 1M tokens!) + R1 reasoning model

### 🌐 Console
👉 https://platform.deepseek.com/api_keys

### 📝 ขั้นตอน
1. ไป https://platform.deepseek.com/ → Sign up (email หรือ phone)
2. มี **$5 free credit** ตอนสมัครใหม่
3. **API Keys** เมนู → **"Create new API key"**
4. ตั้งชื่อ → Copy (`sk-...`)
5. `/admin/ai-api-keys` → provider=`DeepSeek`, paste

### 💰 Pricing (per 1M tokens)
| Model | Input | Output |
|-------|-------|--------|
| deepseek-chat | $0.07 | $1.10 |
| deepseek-reasoner | $0.55 | $2.19 |

### 🎯 Model แนะนำ
- **Chat:** `deepseek-chat` (general, super cheap)
- **Reasoning:** `deepseek-reasoner` (R1 — เทียบ o1 ราคาถูก)

### 💡 Tips
- ⚠️ Server อยู่ในจีน — บางครั้ง latency สูง
- ระบบรองรับใน Pool fallback (ไม่ใช่ primary)

---

## 7. OpenRouter 🟢

**ทำไมต้องใช้:** Gateway 1 key เข้าได้ Claude/GPT/Llama/Gemini ฯลฯ + มี `:free` model

### 🌐 Console
👉 https://openrouter.ai/keys

### 📝 ขั้นตอน
1. ไป https://openrouter.ai/ → "Sign in" (Google / GitHub / email)
2. (Optional) **Top up credits** → Settings → Credits → ขั้นต่ำ $5
3. คลิก **"Keys"** มุมขวาบน หรือ https://openrouter.ai/keys
4. **"Create Key"** → ตั้งชื่อ + (optional) credit limit
5. Copy (`sk-or-v1-...`)
6. `/admin/ai-api-keys` → provider=`OpenRouter`, paste

### 💰 Pricing
- ราคา **upstream + 5% markup**
- มี **free model** ปลายชื่อ `:free` (Llama, Gemini, Mistral) — แต่ rate limit ต่ำ

### 🎯 Model แนะนำ
- **Chat ฟรี:** `meta-llama/llama-3.3-70b-instruct:free` (ฟรี แต่ช้า)
- **Claude:** `anthropic/claude-haiku-4.5` (ถูก) / `anthropic/claude-sonnet-4` (mid)
- **GPT:** `openai/gpt-4o-mini` (ถูก)
- **Gemini:** `google/gemini-2.0-flash-001`

### 💡 Tips
- ใช้แทน Anthropic/OpenAI ถ้าไม่อยากสมัครหลายที่
- ⚠️ Latency สูงกว่า direct provider เล็กน้อย (+200ms)

---

## 8. Qwen (Alibaba) 🟢

**ทำไมต้องใช้:** ฟรี 6 เดือน + ภาษาจีน/ไทยดี + multimodal

### 🌐 Console
👉 https://bailian.console.alibabacloud.com/ (International — Singapore region)
หรือ 👉 https://dashscope.console.aliyun.com/apiKey (China region)

### 📝 ขั้นตอน (International — แนะนำ)
1. ไป https://www.alibabacloud.com/ → Sign up
2. Verify ด้วยบัตรเครดิต (international card, ไม่หักเงิน)
3. ไป **Model Studio (Bailian)** → Activate
4. **API-Key** เมนู → **"Create API Key"**
5. Copy (`sk-...`)
6. `/admin/ai-api-keys` → provider=`Qwen (Alibaba)`, paste

### 📊 Free tier
- **1M tokens free per model** ในช่วง 6 เดือนแรก
- หลังจากนั้น pay-as-you-go

### 🎯 Model แนะนำ
- **Chat:** `qwen-turbo` (ถูก + เร็ว)
- **General:** `qwen-plus`
- **Top:** `qwen-max` (เก่ง)

### 💡 Tips
- ผ่าน HF Inference Router ก็ได้ (ใส่ HF token เป็น key, base_url=HF)
- Cluster Singapore latency ดีในไทย

---

## 9. Typhoon (SCB 10X) 🟢

**ทำไมต้องใช้:** **ภาษาไทยดีที่สุด** (tune จาก Llama เน้นไทย) + ฟรี

### 🌐 Console
👉 https://opentyphoon.ai/

### 📝 ขั้นตอน
1. ไป https://opentyphoon.ai/ → "Sign Up"
2. Email + verify
3. **Playground** → "API" tab → **"Create API Key"**
4. Copy (`sk-...`)
5. `/admin/ai-api-keys` → provider=`Typhoon (SCB 10X)`, paste

### 📊 Free tier
- มี free quota (จำกัด, ตรวจในหน้า usage)
- Paid tier ราคาถูก (per call)

### 🎯 Model แนะนำ
- **ไทย:** `typhoon-v2-70b-instruct` (ใหญ่ + ไทยดี)
- **เร็ว:** `typhoon-v2-8b-instruct` (ถูก + เร็ว)

### 💡 Tips
- ใช้เป็น fallback เฉพาะลูกค้าไทย — quality ไทยดีกว่า Gemini บางที
- ตั้ง `purpose=chat` + priority สูง

---

## 10. Xiaomi MiMo 🟡

**ทำไมต้องใช้:** Model จีนใหม่ — ทดลอง / ภาษา CN ดี

### 🌐 Console
👉 https://api.xiaomimimo.com/ (PAY-AS-YOU-GO)
หรือ 👉 https://token-plan-cn.xiaomimimo.com/ (Token Plan — CN)

### 📝 ขั้นตอน
1. ไป https://api.xiaomimimo.com/ → Sign up (จีน — อาจต้อง VPN ถ้าไทย block)
2. Verify เบอร์โทร CN (หรือใช้ international account ถ้ามี option)
3. **API Keys** → Create
4. Copy
5. `/admin/ai-api-keys` → provider=`Xiaomi MiMo`, paste

### 🎯 Model แนะนำ
- `mimo-v2.5-pro` — flagship
- `mimo-v2-flash` — เร็ว + ถูก

### ⚠️ คำเตือน
- ตรวจสอบสิทธิ์การใช้ในไทย (อาจมีข้อจำกัด)
- ระบบรองรับใน Pool fallback ไม่ใช่ primary

---

## 11. MiniMax (TTS) 🎙️

**ทำไมต้องใช้:** **Text-to-Speech คุณภาพสูง** (เสียงไทย) — สำหรับ Celtic 99฿ บันทึกเสียง

### 🌐 Console
👉 https://platform.minimax.io/ (v2 endpoint — ใหม่)

### 📝 ขั้นตอน
1. ไป https://platform.minimax.io/ → Sign up (email หรือ Google)
2. Verify
3. **Account → API Keys → Create**
4. Copy (`...`)
5. `/admin/ai-api-keys` → provider=`MiniMax (TTS / Audio)`, paste
6. ⚠️ ตั้ง **`purpose=tts`** เสมอ (strict scope — กัน chat path call ผิด)

### 📊 Free tier
- มี trial credits ตอนสมัครใหม่
- Pay-as-you-go หลังจากนั้น

### 🎯 Model แนะนำ
- **`speech-2.8-hd`** ⭐ — HD quality (ใหม่สุด)
- `speech-2.8-turbo` — เร็ว + ถูก
- `speech-2.6-hd` — gen ก่อน

### 💡 Tips
- ใช้กับ Celtic 99฿ → สังเคราะห์เสียงสรุปคำทำนาย
- ⚠️ ห้ามตั้ง purpose='chat' — schema คนละแบบจะ call fail

---

## 🛡️ Best Practices ทั่วไป

### หลังเพิ่ม key แล้วต้องทำอะไรบ้าง?

1. **กดทดสอบ** ทันที — admin UI มีปุ่ม "🔍 ทดสอบ" → `last_test_passed_at` ต้องเขียว
   - ถ้าไม่ผ่าน → key ไม่เข้า Pool (health gate filter ออก)
2. **ตั้ง purpose** ให้ถูก:
   - Groq/Gemini free → `chat`
   - OpenAI/Claude paid → `sensitive` หรือ `prediction_celtic`
   - MiniMax → `tts`
3. **ตั้ง priority** — สูง=ใช้ก่อน, ต่ำ=backup:
   - Free keys → 80-100
   - Paid keys → 10-30 (ใช้เมื่อฟรีหมด)
4. **ตั้ง rate_limit_per_minute** เฉพาะ paid:
   - ถ้าว่าง → ระบบ assume free + smart default ตาม model
   - Paid Gemini = 1000, Paid OpenAI = 500, etc.

### ❓ จัด Pool ยังไงให้ประหยัดสุด?

```
Chat ปกติ:
  Priority 100: Free Groq (purpose=chat, RPM 28)
  Priority  90: Free Gemini Flash Lite (purpose=chat, RPM 14)
  Priority  80: Free Typhoon (purpose=chat, ภาษาไทยดี)
  Priority  10: Paid Gemini (purpose=chat, RPM 1000) ← backup เท่านั้น

Deep 39฿ (prediction_deep):
  Priority 100: Paid Gemini 3.1 Pro preview (RPM 5)
  Priority  50: Free Gemini 2.5 Pro (RPM 4)

Celtic 99฿ (prediction_celtic):
  Priority 100: Paid OpenAI gpt-5.5-pro

Sensitive (sensitive):
  Priority 100: Paid OpenAI gpt-5.5
  Priority  90: Paid Claude opus-4-7
```

### 🔍 Verify ผ่าน dashboard

หลัง deploy + ใช้ไป 1 ชม. ดูที่:
- `/admin/ai-api-keys/dashboard` — เห็น usage แยก provider + purpose
- Filter `1hr` → free providers ขึ้นเยอะ, paid น้อย = OK
- ถ้า paid ขึ้นเยอะแบบไม่ปกติ → ตรวจ rate_limit_per_minute + priority

---

## 📚 อ้างอิง

- ระบบ Pool: [app/Services/AiApiKeyPoolService.php](../app/Services/AiApiKeyPoolService.php)
- Model + base URL: [app/Models/AiApiKey.php](../app/Models/AiApiKey.php)
- Smart RPM table: `AiApiKey::MODEL_RPM_FREE_TIER` (verified 2026-05)
- Brain note ที่เกี่ยวข้อง: `Session 2026-05-22 #2 — AI Pool smart RPM default`

---

**Maintenance note:** เมื่อ provider ออก model ใหม่ → update ที่ 2 จุด:
1. `MODELS_BY_PROVIDER[provider]` array (สำหรับ admin dropdown)
2. `MODEL_RPM_FREE_TIER[provider][model_name]` ถ้า RPM ต่างจาก `_default`

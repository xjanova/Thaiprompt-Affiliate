# กริ่งสลิปและคำถามไพ่ฟรีสำหรับ juntraweb

## กริ่งสลิป

Thaiprompt ส่ง `POST {services.juntra.url}/api/server/chat-slips/ping`
พร้อม `Authorization: Bearer <token>` และ JSON `{"trans_ref":"..."}` เท่านั้น
อ่าน token จาก `services.juntra.chat_slip_ping_token` / `JUNTRA_CHAT_SLIP_PING_TOKEN`
ซึ่งต้องเป็นค่าเดียวกับ `services.thaiprompt.chat_slip_ping_token` /
`THAIPROMPT_CHAT_SLIP_PING_TOKEN` ฝั่ง juntraweb และยาวอย่างน้อย 32 ตัว
เป็น secret เฉพาะกริ่ง ไม่ใช้ Passport token หรือ API key อื่นร่วมกัน

ถ้า token ว่างหรือสั้น Thaiprompt จะไม่ยิงกริ่งและไม่เพิ่ม `ping_attempts`
แต่สลิปยังพักพร้อมเลขอ้างอิงที่จองไว้ ตัวกวาดทุกนาทีฝั่ง juntraweb มารับได้เหมือนเดิม
ถ้า token ใช้ได้ ยังใช้ connect timeout 3 วินาที / timeout 8 วินาทีเดิม
ไม่มีการเพิ่ม retry ภายใน request; การลองครั้งถัดไปใช้แถวเดิม
HTTP ผิดพลาด รวมถึง 401 หรือเชื่อมต่อไม่ได้ ไม่ลบสลิปและไม่ปล่อยเลขอ้างอิง
ไม่บันทึก token หรือ exception message ของการกดกริ่งลง log

ลำดับเปิดใช้ภายหลังผู้ดูแลอนุมัติ deploy:

1. เตรียม secret ผ่านช่องทางจัดการ secret ของผู้ดูแล โดยไม่ใส่ใน git หรือ log
2. ตั้งค่า env ทั้งสองฝั่งให้ตรงกัน แล้ว refresh config cache ทั้งสองแอป
3. เปิด receiver ที่ตรวจ bearer แบบ fail-closed แล้วเปิด caller นี้
4. ตรวจว่ากริ่งผ่านและตัวกวาดยังทำงาน ช่วง rollout ที่ config ไม่พร้อม/ไม่ตรงจะพึ่งตัวกวาด

PR นี้ไม่มี secret จริง และไม่ทำ merge/deploy/SSH

## คำถามไพ่ฟรี

ทั้ง `POST /api/v1/juntra/fortune/tarot/free` และ
`POST /api/v1/juntra/server/fortune/tarot/free` ใช้ `FreeTarotController` เดียวกัน
เพิ่ม top-level `question` แบบ `nullable|string|max:500` โดยคงฟิลด์เดิม:

```json
{
  "card": {"name_en": "The Sun", "name_th": "พระอาทิตย์", "is_reversed": false},
  "position_name": "คำตอบของไพ่",
  "max_chars": 500,
  "customer_name": "ผู้ทดสอบ",
  "question": "งานใหม่จะเป็นอย่างไร"
}
```

`card.name_en` จำเป็น; `card.name_th`, `card.is_reversed`, `card.meaning`,
`position_name`, `max_chars`, `customer_name`, `persona` เป็นตัวเลือกตาม validator เดิม
เมื่อมีคำถาม จะส่งเป็นข้อมูล JSON ใน user prompt พร้อมกฎระบบไม่ให้ทำตามคำสั่งแฝงในคำถาม
คำสั่งทำนายและคำอธิบายตำแหน่งเปลี่ยนเป็นตอบคำถาม ไม่ใช้คำสั่งภาพรวมชีวิตทับ
ไม่ส่งคำถาม / `null` / สตริงว่างหรือช่องว่าง = ใช้ prompt ภาพรวมชีวิตเดิม
คำถามเกิน 500 ตัวหรือชนิดอื่นไม่ผ่าน validation (422)
รูปแบบ response, ด่าน persona, จำนวนครั้งลอง AI, โควตา และบิลไม่เปลี่ยน

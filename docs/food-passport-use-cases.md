# Food Passport System - Use Cases & User Stories

## 🎭 User Personas

### 1. นาย A - เกษตรกรผู้ปลูกผักอินทรีย์
- **อายุ:** 45 ปี
- **ทักษะ:** ใช้สมาร์ทโฟนได้ พอใช้ LINE
- **ปัญหา:** ผลผลิตดีแต่ขายได้ราคาไม่สูง ลูกค้าไม่เชื่อว่าปลอดสาร
- **เป้าหมาย:** อยากพิสูจน์คุณภาพ ขายได้ราคาดี เพิ่มรายได้

### 2. คุณ B - ผู้บริหารซูเปอร์มาร์เก็ต
- **อายุ:** 38 ปี
- **ทักษะ:** เชี่ยวชาญด้านธุรกิจ
- **ปัญหา:** ลูกค้าต้องการสินค้าที่มีคุณภาพและโปร่งใส
- **เป้าหมาย:** หาซัพพลายเออร์ที่น่าเชื่อถือ สร้างความแตกต่าง

### 3. คุณ C - ผู้บริโภคที่ใส่ใจสุขภาพ
- **อายุ:** 32 ปี
- **ทักษะ:** Tech-savvy
- **ปัญหา:** ไม่แน่ใจว่าอาหารที่ซื้อมีคุณภาพจริงหรือไม่
- **เป้าหมาย:** กินอาหารดี ปลอดภัย รู้ที่มา

### 4. ดร. D - นักวิทยาศาสตร์ด้านอาหาร/ผู้ตรวจสอบ
- **อายุ:** 50 ปี
- **ทักษะ:** ผู้เชี่ยวชาญด้านมาตรฐานอาหาร
- **ปัญหา:** กระบวนการตรวจสอบช้า เอกสารมาก
- **เป้าหมาย:** ตรวจสอบได้รวดเร็ว มีหลักฐานชัดเจน

### 5. คุณ E - นักธุรกิจ Carbon Credit
- **อายุ:** 40 ปี
- **ทักษะ:** เข้าใจตลาด carbon
- **ปัญหา:** ขาดแหล่ง carbon credit ที่เชื่อถือได้
- **เป้าหมาย:** ซื้อ-ขาย carbon credit ที่มี verification

---

## 📖 Use Cases

### UC-01: ลงทะเบียนผลผลิตใหม่ (Farmer)

**Actor:** เกษตรกร (นาย A)

**Preconditions:**
- เกษตรกรมีบัญชีผู้ใช้และได้รับการยืนยันสถานะ "Farmer"
- มีผลผลิตที่พร้อมจะเข้าสู่ระบบ

**Main Flow:**
1. นาย A เปิดแอพ Food Passport
2. เลือกเมนู "ลงทะเบียนผลผลิตใหม่"
3. กรอกข้อมูล:
   - ชนิดผลผลิต: "ผักสลัดโรแมน"
   - ปริมาณ: 500 กก.
   - วันที่เก็บเกี่ยว: วันนี้
   - ชื่อฟาร์ม: "ฟาร์มหุบเขาเขียว"
   - พิกัดฟาร์ม: (auto-detect จาก GPS)
4. ถ่ายรูปผลผลิต (3-5 รูป)
5. เลือกใบรับรองที่มี: "Organic Thailand", "HACCP"
6. ระบบสร้าง Food Passport ID: `FP-2025-001234`
7. ระบบสร้าง QR Code
8. บันทึกข้อมูลบน Blockchain
9. ส่ง LINE notification: "ลงทะเบียนผลผลิตสำเร็จ! Passport ID: FP-2025-001234"

**Postconditions:**
- ผลผลิตถูกสร้างในระบบพร้อม Passport ID
- QR Code พร้อมใช้งาน
- ข้อมูลถูกบันทึกบน Blockchain

**Alternative Flows:**
- A1: ถ้าข้อมูลไม่ครบ → แจ้งเตือนและขอให้กรอกให้ครบ
- A2: ถ้า GPS ไม่ทำงาน → ให้เลือก location จากแผนที่

---

### UC-02: เพิ่มขั้นตอนการเดินทาง (Distributor/Retailer)

**Actor:** ผู้ขนส่ง/ผู้จัดจำหน่าย

**Preconditions:**
- มีผลผลิตที่อยู่ในระบบแล้ว
- User มีสิทธิ์ในการเพิ่มขั้นตอน

**Main Flow:**
1. Scan QR Code ของผลผลิต
2. ระบบแสดงข้อมูลผลผลิตปัจจุบัน
3. เลือก "เพิ่มขั้นตอนการเดินทาง"
4. เลือกประเภทขั้นตอน: "Transportation"
5. กรอกข้อมูล:
   - สถานที่ต้นทาง: (auto-detect จากขั้นตอนก่อนหน้า)
   - สถานที่ปลายทาง: "ศูนย์กระจายสินค้า กรุงเทพฯ"
   - ระยะทาง: 150 กม.
   - วิธีการขนส่ง: "รถบรรทุกดีเซล (3.5 ตัน)"
   - ใช้เชื้อเพลิง: 45 ลิตร
6. ระบบคำนวณ carbon emission: 12.5 kg CO2
7. ถ่ายรูปหลักฐานการรับสินค้า
8. ยืนยันและบันทึก
9. ระบบอัพเดทสถานะบน Blockchain
10. ส่ง notification ไปยังเกษตรกรและผู้เกี่ยวข้อง

**Postconditions:**
- ขั้นตอนใหม่ถูกเพิ่มใน journey
- Carbon footprint ถูกอัพเดท
- ผู้บริโภคเห็นข้อมูลอัพเดท real-time

---

### UC-03: ตรวจสอบคุณภาพ (Quality Inspector)

**Actor:** ผู้ตรวจสอบคุณภาพ (ดร. D)

**Preconditions:**
- ผู้ตรวจสอบมีใบอนุญาต/การรับรอง
- ผลผลิตพร้อมตรวจสอบ

**Main Flow:**
1. ดร. D login ในฐานะ Quality Inspector
2. เลือก "สร้างจุดตรวจสอบใหม่"
3. Scan QR Code ผลผลิต
4. เลือกประเภทการตรวจ: "Laboratory Test"
5. เลือก stage ที่ตรวจ: "Processing Center"
6. กรอกผลการทดสอบ:
   - Pesticide Residue: 0.001 mg/kg (ผ่าน ✓)
   - Heavy Metals: 0.5 ppm (ผ่าน ✓)
   - Bacterial Count: 100 CFU/g (ผ่าน ✓)
7. ระบบคำนวณคะแนนโดยรวม: 96/100
8. Upload รายงานการทดสอบ (PDF)
9. อัพโหลดรูปถ่ายตัวอย่าง
10. ระบบออกใบรับรอง (Certificate)
11. Digital signature ของผู้ตรวจ
12. บันทึกบน Blockchain และ IPFS
13. ส่ง notification ไปยังเกษตรกร

**Postconditions:**
- Quality checkpoint ถูกบันทึก
- ใบรับรองถูกออกและเชื่อมกับผลผลิต
- ข้อมูลแสดงใน product journey

**Alternative Flows:**
- A1: ถ้าผลการทดสอบไม่ผ่าน → ออกคำแนะนำแก้ไข, flag สินค้า
- A2: ถ้าต้องการทดสอบซ้ำ → สร้าง retest checkpoint

---

### UC-04: สแกนผลิตภัณฑ์ (Consumer)

**Actor:** ผู้บริโภค (คุณ C)

**Preconditions:**
- ผลิตภัณฑ์มี QR Code ติดอยู่
- ผู้บริโภคมีสมาร์ทโฟน

**Main Flow:**
1. คุณ C เปิดแอพหรือ camera สแกน QR Code
2. ระบบโหลดข้อมูล Food Passport
3. แสดงหน้า Product Story:
   ```
   🥬 ผักสลัดโรแมนอินทรีย์
   จาก: ฟาร์มหุบเขาเขียว, เชียงใหม่
   เก็บเกี่ยว: 10 พ.ย. 2025

   คุณภาพ: 96/100 ⭐⭐⭐⭐⭐
   Carbon Score: A+ 🌱
   ใบรับรอง: ✓ Organic ✓ HACCP

   [ดูเส้นทางเต็ม] [คุณภาพ] [Carbon]
   ```
4. คุณ C เลือก "ดูเส้นทางเต็ม"
5. แสดง Timeline:
   - 🌱 ฟาร์ม (10 พ.ย. 09:00)
   - 🚚 ขนส่ง (10 พ.ย. 14:00-18:00) 150 km
   - 🏭 โรงคัดบรรจุ (11 พ.ย. 08:00-16:00)
   - ✓ ตรวจสอบคุณภาพ (96/100)
   - 🚚 ขนส่ง (11 พ.ย. 17:00-19:00) 50 km
   - 🏪 Tesco Lotus (12 พ.ย. 06:00)
6. เลือกดู "Carbon Footprint"
   - แสดงกราฟแสดง emission แต่ละขั้นตอน
   - Total: 16.7 kg CO2 (ต่ำกว่าเกณฑ์ 45%)
7. คุณ C ให้คะแนน 5 ดาว และเขียนรีวิว
8. ระบบบันทึก consumer scan และ feedback

**Postconditions:**
- การสแกนถูกบันทึก (สำหรับ analytics)
- ผู้บริโภคมีความรู้เกี่ยวกับผลิตภัณฑ์
- Feedback ถูกส่งไปยังเกษตรกร

---

### UC-05: คำนวณและออก Carbon Credit (System + Verifier)

**Actor:** ระบบ (อัตโนมัติ) + Carbon Verifier

**Preconditions:**
- ผลิตภัณฑ์มีข้อมูล carbon footprint ครบถ้วน
- ผลิตภัณฑ์ผ่าน quality check แล้ว

**Main Flow:**
1. เมื่อผลิตภัณฑ์เข้าถึง "retail" stage และครบทุก checkpoint
2. ระบบรวบรวมข้อมูล carbon จากทุก stage
3. คำนวณ total emission: 16.7 kg CO2
4. ดึงข้อมูล baseline สำหรับ "ผักสลัดโรแมน": 30 kg CO2 (conventional farming)
5. คำนวณส่วนต่าง: 30 - 16.7 = 13.3 kg CO2 saved
6. สำหรับ 500 กก. ผลผลิต:
   - Total saving = 13.3 kg CO2/kg × 500 kg = 6,650 kg CO2 = 6.65 tons CO2
7. ระบบสร้าง Carbon Credit request
8. ส่งไปยัง Carbon Verifier ตรวจสอบ
9. Verifier ตรวจสอบข้อมูล:
   - ความถูกต้องของ emission factors
   - หลักฐานประกอบ (ใบเสร็จน้ำมัน, ใบรับรองไฟฟ้า)
   - ความสอดคล้องกับมาตรฐาน
10. Verifier อนุมัติ
11. ระบบออก Carbon Credit: 6.65 tons
12. มูลค่า (ราคาตลาด ฿250/ton): ฿1,662.50
13. บันทึกบน Blockchain
14. ส่ง notification ไปยังเกษตรกร:
    "🎉 คุณได้รับ Carbon Credit 6.65 tons มูลค่า ฿1,662!"

**Postconditions:**
- Carbon credit ถูกออกและเข้า wallet ของเกษตรกร
- สามารถนำไปซื้อขายหรือใช้ในระบบได้
- ข้อมูลโปร่งใสบน Blockchain

---

### UC-06: ซื้อขาย Carbon Credit (Trader)

**Actor:** นักธุรกิจ Carbon (คุณ E) และเกษตรกร (นาย A)

**Preconditions:**
- นาย A มี carbon credit ที่สามารถขายได้
- คุณ E มีบัญชีและ wallet พร้อมเงิน

**Main Flow:**
1. คุณ E เข้าหน้า "Carbon Credit Marketplace"
2. ดูรายการ carbon credit ที่วางขาย:
   ```
   🌱 6.65 tons CO2 - จาก ผักสลัดโรแมน
   ผู้ขาย: นาย A (ฟาร์มหุบเขาเขียว)
   Verified: ✓
   ราคา: ฿1,800 (฿270/ton)
   ```
3. เลือก "ซื้อ"
4. ระบบแสดงรายละเอียดและข้อมูล verification
5. ยืนยันการซื้อ
6. ชำระเงินผ่าน:
   - Crypto wallet
   - หรือ Payment gateway (PromptPay, Bank transfer)
7. ระบบหัก 5% ค่าคอมมิชชั่น: ฾90
8. โอนเงินให้นาย A: ฿1,710
9. Transfer carbon credit ownership จาก นาย A → คุณ E
10. บันทึก transaction บน Blockchain
11. ออกใบรับรอง (Certificate of Transfer)
12. ส่ง notification ไปยังทั้งสองฝ่าย

**Postconditions:**
- Carbon credit เปลี่ยนเจ้าของ
- นาย A ได้รับเงิน
- คุณ E ได้ carbon credit เข้า portfolio

---

### UC-07: ตรวจสอบใบรับรอง (Public Verification)

**Actor:** ผู้ตรวจสอบภายนอก (หน่วยงาน/ผู้สนใจ)

**Preconditions:**
- มีเลขที่ใบรับรอง (Certificate Number)

**Main Flow:**
1. เข้าเว็บไซต์ Food Passport
2. เลือก "ตรวจสอบใบรับรอง"
3. กรอกเลขที่ใบรับรอง: `CERT-2025-001234`
4. หรือ Scan QR Code บนใบรับรอง
5. ระบบค้นหาและแสดงข้อมูล:
   ```
   ใบรับรอง: CERT-2025-001234
   ชนิด: Organic Certification
   ผู้ออก: Thai Organic Center
   ออกให้: ผักสลัดโรแมน จากฟาร์มหุบเขาเขียว
   วันที่ออก: 11 พ.ย. 2025
   วันหมดอายุ: 11 พ.ย. 2026
   สถานะ: ✓ ใช้งานได้

   Blockchain Hash: 0x7a3f8b2c...
   [ตรวจสอบบน Blockchain] [ดาวน์โหลด PDF]
   ```
6. เลือก "ตรวจสอบบน Blockchain"
7. ระบบเปิด Block Explorer แสดง transaction
8. แสดง IPFS hash ของเอกสารต้นฉบับ

**Postconditions:**
- ผู้ตรวจสอบมั่นใจในความถูกต้องของใบรับรอง
- ระบบมีความโปร่งใส

---

### UC-08: รายงานปัญหาคุณภาพ (Consumer Complaint)

**Actor:** ผู้บริโภค (คุณ C)

**Preconditions:**
- ผู้บริโภคซื้อสินค้าและพบปัญหา
- มี Passport ID หรือ QR Code

**Main Flow:**
1. คุณ C สแกน QR Code ของผลิตภัณฑ์
2. เลือก "รายงานปัญหา"
3. เลือกประเภทปัญหา:
   - [ ] คุณภาพไม่ดี/เน่าเสีย
   - [ ] ไม่ตรงกับที่โฆษณา
   - [ ] พบสิ่งแปลกปลอม
   - [x] อื่นๆ
4. กรอกรายละเอียด: "พบใบเหลืองผิดปกติ ไม่สด"
5. ถ่ายรูปประกอบ (3 รูป)
6. อัพโหลดใบเสร็จรับเงิน (ถ้ามี)
7. ยืนยันและส่งรายงาน
8. ระบบสร้าง ticket: `#COMPLAINT-001234`
9. ส่ง notification ไปยัง:
   - เกษตรกร (นาย A)
   - ร้านค้าที่ขาย
   - Quality inspector ที่ตรวจ
10. ระบบ flag ผลิตภัณฑ์นี้ (ลด quality score ชั่วคราว)
11. ส่ง LINE message แจ้งคุณ C: "เราได้รับเรื่องร้องเรียนแล้ว จะตรวจสอบภายใน 48 ชม."

**Postconditions:**
- มีการบันทึก complaint
- ผู้เกี่ยวข้องได้รับแจ้ง
- เริ่มกระบวนการสืบสวน

**Alternative Flows:**
- A1: ถ้าเป็นปัญหาร้ายแรง → แจ้งหน่วยงานที่เกี่ยวข้องทันที
- A2: ถ้ามีรายงานซ้ำหลายครั้ง → ระงับสินค้าชั่วคราว

---

### UC-09: Dashboard Analytics (Farmer/Admin)

**Actor:** เกษตรกร (นาย A) หรือ Admin

**Preconditions:**
- มีข้อมูลผลผลิตในระบบ

**Main Flow:**
1. นาย A login เข้าระบบ
2. เข้าหน้า Dashboard
3. แสดงข้อมูลสรุป:
   ```
   📊 สรุปผลการดำเนินงาน

   เดือนนี้ (พฤศจิกายน 2025)
   ├─ ผลผลิต: 24 รายการ
   ├─ ปริมาณ: 12,500 กก.
   ├─ คุณภาพเฉลี่ย: 94/100
   ├─ Carbon Score เฉลี่ย: A
   ├─ Carbon Credits ที่ได้: 85.2 tons
   └─ รายได้: ฿456,800

   🏆 อันดับของคุณ
   Carbon Reduction: #3 ใน Thailand
   Quality Score: #7 ในภาคเหนือ

   📈 กราฟแนวโน้ม
   [กราฟ] คุณภาพผลผลิตล่าสุด 6 เดือน
   [กราฟ] Carbon savings เทียบกับ baseline
   [กราฟ] รายได้จากการขาย + carbon credits

   🌟 Top Performers
   1. ผักสลัดโรแมน - Quality 96, Carbon A+
   2. มะเขือเทศราชินี - Quality 95, Carbon A+
   3. แตงกวาญี่ปุ่น - Quality 93, Carbon A
   ```
4. เลือกดูรายละเอียดผลผลิตแต่ละชิ้น
5. ดูการสแกนของผู้บริโภค (จำนวน, เวลา, สถานที่)
6. ดู feedback จากผู้บริโภค

**Postconditions:**
- เกษตรกรเข้าใจผลการดำเนินงาน
- มีข้อมูลสำหรับการตัดสินใจ

---

### UC-10: Bulk Import Products (Large Farm)

**Actor:** ฟาร์มขนาดใหญ่/Admin

**Preconditions:**
- มีไฟล์ CSV/Excel ข้อมูลผลผลิต
- ข้อมูลมีรูปแบบตามที่กำหนด

**Main Flow:**
1. เข้าหน้า "นำเข้าผลผลิตจำนวนมาก"
2. ดาวน์โหลด Template CSV
3. กรอกข้อมูลใน Excel:
   ```
   product_type, variety, quantity, unit, harvest_date, batch_number, certifications
   vegetables, Romaine Lettuce, 500, kg, 2025-11-10, BATCH-001, "organic,haccp"
   vegetables, Cherry Tomatoes, 300, kg, 2025-11-10, BATCH-002, "organic,gmp"
   fruits, Dragon Fruit, 200, kg, 2025-11-09, BATCH-003, "gmp"
   ```
4. อัพโหลดไฟล์
5. ระบบ validate ข้อมูล
6. แสดง preview:
   - ✓ 3 รายการถูกต้อง
   - ⚠ 0 รายการมีปัญหา
7. ยืนยัน "Import"
8. ระบบสร้าง Food Passport พร้อมกัน 3 รายการ
9. สร้าง QR Code ทั้งหมด
10. บันทึกบน Blockchain (batch transaction)
11. ส่งอีเมลพร้อม PDF รวม QR Code ทั้งหมด

**Postconditions:**
- หลายผลผลิตถูกสร้างพร้อมกัน
- ประหยัดเวลา
- QR Code พร้อมพิมพ์ติดสินค้า

---

## 🔄 User Journey Maps

### Journey 1: เกษตรกรครั้งแรก (First-time Farmer)

```
1. [Discovery] ได้ยินเพื่อนพูดถึงระบบ Food Passport
   └─ Emotion: สนใจ 😃

2. [Registration] สมัครบัญชีผ่าน LINE
   └─ Emotion: ง่าย 😊

3. [Verification] ยืนยันตัวตนและยื่นเอกสารฟาร์ม
   └─ Emotion: กังวลนิดหน่อย 😐

4. [Approval] ได้รับการอนุมัติภายใน 24 ชม.
   └─ Emotion: ดีใจ 😃

5. [First Product] ลงทะเบียนผลผลิตชิ้นแรก
   └─ Emotion: ตื่นเต้น 😊
   └─ Pain point: ไม่แน่ใจว่ากรอกถูกหรือไม่
   └─ Solution: มี tooltip และ tutorial video

6. [QR Code] ได้ QR Code พร้อมพิมพ์
   └─ Emotion: ประทับใจ 😊

7. [Journey Updates] ผู้ขนส่งเพิ่มขั้นตอนการเดินทาง
   └─ Emotion: อยากรู้ 🤔
   └─ Receive LINE notification → ตรวจสอบ

8. [Quality Check] ผ่านการตรวจสอบคุณภาพ
   └─ Emotion: ภูมิใจ 😊

9. [Consumer Scans] มีผู้บริโภคสแกน 50 ครั้ง
   └─ Emotion: ดีใจมาก 🤩

10. [Carbon Credit] ได้รับ carbon credit ครั้งแรก
    └─ Emotion: ประหลาดใจและดีใจ 🤩
    └─ "ไม่คิดว่าจะได้เงินจากการลด carbon ด้วย!"

11. [Recurring Usage] ลงทะเบียนผลผลิตต่อเนื่อง
    └─ Emotion: เคยชิน พอใจ 😊
```

---

### Journey 2: ผู้บริโภค (Consumer)

```
1. [Shopping] ซื้อของที่ซูเปอร์มาร์เก็ต
   └─ เห็น QR Code บนผักสลัด
   └─ Emotion: สงสัย 🤔

2. [Scan] สแกน QR Code ด้วยมือถือ
   └─ Emotion: อยากรู้ 😃

3. [View Info] เห็นข้อมูลฟาร์ม, เส้นทาง, คุณภาพ
   └─ Emotion: ประทับใจ 😊
   └─ "อ้าว รู้เรื่องแบบนี้ด้วย!"

4. [Trust Building] อ่านว่ามีใบรับรอง + ผ่านตรวจสอบ
   └─ Emotion: มั่นใจ 😊

5. [Carbon Score] เห็น Carbon Score A+
   └─ Emotion: ดีใจ 🌱
   └─ "ช่วยรักษ์โลกด้วย!"

6. [Purchase Decision] ตัดสินใจซื้อ (แม้ราคาแพงกว่านิดหน่อย)
   └─ Emotion: พอใจ 😊

7. [Feedback] ให้ 5 ดาวและเขียนรีวิว
   └─ Emotion: ชอบระบบ 🤩

8. [Repeat] ครั้งต่อไปหาซื้อสินค้าที่มี Food Passport
   └─ Emotion: ภักดี 😊
```

---

## 💡 Edge Cases & Error Handling

### Edge Case 1: ไฟดับระหว่างบันทึกข้อมูล
- **Solution:** ระบบ auto-save draft ทุก 30 วินาที
- สามารถกลับมาทำต่อได้ (Resume functionality)

### Edge Case 2: GPS ไม่ทำงาน/ไม่มีสัญญาณ
- **Solution:** ให้เลือก location จากแผนที่
- หรือพิมพ์ address แล้วระบบแปลงเป็นพิกัด

### Edge Case 3: Blockchain transaction ล้มเหลว
- **Solution:** ข้อมูลถูกเก็บใน database ก่อน
- ระบบจะ retry บันทึก blockchain ทุก 5 นาที
- แจ้งเตือน admin ถ้า retry ไม่สำเร็จหลัง 1 ชม.

### Edge Case 4: QR Code เสียหาย/อ่านไม่ได้
- **Solution:** ให้พิมพ์ Passport ID แทน
- หรือค้นหาจากรายการสินค้า

### Edge Case 5: ใบรับรองหมดอายุ
- **Solution:** ระบบแจ้งเตือนล่วงหน้า 30 วัน
- หลังหมดอายุ: Badge แสดง "หมดอายุ" แต่ยังดูข้อมูลเก่าได้

### Edge Case 6: ข้อมูล Carbon emission ผิดพลาด/โกง
- **Solution:** มี Carbon Verifier ตรวจสอบ
- Algorithm ตรวจจับค่าผิดปกติ (anomaly detection)
- Flag และส่งไป manual review

### Edge Case 7: สินค้าถูกปลอมแปลง (สแกน QR แล้วไปติดสินค้าอื่น)
- **Solution:** QR Code มี security features:
  - Hologram/Tamper-evident sticker
  - Time-limited QR Code
  - Geolocation validation
- Consumer สามารถรายงานได้

### Edge Case 8: เกษตรกรลงทะเบียนผลผลิตที่ไม่มีอยู่จริง
- **Solution:** ระบบตรวจสอบ:
  - ปริมาณต้องสมเหตุสมผลตามขนาดฟาร์ม
  - ต้องมี Inspector visit อย่างน้อย 1 ครั้ง/เดือน
  - Flagging system สำหรับพฤติกรรมผิดปกติ

---

## 🎬 Demo Scenarios

### Scenario 1: "From Farm to Table in 2 Days"

**Story:** นาย A ปลูกผักสลัดออร์แกนิก คุณ C ซื้อจากห้างในกรุงเทพฯ

**Steps:**
1. **Day 1, 09:00** - นาย A เก็บเกี่ยวผักสลัด 500 กก.
2. **Day 1, 09:30** - ลงทะเบียนใน Food Passport ผ่านมือถือ
3. **Day 1, 14:00** - รถขนส่งมารับ → บันทึกขั้นตอน "Transportation"
4. **Day 1, 18:00** - ถึงโรงคัดบรรจุกรุงเทพฯ
5. **Day 2, 08:00** - ผ่านการตรวจสอบคุณภาพ → ได้ 96/100
6. **Day 2, 10:00** - บรรจุและส่งไปห้าง
7. **Day 2, 14:00** - วางขายที่ห้าง Tesco Lotus
8. **Day 2, 18:00** - คุณ C สแกน QR → เห็นเส้นทางทั้งหมดภายใน 2 วัน
9. **Day 2, 18:05** - คุณ C ตัดสินใจซื้อ → ให้ 5 ดาว
10. **Day 3, 10:00** - นาย A ได้รับ carbon credit 6.65 tons

**Outcome:**
- 💚 ความโปร่งใส: คุณ C มั่นใจในที่มาสินค้า
- 💰 รายได้เพิ่ม: นาย A ขายได้ราคาพรีเมี่ยม + carbon credit
- 🌍 ลด Carbon: ระบบชัดเจนว่าลดได้เท่าไหร่

---

## 📊 Success Metrics per Use Case

| Use Case | Primary Metric | Target |
|----------|---------------|--------|
| UC-01: ลงทะเบียนผลผลิต | Time to complete | < 5 นาที |
| UC-02: เพิ่มขั้นตอน | Data completeness | > 95% |
| UC-03: ตรวจสอบคุณภาพ | Pass rate | > 90% |
| UC-04: สแกนผลิตภัณฑ์ | Engagement rate | > 70% view full journey |
| UC-05: ออก Carbon Credit | Approval rate | > 85% |
| UC-06: ซื้อขาย Carbon | Transaction completion | > 95% |
| UC-07: ตรวจสอบใบรับรอง | Verification time | < 10 วินาที |
| UC-08: รายงานปัญหา | Response time | < 48 ชม. |
| UC-09: Dashboard | Daily active users | > 60% farmers |
| UC-10: Bulk Import | Success rate | > 98% |

---

## 🎯 Conclusion

Use cases และ user stories เหล่านี้ครอบคลุม:

✅ **ทุก user persona** - Farmer, Inspector, Consumer, Trader, Verifier
✅ **ทุก flow หลัก** - Registration, Tracking, Quality, Carbon, Trading
✅ **Edge cases** - Error handling, fraud prevention
✅ **Real-world scenarios** - จากฟาร์มถึงผู้บริโภคจริง

เอกสารนี้สามารถใช้เป็น:
1. **Requirements** สำหรับ developers
2. **Test cases** สำหรับ QA team
3. **Training materials** สำหรับ users
4. **Demo scripts** สำหรับ presentations

---

*Document version: 1.0 | Last updated: 2025-11-13*

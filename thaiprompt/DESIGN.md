# TP UltraApp — ระบบดีไซน์ "รอยัล น้ำเงินกรมท่า-ทอง"

> เจ้าของเลือก **แบบ A (รอยัล) + โหมดมืดแบบ B (มิดไนท์โกลด์)** เมื่อ 2026-09-26
> ต่อยอดจากไอคอนแอป TP UltraApp (น้ำเงินกรมท่า + ทองลายกนก) — เป้าหมาย: แอปดู **พรีเมียม ไม่เหมือนเทมเพลต**

## 1. บุคลิก

- **หัวหน้าจอน้ำเงินกรมท่า** ไล่เฉด + ลายกนกทองจางมุมขวาบน (`RoyalHeader`) — ทุกหน้าที่มีชื่อหน้า
- **เนื้อหาบน "แผ่นงาช้าง"** `#F4F0E7` มุมบนโค้ง 26 ซ้อนขึ้นบนหัว · การ์ดขาวลอยด้วยเงานุ่มสองชั้น
- **ทองใช้น้อยแต่ชัด**: ปุ่มหลัก ตัวเลขเงิน ไอคอนเน้น ลิงก์ — ส่วนใหญ่เป็นสีกลาง (ขาว/งาช้าง/น้ำเงิน)
- **ไอคอน 3D ประจำแบรนด์** (`BrandArt`) เป็นพระเอกของหน้าแรก/หน้าว่าง · ไอคอนเล็กทั้งหมดเป็น **เส้น Phosphor** (`Icon`)
- **ฟอนต์**: Anuphan (ข้อความทั้งหมด เลือกน้ำหนักอัตโนมัติ) + Noto Serif Thai (ชื่อหน้า/ชื่อร้าน/คำทักทาย)
- **โหมดมืด** = มิดไนท์ `#0A0D14` การ์ดกระจกทึบ `#141925` ขอบบาง ทองเรือง `#F0C96A` — ทุกหน้าต้องรองรับ

## 2. กติกาเหล็ก (ห้ามพลาด)

1. **ห้ามอีโมจิเป็นไอคอน/ของตกแต่ง** ทุกที่ที่ผู้ใช้เห็น (ปุ่ม ป้าย หัวข้อ Alert ข้อความสถานะ)
   - ไอคอน → `<Icon name="moped" />` หรือส่งชื่อให้ prop `icon="moped"`
   - ข้อความที่มีอีโมจิประดับ เช่น `'🎉 สั่งสำเร็จ'` → ตัดอีโมจิออก (`'สั่งสำเร็จ'`) หรือวาง `<Icon>` ข้างข้อความ
   - prop `icon` ของ Button3D/Chip/Pill/SectionHeader/StatTile/EmptyState/ConsentSheet ที่ยังส่งอีโมจิเดิม จะถูกแปลงอัตโนมัติ (`iconFromLegacy`) แต่ **ควรเปลี่ยนเป็นชื่อไอคอนตรงๆ** เมื่อแก้ไฟล์นั้น
   - ข้อมูลจาก server (เช่น `category.icon` เป็นอีโมจิ) → ไม่แสดงอีโมจิ ใช้ไอคอน/รูปแทน
2. **สีจากธีมเท่านั้น** `const { colors, gradients, isDark } = useTheme()` — ห้าม hex/rgba ในหน้าจอ (ยกเว้นเงา/ม่านบนรูปภาพที่ต้องมืดเสมอ)
3. **Text/TextInput import จาก `@/components/ui/Text`** (หรือ `@/components/ui`) — ห้าม import Text จาก `react-native`
4. **แก้หน้าตาเท่านั้น** — ห้ามเปลี่ยน logic/API/การนำทาง/state/การตรวจสอบข้อมูล · คง `accessibilityLabel` ทั้งหมด
5. คอมเมนต์ในโค้ดเป็นภาษาไทย · ข้อความ UI ภาษาไทย

## 3. Tokens (`@/theme`)

| กลุ่ม | ใช้ |
|---|---|
| `colors.background` | พื้นหน้าจอ (งาช้าง / มิดไนท์) |
| `colors.card` | การ์ด (ขาว / #141925) · `colors.surface` พื้นรอง · `colors.inset` ช่องกรอก |
| `colors.textStrong / text / textMuted / textFaint` | ตัวอักษร 4 ระดับ |
| `colors.navy / navyDeep / navySoft` | น้ำเงินกรมท่าสำหรับ**ไอคอน/ตัวอักษร/เส้น** (โหมดมืดเป็นฟ้าอ่อนให้อ่านออก) · navySoft = พื้นไอคอนในวงกลม |
| `colors.navyFill` | **พื้น**น้ำเงินสีเดียวใต้ตัวอักษรขาว/ทอง (ฟองแชทของฉัน toast ป้ายเลขขั้น) — เข้มทั้งสองโหมด · ห้ามใช้ `colors.navy` เป็นพื้น |
| `colors.gold / goldDeep / goldLight / goldSoft` | gold=เส้น/ไอคอนเน้น · goldDeep=ตัวอักษรทองบนพื้นสว่าง · goldLight=ทองบนพื้นน้ำเงิน · goldSoft=พื้นป้าย |
| `colors.onHeader / onHeaderMuted / headerGlass / headerGlassBorder` | ของที่วางบนหัวน้ำเงิน |
| `colors.success/danger/info/warning` + `*Soft` | สถานะ |
| `gradients.primary` | ปุ่มทอง · `gradients.navy` ปุ่ม/เม็ดน้ำเงิน · `gradients.hero` หัวหน้าจอ · `gradients.gold` ทองฟอยล์ · `gradients.glass` การ์ดกระจกบนหัว |
| `typography.serifLg / serif / serifSm` | Noto Serif Thai (ชื่อหน้า ชื่อร้าน คำทักทาย) |
| `typography.h1 h2 h3 body bodyStrong bodySm caption micro overline` | Anuphan |
| `typography.money / moneyLg` | ตัวเลขเงิน (tabular) |
| `spacing.screen = 16` · ระยะระหว่าง section 24 · `radii.xl = 22` (การ์ด) |
| `shadowStyle('sm'|'md'|'lg', colors.shadowDark)` · `glowStyle(color)` ปุ่มเรืองแสง |

## 4. Components (`@/components/ui`)

| Component | ใช้เมื่อ |
|---|---|
| `Screen` | หน้าจอทั่วไป — มี `title` = หัวน้ำเงินลายกนก + ปุ่มย้อนกลับกระจก + ชื่อหน้า serif อัตโนมัติ · `right` วางปุ่มขวา (ปุ่ม ghost/secondary/Pill/IconButton จะกลายเป็นแบบกระจกเอง) |
| `RoyalHeader` + `GlassIconButton` | หน้าที่มีหัวใหญ่ของตัวเอง (หน้าแรก ตลาดสด ไรเดอร์ กระเป๋าเงิน) |
| `Icon` | ไอคอนเส้น — ชื่อดูใน `components/ui/iconPaths.ts` (164 ตัว) · `weight="fill"` สถานะเลือก · `"bold"` บางตัว |
| `BrandArt` | ภาพ 3D: basket(ตลาดสด) cart(รถเข็น) bag(ช้อป) scooter(ไรเดอร์) store(ร้าน) wallet gift(ชวนเพื่อน) tarot |
| `Button3D` | variant `primary`(ทอง—การกระทำหลัก) `navy`(น้ำเงินตัวทอง) `secondary`(ขาว) `success` `danger` `ghost` · `icon`/`iconRight` = ชื่อไอคอน |
| `Card3D` | การ์ดขาวเงานุ่ม · `variant="inset"` กล่องสรุป · `gradientBorder` เฉพาะการ์ดสำคัญมาก |
| `Chip` / `Pill` | ตัวกรอง (เลือก = เม็ดน้ำเงินตัวทอง) / ป้ายสถานะ `tone` |
| `SectionHeader` | หัวข้อส่วน + "ดูทั้งหมด ›" |
| `EmptyState` | หน้าว่าง — ใส่ `art="scooter"` ฯลฯ ให้ดูมีชีวิต |
| `StatTile` | ตัวเลขสรุป · `IconButton` ปุ่มไอคอนสี่เหลี่ยม (มี badge) |
| `PriceText` | แสดงเงินบาท |

## 5. แพทเทิร์นหน้าตา

- **แถวรายการในการ์ด**: การ์ดขาว 1 ใบ → แถว padding 14 คั่นด้วย `colors.divider` 1px · ไอคอนนำหน้าในสี่เหลี่ยมมน 44×44 radius 15 พื้น `navySoft` ไอคอน `navy` (หรือ `goldSoft`+`goldDeep` สำหรับเรื่องเงิน, `successSoft`+`success` สำหรับเงินเข้า)
- **หัวข้อ section**: `SectionHeader` (Anuphan bold 18.5) ห่างกัน 24
- **ปุ่มหลักท้ายหน้า** (ยืนยัน/ชำระเงิน/ใส่ตะกร้า): `Button3D primary size="lg"` เต็มความกว้าง ในแถบขาวลอยท้ายจอ (พื้น `colors.card` + เส้นบน `colors.divider`)
- **แถบตะกร้าลอย**: การ์ดน้ำเงิน `gradients.navy` มุม 20 + ปุ่มทองด้านขวา
- **ตัวเลขเงินบนหัวน้ำเงิน**: สี `colors.goldLight` ขนาด `typography.moneyLg`
- **สถานะเปิดอยู่/ออนไลน์**: จุดเขียว 8px + ข้อความ `colors.success`
- **รูปอาหาร/สินค้า**: มุม 18–20 · การ์ดรูปใหญ่ใช้ม่าน `gradients.bannerScrim` ใต้ตัวอักษร
- **ช่องกรอก**: พื้น `colors.inset` มุม 14 สูง ≥ 48 ขอบ `colors.border` · focus = ขอบ `colors.gold`

## 6. ทรัพยากร

- ฟอนต์: `assets/fonts/` (OFL) · โหลดใน `app/_layout.tsx`
- ภาพแบรนด์: `assets/images/brand/` — `icons/*.webp` (3D), `kanok-gold.webp` (ลายกนก), `night-market.webp`, `map-light.webp`
- ไอคอน: สร้าง `components/ui/iconPaths.ts` ใหม่ด้วย `scripts/gen_icon_paths.py` (เพิ่มชื่อในรายการ แล้วรันกับ `@phosphor-icons/core` ที่ `npm pack` มา)

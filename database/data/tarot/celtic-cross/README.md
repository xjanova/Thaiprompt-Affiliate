# Tarot Celtic Cross Position Meanings - Data Files

> โฟลเดอร์เก็บไฟล์ JSON คำทำนายไพ่ยิปซีในรูปแบบ Celtic Cross แบบละเอียดที่สุด

## โครงสร้างข้อมูล

แต่ละไฟล์ JSON เก็บคำทำนายของไพ่ 1 ใบ ครอบคลุม:
- **10 ตำแหน่ง** ใน Celtic Cross
- **2 ทิศทาง** (upright + reversed)
- **5 หมวดหมู่** (general, love-relationships, career-finance, personal-growth, health-wellness)

รวม: 10 × 2 × 5 = **100 entries ต่อไพ่ 1 ใบ**

## รายชื่อไฟล์ (78 ไฟล์)

### Major Arcana (22 ใบ)
- `00-the-fool.json` - The Fool / คนบ้า
- `01-the-magician.json` - The Magician / นักมายากล
- `02-the-high-priestess.json` - The High Priestess / มหาปุโรหิตหญิง
- `03-the-empress.json` - The Empress / จักรพรรดินี
- `04-the-emperor.json` - The Emperor / จักรพรรดิ
- `05-the-hierophant.json` - The Hierophant / มหาปุโรหิต
- `06-the-lovers.json` - The Lovers / คู่รัก
- `07-the-chariot.json` - The Chariot / รถม้า
- `08-strength.json` - Strength / พลัง
- `09-the-hermit.json` - The Hermit / นักบวช
- `10-wheel-of-fortune.json` - Wheel of Fortune / กงล้อแห่งโชค
- `11-justice.json` - Justice / ความยุติธรรม
- `12-the-hanged-man.json` - The Hanged Man / ชายผู้ถูกแขวนคอ
- `13-death.json` - Death / ความตาย
- `14-temperance.json` - Temperance / ความพอประมาณ
- `15-the-devil.json` - The Devil / ปีศาจ
- `16-the-tower.json` - The Tower / หอคอย
- `17-the-star.json` - The Star / ดวงดาว
- `18-the-moon.json` - The Moon / ดวงจันทร์
- `19-the-sun.json` - The Sun / ดวงอาทิตย์
- `20-judgement.json` - Judgement / การพิพากษา
- `21-the-world.json` - The World / โลก

### Minor Arcana - Wands ไม้เท้า (14 ใบ)
- `wands-ace.json` ถึง `wands-king.json`

### Minor Arcana - Cups ถ้วย (14 ใบ)
- `cups-ace.json` ถึง `cups-king.json`

### Minor Arcana - Swords ดาบ (14 ใบ)
- `swords-ace.json` ถึง `swords-king.json`

### Minor Arcana - Pentacles เหรียญ (14 ใบ)
- `pentacles-ace.json` ถึง `pentacles-king.json`

## รูปแบบไฟล์ JSON

```json
{
  "card_identifier": {
    "name_en": "The Fool",
    "name_th": "คนบ้า",
    "number": 0,
    "type": "major_arcana",
    "suit": null
  },
  "positions": {
    "1": {
      "position_slug": "present",
      "position_name_th": "ปัจจุบัน",
      "upright": {
        "general": {
          "core_meaning_th": "...",
          "detailed_interpretation_th": "...",
          "adjacent_influences": {
            "major_arcana": "...",
            "wands": "...",
            "cups": "...",
            "swords": "...",
            "pentacles": "...",
            "court_cards": "..."
          },
          "secret_keys_th": "...",
          "advice_th": "...",
          "keywords_th": ["..."],
          "intensity_level": 3,
          "emotional_tone": "positive"
        },
        "love-relationships": { ... },
        "career-finance": { ... },
        "personal-growth": { ... },
        "health-wellness": { ... }
      },
      "reversed": {
        "general": { ... },
        ...
      }
    },
    "2": { ... },
    ...
    "10": { ... }
  }
}
```

## 10 ตำแหน่ง Celtic Cross

| # | Slug | ชื่อไทย | ความหมาย |
|---|------|---------|----------|
| 1 | present | ปัจจุบัน | สถานการณ์ปัจจุบัน |
| 2 | challenge | อุปสรรค | สิ่งที่ขัดขวาง (ตีความตั้งตรงเสมอ) |
| 3 | past | อดีต | รากฐานในอดีต |
| 4 | future | อนาคต | สิ่งที่จะเกิดในระยะใกล้ |
| 5 | above | เป้าหมาย | สิ่งที่มุ่งหวัง/จิตสำนึก |
| 6 | below | รากฐาน | จิตใต้สำนึก/พลังภายใน |
| 7 | advice | คำแนะนำ | ตัวคุณ - ทัศนคติ |
| 8 | external | ภายนอก | อิทธิพลคนรอบข้าง |
| 9 | hopes | ความหวัง | ความหวังและความกลัว |
| 10 | outcome | ผลลัพธ์ | ผลลัพธ์สุดท้าย |

## วิธีโหลดข้อมูล

```bash
# Run migration first
php artisan migrate

# Run seeder to load all JSON files into database
php artisan db:seed --class=TarotCelticPositionMeaningsSeeder
```

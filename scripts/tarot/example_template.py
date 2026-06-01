#!/usr/bin/env python3
"""ตัวอย่าง template สำหรับสร้างไพ่ใบหนึ่ง (ใช้รูปแบบกะทัดรัด)

วิธีใช้:
1. คัดลอกไฟล์นี้เป็น gen_<suit>_<rank>.py
2. แก้ identifier (name_en, name_th, number, suit)
3. แก้ themes data ทั้ง 10 positions (present, challenge, past, future, above, below, advice, external, hopes-fears, outcome)
4. แต่ละ position มี upright (u) และ reversed (r) ครบ 5 categories: general, love-relationships, career-finance, personal-growth, health-wellness
5. หมายเหตุ: position challenge (2) — reversed = upright อัตโนมัติ
6. รัน: python3 scripts/tarot/gen_<suit>_<rank>.py
7. Verify: php -r '$d=json_decode(file_get_contents("..."),true); ... echo count entries;'
8. Commit + push

โครงสร้าง theme tuple:
    t(core_meaning, detailed_interpretation, advice, keywords[5], intensity, emotional_tone)
"""
import sys
sys.path.insert(0, "scripts/tarot")
from gen_card2 import write_compact


def t(c, d, a, k, i=3, tone=""):
    return (c, d, a, k, i, tone)


def mt(data):
    """แปลง flat data เป็น themes dict ที่ build() เข้าใจ"""
    th = {}
    for ps, dirs in data.items():
        th[ps] = {"u": {}, "r": {}}
        for direction, cats in dirs.items():
            for cat, theme in cats.items():
                th[ps][direction][cat] = theme
    return th


# ============================================================
# ตัวอย่าง: Eight of Swords / แปดดาบ
# Themes: imprisonment, restriction, victim mentality, self-limiting beliefs
# Reversed: freedom, self-acceptance, new perspective, taking control
# ============================================================

data = {
    "present": {
        "u": {
            "general": t(
                "คุณรู้สึกติดกับและมองไม่เห็นทางออก",
                "บ่งบอกถึงความรู้สึกติดกับ ถูกจำกัด มองไม่เห็นทางออก แต่จริงๆ มีทางเสมอ",
                "เปิดตาดูทางเลือก",
                ["ติดกับ", "ถูกจำกัด", "ไม่เห็นทาง", "ทางมี", "เปิดตา"],
                3,
                "อึดอัด"
            ),
            "love-relationships": t(
                "ความรักรู้สึกติดและถูกจำกัด",
                "ในรัก คุณรู้สึกติดในความสัมพันธ์ ถูกจำกัด ไม่กล้าเปลี่ยน",
                "พูดความรู้สึก",
                ["ติด", "ถูกจำกัด", "ไม่กล้า", "พูด", "ในรัก"],
                3,
                "อึดอัดในรัก"
            ),
            "career-finance": t(
                "งานติดและไม่ก้าว",
                "ในงาน คุณรู้สึกติดในงาน ไม่ก้าวหน้า ไม่กล้าเปลี่ยน",
                "หาทางเลือก",
                ["ติดงาน", "ไม่ก้าว", "ไม่กล้า", "ทางเลือก", "ในงาน"],
                3,
                "ติดในงาน"
            ),
            "personal-growth": t(
                "คุณติดในความเชื่อจำกัดตัวเอง",
                "ในการเติบโต คุณติดในความเชื่อที่จำกัดตัวเอง ทำให้ไม่เติบโต",
                "ท้าทายความเชื่อ",
                ["ติดเชื่อ", "จำกัด", "ตัวเอง", "ท้าทาย", "ในใจ"],
                4,
                "ติดในใจ"
            ),
            "health-wellness": t(
                "ความเครียดทำให้ใจอึดอัด",
                "ในสุขภาพ ความเครียดทำให้ใจอึดอัด รู้สึกถูกจำกัด",
                "ผ่อนคลาย",
                ["เครียด", "อึดอัด", "ผ่อน", "คลาย", "ในกาย"],
                3,
                "ตึงในกาย"
            )
        },
        "r": {
            "general": t(
                "คุณเริ่มเห็นทางออกและเป็นอิสระ",
                "บ่งบอกถึงการเริ่มเห็นทางออก เป็นอิสระจากกรอบ",
                "ก้าวออก",
                ["เห็นทาง", "อิสระ", "ก้าวออก", "กรอบ", "ก้าว"],
                4,
                "อิสระ"
            ),
            "love-relationships": t(
                "ความรักเริ่มมีอิสระ",
                "ในรัก คุณเริ่มมีอิสระและกล้าเป็นตัวเอง",
                "คง",
                ["อิสระ", "เป็นตัวเอง", "คง", "ในรัก", "ก้าว"],
                4,
                "อิสระในรัก"
            ),
            "career-finance": t(
                "งานคืนอิสระและก้าวต่อ",
                "ในงาน คุณคืนอิสระและก้าวต่อ ไม่ติดกรอบเก่า",
                "ลงมือ",
                ["อิสระ", "ก้าวต่อ", "ไม่ติด", "ลงมือ", "ในงาน"],
                4,
                "พร้อมในงาน"
            ),
            "personal-growth": t(
                "คุณปลดความเชื่อจำกัด",
                "ในการเติบโต คุณปลดความเชื่อที่จำกัดตัวเองและเติบโต",
                "ส่งต่อ",
                ["ปลดเชื่อ", "จำกัด", "เติบโต", "ส่งต่อ", "ในใจ"],
                5,
                "อิสระลึก"
            ),
            "health-wellness": t(
                "สุขภาพใจดีและเป็นอิสระ",
                "ในสุขภาพ สุขภาพใจดีและเป็นอิสระจากความเครียด",
                "คง",
                ["สุขใจดี", "อิสระ", "ไม่เครียด", "คง", "ในกาย"],
                4,
                "ฟื้นในกาย"
            )
        }
    },
    # TODO: เพิ่ม "challenge", "past", "future", "above", "below", "advice", "external", "hopes-fears", "outcome" ทั้งหมด
    # (ใช้รูปแบบเดียวกัน แต่ละตำแหน่งต้องมี 5 categories ใน upright และ reversed)
}


if __name__ == "__main__":
    positions, total = write_compact(
        "database/data/tarot/celtic-cross/swords-eight.json",
        {
            "name_en": "Eight of Swords",
            "name_th": "แปดดาบ",
            "number": 8,
            "type": "minor_arcana",
            "suit": "swords"
        },
        mt(data)
    )
    print(f"Positions: {positions}, Entries: {total}")
    # ควรได้ Positions: 10, Entries: 100

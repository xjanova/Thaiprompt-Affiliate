#!/usr/bin/env python3
"""Compact card generator - takes ultra-compact data and produces full Celtic Cross JSON.

ใช้สำหรับสร้างไพ่ทาโรต์ "แม่หมอจันทรา" แบบ 10 positions x 2 directions x 5 categories = 100 entries

วิธีใช้:
    from gen_card2 import write_compact

    def t(c, d, a, k, i=3, tone=""):
        return (c, d, a, k, i, tone)

    data = {
        "present": {
            "u": { "general": t("...", "...", "...", [...], 3, "..."), ... },
            "r": { "general": t(...), ... }
        },
        ...
    }

    # แปลง flat data เป็นโครงสร้าง themes
    def mt(d):
        th = {}
        for ps, dirs in d.items():
            th[ps] = {"u": {}, "r": {}}
            for direction, cats in dirs.items():
                for cat, theme in cats.items():
                    th[ps][direction][cat] = theme
        return th

    write_compact("database/data/tarot/celtic-cross/swords-eight.json",
                  {"name_en": "Eight of Swords", "name_th": "แปดดาบ",
                   "number": 8, "type": "minor_arcana", "suit": "swords"},
                  mt(data))

โครงสร้าง theme tuple:
    (core_meaning, detailed_interpretation, advice, keywords[5], intensity, emotional_tone)

หมายเหตุ position 2 (challenge): reversed = upright อัตโนมัติ
"""
import json
import sys

POSITIONS = [
    ("present", "ปัจจุบัน", "ในตำแหน่งปัจจุบัน"),
    ("challenge", "อุปสรรค", "ในตำแหน่งอุปสรรค"),
    ("past", "อดีต", "ในตำแหน่งอดีต"),
    ("future", "อนาคต", "ในตำแหน่งอนาคต"),
    ("above", "จิตสำนึก", "ในตำแหน่งจิตสำนึก"),
    ("below", "จิตใต้สำนึก", "ในตำแหน่งจิตใต้สำนึก"),
    ("advice", "คำแนะนำ", "ในตำแหน่งคำแนะนำ"),
    ("external", "อิทธิพลภายนอก", "ในตำแหน่งอิทธิพลภายนอก"),
    ("hopes-fears", "ความหวังและความกลัว", "ในตำแหน่งความหวังและความกลัว"),
    ("outcome", "ผลลัพธ์สุดท้าย", "ในตำแหน่งผลลัพธ์สุดท้าย"),
]
CATS = [
    ("general", "ในเรื่องทั่วไป"),
    ("love-relationships", "ในเรื่องความรัก"),
    ("career-finance", "ในด้านการงานและการเงิน"),
    ("personal-growth", "ในการพัฒนาตนเอง"),
    ("health-wellness", "ในด้านสุขภาพ"),
]


def make_entry(card_th, pos_slug, direction, theme, primary_kw):
    """สร้าง entry เดียวจาก theme tuple: (core, detail, advice, keywords, intensity, tone)"""
    return {
        "core_meaning_th": theme[0],
        "detailed_interpretation_th": theme[1],
        "adjacent_influences": {
            "major_arcana": f"ไพ่ชุดใหญ่ข้างเคียงบ่งบอกถึงพลังของชะตาที่เชื่อมกับ{primary_kw}",
            "wands": f"ไพ่ไม้เท้าข้างเคียงเสริมไฟแห่ง{primary_kw}",
            "cups": f"ไพ่ถ้วยข้างเคียงบ่งบอกถึงมิติอารมณ์ของ{primary_kw}",
            "swords": f"ไพ่ดาบข้างเคียงชี้ถึงมิติความคิดใน{primary_kw}",
            "pentacles": f"ไพ่เหรียญข้างเคียงบ่งบอกถึงมิติทางวัตถุของ{primary_kw}",
            "court_cards": f"ไพ่บุคคลข้างเคียงบ่งบอกถึงบุคคลที่เกี่ยวข้องกับ{primary_kw}",
        },
        "secret_keys_th": f"กุญแจลับคือการเข้าใจว่า{primary_kw}คือพลังที่ต้องใช้อย่างฉลาด",
        "advice_th": theme[2],
        "keywords_th": theme[3],
        "intensity_level": theme[4] if len(theme) > 4 else 3,
        "emotional_tone": theme[5] if len(theme) > 5 else primary_kw,
    }


def build(card_th, themes):
    """สร้างไพ่ครบใบ themes structure: themes[pos_slug][direction][cat_slug] = theme tuple
    direction = 'u' (upright) หรือ 'r' (reversed)
    position 2 (challenge): reversed = upright อัตโนมัติ"""
    card = {"card_identifier": None, "positions": {}}
    for idx, (slug, name_th, phrase) in enumerate(POSITIONS, 1):
        is_challenge = (slug == "challenge")
        pos = {
            "position_slug": slug,
            "position_name_th": name_th,
            "upright": {},
            "reversed": {}
        }
        for cat_slug, cat_prefix in CATS:
            u_theme = themes.get(slug, {}).get("u", {}).get(cat_slug)
            r_theme = themes.get(slug, {}).get("r", {}).get(cat_slug) if not is_challenge else u_theme
            if u_theme is None:
                u_theme = ("พลังของไพ่นี้ในตำแหน่งนี้", "พลังของไพ่นี้ในตำแหน่งนี้และหมวดนี้",
                           "ใช้พลังของไพ่อย่างฉลาด", ["พลัง", "ไพ่"], 3, "พลังของไพ่")
            if r_theme is None:
                r_theme = u_theme

            u_kw = u_theme[3][0] if u_theme[3] else "พลังของไพ่"
            r_kw = r_theme[3][0] if r_theme[3] else "พลังของไพ่"
            pos["upright"][cat_slug] = make_entry(card_th, slug, "upright", u_theme, u_kw)
            pos["reversed"][cat_slug] = make_entry(card_th, slug, "reversed", r_theme, r_kw)
        card["positions"][str(idx)] = pos
    return card


def write_compact(output_path, identifier, themes):
    """เขียนไพ่ลงไฟล์และคืนค่า (positions_count, total_entries) สำหรับ verify"""
    card = build(identifier["name_th"], themes)
    card["card_identifier"] = identifier
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(card, f, ensure_ascii=False, indent=2)
    total = sum(len(p["upright"]) + len(p["reversed"]) for p in card["positions"].values())
    return len(card["positions"]), total

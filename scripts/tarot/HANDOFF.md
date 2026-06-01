# Handoff — งานสร้างคำทำนายไพ่ทาโรต์ "แม่หมอจันทรา"

## สถานะปัจจุบัน (เมื่อ commit ล่าสุด)

**Branch:** `claude/exciting-curie-TeFgP`
**Repo:** `xjanova/Thaiprompt-Affiliate`
**Path:** `database/data/tarot/celtic-cross/`

**เสร็จแล้ว: 57/78 ใบ**
- Major Arcana 22/22 ใบ ✅ (00-the-fool → 21-the-world)
- Wands 14/14 ใบ ✅ (ace, two ... ten, page, knight, queen, king)
- Cups 14/14 ใบ ✅ (ace, two ... ten, page, knight, queen, king)
- Swords 7/14 ใบ ⏳ (ace, two, three, four, five, six, seven)
- Pentacles 0/14 ใบ ⏳

**เหลือ 21 ใบ:**
- Swords 7 ใบ: eight, nine, ten, page, knight, queen, king
- Pentacles 14 ใบ: ace, two, three, four, five, six, seven, eight, nine, ten, page, knight, queen, king

## โครงสร้างไพ่แต่ละใบ

- ที่อยู่: `database/data/tarot/celtic-cross/{card-slug}.json`
- 10 positions × 2 directions (upright/reversed) × 5 categories = **100 entries**
- Categories: general, love-relationships, career-finance, personal-growth, health-wellness
- Positions Celtic Cross:
  1. present (ปัจจุบัน)
  2. challenge (อุปสรรค) — **ตีความตั้งตรงเสมอ**: ใน script generator นี้ reversed ของ position 2 จะ copy จาก upright อัตโนมัติ
  3. past (อดีต)
  4. future (อนาคต)
  5. above (จิตสำนึก)
  6. below (จิตใต้สำนึก)
  7. advice (คำแนะนำ)
  8. external (อิทธิพลภายนอก)
  9. hopes-fears (ความหวังและความกลัว)
  10. outcome (ผลลัพธ์สุดท้าย)

## วิธีทำงาน — รูปแบบกะทัดรัด (แนะนำ)

ใช้ `scripts/tarot/gen_card2.py` เป็น helper แล้วเขียน gen script สำหรับแต่ละไพ่:

```python
import sys
sys.path.insert(0, "scripts/tarot")
from gen_card2 import write_compact

def t(c, d, a, k, i=3, tone=""):
    return (c, d, a, k, i, tone)

def mt(data):
    th = {}
    for ps, dirs in data.items():
        th[ps] = {"u": {}, "r": {}}
        for direction, cats in dirs.items():
            for cat, theme in cats.items():
                th[ps][direction][cat] = theme
    return th

data = {
    "present": {
        "u": {
            "general": t("core_meaning", "detailed_interpretation", "advice", ["kw1","kw2","kw3","kw4","kw5"], 3, "tone"),
            "love-relationships": t(...),
            "career-finance": t(...),
            "personal-growth": t(...),
            "health-wellness": t(...)
        },
        "r": {
            "general": t(...),
            # ... ครบ 5 categories
        }
    },
    "challenge": {"u": {...}},  # ไม่ต้องใส่ "r" — script copy จาก "u" อัตโนมัติ
    "past": {"u": {...}, "r": {...}},
    # ... ครบ 10 positions
}

write_compact(
    "database/data/tarot/celtic-cross/{card-slug}.json",
    {
        "name_en": "Eight of Swords",
        "name_th": "แปดดาบ",
        "number": 8,
        "type": "minor_arcana",
        "suit": "swords"
    },
    mt(data)
)
```

ดูตัวอย่างเต็มที่ `scripts/tarot/example_template.py`

## โครงสร้าง JSON ที่ output ออกมา

แต่ละ entry มี:

```json
{
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
  "keywords_th": ["...", "...", "...", "...", "..."],
  "intensity_level": 1-5,
  "emotional_tone": "..."
}
```

> `adjacent_influences` และ `secret_keys_th` ถูก generate อัตโนมัติจาก keyword แรกใน `keywords` — ไม่ต้องเขียนมือทุก field

## ⚠️ ข้อควรระวัง — Thai typo "_"

**ปัญหา:** อักขระ "_" (underscore) มักโผล่แทนเครื่องหมายวรรณยุกต์ไทย (่ ้ ๊ ๋) เมื่อพิมพ์ Thai text ผ่าน tool calls

**ตรวจหลังสร้างไฟล์เสมอ:**

```bash
grep -oE '[ก-๙]_[ก-๙]' database/data/tarot/celtic-cross/{card-slug}.json | sort -u
```

ถ้ามี output → มี typo ต้องแก้ ใช้ sed ตามนี้:

```bash
sed -i 's/ไพ_/ไพ่/g; s/ใหญ_/ใหญ่/g' database/data/tarot/celtic-cross/{card-slug}.json
```

แต่ถ้า typo อยู่ในจุดอื่น (เช่น "ข_าง" ที่ควรเป็น "ข้าง") ต้องใส่ pattern เพิ่ม สังเกตจาก grep output แล้วเขียน sed pattern แก้ทีละกรณี

## ขั้นตอนทำแต่ละไพ่

```bash
# 1. สร้าง script (คัดลอกจาก example_template.py แล้วแก้ themes)
# วางไว้ที่ scripts/tarot/gen_swords_eight.py (ตัวอย่าง)

# 2. รัน
python3 scripts/tarot/gen_swords_eight.py
# คาดหวัง: Positions: 10, Entries: 100

# 3. ตรวจ typo
grep -oE '[ก-๙]_[ก-๙]' database/data/tarot/celtic-cross/swords-eight.json | sort -u | head
# ถ้าว่าง = ไม่มี typo

# 4. Validate JSON + count entries
php -r '
$d = json_decode(file_get_contents("database/data/tarot/celtic-cross/swords-eight.json"), true);
if(!$d){echo "INVALID\n";exit(1);}
$t=0; foreach($d["positions"] as $p){foreach(["upright","reversed"] as $dir)$t+=count($p[$dir]);}
echo "Positions: ".count($d["positions"]).", Entries: $t\n";
'
# คาดหวัง: Positions: 10, Entries: 100

# 5. Commit + push
git add database/data/tarot/celtic-cross/swords-eight.json
git commit -m "feat(tarot): add Celtic Cross meanings for Eight of Swords

- 10 positions x 2 directions x 5 categories = 100 entries
- Card: Eight of Swords / แปดดาบ (Minor Arcana - Swords #8)
- Themes: imprisonment, restriction, victim mentality
- Reversed: freedom, self-acceptance, new perspective"

git push -u origin claude/exciting-curie-TeFgP
```

## Theme Reference สำหรับ 21 ใบที่เหลือ

### Swords (7 ใบ)

| Card | Thai | Themes (upright) | Reversed |
|------|------|------------------|----------|
| Eight of Swords | แปดดาบ | imprisonment, restriction, victim mentality, self-limiting beliefs | freedom, self-acceptance, new perspective |
| Nine of Swords | เก้าดาบ | anxiety, worry, nightmares, mental anguish | hope, recovery from anxiety, releasing worry |
| Ten of Swords | สิบดาบ | painful endings, defeat, rock bottom, betrayal | recovery, surviving the worst, new dawn |
| Page of Swords | ข้าราชบริพารดาบ | curiosity, mental energy, new ideas, vigilance | gossip, hasty words, all talk no action |
| Knight of Swords | อัศวินดาบ | action, ambition, swift decisions, charging in | recklessness, impatience, scattered focus |
| Queen of Swords | ราชินีดาบ | independent, perceptive, honest, sharp wit | cold, bitter, harsh judgment, isolated |
| King of Swords | กษัตริย์ดาบ | intellectual authority, truth, ethical leadership | tyranny, manipulation, abuse of intellect |

### Pentacles (14 ใบ)

| Card | Thai | Themes (upright) | Reversed |
|------|------|------------------|----------|
| Ace of Pentacles | เอซเหรียญ | new financial opportunity, manifestation, prosperity | missed opportunity, scarcity mindset |
| Two of Pentacles | สองเหรียญ | balance, juggling priorities, adaptability | overwhelm, imbalance, dropping the ball |
| Three of Pentacles | สามเหรียญ | teamwork, collaboration, learning, craftsmanship | lack of teamwork, poor quality work |
| Four of Pentacles | สี่เหรียญ | security, control, conservation, holding tight | greed, hoarding, control issues |
| Five of Pentacles | ห้าเหรียญ | financial loss, hardship, feeling left out | recovery from loss, finding help |
| Six of Pentacles | หกเหรียญ | generosity, charity, giving and receiving | strings attached, one-sided giving |
| Seven of Pentacles | เจ็ดเหรียญ | patience, long-term view, investment, evaluation | impatience, lack of progress, wasted effort |
| Eight of Pentacles | แปดเหรียญ | dedication, mastery, skill development, craftsmanship | perfectionism, lack of focus, monotony |
| Nine of Pentacles | เก้าเหรียญ | luxury, self-sufficiency, financial independence | reckless spending, dependence on others |
| Ten of Pentacles | สิบเหรียญ | wealth, legacy, family, lasting prosperity | family disputes, financial loss, no legacy |
| Page of Pentacles | ข้าราชบริพารเหรียญ | new opportunities, manifestation, studious | lack of progress, procrastination |
| Knight of Pentacles | อัศวินเหรียญ | hard work, reliability, methodical, dedicated | laziness, boredom, lack of progress |
| Queen of Pentacles | ราชินีเหรียญ | nurturing, practical, abundance, security | smothering, materialistic, neglect self-care |
| King of Pentacles | กษัตริย์เหรียญ | wealth, business success, security, leadership | greed, materialism, stubborn, corrupt |

## ความหมายไทยที่ใช้ใน naming

- Suits: wands=ไม้เท้า, cups=ถ้วย, swords=ดาบ, pentacles=เหรียญ
- Ranks: Ace=เอซ, Two=สอง, Three=สาม, Four=สี่, Five=ห้า, Six=หก, Seven=เจ็ด, Eight=แปด, Nine=เก้า, Ten=สิบ, Page=ข้าราชบริพาร, Knight=อัศวิน, Queen=ราชินี, King=กษัตริย์
- name_en format: "{Rank} of {Suit}" เช่น "Eight of Pentacles"
- name_th format: "{rank_th}{suit_th}" เช่น "แปดเหรียญ"
- filename: "{suit}-{rank}.json" เช่น "pentacles-eight.json"

## Git rules

- ห้าม push branch อื่นที่ไม่ใช่ `claude/exciting-curie-TeFgP`
- Push: `git push -u origin claude/exciting-curie-TeFgP`
- ถ้า network error: retry สูงสุด 4 ครั้ง backoff 2s, 4s, 8s, 16s
- ทำทีละใบให้จบ + commit + push **ก่อน** เริ่มใบใหม่

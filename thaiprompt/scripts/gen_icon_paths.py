"""สร้าง components/ui/iconPaths.ts จาก SVG ของ @phosphor-icons/core (MIT)

ใช้งาน:
  npm pack @phosphor-icons/core@2.1.1 && tar -xzf phosphor-icons-core-2.1.1.tgz
  python scripts/gen_icon_paths.py package/assets components/ui/iconPaths.ts

ฝังเฉพาะไอคอนในรายการ ICONS (regular + fill และ bold สำหรับ BOLD) ไม่ติดตั้งทั้งไลบรารี 1,500 ตัว
"""
import os
import re
import sys

CORE = sys.argv[1]
OUT = sys.argv[2]

ICONS = """
house house-line storefront receipt wallet user-circle user users-three user-plus
bell bell-simple bell-ringing map-pin map-pin-line map-trifold navigation-arrow crosshair compass
caret-down caret-up caret-left caret-right arrow-left arrow-right arrow-up-right arrow-down-left arrow-down arrow-up
arrow-clockwise arrows-clockwise arrow-square-out arrow-counter-clockwise
magnifying-glass sliders-horizontal funnel plus minus check x check-circle x-circle warning warning-circle
info question paper-plane-tilt bank clock clock-counter-clockwise timer hourglass eye eye-slash heart star
moped motorcycle moon-stars sun moon shopping-bag-open shopping-bag shopping-cart shopping-cart-simple basket
share-network export copy link crown-simple note-pencil pencil-simple trash hand-tap hand-coins hand-heart handshake
coins money credit-card qr-code scan identification-card shield-check shield lock lock-key key gear-six
chat-circle-dots chats headset book-open sign-out sign-in camera image images upload-simple download-simple
phone envelope globe translate package truck tag gift ticket sparkle fire lightning seal-check cooking-pot
bowl-food fork-knife carrot leaf plant buildings calendar calendar-blank list dots-three dots-three-vertical
squares-four power wifi-slash cloud-slash file-text clipboard-text prohibit smiley thumbs-up trophy medal
target road-horizon path chart-bar chart-line-up trend-up percent calculator megaphone flag lifebuoy
fingerprint device-mobile cards sliders house-simple speedometer battery-full cell-signal-full wifi-high
navigation-arrow broadcast storefront coffee cake hamburger pizza orange-slice fish egg-crack
""".split()

BOLD = set('plus minus check x arrow-right arrow-left caret-right caret-left caret-down caret-up sliders-horizontal hand-tap arrow-up-right'.split())

PATH_RE = re.compile(r'<path d="([^"]+)"')
OTHER_RE = re.compile(r'<(circle|rect|line|polyline|polygon|ellipse)\b')


def read(weight, name):
    fname = f'{name}.svg' if weight == 'regular' else f'{name}-{weight}.svg'
    p = os.path.join(CORE, weight, fname)
    if not os.path.exists(p):
        return None
    svg = open(p, encoding='utf-8').read()
    if OTHER_RE.search(svg):
        print('WARN non-path element in', weight, name)
    return PATH_RE.findall(svg)


def camel(name):
    parts = name.split('-')
    return parts[0] + ''.join(x.capitalize() for x in parts[1:])


seen = []
lines = []
missing = []
for name in ICONS:
    if name in seen:
        continue
    seen.append(name)
    reg = read('regular', name)
    fill = read('fill', name)
    if not reg or not fill:
        missing.append(name)
        continue
    entry = [f"  '{name}': {{", f"    regular: {reg!r},", f"    fill: {fill!r},"]
    if name in BOLD:
        bold = read('bold', name)
        if bold:
            entry.append(f"    bold: {bold!r},")
    entry.append('  },')
    lines.extend(entry)

header = '''/**
 * เส้นไอคอน Phosphor (MIT) เฉพาะที่แอปใช้ — สร้างอัตโนมัติจาก @phosphor-icons/core 2.1.1
 * ห้ามแก้มือ: เพิ่มชื่อไอคอนใน scripts/gen_icon_paths.py แล้วสร้างใหม่
 * viewBox 0 0 256 256 · regular = เส้น · fill = ทึบ · bold = เส้นหนา (บางตัว)
 */

export interface IconPathSet {
  regular: string[];
  fill: string[];
  bold?: string[];
}

export const ICON_PATHS = {
'''
footer = '''} satisfies Record<string, IconPathSet>;

export type IconName = keyof typeof ICON_PATHS;
'''
body = '\n'.join(lines).replace("['", '["').replace("']", '"]').replace("', '", '", "')
open(OUT, 'w', encoding='utf-8', newline='\n').write(header + body + '\n' + footer)
print('icons', len(seen) - len(missing), 'missing', missing, 'bytes', os.path.getsize(OUT))

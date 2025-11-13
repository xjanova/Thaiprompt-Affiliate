# คู่มือเสริมทางเศรษฐี Pro - Wealth Guide Pro

## 📋 Overview

คู่มือเสริมทางเศรษฐี Pro เป็นเวอร์ชันอัพเกรดของคู่มือเดิม พร้อมด้วย:
- ✨ **3D Mind Map** - แผนผังธุรกิจแบบ 3 มิติที่ interactive
- 💸 **3D Income Flow Visualization** - แสดงกระแสเงินไหลจากทุกช่องทางแบบเรียลไทม์
- 🧮 **Income Calculator** - เครื่องคำนวณรายได้แบบ interactive
- 🗺️ **Rank Roadmap** - เส้นทางสู่ระดับ Diamond อย่างชัดเจน
- 📊 **6 ช่องทางรายได้** - อธิบายครบถ้วนทุกช่องทาง

## 🎯 Features

### 1. 3D Mind Map (OpenGL/WebGL)

แผนผังแสดงภาพรวมธุรกิจทั้งหมดในรูปแบบ 3D:

**เทคโนโลยี:**
- Three.js - WebGL renderer
- OrbitControls - การควบคุมกล้อง
- Custom shaders - เอฟเฟกต์พิเศษ

**ฟีเจอร์:**
- หมุน ซูม แพนได้อย่างอิสระ
- Auto-rotate mode
- Interactive nodes - คลิกเพื่อดูรายละเอียด
- Animated particles - ฟองสบู่ลอยไปมา
- Glow effects - เอฟเฟกต์แสงเรืองแสง
- Connection lines - เส้นเชื่อมระหว่างโหนด

**การใช้งาน:**
```javascript
import Wealth3DMindMap from './wealth-3d-mindmap.js';

const mindMap = new Wealth3DMindMap('container-id', {
    theme: 'gold',
    autoRotate: true,
    showLabels: true
});

// Focus on specific node
mindMap.focusOnNode('รายได้ตรง');

// Toggle auto rotate
mindMap.toggleAutoRotate();
```

### 2. 3D Income Flow Visualization

แสดงการไหลของเงินจากทุกช่องทางรายได้แบบ 3D:

**เทคโนโลยี:**
- Three.js - WebGL renderer
- Particle system - ระบบอนุภาค
- Curve animations - แอนิเมชันเส้นโค้ง

**ฟีเจอร์:**
- แสดงกระแสเงินไหลจาก 6 ช่องทาง
- Animated particles - อนุภาคเงินไหลเข้า wallet
- Real-time income display - แสดงรายได้แบบเรียลไทม์
- Color-coded streams - แยกสีตามแหล่งรายได้
- Interactive labels - ป้ายชื่อติดตามโหนด

**การใช้งาน:**
```javascript
import Wealth3DIncomeFlow from './wealth-3d-income-flow.js';

const incomeFlow = new Wealth3DIncomeFlow('container-id', {
    showAmounts: true,
    animationSpeed: 1.0,
    incomeData: {
        direct: 15000,
        binary: 8000,
        rank: 25000,
        sponsor: 5000,
        marketplace: 12000,
        team: 18000
    }
});

// Update income data
incomeFlow.updateIncomeData({
    direct: 20000,
    binary: 10000
});
```

### 3. Income Calculator

เครื่องคำนวณรายได้แบบ interactive:

**ฟีเจอร์:**
- คำนวณรายได้จาก Direct Commission
- คำนวณรายได้จาก Binary Matching
- คำนวณรายได้จาก Sponsor Bonus
- คำนวณรายได้จาก Team Override
- แสดงรายได้รวมต่อเดือนและต่อปี

**ตัวแปรที่ใช้:**
- จำนวนสมาชิกที่แนะนำต่อเดือน
- ยอดซื้อเฉลี่ยต่อคน
- ขนาดทีมปัจจุบัน
- Binary Pairs ต่อสัปดาห์

### 4. 6 ช่องทางรายได้

#### 1. Direct Commission (รายได้ตรง)
- 30% ชั้นที่ 1 (Direct)
- 20% ชั้นที่ 2
- 10-15% ชั้นที่ 3-5
- ระบบ Unilevel ลึก 5 ชั้น

#### 2. Binary Matching Bonus
- 1,000฿ ต่อ 1 คู่
- ระบบ Binary Tree (ขาซ้าย-ขวา)
- ยิ่งสมดุลยิ่งได้มาก

#### 3. Rank Achievement Bonus
- Bronze: 5,000฿ + 1,000฿/เดือน
- Silver: 20,000฿ + 5,000฿/เดือน
- Gold: 50,000฿ + 15,000฿/เดือน
- Platinum: 200,000฿ + 50,000฿/เดือน
- Diamond: 500,000฿ + 150,000฿/เดือน

#### 4. Sponsorship Bonus
- 10-20% ของยอดซื้อแรก
- รับทันทีเมื่อแนะนำสมาชิกใหม่

#### 5. Marketplace Affiliate
- Lazada: 2-10% commission
- Shopee: 5-15% commission
- TikTok Shop: 8-20% commission

#### 6. Team Override Bonus
- 5-10% จากยอดขายรวมของทีม
- ยิ่งทีมใหญ่ยิ่งได้มาก

### 5. Rank Roadmap

เส้นทางสู่ระดับ Diamond:

**Timeline:**
- Fast Track: 6-12 เดือน (ทำงานเต็มเวลา)
- Standard: 1-2 ปี (ทำงานสม่ำเสมอ)
- Steady: 2-3 ปี (รายได้เสริม)

**เงื่อนไขแต่ละยศ:**
- Bronze: 3 คน, ยอดซื้อ 5,000฿
- Silver: 15 คน, ยอด 50,000฿/เดือน, มี Bronze 3 คน
- Gold: 50 คน, ยอด 150,000฿/เดือน, มี Silver 3 คน
- Platinum: 150 คน, ยอด 500,000฿/เดือน, มี Gold 3 คน
- Diamond: 500 คน, ยอด 2,000,000฿/เดือน, มี Platinum 3 คน

## 🛠️ Technical Stack

### Frontend
- **Three.js** - WebGL 3D library
- **Vite** - Build tool
- **Tailwind CSS** - Styling
- **Alpine.js** - Reactive UI
- **GSAP** - Animations

### 3D Components
- **WebGL Renderer** - Hardware accelerated rendering
- **OrbitControls** - Camera control
- **Particle Systems** - อนุภาคแอนิเมชัน
- **Custom Shaders** - เอฟเฟกต์พิเศษ

## 📁 File Structure

```
resources/
├── js/
│   ├── wealth-3d-mindmap.js       # 3D Mind Map component
│   ├── wealth-3d-income-flow.js   # 3D Income Flow component
│   └── wealth-guide-pro.js        # Main entry point
├── views/
│   └── user/
│       └── wealth-guide-pro.blade.php  # Main view
└── css/
    └── app.css                     # Styles

routes/
└── user.php                        # Routes

vite.config.js                      # Vite configuration
package.json                        # Dependencies
```

## 🚀 Installation & Setup

### 1. Install Dependencies

```bash
npm install three --save
```

### 2. Build Assets

```bash
npm run build
# or for development
npm run dev
```

### 3. Access the Page

Navigate to: `/user/wealth-guide-pro`

Route name: `user.wealth-guide-pro`

## 🎨 Customization

### Change Theme Colors

Edit `wealth-3d-mindmap.js`:
```javascript
const colorPalette = {
    primary: 0xffd700,    // Gold
    secondary: 0x4ecdc4,  // Cyan
    accent: 0xff6b6b      // Red
};
```

### Adjust Animation Speed

Edit `wealth-3d-income-flow.js`:
```javascript
const incomeFlow = new Wealth3DIncomeFlow('container-id', {
    animationSpeed: 1.5  // 1.0 = normal, 2.0 = double speed
});
```

### Update Income Data

Pass real data from backend:
```blade
<script>
window.userIncomeData = {!! json_encode([
    'direct' => auth()->user()->direct_commission ?? 0,
    'binary' => auth()->user()->binary_bonus ?? 0,
    'rank' => auth()->user()->rank_bonus ?? 0,
    'sponsor' => auth()->user()->sponsor_bonus ?? 0,
    'marketplace' => auth()->user()->marketplace_commission ?? 0,
    'team' => auth()->user()->team_override ?? 0,
]) !!};
</script>
```

## 📱 Responsive Design

- ✅ Desktop (1920x1080+)
- ✅ Laptop (1366x768+)
- ✅ Tablet (768x1024+)
- ✅ Mobile (375x667+)

3D components จะปรับขนาดอัตโนมัติตามหน้าจอ

## ⚡ Performance

### Optimizations
- Manual chunks for Three.js (separate bundle)
- Lazy loading for heavy components
- Pause animations when page is hidden
- Auto dispose resources on unmount
- Throttled render loop (60 FPS)

### Browser Support
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

Requires WebGL 1.0+ support

## 🐛 Troubleshooting

### 3D not loading?
1. Check browser console for errors
2. Verify WebGL is enabled: Visit `chrome://gpu`
3. Update graphics drivers
4. Try disabling hardware acceleration

### Performance issues?
1. Reduce particle count in source code
2. Disable auto-rotate
3. Lower animation speed
4. Use simpler materials (less metalness/roughness)

### Build errors?
1. Clear node_modules: `rm -rf node_modules && npm install`
2. Clear Vite cache: `rm -rf node_modules/.vite`
3. Rebuild: `npm run build`

## 📝 Future Enhancements

### Planned Features
- [ ] VR mode support
- [ ] Export 3D scene as image
- [ ] Real-time collaboration
- [ ] Animated tutorial walkthrough
- [ ] Custom node creation
- [ ] Save/load layouts
- [ ] Share mind map links
- [ ] Mobile gesture controls
- [ ] Voice commands
- [ ] AI-powered suggestions

### Performance Improvements
- [ ] WebGL 2.0 features
- [ ] Instanced rendering
- [ ] Level of Detail (LOD)
- [ ] Occlusion culling
- [ ] Web Workers for calculations

## 🤝 Contributing

ถ้าต้องการปรับปรุงหรือเพิ่มฟีเจอร์:

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

## 📄 License

Copyright © 2024 Thaiprompt Affiliate. All rights reserved.

## 📞 Support

- 📧 Email: support@thaiprompt.com
- 💬 Discord: [Join our community](#)
- 📚 Docs: [Full documentation](#)

---

**สร้างโดย:** Thaiprompt Team
**เวอร์ชัน:** 1.0.0
**วันที่อัพเดท:** 13 พฤศจิกายน 2024
**เทคโนโลยี:** Three.js + WebGL + Laravel + Vite

# 🎮 Demo Games & Interactive Features

<div align="center">

![Games](https://img.shields.io/badge/Games-3%20Demos-brightgreen.svg)
![Technology](https://img.shields.io/badge/Tech-HTML5%20%7C%20Canvas%20%7C%20WebGL-blue.svg)
![Status](https://img.shields.io/badge/Status-Active-success.svg)

**คอลเลคชั่นเกมและ Demo แอนิเมชั่นที่สวยงาม**
พัฒนาด้วย Pure JavaScript | HTML5 Canvas | Three.js

[🎯 Tetris](#-tetris-game) •
[🚀 Space Shooter](#-space-shooter) •
[🌐 3D Navigation](#-3d-navigation) •
[💫 Loading Animations](#-loading-animations)

</div>

---

## 🎯 Tetris Game

<div align="center">

### เกมเตตริสสุดเท่ห์ พร้อมระบบบันทึก Top Score

![Tetris](https://img.shields.io/badge/Type-Puzzle%20Game-purple.svg)
![Tech](https://img.shields.io/badge/Tech-Canvas%20API-orange.svg)
![Players](https://img.shields.io/badge/Players-Single%20Player-blue.svg)

**🎮 เล่นเลย:** `/demo/tetris`

</div>

### 📋 รายละเอียด

เกม Tetris คลาสสิกที่ได้รับการออกแบบใหม่ด้วย UI/UX ที่ทันสมัย พร้อมฟีเจอร์ครบครันและระบบบันทึกคะแนนสูงสุดแบบถาวร

### ✨ ฟีเจอร์เด่น

#### 🎮 การเล่นเกม
- ✅ **เกมส์ Tetris ครบรูปแบบ** - กลไกการเล่นแบบคลาสสิก
- ✅ **7 Tetromino Types** - I, O, T, S, Z, J, L พร้อมสีสันสวยงาม
- ✅ **Ghost Piece System** - เงาแสดงตำแหน่งที่ชิ้นจะตกลงไป
- ✅ **Next Piece Preview** - ดูชิ้นถัดไปที่จะมา
- ✅ **Level Progression** - ความเร็วเพิ่มขึ้นทุก 10 เส้นที่ล้าง
- ✅ **Score System** - คำนวณคะแนนตามเส้นที่ล้างและระดับ

#### 🎯 ระบบคะแนน
| เส้นที่ล้าง | คะแนนพื้นฐาน | คูณด้วยระดับ |
|------------|-------------|-------------|
| 1 เส้น | 100 | ✕ Level |
| 2 เส้น | 300 | ✕ Level |
| 3 เส้น | 500 | ✕ Level |
| 4 เส้น (Tetris!) | 800 | ✕ Level |

#### 🏆 Top Score Tracking
- 💾 **localStorage Persistence** - บันทึกคะแนนสูงสุดถาวร
- 🎉 **New High Score Alert** - แจ้งเตือนเมื่อทำสถิติใหม่
- 📊 **Real-time Display** - แสดงสถิติปัจจุบันและสูงสุด
- 🌟 **Golden Badge** - ป้ายทองสำหรับคะแนนสูงสุด

#### 🎨 UI/UX Design
- 🌈 **Gradient Background** - พื้นหลังสีม่วงไล่เฉดสวยงาม
- ✨ **Glassmorphism Effect** - เอฟเฟกต์กระจกฝ้าทันสมัย
- 💎 **3D Block Design** - บล็อกสีสันสดใสพร้อม Gradient
- 🎭 **Smooth Animations** - แอนิเมชั่นที่ลื่นไหลด้วย requestAnimationFrame
- 📱 **Responsive Layout** - ใช้งานได้ทุกขนาดหน้าจอ
- 🇹🇭 **Thai Interface** - ภาษาไทยทั้งหมด

### 🎹 การควบคุม

| ปุ่ม | การทำงาน |
|------|---------|
| ⬅️ `Arrow Left` | เลื่อนชิ้นไปทางซ้าย |
| ➡️ `Arrow Right` | เลื่อนชิ้นไปทางขวา |
| ⬆️ `Arrow Up` | หมุนชิ้น 90 องศา |
| ⬇️ `Arrow Down` | เร่งการตก (Soft Drop) |
| `SPACE` | ตกลงทันที (Hard Drop) |
| `P` | หยุดชั่วคราว (Pause/Resume) |

### 🔧 เทคนิคการพัฒนา

#### Frontend Technology
```javascript
- HTML5 Canvas API
- Vanilla JavaScript (ES6+)
- CSS3 Animations
- LocalStorage API
- requestAnimationFrame
```

#### Architecture
- **MVC Pattern** - แยก Logic, Render, และ State
- **Game Loop** - ใช้ requestAnimationFrame สำหรับ 60 FPS
- **Collision Detection** - ระบบตรวจจับการชนที่แม่นยำ
- **Matrix System** - Grid 10x20 สำหรับ Game Board
- **Rotation Algorithm** - Matrix Rotation แบบ Clockwise

#### Performance
- ⚡ **60 FPS** - เล่นได้ลื่นไหล
- 💪 **Zero Dependencies** - ไม่ต้องใช้ Library เพิ่ม
- 🚀 **Fast Loading** - โหลดเร็วทันใจ
- 📦 **Small Size** - ไฟล์เดียว < 50 KB

### 📊 Game Stats Display

เกมแสดงสถิติแบบ Real-time:

1. **Score Panel** 🎯
   - คะแนนปัจจุบัน
   - ระดับ (Level)
   - จำนวนเส้นที่ล้าง

2. **High Score Badge** 🏆
   - แสดงคะแนนสูงสุดตลอดกาล
   - เอฟเฟกต์ทอง Pulsing
   - บันทึกอัตโนมัติ

3. **Next Piece Preview** ⏭️
   - Canvas แสดงชิ้นถัดไป
   - ขนาด 120x120px
   - พื้นหลังโปร่งใส

4. **Controls Guide** 🎮
   - แสดงคีย์บอร์ดทั้งหมด
   - ตำแหน่งด้านล่างซ้าย
   - ออกแบบแบบ Retro

### 🎭 Game Over Screen

เมื่อเกมจบ จะแสดง:
- 📊 **Final Stats** - คะแนน, ระดับ, เส้น
- 🎉 **New High Score Message** - ถ้าทำสถิติใหม่
- 🔄 **Play Again Button** - เริ่มเกมใหม่ทันที
- 🏠 **Home Button** - กลับหน้าหลัก

### 💻 ตัวอย่างโค้ด

#### การสร้าง Tetromino
```javascript
const SHAPES = {
    I: [[1, 1, 1, 1]],
    O: [[1, 1], [1, 1]],
    T: [[0, 1, 0], [1, 1, 1]],
    S: [[0, 1, 1], [1, 1, 0]],
    Z: [[1, 1, 0], [0, 1, 1]],
    J: [[1, 0, 0], [1, 1, 1]],
    L: [[0, 0, 1], [1, 1, 1]]
};
```

#### การบันทึก High Score
```javascript
// บันทึก
localStorage.setItem('tetris-high-score', highScore);

// โหลด
highScore = localStorage.getItem('tetris-high-score') || 0;
```

### 🎨 Color Scheme

| Piece | Color | Hex Code |
|-------|-------|----------|
| I | Cyan | `#00f0f0` |
| O | Yellow | `#f0f000` |
| T | Purple | `#a000f0` |
| S | Green | `#00f000` |
| Z | Red | `#f00000` |
| J | Blue | `#0000f0` |
| L | Orange | `#f0a000` |

### 📁 ไฟล์ที่เกี่ยวข้อง

```
resources/views/demo-tetris.blade.php  # ไฟล์เกมทั้งหมด (HTML + CSS + JS)
routes/web.php                          # Route: /demo/tetris
```

### 🎯 Tips & Tricks

1. **Hard Drop Strategy** 💡
   - ใช้ SPACE เพื่อวางชิ้นเร็วขึ้น
   - ดู Ghost Piece เพื่อวางตำแหน่งที่แม่นยำ

2. **Combo Scoring** 🔥
   - พยายามล้าง 4 เส้นพร้อมกัน (Tetris) เพื่อคะแนนสูงสุด
   - เก็บ I-piece ไว้สำหรับ Combo

3. **Level Management** 📈
   - ระดับสูง = คะแนนมากขึ้น
   - ฝึกฝนที่ระดับต่ำก่อนเพื่อปรับตัว

4. **Ghost Piece** 👻
   - ใช้ประโยชน์จาก Ghost Piece
   - มองเห็นตำแหน่งที่จะตกล่วงหน้า

---

## 🚀 Space Shooter

<div align="center">

### เกมยิงยานอวกาศ 3D สุดมันส์

![Type](https://img.shields.io/badge/Type-Action%20Shooter-red.svg)
![Tech](https://img.shields.io/badge/Tech-Three.js%20%7C%20WebGL-green.svg)

**🎮 เล่นเลย:** `/demo/space-shooter`

</div>

### ✨ ฟีเจอร์

- 🚀 **3D Spaceship** - ยานอวกาศควบคุมได้ 360 องศา
- 💥 **Shooting System** - ยิงเลเซอร์ได้ไม่จำกัด
- 🎯 **Enemy AI** - ศัตรูที่ฉลาดและท้าทาย
- ⭐ **Star Field** - พื้นหลังดาวที่เคลื่อนไหว
- 🔊 **Sound Effects** - เสียงประกอบ (ถ้าเปิดใช้งาน)
- 📊 **Score System** - คะแนนและระดับ

### 🎹 การควบคุม

- ⬅️➡️ **Arrow Keys** - เลื่อนยาน
- `SPACE` - ยิง
- `ESC` - Pause

### 🔧 เทคโนโลยี

- Three.js
- WebGL
- GSAP Animation
- Vanilla JavaScript

---

## 🌐 3D Navigation

<div align="center">

### ระบบนำทางแบบ 3D Interactive

![Type](https://img.shields.io/badge/Type-Interactive%20Demo-blue.svg)
![Tech](https://img.shields.io/badge/Tech-Three.js%20%7C%20WebGL-cyan.svg)

**🎮 ดูเลย:** `/demo/3d-navigation`

</div>

### ✨ ฟีเจอร์

- 🗺️ **3D Environment** - สภาพแวดล้อม 3 มิติ
- 🎮 **Interactive Controls** - ควบคุมกล้องได้อิสระ
- 📍 **Waypoints** - จุดหมายปลายทางที่กำหนดได้
- 🎨 **Beautiful Lighting** - แสงเงาสมจริง
- 🔄 **Smooth Transitions** - การเคลื่อนไหวที่ลื่นไหล

### 🎹 การควบคุม

- 🖱️ **Mouse Drag** - หมุนมุมมอง
- 🎯 **Click** - เลือกจุดหมาย
- 🔄 **Scroll** - ซูมเข้า/ออก

---

## 💫 Loading Animations

<div align="center">

### คอลเลคชั่นแอนิเมชั่น Loading สวยงาม

![Type](https://img.shields.io/badge/Type-Animation%20Showcase-yellow.svg)
![Tech](https://img.shields.io/badge/Tech-GSAP%20%7C%20CSS3-pink.svg)

**🎮 ดูเลย:** `/demo/loading`

</div>

### ✨ ฟีเจอร์

- ⚡ **GSAP Effects** - แอนิเมชั่นด้วย GreenSock
- 🎨 **CSS Animations** - เอฟเฟกต์ CSS3 ขั้นสูง
- 🌀 **Multiple Styles** - หลายรูปแบบให้เลือก
- 💎 **Smooth Performance** - ทำงานลื่นไหล 60 FPS
- 📱 **Responsive** - ใช้งานได้ทุกอุปกรณ์

### 🎭 รูปแบบ Loading

1. **Spinner** - หมุนวนทั่วไป
2. **Progress Bar** - แถบความคืบหน้า
3. **Dots Animation** - จุดเคลื่อนไหว
4. **Wave Effect** - เอฟเฟกต์คลื่น
5. **Morphing Shapes** - รูปทรงเปลี่ยนแปลง

---

## 🛠️ Technical Specifications

### 📋 ความต้องการของระบบ

| Component | ข้อกำหนด |
|-----------|----------|
| **Browser** | Chrome 90+, Firefox 88+, Safari 14+ |
| **JavaScript** | ES6+ Support |
| **Canvas** | HTML5 Canvas Support |
| **WebGL** | WebGL 1.0+ (สำหรับเกม 3D) |
| **Storage** | LocalStorage Support |

### 🎯 Browser Compatibility

| Browser | Tetris | Space Shooter | 3D Nav | Loading |
|---------|--------|---------------|--------|---------|
| Chrome | ✅ | ✅ | ✅ | ✅ |
| Firefox | ✅ | ✅ | ✅ | ✅ |
| Safari | ✅ | ✅ | ✅ | ✅ |
| Edge | ✅ | ✅ | ✅ | ✅ |
| Mobile | ✅* | ⚠️ | ⚠️ | ✅ |

*✅ = รองรับเต็มรูปแบบ | ⚠️ = รองรับบางส่วน*

### 📊 Performance Metrics

#### Tetris Game
- **FPS:** 60
- **File Size:** ~45 KB (inline)
- **Load Time:** < 1 วินาที
- **Memory:** ~10-20 MB

#### Space Shooter
- **FPS:** 60
- **File Size:** ~150 KB + Three.js
- **Load Time:** ~2-3 วินาที
- **Memory:** ~50-100 MB

---

## 🎨 Design Philosophy

### การออกแบบเกมทั้งหมดยึดหลัก:

1. **🎯 User Experience First**
   - เล่นง่าย เข้าใจง่าย
   - Responsive ทุกอุปกรณ์
   - ไม่ต้อง Tutorial ยาวๆ

2. **✨ Modern Aesthetics**
   - Gradient & Glassmorphism
   - Smooth Animations
   - Color Harmony

3. **⚡ Performance**
   - 60 FPS ตลอดเวลา
   - โหลดเร็ว
   - ใช้ทรัพยากรน้อย

4. **💾 No Dependencies (Tetris)**
   - Pure JavaScript
   - ไม่ต้องติดตั้งเพิ่ม
   - เล่นได้ทันที

5. **🌐 Accessibility**
   - Keyboard Support
   - Visual Feedback
   - Clear Instructions

---

## 📖 เอกสารเพิ่มเติม

### การใช้งาน

1. **เข้าถึงเกม**
   ```
   http://yourdomain.com/demo/tetris
   http://yourdomain.com/demo/space-shooter
   http://yourdomain.com/demo/3d-navigation
   http://yourdomain.com/demo/loading
   ```

2. **การฝังใน Website**
   ```html
   <iframe src="/demo/tetris" width="100%" height="800px"></iframe>
   ```

3. **การเพิ่มเกมใหม่**
   - สร้างไฟล์ `demo-yourgame.blade.php`
   - เพิ่ม Route ใน `routes/web.php`
   - อัปเดตเอกสารนี้

### สำหรับนักพัฒนา

#### โครงสร้างไฟล์เกม

```
resources/views/
├── demo-tetris.blade.php       # Tetris Game
├── demo-space-shooter.blade.php # Space Shooter
├── demo-3d-navigation.blade.php # 3D Navigation
└── demo-loading.blade.php       # Loading Animations
```

#### การเพิ่ม Route

```php
// routes/web.php
Route::get('/demo/your-game', function () {
    return view('demo-your-game');
})->name('demo.your-game');
```

---

## 🎮 Future Games

### 🚧 กำลังวางแผน

- 🧩 **Sudoku** - เกมซูโดกุ
- 🎰 **Slot Machine** - สล็อตแมชชีน
- 🃏 **Card Games** - เกมไพ่
- 🎯 **Dart Game** - เกมปาเป้า
- 🏓 **Pong** - เกม Pong คลาสสิก

---

## 💡 Tips for Players

### 🏆 การทำคะแนนสูง (Tetris)

1. **เรียนรู้ Pattern** - จำรูปแบบชิ้นต่างๆ
2. **สร้างพื้นที่เรียบ** - อย่าทิ้งช่องว่าง
3. **เก็บพื้นที่สำหรับ I-piece** - เพื่อทำ Tetris
4. **ฝึกการหมุน** - หมุนได้อย่างรวดเร็ว
5. **ใช้ Ghost Piece** - วางตำแหน่งได้แม่นยำ

---

## 🐛 Known Issues

### Tetris
- ✅ ไม่มีปัญหาที่ทราบ

### Space Shooter
- ⚠️ บน Mobile: Performance อาจช้าในบางเครื่อง

### 3D Navigation
- ⚠️ บน Mobile: Touch controls จำกัด

---

## 📞 Support & Feedback

### 💬 ติดต่อเรา

- **GitHub Issues**: [Report Bug](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- **Email**: support@thaiprompt.com
- **Website**: https://thaiprompt.com

### 🌟 Feature Requests

มีไอเดียเกมใหม่? แจ้งได้ที่ [GitHub Discussions](https://github.com/xjanova/Thaiprompt-Affiliate/discussions)

---

<div align="center">

**Made with 🎮 & ❤️ in Thailand**

[🏠 Back to Main](README.md) •
[📖 Documentation](INSTALLATION.md) •
[🐛 Report Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

**Happy Gaming! 🎉**

</div>

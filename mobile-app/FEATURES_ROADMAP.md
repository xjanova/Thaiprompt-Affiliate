# 📱 Mobile App Features Roadmap

## 🎯 สรุปความต้องการทั้งหมด

---

## 1️⃣ API Endpoints ที่ต้องสร้าง (Backend Laravel)

### 🔐 Authentication
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/register` | POST | สมัครสมาชิก | 🔴 High |
| `/v1/forgot-password` | POST | ลืมรหัสผ่าน | 🟡 Medium |
| `/v1/reset-password` | POST | รีเซ็ตรหัสผ่าน | 🟡 Medium |
| `/v1/verify-otp` | POST | ยืนยัน OTP | 🟡 Medium |

### 👤 Profile
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/profile` | GET | ดึงข้อมูลโปรไฟล์ | 🔴 High |
| `/v1/profile` | PUT | อัพเดทโปรไฟล์ | 🔴 High |
| `/v1/profile/avatar` | POST | อัพโหลดรูปโปรไฟล์ | 🟡 Medium |
| `/v1/profile/change-password` | POST | เปลี่ยนรหัสผ่าน | 🔴 High |
| `/v1/profile/referral-code` | GET | ดึงรหัสแนะนำ | 🔴 High |

### 🛒 E-commerce / Shopping
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/products` | GET | รายการสินค้า | 🔴 High |
| `/v1/products/{id}` | GET | รายละเอียดสินค้า | 🔴 High |
| `/v1/products/categories` | GET | หมวดหมู่สินค้า | 🔴 High |
| `/v1/products/search` | GET | ค้นหาสินค้า | 🔴 High |
| `/v1/cart` | GET | ดูตะกร้า | 🔴 High |
| `/v1/cart/add` | POST | เพิ่มสินค้า | 🔴 High |
| `/v1/cart/update` | PUT | แก้ไขจำนวน | 🔴 High |
| `/v1/cart/remove` | DELETE | ลบสินค้า | 🔴 High |
| `/v1/orders` | GET | รายการคำสั่งซื้อ | 🔴 High |
| `/v1/orders` | POST | สร้างคำสั่งซื้อ | 🔴 High |
| `/v1/orders/{id}` | GET | รายละเอียดคำสั่งซื้อ | 🔴 High |
| `/v1/orders/{id}/cancel` | POST | ยกเลิกคำสั่งซื้อ | 🟡 Medium |

### 🍔 Food Ordering
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/restaurants` | GET | รายการร้านอาหาร | 🔴 High |
| `/v1/restaurants/{id}` | GET | รายละเอียดร้าน | 🔴 High |
| `/v1/restaurants/{id}/menu` | GET | เมนูร้าน | 🔴 High |
| `/v1/restaurants/nearby` | GET | ร้านใกล้เคียง (GPS) | 🔴 High |
| `/v1/food-orders` | POST | สั่งอาหาร | 🔴 High |
| `/v1/food-orders/{id}` | GET | ติดตามออเดอร์ | 🔴 High |
| `/v1/food-orders/{id}/track` | GET | Real-time tracking | 🔴 High |

### 🚴 Rider / Delivery
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/rider/register` | POST | สมัครเป็นไรเดอร์ | 🔴 High |
| `/v1/rider/status` | PUT | อัพเดทสถานะ | 🔴 High |
| `/v1/rider/orders` | GET | รายการออเดอร์ | 🔴 High |
| `/v1/rider/orders/{id}/accept` | POST | รับงาน | 🔴 High |
| `/v1/rider/orders/{id}/pickup` | POST | รับของแล้ว | 🔴 High |
| `/v1/rider/orders/{id}/deliver` | POST | ส่งแล้ว | 🔴 High |
| `/v1/rider/location` | POST | อัพเดทตำแหน่ง | 🔴 High |
| `/v1/rider/earnings` | GET | รายได้ | 🔴 High |

### 🏨 Hotel Booking
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/hotels` | GET | รายการโรงแรม | 🔴 High |
| `/v1/hotels/{id}` | GET | รายละเอียดโรงแรม | 🔴 High |
| `/v1/hotels/{id}/rooms` | GET | ห้องพัก | 🔴 High |
| `/v1/hotels/search` | GET | ค้นหา (วันที่, ราคา) | 🔴 High |
| `/v1/bookings` | POST | จองห้องพัก | 🔴 High |
| `/v1/bookings` | GET | รายการจอง | 🔴 High |
| `/v1/bookings/{id}` | GET | รายละเอียดการจอง | 🔴 High |
| `/v1/bookings/{id}/cancel` | POST | ยกเลิกการจอง | 🟡 Medium |

### 💰 Wallet / Crypto
| Endpoint | Method | Description | Priority |
|----------|--------|-------------|----------|
| `/v1/wallet/balance` | GET | ยอดเงิน | 🔴 High |
| `/v1/wallet/transactions` | GET | ประวัติธุรกรรม | 🔴 High |
| `/v1/wallet/deposit` | POST | เติมเงิน | 🔴 High |
| `/v1/wallet/withdraw` | POST | ถอนเงิน | 🔴 High |
| `/v1/wallet/transfer` | POST | โอนเงิน | 🔴 High |
| `/v1/crypto/balances` | GET | ยอด Crypto | 🔴 High |
| `/v1/crypto/prices` | GET | ราคา Crypto | 🔴 High |
| `/v1/crypto/send` | POST | ส่ง Crypto | 🔴 High |
| `/v1/crypto/receive` | GET | รับ Crypto (Address) | 🔴 High |
| `/v1/crypto/swap` | POST | แลกเปลี่ยน | 🟡 Medium |

---

## 2️⃣ หน้าจอที่ต้องสร้างเพิ่ม (Mobile App)

### 🔐 Authentication Screens
- [ ] `app/register.tsx` - สมัครสมาชิก
- [ ] `app/forgot-password.tsx` - ลืมรหัสผ่าน
- [ ] `app/verify-otp.tsx` - ยืนยัน OTP

### 🛒 Shopping Screens
- [ ] `app/product/[id].tsx` - รายละเอียดสินค้า
- [ ] `app/cart.tsx` - ตะกร้าสินค้า
- [ ] `app/checkout.tsx` - ชำระเงิน
- [ ] `app/orders.tsx` - รายการคำสั่งซื้อ
- [ ] `app/order/[id].tsx` - รายละเอียดคำสั่งซื้อ

### 🍔 Food Ordering Screens
- [ ] `app/restaurants.tsx` - ร้านอาหาร
- [ ] `app/restaurant/[id].tsx` - รายละเอียดร้าน
- [ ] `app/food-cart.tsx` - ตะกร้าอาหาร
- [ ] `app/food-checkout.tsx` - สั่งอาหาร
- [ ] `app/food-track/[id].tsx` - ติดตามออเดอร์

### 🚴 Rider Screens
- [ ] `app/rider/register.tsx` - สมัครไรเดอร์
- [ ] `app/rider/dashboard.tsx` - หน้าหลักไรเดอร์
- [ ] `app/rider/orders.tsx` - รายการงาน
- [ ] `app/rider/order/[id].tsx` - รายละเอียดงาน
- [ ] `app/rider/earnings.tsx` - รายได้
- [ ] `app/rider/navigation.tsx` - แผนที่นำทาง

### 🏨 Hotel Booking Screens
- [ ] `app/hotels.tsx` - รายการโรงแรม
- [ ] `app/hotel/[id].tsx` - รายละเอียดโรงแรม
- [ ] `app/hotel-booking.tsx` - จองห้องพัก
- [ ] `app/my-bookings.tsx` - รายการจอง

### 💰 Wallet / Crypto Screens
- [ ] `app/wallet/deposit.tsx` - เติมเงิน
- [ ] `app/wallet/withdraw.tsx` - ถอนเงิน
- [ ] `app/wallet/transfer.tsx` - โอนเงิน
- [ ] `app/wallet/transactions.tsx` - ประวัติ
- [ ] `app/crypto/portfolio.tsx` - พอร์ตโฟลิโอ
- [ ] `app/crypto/send.tsx` - ส่ง Crypto
- [ ] `app/crypto/receive.tsx` - รับ Crypto
- [ ] `app/crypto/swap.tsx` - แลกเปลี่ยน

---

## 3️⃣ Features ที่ต้องมีในแต่ละ App

### 🛒 Shopping App Features
```
✅ หมวดหมู่สินค้า
✅ ค้นหาสินค้า (Text, Voice, Image)
✅ รายละเอียดสินค้า (รูป, คำอธิบาย, รีวิว)
✅ ตะกร้าสินค้า
✅ Wishlist / Favorites
✅ ระบบคูปอง / โค้ดส่วนลด
✅ การชำระเงิน (QR, บัตรเครดิต, Wallet)
✅ ติดตามสถานะการจัดส่ง
✅ ระบบรีวิว / Rating
✅ แจ้งเตือน Flash Sale
✅ คอมมิชชั่นจากการแนะนำ
✅ NFC Payment Support
```

### 🚚 Delivery App Features
```
✅ เลือกที่อยู่จัดส่ง (GPS Auto-detect)
✅ เลือกวิธีจัดส่ง (ด่วน, ปกติ, รับเอง)
✅ คำนวณค่าส่ง Real-time
✅ ติดตามพัสดุ Live Map
✅ แจ้งเตือนสถานะ Push Notification
✅ ติดต่อไรเดอร์ (Chat, Call)
✅ ให้ Rating ไรเดอร์
✅ ประวัติการจัดส่ง
✅ ร้องเรียน / Report
```

### 🍔 Food Ordering App Features
```
✅ ค้นหาร้านอาหาร (ประเภท, ระยะทาง)
✅ ร้านใกล้เคียง (GPS)
✅ เมนูร้าน + รูปภาพ
✅ Customization (เพิ่มเส้น, ไม่ใส่ผัก)
✅ ตะกร้าอาหาร (หลายร้าน)
✅ เวลาประมาณการ (ETA)
✅ ติดตามออเดอร์ Real-time Map
✅ ติดต่อร้าน / ไรเดอร์
✅ รีวิวร้านอาหาร
✅ บันทึกร้านโปรด
✅ สั่งซ้ำจากประวัติ
✅ Group Order (สั่งหลายคน)
```

### 🚴 Rider App Features
```
✅ สมัครเป็นไรเดอร์ (KYC)
✅ อัพโหลดเอกสาร (บัตร, ใบขับขี่)
✅ เปิด/ปิดรับงาน
✅ รายการออเดอร์ใกล้เคียง
✅ รับ/ปฏิเสธงาน
✅ Navigation แผนที่
✅ อัพเดทสถานะงาน
✅ ถ่ายรูปยืนยันส่ง
✅ สแกน QR ยืนยัน
✅ รายได้ Real-time
✅ ถอนเงิน
✅ ประวัติงาน / สถิติ
✅ Rating / Feedback
✅ Emergency SOS
```

### 🏨 Hotel Booking App Features
```
✅ ค้นหาโรงแรม (วันที่, จำนวนคน)
✅ Filter (ราคา, ดาว, สิ่งอำนวยความสะดวก)
✅ รูปภาพ Gallery
✅ รายละเอียดห้อง
✅ เปรียบเทียบราคา
✅ ปฏิทินว่าง
✅ จองทันที / จองพร้อมจ่าย
✅ ยืนยันการจอง
✅ E-Voucher
✅ Check-in Online
✅ NFC Room Key
✅ บริการเพิ่มเติม (อาหารเช้า, สปา)
✅ รีวิวที่พัก
✅ แผนที่ / ทิศทาง
```

### 💰 Crypto Wallet App Features
```
✅ ยอดคงเหลือ Multi-chain
✅ ราคา Real-time
✅ กราฟราคา (1D, 1W, 1M, 1Y)
✅ ส่ง Crypto (Address / QR)
✅ รับ Crypto (Generate QR)
✅ Swap Token
✅ NFT Gallery
✅ DeFi Integration
✅ Web3 dApp Browser
✅ WalletConnect Support
✅ Hardware Wallet Support
✅ Multi-sig Support
✅ Transaction History
✅ Gas Fee Estimation
✅ Portfolio Analytics
✅ Price Alerts
✅ Staking
```

---

## 4️⃣ Hardware / Device Features

### 📷 Camera Features
```
✅ สแกน QR Code (Payment, Product, Login)
✅ สแกน Barcode (Product lookup)
✅ ถ่ายรูปสินค้า (Image Search)
✅ ถ่ายรูปยืนยันส่ง (Rider)
✅ OCR อ่านข้อความ
✅ Face ID / Recognition
✅ AR Preview (วางสินค้าในห้อง)
```

### 📶 NFC Features
```
✅ NFC Payment (Tap to Pay)
✅ NFC Room Key (Hotel)
✅ NFC Product Info (แตะดูข้อมูล)
✅ NFC Loyalty Card
✅ NFC Transfer (Tap to Transfer)
✅ NFC Hardware Wallet
```

### 🌐 Web3 / dApp Features
```
✅ WalletConnect Integration
✅ dApp Browser
✅ Smart Contract Interaction
✅ Sign Transaction
✅ Sign Message
✅ ENS Support
✅ Multi-chain Support
✅ Token Approve/Revoke
```

---

## 5️⃣ Theme & Design

### 🎨 Lava Lamp Theme
```css
/* Colors */
Primary: #FF6B6B (Coral Red)
Secondary: #4ECDC4 (Teal)
Accent: #FFE66D (Yellow)
Gradient 1: #FF6B6B → #FF8E53 → #FFE66D
Gradient 2: #4ECDC4 → #556270
Background: #0F0F23 (Dark) / #FFFFFF (Light)

/* Effects */
- Animated Gradient Background
- Blob Animation
- Glassmorphism Cards
- Glow Effects
- Smooth Transitions
- Particle Effects
```

### 🛒 Shopping Theme Colors
```css
/* Primary Palette */
Orange: #FF6B35 (CTA Buttons)
Purple: #7B2CBF (Highlights)
Teal: #2EC4B6 (Success)
Pink: #FF6B6B (Sale/Discount)
Gold: #FFD700 (Premium)

/* Background */
Dark: #1A1A2E → #16213E
Light: #F8F9FA → #FFFFFF
```

---

## 6️⃣ Dependencies ที่ต้องเพิ่ม

```json
{
  "dependencies": {
    // Camera & QR
    "expo-camera": "~15.0.0",
    "expo-barcode-scanner": "~13.0.0",

    // NFC
    "react-native-nfc-manager": "^3.14.0",

    // Maps & Location
    "react-native-maps": "^1.8.0",
    "expo-location": "~17.0.0",

    // Web3
    "@walletconnect/react-native-compat": "^2.10.0",
    "@web3modal/wagmi-react-native": "^1.0.0",
    "ethers": "^6.9.0",
    "viem": "^1.21.0",

    // Animation
    "lottie-react-native": "^6.4.0",
    "@shopify/react-native-skia": "^0.1.0",

    // Payments
    "@stripe/stripe-react-native": "^0.35.0"
  }
}
```

---

## 7️⃣ Implementation Priority

### Phase 1 - Core (Week 1-2)
1. ✅ API: Register, Profile, Products
2. ✅ Screens: Register, Product Detail, Cart
3. ✅ Theme: Lava Lamp + Shopping Colors

### Phase 2 - E-commerce (Week 3-4)
1. Orders, Checkout, Payment
2. Order tracking
3. Reviews & Ratings

### Phase 3 - Food & Delivery (Week 5-6)
1. Restaurant listing
2. Food ordering
3. Rider app basics

### Phase 4 - Hotel & Booking (Week 7-8)
1. Hotel search
2. Room booking
3. E-voucher

### Phase 5 - Crypto & Web3 (Week 9-10)
1. Wallet integration
2. Web3 dApp browser
3. NFC support

---

## 📝 Notes

- ทุก Feature ต้องรองรับ **Offline Mode**
- UI ต้องรองรับ **Dark/Light Mode**
- ต้องมี **Thai/English** language support
- **Real-time updates** ใช้ WebSocket
- **Push Notifications** ทุก event สำคัญ

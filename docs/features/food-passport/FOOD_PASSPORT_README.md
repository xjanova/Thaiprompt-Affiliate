# 🌱 Food Passport System

> **ระบบพาสปอร์ตอาหาร**: ติดตามและตรวจสอบคุณภาพอาหารตั้งแต่แหล่งผลิตจนถึงผู้บริโภค พร้อมระบบ Carbon Credit

[![Status](https://img.shields.io/badge/status-planning-yellow)](https://github.com)
[![License](https://img.shields.io/badge/license-MIT-blue)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/laravel-11.x-red)](https://laravel.com)
[![Blockchain](https://img.shields.io/badge/blockchain-enabled-green)](https://ethereum.org)

---

## 📚 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [Documentation](#-documentation)
- [Architecture](#-architecture)
- [Getting Started](#-getting-started)
- [User Guides](#-user-guides)
- [API Reference](#-api-reference)
- [Contributing](#-contributing)

---

## 🎯 Overview

Food Passport System เป็นแพลตฟอร์มที่ช่วยให้เกษตรกร ผู้ประกอบการ และผู้บริโภค สามารถ:

- ✅ **ติดตามที่มา** ของอาหารได้ตั้งแต่ฟาร์มจนถึงโต๊ะอาหาร (Farm to Fork)
- ✅ **ตรวจสอบคุณภาพ** ในทุกขั้นตอนการผลิตและจัดจำหน่าย
- ✅ **คำนวณ Carbon Footprint** และได้รับรางวัล Carbon Credit
- ✅ **เชื่อมต่อ Blockchain** เพื่อความโปร่งใสและไม่สามารถแก้ไขได้

### Vision

> "สร้างระบบอาหารที่โปร่งใส ปลอดภัย และยั่งยืน เพื่อสุขภาพที่ดีของประชาชนและสิ่งแวดล้อม"

### Target Users

| User Type | Description |
|-----------|-------------|
| 👨‍🌾 **เกษตรกร** | ผู้ผลิตอาหารที่ต้องการสร้างความน่าเชื่อถือและเพิ่มรายได้ |
| 🔬 **ผู้ตรวจสอบ** | นักวิทยาศาสตร์ที่รับรองคุณภาพอาหาร |
| 🚚 **ผู้ขนส่ง/จัดจำหน่าย** | ผู้รับผิดชอบในการเคลื่อนย้ายอาหารอย่างปลอดภัย |
| 🏪 **ร้านค้า/ซูเปอร์มาร์เก็ต** | จุดขายปลีกที่ต้องการสินค้าคุณภาพ |
| 👥 **ผู้บริโภค** | ผู้ที่ใส่ใจสุขภาพและต้องการทราบที่มาของอาหาร |
| 💼 **นักธุรกิจ Carbon** | ผู้ซื้อขาย Carbon Credit |

---

## ✨ Key Features

### 1. 📍 Traceability System

```
🌱 Farm → 🚚 Transport → 🏭 Processing → 🚚 Distribution → 🏪 Retail → 👥 Consumer
```

- ติดตามเส้นทางการเดินทางของอาหารทุกขั้นตอน
- บันทึกข้อมูลผู้รับผิดชอบในแต่ละจุด
- แสดง Timeline แบบ real-time
- รองรับ QR Code และ NFC scanning

### 2. ✅ Quality Control

- ระบบตรวจสอบคุณภาพในทุกขั้นตอน
- รองรับมาตรฐานสากล (ISO 22000, HACCP, GMP, GAP)
- ออกใบรับรองอิเล็กทรอนิกส์
- Test results และ lab reports
- Digital signature verification

### 3. 🌍 Carbon Footprint Tracking

**การคำนวณ Carbon Emission:**

```
Total Carbon = Farming + Processing + Transportation +
               Storage + Packaging + Retail
```

**Carbon Score Grading:**

| Grade | Emission | Description |
|-------|----------|-------------|
| 🌟 A+ | Net Zero | คาร์บอนเป็นกลางหรือติดลบ |
| 🟢 A | < 1 kg | ต่ำมาก (ยอดเยี่ยม) |
| 🟡 B | 1-3 kg | ต่ำ (ดี) |
| 🟠 C | 3-5 kg | ปานกลาง |
| 🔴 D | 5-10 kg | สูง |
| ⚫ E | > 10 kg | สูงมาก |

### 4. 💰 Carbon Credit System

```
Baseline Emission - Actual Emission = Carbon Saved
                                    ↓
                            Carbon Credit Earned
                                    ↓
                            Can Trade or Sell
```

**Benefits:**
- เกษตรกรได้รับรางวัลจากการลด carbon
- ซื้อขายได้ใน marketplace
- เชื่อมต่อกับตลาด carbon โลก
- Blockchain-verified credits

### 5. 🔗 Blockchain Integration

- บันทึกข้อมูลสำคัญบน Blockchain (Ethereum, BSC, Polygon)
- ใช้ IPFS เก็บเอกสาร/รูปภาพ
- Smart Contract สำหรับ carbon credit
- NFT certificates
- ไม่สามารถปลอมแปลงได้

---

## 📖 Documentation

### Planning & Design Documents

| Document | Description | Status |
|----------|-------------|--------|
| [📋 Project Plan](./food-passport-plan.md) | แผนโปรเจคโดยละเอียด รวม architecture, database schema, API design | ✅ Complete |
| [📝 Use Cases](./food-passport-use-cases.md) | Use cases, user stories, และ user journeys | ✅ Complete |
| [🏗️ Technical Specs](./food-passport-technical-specs.md) | รายละเอียดทางเทคนิคสำหรับ developers | 🔄 In Progress |
| [📊 API Documentation](./food-passport-api-docs.md) | REST API endpoints และ examples | ⏳ Planned |

### User Guides

| Guide | For | Status |
|-------|-----|--------|
| 👨‍🌾 Farmer Guide | เกษตรกร | ⏳ Planned |
| 🔬 Inspector Guide | ผู้ตรวจสอบคุณภาพ | ⏳ Planned |
| 👥 Consumer Guide | ผู้บริโภค | ⏳ Planned |
| 🔧 Admin Manual | ผู้ดูแลระบบ | ⏳ Planned |

---

## 🏗️ Architecture

### High-Level System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │   Web    │  │  Mobile  │  │   POS    │             │
│  │  (Vue)   │  │ (.NET)   │  │ Terminal │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└────────────────────┬────────────────────────────────────┘
                     │ REST API
┌────────────────────┴────────────────────────────────────┐
│              Application Layer (Laravel)                 │
│  ┌──────────────────────────────────────────────────┐   │
│  │            Food Passport Services                │   │
│  │  • Traceability  • Quality Control              │   │
│  │  • Carbon Credit • Blockchain Record            │   │
│  └──────────────────────────────────────────────────┘   │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────┴────────────────────────────────────┐
│                  Data Layer                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │  MySQL   │  │  Redis   │  │  IPFS    │             │
│  │(Relational)│(Cache)    │  │ (Docs)   │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────┴────────────────────────────────────┐
│              Blockchain Layer                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │ Ethereum │  │   BSC    │  │ Polygon  │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└─────────────────────────────────────────────────────────┘
```

### Database Schema

**Core Tables:**
- `food_products` - ผลิตภัณฑ์อาหาร
- `product_journey` - เส้นทางการเดินทาง
- `quality_checkpoints` - จุดตรวจสอบคุณภาพ
- `carbon_footprint_records` - บันทึก carbon
- `carbon_credits` - carbon credits
- `food_certifications` - ใบรับรอง
- `stakeholder_roles` - บทบาทผู้มีส่วนร่วม
- `consumer_scans` - การสแกนของผู้บริโภค

📖 [ดูรายละเอียด Database Schema](./food-passport-plan.md#-database-schema-design)

---

## 🚀 Getting Started

### Prerequisites

```bash
# Server Requirements
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Node.js 18+
- Composer 2.x

# Blockchain
- Web3 wallet (MetaMask)
- RPC endpoints (Infura/Alchemy)

# Optional
- IPFS node
- Redis 6+
```

### Installation

```bash
# 1. Clone repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Setup database
php artisan migrate
php artisan db:seed --class=FoodPassportSeeder

# 5. Build assets
npm run build

# 6. Start server
php artisan serve
```

### Configuration

```env
# Food Passport Settings
FOOD_PASSPORT_ENABLED=true
FOOD_PASSPORT_QR_DOMAIN=https://foodpassport.example.com

# Blockchain
BLOCKCHAIN_NETWORK=bsc-testnet
BLOCKCHAIN_RPC_URL=https://data-seed-prebsc-1-s1.binance.org:8545/
FOOD_PASSPORT_CONTRACT_ADDRESS=0x...

# IPFS
IPFS_ENABLED=true
IPFS_HOST=127.0.0.1
IPFS_PORT=5001

# Carbon Credit
CARBON_CREDIT_ENABLED=true
CARBON_CREDIT_MARKETPLACE=true
```

---

## 👥 User Guides

### For Farmers (เกษตรกร)

#### Quick Start: ลงทะเบียนผลผลิตแรก

1. **สมัครสมาชิก**
   ```
   เข้าแอพ → สมัครสมาชิก → ยืนยัน OTP
   → ยื่นเอกสารฟาร์ม → รอการอนุมัติ (24 ชม.)
   ```

2. **เพิ่มผลผลิต**
   ```
   Dashboard → "+" ลงทะเบียนผลผลิต
   → กรอกข้อมูล → ถ่ายรูป → ยืนยัน
   → ได้ QR Code
   ```

3. **พิมพ์ QR Code**
   ```
   ดาวน์โหลด PDF → พิมพ์สติกเกอร์
   → ติดบนผลิตภัณฑ์
   ```

4. **ติดตามผลิตภัณฑ์**
   ```
   Dashboard → เลือกผลิตภัณฑ์
   → ดูเส้นทาง, คุณภาพ, carbon score
   ```

5. **รับ Carbon Credit**
   ```
   เมื่อสินค้าขายออก + ผ่านการตรวจสอบ
   → ระบบคำนวณ carbon saved
   → รับ carbon credit อัตโนมัติ
   ```

📱 [ดาวน์โหลดคู่มือเกษตรกร PDF](./guides/farmer-guide.pdf)

### For Consumers (ผู้บริโภค)

#### How to Scan & Verify

1. **สแกน QR Code**
   ```
   เปิดกล้องมือถือ → สแกน QR บนผลิตภัณฑ์
   → เว็บเปิดอัตโนมัติ
   ```

2. **ดูข้อมูลผลิตภัณฑ์**
   - 🌱 ฟาร์มต้นทาง
   - 📍 เส้นทางการเดินทาง
   - ✅ ผลการตรวจสอบคุณภาพ
   - 🌍 Carbon Score
   - 🏆 ใบรับรอง

3. **ให้คะแนนและรีวิว**
   ```
   ⭐ ให้คะแนน 1-5 ดาว
   💬 เขียนความคิดเห็น
   📸 แนบรูปภาพ (ถ้ามี)
   ```

---

## 🔌 API Reference

### Authentication

```bash
# Login
POST /api/v1/login
Content-Type: application/json

{
  "email": "farmer@example.com",
  "password": "password123"
}

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {...}
}
```

### Food Products

```bash
# Create new food product
POST /api/v1/food-passport/products
Authorization: Bearer {token}

{
  "product_type": "vegetables",
  "variety": "Romaine Lettuce",
  "quantity": 500,
  "unit": "kg",
  "harvest_date": "2025-11-10",
  "certifications": ["organic", "haccp"]
}

# Response
{
  "id": 1234,
  "food_passport_id": "FP-2025-001234",
  "qr_code_url": "https://api.example.com/qr/FP-2025-001234.png",
  "blockchain_hash": "0x7a3f8b2c..."
}
```

### Journey Tracking

```bash
# Add journey stage
POST /api/v1/food-passport/journey/stages
Authorization: Bearer {token}

{
  "food_product_id": 1234,
  "stage": "transport",
  "location_name": "Bangkok Distribution Center",
  "transport_method": "truck",
  "distance_km": 150,
  "fuel_type": "diesel",
  "fuel_consumption": 45
}
```

### Carbon Footprint

```bash
# Get carbon footprint
GET /api/v1/food-passport/carbon/products/1234/footprint

# Response
{
  "food_product_id": 1234,
  "total_carbon_footprint": 16.7,
  "carbon_score": "A+",
  "breakdown": [
    {"category": "farming", "emission": 5.2},
    {"category": "transport", "emission": 8.5},
    {"category": "processing", "emission": 3.0}
  ],
  "baseline_emission": 30.0,
  "reduction_percentage": 44.3
}
```

📖 [Full API Documentation](./food-passport-api-docs.md)

---

## 🎓 Examples & Tutorials

### Example 1: Complete Product Journey

```php
use App\Services\FoodTraceabilityService;
use App\Services\QualityControlService;
use App\Services\CarbonCreditService;

// 1. Create food product
$traceability = app(FoodTraceabilityService::class);
$product = $traceability->createFoodProduct([
    'product_type' => 'vegetables',
    'variety' => 'Romaine Lettuce',
    'quantity' => 500,
    'unit' => 'kg',
    'harvest_date' => now(),
    'farmer_id' => auth()->id(),
]);

// 2. Add journey stages
$traceability->addJourneyStage($product->id, [
    'stage' => 'transport',
    'location_name' => 'Distribution Center',
    'transport_method' => 'truck',
    'distance_km' => 150,
]);

// 3. Quality checkpoint
$quality = app(QualityControlService::class);
$checkpoint = $quality->createCheckpoint([
    'food_product_id' => $product->id,
    'checkpoint_type' => 'laboratory',
    'test_parameters' => [
        ['name' => 'Pesticide', 'value' => 0.001, 'status' => 'pass'],
    ],
]);

// 4. Calculate carbon and issue credit
$carbonService = app(CarbonCreditService::class);
$footprint = $carbonService->calculateFootprint($product->id);
$credit = $carbonService->issueCarbonCredit(
    auth()->id(),
    $product->id
);
```

### Example 2: Consumer Scan

```javascript
// Frontend: Scan QR Code
async function scanProduct(passportId) {
  const response = await fetch(
    `/api/v1/food-passport/public/${passportId}/story`
  );
  const data = await response.json();

  // Display product info
  displayProductStory(data);

  // Track scan
  await fetch(`/api/v1/food-passport/scan/${passportId}/track`, {
    method: 'POST',
    body: JSON.stringify({
      location: await getCurrentLocation(),
      device_info: getDeviceInfo()
    })
  });
}
```

---

## 📊 Dashboard & Analytics

### Farmer Dashboard

**Key Metrics:**
- 📦 Total Products
- ⭐ Average Quality Score
- 🌍 Average Carbon Score
- 💰 Carbon Credits Earned
- 👥 Consumer Scans
- 💵 Revenue (Products + Credits)

**Charts:**
- Quality trend (6 months)
- Carbon savings vs baseline
- Revenue breakdown
- Product distribution by type

### Admin Dashboard

**System Metrics:**
- Total farmers registered
- Total products tracked
- Total quality checkpoints
- Total carbon credits issued
- Consumer engagement rate
- System health status

---

## 🛠️ Development

### Project Structure

```
app/
├── Models/
│   ├── FoodProduct.php
│   ├── ProductJourney.php
│   ├── QualityCheckpoint.php
│   ├── CarbonFootprintRecord.php
│   └── CarbonCredit.php
├── Services/
│   ├── FoodTraceabilityService.php
│   ├── QualityControlService.php
│   ├── CarbonCreditService.php
│   ├── BlockchainRecordService.php
│   └── CertificationService.php
├── Http/Controllers/
│   ├── FoodPassportController.php
│   ├── TraceabilityController.php
│   ├── QualityController.php
│   └── CarbonCreditController.php
└── Observers/
    ├── FoodProductObserver.php
    └── QualityCheckpointObserver.php
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=FoodPassport

# With coverage
php artisan test --coverage
```

### Code Style

```bash
# Format code
./vendor/bin/pint

# Check style
./vendor/bin/pint --test
```

---

## 🤝 Contributing

เรายินดีรับ contributions! โปรดอ่าน [Contributing Guidelines](./CONTRIBUTING.md) ก่อน

### Development Workflow

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📅 Roadmap

### Phase 1: Foundation (Weeks 1-3) ✅
- [x] Database design
- [x] Core models
- [x] Basic API endpoints
- [x] Traceability service

### Phase 2: Quality Control (Weeks 4-5) 🔄
- [ ] Quality checkpoint recording
- [ ] Certification service
- [ ] Inspector interface

### Phase 3: Carbon System (Weeks 6-8) ⏳
- [ ] Carbon calculation
- [ ] Carbon credit issuance
- [ ] Marketplace

### Phase 4: Blockchain (Weeks 9-11) ⏳
- [ ] Smart contracts
- [ ] IPFS integration
- [ ] NFT certificates

### Phase 5: Consumer Interface (Weeks 12-13) ⏳
- [ ] QR code scanning
- [ ] Product story page
- [ ] Mobile app

### Phase 6: Launch (Week 18) ⏳
- [ ] Production deployment
- [ ] User training
- [ ] Marketing campaign

---

## 📞 Support

### Need Help?

- 📧 Email: support@foodpassport.example.com
- 💬 LINE Official: @foodpassport
- 📱 Hotline: 02-XXX-XXXX
- 🌐 Website: https://foodpassport.example.com

### Report Issues

🐛 [Report a Bug](https://github.com/xjanova/Thaiprompt-Affiliate/issues/new?template=bug_report.md)
✨ [Request Feature](https://github.com/xjanova/Thaiprompt-Affiliate/issues/new?template=feature_request.md)

---

## 📄 License

This project is licensed under the MIT License - see [LICENSE](../LICENSE) file.

---

## 🙏 Acknowledgments

- เกษตรกรไทยที่ร่วมทดสอบระบบ
- หน่วยงานภาครัฐที่ให้การสนับสนุน
- ชุมชน Open Source

---

## 🌟 Quick Links

| Link | Description |
|------|-------------|
| [📋 Full Plan](./food-passport-plan.md) | แผนโปรเจคฉบับเต็ม |
| [📝 Use Cases](./food-passport-use-cases.md) | Use cases และ user stories |
| [🔧 Tech Specs](./food-passport-technical-specs.md) | เอกสารทางเทคนิค |
| [📖 API Docs](./food-passport-api-docs.md) | API documentation |
| [🎓 Tutorials](./tutorials/) | บทเรียนและ examples |

---

<div align="center">

**Made with ❤️ for Thai Farmers and Sustainable Food System**

[เริ่มต้นใช้งาน](#-getting-started) • [ดูเอกสาร](#-documentation) • [ติดต่อเรา](#-support)

</div>

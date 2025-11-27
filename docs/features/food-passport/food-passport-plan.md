# 📋 Food Passport System - Project Plan
**โปรเจค: ระบบพาสปอร์ตอาหาร (Food Passport)**
**วันที่สร้าง:** 2025-11-13
**สถานะ:** Planning Phase

---

## 🎯 Executive Summary

ระบบ Food Passport เป็นแพลตฟอร์มที่ติดตามและตรวจสอบคุณภาพอาหารตั้งแต่แหล่งผลิต (Farm) จนถึงผู้บริโภค (Fork) โดยใช้เทคโนโลยี Blockchain เพื่อความโปร่งใส พร้อมบูรณาการระบบคำนวณ Carbon Credit เพื่อส่งเสริมการผลิตอาหารที่เป็นมิตรกับสิ่งแวดล้อม

### ความสามารถหลัก
- ✅ **Traceability**: ติดตามที่มาของอาหารทุกขั้นตอนการผลิต
- ✅ **Quality Assurance**: ตรวจสอบและรับรองคุณภาพในแต่ละจุด
- ✅ **Carbon Footprint Tracking**: คำนวณและติดตาม Carbon Footprint ของผลิตภัณฑ์
- ✅ **Blockchain Integration**: บันทึกข้อมูลบน Blockchain เพื่อความโปร่งใสและไม่สามารถแก้ไขได้
- ✅ **Certification & Rewards**: ให้รางวัลแก่ผู้ผลิตที่เป็นมิตรกับสิ่งแวดล้อม

---

## 🏗️ System Architecture

### 1. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Food Passport Platform                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌───────────────┐  ┌──────────────┐  ┌────────────────────┐   │
│  │  Farm/Origin  │→ │  Processing  │→ │  Distribution &    │   │
│  │   Management  │  │  & Quality   │  │   Retail           │   │
│  └───────────────┘  └──────────────┘  └────────────────────┘   │
│         ↓                   ↓                    ↓               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │         Blockchain Layer (Immutable Records)             │   │
│  │  • Product Journey • Quality Checks • Carbon Data        │   │
│  └──────────────────────────────────────────────────────────┘   │
│         ↓                   ↓                    ↓               │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐   │
│  │  Traceability│  │    Quality   │  │  Carbon Credit     │   │
│  │    Engine    │  │   Control    │  │   Calculator       │   │
│  └──────────────┘  └──────────────┘  └────────────────────┘   │
│         ↓                   ↓                    ↓               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              Consumer Interface (QR/NFC)                  │   │
│  │  • Product Story • Quality Certifications • Carbon Score │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2. Technology Stack Integration

```
Laravel 11 Backend
├── Models (Food Passport Entities)
│   ├── FoodProduct
│   ├── ProductJourney
│   ├── QualityCheckpoint
│   ├── CarbonFootprint
│   └── Certification
│
├── Services (Business Logic)
│   ├── FoodTraceabilityService
│   ├── QualityControlService
│   ├── CarbonCreditService
│   ├── BlockchainRecordService
│   └── CertificationService
│
├── Controllers (API Endpoints)
│   ├── FoodPassportController
│   ├── TraceabilityController
│   ├── QualityController
│   └── CarbonCreditController
│
└── Blockchain Integration
    ├── Existing: CryptoWallet, CryptoTransaction
    ├── New: FoodPassportContract (Smart Contract)
    └── IPFS Integration (for documents/certificates)
```

---

## 📊 Database Schema Design

### Core Tables

#### 1. `food_products`
อาหารที่อยู่ในระบบ Food Passport

```sql
CREATE TABLE food_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED, -- Link to existing products table
    food_passport_id VARCHAR(100) UNIQUE NOT NULL, -- Unique passport ID (QR code)

    -- Origin Information
    farm_name VARCHAR(255),
    farm_location JSON, -- {lat, lng, address}
    farmer_id BIGINT UNSIGNED, -- Link to users table

    -- Product Details
    product_type VARCHAR(100), -- vegetables, fruits, meat, seafood, etc.
    variety VARCHAR(100), -- e.g., "Organic Rice", "Free-Range Chicken"
    harvest_date DATE,
    batch_number VARCHAR(50),
    estimated_quantity DECIMAL(10,2),
    unit VARCHAR(20), -- kg, pieces, liters

    -- Certifications
    certifications JSON, -- ["organic", "gmp", "haccp", "fairtrade"]

    -- Status
    current_stage ENUM('farm', 'processing', 'distribution', 'retail', 'consumed'),
    blockchain_hash VARCHAR(66), -- Transaction hash on blockchain
    ipfs_hash VARCHAR(100), -- IPFS hash for documents

    -- Carbon Data (summary)
    total_carbon_footprint DECIMAL(10,4), -- kg CO2
    carbon_score VARCHAR(5), -- A+, A, B, C, D, E

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (farmer_id) REFERENCES users(id),
    INDEX idx_passport_id (food_passport_id),
    INDEX idx_product_type (product_type),
    INDEX idx_current_stage (current_stage)
);
```

#### 2. `product_journey`
ติดตามเส้นทางการเดินทางของผลิตภัณฑ์

```sql
CREATE TABLE product_journey (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    food_product_id BIGINT UNSIGNED NOT NULL,

    -- Stage Information
    stage ENUM('farm', 'processing', 'storage', 'distribution', 'retail', 'transport'),
    stage_name VARCHAR(100), -- Human-readable name
    sequence_order INT, -- Order in journey

    -- Location & Time
    location_name VARCHAR(255),
    location_coordinates JSON, -- {lat, lng}
    arrived_at TIMESTAMP,
    departed_at TIMESTAMP NULL,
    duration_hours DECIMAL(8,2), -- Auto-calculated

    -- Handler Information
    handler_id BIGINT UNSIGNED, -- User responsible at this stage
    handler_name VARCHAR(255),
    handler_type ENUM('farmer', 'processor', 'distributor', 'retailer', 'transporter'),
    organization VARCHAR(255),

    -- Environmental Data
    temperature_range JSON, -- {min, max, avg} in Celsius
    humidity_range JSON, -- {min, max, avg} in %
    storage_conditions TEXT,

    -- Transportation
    transport_method VARCHAR(50), -- truck, ship, plane, train
    distance_km DECIMAL(10,2),
    fuel_type VARCHAR(50),

    -- Carbon Impact
    stage_carbon_emission DECIMAL(10,4), -- kg CO2 for this stage

    -- Quality Status
    quality_status ENUM('excellent', 'good', 'acceptable', 'poor'),

    -- Documentation
    notes TEXT,
    photos JSON, -- Array of photo URLs
    documents JSON, -- Array of document URLs

    -- Blockchain
    blockchain_hash VARCHAR(66),
    ipfs_hash VARCHAR(100),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (food_product_id) REFERENCES food_products(id) ON DELETE CASCADE,
    FOREIGN KEY (handler_id) REFERENCES users(id),
    INDEX idx_stage (stage),
    INDEX idx_food_product (food_product_id, sequence_order)
);
```

#### 3. `quality_checkpoints`
จุดตรวจสอบคุณภาพในแต่ละขั้นตอน

```sql
CREATE TABLE quality_checkpoints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    food_product_id BIGINT UNSIGNED NOT NULL,
    journey_stage_id BIGINT UNSIGNED, -- Link to product_journey

    -- Checkpoint Information
    checkpoint_type ENUM('visual', 'laboratory', 'sensor', 'certification', 'audit'),
    checkpoint_name VARCHAR(255),
    checked_at TIMESTAMP,

    -- Inspector
    inspector_id BIGINT UNSIGNED,
    inspector_name VARCHAR(255),
    inspector_license VARCHAR(100), -- Professional license number
    organization VARCHAR(255),

    -- Test Results
    test_parameters JSON, -- [{name, value, unit, standard, status}]
    /*
    Example:
    [
        {"name": "Pesticide Residue", "value": 0.001, "unit": "mg/kg", "standard": 0.01, "status": "pass"},
        {"name": "Heavy Metals", "value": 0.5, "unit": "ppm", "standard": 1.0, "status": "pass"},
        {"name": "Bacterial Count", "value": 100, "unit": "CFU/g", "standard": 1000, "status": "pass"}
    ]
    */

    overall_result ENUM('pass', 'conditional_pass', 'fail'),
    pass_score DECIMAL(5,2), -- Percentage score

    -- Standards & Certifications
    standards_applied JSON, -- ["ISO 22000", "HACCP", "GMP"]
    certification_issued BOOLEAN DEFAULT FALSE,
    certificate_number VARCHAR(100),
    certificate_expiry DATE,

    -- Documentation
    test_report_url VARCHAR(500),
    photos JSON,
    notes TEXT,

    -- Corrective Actions (if failed)
    corrective_actions TEXT,
    retest_required BOOLEAN DEFAULT FALSE,
    retest_checkpoint_id BIGINT UNSIGNED NULL, -- Link to retest

    -- Blockchain
    blockchain_hash VARCHAR(66),
    ipfs_hash VARCHAR(100),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (food_product_id) REFERENCES food_products(id) ON DELETE CASCADE,
    FOREIGN KEY (journey_stage_id) REFERENCES product_journey(id),
    FOREIGN KEY (inspector_id) REFERENCES users(id),
    FOREIGN KEY (retest_checkpoint_id) REFERENCES quality_checkpoints(id),
    INDEX idx_result (overall_result),
    INDEX idx_checkpoint_type (checkpoint_type)
);
```

#### 4. `carbon_footprint_records`
บันทึกรายละเอียด Carbon Footprint

```sql
CREATE TABLE carbon_footprint_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    food_product_id BIGINT UNSIGNED NOT NULL,
    journey_stage_id BIGINT UNSIGNED, -- Which stage this emission is from

    -- Emission Source
    emission_category ENUM('farming', 'processing', 'transportation', 'storage', 'packaging', 'retail', 'waste'),
    emission_source VARCHAR(255), -- Specific source description

    -- Calculation Data
    activity_data JSON, -- Raw data used for calculation
    /*
    Example for transportation:
    {
        "distance_km": 150,
        "vehicle_type": "diesel_truck",
        "load_weight_kg": 5000,
        "fuel_consumption_liters": 45
    }
    */

    emission_factor DECIMAL(10,6), -- kg CO2 per unit
    emission_factor_source VARCHAR(255), -- e.g., "IPCC 2023", "Thai EPA"

    -- Results
    co2_emission DECIMAL(10,4), -- kg CO2
    ch4_emission DECIMAL(10,4), -- kg CH4 (methane)
    n2o_emission DECIMAL(10,4), -- kg N2O (nitrous oxide)
    total_co2_equivalent DECIMAL(10,4), -- Total in CO2 equivalent

    -- Credits & Offsets
    carbon_offset_applied DECIMAL(10,4), -- kg CO2
    offset_source VARCHAR(255), -- e.g., "Solar power", "Reforestation"
    net_emission DECIMAL(10,4), -- After offsets

    -- Calculation Metadata
    calculation_method VARCHAR(100),
    calculation_date TIMESTAMP,
    verified_by_id BIGINT UNSIGNED, -- Verifier user
    verification_status ENUM('pending', 'verified', 'disputed'),

    -- Documentation
    supporting_documents JSON,
    notes TEXT,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (food_product_id) REFERENCES food_products(id) ON DELETE CASCADE,
    FOREIGN KEY (journey_stage_id) REFERENCES product_journey(id),
    FOREIGN KEY (verified_by_id) REFERENCES users(id),
    INDEX idx_emission_category (emission_category),
    INDEX idx_food_product (food_product_id)
);
```

#### 5. `carbon_credits`
Carbon Credit ที่ได้รับจากการผลิตที่เป็นมิตรกับสิ่งแวดล้อม

```sql
CREATE TABLE carbon_credits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Owner
    user_id BIGINT UNSIGNED NOT NULL, -- Farmer or producer
    food_product_id BIGINT UNSIGNED, -- Specific product that earned credits

    -- Credit Details
    credit_amount DECIMAL(10,4), -- Amount of carbon credits (in tons CO2)
    credit_type ENUM('reduction', 'sequestration', 'avoidance', 'removal'),

    -- Earning Criteria
    baseline_emission DECIMAL(10,4), -- Standard emission for this product type
    actual_emission DECIMAL(10,4), -- Actual emission achieved
    reduction_percentage DECIMAL(5,2), -- % reduction

    -- Valuation
    credit_value_per_ton DECIMAL(10,2), -- THB per ton CO2
    total_value DECIMAL(12,2), -- Total value in THB

    -- Validity
    issued_date DATE,
    expiry_date DATE,
    status ENUM('active', 'traded', 'retired', 'expired'),

    -- Trading
    tradeable BOOLEAN DEFAULT TRUE,
    traded_to_user_id BIGINT UNSIGNED NULL,
    traded_at TIMESTAMP NULL,
    trade_price DECIMAL(12,2),

    -- Verification
    verified_by VARCHAR(255), -- Verification organization
    verification_standard VARCHAR(100), -- e.g., "VCS", "Gold Standard"
    certificate_number VARCHAR(100),

    -- Blockchain
    blockchain_hash VARCHAR(66),
    token_id VARCHAR(100), -- If tokenized as NFT

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (food_product_id) REFERENCES food_products(id),
    FOREIGN KEY (traded_to_user_id) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
);
```

#### 6. `food_certifications`
ใบรับรอง/เกียรติบัตรต่างๆ

```sql
CREATE TABLE food_certifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    food_product_id BIGINT UNSIGNED NOT NULL,

    -- Certification Type
    certification_type VARCHAR(100), -- e.g., "Organic", "Fair Trade", "GMP", "HACCP"
    certification_name VARCHAR(255),
    certifying_body VARCHAR(255), -- Organization that issued

    -- Certificate Details
    certificate_number VARCHAR(100) UNIQUE,
    issued_date DATE,
    expiry_date DATE,
    status ENUM('active', 'expired', 'revoked', 'suspended'),

    -- Scope
    scope TEXT, -- What this certification covers
    standards_version VARCHAR(50), -- e.g., "ISO 22000:2018"

    -- Documentation
    certificate_url VARCHAR(500), -- PDF or image of certificate
    audit_report_url VARCHAR(500),

    -- Verification
    verification_code VARCHAR(100), -- For public verification
    qr_code_url VARCHAR(500),

    -- Blockchain
    blockchain_hash VARCHAR(66),
    ipfs_hash VARCHAR(100),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (food_product_id) REFERENCES food_products(id) ON DELETE CASCADE,
    INDEX idx_type (certification_type),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry_date)
);
```

#### 7. `stakeholder_roles`
ผู้มีส่วนร่วมในห่วงโซ่อาหาร

```sql
CREATE TABLE stakeholder_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    -- Role Information
    role_type ENUM('farmer', 'processor', 'quality_inspector', 'distributor', 'retailer', 'certifier', 'consumer'),
    organization_name VARCHAR(255),
    license_number VARCHAR(100),

    -- Credentials
    certifications JSON, -- Professional certifications
    verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    verified_by_id BIGINT UNSIGNED,

    -- Location
    operating_location JSON, -- {address, coordinates}
    service_areas JSON, -- Areas they can operate in

    -- Permissions
    can_add_quality_checkpoints BOOLEAN DEFAULT FALSE,
    can_issue_certificates BOOLEAN DEFAULT FALSE,
    can_verify_carbon_data BOOLEAN DEFAULT FALSE,

    -- Reputation
    reputation_score DECIMAL(3,2), -- 0-5 stars
    total_products_handled INT DEFAULT 0,
    total_quality_checks INT DEFAULT 0,

    status ENUM('active', 'inactive', 'suspended'),

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by_id) REFERENCES users(id),
    UNIQUE KEY unique_user_role (user_id, role_type),
    INDEX idx_role_type (role_type)
);
```

#### 8. `consumer_scans`
การสแกน QR Code โดยผู้บริโภค

```sql
CREATE TABLE consumer_scans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    food_product_id BIGINT UNSIGNED NOT NULL,

    -- Scanner Information
    user_id BIGINT UNSIGNED NULL, -- If logged in
    session_id VARCHAR(100), -- Anonymous session

    -- Scan Details
    scanned_at TIMESTAMP,
    scan_location JSON, -- {lat, lng} from device
    device_info JSON, -- {type, os, browser}

    -- Engagement
    viewed_journey BOOLEAN DEFAULT FALSE,
    viewed_quality BOOLEAN DEFAULT FALSE,
    viewed_carbon BOOLEAN DEFAULT FALSE,
    time_spent_seconds INT,

    -- Feedback
    consumer_rating TINYINT, -- 1-5 stars
    feedback TEXT,

    -- Verification
    verified_purchase BOOLEAN DEFAULT FALSE,
    purchase_receipt_url VARCHAR(500),

    created_at TIMESTAMP,

    FOREIGN KEY (food_product_id) REFERENCES food_products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_scanned_at (scanned_at),
    INDEX idx_food_product (food_product_id)
);
```

---

## 🔧 API Endpoints Design

### 1. Food Product Management

```
POST   /api/v1/food-passport/products
GET    /api/v1/food-passport/products
GET    /api/v1/food-passport/products/{id}
PUT    /api/v1/food-passport/products/{id}
DELETE /api/v1/food-passport/products/{id}

GET    /api/v1/food-passport/products/{id}/full-journey
GET    /api/v1/food-passport/products/{id}/timeline
```

### 2. Product Journey Tracking

```
POST   /api/v1/food-passport/journey/stages
PUT    /api/v1/food-passport/journey/stages/{id}
POST   /api/v1/food-passport/journey/stages/{id}/complete

GET    /api/v1/food-passport/journey/{product_id}/current-location
GET    /api/v1/food-passport/journey/{product_id}/history
```

### 3. Quality Control

```
POST   /api/v1/food-passport/quality/checkpoints
GET    /api/v1/food-passport/quality/checkpoints/{id}
PUT    /api/v1/food-passport/quality/checkpoints/{id}

POST   /api/v1/food-passport/quality/checkpoints/{id}/approve
POST   /api/v1/food-passport/quality/checkpoints/{id}/reject
POST   /api/v1/food-passport/quality/checkpoints/{id}/retest

GET    /api/v1/food-passport/quality/products/{product_id}/report
```

### 4. Carbon Footprint & Credits

```
POST   /api/v1/food-passport/carbon/records
GET    /api/v1/food-passport/carbon/products/{product_id}/footprint
GET    /api/v1/food-passport/carbon/products/{product_id}/breakdown

POST   /api/v1/food-passport/carbon/calculate
POST   /api/v1/food-passport/carbon/verify/{record_id}

GET    /api/v1/food-passport/carbon/credits
POST   /api/v1/food-passport/carbon/credits/claim
POST   /api/v1/food-passport/carbon/credits/{id}/trade
GET    /api/v1/food-passport/carbon/credits/leaderboard
```

### 5. Certifications

```
POST   /api/v1/food-passport/certifications
GET    /api/v1/food-passport/certifications/{id}
GET    /api/v1/food-passport/certifications/verify/{certificate_number}

GET    /api/v1/food-passport/products/{product_id}/certifications
```

### 6. Consumer Interface

```
GET    /api/v1/food-passport/scan/{passport_id}
POST   /api/v1/food-passport/scan/{passport_id}/track
POST   /api/v1/food-passport/scan/{passport_id}/feedback

GET    /api/v1/food-passport/public/{passport_id}/story
GET    /api/v1/food-passport/public/{passport_id}/certifications
GET    /api/v1/food-passport/public/{passport_id}/carbon-score
```

### 7. Blockchain Integration

```
POST   /api/v1/food-passport/blockchain/record
GET    /api/v1/food-passport/blockchain/verify/{hash}
GET    /api/v1/food-passport/blockchain/products/{product_id}/transactions
```

### 8. Analytics & Reporting

```
GET    /api/v1/food-passport/analytics/dashboard
GET    /api/v1/food-passport/analytics/products/top-performers
GET    /api/v1/food-passport/analytics/carbon/savings
GET    /api/v1/food-passport/analytics/quality/trends

GET    /api/v1/food-passport/reports/monthly
GET    /api/v1/food-passport/reports/sustainability
```

---

## 📱 Services Architecture

### 1. `FoodTraceabilityService`

**ความรับผิดชอบ:**
- จัดการเส้นทางการเดินทางของผลิตภัณฑ์
- ติดตามสถานะปัจจุบัน
- สร้าง Timeline visualization
- แจ้งเตือนเมื่อมีการเปลี่ยนแปลงสถานะ

**Key Methods:**
```php
- createFoodProduct(array $data): FoodProduct
- addJourneyStage(int $productId, array $stageData): ProductJourney
- updateStageStatus(int $stageId, string $status): ProductJourney
- getFullJourney(int $productId): Collection
- getCurrentLocation(int $productId): ProductJourney
- calculateJourneyDuration(int $productId): float
- generateTimeline(int $productId): array
```

### 2. `QualityControlService`

**ความรับผิดชอบ:**
- บันทึกผลการตรวจสอบคุณภาพ
- ประเมินผลตามมาตรฐาน
- จัดการ corrective actions
- ออกใบรับรอง

**Key Methods:**
```php
- createCheckpoint(array $data): QualityCheckpoint
- evaluateResults(int $checkpointId): array
- approveCheckpoint(int $checkpointId): bool
- rejectCheckpoint(int $checkpointId, string $reason): bool
- requestRetest(int $checkpointId): QualityCheckpoint
- generateQualityReport(int $productId): array
- calculateQualityScore(int $productId): float
```

### 3. `CarbonCreditService`

**ความรับผิดชอบ:**
- คำนวณ Carbon Footprint
- จัดการ Carbon Credits
- ตรวจสอบและ verify ข้อมูล
- คำนวณรางวัล

**Key Methods:**
```php
- calculateFootprint(int $productId): float
- recordEmission(array $emissionData): CarbonFootprintRecord
- verifyEmissionData(int $recordId): bool
- calculateCarbonScore(int $productId): string
- issueCarbonCredit(int $userId, int $productId): CarbonCredit
- tradeCarbonCredit(int $creditId, int $buyerId, float $price): bool
- getLeaderboard(string $period): Collection
- calculateRewards(int $userId): float
```

### 4. `BlockchainRecordService`

**ความรับผิดชอบ:**
- บันทึกข้อมูลบน Blockchain
- ตรวจสอบความถูกต้อง
- จัดการ IPFS สำหรับเอกสาร

**Key Methods:**
```php
- recordProductOnChain(FoodProduct $product): string
- recordJourneyStageOnChain(ProductJourney $stage): string
- recordQualityCheckOnChain(QualityCheckpoint $checkpoint): string
- recordCarbonDataOnChain(CarbonFootprintRecord $record): string
- verifyBlockchainRecord(string $hash): bool
- uploadToIPFS(string $filePath): string
- retrieveFromIPFS(string $hash): string
```

### 5. `CertificationService`

**ความรับผิดชอบ:**
- ออกใบรับรอง
- ตรวจสอบใบรับรอง
- จัดการวันหมดอายุ
- สร้าง QR code

**Key Methods:**
```php
- issueCertification(int $productId, array $certData): FoodCertification
- verifyCertificate(string $certificateNumber): ?FoodCertification
- renewCertificate(int $certId): FoodCertification
- revokeCertificate(int $certId, string $reason): bool
- generateQRCode(int $certId): string
- checkExpiringSoon(int $days): Collection
```

### 6. `FoodPassportAnalyticsService`

**ความรับผิดชอบ:**
- สรุปข้อมูลเชิงสถิติ
- สร้างรายงาน
- วิเคราะห์แนวโน้ม
- Dashboard metrics

**Key Methods:**
```php
- getDashboardMetrics(int $userId): array
- getProductPerformance(int $productId): array
- getCarbonSavingsReport(string $period): array
- getQualityTrends(string $period): array
- getTopPerformers(string $metric, int $limit): Collection
- generateMonthlyReport(int $userId, string $month): array
- getSustainabilityScore(int $userId): float
```

---

## 🔗 Integration with Existing Systems

### 1. Product Management Integration

```php
// Link existing Product with FoodProduct
class Product extends Model {
    public function foodPassport() {
        return $this->hasOne(FoodProduct::class);
    }

    public function createFoodPassport(array $data) {
        return $this->foodPassport()->create($data);
    }
}
```

### 2. User & Stakeholder Integration

```php
// Extend User model
class User extends Model {
    public function stakeholderRoles() {
        return $this->hasMany(StakeholderRole::class);
    }

    public function foodProducts() {
        return $this->hasMany(FoodProduct::class, 'farmer_id');
    }

    public function carbonCredits() {
        return $this->hasMany(CarbonCredit::class);
    }

    public function isFarmer(): bool {
        return $this->stakeholderRoles()
            ->where('role_type', 'farmer')
            ->where('status', 'active')
            ->exists();
    }
}
```

### 3. Blockchain Integration

```php
// Reuse existing CryptoWallet infrastructure
class BlockchainRecordService {
    protected CryptoWalletService $walletService;

    public function recordOnChain(array $data): string {
        $wallet = $this->walletService->getSystemWallet();

        // Create transaction data
        $txData = $this->prepareSmartContractCall(
            'recordFoodData',
            $data
        );

        // Submit to blockchain
        return $this->submitTransaction($wallet, $txData);
    }
}
```

### 4. MLM & Commission Integration

```php
// Farmers earn commission on quality products
class FoodPassportCommissionObserver {
    public function created(FoodProduct $product) {
        if ($product->carbon_score === 'A+') {
            // Award bonus commission to farmer
            $commission = Commission::create([
                'user_id' => $product->farmer_id,
                'type' => 'food_passport_bonus',
                'amount' => $this->calculateBonus($product),
                'description' => 'High-quality sustainable product bonus'
            ]);
        }
    }
}
```

### 5. LINE Integration for Notifications

```php
// Send LINE notification when quality check completed
class QualityCheckpointObserver {
    protected LineService $lineService;

    public function updated(QualityCheckpoint $checkpoint) {
        if ($checkpoint->wasChanged('overall_result')) {
            $product = $checkpoint->foodProduct;
            $farmer = $product->farmer;

            // Send LINE notification
            $this->lineService->sendFlexMessage(
                $farmer->line_user_id,
                $this->buildQualityResultMessage($checkpoint)
            );
        }
    }
}
```

---

## 🎨 Frontend Components

### 1. Consumer Scan Interface

**หน้าที่ผู้บริโภคสแกน QR Code เห็น:**

```
┌─────────────────────────────────────────┐
│  🥬 Organic Romaine Lettuce             │
│  Passport ID: FP-2025-001234            │
├─────────────────────────────────────────┤
│                                          │
│  📍 Current Location                     │
│  Tesco Lotus, Bangkok                   │
│  Last updated: 2 hours ago              │
│                                          │
│  🌱 From Farm                            │
│  Green Valley Farm, Chiang Mai          │
│  Harvest: Nov 10, 2025                  │
│                                          │
│  ⭐ Quality Score: 95/100                │
│  🌍 Carbon Score: A+                     │
│  🏆 Certifications: Organic, HACCP      │
│                                          │
│  [View Full Journey] [Quality Reports]  │
│  [Carbon Footprint] [Give Feedback]     │
│                                          │
└─────────────────────────────────────────┘
```

### 2. Journey Timeline

```
🌱 Farm (Green Valley Farm)
│  Nov 10, 09:00 - Harvested
│  ✓ Quality Check: Pass (98/100)
│
├─ 🚚 Transport (150 km)
│  Nov 10, 14:00 - 18:00
│  Carbon: 12.5 kg CO2
│
├─ 🏭 Processing Center (Bangkok)
│  Nov 11, 08:00 - 16:00
│  ✓ Quality Check: Pass (96/100)
│  ✓ Pesticide Test: Pass
│
├─ 🚚 Distribution (50 km)
│  Nov 11, 17:00 - 19:00
│  Carbon: 4.2 kg CO2
│
└─ 🏪 Retail (Tesco Lotus)
   Nov 12, 06:00 - Present
   ✓ Storage Temp: 4-6°C
```

### 3. Farmer Dashboard

```
┌─────────────────────────────────────────┐
│  Food Passport Dashboard                │
├─────────────────────────────────────────┤
│  Active Products: 24                    │
│  Total Carbon Credits: 15.8 tons        │
│  Average Quality Score: 94/100          │
│  This Month Earnings: ฿45,680           │
├─────────────────────────────────────────┤
│  Recent Products                        │
│  ┌───────────────────────────────────┐  │
│  │ 🥬 Organic Lettuce                │  │
│  │ Status: In Transit                │  │
│  │ Quality: 95/100 | Carbon: A+      │  │
│  │ [Track] [View Details]            │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │ 🍅 Cherry Tomatoes                │  │
│  │ Status: At Retail                 │  │
│  │ Quality: 92/100 | Carbon: A       │  │
│  │ [Track] [View Details]            │  │
│  └───────────────────────────────────┘  │
├─────────────────────────────────────────┤
│  Carbon Credits Trading                 │
│  Available: 8.5 tons | Value: ฿21,250   │
│  [Trade Credits] [View Market]          │
└─────────────────────────────────────────┘
```

---

## 🔐 Security & Privacy

### 1. Data Privacy
- ข้อมูลส่วนตัวของเกษตรกรและผู้ประกอบการต้องได้รับการคุ้มครอง
- ผู้บริโภคเห็นเฉพาะข้อมูลที่จำเป็น (ชื่อฟาร์ม, พื้นที่, ไม่ใช่ที่อยู่แน่นอน)
- Blockchain เก็บเฉพาะ hash, ไม่เก็บข้อมูลส่วนตัว

### 2. Authentication & Authorization
```php
// Middleware สำหรับ Food Passport API
Route::middleware(['auth:sanctum', 'verified'])
    ->prefix('food-passport')
    ->group(function () {
        // Farmer routes
        Route::middleware(['role:farmer'])
            ->group(function () {
                Route::post('/products', [FoodPassportController::class, 'store']);
                Route::put('/products/{id}', [FoodPassportController::class, 'update']);
            });

        // Quality Inspector routes
        Route::middleware(['role:quality_inspector'])
            ->group(function () {
                Route::post('/quality/checkpoints', [QualityController::class, 'store']);
                Route::post('/quality/checkpoints/{id}/approve', [QualityController::class, 'approve']);
            });

        // Carbon Verifier routes
        Route::middleware(['role:carbon_verifier'])
            ->group(function () {
                Route::post('/carbon/verify/{id}', [CarbonCreditController::class, 'verify']);
            });
    });

// Public routes (no auth required)
Route::prefix('food-passport/public')
    ->group(function () {
        Route::get('/scan/{passport_id}', [FoodPassportController::class, 'scan']);
        Route::get('/{passport_id}/story', [FoodPassportController::class, 'story']);
    });
```

### 3. Data Integrity
- ทุกข้อมูลสำคัญบันทึกบน Blockchain
- ใช้ IPFS เก็บเอกสาร/รูปภาพเพื่อความไม่สามารถแก้ไขได้
- Hash verification ทุกครั้งที่เรียกดูข้อมูล

---

## 📊 Smart Contract Design

### FoodPassportContract (Solidity)

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract FoodPassportRegistry {
    struct ProductRecord {
        string passportId;
        string productType;
        uint256 harvestDate;
        address farmer;
        uint256 timestamp;
        string metadataHash; // IPFS hash
        bool exists;
    }

    struct JourneyStage {
        string stageName;
        address handler;
        uint256 timestamp;
        string location;
        string metadataHash;
    }

    struct QualityCheck {
        address inspector;
        uint256 timestamp;
        bool passed;
        uint8 score;
        string reportHash;
    }

    struct CarbonRecord {
        uint256 totalEmission; // in grams CO2
        uint256 carbonOffset;
        string grade; // A+, A, B, C, D
        bool verified;
        address verifier;
    }

    // Mappings
    mapping(string => ProductRecord) public products;
    mapping(string => JourneyStage[]) public productJourney;
    mapping(string => QualityCheck[]) public qualityChecks;
    mapping(string => CarbonRecord) public carbonRecords;

    // Events
    event ProductRegistered(string passportId, address farmer, uint256 timestamp);
    event JourneyStageAdded(string passportId, string stage, address handler);
    event QualityCheckAdded(string passportId, bool passed, uint8 score);
    event CarbonRecorded(string passportId, uint256 emission, string grade);

    // Functions
    function registerProduct(
        string memory _passportId,
        string memory _productType,
        uint256 _harvestDate,
        string memory _metadataHash
    ) public {
        require(!products[_passportId].exists, "Product already registered");

        products[_passportId] = ProductRecord({
            passportId: _passportId,
            productType: _productType,
            harvestDate: _harvestDate,
            farmer: msg.sender,
            timestamp: block.timestamp,
            metadataHash: _metadataHash,
            exists: true
        });

        emit ProductRegistered(_passportId, msg.sender, block.timestamp);
    }

    function addJourneyStage(
        string memory _passportId,
        string memory _stageName,
        string memory _location,
        string memory _metadataHash
    ) public {
        require(products[_passportId].exists, "Product not found");

        productJourney[_passportId].push(JourneyStage({
            stageName: _stageName,
            handler: msg.sender,
            timestamp: block.timestamp,
            location: _location,
            metadataHash: _metadataHash
        }));

        emit JourneyStageAdded(_passportId, _stageName, msg.sender);
    }

    function addQualityCheck(
        string memory _passportId,
        bool _passed,
        uint8 _score,
        string memory _reportHash
    ) public {
        require(products[_passportId].exists, "Product not found");
        require(_score <= 100, "Score must be <= 100");

        qualityChecks[_passportId].push(QualityCheck({
            inspector: msg.sender,
            timestamp: block.timestamp,
            passed: _passed,
            score: _score,
            reportHash: _reportHash
        }));

        emit QualityCheckAdded(_passportId, _passed, _score);
    }

    function recordCarbon(
        string memory _passportId,
        uint256 _totalEmission,
        uint256 _carbonOffset,
        string memory _grade
    ) public {
        require(products[_passportId].exists, "Product not found");

        carbonRecords[_passportId] = CarbonRecord({
            totalEmission: _totalEmission,
            carbonOffset: _carbonOffset,
            grade: _grade,
            verified: false,
            verifier: address(0)
        });

        emit CarbonRecorded(_passportId, _totalEmission, _grade);
    }

    function verifyCarbonData(string memory _passportId) public {
        require(products[_passportId].exists, "Product not found");
        require(!carbonRecords[_passportId].verified, "Already verified");

        carbonRecords[_passportId].verified = true;
        carbonRecords[_passportId].verifier = msg.sender;
    }

    // View functions
    function getProductInfo(string memory _passportId)
        public view returns (ProductRecord memory) {
        require(products[_passportId].exists, "Product not found");
        return products[_passportId];
    }

    function getJourneyLength(string memory _passportId)
        public view returns (uint256) {
        return productJourney[_passportId].length;
    }

    function getQualityChecksCount(string memory _passportId)
        public view returns (uint256) {
        return qualityChecks[_passportId].length;
    }
}
```

---

## 🎯 Implementation Phases

### Phase 1: Foundation (Weeks 1-3)
- [ ] Database migrations
- [ ] Core models (FoodProduct, ProductJourney, QualityCheckpoint)
- [ ] Basic API endpoints
- [ ] FoodTraceabilityService
- [ ] Admin interface for product management

### Phase 2: Quality Control (Weeks 4-5)
- [ ] QualityControlService
- [ ] Quality checkpoint recording
- [ ] Quality reports
- [ ] Inspector role management
- [ ] CertificationService

### Phase 3: Carbon Footprint (Weeks 6-8)
- [ ] Carbon calculation algorithms
- [ ] CarbonCreditService
- [ ] Carbon footprint recording
- [ ] Carbon credit issuance
- [ ] Carbon marketplace (trading)

### Phase 4: Blockchain Integration (Weeks 9-11)
- [ ] Smart contract development
- [ ] BlockchainRecordService
- [ ] IPFS integration
- [ ] Blockchain verification
- [ ] NFT certificates

### Phase 5: Consumer Interface (Weeks 12-13)
- [ ] QR code generation
- [ ] Public product story page
- [ ] Journey timeline visualization
- [ ] Mobile-responsive design
- [ ] Consumer feedback system

### Phase 6: Analytics & Reporting (Weeks 14-15)
- [ ] Dashboard for farmers
- [ ] Dashboard for inspectors/verifiers
- [ ] Analytics service
- [ ] Sustainability reports
- [ ] Performance metrics

### Phase 7: Integration & Testing (Weeks 16-17)
- [ ] LINE notifications
- [ ] MLM commission integration
- [ ] Payment gateway (for carbon credit trading)
- [ ] Mobile app updates
- [ ] Comprehensive testing

### Phase 8: Launch & Optimization (Week 18)
- [ ] User training materials
- [ ] Documentation
- [ ] Performance optimization
- [ ] Security audit
- [ ] Production deployment

---

## 📈 Carbon Calculation Methodology

### Emission Factors (ตัวอย่าง)

#### Transportation
```
Diesel Truck: 0.97 kg CO2 per liter
- Light duty (<3.5 tons): 0.25 kg CO2/km
- Medium duty (3.5-12 tons): 0.68 kg CO2/km
- Heavy duty (>12 tons): 1.2 kg CO2/km

Electric Vehicle: 0.15 kg CO2/km (based on grid mix)

Ship: 0.01 kg CO2/ton-km
Air Freight: 1.2 kg CO2/ton-km
```

#### Farming
```
Organic vegetables: 0.5-1.5 kg CO2/kg product
Conventional vegetables: 2-4 kg CO2/kg product
Rice: 2.5-4 kg CO2/kg
Beef: 27-60 kg CO2/kg
Chicken: 6-10 kg CO2/kg
Fish (farmed): 5-8 kg CO2/kg
```

#### Processing & Storage
```
Cold storage: 0.2 kg CO2/kg/day
Packaging (plastic): 6 kg CO2/kg packaging
Packaging (paper): 2.5 kg CO2/kg packaging
Processing (washing, cutting): 0.1-0.3 kg CO2/kg
```

### Carbon Score Grading

```
A+ : Net zero or carbon negative
A  : < 1 kg CO2/kg product
B  : 1-3 kg CO2/kg product
C  : 3-5 kg CO2/kg product
D  : 5-10 kg CO2/kg product
E  : > 10 kg CO2/kg product
```

### Carbon Credit Calculation

```php
$baseline = $this->getBaselineEmission($productType);
$actual = $product->total_carbon_footprint;

if ($actual < $baseline) {
    $reduction = $baseline - $actual;
    $creditAmount = $reduction * $product->quantity;

    // Award credits
    $this->issueCarbonCredit($farmer, $creditAmount);
}
```

---

## 🎁 Incentive System

### 1. Carbon Credit Rewards

**Tier System:**
```
🥉 Bronze (10-50 tons CO2 saved)
   - 5% bonus on carbon credit value
   - Badge display on products

🥈 Silver (50-100 tons CO2 saved)
   - 10% bonus on carbon credit value
   - Priority in marketplace
   - Featured farmer status

🥇 Gold (100-500 tons CO2 saved)
   - 15% bonus on carbon credit value
   - Premium listing
   - Sustainability ambassador badge

💎 Platinum (>500 tons CO2 saved)
   - 20% bonus on carbon credit value
   - Direct partnership opportunities
   - Media features
```

### 2. Quality Excellence Rewards

```
Perfect Quality Month (All products >95 score)
└─> ฿5,000 bonus + Featured profile

Consistent High Quality (6 months avg >90)
└─> ฿15,000 bonus + Gold quality badge

Zero Failed Inspections (1 year)
└─> ฿30,000 bonus + Premium certification
```

### 3. MLM Commission Boost

```php
// Bonus commission for sustainable products
if ($product->carbon_score === 'A+') {
    $commission *= 1.5; // 50% boost
} elseif ($product->carbon_score === 'A') {
    $commission *= 1.3; // 30% boost
}

// Quality bonus
if ($product->average_quality_score >= 95) {
    $commission *= 1.2; // 20% boost
}
```

---

## 📱 Mobile App Features

### Farmer App
- [ ] Quick product registration with camera
- [ ] Real-time journey tracking
- [ ] Quality checkpoint photos
- [ ] Carbon footprint dashboard
- [ ] Carbon credit wallet
- [ ] Earnings tracker

### Inspector App
- [ ] Checklist-based inspections
- [ ] Photo upload with geotag
- [ ] Offline mode with sync
- [ ] Digital signature
- [ ] Certificate generation

### Consumer App (ส่วนหนึ่งของ main app)
- [ ] QR/NFC scanner
- [ ] Product story viewer
- [ ] Sustainability score
- [ ] Feedback submission
- [ ] Product favorites

---

## 🔍 Success Metrics

### Key Performance Indicators (KPIs)

**Adoption Metrics:**
- Number of registered farmers
- Number of products with Food Passport
- Number of consumer scans
- Stakeholder engagement rate

**Quality Metrics:**
- Average quality score
- Pass rate of quality checks
- Number of certifications issued
- Consumer satisfaction rating

**Sustainability Metrics:**
- Total CO2 emissions tracked
- Total CO2 saved vs baseline
- Carbon credits issued
- Percentage of A+ rated products

**Business Metrics:**
- Carbon credit trading volume
- Farmer income increase
- Premium price achieved for certified products
- Consumer trust score

**Technical Metrics:**
- API response time
- Blockchain transaction success rate
- System uptime
- Data accuracy rate

---

## 🌟 Unique Selling Points (USPs)

1. **ความโปร่งใส 100%**
   - ทุกขั้นตอนบันทึกบน Blockchain
   - ไม่สามารถปลอมแปลงได้
   - ผู้บริโภคตรวจสอบได้ตลอดเวลา

2. **เป็นมิตรกับสิ่งแวดล้อม**
   - ติดตาม Carbon Footprint แบบ real-time
   - ให้รางวัลการผลิตที่ sustainable
   - ส่งเสริมเศรษฐกิจสีเขียว

3. **รายได้เพิ่มให้เกษตรกร**
   - ผลิตภัณฑ์คุณภาพดีขายได้ราคาสูงขึ้น
   - รับ Carbon Credit สามารถซื้อขายได้
   - ระบบ MLM Commission boost

4. **ง่ายต่อการใช้งาน**
   - สแกน QR Code ครั้งเดียว รู้ทุกอย่าง
   - Interface เข้าใจง่าย ภาษาไทย
   - Mobile-first design

5. **มาตรฐานสากล**
   - ใช้มาตรฐาน ISO, HACCP, GMP
   - การคำนวณ carbon ตาม IPCC guidelines
   - Blockchain security standard

---

## 🚀 Go-to-Market Strategy

### Phase 1: Pilot Program (Month 1-3)
- เริ่มกับ 10-20 เกษตรกร/ฟาร์มที่เป็น early adopters
- ผักปลอดสารพิษ, ผลไม้ organic
- ทดสอบในกรุงเทพฯและปริมณฑล
- Feedback และปรับปรุงระบบ

### Phase 2: Market Expansion (Month 4-6)
- ขยายไปยังเกษตรกรเพิ่มเติม 100-200 ราย
- เพิ่มประเภทสินค้า (ข้าว, เนื้อสัตว์)
- จับคู่กับห้าง/ซูเปอร์มาร์เก็ต
- Marketing campaign "รู้ที่มา กินอุ่นใจ"

### Phase 3: National Scale (Month 7-12)
- เปิดให้เกษตรกรทั่วประเทศเข้าร่วม
- Partnership กับกระทรวงเกษตรฯ
- Carbon credit marketplace launch
- Export certification

### Marketing Channels
- LINE OA (มีอยู่แล้ว)
- Facebook/Instagram (เน้น storytelling)
- YouTube (farmer success stories)
- Events & Farm tours
- B2B partnerships (restaurants, hotels)

---

## 💰 Revenue Model

### 1. Subscription Fees
```
Farmer Basic (Free)
├─ 10 products/month
├─ Basic traceability
└─ Community support

Farmer Pro (฿299/month)
├─ Unlimited products
├─ Full traceability + QC
├─ Carbon tracking
├─ Priority support
└─ Marketing boost

Enterprise (฿2,999/month)
├─ Multi-location support
├─ API access
├─ White-label options
├─ Custom integrations
└─ Dedicated account manager
```

### 2. Transaction Fees
- Carbon credit trading: 5% commission
- Premium product listing: ฿50/product/month
- Certification services: ฿500-2,000/certificate

### 3. B2B Services
- API access for retailers: ฿5,000/month
- Custom reports: ฿10,000/report
- Consulting services: ฿50,000+/project

### 4. Government Grants
- Sustainability initiatives
- Agricultural technology development
- Carbon reduction programs

---

## 📚 Documentation Requirements

### User Manuals
- [ ] Farmer guide (ภาษาไทย)
- [ ] Inspector guide
- [ ] Consumer guide
- [ ] Admin manual

### Technical Documentation
- [ ] API documentation
- [ ] Database schema
- [ ] Smart contract documentation
- [ ] Deployment guide

### Training Materials
- [ ] Video tutorials
- [ ] Quick start guides
- [ ] Best practices
- [ ] FAQs

---

## 🔒 Compliance & Standards

### Food Safety Standards
- ISO 22000 (Food Safety Management)
- HACCP (Hazard Analysis Critical Control Points)
- GMP (Good Manufacturing Practice)
- GAP (Good Agricultural Practice)

### Carbon Standards
- ISO 14064 (Greenhouse Gas Accounting)
- PAS 2050 (Product Carbon Footprint)
- Greenhouse Gas Protocol
- Carbon Trust Standard

### Blockchain Standards
- ERC-721 (NFT for certificates)
- IPFS best practices
- Smart contract security (OpenZeppelin)

### Data Privacy
- PDPA (Personal Data Protection Act) Thailand
- GDPR compliance (for export)
- Data encryption standards

---

## 🎓 Conclusion

ระบบ Food Passport นี้จะเป็นโซลูชั่นที่ครอบคลุมและทันสมัยสำหรับ:

✅ **เกษตรกร:** เพิ่มรายได้ สร้างความน่าเชื่อถือ รับรางวัล carbon credit
✅ **ผู้บริโภค:** มั่นใจในคุณภาพ รู้ที่มา ร่วมรักษ์โลก
✅ **ผู้ประกอบการ:** ลดความเสี่ยง เพิ่มความโปร่งใส สร้างแบรนด์
✅ **สังคม:** ส่งเสริมความยั่งยืน ลด carbon footprint สร้างระบบนิเวศที่ดีกว่า

การบูรณาการกับระบบที่มีอยู่แล้ว (Products, Blockchain, MLM, LINE) ทำให้สามารถพัฒนาได้รวดเร็วและใช้ประโยชน์จากโครงสร้างพื้นฐานที่มีอยู่

---

**Next Steps:**
1. Review และ approve แผนนี้
2. จัดลำดับความสำคัญของ features
3. Estimate งบประมาณและทรัพยากร
4. เริ่ม Phase 1 development
5. Recruit pilot farmers

**Timeline:** 18 weeks to MVP
**Estimated Budget:** TBD based on team size and resources
**Risk Level:** Medium (requires stakeholder buy-in, pilot testing critical)

---

*เอกสารนี้เป็น living document ที่จะมีการ update ตามความคืบหน้าของโปรเจค*

# 🔧 Food Passport System - Technical Specifications

**Version:** 1.0
**Last Updated:** 2025-11-13
**Status:** Planning Phase

---

## 📋 Table of Contents

1. [System Requirements](#system-requirements)
2. [Technology Stack](#technology-stack)
3. [Architecture Details](#architecture-details)
4. [Database Specifications](#database-specifications)
5. [API Specifications](#api-specifications)
6. [Smart Contract Specifications](#smart-contract-specifications)
7. [Security Specifications](#security-specifications)
8. [Performance Requirements](#performance-requirements)
9. [Integration Specifications](#integration-specifications)
10. [Deployment Guide](#deployment-guide)

---

## System Requirements

### Server Requirements

#### Production Environment

```yaml
Web Server:
  - Nginx 1.20+ or Apache 2.4+
  - PHP 8.1+ with extensions:
    - BCMath
    - Ctype
    - Fileinfo
    - JSON
    - Mbstring
    - OpenSSL
    - PDO
    - Tokenizer
    - XML
    - GD or Imagick
    - Redis
    - GMP (for blockchain)

Database:
  - MySQL 8.0+ or MariaDB 10.6+
  - Minimum 4GB RAM allocated
  - InnoDB storage engine

Cache:
  - Redis 6.2+
  - Minimum 1GB RAM

Storage:
  - 100GB+ SSD storage
  - S3 or compatible object storage

Blockchain:
  - Ethereum/BSC/Polygon node access
  - IPFS node (optional, can use Infura/Pinata)
```

#### Development Environment

```bash
# Minimum Requirements
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Node.js 18+
- Composer 2.x
- npm 9+

# Recommended
- Docker 20+
- Redis 6+
- Git 2.x
```

### Client Requirements

```yaml
Web Browser:
  - Chrome 90+
  - Firefox 88+
  - Safari 14+
  - Edge 90+

Mobile:
  - iOS 13+
  - Android 8.0+
  - .NET MAUI runtime

Network:
  - Minimum 3G connection
  - HTTPS required for production
```

---

## Technology Stack

### Backend Framework

```php
Laravel Framework 11.x
├── PHP 8.1+
├── Composer 2.x
└── Extensions:
    ├── Laravel Sanctum (API authentication)
    ├── Laravel Horizon (queue monitoring)
    ├── Laravel Telescope (debugging)
    └── Spatie packages (permissions, media)
```

### Frontend Stack

```javascript
// Build Tools
Vite 5.0
├── JavaScript/TypeScript
├── CSS/SCSS
└── Asset optimization

// Libraries
Alpine.js 3.13.5        // Reactive components
Tailwind CSS 3.4        // Styling
Chart.js 4.4.1         // Charts
D3.js 7.9              // Data visualization
GSAP 3.12.5            // Animations
```

### Mobile Development

```csharp
.NET MAUI (C#)
├── Target Platforms:
│   ├── Android 8.0+
│   ├── iOS 13+
│   └── Windows 10+
└── Architecture:
    ├── MVVM pattern
    ├── Dependency Injection
    └── REST API client
```

### Blockchain Stack

```javascript
// Web3 Integration
Web3.js / Ethers.js 5.8.0
├── Smart Contracts (Solidity 0.8.x)
├── Wallet Integration
│   ├── MetaMask
│   ├── WalletConnect
│   └── Binance Chain Wallet
└── Networks:
    ├── Ethereum Mainnet/Testnet
    ├── Binance Smart Chain
    └── Polygon
```

### Storage Solutions

```yaml
Database:
  Primary: MySQL 8.0 / MariaDB 10.6
  Search: Elasticsearch 8.x (optional)

Cache:
  Primary: Redis 6.2+
  Session: Redis/Database

File Storage:
  Local: Laravel Storage
  Cloud: S3/DigitalOcean Spaces
  Distributed: IPFS

Blockchain:
  Ethereum/BSC/Polygon: Smart contracts
  IPFS: Document storage
```

---

## Architecture Details

### Layered Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  Presentation Layer                      │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐        │
│  │    Web     │  │   Mobile   │  │    API     │        │
│  │  (Blade/   │  │  (.NET     │  │  (REST)    │        │
│  │  Alpine)   │  │   MAUI)    │  │            │        │
│  └────────────┘  └────────────┘  └────────────┘        │
└───────────────────────┬─────────────────────────────────┘
                        │
┌───────────────────────┴─────────────────────────────────┐
│                 Application Layer                        │
│  ┌──────────────────────────────────────────────────┐   │
│  │             Controllers                          │   │
│  │  ├─ FoodPassportController                      │   │
│  │  ├─ TraceabilityController                      │   │
│  │  ├─ QualityController                           │   │
│  │  └─ CarbonCreditController                      │   │
│  └──────────────────────────────────────────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │          Business Logic (Services)               │   │
│  │  ├─ FoodTraceabilityService                     │   │
│  │  ├─ QualityControlService                       │   │
│  │  ├─ CarbonCreditService                         │   │
│  │  ├─ BlockchainRecordService                     │   │
│  │  └─ CertificationService                        │   │
│  └──────────────────────────────────────────────────┘   │
└───────────────────────┬─────────────────────────────────┘
                        │
┌───────────────────────┴─────────────────────────────────┐
│                   Data Layer                             │
│  ┌──────────────────────────────────────────────────┐   │
│  │         Models (Eloquent ORM)                    │   │
│  │  ├─ FoodProduct                                  │   │
│  │  ├─ ProductJourney                               │   │
│  │  ├─ QualityCheckpoint                            │   │
│  │  ├─ CarbonFootprintRecord                        │   │
│  │  └─ CarbonCredit                                 │   │
│  └──────────────────────────────────────────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │             Repositories                         │   │
│  │  └─ Repository pattern for complex queries      │   │
│  └──────────────────────────────────────────────────┘   │
└───────────────────────┬─────────────────────────────────┘
                        │
┌───────────────────────┴─────────────────────────────────┐
│               Infrastructure Layer                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │  MySQL   │  │  Redis   │  │   S3     │             │
│  └──────────┘  └──────────┘  └──────────┘             │
│  ┌──────────┐  ┌──────────┐                             │
│  │Blockchain│  │   IPFS   │                             │
│  └──────────┘  └──────────┘                             │
└─────────────────────────────────────────────────────────┘
```

### Service Layer Design

#### FoodTraceabilityService

```php
<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\ProductJourney;
use Illuminate\Support\Collection;

class FoodTraceabilityService
{
    protected BlockchainRecordService $blockchain;

    public function __construct(BlockchainRecordService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Create a new food product with passport
     */
    public function createFoodProduct(array $data): FoodProduct
    {
        // Generate unique passport ID
        $passportId = $this->generatePassportId();

        $product = FoodProduct::create([
            'food_passport_id' => $passportId,
            'product_id' => $data['product_id'] ?? null,
            'farmer_id' => auth()->id(),
            'product_type' => $data['product_type'],
            'variety' => $data['variety'],
            'harvest_date' => $data['harvest_date'],
            'batch_number' => $data['batch_number'] ?? $this->generateBatchNumber(),
            'estimated_quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'certifications' => $data['certifications'] ?? [],
            'current_stage' => 'farm',
        ]);

        // Record on blockchain
        $hash = $this->blockchain->recordProductOnChain($product);
        $product->update(['blockchain_hash' => $hash]);

        // Generate QR code
        $this->generateQRCode($product);

        return $product->fresh();
    }

    /**
     * Add a journey stage
     */
    public function addJourneyStage(int $productId, array $stageData): ProductJourney
    {
        $product = FoodProduct::findOrFail($productId);

        $sequence = ProductJourney::where('food_product_id', $productId)
            ->max('sequence_order') + 1;

        $journey = ProductJourney::create([
            'food_product_id' => $productId,
            'stage' => $stageData['stage'],
            'stage_name' => $stageData['stage_name'] ?? ucfirst($stageData['stage']),
            'sequence_order' => $sequence,
            'location_name' => $stageData['location_name'],
            'location_coordinates' => $stageData['location_coordinates'] ?? null,
            'arrived_at' => now(),
            'handler_id' => auth()->id(),
            'handler_name' => auth()->user()->name,
            'transport_method' => $stageData['transport_method'] ?? null,
            'distance_km' => $stageData['distance_km'] ?? null,
        ]);

        // Calculate carbon emission for this stage
        if ($journey->transport_method) {
            $emission = app(CarbonCreditService::class)
                ->calculateTransportEmission($journey);
            $journey->update(['stage_carbon_emission' => $emission]);
        }

        // Update product current stage
        $product->update(['current_stage' => $stageData['stage']]);

        // Record on blockchain
        $hash = $this->blockchain->recordJourneyStageOnChain($journey);
        $journey->update(['blockchain_hash' => $hash]);

        // Send notifications
        $this->notifyStakeholders($product, $journey);

        return $journey;
    }

    /**
     * Get full journey with all details
     */
    public function getFullJourney(int $productId): Collection
    {
        return ProductJourney::where('food_product_id', $productId)
            ->with(['handler', 'qualityCheckpoints'])
            ->orderBy('sequence_order')
            ->get();
    }

    /**
     * Generate unique passport ID
     */
    protected function generatePassportId(): string
    {
        $prefix = 'FP';
        $year = date('Y');
        $sequence = FoodProduct::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Generate QR code
     */
    protected function generateQRCode(FoodProduct $product): void
    {
        // Implementation using QR code library
        // Store QR code image in storage
    }
}
```

#### CarbonCreditService

```php
<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\CarbonFootprintRecord;
use App\Models\CarbonCredit;

class CarbonCreditService
{
    /**
     * Emission factors (kg CO2 per unit)
     */
    protected array $emissionFactors = [
        'transport' => [
            'diesel_truck_light' => 0.25,  // per km
            'diesel_truck_medium' => 0.68,
            'diesel_truck_heavy' => 1.2,
            'electric_vehicle' => 0.15,
            'ship' => 0.01,  // per ton-km
            'air_freight' => 1.2,  // per ton-km
        ],
        'farming' => [
            'organic_vegetables' => 1.0,  // per kg product
            'conventional_vegetables' => 3.0,
            'organic_rice' => 2.5,
            'conventional_rice' => 4.0,
        ],
        'processing' => [
            'cold_storage' => 0.2,  // per kg per day
            'packaging_plastic' => 6.0,  // per kg packaging
            'packaging_paper' => 2.5,
        ],
    ];

    /**
     * Baseline emissions for product types
     */
    protected array $baselines = [
        'vegetables' => 30.0,  // kg CO2 per ton
        'fruits' => 25.0,
        'rice' => 40.0,
        'meat' => 150.0,
    ];

    /**
     * Calculate total carbon footprint
     */
    public function calculateFootprint(int $productId): float
    {
        $product = FoodProduct::with('journeyStages')->findOrFail($productId);

        $total = 0;

        // Sum all recorded emissions
        $total += CarbonFootprintRecord::where('food_product_id', $productId)
            ->sum('total_co2_equivalent');

        // Update product
        $product->update(['total_carbon_footprint' => $total]);

        // Calculate carbon score
        $score = $this->calculateCarbonScore($product);
        $product->update(['carbon_score' => $score]);

        return $total;
    }

    /**
     * Calculate transport emission
     */
    public function calculateTransportEmission(ProductJourney $journey): float
    {
        $method = $journey->transport_method;
        $distance = $journey->distance_km;

        // Map method to emission factor
        $factorKey = $this->mapTransportMethod($method);
        $factor = $this->emissionFactors['transport'][$factorKey] ?? 0.5;

        $emission = $distance * $factor;

        // Record this emission
        CarbonFootprintRecord::create([
            'food_product_id' => $journey->food_product_id,
            'journey_stage_id' => $journey->id,
            'emission_category' => 'transportation',
            'emission_source' => "{$method} - {$distance} km",
            'activity_data' => [
                'distance_km' => $distance,
                'vehicle_type' => $method,
            ],
            'emission_factor' => $factor,
            'co2_emission' => $emission,
            'total_co2_equivalent' => $emission,
            'net_emission' => $emission,
            'calculation_method' => 'Distance-based',
            'calculation_date' => now(),
            'verification_status' => 'pending',
        ]);

        return $emission;
    }

    /**
     * Calculate carbon score (A+ to E)
     */
    public function calculateCarbonScore(FoodProduct $product): string
    {
        $emission = $product->total_carbon_footprint;
        $quantity = $product->estimated_quantity;

        if ($quantity == 0) return 'N/A';

        $emissionPerKg = $emission / $quantity;

        // Grading
        if ($emissionPerKg <= 0) return 'A+';
        if ($emissionPerKg < 1) return 'A';
        if ($emissionPerKg < 3) return 'B';
        if ($emissionPerKg < 5) return 'C';
        if ($emissionPerKg < 10) return 'D';
        return 'E';
    }

    /**
     * Issue carbon credit
     */
    public function issueCarbonCredit(int $userId, int $productId): ?CarbonCredit
    {
        $product = FoodProduct::findOrFail($productId);

        // Get baseline
        $baseline = $this->baselines[$product->product_type] ?? 30.0;
        $baselineTotal = ($baseline / 1000) * $product->estimated_quantity;

        $actual = $product->total_carbon_footprint;

        // Must be lower than baseline
        if ($actual >= $baselineTotal) {
            return null;
        }

        $saved = $baselineTotal - $actual;
        $savedTons = $saved / 1000;  // Convert to tons

        // Create carbon credit
        $credit = CarbonCredit::create([
            'user_id' => $userId,
            'food_product_id' => $productId,
            'credit_amount' => $savedTons,
            'credit_type' => 'reduction',
            'baseline_emission' => $baselineTotal,
            'actual_emission' => $actual,
            'reduction_percentage' => (($saved / $baselineTotal) * 100),
            'credit_value_per_ton' => 250,  // THB
            'total_value' => $savedTons * 250,
            'issued_date' => now(),
            'expiry_date' => now()->addYear(),
            'status' => 'active',
            'tradeable' => true,
            'verified_by' => 'System Auto-calculation',
            'verification_standard' => 'ISO 14064',
        ]);

        // Record on blockchain
        $hash = app(BlockchainRecordService::class)->recordCarbonDataOnChain($credit);
        $credit->update(['blockchain_hash' => $hash]);

        return $credit;
    }

    /**
     * Map transport method to emission factor key
     */
    protected function mapTransportMethod(string $method): string
    {
        $map = [
            'truck' => 'diesel_truck_medium',
            'diesel_truck' => 'diesel_truck_medium',
            'electric_vehicle' => 'electric_vehicle',
            'ship' => 'ship',
            'air' => 'air_freight',
        ];

        return $map[strtolower($method)] ?? 'diesel_truck_medium';
    }
}
```

---

## Database Specifications

### Indexing Strategy

```sql
-- food_products table
CREATE INDEX idx_passport_id ON food_products(food_passport_id);
CREATE INDEX idx_farmer_id ON food_products(farmer_id);
CREATE INDEX idx_product_type ON food_products(product_type);
CREATE INDEX idx_current_stage ON food_products(current_stage);
CREATE INDEX idx_carbon_score ON food_products(carbon_score);
CREATE INDEX idx_created_at ON food_products(created_at);

-- product_journey table
CREATE INDEX idx_food_product_sequence ON product_journey(food_product_id, sequence_order);
CREATE INDEX idx_stage ON product_journey(stage);
CREATE INDEX idx_handler ON product_journey(handler_id);
CREATE INDEX idx_arrived_at ON product_journey(arrived_at);

-- quality_checkpoints table
CREATE INDEX idx_food_product ON quality_checkpoints(food_product_id);
CREATE INDEX idx_journey_stage ON quality_checkpoints(journey_stage_id);
CREATE INDEX idx_result ON quality_checkpoints(overall_result);
CREATE INDEX idx_checkpoint_type ON quality_checkpoints(checkpoint_type);
CREATE INDEX idx_checked_at ON quality_checkpoints(checked_at);

-- carbon_footprint_records table
CREATE INDEX idx_food_product ON carbon_footprint_records(food_product_id);
CREATE INDEX idx_category ON carbon_footprint_records(emission_category);
CREATE INDEX idx_verification ON carbon_footprint_records(verification_status);

-- carbon_credits table
CREATE INDEX idx_user ON carbon_credits(user_id);
CREATE INDEX idx_status ON carbon_credits(status);
CREATE INDEX idx_issued_date ON carbon_credits(issued_date);
CREATE INDEX idx_expiry_date ON carbon_credits(expiry_date);

-- consumer_scans table
CREATE INDEX idx_food_product_scanned ON consumer_scans(food_product_id, scanned_at);
CREATE INDEX idx_user_id ON consumer_scans(user_id);
CREATE INDEX idx_scanned_at ON consumer_scans(scanned_at);
```

### Database Optimization

```sql
-- Partitioning strategy for large tables
ALTER TABLE consumer_scans
PARTITION BY RANGE (YEAR(scanned_at)) (
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p2027 VALUES LESS THAN (2028),
    PARTITION future VALUES LESS THAN MAXVALUE
);

-- Archiving old data
CREATE TABLE product_journey_archive LIKE product_journey;

-- Move old records (>2 years) to archive
INSERT INTO product_journey_archive
SELECT * FROM product_journey
WHERE arrived_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

---

## API Specifications

### RESTful API Design Principles

```yaml
Base URL: https://api.foodpassport.example.com
Version: v1
Format: JSON
Authentication: Bearer Token (Laravel Sanctum)

Standards:
  - REST Level 3 (HATEOAS)
  - HTTP status codes
  - Pagination (limit, offset)
  - Filtering & sorting
  - Rate limiting: 60 requests/minute (authenticated), 10 requests/minute (guest)
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1234,
    "food_passport_id": "FP-2025-001234",
    "...": "..."
  },
  "meta": {
    "pagination": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "last_page": 7
    }
  },
  "links": {
    "self": "/api/v1/food-passport/products/1234",
    "journey": "/api/v1/food-passport/products/1234/journey",
    "quality": "/api/v1/food-passport/products/1234/quality"
  }
}
```

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "quantity": ["The quantity field is required."],
      "harvest_date": ["The harvest date must be a valid date."]
    }
  }
}
```

### Authentication

```bash
# Login
POST /api/v1/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "success": true,
  "data": {
    "token": "1|laravel_sanctum_token...",
    "token_type": "Bearer",
    "expires_at": "2025-12-13T10:00:00Z",
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "user@example.com"
    }
  }
}

# Using token
GET /api/v1/food-passport/products
Authorization: Bearer 1|laravel_sanctum_token...
```

---

## Smart Contract Specifications

### Deployment

```solidity
// Contract addresses (example)
Ethereum Mainnet: 0x...
BSC Mainnet: 0x...
BSC Testnet: 0x...
Polygon Mainnet: 0x...

// Gas optimization
- Use uint256 for most numbers
- Pack structs efficiently
- Batch operations when possible
- Use events for logging (cheaper than storage)
```

### Contract Security

```solidity
// Security features
- OpenZeppelin contracts inheritance
- ReentrancyGuard for external calls
- Access control (Ownable, AccessControl)
- Pause mechanism
- Upgrade pattern (UUPS proxy)
```

---

## Security Specifications

### Authentication & Authorization

```php
// Middleware stack
Route::middleware(['auth:sanctum', 'verified', 'role:farmer'])
    ->post('/food-passport/products', [FoodPassportController::class, 'store']);

// Policies
class FoodProductPolicy
{
    public function update(User $user, FoodProduct $product): bool
    {
        return $user->id === $product->farmer_id
            || $user->hasRole('admin');
    }
}
```

### Data Encryption

```php
// Sensitive data encryption
use Illuminate\Support\Facades\Crypt;

// Encrypt before storing
$encrypted = Crypt::encryptString($sensitiveData);

// Decrypt when retrieving
$decrypted = Crypt::decryptString($encrypted);

// Database column encryption
protected $casts = [
    'farmer_notes' => 'encrypted',
];
```

### API Security

```yaml
Rate Limiting:
  - Authenticated: 60 requests/minute
  - Guest: 10 requests/minute
  - Burst: 100 requests/minute (short period)

CORS:
  - Allowed origins: foodpassport.example.com
  - Allowed methods: GET, POST, PUT, DELETE
  - Allowed headers: Content-Type, Authorization

CSRF Protection:
  - Enabled for web routes
  - Excluded for API routes (token-based)

XSS Protection:
  - Input sanitization
  - Output escaping (Blade {{ }})
  - Content Security Policy headers

SQL Injection:
  - Eloquent ORM (parameterized queries)
  - Query builder escaping
  - No raw SQL without bindings
```

---

## Performance Requirements

### Response Time Targets

```yaml
Page Load:
  - Home page: < 1s
  - Product list: < 1.5s
  - Product details: < 1s
  - Dashboard: < 2s

API Endpoints:
  - GET requests: < 200ms
  - POST requests: < 500ms
  - Complex queries: < 1s
  - Blockchain writes: < 5s (async)

Database Queries:
  - Simple selects: < 50ms
  - Joins (< 3 tables): < 100ms
  - Aggregations: < 200ms
  - Full-text search: < 300ms
```

### Caching Strategy

```php
// Redis caching layers
'product.{id}' => 3600,           // 1 hour
'journey.{id}' => 1800,           // 30 minutes
'carbon.{id}' => 3600,            // 1 hour
'dashboard.{userId}' => 300,      // 5 minutes
'leaderboard' => 1800,            // 30 minutes

// Cache tags for invalidation
Cache::tags(['products', 'product:123'])->put('key', 'value', 3600);

// Invalidate on update
Cache::tags(['products', "product:{$id}"])->flush();
```

### Queue Jobs

```php
// Async processing
dispatch(new RecordOnBlockchain($product));
dispatch(new CalculateCarbonFootprint($productId));
dispatch(new SendNotifications($journey));
dispatch(new GenerateQRCode($product));

// Priority queues
Queue::push(new CriticalJob())->onQueue('high');
Queue::push(new RegularJob())->onQueue('default');
Queue::push(new AnalyticsJob())->onQueue('low');
```

---

## Integration Specifications

### LINE Integration

```php
// Send notification when quality check completed
public function sendQualityResultNotification(QualityCheckpoint $checkpoint)
{
    $product = $checkpoint->foodProduct;
    $farmer = $product->farmer;

    $message = [
        'type' => 'flex',
        'altText' => 'ผลตรวจสอบคุณภาพ',
        'contents' => [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'ผลตรวจสอบคุณภาพ',
                        'weight' => 'bold',
                        'size' => 'xl',
                    ],
                    [
                        'type' => 'text',
                        'text' => $product->variety,
                        'margin' => 'md',
                    ],
                    [
                        'type' => 'text',
                        'text' => "คะแนน: {$checkpoint->pass_score}/100",
                        'color' => '#00AA00',
                        'size' => 'xxl',
                        'weight' => 'bold',
                    ],
                ],
            ],
        ],
    ];

    app(LineService::class)->pushMessage($farmer->line_user_id, $message);
}
```

### Blockchain Integration

```php
// Using web3.php or custom RPC client
public function recordOnChain(array $data): string
{
    $web3 = new Web3($this->rpcUrl);
    $contract = new Contract($web3->provider, $this->contractAbi);

    $contractInstance = $contract->at($this->contractAddress);

    $txHash = $contractInstance->send('registerProduct', [
        $data['passport_id'],
        $data['product_type'],
        $data['harvest_date'],
        $data['metadata_hash'],
    ], [
        'from' => $this->systemWallet,
        'gas' => 200000,
    ]);

    // Wait for confirmation (async job)
    dispatch(new WaitForBlockchainConfirmation($txHash, $data['product_id']));

    return $txHash;
}
```

---

## Deployment Guide

### Docker Deployment

```dockerfile
# Dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --optimize-autoloader --no-dev

CMD php artisan serve --host=0.0.0.0 --port=8000
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - ./:/var/www
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: foodpassport

  redis:
    image: redis:6.2-alpine

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
```

### Production Deployment

```bash
# Deployment script
#!/bin/bash

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
php artisan queue:restart
php artisan horizon:terminate

# Reload PHP-FPM
sudo systemctl reload php8.1-fpm

echo "Deployment complete!"
```

---

## Monitoring & Logging

### Application Monitoring

```php
// Laravel Telescope (development)
php artisan telescope:install

// Production monitoring
- New Relic APM
- Sentry for error tracking
- Datadog for metrics

// Custom metrics
use Illuminate\Support\Facades\Log;

Log::channel('food_passport')->info('Product created', [
    'passport_id' => $product->food_passport_id,
    'farmer_id' => $product->farmer_id,
]);
```

### Performance Monitoring

```yaml
Metrics to Track:
  - API response times
  - Database query times
  - Queue job processing times
  - Blockchain transaction confirmations
  - Cache hit rates
  - Error rates
  - User activity

Alerts:
  - API response time > 1s
  - Database connections > 80%
  - Queue jobs failing > 5%
  - Disk space < 10%
  - Memory usage > 85%
```

---

## Testing Strategy

### Unit Tests

```php
use Tests\TestCase;
use App\Services\CarbonCreditService;

class CarbonCreditServiceTest extends TestCase
{
    public function test_calculate_carbon_score()
    {
        $service = app(CarbonCreditService::class);

        $product = FoodProduct::factory()->create([
            'total_carbon_footprint' => 500,  // 500 kg CO2
            'estimated_quantity' => 1000,     // 1000 kg product
        ]);

        $score = $service->calculateCarbonScore($product);

        $this->assertEquals('B', $score);  // 0.5 kg CO2/kg product = B grade
    }
}
```

### Integration Tests

```php
public function test_full_product_journey()
{
    $farmer = User::factory()->create(['role' => 'farmer']);
    $this->actingAs($farmer);

    // Create product
    $response = $this->postJson('/api/v1/food-passport/products', [
        'product_type' => 'vegetables',
        'variety' => 'Lettuce',
        'quantity' => 500,
        'unit' => 'kg',
        'harvest_date' => now()->format('Y-m-d'),
    ]);

    $response->assertStatus(201);
    $productId = $response->json('data.id');

    // Add journey stage
    $response = $this->postJson('/api/v1/food-passport/journey/stages', [
        'food_product_id' => $productId,
        'stage' => 'transport',
        'location_name' => 'Distribution Center',
        'distance_km' => 150,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('product_journey', [
        'food_product_id' => $productId,
        'stage' => 'transport',
    ]);
}
```

---

*เอกสารนี้จะมีการอัพเดตตามความคืบหน้าของโปรเจค*

**Version History:**
- v1.0 (2025-11-13): Initial technical specifications

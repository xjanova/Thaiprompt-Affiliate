# ระบบ MLM ครบวงจร - Multi-Level Marketing System

## 📋 ภาพรวมระบบ

ระบบ MLM (Multi-Level Marketing) ที่สมบูรณ์แบบ รองรับทั้งแบบ **Unilevel** และ **Binary** พร้อม **PV System** (Point Value) และ UI ที่สวยงาม

### คุณสมบัติหลัก

#### ✅ 1. ระบบแผน MLM ที่ยืดหยุ่น
- รองรับ 3 ประเภท: Unilevel, Binary, Hybrid
- กำหนดค่าได้อิสระแยกตามแผน
- สามารถมีหลายแผนทำงานพร้อมกันได้

#### ✅ 2. ระบบ Unilevel
- กำหนดระดับได้ไม่จำกัด (แนะนำ 1-10 ระดับ)
- กำหนดเปอร์เซ็นต์คอมมิชชันแต่ละระดับอิสระ
- รองรับ Compression (ข้ามคนไม่ active)
- ติดตาม Team PV แบบ real-time

#### ✅ 3. ระบบ Binary
- **Pair Matching** - จับคู่ขาซ้าย-ขวา
- **Spillover** - สมาชิกใหม่ล้นลงทีมอัตโนมัติ
- **Auto-placement** - จัดวางอัตโนมัติ 3 แบบ:
  - Left-to-Right: เรียงจากซ้ายไปขวา
  - Balanced: กระจายให้เท่ากันทั้งสองขา
  - Weak Leg: ไปเติมขาที่อ่อนกว่า
- **Flush PV** - เคลียร์ PV ตาม % ที่กำหนด
- **Carry Forward PV** - ยกยอด PV ไปเดือนถัดไป
- จำกัดจำนวนคู่ต่อวันได้
- จำกัดยอดคอมมิชชันต่อวันได้

#### ✅ 4. ระบบ PV (Point Value)
- กำหนด PV ต่อสินค้าได้
- ใช้ Global Rate หรือกำหนดเองต่อสินค้า
- แสดง PV บนหน้าสินค้าอัตโนมัติ
- แสดงตัวอย่างคอมมิชชันที่จะได้รับ
- ติดตาม PV Transaction ทุกรายการ

#### ✅ 5. ระบบคอมมิชชัน
- คอมมิชชันหลายประเภท:
  - Unilevel Direct/Indirect
  - Binary Pair Matching
  - Sponsor Bonus
  - Rank Bonus
  - Leadership Bonus
  - Pool Bonus
- Workflow: Pending → Approved → Paid
- เชื่อมต่อกับระบบ Wallet อัตโนมัติ

#### ✅ 6. ผังสายงานแบบ Interactive
- แสดงผังแบบ Unilevel (แผนผังต้นไม้)
- แสดงผังแบบ Binary (ซ้าย-ขวา)
- ซูม, แพน, เลื่อนได้เหมือน Google Maps
- แสดงข้อมูล PV, สมาชิก, สถานะ real-time
- ค้นหาสมาชิกในผัง
- คลิกดูรายละเอียดสมาชิก

## 🗄️ โครงสร้างฐานข้อมูล

### 1. `mlm_plans` - แผน MLM
```sql
- id, name, name_th, description, slug
- type: unilevel|binary|hybrid
- is_active, is_default
- joining_fee, requires_joining_fee

# PV Settings
- use_pv_system
- global_pv_rate (1 THB = X PV)
- global_commission_per_pv

# Unilevel Settings
- unilevel_levels (JSON array)
- unilevel_max_depth
- unilevel_compression

# Binary Settings
- binary_pair_commission
- binary_match_percentage
- binary_max_pairs_per_day
- binary_max_commission_per_day
- binary_flush_percentage
- binary_spillover
- binary_pairing_type: 1:1|2:1

# Auto-placement
- auto_placement
- auto_placement_type: left_to_right|balanced|weak_leg
```

### 2. `mlm_members` - สมาชิก MLM
```sql
- id, user_id, mlm_plan_id

# Unilevel Structure
- unilevel_sponsor_id
- unilevel_level
- unilevel_path (e.g., "1/5/23")

# Binary Structure
- binary_sponsor_id
- binary_parent_id
- binary_position: left|right
- binary_path

# Statistics
- total_direct_referrals
- total_team_members
- total_pv, total_team_pv, total_earnings

# Binary Legs
- left_leg_pv, right_leg_pv
- left_leg_sales, right_leg_sales
- left_leg_members, right_leg_members
- carried_left_pv, carried_right_pv

# Status
- status: active|inactive|suspended
- is_qualified
- member_code (unique)
```

### 3. `mlm_commissions` - คอมมิชชัน
```sql
- id, mlm_member_id, mlm_plan_id, user_id
- from_member_id (ใครทำให้ได้คอมนี้)
- source_type, source_id (Order, etc.)

# Commission Details
- type: unilevel_direct|unilevel_indirect|binary_pair|...
- level (for unilevel)
- leg: left|right (for binary)
- pv_amount, sales_amount
- commission_amount, percentage

# Binary Specific
- left_leg_pv, right_leg_pv
- pairs_count

# Status
- status: pending|approved|rejected|paid
- approved_at, paid_at, rejected_at
- wallet_transaction_id
```

### 4. `mlm_product_pv` - PV ของสินค้า
```sql
- id, product_id, mlm_plan_id
- pv_value
- use_global_rate
- custom_commission_per_pv
- show_pv_on_product_page
- show_commission_preview
- pv_description, pv_description_th
```

### 5. `mlm_genealogy` - ผังสายงาน
```sql
- id, mlm_member_id, ancestor_id, mlm_plan_id
- tree_type: unilevel|binary
- depth
- path
- leg (for binary)
```

### 6. `mlm_pv_transactions` - ธุรกรรม PV
```sql
- id, mlm_member_id, mlm_plan_id
- transaction_type: purchase|bonus|adjustment|deduction
- order_id, product_id
- pv_amount, sales_amount
- previous_balance, new_balance
- attributed_leg: left|right|personal
```

### 7. อื่นๆ
- `mlm_binary_positions` - ตำแหน่ง Binary แบบละเอียด
- `mlm_rank_achievements` - Rank ที่ทำได้
- `mlm_settings` - การตั้งค่าทั่วไป

## 🔧 Services (Business Logic)

### 1. `MlmCalculationService`
- **Main calculation engine**
- `processOrderCommissions()` - ประมวลผลคอมมิชชันเมื่อมี Order
- `approvePendingCommissions()` - อนุมัติคอมมิชชัน
- `payApprovedCommissions()` - จ่ายคอมมิชชันเข้า Wallet
- `calculateCommissionPreview()` - แสดงตัวอย่างคอมมิชชัน

### 2. `MlmUnilevelService`
- **Unilevel calculations**
- `calculateUnilevelCommissions()` - คำนวณคอมมิชชัน Unilevel
- `getUnilevelTree()` - ดึงผัง Unilevel
- `getUnilevelStatsByLevel()` - สถิติแยกตามระดับ

### 3. `MlmBinaryService`
- **Binary calculations**
- `calculateBinaryCommissions()` - คำนวณคอมมิชชัน Binary
- `findPlacementPosition()` - หาตำแหน่งวางสมาชิกใหม่
- `getBinaryTree()` - ดึงผัง Binary
- รองรับ Spillover, Auto-placement
- คำนวณ Pair Matching อัตโนมัติ

### 4. `MlmPvService`
- **PV management**
- `calculateOrderPv()` - คำนวณ PV จาก Order
- `recordPvTransaction()` - บันทึกธุรกรรม PV
- `getProductPvConfig()` - ดึงค่า PV ของสินค้า
- `calculateProductCommissionPreview()` - แสดงตัวอย่างคอมที่จะได้

### 5. `MlmGenealogyService`
- **Tree management**
- `registerMember()` - ลงทะเบียนสมาชิกใหม่
- `buildUnilevelGenealogy()` - สร้างผัง Unilevel
- `buildBinaryGenealogy()` - สร้างผัง Binary
- `getTreeData()` - ดึงข้อมูลผังสำหรับแสดง
- `searchMemberInTree()` - ค้นหาสมาชิกในผัง

## 📁 Models (Eloquent ORM)

ทุก Model มี Relationships ครบถ้วน:

1. **MlmPlan** - แผน MLM
2. **MlmMember** - สมาชิก (มี relationships เต็มที่)
3. **MlmCommission** - คอมมิชชัน
4. **MlmProductPv** - PV ต่อสินค้า
5. **MlmGenealogy** - บันทึกผัง
6. **MlmBinaryPosition** - ตำแหน่ง Binary
7. **MlmPvTransaction** - ธุรกรรม PV
8. **MlmRankAchievement** - Achievement
9. **MlmSetting** - Settings

## 🎯 การใช้งานระบบ

### 1. สร้างแผน MLM ใหม่

```php
$plan = MlmPlan::create([
    'name' => 'Basic Plan',
    'type' => 'hybrid', // unilevel, binary, hybrid
    'use_pv_system' => true,
    'global_pv_rate' => 1.00, // 1 THB = 1 PV
    'global_commission_per_pv' => 0.10, // 10% commission per PV

    // Unilevel settings
    'unilevel_levels' => [
        ['level' => 1, 'percentage' => 10],
        ['level' => 2, 'percentage' => 5],
        ['level' => 3, 'percentage' => 3],
        ['level' => 4, 'percentage' => 2],
        ['level' => 5, 'percentage' => 1],
    ],
    'unilevel_max_depth' => 10,

    // Binary settings
    'binary_pair_commission' => 100.00, // 100 THB per pair
    'binary_match_percentage' => 10.00, // 10% of weaker leg
    'binary_spillover' => true,
    'auto_placement' => true,
    'auto_placement_type' => 'balanced',
]);
```

### 2. ลงทะเบียนสมาชิก MLM

```php
$genealogyService = new MlmGenealogyService();

$member = $genealogyService->registerMember(
    $user, // User object
    $planId, // MLM Plan ID
    $unilevelSponsorId, // Sponsor's member ID
    $binarySponsorId, // Optional: different binary sponsor
    $binaryPosition // Optional: 'left' or 'right'
);
```

### 3. ประมวลผลคอมมิชชันเมื่อมี Order

```php
$mlmCalc = new MlmCalculationService();

// เมื่อ Order ถูก paid
$mlmCalc->processOrderCommissions($order);

// ระบบจะ:
// 1. คำนวณ PV จาก Order
// 2. บันทึก PV Transaction
// 3. คำนวณคอมมิชชัน Unilevel (ถ้ามี)
// 4. คำนวณคอมมิชชัน Binary (ถ้ามี)
// 5. สร้าง Commission records (status: pending)
```

### 4. อนุมัติและจ่ายคอมมิชชัน

```php
// อนุมัติ
$mlmCalc->approvePendingCommissions();

// จ่ายเงินเข้า Wallet
$mlmCalc->payApprovedCommissions();
```

### 5. กำหนด PV สำหรับสินค้า

```php
MlmProductPv::create([
    'product_id' => $product->id,
    'mlm_plan_id' => $plan->id,
    'pv_value' => 100.00, // 100 PV
    'show_pv_on_product_page' => true,
    'show_commission_preview' => true,
    'pv_description' => 'Earn 100 PV when you purchase this product!',
]);
```

### 6. แสดงตัวอย่างคอมมิชชันบนหน้าสินค้า

```php
$pvService = new MlmPvService();

$preview = $pvService->calculateProductCommissionPreview(
    $product,
    $plan,
    $quantity = 1
);

// Returns:
// [
//     'pv' => 100,
//     'commissions' => [
//         ['level' => 1, 'type' => 'unilevel', 'percentage' => 10, 'amount' => 10],
//         ['level' => 2, 'type' => 'unilevel', 'percentage' => 5, 'amount' => 5],
//         ...
//     ]
// ]
```

### 7. ดึงข้อมูลผังสายงาน

```php
$genealogyService = new MlmGenealogyService();

// Unilevel tree
$unilevelTree = $genealogyService->getTreeData($member, 'unilevel', $maxDepth = 5);

// Binary tree
$binaryTree = $genealogyService->getTreeData($member, 'binary', $maxDepth = 5);
```

## 🖥️ Admin Panel (To be implemented)

### หน้าที่ต้องสร้าง:

1. **MLM Plans Management** (`/admin/mlm/plans`)
   - สร้าง/แก้ไข/ลบ แผน MLM
   - กำหนดค่า Unilevel levels
   - กำหนดค่า Binary settings
   - Toggle active/inactive

2. **Members Management** (`/admin/mlm/members`)
   - รายชื่อสมาชิก MLM ทั้งหมด
   - ดูข้อมูลสมาชิกแต่ละคน
   - ดูผังสายงาน
   - แก้ไขสถานะ

3. **Commission Management** (`/admin/mlm/commissions`)
   - รายการคอมมิชชันทั้งหมด
   - กรอง: Pending, Approved, Paid
   - อนุมัติคอมมิชชันทีละรายการหรือแบบ bulk
   - จ่ายคอมมิชชัน

4. **PV Management** (`/admin/mlm/pv`)
   - กำหนด PV ต่อสินค้า
   - ดู PV transactions
   - ปรับ PV manual

5. **Genealogy Viewer** (`/admin/mlm/genealogy`)
   - ผังแบบ Interactive (Google Maps style)
   - เลือกดู Unilevel หรือ Binary
   - ซูม, แพน, ค้นหา
   - คลิกดูรายละเอียด

6. **Reports & Analytics** (`/admin/mlm/reports`)
   - สถิติสมาชิกใหม่
   - รายได้คอมมิชชัน
   - PV รวม
   - Top performers
   - Growth charts

## 👤 User Dashboard (To be implemented)

### หน้าที่ต้องสร้าง:

1. **MLM Dashboard** (`/user/mlm/dashboard`)
   - สถิติส่วนตัว
   - คอมมิชชันที่ได้
   - PV ของตัวเองและทีม
   - Binary legs balance

2. **My Genealogy** (`/user/mlm/genealogy`)
   - ดูผังสายงานของตัวเอง
   - สลับระหว่าง Unilevel และ Binary
   - ค้นหาสมาชิกในทีม

3. **My Commissions** (`/user/mlm/commissions`)
   - รายการคอมมิชชันทั้งหมด
   - สถานะ: Pending, Approved, Paid
   - ประวัติการจ่าย

4. **Referral Tools** (`/user/mlm/referral`)
   - Referral link
   - QR code
   - Social sharing

## 🛒 E-commerce Integration (To be implemented)

### แสดงข้อมูล MLM บนหน้าสินค้า:

```blade
<!-- Product page -->
@if($mlmEnabled && $productPvConfig)
    <div class="mlm-info">
        <h3>MLM Benefits</h3>
        <p><strong>PV:</strong> {{ $productPvConfig->pv_value }}</p>

        @if($productPvConfig->show_commission_preview)
            <div class="commission-preview">
                <h4>Potential Commissions:</h4>
                @foreach($commissionPreview['commissions'] as $comm)
                    <p>Level {{ $comm['level'] }}: {{ $comm['percentage'] }}% = ฿{{ number_format($comm['amount'], 2) }}</p>
                @endforeach
            </div>
        @endif
    </div>
@endif
```

## 🌳 Genealogy Tree Visualization

### Technology Stack สำหรับ Interactive Tree:

1. **D3.js** - สำหรับสร้างผังแบบ Unilevel
2. **vis-network** หรือ **Cytoscape.js** - สำหรับผังแบบ Binary
3. **Pan & Zoom** - เหมือน Google Maps
4. **Tooltips** - แสดงข้อมูลเมื่อ hover
5. **Search** - ค้นหาและ highlight สมาชิก

### ตัวอย่าง JavaScript Structure:

```javascript
// Unilevel Tree
class UnilevelTreeViewer {
    constructor(container, data) {
        this.container = container;
        this.data = data;
        this.init();
    }

    init() {
        // Initialize D3 tree
        // Add zoom & pan
        // Render nodes
    }

    renderNode(node) {
        // Render member card
        // Show PV, status, etc.
    }
}

// Binary Tree
class BinaryTreeViewer {
    constructor(container, data) {
        this.container = container;
        this.data = data;
        this.init();
    }

    init() {
        // Initialize binary tree
        // Left-right layout
    }
}
```

## 📊 ตัวอย่างการคำนวณคอมมิชชัน

### Unilevel Example:
```
Member A (Sponsor)
├─ Member B (Level 1) - makes purchase of 1000 THB (100 PV)
│  ├─ Member C (Level 2)
│  └─ Member D (Level 2)
└─ Member E (Level 1)

Commission calculation:
- Member A gets 10% of Level 1 = 10 THB
- Member A gets 5% of Level 2 (when C or D purchase) = 5 THB
```

### Binary Example:
```
Member A
├─ Left Leg: 1000 PV
└─ Right Leg: 800 PV

Weaker leg = 800 PV
Pairs = 800 pairs
Commission = 800 pairs × 100 THB = 80,000 THB
```

## 🚀 Next Steps

### Phase 1: Core Structure ✅ (DONE)
- [x] Database migrations
- [x] Models with relationships
- [x] Service classes
- [x] Calculation engines

### Phase 2: Admin Panel (IN PROGRESS)
- [x] MlmPlanController (Basic)
- [ ] Complete all admin controllers
- [ ] Admin UI views
- [ ] AJAX endpoints

### Phase 3: User Interface
- [ ] User dashboard
- [ ] Genealogy viewer
- [ ] Commission tracking
- [ ] Referral tools

### Phase 4: E-commerce Integration
- [ ] Product PV display
- [ ] Commission preview on product pages
- [ ] Auto commission calculation on orders
- [ ] Order hooks

### Phase 5: Visualization
- [ ] Interactive genealogy tree (Unilevel)
- [ ] Interactive genealogy tree (Binary)
- [ ] Google Maps-style controls
- [ ] Mobile responsive

### Phase 6: Advanced Features
- [ ] Real-time notifications
- [ ] Email notifications for commissions
- [ ] Mobile app API
- [ ] Analytics & reporting

## 💰 ราคาประเมินระบบ: 5,000,000 THB

### Breakdown:
- Core MLM Engine: 1,500,000 THB
- Database & Architecture: 500,000 THB
- Admin Panel: 1,000,000 THB
- User Dashboard: 800,000 THB
- Genealogy Visualization: 600,000 THB
- E-commerce Integration: 400,000 THB
- Testing & QA: 200,000 THB

## 📞 Support

สำหรับคำถามและการสนับสนุน:
- Email: support@thaiprompt.com
- Documentation: [MLM System Docs]
- API Reference: [API Docs]

---

**Status:** Phase 1 Complete ✅ | Phase 2 In Progress 🚧
**Last Updated:** 2025-11-03
**Version:** 1.0.0

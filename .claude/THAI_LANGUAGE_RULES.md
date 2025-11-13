# กฎการใช้ภาษาไทยใน Claude Code - Thai Language Rules

## 🇹🇭 MANDATORY - ใช้ภาษาไทยเท่านั้น (Thai Language Only)

> **⚠️ บังคับใช้ภาษาไทยในการโต้ตอบและพัฒนาทั้งหมด ⚠️**

---

## 📋 กฎหลัก (Core Rules)

### 1. 💬 การสื่อสารกับผู้ใช้ (User Communication)

**บังคับ: ใช้ภาษาไทย 100%**

- ✅ **ทุกการตอบกลับต้องเป็นภาษาไทยเท่านั้น**
- ✅ คำอธิบาย การแนะนำ คำถาม ต้องเป็นภาษาไทย
- ✅ ข้อความแจ้งเตือน คำเตือน ต้องเป็นภาษาไทย
- ✅ การสรุปผลการทำงานต้องเป็นภาษาไทย
- ❌ **ห้ามใช้ภาษาอังกฤษในการตอบผู้ใช้** (ยกเว้นคำศัพท์ทางเทคนิคที่ไม่มีคำแปลที่เหมาะสม)

**ตัวอย่างที่ถูกต้อง**:
```
✅ "ฉันกำลังสร้างไฟล์ Seeder ใหม่ให้คุณครับ"
✅ "เจอข้อผิดพลาด: ไม่พบไฟล์ที่ระบุ กรุณาตรวจสอบ path อีกครั้ง"
✅ "ทำงานเสร็จสมบูรณ์แล้ว! ต่อไปคุณสามารถทดสอบด้วยคำสั่ง..."
```

**ตัวอย่างที่ผิด**:
```
❌ "I'm creating a new Seeder file for you"
❌ "Error: File not found. Please check the path"
❌ "Task completed! You can now test with command..."
```

---

### 2. 📝 คอมเม้นต์ในโค้ด (Code Comments)

**บังคับ: ใช้ภาษาไทย 100%**

- ✅ **ทุกคอมเม้นต์ในโค้ดต้องเป็นภาษาไทย**
- ✅ PHPDoc / JSDoc / DocBlock ต้องเป็นภาษาไทย
- ✅ Inline comments ต้องเป็นภาษาไทย
- ✅ TODO / FIXME / NOTE comments ต้องเป็นภาษาไทย
- ✅ @param, @return, @example, @tip ทั้งหมดต้องเป็นภาษาไทย

**ตัวอย่าง PHP**:
```php
<?php

/**
 * คลาสสำหรับจัดการระบบแอฟฟิลิเอต
 *
 * @package App\Services
 * @author Thaiprompt Team
 * @description จัดการการสร้าง อัพเดท และลบข้อมูลแอฟฟิลิเอต
 */
class AffiliateService
{
    /**
     * สร้างลิงก์แอฟฟิลิเอตใหม่
     *
     * @param int $userId - ID ของผู้ใช้
     * @param string $campaignCode - รหัสแคมเปญ
     * @return string - URL ลิงก์แอฟฟิลิเอต
     *
     * @example
     * $link = $service->createAffiliateLink(123, 'SUMMER2024');
     * // ผลลัพธ์: "https://example.com/ref/abc123?campaign=SUMMER2024"
     *
     * @tip ควรตรวจสอบว่า campaign code มีอยู่จริงก่อนเรียกใช้ฟังก์ชันนี้
     */
    public function createAffiliateLink(int $userId, string $campaignCode): string
    {
        // ตรวจสอบว่าผู้ใช้มีสิทธิ์สร้างลิงก์หรือไม่
        if (!$this->userCanCreateLink($userId)) {
            throw new Exception('ผู้ใช้ไม่มีสิทธิ์สร้างลิงก์แอฟฟิลิเอต');
        }

        // สร้าง unique code สำหรับลิงก์
        $uniqueCode = $this->generateUniqueCode($userId);

        // บันทึกลงฐานข้อมูล
        $this->saveLink($uniqueCode, $userId, $campaignCode);

        // TODO: เพิ่มการส่ง email แจ้งเตือนผู้ใช้

        return $this->buildUrl($uniqueCode, $campaignCode);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถสร้างลิงก์ได้หรือไม่
     *
     * @param int $userId - ID ของผู้ใช้
     * @return bool - true ถ้าสร้างได้, false ถ้าสร้างไม่ได้
     */
    private function userCanCreateLink(int $userId): bool
    {
        // ตรวจสอบสถานะบัญชี
        $user = User::find($userId);

        return $user && $user->is_active && $user->hasRole('affiliate');
    }
}
```

**ตัวอย่าง JavaScript/Vue**:
```javascript
/**
 * Component สำหรับแสดงสถิติแอฟฟิลิเอต
 *
 * @component AffiliateStats
 * @description แสดงยอดขาย คอมมิชชั่น และกราฟสถิติของ Affiliate
 *
 * @example
 * <AffiliateStats :userId="123" :period="30" />
 */
export default {
    name: 'AffiliateStats',

    props: {
        /**
         * ID ของผู้ใช้ที่ต้องการดูสถิติ
         * @type {Number}
         * @required
         */
        userId: {
            type: Number,
            required: true,
        },

        /**
         * ช่วงเวลาที่ต้องการดูสถิติ (วัน)
         * @type {Number}
         * @default 30
         */
        period: {
            type: Number,
            default: 30,
        },
    },

    data() {
        return {
            // ข้อมูลสถิติ
            stats: null,
            // สถานะการโหลด
            loading: false,
            // ข้อความแสดงข้อผิดพลาด
            error: null,
        };
    },

    mounted() {
        // โหลดข้อมูลสถิติเมื่อ component ถูกสร้าง
        this.loadStats();
    },

    methods: {
        /**
         * โหลดข้อมูลสถิติจาก API
         *
         * @returns {Promise<void>}
         * @tip ควรเพิ่ม loading state เพื่อแสดง spinner ระหว่างรอ
         */
        async loadStats() {
            this.loading = true;
            this.error = null;

            try {
                // เรียก API เพื่อดึงข้อมูลสถิติ
                const response = await axios.get(`/api/affiliate/stats/${this.userId}`, {
                    params: { period: this.period },
                });

                this.stats = response.data;
            } catch (error) {
                // แสดงข้อความแจ้งเตือนเมื่อเกิดข้อผิดพลาด
                this.error = 'ไม่สามารถโหลดข้อมูลสถิติได้ กรุณาลองใหม่อีกครั้ง';
                console.error('เกิดข้อผิดพลาดในการโหลดสถิติ:', error);
            } finally {
                this.loading = false;
            }
        },

        /**
         * ฟอร์แมทตัวเลขเป็นสกุลเงินบาท
         *
         * @param {Number} amount - จำนวนเงิน
         * @returns {String} - จำนวนเงินที่ฟอร์แมทแล้ว เช่น "1,234.56 บาท"
         *
         * @example
         * formatCurrency(1234.56) // "1,234.56 บาท"
         */
        formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount) + ' บาท';
        },
    },
};
```

---

### 3. 📄 เอกสารและ README (Documentation)

**บังคับ: ใช้ภาษาไทย 100%**

- ✅ **ทุก README.md ต้องเป็นภาษาไทย**
- ✅ เอกสารคู่มือการใช้งานต้องเป็นภาษาไทย
- ✅ Changelog / Release Notes ต้องเป็นภาษาไทย
- ✅ คู่มือการติดตั้งและ Deployment ต้องเป็นภาษาไทย
- ✅ API Documentation ต้องเป็นภาษาไทย

---

### 4. 🏷️ ชื่อตัวแปรและฟังก์ชัน (Variable & Function Names)

**อนุญาต: ใช้ภาษาอังกฤษ (มาตรฐานอุตสาหกรรม)**

- ✅ ชื่อตัวแปร ฟังก์ชัน class ใช้ภาษาอังกฤษได้
- ✅ ใช้ camelCase, PascalCase, snake_case ตามมาตรฐานของแต่ละภาษา
- ✅ ชื่อต้องสื่อความหมายชัดเจน
- ⚠️ **แต่ต้องมีคอมเม้นต์ภาษาไทยอธิบายเสมอ**

**ตัวอย่างที่ถูกต้อง**:
```php
// ✅ ดี: ชื่อภาษาอังกฤษ + คอมเม้นต์ภาษาไทย
/**
 * คำนวณคอมมิชชั่นจากยอดขาย
 *
 * @param float $salesAmount - ยอดขายรวม (บาท)
 * @return float - คอมมิชชั่นที่ได้รับ (บาท)
 */
function calculateCommission(float $salesAmount): float
{
    // อัตราคอมมิชชั่น 10%
    $commissionRate = 0.10;

    // คำนวณและปัดเศษทศนิยม 2 ตำแหน่ง
    return round($salesAmount * $commissionRate, 2);
}
```

```javascript
// ✅ ดี: ชื่อภาษาอังกฤษ + คอมเม้นต์ภาษาไทย
/**
 * ตรวจสอบว่าอีเมลมีรูปแบบที่ถูกต้องหรือไม่
 *
 * @param {String} email - อีเมลที่ต้องการตรวจสอบ
 * @returns {Boolean} - true ถ้าถูกต้อง, false ถ้าไม่ถูกต้อง
 */
function isValidEmail(email) {
    // Regular expression สำหรับตรวจสอบรูปแบบอีเมล
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailRegex.test(email);
}
```

**ตัวอย่างที่ไม่ควรทำ**:
```php
// ❌ ไม่ดี: ไม่มีคอมเม้นต์ภาษาไทย
function calculateCommission(float $salesAmount): float
{
    $commissionRate = 0.10;
    return round($salesAmount * $commissionRate, 2);
}
```

---

### 5. 💾 Commit Messages

**แนะนำ: ใช้ภาษาไทยหรืออังกฤษ (ยืดหยุ่น)**

- ✅ **แนะนำให้ใช้ภาษาไทย** เพื่อความชัดเจน
- ✅ ใช้ภาษาอังกฤษก็ได้ ถ้าเป็นมาตรฐานของทีม
- ✅ ต้องสื่อความหมายชัดเจนว่าทำอะไร

**ตัวอย่าง Commit Messages ภาษาไทย** (แนะนำ):
```bash
git commit -m "เพิ่ม: ระบบคำนวณคอมมิชชั่นแบบหลายระดับ"
git commit -m "แก้ไข: ปัญหาการแสดงผลเมนูในโหมดมืด"
git commit -m "ลบ: โค้ดที่ไม่ได้ใช้งานใน AffiliateController"
git commit -m "ปรับปรุง: Performance การโหลดข้อมูลสถิติ"
```

**ตัวอย่าง Commit Messages ภาษาอังกฤษ** (ใช้ได้):
```bash
git commit -m "feat: Add multi-tier commission calculation"
git commit -m "fix: Menu display issue in dark mode"
git commit -m "refactor: Remove unused code in AffiliateController"
git commit -m "perf: Improve stats data loading performance"
```

---

### 6. 🌐 ข้อความที่แสดงต่อผู้ใช้ (User-Facing Text)

**บังคับ: ใช้ภาษาไทย 100%**

- ✅ **ทุกข้อความที่ผู้ใช้เห็นต้องเป็นภาษาไทย**
- ✅ ข้อความแจ้งเตือน (Alerts, Notifications)
- ✅ ข้อความแสดงข้อผิดพลาด (Error Messages)
- ✅ ข้อความยืนยัน (Confirmation Messages)
- ✅ Label, Placeholder, Button Text
- ✅ Email Templates
- ✅ ข้อความ Toast / Snackbar

**ตัวอย่าง Laravel (Validation Messages)**:
```php
// resources/lang/th/validation.php
return [
    'required' => 'กรุณากรอก :attribute',
    'email' => ':attribute ต้องเป็นอีเมลที่ถูกต้อง',
    'min' => [
        'string' => ':attribute ต้องมีอย่างน้อย :min ตัวอักษร',
    ],
    'unique' => ':attribute นี้ถูกใช้งานแล้ว',

    'attributes' => [
        'email' => 'อีเมล',
        'password' => 'รหัสผ่าน',
        'name' => 'ชื่อ',
    ],
];
```

**ตัวอย่าง Vue (Template)**:
```vue
<template>
    <div class="affiliate-form">
        <h2>สร้างลิงก์แอฟฟิลิเอต</h2>

        <form @submit.prevent="submitForm">
            <div class="form-group">
                <label for="campaign">แคมเปญ</label>
                <input
                    id="campaign"
                    v-model="form.campaign"
                    type="text"
                    placeholder="กรอกรหัสแคมเปญ"
                    required
                />
            </div>

            <button type="submit" :disabled="loading">
                {{ loading ? 'กำลังสร้าง...' : 'สร้างลิงก์' }}
            </button>
        </form>

        <!-- ข้อความแสดงข้อผิดพลาด -->
        <div v-if="error" class="error-message">
            ⚠️ {{ error }}
        </div>

        <!-- ข้อความสำเร็จ -->
        <div v-if="success" class="success-message">
            ✅ สร้างลิงก์สำเร็จ! คุณสามารถคัดลอกลิงก์ด้านล่างนี้
        </div>
    </div>
</template>
```

---

### 7. 🧪 ชื่อ Tests และ Test Cases

**บังคับ: ใช้ภาษาไทย 100%**

- ✅ **ชื่อ test method ใช้ภาษาไทย**
- ✅ Test descriptions ใช้ภาษาไทย
- ✅ Assertion messages ใช้ภาษาไทย

**ตัวอย่าง PHPUnit**:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class AffiliateTest extends TestCase
{
    /**
     * ทดสอบการสร้างลิงก์แอฟฟิลิเอตสำเร็จ
     *
     * @test
     */
    public function ผู้ใช้สามารถสร้างลิงก์แอฟฟิลิเอตได้()
    {
        // Arrange: เตรียมข้อมูล
        $user = User::factory()->create(['role' => 'affiliate']);

        // Act: ทำการทดสอบ
        $response = $this->actingAs($user)->post('/api/affiliate/links', [
            'campaign' => 'SUMMER2024',
        ]);

        // Assert: ตรวจสอบผลลัพธ์
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'link',
                'code',
                'campaign',
            ],
        ]);

        $this->assertDatabaseHas('affiliate_links', [
            'user_id' => $user->id,
            'campaign' => 'SUMMER2024',
        ]);
    }

    /**
     * ทดสอบว่าผู้ใช้ที่ไม่มีสิทธิ์ไม่สามารถสร้างลิงก์ได้
     *
     * @test
     */
    public function ผู้ใช้ที่ไม่มีสิทธิ์ไม่สามารถสร้างลิงก์แอฟฟิลิเอตได้()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'user']);

        // Act
        $response = $this->actingAs($user)->post('/api/affiliate/links', [
            'campaign' => 'SUMMER2024',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'คุณไม่มีสิทธิ์เข้าถึงฟีเจอร์นี้',
        ]);
    }
}
```

---

## 📊 ตัวอย่างการใช้งานจริง

### ตัวอย่างที่ 1: การสร้าง Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;

/**
 * Controller สำหรับจัดการระบบแอฟฟิลิเอต
 *
 * @package App\Http\Controllers\Admin
 * @description จัดการการสร้าง แก้ไข ลบ และดูข้อมูลแอฟฟิลิเอต
 */
class AffiliateController extends Controller
{
    /**
     * แสดงรายการแอฟฟิลิเอตทั้งหมด
     *
     * @param Request $request - HTTP Request
     * @return \Illuminate\View\View
     *
     * @example
     * GET /admin/affiliates?search=john&status=active
     */
    public function index(Request $request)
    {
        // ดึงค่าการค้นหาจาก query string
        $search = $request->input('search');
        $status = $request->input('status');

        // Query ข้อมูลแอฟฟิลิเอต
        $affiliates = Affiliate::query()
            ->when($search, function ($query, $search) {
                // ค้นหาจากชื่อหรืออีเมล
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                // กรองตามสถานะ
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // ส่งข้อมูลไปยัง View
        return view('admin.affiliates.index', [
            'affiliates' => $affiliates,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * บันทึกข้อมูลแอฟฟิลิเอตใหม่
     *
     * @param Request $request - HTTP Request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ตรวจสอบความถูกต้องของข้อมูล
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:affiliates',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'commission_rate.required' => 'กรุณากรอกอัตราคอมมิชชั่น',
        ]);

        // สร้างข้อมูลแอฟฟิลิเอตใหม่
        $affiliate = Affiliate::create($validated);

        // ส่ง email แจ้งเตือนไปยังแอฟฟิลิเอต
        // TODO: เพิ่มการส่ง email

        // Redirect พร้อมข้อความสำเร็จ
        return redirect()
            ->route('admin.affiliates.index')
            ->with('success', 'สร้างแอฟฟิลิเอตสำเร็จ');
    }
}
```

---

### ตัวอย่างที่ 2: การสร้าง Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง affiliate_links
 *
 * @description เก็บข้อมูลลิงก์แอฟฟิลิเอตที่ผู้ใช้สร้าง
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางยังไม่มีอยู่ก่อนสร้าง
        if (!Schema::hasTable('affiliate_links')) {
            Schema::create('affiliate_links', function (Blueprint $table) {
                // คอลัมน์หลัก
                $table->id();

                // ความสัมพันธ์กับผู้ใช้
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade')
                    ->comment('ID ของผู้ใช้ที่สร้างลิงก์');

                // ข้อมูลลิงก์
                $table->string('code', 50)
                    ->unique()
                    ->comment('รหัส unique สำหรับลิงก์');

                $table->string('campaign', 100)
                    ->nullable()
                    ->comment('รหัสแคมเปญ');

                // สถิติ
                $table->unsignedInteger('clicks')
                    ->default(0)
                    ->comment('จำนวนครั้งที่มีคนคลิก');

                $table->unsignedInteger('conversions')
                    ->default(0)
                    ->comment('จำนวนครั้งที่แปลงเป็นยอดขาย');

                // สถานะ
                $table->enum('status', ['active', 'inactive', 'expired'])
                    ->default('active')
                    ->comment('สถานะของลิงก์');

                $table->timestamp('expires_at')
                    ->nullable()
                    ->comment('วันหมดอายุของลิงก์');

                // Timestamps
                $table->timestamps();
                $table->softDeletes();

                // Indexes สำหรับ performance
                $table->index('user_id');
                $table->index('code');
                $table->index('campaign');
                $table->index('status');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ลบตารางถ้ามีอยู่
        Schema::dropIfExists('affiliate_links');
    }
};
```

---

## ✅ Checklist สำหรับ Claude

เมื่อทำงานกับผู้ใช้ ต้องตรวจสอบ:

- [ ] **การสื่อสาร**: ตอบกลับผู้ใช้เป็นภาษาไทย 100%
- [ ] **คอมเม้นต์**: ทุกคอมเม้นต์ในโค้ดเป็นภาษาไทย
- [ ] **PHPDoc/JSDoc**: ครบถ้วน และเป็นภาษาไทย
- [ ] **User-Facing Text**: ข้อความที่แสดงต่อผู้ใช้เป็นภาษาไทย
- [ ] **เอกสาร**: README และคู่มือเป็นภาษาไทย
- [ ] **ชื่อตัวแปร**: ใช้ภาษาอังกฤษได้ แต่ต้องมีคอมเม้นต์ภาษาไทย
- [ ] **Test Names**: ชื่อ test method เป็นภาษาไทย

---

## 💡 เคล็ดลับ (Tips)

### 1. คำศัพท์ทางเทคนิค

สำหรับคำศัพท์ทางเทคนิคที่ไม่มีคำแปลที่เหมาะสม สามารถใช้ภาษาอังกฤษได้ แต่ต้องอธิบายเพิ่มเติม:

```php
/**
 * Middleware สำหรับตรวจสอบ API rate limiting
 *
 * @description จำกัดจำนวนครั้งที่เรียก API ต่อนาที เพื่อป้องกัน abuse
 * @param Request $request - HTTP Request object
 * @param Closure $next - Callback function ถัดไป
 * @return Response
 */
```

### 2. การใช้ Emoji

สามารถใช้ emoji เพื่อทำให้คอมเม้นต์อ่านง่ายขึ้น:

```php
// ✅ สำเร็จ: บันทึกข้อมูลลงฐานข้อมูล
// ❌ ข้อผิดพลาด: ไม่พบข้อมูลผู้ใช้
// ⚠️ คำเตือน: ฟังก์ชันนี้จะถูกยกเลิกในเวอร์ชันถัดไป
// 💡 เคล็ดลับ: ควรใช้ eager loading เพื่อลด query
// 🔒 ความปลอดภัย: ต้อง sanitize input ก่อนใช้งาน
// 🚀 Performance: ใช้ cache เพื่อเพิ่มความเร็ว
```

### 3. ตัวอย่างที่ดี vs ไม่ดี

**❌ ไม่ดี** - ไม่มีคอมเม้นต์เลย:
```php
public function calculate($amount) {
    return $amount * 0.10;
}
```

**⚠️ พอใช้** - มีคอมเม้นต์ แต่เป็นภาษาอังกฤษ:
```php
// Calculate 10% commission
public function calculate($amount) {
    return $amount * 0.10;
}
```

**✅ ดีมาก** - มีคอมเม้นต์ภาษาไทยครบถ้วน:
```php
/**
 * คำนวณคอมมิชชั่น 10% จากยอดขาย
 *
 * @param float $amount - ยอดขาย (บาท)
 * @return float - คอมมิชชั่นที่คำนวณได้ (บาท)
 *
 * @example
 * calculate(1000) // คืนค่า 100.0
 *
 * @tip ควรตรวจสอบว่า $amount เป็นค่าบวกก่อนเรียกใช้
 */
public function calculate(float $amount): float
{
    // อัตราคอมมิชชั่นมาตรฐาน 10%
    $commissionRate = 0.10;

    // คำนวณและปัดเศษทศนิยม 2 ตำแหน่ง
    return round($amount * $commissionRate, 2);
}
```

---

## 🚨 ข้อห้าม (DO NOT)

### ❌ ห้ามทำ:

1. **ห้ามใช้ภาษาอังกฤษในการตอบผู้ใช้** (ยกเว้นคำศัพท์เทคนิคที่จำเป็น)
2. **ห้ามมีคอมเม้นต์ภาษาอังกฤษในโค้ด**
3. **ห้ามมีข้อความ user-facing เป็นภาษาอังกฤษ**
4. **ห้ามสร้าง README หรือเอกสารเป็นภาษาอังกฤษ**
5. **ห้ามเขียน test cases เป็นภาษาอังกฤษ**

### ✅ ยกเว้น:

1. ชื่อตัวแปร ฟังก์ชัน class (ใช้ภาษาอังกฤษได้ แต่ต้องมีคอมเม้นต์ไทย)
2. คำศัพท์เทคนิคที่เป็นมาตรฐานสากล (เช่น API, HTTP, JSON, middleware)
3. Package names และ library names
4. Git commands และ terminal commands

---

## 📌 สรุป

**กฎทองสำหรับการใช้ภาษาไทย:**

> "ทุกสิ่งที่มนุษย์อ่าน ต้องเป็นภาษาไทย
> ทุกสิ่งที่เครื่องอ่าน ใช้ภาษาอังกฤษได้ แต่ต้องมีคอมเม้นต์ภาษาไทยประกอบ"

**หมายความว่า:**
- 💬 สื่อสารกับผู้ใช้ → **ภาษาไทย 100%**
- 📝 คอมเม้นต์ในโค้ด → **ภาษาไทย 100%**
- 📄 เอกสาร README → **ภาษาไทย 100%**
- 🌐 ข้อความบนหน้าเว็บ → **ภาษาไทย 100%**
- 🧪 Test descriptions → **ภาษาไทย 100%**
- 🏷️ ชื่อตัวแปร/ฟังก์ชัน → **ภาษาอังกฤษได้ (+ คอมเม้นต์ไทย)**

---

**เป้าหมาย**: ให้โค้ดทุกบรรทัดเข้าใจง่าย อ่านง่าย และทีมงานคนไทยสามารถ maintain ได้อย่างมีประสิทธิภาพ! 🇹🇭✨

---

*อัพเดทล่าสุด: 13 พฤศจิกายน 2025*

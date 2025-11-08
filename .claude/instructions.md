# คำแนะนำสำหรับ Claude - Thaiprompt Affiliate System

## 📚 คู่มือเพิ่มเติม (Additional Guidelines)

- **[DATABASE_GUIDELINES.md](./DATABASE_GUIDELINES.md)** - คู่มือการจัดการ Database, Migrations และ Seeders
  - กฎสำคัญสำหรับ migrations (Table existence checks)
  - Best practices สำหรับ seeders
  - Common issues และวิธีแก้ไข

---

## หลักการออกแบบและพัฒนาโค้ด

### 🌓 การรองรับโหมดมืด-สว่าง (Dark/Light Mode)

**สำคัญที่สุด**: ทุกคอมโพเนนต์ UI ที่สร้างใหม่หรือแก้ไข **ต้องรองรับทั้งโหมดมืดและโหมดสว่างเสมอ**

#### หลักการสำคัญ:

1. **ใช้ CSS Variables สำหรับสี**
   - ห้ามใช้สีแบบ hard-coded (เช่น `#ffffff`, `black`)
   - ต้องใช้ CSS variables ที่กำหนดไว้ใน theme
   - ตัวอย่าง: `var(--bg-primary)`, `var(--text-primary)`, `var(--border-color)`

2. **ทดสอบทั้งสองโหมด**
   - ตรวจสอบ contrast ratio ให้เหมาะสม
   - ทดสอบการอ่านง่ายในทั้งสองโหมด
   - ตรวจสอบ shadow, border, และ hover states

3. **Tailwind CSS Dark Mode**
   - ใช้ `dark:` prefix สำหรับ dark mode styles
   - ตัวอย่าง: `bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100`

4. **Component Theme Awareness**
   - ทุก component ต้องรับรู้ theme context
   - ใช้ theme provider หรือ context API
   - อัปเดต styling ตาม theme ที่เลือก

### 🎨 มาตรฐานความสวยงาม (Professional Design Standards)

**ทุกโค้ดและ UI ต้องมีคุณภาพระดับมืออาชีพ - สวยงามหลักล้าน**

#### UI/UX Principles:

1. **Spacing และ Layout**
   - ใช้ spacing system ที่สม่ำเสมอ (4px, 8px, 16px, 24px, 32px)
   - Maintain proper white space
   - Responsive design สำหรับทุก screen size

2. **Typography**
   - ใช้ font hierarchy ที่ชัดเจน
   - Line height และ letter spacing เหมาะสม
   - รองรับ Thai และ English fonts

3. **Colors และ Contrast**
   - Color palette ที่สอดคล้องกัน
   - WCAG AA compliance ขึ้นไป (contrast ratio ≥ 4.5:1)
   - Semantic colors (success, error, warning, info)

4. **Animations และ Transitions**
   - Smooth transitions (200-300ms)
   - Meaningful animations ที่ช่วย UX
   - Performance-conscious (60fps)

5. **Icons และ Imagery**
   - Consistent icon style
   - Optimized images (WebP, lazy loading)
   - Proper alt text และ accessibility

### 💬 การเขียนคอมเม้นต์และเอกสาร (Documentation Standards)

**บังคับ**: ทุกโค้ดต้องมีคอมเม้นต์ภาษาไทยอธิบายการทำงาน

#### หลักการเขียนคอมเม้นต์:

1. **คอมเม้นต์ภาษาไทยเสมอ**
   - อธิบายการทำงานของฟังก์ชัน/เมธอดเป็นภาษาไทย
   - ระบุ parameters และ return values
   - อธิบาย business logic ที่ซับซ้อน
   - ใส่คำเตือนสำหรับส่วนสำคัญ

2. **เอกสารการใช้งาน (Tips)**
   - เพิ่ม JSDoc/PHPDoc สำหรับทุกฟังก์ชัน public
   - ใส่ @example แสดงวิธีใช้งาน
   - ระบุ @param, @returns, @throws
   - เพิ่ม tips และ best practices ในคอมเม้นต์

3. **Component Documentation**
   - อธิบายการใช้งาน component
   - ระบุ props และ events
   - ให้ตัวอย่างการใช้งาน (usage example)
   - Tips สำหรับ customization

#### ตัวอย่างคอมเม้นต์ที่ดี:

```php
/**
 * คำนวณค่าคอมมิชชั่นตามระดับสมาชิก
 *
 * ฟังก์ชันนี้จะคำนวณค่าคอมมิชชั่นโดยพิจารณาจาก:
 * - ระดับสมาชิก (Bronze, Silver, Gold, Platinum)
 * - ยอดขายรวม
 * - โบนัสพิเศษ (ถ้ามี)
 *
 * @param User $user ข้อมูลผู้ใช้
 * @param float $salesAmount ยอดขายทั้งหมด
 * @param bool $includeBonus รวมโบนัสพิเศษหรือไม่
 * @return float ค่าคอมมิชชั่นที่คำนวณได้
 *
 * @example
 * $commission = calculateCommission($user, 10000, true);
 * // Returns: 1500.00 (15% + 5% bonus)
 *
 * @tip ใช้ includeBonus=true เฉพาะช่วงโปรโมชั่น
 */
public function calculateCommission(User $user, float $salesAmount, bool $includeBonus = false): float
{
    // ดึงอัตราค่าคอมมิชชั่นตามระดับสมาชิก
    $rate = $user->membership_level->commission_rate;

    // คำนวณค่าคอมมิชชั่นพื้นฐาน
    $commission = $salesAmount * $rate;

    // เพิ่มโบนัสพิเศษ 5% ถ้าระบุ
    if ($includeBonus) {
        $commission += $salesAmount * 0.05;
    }

    return round($commission, 2);
}
```

```vue
<!--
  คอมโพเนนต์แสดงการ์ดสมาชิก

  แสดงข้อมูลสมาชิกในรูปแบบการ์ดที่สวยงาม รองรับ dark/light mode

  Props:
  - user: ข้อมูลผู้ใช้ (required)
  - showStats: แสดงสถิติหรือไม่ (default: true)
  - clickable: คลิกได้หรือไม่ (default: false)

  Events:
  - @click: เมื่อคลิกที่การ์ด (ถ้า clickable=true)
  - @refresh: เมื่อต้องการรีเฟรชข้อมูล

  Usage:
  <UserCard
    :user="currentUser"
    :show-stats="true"
    :clickable="true"
    @click="viewProfile"
  />

  💡 Tips:
  - ใช้ slot="actions" เพื่อเพิ่มปุ่มกระทำ
  - รองรับ skeleton loading ตอนโหลดข้อมูล
-->
<template>
  <div class="user-card">
    <!-- Component content -->
  </div>
</template>

<script setup>
/**
 * Composable สำหรับจัดการข้อมูลผู้ใช้
 */
import { ref, computed } from 'vue'

// Props - กำหนดค่าที่รับเข้ามา
const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  showStats: {
    type: Boolean,
    default: true
  }
})

// คำนวณชื่อแสดงผล
const displayName = computed(() => {
  return props.user?.name || 'Guest'
})
</script>
```

### 🎯 Icons และ Visual Elements

**ใส่ไอคอนให้สวยงามเสมอ แต่ไม่รกเกินไป**

#### หลักการใช้ไอคอน:

1. **Consistent Icon Library**
   - ใช้ icon library เดียว (แนะนำ: Heroicons, Lucide, Font Awesome)
   - ขนาด icon สม่ำเสมอ (16px, 20px, 24px)
   - สไตล์เดียวกัน (outline หรือ solid)

2. **Icon Placement**
   - ใส่ icon ที่มีความหมาย (meaningful icons)
   - ไม่ใส่ icon มากเกินไปจนรกตา
   - ใช้ icon เพื่อช่วยให้เข้าใจง่ายขึ้น
   - Position: ซ้าย/ขวาของ text ให้สม่ำเสมอ

3. **Icon Colors**
   - ใช้สีที่สอดคล้องกับ theme
   - รองรับ dark/light mode
   - ใช้สีตามความหมาย (success=green, error=red)

4. **Icon Animations**
   - Subtle animations เมื่อมี interaction
   - Smooth transitions (200ms)
   - ไม่ทำ animation มากเกินไป

#### ตัวอย่างการใช้ไอคอน:

```vue
<!-- ปุ่มพร้อม icon ที่สวยงาม -->
<button class="btn-primary">
  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor">
    <path d="M12 4v16m8-8H4"/>
  </svg>
  เพิ่มสมาชิก
</button>

<!-- Status badge พร้อม icon -->
<div class="status-badge success">
  <svg class="w-4 h-4" fill="currentColor">
    <path d="M9 12l2 2 4-4"/>
  </svg>
  <span>เสร็จสิ้น</span>
</div>

<!-- Navigation พร้อม icon -->
<nav>
  <a href="/dashboard" class="nav-link">
    <svg class="w-5 h-5"><!-- home icon --></svg>
    <span>หน้าหลัก</span>
  </a>
  <a href="/users" class="nav-link">
    <svg class="w-5 h-5"><!-- users icon --></svg>
    <span>สมาชิก</span>
  </a>
</nav>
```

### 💎 Code Quality Standards

**โค้ดทุกบรรทัดต้องมีคุณภาพระดับมืออาชีพ**

#### Backend (Laravel/PHP):

1. **Clean Code Principles**
   - Single Responsibility Principle
   - DRY (Don't Repeat Yourself)
   - Meaningful variable และ function names (ภาษาอังกฤษ)
   - Proper type hints และ return types
   - **คอมเม้นต์ภาษาไทยอธิบายการทำงาน**

2. **Laravel Best Practices**
   - ใช้ Eloquent relationships ถูกต้อง
   - Service layer สำหรับ business logic
   - Repository pattern เมื่อจำเป็น
   - Proper validation และ authorization

3. **Database**
   - Indexed columns สำหรับ queries ที่ใช้บ่อย
   - Eager loading เพื่อป้องกัน N+1 queries
   - Database transactions สำหรับ critical operations

#### Frontend (Vue.js/JavaScript):

1. **Component Structure**
   - Single File Components (SFC)
   - Props validation และ TypeScript types
   - Composition API สำหรับ logic reuse
   - Proper component lifecycle management

2. **State Management**
   - Vuex/Pinia สำหรับ global state
   - Local state สำหรับ component-specific data
   - Computed properties สำหรับ derived data

3. **Performance**
   - Lazy loading components
   - Virtual scrolling สำหรับ large lists
   - Debounce/throttle สำหรับ expensive operations
   - Code splitting และ tree shaking

### 🔒 Security และ Best Practices

1. **Input Validation**
   - Validate ทุก input ทั้ง frontend และ backend
   - Sanitize data ก่อน display
   - XSS protection

2. **Authentication & Authorization**
   - Proper middleware usage
   - CSRF protection
   - Secure session management

3. **Error Handling**
   - Graceful error handling
   - User-friendly error messages
   - Proper logging สำหรับ debugging

### 🛣️ Views และ Routes Verification (บังคับ)

**ทุกครั้งที่สร้างหรือแก้ไข Routes และ Views ต้องตรวจสอบความถูกต้อง**

#### 1. Route Verification Checklist

**ก่อนสร้าง Route ใหม่:**
- [ ] ตรวจสอบว่าไม่มี route ซ้ำ (duplicate routes)
- [ ] ตรวจสอบ route naming conflicts
- [ ] ตรวจสอบว่า route parameters ไม่ซ้ำกับ route อื่น
- [ ] ตรวจสอบ middleware ที่จำเป็น (auth, role, permission)
- [ ] ตรวจสอบ route grouping และ prefix

#### 2. Route Best Practices

```php
// ✅ ตัวอย่างที่ดี - มี middleware, naming, และ grouping
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // ตรวจสอบ: route name ไม่ซ้ำ
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');

    // ตรวจสอบ: parameter binding ถูกต้อง
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
});

// ❌ ห้ามทำ - ไม่มี middleware, naming ซ้ำ
Route::get('/users', [UserController::class, 'index']); // ซ้ำกับ route ด้านบน!
Route::get('/user/{id}', [UserController::class, 'show']); // parameter name ไม่สม่ำเสมอ
```

#### 3. View Verification Checklist

**ก่อนสร้าง View ใหม่:**
- [ ] ตรวจสอบว่า view file ไม่ซ้ำ
- [ ] ตรวจสอบว่า view path ถูกต้อง
- [ ] ตรวจสอบ layout และ component dependencies
- [ ] ตรวจสอบ data variables ที่ส่งมาจาก controller
- [ ] ตรวจสอบว่ามี error handling สำหรับข้อมูลที่อาจเป็น null

#### 4. View Best Practices

```blade
{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="container">
    {{-- ตรวจสอบ: มีการ check ข้อมูลก่อนแสดง --}}
    @if(isset($users) && $users->count() > 0)
        <table>
            @foreach($users as $user)
                {{-- ตรวจสอบ: escape output ป้องกัน XSS --}}
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p>ไม่มีข้อมูลผู้ใช้</p>
    @endif
</div>
@endsection
```

#### 5. Controller → View Data Flow

**ตรวจสอบการส่งข้อมูล:**

```php
// Controller
public function index()
{
    // ตรวจสอบ: ดึงข้อมูลอย่างถูกต้อง พร้อม eager loading
    $users = User::with(['profile', 'roles'])
        ->latest()
        ->paginate(15);

    // ตรวจสอบ: ส่งข้อมูลครบถ้วน
    return view('admin.users.index', [
        'users' => $users,
        'pageTitle' => 'จัดการผู้ใช้',
        'breadcrumbs' => $this->getBreadcrumbs()
    ]);
}
```

#### 6. Route Testing Commands

**ใช้คำสั่งเหล่านี้เพื่อตรวจสอบ routes:**

```bash
# ดูรายการ routes ทั้งหมด
php artisan route:list

# ค้นหา route ที่ซ้ำ
php artisan route:list | sort | uniq -d

# ดู routes สำหรับ controller เฉพาะ
php artisan route:list --name=admin.users

# ทดสอบ route
php artisan route:cache
php artisan route:clear
```

#### 7. Common Route/View Issues

**ปัญหาที่พบบ่อยและวิธีแก้:**

```php
// ❌ ปัญหา: Route ซ้ำ
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard', [AdminController::class, 'dashboard']); // ซ้ำ!

// ✅ แก้ไข: ใช้ prefix หรือ name แยก
Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');
Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

// ❌ ปัญหา: View ไม่พบ
return view('users.index'); // ไฟล์ไม่มี!

// ✅ แก้ไข: ตรวจสอบว่าไฟล์มีอยู่จริง
if (view()->exists('users.index')) {
    return view('users.index', $data);
}
return view('errors.404');
```

### 🗄️ Database Schema และ Key Verification (บังคับ)

**ทุกครั้งที่สร้างหรือแก้ไข Database Schema ต้องตรวจสอบความถูกต้องอย่างเข้มงวด**

#### 1. Migration Verification Checklist

**ก่อนรัน Migration:**
- [ ] ตรวจสอบว่าไม่มี table ซ้ำ
- [ ] ตรวจสอบว่าไม่มี column ซ้ำในตารางเดียวกัน
- [ ] ตรวจสอบ data types ให้เหมาะสม
- [ ] ตรวจสอบ unique constraints
- [ ] ตรวจสอบ foreign keys และ references
- [ ] ตรวจสอบ indexes สำหรับ performance
- [ ] ตรวจสอบ default values
- [ ] ตรวจสอบ nullable/required fields

#### 2. Table Creation Best Practices

```php
/**
 * สร้างตาราง users
 * ตรวจสอบ: ไม่มีตาราง users อยู่แล้ว
 */
public function up()
{
    // ตรวจสอบก่อนสร้าง
    if (!Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Unique Fields - ตรวจสอบ: ต้องไม่ซ้ำ
            $table->string('email')->unique();
            $table->string('username')->unique();

            // Regular Fields
            $table->string('name');
            $table->string('password');
            $table->string('phone')->nullable();

            // Foreign Keys - ตรวจสอบ: table ที่ reference ต้องมีอยู่จริง
            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // Indexes - ตรวจสอบ: เพิ่ม index สำหรับ columns ที่ค้นหาบ่อย
            $table->index('email');
            $table->index(['name', 'created_at']);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
```

#### 3. Column Modification Best Practices

```php
/**
 * แก้ไข table users
 * ตรวจสอบ: table และ column มีอยู่จริง
 */
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // ตรวจสอบว่า column ยังไม่มี
        if (!Schema::hasColumn('users', 'avatar')) {
            $table->string('avatar')->nullable()->after('email');
        }

        // ตรวจสอบว่า column มีอยู่ก่อนแก้ไข
        if (Schema::hasColumn('users', 'phone')) {
            $table->string('phone', 20)->nullable()->change();
        }
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        // ตรวจสอบก่อนลบ
        if (Schema::hasColumn('users', 'avatar')) {
            $table->dropColumn('avatar');
        }
    });
}
```

#### 4. Foreign Key Best Practices

```php
/**
 * สร้างความสัมพันธ์ระหว่างตาราง
 * ตรวจสอบ: ตารางทั้งสองมีอยู่จริง
 */
public function up()
{
    // ตรวจสอบ: ตาราง parent (users) ต้องมีก่อน
    if (Schema::hasTable('users') && Schema::hasTable('posts')) {
        Schema::table('posts', function (Blueprint $table) {
            // ตรวจสอบว่ายังไม่มี foreign key
            if (!Schema::hasColumn('posts', 'user_id')) {
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onUpdate('cascade')
                    ->onDelete('cascade'); // หรือ 'set null', 'restrict'
            }
        });
    }
}
```

#### 5. Index Best Practices

```php
/**
 * เพิ่ม indexes เพื่อเพิ่มประสิทธิภาพ
 * ตรวจสอบ: column ที่ใช้ค้นหาบ่อย
 */
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        // Single column index
        $table->index('status'); // WHERE status = ?
        $table->index('created_at'); // ORDER BY created_at

        // Composite index
        $table->index(['user_id', 'status']); // WHERE user_id = ? AND status = ?

        // Unique index
        $table->unique(['user_id', 'product_id']); // ป้องกันซื้อซ้ำ
    });
}
```

#### 6. Common Database Issues

**ปัญหาที่พบบ่อยและวิธีแก้:**

```php
// ❌ ปัญหา: Foreign key error - ตาราง parent ยังไม่มี
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained(); // Error: ตาราง users ยังไม่มี!
});

// ✅ แก้ไข: สร้างตาราง parent ก่อน หรือแยก migration
// Migration 1: 2024_01_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    // ...
});

// Migration 2: 2024_01_01_000002_create_posts_table.php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
});

// ❌ ปัญหา: Column ซ้ำ
Schema::table('users', function (Blueprint $table) {
    $table->string('email')->unique(); // Column นี้มีอยู่แล้ว!
});

// ✅ แก้ไข: ตรวจสอบก่อนเพิ่ม
if (!Schema::hasColumn('users', 'email')) {
    $table->string('email')->unique();
}

// ❌ ปัญหา: ลืม rollback
public function down()
{
    // ว่างเปล่า - ไม่มี rollback logic!
}

// ✅ แก้ไข: เขียน rollback logic ให้ครบ
public function down()
{
    if (Schema::hasTable('posts')) {
        Schema::dropIfExists('posts');
    }
}
```

#### 7. Database Testing Commands

**ใช้คำสั่งเหล่านี้เพื่อตรวจสอบ database:**

```bash
# ตรวจสอบ migration status
php artisan migrate:status

# ทดสอบ migration แบบ dry-run (Laravel 10+)
php artisan migrate --pretend

# Rollback และทดสอบใหม่
php artisan migrate:rollback
php artisan migrate

# ตรวจสอบ database schema
php artisan db:show
php artisan db:table users

# สร้าง database diagram
php artisan schema:dump
```

#### 8. Data Integrity Checklist

**ตรวจสอบความสมบูรณ์ของข้อมูล:**

- [ ] **Unique Constraints**: ป้องกันข้อมูลซ้ำ (email, username, etc.)
- [ ] **Foreign Keys**: ความสัมพันธ์ถูกต้อง และมี onDelete/onUpdate
- [ ] **Indexes**: เพิ่ม index สำหรับ columns ที่ใช้ใน WHERE, ORDER BY, JOIN
- [ ] **Default Values**: กำหนดค่า default ที่เหมาะสม
- [ ] **Nullable Fields**: ระบุ nullable เฉพาะ fields ที่จำเป็น
- [ ] **Data Types**: ใช้ data type ที่เหมาะสม (string length, integer size, etc.)
- [ ] **Cascade Rules**: กำหนด cascade behavior ที่ถูกต้อง
- [ ] **Timestamps**: เพิ่ม created_at, updated_at, deleted_at ตามความเหมาะสม

#### 9. Model Relationship Verification

**ตรวจสอบ Model relationships ให้ตรงกับ database:**

```php
// app/Models/User.php
class User extends Model
{
    /**
     * ความสัมพันธ์กับ posts
     * ตรวจสอบ: foreign key 'user_id' มีอยู่จริงในตาราง posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * ความสัมพันธ์กับ role
     * ตรวจสอบ: foreign key 'role_id' มีอยู่จริงในตาราง users
     * ตรวจสอบ: ตาราง roles มีอยู่จริง
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
```

### 📦 Composer Package Management และ Deployment (บังคับ)

**ทุกครั้งที่มีการติดตั้ง Composer Package ใหม่ ต้องเพิ่มการติดตั้งใน deploy.sh เสมอ**

#### หลักการสำคัญ:

**"ถ้ามีการใช้ composer require ในโปรเจกต์ ต้องเพิ่มใน deploy.sh ทันที"**

#### 1. Composer Package Installation Checklist

**เมื่อติดตั้ง Package ใหม่:**
- [ ] ติดตั้ง package ในโปรเจกต์ด้วย `composer require package-name`
- [ ] **เพิ่มการตรวจสอบและติดตั้ง package ใน deploy.sh ทันที (บังคับ)**
- [ ] ใช้รูปแบบมาตรฐานเดียวกับ packages อื่นๆ
- [ ] เพิ่ม version detection และ logging
- [ ] มี error handling และ fallback message
- [ ] วาง step ในตำแหน่งที่เหมาะสมของ deployment script

#### 2. Deploy.sh Package Installation Template

**รูปแบบมาตรฐานสำหรับเพิ่ม package ใน deploy.sh:**

```bash
# Step X.X: Install/Verify [Package Name] for [Purpose]
print_info "[X.X/20] Installing [Package Name]..."
if composer show package-name &>/dev/null; then
    PACKAGE_VERSION=$(composer show package-name 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
    print_success "[Package Name] already installed (${PACKAGE_VERSION})"
else
    print_info "Installing [Package Name]..."
    if ! composer require package-name --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "[Package Name] installation failed - [describe fallback behavior]"
        log "Warning: package-name installation failed"
    else
        PACKAGE_VERSION=$(composer show package-name 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "[Package Name] installed successfully (${PACKAGE_VERSION})"
        log "[Package Name] installed: ${PACKAGE_VERSION}"
    fi
fi
```

#### 3. ตัวอย่าง Package Installation

**ตัวอย่าง: DomPDF สำหรับ PDF Generation**

```bash
# Step 7.6: Install/Verify DomPDF for PDF Generation (Software Sales System)
print_info "[7.6/20] Installing DomPDF for PDF generation..."
if composer show barryvdh/laravel-dompdf &>/dev/null; then
    DOMPDF_VERSION=$(composer show barryvdh/laravel-dompdf 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
    print_success "DomPDF already installed (${DOMPDF_VERSION})"
else
    print_info "Installing DomPDF..."
    if ! composer require barryvdh/laravel-dompdf --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "DomPDF installation failed - PDF quotations will use HTML fallback"
        log "Warning: barryvdh/laravel-dompdf installation failed"
    else
        DOMPDF_VERSION=$(composer show barryvdh/laravel-dompdf 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "DomPDF installed successfully (${DOMPDF_VERSION})"
        log "DomPDF installed: ${DOMPDF_VERSION}"
    fi
fi
```

**ตัวอย่าง: Intervention Image สำหรับ Image Processing**

```bash
# Step 7.7: Install/Verify Intervention Image for Image Processing
print_info "[7.7/20] Installing Intervention Image..."
if composer show intervention/image &>/dev/null; then
    IMAGE_VERSION=$(composer show intervention/image 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
    print_success "Intervention Image already installed (${IMAGE_VERSION})"
else
    print_info "Installing Intervention Image..."
    if ! composer require intervention/image --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "Intervention Image installation failed - image processing features may not work"
        log "Warning: intervention/image installation failed"
    else
        IMAGE_VERSION=$(composer show intervention/image 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "Intervention Image installed successfully (${IMAGE_VERSION}"
        log "Intervention Image installed: ${IMAGE_VERSION}"
    fi
fi
```

#### 4. Deploy.sh Placement Guidelines

**วาง package installation step ในตำแหน่งที่เหมาะสม:**

- **Step 7.x**: สำหรับ packages ที่เป็น optional dependencies หรือ feature-specific
  - DomPDF (PDF generation)
  - Intervention Image (image processing)
  - Laravel Excel (Excel import/export)
  - Package ที่ไม่ใช่ core dependencies

- **ก่อน Step 8** (Laravel Sanctum): สำหรับ packages ที่อาจต้องมีก่อน authentication/authorization

- **หลัง Step 8**: สำหรับ packages ที่ depend on authentication

#### 5. Common Packages Checklist

**Packages ที่มักใช้และต้องเพิ่มใน deploy.sh:**

```bash
# PDF Generation
composer require barryvdh/laravel-dompdf

# Image Processing
composer require intervention/image

# Excel Import/Export
composer require maatwebsite/excel

# Payment Gateways
composer require omnipay/omnipay

# API Resources
composer require spatie/laravel-query-builder

# Testing Tools (dev only)
composer require --dev barryvdh/laravel-debugbar
```

#### 6. Error Handling Best Practices

**สิ่งที่ต้องมีใน error handling:**

1. **ข้อความเตือนที่ชัดเจน** - บอกว่าถ้า package ติดตั้งไม่สำเร็จจะเกิดอะไร
2. **Fallback behavior** - อธิบายว่าระบบจะทำงานอย่างไรถ้าไม่มี package
3. **Logging** - บันทึกลงไฟล์ log เพื่อ debugging
4. **Non-breaking** - อย่าให้ deployment fail ถ้า package ไม่สำคัญมาก

```bash
if ! composer require package-name --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
    # ✅ ดี: บอกผลกระทบและ fallback ชัดเจน
    print_warning "Package installation failed - Feature X will use fallback method Y"
    log "Warning: package-name installation failed"

    # ❌ ไม่ดี: ไม่บอกผลกระทบ
    # print_warning "Failed"
fi
```

#### 7. Testing Deployment Script

**ทดสอบ deploy.sh หลังเพิ่ม package:**

```bash
# ทดสอบ syntax
bash -n deploy.sh

# ทดสอบ dry-run (ถ้ามี)
./deploy.sh --dry-run

# ทดสอบจริงบน staging environment
./deploy.sh

# ตรวจสอบว่า package ติดตั้งสำเร็จ
composer show | grep package-name
```

#### 8. Documentation Update

**เมื่อเพิ่ม package ใหม่ ต้องอัปเดต:**

- [ ] `deploy.sh` - เพิ่ม installation step
- [ ] `README.md` - อัปเดต dependencies list
- [ ] `composer.json` - verify package อยู่ใน require/require-dev
- [ ] Documentation - อธิบายการใช้งาน package
- [ ] `.env.example` - เพิ่ม config variables ถ้ามี

#### 9. ตัวอย่างการทำงานจริง

**Workflow ที่ถูกต้อง:**

1. **พัฒนา Feature ใหม่** - ต้องการใช้ DomPDF
   ```bash
   composer require barryvdh/laravel-dompdf
   ```

2. **ทันทีที่ติดตั้ง - เพิ่มใน deploy.sh**
   ```bash
   # เปิดไฟล์ deploy.sh
   # เพิ่ม Step 7.6 สำหรับ DomPDF installation
   # บันทึกไฟล์
   ```

3. **Commit ทั้งสองอย่างพร้อมกัน**
   ```bash
   git add composer.json composer.lock deploy.sh
   git commit -m "feat: Add DomPDF for PDF quotation generation

   - Install barryvdh/laravel-dompdf package
   - Add DomPDF installation to deploy.sh
   - Include HTML fallback if installation fails"
   ```

4. **ทดสอบ Deployment**
   ```bash
   ./deploy.sh
   # ตรวจสอบว่า DomPDF ติดตั้งสำเร็จ
   ```

#### 10. ห้ามทำ (Deploy.sh):

- ❌ ติดตั้ง package แล้วไม่เพิ่มใน deploy.sh
- ❌ ใช้รูปแบบที่ไม่สม่ำเสมอกับ packages อื่น
- ❌ ไม่มี error handling หรือ fallback
- ❌ ไม่ log การติดตั้ง
- ❌ ไม่ตรวจสอบว่า package ติดตั้งแล้วหรือยัง (จะติดตั้งซ้ำทุกครั้ง)
- ❌ ไม่แสดง version ของ package
- ❌ ทำให้ deployment fail ถ้า optional package ติดตั้งไม่สำเร็จ
- ❌ วาง step ไม่เป็นระเบียบหรือไม่อัปเดต step numbers

### 📱 Responsive Design

**บังคับ: ทุก UI ต้องเป็น Responsive เสมอ - ทำงานได้ดีบนทุก device**

#### หลักการ Responsive แบบบังคับ:

1. **Mobile-First Approach (บังคับ)**
   - เริ่มออกแบบจาก mobile ก่อนเสมอ
   - ทดสอบบน mobile ก่อนเสมอ
   - Progressive enhancement สำหรับ larger screens

2. **Breakpoints ที่ต้องรองรับ**
   - Mobile: < 640px (320px - 639px)
   - Tablet: 640px - 1024px
   - Desktop: > 1024px
   - Large Desktop: > 1280px

3. **Responsive Components (บังคับทดสอบ)**
   - Navigation: แสดง mobile menu บน mobile, full menu บน desktop
   - Tables: แสดงเป็น cards บน mobile, table บน desktop
   - Forms: full-width บน mobile, appropriate width บน desktop
   - Images: ใช้ responsive images, lazy loading
   - Grids: 1 column mobile → 2-3 columns tablet → 3-4 columns desktop

4. **Touch-Friendly Design**
   - ปุ่มขนาดอย่างน้อย 44x44px บน mobile
   - Spacing เพียงพอสำหรับการกด
   - No hover-only interactions

5. **Testing Checklist**
   - ✅ ทดสอบบน iPhone (375px)
   - ✅ ทดสอบบน Android (360px)
   - ✅ ทดสอบบน iPad (768px)
   - ✅ ทดสอบบน Desktop (1920px)

#### ตัวอย่าง Responsive Code:

```vue
<template>
  <!-- Responsive Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <div v-for="item in items" :key="item.id" class="card">
      {{ item.name }}
    </div>
  </div>

  <!-- Responsive Navigation -->
  <nav class="hidden md:flex">
    <!-- Desktop menu -->
  </nav>
  <button class="md:hidden" @click="toggleMobileMenu">
    <!-- Mobile menu button -->
  </button>

  <!-- Responsive Typography -->
  <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold">
    หัวข้อ
  </h1>

  <!-- Responsive Spacing -->
  <div class="p-4 md:p-6 lg:p-8">
    Content
  </div>
</template>
```

### ♿ Accessibility (a11y)

1. **Semantic HTML**
   - ใช้ proper HTML tags
   - ARIA labels เมื่อจำเป็น

2. **Keyboard Navigation**
   - Tab order ที่เหมาะสม
   - Focus indicators ชัดเจน

3. **Screen Reader Support**
   - Alt text สำหรับ images
   - Descriptive labels สำหรับ form inputs

## 🎯 มาตรฐานคุณภาพระดับหลักล้าน (บังคับ)

**หลักการสำคัญ**: โปรแกรมที่เราพัฒนาต้องมีคุณภาพระดับ "หลักล้าน" - มืออาชีพ, สวยงาม, ใช้งานง่าย, มีเอกสารครบถ้วน

### 💎 คุณภาพที่ต้องมี (Non-Negotiable)

1. **UI/UX ระดับมืออาชีพ**
   - สวยงามระดับโปรแกรมพรีเมี่ยม
   - ใช้งานง่าย และเป็นมิตรกับผู้ใช้
   - รองรับทุก device (responsive)
   - รองรับทั้ง dark และ light mode
   - มี animations และ transitions ที่ลื่นไหล

2. **Code Quality ระดับ Enterprise**
   - Clean, maintainable, และ scalable
   - มี architecture ที่ดี (Service layer, Repository pattern)
   - Type-safe (type hints, validation)
   - Proper error handling
   - No code smells หรือ technical debt

3. **Documentation ครบถ้วน**
   - คอมเม้นต์ภาษาไทยอธิบายการทำงาน
   - PHPDoc/JSDoc พร้อม @param, @returns, @example
   - Tips และ best practices
   - Usage examples ที่ชัดเจน

4. **Performance ระดับโปรดักชัน**
   - Fast loading (< 3s)
   - No N+1 queries
   - Optimized images และ assets
   - Caching strategies
   - Lazy loading

5. **Security Standards**
   - Input validation และ sanitization
   - CSRF และ XSS protection
   - Proper authentication และ authorization
   - Secure data handling

## การตรวจสอบก่อน Commit

ก่อน commit code ทุกครั้ง ต้องตรวจสอบ:

### Design & UI (คุณภาพหลักล้าน)
- [ ] รองรับทั้ง dark mode และ light mode (บังคับ)
- [ ] UI สวยงามระดับมืออาชีพ - ไม่มีตรงไหนดูไม่เป็นระเบียบ (บังคับ)
- [ ] Responsive บนทุก device: mobile, tablet, desktop (บังคับ)
- [ ] ใส่ไอคอนที่เหมาะสม ไม่รกเกินไป มีความหมาย
- [ ] Animations smooth (60fps) และเป็นธรรมชาติ
- [ ] Loading states แสดงอย่างเหมาะสม
- [ ] Spacing, Typography, Colors ลงตัว
- [ ] Professional-grade UI ที่น่าใช้และน่าประทับใจ

### Code Quality (คุณภาพหลักล้าน)
- [ ] Code clean, readable, และ maintainable
- [ ] มีคอมเม้นต์ภาษาไทยอธิบายการทำงาน (บังคับ)
- [ ] มี JSDoc/PHPDoc พร้อม @param, @returns, @example (บังคับ)
- [ ] มี @tip การใช้งานในคอมเม้นต์ (บังคับ)
- [ ] ไม่มี duplicated code
- [ ] Type hints และ validation ครบถ้วน
- [ ] Error handling ถูกต้องและครอบคลุม
- [ ] Follow Laravel และ Vue.js best practices

### Routes & Views Verification (บังคับ)
- [ ] ตรวจสอบ routes ไม่ซ้ำ (ใช้ `php artisan route:list`)
- [ ] Route names และ parameters สม่ำเสมอ
- [ ] Middleware ครบถ้วน (auth, permission)
- [ ] Views ส่งข้อมูลครบถ้วนจาก controller
- [ ] มี error handling สำหรับข้อมูลที่เป็น null
- [ ] ทดสอบทุก route ทำงานถูกต้อง

### Database Schema Verification (บังคับ)
- [ ] ตรวจสอบไม่มี table/column ซ้ำ
- [ ] Foreign keys ถูกต้อง พร้อม onDelete/onUpdate
- [ ] Indexes ครบสำหรับ columns ที่ค้นหาบ่อย
- [ ] Unique constraints ป้องกันข้อมูลซ้ำ
- [ ] Data types เหมาะสม
- [ ] มี migration rollback (down method)
- [ ] ทดสอบ migration ด้วย `php artisan migrate --pretend`
- [ ] Model relationships ตรงกับ database schema

### Testing
- [ ] ผ่าน linting และ formatting standards
- [ ] ทดสอบบน iPhone (375px)
- [ ] ทดสอบบน Android (360px)
- [ ] ทดสอบบน iPad (768px)
- [ ] ทดสอบบน Desktop (1920px)
- [ ] ทดสอบ dark และ light mode
- [ ] ไม่มี console errors หรือ warnings
- [ ] Performance ดี (Lighthouse score > 90)
- [ ] ทดสอบ user flows หลักทั้งหมด

### Accessibility & Security
- [ ] Accessibility compliance (WCAG AA)
- [ ] Keyboard navigation ทำงาน
- [ ] Screen reader compatible
- [ ] Input validation ครบถ้วน
- [ ] XSS และ CSRF protection
- [ ] SQL injection prevention
- [ ] Proper authorization checks

## สรุป

### 💎 หลักการทอง 9 ข้อ (บังคับเสมอ):

**"โปรแกรมที่เราพัฒนาต้องมีคุณภาพระดับหลักล้าน - ไม่ยอมรับความผิดพลาด"**

1. **💎 คุณภาพหลักล้านเสมอ**
   - โปรแกรมต้องมีคุณภาพระดับ Enterprise/Premium
   - UI สวยงาม น่าใช้ น่าประทับใจ
   - Code quality ระดับมืออาชีพ
   - ทุกรายละเอียดต้องใส่ใจ ไม่มีส่วนไหนดูไม่เป็นระเบียบ
   - Performance ต้องดี responsive เร็ว

2. **🌓 Dark/Light Mode เสมอ**
   - ทุก UI ต้องรองรับทั้งสองโหมด (บังคับ)
   - ใช้ CSS variables และ Tailwind dark utilities
   - ทดสอบ contrast และ readability (WCAG AA)
   - Colors, shadows, borders ต้องเหมาะสมในทั้งสองโหมด

3. **🎨 UI สวยงามระดับมืออาชีพเสมอ**
   - Professional-grade UI/UX ทุก pixel
   - Spacing, typography, colors ต้องลงตัว
   - ใส่ไอคอนสวยงามแต่ไม่รก มีความหมาย
   - Animations smooth (60fps) และเป็นธรรมชาติ
   - Loading states ที่สวยงามและให้ feedback ที่ดี

4. **📱 Responsive เสมอ**
   - Mobile-first approach (บังคับ)
   - ทดสอบทุก device (mobile, tablet, desktop)
   - Touch-friendly บน mobile (≥44px)
   - ไม่มี horizontal scroll
   - Content readable และใช้งานง่ายบนทุกขนาดหน้าจอ

5. **💬 คอมเม้นต์ภาษาไทยเสมอ**
   - อธิบายการทำงานเป็นภาษาไทย (บังคับ)
   - มี JSDoc/PHPDoc พร้อม @param, @returns, @example (บังคับ)
   - ใส่ @tip การใช้งานและ best practices (บังคับ)
   - อธิบาย business logic ที่ซับซ้อน

6. **📚 คู่มือการใช้งานเสมอ**
   - ระบุ props, events, parameters, slots (Vue)
   - ให้ usage examples ที่ชัดเจนและทดสอบแล้ว
   - เพิ่ม tips และ best practices
   - อธิบาย edge cases และ error handling

7. **🛣️ ตรวจสอบ Routes & Views เสมอ**
   - ตรวจสอบ routes ไม่ซ้ำ (ใช้ `php artisan route:list`)
   - Route names, middleware, parameters ถูกต้อง
   - Views ส่งข้อมูลครบถ้วน มี error handling
   - ทดสอบทุก route path ทำงานถูกต้อง

8. **🗄️ ตรวจสอบ Database Schema เสมอ**
   - ตรวจสอบ tables/columns ไม่ซ้ำ
   - Foreign keys ถูกต้อง พร้อม onDelete/onUpdate
   - Indexes ครบสำหรับ performance
   - Unique constraints ป้องกันข้อมูลซ้ำ
   - ทดสอบ migration ด้วย `--pretend`
   - Model relationships ตรงกับ database schema

9. **📦 เพิ่ม Composer Packages ใน deploy.sh เสมอ**
   - ทุกครั้งที่ติดตั้ง package ใหม่ต้องเพิ่มใน deploy.sh ทันที (บังคับ)
   - ใช้รูปแบบมาตรฐาน: check → install → log → handle errors
   - มี version detection และ fallback message
   - ทดสอบ deployment script หลังเพิ่ม package
   - Commit ทั้ง composer.json, composer.lock และ deploy.sh พร้อมกัน

### 🔧 Code Quality Standards (บังคับ)

**โค้ดทุกบรรทัดต้องมีคุณภาพระดับมืออาชีพ:**
- Clean, readable, maintainable
- Follow Laravel & Vue.js best practices
- Type-safe (type hints, validation)
- Proper error handling และ logging
- No code smells หรือ technical debt
- Complete testing coverage

### ❌ ห้ามทำ (ห้ามเด็ดขาด):

#### Design & UI:
- ❌ Hard-code colors (ต้องใช้ CSS variables)
- ❌ UI ไม่สวยหรือไม่เป็นมืออาชีพ - ดูเหมือนโปรแกรมราคาถูก
- ❌ ไม่รองรับ dark mode
- ❌ ไอคอนรกเกินไป หรือไม่มีไอคอนเลย
- ❌ Animations ที่กระตุก (< 60fps)
- ❌ Loading states ไม่มีหรือไม่ชัดเจน
- ❌ Spacing, Typography, Colors ไม่ลงตัว

#### Responsive:
- ❌ ไม่ responsive (fixed width)
- ❌ Desktop-first approach
- ❌ Touch targets เล็กเกินไป (< 44px)
- ❌ Hover-only interactions บน mobile
- ❌ มี horizontal scroll บน mobile
- ❌ ไม่ทดสอบบนหลาย device sizes

#### Documentation:
- ❌ ไม่มีคอมเม้นต์ภาษาไทย
- ❌ ไม่มี JSDoc/PHPDoc
- ❌ ไม่มี @example usage
- ❌ ไม่มี @tip การใช้งาน
- ❌ ไม่มีคู่มือการใช้งาน (props, params, etc.)

#### Code Quality:
- ❌ Code messy, unreadable, หรือ duplicated
- ❌ ละเลย error handling
- ❌ ไม่มี type hints หรือ validation
- ❌ ละเลย accessibility
- ❌ ไม่ทดสอบก่อน commit
- ❌ มี technical debt หรือ code smells

#### Routes & Views:
- ❌ Routes ซ้ำหรือไม่มี naming convention
- ❌ ไม่มี middleware ที่จำเป็น
- ❌ Views ไม่ check null/empty data
- ❌ ไม่ทดสอบ routes ทำงานถูกต้อง
- ❌ Controller ส่งข้อมูลไม่ครบ

#### Database:
- ❌ Tables/Columns ซ้ำ
- ❌ Foreign keys ไม่ถูกต้อง หรือไม่มี cascade rules
- ❌ ไม่มี indexes สำหรับ columns ที่ค้นหาบ่อย
- ❌ ไม่มี unique constraints ป้องกันข้อมูลซ้ำ
- ❌ Data types ไม่เหมาะสม
- ❌ ไม่มี migration rollback (down method)
- ❌ Model relationships ไม่ตรงกับ database

#### Deployment & Packages:
- ❌ ติดตั้ง composer package แล้วไม่เพิ่มใน deploy.sh
- ❌ ใช้รูปแบบที่ไม่สม่ำเสมอกับ packages อื่นใน deploy.sh
- ❌ ไม่มี error handling หรือ fallback message
- ❌ ไม่ตรวจสอบว่า package ติดตั้งแล้วหรือยัง
- ❌ ไม่แสดง version ของ package
- ❌ ทำให้ deployment fail ถ้า optional package ติดตั้งไม่สำเร็จ
- ❌ ไม่ทดสอบ deployment script หลังแก้ไข

---

## 🎯 สรุปสุดท้าย

**"โปรแกรมที่เราพัฒนาต้องมีคุณภาพระดับหลักล้าน"**

ทุกครั้งที่เขียนโค้ด ถามตัวเองว่า:
1. ✅ UI สวยงามระดับมืออาชีพหรือยัง?
2. ✅ รองรับ dark/light mode และ responsive หรือยัง?
3. ✅ มีคอมเม้นต์และคู่มือครบถ้วนหรือยัง?
4. ✅ Routes/Views ตรวจสอบแล้วหรือยัง?
5. ✅ Database schema ถูกต้องและมี indexes หรือยัง?
6. ✅ Composer packages ทั้งหมดอยู่ใน deploy.sh แล้วหรือยัง?
7. ✅ Code clean และไม่มี technical debt หรือยัง?
8. ✅ ทดสอบครบทุก device และทุก scenario หรือยัง?
9. ✅ น่าภูมิใจที่จะให้คนอื่นใช้หรือยัง?

**ถ้าตอบ "ใช่" ทั้ง 9 ข้อ แสดงว่าโค้ดของเรามีคุณภาพระดับหลักล้าน! 💎✨**

---

*"Excellence is not an act, but a habit" - ทำให้ทุกโค้ดเป็นผลงานที่ภาคภูมิใจ*

*"Quality is never an accident; it is always the result of intelligent effort" - ใส่ใจในทุกรายละเอียด*

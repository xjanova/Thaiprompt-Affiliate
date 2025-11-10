# Routes & Views Guidelines

## 🛣️ การจัดการ Routes และ Views (บังคับ)

**ทุกครั้งที่สร้างหรือแก้ไข Routes และ Views ต้องตรวจสอบความถูกต้อง**

---

## 1. Route Verification Checklist

**ก่อนสร้าง Route ใหม่:**
- [ ] ตรวจสอบว่าไม่มี route ซ้ำ (duplicate routes)
- [ ] ตรวจสอบ route naming conflicts
- [ ] ตรวจสอบว่า route parameters ไม่ซ้ำกับ route อื่น
- [ ] ตรวจสอบ middleware ที่จำเป็น (auth, role, permission)
- [ ] ตรวจสอบ route grouping และ prefix

---

## 2. Route Best Practices

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

---

## 3. View Verification Checklist

**ก่อนสร้าง View ใหม่:**
- [ ] ตรวจสอบว่า view file ไม่ซ้ำ
- [ ] ตรวจสอบว่า view path ถูกต้อง
- [ ] ตรวจสอบ layout และ component dependencies
- [ ] ตรวจสอบ data variables ที่ส่งมาจาก controller
- [ ] ตรวจสอบว่ามี error handling สำหรับข้อมูลที่อาจเป็น null

---

## 4. View Best Practices

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

---

## 5. Controller → View Data Flow

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

---

## 6. Route Testing Commands

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

---

## 7. Common Route/View Issues

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

---

**Last Updated:** 2025-11-10
**Version:** 1.0

# 👑 Super Admin System

ระบบ Super Admin สำหรับควบคุมและจัดการระบบทั้งหมดด้วยสิทธิ์สูงสุด

---

## 🔐 ความสามารถของ Super Admin

Super Admin มีสิทธิ์เข้าถึงและจัดการทุกอย่างในระบบ:

- ✅ **เข้าถึงได้ทุกหน้า** - รวมถึงหน้าที่ผู้ใช้ทั่วไปและ Admin ไม่สามารถเข้าถึงได้
- ✅ **จัดการผู้ใช้ทุกคน** - สร้าง, แก้ไข, ลบ ผู้ใช้ทุกประเภท
- ✅ **จัดการ Affiliates** - อนุมัติ, ระงับ, แก้ไขข้อมูล
- ✅ **จัดการ Commissions** - อนุมัติ, ปฏิเสธ, จ่ายเงิน
- ✅ **จัดการ Settings** - เปลี่ยนแปลงการตั้งค่าระบบทั้งหมด
- ✅ **ดูรายงานทั้งหมด** - สถิติ, รายงานการเงิน, logs
- ✅ **ไม่สามารถถูกลบได้** - ป้องกันการลบโดยไม่ตั้งใจ

---

## 🚀 การสร้าง Super Admin ครั้งแรก

### ผ่าน Setup Wizard (แนะนำ)

เมื่อติดตั้งระบบครั้งแรก:

1. เปิด browser ไปที่ `https://yourdomain.com`
2. ระบบจะ redirect ไปหน้า **Setup Wizard** อัตโนมัติ
3. กรอกข้อมูล:
   ```
   ชื่อ: Admin User
   อีเมล: admin@example.com
   รหัสผ่าน: ********
   ยืนยันรหัสผ่าน: ********
   ```
4. คลิก **"สร้างบัญชี Super Admin"**
5. เข้าสู่ระบบสำเร็จ!

### ผ่าน Artisan Command

```bash
php artisan tinker
```

```php
// สร้าง Super Admin ใหม่
$user = App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'superadmin@example.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'super_admin',
    'is_super_admin' => true,
]);

// สร้าง Affiliate สำหรับ Super Admin
App\Models\Affiliate::create([
    'user_id' => $user->id,
    'referral_code' => App\Models\Affiliate::generateReferralCode(),
    'level' => 1,
    'status' => 'active',
]);
```

---

## 🔒 การใช้ Super Admin Middleware

### ใน Routes

```php
// Route ที่ต้องการสิทธิ์ Super Admin เท่านั้น
Route::middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/admin/system-settings', [SystemController::class, 'index']);
    Route::get('/admin/all-users', [UserController::class, 'all']);
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
});
```

### ใน Controllers

```php
class SystemSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super_admin']);
    }

    public function index()
    {
        // เฉพาะ Super Admin เท่านั้นที่เข้าถึงได้
        return view('admin.system-settings');
    }
}
```

### เช็คใน Blade Templates

```blade
@auth
    @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.system-settings') }}" class="btn">
            ⚙️ System Settings
        </a>

        <a href="{{ route('admin.users.delete-all') }}" class="btn btn-danger">
            🗑️ Delete All Users
        </a>
    @endif
@endauth
```

### เช็คใน Controllers/Models

```php
// ใน Controller
public function update(Request $request, User $user)
{
    if (!auth()->user()->isSuperAdmin()) {
        abort(403, 'Unauthorized action.');
    }

    // อัพเดทผู้ใช้
    $user->update($request->validated());
}

// ตรวจสอบสิทธิ์
if (auth()->user()->isSuperAdmin()) {
    // ทำอะไรก็ได้
}
```

---

## 👥 ความแตกต่างระหว่าง Roles

| ความสามารถ | User | Admin | Super Admin |
|-----------|------|-------|-------------|
| ดู Dashboard | ❌ | ✅ | ✅ |
| จัดการโปรไฟล์ตัวเอง | ✅ | ✅ | ✅ |
| จัดการผู้ใช้อื่น | ❌ | ⚠️ บางส่วน | ✅ ทุกคน |
| จัดการ Affiliates | ❌ | ⚠️ บางส่วน | ✅ ทั้งหมด |
| อนุมัติ Commissions | ❌ | ✅ | ✅ |
| เปลี่ยน System Settings | ❌ | ❌ | ✅ |
| ลบผู้ใช้คนอื่น | ❌ | ⚠️ บางคน | ✅ ทุกคน |
| ดู Logs | ❌ | ⚠️ บางส่วน | ✅ ทั้งหมด |

**สัญลักษณ์:**
- ✅ = เข้าถึงได้เต็มที่
- ⚠️ = เข้าถึงได้บางส่วน
- ❌ = เข้าถึงไม่ได้

---

## 🛡️ การป้องกัน Super Admin

### 1. ป้องกันการลบ

```php
// ใน app/Models/User.php
public static function boot()
{
    parent::boot();

    static::deleting(function ($user) {
        if ($user->is_super_admin) {
            throw new \Exception('Cannot delete Super Admin!');
        }
    });
}
```

### 2. ป้องกันการเปลี่ยน Role

```php
// ใน Controller
public function updateRole(Request $request, User $user)
{
    if ($user->is_super_admin && !auth()->user()->isSuperAdmin()) {
        abort(403, 'Cannot modify Super Admin role');
    }

    $user->update(['role' => $request->role]);
}
```

### 3. Audit Logging

```php
// บันทึกการกระทำของ Super Admin
if (auth()->user()->isSuperAdmin()) {
    Log::info('Super Admin action', [
        'user_id' => auth()->id(),
        'action' => 'deleted_user',
        'target_user' => $user->id,
        'ip' => request()->ip(),
    ]);
}
```

---

## 📊 ตัวอย่างการใช้งาน

### ตัวอย่างที่ 1: เมนู Admin

```blade
{{-- resources/views/layouts/admin.blade.php --}}
<nav class="sidebar">
    <ul>
        <li><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
        <li><a href="{{ route('admin.users.index') }}">{{ __('messages.users') }}</a></li>
        <li><a href="{{ route('admin.affiliates.index') }}">{{ __('messages.affiliates') }}</a></li>
        <li><a href="{{ route('admin.commissions.index') }}">{{ __('messages.commissions') }}</a></li>

        @if(auth()->user()->isSuperAdmin())
            {{-- Super Admin Only --}}
            <li class="divider">Super Admin</li>
            <li><a href="{{ route('admin.system-settings') }}">⚙️ System Settings</a></li>
            <li><a href="{{ route('admin.logs') }}">📋 System Logs</a></li>
            <li><a href="{{ route('admin.backup') }}">💾 Backup</a></li>
        @endif
    </ul>
</nav>
```

### ตัวอย่างที่ 2: User Management

```php
// app/Http/Controllers/Admin/UserController.php
public function destroy(User $user)
{
    // ตรวจสอบสิทธิ์ Super Admin
    if (!auth()->user()->isSuperAdmin()) {
        return redirect()->back()->with('error', __('auth.forbidden'));
    }

    // ป้องกันการลบ Super Admin
    if ($user->is_super_admin) {
        return redirect()->back()->with('error', 'Cannot delete Super Admin!');
    }

    // ลบผู้ใช้
    $user->delete();

    return redirect()->route('admin.users.index')
        ->with('success', __('messages.user_deleted'));
}
```

### ตัวอย่างที่ 3: System Settings

```php
// app/Http/Controllers/Admin/SettingsController.php
class SettingsController extends Controller
{
    public function __construct()
    {
        // เฉพาะ Super Admin เท่านั้น
        $this->middleware(['auth', 'super_admin']);
    }

    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()
            ->with('success', __('messages.settings_updated'));
    }
}
```

---

## 🔧 การตั้งค่าเพิ่มเติม

### เปลี่ยน User ธรรมดาเป็น Super Admin

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'user@example.com')->first();
$user->is_super_admin = true;
$user->role = 'super_admin';
$user->save();
```

### ถอดสิทธิ์ Super Admin

```php
$user = App\Models\User::where('email', 'former-admin@example.com')->first();
$user->is_super_admin = false;
$user->role = 'admin'; // หรือ 'user'
$user->save();
```

---

## 🐛 Troubleshooting

### ปัญหา: Super Admin ไม่สามารถเข้าถึงบางหน้าได้

**วิธีแก้:**
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# ตรวจสอบ middleware
php artisan route:list --name=admin
```

### ปัญหา: ลืมรหัสผ่าน Super Admin

**วิธีแก้:**
```bash
php artisan tinker
```

```php
$user = App\Models\User::where('is_super_admin', true)->first();
$user->password = bcrypt('new-password');
$user->save();
```

---

## 📞 ความช่วยเหลือ

- **Documentation**: README.md, DEVELOPMENT.md
- **Multi-language**: MULTI-LANGUAGE.md
- **GitHub Issues**: [Report a bug](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

---

**👑 Super Admin คือผู้มีอำนาจสูงสุดในระบบ - ใช้อย่างระมัดระวัง!**

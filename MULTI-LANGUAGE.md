# 🌍 ระบบหลายภาษา (Multi-Language System)

TP-Affiliate รองรับหลายภาษาและสามารถเพิ่มภาษาใหม่ได้อย่างง่ายดาย

---

## 📋 ภาษาที่รองรับในปัจจุบัน

- 🇹🇭 **ไทย** (Thai) - ภาษาเริ่มต้น
- 🇬🇧 **อังกฤษ** (English)

---

## 🚀 วิธีใช้งาน

### 1. เปลี่ยนภาษาผ่าน UI

ใช้ Language Switcher component ที่มุมบนขวาของหน้าเว็บ:

```blade
<x-language-switcher />
```

### 2. เปลี่ยนภาษาผ่าน URL

```
https://yourdomain.com/lang/th   (เปลี่ยนเป็นไทย)
https://yourdomain.com/lang/en   (เปลี่ยนเป็นอังกฤษ)
```

### 3. ใช้ใน Blade Templates

```blade
{{-- แสดงข้อความแปล --}}
<h1>{{ __('messages.welcome') }}</h1>

{{-- แสดงข้อความแปลพร้อม parameter --}}
<p>{{ __('messages.hello', ['name' => $user->name]) }}</p>

{{-- เลือกไฟล์ภาษา --}}
<label>{{ __('auth.email') }}</label>
<input type="email" name="email">
```

### 4. ใช้ใน Controllers

```php
// แสดงข้อความแปล
return redirect()->back()->with('success', __('messages.action_success'));

// ตรวจสอบภาษาปัจจุบัน
$currentLocale = app()->getLocale(); // 'th' หรือ 'en'

// เปลี่ยนภาษาชั่วคราว
app()->setLocale('en');
```

---

## 📁 โครงสร้างไฟล์ภาษา

```
lang/
├── en/                    # ภาษาอังกฤษ
│   ├── auth.php          # การ login, register
│   ├── messages.php      # ข้อความทั่วไป
│   └── validation.php    # ข้อความ validation
│
└── th/                    # ภาษาไทย
    ├── auth.php          # การเข้าสู่ระบบ, สมัครสมาชิก
    ├── messages.php      # ข้อความทั่วไป
    └── validation.php    # ข้อความตรวจสอบ
```

---

## ➕ วิธีเพิ่มภาษาใหม่

### Step 1: เพิ่มภาษาใน config

แก้ไขไฟล์ `config/app.php`:

```php
'supported_locales' => ['en', 'th', 'ja'], // เพิ่ม 'ja' สำหรับภาษาญี่ปุ่น
```

### Step 2: สร้างโฟลเดอร์ภาษา

```bash
mkdir lang/ja
```

### Step 3: สร้างไฟล์แปล

คัดลอกไฟล์จากภาษาอื่น:

```bash
cp lang/en/auth.php lang/ja/auth.php
cp lang/en/messages.php lang/ja/messages.php
```

### Step 4: แปลข้อความ

แก้ไข `lang/ja/auth.php`:

```php
<?php

return [
    'login' => 'ログイン',
    'email' => 'メールアドレス',
    'password_label' => 'パスワード',
    // ... แปลข้อความอื่นๆ
];
```

### Step 5: เพิ่มใน Language Switcher

แก้ไข `app/Http/Controllers/LanguageController.php`:

```php
public function getLanguages()
{
    return [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇬🇧',
        ],
        'th' => [
            'name' => 'Thai',
            'native' => 'ไทย',
            'flag' => '🇹🇭',
        ],
        'ja' => [                    // เพิ่มภาษาใหม่
            'name' => 'Japanese',
            'native' => '日本語',
            'flag' => '🇯🇵',
        ],
    ];
}
```

### Step 6: อัพเดท Language Switcher UI

แก้ไข `resources/views/components/language-switcher.blade.php`:

```blade
{{-- Japanese --}}
<a href="{{ route('lang.switch', 'ja') }}" class="...">
    <span class="mr-2">🇯🇵</span>
    <span>日本語</span>
</a>
```

---

## 🎯 ตัวอย่างการใช้งาน

### ตัวอย่างที่ 1: Dashboard

```blade
{{-- resources/views/admin/dashboard.blade.php --}}
<h1>{{ __('messages.dashboard') }}</h1>

<div class="stats">
    <div class="stat">
        <h3>{{ __('messages.total_users') }}</h3>
        <p>{{ $totalUsers }}</p>
    </div>
    <div class="stat">
        <h3>{{ __('messages.total_affiliates') }}</h3>
        <p>{{ $totalAffiliates }}</p>
    </div>
</div>
```

### ตัวอย่างที่ 2: Login Form

```blade
{{-- resources/views/auth/login.blade.php --}}
<form method="POST" action="{{ route('login') }}">
    @csrf

    <label>{{ __('auth.email') }}</label>
    <input type="email" name="email" required>

    <label>{{ __('auth.password_label') }}</label>
    <input type="password" name="password" required>

    <button type="submit">{{ __('auth.login') }}</button>
</form>
```

### ตัวอย่างที่ 3: Flash Messages

```php
// Controller
return redirect()->route('admin.dashboard')
    ->with('success', __('messages.action_success'));

return redirect()->back()
    ->with('error', __('auth.unauthorized'));
```

---

## 📚 ไฟล์ภาษาที่สำคัญ

### `lang/th/auth.php`
- การเข้าสู่ระบบ
- การสมัครสมาชิก
- ข้อความ error authentication

### `lang/th/messages.php`
- ข้อความทั่วไป
- เมนู navigation
- Dashboard
- Affiliates
- Commissions
- Settings

### `lang/th/validation.php` (Laravel default)
- ข้อความ validation
- ข้อความ error จาก form

---

## 🔧 Configuration

### ตั้งค่าภาษาเริ่มต้น

แก้ไข `config/app.php`:

```php
'locale' => 'th',              // ภาษาเริ่มต้น (Thai)
'fallback_locale' => 'en',     // ภาษาสำรอง (English)
```

### ตั้งค่าใน .env

```env
APP_LOCALE=th
APP_FALLBACK_LOCALE=en
```

---

## 🛠️ Middleware

### SetLocale Middleware

ตรวจสอบและตั้งค่าภาษาอัตโนมัติจาก:
1. Query parameter `?lang=th`
2. Session
3. Default locale จาก config

**ติดตั้งอัตโนมัติ** ใน `bootstrap/app.php`:

```php
$middleware->web(append: [
    \App\Http\Middleware\SetLocale::class,
]);
```

---

## 💡 Tips

### 1. การใช้ Pluralization

```php
// ใน lang/en/messages.php
'apples' => '{0} No apples|{1} One apple|[2,*] :count apples',

// ใช้งาน
echo trans_choice('messages.apples', 0);  // No apples
echo trans_choice('messages.apples', 1);  // One apple
echo trans_choice('messages.apples', 10); // 10 apples
```

### 2. การแทนค่า Parameters

```php
// ใน lang/th/messages.php
'welcome_user' => 'ยินดีต้อนรับ :name',

// ใช้งาน
__('messages.welcome_user', ['name' => 'John'])
// Output: ยินดีต้อนรับ John
```

### 3. การเช็คภาษาปัจจุบัน

```php
@if(app()->getLocale() === 'th')
    <p>แสดงเฉพาะภาษาไทย</p>
@endif

@if(app()->isLocale('en'))
    <p>Show only in English</p>
@endif
```

---

## 🐛 Troubleshooting

### ปัญหา: ข้อความไม่แปล

**สาเหตุ:** ไม่มี key ในไฟล์ภาษา

**วิธีแก้:**
```bash
# เช็คว่าไฟล์มีอยู่
ls -la lang/th/

# เช็คว่ามี key ในไฟล์
grep "welcome" lang/th/messages.php
```

### ปัญหา: ภาษาไม่เปลี่ยน

**วิธีแก้:**
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# ตรวจสอบ middleware
# ตรวจสอบว่า SetLocale middleware ถูก register ใน bootstrap/app.php
```

---

## 📞 ความช่วยเหลือ

- **Documentation**: README.md
- **GitHub**: [Issues](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

---

**🎉 ตอนนี้คุณสามารถรองรับหลายภาษาได้แล้ว!**

# ระบบ Two-Factor Authentication (2FA) สำหรับ Thaiprompt-Affiliate

## ภาพรวม

ระบบ 2FA ที่พัฒนาขึ้นรองรับการยืนยันตัวตนด้วย:
- ✅ SMS (Twilio, Nexmo, Custom API)
- ✅ LINE OA Messaging
- 🔄 Email (Coming soon)

## คุณสมบัติหลัก

### 1. การตั้งค่าระดับแอดมิน
- เปิด/ปิดระบบ 2FA ทั้งหมด
- กำหนดจุดที่ต้องใช้ 2FA:
  - ✅ การเข้าใช้งาน (Login)
  - ✅ การถอนเงิน (Withdrawal)
  - ✅ การโอนเงิน (Transfer)
  - ✅ การเปลี่ยนแปลงโปรไฟล์
  - ✅ การเปลี่ยนรหัสผ่าน
  - ✅ การเปลี่ยนแปลงวิธีการชำระเงิน
- กำหนด threshold สำหรับการถอน/โอนเงิน
- ตั้งค่า grace period และ remember device

### 2. การตั้งค่าระดับ User
- เลือกวิธีการยืนยันตัวตน (SMS/LINE)
- จัดการอุปกรณ์ที่เชื่อถือ (Trusted Devices)
- Recovery Codes สำหรับกรณีฉุกเฉิน
- สถิติการใช้งาน 2FA

### 3. LINE OA Integration
- ส่ง OTP ผ่าน LINE Messaging API
- Template message ภาษาไทย
- รองรับ user ที่เชื่อมต่อบัญชี LINE แล้ว

## โครงสร้างไฟล์

### Migrations (4 files)
```
database/migrations/
├── 2025_11_06_150001_add_line_support_to_otp_settings_table.php
├── 2025_11_06_150002_add_two_factor_fields_to_otp_verifications_table.php
├── 2025_11_06_150003_create_two_factor_settings_table.php
└── 2025_11_06_150004_create_two_factor_user_settings_table.php
```

### Models (4 files)
```
app/Models/
├── TwoFactorSetting.php          # การตั้งค่าระดับระบบ
├── TwoFactorUserSetting.php      # การตั้งค่าของแต่ละ user
├── OtpSetting.php                # อัพเดทรองรับ LINE OA
└── OtpVerification.php           # อัพเดทรองรับ multi-channel
```

### Services (2 files)
```
app/Services/
├── OtpService.php                # รองรับการส่ง OTP ผ่าน LINE
└── TwoFactorService.php          # จัดการ 2FA ทั้งหมด
```

### Middleware (1 file)
```
app/Http/Middleware/
└── RequireTwoFactor.php          # ตรวจสอบ 2FA ก่อนเข้าถึงฟังก์ชัน
```

### Controllers (2 files)
```
app/Http/Controllers/
├── Admin/TwoFactorSettingsController.php    # Admin panel
└── User/TwoFactorController.php             # User 2FA management
```

## การติดตั้งและใช้งาน

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. ตั้งค่า LINE OA
1. เข้าไปที่ Admin Panel > OTP Settings
2. เปิดใช้งาน LINE OTP
3. ตั้งค่า message template

### 3. เปิดใช้งาน 2FA
1. เข้าไปที่ Admin Panel > Two-Factor Settings
2. เปิดใช้งาน 2FA
3. เลือกจุดที่ต้องการใช้ 2FA
4. กำหนดค่าต่างๆ ตามต้องการ

### 4. User Setup 2FA
1. User เข้าไปที่ Profile > Two-Factor Authentication
2. เลือกวิธีการยืนยันตัวตน (SMS/LINE)
3. ยืนยันการตั้งค่า
4. เก็บ recovery codes ไว้ในที่ปลอดภัย

## การใช้งานใน Code

### ตรวจสอบว่าต้องใช้ 2FA หรือไม่
```php
use App\Services\TwoFactorService;

public function withdraw(Request $request, TwoFactorService $twoFactorService)
{
    $user = Auth::user();
    $amount = $request->input('amount');

    if ($twoFactorService->isRequired('withdrawal', $user, $amount)) {
        // Redirect to 2FA verification
        return redirect()->route('two-factor.verify', [
            'action' => 'withdrawal',
            'redirect' => route('wallet.withdraw'),
        ]);
    }

    // Proceed with withdrawal
}
```

### ใช้ Middleware
```php
// In routes/web.php
Route::middleware(['auth', 'two-factor:withdrawal'])->group(function () {
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
});
```

### ส่ง OTP
```php
use App\Services\TwoFactorService;

$twoFactorService->sendCode($user, 'withdrawal');
```

### ตรวจสอบ OTP
```php
$result = $twoFactorService->verifyCode($user, $code, 'withdrawal', $rememberDevice);

if ($result['success']) {
    // OTP verified successfully
}
```

## สิ่งที่ต้องทำต่อ (30% remaining)

### 1. Views (ยังไม่ได้สร้าง)
- `resources/views/admin/two-factor/settings.blade.php` - Admin settings page
- `resources/views/user/two-factor/setup.blade.php` - User setup page
- `resources/views/user/two-factor/verify.blade.php` - Verification page
- `resources/views/user/two-factor/recovery-codes.blade.php` - Recovery codes page

### 2. Routes (ยังไม่ได้เพิ่ม)
```php
// In routes/web.php

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/two-factor/settings', [TwoFactorSettingsController::class, 'index']);
    Route::post('/two-factor/settings', [TwoFactorSettingsController::class, 'update']);
});

// User routes
Route::middleware(['auth'])->group(function () {
    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup']);
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable']);
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable']);
    Route::get('/two-factor/verify', [TwoFactorController::class, 'verify']);
    Route::post('/two-factor/send-code', [TwoFactorController::class, 'sendCode']);
    Route::post('/two-factor/verify-code', [TwoFactorController::class, 'verifyCode']);
    Route::post('/two-factor/verify-recovery-code', [TwoFactorController::class, 'verifyRecoveryCode']);
    Route::get('/two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes']);
    Route::post('/two-factor/regenerate-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    Route::delete('/two-factor/trusted-devices/{fingerprint}', [TwoFactorController::class, 'removeTrustedDevice']);
    Route::delete('/two-factor/trusted-devices', [TwoFactorController::class, 'removeAllTrustedDevices']);
});
```

### 3. Integration Points
- แก้ไข `LoginController` เพิ่ม 2FA check หลัง login
- แก้ไข `WalletService` เพิ่ม 2FA check ก่อน transfer
- แก้ไข `WithdrawalService` เพิ่ม 2FA check ก่อน withdraw
- แก้ไข Admin `OtpSettingsController` เพิ่ม UI สำหรับ LINE OA settings

### 4. Testing
- Unit tests สำหรับ TwoFactorService
- Integration tests สำหรับ 2FA flow
- E2E tests สำหรับ user journey

## Database Schema

### two_factor_settings
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| enabled | boolean | เปิด/ปิด 2FA |
| require_on_login | boolean | ต้องใช้ 2FA ตอน login |
| require_on_withdrawal | boolean | ต้องใช้ 2FA ตอนถอนเงิน |
| require_on_transfer | boolean | ต้องใช้ 2FA ตอนโอนเงิน |
| allow_sms | boolean | อนุญาตใช้ SMS |
| allow_line | boolean | อนุญาตใช้ LINE |
| default_method | string | วิธีเริ่มต้น |
| grace_period_minutes | integer | ช่วงเวลา grace period |
| withdrawal_threshold | decimal | ขีดจำกัดการถอนเงิน |

### two_factor_user_settings
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| enabled | boolean | เปิดใช้ 2FA หรือไม่ |
| preferred_method | string | วิธีที่ชอบ (sms/line) |
| phone_verified | boolean | เบอร์โทรยืนยันแล้ว |
| line_verified | boolean | LINE ยืนยันแล้ว |
| recovery_codes | json | รหัสกู้คืน |
| trusted_devices | json | อุปกรณ์ที่เชื่อถือ |
| last_verified_at | timestamp | ยืนยันล่าสุด |

## API Reference

### TwoFactorService

#### `isRequired(string $action, User $user, ?float $amount = null): bool`
ตรวจสอบว่าต้องใช้ 2FA หรือไม่

#### `sendCode(User $user, string $action = 'verification'): array`
ส่งรหัส OTP ไปยัง user

#### `verifyCode(User $user, string $code, string $action, bool $rememberDevice): array`
ตรวจสอบรหัส OTP

#### `verifyRecoveryCode(User $user, string $code, string $action): array`
ตรวจสอบ recovery code

#### `enable(User $user, string $preferredMethod): array`
เปิดใช้งาน 2FA สำหรับ user

#### `disable(User $user): array`
ปิดใช้งาน 2FA

#### `getStatus(User $user): array`
ดึงข้อมูลสถานะ 2FA ของ user

## Security Considerations

1. **Rate Limiting**: จำกัดจำนวนครั้งในการส่ง OTP
2. **Recovery Codes**: เข้ารหัสด้วย bcrypt
3. **Device Fingerprinting**: SHA-256 hash
4. **Session Management**: Grace period และ trusted devices
5. **Audit Logging**: บันทึกการใช้งาน 2FA

## Performance

- ใช้ Cache สำหรับ TwoFactorSetting (3600 seconds)
- Indexed foreign keys และ composite indexes
- Lazy loading สำหรับ relationships

## Support

หากมีปัญหาหรือข้อสงสัย กรุณาติดต่อ:
- GitHub Issues: [Repository Link]
- Email: support@example.com

---

**สร้างโดย:** Claude AI (Anthropic)
**วันที่:** November 6, 2025
**เวอร์ชัน:** 1.0.0

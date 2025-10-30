# ระบบ Wallet แบบครบวงจร

## ภาพรวม

ระบบกระเป๋าเงินอิเล็กทรอนิกส์ที่สมบูรณ์แบบสำหรับระบบ Affiliate พร้อมฟีเจอร์ระดับมืออาชีพ

## ฟีเจอร์หลัก

### 1. ระบบกระเป๋าเงิน (Wallet System)
- ✅ ยอดเงินคงเหลือแบบเรียลไทม์
- ✅ รองรับหลายสกุลเงิน (ปัจจุบันรองรับ THB)
- ✅ ระบบ PIN code สำหรับการยืนยันตัวตน
- ✅ ระบบ 2FA (Two-Factor Authentication)
- ✅ ระบบล็อคกระเป๋าอัตโนมัติเมื่อมีการพยายามเข้าถึงที่ผิดพลาด
- ✅ Wallet Address ที่ไม่ซ้ำกัน (รูปแบบ: TPW + 16 ตัวอักษร)

### 2. ระบบฝากเงิน (Deposit System)
- ✅ **PromptPay** - ชำระผ่าน QR Code
- ✅ **Bank Transfer** - โอนผ่านธนาคารพร้อมอัพโหลดสลิป
- ✅ **Stripe** - ชำระด้วยบัตรเครดิต/เดบิต
- ✅ **PayPal** - ชำระผ่าน PayPal
- ✅ กำหนดยอดฝากขั้นต่ำได้
- ✅ ระบบแจ้งเตือนเมื่อฝากเงินสำเร็จ

### 3. ระบบถอนเงิน (Withdrawal System)
- ✅ **ต้องผ่านการอนุมัติจากแอดมิน**
- ✅ คำนวณค่าธรรมเนียมอัตโนมัติ (แบบคงที่หรือเปอร์เซ็นต์)
- ✅ หักภาษี ณ ที่จ่ายอัตโนมัติ (ตั้งค่าได้)
- ✅ แนบสลิปการโอนเงินจากแอดมิน
- ✅ ระบบแจ้งเตือนทุกขั้นตอน (รอดำเนินการ, อนุมัติ, ปฏิเสธ, เสร็จสิ้น)
- ✅ สถานะการถอนหลายแบบ: Pending, Processing, Approved, Rejected, Cancelled, Completed
- ✅ ผู้ใช้สามารถยกเลิกคำขอที่รอดำเนินการได้
- ✅ แอดมินสามารถอนุมัติหลายรายการพร้อมกันได้

### 4. ระบบโอนเงิน (Transfer System)
- ✅ โอนเงินระหว่างผู้ใช้
- ✅ ต้องยืนยันด้วย PIN code
- ✅ คำนวณค่าธรรมเนียมการโอน
- ✅ ระบบแจ้งเตือนผู้รับและผู้ส่ง

### 5. ระบบค่าธรรมเนียมและภาษี
- ✅ ตั้งค่าค่าธรรมเนียมการถอน (Fixed หรือ Percentage)
- ✅ กำหนดค่าธรรมเนียมขั้นต่ำและสูงสุด
- ✅ ตั้งค่าเปอร์เซ็นต์ภาษี
- ✅ กำหนดยอดเงินขั้นต่ำที่ต้องเสียภาษี
- ✅ คำนวณอัตโนมัติและแสดงรายละเอียด

### 6. ระบบแจ้งเตือน (Notification System)
- ✅ **รูประฆัง (Bell Icon)** พร้อมจำนวนการแจ้งเตือนที่ยังไม่ได้อ่าน
- ✅ แจ้งเตือนแบบเรียลไทม์
- ✅ หลายระดับความสำคัญ: Low, Normal, High, Urgent
- ✅ รองรับหลายประเภท: Wallet, Withdrawal, Deposit, Transfer, Commission, System
- ✅ ไอคอนและสีที่แตกต่างกันตามประเภท
- ✅ Action Button เชื่อมโยงไปยังหน้าที่เกี่ยวข้อง
- ✅ ระบบหมดอายุอัตโนมัติ
- ✅ สามารถ Archive และลบได้
- ✅ รองรับการส่งผ่าน Email และ Push Notification (พร้อมใช้งาน)

### 7. ระบบจัดการช่องทางการรับเงิน (Payment Methods)
- ✅ เพิ่ม/ลบ/แก้ไขช่องทางการรับเงิน
- ✅ รองรับ: PromptPay, Bank Transfer, Stripe, PayPal
- ✅ ตั้งค่าช่องทางเริ่มต้น
- ✅ เก็บข้อมูลบัญชีอย่างปลอดภัย
- ✅ แสดงเลขบัญชีแบบซ่อน (Masked)
- ✅ ระบบยืนยันช่องทางการรับเงิน

### 8. ระบบประวัติและ Audit Log
- ✅ บันทึกทุกธุรกรรม พร้อม Transaction ID ที่ไม่ซ้ำกัน
- ✅ บันทึก IP Address และ User Agent
- ✅ Wallet Logs สำหรับติดตาม Security Events
- ✅ ระบบ Soft Delete สำหรับข้อมูลสำคัญ
- ✅ เก็บยอดเงินก่อนและหลังทุกธุรกรรม

### 9. ระบบแอดมิน (Admin Dashboard)
- ✅ **ดูกระเป๋าเงินทั้งหมดในระบบ**
- ✅ **อนุมัติ/ปฏิเสธคำขอถอนเงิน**
- ✅ **อัพโหลดสลิปการโอนเงิน**
- ✅ จัดการค่าธรรมเนียมและภาษี
- ✅ ดูสถิติและรายงาน
- ✅ ระบบ Permission สำหรับการจัดการสิทธิ์
- ✅ ฝากเงินเข้ากระเป๋าผู้ใช้ได้โดยตรง

### 10. ระบบ Settings (Wallet Settings)
- ✅ ตั้งค่าผ่านฐานข้อมูลแบบ Dynamic
- ✅ Cache settings เพื่อประสิทธิภาพ
- ✅ จัดกลุ่ม settings: fees, tax, limits, payment_methods, general
- ✅ แสดงให้ผู้ใช้เห็นได้ (is_public)
- ✅ เปิด/ปิดการใช้งานแต่ละ setting

## โครงสร้างฐานข้อมูล

### Tables
1. **wallets** - กระเป๋าเงินหลัก
2. **wallet_transactions** - ธุรกรรมทั้งหมด
3. **wallet_logs** - บันทึกการใช้งานและความปลอดภัย
4. **withdrawal_requests** - คำขอถอนเงิน
5. **payment_methods** - ช่องทางการรับเงิน
6. **notifications** - การแจ้งเตือน
7. **wallet_settings** - การตั้งค่าระบบ

## Models

```
├── Wallet.php
├── WalletTransaction.php
├── WalletLog.php
├── WithdrawalRequest.php
├── PaymentMethod.php
├── Notification.php
└── WalletSetting.php
```

## Services

```
├── WalletService.php - จัดการ Wallet และธุรกรรม
├── WithdrawalService.php - จัดการการถอนเงินและการอนุมัติ
├── PaymentGatewayService.php - รองรับ Payment Gateways
└── NotificationService.php - ระบบแจ้งเตือน
```

## Controllers

### Admin
```
├── WalletController.php - จัดการ Wallet ของแอดมิน
└── WithdrawalController.php - อนุมัติ/ปฏิเสธการถอนเงิน
```

### User
```
└── WalletController.php - จัดการ Wallet ของผู้ใช้
```

### Shared
```
└── NotificationController.php - การแจ้งเตือน
```

## Routes

### Admin Routes
```
/admin/wallet/* - จัดการกระเป๋าเงิน
/admin/withdrawals/* - อนุมัติการถอนเงิน
```

### User Routes
```
/user/wallet/* - กระเป๋าเงินส่วนตัว
/user/wallet/deposit - ฝากเงิน
/user/wallet/withdraw - ถอนเงิน
/user/wallet/transfer - โอนเงิน
/user/wallet/withdrawals - ประวัติการถอน
/user/wallet/transactions - ประวัติธุรกรรม
/user/wallet/payment-methods - จัดการช่องทางรับเงิน
/user/notifications/* - การแจ้งเตือน
```

## การตั้งค่า (Wallet Settings)

### Withdrawal Settings
- `withdrawal_min_amount` - ยอดถอนขั้นต่ำ (100 บาท)
- `withdrawal_max_amount` - ยอดถอนสูงสุด (100,000 บาท)
- `withdrawal_fee_type` - ประเภทค่าธรรมเนียม (fixed/percentage)
- `withdrawal_fee_amount` - จำนวนค่าธรรมเนียม (2.5%)
- `withdrawal_fee_min` - ค่าธรรมเนียมขั้นต่ำ (10 บาท)
- `withdrawal_fee_max` - ค่าธรรมเนียมสูงสุด (500 บาท)
- `withdrawal_requires_approval` - ต้องผ่านการอนุมัติ (เปิด)
- `auto_approve_threshold` - ยอดที่อนุมัติอัตโนมัติ (0 = ปิด)

### Tax Settings
- `tax_enabled` - เปิดใช้งานภาษี (เปิด)
- `tax_percentage` - เปอร์เซ็นต์ภาษี (3%)
- `tax_threshold` - ยอดขั้นต่ำที่ต้องเสียภาษี (1,000 บาท)

### Transfer Settings
- `transfer_fee_amount` - ค่าธรรมเนียมการโอน (5 บาท)
- `transfer_min_amount` - ยอดโอนขั้นต่ำ (10 บาท)

### Deposit Settings
- `deposit_min_amount` - ยอดฝากขั้นต่ำ (1 บาท)

### Payment Method Settings
- `promptpay_enabled` - เปิดใช้งาน PromptPay (เปิด)
- `stripe_enabled` - เปิดใช้งาน Stripe (ปิด)
- `paypal_enabled` - เปิดใช้งาน PayPal (ปิด)

## Security Features

1. **PIN Protection** - ทุกธุรกรรมต้องยืนยันด้วย PIN
2. **Two-Factor Authentication** - 2FA สำหรับความปลอดภัยเพิ่มเติม
3. **Failed Attempt Lockout** - ล็อคอัตโนมัติหลังพยายามผิด 5 ครั้ง
4. **IP Tracking** - บันทึก IP Address ทุกธุรกรรม
5. **Audit Logs** - บันทึกทุก action ที่สำคัญ
6. **Soft Delete** - ไม่ลบข้อมูลจริง เพื่อ audit trail
7. **Transaction Locking** - ใช้ Database Transaction เพื่อความปลอดภัย

## Permissions

สิทธิ์ใหม่ที่เพิ่มเข้ามา:
- `manage_wallets` - จัดการกระเป๋าเงิน
- `approve_withdrawals` - อนุมัติการถอนเงิน
- `view_all_wallets` - ดูกระเป๋าเงินทั้งหมด
- `manage_wallet_settings` - จัดการการตั้งค่า Wallet

## การติดตั้ง

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. สร้าง Storage Link
```bash
php artisan storage:link
```

### 3. กำหนดค่า Environment Variables (ถ้าจำเป็น)
```env
# Stripe
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret

# PayPal
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_SECRET=your_paypal_secret
```

## API Usage Examples

### สร้าง Wallet
```php
$wallet = $walletService->getOrCreateWallet($user);
```

### ฝากเงิน
```php
$transaction = $walletService->deposit(
    $wallet,
    1000, // amount
    'Deposit via PromptPay',
    'promptpay',
    $referenceId
);
```

### ถอนเงิน (สร้างคำขอ)
```php
$request = $withdrawalService->createWithdrawalRequest(
    $user,
    500, // amount
    $paymentMethodId,
    'User note'
);
```

### อนุมัติการถอน
```php
$withdrawalService->approveWithdrawal($request, $admin);
```

### โอนเงินให้เสร็จสมบูรณ์
```php
$withdrawalService->completeWithdrawal(
    $request,
    $admin,
    $slipFile,
    'Transferred successfully'
);
```

### โอนเงินระหว่างผู้ใช้
```php
$result = $walletService->transfer(
    $fromWallet,
    $toWallet,
    100,
    $pin,
    'Transfer to friend'
);
```

## Notification Examples

### แจ้งเตือนการฝากเงิน
```php
$notificationService->notifyDeposit($user, 1000);
```

### แจ้งเตือนการถอนเงินสำเร็จ
```php
$notificationService->notifyWithdrawalCompleted($user, $withdrawal);
```

### แจ้งเตือนแอดมินเมื่อมีคำขอถอนใหม่
```php
$notificationService->notifyAdminNewWithdrawal($withdrawal);
```

## Testing

ทดสอบฟีเจอร์หลัก:
1. สร้าง Wallet ให้ผู้ใช้
2. ฝากเงินเข้า Wallet
3. ขอถอนเงิน
4. แอดมินอนุมัติ
5. แอดมินอัพโหลดสลิป
6. ตรวจสอบการแจ้งเตือน
7. โอนเงินระหว่างผู้ใช้

## Performance Considerations

1. **Indexes** - เพิ่ม indexes สำหรับ columns ที่ query บ่อย
2. **Caching** - Cache wallet settings
3. **Database Transactions** - ใช้ DB::transaction() สำหรับ consistency
4. **Pagination** - แสดงผลแบบแบ่งหน้า
5. **Eager Loading** - ใช้ with() เพื่อลด N+1 queries

## Future Enhancements

- [ ] รองรับ Cryptocurrency
- [ ] ระบบ Recurring Deposits
- [ ] ระบบ Savings Account
- [ ] Integration กับ SMS Gateway สำหรับ OTP
- [ ] Mobile App API
- [ ] WebSocket สำหรับ Real-time Updates
- [ ] Export ประวัติธุรกรรมเป็น PDF/Excel
- [ ] ระบบ Dispute Resolution
- [ ] Multi-currency Support

## Support

สำหรับคำถามหรือปัญหา กรุณาติดต่อทีมพัฒนา

---

**พัฒนาโดย:** Claude Code
**วันที่:** 30 ตุลาคม 2025
**เวอร์ชัน:** 1.0.0

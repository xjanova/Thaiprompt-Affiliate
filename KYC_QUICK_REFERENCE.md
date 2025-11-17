# KYC System - Quick Reference Guide

## 📊 System Overview

| Component | Status | File Location |
|-----------|--------|--------------|
| **Database** | ✅ 90% | `database/migrations/2025_11_03_200010_create_kyc_verifications_table.php` |
| **Model** | ✅ 90% | `app/Models/KycVerification.php` (105 lines) |
| **User Controller** | ✅ 85% | `app/Http/Controllers/User/KycController.php` (181 lines) |
| **Admin Controller** | ✅ 85% | `app/Http/Controllers/Admin/KycController.php` (197 lines) |
| **OCR Service** | ✅ 95% | `app/Services/OCR/ThaiIdCardOcrService.php` (599 lines) ⭐ |
| **Notifications** | ✅ 85% | `app/Services/NotificationService.php` (KYC methods) |
| **Observer** | ✅ 100% | `app/Observers/KycVerificationObserver.php` (87 lines) |
| **User Views** | ✅ 85% | `resources/views/user/kyc/` (3 files, 680 lines) |
| **Admin Views** | ✅ 85% | `resources/views/admin/kyc/` (2 files, 435+ lines) |
| **Routes** | ✅ 100% | `routes/user.php` & `routes/admin.php` |

---

## 🗄️ Database Schema

### Primary Table: `kyc_verifications`
```
Columns: id, user_id, id_card_image, selfie_image, status, reviewed_by, 
         reviewed_at, rejection_reason, submitted_at, extracted_data, 
         timestamps
         
Status: ENUM('pending', 'approved', 'rejected')
```

### User Extensions: `users`
```
Added Fields: kyc_status, kyc_verified_at, 
              id_card_number, thai_first_name, thai_last_name,
              english_first_name, english_last_name, id_card_birth_date,
              id_card_religion, id_card_address, id_card_issue_date,
              id_card_expiry_date
```

---

## 🎯 User Workflows

### Submission Flow
```
1. User visits /user/kyc
   ↓
2. Clicks "เริ่มยืนยันตัวตน"
   ↓
3. Uploads ID card image + selfie
   ↓
4. System converts to WebP, runs OCR
   ↓
5. OCR data stored in JSON
   ↓
6. User sees status page (pending)
   ↓
7. Admin reviews & approves/rejects
   ↓
8. User gets notification ✅
```

### Status States
- **not_submitted** → No KYC submission
- **pending** → Waiting for admin review
- **approved** → ✅ KYC verified, profile auto-filled
- **rejected** → ❌ Can resubmit

---

## 🔧 Admin Workflows

### Review Flow
```
1. Admin visits /admin/kyc (dashboard)
   ↓
2. Sees statistics & pending submissions
   ↓
3. Clicks submission to review
   ↓
4. Views:
   - User info
   - ID card + selfie images
   - OCR extracted data (if available)
   ↓
5. Action: Approve ✅ or Reject ❌
   ↓
6. If Approve:
   - User profile auto-filled from OCR
   - User notified
   - kyc_status = 'approved'
   
7. If Reject:
   - User notified with reason
   - kyc_status = 'rejected'
   - User can resubmit
```

### Admin Features
- ✅ Filter by status
- ✅ Search by name/email
- ✅ View OCR extracted fields
- ✅ Approve with auto-fill
- ✅ Reject with reason
- ✅ Delete submission
- ✅ Statistics dashboard
- ✅ Pagination

---

## 🤖 OCR (Optical Character Recognition)

### Supported Documents
- ✅ Thai National ID Card (บัตรประชาชน)
- ✅ Thai Driver's License (ใบขับขี่)

### Detection Method
Uses **Google Cloud Vision API** to detect Thai text

### Extracted Fields

#### ID Card
```
✓ id_card_number (13 digits + checksum validation)
✓ thai_first_name / thai_last_name
✓ english_first_name / english_last_name
✓ birth_date (Buddhist year → Christian year)
✓ issue_date / expiry_date
✓ religion (ศาสนา)
✓ address
```

#### Driver's License
```
✓ license_number (8-11 digits)
✓ id_card_number (linked ID)
✓ Names (Thai + English)
✓ Birth date / Issue date / Expiry date
✓ license_type (ประเภทรถ)
✓ address
```

### Quality Validation
- File size: min 10KB (reject screenshots)
- Dimensions: min 300×200 pixels
- Format: JPEG, JPG, PNG
- Max 5MB per file

### Error Handling
Returns structured errors with user-friendly Thai messages:
```
{
    "success": false,
    "error": "ไม่สามารถตรวจจับข้อความจากรูปภาพได้",
    "error_code": "NO_TEXT_DETECTED",
    "suggestion": "กรุณาถ่ายรูปบัตรให้ชัดเจน..."
}
```

---

## 📧 Notifications

### User Notifications
- **New Submission**: In-app notification (admin)
- **Approval**: "การยืนยันตัวตนสำเร็จ" ✅ (in-app)
- **Rejection**: "การยืนยันตัวตนถูกปฏิเสธ" ❌ (in-app + reason)

### Admin Notifications
- **New Submission**: "ยืนยันตัวตนใหม่รออนุมัติ" (high priority)

**Note:** Currently in-app only, no email/SMS

---

## 🛠️ Key Routes

```
User Routes:
  GET    /user/kyc                  (index - view status)
  GET    /user/kyc/create           (form)
  POST   /user/kyc                  (submit)
  GET    /user/kyc/{id}             (view submission)

Admin Routes:
  GET    /admin/kyc                 (dashboard)
  GET    /admin/kyc/{id}            (review)
  POST   /admin/kyc/{id}/approve    (approve)
  POST   /admin/kyc/{id}/reject     (reject)
  DELETE /admin/kyc/{id}            (delete)
```

---

## 🎨 Frontend Stack (V3)

- **Framework**: Tailwind CSS 3.4 (pure utilities)
- **JS**: Alpine.js 3.13.5 (reactive components)
- **Components**: Blade templates + Alpine
- **Dark Mode**: ✅ Full support
- **Responsive**: ✅ Mobile-first design

### User Views
- `user/kyc/index.blade.php` - Status dashboard (209 lines)
- `user/kyc/create.blade.php` - Upload form (312 lines)
- `user/kyc/show.blade.php` - View details (159 lines)

### Admin Views
- `admin/kyc/index.blade.php` - Management dashboard (100+ lines)
- `admin/kyc/show.blade.php` - Review details (435 lines)

### Component
- `components/kyc-camera-capture.blade.php` - Camera capture (144 lines)
  - Status: Implemented but **not integrated**

---

## 🔐 Permissions

```
Required Permissions:
- view_kyc_verifications    (view list/details)
- approve_kyc               (approve/reject)
- manage_kyc                (delete submissions)

Super Admins: Auto-have all permissions
```

---

## 📋 Configuration

### Google Cloud Vision Setup
```env
In settings table:
- google_vision_enabled = 1
- google_vision_credentials_path = storage/app/google-credentials.json
- google_vision_project_id = your-project-id

Steps:
1. Create Google Cloud project
2. Enable Vision API
3. Create service account JSON
4. Download JSON to storage/app/google-credentials.json
5. Update settings above
```

---

## ⚠️ Known Limitations

### Not Implemented
- ❌ **LINE Integration** - No LINE messaging for KYC
- ❌ **Camera Integration** - Component built but not used
- ❌ **Batch Operations** - No bulk approve/reject
- ❌ **Email/SMS** - Only in-app notifications
- ❌ **Audit Logs** - No history tracking
- ❌ **Fraud Detection** - No duplicate/tampering detection
- ❌ **Liveness Detection** - No selfie validation
- ❌ **Rate Limiting** - No submission throttling

### Partial Implementation
- ⚠️ Pattern matching OCR (fallback not complete)
- ⚠️ Camera capture component (built, unused)

---

## 🚀 Performance Metrics

- **Image Format**: WebP (90% quality)
- **File Size**: Max 5MB per image
- **OCR Speed**: ~2-5 seconds (API dependent)
- **Database**: Indexed on user_id, status
- **UI**: V3 framework (lightweight, ~150KB)

---

## 📊 Completion Summary

```
Overall System: 70-75% Complete

Core Features:      ████████░░ 85%
OCR Service:        █████████░ 95% ⭐
Notifications:      ████████░░ 85%
Admin Tools:        ██████░░░░ 60%
Security:           ████░░░░░░ 40%
Integration:        ░░░░░░░░░░  0%
```

---

## 🎯 Recommended Next Steps

1. **HIGH**: Add LINE Integration for notifications
2. **MEDIUM**: Implement batch admin operations
3. **MEDIUM**: Add fraud detection (duplicate check)
4. **LOW**: Integrate camera capture component
5. **LOW**: Add email notifications

---

## 📞 Key Files Reference

```
For Implementation:
- Models: app/Models/KycVerification.php
- Controllers: app/Http/Controllers/{User,Admin}/KycController.php
- Services: app/Services/OCR/ThaiIdCardOcrService.php
- Views: resources/views/{user,admin}/kyc/

For Configuration:
- Migrations: database/migrations/2025_11_03_*
- Routes: routes/{user,admin}.php
- Settings: via database (google_vision_*)
```

---

**Last Updated**: 2025-11-17
**Repository**: xjanova/Thaiprompt-Affiliate
**Branch**: claude/line-registration-system-01T6uV5UQenHhkFnLrCwD66m

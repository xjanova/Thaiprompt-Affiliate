# LINE Quick Settings Panel - คู่มือการใช้งาน

## 📖 ภาพรวม

**LINE Quick Settings Panel** คือระบบจัดการการตั้งค่า LINE Official Account แบบรวดเร็ว ผ่านปุ่มลอยที่มุมล่างขวาของหน้า Admin

![Quick Settings Panel](https://via.placeholder.com/800x600?text=Quick+Settings+Panel+Screenshot)

---

## ✨ Features

### 1. **Floating Action Button**
- ปุ่มลอยมุมล่างขวา
- แสดงสถานะเรียลไทม์ (🟢 Active / ⚫ Inactive)
- Animation เมื่อ hover
- Tooltip แสดงข้อมูล

### 2. **Slide-in Settings Panel**
- เลื่อนเข้ามาจากด้านขวา
- Glassmorphism UI (V3 Design)
- Dark Mode Support
- Responsive (มือถือ/แท็บเล็ต/PC)

### 3. **Master Switch**
- เปิด/ปิดระบบ LINE OA ทั้งหมด
- Toggle switch แบบ animated
- อัพเดทแบบเรียลไทม์

### 4. **Feature Toggles**
- **LINE Messaging** - เปิด/ปิดการส่งข้อความ
- **Require LINE Registration** - บังคับลงทะเบียนผ่าน LINE

### 5. **Quick Actions**
- Test Connection - ทดสอบการเชื่อมต่อ
- View Logs - ดูประวัติการใช้งาน
- Check Stats - เช็คสถิติ
- Full Settings - ไปยังหน้าตั้งค่าเต็ม

### 6. **System Info**
- Channel ID
- Webhook Status
- Last Updated

---

## 🚀 วิธีใช้งาน

### เปิด Quick Settings Panel

**วิธีที่ 1: คลิกปุ่ม**
1. มองหาปุ่มลอยมุมล่างขวา (มีไอคอน LINE)
2. คลิกที่ปุ่ม
3. Panel จะเลื่อนเข้ามาจากด้านขวา

**วิธีที่ 2: Keyboard Shortcut**
- กด `Ctrl + Shift + L` (Windows/Linux)
- กด `Cmd + Shift + L` (Mac)

### เปิด/ปิดระบบ LINE OA

1. เปิด Quick Settings Panel
2. มองหา **Master Switch** ด้านบนสุด
3. คลิก toggle switch
4. ระบบจะอัพเดททันที
5. แจ้งเตือน "🟢 เปิดใช้งานระบบ LINE OA" หรือ "⚫ ปิดใช้งานระบบ LINE OA"

### เปิด/ปิด Features

**เปิดใช้งาน LINE Messaging:**
1. เปิด Quick Settings Panel
2. มองหา "LINE Messaging" ในส่วน Features
3. คลิก toggle switch
4. ระบบจะอัพเดททันที

**บังคับลงทะเบียนผ่าน LINE:**
1. เปิด Quick Settings Panel
2. มองหา "Require LINE Registration"
3. คลิก toggle switch
4. ระบบจะอัพเดททันที

> **⚠️ หมายเหตุ:** Features จะทำงานได้เฉพาะเมื่อ Master Switch เปิดอยู่เท่านั้น

### ทดสอบการเชื่อมต่อ

1. เปิด Quick Settings Panel
2. มองหา "Test Connection" ในส่วน Quick Actions
3. คลิกปุ่ม
4. รอสักครู่
5. แจ้งเตือน "เชื่อมต่อสำเร็จ!" หรือ "การเชื่อมต่อล้มเหลว"

### ดู Logs

1. เปิด Quick Settings Panel
2. คลิก "View Logs" ในส่วน Quick Actions
3. จะเปิดหน้า Logs ใหม่

---

## 🎨 UI Design

### Color Scheme

**Status Colors:**
- 🟢 Green (`#10B981`) - Active/Success
- ⚫ Gray (`#6B7280`) - Inactive
- 🔵 Blue (`#3B82F6`) - LINE Messaging
- 🟣 Purple (`#A855F7`) - Registration
- 🔴 Red (`#EF4444`) - Error

**Components:**
- Glass Fusion - `backdrop-blur-xl` + `bg-white/10`
- Gradients - `bg-gradient-to-br`
- Shadows - `shadow-2xl`
- Borders - `border border-white/20`

### Animations

**Panel Transitions:**
```
Enter: translate-x-full → translate-x-0 (300ms)
Leave: translate-x-0 → translate-x-full (200ms)
```

**Toggle Switches:**
```
Transform: translateX(0) → translateX(full) (300ms)
Background: gray-600 → gradient (instant)
```

**Button Hover:**
```
Scale: 1 → 1.1
Rotate: 0deg → 12deg
Shadow: normal → xl
```

---

## 🔧 Technical Details

### Files Created/Modified

**1. Component:**
```
resources/views/components/line/quick-settings-panel.blade.php
```
- Alpine.js component
- Self-contained (no external dependencies)
- ~500 lines

**2. Controller:**
```
app/Http/Controllers/Admin/LineOaController.php
```
- Added `quickUpdate()` method
- Added `generateQuickUpdateMessage()` helper
- ~100 lines added

**3. Route:**
```
routes/admin.php
```
- Added `PATCH /admin/line-oa/quick-update`

**4. Layout:**
```
resources/views/layouts/admin-v3.blade.php
```
- Added `<x-line.quick-settings-panel />` component

### API Endpoint

**Quick Update API:**
```
PATCH /admin/line-oa/quick-update
Content-Type: application/json

Request Body:
{
  "is_active": true,
  "enable_line_messaging": true,
  "require_line_registration": false
}

Response (Success):
{
  "success": true,
  "message": "🟢 เปิดใช้งานระบบ LINE OA | 💬 เปิดใช้งาน LINE Messaging",
  "settings": {
    "is_active": true,
    "enable_line_messaging": true,
    "require_line_registration": false
  }
}

Response (Error):
{
  "success": false,
  "message": "ไม่สามารถอัพเดทการตั้งค่าได้: [error message]"
}
```

### Alpine.js Component API

**State:**
```javascript
{
  isOpen: false,           // Panel open/close state
  loading: false,          // Loading state
  systemStatus: {          // Current settings
    is_active: boolean,
    enable_line_messaging: boolean,
    require_line_registration: boolean
  },
  systemInfo: {            // System information
    channel_id: string,
    webhook_verified: boolean,
    updated_at: string
  }
}
```

**Methods:**
```javascript
init()                         // Initialize component
togglePanel()                  // Open/close panel
loadSystemInfo()              // Load system info (async)
updateSetting(key, value)     // Update setting (async)
testConnection()              // Test LINE connection (async)
showNotification(type, msg)   // Show toast notification
formatDate(dateString)        // Format date to relative time
setupKeyboardShortcuts()      // Setup Ctrl+Shift+L
```

### Security Features

**CSRF Protection:**
- All PATCH requests include `X-CSRF-TOKEN` header
- Token retrieved from `<meta name="csrf-token">`

**Validation:**
- Server-side validation using Laravel Request Validation
- Only boolean fields allowed
- `sometimes` rule for partial updates

**Error Handling:**
- Try-catch blocks in API calls
- Automatic state reversion on error
- Error logging to `storage/logs/laravel.log`
- User-friendly error messages

---

## 🧪 Testing

### Manual Testing Checklist

**UI Testing:**
- [ ] ปุ่มลอยแสดงถูกต้อง (มุมล่างขวา)
- [ ] คลิกปุ่มเปิด panel ได้
- [ ] Panel เลื่อนเข้ามาจากขวา (smooth animation)
- [ ] คลิกนอก panel ปิดได้
- [ ] กด ESC ปิดได้
- [ ] กด Ctrl+Shift+L เปิด/ปิดได้
- [ ] Responsive บนมือถือ/แท็บเล็ต
- [ ] Dark mode ทำงานถูกต้อง

**Functionality Testing:**
- [ ] Master Switch เปิด/ปิดได้
- [ ] LINE Messaging toggle ทำงาน
- [ ] Require Registration toggle ทำงาน
- [ ] Features ถูก disable เมื่อ Master Switch ปิด
- [ ] Test Connection ทำงาน
- [ ] Quick Actions ทุกปุ่มคลิกได้
- [ ] System Info แสดงข้อมูลถูกต้อง

**API Testing:**
```bash
# Test quick-update API
curl -X PATCH http://localhost:8000/admin/line-oa/quick-update \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"is_active": true}'

# Expected response
{
  "success": true,
  "message": "🟢 เปิดใช้งานระบบ LINE OA",
  "settings": {
    "is_active": true,
    "enable_line_messaging": false,
    "require_line_registration": false
  }
}
```

---

## 🐛 Troubleshooting

### ปัญหา: ปุ่มลอยไม่แสดง

**สาเหตุที่เป็นไปได้:**
1. Component ยังไม่ถูกเพิ่มใน layout
2. CSS/JS ยังไม่โหลด
3. Z-index ต่ำเกินไป

**วิธีแก้:**
```bash
# 1. ตรวจสอบว่า component อยู่ใน layout
grep "quick-settings-panel" resources/views/layouts/admin-v3.blade.php

# 2. Clear cache และ rebuild assets
php artisan view:clear
npm run build

# 3. ตรวจสอบ browser console
# F12 → Console → ดู errors
```

### ปัญหา: API Error 500

**สาเหตุที่เป็นไปได้:**
1. Route ยังไม่ถูกเพิ่ม
2. Controller method ไม่มี
3. Database error

**วิธีแก้:**
```bash
# 1. ตรวจสอบ route
php artisan route:list | grep "quick-update"

# 2. ดู error logs
tail -f storage/logs/laravel.log

# 3. ทดสอบ database connection
php artisan tinker
>>> LineOaSetting::first();
```

### ปัญหา: Toggle ไม่อัพเดท

**สาเหตุที่เป็นไปได้:**
1. CSRF token หมดอายุ
2. ไม่มีสิทธิ์ admin
3. API endpoint ผิด

**วิธีแก้:**
```bash
# 1. Refresh หน้าเพื่อรับ CSRF token ใหม่
# F5

# 2. ตรวจสอบสิทธิ์ผู้ใช้
php artisan tinker
>>> auth()->check();
>>> auth()->user()->is_admin;

# 3. ตรวจสอบ API endpoint
# F12 → Network → ดู request URL
```

---

## 🔐 Security Considerations

### Permission Requirements

**ต้องมีสิทธิ์:**
- ✅ ต้อง login as admin
- ✅ ต้องผ่าน middleware `auth` และ `role:admin`

**ห้าม:**
- ❌ ผู้ใช้ทั่วไปเข้าถึงไม่ได้
- ❌ Guest เข้าถึงไม่ได้
- ❌ Public API ไม่มี

### Data Sanitization

**Input Validation:**
```php
$validated = $request->validate([
    'is_active' => ['sometimes', 'boolean'],
    'enable_line_messaging' => ['sometimes', 'boolean'],
    'require_line_registration' => ['sometimes', 'boolean'],
]);
```

**No SQL Injection:**
- ใช้ Eloquent ORM
- Parameter binding อัตโนมัติ
- No raw queries

**XSS Protection:**
- Blade `{{ }}` auto-escape
- No `{!! !!}` raw output
- CSP headers (ถ้ามี)

---

## 📈 Performance

### Optimizations

**Cache:**
- Settings cached for 1 hour
- `LineOaSetting::getActive()` uses cache
- Auto-clear on update

**API Calls:**
- Single endpoint for all updates
- Partial updates (PATCH)
- No redundant queries

**Frontend:**
- Lazy load system info
- Debounced API calls (300ms)
- Optimistic UI updates

### Benchmarks

**Expected Performance:**
```
Panel Open: < 100ms
Toggle Update: 200-500ms
Connection Test: 500-2000ms (depends on LINE API)
```

---

## 🎓 Best Practices

### การใช้งาน

**DO:**
- ✅ ใช้ Quick Settings สำหรับการเปลี่ยนแปลงเร็วๆ
- ✅ ใช้ Full Settings สำหรับการตั้งค่าละเอียด
- ✅ ทดสอบ connection หลังจากเปลี่ยน credentials
- ✅ ตรวจสอบ logs เป็นประจำ

**DON'T:**
- ❌ เปิด/ปิดระบบบ่อยเกินไปในเวลาสั้นๆ
- ❌ เปลี่ยนการตั้งค่าตอนมี users ใช้งาน production
- ❌ ใช้ panel แทน Full Settings สำหรับการตั้งค่าครั้งแรก

### การพัฒนา

**Code Style:**
- ใช้ภาษาไทยในคอมเมนต์ 100%
- Follow V3 Coding Guidelines
- Tailwind CSS utilities only
- Alpine.js for interactivity

**Git Commits:**
```
feat: add LINE Quick Settings Panel
fix: update toggle switch animation
docs: add Quick Settings Panel guide
```

---

## 🔮 Future Enhancements

### Planned Features

**Phase 2:**
- [ ] แสดง active users count
- [ ] Recent activities timeline
- [ ] Quick broadcast message
- [ ] Template management shortcuts

**Phase 3:**
- [ ] Drag & drop reordering
- [ ] Keyboard navigation (Tab, Arrow keys)
- [ ] Accessibility (ARIA labels)
- [ ] Multi-language support

**Advanced:**
- [ ] Custom themes per admin user
- [ ] Saved presets (Development, Production)
- [ ] Scheduled enable/disable
- [ ] Webhooks for setting changes

---

## 📞 Support

### ถ้ามีปัญหา

**1. ตรวจสอบเอกสาร:**
- อ่านคู่มือนี้ทั้งหมด
- ดู Troubleshooting section

**2. ตรวจสอบ Logs:**
```bash
tail -f storage/logs/laravel.log
```

**3. ดู Browser Console:**
- F12 → Console
- ดู error messages

**4. ติดต่อ Developer:**
- Claude AI Assistant
- GitHub Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues

---

## 📄 License

Proprietary - Thaiprompt Affiliate System

---

## 🙏 Credits

**Developed by:** Claude AI Assistant
**Date:** 2025-11-23
**Version:** 1.0.0
**Framework:** Laravel 11 + Vite + V3 Design System

---

**Made with ❤️ for Thaiprompt-Affiliate**

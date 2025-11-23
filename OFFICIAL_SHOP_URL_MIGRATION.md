# 🔄 Official Shop URL Migration Guide

## การเปลี่ยนแปลง URL

เพื่อให้ระบบร้านของระบบมี URL เฉพาะและ UI ที่โดดเด่น เราได้สร้าง official shop แยกออกมา

### ❌ URL เก่า (Deprecated)
```
/shop?shop_type=official
/shop?shop_type=official&sort_by=newest
/shop?shop_type=official&category=electronics
/shop?shop_type=official&search=iphone
```

### ✅ URL ใหม่ (Recommended)
```
/official-shop
/official-shop?sort_by=newest
/official-shop?category=electronics
/official-shop?search=iphone
```

---

## 🔁 Automatic Redirect

ระบบจะ **redirect อัตโนมัติ** จาก URL เก่าไปยัง URL ใหม่

### ตัวอย่าง:
| URL เก่า | → | URL ใหม่ |
|----------|---|----------|
| `/shop?shop_type=official` | → | `/official-shop` |
| `/shop?shop_type=official&sort_by=newest` | → | `/official-shop?sort_by=newest` |
| `/shop?shop_type=official&category=electronics&search=iphone` | → | `/official-shop?category=electronics&search=iphone` |

### การทำงาน:
1. ✅ รักษา query parameters ทั้งหมด (search, category, sort_by, etc.)
2. ✅ ลบ `shop_type=official` ออกอัตโนมัติ
3. ✅ HTTP 302 redirect (temporary redirect)
4. ✅ รองรับ backward compatibility 100%

---

## 💻 Implementation

### ShopController.php
```php
public function index(Request $request)
{
    // ✨ REDIRECT: ถ้าเป็น shop_type=official ให้ redirect ไปยังร้านของระบบใหม่
    if ($request->filled('shop_type') && $request->shop_type === 'official') {
        // สร้าง query parameters ใหม่ (ยกเว้น shop_type)
        $params = $request->except('shop_type');

        // Redirect ไปยัง official shop พร้อม query parameters
        return redirect()->route('official-shop.index', $params);
    }

    // ... rest of code
}
```

---

## 🔗 Routes

### Official Shop Routes (ใหม่)
```php
Route::prefix('official-shop')->name('official-shop.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [OfficialShopController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/featured', [OfficialShopController::class, 'featured'])->name('featured');
    Route::match(['GET', 'HEAD'], '/category/{slug}', [OfficialShopController::class, 'category'])->name('category');
    Route::match(['GET', 'HEAD'], '/search', [OfficialShopController::class, 'quickSearch'])->name('search');
    Route::match(['GET', 'HEAD'], '/{slug}', [OfficialShopController::class, 'show'])->name('show');
});
```

### Legacy Routes (Auto-redirect)
```php
// /shop?shop_type=official → /official-shop
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');

// /admin-store → /official-shop (redirect)
Route::prefix('admin-store')->redirect('/official-shop');
```

---

## 🎯 Update Guide

### สำหรับ Frontend Links
```blade
{{-- ❌ เก่า (ยังใช้ได้ แต่จะ redirect) --}}
<a href="{{ route('shop.index', ['shop_type' => 'official']) }}">ร้านของระบบ</a>

{{-- ✅ ใหม่ (แนะนำ) --}}
<a href="{{ route('official-shop.index') }}">ร้านของระบบ</a>
```

### สำหรับ JavaScript
```javascript
// ❌ เก่า
window.location.href = '/shop?shop_type=official';

// ✅ ใหม่
window.location.href = '/official-shop';
```

### สำหรับ Menu Items
```php
// ❌ เก่า
[
    'label' => 'ร้านของระบบ',
    'url' => route('shop.index', ['shop_type' => 'official']),
]

// ✅ ใหม่
[
    'label' => 'ร้านของระบบ',
    'url' => route('official-shop.index'),
    'icon' => 'fa-shield-check', // ไอคอนพิเศษ
]
```

---

## 🎨 UI Differences

| Feature | Shop ทั่วไป | Official Shop |
|---------|-------------|---------------|
| **URL** | `/shop` | `/official-shop` |
| **สี** | Blue-Cyan | Purple-Pink-Orange |
| **Badge** | ไม่มี | 🛡️ "ร้านทางการ" |
| **Commission** | 5-20% | 25-40% |
| **3D Effects** | - | ✅ Full 3D + Glow |
| **Glassmorphism** | - | ✅ Backdrop blur |

---

## 📊 SEO Impact

### Canonical URLs
```html
<!-- หน้า Official Shop -->
<link rel="canonical" href="https://yourdomain.com/official-shop">

<!-- 301 Redirect (ถ้าต้องการ permanent) -->
<!-- ปัจจุบันใช้ 302 redirect เพื่อ flexibility -->
```

### Sitemap
```xml
<url>
    <loc>https://yourdomain.com/official-shop</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
</url>
```

---

## 🧪 Testing

### Test Cases
```bash
# 1. Basic redirect
curl -I "https://yourdomain.com/shop?shop_type=official"
# Expected: 302 Redirect to /official-shop

# 2. With query parameters
curl -I "https://yourdomain.com/shop?shop_type=official&sort_by=newest"
# Expected: 302 Redirect to /official-shop?sort_by=newest

# 3. With search
curl -I "https://yourdomain.com/shop?shop_type=official&search=iphone&category=electronics"
# Expected: 302 Redirect to /official-shop?search=iphone&category=electronics

# 4. Direct access (should work)
curl -I "https://yourdomain.com/official-shop"
# Expected: 200 OK
```

---

## ⚠️ Important Notes

1. **Backward Compatibility:**
   - ลิงก์เก่ายังใช้งานได้ (redirect อัตโนมัติ)
   - ไม่มี breaking changes
   - API endpoints ไม่เปลี่ยนแปลง

2. **Performance:**
   - Redirect ใช้ 302 (temporary) แทน 301 (permanent)
   - เพื่อความยืดหยุ่นในอนาคต

3. **Analytics:**
   - Track ทั้ง `/shop?shop_type=official` และ `/official-shop`
   - Redirect จะนับเป็น page view 2 ครั้ง (redirect + final page)

4. **Cache:**
   - Clear browser cache หลัง deploy
   - CDN cache ต้อง purge routes ที่เปลี่ยน

---

## 📝 Changelog

### Version 3.145.2 (2025-11-23)
- ✅ เพิ่ม `/official-shop` routes
- ✅ Auto-redirect จาก `/shop?shop_type=official`
- ✅ UI แบบ 3D premium สำหรับร้านของระบบ
- ✅ Commission สูงสุด 40%
- ✅ Backward compatibility 100%

---

**สรุป:** ลิงก์เก่ายังใช้งานได้ปกติ แต่แนะนำให้อัปเดตเป็นลิงก์ใหม่เพื่อ UX ที่ดีกว่า! ✨

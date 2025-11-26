# Avatar Consistency Guidelines

> **เอกสารแนวทางการแสดงรูป Avatar ที่สอดคล้องกันทั้งระบบ**
>
> **Version: 1.0.0** | Last Updated: 2025-11-26

---

## 📋 สารบัญ

1. [หลักการพื้นฐาน](#หลักการพื้นฐาน)
2. [Components ที่ต้องใช้](#components-ที่ต้องใช้)
3. [ฐานข้อมูล Avatar](#ฐานข้อมูล-avatar)
4. [การใช้งานที่ถูกต้อง](#การใช้งานที่ถูกต้อง)
5. [ข้อห้าม](#ข้อห้าม)
6. [กรณีพิเศษ](#กรณีพิเศษ)
7. [Checklist](#checklist)

---

## หลักการพื้นฐาน

### 🎯 เป้าหมาย

1. **ความสอดคล้อง** - Avatar ต้องแสดงเหมือนกันทุกที่ในระบบ
2. **Fallback ที่ถูกต้อง** - มี fallback เมื่อรูปไม่พบหรือโหลดไม่ได้
3. **ใช้รูปที่อัพโหลดเสมอ** - หากผู้ใช้อัพโหลดรูปแล้ว ต้องแสดงรูปนั้นทุกที่

### 🔢 ลำดับความสำคัญของ Avatar

```
1. LINE profile picture (line_picture_url) - หากเชื่อมต่อ LINE
2. รูปที่อัพโหลด (profile_picture) - หากอัพโหลดแล้ว
3. Default (UI Avatars API) - หากไม่มีรูป
```

---

## Components ที่ต้องใช้

### 1. `<x-user-avatar>` - Avatar ผู้ใช้ทั่วไป

**ใช้เมื่อ:** แสดง avatar ผู้ใช้ทั่วไปที่ไม่ต้องการ rank frame

**ตำแหน่งไฟล์:** `resources/views/components/user-avatar.blade.php`

**Props:**
| Prop | Type | Default | คำอธิบาย |
|------|------|---------|---------|
| `user` | User\|null | Auth::user() | User model |
| `size` | string | 'md' | xs, sm, md, lg, xl, 2xl, 3xl |
| `ring` | bool | true | แสดง ring border |
| `ringColor` | string | 'white' | white, purple, blue, green, gradient, none |
| `showStatus` | bool | false | แสดง online status |
| `showBadge` | bool | false | แสดง badge |
| `badge` | string | '' | ข้อความ badge |
| `clickable` | bool | false | คลิกได้ |

**ตัวอย่าง:**

```blade
{{-- แสดง avatar ผู้ใช้ปัจจุบัน --}}
<x-user-avatar />

{{-- แสดง avatar ผู้ใช้ที่ระบุ --}}
<x-user-avatar :user="$user" size="lg" />

{{-- ไม่แสดง ring --}}
<x-user-avatar :user="$user" size="md" :ring="false" />

{{-- ring สีม่วง --}}
<x-user-avatar :user="$user" ringColor="purple" />

{{-- พร้อม status online --}}
<x-user-avatar :user="$user" :showStatus="true" />
```

### 2. `<x-rank-avatar>` - Avatar พร้อม Rank Frame

**ใช้เมื่อ:** แสดง avatar พร้อมกรอบตาม rank ของผู้ใช้

**ตำแหน่งไฟล์:** `resources/views/components/rank-avatar.blade.php`

**Props:**
| Prop | Type | Default | คำอธิบาย |
|------|------|---------|---------|
| `user` | User\|null | Auth::user() | User model |
| `rankLevel` | int\|null | จาก user | Rank level (1-8) |
| `rank` | Rank\|null | จาก user | Rank model |
| `size` | string | 'md' | xs, sm, md, lg, xl, 2xl |
| `src` | string\|null | จาก user | URL รูป custom |
| `showBadge` | bool | true | แสดง rank badge |
| `animate` | bool | true | เปิด animation |

**ตัวอย่าง:**

```blade
{{-- แสดง rank avatar ของผู้ใช้ --}}
<x-rank-avatar :user="$user" size="lg" />

{{-- ระบุ rank level ตรงๆ --}}
<x-rank-avatar :rank-level="5" :src="$avatarUrl" size="xl" />

{{-- ไม่แสดง badge --}}
<x-rank-avatar :user="$user" :show-badge="false" />

{{-- ปิด animation --}}
<x-rank-avatar :user="$user" :animate="false" />
```

---

## ฐานข้อมูล Avatar

### ตาราง `users`

| Field | Type | คำอธิบาย |
|-------|------|---------|
| `profile_picture` | varchar(255) null | Path รูปที่อัพโหลด (เช่น `avatars/abc123.webp`) |
| `line_picture_url` | varchar(255) null | URL รูป LINE profile |

### Accessor ที่สำคัญ

**`profile_picture_url`** - ใช้ accessor นี้เสมอ!

```php
// ✅ ถูกต้อง - ใช้ accessor
$user->profile_picture_url

// ❌ ผิด - ใช้ field ตรงๆ
$user->profile_picture
```

**หมายเหตุ:** Accessor `profile_picture_url` จัดการ fallback ให้อัตโนมัติ

---

## การใช้งานที่ถูกต้อง

### ✅ กรณีที่ 1: แสดง Avatar ธรรมดา (ใช้ Component)

```blade
{{-- ✅ ถูกต้อง: ใช้ component --}}
<x-user-avatar :user="$user" size="md" />
```

### ✅ กรณีที่ 2: แสดง Avatar พร้อม Rank (ใช้ Component)

```blade
{{-- ✅ ถูกต้อง: ใช้ rank-avatar component --}}
<x-rank-avatar :user="$user" :rank-level="$rankLevel" size="lg" />
```

### ✅ กรณีที่ 3: Avatar ใน List/Table

```blade
{{-- ✅ ถูกต้อง: ใช้ component ใน loop --}}
@foreach($users as $user)
    <tr>
        <td>
            <x-user-avatar :user="$user" size="md" :ring="false" />
        </td>
        <td>{{ $user->name }}</td>
    </tr>
@endforeach
```

### ✅ กรณีที่ 4: หน้า Profile Edit (มี Alpine.js preview)

```blade
{{-- ✅ ถูกต้อง: ใช้ img tag พร้อม Alpine + onerror fallback --}}
<img :src="avatarPreview || '{{ $user->profile_picture_url }}'"
     alt="{{ $user->name }}"
     class="w-full h-full object-cover rounded-full"
     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
```

### ✅ กรณีที่ 5: หน้าที่ต้องการ Custom Styling มาก

```blade
{{-- ✅ ถูกต้อง: ใช้ accessor + onerror fallback --}}
<img src="{{ $user->profile_picture_url }}"
     alt="{{ $user->name }}"
     class="custom-avatar-class"
     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
```

---

## ข้อห้าม

### ❌ ห้ามใช้ `avatar_url` กับ User model

```blade
{{-- ❌ ผิด: User model ไม่มี avatar_url --}}
<img src="{{ $user->avatar_url }}">

{{-- ✅ ถูกต้อง: ใช้ profile_picture_url --}}
<img src="{{ $user->profile_picture_url }}">
```

### ❌ ห้ามใช้ field ตรงๆ โดยไม่มี fallback

```blade
{{-- ❌ ผิด: ไม่มี fallback --}}
<img src="{{ $user->profile_picture }}">

{{-- ✅ ถูกต้อง: ใช้ accessor (มี fallback ในตัว) --}}
<img src="{{ $user->profile_picture_url }}">
```

### ❌ ห้ามใช้ img tag โดยไม่มี onerror

```blade
{{-- ❌ ผิด: ไม่มี onerror fallback --}}
<img src="{{ $user->profile_picture_url }}" alt="{{ $user->name }}">

{{-- ✅ ถูกต้อง: มี onerror fallback --}}
<img src="{{ $user->profile_picture_url }}"
     alt="{{ $user->name }}"
     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">

{{-- ✅ หรือใช้ component (ดีกว่า) --}}
<x-user-avatar :user="$user" size="md" />
```

---

## กรณีพิเศษ

### 1. AI Bot Avatar

AI Bot ใช้ field `avatar_url` ตรงๆ (แตกต่างจาก User)

```blade
{{-- AI Bot ใช้ avatar_url ได้ถูกต้อง --}}
<img src="{{ $bot->avatar_url }}"
     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Bot&background=10b981&color=fff';">
```

### 2. Provider Avatar

Provider ใช้ field `logo_url`

```blade
{{-- Provider ใช้ logo_url --}}
<img src="{{ $provider->logo_url ?? 'https://ui-avatars.com/api/?name=P' }}">
```

### 3. ID Card / Virtual Card

ใช้ img tag โดยตรงได้เนื่องจากต้องการ styling พิเศษ แต่ต้องมี onerror

```blade
<div class="w-20 h-20 rounded-xl overflow-hidden ring-4 ring-yellow-400">
    <img src="{{ $user->profile_picture_url }}"
         class="w-full h-full object-cover"
         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
</div>
```

---

## Checklist

### เมื่อสร้าง View ใหม่ที่มี Avatar

- [ ] ใช้ `<x-user-avatar>` หรือ `<x-rank-avatar>` component เป็นอันดับแรก
- [ ] ถ้าจำเป็นต้องใช้ img tag ตรงๆ:
  - [ ] ใช้ `$user->profile_picture_url` accessor (ไม่ใช่ `profile_picture` หรือ `avatar_url`)
  - [ ] มี `onerror` fallback handler
  - [ ] ใส่ `alt` attribute
- [ ] ทดสอบกับผู้ใช้ที่:
  - [ ] มีรูป profile ที่อัพโหลด
  - [ ] เชื่อมต่อ LINE (มี line_picture_url)
  - [ ] ไม่มีรูปเลย (ใช้ default)

### เมื่อ Review Code

- [ ] ตรวจสอบว่าไม่ใช้ `avatar_url` กับ User model
- [ ] ตรวจสอบว่าใช้ accessor `profile_picture_url` ไม่ใช่ field `profile_picture`
- [ ] ตรวจสอบว่ามี fallback handler (onerror หรือ ใช้ component)

---

## Quick Reference

### Size Classes

| Size | Tailwind | Pixels |
|------|----------|--------|
| xs | w-6 h-6 | 24px |
| sm | w-8 h-8 | 32px |
| md | w-10 h-10 | 40px |
| lg | w-14 h-14 | 56px |
| xl | w-20 h-20 | 80px |
| 2xl | w-32 h-32 | 128px |
| 3xl | w-40 h-40 | 160px |

### Fallback URL Template

```
https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200
```

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-26
**Maintained By:** Development Team

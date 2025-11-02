# คำแนะนำสำหรับ Claude - Thaiprompt Affiliate System

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

## การตรวจสอบก่อน Commit

ก่อน commit code ทุกครั้ง ต้องตรวจสอบ:

### Design & UI
- [ ] รองรับทั้ง dark mode และ light mode (บังคับ)
- [ ] UI สวยงามและเป็นมืออาชีพ (บังคับ)
- [ ] Responsive บนทุก device: mobile, tablet, desktop (บังคับ)
- [ ] ใส่ไอคอนที่เหมาะสม ไม่รกเกินไป
- [ ] Animations smooth (60fps)
- [ ] Loading states แสดงอย่างเหมาะสม

### Code Quality
- [ ] Code clean และ maintainable
- [ ] มีคอมเม้นต์ภาษาไทยอธิบายการทำงาน (บังคับ)
- [ ] มี JSDoc/PHPDoc พร้อม @example (บังคับ)
- [ ] มี Tips การใช้งานในคอมเม้นต์
- [ ] ไม่มี duplicated code
- [ ] Type hints และ validation ครบถ้วน
- [ ] Error handling ถูกต้อง

### Testing
- [ ] ผ่าน linting และ formatting standards
- [ ] ทดสอบบน iPhone (375px)
- [ ] ทดสอบบน Android (360px)
- [ ] ทดสอบบน iPad (768px)
- [ ] ทดสอบบน Desktop (1920px)
- [ ] ทดสอบ dark และ light mode
- [ ] ไม่มี console errors หรือ warnings
- [ ] Performance ดี (Lighthouse score > 90)

### Accessibility & Security
- [ ] Accessibility compliance (WCAG AA)
- [ ] Keyboard navigation ทำงาน
- [ ] Input validation ครบถ้วน
- [ ] XSS และ CSRF protection

## สรุป

### หลักการทอง 6 ข้อ (บังคับเสมอ):

1. **🌓 Dark/Light Mode เสมอ**
   - ทุก UI ต้องรองรับทั้งสองโหมด
   - ใช้ CSS variables และ Tailwind dark utilities
   - ทดสอบ contrast และ readability

2. **💎 สวยงามหลักล้านเสมอ**
   - Professional-grade UI/UX
   - Spacing, typography, colors ต้องลงตัว
   - ใส่ไอคอนสวยงามแต่ไม่รก

3. **📱 Responsive เสมอ**
   - Mobile-first approach
   - ทดสอบทุก device (mobile, tablet, desktop)
   - Touch-friendly บน mobile

4. **💬 คอมเม้นต์ภาษาไทยเสมอ**
   - อธิบายการทำงานเป็นภาษาไทย
   - มี JSDoc/PHPDoc พร้อม @example
   - ใส่ Tips การใช้งาน

5. **📚 คู่มือการใช้งานเสมอ**
   - ระบุ props, events, parameters
   - ให้ตัวอย่างการใช้งาน
   - เพิ่ม tips และ best practices

6. **🔧 โค้ดมืออาชีพเสมอ**
   - Clean, maintainable, performant
   - Follow best practices
   - Proper testing

### ห้ามทำ (ห้ามเด็ดขาด):

- ❌ Hard-code colors (ไม่รองรับ dark mode)
- ❌ UI ไม่สวยหรือไม่เป็นมืออาชีพ
- ❌ ไม่ responsive (fixed width)
- ❌ ไม่มีคอมเม้นต์ภาษาไทย
- ❌ ไม่มีคู่มือการใช้งาน
- ❌ Code messy หรือ duplicated
- ❌ ละเลย accessibility
- ❌ ไอคอนรกเกินไป หรือไม่มีไอคอนเลย

---

*"Excellence is not an act, but a habit" - ทำให้ทุกโค้ดเป็นผลงานที่ภาคภูมิใจ*

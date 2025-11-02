# 🎨 Design Guidelines - Thaiprompt Affiliate System

> **หลักการสำคัญ**: ทุกโค้ดและ UI ที่พัฒนาต้องรองรับโหมดมืด-สว่าง, สวยงามหลักล้าน, มืออาชีพเสมอ, มีคอมเม้นต์ภาษาไทย, คู่มือการใช้งาน, Responsive เสมอ, และมีไอคอนสวยงามแต่ไม่รก

## 📑 สารบัญ

1. [Dark/Light Mode Support](#darklight-mode-support)
2. [Professional Design Standards](#professional-design-standards)
3. [Documentation & Comments](#documentation--comments) ⭐ ใหม่
4. [Icons & Visual Elements](#icons--visual-elements) ⭐ ใหม่
5. [Responsive Design Guidelines](#responsive-design-guidelines) ⭐ อัปเดต
6. [Code Quality Guidelines](#code-quality-guidelines)
7. [Component Development](#component-development)
8. [Performance Standards](#performance-standards)
9. [Accessibility Requirements](#accessibility-requirements)
10. [Testing Checklist](#testing-checklist)

---

## 🌓 Dark/Light Mode Support

### การรองรับโหมดมืด-สว่างเป็นข้อกำหนดบังคับ

**ทุกคอมโพเนนต์ UI ต้องรองรับทั้ง Dark Mode และ Light Mode โดยอัตโนมัติ**

### 1. CSS Variables และ Theme System

#### ใช้ CSS Variables แทน Hard-coded Colors

❌ **ห้ามทำ:**
```css
.button {
  background-color: #3b82f6;
  color: white;
  border: 1px solid #1e40af;
}
```

✅ **ควรทำ:**
```css
.button {
  background-color: var(--color-primary);
  color: var(--color-text-inverse);
  border: 1px solid var(--color-primary-dark);
}
```

#### Color Variables ที่ต้องใช้

```css
:root {
  /* Background Colors */
  --bg-primary: #ffffff;
  --bg-secondary: #f3f4f6;
  --bg-tertiary: #e5e7eb;
  --bg-inverse: #1f2937;

  /* Text Colors */
  --text-primary: #111827;
  --text-secondary: #6b7280;
  --text-tertiary: #9ca3af;
  --text-inverse: #ffffff;

  /* Border Colors */
  --border-primary: #d1d5db;
  --border-secondary: #e5e7eb;
  --border-focus: #3b82f6;

  /* Brand Colors */
  --color-primary: #3b82f6;
  --color-primary-dark: #2563eb;
  --color-primary-light: #60a5fa;

  /* Semantic Colors */
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-error: #ef4444;
  --color-info: #3b82f6;
}

[data-theme="dark"] {
  /* Background Colors */
  --bg-primary: #1f2937;
  --bg-secondary: #111827;
  --bg-tertiary: #374151;
  --bg-inverse: #ffffff;

  /* Text Colors */
  --text-primary: #f9fafb;
  --text-secondary: #d1d5db;
  --text-tertiary: #9ca3af;
  --text-inverse: #111827;

  /* Border Colors */
  --border-primary: #4b5563;
  --border-secondary: #374151;
  --border-focus: #60a5fa;
}
```

### 2. Tailwind CSS Dark Mode

#### ใช้ Dark Mode Utilities

```html
<!-- Background และ Text -->
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
  Content
</div>

<!-- Borders -->
<div class="border border-gray-300 dark:border-gray-600">
  Content
</div>

<!-- Hover States -->
<button class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700">
  Click Me
</button>

<!-- Shadows -->
<div class="shadow-lg dark:shadow-gray-800/50">
  Card Content
</div>
```

### 3. Vue.js Theme Implementation

#### Theme Composable

```javascript
// composables/useTheme.js
import { ref, computed, watch } from 'vue'

const theme = ref(localStorage.getItem('theme') || 'light')

export function useTheme() {
  const isDark = computed(() => theme.value === 'dark')

  const toggleTheme = () => {
    theme.value = isDark.value ? 'light' : 'dark'
    localStorage.setItem('theme', theme.value)
    document.documentElement.setAttribute('data-theme', theme.value)
  }

  const setTheme = (newTheme) => {
    theme.value = newTheme
    localStorage.setItem('theme', newTheme)
    document.documentElement.setAttribute('data-theme', newTheme)
  }

  // Apply theme on mount
  document.documentElement.setAttribute('data-theme', theme.value)

  return {
    theme,
    isDark,
    toggleTheme,
    setTheme
  }
}
```

#### Component Usage

```vue
<template>
  <div :class="themeClasses">
    <button @click="toggleTheme" class="theme-toggle">
      <span v-if="isDark">☀️ Light</span>
      <span v-else>🌙 Dark</span>
    </button>
  </div>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme } = useTheme()

const themeClasses = computed(() => ({
  'dark': isDark.value,
  'light': !isDark.value
}))
</script>
```

### 4. Testing Dark/Light Modes

#### Checklist สำหรับการทดสอบ:

- [ ] ทดสอบ contrast ratio ≥ 4.5:1 (WCAG AA)
- [ ] ตรวจสอบ readability ในทั้งสองโหมด
- [ ] ทดสอบ hover, focus, active states
- [ ] ตรวจสอบ shadows และ borders
- [ ] ทดสอบ images และ icons
- [ ] ตรวจสอบ transitions ระหว่าง theme
- [ ] ทดสอบ localStorage persistence

---

## 🎯 Professional Design Standards

### ความสวยงามระดับมืออาชีพเป็นข้อบังคับ

**ทุก UI element ต้องมีคุณภาพระดับ "หลักล้าน" - สวยงาม, ใช้งานง่าย, และเป็นมืออาชีพ**

### 1. Spacing System

ใช้ spacing scale ที่สม่ำเสมอ:

```css
/* Spacing Scale */
--space-xs: 0.25rem;   /* 4px */
--space-sm: 0.5rem;    /* 8px */
--space-md: 1rem;      /* 16px */
--space-lg: 1.5rem;    /* 24px */
--space-xl: 2rem;      /* 32px */
--space-2xl: 3rem;     /* 48px */
--space-3xl: 4rem;     /* 64px */
```

#### Tailwind Spacing

```html
<!-- Padding -->
<div class="p-4">      <!-- 16px -->
<div class="px-6 py-4"> <!-- x: 24px, y: 16px -->

<!-- Margin -->
<div class="mt-8 mb-4"> <!-- top: 32px, bottom: 16px -->

<!-- Gap (Flexbox/Grid) -->
<div class="flex gap-4">
  <div>Item 1</div>
  <div>Item 2</div>
</div>
```

### 2. Typography System

#### Font Hierarchy

```css
/* Headings */
--text-h1: 2.5rem;      /* 40px */
--text-h2: 2rem;        /* 32px */
--text-h3: 1.75rem;     /* 28px */
--text-h4: 1.5rem;      /* 24px */
--text-h5: 1.25rem;     /* 20px */
--text-h6: 1rem;        /* 16px */

/* Body */
--text-base: 1rem;      /* 16px */
--text-sm: 0.875rem;    /* 14px */
--text-xs: 0.75rem;     /* 12px */

/* Line Height */
--leading-tight: 1.25;
--leading-normal: 1.5;
--leading-relaxed: 1.75;
```

#### Font Weights

```css
--font-light: 300;
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

#### การใช้งาน Typography

```html
<h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100">
  หัวข้อหลัก
</h1>

<p class="text-base text-gray-700 dark:text-gray-300 leading-relaxed">
  เนื้อหาบทความ
</p>

<span class="text-sm text-gray-500 dark:text-gray-400">
  รายละเอียดเพิ่มเติม
</span>
```

### 3. Color Palette

#### Brand Colors

```css
/* Primary - Blue */
--primary-50: #eff6ff;
--primary-100: #dbeafe;
--primary-500: #3b82f6;
--primary-600: #2563eb;
--primary-700: #1d4ed8;

/* Secondary - Gray */
--gray-50: #f9fafb;
--gray-100: #f3f4f6;
--gray-500: #6b7280;
--gray-700: #374151;
--gray-900: #111827;
```

#### Semantic Colors

```css
/* Success - Green */
--success-light: #d1fae5;
--success: #10b981;
--success-dark: #059669;

/* Warning - Amber */
--warning-light: #fef3c7;
--warning: #f59e0b;
--warning-dark: #d97706;

/* Error - Red */
--error-light: #fee2e2;
--error: #ef4444;
--error-dark: #dc2626;

/* Info - Blue */
--info-light: #dbeafe;
--info: #3b82f6;
--info-dark: #2563eb;
```

### 4. Border Radius

```css
--radius-sm: 0.25rem;   /* 4px */
--radius-md: 0.375rem;  /* 6px */
--radius-lg: 0.5rem;    /* 8px */
--radius-xl: 0.75rem;   /* 12px */
--radius-2xl: 1rem;     /* 16px */
--radius-full: 9999px;  /* Full rounded */
```

### 5. Shadows

```css
/* Light Mode */
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
--shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
--shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);

/* Dark Mode */
--shadow-dark-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3);
--shadow-dark-md: 0 4px 6px -1px rgb(0 0 0 / 0.4);
--shadow-dark-lg: 0 10px 15px -3px rgb(0 0 0 / 0.5);
```

### 6. Animations และ Transitions

```css
/* Durations */
--duration-fast: 150ms;
--duration-normal: 200ms;
--duration-slow: 300ms;

/* Easing */
--ease-in: cubic-bezier(0.4, 0, 1, 1);
--ease-out: cubic-bezier(0, 0, 0.2, 1);
--ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
```

#### การใช้งาน

```css
.button {
  transition: all var(--duration-normal) var(--ease-in-out);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity var(--duration-normal);
}
```

---

## 💬 Documentation & Comments

### การเขียนคอมเม้นต์และเอกสารเป็นข้อบังคับ

**ทุกโค้ดต้องมีคอมเม้นต์ภาษาไทยอธิบายการทำงาน และคู่มือการใช้งาน (Tips)**

### 1. หลักการเขียนคอมเม้นต์

#### PHP/Laravel Documentation

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
 * $commission = $this->calculateCommission($user, 10000, true);
 * // Returns: 1500.00 (15% + 5% bonus)
 *
 * @tip ใช้ includeBonus=true เฉพาะช่วงโปรโมชั่น
 * @throws InvalidArgumentException เมื่อ salesAmount น้อยกว่า 0
 */
public function calculateCommission(User $user, float $salesAmount, bool $includeBonus = false): float
{
    // ตรวจสอบความถูกต้องของข้อมูล
    if ($salesAmount < 0) {
        throw new InvalidArgumentException('ยอดขายต้องมากกว่าหรือเท่ากับ 0');
    }

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

/**
 * ดึงรายการผู้ใช้ทั้งหมดพร้อมข้อมูลที่เกี่ยวข้อง
 *
 * @param array $filters ตัวกรองข้อมูล ['role' => 'admin', 'status' => 'active']
 * @param int $perPage จำนวนรายการต่อหน้า (default: 15)
 * @return \Illuminate\Pagination\LengthAwarePaginator
 *
 * @example
 * $users = $this->getUsers(['role' => 'admin'], 20);
 *
 * @tip ใช้ eager loading เพื่อป้องกัน N+1 query problem
 */
public function getUsers(array $filters = [], int $perPage = 15)
{
    // เริ่มต้น query พร้อม eager loading ความสัมพันธ์
    $query = User::with(['profile', 'membership']);

    // กรองตาม role ถ้ามี
    if (isset($filters['role'])) {
        $query->where('role', $filters['role']);
    }

    // กรองตาม status ถ้ามี
    if (isset($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    // ส่งกลับข้อมูลแบบ paginate
    return $query->paginate($perPage);
}
```

#### Vue.js/JavaScript Documentation

```vue
<!--
  คอมโพเนนต์การ์ดแสดงข้อมูลสมาชิก

  แสดงข้อมูลสมาชิกในรูปแบบการ์ดที่สวยงาม รองรับ dark/light mode
  พร้อม skeleton loading และ error handling

  Props:
  - user (Object, required): ข้อมูลผู้ใช้
    { id, name, email, avatar, membership_level, stats }
  - showStats (Boolean, default: true): แสดงสถิติหรือไม่
  - clickable (Boolean, default: false): สามารถคลิกได้หรือไม่
  - loading (Boolean, default: false): สถานะกำลังโหลด

  Events:
  - @click: เมื่อคลิกที่การ์ด (ถ้า clickable=true)
  - @refresh: เมื่อต้องการรีเฟรชข้อมูล
  - @edit: เมื่อคลิกปุ่มแก้ไข

  Slots:
  - actions: พื้นที่สำหรับปุ่มกระทำ
  - footer: พื้นที่ส่วนท้ายการ์ด

  Usage:
  <UserCard
    :user="currentUser"
    :show-stats="true"
    :clickable="true"
    @click="viewProfile"
    @refresh="loadUserData"
  >
    <template #actions>
      <button @click="editUser">แก้ไข</button>
    </template>
  </UserCard>

  💡 Tips:
  - ใช้ loading prop เพื่อแสดง skeleton loading
  - ใช้ slot="actions" เพื่อเพิ่มปุ่มกระทำ
  - รองรับ responsive (card เต็มจอบน mobile)
  - รองรับ dark/light mode อัตโนมัติ
-->
<template>
  <div
    :class="cardClasses"
    @click="handleClick"
  >
    <!-- Loading skeleton -->
    <div v-if="loading" class="skeleton-loader">
      <!-- Skeleton content -->
    </div>

    <!-- Card content -->
    <div v-else class="card-content">
      <!-- Header -->
      <div class="card-header">
        <!-- Avatar -->
        <img
          :src="user.avatar"
          :alt="`${user.name} avatar`"
          class="avatar"
        />

        <!-- Name and email -->
        <div class="user-info">
          <h3 class="user-name">{{ user.name }}</h3>
          <p class="user-email">{{ user.email }}</p>
        </div>

        <!-- Actions slot -->
        <div v-if="$slots.actions" class="card-actions">
          <slot name="actions" />
        </div>
      </div>

      <!-- Stats (ถ้าต้องการแสดง) -->
      <div v-if="showStats && user.stats" class="card-stats">
        <div class="stat-item">
          <span class="stat-label">ยอดขาย</span>
          <span class="stat-value">{{ formatCurrency(user.stats.sales) }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">คอมมิชชั่น</span>
          <span class="stat-value">{{ formatCurrency(user.stats.commission) }}</span>
        </div>
      </div>

      <!-- Footer slot -->
      <div v-if="$slots.footer" class="card-footer">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

/**
 * Props - กำหนดค่าที่รับเข้ามา
 */
const props = defineProps({
  user: {
    type: Object,
    required: true,
    validator: (value) => {
      return value.id && value.name && value.email
    }
  },
  showStats: {
    type: Boolean,
    default: true
  },
  clickable: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  }
})

/**
 * Events - กำหนด events ที่ส่งออก
 */
const emit = defineEmits(['click', 'refresh', 'edit'])

/**
 * คำนวณ CSS classes สำหรับการ์ด
 */
const cardClasses = computed(() => ({
  'user-card': true,
  'clickable': props.clickable,
  'loading': props.loading
}))

/**
 * จัดการการคลิกการ์ด
 * ส่ง event 'click' พร้อมข้อมูลผู้ใช้
 */
const handleClick = () => {
  if (props.clickable && !props.loading) {
    emit('click', props.user)
  }
}

/**
 * แปลงค่าเงินเป็นรูปแบบที่อ่านง่าย
 * @param {number} amount - จำนวนเงิน
 * @returns {string} - จำนวนเงินในรูปแบบ "฿1,000.00"
 */
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB'
  }).format(amount)
}
</script>

<style scoped>
/*
  Styles พร้อม dark mode support
  ใช้ Tailwind @apply directives
*/
.user-card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-md;
  @apply border border-gray-200 dark:border-gray-700;
  @apply transition-all duration-200;
  @apply p-6;
}

/* Clickable state */
.user-card.clickable {
  @apply cursor-pointer;
  @apply hover:shadow-lg hover:scale-105;
}

/* Header */
.card-header {
  @apply flex items-center gap-4;
  @apply mb-4;
}

/* Avatar */
.avatar {
  @apply w-12 h-12 rounded-full;
  @apply border-2 border-gray-200 dark:border-gray-600;
}

/* User info */
.user-info {
  @apply flex-1;
}

.user-name {
  @apply text-lg font-semibold;
  @apply text-gray-900 dark:text-gray-100;
}

.user-email {
  @apply text-sm text-gray-500 dark:text-gray-400;
}

/* Stats */
.card-stats {
  @apply grid grid-cols-2 gap-4;
  @apply py-4 border-t border-gray-200 dark:border-gray-700;
}

.stat-item {
  @apply flex flex-col;
}

.stat-label {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.stat-value {
  @apply text-lg font-bold text-gray-900 dark:text-gray-100;
}

/* Responsive */
@media (max-width: 640px) {
  .user-card {
    @apply p-4;
  }

  .card-stats {
    @apply grid-cols-1 gap-2;
  }
}
</style>
```

### 2. คู่มือการใช้งาน (Tips) - บังคับ

**ทุก function/component ต้องมี tips การใช้งาน**

#### ตัวอย่าง Tips ที่ดี:

```php
/**
 * @tip การใช้งาน:
 * 1. ใช้ cache() helper เพื่อเพิ่มความเร็ว
 * 2. เรียกใช้ภายใน transaction สำหรับ data consistency
 * 3. จำกัด perPage ไม่เกิน 100 เพื่อ performance
 */

/**
 * @tip Best Practices:
 * - ตรวจสอบ permission ก่อนเรียกใช้
 * - ใช้ queue สำหรับ bulk operations
 * - Enable cache ในโหมด production
 */
```

#### Vue Component Tips:

```vue
<!--
  💡 Tips การใช้งาน:

  1. Performance:
     - ใช้ v-show แทน v-if สำหรับ toggle บ่อยๆ
     - ใช้ computed properties แทน methods สำหรับการคำนวณ

  2. Accessibility:
     - Component รองรับ keyboard navigation
     - ใช้ tab สำหรับสลับ focus

  3. Customization:
     - Override CSS variables เพื่อปรับสี
     - ใช้ slots เพื่อ customize เนื้อหา

  4. Error Handling:
     - Component จะ emit 'error' event เมื่อมีปัญหา
     - ใช้ try-catch wrapper สำหรับ async operations
-->
```

### 3. Checklist สำหรับ Documentation

ก่อน commit ต้องตรวจสอบ:

- [ ] มีคอมเม้นต์ภาษาไทยอธิบายการทำงาน
- [ ] มี PHPDoc/JSDoc ครบถ้วน
- [ ] ระบุ @param, @returns, @throws
- [ ] มี @example แสดงวิธีใช้งาน
- [ ] มี @tip สำหรับ best practices
- [ ] Vue component มี props/events/slots documentation
- [ ] มี usage example ที่ชัดเจน

---

## 🎯 Icons & Visual Elements

### ใส่ไอคอนให้สวยงามเสมอ แต่ไม่รกเกินไป

**หลักการ: ไอคอนช่วยให้ UI เข้าใจง่ายขึ้น แต่ต้องใช้อย่างเหมาะสม**

### 1. Icon Library และมาตรฐาน

#### เลือกใช้ Icon Library เดียว

แนะนำ:
- **Heroicons** (ใช้งานกับ Tailwind CSS)
- **Lucide Icons** (Modern, lightweight)
- **Font Awesome** (มีไอคอนเยอะ)

```bash
# ติดตั้ง Heroicons สำหรับ Vue
npm install @heroicons/vue
```

#### การใช้งาน Heroicons:

```vue
<template>
  <!-- Outline style (24x24) -->
  <HomeIcon class="w-6 h-6 text-gray-500" />

  <!-- Solid style (20x20) -->
  <HomeIcon class="w-5 h-5 text-blue-500" />
</template>

<script setup>
import { HomeIcon } from '@heroicons/vue/24/outline'
// หรือ
import { HomeIcon } from '@heroicons/vue/24/solid'
</script>
```

### 2. ขนาดและ Spacing ของไอคอน

#### ขนาดมาตรฐาน:

```css
/* Small - สำหรับ inline text, badges */
.icon-sm {
  @apply w-4 h-4;  /* 16px */
}

/* Medium - สำหรับปุ่ม, navigation */
.icon-md {
  @apply w-5 h-5;  /* 20px */
}

/* Large - สำหรับ headers, cards */
.icon-lg {
  @apply w-6 h-6;  /* 24px */
}

/* Extra Large - สำหรับ empty states, placeholders */
.icon-xl {
  @apply w-8 h-8;  /* 32px */
}
```

#### Spacing กับ Text:

```vue
<!-- Icon ซ้าย + Text -->
<button class="flex items-center gap-2">
  <PlusIcon class="w-5 h-5" />
  <span>เพิ่มข้อมูล</span>
</button>

<!-- Icon ขวา + Text -->
<button class="flex items-center gap-2">
  <span>ดูรายละเอียด</span>
  <ArrowRightIcon class="w-5 h-5" />
</button>

<!-- Icon เดียว (tooltip) -->
<button
  class="p-2"
  title="ลบรายการ"
  aria-label="ลบรายการ"
>
  <TrashIcon class="w-5 h-5" />
</button>
```

### 3. Icon Colors และ Dark Mode

#### ใช้สีที่สอดคล้องกับ theme:

```vue
<template>
  <!-- Default - สีตาม theme -->
  <HomeIcon class="w-6 h-6 text-gray-500 dark:text-gray-400" />

  <!-- Primary color -->
  <UserIcon class="w-6 h-6 text-blue-500 dark:text-blue-400" />

  <!-- Semantic colors -->
  <CheckCircleIcon class="w-6 h-6 text-green-500" />
  <ExclamationIcon class="w-6 h-6 text-yellow-500" />
  <XCircleIcon class="w-6 h-6 text-red-500" />

  <!-- Interactive - hover state -->
  <button class="group">
    <PencilIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
  </button>
</template>
```

### 4. Icon Placement - ไม่รกเกินไป

#### ✅ ตัวอย่างที่ดี (ใช้ไอคอนเหมาะสม):

```vue
<template>
  <nav class="space-y-2">
    <!-- Navigation - ควรมีไอคอน -->
    <a href="/dashboard" class="nav-link">
      <HomeIcon class="w-5 h-5" />
      <span>หน้าหลัก</span>
    </a>

    <a href="/users" class="nav-link">
      <UsersIcon class="w-5 h-5" />
      <span>สมาชิก</span>
    </a>

    <a href="/settings" class="nav-link">
      <CogIcon class="w-5 h-5" />
      <span>ตั้งค่า</span>
    </a>
  </nav>

  <!-- Alert/Status - ควรมีไอคอน -->
  <div class="alert alert-success">
    <CheckCircleIcon class="w-5 h-5" />
    <span>บันทึกข้อมูลสำเร็จ</span>
  </div>

  <!-- Action buttons - ควรมีไอคอน -->
  <div class="action-buttons">
    <button class="btn-primary">
      <PlusIcon class="w-5 h-5" />
      เพิ่ม
    </button>

    <button class="btn-secondary">
      <DownloadIcon class="w-5 h-5" />
      ดาวน์โหลด
    </button>
  </div>

  <!-- Stats cards - ควรมีไอคอน -->
  <div class="stats-grid">
    <div class="stat-card">
      <UsersIcon class="w-8 h-8 text-blue-500" />
      <div class="stat-value">1,234</div>
      <div class="stat-label">ผู้ใช้ทั้งหมด</div>
    </div>
  </div>
</template>
```

#### ❌ ตัวอย่างที่ไม่ดี (ไอคอนรกเกินไป):

```vue
<!-- ห้ามทำแบบนี้ - ไอคอนมากเกินไป -->
<div class="user-card">
  <UserIcon class="icon" /> <!-- ไม่จำเป็น -->
  <h3>
    <StarIcon class="icon" /> <!-- ไม่จำเป็น -->
    ชื่อผู้ใช้
    <BadgeIcon class="icon" /> <!-- ไม่จำเป็น -->
  </h3>
  <p>
    <MailIcon class="icon" /> <!-- ซ้ำซ้อน -->
    อีเมล: user@example.com
  </p>
  <p>
    <PhoneIcon class="icon" /> <!-- ซ้ำซ้อน -->
    เบอร์: 0812345678
  </p>
</div>

<!-- ควรทำแบบนี้แทน - เรียบง่าย สวยงาม -->
<div class="user-card">
  <div class="user-avatar">
    <!-- Avatar image หรือ icon เดียว -->
    <UserIcon class="w-12 h-12" />
  </div>
  <div class="user-info">
    <h3>ชื่อผู้ใช้</h3>
    <p>user@example.com</p>
    <p>0812345678</p>
  </div>
</div>
```

### 5. Icon Animations

#### Subtle animations ที่ช่วย UX:

```vue
<template>
  <!-- Loading spinner -->
  <div class="spinner">
    <ArrowPathIcon class="w-6 h-6 animate-spin" />
  </div>

  <!-- Hover effect -->
  <button class="icon-button group">
    <HeartIcon class="w-6 h-6 transition-all duration-200 group-hover:scale-110 group-hover:text-red-500" />
  </button>

  <!-- Bounce on success -->
  <div v-if="success" class="alert">
    <CheckCircleIcon class="w-6 h-6 animate-bounce" />
    <span>สำเร็จ!</span>
  </div>
</template>

<style scoped>
/* Custom icon animations */
.icon-button:hover svg {
  transform: scale(1.1);
  transition: transform 200ms ease-in-out;
}

/* Pulse animation */
@keyframes pulse-icon {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.icon-pulse {
  animation: pulse-icon 2s ease-in-out infinite;
}
</style>
```

### 6. Icon Checklist

ก่อน commit ต้องตรวจสอบ:

- [ ] ใช้ icon library เดียวกันทั้งโปรเจค
- [ ] ขนาด icon สม่ำเสมอ (16px, 20px, 24px)
- [ ] มี dark mode support สำหรับสี icon
- [ ] Spacing เหมาะสมกับ text
- [ ] ไม่มี icon มากเกินไปจนรกตา
- [ ] Icon มีความหมายและช่วยให้เข้าใจง่ายขึ้น
- [ ] มี aria-label สำหรับ icon-only buttons
- [ ] Animations smooth และไม่มากเกินไป

---

## 📱 Responsive Design Guidelines

### บังคับ: ทุก UI ต้องเป็น Responsive เสมอ

**ทุก component, page, layout ต้องทำงานได้ดีบนทุก device**

### 1. Mobile-First Approach (บังคับ)

#### เริ่มออกแบบจาก Mobile ก่อนเสมอ:

```vue
<template>
  <!-- ❌ Desktop-first (ห้ามทำ) -->
  <div class="w-1/2 md:w-3/4 sm:w-full">
    <!-- เริ่มจาก desktop แล้วค่อยปรับ mobile -->
  </div>

  <!-- ✅ Mobile-first (ควรทำ) -->
  <div class="w-full md:w-3/4 lg:w-1/2">
    <!-- เริ่มจาก mobile แล้วค่อย enhance ไป desktop -->
  </div>
</template>
```

### 2. Breakpoints ที่ต้องรองรับ (บังคับ)

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    screens: {
      'sm': '640px',   // Mobile landscape / Small tablet
      'md': '768px',   // Tablet
      'lg': '1024px',  // Desktop
      'xl': '1280px',  // Large desktop
      '2xl': '1536px'  // Extra large desktop
    }
  }
}
```

#### ทดสอบบนหน้าจอขนาดต่างๆ:

- ✅ **Mobile**: 320px - 639px (iPhone SE, Android)
- ✅ **Mobile Landscape**: 640px - 767px
- ✅ **Tablet**: 768px - 1023px (iPad)
- ✅ **Desktop**: 1024px - 1279px
- ✅ **Large Desktop**: 1280px+

### 3. Responsive Components (ตัวอย่าง)

#### Grid Layout:

```vue
<template>
  <!-- 1 column mobile → 2 tablet → 3 desktop → 4 large -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <div v-for="item in items" :key="item.id" class="card">
      {{ item.name }}
    </div>
  </div>
</template>
```

#### Navigation:

```vue
<template>
  <nav>
    <!-- Desktop menu (แสดงบน desktop เท่านั้น) -->
    <div class="hidden lg:flex space-x-4">
      <a href="/dashboard">หน้าหลัก</a>
      <a href="/users">สมาชิก</a>
      <a href="/settings">ตั้งค่า</a>
    </div>

    <!-- Mobile menu button (แสดงบน mobile เท่านั้น) -->
    <button
      class="lg:hidden"
      @click="toggleMobileMenu"
      aria-label="เปิดเมนู"
    >
      <Bars3Icon class="w-6 h-6" />
    </button>

    <!-- Mobile menu (slide-in) -->
    <div
      v-show="mobileMenuOpen"
      class="fixed inset-0 bg-white dark:bg-gray-900 lg:hidden"
    >
      <!-- Mobile menu content -->
    </div>
  </nav>
</template>
```

#### Tables → Cards บน Mobile:

```vue
<template>
  <!-- Desktop: แสดงเป็น table -->
  <table class="hidden md:table">
    <thead>
      <tr>
        <th>ชื่อ</th>
        <th>อีเมล</th>
        <th>สถานะ</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="user in users" :key="user.id">
        <td>{{ user.name }}</td>
        <td>{{ user.email }}</td>
        <td>{{ user.status }}</td>
      </tr>
    </tbody>
  </table>

  <!-- Mobile: แสดงเป็น cards -->
  <div class="md:hidden space-y-4">
    <div
      v-for="user in users"
      :key="user.id"
      class="card"
    >
      <div class="font-bold">{{ user.name }}</div>
      <div class="text-sm text-gray-500">{{ user.email }}</div>
      <div class="mt-2">
        <span class="badge">{{ user.status }}</span>
      </div>
    </div>
  </div>
</template>
```

#### Typography Responsive:

```vue
<template>
  <!-- Heading ปรับขนาดตาม screen -->
  <h1 class="text-2xl md:text-3xl lg:text-4xl xl:text-5xl font-bold">
    หัวข้อหลัก
  </h1>

  <!-- Paragraph spacing responsive -->
  <p class="text-sm md:text-base lg:text-lg leading-relaxed">
    เนื้อหาบทความ
  </p>

  <!-- Container padding responsive -->
  <div class="px-4 md:px-6 lg:px-8 py-4 md:py-6 lg:py-8">
    Content
  </div>
</template>
```

### 4. Touch-Friendly Design (Mobile)

#### ขนาดปุ่มและ Touch Targets:

```vue
<template>
  <!-- ✅ Touch-friendly (ขนาดอย่างน้อย 44x44px) -->
  <button class="min-w-[44px] min-h-[44px] px-4 py-2">
    บันทึก
  </button>

  <!-- ✅ Spacing เพียงพอ -->
  <div class="flex gap-4">
    <button class="btn">ยืนยัน</button>
    <button class="btn">ยกเลิก</button>
  </div>

  <!-- ❌ ห้ามทำ - ปุ่มเล็กเกินไป, spacing น้อยเกินไป -->
  <div class="flex gap-1">
    <button class="px-1 py-0.5 text-xs">แก้ไข</button>
    <button class="px-1 py-0.5 text-xs">ลบ</button>
  </div>
</template>
```

#### No Hover-Only Interactions:

```vue
<!-- ❌ ห้ามใช้ hover-only (mobile ไม่มี hover) -->
<div class="group">
  <div class="hidden group-hover:block">
    <!-- เนื้อหาจะไม่แสดงบน mobile -->
  </div>
</div>

<!-- ✅ ใช้ click/tap แทน -->
<div>
  <button @click="showDetails = !showDetails">
    ดูรายละเอียด
  </button>
  <div v-show="showDetails">
    <!-- เนื้อหา -->
  </div>
</div>
```

### 5. Images และ Media Responsive:

```vue
<template>
  <!-- Responsive images -->
  <img
    :src="imageSrc"
    :srcset="`
      ${imageSrc}?w=400 400w,
      ${imageSrc}?w=800 800w,
      ${imageSrc}?w=1200 1200w
    `"
    sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
    class="w-full h-auto"
    loading="lazy"
    alt="รูปภาพ"
  />

  <!-- Responsive video -->
  <div class="aspect-w-16 aspect-h-9">
    <video
      class="w-full h-full object-cover"
      controls
    >
      <source src="video.mp4" type="video/mp4">
    </video>
  </div>
</template>
```

### 6. Responsive Testing Checklist (บังคับ)

ก่อน commit ต้องทดสอบทุกขนาดหน้าจอ:

#### Device Testing:
- [ ] **iPhone SE** (375x667) - smallest mobile
- [ ] **iPhone 12/13** (390x844) - standard mobile
- [ ] **Android** (360x640) - common Android size
- [ ] **iPad** (768x1024) - tablet portrait
- [ ] **iPad Landscape** (1024x768) - tablet landscape
- [ ] **Desktop** (1920x1080) - standard desktop
- [ ] **Large Desktop** (2560x1440) - large screen

#### Functionality Testing:
- [ ] Navigation ทำงานบนทุก device
- [ ] Forms กรอกได้สะดวกบน mobile
- [ ] Tables/Lists แสดงผลเหมาะสมบน mobile
- [ ] Images โหลดเร็วและแสดงผลถูกต้อง
- [ ] Touch targets ขนาดเหมาะสม (≥44px)
- [ ] ไม่มี horizontal scroll
- [ ] Text อ่านง่ายบนทุกขนาดหน้าจอ
- [ ] Spacing เหมาะสมบนทุก device

### 7. Responsive Utilities

#### ซ่อน/แสดง Elements:

```vue
<!-- แสดงเฉพาะ mobile -->
<div class="block md:hidden">
  Mobile only content
</div>

<!-- แสดงเฉพาะ tablet+ -->
<div class="hidden md:block">
  Tablet and desktop content
</div>

<!-- แสดงเฉพาะ desktop -->
<div class="hidden lg:block">
  Desktop only content
</div>
```

#### Container Max Width:

```vue
<template>
  <!-- Responsive container -->
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <!-- เนื้อหาจะอยู่กึ่งกลาง พร้อม padding ที่เหมาะสม -->
  </div>

  <!-- Max width responsive -->
  <div class="max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl mx-auto">
    Content
  </div>
</template>
```

---

## 💻 Code Quality Guidelines

### Backend (Laravel/PHP)

#### 1. Clean Code Principles

```php
// ❌ ห้ามทำ
public function a($u) {
    $d = DB::table('users')->where('id', $u)->first();
    return $d;
}

// ✅ ควรทำ
public function getUserById(int $userId): ?User
{
    return User::query()
        ->where('id', $userId)
        ->first();
}
```

#### 2. Service Layer Pattern

```php
// app/Services/UserService.php
class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService
    ) {}

    public function createUser(array $data): User
    {
        DB::beginTransaction();

        try {
            $user = $this->userRepository->create($data);
            $this->notificationService->sendWelcomeEmail($user);

            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

#### 3. Controller Best Practices

```php
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user'
            ], 500);
        }
    }
}
```

### Frontend (Vue.js)

#### 1. Component Structure

```vue
<template>
  <div class="user-card">
    <!-- Component markup -->
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'

// Props
const props = defineProps({
  userId: {
    type: Number,
    required: true
  }
})

// Emits
const emit = defineEmits(['user-loaded', 'error'])

// Store
const userStore = useUserStore()

// Refs
const loading = ref(false)
const user = ref(null)

// Computed
const displayName = computed(() => {
  return user.value?.name || 'Guest'
})

// Methods
const loadUser = async () => {
  loading.value = true
  try {
    user.value = await userStore.fetchUser(props.userId)
    emit('user-loaded', user.value)
  } catch (error) {
    emit('error', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadUser()
})
</script>

<style scoped>
.user-card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-6;
}
</style>
```

#### 2. Composables

```javascript
// composables/useApi.js
import { ref } from 'vue'
import axios from 'axios'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  const request = async (method, url, data = null) => {
    loading.value = true
    error.value = null

    try {
      const response = await axios({ method, url, data })
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    get: (url) => request('get', url),
    post: (url, data) => request('post', url, data),
    put: (url, data) => request('put', url, data),
    delete: (url) => request('delete', url)
  }
}
```

---

## 📱 Responsive Design

### Breakpoints

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    screens: {
      'sm': '640px',   // Mobile landscape
      'md': '768px',   // Tablet
      'lg': '1024px',  // Desktop
      'xl': '1280px',  // Large desktop
      '2xl': '1536px'  // Extra large
    }
  }
}
```

### Mobile-First Approach

```html
<!-- ❌ Desktop-first (ห้ามทำ) -->
<div class="w-full lg:w-1/2 md:w-3/4">

<!-- ✅ Mobile-first (ควรทำ) -->
<div class="w-full md:w-3/4 lg:w-1/2">
```

---

## ♿ Accessibility Requirements

### 1. Semantic HTML

```html
<!-- ✅ ควรทำ -->
<nav aria-label="Main navigation">
  <ul>
    <li><a href="/">Home</a></li>
  </ul>
</nav>

<main>
  <article>
    <h1>Title</h1>
    <p>Content</p>
  </article>
</main>

<footer>
  <!-- Footer content -->
</footer>
```

### 2. ARIA Labels

```html
<button aria-label="Close modal" @click="closeModal">
  <span aria-hidden="true">&times;</span>
</button>

<input
  type="text"
  id="search"
  aria-describedby="search-help"
/>
<span id="search-help">Enter keywords to search</span>
```

### 3. Keyboard Navigation

```vue
<template>
  <div
    role="button"
    tabindex="0"
    @click="handleClick"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    Interactive Element
  </div>
</template>
```

---

## ✅ Testing Checklist

### Pre-Commit Checklist

ก่อน commit code ทุกครั้ง ต้องตรวจสอบ:

#### Design & UX
- [ ] รองรับทั้ง dark mode และ light mode
- [ ] UI สวยงามและเป็นมืออาชีพ
- [ ] Responsive บนทุก device (mobile, tablet, desktop)
- [ ] Animations smooth (60fps)
- [ ] Loading states แสดงอย่างเหมาะสม

#### Code Quality
- [ ] Code clean และ readable
- [ ] ไม่มี duplicated code
- [ ] Type hints และ validation ครบถ้วน
- [ ] Error handling ถูกต้อง
- [ ] Comments สำหรับ complex logic

#### Performance
- [ ] ไม่มี N+1 queries
- [ ] Images optimized
- [ ] Lazy loading ถูกใช้งาน
- [ ] No memory leaks

#### Testing
- [ ] Unit tests passed
- [ ] Manual testing บนทุก browser
- [ ] ไม่มี console errors/warnings
- [ ] Lighthouse score > 90

#### Accessibility
- [ ] Semantic HTML ถูกต้อง
- [ ] Keyboard navigation ทำงาน
- [ ] Screen reader compatible
- [ ] WCAG AA compliance

#### Security
- [ ] Input validation
- [ ] XSS protection
- [ ] CSRF protection
- [ ] Authorization checks

---

## 🎯 สรุป

### หลักการทอง 6 ข้อ (บังคับเสมอ):

1. **🌓 Dark/Light Mode เสมอ**
   - ทุก UI ต้องรองรับทั้งสองโหมด
   - ใช้ CSS variables และ Tailwind dark utilities
   - ทดสอบ contrast และ readability (WCAG AA)

2. **💎 สวยงามหลักล้านเสมอ**
   - Professional-grade UI/UX
   - Spacing, typography, colors ต้องลงตัว
   - ใส่ไอคอนสวยงามแต่ไม่รก
   - Animations smooth (60fps)

3. **📱 Responsive เสมอ**
   - Mobile-first approach (บังคับ)
   - ทดสอบทุก device (mobile, tablet, desktop)
   - Touch-friendly บน mobile (≥44px)
   - ไม่มี horizontal scroll

4. **💬 คอมเม้นต์ภาษาไทยเสมอ**
   - อธิบายการทำงานเป็นภาษาไทย
   - มี JSDoc/PHPDoc พร้อม @param, @returns
   - มี @example แสดงวิธีใช้งาน
   - ใส่ @tip สำหรับ best practices

5. **📚 คู่มือการใช้งานเสมอ**
   - ระบุ props, events, slots (Vue)
   - ระบุ parameters, return values (PHP)
   - ให้ usage example ที่ชัดเจน
   - เพิ่ม tips และ best practices

6. **🔧 โค้ดมืออาชีพเสมอ**
   - Clean, maintainable, performant
   - Follow best practices
   - Proper error handling
   - Complete testing

### ห้ามทำ (ห้ามเด็ดขาด):

#### Design:
- ❌ Hard-code colors (ต้องใช้ CSS variables)
- ❌ UI ไม่สวยหรือไม่เป็นมืออาชีพ
- ❌ ไม่รองรับ dark mode
- ❌ ไอคอนรกเกินไป หรือไม่มีไอคอนเลย
- ❌ Animations ที่กระตุก (< 60fps)

#### Responsive:
- ❌ ไม่ responsive (fixed width)
- ❌ Desktop-first approach
- ❌ Touch targets เล็กเกินไป (< 44px)
- ❌ Hover-only interactions
- ❌ มี horizontal scroll บน mobile

#### Documentation:
- ❌ ไม่มีคอมเม้นต์ภาษาไทย
- ❌ ไม่มี JSDoc/PHPDoc
- ❌ ไม่มี usage example
- ❌ ไม่มี tips การใช้งาน

#### Code Quality:
- ❌ Code messy หรือ duplicated
- ❌ ละเลย error handling
- ❌ ละเลย accessibility
- ❌ ไม่ทดสอบ

---

### ตัวอย่าง Component ที่สมบูรณ์

```vue
<template>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">{{ title }}</h3>
      <button
        class="card-close"
        aria-label="Close"
        @click="handleClose"
      >
        <svg class="icon" viewBox="0 0 24 24">
          <path d="M18 6L6 18M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="card-body">
      <slot />
    </div>

    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['close'])

const handleClose = () => {
  emit('close')
}
</script>

<style scoped>
.card {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden;
  @apply border border-gray-200 dark:border-gray-700;
  @apply transition-all duration-200;
}

.card:hover {
  @apply shadow-xl;
}

.card-header {
  @apply flex items-center justify-between px-6 py-4;
  @apply border-b border-gray-200 dark:border-gray-700;
  @apply bg-gray-50 dark:bg-gray-900/50;
}

.card-title {
  @apply text-lg font-semibold text-gray-900 dark:text-gray-100;
}

.card-close {
  @apply p-2 rounded-lg transition-colors;
  @apply hover:bg-gray-200 dark:hover:bg-gray-700;
  @apply text-gray-500 dark:text-gray-400;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.card-body {
  @apply px-6 py-4;
  @apply text-gray-700 dark:text-gray-300;
}

.card-footer {
  @apply px-6 py-4;
  @apply border-t border-gray-200 dark:border-gray-700;
  @apply bg-gray-50 dark:bg-gray-900/50;
}

.icon {
  @apply w-5 h-5 stroke-current stroke-2;
  fill: none;
}
</style>
```

---

**Remember**: ทุกโค้ดที่เราเขียนคือผลงานที่เราภาคภูมิใจ 🎨✨

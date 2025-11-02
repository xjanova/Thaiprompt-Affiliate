# 🎨 Design Guidelines - Thaiprompt Affiliate System

> **หลักการสำคัญ**: ทุกโค้ดและ UI ที่พัฒนาต้องรองรับโหมดมืด-สว่าง, สวยงามหลักล้าน, และมืออาชีพเสมอ

## 📑 สารบัญ

1. [Dark/Light Mode Support](#darklight-mode-support)
2. [Professional Design Standards](#professional-design-standards)
3. [Code Quality Guidelines](#code-quality-guidelines)
4. [Component Development](#component-development)
5. [Performance Standards](#performance-standards)
6. [Accessibility Requirements](#accessibility-requirements)
7. [Testing Checklist](#testing-checklist)

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

### หลักการทอง 3 ข้อ:

1. **🌓 Dark/Light Mode เป็นบังคับ**
   - ทุก UI ต้องรองรับทั้งสองโหมด
   - ใช้ CSS variables และ Tailwind dark utilities
   - ทดสอบ contrast และ readability

2. **💎 สวยงามหลักล้านเสมอ**
   - ใช้ design system อย่างสม่ำเสมอ
   - Spacing, typography, colors ต้องลงตัว
   - Professional-grade UI/UX

3. **🔧 โค้ดมืออาชีพเสมอ**
   - Clean, maintainable, performant
   - Follow best practices
   - Proper testing และ documentation

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

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

### 💎 Code Quality Standards

**โค้ดทุกบรรทัดต้องมีคุณภาพระดับมืออาชีพ**

#### Backend (Laravel/PHP):

1. **Clean Code Principles**
   - Single Responsibility Principle
   - DRY (Don't Repeat Yourself)
   - Meaningful variable และ function names
   - Proper type hints และ return types

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

**ทุก UI ต้องทำงานได้ดีบนทุก device**

1. **Breakpoints**
   - Mobile: < 640px
   - Tablet: 640px - 1024px
   - Desktop: > 1024px

2. **Mobile-First Approach**
   - เริ่มออกแบบจาก mobile ก่อน
   - Progressive enhancement สำหรับ larger screens

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

- [ ] รองรับทั้ง dark mode และ light mode
- [ ] UI สวยงามและเป็นมืออาชีพ
- [ ] Code clean และ maintainable
- [ ] ผ่าน linting และ formatting standards
- [ ] ทดสอบบน mobile, tablet, desktop
- [ ] ไม่มี console errors หรือ warnings
- [ ] Performance ดี (Lighthouse score > 90)
- [ ] Accessibility compliance

## สรุป

**ทุกโค้ดที่เขียนต้อง:**
- ✅ รองรับ dark/light mode เสมอ
- ✅ สวยงามระดับมืออาชีพ
- ✅ Clean, maintainable, และ performant
- ✅ Accessible และ responsive
- ✅ Secure และ follow best practices

**ห้าม:**
- ❌ Hard-code colors
- ❌ UI ที่ไม่สวยหรือไม่เป็นมืออาชีพ
- ❌ Code ที่ messy หรือ duplicated
- ❌ Fixed dimensions ที่ไม่ responsive
- ❌ ละเลย accessibility

---

*"Excellence is not an act, but a habit" - ทำให้ทุกโค้ดเป็นผลงานที่ภาคภูมิใจ*

# 🏔️ Alpine.js Best Practices - V3

> **แนวทางการใช้ Alpine.js สำหรับ Version 3**
> Component patterns, State management, Performance optimization

**Version**: 3.0.0
**Last Updated**: 2025-11-15
**Alpine.js Version**: 3.13.x

---

## 📋 สารบัญ

1. [ทำไมต้องใช้ Alpine.js](#ทำไมต้องใช้-alpinejs)
2. [Component Patterns](#component-patterns)
3. [State Management](#state-management)
4. [Directives Best Practices](#directives-best-practices)
5. [Integration กับ Laravel](#integration-กับ-laravel)
6. [Integration กับ SortableJS](#integration-กับ-sortablejs)
7. [Performance Optimization](#performance-optimization)
8. [Common Patterns](#common-patterns)
9. [Anti-Patterns (ห้ามทำ)](#anti-patterns-ห้ามทำ)

---

## ทำไมต้องใช้ Alpine.js

### ✅ ข้อดี

```
┌──────────────────────────────────────┐
│  Alpine.js Advantages                │
├──────────────────────────────────────┤
│  ✅ เบา (~15KB gzipped)              │
│  ✅ เร็ว (No virtual DOM)            │
│  ✅ เรียบง่าย (Syntax คล้าย Vue)     │
│  ✅ Declarative (เขียนใน HTML)      │
│  ✅ ไม่ต้อง build step               │
│  ✅ เข้ากับ Laravel ได้ดี            │
└──────────────────────────────────────┘
```

### ⚖️ Alpine.js vs jQuery vs Vue.js

| Feature | Alpine.js | jQuery | Vue.js |
|---------|-----------|--------|--------|
| **ขนาด** | ~15KB | ~30KB | ~90KB |
| **Learning Curve** | ต่ำ | ต่ำ | กลาง-สูง |
| **Reactive** | ✅ | ❌ | ✅ |
| **Build Step** | ❌ | ❌ | ✅ |
| **SSR Friendly** | ✅ | ✅ | ⚠️ |
| **Component System** | ⚠️ Simple | ❌ | ✅ Advanced |

**สรุป**: ใช้ Alpine.js สำหรับ simple → medium complexity, Vue.js สำหรับ complex SPA

---

## Component Patterns

### 1. 📦 Basic Component Structure

```javascript
/**
 * Component Pattern - โครงสร้าง component พื้นฐาน
 *
 * @param {object} options - ตัวเลือก component
 * @returns {object} Alpine component object
 *
 * @example
 * <div x-data="myComponent()">...</div>
 */
function myComponent(options = {}) {
    return {
        // ----------------
        // 1. State (ข้อมูล)
        // ----------------
        title: options.title || 'Default Title',
        isLoading: false,
        items: [],
        error: null,

        // ----------------
        // 2. Computed Properties (คำนวณจาก state)
        // ----------------
        get itemCount() {
            return this.items.length;
        },

        get hasItems() {
            return this.items.length > 0;
        },

        // ----------------
        // 3. Lifecycle Methods
        // ----------------

        /**
         * เริ่มต้น component (เหมือน mounted ใน Vue)
         */
        init() {
            console.log('Component initialized');

            // โหลดข้อมูลเริ่มต้น
            this.loadData();

            // Setup listeners
            this.$watch('items', (value) => {
                console.log('Items changed:', value);
            });
        },

        // ----------------
        // 4. Methods (ฟังก์ชันต่างๆ)
        // ----------------

        /**
         * โหลดข้อมูล
         */
        async loadData() {
            this.isLoading = true;
            this.error = null;

            try {
                const response = await fetch('/api/items');
                this.items = await response.json();
            } catch (error) {
                this.error = error.message;
                console.error('Error loading data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * เพิ่มรายการ
         */
        async addItem(data) {
            try {
                const response = await fetch('/api/items', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const newItem = await response.json();
                this.items.push(newItem);

                // Dispatch event
                this.$dispatch('item-added', newItem);
            } catch (error) {
                this.error = error.message;
            }
        },

        /**
         * ลบรายการ
         */
        async deleteItem(id) {
            if (!confirm('ต้องการลบหรือไม่?')) return;

            try {
                await fetch(`/api/items/${id}`, { method: 'DELETE' });

                this.items = this.items.filter(item => item.id !== id);

                this.$dispatch('item-deleted', { id });
            } catch (error) {
                this.error = error.message;
            }
        },

        // ----------------
        // 5. Event Handlers
        // ----------------

        /**
         * Handle form submit
         */
        handleSubmit(e) {
            e.preventDefault();
            // Handle form submission
        },

        /**
         * Handle item click
         */
        handleItemClick(item) {
            console.log('Item clicked:', item);
        },
    };
}

// Export สำหรับใช้งาน global
window.myComponent = myComponent;
```

### 2. 🎯 Modal Component (สมบูรณ์)

```javascript
/**
 * Modal Component - จัดการ modal dialog
 *
 * @param {object} options - ตัวเลือก modal
 * @param {string} options.title - หัวข้อ modal
 * @param {boolean} options.closeOnEscape - ปิดด้วย Escape key
 * @param {boolean} options.closeOnOutside - ปิดเมื่อคลิกภายนอก
 *
 * @returns {object} Alpine component object
 *
 * @example
 * <div x-data="modalComponent({ title: 'ยืนยันการลบ' })">
 *   <button @click="open()">เปิด Modal</button>
 * </div>
 */
function modalComponent(options = {}) {
    return {
        // State
        isOpen: false,
        title: options.title || '',
        closeOnEscape: options.closeOnEscape !== false,
        closeOnOutside: options.closeOnOutside !== false,

        // Lifecycle
        init() {
            // Listen for Escape key
            if (this.closeOnEscape) {
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.close();
                    }
                });
            }

            // Listen for custom events
            this.$watch('isOpen', (value) => {
                // ล็อก scroll เมื่อ modal เปิด
                document.body.style.overflow = value ? 'hidden' : '';

                // Dispatch event
                this.$dispatch('modal-state-changed', { isOpen: value });
            });
        },

        // Methods

        /**
         * เปิด modal
         */
        open() {
            this.isOpen = true;
            this.$dispatch('modal-opened');

            // Focus on first input
            this.$nextTick(() => {
                const firstInput = this.$el.querySelector('input, textarea, select');
                if (firstInput) {
                    firstInput.focus();
                }
            });
        },

        /**
         * ปิด modal
         */
        close() {
            this.isOpen = false;
            this.$dispatch('modal-closed');
        },

        /**
         * Toggle modal
         */
        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        /**
         * Handle outside click
         */
        handleOutsideClick() {
            if (this.closeOnOutside) {
                this.close();
            }
        },
    };
}

window.modalComponent = modalComponent;
```

**การใช้งาน**:

```blade
<div x-data="modalComponent({ title: 'ยืนยันการลบ', closeOnOutside: true })">

    {{-- Trigger Button --}}
    <button @click="open()"
            class="px-4 py-2 bg-blue-500 text-white rounded-lg">
        เปิด Modal
    </button>

    {{-- Modal --}}
    <div x-show="isOpen"
         x-transition
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div @click="handleOutsideClick()"
             class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        {{-- Modal Content --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white" x-text="title"></h3>
                    <button @click="close()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <p class="text-gray-700 dark:text-gray-300">
                    คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?
                </p>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
                <button @click="close()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">
                    ยกเลิก
                </button>
                <button @click="close()"
                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg">
                    ยืนยัน
                </button>
            </div>
        </div>
    </div>
</div>
```

### 3. 📋 Form Component with Validation

```javascript
/**
 * Form Component - จัดการฟอร์มพร้อม validation
 */
function formComponent() {
    return {
        // Form Data
        formData: {
            name: '',
            email: '',
            message: '',
        },

        // Validation
        errors: {},
        isSubmitting: false,

        // Validation Rules
        rules: {
            name: {
                required: true,
                minLength: 3,
            },
            email: {
                required: true,
                email: true,
            },
            message: {
                required: true,
                minLength: 10,
            },
        },

        /**
         * Validate single field
         */
        validateField(fieldName) {
            const value = this.formData[fieldName];
            const rules = this.rules[fieldName];
            const errors = [];

            // Required
            if (rules.required && !value) {
                errors.push('กรุณากรอกข้อมูล');
            }

            // Min Length
            if (rules.minLength && value.length < rules.minLength) {
                errors.push(`ต้องมีอย่างน้อย ${rules.minLength} ตัวอักษร`);
            }

            // Email
            if (rules.email && value && !this.isValidEmail(value)) {
                errors.push('รูปแบบอีเมลไม่ถูกต้อง');
            }

            // Update errors
            if (errors.length > 0) {
                this.errors[fieldName] = errors[0];
            } else {
                delete this.errors[fieldName];
            }

            return errors.length === 0;
        },

        /**
         * Validate all fields
         */
        validateAll() {
            let isValid = true;

            for (const fieldName in this.rules) {
                if (!this.validateField(fieldName)) {
                    isValid = false;
                }
            }

            return isValid;
        },

        /**
         * Check if email is valid
         */
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        /**
         * Submit form
         */
        async submitForm() {
            // Validate
            if (!this.validateAll()) {
                return;
            }

            this.isSubmitting = true;

            try {
                const response = await fetch('/api/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.formData)
                });

                if (response.ok) {
                    this.$dispatch('form-submitted', this.formData);
                    this.resetForm();
                } else {
                    throw new Error('เกิดข้อผิดพลาด');
                }
            } catch (error) {
                this.$dispatch('form-error', error);
            } finally {
                this.isSubmitting = false;
            }
        },

        /**
         * Reset form
         */
        resetForm() {
            this.formData = {
                name: '',
                email: '',
                message: '',
            };
            this.errors = {};
        },
    };
}

window.formComponent = formComponent;
```

---

## State Management

### 1. 🗃️ Component State (Local State)

```javascript
// ใช้สำหรับ state ที่เฉพาะ component
function counter() {
    return {
        count: 0,  // Local state

        increment() {
            this.count++;
        },

        decrement() {
            this.count--;
        },
    };
}
```

### 2. 🌍 Global State (Alpine Stores)

**สร้าง Store** (`resources/js/alpine/stores/cart.js`):

```javascript
import Alpine from 'alpinejs';

/**
 * Cart Store - จัดการตะกร้าสินค้า (global state)
 */
Alpine.store('cart', {
    // State
    items: [],
    isOpen: false,

    // Computed
    get total() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    get itemCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    // Methods

    /**
     * เพิ่มสินค้า
     */
    addItem(product) {
        const existingItem = this.items.find(item => item.id === product.id);

        if (existingItem) {
            existingItem.quantity++;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
            });
        }

        // Save to localStorage
        this.save();

        // Dispatch event
        Alpine.store('notification').show({
            message: `เพิ่ม ${product.name} ลงตะกร้าแล้ว`,
            type: 'success'
        });
    },

    /**
     * ลบสินค้า
     */
    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.save();
    },

    /**
     * อัพเดทจำนวน
     */
    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);

        if (item) {
            item.quantity = Math.max(1, quantity);
            this.save();
        }
    },

    /**
     * ล้างตะกร้า
     */
    clear() {
        this.items = [];
        this.save();
    },

    /**
     * Toggle cart sidebar
     */
    toggle() {
        this.isOpen = !this.isOpen;
    },

    /**
     * Save to localStorage
     */
    save() {
        localStorage.setItem('cart', JSON.stringify(this.items));
    },

    /**
     * Load from localStorage
     */
    load() {
        const saved = localStorage.getItem('cart');
        if (saved) {
            this.items = JSON.parse(saved);
        }
    },

    /**
     * Initialize
     */
    init() {
        this.load();
    }
});
```

**การใช้งาน Store**:

```blade
{{-- Add to Cart Button --}}
<button @click="$store.cart.addItem({
            id: {{ $product->id }},
            name: '{{ $product->name }}',
            price: {{ $product->price }}
        })"
        class="px-4 py-2 bg-blue-500 text-white rounded-lg">
    <i class="fas fa-cart-plus mr-2"></i>
    เพิ่มลงตะกร้า
</button>

{{-- Cart Badge --}}
<div class="relative">
    <button @click="$store.cart.toggle()">
        <i class="fas fa-shopping-cart"></i>

        {{-- Item Count Badge --}}
        <span x-show="$store.cart.itemCount > 0"
              x-text="$store.cart.itemCount"
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
        </span>
    </button>
</div>

{{-- Cart Sidebar --}}
<div x-show="$store.cart.isOpen"
     x-transition
     class="fixed right-0 top-0 h-full w-96 bg-white dark:bg-gray-800 shadow-2xl z-50">

    <div class="p-6">
        <h2 class="text-2xl font-bold mb-4">ตะกร้าสินค้า</h2>

        {{-- Cart Items --}}
        <template x-for="item in $store.cart.items" :key="item.id">
            <div class="flex items-center gap-4 mb-4">
                <div class="flex-1">
                    <h4 x-text="item.name"></h4>
                    <p class="text-sm text-gray-500">
                        ฿<span x-text="item.price"></span> x <span x-text="item.quantity"></span>
                    </p>
                </div>

                <button @click="$store.cart.removeItem(item.id)"
                        class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </template>

        {{-- Total --}}
        <div class="border-t pt-4 mt-4">
            <div class="flex justify-between text-xl font-bold">
                <span>รวม:</span>
                <span>฿<span x-text="$store.cart.total.toLocaleString()"></span></span>
            </div>
        </div>
    </div>
</div>
```

---

## Directives Best Practices

### ✅ ใช้ Directives อย่างถูกต้อง

```blade
{{-- x-data: กำหนด component scope --}}
<div x-data="{ count: 0 }">

{{-- x-init: รันเมื่อ component เริ่มต้น --}}
<div x-init="console.log('Initialized')">

{{-- x-show: แสดง/ซ่อน (element ยังอยู่ใน DOM) --}}
<div x-show="isVisible">

{{-- x-if: แสดง/ซ่อน (remove จาก DOM) - ใช้กับ template --}}
<template x-if="isVisible">
    <div>Content</div>
</template>

{{-- x-for: Loop --}}
<template x-for="item in items" :key="item.id">
    <div x-text="item.name"></div>
</template>

{{-- x-model: Two-way binding --}}
<input type="text" x-model="name">

{{-- x-bind: One-way binding (shorthand :) --}}
<div :class="isActive ? 'active' : ''">

{{-- x-on: Event listener (shorthand @) --}}
<button @click="handleClick">

{{-- x-text: Set text content --}}
<p x-text="message"></p>

{{-- x-html: Set HTML content (ระวัง XSS!) --}}
<div x-html="htmlContent"></div>

{{-- x-cloak: ซ่อนจนกว่า Alpine จะ init --}}
<div x-cloak>

{{-- x-transition: Smooth transitions --}}
<div x-show="isVisible" x-transition>
```

### ⚡ Performance Tips

```blade
{{-- ✅ ดี: ใช้ x-if สำหรับ content ที่ซับซ้อนและไม่ค่อยแสดง --}}
<template x-if="showComplexComponent">
    <div>
        <!-- Complex content -->
    </div>
</template>

{{-- ✅ ดี: ใช้ x-show สำหรับ content ที่เปลี่ยนบ่อย --}}
<div x-show="isVisible">
    <!-- Simple content that toggles often -->
</div>

{{-- ✅ ดี: Debounce input events --}}
<input type="text" @input.debounce.500ms="search()">

{{-- ✅ ดี: Throttle scroll events --}}
<div @scroll.throttle.100ms="handleScroll()">

{{-- ❌ ห้าม: ใช้ complex expressions ใน template --}}
<div x-text="items.filter(i => i.active).map(i => i.name).join(', ')"></div>

{{-- ✅ ดี: ใช้ computed property แทน --}}
<div x-text="activeItemNames"></div>

<script>
{
    get activeItemNames() {
        return this.items
            .filter(i => i.active)
            .map(i => i.name)
            .join(', ');
    }
}
</script>
```

---

## Integration กับ SortableJS

```javascript
/**
 * Sortable List Component - Integration กับ SortableJS
 */
import Sortable from 'sortablejs';

function sortableListComponent() {
    return {
        items: [],
        sortable: null,

        init() {
            this.loadItems();

            this.$nextTick(() => {
                this.initSortable();
            });
        },

        /**
         * เริ่มต้น SortableJS
         */
        initSortable() {
            const el = this.$refs.sortableList;

            this.sortable = new Sortable(el, {
                animation: 200,
                ghostClass: 'opacity-30',
                chosenClass: 'ring-2 ring-blue-500',
                dragClass: 'shadow-2xl scale-105',

                onEnd: (evt) => {
                    this.updateOrder(evt);
                }
            });
        },

        /**
         * โหลดรายการ
         */
        async loadItems() {
            const response = await fetch('/api/items');
            this.items = await response.json();
        },

        /**
         * อัพเดทลำดับ
         */
        async updateOrder(evt) {
            // อัพเดทใน array
            const item = this.items.splice(evt.oldIndex, 1)[0];
            this.items.splice(evt.newIndex, 0, item);

            // ส่งไป backend
            await fetch('/api/items/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    items: this.items.map((item, index) => ({
                        id: item.id,
                        order: index + 1
                    }))
                })
            });
        }
    };
}

window.sortableListComponent = sortableListComponent;
```

---

## Common Patterns

### 1. 📡 Fetching Data

```javascript
function dataFetcher() {
    return {
        data: null,
        isLoading: false,
        error: null,

        /**
         * Fetch data with loading state
         */
        async fetchData(url) {
            this.isLoading = true;
            this.error = null;

            try {
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                this.data = await response.json();
            } catch (error) {
                this.error = error.message;
                console.error('Fetch error:', error);
            } finally {
                this.isLoading = false;
            }
        }
    };
}
```

### 2. 🔔 Notifications

```javascript
Alpine.store('notification', {
    items: [],
    nextId: 1,

    /**
     * แสดงการแจ้งเตือน
     */
    show({ message, type = 'info', duration = 3000 }) {
        const id = this.nextId++;

        this.items.push({ id, message, type });

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
        }
    },

    /**
     * ลบการแจ้งเตือน
     */
    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
    },

    // Shortcuts
    success(message) {
        this.show({ message, type: 'success' });
    },

    error(message) {
        this.show({ message, type: 'error' });
    },

    warning(message) {
        this.show({ message, type: 'warning' });
    },

    info(message) {
        this.show({ message, type: 'info' });
    }
});
```

---

## Anti-Patterns (ห้ามทำ)

### ❌ สิ่งที่ไม่ควรทำ

```blade
{{-- ❌ ห้าม: ใช้ jQuery ร่วมกับ Alpine --}}
<button @click="$('#modal').show()">Open</button>

{{-- ✅ ควร: ใช้ Alpine pure --}}
<button @click="isOpen = true">Open</button>

{{-- ❌ ห้าม: Complex logic ใน template --}}
<div x-text="items.filter(i => i.status === 'active').sort((a, b) => a.name.localeCompare(b.name)).slice(0, 10).map(i => i.name).join(', ')"></div>

{{-- ✅ ควร: ใช้ computed property --}}
<div x-text="topActiveItems"></div>

{{-- ❌ ห้าม: Mutate props directly --}}
<div x-data="{ item: { name: 'test' } }">
    <button @click="item = { name: 'new' }">Change</button>
</div>

{{-- ✅ ควร: Use methods to update --}}
<div x-data="component()">
    <button @click="updateItem('new')">Change</button>
</div>

{{-- ❌ ห้าม: ไม่ใช้ x-cloak --}}
<div x-show="isVisible">Content</div> <!-- จะเห็น flash -->

{{-- ✅ ควร: ใช้ x-cloak --}}
<div x-show="isVisible" x-cloak>Content</div>
```

---

## 📚 สรุป

### ✅ Best Practices Checklist

- [x] ใช้ Component Pattern แทน inline `x-data`
- [x] แยก logic ออกจาก template
- [x] ใช้ Stores สำหรับ global state
- [x] ใช้ `x-cloak` เสมอ
- [x] Debounce/Throttle events ที่เรียกบ่อย
- [x] ใช้ `x-if` สำหรับ complex components
- [x] ใช้ `x-show` สำหรับ simple toggles
- [x] คอมเม้นต์ภาษาไทย พร้อม @example และ @tip
- [x] Export components เป็น global functions
- [x] ทดสอบ performance

---

**สร้างโดย**: Development Team
**สำหรับ**: Thaiprompt-Affiliate V3
**Alpine.js Documentation**: https://alpinejs.dev/

*"Keep it simple, make it work" - Alpine.js Philosophy*

import Alpine from 'alpinejs';

/**
 * Sidebar Store V3 - จัดการ sidebar state แบบรวมศูนย์
 *
 * รองรับ 2 โหมด:
 * - Mobile (< 768px): Drawer overlay (เปิด/ปิดเท่านั้น)
 * - Desktop (>= 768px): Auto-hide mode (hover เพื่อขยาย)
 *
 * @example
 * // Toggle auto-hide mode (desktop)
 * $store.sidebar.toggleAutoHide()
 *
 * // Toggle sidebar (mobile drawer)
 * $store.sidebar.toggle()
 *
 * // Check states
 * if ($store.sidebar.isOpen) { ... }
 * if ($store.sidebar.autoHideMode) { ... }
 * if ($store.sidebar.hovered) { ... }
 *
 * // Scroll to active menu item
 * $store.sidebar.scrollToActiveMenu()
 *
 * @tip State เดียวสำหรับทั้ง mobile และ desktop
 */
Alpine.store('sidebar', {
    // ========================================
    // State Variables
    // ========================================

    /** @type {boolean} เปิด/ปิด sidebar (mobile drawer / desktop toggle) */
    isOpen: true,

    /** @type {boolean} โหมด Auto-hide (desktop เท่านั้น) */
    autoHideMode: false,

    /** @type {boolean} กำลัง hover อยู่หรือไม่ (desktop auto-hide mode) */
    hovered: false,

    /** @type {boolean} อยู่บน desktop หรือไม่ (>= 768px) - reactive state */
    _isDesktop: true,

    /** @type {string|null} Element ref สำหรับ nav container */
    _navElement: null,

    // ========================================
    // Computed Properties (ใช้ function แทน getter เพื่อ reactivity)
    // ========================================

    /**
     * ตรวจสอบว่าอยู่บน desktop หรือไม่
     */
    get isDesktop() {
        return this._isDesktop;
    },

    /**
     * ตรวจสอบว่า sidebar ควรแสดงหรือไม่
     */
    get shouldShow() {
        // Mobile: แสดงตาม isOpen
        if (!this._isDesktop) {
            return this.isOpen;
        }

        // Desktop: แสดงเสมอ (แค่เปลี่ยน width)
        return true;
    },

    /**
     * ตรวจสอบว่า sidebar ควรขยายหรือไม่
     */
    get shouldExpand() {
        // Mobile: ไม่มี auto-hide
        if (!this._isDesktop) {
            return this.isOpen;
        }

        // Desktop auto-hide mode: ขยายเมื่อ hover หรือ isOpen
        if (this.autoHideMode) {
            return this.hovered || this.isOpen;
        }

        // Desktop ปกติ: ขยายตาม isOpen
        return this.isOpen;
    },

    /**
     * Width ของ sidebar (px)
     */
    get sidebarWidth() {
        // Mobile: Fixed 256px
        if (!this._isDesktop) {
            return 256;
        }

        // Desktop auto-hide mode แบบย่อ: 80px
        if (this.autoHideMode && !this.shouldExpand) {
            return 80;
        }

        // Desktop ขยาย: ใช้จาก theme variable หรือ default 260px
        const themeWidth = getComputedStyle(document.documentElement)
            .getPropertyValue('--arrow-x-sidebar-width').trim();
        return themeWidth ? parseInt(themeWidth) : 260;
    },

    // ========================================
    // Lifecycle Methods
    // ========================================

    /**
     * เริ่มต้น sidebar store
     */
    init() {
        // ตั้งค่า initial desktop state
        this._isDesktop = window.innerWidth >= 768;

        // โหลดค่า autoHideMode จาก localStorage
        const savedAutoHide = localStorage.getItem('sidebarAutoHide');
        this.autoHideMode = savedAutoHide === 'true';

        // ตั้งค่า initial state ตาม screen size
        this.updateBasedOnScreenSize();

        // Listen to resize events
        window.addEventListener('resize', () => {
            const wasDesktop = this._isDesktop;
            this._isDesktop = window.innerWidth >= 768;

            // เรียก update เฉพาะเมื่อเปลี่ยนจาก mobile <-> desktop
            if (wasDesktop !== this._isDesktop) {
                this.updateBasedOnScreenSize();
            }
        });

        // Expose to window for backward compatibility
        window.sidebarOpen = this.isOpen;
    },

    // ========================================
    // Public Methods
    // ========================================

    /**
     * Toggle sidebar (mobile drawer)
     */
    toggle() {
        this.isOpen = !this.isOpen;
        this.syncToWindow();
    },

    /**
     * เปิด sidebar
     */
    open() {
        this.isOpen = true;
        this.syncToWindow();
    },

    /**
     * ปิด sidebar
     */
    close() {
        this.isOpen = false;
        this.syncToWindow();
    },

    /**
     * Toggle auto-hide mode (desktop)
     */
    toggleAutoHide() {
        this.autoHideMode = !this.autoHideMode;

        // บันทึกลง localStorage
        localStorage.setItem('sidebarAutoHide', this.autoHideMode.toString());

        // ปรับ isOpen ตาม mode (desktop เท่านั้น)
        if (this._isDesktop) {
            if (this.autoHideMode) {
                // เปิด auto-hide: ย่อ sidebar
                this.isOpen = false;
                this.hovered = false;
            } else {
                // ปิด auto-hide: ขยาย sidebar
                this.isOpen = true;
            }
            this.syncToWindow();
        }
    },

    /**
     * Set hover state (desktop auto-hide mode)
     */
    setHovered(value) {
        // ใช้ได้เฉพาะ desktop + auto-hide mode
        if (this._isDesktop && this.autoHideMode) {
            this.hovered = value;
        }
    },

    /**
     * ปิด sidebar เมื่อคลิก menu item (desktop auto-hide mode)
     */
    closeOnMenuClick() {
        // ใช้ได้เฉพาะ desktop + auto-hide mode + hovered
        if (this._isDesktop && this.autoHideMode && this.hovered) {
            this.hovered = false;
        }
    },

    /**
     * ตั้งค่า nav element reference สำหรับ scroll
     * @param {HTMLElement} el - Nav container element
     */
    setNavElement(el) {
        this._navElement = el;
    },

    /**
     * Scroll ไปยังเมนูที่ active
     * V8: ใช้วิธีง่ายๆ - หา active element ที่เฉพาะเจาะจงที่สุดแล้ว scroll ไปเลย
     */
    scrollToActiveMenu() {
        // รอให้ DOM render และ submenu expand เสร็จก่อน (500ms)
        setTimeout(() => {
            this._performScroll();
        }, 500);
    },

    /**
     * ทำการ scroll ไปยัง active element
     * Priority: submenu > item > parent
     */
    _performScroll() {
        const nav = this._navElement || document.querySelector('[data-sidebar-nav]');
        if (!nav) {
            console.warn('[Sidebar] ไม่พบ nav element');
            return;
        }

        // หา active element ตามลำดับความสำคัญ:
        // 1. Submenu item (เฉพาะเจาะจงที่สุด - สำหรับเมนูที่มี submenu)
        // 2. Single menu item (เมนูเดี่ยวไม่มี submenu)
        // 3. Parent menu (fallback)
        let activeElement = nav.querySelector('[data-menu-active="true"][data-menu-type="submenu"]');

        if (!activeElement) {
            activeElement = nav.querySelector('[data-menu-active="true"][data-menu-type="item"]');
        }

        if (!activeElement) {
            activeElement = nav.querySelector('[data-menu-active="true"][data-menu-type="parent"]');
        }

        // Fallback: หาจาก CSS class (สำหรับ hardcoded menus ที่ไม่มี data attributes)
        if (!activeElement) {
            const allLinks = nav.querySelectorAll('a');
            for (const link of allLinks) {
                if (link.classList.contains('font-bold') || link.className.includes('bg-white/30')) {
                    activeElement = link;
                    break;
                }
            }
        }

        if (!activeElement) {
            return;
        }

        // Scroll ไปที่ element
        this._scrollToElement(nav, activeElement);
    },

    /**
     * Helper: Scroll nav container ไปที่ element ที่กำหนด
     * ให้ element อยู่ที่ 1/3 จากบนของ nav viewport
     */
    _scrollToElement(nav, element) {
        const navRect = nav.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();
        const currentScrollTop = nav.scrollTop;

        // คำนวณตำแหน่งของ element เทียบกับ nav (รวม scroll ปัจจุบัน)
        const elementTopRelativeToNav = elementRect.top - navRect.top + currentScrollTop;

        // Scroll ให้ element อยู่ 1/3 จากบนของ nav viewport
        const targetScrollTop = elementTopRelativeToNav - (nav.clientHeight / 3);

        nav.scrollTo({
            top: Math.max(0, targetScrollTop),
            behavior: 'smooth'
        });
    },

    /**
     * ขยาย parent menu ที่มี active submenu item
     * ใช้สำหรับ initialize menu state ตอนโหลดหน้า
     */
    expandActiveParentMenus() {
        // ฟังก์ชันนี้จะถูกเรียกจาก component
        // เพื่อให้ submenu ที่มี active item เปิดออกอัตโนมัติ
        const event = new CustomEvent('expand-active-menus');
        document.dispatchEvent(event);
    },

    // ========================================
    // Private Methods
    // ========================================

    /**
     * ปรับ sidebar state ตาม screen size
     */
    updateBasedOnScreenSize() {
        if (this._isDesktop) {
            // Desktop: เปิด sidebar (เว้นแต่อยู่ใน auto-hide mode)
            if (!this.autoHideMode) {
                this.isOpen = true;
            }
            this.hovered = false;
        } else {
            // Mobile: ปิด sidebar
            this.isOpen = false;
            this.autoHideMode = false; // ไม่ใช้ auto-hide บน mobile
            this.hovered = false;
        }
        this.syncToWindow();
    },

    /**
     * Sync state ไปยัง window.sidebarOpen (backward compatibility)
     */
    syncToWindow() {
        window.sidebarOpen = this.isOpen;
    }
});

export default Alpine.store('sidebar');

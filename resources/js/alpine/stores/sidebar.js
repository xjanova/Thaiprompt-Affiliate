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
     * ใช้ 2-step approach:
     * 1. Scroll ไปที่ parent menu ก่อน (เพื่อให้ submenu มองเห็นและ expand)
     * 2. รอ animation เสร็จ แล้วค่อย scroll ไปที่ submenu item
     */
    scrollToActiveMenu() {
        console.log('[Sidebar] scrollToActiveMenu() called, รอ 300ms ก่อน...');

        // รอให้ DOM render เสร็จก่อน (เพิ่มเป็น 300ms)
        setTimeout(() => {
            console.log('[Sidebar] เริ่ม _scrollStep1_ToParent()...');
            this._scrollStep1_ToParent();
        }, 300);
    },

    /**
     * Step 1: Scroll ไปที่ parent menu หรือ menu item ที่ active ก่อน
     * เมนูหลักจะมองเห็นเสมอ (ไม่อยู่ใน collapsed area)
     */
    _scrollStep1_ToParent() {
        const nav = this._navElement || document.querySelector('[data-sidebar-nav]');
        if (!nav) {
            console.warn('[Sidebar] ไม่พบ nav element');
            return;
        }

        console.log('[Sidebar] Step 1: เริ่มค้นหา active menu...');

        // Debug: ตรวจสอบว่า nav สามารถ scroll ได้หรือไม่
        const canScroll = nav.scrollHeight > nav.clientHeight;
        console.log('[Sidebar] Nav scrollable:', canScroll, '| scrollHeight:', nav.scrollHeight, '| clientHeight:', nav.clientHeight);

        // Debug: แสดงจำนวน elements ทั้งหมดที่มี data-menu-type
        const allMenuItems = nav.querySelectorAll('[data-menu-type]');
        console.log('[Sidebar] พบ menu items ทั้งหมด (มี data-menu-type):', allMenuItems.length);

        // Debug: แสดงจำนวน elements ที่มี data-menu-active
        const allActiveItems = nav.querySelectorAll('[data-menu-active="true"]');
        console.log('[Sidebar] พบ active items ทั้งหมด:', allActiveItems.length);
        allActiveItems.forEach((item, i) => {
            const type = item.getAttribute('data-menu-type');
            const key = item.getAttribute('data-menu-key') || item.textContent?.trim().substring(0, 30);
            console.log(`[Sidebar] Active item ${i + 1}: type="${type}", key="${key}"`);
        });

        // หา active element - ลำดับความสำคัญ:
        // 1. Parent menu (มี submenu)
        // 2. Single menu item (ไม่มี submenu)
        let activeMainMenu = nav.querySelector('[data-menu-active="true"][data-menu-type="parent"]');

        if (!activeMainMenu) {
            // หาเมนูหลักที่ไม่มี submenu (type="item")
            activeMainMenu = nav.querySelector('[data-menu-active="true"][data-menu-type="item"]');
            if (activeMainMenu) {
                console.log('[Sidebar] พบ single menu item (type="item")');
            }
        } else {
            console.log('[Sidebar] พบ parent menu (type="parent")');
        }

        if (activeMainMenu) {
            const menuType = activeMainMenu.getAttribute('data-menu-type');
            const menuKey = activeMainMenu.getAttribute('data-menu-key');
            console.log('[Sidebar] จะ scroll ไปที่:', menuType, menuKey);

            // Scroll ไปที่ element เสมอ (ไม่ต้องเช็ค viewport)
            this._scrollToElement(nav, activeMainMenu);
            console.log('[Sidebar] Step 1: Scrolled to main menu, type:', menuType);

            // ถ้าเป็น parent menu (มี submenu) → รอ submenu expand แล้วค่อย scroll ไปที่ submenu item
            if (menuType === 'parent') {
                setTimeout(() => {
                    this._scrollStep2_ToSubmenuItem(nav);
                }, 400); // รอ x-collapse animation เสร็จ
            }
            // ถ้าเป็น single item (type="item") → จบเลย ไม่ต้องหา submenu
        } else {
            console.log('[Sidebar] ไม่พบ active main menu → ลองหา submenu item');
            // ไม่มี main menu ที่ active → ลองหา submenu item โดยตรง
            this._scrollStep2_ToSubmenuItem(nav);
        }
    },

    /**
     * Step 2: Scroll ไปที่ active element (submenu item, parent, หรือ single item)
     * เรียกหลังจาก submenu expand แล้ว (ถ้ามี submenu)
     */
    _scrollStep2_ToSubmenuItem(nav, retryCount = 0) {
        const maxRetries = 8;

        // หา active element - ลำดับความสำคัญ:
        // 1. Submenu item (ในเมนูที่มี submenu)
        // 2. Single menu item (เมนูหลักที่ไม่มี submenu)
        // 3. Parent menu (fallback)
        let activeElement = nav.querySelector('[data-menu-active="true"][data-menu-type="submenu"]');

        // Fallback 1: หา single menu item
        if (!activeElement) {
            activeElement = nav.querySelector('[data-menu-active="true"][data-menu-type="item"]');
        }

        // Fallback 2: หาจาก CSS class (สำหรับ hardcoded menus)
        if (!activeElement) {
            const allAnchors = nav.querySelectorAll('a');
            for (const anchor of allAnchors) {
                if (anchor.classList.contains('font-bold') && anchor.className.includes('bg-white/30')) {
                    activeElement = anchor;
                    break;
                }
            }
        }

        // Fallback 3: หา parent menu
        if (!activeElement) {
            activeElement = nav.querySelector('[data-menu-active="true"][data-menu-type="parent"]');
        }

        if (!activeElement) {
            if (retryCount < maxRetries) {
                setTimeout(() => this._scrollStep2_ToSubmenuItem(nav, retryCount + 1), 150);
            }
            return;
        }

        // ตรวจสอบว่า element มองเห็นได้ (height > 0)
        const rect = activeElement.getBoundingClientRect();
        if (rect.height === 0 && retryCount < maxRetries) {
            setTimeout(() => this._scrollStep2_ToSubmenuItem(nav, retryCount + 1), 150);
            return;
        }

        // Scroll ไปที่ element
        this._scrollToElement(nav, activeElement);
        console.log('[Sidebar] Step 2: Scrolled to:', activeElement.textContent?.trim().substring(0, 30));
    },

    /**
     * Helper: Scroll nav container ไปที่ element ที่กำหนด
     * ให้ element อยู่ที่ 1/3 จากบนของ nav viewport
     */
    _scrollToElement(nav, element) {
        const navRect = nav.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();
        const currentScrollTop = nav.scrollTop;

        console.log('[Sidebar] _scrollToElement called');
        console.log('[Sidebar] nav.scrollTop:', currentScrollTop);
        console.log('[Sidebar] nav.clientHeight:', nav.clientHeight);
        console.log('[Sidebar] nav.scrollHeight:', nav.scrollHeight);
        console.log('[Sidebar] element rect:', elementRect.top, elementRect.bottom, 'height:', elementRect.height);

        // คำนวณตำแหน่งของ element เทียบกับ nav (รวม scroll ปัจจุบัน)
        const elementTopRelativeToNav = elementRect.top - navRect.top + currentScrollTop;

        // Scroll ให้ element อยู่ 1/3 จากบนของ nav viewport
        const targetScrollTop = elementTopRelativeToNav - (nav.clientHeight / 3);

        console.log('[Sidebar] elementTopRelativeToNav:', elementTopRelativeToNav);
        console.log('[Sidebar] targetScrollTop:', targetScrollTop);
        console.log('[Sidebar] จะ scroll ไปที่:', Math.max(0, targetScrollTop));

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

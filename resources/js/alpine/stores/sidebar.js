import Alpine from 'alpinejs';

/**
 * Sidebar Store - จัดการ sidebar state
 *
 * @example
 * // Toggle sidebar
 * $store.sidebar.toggle()
 *
 * // Check if open
 * if ($store.sidebar.isOpen) { ... }
 *
 * @tip ใช้ store นี้แทนการใช้ local state ใน sidebar component
 */
Alpine.store('sidebar', {
    // State
    isOpen: true,           // เปิด/ปิด sidebar (mobile)
    isCollapsed: false,     // ย่อ/ขยาย sidebar (desktop)

    /**
     * เริ่มต้น sidebar store
     */
    init() {
        // โหลดค่าจาก localStorage (เฉพาะ desktop)
        if (window.innerWidth >= 1024) {
            const savedCollapsed = localStorage.getItem('sidebarCollapsed');
            this.isCollapsed = savedCollapsed === 'true';
        }

        // ปรับตาม screen size
        this.updateBasedOnScreenSize();

        // Listen to resize
        window.addEventListener('resize', () => {
            this.updateBasedOnScreenSize();
        });
    },

    /**
     * Toggle sidebar (mobile)
     */
    toggle() {
        this.isOpen = !this.isOpen;
    },

    /**
     * Toggle collapsed (desktop)
     */
    toggleCollapse() {
        this.isCollapsed = !this.isCollapsed;
        this.saveCollapsed();
    },

    /**
     * เปิด sidebar
     */
    open() {
        this.isOpen = true;
    },

    /**
     * ปิด sidebar
     */
    close() {
        this.isOpen = false;
    },

    /**
     * ขยาย sidebar (desktop)
     */
    expand() {
        this.isCollapsed = false;
        this.saveCollapsed();
    },

    /**
     * ย่อ sidebar (desktop)
     */
    collapse() {
        this.isCollapsed = true;
        this.saveCollapsed();
    },

    /**
     * ปรับ sidebar ตาม screen size
     */
    updateBasedOnScreenSize() {
        if (window.innerWidth < 1024) {
            // Mobile/Tablet: ปิด sidebar
            this.isOpen = false;
            this.isCollapsed = false;
        } else {
            // Desktop: เปิด sidebar (ใช้ค่าจาก localStorage)
            this.isOpen = true;
        }
    },

    /**
     * บันทึก collapsed state
     */
    saveCollapsed() {
        localStorage.setItem('sidebarCollapsed', this.isCollapsed.toString());
    }
});

export default Alpine.store('sidebar');

import Alpine from 'alpinejs';

/**
 * Pinned Menus Store - จัดการเมนูที่ปักหมุดไว้
 *
 * รองรับการปักหมุดเมนูที่ใช้บ่อยไว้ด้านบนสุดของ sidebar
 * เก็บข้อมูลใน localStorage แยกตาม dashboard type (admin, user, seller)
 *
 * @example
 * // ปักหมุดเมนู
 * $store.pinnedMenus.pin('admin', 'admin.dashboard', { label: 'แดชบอร์ด', icon: '📊', url: '/admin' })
 *
 * // ยกเลิกปักหมุด
 * $store.pinnedMenus.unpin('admin', 'admin.dashboard')
 *
 * // ตรวจสอบว่าปักหมุดไว้หรือไม่
 * $store.pinnedMenus.isPinned('admin', 'admin.dashboard')
 *
 * // ดึงรายการเมนูที่ปักหมุด
 * $store.pinnedMenus.getPinnedMenus('admin')
 *
 * @tip เก็บแยก localStorage key ตาม dashboard type
 */
Alpine.store('pinnedMenus', {
    // ========================================
    // State Variables
    // ========================================

    /**
     * @type {Object} เก็บรายการเมนูที่ปักหมุดไว้ตาม dashboard type
     * รูปแบบ: { admin: [...], user: [...], seller: [...] }
     */
    pinned: {
        admin: [],
        user: [],
        seller: []
    },

    /**
     * @type {number} จำนวนเมนูสูงสุดที่ปักหมุดได้
     */
    maxPinned: 10,

    // ========================================
    // Lifecycle Methods
    // ========================================

    /**
     * เริ่มต้น store - โหลดข้อมูลจาก localStorage
     */
    init() {
        this.loadFromStorage();
    },

    // ========================================
    // Public Methods
    // ========================================

    /**
     * ปักหมุดเมนู
     *
     * @param {string} dashboardType ประเภท dashboard (admin, user, seller)
     * @param {string} menuKey รหัสเมนู (unique identifier)
     * @param {Object} menuData ข้อมูลเมนู { label, icon, url, route? }
     * @returns {boolean} สำเร็จหรือไม่
     */
    pin(dashboardType, menuKey, menuData) {
        // ตรวจสอบว่า dashboard type ถูกต้อง
        if (!this.pinned[dashboardType]) {
            console.warn(`Invalid dashboard type: ${dashboardType}`);
            return false;
        }

        // ตรวจสอบว่าปักหมุดไว้แล้วหรือยัง
        if (this.isPinned(dashboardType, menuKey)) {
            console.info(`Menu ${menuKey} is already pinned`);
            return false;
        }

        // ตรวจสอบจำนวนสูงสุด
        if (this.pinned[dashboardType].length >= this.maxPinned) {
            console.warn(`Maximum pinned menus (${this.maxPinned}) reached for ${dashboardType}`);
            return false;
        }

        // เพิ่มเมนูใหม่
        const pinnedItem = {
            key: menuKey,
            label: menuData.label || '',
            icon: menuData.icon || '',
            url: menuData.url || '',
            route: menuData.route || null,
            pinnedAt: Date.now()
        };

        this.pinned[dashboardType].push(pinnedItem);
        this.saveToStorage();

        // Dispatch event สำหรับ UI update
        window.dispatchEvent(new CustomEvent('menu-pinned', {
            detail: { dashboardType, menuKey, menuData: pinnedItem }
        }));

        return true;
    },

    /**
     * ยกเลิกปักหมุดเมนู
     *
     * @param {string} dashboardType ประเภท dashboard
     * @param {string} menuKey รหัสเมนู
     * @returns {boolean} สำเร็จหรือไม่
     */
    unpin(dashboardType, menuKey) {
        if (!this.pinned[dashboardType]) {
            return false;
        }

        const index = this.pinned[dashboardType].findIndex(item => item.key === menuKey);
        if (index === -1) {
            return false;
        }

        this.pinned[dashboardType].splice(index, 1);
        this.saveToStorage();

        // Dispatch event สำหรับ UI update
        window.dispatchEvent(new CustomEvent('menu-unpinned', {
            detail: { dashboardType, menuKey }
        }));

        return true;
    },

    /**
     * สลับสถานะปักหมุด
     *
     * @param {string} dashboardType ประเภท dashboard
     * @param {string} menuKey รหัสเมนู
     * @param {Object} menuData ข้อมูลเมนู (ใช้เมื่อปักหมุด)
     * @returns {boolean} สถานะหลังสลับ (true = pinned, false = unpinned)
     */
    toggle(dashboardType, menuKey, menuData = {}) {
        if (this.isPinned(dashboardType, menuKey)) {
            this.unpin(dashboardType, menuKey);
            return false;
        } else {
            this.pin(dashboardType, menuKey, menuData);
            return true;
        }
    },

    /**
     * ตรวจสอบว่าเมนูถูกปักหมุดไว้หรือไม่
     *
     * @param {string} dashboardType ประเภท dashboard
     * @param {string} menuKey รหัสเมนู
     * @returns {boolean}
     */
    isPinned(dashboardType, menuKey) {
        if (!this.pinned[dashboardType]) {
            return false;
        }
        return this.pinned[dashboardType].some(item => item.key === menuKey);
    },

    /**
     * ดึงรายการเมนูที่ปักหมุดไว้
     *
     * @param {string} dashboardType ประเภท dashboard
     * @returns {Array} รายการเมนูที่ปักหมุด
     */
    getPinnedMenus(dashboardType) {
        return this.pinned[dashboardType] || [];
    },

    /**
     * ดึงจำนวนเมนูที่ปักหมุดไว้
     *
     * @param {string} dashboardType ประเภท dashboard
     * @returns {number}
     */
    getPinnedCount(dashboardType) {
        return this.pinned[dashboardType]?.length || 0;
    },

    /**
     * เปลี่ยนลำดับเมนูที่ปักหมุด
     *
     * @param {string} dashboardType ประเภท dashboard
     * @param {number} fromIndex ตำแหน่งเดิม
     * @param {number} toIndex ตำแหน่งใหม่
     */
    reorder(dashboardType, fromIndex, toIndex) {
        if (!this.pinned[dashboardType]) {
            return;
        }

        const items = this.pinned[dashboardType];
        if (fromIndex < 0 || fromIndex >= items.length ||
            toIndex < 0 || toIndex >= items.length) {
            return;
        }

        const [item] = items.splice(fromIndex, 1);
        items.splice(toIndex, 0, item);
        this.saveToStorage();
    },

    /**
     * ล้างเมนูที่ปักหมุดทั้งหมด
     *
     * @param {string} dashboardType ประเภท dashboard
     */
    clearAll(dashboardType) {
        if (this.pinned[dashboardType]) {
            this.pinned[dashboardType] = [];
            this.saveToStorage();

            window.dispatchEvent(new CustomEvent('menu-pins-cleared', {
                detail: { dashboardType }
            }));
        }
    },

    // ========================================
    // Private Methods
    // ========================================

    /**
     * โหลดข้อมูลจาก localStorage
     */
    loadFromStorage() {
        try {
            const storageKey = 'pinnedMenus';
            const stored = localStorage.getItem(storageKey);

            if (stored) {
                const parsed = JSON.parse(stored);

                // Merge กับ default structure
                this.pinned = {
                    admin: parsed.admin || [],
                    user: parsed.user || [],
                    seller: parsed.seller || []
                };
            }
        } catch (error) {
            console.error('Error loading pinned menus from storage:', error);
            // Reset เป็นค่าเริ่มต้น
            this.pinned = { admin: [], user: [], seller: [] };
        }
    },

    /**
     * บันทึกข้อมูลลง localStorage
     */
    saveToStorage() {
        try {
            const storageKey = 'pinnedMenus';
            localStorage.setItem(storageKey, JSON.stringify(this.pinned));
        } catch (error) {
            console.error('Error saving pinned menus to storage:', error);
        }
    }
});

export default Alpine.store('pinnedMenus');

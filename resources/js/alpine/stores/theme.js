import Alpine from 'alpinejs';

/**
 * Theme Store - จัดการ dark mode และ theme settings
 *
 * @example
 * // Toggle dark mode
 * $store.theme.toggle()
 *
 * // Check if dark
 * if ($store.theme.isDark) { ... }
 *
 * @tip ใช้ store นี้สำหรับ dark mode ทั่วทั้งแอพ แทนการใช้ local state
 */
Alpine.store('theme', {
    // State
    isDark: false,

    /**
     * เริ่มต้น theme store
     */
    init() {
        // โหลดค่าจาก localStorage
        const saved = localStorage.getItem('darkMode');
        this.isDark = saved === 'true';

        // Apply ทันที
        this.apply();
    },

    /**
     * Toggle dark mode
     */
    toggle() {
        this.isDark = !this.isDark;
        this.save();
        this.apply();

        // Dispatch event สำหรับ components อื่นๆ
        window.dispatchEvent(new CustomEvent('theme-changed', {
            detail: { isDark: this.isDark }
        }));
    },

    /**
     * เปิด dark mode
     */
    enable() {
        this.isDark = true;
        this.save();
        this.apply();
    },

    /**
     * ปิด dark mode
     */
    disable() {
        this.isDark = false;
        this.save();
        this.apply();
    },

    /**
     * Apply dark mode ให้กับ document
     */
    apply() {
        if (this.isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },

    /**
     * บันทึกลง localStorage
     */
    save() {
        localStorage.setItem('darkMode', this.isDark.toString());
    }
});

export default Alpine.store('theme');

import Alpine from 'alpinejs';

/**
 * Language Store - จัดการการเปลี่ยนภาษา
 *
 * @example
 * // เปลี่ยนภาษา
 * $store.language.change('en')
 *
 * // ตรวจสอบภาษาปัจจุบัน
 * if ($store.language.current === 'th') { ... }
 *
 * @tip ใช้ store นี้สำหรับการเปลี่ยนภาษาทั่วทั้งแอพ
 */
Alpine.store('language', {
    // State
    current: document.documentElement.lang || 'th',
    available: [
        { code: 'th', name: 'ไทย', flag: '🇹🇭' },
        { code: 'en', name: 'English', flag: '🇬🇧' },
    ],

    /**
     * เริ่มต้น language store
     */
    init() {
        // โหลดค่าจาก localStorage
        const saved = localStorage.getItem('language');
        if (saved && this.available.find(l => l.code === saved)) {
            this.current = saved;
        }

        // Apply ทันที
        this.apply();
    },

    /**
     * เปลี่ยนภาษา
     *
     * @param {string} langCode รหัสภาษา (th, en)
     */
    change(langCode) {
        if (!this.available.find(l => l.code === langCode)) {
            console.error(`Language ${langCode} not supported`);
            return;
        }

        this.current = langCode;
        this.save();
        this.apply();

        // รีเฟรชหน้าเพื่อโหลดภาษาใหม่
        window.location.href = this.getLanguageUrl(langCode);
    },

    /**
     * สร้าง URL สำหรับเปลี่ยนภาษา
     *
     * @param {string} langCode รหัสภาษา
     * @return {string} URL พร้อมพารามิเตอร์ language
     */
    getLanguageUrl(langCode) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', langCode);
        return url.toString();
    },

    /**
     * ดึงข้อมูลภาษาปัจจุบัน
     *
     * @return {object} ข้อมูลภาษา
     */
    getCurrentLanguage() {
        return this.available.find(l => l.code === this.current) || this.available[0];
    },

    /**
     * Apply language ให้กับ document
     */
    apply() {
        document.documentElement.lang = this.current;
    },

    /**
     * บันทึกลง localStorage
     */
    save() {
        localStorage.setItem('language', this.current);
    }
});

export default Alpine.store('language');

import Alpine from 'alpinejs';

/**
 * Language Store - Simple Language Selector (ไม่แปลอัตโนมัติ)
 *
 * Features:
 * - เลือกภาษาได้ แต่ไม่แปลอัตโนมัติ
 * - บันทึก preference ลง localStorage
 * - แสดงธงภาษา
 */
Alpine.store('language', {
    // สถานะปัจจุบัน
    current: 'th',
    isTranslating: false,

    // ภาษาที่รองรับ
    languages: [
        { code: 'th', name: 'ไทย', flag: '🇹🇭', nativeName: 'ภาษาไทย' },
        { code: 'en', name: 'English', flag: '🇺🇸', nativeName: 'English' },
        { code: 'zh-CN', name: 'Chinese', flag: '🇨🇳', nativeName: '中文' },
        { code: 'ja', name: 'Japanese', flag: '🇯🇵', nativeName: '日本語' },
        { code: 'ko', name: 'Korean', flag: '🇰🇷', nativeName: '한국어' },
        { code: 'vi', name: 'Vietnamese', flag: '🇻🇳', nativeName: 'Tiếng Việt' },
        { code: 'de', name: 'German', flag: '🇩🇪', nativeName: 'Deutsch' },
        { code: 'fr', name: 'French', flag: '🇫🇷', nativeName: 'Français' },
        { code: 'es', name: 'Spanish', flag: '🇪🇸', nativeName: 'Español' },
    ],

    /**
     * เริ่มต้น language store
     */
    init() {
        // โหลดภาษาจาก localStorage
        const savedLang = localStorage.getItem('app_language');
        if (savedLang && this.languages.find(l => l.code === savedLang)) {
            this.current = savedLang;
        } else {
            // Default เป็นภาษาไทยเสมอ
            this.current = 'th';
            localStorage.setItem('app_language', 'th');
        }

        console.log('🌐 Language Store initialized (ไม่มีการแปลอัตโนมัติ):', this.current);
    },

    /**
     * เปลี่ยนภาษา (ไม่แปล - เพียงแค่บันทึก preference)
     */
    setLanguage(langCode) {
        console.log('🌐 เปลี่ยนภาษาเป็น:', langCode, '(ไม่มีการแปลอัตโนมัติ)');

        this.current = langCode;
        localStorage.setItem('app_language', langCode);
    },

    /**
     * ดึงข้อมูลภาษาปัจจุบัน
     */
    getCurrentLanguage() {
        return this.languages.find(l => l.code === this.current) || this.languages[0];
    },

    /**
     * Clear cache
     */
    clearCache() {
        localStorage.removeItem('app_language');
        this.current = 'th';
    }
});

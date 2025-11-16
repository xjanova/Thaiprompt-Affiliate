import Alpine from 'alpinejs';

/**
 * Language Store - Alpine.js Store สำหรับจัดการภาษาด้วย Google Translate Widget (ฟรี 100%)
 *
 * Features:
 * - ใช้ Google Translate Widget ฟรี ไม่ต้องใช้ API key
 * - แปลทั้งหน้าอัตโนมัติ
 * - รองรับ Chrome, Edge, Firefox, Safari
 * - บันทึก preference ลง localStorage
 * - ไม่มีค่าใช้จ่าย ไม่จำกัดการใช้งาน
 *
 * @example
 * // เปลี่ยนภาษา
 * $store.language.setLanguage('en')
 */
Alpine.store('language', {
    // สถานะปัจจุบัน
    current: 'th',
    isTranslating: false,
    isBrowserSupported: true,

    // ภาษาที่รองรับ (ตาม Google Translate)
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

        console.log('🌐 Language Store initialized (Google Translate Widget - ฟรี 100%):', this.current);

        // ตรวจสอบบราวเซอร์
        this.checkBrowserSupport();

        // โหลด Google Translate Widget
        this.loadGoogleTranslateWidget();

        // ถ้าไม่ใช่ภาษาไทย ให้แปลหน้าอัตโนมัติ
        if (this.current !== 'th') {
            setTimeout(() => this.translatePage(), 1500);
        }
    },

    /**
     * ตรวจสอบว่าบราวเซอร์รองรับการแปลหรือไม่
     */
    checkBrowserSupport() {
        const userAgent = navigator.userAgent.toLowerCase();
        const isChrome = /chrome/.test(userAgent) && !/edge/.test(userAgent);
        const isEdge = /edg/.test(userAgent);
        const isFirefox = /firefox/.test(userAgent);
        const isSafari = /safari/.test(userAgent) && !/chrome/.test(userAgent);

        // Google Translate Widget รองรับทุกบราวเซอร์
        this.isBrowserSupported = true;
        this.browserName = isChrome ? 'Chrome' : isEdge ? 'Edge' : isFirefox ? 'Firefox' : isSafari ? 'Safari' : 'Unknown';

        console.log('🌐 Browser:', this.browserName, '- Google Translate Widget รองรับทุกบราวเซอร์');
    },

    /**
     * โหลด Google Translate Widget Script (ฟรี!)
     */
    loadGoogleTranslateWidget() {
        // ตรวจสอบว่าโหลดแล้วหรือยัง
        if (window.google?.translate) {
            console.log('✅ Google Translate Widget โหลดแล้ว');
            return;
        }

        // สร้าง div สำหรับ widget (ซ่อนไว้)
        if (!document.getElementById('google_translate_element')) {
            const div = document.createElement('div');
            div.id = 'google_translate_element';
            div.style.display = 'none';
            document.body.appendChild(div);
        }

        // โหลด Google Translate Widget Script
        const script = document.createElement('script');
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.head.appendChild(script);

        // Callback function สำหรับ Google Translate
        window.googleTranslateElementInit = () => {
            new window.google.translate.TranslateElement({
                pageLanguage: 'th',
                includedLanguages: 'th,en,zh-CN,ja,ko,vi,de,fr,es',
                layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');

            console.log('✅ Google Translate Widget โหลดสำเร็จ (ฟรี 100%)');
        };

        // ซ่อน Google Translate Toolbar
        const style = document.createElement('style');
        style.textContent = `
            .goog-te-banner-frame,
            .goog-te-balloon-frame,
            .goog-tooltip {
                display: none !important;
            }
            body {
                top: 0 !important;
            }
            #google_translate_element {
                display: none;
            }
        `;
        document.head.appendChild(style);
    },

    /**
     * เปลี่ยนภาษา
     */
    setLanguage(langCode) {
        console.log('🌐 เปลี่ยนภาษาเป็น:', langCode);

        this.current = langCode;
        localStorage.setItem('app_language', langCode);

        // แปลหน้า
        this.translatePage();
    },

    /**
     * แปลทั้งหน้าด้วย Google Translate Widget
     */
    translatePage() {
        this.isTranslating = true;

        // รอให้ Google Translate โหลด
        const checkGoogleTranslate = setInterval(() => {
            if (window.google?.translate) {
                clearInterval(checkGoogleTranslate);

                // หา select element ของ Google Translate
                const selectElement = document.querySelector('.goog-te-combo');
                if (selectElement) {
                    // เปลี่ยนภาษา
                    selectElement.value = this.current;
                    selectElement.dispatchEvent(new Event('change'));

                    console.log('✅ แปลภาษาเป็น', this.current, 'สำเร็จ (Google Translate Widget - ฟรี!)');

                    setTimeout(() => {
                        this.isTranslating = false;
                    }, 500);
                } else {
                    console.warn('⚠️ ไม่พบ Google Translate select element - ลองใหม่...');
                    this.isTranslating = false;
                }
            }
        }, 100);

        // Timeout หลัง 5 วินาที
        setTimeout(() => {
            clearInterval(checkGoogleTranslate);
            this.isTranslating = false;
        }, 5000);
    },

    /**
     * ดึงข้อมูลภาษาปัจจุบัน
     */
    getCurrentLanguage() {
        return this.languages.find(l => l.code === this.current) || this.languages[0];
    },

    /**
     * Clear cache (Reload หน้าเพื่อ reset)
     */
    clearCache() {
        localStorage.removeItem('app_language');
        window.location.reload();
    }
});

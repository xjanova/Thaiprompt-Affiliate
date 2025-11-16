import Alpine from 'alpinejs';

/**
 * Language Store - Google Translate Integration (แปลอัตโนมัติเหมือน Chrome)
 *
 * Features:
 * - ใช้ Google Translate Element API
 * - แปลหน้าเว็บอัตโนมัติเหมือน Chrome
 * - ฟรี ไม่ต้องใช้ API key
 */
Alpine.store('language', {
    // สถานะปัจจุบัน
    current: 'th',
    isTranslating: false,
    isGoogleTranslateReady: false,
    translateRetryCount: 0,
    maxTranslateRetries: 10,

    // ภาษาที่รองรับ (ใช้ code ที่ Google Translate รองรับ)
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
        // โหลด Google Translate Element script
        this.loadGoogleTranslate();

        // โหลดภาษาที่บันทึกไว้
        const savedLang = localStorage.getItem('app_language');
        if (savedLang && this.languages.find(l => l.code === savedLang)) {
            this.current = savedLang;
        } else {
            this.current = 'th';
            localStorage.setItem('app_language', 'th');
        }

        console.log('🌐 Language Store initialized with Google Translate:', this.current);
    },

    /**
     * โหลด Google Translate Element script
     */
    loadGoogleTranslate() {
        // เช็คว่าโหลดแล้วหรือยัง
        if (window.google?.translate) {
            this.initGoogleTranslate();
            return;
        }

        // สร้าง callback function สำหรับ Google Translate
        window.googleTranslateElementInit = () => {
            this.initGoogleTranslate();
        };

        // โหลด Google Translate script
        const script = document.createElement('script');
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        script.onerror = () => {
            console.error('❌ ไม่สามารถโหลด Google Translate ได้');
        };
        document.head.appendChild(script);
    },

    /**
     * Initialize Google Translate Element
     */
    initGoogleTranslate() {
        // สร้าง hidden div สำหรับ Google Translate Element
        if (!document.getElementById('google_translate_element')) {
            const div = document.createElement('div');
            div.id = 'google_translate_element';
            div.style.display = 'none';
            document.body.appendChild(div);
        }

        try {
            // Initialize Google Translate
            new window.google.translate.TranslateElement({
                pageLanguage: 'th',
                includedLanguages: 'th,en,zh-CN,ja,ko,vi,de,fr,es',
                autoDisplay: false,
                layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element');

            this.isGoogleTranslateReady = true;
            console.log('✅ Google Translate พร้อมใช้งาน');

            // ซ่อน Google Translate toolbar
            this.hideGoogleTranslateToolbar();

            // ถ้ามีภาษาที่บันทึกไว้ ให้แปลทันที
            const savedLang = localStorage.getItem('app_language');
            if (savedLang && savedLang !== 'th') {
                setTimeout(() => this.translatePage(savedLang), 1000);
            }
        } catch (error) {
            console.error('❌ เกิดข้อผิดพลาดใน Google Translate:', error);
        }
    },

    /**
     * ซ่อน Google Translate toolbar/banner
     */
    hideGoogleTranslateToolbar() {
        const style = document.createElement('style');
        style.textContent = `
            .goog-te-banner-frame,
            .goog-te-balloon-frame,
            #goog-gt-tt,
            .goog-tooltip {
                display: none !important;
            }
            body {
                top: 0 !important;
            }
            .skiptranslate {
                display: none !important;
            }
            #google_translate_element {
                display: none !important;
            }
        `;
        document.head.appendChild(style);
    },

    /**
     * เปลี่ยนภาษาและแปลหน้าเว็บ
     */
    setLanguage(langCode) {
        console.log('🌐 [DEBUG] setLanguage() called with:', langCode);
        console.log('🌐 [DEBUG] isGoogleTranslateReady:', this.isGoogleTranslateReady);

        this.current = langCode;
        localStorage.setItem('app_language', langCode);
        this.translatePage(langCode);
    },

    /**
     * แปลหน้าเว็บด้วย Google Translate
     */
    translatePage(targetLang) {
        console.log('🔄 [DEBUG] translatePage() called with:', targetLang, '(retry:', this.translateRetryCount + ')');
        console.log('🔄 [DEBUG] isGoogleTranslateReady:', this.isGoogleTranslateReady);

        if (!this.isGoogleTranslateReady) {
            console.log('⏳ [DEBUG] รอ Google Translate โหลด... (retry ใน 500ms)');
            if (this.translateRetryCount < this.maxTranslateRetries) {
                this.translateRetryCount++;
                setTimeout(() => this.translatePage(targetLang), 500);
            } else {
                console.error('❌ [DEBUG] Google Translate ไม่โหลดหลัง', this.maxTranslateRetries, 'ครั้ง');
                this.translateRetryCount = 0;
            }
            return;
        }

        this.isTranslating = true;
        console.log('🔄 [DEBUG] isTranslating set to true');

        // ลอง selector หลายๆ แบบ
        let selectElement = document.querySelector('.goog-te-combo');

        if (!selectElement) {
            // ลอง selector อื่น
            selectElement = document.querySelector('select.goog-te-combo');
        }

        if (!selectElement) {
            // ลองหา select ใน iframe
            const gtFrame = document.querySelector('#google_translate_element select');
            if (gtFrame) selectElement = gtFrame;
        }

        if (!selectElement) {
            // ลองหาทุก select ที่อยู่ใน element ที่มี class goog-te
            const googTeElements = document.querySelectorAll('[class*="goog-te"]');
            console.log('🔍 [DEBUG] googTeElements:', Array.from(googTeElements).map(el => ({
                tag: el.tagName,
                class: el.className,
                id: el.id,
                innerHTML: el.innerHTML.substring(0, 100)
            })));

            for (const el of googTeElements) {
                const select = el.querySelector('select');
                if (select) {
                    selectElement = select;
                    console.log('✅ [DEBUG] พบ select ใน:', el.className);
                    break;
                }
            }
        }

        console.log('🔍 [DEBUG] selectElement:', selectElement);
        console.log('🔍 [DEBUG] selectElement value before:', selectElement?.value);
        console.log('🔍 [DEBUG] selectElement options:', selectElement ? Array.from(selectElement.options).map(opt => opt.value) : 'N/A');

        if (selectElement) {
            // รีเซ็ต retry count
            this.translateRetryCount = 0;

            // เปลี่ยนค่า select และ trigger change event
            selectElement.value = targetLang;
            console.log('🔍 [DEBUG] selectElement value after:', selectElement.value);

            selectElement.dispatchEvent(new Event('change'));
            console.log('✅ [DEBUG] change event dispatched for language:', targetLang);

            setTimeout(() => {
                this.isTranslating = false;
                console.log('🔄 [DEBUG] isTranslating set to false');
            }, 1000);
        } else {
            console.warn('⚠️ [DEBUG] ไม่พบ select element - retry:', this.translateRetryCount);

            if (this.translateRetryCount < this.maxTranslateRetries) {
                this.translateRetryCount++;
                setTimeout(() => this.translatePage(targetLang), 300);
            } else {
                console.error('❌ [DEBUG] ไม่พบ select element หลังจาก', this.maxTranslateRetries, 'ครั้ง - ยกเลิก');
                this.translateRetryCount = 0;
                this.isTranslating = false;
            }
        }
    },

    /**
     * ดึงข้อมูลภาษาปัจจุบัน
     */
    getCurrentLanguage() {
        return this.languages.find(l => l.code === this.current) || this.languages[0];
    },

    /**
     * Clear cache และกลับเป็นภาษาไทย
     */
    clearCache() {
        localStorage.removeItem('app_language');
        this.current = 'th';
        this.translatePage('th');
    }
});

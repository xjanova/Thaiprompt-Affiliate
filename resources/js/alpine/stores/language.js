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
                layout: window.google.translate.TranslateElement.InlineLayout.VERTICAL  // เปลี่ยนจาก SIMPLE เป็น VERTICAL
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

            /* ทำให้ dropdown ทึบกว่าเดิม - มองเห็นชัดเจน */
            .goog-te-combo {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(12px) !important;
                -webkit-backdrop-filter: blur(12px) !important;
                border: 1px solid rgba(255, 255, 255, 0.3) !important;
                color: #1f2937 !important;
                padding: 8px 12px !important;
                border-radius: 8px !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            }

            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .goog-te-combo {
                    background: rgba(31, 41, 55, 0.95) !important;
                    color: #f9fafb !important;
                    border: 1px solid rgba(255, 255, 255, 0.2) !important;
                }
            }

            /* Hover effect */
            .goog-te-combo:hover {
                background: rgba(255, 255, 255, 1) !important;
                border-color: rgba(59, 130, 246, 0.5) !important;
            }

            @media (prefers-color-scheme: dark) {
                .goog-te-combo:hover {
                    background: rgba(31, 41, 55, 1) !important;
                }
            }

            /* Focus effect */
            .goog-te-combo:focus {
                outline: none !important;
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            }
        `;
        document.head.appendChild(style);
    },

    /**
     * เปลี่ยนภาษาและแปลหน้าเว็บ
     */
    setLanguage(langCode) {
        this.current = langCode;
        localStorage.setItem('app_language', langCode);

        // Dispatch event เมื่อเริ่มแปล
        const lang = this.languages.find(l => l.code === langCode);
        window.dispatchEvent(new CustomEvent('language-changed', {
            detail: { code: langCode, language: lang?.nativeName || langCode }
        }));

        this.translatePage(langCode);
    },

    /**
     * แปลหน้าเว็บด้วย Google Translate
     */
    translatePage(targetLang) {
        if (!this.isGoogleTranslateReady) {
            if (this.translateRetryCount < this.maxTranslateRetries) {
                this.translateRetryCount++;
                setTimeout(() => this.translatePage(targetLang), 500);
            } else {
                this.translateRetryCount = 0;
            }
            return;
        }

        this.isTranslating = true;

        // ลอง selector หลายๆ แบบ
        let selectElement = document.querySelector('.goog-te-combo');

        if (!selectElement) {
            selectElement = document.querySelector('select.goog-te-combo');
        }

        if (!selectElement) {
            const gtFrame = document.querySelector('#google_translate_element select');
            if (gtFrame) selectElement = gtFrame;
        }

        if (!selectElement) {
            // ลองหา select ใน googTeElements
            const googTeElements = document.querySelectorAll('[class*="goog-te"]');
            for (const el of googTeElements) {
                const select = el.querySelector('select');
                if (select) {
                    selectElement = select;
                    break;
                }
            }
        }

        if (selectElement) {
            // รีเซ็ต retry count
            this.translateRetryCount = 0;

            // Debug: แสดงภาษาที่กำลังแปล
            console.log('🌐 กำลังแปลเป็น:', targetLang, this.languages.find(l => l.code === targetLang)?.nativeName);
            console.log('🌐 Select element value before:', selectElement.value);

            // เปลี่ยนค่า select
            const oldValue = selectElement.value;
            selectElement.value = targetLang;

            console.log('🌐 Select element value after:', selectElement.value);
            console.log('🌐 Value changed:', oldValue, '→', targetLang);

            // Trigger หลาย events พร้อมกัน (บางครั้ง Google Translate ต้องการหลาย events)
            const events = [
                new Event('change', { bubbles: true, cancelable: true }),
                new Event('input', { bubbles: true, cancelable: true }),
                new Event('click', { bubbles: true, cancelable: true }),
            ];

            events.forEach(event => {
                selectElement.dispatchEvent(event);
            });

            // เรียก onchange handler ถ้ามี (fallback)
            if (typeof selectElement.onchange === 'function') {
                selectElement.onchange.call(selectElement);
            }

            // Focus และ blur เพื่อบังคับให้ Google Translate สังเกตเห็น
            selectElement.focus();
            setTimeout(() => selectElement.blur(), 50);

            // ถ้าแปลไม่สำเร็จภายใน 2 วินาที ให้ reload หน้าเว็บ (fallback)
            const translationTimeout = setTimeout(() => {
                console.warn('⚠️ Google Translate ไม่ตอบสนอง - กำลัง reload หน้าเว็บ...');

                // บันทึก scroll position
                sessionStorage.setItem('scrollPosition', window.scrollY.toString());

                // Reload หน้าเว็บเพื่อให้แปลภาษา (fallback solution)
                window.location.reload();
            }, 2000);

            // ตรวจสอบว่าแปลสำเร็จหรือไม่โดยดูจาก body class
            const checkTranslation = setInterval(() => {
                const bodyClass = document.body.className;
                const isTranslated = bodyClass.includes('translated-') ||
                                   document.querySelector('html').getAttribute('lang') !== 'th';

                if (isTranslated || targetLang === 'th') {
                    clearInterval(checkTranslation);
                    clearTimeout(translationTimeout);

                    this.isTranslating = false;
                    console.log('✅ แปลเสร็จแล้ว:', targetLang);

                    // Dispatch event เมื่อแปลเสร็จ
                    const lang = this.languages.find(l => l.code === targetLang);
                    window.dispatchEvent(new CustomEvent('translation-complete', {
                        detail: { code: targetLang, language: lang?.nativeName || targetLang }
                    }));

                    // กลับไปตำแหน่ง scroll เดิม
                    const savedScroll = sessionStorage.getItem('scrollPosition');
                    if (savedScroll) {
                        window.scrollTo(0, parseInt(savedScroll));
                        sessionStorage.removeItem('scrollPosition');
                    }
                }
            }, 200);
        } else {
            if (this.translateRetryCount < this.maxTranslateRetries) {
                this.translateRetryCount++;
                setTimeout(() => this.translatePage(targetLang), 300);
            } else {
                console.error('❌ ไม่พบ Google Translate select element');
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

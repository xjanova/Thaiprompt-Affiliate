import Alpine from 'alpinejs';

/**
 * Language Store - Alpine.js Store สำหรับจัดการภาษาและการแปลด้วย Google Translate API
 *
 * Features:
 * - เปลี่ยนภาษาได้ทันที (th, en, zh, ja, ko, vi, de, fr, es)
 * - แปลข้อความผ่าน Google Translate API
 * - บันทึก preference ลง localStorage
 * - แปลเนื้อหาหน้าเว็บแบบเรียลไทม์
 * - Cache การแปลเพื่อลด API calls
 *
 * @example
 * // เปลี่ยนภาษา
 * $store.language.setLanguage('en')
 *
 * // แปลข้อความ
 * await $store.language.translate('สวัสดี')
 *
 * // แปลทั้งหน้า
 * $store.language.translatePage()
 */
Alpine.store('language', {
    // สถานะปัจจุบัน
    current: 'th',
    isTranslating: false,
    translationCache: {},

    // ภาษาที่รองรับ
    languages: [
        { code: 'th', name: 'ไทย', flag: '🇹🇭', nativeName: 'ภาษาไทย' },
        { code: 'en', name: 'English', flag: '🇺🇸', nativeName: 'English' },
        { code: 'zh', name: 'Chinese', flag: '🇨🇳', nativeName: '中文' },
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
            // ตรวจจากบราวเซอร์
            const browserLang = navigator.language.split('-')[0];
            if (this.languages.find(l => l.code === browserLang)) {
                this.current = browserLang;
            }
        }

        console.log('🌐 Language Store initialized:', this.current);

        // ถ้าไม่ใช่ภาษาไทย ให้แปลหน้าอัตโนมัติ
        if (this.current !== 'th') {
            setTimeout(() => this.translatePage(), 500);
        }
    },

    /**
     * เปลี่ยนภาษา
     */
    async setLanguage(langCode) {
        if (this.current === langCode) return;

        this.current = langCode;
        localStorage.setItem('app_language', langCode);

        console.log('🌐 Language changed to:', langCode);

        // แปลหน้าทันที
        await this.translatePage();

        // Reload หน้าเพื่อ apply ภาษาใหม่ทั้งหมด
        setTimeout(() => window.location.reload(), 500);
    },

    /**
     * ดึงข้อมูลภาษาปัจจุบัน
     */
    getCurrentLanguage() {
        return this.languages.find(l => l.code === this.current) || this.languages[0];
    },

    /**
     * แปลข้อความเดี่ยว
     */
    async translate(text, targetLang = null) {
        if (!text || text.trim() === '') return text;

        const target = targetLang || this.current;

        // ถ้าเป็นภาษาไทย ไม่ต้องแปล
        if (target === 'th') return text;

        // เช็ค cache
        const cacheKey = `${text}_${target}`;
        if (this.translationCache[cacheKey]) {
            return this.translationCache[cacheKey];
        }

        try {
            const response = await fetch('/api/v1/translate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    text: text,
                    target: target,
                    source: 'th'
                })
            });

            const data = await response.json();

            if (data.success && data.translatedText) {
                // บันทึก cache
                this.translationCache[cacheKey] = data.translatedText;
                return data.translatedText;
            }

            return text; // fallback
        } catch (error) {
            console.error('Translation error:', error);
            return text; // fallback
        }
    },

    /**
     * แปลทั้งหน้าเว็บ
     */
    async translatePage() {
        if (this.current === 'th' || this.isTranslating) return;

        this.isTranslating = true;

        try {
            console.log('🌐 Translating page to:', this.current);

            // หา elements ที่ต้องแปล
            const elements = document.querySelectorAll('[data-translate], h1, h2, h3, p, span:not(.no-translate), button:not(.no-translate), a:not(.no-translate), label, td');

            const textsToTranslate = [];
            const elementMap = [];

            elements.forEach(el => {
                // ข้าม elements ที่มี class no-translate
                if (el.classList.contains('no-translate')) return;

                // ข้าม elements ว่าง
                const text = el.textContent?.trim();
                if (!text || text.length === 0) return;

                // ข้าม elements ที่มีแต่ตัวเลขหรือสัญลักษณ์
                if (/^[\d\s\.,\-\+\*\/\=\(\)\[\]\{\}]+$/.test(text)) return;

                textsToTranslate.push(text);
                elementMap.push(el);
            });

            // แปลทีละ batch (10 texts per request)
            const batchSize = 10;
            for (let i = 0; i < textsToTranslate.length; i += batchSize) {
                const batch = textsToTranslate.slice(i, i + batchSize);
                const translations = await Promise.all(
                    batch.map(text => this.translate(text))
                );

                // Apply translations
                translations.forEach((translated, index) => {
                    const el = elementMap[i + index];
                    if (el && translated) {
                        el.textContent = translated;
                    }
                });

                // Delay เล็กน้อยเพื่อไม่ให้โดน rate limit
                await new Promise(resolve => setTimeout(resolve, 100));
            }

            console.log('✅ Page translation completed');
        } catch (error) {
            console.error('Page translation error:', error);
        } finally {
            this.isTranslating = false;
        }
    },

    /**
     * Clear cache
     */
    clearCache() {
        this.translationCache = {};
        console.log('🗑️ Translation cache cleared');
    }
});

export default Alpine.store('language');

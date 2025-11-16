/**
 * Alpine.js Global Stores
 *
 * ไฟล์นี้รวม Alpine.js stores ทั้งหมดสำหรับแอปพลิเคชัน
 * - language: จัดการภาษาและ Google Translate
 * - theme: จัดการ dark/light mode
 */

document.addEventListener('alpine:init', () => {
    // Language Store ถูก import จาก ./alpine/stores/language.js แล้ว
    // ไม่ต้องสร้างใหม่ที่นี่

    /**
     * Theme Presets Store - ธีมสำเร็จรูป
     */
    Alpine.store('themePresets', {
        presets: {
            classic: {
                name: 'Classic',
                icon: 'fas fa-desktop',
                description: 'ปิดเอฟเฟคทั้งหมด',
                settings: {
                    glassOpacity: 0,
                    glassBlur: 0,
                    borderOpacity: 0,
                    shadowIntensity: 0,
                    glowIntensity: 0,
                    textShadow: false,
                    animationSpeed: 0,
                    hoverScale: 100,
                    cardRoundness: 4,
                    buttonRoundness: 4,
                    primaryHue: 260,
                    accentHue: 340,
                    backdropSaturate: 100,
                    perspectiveDepth: 0,
                }
            },
            modern: {
                name: 'Modern',
                icon: 'fas fa-layer-group',
                description: 'เอฟเฟคปานกลาง สมดุล',
                settings: {
                    glassOpacity: 10,
                    glassBlur: 8,
                    borderOpacity: 20,
                    shadowIntensity: 30,
                    glowIntensity: 40,
                    textShadow: true,
                    animationSpeed: 300,
                    hoverScale: 103,
                    cardRoundness: 12,
                    buttonRoundness: 8,
                    primaryHue: 220,
                    accentHue: 200,
                    backdropSaturate: 100,
                    perspectiveDepth: 500,
                }
            },
            glassmorphism: {
                name: 'Glassmorphism',
                icon: 'fas fa-gem',
                description: 'เต็มเอฟเฟค สวยงาม (ค่าเริ่มต้น)',
                settings: {
                    glassOpacity: 15,
                    glassBlur: 12,
                    borderOpacity: 30,
                    shadowIntensity: 50,
                    glowIntensity: 60,
                    textShadow: true,
                    animationSpeed: 500,
                    hoverScale: 105,
                    cardRoundness: 16,
                    buttonRoundness: 12,
                    primaryHue: 260,
                    accentHue: 340,
                    backdropSaturate: 100,
                    perspectiveDepth: 1000,
                }
            },
            neon: {
                name: 'Neon',
                icon: 'fas fa-sun',
                description: 'สีสันสดใส โดดเด่น',
                settings: {
                    glassOpacity: 15,
                    glassBlur: 12,
                    borderOpacity: 35,
                    shadowIntensity: 60,
                    glowIntensity: 100,
                    textShadow: true,
                    animationSpeed: 500,
                    hoverScale: 110,
                    cardRoundness: 16,
                    buttonRoundness: 12,
                    primaryHue: 180,
                    accentHue: 300,
                    backdropSaturate: 150,
                    perspectiveDepth: 1000,
                }
            }
        }
    });
});

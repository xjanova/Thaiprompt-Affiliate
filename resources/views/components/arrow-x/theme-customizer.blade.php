{{--
/**
 * Theme Customizer V3 - ปรับแต่งธีมแบบละเอียด
 *
 * @tip เปิดด้วยปุ่ม settings หรือ keyboard shortcut (Ctrl+Shift+T)
 */
--}}

<div x-data="{
    customizerOpen: false,
    activeTab: 'general',
    settings: {
        // Glass Effects
        glassOpacity: 15,
        glassBlur: 12,
        borderOpacity: 30,

        // Shadows & Glow
        shadowIntensity: 50,
        glowIntensity: 60,
        textShadow: true,

        // Animations
        animationSpeed: 500,
        hoverScale: 105,

        // Roundness
        cardRoundness: 16,
        buttonRoundness: 12,

        // Colors
        primaryHue: 260, // Purple
        accentHue: 340, // Pink
        contrast: 100, // ความตัดกันของสี

        // Advanced
        backdropSaturate: 100,
        perspectiveDepth: 1000,
    },

    init() {
        // โหลดการตั้งค่าจาก localStorage
        const saved = localStorage.getItem('themeSettings');
        if (saved) {
            this.settings = { ...this.settings, ...JSON.parse(saved) };
        }
        this.applySettings();

        // Keyboard shortcut (Ctrl+Shift+T)
        window.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                this.customizerOpen = !this.customizerOpen;
            }
        });
    },

    saveSettings() {
        localStorage.setItem('themeSettings', JSON.stringify(this.settings));
        this.applySettings();
    },

    applySettings() {
        // Apply CSS custom properties
        const root = document.documentElement;
        root.style.setProperty('--glass-opacity', this.settings.glassOpacity / 100);
        root.style.setProperty('--glass-blur', this.settings.glassBlur + 'px');
        root.style.setProperty('--border-opacity', this.settings.borderOpacity / 100);
        root.style.setProperty('--shadow-intensity', this.settings.shadowIntensity / 100);
        root.style.setProperty('--glow-intensity', this.settings.glowIntensity / 100);
        root.style.setProperty('--animation-speed', this.settings.animationSpeed + 'ms');
        root.style.setProperty('--hover-scale', this.settings.hoverScale / 100);
        root.style.setProperty('--card-roundness', this.settings.cardRoundness + 'px');
        root.style.setProperty('--button-roundness', this.settings.buttonRoundness + 'px');
        root.style.setProperty('--primary-hue', this.settings.primaryHue);
        root.style.setProperty('--accent-hue', this.settings.accentHue);
        root.style.setProperty('--contrast', this.settings.contrast + '%');
        root.style.setProperty('--backdrop-saturate', this.settings.backdropSaturate + '%');
        root.style.setProperty('--perspective-depth', this.settings.perspectiveDepth + 'px');

        // ถ้าเป็นโหมด Classic (ไม่มี glass effects) ให้เพิ่ม class พิเศษ
        const isClassicMode = this.settings.glassOpacity === 0 && this.settings.glassBlur === 0;
        if (isClassicMode) {
            document.body.classList.add('theme-classic');
        } else {
            document.body.classList.remove('theme-classic');
        }
    },

    resetSettings() {
        if (confirm('รีเซ็ตการตั้งค่าทั้งหมดเป็นค่าเริ่มต้น?')) {
            this.settings = {
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
                contrast: 100,
                backdropSaturate: 100,
                perspectiveDepth: 1000,
            };
            this.saveSettings();
        }
    }
}"
     class="fixed right-0 top-0 bottom-0 z-[9999]"
     @keydown.escape.window="customizerOpen = false"
     @toggle-customizer.window="customizerOpen = !customizerOpen">

    {{-- Customizer Panel --}}
    <div x-show="customizerOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="w-[400px] h-full glass-fusion border-l border-white/30 shadow-2xl overflow-hidden flex flex-col">

        {{-- Header --}}
        <div class="p-6 border-b border-white/30 bg-gradient-to-r from-purple-500/20 to-pink-600/20">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white drop-shadow-lg flex items-center gap-2">
                        <i class="fas fa-paint-brush"></i>
                        ปรับแต่งธีม
                    </h2>
                    <p class="text-xs text-white/70 mt-1">ปรับแต่งทุกรายละเอียดตามใจคุณ</p>
                </div>
                <button @click="customizerOpen = false"
                        class="p-2 rounded-lg hover:bg-white/20 transition">
                    <i class="fas fa-times text-white"></i>
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-white/30 bg-white/5">
            <button @click="activeTab = 'general'"
                    class="flex-1 px-4 py-3 text-sm font-medium transition"
                    :class="activeTab === 'general' ? 'text-white bg-white/20 border-b-2 border-blue-400' : 'text-white/70 hover:text-white hover:bg-white/10'">
                <i class="fas fa-sliders-h mr-2"></i>
                ทั่วไป
            </button>
            <button @click="activeTab = 'effects'"
                    class="flex-1 px-4 py-3 text-sm font-medium transition"
                    :class="activeTab === 'effects' ? 'text-white bg-white/20 border-b-2 border-blue-400' : 'text-white/70 hover:text-white hover:bg-white/10'">
                <i class="fas fa-magic mr-2"></i>
                เอฟเฟกต์
            </button>
            <button @click="activeTab = 'advanced'"
                    class="flex-1 px-4 py-3 text-sm font-medium transition"
                    :class="activeTab === 'advanced' ? 'text-white bg-white/20 border-b-2 border-blue-400' : 'text-white/70 hover:text-white hover:bg-white/10'">
                <i class="fas fa-cog mr-2"></i>
                ขั้นสูง
            </button>
            @auth
                @if(auth()->user()->is_admin || auth()->user()->hasRole(['admin', 'super_admin']))
                    <button @click="activeTab = 'mlm'"
                            class="flex-1 px-4 py-3 text-sm font-medium transition"
                            :class="activeTab === 'mlm' ? 'text-white bg-white/20 border-b-2 border-purple-400' : 'text-white/70 hover:text-white hover:bg-white/10'">
                        <i class="fas fa-sitemap mr-2"></i>
                        MLM
                    </button>
                @endif
            @endauth
        </div>

        {{-- Preset Themes --}}
        <div class="p-4 border-b border-white/30 bg-white/5">
            <h3 class="text-xs font-bold text-white/80 mb-3 uppercase tracking-wider">
                <i class="fas fa-palette mr-2"></i>ธีมสำเร็จรูป
            </h3>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="(preset, key) in $store.themePresets.presets" :key="key">
                    <button
                        @click="settings = { ...preset.settings }; saveSettings();"
                        class="px-3 py-2.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 hover:border-white/40 transition-all text-left group"
                        :title="preset.description">
                        <div class="flex items-center gap-2">
                            <i :class="preset.icon + ' text-blue-300 group-hover:text-blue-200 transition'"></i>
                            <span class="text-xs font-medium text-white" x-text="preset.name"></span>
                        </div>
                        <p class="text-[10px] text-white/60 mt-0.5 line-clamp-1" x-text="preset.description"></p>
                    </button>
                </template>
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- General Tab --}}
            <div x-show="activeTab === 'general'" class="space-y-6">
                {{-- Glass Opacity --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-droplet mr-2"></i>ความโปร่งใส Glass</span>
                        <span x-text="settings.glassOpacity + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="5" max="50" step="1"
                           x-model="settings.glassOpacity"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Blur Amount --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-blur mr-2"></i>ความเบลอ Backdrop</span>
                        <span x-text="settings.glassBlur + 'px'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="24" step="1"
                           x-model="settings.glassBlur"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Border Opacity --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-border-style mr-2"></i>ความโปร่งใสขอบ</span>
                        <span x-text="settings.borderOpacity + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="100" step="5"
                           x-model="settings.borderOpacity"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Card Roundness --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-shapes mr-2"></i>ความโค้งมุมการ์ด</span>
                        <span x-text="settings.cardRoundness + 'px'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="32" step="2"
                           x-model="settings.cardRoundness"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Button Roundness --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-square mr-2"></i>ความโค้งมุมปุ่ม</span>
                        <span x-text="settings.buttonRoundness + 'px'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="24" step="2"
                           x-model="settings.buttonRoundness"
                           @input="saveSettings()"
                           class="w-full">
                </div>
            </div>

            {{-- Effects Tab --}}
            <div x-show="activeTab === 'effects'" class="space-y-6">
                {{-- Shadow Intensity --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-moon mr-2"></i>ความเข้มเงา</span>
                        <span x-text="settings.shadowIntensity + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="100" step="5"
                           x-model="settings.shadowIntensity"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Glow Intensity --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-sun mr-2"></i>ความเข้ม Glow</span>
                        <span x-text="settings.glowIntensity + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="100" step="5"
                           x-model="settings.glowIntensity"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Animation Speed --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-tachometer-alt mr-2"></i>ความเร็วแอนิเมชัน</span>
                        <span x-text="settings.animationSpeed + 'ms'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="100" max="1000" step="50"
                           x-model="settings.animationSpeed"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Hover Scale --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-expand mr-2"></i>การขยาย Hover</span>
                        <span x-text="settings.hoverScale + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="100" max="120" step="1"
                           x-model="settings.hoverScale"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Text Shadow Toggle --}}
                <div class="flex items-center justify-between">
                    <label class="text-white text-sm font-medium">
                        <i class="fas fa-font mr-2"></i>เงาข้อความ
                    </label>
                    <button @click="settings.textShadow = !settings.textShadow; saveSettings()"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                            :class="settings.textShadow ? 'bg-blue-500' : 'bg-gray-600'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                              :class="settings.textShadow ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
            </div>

            {{-- Advanced Tab --}}
            <div x-show="activeTab === 'advanced'" class="space-y-6">
                {{-- Primary Hue --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-palette mr-2"></i>สีหลัก (Hue)</span>
                        <span x-text="settings.primaryHue + '°'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="360" step="1"
                           x-model="settings.primaryHue"
                           @input="saveSettings()"
                           class="w-full">
                    <div class="h-8 rounded-lg mt-2"
                         :style="`background: hsl(${settings.primaryHue}, 70%, 60%)`"></div>
                </div>

                {{-- Accent Hue --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-palette mr-2"></i>สีเสริม (Hue)</span>
                        <span x-text="settings.accentHue + '°'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="0" max="360" step="1"
                           x-model="settings.accentHue"
                           @input="saveSettings()"
                           class="w-full">
                    <div class="h-8 rounded-lg mt-2"
                         :style="`background: hsl(${settings.accentHue}, 70%, 60%)`"></div>
                </div>

                {{-- Contrast --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-adjust mr-2"></i>ความตัดกันของสี (Contrast)</span>
                        <span x-text="settings.contrast + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="50" max="150" step="5"
                           x-model="settings.contrast"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Backdrop Saturate --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-adjust mr-2"></i>ความอิ่มตัวสี</span>
                        <span x-text="settings.backdropSaturate + '%'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="50" max="200" step="10"
                           x-model="settings.backdropSaturate"
                           @input="saveSettings()"
                           class="w-full">
                </div>

                {{-- Perspective Depth --}}
                <div>
                    <label class="flex items-center justify-between text-white text-sm font-medium mb-2">
                        <span><i class="fas fa-cube mr-2"></i>ความลึก 3D</span>
                        <span x-text="settings.perspectiveDepth + 'px'" class="text-blue-300"></span>
                    </label>
                    <input type="range" min="500" max="2000" step="100"
                           x-model="settings.perspectiveDepth"
                           @input="saveSettings()"
                           class="w-full">
                </div>
            </div>

            {{-- MLM Tab (เฉพาะแอดมิน) --}}
            @auth
                @if(auth()->user()->is_admin || auth()->user()->hasRole(['admin', 'super_admin']))
                    <x-arrow-x.mlm-settings-tab />
                @endif
            @endauth
        </div>

        {{-- Footer Actions --}}
        <div class="p-4 border-t border-white/30 bg-white/5 space-y-2">
            <button @click="resetSettings()"
                    class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg hover:from-red-600 hover:to-pink-700 transition font-medium">
                <i class="fas fa-undo mr-2"></i>
                รีเซ็ตเป็นค่าเริ่มต้น
            </button>
            <div class="text-xs text-white/60 text-center">
                <kbd class="px-2 py-1 bg-white/10 rounded">Ctrl</kbd> +
                <kbd class="px-2 py-1 bg-white/10 rounded">Shift</kbd> +
                <kbd class="px-2 py-1 bg-white/10 rounded">T</kbd>
                เพื่อเปิด/ปิด
            </div>
        </div>
    </div>
</div>

<style>
/**
 * Custom Range Slider Styles
 */
input[type="range"] {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: rgba(255, 255, 255, 0.2);
    outline: none;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgb(59, 130, 246), rgb(147, 51, 234));
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgb(59, 130, 246), rgb(147, 51, 234));
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

/**
 * Glass Fusion Effect
 */
.glass-fusion {
    background: rgba(255, 255, 255, var(--glass-opacity, 0.15));
    backdrop-filter: blur(var(--glass-blur, 12px)) saturate(var(--backdrop-saturate, 100%));
    -webkit-backdrop-filter: blur(var(--glass-blur, 12px)) saturate(var(--backdrop-saturate, 100%));
}

/**
 * Classic Mode - ทึบหมด ไม่มี Glass Effects
 * เมื่อ body มี class "theme-classic" จะ override glass effects ทั้งหมด
 */
body.theme-classic .glass-fusion,
body.theme-classic .glass-neu,
body.theme-classic .glass-dropdown {
    background: rgba(30, 41, 59, 0.95) !important; /* Dark solid background */
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: 1px solid rgba(71, 85, 105, 0.5) !important;
}

body.theme-classic aside.glass-fusion {
    background: rgba(15, 23, 42, 0.98) !important; /* Sidebar darker */
}

body.theme-classic .glass-dropdown {
    background: rgba(30, 41, 59, 0.98) !important; /* Dropdown solid */
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
}

/**
 * Dynamic Color System - ใช้ CSS variables สำหรับสี
 * ปรับ primaryHue, accentHue และ contrast แบบ real-time
 */
:root {
    --primary-hue: 260;
    --accent-hue: 340;
    --contrast: 100%;
}

/* Gradient Backgrounds ที่ใช้ primary/accent hue */
.bg-gradient-to-r {
    filter: contrast(var(--contrast));
}

/* Stats cards gradients - ใช้ primary hue */
.from-purple-500 {
    --tw-gradient-from: hsl(var(--primary-hue), 70%, 55%) !important;
}

.to-purple-600 {
    --tw-gradient-to: hsl(var(--primary-hue), 70%, 45%) !important;
}

/* Stats cards gradients - ใช้ accent hue */
.from-pink-500 {
    --tw-gradient-from: hsl(var(--accent-hue), 70%, 55%) !important;
}

.to-pink-600 {
    --tw-gradient-to: hsl(var(--accent-hue), 70%, 45%) !important;
}

/* Blue gradients - adjustable */
.from-blue-500 {
    --tw-gradient-from: hsl(calc(var(--primary-hue) - 40), 70%, 55%) !important;
}

.to-blue-600 {
    --tw-gradient-to: hsl(calc(var(--primary-hue) - 40), 70%, 45%) !important;
}

/* Orange gradients */
.from-orange-500 {
    --tw-gradient-from: hsl(25, 90%, 55%) !important;
}

.to-red-600 {
    --tw-gradient-to: hsl(0, 75%, 45%) !important;
}

/* Green gradients */
.from-green-500 {
    --tw-gradient-from: hsl(142, 70%, 45%) !important;
}

.to-emerald-600 {
    --tw-gradient-to: hsl(160, 75%, 38%) !important;
}

/* Buttons และ interactive elements */
button.bg-gradient-to-r,
a.bg-gradient-to-r {
    filter: contrast(var(--contrast)) brightness(calc(100% + (var(--contrast) - 100%) * 0.2));
}

/**
 * Scrollbar Styles
 */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>

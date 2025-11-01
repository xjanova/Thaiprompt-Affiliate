<div class="p-6" x-data="headerEditor()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">แก้ไข Header & Menu</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">ปรับแต่ง Header ให้ดูเป็นมืออาชีพ พร้อม Live Preview แบบเรียลไทม์</p>
    </div>

    <!-- Main Layout: Split Screen -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- LEFT SIDE: Settings Controls -->
        <div class="space-y-4 overflow-y-auto" style="max-height: calc(100vh - 200px);">

            <!-- Layout & Position -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                    โครงสร้าง & ตำแหน่ง
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบ Header</label>
                        <select x-model="settings.header_layout" @change="updatePreview" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="default">ปกติ (Default)</option>
                            <option value="floating">ลอยอิสระ (Floating)</option>
                            <option value="sticky">ติดด้านบน (Sticky)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่ง</label>
                        <select x-model="settings.header_position" @change="updatePreview" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="relative">Relative</option>
                            <option value="fixed">Fixed (ติดด้านบนเสมอ)</option>
                            <option value="absolute">Absolute</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">พื้นหลังโปร่งใส</label>
                        <button @click="settings.header_transparent = !settings.header_transparent; updatePreview()"
                                :class="settings.header_transparent ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_transparent ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">โปร่งใสเมื่อเลื่อน (Scroll)</label>
                        <button @click="settings.header_transparent_on_scroll = !settings.header_transparent_on_scroll; updatePreview()"
                                :class="settings.header_transparent_on_scroll ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_transparent_on_scroll ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scroll Behavior -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    พฤติกรรมเมื่อเลื่อน (Scroll)
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ติดตามเมื่อเลื่อน (Sticky)</label>
                        <button @click="settings.header_sticky_scroll = !settings.header_sticky_scroll; updatePreview()"
                                :class="settings.header_sticky_scroll ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_sticky_scroll ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ซ่อนเมื่อเลื่อนลง</label>
                        <button @click="settings.header_hide_on_scroll = !settings.header_hide_on_scroll; updatePreview()"
                                :class="settings.header_hide_on_scroll ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_hide_on_scroll ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">หดเล็กลงเมื่อเลื่อน</label>
                        <button @click="settings.header_shrink_on_scroll = !settings.header_shrink_on_scroll; updatePreview()"
                                :class="settings.header_shrink_on_scroll ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_shrink_on_scroll ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Background & Colors -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    พื้นหลัง & สี
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีพื้นหลัง</label>
                        <div class="flex items-center space-x-3">
                            <div class="color-input-wrapper">
                                <div class="color-preview" :style="{ backgroundColor: settings.header_bg_color }"></div>
                                <input type="color" x-model="settings.header_bg_color" @change="updatePreview">
                            </div>
                            <input type="text" x-model="settings.header_bg_color" @input="updatePreview" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความทึบแสง: <span x-text="settings.header_bg_opacity + '%'"></span></label>
                        <input type="range" x-model="settings.header_bg_opacity" @input="updatePreview" min="0" max="100" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ใช้ไล่สี (Gradient)</label>
                        <button @click="settings.header_gradient = !settings.header_gradient; updatePreview()"
                                :class="settings.header_gradient ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_gradient ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div x-show="settings.header_gradient" class="space-y-3 pl-4 border-l-2 border-blue-200 dark:border-blue-700">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีเริ่มต้น</label>
                            <div class="flex items-center space-x-3">
                                <div class="color-input-wrapper">
                                    <div class="color-preview" :style="{ backgroundColor: settings.header_gradient_from }"></div>
                                    <input type="color" x-model="settings.header_gradient_from" @change="updatePreview">
                                </div>
                                <input type="text" x-model="settings.header_gradient_from" @input="updatePreview" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีปลายทาง</label>
                            <div class="flex items-center space-x-3">
                                <div class="color-input-wrapper">
                                    <div class="color-preview" :style="{ backgroundColor: settings.header_gradient_to }"></div>
                                    <input type="color" x-model="settings.header_gradient_to" @change="updatePreview">
                                </div>
                                <input type="text" x-model="settings.header_gradient_to" @input="updatePreview" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ทิศทางไล่สี</label>
                            <select x-model="settings.header_gradient_direction" @change="updatePreview" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                <option value="to-r">ซ้าย → ขวา</option>
                                <option value="to-l">ขวา → ซ้าย</option>
                                <option value="to-b">บน → ล่าง</option>
                                <option value="to-t">ล่าง → บน</option>
                                <option value="to-br">บนซ้าย → ล่างขวา</option>
                                <option value="to-bl">บนขวา → ล่างซ้าย</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีข้อความ</label>
                        <div class="flex items-center space-x-3">
                            <div class="color-input-wrapper">
                                <div class="color-preview" :style="{ backgroundColor: settings.header_text_color }"></div>
                                <input type="color" x-model="settings.header_text_color" @change="updatePreview">
                            </div>
                            <input type="text" x-model="settings.header_text_color" @input="updatePreview" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีข้อความเมื่อ Hover</label>
                        <div class="flex items-center space-x-3">
                            <div class="color-input-wrapper">
                                <div class="color-preview" :style="{ backgroundColor: settings.header_hover_color }"></div>
                                <input type="color" x-model="settings.header_hover_color" @change="updatePreview">
                            </div>
                            <input type="text" x-model="settings.header_hover_color" @input="updatePreview" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Effects & Shadow -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    เอฟเฟค & เงา
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เงา (Shadow)</label>
                        <select x-model="settings.header_shadow" @change="updatePreview" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            <option value="none">ไม่มีเงา</option>
                            <option value="sm">เล็ก (Small)</option>
                            <option value="md">กลาง (Medium)</option>
                            <option value="lg">ใหญ่ (Large)</option>
                            <option value="xl">ใหญ่พิเศษ (XL)</option>
                            <option value="2xl">ใหญ่มาก (2XL)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">เงาเมื่อเลื่อน</label>
                        <button @click="settings.header_shadow_on_scroll = !settings.header_shadow_on_scroll; updatePreview()"
                                :class="settings.header_shadow_on_scroll ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_shadow_on_scroll ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">เบลอพื้นหลัง (Blur)</label>
                        <button @click="settings.header_blur = !settings.header_blur; updatePreview()"
                                :class="settings.header_blur ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_blur ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div x-show="settings.header_blur">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับเบลอ: <span x-text="settings.header_blur_amount + 'px'"></span></label>
                        <input type="range" x-model="settings.header_blur_amount" @input="updatePreview" min="0" max="50" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>
                </div>
            </div>

            <!-- Dimensions -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    ขนาด & มิติ
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูง Header: <span x-text="settings.header_height + 'px'"></span></label>
                        <input type="range" x-model="settings.header_height" @input="updatePreview" min="40" max="200" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>

                    <div x-show="settings.header_shrink_on_scroll">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูงเมื่อเลื่อน: <span x-text="settings.header_height_scrolled + 'px'"></span></label>
                        <input type="range" x-model="settings.header_height_scrolled" @input="updatePreview" min="40" max="200" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Padding ซ้าย-ขวา: <span x-text="settings.header_padding_x + 'px'"></span></label>
                        <input type="range" x-model="settings.header_padding_x" @input="updatePreview" min="0" max="100" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูงโลโก้: <span x-text="settings.header_logo_height + 'px'"></span></label>
                        <input type="range" x-model="settings.header_logo_height" @input="updatePreview" min="20" max="100" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>

                    <div x-show="settings.header_shrink_on_scroll">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูงโลโก้เมื่อเลื่อน: <span x-text="settings.header_logo_height_scrolled + 'px'"></span></label>
                        <input type="range" x-model="settings.header_logo_height_scrolled" @input="updatePreview" min="20" max="100" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>
                </div>
            </div>

            <!-- Border -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    เส้นขอบ (Border)
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">เส้นขอบด้านล่าง</label>
                        <button @click="settings.header_border_bottom = !settings.header_border_bottom; updatePreview()"
                                :class="settings.header_border_bottom ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <span :class="settings.header_border_bottom ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <div x-show="settings.header_border_bottom">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีเส้นขอบ</label>
                        <div class="flex items-center space-x-3">
                            <div class="color-input-wrapper">
                                <div class="color-preview" :style="{ backgroundColor: settings.header_border_color }"></div>
                                <input type="color" x-model="settings.header_border_color" @change="updatePreview">
                            </div>
                            <input type="text" x-model="settings.header_border_color" @input="updatePreview" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        </div>
                    </div>

                    <div x-show="settings.header_border_bottom">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความหนา: <span x-text="settings.header_border_width + 'px'"></span></label>
                        <input type="range" x-model="settings.header_border_width" @input="updatePreview" min="0" max="10" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>
                </div>
            </div>

            <!-- Animation -->
            <div class="setting-card">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    แอนิเมชัน
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระยะเวลา: <span x-text="settings.header_animation_duration + 'ms'"></span></label>
                        <input type="range" x-model="settings.header_animation_duration" @input="updatePreview" min="100" max="1000" step="50" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer slider-thumb">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Easing</label>
                        <select x-model="settings.header_animation_easing" @change="updatePreview" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            <option value="linear">Linear</option>
                            <option value="ease">Ease</option>
                            <option value="ease-in">Ease In</option>
                            <option value="ease-out">Ease Out</option>
                            <option value="ease-in-out">Ease In Out</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-4 sticky bottom-0 bg-white dark:bg-gray-800 py-4 border-t border-gray-200 dark:border-gray-700">
                <button @click="saveSettings" :disabled="saving" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!saving">💾 บันทึกการตั้งค่า</span>
                    <span x-show="saving">⏳ กำลังบันทึก...</span>
                </button>
                <button @click="resetSettings" class="bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-600 transition-all">
                    🔄 รีเซ็ต
                </button>
            </div>

        </div>

        <!-- RIGHT SIDE: Live Preview -->
        <div class="sticky top-6" style="height: calc(100vh - 160px);">
            <div class="setting-card h-full flex flex-col">
                <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    ดูตัวอย่าง (Live Preview)
                </h3>

                <div class="flex-1 bg-gray-50 dark:bg-gray-900 rounded-lg overflow-hidden relative border-2 border-gray-200 dark:border-gray-700">
                    <!-- Preview Content with simulated scrolling -->
                    <div class="h-full overflow-y-auto" x-ref="previewScroll">
                        <!-- Simulated Header -->
                        <nav class="preview-header transition-all"
                             :class="{
                                 'fixed top-0 left-0 right-0 z-50': settings.header_position === 'fixed',
                                 'absolute top-0 left-0 right-0': settings.header_position === 'absolute',
                                 'relative': settings.header_position === 'relative',
                                 'shadow-sm': settings.header_shadow === 'sm' && !scrolled,
                                 'shadow': settings.header_shadow === 'md' && !scrolled,
                                 'shadow-lg': settings.header_shadow === 'lg' && !scrolled,
                                 'shadow-xl': settings.header_shadow === 'xl' && !scrolled,
                                 'shadow-2xl': settings.header_shadow === '2xl' && !scrolled,
                                 'shadow-lg': settings.header_shadow_on_scroll && scrolled,
                             }"
                             :style="{
                                 height: (scrolled && settings.header_shrink_on_scroll ? settings.header_height_scrolled : settings.header_height) + 'px',
                                 paddingLeft: settings.header_padding_x + 'px',
                                 paddingRight: settings.header_padding_x + 'px',
                                 backgroundColor: settings.header_gradient ? 'transparent' : hexToRgba(settings.header_bg_color, settings.header_bg_opacity),
                                 backgroundImage: settings.header_gradient ? `linear-gradient(${settings.header_gradient_direction}, ${settings.header_gradient_from}, ${settings.header_gradient_to})` : 'none',
                                 color: settings.header_text_color,
                                 backdropFilter: settings.header_blur ? `blur(${settings.header_blur_amount}px)` : 'none',
                                 borderBottom: settings.header_border_bottom ? `${settings.header_border_width}px solid ${settings.header_border_color}` : 'none',
                                 transitionDuration: settings.header_animation_duration + 'ms',
                                 transitionTimingFunction: settings.header_animation_easing,
                                 opacity: (settings.header_transparent || (settings.header_transparent_on_scroll && scrolled)) ? '0.95' : '1'
                             }">

                            <div class="flex items-center justify-between h-full">
                                <!-- Logo -->
                                <div class="flex items-center">
                                    <div class="font-bold text-xl transition-all"
                                         :style="{ fontSize: (scrolled && settings.header_shrink_on_scroll ? (settings.header_logo_height_scrolled / 2.5) : (settings.header_logo_height / 2.5)) + 'px' }">
                                        TP-Affiliate
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="hidden md:flex space-x-6">
                                    <a href="#" class="hover:transition-colors" :style="{ '--hover-color': settings.header_hover_color }" @mouseenter="$el.style.color = settings.header_hover_color" @mouseleave="$el.style.color = settings.header_text_color">หน้าแรก</a>
                                    <a href="#" class="hover:transition-colors" :style="{ '--hover-color': settings.header_hover_color }" @mouseenter="$el.style.color = settings.header_hover_color" @mouseleave="$el.style.color = settings.header_text_color">เกี่ยวกับเรา</a>
                                    <a href="#" class="hover:transition-colors" :style="{ '--hover-color': settings.header_hover_color }" @mouseenter="$el.style.color = settings.header_hover_color" @mouseleave="$el.style.color = settings.header_text_color">ติดต่อเรา</a>
                                </div>

                                <!-- CTA Button -->
                                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-all">
                                    สมัครสมาชิก
                                </button>
                            </div>
                        </nav>

                        <!-- Simulated Page Content -->
                        <div class="p-8" :class="{ 'pt-32': settings.header_position === 'fixed' || settings.header_position === 'absolute' }">
                            <div class="space-y-6">
                                <div class="h-64 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-2xl font-bold">
                                    Hero Section
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="h-32 bg-gray-300 dark:bg-gray-700 rounded-lg"></div>
                                    <div class="h-32 bg-gray-300 dark:bg-gray-700 rounded-lg"></div>
                                    <div class="h-32 bg-gray-300 dark:bg-gray-700 rounded-lg"></div>
                                </div>
                                <div class="h-48 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                                <div class="h-48 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                                <div class="h-48 bg-gray-200 dark:bg-gray-800 rounded-lg"></div>
                                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                                    เลื่อนดูพฤติกรรมของ Header 👆
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function headerEditor() {
    return {
        settings: @json($headerSettings),
        originalSettings: @json($headerSettings),
        saving: false,
        scrolled: false,

        init() {
            // Listen to preview scroll
            this.$refs.previewScroll.addEventListener('scroll', () => {
                this.scrolled = this.$refs.previewScroll.scrollTop > 50;
            });
        },

        updatePreview() {
            // This function is called on every change to trigger reactivity
            this.$nextTick(() => {
                // Force update
                this.$el.querySelectorAll('.preview-header').forEach(el => {
                    el.style.transition = `all ${this.settings.header_animation_duration}ms ${this.settings.header_animation_easing}`;
                });
            });
        },

        hexToRgba(hex, opacity) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${opacity / 100})`;
        },

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('{{ route('admin.header-editor.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.settings)
                });

                const data = await response.json();

                if (data.success) {
                    // Show success notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2';
                    notification.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>' + data.message + '</span>';
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);

                    this.originalSettings = { ...this.settings };
                } else {
                    throw new Error(data.message || 'ไม่สามารถบันทึกได้');
                }
            } catch (error) {
                console.error('Error:', error);
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2';
                notification.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg><span>เกิดข้อผิดพลาด: ' + error.message + '</span>';
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            } finally {
                this.saving = false;
            }
        },

        async resetSettings() {
            if (!confirm('คุณต้องการรีเซ็ตการตั้งค่าเป็นค่าเริ่มต้นหรือไม่?')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.header-editor.reset') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    throw new Error('ไม่สามารถรีเซ็ตได้');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        }
    }
}
</script>

<style>
/* Custom Slider Thumb for Dark Mode */
.slider-thumb::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider-thumb::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider-thumb::-webkit-slider-thumb:hover {
    background: #2563eb;
}

.slider-thumb::-moz-range-thumb:hover {
    background: #2563eb;
}

/* Dark mode slider track */
.dark .slider-thumb::-webkit-slider-runnable-track {
    background: #374151;
}

.dark .slider-thumb::-moz-range-track {
    background: #374151;
}
</style>
@endpush

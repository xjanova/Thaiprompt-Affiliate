@extends('layouts.admin-v3')

@section('title', 'Rich Menu Editor - แม่หมอจันทรา')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="richMenuEditor()" x-init="loadConfig()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🎨 Rich Menu Editor
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">แก้ไขปุ่ม สี ข้อความ Rich Menu แม่หมอจันทรา</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.fortune.rich-menu.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>📤</span> Deploy
            </a>
            <a href="{{ route('admin.fortune.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>📊</span> Dashboard
            </a>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
        <button @click="activeTab = 'config'"
                :class="activeTab === 'config'
                    ? 'border-purple-500 text-purple-600 dark:text-purple-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-6 py-3 text-sm font-semibold border-b-2 transition">
            ✏️ Config Editor
        </button>
        <button @click="activeTab = 'custom'"
                :class="activeTab === 'custom'
                    ? 'border-purple-500 text-purple-600 dark:text-purple-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-6 py-3 text-sm font-semibold border-b-2 transition">
            🖼️ Custom Upload
        </button>
    </div>

    {{-- ========== Tab 1: Config Editor ========== --}}
    <div x-show="activeTab === 'config'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Editor Panel (ซ้าย) --}}
            <div class="space-y-6">
                {{-- Theme Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        🎨 ตั้งค่า Theme
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Gradient Start --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นหลัง (บน)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="config.theme.bg_gradient_start"
                                       class="w-10 h-10 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                <input type="text" x-model="config.theme.bg_gradient_start"
                                       class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                        </div>

                        {{-- Gradient End --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นหลัง (ล่าง)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="config.theme.bg_gradient_end"
                                       class="w-10 h-10 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                <input type="text" x-model="config.theme.bg_gradient_end"
                                       class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                        </div>

                        {{-- Grid Line Color --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีเส้นแบ่ง</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="config.theme.grid_line_color"
                                       class="w-10 h-10 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                <input type="text" x-model="config.theme.grid_line_color"
                                       class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                        </div>

                        {{-- Branding Color --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี Branding</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="config.theme.branding_color"
                                       class="w-10 h-10 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                <input type="text" x-model="config.theme.branding_color"
                                       class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                        </div>
                    </div>

                    {{-- Branding Text --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความ Branding</label>
                        <input type="text" x-model="config.theme.branding_text"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>

                    {{-- Toggles --}}
                    <div class="mt-4 flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="config.theme.show_stars"
                                   class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">แสดงดาว</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="config.theme.show_moon"
                                   class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">แสดงพระจันทร์</span>
                        </label>
                    </div>
                </div>

                {{-- Button Editor --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        🔘 แก้ไขปุ่ม (<span x-text="config.buttons.length"></span> ปุ่ม)
                    </h3>

                    {{-- Button Tabs --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        <template x-for="(btn, idx) in config.buttons" :key="idx">
                            <button @click="activeButton = idx"
                                    :class="activeButton === idx
                                        ? 'bg-purple-600 text-white shadow-lg'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="px-4 py-2 rounded-lg text-sm font-semibold transition">
                                <span x-text="'ปุ่ม ' + (idx + 1)"></span>
                                <span class="text-xs opacity-75" x-text="': ' + (btn.label || '-')"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Active Button Editor --}}
                    <template x-if="config.buttons[activeButton]">
                        <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            {{-- Label + Subtitle --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความหลัก</label>
                                    <input type="text" x-model="config.buttons[activeButton].label"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความรอง</label>
                                    <input type="text" x-model="config.buttons[activeButton].subtitle"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                            </div>

                            {{-- Extra Text --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความเพิ่มเติม (บรรทัด 3)</label>
                                <input type="text" x-model="config.buttons[activeButton].extra_text"
                                       placeholder="(ไม่บังคับ)"
                                       class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            </div>

                            {{-- Icon + Glow --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ไอคอน</label>
                                    <select x-model="config.buttons[activeButton].icon"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                        <option value="crystal_ball">🔮 ลูกแก้ว (Crystal Ball)</option>
                                        <option value="star">⭐ ดาว (Star)</option>
                                        <option value="scroll">📜 ม้วนกระดาษ (Scroll)</option>
                                        <option value="status">✅ สถานะ (Status)</option>
                                        <option value="chart">📊 กราฟ (Chart)</option>
                                        <option value="warning">⚠️ คำเตือน (Warning)</option>
                                        <option value="info">ℹ️ ข้อมูล (Info)</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="config.buttons[activeButton].glow"
                                               class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">เอฟเฟกต์เรืองแสง (Glow)</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Colors --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีข้อความ</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="config.buttons[activeButton].text_color"
                                               class="w-8 h-8 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                        <input type="text" x-model="config.buttons[activeButton].text_color"
                                               class="flex-1 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs font-mono">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี Subtitle</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="config.buttons[activeButton].subtitle_color"
                                               class="w-8 h-8 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                        <input type="text" x-model="config.buttons[activeButton].subtitle_color"
                                               class="flex-1 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs font-mono">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นปุ่ม</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color"
                                               :value="config.buttons[activeButton].bg_color || '#000000'"
                                               @input="config.buttons[activeButton].bg_color = $event.target.value"
                                               class="w-8 h-8 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                                        <input type="text"
                                               :value="config.buttons[activeButton].bg_color || ''"
                                               @input="config.buttons[activeButton].bg_color = $event.target.value || null"
                                               placeholder="ไม่มี"
                                               class="flex-1 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs font-mono">
                                    </div>
                                    <button @click="config.buttons[activeButton].bg_color = null"
                                            class="mt-1 text-xs text-red-500 hover:text-red-700">ล้างสีพื้น</button>
                                </div>
                            </div>

                            {{-- Font Sizes --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        ขนาดตัวอักษร: <span class="font-mono text-purple-600 dark:text-purple-400" x-text="config.buttons[activeButton].font_size + 'px'"></span>
                                    </label>
                                    <input type="range" x-model.number="config.buttons[activeButton].font_size"
                                           min="24" max="96" step="2"
                                           class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg cursor-pointer accent-purple-600">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        ขนาด Subtitle: <span class="font-mono text-purple-600 dark:text-purple-400" x-text="config.buttons[activeButton].subtitle_size + 'px'"></span>
                                    </label>
                                    <input type="range" x-model.number="config.buttons[activeButton].subtitle_size"
                                           min="16" max="64" step="2"
                                           class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg cursor-pointer accent-purple-600">
                                </div>
                            </div>

                            {{-- Action --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action Type</label>
                                    <select x-model="config.buttons[activeButton].action_type"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                        <option value="message">Message (ส่งข้อความ)</option>
                                        <option value="postback">Postback (ส่ง data)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action Data</label>
                                    <input type="text" x-model="config.buttons[activeButton].action_data"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Text</label>
                                    <input type="text" x-model="config.buttons[activeButton].display_text"
                                           placeholder="(postback เท่านั้น)"
                                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Preview Panel (ขวา) --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        👁️ Live Preview
                    </h3>

                    {{-- Preview Image --}}
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden min-h-[200px] flex items-center justify-center">
                        <template x-if="previewUrl">
                            <img :src="previewUrl + '?t=' + Date.now()" alt="Preview Rich Menu"
                                 class="w-full rounded-lg">
                        </template>
                        <template x-if="!previewUrl && !previewLoading">
                            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <div class="text-4xl mb-2">🖼️</div>
                                <p>กด "Preview" เพื่อดูตัวอย่าง</p>
                            </div>
                        </template>
                        <template x-if="previewLoading">
                            <div class="text-center py-12">
                                <svg class="animate-spin h-8 w-8 text-purple-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">กำลังสร้าง preview...</p>
                            </div>
                        </template>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        <button @click="generatePreview()"
                                :disabled="loading"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-300 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl text-sm">
                            <template x-if="previewLoading">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            🖼️ Preview
                        </button>
                        <button @click="saveConfigDraft()"
                                :disabled="loading"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl text-sm">
                            💾 Save Draft
                        </button>
                        <button @click="deployFromEditor('config')"
                                :disabled="loading"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl text-sm">
                            <template x-if="deployLoading">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            🚀 Deploy to LINE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== Tab 2: Custom Upload ========== --}}
    <div x-show="activeTab === 'custom'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Upload Panel (ซ้าย) --}}
            <div class="space-y-6">
                {{-- Upload Zone --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        📁 อัปโหลดภาพ Rich Menu
                    </h3>

                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-purple-400 dark:hover:border-purple-500 transition cursor-pointer"
                         @click="$refs.fileInput.click()"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleFileDrop($event)"
                         :class="dragOver ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : ''">
                        <input type="file" x-ref="fileInput" @change="handleFileSelect($event)"
                               accept="image/png,image/jpeg" class="hidden">
                        <div class="text-4xl mb-3">📤</div>
                        <p class="text-gray-600 dark:text-gray-400 font-semibold">คลิกหรือลากไฟล์มาวาง</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">PNG/JPG ขนาด 2500x1686 หรือ 2500x843</p>
                    </div>

                    <template x-if="uploadLoading">
                        <div class="mt-4 flex items-center gap-3 text-purple-600 dark:text-purple-400">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-semibold">กำลังอัปโหลด...</span>
                        </div>
                    </template>
                </div>

                {{-- Areas Editor --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            📐 พื้นที่คลิก (Areas)
                        </h3>
                        <button @click="useDefaultAreas()"
                                class="text-sm px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                            ใช้ Default 6 ปุ่ม
                        </button>
                    </div>

                    {{-- Areas List --}}
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <template x-for="(area, idx) in customAreas" :key="idx">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 flex items-start gap-3">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 px-2 py-0.5 rounded"
                                              x-text="'Area ' + (idx + 1)"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono"
                                              x-text="area.bounds.x + ',' + area.bounds.y + ' ' + area.bounds.width + 'x' + area.bounds.height"></span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <select x-model="area.action.type" class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option value="message">Message</option>
                                            <option value="postback">Postback</option>
                                        </select>
                                        <input type="text"
                                               :value="area.action.type === 'message' ? area.action.text : area.action.data"
                                               @input="area.action.type === 'message' ? (area.action.text = $event.target.value) : (area.action.data = $event.target.value)"
                                               placeholder="Action data"
                                               class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono col-span-2">
                                    </div>
                                </div>
                                <button @click="customAreas.splice(idx, 1)"
                                        class="text-red-400 hover:text-red-600 mt-1 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <template x-if="customAreas.length === 0">
                        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">ยังไม่มี area — กด "ใช้ Default 6 ปุ่ม" หรือวาดบนภาพ</p>
                    </template>
                </div>
            </div>

            {{-- Preview + Canvas Panel (ขวา) --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        🖼️ ภาพที่อัปโหลด
                    </h3>

                    {{-- Canvas Area --}}
                    <div class="relative bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden min-h-[200px]">
                        <template x-if="customImageUrl">
                            <div class="relative">
                                <img :src="customImageUrl" alt="Custom Rich Menu" class="w-full rounded-lg"
                                     x-ref="customImage"
                                     @load="canvasReady = true">

                                {{-- Area Overlays --}}
                                <template x-for="(area, idx) in customAreas" :key="'overlay-' + idx">
                                    <div class="absolute border-2 border-red-400 bg-red-400/10 flex items-center justify-center cursor-pointer hover:bg-red-400/20 transition"
                                         :style="getAreaOverlayStyle(area.bounds)"
                                         :title="'Area ' + (idx + 1) + ': ' + (area.action.text || area.action.data || '')">
                                        <span class="text-red-500 font-bold text-xs bg-white/80 dark:bg-gray-900/80 px-1 rounded"
                                              x-text="idx + 1"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!customImageUrl">
                            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                <div class="text-4xl mb-2">🖼️</div>
                                <p>อัปโหลดภาพเพื่อดูตัวอย่าง</p>
                            </div>
                        </template>
                    </div>

                    {{-- Deploy Custom --}}
                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        <button @click="deployFromEditor('custom')"
                                :disabled="loading || !customImagePath || customAreas.length === 0"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-xl text-sm">
                            <template x-if="deployLoading">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            🚀 Deploy Custom Image
                        </button>
                    </div>

                    <template x-if="!customImagePath || customAreas.length === 0">
                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            <template x-if="!customImagePath">
                                <span>กรุณาอัปโหลดภาพก่อน</span>
                            </template>
                            <template x-if="customImagePath && customAreas.length === 0">
                                <span>กรุณากำหนด areas ก่อน deploy</span>
                            </template>
                        </p>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== Messages ========== --}}
    <div x-show="successMessage" x-cloak x-transition
         class="fixed bottom-6 right-6 z-50 max-w-md bg-green-50 dark:bg-green-900/90 border border-green-200 dark:border-green-800 rounded-xl p-4 shadow-2xl">
        <div class="flex items-start gap-3">
            <span class="text-green-500 text-xl">✅</span>
            <div class="flex-1">
                <p class="text-green-700 dark:text-green-300 font-semibold text-sm" x-text="successMessage"></p>
            </div>
            <button @click="successMessage = null" class="text-green-400 hover:text-green-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div x-show="errorMessage" x-cloak x-transition
         class="fixed bottom-6 right-6 z-50 max-w-md bg-red-50 dark:bg-red-900/90 border border-red-200 dark:border-red-800 rounded-xl p-4 shadow-2xl">
        <div class="flex items-start gap-3">
            <span class="text-red-500 text-xl">❌</span>
            <div class="flex-1">
                <p class="text-red-700 dark:text-red-300 font-semibold text-sm" x-text="errorMessage"></p>
            </div>
            <button @click="errorMessage = null" class="text-red-400 hover:text-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function richMenuEditor() {
    return {
        // สถานะ
        activeTab: 'config',
        activeButton: 0,
        loading: false,
        previewLoading: false,
        deployLoading: false,
        uploadLoading: false,
        dragOver: false,
        canvasReady: false,
        successMessage: null,
        errorMessage: null,
        previewUrl: null,

        // Config Editor
        config: {
            theme: {
                bg_gradient_start: '#0d0521',
                bg_gradient_end: '#1a0a3e',
                grid_line_color: '#4C1D95',
                branding_text: '~~ แม่หมอจันทราพยากรณ์ ~~',
                branding_color: '#FFD700',
                show_stars: true,
                show_moon: true,
            },
            buttons: [],
        },

        // Custom Upload
        customImagePath: null,
        customImageUrl: null,
        customAreas: [],

        // CSRF token
        get csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        // === โหลด config เริ่มต้น ===
        async loadConfig() {
            try {
                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.config") }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.success && data.config) {
                    this.config = data.config;
                    if (data.editor_mode) {
                        this.activeTab = data.editor_mode;
                    }
                    if (data.image_url) {
                        this.previewUrl = data.image_url;
                        if (data.editor_mode === 'custom') {
                            this.customImageUrl = data.image_url;
                        }
                    }
                }
            } catch (e) {
                console.error('โหลด config ล้มเหลว:', e);
            }
        },

        // === Config: สร้าง Preview ===
        async generatePreview() {
            this.previewLoading = true;
            this.loading = true;
            this.clearMessages();

            try {
                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ config: this.config }),
                });
                const data = await res.json();

                if (data.success) {
                    this.previewUrl = data.preview_url;
                    this.successMessage = 'สร้าง Preview สำเร็จ!';
                    this.autoHideSuccess();
                } else {
                    this.errorMessage = data.error || 'สร้าง Preview ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.previewLoading = false;
                this.loading = false;
            }
        },

        // === Config: บันทึก Draft ===
        async saveConfigDraft() {
            this.loading = true;
            this.clearMessages();

            try {
                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.save-config") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        config: this.config,
                        editor_mode: this.activeTab,
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    this.successMessage = 'บันทึก Draft สำเร็จ!';
                    this.autoHideSuccess();
                } else {
                    this.errorMessage = data.error || 'บันทึก Draft ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        // === Deploy (ทั้ง config และ custom) ===
        async deployFromEditor(mode) {
            const confirmMsg = mode === 'config'
                ? 'ยืนยันการ Deploy Rich Menu จาก Config?\n\nRich Menu เดิมจะถูกแทนที่'
                : 'ยืนยันการ Deploy ภาพ Custom Rich Menu?\n\nRich Menu เดิมจะถูกแทนที่';

            if (!confirm(confirmMsg)) return;

            this.deployLoading = true;
            this.loading = true;
            this.clearMessages();

            try {
                const body = {
                    editor_mode: mode,
                };

                if (mode === 'config') {
                    body.config = this.config;
                } else {
                    body.custom_image_path = this.customImagePath;
                    body.custom_areas = this.customAreas;
                }

                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();

                if (data.success) {
                    this.successMessage = 'Deploy Rich Menu สำเร็จ! ID: ' + (data.rich_menu_id || '');
                    setTimeout(() => window.location.reload(), 3000);
                } else {
                    this.errorMessage = data.error || 'Deploy ไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.deployLoading = false;
                this.loading = false;
            }
        },

        // === Custom: อัปโหลดภาพ ===
        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) await this.uploadFile(file);
        },

        async handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file) await this.uploadFile(file);
        },

        async uploadFile(file) {
            if (!file.type.match(/image\/(png|jpeg|jpg)/)) {
                this.errorMessage = 'รองรับเฉพาะไฟล์ PNG/JPG';
                return;
            }

            this.uploadLoading = true;
            this.loading = true;
            this.clearMessages();

            try {
                const formData = new FormData();
                formData.append('image', file);

                const res = await fetch('{{ route("admin.fortune.rich-menu.editor.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();

                if (data.success) {
                    this.customImagePath = data.image_path;
                    this.customImageUrl = data.image_url;
                    this.successMessage = 'อัปโหลดภาพสำเร็จ! (' + data.dimensions.width + 'x' + data.dimensions.height + ')';
                    this.autoHideSuccess();
                } else {
                    this.errorMessage = data.error || 'อัปโหลดไม่สำเร็จ';
                }
            } catch (e) {
                this.errorMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            } finally {
                this.uploadLoading = false;
                this.loading = false;
            }
        },

        // === Custom: ใช้ Default Areas (6 ปุ่ม 3x2 grid) ===
        useDefaultAreas() {
            const colWidths = [833, 834, 833];
            const rowHeight = 843;
            const defaultActions = [
                { type: 'message', text: 'ดูดวง' },
                { type: 'postback', data: 'action=deep_reading', displayText: 'ดูดวงละเอียด' },
                { type: 'postback', data: 'action=view_last_reading', displayText: 'ดูคำทำนายล่าสุด' },
                { type: 'postback', data: 'action=check_status', displayText: 'ดูสถานะสิทธิ์' },
                { type: 'postback', data: 'action=report_payment', displayText: 'แจ้งปัญหาโอน' },
                { type: 'postback', data: 'action=help', displayText: 'วิธีใช้งาน' },
            ];

            this.customAreas = [];
            for (let i = 0; i < 6; i++) {
                const row = Math.floor(i / 3);
                const col = i % 3;
                let x = 0;
                for (let c = 0; c < col; c++) x += colWidths[c];

                this.customAreas.push({
                    bounds: {
                        x: x,
                        y: row * rowHeight,
                        width: colWidths[col],
                        height: rowHeight,
                    },
                    action: { ...defaultActions[i] },
                });
            }

            this.successMessage = 'โหลด Default Areas 6 ปุ่ม (3x2) สำเร็จ!';
            this.autoHideSuccess();
        },

        // === Overlay style สำหรับ areas บนภาพ custom ===
        getAreaOverlayStyle(bounds) {
            // คำนวณ % จากภาพ 2500x1686
            const imageWidth = 2500;
            const imageHeight = 1686;
            return {
                left: (bounds.x / imageWidth * 100) + '%',
                top: (bounds.y / imageHeight * 100) + '%',
                width: (bounds.width / imageWidth * 100) + '%',
                height: (bounds.height / imageHeight * 100) + '%',
            };
        },

        // === Helpers ===
        clearMessages() {
            this.successMessage = null;
            this.errorMessage = null;
        },

        autoHideSuccess() {
            setTimeout(() => { this.successMessage = null; }, 5000);
        },
    };
}
</script>
@endpush
@endsection

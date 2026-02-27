@extends('layouts.seller')

@section('title', 'ปรับแต่ง Layout ร้านค้า - ' . ($store->store_name ?? 'ร้านค้าของคุณ'))

@push('styles')
<style>
    /* Color Picker Custom Styles */
    input[type="color"] {
        -webkit-appearance: none;
        width: 100%;
        height: 48px;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        padding: 0;
    }
    input[type="color"]::-webkit-color-swatch-wrapper {
        padding: 4px;
        border-radius: 12px;
    }
    input[type="color"]::-webkit-color-swatch {
        border: none;
        border-radius: 8px;
    }

    /* Tab Animation */
    .tab-panel {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Preview Frame */
    .preview-frame {
        border: 2px solid transparent;
        background: linear-gradient(white, white) padding-box,
                    linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899) border-box;
        border-radius: 16px;
    }

    /* Save Button Animation */
    .save-btn.saving {
        pointer-events: none;
    }
    .save-btn.saving::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-left: 8px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div x-data="layoutEditor()" x-init="init()" class="min-h-screen pb-20 lg:pb-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-3xl shadow-2xl p-6 md:p-8 text-white mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-white/20 flex items-center justify-center text-3xl md:text-4xl shadow-lg border-4 border-white/30">
                    🎨
                </div>
                <div>
                    <h1 class="text-2xl md:text-4xl font-bold mb-1">ปรับแต่ง Layout ร้านค้า</h1>
                    <p class="text-indigo-100 text-sm md:text-base">ออกแบบหน้าร้านให้สวยงามตามสไตล์ของคุณ</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- สถานะการเผยแพร่ --}}
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 rounded-xl">
                    <template x-if="settings.is_published">
                        <span class="flex items-center gap-1 text-green-300">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            เผยแพร่แล้ว
                        </span>
                    </template>
                    <template x-if="!settings.is_published">
                        <span class="flex items-center gap-1 text-yellow-300">
                            <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                            ฉบับร่าง
                        </span>
                    </template>
                </div>

                {{-- ปุ่มดู Preview --}}
                <button @click="showPreviewModal = true" type="button"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl text-sm transition flex items-center gap-2">
                    👁️ ดูตัวอย่าง
                </button>

                {{-- ปุ่มเผยแพร่/ยกเลิก --}}
                <template x-if="settings.is_published">
                    <button @click="unpublish()" type="button"
                            class="px-4 py-2 bg-red-500/80 hover:bg-red-600/80 rounded-xl text-sm transition">
                        ยกเลิกเผยแพร่
                    </button>
                </template>
                <template x-if="!settings.is_published">
                    <button @click="publish()" type="button"
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 rounded-xl text-sm transition">
                        เผยแพร่ Layout
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Main Content with Tabs --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left Panel: Settings --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Tab Navigation --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-2 overflow-x-auto">
                <div class="flex space-x-1 min-w-max">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button @click="activeTab = tab.id"
                                :class="activeTab === tab.id
                                    ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-lg'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'"
                                class="px-4 py-3 rounded-xl text-sm font-semibold transition whitespace-nowrap flex items-center gap-2">
                            <span x-text="tab.icon"></span>
                            <span x-text="tab.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Tab Content --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 md:p-8">
                {{-- General Tab --}}
                <div x-show="activeTab === 'general'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>⚙️</span> ตั้งค่าทั่วไป
                    </h2>

                    {{-- Theme Colors --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                            <span>🎨</span> สีธีม
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">สีหลัก</label>
                                <div class="relative">
                                    <input type="color" x-model="settings.primary_color"
                                           @change="updatePreview()"
                                           class="w-full">
                                    <span class="absolute -bottom-5 left-0 text-xs text-gray-500" x-text="settings.primary_color"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">สีรอง</label>
                                <div class="relative">
                                    <input type="color" x-model="settings.secondary_color"
                                           @change="updatePreview()"
                                           class="w-full">
                                    <span class="absolute -bottom-5 left-0 text-xs text-gray-500" x-text="settings.secondary_color"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">สีเน้น</label>
                                <div class="relative">
                                    <input type="color" x-model="settings.accent_color"
                                           @change="updatePreview()"
                                           class="w-full">
                                    <span class="absolute -bottom-5 left-0 text-xs text-gray-500" x-text="settings.accent_color"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">สีพื้นหลัง</label>
                                <div class="relative">
                                    <input type="color" x-model="settings.background_color"
                                           @change="updatePreview()"
                                           class="w-full">
                                    <span class="absolute -bottom-5 left-0 text-xs text-gray-500" x-text="settings.background_color"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Layout Templates --}}
                    <div class="space-y-4 pt-6">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                            <span>📐</span> เทมเพลต Layout
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            เลือกเทมเพลตแล้วกด "ใช้เทมเพลต" เพื่อปรับสี, Header, Product Card ให้ตรงตามแบบ (รูปภาพ/ข้อความ/Social ไม่ถูกเปลี่ยน)
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            @php $presets = \App\Models\StoreLayoutSetting::getLayoutPresets(); @endphp
                            @foreach($layoutStyles as $key => $style)
                            @php $preset = $presets[$key] ?? null; @endphp
                            <div :class="settings.layout_style === '{{ $key }}'
                                       ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                       : 'hover:bg-gray-50 dark:hover:bg-slate-700'"
                                 class="relative block p-4 rounded-xl border-2 border-gray-200 dark:border-slate-600 transition">
                                {{-- Radio + Click area --}}
                                <label class="cursor-pointer block">
                                    <input type="radio" x-model="settings.layout_style" value="{{ $key }}"
                                           @change="updatePreview()" class="sr-only">
                                    <div class="text-center">
                                        {{-- Preview gradient bar --}}
                                        @if($preset)
                                        <div class="w-full h-16 rounded-lg mb-2 flex items-center justify-center text-3xl overflow-hidden"
                                             style="background: linear-gradient(135deg, {{ $preset['colors']['primary_color'] }}, {{ $preset['colors']['secondary_color'] }})">
                                            <span class="text-white drop-shadow-lg">{{ $preset['icon'] }}</span>
                                        </div>
                                        {{-- Color swatches --}}
                                        <div class="flex justify-center gap-1 mb-2">
                                            <span class="w-5 h-5 rounded-full border border-gray-200" style="background: {{ $preset['colors']['primary_color'] }}"></span>
                                            <span class="w-5 h-5 rounded-full border border-gray-200" style="background: {{ $preset['colors']['secondary_color'] }}"></span>
                                            <span class="w-5 h-5 rounded-full border border-gray-200" style="background: {{ $preset['colors']['accent_color'] }}"></span>
                                        </div>
                                        @endif
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $style['name'] }}</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $style['description'] }}</p>
                                    </div>
                                </label>
                                {{-- Apply Template Button --}}
                                <button @click="applyTemplate('{{ $key }}')"
                                        :disabled="saving"
                                        class="mt-3 w-full text-xs font-semibold py-2 px-3 rounded-lg transition
                                               bg-indigo-500 hover:bg-indigo-600 text-white
                                               disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!saving">ใช้เทมเพลตนี้</span>
                                    <span x-show="saving" x-cloak>กำลังใช้...</span>
                                </button>
                                {{-- Active check --}}
                                <div x-show="settings.layout_style === '{{ $key }}'"
                                     class="absolute top-2 right-2 w-6 h-6 bg-indigo-500 text-white rounded-full flex items-center justify-center text-sm">
                                    ✓
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Products Per Row --}}
                    <div class="space-y-4 pt-6">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                            <span>📦</span> จำนวนสินค้าต่อแถว
                        </h3>
                        <div class="flex flex-wrap gap-3">
                            <template x-for="n in [2, 3, 4, 5, 6]" :key="n">
                                <button @click="settings.products_per_row = n; updatePreview()"
                                        :class="settings.products_per_row === n
                                            ? 'bg-indigo-500 text-white border-indigo-500'
                                            : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-slate-600 hover:border-indigo-400'"
                                        class="w-12 h-12 rounded-xl border-2 font-bold transition"
                                        x-text="n">
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button @click="saveGeneral()"
                                :disabled="saving"
                                :class="saving ? 'opacity-50 cursor-wait' : ''"
                                class="save-btn px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white font-semibold rounded-xl shadow-lg transition flex items-center gap-2">
                            <span x-show="!saving">💾 บันทึกการตั้งค่าทั่วไป</span>
                            <span x-show="saving">กำลังบันทึก...</span>
                        </button>
                    </div>
                </div>

                {{-- Header Tab --}}
                <div x-show="activeTab === 'header'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>📌</span> ส่วนหัวร้านค้า
                    </h2>

                    {{-- Header Style --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">รูปแบบ Header</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($headerStyles as $key => $name)
                            <label :class="settings.header_style === '{{ $key }}'
                                       ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                       : 'hover:bg-gray-50 dark:hover:bg-slate-700'"
                                   class="relative block p-4 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer transition text-center">
                                <input type="radio" x-model="settings.header_style" value="{{ $key }}"
                                       @change="updatePreview()" class="sr-only">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Header Height --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">ความสูง Header</h3>
                        <div class="flex items-center gap-4">
                            <input type="range" x-model="settings.header_height" min="100" max="500" step="10"
                                   @input="updatePreview()"
                                   class="flex-1 h-2 bg-gray-200 dark:bg-slate-600 rounded-lg appearance-none cursor-pointer">
                            <span class="text-sm font-mono bg-gray-100 dark:bg-slate-700 px-3 py-1 rounded-lg" x-text="settings.header_height + 'px'"></span>
                        </div>
                    </div>

                    {{-- Header Image Upload --}}
                    <div x-show="settings.header_style === 'image'" class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">รูปภาพพื้นหลัง Header</h3>
                        <div class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-xl p-6">
                            <template x-if="settings.header_image">
                                <div class="relative">
                                    <img :src="'/storage/' + settings.header_image" class="w-full h-48 object-cover rounded-lg">
                                    <button @click="deleteHeaderImage()" type="button"
                                            class="absolute top-2 right-2 w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition">
                                        ✕
                                    </button>
                                </div>
                            </template>
                            <template x-if="!settings.header_image">
                                <div class="text-center">
                                    <input type="file" @change="uploadHeaderImage($event)" accept="image/*"
                                           class="hidden" id="header-image-input">
                                    <label for="header-image-input" class="cursor-pointer">
                                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-3xl mb-4">
                                            📷
                                        </div>
                                        <p class="text-gray-600 dark:text-gray-300">คลิกเพื่ออัพโหลดรูปภาพ</p>
                                        <p class="text-xs text-gray-400 mt-1">รองรับ: JPG, PNG, GIF, WebP (สูงสุด 4MB)</p>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Show/Hide Elements --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">แสดง/ซ่อนองค์ประกอบ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer">
                                <span class="text-gray-700 dark:text-gray-200">แสดงโลโก้ร้าน</span>
                                <input type="checkbox" x-model="settings.show_store_logo" @change="updatePreview()"
                                       class="w-5 h-5 text-indigo-500 rounded">
                            </label>
                            <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer">
                                <span class="text-gray-700 dark:text-gray-200">แสดงชื่อร้าน</span>
                                <input type="checkbox" x-model="settings.show_store_name" @change="updatePreview()"
                                       class="w-5 h-5 text-indigo-500 rounded">
                            </label>
                            <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer">
                                <span class="text-gray-700 dark:text-gray-200">แสดงคำอธิบายร้าน</span>
                                <input type="checkbox" x-model="settings.show_store_description" @change="updatePreview()"
                                       class="w-5 h-5 text-indigo-500 rounded">
                            </label>
                            <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer">
                                <span class="text-gray-700 dark:text-gray-200">แสดงสถิติร้าน</span>
                                <input type="checkbox" x-model="settings.show_store_stats" @change="updatePreview()"
                                       class="w-5 h-5 text-indigo-500 rounded">
                            </label>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button @click="saveGeneral()"
                                :disabled="saving"
                                class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white font-semibold rounded-xl shadow-lg transition">
                            💾 บันทึกการตั้งค่า Header
                        </button>
                    </div>
                </div>

                {{-- Slider Tab --}}
                <div x-show="activeTab === 'slider'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>🖼️</span> Banner Slider
                    </h2>

                    {{-- Enable Slider --}}
                    <label class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-xl cursor-pointer">
                        <div>
                            <span class="text-lg font-semibold text-gray-800 dark:text-white">เปิดใช้งาน Slider</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">แสดง Banner แบบสไลด์บนหน้าร้านค้า</p>
                        </div>
                        <input type="checkbox" x-model="settings.slider_enabled" @change="updatePreview()"
                               class="w-6 h-6 text-indigo-500 rounded">
                    </label>

                    <div x-show="settings.slider_enabled" class="space-y-6">
                        {{-- Slider Settings --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">ความเร็ว Autoplay (ms)</label>
                                <input type="number" x-model.number="settings.slider_autoplay_speed" min="1000" max="10000" step="500"
                                       class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Effect</label>
                                <select x-model="settings.slider_effect"
                                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-indigo-500 transition">
                                    @foreach($sliderEffects as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="settings.slider_show_arrows" class="w-4 h-4 text-indigo-500 rounded">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">แสดงปุ่มเลื่อน</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="settings.slider_show_dots" class="w-4 h-4 text-indigo-500 rounded">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">แสดงจุดบอกตำแหน่ง</span>
                                </label>
                            </div>
                        </div>

                        {{-- Slider Images --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">รูปภาพ Slider</h3>
                                <button @click="addSlide()" type="button"
                                        class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition">
                                    + เพิ่มสไลด์
                                </button>
                            </div>

                            <div class="space-y-4">
                                <template x-for="(slide, index) in settings.slider_images || []" :key="index">
                                    <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-4 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-700 dark:text-gray-200">สไลด์ #<span x-text="index + 1"></span></span>
                                            <button @click="removeSlide(index)" type="button"
                                                    class="text-red-500 hover:text-red-600 transition">
                                                🗑️ ลบ
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {{-- Image Upload --}}
                                            <div>
                                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">รูปภาพ</label>
                                                <template x-if="slide.image">
                                                    <div class="relative">
                                                        <img :src="slide.image.startsWith('http') ? slide.image : '/storage/' + slide.image"
                                                             class="w-full h-32 object-cover rounded-lg">
                                                        <button @click="deleteSlideImage(index)" type="button"
                                                                class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs">✕</button>
                                                    </div>
                                                </template>
                                                <template x-if="!slide.image">
                                                    <div class="border-2 border-dashed border-gray-300 dark:border-slate-600 rounded-lg p-4 text-center">
                                                        <input type="file" @change="uploadSlideImage($event, index)" accept="image/*"
                                                               class="hidden" :id="'slide-image-' + index">
                                                        <label :for="'slide-image-' + index" class="cursor-pointer text-sm text-gray-500">
                                                            📷 อัพโหลดรูป
                                                        </label>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Slide Details --}}
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">หัวข้อ</label>
                                                    <input type="text" x-model="slide.title" placeholder="หัวข้อสไลด์"
                                                           class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-600 dark:bg-slate-800 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">ลิงก์</label>
                                                    <input type="text" x-model="slide.link" placeholder="https://..."
                                                           class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-600 dark:bg-slate-800 text-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!settings.slider_images || settings.slider_images.length === 0">
                                    <div class="text-center py-8 text-gray-400">
                                        <span class="text-4xl">📸</span>
                                        <p class="mt-2">ยังไม่มีสไลด์ กดปุ่ม "เพิ่มสไลด์" เพื่อเริ่มต้น</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button @click="saveSlider()"
                                :disabled="saving"
                                class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white font-semibold rounded-xl shadow-lg transition">
                            💾 บันทึกการตั้งค่า Slider
                        </button>
                    </div>
                </div>

                {{-- Featured Tab --}}
                <div x-show="activeTab === 'featured'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>⭐</span> สินค้าแนะนำ
                    </h2>

                    <label class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl cursor-pointer">
                        <div>
                            <span class="text-lg font-semibold text-gray-800 dark:text-white">แสดงสินค้าแนะนำ</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">แสดงส่วนสินค้าแนะนำบนหน้าร้านค้า</p>
                        </div>
                        <input type="checkbox" x-model="settings.show_featured_products" @change="updatePreview()"
                               class="w-6 h-6 text-amber-500 rounded">
                    </label>

                    <div x-show="settings.show_featured_products" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">หัวข้อส่วนสินค้าแนะนำ</label>
                            <input type="text" x-model="settings.featured_title"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-amber-500 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">จำนวนสินค้าที่แสดง</label>
                            <div class="flex flex-wrap gap-3">
                                <template x-for="n in [4, 6, 8, 12, 16, 24]" :key="n">
                                    <button @click="settings.featured_products_count = n"
                                            :class="settings.featured_products_count === n
                                                ? 'bg-amber-500 text-white border-amber-500'
                                                : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-slate-600'"
                                            class="w-12 h-12 rounded-xl border-2 font-bold transition"
                                            x-text="n">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button @click="saveFeatured()"
                                :disabled="saving"
                                class="px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-xl shadow-lg transition">
                            💾 บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>

                {{-- Social Tab --}}
                <div x-show="activeTab === 'social'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>🌐</span> Social Links
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                <span class="text-xl">📘</span> Facebook
                            </label>
                            <input type="url" x-model="socialLinks.facebook" placeholder="https://facebook.com/yourpage"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-blue-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                <span class="text-xl">💚</span> LINE Official
                            </label>
                            <input type="text" x-model="socialLinks.line" placeholder="@yourlineid"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-green-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                <span class="text-xl">📸</span> Instagram
                            </label>
                            <input type="url" x-model="socialLinks.instagram" placeholder="https://instagram.com/youraccount"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-pink-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                <span class="text-xl">🎵</span> TikTok
                            </label>
                            <input type="url" x-model="socialLinks.tiktok" placeholder="https://tiktok.com/@youraccount"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-gray-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                <span class="text-xl">📺</span> YouTube
                            </label>
                            <input type="url" x-model="socialLinks.youtube" placeholder="https://youtube.com/c/yourchannel"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-red-500 transition">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button @click="saveSocialLinks()"
                                :disabled="saving"
                                class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white font-semibold rounded-xl shadow-lg transition">
                            💾 บันทึก Social Links
                        </button>
                    </div>
                </div>

                {{-- SEO Tab --}}
                <div x-show="activeTab === 'seo'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>🔍</span> SEO Settings
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Meta Title</label>
                            <input type="text" x-model="settings.meta_title" placeholder="ชื่อที่แสดงใน Google"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-indigo-500 transition">
                            <p class="text-xs text-gray-400 mt-1">แนะนำ 50-60 ตัวอักษร</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Meta Description</label>
                            <textarea x-model="settings.meta_description" rows="3" placeholder="คำอธิบายสั้นๆ เกี่ยวกับร้านค้า"
                                      class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-indigo-500 transition"></textarea>
                            <p class="text-xs text-gray-400 mt-1">แนะนำ 150-160 ตัวอักษร</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Keywords</label>
                            <input type="text" x-model="settings.meta_keywords" placeholder="คำค้นหา, คั่นด้วยเครื่องหมายจุลภาค"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-slate-600 dark:bg-slate-700 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button @click="saveSeo()"
                                :disabled="saving"
                                class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-semibold rounded-xl shadow-lg transition">
                            💾 บันทึก SEO Settings
                        </button>
                    </div>
                </div>

                {{-- Reset Tab --}}
                <div x-show="activeTab === 'reset'" x-cloak class="tab-panel space-y-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>🔄</span> รีเซ็ต Layout
                    </h2>

                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-4xl">⚠️</div>
                            <div>
                                <h3 class="text-lg font-bold text-red-800 dark:text-red-300">คำเตือน</h3>
                                <p class="text-red-700 dark:text-red-400 mt-2">
                                    การรีเซ็ต Layout จะลบการตั้งค่าทั้งหมดที่คุณได้ปรับแต่งไว้ รวมถึงรูปภาพ Slider และ Header
                                    คุณจะไม่สามารถกู้คืนข้อมูลเหล่านี้ได้หลังจากรีเซ็ต
                                </p>
                            </div>
                        </div>
                    </div>

                    <button @click="resetLayout()"
                            class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-xl shadow-lg transition">
                        🗑️ รีเซ็ตเป็นค่าเริ่มต้น
                    </button>
                </div>
            </div>
        </div>

        {{-- Right Panel: Mini Preview --}}
        <div class="hidden xl:block">
            <div class="sticky top-6 space-y-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-4">
                    <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <span>📱</span> ตัวอย่าง (Preview)
                    </h3>
                    <div class="preview-frame rounded-xl overflow-hidden">
                        <iframe :src="previewUrl" class="w-full h-[700px]" frameborder="0"></iframe>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button @click="showPreviewModal = true"
                                class="flex-1 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition">
                            🔍 ขยายดูเต็มจอ
                        </button>
                        <a :href="'{{ $store->store_url ?? '#' }}'" target="_blank"
                           class="flex-1 px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm transition text-center">
                            🌐 เปิดหน้าจริง
                        </a>
                    </div>
                </div>

                {{-- Quick Tips --}}
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-4 text-white">
                    <h3 class="font-bold mb-2 flex items-center gap-2">
                        <span>💡</span> เคล็ดลับ
                    </h3>
                    <ul class="text-sm space-y-2 text-indigo-100">
                        <li>• ใช้สีที่ตัดกันเพื่อความโดดเด่น</li>
                        <li>• รูป Slider ควรมีขนาด 1920x800px</li>
                        <li>• อย่าลืมกด "เผยแพร่" เมื่อพร้อม</li>
                        <li>• ทดสอบบนมือถือด้วยนะ!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div x-show="showPreviewModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         @click.self="showPreviewModal = false"
         x-transition>
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-7xl max-h-[95vh] overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">ตัวอย่างหน้าร้านค้า</h3>
                <button @click="showPreviewModal = false"
                        class="w-10 h-10 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-full flex items-center justify-center transition">
                    ✕
                </button>
            </div>
            <iframe :src="previewUrl" class="w-full h-[85vh]" frameborder="0"></iframe>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show" x-cloak
         x-transition:enter="transform ease-out duration-300"
         x-transition:enter-start="translate-y-4 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transform ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-4 opacity-0"
         class="fixed bottom-6 right-6 z-50"
         :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
         class="text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3">
        <span x-show="toast.type === 'success'" class="text-2xl">✅</span>
        <span x-show="toast.type === 'error'" class="text-2xl">❌</span>
        <span x-text="toast.message"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
function layoutEditor() {
    return {
        activeTab: 'general',
        tabs: [
            { id: 'general', name: 'ทั่วไป', icon: '⚙️' },
            { id: 'header', name: 'Header', icon: '📌' },
            { id: 'slider', name: 'Slider', icon: '🖼️' },
            { id: 'featured', name: 'สินค้าแนะนำ', icon: '⭐' },
            { id: 'social', name: 'Social', icon: '🌐' },
            { id: 'seo', name: 'SEO', icon: '🔍' },
            { id: 'reset', name: 'รีเซ็ต', icon: '🔄' },
        ],
        settings: @json($layoutSettings),
        socialLinks: @json($layoutSettings->social_links ?? []),
        saving: false,
        showPreviewModal: false,
        previewUrl: '{{ route("seller.store.layout.preview") }}',
        toast: { show: false, message: '', type: 'success' },

        init() {
            // Initialize slider_images if null
            if (!this.settings.slider_images) {
                this.settings.slider_images = [];
            }
            if (!this.socialLinks) {
                this.socialLinks = {};
            }
        },

        updatePreview() {
            // Refresh preview iframe
            const timestamp = new Date().getTime();
            this.previewUrl = '{{ route("seller.store.layout.preview") }}?t=' + timestamp;
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        async saveGeneral() {
            this.saving = true;
            try {
                const response = await fetch('{{ route("seller.store.layout.save-general") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.settings)
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast('บันทึกสำเร็จ!');
                    this.updatePreview();
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
            this.saving = false;
        },

        async saveSlider() {
            this.saving = true;
            try {
                const response = await fetch('{{ route("seller.store.layout.save-slider") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        slider_enabled: this.settings.slider_enabled,
                        slider_autoplay_speed: this.settings.slider_autoplay_speed,
                        slider_show_arrows: this.settings.slider_show_arrows,
                        slider_show_dots: this.settings.slider_show_dots,
                        slider_effect: this.settings.slider_effect,
                        slider_images: this.settings.slider_images
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast('บันทึก Slider สำเร็จ!');
                    this.updatePreview();
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
            this.saving = false;
        },

        async saveFeatured() {
            this.saving = true;
            try {
                const response = await fetch('{{ route("seller.store.layout.save-featured") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        show_featured_products: this.settings.show_featured_products,
                        featured_title: this.settings.featured_title,
                        featured_products_count: this.settings.featured_products_count
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast('บันทึกสำเร็จ!');
                    this.updatePreview();
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        async saveSocialLinks() {
            this.saving = true;
            try {
                const response = await fetch('{{ route("seller.store.layout.save-social-links") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ social_links: this.socialLinks })
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast('บันทึก Social Links สำเร็จ!');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        async saveSeo() {
            this.saving = true;
            try {
                const response = await fetch('{{ route("seller.store.layout.save-seo") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        meta_title: this.settings.meta_title,
                        meta_description: this.settings.meta_description,
                        meta_keywords: this.settings.meta_keywords
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.showToast('บันทึก SEO Settings สำเร็จ!');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        async publish() {
            if (!confirm('คุณต้องการเผยแพร่ Layout นี้หรือไม่?')) return;

            try {
                const response = await fetch('{{ route("seller.store.layout.publish") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.settings.is_published = true;
                    this.showToast('เผยแพร่ Layout สำเร็จ!');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async unpublish() {
            if (!confirm('คุณต้องการยกเลิกการเผยแพร่หรือไม่?')) return;

            try {
                const response = await fetch('{{ route("seller.store.layout.unpublish") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.settings.is_published = false;
                    this.showToast('ยกเลิกการเผยแพร่แล้ว');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async applyTemplate(templateKey) {
            if (!confirm('ใช้เทมเพลต "' + templateKey + '"?\n\nจะเปลี่ยนแปลง: สี, Header, Product Card, Layout\nจะไม่เปลี่ยนแปลง: รูปภาพ, ข้อความ, Social Links, SEO, Custom Code')) return;

            this.saving = true;
            try {
                const response = await fetch('{{ route("seller.store.layout.apply-template") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ template: templateKey })
                });
                const data = await response.json();
                if (data.success) {
                    this.settings = data.data;
                    this.showToast('ใช้เทมเพลตสำเร็จ!');
                    this.updatePreview();
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
            this.saving = false;
        },

        async resetLayout() {
            if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการรีเซ็ต Layout?\nการดำเนินการนี้ไม่สามารถยกเลิกได้!')) return;

            try {
                const response = await fetch('{{ route("seller.store.layout.reset") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.settings = data.data;
                    this.showToast('รีเซ็ต Layout สำเร็จ!');
                    this.updatePreview();
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        addSlide() {
            if (!this.settings.slider_images) {
                this.settings.slider_images = [];
            }
            this.settings.slider_images.push({
                image: '',
                title: '',
                subtitle: '',
                link: ''
            });
        },

        removeSlide(index) {
            this.settings.slider_images.splice(index, 1);
        },

        async uploadSlideImage(event, index) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('{{ route("seller.store.layout.upload-slider-image") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    this.settings.slider_images[index].image = data.path;
                    this.showToast('อัพโหลดรูปภาพสำเร็จ!');
                } else {
                    this.showToast(data.message || 'อัพโหลดไม่สำเร็จ', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async deleteSlideImage(index) {
            const slide = this.settings.slider_images[index];
            if (!slide.image) return;

            try {
                await fetch('{{ route("seller.store.layout.delete-slider-image") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ path: slide.image })
                });
                slide.image = '';
                this.showToast('ลบรูปภาพแล้ว');
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async uploadHeaderImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('{{ route("seller.store.layout.upload-header-image") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    this.settings.header_image = data.path;
                    this.showToast('อัพโหลดรูปภาพ Header สำเร็จ!');
                    this.updatePreview();
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        deleteHeaderImage() {
            this.settings.header_image = null;
            this.updatePreview();
        }
    };
}
</script>
@endpush

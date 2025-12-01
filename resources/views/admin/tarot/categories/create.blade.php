@extends('layouts.admin-v3')

@section('title', 'สร้างหมวดหมู่ใหม่')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl" x-data="categoryForm()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.tarot.categories.index') }}"
               class="p-2 rounded-xl bg-white/10 dark:bg-gray-800/50 hover:bg-white/20 dark:hover:bg-gray-700/50 transition-all">
                <i class="fas fa-arrow-left text-gray-600 dark:text-gray-400"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    สร้างหมวดหมู่ใหม่
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1 ml-16">
                    เพิ่มหมวดหมู่สำหรับการทำนายไพ่ทาโร่ต์
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.tarot.categories.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="glass-fusion rounded-2xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        ข้อมูลพื้นฐาน
                    </h2>

                    <div class="space-y-6">
                        {{-- Names --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ชื่อภาษาไทย <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name_th"
                                       value="{{ old('name_th') }}"
                                       required
                                       placeholder="เช่น ความรัก"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                                @error('name_th')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ชื่อภาษาอังกฤษ <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name_en"
                                       value="{{ old('name_en') }}"
                                       required
                                       placeholder="e.g. Love"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                                @error('name_en')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Descriptions --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    คำอธิบายภาษาไทย
                                </label>
                                <textarea name="description_th"
                                          rows="3"
                                          placeholder="อธิบายหมวดหมู่นี้..."
                                          class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none">{{ old('description_th') }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    คำอธิบายภาษาอังกฤษ
                                </label>
                                <textarea name="description_en"
                                          rows="3"
                                          placeholder="Describe this category..."
                                          class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none">{{ old('description_en') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Appearance --}}
                <div class="glass-fusion rounded-2xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-palette text-pink-500"></i>
                        ลักษณะที่ปรากฏ
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Icon --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ไอคอน (Font Awesome)
                            </label>
                            <div class="flex gap-3">
                                <input type="text"
                                       name="icon"
                                       x-model="icon"
                                       value="{{ old('icon', 'fa-star') }}"
                                       placeholder="fa-heart"
                                       class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl"
                                     :style="{ background: color }">
                                    <i class="fas" :class="icon"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                ใช้ชื่อ Font Awesome เช่น fa-heart, fa-star, fa-briefcase
                            </p>

                            {{-- Quick Icon Picker --}}
                            <div class="mt-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">เลือกด่วน:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['fa-heart', 'fa-star', 'fa-briefcase', 'fa-coins', 'fa-users', 'fa-graduation-cap', 'fa-plane', 'fa-home', 'fa-gem', 'fa-moon'] as $iconOption)
                                        <button type="button"
                                                @click="icon = '{{ $iconOption }}'"
                                                class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-purple-100 dark:hover:bg-purple-900/30 flex items-center justify-center transition-colors"
                                                :class="{ 'ring-2 ring-purple-500': icon === '{{ $iconOption }}' }">
                                            <i class="fas {{ $iconOption }} text-gray-600 dark:text-gray-400"></i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Color --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สีประจำหมวดหมู่
                            </label>
                            <div class="flex gap-3 items-center">
                                <input type="color"
                                       name="color"
                                       x-model="color"
                                       value="{{ old('color', '#8B5CF6') }}"
                                       class="w-20 h-12 rounded-xl border border-gray-300 dark:border-gray-600 cursor-pointer">
                                <input type="text"
                                       x-model="color"
                                       class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-mono uppercase">
                            </div>

                            {{-- Quick Color Picker --}}
                            <div class="mt-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">เลือกด่วน:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['#EF4444', '#F97316', '#EAB308', '#22C55E', '#14B8A6', '#3B82F6', '#8B5CF6', '#EC4899', '#6B7280', '#1F2937'] as $colorOption)
                                        <button type="button"
                                                @click="color = '{{ $colorOption }}'"
                                                class="w-8 h-8 rounded-lg transition-transform hover:scale-110"
                                                style="background: {{ $colorOption }};"
                                                :class="{ 'ring-2 ring-offset-2 ring-gray-400 dark:ring-gray-600': color === '{{ $colorOption }}' }">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="glass-fusion rounded-2xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-baht-sign text-green-500"></i>
                        การตั้งราคา
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคา (บาท) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">฿</span>
                                <input type="number"
                                       name="price"
                                       x-model="price"
                                       value="{{ old('price', 0) }}"
                                       step="1"
                                       min="0"
                                       required
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                กำหนดเป็น 0 เพื่อให้ใช้งานฟรี
                            </p>
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox"
                                           name="is_free_first"
                                           value="1"
                                           x-model="isFreeFirst"
                                           :disabled="price == 0"
                                           checked
                                           class="sr-only peer">
                                    <div class="w-12 h-7 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-green-500 transition-colors peer-disabled:opacity-50"></div>
                                    <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ฟรีครั้งแรกต่อวัน</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ผู้ใช้สามารถดูดวงฟรี 1 ครั้ง/วัน</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Preview --}}
                <div class="glass-fusion rounded-2xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-eye text-purple-500"></i>
                        ตัวอย่าง
                    </h2>

                    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                        <div class="h-16 relative" :style="{ background: 'linear-gradient(135deg, ' + color + '40, ' + color + '20)' }">
                            <div class="absolute bottom-3 left-4">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                                     :style="{ background: color }">
                                    <i class="fas" :class="icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800">
                            <h3 class="font-bold text-gray-900 dark:text-white" x-text="nameTh || 'ชื่อหมวดหมู่'"></h3>
                            <template x-if="price > 0">
                                <div class="mt-2">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">฿<span x-text="Number(price).toLocaleString()"></span></span>
                                    <template x-if="isFreeFirst">
                                        <span class="text-xs text-green-600 ml-2">ฟรีครั้งแรก</span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="price == 0">
                                <div class="mt-2 text-lg font-bold text-green-600">
                                    <i class="fas fa-gift mr-1"></i> ฟรี
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="glass-fusion rounded-2xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-toggle-on text-blue-500"></i>
                        สถานะ
                    </h2>

                    <label class="flex items-center gap-3 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   checked
                                   class="sr-only peer">
                            <div class="w-12 h-7 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-green-500 transition-colors"></div>
                            <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">แสดงหมวดหมู่ให้ผู้ใช้เลือก</p>
                        </div>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="glass-fusion rounded-2xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                    <button type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        บันทึกหมวดหมู่
                    </button>
                    <a href="{{ route('admin.tarot.categories.index') }}"
                       class="w-full mt-3 px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i>
                        ยกเลิก
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function categoryForm() {
    return {
        nameTh: '{{ old("name_th") }}',
        icon: '{{ old("icon", "fa-star") }}',
        color: '{{ old("color", "#8B5CF6") }}',
        price: {{ old('price', 0) }},
        isFreeFirst: {{ old('is_free_first', true) ? 'true' : 'false' }},

        init() {
            // Watch name field
            this.$watch('nameTh', () => {});

            // Sync with input
            const nameInput = document.querySelector('input[name="name_th"]');
            if (nameInput) {
                nameInput.addEventListener('input', (e) => {
                    this.nameTh = e.target.value;
                });
            }
        }
    }
}
</script>
@endsection

@extends('layouts.admin-v3')

@section('title', 'เพิ่มไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">เพิ่มไพ่ทาโร่ต์ใหม่</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">สร้างไพ่ทาโร่ต์ใบใหม่ในระบบ</p>
        </div>
        <a href="{{ route('admin.tarot.cards.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับ
        </a>
    </div>

    <form action="{{ route('admin.tarot.cards.store') }}" method="POST" enctype="multipart/form-data"
          x-data="cardImageUpload()"
          class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column - Image Upload --}}
            <div class="lg:col-span-1">
                <div class="glass-fusion rounded-xl shadow p-6 border border-white/20 dark:border-white/10 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รูปภาพไพ่</h2>

                    {{-- Image Preview --}}
                    <div class="relative aspect-[9/16] bg-gradient-to-br from-purple-900/20 to-indigo-900/20 rounded-xl overflow-hidden mb-4 border-2 border-dashed border-purple-300 dark:border-purple-700"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleDrop($event)"
                         :class="{ 'border-purple-500 bg-purple-50 dark:bg-purple-900/30': isDragging }">

                        {{-- Preview Image — object-contain แสดงไพ่ 9:16 เต็มใบไม่ขาด --}}
                        <img x-show="previewUrl"
                             :src="previewUrl"
                             alt="Preview"
                             class="w-full h-full object-contain">

                        {{-- Placeholder when no image --}}
                        <div x-show="!previewUrl"
                             class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm">ลากรูปมาวางที่นี่</span>
                            <span class="text-xs mt-1">หรือคลิกปุ่มด้านล่าง</span>
                        </div>

                        {{-- Drag overlay --}}
                        <div x-show="isDragging"
                             class="absolute inset-0 flex items-center justify-center bg-purple-500/20 backdrop-blur-sm">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="mt-2 text-purple-600 font-medium">วางรูปที่นี่</p>
                            </div>
                        </div>
                    </div>

                    {{-- Upload Button --}}
                    <label class="block">
                        <input type="file" name="image" accept="image/*"
                               @change="handleFileSelect($event)"
                               class="hidden">
                        <div class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl cursor-pointer transition transform hover:scale-[1.02]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span>เลือกรูปภาพ</span>
                        </div>
                    </label>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">
                        รองรับ JPG, PNG, GIF, WebP ขนาดไม่เกิน 20MB<br>
                        <span class="text-green-600 dark:text-green-400">✓ แปลงเป็น WebP อัตโนมัติ</span><br>
                        <span class="text-purple-600 dark:text-purple-400">✓ ปรับขนาดให้พอดี 400x600px</span>
                    </p>

                    {{-- Remove image button --}}
                    <button type="button"
                            x-show="previewUrl"
                            @click="removePreview()"
                            class="mt-3 w-full px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl transition text-sm">
                        ยกเลิกรูปที่เลือก
                    </button>
                </div>
            </div>

            {{-- Right Column - Card Details --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อภาษาไทย <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name_th" required
                                   value="{{ old('name_th') }}"
                                   placeholder="เช่น คนโง่"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อภาษาอังกฤษ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name_en" required
                                   value="{{ old('name_en') }}"
                                   placeholder="e.g. The Fool"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท <span class="text-red-500">*</span></label>
                            <select name="type" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="major_arcana" {{ old('type') == 'major_arcana' ? 'selected' : '' }}>Major Arcana</option>
                                <option value="minor_arcana" {{ old('type') == 'minor_arcana' ? 'selected' : '' }}>Minor Arcana</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชุด (Suit)</label>
                            <select name="suit" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">-- ไม่มี --</option>
                                <option value="wands" {{ old('suit') == 'wands' ? 'selected' : '' }}>Wands (ไม้เท้า)</option>
                                <option value="cups" {{ old('suit') == 'cups' ? 'selected' : '' }}>Cups (ถ้วย)</option>
                                <option value="swords" {{ old('suit') == 'swords' ? 'selected' : '' }}>Swords (ดาบ)</option>
                                <option value="pentacles" {{ old('suit') == 'pentacles' ? 'selected' : '' }}>Pentacles (เหรียญ)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเลข</label>
                            <input type="number" name="number" value="{{ old('number') }}" min="0" max="21"
                                   placeholder="0-21"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               id="is_active"
                               class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งานไพ่ใบนี้</label>
                    </div>
                </div>

                {{-- Keywords --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คำสำคัญ (Keywords)</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำสำคัญ (ไทย)</label>
                            <input type="text" name="keywords_th"
                                   value="{{ old('keywords_th') }}"
                                   placeholder="ความรัก, ความสุข, โอกาสใหม่"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คั่นด้วยเครื่องหมายจุลภาค (,)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำสำคัญ (อังกฤษ)</label>
                            <input type="text" name="keywords_en"
                                   value="{{ old('keywords_en') }}"
                                   placeholder="love, happiness, new beginnings"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Separate with comma (,)</p>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-white/20 dark:border-white/10">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คำอธิบาย</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (ไทย)</label>
                            <textarea name="description_th" rows="4"
                                      placeholder="อธิบายลักษณะและความหมายของไพ่..."
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description_th') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (อังกฤษ)</label>
                            <textarea name="description_en" rows="4"
                                      placeholder="Describe the card's imagery and meaning..."
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description_en') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Upright Meaning --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-green-200 dark:border-green-700 bg-green-50/50 dark:bg-green-900/10">
                    <h2 class="text-lg font-semibold text-green-800 dark:text-green-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        ความหมายหัวตั้ง (Upright)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาไทย</label>
                            <textarea name="upright_meaning_th" rows="5"
                                      placeholder="ความหมายเมื่อไพ่หัวตั้ง..."
                                      class="w-full rounded-xl border-green-300 dark:border-green-700 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">{{ old('upright_meaning_th') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ</label>
                            <textarea name="upright_meaning_en" rows="5"
                                      placeholder="Upright meaning..."
                                      class="w-full rounded-xl border-green-300 dark:border-green-700 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">{{ old('upright_meaning_en') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Reversed Meaning --}}
                <div class="glass-fusion rounded-xl shadow p-6 border border-red-200 dark:border-red-700 bg-red-50/50 dark:bg-red-900/10">
                    <h2 class="text-lg font-semibold text-red-800 dark:text-red-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        ความหมายกลับหัว (Reversed)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาไทย</label>
                            <textarea name="reversed_meaning_th" rows="5"
                                      placeholder="ความหมายเมื่อไพ่กลับหัว..."
                                      class="w-full rounded-xl border-red-300 dark:border-red-700 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">{{ old('reversed_meaning_th') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ</label>
                            <textarea name="reversed_meaning_en" rows="5"
                                      placeholder="Reversed meaning..."
                                      class="w-full rounded-xl border-red-300 dark:border-red-700 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">{{ old('reversed_meaning_en') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.tarot.cards.index') }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition transform hover:scale-[1.02] shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        สร้างไพ่ใหม่
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function cardImageUpload() {
    return {
        previewUrl: null,
        isDragging: false,

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.createPreview(file);
            }
        },

        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                // Update the file input
                const input = this.$el.querySelector('input[type="file"]');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
                this.createPreview(file);
            }
        },

        createPreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        removePreview() {
            this.previewUrl = null;
            const input = this.$el.querySelector('input[type="file"]');
            input.value = '';
        }
    }
}
</script>
@endpush
@endsection

@extends('layouts.admin-v3')

@section('title', 'แก้ไขไพ่ทาโร่ต์ - ' . $card->name_th)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">แก้ไขไพ่ทาโร่ต์</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $card->name_th }} ({{ $card->name_en }})</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.tarot.interpretations.edit', $card->id) }}"
               class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                จัดการคำทำนายตามหมวด
            </a>
            <a href="{{ route('admin.tarot.cards.index', session('tarot_cards_last_filter', [])) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    <form action="{{ route('admin.tarot.cards.update', $card->id) }}" method="POST" enctype="multipart/form-data"
          x-data="cardImageUpload()"
          class="space-y-6">
        @csrf
        @method('PUT')

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

                        {{-- Current/Preview Image — object-contain แสดงไพ่ 9:16 เต็มใบไม่ขาด --}}
                        <img x-show="currentImageUrl"
                             :src="currentImageUrl"
                             alt="{{ $card->name_th }}"
                             class="w-full h-full object-contain">

                        {{-- Placeholder when no image --}}
                        <div x-show="!currentImageUrl"
                             class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm">ยังไม่มีรูปภาพ</span>
                        </div>

                        {{-- Loading overlay --}}
                        <div x-show="isUploading"
                             class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm">
                            <div class="w-12 h-12 border-4 border-purple-400 border-t-transparent rounded-full animate-spin mb-3"></div>
                            <p class="text-white font-medium">กำลังอัพโหลด...</p>
                            <p class="text-purple-300 text-sm mt-1" x-text="uploadProgress + '%'"></p>
                        </div>

                        {{-- Drag overlay --}}
                        <div x-show="isDragging && !isUploading"
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
                    <label class="block" :class="{ 'pointer-events-none opacity-50': isUploading }">
                        <input type="file" name="image" accept="image/*"
                               @change="handleFileSelect($event)"
                               class="hidden"
                               :disabled="isUploading">
                        <div class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl cursor-pointer transition transform hover:scale-[1.02]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span x-text="isUploading ? 'กำลังอัพโหลด...' : 'เลือกรูปภาพ'"></span>
                        </div>
                    </label>

                    {{-- Status Message --}}
                    <div x-show="statusMessage" x-transition
                         :class="statusType === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-400'"
                         class="mt-3 p-3 rounded-xl border text-sm">
                        <span x-text="statusMessage"></span>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">
                        รองรับ JPG, PNG, GIF, WebP ขนาดไม่เกิน 20MB<br>
                        <span class="text-green-600 dark:text-green-400">✓ อัพโหลดทันทีเมื่อเลือกรูป</span><br>
                        <span class="text-purple-600 dark:text-purple-400">✓ ปรับขนาดให้พอดี 400x600px</span>
                    </p>
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
                            <input type="text" name="name_th" value="{{ $card->name_th }}" required
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อภาษาอังกฤษ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name_en" value="{{ $card->name_en }}" required
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                            <select name="type" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="major_arcana" {{ $card->type == 'major_arcana' ? 'selected' : '' }}>Major Arcana</option>
                                <option value="minor_arcana" {{ $card->type == 'minor_arcana' ? 'selected' : '' }}>Minor Arcana</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชุด (Suit)</label>
                            <select name="suit" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">-- ไม่มี --</option>
                                <option value="wands" {{ $card->suit == 'wands' ? 'selected' : '' }}>Wands (ไม้เท้า)</option>
                                <option value="cups" {{ $card->suit == 'cups' ? 'selected' : '' }}>Cups (ถ้วย)</option>
                                <option value="swords" {{ $card->suit == 'swords' ? 'selected' : '' }}>Swords (ดาบ)</option>
                                <option value="pentacles" {{ $card->suit == 'pentacles' ? 'selected' : '' }}>Pentacles (เหรียญ)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเลข</label>
                            <input type="number" name="number" value="{{ $card->number }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ $card->is_active ? 'checked' : '' }}
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
                                   value="{{ is_array($card->keywords_th) ? implode(', ', $card->keywords_th) : $card->keywords_th }}"
                                   placeholder="ความรัก, ความสุข, โอกาสใหม่"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คั่นด้วยเครื่องหมายจุลภาค (,)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำสำคัญ (อังกฤษ)</label>
                            <input type="text" name="keywords_en"
                                   value="{{ is_array($card->keywords_en) ? implode(', ', $card->keywords_en) : $card->keywords_en }}"
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
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $card->description_th }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (อังกฤษ)</label>
                            <textarea name="description_en" rows="4"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $card->description_en }}</textarea>
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
                                      class="w-full rounded-xl border-green-300 dark:border-green-700 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">{{ $card->upright_meaning_th }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ</label>
                            <textarea name="upright_meaning_en" rows="5"
                                      class="w-full rounded-xl border-green-300 dark:border-green-700 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">{{ $card->upright_meaning_en }}</textarea>
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
                                      class="w-full rounded-xl border-red-300 dark:border-red-700 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">{{ $card->reversed_meaning_th }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาอังกฤษ</label>
                            <textarea name="reversed_meaning_en" rows="5"
                                      class="w-full rounded-xl border-red-300 dark:border-red-700 dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500">{{ $card->reversed_meaning_en }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.tarot.cards.index', session('tarot_cards_last_filter', [])) }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition transform hover:scale-[1.02] shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึกการเปลี่ยนแปลง
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
        currentImageUrl: '{{ $card->image_url }}',
        isDragging: false,
        isUploading: false,
        uploadProgress: 0,
        statusMessage: '',
        statusType: '',

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.uploadImage(file);
            }
        },

        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                this.uploadImage(file);
            }
        },

        async uploadImage(file) {
            // Validate file size (20MB — synced with TarotManagementController validation 2026-05-04)
            if (file.size > 20 * 1024 * 1024) {
                this.showStatus('ไฟล์ใหญ่เกินไป (ขนาดสูงสุด 20MB)', 'error');
                return;
            }

            // Validate file type
            if (!file.type.startsWith('image/')) {
                this.showStatus('กรุณาเลือกไฟล์รูปภาพ', 'error');
                return;
            }

            // Show preview immediately
            const reader = new FileReader();
            reader.onload = (e) => {
                this.currentImageUrl = e.target.result;
            };
            reader.readAsDataURL(file);

            // Start upload
            this.isUploading = true;
            this.uploadProgress = 0;
            this.statusMessage = '';

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch('{{ route("admin.tarot.cards.upload-image", $card->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.currentImageUrl = data.image_url;
                    this.showStatus('อัพโหลดรูปสำเร็จ!', 'success');
                } else {
                    this.showStatus(data.message || 'เกิดข้อผิดพลาดในการอัพโหลด', 'error');
                }
            } catch (error) {
                console.error('Upload error:', error);
                this.showStatus('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.isUploading = false;
                this.uploadProgress = 100;
            }
        },

        showStatus(message, type) {
            this.statusMessage = message;
            this.statusType = type;

            // Auto hide after 5 seconds
            setTimeout(() => {
                this.statusMessage = '';
            }, 5000);
        }
    }
}
</script>
@endpush
@endsection

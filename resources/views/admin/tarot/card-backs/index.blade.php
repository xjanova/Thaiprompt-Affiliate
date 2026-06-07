@extends('layouts.admin-v3')

@section('title', 'จัดการรูปหลังไพ่ทาโร่ต์')

@section('content')
<div class="container mx-auto px-4 py-8"
     x-data="cardBackManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการรูปหลังไพ่</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">อัพโหลดและจัดการรูปหลังไพ่ทาโร่ต์</p>
        </div>
        <div class="flex gap-3">
            <button @click="showUploadForm = !showUploadForm"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition transform hover:scale-[1.02]">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                อัพโหลดรูปใหม่
            </button>
            <a href="{{ route('admin.tarot.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Upload Form --}}
    <div x-show="showUploadForm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-4"
         class="glass-fusion rounded-xl shadow-lg p-6 mb-8 border border-white/20 dark:border-white/10">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">อัพโหลดรูปหลังไพ่ใหม่</h2>

        <form action="{{ route('admin.tarot.card-backs.store') }}" method="POST" enctype="multipart/form-data"
              class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Left - Preview --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตัวอย่างรูป</label>
                    <div class="relative aspect-[9/16] bg-gradient-to-br from-purple-900/20 to-indigo-900/20 rounded-xl overflow-hidden border-2 border-dashed border-purple-300 dark:border-purple-700"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleDrop($event)"
                         :class="{ 'border-purple-500 bg-purple-50 dark:bg-purple-900/30': isDragging }">

                        {{-- Preview Image — 9:16 object-contain แสดงเต็มใบไม่ขาด --}}
                        <img x-show="previewUrl"
                             :src="previewUrl"
                             class="w-full h-full object-contain">

                        {{-- Placeholder --}}
                        <div x-show="!previewUrl"
                             class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm">ลากไฟล์มาวางที่นี่</span>
                            <span class="text-xs mt-1">หรือคลิกเลือกไฟล์</span>
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

                        {{-- Hidden file input --}}
                        <input type="file" name="image" accept="image/*" required
                               @change="handleFileSelect($event)"
                               class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>
                </div>

                {{-- Right - Form Fields --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อรูปหลังไพ่ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               placeholder="เช่น ลายคลาสสิก, ลายมังกร"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    <div class="flex items-center gap-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_default" value="1"
                                   class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ตั้งเป็นรูปเริ่มต้น</span>
                        </label>

                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                        </label>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        รองรับ JPG, PNG, WebP ขนาดไม่เกิน 2MB<br>
                        แนะนำ: อัตราส่วน 2:3 (เช่น 400x600px)
                    </p>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition transform hover:scale-[1.02]">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            อัพโหลด
                        </button>
                        <button type="button"
                                @click="showUploadForm = false; previewUrl = null"
                                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition">
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Card Backs Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
        @forelse($cardBacks as $cardBack)
            <div class="group glass-fusion rounded-xl shadow overflow-hidden border border-white/20 dark:border-white/10 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                {{-- Image --}}
                <div class="relative aspect-[9/16] bg-gradient-to-br from-purple-900/20 to-indigo-900/20">
                    <img src="{{ $cardBack->image_url }}"
                         alt="{{ $cardBack->name }}"
                         class="w-full h-full object-contain">

                    {{-- Default Badge --}}
                    @if($cardBack->is_default)
                        <div class="absolute top-2 left-2">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-500 text-white shadow">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                เริ่มต้น
                            </span>
                        </div>
                    @endif

                    {{-- Inactive Badge --}}
                    @if(!$cardBack->is_active)
                        <div class="absolute top-2 right-2">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-500 text-white shadow">
                                ปิดใช้งาน
                            </span>
                        </div>
                    @endif

                    {{-- Hover Actions --}}
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-2">
                        @if(!$cardBack->is_default)
                            <form action="{{ route('admin.tarot.card-backs.set-default', $cardBack->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition"
                                        title="ตั้งเป็นเริ่มต้น">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </button>
                            </form>

                            <form action="{{ route('admin.tarot.card-backs.destroy', $cardBack->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('ยืนยันการลบรูปหลังไพ่นี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition"
                                        title="ลบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="p-3">
                    <h3 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $cardBack->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ลำดับ: {{ $cardBack->sort_order }}
                    </p>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรูปหลังไพ่</p>
                <button @click="showUploadForm = true"
                        class="mt-4 px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition">
                    อัพโหลดรูปแรก
                </button>
            </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
function cardBackManager() {
    return {
        showUploadForm: false,
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
                const input = event.target.closest('form').querySelector('input[type="file"]');
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
        }
    }
}
</script>
@endpush
@endsection

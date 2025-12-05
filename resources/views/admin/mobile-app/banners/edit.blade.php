@extends('layouts.admin')

@section('title', 'แก้ไข Banner')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.mobile-app.banners.index') }}"
           class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition">
            <span class="text-xl">←</span>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-4xl">✏️</span>
                แก้ไข Banner
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                {{ $banner->title }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <form action="{{ route('admin.mobile-app.banners.update', $banner) }}" method="POST" enctype="multipart/form-data"
              x-data="bannerForm('{{ $banner->image }}')" class="lg:col-span-2">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อ Banner <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" required
                           value="{{ old('title', $banner->title) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="เช่น โปรโมชั่นพิเศษ 50%">
                    @error('title')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Current Image --}}
                @if($banner->image)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปภาพปัจจุบัน
                    </label>
                    <div class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden max-w-md">
                        <img src="{{ $banner->image }}" alt="{{ $banner->title }}"
                             class="w-full h-full object-cover">
                    </div>
                </div>
                @endif

                {{-- New Image Upload --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เปลี่ยนรูปภาพ
                    </label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6
                                hover:border-orange-500 dark:hover:border-orange-400 transition cursor-pointer"
                         @click="$refs.imageInput.click()"
                         @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="handleDrop($event)"
                         :class="{ 'border-orange-500 bg-orange-50 dark:bg-orange-900/20': dragging }">

                        <input type="file" name="image" ref="imageInput" accept="image/*"
                               class="hidden" @change="handleImageChange($event)">

                        <div x-show="!newImagePreview" class="text-center">
                            <div class="text-4xl mb-2">📁</div>
                            <p class="text-gray-600 dark:text-gray-400">
                                คลิกหรือลากไฟล์มาวางเพื่อเปลี่ยนรูป
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                แนะนำขนาด 1200x400 pixels (อัตราส่วน 3:1)
                            </p>
                        </div>

                        <div x-show="newImagePreview" class="text-center">
                            <img :src="newImagePreview" alt="Preview"
                                 class="max-h-48 mx-auto rounded-lg mb-3">
                            <button type="button" @click.stop="removeNewImage()"
                                    class="text-red-500 hover:text-red-600 text-sm">
                                🗑️ ยกเลิกรูปใหม่
                            </button>
                        </div>
                    </div>
                    @error('image')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Link URL --}}
                <div>
                    <label for="link_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลิงก์เมื่อคลิก
                    </label>
                    <input type="url" name="link_url" id="link_url"
                           value="{{ old('link_url', $banner->link_url) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="https://example.com/promotion">
                    @error('link_url')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Position --}}
                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ตำแหน่งแสดง <span class="text-red-500">*</span>
                    </label>
                    <select name="position" id="position" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                   bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                   focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        <option value="top" {{ old('position', $banner->position) == 'top' ? 'selected' : '' }}>
                            🔝 ด้านบน (Top)
                        </option>
                        <option value="middle" {{ old('position', $banner->position) == 'middle' ? 'selected' : '' }}>
                            ➖ ตรงกลาง (Middle)
                        </option>
                        <option value="bottom" {{ old('position', $banner->position) == 'bottom' ? 'selected' : '' }}>
                            🔽 ด้านล่าง (Bottom)
                        </option>
                    </select>
                    @error('position')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sort Order --}}
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับการแสดง
                    </label>
                    <input type="number" name="sort_order" id="sort_order"
                           value="{{ old('sort_order', $banner->sort_order) }}" min="0"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    @error('sort_order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันที่เริ่มแสดง
                        </label>
                        <input type="datetime-local" name="start_date" id="start_date"
                               value="{{ old('start_date', $banner->start_date?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                      focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันที่สิ้นสุด
                        </label>
                        <input type="datetime-local" name="end_date" id="end_date"
                               value="{{ old('end_date', $banner->end_date?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                      focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Active Status --}}
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $banner->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        เปิดใช้งาน
                    </label>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit"
                            class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-xl transition">
                        💾 บันทึกการแก้ไข
                    </button>
                    <a href="{{ route('admin.mobile-app.banners.index') }}"
                       class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600
                              text-gray-700 dark:text-gray-300 font-medium rounded-xl transition">
                        ยกเลิก
                    </a>
                </div>
            </div>
        </form>

        {{-- Stats Sidebar --}}
        <div class="space-y-6">
            {{-- Statistics --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📊 สถิติ Banner
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">การแสดงผล</span>
                        <span class="text-xl font-bold text-blue-500">
                            {{ number_format($banner->view_count) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">การคลิก</span>
                        <span class="text-xl font-bold text-purple-500">
                            {{ number_format($banner->click_count) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">CTR</span>
                        <span class="text-xl font-bold text-green-500">
                            {{ $banner->ctr }}%
                        </span>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    ℹ️ ข้อมูล Banner
                </h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">ID</span>
                        <span class="text-gray-900 dark:text-white font-mono">
                            #{{ $banner->id }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white">
                            {{ $banner->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">อัพเดทล่าสุด</span>
                        <span class="text-gray-900 dark:text-white">
                            {{ $banner->updated_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สถานะ</span>
                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                            {{ $banner->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                  : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $banner->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Delete --}}
            <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-6">
                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2">
                    ⚠️ Danger Zone
                </h3>
                <p class="text-sm text-red-600 dark:text-red-400 mb-4">
                    การลบ Banner จะไม่สามารถกู้คืนได้
                </p>
                <form action="{{ route('admin.mobile-app.banners.destroy', $banner) }}"
                      method="POST"
                      onsubmit="return confirm('ต้องการลบ Banner นี้หรือไม่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl transition">
                        🗑️ ลบ Banner
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function bannerForm(existingImage) {
        return {
            dragging: false,
            existingImage: existingImage,
            newImagePreview: null,

            handleImageChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.showPreview(file);
                }
            },

            handleDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.$refs.imageInput.files = event.dataTransfer.files;
                    this.showPreview(file);
                }
            },

            showPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newImagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            removeNewImage() {
                this.newImagePreview = null;
                this.$refs.imageInput.value = '';
            }
        };
    }
</script>
@endpush
@endsection

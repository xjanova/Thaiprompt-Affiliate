{{--
    Admin: Create Storefront Banner

    ฟอร์มสร้าง Banner ใหม่สำหรับ Storefront
    รองรับการอัพโหลดรูป, เลือก Gradient, กำหนดช่วงเวลาแสดง
--}}

@extends('layouts.admin-v3')

@section('title', 'เพิ่ม Banner ใหม่')

@section('content')
<div class="container-fluid py-6" x-data="bannerForm()">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.storefront.banners.index') }}"
           class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200
                 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-plus-circle text-orange-500 mr-3"></i>
                เพิ่ม Banner ใหม่
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                สร้าง Banner สำหรับแสดงบนหน้า Storefront
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.storefront.banners.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                           border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4">
                        <h2 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            ข้อมูลพื้นฐาน
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Title --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                หัวข้อ Banner
                            </label>
                            <input type="text"
                                   name="title"
                                   value="{{ old('title') }}"
                                   placeholder="เช่น: สินค้าลดราคาพิเศษ!"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                         bg-gray-50 dark:bg-gray-700/50
                                         text-gray-900 dark:text-white
                                         focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                         transition-all">
                            @error('title')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Subtitle --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                หัวข้อรอง
                            </label>
                            <input type="text"
                                   name="subtitle"
                                   value="{{ old('subtitle') }}"
                                   placeholder="เช่น: ลดสูงสุดถึง 70%"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                         bg-gray-50 dark:bg-gray-700/50
                                         text-gray-900 dark:text-white
                                         focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                         transition-all">
                        </div>

                        {{-- Badge & Highlight --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    Badge
                                </label>
                                <input type="text"
                                       name="badge"
                                       value="{{ old('badge') }}"
                                       placeholder="เช่น: Flash Sale"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                             bg-gray-50 dark:bg-gray-700/50
                                             text-gray-900 dark:text-white
                                             focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                             transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    Highlight Text
                                </label>
                                <input type="text"
                                       name="highlight_text"
                                       value="{{ old('highlight_text') }}"
                                       placeholder="เช่น: 70%"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                             bg-gray-50 dark:bg-gray-700/50
                                             text-gray-900 dark:text-white
                                             focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                             transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    Highlight Label
                                </label>
                                <input type="text"
                                       name="highlight_label"
                                       value="{{ old('highlight_label') }}"
                                       placeholder="เช่น: ส่วนลด"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                             bg-gray-50 dark:bg-gray-700/50
                                             text-gray-900 dark:text-white
                                             focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                             transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CTA Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                           border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4">
                        <h2 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-mouse-pointer"></i>
                            Call-to-Action
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    ข้อความปุ่ม
                                </label>
                                <input type="text"
                                       name="cta_text"
                                       value="{{ old('cta_text', 'ช้อปเลย') }}"
                                       placeholder="เช่น: ช้อปเลย, ดูเพิ่มเติม"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                             bg-gray-50 dark:bg-gray-700/50
                                             text-gray-900 dark:text-white
                                             focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                             transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    ลิงก์ปลายทาง
                                </label>
                                <input type="url"
                                       name="cta_url"
                                       value="{{ old('cta_url') }}"
                                       placeholder="https://..."
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                             bg-gray-50 dark:bg-gray-700/50
                                             text-gray-900 dark:text-white
                                             focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                             transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Image/Gradient Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                           border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-4">
                        <h2 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-image"></i>
                            รูปภาพ / Gradient
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Image Upload --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                อัพโหลดรูปภาพ
                            </label>
                            <div class="relative">
                                <input type="file"
                                       name="image"
                                       accept="image/*"
                                       @change="previewImage($event)"
                                       class="hidden"
                                       id="image-upload">
                                <label for="image-upload"
                                       class="flex items-center justify-center w-full h-40
                                             border-2 border-dashed border-gray-300 dark:border-gray-600
                                             rounded-xl cursor-pointer
                                             hover:border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20
                                             transition-all">
                                    <template x-if="!imagePreview">
                                        <div class="text-center">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-gray-500 dark:text-gray-400">
                                                คลิกเพื่ออัพโหลดรูปภาพ
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                รองรับ: JPG, PNG, WebP (สูงสุด 5MB)
                                            </p>
                                        </div>
                                    </template>
                                    <template x-if="imagePreview">
                                        <img :src="imagePreview"
                                             class="w-full h-full object-cover rounded-xl">
                                    </template>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                แนะนำขนาด: 1920x600 พิกเซล
                            </p>
                        </div>

                        {{-- Or use Gradient --}}
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="px-4 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm">
                                    หรือเลือก Gradient
                                </span>
                            </div>
                        </div>

                        {{-- Gradient Options --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                เลือก Gradient Background
                            </label>
                            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($gradients ?? [
                                    'from-orange-500 to-red-500',
                                    'from-blue-500 to-indigo-500',
                                    'from-green-500 to-emerald-500',
                                    'from-purple-500 to-pink-500',
                                    'from-yellow-400 to-orange-500',
                                    'from-pink-500 to-rose-500',
                                    'from-cyan-500 to-blue-500',
                                    'from-violet-500 to-purple-500',
                                    'from-red-500 to-pink-500',
                                    'from-teal-500 to-green-500',
                                    'from-indigo-500 to-blue-500',
                                    'from-rose-500 to-orange-500',
                                ] as $gradient)
                                <label class="relative cursor-pointer group">
                                    <input type="radio"
                                           name="gradient"
                                           value="{{ $gradient }}"
                                           {{ old('gradient') === $gradient ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="h-16 rounded-xl bg-gradient-to-r {{ $gradient }}
                                               ring-2 ring-transparent
                                               peer-checked:ring-4 peer-checked:ring-offset-2
                                               peer-checked:ring-orange-500
                                               dark:peer-checked:ring-offset-gray-800
                                               group-hover:scale-105 transition-all">
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Publish Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                           border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-gray-700 to-gray-900 px-6 py-4">
                        <h2 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-cog"></i>
                            ตั้งค่าการแสดงผล
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Location --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                ตำแหน่งที่แสดง
                            </label>
                            <select name="location"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                          bg-gray-50 dark:bg-gray-700/50
                                          text-gray-900 dark:text-white
                                          focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                          transition-all">
                                <option value="homepage" {{ old('location') === 'homepage' ? 'selected' : '' }}>
                                    หน้าแรก (Homepage)
                                </option>
                                <option value="category" {{ old('location') === 'category' ? 'selected' : '' }}>
                                    หน้าหมวดหมู่
                                </option>
                            </select>
                        </div>

                        {{-- Category Slug (shown when location = category) --}}
                        <div x-show="$refs.location?.value === 'category'" x-cloak>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                หมวดหมู่
                            </label>
                            <input type="text"
                                   name="category_slug"
                                   value="{{ old('category_slug') }}"
                                   placeholder="slug ของหมวดหมู่"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                         bg-gray-50 dark:bg-gray-700/50
                                         text-gray-900 dark:text-white
                                         focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                         transition-all">
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}
                                       class="w-5 h-5 text-orange-500 rounded
                                             focus:ring-orange-500 focus:ring-offset-0
                                             border-gray-300 dark:border-gray-600
                                             dark:bg-gray-700">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                    เปิดใช้งาน Banner
                                </span>
                            </label>
                        </div>

                        {{-- Sort Order --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                ลำดับการแสดงผล
                            </label>
                            <input type="number"
                                   name="sort_order"
                                   value="{{ old('sort_order', 0) }}"
                                   min="0"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                         bg-gray-50 dark:bg-gray-700/50
                                         text-gray-900 dark:text-white
                                         focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                         transition-all">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ตัวเลขน้อย = แสดงก่อน
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                           border border-gray-100 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-amber-500 to-yellow-500 px-6 py-4">
                        <h2 class="font-bold text-white text-lg flex items-center gap-2">
                            <i class="fas fa-calendar-alt"></i>
                            กำหนดช่วงเวลาแสดง
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Start Date --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                เริ่มแสดง
                            </label>
                            <input type="datetime-local"
                                   name="start_at"
                                   value="{{ old('start_at') }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                         bg-gray-50 dark:bg-gray-700/50
                                         text-gray-900 dark:text-white
                                         focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                         transition-all">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                เว้นว่างไว้ = แสดงทันที
                            </p>
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                สิ้นสุดการแสดง
                            </label>
                            <input type="datetime-local"
                                   name="end_at"
                                   value="{{ old('end_at') }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                         bg-gray-50 dark:bg-gray-700/50
                                         text-gray-900 dark:text-white
                                         focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                         transition-all">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                เว้นว่างไว้ = แสดงไม่มีกำหนด
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    <button type="submit"
                            class="w-full py-4 bg-gradient-to-r from-orange-500 to-red-500
                                  hover:from-orange-600 hover:to-red-600
                                  text-white font-bold rounded-xl
                                  shadow-lg hover:shadow-xl
                                  transform hover:scale-[1.02]
                                  transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        บันทึก Banner
                    </button>

                    <a href="{{ route('admin.storefront.banners.index') }}"
                       class="w-full py-4 bg-gray-200 dark:bg-gray-700
                             hover:bg-gray-300 dark:hover:bg-gray-600
                             text-gray-700 dark:text-gray-300 font-bold rounded-xl
                             transition-all text-center">
                        ยกเลิก
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
/**
 * Banner Form Component
 *
 * จัดการฟอร์มสร้าง/แก้ไข Banner
 */
function bannerForm() {
    return {
        imagePreview: null,

        /**
         * แสดงตัวอย่างรูปภาพที่เลือก
         *
         * @param {Event} event
         */
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    };
}
</script>
@endpush
@endsection

@extends('layouts.admin-v3')

@section('title', 'แก้ไข: ' . $softwareProduct->name)

@push('styles')
<style>
    .feature-item {
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-cyan-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-blue-900/20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.software-products.show', $softwareProduct) }}"
                   class="p-2 hover:bg-white dark:hover:bg-slate-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-400 dark:via-blue-400 dark:to-indigo-400">
                        แก้ไขผลิตภัณฑ์
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $softwareProduct->name }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.software-products.update', $softwareProduct) }}" method="POST" x-data="productForm()">
            @csrf
            @method('PUT')

            {{-- Basic Information --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อผลิตภัณฑ์ <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $softwareProduct->name) }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Slug
                        </label>
                        <input type="text"
                               name="slug"
                               value="{{ old('slug', $softwareProduct->slug) }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            หมวดหมู่
                        </label>
                        <select name="category_id"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                            <option value="">ไม่ระบุ</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $softwareProduct->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบายสั้น
                        </label>
                        <textarea name="short_description"
                                  rows="2"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">{{ old('short_description', $softwareProduct->short_description) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบายแบบเต็ม
                        </label>
                        <textarea name="description"
                                  rows="6"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">{{ old('description', $softwareProduct->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ราคา</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ราคาเริ่มต้น <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="base_price"
                               value="{{ old('base_price', $softwareProduct->base_price) }}"
                               required
                               step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            สกุลเงิน
                        </label>
                        <select name="currency"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                            <option value="THB" {{ old('currency', $softwareProduct->currency) == 'THB' ? 'selected' : '' }}>THB</option>
                            <option value="USD" {{ old('currency', $softwareProduct->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency', $softwareProduct->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ป้ายราคา
                        </label>
                        <input type="text"
                               name="price_label"
                               value="{{ old('price_label', $softwareProduct->price_label) }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Images --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รูปภาพ</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            รูปภาพหลัก (URL)
                        </label>
                        <input type="url"
                               name="main_image_url"
                               value="{{ old('main_image_url', $softwareProduct->main_image_url) }}"
                               x-model="mainImage"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                        <template x-if="mainImage">
                            <div class="mt-4">
                                <img :src="mainImage" alt="Preview" class="w-full h-48 object-cover rounded-xl">
                            </div>
                        </template>
                    </div>

                    <div x-data="{ galleryImages: {{ json_encode($softwareProduct->gallery_images ?? []) }} }">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            แกลเลอรี่ (URLs)
                        </label>
                        <template x-for="(img, index) in galleryImages" :key="index">
                            <div class="flex gap-2 mb-2">
                                <input type="url"
                                       :name="'gallery_images[' + index + ']'"
                                       x-model="galleryImages[index]"
                                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                                <button type="button"
                                        @click="galleryImages.splice(index, 1)"
                                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button type="button"
                                @click="galleryImages.push('')"
                                class="mt-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl transition-colors">
                            + เพิ่มรูปภาพ
                        </button>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 mb-6" x-data="{ features: {{ json_encode($softwareProduct->features ?? []) }} }">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">คุณสมบัติ</h2>
                </div>

                <template x-for="(feature, index) in features" :key="index">
                    <div class="flex gap-2 mb-3 feature-item">
                        <input type="text"
                               :name="'features[' + index + ']'"
                               x-model="features[index]"
                               class="flex-1 px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                        <button type="button"
                                @click="features.splice(index, 1)"
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </template>
                <button type="button"
                        @click="features.push('')"
                        class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors">
                    + เพิ่มคุณสมบัติ
                </button>
            </div>

            {{-- Additional Information --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลเพิ่มเติม</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            URL สาธิต
                        </label>
                        <input type="url"
                               name="demo_url"
                               value="{{ old('demo_url', $softwareProduct->demo_url) }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            URL เอกสาร
                        </label>
                        <input type="url"
                               name="documentation_url"
                               value="{{ old('documentation_url', $softwareProduct->documentation_url) }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ระยะเวลาส่งมอบ (วัน)
                        </label>
                        <input type="number"
                               name="estimated_delivery_days"
                               value="{{ old('estimated_delivery_days', $softwareProduct->estimated_delivery_days) }}"
                               min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ลำดับการแสดง
                        </label>
                        <input type="number"
                               name="sort_order"
                               value="{{ old('sort_order', $softwareProduct->sort_order) }}"
                               min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Options --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ตัวเลือก</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $softwareProduct->is_active) ? 'checked' : '' }} class="w-5 h-5 text-cyan-600 rounded focus:ring-cyan-500">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $softwareProduct->is_featured) ? 'checked' : '' }} class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">แนะนำ</span>
                    </label>

                    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <input type="checkbox" name="is_customizable" value="1" {{ old('is_customizable', $softwareProduct->is_customizable) ? 'checked' : '' }} class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">กำหนดเองได้</span>
                    </label>

                    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <input type="checkbox" name="requires_consultation" value="1" {{ old('requires_consultation', $softwareProduct->requires_consultation) ? 'checked' : '' }} class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">ต้องการคำปรึกษา</span>
                    </label>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex gap-4">
                <button type="submit"
                        class="flex-1 px-8 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึกการเปลี่ยนแปลง
                </button>
                <a href="{{ route('admin.software-products.show', $softwareProduct) }}"
                   class="px-8 py-4 bg-gray-300 dark:bg-slate-700 hover:bg-gray-400 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 font-bold rounded-xl transition-colors">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function productForm() {
    return {
        mainImage: '{{ $softwareProduct->main_image_url }}',
    }
}
</script>
@endpush
@endsection

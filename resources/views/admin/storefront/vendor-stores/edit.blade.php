{{--
    Admin Vendor Store Edit - แก้ไขร้านค้า

    ฟอร์มแก้ไขข้อมูลร้านค้าโดย Admin

    @version 3.0
    @uses Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin-v3')

@section('title', 'แก้ไขร้าน: ' . $store->store_name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.storefront.vendor-stores.index') }}"
           class="p-2 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left text-gray-600 dark:text-gray-300"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                แก้ไขร้านค้า
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $store->store_name }}
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.storefront.vendor-stores.update', $store) }}"
          method="POST"
          class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-store text-orange-500"></i>
                ข้อมูลร้านค้า
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Store Name --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อร้านค้า <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="store_name"
                           value="{{ old('store_name', $store->store_name) }}"
                           required
                           class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl
                                  text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500">
                    @error('store_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบายร้านค้า
                    </label>
                    <textarea name="store_description"
                              rows="4"
                              class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl
                                     text-gray-900 dark:text-white
                                     focus:ring-2 focus:ring-orange-500">{{ old('store_description', $store->store_description) }}</textarea>
                    @error('store_description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Commission Rate --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ค่าคอมมิชชั่น (%)
                    </label>
                    <input type="number"
                           name="commission_rate"
                           value="{{ old('commission_rate', $store->commission_rate) }}"
                           min="0"
                           max="100"
                           step="0.1"
                           class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl
                                  text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500">
                </div>

                {{-- Primary Color --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีหลัก
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="color"
                               name="primary_color"
                               value="{{ old('primary_color', $store->primary_color ?? '#f97316') }}"
                               class="w-12 h-12 rounded-xl cursor-pointer">
                        <input type="text"
                               value="{{ old('primary_color', $store->primary_color ?? '#f97316') }}"
                               readonly
                               class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl
                                      text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-toggle-on text-green-500"></i>
                สถานะร้านค้า
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Is Active --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">เปิดใช้งาน</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ร้านค้าสามารถขายสินค้าได้</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ $store->is_active ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-orange-500 rounded-full peer
                                    dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                    after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-green-500"></div>
                    </label>
                </div>

                {{-- Is Verified --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">ยืนยันแล้ว</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แสดง badge ยืนยัน</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_verified"
                               value="1"
                               {{ $store->is_verified ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-orange-500 rounded-full peer
                                    dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                    after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-blue-500"></div>
                    </label>
                </div>

                {{-- Is Featured --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">ร้านค้าแนะนำ</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แสดงในหน้าแรก</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_featured_home"
                               value="1"
                               {{ $store->is_featured_home ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-orange-500 rounded-full peer
                                    dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white
                                    after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-yellow-500"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.storefront.vendor-stores.index') }}"
               class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                      font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-orange-500 to-pink-600
                           hover:from-orange-600 hover:to-pink-700
                           text-white font-semibold rounded-xl
                           transition-all hover:scale-105 shadow-lg">
                <i class="fas fa-save mr-2"></i>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>
@endsection

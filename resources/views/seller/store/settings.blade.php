@extends('layouts.seller')

@section('title', 'ตั้งค่าร้านค้า - ' . ($store->store_name ?? 'ร้านค้าของคุณ'))

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-3xl shadow-2xl p-6 md:p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-white/20 flex items-center justify-center text-3xl md:text-4xl shadow-lg border-4 border-white/30">
                ⚙️
            </div>
            <div>
                <h1 class="text-2xl md:text-4xl font-bold mb-1">ตั้งค่าร้านค้า</h1>
                <p class="text-indigo-100 text-sm md:text-base">จัดการข้อมูลและการตั้งค่าร้านค้าของคุณ</p>
            </div>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <span class="text-2xl mr-3">✅</span>
                <p class="text-green-700 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Settings Form --}}
    <form method="POST" action="{{ route('seller.store.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <span>🏪</span> ข้อมูลพื้นฐาน
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Store Name --}}
                <div class="md:col-span-2">
                    <label for="store_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        ชื่อร้านค้า <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="store_name" id="store_name"
                           value="{{ old('store_name', $store->store_name) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_name') border-red-500 @enderror"
                           required>
                    @error('store_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Store Description --}}
                <div class="md:col-span-2">
                    <label for="store_description" class="block text-sm font-semibold text-gray-700 mb-2">
                        คำอธิบายร้านค้า
                    </label>
                    <textarea name="store_description" id="store_description" rows="4"
                              class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_description') border-red-500 @enderror"
                    >{{ old('store_description', $store->store_description) }}</textarea>
                    @error('store_description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Store Logo --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        โลโก้ร้านค้า
                    </label>
                    <div class="mb-3">
                        <div id="logo-preview-container" class="relative inline-block">
                            <img id="logo-preview"
                                 src="{{ $store->logo_url ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'128\' height=\'128\'%3E%3Crect fill=\'%23e5e7eb\' width=\'128\' height=\'128\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E' }}"
                                 alt="Store Logo Preview"
                                 class="w-32 h-32 rounded-lg object-cover shadow-md border-2 border-gray-200">
                            <button type="button" id="remove-logo"
                                    class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition {{ $store->store_logo ? '' : 'hidden' }}"
                                    onclick="removeLogo()">
                                ✕
                            </button>
                        </div>
                    </div>
                    <input type="file" name="store_logo" id="store_logo" accept="image/*"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_logo') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">รองรับไฟล์: JPG, PNG, GIF (จะถูกแปลงเป็น WebP อัตโนมัติ, สูงสุด 2MB)</p>
                    @error('store_logo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Store Banner --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        แบนเนอร์ร้านค้า
                    </label>
                    <div class="mb-3 relative">
                        <div id="banner-preview-container" class="relative overflow-hidden rounded-lg border-2 border-gray-200 bg-gray-100" style="height: 300px; cursor: grab;">
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm pointer-events-none z-10" id="banner-drag-hint">
                                <div class="bg-white bg-opacity-90 px-4 py-2 rounded-lg shadow-md">
                                    📸 คลิกและลากขึ้น-ลง เพื่อปรับตำแหน่งแบนเนอร์
                                </div>
                            </div>
                            <img id="banner-preview"
                                 src="{{ $store->banner_url ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'800\' height=\'600\'%3E%3Crect fill=\'%23e5e7eb\' width=\'800\' height=\'600\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Banner Image%3C/text%3E%3C/svg%3E' }}"
                                 alt="Store Banner Preview"
                                 class="select-none"
                                 style="width: 100%; height: auto; position: absolute; top: 0; left: 0; user-select: none; -webkit-user-drag: none; pointer-events: none;"
                                 draggable="false"
                                 id="draggable-banner">
                            <button type="button" id="remove-banner"
                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition z-20 {{ $store->store_banner ? '' : 'hidden' }}"
                                    onclick="removeBanner(event)">
                                ✕
                            </button>
                        </div>
                        <div class="mt-2 flex gap-3 items-center justify-center text-xs">
                            <span id="position-indicator" class="text-gray-600">ตำแหน่ง Y: <span id="position-value" class="font-semibold">0px</span></span>
                            <button type="button" onclick="resetBannerPosition()" class="text-indigo-600 hover:text-indigo-800 font-semibold underline">
                                รีเซ็ตตำแหน่ง
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">💡 เคล็ดลับ: ลากในกรอบสีเทาเพื่อปรับตำแหน่งแบนเนอร์แนวตั้ง</p>
                    </div>
                    <input type="file" name="store_banner" id="store_banner" accept="image/*"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_banner') border-red-500 @enderror">
                    <input type="hidden" name="banner_position_y" id="banner_position_y" value="{{ old('banner_position_y', $store->banner_position_y ?? 0) }}">
                    <p class="mt-1 text-xs text-gray-500">รองรับไฟล์: JPG, PNG, GIF (จะถูกแปลงเป็น WebP อัตโนมัติ, สูงสุด 4MB)</p>
                    @error('store_banner')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <span>📞</span> ข้อมูลติดต่อ
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Store Email --}}
                <div>
                    <label for="store_email" class="block text-sm font-semibold text-gray-700 mb-2">
                        อีเมล
                    </label>
                    <input type="email" name="store_email" id="store_email"
                           value="{{ old('store_email', $store->store_email) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_email') border-red-500 @enderror">
                    @error('store_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Store Phone --}}
                <div>
                    <label for="store_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                        เบอร์โทรศัพท์
                    </label>
                    <input type="text" name="store_phone" id="store_phone"
                           value="{{ old('store_phone', $store->store_phone) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_phone') border-red-500 @enderror">
                    @error('store_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Store Address --}}
                <div class="md:col-span-2">
                    <label for="store_address" class="block text-sm font-semibold text-gray-700 mb-2">
                        ที่อยู่
                    </label>
                    <textarea name="store_address" id="store_address" rows="3"
                              class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_address') border-red-500 @enderror"
                    >{{ old('store_address', $store->store_address) }}</textarea>
                    @error('store_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- City --}}
                <div>
                    <label for="store_city" class="block text-sm font-semibold text-gray-700 mb-2">
                        เมือง/จังหวัด
                    </label>
                    <input type="text" name="store_city" id="store_city"
                           value="{{ old('store_city', $store->store_city) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_city') border-red-500 @enderror">
                    @error('store_city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- State --}}
                <div>
                    <label for="store_state" class="block text-sm font-semibold text-gray-700 mb-2">
                        รัฐ/ภูมิภาค
                    </label>
                    <input type="text" name="store_state" id="store_state"
                           value="{{ old('store_state', $store->store_state) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_state') border-red-500 @enderror">
                    @error('store_state')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Postal Code --}}
                <div>
                    <label for="store_postal_code" class="block text-sm font-semibold text-gray-700 mb-2">
                        รหัสไปรษณีย์
                    </label>
                    <input type="text" name="store_postal_code" id="store_postal_code"
                           value="{{ old('store_postal_code', $store->store_postal_code) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_postal_code') border-red-500 @enderror">
                    @error('store_postal_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Country --}}
                <div>
                    <label for="store_country" class="block text-sm font-semibold text-gray-700 mb-2">
                        ประเทศ
                    </label>
                    <input type="text" name="store_country" id="store_country"
                           value="{{ old('store_country', $store->store_country ?? 'ประเทศไทย') }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('store_country') border-red-500 @enderror">
                    @error('store_country')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Business Information --}}
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <span>🏢</span> ข้อมูลธุรกิจ
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Business Type --}}
                <div>
                    <label for="business_type" class="block text-sm font-semibold text-gray-700 mb-2">
                        ประเภทธุรกิจ
                    </label>
                    <input type="text" name="business_type" id="business_type"
                           value="{{ old('business_type', $store->business_type) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('business_type') border-red-500 @enderror">
                    @error('business_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Company Name --}}
                <div>
                    <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        ชื่อบริษัท
                    </label>
                    <input type="text" name="company_name" id="company_name"
                           value="{{ old('company_name', $store->company_name) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('company_name') border-red-500 @enderror">
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tax ID --}}
                <div>
                    <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-2">
                        เลขประจำตัวผู้เสียภาษี
                    </label>
                    <input type="text" name="tax_id" id="tax_id"
                           value="{{ old('tax_id', $store->tax_id) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('tax_id') border-red-500 @enderror">
                    @error('tax_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Social Media --}}
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <span>📱</span> โซเชียลมีเดีย
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Facebook --}}
                <div>
                    <label for="facebook_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        Facebook URL
                    </label>
                    <input type="url" name="facebook_url" id="facebook_url"
                           value="{{ old('facebook_url', $store->facebook_url) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('facebook_url') border-red-500 @enderror"
                           placeholder="https://facebook.com/yourstore">
                    @error('facebook_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- LINE OA ID --}}
                <div>
                    <label for="line_oa_id" class="block text-sm font-semibold text-gray-700 mb-2">
                        LINE OA ID
                    </label>
                    <input type="text" name="line_oa_id" id="line_oa_id"
                           value="{{ old('line_oa_id', $store->line_oa_id) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('line_oa_id') border-red-500 @enderror"
                           placeholder="@yourstore">
                    @error('line_oa_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Instagram --}}
                <div>
                    <label for="instagram_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        Instagram URL
                    </label>
                    <input type="url" name="instagram_url" id="instagram_url"
                           value="{{ old('instagram_url', $store->instagram_url) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('instagram_url') border-red-500 @enderror"
                           placeholder="https://instagram.com/yourstore">
                    @error('instagram_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Twitter --}}
                <div>
                    <label for="twitter_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        Twitter/X URL
                    </label>
                    <input type="url" name="twitter_url" id="twitter_url"
                           value="{{ old('twitter_url', $store->twitter_url) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('twitter_url') border-red-500 @enderror"
                           placeholder="https://twitter.com/yourstore">
                    @error('twitter_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- TikTok --}}
                <div>
                    <label for="tiktok_url" class="block text-sm font-semibold text-gray-700 mb-2">
                        TikTok URL
                    </label>
                    <input type="url" name="tiktok_url" id="tiktok_url"
                           value="{{ old('tiktok_url', $store->tiktok_url) }}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('tiktok_url') border-red-500 @enderror"
                           placeholder="https://tiktok.com/@yourstore">
                    @error('tiktok_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Store Settings --}}
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <span>💳</span> การตั้งค่าการขาย
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Minimum Order Amount --}}
                <div>
                    <label for="minimum_order_amount" class="block text-sm font-semibold text-gray-700 mb-2">
                        ยอดสั่งซื้อขั้นต่ำ (บาท)
                    </label>
                    <input type="number" name="minimum_order_amount" id="minimum_order_amount"
                           value="{{ old('minimum_order_amount', $store->minimum_order_amount) }}"
                           min="0" step="0.01"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('minimum_order_amount') border-red-500 @enderror">
                    @error('minimum_order_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Shipping Fee --}}
                <div>
                    <label for="shipping_fee" class="block text-sm font-semibold text-gray-700 mb-2">
                        ค่าจัดส่ง (บาท)
                    </label>
                    <input type="number" name="shipping_fee" id="shipping_fee"
                           value="{{ old('shipping_fee', $store->shipping_fee) }}"
                           min="0" step="0.01"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('shipping_fee') border-red-500 @enderror">
                    @error('shipping_fee')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Free Shipping Threshold --}}
                <div>
                    <label for="free_shipping_threshold" class="block text-sm font-semibold text-gray-700 mb-2">
                        ยอดสั่งซื้อสำหรับจัดส่งฟรี (บาท)
                    </label>
                    <input type="number" name="free_shipping_threshold" id="free_shipping_threshold"
                           value="{{ old('free_shipping_threshold', $store->free_shipping_threshold) }}"
                           min="0" step="0.01"
                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition @error('free_shipping_threshold') border-red-500 @enderror">
                    @error('free_shipping_threshold')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Theme Colors --}}
                <div>
                    <label for="primary_color" class="block text-sm font-semibold text-gray-700 mb-2">
                        สีหลัก
                    </label>
                    <div class="flex gap-2">
                        <input type="color" name="primary_color" id="primary_color"
                               value="{{ old('primary_color', $store->primary_color ?? '#6366f1') }}"
                               class="h-12 w-16 rounded-lg border-2 border-gray-200 cursor-pointer">
                        <input type="text" value="{{ old('primary_color', $store->primary_color ?? '#6366f1') }}"
                               class="flex-1 px-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50" readonly>
                    </div>
                    @error('primary_color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="secondary_color" class="block text-sm font-semibold text-gray-700 mb-2">
                        สีรอง
                    </label>
                    <div class="flex gap-2">
                        <input type="color" name="secondary_color" id="secondary_color"
                               value="{{ old('secondary_color', $store->secondary_color ?? '#8b5cf6') }}"
                               class="h-12 w-16 rounded-lg border-2 border-gray-200 cursor-pointer">
                        <input type="text" value="{{ old('secondary_color', $store->secondary_color ?? '#8b5cf6') }}"
                               class="flex-1 px-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50" readonly>
                    </div>
                    @error('secondary_color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Options --}}
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="enable_cod" id="enable_cod" value="1"
                               {{ old('enable_cod', $store->enable_cod) ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <label for="enable_cod" class="ml-3 text-sm font-semibold text-gray-700">
                            เปิดรับชำระเงินปลายทาง (COD)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="enable_reviews" id="enable_reviews" value="1"
                               {{ old('enable_reviews', $store->enable_reviews) ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <label for="enable_reviews" class="ml-3 text-sm font-semibold text-gray-700">
                            เปิดให้ลูกค้ารีวิวสินค้า
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-end">
            <a href="{{ route('seller.dashboard') }}"
               class="px-8 py-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-xl transition text-center">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg transform hover:scale-105 transition">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Sync color inputs with text display
    document.getElementById('primary_color').addEventListener('input', function(e) {
        this.nextElementSibling.value = e.target.value;
    });

    document.getElementById('secondary_color').addEventListener('input', function(e) {
        this.nextElementSibling.value = e.target.value;
    });

    // === Logo Preview Functionality ===
    const logoInput = document.getElementById('store_logo');
    const logoPreview = document.getElementById('logo-preview');
    const removeLogoBtn = document.getElementById('remove-logo');
    let logoFile = null;

    logoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            logoFile = file;
            const reader = new FileReader();
            reader.onload = function(event) {
                logoPreview.src = event.target.result;
                removeLogoBtn.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    function removeLogo() {
        logoPreview.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'128\' height=\'128\'%3E%3Crect fill=\'%23e5e7eb\' width=\'128\' height=\'128\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E';
        logoInput.value = '';
        logoFile = null;
        removeLogoBtn.classList.add('hidden');
    }

    // === Banner Preview & Drag Functionality ===
    const bannerInput = document.getElementById('store_banner');
    const bannerPreview = document.getElementById('banner-preview');
    const draggableBanner = document.getElementById('draggable-banner');
    const removeBannerBtn = document.getElementById('remove-banner');
    const bannerContainer = document.getElementById('banner-preview-container');
    const bannerPositionInput = document.getElementById('banner_position_y');
    const bannerDragHint = document.getElementById('banner-drag-hint');
    const positionValue = document.getElementById('position-value');
    let bannerFile = null;
    let isDragging = false;
    let startY = 0;
    let startPosY = 0;
    let currentY = {{ old('banner_position_y', $store->banner_position_y ?? 0) }};

    // Check if there's an existing banner
    @if($store->store_banner)
        bannerDragHint.style.display = 'none';
    @endif

    // Update position display
    function updatePositionDisplay(value) {
        positionValue.textContent = Math.round(value) + 'px';
    }

    // Apply transform - simple translateY only
    function applyBannerTransform(yPos) {
        draggableBanner.style.transform = `translateY(${yPos}px)`;
    }

    // Initialize banner position on image load
    bannerPreview.addEventListener('load', function() {
        console.log('Banner image loaded');
        if (currentY !== 0) {
            applyBannerTransform(currentY);
            updatePositionDisplay(currentY);
        }
        // Hide hint if real image is loaded (not placeholder SVG)
        if (!bannerPreview.src.includes('data:image/svg+xml')) {
            bannerDragHint.style.display = 'none';
        }
    });

    bannerInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            bannerFile = file;
            const reader = new FileReader();
            reader.onload = function(event) {
                bannerPreview.src = event.target.result;
                removeBannerBtn.classList.remove('hidden');
                bannerDragHint.style.display = 'none';
                // Reset position on new image
                currentY = 0;
                applyBannerTransform(0);
                bannerPositionInput.value = 0;
                updatePositionDisplay(0);
            };
            reader.readAsDataURL(file);
        }
    });

    function removeBanner(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        bannerPreview.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'800\' height=\'600\'%3E%3Crect fill=\'%23e5e7eb\' width=\'800\' height=\'600\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Banner Image%3C/text%3E%3C/svg%3E';
        bannerInput.value = '';
        bannerFile = null;
        removeBannerBtn.classList.add('hidden');
        bannerDragHint.style.display = 'flex';
        resetBannerPosition();
    }

    function resetBannerPosition() {
        currentY = 0;
        applyBannerTransform(0);
        bannerPositionInput.value = 0;
        updatePositionDisplay(0);
    }

    // Drag functionality - Listen on container instead of image
    bannerContainer.addEventListener('mousedown', function(e) {
        // Don't start dragging if clicking on remove button
        if (e.target.closest('#remove-banner')) {
            return;
        }

        isDragging = true;
        startY = e.clientY;
        startPosY = currentY;
        bannerContainer.style.cursor = 'grabbing';
        e.preventDefault();
        console.log('Started dragging from Y:', startY, 'Current position:', currentY);
    });

    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;

        const deltaY = e.clientY - startY;
        const newY = startPosY + deltaY;

        const containerHeight = bannerContainer.offsetHeight;
        const imageHeight = draggableBanner.offsetHeight;

        console.log('Container height:', containerHeight, 'Image height:', imageHeight);

        // Calculate bounds
        const maxY = 0;
        const minY = Math.min(0, containerHeight - imageHeight);

        // Constrain the movement
        currentY = Math.max(minY, Math.min(maxY, newY));
        applyBannerTransform(currentY);
        bannerPositionInput.value = Math.round(currentY);
        updatePositionDisplay(currentY);
    });

    document.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            bannerContainer.style.cursor = 'grab';
            console.log('Stopped dragging at position:', currentY);
        }
    });

    // Touch support for mobile
    bannerContainer.addEventListener('touchstart', function(e) {
        if (e.target.closest('#remove-banner')) {
            return;
        }

        isDragging = true;
        startY = e.touches[0].clientY;
        startPosY = currentY;
        e.preventDefault();
    });

    document.addEventListener('touchmove', function(e) {
        if (!isDragging) return;

        const deltaY = e.touches[0].clientY - startY;
        const newY = startPosY + deltaY;

        const containerHeight = bannerContainer.offsetHeight;
        const imageHeight = draggableBanner.offsetHeight;

        const maxY = 0;
        const minY = Math.min(0, containerHeight - imageHeight);

        currentY = Math.max(minY, Math.min(maxY, newY));
        applyBannerTransform(currentY);
        bannerPositionInput.value = Math.round(currentY);
        updatePositionDisplay(currentY);
    });

    document.addEventListener('touchend', function() {
        if (isDragging) {
            isDragging = false;
        }
    });

    // Initialize position display and transform
    if (currentY !== 0) {
        applyBannerTransform(currentY);
    }
    updatePositionDisplay(currentY);
</script>
@endpush
@endsection

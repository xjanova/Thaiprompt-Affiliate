@extends('layouts.seller')

@section('title', 'เพิ่มสินค้าใหม่')

@php
    // ดึงค่า platform fee จาก settings (readonly)
    $platformFeeFromSettings = \App\Models\MarketplaceSetting::get('platform_fee_percentage', 10);
    $vatFromSettings = \App\Models\MarketplaceSetting::get('vat_percentage', 7);

    // ดึง MLM settings (ค่าปัจจุบันจาก database)
    // commission_per_pv = ค่าคอมมิชชันต่อ 1 PV (บาท) - ใช้แปลง PV เป็นเงินที่หักเป็นค่า MLM Commission
    $commissionPerPv = \App\Models\MlmGlobalSetting::get('commission_per_pv', 1.00);
    $vatEnabled = \App\Models\MlmGlobalSetting::get('vat_enabled', true);
@endphp

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('seller.products.index') }}"
           class="flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">เพิ่มสินค้าใหม่</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">กรอกข้อมูลสินค้าของคุณให้ครบถ้วน</p>
        </div>
    </div>

    {{-- Grid Layout: Form + Calculator --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="productForm({{ json_encode([
        'platformFee' => $platformFeeFromSettings,
        'vat' => $vatFromSettings,
        'commissionPerPv' => $commissionPerPv,
        'vatEnabled' => $vatEnabled,
    ]) }})">
        {{-- Left Column: Form (2/3 width on desktop) --}}
        <div class="lg:col-span-2 space-y-6">
    <!-- Form -->
    <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 1. Basic Information -->
        <x-modern-card
            title="ข้อมูลพื้นฐาน"
            description="ข้อมูลหลักของสินค้าที่จะแสดงต่อลูกค้า"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'/></svg>'"
        >
            <div class="grid grid-cols-1 gap-5">
                <x-form-field
                    label="ชื่อสินค้า"
                    name="name"
                    type="text"
                    :value="old('name')"
                    placeholder="เช่น: iPhone 15 Pro Max 256GB"
                    :required="true"
                    helpText="ใช้ชื่อที่ชัดเจนและง่ายต่อการค้นหา"
                    tooltip="ชื่อสินค้าควรมีข้อมูลครบถ้วน เช่น ยี่ห้อ รุ่น ขนาด สี เพื่อให้ลูกค้าเข้าใจได้ง่าย"
                />

                <x-form-field
                    label="คำอธิบายสั้น"
                    name="short_description"
                    type="textarea"
                    :rows="2"
                    :value="old('short_description')"
                    placeholder="สรุปจุดเด่นของสินค้าในประโยคสั้นๆ"
                    helpText="จะแสดงในหน้ารายการสินค้า (แนะนำไม่เกิน 150 ตัวอักษร)"
                />

                <x-form-field
                    label="คำอธิบายสินค้าแบบเต็ม"
                    name="description"
                    type="textarea"
                    :rows="6"
                    :value="old('description')"
                    placeholder="อธิบายรายละเอียดสินค้า คุณสมบัติ ข้อดี วิธีการใช้งาน ฯลฯ"
                    helpText="อธิบายรายละเอียดให้ครบถ้วนเพื่อให้ลูกค้าตัดสินใจได้ง่าย"
                    tooltip="ควรมีข้อมูล: คุณสมบัติหลัก, วัสดุ, วิธีใช้, ข้อดี, สิ่งที่ได้รับในกล่อง"
                />
            </div>
        </x-modern-card>

        <!-- 2. Pricing -->
        <x-modern-card
            title="ราคาและต้นทุน"
            description="กำหนดราคาขายและราคาทุนเพื่อคำนวณกำไร"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'/></svg>'"
        >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <x-form-field
                    label="ราคาขาย"
                    name="price"
                    type="number"
                    :value="old('price', 1000)"
                    placeholder="0.00"
                    :required="true"
                    step="0.01"
                    min="0"
                    helpText="ราคาที่ลูกค้าจะจ่าย (บาท)"
                    tooltip="ราคาขายควรคำนึงถึงต้นทุน, ค่าคอมมิชชั่น, และกำไรที่ต้องการ"
                    x-model.number="price"
                />

                <x-form-field
                    label="ราคาเปรียบเทียบ"
                    name="compare_at_price"
                    type="number"
                    :value="old('compare_at_price')"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    helpText="ราคาก่อนลด (ถ้ามี)"
                    tooltip="แสดงราคาเต็มที่ขีดทับเพื่อให้ลูกค้าเห็นส่วนลด"
                />

                <x-form-field
                    label="ราคาทุน"
                    name="cost_price"
                    type="number"
                    :value="old('cost_price')"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    helpText="สำหรับคำนวณกำไร"
                    tooltip="ใช้ภายในเท่านั้น ลูกค้าจะไม่เห็นข้อมูลนี้"
                    x-model.number="costPrice"
                />
            </div>

            {{-- Platform Fee & VAT (Readonly - ดึงจาก Settings) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
                {{-- Platform Fee - Readonly --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        ค่าแพลตฟอร์ม (%)
                        <span class="ml-1 text-xs text-gray-500">(กำหนดโดยระบบ)</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               :value="platformFee.toFixed(2) + '%'"
                               disabled
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 cursor-not-allowed">
                        <input type="hidden" name="platform_fee_percentage" :value="platformFee">
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        ค่าธรรมเนียมที่แพลตฟอร์มหักจากการขาย
                    </p>
                </div>

                {{-- VAT - Readonly --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        VAT (%)
                        <span class="ml-1 text-xs text-gray-500">(กำหนดโดยระบบ)</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               :value="vat.toFixed(2) + '%'"
                               disabled
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 cursor-not-allowed">
                        <input type="hidden" name="vat_percentage" :value="vat">
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        ภาษีมูลค่าเพิ่มที่หักจากรายได้สุทธิ
                    </p>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">คำนวณรายได้สุทธิ</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ตรวจสอบรายได้สุทธิแบบเรียลไทม์ในตัวคำนวณด้านขวา →<br>
                            รวมการหัก: ค่าแพลตฟอร์ม, Cashback, VAT, และ MLM Commission
                        </p>
                    </div>
                </div>
            </div>
        </x-modern-card>

        <!-- 3. MLM & Commissions -->
        <x-modern-card
            title="MLM & คอมมิชชั่น"
            description="กำหนด PV และ Cashback สำหรับระบบ MLM"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z\'/></svg>'"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-form-field
                    label="PV (Point Value)"
                    name="pv_value"
                    type="number"
                    :value="old('pv_value', 0)"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    helpText="คะแนน PV สำหรับคำนวณ MLM Commission"
                    tooltip="PV จะถูกใช้คำนวณค่าคอมมิชชั่นในระบบ MLM - ยิ่ง PV สูง ค่าคอมมิชชั่นยิ่งมาก (0 = ใช้ราคาเป็น PV)"
                    x-model.number="pvValue"
                />

                <x-form-field
                    label="% Cashback"
                    name="cashback_percentage"
                    type="number"
                    :value="old('cashback_percentage', 0)"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                    max="100"
                    helpText="เปอร์เซ็นต์ Cashback ให้ลูกค้า"
                    tooltip="Cashback จะคืนให้ลูกค้าหลังสั่งซื้อ (ผู้ขายจ่าย)"
                    x-model.number="cashback"
                />
            </div>

            <!-- MLM Preview -->
            <div class="mt-4 p-4 bg-gradient-to-r from-orange-50 to-pink-50 dark:from-orange-900/20 dark:to-pink-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">ข้อมูล MLM</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            • PV จะถูกนำไปคำนวณค่าคอมมิชชั่นตามสายงาน MLM<br>
                            • คอมมิชชั่นจะถูกแจกจ่ายให้อัพไลน์ตามระดับชั้น<br>
                            • Cashback จะคืนให้ลูกค้าทันที หลังยืนยันการสั่งซื้อ
                        </p>
                    </div>
                </div>
            </div>
        </x-modern-card>

        <!-- 4. Inventory -->
        <x-modern-card
            title="สต็อกสินค้า"
            description="จัดการจำนวนสินค้าและการติดตามสต็อก"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4\'/></svg>'"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- SKU with Auto-Generate --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        SKU (รหัสสินค้า)
                    </label>
                    <div class="flex gap-2">
                        <input type="text"
                               name="sku"
                               x-model="sku"
                               :placeholder="skuPlaceholder"
                               class="flex-1 px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                        <button type="button"
                                @click="generateSku()"
                                class="px-4 py-3 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white rounded-xl font-medium transition-all flex items-center gap-2 whitespace-nowrap"
                                title="สร้าง SKU อัตโนมัติ">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="hidden sm:inline">สร้าง SKU</span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="!sku" class="text-orange-600 dark:text-orange-400">
                            💡 คลิก "สร้าง SKU" หรือปล่อยว่างเพื่อให้ระบบสร้างอัตโนมัติ
                        </span>
                        <span x-show="sku">
                            ✓ SKU สามารถแก้ไขได้ตามต้องการ
                        </span>
                    </p>
                </div>

                <x-form-field
                    label="จำนวนสต็อก"
                    name="stock_quantity"
                    type="number"
                    :value="old('stock_quantity', 0)"
                    placeholder="0"
                    :required="true"
                    min="0"
                    helpText="จำนวนสินค้าที่มีพร้อมขาย"
                    tooltip="ระบบจะหยุดให้สั่งซื้อเมื่อสต็อกหมด (ถ้าเปิดการติดตามสต็อก)"
                />
            </div>

            <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                <input type="checkbox" name="track_inventory" value="1" checked
                       id="track_inventory"
                       class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                <label for="track_inventory" class="flex-1 cursor-pointer">
                    <span class="font-medium text-gray-900 dark:text-white">เปิดใช้งานการติดตามสต็อก</span>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                        ระบบจะติดตามจำนวนสินค้าและหยุดให้สั่งซื้อเมื่อสินค้าหมด
                    </p>
                </label>
            </div>
        </x-modern-card>

        <!-- 4.5 Shipping Settings -->
        <x-modern-card
            title="การจัดส่งสินค้า"
            description="ตั้งค่าวิธีคิดค่าจัดส่งสำหรับสินค้านี้"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 17h8M8 17v-4m8 4v-4m-8 0h8m-8 0L6 9m10 4l2-4M6 9h12M6 9L4 5h16l-2 4\'/></svg>'"
        >
            <div x-data="{ shippingMethod: '{{ old('shipping_method', 'store_default') }}' }" class="space-y-5">
                {{-- วิธีคิดค่าจัดส่ง --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        วิธีคิดค่าจัดส่ง
                    </label>
                    <select name="shipping_method"
                            x-model="shippingMethod"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                        <option value="store_default">ใช้ค่าเริ่มต้นของร้าน</option>
                        <option value="free">ฟรีค่าจัดส่ง</option>
                        <option value="flat_rate">คิดแบบเหมา (Flat Rate)</option>
                        <option value="weight_based">คิดตามน้ำหนัก</option>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        เลือกวิธีคำนวณค่าจัดส่งสำหรับสินค้าชิ้นนี้
                    </p>
                </div>

                {{-- ค่าจัดส่งแบบเหมา (แสดงเมื่อเลือก flat_rate) --}}
                <div x-show="shippingMethod === 'flat_rate'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-form-field
                        label="ค่าจัดส่งต่อชิ้น (บาท)"
                        name="shipping_fee"
                        type="number"
                        :value="old('shipping_fee', 50)"
                        placeholder="50"
                        step="0.01"
                        min="0"
                        helpText="ค่าจัดส่งคงที่ต่อชิ้นสินค้า"
                    />

                    <x-form-field
                        label="ฟรีค่าส่งเมื่อซื้อขั้นต่ำ (บาท)"
                        name="free_shipping_min_amount"
                        type="number"
                        :value="old('free_shipping_min_amount')"
                        placeholder="เช่น 500 (ปล่อยว่างถ้าไม่ต้องการ)"
                        step="0.01"
                        min="0"
                        helpText="ยอดขั้นต่ำเพื่อรับฟรีค่าส่ง (ปล่อยว่าง = ไม่มี)"
                    />
                </div>

                {{-- น้ำหนักสำหรับคำนวณ (แสดงเมื่อเลือก weight_based) --}}
                <div x-show="shippingMethod === 'weight_based'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-form-field
                        label="น้ำหนักจัดส่ง (กก.)"
                        name="shipping_weight_kg"
                        type="number"
                        :value="old('shipping_weight_kg')"
                        placeholder="เช่น 0.5"
                        step="0.001"
                        min="0"
                        :required="false"
                        helpText="น้ำหนักรวมบรรจุภัณฑ์สำหรับคำนวณค่าส่ง"
                    />

                    <x-form-field
                        label="ฟรีค่าส่งเมื่อซื้อขั้นต่ำ (บาท)"
                        name="free_shipping_min_amount_weight"
                        type="number"
                        :value="old('free_shipping_min_amount')"
                        placeholder="เช่น 1000 (ปล่อยว่างถ้าไม่ต้องการ)"
                        step="0.01"
                        min="0"
                        helpText="ยอดขั้นต่ำเพื่อรับฟรีค่าส่ง (ปล่อยว่าง = ไม่มี)"
                    />
                </div>

                {{-- ข้อมูลสรุปแต่ละวิธี --}}
                <div class="p-4 rounded-xl border" :class="{
                    'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': shippingMethod === 'free',
                    'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800': shippingMethod === 'flat_rate',
                    'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800': shippingMethod === 'weight_based',
                    'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-700': shippingMethod === 'store_default'
                }">
                    <div x-show="shippingMethod === 'store_default'" class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">ค่าเริ่มต้นของร้าน</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                ระบบจะใช้ค่าจัดส่งที่กำหนดไว้ในการตั้งค่าร้านค้า ฟรีค่าส่งเมื่อซื้อครบตามยอดที่ร้านกำหนด
                            </p>
                        </div>
                    </div>
                    <div x-show="shippingMethod === 'free'" class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium text-green-700 dark:text-green-300">ฟรีค่าจัดส่ง</p>
                            <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                                ลูกค้าไม่ต้องจ่ายค่าจัดส่งสำหรับสินค้าชิ้นนี้
                            </p>
                        </div>
                    </div>
                    <div x-show="shippingMethod === 'flat_rate'" class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium text-blue-700 dark:text-blue-300">ค่าจัดส่งแบบเหมา</p>
                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                คิดค่าจัดส่งคงที่ต่อชิ้น ไม่ว่าน้ำหนักเท่าไหร่
                            </p>
                        </div>
                    </div>
                    <div x-show="shippingMethod === 'weight_based'" class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium text-purple-700 dark:text-purple-300">คิดตามน้ำหนัก</p>
                            <p class="text-sm text-purple-600 dark:text-purple-400 mt-1">
                                ระบบจะคำนวณค่าจัดส่งจากน้ำหนักสินค้าตามอัตราที่กำหนดในระบบ (ตามมาตรฐานสากล)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-modern-card>

        <!-- 5. Images -->
        <x-modern-card
            title="รูปภาพสินค้า"
            description="อัปโหลดรูปภาพเพื่อแสดงสินค้าของคุณ"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg>'"
        >
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">
                        รูปหลัก <span class="text-red-500">*</span>
                    </label>
                    <x-image-upload
                        name="main_image"
                        :multiple="false"
                        :maxFiles="1"
                        :maxSize="5"
                        :required="true"
                    />
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span>ขนาดแนะนำ: 1200x1200px สำหรับคุณภาพที่ดีที่สุด | รองรับ: JPG, PNG, WebP สูงสุด 5MB</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">
                        รูปเพิ่มเติม (Gallery)
                    </label>
                    <x-image-upload
                        name="images"
                        :multiple="true"
                        :maxFiles="10"
                        :maxSize="5"
                    />
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span>อัปโหลดได้สูงสุด 10 รูป เพื่อแสดงมุมมองต่างๆ ของสินค้า</span>
                    </p>
                </div>
            </div>
        </x-modern-card>

        <!-- 6. Category & Details -->
        <x-modern-card
            title="หมวดหมู่และรายละเอียด"
            description="จัดหมวดหมู่และข้อมูลเพิ่มเติม"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z\'/></svg>'"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-form-field
                    label="หมวดหมู่สินค้า"
                    name="category_id"
                    type="select"
                    :value="old('category_id')"
                    :required="true"
                    :options="['' => '-- เลือกหมวดหมู่ --'] + $categories->pluck('name', 'id')->toArray()"
                    helpText="เลือกหมวดหมู่ที่เหมาะสมกับสินค้า"
                    tooltip="หมวดหมู่ช่วยให้ลูกค้าค้นหาสินค้าได้ง่ายขึ้น"
                />

                <x-form-field
                    label="แบรนด์"
                    name="brand"
                    type="text"
                    :value="old('brand')"
                    placeholder="เช่น: Apple, Samsung, Nike"
                    helpText="ชื่อแบรนด์หรือผู้ผลิต"
                />

                <x-form-field
                    label="น้ำหนัก (กรัม)"
                    name="weight"
                    type="number"
                    :value="old('weight')"
                    placeholder="0"
                    step="0.01"
                    min="0"
                    helpText="น้ำหนักสำหรับคำนวณค่าจัดส่ง"
                />

                <x-form-field
                    label="ขนาด"
                    name="dimensions"
                    type="text"
                    :value="old('dimensions')"
                    placeholder="เช่น: 10 x 20 x 5 cm"
                    helpText="ขนาดสินค้า (กว้าง x ยาว x สูง)"
                />
            </div>
        </x-modern-card>

        <!-- Action Buttons -->
        <div class="flex items-center gap-4 pb-8">
            <button type="submit"
                    class="flex-1 md:flex-none px-8 py-4 bg-gradient-to-r from-orange-600 to-pink-600 hover:from-orange-700 hover:to-pink-700 text-white text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    บันทึกสินค้า
                </span>
            </button>

            <a href="{{ route('seller.products.index') }}"
               class="px-8 py-4 bg-white dark:bg-slate-800 border-2 border-gray-300 dark:border-slate-700 text-gray-700 dark:text-gray-300 text-lg font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-slate-750 transition-colors">
                ยกเลิก
            </a>
        </div>
    </form>
        </div>
        {{-- End Left Column --}}

        {{-- Right Column: Inline Earnings Calculator (sync กับ form โดยตรง) --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-6">
                {{-- Inline Earnings Calculator --}}
                <div class="backdrop-blur-xl bg-gradient-to-br from-blue-50/90 to-indigo-100/80 dark:from-blue-900/40 dark:to-indigo-800/30 rounded-2xl border border-blue-200/50 dark:border-blue-700/30 p-6">

                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            คำนวณรายได้สุทธิ
                        </h3>
                        <button @click="showCalculatorHelp = !showCalculatorHelp"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-700 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            วิธีคำนวณ
                        </button>
                    </div>

                    {{-- Calculator Help Modal --}}
                    <div x-show="showCalculatorHelp" x-transition @click.away="showCalculatorHelp = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                        <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-lg max-h-[80vh] overflow-y-auto">
                            <h4 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">🧮 สูตรการคำนวณ</h4>
                            <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                                <div><strong>1. ราคาขาย:</strong> ราคาที่ลูกค้าจ่าย</div>
                                <div><strong>2. หัก Platform Fee:</strong> ค่าบริการแพลตฟอร์ม</div>
                                <div><strong>3. หัก Cashback:</strong> คุณจ่ายเอง เป็นค่าการตลาด</div>
                                <div><strong>4. หัก VAT:</strong> ภาษีมูลค่าเพิ่ม (คุณจ่าย)</div>
                                <div><strong>5. หัก MLM Commission:</strong> คำนวณจาก PV</div>
                                <div class="pt-2 border-t dark:border-gray-700"><strong>= รายได้สุทธิ</strong> (โอนเข้า wallet)</div>

                                {{-- PV Calculation Explanation --}}
                                <div class="mt-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                                    <h5 class="font-bold text-indigo-800 dark:text-indigo-200 mb-2">📊 การคำนวณ MLM Commission จาก PV</h5>
                                    <div class="text-indigo-700 dark:text-indigo-300 space-y-1">
                                        <div>• <strong>อัตราแปลง PV:</strong> 1 PV = ฿<span x-text="commissionPerPv.toFixed(2)"></span></div>
                                        <div>• <strong>สูตร:</strong> PV × % Level × ฿<span x-text="commissionPerPv.toFixed(2)"></span></div>
                                        <div class="text-xs mt-2">ตัวอย่าง: 100 PV, Level 1 (10%) = 100 × 10% × ฿<span x-text="commissionPerPv.toFixed(2)"></span> = ฿<span x-text="(100 * 0.1 * commissionPerPv).toFixed(2)"></span></div>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-yellow-800 dark:text-yellow-200">
                                    <strong>💡 เคล็ดลับ:</strong> ยิ่ง Cashback & PV สูง = ลูกค้าสนใจมากขึ้น แต่กำไรคุณลด
                                </div>
                            </div>
                            <button @click="showCalculatorHelp = false" class="mt-4 w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                เข้าใจแล้ว
                            </button>
                        </div>
                    </div>

                    {{-- Quick Input Section (กรอกค่าได้โดยตรงใน Calculator) --}}
                    <div class="space-y-3 mb-4 pb-4 border-b border-blue-200/50 dark:border-blue-700/30">
                        {{-- ราคาขาย --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ราคาขาย (บาท)</label>
                            <input type="number"
                                   x-model.number="price"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        {{-- Cashback & PV in row --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cashback (%)</label>
                                <input type="number"
                                       x-model.number="cashback"
                                       min="0"
                                       max="100"
                                       step="0.5"
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">PV (MLM)</label>
                                <input type="number"
                                       x-model.number="pvValue"
                                       min="0"
                                       step="1"
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                        </div>

                        {{-- Cost Price (optional) --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ราคาทุน (บาท) - ไม่บังคับ</label>
                            <input type="number"
                                   x-model.number="costPrice"
                                   min="0"
                                   step="0.01"
                                   placeholder="กรอกเพื่อดูกำไร"
                                   class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>

                    {{-- Calculation Result (Real-time) --}}
                    <div class="space-y-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            รายละเอียดการหัก
                        </h4>

                        {{-- ราคาขาย --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ราคาขาย:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                ฿<span x-text="Number(price).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>

                        {{-- Platform Fee --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                ค่า Platform (<span x-text="Number(platformFee).toFixed(2)"></span>%):
                            </span>
                            <span class="text-red-600 dark:text-red-400">
                                -฿<span x-text="(calc?.platformFeeAmount ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>

                        {{-- Cashback (แสดงเสมอ) --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                Cashback (<span x-text="Number(cashback).toFixed(2)"></span>%):
                            </span>
                            <span :class="cashback > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 dark:text-gray-500'">
                                -฿<span x-text="(calc?.cashbackAmount ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>

                        {{-- VAT --}}
                        <template x-if="vatEnabled">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">
                                    VAT (<span x-text="Number(vat).toFixed(2)"></span>%):
                                </span>
                                <span class="text-yellow-600 dark:text-yellow-400">
                                    -฿<span x-text="(calc?.vatAmount ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                                </span>
                            </div>
                        </template>

                        {{-- MLM Commission (Unilevel + Binary) --}}
                        <div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">
                                    คอม MLM (<span x-text="Number(pvValue).toLocaleString()"></span> PV):
                                </span>
                                <span :class="Number(pvValue) > 0 ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-400 dark:text-gray-500'">
                                    -฿<span x-text="(calc?.mlmCommissionTotal ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                                </span>
                            </div>

                            {{-- PV Calculation Info --}}
                            <div x-show="pvValue > 0" class="mt-1 text-xs text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 rounded px-2 py-1">
                                <span x-text="Number(pvValue).toLocaleString()"></span> PV × ฿<span x-text="Number(commissionPerPv).toFixed(2)"></span>
                                = <strong>฿<span x-text="(pvValue * commissionPerPv).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span></strong>
                                <span class="text-gray-500 dark:text-gray-400 ml-1">(แบ่งให้ Unilevel + Binary)</span>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-blue-300/50 dark:border-blue-600/30 my-3"></div>

                        {{-- รายได้สุทธิ --}}
                        <div class="flex justify-between items-center pt-2">
                            <span class="font-bold text-gray-900 dark:text-gray-100">คุณจะได้รับ:</span>
                            <span class="text-3xl font-bold text-green-600 dark:text-green-400">
                                ฿<span x-text="(calc?.netEarnings ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                            </span>
                        </div>

                        {{-- เปอร์เซ็นต์รายได้ --}}
                        <div class="text-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                คุณได้รับ
                                <span class="font-bold text-lg" :class="{
                                    'text-green-600 dark:text-green-400': (calc?.earningsPercentage ?? 0) > 70,
                                    'text-yellow-600 dark:text-yellow-400': (calc?.earningsPercentage ?? 0) > 50 && (calc?.earningsPercentage ?? 0) <= 70,
                                    'text-orange-600 dark:text-orange-400': (calc?.earningsPercentage ?? 0) <= 50
                                }" x-text="(calc?.earningsPercentage ?? 0).toFixed(2)"></span>%
                            </span>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-600 transition-all duration-300"
                                 :style="{ width: Math.min((calc?.earningsPercentage ?? 0), 100) + '%' }"></div>
                        </div>

                        {{-- กำไรสุทธิ (แสดงเสมอ) --}}
                        <div class="mt-4 p-4 rounded-lg bg-gradient-to-br from-green-50/90 to-emerald-100/80 dark:from-green-900/40 dark:to-emerald-800/30 border border-green-200/50 dark:border-green-700/30">
                            {{-- ถ้ามี costPrice --}}
                            <div x-show="Number(costPrice) > 0" x-cloak>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ต้นทุน:</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        ฿<span x-text="Number(costPrice).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-gray-900 dark:text-gray-100">กำไรสุทธิ:</span>
                                    <span class="text-2xl font-bold"
                                          :class="(calc?.netProfit ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <span x-text="(calc?.netProfit ?? 0) >= 0 ? '+' : ''"></span>฿<span x-text="Math.abs(calc?.netProfit ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                                    </span>
                                </div>
                                <div class="text-center mt-2">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        Profit Margin:
                                        <span class="font-bold" x-text="(calc?.profitMargin ?? 0).toFixed(2)"></span>%
                                    </span>
                                </div>
                            </div>

                            {{-- ถ้ายังไม่มี costPrice --}}
                            <div x-show="Number(costPrice) <= 0" class="text-center">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold text-gray-900 dark:text-gray-100">กำไรสุทธิ:</span>
                                    <span class="text-xl font-bold text-gray-400 dark:text-gray-500">
                                        รอกรอกราคาทุน
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    💡 กรอก "ราคาทุน" ด้านบนเพื่อดูกำไรสุทธิ
                                </p>
                            </div>
                        </div>

                        {{-- Tip --}}
                        <div class="mt-4 p-3 rounded-lg bg-blue-50/50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-700/30">
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                💡 <strong>เคล็ดลับ:</strong> ปรับค่า Cashback และ PV เพื่อหายอดขายเพิ่ม แต่อย่าลืมคำนวณกำไรด้วย!
                            </p>
                        </div>
                    </div>
                </div>
                {{-- End Inline Earnings Calculator --}}
            </div>
        </div>
        {{-- End Right Column --}}
    </div>
    {{-- End Grid Layout --}}
</div>

@push('scripts')
<script>
function productForm(config) {
    return {
        // ===== SKU Generator =====
        sku: '{{ old('sku', '') }}',
        skuPlaceholder: 'เช่น: PRD-XXXXXXXX',

        /**
         * สร้าง SKU อัตโนมัติ
         * รูปแบบ: PRD-XXXXXXXX (8 ตัวอักษรสุ่ม)
         */
        generateSku() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let randomPart = '';
            for (let i = 0; i < 8; i++) {
                randomPart += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.sku = 'PRD-' + randomPart;
        },

        // ===== Pricing Data =====
        price: {{ old('price', 1000) }},
        costPrice: {{ old('cost_price', 0) }},
        platformFee: Number(config.platformFee) || 10,
        vat: Number(config.vat) || 7,
        cashback: {{ old('cashback_percentage', 0) }},
        pvValue: {{ old('pv_value', 0) }},

        // ===== MLM Settings (จาก config - ค่าปัจจุบันจาก database) =====
        // commission_per_pv = ค่าคอมมิชชันต่อ 1 PV (บาท) - ใช้แปลง PV เป็นเงิน
        commissionPerPv: Number(config.commissionPerPv) || 1.00,
        vatEnabled: config.vatEnabled !== false,

        // ===== UI State =====
        showCalculatorHelp: false,

        // ===== Calculator (Computed) =====
        get calc() {
            // แปลงค่าให้เป็นตัวเลขทั้งหมดเพื่อป้องกัน NaN
            const price = Number(this.price) || 0;
            const platformFeeRate = Number(this.platformFee) || 0;
            const cashbackRate = Number(this.cashback) || 0;
            const vatRate = Number(this.vat) || 0;
            const costPrice = Number(this.costPrice) || 0;

            // 1. Platform Fee
            const platformFeeAmount = price * (platformFeeRate / 100);

            // 2. Cashback
            const cashbackAmount = price * (cashbackRate / 100);

            // 3. ก่อนหัก VAT
            const beforeTax = price - platformFeeAmount - cashbackAmount;

            // 4. VAT
            const vatAmount = this.vatEnabled ? (beforeTax * (vatRate / 100)) : 0;

            // 5. MLM Commission (คำนวณจาก PV)
            const mlmResult = this.calculateMlmCommission();

            // 6. รายได้สุทธิ
            const netEarnings = beforeTax - vatAmount - mlmResult.total;

            // 7. กำไร (ถ้ามี cost)
            const netProfit = netEarnings - costPrice;
            const profitMargin = price > 0 ? (netProfit / price) * 100 : 0;

            return {
                platformFeeAmount,
                cashbackAmount,
                vatAmount,
                mlmCommissionTotal: mlmResult.total,
                netEarnings: Math.max(0, netEarnings),
                earningsPercentage: price > 0 ? (Math.max(0, netEarnings) / price) * 100 : 0,
                netProfit,
                profitMargin,
            };
        },

        /**
         * คำนวณ MLM Commission จาก PV
         *
         * สูตร: PV × commissionPerPv = THB (หักทั้งก้อน)
         * เงินนี้จะถูกแบ่งให้ Unilevel + Binary ภายหลัง
         *
         * ตัวอย่าง: 10000 PV × ฿0.01 = ฿100.00 (หักทั้งหมด)
         */
        calculateMlmCommission() {
            const pv = Number(this.pvValue) || 0;
            const commissionRate = Number(this.commissionPerPv) || 1.00;

            // ถ้าไม่มี PV ไม่ต้องคำนวณ
            if (pv <= 0) {
                return { total: 0 };
            }

            // คำนวณ MLM Commission = PV × commissionPerPv (หักทั้งก้อน)
            // เงินนี้จะถูกแบ่งให้ Unilevel + Binary ภายหลังตามระบบ
            const total = pv * commissionRate;

            return { total };
        },

        // ===== Initialize =====
        init() {
            // Debug: แสดงค่า MLM config (เปิดเฉพาะตอน debug)
            console.log('MLM Config:', { commissionPerPv: this.commissionPerPv });
        }
    }
}
</script>
@endpush
@endsection

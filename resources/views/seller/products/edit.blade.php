@extends('layouts.seller')

@section('title', 'แก้ไขสินค้า: ' . $product->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-900 dark:to-slate-800 py-8">
    <div class="max-w-5xl mx-auto px-4">
        <!-- Header Section -->
        <div class="mb-8">
            <a href="{{ route('seller.products.index') }}"
               class="inline-flex items-center gap-2 text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300 font-medium mb-4 transition-colors group">
                <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับไปรายการสินค้า
            </a>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">แก้ไขสินค้า</h1>
            <p class="text-gray-600 dark:text-gray-400">อัพเดตข้อมูลสินค้าของคุณให้ทันสมัยและถูกต้อง</p>
        </div>

        <form action="{{ route('seller.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Hidden field for deleted images -->
            <input type="hidden" name="deleted_images" id="deleted_images" value="">

            <!-- Section 1: Basic Information -->
            <x-modern-card
                title="ข้อมูลพื้นฐานสินค้า"
                description="ชื่อและรายละเอียดสินค้าที่ลูกค้าจะเห็นเป็นอันดับแรก"
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'>

                <div class="space-y-6">
                    <x-form-field
                        label="ชื่อสินค้า"
                        name="name"
                        type="text"
                        :value="old('name', $product->name)"
                        placeholder="เช่น iPhone 15 Pro Max 256GB"
                        :required="true"
                        helpText="ชื่อสินค้าควรชัดเจน กระชับ และมีคีย์เวิร์ดสำคัญ"
                        tooltip="ใช้ชื่อที่ลูกค้าสามารถค้นหาได้ง่าย รวมถึงรุ่น ขนาด หรือสีหากมี"
                        icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>'
                    />

                    <x-form-field
                        label="คำอธิบายสั้น"
                        name="short_description"
                        type="textarea"
                        :rows="3"
                        :value="old('short_description', $product->short_description)"
                        placeholder="สรุปจุดเด่นของสินค้าใน 1-2 ประโยค"
                        helpText="ข้อความนี้จะแสดงในรายการสินค้า ไม่ควรเกิน 500 ตัวอักษร"
                        tooltip="เน้นจุดเด่นหลักที่ทำให้สินค้านี้โดดเด่น"
                        icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>'
                    />

                    <x-form-field
                        label="คำอธิบายสินค้าแบบละเอียด"
                        name="description"
                        type="textarea"
                        :rows="8"
                        :value="old('description', $product->description)"
                        placeholder="อธิบายรายละเอียดสินค้า คุณสมบัติ วิธีใช้งาน และข้อมูลที่สำคัญ"
                        helpText="ใส่รายละเอียดครบถ้วนเพื่อให้ลูกค้าเข้าใจสินค้าได้ดียิ่งขึ้น"
                        tooltip="รายละเอียดที่ดีช่วยเพิ่มโอกาสในการขาย รวมถึงข้อมูลสเปค วิธีใช้ ข้อควรระวัง"
                        icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                    />
                </div>
            </x-modern-card>

            @php
                // Get existing PV data if exists
                $defaultPlan = \App\Models\MlmPlan::where('is_active', true)->first();
                $existingPv = $defaultPlan ? $product->getMlmPv($defaultPlan->id) : null;
                $pvValue = old('pv_value', $existingPv?->pv_value ?? 0);
            @endphp

            <!-- Section 2: Pricing with Real-time Calculator -->
            <x-modern-card
                title="ราคาและกำไร"
                description="กำหนดราคาขายและติดตามกำไรของคุณแบบเรียลไทม์"
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'>

                <div x-data="{
                    price: {{ old('price', $product->price) }},
                    comparePrice: {{ old('compare_at_price', $product->compare_at_price ?? 0) }},
                    costPrice: {{ old('cost_price', $product->cost_price ?? 0) }},
                    commissionRate: {{ old('commission_rate', $product->commission_rate ?? 10) }},
                    get commission() {
                        return this.price * (this.commissionRate / 100);
                    },
                    get profit() {
                        return this.price - this.costPrice - this.commission;
                    },
                    get profitMargin() {
                        return this.price > 0 ? ((this.profit / this.price) * 100).toFixed(2) : 0;
                    },
                    get discount() {
                        return this.comparePrice > this.price ? (((this.comparePrice - this.price) / this.comparePrice) * 100).toFixed(0) : 0;
                    }
                }" class="space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-form-field
                            label="ราคาขาย"
                            name="price"
                            type="number"
                            x-model.number="price"
                            :value="old('price', $product->price)"
                            placeholder="0.00"
                            :required="true"
                            :step="0.01"
                            :min="0"
                            helpText="ราคาที่คุณจะขายให้ลูกค้า"
                            tooltip="นี่คือราคาจริงที่ลูกค้าจะจ่าย"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
                        />

                        <x-form-field
                            label="ราคาเปรียบเทียบ"
                            name="compare_at_price"
                            type="number"
                            x-model.number="comparePrice"
                            :value="old('compare_at_price', $product->compare_at_price)"
                            placeholder="0.00"
                            :step="0.01"
                            :min="0"
                            helpText="ราคาปกติก่อนลด (ถ้ามี)"
                            tooltip="จะแสดงเป็นราคาขีดฆ่า ทำให้ดูเหมือนได้ส่วนลด"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>'
                        />

                        <x-form-field
                            label="ราคาทุน"
                            name="cost_price"
                            type="number"
                            x-model.number="costPrice"
                            :value="old('cost_price', $product->cost_price)"
                            placeholder="0.00"
                            :step="0.01"
                            :min="0"
                            helpText="ต้นทุนจริงของสินค้า"
                            tooltip="ใช้คำนวณกำไรสุทธิ (ไม่แสดงให้ลูกค้าเห็น)"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>'
                        />
                    </div>

                    <!-- Real-time Profit Calculator -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-700 dark:to-slate-600 rounded-xl p-6 border border-blue-200 dark:border-slate-500">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-blue-600 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การคำนวณกำไรแบบเรียลไทม์</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ส่วนลด</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="discount + '%'"></p>
                            </div>
                            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ค่าคอมมิชชั่น</p>
                                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400" x-text="'฿' + commission.toFixed(2)"></p>
                            </div>
                            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">กำไรสุทธิ</p>
                                <p class="text-2xl font-bold" :class="profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="'฿' + profit.toFixed(2)"></p>
                            </div>
                            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">อัตรากำไร</p>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="profitMargin + '%'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-modern-card>

            <!-- Section 3: MLM & Commissions -->
            <x-modern-card
                title="MLM & รางวัล"
                description="กำหนด Point Value และ Cashback สำหรับระบบ MLM และสิทธิประโยชน์ลูกค้า"
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form-field
                            label="PV (Point Value)"
                            name="pv_value"
                            type="number"
                            :value="old('pv_value', $pvValue)"
                            placeholder="0"
                            :step="0.01"
                            :min="0"
                            helpText="แต้มที่ใช้คำนวณค่าคอมมิชชั่น MLM"
                            tooltip="PV คือแต้มที่จะนำไปคำนวณรายได้ในเครือข่าย MLM ยิ่ง PV สูง ค่าคอมฯ ก็สูงตาม"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>'
                        />

                        <x-form-field
                            label="อัตราค่าคอมมิชชั่น (%)"
                            name="commission_rate"
                            type="number"
                            :value="old('commission_rate', $product->commission_rate ?? 10)"
                            placeholder="10.00"
                            :step="0.01"
                            :min="0"
                            :max="100"
                            helpText="เปอร์เซ็นต์ค่าคอมฯ ที่แพลตฟอร์มหัก"
                            tooltip="แพลตฟอร์มจะหักค่าคอมมิชชั่นจากราคาขาย ค่าเริ่มต้น 10%"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>'
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form-field
                            label="Cashback แบบจำนวนคงที่ (฿)"
                            name="customer_cashback"
                            type="number"
                            :value="old('customer_cashback', $product->customer_cashback ?? 0)"
                            placeholder="0.00"
                            :step="0.01"
                            :min="0"
                            helpText="เงินคืนให้ลูกค้าเป็นจำนวนคงที่"
                            tooltip="ลูกค้าจะได้รับเงินคืนเป็นจำนวนเงินที่กำหนด เช่น 50 บาท"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
                        />

                        <x-form-field
                            label="Cashback แบบเปอร์เซ็นต์ (%)"
                            name="cashback_percentage"
                            type="number"
                            :value="old('cashback_percentage', $product->cashback_percentage ?? 0)"
                            placeholder="0.00"
                            :step="0.01"
                            :min="0"
                            :max="100"
                            helpText="เงินคืนให้ลูกค้าเป็นเปอร์เซ็นต์"
                            tooltip="ลูกค้าจะได้รับเงินคืนเป็น % ของราคา เช่น 5% ของ 1000 บาท = 50 บาท"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>'
                        />
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h4 class="font-semibold text-amber-900 dark:text-amber-200 mb-1">หมายเหตุเกี่ยวกับ Cashback</h4>
                                <p class="text-sm text-amber-800 dark:text-amber-300">หากกำหนดทั้ง Cashback คงที่และเปอร์เซ็นต์ ระบบจะรวมทั้งสองค่าให้ลูกค้า (เช่น 50฿ + 5% = รวมเป็นเงินคืนทั้งหมด)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-modern-card>

            <!-- Section 4: Inventory Management -->
            <x-modern-card
                title="การจัดการสต็อก"
                description="ติดตามและจัดการสินค้าคงคลังของคุณ"
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>'>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-form-field
                            label="SKU (รหัสสินค้า)"
                            name="sku"
                            type="text"
                            :value="old('sku', $product->sku)"
                            placeholder="PRD-XXXXX"
                            helpText="รหัสประจำตัวสินค้า (สร้างอัตโนมัติหากไม่กำหนด)"
                            tooltip="SKU ช่วยในการติดตามสินค้า ควรไม่ซ้ำกับสินค้าอื่น"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>'
                        />

                        <x-form-field
                            label="จำนวนสต็อก"
                            name="stock_quantity"
                            type="number"
                            :value="old('stock_quantity', $product->stock_quantity)"
                            placeholder="0"
                            :required="true"
                            :min="0"
                            helpText="จำนวนสินค้าคงเหลือ"
                            tooltip="อัพเดตจำนวนสินค้าให้ตรงกับที่มีจริง จะมีการหักอัตโนมัติเมื่อมีคนซื้อ"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>'
                        />
                    </div>

                    <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <input type="checkbox"
                               name="track_inventory"
                               id="track_inventory"
                               value="1"
                               {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                        <label for="track_inventory" class="flex-1">
                            <span class="font-semibold text-gray-900 dark:text-white">ติดตามสต็อกอัตโนมัติ</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">เปิดใช้งานการหักสต็อกอัตโนมัติเมื่อมีการขาย และแจ้งเตือนเมื่อสินค้าใกล้หมด</p>
                        </label>
                    </div>

                    <!-- Product Stats -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            สถิติสินค้า
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ยอดขายทั้งหมด</p>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($product->sales_count) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">คะแนนเฉลี่ย</p>
                                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($product->rating_average, 1) }} ⭐</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ</p>
                                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ $product->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-modern-card>

            <!-- Section 5: Product Images -->
            <x-modern-card
                title="รูปภาพสินค้า"
                description="อัปโหลดรูปภาพสินค้าที่สวยงามและชัดเจน รองรับ drag & drop"
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'>

                <div class="space-y-6">
                    <div>
                        <label class="block text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            รูปหลัก (Main Image)
                        </label>
                        <x-image-upload
                            name="main_image"
                            :multiple="false"
                            :maxFiles="1"
                            :maxSize="5"
                            :existingImages="$product->main_image_url ? [[
                                'url' => Storage::url($product->main_image_url),
                                'name' => 'รูปหลัก'
                            ]] : []"
                        />
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3 flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>รูปนี้จะแสดงในหน้ารายการสินค้า แนะนำขนาด 1:1 (จัตุรัส) ความละเอียดอย่างน้อย 800x800px</span>
                        </p>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <label class="block text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            รูปเพิ่มเติม (Gallery)
                        </label>
                        <x-image-upload
                            name="images"
                            :multiple="true"
                            :maxFiles="10"
                            :maxSize="5"
                            :existingImages="$product->images->map(function($img) {
                                return [
                                    'id' => $img->id,
                                    'url' => Storage::url($img->image_url),
                                    'name' => 'Gallery Image'
                                ];
                            })->toArray()"
                        />
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3 flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>อัปโหลดได้สูงสุด 10 รูป แต่ละรูปไม่เกิน 5MB รูปเหล่านี้จะแสดงในหน้ารายละเอียดสินค้า</span>
                        </p>
                    </div>
                </div>
            </x-modern-card>

            <!-- Section 6: Category & Additional Details -->
            <x-modern-card
                title="หมวดหมู่และรายละเอียดเพิ่มเติม"
                description="จัดหมวดหมู่และเพิ่มข้อมูลเสริมเพื่อช่วยให้ลูกค้าค้นหาสินค้าได้ง่าย"
                icon='<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>'>

                <div class="space-y-6">
                    <x-form-field
                        label="หมวดหมู่สินค้า"
                        name="category_id"
                        type="select"
                        :value="old('category_id', $product->category_id)"
                        :required="true"
                        helpText="เลือกหมวดหมู่ที่เหมาะสมกับสินค้า"
                        tooltip="หมวดหมู่ช่วยให้ลูกค้าค้นหาและกรองสินค้าได้ง่ายขึ้น"
                        icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>'
                        :options="array_merge(
                            ['' => '-- เลือกหมวดหมู่ --'],
                            $categories->pluck('name', 'id')->toArray()
                        )"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-form-field
                            label="แบรนด์"
                            name="brand"
                            type="text"
                            :value="old('brand', $product->brand)"
                            placeholder="เช่น Apple, Samsung"
                            helpText="ชื่อแบรนด์หรือผู้ผลิต"
                            tooltip="ช่วยให้ลูกค้าค้นหาและกรองตามแบรนด์ที่ต้องการ"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>'
                        />

                        <x-form-field
                            label="น้ำหนัก (กรัม)"
                            name="weight"
                            type="number"
                            :value="old('weight', $product->weight)"
                            placeholder="0"
                            :step="0.01"
                            :min="0"
                            helpText="น้ำหนักสำหรับคำนวณค่าจัดส่ง"
                            tooltip="กรอกน้ำหนักเป็นกรัม ใช้สำหรับคำนวณค่าจัดส่ง"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>'
                        />

                        <x-form-field
                            label="ขนาด"
                            name="dimensions"
                            type="text"
                            :value="old('dimensions', $product->dimensions)"
                            placeholder="กว้าง x ยาว x สูง (cm)"
                            helpText="ขนาดของสินค้า"
                            tooltip="กรอกขนาดในรูปแบบ กว้าง x ยาว x สูง เช่น 10x20x5 cm"
                            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>'
                        />
                    </div>
                </div>
            </x-modern-card>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-end sticky bottom-6 bg-white/80 dark:bg-slate-800/80 backdrop-blur-lg rounded-2xl shadow-2xl p-6 border border-gray-200 dark:border-slate-700">
                <a href="{{ route('seller.products.index') }}"
                   class="px-8 py-4 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-white rounded-xl font-semibold transition-all transform hover:scale-105 text-center">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-8 py-4 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white rounded-xl font-semibold shadow-lg transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle image deletion tracking for x-image-upload component
document.addEventListener('DOMContentLoaded', function() {
    const deletedImagesInput = document.getElementById('deleted_images');
    const deletedImageIds = [];

    // Listen for image deletion events from x-image-upload component
    document.addEventListener('image-deleted', function(e) {
        if (e.detail.id) {
            deletedImageIds.push(e.detail.id);
            deletedImagesInput.value = deletedImageIds.join(',');
        }
    });
});
</script>
@endsection

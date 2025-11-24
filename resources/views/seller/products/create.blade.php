@extends('layouts.seller')

@section('title', 'เพิ่มสินค้าใหม่')

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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="productForm()">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
                <x-form-field
                    label="ค่าแพลตฟอร์ม (%)"
                    name="platform_fee_percentage"
                    type="number"
                    :value="old('platform_fee_percentage', 10)"
                    placeholder="10.00"
                    step="0.01"
                    min="0"
                    max="100"
                    helpText="ค่าธรรมเนียมแพลตฟอร์ม (default: 10%)"
                    tooltip="ค่าธรรมเนียมที่แพลตฟอร์มจะหักจากราคาขาย"
                    x-model.number="platformFee"
                />

                <x-form-field
                    label="VAT (%)"
                    name="vat_percentage"
                    type="number"
                    :value="old('vat_percentage', 7)"
                    placeholder="7.00"
                    step="0.01"
                    min="0"
                    max="100"
                    helpText="ภาษีมูลค่าเพิ่ม (default: 7%)"
                    tooltip="VAT จะถูกหักจากรายได้สุทธิของผู้ขาย"
                    x-model.number="vat"
                />
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
                <x-form-field
                    label="SKU"
                    name="sku"
                    type="text"
                    :value="old('sku')"
                    placeholder="จะสร้างอัตโนมัติถ้าไม่กรอก"
                    helpText="รหัสสินค้าเฉพาะ (ถ้าว่างจะสร้างให้อัตโนมัติ)"
                    tooltip="SKU ใช้สำหรับจัดการสต็อกและระบุตัวสินค้า ควรไม่ซ้ำกัน"
                />

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

        {{-- Right Column: Earnings Calculator (1/3 width on desktop, sticky) --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-6">
                <x-earnings-calculator
                    itemType="product"
                    :price="old('price', 1000)"
                    :costPrice="old('cost_price', 0)"
                    :platformFee="old('platform_fee_percentage', 10)"
                    :cashback="old('cashback_percentage', 0)"
                    :vat="old('vat_percentage', 7)"
                    :pvValue="old('pv_value', 0)"
                    x-bind:price="price"
                    x-bind:costPrice="costPrice"
                    x-bind:platformFee="platformFee"
                    x-bind:cashback="cashback"
                    x-bind:vat="vat"
                    x-bind:pvValue="pvValue"
                />
            </div>
        </div>
        {{-- End Right Column --}}
    </div>
    {{-- End Grid Layout --}}
</div>

@push('scripts')
<script>
function productForm() {
    return {
        // Pricing data (reactive for calculator)
        price: {{ old('price', 1000) }},
        costPrice: {{ old('cost_price', 0) }},
        platformFee: {{ old('platform_fee_percentage', 10) }},
        cashback: {{ old('cashback_percentage', 0) }},
        vat: {{ old('vat_percentage', 7) }},
        pvValue: {{ old('pv_value', 0) }},
    }
}
</script>
@endpush
@endsection

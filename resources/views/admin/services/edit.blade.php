@extends('layouts.admin')

@section('title', 'แก้ไขบริการ')

@section('content')
<div class="max-w-7xl mx-auto" x-data="serviceForm()">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('admin.services.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors mb-4">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปหน้ารายการ</span>
        </a>

        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                <i class="fas fa-edit mr-3"></i>แก้ไขบริการ
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                แก้ไขข้อมูลบริการ "{{ $service->name }}"
            </p>
        </div>
    </div>

    {{-- Grid Layout: Form + Calculator --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Form (2/3 width on desktop) --}}
        <div class="lg:col-span-2">
    {{-- Form --}}
    <form action="{{ route('admin.services.update', $service) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            {{-- Basic Information --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-info-circle mr-2 text-purple-600 dark:text-purple-400"></i>
                        ข้อมูลพื้นฐาน
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            หมวดหมู่บริการ <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $service->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->icon }} {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Service Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อบริการ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $service->name) }}" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" rows="4" required
                                  class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">{{ old('description', $service->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Current Image --}}
                    @if($service->image_path)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปปัจจุบัน</label>
                            <img src="{{ asset('storage/' . $service->image_path) }}" class="w-32 h-32 object-cover rounded-xl border-2 border-purple-500">
                        </div>
                    @endif

                    {{-- Image Upload --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            อัพโหลดรูปใหม่ (ถ้าต้องการเปลี่ยน)
                        </label>
                        <input type="file" name="image" accept="image/*" @change="previewImage($event)"
                               class="block w-full text-sm text-gray-900 dark:text-white
                                      file:mr-4 file:py-3 file:px-6
                                      file:rounded-xl file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-purple-50 dark:file:bg-purple-900/30
                                      file:text-purple-700 dark:file:text-purple-400
                                      hover:file:bg-purple-100
                                      file:cursor-pointer cursor-pointer
                                      border border-gray-300 dark:border-gray-600 rounded-xl
                                      bg-white dark:bg-gray-700">
                        <div x-show="imagePreview" class="mt-4">
                            <img :src="imagePreview" class="w-32 h-32 object-cover rounded-xl border-2 border-purple-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-dollar-sign mr-2 text-purple-600 dark:text-purple-400"></i>
                        ราคาและค่าธรรมเนียม
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Base Price --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ราคาพื้นฐาน (บาท) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="base_price" x-model.number="price" value="{{ old('base_price', $service->base_price) }}" required min="0" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            @error('base_price')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Duration --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ระยะเวลา (นาที)
                            </label>
                            <input type="number" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', $service->estimated_duration_minutes) }}" min="15" step="15"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                        </div>

                        {{-- Platform Fee --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-percentage text-blue-500"></i> ค่าแพลตฟอร์ม (%)
                            </label>
                            <input type="number" name="platform_fee_percentage" x-model.number="platformFee" value="{{ old('platform_fee_percentage', $service->platform_fee_percentage ?? 10) }}" min="0" max="100" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ค่าธรรมเนียมแพลตฟอร์ม (default: 10%)</p>
                        </div>

                        {{-- Provider PV --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-chart-line text-purple-500"></i> Provider PV (%)
                            </label>
                            <input type="number" name="provider_pv_percentage" x-model.number="providerPv" value="{{ old('provider_pv_percentage', $service->provider_pv_percentage ?? 5) }}" min="0" max="100" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ค่าการตลาดของ Provider (ส่วนเสริมสำหรับบริการ)</p>
                        </div>

                        {{-- Cashback --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-gift text-green-500"></i> Cashback (%)
                            </label>
                            <input type="number" name="cashback_percentage" x-model.number="cashback" value="{{ old('cashback_percentage', $service->cashback_percentage ?? 0) }}" min="0" max="100" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คืนเงินลูกค้า (Provider จ่าย)</p>
                        </div>

                        {{-- VAT --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-receipt text-orange-500"></i> VAT (%)
                            </label>
                            <input type="number" name="vat_percentage" x-model.number="vat" value="{{ old('vat_percentage', $service->vat_percentage ?? 7) }}" min="0" max="100" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ภาษีมูลค่าเพิ่ม (default: 7%)</p>
                        </div>

                        {{-- PV Value --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-star text-yellow-500"></i> PV Value (คะแนน)
                            </label>
                            <input type="number" name="pv_value" x-model.number="pvValue" value="{{ old('pv_value', $service->pv_value ?? 0) }}" min="0" step="0.01"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PV สำหรับคำนวณค่าคอมมิชชั่น MLM (0 = ไม่มีคอม, หรือใช้ราคาเป็น PV)</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-cog mr-2 text-purple-600 dark:text-purple-400"></i>
                        การตั้งค่า
                    </h2>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Require Location --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                ต้องระบุตำแหน่ง GPS
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                บังคับให้ลูกค้าระบุตำแหน่งเพื่อคำนวณค่าระยะทาง
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="requires_location" value="1" {{ old('requires_location', $service->requires_location) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        </label>
                    </div>

                    {{-- Is Active --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                เปิดใช้งานบริการ
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                บริการที่ปิดใช้งานจะไม่แสดงในหน้าเว็บไซต์
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    {{-- Stats (Read-only) --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400 text-xl mt-1"></i>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">สถิติการใช้งาน</h4>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-blue-600 dark:text-blue-400">การจอง:</span>
                                        <span class="font-semibold text-blue-900 dark:text-blue-200 ml-1">{{ $service->bookings_count ?? 0 }}</span>
                                    </div>
                                    <div>
                                        <span class="text-blue-600 dark:text-blue-400">คะแนน:</span>
                                        <span class="font-semibold text-blue-900 dark:text-blue-200 ml-1">{{ number_format($service->average_rating ?? 0, 1) }} / 5</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="fas fa-save"></i>
                    <span>บันทึกการเปลี่ยนแปลง</span>
                </button>

                <a href="{{ route('admin.services.index') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl font-semibold shadow hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-times"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </div>
    </form>
        </div>
        {{-- End Left Column --}}

        {{-- Right Column: Earnings Calculator (1/3 width on desktop, sticky) --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-6">
                <x-earnings-calculator
                    itemType="service"
                    :price="old('base_price', $service->base_price)"
                    :platformFee="old('platform_fee_percentage', $service->platform_fee_percentage ?? 10)"
                    :providerPv="old('provider_pv_percentage', $service->provider_pv_percentage ?? 5)"
                    :cashback="old('cashback_percentage', $service->cashback_percentage ?? 0)"
                    :vat="old('vat_percentage', $service->vat_percentage ?? 7)"
                    :pvValue="old('pv_value', $service->pv_value ?? 0)"
                    x-bind:price="price"
                    x-bind:platformFee="platformFee"
                    x-bind:providerPv="providerPv"
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
@endsection

@push('scripts')
<script>
function serviceForm() {
    return {
        // Image preview
        imagePreview: null,

        // Pricing data (reactive for calculator)
        price: {{ old('base_price', $service->base_price) }},
        platformFee: {{ old('platform_fee_percentage', $service->platform_fee_percentage ?? 10) }},
        providerPv: {{ old('provider_pv_percentage', $service->provider_pv_percentage ?? 5) }},
        cashback: {{ old('cashback_percentage', $service->cashback_percentage ?? 0) }},
        vat: {{ old('vat_percentage', $service->vat_percentage ?? 7) }},
        pvValue: {{ old('pv_value', $service->pv_value ?? 0) }},

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
    }
}
</script>
@endpush

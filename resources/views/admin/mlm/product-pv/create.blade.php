@extends('layouts.admin')

@section('title', 'เพิ่มการกำหนด PV สินค้า')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-box text-purple-600 dark:text-purple-400"></i>
                    เพิ่มการกำหนด PV สินค้า
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    กำหนดค่า Point Value และค่าคอมมิชชั่นสำหรับสินค้า
                </p>
            </div>
            <a href="{{ route('admin.mlm.product-pv.index') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all duration-200">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 text-xl mt-0.5 mr-3"></i>
            <div>
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">เกี่ยวกับ PV (Point Value)</h3>
                <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                    PV คือค่าคะแนนที่กำหนดให้กับสินค้า ใช้ในการคำนวณคอมมิชชั่น MLM
                    สินค้าหนึ่งสามารถมี PV แตกต่างกันในแต่ละแผน MLM ได้
                    หากเปิดใช้ Global Rate จะใช้อัตราค่าคอมมิชชั่นต่อ PV จากการตั้งค่าแผน MLM
                </p>
            </div>
        </div>
    </div>

    @if($products->isEmpty())
    <!-- No Products Alert -->
    <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
        <i class="fas fa-exclamation-triangle text-6xl text-yellow-400 dark:text-yellow-500 mb-4"></i>
        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">ไม่มีสินค้าที่พร้อมกำหนด PV</h3>
        <p class="text-yellow-700 dark:text-yellow-400 mb-4">สินค้าทั้งหมดได้รับการกำหนด PV เรียบร้อยแล้ว หรือยังไม่มีสินค้าในระบบ</p>
        <a href="{{ route('admin.mlm.product-pv.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg transition-all duration-200 gap-2">
            กลับไปหน้ารายการ
        </a>
    </div>
    @elseif($plans->isEmpty())
    <!-- No Plans Alert -->
    <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
        <i class="fas fa-exclamation-triangle text-6xl text-yellow-400 dark:text-yellow-500 mb-4"></i>
        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">ไม่มีแผน MLM ที่ใช้งานอยู่</h3>
        <p class="text-yellow-700 dark:text-yellow-400 mb-4">กรุณาสร้างและเปิดใช้งานแผน MLM ก่อนกำหนด PV สินค้า</p>
        <a href="{{ route('admin.mlm.plans.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg transition-all duration-200 gap-2">
            ไปที่จัดการแผน MLM
        </a>
    </div>
    @else
    <!-- Form -->
    <form action="{{ route('admin.mlm.product-pv.store') }}" method="POST">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 space-y-6">
            <!-- Product & Plan Selection -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-bullseye text-purple-600 dark:text-purple-400"></i>
                    เลือกสินค้าและแผน MLM
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สินค้า <span class="text-red-500">*</span>
                        </label>
                        <select name="product_id" id="product-select"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                required>
                            <option value="">เลือกสินค้า...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                        data-price="{{ $product->price }}"
                                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} (SKU: {{ $product->sku }}) - ฿{{ number_format($product->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลือกสินค้าที่ต้องการกำหนด PV</p>
                        @error('product_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            แผน MLM <span class="text-red-500">*</span>
                        </label>
                        <select name="mlm_plan_id" id="plan-select"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                required>
                            <option value="">เลือกแผน MLM...</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}"
                                        data-commission-rate="{{ $plan->commission_per_pv }}"
                                        {{ old('mlm_plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->display_name }} (฿{{ number_format($plan->commission_per_pv, 2) }}/PV)
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลือกแผน MLM ที่ต้องการกำหนด</p>
                        @error('mlm_plan_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- PV Configuration -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-500 dark:text-yellow-400"></i>
                    กำหนดค่า PV
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            PV Value <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="pv_value" id="pv-value-input"
                               value="{{ old('pv_value', 0) }}"
                               min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                               required>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">กำหนดค่า Point Value สำหรับสินค้านี้</p>
                        <div id="pv-helper" class="text-xs text-blue-600 dark:text-blue-400 mt-1 hidden"></div>
                        @error('pv_value')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="product-price-display" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ข้อมูลอ้างอิง
                        </label>
                        <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-lg border border-purple-100 dark:border-purple-800">
                            <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                <span class="font-medium">ราคาสินค้า:</span>
                                <span id="product-price-text" class="text-purple-600 dark:text-purple-400 font-semibold">-</span>
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">PV/ราคา:</span>
                                <span id="pv-ratio-text" class="text-pink-600 dark:text-pink-400 font-semibold">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick PV Calculation Buttons -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำนวณ PV อย่างรวดเร็ว
                    </label>
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" onclick="setPvByPercentage(100)"
                                class="px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm transition-colors">
                            100% ของราคา
                        </button>
                        <button type="button" onclick="setPvByPercentage(80)"
                                class="px-3 py-2 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-800/30 text-green-700 dark:text-green-300 rounded-lg text-sm transition-colors">
                            80% ของราคา
                        </button>
                        <button type="button" onclick="setPvByPercentage(50)"
                                class="px-3 py-2 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-800/30 text-yellow-700 dark:text-yellow-300 rounded-lg text-sm transition-colors">
                            50% ของราคา
                        </button>
                        <button type="button" onclick="setPvByPercentage(30)"
                                class="px-3 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-800/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm transition-colors">
                            30% ของราคา
                        </button>
                    </div>
                </div>
            </div>

            <!-- Commission Configuration -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-dollar-sign text-green-600 dark:text-green-400"></i>
                    กำหนดอัตราค่าคอมมิชชั่น
                </h3>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="use_global_rate" id="use-global-rate"
                               value="1"
                               {{ old('use_global_rate', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            ใช้ Global Rate จากแผน MLM
                        </span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 ml-6 mt-1">
                        ถ้าเลือก จะใช้อัตราค่าคอมมิชชั่นต่อ PV จากการตั้งค่าแผน MLM
                    </p>
                </div>

                <div id="custom-rate-section" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Custom Commission Per PV (฿)
                    </label>
                    <input type="number" name="custom_commission_per_pv" id="custom-commission-input"
                           value="{{ old('custom_commission_per_pv', 0) }}"
                           min="0" step="0.01"
                           class="w-full md:w-1/2 px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">กำหนดอัตราค่าคอมมิชชั่นต่อ PV เฉพาะสำหรับสินค้านี้</p>
                    @error('custom_commission_per_pv')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Commission Preview -->
                <div id="commission-preview" class="mt-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-lg border border-green-100 dark:border-green-800 hidden">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ตัวอย่างค่าคอมมิชชั่น</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">PV ทั้งหมด:</span>
                            <span id="preview-pv" class="ml-2 font-semibold text-blue-600 dark:text-blue-400">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">ค่าคอมมิชชั่น:</span>
                            <span id="preview-commission" class="ml-2 font-semibold text-green-600 dark:text-green-400">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-eye text-blue-600 dark:text-blue-400"></i>
                    การแสดงผล
                </h3>

                <div class="space-y-3">
                    <label class="flex items-start">
                        <input type="checkbox" name="show_pv_on_product_page"
                               value="1"
                               {{ old('show_pv_on_product_page', true) ? 'checked' : '' }}
                               class="w-4 h-4 mt-1 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500">
                        <div class="ml-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">แสดง PV ในหน้าสินค้า</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">แสดงค่า PV ให้ลูกค้าเห็นในหน้ารายละเอียดสินค้า</p>
                        </div>
                    </label>

                    <label class="flex items-start">
                        <input type="checkbox" name="show_commission_preview"
                               value="1"
                               {{ old('show_commission_preview', true) ? 'checked' : '' }}
                               class="w-4 h-4 mt-1 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500">
                        <div class="ml-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">แสดงตัวอย่างค่าคอมมิชชั่น</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">แสดงตัวอย่างค่าคอมมิชชั่นที่จะได้รับในหน้าสินค้า</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Description -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-file-alt text-gray-600 dark:text-gray-400"></i>
                    คำอธิบาย (ไม่บังคับ)
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            PV Description (EN)
                        </label>
                        <textarea name="pv_description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                  placeholder="Description about PV for this product...">{{ old('pv_description') }}</textarea>
                        @error('pv_description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            PV Description (TH)
                        </label>
                        <textarea name="pv_description_th" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500"
                                  placeholder="คำอธิบายเกี่ยวกับ PV สำหรับสินค้านี้...">{{ old('pv_description_th') }}</textarea>
                        @error('pv_description_th')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.mlm.product-pv.index') }}"
                       class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all duration-200">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        บันทึกการกำหนด PV
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>

<!-- JavaScript for Dynamic Calculations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product-select');
    const planSelect = document.getElementById('plan-select');
    const pvValueInput = document.getElementById('pv-value-input');
    const useGlobalRate = document.getElementById('use-global-rate');
    const customRateSection = document.getElementById('custom-rate-section');
    const customCommissionInput = document.getElementById('custom-commission-input');
    const productPriceDisplay = document.getElementById('product-price-display');
    const commissionPreview = document.getElementById('commission-preview');

    // Toggle custom rate section
    if (useGlobalRate) {
        useGlobalRate.addEventListener('change', function() {
            if (this.checked) {
                customRateSection.classList.add('hidden');
            } else {
                customRateSection.classList.remove('hidden');
            }
            updateCommissionPreview();
        });

        // Initial state
        if (!useGlobalRate.checked) {
            customRateSection.classList.remove('hidden');
        }
    }

    // Update display when product changes
    productSelect.addEventListener('change', updateProductInfo);
    pvValueInput.addEventListener('input', updateProductInfo);
    planSelect.addEventListener('change', updateCommissionPreview);
    customCommissionInput.addEventListener('input', updateCommissionPreview);

    function updateProductInfo() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = parseFloat(selectedOption.dataset.price) || 0;
        const pvValue = parseFloat(pvValueInput.value) || 0;

        if (price > 0) {
            productPriceDisplay.classList.remove('hidden');
            document.getElementById('product-price-text').textContent = '฿' + price.toFixed(2);

            const ratio = price > 0 ? (pvValue / price * 100) : 0;
            document.getElementById('pv-ratio-text').textContent = ratio.toFixed(1) + '%';

            // Show helper text
            const pvHelper = document.getElementById('pv-helper');
            if (pvValue > 0) {
                pvHelper.classList.remove('hidden');
                pvHelper.textContent = `${pvValue.toFixed(2)} PV = ${ratio.toFixed(1)}% ของราคาสินค้า`;
            } else {
                pvHelper.classList.add('hidden');
            }
        } else {
            productPriceDisplay.classList.add('hidden');
        }

        updateCommissionPreview();
    }

    function updateCommissionPreview() {
        const pvValue = parseFloat(pvValueInput.value) || 0;
        const selectedPlan = planSelect.options[planSelect.selectedIndex];
        const globalRate = parseFloat(selectedPlan.dataset.commissionRate) || 0;
        const customRate = parseFloat(customCommissionInput.value) || 0;
        const rate = useGlobalRate.checked ? globalRate : customRate;

        if (pvValue > 0 && rate > 0) {
            const commission = pvValue * rate;
            commissionPreview.classList.remove('hidden');
            document.getElementById('preview-pv').textContent = pvValue.toFixed(2) + ' PV';
            document.getElementById('preview-commission').textContent = '฿' + commission.toFixed(2);
        } else {
            commissionPreview.classList.add('hidden');
        }
    }

    // Initialize display
    updateProductInfo();
});

// Quick PV calculation function
function setPvByPercentage(percentage) {
    const productSelect = document.getElementById('product-select');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price) || 0;

    if (price > 0) {
        const pvValue = price * (percentage / 100);
        document.getElementById('pv-value-input').value = pvValue.toFixed(2);
        document.getElementById('pv-value-input').dispatchEvent(new Event('input'));
    } else {
        alert('กรุณาเลือกสินค้าก่อน');
    }
}
</script>
@endsection

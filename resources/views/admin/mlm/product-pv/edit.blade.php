@extends('layouts.admin-v3')

@section('title', 'แก้ไขค่า PV สินค้า')

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.mlm.product-pv.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปยังรายการ PV</span>
        </a>
    </div>

    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 dark:from-purple-800 dark:via-pink-800 dark:to-orange-700 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 glass-fusion opacity-5 rounded-full -mr-48 -mt-48" border border-white/20 dark:border-white/10></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 glass-fusion opacity-5 rounded-full -ml-32 -mb-32" border border-white/20 dark:border-white/10></div>

        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="glass-fusion backdrop-blur-sm p-4 rounded-2xl" border border-white/20 dark:border-white/10>
                    <i class="fas fa-gem text-4xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">แก้ไขค่า Point Value</h1>
                    <p class="text-purple-100 mt-1">กำหนดค่า PV และคอมมิชชั่นสำหรับสินค้า</p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.mlm.product-pv.update', $productPv) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Product Information (Read-only) -->
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 dark:border-gray-700" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-xl mr-3">
                            <i class="fas fa-box text-white"></i>
                        </div>
                        ข้อมูลสินค้า
                    </h3>

                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-6">
                            @if($productPv->product->images->count() > 0)
                                <img src="{{ $productPv->product->images->first()->image_path }}"
                                     alt="{{ $productPv->product->name }}"
                                     class="w-32 h-32 rounded-xl object-cover shadow-lg ring-4 ring-white dark:ring-gray-700">
                            @else
                                <div class="w-32 h-32 rounded-xl bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-image text-4xl text-gray-400 dark:text-gray-500 dark:text-gray-400"></i>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $productPv->product->name }}</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">SKU</p>
                                        <p class="font-mono text-gray-900 dark:text-white">{{ $productPv->product->sku }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ราคา</p>
                                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($productPv->product->price, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">แผน MLM</p>
                                        <span class="inline-flex items-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-xl font-medium">
                                            <i class="fas fa-award mr-2"></i>
                                            {{ $productPv->plan->display_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PV Configuration -->
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 dark:border-gray-700" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-yellow-500 to-orange-600 p-2 rounded-xl mr-3">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        กำหนดค่า Point Value
                    </h3>

                    <div class="space-y-6">
                        <!-- PV Value -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <i class="fas fa-gem text-purple-600 dark:text-purple-400 mr-2"></i>
                                ค่า PV (Point Value) *
                            </label>
                            <div class="relative">
                                <input type="number" name="pv_value" id="pvValue" step="0.01" min="0"
                                       value="{{ old('pv_value', $productPv->pv_value) }}"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pl-12 pr-4 py-3 text-lg font-bold focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400"
                                       required oninput="calculatePreview()">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-gem text-purple-600 dark:text-purple-400"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                จำนวน Point Value ที่สินค้านี้จะได้รับ
                            </p>

                            <!-- PV Percentage Calculator -->
                            <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">PV เป็นเปอร์เซ็นต์ของราคา</p>
                                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="pvPercentage">0%</p>
                                    </div>
                                    <button type="button" onclick="setPvFromPrice()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-medium transition-colors">
                                        <i class="fas fa-calculator mr-2"></i>
                                        ใช้ราคาเป็น PV
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Commission Settings -->
                        <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="use_global_rate" id="useGlobalRate"
                                       value="1" {{ old('use_global_rate', $productPv->use_global_rate) ? 'checked' : '' }}
                                       class="w-6 h-6 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 text-green-600 focus:ring-green-500 dark:focus:ring-green-400"
                                       onchange="toggleCustomRate()">
                                <div>
                                    <span class="text-base font-medium text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                        <i class="fas fa-globe mr-2"></i>
                                        ใช้ Global Commission Rate
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        ใช้อัตราค่าคอมมิชชั่นจากการตั้งค่าแผน MLM (฿{{ number_format($productPv->plan->commission_per_pv, 2) }}/PV)
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Custom Commission Rate -->
                        <div id="customRateSection" class="{{ old('use_global_rate', $productPv->use_global_rate) ? 'hidden' : '' }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <i class="fas fa-coins text-green-600 dark:text-green-400 mr-2"></i>
                                Custom Commission Rate (บาท/PV)
                            </label>
                            <div class="relative">
                                <input type="number" name="custom_commission_per_pv" id="customCommissionPv" step="0.01" min="0"
                                       value="{{ old('custom_commission_per_pv', $productPv->custom_commission_per_pv) }}"
                                       class="w-full border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pl-12 pr-4 py-3 text-lg font-bold focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400"
                                       oninput="calculatePreview()">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                    ฿
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                จำนวนเงินบาทต่อ 1 PV ที่จะได้รับเป็นคอมมิชชั่น
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Display Settings -->
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 dark:border-gray-700" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 p-2 rounded-xl mr-3">
                            <i class="fas fa-eye text-white"></i>
                        </div>
                        การแสดงผล
                    </h3>

                    <div class="space-y-4">
                        <!-- Show PV on Product Page -->
                        <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <input type="checkbox" name="show_pv_on_product_page" id="showPv"
                                   value="1" {{ old('show_pv_on_product_page', $productPv->show_pv_on_product_page) ? 'checked' : '' }}
                                   class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-gray-600 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-400">
                            <div class="flex-1">
                                <label for="showPv" class="font-medium text-gray-900 dark:text-white cursor-pointer">
                                    <i class="fas fa-tags mr-2 text-blue-600 dark:text-blue-400"></i>
                                    แสดงค่า PV บนหน้าสินค้า
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">
                                    ลูกค้าจะเห็นจำนวน PV ที่จะได้รับเมื่อซื้อสินค้านี้
                                </p>
                            </div>
                        </div>

                        <!-- Show Commission Preview -->
                        <div class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <input type="checkbox" name="show_commission_preview" id="showCommission"
                                   value="1" {{ old('show_commission_preview', $productPv->show_commission_preview) ? 'checked' : '' }}
                                   class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-gray-600 dark:border-gray-600 text-green-600 focus:ring-green-500 dark:focus:ring-green-400">
                            <div class="flex-1">
                                <label for="showCommission" class="font-medium text-gray-900 dark:text-white cursor-pointer">
                                    <i class="fas fa-money-bill-wave mr-2 text-green-600 dark:text-green-400"></i>
                                    แสดงตัวอย่างค่าคอมมิชชั่น
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">
                                    แสดงประมาณการค่าคอมมิชชั่นที่จะได้รับให้สมาชิกเห็น
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 dark:border-gray-700" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-xl mr-3">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                        คำอธิบาย PV (ไม่บังคับ)
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                คำอธิบาย (English)
                            </label>
                            <textarea name="pv_description" rows="3"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl"
                                      placeholder="Optional description about PV for this product...">{{ old('pv_description', $productPv->pv_description) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                คำอธิบาย (ภาษาไทย)
                            </label>
                            <textarea name="pv_description_th" rows="3"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl"
                                      placeholder="คำอธิบายเกี่ยวกับ PV สำหรับสินค้านี้...">{{ old('pv_description_th', $productPv->pv_description_th) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                    <a href="{{ route('admin.mlm.product-pv.index') }}"
                       class="flex-1 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-6 py-3 rounded-xl font-semibold text-center transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </div>

            <!-- Sidebar - Preview -->
            <div class="space-y-6">
                <!-- Commission Preview -->
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 dark:from-yellow-600 dark:to-orange-700 rounded-2xl shadow-2xl p-6 text-white sticky top-6">
                    <h3 class="text-xl font-bold mb-6 flex items-center">
                        <i class="fas fa-calculator mr-3"></i>
                        ตัวอย่างคอมมิชชั่น
                    </h3>

                    <div class="space-y-4">
                        <!-- Total PV -->
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-4" border border-white/20 dark:border-white/10>
                            <p class="text-sm opacity-90 mb-1">PV ทั้งหมด</p>
                            <p class="text-3xl font-bold" id="previewPV">0</p>
                        </div>

                        <!-- Commission Rate -->
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-4" border border-white/20 dark:border-white/10>
                            <p class="text-sm opacity-90 mb-1">อัตราค่าคอมมิชชั่น</p>
                            <p class="text-2xl font-bold" id="previewRate">฿0</p>
                            <p class="text-xs opacity-75 mt-1">ต่อ 1 PV</p>
                        </div>

                        <!-- Total Commission -->
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-4 border-2 border-white/50" border border-white/20 dark:border-white/10>
                            <p class="text-sm opacity-90 mb-1">คอมมิชชั่นรวม (Level 1)</p>
                            <p class="text-4xl font-bold" id="previewCommission">฿0</p>
                        </div>

                        <!-- Multi-level Preview -->
                        <div class="border-t border-white/30 pt-4 space-y-2">
                            <p class="text-sm font-semibold mb-3">
                                <i class="fas fa-layer-group mr-2"></i>
                                ตัวอย่างหลายระดับ
                            </p>
                            <div class="flex justify-between text-sm">
                                <span class="opacity-90">Level 2 (50%)</span>
                                <span class="font-bold" id="previewLevel2">฿0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="opacity-90">Level 3 (30%)</span>
                                <span class="font-bold" id="previewLevel3">฿0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="opacity-90">Level 4 (20%)</span>
                                <span class="font-bold" id="previewLevel4">฿0</span>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-3 text-xs opacity-75" border border-white/20 dark:border-white/10>
                            <i class="fas fa-info-circle mr-2"></i>
                            ค่าเปอร์เซ็นต์ตามระดับจะขึ้นกับการตั้งค่าแผน MLM
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 dark:border-gray-700" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 mr-2"></i>
                        สถิติ
                    </h3>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ราคาสินค้า</span>
                            <span class="font-bold text-gray-900 dark:text-white">฿{{ number_format($productPv->product->price, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">PV ปัจจุบัน</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">{{ number_format($productPv->pv_value, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Global Rate</span>
                            <span class="font-bold text-green-600 dark:text-green-400">฿{{ number_format($productPv->plan->commission_per_pv, 2) }}/PV</span>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-4 border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-2 flex items-center">
                        <i class="fas fa-question-circle mr-2"></i>
                        ความช่วยเหลือ
                    </h4>
                    <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-2">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                            <span>PV คือคะแนนที่ใช้คำนวณคอมมิชชั่น</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                            <span>Global Rate ใช้ค่าจากการตั้งค่าแผน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                            <span>Custom Rate ใช้ค่าเฉพาะสินค้า</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const productPrice = {{ $productPv->product->price }};
const globalRate = {{ $productPv->plan->commission_per_pv }};

function calculatePreview() {
    const pvValue = parseFloat(document.getElementById('pvValue').value) || 0;
    const useGlobal = document.getElementById('useGlobalRate').checked;
    const customRate = parseFloat(document.getElementById('customCommissionPv').value) || 0;

    // Calculate PV percentage
    const pvPercentage = productPrice > 0 ? (pvValue / productPrice * 100).toFixed(2) : 0;
    document.getElementById('pvPercentage').textContent = pvPercentage + '%';

    // Calculate commission
    const rate = useGlobal ? globalRate : customRate;
    const commission = pvValue * rate;

    // Update preview
    document.getElementById('previewPV').textContent = pvValue.toFixed(2);
    document.getElementById('previewRate').textContent = '฿' + rate.toFixed(2);
    document.getElementById('previewCommission').textContent = '฿' + commission.toFixed(2);

    // Multi-level preview
    document.getElementById('previewLevel2').textContent = '฿' + (commission * 0.5).toFixed(2);
    document.getElementById('previewLevel3').textContent = '฿' + (commission * 0.3).toFixed(2);
    document.getElementById('previewLevel4').textContent = '฿' + (commission * 0.2).toFixed(2);
}

function toggleCustomRate() {
    const useGlobal = document.getElementById('useGlobalRate').checked;
    const customSection = document.getElementById('customRateSection');

    if (useGlobal) {
        customSection.classList.add('hidden');
    } else {
        customSection.classList.remove('hidden');
    }

    calculatePreview();
}

function setPvFromPrice() {
    document.getElementById('pvValue').value = productPrice;
    calculatePreview();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    calculatePreview();
});
</script>
@endsection

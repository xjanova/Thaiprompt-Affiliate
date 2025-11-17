@extends('layouts.admin-v3')

@section('title', 'Smart Commission Calculator')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-calculator text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">Smart Commission Calculator</h1>
                        <p class="text-indigo-100 text-lg mt-1">คำนวณคอมมิชชั่นแบบละเอียด พร้อมข้อจำกัดทั้งหมด</p>
                    </div>
                </div>

                {{-- Quick Action --}}
                <a href="{{ route('admin.mlm.settings.index') }}"
                   class="glass-fusion hover:bg-white/20 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                    <i class="fas fa-cog"></i>
                    ตั้งค่า MLM
                </a>
            </div>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-l-4 border-blue-500 dark:border-blue-400 rounded-xl p-6 shadow-lg">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-info-circle text-white text-xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">เครื่องมือคำนวณคอมมิชชั่นอัจฉริยะ</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    เครื่องมือนี้จะช่วยคำนวณคอมมิชชั่นทั้งหมดตามข้อมูลที่กรอก รวมถึงตรวจสอบ Overpay Protection เพื่อป้องกันการจ่ายคอมมิชชั่นเกินกว่าที่กำหนด (50%)
                </p>
            </div>
        </div>
    </div>

    {{-- Calculator Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Calculator Input Card --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-sliders-h text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลการคำนวณ</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กรอกข้อมูลเพื่อคำนวณคอมมิชชั่น</p>
                </div>
            </div>

            <form id="calculator-form" class="space-y-6">
                {{-- Sales Amount --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-dollar-sign mr-1"></i>
                        ยอดขาย (บาท) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="sales_amount" step="0.01" min="0" required
                           class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                           placeholder="10000">
                </div>

                {{-- PV Rate --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-exchange-alt mr-1"></i>
                        อัตราแลกเปลี่ยน PV (บาท/PV)
                    </label>
                    <input type="number" id="pv_rate" step="0.01" min="0" value="1"
                           class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                           placeholder="1.00">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        ค่าเริ่มต้นจากการตั้งค่าระบบ
                    </p>
                </div>

                {{-- Member Depth --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-layer-group mr-1"></i>
                        จำนวนชั้นสายงาน (Unilevel)
                    </label>
                    <input type="number" id="member_depth" step="1" min="1" max="20" value="10"
                           class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                           placeholder="10">
                </div>

                {{-- Binary Pairs --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-code-branch mr-1"></i>
                        จำนวนคู่ Binary (วันนี้)
                    </label>
                    <input type="number" id="binary_pairs" step="1" min="0" value="0"
                           class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                           placeholder="0">
                </div>

                {{-- Checkboxes --}}
                <div class="space-y-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" id="use_constraints" checked
                               class="w-5 h-5 mt-0.5 text-blue-600 rounded focus:ring-blue-500 cursor-pointer">
                        <div class="flex-1">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                ใช้ข้อจำกัดทั้งหมด
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                max per day, max per level
                            </p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" id="check_overpay" checked
                               class="w-5 h-5 mt-0.5 text-blue-600 rounded focus:ring-blue-500 cursor-pointer">
                        <div class="flex-1">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                เช็ค Overpay Protection
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ตรวจสอบการจ่ายคอมมิชชั่นเกิน 50%
                            </p>
                        </div>
                    </label>
                </div>

                {{-- Calculate Button --}}
                <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-calculator text-xl"></i>
                    <span>คำนวณคอมมิชชั่น</span>
                </button>
            </form>
        </div>

        {{-- Results Card --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-bar text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ผลการคำนวณ</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แสดงผลลัพธ์การคำนวณ</p>
                </div>
            </div>

            <div id="results-container" class="space-y-4">
                <div class="text-center text-gray-500 dark:text-gray-400 py-12">
                    <div class="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fas fa-calculator text-5xl text-blue-400 dark:text-blue-500"></i>
                    </div>
                    <p class="text-sm font-medium">กรอกข้อมูลและกดคำนวณเพื่อดูผล</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Breakdown Section --}}
    <div id="detailed-breakdown" class="hidden glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 px-8 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <i class="fas fa-chart-line text-purple-600 dark:text-purple-400"></i>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">รายละเอียดการคำนวณ</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-gray-700 dark:text-gray-300">ระดับ/ประเภท</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-300">%</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-300">ยอดก่อน Constraint</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-300">Constraint ที่ใช้</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-700 dark:text-gray-300">ยอดสุทธิ</th>
                    </tr>
                </thead>
                <tbody id="breakdown-tbody" class="divide-y divide-gray-200 dark:divide-gray-700">
                </tbody>
                <tfoot class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-gray-900 dark:text-white text-lg">รวมทั้งหมด:</td>
                        <td id="total-amount" class="px-6 py-4 text-right text-xl text-purple-600 dark:text-purple-400"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Feature Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Real-time Calculation --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-blue-500 dark:border-blue-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-bolt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">คำนวณแบบ Real-time</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                คำนวณคอมมิชชั่นทันทีตามข้อมูลที่กรอก แสดงผลลัพธ์อย่างละเอียด
            </p>
        </div>

        {{-- Overpay Protection --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-purple-500 dark:border-purple-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Overpay Protection</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ตรวจสอบและเตือนเมื่อเปอร์เซ็นต์คอมมิชชั่นเกิน 50% เพื่อป้องกันการขาดทุน
            </p>
        </div>

        {{-- Constraint System --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-indigo-500 dark:border-indigo-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-cogs text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Constraint System</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                รองรับข้อจำกัดต่างๆ เช่น max per day, max per level สำหรับควบคุมการจ่ายคอมมิชชั่น
            </p>
        </div>
    </div>
</div>

<script>
document.getElementById('calculator-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const salesAmount = parseFloat(document.getElementById('sales_amount').value);
    const pvRate = parseFloat(document.getElementById('pv_rate').value);
    const memberDepth = parseInt(document.getElementById('member_depth').value);
    const binaryPairs = parseInt(document.getElementById('binary_pairs').value);
    const useConstraints = document.getElementById('use_constraints').checked;
    const checkOverpay = document.getElementById('check_overpay').checked;

    // Show loading
    document.getElementById('results-container').innerHTML = `
        <div class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 dark:border-gray-700 dark:border-t-blue-400"></div>
            <p class="text-gray-600 dark:text-gray-400 mt-4 font-medium">กำลังคำนวณ...</p>
        </div>
    `;

    try {
        const response = await fetch('{{ route("admin.mlm.settings.preview-calculation") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                sales_amount: salesAmount,
                pv_rate: pvRate,
                member_depth: memberDepth,
                binary_pairs: binaryPairs,
                use_constraints: useConstraints,
                check_overpay: checkOverpay
            })
        });

        const data = await response.json();

        displayResults(data);
        displayDetailedBreakdown(data);

    } catch (error) {
        console.error('Calculation error:', error);
        document.getElementById('results-container').innerHTML = `
            <div class="bg-red-50 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-800 rounded-xl p-6 text-red-700 dark:text-red-400">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                    <div>
                        <h4 class="font-bold mb-1">เกิดข้อผิดพลาด</h4>
                        <p class="text-sm">${error.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
});

function displayResults(data) {
    const container = document.getElementById('results-container');

    const totalPercentage = data.total_percentage || 0;
    const isOverpay = totalPercentage > 50;
    const isWarning = totalPercentage > 40 && totalPercentage <= 50;

    let alertClass = 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-700 text-green-800 dark:text-green-300';
    let alertIcon = 'fa-check-circle';
    let alertIconColor = 'text-green-600 dark:text-green-400';
    let alertTitle = 'ปลอดภัย';
    let alertMessage = 'เปอร์เซ็นต์คอมมิชชั่นอยู่ในเกณฑ์ที่เหมาะสม';

    if (isOverpay) {
        alertClass = 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-800 dark:text-red-300';
        alertIcon = 'fa-exclamation-triangle';
        alertIconColor = 'text-red-600 dark:text-red-400';
        alertTitle = 'อันตราย - Overpay!';
        alertMessage = 'เปอร์เซ็นต์คอมมิชชั่นเกิน 50% อาจทำให้ขาดทุน';
    } else if (isWarning) {
        alertClass = 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-300';
        alertIcon = 'fa-exclamation-circle';
        alertIconColor = 'text-yellow-600 dark:text-yellow-400';
        alertTitle = 'คำเตือน';
        alertMessage = 'เปอร์เซ็นต์คอมมิชชั่นใกล้ขีดจำกัด';
    }

    container.innerHTML = `
        <!-- Summary Alert -->
        <div class="${alertClass} border-2 rounded-xl p-5 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas ${alertIcon} ${alertIconColor} text-3xl"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-xl mb-1">${alertTitle}</h3>
                    <p class="text-sm">${alertMessage}</p>
                </div>
            </div>
        </div>

        <!-- Main Stats -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-xl p-5 border border-purple-200 dark:border-purple-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                    <i class="fas fa-coins text-purple-600 dark:text-purple-400"></i>
                    PV รวม
                </div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">${(data.total_pv || 0).toLocaleString()}</div>
            </div>
            <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/30 dark:to-pink-800/30 rounded-xl p-5 border border-pink-200 dark:border-pink-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-pink-600 dark:text-pink-400"></i>
                    คอมมิชชั่นรวม
                </div>
                <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">฿${(data.total_commission || 0).toLocaleString()}</div>
            </div>
        </div>

        <!-- Percentage Progress -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <i class="fas fa-percentage mr-1"></i>
                    เปอร์เซ็นต์คอมมิชชั่นรวม
                </span>
                <span class="text-4xl font-bold ${isOverpay ? 'text-red-600 dark:text-red-400' : isWarning ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'}">
                    ${totalPercentage.toFixed(2)}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                <div class="h-4 rounded-full transition-all duration-500 ${isOverpay ? 'bg-gradient-to-r from-red-500 to-red-600' : isWarning ? 'bg-gradient-to-r from-yellow-500 to-yellow-600' : 'bg-gradient-to-r from-green-500 to-green-600'}"
                     style="width: ${Math.min(totalPercentage, 100)}%"></div>
            </div>
        </div>

        <!-- Commission Breakdown -->
        <div class="mt-6 space-y-3">
            <div class="flex justify-between items-center py-3 px-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i class="fas fa-layer-group text-blue-600 dark:text-blue-400"></i>
                    Unilevel Commission:
                </span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">฿${(data.unilevel_commission || 0).toLocaleString()}</span>
            </div>
            <div class="flex justify-between items-center py-3 px-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i class="fas fa-code-branch text-purple-600 dark:text-purple-400"></i>
                    Binary Commission:
                </span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">฿${(data.binary_commission || 0).toLocaleString()}</span>
            </div>
            ${data.constraints_applied ? `
            <div class="flex justify-between items-center py-3 px-4 bg-green-50 dark:bg-green-900/30 rounded-xl border border-green-200 dark:border-green-700">
                <span class="text-sm font-medium text-green-700 dark:text-green-300 flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Constraints Applied:
                </span>
                <span class="text-sm font-semibold text-green-700 dark:text-green-300">${data.constraints_applied}</span>
            </div>
            ` : ''}
        </div>
    `;
}

function displayDetailedBreakdown(data) {
    const breakdown = data.breakdown || [];
    const tbody = document.getElementById('breakdown-tbody');
    const detailedSection = document.getElementById('detailed-breakdown');

    if (breakdown.length === 0) {
        detailedSection.classList.add('hidden');
        return;
    }

    detailedSection.classList.remove('hidden');

    tbody.innerHTML = breakdown.map((item, index) => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">${item.label}</td>
            <td class="px-6 py-4 text-right text-gray-900 dark:text-white">${item.percentage}%</td>
            <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">฿${item.amount_before_constraint.toLocaleString()}</td>
            <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400">${item.constraint_used || '-'}</td>
            <td class="px-6 py-4 text-right font-bold text-purple-600 dark:text-purple-400">฿${item.final_amount.toLocaleString()}</td>
        </tr>
    `).join('');

    document.getElementById('total-amount').textContent = `฿${(data.total_commission || 0).toLocaleString()}`;
}

// Load settings on page load
async function loadSettings() {
    try {
        const response = await fetch('{{ route("admin.mlm.settings.get") }}');
        const data = await response.json();
        const settings = data.settings || data;

        if (settings.global_pv_rate) {
            document.getElementById('pv_rate').value = settings.global_pv_rate;
        }

        if (settings.unilevel_max_depth) {
            document.getElementById('member_depth').value = settings.unilevel_max_depth;
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
}

loadSettings();
</script>
@endsection

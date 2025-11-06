@extends('layouts.admin')

@section('title', 'Smart Commission Calculator')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">🧮 Smart Commission Calculator</h1>
        <p class="text-gray-600">คำนวณคอมมิชชั่นแบบละเอียด พร้อมข้อจำกัดทั้งหมด</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Calculator Input -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center gap-2">
                <span>⚙️</span>
                ข้อมูลการคำนวณ
            </h2>

            <form id="calculator-form" class="space-y-6">
                <!-- Sales Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ยอดขาย (บาท) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="sales_amount" step="0.01" min="0" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="10000">
                </div>

                <!-- PV Rate -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        อัตราแลกเปลี่ยน PV (บาท/PV)
                    </label>
                    <input type="number" id="pv_rate" step="0.01" min="0" value="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <p class="text-xs text-gray-500 mt-1">ค่าเริ่มต้นจากการตั้งค่าระบบ</p>
                </div>

                <!-- Member Depth -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        จำนวนชั้นสายงาน (Unilevel)
                    </label>
                    <input type="number" id="member_depth" step="1" min="1" max="20" value="10"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>

                <!-- Binary Pairs -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        จำนวนคู่ Binary (วันนี้)
                    </label>
                    <input type="number" id="binary_pairs" step="1" min="0" value="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>

                <!-- Checkboxes -->
                <div class="space-y-3">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" id="use_constraints" checked
                               class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-sm font-medium text-gray-700">ใช้ข้อจำกัดทั้งหมด (max per day, max per level)</span>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" id="check_overpay" checked
                               class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-sm font-medium text-gray-700">เช็ค Overpay Protection</span>
                    </label>
                </div>

                <!-- Calculate Button -->
                <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 rounded-lg font-medium shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    คำนวณ
                </button>
            </form>
        </div>

        <!-- Results -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center gap-2">
                <span>📊</span>
                ผลการคำนวณ
            </h2>

            <div id="results-container" class="space-y-4">
                <div class="text-center text-gray-500 py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <p>กรอกข้อมูลและกดคำนวณเพื่อดูผล</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div id="detailed-breakdown" class="hidden mt-6 bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center gap-2">
            <span>📈</span>
            รายละเอียดการคำนวณ
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">ระดับ/ประเภท</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-700">%</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-700">ยอดก่อน Constraint</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-700">Constraint ที่ใช้</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-700">ยอดสุทธิ</th>
                    </tr>
                </thead>
                <tbody id="breakdown-tbody" class="divide-y divide-gray-200">
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right">รวมทั้งหมด:</td>
                        <td id="total-amount" class="px-4 py-3 text-right text-lg text-purple-600"></td>
                    </tr>
                </tfoot>
            </table>
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
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            <p class="text-gray-600 mt-4">กำลังคำนวณ...</p>
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
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                ❌ เกิดข้อผิดพลาดในการคำนวณ: ${error.message}
            </div>
        `;
    }
});

function displayResults(data) {
    const container = document.getElementById('results-container');

    const totalPercentage = data.total_percentage || 0;
    const isOverpay = totalPercentage > 50;
    const isWarning = totalPercentage > 40 && totalPercentage <= 50;

    let alertClass = 'bg-green-50 border-green-200 text-green-800';
    let alertIcon = '✅';
    let alertTitle = 'ปลอดภัย';
    let alertMessage = 'เปอร์เซ็นต์คอมมิชชั่นอยู่ในเกณฑ์ที่เหมาะสม';

    if (isOverpay) {
        alertClass = 'bg-red-50 border-red-200 text-red-800';
        alertIcon = '⚠️';
        alertTitle = 'อันตราย - Overpay!';
        alertMessage = 'เปอร์เซ็นต์คอมมิชชั่นเกิน 50% อาจทำให้ขาดทุน';
    } else if (isWarning) {
        alertClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
        alertIcon = '⚡';
        alertTitle = 'คำเตือน';
        alertMessage = 'เปอร์เซ็นต์คอมมิชชั่นใกล้ขีดจำกัด';
    }

    container.innerHTML = `
        <!-- Summary Alert -->
        <div class="${alertClass} border rounded-lg p-4 mb-4">
            <div class="flex items-start">
                <span class="text-2xl mr-3">${alertIcon}</span>
                <div class="flex-1">
                    <h3 class="font-semibold text-lg">${alertTitle}</h3>
                    <p class="text-sm mt-1">${alertMessage}</p>
                </div>
            </div>
        </div>

        <!-- Main Stats -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">PV รวม</div>
                <div class="text-2xl font-bold text-purple-600">${(data.total_pv || 0).toLocaleString()}</div>
            </div>
            <div class="bg-pink-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">คอมมิชชั่นรวม</div>
                <div class="text-2xl font-bold text-pink-600">฿${(data.total_commission || 0).toLocaleString()}</div>
            </div>
        </div>

        <!-- Percentage -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">เปอร์เซ็นต์คอมมิชชั่นรวม</span>
                <span class="text-3xl font-bold ${isOverpay ? 'text-red-600' : isWarning ? 'text-yellow-600' : 'text-green-600'}">
                    ${totalPercentage.toFixed(2)}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-500 ${isOverpay ? 'bg-red-600' : isWarning ? 'bg-yellow-600' : 'bg-green-600'}"
                     style="width: ${Math.min(totalPercentage, 100)}%"></div>
            </div>
        </div>

        <!-- Breakdown -->
        <div class="mt-4 space-y-2">
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-700">Unilevel Commission:</span>
                <span class="font-semibold">฿${(data.unilevel_commission || 0).toLocaleString()}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-700">Binary Commission:</span>
                <span class="font-semibold">฿${(data.binary_commission || 0).toLocaleString()}</span>
            </div>
            ${data.constraints_applied ? `
            <div class="flex justify-between py-2 text-sm text-gray-600">
                <span>Constraints Applied:</span>
                <span>✅ ${data.constraints_applied}</span>
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

    tbody.innerHTML = breakdown.map(item => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">${item.label}</td>
            <td class="px-4 py-3 text-right">${item.percentage}%</td>
            <td class="px-4 py-3 text-right">฿${item.amount_before_constraint.toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-sm text-gray-600">${item.constraint_used || '-'}</td>
            <td class="px-4 py-3 text-right font-semibold">฿${item.final_amount.toLocaleString()}</td>
        </tr>
    `).join('');

    document.getElementById('total-amount').textContent = `฿${(data.total_commission || 0).toLocaleString()}`;
}

// Load settings on page load
async function loadSettings() {
    try {
        const response = await fetch('{{ route("admin.mlm.settings.get-settings") }}');
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

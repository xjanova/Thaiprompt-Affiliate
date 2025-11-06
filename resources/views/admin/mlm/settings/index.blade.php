@extends('layouts.admin')

@section('title', 'ตั้งค่า MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Premium Header -->
    <div class="mb-8">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 bg-clip-text text-transparent mb-2 animate-gradient">
                    ⚙️ ตั้งค่า MLM
                </h1>
                <p class="text-gray-600 text-lg">Premium Edition - ระบบตั้งค่าแบบมืออาชีพ พร้อมการคำนวณ Real-time</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.mlm.calculator') }}"
                   class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl shadow-lg transition-all duration-200 flex items-center gap-2 transform hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    เครื่องคำนวณ
                </a>
            </div>
        </div>
    </div>

    <!-- Overpay Warning Alert -->
    @if(isset($currentCommissionPercentage))
    <div class="mb-6 rounded-lg p-4 border
        {{ $currentCommissionPercentage > 50 ? 'bg-red-50 border-red-200' : ($currentCommissionPercentage > 40 ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200') }}">
        <div class="flex items-start">
            <svg class="w-6 h-6 mt-0.5 mr-3
                {{ $currentCommissionPercentage > 50 ? 'text-red-500' : ($currentCommissionPercentage > 40 ? 'text-yellow-500' : 'text-green-500') }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($currentCommissionPercentage > 50)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                @elseif($currentCommissionPercentage > 40)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @endif
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold
                    {{ $currentCommissionPercentage > 50 ? 'text-red-800' : ($currentCommissionPercentage > 40 ? 'text-yellow-800' : 'text-green-800') }}">
                    ประมาณการเปอร์เซ็นต์คอมมิชชั่นรวม: {{ number_format($currentCommissionPercentage, 2) }}%
                </h3>
                <p class="text-sm mt-1
                    {{ $currentCommissionPercentage > 50 ? 'text-red-700' : ($currentCommissionPercentage > 40 ? 'text-yellow-700' : 'text-green-700') }}">
                    @if($currentCommissionPercentage > 50)
                        <strong>⚠️ อันตราย - Overpay!</strong> เปอร์เซ็นต์คอมมิชชั่นรวมเกิน 50% อาจทำให้ขาดทุน
                        <br>แนะนำ: ลดเปอร์เซ็นต์ Unilevel หรือ Binary เพื่อควบคุมต้นทุน
                    @elseif($currentCommissionPercentage > 40)
                        <strong>⚡ คำเตือน</strong> เปอร์เซ็นต์คอมมิชชั่นใกล้ขีดจำกัด กรุณาตรวจสอบการคำนวณอีกครั้ง
                    @else
                        <strong>✓ ปลอดภัย</strong> เปอร์เซ็นต์คอมมิชชั่นอยู่ในเกณฑ์ที่เหมาะสม
                    @endif
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">การตั้งค่าระบบ MLM</h3>
                <p class="text-sm text-blue-700 mt-1">
                    ค่าเหล่านี้ใช้ควบคุมการทำงานของระบบ MLM โปรดตรวจสอบให้แน่ใจก่อนทำการเปลี่ยนแปลง
                    การเปลี่ยนแปลงบางอย่างอาจส่งผลกระทบต่อการคำนวณคอมมิชชั่น
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Left Column: Settings Form (2/3 width) -->
        <div class="xl:col-span-2">
            <form id="settings-form" action="{{ route('admin.mlm.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                @forelse($settings as $group => $groupSettings)
        <!-- Settings Group -->
        <div class="bg-white rounded-2xl shadow-xl mb-6 overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-2xl">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                        <span class="text-2xl">{{ getGroupIcon($group) }}</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ getGroupName($group) }}</h2>
                        <p class="text-sm text-white/80">{{ getGroupDescription($group) }}</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($groupSettings as $setting)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Setting Info -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-800 mb-1">
                                {{ $setting->key }}
                            </label>
                            <p class="text-sm text-gray-600">
                                {{ app()->getLocale() === 'th' && $setting->description_th
                                    ? $setting->description_th
                                    : $setting->description }}
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $setting->type === 'string' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $setting->type === 'number' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $setting->type === 'boolean' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $setting->type === 'json' ? 'bg-pink-100 text-pink-800' : '' }}">
                                    {{ $setting->type }}
                                </span>
                            </div>
                        </div>

                        <!-- Setting Input -->
                        <div class="flex items-center gap-2">
                            @if(!$setting->is_editable)
                                <!-- Read-only display for non-editable settings -->
                                <div class="w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg border border-gray-300">
                                    @if($setting->type === 'boolean')
                                        {{ $setting->getTypedValue() ? 'เปิด' : 'ปิด' }}
                                    @elseif($setting->type === 'json')
                                        <pre class="font-mono text-xs">{{ $setting->value }}</pre>
                                    @else
                                        {{ $setting->value }}
                                    @endif
                                </div>
                                <span class="px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded-full whitespace-nowrap">
                                    🔒 Read-only
                                </span>
                            @else
                                <!-- Editable inputs -->
                                @if($setting->type === 'boolean')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               name="settings[{{ $setting->key }}]"
                                               value="1"
                                               {{ $setting->getTypedValue() ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">
                                            {{ $setting->getTypedValue() ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </label>
                                @elseif($setting->type === 'integer' || $setting->type === 'decimal' || $setting->type === 'float')
                                    <input type="number"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           step="{{ $setting->type === 'integer' ? '1' : 'any' }}"
                                           onchange="updatePreview()"
                                           class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                @elseif($setting->key === 'unilevel_levels')
                                    <!-- Visual Unilevel Editor -->
                                    <div class="w-full">
                                        <div id="unilevel-editor" class="space-y-2 mb-3">
                                            <!-- Will be populated by JavaScript -->
                                        </div>
                                        <textarea name="settings[{{ $setting->key }}]"
                                                  id="unilevel-json"
                                                  class="hidden">{{ $setting->value }}</textarea>
                                        <button type="button" onclick="addUnilevelLevel()"
                                                class="w-full px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            เพิ่มระดับ
                                        </button>
                                    </div>
                                @elseif($setting->type === 'json' || $setting->type === 'array')
                                    <textarea name="settings[{{ $setting->key }}]"
                                              rows="3"
                                              class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ $setting->value }}</textarea>
                                @else
                                    <input type="text"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Save Button -->
        @if($settings->count() > 0)
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg font-medium shadow-lg transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
        @endif
            </form>
        </div>

        <!-- Right Column: Live Preview & Dashboard (1/3 width) -->
        <div class="xl:col-span-1">
            <div class="sticky top-4 space-y-6">
                <!-- Commission Dashboard -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg flex items-center justify-center text-white text-sm">⚡</span>
                        ภาพรวมคอมมิชชั่น
                    </h3>

                    <!-- Commission Percentage Gauge -->
                    <div class="mb-6">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-medium text-gray-600">เปอร์เซ็นต์รวม</span>
                            <span id="total-percentage-text" class="text-3xl font-bold text-purple-600">{{ number_format($currentCommissionPercentage ?? 0, 2) }}%</span>
                        </div>
                        <div class="relative w-full h-6 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                            <div id="percentage-bar"
                                 class="absolute top-0 left-0 h-full transition-all duration-500 ease-out rounded-full"
                                 style="width: {{ min($currentCommissionPercentage ?? 0, 100) }}%; background: linear-gradient(90deg, #9333ea, #ec4899);"></div>
                            <div class="absolute top-0 left-1/2 w-0.5 h-full bg-yellow-400 opacity-50"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>0%</span>
                            <span class="text-yellow-600 font-semibold">← 50% (Limit)</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div id="overpay-status" class="mb-6 p-4 rounded-xl border-2 {{ ($currentCommissionPercentage ?? 0) > 50 ? 'bg-red-50 border-red-300' : (($currentCommissionPercentage ?? 0) > 40 ? 'bg-yellow-50 border-yellow-300' : 'bg-green-50 border-green-300') }}">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">
                                @if(($currentCommissionPercentage ?? 0) > 50)
                                    ⚠️
                                @elseif(($currentCommissionPercentage ?? 0) > 40)
                                    ⚡
                                @else
                                    ✅
                                @endif
                            </span>
                            <div class="flex-1">
                                <h4 id="status-title" class="font-bold text-sm {{ ($currentCommissionPercentage ?? 0) > 50 ? 'text-red-800' : (($currentCommissionPercentage ?? 0) > 40 ? 'text-yellow-800' : 'text-green-800') }}">
                                    @if(($currentCommissionPercentage ?? 0) > 50)
                                        อันตราย - Overpay!
                                    @elseif(($currentCommissionPercentage ?? 0) > 40)
                                        ใกล้ขีดจำกัด
                                    @else
                                        ปลอดภัย
                                    @endif
                                </h4>
                                <p id="status-message" class="text-xs mt-1 {{ ($currentCommissionPercentage ?? 0) > 50 ? 'text-red-700' : (($currentCommissionPercentage ?? 0) > 40 ? 'text-yellow-700' : 'text-green-700') }}">
                                    @if(($currentCommissionPercentage ?? 0) > 50)
                                        เปอร์เซ็นต์เกิน 50% อาจขาดทุน
                                    @elseif(($currentCommissionPercentage ?? 0) > 40)
                                        ควรตรวจสอบอีกครั้ง
                                    @else
                                        เปอร์เซ็นต์เหมาะสม
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Commission Chart -->
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">แยกตามประเภท</h4>
                        <div style="height: 200px;">
                            <canvas id="commission-chart"></canvas>
                        </div>
                    </div>

                    <!-- Breakdown Details -->
                    <div id="breakdown-details" class="space-y-2 mb-4">
                        <!-- Will be populated by JavaScript -->
                    </div>

                    <!-- Recalculate Button -->
                    <button type="button" onclick="calculatePreview()" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        คำนวณใหม่
                    </button>
                </div>

                <!-- Tips Card -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl shadow-lg p-5 border border-purple-200">
                    <h3 class="text-base font-bold text-purple-900 mb-3 flex items-center gap-2">
                        💡 เคล็ดลับ
                    </h3>
                    <ul class="space-y-2 text-sm text-purple-800">
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>เปอร์เซ็นต์รวมไม่ควรเกิน 50%</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>Unilevel ชั้นแรกควรสูงสุด</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>ใช้ Constraints จำกัดจ่ายต่อวัน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 font-bold">•</span>
                            <span>เปิด Roll-up รักษา flow</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Common Settings Reference -->
    <div class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">การตั้งค่าทั่วไป</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <h4 class="font-medium text-gray-700 mb-2">General Settings</h4>
                <ul class="space-y-1 text-gray-600">
                    <li>• <code class="bg-white px-2 py-0.5 rounded">auto_approve_commissions</code> - อนุมัติคอมมิชชั่นอัตโนมัติ</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">commission_payout_day</code> - วันจ่ายคอมมิชชั่น</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">minimum_payout</code> - ขั้นต่ำในการถอน</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Calculation Settings</h4>
                <ul class="space-y-1 text-gray-600">
                    <li>• <code class="bg-white px-2 py-0.5 rounded">global_commission_per_pv</code> - อัตราค่าคอม/PV โกลบอล</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">enable_compression</code> - เปิดใช้งาน Compression</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">enable_carry_forward</code> - เปิดใช้งาน Carry Forward</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let unilevelData = [];
let commissionChart = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeUnilevelEditor();
    initializeChart();
    updatePreview();
});

// Initialize Unilevel Visual Editor
function initializeUnilevelEditor() {
    const jsonTextarea = document.getElementById('unilevel-json');
    if (!jsonTextarea) return;

    try {
        unilevelData = JSON.parse(jsonTextarea.value);
    } catch(e) {
        unilevelData = [];
    }

    renderUnilevelEditor();
}

function renderUnilevelEditor() {
    const container = document.getElementById('unilevel-editor');
    if (!container) return;

    container.innerHTML = '';

    unilevelData.forEach((level, index) => {
        const levelDiv = document.createElement('div');
        levelDiv.className = 'flex items-center gap-3 p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border-2 border-purple-200 hover:border-purple-400 transition-all duration-200 shadow-sm hover:shadow-md';
        levelDiv.innerHTML = `
            <div class="flex-shrink-0 w-20 text-center">
                <div class="px-3 py-1 bg-purple-600 text-white rounded-lg font-bold text-sm">
                    ชั้น ${level.level}
                </div>
            </div>
            <div class="flex-1 flex items-center gap-2">
                <input type="number"
                       value="${level.percentage}"
                       onchange="updateUnilevelLevel(${index}, this.value)"
                       class="flex-1 px-4 py-3 text-xl font-bold text-center border-2 border-purple-300 rounded-xl focus:ring-4 focus:ring-purple-500 focus:border-purple-500 bg-white"
                       step="0.1" min="0" max="100">
                <span class="text-2xl font-bold text-purple-600">%</span>
            </div>
            <button type="button" onclick="removeUnilevelLevel(${index})"
                    class="flex-shrink-0 p-3 text-red-600 hover:bg-red-100 rounded-xl transition-all duration-200 hover:scale-110">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        `;
        container.appendChild(levelDiv);
    });

    updateUnilevelJSON();
    updatePreview();
}

function updateUnilevelLevel(index, newValue) {
    unilevelData[index].percentage = parseFloat(newValue);
    updateUnilevelJSON();
    updatePreview();
}

function addUnilevelLevel() {
    const nextLevel = unilevelData.length + 1;
    unilevelData.push({
        level: nextLevel,
        percentage: 1.0
    });
    renderUnilevelEditor();
}

function removeUnilevelLevel(index) {
    if (unilevelData.length <= 1) {
        alert('ต้องมีอย่างน้อย 1 ระดับ');
        return;
    }

    unilevelData.splice(index, 1);
    // Re-number levels
    unilevelData.forEach((level, idx) => {
        level.level = idx + 1;
    });
    renderUnilevelEditor();
}

function updateUnilevelJSON() {
    const jsonTextarea = document.getElementById('unilevel-json');
    if (jsonTextarea) {
        jsonTextarea.value = JSON.stringify(unilevelData);
    }
}

// Initialize Chart
function initializeChart() {
    const ctx = document.getElementById('commission-chart');
    if (!ctx) return;

    commissionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Unilevel', 'Binary', 'เหลือ'],
            datasets: [{
                data: [26, 12, 62],
                backgroundColor: [
                    'rgba(147, 51, 234, 0.9)',
                    'rgba(236, 72, 153, 0.9)',
                    'rgba(209, 213, 219, 0.3)'
                ],
                borderColor: [
                    'rgb(147, 51, 234)',
                    'rgb(236, 72, 153)',
                    'rgb(209, 213, 219)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.toFixed(2) + '%';
                        }
                    }
                }
            }
        }
    });
}

// Update Preview and Calculate
function updatePreview() {
    calculatePreview();
}

async function calculatePreview() {
    try {
        // Calculate Unilevel total
        let unilevelTotal = 0;
        if (unilevelData.length > 0) {
            unilevelTotal = unilevelData.reduce((sum, level) => sum + parseFloat(level.percentage || 0), 0);
        }

        // Get Binary percentage
        const binaryInput = document.querySelector('input[name="settings[binary_match_percentage]"]');
        const binaryPercentage = binaryInput ? parseFloat(binaryInput.value || 0) : 0;
        const binaryEstimate = binaryPercentage * 0.5; // Estimate 50% weak leg

        const totalPercentage = unilevelTotal + binaryEstimate;

        // Update Dashboard UI
        updateDashboard(totalPercentage, unilevelTotal, binaryEstimate);

    } catch (error) {
        console.error('Preview calculation error:', error);
    }
}

function updateDashboard(total, unilevel, binary) {
    // Update percentage text
    const percentText = document.getElementById('total-percentage-text');
    if (percentText) {
        percentText.textContent = total.toFixed(2) + '%';
    }

    // Update progress bar
    const bar = document.getElementById('percentage-bar');
    if (bar) {
        bar.style.width = Math.min(total, 100) + '%';

        // Change color based on threshold
        if (total > 50) {
            bar.style.background = 'linear-gradient(90deg, #dc2626, #ef4444)';
        } else if (total > 40) {
            bar.style.background = 'linear-gradient(90deg, #f59e0b, #fbbf24)';
        } else {
            bar.style.background = 'linear-gradient(90deg, #9333ea, #ec4899)';
        }
    }

    // Update status
    const statusDiv = document.getElementById('overpay-status');
    const statusTitle = document.getElementById('status-title');
    const statusMessage = document.getElementById('status-message');

    if (statusDiv && statusTitle && statusMessage) {
        if (total > 50) {
            statusDiv.className = 'mb-6 p-4 rounded-xl border-2 bg-red-50 border-red-300';
            statusTitle.className = 'font-bold text-sm text-red-800';
            statusTitle.innerHTML = '⚠️ อันตราย - Overpay!';
            statusMessage.className = 'text-xs mt-1 text-red-700';
            statusMessage.textContent = 'เปอร์เซ็นต์เกิน 50% อาจขาดทุน';
        } else if (total > 40) {
            statusDiv.className = 'mb-6 p-4 rounded-xl border-2 bg-yellow-50 border-yellow-300';
            statusTitle.className = 'font-bold text-sm text-yellow-800';
            statusTitle.innerHTML = '⚡ ใกล้ขีดจำกัด';
            statusMessage.className = 'text-xs mt-1 text-yellow-700';
            statusMessage.textContent = 'ควรตรวจสอบอีกครั้ง';
        } else {
            statusDiv.className = 'mb-6 p-4 rounded-xl border-2 bg-green-50 border-green-300';
            statusTitle.className = 'font-bold text-sm text-green-800';
            statusTitle.innerHTML = '✅ ปลอดภัย';
            statusMessage.className = 'text-xs mt-1 text-green-700';
            statusMessage.textContent = 'เปอร์เซ็นต์เหมาะสม';
        }
    }

    // Update chart
    if (commissionChart) {
        const remaining = Math.max(0, 100 - total);
        commissionChart.data.datasets[0].data = [unilevel, binary, remaining];
        commissionChart.update();
    }

    // Update breakdown details
    const breakdownDiv = document.getElementById('breakdown-details');
    if (breakdownDiv) {
        const totalColor = total > 50 ? 'text-red-600' : (total > 40 ? 'text-yellow-600' : 'text-green-600');
        breakdownDiv.innerHTML = `
            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                <span class="text-sm font-medium text-gray-700">Unilevel</span>
                <span class="text-lg font-bold text-purple-600">${unilevel.toFixed(2)}%</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-pink-50 rounded-lg">
                <span class="text-sm font-medium text-gray-700">Binary (ประมาณ)</span>
                <span class="text-lg font-bold text-pink-600">${binary.toFixed(2)}%</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border-t-2 border-gray-300">
                <span class="text-sm font-bold text-gray-900">รวม</span>
                <span class="text-xl font-bold ${totalColor}">${total.toFixed(2)}%</span>
            </div>
        `;
    }
}
</script>

@php
function getGroupIcon($group) {
    return match($group) {
        'general' => '⚙️',
        'pv' => '💎',
        'unilevel' => '📊',
        'binary' => '🔄',
        'flush' => '🌊',
        'placement' => '📍',
        'rollup' => '↗️',
        'retention' => '🎯',
        'commission' => '💰',
        default => '📋'
    };
}

function getGroupName($group) {
    return match($group) {
        'general' => 'ทั่วไป',
        'pv' => 'ระบบ PV',
        'unilevel' => 'Unilevel Commission',
        'binary' => 'Binary Commission',
        'flush' => 'การล้าง PV',
        'placement' => 'การจัดวางสมาชิก',
        'rollup' => 'Roll-up / Compression',
        'retention' => 'การรักษายอด',
        'commission' => 'ป้องกัน Overpay',
        default => ucfirst($group)
    };
}

function getGroupDescription($group) {
    return match($group) {
        'general' => 'การตั้งค่าพื้นฐานของระบบ',
        'pv' => 'อัตราแลกเปลี่ยนและการคำนวณ PV',
        'unilevel' => 'เปอร์เซ็นต์คอมมิชชันแต่ละชั้น',
        'binary' => 'การจับคู่และคอมมิชชันแบบ Binary',
        'flush' => 'การล้างหรือยกยอด PV',
        'placement' => 'กลยุทธ์การจัดวางสมาชิกใหม่',
        'rollup' => 'การยกคอมมิชชันข้ามคนไม่ active',
        'retention' => 'เงื่อนไขการรักษายอดขาย',
        'commission' => 'การป้องกันการจ่ายเกิน',
        default => ''
    };
}
@endphp

<style>
/* Premium Animations */
@keyframes gradient {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #9333ea, #ec4899);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #7e22ce, #db2777);
}

/* Smooth transitions for all interactive elements */
input, select, textarea, button {
    transition: all 0.2s ease;
}

/* Pulse animation for important elements */
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.animate-pulse-subtle {
    animation: pulse-subtle 2s ease-in-out infinite;
}
</style>

@endsection

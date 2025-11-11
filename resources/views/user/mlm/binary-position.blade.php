@extends('layouts.user')

@section('title', 'ตำแหน่งในระบบ Binary')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🌲</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ตำแหน่งในระบบ Binary</h1>
                <p class="text-violet-100 mt-1">โครงสร้างทีมแบบ Binary Tree</p>
            </div>
        </div>
    </div>

    <!-- Position Info -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span>📍</span> ข้อมูลตำแหน่งของคุณ
        </h2>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Left Side -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span>👈</span> ทีมซ้าย (Left)
                    </h3>
                    <span class="text-blue-600 text-2xl font-bold">L</span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-blue-200">
                        <span class="text-gray-600">จำนวนสมาชิก:</span>
                        <span class="font-bold text-gray-800">{{ $position['left_count'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-blue-200">
                        <span class="text-gray-600">PV รวม:</span>
                        <span class="font-bold text-blue-600">{{ number_format($position['left_pv'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600">รายได้สะสม:</span>
                        <span class="font-bold text-green-600">฿{{ number_format($position['left_commission'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Right Side -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span>👉</span> ทีมขวา (Right)
                    </h3>
                    <span class="text-purple-600 text-2xl font-bold">R</span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-purple-200">
                        <span class="text-gray-600">จำนวนสมาชิก:</span>
                        <span class="font-bold text-gray-800">{{ $position['right_count'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-purple-200">
                        <span class="text-gray-600">PV รวม:</span>
                        <span class="font-bold text-purple-600">{{ number_format($position['right_pv'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600">รายได้สะสม:</span>
                        <span class="font-bold text-green-600">฿{{ number_format($position['right_commission'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Binary Matching Summary -->
        <div class="mt-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-2 border-green-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span>⚖️</span> Binary Matching Bonus
            </h3>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-1">ทีมเล็กกว่า</div>
                    <div class="text-2xl font-bold text-orange-600">
                        {{ min($position['left_pv'] ?? 0, $position['right_pv'] ?? 0) > 0 ? number_format(min($position['left_pv'] ?? 0, $position['right_pv'] ?? 0), 0) : 0 }} PV
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-1">อัตราจับคู่</div>
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $position['matching_rate'] ?? 10 }}%
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-1">โบนัสที่ได้</div>
                    <div class="text-2xl font-bold text-green-600">
                        ฿{{ number_format($position['matching_bonus'] ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Status -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>📊</span> สถานะความสมดุล
        </h2>

        @php
            $leftPv = $position['left_pv'] ?? 0;
            $rightPv = $position['right_pv'] ?? 0;
            $total = $leftPv + $rightPv;
            $leftPercentage = $total > 0 ? ($leftPv / $total) * 100 : 50;
            $rightPercentage = $total > 0 ? ($rightPv / $total) * 100 : 50;
            $isBalanced = abs($leftPv - $rightPv) / max($leftPv, $rightPv, 1) <= 0.3;
        @endphp

        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>👈 ซ้าย: {{ number_format($leftPv, 0) }} PV ({{ number_format($leftPercentage, 1) }}%)</span>
                <span>👉 ขวา: {{ number_format($rightPv, 0) }} PV ({{ number_format($rightPercentage, 1) }}%)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-8 flex overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold"
                     style="width: {{ $leftPercentage }}%">
                    @if($leftPercentage > 15)
                        {{ number_format($leftPercentage, 0) }}%
                    @endif
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center text-white text-xs font-bold"
                     style="width: {{ $rightPercentage }}%">
                    @if($rightPercentage > 15)
                        {{ number_format($rightPercentage, 0) }}%
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-{{ $isBalanced ? 'green' : 'yellow' }}-50 border border-{{ $isBalanced ? 'green' : 'yellow' }}-200 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <span class="text-3xl">{{ $isBalanced ? '✅' : '⚠️' }}</span>
                <div>
                    <div class="font-bold text-gray-800">
                        {{ $isBalanced ? 'ทีมสมดุล!' : 'ทีมไม่สมดุล' }}
                    </div>
                    <div class="text-sm text-gray-600">
                        @if($isBalanced)
                            ทีมของคุณมีความสมดุลดี เหมาะสำหรับรับ Binary Matching Bonus สูงสุด
                        @else
                            ควรเพิ่มสมาชิกในทีมที่มี PV น้อยกว่า เพื่อเพิ่ม Binary Matching Bonus
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Placement Links -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>🔗</span> ลิงก์วางตำแหน่ง
        </h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <div class="font-bold text-gray-800 mb-2">วางซ้าย (Left)</div>
                <div class="flex gap-2">
                    <input type="text"
                           id="leftLink"
                           value="{{ route('register', ['ref' => $member->member_code, 'position' => 'left']) }}"
                           readonly
                           class="flex-1 px-3 py-2 bg-white border border-blue-300 rounded text-xs font-mono">
                    <button onclick="copyLink('leftLink')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-semibold transition-colors">
                        📋
                    </button>
                </div>
            </div>

            <div class="bg-purple-50 rounded-xl p-4 border border-purple-200">
                <div class="font-bold text-gray-800 mb-2">วางขวา (Right)</div>
                <div class="flex gap-2">
                    <input type="text"
                           id="rightLink"
                           value="{{ route('register', ['ref' => $member->member_code, 'position' => 'right']) }}"
                           readonly
                           class="flex-1 px-3 py-2 bg-white border border-purple-300 rounded text-xs font-mono">
                    <button onclick="copyLink('rightLink')" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded font-semibold transition-colors">
                        📋
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-2xl shadow-xl p-6 border border-amber-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>💡</span> เคล็ดลับ Binary System
        </h3>
        <ul class="space-y-2 text-sm text-gray-700">
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>รักษาความสมดุล:</strong> พยายามเพิ่มสมาชิกทั้งสองฝั่งให้สมดุล</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>Binary Matching:</strong> โบนัสคำนวณจากทีมที่เล็กกว่า</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>Spillover:</strong> สมาชิกใหม่อาจถูกวางในตำแหน่งที่เหมาะสม</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span><strong>PV สำคัญ:</strong> ไม่ใช่แค่จำนวนคน แต่ PV ก็สำคัญเช่นกัน</span>
            </li>
        </ul>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <span id="toastMessage"></span>
</div>

@push('scripts')
<script>
function copyLink(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('✅ คัดลอกลิงก์แล้ว');
    });
}

function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.classList.add('hidden');
            toast.style.opacity = '1';
        }, 500);
    }, 3000);
}
</script>
@endpush
@endsection

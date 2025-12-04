@extends('layouts.user-arrow-x')

@section('title', 'ศูนย์กลาง MLM - สายงานและทีม')

@push('styles')
<style>
    /* Glassmorphism Action Cards */
    .mlm-action-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.5);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .mlm-action-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.2);
    }
    .dark .mlm-action-card {
        background: linear-gradient(135deg, rgba(31,41,55,0.9) 0%, rgba(31,41,55,0.7) 100%);
        border: 1px solid rgba(255,255,255,0.1);
    }

    /* Gradient Buttons */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .btn-gradient-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .btn-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .btn-gradient-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    /* Animated Icon */
    .icon-bounce {
        animation: bounce 2s infinite;
    }
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-6">
    <div class="max-w-7xl mx-auto px-4">

        {{-- ========== HEADER WITH MEMBER INFO ========== --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 p-8 mb-6 shadow-2xl">
            {{-- Background Pattern --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative flex flex-col lg:flex-row items-center justify-between gap-6">
                {{-- Member Info --}}
                <div class="flex items-center gap-6">
                    <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-5xl">
                        👑
                    </div>
                    <div class="text-white">
                        <h1 class="text-3xl lg:text-4xl font-black">ศูนย์กลาง MLM</h1>
                        <p class="text-white/80 text-lg mt-1">{{ auth()->user()->name }}</p>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-3 py-1 rounded-full bg-white/20 text-sm font-semibold">
                                รหัส: {{ $member->member_code }}
                            </span>
                            <span class="px-3 py-1 rounded-full bg-green-400/30 text-sm font-semibold">
                                ✓ {{ $member->plan?->name ?? 'Active' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-white">{{ number_format($member->total_team_members ?? 0) }}</div>
                        <div class="text-white/70 text-xs mt-1">สมาชิกทั้งหมด</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-white">{{ number_format($member->total_direct_referrals ?? 0) }}</div>
                        <div class="text-white/70 text-xs mt-1">แนะนำตรง</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-green-300">{{ $member->left_leg_members ?? 0 }}</div>
                        <div class="text-white/70 text-xs mt-1">⬅️ ขาซ้าย</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-red-300">{{ $member->right_leg_members ?? 0 }}</div>
                        <div class="text-white/70 text-xs mt-1">➡️ ขาขวา</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== MLM ACTION BUTTONS ========== --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            {{-- ผังสายงาน (Active) --}}
            <div class="mlm-action-card rounded-2xl p-4 text-center ring-2 ring-purple-500 ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-2xl text-white shadow-lg mb-3 icon-bounce">
                    🌳
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">ผังสายงาน</div>
                <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">กำลังดู</div>
            </div>

            {{-- ทีมงาน --}}
            <a href="{{ route('user.mlm.team') }}" class="mlm-action-card rounded-2xl p-4 text-center hover:ring-2 hover:ring-blue-500 hover:ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-2xl text-white shadow-lg mb-3">
                    👥
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">ทีมงาน</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $member->total_direct_referrals ?? 0 }} คน</div>
            </a>

            {{-- ลิ้งค์เชิญ --}}
            <a href="{{ route('user.mlm.referral') }}" class="mlm-action-card rounded-2xl p-4 text-center hover:ring-2 hover:ring-green-500 hover:ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center text-2xl text-white shadow-lg mb-3">
                    🔗
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">ลิ้งค์เชิญ</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">แชร์รับเงิน</div>
            </a>

            {{-- คอมมิชชั่น --}}
            <a href="{{ route('user.mlm.commissions') }}" class="mlm-action-card rounded-2xl p-4 text-center hover:ring-2 hover:ring-yellow-500 hover:ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center text-2xl text-white shadow-lg mb-3">
                    💰
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">คอมมิชชั่น</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ดูรายได้</div>
            </a>

            {{-- ตำแหน่ง Binary --}}
            <a href="{{ route('user.mlm.binary-position') }}" class="mlm-action-card rounded-2xl p-4 text-center hover:ring-2 hover:ring-indigo-500 hover:ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-2xl text-white shadow-lg mb-3">
                    🔀
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">Binary</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตำแหน่งขา</div>
            </a>

            {{-- จำลองรายได้ --}}
            <a href="{{ route('user.mlm.income-simulator') }}" class="mlm-action-card rounded-2xl p-4 text-center hover:ring-2 hover:ring-pink-500 hover:ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-pink-500 to-rose-500 flex items-center justify-center text-2xl text-white shadow-lg mb-3">
                    📊
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">จำลองรายได้</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">คำนวณ</div>
            </a>
        </div>

        {{-- ========== SECONDARY ACTIONS ========== --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <a href="{{ route('user.mlm.dashboard') }}" class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-gray-800 shadow hover:shadow-lg transition-all">
                <span class="text-2xl">📋</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white text-sm">แดชบอร์ด</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">ภาพรวม MLM</div>
                </div>
            </a>

            <a href="{{ route('user.mlm.scenario-simulator') }}" class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-gray-800 shadow hover:shadow-lg transition-all">
                <span class="text-2xl">🎯</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white text-sm">จำลองสถานการณ์</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">วางแผนทีม</div>
                </div>
            </a>

            <a href="{{ route('user.mlm.income-comparison') }}" class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-gray-800 shadow hover:shadow-lg transition-all">
                <span class="text-2xl">⚖️</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white text-sm">เปรียบเทียบรายได้</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">วิเคราะห์</div>
                </div>
            </a>

            <a href="{{ route('user.mlm.dividend-simulator') }}" class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-gray-800 shadow hover:shadow-lg transition-all">
                <span class="text-2xl">💎</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white text-sm">ปันผล TPIX</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Tokenomics</div>
                </div>
            </a>
        </div>

        {{-- ========== COPY REFERRAL LINK ========== --}}
        <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl p-6 mb-6 shadow-xl">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4 text-white">
                    <span class="text-4xl">🎁</span>
                    <div>
                        <h3 class="text-xl font-bold">แชร์ลิ้งค์รับรายได้ทันที!</h3>
                        <p class="text-white/80 text-sm">แนะนำเพื่อนมาสมัคร รับค่าคอมมิชชั่นตลอดชีพ</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <div class="flex-1 md:flex-none">
                        <input type="text" id="referral-link" readonly
                               value="{{ route('register', ['ref' => $member->member_code]) }}"
                               class="w-full md:w-80 px-4 py-3 rounded-xl border-0 bg-white/20 text-white placeholder-white/60 focus:ring-2 focus:ring-white text-sm font-mono">
                    </div>
                    <button onclick="copyReferralLink()" class="px-6 py-3 bg-white text-green-600 rounded-xl font-bold hover:bg-green-50 transition-all shadow-lg flex items-center justify-center gap-2">
                        <span id="copy-icon">📋</span>
                        <span id="copy-text">คัดลอกลิ้งค์</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ========== GENEALOGY VIEWER ========== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden mb-6">
            <div class="p-4 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-gray-700 dark:to-gray-600 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🌳</span>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">ผังสายงาน MLM</h2>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="viewer && viewer.resetView()" class="px-4 py-2 bg-white dark:bg-gray-700 rounded-lg text-sm font-semibold hover:shadow transition-all">
                        🔄 รีเซ็ต
                    </button>
                </div>
            </div>
            <div id="genealogy-container" class="min-h-[600px]"></div>
        </div>

        {{-- ========== QUICK TIPS ========== --}}
        <div class="bg-gradient-to-r from-purple-100 to-pink-100 dark:from-gray-800 dark:to-gray-700 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                💡 วิธีใช้งานผังสายงาน
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="text-lg">🖱️</span>
                    <span>ลากเมาส์เลื่อนดู</span>
                </div>
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="text-lg">🔍</span>
                    <span>Scroll เพื่อซูม</span>
                </div>
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="text-lg">👆</span>
                    <span>คลิกดูรายละเอียด</span>
                </div>
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="text-lg">🔎</span>
                    <span>ค้นหาสมาชิก</span>
                </div>
            </div>
        </div>

    </div>
</div>

@vite('resources/js/mlm-genealogy-premium.js')
<script>
let viewer = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize genealogy viewer
    viewer = new MlmGenealogyPremium('genealogy-container', {
        memberCode: '{{ $member->member_code }}',
        apiUrl: '{{ route("user.mlm.genealogy-data") }}',
        type: '{{ ($member->plan?->type === "binary" || $member->plan?->type === "hybrid") ? "binary" : "unilevel" }}',
        maxDepth: 5,
        showMinimap: true,
        showStats: true,
        enableSearch: true
    });
});

function copyReferralLink() {
    const input = document.getElementById('referral-link');
    const copyText = document.getElementById('copy-text');
    const copyIcon = document.getElementById('copy-icon');

    navigator.clipboard.writeText(input.value).then(() => {
        copyIcon.textContent = '✅';
        copyText.textContent = 'คัดลอกแล้ว!';

        setTimeout(() => {
            copyIcon.textContent = '📋';
            copyText.textContent = 'คัดลอกลิ้งค์';
        }, 2000);
    });
}
</script>
@endsection

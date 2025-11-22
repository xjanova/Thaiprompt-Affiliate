@extends('layouts.user-arrow-x')

@section('title', 'ผังสายงานของฉัน - MLM')

@push('styles')
<link rel="stylesheet" href="{{ asset('js/mlm-genealogy-premium.js') }}">
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Page Header -->
        <x-arrow-x.card-v3 class="p-8 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        🌳 ผังสายงานของฉัน
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-lg">
                        แสดงโครงสร้างทีมและสายงาน MLM ของคุณ
                    </p>
                </div>

                <a href="{{ route('user.mlm.dashboard') }}"
                   class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all">
                    ← กลับไปแดชบอร์ด
                </a>
            </div>
        </x-arrow-x.card-v3>

        <!-- Quick Stats Bar -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <x-arrow-x.card-v3 class="p-4 text-center">
                <div class="text-3xl mb-2">👥</div>
                <div class="text-2xl font-bold text-purple-600">{{ $member->total_team_members ?? 0 }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">สมาชิกทั้งหมด</div>
            </div>

            <x-arrow-x.card-v3 class="p-4 text-center">
                <div class="text-3xl mb-2">⭐</div>
                <div class="text-2xl font-bold text-blue-600">{{ number_format($member->total_team_pv ?? 0) }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Team PV</div>
            </div>

            <x-arrow-x.card-v3 class="p-4 text-center">
                <div class="text-3xl mb-2">⬅️</div>
                <div class="text-2xl font-bold text-green-600">{{ $member->left_leg_members ?? 0 }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Left Leg</div>
            </div>

            <x-arrow-x.card-v3 class="p-4 text-center">
                <div class="text-3xl mb-2">➡️</div>
                <div class="text-2xl font-bold text-red-600">{{ $member->right_leg_members ?? 0 }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Right Leg</div>
            </div>
        </div>

        <!-- Genealogy Viewer -->
        <div id="genealogy-container"></div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <x-arrow-x.card-v3 class="p-6">
                <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">🎯</span>
                    เป้าหมายถัดไป
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Build Left Leg:</span>
                        <span class="font-bold text-green-600">+5 members</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Build Right Leg:</span>
                        <span class="font-bold text-red-600">+3 members</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Team PV Goal:</span>
                        <span class="font-bold text-purple-600">20,000 PV</span>
                    </div>
                </div>
            </div>

            <x-arrow-x.card-v3 class="p-6">
                <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">📊</span>
                    สถิติการเติบโต
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">สัปดาห์นี้:</span>
                        <span class="font-bold text-green-600">+3 members</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">เดือนนี้:</span>
                        <span class="font-bold text-blue-600">+12 members</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Growth Rate:</span>
                        <span class="font-bold text-purple-600">+25%</span>
                    </div>
                </div>
            </div>

            <x-arrow-x.card-v3 class="p-6">
                <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">⚡</span>
                    การกระทำด่วน
                </h3>
                <div class="space-y-2">
                    <button onclick="expandAll()" class="w-full px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-semibold transition-all">
                        ขยายทั้งหมด
                    </button>
                    <button onclick="collapseAll()" class="w-full px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm font-semibold transition-all">
                        ย่อทั้งหมด
                    </button>
                    <button onclick="downloadTree()" class="w-full px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold transition-all">
                        ดาวน์โหลดผัง
                    </button>
                </div>
            </div>
        </div>

        <!-- Help Tips -->
        <div class="bg-gradient-to-r from-purple-100 to-pink-100 rounded-2xl p-8 mt-8">
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">💡 เคล็ดลับการดูผังสายงาน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                <div class="flex items-start">
                    <span class="text-2xl mr-3">🖱️</span>
                    <div>
                        <div class="font-bold mb-1">เลื่อนดูผัง</div>
                        <div>คลิกค้างและลากเมาส์เพื่อเลื่อนดู</div>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="text-2xl mr-3">🔍</span>
                    <div>
                        <div class="font-bold mb-1">ซูม</div>
                        <div>ใช้ scroll wheel หรือปุ่ม +/- เพื่อซูม</div>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="text-2xl mr-3">👆</span>
                    <div>
                        <div class="font-bold mb-1">ดูรายละเอียด</div>
                        <div>คลิกที่การ์ดสมาชิกเพื่อดูข้อมูล</div>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="text-2xl mr-3">🔎</span>
                    <div>
                        <div class="font-bold mb-1">ค้นหา</div>
                        <div>ใช้ช่องค้นหาเพื่อหาสมาชิกในทีม</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/mlm-genealogy-premium.js') }}"></script>
<script>
let viewer = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize genealogy viewer
    viewer = new MlmGenealogyPremium('genealogy-container', {
        memberCode: '{{ $member->member_code }}',
        apiUrl: '/api/user/mlm/genealogy',
        type: '{{ $member->plan->type === "binary" || $member->plan->type === "hybrid" ? "binary" : "unilevel" }}',
        maxDepth: 5,
        showMinimap: true,
        showStats: true,
        enableSearch: true
    });
});

function expandAll() {
    if (viewer) {
        alert('ฟีเจอร์นี้จะเปิดใช้งานเร็วๆ นี้');
    }
}

function collapseAll() {
    if (viewer) {
        alert('ฟีเจอร์นี้จะเปิดใช้งานเร็วๆ นี้');
    }
}

function downloadTree() {
    if (viewer) {
        alert('ฟีเจอร์นี้จะเปิดใช้งานเร็วๆ นี้');
    }
}
</script>
@endsection

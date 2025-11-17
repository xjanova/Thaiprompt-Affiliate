@extends('layouts.admin-v3')

@section('title', 'ผังสายงาน MLM')

@push('styles')
<link rel="stylesheet" href="{{ asset('js/mlm-genealogy-premium.js') }}">
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 dark:from-emerald-800 dark:via-teal-800 dark:to-cyan-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse delay-500"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-project-diagram text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">ผังสายงาน MLM</h1>
                        <p class="text-teal-100 text-lg mt-1">แสดงโครงสร้างสายงาน MLM แบบ Interactive</p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.mlm.members.index') }}"
                       class="glass-fusion hover:bg-white/20 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                        <i class="fas fa-users"></i>
                        รายชื่อสมาชิก
                    </a>
                    <a href="{{ route('admin.mlm.plans.index') }}"
                       class="glass-fusion hover:bg-white/20 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg">
                        <i class="fas fa-chart-bar"></i>
                        จัดการแผน
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Member Selector Card --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-user-circle text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">เลือกสมาชิกเพื่อดูผังสายงาน</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">เลือกสมาชิกที่ต้องการดูโครงสร้างสายงาน</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1"></i>
                    สมาชิก
                </label>
                <select id="member-selector"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all shadow-sm">
                    <option value="">-- เลือกสมาชิก --</option>
                    @foreach($members as $member)
                        <option value="{{ $member->member_code }}">
                            {{ $member->member_code }} - {{ $member->user->name ?? 'Unknown' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button id="btn-view-genealogy"
                        class="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 dark:from-emerald-500 dark:to-teal-500 dark:hover:from-emerald-600 dark:hover:to-teal-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-eye"></i>
                    แสดงผังสายงาน
                </button>
            </div>
        </div>
    </div>

    {{-- Genealogy Viewer Container --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <i class="fas fa-sitemap text-emerald-600 dark:text-emerald-400"></i>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">โครงสร้างสายงาน</h3>
            </div>
        </div>
        <div id="genealogy-container" class="min-h-[600px] bg-gray-50 dark:bg-gray-900/50"></div>
    </div>

    {{-- Help Section --}}
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-lightbulb text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">วิธีใช้งานผังสายงาน</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">คู่มือการใช้งานฟีเจอร์ต่างๆ</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Instruction Card 1 --}}
            <div class="group bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-700 hover:border-purple-400 dark:hover:border-purple-500 transition-all transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-mouse-pointer text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">เลื่อนดูผัง</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    คลิกค้างและลากเมาส์เพื่อเลื่อนดูผังสายงานในทิศทางต่างๆ
                </p>
            </div>

            {{-- Instruction Card 2 --}}
            <div class="group bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-search-plus text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ซูมเข้า-ออก</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    ใช้ scroll wheel หรือปุ่ม +/- ด้านขวาบนเพื่อซูมดูรายละเอียด
                </p>
            </div>

            {{-- Instruction Card 3 --}}
            <div class="group bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-700 hover:border-green-400 dark:hover:border-green-500 transition-all transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-hand-pointer text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">คลิกดูรายละเอียด</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    คลิกที่การ์ดสมาชิกเพื่อดูข้อมูลเพิ่มเติมและสถิติ
                </p>
            </div>

            {{-- Instruction Card 4 --}}
            <div class="group bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 rounded-xl p-6 border-2 border-pink-200 dark:border-pink-700 hover:border-pink-400 dark:hover:border-pink-500 transition-all transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-search text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ค้นหาสมาชิก</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    ใช้ช่องค้นหาด้านบนเพื่อค้นหาสมาชิกในผังได้อย่างรวดเร็ว
                </p>
            </div>

            {{-- Instruction Card 5 --}}
            <div class="group bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-6 border-2 border-yellow-200 dark:border-yellow-700 hover:border-yellow-400 dark:hover:border-yellow-500 transition-all transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-sync-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">สลับประเภท</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    สลับระหว่างผัง Binary และ Unilevel ได้ตลอดเวลา
                </p>
            </div>

            {{-- Instruction Card 6 --}}
            <div class="group bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-xl p-6 border-2 border-indigo-200 dark:border-indigo-700 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ดูสถิติ</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    สถิติแสดงอยู่ที่มุมล่างซ้าย พร้อม mini-map มุมล่างขวา
                </p>
            </div>
        </div>
    </div>

    {{-- Feature Highlights --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Binary System --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-emerald-500 dark:border-emerald-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-code-branch text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Binary System</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                โครงสร้าง 2 ขา (Left/Right) สำหรับการคำนวณคอมมิชชั่นแบบ Binary
            </p>
        </div>

        {{-- Unilevel System --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-teal-500 dark:border-teal-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-layer-group text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Unilevel System</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                รองรับสูงสุด 10 ระดับสำหรับการคำนวณคอมมิชชั่นแบบ Unilevel
            </p>
        </div>

        {{-- Real-time Data --}}
        <div class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg border-l-4 border-cyan-500 dark:border-cyan-400 transform hover:scale-105 transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-bolt text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Real-time Data</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ข้อมูลอัพเดทแบบเรียลไทม์ แสดงสถานะล่าสุดของสายงาน
            </p>
        </div>
    </div>
</div>

<script src="{{ asset('js/mlm-genealogy-premium.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let viewer = null;

    document.getElementById('btn-view-genealogy').addEventListener('click', function() {
        const memberCode = document.getElementById('member-selector').value;

        if (!memberCode) {
            alert('กรุณาเลือกสมาชิก');
            return;
        }

        // Initialize or update viewer
        if (!viewer) {
            viewer = new MlmGenealogyPremium('genealogy-container', {
                memberCode: memberCode,
                apiUrl: `/api/admin/mlm/genealogy/${memberCode}`,
                type: 'binary',
                maxDepth: 5,
                showMinimap: true,
                showStats: true,
                enableSearch: true
            });
        } else {
            // Update existing viewer
            viewer.options.memberCode = memberCode;
            viewer.options.apiUrl = `/api/admin/mlm/genealogy/${memberCode}`;
            viewer.loadData().then(() => viewer.render());
        }
    });

    // Auto-load first member if exists
    const firstOption = document.querySelector('#member-selector option:nth-child(2)');
    if (firstOption) {
        document.getElementById('member-selector').value = firstOption.value;
        document.getElementById('btn-view-genealogy').click();
    }
});
</script>

<style>
/* Theme Customizer CSS Variables Integration */
.glass-fusion {
    background: rgba(255, 255, 255, var(--glass-opacity, 0.15)) !important;
    backdrop-filter: blur(var(--glass-blur, 12px)) saturate(var(--backdrop-saturate, 100%)) !important;
    border-radius: var(--card-roundness, 16px) !important;
}

.dark .glass-fusion {
    background: rgba(31, 41, 55, var(--glass-opacity, 0.15)) !important;
}

/* Card roundness */
.rounded-2xl {
    border-radius: var(--card-roundness, 16px) !important;
}

.rounded-xl {
    border-radius: calc(var(--button-roundness, 12px) * 1.33) !important;
}

.rounded-lg {
    border-radius: var(--button-roundness, 12px) !important;
}

/* Hover scale effect */
.hover\:scale-105:hover {
    transform: scale(var(--hover-scale, 1.05)) !important;
}

/* Animation speed */
.transition-all,
.transition {
    transition-duration: var(--animation-speed, 500ms) !important;
}

/* Shadow intensity */
.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.5)) !important;
}

.shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.2)),
                0 10px 10px -5px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.08)) !important;
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.2)),
                0 4px 6px -2px rgba(0, 0, 0, calc(var(--shadow-intensity, 0.5) * 0.1)) !important;
}
</style>
@endsection

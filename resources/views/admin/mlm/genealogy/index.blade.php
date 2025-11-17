@extends('layouts.admin-v3')

@section('title', 'ผังสายงาน MLM - Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('js/mlm-genealogy-premium.js') }}">
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Page Header -->
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8" border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent flex items-center gap-3">
                        <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400"></i>
                        ผังสายงาน MLM
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-2 text-lg">
                        แสดงโครงสร้างสายงาน MLM แบบ Interactive Google Style
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.mlm.members.index') }}"
                       class="px-6 py-3 bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-xl font-semibold transition-all flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        รายชื่อสมาชิก
                    </a>
                    <a href="{{ route('admin.mlm.plans.index') }}"
                       class="px-6 py-3 bg-purple-500 hover:bg-purple-600 dark:bg-purple-600 dark:hover:bg-purple-700 text-white rounded-xl font-semibold transition-all flex items-center gap-2">
                        <i class="fas fa-chart-bar"></i>
                        จัดการแผน
                    </a>
                </div>
            </div>
        </div>

        <!-- Member Selector -->
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center gap-4">
                <label class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">
                    เลือกดูสายงานของ:
                </label>
                <select id="member-selector" class="flex-1 px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">-- เลือกสมาชิก --</option>
                    @foreach($members as $member)
                        <option value="{{ $member->member_code }}">
                            {{ $member->member_code }} - {{ $member->user->name ?? 'Unknown' }}
                        </option>
                    @endforeach
                </select>
                <button id="btn-view-genealogy" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold shadow-lg transition-all">
                    แสดงผัง
                </button>
            </div>
        </div>

        <!-- Genealogy Viewer -->
        <div id="genealogy-container"></div>

        <!-- Help Section -->
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8 mt-8" border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-lightbulb text-yellow-500"></i>
                วิธีใช้งาน
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="p-6 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                    <div class="text-4xl mb-3 text-purple-600 dark:text-purple-400">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2">เลื่อนดูผัง</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        คลิกค้างและลากเมาส์เพื่อเลื่อนดูผังสายงาน
                    </p>
                </div>

                <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="text-4xl mb-3 text-blue-600 dark:text-blue-400">
                        <i class="fas fa-search-plus"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2">ซูมเข้า-ออก</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        ใช้ scroll wheel หรือปุ่ม +/- ด้านขวาบนเพื่อซูม
                    </p>
                </div>

                <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <div class="text-4xl mb-3 text-green-600 dark:text-green-400">
                        <i class="fas fa-hand-pointer"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2">คลิกดูรายละเอียด</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        คลิกที่การ์ดสมาชิกเพื่อดูข้อมูลเพิ่มเติม
                    </p>
                </div>

                <div class="p-6 bg-pink-50 dark:bg-pink-900/20 rounded-xl border border-pink-200 dark:border-pink-800">
                    <div class="text-4xl mb-3 text-pink-600 dark:text-pink-400">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2">ค้นหาสมาชิก</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        ใช้ช่องค้นหาด้านบนเพื่อค้นหาสมาชิกในผัง
                    </p>
                </div>

                <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                    <div class="text-4xl mb-3 text-yellow-600 dark:text-yellow-400">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2">สลับประเภท</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        สลับระหว่างผัง Binary และ Unilevel ได้ตลอดเวลา
                    </p>
                </div>

                <div class="p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                    <div class="text-4xl mb-3 text-indigo-600 dark:text-indigo-400">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-2">ดูสถิติ</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        สถิติแสดงอยู่ที่มุมล่างซ้าย พร้อม mini-map มุมล่างขวา
                    </p>
                </div>
            </div>
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
@endsection

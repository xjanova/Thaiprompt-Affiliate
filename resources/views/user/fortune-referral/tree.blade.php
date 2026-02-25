{{-- resources/views/user/fortune-referral/tree.blade.php --}}
{{--
    ผังสายงานดูดวง - User
    แสดงโครงสร้างการแนะนำเพื่อนดูดวง (L1/L2) ของ user ปัจจุบัน
    ใช้ OrgChartViewer + Fortune commission data overlay

    @author TP-Affiliate Team
    @version 3.0.0
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ผังสายงานดูดวง')

@push('styles')
<style>
    /* กำหนดความสูงขั้นต่ำสำหรับ chart container */
    .org-chart-container {
        height: calc(100vh - 500px);
        min-height: 450px;
        max-height: 650px;
    }

    @media (max-width: 768px) {
        .org-chart-container {
            height: calc(100vh - 400px);
            min-height: 350px;
        }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50 dark:from-gray-900 dark:via-purple-900/20 dark:to-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Hero Header --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-violet-600 p-6 sm:p-8 mb-8 shadow-xl">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-300/20 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-3xl">🔮</span>
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">ผังสายงานดูดวง</h1>
                        </div>
                        <p class="text-purple-100 text-sm sm:text-base">
                            ดูโครงสร้างสายงานการแนะนำดูดวงของคุณ — Level 1 (สายตรง) และ Level 2 (ชั้นหลาน)
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('user.fortune-referral.commissions') }}"
                           class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                            <span>💰</span> คอมมิชชั่น
                        </a>
                        <a href="{{ route('user.fortune-referral.recruit') }}"
                           class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                            <span>📢</span> ชวนเพื่อน
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- รายได้รวม --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-purple-100 dark:border-purple-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">💰</span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">รายได้รวม</span>
                </div>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    ฿{{ number_format($stats['total'], 2) }}
                </p>
            </div>

            {{-- L1 สายตรง --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-blue-100 dark:border-blue-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">L1</span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สายตรง</span>
                </div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    ฿{{ number_format($stats['level1'], 2) }}
                </p>
            </div>

            {{-- L2 ชั้นหลาน --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-indigo-100 dark:border-indigo-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">L2</span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ชั้นหลาน</span>
                </div>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    ฿{{ number_format($stats['level2'], 2) }}
                </p>
            </div>

            {{-- จำนวนผู้แนะนำ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-green-100 dark:border-green-900/50">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">👥</span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ผู้แนะนำ</span>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($stats['referral_count']) }} คน
                </p>
            </div>
        </div>

        @if($currentMember)
            {{-- Controls --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-sitemap text-white text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">สายงานของฉัน</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                รหัส: {{ $currentMember->member_code ?? '-' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">ความลึก</label>
                            <select id="depth-selector"
                                    class="px-3 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm text-sm">
                                <option value="3">3 ระดับ</option>
                                <option value="5" selected>5 ระดับ</option>
                                <option value="7">7 ระดับ</option>
                                <option value="10">10 ระดับ</option>
                            </select>
                        </div>
                        <button id="btn-reload-tree"
                                class="mt-5 px-5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center gap-2 text-sm">
                            <i class="fas fa-sync-alt"></i>
                            รีเฟรช
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tree Viewer Container --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-project-diagram text-purple-600 dark:text-purple-400"></i>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">โครงสร้างสายงาน</h3>
                    </div>
                </div>
                <div class="org-chart-container">
                    <div id="fortune-tree-container" class="w-full h-full"></div>
                </div>
            </div>

        @else
            {{-- ยังไม่มี MLM Member --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center border border-gray-200 dark:border-gray-700 mb-6">
                <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center">
                    <span class="text-4xl">🔮</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีข้อมูลสายงาน</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    คุณยังไม่ได้เป็นสมาชิกในระบบ MLM เริ่มชวนเพื่อนมาดูดวงเพื่อสร้างสายงานของคุณ!
                </p>
                <a href="{{ route('user.fortune-referral.recruit') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg transition-all">
                    <span>📢</span> ชวนเพื่อนดูดวง
                </a>
            </div>
        @endif

        {{-- Legend + วิธีใช้งาน --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Legend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-lg">📋</span>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">คำอธิบายสัญลักษณ์</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">L1</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300">ชั้น 1 (สายตรง) — เพื่อนที่คุณแนะนำโดยตรง</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">L2</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300">ชั้น 2 (ชั้นหลาน) — เพื่อนของเพื่อนที่คุณแนะนำ</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-4 h-4 rounded-full bg-green-500"></span>
                        <span class="text-sm text-gray-700 dark:text-gray-300">สมาชิกที่ยังใช้งานอยู่</span>
                    </div>
                </div>
            </div>

            {{-- วิธีใช้งาน --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-lg">💡</span>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">วิธีใช้งาน</h3>
                </div>
                <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <p>📱 <strong>มือถือ:</strong> ลากนิ้วเพื่อเลื่อน, บีบนิ้วเพื่อซูม</p>
                    <p>🖥️ <strong>คอมพิวเตอร์:</strong> คลิกค้างลากเพื่อเลื่อน, Scroll wheel เพื่อซูม</p>
                    <p>👆 <strong>คลิก/แตะ</strong> ที่การ์ดสมาชิกเพื่อดูข้อมูลเพิ่มเติม</p>
                    <p>🔄 กดปุ่ม <strong>"รีเฟรช"</strong> เพื่อโหลดข้อมูลล่าสุด</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($currentMember)
@push('scripts')
<script src="{{ asset('assets/js/org-chart-viewer.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let viewer = null;
    const container = document.getElementById('fortune-tree-container');
    const depthSelector = document.getElementById('depth-selector');
    const btnReload = document.getElementById('btn-reload-tree');

    /**
     * โหลดและแสดงผังสายงานดูดวง
     */
    async function loadFortuneTree() {
        const depth = depthSelector.value;

        // สร้าง viewer ถ้ายังไม่มี
        if (!viewer) {
            viewer = new OrgChartViewer(container, {
                treeType: 'unilevel',
                maxDepth: parseInt(depth),
                nodeWidth: window.innerWidth < 768 ? 160 : 200,
                nodeHeight: window.innerWidth < 768 ? 110 : 120,
                horizontalSpacing: window.innerWidth < 768 ? 20 : 40,
                verticalSpacing: window.innerWidth < 768 ? 80 : 100
            });
        } else {
            viewer.options.maxDepth = parseInt(depth);
            viewer.showLoading();
        }

        // Fetch tree data จาก API
        try {
            const response = await fetch(`{{ route('user.fortune-referral.tree-data') }}?depth=${depth}`);
            const result = await response.json();

            if (result.success && result.data) {
                viewer.setData(result.data);
            } else {
                if (viewer) viewer.hideLoading();
                // แสดง empty state ในคอนเทนเนอร์
                container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400"><div class="text-center"><span class="text-4xl mb-4 block">🔮</span><p class="text-lg font-semibold">ยังไม่มีข้อมูลสายงาน</p><p class="text-sm mt-2">เริ่มชวนเพื่อนมาดูดวงเพื่อสร้างสายงาน!</p></div></div>';
            }
        } catch (error) {
            console.error('Error loading fortune tree:', error);
            if (viewer) viewer.hideLoading();
            container.innerHTML = '<div class="flex items-center justify-center h-full text-red-500"><div class="text-center"><span class="text-4xl mb-4 block">⚠️</span><p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p></div></div>';
        }
    }

    // Event Listeners
    btnReload.addEventListener('click', loadFortuneTree);

    depthSelector.addEventListener('change', function() {
        if (viewer) {
            loadFortuneTree();
        }
    });

    // Auto-load on page load
    loadFortuneTree();

    // Responsive handling
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (viewer) {
                viewer.options.nodeWidth = window.innerWidth < 768 ? 160 : 200;
                viewer.options.nodeHeight = window.innerWidth < 768 ? 110 : 120;
                viewer.options.horizontalSpacing = window.innerWidth < 768 ? 20 : 40;
                viewer.options.verticalSpacing = window.innerWidth < 768 ? 80 : 100;

                if (viewer.data) {
                    viewer.nodeCount = 0;
                    viewer.maxDepthReached = 0;
                    viewer.render();
                    viewer.fitToScreen();
                }
            }
        }, 250);
    });
});
</script>
@endpush
@endif

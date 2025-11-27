{{--
    Bloodline Viewer - Admin (Classic Mode)
    แสดงเส้นทางสายเลือดจาก Root ลงมาถึงสมาชิกที่เลือก

    @author TP-Affiliate Team
    @version 3.0.0
--}}

@extends('layouts.admin-v3')

@section('title', 'ผังสายเลือด MLM')

@push('styles')
<style>
    /* กำหนดความสูงขั้นต่ำสำหรับ chart container */
    .bloodline-chart-container {
        height: calc(100vh - 450px);
        min-height: 500px;
        max-height: 700px;
    }

    @media (max-width: 768px) {
        .bloodline-chart-container {
            height: calc(100vh - 350px);
            min-height: 400px;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-rose-600 via-pink-600 to-fuchsia-600 dark:from-rose-800 dark:via-pink-800 dark:to-fuchsia-800 rounded-2xl shadow-2xl p-6 md:p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s;"></div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 backdrop-blur-sm p-4 rounded-2xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">ผังสายเลือด MLM</h1>
                        <p class="text-pink-100 text-base md:text-lg mt-1">แสดงเส้นทางจาก Root ลงมาถึงสมาชิกที่เลือก</p>
                    </div>
                </div>

                {{-- View Mode Toggle --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- View Mode Toggle --}}
                    <div class="flex items-center gap-1 bg-white/10 backdrop-blur-sm rounded-xl p-1">
                        <span class="px-4 py-2 bg-white/20 text-white rounded-lg font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                            Classic
                        </span>
                        <a href="{{ route('admin.mlm.genealogy.bloodline.workflow') }}"
                           class="px-4 py-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg font-medium text-sm flex items-center gap-2 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            Workflow
                        </a>
                    </div>

                    <div class="h-6 w-px bg-white/20"></div>

                    <a href="{{ route('admin.mlm.genealogy.index') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-5 py-2.5 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        ผังสายงาน
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Member Selector Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">เลือกสมาชิกเพื่อดูสายเลือด</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">เลือกสมาชิกเพื่อดูเส้นทางจาก Root ลงมาถึงสมาชิก</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    สมาชิก
                </label>
                <select id="member-selector"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all shadow-sm">
                    <option value="">-- เลือกสมาชิก --</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" data-code="{{ $member->member_code }}">
                            {{ $member->member_code }} - {{ $member->user->name ?? 'Unknown' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภทผัง</label>
                    <select id="tree-type-selector"
                            class="px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all shadow-sm min-w-[140px]">
                        <option value="binary">Binary (2 ขา)</option>
                        <option value="unilevel" selected>Unilevel (ไม่จำกัด)</option>
                    </select>
                </div>
                <button id="btn-view-bloodline"
                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2 min-w-[160px]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    แสดงสายเลือด
                </button>
            </div>
        </div>
    </div>

    {{-- Bloodline Viewer Container --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">เส้นทางสายเลือด</h3>
                </div>
                <div id="current-member-info" class="text-sm text-gray-600 dark:text-gray-400 hidden">
                    <span id="current-member-name"></span>
                </div>
            </div>
        </div>
        <div class="bloodline-chart-container">
            <div id="bloodline-container" class="w-full h-full"></div>
        </div>
    </div>

    {{-- Info Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 md:p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">ผังสายเลือด คืออะไร?</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">ทำความเข้าใจเกี่ยวกับผังสายเลือด</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gradient-to-br from-rose-50 to-pink-100 dark:from-rose-900/20 dark:to-pink-800/20 rounded-xl p-5 border-2 border-rose-200 dark:border-rose-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <span class="text-white text-lg">🌳</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">สายเลือด (Bloodline)</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300">แสดงเส้นทางจาก Root (สมาชิกแรกสุด) ลงมาถึงสมาชิกที่เลือก ทำให้เห็นว่าสมาชิกมาจากสายใด ใครเป็นคนแนะนำต่อๆ กันมา</p>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-900/20 dark:to-violet-800/20 rounded-xl p-5 border-2 border-purple-200 dark:border-purple-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                        <span class="text-white text-lg">👥</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ผังสายงาน (Genealogy)</h3>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300">แสดงทีมงาน (Downline) ของสมาชิกที่เลือก ทำให้เห็นว่าสมาชิกมีทีมงานใครบ้าง มีกี่ระดับ</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/org-chart-viewer.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let viewer = null;
    const container = document.getElementById('bloodline-container');
    const memberSelector = document.getElementById('member-selector');
    const treeTypeSelector = document.getElementById('tree-type-selector');
    const btnView = document.getElementById('btn-view-bloodline');
    const currentMemberInfo = document.getElementById('current-member-info');
    const currentMemberName = document.getElementById('current-member-name');

    /**
     * โหลดและแสดงสายเลือด
     */
    async function loadBloodline() {
        const memberId = memberSelector.value;
        const treeType = treeTypeSelector.value;

        if (!memberId) {
            alert('กรุณาเลือกสมาชิก');
            return;
        }

        // แสดงชื่อสมาชิกที่เลือก
        const selectedOption = memberSelector.options[memberSelector.selectedIndex];
        const treeTypeText = treeType === 'binary' ? 'Binary' : 'Unilevel';
        currentMemberName.textContent = `${selectedOption.text} (${treeTypeText})`;
        currentMemberInfo.classList.remove('hidden');

        // สร้าง viewer ถ้ายังไม่มี
        if (!viewer) {
            viewer = new OrgChartViewer(container, {
                treeType: treeType,
                maxDepth: 50, // ไม่จำกัดความลึกสำหรับ bloodline
                nodeWidth: window.innerWidth < 768 ? 160 : 200,
                nodeHeight: window.innerWidth < 768 ? 110 : 120,
                horizontalSpacing: window.innerWidth < 768 ? 20 : 40,
                verticalSpacing: window.innerWidth < 768 ? 80 : 100
            });
        } else {
            viewer.options.treeType = treeType;
            viewer.showLoading();
        }

        // Fetch bloodline data จาก API
        try {
            const response = await fetch(`/admin/mlm/members/${memberId}/bloodline-data?type=${treeType}`);
            const result = await response.json();

            if (result.success && result.data) {
                viewer.setData(result.data);
            } else {
                viewer.hideLoading();
                alert('ไม่พบข้อมูลสายเลือด');
            }
        } catch (error) {
            console.error('Error loading bloodline:', error);
            viewer.hideLoading();
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    // Event Listeners
    btnView.addEventListener('click', loadBloodline);

    treeTypeSelector.addEventListener('change', function() {
        if (viewer && memberSelector.value) {
            loadBloodline();
        }
    });

    // Auto-load first member if available
    const firstMember = memberSelector.querySelector('option:nth-child(2)');
    if (firstMember) {
        memberSelector.value = firstMember.value;
        loadBloodline();
    }

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

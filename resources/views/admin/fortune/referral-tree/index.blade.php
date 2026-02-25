{{--
    ผังสายงานดูดวง - Admin
    แสดงโครงสร้างการแนะนำเพื่อนดูดวง (L1/L2) แบบ Interactive
    ใช้ OrgChartViewer + Fortune commission data overlay

    @author TP-Affiliate Team
    @version 3.0.0
--}}

@extends('layouts.admin-v3')

@section('title', 'ผังสายงานดูดวง')

@push('styles')
<style>
    /* กำหนดความสูงขั้นต่ำสำหรับ chart container */
    .org-chart-container {
        height: calc(100vh - 450px);
        min-height: 500px;
        max-height: 700px;
    }

    @media (max-width: 768px) {
        .org-chart-container {
            height: calc(100vh - 350px);
            min-height: 400px;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header - Purple/Indigo Gradient (ธีมดูดวง) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-indigo-600 to-violet-600 dark:from-purple-800 dark:via-indigo-800 dark:to-violet-800 rounded-2xl shadow-2xl p-6 md:p-8">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">ผังสายงานดูดวง</h1>
                        <p class="text-purple-100 text-base md:text-lg mt-1">แสดงโครงสร้างการแนะนำเพื่อนดูดวง L1/L2 แบบ Interactive</p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.fortune.commissions.index') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-5 py-2.5 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg text-sm">
                        <i class="fas fa-hand-holding-usd w-4"></i>
                        ภาพรวมคอมมิชชั่น
                    </a>
                    <a href="{{ route('admin.fortune.commissions.manage') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-5 py-2.5 rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg text-sm">
                        <i class="fas fa-tasks w-4"></i>
                        จัดการคอมมิชชั่น
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Member Selector Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">เลือกสมาชิกเพื่อดูผังสายงานดูดวง</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">เลือกสมาชิกที่ต้องการดูโครงสร้างสายงานการแนะนำดูดวง</p>
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
                        class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm">
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
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ความลึก</label>
                    <select id="depth-selector"
                            class="px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm min-w-[120px]">
                        <option value="3">3 ระดับ</option>
                        <option value="5" selected>5 ระดับ</option>
                        <option value="7">7 ระดับ</option>
                        <option value="10">10 ระดับ</option>
                    </select>
                </div>
                <button id="btn-view-tree"
                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2 min-w-[160px]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    แสดงผังสายงาน
                </button>
            </div>
        </div>
    </div>

    {{-- Tree Viewer Container --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">โครงสร้างสายงานดูดวง</h3>
                </div>
                <div id="current-member-info" class="text-sm text-gray-600 dark:text-gray-400 hidden">
                    กำลังดู: <span id="current-member-name" class="font-semibold text-purple-600 dark:text-purple-400"></span>
                </div>
            </div>
        </div>
        <div class="org-chart-container">
            <div id="fortune-tree-container" class="w-full h-full"></div>
        </div>
    </div>

    {{-- Legend + Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Legend --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-info-circle text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">คำอธิบายสัญลักษณ์</h3>
            </div>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">L1</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ชั้นที่ 1 (สายตรง) — คอมมิชชั่นจากผู้แนะนำโดยตรง</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">L2</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ชั้นที่ 2 (ชั้นหลาน) — คอมมิชชั่นจากผู้แนะนำของผู้แนะนำ</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                        <i class="fas fa-check-circle mr-1"></i> active
                    </span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">สมาชิกที่ยังใช้งานอยู่</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                        <i class="fas fa-times-circle mr-1"></i> inactive
                    </span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">สมาชิกที่ไม่ใช้งาน</span>
                </div>
            </div>
        </div>

        {{-- วิธีใช้งาน --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-lightbulb text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">วิธีใช้งาน</h3>
            </div>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">1</span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">เลือกสมาชิกที่ต้องการดูผังจาก dropdown ด้านบน</p>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">2</span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">กดปุ่ม "แสดงผังสายงาน" เพื่อแสดงโครงสร้าง</p>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">3</span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>เลื่อนดู:</strong> คลิกค้างลาก (Desktop) / ลากนิ้ว (Mobile)</p>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">4</span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>ซูม:</strong> Scroll wheel (Desktop) / บีบนิ้ว (Mobile)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature Highlights --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-purple-500 dark:border-purple-400 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-sitemap text-white"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">ระบบ L1/L2</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">โครงสร้าง 2 ชั้น — L1 สายตรง + L2 ชั้นหลาน สำหรับคอมมิชชั่นดูดวง</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-indigo-500 dark:border-indigo-400 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-bar text-white"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">ข้อมูลรายได้</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">แสดงจำนวนคอมมิชชั่นและรายได้รวมในแต่ละ node ของสายงาน</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-violet-500 dark:border-violet-400 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-violet-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-mobile-alt text-white"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Touch-Friendly</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">รองรับการใช้งานบนมือถือ ลากเลื่อนและซูมด้วยนิ้วได้</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/org-chart-viewer.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let viewer = null;
    const container = document.getElementById('fortune-tree-container');
    const memberSelector = document.getElementById('member-selector');
    const depthSelector = document.getElementById('depth-selector');
    const btnView = document.getElementById('btn-view-tree');
    const currentMemberInfo = document.getElementById('current-member-info');
    const currentMemberName = document.getElementById('current-member-name');

    /**
     * โหลดและแสดงผังสายงานดูดวง
     */
    async function loadFortuneTree() {
        const memberId = memberSelector.value;
        const depth = depthSelector.value;

        if (!memberId) {
            alert('กรุณาเลือกสมาชิก');
            return;
        }

        // แสดงชื่อสมาชิกที่เลือก
        const selectedOption = memberSelector.options[memberSelector.selectedIndex];
        currentMemberName.textContent = selectedOption.text;
        currentMemberInfo.classList.remove('hidden');

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
            const response = await fetch(`/admin/fortune/referral-tree/${memberId}/tree-data?depth=${depth}`);
            const result = await response.json();

            if (result.success && result.data) {
                viewer.setData(result.data);
            } else {
                viewer.hideLoading();
                alert('ไม่พบข้อมูลผังสายงาน');
            }
        } catch (error) {
            console.error('Error loading fortune tree:', error);
            if (viewer) viewer.hideLoading();
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    // Event Listeners
    btnView.addEventListener('click', loadFortuneTree);

    // เปลี่ยน depth ให้โหลดใหม่
    depthSelector.addEventListener('change', function() {
        if (viewer && memberSelector.value) {
            loadFortuneTree();
        }
    });

    // Auto-load first member if available
    const firstMember = memberSelector.querySelector('option:nth-child(2)');
    if (firstMember) {
        memberSelector.value = firstMember.value;
        loadFortuneTree();
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

{{--
    Bloodline Viewer - Admin (Workflow Mode / n8n Style)
    แสดงเส้นทางสายเลือดจาก Root ลงมาถึงสมาชิกที่เลือก
    รองรับการลาก nodes ได้อิสระ

    @author TP-Affiliate Team
    @version 3.0.0
--}}

@extends('layouts.admin-v3')

@section('title', 'ผังสายเลือด MLM - Workflow')

@push('styles')
<style>
    /* กำหนดความสูงขั้นต่ำสำหรับ workflow container */
    .workflow-container {
        height: calc(100vh - 400px);
        min-height: 500px;
        max-height: 800px;
    }

    @media (max-width: 768px) {
        .workflow-container {
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">ผังสายเลือด - Workflow</h1>
                        <p class="text-pink-100 text-base md:text-lg mt-1">ลากโหนดได้อิสระ แบบ n8n Style</p>
                    </div>
                </div>

                {{-- View Mode Toggle --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- View Mode Toggle --}}
                    <div class="flex items-center gap-1 bg-white/10 backdrop-blur-sm rounded-xl p-1">
                        <a href="{{ route('admin.mlm.genealogy.bloodline') }}"
                           class="px-4 py-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg font-medium text-sm flex items-center gap-2 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                            Classic
                        </a>
                        <span class="px-4 py-2 bg-white/20 text-white rounded-lg font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            Workflow
                        </span>
                    </div>

                    <div class="h-6 w-px bg-white/20"></div>

                    <a href="{{ route('admin.mlm.genealogy.workflow') }}"
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
                <p class="text-sm text-gray-600 dark:text-gray-400">ลาก nodes ได้อิสระ, คลิกดูรายละเอียด</p>
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

                <div class="flex gap-2">
                    <button id="btn-view-bloodline"
                            class="px-6 py-3 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        แสดงสายเลือด
                    </button>

                    <button id="btn-export"
                            class="p-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 hover:border-rose-500 dark:hover:border-rose-500 text-gray-700 dark:text-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all"
                            title="Export JSON">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Workflow Diagram Container --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">เส้นทางสายเลือด - Workflow</h3>
                </div>
                <div id="current-member-info" class="text-sm text-gray-600 dark:text-gray-400 hidden">
                    <span id="current-member-name"></span>
                </div>
            </div>
        </div>
        <div class="workflow-container">
            <div id="workflow-diagram" class="w-full h-full"></div>
        </div>
    </div>

    {{-- Tips Section --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-rose-500 dark:border-rose-400 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-pink-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">ลากการ์ด</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">คลิกที่การ์ดแล้วลากเพื่อจัดตำแหน่ง node ได้อิสระ</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-pink-500 dark:border-pink-400 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-fuchsia-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">คลิกดูข้อมูล</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">คลิกที่การ์ดเพื่อดูข้อมูลสมาชิกโดยละเอียด</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-fuchsia-500 dark:border-fuchsia-400 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-fuchsia-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">ซูมเข้า-ออก</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">ใช้ scroll wheel หรือ pinch ในมือถือเพื่อซูมดูรายละเอียด</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- โหลด WorkflowDiagram script --}}
<script src="{{ asset('assets/js/workflow-diagram.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let diagram = null;
    const container = document.getElementById('workflow-diagram');
    const memberSelector = document.getElementById('member-selector');
    const treeTypeSelector = document.getElementById('tree-type-selector');
    const btnView = document.getElementById('btn-view-bloodline');
    const btnExport = document.getElementById('btn-export');
    const currentMemberInfo = document.getElementById('current-member-info');
    const currentMemberName = document.getElementById('current-member-name');

    /**
     * Initialize Workflow Diagram
     */
    function initDiagram() {
        if (diagram) return diagram;

        // ตรวจสอบว่า class พร้อมใช้งาน
        if (typeof WorkflowDiagram === 'undefined') {
            console.error('WorkflowDiagram class not loaded');
            container.innerHTML = `
                <div class="flex items-center justify-center h-full text-red-500">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg font-semibold">ไม่สามารถโหลด Workflow Diagram ได้</p>
                        <p class="text-sm mt-2">กรุณารีเฟรชหน้าเว็บ</p>
                    </div>
                </div>
            `;
            return null;
        }

        diagram = new WorkflowDiagram(container, {
            editable: true,
            snapToGrid: true,
            showGrid: true,
            gridSize: 20,
            nodeWidth: window.innerWidth < 768 ? 180 : 200,
            nodeHeight: window.innerWidth < 768 ? 70 : 80,
            animateConnections: true,

            onNodeClick: function(node) {
                console.log('Node clicked:', node);
            }
        });

        return diagram;
    }

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

        // สร้าง diagram ถ้ายังไม่มี
        if (!diagram) {
            diagram = initDiagram();
            if (!diagram) return;
        }

        diagram.showLoading();

        // Fetch bloodline data จาก API
        try {
            const response = await fetch(`/admin/mlm/members/${memberId}/bloodline-data?type=${treeType}`);
            const result = await response.json();

            if (result.success && result.data) {
                // โหลดเป็น tree data
                diagram.loadTreeData(result.data, {
                    horizontalSpacing: window.innerWidth < 768 ? 200 : 250,
                    verticalSpacing: window.innerWidth < 768 ? 120 : 150
                });
            } else {
                diagram.hideLoading();
                alert('ไม่พบข้อมูลสายเลือด');
            }
        } catch (error) {
            console.error('Error loading bloodline:', error);
            diagram.hideLoading();
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    /**
     * Export data เป็น JSON
     */
    function exportData() {
        if (!diagram) {
            alert('กรุณาโหลดสายเลือดก่อน');
            return;
        }

        const data = diagram.getData();
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `bloodline-${new Date().toISOString().split('T')[0]}.json`;
        a.click();

        URL.revokeObjectURL(url);
    }

    // Event Listeners
    btnView.addEventListener('click', loadBloodline);
    btnExport.addEventListener('click', exportData);

    treeTypeSelector.addEventListener('change', function() {
        if (diagram && memberSelector.value) {
            loadBloodline();
        }
    });

    // Initialize diagram on load
    initDiagram();

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
            if (diagram) {
                diagram.options.nodeWidth = window.innerWidth < 768 ? 180 : 200;
                diagram.options.nodeHeight = window.innerWidth < 768 ? 70 : 80;

                if (diagram.nodes.size > 0) {
                    diagram.fitToView();
                }
            }
        }, 250);
    });
});
</script>
@endpush

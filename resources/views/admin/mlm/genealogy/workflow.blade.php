{{--
    Workflow-style Genealogy Viewer - Admin
    แสดงผังสายงาน MLM แบบ n8n Style
    รองรับการลาก nodes และเส้นเชื่อมต่อได้อิสระ

    @author TP-Affiliate Team
    @version 1.0.0
--}}

@extends('layouts.admin-v3')

@section('title', 'ผังสายงาน MLM - Workflow Mode')

@push('styles')
<style>
    /* กำหนดความสูงสำหรับ diagram container */
    .workflow-container {
        height: calc(100vh - 380px);
        min-height: 550px;
        max-height: 800px;
    }

    @media (max-width: 768px) {
        .workflow-container {
            height: calc(100vh - 300px);
            min-height: 450px;
        }
    }

    /* Toggle Switch Styles */
    .view-toggle-btn {
        @apply relative inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all;
    }

    .view-toggle-btn.active {
        @apply bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg;
    }

    .view-toggle-btn:not(.active) {
        @apply bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-800 dark:via-indigo-800 dark:to-blue-800 rounded-2xl shadow-2xl p-6 md:p-8">
        {{-- Animated Background --}}
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
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">ผังสายงาน Workflow Mode</h1>
                        <p class="text-purple-100 text-base md:text-lg mt-1">แบบ n8n - ลาก nodes และเส้นเชื่อมต่อได้อิสระ</p>
                    </div>
                </div>

                {{-- View Mode Toggle --}}
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl p-1">
                        <a href="{{ route('admin.mlm.genealogy.index') }}"
                           class="view-toggle-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                            Classic
                        </a>
                        <span class="view-toggle-btn active">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            Workflow
                        </span>
                    </div>

                    <div class="h-6 w-px bg-white/20 hidden sm:block"></div>

                    <a href="{{ route('admin.mlm.genealogy.bloodline') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-4 py-2 rounded-xl font-medium transition-all flex items-center gap-2 shadow-lg text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        ผังสายเลือด
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
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">เลือกสมาชิกเพื่อดูผังสายงาน</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">ลากโหนด, สร้างเส้นเชื่อมต่อ, ซูมและเลื่อนได้อิสระ</p>
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
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภทผัง</label>
                    <select id="tree-type-selector"
                            class="px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm min-w-[140px]">
                        <option value="binary">Binary (2 ขา)</option>
                        <option value="unilevel" selected>Unilevel (ไม่จำกัด)</option>
                    </select>
                </div>
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

                <div class="flex gap-2">
                    <button id="btn-view-genealogy"
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        แสดงผัง
                    </button>

                    <button id="btn-export"
                            class="px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all flex items-center gap-2"
                            title="Export Data">
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
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Workflow Diagram</h3>
                </div>
                <div id="current-member-info" class="text-sm text-gray-600 dark:text-gray-400 hidden">
                    กำลังดู: <span id="current-member-name" class="font-semibold text-purple-600 dark:text-purple-400"></span>
                </div>
            </div>
        </div>

        <div class="workflow-container">
            <div id="workflow-diagram" class="w-full h-full"></div>
        </div>
    </div>

    {{-- Features Info --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-purple-500 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Drag Nodes</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">ลากโหนดเพื่อจัดเรียงตำแหน่งได้อิสระ</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-indigo-500 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Connect Nodes</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">ลากจาก port เพื่อสร้างเส้นเชื่อมต่อ</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-blue-500 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Zoom & Pan</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">ซูมและเลื่อนดูผังได้อิสระ</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-lg border-l-4 border-cyan-500 hover:shadow-xl transition-all">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-lg flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Multi-Select</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Shift+Drag เพื่อเลือกหลายโหนด</p>
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
    const depthSelector = document.getElementById('depth-selector');
    const btnView = document.getElementById('btn-view-genealogy');
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
            },
            onConnectionCreate: function(conn) {
                console.log('Connection created:', conn);
            }
        });

        return diagram;
    }

    /**
     * โหลดและแสดงผังสายงาน
     */
    async function loadGenealogy() {
        const memberId = memberSelector.value;
        const treeType = treeTypeSelector.value;
        const depth = depthSelector.value;

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

        // Fetch tree data จาก API
        try {
            const response = await fetch(`/admin/mlm/members/${memberId}/tree-data?type=${treeType}&depth=${depth}`);
            const result = await response.json();

            if (result.success && result.data) {
                // กำหนด spacing ตามประเภทผัง
                const horizontalSpacing = treeType === 'binary'
                    ? (window.innerWidth < 768 ? 180 : 240)
                    : (window.innerWidth < 768 ? 220 : 280);

                // โหลดเป็น tree data
                diagram.loadTreeData(result.data, {
                    horizontalSpacing: horizontalSpacing,
                    verticalSpacing: window.innerWidth < 768 ? 120 : 150
                });
            } else {
                diagram.hideLoading();
                alert('ไม่พบข้อมูลผังสายงาน');
            }
        } catch (error) {
            console.error('Error loading genealogy:', error);
            diagram.hideLoading();
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    }

    /**
     * Export data เป็น JSON
     */
    function exportData() {
        if (!diagram) {
            alert('กรุณาโหลดผังสายงานก่อน');
            return;
        }

        const data = diagram.getData();
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `genealogy-${new Date().toISOString().split('T')[0]}.json`;
        a.click();

        URL.revokeObjectURL(url);
    }

    // Event Listeners
    btnView.addEventListener('click', loadGenealogy);
    btnExport.addEventListener('click', exportData);

    treeTypeSelector.addEventListener('change', function() {
        if (diagram && memberSelector.value) {
            loadGenealogy();
        }
    });

    depthSelector.addEventListener('change', function() {
        if (diagram && memberSelector.value) {
            loadGenealogy();
        }
    });

    // Initialize diagram on load
    initDiagram();

    // Auto-load first member if available
    const firstMember = memberSelector.querySelector('option:nth-child(2)');
    if (firstMember) {
        memberSelector.value = firstMember.value;
        loadGenealogy();
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

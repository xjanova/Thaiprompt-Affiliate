{{--
    ผังสายงาน MLM - Admin View
    แสดงผังสายงานของสมาชิก MLM แบบ Interactive
    รองรับทั้ง Desktop และ Mobile (Touch events)

    @author TP-Affiliate Team
    @version 3.0.0
--}}

@extends('layouts.admin-v3')

@section('title', 'ผังสายงาน MLM')

@push('styles')
<style>
    /* กำหนดความสูงขั้นต่ำสำหรับ chart container */
    .org-chart-container {
        height: calc(100vh - 400px);
        min-height: 500px;
        max-height: 800px;
    }

    @media (max-width: 768px) {
        .org-chart-container {
            height: calc(100vh - 300px);
            min-height: 400px;
        }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="bg-gradient-to-br from-purple-500 to-pink-500 p-2 rounded-xl text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </span>
                ผังสายงาน MLM
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ $member->user->name }} ({{ $member->member_code }})
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.mlm.members.show', $member) }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                ดูข้อมูล
            </a>
            <a href="{{ route('admin.mlm.members.index') }}"
               class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Member Info Card --}}
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 rounded-2xl shadow-xl p-6 text-white">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center text-2xl font-bold shadow-lg">
                {{ strtoupper(substr($member->user->name, 0, 2)) }}
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold">{{ $member->user->name }}</h2>
                <p class="text-sm opacity-90">{{ $member->member_code }} • {{ $member->plan?->display_name ?? 'ไม่มีแผน' }}</p>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                    <span class="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        PV: {{ number_format($member->total_pv, 2) }}
                    </span>
                    <span class="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        รายได้: ฿{{ number_format($member->total_earnings, 2) }}
                    </span>
                    <span class="flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        ทีม: {{ number_format($member->total_team_members) }} คน
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 md:p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            {{-- Tree Type Selector --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    ประเภทผัง:
                </label>
                <div class="flex bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                    <a href="{{ route('admin.mlm.members.genealogy', ['member' => $member, 'type' => 'unilevel', 'depth' => request('depth', 5)]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 {{ $treeType === 'unilevel' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Unilevel
                    </a>
                    <a href="{{ route('admin.mlm.members.genealogy', ['member' => $member, 'type' => 'binary', 'depth' => request('depth', 5)]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 {{ $treeType === 'binary' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                        </svg>
                        Binary
                    </a>
                </div>
            </div>

            {{-- Depth Selector --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    ความลึก:
                </label>
                <select id="depth-selector"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-sm min-w-[120px]">
                    <option value="3" {{ request('depth', 5) == 3 ? 'selected' : '' }}>3 ระดับ</option>
                    <option value="5" {{ request('depth', 5) == 5 ? 'selected' : '' }}>5 ระดับ</option>
                    <option value="7" {{ request('depth', 5) == 7 ? 'selected' : '' }}>7 ระดับ</option>
                    <option value="10" {{ request('depth', 5) == 10 ? 'selected' : '' }}>10 ระดับ</option>
                </select>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Active</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Grace</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                    <span class="text-gray-600 dark:text-gray-400">Inactive</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Suspended</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tree Container --}}
    <div class="org-chart-container bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div id="org-chart-viewer" class="w-full h-full"></div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @if($treeType === 'binary')
            {{-- Binary Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">จำนวนโหนดในผัง</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="stat-node-count">-</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Left Leg</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($member->left_leg_members) }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Right Leg</p>
                        <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($member->right_leg_members) }}</p>
                    </div>
                    <div class="bg-pink-100 dark:bg-pink-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Balance</p>
                        @php
                            $diff = abs($member->left_leg_members - $member->right_leg_members);
                            $total = $member->left_leg_members + $member->right_leg_members;
                            $balance = $total > 0 ? (1 - ($diff / $total)) * 100 : 100;
                        @endphp
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($balance, 0) }}%</p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                    </div>
                </div>
            </div>
        @else
            {{-- Unilevel Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">จำนวนโหนดในผัง</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="stat-node-count">-</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ผู้แนะนำโดยตรง</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($member->total_direct_referrals) }}</p>
                    </div>
                    <div class="bg-emerald-100 dark:bg-emerald-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ระดับสูงสุด</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="stat-max-depth">-</p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ทีมทั้งหมด</p>
                        <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($member->total_team_members) }}</p>
                    </div>
                    <div class="bg-pink-100 dark:bg-pink-900/30 p-3 rounded-xl">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
{{-- Load OrgChartViewer --}}
<script src="{{ asset('assets/js/org-chart-viewer.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ข้อมูล tree จาก backend
    const treeData = @json($treeData);
    const treeType = '{{ $treeType }}';
    const maxDepth = {{ request('depth', 5) }};

    // สร้าง OrgChartViewer
    const viewer = new OrgChartViewer('org-chart-viewer', {
        treeType: treeType,
        maxDepth: maxDepth,
        nodeWidth: window.innerWidth < 768 ? 160 : 200,
        nodeHeight: window.innerWidth < 768 ? 110 : 120,
        horizontalSpacing: window.innerWidth < 768 ? 20 : 40,
        verticalSpacing: window.innerWidth < 768 ? 80 : 100,
        onNodeClick: function(node) {
            console.log('Node clicked:', node);
        }
    });

    // โหลดข้อมูล
    if (treeData) {
        viewer.setData(treeData);

        // อัพเดทสถิติ
        setTimeout(() => {
            const nodeCountEl = document.getElementById('stat-node-count');
            const maxDepthEl = document.getElementById('stat-max-depth');

            if (nodeCountEl) {
                nodeCountEl.textContent = viewer.nodeCount.toLocaleString('th-TH');
            }
            if (maxDepthEl) {
                maxDepthEl.textContent = viewer.maxDepthReached;
            }
        }, 100);
    }

    // Event: เปลี่ยน depth
    document.getElementById('depth-selector').addEventListener('change', function() {
        const newDepth = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('depth', newDepth);
        window.location.href = currentUrl.toString();
    });

    // Responsive: ปรับขนาดเมื่อ resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            viewer.options.nodeWidth = window.innerWidth < 768 ? 160 : 200;
            viewer.options.nodeHeight = window.innerWidth < 768 ? 110 : 120;
            viewer.options.horizontalSpacing = window.innerWidth < 768 ? 20 : 40;
            viewer.options.verticalSpacing = window.innerWidth < 768 ? 80 : 100;

            if (treeData) {
                viewer.nodeCount = 0;
                viewer.maxDepthReached = 0;
                viewer.render();
                viewer.fitToScreen();
            }
        }, 250);
    });
});
</script>
@endpush

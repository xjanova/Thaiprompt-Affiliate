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

    /* Glass Fusion Effect for Hero Header */
    .glass-fusion {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Float Animation */
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    /* Node Detail Panel - slide animation */
    .detail-panel-enter {
        animation: slideInRight 0.3s ease-out forwards;
    }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-6"
     x-data="genealogyPage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4">

        {{-- ========== Premium Hero Header ========== --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 dark:from-purple-800 dark:via-pink-800 dark:to-blue-800 p-8 mb-6 shadow-2xl">
            {{-- Animated Background Orbs --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
            </div>

            {{-- Floating Icons --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                    <i class="fas fa-sitemap"></i>
                </div>
            </div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-6">
                {{-- Member Info พร้อม Avatar --}}
                <div class="flex items-center gap-6">
                    <div class="glass-fusion p-2 rounded-2xl">
                        <img src="{{ auth()->user()->profile_picture_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=6366f1&color=fff&size=80' }}"
                             alt="avatar"
                             class="w-16 h-16 rounded-xl object-cover">
                    </div>
                    <div class="text-white">
                        <h1 class="text-3xl lg:text-4xl font-bold drop-shadow-lg">ศูนย์กลาง MLM</h1>
                        <p class="text-white/90 text-lg mt-1">{{ auth()->user()->name }}</p>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <span class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-sm font-semibold border border-white/30">
                                รหัส: {{ $member->member_code }}
                            </span>
                            <span class="px-3 py-1 rounded-full bg-green-400/30 backdrop-blur-sm text-sm font-semibold border border-green-400/50">
                                ✓ {{ $member->plan?->name ?? 'Active' }}
                            </span>
                            @if($member->rank)
                            <span class="px-3 py-1 rounded-full backdrop-blur-sm text-sm font-semibold border border-white/30"
                                  style="background: {{ $member->rank->color ?? '#6366F1' }}40;">
                                {{ $member->rank->name }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-white drop-shadow-lg">{{ number_format($member->total_team_members ?? 0) }}</div>
                        <div class="text-white/80 text-xs mt-1">สมาชิกทั้งหมด</div>
                    </div>
                    <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-white drop-shadow-lg">{{ number_format($member->total_direct_referrals ?? 0) }}</div>
                        <div class="text-white/80 text-xs mt-1">แนะนำตรง</div>
                    </div>
                    <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-green-300 drop-shadow-lg">{{ $member->left_leg_members ?? 0 }}</div>
                        <div class="text-white/80 text-xs mt-1">ขาซ้าย</div>
                    </div>
                    <div class="bg-white/20 dark:bg-white/10 backdrop-blur-xl border border-white/30 rounded-2xl p-4 text-center min-w-[100px]">
                        <div class="text-3xl font-black text-red-300 drop-shadow-lg">{{ $member->right_leg_members ?? 0 }}</div>
                        <div class="text-white/80 text-xs mt-1">ขาขวา</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== MLM ACTION BUTTONS ========== --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4 mb-6">
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

            {{-- บิลดูดวง --}}
            <a href="{{ route('user.fortune-referral.commissions') }}" class="mlm-action-card rounded-2xl p-4 text-center hover:ring-2 hover:ring-violet-500 hover:ring-offset-2">
                <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-2xl text-white shadow-lg mb-3">
                    🔮
                </div>
                <div class="font-bold text-gray-800 dark:text-white text-sm">บิลดูดวง</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ค่าแนะนำ</div>
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

            <a href="{{ route('user.fortune-referral.recruit') }}" class="flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-gray-800 shadow hover:shadow-lg transition-all">
                <span class="text-2xl">🔮</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white text-sm">ชวนเพื่อนดูดวง</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">รับค่าแนะนำ</div>
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
                    <button @click="copyReferralLink()" class="px-6 py-3 bg-white text-green-600 rounded-xl font-bold hover:bg-green-50 transition-all shadow-lg flex items-center justify-center gap-2">
                        <span x-text="copyIcon">📋</span>
                        <span x-text="copyText">คัดลอกลิ้งค์</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ========== GENEALOGY TOOLBAR ========== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden mb-6">
            {{-- Toolbar --}}
            <div class="p-4 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-gray-700 dark:to-gray-600">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🌳</span>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">ผังสายงาน MLM</h2>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- ค้นหา --}}
                        <div class="relative">
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.300ms="doSearch()"
                                   @keydown.enter.prevent="searchNext()"
                                   placeholder="ค้นหาชื่อ/รหัส..."
                                   class="w-48 pl-8 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <span class="absolute left-2.5 top-2.5 text-gray-400 text-sm">🔎</span>
                            <span x-show="searchResultCount > 0" class="absolute right-2 top-2 text-xs text-purple-600 dark:text-purple-400 font-semibold"
                                  x-text="(searchCurrentIndex + 1) + '/' + searchResultCount"></span>
                        </div>

                        {{-- Filter สถานะ --}}
                        <select x-model="statusFilter" @change="applyFilter()"
                                class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white">
                            <option value="all">ทั้งหมด</option>
                            <option value="active">Active เท่านั้น</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        {{-- ปุ่ม Fit --}}
                        <button @click="fitToScreen()"
                                class="px-4 py-2 bg-white dark:bg-gray-700 rounded-lg text-sm font-semibold hover:shadow transition-all border border-gray-200 dark:border-gray-600">
                            🔄 พอดีจอ
                        </button>
                    </div>
                </div>
            </div>

            {{-- Genealogy Container --}}
            <div class="relative">
                <div id="genealogy-container" class="min-h-[500px] md:min-h-[650px]"></div>

                {{-- Node Detail Panel (Sidebar Desktop / Bottom Sheet Mobile) --}}
                <div x-show="selectedNode" x-transition
                     class="absolute top-0 right-0 w-full md:w-80 h-full bg-white/95 dark:bg-gray-800/95 backdrop-blur-lg border-l border-gray-200 dark:border-gray-700 shadow-2xl overflow-y-auto detail-panel-enter z-30">
                    <div class="p-5">
                        {{-- ปิด --}}
                        <button @click="selectedNode = null" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xl">
                            &times;
                        </button>

                        {{-- Avatar + ชื่อ --}}
                        <div class="text-center mb-4">
                            <img :src="selectedNode?.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent((selectedNode?.name || 'U').charAt(0)) + '&background=6366f1&color=fff&size=80'"
                                 class="w-16 h-16 rounded-full mx-auto border-4 shadow-lg"
                                 :class="{
                                     'border-green-400': selectedNode?.retention_status === 'active',
                                     'border-yellow-400': selectedNode?.retention_status === 'grace_period',
                                     'border-red-400': selectedNode?.retention_status === 'inactive',
                                     'border-gray-300': !selectedNode?.retention_status
                                 }">
                            <h3 class="mt-2 text-lg font-bold text-gray-800 dark:text-white" x-text="selectedNode?.name || '-'"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedNode?.member_code || ''"></p>
                        </div>

                        {{-- Rank Badge --}}
                        <div x-show="selectedNode?.rank_name" class="flex justify-center mb-4">
                            <span class="px-4 py-1 rounded-full text-white text-sm font-semibold"
                                  :style="'background:' + (selectedNode?.rank_color || '#6366F1')"
                                  x-text="selectedNode?.rank_name"></span>
                        </div>

                        {{-- สถานะ --}}
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">สถานะ</span>
                                <span class="font-semibold"
                                      :class="{
                                          'text-green-600': selectedNode?.retention_status === 'active',
                                          'text-yellow-600': selectedNode?.retention_status === 'grace_period',
                                          'text-red-600': selectedNode?.retention_status === 'inactive'
                                      }"
                                      x-text="selectedNode?.retention_status === 'active' ? 'Active' : (selectedNode?.retention_status === 'grace_period' ? 'ใกล้หมดอายุ' : 'Inactive')">
                                </span>
                            </div>
                            <div x-show="selectedNode?.retention_days_left != null" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">หมดอายุใน</span>
                                <span class="font-semibold text-gray-800 dark:text-white" x-text="(selectedNode?.retention_days_left || 0) + ' วัน'"></span>
                            </div>
                            <div x-show="selectedNode?.email" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">อีเมล</span>
                                <span class="font-semibold text-gray-800 dark:text-white text-xs" x-text="selectedNode?.email || '-'"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">PV ส่วนตัว</span>
                                <span class="font-semibold text-gray-800 dark:text-white" x-text="(selectedNode?.monthly_pv || 0).toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">ทีมทั้งหมด</span>
                                <span class="font-semibold text-gray-800 dark:text-white" x-text="(selectedNode?.total_team_members || 0).toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">แนะนำตรง</span>
                                <span class="font-semibold text-gray-800 dark:text-white" x-text="(selectedNode?.direct_referrals || 0).toLocaleString()"></span>
                            </div>
                            <div x-show="selectedNode?.joined_at" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">เข้าร่วมเมื่อ</span>
                                <span class="font-semibold text-gray-800 dark:text-white text-xs" x-text="selectedNode?.joined_at || '-'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Legend --}}
                <div class="absolute bottom-4 left-4 flex flex-wrap gap-3 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-lg p-2 shadow text-xs z-10">
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">Active</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">ใกล้หมดอายุ</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">Inactive</span>
                    </div>
                </div>
            </div>
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

@vite('resources/js/mlm-genealogy-v3.js')
<script>
function genealogyPage() {
    return {
        viewer: null,
        selectedNode: null,
        searchQuery: '',
        searchResultCount: 0,
        searchCurrentIndex: -1,
        statusFilter: 'all',
        copyIcon: '📋',
        copyText: 'คัดลอกลิ้งค์',

        init() {
            this.$nextTick(() => {
                const container = document.getElementById('genealogy-container');
                if (!container || typeof MlmGenealogyV3 === 'undefined') return;

                this.viewer = new MlmGenealogyV3(container, {
                    apiUrl: '{{ route("user.mlm.genealogy-data") }}',
                    treeType: '{{ ($member->plan?->type === "binary" || $member->plan?->type === "hybrid") ? "binary" : "unilevel" }}',
                    maxDepth: 5,
                    onNodeClick: (node) => {
                        this.selectedNode = node;
                    }
                });
                this.viewer.loadTree();
            });
        },

        doSearch() {
            if (!this.viewer) return;
            const results = this.viewer.search(this.searchQuery);
            this.searchResultCount = results.length;
            this.searchCurrentIndex = results.length > 0 ? 0 : -1;
        },

        searchNext() {
            if (!this.viewer) return;
            this.viewer.searchNext();
            if (this.searchResultCount > 0) {
                this.searchCurrentIndex = (this.searchCurrentIndex + 1) % this.searchResultCount;
            }
        },

        fitToScreen() {
            if (this.viewer) this.viewer.fitToScreen();
        },

        applyFilter() {
            // Filter จะถูกใช้ในครั้งถัดไปที่ render
            // สำหรับตอนนี้ reload tree
            if (this.viewer) this.viewer.loadTree();
        },

        copyReferralLink() {
            const input = document.getElementById('referral-link');
            if (!input) return;

            navigator.clipboard.writeText(input.value).then(() => {
                this.copyIcon = '✅';
                this.copyText = 'คัดลอกแล้ว!';
                setTimeout(() => {
                    this.copyIcon = '📋';
                    this.copyText = 'คัดลอกลิ้งค์';
                }, 2000);
            });
        }
    };
}
</script>
@endsection

@extends('layouts.admin-v3')

@section('title', 'รายละเอียดผู้ใช้ - ' . $user->name)

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการผู้ใช้
        </a>
    </div>

    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 left-20" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- User Profile Section --}}
                <div class="flex items-center gap-6">
                    {{-- Avatar --}}
                    <div class="relative">
                        <img src="{{ $user->profile_picture_url }}"
                             alt="{{ $user->name }}"
                             class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white/30 shadow-2xl"
                             onerror="this.onerror=null; this.outerHTML='<div class=\'h-24 w-24 rounded-2xl flex items-center justify-center text-4xl font-bold ring-4 ring-white/30 shadow-2xl\' style=\'background: rgba(255,255,255,0.2); color: white;\'>{{ strtoupper(substr($user->name, 0, 1)) }}</div>';">
                        @if($user->email_verified_at)
                            <div class="absolute -bottom-2 -right-2 bg-green-500 text-white rounded-full p-2 shadow-lg">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                        @endif
                    </div>

                    {{-- User Info --}}
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg mb-2">{{ $user->name }}</h1>
                        <p class="text-purple-100 text-lg mb-3">{{ $user->email }}</p>

                        {{-- Role Badges --}}
                        <div class="flex flex-wrap gap-2">
                            @if($user->is_super_admin)
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-400/20 text-yellow-100 border border-yellow-300/30">
                                    🔐 Super Admin
                                </span>
                            @endif
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-white/20 text-white border border-white/30">
                                {{ ucfirst($user->role ?? 'user') }}
                            </span>
                            @if($user->email_verified_at)
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-400/20 text-green-100 border border-green-300/30">
                                    ✓ Verified
                                </span>
                            @else
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-400/20 text-orange-100 border border-orange-300/30">
                                    ⚠ Not Verified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-3">
                    @php
                        $mlmMember = $user->mlmMembers()->first();
                    @endphp
                    @if($mlmMember)
                        <a href="{{ route('admin.mlm.members.show', $mlmMember) }}"
                           class="glass-fusion hover:bg-white/30 text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                            <i class="fas fa-network-wired group-hover:scale-110 transition-transform"></i>
                            ดู MLM Member
                        </a>
                    @endif
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-edit group-hover:rotate-12 transition-transform duration-300"></i>
                        แก้ไขข้อมูล
                    </a>
                </div>
            </div>
        </div>
    </div>

<!-- Details Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- User Information -->
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลผู้ใช้</h3>
        <dl class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">ชื่อ</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->name }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">อีเมล</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Role</dt>
                <dd class="mt-1">
                    <span class="px-2 py-1 text-xs font-medium rounded"
                          style="@if($user->role === 'admin')background-color: color-mix(in srgb, var(--arrow-x-primary) 15%, transparent); color: var(--arrow-x-primary)@elseif($user->role === 'super_admin')background-color: color-mix(in srgb, var(--arrow-x-warning) 15%, transparent); color: var(--arrow-x-warning)@elsebackground-color: color-mix(in srgb, var(--arrow-x-surface) 15%, transparent); color: var(--arrow-x-text-secondary)@endif">
                        {{ ucfirst($user->role ?? 'user') }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">ธีมเมนู</dt>
                <dd class="mt-1">
                    @php
                        $userTheme = $user->menu_theme_preference ?? 'millennium';
                    @endphp
                    @if($userTheme === 'classic_x')
                        <span class="px-3 py-1.5 inline-flex items-center text-xs font-semibold rounded-lg border shadow-sm"
                              style="background-color: color-mix(in srgb, var(--arrow-x-primary) 15%, transparent); color: var(--arrow-x-primary); border-color: var(--arrow-x-primary)">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V3zm3 1v10h8V4H6z" clip-rule="evenodd"/>
                            </svg>
                            Classic X Theme
                        </span>
                    @else
                        <span class="px-3 py-1.5 inline-flex items-center text-xs font-semibold rounded-lg border shadow-sm"
                              style="background-color: color-mix(in srgb, var(--arrow-x-warning) 15%, transparent); color: var(--arrow-x-warning); border-color: var(--arrow-x-warning)">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                            Millennium Theme
                        </span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">สมัครเมื่อ</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">อัพเดทล่าสุด</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->updated_at->format('d/m/Y H:i') }}</dd>
            </div>

            @if($user->email_verified_at)
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">ยืนยันอีเมลเมื่อ</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email_verified_at->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <!-- MLM Member Information -->
    @php
        $mlmMember = $user->mlmMembers()->first();
    @endphp
    @if($mlmMember)
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล MLM Member</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">รหัสสมาชิก</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-white/60 dark:bg-white/10 border border-gray-200 dark:border-white/20 rounded text-sm font-mono text-gray-900 dark:text-white">
                            {{ $mlmMember->member_code }}
                        </code>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Unilevel Level</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $mlmMember->unilevel_level }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Total PV</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ number_format($mlmMember->total_pv, 2) }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Team PV</dt>
                    <dd class="mt-1 text-lg font-bold" style="color: var(--arrow-x-success)">
                        {{ number_format($mlmMember->total_team_pv, 2) }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">สถานะ</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full"
                              style="@if($mlmMember->is_qualified)background-color: color-mix(in srgb, var(--arrow-x-success) 15%, transparent); color: var(--arrow-x-success)@elsebackground-color: color-mix(in srgb, var(--arrow-x-surface) 15%, transparent); color: var(--arrow-x-text-secondary)@endif">
                            {{ $mlmMember->is_qualified ? 'Qualified' : 'Not Qualified' }}
                        </span>
                    </dd>
                </div>

                <div class="pt-4 border-t border-gray-200 dark:border-white/10">
                    <a href="{{ route('admin.mlm.members.show', $mlmMember) }}"
                       class="text-sm font-medium hover:opacity-80 transition-opacity"
                       style="color: var(--arrow-x-primary)">
                        ดูรายละเอียด MLM Member →
                    </a>
                </div>
            </dl>
        </div>
    @else
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล MLM Member</h3>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>ผู้ใช้นี้ยังไม่ได้เป็น MLM Member</p>
            </div>
        </div>
    @endif
</div>

<!-- MLM Commission History -->
@if($user->mlmCommissions && $user->mlmCommissions->count() > 0)
<div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ประวัติคอมมิชชั่น</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
            <thead class="bg-gray-50/50 dark:bg-white/5">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ยอดขาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เปอร์เซ็นต์</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คอมมิชชั่น</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                </tr>
            </thead>
            <tbody class="bg-white/50 dark:bg-transparent divide-y divide-gray-200 dark:divide-white/10">
                @foreach($user->mlmCommissions->take(10) as $commission)
                    <tr class="hover:bg-white/60 dark:hover:bg-white/5 transition-all duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $commission->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($commission->sales_amount ?? 0, 2) }}฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $commission->type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" style="color: var(--arrow-x-success)">
                            {{ number_format($commission->commission_amount, 2) }}฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                  style="@if($commission->status === 'paid')background-color: color-mix(in srgb, var(--arrow-x-success) 15%, transparent); color: var(--arrow-x-success)@elseif($commission->status === 'pending')background-color: color-mix(in srgb, var(--arrow-x-warning) 15%, transparent); color: var(--arrow-x-warning)@elsebackground-color: color-mix(in srgb, var(--arrow-x-surface) 15%, transparent); color: var(--arrow-x-text-secondary)@endif">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($user->mlmCommissions->count() > 10)
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 text-center">
            แสดง 10 รายการล่าสุด จากทั้งหมด {{ $user->mlmCommissions->count() }} รายการ
        </p>
    @endif
</div>
@endif

<!-- Membership Retention Status -->
@php
    $retentionStatus = \App\Models\MembershipRetentionStatus::where('user_id', $user->id)->first();
@endphp
@if($retentionStatus)
<div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fas fa-heart-pulse" style="color: var(--arrow-x-primary)"></i>
            ระบบรักษายอด (Retention)
        </h3>
        <a href="{{ route('admin.retention.users.show', $user->id) }}"
           class="text-sm font-medium hover:opacity-80 transition-opacity"
           style="color: var(--arrow-x-primary)">
            ดูรายละเอียด →
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        {{-- สถานะ --}}
        <div class="bg-white/60 dark:bg-white/10 rounded-xl p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">สถานะ</div>
            <div class="flex items-center gap-2">
                @if($retentionStatus->status === 'active')
                    <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="font-semibold" style="color: var(--arrow-x-success)">Active</span>
                @elseif($retentionStatus->status === 'expired')
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                    <span class="font-semibold text-red-500">Expired</span>
                @else
                    <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                    <span class="font-semibold text-gray-500">Inactive</span>
                @endif
            </div>
        </div>

        {{-- วันที่เหลือ --}}
        <div class="bg-white/60 dark:bg-white/10 rounded-xl p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">วันที่เหลือ</div>
            <div class="text-2xl font-bold {{ $retentionStatus->daysRemaining() <= 7 ? 'text-amber-500' : '' }}" style="color: {{ $retentionStatus->daysRemaining() > 7 ? 'var(--arrow-x-primary)' : '' }}">
                {{ $retentionStatus->daysRemaining() }} <span class="text-sm font-normal text-gray-500">วัน</span>
            </div>
        </div>

        {{-- แต้มปัจจุบัน --}}
        <div class="bg-white/60 dark:bg-white/10 rounded-xl p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">แต้มปัจจุบัน</div>
            <div class="text-2xl font-bold" style="color: var(--arrow-x-primary)">
                {{ number_format($retentionStatus->current_points, 0) }}
                <span class="text-sm font-normal text-gray-500">/ {{ number_format($retentionStatus->required_points, 0) }}</span>
            </div>
        </div>

        {{-- เดือนติดต่อกัน --}}
        <div class="bg-white/60 dark:bg-white/10 rounded-xl p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">เดือนติดต่อกัน</div>
            <div class="text-2xl font-bold" style="color: var(--arrow-x-success)">
                {{ $retentionStatus->consecutive_months }} <span class="text-sm font-normal text-gray-500">เดือน</span>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="mb-4">
        <div class="flex items-center justify-between text-sm mb-1">
            <span class="text-gray-600 dark:text-gray-400">ความคืบหน้าแต้ม</span>
            <span class="font-semibold" style="color: var(--arrow-x-primary)">{{ number_format($retentionStatus->getPointsPercentage(), 1) }}%</span>
        </div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 {{ $retentionStatus->getPointsPercentage() >= 100 ? 'bg-gradient-to-r from-green-500 to-emerald-500' : 'bg-gradient-to-r from-blue-500 to-indigo-500' }}"
                 style="width: {{ min(100, $retentionStatus->getPointsPercentage()) }}%"></div>
        </div>
    </div>

    {{-- Auto-Renewal Settings --}}
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-4 border border-indigo-200/50 dark:border-indigo-700/30">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-robot text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <div class="font-semibold text-gray-900 dark:text-white">Auto-Renewal</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $retentionStatus->getAutoRenewalStatusText() }}
                    </div>
                </div>
            </div>
            <div class="text-right">
                @if($retentionStatus->isAutoRenewalEnabled())
                    <div class="text-sm">
                        <span class="text-gray-500">แหล่งเงิน:</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $retentionStatus->getAutoRenewalSourceLabel() }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="text-gray-500">ต่ออายุ:</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $retentionStatus->auto_renewal_months ?? 1 }} เดือน</span>
                    </div>
                @else
                    <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm rounded-full">
                        ยังไม่เปิดใช้งาน
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- Activity Summary - Beautiful Gradient Cards --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar" style="color: var(--arrow-x-primary-start)"></i>
            สถิติกิจกรรม
        </h2>

        @php
            $mlmMemberSummary = $user->mlmMembers()->first();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Account Status --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-800">
                {{-- Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-user-check text-9xl text-white"></i>
                </div>

                {{-- Content --}}
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">{{ $user->email_verified_at ? '✅' : '⏳' }}</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                            Status
                        </span>
                    </div>
                    <p class="text-blue-100 text-sm mb-1">สถานะบัญชี</p>
                    <p class="text-3xl font-bold text-white">{{ $user->email_verified_at ? 'Active' : 'Pending' }}</p>
                    <p class="text-xs text-blue-100 mt-2">Account Status</p>
                </div>
            </div>

            @if($mlmMemberSummary)
                {{-- Total PV --}}
                <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800">
                    {{-- Background Icon --}}
                    <div class="absolute -right-8 -top-8 opacity-10">
                        <i class="fas fa-star text-9xl text-white"></i>
                    </div>

                    {{-- Content --}}
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-5xl">⭐</span>
                            <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                                PV
                            </span>
                        </div>
                        <p class="text-purple-100 text-sm mb-1">Total PV</p>
                        <p class="text-3xl font-bold text-white">{{ number_format($mlmMemberSummary->total_pv, 2) }}</p>
                        <p class="text-xs text-purple-100 mt-2">Personal Volume</p>
                    </div>
                </div>

                {{-- Team PV --}}
                <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900">
                    {{-- Background Icon --}}
                    <div class="absolute -right-8 -top-8 opacity-10">
                        <i class="fas fa-users text-9xl text-white"></i>
                    </div>

                    {{-- Content --}}
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-5xl">👥</span>
                            <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                                Team
                            </span>
                        </div>
                        <p class="text-green-100 text-sm mb-1">Team PV</p>
                        <p class="text-3xl font-bold text-white">{{ number_format($mlmMemberSummary->total_team_pv, 2) }}</p>
                        <p class="text-xs text-green-100 mt-2">Team Volume</p>
                    </div>
                </div>
            @else
                {{-- Placeholder for non-MLM users --}}
                <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 bg-gray-400 dark:bg-gray-600">
                    <div class="text-center text-white/80">
                        <i class="fas fa-ban text-4xl mb-2"></i>
                        <p class="text-sm">ไม่ได้เป็น MLM Member</p>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 bg-gray-400 dark:bg-gray-600">
                    <div class="text-center text-white/80">
                        <i class="fas fa-ban text-4xl mb-2"></i>
                        <p class="text-sm">ไม่ได้เป็น MLM Member</p>
                    </div>
                </div>
            @endif

            {{-- MLM Commissions Count --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-800">
                {{-- Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10">
                    <i class="fas fa-money-bill-wave text-9xl text-white"></i>
                </div>

                {{-- Content --}}
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">💰</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                            Total
                        </span>
                    </div>
                    <p class="text-orange-100 text-sm mb-1">MLM Commissions</p>
                    <p class="text-3xl font-bold text-white">{{ $user->mlmCommissions->count() ?? 0 }}</p>
                    <p class="text-xs text-orange-100 mt-2">Commission Records</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

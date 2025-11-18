@extends('layouts.admin-v3')

@section('title', 'รายละเอียดผู้ใช้ - ' . $user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="hover:opacity-80 transition-opacity" style="color: var(--arrow-x-primary)">
        ← กลับไปรายการผู้ใช้
    </a>
</div>

<!-- User Header Card -->
<div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <div class="h-16 w-16 rounded-full flex items-center justify-center text-2xl font-bold"
                 style="background-color: color-mix(in srgb, var(--arrow-x-primary) 15%, transparent); color: var(--arrow-x-primary)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="ml-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            @php
                $mlmMember = $user->mlmMembers()->first();
            @endphp
            @if($mlmMember)
                <a href="{{ route('admin.mlm.members.show', $mlmMember) }}"
                   class="px-5 py-2.5 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5"
                   style="background: var(--arrow-x-primary-gradient)">
                    ดู MLM Member
                </a>
            @endif
            <a href="{{ route('admin.users.edit', $user) }}"
               class="px-5 py-2.5 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5"
               style="background: var(--arrow-x-primary-gradient)">
                แก้ไข
            </a>
        </div>
    </div>

    <!-- Role Badge -->
    <div class="flex gap-2 mb-4">
        @if($user->is_super_admin)
            <span class="px-3 py-1 text-sm font-semibold rounded-full"
                  style="background-color: color-mix(in srgb, var(--arrow-x-warning) 15%, transparent); color: var(--arrow-x-warning)">
                🔐 Super Admin
            </span>
        @endif
        <span class="px-3 py-1 text-sm font-semibold rounded-full"
              style="@if($user->role === 'admin')background-color: color-mix(in srgb, var(--arrow-x-primary) 15%, transparent); color: var(--arrow-x-primary)@elseif($user->role === 'super_admin')background-color: color-mix(in srgb, var(--arrow-x-warning) 15%, transparent); color: var(--arrow-x-warning)@elsebackground-color: color-mix(in srgb, var(--arrow-x-surface) 15%, transparent); color: var(--arrow-x-text-secondary)@endif">
            {{ ucfirst($user->role ?? 'user') }}
        </span>
        @if($user->email_verified_at)
            <span class="px-3 py-1 text-sm font-semibold rounded-full"
                  style="background-color: color-mix(in srgb, var(--arrow-x-success) 15%, transparent); color: var(--arrow-x-success)">
                ✓ Email Verified
            </span>
        @else
            <span class="px-3 py-1 text-sm font-semibold rounded-full"
                  style="background-color: color-mix(in srgb, var(--arrow-x-warning) 15%, transparent); color: var(--arrow-x-warning)">
                ⚠ Email Not Verified
            </span>
        @endif
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

<!-- Activity Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">สถานะบัญชี</h3>
        <p class="text-2xl font-bold" style="color: var(--arrow-x-primary)">
            {{ $user->email_verified_at ? 'Active' : 'Pending' }}
        </p>
    </div>

    @php
        $mlmMemberSummary = $user->mlmMembers()->first();
    @endphp
    @if($mlmMemberSummary)
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Total PV</h3>
        <p class="text-2xl font-bold" style="color: var(--arrow-x-primary)">{{ number_format($mlmMemberSummary->total_pv, 2) }}</p>
    </div>

    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Team PV</h3>
        <p class="text-2xl font-bold" style="color: var(--arrow-x-success)">{{ number_format($mlmMemberSummary->total_team_pv, 2) }}</p>
    </div>
    @endif

    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">MLM Commissions</h3>
        <p class="text-2xl font-bold" style="color: var(--arrow-x-warning)">{{ $user->mlmCommissions->count() ?? 0 }}</p>
    </div>
</div>
@endsection

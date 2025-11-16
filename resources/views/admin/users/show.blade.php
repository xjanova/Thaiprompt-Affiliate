@extends('layouts.admin-v3')

@section('title', 'รายละเอียดผู้ใช้ - ' . $user->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
        ← กลับไปรายการผู้ใช้
    </a>
</div>

<!-- User Header Card -->
<div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <div class="h-16 w-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-2xl font-bold text-indigo-600 dark:text-indigo-300">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="ml-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            @if($user->affiliate)
                <a href="{{ route('admin.affiliates.show', $user->affiliate) }}"
                   class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-700 hover:to-emerald-700 shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                    ดู Affiliate
                </a>
            @endif
            <a href="{{ route('admin.users.edit', $user) }}"
               class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-cyan-700 shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                แก้ไข
            </a>
        </div>
    </div>

    <!-- Role Badge -->
    <div class="flex gap-2 mb-4">
        @if($user->is_super_admin)
            <span class="px-3 py-1 text-sm font-semibold bg-purple-100 text-purple-800 rounded-full">
                🔐 Super Admin
            </span>
        @endif
        <span class="px-3 py-1 text-sm font-semibold rounded-full
            @if($user->role === 'admin') bg-blue-100 text-blue-800
            @elseif($user->role === 'super_admin') bg-purple-100 text-purple-800
            @else bg-gray-100 text-gray-800
            @endif">
            {{ ucfirst($user->role ?? 'user') }}
        </span>
        @if($user->email_verified_at)
            <span class="px-3 py-1 text-sm font-semibold bg-green-100 text-green-800 rounded-full">
                ✓ Email Verified
            </span>
        @else
            <span class="px-3 py-1 text-sm font-semibold bg-yellow-100 text-yellow-800 rounded-full">
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
                    <span class="px-2 py-1 text-xs font-medium rounded
                        @if($user->role === 'admin') bg-blue-100 text-blue-800
                        @elseif($user->role === 'super_admin') bg-purple-100 text-purple-800
                        @else bg-gray-100 text-gray-800
                        @endif">
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
                        <span class="px-3 py-1.5 inline-flex items-center text-xs font-semibold rounded-lg bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200 shadow-sm">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V3zm3 1v10h8V4H6z" clip-rule="evenodd"/>
                            </svg>
                            Classic X Theme
                        </span>
                    @else
                        <span class="px-3 py-1.5 inline-flex items-center text-xs font-semibold rounded-lg bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 border border-purple-200 shadow-sm">
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

    <!-- Affiliate Information -->
    @if($user->affiliate)
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล Affiliate</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">รหัสแนะนำ</dt>
                    <dd class="mt-1">
                        <code class="px-2 py-1 bg-white/60 dark:bg-white/10 border border-gray-200 dark:border-white/20 rounded text-sm font-mono text-gray-900 dark:text-white">
                            {{ $user->affiliate->referral_code }}
                        </code>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">ระดับ</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->affiliate->level }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Referrals</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->affiliate->total_referrals }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Earnings</dt>
                    <dd class="mt-1 text-lg font-bold text-green-600 dark:text-green-400">
                        {{ number_format($user->affiliate->total_earnings, 2) }}฿
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">สถานะ</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($user->affiliate->status === 'active') bg-green-100 text-green-800
                            @elseif($user->affiliate->status === 'inactive') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($user->affiliate->status) }}
                        </span>
                    </dd>
                </div>

                <div class="pt-4 border-t border-gray-200 dark:border-white/10">
                    <a href="{{ route('admin.affiliates.show', $user->affiliate) }}"
                       class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium">
                        ดูรายละเอียด Affiliate →
                    </a>
                </div>
            </dl>
        </div>
    @else
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูล Affiliate</h3>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>ผู้ใช้นี้ยังไม่ได้เป็น Affiliate</p>
            </div>
        </div>
    @endif
</div>

<!-- Commission History -->
@if($user->commissions && $user->commissions->count() > 0)
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
                @foreach($user->commissions->take(10) as $commission)
                    <tr class="hover:bg-white/60 dark:hover:bg-white/5 transition-all duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $commission->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($commission->sale_amount ?? 0, 2) }}฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $commission->commission_rate ?? 0 }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($commission->amount, 2) }}฿
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if($commission->status === 'paid') bg-green-100 text-green-800
                                @elseif($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($commission->status) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($user->commissions->count() > 10)
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 text-center">
            แสดง 10 รายการล่าสุด จากทั้งหมด {{ $user->commissions->count() }} รายการ
        </p>
    @endif
</div>
@endif

<!-- Activity Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">สถานะบัญชี</h3>
        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
            {{ $user->email_verified_at ? 'Active' : 'Pending' }}
        </p>
    </div>

    @if($user->affiliate)
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Total Referrals</h3>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $user->affiliate->total_referrals }}</p>
    </div>

    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Total Earnings</h3>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($user->affiliate->total_earnings, 2) }}฿</p>
    </div>
    @endif

    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Commissions</h3>
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $user->commissions->count() ?? 0 }}</p>
    </div>
</div>
@endsection

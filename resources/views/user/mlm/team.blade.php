@extends('layouts.user-arrow-x')

@section('title', 'ทีมของฉัน - MLM')

@section('content')
{{-- แสดงข้อความแนะนำถ้าไม่ได้เป็น MLM Member --}}
@if(isset($notMlmMember) && $notMlmMember)
<div class="mb-6">
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl shadow-xl p-8 text-white">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="flex-shrink-0">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-2xl font-bold mb-2">คุณยังไม่ได้เป็นสมาชิก MLM</h2>
                <p class="text-amber-100 mb-4">เพื่อดูทีมงานและสร้างเครือข่าย กรุณาสมัครเป็นสมาชิก MLM ก่อน</p>
                <a href="{{ route('user.mlm.dashboard') }}"
                   class="inline-flex items-center px-6 py-3 bg-white text-amber-600 font-bold rounded-xl hover:bg-amber-50 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                    ไปหน้า MLM Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@else

<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Indigo-Purple-Pink for Team) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-user-friends text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">ทีมของฉัน</h1>
                    <p class="text-indigo-100 text-lg mt-1">สมาชิกที่แนะนำทางตรง (Direct Referrals)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Statistics -->
    <div class="grid md:grid-cols-4 gap-4">
        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👤</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">สมาชิกทางตรง</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $directReferrals->total() }}</div>
                </div>
            </div>
        </x-arrow-x.card-v3>

        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">ใช้งาน</div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ $directReferrals->where('status', 'active')->count() }}
                    </div>
                </div>
            </div>
        </x-arrow-x.card-v3>

        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">⏳</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">รออนุมัติ</div>
                    <div class="text-2xl font-bold text-yellow-600">
                        {{ $directReferrals->where('status', 'pending')->count() }}
                    </div>
                </div>
            </div>
        </x-arrow-x.card-v3>

        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📅</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">เดือนนี้</div>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $directReferrals->filter(function($ref) {
                            return $ref->created_at->isCurrentMonth();
                        })->count() }}
                    </div>
                </div>
            </div>
        </x-arrow-x.card-v3>
    </div>

    <!-- Team Members List -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <span>📋</span> รายชื่อสมาชิกในทีม
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สมาชิก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">รหัสสมาชิก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">วันที่เข้าร่วม</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ยศ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($directReferrals as $index => $referral)
                        <tr class="hover:bg-gray-50 dark:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ ($directReferrals->currentPage() - 1) * $directReferrals->perPage() + $index + 1 }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($referral->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $referral->user->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $referral->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded font-mono text-sm">{{ $referral->member_code }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $referral->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $referral->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{-- สถานะบัญชี --}}
                                @if($referral->status === 'active')
                                    <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">✅ ใช้งาน</span>
                                @elseif($referral->status === 'pending')
                                    <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">⏳ รออนุมัติ</span>
                                @elseif($referral->status === 'inactive')
                                    <span class="px-3 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white rounded-full">❌ ไม่ใช้งาน</span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full">🚫 ระงับ</span>
                                @endif
                                {{-- สถานะรักษายอด (volume retention) --}}
                                @if($referral->status === 'active')
                                    @php
                                        $refRetention = \App\Helpers\MlmRetentionHelper::getRetentionStatus($referral);
                                    @endphp
                                    @if(($refRetention['retention_enabled'] ?? false) && ($refRetention['status'] ?? 'active') !== 'active')
                                        @if($refRetention['status'] === 'grace_period')
                                            <span class="ml-1 px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-full">⏳ ผ่อนผัน</span>
                                        @else
                                            <span class="ml-1 px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full">PV ไม่ถึง</span>
                                        @endif
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($referral->rank)
                                    <x-rank-icon :rank="$referral->rank" size="sm" show-name />
                                @else
                                    <span class="text-gray-400 text-sm">ไม่มียศ</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <span class="text-4xl mb-4 block">👥</span>
                                <p class="text-gray-600 dark:text-gray-400 mb-2">ยังไม่มีสมาชิกในทีม</p>
                                <a href="{{ route('user.mlm.referral') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                                    ดูลิงก์แนะนำ →
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($directReferrals->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $directReferrals->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-3 gap-4">
        <a href="{{ route('user.mlm.referral') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">🔗</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-800 dark:text-white">ลิงก์แนะนำ</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">แชร์ลิงก์เชิญเพื่อน</div>
                </div>
                <span class="text-blue-600">→</span>
            </div>
        </a>

        <a href="{{ route('user.mlm.genealogy') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">🌳</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-800 dark:text-white">โครงสร้างทีม</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">ดูแผนผังองค์กร</div>
                </div>
                <span class="text-purple-600">→</span>
            </div>
        </a>

        <a href="{{ route('user.mlm.dashboard') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📊</span>
                </div>
                <div class="flex-1">
                    <div class="font-bold text-gray-800 dark:text-white">Dashboard</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">กลับหน้าหลัก MLM</div>
                </div>
                <span class="text-green-600">→</span>
            </div>
        </a>
    </div>
</div>
@endif

@push('styles')
<style>
/* Glass Fusion Effect for Hero Header */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Float Animation */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
@endpush
@endsection

@extends('layouts.user-arrow-x')

@section('title', 'ทีมของฉัน - MLM')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">👥</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ทีมของฉัน</h1>
                <p class="text-indigo-100 mt-1">สมาชิกที่แนะนำทางตรง (Direct Referrals)</p>
            </div>
        </div>
    </div>

    <!-- Team Statistics -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👤</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">สมาชิกทางตรง</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $directReferrals->total() }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
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
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
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
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
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
        </div>
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
                                @if($referral->status === 'active')
                                    <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">✅ ใช้งาน</span>
                                @elseif($referral->status === 'pending')
                                    <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">⏳ รออนุมัติ</span>
                                @elseif($referral->status === 'inactive')
                                    <span class="px-3 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white rounded-full">❌ ไม่ใช้งาน</span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">🚫 ระงับ</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($referral->rank)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">{{ $referral->rank->icon ?? '🏅' }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $referral->rank->name }}</span>
                                    </div>
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
@endsection

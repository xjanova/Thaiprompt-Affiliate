@extends('layouts.user-arrow-x')

@section('title', 'สมาชิกที่แนะนำ')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🤝</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">สมาชิกที่แนะนำทั้งหมด</h1>
                <p class="text-cyan-100 mt-1">รายชื่อสมาชิกที่คุณแนะนำเข้ามา</p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid md:grid-cols-3 gap-4">
        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">จำนวนทั้งหมด</div>
                    <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ $directReferrals->total() }}</div>
                </div>
            </div>
        </div>

        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">สมาชิกใช้งาน</div>
                    <div class="text-3xl font-bold text-green-600">
                        {{ $directReferrals->where('status', 'active')->count() }}
                    </div>
                </div>
            </div>
        </div>

        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📅</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">เพิ่มเดือนนี้</div>
                    <div class="text-3xl font-bold text-purple-600">
                        {{ $directReferrals->filter(fn($r) => $r->created_at->isCurrentMonth())->count() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Referrals List -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <span>📋</span> รายชื่อสมาชิก
            </h2>
            <a href="{{ route('user.mlm.referral') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                🔗 ลิงก์แนะนำ
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">#</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">ชื่อ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">รหัสสมาชิก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">วันที่เข้าร่วม</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($directReferrals as $index => $referral)
                        <tr class="hover:bg-gray-50 dark:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ ($directReferrals->currentPage() - 1) * $directReferrals->perPage() + $index + 1 }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ strtoupper(substr($referral->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $referral->user->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $referral->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded font-mono text-sm">
                                    {{ $referral->member_code }}
                                </code>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $referral->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $referral->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($referral->status === 'active')
                                    <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                        ✅ ใช้งาน
                                    </span>
                                @elseif($referral->status === 'pending')
                                    <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">
                                        ⏳ รออนุมัติ
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white rounded-full">
                                        ❌ ไม่ใช้งาน
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                                    ดูรายละเอียด
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <span class="text-4xl mb-4 block">🤝</span>
                                <p class="text-gray-600 dark:text-gray-400 mb-2">ยังไม่มีสมาชิกที่แนะนำ</p>
                                <a href="{{ route('user.mlm.referral') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                                    รับลิงก์แนะนำ →
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($directReferrals->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $directReferrals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

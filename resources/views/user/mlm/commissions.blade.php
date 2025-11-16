@extends('layouts.user-arrow-x')

@section('title', 'ค่าคอมมิชชั่น MLM')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">💰</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ค่าคอมมิชชั่น MLM</h1>
                <p class="text-green-100 mt-1">รายละเอียดรายได้จากระบบ MLM</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">⏳</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">รอดำเนินการ</div>
                    <div class="text-2xl font-bold text-yellow-600">฿{{ number_format($stats['pending'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">อนุมัติแล้ว</div>
                    <div class="text-2xl font-bold text-blue-600">฿{{ number_format($stats['approved'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">💸</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600 dark:text-gray-400">จ่ายแล้ว</div>
                    <div class="text-2xl font-bold text-green-600">฿{{ number_format($stats['paid'], 2) }}</div>
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
                    <div class="text-2xl font-bold text-purple-600">฿{{ number_format($stats['this_month'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <form method="GET" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="direct_referral" {{ request('type') === 'direct_referral' ? 'selected' : '' }}>Direct Referral</option>
                    <option value="binary_matching" {{ request('type') === 'binary_matching' ? 'selected' : '' }}>Binary Matching</option>
                    <option value="unilevel" {{ request('type') === 'unilevel' ? 'selected' : '' }}>Unilevel</option>
                    <option value="generation" {{ request('type') === 'generation' ? 'selected' : '' }}>Generation</option>
                    <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>

            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="flex-1 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('user.mlm.commissions') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 dark:text-gray-300 rounded-lg font-semibold transition-colors">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จาก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จำนวนเงิน</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($commissions as $commission)
                        <tr class="hover:bg-gray-50 dark:bg-gray-900/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $commission->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $commission->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full
                                    @if($commission->type === 'direct_referral') bg-blue-100 text-blue-800
                                    @elseif($commission->type === 'binary_matching') bg-purple-100 text-purple-800
                                    @elseif($commission->type === 'unilevel') bg-green-100 text-green-800
                                    @elseif($commission->type === 'generation') bg-orange-100 text-orange-800
                                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $commission->type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($commission->fromMember && $commission->fromMember->user)
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $commission->fromMember->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $commission->fromMember->member_code }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold text-green-600">฿{{ number_format($commission->commission_amount, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($commission->status === 'pending')
                                    <span class="px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">⏳ รอดำเนินการ</span>
                                @elseif($commission->status === 'approved')
                                    <span class="px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">✅ อนุมัติแล้ว</span>
                                @elseif($commission->status === 'paid')
                                    <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">💸 จ่ายแล้ว</span>
                                @elseif($commission->status === 'cancelled')
                                    <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">❌ ยกเลิก</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $commission->notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <span class="text-4xl mb-4 block">💰</span>
                                <p class="text-gray-600 dark:text-gray-400">ยังไม่มีค่าคอมมิชชั่น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($commissions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.admin-v3')

@section('title', 'ธุรกรรมทั้งหมด')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">ธุรกรรมทั้งหมดในระบบ</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ดูและจัดการธุรกรรมกระเป๋าเงินทั้งหมด</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.wallet.all') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                ← กลับ
            </a>
            <a href="{{ route('admin.wallet.export-transactions', request()->all()) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ธุรกรรมวันนี้</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_today']) }}</p>
                </div>
                <span class="text-3xl">📊</span>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ยอดวันนี้</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['volume_today'], 2) }}</p>
                </div>
                <span class="text-3xl">💰</span>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ number_format($stats['total_pending']) }}</p>
                </div>
                <span class="text-3xl">⏳</span>
            </div>
        </div>

        <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ล้มเหลว</p>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($stats['total_failed']) }}</p>
                </div>
                <span class="text-3xl">❌</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">กรองข้อมูล</h3>
        <form method="GET" action="{{ route('admin.wallet.all-transactions') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="รหัส, ชื่อ, อีเมล..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($transactionTypes as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['type'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="failed" {{ ($filters['status'] ?? '') == 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จากวันที่</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.wallet.all-transactions') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="glass-fusion rounded-xl shadow-lg overflow-hidden border border-white/20 dark:border-white/10">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รหัสธุรกรรม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวนเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดก่อน/หลัง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ Str::limit($transaction->transaction_id, 15) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 dark:text-indigo-300 font-bold">{{ substr($transaction->wallet->user->name ?? 'N', 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->wallet->user->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->wallet->user->email ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-xl mr-2">{{ $transaction->type_icon }}</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $transaction->type_label }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus', 'refund']) ? 'text-green-600' : 'text-red-600' }}">
                                {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus', 'refund']) ? '+' : '-' }}{{ number_format($transaction->amount, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="text-gray-500 dark:text-gray-400">{{ number_format($transaction->balance_before, 2) }}</div>
                            <div class="text-gray-900 dark:text-white font-medium">→ {{ number_format($transaction->balance_after, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($transaction->status == 'completed') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                @elseif($transaction->status == 'pending') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                @elseif($transaction->status == 'failed') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                @endif">
                                {{ $transaction->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.wallet.show', $transaction->wallet_id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                ดูกระเป๋า
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-gray-400 dark:text-gray-500">
                                <div class="text-5xl mb-4">📭</div>
                                <p class="text-lg">ไม่พบธุรกรรม</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
            {{ $transactions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

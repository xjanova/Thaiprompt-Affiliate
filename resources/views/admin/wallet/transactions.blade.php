@extends('layouts.admin-v3')

@section('title', 'ประวัติธุรกรรม')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ประวัติธุรกรรมทั้งหมด</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">กระเป๋าเงิน: {{ $wallet->wallet_address }}</p>
            </div>
            <a href="{{ route('admin.wallet.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>ฝากเงิน</option>
                    <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>ถอนเงิน</option>
                    <option value="transfer_in" {{ request('type') == 'transfer_in' ? 'selected' : '' }}>รับโอน</option>
                    <option value="transfer_out" {{ request('type') == 'transfer_out' ? 'selected' : '' }}>โอนออก</option>
                    <option value="commission" {{ request('type') == 'commission' ? 'selected' : '' }}>คอมมิชชั่น</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จากวันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.wallet.transactions') }}" class="px-6 py-2 bg-gray-100/50 dark:bg-gray-800/50 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl transition">
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="glass-fusion rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID ธุรกรรม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดก่อน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดหลัง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รายละเอียด</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $transaction->transaction_id }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">{{ $transaction->type_icon }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $transaction->type_label }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'bonus']) ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->formatted_amount }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($transaction->balance_before, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($transaction->balance_after, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $transaction->description }}</div>
                            @if($transaction->related_wallet_id)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ in_array($transaction->type, ['transfer_in']) ? 'จาก:' : 'ไปยัง:' }}
                                    {{ $transaction->relatedWallet->wallet_address ?? 'N/A' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800">
                                {{ $transaction->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center">
                            <div class="text-gray-400">
                                <span class="text-4xl block mb-2">📭</span>
                                <p class="text-sm">ไม่พบธุรกรรม</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
        <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 px-6 py-4">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

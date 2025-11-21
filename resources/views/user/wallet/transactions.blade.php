@extends('layouts.user-arrow-x')

@section('title', 'ประวัติธุรกรรม')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white dark:bg-gray-800 opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">📝 ประวัติธุรกรรม</h1>
            </div>
            <p class="text-indigo-100">รายการธุรกรรมทั้งหมดของกระเป๋าเงิน</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white dark:bg-gray-800 bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-indigo-100 text-sm mb-1">ยอดเงินคงเหลือปัจจุบัน</p>
                <p class="text-3xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <x-arrow-x.card-v3 class="p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">🔍 ตัวกรอง</h2>

        <form method="GET" action="{{ route('user.wallet.transactions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภทธุรกรรม</label>
                <select name="type"
                        class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>ฝากเงิน</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>ถอนเงิน</option>
                    <option value="transfer_in" {{ request('type') === 'transfer_in' ? 'selected' : '' }}>รับโอน</option>
                    <option value="transfer_out" {{ request('type') === 'transfer_out' ? 'selected' : '' }}>โอนออก</option>
                    <option value="commission" {{ request('type') === 'commission' ? 'selected' : '' }}>คอมมิชชั่น</option>
                    <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>คืนเงิน</option>
                    <option value="fee" {{ request('type') === 'fee' ? 'selected' : '' }}>ค่าธรรมเนียม</option>
                    <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>โบนัส</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มต้น</label>
                <input type="date"
                       name="date_from"
                       value="{{ request('date_from') }}"
                       class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                <input type="date"
                       name="date_to"
                       value="{{ request('date_to') }}"
                       class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    🔍 กรอง
                </button>
                <a href="{{ route('user.wallet.transactions') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-300 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">รายการธุรกรรม</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ทั้งหมด {{ $transactions->total() }} รายการ</p>
            </div>

            <button onclick="window.print()"
                    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold hover:bg-blue-200 transition">
                🖨️ พิมพ์
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">รหัสธุรกรรม</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">ประเภท</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">รายละเอียด</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">จำนวนเงิน</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">ยอดคงเหลือ</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">สถานะ</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr class="border-b border-gray-100 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                            <td class="py-4 px-4">
                                <p class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $transaction->transaction_id }}</p>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $transaction->type_icon }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $transaction->type_label }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->description }}</p>
                                @if($transaction->relatedWallet)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        @if($transaction->type === 'transfer_in')
                                            จาก: {{ $transaction->relatedWallet->user->name }}
                                        @elseif($transaction->type === 'transfer_out')
                                            ถึง: {{ $transaction->relatedWallet->user->name }}
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                <span class="text-sm font-bold {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus']) ? 'text-green-600' : 'text-red-600' }}">
                                    {{ in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus']) ? '+' : '-' }}
                                    ฿{{ number_format(abs($transaction->amount), 2) }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <p class="text-sm text-gray-900 dark:text-gray-100">฿{{ number_format($transaction->balance_after, 2) }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    จาก ฿{{ number_format($transaction->balance_before, 2) }}
                                </p>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800">
                                    {{ $transaction->status_label }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $transaction->created_at->format('H:i:s') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center">
                                <div class="text-gray-400">
                                    <span class="text-4xl block mb-2">📭</span>
                                    <p class="text-sm">ไม่พบรายการธุรกรรม</p>
                                    @if(request()->hasAny(['type', 'date_from', 'date_to']))
                                        <a href="{{ route('user.wallet.transactions') }}"
                                           class="inline-block mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-semibold">
                                            ล้างตัวกรอง
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-3xl">📥</span>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">รายรับทั้งหมด</p>
            <p class="text-2xl font-bold">฿{{ number_format($wallet->total_income, 2) }}</p>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-3xl">📤</span>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">รายจ่ายทั้งหมด</p>
            <p class="text-2xl font-bold">฿{{ number_format($wallet->total_expense, 2) }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-3xl">💰</span>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">ยอดคงเหลือ</p>
            <p class="text-2xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">⚡ การกระทำด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('user.wallet.deposit') }}"
               class="bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">💵</div>
                <p class="text-sm font-semibold">ฝากเงิน</p>
            </a>
            <a href="{{ route('user.wallet.withdraw') }}"
               class="bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">💸</div>
                <p class="text-sm font-semibold">ถอนเงิน</p>
            </a>
            <a href="{{ route('user.wallet.transfer') }}"
               class="bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">📤</div>
                <p class="text-sm font-semibold">โอนเงิน</p>
            </a>
            <a href="{{ route('user.wallet.withdrawals') }}"
               class="bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">📋</div>
                <p class="text-sm font-semibold">ประวัติถอน</p>
            </a>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .bg-gradient-to-r,
    .bg-gradient-to-br,
    .shadow-xl {
        background: white !important;
        box-shadow: none !important;
    }
}
</style>
@endpush
@endsection

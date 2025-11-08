@extends('layouts.admin')

@section('title', 'Crypto Transactions')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">🔄 Crypto Transactions</h1>
                <p class="text-cyan-100">รายการธุรกรรมสกุลเงินดิจิทัล</p>
            </div>
            <a href="{{ route('admin.crypto.dashboard') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-6 py-3 rounded-xl transition font-semibold">
                ← กลับ Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🔍 ตัวกรอง</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>📥 ฝากเงิน</option>
                    <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>📤 ถอนเงิน</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ รอดำเนินการ</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>✅ ยืนยันแล้ว</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>❌ ล้มเหลว</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สกุลเงิน</label>
                <select name="currency" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}" {{ request('currency') == $currency->code ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">TX Hash</label>
                <input type="text" name="tx_hash" value="{{ request('tx_hash') }}"
                    placeholder="ค้นหา TX Hash"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-cyan-500">
            </div>

            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-lg transition font-semibold shadow-lg">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.crypto.transactions') }}" class="px-6 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition font-semibold">
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                📊 รายการธุรกรรม ({{ $transactions->total() }} รายการ)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">มูลค่า (THB)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">TX Hash</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $transaction->user->name ?? 'ไม่ระบุ' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ID: {{ $transaction->user_id }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">{{ $transaction->type === 'deposit' ? '📥' : '📤' }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $transaction->type === 'deposit' ? 'ฝากเงิน' : 'ถอนเงิน' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold {{ $transaction->type === 'deposit' ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400' }}">
                                {{ $transaction->amount }} {{ $transaction->currency->code ?? '' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                ฿{{ number_format($transaction->amount_thb ?? 0, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                @if($transaction->tx_hash)
                                    <a href="#" class="hover:text-cyan-600 dark:hover:text-cyan-400" title="{{ $transaction->tx_hash }}">
                                        {{ substr($transaction->tx_hash, 0, 10) }}...{{ substr($transaction->tx_hash, -8) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                {{ $transaction->status === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $transaction->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $transaction->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $transaction->created_at->format('H:i:s') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.crypto.wallets', ['user_id' => $transaction->user_id]) }}"
                               class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-900 dark:hover:text-cyan-300 font-medium">
                                ดูกระเป๋า
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
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
        <div class="bg-gray-50 dark:bg-slate-900 px-6 py-4">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    <!-- Summary Stats -->
    @if($transactions->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">📥</div>
            <p class="text-white text-opacity-80 text-sm mb-1">ฝากเงินในหน้านี้</p>
            <p class="text-3xl font-bold">{{ $transactions->where('type', 'deposit')->count() }}</p>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">📤</div>
            <p class="text-white text-opacity-80 text-sm mb-1">ถอนเงินในหน้านี้</p>
            <p class="text-3xl font-bold">{{ $transactions->where('type', 'withdrawal')->count() }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="text-3xl mb-2">⏳</div>
            <p class="text-white text-opacity-80 text-sm mb-1">รอดำเนินการในหน้านี้</p>
            <p class="text-3xl font-bold">{{ $transactions->where('status', 'pending')->count() }}</p>
        </div>
    </div>
    @endif
</div>
@endsection

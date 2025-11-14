@extends('layouts.admin')

@section('title', 'TPIX Transactions')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                        <span class="text-5xl">💸</span>
                        <span>TPIX Transactions</span>
                    </h1>
                    <p class="text-purple-100">ธุรกรรมทั้งหมดในระบบ TPIX Blockchain</p>
                </div>
                <a href="{{ route('admin.tpix.dashboard') }}" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-semibold transition">
                    ← Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-2xl">
                    📊
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Transactions</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_count']) }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white text-2xl">
                    ✅
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Confirmed</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['confirmed_count']) }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center text-white text-2xl">
                    ⏳
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pending</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['pending_count']) }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-2xl">
                    💰
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Amount</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_amount'], 2) }} TPIX</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <form method="GET" action="{{ route('admin.tpix.transactions') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            {{-- Type Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Deposit</option>
                    <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
                    <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="swap" {{ request('type') == 'swap' ? 'selected' : '' }}>Swap</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จากวันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
            </div>

            {{-- Search --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="TX Hash, ที่อยู่..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
            </div>

            {{-- Submit --}}
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition">
                    ค้นหา
                </button>
                <a href="{{ route('admin.tpix.transactions') }}" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📋</span>
                <span>ธุรกรรม ({{ number_format($transactions->total()) }} รายการ)</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">TX Hash</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ใช้</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4">
                            @if($tx->tx_hash)
                            <div class="font-mono text-sm text-gray-900 dark:text-white">{{ Str::limit($tx->tx_hash, 16) }}</div>
                            @else
                            <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $typeIcons = ['deposit' => '📥', 'withdrawal' => '📤', 'transfer' => '🔄', 'swap' => '💱'];
                                $typeColors = [
                                    'deposit' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'withdrawal' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                    'transfer' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                    'swap' => 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$tx->type] ?? '' }}">
                                <span>{{ $typeIcons[$tx->type] ?? '💸' }}</span>
                                <span>{{ ucfirst($tx->type) }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $tx->user->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tx->user->email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($tx->amount, 4) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">TPIX</div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                    'confirmed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'failed' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tx->status] ?? '' }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $tx->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center text-gray-500 dark:text-gray-400">
                                <div class="text-6xl mb-4">💸</div>
                                <div class="text-lg font-semibold">ไม่พบธุรกรรม</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

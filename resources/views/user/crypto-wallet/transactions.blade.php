@extends('layouts.user-arrow-x')

@section('title', 'ประวัติธุรกรรมคริปโต')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('user.crypto-wallet.index') }}" class="text-amber-600 hover:text-amber-700 font-medium mb-2 inline-block">
            ← กลับไปหน้าหลัก
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">ประวัติธุรกรรม</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">ธุรกรรมคริปโตทั้งหมดของคุณ</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('user.crypto-wallet.transactions') }}" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>ฝาก</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>ถอน</option>
                    <option value="exchange_buy" {{ request('type') === 'exchange_buy' ? 'selected' : '' }}>ซื้อ (Exchange)</option>
                    <option value="exchange_sell" {{ request('type') === 'exchange_sell' ? 'selected' : '' }}>ขาย (Exchange)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สกุลเงิน</label>
                <select name="currency" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100">
                    <option value="">ทั้งหมด</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}" {{ request('currency') === $curr->code ? 'selected' : '' }}>{{ $curr->code }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-2 rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all">
                    🔍 กรอง
                </button>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                        <tr class="text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                            <th class="px-6 py-4">TX ID</th>
                            <th class="px-6 py-4">ประเภท</th>
                            <th class="px-6 py-4">สกุลเงิน</th>
                            <th class="px-6 py-4 text-right">จำนวน</th>
                            <th class="px-6 py-4">สถานะ</th>
                            <th class="px-6 py-4">เวลา</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($transactions as $tx)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs text-gray-600 dark:text-gray-400">
                                        {{ substr($tx->transaction_id, 0, 12) }}...
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-medium">
                                        @switch($tx->type)
                                            @case('deposit')
                                                📥 ฝาก
                                                @break
                                            @case('withdrawal')
                                                📤 ถอน
                                                @break
                                            @case('exchange_buy')
                                                🛒 ซื้อ
                                                @break
                                            @case('exchange_sell')
                                                💰 ขาย
                                                @break
                                            @default
                                                {{ $tx->type }}
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        @php
                                            $iconPath = public_path('icons/cryptocurrency/' . strtolower($tx->currency->code) . '.svg');
                                        @endphp
                                        @if(file_exists($iconPath))
                                            <img src="{{ asset('icons/cryptocurrency/' . strtolower($tx->currency->code) . '.svg') }}"
                                                 alt="{{ $tx->currency->code }}"
                                                 class="w-6 h-6">
                                        @endif
                                        <div>
                                            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $tx->currency->code }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tx->network }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="font-bold {{ $tx->is_incoming ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $tx->is_incoming ? '+' : '-' }}{{ number_format($tx->amount, 8) }}
                                    </div>
                                    @if($tx->amount_thb)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">≈ ฿{{ number_format($tx->amount_thb, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($tx->status === 'completed')
                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded text-xs font-medium">
                                            ✓ สำเร็จ
                                        </span>
                                    @elseif($tx->status === 'pending' || $tx->status === 'confirming')
                                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded text-xs font-medium">
                                            ⏳ รอดำเนินการ
                                        </span>
                                        @if($tx->confirmations > 0)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $tx->confirmations }}/{{ $tx->confirmations_required }} conf.
                                            </div>
                                        @endif
                                    @elseif($tx->status === 'failed')
                                        <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded text-xs font-medium">
                                            ✕ ล้มเหลว
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 rounded text-xs font-medium">
                                            {{ $tx->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div>{{ $tx->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $tx->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($tx->tx_hash && $tx->currency->explorer_url)
                                        <a href="{{ $tx->getExplorerUrl() }}" target="_blank"
                                           class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                                            🔗 Explorer
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t dark:border-gray-600">
                {{ $transactions->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📝</div>
                <p class="text-gray-500 dark:text-gray-400 mb-2">ไม่พบธุรกรรม</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">เริ่มต้นด้วยการฝากหรือแลกเปลี่ยนสกุลเงิน</p>
            </div>
        @endif
    </div>
</div>
@endsection

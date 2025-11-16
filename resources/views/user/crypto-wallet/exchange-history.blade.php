@extends('layouts.user-arrow-x')

@section('title', 'ประวัติการแลกเปลี่ยน')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('user.crypto-wallet.exchange') }}" class="text-amber-600 hover:text-amber-700 font-medium mb-2 inline-block">
            ← กลับไปหน้าแลกเปลี่ยน
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">ประวัติการแลกเปลี่ยน</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">ธุรกรรมแลกเปลี่ยน THB ↔ Crypto ทั้งหมด</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('user.crypto-wallet.exchange.history') }}" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100">
                    <option value="">ทั้งหมด</option>
                    <option value="buy" {{ request('type') === 'buy' ? 'selected' : '' }}>ซื้อ Crypto</option>
                    <option value="sell" {{ request('type') === 'sell' ? 'selected' : '' }}>ขาย Crypto</option>
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
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
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

    <!-- Exchange History List -->
    <div class="space-y-4">
        @forelse($exchanges as $exchange)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        @if($exchange->type === 'buy')
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white text-xl">
                                🛒
                            </div>
                        @else
                            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-rose-500 rounded-full flex items-center justify-center text-white text-xl">
                                💰
                            </div>
                        @endif

                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                                @if($exchange->type === 'buy')
                                    ซื้อ {{ $exchange->to_currency_code }}
                                @else
                                    ขาย {{ $exchange->from_currency_code }}
                                @endif
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $exchange->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <div>
                        @if($exchange->status === 'completed')
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-medium">
                                ✓ สำเร็จ
                            </span>
                        @elseif($exchange->status === 'pending')
                            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-sm font-medium">
                                ⏳ รอดำเนินการ
                            </span>
                        @elseif($exchange->status === 'failed')
                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-sm font-medium">
                                ✕ ล้มเหลว
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Exchange Summary -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div class="text-center flex-1">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">จาก</div>
                            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                                {{ number_format($exchange->from_amount, $exchange->from_currency_type === 'crypto' ? 8 : 2) }}
                            </div>
                            <div class="flex items-center justify-center gap-2 mt-2">
                                @php
                                    $fromCode = strtolower($exchange->from_currency_code);
                                    $fromIconPath = public_path('icons/cryptocurrency/' . $fromCode . '.svg');
                                @endphp
                                @if($exchange->from_currency_type === 'crypto' && file_exists($fromIconPath))
                                    <img src="{{ asset('icons/cryptocurrency/' . $fromCode . '.svg') }}"
                                         alt="{{ $exchange->from_currency_code }}"
                                         class="w-5 h-5">
                                @endif
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $exchange->from_currency_code }}
                                </div>
                            </div>
                        </div>

                        <div class="px-4">
                            <div class="text-2xl">→</div>
                        </div>

                        <div class="text-center flex-1">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ไปยัง</div>
                            <div class="text-2xl font-bold {{ $exchange->type === 'buy' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}">
                                {{ number_format($exchange->to_amount, $exchange->to_currency_type === 'crypto' ? 8 : 2) }}
                            </div>
                            <div class="flex items-center justify-center gap-2 mt-2">
                                @php
                                    $toCode = strtolower($exchange->to_currency_code);
                                    $toIconPath = public_path('icons/cryptocurrency/' . $toCode . '.svg');
                                @endphp
                                @if($exchange->to_currency_type === 'crypto' && file_exists($toIconPath))
                                    <img src="{{ asset('icons/cryptocurrency/' . $toCode . '.svg') }}"
                                         alt="{{ $exchange->to_currency_code }}"
                                         class="w-5 h-5">
                                @endif
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $exchange->to_currency_code }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600 dark:text-gray-400">อัตราแลกเปลี่ยน:</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                1 {{ $exchange->to_currency_type === 'crypto' ? $exchange->to_currency_code : $exchange->from_currency_code }}
                                = ฿{{ number_format($exchange->exchange_rate, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียม:</span>
                            <span class="text-red-600 dark:text-red-400">
                                {{ number_format($exchange->fee_amount, 2) }} {{ $exchange->fee_currency }}
                                ({{ number_format($exchange->fee_percentage, 2) }}%)
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Exchange ID:</span>
                            <span class="font-mono text-xs text-gray-800 dark:text-gray-100">
                                {{ substr($exchange->exchange_id, 0, 16) }}...
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">เวลาทำรายการ:</span>
                            <span class="text-gray-800 dark:text-gray-100">
                                {{ $exchange->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Final Amount -->
                <div class="mt-4 pt-4 border-t dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">
                            @if($exchange->type === 'buy')
                                จำนวนที่ได้รับ:
                            @else
                                จำนวนที่ได้รับ (หลังหักค่าธรรมเนียม):
                            @endif
                        </span>
                        <span class="text-xl font-bold {{ $exchange->type === 'buy' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}">
                            {{ number_format($exchange->final_to_amount, $exchange->to_currency_type === 'crypto' ? 8 : 2) }}
                            {{ $exchange->to_currency_code }}
                        </span>
                    </div>
                </div>

                <!-- Error Message -->
                @if($exchange->status === 'failed' && $exchange->error_message)
                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="text-sm font-medium text-red-900 dark:text-red-300 mb-1">ข้อผิดพลาด:</div>
                        <div class="text-sm text-red-800 dark:text-red-400">{{ $exchange->error_message }}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
                <div class="text-6xl mb-4">💱</div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีประวัติการแลกเปลี่ยน</p>
                <a href="{{ route('user.crypto-wallet.exchange') }}"
                   class="inline-block bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-3 rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all">
                    เริ่มแลกเปลี่ยน
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($exchanges->hasPages())
        <div class="mt-6">
            {{ $exchanges->links() }}
        </div>
    @endif
</div>
@endsection

@extends('layouts.user-arrow-x')

@section('title', 'แลกเปลี่ยน THB ↔ Crypto')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    {{-- Premium Hero Header (Purple-Pink for Exchange) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-fuchsia-600 dark:from-purple-800 dark:via-pink-800 dark:to-fuchsia-800 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-sync-alt"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-exchange-alt text-3xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                            💱 แลกเปลี่ยนสกุลเงิน
                        </h1>
                        <p class="text-purple-100 mt-1">
                            ซื้อ/ขาย Crypto ด้วยบาทไทย
                        </p>
                    </div>
                </div>
                <a href="{{ route('user.crypto-wallet.index') }}"
                   class="glass-fusion hover:bg-white/30 rounded-lg px-4 py-2 text-white font-medium transition-all flex items-center gap-2 justify-center lg:justify-start">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden md:inline">กลับหน้าหลัก</span>
                </a>
            </div>

            {{-- Quick Balance Display --}}
            <div class="grid md:grid-cols-2 gap-4 mt-6">
                <div class="glass-fusion rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-xl">฿</span>
                        </div>
                        <div>
                            <p class="text-purple-100 text-xs font-medium">กระเป๋าบาท (THB)</p>
                            <p class="text-white text-xl font-bold drop-shadow-lg">฿{{ number_format($thbWallet->balance ?? 0, 2) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('user.wallet.index') }}" class="text-purple-100 hover:text-white text-xs">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="glass-fusion rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-xl">₿</span>
                        </div>
                        <div>
                            <p class="text-purple-100 text-xs font-medium">กระเป๋าคริปโต</p>
                            <p class="text-white text-xl font-bold drop-shadow-lg">{{ count($cryptoBalances) }} สกุล</p>
                        </div>
                    </div>
                    <a href="{{ route('user.crypto-wallet.index') }}" class="text-purple-100 hover:text-white text-xs">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-400 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Exchange Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden" x-data="{ tab: 'buy' }">
        <!-- Tabs Header -->
        <div class="flex border-b dark:border-gray-700">
            <button @click="tab = 'buy'"
                    :class="tab === 'buy' ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-b-2 border-green-600' : 'text-gray-600 dark:text-gray-400'"
                    class="flex-1 py-4 font-bold transition-all">
                🛒 ซื้อ Crypto
            </button>
            <button @click="tab = 'sell'"
                    :class="tab === 'sell' ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-b-2 border-red-600' : 'text-gray-600 dark:text-gray-400'"
                    class="flex-1 py-4 font-bold transition-all">
                💰 ขาย Crypto
            </button>
        </div>

        <!-- Buy Tab -->
        <div x-show="tab === 'buy'" class="p-6">
            <form action="{{ route('user.crypto-wallet.exchange.buy') }}" method="POST">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกสกุลเงินที่ต้องการซื้อ</label>
                    <select name="currency_id" required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">-- เลือกสกุลเงิน --</option>
                        @foreach($currencies as $curr)
                            @if($curr->exchange_enabled)
                                <option value="{{ $curr->id }}">
                                    {{ $curr->code }} ({{ $curr->name }})
                                    @if(isset($prices[$curr->code]))
                                        - ราคา ฿{{ number_format($prices[$curr->code]['buy_price'], 2) }}
                                    @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินบาทที่ต้องการใช้</label>
                    <div class="relative">
                        <input type="number" name="thb_amount" step="0.01" required min="1"
                               class="w-full px-4 py-3 pr-16 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="0.00">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">THB</div>
                    </div>
                    @if($thbWallet)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">มีอยู่: ฿{{ number_format($thbWallet->balance, 2) }}</p>
                    @endif
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PIN กระเป๋าเงินบาท</label>
                    <input type="password" name="pin" required minlength="4" maxlength="6"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-gray-100"
                           placeholder="กรอก PIN">
                </div>

                <div class="mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="accept_terms" required class="mt-1 mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            ฉันยอมรับว่าราคาอาจเปลี่ยนแปลงได้ และ ค่าธรรมเนียมการแลกเปลี่ยนจะถูกหักออกจากยอดเงิน
                        </span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-4 rounded-lg font-bold text-lg hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg">
                    🛒 ซื้อ Crypto ด้วยบาท
                </button>
            </form>
        </div>

        <!-- Sell Tab -->
        <div x-show="tab === 'sell'" class="p-6">
            <form action="{{ route('user.crypto-wallet.exchange.sell') }}" method="POST">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกสกุลเงินที่ต้องการขาย</label>
                    <select name="currency_id" required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">-- เลือกสกุลเงิน --</option>
                        @foreach($currencies as $curr)
                            @if($curr->exchange_enabled && isset($cryptoBalances[$curr->code]) && $cryptoBalances[$curr->code]['balance'] > 0)
                                <option value="{{ $curr->id }}">
                                    {{ $curr->code }} - มี {{ number_format($cryptoBalances[$curr->code]['balance'], 8) }}
                                    @if(isset($prices[$curr->code]))
                                        (ราคา ฿{{ number_format($prices[$curr->code]['sell_price'], 2) }})
                                    @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวน Crypto ที่ต้องการขาย</label>
                    <input type="number" name="crypto_amount" step="0.00000001" required min="0.00000001"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-gray-100"
                           placeholder="0.00000000">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PIN กระเป๋าคริปโต</label>
                    <input type="password" name="pin" required minlength="4" maxlength="6"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-gray-100"
                           placeholder="กรอก PIN">
                </div>

                <div class="mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="accept_terms" required class="mt-1 mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            ฉันยอมรับว่าราคาอาจเปลี่ยนแปลงได้ และ ค่าธรรมเนียมการแลกเปลี่ยนจะถูกหักออกจากยอดเงินบาทที่ได้รับ
                        </span>
                    </label>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-red-500 to-rose-600 text-white py-4 rounded-lg font-bold text-lg hover:from-red-600 hover:to-rose-700 transition-all shadow-lg">
                    💰 ขาย Crypto รับบาท
                </button>
            </form>
        </div>
    </div>

    <!-- Price Table -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">ราคาปัจจุบัน</h3>
            <a href="{{ route('user.crypto-wallet.exchange.history') }}" class="text-amber-600 hover:text-amber-700 text-sm font-medium">
                ประวัติการแลกเปลี่ยน →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b dark:border-gray-700">
                    <tr class="text-left text-sm text-gray-600 dark:text-gray-400">
                        <th class="pb-3">สกุลเงิน</th>
                        <th class="pb-3 text-right">ราคาซื้อ</th>
                        <th class="pb-3 text-right">ราคาขาย</th>
                        <th class="pb-3 text-right">เปลี่ยนแปลง 24h</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($currencies->take(5) as $curr)
                        @if(isset($prices[$curr->code]))
                            <tr class="text-sm">
                                <td class="py-3">
                                    <div class="flex items-center space-x-3">
                                        @php
                                            $iconPath = public_path('icons/cryptocurrency/' . strtolower($curr->code) . '.svg');
                                        @endphp
                                        @if(file_exists($iconPath))
                                            <img src="{{ asset('icons/cryptocurrency/' . strtolower($curr->code) . '.svg') }}"
                                                 alt="{{ $curr->code }}"
                                                 class="w-8 h-8">
                                        @endif
                                        <div>
                                            <div class="font-bold text-gray-800 dark:text-gray-100">{{ $curr->code }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $curr->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-right font-semibold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($prices[$curr->code]['buy_price'], 2) }}
                                </td>
                                <td class="py-3 text-right font-semibold text-red-600 dark:text-red-400">
                                    ฿{{ number_format($prices[$curr->code]['sell_price'], 2) }}
                                </td>
                                <td class="py-3 text-right">
                                    @if(isset($prices[$curr->code]['change_24h']))
                                        <span class="{{ $prices[$curr->code]['change_24h'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $prices[$curr->code]['change_24h'] >= 0 ? '+' : '' }}{{ number_format($prices[$curr->code]['change_24h'], 2) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h4 class="font-bold text-blue-900 dark:text-blue-300 mb-2">ข้อมูลการแลกเปลี่ยน</h4>
        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300 text-sm">
            <li>ราคาอ้างอิงจากตลาดโลก อัพเดททุก 5 นาที</li>
            <li>ค่าธรรมเนียมการแลกเปลี่ยนแตกต่างกันไปตามสกุลเงิน</li>
            <li>การแลกเปลี่ยนจะเสร็จสิ้นทันที ไม่ต้องรอยืนยันจาก Blockchain</li>
            <li>ยอดเงินจะถูกโอนเข้ากระเป๋าที่เลือกโดยอัตโนมัติ</li>
        </ul>
    </div>
</div>
@endsection

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush

@extends('layouts.user')

@section('title', 'แลกเปลี่ยน THB ↔ Crypto')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('user.crypto-wallet.index') }}" class="text-amber-600 hover:text-amber-700 font-medium mb-2 inline-block">
            ← กลับไปหน้าหลัก
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">แลกเปลี่ยนสกุลเงิน</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">ซื้อ/ขาย Crypto ด้วยบาทไทย</p>
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

    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <!-- THB Balance -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-sm font-medium mb-2 opacity-90">กระเป๋าเงินบาท (THB)</h3>
            <div class="text-3xl font-bold mb-1">฿{{ number_format($thbWallet->balance ?? 0, 2) }}</div>
            <a href="{{ route('user.wallet.index') }}" class="text-xs text-indigo-100 hover:text-white">จัดการกระเป๋า →</a>
        </div>

        <!-- Crypto Balance -->
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-sm font-medium mb-2 opacity-90">กระเป๋าคริปโต</h3>
            <div class="text-3xl font-bold mb-1">
                {{ count($cryptoBalances) }} สกุล
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="text-xs text-amber-100 hover:text-white">ดูรายละเอียด →</a>
        </div>
    </div>

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
                                    <div class="font-bold text-gray-800 dark:text-gray-100">{{ $curr->code }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $curr->name }}</div>
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

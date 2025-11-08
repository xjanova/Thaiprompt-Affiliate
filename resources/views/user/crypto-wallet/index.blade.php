@extends('layouts.user')

@section('title', 'กระเป๋าคริปโต')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">กระเป๋าคริปโต</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการเหรียญคริปโตของคุณ</p>
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

    <!-- Total Balance Card -->
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-8 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-amber-100 text-sm font-medium mb-2">ยอดรวมทั้งหมด</p>
                <h2 class="text-4xl font-bold">฿{{ number_format($totalValueTHB, 2) }} THB</h2>
                <p class="text-amber-100 text-sm mt-2">≈ ${{ number_format($totalValueTHB / 33, 2) }} USD</p>
            </div>
            <div class="text-6xl">
                ₿
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
            <a href="{{ route('user.crypto-wallet.deposit') }}"
               class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 text-center transition-all">
                <div class="text-2xl mb-1">📥</div>
                <div class="text-sm font-medium">ฝากเหรียญ</div>
            </a>
            <a href="{{ route('user.crypto-wallet.withdraw') }}"
               class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 text-center transition-all">
                <div class="text-2xl mb-1">📤</div>
                <div class="text-sm font-medium">ถอนเหรียญ</div>
            </a>
            <a href="{{ route('user.crypto-wallet.exchange') }}"
               class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 text-center transition-all">
                <div class="text-2xl mb-1">💱</div>
                <div class="text-sm font-medium">แลกเปลี่ยน</div>
            </a>
            <a href="{{ route('user.crypto-wallet.transactions') }}"
               class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 text-center transition-all">
                <div class="text-2xl mb-1">📝</div>
                <div class="text-sm font-medium">ธุรกรรม</div>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Crypto Balances -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">ยอดคงเหลือ</h3>

            @if($wallet && count($balances) > 0)
                <div class="space-y-3">
                    @foreach($balances as $code => $balance)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center">
                                @php
                                    $iconPath = public_path('icons/cryptocurrency/' . strtolower($code) . '.svg');
                                @endphp
                                @if(file_exists($iconPath))
                                    <img src="{{ asset('icons/cryptocurrency/' . strtolower($code) . '.svg') }}"
                                         alt="{{ $code }}"
                                         class="w-10 h-10 mr-3">
                                @else
                                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                        {{ substr($code, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $balance['currency']->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $code }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($balance['balance'], 8) }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">≈ ฿{{ number_format($balance['balance_thb'], 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="text-6xl mb-4">🪙</div>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มียอดคงเหลือ</p>
                    <a href="{{ route('user.crypto-wallet.deposit') }}"
                       class="inline-block bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-2 rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all">
                        ฝากเหรียญ
                    </a>
                </div>
            @endif
        </div>

        <!-- Wallet Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">กระเป๋าของฉัน</h3>

            @if($wallet)
                <div class="space-y-4">
                    <div class="p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-gray-700 dark:to-gray-600 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">ชื่อกระเป๋า</span>
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ $wallet->name }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">ประเภท</span>
                            <span class="px-3 py-1 bg-white dark:bg-gray-800 rounded-full text-sm font-medium">
                                {{ $wallet->wallet_type === 'custodial' ? '🔐 Custodial' : '🦊 External' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-700 dark:text-gray-300">สถานะ</span>
                            <span class="px-3 py-1 {{ $wallet->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }} rounded-full text-sm font-medium">
                                {{ $wallet->status === 'active' ? '✓ ใช้งาน' : '✕ ไม่ใช้งาน' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('user.crypto-wallet.wallets') }}"
                           class="flex-1 text-center bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                            จัดการกระเป๋า
                        </a>
                    </div>
                </div>
            @else
                <div class="text-center py-6">
                    <div class="text-5xl mb-3">🆕</div>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">คุณยังไม่มีกระเป๋าคริปโต</p>
                    <button onclick="document.getElementById('createWalletModal').classList.remove('hidden')"
                            class="bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-2 rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all">
                        สร้างกระเป๋าใหม่
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">ธุรกรรมล่าสุด</h3>
            <a href="{{ route('user.crypto-wallet.transactions') }}" class="text-amber-600 hover:text-amber-700 font-medium text-sm">
                ดูทั้งหมด →
            </a>
        </div>

        @if($recentTransactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b dark:border-gray-700">
                        <tr class="text-left text-sm text-gray-600 dark:text-gray-400">
                            <th class="pb-3">ประเภท</th>
                            <th class="pb-3">สกุลเงิน</th>
                            <th class="pb-3">จำนวน</th>
                            <th class="pb-3">สถานะ</th>
                            <th class="pb-3">เวลา</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($recentTransactions as $tx)
                            <tr class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="py-3">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                        {{ $tx->type }}
                                    </span>
                                </td>
                                <td class="py-3 font-medium">{{ $tx->currency->code }}</td>
                                <td class="py-3 font-semibold {{ $tx->is_incoming ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $tx->is_incoming ? '+' : '-' }}{{ number_format($tx->amount, 8) }}
                                </td>
                                <td class="py-3">
                                    @if($tx->status === 'completed')
                                        <span class="text-green-600 dark:text-green-400">✓ สำเร็จ</span>
                                    @elseif($tx->status === 'pending')
                                        <span class="text-yellow-600 dark:text-yellow-400">⏳ รอดำเนินการ</span>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400">{{ $tx->status }}</span>
                                    @endif
                                </td>
                                <td class="py-3 text-gray-500 dark:text-gray-400">
                                    {{ $tx->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-5xl mb-3">📝</div>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีธุรกรรม</p>
            </div>
        @endif
    </div>
</div>

<!-- Create Wallet Modal -->
<div id="createWalletModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">สร้างกระเป๋าคริปโต</h3>

        <form action="{{ route('user.crypto-wallet.create-wallet') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อกระเป๋า</label>
                <input type="text" name="name" value="My Crypto Wallet" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PIN (4-6 หลัก)</label>
                <input type="password" name="pin" required minlength="4" maxlength="6"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100"
                       placeholder="ตั้ง PIN สำหรับกระเป๋านี้">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยืนยัน PIN</label>
                <input type="password" name="pin_confirmation" required minlength="4" maxlength="6"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100"
                       placeholder="ยืนยัน PIN อีกครั้ง">
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-4">
                <p class="text-sm text-yellow-800 dark:text-yellow-300">
                    ⚠️ กรุณาจดจำ PIN ของคุณ จะต้องใช้ทุกครั้งที่ทำธุรกรรม
                </p>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('createWalletModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-lg hover:from-amber-600 hover:to-orange-700 transition-all">
                    สร้างกระเป๋า
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.user-arrow-x')

@section('title', 'จัดการกระเป๋าคริปโต')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('user.crypto-wallet.index') }}" class="text-amber-600 hover:text-amber-700 font-medium mb-2 inline-block">
            ← กลับไปหน้าหลัก
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">จัดการกระเป๋าคริปโต</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">กระเป๋าทั้งหมดของคุณ</p>
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

    <!-- Add New Wallet -->
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <button onclick="document.getElementById('createWalletModal').classList.remove('hidden')"
                class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl shadow-lg p-6 transition-all text-left">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl mr-4">
                    🆕
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-1">สร้างกระเป๋าใหม่</h3>
                    <p class="text-sm text-green-100">Custodial Wallet - ระบบจัดการให้</p>
                </div>
            </div>
        </button>

        <button onclick="alert('ฟีเจอร์นี้จะพร้อมใช้งานเร็วๆ นี้')"
                class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-xl shadow-lg p-6 transition-all text-left">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl mr-4">
                    🦊
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-1">เชื่อมต่อกระเป๋า</h3>
                    <p class="text-sm text-blue-100">MetaMask, WalletConnect</p>
                </div>
            </div>
        </button>
    </div>

    <!-- Wallets List -->
    <div class="grid gap-6">
        @forelse($wallets as $wallet)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r {{ $wallet->is_default ? 'from-amber-500 to-orange-600' : 'from-gray-500 to-gray-600' }} p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-2xl font-bold">{{ $wallet->name }}</h3>
                                @if($wallet->is_default)
                                    <span class="px-2 py-1 bg-white/20 rounded text-xs font-medium">กระเป๋าหลัก</span>
                                @endif
                            </div>
                            <p class="text-sm opacity-90">
                                {{ $wallet->wallet_type === 'custodial' ? '🔐 Custodial Wallet' : '🦊 External Wallet' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm opacity-90 mb-1">สถานะ</div>
                            @if($wallet->status === 'active')
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✓ ใช้งาน</span>
                            @elseif($wallet->status === 'locked')
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">🔒 ล็อค</span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">{{ $wallet->status }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <!-- Addresses -->
                    <h4 class="font-bold text-gray-800 dark:text-gray-100 mb-3">ที่อยู่กระเป๋า (Addresses)</h4>

                    @if($wallet->cryptoAddresses->count() > 0)
                        <div class="space-y-2 mb-4">
                            @foreach($wallet->cryptoAddresses->take(5) as $address)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center flex-1 mr-4">
                                        @php
                                            $iconPath = public_path('icons/cryptocurrency/' . strtolower($address->currency->code) . '.svg');
                                        @endphp
                                        @if(file_exists($iconPath))
                                            <img src="{{ asset('icons/cryptocurrency/' . strtolower($address->currency->code) . '.svg') }}"
                                                 alt="{{ $address->currency->code }}"
                                                 class="w-10 h-10 mr-3">
                                        @else
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold mr-3"
                                                 style="background: {{ $address->currency->color ?? 'linear-gradient(135deg, #F59E0B, #D97706)' }};">
                                                {{ substr($address->currency->code, 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $address->currency->code }}</div>
                                            <div class="font-mono text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $address->address }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($address->balance, 8) }}</div>
                                        @if($address->balance > 0)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">≈ ฿{{ number_format($address->balance_in_thb, 2) }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 mb-4">
                            <div class="text-4xl mb-2">📭</div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีที่อยู่ในกระเป๋านี้</p>
                        </div>
                    @endif

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $wallet->cryptoAddresses->count() }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Addresses</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $wallet->total_transactions }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Transactions</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                                {{ $wallet->last_activity_at ? $wallet->last_activity_at->diffForHumans() : '-' }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Last Activity</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        @if(!$wallet->is_default)
                            <form action="{{ route('user.crypto-wallet.wallet.set-default', $wallet->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg font-medium hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-all">
                                    ⭐ ตั้งเป็นกระเป๋าหลัก
                                </button>
                            </form>
                        @endif

                        @if($wallet->wallet_type === 'custodial')
                            <button onclick="alert('ฟีเจอร์ Export Private Key จะพร้อมใช้งานเร็วๆ นี้')"
                                    class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                🔑 Export Key
                            </button>
                        @endif

                        <form action="{{ route('user.crypto-wallet.wallet.delete', $wallet->id) }}" method="POST"
                              onsubmit="return confirm('คุณแน่ใจที่จะลบกระเป๋านี้? (ต้องไม่มียอดเงิน)')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition-all">
                                🗑️ ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
                <div class="text-6xl mb-4">👛</div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">คุณยังไม่มีกระเป๋าคริปโต</p>
                <button onclick="document.getElementById('createWalletModal').classList.remove('hidden')"
                        class="bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-3 rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all">
                    สร้างกระเป๋าใหม่
                </button>
            </div>
        @endforelse
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

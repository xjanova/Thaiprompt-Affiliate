@extends('layouts.admin-v3')

@section('title', 'Wallets ของผู้ใช้: ' . $user->name)

@section('content')
<div class="space-y-6">

    <!-- Navigation Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
        <a href="{{ route('admin.crypto.hd-wallets.index') }}" class="hover:text-emerald-600 transition-colors">HD Wallets</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('admin.users.index') }}" class="hover:text-emerald-600 transition-colors">Users</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-semibold">User Wallets</span>
    </div>

    <!-- User Profile Header -->
    <div class="bg-gradient-to-r from-purple-500 via-pink-600 to-red-500 rounded-3xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 glass-fusion rounded-full -mr-48 -mt-48" border border-white/20 dark:border-white/10></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 glass-fusion rounded-full -ml-32 -mb-32" border border-white/20 dark:border-white/10></div>
        </div>
        <div class="relative">
            <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex items-center space-x-2 px-4 py-2 glass-fusion hover:glass-fusion backdrop-blur-sm rounded-xl mb-6 transition-all">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปที่โปรไฟล์ผู้ใช้</span>
            </a>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <!-- User Avatar -->
                    <div class="w-24 h-24 glass-fusion backdrop-blur-sm rounded-full flex items-center justify-center border-4 border-white/30" border border-white/20 dark:border-white/10>
                        <span class="text-5xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                    <!-- User Info -->
                    <div>
                        <h1 class="text-4xl font-bold mb-2">{{ $user->name }}</h1>
                        <p class="text-purple-100 text-lg mb-3">{{ $user->email }}</p>
                        <div class="flex items-center space-x-4">
                            <span class="px-4 py-2 glass-fusion backdrop-blur-sm rounded-xl text-sm font-semibold">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                สมัครเมื่อ: {{ $user->created_at->format('d/m/Y') }}
                            </span>
                            <span class="px-4 py-2 glass-fusion backdrop-blur-sm rounded-xl text-sm font-semibold">
                                <i class="fas fa-clock mr-2"></i>
                                {{ $user->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>
                <!-- User ID Badge -->
                <div class="hidden lg:block text-center">
                    <div class="w-24 h-24 glass-fusion backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center border-2 border-white/30" border border-white/20 dark:border-white/10>
                        <p class="text-purple-100 text-xs mb-1">User ID</p>
                        <p class="text-3xl font-bold">{{ $user->id }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Statistics -->
    @if($statistics)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <i class="fas fa-crown text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">{{ $masterWallet ? 1 : 0 }}</p>
            <p class="text-yellow-100 text-sm">Master Wallet</p>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <i class="fas fa-layer-group text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">{{ $statistics['child_wallets'] ?? $childWallets->count() }}</p>
            <p class="text-emerald-100 text-sm">Child Wallets</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <i class="fas fa-coins text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">฿{{ number_format($statistics['balance_thb'] ?? 0, 2) }}</p>
            <p class="text-blue-100 text-sm">Total Balance</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">{{ number_format($statistics['total_transactions'] ?? 0) }}</p>
            <p class="text-purple-100 text-sm">Transactions</p>
        </div>
    </div>
    @endif

    <!-- Master Wallet Section -->
    @if($masterWallet)
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 px-6 py-4">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-crown mr-3"></i>
                Master Wallet
            </h2>
        </div>
        <div class="p-8">
            <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-2xl p-6 border-2 border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full flex items-center justify-center shadow-lg">
                                <i class="fas fa-crown text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $masterWallet->name }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Master Wallet ID: {{ $masterWallet->id }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mt-6">
                            <div class="text-center p-4 glass-fusion dark:bg-gray-700 rounded-xl" border border-white/20 dark:border-white/10>
                                <i class="fas fa-layer-group text-2xl text-emerald-600 mb-2"></i>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $masterWallet->total_derived_wallets }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">Total Derived</p>
                            </div>
                            <div class="text-center p-4 glass-fusion dark:bg-gray-700 rounded-xl" border border-white/20 dark:border-white/10>
                                <i class="fas fa-hashtag text-2xl text-blue-600 mb-2"></i>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $masterWallet->derivation_index ?? 0 }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">Derivation Index</p>
                            </div>
                            <div class="text-center p-4 glass-fusion dark:bg-gray-700 rounded-xl" border border-white/20 dark:border-white/10>
                                @if($masterWallet->status === 'active')
                                    <i class="fas fa-check-circle text-2xl text-green-600 mb-2"></i>
                                    <p class="text-lg font-bold text-green-700 dark:text-green-400">Active</p>
                                @elseif($masterWallet->status === 'locked')
                                    <i class="fas fa-lock text-2xl text-yellow-600 mb-2"></i>
                                    <p class="text-lg font-bold text-yellow-700 dark:text-yellow-400">Locked</p>
                                @else
                                    <i class="fas fa-ban text-2xl text-red-600 mb-2"></i>
                                    <p class="text-lg font-bold text-red-700 dark:text-red-400">Suspended</p>
                                @endif
                                <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">Status</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('admin.crypto.hd-wallets.show', $masterWallet->id) }}" class="px-8 py-4 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transition-all inline-flex items-center">
                            <i class="fas fa-eye mr-2"></i>
                            ดูรายละเอียด
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center" border border-white/20 dark:border-white/10>
        <i class="fas fa-crown text-6xl text-gray-300 dark:text-gray-600 dark:text-gray-400 mb-6"></i>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Master Wallet</h3>
        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-6">ผู้ใช้รายนี้ยังไม่มี Master Wallet</p>
        <button class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
            <i class="fas fa-plus mr-2"></i>สร้าง Master Wallet
        </button>
    </div>
    @endif

    <!-- Child Wallets Section -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-emerald-500 to-green-600 px-6 py-4 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-layer-group mr-3"></i>
                Child Wallets ({{ $childWallets->count() }})
            </h2>
            @if($masterWallet)
            <button class="px-4 py-2 glass-fusion hover:glass-fusion backdrop-blur-sm text-white font-semibold rounded-xl transition-all">
                <i class="fas fa-plus mr-2"></i>สร้าง Child Wallet ใหม่
            </button>
            @endif
        </div>

        @if($childWallets->count() > 0)
        <div class="p-6">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($childWallets as $wallet)
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl p-6 border-2 border-transparent hover:border-emerald-500 transition-all transform hover:scale-[1.02]">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                {{ $wallet->derivation_index }}
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $wallet->name }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Index: {{ $wallet->derivation_index }}</p>
                            </div>
                        </div>
                        <!-- Status Dot -->
                        @if($wallet->status === 'active')
                            <div class="w-3 h-3 bg-green-500 rounded-full shadow-lg animate-pulse"></div>
                        @elseif($wallet->status === 'locked')
                            <div class="w-3 h-3 bg-yellow-500 rounded-full shadow-lg"></div>
                        @else
                            <div class="w-3 h-3 bg-red-500 rounded-full shadow-lg"></div>
                        @endif
                    </div>

                    <!-- Balance -->
                    <div class="mb-4 p-4 glass-fusion dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 dark:border-gray-600" border border-white/20 dark:border-white/10>
                        <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-1">ยอดคงเหลือ</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">฿0.00</p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <i class="fas fa-link text-blue-600 text-lg mb-1"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $wallet->cryptoAddresses->count() }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">Addresses</p>
                        </div>
                        <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <i class="fas fa-exchange-alt text-purple-600 text-lg mb-1"></i>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">0</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">Txns</p>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="mb-4">
                        @if($wallet->status === 'active')
                            <span class="block text-center px-4 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-xl text-sm font-semibold">
                                <i class="fas fa-check-circle mr-1"></i> ใช้งานอยู่
                            </span>
                        @elseif($wallet->status === 'locked')
                            <span class="block text-center px-4 py-2 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-xl text-sm font-semibold">
                                <i class="fas fa-lock mr-1"></i> ล็อกอยู่
                            </span>
                        @else
                            <span class="block text-center px-4 py-2 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-xl text-sm font-semibold">
                                <i class="fas fa-ban mr-1"></i> ระงับ
                            </span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('admin.crypto.hd-wallets.show', $wallet->id) }}" class="text-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl transition-all text-sm">
                            <i class="fas fa-eye mr-1"></i>ดู
                        </a>
                        @if($wallet->status === 'active')
                        <button onclick="lockWallet({{ $wallet->id }})" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-xl transition-all text-sm">
                            <i class="fas fa-lock mr-1"></i>ล็อก
                        </button>
                        @else
                        <button onclick="unlockWallet({{ $wallet->id }})" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-xl transition-all text-sm">
                            <i class="fas fa-unlock mr-1"></i>ปลดล็อก
                        </button>
                        @endif
                    </div>

                    <!-- Created Date -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-600">
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 text-center">
                            <i class="far fa-clock mr-1"></i>
                            {{ $wallet->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="p-16 text-center">
            <i class="fas fa-layer-group text-6xl text-gray-300 dark:text-gray-600 dark:text-gray-400 mb-6"></i>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Child Wallet</h3>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-6">
                @if($masterWallet)
                    ผู้ใช้รายนี้ยังไม่มี Child Wallet จาก Master Wallet
                @else
                    สร้าง Master Wallet ก่อนเพื่อเริ่มสร้าง Child Wallets
                @endif
            </p>
            @if($masterWallet)
            <button class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-plus mr-2"></i>สร้าง Child Wallet แรก
            </button>
            @endif
        </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-8" border border-white/20 dark:border-white/10>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-bolt text-yellow-500 mr-3"></i>
            การดำเนินการด่วน
        </h3>
        <div class="grid md:grid-cols-4 gap-4">
            <a href="{{ route('admin.users.show', $user->id) }}" class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800 hover:shadow-lg transition-all">
                <i class="fas fa-user text-3xl text-blue-600 mb-3"></i>
                <p class="font-semibold text-gray-900 dark:text-white">โปรไฟล์ผู้ใช้</p>
            </a>
            <button class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl border-2 border-emerald-200 dark:border-emerald-800 hover:shadow-lg transition-all">
                <i class="fas fa-history text-3xl text-emerald-600 mb-3"></i>
                <p class="font-semibold text-gray-900 dark:text-white">ประวัติธุรกรรม</p>
            </button>
            <button class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border-2 border-purple-200 dark:border-purple-800 hover:shadow-lg transition-all">
                <i class="fas fa-chart-line text-3xl text-purple-600 mb-3"></i>
                <p class="font-semibold text-gray-900 dark:text-white">สถิติ & รายงาน</p>
            </button>
            <button class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border-2 border-orange-200 dark:border-orange-800 hover:shadow-lg transition-all">
                <i class="fas fa-cog text-3xl text-orange-600 mb-3"></i>
                <p class="font-semibold text-gray-900 dark:text-white">ตั้งค่า</p>
            </button>
        </div>
    </div>

</div>

@push('scripts')
<script>
function lockWallet(walletId) {
    if (confirm('คุณต้องการล็อกกระเป๋านี้หรือไม่?')) {
        fetch(`/admin/crypto/hd-wallets/${walletId}/lock`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ minutes: 30, reason: 'Locked by admin' })
        }).then(() => window.location.reload());
    }
}

function unlockWallet(walletId) {
    if (confirm('คุณต้องการปลดล็อกกระเป๋านี้หรือไม่?')) {
        fetch(`/admin/crypto/hd-wallets/${walletId}/unlock`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        }).then(() => window.location.reload());
    }
}
</script>
@endpush
@endsection

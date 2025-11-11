@extends('layouts.admin')

@section('title', 'Child Wallets - ' . $masterWallet->name)

@section('content')
<div class="space-y-6">

    <!-- Navigation Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
        <a href="{{ route('admin.crypto.hd-wallets.index') }}" class="hover:text-emerald-600 transition-colors">HD Wallets</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('admin.crypto.master-wallets.index') }}" class="hover:text-emerald-600 transition-colors">Master Wallets</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-semibold">Child Wallets</span>
    </div>

    <!-- Master Wallet Info Header -->
    <div class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-3xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -mr-32 -mt-32"></div>
        </div>
        <div class="relative">
            <a href="{{ route('admin.crypto.hd-wallets.show', $masterWallet->id) }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg mb-4 transition-all">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปที่ Master Wallet</span>
            </a>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-3">
                        <i class="fas fa-crown text-4xl"></i>
                        <h1 class="text-3xl font-bold">{{ $masterWallet->name }}</h1>
                    </div>
                    <p class="text-yellow-100 mb-4">เจ้าของ: {{ $masterWallet->user->name }} ({{ $masterWallet->user->email }})</p>
                    <div class="flex items-center space-x-6">
                        <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <p class="text-yellow-100 text-xs mb-1">Total Child Wallets</p>
                            <p class="text-2xl font-bold">{{ $childWallets->total() }}</p>
                        </div>
                        <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <p class="text-yellow-100 text-xs mb-1">Derivation Index</p>
                            <p class="text-2xl font-bold">{{ $masterWallet->derivation_index ?? 0 }}</p>
                        </div>
                        <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg">
                            <p class="text-yellow-100 text-xs mb-1">Total Derived</p>
                            <p class="text-2xl font-bold">{{ $masterWallet->total_derived_wallets }}</p>
                        </div>
                    </div>
                </div>
                <div class="hidden lg:block text-8xl opacity-20">
                    <i class="fas fa-sitemap"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    @if(isset($statistics))
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">{{ $statistics['child_wallets'] ?? $childWallets->total() }}</p>
            <p class="text-emerald-100 text-sm">Child Wallets</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-coins text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">฿{{ number_format($statistics['balance_thb'] ?? 0, 2) }}</p>
            <p class="text-blue-100 text-sm">Total Balance</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">{{ number_format($statistics['total_transactions'] ?? 0) }}</p>
            <p class="text-purple-100 text-sm">Transactions</p>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
            <p class="text-3xl font-bold mb-1">{{ $childWallets->where('status', 'active')->count() }}</p>
            <p class="text-orange-100 text-sm">Active Wallets</p>
        </div>
    </div>
    @endif

    <!-- Child Wallets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($childWallets as $wallet)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all transform hover:scale-[1.02] border-2 border-transparent hover:border-emerald-500">
            <!-- Header -->
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-12 -mt-12"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-full">
                            CHILD #{{ $wallet->derivation_index }}
                        </span>
                        <!-- Status Indicator -->
                        @if($wallet->status === 'active')
                            <div class="w-3 h-3 bg-green-400 rounded-full shadow-lg"></div>
                        @elseif($wallet->status === 'locked')
                            <div class="w-3 h-3 bg-yellow-400 rounded-full shadow-lg"></div>
                        @else
                            <div class="w-3 h-3 bg-red-400 rounded-full shadow-lg"></div>
                        @endif
                    </div>
                    <h3 class="text-xl font-bold text-white mb-1">{{ $wallet->name }}</h3>
                    <p class="text-emerald-100 text-xs">Index: {{ $wallet->derivation_index }}</p>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- Balance Display (if available) -->
                <div class="mb-4 p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ยอดคงเหลือ</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿0.00</p>
                        </div>
                        <i class="fas fa-coins text-3xl text-emerald-600 opacity-20"></i>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <i class="fas fa-link text-blue-600 mb-1"></i>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $wallet->cryptoAddresses->count() }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Addresses</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                        <i class="fas fa-exchange-alt text-purple-600 mb-1"></i>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">0</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Transactions</p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="mb-4 flex items-center justify-center">
                    @if($wallet->status === 'active')
                        <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-sm font-semibold">
                            <i class="fas fa-check-circle mr-2"></i> ใช้งานอยู่
                        </span>
                    @elseif($wallet->status === 'locked')
                        <span class="inline-flex items-center px-4 py-2 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-sm font-semibold">
                            <i class="fas fa-lock mr-2"></i> ล็อกอยู่
                        </span>
                    @else
                        <span class="inline-flex items-center px-4 py-2 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-sm font-semibold">
                            <i class="fas fa-ban mr-2"></i> ระงับ
                        </span>
                    @endif
                </div>

                <!-- Actions -->
                <div class="space-y-2">
                    <a href="{{ route('admin.crypto.hd-wallets.show', $wallet->id) }}" class="block text-center px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-eye mr-2"></i>ดูรายละเอียด
                    </a>
                    <div class="grid grid-cols-2 gap-2">
                        @if($wallet->status === 'active')
                        <button onclick="lockWallet({{ $wallet->id }})" class="px-3 py-2 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:hover:bg-yellow-800 text-yellow-700 dark:text-yellow-300 font-semibold rounded-lg transition-all text-sm">
                            <i class="fas fa-lock mr-1"></i>ล็อก
                        </button>
                        @else
                        <button onclick="unlockWallet({{ $wallet->id }})" class="px-3 py-2 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-700 dark:text-green-300 font-semibold rounded-lg transition-all text-sm">
                            <i class="fas fa-unlock mr-1"></i>ปลดล็อก
                        </button>
                        @endif
                        <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition-all text-sm">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                    <i class="far fa-clock mr-1"></i>
                    สร้างเมื่อ {{ $wallet->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
        </div>
        @empty
        <div class="col-span-3 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-16 text-center">
            <i class="fas fa-layer-group text-6xl text-gray-300 dark:text-gray-600 mb-6"></i>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบ Child Wallet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Master Wallet นี้ยังไม่มี Child Wallet</p>
            <button class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-plus mr-2"></i>สร้าง Child Wallet แรก
            </button>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($childWallets->hasPages())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        {{ $childWallets->links() }}
    </div>
    @endif

    <!-- Visualization -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-project-diagram text-emerald-600 mr-3"></i>
            HD Wallet Hierarchy
        </h3>
        <div class="flex flex-col items-center space-y-8 py-8">
            <!-- Master Node -->
            <div class="relative">
                <div class="px-8 py-4 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-2xl shadow-xl">
                    <div class="flex items-center space-x-3 text-white">
                        <i class="fas fa-crown text-2xl"></i>
                        <div>
                            <p class="font-bold text-lg">{{ $masterWallet->name }}</p>
                            <p class="text-xs text-yellow-100">Master Wallet</p>
                        </div>
                    </div>
                </div>
                <!-- Connection Lines -->
                <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 w-0.5 h-8 bg-gray-300 dark:bg-gray-600"></div>
            </div>

            <!-- Child Nodes (showing first 5) -->
            <div class="flex items-center space-x-4">
                @foreach($childWallets->take(5) as $wallet)
                <div class="relative">
                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 w-0.5 h-8 bg-gray-300 dark:bg-gray-600"></div>
                    <div class="px-6 py-3 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl shadow-lg">
                        <div class="text-white text-center">
                            <i class="fas fa-wallet text-xl mb-1"></i>
                            <p class="font-bold text-sm">Child #{{ $wallet->derivation_index }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
                @if($childWallets->count() > 5)
                <div class="px-4 py-3 bg-gray-200 dark:bg-gray-700 rounded-xl">
                    <p class="text-gray-600 dark:text-gray-400 font-bold">+{{ $childWallets->count() - 5 }} more</p>
                </div>
                @endif
            </div>
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

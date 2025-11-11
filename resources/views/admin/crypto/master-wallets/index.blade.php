@extends('layouts.admin')

@section('title', 'Master Wallets')

@section('content')
<div class="space-y-6">

    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-3xl shadow-2xl p-10 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full -mr-48 -mt-48"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full -ml-32 -mb-32"></div>
        </div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-4 mb-4">
                    <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                        <i class="fas fa-crown text-5xl"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold mb-2">Master Wallets</h1>
                        <p class="text-yellow-100">กระเป๋าหลักที่ใช้สร้าง Child Wallets ในระบบ HD Wallet</p>
                    </div>
                </div>
                <div class="flex items-center space-x-8 mt-6">
                    <div class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <p class="text-sm text-yellow-100 mb-1">Total Master Wallets</p>
                        <p class="text-3xl font-bold">{{ $masterWallets->total() }}</p>
                    </div>
                    <div class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <p class="text-sm text-yellow-100 mb-1">Total Child Wallets</p>
                        <p class="text-3xl font-bold">{{ $masterWallets->sum('child_wallets_count') }}</p>
                    </div>
                </div>
            </div>
            <div class="hidden lg:block text-9xl opacity-20">
                <i class="fas fa-sitemap"></i>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <form method="GET" class="flex items-center space-x-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา Master Wallet, ผู้ใช้, อีเมล..." class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
            </div>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
            @if(request('search'))
            <a href="{{ route('admin.crypto.master-wallets.index') }}" class="px-8 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-xl transition-all">
                <i class="fas fa-times mr-2"></i>ล้าง
            </a>
            @endif
        </form>
    </div>

    <!-- Master Wallets Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($masterWallets as $wallet)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all transform hover:scale-[1.02]">
            <!-- Header with Gradient -->
            <div class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 p-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <i class="fas fa-crown text-2xl text-white"></i>
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-full">MASTER</span>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-1">{{ $wallet->name }}</h3>
                        <p class="text-yellow-100 text-sm">Created {{ $wallet->created_at->diffForHumans() }}</p>
                    </div>
                    <!-- Status Badge -->
                    @if($wallet->status === 'active')
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-check text-white text-xl"></i>
                        </div>
                    @elseif($wallet->status === 'locked')
                        <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-lock text-white text-xl"></i>
                        </div>
                    @else
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-ban text-white text-xl"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- Owner Info -->
                <div class="flex items-center space-x-3 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ strtoupper(substr($wallet->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $wallet->user->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $wallet->user->email }}</p>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <i class="fas fa-layer-group text-2xl text-blue-600 mb-2"></i>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $wallet->child_wallets_count }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Child Wallets</p>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <i class="fas fa-hashtag text-2xl text-emerald-600 mb-2"></i>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $wallet->derivation_index ?? 0 }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Index</p>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                        <i class="fas fa-chart-line text-2xl text-purple-600 mb-2"></i>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $wallet->total_derived_wallets }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Total Derived</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('admin.crypto.hd-wallets.show', $wallet->id) }}" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-eye mr-2"></i>
                        ดูรายละเอียด
                    </a>
                    <a href="{{ route('admin.crypto.hd-wallets.child-wallets', $wallet->id) }}" class="flex items-center justify-center px-4 py-3 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-sitemap mr-2"></i>
                        Child Wallets
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    <i class="far fa-clock mr-1"></i>
                    อัปเดตล่าสุด: {{ $wallet->updated_at->format('d/m/Y H:i') }}
                </span>
                <div class="flex items-center space-x-2">
                    @if($wallet->status === 'active')
                        <button onclick="lockWallet({{ $wallet->id }})" class="p-2 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:hover:bg-yellow-800 text-yellow-600 dark:text-yellow-300 rounded-lg transition-colors" title="ล็อก">
                            <i class="fas fa-lock"></i>
                        </button>
                    @else
                        <button onclick="unlockWallet({{ $wallet->id }})" class="p-2 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-600 dark:text-green-300 rounded-lg transition-colors" title="ปลดล็อก">
                            <i class="fas fa-unlock"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-16 text-center">
            <i class="fas fa-crown text-6xl text-gray-300 dark:text-gray-600 mb-6"></i>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบ Master Wallet</h3>
            <p class="text-gray-600 dark:text-gray-400">ยังไม่มี Master Wallet ในระบบ</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($masterWallets->hasPages())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        {{ $masterWallets->links() }}
    </div>
    @endif

</div>

@push('scripts')
<script>
function lockWallet(walletId) {
    if (confirm('คุณต้องการล็อก Master Wallet นี้หรือไม่?')) {
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
    if (confirm('คุณต้องการปลดล็อก Master Wallet นี้หรือไม่?')) {
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

@extends('layouts.admin-v3')

@section('title', 'จัดการ HD Wallets')

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-wallet text-emerald-600 mr-3"></i>
                จัดการ HD Wallets
            </h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">ระบบจัดการกระเป๋าเงินดิจิทัลแบบ Hierarchical Deterministic</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="window.location.reload()" class="px-4 py-2 glass-fusion dark:bg-gray-800 border border-gray-200 dark:border-gray-700 dark:border-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl hover:shadow-md transition-all">
                <i class="fas fa-sync-alt mr-2"></i>รีเฟรช
            </button>
            <a href="{{ route('admin.crypto.hd-wallets.export') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-download mr-2"></i>ส่งออกข้อมูล
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Master Wallets -->
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 glass-fusion opacity-5 rounded-full -mr-16 -mt-16" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                        <i class="fas fa-wallet text-2xl"></i>
                    </div>
                    <span class="text-xs glass-fusion px-3 py-1 rounded-full backdrop-blur-sm">Master</span>
                </div>
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_master_wallets']) }}</p>
                <p class="text-emerald-100 text-sm">Master Wallets ทั้งหมด</p>
            </div>
        </div>

        <!-- Total Child Wallets -->
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 glass-fusion opacity-5 rounded-full -mr-16 -mt-16" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                        <i class="fas fa-layer-group text-2xl"></i>
                    </div>
                    <span class="text-xs glass-fusion px-3 py-1 rounded-full backdrop-blur-sm">Child</span>
                </div>
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_child_wallets']) }}</p>
                <p class="text-blue-100 text-sm">Child Wallets ทั้งหมด</p>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 glass-fusion opacity-5 rounded-full -mr-16 -mt-16" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <span class="text-xs glass-fusion px-3 py-1 rounded-full backdrop-blur-sm">Users</span>
                </div>
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_users_with_wallets']) }}</p>
                <p class="text-purple-100 text-sm">ผู้ใช้ที่มีกระเป๋า</p>
            </div>
        </div>

        <!-- Total Balance -->
        <div class="style="background: var(--arrow-x-warning-gradient)" rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 glass-fusion opacity-5 rounded-full -mr-16 -mt-16" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                        <i class="fas fa-coins text-2xl"></i>
                    </div>
                    <span class="text-xs glass-fusion px-3 py-1 rounded-full backdrop-blur-sm">THB</span>
                </div>
                <p class="text-3xl font-bold mb-1">฿{{ number_format($stats['total_balance_thb'], 2) }}</p>
                <p class="text-orange-100 text-sm">มูลค่ารวมในระบบ</p>
            </div>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-green-500" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-1">กระเป๋าที่ใช้งานอยู่</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_wallets']) }}</p>
                </div>
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-3xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-1">กระเป๋าที่ถูกล็อก</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['locked_wallets']) }}</p>
                </div>
                <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-lock text-3xl text-yellow-600"></i>
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-red-500" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-1">กระเป๋าที่ระงับ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['suspended_wallets']) }}</p>
                </div>
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-ban text-3xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10 x-data="{ showFilters: false }">
        <button @click="showFilters = !showFilters" class="w-full flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-filter text-emerald-600 mr-2"></i>
                ตัวกรอง
            </h3>
            <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': showFilters }"></i>
        </button>

        <form method="GET" x-show="showFilters" x-collapse class="grid md:grid-cols-4 gap-4">
            <!-- Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500">
                    <option value="">ทั้งหมด</option>
                    <option value="master" {{ request('type') === 'master' ? 'selected' : '' }}>Master Wallet</option>
                    <option value="child" {{ request('type') === 'child' ? 'selected' : '' }}>Child Wallet</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานอยู่</option>
                    <option value="locked" {{ request('status') === 'locked' ? 'selected' : '' }}>ล็อกอยู่</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>ระงับ</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อผู้ใช้, อีเมล..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-2 px-4 rounded-xl transition-all">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Wallets Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-emerald-500 to-green-600 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">ประเภท</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">ชื่อกระเป๋า</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Child Wallets</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">สถานะ</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">สร้างเมื่อ</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($wallets as $wallet)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-colors">
                        <!-- User -->
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-green-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($wallet->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $wallet->user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $wallet->user->email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Type -->
                        <td class="px-6 py-4">
                            @if($wallet->is_master_wallet)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                    <i class="fas fa-crown mr-1"></i> Master
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    <i class="fas fa-sitemap mr-1"></i> Child
                                </span>
                            @endif
                        </td>

                        <!-- Name -->
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $wallet->name }}</p>
                            @if($wallet->derivation_index !== null)
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Index: {{ $wallet->derivation_index }}</p>
                            @endif
                        </td>

                        <!-- Child Wallets Count -->
                        <td class="px-6 py-4">
                            @if($wallet->is_master_wallet)
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $wallet->child_wallets_count }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">wallets</span>
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-4">
                            @if($wallet->status === 'active')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <i class="fas fa-check-circle mr-1"></i> ใช้งาน
                                </span>
                            @elseif($wallet->status === 'locked')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <i class="fas fa-lock mr-1"></i> ล็อก
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <i class="fas fa-ban mr-1"></i> ระงับ
                                </span>
                            @endif
                        </td>

                        <!-- Created At -->
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $wallet->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $wallet->created_at->format('H:i') }}</p>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="{{ route('admin.crypto.hd-wallets.show', $wallet->id) }}" class="p-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-600 dark:text-blue-300 rounded-xl transition-colors" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($wallet->status === 'active')
                                    <button onclick="lockWallet({{ $wallet->id }})" class="p-2 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:hover:bg-yellow-800 text-yellow-600 dark:text-yellow-300 rounded-xl transition-colors" title="ล็อกกระเป๋า">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                @else
                                    <button onclick="unlockWallet({{ $wallet->id }})" class="p-2 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-600 dark:text-green-300 rounded-xl transition-colors" title="ปลดล็อก">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-wallet text-5xl text-gray-300 dark:text-gray-600 dark:text-gray-400 mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400">ไม่พบข้อมูลกระเป๋าเงิน</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($wallets->hasPages())
        <div class="px-6 py-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
            {{ $wallets->links() }}
        </div>
        @endif
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

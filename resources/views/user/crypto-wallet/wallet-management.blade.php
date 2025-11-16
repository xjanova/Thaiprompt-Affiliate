@extends('layouts.user-arrow-x')

@section('title', 'Wallet Management')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900" x-data="walletManagement()">

    <!-- Premium Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full filter blur-3xl"></div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-5xl font-black mb-3 bg-clip-text text-transparent bg-gradient-to-r from-white to-purple-200">
                        💼 Wallet Management
                    </h1>
                    <p class="text-purple-100 text-xl">จัดการกระเป๋าเงินคริปโตของคุณ</p>
                </div>

                <div class="mt-6 md:mt-0">
                    <button @click="showCreateModal = true"
                            class="px-8 py-4 bg-white hover:bg-gray-100 text-purple-600 font-bold rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        สร้างกระเป๋าใหม่
                    </button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Total Wallets</div>
                    <div class="text-3xl font-bold text-white">{{ count($wallets ?? []) }}</div>
                </div>
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Active Wallets</div>
                    <div class="text-3xl font-bold text-green-300">{{ $wallets->where('status', 'active')->count() }}</div>
                </div>
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Total Assets</div>
                    <div class="text-3xl font-bold text-white">{{ $totalAssets ?? 0 }}</div>
                </div>
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Total Value</div>
                    <div class="text-3xl font-bold text-white">฿{{ number_format($totalValue ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallets Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($wallets ?? [] as $wallet)
        <div class="bg-gray-900/80 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-purple-500/20 hover:border-purple-500/40 transition-all duration-300 hover:shadow-purple-500/20"
             :class="{ 'ring-4 ring-purple-500': {{ $wallet->is_default ? 'true' : 'false' }} }">

            <!-- Wallet Header -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-3xl">
                        {{ $wallet->type === 'custodial' ? '🔒' : '🌐' }}
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-1">
                            {{ $wallet->name ?? 'Wallet #' . $wallet->id }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $wallet->type === 'custodial' ? 'bg-blue-500/20 text-blue-300' : 'bg-orange-500/20 text-orange-300' }}">
                                {{ ucfirst($wallet->type) }}
                            </span>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $wallet->status === 'active' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                {{ ucfirst($wallet->status) }}
                            </span>
                            @if($wallet->is_default)
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-500/20 text-purple-300 flex items-center">
                                ⭐ Default
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="p-2 hover:bg-gray-800 rounded-lg transition">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false"
                         class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-xl border border-gray-700 py-2 z-10">
                        @if(!$wallet->is_default)
                        <form action="{{ route('user.crypto-wallet.wallet.set-default', $wallet->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                ⭐ Set as Default
                            </button>
                        </form>
                        @endif
                        <button @click="editWallet({{ $wallet->id }})" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                            ✏️ Edit Name
                        </button>
                        <button @click="viewDetails({{ $wallet->id }})" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                            👁️ View Details
                        </button>
                        <div class="border-t border-gray-700 my-1"></div>
                        <button @click="deleteWallet({{ $wallet->id }})" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-700">
                            🗑️ Delete Wallet
                        </button>
                    </div>
                </div>
            </div>

            <!-- Wallet Info -->
            @if($wallet->type === 'external')
            <div class="mb-6">
                <div class="text-xs text-gray-400 mb-2">Wallet Address</div>
                <div class="flex items-center space-x-2 bg-gray-800/50 rounded-lg p-3">
                    <code class="flex-1 text-sm text-gray-300 font-mono truncate">{{ $wallet->addresses->first()->address ?? 'N/A' }}</code>
                    <button @click="copyToClipboard('{{ $wallet->addresses->first()->address ?? '' }}')"
                            class="px-3 py-1 bg-purple-600 hover:bg-purple-700 rounded text-xs font-semibold transition">
                        Copy
                    </button>
                </div>
            </div>
            @endif

            <!-- Assets Summary -->
            <div class="mb-6">
                <div class="text-sm text-gray-400 mb-3">Assets</div>
                <div class="grid grid-cols-3 gap-3">
                    @php
                        $walletBalances = $balancesByWallet[$wallet->id] ?? [];
                    @endphp
                    @forelse($walletBalances as $balance)
                    <div class="bg-gray-800/50 rounded-lg p-3 text-center">
                        <div class="text-xs text-gray-400">{{ $balance['code'] }}</div>
                        <div class="text-sm font-bold text-white mt-1">{{ number_format($balance['balance'], 4) }}</div>
                    </div>
                    @empty
                    <div class="col-span-3 text-center text-gray-500 text-sm py-4">
                        No assets in this wallet
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Wallet Stats -->
            <div class="grid grid-cols-3 gap-4 pt-6 border-t border-gray-700">
                <div class="text-center">
                    <div class="text-xs text-gray-400 mb-1">Created</div>
                    <div class="text-sm font-semibold text-white">{{ $wallet->created_at->diffForHumans() }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-400 mb-1">Last Used</div>
                    <div class="text-sm font-semibold text-white">{{ $wallet->updated_at->diffForHumans() }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-400 mb-1">Value</div>
                    <div class="text-sm font-semibold text-green-400">฿{{ number_format($walletBalances->sum('balance_thb') ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        @empty
        <div class="lg:col-span-2 text-center py-20">
            <div class="text-6xl mb-4">💳</div>
            <h3 class="text-2xl font-bold text-white mb-2">No Wallets Yet</h3>
            <p class="text-gray-400 mb-6">Create your first crypto wallet to get started</p>
            <button @click="showCreateModal = true"
                    class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
                🚀 Create Wallet
            </button>
        </div>
        @endforelse
    </div>

    <!-- Create Wallet Modal -->
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showCreateModal = false">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div @click="showCreateModal = false"
                 class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-90"
                 x-show="showCreateModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border-2 border-purple-500/30"
                 x-show="showCreateModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="bg-gray-800 px-6 py-5">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-3xl font-bold text-white">
                            Create New Wallet
                        </h3>
                        <button @click="showCreateModal = false" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Custodial Wallet Option -->
                        <button onclick="window.location='{{ route('user.crypto-wallet.index') }}#create-custodial'"
                                class="w-full flex items-center justify-between p-6 bg-gradient-to-r from-blue-900/30 to-blue-800/30 hover:from-blue-900/50 hover:to-blue-800/50 border-2 border-blue-500/30 rounded-2xl transition-all duration-200">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center text-3xl">
                                    🔒
                                </div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-xl font-semibold text-white">Custodial Wallet</h4>
                                    <p class="text-sm text-gray-400 mt-1">ระบบจัดการให้ ปลอดภัย มี PIN ป้องกัน</p>
                                </div>
                            </div>
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <!-- External Wallet Option -->
                        <button onclick="window.location='{{ route('user.crypto-wallet.index') }}#connect-external'"
                                class="w-full flex items-center justify-between p-6 bg-gradient-to-r from-orange-900/30 to-orange-800/30 hover:from-orange-900/50 hover:to-orange-800/50 border-2 border-orange-500/30 rounded-2xl transition-all duration-200">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center text-3xl">
                                    🌐
                                </div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-xl font-semibold text-white">External Wallet</h4>
                                    <p class="text-sm text-gray-400 mt-1">เชื่อมต่อ MetaMask หรือ WalletConnect</p>
                                </div>
                            </div>
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function walletManagement() {
    return {
        showCreateModal: false,

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('คัดลอกแล้ว!');
            });
        },

        editWallet(walletId) {
            // Implementation for editing wallet name
            const newName = prompt('Enter new wallet name:');
            if (newName) {
                // Submit via form or AJAX
                console.log('Rename wallet', walletId, 'to', newName);
            }
        },

        viewDetails(walletId) {
            window.location.href = `/user/crypto-wallet/wallets/${walletId}`;
        },

        async deleteWallet(walletId) {
            if (!confirm('Are you sure you want to delete this wallet? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/user/crypto-wallet/wallet/${walletId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    alert('Wallet deleted successfully');
                    window.location.reload();
                } else {
                    alert('Failed to delete wallet');
                }
            } catch (error) {
                console.error('Delete failed:', error);
                alert('An error occurred');
            }
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection

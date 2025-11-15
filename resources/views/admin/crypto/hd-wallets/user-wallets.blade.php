@extends('layouts.admin')

@section('title', 'User HD Wallets')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-amber-50 to-yellow-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-700">🇹🇭 ไทย</a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-700">🇬🇧 English</a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-700">🇨🇳 中文</a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-700">🇯🇵 日本語</a>
                </div>
            </div>
        </div>

        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-amber-600 via-orange-600 to-yellow-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-center">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2" data-translate>User HD Wallets</h1>
                    <p class="text-amber-100" data-translate>กระเป๋าเงิน HD ของผู้ใช้งาน</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Search Input -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           id="searchWallets"
                           class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 transition-all"
                           placeholder="ค้นหากระเป๋าผู้ใช้..."
                           data-translate-placeholder>
                </div>

                <!-- Currency Filter -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                    </div>
                    <select id="filterCurrency"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 transition-all appearance-none">
                        <option value="" data-translate>สกุลเงินทั้งหมด</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Wallets Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-yellow-600 px-8 py-6">
                <h2 class="text-2xl font-bold text-white" data-translate>รายการกระเป๋าผู้ใช้</h2>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ผู้ใช้</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ที่อยู่กระเป๋า</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>สกุลเงิน</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ยอดคงเหลือ</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ธุรกรรม</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>กิจกรรมล่าสุด</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>สถานะ</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($userWallets ?? [] as $wallet)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <!-- User -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-white font-bold text-sm">{{ substr($wallet->user_name ?? 'N', 0, 1) }}</span>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $wallet->user_name ?? 'N/A' }}</span>
                                        </div>
                                    </td>

                                    <!-- Wallet Address -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-mono">
                                            {{ Str::limit($wallet->address ?? 'N/A', 20) }}
                                        </code>
                                    </td>

                                    <!-- Currency -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-full text-xs font-semibold">
                                            {{ $wallet->currency ?? 'BTC' }}
                                        </span>
                                    </td>

                                    <!-- Balance -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-lg flex items-center justify-center mr-2">
                                                <span class="text-white font-bold text-xs">₿</span>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $wallet->balance ?? '0' }}</span>
                                        </div>
                                    </td>

                                    <!-- Transactions -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-semibold">
                                            {{ $wallet->transactions_count ?? '0' }} <span class="ml-1" data-translate>รายการ</span>
                                        </span>
                                    </td>

                                    <!-- Last Activity -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ isset($wallet->last_activity) ? date('M d, Y', strtotime($wallet->last_activity)) : 'Never' }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ ($wallet->status ?? 'active') == 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                            {{ ucfirst($wallet->status ?? 'Active') }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button onclick="viewWallet({{ $wallet->id }})" class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-lg transition-all transform hover:scale-105">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium" data-translate>ไม่พบกระเป๋าผู้ใช้</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function viewWallet(id) {
    console.log('Viewing wallet:', id);
}
</script>
@endsection

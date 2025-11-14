@extends('layouts.user')

@section('title', 'TPIX Wallet - กระเป๋าเงิน TPIX')

@section('content')
{{-- Hero Section --}}
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900 p-12 mb-8 shadow-2xl">
    <div class="absolute inset-0 bg-black/10 dark:bg-white/5"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -mr-48 -mt-48"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full -ml-32 -mb-32"></div>

    <div class="relative z-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-white/20 dark:bg-black/30 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-wallet text-2xl text-white"></i>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                        TPIX Wallet
                    </h1>
                </div>
                <p class="text-white/80 text-sm">Blockchain Address: <span class="font-mono bg-black/20 px-3 py-1 rounded-lg backdrop-blur-sm">0x...</span></p>
            </div>
            <button class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold rounded-xl transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-qrcode"></i>
                <span class="hidden md:inline">แสดง QR Code</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Balance --}}
            <div class="col-span-1 md:col-span-2 bg-white/10 dark:bg-black/20 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                <div class="text-white/80 text-sm mb-2">ยอดคงเหลือทั้งหมด</div>
                <div class="flex items-baseline gap-3 mb-4">
                    <div id="totalBalance" class="text-5xl font-bold text-white">0.00</div>
                    <div class="text-2xl text-white/80">TPIX</div>
                </div>
                <div class="text-white/60 text-sm">
                    ≈ $<span id="totalBalanceUSD">0.00</span> USD
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white/10 dark:bg-black/20 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                <div class="text-white/80 text-sm mb-4">ดำเนินการด่วน</div>
                <div class="space-y-3">
                    <a href="{{ route('user.tpix.send') }}" class="flex items-center gap-3 px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-all duration-200">
                        <i class="fas fa-paper-plane w-5"></i>
                        <span class="font-medium">ส่ง TPIX</span>
                    </a>
                    <a href="{{ route('user.tpix.deposit') }}" class="flex items-center gap-3 px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-all duration-200">
                        <i class="fas fa-download w-5"></i>
                        <span class="font-medium">ฝาก</span>
                    </a>
                    <a href="{{ route('user.tpix.withdrawal') }}" class="flex items-center gap-3 px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-all duration-200">
                        <i class="fas fa-upload w-5"></i>
                        <span class="font-medium">ถอน</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Stats --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fas fa-coins text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <span class="text-xs text-green-600 dark:text-green-400 font-semibold bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">+12.5%</span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">TPIX Price</div>
        <div id="tpixPrice" class="text-2xl font-bold text-gray-900 dark:text-white">$0.00</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <i class="fas fa-chart-line text-green-600 dark:text-green-400 text-xl"></i>
            </div>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">กำไร/ขาดทุน</div>
        <div id="profitLoss" class="text-2xl font-bold text-green-600 dark:text-green-400">+$0.00</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <i class="fas fa-layer-group text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Staked</div>
        <div id="totalStaked" class="text-2xl font-bold text-gray-900 dark:text-white">0.00</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                <i class="fas fa-gift text-orange-600 dark:text-orange-400 text-xl"></i>
            </div>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Rewards Earned</div>
        <div id="rewardsEarned" class="text-2xl font-bold text-gray-900 dark:text-white">0.00</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Token Holdings --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                            <i class="fas fa-coins text-white text-lg"></i>
                        </span>
                        Token ที่ถือครอง
                    </h2>
                    <a href="{{ route('user.tokens.index') }}" class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium text-sm transition-colors">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            {{-- Token List --}}
            <div id="tokenHoldings" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-purple-600 border-t-transparent"></div>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden mt-8">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center">
                            <i class="fas fa-history text-white text-lg"></i>
                        </span>
                        ธุรกรรมล่าสุด
                    </h2>
                    <a href="{{ route('user.tpix.transactions') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium text-sm transition-colors">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div id="recentTransactions" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                    <i class="fas fa-receipt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีธุรกรรม</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- TPIX Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-bolt text-indigo-600 dark:text-indigo-400"></i>
                    TPIX Ecosystem
                </h3>
            </div>
            <div class="p-6 space-y-3">
                <a href="{{ route('user.dex.swap') }}" class="group flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 hover:from-purple-100 hover:to-pink-100 dark:hover:from-purple-900/30 dark:hover:to-pink-900/30 rounded-xl transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-600 dark:bg-purple-500 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-white"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">DEX Swap</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">แลกเปลี่ยน Token</div>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors"></i>
                </a>

                <a href="{{ route('user.staking.index') }}" class="group flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-pink-50 dark:from-orange-900/20 dark:to-pink-900/20 hover:from-orange-100 hover:to-pink-100 dark:hover:from-orange-900/30 dark:hover:to-pink-900/30 rounded-xl transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-orange-600 dark:bg-orange-500 flex items-center justify-center">
                            <i class="fas fa-coins text-white"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Staking</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Stake รับรางวัล</div>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors"></i>
                </a>

                <a href="{{ route('user.dex.pools') }}" class="group flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 rounded-xl transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600 dark:bg-blue-500 flex items-center justify-center">
                            <i class="fas fa-water text-white"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Liquidity Pools</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">เพิ่มสภาพคล่อง</div>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"></i>
                </a>

                <a href="{{ route('user.tokens.index') }}" class="group flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 hover:from-green-100 hover:to-emerald-100 dark:hover:from-green-900/30 dark:hover:to-emerald-900/30 rounded-xl transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-600 dark:bg-green-500 flex items-center justify-center">
                            <i class="fas fa-store text-white"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Token Marketplace</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">ซื้อขาย Token</div>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors"></i>
                </a>
            </div>
        </div>

        {{-- Network Info --}}
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-3xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-network-wired"></i>
                Network Status
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">สถานะ</span>
                    <span class="flex items-center gap-2 text-green-600 dark:text-green-400 font-medium">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        Online
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Block Height</span>
                    <span id="blockHeight" class="text-gray-900 dark:text-white font-mono">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Gas Price</span>
                    <span id="gasPrice" class="text-gray-900 dark:text-white font-mono">- Gwei</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * TPIX Wallet Dashboard
 * โหลดข้อมูลและแสดงผล
 */
console.log('TPIX Wallet Dashboard - View loaded');
</script>
@endpush
@endsection

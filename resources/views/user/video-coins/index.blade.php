@extends('layouts.user')

@section('title', 'Video Coins')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                Video Coins
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                จัดการและใช้งาน Video Coins ของคุณ
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('user.coin-shop.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white rounded-xl font-medium shadow-lg transition-all">
                <i class="fas fa-store"></i>
                <span>ร้านค้า Coins</span>
            </a>
            <a href="{{ route('user.video-coins.exchange') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl font-medium shadow-lg transition-all">
                <i class="fas fa-exchange-alt"></i>
                <span>แลกเงิน</span>
            </a>
        </div>
    </div>

    {{-- Main Balance Card --}}
    <x-video-reward.coin-balance :user="auth()->user()" size="lg" :showStats="true" />

    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- ได้รับวันนี้ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-arrow-down text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ได้รับวันนี้</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($videoCoin->earned_today ?? 0, 0) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ใช้ไปวันนี้ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ใช้ไปวันนี้</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($videoCoin->spent_today ?? 0, 0) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- แลกไปแล้ว --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-coins text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">แลกไปแล้ว</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($videoCoin->lifetime_exchanged ?? 0, 0) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- คงเหลือ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <span class="text-yellow-600 dark:text-yellow-400 text-xl font-black">C</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยอดคงเหลือ</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($videoCoin->balance ?? 0, 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('user.video-missions.index') }}"
           class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center hover:shadow-xl hover:-translate-y-1 transition-all group">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-play text-white text-2xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white">ดูวิดีโอ</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">รับ Coins ฟรี</p>
        </a>

        <a href="{{ route('user.coin-shop.index') }}"
           class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center hover:shadow-xl hover:-translate-y-1 transition-all group">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-pink-500 to-rose-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-store text-white text-2xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white">ร้านค้า</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แลกของรางวัล</p>
        </a>

        <a href="{{ route('user.video-coins.exchange') }}"
           class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center hover:shadow-xl hover:-translate-y-1 transition-all group">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-emerald-500 to-green-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-exchange-alt text-white text-2xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white">แลกเงิน</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ถอนเงินบาท</p>
        </a>

        <a href="{{ route('user.video-coins.transactions') }}"
           class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center hover:shadow-xl hover:-translate-y-1 transition-all group">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-history text-white text-2xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white">ประวัติ</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ดูธุรกรรม</p>
        </a>
    </div>

    {{-- Recent Transactions --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                <i class="fas fa-history mr-2 text-yellow-500"></i>
                ธุรกรรมล่าสุด
            </h2>
            <a href="{{ route('user.video-coins.transactions') }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($recentTransactions->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($recentTransactions as $transaction)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                                {{ str_starts_with($transaction->type, 'earned') ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                                <i class="fas {{ str_starts_with($transaction->type, 'earned') ? 'fa-arrow-down text-green-600 dark:text-green-400' : 'fa-arrow-up text-red-600 dark:text-red-400' }}"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $transaction->description ?? $transaction->type }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold {{ str_starts_with($transaction->type, 'earned') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ str_starts_with($transaction->type, 'earned') ? '+' : '-' }}{{ number_format(abs($transaction->amount), 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                คงเหลือ {{ number_format($transaction->balance_after, 2) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <i class="fas fa-coins text-gray-400 dark:text-gray-500 text-3xl"></i>
                </div>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติธุรกรรม</p>
                <a href="{{ route('user.video-missions.index') }}"
                   class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-medium transition-colors">
                    <i class="fas fa-play"></i>
                    ดูวิดีโอรับ Coins
                </a>
            </div>
        @endif
    </div>

    {{-- Exchange Rates --}}
    @if($exchangeRates->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                <i class="fas fa-exchange-alt mr-2 text-emerald-500"></i>
                อัตราแลกเปลี่ยน
            </h2>
        </div>

        <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($exchangeRates as $rate)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-emerald-500 dark:hover:border-emerald-400 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-black text-yellow-500">C</span>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($rate->coins_amount, 0) }}
                            </span>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                        <div class="flex items-center gap-1">
                            <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($rate->money_amount, 0) }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">฿</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        อัตรา: 1 Coin = {{ number_format($rate->rate, 4) }} บาท
                    </p>
                </div>
            @endforeach
        </div>

        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 text-center">
            <a href="{{ route('user.video-coins.exchange') }}"
               class="inline-flex items-center gap-2 text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
                แลก Coins เป็นเงิน <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    @endif
</div>
@endsection

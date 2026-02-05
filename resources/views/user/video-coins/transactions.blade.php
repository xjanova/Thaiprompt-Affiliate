@extends('layouts.user')

@section('title', 'ประวัติธุรกรรม Coins')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('user.video-coins.index') }}"
           class="p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left text-gray-600 dark:text-gray-400"></i>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                ประวัติธุรกรรม Coins
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                ดูรายการได้รับและใช้จ่าย Video Coins
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
        <form action="{{ route('user.video-coins.transactions') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <select name="type" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="earned_video" {{ request('type') == 'earned_video' ? 'selected' : '' }}>ได้รับจากวิดีโอ</option>
                    <option value="earned_quest" {{ request('type') == 'earned_quest' ? 'selected' : '' }}>ได้รับจากภารกิจ</option>
                    <option value="earned_referral" {{ request('type') == 'earned_referral' ? 'selected' : '' }}>ได้รับจากแนะนำ</option>
                    <option value="earned_bonus" {{ request('type') == 'earned_bonus' ? 'selected' : '' }}>โบนัส</option>
                    <option value="spent_exchange" {{ request('type') == 'spent_exchange' ? 'selected' : '' }}>แลกเป็นเงิน</option>
                    <option value="spent_shop" {{ request('type') == 'spent_shop' ? 'selected' : '' }}>ซื้อสินค้า</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จากวันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
                <a href="{{ route('user.video-coins.transactions') }}"
                   class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-colors">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Transactions List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        @if($transactions->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($transactions as $transaction)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center
                                {{ str_starts_with($transaction->type, 'earned') ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                                @switch($transaction->type)
                                    @case('earned_video')
                                        <i class="fas fa-play text-green-600 dark:text-green-400"></i>
                                        @break
                                    @case('earned_quest')
                                        <i class="fas fa-trophy text-green-600 dark:text-green-400"></i>
                                        @break
                                    @case('earned_referral')
                                        <i class="fas fa-users text-green-600 dark:text-green-400"></i>
                                        @break
                                    @case('earned_bonus')
                                        <i class="fas fa-gift text-green-600 dark:text-green-400"></i>
                                        @break
                                    @case('spent_exchange')
                                        <i class="fas fa-exchange-alt text-red-600 dark:text-red-400"></i>
                                        @break
                                    @case('spent_shop')
                                        <i class="fas fa-shopping-cart text-red-600 dark:text-red-400"></i>
                                        @break
                                    @default
                                        <i class="fas {{ str_starts_with($transaction->type, 'earned') ? 'fa-arrow-down text-green-600 dark:text-green-400' : 'fa-arrow-up text-red-600 dark:text-red-400' }}"></i>
                                @endswitch
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $transaction->description ?? $transaction->type }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    <span class="text-gray-400 mx-1">|</span>
                                    {{ $transaction->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold {{ str_starts_with($transaction->type, 'earned') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ str_starts_with($transaction->type, 'earned') ? '+' : '-' }}{{ number_format(abs($transaction->amount), 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                คงเหลือ {{ number_format($transaction->balance_after, 2) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $transactions->withQueryString()->links() }}
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <i class="fas fa-receipt text-gray-400 dark:text-gray-500 text-3xl"></i>
                </div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ไม่พบประวัติธุรกรรม</p>
                <a href="{{ route('user.video-missions.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-medium transition-colors">
                    <i class="fas fa-play"></i>
                    ดูวิดีโอรับ Coins
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

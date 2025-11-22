@extends('layouts.user')

@section('title', 'การ์ด NFC Tap-to-Pay')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="nfcDashboard()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-purple-400">
                    <i class="fas fa-credit-card mr-3"></i>
                    การ์ด NFC Tap-to-Pay
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    จัดการการ์ด NFC, วงเงิน และการชำระเงินแบบแตะแล้วจ่าย
                </p>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Total Balance --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-xl">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-blue-100">ยอดเงินรวมทั้งหมด</p>
                    <i class="fas fa-wallet text-2xl text-blue-200"></i>
                </div>
                <p class="text-4xl font-bold">฿{{ number_format($totalBalance, 2) }}</p>
                <p class="text-sm text-blue-100 mt-2">จากการ์ดทั้งหมด {{ $totalCards }} ใบ</p>
            </div>
        </div>

        {{-- Active Cards --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-green-600 p-6 text-white shadow-xl">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-green-100">การ์ดที่ใช้งานได้</p>
                    <i class="fas fa-check-circle text-2xl text-green-200"></i>
                </div>
                <p class="text-4xl font-bold">{{ $activeCards }}</p>
                <p class="text-sm text-green-100 mt-2">จากทั้งหมด {{ $totalCards }} ใบ</p>
            </div>
        </div>

        {{-- Wallet Balance --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-white shadow-xl">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-purple-100">Wallet ของคุณ</p>
                    <i class="fas fa-coins text-2xl text-purple-200"></i>
                </div>
                <p class="text-4xl font-bold">฿{{ number_format($wallet->balance ?? 0, 2) }}</p>
                <p class="text-sm text-purple-100 mt-2">พร้อมเติมเงินเข้าการ์ด</p>
            </div>
        </div>
    </div>

    {{-- Cards List --}}
    <div class="space-y-6">
        @forelse($cards as $card)
            <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                {{-- Card Header - Glassmorphism Effect --}}
                <div class="relative p-6 bg-gradient-to-r {{ $card->isEnabled() ? 'from-blue-500/10 to-purple-500/10' : 'from-gray-500/10 to-gray-600/10' }} backdrop-blur-sm">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        {{-- Card Info --}}
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                                    <i class="fas fa-credit-card text-2xl"></i>
                                </div>
                                @if($card->isEnabled())
                                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                @else
                                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-times text-white text-xs"></i>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $card->card_name ?? 'การ์ด NFC' }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $card->masked_card_number }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $card->status_badge_color }}-100 text-{{ $card->status_badge_color }}-700 dark:bg-{{ $card->status_badge_color }}-900/30 dark:text-{{ $card->status_badge_color }}-400">
                                        {{ $card->status_label }}
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $card->card_type_label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Toggle Switch --}}
                        <div class="flex items-center gap-4">
                            <div class="text-right hidden md:block">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    ฿{{ number_format($card->balance, 2) }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    ยอดคงเหลือ
                                </p>
                            </div>

                            <form action="{{ $card->isEnabled() ? route('user.nfc.disable', $card) : route('user.nfc.enable', $card) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $card->isEnabled() ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition-transform {{ $card->isEnabled() ? 'translate-x-9' : 'translate-x-1' }}"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="p-6">
                    {{-- Spending Limits Progress --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">วงเงินรายวัน</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                ฿{{ number_format($card->daily_spent, 2) }} / ฿{{ number_format($card->daily_spending_limit, 2) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                            @php
                                $dailyPercent = $card->daily_spending_limit > 0 ? ($card->daily_spent / $card->daily_spending_limit) * 100 : 0;
                            @endphp
                            <div class="h-2 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-500"
                                 style="width: {{ min($dailyPercent, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            คงเหลือ ฿{{ number_format($card->daily_remaining, 2) }}
                        </p>
                    </div>

                    {{-- Quick Info Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <p class="text-xs text-gray-600 dark:text-gray-400">วงเงิน/รายการ</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                                ฿{{ number_format($card->transaction_limit, 0) }}
                            </p>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <p class="text-xs text-gray-600 dark:text-gray-400">วงเงินรายเดือน</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                                ฿{{ number_format($card->monthly_spending_limit, 0) }}
                            </p>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <p class="text-xs text-gray-600 dark:text-gray-400">Auto Top-up</p>
                            <p class="text-sm font-bold {{ $card->auto_topup_enabled ? 'text-green-600' : 'text-gray-900 dark:text-white' }} mt-1">
                                {{ $card->auto_topup_enabled ? 'เปิด' : 'ปิด' }}
                            </p>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <p class="text-xs text-gray-600 dark:text-gray-400">Wallet</p>
                            <p class="text-sm font-bold {{ $card->hasLinkedWallet() ? 'text-green-600' : 'text-gray-900 dark:text-white' }} mt-1">
                                {{ $card->hasLinkedWallet() ? 'ผูกแล้ว' : 'ยังไม่ผูก' }}
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('user.nfc.show', $card) }}"
                           class="flex-1 min-w-[140px] px-4 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-cog"></i>
                            <span>จัดการการ์ด</span>
                        </a>

                        @if($card->hasLinkedWallet())
                            <button @click="openTopUpModal({{ $card->id }}, '{{ $card->card_name }}')"
                                    class="flex-1 min-w-[140px] px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-plus-circle"></i>
                                <span>เติมเงิน</span>
                            </button>
                        @else
                            <form action="{{ route('user.nfc.wallet.link', $card) }}" method="POST" class="flex-1 min-w-[140px]">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2.5 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                    <i class="fas fa-link"></i>
                                    <span>ผูก Wallet</span>
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('user.nfc.transactions', $card) }}"
                           class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-history"></i>
                            <span>ประวัติ</span>
                        </a>
                    </div>

                    {{-- Recent Transactions Preview --}}
                    @if($card->transactions->count() > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <i class="fas fa-clock text-blue-500"></i>
                                ธุรกรรมล่าสุด
                            </h4>
                            <div class="space-y-2">
                                @foreach($card->transactions->take(3) as $transaction)
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-{{ $transaction->status_badge_color }}-100 dark:bg-{{ $transaction->status_badge_color }}-900/30 flex items-center justify-center">
                                                <i class="fas fa-{{ $transaction->type === 'payment' ? 'arrow-up' : 'arrow-down' }} text-{{ $transaction->status_badge_color }}-600 text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="text-gray-900 dark:text-white font-medium">{{ $transaction->type_label }}</p>
                                                <p class="text-xs text-gray-500">{{ $transaction->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-gray-900 dark:text-white">
                                                {{ $transaction->type === 'payment' ? '-' : '+' }}฿{{ number_format($transaction->amount, 2) }}
                                            </p>
                                            <p class="text-xs text-{{ $transaction->status_badge_color }}-600">{{ $transaction->status_label }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            {{-- Empty State --}}
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 mb-6">
                    <i class="fas fa-credit-card text-5xl text-blue-500"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีการ์ด NFC</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    คุณยังไม่มีการ์ด NFC ในระบบ กรุณาติดต่อแอดมินเพื่อขอรับการ์ด
                </p>
                <a href="{{ route('user.support') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl">
                    <i class="fas fa-headset"></i>
                    <span>ติดต่อฝ่ายสนับสนุน</span>
                </a>
            </div>
        @endforelse
    </div>

    {{-- Top Up Modal --}}
    <div x-show="showTopUpModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div @click="showTopUpModal = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        {{-- Modal Content --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">เติมเงินจาก Wallet</h3>
                <button @click="showTopUpModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form :action="`/user/nfc/${selectedCardId}/topup`" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            การ์ด
                        </label>
                        <input type="text" :value="selectedCardName" readonly
                               class="w-full px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนเงิน (฿)
                        </label>
                        <input type="number" name="amount" x-model="topUpAmount" required min="1" step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ยอด Wallet คงเหลือ: ฿{{ number_format($wallet->balance ?? 0, 2) }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            PIN (6 หลัก)
                        </label>
                        <input type="password" name="pin" required maxlength="6" pattern="[0-9]{6}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showTopUpModal = false"
                                class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white rounded-lg font-medium transition shadow-lg">
                            เติมเงิน
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * NFC Dashboard Component
 *
 * จัดการ UI interactions สำหรับ NFC dashboard
 */
function nfcDashboard() {
    return {
        showTopUpModal: false,
        selectedCardId: null,
        selectedCardName: '',
        topUpAmount: '',

        /**
         * เปิด modal เติมเงิน
         */
        openTopUpModal(cardId, cardName) {
            this.selectedCardId = cardId;
            this.selectedCardName = cardName;
            this.topUpAmount = '';
            this.showTopUpModal = true;
        }
    };
}
</script>
@endpush
@endsection

@extends('layouts.user')

@section('title', 'รายละเอียดการ์ด NFC - ' . ($card->card_name ?? 'การ์ด NFC'))

@section('content')
<div class="container mx-auto px-4 py-8" x-data="nfcCardDetail()">
    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('user.nfc.index') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปรายการการ์ด</span>
        </a>
    </div>

    {{-- Card Overview --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        {{-- Card Visual --}}
        <div class="lg:col-span-1">
            <div class="sticky top-8">
                {{-- 3D Card Design --}}
                <div class="relative perspective-1000">
                    <div class="relative w-full aspect-[1.586/1] rounded-2xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 p-6 shadow-2xl transform transition-transform duration-500 hover:scale-105"
                         style="transform-style: preserve-3d;">
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-white/10 to-transparent backdrop-blur-xl"></div>

                        <div class="relative h-full flex flex-col justify-between text-white">
                            {{-- Chip --}}
                            <div>
                                <div class="w-12 h-10 rounded bg-gradient-to-br from-yellow-200 to-yellow-400 mb-4"></div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-wifi text-2xl"></i>
                                    <span class="text-sm font-medium">NFC</span>
                                </div>
                            </div>

                            {{-- Card Info --}}
                            <div>
                                <p class="text-2xl font-mono tracking-wider mb-2">
                                    {{ $card->masked_card_number }}
                                </p>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <p class="text-xs opacity-75 mb-1">ชื่อบัตร</p>
                                        <p class="font-medium">{{ $card->card_name ?? 'NFC CARD' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs opacity-75 mb-1">ประเภท</p>
                                        <p class="font-medium uppercase">{{ $card->card_type }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Status --}}
                <div class="mt-6 p-4 rounded-xl bg-white dark:bg-gray-800 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">สถานะการ์ด</span>
                        <form action="{{ $card->isEnabled() ? route('user.nfc.disable', $card) : route('user.nfc.enable', $card) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors {{ $card->isEnabled() ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition-transform {{ $card->isEnabled() ? 'translate-x-9' : 'translate-x-1' }}"></span>
                            </button>
                        </form>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">สถานะ</span>
                            <span class="px-2 py-1 rounded-full bg-{{ $card->status_badge_color }}-100 text-{{ $card->status_badge_color }}-700 dark:bg-{{ $card->status_badge_color }}-900/30 dark:text-{{ $card->status_badge_color }}-400 text-xs">
                                {{ $card->status_label }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ใช้งานล่าสุด</span>
                            <span class="text-gray-900 dark:text-white">
                                {{ $card->last_used_at ? $card->last_used_at->diffForHumans() : 'ยังไม่เคยใช้' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">หมดอายุ</span>
                            <span class="text-gray-900 dark:text-white">
                                {{ $card->expires_at ? $card->expires_at->format('d/m/Y') : 'ไม่มี' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Details & Settings --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Balance Card --}}
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 mb-2">ยอดเงินคงเหลือ</p>
                        <p class="text-5xl font-bold">฿{{ number_format($card->balance, 2) }}</p>
                        @if($card->hasLinkedWallet())
                            <p class="text-sm text-blue-100 mt-2">
                                <i class="fas fa-link mr-1"></i>
                                ผูกกับ Wallet (฿{{ number_format($card->wallet->balance ?? 0, 2) }})
                            </p>
                        @endif
                    </div>
                    <div>
                        @if($card->hasLinkedWallet())
                            <button @click="showTopUpModal = true"
                                    class="px-6 py-3 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition shadow-lg">
                                <i class="fas fa-plus-circle mr-2"></i>
                                เติมเงิน
                            </button>
                        @else
                            <form action="{{ route('user.nfc.wallet.link', $card) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-6 py-3 bg-white text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition shadow-lg">
                                    <i class="fas fa-link mr-2"></i>
                                    ผูก Wallet
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ธุรกรรมทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statistics['total_transactions'] ?? 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ยอดใช้จ่ายรวม</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($statistics['total_spent'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">เติมเงินรวม</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($statistics['total_topped_up'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ธุรกรรมสำเร็จ</p>
                    <p class="text-2xl font-bold text-green-600">{{ $statistics['completed_transactions'] ?? 0 }}</p>
                </div>
            </div>

            {{-- Spending Limits --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                        วงเงินการใช้จ่าย
                    </h3>
                    <button @click="showLimitsModal = true"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                        <i class="fas fa-edit mr-2"></i>
                        แก้ไข
                    </button>
                </div>

                <div class="space-y-6">
                    {{-- Daily Limit --}}
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">วงเงินรายวัน</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                ฿{{ number_format($card->daily_spent, 2) }} / ฿{{ number_format($card->daily_spending_limit, 2) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                            @php
                                $dailyPercent = $card->daily_spending_limit > 0 ? ($card->daily_spent / $card->daily_spending_limit) * 100 : 0;
                                $dailyColor = $dailyPercent >= 90 ? 'red' : ($dailyPercent >= 70 ? 'yellow' : 'blue');
                            @endphp
                            <div class="h-3 rounded-full bg-gradient-to-r from-{{ $dailyColor }}-400 to-{{ $dailyColor }}-600 transition-all duration-500"
                                 style="width: {{ min($dailyPercent, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            คงเหลือ ฿{{ number_format($card->daily_remaining, 2) }} ({{ number_format(100 - $dailyPercent, 1) }}%)
                        </p>
                    </div>

                    {{-- Monthly Limit --}}
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">วงเงินรายเดือน</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                ฿{{ number_format($card->monthly_spent, 2) }} / ฿{{ number_format($card->monthly_spending_limit, 2) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                            @php
                                $monthlyPercent = $card->monthly_spending_limit > 0 ? ($card->monthly_spent / $card->monthly_spending_limit) * 100 : 0;
                                $monthlyColor = $monthlyPercent >= 90 ? 'red' : ($monthlyPercent >= 70 ? 'yellow' : 'purple');
                            @endphp
                            <div class="h-3 rounded-full bg-gradient-to-r from-{{ $monthlyColor }}-400 to-{{ $monthlyColor }}-600 transition-all duration-500"
                                 style="width: {{ min($monthlyPercent, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            คงเหลือ ฿{{ number_format($card->monthly_remaining, 2) }} ({{ number_format(100 - $monthlyPercent, 1) }}%)
                        </p>
                    </div>

                    {{-- Transaction Limit --}}
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">วงเงินต่อธุรกรรม</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                ฿{{ number_format($card->transaction_limit, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Auto Top-up Settings --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-sync-alt text-green-500 mr-2"></i>
                        Auto Top-up
                    </h3>
                    <button @click="showAutoTopupModal = true"
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>
                        ตั้งค่า
                    </button>
                </div>

                @if($card->auto_topup_enabled)
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-green-900 dark:text-green-100 mb-2">Auto Top-up เปิดใช้งาน</p>
                                <div class="text-sm text-green-700 dark:text-green-300 space-y-1">
                                    <p>• เติมเงินอัตโนมัติเมื่อยอดต่ำกว่า ฿{{ number_format($card->auto_topup_threshold, 2) }}</p>
                                    <p>• จำนวนที่เติมแต่ละครั้ง ฿{{ number_format($card->auto_topup_amount, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-power-off text-3xl mb-2"></i>
                        <p>Auto Top-up ปิดอยู่</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top Up Modal --}}
    <div x-show="showTopUpModal"
         x-cloak
         @click.self="showTopUpModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">เติมเงินจาก Wallet</h3>

            <form action="{{ route('user.nfc.topup', $card) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (฿)</label>
                        <input type="number" name="amount" required min="1" step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Wallet คงเหลือ: ฿{{ number_format($card->wallet->balance ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PIN (6 หลัก)</label>
                        <input type="password" name="pin" required maxlength="6" pattern="[0-9]{6}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showTopUpModal = false"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg">
                            เติมเงิน
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Limits Modal --}}
    <div x-show="showLimitsModal"
         x-cloak
         @click.self="showLimitsModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">แก้ไขวงเงิน</h3>

            <form action="{{ route('user.nfc.limits.update', $card) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วงเงินรายวัน (฿)</label>
                        <input type="number" name="daily_limit" value="{{ $card->daily_spending_limit }}" required min="0"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วงเงินรายเดือน (฿)</label>
                        <input type="number" name="monthly_limit" value="{{ $card->monthly_spending_limit }}" required min="0"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วงเงินต่อธุรกรรม (฿)</label>
                        <input type="number" name="transaction_limit" value="{{ $card->transaction_limit }}" required min="0"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showLimitsModal = false"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg">
                            บันทึก
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Auto Top-up Modal --}}
    <div x-show="showAutoTopupModal"
         x-cloak
         @click.self="showAutoTopupModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">ตั้งค่า Auto Top-up</h3>

            <form action="{{ route('user.nfc.auto-topup.configure', $card) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="enabled" value="1" {{ $card->auto_topup_enabled ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน Auto Top-up</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เติมเมื่อยอดต่ำกว่า (฿)</label>
                        <input type="number" name="threshold" value="{{ $card->auto_topup_threshold }}" min="0"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนที่เติมแต่ละครั้ง (฿)</label>
                        <input type="number" name="amount" value="{{ $card->auto_topup_amount }}" min="100"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showAutoTopupModal = false"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg">
                            บันทึก
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
 * NFC Card Detail Component
 *
 * จัดการ modals และ UI interactions
 */
function nfcCardDetail() {
    return {
        showTopUpModal: false,
        showLimitsModal: false,
        showAutoTopupModal: false
    };
}
</script>
@endpush
@endsection

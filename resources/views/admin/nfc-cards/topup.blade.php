@extends('layouts.admin-v3')

@section('title', 'เติมเงินบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400"></i>
                เติมเงินบัตร NFC
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                เติมเงินเข้าบัตร <span class="font-mono font-semibold">{{ $nfcCard->card_number }}</span>
            </p>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-semibold mb-2">พบข้อผิดพลาด:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <form action="{{ route('admin.nfc-cards.topup', $nfcCard) }}" method="POST" x-data="{
                    amount: '',
                    quickAmounts: [100, 500, 1000, 5000, 10000],
                    setAmount(value) {
                        this.amount = value;
                        $refs.amountInput.focus();
                    },
                    get newBalance() {
                        const currentBalance = {{ $nfcCard->balance }};
                        const topupAmount = parseFloat(this.amount) || 0;
                        return (currentBalance + topupAmount).toFixed(2);
                    },
                    formatNumber(num) {
                        return new Intl.NumberFormat('th-TH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(num);
                    }
                }">
                    @csrf

                    <div class="p-6 space-y-6">
                        {{-- Current Balance Display --}}
                        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดเงินปัจจุบัน</div>
                                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                        ฿{{ number_format($nfcCard->balance, 2) }}
                                    </div>
                                </div>
                                <i class="fas fa-wallet text-4xl text-blue-600/20"></i>
                            </div>
                        </div>

                        {{-- Quick Amount Buttons --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-zap mr-1"></i>
                                จำนวนเงินด่วน
                            </label>
                            <div class="grid grid-cols-3 md:grid-cols-5 gap-3">
                                <template x-for="quickAmount in quickAmounts" :key="quickAmount">
                                    <button type="button"
                                            @click="setAmount(quickAmount)"
                                            :class="amount == quickAmount ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-600'"
                                            class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold transition-colors">
                                        <span x-text="'฿' + quickAmount.toLocaleString()"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Amount Input --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-money-bill mr-1"></i>
                                จำนวนเงินที่ต้องการเติม (บาท) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-xl font-bold">฿</span>
                                </div>
                                <input type="number"
                                       name="amount"
                                       x-ref="amountInput"
                                       x-model="amount"
                                       min="1"
                                       step="0.01"
                                       required
                                       placeholder="0.00"
                                       class="w-full pl-12 pr-4 py-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white text-2xl font-bold">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-info-circle"></i>
                                กรอกจำนวนเงินที่ต้องการเติม ขั้นต่ำ 1 บาท
                            </p>
                        </div>

                        {{-- New Balance Preview --}}
                        <div x-show="amount > 0" x-transition class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border-2 border-green-200 dark:border-green-800">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ยอดเงินหลังเติม:</span>
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    ฿<span x-text="formatNumber(newBalance)"></span>
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calculator"></i>
                                <span>
                                    {{ number_format($nfcCard->balance, 2) }}
                                    <span class="mx-1">+</span>
                                    <span x-text="formatNumber(amount)"></span>
                                    <span class="mx-1">=</span>
                                    <span x-text="formatNumber(newBalance)"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sticky-note mr-1"></i>
                                หมายเหตุ (ไม่บังคับ)
                            </label>
                            <textarea name="notes"
                                      rows="3"
                                      placeholder="บันทึกรายละเอียดการเติมเงิน เช่น เหตุผล, ผู้อนุมัติ ฯลฯ"
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white resize-none">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 rounded-b-xl flex justify-between items-center gap-4">
                        <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
                           class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-times"></i>
                            ยกเลิก
                        </a>

                        <button type="submit"
                                x-bind:disabled="!amount || amount <= 0"
                                class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-check"></i>
                            ยืนยันเติมเงิน
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Card Info Sidebar --}}
        <div class="space-y-6">
            {{-- Card Preview --}}
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 rounded-xl shadow-lg p-6 text-white">
                <h3 class="font-semibold text-green-100 mb-4 flex items-center gap-2">
                    <i class="fas fa-credit-card"></i>
                    ข้อมูลบัตร
                </h3>

                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-green-200 mb-1">เลขบัตร</div>
                        <div class="font-mono font-bold text-lg">{{ $nfcCard->card_number }}</div>
                    </div>

                    @if($nfcCard->card_name)
                        <div>
                            <div class="text-xs text-green-200 mb-1">ชื่อบัตร</div>
                            <div class="font-semibold">{{ $nfcCard->card_name }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs text-green-200 mb-1">ประเภท</div>
                        <div class="font-semibold">
                            @if($nfcCard->card_type === 'standard')
                                <i class="fas fa-star mr-1"></i>มาตรฐาน
                            @elseif($nfcCard->card_type === 'premium')
                                <i class="fas fa-gem mr-1"></i>พรีเมียม
                            @elseif($nfcCard->card_type === 'vip')
                                <i class="fas fa-crown mr-1"></i>วีไอพี
                            @endif
                        </div>
                    </div>

                    @if($nfcCard->user)
                        <div class="pt-3 mt-3 border-t border-green-400/30">
                            <div class="text-xs text-green-200 mb-1">ผู้ถือบัตร</div>
                            <div class="font-semibold">{{ $nfcCard->user->name }}</div>
                            <div class="text-xs text-green-200 mt-1">{{ $nfcCard->user->email }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Transaction History Preview --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-history text-orange-600"></i>
                    ธุรกรรมล่าสุด
                </h3>

                @if($nfcCard->transactions && $nfcCard->transactions()->count() > 0)
                    <div class="space-y-2">
                        @foreach($nfcCard->transactions()->latest()->limit(5)->get() as $transaction)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $transaction->type }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold {{ $transaction->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $transaction->amount >= 0 ? '+' : '' }}฿{{ number_format(abs($transaction->amount), 2) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-receipt text-3xl mb-2 text-gray-300 dark:text-gray-600"></i>
                        <p class="text-sm">ยังไม่มีธุรกรรม</p>
                    </div>
                @endif
            </div>

            {{-- Info Card --}}
            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl p-6 border border-amber-200 dark:border-amber-800">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <i class="fas fa-info-circle text-amber-600"></i>
                    ข้อมูลการเติมเงิน
                </h3>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>จำนวนเงินจะถูกเพิ่มเข้าบัตรทันที</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>ผู้ถือบัตรจะได้รับการแจ้งเตือน (ถ้ามี)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>ธุรกรรมจะถูกบันทึกในประวัติ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-600 mt-0.5"></i>
                        <span>ไม่มีค่าธรรมเนียมการเติมเงิน</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Remove number input spinners */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush

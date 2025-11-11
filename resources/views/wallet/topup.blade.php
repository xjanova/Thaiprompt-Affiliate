@extends('layouts.app')

@section('title', 'เติมเงินกระเป๋า')

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
    .shimmer {
        animation: shimmer 2s infinite;
        background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
        background-size: 1000px 100%;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4">
    <div class="max-w-7xl mx-auto">

        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full mb-4 animate-float shadow-2xl">
                <i class="fas fa-wallet text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">เติมเงินกระเป๋า</h1>
            <p class="text-gray-600 dark:text-gray-400">เติมเงินเข้ากระเป๋าของคุณอย่างปลอดภัยและรวดเร็ว</p>
        </div>

        <!-- Wallet Balance Card -->
        <div class="mb-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 via-green-600 to-emerald-700 rounded-3xl transform rotate-1"></div>
            <div class="relative bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl shadow-2xl p-8 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-emerald-100 text-sm mb-1">ยอดคงเหลือปัจจุบัน</p>
                        <h2 class="text-5xl font-bold" x-data="{ balance: {{ $wallet->balance ?? 0 }} }" x-text="balance.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                            {{ number_format($wallet->balance ?? 0, 2) }}
                        </h2>
                        <p class="text-emerald-100 text-sm mt-1">บาท (THB)</p>
                    </div>
                    <div class="text-6xl opacity-20 animate-float">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>

                <div class="flex items-center space-x-4 pt-4 border-t border-emerald-400/30">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-shield-alt text-emerald-200"></i>
                        <span class="text-sm text-emerald-100">ปลอดภัย 100%</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-bolt text-yellow-300"></i>
                        <span class="text-sm text-emerald-100">เข้าบัญชีทันที</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Left: Topup Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <i class="fas fa-money-bill-wave text-emerald-500 mr-3"></i>
                        เลือกจำนวนเงิน
                    </h3>

                    <form action="{{ route('wallet.topup.process') }}" method="POST" x-data="topupForm()">
                        @csrf

                        <!-- Amount Selection -->
                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">จำนวนเงินที่ต้องการเติม</label>

                            <!-- Quick Amount Buttons -->
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                @foreach([100, 500, 1000, 2000, 5000, 10000] as $quickAmount)
                                <button type="button" @click="selectAmount({{ $quickAmount }})"
                                    :class="amount === {{ $quickAmount }} ? 'bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-lg scale-105' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-md'"
                                    class="py-4 px-6 rounded-xl font-bold transition-all transform hover:scale-105">
                                    ฿{{ number_format($quickAmount) }}
                                </button>
                                @endforeach
                            </div>

                            <!-- Custom Amount Input -->
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-gray-400">฿</span>
                                <input type="number" name="amount" x-model="amount" min="100" max="100000" step="1" required
                                    class="w-full pl-12 pr-4 py-4 text-2xl font-bold border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-4 focus:ring-emerald-200 dark:focus:ring-emerald-800 focus:border-emerald-500 transition-all"
                                    placeholder="หรือกรอกจำนวนเอง">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ขั้นต่ำ ฿100 - สูงสุด ฿100,000</p>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">เลือกช่องทางการชำระเงิน</label>
                            <div class="space-y-3">
                                @foreach($paymentMethods as $method)
                                <label class="relative block cursor-pointer">
                                    <input type="radio" name="payment_method" value="{{ $method['id'] }}" x-model="paymentMethod" required class="peer sr-only">
                                    <div class="flex items-center justify-between p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 bg-white dark:bg-gray-700 transition-all transform peer-checked:scale-[1.02] peer-checked:shadow-lg">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 flex items-center justify-center bg-gray-100 dark:bg-gray-600 rounded-lg peer-checked:bg-emerald-100 dark:peer-checked:bg-emerald-800">
                                                @if($method['id'] === 'promptpay')
                                                    <i class="fas fa-qrcode text-2xl text-blue-600"></i>
                                                @elseif($method['id'] === 'credit_card')
                                                    <i class="fas fa-credit-card text-2xl text-purple-600"></i>
                                                @elseif($method['id'] === 'bank_transfer')
                                                    <i class="fas fa-university text-2xl text-indigo-600"></i>
                                                @else
                                                    <i class="fas fa-money-check-alt text-2xl text-emerald-600"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $method['name'] }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $method['description'] ?? 'ปลอดภัยและรวดเร็ว' }}</p>
                                            </div>
                                        </div>
                                        <div class="w-6 h-6 rounded-full border-2 border-gray-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 flex items-center justify-center">
                                            <i class="fas fa-check text-white text-xs hidden peer-checked:block"></i>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" :disabled="!amount || amount < 100 || amount > 100000 || !paymentMethod"
                            class="w-full bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-bold text-lg py-5 rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center space-x-3">
                            <i class="fas fa-lock"></i>
                            <span x-text="amount >= 100 ? 'ดำเนินการเติมเงิน ฿' + amount.toLocaleString() : 'เลือกจำนวนเงินและช่องทางชำระเงิน'"></span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Security Notice -->
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                                <div class="text-sm text-blue-800 dark:text-blue-300">
                                    <p class="font-semibold mb-1">ข้อมูลความปลอดภัย:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>การทำรายการทั้งหมดได้รับการเข้ารหัสด้วย SSL</li>
                                        <li>เราไม่เก็บข้อมูลบัตรเครดิตของคุณ</li>
                                        <li>เงินจะเข้าสู่กระเป๋าทันทีหลังชำระเงินสำเร็จ</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Transaction History -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-6 sticky top-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-history text-emerald-500 mr-2"></i>
                        ประวัติการเติมเงิน
                    </h3>

                    @if($recentTransactions->count() > 0)
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach($recentTransactions as $transaction)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-100 dark:border-gray-600 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">฿{{ number_format($transaction->amount, 2) }}</span>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($transaction->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @endif">
                                        @if($transaction->status === 'completed') สำเร็จ
                                        @elseif($transaction->status === 'pending') รอดำเนินการ
                                        @else ล้มเหลว
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $transaction->payment_method }}</span>
                                    <span>{{ $transaction->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการเติมเงิน</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>
function topupForm() {
    return {
        amount: 0,
        paymentMethod: '',
        selectAmount(value) {
            this.amount = value;
        }
    }
}
</script>
@endpush
@endsection

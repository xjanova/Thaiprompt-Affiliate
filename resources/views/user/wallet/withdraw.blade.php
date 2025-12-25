@extends('layouts.user-arrow-x')

@section('title', 'ถอนเงิน')

@push('scripts')
@if(config('turnstile.enabled') && config('turnstile.points.withdrawal_request'))
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endpush

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Red-Rose-Pink for Withdraw) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 dark:from-red-800 dark:via-rose-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-minus-circle text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">ถอนเงิน</h1>
                    <p class="text-red-100 text-lg mt-1">ถอนเงินจากกระเป๋าของคุณ</p>
                </div>
            </div>

            {{-- Available Balance --}}
            <div class="mt-6 glass-fusion rounded-xl p-6">
                <p class="text-red-100 text-sm mb-1">ยอดเงินที่สามารถถอนได้</p>
                <p class="text-5xl font-bold text-white drop-shadow-lg">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Withdrawal Form -->
    <x-arrow-x.card-v3 class="p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">แบบฟอร์มถอนเงิน</h2>

        @if($paymentMethods->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <span class="text-5xl block mb-3">🏦</span>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">ยังไม่มีช่องทางรับเงิน</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">กรุณาเพิ่มช่องทางรับเงินก่อนทำการถอน</p>
                <a href="{{ route('user.wallet.payment-methods') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    เพิ่มช่องทางรับเงิน
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('user.wallet.withdraw.submit') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เลือกช่องทางรับเงิน</label>
                    <select name="payment_method_id"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- เลือกช่องทางรับเงิน --</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">
                                @if($method->type === 'bank_transfer')
                                    🏦 {{ $method->bank_name }} - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'promptpay')
                                    💳 PromptPay - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'paypal')
                                    💰 PayPal - {{ $method->paypal_email }}
                                @endif
                                @if($method->is_default)
                                    ⭐ (ค่าเริ่มต้น)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                    <input type="number"
                           name="amount"
                           step="0.01"
                           min="1"
                           max="{{ $wallet->balance }}"
                           required
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="ระบุจำนวนเงินที่ต้องการถอน">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ยอดเงินสูงสุดที่ถอนได้: ฿{{ number_format($wallet->balance, 2) }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">PIN กระเป๋าเงิน (6 หลัก)</label>
                    <input type="password"
                           name="pin"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="ระบุ PIN 6 หลัก">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">กรอก PIN เพื่อยืนยันการถอนเงิน</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="user_note"
                              rows="3"
                              maxlength="500"
                              class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="หมายเหตุเพิ่มเติม (ไม่เกิน 500 ตัวอักษร)"></textarea>
                </div>

                <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">📋 เงื่อนไขการถอนเงิน</h4>
                    <ul class="space-y-1 text-sm text-blue-900 dark:text-blue-100">
                        <li>• จำนวนเงินขั้นต่ำในการถอนคือ 100 บาท</li>
                        <li>• ระบบจะตรวจสอบและดำเนินการภายใน 1-3 วันทำการ</li>
                        <li>• กรุณาตรวจสอบข้อมูลบัญชีให้ถูกต้อง</li>
                        <li>• หากพบปัญหา กรุณาติดต่อฝ่ายสนับสนุน</li>
                    </ul>
                </div>

                @if(config('turnstile.enabled') && config('turnstile.points.withdrawal_request'))
                <div class="flex justify-center">
                    <div class="cf-turnstile"
                         data-sitekey="{{ config('turnstile.site_key') }}"
                         data-theme="{{ config('turnstile.theme') }}"
                         data-size="{{ config('turnstile.size') }}">
                    </div>
                </div>
                @endif

                <div class="flex gap-3">
                    <a href="{{ route('user.wallet.index') }}"
                       class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold rounded-lg hover:bg-gray-300 transition text-center">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                        ยืนยันการถอนเงิน
                    </button>
                </div>
            </form>
        @endif
    </x-arrow-x.card-v3>

    <!-- Recent Withdrawals -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">📋 คำขอถอนเงินล่าสุด</h3>
            <a href="{{ route('user.wallet.withdrawals') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
            <span class="text-4xl block mb-2">💸</span>
            <p class="text-sm">คลิก "ดูทั้งหมด" เพื่อดูประวัติการถอนเงิน</p>
        </div>
    </x-arrow-x.card-v3>

    <!-- Instructions -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">💡 คำแนะนำ</h3>
        <ul class="space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ตรวจสอบช่องทางรับเงินให้ถูกต้องก่อนถอน</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>กรอกจำนวนเงินที่ต้องการถอนอย่างระมัดระวัง</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>เก็บรักษา PIN ของคุณไว้เป็นความลับ</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>หากลืม PIN สามารถรีเซ็ตได้ที่หน้าโปรไฟล์</span>
            </li>
        </ul>
    </div>
</div>

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
@endsection

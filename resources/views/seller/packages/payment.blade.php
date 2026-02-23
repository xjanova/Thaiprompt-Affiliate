@extends('layouts.seller')

@section('title', 'ชำระเงินแพ็คเกจ')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 pb-24 lg:pb-8">
    <div class="bg-white/10 backdrop-blur-md rounded-3xl shadow-2xl overflow-hidden border border-white/20">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 md:px-8 py-6 text-white">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-credit-card text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">ชำระเงินแพ็คเกจ</h1>
                    <p class="text-indigo-100 mt-1">กรุณาทำการชำระเงินเพื่อเริ่มใช้งานแพ็คเกจของคุณ</p>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8">
            {{-- Package Summary --}}
            <div class="bg-gradient-to-r from-purple-500/10 to-indigo-500/10 rounded-2xl p-6 mb-8 border border-purple-400/30">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-receipt text-purple-400"></i>
                    สรุปรายการ
                </h2>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-300">แพ็คเกจ:</span>
                        <span class="font-semibold text-white flex items-center gap-2">
                            @if($subscription->package->badge)
                                <span class="px-2 py-1 text-xs rounded-lg text-white" style="background-color: {{ $subscription->package->badge_color ?? '#9333EA' }}">
                                    {{ $subscription->package->badge }}
                                </span>
                            @endif
                            {{ $subscription->package->display_name }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-300">ประเภทการชำระ:</span>
                        <span class="font-semibold text-white">
                            @if($subscription->subscription_type === 'yearly')
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg text-sm">
                                    <i class="fas fa-calendar-alt mr-1"></i> รายปี (ประหยัด)
                                </span>
                            @else
                                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-lg text-sm">
                                    <i class="fas fa-calendar-week mr-1"></i> รายเดือน
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-300">ระยะเวลา:</span>
                        <span class="font-semibold text-white">
                            {{ $subscription->started_at->format('d/m/Y') }} - {{ $subscription->expires_at->format('d/m/Y') }}
                        </span>
                    </div>

                    @if($subscription->package->setup_fee > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">ค่าติดตั้ง:</span>
                            <span class="font-semibold text-white">฿{{ number_format($subscription->package->setup_fee, 2) }}</span>
                        </div>
                    @endif

                    {{-- Total --}}
                    <div class="border-t border-purple-400/30 pt-4 flex justify-between items-center">
                        <span class="text-lg font-bold text-white">ยอดรวม:</span>
                        <span class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                            ฿{{ number_format($subscription->amount + ($subscription->package->setup_fee ?? 0), 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Payment Methods --}}
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-wallet text-purple-400"></i>
                    เลือกวิธีการชำระเงิน
                </h2>

                <form action="{{ route('seller.packages.process-payment', $subscription->id) }}" method="POST" id="paymentForm">
                    @csrf

                    <div class="space-y-4">
                        {{-- QR Code Payment --}}
                        <label class="flex items-center p-4 bg-white/5 border-2 border-white/10 rounded-2xl cursor-pointer hover:border-purple-500/50 hover:bg-white/10 transition group">
                            <input type="radio" name="payment_method" value="promptpay_qr" class="w-5 h-5 mr-4 text-purple-600" checked>
                            <div class="flex-1">
                                <div class="font-semibold text-white group-hover:text-purple-300 transition">PromptPay QR Code</div>
                                <div class="text-sm text-gray-400">สแกน QR Code ผ่านแอปธนาคาร</div>
                            </div>
                            <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-qrcode text-2xl text-purple-400"></i>
                            </div>
                        </label>

                        {{-- Bank Transfer --}}
                        <label class="flex items-center p-4 bg-white/5 border-2 border-white/10 rounded-2xl cursor-pointer hover:border-purple-500/50 hover:bg-white/10 transition group">
                            <input type="radio" name="payment_method" value="bank_transfer" class="w-5 h-5 mr-4 text-purple-600">
                            <div class="flex-1">
                                <div class="font-semibold text-white group-hover:text-purple-300 transition">โอนเงินผ่านธนาคาร</div>
                                <div class="text-sm text-gray-400">โอนเงินเข้าบัญชีธนาคาร</div>
                            </div>
                            <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-university text-2xl text-blue-400"></i>
                            </div>
                        </label>

                        {{-- Credit Card (Coming Soon) --}}
                        <label class="flex items-center p-4 bg-white/5 border-2 border-white/10 rounded-2xl opacity-50 cursor-not-allowed">
                            <input type="radio" name="payment_method" value="credit_card" class="w-5 h-5 mr-4 text-purple-600" disabled>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-400">บัตรเครดิต/เดบิต</div>
                                <div class="text-sm text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-clock"></i> เร็วๆ นี้
                                </div>
                            </div>
                            <div class="w-12 h-12 bg-gray-600/20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-credit-card text-2xl text-gray-500"></i>
                            </div>
                        </label>

                        {{-- Wallet (If available) --}}
                        <label class="flex items-center p-4 bg-white/5 border-2 border-white/10 rounded-2xl cursor-pointer hover:border-purple-500/50 hover:bg-white/10 transition group">
                            <input type="radio" name="payment_method" value="wallet" class="w-5 h-5 mr-4 text-purple-600">
                            <div class="flex-1">
                                <div class="font-semibold text-white group-hover:text-purple-300 transition">ใช้ยอดเงินในกระเป๋า</div>
                                <div class="text-sm text-gray-400">ตัดจากยอดคงเหลือในกระเป๋าเงิน</div>
                            </div>
                            <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-wallet text-2xl text-green-400"></i>
                            </div>
                        </label>
                    </div>

                    {{-- Terms --}}
                    <div class="mt-6 p-4 bg-white/5 rounded-xl border border-white/10">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" name="accept_terms" class="mt-1 mr-3 w-5 h-5 text-purple-600 rounded" required>
                            <span class="text-sm text-gray-300">
                                ฉันยอมรับ
                                <a href="/terms-of-service.html" target="_blank" class="text-purple-400 hover:text-purple-300 hover:underline transition">ข้อกำหนดและเงื่อนไข</a>
                                และ
                                <a href="/privacy-policy.html" target="_blank" class="text-purple-400 hover:text-purple-300 hover:underline transition">นโยบายความเป็นส่วนตัว</a>
                            </span>
                        </label>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('seller.packages') }}"
                           class="flex-1 py-4 px-6 bg-gray-600 hover:bg-gray-500 text-white rounded-xl font-semibold text-center transition flex items-center justify-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            ย้อนกลับ
                        </a>
                        <button type="submit"
                                class="flex-1 py-4 px-6 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-bold text-center transition shadow-lg shadow-purple-500/30 flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            ดำเนินการชำระเงิน
                        </button>
                    </div>
                </form>
            </div>

            {{-- Payment Info --}}
            <div class="bg-blue-500/10 border border-blue-400/30 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="text-sm text-blue-200">
                        <p class="font-semibold mb-2 text-blue-100">ข้อมูลการชำระเงิน</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-300">
                            <li>การชำระเงินจะดำเนินการผ่านระบบที่ปลอดภัย</li>
                            <li>แพ็คเกจจะเริ่มใช้งานทันทีหลังชำระเงินสำเร็จ</li>
                            <li>หากมีปัญหาติดต่อทีมงาน support@thaiprompt.online</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Security Badge --}}
            <div class="mt-6 flex items-center justify-center gap-4 text-gray-400 text-sm">
                <span class="flex items-center gap-1">
                    <i class="fas fa-lock text-green-400"></i>
                    SSL Secured
                </span>
                <span class="flex items-center gap-1">
                    <i class="fas fa-shield-alt text-blue-400"></i>
                    PCI Compliant
                </span>
                <span class="flex items-center gap-1">
                    <i class="fas fa-user-shield text-purple-400"></i>
                    ข้อมูลปลอดภัย
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

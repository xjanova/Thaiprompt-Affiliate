@extends('layouts.app')

@section('title', 'ชำระเงิน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-white">
                <h1 class="text-3xl font-bold">ชำระเงินแพ็คเกจ</h1>
                <p class="mt-2 text-indigo-100">กรุณาทำการชำระเงินเพื่อเริ่มใช้งานแพ็คเกจของคุณ</p>
            </div>

            <div class="p-8">
                {{-- Package Summary --}}
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6 mb-8 border border-purple-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">สรุปรายการ</h2>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">แพ็คเกจ:</span>
                            <span class="font-semibold">{{ $subscription->package->display_name }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">ประเภทการชำระ:</span>
                            <span class="font-semibold">
                                @if($subscription->subscription_type === 'yearly')
                                    รายปี
                                @else
                                    รายเดือน
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">ระยะเวลา:</span>
                            <span class="font-semibold">
                                {{ $subscription->started_at->format('d/m/Y') }} - {{ $subscription->expires_at->format('d/m/Y') }}
                            </span>
                        </div>

                        @if($subscription->package->setup_fee > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">ค่าติดตั้ง:</span>
                                <span class="font-semibold">฿{{ number_format($subscription->package->setup_fee, 2) }}</span>
                            </div>
                        @endif

                        <div class="border-t border-purple-200 pt-3 flex justify-between text-lg">
                            <span class="font-bold text-gray-900">ยอดรวม:</span>
                            <span class="font-bold text-purple-600">฿{{ number_format($subscription->amount + ($subscription->package->setup_fee ?? 0), 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Payment Methods --}}
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">เลือกวิธีการชำระเงิน</h2>

                    <form action="{{ route('seller.packages.process-payment', $subscription->id) }}" method="POST" id="paymentForm">
                        @csrf

                        <div class="space-y-4">
                            {{-- QR Code Payment --}}
                            <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-500 transition">
                                <input type="radio" name="payment_method" value="promptpay_qr" class="mr-4" checked>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">PromptPay QR Code</div>
                                    <div class="text-sm text-gray-500">สแกน QR Code ผ่านแอปธนาคาร</div>
                                </div>
                                <div class="text-purple-600">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                </div>
                            </label>

                            {{-- Bank Transfer --}}
                            <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-500 transition">
                                <input type="radio" name="payment_method" value="bank_transfer" class="mr-4">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">โอนเงินผ่านธนาคาร</div>
                                    <div class="text-sm text-gray-500">โอนเงินเข้าบัญชีธนาคาร</div>
                                </div>
                                <div class="text-purple-600">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                            </label>

                            {{-- Credit Card --}}
                            <label class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-500 transition opacity-50">
                                <input type="radio" name="payment_method" value="credit_card" class="mr-4" disabled>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">บัตรเครดิต/เดบิต</div>
                                    <div class="text-sm text-gray-500">เร็วๆ นี้</div>
                                </div>
                                <div class="text-gray-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                            </label>
                        </div>

                        {{-- Terms --}}
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" name="accept_terms" class="mt-1 mr-3" required>
                                <span class="text-sm text-gray-600">
                                    ฉันยอมรับ <a href="#" class="text-purple-600 hover:underline">ข้อกำหนดและเงื่อนไข</a> และ <a href="#" class="text-purple-600 hover:underline">นโยบายความเป็นส่วนตัว</a>
                                </span>
                            </label>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('seller.packages') }}" class="flex-1 py-3 px-6 bg-gray-200 text-gray-700 rounded-lg font-semibold text-center hover:bg-gray-300 transition">
                                ยกเลิก
                            </a>
                            <button type="submit" class="flex-1 py-3 px-6 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-indigo-700 transition shadow-lg">
                                ดำเนินการชำระเงิน
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Payment Info --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">ข้อมูลการชำระเงิน</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>การชำระเงินจะดำเนินการผ่านระบบที่ปลอดภัย</li>
                                <li>แพ็คเกจจะเริ่มใช้งานทันทีหลังชำระเงินสำเร็จ</li>
                                <li>หากมีปัญหาติดต่อทีมงาน support@thaiprompt.online</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

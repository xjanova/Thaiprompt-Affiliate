@extends('layouts.seller')

@section('title', 'ถอนเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('seller.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">💸 ถอนเงิน</h1>
            </div>
            <p class="text-red-100">ถอนเงินจากกระเป๋าของร้านค้า</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-red-100 text-sm mb-1">ยอดเงินที่สามารถถอนได้</p>
                <p class="text-3xl font-bold">฿{{ number_format($balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex gap-3">
            <span class="text-xl">⚠️</span>
            <div>
                <h3 class="font-bold text-red-900 mb-1">เกิดข้อผิดพลาด</h3>
                <ul class="list-disc list-inside text-red-800 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Success Message -->
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex gap-3">
            <span class="text-xl">✅</span>
            <div>
                <p class="text-green-800 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Withdrawal Form -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">แบบฟอร์มถอนเงิน</h2>

        @if($paymentMethods->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <span class="text-5xl block mb-3">🏦</span>
                <h3 class="text-lg font-bold text-gray-900 mb-2">ยังไม่มีช่องทางรับเงิน</h3>
                <p class="text-gray-600 mb-4">กรุณาเพิ่มช่องทางรับเงินก่อนทำการถอน</p>
                <a href="{{ route('user.wallet.payment-methods') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    เพิ่มช่องทางรับเงิน
                </a>
            </div>
        @elseif($balance <= 0)
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                <span class="text-5xl block mb-3">💰</span>
                <h3 class="text-lg font-bold text-gray-900 mb-2">ไม่มียอดเงินที่สามารถถอนได้</h3>
                <p class="text-gray-600 mb-4">ยอดเงินในกระเป๋าของคุณเป็น 0 บาท</p>
                <a href="{{ route('seller.dashboard') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    กลับไปหน้าแดชบอร์ด
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('seller.wallet.withdraw.submit') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">เลือกช่องทางรับเงิน</label>
                    <select name="payment_method_id"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- เลือกช่องทางรับเงิน --</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>
                                @if($method->type === 'bank_transfer')
                                    🏦 {{ $method->bank_name }} - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'promptpay')
                                    💳 PromptPay - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'paypal')
                                    💰 PayPal - {{ $method->paypal_email }}
                                @else
                                    📌 {{ $method->type }} - {{ $method->account_name }}
                                @endif
                                @if($method->is_default)
                                    ⭐ (ค่าเริ่มต้น)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนเงิน (บาท)</label>
                    <input type="number"
                           name="amount"
                           step="0.01"
                           min="{{ $withdrawalSettings['min_amount'] }}"
                           max="{{ min($balance, $withdrawalSettings['max_amount']) }}"
                           value="{{ old('amount') }}"
                           required
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="ระบุจำนวนเงินที่ต้องการถอน">
                    <p class="text-xs text-gray-500 mt-1">
                        ขั้นต่ำ: ฿{{ number_format($withdrawalSettings['min_amount'], 2) }} |
                        สูงสุด: ฿{{ number_format(min($balance, $withdrawalSettings['max_amount']), 2) }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="user_note"
                              rows="3"
                              maxlength="500"
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="หมายเหตุเพิ่มเติม (ไม่เกิน 500 ตัวอักษร)">{{ old('user_note') }}</textarea>
                </div>

                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">📋 เงื่อนไขการถอนเงิน</h4>
                    <ul class="space-y-1 text-sm text-blue-800">
                        <li>• จำนวนเงินขั้นต่ำในการถอนคือ {{ number_format($withdrawalSettings['min_amount']) }} บาท</li>
                        <li>• ระบบจะตรวจสอบและดำเนินการภายใน 1-3 วันทำการ</li>
                        <li>• กรุณาตรวจสอบข้อมูลบัญชีให้ถูกต้อง</li>
                        <li>• หากพบปัญหา กรุณาติดต่อฝ่ายสนับสนุน</li>
                    </ul>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('seller.wallet.index') }}"
                       class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition text-center">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                        ยืนยันการถอนเงิน
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- Recent Withdrawals -->
    @if($recentWithdrawals && $recentWithdrawals->count() > 0)
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">📋 คำขอถอนเงินล่าสุด</h3>
            <a href="{{ route('seller.wallet.withdrawals') }}" class="text-sm text-red-600 hover:text-red-700 font-semibold">
                ดูทั้งหมด →
            </a>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($recentWithdrawals as $withdrawal)
            <div class="py-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">{{ $withdrawal->request_id }}</p>
                    <p class="text-sm text-gray-500">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-900">฿{{ number_format($withdrawal->amount, 2) }}</p>
                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                        @if($withdrawal->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($withdrawal->status === 'processing') bg-blue-100 text-blue-800
                        @elseif($withdrawal->status === 'completed') bg-green-100 text-green-800
                        @elseif($withdrawal->status === 'rejected') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $withdrawal->status_label }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

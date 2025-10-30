@extends('layouts.user')

@section('title', 'ฝากเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">💵 ฝากเงิน</h1>
            </div>
            <p class="text-green-100">เติมเงินเข้ากระเป๋าของคุณ</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-green-100 text-sm mb-1">ยอดเงินปัจจุบัน</p>
                <p class="text-3xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">เลือกช่องทางการชำระเงิน</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($availableGateways as $key => $gateway)
                @if($gateway['enabled'])
                    <div class="border-2 border-gray-200 hover:border-indigo-500 rounded-xl p-6 cursor-pointer transition group"
                         onclick="selectPaymentMethod('{{ $key }}')">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">{{ $gateway['icon'] }}</div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition">
                                    {{ $gateway['name'] }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $gateway['description'] }}</p>

                                @if($key === 'promptpay')
                                    <div class="mt-3">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">
                                            ⚡ ทันที
                                        </span>
                                    </div>
                                @elseif($key === 'bank_transfer')
                                    <div class="mt-3">
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">
                                            ⏰ รออนุมัติ
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- PromptPay Payment Form -->
    <div id="promptpay-form" class="bg-white rounded-2xl shadow-xl p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 mb-4">💳 ชำระเงินผ่าน พร้อมเพย์</h3>

        <form method="POST" action="{{ route('user.wallet.deposit.promptpay') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="1"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ระบุจำนวนเงิน">
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800">
                    <strong>หมายเหตุ:</strong> หลังจากกดยืนยัน คุณจะได้รับ QR Code สำหรับชำระเงิน
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="hideAllForms()"
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    สร้าง QR Code
                </button>
            </div>
        </form>
    </div>

    <!-- Bank Transfer Form -->
    <div id="bank_transfer-form" class="bg-white rounded-2xl shadow-xl p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 mb-4">🏦 โอนผ่านธนาคาร</h3>

        <form method="POST" action="{{ route('user.wallet.deposit.bank-transfer') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="1"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ระบุจำนวนเงิน">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">อัพโหลดสลิปการโอนเงิน</label>
                <input type="file"
                       name="slip"
                       accept="image/*"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">หมายเหตุ (ถ้าม)</label>
                <textarea name="note"
                          rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="หมายเหตุเพิ่มเติม"></textarea>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-sm text-yellow-800">
                    <strong>หมายเหตุ:</strong> หลังจากส่งคำขอ แอดมินจะตรวจสอบและอนุมัติภายใน 24 ชั่วโมง
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="hideAllForms()"
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    ส่งคำขอ
                </button>
            </div>
        </form>
    </div>

    <!-- Stripe Form -->
    <div id="stripe-form" class="bg-white rounded-2xl shadow-xl p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 mb-4">💰 ชำระเงินด้วย Stripe</h3>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <strong>ข้อมูล:</strong> ระบบ Stripe กำลังอยู่ในระหว่างการพัฒนา กรุณาเลือกช่องทางอื่น
            </p>
        </div>

        <button type="button"
                onclick="hideAllForms()"
                class="w-full mt-4 px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
            กลับ
        </button>
    </div>

    <!-- PayPal Form -->
    <div id="paypal-form" class="bg-white rounded-2xl shadow-xl p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 mb-4">💵 ชำระเงินด้วย PayPal</h3>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <strong>ข้อมูล:</strong> ระบบ PayPal กำลังอยู่ในระหว่างการพัฒนา กรุณาเลือกช่องทางอื่น
            </p>
        </div>

        <button type="button"
                onclick="hideAllForms()"
                class="w-full mt-4 px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
            กลับ
        </button>
    </div>

    <!-- Instructions -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">📋 คำแนะนำ</h3>
        <ul class="space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ตรวจสอบจำนวนเงินให้ถูกต้องก่อนทำรายการ</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>สำหรับการโอนธนาคาร กรุณาอัพโหลดสลิปที่ชัดเจน</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ระบบจะบันทึกรายการธุรกรรมทั้งหมดอัตโนมัติ</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>หากมีปัญหา กรุณาติดต่อฝ่ายสนับสนุน</span>
            </li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
function selectPaymentMethod(method) {
    hideAllForms();

    const formId = method + '-form';
    const formElement = document.getElementById(formId);

    if (formElement) {
        formElement.classList.remove('hidden');
        formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function hideAllForms() {
    document.querySelectorAll('[id$="-form"]').forEach(form => {
        form.classList.add('hidden');
    });
}
</script>
@endpush
@endsection

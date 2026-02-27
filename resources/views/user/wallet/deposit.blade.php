@extends('layouts.user-arrow-x')

@section('title', 'ฝากเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="p-4 bg-red-100 dark:bg-red-900/30 border-2 border-red-300 dark:border-red-600 rounded-xl">
            <div class="flex items-center gap-3">
                <span class="text-2xl">❌</span>
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="p-4 bg-green-100 dark:bg-green-900/30 border-2 border-green-300 dark:border-green-600 rounded-xl">
            <div class="flex items-center gap-3">
                <span class="text-2xl">✅</span>
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-100 dark:bg-red-900/30 border-2 border-red-300 dark:border-red-600 rounded-xl">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <ul class="text-sm text-red-800 dark:text-red-200 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Premium Hero Header (Green-Emerald-Teal for Deposit) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-800 dark:via-emerald-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-plus-circle text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">ฝากเงิน</h1>
                    <p class="text-green-100 text-lg mt-1">เติมเงินเข้ากระเป๋าของคุณ</p>
                </div>
            </div>

            {{-- Current Balance --}}
            <div class="mt-6 glass-fusion rounded-xl p-6">
                <p class="text-green-100 text-sm mb-1">ยอดเงินปัจจุบัน</p>
                <p class="text-5xl font-bold text-white drop-shadow-lg">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <x-arrow-x.card-v3 class="p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">เลือกช่องทางการชำระเงิน</h2>

        {{-- ⚠️ แจ้งเตือน: ยอดต่ำกว่า 100 บาท ต้องใช้ PromptPay เท่านั้น --}}
        <div id="low-amount-notice" class="hidden mb-4 p-4 bg-amber-50 dark:bg-amber-900/30 border-2 border-amber-300 dark:border-amber-600 rounded-lg">
            <p class="text-sm text-amber-800 dark:text-amber-200 font-medium">
                ⚠️ <strong>ยอดเงินต่ำกว่า 100 บาท</strong> — กรุณาใช้ช่องทาง <strong>พร้อมเพย์</strong> เท่านั้น (โอนผ่านธนาคารไม่รองรับยอดต่ำกว่า 100 บาท เนื่องจากระบบ SMS แจ้งเตือนไม่ทำงาน)
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($availableGateways as $key => $gateway)
                @if($gateway['enabled'])
                    <div id="gateway-card-{{ $key }}"
                         class="border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-500 dark:hover:border-indigo-400 bg-gradient-to-br from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-xl p-6 cursor-pointer transition group"
                         onclick="selectPaymentMethod('{{ $key }}')">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">{{ $gateway['icon'] }}</div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                    {{ $gateway['name'] }}
                                </h3>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ $gateway['description'] }}</p>

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
                                        {{-- แจ้งเตือนขั้นต่ำ 100 บาท --}}
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold ml-1">
                                            ขั้นต่ำ ฿100
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </x-arrow-x.card-v3>

    <!-- PromptPay Payment Form -->
    <x-arrow-x.card-v3 id="promptpay-form" class="p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">💳 ชำระเงินผ่าน พร้อมเพย์</h3>

        <form method="POST" action="{{ route('user.wallet.deposit.promptpay') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="1"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ระบุจำนวนเงิน">
            </div>

            <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
                <p class="text-sm text-blue-900 dark:text-blue-100 font-medium">
                    <strong>หมายเหตุ:</strong> หลังจากกดยืนยัน คุณจะได้รับ QR Code สำหรับชำระเงิน
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="hideAllForms()"
                        class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    สร้าง QR Code
                </button>
            </div>
        </form>
    </x-arrow-x.card-v3>

    <!-- Bank Transfer Form -->
    <x-arrow-x.card-v3 id="bank_transfer-form" class="p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">🏦 โอนผ่านธนาคาร</h3>

        {{-- ⚠️ แจ้งขั้นต่ำ 100 บาท --}}
        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-600 rounded-lg">
            <p class="text-sm text-amber-800 dark:text-amber-200">
                ⚠️ ขั้นต่ำ <strong>100 บาท</strong> — ยอดต่ำกว่า 100 บาท กรุณาใช้ <a href="javascript:selectPaymentMethod('promptpay')" class="text-indigo-600 dark:text-indigo-400 underline font-semibold">พร้อมเพย์</a> แทน
            </p>
        </div>

        <form method="POST" action="{{ route('user.wallet.deposit.bank-transfer') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="100"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="ขั้นต่ำ 100 บาท">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">อัพโหลดสลิปการโอนเงิน</label>
                <input type="file"
                       name="slip"
                       accept="image/*"
                       required
                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                <textarea name="note"
                          rows="3"
                          class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="หมายเหตุเพิ่มเติม"></textarea>
            </div>

            <div class="bg-yellow-100 dark:bg-yellow-900 border-2 border-yellow-300 dark:border-yellow-700 rounded-lg p-4">
                <p class="text-sm text-yellow-900 dark:text-yellow-100 font-medium">
                    <strong>หมายเหตุ:</strong> หลังจากส่งคำขอ แอดมินจะตรวจสอบและอนุมัติภายใน 24 ชั่วโมง
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="hideAllForms()"
                        class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    ส่งคำขอ
                </button>
            </div>
        </form>
    </x-arrow-x.card-v3>

    <!-- Stripe Form -->
    <x-arrow-x.card-v3 id="stripe-form" class="p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">💰 ชำระเงินด้วย Stripe</h3>

        <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
            <p class="text-sm text-blue-900 dark:text-blue-100 font-medium">
                <strong>ข้อมูล:</strong> ระบบ Stripe กำลังอยู่ในระหว่างการพัฒนา กรุณาเลือกช่องทางอื่น
            </p>
        </div>

        <button type="button"
                onclick="hideAllForms()"
                class="w-full mt-4 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
            กลับ
        </button>
    </x-arrow-x.card-v3>

    <!-- PayPal Form -->
    <x-arrow-x.card-v3 id="paypal-form" class="p-6 hidden">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">💵 ชำระเงินด้วย PayPal</h3>

        <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4">
            <p class="text-sm text-blue-900 dark:text-blue-100 font-medium">
                <strong>ข้อมูล:</strong> ระบบ PayPal กำลังอยู่ในระหว่างการพัฒนา กรุณาเลือกช่องทางอื่น
            </p>
        </div>

        <button type="button"
                onclick="hideAllForms()"
                class="w-full mt-4 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
            กลับ
        </button>
    </x-arrow-x.card-v3>

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
/**
 * ค่าขั้นต่ำสำหรับโอนผ่านธนาคาร (SMS ไม่แจ้งเตือนถ้ายอดต่ำกว่านี้)
 */
const BANK_TRANSFER_MIN_AMOUNT = 100;

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

/**
 * ✅ ตรวจสอบยอดเงินใน PromptPay form แบบ realtime
 * ถ้ายอดต่ำกว่า 100 → ซ่อน bank transfer card + แสดงข้อความแจ้ง
 * ถ้ายอด >= 100 → แสดง bank transfer card ปกติ
 */
function checkAmountForBankTransfer(amount) {
    const bankCard = document.getElementById('gateway-card-bank_transfer');
    const notice = document.getElementById('low-amount-notice');

    if (!bankCard || !notice) return;

    if (amount > 0 && amount < BANK_TRANSFER_MIN_AMOUNT) {
        // ยอดต่ำกว่า 100 → ซ่อน bank transfer + แจ้งเตือน
        bankCard.classList.add('opacity-50', 'pointer-events-none');
        bankCard.title = 'ยอดต่ำกว่า 100 บาท กรุณาใช้พร้อมเพย์';
        notice.classList.remove('hidden');
    } else {
        // ยอดปกติ → แสดง bank transfer
        bankCard.classList.remove('opacity-50', 'pointer-events-none');
        bankCard.title = '';
        notice.classList.add('hidden');
    }
}

// ฟังการพิมพ์จำนวนเงินในฟอร์ม PromptPay
document.addEventListener('DOMContentLoaded', function() {
    const promptPayAmountInput = document.querySelector('#promptpay-form input[name="amount"]');
    if (promptPayAmountInput) {
        promptPayAmountInput.addEventListener('input', function() {
            checkAmountForBankTransfer(parseFloat(this.value) || 0);
        });
    }

    // ฟังการพิมพ์จำนวนเงินในฟอร์ม Bank Transfer ด้วย (ป้องกัน submit ถ้า < 100)
    const bankForm = document.querySelector('#bank_transfer-form form');
    if (bankForm) {
        bankForm.addEventListener('submit', function(e) {
            const amount = parseFloat(bankForm.querySelector('input[name="amount"]').value) || 0;
            if (amount < BANK_TRANSFER_MIN_AMOUNT) {
                e.preventDefault();
                alert('ยอดขั้นต่ำสำหรับโอนผ่านธนาคาร คือ ' + BANK_TRANSFER_MIN_AMOUNT + ' บาท\nกรุณาใช้ช่องทาง พร้อมเพย์ แทน');
                return false;
            }
        });
    }
});
</script>
@endpush

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

@extends('layouts.user-arrow-x')

@section('title', 'เติมเงิน Wallet')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">💰 เติมเงิน Wallet</h1>
            </div>
            <p class="text-purple-100">เลือกจำนวนเงินที่ต้องการเติม</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-purple-100 text-sm mb-1">ยอดเงินปัจจุบัน</p>
                <p class="text-3xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Topup Amount Selection -->
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">เลือกจำนวนเงินที่ต้องการเติม</h2>

        @if($topupPackages->isEmpty())
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-300 dark:border-yellow-600 rounded-lg">
                <p class="text-yellow-800 dark:text-yellow-300">
                    ⚠️ <strong>ข้อมูลแพ็คเกจยังไม่พร้อม</strong><br>
                    กรุณาติดต่อผู้ดูแลระบบเพื่อรัน seeder: <code class="bg-yellow-100 dark:bg-yellow-800 px-2 py-1 rounded">php artisan db:seed --class=WalletTopupPackagesSeeder</code>
                </p>
            </div>
        @endif

        <form id="topup-form" action="{{ route('user.wallet.topup.process') }}" method="POST">
            @csrf
            <input type="hidden" id="topup-amount" name="amount" value="">

            <!-- Quick Amount Buttons -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">เลือกจำนวนเงินด่วน</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @php
                        $quickAmounts = [100, 300, 500, 1000, 2000, 5000, 10000];
                    @endphp
                    @foreach($quickAmounts as $amount)
                        <button type="button"
                                onclick="selectAmount({{ $amount }})"
                                class="quick-amount-btn px-4 py-3 border-2 border-gray-300 dark:border-gray-600 hover:border-purple-500 dark:hover:border-purple-400 bg-white dark:bg-gray-700 rounded-lg transition hover:shadow-lg font-semibold text-gray-900 dark:text-gray-100"
                                data-amount="{{ $amount }}">
                            ฿{{ number_format($amount) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Custom Amount Input -->
            <div class="mb-6">
                <label for="custom-amount" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    หรือระบุจำนวนเงินเอง
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-bold text-lg">฿</span>
                    <input type="number"
                           id="custom-amount"
                           name="custom_amount"
                           min="100"
                           max="100000"
                           step="1"
                           placeholder="ระบุจำนวนเงิน (ขั้นต่ำ 100 บาท)"
                           class="w-full pl-10 pr-4 py-4 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg font-semibold"
                           oninput="updateCustomAmount(this.value)">
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    ⚠️ จำนวนเงินขั้นต่ำ 100 บาท, สูงสุด 100,000 บาท
                </p>
            </div>

            <!-- Selected Amount Display -->
            <div id="selected-amount-display" class="hidden mb-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border-2 border-purple-300 dark:border-purple-600 rounded-xl">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">จำนวนเงินที่เลือก</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400" id="selected-amount-text">฿0</p>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    id="submit-btn"
                    disabled
                    class="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-bold text-lg rounded-lg transition transform hover:scale-105 shadow-lg disabled:transform-none">
                🛒 ดำเนินการเติมเงิน
            </button>
        </form>
    </div>

    <!-- Info Box -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">ℹ️</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">วิธีการเติมเงิน</h3>
                <ol class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-decimal list-inside">
                    <li>เลือกจำนวนเงินที่ต้องการเติม (คลิกปุ่มด่วนหรือกรอกเอง)</li>
                    <li>กดปุ่ม "ดำเนินการเติมเงิน"</li>
                    <li>ระบบจะพาไปหน้า Checkout เพื่อชำระเงิน</li>
                    <li>เลือกช่องทางการชำระเงิน (PromptPay, โอนธนาคาร, บัตรเครดิต, ฯลฯ)</li>
                    <li>หลังชำระเงินสำเร็จ ยอดเงินจะเข้า Wallet ทันที</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Benefits Box -->
    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">✅</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">ใช้เงินใน Wallet ทำอะไรได้บ้าง?</h3>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside">
                    <li>🎮 บันทึกคะแนนเกม Snake.io (1 แต้ม/ครั้ง)</li>
                    <li>🛍️ ซื้อสินค้าและบริการในระบบ</li>
                    <li>🤖 เช่าบอท AI และบริการ Automation</li>
                    <li>📚 ซื้อคอร์สเรียนในระบบ Academy</li>
                    <li>💼 ชำระค่าแพ็คเกจ MLM และ Affiliate</li>
                    <li>⚡ ชำระเงินรวดเร็ว ไม่ต้องกรอกข้อมูลซ้ำ</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedAmount = 0;

/**
 * เลือกจำนวนเงินจากปุ่มด่วน
 */
function selectAmount(amount) {
    selectedAmount = amount;

    // ล้างค่า custom input
    document.getElementById('custom-amount').value = '';

    // อัพเดต UI
    updateUI(amount);

    // ตั้งค่าจำนวนเงินใน hidden field
    document.getElementById('topup-amount').value = amount;

    // Highlight ปุ่มที่เลือก
    document.querySelectorAll('.quick-amount-btn').forEach(btn => {
        if (parseInt(btn.dataset.amount) === amount) {
            btn.classList.add('border-purple-500', 'bg-purple-50', 'dark:bg-purple-900/30');
            btn.classList.remove('border-gray-300', 'dark:border-gray-600');
        } else {
            btn.classList.remove('border-purple-500', 'bg-purple-50', 'dark:bg-purple-900/30');
            btn.classList.add('border-gray-300', 'dark:border-gray-600');
        }
    });
}

/**
 * อัพเดตจำนวนเงินจาก custom input
 */
function updateCustomAmount(value) {
    const amount = parseInt(value) || 0;

    if (amount < 100 && amount > 0) {
        return; // ไม่ทำอะไรถ้าน้อยกว่า 100
    }

    selectedAmount = amount;

    // ล้าง highlight ปุ่มด่วน
    document.querySelectorAll('.quick-amount-btn').forEach(btn => {
        btn.classList.remove('border-purple-500', 'bg-purple-50', 'dark:bg-purple-900/30');
        btn.classList.add('border-gray-300', 'dark:border-gray-600');
    });

    // อัพเดต UI
    updateUI(amount);

    // ตั้งค่าจำนวนเงินใน hidden field
    document.getElementById('topup-amount').value = amount;
}

/**
 * อัพเดต UI
 */
function updateUI(amount) {
    const displayEl = document.getElementById('selected-amount-display');
    const amountTextEl = document.getElementById('selected-amount-text');
    const submitBtn = document.getElementById('submit-btn');

    if (amount >= 100) {
        displayEl.classList.remove('hidden');
        amountTextEl.textContent = '฿' + amount.toLocaleString();
        submitBtn.disabled = false;
    } else {
        displayEl.classList.add('hidden');
        submitBtn.disabled = true;
    }
}

// Form validation
document.getElementById('topup-form').addEventListener('submit', function(e) {
    if (selectedAmount < 100) {
        e.preventDefault();
        alert('กรุณาเลือกจำนวนเงินขั้นต่ำ 100 บาท');
        return false;
    }

    if (selectedAmount > 100000) {
        e.preventDefault();
        alert('จำนวนเงินสูงสุด 100,000 บาท');
        return false;
    }

    console.log('[Topup] เลือกจำนวนเงิน:', selectedAmount, 'บาท');
    return true;
});
</script>
@endpush
@endsection

@extends('layouts.user-arrow-x')

@section('title', 'ถอนเหรียญคริปโต')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('user.crypto-wallet.index') }}" class="text-amber-600 hover:text-amber-700 font-medium mb-2 inline-block">
            ← กลับไปหน้าหลัก
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">ถอนเหรียญคริปโต</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">โอนเหรียญไปยังกระเป๋าภายนอก</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-400 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Balances Overview -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6">
        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4">ยอดคงเหลือของคุณ</h3>

        @if(count($balances) > 0)
            <div class="grid gap-3">
                @foreach($balances as $code => $balance)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            @php
                                $iconPath = public_path('icons/cryptocurrency/' . strtolower($code) . '.svg');
                            @endphp
                            @if(file_exists($iconPath))
                                <img src="{{ asset('icons/cryptocurrency/' . strtolower($code) . '.svg') }}"
                                     alt="{{ $code }}"
                                     class="w-10 h-10 mr-3">
                            @else
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                    {{ substr($code, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $balance['currency']->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $code }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($balance['balance'], 8) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">≈ ฿{{ number_format($balance['balance_thb'], 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6">
                <div class="text-5xl mb-3">💰</div>
                <p class="text-gray-500 dark:text-gray-400">ไม่มียอดคงเหลือ</p>
            </div>
        @endif
    </div>

    <!-- Withdrawal Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8">
        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-6">ฟอร์มถอนเหรียญ</h3>

        <form action="{{ route('user.crypto-wallet.withdraw.submit') }}" method="POST" x-data="{ selectedCurrency: null, amount: '', fee: 0, netAmount: 0 }">
            @csrf

            <!-- Select Currency -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกสกุลเงิน</label>
                <select name="currency_id" required
                        @change="selectedCurrency = $event.target.options[$event.target.selectedIndex].dataset.currency ? JSON.parse($event.target.options[$event.target.selectedIndex].dataset.currency) : null; fee = selectedCurrency ? parseFloat(selectedCurrency.withdrawal_fee) : 0; calculateNet()"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">-- เลือกสกุลเงิน --</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->id }}"
                                data-currency='@json(["code" => $curr->code, "min_withdrawal" => $curr->min_withdrawal, "max_withdrawal" => $curr->max_withdrawal, "withdrawal_fee" => $curr->withdrawal_fee, "network" => $curr->network])'>
                            {{ $curr->code }} ({{ strtoupper($curr->network) }})
                            @if(isset($balances[$curr->code]))
                                - มี {{ number_format($balances[$curr->code]['balance'], 8) }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Destination Address -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ที่อยู่ปลายทาง (Destination Address)</label>
                <input type="text" name="to_address" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100 font-mono text-sm"
                       placeholder="0x...">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">กรุณาตรวจสอบที่อยู่ให้ถูกต้อง การถอนไปที่อยู่ผิดจะไม่สามารถกู้คืนได้</p>
            </div>

            <!-- Amount -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนที่ต้องการถอน</label>
                <input type="number" name="amount" step="0.00000001" required x-model="amount" @input="calculateNet()"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100"
                       placeholder="0.00000000">

                <template x-if="selectedCurrency">
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <div>ขั้นต่ำ: <span x-text="selectedCurrency.min_withdrawal"></span> <span x-text="selectedCurrency.code"></span></div>
                        <div x-show="selectedCurrency.max_withdrawal">สูงสุด: <span x-text="selectedCurrency.max_withdrawal"></span> <span x-text="selectedCurrency.code"></span></div>
                    </div>
                </template>
            </div>

            <!-- Fee Display -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6" x-show="selectedCurrency && amount > 0">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">จำนวนถอน:</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                            <span x-text="parseFloat(amount).toFixed(8)"></span> <span x-text="selectedCurrency ? selectedCurrency.code : ''"></span>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียมเครือข่าย:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">
                            - <span x-text="fee.toFixed(8)"></span> <span x-text="selectedCurrency ? selectedCurrency.code : ''"></span>
                        </span>
                    </div>
                    <div class="border-t border-gray-300 dark:border-gray-600 pt-2 flex justify-between">
                        <span class="font-bold text-gray-800 dark:text-gray-100">จำนวนที่จะได้รับ:</span>
                        <span class="font-bold text-green-600 dark:text-green-400">
                            <span x-text="netAmount.toFixed(8)"></span> <span x-text="selectedCurrency ? selectedCurrency.code : ''"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- PIN -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PIN กระเป๋า</label>
                <input type="password" name="pin" required minlength="4" maxlength="6"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100"
                       placeholder="กรอก PIN ของคุณ">
            </div>

            <!-- Note (Optional) -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                <textarea name="note" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-gray-100"
                          placeholder="หมายเหตุส่วนตัว..."></textarea>
            </div>

            <!-- Terms -->
            <div class="mb-6">
                <label class="flex items-start">
                    <input type="checkbox" required class="mt-1 mr-2">
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        ฉันยืนยันว่าได้ตรวจสอบที่อยู่ปลายทางและเครือข่ายแล้ว และเข้าใจว่าการส่งไปที่อยู่ผิดจะไม่สามารถกู้คืนได้
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white py-4 rounded-lg font-bold text-lg hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg">
                ส่งคำขอถอนเงิน
            </button>
        </form>
    </div>

    <!-- Warning -->
    <div class="mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6">
        <h4 class="font-bold text-red-900 dark:text-red-300 mb-2 flex items-center">
            <span class="text-xl mr-2">⚠️</span>
            คำเตือนสำคัญ!
        </h4>
        <ul class="list-disc list-inside space-y-1 text-red-800 dark:text-red-300 text-sm">
            <li>ตรวจสอบที่อยู่ปลายทางและเครือข่ายให้ถูกต้องก่อนยืนยัน</li>
            <li>การถอนไปที่อยู่ผิดหรือเครือข่ายผิดจะทำให้เหรียญสูญหายถาวร</li>
            <li>คำขอถอนจำนวนมากอาจต้องใช้เวลาตรวจสอบเพิ่มเติม</li>
            <li>ค่าธรรมเนียมเครือข่ายขึ้นอยู่กับความหนาแน่นของ Blockchain</li>
        </ul>
    </div>
</div>

<script>
function calculateNet() {
    const amount = parseFloat(this.amount) || 0;
    const fee = parseFloat(this.fee) || 0;
    this.netAmount = Math.max(0, amount - fee);
}
</script>
@endsection

{{--
    คอมโพเนนต์อธิบายให้ลูกค้าเข้าใจว่าทำไมยอดโอนมีจุดทศนิยม
    ใช้ในหน้าชำระเงิน, หน้ายืนยันออเดอร์, และที่อื่นที่เกี่ยวข้อง

    การใช้งาน:
    @include('components.sms-payment-explanation')
    @include('components.sms-payment-explanation', ['compact' => true])
    @include('components.sms-payment-explanation', ['compact' => true, 'amount' => 500.37, 'originalAmount' => 500])
--}}

@php
    $explanation = config('smschecker.customer_explanation', []);
    $isCompact = $compact ?? false;
    $displayAmount = $amount ?? null;
    $displayOriginal = $originalAmount ?? null;
    $hasDecimalDiff = $displayAmount && $displayOriginal && $displayAmount != $displayOriginal;
@endphp

@if($isCompact)
    {{-- แบบกะทัดรัด สำหรับแสดงในฟอร์มชำระเงิน --}}
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-sm">
        <p class="font-medium text-blue-800 dark:text-blue-300 mb-1">
            💡 {{ $explanation['title'] ?? 'ทำไมยอดโอนมีจุดทศนิยม?' }}
        </p>
        @if($hasDecimalDiff)
        <p class="text-blue-700 dark:text-blue-400 mb-2">
            ยอดสินค้า <span class="font-semibold">฿{{ number_format($displayOriginal, 2) }}</span>
            → ยอดที่ต้องโอน <span class="font-bold text-blue-900 dark:text-blue-100 text-base">฿{{ number_format($displayAmount, 2) }}</span>
        </p>
        @endif
        <p class="text-blue-700 dark:text-blue-400">
            {{ $explanation['note'] ?? 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมจุดทศนิยม) เพื่อให้ระบบยืนยันอัตโนมัติ ไม่ต้องรอแอดมิน' }}
        </p>
    </div>
@else
    {{-- แบบเต็ม สำหรับแสดงในหน้าชำระเงินหลัก --}}
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-5 space-y-3">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200">
            💡 {{ $explanation['title'] ?? 'ทำไมยอดโอนมีจุดทศนิยม?' }}
        </h3>
        <p class="text-blue-800 dark:text-blue-300">
            {{ $explanation['message'] ?? 'ยอดเงินที่ท่านต้องโอนจะมีทศนิยม เพื่อให้ระบบตรวจสอบการชำระเงินได้อัตโนมัติและรวดเร็ว โดยไม่ต้องรอแอดมินยืนยัน' }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
            {{-- ตัวอย่าง: ระบบอัตโนมัติ --}}
            <div class="flex items-start space-x-3 bg-green-50 dark:bg-green-900/30 rounded-lg p-3">
                <span class="text-2xl">⚡</span>
                <div>
                    <p class="font-medium text-green-800 dark:text-green-300 text-sm">โอนยอดตรง (มีทศนิยม)</p>
                    <p class="text-green-700 dark:text-green-400 text-xs">
                        ระบบยืนยันอัตโนมัติ ไม่ต้องรอแอดมิน
                    </p>
                </div>
            </div>

            {{-- ตัวอย่าง: รอแอดมิน --}}
            <div class="flex items-start space-x-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-3">
                <span class="text-2xl">⏳</span>
                <div>
                    <p class="font-medium text-yellow-800 dark:text-yellow-300 text-sm">โอนยอดกลม (ไม่มีทศนิยม)</p>
                    <p class="text-yellow-700 dark:text-yellow-400 text-xs">
                        ต้องรอแอดมินตรวจสอบด้วยตนเอง
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-blue-100 dark:bg-blue-800/50 rounded-lg p-3 mt-2">
            <p class="text-sm text-blue-800 dark:text-blue-300">
                ⚠️ {{ $explanation['note'] ?? 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมจุดทศนิยม) หากโอนยอดไม่ตรง จะต้องรอแอดมินตรวจสอบด้วยตนเอง' }}
            </p>
        </div>
    </div>
@endif

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ปิดเซสชัน - POS Cashier</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4" x-data="closeSession()">
        <div class="w-full max-w-2xl">
            <!-- Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-sign-out-alt text-4xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold mb-2">ปิดเซสชันการขาย</h1>
                        <p class="text-red-100">{{ $session->posDevice->device_name ?? 'POS Terminal' }}</p>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('pos.session.close', $session) }}" class="p-6">
                    @csrf

                    <!-- Session Info -->
                    <div class="mb-6 grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รหัสเซสชัน</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->session_code }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">แคชเชียร์</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->user->name ?? auth()->user()->name }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">เปิดเซสชันเมื่อ</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->opened_at->locale('th')->isoFormat('DD MMM YYYY HH:mm') }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ระยะเวลา</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->opened_at->diffForHumans(null, true) }}</p>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-chart-line text-green-600"></i>
                            สรุปยอดขาย
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                                <p class="text-sm text-blue-600 dark:text-blue-400 mb-1">รายการทั้งหมด</p>
                                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($session->total_transactions) }}</p>
                            </div>
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                                <p class="text-sm text-green-600 dark:text-green-400 mb-1">ยอดขายรวม</p>
                                <p class="text-2xl font-bold text-green-700 dark:text-green-300">฿{{ number_format($session->total_sales, 2) }}</p>
                            </div>
                            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                                <p class="text-sm text-purple-600 dark:text-purple-400 mb-1">เงินสด</p>
                                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">฿{{ number_format($session->total_cash_sales, 2) }}</p>
                            </div>
                            <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-center">
                                <p class="text-sm text-orange-600 dark:text-orange-400 mb-1">บัตร/อื่นๆ</p>
                                <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">฿{{ number_format($session->total_card_sales + $session->total_other_sales, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Reconciliation -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-money-check-alt text-green-600"></i>
                            ตรวจนับเงินสด
                        </h3>

                        <div class="space-y-4">
                            <!-- Opening Cash -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-gray-700 dark:text-gray-300">เงินสดเริ่มต้น</span>
                                <span class="font-bold text-gray-900 dark:text-white text-lg">฿{{ number_format($session->opening_cash, 2) }}</span>
                            </div>

                            <!-- Cash Sales -->
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-green-700 dark:text-green-300">+ ยอดขายเงินสด</span>
                                <span class="font-bold text-green-700 dark:text-green-300 text-lg">฿{{ number_format($session->total_cash_sales, 2) }}</span>
                            </div>

                            @if($session->total_refund_amount > 0)
                            <!-- Refunds -->
                            <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <span class="text-red-700 dark:text-red-300">- เงินคืน/ยกเลิก</span>
                                <span class="font-bold text-red-700 dark:text-red-300 text-lg">฿{{ number_format($session->total_refund_amount, 2) }}</span>
                            </div>
                            @endif

                            <!-- Expected Cash -->
                            <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-300 dark:border-blue-700">
                                <span class="text-blue-700 dark:text-blue-300 font-medium">เงินสดที่ควรมี</span>
                                <span class="font-bold text-blue-700 dark:text-blue-300 text-2xl">฿{{ number_format($expectedCash, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Closing Cash Input -->
                    <div class="mb-6">
                        <label for="closing_cash" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-money-bill-wave text-green-600"></i>
                            จำนวนเงินสดที่นับได้จริง
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">฿</span>
                            <input type="number"
                                   name="closing_cash"
                                   id="closing_cash"
                                   step="0.01"
                                   min="0"
                                   required
                                   autofocus
                                   x-model.number="closingCash"
                                   @input="calculateDifference()"
                                   class="w-full pl-10 pr-4 py-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-red-500 focus:border-transparent text-2xl font-bold"
                                   placeholder="0.00">
                        </div>
                        @error('closing_cash')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <!-- Difference Display -->
                        <div x-show="closingCash !== null" class="mt-4">
                            <div class="p-4 rounded-lg"
                                 :class="difference === 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700' :
                                         difference > 0 ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700' :
                                         'bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700'">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium"
                                          :class="difference === 0 ? 'text-green-700 dark:text-green-300' :
                                                  difference > 0 ? 'text-blue-700 dark:text-blue-300' :
                                                  'text-red-700 dark:text-red-300'">
                                        <i class="fas" :class="difference === 0 ? 'fa-check-circle' :
                                                                difference > 0 ? 'fa-arrow-up' :
                                                                'fa-arrow-down'"></i>
                                        <span x-text="difference === 0 ? 'ถูกต้อง' : difference > 0 ? 'เงินเกิน' : 'เงินขาด'"></span>
                                    </span>
                                    <span class="text-2xl font-bold"
                                          :class="difference === 0 ? 'text-green-700 dark:text-green-300' :
                                                  difference > 0 ? 'text-blue-700 dark:text-blue-300' :
                                                  'text-red-700 dark:text-red-300'"
                                          x-text="'฿' + Math.abs(difference).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Closing Notes -->
                    <div class="mb-6">
                        <label for="closing_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-sticky-note text-green-600"></i>
                            หมายเหตุ (ถ้ามี)
                        </label>
                        <textarea name="closing_notes"
                                  id="closing_notes"
                                  rows="3"
                                  maxlength="500"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                  placeholder="บันทึกข้อมูลเพิ่มเติม เช่น สาเหตุของเงินเกิน/ขาด"></textarea>
                        @error('closing_notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Warning -->
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-500 mt-1"></i>
                            <div class="text-sm text-red-800 dark:text-red-300">
                                <p class="font-medium mb-1">คำเตือน:</p>
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    <li>กรุณานับเงินสดให้ถูกต้องก่อนปิดเซสชัน</li>
                                    <li>ข้อมูลจะถูกบันทึกและไม่สามารถแก้ไขได้</li>
                                    <li>หากมีเงินเกิน/ขาด กรุณาระบุเหตุผลในหมายเหตุ</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <a href="{{ route('pos.cashier', ['device_code' => request('device_code')]) }}"
                           class="flex-1 py-3 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg font-bold text-lg transition text-center">
                            <i class="fas fa-arrow-left"></i>
                            ยกเลิก
                        </a>
                        <button type="submit"
                                class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold text-lg transition shadow-lg hover:shadow-xl">
                            <i class="fas fa-lock"></i>
                            ปิดเซสชัน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'dark');
            }
        }

        function closeSession() {
            return {
                closingCash: null,
                expectedCash: {{ $expectedCash }},
                difference: 0,

                calculateDifference() {
                    if (this.closingCash !== null && this.closingCash !== '') {
                        this.difference = parseFloat(this.closingCash) - this.expectedCash;
                    } else {
                        this.difference = 0;
                    }
                }
            }
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>รายงานเซสชัน - {{ $session->session_code }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .print-shadow {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="min-h-screen p-4 md:p-8">
        <!-- Header (No Print) -->
        <div class="max-w-5xl mx-auto mb-6 no-print">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-file-alt text-green-600"></i>
                        รายงานเซสชันการขาย
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $session->session_code }}</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="window.print()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                        <i class="fas fa-print"></i>
                        พิมพ์
                    </button>
                    <button onclick="toggleDarkMode()"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                    <a href="{{ route('pos.cashier', ['device_code' => $session->posDevice->device_code]) }}"
                       class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                        <i class="fas fa-cash-register"></i>
                        กลับไปขาย
                    </a>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="max-w-5xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl print-shadow overflow-hidden">
                <!-- Report Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold mb-2">{{ $session->posDevice->store->name ?? 'ร้านค้า' }}</h2>
                        <p class="text-green-100">รายงานการขาย - {{ $session->posDevice->device_name }}</p>
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    <!-- Session Info -->
                    <div class="mb-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">รหัสเซสชัน</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->session_code }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">แคชเชียร์</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->user->name }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">เปิดเซสชัน</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->opened_at->locale('th')->isoFormat('DD MMM YY HH:mm') }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ปิดเซสชัน</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $session->closed_at ? $session->closed_at->locale('th')->isoFormat('DD MMM YY HH:mm') : '-' }}</p>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-chart-bar text-green-600"></i>
                            สรุปภาพรวม
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-6 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl text-white shadow-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-receipt text-2xl opacity-80"></i>
                                </div>
                                <p class="text-3xl font-bold mb-1">{{ number_format($session->total_transactions) }}</p>
                                <p class="text-blue-100 text-sm">รายการทั้งหมด</p>
                            </div>

                            <div class="p-6 bg-gradient-to-br from-green-500 to-green-600 rounded-xl text-white shadow-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-dollar-sign text-2xl opacity-80"></i>
                                </div>
                                <p class="text-3xl font-bold mb-1">฿{{ number_format($session->total_sales, 2) }}</p>
                                <p class="text-green-100 text-sm">ยอดขายรวม</p>
                            </div>

                            <div class="p-6 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl text-white shadow-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-money-bill-wave text-2xl opacity-80"></i>
                                </div>
                                <p class="text-3xl font-bold mb-1">฿{{ number_format($session->total_cash_sales, 2) }}</p>
                                <p class="text-purple-100 text-sm">เงินสด</p>
                            </div>

                            <div class="p-6 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl text-white shadow-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-credit-card text-2xl opacity-80"></i>
                                </div>
                                <p class="text-3xl font-bold mb-1">฿{{ number_format($session->total_card_sales + $session->total_other_sales, 2) }}</p>
                                <p class="text-orange-100 text-sm">บัตร/อื่นๆ</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Reconciliation -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-money-check-alt text-green-600"></i>
                            รายงานการตรวจนับเงินสด
                        </h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-700 dark:text-gray-300">เงินสดเริ่มต้น</span>
                                    <span class="font-bold text-gray-900 dark:text-white">฿{{ number_format($session->opening_cash, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-green-700 dark:text-green-300">+ ยอดขายเงินสด</span>
                                    <span class="font-bold text-green-700 dark:text-green-300">฿{{ number_format($session->total_cash_sales, 2) }}</span>
                                </div>

                                @if($session->total_refund_amount > 0)
                                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-red-700 dark:text-red-300">- เงินคืน/ยกเลิก</span>
                                    <span class="font-bold text-red-700 dark:text-red-300">฿{{ number_format($session->total_refund_amount, 2) }}</span>
                                </div>
                                @endif

                                <div class="flex items-center justify-between py-3 border-b-2 border-blue-300 dark:border-blue-700">
                                    <span class="font-medium text-blue-700 dark:text-blue-300">เงินสดที่ควรมี</span>
                                    <span class="font-bold text-blue-700 dark:text-blue-300 text-xl">฿{{ number_format($session->expected_cash ?? 0, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b-2 border-gray-300 dark:border-gray-600">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">เงินสดที่นับได้จริง</span>
                                    <span class="font-bold text-gray-900 dark:text-white text-xl">฿{{ number_format($session->closing_cash ?? 0, 2) }}</span>
                                </div>

                                @if($session->cash_difference != 0)
                                <div class="flex items-center justify-between py-3 rounded-lg px-4"
                                     style="background-color: {{ $session->cash_difference > 0 ? 'rgba(59, 130, 246, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}">
                                    <span class="font-bold" style="color: {{ $session->cash_difference > 0 ? '#3b82f6' : '#ef4444' }}">
                                        <i class="fas fa-{{ $session->cash_difference > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                        {{ $session->cash_difference > 0 ? 'เงินเกิน' : 'เงินขาด' }}
                                    </span>
                                    <span class="font-bold text-xl" style="color: {{ $session->cash_difference > 0 ? '#3b82f6' : '#ef4444' }}">
                                        ฿{{ number_format(abs($session->cash_difference), 2) }}
                                    </span>
                                </div>
                                @else
                                <div class="flex items-center justify-between py-3 bg-green-100 dark:bg-green-900/20 rounded-lg px-4">
                                    <span class="font-bold text-green-700 dark:text-green-300">
                                        <i class="fas fa-check-circle"></i>
                                        ถูกต้อง
                                    </span>
                                    <span class="font-bold text-green-700 dark:text-green-300 text-xl">฿0.00</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Breakdown -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-chart-pie text-green-600"></i>
                            แยกตามช่องทางการชำระเงิน
                        </h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-money-bill-wave text-xl text-purple-600 dark:text-purple-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">เงินสด</p>
                                            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($session->total_cash_sales, 2) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-credit-card text-xl text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">บัตร</p>
                                            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($session->total_card_sales, 2) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-wallet text-xl text-orange-600 dark:text-orange-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">อื่นๆ</p>
                                            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($session->total_other_sales, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction List -->
                    @if($session->transactions->count() > 0)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-list text-green-600"></i>
                            รายการขายทั้งหมด ({{ $session->transactions->count() }} รายการ)
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300">เลขที่</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300">เวลา</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300">ชำระผ่าน</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300">ยอดเงิน</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($session->transactions as $transaction)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->receipt_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $transaction->created_at->format('H:i:s') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                {{ $transaction->payment_method === 'cash' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : '' }}
                                                {{ $transaction->payment_method === 'card' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                                {{ !in_array($transaction->payment_method, ['cash', 'card']) ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' : '' }}">
                                                {{ ucfirst($transaction->payment_method) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-bold text-right text-gray-900 dark:text-white">฿{{ number_format($transaction->total_amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Notes -->
                    @if($session->opening_notes || $session->closing_notes)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-sticky-note text-green-600"></i>
                            หมายเหตุ
                        </h3>
                        <div class="space-y-3">
                            @if($session->opening_notes)
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-1">หมายเหตุเปิดเซสชัน:</p>
                                <p class="text-sm text-blue-800 dark:text-blue-200">{{ $session->opening_notes }}</p>
                            </div>
                            @endif

                            @if($session->closing_notes)
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <p class="text-sm font-medium text-red-900 dark:text-red-300 mb-1">หมายเหตุปิดเซสชัน:</p>
                                <p class="text-sm text-red-800 dark:text-red-200">{{ $session->closing_notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Footer -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
                        <p>พิมพ์เมื่อ: {{ now()->locale('th')->isoFormat('DD MMMM YYYY HH:mm') }} น.</p>
                        <p class="mt-1">ระบบ POS - {{ config('app.name') }}</p>
                    </div>
                </div>
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
    </script>
</body>
</html>

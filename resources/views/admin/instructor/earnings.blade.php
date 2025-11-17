@extends('layouts.admin-v3')

@section('title', 'รายได้ผู้สอน')

@section('content')
<div class="space-y-6">
    <!-- Hero Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-32 -mb-32"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-4xl font-bold mb-3 flex items-center animate-fade-in">
                        <i class="fas fa-wallet mr-4 text-5xl"></i>
                        รายได้ของฉัน
                    </h1>
                    <p class="text-blue-100 text-lg">ติดตามรายได้และการถอนเงิน</p>
                </div>
                <div class="text-right">
                    <p class="text-blue-100 text-sm mb-2">รายได้สะสมทั้งหมด</p>
                    <h2 class="text-5xl font-bold mb-2">฿{{ number_format($earnings['lifetime_earnings'], 2) }}</h2>
                    <p class="text-blue-100 text-sm">ตั้งแต่เริ่มสอน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Earnings Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Earnings -->
        <div class="group bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-money-bill-wave text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">รายได้รวม</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-coins text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">฿{{ number_format($earnings['total_earnings'], 2) }}</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-chart-line mr-2 text-green-200"></i>
                    <span class="opacity-90">ทั้งหมด</span>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="group bg-gradient-to-br from-blue-500 to-indigo-700 dark:from-blue-600 dark:to-indigo-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-calendar-alt text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">เดือนนี้</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-calendar-check text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">฿{{ number_format($earnings['this_month'], 2) }}</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-arrow-{{ $earnings['this_month'] >= $earnings['last_month'] ? 'up' : 'down' }} mr-2 text-blue-200"></i>
                    <span class="opacity-90">{{ abs((($earnings['this_month'] - $earnings['last_month']) / max($earnings['last_month'], 1)) * 100) }}% จากเดือนที่แล้ว</span>
                </div>
            </div>
        </div>

        <!-- Last Month -->
        <div class="group bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-history text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">เดือนที่แล้ว</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-calendar text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">฿{{ number_format($earnings['last_month'], 2) }}</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-clock mr-2 text-purple-200"></i>
                    <span class="opacity-90">เดือนก่อน</span>
                </div>
            </div>
        </div>

        <!-- Pending Payout -->
        <div class="group bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-hourglass-half text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">รอการถอน</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-hand-holding-usd text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">฿{{ number_format($earnings['pending_payout'], 2) }}</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-exclamation-circle mr-2 text-yellow-200"></i>
                    <span class="opacity-90">พร้อมถอน</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Earnings Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-chart-area text-white"></i>
                    </div>
                    รายได้รายเดือน
                </h2>
                <select class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option>6 เดือนที่แล้ว</option>
                    <option>12 เดือนที่แล้ว</option>
                    <option>ปีนี้</option>
                </select>
            </div>
            <canvas id="earningsChart" height="300"></canvas>
        </div>

        <!-- Withdrawal Action Card -->
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-800 rounded-2xl shadow-2xl p-6 text-white">
            <div class="text-center mb-6">
                <div class="bg-white/20 backdrop-blur-sm w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hand-holding-usd text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">ถอนเงิน</h3>
                <p class="text-emerald-100 text-sm">ยอดเงินพร้อมถอน</p>
            </div>

            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-6 mb-6">
                <p class="text-center text-5xl font-bold mb-2">฿{{ number_format($earnings['pending_payout'], 2) }}</p>
                <p class="text-center text-sm opacity-75">จากรายได้ที่ได้รับ</p>
            </div>

            <button onclick="showWithdrawModal()"
                    class="w-full bg-white hover:bg-gray-100 text-green-600 font-bold py-4 px-6 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center justify-center gap-2">
                <i class="fas fa-download"></i>
                <span>ขอถอนเงิน</span>
            </button>

            <p class="text-xs text-center text-emerald-100 mt-4">
                <i class="fas fa-info-circle mr-1"></i>
                ถอนขั้นต่ำ ฿1,000
            </p>
        </div>
    </div>

    <!-- Revenue Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue by Course -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                รายได้ตามคอร์ส
            </h2>

            <div class="space-y-4">
                @for($i = 1; $i <= 5; $i++)
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white">คอร์สตัวอย่างที่ {{ $i }}</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ rand(50, 200) }} นักเรียน</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">฿{{ number_format(rand(10000, 50000), 2) }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ rand(20, 40) }}%</p>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-500 to-pink-600 h-2 rounded-full" style="width: {{ rand(20, 40) }}%"></div>
                    </div>
                </div>
                @endfor
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-credit-card text-white"></i>
                </div>
                บัญชีรับเงิน
            </h2>

            <div class="space-y-4">
                <!-- Bank Account -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="bg-blue-500 p-3 rounded-xl">
                            <i class="fas fa-university text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-600 dark:text-gray-400">ธนาคารหลัก</p>
                            <p class="font-bold text-gray-900 dark:text-white text-lg">ธนาคารกรุงเทพ</p>
                        </div>
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">
                            <i class="fas fa-check-circle mr-1"></i>
                            ยืนยันแล้ว
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">ชื่อบัญชี</p>
                            <p class="font-medium text-gray-900 dark:text-white">คุณสมชาย ใจดี</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs">เลขที่บัญชี</p>
                            <p class="font-mono font-medium text-gray-900 dark:text-white">123-4-56789-0</p>
                        </div>
                    </div>
                </div>

                <!-- Add Account Button -->
                <button class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white py-4 px-6 rounded-xl font-semibold shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    <span>เพิ่มบัญชีธนาคาร</span>
                </button>

                <!-- Withdrawal Info -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                    <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        ข้อมูลการถอนเงิน
                    </h4>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                            <span>ถอนเงินขั้นต่ำ ฿1,000</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                            <span>ระยะเวลาโอนเงิน 3-5 วันทำการ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-600 dark:text-green-400 mt-1"></i>
                            <span>ไม่มีค่าธรรมเนียมการถอน</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-history text-white"></i>
                    </div>
                    ประวัติธุรกรรม
                </h2>

                <div class="flex gap-2">
                    <button class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                        ทั้งหมด
                    </button>
                    <button class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-medium hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                        รายได้
                    </button>
                    <button class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                        การถอน
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">รายละเอียด</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @for($i = 1; $i <= 10; $i++)
                    @php
                        $types = ['earning', 'withdrawal'];
                        $type = $types[array_rand($types)];
                        $statuses = ['completed', 'pending', 'processing'];
                        $status = $statuses[array_rand($statuses)];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ now()->subDays(rand(1, 30))->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $type === 'earning' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : '' }}
                                {{ $type === 'withdrawal' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : '' }}">
                                @if($type === 'earning')
                                    <i class="fas fa-arrow-down mr-1"></i> รายได้
                                @else
                                    <i class="fas fa-arrow-up mr-1"></i> ถอนเงิน
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                @if($type === 'earning')
                                    ขายคอร์ส: คอร์สตัวอย่าง {{ rand(1, 5) }}
                                @else
                                    โอนเข้าบัญชี ธนาคารกรุงเทพ
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @if($type === 'earning')
                                    จากนักเรียน 1 คน
                                @else
                                    xxx-xxx-{{ rand(1000, 9999) }}
                                @endif
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-lg font-bold {{ $type === 'earning' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400' }}">
                                {{ $type === 'earning' ? '+' : '-' }}฿{{ number_format(rand(500, 5000), 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $status === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : '' }}
                                {{ $status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300' : '' }}
                                {{ $status === 'processing' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : '' }}">
                                @if($status === 'completed')
                                    <i class="fas fa-check-circle mr-1"></i> สำเร็จ
                                @elseif($status === 'pending')
                                    <i class="fas fa-clock mr-1"></i> รอดำเนินการ
                                @else
                                    <i class="fas fa-spinner mr-1"></i> กำลังดำเนินการ
                                @endif
                            </span>
                        </td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-hand-holding-usd text-green-600 dark:text-green-400 mr-3"></i>
            ขอถอนเงิน
        </h3>

        <form id="withdrawForm">
            <div class="space-y-4">
                <!-- Available Balance -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-5 border border-green-200 dark:border-green-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดเงินพร้อมถอน</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($earnings['pending_payout'], 2) }}</p>
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนเงินที่ต้องการถอน *
                    </label>
                    <div class="relative">
                        <input type="number" id="withdrawAmount" step="0.01" min="1000" max="{{ $earnings['pending_payout'] }}"
                               class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl pl-10 pr-4 py-3 text-lg font-bold focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400"
                               placeholder="1000.00" required>
                        <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">฿</div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        ถอนขั้นต่ำ ฿1,000
                    </p>
                </div>

                <!-- Bank Account -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        บัญชีรับเงิน *
                    </label>
                    <select class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl" required>
                        <option>ธนาคารกรุงเทพ - 123-4-56789-0</option>
                    </select>
                </div>

                <!-- Note -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หมายเหตุ (ไม่บังคับ)
                    </label>
                    <textarea rows="3"
                              class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl"
                              placeholder="หมายเหตุเพิ่มเติม..."></textarea>
                </div>

                <!-- Terms -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" required class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-400">
                        <div class="flex-1">
                            <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">ฉันยืนยันข้อมูล</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                โปรดตรวจสอบข้อมูลให้ถูกต้อง การถอนเงินจะดำเนินการภายใน 3-5 วันทำการ
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="hideWithdrawModal()"
                        class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-300 transform hover:scale-105">
                    ยืนยันการถอน
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Earnings Chart
const earningsCtx = document.getElementById('earningsChart').getContext('2d');
new Chart(earningsCtx, {
    type: 'bar',
    data: {
        labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
        datasets: [{
            label: 'รายได้ (บาท)',
            data: [15000, 18500, 22300, 19800, 25600, {{ $earnings['this_month'] }}],
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '฿' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

function showWithdrawModal() {
    document.getElementById('withdrawModal').classList.remove('hidden');
}

function hideWithdrawModal() {
    document.getElementById('withdrawModal').classList.add('hidden');
}

document.getElementById('withdrawForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('คำขอถอนเงินของคุณได้ถูกส่งแล้ว!\n\nเราจะดำเนินการโอนเงินภายใน 3-5 วันทำการ');
    hideWithdrawModal();
});
</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>
@endsection

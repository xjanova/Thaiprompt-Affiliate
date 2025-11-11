<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>เปิดเซสชัน - POS Cashier</title>

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
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-cash-register text-4xl"></i>
                        </div>
                        <h1 class="text-2xl font-bold mb-2">เปิดเซสชันการขาย</h1>
                        <p class="text-green-100">{{ $device->device_name }}</p>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('pos.session.open', ['device_code' => request('device_code')]) }}" class="p-6">
                    @csrf

                    <!-- Device Info -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-store text-2xl text-green-600 mt-1"></i>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $device->store->name ?? 'ร้านค้า' }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-user text-green-600"></i>
                                    แคชเชียร์: {{ auth()->user()->name }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-clock text-green-600"></i>
                                    {{ now()->locale('th')->isoFormat('DD MMMM YYYY HH:mm') }} น.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Opening Cash -->
                    <div class="mb-6">
                        <label for="opening_cash" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-money-bill-wave text-green-600"></i>
                            จำนวนเงินสดเริ่มต้น
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">฿</span>
                            <input type="number"
                                   name="opening_cash"
                                   id="opening_cash"
                                   step="0.01"
                                   min="0"
                                   required
                                   autofocus
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg font-medium"
                                   placeholder="0.00">
                        </div>
                        @error('opening_cash')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle"></i>
                            กรุณานับเงินสดในลิ้นชักและกรอกจำนวนที่นับได้
                        </p>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินแนะนำ:</p>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button"
                                    onclick="document.getElementById('opening_cash').value = '1000'"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-green-100 dark:hover:bg-green-900 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">
                                ฿1,000
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('opening_cash').value = '2000'"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-green-100 dark:hover:bg-green-900 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">
                                ฿2,000
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('opening_cash').value = '5000'"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-green-100 dark:hover:bg-green-900 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">
                                ฿5,000
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('opening_cash').value = '10000'"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-green-100 dark:hover:bg-green-900 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">
                                ฿10,000
                            </button>
                        </div>
                    </div>

                    <!-- Opening Notes -->
                    <div class="mb-6">
                        <label for="opening_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-sticky-note text-green-600"></i>
                            หมายเหตุ (ถ้ามี)
                        </label>
                        <textarea name="opening_notes"
                                  id="opening_notes"
                                  rows="3"
                                  maxlength="500"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
                                  placeholder="บันทึกข้อมูลเพิ่มเติม (ถ้ามี)"></textarea>
                        @error('opening_notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Warning -->
                    <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-500 mt-1"></i>
                            <div class="text-sm text-yellow-800 dark:text-yellow-300">
                                <p class="font-medium mb-1">ข้อควรระวัง:</p>
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    <li>กรุณานับเงินสดให้ถูกต้องก่อนกรอกจำนวน</li>
                                    <li>ข้อมูลจะถูกบันทึกและไม่สามารถแก้ไขได้</li>
                                    <li>คุณจะต้องปิดเซสชันเมื่อเลิกใช้งาน</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold text-lg transition shadow-lg hover:shadow-xl">
                            <i class="fas fa-play-circle"></i>
                            เปิดเซสชัน
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-question-circle"></i>
                    ต้องการความช่วยเหลือ? ติดต่อผู้ดูแลระบบ
                </p>
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

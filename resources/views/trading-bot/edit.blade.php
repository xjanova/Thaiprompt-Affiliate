<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Bot') }}: {{ $bot->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('trading-bot.update', $bot) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Bot Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อบอท <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $bot->name) }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Read-only Info -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">ℹ️ ข้อมูลที่ไม่สามารถแก้ไขได้</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">คู่เทรด:</span>
                                <span class="font-medium ml-2">{{ $bot->trading_pair }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Timeframe:</span>
                                <span class="font-medium ml-2">{{ $bot->timeframe }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">กลยุทธ์:</span>
                                <span class="font-medium ml-2">{{ $bot->strategy->name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">บัญชีเทรด:</span>
                                <span class="font-medium ml-2">{{ $bot->account->account_name }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            * หากต้องการเปลี่ยนข้อมูลเหล่านี้ กรุณาสร้างบอทใหม่
                        </p>
                    </div>

                    <!-- Allocated Capital -->
                    <div>
                        <label for="allocated_capital" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เงินทุนจัดสรร (฿) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="allocated_capital"
                               name="allocated_capital"
                               value="{{ old('allocated_capital', $bot->allocated_capital) }}"
                               step="0.01"
                               min="10"
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            เงินทุนปัจจุบัน: ฿{{ number_format($bot->allocated_capital, 2) }} |
                            คงเหลือ: ฿{{ number_format($bot->available_capital, 2) }}
                        </p>
                    </div>

                    <!-- Position Size Percentage -->
                    <div>
                        <label for="position_size_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ขนาดของออเดอร์ (% ของเงินทุนคงเหลือ) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="position_size_percentage"
                               name="position_size_percentage"
                               value="{{ old('position_size_percentage', $bot->position_size_percentage) }}"
                               step="0.1"
                               min="1"
                               max="100"
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            ปัจจุบัน: {{ $bot->position_size_percentage }}% |
                            แนะนำ: 5-20% สำหรับความเสี่ยงปานกลาง
                        </p>
                    </div>

                    <!-- Dry Run Mode -->
                    <div>
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox"
                                   name="dry_run"
                                   value="1"
                                   {{ old('dry_run', $bot->dry_run) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                โหมดทดสอบ (Paper Trading)
                            </span>
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-8">
                            เปิดโหมดนี้เพื่อทดสอบกลยุทธ์โดยไม่ใช้เงินจริง
                        </p>
                    </div>

                    <!-- Performance Stats (Read-only) -->
                    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">📊 สถิติปัจจุบัน</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">จำนวนเทรด</p>
                                <p class="font-bold text-lg">{{ $bot->total_trades ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Win Rate</p>
                                <p class="font-bold text-lg">{{ number_format($bot->win_rate ?? 0, 1) }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">กำไร/ขาดทุน</p>
                                <p class="font-bold text-lg {{ $bot->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ฿{{ number_format($bot->net_profit, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">ROI</p>
                                <p class="font-bold text-lg {{ $bot->roi_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($bot->roi_percentage ?? 0, 2) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">⚠️</span>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    คำเตือน
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>การแก้ไขเงินทุนจัดสรรอาจส่งผลต่อการทำงานของบอท</li>
                                        <li>การเปลี่ยนขนาดออเดอร์จะมีผลกับออเดอร์ใหม่เท่านั้น</li>
                                        <li>ไม่สามารถแก้ไขบอทที่กำลังทำงานอยู่ กรุณาหยุดบอทก่อน</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('trading-bot.show', $bot) }}"
                           class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            ← ย้อนกลับ
                        </a>
                        <div class="flex gap-3">
                            <button type="reset"
                                    class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                รีเซ็ต
                            </button>
                            <button type="submit"
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                                💾 บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

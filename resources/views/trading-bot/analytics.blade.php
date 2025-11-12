<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Bot Analytics') }}: {{ $bot->name }}
            </h2>
            <a href="{{ route('trading-bot.show', $bot) }}" class="text-gray-600 dark:text-gray-400 hover:underline">
                ← กลับหน้ารายละเอียด
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Performance Overview -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl shadow-xl p-6 text-white">
                <h3 class="text-2xl font-bold mb-6">📊 ภาพรวมผลการดำเนินงาน</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <p class="text-sm opacity-90 mb-1">กำไร/ขาดทุนรวม</p>
                        <p class="text-3xl font-bold">฿{{ number_format($performanceData['total_profit_loss'] ?? 0, 2) }}</p>
                        <p class="text-sm mt-1">ROI: {{ number_format($performanceData['roi_percentage'] ?? 0, 2) }}%</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <p class="text-sm opacity-90 mb-1">จำนวนเทรดทั้งหมด</p>
                        <p class="text-3xl font-bold">{{ $performanceData['total_trades'] ?? 0 }}</p>
                        <p class="text-sm mt-1">เทรดต่อวัน: {{ number_format($performanceData['trades_per_day'] ?? 0, 1) }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <p class="text-sm opacity-90 mb-1">อัตราชนะ (Win Rate)</p>
                        <p class="text-3xl font-bold">{{ number_format($performanceData['win_rate'] ?? 0, 1) }}%</p>
                        <p class="text-sm mt-1">ชนะ: {{ $performanceData['winning_trades'] ?? 0 }} / แพ้: {{ $performanceData['losing_trades'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <p class="text-sm opacity-90 mb-1">กำไรเฉลี่ยต่อเทรด</p>
                        <p class="text-3xl font-bold">฿{{ number_format($performanceData['average_profit_per_trade'] ?? 0, 2) }}</p>
                        <p class="text-sm mt-1">{{ $performanceData['total_trades'] > 0 ? 'ต่อครั้ง' : 'ยังไม่มีข้อมูล' }}</p>
                    </div>
                </div>
            </div>

            <!-- Advanced Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Risk Metrics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚠️ ตัวชี้วัดความเสี่ยง</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-medium">Sharpe Ratio</span>
                            <span class="font-bold {{ ($performanceData['sharpe_ratio'] ?? 0) > 1 ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ number_format($performanceData['sharpe_ratio'] ?? 0, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-medium">Sortino Ratio</span>
                            <span class="font-bold">{{ number_format($performanceData['sortino_ratio'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-medium">Max Drawdown</span>
                            <span class="font-bold text-red-600">{{ number_format($performanceData['max_drawdown_percentage'] ?? 0, 2) }}%</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-medium">Value at Risk (95%)</span>
                            <span class="font-bold">฿{{ number_format($performanceData['value_at_risk'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-medium">Profit Factor</span>
                            <span class="font-bold {{ ($performanceData['profit_factor'] ?? 0) > 1.5 ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ number_format($performanceData['profit_factor'] ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900 rounded-lg text-sm">
                        <p class="font-medium mb-1">💡 คำแนะนำ:</p>
                        <ul class="text-xs space-y-1 text-gray-700 dark:text-gray-300">
                            <li>• Sharpe Ratio > 1: ดี, > 2: ดีมาก</li>
                            <li>• Profit Factor > 1.5: แนวโน้มกำไรดี</li>
                            <li>• Max Drawdown < 20%: ระดับความเสี่ยงยอมรับได้</li>
                        </ul>
                    </div>
                </div>

                <!-- Trade Distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📈 การกระจายตัวของเทรด</h3>
                    <div class="space-y-4">
                        <!-- Win/Loss Distribution -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span>เทรดที่ชนะ ({{ $performanceData['winning_trades'] ?? 0 }})</span>
                                <span class="text-green-600 font-semibold">{{ number_format($performanceData['win_rate'] ?? 0, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-green-500 h-3 rounded-full transition-all duration-500"
                                     style="width: {{ $performanceData['win_rate'] ?? 0 }}%"></div>
                            </div>
                        </div>

                        <!-- Average Win -->
                        <div class="p-3 bg-green-50 dark:bg-green-900 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">กำไรเฉลี่ยต่อเทรดที่ชนะ</p>
                            <p class="text-2xl font-bold text-green-600">฿{{ number_format($performanceData['average_win'] ?? 0, 2) }}</p>
                        </div>

                        <!-- Average Loss -->
                        <div class="p-3 bg-red-50 dark:bg-red-900 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">ขาดทุนเฉลี่ยต่อเทรดที่แพ้</p>
                            <p class="text-2xl font-bold text-red-600">฿{{ number_format($performanceData['average_loss'] ?? 0, 2) }}</p>
                        </div>

                        <!-- Best/Worst Trade -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">เทรดที่ดีที่สุด</p>
                                <p class="text-lg font-bold text-green-600">฿{{ number_format($performanceData['best_trade'] ?? 0, 2) }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">เทรดที่แย่ที่สุด</p>
                                <p class="text-lg font-bold text-red-600">฿{{ number_format($performanceData['worst_trade'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time-based Analytics -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⏰ วิเคราะห์ตามเวลา</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">ระยะเวลาการทำงาน</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">เริ่มต้น:</span> {{ $bot->created_at->format('d/m/Y H:i') }}</p>
                            <p><span class="font-medium">ทำงานมาแล้ว:</span> {{ $bot->created_at->diffForHumans(null, true) }}</p>
                            <p><span class="font-medium">เทรดล่าสุด:</span>
                                @if($performanceData['last_trade_at'])
                                    {{ \Carbon\Carbon::parse($performanceData['last_trade_at'])->format('d/m/Y H:i') }}
                                @else
                                    ยังไม่มีเทรด
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">ประสิทธิภาพ</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">เทรดต่อวัน:</span> {{ number_format($performanceData['trades_per_day'] ?? 0, 1) }}</p>
                            <p><span class="font-medium">กำไรต่อวัน:</span>
                                <span class="{{ ($performanceData['profit_per_day'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ฿{{ number_format($performanceData['profit_per_day'] ?? 0, 2) }}
                                </span>
                            </p>
                            <p><span class="font-medium">อัตราการทำงาน:</span> {{ $bot->status === 'running' ? '🟢 กำลังทำงาน' : '🔴 หยุดชั่วคราว' }}</p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">เป้าหมาย</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">ROI เป้าหมาย:</span>
                                <span class="{{ ($performanceData['roi_percentage'] ?? 0) >= 10 ? 'text-green-600' : 'text-yellow-600' }}">
                                    10% ({{ number_format($performanceData['roi_percentage'] ?? 0, 2) }}%)
                                </span>
                            </p>
                            <p><span class="font-medium">Win Rate เป้าหมาย:</span>
                                <span class="{{ ($performanceData['win_rate'] ?? 0) >= 60 ? 'text-green-600' : 'text-yellow-600' }}">
                                    60% ({{ number_format($performanceData['win_rate'] ?? 0, 1) }}%)
                                </span>
                            </p>
                            <p><span class="font-medium">ความก้าวหน้า:</span>
                                <span class="font-semibold">{{ $performanceData['progress_percentage'] ?? 0 }}%</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Strategy Performance -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 ประสิทธิภาพของกลยุทธ์</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">ข้อมูลกลยุทธ์</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">ชื่อ:</span> {{ $bot->strategy->name }}</p>
                            <p><span class="font-medium">ประเภท:</span>
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                    {{ $bot->strategy->strategy_type }}
                                </span>
                            </p>
                            <p><span class="font-medium">Stop Loss:</span> {{ $bot->strategy->stop_loss_percentage }}%</p>
                            <p><span class="font-medium">Take Profit:</span> {{ $bot->strategy->take_profit_percentage }}%</p>
                            @if($bot->strategy->use_ai)
                                <p><span class="font-medium">AI Model:</span>
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs">
                                        {{ $bot->strategy->ai_model }}
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">ผลการดำเนินงาน</h4>
                        <div class="space-y-3">
                            <div class="p-3 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900 dark:to-blue-900 rounded-lg">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">จำนวนสัญญาณที่สร้าง</p>
                                <p class="text-2xl font-bold">{{ $performanceData['signals_generated'] ?? 0 }}</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900 dark:to-purple-900 rounded-lg">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">อัตราการดำเนินการสัญญาณ</p>
                                <p class="text-2xl font-bold">{{ number_format($performanceData['signal_execution_rate'] ?? 0, 1) }}%</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900 dark:to-pink-900 rounded-lg">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ความแม่นยำของสัญญาณ</p>
                                <p class="text-2xl font-bold">{{ number_format($performanceData['signal_accuracy'] ?? 0, 1) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Trade Chart Placeholder -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📉 กราฟผลกำไร/ขาดทุนสะสม</h3>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400 mb-2">📊 Chart Visualization</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        ใช้ Chart.js หรือ ApexCharts เพื่อแสดงกราฟประสิทธิภาพ
                    </p>
                    <div class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                        Data points: {{ count($performanceData['chart_data'] ?? []) }}
                    </div>
                </div>
            </div>

            <!-- Export & Actions -->
            <div class="flex justify-between items-center">
                <a href="{{ route('trading-bot.show', $bot) }}"
                   class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    ← กลับหน้ารายละเอียด
                </a>
                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                        📥 Export CSV
                    </button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        📄 Export PDF Report
                    </button>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

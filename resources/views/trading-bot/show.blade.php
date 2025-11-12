<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Bot Details') }}: {{ $bot->name }}
            </h2>
            <div class="flex gap-2">
                @if($bot->status === 'stopped')
                    <form action="{{ route('trading-bot.start', $bot) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                            ▶ เริ่มบอท
                        </button>
                    </form>
                @elseif($bot->status === 'running')
                    <form action="{{ route('trading-bot.stop', $bot) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                            ⏸ หยุดบอท
                        </button>
                    </form>
                @endif
                <a href="{{ route('trading-bot.edit', $bot) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    ✏️ แก้ไข
                </a>
                <a href="{{ route('trading-bot.analytics', $bot) }}" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg">
                    📊 Analytics
                </a>
                <a href="{{ route('trading-bot.advanced-config', $bot) }}" class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white px-4 py-2 rounded-lg">
                    📈 Advanced Config
                </a>
                <a href="{{ route('trading-bot.pro-analytics', $bot) }}" class="bg-gradient-to-r from-pink-500 to-purple-500 hover:from-pink-600 hover:to-purple-600 text-white px-4 py-2 rounded-lg">
                    💎 Pro Analytics
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Bot Status Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $bot->name }}</h3>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                        @if($bot->status === 'running') bg-green-100 text-green-800
                        @elseif($bot->status === 'stopped') bg-gray-100 text-gray-800
                        @elseif($bot->status === 'paused') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ strtoupper($bot->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Configuration -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-lg">⚙️ การตั้งค่า</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">คู่เทรด:</span> {{ $bot->trading_pair }}</p>
                            <p><span class="font-medium">Timeframe:</span> {{ $bot->timeframe }}</p>
                            <p><span class="font-medium">กลยุทธ์:</span> {{ $bot->strategy->name }}</p>
                            <p><span class="font-medium">Exchange:</span> {{ $bot->account->exchange->name }}</p>
                            <p><span class="font-medium">บัญชี:</span> {{ $bot->account->account_name }}</p>
                            <p><span class="font-medium">โหมด:</span>
                                <span class="px-2 py-1 rounded {{ $bot->dry_run ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $bot->dry_run ? 'Paper Trading' : 'Live Trading' }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Capital -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-lg">💰 เงินทุน</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">เงินทุนจัดสรร:</span>
                                ฿{{ number_format($bot->allocated_capital, 2) }}
                            </p>
                            <p><span class="font-medium">เงินทุนคงเหลือ:</span>
                                ฿{{ number_format($bot->available_capital, 2) }}
                            </p>
                            <p><span class="font-medium">ใช้ไปแล้ว:</span>
                                ฿{{ number_format($bot->allocated_capital - $bot->available_capital, 2) }}
                            </p>
                            <p><span class="font-medium">ขนาดออเดอร์:</span>
                                {{ $bot->position_size_percentage }}% ของทุนคงเหลือ
                            </p>
                        </div>
                    </div>

                    <!-- Performance -->
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-lg">📈 ผลงาน</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">กำไร/ขาดทุน:</span>
                                <span class="{{ $bot->total_profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                                    ฿{{ number_format($bot->total_profit_loss, 2) }}
                                </span>
                            </p>
                            <p><span class="font-medium">ROI:</span>
                                <span class="{{ ($performanceMetrics['roi_percentage'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                                    {{ number_format($performanceMetrics['roi_percentage'] ?? 0, 2) }}%
                                </span>
                            </p>
                            <p><span class="font-medium">จำนวนเทรด:</span> {{ $performanceMetrics['total_trades'] ?? 0 }}</p>
                            <p><span class="font-medium">Win Rate:</span>
                                <span class="font-semibold">{{ number_format($performanceMetrics['win_rate'] ?? 0, 1) }}%</span>
                            </p>
                            <p><span class="font-medium">เริ่มเมื่อ:</span> {{ $bot->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Arbitrage Status (if applicable) -->
            @if($arbitrageStatus)
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900 dark:to-pink-900 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🔄 Arbitrage Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p><span class="font-medium">โอกาสที่พบ:</span> {{ $arbitrageStatus['opportunities_found'] ?? 0 }}</p>
                        <p><span class="font-medium">โอกาสที่ดำเนินการ:</span> {{ $arbitrageStatus['opportunities_executed'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p><span class="font-medium">กำไรจาก Arbitrage:</span>
                            <span class="text-green-600 font-bold">฿{{ number_format($arbitrageStatus['total_arbitrage_profit'] ?? 0, 2) }}</span>
                        </p>
                        <p><span class="font-medium">ตรวจสอบล่าสุด:</span> {{ $arbitrageStatus['last_check'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Signals -->
            @if($signals->count() > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 สัญญาณล่าสุด</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สัญญาณ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคา</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Confidence</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($signals as $signal)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $signal->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold
                                        {{ $signal->signal_type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ strtoupper($signal->signal_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">฿{{ number_format($signal->price ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format(($signal->confidence ?? 0) * 100, 1) }}%</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold
                                        {{ $signal->executed ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $signal->executed ? 'Executed' : 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Recent Trades -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 ประวัติการเทรด</h3>
                @if($trades->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คู่เทรด</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคา</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">กำไร/ขาดทุน</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($trades as $trade)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $trade->executed_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold
                                        {{ $trade->side === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ strtoupper($trade->side) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $trade->symbol }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($trade->quantity, 8) }}</td>
                                <td class="px-4 py-3 text-sm">฿{{ number_format($trade->price, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if($trade->profit_loss !== null)
                                        <span class="{{ $trade->profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                            ฿{{ number_format($trade->profit_loss, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold
                                        @if($trade->status === 'filled') bg-green-100 text-green-800
                                        @elseif($trade->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($trade->status === 'canceled') bg-gray-100 text-gray-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ strtoupper($trade->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $trades->links() }}
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">ยังไม่มีประวัติการเทรด</p>
                @endif
            </div>

            <!-- Danger Zone -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 border-2 border-red-200">
                <h3 class="text-xl font-bold text-red-600 mb-4">⚠️ Danger Zone</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    การลบบอทจะลบข้อมูลทั้งหมดรวมถึงประวัติการเทรดอย่างถาวร คุณไม่สามารถกู้คืนได้
                </p>
                <form action="{{ route('trading-bot.destroy', $bot) }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบอทนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-semibold"
                            @if($bot->status === 'running') disabled title="หยุดบอทก่อนลบ" @endif>
                        🗑️ ลบบอทนี้
                    </button>
                    @if($bot->status === 'running')
                        <span class="ml-3 text-sm text-gray-500">กรุณาหยุดบอทก่อนลบ</span>
                    @endif
                </form>
            </div>

        </div>
    </div>
</x-app-layout>

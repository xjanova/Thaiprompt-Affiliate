<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Admin: Bot Details - {{ $bot->name }}
            </h2>
            <a href="{{ route('admin.trading-bot.bots.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Bot Info & Controls -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Bot Information</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Owner:</span> {{ $bot->user->name }}</p>
                            <p><span class="font-medium">Email:</span> {{ $bot->user->email }}</p>
                            <p><span class="font-medium">Trading Pair:</span> {{ $bot->trading_pair }}</p>
                            <p><span class="font-medium">Strategy:</span> {{ $bot->strategy->name }}</p>
                            <p><span class="font-medium">Exchange:</span> {{ $bot->account->exchange->name }}</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Performance</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Total P&L:</span>
                                <span class="{{ $bot->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-bold">
                                    ฿{{ number_format($bot->net_profit, 2) }}
                                </span>
                            </p>
                            <p><span class="font-medium">ROI:</span> {{ number_format($performanceMetrics['roi_percentage'] ?? 0, 2) }}%</p>
                            <p><span class="font-medium">Total Trades:</span> {{ $performanceMetrics['total_trades'] ?? 0 }}</p>
                            <p><span class="font-medium">Win Rate:</span> {{ number_format($performanceMetrics['win_rate'] ?? 0, 1) }}%</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Admin Actions</h3>
                        <div class="space-y-2">
                            @if($bot->status === 'running')
                                <form action="{{ route('admin.trading-bot.bots.stop', $bot) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                                        ⏸ Force Stop
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.trading-bot.bots.start', $bot) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                        ▶ Force Start
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.trading-bot.bots.destroy', $bot) }}" method="POST" onsubmit="return confirm('Delete this bot?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                                    🗑️ Delete Bot
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Trades -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <h3 class="text-xl font-bold mb-4">Recent Trades (Last 20)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Side</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">P&L</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($bot->trades as $trade)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $trade->executed_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded {{ $trade->side === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ strtoupper($trade->side) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm">฿{{ number_format($trade->price, 2) }}</td>
                                <td class="px-4 py-2 text-sm">{{ number_format($trade->quantity, 8) }}</td>
                                <td class="px-4 py-2">
                                    @if($trade->net_profit !== null)
                                        <span class="{{ $trade->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                            ฿{{ number_format($trade->net_profit, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded
                                        @if($trade->status === 'filled') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ strtoupper($trade->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

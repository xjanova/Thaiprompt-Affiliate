<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin: Arbitrage Opportunities Monitor
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Real-time Monitor -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl shadow-xl p-6 text-white">
                <h3 class="text-2xl font-bold mb-2">🔄 Real-time Arbitrage Monitor</h3>
                <p class="text-sm opacity-90">Scanning {{ count($exchanges) }} exchanges for profitable opportunities</p>
            </div>

            <!-- Active Opportunities -->
            @if(count($opportunities) > 0)
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ Active Opportunities</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buy From</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sell To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buy Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sell Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit %</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($opportunities as $opp)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 font-bold">{{ $opp['symbol'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $opp['buy_exchange'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $opp['sell_exchange'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-green-600">฿{{ number_format($opp['buy_price'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-red-600">฿{{ number_format($opp['sell_price'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm font-bold">
                                    +{{ number_format($opp['profit_percentage'] ?? 0, 2) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-green-600">
                                ฿{{ number_format($opp['net_profit'] ?? 0, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-12 text-center">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Opportunities Found</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Currently monitoring markets. Profitable opportunities will appear here.
                </p>
            </div>
            @endif

            <!-- Exchange Status -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 Exchange Status</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($exchanges as $exchange)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-center">
                        <div class="font-bold text-lg">{{ $exchange->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span class="inline-block w-2 h-2 rounded-full {{ $exchange->is_active ? 'bg-green-500' : 'bg-gray-500' }} mr-1"></span>
                            {{ $exchange->is_active ? 'Online' : 'Offline' }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

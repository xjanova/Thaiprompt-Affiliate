<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white dark:text-gray-200 leading-tight">
            Admin: All Trading Bots
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="glass-fusion dark:bg-gray-800 shadow-sm rounded-xl p-4 mb-6" border border-white/20 dark:border-white/10>
                <form method="GET" class="flex gap-4">
                    <select name="status" class="px-4 py-2 border rounded-xl dark:bg-gray-700">
                        <option value="">All Status</option>
                        <option value="running">Running</option>
                        <option value="stopped">Stopped</option>
                        <option value="error">Error</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl">Filter</button>
                </form>
            </div>

            <!-- Table -->
            <div class="glass-fusion dark:bg-gray-800 shadow-xl sm:rounded-xl overflow-hidden" border border-white/20 dark:border-white/10>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bot Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pair</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">P&L</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($bots as $bot)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ $bot->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $bot->strategy->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div>{{ $bot->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $bot->account->exchange->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $bot->trading_pair }}</td>
                            <td class="px-6 py-4">
                                <span class="{{ $bot->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                    ฿{{ number_format($bot->net_profit, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($bot->status === 'running') bg-green-100 text-green-800
                                    @elseif($bot->status === 'stopped') bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ strtoupper($bot->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <a href="{{ route('admin.trading-bot.bots.show', $bot) }}" class="text-blue-600 hover:underline">View</a>
                                @if($bot->status === 'running')
                                    <form action="{{ route('admin.trading-bot.bots.stop', $bot) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline">Stop</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-6 py-4">
                    {{ $bots->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

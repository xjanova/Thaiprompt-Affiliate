<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Admin: Subscription Details
            </h2>
            <a href="{{ route('admin.trading-bot.subscriptions.index') }}" class="text-gray-600 hover:underline">← Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Subscription Info -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">User Information</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Name:</span> {{ $subscription->user->name }}</p>
                            <p><span class="font-medium">Email:</span> {{ $subscription->user->email }}</p>
                            <p><span class="font-medium">Phone:</span> {{ $subscription->user->phone ?? 'N/A' }}</p>
                            <a href="#" class="text-blue-600 hover:underline text-sm">View User Profile</a>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Package Details</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Package:</span> {{ $subscription->package->name }}</p>
                            <p><span class="font-medium">Price:</span> ฿{{ number_format($subscription->package->price, 0) }}</p>
                            <p><span class="font-medium">Billing:</span> {{ ucfirst($subscription->package->billing_cycle) }}</p>
                            <p><span class="font-medium">Max Bots:</span> {{ $subscription->package->max_bots }}</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Subscription Status</h3>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-medium">Status:</span>
                                <span class="px-2 py-1 text-xs rounded-full {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ strtoupper($subscription->status) }}
                                </span>
                            </p>
                            <p><span class="font-medium">Started:</span> {{ $subscription->started_at->format('d/m/Y H:i') }}</p>
                            <p><span class="font-medium">Expires:</span> {{ $subscription->expires_at?->format('d/m/Y H:i') ?? 'Lifetime' }}</p>
                            @if($subscription->canceled_at)
                                <p><span class="font-medium">Canceled:</span> {{ $subscription->canceled_at->format('d/m/Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                @if($subscription->status === 'active')
                <div class="mt-6 pt-6 border-t">
                    <form action="{{ route('admin.trading-bot.subscriptions.cancel', $subscription) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to cancel this subscription? This will stop all associated bots.')">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg">
                            Cancel Subscription
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <!-- Associated Bots -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Associated Bots ({{ $subscription->bots->count() }})</h3>
                @if($subscription->bots->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bot Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trading Pair</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Strategy</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">P&L</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($subscription->bots as $bot)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">{{ $bot->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $bot->trading_pair }}</td>
                                <td class="px-4 py-3 text-sm">{{ $bot->strategy->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $bot->total_profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                        ฿{{ number_format($bot->total_profit_loss, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        {{ $bot->status === 'running' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ strtoupper($bot->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('admin.trading-bot.bots.show', $bot) }}" class="text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-gray-500 text-center py-8">No bots created yet</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>

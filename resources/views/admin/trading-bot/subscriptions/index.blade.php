<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white dark:text-gray-200 leading-tight">
            Admin: Subscriptions Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="glass-fusion dark:bg-gray-800 shadow-sm rounded-xl p-4 mb-6" border border-white/20 dark:border-white/10>
                <form method="GET" class="flex gap-4">
                    <select name="status" class="px-4 py-2 border rounded-xl dark:bg-gray-700">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="canceled">Canceled</option>
                    </select>
                    <select name="package_id" class="px-4 py-2 border rounded-xl dark:bg-gray-700">
                        <option value="">All Packages</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl">Filter</button>
                </form>
            </div>

            <!-- Table -->
            <div class="glass-fusion dark:bg-gray-800 shadow-xl sm:rounded-xl overflow-hidden" border border-white/20 dark:border-white/10>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Package</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Started</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($subscriptions as $subscription)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ $subscription->user->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->user->email }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $subscription->package->name }}</td>
                            <td class="px-6 py-4 text-sm">{{ $subscription->started_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $subscription->expires_at?->format('d/m/Y') ?? 'Lifetime' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($subscription->status === 'active') bg-green-100 text-green-800
                                    @else bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white
                                    @endif">
                                    {{ strtoupper($subscription->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('admin.trading-bot.subscriptions.show', $subscription) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-6 py-4">
                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

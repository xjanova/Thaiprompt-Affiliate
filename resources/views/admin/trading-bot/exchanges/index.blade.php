<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white dark:text-gray-200 leading-tight">
            Admin: Exchanges Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-fusion dark:bg-gray-800 shadow-xl sm:rounded-xl overflow-hidden" border border-white/20 dark:border-white/10>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Exchange</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Trading Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Accounts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($exchanges as $exchange)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($exchange->logo_url)
                                        <img src="{{ $exchange->logo_url }}" alt="{{ $exchange->name }}" class="w-8 h-8 rounded-full mr-3">
                                    @endif
                                    <div>
                                        <div class="font-medium">{{ $exchange->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $exchange->website_url }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono">{{ $exchange->code }}</td>
                            <td class="px-6 py-4 text-sm">{{ $exchange->trading_fee_percentage }}%</td>
                            <td class="px-6 py-4 text-sm">{{ $exchange->accounts_count }} accounts</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $exchange->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white' }}">
                                    {{ $exchange->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button class="text-blue-600 hover:underline">Edit</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

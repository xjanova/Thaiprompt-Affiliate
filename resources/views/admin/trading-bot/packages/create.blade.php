<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin: Create Package
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.trading-bot.packages.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Package Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug *</label>
                            <input type="text" name="slug" value="{{ old('slug') }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description *</label>
                        <textarea name="description" rows="3" required
                                  class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price (฿) *</label>
                            <input type="number" name="price" step="0.01" min="0" value="{{ old('price', 0) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Billing Cycle *</label>
                            <select name="billing_cycle" required
                                    class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="lifetime">Lifetime</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Bots *</label>
                            <input type="number" name="max_bots" min="1" value="{{ old('max_bots', 1) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Exchanges *</label>
                            <input type="number" name="max_exchanges" min="1" value="{{ old('max_exchanges', 1) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Strategies *</label>
                            <input type="number" name="max_strategies" min="1" value="{{ old('max_strategies', 1) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Concurrent Trades *</label>
                            <input type="number" name="max_concurrent_trades" min="1" value="{{ old('max_concurrent_trades', 5) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Features (one per line)</label>
                        <textarea name="features[]" rows="6" placeholder="Advanced AI Trading&#10;Multi-Exchange Support&#10;24/7 Support"
                                  class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                        <p class="text-sm text-gray-500 mt-1">Enter each feature on a new line</p>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t">
                        <a href="{{ route('admin.trading-bot.packages.index') }}" class="text-gray-600 hover:underline">← Back</a>
                        <div class="flex gap-3">
                            <button type="reset" class="px-6 py-2 border rounded-lg hover:bg-gray-50">Reset</button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Package</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

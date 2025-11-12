<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin: Edit Package - {{ $package->name }}
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

                <form action="{{ route('admin.trading-bot.packages.update', $package) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Package Name *</label>
                            <input type="text" name="name" value="{{ old('name', $package->name) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug *</label>
                            <input type="text" name="slug" value="{{ old('slug', $package->slug) }}" readonly
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white bg-gray-100">
                            <p class="text-xs text-gray-500 mt-1">Slug cannot be changed</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description *</label>
                        <textarea name="description" rows="3" required
                                  class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">{{ old('description', $package->description) }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price (฿) *</label>
                            <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $package->price) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Billing Cycle *</label>
                            <select name="billing_cycle" required
                                    class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="monthly" {{ $package->billing_cycle === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ $package->billing_cycle === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                <option value="lifetime" {{ $package->billing_cycle === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Bots *</label>
                            <input type="number" name="max_bots" min="1" value="{{ old('max_bots', $package->max_bots) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Exchanges *</label>
                            <input type="number" name="max_exchanges" min="1" value="{{ old('max_exchanges', $package->max_exchanges) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Strategies *</label>
                            <input type="number" name="max_strategies" min="1" value="{{ old('max_strategies', $package->max_strategies) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Concurrent Trades *</label>
                            <input type="number" name="max_concurrent_trades" min="1" value="{{ old('max_concurrent_trades', $package->max_concurrent_trades) }}" required
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Features (one per line)</label>
                        <textarea name="features[]" rows="6"
                                  class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">{{ old('features', is_array($package->features) ? implode("\n", $package->features) : '') }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Enter each feature on a new line</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }}
                                   class="mr-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active Package</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t">
                        <a href="{{ route('admin.trading-bot.packages.index') }}" class="text-gray-600 hover:underline">← Back</a>
                        <div class="flex gap-3">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Package</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

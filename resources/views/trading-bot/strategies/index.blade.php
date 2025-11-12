<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Trading Strategies') }}
            </h2>
            <a href="{{ route('trading-bot.strategies.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">
                + สร้างกลยุทธ์ใหม่
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Strategies Grid -->
            @if($strategies->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($strategies as $strategy)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ $strategy->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($strategy->description, 60) }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($strategy->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ strtoupper($strategy->status) }}
                        </span>
                    </div>

                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ประเภท:</span>
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                {{ $strategy->strategy_type }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Stop Loss:</span>
                            <span class="font-medium text-red-600">{{ $strategy->stop_loss_percentage }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Take Profit:</span>
                            <span class="font-medium text-green-600">{{ $strategy->take_profit_percentage }}%</span>
                        </div>
                        @if($strategy->use_ai)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">AI Model:</span>
                            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs">
                                {{ $strategy->ai_model }}
                            </span>
                        </div>
                        @endif
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            🤖 {{ $strategy->bots()->count() }} บอทใช้กลยุทธ์นี้
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button class="flex-1 px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg text-sm font-semibold">
                            ✏️ แก้ไข
                        </button>
                        <button class="flex-1 px-3 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-lg text-sm font-semibold">
                            📊 Backtest
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-12 text-center">
                <div class="text-6xl mb-4">🎯</div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีกลยุทธ์</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    สร้างกลยุทธ์การเทรดของคุณเอง หรือใช้กลยุทธ์สาธารณะ
                </p>
                <a href="{{ route('trading-bot.strategies.create') }}"
                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                    + สร้างกลยุทธ์แรก
                </a>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create New Strategy') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('trading-bot.strategies.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Name & Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อกลยุทธ์ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย
                        </label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">{{ old('description') }}</textarea>
                    </div>

                    <!-- Strategy Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ประเภทกลยุทธ์ <span class="text-red-500">*</span>
                        </label>
                        <select name="strategy_type" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="trend_following">Trend Following (ตามเทรนด์)</option>
                            <option value="mean_reversion">Mean Reversion (กลับค่าเฉลี่ย)</option>
                            <option value="breakout">Breakout (เบรคเอาท์)</option>
                            <option value="scalping">Scalping (สแคลป์ปิ้ง)</option>
                            <option value="grid">Grid Trading (กริด)</option>
                            <option value="arbitrage">Arbitrage (อาบิเทรจ)</option>
                            <option value="ai_ml">AI/ML Based</option>
                        </select>
                    </div>

                    <!-- Risk Management -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Stop Loss (%) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="stop_loss_percentage" value="{{ old('stop_loss_percentage', 2) }}" step="0.1" min="0.1" max="50" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Take Profit (%) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="take_profit_percentage" value="{{ old('take_profit_percentage', 5) }}" step="0.1" min="0.1" max="100" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <!-- Indicators (JSON) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Indicators <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="indicators[]" value="RSI" class="mr-2">
                                <span class="text-sm">RSI (Relative Strength Index)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="indicators[]" value="MACD" class="mr-2">
                                <span class="text-sm">MACD (Moving Average Convergence Divergence)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="indicators[]" value="EMA" class="mr-2">
                                <span class="text-sm">EMA (Exponential Moving Average)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="indicators[]" value="BB" class="mr-2">
                                <span class="text-sm">Bollinger Bands</span>
                            </label>
                        </div>
                    </div>

                    <!-- Entry Conditions (simplified) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เงื่อนไขเข้า (Entry) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="entry_conditions[]" placeholder="เช่น: RSI < 30" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- AI Options -->
                    <div class="bg-purple-50 dark:bg-purple-900 rounded-lg p-4">
                        <label class="flex items-center space-x-3 cursor-pointer mb-4">
                            <input type="checkbox" name="use_ai" value="1" id="use_ai" onchange="document.getElementById('ai_options').classList.toggle('hidden')"
                                   class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-2 focus:ring-purple-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                🤖 ใช้ AI/ML Enhancement
                            </span>
                        </label>
                        <div id="ai_options" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                AI Model
                            </label>
                            <select name="ai_model"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                <option value="lstm">LSTM (Long Short-Term Memory)</option>
                                <option value="transformer">Transformer</option>
                                <option value="random_forest">Random Forest</option>
                                <option value="sentiment">Sentiment Analysis</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('trading-bot.strategies') }}"
                           class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            ← ย้อนกลับ
                        </a>
                        <div class="flex gap-3">
                            <button type="reset"
                                    class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                รีเซ็ต
                            </button>
                            <button type="submit"
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                                💾 สร้างกลยุทธ์
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

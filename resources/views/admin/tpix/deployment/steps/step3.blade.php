@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<form method="POST" action="{{ route('admin.tpix.deployment.step3.save', $config->slug) }}" class="space-y-8" x-data="{
    totalSupply: {{ $config->total_supply ?? 1000000 }},
    distribution: {
        team: {{ old('supply_distribution.team.percentage', ($config->supply_distribution['team']['percentage'] ?? 20)) }},
        public: {{ old('supply_distribution.public.percentage', ($config->supply_distribution['public']['percentage'] ?? 40)) }},
        liquidity: {{ old('supply_distribution.liquidity.percentage', ($config->supply_distribution['liquidity']['percentage'] ?? 30)) }},
        marketing: {{ old('supply_distribution.marketing.percentage', ($config->supply_distribution['marketing']['percentage'] ?? 10)) }}
    },
    get totalPercentage() {
        return this.distribution.team + this.distribution.public + this.distribution.liquidity + this.distribution.marketing;
    },
    get isValid() {
        return this.totalPercentage === 100;
    }
}">
    @csrf

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 3: Tokenomics
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            กำหนดเศรษฐศาสตร์และการกระจายเหรียญ
        </p>
    </div>

    {{-- Supply Distribution --}}
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            การกระจายเหรียญ (Supply Distribution)
        </h3>

        {{-- Total Percentage Indicator --}}
        <div class="mb-6 p-4 rounded-xl transition-colors"
             :class="isValid ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
            <div class="flex items-center justify-between">
                <span class="font-semibold" :class="isValid ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100'">
                    Total: <span x-text="totalPercentage"></span>%
                </span>
                <span x-show="isValid" class="text-green-600 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    ครบ 100%
                </span>
                <span x-show="!isValid" class="text-red-600">รวมต้องเท่ากับ 100%</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach(['team' => 'Team/Founders', 'public' => 'Public Sale', 'liquidity' => 'Liquidity Pool', 'marketing' => 'Marketing'] as $key => $label)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $label }} (%)
                    </label>
                    <input type="number" name="supply_distribution[{{ $key }}][percentage]"
                           x-model.number="distribution.{{ $key }}"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                  focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition"
                           min="0" max="100" required />
                    <p class="mt-1 text-xs text-gray-500">
                        = <span x-text="Math.round(totalSupply * distribution.{{ $key }} / 100).toLocaleString()"></span> tokens
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Pricing --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
            </svg>
            ราคาเริ่มต้น
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ราคาเริ่มต้น (TPIX)
                </label>
                <input type="number" step="0.00000001" name="initial_price_tpix"
                       value="{{ old('initial_price_tpix', $config->initial_price_tpix) }}"
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition"
                       placeholder="0.01" min="0" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ราคาเริ่มต้น (USD)
                </label>
                <input type="number" step="0.00000001" name="initial_price_usd"
                       value="{{ old('initial_price_usd', $config->initial_price_usd) }}"
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition"
                       placeholder="0.20" min="0" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Market Cap เป้าหมาย (USD)
                </label>
                <input type="number" step="0.01" name="market_cap_target"
                       value="{{ old('market_cap_target', $config->market_cap_target) }}"
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition"
                       placeholder="200000" min="0" />
            </div>
        </div>
    </div>

    {{-- Token Features --}}
    <div class="p-6 bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
            </svg>
            ฟีเจอร์ของเหรียญ
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach([
                'is_burnable' => ['label' => 'Burnable', 'desc' => 'สามารถเผาเหรียญได้', 'default' => true],
                'is_mintable' => ['label' => 'Mintable', 'desc' => 'สามารถสร้างเหรียญเพิ่มได้', 'default' => false],
                'is_pausable' => ['label' => 'Pausable', 'desc' => 'สามารถหยุดการทำงานชั่วคราว', 'default' => false],
                'is_freezable' => ['label' => 'Freezable', 'desc' => 'สามารถแช่แข็ง address', 'default' => false]
            ] as $key => $feature)
                <label x-data="{ enabled: {{ old($key, $config->$key ?? $feature['default']) ? 'true' : 'false' }} }"
                       class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                       :class="enabled ? 'border-green-500 bg-green-50 dark:bg-green-900/30' : 'border-gray-200 dark:border-gray-700'">
                    <div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $feature['label'] }}</span>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $feature['desc'] }}</p>
                    </div>
                    <div @click="enabled = !enabled" class="relative">
                        <input type="checkbox" name="{{ $key }}" value="1" :checked="enabled" class="sr-only" />
                        <div class="w-14 h-8 rounded-full transition-colors duration-300"
                             :class="enabled ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gray-300 dark:bg-gray-600'">
                        </div>
                        <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full shadow-lg transition-transform duration-300"
                             :class="enabled ? 'transform translate-x-6' : ''">
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        {{-- Max Supply (conditional) --}}
        <div class="mt-6" x-show="$el.querySelector('input[name=is_mintable]')?.checked">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Supply สูงสุด (ถ้า Mintable)
            </label>
            <input type="number" name="max_supply"
                   value="{{ old('max_supply', $config->max_supply) }}"
                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                          focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition"
                   placeholder="10000000" min="0" />
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-4 pt-6">
        <a href="{{ route('admin.tpix.deployment.step2', $config->slug) }}"
           class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← ย้อนกลับ
        </a>
        <button type="submit" :disabled="!isValid"
                class="flex-1 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg transition-all duration-300 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="isValid ? 'hover:shadow-xl hover:scale-105' : ''">
            ถัดไป: Smart Contract →
        </button>
    </div>
</form>
@endsection

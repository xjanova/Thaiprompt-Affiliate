@extends('layouts.admin-v3')

@section('title', 'Cost Calculator - คำนวณค่าใช้จ่าย GPU')

@section('content')
<div class="space-y-6" x-data="costCalculator()">
    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-calculator text-3xl text-white"></i>
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                    Cost Calculator
                </h1>
                <p class="text-white/80 mt-2 drop-shadow">
                    คำนวณค่าใช้จ่ายโดยประมาณสำหรับการเช่า GPU cloud
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Calculator Form --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Provider Selection --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <label class="block text-white font-bold mb-4">เลือก Cloud Provider</label>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($providers as $provider)
                    <button @click="selectProvider({{ json_encode($provider) }})"
                            :class="selectedProvider && selectedProvider.id === {{ $provider->id }} ?
                                'bg-gradient-to-r from-cyan-500 to-blue-600 text-white scale-105 border-cyan-400' :
                                'glass-neu text-white/80 hover:text-white border-white/20'"
                            class="p-4 rounded-xl transition-all transform hover:scale-105 border-2">
                        <div class="text-center">
                            <div class="font-bold text-sm mb-1">{{ $provider->name }}</div>
                            <div class="text-xs opacity-75">
                                @if($provider->min_price_per_hour)
                                    ${{ number_format($provider->min_price_per_hour, 2) }}/hr
                                @else
                                    ฟรี
                                @endif
                            </div>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Parameters --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30" x-show="selectedProvider">
                <h3 class="text-xl font-bold text-white mb-4">ตั้งค่าการใช้งาน</h3>

                <div class="space-y-4">
                    {{-- Hours --}}
                    <div>
                        <label class="block text-white font-semibold mb-2">จำนวนชั่วโมง</label>
                        <div class="flex items-center gap-4">
                            <input type="range" x-model="hours" min="1" max="720" step="1"
                                   class="flex-1 h-2 bg-white/20 rounded-lg appearance-none cursor-pointer accent-cyan-500">
                            <div class="w-24 text-right">
                                <span class="text-white text-2xl font-bold" x-text="hours"></span>
                                <span class="text-white/60 text-sm ml-1">hrs</span>
                            </div>
                        </div>
                        <div class="flex justify-between text-white/60 text-xs mt-2">
                            <span>1 hr</span>
                            <span>30 days (720 hrs)</span>
                        </div>
                    </div>

                    {{-- GPU Count --}}
                    <div>
                        <label class="block text-white font-semibold mb-2">จำนวน GPU</label>
                        <div class="flex items-center gap-4">
                            <input type="range" x-model="gpuCount" min="1" max="8" step="1"
                                   class="flex-1 h-2 bg-white/20 rounded-lg appearance-none cursor-pointer accent-purple-500">
                            <div class="w-24 text-right">
                                <span class="text-white text-2xl font-bold" x-text="gpuCount"></span>
                                <span class="text-white/60 text-sm ml-1">GPUs</span>
                            </div>
                        </div>
                        <div class="flex justify-between text-white/60 text-xs mt-2">
                            <span>1 GPU</span>
                            <span>8 GPUs</span>
                        </div>
                    </div>

                    {{-- Quick Presets --}}
                    <div>
                        <label class="block text-white font-semibold mb-2">เลือกแพ็กเกจ</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button @click="hours = 24; gpuCount = 1"
                                    class="px-4 py-2 glass-neu rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition text-sm">
                                1 Day
                            </button>
                            <button @click="hours = 168; gpuCount = 1"
                                    class="px-4 py-2 glass-neu rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition text-sm">
                                1 Week
                            </button>
                            <button @click="hours = 720; gpuCount = 1"
                                    class="px-4 py-2 glass-neu rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition text-sm">
                                1 Month
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comparison Table --}}
            <div class="glass-fusion rounded-2xl p-6 border border-white/30">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-cyan-400"></i>
                    เปรียบเทียบราคา
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/20">
                                <th class="text-left text-white/70 font-semibold pb-3 pr-4">Provider</th>
                                <th class="text-right text-white/70 font-semibold pb-3 px-2">ราคา/ชม.</th>
                                <th class="text-right text-white/70 font-semibold pb-3 px-2">รวม</th>
                                <th class="text-center text-white/70 font-semibold pb-3 pl-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providers->sortBy('min_price_per_hour') as $provider)
                            <tr class="border-b border-white/10"
                                :class="selectedProvider && selectedProvider.id === {{ $provider->id }} ? 'bg-cyan-500/10' : ''">
                                <td class="py-3 pr-4">
                                    <div class="text-white font-semibold">{{ $provider->name }}</div>
                                    @if($provider->has_free_tier)
                                        <span class="text-green-400 text-xs">ฟรี {{ $provider->free_gpu_hours ?? 0 }} hrs</span>
                                    @endif
                                </td>
                                <td class="text-right text-white px-2">
                                    @if($provider->min_price_per_hour)
                                        ${{ number_format($provider->min_price_per_hour, 2) }}
                                    @else
                                        <span class="text-green-400">ฟรี</span>
                                    @endif
                                </td>
                                <td class="text-right px-2">
                                    <span class="text-white font-bold text-lg" x-text="calculateCost({{ $provider->min_price_per_hour ?? 0 }})"></span>
                                </td>
                                <td class="text-center pl-4">
                                    @if($provider->is_recommended)
                                        <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs rounded-full">แนะนำ</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Result Card --}}
        <div class="lg:col-span-1">
            <div class="glass-fusion rounded-2xl p-6 border border-white/30 sticky top-6" x-show="selectedProvider">
                <h3 class="text-xl font-bold text-white mb-6">ค่าใช้จ่ายโดยประมาณ</h3>

                {{-- Selected Provider --}}
                <div class="bg-white/5 rounded-xl p-4 mb-6">
                    <div class="text-white/60 text-sm mb-1">Provider</div>
                    <div class="text-white font-bold text-lg" x-text="selectedProvider?.name || '-'"></div>
                </div>

                {{-- Total Cost --}}
                <div class="bg-gradient-to-br from-cyan-500/20 to-purple-500/20 border-2 border-cyan-400/50 rounded-xl p-6 mb-6">
                    <div class="text-white/80 text-sm mb-2">ค่าใช้จ่ายรวม</div>
                    <div class="text-white font-bold text-4xl" x-text="totalCost"></div>
                    <div class="text-white/60 text-xs mt-2">
                        <span x-text="hours"></span> hours ×
                        <span x-text="gpuCount"></span> GPU(s)
                    </div>
                </div>

                {{-- Breakdown --}}
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-white/70">Hourly Rate:</span>
                        <span class="text-white font-bold" x-text="hourlyRate"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/70">Total Hours:</span>
                        <span class="text-white font-bold" x-text="hours + ' hrs'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/70">GPU Count:</span>
                        <span class="text-white font-bold" x-text="gpuCount + ' GPU(s)'"></span>
                    </div>
                    <div class="border-t border-white/20 pt-3 flex justify-between items-center">
                        <span class="text-white font-bold">Total:</span>
                        <span class="text-cyan-400 font-bold text-2xl" x-text="totalCost"></span>
                    </div>
                </div>

                {{-- Savings Alert --}}
                <div class="mt-6 bg-green-500/10 border border-green-500/30 rounded-xl p-4" x-show="selectedProvider?.has_free_tier">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-gift text-green-400 mt-1"></i>
                        <div>
                            <div class="text-green-400 font-bold text-sm mb-1">Free Tier Available!</div>
                            <div class="text-white/70 text-xs">
                                Get <span x-text="selectedProvider?.free_gpu_hours || 0"></span> free GPU hours
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <a :href="selectedProvider?.website_url" target="_blank"
                   class="mt-6 w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold rounded-xl hover:scale-105 transition-transform shadow-lg">
                    <i class="fas fa-external-link-alt"></i>
                    ไปยัง <span x-text="selectedProvider?.name"></span>
                </a>
            </div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-400"></i>
            หมายเหตุ
        </h3>
        <ul class="text-white/70 space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-cyan-400 mt-1"></i>
                <span>ราคาที่แสดงเป็นราคาโดยประมาณและอาจเปลี่ยนแปลงได้</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-cyan-400 mt-1"></i>
                <span>ราคาอาจแตกต่างกันตาม GPU type และ region</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-cyan-400 mt-1"></i>
                <span>Free tier มักมีข้อจำกัดเรื่องเวลาและ GPU types</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-cyan-400 mt-1"></i>
                <span>ตรวจสอบราคาล่าสุดที่เว็บไซต์ของ provider โดยตรง</span>
            </li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
function costCalculator() {
    return {
        selectedProvider: null,
        hours: 24,
        gpuCount: 1,

        selectProvider(provider) {
            this.selectedProvider = provider;
        },

        get hourlyRate() {
            if (!this.selectedProvider) return '$0.00';
            const rate = this.selectedProvider.min_price_per_hour || 0;
            return '$' + (rate * this.gpuCount).toFixed(2);
        },

        get totalCost() {
            if (!this.selectedProvider) return '$0.00';
            const rate = this.selectedProvider.min_price_per_hour || 0;
            const total = rate * this.hours * this.gpuCount;
            return '$' + total.toFixed(2);
        },

        calculateCost(hourlyRate) {
            const total = hourlyRate * this.hours * this.gpuCount;
            return '$' + total.toFixed(2);
        }
    }
}
</script>
@endpush

@endsection

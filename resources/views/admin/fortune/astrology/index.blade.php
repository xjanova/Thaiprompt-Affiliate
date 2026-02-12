@extends('layouts.admin')

@section('title', 'ตั้งค่าโหราศาสตร์ไทย')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="astrologySettings()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                ✨ ตั้งค่าโหราศาสตร์ไทย (เจ้าชนะ)
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ตารางเจ้าชนะ ดาวเคราะห์ ภพ และทดสอบ Birth Chart</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.fortune.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>📊</span> Dashboard
            </a>
        </div>
    </div>

    {{-- Planet Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            🪐 ดาวเคราะห์ 9 ดวง
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สัญลักษณ์</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ชื่อ</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">วัน</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สี</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ความหมาย</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($planets as $key => $planet)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3">
                                <span class="text-2xl" style="color: {{ $planet['color'] }}">{{ $planet['symbol'] }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $planet['name'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                @if(isset($planet['day']))
                                    {{ $dayNames[$planet['day']] ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block w-6 h-6 rounded-full shadow-sm" style="background-color: {{ $planet['color'] }}"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $key }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Houses Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            🏠 ภพ 12 ภพ (เรือนชะตา)
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($houses as $key => $house)
                <div class="rounded-xl p-4 border-2 transition hover:shadow-md"
                     style="border-color: {{ $house['color'] }}20; background: {{ $house['color'] }}08">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow"
                              style="background-color: {{ $house['color'] }}">
                            {{ $key }}
                        </span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $house['name'] }}</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $house['meaning'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Chaochana Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            ⚔️ ตารางเจ้าชนะ (ดาวมิตร / ดาวศัตรู)
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">วัน</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ดาวเจ้าชนะ</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ธาตุ</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สีมงคล</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ดาวมิตร</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ดาวศัตรู</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($chaochana as $dayNum => $data)
                        @php
                            $rulingPlanet = $planets[$data['planet']] ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                วัน{{ $dayNames[$dayNum] ?? '?' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($rulingPlanet)
                                    <span class="inline-flex items-center gap-1">
                                        <span style="color: {{ $rulingPlanet['color'] }}">{{ $rulingPlanet['symbol'] }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $rulingPlanet['name'] }}</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $data['element'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                      style="background: {{ $rulingPlanet['color'] ?? '#888' }}20; color: {{ $rulingPlanet['color'] ?? '#888' }}">
                                    {{ $data['lucky_color'] ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach(($data['friends'] ?? []) as $friend)
                                        @php $fp = $planets[$friend] ?? null; @endphp
                                        @if($fp)
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs">
                                                <span style="color: {{ $fp['color'] }}">{{ $fp['symbol'] }}</span>
                                                {{ $fp['name'] }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach(($data['enemies'] ?? []) as $enemy)
                                        @php $ep = $planets[$enemy] ?? null; @endphp
                                        @if($ep)
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-xs">
                                                <span style="color: {{ $ep['color'] }}">{{ $ep['symbol'] }}</span>
                                                {{ $ep['name'] }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Test & Preview Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Test Calculation --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                🧮 ทดสอบคำนวณดาว
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันเกิด</label>
                    <input type="date" x-model="testDate"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <button @click="testCalc()"
                        :disabled="!testDate || testLoading"
                        class="w-full px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg font-medium shadow hover:shadow-lg transition disabled:opacity-50">
                    <span x-show="!testLoading">🔍 คำนวณ</span>
                    <span x-show="testLoading">⏳ กำลังคำนวณ...</span>
                </button>

                {{-- Result --}}
                <div x-show="testResult" x-transition class="bg-purple-50 dark:bg-purple-900/30 rounded-xl p-4 space-y-2">
                    <p class="font-bold text-purple-800 dark:text-purple-200">
                        📅 <span x-text="testResult?.birth_date"></span> — วัน<span x-text="testResult?.day_of_week"></span>
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        🪐 ดาวเจ้าชนะ: <span x-text="testResult?.ruling_planet?.symbol"></span> <span x-text="testResult?.ruling_planet?.name" class="font-medium"></span>
                        (<span x-text="testResult?.ruling_planet?.element"></span>)
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        🎨 สีมงคล: <span x-text="testResult?.lucky_color" class="font-medium"></span>
                    </p>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        ✅ ดาวมิตร:
                        <template x-for="f in (testResult?.friends || [])" :key="f">
                            <span class="inline-flex items-center px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs mr-1" x-text="f"></span>
                        </template>
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        ❌ ดาวศัตรู:
                        <template x-for="e in (testResult?.enemies || [])" :key="e">
                            <span class="inline-flex items-center px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-xs mr-1" x-text="e"></span>
                        </template>
                    </div>

                    {{-- Planet Positions in Houses --}}
                    <template x-if="testResult?.planet_positions?.length > 0">
                        <div class="mt-3 pt-3 border-t border-purple-200 dark:border-purple-700">
                            <p class="font-semibold text-purple-800 dark:text-purple-200 mb-2">🏛️ ตำแหน่งดาวในภพ:</p>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-1.5">
                                <template x-for="pos in testResult.planet_positions" :key="pos.house_number">
                                    <div class="bg-white/60 dark:bg-gray-700/60 rounded-lg p-2 text-center border border-purple-100 dark:border-purple-800">
                                        <div class="text-[10px] text-gray-500 dark:text-gray-400" x-text="pos.house_name"></div>
                                        <template x-if="pos.planets.length > 0">
                                            <div>
                                                <template x-for="pl in pos.planets" :key="pl.key">
                                                    <span class="text-lg mx-0.5" :style="'color:' + pl.color" x-text="pl.symbol" :title="pl.name"></span>
                                                </template>
                                                <div class="text-[10px] font-medium text-gray-700 dark:text-gray-300">
                                                    <template x-for="pl in pos.planets" :key="pl.key + '_name'">
                                                        <span class="mr-0.5" x-text="pl.name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="pos.planets.length === 0">
                                            <div class="text-lg text-gray-300 dark:text-gray-600">—</div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Preview Birth Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                🎨 Preview Birth Chart
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ</label>
                    <input type="text" x-model="chartName" placeholder="ชื่อตัวอย่าง"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันเกิด</label>
                    <input type="date" x-model="chartDate"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เพศ</label>
                    <select x-model="chartGender"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">ไม่ระบุ</option>
                        <option value="male">ชาย</option>
                        <option value="female">หญิง</option>
                    </select>
                </div>
                <button @click="previewChart()"
                        :disabled="!chartName || !chartDate || chartLoading"
                        class="w-full px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg font-medium shadow hover:shadow-lg transition disabled:opacity-50">
                    <span x-show="!chartLoading">🎨 สร้าง Birth Chart</span>
                    <span x-show="chartLoading">⏳ กำลังสร้าง...</span>
                </button>

                {{-- Chart Preview --}}
                <div x-show="chartUrl" x-transition class="text-center">
                    <img :src="chartUrl" alt="Birth Chart Preview"
                         class="max-w-full mx-auto rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 mt-2">ภาพ Birth Chart ที่จะส่งให้ผู้ใช้ก่อนคำทำนาย</p>
                </div>

                {{-- Error --}}
                <div x-show="chartError" x-transition class="bg-red-50 dark:bg-red-900/30 rounded-xl p-4 text-red-800 dark:text-red-300 text-sm">
                    <span x-text="chartError"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function astrologySettings() {
    return {
        // Test calculation
        testDate: '',
        testLoading: false,
        testResult: null,

        // Chart preview
        chartName: 'ทดสอบ',
        chartDate: '',
        chartGender: '',
        chartLoading: false,
        chartUrl: null,
        chartError: null,

        async testCalc() {
            if (!this.testDate) return;
            this.testLoading = true;
            this.testResult = null;
            try {
                const res = await fetch('{{ route("admin.fortune.astrology.test-calculation") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: JSON.stringify({ birth_date: this.testDate }),
                });
                const data = await res.json();
                if (data.success) {
                    this.testResult = data;
                } else {
                    alert(data.error || 'เกิดข้อผิดพลาด');
                }
            } catch (e) {
                alert('ไม่สามารถเชื่อมต่อได้: ' + e.message);
            } finally {
                this.testLoading = false;
            }
        },

        async previewChart() {
            if (!this.chartName || !this.chartDate) return;
            this.chartLoading = true;
            this.chartUrl = null;
            this.chartError = null;
            try {
                const res = await fetch('{{ route("admin.fortune.astrology.preview-chart") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: JSON.stringify({
                        birth_date: this.chartDate,
                        name: this.chartName,
                        gender: this.chartGender || null,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.chartUrl = data.chart_url;
                } else {
                    this.chartError = data.error || 'ไม่สามารถสร้าง chart ได้';
                }
            } catch (e) {
                this.chartError = 'ไม่สามารถเชื่อมต่อได้: ' + e.message;
            } finally {
                this.chartLoading = false;
            }
        },
    };
}
</script>
@endpush

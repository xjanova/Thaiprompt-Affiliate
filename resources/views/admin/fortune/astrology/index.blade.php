@extends('layouts.admin-v4')

@section('title', 'ตั้งค่าโหราศาสตร์ไทย')

@section('content')
{{-- หน้าตั้งค่าโหราศาสตร์ไทย (เจ้าชนะ) — ธีม V4 นวลทองคำ --}}
<div x-data="astrologySettings()" style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัวของหน้า --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.04em;margin-bottom:4px;">
                หลังบ้าน · ระบบดูดวง · โหราศาสตร์ไทย
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-star-and-crescent" style="color:var(--accent2);"></i>
                ตั้งค่าโหราศาสตร์ไทย (เจ้าชนะ)
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:14px;">
                ตารางเจ้าชนะ ดาวเคราะห์ ภพ และทดสอบ Birth Chart
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.dashboard'))
                <a href="{{ route('admin.fortune.dashboard') }}" class="tp-btn">
                    <i class="fas fa-gauge-high"></i> แดชบอร์ด
                </a>
            @endif
        </div>
    </div>

    {{-- KPI สรุปข้อมูลโหราศาสตร์ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="color:var(--accent2);"><i class="fas fa-circle-nodes"></i></div>
                <div>
                    <div class="tp-num" style="font-size:24px;font-weight:800;">{{ count($planets) }}</div>
                    <div class="tp-muted" style="font-size:12px;">ดาวเคราะห์</div>
                </div>
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="color:#5689b8;"><i class="fas fa-house-chimney-window"></i></div>
                <div>
                    <div class="tp-num" style="font-size:24px;font-weight:800;">{{ count($houses) }}</div>
                    <div class="tp-muted" style="font-size:12px;">ภพ (เรือนชะตา)</div>
                </div>
            </div>
        </div>
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="color:#d6824a;"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="tp-num" style="font-size:24px;font-weight:800;">{{ count($chaochana) }}</div>
                    <div class="tp-muted" style="font-size:12px;">วันเจ้าชนะ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ตารางดาวเคราะห์ 9 ดวง --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <i class="fas fa-circle-nodes" style="color:var(--accent2);"></i>
            <span>ดาวเคราะห์ 9 ดวง</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:560px;">
                <thead>
                    <tr style="text-align:left;">
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">สัญลักษณ์</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">ชื่อ</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">วัน</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;text-align:center;">สี</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">รหัส</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($planets as $key => $planet)
                        <tr style="border-top:1px solid var(--sd);">
                            <td style="padding:12px;">
                                <span style="font-size:24px;color: {{ $planet['color'] }};">{{ $planet['symbol'] }}</span>
                            </td>
                            <td style="padding:12px;font-weight:600;color:var(--ink);">{{ $planet['name'] }}</td>
                            <td style="padding:12px;font-size:14px;color:var(--ink2);">
                                @if(isset($planet['day']))
                                    {{ $dayNames[$planet['day']] ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td style="padding:12px;text-align:center;">
                                <span style="display:inline-block;width:22px;height:22px;border-radius:50%;box-shadow:var(--inset-sm);background-color: {{ $planet['color'] }};"></span>
                            </td>
                            <td style="padding:12px;font-size:13px;color:var(--ink2);font-family:ui-monospace,monospace;">{{ $key }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ภพ 12 ภพ (เรือนชะตา) --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <i class="fas fa-house-chimney-window" style="color:#5689b8;"></i>
            <span>ภพ 12 ภพ (เรือนชะตา)</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
            @foreach($houses as $key => $house)
                <div class="tp-inset" style="padding:14px;border-left:3px solid {{ $house['color'] }};">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;box-shadow:var(--inset-sm);background-color: {{ $house['color'] }};">
                            {{ $key }}
                        </span>
                        <span style="font-weight:700;color:var(--ink);">{{ $house['name'] }}</span>
                    </div>
                    <p class="tp-muted" style="margin:0;font-size:13px;">{{ $house['meaning'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ตารางเจ้าชนะ (ดาวมิตร / ดาวศัตรู) --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <i class="fas fa-shield-halved" style="color:#d6824a;"></i>
            <span>ตารางเจ้าชนะ (ดาวมิตร / ดาวศัตรู)</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:720px;">
                <thead>
                    <tr style="text-align:left;">
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">วัน</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">ดาวเจ้าชนะ</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">ธาตุ</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">สีมงคล</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">ดาวมิตร</th>
                        <th class="tp-muted" style="padding:10px 12px;font-size:12px;font-weight:600;">ดาวศัตรู</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chaochana as $dayNum => $data)
                        @php
                            $rulingPlanet = $planets[$data['planet']] ?? null;
                        @endphp
                        <tr style="border-top:1px solid var(--sd);">
                            <td style="padding:12px;font-weight:600;color:var(--ink);">
                                วัน{{ $dayNames[$dayNum] ?? '?' }}
                            </td>
                            <td style="padding:12px;">
                                @if($rulingPlanet)
                                    <span style="display:inline-flex;align-items:center;gap:6px;">
                                        <span style="color: {{ $rulingPlanet['color'] }};font-size:18px;">{{ $rulingPlanet['symbol'] }}</span>
                                        <span style="font-size:14px;color:var(--ink);">{{ $rulingPlanet['name'] }}</span>
                                    </span>
                                @endif
                            </td>
                            <td style="padding:12px;font-size:14px;color:var(--ink2);">
                                {{ $data['element'] ?? '-' }}
                            </td>
                            <td style="padding:12px;">
                                <span class="tp-pill" style="background: {{ ($rulingPlanet['color'] ?? '#9a8f7c') }}22; color: {{ $rulingPlanet['color'] ?? '#9a8f7c' }};border:0;">
                                    {{ $data['lucky_color'] ?? '-' }}
                                </span>
                            </td>
                            <td style="padding:12px;">
                                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                    @foreach(($data['friends'] ?? []) as $friend)
                                        @php $fp = $planets[$friend] ?? null; @endphp
                                        @if($fp)
                                            <span class="tp-pill" style="background:rgba(90,160,126,.14);color:#5aa07e;border:0;display:inline-flex;align-items:center;gap:4px;">
                                                <span style="color: {{ $fp['color'] }};">{{ $fp['symbol'] }}</span>
                                                {{ $fp['name'] }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td style="padding:12px;">
                                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                    @foreach(($data['enemies'] ?? []) as $enemy)
                                        @php $ep = $planets[$enemy] ?? null; @endphp
                                        @if($ep)
                                            <span class="tp-pill" style="background:rgba(217,83,79,.14);color:#d9534f;border:0;display:inline-flex;align-items:center;gap:4px;">
                                                <span style="color: {{ $ep['color'] }};">{{ $ep['symbol'] }}</span>
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

    {{-- ส่วนทดสอบ + พรีวิว --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;">

        {{-- ทดสอบคำนวณดาว --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-calculator" style="color:var(--accent2);"></i>
                <span>ทดสอบคำนวณดาว</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">วันเกิด</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="date" x-model="testDate"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>
                <button @click="testCalc()"
                        :disabled="!testDate || testLoading"
                        class="tp-btn tp-btn-primary"
                        :style="(!testDate || testLoading) ? 'opacity:.5;cursor:not-allowed;' : ''"
                        style="justify-content:center;">
                    <span x-show="!testLoading"><i class="fas fa-magnifying-glass"></i> คำนวณ</span>
                    <span x-show="testLoading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> กำลังคำนวณ...</span>
                </button>

                {{-- ผลลัพธ์การคำนวณ --}}
                <div x-show="testResult" x-transition class="tp-inset" style="padding:16px;display:none;">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <p style="margin:0;font-weight:700;color:var(--ink);">
                            <i class="fas fa-calendar-day" style="color:var(--accent2);"></i>
                            <span x-text="testResult?.birth_date"></span> — วัน<span x-text="testResult?.day_of_week"></span>
                        </p>
                        <p style="margin:0;font-size:14px;color:var(--ink2);">
                            <i class="fas fa-star" style="color:var(--accent2);"></i>
                            ดาวเจ้าชนะ:
                            <span x-text="testResult?.ruling_planet?.symbol"></span>
                            <span x-text="testResult?.ruling_planet?.name" style="font-weight:600;color:var(--ink);"></span>
                            (<span x-text="testResult?.ruling_planet?.element"></span>)
                        </p>
                        <p style="margin:0;font-size:14px;color:var(--ink2);">
                            <i class="fas fa-palette" style="color:var(--accent2);"></i>
                            สีมงคล: <span x-text="testResult?.lucky_color" style="font-weight:600;color:var(--ink);"></span>
                        </p>
                        <div style="font-size:14px;color:var(--ink2);">
                            <i class="fas fa-circle-check" style="color:#5aa07e;"></i> ดาวมิตร:
                            <template x-for="f in (testResult?.friends || [])" :key="f">
                                <span class="tp-pill" style="background:rgba(90,160,126,.14);color:#5aa07e;border:0;margin:2px 2px 0 0;" x-text="f"></span>
                            </template>
                        </div>
                        <div style="font-size:14px;color:var(--ink2);">
                            <i class="fas fa-circle-xmark" style="color:#d9534f;"></i> ดาวศัตรู:
                            <template x-for="e in (testResult?.enemies || [])" :key="e">
                                <span class="tp-pill" style="background:rgba(217,83,79,.14);color:#d9534f;border:0;margin:2px 2px 0 0;" x-text="e"></span>
                            </template>
                        </div>

                        {{-- ตำแหน่งดาวในภพ --}}
                        <template x-if="testResult?.planet_positions?.length > 0">
                            <div style="margin-top:8px;padding-top:12px;border-top:1px solid var(--sd);">
                                <p style="margin:0 0 8px;font-weight:600;color:var(--ink);">
                                    <i class="fas fa-landmark-dome" style="color:var(--accent2);"></i> ตำแหน่งดาวในภพ:
                                </p>
                                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:6px;">
                                    <template x-for="pos in testResult.planet_positions" :key="pos.house_number">
                                        <div class="tp-well" style="padding:8px;text-align:center;">
                                            <div class="tp-muted" style="font-size:10px;" x-text="pos.house_name"></div>
                                            <template x-if="pos.planets.length > 0">
                                                <div>
                                                    <template x-for="pl in pos.planets" :key="pl.key">
                                                        <span style="font-size:18px;margin:0 2px;" :style="'color:' + pl.color" x-text="pl.symbol" :title="pl.name"></span>
                                                    </template>
                                                    <div style="font-size:10px;font-weight:600;color:var(--ink2);">
                                                        <template x-for="pl in pos.planets" :key="pl.key + '_name'">
                                                            <span style="margin-right:2px;" x-text="pl.name"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="pos.planets.length === 0">
                                                <div class="tp-muted" style="font-size:18px;">—</div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- พรีวิว Birth Chart --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-image" style="color:#5689b8;"></i>
                <span>พรีวิว Birth Chart</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">ชื่อ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" x-model="chartName" placeholder="ชื่อตัวอย่าง"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">วันเกิด</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="date" x-model="chartDate"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">เพศ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select x-model="chartGender"
                                style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                            <option value="">ไม่ระบุ</option>
                            <option value="male">ชาย</option>
                            <option value="female">หญิง</option>
                        </select>
                    </div>
                </div>
                <button @click="previewChart()"
                        :disabled="!chartName || !chartDate || chartLoading"
                        class="tp-btn tp-btn-primary"
                        :style="(!chartName || !chartDate || chartLoading) ? 'opacity:.5;cursor:not-allowed;' : ''"
                        style="justify-content:center;">
                    <span x-show="!chartLoading"><i class="fas fa-wand-magic-sparkles"></i> สร้าง Birth Chart</span>
                    <span x-show="chartLoading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...</span>
                </button>

                {{-- พรีวิวภาพ Chart --}}
                <div x-show="chartUrl" x-transition style="text-align:center;display:none;">
                    <img :src="chartUrl" alt="Birth Chart Preview"
                         class="tp-raise"
                         style="max-width:100%;margin:0 auto;border-radius:14px;">
                    <p class="tp-muted" style="font-size:12px;margin-top:8px;">ภาพ Birth Chart ที่จะส่งให้ผู้ใช้ก่อนคำทำนาย</p>
                </div>

                {{-- ข้อความข้อผิดพลาด --}}
                <div x-show="chartError" x-transition class="tp-inset" style="padding:14px;color:#d9534f;font-size:14px;display:none;">
                    <i class="fas fa-triangle-exclamation"></i> <span x-text="chartError"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- scripts stack: Alpine component + AJAX (test-calculation / preview-chart) คง endpoint/payload เดิม --}}
@push('scripts')
<script>
function astrologySettings() {
    return {
        // ทดสอบคำนวณดาว
        testDate: '',
        testLoading: false,
        testResult: null,

        // พรีวิว birth chart
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
                    this.$dispatch('notify', { type: 'error', message: data.error || 'เกิดข้อผิดพลาด' });
                }
            } catch (e) {
                this.$dispatch('notify', { type: 'error', message: 'ไม่สามารถเชื่อมต่อได้: ' + e.message });
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

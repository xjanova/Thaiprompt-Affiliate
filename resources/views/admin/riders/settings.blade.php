{{--
 | ตั้งค่าระบบไรเดอร์ / ค่าส่ง (admin.riders.settings) — ธีม V4
 | ตัวแปรจาก Admin\RiderController@settings:
 |   $settings [field => value], $spec (RiderController::SETTINGS_SPEC: key,type,min,max,options,label,unit),
 |   $feeExamples (DeliveryFeeCalculator::quoteForDistance ของค่าที่บันทึกแล้ว: 1/3/5/8/12 กม.), $updateUrl, $pageTitle
 | ฟอร์ม POST $updateUrl ฟิลด์ settings[<field>] — boolean ส่ง hidden 0 + checkbox 1
 | ตัวคำนวณค่าส่งสดใช้สูตรเดียวกับ DeliveryFeeCalculator::quoteForDistance (ปัดขึ้นเป็นบาทเต็ม, ไม่ต่ำกว่าค่าส่งขั้นต่ำ)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'ตั้งค่าระบบไรเดอร์')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $groups = [
        'fees' => ['title' => 'ค่าส่งและส่วนแบ่งรายได้', 'icon' => 'fa-coins', 'hint' => 'ลูกค้าจ่ายค่าส่งตามสูตรนี้ ไรเดอร์ได้ส่วนแบ่งตามเปอร์เซ็นต์ ที่เหลือเป็นรายได้แพลตฟอร์ม',
            'fields' => ['base_fee', 'free_km', 'per_km_fee', 'min_fee', 'max_distance_km', 'road_factor', 'rider_share_percent']],
        'dispatch' => ['title' => 'การกระจายงาน', 'icon' => 'fa-bullhorn', 'hint' => 'ระบบแจ้งไรเดอร์ใกล้ร้านก่อน ถ้าไม่มีคนรับจะขยายรัศมีตามรอบ แล้วส่งให้แอดมินจัดเอง',
            'fields' => ['dispatch_mode', 'offer_radius_km', 'max_offer_radius_km', 'max_dispatch_rounds', 'rebroadcast_interval_minutes', 'offer_timeout_seconds', 'pending_timeout_minutes', 'max_release_count']],
        'cod' => ['title' => 'เก็บเงินปลายทาง (COD) และเงินประกัน', 'icon' => 'fa-hand-holding-dollar', 'hint' => 'ไรเดอร์รับงาน COD ได้เมื่อยอดวอลเลต ≥ ยอด COD − ค่าส่งที่ไรเดอร์ได้',
            'fields' => ['max_cod_amount', 'require_deposit']],
        'gps' => ['title' => 'GPS และลิงก์ติดตาม', 'icon' => 'fa-location-crosshairs', 'hint' => 'ความสดของพิกัดที่ใช้เลือกไรเดอร์ ระยะเวลาเก็บประวัติ และอายุลิงก์ติดตามของลูกค้า',
            'fields' => ['location_fresh_minutes', 'location_retention_days', 'tracking_expiry_hours', 'tracking_grace_minutes', 'avg_speed_kmh']],
    ];
    $grouped = collect($groups)->flatMap(fn ($g) => $g['fields'])->all();
    $others = array_values(array_diff(array_keys($spec), $grouped));
    if ($others !== []) {
        $groups['others'] = ['title' => 'อื่นๆ', 'icon' => 'fa-sliders', 'hint' => null, 'fields' => $others];
    }
    $optionLabels = [
        'dispatch_mode' => [
            'broadcast' => 'แจ้งทุกคนในรัศมี (ใครกดรับก่อนได้งาน)',
            'cascade' => 'เสนอทีละคน (รอตัดสินใจทีละคน)',
        ],
    ];
    $fieldHints = [
        'base_fee' => 'รวมระยะทางช่วงแรกตาม "ระยะที่รวมในค่าส่งเริ่มต้น"',
        'road_factor' => 'ระยะเส้นตรงบนแผนที่ × ตัวคูณนี้ = ระยะถนนจริงโดยประมาณ',
        'rider_share_percent' => 'ไรเดอร์ได้กี่ % ของค่าส่ง ที่เหลือเข้าแพลตฟอร์ม',
        'pending_timeout_minutes' => 'งานที่ไม่มีคนรับเกินเวลานี้จะขึ้น "ต้องจัดเอง" ในจอมอนิเตอร์',
        'require_deposit' => 'เปิดแล้วไรเดอร์ที่ยังไม่วางเงินประกันจะเปิดรับงานไม่ได้',
    ];
    $initial = [];
    foreach ($spec as $field => $item) {
        $value = old("settings.{$field}", $settings[$field] ?? null);
        $initial[$field] = match ($item['type']) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }
@endphp
<div x-data="w1RiderSettings(@js($initial), @js($settings))" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.riders.index') }}" class="tp-icon-btn" title="กลับหน้ารายชื่อไรเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · ตั้งค่าค่าส่ง</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ตั้งค่าค่าส่งและระบบไรเดอร์ ⚙️</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ปรับตัวเลขแล้วดูผลค่าส่งจริงได้ทันทีก่อนกดบันทึก</div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            @if (\Illuminate\Support\Facades\Route::has('admin.pricing.settings'))
                <a href="{{ route('admin.pricing.settings') }}" class="tp-btn tp-btn-sm"><i class="fas fa-percent"></i> ส่วนแบ่งรายได้ & GP</a>
            @endif
            <a href="{{ route('admin.riders.monitor') }}" class="tp-btn tp-btn-sm"><i class="fas fa-satellite-dish"></i> มอนิเตอร์สด</a>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    <form method="POST" action="{{ $updateUrl }}" @submit="submitting = true" style="display:flex; flex-direction:column; gap:18px;">
        @csrf

        @foreach ($groups as $groupKey => $group)
            <section class="tp-card" style="padding:20px;">
                <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:38px; height:38px; font-size:15px;"><i class="fas {{ $group['icon'] }}"></i></span>
                    <div>
                        <div class="tp-section-h">{{ $group['title'] }}</div>
                        @if ($group['hint'])
                            <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $group['hint'] }}</div>
                        @endif
                    </div>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">
                    <div style="flex:3 1 420px; min-width:0; display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr)); gap:14px;">
                        @foreach ($group['fields'] as $field)
                            @continue(! isset($spec[$field]))
                            @php
                                $item = $spec[$field];
                                $name = "settings[{$field}]";
                                $hasError = $errors->has("settings.{$field}");
                            @endphp
                            <div style="display:flex; flex-direction:column; min-width:0;">
                                @if ($item['type'] === 'boolean')
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <label style="display:flex; align-items:center; gap:10px; padding:11px 13px; border-radius:13px; box-shadow:var(--inset-sm); cursor:pointer; min-height:44px;">
                                        <input type="checkbox" name="{{ $name }}" value="1" x-model="s.{{ $field }}" style="width:18px; height:18px; accent-color:var(--accent1);">
                                        <span style="font-size:13px; font-weight:600;">{{ $item['label'] }}</span>
                                    </label>
                                @elseif ($item['type'] === 'string')
                                    <label for="setting-{{ $field }}" style="font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">{{ $item['label'] }}</label>
                                    <select id="setting-{{ $field }}" name="{{ $name }}" class="tp-input" x-model="s.{{ $field }}"
                                            @if ($hasError) style="box-shadow:var(--inset), 0 0 0 2px var(--w-bad);" @endif>
                                        @foreach ($item['options'] ?? [] as $option)
                                            <option value="{{ $option }}">{{ $optionLabels[$field][$option] ?? $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <label for="setting-{{ $field }}" style="font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px; display:flex; justify-content:space-between; gap:6px;">
                                        <span>{{ $item['label'] }} @isset($item['unit'])<span style="font-weight:500;">({{ $item['unit'] }})</span>@endisset</span>
                                        <span x-show="changed('{{ $field }}')" x-cloak class="tp-pill tp-pill-soft" style="font-size:9.5px; padding:2px 7px;">แก้ไขแล้ว</span>
                                    </label>
                                    <input id="setting-{{ $field }}" type="number" name="{{ $name }}" class="tp-input"
                                           x-model.number="s.{{ $field }}"
                                           step="{{ $item['type'] === 'integer' ? '1' : '0.01' }}"
                                           @isset($item['min']) min="{{ $item['min'] }}" @endisset
                                           @isset($item['max']) max="{{ $item['max'] }}" @endisset
                                           inputmode="decimal"
                                           @if ($hasError) style="box-shadow:var(--inset), 0 0 0 2px var(--w-bad);" @endif>
                                    @isset($item['min'], $item['max'])
                                        <span style="font-size:11px; color:var(--ink2); margin-top:4px;">ตั้งได้ {{ $item['min'] }} – {{ number_format((float) $item['max']) }}</span>
                                    @endisset
                                @endif
                                @if (! empty($fieldHints[$field]))
                                    <span style="font-size:11px; color:var(--ink2); margin-top:3px;">{{ $fieldHints[$field] }}</span>
                                @endif
                                @error("settings.{$field}")
                                    <span style="font-size:12px; color:var(--w-bad); margin-top:4px;">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    @if ($groupKey === 'fees')
                        {{-- ===== ตัวคำนวณค่าส่งสด (ใช้ค่าที่กำลังแก้ ยังไม่บันทึก) ===== --}}
                        <div class="tp-well" style="flex:2 1 300px; min-width:0; padding:16px; border-radius:18px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:10px;">
                                <div class="tp-section-h" style="font-size:14px;"><i class="fas fa-calculator"></i> ทดลองคำนวณค่าส่ง</div>
                                <span class="tp-pill tp-pill-soft">ค่าที่กำลังแก้</span>
                            </div>
                            <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600;">
                                ระยะทางตามถนน: <b class="tp-num" style="color:var(--ink);" x-text="km.toFixed(1) + ' กม.'"></b>
                            </label>
                            <input type="range" class="tp-range" min="0.5" :max="Math.max(5, (Number(s.max_distance_km) || 15) + 5)" step="0.5" x-model.number="km" style="margin:8px 0 4px;">
                            <div style="font-size:11px; color:var(--ink2);">
                                = เส้นตรงบนแผนที่ประมาณ <span class="tp-num" x-text="(km / Math.max(1, Number(s.road_factor) || 1)).toFixed(2)"></span> กม. × ตัวคูณ <span class="tp-num" x-text="Number(s.road_factor || 1).toFixed(2)"></span>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr auto; gap:6px 12px; font-size:13px; margin-top:12px;">
                                <span style="color:var(--ink2);">ค่าส่งเริ่มต้น</span><b class="tp-num" x-text="'฿' + money(quote(km).base_fee)"></b>
                                <span style="color:var(--ink2);">ค่าระยะทางเพิ่ม</span><b class="tp-num" x-text="'฿' + money(quote(km).distance_fee)"></b>
                                <span style="font-weight:700;">ลูกค้าจ่าย</span><b class="tp-num" style="font-size:17px;" x-text="'฿' + money(quote(km).total_fee)"></b>
                            </div>

                            {{-- แท่งส่วนแบ่ง ไรเดอร์ / แพลตฟอร์ม --}}
                            <div style="display:flex; height:12px; border-radius:12px; overflow:hidden; margin:12px 0 6px; box-shadow:var(--inset-sm);">
                                <span :style="{ width: share() + '%', background: 'linear-gradient(90deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 70%, var(--ink)))' }"></span>
                                <span :style="{ width: (100 - share()) + '%', background: 'linear-gradient(90deg, var(--accent1), var(--accent2))' }"></span>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr auto; gap:6px 12px; font-size:13px;">
                                <span><i class="fas fa-motorcycle" style="color:var(--w-ok);"></i> ไรเดอร์ได้ (<span x-text="share()"></span>%)</span><b class="tp-num" x-text="'฿' + money(quote(km).rider_earnings)"></b>
                                <span><i class="fas fa-building" style="color:var(--accent1);"></i> แพลตฟอร์มได้</span><b class="tp-num" x-text="'฿' + money(quote(km).platform_fee)"></b>
                                <span style="color:var(--ink2);">เวลาโดยประมาณ</span><b class="tp-num" x-text="quote(km).estimated_duration_minutes + ' นาที'"></b>
                            </div>
                            <div x-show="!quote(km).within_service_area" x-cloak style="margin-top:10px; font-size:12px; color:var(--w-bad);">
                                <i class="fas fa-triangle-exclamation"></i> เกินระยะส่งสูงสุด <span x-text="s.max_distance_km"></span> กม. — ลูกค้าจะสั่งแบบไรเดอร์ไม่ได้
                            </div>

                            <div class="tp-divider" style="margin:14px 0 10px;"></div>
                            <div style="font-size:12px; font-weight:700; margin-bottom:6px;">ตัวอย่างตามระยะ (ค่าที่กำลังแก้)</div>
                            <div style="overflow-x:auto;">
                                <table style="width:100%; border-collapse:collapse; font-size:12px;">
                                    <thead>
                                        <tr style="color:var(--ink2); text-align:right;">
                                            <th style="text-align:left; padding:4px 0;">ระยะ</th><th style="padding:4px;">ลูกค้าจ่าย</th><th style="padding:4px;">ไรเดอร์</th><th style="padding:4px 0;">แพลตฟอร์ม</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="d in [1, 3, 5, 8, 12]" :key="d">
                                            <tr style="text-align:right;" :style="{ opacity: quote(d).within_service_area ? 1 : .45 }">
                                                <td style="text-align:left; padding:4px 0;" class="tp-num" x-text="d + ' กม.'"></td>
                                                <td style="padding:4px;" class="tp-num" x-text="'฿' + money(quote(d).total_fee)"></td>
                                                <td style="padding:4px;" class="tp-num" x-text="'฿' + money(quote(d).rider_earnings)"></td>
                                                <td style="padding:4px 0;" class="tp-num" x-text="'฿' + money(quote(d).platform_fee)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach

        {{-- ===== แถบบันทึก ===== --}}
        <div class="tp-card" style="padding:14px 18px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; position:sticky; bottom:12px; z-index:5;">
            <div style="font-size:13px; color:var(--ink2);">
                <span x-show="changedCount() === 0">ยังไม่มีการแก้ไข</span>
                <span x-show="changedCount() > 0" x-cloak>แก้ไข <b class="tp-num" style="color:var(--ink);" x-text="changedCount()"></b> ค่า — มีผลกับงานใหม่ทันทีหลังบันทึก (งานที่สร้างไปแล้วไม่เปลี่ยน)</span>
            </div>
            <div style="display:flex; gap:9px;">
                <button type="button" class="tp-btn" @click="reset()" x-show="changedCount() > 0" x-cloak><i class="fas fa-rotate-left"></i> คืนค่าเดิม</button>
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="submitting">
                    <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- ===== ค่าส่งจริงตอนนี้ (ค่าที่บันทึกแล้ว — คำนวณฝั่งเซิร์ฟเวอร์) ===== --}}
    <section class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 18px 6px;">
            <div class="tp-section-h"><i class="fas fa-receipt"></i> ค่าส่งที่ลูกค้าจ่ายจริงตอนนี้</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:2px;">คำนวณจากค่าที่บันทึกล่าสุดด้วย DeliveryFeeCalculator (ตัวเดียวกับหน้าชำระเงิน)</div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:620px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:right; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px; text-align:left;">ระยะทาง</th>
                        <th style="padding:10px 12px;">ลูกค้าจ่าย</th>
                        <th style="padding:10px 12px;">ไรเดอร์ได้</th>
                        <th style="padding:10px 12px;">แพลตฟอร์มได้</th>
                        <th style="padding:10px 12px;">เวลาโดยประมาณ</th>
                        <th style="padding:10px 18px; text-align:center;">พื้นที่บริการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feeExamples as $example)
                        <tr class="w1-row" style="text-align:right; box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:10px 18px; text-align:left;" class="tp-num">{{ number_format((float) $example['distance_km'], 1) }} กม.</td>
                            <td style="padding:10px 12px; font-weight:700;" class="tp-num">฿{{ number_format((float) $example['total_fee'], 2) }}</td>
                            <td style="padding:10px 12px; color:color-mix(in srgb, var(--w-ok) 74%, var(--ink));" class="tp-num">฿{{ number_format((float) $example['rider_earnings'], 2) }}</td>
                            <td style="padding:10px 12px;" class="tp-num">฿{{ number_format((float) $example['platform_fee'], 2) }}</td>
                            <td style="padding:10px 12px;" class="tp-num">{{ (int) $example['estimated_duration_minutes'] }} นาที</td>
                            <td style="padding:10px 18px; text-align:center;">
                                @include('admin.riders.partials.pill', ['pillTone' => ! empty($example['within_service_area']) ? 'ok' : 'bad', 'pillText' => ! empty($example['within_service_area']) ? 'ส่งได้' : 'เกินระยะ', 'pillIcon' => null, 'pillTitle' => null])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:28px; text-align:center; color:var(--ink2);">ยังคำนวณตัวอย่างไม่ได้</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    /* ฟอร์มตั้งค่าไรเดอร์ + ตัวคำนวณค่าส่งสด (สูตรเดียวกับ App\Services\DeliveryFeeCalculator::quoteForDistance) */
    function w1RiderSettings(initial, saved) {
        return {
            s: Object.assign({}, initial),
            saved: Object.assign({}, saved),
            km: 5,
            submitting: false,
            r2(v) {
                return Math.round((Number(v) + Number.EPSILON) * 100) / 100;
            },
            share() {
                return Math.min(100, Math.max(0, Number(this.s.rider_share_percent) || 0));
            },
            quote(distance) {
                const d = this.r2(Math.max(0, Number(distance) || 0));
                const base = this.r2(Math.max(0, Number(this.s.base_fee) || 0));
                const perKm = Math.max(0, Number(this.s.per_km_fee) || 0);
                const freeKm = Math.max(0, Number(this.s.free_km) || 0);
                const minFee = Math.max(0, Number(this.s.min_fee) || 0);
                const raw = Math.max(0, d - freeKm) * perKm;
                let total = Math.ceil(this.r2(Math.max(minFee, base + raw)));
                total = Math.max(total, base);
                const rider = this.r2(total * this.share() / 100);
                const speed = Math.max(5, Number(this.s.avg_speed_kmh) || 25);
                return {
                    base_fee: base,
                    distance_fee: this.r2(total - base),
                    total_fee: total,
                    rider_earnings: rider,
                    platform_fee: this.r2(total - rider),
                    estimated_duration_minutes: Math.ceil(d / speed * 60) + 5,
                    within_service_area: d <= (Number(this.s.max_distance_km) || 0),
                };
            },
            money(v) {
                return (Number(v) || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            changed(field) {
                const a = this.s[field], b = this.saved[field];
                if (typeof b === 'boolean') return Boolean(a) !== b;
                if (typeof b === 'number') return Number(a) !== b;
                return String(a) !== String(b);
            },
            changedCount() {
                return Object.keys(this.saved).filter((f) => this.changed(f)).length;
            },
            reset() {
                this.s = Object.assign({}, this.saved);
            },
        };
    }
</script>
@endpush

{{--
    จำลองรายได้ MLM (ธีมโนวาเต็ม) — route closure user.mlm.income-simulator (เครื่องมือการตลาด ไม่แตะเงินจริง)
    ข้อมูล: การตั้งค่า GET user.mlm.settings · ยศ GET /api/v1/ranks (public) · คำนวณ POST user.mlm.preview-calculation
    🐛 แก้จากหน้าเดิม:
       - ranks มาจาก JSON เป็นสตริง (decimal:2) → selectedRank.bonus_multiplier.toFixed() พังทั้งหน้า → ใช้ Number()
       - "ระดับ x / 5" ฮาร์ดโค้ด → ใช้ระดับสูงสุดของยศที่เปิดใช้จริง
       - ตัวคูณยศ < 1 หรือความลึก > 10 ทำให้ API ตอบ 422 แล้วรายได้กลายเป็น 0 เงียบๆ → บีบค่าให้อยู่ในช่วงที่ API รับ
       - โหลด settings ไม่สำเร็จ (500) แล้ว this.settings = undefined → ปุ่มจำลองพัง → ใช้ค่าเริ่มต้นแทน
       - กดรีเซ็ตระหว่างจำลองแล้ว loop ยังวิ่งต่อ → มีตัวนับรอบ ยกเลิกได้จริง
       - ทีม 0 คน / รายได้ 0 → หารศูนย์ได้ NaN ในคำแนะนำ
       - tooltip ใช้ x-html → เปลี่ยนเป็นข้อความธรรมดา, Chart.js ไม่ระบุเวอร์ชัน → ปักที่ 4.4.1
--}}
@extends('layouts.user-nova')

@section('title', 'จำลองรายได้ MLM')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page" x-data="nvmMlIncomeSim(@js(route('user.mlm.settings')), @js(url('/api/v1/ranks')), @js(route('user.mlm.preview-calculation')))">

<section class="nvm-ph" aria-labelledby="nvm-mls-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Income simulator</div>
            <h1 id="nvm-mls-h">จำลองรายได้ MLM</h1>
            <p class="nvm-ph__lead">ลองใส่ยอดขายและขนาดทีม ดูรายได้โดยประมาณจากค่าตั้งระบบจริง</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-alert nvm-alert--info">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt"><b>ตัวเลขนี้เป็นการประมาณเท่านั้น</b><small>ไม่ใช่การรับประกันรายได้ รายได้จริงขึ้นกับยอดขายจริงของคุณและทีม</small></div>
</div>

<section class="nvm-card nvm-box" aria-labelledby="nvm-mls-set-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">System settings</small><h2 id="nvm-mls-set-h">ค่าตั้งของระบบ</h2></div>
        <button type="button" class="nvm-btn nvm-btn--ghost nvm-btn--sm" @click="showSettingsHelp = ! showSettingsHelp" :aria-expanded="showSettingsHelp.toString()">
            <svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span x-text="showSettingsHelp ? 'ซ่อนคำอธิบาย' : 'ดูคำอธิบาย'"></span>
        </button>
    </div>
    <div class="nvm-minis">
        <div class="nvm-mini"><small>อัตรา PV</small><b class="nvm-num" x-text="Number(settings.global_pv_rate || 1).toFixed(2)"></b></div>
        <div class="nvm-mini"><small>Unilevel ลึกสูงสุด</small><b class="nvm-num"><span x-text="settings.unilevel_max_depth || 10"></span> ชั้น</b></div>
        <div class="nvm-mini"><small>ค่าคอม Binary ต่อคู่</small><b class="nvm-num">฿<span x-text="fmt(settings.binary_pair_commission || 0)"></span></b></div>
        <div class="nvm-mini"><small>แบบจับคู่</small><b class="nvm-num" x-text="settings.binary_pairing_type || '1:1'"></b></div>
    </div>
    <p class="nvm-err nvm-gap-t" x-show="settingsFallback" x-cloak>โหลดค่าตั้งของระบบไม่สำเร็จ กำลังใช้ค่าเริ่มต้นแทน</p>
    <ul class="nvm-steps nvm-gap-t" x-show="showSettingsHelp" x-cloak x-transition.opacity>
        <li><b>อัตรา PV</b> อัตราแปลงยอดขายเป็นคะแนน PV</li>
        <li><b>Unilevel ลึกสูงสุด</b> จำนวนชั้นที่ระบบคิดค่าคอมมิชชั่นให้</li>
        <li><b>ค่าคอม Binary ต่อคู่</b> เงินที่ได้ต่อการจับคู่ซ้าย-ขวา 1 คู่</li>
        <li><b>แบบจับคู่</b> สัดส่วน PV ขาซ้ายต่อขาขวาที่ใช้จับคู่ (1:1 หรือ 2:1)</li>
    </ul>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-mls-in-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Inputs</small><h2 id="nvm-mls-in-h">ข้อมูลที่ใช้จำลอง</h2></div></div>

    <div class="nvm-form">
        <div class="nvm-field">
            <label class="nvm-label" for="mls-rank">ยศของคุณ (ไม่บังคับ)</label>
            <select id="mls-rank" class="nvm-input" x-model="selectedRankId" @change="updateRankInfo()">
                <option value="">ไม่เลือกยศ (คำนวณแบบพื้นฐาน)</option>
                <template x-for="rank in ranks" :key="rank.id">
                    <option :value="rank.id" x-text="rankLabel(rank)"></option>
                </template>
            </select>
            <span class="nvm-help" x-show="ranksError" x-cloak>โหลดรายการยศไม่สำเร็จ ยังจำลองแบบพื้นฐานได้</span>
        </div>

        <div class="nvm-minis nvm-ml-minis3" x-show="selectedRank" x-cloak>
            <div class="nvm-mini"><small>คอมมิชชั่นส่วนตัว</small><b class="nvm-num"><span x-text="num(selectedRank?.commission_rate).toFixed(2)"></span>%</b></div>
            <div class="nvm-mini"><small>ตัวคูณโบนัส</small><b class="nvm-num"><span x-text="num(selectedRank?.bonus_multiplier, 1).toFixed(2)"></span>x</b></div>
            <div class="nvm-mini"><small>ระดับยศ</small><b class="nvm-num"><span x-text="selectedRank?.level ?? '-'"></span> / <span x-text="maxLevel"></span></b></div>
        </div>
        <p class="nvm-note" x-show="! selectedRank">
            <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
            <span>ไม่ได้เลือกยศ จะไม่มีคอมมิชชั่นส่วนตัว และ Unilevel/Binary ไม่มีตัวคูณจากยศ (1.00x)</span>
        </p>

        <div class="nvm-ml-inputs">
            @foreach([
                ['personalSales', 'ยอดขายของคุณ', 'บาท/เดือน', 1000, 0, null, 'personal'],
                ['teamSize', 'จำนวนสมาชิกในทีม', 'คน', 1, 0, null, 'team'],
                ['teamAvgSales', 'ยอดขายเฉลี่ยต่อคน', 'บาท/คน/เดือน', 500, 0, null, 'avg'],
                ['months', 'ระยะเวลา', 'เดือน (1-60)', 1, 1, 60, 'months'],
            ] as [$key, $label, $unit, $step, $min, $max, $tip])
                <div class="nvm-field">
                    <div class="nvm-ml-lblrow">
                        <label class="nvm-label" for="mls-{{ $key }}">{{ $label }}</label>
                        <button type="button" class="nvm-ml-tipbtn" @click="openTip('{{ $tip }}')" aria-label="คำอธิบาย {{ $label }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></button>
                    </div>
                    <input id="mls-{{ $key }}" type="number" inputmode="numeric" class="nvm-input nvm-num" x-model.number="inputs.{{ $key }}" step="{{ $step }}" min="{{ $min }}" @if($max) max="{{ $max }}" @endif>
                    <span class="nvm-help">{{ $unit }}</span>
                </div>
            @endforeach
        </div>

        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--gold" @click="startSimulation()" :disabled="isRunning">
                <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg><span x-text="isRunning ? 'กำลังจำลอง...' : 'เริ่มจำลอง'"></span>
            </button>
            <button type="button" class="nvm-btn nvm-btn--line" @click="resetSimulation()"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>รีเซ็ต</button>
            <button type="button" class="nvm-btn nvm-btn--ghost" @click="window.print()" x-show="showResults" x-cloak><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>พิมพ์</button>
        </div>
        <p class="nvm-err" x-show="inputError" x-cloak x-text="inputError"></p>
    </div>
</section>

{{-- ระหว่างจำลอง --}}
<section class="nvm-card nvm-box" x-show="showAnimation" x-cloak aria-live="polite">
    <div class="nvm-ml-runhead">
        <small>กำลังจำลอง</small>
        <b class="nvm-num">เดือนที่ <span x-text="currentMonth"></span></b>
        <small>จาก <span x-text="inputs.months"></span> เดือน</small>
    </div>
    <div class="nvm-minis nvm-ml-minis3 nvm-gap-t">
        <div class="nvm-mini"><small>คอมมิชชั่นส่วนตัว</small><b class="nvm-num">฿<span x-text="fmt(currentIncome.personal)"></span></b></div>
        <div class="nvm-mini"><small>จากทีม (Unilevel + Binary)</small><b class="nvm-num">฿<span x-text="fmt(currentIncome.team)"></span></b></div>
        <div class="nvm-mini"><small>รวมเดือนนี้</small><b class="nvm-num is-in">฿<span x-text="fmt(currentIncome.total)"></span></b></div>
    </div>
    <div class="nvm-meter nvm-gap-t">
        <div class="nvm-meter__row"><span>ความคืบหน้า</span><b class="nvm-num" x-text="progress + '%'"></b></div>
        <div class="nvm-meter__bar"><i :style="'--w: ' + progress + '%'"></i></div>
    </div>
</section>

{{-- ผลลัพธ์ --}}
<template x-if="showResults">
<div class="nvm-stack">
    <section class="nvm-kpis" aria-label="สรุปผลการจำลอง">
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">รายได้เฉลี่ย/เดือน</div><div class="nvm-kpi__en nvm-latin">Average</div>
            <div class="nvm-kpi__val nvm-num">฿<span x-text="fmt(metrics.avgMonthly)"></span></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">เดือนที่สูงสุด</div><div class="nvm-kpi__en nvm-latin">Best month</div>
            <div class="nvm-kpi__val nvm-num">฿<span x-text="fmt(metrics.bestMonth)"></span></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">อัตราการเติบโต</div><div class="nvm-kpi__en nvm-latin">Growth</div>
            <div class="nvm-kpi__val nvm-num"><span x-text="fmt(metrics.growthRate)"></span>%</div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">คาดการณ์ต่อปี</div><div class="nvm-kpi__en nvm-latin">Per year</div>
            <div class="nvm-kpi__val nvm-num">฿<span x-text="fmt(metrics.yearlyProjection)"></span></div>
        </div>
    </section>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-mls-ins-h" x-show="insights.length">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Insights</small><h2 id="nvm-mls-ins-h">คำแนะนำ</h2></div></div>
        <div class="nvm-ml-insights">
            <template x-for="(insight, i) in insights" :key="i">
                <div class="nvm-alert" :class="{ 'nvm-alert--ok': insight.type === 'success', 'nvm-alert--info': insight.type === 'info' }">
                    <div class="nvm-alert__txt"><b x-text="insight.title"></b><small x-text="insight.message"></small></div>
                </div>
            </template>
        </div>
    </section>

    <div class="nvm-split">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mls-c1-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Growth</small><h2 id="nvm-mls-c1-h">รายได้แต่ละเดือน</h2></div></div>
            <div class="nvm-ml-chart"><canvas x-ref="incomeChart" aria-label="กราฟรายได้รวมแต่ละเดือน" role="img"></canvas></div>
        </section>
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mls-c2-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Breakdown</small><h2 id="nvm-mls-c2-h">สัดส่วนรายได้</h2></div></div>
            <div class="nvm-ml-chart"><canvas x-ref="breakdownChart" aria-label="กราฟสัดส่วนรายได้แต่ละประเภท" role="img"></canvas></div>
        </section>
    </div>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-mls-t-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Monthly</small><h2 id="nvm-mls-t-h">สรุปรายได้แต่ละเดือน</h2></div></div>
        <div class="nvm-table-wrap">
            <table class="nvm-table">
                <thead>
                    <tr><th>เดือน</th><th class="r">ยอดขายตัวเอง</th><th class="r">ยอดขายทีม</th><th class="r">ส่วนตัว</th><th class="r">Unilevel</th><th class="r">Binary</th><th class="r">รวม</th><th class="r">สะสม</th></tr>
                </thead>
                <tbody>
                    <template x-for="m in monthlyData" :key="m.month">
                        <tr>
                            <td class="nvm-ml-nowrap">เดือน <span class="nvm-num" x-text="m.month"></span></td>
                            <td class="r nvm-num">฿<span x-text="fmt(m.personalSales)"></span></td>
                            <td class="r nvm-num">฿<span x-text="fmt(m.teamSales)"></span></td>
                            <td class="r nvm-num">฿<span x-text="fmt(m.personal)"></span></td>
                            <td class="r nvm-num">฿<span x-text="fmt(m.unilevel)"></span></td>
                            <td class="r nvm-num">฿<span x-text="fmt(m.binary)"></span></td>
                            <td class="r nvm-num"><b>฿<span x-text="fmt(m.total)"></span></b></td>
                            <td class="r nvm-num nvm-ml-mute">฿<span x-text="fmt(m.accumulated)"></span></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr><th colspan="6" class="r">รวมทั้งหมด</th><th class="r nvm-num">฿<span x-text="fmt(metrics.grandTotal)"></span></th><th></th></tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="nvm-card nvm-box nvm-ml-cta">
        <h2>พร้อมเริ่มต้นหรือยัง</h2>
        <p class="nvm-desc">ดูยอดจริงของทีมคุณได้ที่แดชบอร์ด MLM</p>
        <div class="nvm-filter__btns">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>ไปแดชบอร์ด MLM</a>
            @endif
            <button type="button" class="nvm-btn nvm-btn--line" @click="startSimulation()"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>จำลองอีกครั้ง</button>
        </div>
    </section>
</div>
</template>

{{-- คำอธิบายช่องกรอก --}}
<div class="nvm-modal" x-show="tip" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-mls-tip-h" @click.self="tip = null" @keydown.escape.window="tip = null">
    <div class="nvm-card nvm-modal__box nvm-ml-tipbox">
        <h2 id="nvm-mls-tip-h" x-text="tip?.title"></h2>
        <dl class="nvm-kv">
            <dt>คือ</dt><dd x-text="tip?.what"></dd>
            <dt>ใช้เพื่อ</dt><dd x-text="tip?.use"></dd>
            <dt>เคล็ดลับ</dt><dd x-text="tip?.tip"></dd>
        </dl>
        <div class="nvm-filter__btns"><button type="button" class="nvm-btn nvm-btn--gold" @click="tip = null">เข้าใจแล้ว</button></div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/**
 * จำลองรายได้ MLM — Alpine component
 * คำนวณ Unilevel/Binary ผ่าน API ฝั่งเซิร์ฟเวอร์ (logic เดียวกับเครื่องคิดเลขแอดมิน)
 */
function nvmMlIncomeSim(settingsUrl, ranksUrl, previewUrl) {
    const DEFAULTS = {
        global_pv_rate: 1, unilevel_max_depth: 10, binary_pair_commission: 100,
        binary_pairing_type: '1:1', binary_default_left_ratio: 50,
    };
    const TIPS = {
        personal: { title: 'ยอดขายของคุณ', what: 'ยอดขายที่คุณทำเองในแต่ละเดือน', use: 'คิดคอมมิชชั่นส่วนตัวตามเปอร์เซ็นต์ของยศที่เลือก', tip: 'ยอดขายส่วนตัวที่สม่ำเสมอทำให้รายได้ไม่ขึ้นกับทีมอย่างเดียว' },
        team: { title: 'จำนวนสมาชิกในทีม', what: 'จำนวนสมาชิกทั้งหมดในทีม (ทุกชั้น)', use: 'คิดยอดขายรวมของทีม และค่าคอม Unilevel กับ Binary', tip: 'ในการจำลอง ทีมจะโตขึ้นเดือนละ 3% อัตโนมัติ' },
        avg: { title: 'ยอดขายเฉลี่ยต่อคน', what: 'ยอดขายเฉลี่ยของสมาชิกแต่ละคนต่อเดือน', use: 'ยอดขายทีม = จำนวนคน × ยอดขายเฉลี่ย', tip: 'ค่านี้สะท้อนคุณภาพทีม การอบรมทีมช่วยเพิ่มได้' },
        months: { title: 'ระยะเวลา', what: 'จำนวนเดือนที่ต้องการจำลอง (1-60)', use: 'ดูแนวโน้มระยะยาวและวางเป้าหมาย', tip: 'แนะนำ 12-24 เดือนเพื่อให้เห็นภาพชัด' },
    };
    const css = (name, fallback) => (getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim() || fallback;

    return {
        settings: { ...DEFAULTS },
        settingsFallback: false,
        ranks: [],
        ranksError: false,
        selectedRankId: '',
        selectedRank: null,
        inputs: { personalSales: 10000, teamSize: 10, teamAvgSales: 5000, months: 12 },
        inputError: '',
        isRunning: false,
        runId: 0,
        showAnimation: false,
        showResults: false,
        showSettingsHelp: false,
        currentMonth: 0,
        progress: 0,
        currentIncome: { personal: 0, team: 0, total: 0 },
        monthlyData: [],
        metrics: { avgMonthly: 0, bestMonth: 0, growthRate: 0, yearlyProjection: 0, grandTotal: 0 },
        insights: [],
        tip: null,
        charts: [],

        get maxLevel() {
            return this.ranks.reduce((max, r) => Math.max(max, parseInt(r.level, 10) || 0), 0) || '-';
        },

        async init() {
            await Promise.all([this.loadSettings(), this.loadRanks()]);
        },

        /** แปลงค่าเป็นตัวเลข (JSON ของยศเป็นสตริง) */
        num(v, fallback = 0) {
            const n = Number(v);
            return Number.isFinite(n) ? n : fallback;
        },

        async loadSettings() {
            try {
                const res = await fetch(settingsUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (! res.ok || ! data.success || ! data.settings) throw new Error('settings');
                this.settings = { ...DEFAULTS, ...data.settings };
            } catch (e) {
                this.settings = { ...DEFAULTS };
                this.settingsFallback = true;
            }
        },

        async loadRanks() {
            try {
                const res = await fetch(ranksUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                const list = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
                this.ranks = list.map(r => ({ id: r.id, name: r.name, name_th: r.name_th, level: r.level, commission_rate: r.commission_rate, bonus_multiplier: r.bonus_multiplier }));
            } catch (e) {
                this.ranks = [];
                this.ranksError = true;
            }
        },

        rankLabel(rank) {
            return (rank.name_th || rank.name) + ' - ' + this.num(rank.commission_rate).toFixed(2) + '% + ' + this.num(rank.bonus_multiplier, 1).toFixed(2) + 'x โบนัส';
        },

        updateRankInfo() {
            this.selectedRank = this.selectedRankId ? (this.ranks.find(r => String(r.id) === String(this.selectedRankId)) || null) : null;
        },

        openTip(key) {
            this.tip = TIPS[key] || null;
        },

        validInputs() {
            const i = this.inputs;
            for (const k of ['personalSales', 'teamSize', 'teamAvgSales', 'months']) {
                if (! Number.isFinite(Number(i[k])) || Number(i[k]) < 0) {
                    this.inputError = 'กรุณากรอกตัวเลขที่ไม่ติดลบให้ครบทุกช่อง';
                    return false;
                }
            }
            i.months = Math.min(60, Math.max(1, Math.floor(Number(i.months))));
            i.teamSize = Math.floor(Number(i.teamSize));
            this.inputError = '';
            return true;
        },

        async startSimulation() {
            if (this.isRunning || ! this.validInputs()) return;
            const run = ++this.runId;
            this.isRunning = true;
            this.showAnimation = true;
            this.showResults = false;
            this.monthlyData = [];
            this.currentMonth = 0;
            this.progress = 0;

            const total = this.inputs.months;
            for (let month = 1; month <= total; month++) {
                if (run !== this.runId) return; // ถูกรีเซ็ตระหว่างทาง
                this.currentMonth = month;
                await this.simulateMonth(month);
                this.progress = Math.floor((month / total) * 100);
                await new Promise(r => setTimeout(r, total > 24 ? 120 : 300));
            }
            if (run !== this.runId) return;

            this.isRunning = false;
            this.showAnimation = false;
            this.calculateMetrics();
            this.generateInsights();
            this.showResults = true;
            await this.$nextTick();
            this.createCharts();
        },

        /** จำลอง 1 เดือน — Unilevel/Binary คิดที่เซิร์ฟเวอร์ */
        async simulateMonth(month) {
            const s = this.settings;
            const currentTeamSize = Math.floor(this.inputs.teamSize * (1 + month * 0.03));
            const totalTeamSales = currentTeamSize * this.inputs.teamAvgSales;
            const pvRate = this.num(s.global_pv_rate, 1) || 1;
            const personalPv = this.inputs.personalSales * pvRate;
            const teamPv = totalTeamSales * pvRate;

            // API รับตัวคูณ >= 1 และความลึก 1-10 เท่านั้น — นอกช่วงจะได้ 422
            const rankMultiplier = Math.max(1, this.selectedRank ? this.num(this.selectedRank.bonus_multiplier, 1) : 1);
            const rankCommissionRate = this.selectedRank ? this.num(this.selectedRank.commission_rate) : 0;
            const leftRatio = Math.min(100, Math.max(0, this.num(s.binary_default_left_ratio, 50)));
            const leftPv = teamPv * leftRatio / 100;
            const rightPv = teamPv * (100 - leftRatio) / 100;
            const weakerLegPv = Math.min(leftPv, rightPv);
            const strongerLegPv = Math.max(leftPv, rightPv);
            // สูตรเดียวกับ MlmBinaryService::calculatePairsAvailable — 2:1 = min(ขาอ่อน, ขาแข็ง/2)
            const estimatedPairs = Math.max(0, Math.floor(s.binary_pairing_type === '2:1'
                ? Math.min(weakerLegPv, strongerLegPv / 2)
                : weakerLegPv));
            const depth = Math.min(10, Math.max(1, parseInt(s.unilevel_max_depth, 10) || 10));

            let unilevel = 0;
            let binary = 0;
            try {
                const res = await fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        sales_amount: totalTeamSales, team_size: currentTeamSize, member_depth: depth,
                        binary_pairs: estimatedPairs, left_ratio: leftRatio, rank_multiplier: rankMultiplier,
                    }),
                });
                const result = await res.json();
                if (res.ok && result.success) {
                    unilevel = this.num(result.calculation?.unilevel_commission);
                    binary = this.num(result.calculation?.binary_commission);
                } else if (month === 1) {
                    window.showNotification?.('คำนวณค่าคอมทีมไม่สำเร็จ ผลลัพธ์จะแสดงเฉพาะส่วนตัว', 'error');
                }
            } catch (e) {
                if (month === 1) window.showNotification?.('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ ผลลัพธ์จะแสดงเฉพาะส่วนตัว', 'error');
            }

            const personal = (personalPv * rankCommissionRate) / 100;
            const total = personal + unilevel + binary;
            this.currentIncome = { personal, team: unilevel + binary, total };
            const accumulated = this.monthlyData.reduce((sum, m) => sum + m.total, 0) + total;
            this.monthlyData.push({ month, personalSales: this.inputs.personalSales, teamSales: totalTeamSales, teamSize: currentTeamSize, unilevel, binary, personal, total, accumulated });
        },

        calculateMetrics() {
            const n = this.monthlyData.length || 1;
            const total = this.monthlyData.reduce((sum, m) => sum + m.total, 0);
            const avg = total / n;
            const best = this.monthlyData.length ? Math.max(...this.monthlyData.map(m => m.total)) : 0;
            let growthRate = 0;
            if (this.monthlyData.length > 1) {
                const first = this.monthlyData[0].total;
                const last = this.monthlyData[this.monthlyData.length - 1].total;
                growthRate = first > 0 ? ((last - first) / first) * 100 : 0;
            }
            this.metrics = { avgMonthly: avg, bestMonth: best, growthRate, yearlyProjection: avg * 12, grandTotal: total };
        },

        generateInsights() {
            const d = this.monthlyData;
            const out = [];
            const sum = key => d.reduce((s, m) => s + m[key], 0);

            if (! this.selectedRank) {
                out.push({ type: 'warning', title: 'ลองเลือกยศ', message: 'ยศให้คอมมิชชั่นส่วนตัวและตัวคูณโบนัส ทำให้ตัวเลขใกล้ความจริงขึ้น' });
            } else if (this.metrics.avgMonthly > 0) {
                const pct = (sum('personal') / d.length) / this.metrics.avgMonthly * 100;
                if (pct < 20) out.push({ type: 'info', title: 'เพิ่มยอดขายส่วนตัว', message: 'คอมมิชชั่นส่วนตัวคิดเป็น ' + pct.toFixed(0) + '% ของรายได้ ยอดขายส่วนตัวช่วยให้รายได้มั่นคงขึ้น' });
            }

            const firstTeam = d[0]?.teamSize || 0;
            const teamGrowth = d.length > 1 && firstTeam > 0 ? ((d[d.length - 1].teamSize - firstTeam) / firstTeam) * 100 : 0;
            if (teamGrowth > 30) out.push({ type: 'success', title: 'ทีมโตดีมาก', message: 'ทีมโตขึ้น ' + teamGrowth.toFixed(0) + '% ในช่วงที่จำลอง' });
            else if (teamGrowth < 10) out.push({ type: 'info', title: 'โฟกัสการสร้างทีม', message: 'ทีมโตช้า ลองเพิ่มกิจกรรมชวนคนและอบรมสมาชิก' });

            const b = sum('binary');
            const u = sum('unilevel');
            if (b > u * 1.5) out.push({ type: 'success', title: 'Binary ทำงานดี', message: 'ขาซ้าย-ขวาสมดุล ทำให้รายได้ Binary สูง' });
            else if (u > b * 2) out.push({ type: 'info', title: 'ปรับสมดุล Binary', message: 'วางทีมให้สองขาใกล้เคียงกันเพื่อเพิ่มรายได้ Binary' });

            if (d.length > 1 && this.metrics.growthRate < 5) out.push({ type: 'warning', title: 'เร่งการเติบโต', message: 'รายได้โตช้า ลองเพิ่มยอดขายเฉลี่ยของทีม' });
            this.insights = out;
        },

        createCharts() {
            if (typeof Chart === 'undefined') return;
            this.charts.forEach(c => c.destroy());
            const gold = css('--nv-gold-500', '#c9a24a');
            const good = css('--nvm-good', '#2f9e6a');
            const info = css('--nvm-info', '#3b6fd8');
            const ink = css('--nvm-ink-3', '#6b7280');
            const line = css('--nvm-line', 'rgba(0,0,0,.08)');
            const money = v => '฿' + this.fmt(v);

            this.charts = [
                new Chart(this.$refs.incomeChart, {
                    type: 'line',
                    data: { labels: this.monthlyData.map(m => 'เดือน ' + m.month), datasets: [{ label: 'รายได้รวม', data: this.monthlyData.map(m => m.total), borderColor: gold, backgroundColor: gold + '22', tension: .35, fill: true }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => money(c.parsed.y) } } }, scales: { x: { ticks: { color: ink }, grid: { color: line } }, y: { beginAtZero: true, ticks: { color: ink, callback: money }, grid: { color: line } } } },
                }),
                new Chart(this.$refs.breakdownChart, {
                    type: 'doughnut',
                    data: { labels: ['ส่วนตัว', 'Unilevel', 'Binary'], datasets: [{ data: ['personal', 'unilevel', 'binary'].map(k => this.monthlyData.reduce((s, m) => s + m[k], 0)), backgroundColor: [good, info, gold], borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: ink } }, tooltip: { callbacks: { label: c => c.label + ': ' + money(c.parsed) } } } },
                }),
            ];
        },

        resetSimulation() {
            this.runId++;
            this.charts.forEach(c => c.destroy());
            this.charts = [];
            this.monthlyData = [];
            this.isRunning = false;
            this.showAnimation = false;
            this.showResults = false;
            this.currentMonth = 0;
            this.progress = 0;
            this.currentIncome = { personal: 0, team: 0, total: 0 };
            this.metrics = { avgMonthly: 0, bestMonth: 0, growthRate: 0, yearlyProjection: 0, grandTotal: 0 };
            this.insights = [];
        },

        fmt(n) {
            return Math.floor(this.num(n)).toLocaleString('th-TH');
        },
    };
}
</script>
@endpush

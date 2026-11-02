{{--
    เครื่องคำนวณ TPIX Staking (ธีมโนวาเต็ม) — route closure user.mlm.dividend-simulator (คำนวณในเบราว์เซอร์ ไม่แตะเงินจริง)
    ตัวแปร: $pools (pool ที่เปิดอยู่จริง: id/name/apy/days/min) — ว่างได้ ถ้าว่างใช้ค่าตัวอย่างและบอกผู้ใช้ตรงๆ
    🐛 แก้จากหน้าเดิม:
       - APY 30/60/90/120% ฮาร์ดโค้ดและโฆษณา "ผลตอบแทนสูงสุด 120% ต่อปี" ทั้งที่ระบบจริงอ่าน APY จาก tpix_staking_pools
         → ใช้ pool จริง, ถ้าไม่มีบอกว่าเป็นตัวเลขตัวอย่าง
       - ระบบจริงคิดผลตอบแทนแบบไม่ทบต้น (TPIXStakingPool::calculateRewards) แต่หน้านี้เปิดทบต้นเป็นค่าเริ่มต้น → ปิดเป็นค่าเริ่มต้นและอธิบาย
       - select ระยะล็อกได้ค่าเป็นสตริง ทำให้ day === lockPeriod ไม่ตรง → x-model.number
       - ปุ่ม "เริ่มต้น Stake" พาไปหน้า dashboard → ไปหน้า staking จริง
       - รีเซ็ตระหว่างจำลองแล้ว loop ยังวิ่ง และต้องรอ 7 วินาทีทุกครั้ง → คำนวณทันที
       - Chart.js ไม่ปักเวอร์ชัน → 4.4.1
--}}
@extends('layouts.user-nova')

@section('title', 'เครื่องคำนวณ TPIX Staking')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $pools = collect($pools ?? []);
    $isExample = $pools->isEmpty();
    $options = $isExample
        ? collect([[30, 30], [90, 60], [180, 90], [365, 120]])->map(fn ($o, $i) => ['id' => 'ex'.$i, 'name' => 'ตัวอย่าง '.$o[0].' วัน', 'apy' => $o[1], 'days' => $o[0], 'min' => 100])->values()
        : $pools;
    $stakeUrl = $nvR('user.staking.index') ? route('user.staking.index') : null;
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page" x-data="nvmMlStaking(@js($options))">

<section class="nvm-ph" aria-labelledby="nvm-mld-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">TPIX staking calculator</div>
            <h1 id="nvm-mld-h">เครื่องคำนวณ TPIX Staking</h1>
            <p class="nvm-ph__lead">ลองใส่จำนวน TPIX และระยะล็อก ดูผลตอบแทนโดยประมาณ</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($isExample)
    <div class="nvm-alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ตอนนี้ยังไม่มี pool staking ที่เปิดอยู่</b><small>APY ในหน้านี้เป็นตัวเลขตัวอย่างเพื่อดูวิธีคิดเท่านั้น ไม่ใช่อัตราจริงและไม่ใช่การรับประกันผลตอบแทน</small></div>
    </div>
@else
    <div class="nvm-alert nvm-alert--info">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ใช้ APY จาก pool ที่เปิดอยู่จริง</b><small>ผลลัพธ์เป็นการประมาณ APY อาจเปลี่ยนตามที่ระบบประกาศ และมูลค่าเหรียญขึ้นลงได้</small></div>
    </div>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-mld-in-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Inputs</small><h2 id="nvm-mld-in-h">ตั้งค่าการคำนวณ</h2></div></div>
    <div class="nvm-form">
        <div class="nvm-ml-inputs">
            <div class="nvm-field">
                <label class="nvm-label" for="mld-amt">จำนวน TPIX ที่ Stake</label>
                <input id="mld-amt" type="number" inputmode="decimal" min="0" step="100" class="nvm-input nvm-num" x-model.number="amount" @input.debounce.250ms="calc()">
                <span class="nvm-help">ขั้นต่ำ <span class="nvm-num" x-text="fmt(pool?.min || 0)"></span> TPIX</span>
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="mld-pool">{{ $isExample ? 'ระยะเวลาล็อก (ตัวอย่าง)' : 'Pool / ระยะเวลาล็อก' }}</label>
                <select id="mld-pool" class="nvm-input" x-model="poolId" @change="calc()">
                    <template x-for="p in pools" :key="p.id">
                        <option :value="String(p.id)" :selected="String(p.id) === poolId" x-text="p.name + ' · ' + p.days + ' วัน · APY ' + Number(p.apy).toFixed(2) + '%'"></option>
                    </template>
                </select>
            </div>
        </div>
        <div class="nvm-minis nvm-ml-minis3">
            <div class="nvm-mini"><small>APY</small><b class="nvm-num is-in"><span x-text="Number(pool?.apy || 0).toFixed(2)"></span>%</b></div>
            <div class="nvm-mini"><small>ระยะล็อก</small><b class="nvm-num"><span x-text="pool?.days || 0"></span> วัน</b></div>
            <div class="nvm-mini"><small>ผลตอบแทนต่อวัน</small><b class="nvm-num"><span x-text="(Number(pool?.apy || 0) / 365).toFixed(4)"></span>%</b></div>
        </div>
        <label class="nvm-ml-swrow">
            <span><b>คิดแบบทบต้น (สมมติ)</b><small>ระบบจริงจ่ายผลตอบแทนแบบไม่ทบต้น เปิดเพื่อเปรียบเทียบเท่านั้น</small></span>
            <span class="nvm-sw"><input type="checkbox" x-model="compound" @change="calc()" aria-label="คิดแบบทบต้น"><i></i></span>
        </label>
        <p class="nvm-err" x-show="error" x-cloak x-text="error"></p>
    </div>
</section>

<template x-if="result">
<div class="nvm-stack">
    <section class="nvm-kpis" aria-label="ผลการคำนวณ">
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">TPIX ที่ Stake</div><div class="nvm-kpi__en nvm-latin">Principal</div>
            <div class="nvm-kpi__val nvm-num" x-text="fmt(amount)"></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/gift.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">ผลตอบแทนรวม</div><div class="nvm-kpi__en nvm-latin">Rewards</div>
            <div class="nvm-kpi__val nvm-num" x-text="fmt(result.rewards)"></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">ผลตอบแทนตลอดระยะล็อก</div><div class="nvm-kpi__en nvm-latin">Return</div>
            <div class="nvm-kpi__val nvm-num"><span x-text="result.roi.toFixed(2)"></span>%</div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hero.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">TPIX เมื่อครบกำหนด</div><div class="nvm-kpi__en nvm-latin">At maturity</div>
            <div class="nvm-kpi__val nvm-num" x-text="fmt(result.final)"></div>
        </div>
    </section>

    <div class="nvm-split">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mld-c1-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Growth</small><h2 id="nvm-mld-c1-h">TPIX ตามเวลา</h2></div></div>
            <div class="nvm-ml-chart"><canvas x-ref="growth" role="img" aria-label="กราฟ TPIX รวมตามจำนวนวัน"></canvas></div>
        </section>
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mld-c2-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Split</small><h2 id="nvm-mld-c2-h">เงินต้นกับผลตอบแทน</h2></div></div>
            <div class="nvm-ml-chart"><canvas x-ref="split" role="img" aria-label="กราฟสัดส่วนเงินต้นและผลตอบแทน"></canvas></div>
        </section>
    </div>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-mld-t-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Checkpoints</small><h2 id="nvm-mld-t-h">ผลตอบแทนตามช่วงเวลา</h2></div></div>
        <div class="nvm-table-wrap">
            <table class="nvm-table">
                <thead><tr><th>วันที่</th><th class="r">TPIX ที่ Stake</th><th class="r">ผลตอบแทนวันนั้น</th><th class="r">ผลตอบแทนสะสม</th><th class="r">TPIX รวม</th></tr></thead>
                <tbody>
                    <template x-for="row in result.rows" :key="row.day">
                        <tr>
                            <td class="nvm-ml-nowrap">วันที่ <span class="nvm-num" x-text="row.day"></span></td>
                            <td class="r nvm-num" x-text="fmt(row.stake)"></td>
                            <td class="r nvm-num" x-text="fmt(row.daily, 4)"></td>
                            <td class="r nvm-num nvm-ml-in" x-text="fmt(row.rewards)"></td>
                            <td class="r nvm-num"><b x-text="fmt(row.total)"></b></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>

    @if($stakeUrl)
        <section class="nvm-card nvm-box nvm-ml-cta">
            <h2>{{ $isExample ? 'ดูหน้า TPIX Staking' : 'พร้อม Stake จริงแล้วหรือยัง' }}</h2>
            <p class="nvm-desc">ตรวจเงื่อนไขของแต่ละ pool ให้ครบก่อนตัดสินใจ</p>
            <div class="nvm-filter__btns"><a class="nvm-btn nvm-btn--gold" href="{{ $stakeUrl }}"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ไปหน้า TPIX Staking</a></div>
        </section>
    @endif
</div>
</template>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/**
 * เครื่องคำนวณ TPIX Staking — Alpine + Chart.js
 * แบบไม่ทบต้น: ผลตอบแทน = เงินต้น × APY × วัน / 36500 (ตรงกับ TPIXStakingPool::calculateRewards)
 */
function nvmMlStaking(pools) {
    const css = (name, fallback) => (getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim() || fallback;
    const charts = {};
    const CHECKPOINTS = [1, 7, 14, 30, 60, 90, 180, 270, 365, 730];

    return {
        pools,
        poolId: pools.length ? String(pools[pools.length - 1].id) : '',
        amount: 10000,
        compound: false,
        error: '',
        result: null,

        get pool() { return this.pools.find(p => String(p.id) === String(this.poolId)) || null; },

        init() { this.calc(); },

        calc() {
            const p = this.pool;
            const amount = Number(this.amount);
            if (! p) { this.result = null; return; }
            if (! Number.isFinite(amount) || amount <= 0) { this.error = 'กรุณากรอกจำนวน TPIX มากกว่า 0'; this.result = null; return; }
            const min = Number(p.min) || 0;
            if (amount < min) { this.error = 'จำนวนขั้นต่ำของ pool นี้คือ ' + this.fmt(min) + ' TPIX'; this.result = null; return; }
            this.error = '';

            const days = Math.max(1, parseInt(p.days, 10) || 1);
            const daily = (Number(p.apy) || 0) / 365 / 100;
            let stake = amount;
            let rewards = 0;
            const rows = [];
            const series = [];
            const every = Math.max(1, Math.ceil(days / 60));
            for (let day = 1; day <= days; day++) {
                const r = stake * daily;
                rewards += r;
                if (this.compound) stake += r;
                const total = this.compound ? stake : amount + rewards;
                if (CHECKPOINTS.includes(day) || day === days) rows.push({ day, stake, daily: r, rewards, total });
                if (day % every === 0 || day === days) series.push({ day, total });
            }
            const final = this.compound ? stake : amount + rewards;
            this.result = { rewards, final, roi: rewards / amount * 100, rows, series };
            this.$nextTick(() => this.draw());
        },

        draw() {
            if (typeof Chart === 'undefined' || ! this.result || ! this.$refs.growth) return;
            const gold = css('--nv-gold-500', '#c9a24a');
            const good = css('--nvm-good', '#2f9e6a');
            const info = css('--nvm-info', '#3b6fd8');
            const ink = css('--nvm-ink-3', '#6b7280');
            const line = css('--nvm-line', 'rgba(0,0,0,.08)');
            const s = this.result.series;
            charts.growth?.destroy();
            charts.split?.destroy();
            charts.growth = new Chart(this.$refs.growth, {
                type: 'line',
                data: { labels: s.map(x => 'วัน ' + x.day), datasets: [{ data: s.map(x => x.total), borderColor: gold, backgroundColor: gold + '22', tension: .3, fill: true, pointRadius: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => this.fmt(c.parsed.y) + ' TPIX' } } }, scales: { x: { ticks: { color: ink, maxTicksLimit: 8 }, grid: { display: false } }, y: { ticks: { color: ink, callback: v => this.fmt(v) }, grid: { color: line } } } },
            });
            charts.split = new Chart(this.$refs.split, {
                type: 'doughnut',
                data: { labels: ['เงินต้น', 'ผลตอบแทน'], datasets: [{ data: [Number(this.amount), this.result.rewards], backgroundColor: [info, good], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: ink } }, tooltip: { callbacks: { label: c => c.label + ': ' + this.fmt(c.parsed) + ' TPIX' } } } },
            });
        },

        fmt(n, digits = 2) {
            return (Number(n) || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: digits });
        },
    };
}
</script>
@endpush

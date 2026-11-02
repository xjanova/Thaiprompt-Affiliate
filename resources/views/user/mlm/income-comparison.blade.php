{{--
    เปรียบเทียบแผนรายได้ (ธีมโนวาเต็ม) — route closure user.mlm.income-comparison (เครื่องมือการตลาด ไม่แตะเงินจริง)
    ข้อมูล: GET user.mlm.settings · ตัวอย่างแผน 3 แบบคงที่ คำนวณคร่าวๆ ในเบราว์เซอร์
    🐛 แก้จากหน้าเดิม:
       - "เรื่องราวความสำเร็จ" คุณสมชาย/สมหญิง/สมศักดิ์ เป็นคำรับรองรายได้ที่แต่งขึ้น → เอาออก แล้วใส่คำเตือนว่าเป็นการประมาณ
       - โหลด settings ไม่สำเร็จแล้วการ์ดเป็น 0 ทั้งหมด กราฟไม่ขึ้น → ใช้ค่าเริ่มต้นแทน
       - กล่องรายละเอียดซ่อนด้วยคลาส Tailwind "hidden" ที่ธีมนี้ไม่มี → เปิด/ปิดด้วย Alpine
       - "ROI" หารศูนย์ได้ NaN และจริงๆ คือการเติบโตจากเดือนแรก → เปลี่ยนชื่อและกันหารศูนย์
       - Chart.js ไม่ปักเวอร์ชัน → 4.4.1
--}}
@extends('layouts.user-nova')

@section('title', 'เปรียบเทียบแผนรายได้')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    // คีย์ต้องตรงกับ SCENARIOS ใน JS
    $plans = [
        ['key' => 'starter', 'name' => 'แผนเริ่มต้น', 'desc' => 'เหมาะกับมือใหม่', 'img' => 'images/nova/deco/lotus.webp', 'sales' => 5000, 'team' => 5, 'avg' => 3000, 'pick' => false],
        ['key' => 'growth', 'name' => 'แผนเติบโต', 'desc' => 'สร้างรายได้มั่นคง', 'img' => 'images/nova/deco/lantern.webp', 'sales' => 20000, 'team' => 20, 'avg' => 5000, 'pick' => true],
        ['key' => 'elite', 'name' => 'แผนทีมใหญ่', 'desc' => 'ทีมขนาดใหญ่และยอดขายสูง', 'img' => 'images/nova/deco/crystal.webp', 'sales' => 50000, 'team' => 50, 'avg' => 5000, 'pick' => false],
    ];
    $scenarioData = [];
    foreach ($plans as $p) {
        $scenarioData[$p['key']] = ['name' => $p['name'], 'personalSales' => $p['sales'], 'teamSize' => $p['team'], 'teamAvgSales' => $p['avg']];
    }
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page" x-data="nvmMlCompare(@js(route('user.mlm.settings')), @js($scenarioData))">

<section class="nvm-ph" aria-labelledby="nvm-mlcp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Income comparison</div>
            <h1 id="nvm-mlcp-h">เปรียบเทียบแผนรายได้</h1>
            <p class="nvm-ph__lead">ดูตัวอย่าง 3 ระดับว่าการขายและขนาดทีมให้รายได้ต่างกันแค่ไหน</p>
            @if($nvR('user.mlm.income-simulator'))
                <div class="nvm-hero__actions"><a class="nvm-btn nvm-btn--gold" href="{{ route('user.mlm.income-simulator') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>จำลองด้วยตัวเลขของคุณ</a></div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-alert">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt"><b>ตัวเลขเป็นการประมาณคร่าวๆ ไม่ใช่การรับประกันรายได้</b><small>คิดจากค่าตั้งของระบบกับยอดสมมติ กำไรขายปลีกสมมติที่ 20% ของยอดขายตัวเอง รายได้จริงขึ้นกับยอดขายจริง</small></div>
</div>
<p class="nvm-err" x-show="settingsFallback" x-cloak>โหลดค่าตั้งของระบบไม่สำเร็จ กำลังใช้ค่าเริ่มต้นแทน</p>

<section class="nvm-ml-plans" aria-label="แผนตัวอย่าง">
    @foreach($plans as $p)
        <article class="nvm-card nvm-box nvm-ml-plan {{ $p['pick'] ? 'is-pick' : '' }}">
            @if($p['pick'])<span class="nvm-chip nvm-chip--gold nvm-ml-plan__tag">แนะนำ</span>@endif
            <img src="{{ asset($p['img']) }}" alt="" width="72" height="72" loading="lazy">
            <h2>{{ $p['name'] }}</h2>
            <p class="nvm-desc">{{ $p['desc'] }}</p>
            <dl class="nvm-kv">
                <dt>ยอดขายตัวเอง/เดือน</dt><dd class="nvm-num">฿{{ number_format($p['sales']) }}</dd>
                <dt>จำนวนทีม</dt><dd class="nvm-num">{{ number_format($p['team']) }} คน</dd>
                <dt>ยอดขายทีม/เดือน</dt><dd class="nvm-num">฿{{ number_format($p['team'] * $p['avg']) }}</dd>
            </dl>
            <div class="nvm-ml-plan__sum">
                <small>รายได้โดยประมาณ/เดือน</small>
                <b class="nvm-num">฿<span x-text="fmt(results.{{ $p['key'] }}?.total)"></span></b>
                <small>≈ ฿<span class="nvm-num" x-text="fmt(results.{{ $p['key'] }}?.yearly)"></span> ต่อปี</small>
            </div>
            <button type="button" class="nvm-btn {{ $p['pick'] ? 'nvm-btn--gold' : 'nvm-btn--line' }} nvm-btn--block" @click="open('{{ $p['key'] }}')">ดูรายละเอียด</button>
        </article>
    @endforeach
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-mlcp-c-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Compare</small><h2 id="nvm-mlcp-c-h">รายได้ต่อเดือนของแต่ละแผน</h2></div></div>
    <div class="nvm-ml-chart"><canvas x-ref="compare" role="img" aria-label="กราฟแท่งเปรียบเทียบรายได้ต่อเดือนของ 3 แผน"></canvas></div>
</section>

<section class="nvm-card nvm-box" x-show="active" x-cloak x-ref="detail" aria-labelledby="nvm-mlcp-d-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Details</small><h2 id="nvm-mlcp-d-h">รายละเอียด: <span x-text="active ? scenarios[active].name : ''"></span></h2></div>
        <button type="button" class="nvm-btn nvm-btn--ghost nvm-btn--sm" @click="close()"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg>ปิด</button>
    </div>
    <div class="nvm-split">
        <div>
            <h3 class="nvm-ml-h3">สัดส่วนรายได้</h3>
            <div class="nvm-ml-chart"><canvas x-ref="pie" role="img" aria-label="กราฟสัดส่วนรายได้"></canvas></div>
        </div>
        <div>
            <h3 class="nvm-ml-h3">ถ้าทีมโตเดือนละ 5% ใน 12 เดือน</h3>
            <div class="nvm-ml-chart"><canvas x-ref="growth" role="img" aria-label="กราฟรายได้ 12 เดือน"></canvas></div>
        </div>
    </div>
    <div class="nvm-minis nvm-gap-t">
        <div class="nvm-mini"><small>เดือนแรก</small><b class="nvm-num">฿<span x-text="fmt(detail.m1)"></span></b></div>
        <div class="nvm-mini"><small>เดือนที่ 6</small><b class="nvm-num">฿<span x-text="fmt(detail.m6)"></span></b></div>
        <div class="nvm-mini"><small>เดือนที่ 12</small><b class="nvm-num">฿<span x-text="fmt(detail.m12)"></span></b></div>
        <div class="nvm-mini"><small>เติบโตจากเดือนแรก</small><b class="nvm-num is-in"><span x-text="detail.growth"></span>%</b></div>
    </div>
</section>

@if($nvR('user.mlm.income-simulator'))
    <section class="nvm-card nvm-box nvm-ml-cta">
        <h2>อยากรู้ตัวเลขของคุณเอง</h2>
        <p class="nvm-desc">ใส่ยอดขาย ขนาดทีม และยศของคุณ แล้วดูผลทีละเดือน</p>
        <div class="nvm-filter__btns"><a class="nvm-btn nvm-btn--gold" href="{{ route('user.mlm.income-simulator') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ไปหน้าจำลองรายได้</a></div>
    </section>
@endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/**
 * เปรียบเทียบแผนรายได้ตัวอย่าง — Alpine + Chart.js (ตัวเลขประมาณการเท่านั้น)
 */
function nvmMlCompare(settingsUrl, scenarios) {
    const DEFAULTS = { global_pv_rate: 1, unilevel_levels: [], binary_pair_commission: 100 };
    const css = (name, fallback) => (getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim() || fallback;
    // เก็บกราฟไว้นอก state ของ Alpine
    const charts = {};

    return {
        scenarios,
        settings: { ...DEFAULTS },
        settingsFallback: false,
        results: {},
        active: null,
        detail: { m1: 0, m6: 0, m12: 0, growth: 0 },

        async init() {
            try {
                const res = await fetch(settingsUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (! res.ok || ! data.success || ! data.settings) throw new Error('settings');
                this.settings = { ...DEFAULTS, ...data.settings };
            } catch (e) {
                this.settingsFallback = true;
            }
            Object.keys(this.scenarios).forEach(k => { this.results[k] = this.calc(this.scenarios[k]); });
            this.$nextTick(() => this.drawCompare());
        },

        calc(s) {
            const teamSales = s.teamSize * s.teamAvgSales;
            const pvRate = Number(this.settings.global_pv_rate) || 1;
            // PV = ยอดขาย × global_pv_rate (สูตรเดียวกับ MlmPvService ที่คิด PV จริง)
            const teamPv = teamSales * pvRate;
            let levels = this.settings.unilevel_levels;
            if (typeof levels === 'string') { try { levels = JSON.parse(levels); } catch (e) { levels = []; } }
            const unilevel = (Array.isArray(levels) ? levels : []).reduce((sum, l) => sum + teamPv * (Number(l.percentage) || 0) / 100, 0);
            const pairCommission = Number(this.settings.binary_pair_commission) || 0;
            const binary = Math.min(Math.floor(teamPv * 0.4) * pairCommission, teamPv * 0.1);
            const personal = s.personalSales * 0.2;
            const total = personal + unilevel + binary;
            return { personal, unilevel, binary, total, yearly: total * 12 };
        },

        colors() {
            return { gold: css('--nv-gold-500', '#c9a24a'), good: css('--nvm-good', '#2f9e6a'), info: css('--nvm-info', '#3b6fd8'), ink: css('--nvm-ink-3', '#6b7280'), line: css('--nvm-line', 'rgba(0,0,0,.08)') };
        },

        drawCompare() {
            if (typeof Chart === 'undefined') return;
            const c = this.colors();
            const keys = Object.keys(this.scenarios);
            charts.compare?.destroy();
            charts.compare = new Chart(this.$refs.compare, {
                type: 'bar',
                data: { labels: keys.map(k => this.scenarios[k].name), datasets: [{ data: keys.map(k => this.results[k].total), backgroundColor: [c.good, c.gold, c.info], borderRadius: 10 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: x => '฿' + this.fmt(x.parsed.y) } } }, scales: { x: { ticks: { color: c.ink }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: c.ink, callback: v => '฿' + this.fmt(v) }, grid: { color: c.line } } } },
            });
        },

        async open(key) {
            const r = this.results[key];
            if (! r) return;
            this.active = key;
            const growth = Array.from({ length: 12 }, (_, i) => r.total * (1 + (i + 1) * 0.05));
            this.detail = { m1: growth[0], m6: growth[5], m12: growth[11], growth: growth[0] > 0 ? Math.round((growth[11] - growth[0]) / growth[0] * 100) : 0 };
            await this.$nextTick();
            if (typeof Chart !== 'undefined') {
                const c = this.colors();
                charts.pie?.destroy();
                charts.growth?.destroy();
                charts.pie = new Chart(this.$refs.pie, {
                    type: 'doughnut',
                    data: { labels: ['กำไรขายปลีก', 'Unilevel', 'Binary'], datasets: [{ data: [r.personal, r.unilevel, r.binary], backgroundColor: [c.good, c.info, c.gold], borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: c.ink } }, tooltip: { callbacks: { label: x => x.label + ': ฿' + this.fmt(x.parsed) } } } },
                });
                charts.growth = new Chart(this.$refs.growth, {
                    type: 'line',
                    data: { labels: growth.map((_, i) => 'เดือน ' + (i + 1)), datasets: [{ data: growth, borderColor: c.gold, backgroundColor: c.gold + '22', tension: .35, fill: true }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: x => '฿' + this.fmt(x.parsed.y) } } }, scales: { x: { ticks: { color: c.ink }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: c.ink, callback: v => '฿' + this.fmt(v) }, grid: { color: c.line } } } },
                });
            }
            this.$refs.detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        close() {
            this.active = null;
            charts.pie?.destroy();
            charts.growth?.destroy();
        },

        fmt(n) { return Math.floor(Number(n) || 0).toLocaleString('th-TH'); },
    };
}
</script>
@endpush

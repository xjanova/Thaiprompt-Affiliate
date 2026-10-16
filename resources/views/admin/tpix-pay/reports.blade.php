@extends('layouts.admin-v4')

@section('title', 'รายงาน TPIX')

@push('styles')
<style>
    /* จัดวางเฉพาะหน้านี้ (สีทั้งหมดมาจากตัวแปรธีม V4 → สว่าง/มืดอ่านได้ทั้งคู่) */
    .trp-wrap { max-width: 1180px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
    .trp-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    .trp-grid-2 { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 460px), 1fr)); }
    .trp-k { font-size: .8rem; color: var(--ink2); }
    .trp-v { font-size: 1.35rem; font-weight: 800; color: var(--ink); line-height: 1.3; }
    .trp-s { font-size: .78rem; color: var(--ink2); }
    .trp-h { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--ink); }
    .trp-chart { position: relative; height: 280px; }
    .trp-chart-sm { position: relative; height: 240px; }
    .trp-table { width: 100%; border-collapse: collapse; font-size: .85rem; color: var(--ink); }
    .trp-table th { padding: 8px; text-align: left; color: var(--ink2); font-weight: 600; }
    .trp-table td { padding: 8px; border-top: 1px solid color-mix(in srgb, var(--ink2) 25%, transparent); vertical-align: top; }
    .trp-r { text-align: right !important; }
    .trp-seg { display: inline-flex; border-radius: 999px; overflow: hidden; border: 1px solid color-mix(in srgb, var(--ink2) 35%, transparent); }
    .trp-seg button { padding: 5px 14px; font-size: .8rem; font-weight: 700; color: var(--ink2); background: transparent; border: 0; cursor: pointer; }
    .trp-seg button[aria-pressed="true"] { background: var(--accent1); color: #fff; }
    .trp-empty { padding: 28px 8px; text-align: center; color: var(--ink2); font-size: .85rem; }
    @media (max-width: 640px) {
        .trp-chart { height: 230px; }
        .trp-v { font-size: 1.15rem; }
    }
</style>
@endpush

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: รายงาน TPIX (ธีม V4)
     ข้อมูลจาก Admin\TpixPayAdminController@reports → TpixPayReportService::report() (แคช 60 วินาที)
       $report = [kpi, daily, price, statuses, top_payers, action_items, generated_at], $exportFrom, $exportTo
     ════════════════════════════════════════════════════════════ --}}
@php
    $k = $report['kpi'];
    $tpx = fn ($v, $d = 2) => \App\Services\TpixPay\TpixAmount::display((string) $v, $d);
    $statusNames = [
        'pending' => 'รอโอน', 'confirming' => 'รอยืนยันบนเชน', 'paid' => 'ชำระแล้ว', 'credited_wallet' => 'เติมเข้ากระเป๋า',
        'late' => 'โอนหลังหมดเวลา', 'expired' => 'หมดเวลา', 'cancelled' => 'ยกเลิก', 'failed' => 'ไม่ผ่าน',
    ];
    $hasPrice = count($report['price']['labels']) > 0;
    $hasDaily = array_sum($report['daily']['count']) > 0;
    $chartPayload = ['daily' => $report['daily'], 'price' => $report['price'], 'statuses' => $report['statuses'], 'statusNames' => $statusNames];
@endphp
<div class="trp-wrap">

    {{-- ── หัวหน้า ── --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
            <div class="trp-s" style="letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">หลังบ้าน · การเงิน · TPIX Chain {{ config('tpix_pay.chain_id') }}</div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">📊 รายงาน TPIX</h1>
            <div class="trp-s">อัปเดต {{ \Illuminate\Support\Carbon::parse($report['generated_at'])->format('d/m/Y H:i:s') }} · ตัวเลขแคชไว้ 1 นาที</div>
        </div>
        <a href="{{ route('admin.tpix-pay.index') }}" class="tp-btn">← รายการชำระ / ตั้งค่า</a>
    </div>

    {{-- ── ตัวเลขสรุป ── --}}
    <div class="trp-grid">
        <div class="tp-card">
            <div class="trp-k">รับแล้ววันนี้</div>
            <div class="trp-v tp-num">฿{{ number_format($k['today']['thb'], 2) }}</div>
            <div class="trp-s tp-num">{{ $tpx($k['today']['tpix']) }} TPIX · {{ number_format($k['today']['count']) }} รายการ</div>
        </div>
        <div class="tp-card">
            <div class="trp-k">รับแล้ว 7 วัน</div>
            <div class="trp-v tp-num">฿{{ number_format($k['d7']['thb'], 2) }}</div>
            <div class="trp-s tp-num">{{ $tpx($k['d7']['tpix']) }} TPIX · {{ number_format($k['d7']['count']) }} รายการ</div>
        </div>
        <div class="tp-card">
            <div class="trp-k">รับแล้ว 30 วัน</div>
            <div class="trp-v tp-num" data-kpi="thb30">฿{{ number_format($k['d30']['thb'], 2) }}</div>
            <div class="trp-s tp-num">{{ $tpx($k['d30']['tpix']) }} TPIX · {{ number_format($k['d30']['count']) }} รายการ</div>
        </div>
        <div class="tp-card">
            <div class="trp-k">ยอดเฉลี่ยต่อรายการ (30 วัน)</div>
            <div class="trp-v tp-num">฿{{ number_format($k['avg_ticket_thb'], 2) }}</div>
            <div class="trp-s">จากรายการที่ได้รับเงินแล้ว</div>
        </div>
        <div class="tp-card">
            <div class="trp-k">อัตราแปลง (30 วัน)</div>
            <div class="trp-v tp-num">{{ $k['conversion_pct'] === null ? '—' : number_format($k['conversion_pct'], 1).'%' }}</div>
            <div class="trp-s tp-num">ได้รับเงิน {{ number_format($k['quotes_settled']) }} จาก {{ number_format($k['quotes_issued']) }} ใบที่ออก</div>
        </div>
        <div class="tp-card">
            <div class="trp-k">ใบเสนอยอดที่ยังเปิด</div>
            <div class="trp-v tp-num">{{ number_format($k['open_count']) }}</div>
            <div class="trp-s">รอโอน / รอยืนยันบนเชน (ยังไม่หมดเวลา)</div>
        </div>
        <a href="{{ route('admin.tpix-pay.index', ['status' => 'late']) }}" class="tp-card" style="text-decoration:none;{{ $k['review_count'] > 0 ? 'border-left:4px solid #c05621;' : '' }}">
            <div class="trp-k">รอแอดมินอนุมัติ</div>
            <div class="trp-v tp-num">{{ number_format($k['review_count']) }}</div>
            <div class="trp-s">เหรียญเข้าแล้วแต่ติดเพดาน → ตรวจในรายการ</div>
        </a>
        <a href="{{ route('admin.tpix-pay.index', ['status' => 'credited_wallet']) }}" class="tp-card" style="text-decoration:none;">
            <div class="trp-k">ได้เหรียญแต่เติมเข้ากระเป๋าแทน (30 วัน)</div>
            <div class="trp-v tp-num">฿{{ number_format($k['credited_wallet']['thb'], 2) }}</div>
            <div class="trp-s tp-num">{{ $tpx($k['credited_wallet']['tpix']) }} TPIX · {{ number_format($k['credited_wallet']['count']) }} รายการ</div>
        </a>
    </div>

    {{-- ── ยอดรับรายวัน ── --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <h2 class="trp-h" style="flex:1;min-width:180px;">ยอดรับรายวัน 30 วัน (แยกตามประเภท)</h2>
            <div class="trp-seg" role="group" aria-label="หน่วยของกราฟ">
                <button type="button" data-unit="thb" aria-pressed="true">บาท</button>
                <button type="button" data-unit="tpix" aria-pressed="false">TPIX</button>
            </div>
        </div>
        @if($hasDaily)
            <div class="trp-chart"><canvas id="trpDaily" aria-label="กราฟยอดรับรายวัน"></canvas></div>
        @else
            <div class="trp-empty">ยังไม่มีรายการที่ได้รับเงินใน 30 วัน</div>
        @endif
    </div>

    <div class="trp-grid-2">
        {{-- ── ราคา ── --}}
        <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
            <h2 class="trp-h">ราคา TPIX 7 วัน <span class="trp-s" style="font-weight:400;">· เส้นประ = ค่าเฉลี่ย {{ \App\Services\TpixPay\TpixPayReportService::TWAP_MINUTES }} นาที</span></h2>
            @if($hasPrice)
                <div class="trp-chart"><canvas id="trpPrice" aria-label="กราฟราคา TPIX"></canvas></div>
            @else
                <div class="trp-empty">ยังไม่มีข้อมูลราคาย้อนหลัง (ตัวสแกนบันทึกราคานาทีละครั้ง)</div>
            @endif
        </div>

        {{-- ── สภาพคล่อง ── --}}
        <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
            <h2 class="trp-h">สภาพคล่องพูล DEX 7 วัน</h2>
            @if($hasPrice)
                <div class="trp-chart"><canvas id="trpPool" aria-label="กราฟสภาพคล่องพูล"></canvas></div>
            @else
                <div class="trp-empty">ยังไม่มีข้อมูลสภาพคล่องย้อนหลัง</div>
            @endif
        </div>
    </div>

    <div class="trp-grid-2">
        {{-- ── สถานะ ── --}}
        <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
            <h2 class="trp-h">สถานะใบเสนอยอด (ออกใน 30 วัน)</h2>
            @if(count($report['statuses']) > 0)
                <div class="trp-chart-sm"><canvas id="trpStatus" aria-label="กราฟสัดส่วนสถานะ"></canvas></div>
            @else
                <div class="trp-empty">ยังไม่มีใบเสนอยอดใน 30 วัน</div>
            @endif
        </div>

        {{-- ── ส่งออก CSV ── --}}
        <form method="GET" action="{{ route('admin.tpix-pay.export') }}" class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
            <h2 class="trp-h">ส่งออกรายการชำระ (CSV)</h2>
            <div class="trp-s">ตามวันที่ออกใบ · เปิดใน Excel ได้ภาษาไทย · ครั้งละไม่เกิน 366 วัน</div>
            @if($errors->any())
                <div style="color:#c53030;font-size:.82rem;">{{ $errors->first() }}</div>
            @endif
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
                <label class="trp-s">ตั้งแต่วันที่
                    <input type="date" name="from" required value="{{ old('from', $exportFrom) }}" class="tp-input" style="width:100%;margin-top:4px;">
                </label>
                <label class="trp-s">ถึงวันที่
                    <input type="date" name="to" required value="{{ old('to', $exportTo) }}" class="tp-input" style="width:100%;margin-top:4px;">
                </label>
                <label class="trp-s">สถานะ
                    <select name="status" class="tp-input" style="width:100%;margin-top:4px;">
                        <option value="">ทุกสถานะ</option>
                        <option value="settled">ได้รับเงินแล้วทั้งหมด</option>
                        @foreach($statusNames as $val => $name)
                            <option value="{{ $val }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div><button type="submit" class="tp-btn tp-btn-primary">⬇️ ดาวน์โหลด CSV</button></div>
        </form>
    </div>

    {{-- ── ผู้จ่ายสูงสุด ── --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
        <h2 class="trp-h">ผู้จ่ายสูงสุด 30 วัน (ตามมูลค่าบาท)</h2>
        <div style="overflow-x:auto;">
            <table class="trp-table">
                <thead>
                    <tr><th>#</th><th>ผู้ใช้</th><th class="trp-r">รายการ</th><th class="trp-r">บาท</th><th class="trp-r">TPIX</th><th>จ่ายล่าสุด</th></tr>
                </thead>
                <tbody>
                    @forelse($report['top_payers'] as $i => $row)
                        <tr>
                            <td class="tp-num">{{ $i + 1 }}</td>
                            <td>{{ $row['name'] }}<div class="trp-s">#{{ $row['user_id'] }}{{ $row['email'] ? ' · '.$row['email'] : '' }}</div></td>
                            <td class="tp-num trp-r">{{ number_format($row['count']) }}</td>
                            <td class="tp-num trp-r">฿{{ number_format($row['thb'], 2) }}</td>
                            <td class="tp-num trp-r">{{ $tpx($row['tpix'], 4) }}</td>
                            <td class="trp-s" style="white-space:nowrap;">{{ $row['last_paid'] ? \Illuminate\Support\Carbon::parse($row['last_paid'])->format('d/m H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="trp-empty">ยังไม่มีผู้จ่ายใน 30 วัน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── รายการรอจัดการ ── --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
        <h2 class="trp-h">รายการที่ต้องจัดการ <span class="trp-s" style="font-weight:400;">· โอนหลังหมดเวลา / ค้างยืนยันบนเชนเกิน 10 นาที</span></h2>
        <div style="overflow-x:auto;">
            <table class="trp-table">
                <thead>
                    <tr><th>รายการ</th><th>ผู้ใช้</th><th class="trp-r">บาท</th><th class="trp-r">TPIX</th><th>สถานะ</th><th>อัปเดต</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($report['action_items'] as $item)
                        <tr>
                            <td class="tp-num" style="font-weight:700;">{{ $item['reference'] }}</td>
                            <td>{{ $item['user_name'] ?? '—' }}<div class="trp-s">#{{ $item['user_id'] }}</div></td>
                            <td class="tp-num trp-r">฿{{ number_format($item['amount_thb'], 2) }}</td>
                            <td class="tp-num trp-r">{{ $tpx($item['amount_tpix'], 8) }}</td>
                            <td>
                                {{ $statusNames[$item['status']] ?? $item['status'] }}
                                @if($item['needs_review'])<div class="trp-s" style="color:#c05621;font-weight:700;">รออนุมัติเติมเข้ากระเป๋า</div>@endif
                            </td>
                            <td class="trp-s" style="white-space:nowrap;">{{ $item['updated_at'] }}</td>
                            <td><a href="{{ route('admin.tpix-pay.index', ['status' => $item['status'], 'q' => $item['reference']]) }}" class="tp-btn tp-btn-sm">เปิดในรายการ</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="trp-empty">ไม่มีรายการค้าง 👍</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="application/json" id="trpData">@json($chartPayload)</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    // กราฟรายงาน TPIX — สีแกน/ตัวอักษรอ่านจากตัวแปรธีม V4 และวาดใหม่เมื่อสลับสว่าง/มืด
    const el = document.getElementById('trpData');
    if (!el || typeof Chart === 'undefined') return;
    const D = JSON.parse(el.textContent);
    const charts = {};
    let unit = 'thb';

    const css = (name, fb) => (getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim() || fb;
    const fmt = (v, d) => Number(v).toLocaleString('th-TH', { maximumFractionDigits: d });
    // canvas ไม่รับ color-mix() ทุกเบราว์เซอร์ → แปลง #hex เป็น rgba เอง
    const alpha = (hex, a) => {
        const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(hex);
        if (!m) return 'rgba(128,128,128,' + a + ')';
        let h = m[1];
        if (h.length === 3) h = h.split('').map((x) => x + x).join('');
        const n = parseInt(h, 16);
        return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + a + ')';
    };
    const palette = () => ({
        ink: css('--ink', '#544c40'),
        ink2: css('--ink2', '#9a8f7c'),
        grid: alpha(css('--ink2', '#9a8f7c'), .22),
        a1: css('--accent1', '#e6b347'),
        a2: css('--accent2', '#d98e3f'),
        teal: '#2c9a9b',
        blue: '#4a7fd4',
    });
    const statusColors = {
        pending: '#d69e2e', confirming: '#4a7fd4', paid: '#38a169', credited_wallet: '#2c9a9b',
        late: '#dd6b20', expired: '#a0aec0', cancelled: '#718096', failed: '#e53e3e',
    };

    function baseOpts(c) {
        return {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { labels: { color: c.ink, boxWidth: 12 } } },
            scales: {
                x: { ticks: { color: c.ink2, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { color: c.grid } },
                y: { ticks: { color: c.ink2 }, grid: { color: c.grid }, beginAtZero: true },
            },
        };
    }

    function dailyDatasets(c) {
        const src = D.daily[unit];
        return [
            { label: 'คำสั่งซื้อ', data: src.order, backgroundColor: c.a1, stack: 's' },
            { label: 'เติมเงินเข้ากระเป๋า', data: src.wallet_topup, backgroundColor: c.teal, stack: 's' },
        ];
    }

    function render() {
        Object.values(charts).forEach((ch) => ch.destroy());
        const c = palette();

        const daily = document.getElementById('trpDaily');
        if (daily) {
            const o = baseOpts(c);
            o.scales.x.stacked = true; o.scales.y.stacked = true;
            o.plugins.tooltip = { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + (unit === 'thb' ? '฿' + fmt(ctx.raw, 2) : fmt(ctx.raw, 4) + ' TPIX') } };
            charts.daily = new Chart(daily, { type: 'bar', data: { labels: D.daily.labels, datasets: dailyDatasets(c) }, options: o });
        }

        const price = document.getElementById('trpPrice');
        if (price) {
            const o = baseOpts(c);
            o.elements = { point: { radius: 0 } };
            o.scales.y = { position: 'left', beginAtZero: false, ticks: { color: c.ink2 }, grid: { color: c.grid }, title: { display: true, text: 'บาท', color: c.ink2 } };
            o.scales.y1 = { position: 'right', beginAtZero: false, ticks: { color: c.ink2 }, grid: { drawOnChartArea: false }, title: { display: true, text: 'USD', color: c.ink2 } };
            charts.price = new Chart(price, { type: 'line', data: { labels: D.price.labels, datasets: [
                { label: 'ราคา (บาท)', data: D.price.thb, borderColor: c.a1, backgroundColor: c.a1, borderWidth: 2, tension: .2, yAxisID: 'y' },
                { label: 'ค่าเฉลี่ย 30 นาที (บาท)', data: D.price.twap, borderColor: c.ink, borderDash: [5, 4], borderWidth: 1.5, tension: .2, yAxisID: 'y' },
                { label: 'ราคา (USD)', data: D.price.usd, borderColor: c.blue, backgroundColor: c.blue, borderWidth: 1.5, tension: .2, yAxisID: 'y1' },
            ] }, options: o });
        }

        const pool = document.getElementById('trpPool');
        if (pool) {
            const o = baseOpts(c);
            o.elements = { point: { radius: 0 } };
            o.scales.y = { position: 'left', beginAtZero: false, ticks: { color: c.ink2 }, grid: { color: c.grid }, title: { display: true, text: 'USDT', color: c.ink2 } };
            o.scales.y1 = { position: 'right', beginAtZero: false, ticks: { color: c.ink2 }, grid: { drawOnChartArea: false }, title: { display: true, text: 'TPIX', color: c.ink2 } };
            charts.pool = new Chart(pool, { type: 'line', data: { labels: D.price.labels, datasets: [
                { label: 'USDT ในพูล', data: D.price.reserve_usdt, borderColor: c.teal, backgroundColor: c.teal, borderWidth: 2, tension: .2, yAxisID: 'y' },
                { label: 'TPIX ในพูล', data: D.price.reserve_tpix, borderColor: c.a2, backgroundColor: c.a2, borderWidth: 2, tension: .2, yAxisID: 'y1' },
            ] }, options: o });
        }

        const status = document.getElementById('trpStatus');
        if (status) {
            const keys = Object.keys(D.statuses);
            charts.status = new Chart(status, { type: 'doughnut', data: {
                labels: keys.map((k) => D.statusNames[k] || k),
                datasets: [{ data: keys.map((k) => D.statuses[k]), backgroundColor: keys.map((k) => statusColors[k] || '#a0aec0'), borderColor: css('--surf', '#ece5d8'), borderWidth: 2 }],
            }, options: { responsive: true, maintainAspectRatio: false, animation: false, cutout: '62%',
                plugins: { legend: { position: 'right', labels: { color: c.ink, boxWidth: 12 } } } } });
        }
    }

    // สลับหน่วยกราฟรายวัน บาท ↔ TPIX
    document.querySelectorAll('.trp-seg button[data-unit]').forEach((btn) => {
        btn.addEventListener('click', () => {
            unit = btn.dataset.unit;
            document.querySelectorAll('.trp-seg button[data-unit]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
            if (charts.daily) { charts.daily.data.datasets = dailyDatasets(palette()); charts.daily.update(); }
        });
    });

    render();

    // ธีม V4 เปลี่ยนคลาส .dark / ตัวแปรสีบน <html> → วาดใหม่ให้สีแกนอ่านได้ (หน่วงกันวาดถี่)
    let t = null;
    new MutationObserver(() => { clearTimeout(t); t = setTimeout(render, 120); })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'style'] });
})();
</script>
@endpush

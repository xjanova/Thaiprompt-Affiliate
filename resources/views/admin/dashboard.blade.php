@extends('layouts.admin-v4')

@section('title', 'แดชบอร์ด')

@php
    use Illuminate\Support\Str;

    // ── ป้ายเดือนปัจจุบัน (พ.ศ.) ────────────────────────────────
    $thMonth = now()->locale('th')->translatedFormat('F');
    $thYear  = now()->year + 543;

    // ── สถิติหลัก 4 ใบ ─────────────────────────────────────────
    $kpis = [
        [
            'label' => 'ผู้ใช้ทั้งหมด', 'en' => 'Total Users',
            'value' => number_format($stats['total_users'] ?? 0),
            'delta' => $userGrowth ?? 0,
            'href'  => route('admin.users.index'),
            'spark' => [28,40,34,52,46,64,58,80,100],
        ],
        [
            'label' => 'สมาชิก MLM', 'en' => 'MLM Members',
            'value' => number_format($stats['total_affiliates'] ?? 0),
            'badge' => number_format($stats['active_affiliates'] ?? 0) . ' ใช้งาน',
            'href'  => route('admin.mlm.members.index'),
            'spark' => [22,30,44,40,58,52,70,76,92],
        ],
        [
            'label' => 'รายได้ (จ่ายแล้ว)', 'en' => 'Paid Commissions',
            'value' => '฿' . number_format($stats['paid_commissions'] ?? 0, 0),
            'delta' => $revenueGrowth ?? 0,
            'href'  => route('admin.mlm.commissions.index', ['status' => 'paid']),
            'spark' => [18,26,30,42,48,60,72,84,100],
        ],
        [
            'label' => 'คอมมิชชั่นรอจ่าย', 'en' => 'Pending Payout',
            'value' => number_format($stats['pending_commissions'] ?? 0),
            'badge' => number_format($stats['approved_commissions'] ?? 0) . ' อนุมัติ',
            'href'  => route('admin.mlm.commissions.index', ['status' => 'pending']),
            'spark' => [40,32,46,38,52,44,58,50,64],
        ],
    ];

    // ── กราฟแท่งรายได้รายเดือน ─────────────────────────────────
    $revMax = max(1, (float) ($monthlyRevenue->max('total') ?: 1));

    // ── โดนัทสถานะคอมมิชชั่น (conic-gradient) ──────────────────
    $segs = [
        ['label' => 'รอ',      'val' => (int) ($commissionStatus['pending']  ?? 0), 'color' => '#e0a52e'],
        ['label' => 'อนุมัติ', 'val' => (int) ($commissionStatus['approved'] ?? 0), 'color' => '#5aa07e'],
        ['label' => 'จ่าย',    'val' => (int) ($commissionStatus['paid']     ?? 0), 'color' => 'var(--accent1)'],
        ['label' => 'ปฏิเสธ',  'val' => (int) ($commissionStatus['rejected'] ?? 0), 'color' => '#d9534f'],
    ];
    $segTotal = array_sum(array_column($segs, 'val'));
    if ($segTotal > 0) {
        $acc = 0; $stops = [];
        foreach ($segs as $s) {
            $start = $acc / $segTotal * 360; $acc += $s['val']; $end = $acc / $segTotal * 360;
            $stops[] = $s['color'] . ' ' . round($start, 2) . 'deg ' . round($end, 2) . 'deg';
        }
        $conic = 'conic-gradient(' . implode(', ', $stops) . ')';
    } else {
        $conic = 'conic-gradient(color-mix(in srgb, var(--ink2) 22%, transparent) 0deg 360deg)';
    }
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ ───────────────────────────────────────────── --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">ภาพรวมเครือข่าย · OVERVIEW · {{ $thMonth }} {{ $thYear }}</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สวัสดี, {{ auth()->user()->name }} <span style="color:var(--deep1);">✳</span></h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <span class="tp-card" style="display:flex; align-items:center; gap:8px; padding:9px 14px; border-radius:13px;">
                <span style="width:9px; height:9px; border-radius:50%; background:#5aa07e; box-shadow:0 0 0 4px rgba(90,160,126,.18);"></span>
                <span style="font-size:12px; color:var(--ink2);">ออนไลน์</span>
                <span class="tp-num" style="font-weight:700;">{{ number_format($stats['active_affiliates'] ?? 0) }}</span>
            </span>
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-coins"></i> ออกรอบจ่ายคอม
            </a>
        </div>
    </div>

    {{-- ── KPI 4 ใบ ─────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px;">
        @foreach($kpis as $k)
            <a href="{{ $k['href'] }}" class="tp-card tp-card-hover" style="display:block; padding:18px; text-decoration:none; color:inherit;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $k['label'] }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $k['en'] }}</div>
                    </div>
                    @if(!empty($k['badge']))
                        <span class="tp-pill tp-pill-soft" style="white-space:nowrap;">{{ $k['badge'] }}</span>
                    @elseif(isset($k['delta']) && $k['delta'] != 0)
                        <span class="tp-pill" style="white-space:nowrap; {{ $k['delta'] > 0 ? 'color:#4f9e7e; background:rgba(79,158,126,.14);' : 'color:#d9534f; background:rgba(217,83,79,.14);' }}">
                            {{ $k['delta'] > 0 ? '↑' : '↓' }} {{ number_format(abs($k['delta']), 1) }}%
                        </span>
                    @endif
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; margin:10px 0 12px; letter-spacing:.4px;">{{ $k['value'] }}</div>
                <div class="tp-spark">
                    @foreach($k['spark'] as $i => $h)
                        <i style="height:{{ $h }}%; animation-delay:{{ $i * 0.05 }}s;"></i>
                    @endforeach
                </div>
            </a>
        @endforeach
    </div>

    {{-- ── ราคาคริปโต (ถ้ามี) ──────────────────────────────── --}}
    @if(!empty($cryptoRates))
    <div>
        <div class="tp-section-h">₿ ราคาคริปโตปัจจุบัน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px;">
            @foreach($cryptoRates as $symbol => $rate)
                <a href="{{ route('admin.crypto.currencies') }}" class="tp-card tp-card-hover" style="display:block; padding:15px; text-decoration:none; color:inherit;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-weight:700;">{{ $symbol }}</span>
                        <span class="tp-pill" style="{{ ($rate['change_24h'] ?? 0) >= 0 ? 'color:#4f9e7e; background:rgba(79,158,126,.14);' : 'color:#d9534f; background:rgba(217,83,79,.14);' }}">
                            {{ ($rate['change_24h'] ?? 0) >= 0 ? '↗' : '↘' }} {{ number_format(abs($rate['change_24h'] ?? 0), 2) }}%
                        </span>
                    </div>
                    <div class="tp-num" style="font-size:22px; font-weight:800;">฿{{ number_format($rate['price'] ?? 0, 2) }}</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:4px;">Vol: ฿{{ number_format($rate['volume_24h'] ?? 0, 0) }}</div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── กราฟ: ยอดขาย + โดนัท ─────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">
        {{-- รายได้รายเดือน --}}
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;">
                <div>
                    <div style="font-weight:700; font-size:15px;">ยอดขาย · รายเดือน</div>
                    <div style="font-size:11px; color:var(--ink2);">Sales — 6 เดือนล่าสุด</div>
                </div>
                <span class="tp-pill tp-pill-gold tp-num">฿{{ number_format($monthlyRevenue->sum('total'), 0) }}</span>
            </div>
            @if($monthlyRevenue->count() > 0)
            <div class="tp-bars">
                @foreach($monthlyRevenue as $row)
                    <div class="col" title="{{ $row->month }}: ฿{{ number_format($row->total, 0) }}">
                        <div class="stack" style="height:100%;">
                            <span class="bar a" style="height:{{ max(3, round(($row->total / $revMax) * 100)) }}%;"></span>
                        </div>
                        <span class="lbl">{{ Str::limit($row->month, 6, '') }}</span>
                    </div>
                @endforeach
            </div>
            @else
                <div style="text-align:center; color:var(--ink2); padding:40px 0; font-size:13px;">ยังไม่มีข้อมูล</div>
            @endif
        </div>

        {{-- สถานะคอมมิชชั่น (โดนัท) --}}
        <div class="tp-card" style="padding:20px;">
            <div style="margin-bottom:16px;">
                <div style="font-weight:700; font-size:15px;">คอมมิชชั่นตามสถานะ</div>
                <div style="font-size:11px; color:var(--ink2);">Commission by status</div>
            </div>
            <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
                <div style="position:relative; width:150px; height:150px; flex:none;">
                    <div style="width:100%; height:100%; border-radius:50%; background:{{ $conic }}; -webkit-mask:radial-gradient(circle 46px at center, transparent 98%, #000 100%); mask:radial-gradient(circle 46px at center, transparent 98%, #000 100%);"></div>
                    <div style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
                        <span class="tp-num" style="font-size:18px; font-weight:800;">฿{{ number_format($stats['paid_commissions'] ?? 0, 0) }}</span>
                        <span style="font-size:10px; color:var(--ink2);">จ่ายทั้งหมด</span>
                    </div>
                </div>
                <div style="flex:1; min-width:130px; display:flex; flex-direction:column; gap:9px;">
                    @foreach($segs as $s)
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="width:11px; height:11px; border-radius:4px; background:{{ $s['color'] }}; flex:none;"></span>
                            <span style="font-size:12.5px; color:var(--ink2); flex:1;">{{ $s['label'] }}</span>
                            <span class="tp-num" style="font-weight:700; font-size:13px;">{{ number_format($s['val']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── 3 คอลัมน์: Top Affiliates / กิจกรรม / Tickets ────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
        {{-- Top Affiliates --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:12px;">🏆 Top Affiliates</div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                @forelse($topAffiliates->take(5) as $index => $m)
                    <div style="display:flex; align-items:center; gap:11px; padding:9px 11px; border-radius:13px; box-shadow:var(--inset-sm);">
                        <span class="tp-tile" style="width:34px; height:34px; border-radius:10px; font-size:14px;">
                            {{ [0=>'🥇',1=>'🥈',2=>'🥉'][$index] ?? ($index + 1) }}
                        </span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $m->user->name ?? '—' }}</div>
                            <div style="font-size:11px; color:var(--ink2);">{{ $m->total_direct_referrals ?? 0 }} refs</div>
                        </div>
                        <span class="tp-num" style="font-weight:700; font-size:13px; color:var(--deep1);">฿{{ number_format($m->total_earnings ?? 0, 0) }}</span>
                    </div>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:24px 0; font-size:13px;">ยังไม่มีข้อมูล</div>
                @endforelse
            </div>
        </div>

        {{-- กิจกรรมล่าสุด --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:12px;">🔔 กิจกรรมล่าสุด</div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:320px; overflow-y:auto;">
                @forelse($recentCommissions->take(6) as $c)
                    <div style="display:flex; align-items:center; gap:11px; padding:9px 11px; border-radius:13px; box-shadow:var(--inset-sm);">
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $c->affiliate->user->name ?? '—' }}</div>
                            <div class="tp-num" style="font-size:12px; color:var(--deep1); font-weight:700;">฿{{ number_format($c->amount, 2) }}</div>
                        </div>
                        @php
                            $stColor = $c->status === 'pending' ? '#e0a52e' : ($c->status === 'approved' ? '#5aa07e' : ($c->status === 'paid' ? 'var(--accent1)' : '#d9534f'));
                        @endphp
                        <span class="tp-pill" style="color:#fff; background:{{ $stColor }};">{{ ucfirst($c->status) }}</span>
                    </div>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:24px 0; font-size:13px;">ยังไม่มีกิจกรรม</div>
                @endforelse
            </div>
        </div>

        {{-- Tickets ล่าสุด --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:12px;">🎫 Tickets ล่าสุด</div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:320px; overflow-y:auto;">
                @forelse($recentTickets as $t)
                    <a href="{{ route('admin.tickets.show', $t->id) }}" style="display:flex; align-items:center; gap:11px; padding:9px 11px; border-radius:13px; box-shadow:var(--inset-sm); text-decoration:none; color:inherit;">
                        @php
                            $prColor = $t->priority === 'critical' ? '#d9534f' : ($t->priority === 'high' ? '#e0a52e' : ($t->priority === 'medium' ? '#5689b8' : 'var(--ink2)'));
                        @endphp
                        <span style="width:32px; height:32px; flex:none; border-radius:9px; display:grid; place-items:center; color:#fff; font-size:11px; font-weight:700; background:{{ $prColor }};">{{ strtoupper(substr($t->priority, 0, 1)) }}</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">#{{ $t->ticket_number }} · {{ Str::limit($t->subject, 24) }}</div>
                            <div style="font-size:11px; color:var(--ink2);">{{ $t->user->name ?? '—' }} · {{ $t->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:24px 0; font-size:13px;">ยังไม่มี Tickets</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── การดำเนินการด่วน ─────────────────────────────────── --}}
    <div>
        <div class="tp-section-h" style="margin-bottom:12px;">⚡ การดำเนินการด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
            @php
                $quick = [
                    ['➕', 'เพิ่มผู้ใช้', route('admin.users.create')],
                    ['✅', 'อนุมัติคอม', route('admin.mlm.commissions.index', ['status' => 'pending'])],
                    ['💸', 'การถอนเงิน', route('admin.crypto.withdrawals')],
                    ['🎫', 'Tickets', route('admin.tickets.index')],
                    ['🪪', 'ตรวจ KYC', route('admin.kyc.index')],
                    ['🌳', 'ผังสายงาน', route('admin.mlm.genealogy.index')],
                ];
            @endphp
            @foreach($quick as [$emoji, $label, $href])
                <a href="{{ $href }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:16px 10px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:20px;">{{ $emoji }}</span>
                    <span style="font-size:12.5px; font-weight:600; text-align:center;">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection

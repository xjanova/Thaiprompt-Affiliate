@extends('layouts.user-v4')

@section('title', 'คอมมิชชั่น')

@php
    use Illuminate\Support\Str;

    // ── ป้ายประเภทคอมมิชชั่น ───────────────────────────────────
    $typeLabels = [
        'direct' => 'Direct', 'unilevel_direct' => 'Unilevel Direct', 'unilevel_indirect' => 'Unilevel Indirect',
        'binary_pair' => 'Binary Pair', 'binary_matching' => 'Binary Matching', 'sponsor_bonus' => 'Sponsor Bonus',
        'rank_bonus' => 'Rank Bonus', 'leadership_bonus' => 'Leadership Bonus', 'matching_bonus' => 'Matching Bonus',
        'pool_bonus' => 'Pool Bonus', 'mlm_unilevel' => 'MLM Unilevel', 'mlm_binary' => 'MLM Binary', 'bonus' => 'Bonus',
    ];

    // ── สถิติ 4 ใบ (นับจำนวน) ──────────────────────────────────
    $cards = [
        ['label' => 'ทั้งหมด',       'en' => 'Total',    'val' => $stats['total'] ?? 0,    'color' => 'var(--deep1)',  'soft' => 'color-mix(in srgb, var(--accent1) 16%, transparent)', 'icon' => '📊'],
        ['label' => 'รอดำเนินการ',   'en' => 'Pending',  'val' => $stats['pending'] ?? 0,  'color' => '#e0a52e',       'soft' => 'rgba(224,165,46,.16)',  'icon' => '⏳'],
        ['label' => 'อนุมัติแล้ว',   'en' => 'Approved', 'val' => $stats['approved'] ?? 0, 'color' => '#5aa07e',       'soft' => 'rgba(90,160,126,.16)',  'icon' => '✅'],
        ['label' => 'จ่ายแล้ว',      'en' => 'Paid',     'val' => $stats['paid'] ?? 0,     'color' => '#5689b8',       'soft' => 'rgba(86,137,184,.16)',  'icon' => '💰'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-coins" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">คอมมิชชั่นของฉัน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ประวัติคอมมิชชั่นจากทุกระบบ (MLM และ Marketplace)</div>
            </div>
        </div>
    </div>

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($cards as $c)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $c['label'] }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $c['en'] }}</div>
                    </div>
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:{{ $c['soft'] }};">{{ $c['icon'] }}</span>
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800; margin-top:10px; color:{{ $c['color'] }};">{{ number_format($c['val']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── ตารางคอมมิชชั่น ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($commissions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            @foreach(['วันที่','แหล่งที่มา','ประเภท','จำนวน','%','สถานะ','หมายเหตุ'] as $h)
                                <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commissions as $commission)
                            @php
                                $st = $commission->status;
                                $stMap = [
                                    'pending'   => ['รอดำเนินการ', '#e0a52e', 'fa-clock'],
                                    'approved'  => ['อนุมัติแล้ว', '#5aa07e', 'fa-check'],
                                    'paid'      => ['จ่ายแล้ว',     '#5689b8', 'fa-check-double'],
                                    'cancelled' => ['ยกเลิก',       'var(--ink2)', 'fa-ban'],
                                ];
                                $stInfo = $stMap[$st] ?? ['ปฏิเสธ', '#d9534f', 'fa-xmark'];
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; color:var(--ink);">{{ \Carbon\Carbon::parse($commission->created_at)->format('d/m/Y H:i') }}</td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    @if($commission->source === 'mlm')
                                        <span class="tp-pill" style="color:var(--deep1); background:color-mix(in srgb, var(--accent1) 16%, transparent);"><i class="fas fa-network-wired" style="font-size:10px;"></i> MLM</span>
                                    @else
                                        <span class="tp-pill" style="color:#5689b8; background:rgba(86,137,184,.16);"><i class="fas fa-store" style="font-size:10px;"></i> Marketplace</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; color:var(--ink2);">
                                    <span class="tp-pill tp-pill-soft" style="font-size:10.5px;">{{ $typeLabels[$commission->type] ?? ucfirst(str_replace('_', ' ', (string) $commission->type)) }}</span>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; font-weight:700; color:var(--ink);">฿{{ number_format($commission->amount, 2) }}</td>
                                <td class="tp-num" style="padding:12px 16px; color:var(--ink2);">{{ $commission->percentage ? $commission->percentage . '%' : '-' }}</td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <span class="tp-pill" style="color:#fff; background:{{ $stInfo[1] }};"><i class="fas {{ $stInfo[2] }}" style="font-size:10px;"></i> {{ $stInfo[0] }}</span>
                                </td>
                                <td style="padding:12px 16px; color:var(--ink2); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $commission->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $commissions->links() }}
            </div>
        @else
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">📭</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีคอมมิชชั่น</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เมื่อมีคอมมิชชั่นเข้ามาจะแสดงที่นี่</div>
            </div>
        @endif
    </div>
</div>
@endsection

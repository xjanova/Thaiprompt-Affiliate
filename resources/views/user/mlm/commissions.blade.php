@extends('layouts.user-v4')

@section('title', 'ค่าคอมมิชชั่น MLM')

@php
    use Illuminate\Support\Str;

    // ── การ์ดสถิติ 4 ใบ (ยอดเงิน) ──────────────────────────────
    $statCards = [
        ['label' => 'รอดำเนินการ', 'en' => 'Pending',   'val' => $stats['pending'] ?? 0,    'color' => '#e0a52e', 'soft' => 'rgba(224,165,46,.16)', 'icon' => '⏳'],
        ['label' => 'อนุมัติแล้ว', 'en' => 'Approved',  'val' => $stats['approved'] ?? 0,   'color' => '#5aa07e', 'soft' => 'rgba(90,160,126,.16)', 'icon' => '✅'],
        ['label' => 'จ่ายแล้ว',    'en' => 'Paid',      'val' => $stats['paid'] ?? 0,       'color' => '#5689b8', 'soft' => 'rgba(86,137,184,.16)', 'icon' => '💸'],
        ['label' => 'เดือนนี้',    'en' => 'This Month','val' => $stats['this_month'] ?? 0, 'color' => 'var(--deep1)', 'soft' => 'color-mix(in srgb, var(--accent1) 16%, transparent)', 'icon' => '📅'],
    ];

    // ── ป้ายประเภทคอมมิชชั่น MLM ───────────────────────────────
    $typeLabels = [
        'direct_referral' => 'Direct Referral',
        'binary_matching' => 'Binary Matching',
        'unilevel'        => 'Unilevel',
        'generation'      => 'Generation',
        'bonus'           => 'Bonus',
    ];

    // ── สีของป้ายประเภท ──────────────────────────────────────
    $typeColors = [
        'direct_referral' => '#5689b8',
        'binary_matching' => 'var(--deep1)',
        'unilevel'        => '#5aa07e',
        'generation'      => '#e0a52e',
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-money-bill-wave" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ค่าคอมมิชชั่น MLM</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">รายละเอียดรายได้จากระบบ MLM</div>
            </div>
        </div>
    </div>

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($statCards as $c)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $c['label'] }}</div>
                        <div style="font-size:10px; color:var(--ink2); opacity:.8;">{{ $c['en'] }}</div>
                    </div>
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:{{ $c['soft'] }};">{{ $c['icon'] }}</span>
                </div>
                <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:10px; color:{{ $c['color'] }};">฿{{ number_format($c['val'], 2) }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── ตัวกรอง ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">ประเภท</label>
                <select name="type" class="tp-input" style="width:100%;">
                    <option value="">ทั้งหมด</option>
                    <option value="direct_referral" {{ request('type') === 'direct_referral' ? 'selected' : '' }}>Direct Referral</option>
                    <option value="binary_matching" {{ request('type') === 'binary_matching' ? 'selected' : '' }}>Binary Matching</option>
                    <option value="unilevel" {{ request('type') === 'unilevel' ? 'selected' : '' }}>Unilevel</option>
                    <option value="generation" {{ request('type') === 'generation' ? 'selected' : '' }}>Generation</option>
                    <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">สถานะ</label>
                <select name="status" class="tp-input" style="width:100%;">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
                <a href="{{ route('user.mlm.commissions') }}" class="tp-btn">ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ── ตารางค่าคอมมิชชั่น ───────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($commissions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            @foreach(['วันที่','ประเภท','จาก','จำนวนเงิน','สถานะ','หมายเหตุ'] as $h)
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
                                $stInfo = $stMap[$st] ?? null;
                                $typeColor = $typeColors[$commission->type] ?? 'var(--ink2)';
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; color:var(--ink);">
                                    <div style="font-weight:600;">{{ $commission->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $commission->created_at->format('H:i') }}</div>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <span class="tp-pill" style="font-size:10.5px; color:{{ $typeColor }}; background:color-mix(in srgb, {{ $typeColor }} 14%, transparent);">
                                        {{ $typeLabels[$commission->type] ?? ucfirst(str_replace('_', ' ', (string) $commission->type)) }}
                                    </span>
                                </td>
                                <td style="padding:12px 16px;">
                                    @if($commission->fromMember && $commission->fromMember->user)
                                        <div style="font-weight:600; color:var(--ink);">{{ $commission->fromMember->user->name }}</div>
                                        <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $commission->fromMember->member_code }}</div>
                                    @else
                                        <span style="color:var(--ink2);">-</span>
                                    @endif
                                </td>
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; font-weight:700; color:#5aa07e;">฿{{ number_format($commission->commission_amount, 2) }}</td>
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    @if($stInfo)
                                        <span class="tp-pill" style="color:#fff; background:{{ $stInfo[1] }};"><i class="fas {{ $stInfo[2] }}" style="font-size:10px;"></i> {{ $stInfo[0] }}</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; color:var(--ink2); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $commission->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── แบ่งหน้า ──────────────────────────────────────── --}}
            @if($commissions->hasPages())
                <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    {{ $commissions->links() }}
                </div>
            @endif
        @else
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">💰</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีค่าคอมมิชชั่น</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เมื่อมีค่าคอมมิชชั่นเข้ามาจะแสดงที่นี่</div>
            </div>
        @endif
    </div>
</div>
@endsection

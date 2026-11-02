{{--
    ประวัติคอมมิชชั่น MLM (ธีมโนวาเต็ม) — MlmDashboardController::commissions
    ตัวแปร: $commissions (paginator ของผู้ใช้ที่ล็อกอินเท่านั้น), $stats, $commissionTypes, $commissionStatuses
    🐛 ตัวกรองเดิมส่งประเภทที่ไม่มีใน enum (direct_referral/unilevel/...) และเลือก "ทั้งหมด" แล้วได้ผลว่าง
       เพราะ controller ใช้ has() กับค่าว่าง — ตอนนี้ใช้ค่าจาก enum จริง + filled()
--}}
@extends('layouts.user-nova')

@section('title', 'คอมมิชชั่น MLM')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $statusTone = ['paid' => 'ok', 'approved' => 'info', 'pending' => 'warn', 'rejected' => 'bad', 'cancelled' => 'bad', 'clawback' => 'bad'];
    $filtered = request()->filled('type') || request()->filled('status');

    $kpis = [
        ['รอดำเนินการ', 'Pending', $stats['pending'] ?? 0, 'images/nova/wallet/hourglass.webp', 'รอแอดมินตรวจ'],
        ['อนุมัติแล้ว', 'Approved', $stats['approved'] ?? 0, 'images/nova/deco/scroll.webp', 'รอโอนเข้ากระเป๋า'],
        ['จ่ายแล้ว', 'Paid', $stats['paid'] ?? 0, 'images/nova/deco/coins.webp', 'เข้ากระเป๋าเงินแล้ว'],
        ['จ่ายเดือนนี้', 'This month', $stats['this_month'] ?? 0, 'images/nova/wallet/deposit.webp', now()->locale('th')->translatedFormat('F Y')],
    ];
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page">

<section class="nvm-ph" aria-labelledby="nvm-mlc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">MLM commissions</div>
            <h1 id="nvm-mlc-h">คอมมิชชั่น MLM</h1>
            <p class="nvm-ph__lead">รายได้จากทีมทุกรายการ พร้อมสถานะการจ่าย</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุปคอมมิชชั่น">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi nvm-rv">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $baht($value) }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-mlc-f-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Filter</small><h2 id="nvm-mlc-f-h">ค้นหารายการ</h2></div></div>
    <form method="GET" action="{{ route('user.mlm.commissions') }}" class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="mlc-type">ประเภท</label>
            <select id="mlc-type" name="type" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($commissionTypes as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="mlc-status">สถานะ</label>
            <select id="mlc-status" name="status" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($commissionStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ค้นหา</button>
            @if($filtered)
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.mlm.commissions') }}">ล้าง</a>
            @endif
        </div>
    </form>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-mlc-l-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">History</small><h2 id="nvm-mlc-l-h">รายการคอมมิชชั่น</h2></div>
        <span class="nvm-chip">{{ number_format($commissions->total()) }} รายการ</span>
    </div>

    @if($commissions->count() > 0)
        <div class="nvm-table-wrap">
            <table class="nvm-table">
                <thead>
                    <tr><th>วันที่</th><th>ประเภท</th><th>จาก</th><th class="r">จำนวนเงิน</th><th>สถานะ</th><th>หมายเหตุ</th></tr>
                </thead>
                <tbody>
                    @foreach($commissions as $commission)
                        @php
                            $void = in_array($commission->status, ['rejected', 'cancelled', 'clawback'], true);
                            $from = $commission->fromMember;
                        @endphp
                        <tr>
                            <td class="nvm-num nvm-ml-nowrap">{{ $commission->created_at?->format('d/m/Y') }}<small class="nvm-ml-sub">{{ $commission->created_at?->format('H:i') }}</small></td>
                            <td class="nvm-ml-nowrap">
                                <span class="nvm-chip nvm-chip--gold">{{ $commissionTypes[$commission->type] ?? 'คอมมิชชั่น' }}</span>
                                @if($commission->level)<small class="nvm-ml-sub">ชั้น {{ $commission->level }}</small>@endif
                            </td>
                            <td>
                                @if($from)
                                    {{ $from->user?->name ?? 'สมาชิก' }}<small class="nvm-ml-sub nvm-num">{{ $from->member_code }}</small>
                                @else
                                    <span class="nvm-ml-mute">—</span>
                                @endif
                            </td>
                            <td class="r nvm-num nvm-ml-nowrap"><b class="{{ $void ? 'nvm-ml-void' : 'nvm-ml-in' }}">{{ $baht($commission->commission_amount) }}</b></td>
                            <td><span class="nvm-chip nvm-chip--{{ $statusTone[$commission->status] ?? 'warn' }}">{{ $commissionStatuses[$commission->status] ?? $commission->status }}</span></td>
                            <td class="nvm-ml-note">{{ $commission->status === 'rejected' && $commission->rejection_reason ? $commission->rejection_reason : ($commission->notes ?: '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($commissions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $commissions->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            @if($filtered)
                <b>ไม่พบรายการตามเงื่อนไข</b>
                ลองเปลี่ยนประเภทหรือสถานะ หรือกดล้างตัวกรอง
            @else
                <b>ยังไม่มีคอมมิชชั่น</b>
                เมื่อทีมของคุณมียอดซื้อ คอมมิชชั่นจะแสดงที่นี่
            @endif
        </div>
    @endif
</section>

</div>
@endsection

@extends('layouts.seller-v4')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── ยอดเงินคงเหลือ (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:26px 28px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), transparent 72%);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:22px; color:var(--deep1);">
                <i class="fas fa-wallet"></i>
            </span>
            <div>
                <h1 style="font-size:clamp(19px,3.5vw,24px); font-weight:800; margin:0; color:var(--ink);">กระเป๋าเงินของฉัน</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:2px;">จัดการการเงินของร้านค้า</div>
            </div>
        </div>
        <div class="tp-card" style="padding:22px 24px; box-shadow:var(--inset-sm);">
            <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">ยอดเงินคงเหลือ</div>
            <div class="tp-num" style="font-size:clamp(34px,7vw,52px); font-weight:800; line-height:1; color:var(--deep1);">฿{{ number_format($balance, 2) }}</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:6px;">THB</div>
        </div>
    </div>

    {{-- ── แจ้งเตือนคำขอถอนเงินค้าง ───────────────────────────── --}}
    @if(isset($pendingWithdrawals) && $pendingWithdrawals > 0)
        <div class="tp-card" style="padding:16px 20px; background:color-mix(in srgb, #e0a52e 12%, transparent); border:1px solid color-mix(in srgb, #e0a52e 35%, transparent);">
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <span style="font-size:22px;">⏳</span>
                <div>
                    <h3 style="font-weight:800; color:var(--ink); margin:0 0 3px; font-size:14.5px;">มีคำขอถอนเงินรอดำเนินการ</h3>
                    <p style="color:var(--ink2); font-size:13px; margin:0;">
                        จำนวน ฿{{ number_format($pendingWithdrawals, 2) }} กำลังรอการอนุมัติ
                        <a href="{{ route('seller.wallet.withdrawals') }}" style="color:var(--deep1); font-weight:700; text-decoration:underline;">ดูรายละเอียด</a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ── เมนูด่วน ─────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px;">
        @php
            $quickActions = [
                ['seller.wallet.withdraw',   '💸', 'ถอนเงิน',        'Withdraw'],
                ['seller.wallet.withdrawals','📋', 'ประวัติถอนเงิน', 'History'],
                ['seller.reports.sales',     '📊', 'รายงาน',         'Reports'],
                ['seller.dashboard',         '🏠', 'แดชบอร์ด',       'Dashboard'],
            ];
        @endphp
        @foreach($quickActions as [$route, $icon, $label, $sub])
            @if(\Illuminate\Support\Facades\Route::has($route))
                <a href="{{ route($route) }}" class="tp-card" style="padding:20px 16px; text-align:center; text-decoration:none;">
                    <div style="font-size:34px; margin-bottom:8px;">{{ $icon }}</div>
                    <div style="font-weight:700; color:var(--ink); font-size:14px;">{{ $label }}</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $sub }}</div>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ── ธุรกรรมล่าสุด ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 18px; display:flex; align-items:center; gap:8px;">
            <span>📝</span> ธุรกรรมล่าสุด
        </h2>

        @if(empty($transactions) || count($transactions) === 0)
            <div style="text-align:center; padding:48px 0;">
                <span style="font-size:56px; display:block; margin-bottom:14px;">💼</span>
                <p style="color:var(--ink); font-size:16px; font-weight:600; margin:0 0 4px;">ยังไม่มีธุรกรรม</p>
                <p style="color:var(--ink2); font-size:13px; margin:0;">ธุรกรรมทั้งหมดจะแสดงที่นี่</p>
            </div>
        @else
            <div style="display:flex; flex-direction:column;">
                @foreach($transactions as $transaction)
                    @php $isIncome = $transaction->type === 'income'; @endphp
                    <div style="padding:14px 0; display:flex; align-items:center; justify-content:space-between; gap:12px; {{ !$loop->last ? 'border-bottom:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);' : '' }}">
                        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                            <span class="tp-tile" style="width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0;">
                                {{ $isIncome ? '📥' : '📤' }}
                            </span>
                            <div style="min-width:0;">
                                <p style="font-weight:700; color:var(--ink); margin:0; font-size:14px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $transaction->description }}</p>
                                <p style="font-size:12.5px; color:var(--ink2); margin:2px 0 0;">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <p class="tp-num" style="font-weight:800; margin:0; font-size:15px; color:{{ $isIncome ? '#5aa07e' : '#d9534f' }};">
                                {{ $isIncome ? '+' : '-' }}฿{{ number_format($transaction->amount, 2) }}
                            </p>
                            <p style="font-size:11.5px; color:var(--ink2); margin:2px 0 0;">{{ $transaction->status }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.seller-v4')

@section('title', 'ประวัติการถอนเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="{{ route('seller.wallet.index') }}" class="tp-btn" style="width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); text-decoration:none; flex-shrink:0;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 style="font-size:clamp(19px,3.5vw,24px); font-weight:800; margin:0; color:var(--ink);">📋 ประวัติการถอนเงิน</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:2px;">ดูรายการคำขอถอนเงินทั้งหมดของร้านค้า</div>
            </div>
        </div>
    </div>

    {{-- ── Success / Error ───────────────────────────────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:16px 20px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 32%, transparent);">
            <div style="display:flex; gap:12px; align-items:center;"><span style="font-size:20px;">✅</span><p style="color:var(--ink); font-weight:600; font-size:14px; margin:0;">{{ session('success') }}</p></div>
        </div>
    @endif
    @if($errors->any())
        <div class="tp-card" style="padding:16px 20px; background:color-mix(in srgb, #d9534f 10%, transparent); border:1px solid color-mix(in srgb, #d9534f 32%, transparent);">
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <span style="font-size:20px;">⚠️</span>
                <div>@foreach($errors->all() as $error)<p style="color:#d9534f; font-weight:600; font-size:13.5px; margin:0 0 3px;">{{ $error }}</p>@endforeach</div>
            </div>
        </div>
    @endif

    {{-- ── ปุ่มถอนเงินใหม่ ───────────────────────────────────── --}}
    <div>
        <a href="{{ route('seller.wallet.withdraw') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">
            💸 ถอนเงินใหม่
        </a>
    </div>

    {{-- ── ตารางประวัติ ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($withdrawals->isEmpty())
            <div style="text-align:center; padding:60px 24px;">
                <span style="font-size:56px; display:block; margin-bottom:14px;">📭</span>
                <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin:0 0 6px;">ยังไม่มีประวัติการถอนเงิน</h3>
                <p style="color:var(--ink2); font-size:14px; margin:0 0 22px;">คุณยังไม่เคยทำการถอนเงิน</p>
                <a href="{{ route('seller.wallet.withdraw') }}" class="tp-btn" style="display:inline-block; padding:11px 22px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">ถอนเงินเลย</a>
            </div>
        @else
            @php
                $wStatusColor = ['pending' => '#e0a52e', 'processing' => '#5689b8', 'completed' => '#5aa07e', 'approved' => '#5aa07e', 'rejected' => '#d9534f', 'cancelled' => '#9a8f7c'];
            @endphp
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">หมายเลขคำขอ</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">วันที่</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ช่องทางรับเงิน</th>
                            <th style="padding:14px 20px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จำนวนเงิน</th>
                            <th style="padding:14px 20px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                            <th style="padding:14px 20px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($withdrawals as $withdrawal)
                            @php $wc = $wStatusColor[$withdrawal->status] ?? '#9a8f7c'; @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:15px 20px;">
                                    <p style="font-weight:700; color:var(--ink); margin:0; font-size:13.5px;">{{ $withdrawal->request_id }}</p>
                                    @if($withdrawal->user_note)<p style="font-size:11.5px; color:var(--ink2); margin:3px 0 0;">{{ Str::limit($withdrawal->user_note, 30) }}</p>@endif
                                </td>
                                <td style="padding:15px 20px;">
                                    <p style="color:var(--ink); margin:0; font-size:13.5px;">{{ $withdrawal->created_at->format('d/m/Y') }}</p>
                                    <p style="font-size:11.5px; color:var(--ink2); margin:2px 0 0;">{{ $withdrawal->created_at->format('H:i') }} น.</p>
                                </td>
                                <td style="padding:15px 20px;">
                                    @if($withdrawal->paymentMethod)
                                        <p style="color:var(--ink); margin:0; font-size:13.5px;">{{ $withdrawal->paymentMethod->bank_name ?? $withdrawal->payment_type_label }}</p>
                                        <p style="font-size:11.5px; color:var(--ink2); margin:2px 0 0;">{{ $withdrawal->paymentMethod->account_name ?? '-' }}</p>
                                    @else
                                        <p style="color:var(--ink2); margin:0; font-size:13.5px;">{{ $withdrawal->payment_type_label }}</p>
                                    @endif
                                </td>
                                <td style="padding:15px 20px; text-align:right;">
                                    <p class="tp-num" style="font-weight:800; color:var(--ink); margin:0; font-size:14.5px;">฿{{ number_format($withdrawal->amount, 2) }}</p>
                                    @if($withdrawal->fee > 0)<p style="font-size:11.5px; color:var(--ink2); margin:2px 0 0;">ค่าธรรมเนียม: ฿{{ number_format($withdrawal->fee, 2) }}</p>@endif
                                </td>
                                <td style="padding:15px 20px; text-align:center;">
                                    <span class="tp-pill" style="background:color-mix(in srgb, {{ $wc }} 18%, transparent); color:{{ $wc }}; font-size:11px; font-weight:700;">{{ $withdrawal->status_label }}</span>
                                    @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)<p style="font-size:11px; color:#d9534f; margin:4px 0 0;">{{ Str::limit($withdrawal->rejection_reason, 30) }}</p>@endif
                                </td>
                                <td style="padding:15px 20px; text-align:center;">
                                    @if($withdrawal->status === 'pending')
                                        <form action="{{ route('seller.wallet.withdrawal.cancel', $withdrawal->id) }}" method="POST" style="display:inline;"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคำขอนี้?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background:none; border:none; cursor:pointer; color:#d9534f; font-size:13px; font-weight:700;">ยกเลิก</button>
                                        </form>
                                    @elseif($withdrawal->status === 'completed' && $withdrawal->transfer_slip)
                                        <a href="{{ $withdrawal->transfer_slip_url }}" target="_blank" style="color:var(--deep1); font-size:13px; font-weight:700; text-decoration:none;">ดูสลิป</a>
                                    @else
                                        <span style="color:var(--ink2); font-size:13px;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:16px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.user-v4')

@section('title', 'รายละเอียดธุรกรรม TPIX - กระเป๋าเงิน TPIX')

@php
    // รายการของสมาชิกอาจเป็นเหรียญอื่น — controller กรองแค่เจ้าของ ไม่ได้กรองสกุลเงิน
    $code = $transaction->currency?->code ?? 'TPIX';
    $incoming = $transaction->is_incoming;
    // explorer_url ในแถว TPIX ยังชี้เชนเก่า เชนจริงอยู่ใน tpix_pay
    $explorerUrl = ! $transaction->tx_hash
        ? ''
        : ($code === 'TPIX'
            ? rtrim((string) config('tpix_pay.explorer_url'), '/').'/tx/'.$transaction->tx_hash
            : $transaction->getExplorerUrl());
    $rows = array_filter([
        'เลขที่รายการ' => $transaction->transaction_id,
        'ค่าธรรมเนียม' => number_format((float) $transaction->fee, 8).' '.$code,
        'รายละเอียด' => $transaction->description,
        'จากที่อยู่' => $transaction->from_address,
        'ไปยังที่อยู่' => $transaction->to_address,
        'เลขธุรกรรมบนเชน' => $transaction->tx_hash,
        'วันที่ทำรายการ' => $transaction->created_at->format('d/m/Y H:i'),
        'วันที่สำเร็จ' => ($transaction->completed_at ?? $transaction->confirmed_at)?->format('d/m/Y H:i'),
    ], fn ($value) => filled($value));
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:720px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="min-width:0;">
                <h1 style="font-size:clamp(20px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">รายละเอียดธุรกรรม</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:2px;">{{ $transaction->typeLabel() }}</div>
            </div>
            <a href="{{ route('user.tpix.transactions') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับประวัติ</span>
            </a>
        </div>
    </div>

    {{-- ── จำนวนและสถานะ ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;">
            <div style="display:flex; flex-wrap:wrap; align-items:baseline; gap:8px; min-width:0;">
                <div class="tp-num" style="font-size:clamp(24px,6vw,34px); font-weight:800; color:{{ $incoming ? '#5aa07e' : 'var(--deep1)' }}; line-height:1.1; overflow-wrap:anywhere;">{{ $incoming ? '+' : '-' }}{{ number_format((float) $transaction->amount, 8) }}</div>
                <div style="font-size:16px; color:var(--ink2);">{{ $code }}</div>
            </div>
            @include('user.tpix.partials.status-pill', ['status' => $transaction->status])
        </div>

        @if($transaction->tx_hash && $transaction->isPending())
            <div style="font-size:13px; color:var(--ink2); margin-top:12px;">ยืนยันแล้ว {{ (int) $transaction->confirmations }} จาก {{ (int) $transaction->confirmations_required }} บล็อก</div>
        @endif
    </div>

    {{-- ── ข้อมูลรายการ ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:8px 22px;">
        @foreach($rows as $label => $value)
            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:4px 16px; padding:12px 0; {{ $loop->last ? '' : 'border-bottom:1px solid color-mix(in srgb, var(--ink2) 10%, transparent);' }}">
                <div style="font-size:13px; color:var(--ink2);">{{ $label }}</div>
                <div style="font-size:13.5px; font-weight:600; color:var(--ink); overflow-wrap:anywhere; min-width:0; text-align:right; margin-left:auto;">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── ข้อมูลบนเชน ───────────────────────────────────────── --}}
    @if($transaction->tx_hash)
        <div class="tp-card" style="padding:20px 22px;">
            <h2 style="font-size:15px; font-weight:800; color:var(--ink); margin:0 0 12px;">ข้อมูลบนเครือข่าย</h2>
            @if($blockchainData)
                <div style="display:flex; justify-content:space-between; gap:16px; font-size:13.5px; margin-bottom:12px;">
                    <span style="color:var(--ink2);">บล็อก</span>
                    <span class="tp-num" style="font-weight:600; color:var(--ink);">{{ $blockchainData['blockNumber'] ?? 'ยังไม่เข้าบล็อก' }}</span>
                </div>
            @else
                <p style="font-size:13px; color:var(--ink2); margin:0 0 12px;">ขณะนี้เชื่อมต่อเครือข่ายไม่ได้ ข้อมูลรายการด้านบนยังถูกต้องตามระบบ</p>
            @endif
            @if($explorerUrl)
                <a href="{{ $explorerUrl }}" target="_blank" rel="noopener noreferrer" style="color:var(--deep1); font-size:13px; font-weight:700; text-decoration:none;">ดูบน Explorer <i class="fas fa-external-link-alt"></i></a>
            @endif
        </div>
    @endif
</div>
@endsection

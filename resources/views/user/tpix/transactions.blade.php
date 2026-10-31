@extends('layouts.user-v4')

@section('title', 'ประวัติธุรกรรม TPIX - กระเป๋าเงิน TPIX')

@php
    $typeOptions = [
        'deposit' => 'ฝาก',
        'withdrawal' => 'ถอน',
        'transfer_in' => 'รับจากสมาชิก',
        'transfer_out' => 'ส่งให้สมาชิก',
        'exchange_buy' => 'ซื้อเหรียญ',
        'exchange_sell' => 'ขายเหรียญ',
        'payment' => 'ชำระเงิน',
        'refund' => 'คืนเงิน',
    ];
    $statusOptions = [
        'pending' => 'รอดำเนินการ',
        'confirming' => 'รอยืนยันบนเครือข่าย',
        'confirmed' => 'ยืนยันแล้ว',
        'completed' => 'สำเร็จ',
        'failed' => 'ไม่สำเร็จ',
        'cancelled' => 'ยกเลิกแล้ว',
    ];
    $fieldStyle = 'width:100%; min-height:44px; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:16px;';
    $hasFilter = request()->hasAny(['type', 'status', 'date_from', 'date_to']);
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:860px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; color:var(--deep1); flex-shrink:0;"><i class="fas fa-history"></i></span>
                <div style="min-width:0;">
                    <h1 style="font-size:clamp(20px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">ประวัติธุรกรรม TPIX</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">รายการเข้าออกทั้งหมดของกระเป๋า TPIX</div>
                </div>
            </div>
            <a href="{{ route('user.tpix.wallet') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับกระเป๋า</span>
            </a>
        </div>
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('user.tpix.transactions') }}" class="tp-card" style="padding:18px 22px;">
        @if($errors->any())
            <div style="padding:11px 13px; border-radius:11px; margin-bottom:14px; background:color-mix(in srgb, #d9534f 10%, transparent); color:#d9534f; font-size:13px;">{{ $errors->first() }}</div>
        @endif
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:14px;">
            <label style="font-size:12.5px; font-weight:700; color:var(--ink); min-width:0;">
                <span style="display:block; margin-bottom:5px;">ประเภท</span>
                <select name="type" style="{{ $fieldStyle }}">
                    <option value="">ทั้งหมด</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="font-size:12.5px; font-weight:700; color:var(--ink); min-width:0;">
                <span style="display:block; margin-bottom:5px;">สถานะ</span>
                <select name="status" style="{{ $fieldStyle }}">
                    <option value="">ทั้งหมด</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="font-size:12.5px; font-weight:700; color:var(--ink); min-width:0;">
                <span style="display:block; margin-bottom:5px;">ตั้งแต่วันที่</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}" style="{{ $fieldStyle }}">
            </label>
            <label style="font-size:12.5px; font-weight:700; color:var(--ink); min-width:0;">
                <span style="display:block; margin-bottom:5px;">ถึงวันที่</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" style="{{ $fieldStyle }}">
            </label>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <button type="submit" class="tp-btn" style="min-height:44px; padding:10px 20px; border-radius:12px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; border:none; cursor:pointer;">กรองรายการ</button>
            @if($hasFilter)
                <a href="{{ route('user.tpix.transactions') }}" class="tp-btn" style="display:inline-flex; align-items:center; min-height:44px; padding:10px 18px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; text-decoration:none;">ล้างตัวกรอง</a>
            @endif
        </div>
    </form>

    {{-- ── รายการ ────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @forelse($transactions as $transaction)
            @include('user.tpix.partials.tx-row')
        @empty
            <div style="display:flex; flex-direction:column; align-items:center; padding:48px 24px; text-align:center;">
                <i class="fas fa-receipt" style="font-size:44px; color:color-mix(in srgb, var(--ink2) 40%, transparent); margin-bottom:12px;"></i>
                <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $hasFilter ? 'ไม่พบรายการตามตัวกรองนี้' : 'ยังไม่มีธุรกรรม' }}</p>
            </div>
        @endforelse
    </div>

    @if($transactions->hasPages())
        <div>{{ $transactions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@extends('layouts.user-v4')

@section('title', 'ส่ง TPIX - กระเป๋าเงิน TPIX')

@php
    // ตัดเหลือ 8 ตำแหน่งแบบเดียวกับที่ processSend รับ — ปัดขึ้นจะส่งเกินยอดจริง
    $balance = (string) ($wallet?->balance ?? '0');
    $maxAmount = rtrim(rtrim(bcadd($balance, '0', 8), '0'), '.');
    $walletActive = $wallet?->isActive() ?? false;
    $canSend = $walletActive && $tpixCurrency->is_active && bccomp($balance, '0.0001', 18) >= 0;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:720px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; color:var(--deep1); flex-shrink:0;"><i class="fas fa-paper-plane"></i></span>
                <div style="min-width:0;">
                    <h1 style="font-size:clamp(20px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">ส่ง TPIX</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">โอนให้สมาชิกในระบบ ไม่มีค่าธรรมเนียม ยอดเข้าทันที</div>
                </div>
            </div>
            <a href="{{ route('user.tpix.wallet') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับกระเป๋า</span>
            </a>
        </div>
    </div>

    {{-- ── ยอด ──────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div style="font-size:13px; color:var(--ink2); margin-bottom:6px;">ยอดคงเหลือ</div>
        <div style="display:flex; flex-wrap:wrap; align-items:baseline; gap:8px;">
            <div class="tp-num" style="font-size:clamp(26px,6vw,36px); font-weight:800; color:var(--deep1); line-height:1.1; overflow-wrap:anywhere;">{{ number_format((float) $balance, 8) }}</div>
            <div style="font-size:16px; color:var(--ink2);">TPIX</div>
        </div>
    </div>

    {{-- ── ฟอร์มส่ง ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:26px 24px;">
        <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 20px;">ฟอร์มส่ง TPIX</h2>

        @if(! $wallet)
            <div style="padding:13px 15px; border-radius:12px; margin-bottom:18px; background:color-mix(in srgb, #d9534f 10%, transparent); color:#d9534f; font-size:13.5px;">ยังไม่มีกระเป๋า TPIX <a href="{{ route('user.tpix.wallet') }}" style="color:inherit; font-weight:700;">เปิดหน้ากระเป๋า</a> เพื่อสร้างก่อน</div>
        @elseif(! $walletActive)
            <div style="padding:13px 15px; border-radius:12px; margin-bottom:18px; background:color-mix(in srgb, #d9534f 10%, transparent); color:#d9534f; font-size:13.5px;">กระเป๋า TPIX ของคุณถูกระงับการใช้งาน ยังส่งไม่ได้</div>
        @elseif(! $canSend)
            <div style="padding:13px 15px; border-radius:12px; margin-bottom:18px; background:color-mix(in srgb, #d9534f 10%, transparent); color:#d9534f; font-size:13.5px;">ยอดคงเหลือไม่พอให้ส่ง ต้องมีอย่างน้อย 0.0001 TPIX</div>
        @endif

        <form action="{{ route('user.tpix.send.process') }}" method="POST"
              x-data="{ amount: @js(old('amount', '')), maxAmount: @js($maxAmount), canSend: @js($canSend), submitting: false }"
              x-on:submit="submitting = true"
              x-on:pageshow.window="submitting = false">
            @csrf
            <input type="hidden" name="transfer_token" value="{{ $transferToken }}">

            {{-- ผู้รับ --}}
            <div style="margin-bottom:18px;">
                <label for="tpix-recipient" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ผู้รับ</label>
                <input id="tpix-recipient" type="text" name="recipient" value="{{ old('recipient') }}" required maxlength="255"
                       autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="email" placeholder="อีเมลสมาชิก หรือที่อยู่กระเป๋า TPIX"
                       style="width:100%; padding:13px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:16px;">
                @error('recipient')
                    <p style="font-size:12.5px; color:#d9534f; margin:7px 0 0;">{{ $message }}</p>
                @else
                    <p style="font-size:12px; color:var(--ink2); margin:7px 0 0;">ส่งได้เฉพาะสมาชิกในระบบ ถ้าจะส่งออกไปกระเป๋าภายนอกให้ใช้หน้าถอน</p>
                @enderror
            </div>

            {{-- จำนวน --}}
            <div style="margin-bottom:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:7px;">
                    <label for="tpix-amount" style="font-size:13px; font-weight:700; color:var(--ink);">จำนวนที่ต้องการส่ง</label>
                    @if($canSend)
                        <button type="button" x-on:click="amount = maxAmount" style="padding:6px 11px; border-radius:9px; background:var(--surf); box-shadow:var(--inset-sm); border:none; color:var(--deep1); font-size:12.5px; font-weight:700; cursor:pointer;">ส่งทั้งหมด</button>
                    @endif
                </div>
                <input id="tpix-amount" type="number" name="amount" x-model="amount" required
                       step="0.00000001" min="0.0001" @if($canSend) max="{{ $maxAmount }}" @endif
                       inputmode="decimal" placeholder="0.00000000"
                       style="width:100%; padding:13px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:16px;">
                @error('amount')
                    <p style="font-size:12.5px; color:#d9534f; margin:7px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- ยืนยันเงื่อนไข --}}
            <label style="display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; cursor:pointer;">
                <input type="checkbox" required style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); flex-shrink:0;">
                <span style="font-size:13px; color:var(--ink2);">ฉันตรวจสอบผู้รับและจำนวนแล้ว และเข้าใจว่าส่งแล้วเรียกคืนไม่ได้</span>
            </label>

            <button type="submit" class="tp-btn" @disabled(! $canSend)
                    x-bind:disabled="submitting || ! canSend"
                    x-bind:style="{ opacity: (submitting || ! canSend) ? .55 : 1, cursor: (submitting || ! canSend) ? 'not-allowed' : 'pointer' }"
                    style="width:100%; min-height:48px; padding:15px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none;">
                <span x-text="submitting ? 'กำลังส่ง...' : 'ส่ง TPIX'">ส่ง TPIX</span>
            </button>
        </form>
    </div>
</div>
@endsection

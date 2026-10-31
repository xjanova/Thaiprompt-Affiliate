@extends('layouts.user-v4')

@section('title', 'ฝาก TPIX - กระเป๋าเงิน TPIX')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:720px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; color:var(--deep1); flex-shrink:0;"><i class="fas fa-download"></i></span>
                <div style="min-width:0;">
                    <h1 style="font-size:clamp(20px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">ฝาก TPIX</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">รับเหรียญเข้ากระเป๋า TPIX ของคุณ</div>
                </div>
            </div>
            <a href="{{ route('user.tpix.wallet') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับกระเป๋า</span>
            </a>
        </div>
    </div>

    {{-- ── ที่อยู่รับฝาก ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div style="font-size:13px; color:var(--ink2); margin-bottom:6px;">ยอดคงเหลือ</div>
        <div style="display:flex; flex-wrap:wrap; align-items:baseline; gap:8px; margin-bottom:18px;">
            <div class="tp-num" style="font-size:clamp(26px,6vw,36px); font-weight:800; color:var(--deep1); line-height:1.1; overflow-wrap:anywhere;">{{ number_format((float) $wallet->balance, 8) }}</div>
            <div style="font-size:16px; color:var(--ink2);">TPIX</div>
        </div>

        @if($canReceive)
            <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 10px;">ที่อยู่รับฝากของคุณ</h2>
            <div x-data="{ copied: false, copy() { navigator.clipboard?.writeText(@js($depositAddress->address)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800); }); } }"
                 style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                <code style="flex:1 1 240px; min-width:0; padding:13px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-size:14px; overflow-wrap:anywhere;">{{ $depositAddress->address }}</code>
                <button type="button" x-on:click="copy()" style="min-height:44px; padding:10px 16px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:none; color:var(--deep1); font-size:13px; font-weight:700; cursor:pointer;">
                    <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอก'">คัดลอก</span>
                </button>
            </div>
            <ul style="margin:14px 0 0; padding-left:18px; color:var(--ink2); font-size:13px; display:flex; flex-direction:column; gap:5px;">
                <li>รับเฉพาะเหรียญ TPIX บนเครือข่าย TPIX เท่านั้น</li>
                <li>ฝากขั้นต่ำ {{ number_format((float) $tpixCurrency->min_deposit, 8) }} TPIX</li>
                <li>ยอดเข้ากระเป๋าหลังเครือข่ายยืนยันครบ {{ (int) $tpixCurrency->confirmations_required }} บล็อก</li>
            </ul>
        @else
            {{-- ที่อยู่ที่ระบบสร้างตอนนี้ไม่มี private key — ห้ามแสดงให้ใครโอนเหรียญจริงเข้ามา --}}
            <div style="padding:14px 16px; border-radius:12px; background:color-mix(in srgb, var(--accent1) 10%, transparent); color:var(--ink); font-size:13.5px;">
                <div style="font-weight:800; margin-bottom:4px;">ยังไม่เปิดรับฝากเหรียญจากภายนอก</div>
                <div style="color:var(--ink2);">ระบบยังไม่มีที่อยู่รับฝากบนเชนสำหรับบัญชีของคุณ กรุณาอย่าโอนเหรียญมาที่ที่อยู่ใดจนกว่าหน้านี้จะแสดงที่อยู่ให้</div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('tpix-pay.topup'))
                <a href="{{ route('tpix-pay.topup') }}" class="tp-btn" style="display:flex; align-items:center; justify-content:center; gap:8px; min-height:48px; margin-top:14px; padding:12px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--deep1); font-weight:700; font-size:14px; text-decoration:none; text-align:center;">
                    <i class="fas fa-coins"></i> <span>ใช้เหรียญ TPIX เติมเงินเข้ากระเป๋าเงินบาท</span>
                </a>
            @endif
        @endif
    </div>

    {{-- ── รายการฝากล่าสุด ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0;">รายการฝากล่าสุด</h2>
        </div>
        @forelse($recentDeposits as $transaction)
            @include('user.tpix.partials.tx-row')
        @empty
            <div style="display:flex; flex-direction:column; align-items:center; padding:36px 24px; text-align:center;">
                <i class="fas fa-receipt" style="font-size:40px; color:color-mix(in srgb, var(--ink2) 40%, transparent); margin-bottom:12px;"></i>
                <p style="font-size:13px; color:var(--ink2); margin:0;">ยังไม่มีรายการฝาก</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

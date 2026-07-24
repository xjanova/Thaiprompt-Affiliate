@extends('layouts.seller-v4')

@section('title', 'ถอนเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:820px; margin-inline:auto; width:100%;">

    {{-- ── Hero + ยอดถอนได้ ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <a href="{{ route('seller.wallet.index') }}" class="tp-btn" style="width:40px; height:40px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); text-decoration:none; flex-shrink:0;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 style="font-size:clamp(19px,3.5vw,24px); font-weight:800; margin:0; color:var(--ink);">💸 ถอนเงิน</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:2px;">ถอนเงินจากกระเป๋าของร้านค้า</div>
            </div>
        </div>
        <div class="tp-card" style="padding:18px 20px; box-shadow:var(--inset-sm);">
            <div style="font-size:12px; color:var(--ink2); margin-bottom:5px;">ยอดเงินที่สามารถถอนได้</div>
            <div class="tp-num" style="font-size:clamp(26px,5vw,34px); font-weight:800; color:var(--deep1);">฿{{ number_format($balance, 2) }}</div>
        </div>
    </div>

    {{-- ── Error / Success ───────────────────────────────────── --}}
    @if($errors->any())
        <div class="tp-card" style="padding:16px 20px; background:color-mix(in srgb, #d9534f 10%, transparent); border:1px solid color-mix(in srgb, #d9534f 32%, transparent);">
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <span style="font-size:20px;">⚠️</span>
                <div>
                    <h3 style="font-weight:800; color:var(--ink); margin:0 0 4px; font-size:14px;">เกิดข้อผิดพลาด</h3>
                    <ul style="margin:0; padding-left:18px; color:#d9534f; font-size:13px;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="tp-card" style="padding:16px 20px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 32%, transparent);">
            <div style="display:flex; gap:12px; align-items:center;">
                <span style="font-size:20px;">✅</span>
                <p style="color:var(--ink); font-weight:600; font-size:14px; margin:0;">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- ── ฟอร์มถอนเงิน ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 20px;">แบบฟอร์มถอนเงิน</h2>

        @if($paymentMethods->isEmpty())
            <div class="tp-card" style="padding:32px 24px; text-align:center; box-shadow:var(--inset-sm);">
                <span style="font-size:48px; display:block; margin-bottom:12px;">🏦</span>
                <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 6px;">ยังไม่มีช่องทางรับเงิน</h3>
                <p style="color:var(--ink2); font-size:13.5px; margin:0 0 18px;">กรุณาเพิ่มช่องทางรับเงินก่อนทำการถอน</p>
                @if(\Illuminate\Support\Facades\Route::has('user.wallet.payment-methods'))
                    <a href="{{ route('user.wallet.payment-methods') }}" class="tp-btn" style="display:inline-block; padding:11px 22px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">เพิ่มช่องทางรับเงิน</a>
                @endif
            </div>
        @elseif($balance <= 0)
            <div class="tp-card" style="padding:32px 24px; text-align:center; box-shadow:var(--inset-sm);">
                <span style="font-size:48px; display:block; margin-bottom:12px;">💰</span>
                <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 6px;">ไม่มียอดเงินที่สามารถถอนได้</h3>
                <p style="color:var(--ink2); font-size:13.5px; margin:0 0 18px;">ยอดเงินในกระเป๋าของคุณเป็น 0 บาท</p>
                <a href="{{ route('seller.dashboard') }}" class="tp-btn" style="display:inline-block; padding:11px 22px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">กลับไปหน้าแดชบอร์ด</a>
            </div>
        @else
            <form method="POST" action="{{ route('seller.wallet.withdraw.submit') }}" style="display:flex; flex-direction:column; gap:18px;">
                @csrf

                <div>
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">เลือกช่องทางรับเงิน</label>
                    <select name="payment_method_id" required
                            style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
                        <option value="">-- เลือกช่องทางรับเงิน --</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>
                                @if($method->type === 'bank_transfer')
                                    🏦 {{ $method->bank_name }} - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'promptpay')
                                    💳 PromptPay - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'paypal')
                                    💰 PayPal - {{ $method->paypal_email }}
                                @else
                                    📌 {{ $method->type }} - {{ $method->account_name }}
                                @endif
                                @if($method->is_default) ⭐ (ค่าเริ่มต้น) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">จำนวนเงิน (บาท)</label>
                    <input type="number" name="amount" step="0.01"
                           min="{{ $withdrawalSettings['min_amount'] }}"
                           max="{{ min($balance, $withdrawalSettings['max_amount']) }}"
                           value="{{ old('amount') }}" required
                           style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                           placeholder="ระบุจำนวนเงินที่ต้องการถอน">
                    <p style="font-size:12px; color:var(--ink2); margin:7px 0 0;">
                        ขั้นต่ำ: ฿{{ number_format($withdrawalSettings['min_amount'], 2) }} |
                        สูงสุด: ฿{{ number_format(min($balance, $withdrawalSettings['max_amount']), 2) }}
                    </p>
                </div>

                <div>
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="user_note" rows="3" maxlength="500"
                              style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px; resize:vertical;"
                              placeholder="หมายเหตุเพิ่มเติม (ไม่เกิน 500 ตัวอักษร)">{{ old('user_note') }}</textarea>
                </div>

                <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm);">
                    <h4 style="font-weight:800; color:var(--ink); margin:0 0 8px; font-size:14px;">📋 เงื่อนไขการถอนเงิน</h4>
                    <ul style="margin:0; padding-left:16px; color:var(--ink2); font-size:13px; display:flex; flex-direction:column; gap:4px;">
                        <li>จำนวนเงินขั้นต่ำในการถอนคือ {{ number_format($withdrawalSettings['min_amount']) }} บาท</li>
                        <li>ระบบจะตรวจสอบและดำเนินการภายใน 1-3 วันทำการ</li>
                        <li>กรุณาตรวจสอบข้อมูลบัญชีให้ถูกต้อง</li>
                        <li>หากพบปัญหา กรุณาติดต่อฝ่ายสนับสนุน</li>
                    </ul>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:12px;">
                    <a href="{{ route('seller.wallet.index') }}" class="tp-btn" style="flex:1; min-width:140px; padding:13px; border-radius:14px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; text-align:center;">ยกเลิก</a>
                    <button type="submit" class="tp-btn" style="flex:1; min-width:180px; padding:13px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14.5px; box-shadow:var(--raise);">ยืนยันการถอนเงิน</button>
                </div>
            </form>
        @endif
    </div>

    {{-- ── คำขอถอนเงินล่าสุด ─────────────────────────────────── --}}
    @if($recentWithdrawals && $recentWithdrawals->count() > 0)
        @php
            $wStatusColor = ['pending' => '#e0a52e', 'processing' => '#5689b8', 'completed' => '#5aa07e', 'rejected' => '#d9534f'];
        @endphp
        <div class="tp-card" style="padding:22px 24px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin:0;">📋 คำขอถอนเงินล่าสุด</h3>
                <a href="{{ route('seller.wallet.withdrawals') }}" style="font-size:13px; color:var(--deep1); font-weight:700; text-decoration:none;">ดูทั้งหมด →</a>
            </div>
            <div style="display:flex; flex-direction:column;">
                @foreach($recentWithdrawals as $withdrawal)
                    @php $wc = $wStatusColor[$withdrawal->status] ?? '#9a8f7c'; @endphp
                    <div style="padding:13px 0; display:flex; align-items:center; justify-content:space-between; gap:12px; {{ !$loop->last ? 'border-bottom:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);' : '' }}">
                        <div>
                            <p style="font-weight:700; color:var(--ink); margin:0; font-size:13.5px;">{{ $withdrawal->request_id }}</p>
                            <p style="font-size:12px; color:var(--ink2); margin:2px 0 0;">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div style="text-align:right;">
                            <p class="tp-num" style="font-weight:800; color:var(--ink); margin:0; font-size:14.5px;">฿{{ number_format($withdrawal->amount, 2) }}</p>
                            <span class="tp-pill" style="background:color-mix(in srgb, {{ $wc }} 18%, transparent); color:{{ $wc }}; font-size:11px; font-weight:700; margin-top:3px;">{{ $withdrawal->status_label }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

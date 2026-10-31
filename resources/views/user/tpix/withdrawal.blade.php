@extends('layouts.user-v4')

@section('title', 'ถอน TPIX - กระเป๋าเงิน TPIX')

@php
    // ใช้ bcmath แบบเดียวกับ processWithdrawal — ยอดเป็น decimal 18 ตำแหน่ง ทศนิยมลอยตัวปัดเศษผิดได้
    $fee = (string) $tpixCurrency->withdrawal_fee;
    $minAmount = (string) $tpixCurrency->min_withdrawal;
    $maxAmount = bcsub((string) $wallet->balance, $fee, 8);
    if ($tpixCurrency->max_withdrawal && bccomp($maxAmount, (string) $tpixCurrency->max_withdrawal, 8) > 0) {
        $maxAmount = (string) $tpixCurrency->max_withdrawal;
    }
    $walletActive = $wallet->isActive();
    // ยอดไม่ถึงขั้นต่ำ → ฟอร์มยังแสดงแต่กดส่งไม่ได้ พร้อมบอกเหตุผล
    $canWithdraw = $walletActive && bccomp($maxAmount, '0', 8) > 0 && bccomp($maxAmount, $minAmount, 8) >= 0;
    $money = fn ($n) => number_format((float) $n, 8);
    $statusLabels = [
        'pending' => ['รอดำเนินการ', 'var(--accent1)'],
        'broadcasted' => ['ส่งเข้าเครือข่ายแล้ว', 'var(--accent1)'],
        'confirming' => ['รอยืนยันบนเครือข่าย', 'var(--accent1)'],
        'confirmed' => ['สำเร็จ', '#5aa07e'],
        'completed' => ['สำเร็จ', '#5aa07e'],
        'failed' => ['ไม่สำเร็จ', '#d9534f'],
        'cancelled' => ['ยกเลิกแล้ว', '#d9534f'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:720px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; color:var(--deep1); flex-shrink:0;"><i class="fas fa-upload"></i></span>
                <div style="min-width:0;">
                    <h1 style="font-size:clamp(20px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">ถอน TPIX</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">โอนเหรียญออกไปยังกระเป๋าภายนอกบนเครือข่าย TPIX</div>
                </div>
            </div>
            <a href="{{ route('user.tpix.wallet') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับกระเป๋า</span>
            </a>
        </div>
    </div>

    {{-- ── ยอดและเงื่อนไข ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div style="font-size:13px; color:var(--ink2); margin-bottom:6px;">ยอดคงเหลือ</div>
        <div style="display:flex; flex-wrap:wrap; align-items:baseline; gap:8px; margin-bottom:16px;">
            <div class="tp-num" style="font-size:clamp(26px,6vw,36px); font-weight:800; color:var(--deep1); line-height:1.1; overflow-wrap:anywhere;">{{ $money($wallet->balance) }}</div>
            <div style="font-size:16px; color:var(--ink2);">TPIX</div>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px;">
            @foreach([
                ['ถอนขั้นต่ำ', $money($minAmount)],
                ['ถอนสูงสุดต่อครั้ง', $tpixCurrency->max_withdrawal ? $money($tpixCurrency->max_withdrawal) : 'ไม่จำกัด'],
                ['ค่าธรรมเนียม', $money($fee)],
            ] as [$label, $value])
                <div class="tp-card" style="padding:12px 14px; box-shadow:var(--inset-sm); min-width:0;">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:3px;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:14px; font-weight:700; color:var(--ink); overflow-wrap:anywhere;">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── ฟอร์มถอน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:26px 24px;">
        <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 20px;">ฟอร์มถอน TPIX</h2>

        @unless($walletActive)
            <div style="padding:13px 15px; border-radius:12px; margin-bottom:18px; background:color-mix(in srgb, #d9534f 10%, transparent); color:#d9534f; font-size:13.5px;">กระเป๋า TPIX ของคุณถูกระงับการใช้งาน ยังถอนไม่ได้</div>
        @elseif(! $canWithdraw)
            <div style="padding:13px 15px; border-radius:12px; margin-bottom:18px; background:color-mix(in srgb, #d9534f 10%, transparent); color:#d9534f; font-size:13.5px;">ยอดคงเหลือไม่ถึงขั้นต่ำ ต้องมีอย่างน้อย {{ $money(bcadd($minAmount, $fee, 8)) }} TPIX รวมค่าธรรมเนียม</div>
        @endunless

        <form action="{{ route('user.tpix.withdrawal.process') }}" method="POST"
              x-data="{ amount: @js(old('amount', '')), fee: {{ (float) $fee }}, maxAmount: @js(rtrim(rtrim($maxAmount, '0'), '.')), canWithdraw: @js($canWithdraw), submitting: false }"
              x-on:submit="submitting = true"
              x-on:pageshow.window="submitting = false">
            @csrf

            {{-- ที่อยู่ปลายทาง --}}
            <div style="margin-bottom:18px;">
                <label for="tpix-address" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ที่อยู่ปลายทาง</label>
                <input id="tpix-address" type="text" name="address" value="{{ old('address') }}" required
                       maxlength="42" pattern="0x[a-fA-F0-9]{40}" title="ที่อยู่ต้องขึ้นต้นด้วย 0x ตามด้วยอักขระ 40 ตัว"
                       autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="0x..."
                       style="width:100%; padding:13px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-family:monospace; font-size:16px;">
                @error('address')
                    <p style="font-size:12.5px; color:#d9534f; margin:7px 0 0;">{{ $message }}</p>
                @else
                    <p style="font-size:12px; color:var(--ink2); margin:7px 0 0;">ตรวจสอบที่อยู่ให้ถูกต้อง โอนไปที่อยู่ผิดแล้วกู้คืนไม่ได้</p>
                @enderror
            </div>

            {{-- จำนวน --}}
            <div style="margin-bottom:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:7px;">
                    <label for="tpix-amount" style="font-size:13px; font-weight:700; color:var(--ink);">จำนวนที่ต้องการถอน</label>
                    @if($canWithdraw)
                        <button type="button" x-on:click="amount = maxAmount" style="padding:6px 11px; border-radius:9px; background:var(--surf); box-shadow:var(--inset-sm); border:none; color:var(--deep1); font-size:12.5px; font-weight:700; cursor:pointer;">ถอนสูงสุด</button>
                    @endif
                </div>
                <input id="tpix-amount" type="number" name="amount" x-model="amount" required
                       step="0.00000001" min="{{ $minAmount }}" @if($canWithdraw) max="{{ $maxAmount }}" @endif
                       inputmode="decimal" placeholder="0.00000000"
                       style="width:100%; padding:13px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:16px;">
                @error('amount')
                    <p style="font-size:12.5px; color:#d9534f; margin:7px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- ยอดที่จะถูกหัก --}}
            <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm); margin-bottom:18px;" x-show="parseFloat(amount) > 0" x-cloak>
                <div style="display:flex; flex-direction:column; gap:8px; font-size:13.5px;">
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--ink2);">ปลายทางจะได้รับ</span>
                        <span class="tp-num" style="font-weight:700; color:var(--ink);"><span x-text="(parseFloat(amount) || 0).toFixed(8)"></span> TPIX</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; gap:12px;">
                        <span style="color:var(--ink2);">ค่าธรรมเนียม</span>
                        <span class="tp-num" style="font-weight:700; color:var(--ink);"><span x-text="fee.toFixed(8)"></span> TPIX</span>
                    </div>
                    <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); padding-top:8px; display:flex; justify-content:space-between; gap:12px;">
                        <span style="font-weight:800; color:var(--ink);">หักจากกระเป๋า</span>
                        <span class="tp-num" style="font-weight:800; color:var(--deep1);"><span x-text="((parseFloat(amount) || 0) + fee).toFixed(8)"></span> TPIX</span>
                    </div>
                </div>
            </div>

            {{-- ยืนยันเงื่อนไข --}}
            <label style="display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; cursor:pointer;">
                <input type="checkbox" required style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); flex-shrink:0;">
                <span style="font-size:13px; color:var(--ink2);">ฉันตรวจสอบที่อยู่ปลายทางและจำนวนแล้ว และเข้าใจว่าระบบจะหักยอดทันทีเมื่อส่งคำขอ</span>
            </label>

            {{-- route ตรวจ turnstile:tpix_withdrawal --}}
            <x-turnstile point="tpix_withdrawal" />

            <button type="submit" class="tp-btn" @disabled(! $canWithdraw)
                    x-bind:disabled="submitting || ! canWithdraw"
                    x-bind:style="{ opacity: (submitting || ! canWithdraw) ? .55 : 1, cursor: (submitting || ! canWithdraw) ? 'not-allowed' : 'pointer' }"
                    style="width:100%; min-height:48px; padding:15px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none;">
                <span x-text="submitting ? 'กำลังส่งคำขอ...' : 'ส่งคำขอถอน'">ส่งคำขอถอน</span>
            </button>
        </form>
    </div>

    {{-- ── รายการถอนล่าสุด ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0;">รายการถอนล่าสุด</h2>
        </div>
        @forelse($recentWithdrawals as $withdrawal)
            @php [$statusLabel, $statusColor] = $statusLabels[$withdrawal->status] ?? [$withdrawal->status, 'var(--ink2)']; @endphp
            <div style="padding:14px 22px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 9%, transparent); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px 14px;">
                <div style="min-width:0;">
                    <div class="tp-num" style="font-weight:700; color:var(--ink); font-size:14px;">{{ $money($withdrawal->amount) }} TPIX</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:2px;">
                        <span style="font-family:monospace;">{{ substr((string) $withdrawal->to_address, 0, 6) }}...{{ substr((string) $withdrawal->to_address, -4) }}</span>
                        · {{ $withdrawal->created_at->format('d/m/Y H:i') }}
                    </div>
                    @if($withdrawal->status === 'cancelled' && $withdrawal->error_message)
                        <div style="font-size:12px; color:var(--ink2); margin-top:4px; overflow-wrap:anywhere;">ระบบคืนยอดแล้ว · เหตุผล: {{ $withdrawal->error_message }}</div>
                    @endif
                    @if($withdrawal->tx_hash)
                        <div style="font-size:12px; color:var(--ink2); margin-top:4px; font-family:monospace; overflow-wrap:anywhere;">tx {{ $withdrawal->tx_hash }}</div>
                    @endif
                </div>
                <span class="tp-pill" style="background:color-mix(in srgb, {{ $statusColor }} 16%, transparent); color:{{ $statusColor }}; font-size:11.5px; font-weight:700;">{{ $statusLabel }}</span>
            </div>
        @empty
            <div style="display:flex; flex-direction:column; align-items:center; padding:36px 24px; text-align:center;">
                <i class="fas fa-receipt" style="font-size:40px; color:color-mix(in srgb, var(--ink2) 40%, transparent); margin-bottom:12px;"></i>
                <p style="font-size:13px; color:var(--ink2); margin:0;">ยังไม่มีรายการถอน</p>
            </div>
        @endforelse
    </div>

    {{-- ── คำเตือน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm); background:color-mix(in srgb, #d9534f 8%, transparent);">
        <h3 style="font-weight:800; color:#d9534f; margin:0 0 10px; font-size:14.5px; display:flex; align-items:center; gap:8px;"><i class="fas fa-exclamation-triangle"></i> ก่อนถอน</h3>
        <ul style="margin:0; padding-left:18px; color:#d9534f; font-size:13px; display:flex; flex-direction:column; gap:5px;">
            <li>รับเฉพาะที่อยู่บนเครือข่าย TPIX เท่านั้น</li>
            <li>โอนไปที่อยู่ผิดหรือเครือข่ายผิด เหรียญจะสูญหายถาวร</li>
            <li>หลังส่งคำขอ รายการจะอยู่ในสถานะรอดำเนินการ</li>
        </ul>
    </div>
</div>
@endsection

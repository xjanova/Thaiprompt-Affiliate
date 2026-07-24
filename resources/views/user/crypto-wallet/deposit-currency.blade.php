@extends('layouts.user-v4')

@section('title', 'ฝาก ' . $currency->code)

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:720px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    @php $iconPath = public_path('icons/cryptocurrency/' . strtolower($currency->code) . '.svg'); @endphp
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                @if(file_exists($iconPath))
                    <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center;"><img src="{{ asset('icons/cryptocurrency/' . strtolower($currency->code) . '.svg') }}" alt="{{ $currency->code }}" style="width:38px; height:38px;"></span>
                @else
                    <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-coins"></i></span>
                @endif
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">📥 ฝาก {{ $currency->code }}</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">{{ $currency->name }} ({{ strtoupper($currency->network) }})</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.deposit') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>เลือกสกุลเงินอื่น</span>
            </a>
        </div>
    </div>

    {{-- ── ที่อยู่ + QR ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:28px 24px;">
        <div style="text-align:center; margin-bottom:22px;">
            <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 5px;">ที่อยู่กระเป๋าสำหรับฝาก</h3>
            <p style="font-size:13px; color:var(--ink2); margin:0;">สแกน QR Code หรือคัดลอกที่อยู่ด้านล่าง</p>
        </div>

        <div style="display:flex; justify-content:center; margin-bottom:22px;">
            <div class="tp-card" style="padding:20px; box-shadow:var(--inset-sm);">
                @if($depositAddress && $depositAddress->qr_code)
                    <img src="{{ $depositAddress->qr_code }}" alt="QR Code" style="width:240px; height:240px; display:block;">
                @else
                    <div style="width:240px; height:240px; display:flex; align-items:center; justify-content:center; background:color-mix(in srgb, var(--ink2) 10%, transparent); border-radius:12px;">
                        <div style="text-align:center;"><div style="font-size:36px; margin-bottom:8px;">📱</div><p style="font-size:13px; color:var(--ink2); margin:0;">QR Code จะสร้างเร็วๆ นี้</p></div>
                    </div>
                @endif
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ที่อยู่กระเป๋า (Wallet Address)</label>
            <div style="display:flex; gap:9px; flex-wrap:wrap;">
                <input type="text" id="depositAddress" value="{{ $depositAddress->address }}" readonly
                       style="flex:1; min-width:200px; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-family:monospace; font-size:13px;">
                <button type="button" onclick="copyAddress()" class="tp-btn" style="padding:12px 20px; border-radius:12px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise); border:none; cursor:pointer; white-space:nowrap;">📋 คัดลอก</button>
            </div>
            <p id="copySuccess" class="hidden" style="color:#5aa07e; font-size:13px; margin:8px 0 0;">✓ คัดลอกแล้ว!</p>
        </div>

        <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm);">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; font-size:13px;">
                <div><span style="color:var(--ink2);">เครือข่าย:</span> <span style="font-weight:700; color:var(--ink); text-transform:uppercase;">{{ $currency->network }}</span></div>
                <div><span style="color:var(--ink2);">Token Standard:</span> <span style="font-weight:700; color:var(--ink);">{{ $currency->token_standard }}</span></div>
                @if($currency->contract_address)
                    <div style="grid-column:1/-1;"><span style="color:var(--ink2);">Contract Address:</span><div style="font-family:monospace; font-size:11.5px; color:var(--ink); margin-top:3px; word-break:break-all;">{{ $currency->contract_address }}</div></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลการฝาก ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm);">
        <h4 style="font-weight:800; color:var(--ink); margin:0 0 12px; font-size:14.5px;">ข้อมูลการฝาก</h4>
        <div style="display:flex; flex-direction:column; gap:8px; font-size:13.5px;">
            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ฝากขั้นต่ำ:</span><span style="font-weight:700; color:var(--ink);">{{ number_format($currency->min_deposit, 8) }} {{ $currency->code }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">การยืนยันที่ต้องการ:</span><span style="font-weight:700; color:var(--ink);">{{ $currency->confirmations_required }} confirmations</span></div>
            <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">เวลาโดยประมาณ:</span><span style="font-weight:700; color:var(--ink);">
                @if($currency->network === 'bitcoin') ~60 นาที
                @elseif($currency->network === 'ethereum') ~15-30 นาที
                @elseif($currency->network === 'bsc') ~3-5 นาที
                @elseif($currency->network === 'polygon') ~5-10 นาที
                @else ขึ้นอยู่กับเครือข่าย @endif
            </span></div>
        </div>
    </div>

    {{-- ── สถิติการฝาก ─────────────────────────────────────── --}}
    @if($depositAddress->total_deposits > 0)
        <div class="tp-card" style="padding:20px 22px;">
            <h4 style="font-weight:800; color:var(--ink); margin:0 0 12px; font-size:14.5px;">สถิติการฝาก</h4>
            <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:12px; text-align:center;">
                <div class="tp-card" style="padding:16px; box-shadow:var(--inset-sm);">
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">{{ $depositAddress->total_deposits }}</div>
                    <div style="font-size:12.5px; color:var(--ink2);">จำนวนครั้ง</div>
                </div>
                <div class="tp-card" style="padding:16px; box-shadow:var(--inset-sm);">
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">{{ number_format($depositAddress->total_amount_received, 8) }}</div>
                    <div style="font-size:12.5px; color:var(--ink2);">ยอดรวม {{ $currency->code }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── คำเตือน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm); background:color-mix(in srgb, #d9534f 8%, transparent);">
        <h4 style="font-weight:800; color:#d9534f; margin:0 0 12px; font-size:14.5px; display:flex; align-items:center; gap:8px;"><span>⚠️</span> คำเตือนสำคัญ!</h4>
        <ul style="margin:0; padding-left:18px; color:#d9534f; font-size:13px; display:flex; flex-direction:column; gap:6px;">
            <li>ส่งเฉพาะ <strong>{{ $currency->code }}</strong> บนเครือข่าย <strong>{{ strtoupper($currency->network) }}</strong> เท่านั้น</li>
            <li>การส่งเหรียญอื่นหรือใช้เครือข่ายผิด จะทำให้<strong>สูญหายถาวร</strong></li>
            <li>ต้องส่งมากกว่า <strong>{{ number_format($currency->min_deposit, 8) }} {{ $currency->code }}</strong> ขึ้นไป</li>
            <li>เหรียญจะเข้าอัตโนมัติหลังได้รับการยืนยันครบ</li>
            @if($currency->explorer_url)<li>ตรวจสอบสถานะได้ที่: <a href="{{ $currency->explorer_url }}" target="_blank" style="text-decoration:underline; color:#d9534f;">Block Explorer</a></li>@endif
        </ul>
    </div>
</div>

<script>
function copyAddress() {
    const addressInput = document.getElementById('depositAddress');
    addressInput.select();
    document.execCommand('copy');
    const successMsg = document.getElementById('copySuccess');
    successMsg.classList.remove('hidden');
    setTimeout(() => { successMsg.classList.add('hidden'); }, 3000);
}
</script>
@endsection

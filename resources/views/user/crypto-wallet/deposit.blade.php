@extends('layouts.user-v4')

@section('title', 'ฝากเหรียญคริปโต')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-arrow-down"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">📥 ฝากเหรียญคริปโต</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">เลือกสกุลเงินที่ต้องการฝาก</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    {{-- ── วิธีการฝาก ─────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm);">
        <h3 style="font-weight:800; color:var(--ink); margin:0 0 12px; font-size:14.5px; display:flex; align-items:center; gap:8px;"><span>ℹ️</span> วิธีการฝากเหรียญ</h3>
        <ol style="margin:0; padding-left:20px; color:var(--ink2); font-size:13.5px; display:flex; flex-direction:column; gap:6px;">
            <li>เลือกสกุลเงินที่ต้องการฝาก</li>
            <li>คัดลอกที่อยู่กระเป๋า (Wallet Address) หรือสแกน QR Code</li>
            <li>โอนเหรียญจากกระเป๋าภายนอก (Exchange, MetaMask, ฯลฯ)</li>
            <li>รอการยืนยันจาก Blockchain (ใช้เวลาขึ้นอยู่กับแต่ละเชน)</li>
            <li>เหรียญจะเข้ากระเป๋าอัตโนมัติเมื่อได้รับการยืนยันเพียงพอ</li>
        </ol>
    </div>

    {{-- ── เลือกสกุลเงิน ─────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px;">
        @foreach($currencies as $currency)
            @php $iconPath = public_path('icons/cryptocurrency/' . strtolower($currency->code) . '.svg'); @endphp
            <a href="{{ route('user.crypto-wallet.deposit.currency', $currency->code) }}" class="tp-card" style="padding:20px 22px; text-decoration:none;">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">
                    @if(file_exists($iconPath))
                        <img src="{{ asset('icons/cryptocurrency/' . strtolower($currency->code) . '.svg') }}" alt="{{ $currency->code }}" style="width:52px; height:52px; flex-shrink:0;">
                    @else
                        <span class="tp-tile" style="width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:20px; color:var(--deep1); flex-shrink:0;">{{ substr($currency->code, 0, 1) }}</span>
                    @endif
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin:0;">{{ $currency->code }}</h3>
                        <p style="font-size:12.5px; color:var(--ink2); margin:2px 0 0;">{{ $currency->name }}</p>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:14px; font-size:13px;">
                    <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">เครือข่าย:</span><span style="font-weight:600; color:var(--ink); text-transform:uppercase;">{{ $currency->network }}</span></div>
                    <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ฝากขั้นต่ำ:</span><span style="font-weight:600; color:var(--ink);">{{ number_format($currency->min_deposit, 8) }} {{ $currency->code }}</span></div>
                    <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">การยืนยัน:</span><span style="font-weight:600; color:var(--ink);">{{ $currency->confirmations_required }} blocks</span></div>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    @if($currency->deposit_enabled)
                        <span style="color:#5aa07e; font-size:13px; font-weight:600;">✓ พร้อมใช้งาน</span>
                    @else
                        <span style="color:#d9534f; font-size:13px; font-weight:600;">✕ ปิดใช้งานชั่วคราว</span>
                    @endif
                    <span style="color:var(--deep1); font-weight:700; font-size:13.5px;">เลือก →</span>
                </div>
            </a>
        @endforeach
    </div>

    @if($currencies->isEmpty())
        <div class="tp-card" style="padding:52px 24px; text-align:center;">
            <div style="font-size:56px; margin-bottom:14px;">🚫</div>
            <p style="color:var(--ink2); font-size:14px; margin:0;">ไม่มีสกุลเงินที่สามารถฝากได้ในขณะนี้</p>
        </div>
    @endif

    {{-- ── ข้อควรระวัง ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm); background:color-mix(in srgb, #e0a52e 8%, transparent);">
        <h4 style="font-weight:800; color:#c98a1e; margin:0 0 10px; font-size:14.5px; display:flex; align-items:center; gap:8px;"><span>⚠️</span> ข้อควรระวัง</h4>
        <ul style="margin:0; padding-left:18px; color:#a9741a; font-size:13px; display:flex; flex-direction:column; gap:5px;">
            <li>โปรดตรวจสอบเครือข่าย (Network) ให้ถูกต้องก่อนโอน มิฉะนั้นเหรียญอาจสูญหาย</li>
            <li>ส่งเฉพาะสกุลเงินที่ตรงกันเท่านั้น เช่น ที่อยู่ BTC รับได้เฉพาะ BTC</li>
            <li>ระบบจะเริ่มนับการยืนยันหลังจากธุรกรรมปรากฏบน Blockchain</li>
            <li>อย่าส่งเหรียญจากสัญญา Smart Contract โดยตรง อาจทำให้เหรียญสูญหาย</li>
        </ul>
    </div>
</div>
@endsection

@extends('layouts.user-v4')

@section('title', 'ประวัติการแลกเปลี่ยน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-exchange-alt"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">💱 ประวัติการแลกเปลี่ยน</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">ธุรกรรมแลกเปลี่ยน THB ↔ Crypto ทั้งหมด</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.exchange') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าแลกเปลี่ยน</span>
            </a>
        </div>
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px;">
        <form method="GET" action="{{ route('user.crypto-wallet.exchange.history') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">ประเภท</label>
                <select name="type" style="width:100%; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    <option value="">ทั้งหมด</option>
                    <option value="buy" {{ request('type') === 'buy' ? 'selected' : '' }}>ซื้อ Crypto</option>
                    <option value="sell" {{ request('type') === 'sell' ? 'selected' : '' }}>ขาย Crypto</option>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">สกุลเงิน</label>
                <select name="currency" style="width:100%; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    <option value="">ทั้งหมด</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}" {{ request('currency') === $curr->code ? 'selected' : '' }}>{{ $curr->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">สถานะ</label>
                <select name="status" style="width:100%; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    <option value="">ทั้งหมด</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                </select>
            </div>
            <button type="submit" class="tp-btn" style="padding:11px; border-radius:12px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">🔍 กรอง</button>
        </form>
    </div>

    {{-- ── รายการ ─────────────────────────────────────────────── --}}
    @forelse($exchanges as $exchange)
        @php
            $isBuy = $exchange->type === 'buy';
            $exStatusColor = ['completed' => '#5aa07e', 'pending' => '#e0a52e', 'failed' => '#d9534f'];
            $exc = $exStatusColor[$exchange->status] ?? '#9a8f7c';
            $exLabel = ['completed' => '✓ สำเร็จ', 'pending' => '⏳ รอดำเนินการ', 'failed' => '✕ ล้มเหลว'][$exchange->status] ?? $exchange->status;
        @endphp
        <div class="tp-card" style="padding:22px 24px;">
            {{-- head --}}
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px;">{{ $isBuy ? '🛒' : '💰' }}</span>
                    <div>
                        <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0;">{{ $isBuy ? 'ซื้อ ' . $exchange->to_currency_code : 'ขาย ' . $exchange->from_currency_code }}</h3>
                        <p style="font-size:12.5px; color:var(--ink2); margin:2px 0 0;">{{ $exchange->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <span class="tp-pill" style="background:color-mix(in srgb, {{ $exc }} 18%, transparent); color:{{ $exc }}; font-size:12px; font-weight:700;">{{ $exLabel }}</span>
            </div>

            {{-- from → to --}}
            <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm); margin-bottom:14px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="text-align:center; flex:1;">
                        <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">จาก</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ number_format($exchange->from_amount, $exchange->from_currency_type === 'crypto' ? 8 : 2) }}</div>
                        <div style="display:flex; align-items:center; justify-content:center; gap:6px; margin-top:6px;">
                            @php $fromCode = strtolower($exchange->from_currency_code); $fromIconPath = public_path('icons/cryptocurrency/' . $fromCode . '.svg'); @endphp
                            @if($exchange->from_currency_type === 'crypto' && file_exists($fromIconPath))<img src="{{ asset('icons/cryptocurrency/' . $fromCode . '.svg') }}" alt="{{ $exchange->from_currency_code }}" style="width:20px; height:20px;">@endif
                            <span style="font-size:13px; font-weight:600; color:var(--ink2);">{{ $exchange->from_currency_code }}</span>
                        </div>
                    </div>
                    <div style="font-size:22px; color:var(--accent1); padding:0 12px;">→</div>
                    <div style="text-align:center; flex:1;">
                        <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">ไปยัง</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:{{ $isBuy ? '#5aa07e' : '#5689b8' }};">{{ number_format($exchange->to_amount, $exchange->to_currency_type === 'crypto' ? 8 : 2) }}</div>
                        <div style="display:flex; align-items:center; justify-content:center; gap:6px; margin-top:6px;">
                            @php $toCode = strtolower($exchange->to_currency_code); $toIconPath = public_path('icons/cryptocurrency/' . $toCode . '.svg'); @endphp
                            @if($exchange->to_currency_type === 'crypto' && file_exists($toIconPath))<img src="{{ asset('icons/cryptocurrency/' . $toCode . '.svg') }}" alt="{{ $exchange->to_currency_code }}" style="width:20px; height:20px;">@endif
                            <span style="font-size:13px; font-weight:600; color:var(--ink2);">{{ $exchange->to_currency_code }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- details --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:8px 20px; font-size:13px;">
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">อัตราแลกเปลี่ยน:</span><span style="font-weight:700; color:var(--ink);">1 {{ $exchange->to_currency_type === 'crypto' ? $exchange->to_currency_code : $exchange->from_currency_code }} = ฿{{ number_format($exchange->exchange_rate, 2) }}</span></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Exchange ID:</span><span style="font-family:monospace; font-size:11.5px; color:var(--ink);">{{ substr($exchange->exchange_id, 0, 16) }}...</span></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ค่าธรรมเนียม:</span><span style="color:#d9534f;">{{ number_format($exchange->fee_amount, 2) }} {{ $exchange->fee_currency }} ({{ number_format($exchange->fee_percentage, 2) }}%)</span></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">เวลาทำรายการ:</span><span style="color:var(--ink);">{{ $exchange->created_at->diffForHumans() }}</span></div>
            </div>

            {{-- final --}}
            <div style="margin-top:14px; padding-top:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <span style="color:var(--ink2); font-weight:600; font-size:13.5px;">{{ $isBuy ? 'จำนวนที่ได้รับ:' : 'จำนวนที่ได้รับ (หลังหักค่าธรรมเนียม):' }}</span>
                <span class="tp-num" style="font-size:18px; font-weight:800; color:{{ $isBuy ? '#5aa07e' : '#5689b8' }};">{{ number_format($exchange->final_to_amount, $exchange->to_currency_type === 'crypto' ? 8 : 2) }} {{ $exchange->to_currency_code }}</span>
            </div>

            @if($exchange->status === 'failed' && $exchange->error_message)
                <div class="tp-card" style="margin-top:14px; padding:12px 14px; box-shadow:var(--inset-sm); background:color-mix(in srgb, #d9534f 8%, transparent);">
                    <div style="font-size:13px; font-weight:700; color:#d9534f; margin-bottom:3px;">ข้อผิดพลาด:</div>
                    <div style="font-size:13px; color:#d9534f;">{{ $exchange->error_message }}</div>
                </div>
            @endif
        </div>
    @empty
        <div class="tp-card" style="padding:52px 24px; text-align:center;">
            <div style="font-size:56px; margin-bottom:14px;">💱</div>
            <p style="color:var(--ink2); font-size:14px; margin:0 0 20px;">ยังไม่มีประวัติการแลกเปลี่ยน</p>
            <a href="{{ route('user.crypto-wallet.exchange') }}" class="tp-btn" style="display:inline-block; padding:11px 24px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">เริ่มแลกเปลี่ยน</a>
        </div>
    @endforelse

    @if($exchanges->hasPages())
        <div>{{ $exchanges->links() }}</div>
    @endif
</div>
@endsection

@extends('layouts.user-v4')

@section('title', 'รายละเอียดธุรกรรม Crypto')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:860px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; align-items:center; gap:14px;">
            <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:26px;">🔍</span>
            <div>
                <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">รายละเอียดธุรกรรม</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:2px;">ข้อมูลครบถ้วนของธุรกรรม Crypto</div>
            </div>
        </div>
    </div>

    @if(isset($transaction))
        @php
            $st = $transaction->status;
            $stColor = ['completed' => '#5aa07e', 'pending' => '#e0a52e', 'failed' => '#d9534f'][$st] ?? '#9a8f7c';
            $stIcon = ['completed' => '✅', 'pending' => '⏳', 'failed' => '❌'][$st] ?? '📋';
            $stTitle = ['completed' => 'ธุรกรรมสำเร็จ', 'pending' => 'กำลังดำเนินการ', 'failed' => 'ธุรกรรมล้มเหลว'][$st] ?? 'ธุรกรรม';
        @endphp

        {{-- ── สถานะ ─────────────────────────────────────────── --}}
        <div class="tp-card" style="padding:28px 24px; text-align:center;">
            <span class="tp-tile" style="width:88px; height:88px; margin:0 auto 16px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:44px; background:color-mix(in srgb, {{ $stColor }} 18%, transparent);">{{ $stIcon }}</span>
            <h2 style="font-size:22px; font-weight:800; color:var(--ink); margin:0 0 5px;">{{ $stTitle }}</h2>
            <p style="font-size:13.5px; color:var(--ink2); margin:0;">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</p>
        </div>

        {{-- ── ข้อมูลธุรกรรม ─────────────────────────────────── --}}
        <div class="tp-card" style="padding:22px 24px;">
            <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 18px; display:flex; align-items:center; gap:8px;"><span>📋</span> ข้อมูลธุรกรรม</h3>
            @php
                $rowStyle = 'display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 0; border-bottom:1px solid color-mix(in srgb, var(--ink2) 14%, transparent);';
                $codeStyle = 'padding:5px 10px; background:var(--surf); box-shadow:var(--inset-sm); border-radius:8px; font-family:monospace; font-size:12.5px; color:var(--ink);';
            @endphp
            <div>
                <div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">รหัสธุรกรรม:</span><code style="{{ $codeStyle }}">{{ $transaction->transaction_hash ?? $transaction->id }}</code></div>
                <div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">ประเภท:</span>
                    <span style="font-weight:700; font-size:14px; color:{{ $transaction->type === 'deposit' ? '#5aa07e' : ($transaction->type === 'withdraw' ? '#d9534f' : '#5689b8') }};">
                        @if($transaction->type === 'deposit') 💰 ฝาก
                        @elseif($transaction->type === 'withdraw') 📤 ถอน
                        @elseif($transaction->type === 'transfer') 🔄 โอน
                        @elseif($transaction->type === 'exchange') 💱 แลกเปลี่ยน
                        @else {{ ucfirst($transaction->type) }} @endif
                    </span>
                </div>
                <div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">สกุลเงิน:</span><span style="font-weight:700; color:var(--ink); font-size:14px;">{{ strtoupper($transaction->currency ?? 'N/A') }}</span></div>
                <div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">จำนวน:</span><span class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ number_format($transaction->amount ?? 0, 8) }} {{ strtoupper($transaction->currency ?? '') }}</span></div>
                @if(isset($transaction->fee))<div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">ค่าธรรมเนียม:</span><span style="font-weight:700; color:#e0a52e; font-size:14px;">{{ number_format($transaction->fee, 8) }} {{ strtoupper($transaction->currency ?? '') }}</span></div>@endif
                @if(isset($transaction->net_amount))<div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">จำนวนสุทธิ:</span><span class="tp-num" style="font-size:17px; font-weight:800; color:#5aa07e;">{{ number_format($transaction->net_amount, 8) }} {{ strtoupper($transaction->currency ?? '') }}</span></div>@endif
                @if(isset($transaction->from_address))<div style="{{ $rowStyle }} align-items:flex-start;"><span style="color:var(--ink2); font-size:13.5px;">จาก:</span><code style="{{ $codeStyle }} word-break:break-all; max-width:60%; text-align:right;">{{ $transaction->from_address }}</code></div>@endif
                @if(isset($transaction->to_address))<div style="{{ $rowStyle }} align-items:flex-start;"><span style="color:var(--ink2); font-size:13.5px;">ถึง:</span><code style="{{ $codeStyle }} word-break:break-all; max-width:60%; text-align:right;">{{ $transaction->to_address }}</code></div>@endif
                <div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">สถานะ:</span>
                    <span class="tp-pill" style="background:color-mix(in srgb, {{ $stColor }} 18%, transparent); color:{{ $stColor }}; font-size:12.5px; font-weight:700;">
                        @if($st === 'completed') ✅ สำเร็จ @elseif($st === 'pending') ⏳ รอดำเนินการ @elseif($st === 'failed') ❌ ล้มเหลว @else {{ $st }} @endif
                    </span>
                </div>
                @if(isset($transaction->confirmations))<div style="{{ $rowStyle }}"><span style="color:var(--ink2); font-size:13.5px;">Confirmations:</span><span style="font-weight:700; color:var(--ink); font-size:14px;">{{ $transaction->confirmations }} / {{ $transaction->required_confirmations ?? 6 }}</span></div>@endif
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 0;">
                    <span style="color:var(--ink2); font-size:13.5px;">วันที่ทำรายการ:</span>
                    <div style="text-align:right;"><div style="font-weight:700; color:var(--ink); font-size:14px;">{{ $transaction->created_at->format('d/m/Y') }}</div><div style="font-size:12px; color:var(--ink2);">{{ $transaction->created_at->format('H:i:s') }}</div></div>
                </div>
            </div>
        </div>

        {{-- ── หมายเหตุ ─────────────────────────────────────── --}}
        @if(isset($transaction->notes) && $transaction->notes)
            <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm);">
                <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin:0 0 10px; display:flex; align-items:center; gap:8px;"><span>📝</span> หมายเหตุ</h3>
                <p style="color:var(--ink2); font-size:14px; margin:0;">{{ $transaction->notes }}</p>
            </div>
        @endif

        {{-- ── Explorer ─────────────────────────────────────── --}}
        @if(isset($transaction->transaction_hash))
            <div class="tp-card" style="padding:20px 22px;">
                <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin:0 0 14px; display:flex; align-items:center; gap:8px;"><span>🔗</span> Blockchain Explorer</h3>
                <a href="https://etherscan.io/tx/{{ $transaction->transaction_hash }}" target="_blank"
                   class="tp-btn" style="display:block; padding:14px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; text-align:center; text-decoration:none; box-shadow:var(--raise);">🔍 ดูบน Blockchain Explorer →</a>
                <p style="font-size:11.5px; color:var(--ink2); text-align:center; margin:8px 0 0;">ตรวจสอบรายละเอียดบน Blockchain</p>
            </div>
        @endif

        {{-- ── Actions ──────────────────────────────────────── --}}
        <div style="display:flex; flex-wrap:wrap; gap:12px;">
            <a href="{{ route('user.crypto-wallet.transactions') }}" class="tp-btn" style="flex:1; min-width:180px; padding:14px; border-radius:14px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; text-align:center; text-decoration:none;">← กลับไปรายการธุรกรรม</a>
            @if($transaction->status === 'pending')
                <button type="button" onclick="refreshTransaction()" class="tp-btn" style="flex:1; min-width:160px; padding:14px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; box-shadow:var(--raise); border:none; cursor:pointer;">🔄 รีเฟรชสถานะ</button>
            @endif
        </div>
    @else
        <div class="tp-card" style="padding:52px 24px; text-align:center;">
            <span style="font-size:56px; display:block; margin-bottom:14px;">❌</span>
            <h2 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 6px;">ไม่พบข้อมูลธุรกรรม</h2>
            <p style="color:var(--ink2); font-size:14px; margin:0 0 22px;">ไม่สามารถโหลดข้อมูลธุรกรรมได้</p>
            <a href="{{ route('user.crypto-wallet.transactions') }}" class="tp-btn" style="display:inline-block; padding:11px 24px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">← กลับไปรายการธุรกรรม</a>
        </div>
    @endif
</div>

@push('scripts')
<script>
function refreshTransaction() {
    window.location.reload();
}
</script>
@endpush
@endsection

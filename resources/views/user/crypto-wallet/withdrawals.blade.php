@extends('layouts.user-v4')

@section('title', 'ประวัติการถอนเหรียญ')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-history"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">📜 ประวัติการถอนเหรียญ</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">คำขอถอนเหรียญทั้งหมด</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 30%, transparent); color:var(--ink); font-size:14px;">{{ session('success') }}</div>
    @endif

    {{-- ── รายการถอน ─────────────────────────────────────────── --}}
    @forelse($withdrawals as $withdrawal)
        @php
            $wStatus = ['pending' => ['#e0a52e', '⏳ รอดำเนินการ'], 'approved' => ['#5689b8', '👍 อนุมัติแล้ว'], 'completed' => ['#5aa07e', '✓ สำเร็จ'], 'rejected' => ['#d9534f', '✕ ถูกปฏิเสธ'], 'cancelled' => ['#9a8f7c', 'ยกเลิก']];
            [$wc, $wLabel] = $wStatus[$withdrawal->status] ?? ['#9a8f7c', $withdrawal->status];
        @endphp
        <div class="tp-card" style="padding:22px 24px;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px;">
                <div>
                    <div style="display:flex; align-items:center; gap:9px; margin-bottom:5px; flex-wrap:wrap;">
                        <h3 class="tp-num" style="font-size:19px; font-weight:800; color:var(--ink); margin:0;">{{ number_format($withdrawal->amount, 8) }} {{ $withdrawal->currency->code }}</h3>
                        <span class="tp-pill" style="background:color-mix(in srgb, {{ $wc }} 18%, transparent); color:{{ $wc }}; font-size:12px; font-weight:700;">{{ $wLabel }}</span>
                    </div>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0;">Request ID: {{ $withdrawal->request_id }}</p>
                </div>
                <div style="text-align:right; font-size:12.5px; color:var(--ink2);">
                    <div>{{ $withdrawal->created_at->format('d/m/Y') }}</div>
                    <div>{{ $withdrawal->created_at->format('H:i') }}</div>
                </div>
            </div>

            {{-- ที่อยู่ + เครือข่าย --}}
            <div class="tp-card" style="padding:14px 16px; box-shadow:var(--inset-sm); margin-bottom:14px; display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
                <div>
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:3px;">ที่อยู่ปลายทาง</div>
                    <div style="font-family:monospace; font-size:11.5px; color:var(--ink); word-break:break-all;">{{ $withdrawal->to_address }}</div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:3px;">เครือข่าย</div>
                    <div style="font-weight:700; color:var(--ink); text-transform:uppercase; font-size:13.5px;">{{ $withdrawal->network }}</div>
                </div>
            </div>

            {{-- แจกแจงจำนวน --}}
            <div style="display:flex; flex-direction:column; gap:7px; margin-bottom:14px; font-size:13.5px;">
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">จำนวนถอน:</span><span style="font-weight:700; color:var(--ink);">{{ number_format($withdrawal->amount, 8) }} {{ $withdrawal->currency->code }}</span></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ค่าธรรมเนียมเครือข่าย:</span><span style="color:#d9534f;">- {{ number_format($withdrawal->network_fee, 8) }}</span></div>
                @if($withdrawal->platform_fee > 0)<div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ค่าธรรมเนียมแพลตฟอร์ม:</span><span style="color:#d9534f;">- {{ number_format($withdrawal->platform_fee, 8) }}</span></div>@endif
                <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); padding-top:8px; display:flex; justify-content:space-between; font-weight:800;"><span style="color:var(--ink);">จำนวนที่ได้รับ:</span><span style="color:#5aa07e;">{{ number_format($withdrawal->net_amount, 8) }} {{ $withdrawal->currency->code }}</span></div>
            </div>

            {{-- actions --}}
            <div style="display:flex; flex-wrap:wrap; gap:9px;">
                @if($withdrawal->isPending())
                    <form action="{{ route('user.crypto-wallet.withdrawal.cancel', $withdrawal->id) }}" method="POST" style="flex:1; min-width:150px;" onsubmit="return confirm('คุณแน่ใจที่จะยกเลิกคำขอนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn" style="width:100%; padding:10px; border-radius:12px; background:color-mix(in srgb, #d9534f 16%, transparent); color:#d9534f; font-weight:600; font-size:13px; border:none; cursor:pointer;">ยกเลิกคำขอ</button>
                    </form>
                @endif
                @if($withdrawal->cryptoTransaction && $withdrawal->cryptoTransaction->tx_hash)
                    <a href="{{ $withdrawal->cryptoTransaction->getExplorerUrl() }}" target="_blank" class="tp-btn" style="flex:1; min-width:150px; padding:10px; border-radius:12px; background:color-mix(in srgb, var(--accent1) 16%, transparent); color:var(--deep1); font-weight:600; font-size:13px; text-align:center; text-decoration:none;">🔗 ดูใน Block Explorer</a>
                @endif
            </div>

            @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                <div class="tp-card" style="margin-top:14px; padding:12px 14px; box-shadow:var(--inset-sm); background:color-mix(in srgb, #d9534f 8%, transparent);">
                    <div style="font-size:13px; font-weight:700; color:#d9534f; margin-bottom:3px;">เหตุผลที่ถูกปฏิเสธ:</div>
                    <div style="font-size:13px; color:#d9534f;">{{ $withdrawal->rejection_reason }}</div>
                </div>
            @endif
        </div>
    @empty
        <div class="tp-card" style="padding:52px 24px; text-align:center;">
            <div style="font-size:56px; margin-bottom:14px;">📤</div>
            <p style="color:var(--ink2); font-size:14px; margin:0 0 20px;">ยังไม่มีประวัติการถอน</p>
            <a href="{{ route('user.crypto-wallet.withdraw') }}" class="tp-btn" style="display:inline-block; padding:11px 24px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">ถอนเหรียญ</a>
        </div>
    @endforelse

    @if($withdrawals->hasPages())
        <div>{{ $withdrawals->links() }}</div>
    @endif
</div>
@endsection

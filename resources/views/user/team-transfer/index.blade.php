@extends('layouts.user-v4')

@section('title', 'คำขอย้ายทีมของฉัน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#5689b8;"><i class="fas fa-random" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">📋 คำขอย้ายทีมของฉัน</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามสถานะคำขอย้ายทีมของคุณ</div>
                    </div>
                </div>
                <a href="{{ route('user.team-transfer.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fas fa-plus"></i> สร้างคำขอใหม่</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #5aa07e;" x-data="{ show: true }" x-show="show" x-transition>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <span style="color:var(--ink); font-weight:600;"><i class="fas fa-check-circle" style="color:#5aa07e; margin-right:8px;"></i>{{ session('success') }}</span>
                <button @click="show = false" style="background:none; border:none; color:var(--ink2); cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #d9534f;" x-data="{ show: true }" x-show="show" x-transition>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <span style="color:var(--ink); font-weight:600;"><i class="fas fa-exclamation-circle" style="color:#d9534f; margin-right:8px;"></i>{{ session('error') }}</span>
                <button @click="show = false" style="background:none; border:none; color:var(--ink2); cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
        </div>
    @endif

    @if($requests->count() > 0)
        <div style="display:flex; flex-direction:column; gap:16px;">
            @foreach($requests as $request)
                @php
                    $stColor = match($request->status) {
                        'pending' => '#d9a441', 'approved', 'completed' => '#5aa07e',
                        'rejected' => '#d9534f', 'paid' => '#5689b8', 'processing' => '#7c5cbf',
                        default => '#8a8a8a',
                    };
                @endphp
                <div class="tp-card" style="padding:24px;">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px;">
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <span class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; background:#5689b8;"><i class="fas fa-exchange-alt" style="color:#fff;"></i></span>
                            <div>
                                <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0;">คำขอ #{{ $request->id }}</h3>
                                <p style="font-size:12px; color:var(--ink2); margin:2px 0 0;">{{ $request->created_at->locale('th')->diffForHumans() }}</p>
                            </div>
                        </div>
                        <span style="display:inline-flex; align-items:center; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; color:{{ $stColor }}; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);">
                            <span style="width:8px; height:8px; border-radius:50%; margin-right:8px; background:{{ $stColor }};"></span>{{ $request->status_label }}
                        </span>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:18px;">
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <div style="flex-shrink:0; width:40px; height:40px; background:color-mix(in srgb, #d9534f 16%, transparent); border-radius:10px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-user" style="color:#d9534f;"></i></div>
                            <div>
                                <p style="font-size:11px; color:var(--ink2); margin:0 0 2px;">แม่ทีมเดิม</p>
                                <p style="font-weight:700; color:var(--ink); margin:0;">{{ $request->oldSponsor->user->name ?? 'N/A' }}</p>
                                <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->oldSponsor->member_code ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <div style="flex-shrink:0; width:40px; height:40px; background:color-mix(in srgb, #5aa07e 16%, transparent); border-radius:10px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-user" style="color:#5aa07e;"></i></div>
                            <div>
                                <p style="font-size:11px; color:var(--ink2); margin:0 0 2px;">แม่ทีมใหม่</p>
                                <p style="font-weight:700; color:var(--ink); margin:0;">{{ $request->newSponsor->user->name ?? 'N/A' }}</p>
                                <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->newSponsor->member_code ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($request->reason)
                        <div style="margin-bottom:18px; padding:14px; border-radius:10px; box-shadow:var(--inset-sm);">
                            <p style="font-size:11px; color:var(--ink2); margin:0 0 2px;">เหตุผลในการย้าย</p>
                            <p style="font-size:13px; color:var(--ink); margin:0;">{{ $request->reason }}</p>
                        </div>
                    @endif

                    @if($request->status === 'rejected' && $request->rejection_reason)
                        <div style="margin-bottom:18px; padding:14px; border-radius:10px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
                            <p style="font-size:11px; color:#d9534f; margin:0 0 2px;">เหตุผลที่ปฏิเสธ</p>
                            <p style="font-size:13px; color:var(--ink); margin:0;">{{ $request->rejection_reason }}</p>
                        </div>
                    @endif

                    <div style="display:flex; flex-wrap:wrap; gap:12px;">
                        <a href="{{ route('user.team-transfer.show', $request) }}" class="tp-btn tp-btn-sm"><i class="fas fa-eye"></i> ดูรายละเอียด</a>
                        @if($request->canBePaid())
                            <form method="POST" action="{{ route('user.team-transfer.pay', $request) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="tp-btn tp-btn-sm" style="background:#5aa07e; border-color:#5aa07e; color:#fff;" onclick="return confirm('คุณต้องการชำระค่าธรรมเนียม {{ number_format($request->transfer_fee, 2) }} บาทใช่หรือไม่?')"><i class="fas fa-credit-card"></i> ชำระเงิน {{ number_format($request->transfer_fee, 2) }} บาท</button>
                            </form>
                        @endif
                        @if($request->canBeCancelled())
                            <form method="POST" action="{{ route('user.team-transfer.cancel', $request) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;" onclick="return confirm('คุณต้องการยกเลิกคำขอนี้ใช่หรือไม่?')"><i class="fas fa-times"></i> ยกเลิก</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:8px;">{{ $requests->links() }}</div>
    @else
        <div class="tp-card" style="padding:48px; text-align:center;">
            <div style="width:80px; height:80px; margin:0 auto 20px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-file-alt" style="font-size:32px; color:var(--ink2);"></i>
            </div>
            <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 8px;">ยังไม่มีคำขอย้ายทีม</h3>
            <p style="color:var(--ink2); margin:0 0 20px;">คุณยังไม่เคยสร้างคำขอย้ายทีม เริ่มสร้างคำขอแรกของคุณได้เลย</p>
            <a href="{{ route('user.team-transfer.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fas fa-plus"></i> สร้างคำขอย้ายทีม</a>
        </div>
    @endif
</div>
@endsection

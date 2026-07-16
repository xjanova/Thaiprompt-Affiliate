@extends('layouts.user-v4')

@section('title', 'รายละเอียดคำขอย้ายทีม #' . $request->id)

@section('content')
@php
    $stColor = match($request->status) {
        'pending' => '#d9a441', 'approved', 'completed' => '#5aa07e',
        'rejected' => '#d9534f', 'paid' => '#5689b8', 'processing' => '#7c5cbf',
        default => '#8a8a8a',
    };
@endphp
<div style="display:flex; flex-direction:column; gap:18px; max-width:1000px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <a href="{{ route('user.team-transfer.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--ink2); text-decoration:none; font-size:13px; margin-bottom:12px;"><i class="fas fa-arrow-left"></i> กลับไปรายการคำขอ</a>
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#7c5cbf;"><i class="fas fa-exchange-alt" style="color:#fff;"></i></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">📄 รายละเอียดคำขอย้ายทีม</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">#{{ $request->id }}</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #5aa07e;" x-data="{ show: true }" x-show="show" x-transition>
            <span style="color:var(--ink); font-weight:600;"><i class="fas fa-check-circle" style="color:#5aa07e; margin-right:8px;"></i>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #d9534f;" x-data="{ show: true }" x-show="show" x-transition>
            <span style="color:var(--ink); font-weight:600;"><i class="fas fa-exclamation-circle" style="color:#d9534f; margin-right:8px;"></i>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ── หัวข้อ + สถานะ ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; background:#5689b8;"><i class="fas fa-exchange-alt" style="color:#fff;"></i></span>
                <div>
                    <h2 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 2px;">คำขอย้ายทีม #{{ $request->id }}</h2>
                    <p style="font-size:12px; color:var(--ink2); margin:0;">สร้างเมื่อ {{ $request->created_at->locale('th')->isoFormat('D MMMM YYYY HH:mm น.') }}</p>
                </div>
            </div>
            <span style="display:inline-flex; align-items:center; padding:8px 18px; border-radius:999px; font-size:14px; font-weight:800; color:{{ $stColor }}; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);">
                <span style="width:10px; height:10px; border-radius:50%; margin-right:8px; background:{{ $stColor }};"></span>{{ $request->status_label }}
            </span>
        </div>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        {{-- ── Main ──────────────────────────────────────────── --}}
        <div style="flex:2; min-width:320px; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card" style="padding:24px;">
                <div class="tp-section-h" style="margin-bottom:18px;">รายละเอียดการย้าย</div>
                <div style="position:relative; margin-bottom:8px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div style="border-radius:14px; box-shadow:var(--inset-sm); padding:20px; text-align:center; border:2px solid color-mix(in srgb, #d9534f 25%, transparent);">
                            <div style="width:48px; height:48px; margin:0 auto 12px; background:#d9534f; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fas fa-user" style="color:#fff;"></i></div>
                            <p style="font-size:11px; color:#d9534f; margin:0 0 6px; font-weight:700;">แม่ทีมเดิม</p>
                            <p style="font-weight:800; color:var(--ink); margin:0 0 2px;">{{ $request->oldSponsor->user->name ?? 'N/A' }}</p>
                            <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->oldSponsor->member_code ?? 'N/A' }}</p>
                        </div>
                        <div style="border-radius:14px; box-shadow:var(--inset-sm); padding:20px; text-align:center; border:2px solid color-mix(in srgb, #5aa07e 25%, transparent);">
                            <div style="width:48px; height:48px; margin:0 auto 12px; background:#5aa07e; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fas fa-user" style="color:#fff;"></i></div>
                            <p style="font-size:11px; color:#5aa07e; margin:0 0 6px; font-weight:700;">แม่ทีมใหม่</p>
                            <p style="font-weight:800; color:var(--ink); margin:0 0 2px;">{{ $request->newSponsor->user->name ?? 'N/A' }}</p>
                            <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->newSponsor->member_code ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                @if($request->reason)
                    <div style="margin-top:18px;">
                        <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">เหตุผลในการย้าย</div>
                        <div style="padding:14px; border-radius:10px; box-shadow:var(--inset-sm);"><p style="color:var(--ink); margin:0;">{{ $request->reason }}</p></div>
                    </div>
                @endif
                @if($request->notes)
                    <div style="margin-top:18px;">
                        <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">หมายเหตุ</div>
                        <div style="padding:14px; border-radius:10px; box-shadow:var(--inset-sm);"><p style="color:var(--ink); margin:0;">{{ $request->notes }}</p></div>
                    </div>
                @endif
            </div>

            @if($request->status === 'rejected' && $request->rejection_reason)
                <div class="tp-card" style="padding:24px; border-left:4px solid #d9534f;">
                    <div style="font-size:18px; font-weight:800; color:#d9534f; margin-bottom:8px;"><i class="fas fa-times-circle" style="margin-right:6px;"></i>เหตุผลที่ปฏิเสธ</div>
                    <p style="color:var(--ink); margin:0;">{{ $request->rejection_reason }}</p>
                    @if($request->rejecter)
                        <p style="font-size:13px; color:var(--ink2); margin:8px 0 0;">โดย: {{ $request->rejecter->name }} • {{ $request->rejected_at->locale('th')->diffForHumans() }}</p>
                    @endif
                </div>
            @endif

            @if($request->admin_notes)
                <div class="tp-card" style="padding:24px; border-left:4px solid #5689b8;">
                    <div style="font-size:18px; font-weight:800; color:#5689b8; margin-bottom:8px;">หมายเหตุจาก Admin</div>
                    <p style="color:var(--ink); margin:0;">{{ $request->admin_notes }}</p>
                    @if($request->processor)
                        <p style="font-size:13px; color:var(--ink2); margin:8px 0 0;">โดย: {{ $request->processor->name }} • {{ $request->processed_at->locale('th')->diffForHumans() }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── Sidebar ───────────────────────────────────────── --}}
        <div style="flex:1; min-width:280px; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card" style="padding:20px;">
                <div style="font-size:17px; font-weight:800; color:var(--ink); margin-bottom:14px;">ข้อมูลการชำระเงิน</div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:13px; color:var(--ink2);">ค่าธรรมเนียม</span>
                    <span style="font-weight:800; color:var(--ink);">{{ number_format($request->transfer_fee, 2) }} บาท</span>
                </div>
                <div style="padding-top:12px; margin-top:12px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                    @if($request->paid_at)
                        <div style="display:flex; align-items:center; color:#5aa07e; margin-bottom:6px;"><i class="fas fa-check-circle" style="margin-right:8px;"></i><span style="font-weight:700;">ชำระเงินแล้ว</span></div>
                        <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $request->paid_at->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p>
                    @else
                        <div style="display:flex; align-items:center; color:#d9a441;"><i class="fas fa-clock" style="margin-right:8px;"></i><span style="font-weight:700;">ยังไม่ชำระเงิน</span></div>
                    @endif
                </div>
            </div>

            <div class="tp-card" style="padding:20px;">
                <div style="font-size:17px; font-weight:800; color:var(--ink); margin-bottom:14px;">Timeline</div>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    @php
                        $timeline = [['สร้างคำขอ', $request->created_at, '#5689b8']];
                        if ($request->approved_at) $timeline[] = ['อนุมัติโดยแม่ทีมเดิม', $request->approved_at, '#5aa07e'];
                        if ($request->paid_at) $timeline[] = ['ชำระค่าธรรมเนียม', $request->paid_at, '#5689b8'];
                        if ($request->processed_at) $timeline[] = ['ดำเนินการเสร็จสิ้น', $request->processed_at, '#7c5cbf'];
                    @endphp
                    @foreach($timeline as $t)
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <div style="flex-shrink:0; width:32px; height:32px; background:color-mix(in srgb, {{ $t[2] }} 16%, transparent); border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fas fa-circle-check" style="color:{{ $t[2] }}; font-size:13px;"></i></div>
                            <div><p style="font-size:13px; font-weight:700; color:var(--ink); margin:0;">{{ $t[0] }}</p><p style="font-size:11px; color:var(--ink2); margin:0;">{{ $t[1]->locale('th')->isoFormat('D MMM YYYY HH:mm น.') }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tp-card" style="padding:20px;">
                <div style="font-size:17px; font-weight:800; color:var(--ink); margin-bottom:14px;">การจัดการ</div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @if($request->canBePaid())
                        <form method="POST" action="{{ route('user.team-transfer.pay', $request) }}">
                            @csrf
                            <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; background:#5aa07e; border-color:#5aa07e;" onclick="return confirm('คุณต้องการชำระค่าธรรมเนียม {{ number_format($request->transfer_fee, 2) }} บาทใช่หรือไม่?')"><i class="fas fa-credit-card"></i> ชำระเงิน {{ number_format($request->transfer_fee, 2) }} บาท</button>
                        </form>
                    @endif
                    @if($request->canBeCancelled())
                        <form method="POST" action="{{ route('user.team-transfer.cancel', $request) }}">
                            @csrf
                            <button type="submit" class="tp-btn" style="width:100%; justify-content:center; color:#d9534f;" onclick="return confirm('คุณต้องการยกเลิกคำขอนี้ใช่หรือไม่?{{ $request->paid_at ? '\n\nเงินจะถูกคืนเข้า Wallet ของคุณ' : '' }}')"><i class="fas fa-times"></i> ยกเลิกคำขอ</button>
                        </form>
                    @endif
                    @if($request->status === 'completed')
                        <div style="padding:16px; border-radius:10px; box-shadow:var(--inset-sm); text-align:center; border-left:4px solid #5aa07e;">
                            <i class="fas fa-check-circle" style="font-size:36px; color:#5aa07e; display:block; margin-bottom:8px;"></i>
                            <p style="font-size:13px; font-weight:700; color:#5aa07e; margin:0;">การย้ายทีมเสร็จสมบูรณ์</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

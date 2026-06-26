@extends('layouts.user-v4')

@section('title', 'แลก Coins เป็นเงิน')

@php
    // ── แมพสถานะการแลก → ข้อความ + สี hex (ใช้ใน V4 แทน dynamic tailwind class) ──
    // pending = อำพัน / approved+completed = เขียว / rejected = แดง / อื่น ๆ = neutral
    $exStatusMap = [
        'pending'   => ['รอดำเนินการ', '#e0a52e', 'fa-clock'],
        'approved'  => ['อนุมัติแล้ว',  '#5aa07e', 'fa-check'],
        'completed' => ['สำเร็จ',       '#5aa07e', 'fa-check-double'],
        'rejected'  => ['ปฏิเสธ',       '#d9534f', 'fa-times'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ + ปุ่มย้อนกลับ (Hero) ─────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.video-coins.index'))
                <a href="{{ route('user.video-coins.index') }}" class="tp-icon-btn" title="ย้อนกลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-exchange-alt" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">แลก Coins เป็นเงิน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">แลก Video Coins เป็นเงินบาทเข้ากระเป๋าเงิน</div>
            </div>
        </div>
    </div>

    {{-- ── การ์ดยอด Coins คงเหลือ ───────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:54px; height:54px; border-radius:17px; font-size:24px;"><i class="fas fa-coins" style="color:#fff;"></i></span>
                <div>
                    <div style="font-size:12.5px; color:var(--ink2);">ยอด Coins ของคุณ</div>
                    <div class="tp-num" style="font-size:clamp(32px,7vw,48px); font-weight:800; line-height:1.1; margin-top:2px; color:var(--deep1);">
                        {{ number_format($videoCoin->balance, 2) }}
                        <span style="font-size:18px; font-weight:600; color:var(--ink2);">VC</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ข้อความแจ้งเตือน (Flash) ─────────────────────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
            <i class="fas fa-check-circle" style="color:#5aa07e; font-size:18px;"></i>
            <div style="font-size:13px; font-weight:600; color:var(--ink);">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
            <i class="fas fa-exclamation-circle" style="color:#d9534f; font-size:18px;"></i>
            <div style="font-size:13px; font-weight:600; color:var(--ink);">{{ session('error') }}</div>
        </div>
    @endif

    {{-- ── เลือกอัตราแลกเปลี่ยน ──────────────────────────────── --}}
    @if($exchangeRates->count() > 0)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                <div class="tp-section-h">🔄 เลือกจำนวนที่ต้องการแลก</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">กดเลือกแพคเกจที่ต้องการเพื่อยืนยันการแลก</div>
            </div>

            <div style="padding:18px; display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px;">
                @foreach($exchangeRates as $rate)
                    @php
                        // เช็คว่ายอด Coins พอแลกหรือไม่
                        $canExchange = $videoCoin->balance >= $rate->coins_amount;
                    @endphp
                    <form action="{{ route('user.video-coins.exchange.submit') }}" method="POST" style="margin:0;">
                        @csrf
                        <input type="hidden" name="exchange_rate_id" value="{{ $rate->id }}">
                        <button type="submit"
                                @if(!$canExchange) disabled @endif
                                style="width:100%; text-align:left; cursor:{{ $canExchange ? 'pointer' : 'not-allowed' }};
                                       opacity:{{ $canExchange ? '1' : '.55' }}; border:none; background:transparent; padding:0; font:inherit;">
                            <div class="{{ $canExchange ? 'tp-card tp-card-hover' : 'tp-card' }}" style="padding:18px; height:100%;">
                                {{-- จำนวน Coins → จำนวนเงิน --}}
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:14px;">
                                    <div style="display:flex; align-items:baseline; gap:5px;">
                                        <span class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);">{{ number_format($rate->coins_amount, 0) }}</span>
                                        <span style="font-size:12px; font-weight:600; color:var(--ink2);">VC</span>
                                    </div>
                                    <i class="fas fa-arrow-right" style="color:var(--ink2); font-size:15px;"></i>
                                    <div style="display:flex; align-items:baseline; gap:3px;">
                                        <span class="tp-num" style="font-size:26px; font-weight:800; color:#5aa07e;">{{ number_format($rate->money_amount, 0) }}</span>
                                        <span style="font-size:14px; color:var(--ink2);">฿</span>
                                    </div>
                                </div>

                                {{-- รายละเอียดอัตรา --}}
                                <div style="font-size:12px; color:var(--ink2); display:flex; flex-direction:column; gap:4px;">
                                    <div>อัตรา: 1 Coin = <span class="tp-num">{{ number_format($rate->rate, 4) }}</span> บาท</div>
                                    @if($rate->min_level)
                                        <div><i class="fas fa-star" style="color:var(--accent1); margin-right:4px;"></i>ต้องมี Level {{ $rate->min_level }}+</div>
                                    @endif
                                </div>

                                {{-- ปุ่มสถานะ --}}
                                @if($canExchange)
                                    <div style="margin-top:14px; padding:9px; text-align:center; border-radius:12px; font-size:13px; font-weight:700; color:#fff; background:#5aa07e;">
                                        <i class="fas fa-exchange-alt" style="margin-right:6px;"></i>แลกเลย
                                    </div>
                                @else
                                    <div style="margin-top:14px; padding:9px; text-align:center; border-radius:12px; font-size:13px; font-weight:700; color:var(--ink2); box-shadow:var(--inset-sm);">
                                        <i class="fas fa-lock" style="margin-right:6px;"></i>Coins ไม่พอ
                                    </div>
                                @endif
                            </div>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @else
        <div class="tp-card" style="text-align:center; padding:56px 20px;">
            <div style="font-size:52px; opacity:.5;">🔄</div>
            <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีอัตราแลกเปลี่ยน</div>
            <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เมื่อแอดมินเปิดอัตราแลกเปลี่ยนจะแสดงที่นี่</div>
        </div>
    @endif

    {{-- ── ประวัติการแลก ─────────────────────────────────────── --}}
    @if($exchangeHistory->count() > 0)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                <div class="tp-section-h">🕑 ประวัติการแลก</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">10 รายการล่าสุด</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">รายการ</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">จำนวนเงิน</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:center;">สถานะ</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exchangeHistory as $exchange)
                            @php
                                // หาข้อมูลสถานะจากแมพ — ถ้าไม่พบใช้ค่า status ดิบ + สี neutral
                                $exInfo = $exStatusMap[$exchange->status] ?? [$exchange->status, 'var(--ink2)', 'fa-exchange-alt'];
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ number_format($exchange->coins_amount, 0) }} VC</span>
                                        <i class="fas fa-arrow-right" style="color:var(--ink2); font-size:11px;"></i>
                                        <span class="tp-num" style="font-weight:700; color:#5aa07e;">{{ number_format($exchange->money_amount, 0) }} ฿</span>
                                    </div>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:var(--ink);">฿{{ number_format($exchange->money_amount, 2) }}</td>
                                <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                    <span class="tp-pill" style="color:{{ $exInfo[1] }}; background:color-mix(in srgb, {{ $exInfo[1] }} 16%, transparent);"><i class="fas {{ $exInfo[2] }}" style="font-size:10px;"></i> {{ $exInfo[0] }}</span>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; color:var(--ink2);">
                                    <div style="color:var(--ink);">{{ $exchange->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px;">{{ $exchange->created_at->format('H:i') }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

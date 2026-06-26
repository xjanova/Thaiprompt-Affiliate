@extends('layouts.user-v4')

@section('title', 'ประวัติธุรกรรม Coins')

@php
    // ── ป้ายประเภทธุรกรรม + ไอคอน (ใช้แทน switch/case แบบ tailwind เดิม) ──────────
    $typeMeta = [
        'earned_video'    => ['ได้รับจากวิดีโอ', 'fa-play'],
        'earned_quest'    => ['ได้รับจากภารกิจ', 'fa-trophy'],
        'earned_referral' => ['ได้รับจากแนะนำ',  'fa-users'],
        'earned_bonus'    => ['โบนัส',           'fa-gift'],
        'spent_exchange'  => ['แลกเป็นเงิน',     'fa-exchange-alt'],
        'spent_shop'      => ['ซื้อสินค้า',       'fa-shopping-cart'],
    ];

    // ── ตัวเลือก dropdown ประเภท (mirror ของเดิม) ──────────────────────────
    $typeOptions = [
        'earned_video'    => 'ได้รับจากวิดีโอ',
        'earned_quest'    => 'ได้รับจากภารกิจ',
        'earned_referral' => 'ได้รับจากแนะนำ',
        'earned_bonus'    => 'โบนัส',
        'spent_exchange'  => 'แลกเป็นเงิน',
        'spent_shop'      => 'ซื้อสินค้า',
    ];

    // สีรายรับ/รายจ่าย (hex — ไม่ใช้ dynamic tailwind class)
    $incomeColor = '#5aa07e';
    $expenseColor = '#d9534f';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.video-coins.index'))
                <a href="{{ route('user.video-coins.index') }}" class="tp-icon-btn" title="ย้อนกลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-receipt" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ประวัติธุรกรรม Coins</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ดูรายการได้รับและใช้จ่าย Video Coins</div>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์มกรอง (GET — รักษาชื่อ field type/date_from/date_to) ──────── --}}
    <div class="tp-card" style="padding:18px;">
        <form action="{{ route('user.video-coins.transactions') }}" method="GET"
              style="display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ประเภท</label>
                <select name="type" class="tp-input" style="width:100%;">
                    <option value="">ทั้งหมด</option>
                    @foreach($typeOptions as $val => $label)
                        <option value="{{ $val }}" {{ request('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1; min-width:150px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">จากวันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input tp-num" style="width:100%;">
            </div>
            <div style="flex:1; min-width:150px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input tp-num" style="width:100%;">
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-search"></i> ค้นหา</button>
                <a href="{{ route('user.video-coins.transactions') }}" class="tp-btn">ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ── ตารางรายการธุรกรรม ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($transactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">ประเภท</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">รายละเอียด</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">วันที่</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">จำนวน</th>
                            <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; text-align:right;">คงเหลือ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php
                                // เป็นรายรับหรือไม่ (type ขึ้นต้นด้วย earned)
                                $isIncome = str_starts_with($transaction->type, 'earned');
                                $amtColor = $isIncome ? $incomeColor : $expenseColor;
                                // ดึงป้าย + ไอคอนจาก map (fallback ตามทิศทาง)
                                $meta = $typeMeta[$transaction->type] ?? null;
                                $typeLabel = $meta[0] ?? $transaction->type;
                                $typeIcon = $meta[1] ?? ($isIncome ? 'fa-arrow-down' : 'fa-arrow-up');
                            @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:12px 16px; white-space:nowrap;">
                                    <div style="display:flex; align-items:center; gap:9px;">
                                        <span class="tp-tile" style="width:36px; height:36px; border-radius:11px; font-size:14px; background:color-mix(in srgb, {{ $amtColor }} 18%, transparent);"><i class="fas {{ $typeIcon }}" style="color:{{ $amtColor }};"></i></span>
                                        <span style="font-size:12.5px; font-weight:600;">{{ $typeLabel }}</span>
                                    </div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <div style="font-size:12.5px; font-weight:600;">{{ $transaction->description ?? $transaction->type }}</div>
                                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $transaction->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; white-space:nowrap; color:var(--ink);">
                                    <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $transaction->created_at->format('H:i') }}</div>
                                </td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; font-size:14px; color:{{ $amtColor }};">
                                    {{ $isIncome ? '+' : '-' }}{{ number_format(abs($transaction->amount), 2) }}
                                </td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; color:var(--ink2);">
                                    {{ number_format($transaction->balance_after, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── หน้า (Pagination) ──────────────────────────────── --}}
            <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $transactions->withQueryString()->links() }}
            </div>
        @else
            {{-- ── สถานะว่าง ───────────────────────────────────────── --}}
            <div style="text-align:center; padding:56px 20px;">
                <div style="font-size:52px; opacity:.5;">📭</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ไม่พบประวัติธุรกรรม</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เริ่มดูวิดีโอเพื่อรับ Video Coins กันเลย</div>
                @if(\Illuminate\Support\Facades\Route::has('user.video-missions.index'))
                    <div style="margin-top:18px;">
                        <a href="{{ route('user.video-missions.index') }}" class="tp-btn tp-btn-primary"><i class="fas fa-play"></i> ดูวิดีโอรับ Coins</a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

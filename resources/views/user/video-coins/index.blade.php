@extends('layouts.user-v4')

@section('title', 'Video Coins')

@php
    // ── ตัวช่วยเช็คว่าธุรกรรมเป็นรายรับ (earned*) หรือรายจ่าย ──────
    $isIncome = fn ($t) => \Illuminate\Support\Str::startsWith((string) $t, 'earned');

    // ── ปุ่มเมนูด่วน 4 ใบ (ป้าย / ป้ายย่อย / route / ไอคอน) ──────
    $vcActions = [
        ['ดูวิดีโอ', 'รับ Coins ฟรี', 'user.video-missions.index', 'fa-play'],
        ['ร้านค้า',  'แลกของรางวัล',  'user.coin-shop.index',      'fa-store'],
        ['แลกเงิน',  'ถอนเงินบาท',    'user.video-coins.exchange', 'fa-exchange-alt'],
        ['ประวัติ',  'ดูธุรกรรม',     'user.video-coins.transactions', 'fa-history'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ + ปุ่มลัด ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-coins" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">Video Coins</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">จัดการและใช้งาน Video Coins ของคุณ</div>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <a href="{{ route('user.coin-shop.index') }}" class="tp-btn">
                    <i class="fas fa-store"></i> ร้านค้า Coins
                </a>
                <a href="{{ route('user.video-coins.exchange') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-exchange-alt"></i> แลกเงิน
                </a>
            </div>
        </div>
    </div>

    {{-- ── การ์ดยอด Coins (Hero) — native V4 แทน x-video-reward.coin-balance ─ --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px;">
                {{-- ไอคอนเหรียญ "C" --}}
                <span class="tp-tile" style="width:72px; height:72px; border-radius:50%; flex-shrink:0;">
                    <span class="tp-num" style="color:#fff; font-weight:900; font-size:38px;">C</span>
                </span>
                <div style="min-width:0;">
                    <div style="font-size:13px; color:var(--ink2);">ยอด Video Coins คงเหลือ</div>
                    <div class="tp-num" style="font-size:clamp(36px,8vw,56px); font-weight:800; line-height:1.05; margin-top:2px; color:var(--deep1);">
                        {{ number_format($videoCoin->balance ?? 0, 2) }}
                    </div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">Coins</div>
                </div>
                {{-- สถิติสะสมด้านขวา --}}
                <div style="display:flex; gap:22px; margin-left:auto; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:11px; color:var(--ink2);">สะสมทั้งหมด</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">{{ number_format($videoCoin->lifetime_earned ?? 0, 0) }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--ink2);">ใช้ไปทั้งหมด</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:#d9534f;">{{ number_format($videoCoin->lifetime_spent ?? 0, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สถิติด่วน 4 ใบ ───────────────────────────────────────── --}}
    @php
        $vcStats = [
            ['ได้รับวันนี้', number_format($videoCoin->earned_today ?? 0, 0),    'fa-arrow-down', '#5aa07e'],
            ['ใช้ไปวันนี้',  number_format($videoCoin->spent_today ?? 0, 0),     'fa-arrow-up',   '#d9534f'],
            ['แลกไปแล้ว',    number_format($videoCoin->lifetime_exchanged ?? 0, 0), 'fa-coins',    '#5689b8'],
            ['ยอดคงเหลือ',   number_format($videoCoin->balance ?? 0, 2),         'C',             '#e0a52e'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px;">
        @foreach($vcStats as [$label, $val, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:color-mix(in srgb, {{ $color }} 18%, transparent);">
                        @if($icon === 'C')
                            <span class="tp-num" style="color:{{ $color }}; font-weight:900; font-size:17px;">C</span>
                        @else
                            <i class="fas {{ $icon }}" style="color:{{ $color }};"></i>
                        @endif
                    </span>
                </div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:10px;">{{ $val }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── เมนูด่วน 4 ปุ่ม ──────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
        @foreach($vcActions as [$label, $sub, $route, $icon])
            <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:9px; padding:20px 12px; text-decoration:none; color:inherit;">
                <span class="tp-tile" style="width:54px; height:54px; border-radius:17px; font-size:24px;"><i class="fas {{ $icon }}" style="color:#fff;"></i></span>
                <span style="font-size:14px; font-weight:700; text-align:center;">{{ $label }}</span>
                <span style="font-size:11px; color:var(--ink2);">{{ $sub }}</span>
            </a>
        @endforeach
    </div>

    {{-- ── ธุรกรรมล่าสุด ────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div>
                <div class="tp-section-h">🕑 ธุรกรรมล่าสุด</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">10 รายการล่าสุด</div>
            </div>
            <a href="{{ route('user.video-coins.transactions') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
        </div>

        @if($recentTransactions->count() > 0)
            <div>
                @foreach($recentTransactions as $transaction)
                    @php $inc = $isIncome($transaction->type); @endphp
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <div style="display:flex; align-items:center; gap:13px; min-width:0;">
                            <span class="tp-tile" style="width:40px; height:40px; border-radius:13px; font-size:15px; flex-shrink:0; background:color-mix(in srgb, {{ $inc ? '#5aa07e' : '#d9534f' }} 18%, transparent);">
                                <i class="fas {{ $inc ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="color:{{ $inc ? '#5aa07e' : '#d9534f' }};"></i>
                            </span>
                            <div style="min-width:0;">
                                <div style="font-size:13.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $transaction->description ?? $transaction->type }}</div>
                                <div class="tp-num" style="font-size:11.5px; color:var(--ink2);">{{ $transaction->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        <div style="text-align:right; white-space:nowrap;">
                            <div class="tp-num" style="font-size:14.5px; font-weight:800; color:{{ $inc ? '#5aa07e' : '#d9534f' }};">
                                {{ $inc ? '+' : '-' }}{{ number_format(abs($transaction->amount), 2) }}
                            </div>
                            <div class="tp-num" style="font-size:11px; color:var(--ink2);">คงเหลือ {{ number_format($transaction->balance_after, 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align:center; padding:48px 20px;">
                <div style="font-size:46px; opacity:.5;">🪙</div>
                <div style="font-weight:700; font-size:16px; margin-top:10px;">ยังไม่มีประวัติธุรกรรม</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เริ่มดูวิดีโอเพื่อรับ Coins ฟรี</div>
                <a href="{{ route('user.video-missions.index') }}" class="tp-btn tp-btn-primary" style="margin-top:16px;">
                    <i class="fas fa-play"></i> ดูวิดีโอรับ Coins
                </a>
            </div>
        @endif
    </div>

    {{-- ── อัตราแลกเปลี่ยน ──────────────────────────────────────── --}}
    @if($exchangeRates->count() > 0)
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h">💱 อัตราแลกเปลี่ยน</div>
            <a href="{{ route('user.video-coins.exchange') }}" class="tp-btn tp-btn-sm">แลก Coins →</a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
            @foreach($exchangeRates as $rate)
                <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
                        <div style="display:flex; align-items:center; gap:7px;">
                            <span class="tp-num" style="font-size:22px; font-weight:900; color:#e0a52e;">C</span>
                            <span class="tp-num" style="font-size:20px; font-weight:800;">{{ number_format($rate->coins_amount, 0) }}</span>
                        </div>
                        <i class="fas fa-arrow-right" style="color:var(--ink2); font-size:13px;"></i>
                        <div style="display:flex; align-items:baseline; gap:3px;">
                            <span class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">{{ number_format($rate->money_amount, 0) }}</span>
                            <span style="font-size:13px; color:var(--ink2);">฿</span>
                        </div>
                    </div>
                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">อัตรา: 1 Coin = {{ number_format($rate->rate, 4) }} บาท</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

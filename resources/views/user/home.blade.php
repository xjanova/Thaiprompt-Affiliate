{{--
    หน้าหลักแบบแอป (Hub) ของสมาชิก — ธีมโนวาเต็ม
    DashboardController::home ส่งมา: $user, $walletBalance, $walletGrowth, $pendingCommission,
    $totalReferrals, $unreadNotifications, $hubs (การ์ดบริการ), $recentActivities (คอมมิชชั่นล่าสุด 5 รายการ)
--}}
@extends('layouts.user-nova')

@section('title', 'หน้าหลัก')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    [$balInt, $balDec] = explode('.', number_format((float) $walletBalance, 2));
    $initial = mb_strtoupper(mb_substr((string) $user->name, 0, 1)) ?: '?';

    // ภาพประกอบของการ์ดบริการแต่ละอัน (ตาม id จาก DashboardController::getHubItems) — ไม่รู้จัก id = ดอกบัว
    $hubArt = [
        'shopping' => 'icons/cart', 'wallet' => 'icons/wallet', 'invest' => 'deco/coins', 'mlm' => 'earn/earn',
        'rider' => 'icons/scooter', 'aibot' => 'mascot/face', 'academy' => 'deco/scroll',
    ];

    // เมนูด่วน: [ภาพ, ป้าย, route] — ซ่อนอันที่ route ไม่มีในระบบ
    // (เดิม "สแกน QR" ขึ้นว่ากำลังพัฒนา ทั้งที่มีหน้าสแกนจ่ายแล้ว → ชี้ไปหน้าสแกนจ่ายจริง)
    $quick = array_values(array_filter([
        ['images/nova/deco/lamp.webp', 'แดชบอร์ด', 'user.dashboard'],
        ['images/nova/wallet/transfer.webp', 'โอนเงิน', 'user.wallet.transfer'],
        ['images/nova/wallet/deposit.webp', 'เติมเงิน', 'user.wallet.deposit'],
        ['images/nova/deco/scroll.webp', 'ประวัติ', 'user.wallet.transactions'],
        ['images/nova/wallet/phone.webp', 'สแกนจ่าย', 'user.wallet.qr-transfer'],
        ['images/nova/wallet/withdraw.webp', 'ถอนเงิน', 'user.wallet.withdraw'],
        ['images/nova/earn/earn.webp', 'เชิญเพื่อน', 'user.mlm.referral'],
        ['images/nova/account/profile.webp', 'โปรไฟล์', 'user.profile'],
    ], fn ($q) => $nvR($q[2])));

    $statusMap = [
        'approved' => ['ok', 'check', 'อนุมัติแล้ว'],
        'paid' => ['gold', 'cash', 'จ่ายแล้ว'],
        'rejected' => ['bad', 'x', 'ปฏิเสธ'],
        'cancelled' => ['bad', 'x', 'ยกเลิก'],
    ];
@endphp

@section('content')

<div class="nvm-stack" x-data="nvmUmHome()">

{{-- ── ฮีโร่: ทักทาย + ยอดเงิน ─────────────────────────────── --}}
<section class="nvm-ph" aria-labelledby="nvm-um-home-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-um-hello">
                <a class="nvm-avatar nvm-um-hello__av" href="{{ route('user.profile') }}" aria-label="โปรไฟล์ของฉัน">
                    {{ $initial }}
                    @if($user->profile_picture_url)
                        <img src="{{ $user->profile_picture_url }}" alt="" onerror="this.remove()">
                    @endif
                </a>
                <div>
                    <div class="nvm-eyebrow" x-text="greeting">สวัสดี</div>
                    <h1 id="nvm-um-home-h">{{ $user->name }}</h1>
                </div>
            </div>
            <div class="nvm-bal">
                <small>ยอดเงินคงเหลือ</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
            <div class="nvm-pills">
                @if($walletGrowth > 0)
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg>รายได้เดือนนี้ +{{ number_format(abs($walletGrowth), 0) }}%</span>
                @elseif($walletGrowth < 0)
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>รายได้เดือนนี้ -{{ number_format(abs($walletGrowth), 0) }}%</span>
                @endif
                @if($unreadNotifications > 0 && $nvR('user.notifications.index'))
                    <a class="nvm-pill nvm-pill--glass" href="{{ route('user.notifications.index') }}"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg>แจ้งเตือนใหม่ {{ $unreadNotifications > 99 ? '99+' : number_format($unreadNotifications) }}</a>
                @endif
            </div>
            <div class="nvm-hero__actions">
                @if($nvR('user.wallet.deposit'))
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.deposit') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เติมเงิน</a>
                @endif
                @if($nvR('user.wallet.index'))
                    <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>กระเป๋าเงิน</a>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/mascot/welcome.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ── บริการทั้งหมด ───────────────────────────────────────── --}}
<section aria-labelledby="nvm-um-hub-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Services</small><h2 id="nvm-um-hub-h">บริการทั้งหมด</h2></div></div>
    <div class="nvm-um-hubs">
        @foreach($hubs as $hub)
            @php
                $art = $hubArt[$hub['id'] ?? ''] ?? 'deco/lotus';
                $soon = empty($hub['route']) || $hub['route'] === '#';
            @endphp
            @if($soon)
                <button type="button" class="nvm-card nvm-um-hub is-soon" @click="soon(@js($hub['title']))">
            @else
                <a class="nvm-card nvm-um-hub" href="{{ $hub['route'] }}">
            @endif
                    <img src="{{ asset('images/nova/'.$art.'.webp') }}" alt="" width="64" height="64" loading="lazy">
                    <span class="nvm-um-hub__t">
                        <b>{{ $hub['title'] }}</b>
                        <small>{{ $hub['subtitle'] }}</small>
                    </span>
                    @if(! empty($hub['badge']))
                        <span class="nvm-chip {{ $soon ? '' : 'nvm-chip--gold' }} nvm-um-hub__tag">{{ $hub['badge'] }}</span>
                    @endif
            @if($soon)
                </button>
            @else
                </a>
            @endif
        @endforeach
    </div>
</section>

{{-- ── เมนูด่วน ─────────────────────────────────────────── --}}
@if(count($quick))
    <nav class="nvm-tiles nvm-tiles--4 nvm-um-quick" aria-label="เมนูด่วน">
        @foreach($quick as [$img, $label, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
            </a>
        @endforeach
    </nav>
@endif

{{-- ── สรุปตัวเลข ────────────────────────────────────────── --}}
<section class="nvm-um-grid" aria-label="สรุปคอมมิชชั่นและทีม">
    <a class="nvm-card nvm-kpi" href="{{ route('user.commissions') }}">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">คอมมิชชั่นรอดำเนินการ</div>
        <div class="nvm-kpi__en nvm-latin">Pending commission</div>
        <div class="nvm-kpi__val nvm-num">{{ $baht($pendingCommission) }}</div>
        <div class="nvm-kpi__foot"><small>ดูคอมมิชชั่นทั้งหมด</small></div>
    </a>
    <a class="nvm-card nvm-kpi" href="{{ $nvR('user.mlm.team') ? route('user.mlm.team') : route('user.dashboard') }}">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/elephant.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">ผู้ใช้ที่แนะนำ</div>
        <div class="nvm-kpi__en nvm-latin">Total referrals</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) $totalReferrals) }} คน</div>
        <div class="nvm-kpi__foot"><small>ดูทีมของฉัน</small></div>
    </a>
    @if($nvR('user.mlm.referral'))
        <a class="nvm-card nvm-um-share" href="{{ route('user.mlm.referral') }}">
            <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="70" height="70" loading="lazy">
            <span><b>แชร์ลิงก์แนะนำ</b><small>ชวนเพื่อนสมัคร รับค่าแนะนำทันที</small></span>
            <svg class="nvm-ico"><use href="#nvm-i-send"/></svg>
        </a>
    @endif
</section>

{{-- ── คอมมิชชั่นล่าสุด ──────────────────────────────────── --}}
<section class="nvm-card nvm-box" aria-labelledby="nvm-um-act-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Recent commissions</small><h2 id="nvm-um-act-h">คอมมิชชั่นล่าสุด</h2></div>
        <a href="{{ route('user.commissions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
    </div>
    @if(count($recentActivities) > 0)
        <ul class="nvm-txs">
            @foreach($recentActivities as $activity)
                @php
                    [$tone, $icon, $label] = $statusMap[$activity['status'] ?? ''] ?? ['warn', 'clock', 'รอดำเนินการ'];
                    $amt = (float) ($activity['amount'] ?? 0);
                    $void = in_array($activity['status'] ?? '', ['rejected', 'cancelled'], true);
                @endphp
                <li class="nvm-tx">
                    <span class="nvm-tx__ic {{ $void ? '' : 'is-in' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                    <div class="nvm-tx__t">
                        <b>{{ $activity['description'] ?? 'คอมมิชชั่น' }}</b>
                        <small><span class="nvm-chip nvm-chip--{{ $tone }}">{{ $label }}</span></small>
                    </div>
                    <div class="nvm-tx__r">
                        <span class="nvm-tx__amt nvm-num {{ $void ? 'is-void' : 'is-in' }}">{{ $amt >= 0 ? '+' : '-' }}{{ $baht(abs($amt)) }}</span>
                        @if($activity['created_at'] ?? null)
                            <time datetime="{{ $activity['created_at']->toIso8601String() }}">{{ $activity['created_at']->locale('th')->diffForHumans() }}</time>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีคอมมิชชั่น</b>
            แชร์ลิงก์แนะนำหรือสินค้าให้เพื่อน แล้วคอมมิชชั่นจะขึ้นที่นี่
        </div>
    @endif
</section>

</div>
@endsection

@push('scripts')
<script>
/**
 * หน้าหลักแบบแอป — ข้อความทักทายตามเวลา และแจ้งเตือนบริการที่ยังไม่เปิด
 */
function nvmUmHome() {
    return {
        greeting: 'สวัสดี',

        init() {
            const h = new Date().getHours();
            this.greeting = h >= 5 && h < 12 ? 'สวัสดีตอนเช้า'
                : h >= 12 && h < 17 ? 'สวัสดีตอนบ่าย'
                : h >= 17 && h < 21 ? 'สวัสดีตอนเย็น'
                : 'สวัสดีตอนกลางคืน';
        },

        // บริการที่ยังไม่มีหน้า (route = '#') — แจ้งว่ากำลังพัฒนา
        soon(title) {
            window.showNotification(title + ' กำลังพัฒนา เปิดให้ใช้เร็วๆ นี้', 'info');
        },
    };
}
</script>
@endpush

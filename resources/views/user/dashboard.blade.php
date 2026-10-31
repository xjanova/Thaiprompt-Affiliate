@extends('layouts.user-nova')

@section('title', 'แดชบอร์ด')

@php
    // ลิงก์ที่มี route จริงเท่านั้น (ไม่มี = null → ไม่แสดงปุ่มนั้น)
    $nvR = fn (string $name, array $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : null;
    // เงินบาท: มีสตางค์ค่อยแสดงทศนิยม
    $baht = fn ($v) => '฿'.number_format((float) $v, ((int) round(((float) $v) * 100)) % 100 === 0 ? 0 : 2);

    $thMonth = now()->locale('th')->translatedFormat('F');
    $thYear = now()->year + 543;

    // ── ระดับสมาชิก ─────────────────────────────────────────
    $rankLevel = (int) ($currentRank?->level ?? 0);
    $rankTh = $currentRank ? ($currentRank->name_th ?: $currentRank->name) : null;
    $rankEn = $currentRank ? strtoupper((string) $currentRank->name) : 'MEMBER';
    $nextTh = $nextRank ? ($nextRank->name_th ?: $nextRank->name) : null;
    $nextEn = $nextRank ? strtolower((string) $nextRank->name) : null;
    // คะแนนถึงเกณฑ์ระดับถัดไปแล้ว (เช่นขั้นแรกตั้งไว้ 0 คะแนน) — อย่าโชว์ "ต้องการอีก 0 คะแนน" คู่กับวง 0%
    $rankReached = $nextRank && (int) ($pointsNeeded ?? 0) <= 0;
    $rankPct = $rankReached ? 100.0 : (float) min(100, max(0, $rankProgress ?? 0));
    $ringC = 351.9; // 2πr ของวงแหวน r=56 (ตรงกับ stroke-dasharray ใน nova-member.css)
    $ringOff = round($ringC * (1 - $rankPct / 100), 1);

    // บัตร VIP เปิดตั้งแต่ระดับ 2 (เผื่อชื่อ route ต่างกันระหว่างเวอร์ชัน)
    $idCardUrl = $nvR('user.id-card') ?? $nvR('user.ranks.id-card');
    $hasVip = $idCardUrl && $rankLevel >= 2;
    // เลขสมาชิกชุดเดียวกับบัตร ID เสมือน (users.member_number) — ไม่มีก็ไม่แสดง ห้ามแต่งเลขขึ้นเอง
    $memberCode = $user->member_number ?: $mlmMember?->member_code;

    // ── KYC ────────────────────────────────────────────────
    $kyc = $kycStatus ?? 'not_submitted';

    // ── รักษายอด ───────────────────────────────────────────
    $rt = $retentionStatus;
    $rtOn = $rt && ($rt['retention_enabled'] ?? true);
    $rtStatus = $rt['status'] ?? 'inactive';
    $rtReq = (float) ($rt['required_pv'] ?? 100);
    $rtPv = (float) ($rt['monthly_pv'] ?? 0);
    $rtPct = (float) min(100, max(0, $rt['pv_percentage'] ?? ($rtReq > 0 ? $rtPv / $rtReq * 100 : 0)));
    $rtGraceLeft = max(0, (int) ($rt['grace_days'] ?? 7) - (int) ($rt['days_since_last_purchase'] ?? 0));
    // inactive เพราะสถานะสมาชิก/คุณสมบัติ (ไม่ใช่เพราะ PV) — ห้ามบอกว่า "ขาดอีก N PV"
    $rtMemberBlocked = $rtOn && (($rt['member_status'] ?? 'active') !== 'active' || ! ($rt['is_qualified'] ?? true));

    // ── ข้อความใต้คำทักทาย ─────────────────────────────────
    $growth = (float) ($commissionGrowth ?? 0);
    if (($thisMonthCommission ?? 0) > 0 && $growth > 0) {
        $lead = 'เดือนนี้รายได้โตขึ้น '.number_format($growth, 1).'% จากเดือนก่อน'.($nextTh && $rankPct >= 75 ? ' — อีกนิดเดียวก็ถึงระดับ'.$nextTh : '');
    } elseif ($kyc !== 'approved' && ($stats['total_earnings'] ?? 0) <= 0) {
        $lead = 'ยินดีต้อนรับสู่ Thai Prompt — เริ่มจากยืนยันตัวตน แล้วชวนเพื่อนคนแรกกัน';
    } elseif ($nextTh && ! $rankReached) {
        $lead = 'อีก '.number_format($pointsNeeded ?? 0).' คะแนนก็ถึงระดับ'.$nextTh;
    } else {
        $lead = 'ยินดีต้อนรับกลับ — ดูภาพรวมรายได้และทีมของคุณได้ที่นี่';
    }

    // ── กราฟรายได้ 12 เดือน (ส่งให้ Alpine วาด 6/12 เดือน) ───
    $chartValues = array_map('floatval', $chartData['values'] ?? []);
    $chartLabels = array_map(fn ($l) => explode(' ', (string) $l)[0], $chartData['labels'] ?? []);

    // เส้นกราฟเล็กในการ์ดรายได้ — จากรายได้ 12 เดือนจริง (การ์ดอื่นไม่มีประวัติ จึงไม่วาดเส้นปลอม)
    $spark = null;
    if (count($chartValues) > 1 && array_sum($chartValues) > 0) {
        $mx = max($chartValues);
        $mn = min($chartValues);
        $n = count($chartValues);
        $pts = [];
        foreach ($chartValues as $i => $v) {
            $pts[] = [round($i * 120 / ($n - 1), 1), round(34 - (($v - $mn) / (($mx - $mn) ?: 1)) * 28 + 2, 1)];
        }
        $line = collect($pts)->map(fn ($p, $i) => ($i ? 'L' : 'M').$p[0].' '.$p[1])->implode('');
        $spark = ['line' => $line, 'last' => end($pts)];
    }

    // ── การ์ดตัวเลข 4 ใบ ───────────────────────────────────
    $kpis = [
        [
            'label' => 'รายได้เดือนนี้', 'en' => 'This month', 'img' => 'deco/lotus.webp',
            'value' => $baht($thisMonthCommission ?? 0), 'href' => $nvR('user.commissions'),
            'delta' => ($thisMonthCommission ?? 0) > 0 || $growth != 0 ? $growth : null,
            'note' => ($thisMonthCommission ?? 0) > 0 ? 'จากเดือนก่อน' : 'ยังไม่มีรายได้เดือนนี้',
            'spark' => true,
        ],
        [
            'label' => 'คอมมิชชั่นรอรับ', 'en' => 'Pending commission', 'img' => 'deco/coins.webp',
            'value' => $baht($stats['pending_commission'] ?? 0), 'href' => $nvR('user.commissions'),
            'badge' => ($approvedCommission ?? 0) > 0 ? 'อนุมัติแล้ว '.$baht($approvedCommission) : null,
            'note' => ($stats['pending_commission'] ?? 0) > 0 ? 'รอระบบตรวจและอนุมัติ' : 'ไม่มีรายการรอรับ',
        ],
        [
            'label' => 'ทีมทั้งหมด', 'en' => 'Total referrals', 'img' => 'deco/elephant.webp',
            'value' => number_format($stats['total_referrals'] ?? 0), 'href' => $nvR('user.mlm.team'),
            'badge' => ($stats['total_referrals'] ?? 0) > 0 ? number_format($stats['active_referrals'] ?? 0).' ใช้งาน' : 'ยังไม่มีทีม',
            'note' => ($thisMonthReferrals ?? 0) > 0 ? '+'.number_format($thisMonthReferrals).' คนเดือนนี้' : null,
        ],
        [
            'label' => 'คะแนนระดับ', 'en' => 'Rank points', 'img' => 'deco/crystal.webp',
            'value' => number_format($stats['rank_points'] ?? 0), 'href' => $nvR('user.ranks.progress'),
            'note' => $rankReached ? 'ถึงเกณฑ์'.$nextTh.'แล้ว' : ($nextTh ? 'อีก '.number_format($pointsNeeded ?? 0).' ถึง'.$nextTh : ($currentRank ? 'ระดับสูงสุดแล้ว' : null)),
        ],
    ];

    // ── ปุ่มกระเป๋าเงิน ────────────────────────────────────
    $walletActs = array_filter([
        ['url' => $nvR('user.wallet.deposit'), 'label' => 'เติมเงิน', 'icon' => 'plus', 'navy' => false],
        ['url' => $nvR('user.wallet.withdraw'), 'label' => 'ถอนเงิน', 'icon' => 'out', 'navy' => false],
        ['url' => $nvR('user.wallet.transfer'), 'label' => 'โอนเงิน', 'icon' => 'send', 'navy' => true],
        ['url' => $nvR('user.wallet.transactions'), 'label' => 'ประวัติ', 'icon' => 'history', 'navy' => true],
    ], fn ($a) => $a['url']);

    // ── บริการ (ภาพชุดเดียวกับหน้าแรก) ─────────────────────
    $isSeller = $user->hasRole(['seller']);
    $services = array_filter([
        ['url' => $nvR('storefront.index') ?? url('/').'#products', 'img' => 'shop', 'icon' => 'bag', 'name' => 'ช้อปปิ้ง', 'en' => 'Shopping · Points', 'go' => 'เริ่มช้อป', 'tag' => 'ยอดนิยม'],
        ['url' => $nvR('taladsod.home'), 'img' => 'market', 'icon' => 'basket', 'name' => 'ตลาดสด', 'en' => 'Fresh · Local', 'go' => 'เข้าตลาดสด'],
        ['url' => $nvR('user.rider.dashboard'), 'img' => 'rider', 'icon' => 'scooter', 'name' => 'ไรเดอร์', 'en' => 'Rider · Daily', 'go' => 'งานวันนี้'],
        ['url' => (string) config('services.juntra.url', 'https://xn--82c4af5bzdj.online'), 'img' => 'fortune', 'icon' => 'moonstar', 'name' => 'ดูดวง', 'en' => 'Tarot · Daily', 'go' => 'ดวงวันนี้', 'ext' => true],
        $isSeller && $nvR('seller.dashboard')
            ? ['url' => $nvR('seller.dashboard'), 'img' => 'store', 'icon' => 'store', 'name' => 'ร้านของฉัน', 'en' => 'Seller · Online', 'go' => 'จัดการร้าน']
            : ['url' => $nvR('user.seller-apply.index'), 'img' => 'store', 'icon' => 'store', 'name' => 'เปิดร้าน', 'en' => 'Seller · Online', 'go' => 'สมัครเปิดร้าน'],
        ['url' => $referralUrl ? $nvR('user.mlm.referral') : $nvR('user.mlm.dashboard'), 'img' => 'earn', 'icon' => 'gift', 'name' => 'ชวนเพื่อน', 'en' => 'Referral · Earn', 'go' => 'รับค่าแนะนำ', 'tag' => 'ได้เงิน'],
    ], fn ($s) => ! empty($s['url']));

    // ── กิจกรรมล่าสุด ──────────────────────────────────────
    $typeLabels = [
        'direct_referral' => 'ค่าแนะนำตรง', 'binary' => 'โบนัสจับคู่', 'binary_pair' => 'โบนัสจับคู่',
        'unilevel' => 'โบนัสชั้น', 'unilevel_rollup' => 'โบนัสชั้น', 'pool_bonus' => 'โบนัสกองกลาง',
    ];
    $actView = [
        'paid' => ['cls' => 'paid', 'icon' => 'cash', 'title' => 'คอมมิชชั่นจ่ายเข้ากระเป๋าแล้ว'],
        'approved' => ['cls' => 'ok', 'icon' => 'check', 'title' => 'คอมมิชชั่นได้รับอนุมัติ'],
        'rejected' => ['cls' => 'no', 'icon' => 'x', 'title' => 'คอมมิชชั่นถูกปฏิเสธ'],
        'cancelled' => ['cls' => 'no', 'icon' => 'x', 'title' => 'คอมมิชชั่นถูกยกเลิก'],
    ];
    $actWait = ['cls' => 'wait', 'icon' => 'clock', 'title' => 'คอมมิชชั่นรอดำเนินการ'];
@endphp

@section('content')

{{-- ── ฮีโร่ + บัตรสมาชิก ─────────────────────────────── --}}
<section class="nvm-hero" aria-labelledby="nvm-hello">
    <div class="nvm-hero__bg"></div>
    <img class="nvm-hero__kanok" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <div class="nvm-hero__in">
        <div>
            <div class="nvm-eyebrow nvm-latin">My account · {{ $thMonth }} {{ $thYear }}</div>
            <h1 id="nvm-hello">สวัสดี, <span class="nvm-foil">{{ $user->name }}</span></h1>
            <p class="nvm-hero__lead">{{ $lead }}</p>
            <div class="nvm-pills">
                @if($rankTh)
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ระดับ{{ $rankTh }}</span>
                @else
                    <span class="nvm-pill nvm-pill--glass">สมาชิกใหม่</span>
                @endif
                @if($kyc === 'approved')
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ยืนยันตัวตนแล้ว</span>
                @elseif($kyc === 'pending')
                    <span class="nvm-pill nvm-pill--warn"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>รอตรวจเอกสาร</span>
                @elseif($kyc === 'rejected')
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ยืนยันตัวตนไม่ผ่าน</span>
                @else
                    <span class="nvm-pill nvm-pill--warn"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ยังไม่ยืนยันตัวตน</span>
                @endif
                {{-- 🎓 ป้ายผ่านการอบรม (ห้องเรียน) ข้างป้ายยืนยันตัวตน --}}
                <x-training-badge :user="$user" :size="20" />
                @if($rtOn && $rtStatus === 'active')
                    <span class="nvm-pill nvm-pill--glass">รักษายอดแล้ว</span>
                @endif
            </div>
            <div class="nvm-hero__actions">
                @if($nvR('user.wallet.index'))
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>กระเป๋าเงิน</a>
                @endif
                @if($hasVip)
                    <a class="nvm-btn nvm-btn--ghost" href="{{ $idCardUrl }}"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>บัตรสมาชิก VIP</a>
                @endif
            </div>
        </div>

        <div class="nvm-vipwrap">
            <{{ $hasVip ? 'a' : 'div' }} @if($hasVip) href="{{ $idCardUrl }}" @endif class="nvm-vip" x-data="nvmTilt" @pointermove="move($event)" @pointerleave="reset()" aria-label="บัตรสมาชิก {{ $user->name }}">
                <div class="nvm-vip__top">
                    <span class="nvm-vip__brand nvm-latin"><img src="{{ asset('images/brand/thaiprompt-mark.webp') }}" alt="" width="24" height="24">Thai Prompt</span>
                    <span class="nvm-vip__type nvm-latin">{{ $rankLevel >= 2 ? 'VIP Member' : 'Member' }}</span>
                </div>
                <div class="nvm-vip__chip"></div>
                <div class="nvm-vip__name">{{ $user->name }}</div>
                @if($memberCode)<div class="nvm-vip__id nvm-num">{{ $memberCode }}</div>@endif
                <div class="nvm-vip__rank">
                    <div class="nvm-vip__medal">
                        <img src="{{ asset('images/nova/brand/tabbar-kanok-medallion.webp') }}" alt="">
                        <span>{{ $rankLevel ?: '★' }}</span>
                    </div>
                    <b class="nvm-foil">{{ $rankEn }}</b>
                </div>
            </{{ $hasVip ? 'a' : 'div' }}>
        </div>
    </div>
</section>

{{-- ── กระเป๋าเงิน (ซ้อนขอบล่างฮีโร่) ───────────────────── --}}
<section class="nvm-card nvm-wallet" aria-label="กระเป๋าเงิน">
    <img class="nvm-wallet__icon" src="{{ asset('images/nova/icons/wallet.webp') }}" alt="" width="62" height="62">
    <div>
        <div class="nvm-wallet__lbl">ยอดเงินในกระเป๋า</div>
        @php [$wInt, $wDec] = explode('.', number_format($walletBalance, 2)); @endphp
        <div class="nvm-wallet__amt nvm-num">฿{{ $wInt }}<span>.{{ $wDec }}</span></div>
        <div class="nvm-wallet__sub">คอมมิชชั่นรอรับ <b class="nvm-num">{{ $baht($stats['pending_commission'] ?? 0) }}</b> · รายได้สะสม <b class="nvm-num">{{ $baht($stats['total_earnings'] ?? 0) }}</b></div>
    </div>
    @if(count($walletActs))
        <div class="nvm-wallet__acts">
            @foreach($walletActs as $act)
                <a class="nvm-wact {{ $act['navy'] ? 'nvm-wact--navy' : '' }}" href="{{ $act['url'] }}"><span><svg class="nvm-ico"><use href="#nvm-i-{{ $act['icon'] }}"/></svg></span>{{ $act['label'] }}</a>
            @endforeach
        </div>
    @endif
</section>

{{-- ── แจ้งเตือน KYC (ยังไม่ส่ง / ถูกปฏิเสธ) ─────────────── --}}
@if($showKycAlert && $nvR('user.kyc.index'))
    <section class="nvm-card nvm-kyc {{ $kyc === 'rejected' ? 'nvm-kyc--bad' : '' }} nvm-rv">
        <span class="nvm-kyc__ic"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg></span>
        <div class="nvm-kyc__txt">
            @if($kyc === 'rejected')
                <b>เอกสารยืนยันตัวตนไม่ผ่าน กรุณาส่งใหม่</b>
                <p>ดูเหตุผลและส่งเอกสารใหม่ได้ที่หน้ายืนยันตัวตน เพื่อปลดล็อกการถอนเงิน</p>
            @else
                <b>ยืนยันตัวตนเพื่อปลดล็อกการถอนเงิน</b>
                <p>ถ่ายบัตรประชาชน + เซลฟี่ ใช้เวลาไม่กี่นาที แล้วใช้งานได้เต็มรูปแบบ</p>
            @endif
        </div>
        <a class="nvm-btn nvm-btn--gold" href="{{ route('user.kyc.index') }}">{{ $kyc === 'rejected' ? 'ส่งเอกสารใหม่' : 'ยืนยันตัวตนตอนนี้' }}</a>
    </section>
@endif

{{-- ── การ์ดตัวเลข 4 ใบ ─────────────────────────────────── --}}
<section class="nvm-kpis" aria-label="สรุปตัวเลข">
    @foreach($kpis as $k)
        <{{ $k['href'] ? 'a' : 'div' }} @if($k['href']) href="{{ $k['href'] }}" @endif class="nvm-card nvm-kpi nvm-rv">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/'.$k['img']) }}" alt="" width="78" height="78">
            <div class="nvm-kpi__lbl">{{ $k['label'] }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $k['en'] }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $k['value'] }}</div>
            <div class="nvm-kpi__foot">
                @if(! empty($k['badge']))
                    <span class="nvm-delta nvm-delta--mute">{{ $k['badge'] }}</span>
                @elseif(isset($k['delta']) && $k['delta'] != 0)
                    <span class="nvm-delta {{ $k['delta'] > 0 ? 'nvm-delta--up' : 'nvm-delta--down' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $k['delta'] > 0 ? 'up' : 'down' }}"/></svg>{{ number_format(abs($k['delta']), 1) }}%</span>
                @endif
                @if(! empty($k['note']))<small>{{ $k['note'] }}</small>@endif
            </div>
            @if(! empty($k['spark']) && $spark)
                <svg class="nvm-spark" viewBox="0 0 120 38" preserveAspectRatio="none" aria-hidden="true">
                    <path d="{{ $spark['line'] }}L120 38L0 38Z" fill="url(#nvm-spark-fill)"/>
                    <path d="{{ $spark['line'] }}" fill="none" stroke="#d4a64a" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
                    <circle cx="{{ $spark['last'][0] }}" cy="{{ $spark['last'][1] }}" r="3" fill="#f0c96a" stroke="#fff" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
                </svg>
            @endif
        </{{ $k['href'] ? 'a' : 'div' }}>
    @endforeach
</section>

{{-- ── กราฟรายได้ + ระดับสมาชิก ─────────────────────────── --}}
<div class="nvm-row2">
    <section class="nvm-card nvm-chart nvm-rv" aria-labelledby="nvm-chart-h"
             x-data="nvmChart(@js($chartValues), @js($chartLabels))">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Earnings</small><h2 id="nvm-chart-h">รายได้รายเดือน</h2></div>
            <div class="nvm-seg" role="group" aria-label="ช่วงเวลา">
                <button type="button" :class="range === 6 ? 'is-on' : ''" :aria-pressed="range === 6" @click="range = 6">6 เดือน</button>
                <button type="button" :class="range === 12 ? 'is-on' : ''" :aria-pressed="range === 12" @click="range = 12">12 เดือน</button>
            </div>
        </div>
        <div class="nvm-chart__sum"><b class="nvm-num" x-text="money(sum)">{{ $baht(array_sum($chartValues)) }}</b><span x-text="'รวม ' + range + ' เดือนล่าสุด'">รวม 12 เดือนล่าสุด</span></div>
        <div class="nvm-bars" x-show="sum > 0" @if(array_sum($chartValues) <= 0) x-cloak @endif>
            <template x-for="(b, i) in bars" :key="range + '-' + i">
                <div class="nvm-bar" :class="b.now ? 'is-now' : ''" :style="'--h:' + b.h + '%;--d:' + (i * 45) + 'ms'">
                    <em x-text="money(b.v)"></em><i></i><span x-text="b.l"></span>
                </div>
            </template>
        </div>
        <div class="nvm-empty" x-show="sum <= 0" @if(array_sum($chartValues) > 0) x-cloak @endif>
            <img src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="86" height="86">
            <b>ยังไม่มีรายได้ในช่วงนี้</b>
            ชวนเพื่อนคนแรกหรือขายสินค้าชิ้นแรก แล้วกราฟจะเริ่มเติบโตที่นี่
        </div>
    </section>

    <section class="nvm-card nvm-rank nvm-rv" aria-labelledby="nvm-rank-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Rank progress</small><h2 id="nvm-rank-h">ระดับสมาชิก</h2></div>
            @if($nvR('user.ranks.dashboard'))
                <a href="{{ route('user.ranks.dashboard') }}">สิทธิ์ทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        @if($currentRank || $nextRank)
            <div class="nvm-medal">
                <img src="{{ asset('images/nova/brand/tabbar-kanok-medallion.webp') }}" alt="">
                <svg viewBox="0 0 128 128" aria-hidden="true"><circle class="trk" cx="64" cy="64" r="56"/><circle class="val" cx="64" cy="64" r="56" style="--off: {{ $ringOff }}"/></svg>
                <div class="nvm-medal__txt">
                    <b class="nvm-num">{{ $nextRank ? number_format($rankPct, 0).'%' : '★' }}</b>
                    <small class="nvm-latin">{{ $nextEn ? 'to '.$nextEn : 'max rank' }}</small>
                </div>
            </div>
            <div class="nvm-rank__name">
                {{ $rankTh ?? 'สมาชิกใหม่' }}
                @if($nextTh)<svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg> {{ $nextTh }}@endif
            </div>
            <div class="nvm-rank__need">
                @if($rankReached)
                    คะแนนถึงเกณฑ์ระดับ{{ $nextTh }}แล้ว
                @elseif($nextTh)
                    ต้องการอีก <b class="nvm-num">{{ number_format($pointsNeeded ?? 0) }}</b> คะแนน
                @else
                    คุณอยู่ระดับสูงสุดแล้ว ยินดีด้วย!
                @endif
            </div>
            @if($rankLadder->count() > 1)
                <div class="nvm-ladder" aria-hidden="true">
                    @foreach($rankLadder as $step)
                        <i class="{{ (int) $step->level < $rankLevel ? 'done' : ((int) $step->level === $rankLevel ? 'now' : '') }}"></i>
                    @endforeach
                </div>
            @endif
            @if($hasVip)
                <a class="nvm-btn nvm-btn--line" href="{{ $idCardUrl }}"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>ดูบัตรสมาชิก VIP</a>
            @elseif($nvR('user.ranks.progress'))
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.ranks.progress') }}"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ดูวิธีเลื่อนระดับ</a>
            @endif
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86">
                <b>ระบบระดับสมาชิกยังไม่เปิดใช้งาน</b>
                เมื่อเปิดแล้ว ความคืบหน้าของคุณจะแสดงที่นี่
            </div>
        @endif
    </section>
</div>

{{-- ── บริการ ──────────────────────────────────────────── --}}
@if(count($services))
<section class="nvm-rv" aria-labelledby="nvm-svc-h">
    <div class="nvm-crest"><img src="{{ asset('images/nova/brand/tabbar-kanok-arch.webp') }}" alt="" aria-hidden="true"></div>
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Services</small><h2 id="nvm-svc-h">บริการของคุณ</h2></div>
        @if($nvR('user.home'))
            <a href="{{ route('user.home') }}">ทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        @endif
    </div>
    <div class="nvm-svc">
        @foreach($services as $s)
            <a href="{{ $s['url'] }}" @if(! empty($s['ext'])) target="_blank" rel="noopener" @endif>
                @if(! empty($s['tag']))<span class="nvm-svc__tag">{{ $s['tag'] }}</span>@endif
                <div class="nvm-svc__img"><img src="{{ asset('images/nova/svc/'.$s['img'].'.webp') }}" alt="" width="320" height="200" loading="lazy"></div>
                <div class="nvm-svc__body">
                    <span class="nvm-svc__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $s['icon'] }}"/></svg></span>
                    <b>{{ $s['name'] }}</b>
                    <small class="nvm-latin">{{ $s['en'] }}</small>
                    <span class="nvm-svc__go">{{ $s['go'] }}<svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg></span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- ── รักษายอด / กิจกรรม / ชวนเพื่อน ───────────────────── --}}
<div class="nvm-row3">
    <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-pv-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Retention</small><h2 id="nvm-pv-h">สถานะรักษายอด</h2></div></div>
        @if(! $rt)
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86">
                <b>ยังไม่ได้เข้าร่วมแผนสมาชิก</b>
                เข้าร่วมแผนเพื่อเริ่มสะสม PV และรับคอมมิชชั่นจากทีม
            </div>
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-btn nvm-btn--line nvm-btn--block" href="{{ route('user.mlm.dashboard') }}">ดูแผนสมาชิก</a>
            @endif
        @elseif(! $rtOn)
            @if($rtStatus === 'active')
                <div class="nvm-status"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><div>ไม่ต้องรักษายอดเดือนนี้<small>รับคอมมิชชั่นได้ตามปกติ</small></div></div>
            @else
                <div class="nvm-status is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><div>บัญชีสมาชิกยังไม่เปิดใช้งาน<small>ติดต่อทีมงานเพื่อเปิดใช้งาน</small></div></div>
            @endif
        @else
            @if($rtMemberBlocked)
                <div class="nvm-status is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><div>บัญชียังไม่ผ่านเงื่อนไขสมาชิก<small>ยังรับคอมมิชชั่นไม่ได้</small></div></div>
            @elseif($rtStatus === 'active')
                <div class="nvm-status"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><div>รักษายอดสำเร็จ<small>รับคอมมิชชั่นได้ครบเดือนนี้</small></div></div>
            @elseif($rtStatus === 'grace_period')
                <div class="nvm-status is-warn"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><div>อยู่ในช่วงผ่อนผัน<small>เหลืออีก {{ $rtGraceLeft }} วัน</small></div></div>
            @else
                <div class="nvm-status is-bad"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><div>ยังไม่ถึงเกณฑ์<small>ขาดอีก {{ number_format(max(0, $rt['remaining_pv'] ?? ($rtReq - $rtPv))) }} PV</small></div></div>
            @endif
            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>PV เดือนนี้</span><b class="nvm-num">{{ number_format($rtPv) }} / {{ number_format($rtReq) }} PV</b></div>
                <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($rtPct) }}" aria-label="PV เดือนนี้"><i style="width: {{ $rtPct }}%"></i></div>
                <div class="nvm-meter__ticks"><span>0</span><span>{{ number_format($rtReq / 2) }}</span><span>เกณฑ์ {{ number_format($rtReq) }}</span></div>
            </div>
            @if($rtStatus === 'inactive' && ! $rtMemberBlocked)
                <div class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>PV ไม่ถึงเกณฑ์ — คอมมิชชั่นจะถูกระงับจนกว่าจะซื้อสินค้าให้ครบ</span></div>
            @else
                <div class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>ซื้อสินค้าให้ครบ {{ number_format($rtReq) }} PV ต่อเดือนเพื่อรักษาสิทธิ์รับคอมมิชชั่น</span></div>
            @endif
        @endif
    </section>

    <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-act-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Activity</small><h2 id="nvm-act-h">กิจกรรมล่าสุด</h2></div>
            @if($nvR('user.commissions') && count($recentActivities))
                <a href="{{ route('user.commissions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        <div class="nvm-acts">
            {{-- 6 รายการพอดีการ์ด ไม่ต้องเลื่อนในกล่อง — ที่เหลือกด "ดูทั้งหมด" --}}
            @forelse($recentActivities->take(6) as $a)
                @php
                    $av = $actView[$a['status'] ?? ''] ?? $actWait;
                    $aType = $typeLabels[$a['commission_type'] ?? ''] ?? 'คอมมิชชั่น';
                @endphp
                <div class="nvm-act">
                    <span class="nvm-act__ic {{ $av['cls'] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $av['icon'] }}"/></svg></span>
                    <div class="nvm-act__t">
                        <b>{{ $av['title'] }}</b>
                        <small>{{ $aType }}@if($a['created_at']) · {{ $a['created_at']->locale('th')->diffForHumans() }}@endif</small>
                    </div>
                    <span class="nvm-act__amt nvm-num {{ $av['cls'] === 'no' ? 'no' : '' }}">{{ $av['cls'] === 'no' ? '' : '+' }}{{ $baht($a['amount'] ?? 0) }}</span>
                </div>
            @empty
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86">
                    <b>ยังไม่มีความเคลื่อนไหว</b>
                    เมื่อเพื่อนที่คุณชวนซื้อสินค้า คอมมิชชั่นจะมาแสดงที่นี่
                </div>
            @endforelse
        </div>
    </section>

    <section class="nvm-card nvm-box nvm-refer nvm-rv" aria-labelledby="nvm-ref-h">
        <img class="nvm-refer__coins" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" aria-hidden="true">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Invite &amp; earn</small><h2 id="nvm-ref-h">ชวนเพื่อน รับค่าแนะนำ</h2></div></div>
        @if($referralUrl)
            <p>เพื่อนสมัครผ่านลิงก์ของคุณ รับคอมมิชชั่นทุกครั้งที่เพื่อนซื้อสินค้า</p>
            <div class="nvm-refer__code" x-data="nvmCopy(@js($referralUrl))">
                <code x-ref="link">{{ preg_replace('#^https?://#', '', $referralUrl) }}</code>
                <button class="nvm-btn nvm-btn--gold nvm-btn--sm" type="button" @click="copy()"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
            </div>
            <div class="nvm-refer__share">
                <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode($referralUrl) }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>LINE</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($referralUrl) }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-fb"/></svg>Facebook</a>
                @if($nvR('user.mlm.referral'))
                    <a href="{{ route('user.mlm.referral') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>QR</a>
                @endif
            </div>
        @else
            <p>เข้าร่วมแผนสมาชิกเพื่อรับลิงก์ชวนเพื่อนของคุณเอง แล้วรับคอมมิชชั่นทุกครั้งที่เพื่อนซื้อสินค้า</p>
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-btn nvm-btn--gold nvm-btn--block" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>เข้าร่วมแผนสมาชิก</a>
            @endif
        @endif
        <div class="nvm-refer__stat">
            <div><b class="nvm-num">{{ number_format($thisMonthReferrals ?? 0) }}</b>สมัครเดือนนี้</div>
            <div><b class="nvm-num">{{ number_format($activeReferrals ?? 0) }}</b>ใช้งานอยู่</div>
            <div><b class="nvm-num">{{ number_format($totalReferrals ?? 0) }}</b>ทีมทั้งหมด</div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const baht = n => '฿' + Number(n).toLocaleString('en-US', { minimumFractionDigits: Number(n) % 1 ? 2 : 0, maximumFractionDigits: 2 });

    // กราฟแท่งรายได้ — สลับ 6/12 เดือน (เดือนล่าสุดเป็นแท่งทอง)
    Alpine.data('nvmChart', (values, labels) => ({
        range: 12,
        money: baht,
        get slice() { return values.slice(-this.range); },
        get sum() { return this.slice.reduce((a, b) => a + b, 0); },
        get bars() {
            const vals = this.slice, lbls = labels.slice(-this.range), mx = Math.max(1, ...vals);
            return vals.map((v, i) => ({ v, l: lbls[i] || '', h: (Math.max(3, v / mx * 100) * 0.78).toFixed(1), now: i === vals.length - 1 }));
        },
    }));

    // คัดลอกลิงก์ชวนเพื่อน — คลิปบอร์ดใช้ไม่ได้ (http/สิทธิ์) ก็เลือกข้อความให้กดคัดลอกเอง
    Alpine.data('nvmCopy', (link) => ({
        async copy() {
            try {
                await navigator.clipboard.writeText(link);
                window.showNotification('คัดลอกลิงก์ชวนเพื่อนแล้ว', 'success');
            } catch (e) {
                const r = document.createRange();
                r.selectNodeContents(this.$refs.link);
                const s = window.getSelection();
                s.removeAllRanges();
                s.addRange(r);
                window.showNotification('คัดลอกอัตโนมัติไม่ได้ — กดค้างที่ลิงก์เพื่อคัดลอกเอง', 'info');
            }
        },
    }));

    // บัตรสมาชิกเอียงตามเมาส์ — ปิดเองบนจอสัมผัส/ผู้ใช้ที่ลดการเคลื่อนไหว
    const tiltOk = matchMedia('(hover: hover)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches;
    Alpine.data('nvmTilt', () => ({
        move(e) {
            if (!tiltOk) return;
            const el = this.$el, r = el.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
            el.style.transition = 'transform .1s';
            el.style.transform = `rotateY(${x * 16}deg) rotateX(${-y * 12}deg) translateZ(6px)`;
            el.style.setProperty('--shine', (x * 120) + '%');
        },
        reset() {
            const el = this.$el;
            el.style.transition = '';
            el.style.transform = '';
            el.style.setProperty('--shine', '-60%');
        },
    }));
});
</script>
@endpush

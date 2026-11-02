{{--
    ชวนเพื่อนดูดวง (ธีมโนวาเต็ม) — FortuneReferralDashboardController::recruit
    - $referralLink = ลิงก์เชิญจาก FortuneAffiliateService (อาจว่างถ้าสร้างไม่สำเร็จ)
    - $packageRates = อัตรารายแพคเกจ (null = โหมดอัตราเดียว ใช้ $readingPrice/$level1Amount/$level2Amount)
--}}
@extends('layouts.user-nova')

@section('title', 'ชวนเพื่อนดูดวง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/fortune-referral.css') }}?v={{ filemtime(public_path('theme-nova/member/fortune-referral.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $packageRates = $packageRates ?? null;
    $shareText = 'มาดูดวงกัน! '.$referralLink;

    // จำลองรายได้ — โหมดแยกแพคเกจอ้างอิง Celtic Cross (แพคเกจที่ค่าแนะนำสูงสุด)
    $simL1 = $packageRates ? $packageRates['celtic_cross']['l1'] : $level1Amount;
    $simL2Enabled = $packageRates ? $packageRates['celtic_cross']['l2_enabled'] : $level2Enabled;
    $simL2 = $packageRates ? $packageRates['celtic_cross']['l2'] : $level2Amount;
    $simLabel = $packageRates ? $packageRates['celtic_cross']['label'] : 'ดูดวง';
    $monthlyEstimate = ($simL1 * 10 * 3) + ($simL2Enabled ? $simL2 * 10 * 5 * 3 : 0);

    $tips = [
        ['line', 'แชร์ในกลุ่ม LINE', 'ส่งลิงก์เชิญในกลุ่มเพื่อน กลุ่มดูดวง กลุ่มที่สนใจเรื่องโชคชะตา'],
        ['fb', 'โพสต์ใน Social Media', 'แชร์ประสบการณ์ดูดวงพร้อมลิงก์เชิญใน Facebook, Instagram, TikTok'],
        ['send', 'ส่งให้เพื่อนโดยตรง', 'ส่งลิงก์ส่วนตัวให้เพื่อนที่สนใจดูดวง พร้อมบอกข้อดี'],
        ['award', 'ชวนเพื่อนชวนต่อ', 'บอกเพื่อนว่าชวนต่อก็ได้เงิน — คุณได้จากหลานด้วย (Level 2)'],
    ];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-fr-r-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.genealogy'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.genealogy') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>สายงานของฉัน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Invite friends</div>
            <h1 id="nvm-fr-r-h">ชวนเพื่อนมาดูดวง</h1>
            <p class="nvm-ph__lead">แชร์ลิงก์เชิญเพื่อน → เพื่อนดูดวง → คุณรับคอมมิชชั่นทันที!</p>
            <div class="nvm-pills">
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.fortune-referral.commissions') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>คอมมิชชั่นของฉัน</a>
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.fortune-referral.tree') }}"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>ผังสายงานดูดวง</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/svc/fortune.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        {{-- ── ลิงก์เชิญเพื่อน ─────────────────────────────── --}}
        <section class="nvm-card nvm-refer nvm-fr-link" aria-labelledby="nvm-fr-link-h">
            <img class="nvm-refer__coins" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="120" height="120" loading="lazy">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Your invite link</small><h2 id="nvm-fr-link-h">ลิงก์เชิญเพื่อน</h2></div></div>

            @if($referralLink !== '')
                <p>ส่งลิงก์นี้ให้เพื่อน เมื่อเพื่อนเข้ามาดูดวงผ่านลิงก์ คุณจะได้ค่าแนะนำอัตโนมัติ</p>
                <div class="nvm-refer__code">
                    <code>{{ $referralLink }}</code>
                    <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" x-data
                            @click="nvmCopy(@js($referralLink), 'คัดลอกลิงก์เชิญแล้ว')">
                        <svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก
                    </button>
                </div>
                <div class="nvm-filter__btns nvm-fr-share">
                    <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="https://line.me/R/share?text={{ urlencode($shareText) }}" target="_blank" rel="noopener">
                        <svg class="nvm-ico"><use href="#nvm-i-line"/></svg>แชร์ LINE
                    </a>
                    <button type="button" class="nvm-btn nvm-btn--ghost nvm-btn--sm" x-data
                            @click="nvmCopy(@js("มาดูดวงกัน!\n".$referralLink), 'คัดลอกข้อความชวนแล้ว')">
                        <svg class="nvm-ico"><use href="#nvm-i-send"/></svg>คัดลอกข้อความ
                    </button>
                </div>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ลิงก์เชิญแต่ละลิงก์มีอายุ 24 ชั่วโมง เปิดหน้านี้ใหม่เพื่อรับลิงก์ล่าสุด</p>
            @else
                <p>ยังสร้างลิงก์เชิญให้ไม่ได้ในตอนนี้ กรุณาลองเปิดหน้านี้ใหม่อีกครั้ง หรือติดต่อทีมงาน</p>
            @endif
        </section>

        {{-- ── ตัวอย่างรายได้ ─────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-fr-earn-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Earnings example</small><h2 id="nvm-fr-earn-h">ตัวอย่างรายได้จากการแนะนำ</h2></div></div>

            @if($packageRates)
                {{-- 📦 (2026-07-15) โหมดค่าแนะนำแยกตามแพคเกจ --}}
                <p class="nvm-help">ค่าแนะนำขึ้นกับแพคเกจที่เพื่อนเลือก — เพื่อนจ่ายจริงเมื่อไหร่ คุณได้เมื่อนั้น</p>
                <div class="nvm-fr-pkgs nvm-gap-t">
                    @foreach($packageRates as $pkg)
                        <div class="nvm-fr-pkg">
                            <div class="nvm-fr-pkg__h"><b>{{ $pkg['label'] }}</b><span class="nvm-num">{{ number_format($pkg['price'], 0) }} บาท</span></div>
                            <dl class="nvm-kv">
                                <dt>สายตรง (เพื่อนที่คุณชวนเอง)</dt>
                                <dd class="nvm-num nvm-fr-in">+{{ number_format($pkg['l1'], 0) }} บาท</dd>
                                <dt>ชั้นหลาน (เพื่อนของเพื่อน)</dt>
                                @if($pkg['l2_enabled'])
                                    <dd class="nvm-num nvm-fr-l2">+{{ number_format($pkg['l2'], 0) }} บาท</dd>
                                @else
                                    <dd class="nvm-fr-mute">แพคเกจนี้ไม่มี</dd>
                                @endif
                            </dl>
                        </div>
                    @endforeach
                </div>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ค่าแนะนำมี 2 ชั้นเท่านั้น · ชวนได้ไม่จำกัดจำนวนคน ไม่มีเพดานรายได้</p>
            @else
                <p class="nvm-help">ราคาดูดวง: <b class="nvm-num">{{ number_format($readingPrice, 0) }} บาท</b></p>
                <ul class="nvm-txs">
                    <li class="nvm-tx">
                        <span class="nvm-tx__ic is-in"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg></span>
                        <div class="nvm-tx__t nvm-tx__t--wrap">
                            <b>Level 1 — เพื่อนดูดวง 1 ครั้ง</b>
                            <small>{{ $level1Type === 'fixed' ? 'จำนวนคงที่' : 'เปอร์เซ็นต์ '.($settings->fortune_level1_commission_amount ?? 10).'%' }}</small>
                        </div>
                        <div class="nvm-tx__r"><span class="nvm-tx__amt nvm-num is-in">+{{ number_format($level1Amount, 2) }} บาท</span></div>
                    </li>
                    @if($level2Enabled)
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg></span>
                            <div class="nvm-tx__t nvm-tx__t--wrap">
                                <b>Level 2 — หลานดูดวง 1 ครั้ง</b>
                                <small>{{ $level2Type === 'fixed' ? 'จำนวนคงที่' : 'เปอร์เซ็นต์ '.($settings->fortune_level2_commission_amount ?? 5).'%' }}</small>
                            </div>
                            <div class="nvm-tx__r"><span class="nvm-tx__amt nvm-num nvm-fr-l2">+{{ number_format($level2Amount, 2) }} บาท</span></div>
                        </li>
                    @endif
                </ul>
            @endif

            {{-- จำลองรายได้ --}}
            <div class="nvm-fr-sim">
                <h3 class="nvm-fr-sim__h">จำลองรายได้ @if($packageRates)<small>(ถ้าเพื่อนเลือก {{ $simLabel }})</small>@endif</h3>
                <div class="nvm-minis">
                    <div class="nvm-mini">
                        <small>ชวน 10 คน × ดูดวง 3 ครั้ง (สายตรง)</small>
                        <b class="nvm-num is-in">{{ number_format($simL1 * 10 * 3, 0) }} บาท</b>
                    </div>
                    @if($simL2Enabled)
                        <div class="nvm-mini">
                            <small>10 คน × ชวนอีกคนละ 5 หลาน × 3 ครั้ง (ชั้นหลาน)</small>
                            <b class="nvm-num nvm-fr-l2">{{ number_format($simL2 * 10 * 5 * 3, 0) }} บาท</b>
                        </div>
                    @endif
                    <div class="nvm-mini nvm-mini--wide">
                        <small>รวมต่อเดือน (ประมาณ)</small>
                        <b class="nvm-num nvm-foil">{{ number_format($monthlyEstimate, 0) }} บาท</b>
                    </div>
                </div>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เป็นเพียงตัวอย่างการคำนวณ ไม่ใช่การรับประกันรายได้ — รายได้จริงขึ้นกับจำนวนเพื่อนที่ใช้บริการจริง</p>
            </div>
        </section>
    </div>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-fr-how-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-fr-how-h">ได้ค่าแนะนำอย่างไร</h2></div></div>
        <ol class="nvm-steps">
            <li>คัดลอกหรือแชร์ลิงก์เชิญของคุณให้เพื่อน</li>
            <li>เพื่อนเปิดลิงก์แล้วเริ่มดูดวงกับแม่หมอ</li>
            <li>เมื่อเพื่อนชำระค่าดูดวง คุณได้ค่าแนะนำชั้นสายตรง (Level 1)</li>
            <li>ถ้าเพื่อนชวนคนอื่นต่อ คุณได้ค่าแนะนำชั้นหลาน (Level 2) ด้วย</li>
        </ol>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ค่าแนะนำจะเข้าเมื่อเพื่อนชำระค่าดูดวงสำเร็จแล้วเท่านั้น</p>
    </aside>
</div>

{{-- ── เทคนิคการแชร์ ───────────────────────────────────── --}}
<section aria-labelledby="nvm-fr-tips-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Sharing tips</small><h2 id="nvm-fr-tips-h">เทคนิคการแชร์</h2></div></div>
    <div class="nvm-fr-tips">
        @foreach($tips as [$icon, $title, $text])
            <div class="nvm-card nvm-fr-tip nvm-rv">
                <span class="nvm-fr-tip__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                <b>{{ $title }}</b>
                <p>{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<div class="nvm-fr-cta">
    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.fortune-referral.commissions') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ดูคอมมิชชั่นของฉัน</a>
</div>

</div>
@endsection

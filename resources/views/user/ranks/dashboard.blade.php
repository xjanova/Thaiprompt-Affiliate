{{--
    ระดับของฉัน (ธีมโนวาเต็ม) — RankController::dashboard
    ตัวแปร: $user, $currentRank, $nextRank, $pointsPercent, $pointsNeeded, $leaderboardPosition,
            $currentRankBonuses, $currentRankPrivileges, $nextRankRequirements, $nextRankRequirementsProgress,
            $nextRankBonuses, $nextRankPrivileges, $allRanks
    - หลอดคะแนนใช้สูตรเดียวกับการ์ดระดับบนแดชบอร์ดสมาชิก (เกณฑ์ min_points ของยศ) ตัวเลขสองหน้าจึงตรงกัน
    - ยังไม่มียศ: $nextRank = ยศแรก (ไม่ใช่ "ระดับสูงสุดแล้ว")
--}}
@extends('layouts.user-nova')

@section('title', 'ระดับของฉัน')

@php
    $rankName = fn ($r) => $r ? ($r->name_th ?: $r->name) : null;
    $currentName = $rankName($currentRank) ?? 'สมาชิกใหม่';
    $nextName = $rankName($nextRank);
    $points = (int) ($user->rank_points ?? 0);
    $level = (int) ($currentRank?->level ?? 0);
    $rate = (float) ($currentRank?->commission_rate ?? 0);
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');

    // วงแหวนบนเหรียญ (r=56 → เส้นรอบวง 351.9 ตรงกับ stroke-dasharray ใน nova-member.css)
    $ringOff = round(351.9 * (1 - $pointsPercent / 100), 1);

    $bonusIcons = ['one_time' => '💰', 'monthly' => '📆', 'commission' => '📈', 'multiplier' => '✖️', 'privilege' => '⭐', 'reward' => '🎁'];
    $reqIcons = ['points' => 'award', 'referrals' => 'users', 'active_referrals' => 'users', 'sales' => 'cash', 'team_sales' => 'cash', 'consecutive_months' => 'clock'];

    // สิ่งที่จะได้รับ: โบนัส (มีจำนวนเงิน/เปอร์เซ็นต์) + สิทธิพิเศษ รวมเป็นรายการเดียว
    $perks = function ($bonuses, array $privileges) use ($bonusIcons, $pct) {
        $out = [];
        foreach ($bonuses as $b) {
            $out[] = [
                'icon' => $bonusIcons[$b->bonus_type] ?? '🎁',
                'name' => $b->name_th ?: $b->name,
                'value' => $b->amount > 0 ? '฿'.number_format((float) $b->amount, 2) : ($b->percentage > 0 ? $pct($b->percentage).'%' : null),
                'desc' => $b->description_th ?: $b->description,
            ];
        }
        foreach ($privileges as $p) {
            $out[] = ['icon' => $p['icon'] ?? '⭐', 'name' => $p['name_th'] ?? $p['name'], 'value' => null, 'desc' => $p['description_th'] ?? $p['description'] ?? null];
        }

        return $out;
    };
    $myPerks = $perks($currentRankBonuses, $currentRankPrivileges);
    $nextPerks = $perks($nextRankBonuses, $nextRankPrivileges);
    $metCount = collect($nextRankRequirementsProgress)->where('met', true)->count();
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-rk-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด</a>
            <div class="nvm-eyebrow nvm-latin">My rank{{ $level ? ' · level '.$level : '' }}</div>
            <h1 id="nvm-rk-h">{{ $currentName }}</h1>
            <p class="nvm-ph__lead">
                @if($currentRank)
                    {{ $currentRank->description_th ?: ($currentRank->description ?: 'ระดับปัจจุบันของคุณ') }}
                @else
                    สะสมคะแนนจากการแนะนำและยอดขาย เพื่อรับยศแรกและสิทธิพิเศษ
                @endif
            </p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg><span class="nvm-num">{{ number_format($points) }}</span> คะแนน</span>
                @if($currentRank)
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>อันดับ <span class="nvm-num">#{{ number_format($leaderboardPosition) }}</span></span>
                @endif
                @if($rate > 0)
                    <span class="nvm-pill nvm-pill--gold">ค่าคอม {{ $pct($rate) }}%</span>
                @endif
            </div>
        </div>
        <div class="nvm-ph__art nvm-rk-hero">
            @if($currentRank)
                <x-nova.rank-badge :rank="$currentRank" size="xl" />
            @else
                <img src="{{ asset('images/nova/brand/tabbar-kanok-medallion.webp') }}" alt="" width="200" height="200">
            @endif
        </div>
    </div>
</section>

<div class="nvm-split">
    {{-- ── ความคืบหน้าสู่ระดับถัดไป ─────────────────────── --}}
    @if($nextRank)
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rk-next-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Next rank</small><h2 id="nvm-rk-next-h">เป้าหมายถัดไป: {{ $nextName }}</h2></div>
                @if($nextRankRequirements->count())
                    <span class="nvm-chip {{ $metCount === $nextRankRequirements->count() ? 'nvm-chip--ok' : 'nvm-chip--warn' }}">ผ่าน {{ $metCount }}/{{ $nextRankRequirements->count() }}</span>
                @endif
            </div>

            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>คะแนนสะสม</span><b class="nvm-num">{{ number_format($points) }} / {{ number_format((int) $nextRank->min_points) }}</b></div>
                <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($pointsPercent) }}" aria-label="คะแนนสู่ระดับ{{ $nextName }}"><i style="width: {{ $pointsPercent }}%"></i></div>
            </div>
            <p class="nvm-note">
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                @if($pointsNeeded > 0)
                    <span>อีก <b class="nvm-num">{{ number_format($pointsNeeded) }}</b> คะแนนจะถึงเกณฑ์ระดับ{{ $nextName }}</span>
                @else
                    <span>คะแนนถึงเกณฑ์ระดับ{{ $nextName }}แล้ว{{ $nextRankRequirements->count() ? ' ทำเงื่อนไขด้านล่างให้ครบเพื่อเลื่อนระดับ' : '' }}</span>
                @endif
            </p>

            <h3 class="nvm-rk-h3">เงื่อนไขที่ต้องทำให้ครบ</h3>
            @if($nextRankRequirements->count())
                <ul class="nvm-txs">
                    @foreach($nextRankRequirements as $req)
                        @php
                            $p = $nextRankRequirementsProgress[$req->id] ?? [];
                            $met = (bool) ($p['met'] ?? false);
                            $reqPct = min(100, max(0, (float) ($p['percentage'] ?? 0)));
                            $reqDesc = $req->description_th ?: $req->description;
                        @endphp
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic {{ $met ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $met ? 'check' : ($reqIcons[$req->requirement_type] ?? 'award') }}"/></svg></span>
                            <div class="nvm-tx__t nvm-tx__t--wrap">
                                <b>{{ $req->name_th ?: $req->name }}@if($req->is_required) <em class="nvm-rk-req" title="จำเป็น">*</em>@endif</b>
                                @if($reqDesc)<small>{{ $reqDesc }}</small>@endif
                                @unless($met)
                                    <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($reqPct) }}" aria-label="{{ $req->name_th ?: $req->name }}"><i style="width: {{ $reqPct }}%"></i></div>
                                @endunless
                            </div>
                            <div class="nvm-tx__r">
                                @if($met)
                                    <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ผ่านแล้ว</span>
                                @else
                                    <span class="nvm-tx__amt nvm-num">{{ number_format((float) ($p['current'] ?? 0)) }}<small class="nvm-rk-of"> / {{ number_format((float) ($p['target'] ?? $req->target_value)) }} {{ $req->unit }}</small></span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เครื่องหมาย * คือเงื่อนไขที่จำเป็นต้องผ่าน</p>
            @else
                <p class="nvm-desc">ระดับนี้ไม่มีเงื่อนไขเพิ่มเติม สะสมคะแนนให้ถึงเกณฑ์ก็เลื่อนระดับได้</p>
            @endif
        </section>
    @else
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rk-top-h">
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b id="nvm-rk-top-h">{{ $currentRank ? 'ยินดีด้วย! คุณอยู่ระดับสูงสุดแล้ว' : 'ระบบระดับสมาชิกยังไม่เปิดใช้งาน' }}</b>
                {{ $currentRank ? 'รักษายอดและดูแลทีมต่อไป เพื่อรับสิทธิพิเศษของระดับนี้ทุกเดือน' : 'เมื่อเปิดแล้ว ความคืบหน้าของคุณจะแสดงที่นี่' }}
            </div>
        </section>
    @endif

    {{-- ── เหรียญความคืบหน้า + ทางลัด ─────────────────────── --}}
    <aside class="nvm-stack">
        <section class="nvm-card nvm-rank" aria-labelledby="nvm-rk-ring-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Progress</small><h2 id="nvm-rk-ring-h">ความคืบหน้า</h2></div></div>
            <div class="nvm-medal">
                <img src="{{ asset('images/nova/brand/tabbar-kanok-medallion.webp') }}" alt="">
                <svg viewBox="0 0 128 128" aria-hidden="true"><circle class="trk" cx="64" cy="64" r="56"/><circle class="val" cx="64" cy="64" r="56" style="--off: {{ $ringOff }}"/></svg>
                <div class="nvm-medal__txt">
                    <b class="nvm-num">{{ $nextRank ? number_format($pointsPercent, 0).'%' : '★' }}</b>
                    <small class="nvm-latin">{{ $nextRank ? 'to '.strtolower((string) $nextRank->name) : 'max rank' }}</small>
                </div>
            </div>
            <div class="nvm-rank__name">
                {{ $currentName }}
                @if($nextName)<svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg> {{ $nextName }}@endif
            </div>
            @if($allRanks->count() > 1)
                <div class="nvm-ladder" aria-hidden="true">
                    @foreach($allRanks as $step)
                        <i class="{{ $step->level < $level ? 'done' : ($step->level === $level ? 'now' : '') }}"></i>
                    @endforeach
                </div>
            @endif
            <a class="nvm-btn nvm-btn--line" href="{{ route('user.ranks.id-card') }}"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>ดูบัตรสมาชิก</a>
        </section>

        <section class="nvm-card nvm-box" aria-label="ทางลัด">
            <ul class="nvm-txs">
                <li class="nvm-tx nvm-rk-link">
                    <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg></span>
                    <a class="nvm-tx__t" href="{{ route('user.ranks.progress') }}"><b>เส้นทางทุกระดับ</b><small>เงื่อนไขและความคืบหน้าแต่ละยศ</small></a>
                    <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                </li>
                <li class="nvm-tx nvm-rk-link">
                    <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg></span>
                    <a class="nvm-tx__t" href="{{ route('user.ranks.leaderboard') }}"><b>กระดานผู้นำ</b><small>อันดับคะแนนของสมาชิกทั้งระบบ</small></a>
                    <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                </li>
            </ul>
        </section>
    </aside>
</div>

{{-- ── สิทธิ์ที่มีตอนนี้ / สิ่งที่จะได้เมื่อเลื่อนระดับ ───── --}}
@if(count($myPerks) || count($nextPerks))
<div class="nvm-rk-cols">
    @foreach([[$myPerks, 'Your perks', 'สิทธิพิเศษของคุณตอนนี้', 'nvm-rk-mine'], [$nextPerks, 'Unlock next', $nextName ? 'ปลดล็อกเมื่อขึ้นระดับ'.$nextName : null, 'nvm-rk-unlock']] as [$list, $en, $title, $id])
        @if(count($list) && $title)
            <section class="nvm-card nvm-box" aria-labelledby="{{ $id }}">
                <div class="nvm-sec-h"><div><small class="nvm-latin">{{ $en }}</small><h2 id="{{ $id }}">{{ $title }}</h2></div></div>
                <ul class="nvm-rk-perks">
                    @foreach($list as $perk)
                        <li>
                            <span class="nvm-rk-perks__ic" aria-hidden="true">{{ $perk['icon'] }}</span>
                            <div>
                                <b>{{ $perk['name'] }}</b>
                                @if($perk['value'])<span class="nvm-rk-perks__val nvm-num">{{ $perk['value'] }}</span>@endif
                                @if($perk['desc'])<small>{{ $perk['desc'] }}</small>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach
</div>
@endif

{{-- ── เส้นทางทุกระดับ ──────────────────────────────────── --}}
@if($allRanks->count())
<section class="nvm-card nvm-box" aria-labelledby="nvm-rk-road-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Roadmap</small><h2 id="nvm-rk-road-h">เส้นทางสู่ความสำเร็จ</h2></div>
        <a href="{{ route('user.ranks.progress') }}">ดูเงื่อนไขทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
    </div>
    <ol class="nvm-rk-road">
        @foreach($allRanks as $rank)
            @php
                $isNow = $currentRank && $currentRank->id === $rank->id;
                $isDone = ! $isNow && $level >= $rank->level && $currentRank;
            @endphp
            <li class="{{ $isNow ? 'is-now' : ($isDone ? 'is-done' : '') }}" @if($isNow) aria-current="step" @endif>
                <x-nova.rank-badge :rank="$rank" />
                <div>
                    <b>{{ $rankName($rank) }}</b>
                    <small>ระดับ {{ $rank->level }}{{ $rank->min_points ? ' · '.number_format((int) $rank->min_points).' คะแนน' : '' }}{{ (float) $rank->commission_rate > 0 ? ' · ค่าคอม '.$pct($rank->commission_rate).'%' : '' }}</small>
                    @if($rank->activeBonuses->count())
                        <div class="nvm-rk-gifts">
                            @foreach($rank->activeBonuses->take(3) as $bonus)
                                <span class="nvm-chip"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>{{ $bonus->name_th ?: $bonus->name }}</span>
                            @endforeach
                            @if($rank->activeBonuses->count() > 3)<span class="nvm-chip">+{{ $rank->activeBonuses->count() - 3 }}</span>@endif
                        </div>
                    @endif
                </div>
                @if($isNow)
                    <span class="nvm-chip nvm-chip--gold">คุณอยู่ที่นี่</span>
                @elseif($isDone)
                    <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ผ่านแล้ว</span>
                @endif
            </li>
        @endforeach
    </ol>
</section>
@endif

{{-- ── วิธีสะสมคะแนน ────────────────────────────────────── --}}
<section class="nvm-card nvm-box" aria-labelledby="nvm-rk-tip-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">How to grow</small><h2 id="nvm-rk-tip-h">วิธีสะสมคะแนนและเลื่อนระดับ</h2></div></div>
    <ol class="nvm-steps nvm-rk-tips">
        <li><span><b>แนะนำสมาชิกใหม่</b> ชวนเพื่อนมาสมัครเพื่อรับคะแนนโบนัส</span></li>
        <li><span><b>สร้างยอดขาย</b> ขายสินค้าและบริการเพื่อสะสม PV และคะแนน</span></li>
        <li><span><b>พัฒนาทีม</b> ช่วยให้ทีมเติบโตเพื่อรับโบนัสทีม</span></li>
        <li><span><b>รักษาความต่อเนื่อง</b> ทำยอดสม่ำเสมอทุกเดือน</span></li>
    </ol>
</section>

</div>
@endsection

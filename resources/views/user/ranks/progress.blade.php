{{--
    เส้นทางทุกระดับ (ธีมโนวาเต็ม) — RankController::progress
    ตัวแปร: $user, $allRanks (พร้อม activeRequirements), $userProgress (keyBy target_rank_id),
            $currentRank, $nextRank, $pointsPercent, $pointsNeeded
    🐛 หน้าเดิมวนลูป $rank->requirements ซึ่งเป็น relation → พิมพ์ JSON ดิบของเงื่อนไขออกมาทั้งก้อน
       และอ่าน $rank->benefits / $user->team_count ที่ไม่มีอยู่จริง — ตอนนี้ใช้ชื่อเงื่อนไขจาก activeRequirements
--}}
@extends('layouts.user-nova')

@section('title', 'เส้นทางทุกระดับ')

@php
    $rankName = fn ($r) => $r ? ($r->name_th ?: $r->name) : null;
    $level = (int) ($currentRank?->level ?? 0);
    $points = (int) ($user->rank_points ?? 0);
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-rp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.ranks.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ระดับของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Rank roadmap</div>
            <h1 id="nvm-rp-h">เส้นทางทุกระดับ</h1>
            <p class="nvm-ph__lead">
                @if($nextRank && $pointsNeeded > 0)
                    อีก {{ number_format($pointsNeeded) }} คะแนนจะถึงเกณฑ์ระดับ{{ $rankName($nextRank) }}
                @elseif($nextRank)
                    คะแนนถึงเกณฑ์ระดับ{{ $rankName($nextRank) }}แล้ว ทำเงื่อนไขให้ครบเพื่อเลื่อนระดับ
                @elseif($currentRank)
                    คุณอยู่ระดับสูงสุดแล้ว ยินดีด้วย!
                @else
                    เงื่อนไขและสิทธิ์ของแต่ละยศ
                @endif
            </p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ $rankName($currentRank) ?? 'ยังไม่มียศ' }}</span>
                <span class="nvm-pill nvm-pill--glass"><span class="nvm-num">{{ number_format($points) }}</span> คะแนน</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-rp-list-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">All ranks</small><h2 id="nvm-rp-list-h">ยศทั้งหมด {{ $allRanks->count() }} ระดับ</h2></div></div>

    @if($allRanks->count())
        <ol class="nvm-rk-road nvm-rk-road--full">
            @foreach($allRanks as $rank)
                @php
                    $isNow = $currentRank && $currentRank->id === $rank->id;
                    $isDone = ! $isNow && $currentRank && $level >= $rank->level;
                    $isNext = $nextRank && $nextRank->id === $rank->id;
                    $row = $userProgress->get($rank->id);
                    $desc = $rank->description_th ?: $rank->description;
                    // ยศถัดไปใช้หลอดคะแนนชุดเดียวกับหน้า "ระดับของฉัน" · ยศที่ไกลกว่านั้นใช้ผลคำนวณเงื่อนไขล่าสุด (ถ้ามี)
                    $barPct = $isNext ? $pointsPercent : ($row ? min(100, max(0, (float) $row->progress_percentage)) : null);
                @endphp
                <li class="{{ $isNow ? 'is-now' : ($isDone ? 'is-done' : '') }}" @if($isNow) aria-current="step" @endif>
                    <x-nova.rank-badge :rank="$rank" size="lg" />
                    <div>
                        <b>{{ $rankName($rank) }}</b>
                        <small>ระดับ {{ $rank->level }}{{ $rank->min_points ? ' · เกณฑ์ '.number_format((int) $rank->min_points).' คะแนน' : '' }}{{ (float) $rank->commission_rate > 0 ? ' · ค่าคอม '.$num($rank->commission_rate).'%' : '' }}</small>
                        @if($desc)<p class="nvm-desc">{{ $desc }}</p>@endif

                        @if($rank->activeRequirements->count())
                            <ul class="nvm-rk-conds" aria-label="เงื่อนไขของระดับ{{ $rankName($rank) }}">
                                @foreach($rank->activeRequirements as $req)
                                    @php
                                        $reqName = $req->name_th ?: $req->name;
                                        $target = $num($req->target_value);
                                        // ชื่อจาก RankSeeder มีตัวเลขในตัวอยู่แล้ว ("แนะนำ 5 คน") — ต่อท้ายเฉพาะชื่อที่ยังไม่บอกเป้า
                                        $showTarget = (float) $req->target_value > 0 && ! str_contains($reqName, $target) && ! str_contains($reqName, number_format((float) $req->target_value));
                                    @endphp
                                    <li class="{{ $isNow || $isDone ? 'is-met' : '' }}">
                                        <svg class="nvm-ico"><use href="#nvm-i-{{ $isNow || $isDone ? 'check' : 'clock' }}"/></svg>
                                        <span>{{ $reqName }}@if($showTarget) <b class="nvm-num">{{ $target }}</b> {{ $req->unit }}@endif</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(! $isNow && ! $isDone && $barPct !== null)
                            <div class="nvm-meter">
                                <div class="nvm-meter__row"><span>{{ $isNext ? 'คะแนนสู่ระดับนี้' : 'ความคืบหน้าล่าสุด' }}</span><b class="nvm-num">{{ number_format($barPct, 1) }}%</b></div>
                                <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($barPct) }}" aria-label="ความคืบหน้าสู่ระดับ{{ $rankName($rank) }}"><i style="width: {{ $barPct }}%"></i></div>
                            </div>
                        @endif
                    </div>
                    @if($isNow)
                        <span class="nvm-chip nvm-chip--gold">ยศปัจจุบัน</span>
                    @elseif($isDone)
                        <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ผ่านแล้ว</span>
                    @elseif($isNext)
                        <span class="nvm-chip nvm-chip--warn">เป้าหมายถัดไป</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ระบบระดับสมาชิกยังไม่เปิดใช้งาน</b>
            เมื่อเปิดแล้ว เงื่อนไขของแต่ละยศจะแสดงที่นี่
        </div>
    @endif
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-rp-tip-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-rp-tip-h">เคล็ดลับเพื่อเลื่อนยศ</h2></div></div>
    <ol class="nvm-steps nvm-rk-tips">
        <li><span><b>สร้างทีมใหม่</b> เชิญสมาชิกใหม่เพื่อขยายทีมและสร้างรายได้</span></li>
        <li><span><b>รักษาทีมที่มีอยู่</b> ช่วยเหลือทีมให้ประสบความสำเร็จ</span></li>
        <li><span><b>สะสม PV</b> ซื้อสินค้าและบริการเพื่อสะสมคะแนน</span></li>
        <li><span><b>เรียนรู้ตลอดเวลา</b> พัฒนาทักษะการขายและการสร้างทีม</span></li>
    </ol>
</section>

</div>
@endsection

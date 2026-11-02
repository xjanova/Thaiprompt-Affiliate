{{--
    แดชบอร์ด MLM ของสมาชิก (ธีมโนวาเต็ม) — MlmDashboardController::index
    ตัวแปร: $member (พร้อม plan), $recentCommissions, $retentionStatus, $rankInfo, $referralUrl,
            $commissionTypes, $commissionStatuses
    🐛 หน้าเดิม: การ์ด "Rank Progress" เป็นตัวเลขตายตัว (65% · 5,000/5,000 · Silver) ไม่ใช่ข้อมูลจริง,
       ชื่อประเภทคอมมิชชั่นว่าง (อ่าน type_display ที่โมเดลไม่มี), กล่อง QR ว่างเปล่าตลอด
--}}
@extends('layouts.user-nova')

@section('title', 'แดชบอร์ด MLM')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $rankName = fn ($r) => $r ? ($r->name_th ?: $r->name) : null;

    $planType = $member->plan?->type;
    $showBinary = in_array($planType, ['binary', 'hybrid'], true);

    // ── สถานะรักษายอด (Volume Retention) ──
    $rtEnabled = $retentionStatus['retention_enabled'] ?? true;
    $rtStatus = $retentionStatus['status'] ?? 'inactive';
    [$rtTone, $rtIcon, $rtText] = match ($rtStatus) {
        'active' => ['ok', 'check', 'รักษายอดสำเร็จ'],
        'grace_period' => ['warn', 'clock', 'อยู่ในช่วงผ่อนผัน'],
        default => ['bad', 'info', 'ยังไม่ถึงเกณฑ์'],
    };
    $pvPct = min(100, max(0, (float) ($retentionStatus['pv_percentage'] ?? 0)));
    // วงแหวน r=52 → เส้นรอบวง 326.7
    $ringOff = round(326.7 - 326.7 * $pvPct / 100, 1);

    // ── ประมาณการ Binary ด้วยสูตรเดียวกับรอบจับคู่จริง (รวม PV ยกยอด, ก่อนหักเพดานรายวัน) ──
    $estBin = app(\App\Services\MlmBinaryService::class)->estimatePairs($member);
    $weakLegPv = $estBin['weak_pv'];
    $pairComm = (float) \App\Models\MlmGlobalSetting::get('binary_pair_commission', 100);
    $pairLabel = $estBin['pairing_type'];
    $estPairs = (int) $estBin['pairs'];

    $statusTone = ['paid' => 'ok', 'approved' => 'info', 'pending' => 'warn', 'rejected' => 'bad', 'cancelled' => 'bad', 'clawback' => 'bad'];

    // เมนูด่วน: [ภาพ, ป้ายไทย, ป้ายอังกฤษ, route]
    $tiles = array_filter([
        ['images/nova/deco/garland.webp', 'ผังสายงาน', 'Genealogy', 'user.mlm.genealogy'],
        ['images/nova/mascot/welcome.webp', 'ทีมของฉัน', 'My team', 'user.mlm.team'],
        ['images/nova/deco/coins.webp', 'คอมมิชชั่น', 'Commissions', 'user.mlm.commissions'],
        ['images/nova/deco/gift.webp', 'ลิงก์แนะนำ', 'Referral', 'user.mlm.referral'],
        ['images/nova/wallet/scale.webp', 'ตำแหน่ง Binary', 'Binary', $showBinary ? 'user.mlm.binary-position' : ''],
        ['images/nova/deco/crystal.webp', 'จำลองรายได้', 'Simulator', 'user.mlm.income-simulator'],
    ], fn ($t) => $t[3] !== '' && $nvR($t[3]));

    $kpis = [
        ['PV สะสม', 'Total PV', number_format((float) $member->total_pv), 'images/nova/deco/crystal.webp', 'PV ทีม '.number_format((float) $member->total_team_pv)],
        ['แนะนำตรง', 'Direct', number_format((int) $member->total_direct_referrals).' คน', 'images/nova/mascot/welcome.webp', 'ทีมทั้งหมด '.number_format((int) $member->total_team_members).' คน'],
        ['รายได้สะสม', 'Earnings', $baht($member->total_earnings), 'images/nova/deco/coins.webp', 'รายได้ MLM ทั้งหมด'],
        ['ยศปัจจุบัน', 'Rank', $rankName($rankInfo['current']) ?? 'ยังไม่มียศ', 'images/nova/deco/lotus.webp', number_format($rankInfo['points']).' คะแนน'],
    ];
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page">

<section class="nvm-ph" aria-labelledby="nvm-ml-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">MLM dashboard</div>
            <h1 id="nvm-ml-h">สวัสดี {{ auth()->user()->name }}</h1>
            <p class="nvm-ph__lead">ภาพรวมทีม คะแนน PV และรายได้จากแผน {{ $member->plan?->display_name ?? 'MLM' }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span class="nvm-num">{{ $member->member_code }}</span></span>
                @if($member->status === 'active')
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>สมาชิกใช้งานอยู่</span>
                @else
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>บัญชีไม่ได้ใช้งาน</span>
                @endif
                @if($rtEnabled)
                    <span class="nvm-pill nvm-pill--{{ $rtTone }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $rtIcon }}"/></svg>{{ $rtText }}</span>
                @endif
            </div>
            <div class="nvm-copy">
                <small>ลิงก์แนะนำ</small>
                <code>{{ $referralUrl }}</code>
                <button type="button" x-data @click="nvmCopy(@js($referralUrl), 'คัดลอกลิงก์แนะนำแล้ว')" aria-label="คัดลอกลิงก์แนะนำ"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg></button>
            </div>
            <div class="nvm-hero__actions">
                <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" x-data @click="nvmMlShare(@js($referralUrl))"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>แชร์ลิงก์</button>
                @if($nvR('user.mlm.referral'))
                    <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ route('user.mlm.referral') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>QR Code</a>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/earn/earn.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(count($tiles))
    <nav class="nvm-tiles" aria-label="เมนู MLM">
        @foreach($tiles as [$img, $label, $en, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
                <small class="nvm-latin">{{ $en }}</small>
            </a>
        @endforeach
    </nav>
@endif

<section class="nvm-kpis" aria-label="สรุป MLM">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi nvm-rv">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        @if($showBinary)
            <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-ml-bin-h">
                <div class="nvm-sec-h">
                    <div><small class="nvm-latin">Binary legs</small><h2 id="nvm-ml-bin-h">ขาซ้าย · ขาขวา</h2></div>
                    @if($nvR('user.mlm.binary-position'))
                        <a href="{{ route('user.mlm.binary-position') }}">ดูรายละเอียด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                    @endif
                </div>
                <div class="nvm-ml-legs">
                    @foreach(['left' => 'ขาซ้าย', 'right' => 'ขาขวา'] as $side => $label)
                        <div class="nvm-ml-leg nvm-ml-leg--{{ $side }}">
                            <b class="nvm-ml-leg__t">{{ $label }}</b>
                            <dl class="nvm-kv">
                                <dt>PV คงเหลือ</dt><dd class="nvm-num">{{ number_format((float) $member->{$side.'_leg_pv'}) }}</dd>
                                <dt>ยอดขาย</dt><dd class="nvm-num">{{ $baht($member->{$side.'_leg_sales'}) }}</dd>
                                <dt>สมาชิก</dt><dd class="nvm-num">{{ number_format((int) $member->{$side.'_leg_members'}) }} คน</dd>
                            </dl>
                        </div>
                    @endforeach
                </div>
                <div class="nvm-minis nvm-ml-minis3 nvm-gap-t">
                    <div class="nvm-mini"><small>PV ขาอ่อน</small><b class="nvm-num">{{ number_format($weakLegPv) }}</b></div>
                    <div class="nvm-mini"><small>คู่ที่จับได้ (ประมาณ)</small><b class="nvm-num">{{ number_format($estPairs) }}</b></div>
                    <div class="nvm-mini"><small>คอมมิชชั่น (ประมาณ)</small><b class="nvm-num is-in">{{ $baht($estPairs * $pairComm) }}</b></div>
                </div>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ประมาณการจาก PV คงเหลือรวม PV ยกยอดของทั้งสองขา · {{ $baht($pairComm) }} ต่อคู่ (แบบ {{ $pairLabel }}) ยังไม่หักเพดานรายวัน ยอดจริงคำนวณตอนจับคู่</p>
            </section>
        @endif

        <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-ml-com-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Recent commissions</small><h2 id="nvm-ml-com-h">คอมมิชชั่นล่าสุด</h2></div>
                @if($nvR('user.mlm.commissions'))
                    <a href="{{ route('user.mlm.commissions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                @endif
            </div>
            @if($recentCommissions->isNotEmpty())
                <ul class="nvm-txs">
                    @foreach($recentCommissions as $commission)
                        @php $void = in_array($commission->status, ['rejected', 'cancelled', 'clawback'], true); @endphp
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic {{ $void ? '' : 'is-in' }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg></span>
                            <div class="nvm-tx__t">
                                <b>{{ $commissionTypes[$commission->type] ?? 'คอมมิชชั่น' }}</b>
                                <small>{{ $commission->level ? 'ชั้น '.$commission->level.' · ' : '' }}{{ $commission->created_at?->locale('th')->diffForHumans() }}</small>
                            </div>
                            <div class="nvm-tx__r">
                                <span class="nvm-tx__amt nvm-num {{ $void ? 'is-void' : 'is-in' }}">+{{ $baht($commission->commission_amount) }}</span>
                                <span class="nvm-chip nvm-chip--{{ $statusTone[$commission->status] ?? 'warn' }}">{{ $commissionStatuses[$commission->status] ?? $commission->status }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มีคอมมิชชั่น</b>
                    เมื่อทีมของคุณมียอดซื้อ คอมมิชชั่นจะแสดงที่นี่
                </div>
            @endif
        </section>
    </div>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-ml-rt-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Volume retention</small><h2 id="nvm-ml-rt-h">สถานะรักษายอด</h2></div></div>
            @if(! $rtEnabled)
                <div class="nvm-status">
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                    <span>ระบบรักษายอดปิดอยู่<small>สมาชิกทุกคนได้รับคอมมิชชั่นตามปกติ</small></span>
                </div>
            @else
                <div class="nvm-ml-ring nvm-ml-ring--{{ $rtTone }}" role="img" aria-label="PV เดือนนี้ {{ round($pvPct) }} เปอร์เซ็นต์ของเกณฑ์">
                    <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="trk" cx="60" cy="60" r="52"/><circle class="val" cx="60" cy="60" r="52" style="--off: {{ $ringOff }}"/></svg>
                    <div class="nvm-ml-ring__txt"><b class="nvm-num">{{ round($pvPct) }}%</b><small>PV เดือนนี้</small></div>
                </div>
                <dl class="nvm-kv">
                    <dt>PV เดือนนี้</dt><dd class="nvm-num">{{ number_format((float) ($retentionStatus['monthly_pv'] ?? 0)) }} / {{ number_format((float) ($retentionStatus['required_pv'] ?? 0)) }}</dd>
                    @if(($retentionStatus['remaining_pv'] ?? 0) > 0)
                        <dt>ต้องการเพิ่ม</dt><dd class="nvm-num">{{ number_format((float) $retentionStatus['remaining_pv']) }} PV</dd>
                    @endif
                    @if(($retentionStatus['days_since_last_purchase'] ?? null) !== null)
                        <dt>ซื้อล่าสุด</dt><dd class="nvm-num">{{ $retentionStatus['days_since_last_purchase'] }} วันที่แล้ว</dd>
                    @endif
                </dl>
                @if($retentionStatus['in_grace_period'] ?? false)
                    <div class="nvm-status is-warn nvm-gap-t">
                        <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                        <span>อยู่ในช่วงผ่อนผัน<small>เหลืออีก {{ max(0, (int) ($retentionStatus['grace_days'] ?? 7) - (int) ($retentionStatus['days_since_last_purchase'] ?? 0)) }} วัน ซื้อสินค้าเพื่อรักษาสิทธิ์</small></span>
                    </div>
                @elseif($rtStatus === 'inactive')
                    <div class="nvm-status is-bad nvm-gap-t">
                        <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                        <span>PV ไม่ถึงเกณฑ์<small>คอมมิชชั่นจะถูกระงับจนกว่าจะซื้อสินค้าเพิ่ม</small></span>
                    </div>
                @endif
            @endif
        </section>

        <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-ml-rk-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Rank progress</small><h2 id="nvm-ml-rk-h">ความคืบหน้ายศ</h2></div>
                @if($nvR('user.ranks.dashboard'))
                    <a href="{{ route('user.ranks.dashboard') }}">ดูยศ<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                @endif
            </div>
            <dl class="nvm-kv">
                <dt>ยศปัจจุบัน</dt><dd>{{ $rankName($rankInfo['current']) ?? 'ยังไม่มียศ' }}</dd>
                <dt>คะแนนสะสม</dt><dd class="nvm-num">{{ number_format($rankInfo['points']) }}</dd>
            </dl>
            @if($rankInfo['next'])
                <div class="nvm-meter">
                    <div class="nvm-meter__row"><span>สู่ยศ{{ $rankName($rankInfo['next']) }}</span><b class="nvm-num">{{ number_format($rankInfo['percent'], 1) }}%</b></div>
                    <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($rankInfo['percent']) }}" aria-label="ความคืบหน้าสู่ยศถัดไป"><i style="--w: {{ $rankInfo['percent'] }}%"></i></div>
                </div>
                <p class="nvm-note">
                    <svg class="nvm-ico"><use href="#nvm-i-award"/></svg>
                    @if($rankInfo['needed'] > 0)
                        อีก {{ number_format($rankInfo['needed']) }} คะแนนจะถึงเกณฑ์ยศ{{ $rankName($rankInfo['next']) }}
                    @else
                        คะแนนถึงเกณฑ์แล้ว ทำเงื่อนไขอื่นให้ครบเพื่อเลื่อนยศ
                    @endif
                </p>
            @elseif($rankInfo['current'])
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>คุณอยู่ยศสูงสุดแล้ว ยินดีด้วย!</p>
            @else
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ระบบยศยังไม่เปิดใช้งาน</p>
            @endif
        </section>

        <section class="nvm-card nvm-box nvm-rv" aria-labelledby="nvm-ml-tip-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-ml-tip-h">เคล็ดลับเพิ่มรายได้</h2></div></div>
            <ol class="nvm-steps">
                @if($showBinary)<li>สร้างสมดุลระหว่างขาซ้ายและขวา เพื่อจับคู่ Binary ได้มากขึ้น</li>@endif
                <li>ซื้อสินค้าให้ถึงเกณฑ์ PV ทุกเดือน เพื่อไม่ให้คอมมิชชั่นถูกระงับ</li>
                <li>แนะนำทีมให้ซื้อสินค้าต่อเนื่องเพื่อสะสม PV</li>
                <li>ติดตามผังสายงานเพื่อดูการเติบโตของทีม</li>
            </ol>
        </section>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
/**
 * แชร์ลิงก์แนะนำ — ใช้ Web Share ของมือถือถ้ามี ไม่มีก็คัดลอกให้แทน
 */
window.nvmMlShare = function (url) {
    if (navigator.share) {
        navigator.share({ title: 'มาร่วมทีมกับฉัน', text: 'สมัครสมาชิกผ่านลิงก์แนะนำของฉัน', url: url }).catch(function () {});
        return;
    }
    window.nvmCopy(url, 'คัดลอกลิงก์แนะนำแล้ว');
};
</script>
@endpush

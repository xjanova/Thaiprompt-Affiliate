{{--
    รายละเอียดพูล Stake (ธีมโนวาเต็ม)
    - $pool = พูลที่ไม่ใช่ฉบับร่าง (controller กรองแล้ว) · $myStake = Stake active ของสมาชิกในพูลนี้ (อาจ null)
    - $recentStakes = ความเคลื่อนไหวล่าสุดในพูล — มีแค่จำนวน/สถานะ/เวลา ไม่มีตัวตนของสมาชิกคนอื่น
      (เดิมดึงจาก API ที่ส่งชื่อผู้ใช้และ user id ของคนอื่นมาแสดง)
    - รับรางวัล / ถอน Stake ส่งผ่าน route เว็บ user.staking.claim / user.staking.unstake (session + CSRF)
    - พูลไม่มีค่าปรับถอนก่อนกำหนด → ถอน Stake ได้หลังวันปลดล็อกเท่านั้น (เซิร์ฟเวอร์ตรวจซ้ำ) · รับรางวัลได้ตลอด
--}}
@extends('layouts.user-nova')

@section('title', 'พูล '.($pool->name ?: $pool->token->symbol).' - TPIX Staking')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $symbol = $pool->token->symbol;
    $poolName = $pool->name ?: $symbol;
    $statusPill = [
        'active' => ['ok', 'check', 'เปิดรับ Stake'],
        'paused' => ['warn', 'clock', 'หยุดรับชั่วคราว'],
        'ended' => ['bad', 'x', 'ปิดพูลแล้ว'],
    ][$pool->status] ?? ['warn', 'info', $pool->status];
    $cap = (float) ($pool->pool_cap ?? 0);
    $fill = $cap > 0 ? min(100, (float) $pool->total_staked / $cap * 100) : null;
    $locked = $myStake && $myStake->unlock_at && $myStake->unlock_at->isFuture();
    $stakeChip = ['active' => ['ok', 'Stake'], 'unstaked' => ['', 'ถอนแล้ว'], 'slashed' => ['bad', 'ถูกตัด']];
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmDxStakeActions({{ (int) $pool->id }})">

<section class="nvm-ph" aria-labelledby="nvm-dx-sp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.staking.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>พูล Stake ทั้งหมด</a>
            <div class="nvm-eyebrow nvm-latin">Staking pool · {{ $symbol }}</div>
            <h1 id="nvm-dx-sp-h">{{ $poolName }}</h1>
            <p class="nvm-ph__lead">{{ $pool->description ?: 'Stake '.$symbol.' เพื่อรับรางวัลตามอัตรา APY ของพูล' }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $statusPill[0] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $statusPill[1] }}"/></svg>{{ $statusPill[2] }}</span>
                <span class="nvm-pill nvm-pill--gold">APY <span class="nvm-num">{{ $num($pool->apy, 2) }}%</span></span>
            </div>
            @if($pool->status === 'active')
                <div class="nvm-hero__actions">
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.staking.stake-form', $pool->id) }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>{{ $myStake ? 'Stake เพิ่ม' : 'Stake เลย' }}</a>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-mine-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">My stake</small><h2 id="nvm-dx-mine-h">Stake ของฉันในพูลนี้</h2></div></div>
            @if(! $myStake)
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>คุณยังไม่ได้ Stake ในพูลนี้</b>
                    @if($pool->status === 'active')
                        <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.staking.stake-form', $pool->id) }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เริ่ม Stake</a></div>
                    @endif
                </div>
            @else
                <div class="nvm-status {{ $locked ? 'is-warn' : '' }}">
                    <svg class="nvm-ico"><use href="#nvm-i-{{ $locked ? 'lock' : 'check' }}"/></svg>
                    <span>{{ $locked ? 'อยู่ในช่วงล็อก' : 'ปลดล็อกแล้ว ถอนได้' }}<small>{{ $locked ? 'ปลดล็อก '.$thDate($myStake->unlock_at).' · ถอน Stake ได้หลังวันปลดล็อก รับรางวัลได้ตลอด' : 'ถอนพร้อมรับรางวัลสะสมได้ทันที' }}</small></span>
                </div>
                <div class="nvm-minis nvm-gap-t">
                    <div class="nvm-mini"><small>จำนวนที่ Stake</small><b class="nvm-num">{{ $num($myStake->amount) }} {{ $symbol }}</b></div>
                    <div class="nvm-mini"><small>รางวัลสะสม</small><b class="nvm-num is-in">{{ $num($myStake->rewards_earned) }}</b></div>
                    <div class="nvm-mini"><small>รับรางวัลไปแล้ว</small><b class="nvm-num">{{ $num($myStake->rewards_claimed) }}</b></div>
                    <div class="nvm-mini"><small>เริ่ม Stake</small><b class="nvm-num">{{ $thDate($myStake->staked_at ?? $myStake->created_at) }}</b></div>
                </div>
                <div class="nvm-filter__btns nvm-gap-t">
                    <button type="button" class="nvm-btn nvm-btn--line" @click="ask('claim')" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>รับรางวัล</button>
                    <button type="button" class="nvm-btn nvm-btn--bad" @click="ask('unstake')" :disabled="busy || {{ $locked ? 'true' : 'false' }}" @if($locked) title="ถอนได้หลัง {{ $thDate($myStake->unlock_at) }}" @endif><svg class="nvm-ico"><use href="#nvm-i-out"/></svg>ถอน Stake</button>
                </div>
            @endif
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-rc-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Activity</small><h2 id="nvm-dx-rc-h">ความเคลื่อนไหวล่าสุดในพูล</h2></div></div>
            @if($recentStakes->isEmpty())
                <p class="nvm-help">ยังไม่มีรายการในพูลนี้</p>
            @else
                <ul class="nvm-txs">
                    @foreach($recentStakes as $item)
                        @php [$tone, $label] = $stakeChip[$item->status] ?? ['', $item->status]; @endphp
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic {{ $item->status === 'active' ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $item->status === 'active' ? 'down' : 'up' }}"/></svg></span>
                            <div class="nvm-tx__t"><b>สมาชิก</b><small>{{ $thDate($item->created_at) }}</small></div>
                            <div class="nvm-tx__r">
                                <span class="nvm-tx__amt nvm-num">{{ $num($item->amount) }} {{ $symbol }}</span>
                                <span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $label }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-dx-pi-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Pool info</small><h2 id="nvm-dx-pi-h">ข้อมูลพูล</h2></div></div>
        <dl class="nvm-kv">
            <dt>เหรียญ</dt><dd>{{ $pool->token->name }} ({{ $symbol }})</dd>
            <dt>APY</dt><dd class="nvm-num">{{ $num($pool->apy, 2) }}%</dd>
            <dt>ระยะล็อก</dt><dd class="nvm-num">{{ number_format((int) $pool->lock_period_days) }} วัน</dd>
            <dt>Stake ขั้นต่ำ</dt><dd class="nvm-num">{{ $num($pool->min_stake_amount) }} {{ $symbol }}</dd>
            <dt>Stake สูงสุด</dt><dd class="nvm-num">{{ $pool->max_stake_amount ? $num($pool->max_stake_amount).' '.$symbol : 'ไม่จำกัด' }}</dd>
            <dt>Stake รวมในพูล</dt><dd class="nvm-num">{{ $num($pool->total_staked, 2) }} {{ $symbol }}</dd>
            <dt>ผู้ Stake</dt><dd class="nvm-num">{{ number_format((int) $pool->current_stakers) }}{{ $pool->max_stakers ? ' / '.number_format((int) $pool->max_stakers) : '' }}</dd>
            <dt>รางวัลที่จ่ายแล้ว</dt><dd class="nvm-num">{{ $num($pool->total_rewards_distributed, 2) }}</dd>
            @if($pool->ends_at)<dt>สิ้นสุดพูล</dt><dd class="nvm-num">{{ $thDate($pool->ends_at) }}</dd>@endif
        </dl>
        @if($fill !== null)
            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>เต็มพูลแล้ว</span><b class="nvm-num">{{ $num($fill, 1) }}%</b></div>
                <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($fill) }}" aria-label="สัดส่วนที่ Stake แล้วของพูล"><i class="nvm-dx-bar" style="--w: {{ round($fill, 1) }}%"></i></div>
                <div class="nvm-meter__ticks"><span>0</span><span>เพดาน {{ $num($cap, 2) }}</span></div>
            </div>
        @endif
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>รางวัลคำนวณตามเวลาที่ Stake จริง อัตรา APY อาจเปลี่ยนได้ตามเงื่อนไขของพูล</p>
    </aside>
</div>

@if($myStake)
    <div class="nvm-modal" x-show="action" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-dx-sc-h" @click.self="action = null" @keydown.escape.window="action = null">
        <div class="nvm-card nvm-modal__box">
            <h2 id="nvm-dx-sc-h" x-text="action === 'claim' ? 'รับรางวัล Stake' : 'ถอน Stake'"></h2>
            <p x-show="action === 'claim'">รางวัลที่คำนวณได้ถึงตอนนี้จะเข้ายอด {{ $symbol }} ของคุณ</p>
            <p x-show="action === 'unstake'">ถอน <b class="nvm-num">{{ $num($myStake->amount) }} {{ $symbol }}</b> พร้อมรางวัลที่ยังไม่ได้รับ</p>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="action = null" :disabled="busy">ยกเลิก</button>
                <button type="button" class="nvm-btn" :class="action === 'unstake' ? 'nvm-btn--bad' : 'nvm-btn--gold'" @click="run()" :disabled="busy"><span x-text="busy ? 'กำลังทำรายการ...' : 'ยืนยัน'">ยืนยัน</span></button>
            </div>
        </div>
    </div>
@endif

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-js')
<script>
// รับรางวัล / ถอน Stake — ยืนยันในกล่องก่อนส่งทุกครั้ง
function nvmDxStakeActions(poolId) {
    return {
        action: null,
        busy: false,
        ask(kind) { this.action = kind; },
        async run() {
            if (this.busy || ! this.action) return;
            this.busy = true;
            const url = this.action === 'claim' ? @js(route('user.staking.claim')) : @js(route('user.staking.unstake'));
            try {
                await window.nvmDxPost(url, { pool_id: poolId });
                window.showNotification(this.action === 'claim' ? 'รับรางวัลสำเร็จ' : 'ถอน Stake สำเร็จ', 'success');
                window.location.reload();
            } catch (e) {
                window.showNotification(e.message, 'error');
                this.busy = false;
                this.action = null;
            }
        }
    };
}
</script>
@endpush

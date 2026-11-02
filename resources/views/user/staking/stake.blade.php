{{--
    ฟอร์ม Stake (ธีมโนวาเต็ม)
    - $pool = พูลที่ไม่ใช่ฉบับร่าง · $balance = แถว tpix_token_balances ของสมาชิกสำหรับเหรียญของพูล (อาจ null)
    - ยอดใช้ได้อ่านจาก available_balance (เดิมอ่าน $balance->amount ซึ่งไม่มีคอลัมน์นี้ จึงขึ้น 0.00 เสมอ)
    - ตัวเลือกระยะล็อก/ตัวคูณ APY ตรงกับที่ StakingApiController::calculateAPY ใช้ (0,7,30,90,180,365 วัน)
    - ส่งผ่าน route เว็บ user.staking.stake (session + CSRF)
--}}
@extends('layouts.user-nova')

@section('title', 'Stake '.$pool->token->symbol.' - TPIX Staking')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $symbol = $pool->token->symbol;
    $available = (string) ($balance->available_balance ?? $balance->balance ?? '0');
    $apy = (float) $pool->apy;
    $locks = [
        ['days' => 0, 'label' => 'ยืดหยุ่น', 'apy' => $apy * 0.5],
        ['days' => 7, 'label' => '7 วัน', 'apy' => $apy * 0.7],
        ['days' => 30, 'label' => '30 วัน', 'apy' => $apy * 0.9],
        ['days' => 90, 'label' => '90 วัน', 'apy' => $apy],
        ['days' => 180, 'label' => '180 วัน', 'apy' => $apy * 1.2],
        ['days' => 365, 'label' => '365 วัน', 'apy' => $apy * 1.5],
    ];
    $config = [
        'poolId' => $pool->id,
        'balance' => $available,
        'min' => (string) $pool->min_stake_amount,
        'max' => $pool->max_stake_amount ? (string) $pool->max_stake_amount : null,
        'symbol' => $symbol,
        'locks' => $locks,
        'active' => $pool->status === 'active',
    ];
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmDxStake(@js($config))">

<section class="nvm-ph" aria-labelledby="nvm-dx-sf-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.staking.show', $pool->id) }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>{{ $pool->name ?: $symbol }}</a>
            <div class="nvm-eyebrow nvm-latin">Stake {{ $symbol }}</div>
            <h1 id="nvm-dx-sf-h">Stake {{ $pool->token->name }}</h1>
            <p class="nvm-ph__lead">เลือกจำนวนและระยะล็อก ยิ่งล็อกนานยิ่งได้ APY สูง</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass">คงเหลือ <span class="nvm-num">{{ $num($available) }} {{ $symbol }}</span></span>
                <span class="nvm-pill nvm-pill--gold">APY สูงสุด <span class="nvm-num">{{ $num($apy * 1.5, 2) }}%</span></span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($pool->status !== 'active')
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>พูลนี้ไม่ได้เปิดรับ Stake ในตอนนี้</b><p>กลับไปเลือกพูลอื่นที่เปิดอยู่</p></div>
        <div class="nvm-alert__side"><a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.staking.index') }}">ดูพูลทั้งหมด</a></div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-sff-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Stake</small><h2 id="nvm-dx-sff-h">รายละเอียดการ Stake</h2></div></div>

        <form class="nvm-form" @submit.prevent="submit()" novalidate>
            @csrf
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-dx-amt">จำนวนที่ต้องการ Stake <em>*</em></label>
                <div class="nvm-dx-leg">
                    <div class="nvm-dx-leg__row">
                        <input id="nvm-dx-amt" name="amount" type="number" inputmode="decimal" min="0" step="0.00000001" placeholder="0.0" class="nvm-dx-leg__amt nvm-num" x-model="amount" aria-describedby="nvm-dx-amt-help">
                        <span class="nvm-chip nvm-chip--gold">{{ $symbol }}</span>
                    </div>
                    <button type="button" class="nvm-dx-max" @click="useMax()">ใช้ทั้งหมด</button>
                </div>
                <span id="nvm-dx-amt-help" class="nvm-help">ขั้นต่ำ <span class="nvm-num">{{ $num($pool->min_stake_amount) }}</span>@if($pool->max_stake_amount) · สูงสุด <span class="nvm-num">{{ $num($pool->max_stake_amount) }}</span>@endif {{ $symbol }} · คงเหลือ <span class="nvm-num">{{ $num($available) }}</span></span>
                <span class="nvm-err" x-show="error()" x-text="error()" x-cloak></span>
            </div>

            <fieldset class="nvm-field nvm-dx-fs">
                <legend class="nvm-label">ระยะล็อก</legend>
                <div class="nvm-opts nvm-dx-locks" role="radiogroup">
                    @foreach($locks as $lock)
                        <label class="nvm-opt">
                            <input type="radio" name="lock_period_days" value="{{ $lock['days'] }}" x-model.number="lockDays" @checked($lock['days'] === 30)>
                            <b>{{ $lock['label'] }}</b>
                            <small class="nvm-num">APY {{ $num($lock['apy'], 2) }}%</small>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <dl class="nvm-kv">
                <dt>จำนวน</dt><dd class="nvm-num" x-text="fmt(amount || 0) + ' ' + cfg.symbol"></dd>
                <dt>ระยะล็อก</dt><dd x-text="lock().label"></dd>
                <dt>APY</dt><dd class="nvm-num" x-text="fmt(lock().apy, 2) + '%'"></dd>
                <dt>วันปลดล็อก</dt><dd class="nvm-num" x-text="unlockText()"></dd>
                <dt class="nvm-kv__total">รางวัลโดยประมาณ</dt><dd class="nvm-kv__total nvm-num" x-text="fmt(estimate()) + ' ' + cfg.symbol"></dd>
            </dl>
            <p class="nvm-help" x-show="lockDays === 0">แบบยืดหยุ่นคิดรางวัลประมาณการ 30 วัน · แบบล็อกคิดตลอดระยะล็อก</p>

            <label class="nvm-check"><input type="checkbox" x-model="agreed">ฉันเข้าใจว่าการถอนก่อนครบระยะล็อกอาจมีค่าปรับ และมูลค่าเหรียญขึ้นลงได้</label>

            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="! canSubmit() || busy || ! cfg.active">
                <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg><span x-text="busy ? 'กำลัง Stake...' : 'ยืนยัน Stake'">ยืนยัน Stake</span>
            </button>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-dx-sfi-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Pool</small><h2 id="nvm-dx-sfi-h">ข้อมูลพูล</h2></div></div>
        <dl class="nvm-kv">
            <dt>APY พื้นฐาน</dt><dd class="nvm-num">{{ $num($apy, 2) }}%</dd>
            <dt>Stake รวมในพูล</dt><dd class="nvm-num">{{ $num($pool->total_staked, 2) }} {{ $symbol }}</dd>
            <dt>ผู้ Stake</dt><dd class="nvm-num">{{ number_format((int) $pool->current_stakers) }}</dd>
        </dl>
        <ol class="nvm-steps nvm-gap-t">
            <li>เลือกจำนวนและระยะล็อกที่ต้องการ</li>
            <li>รางวัลเริ่มนับทันทีหลัง Stake สำเร็จ</li>
            <li>รับรางวัลหรือถอน Stake ได้จากหน้ารายละเอียดพูล</li>
        </ol>
        <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ถอนก่อนครบระยะล็อกอาจถูกหักค่าปรับตามเงื่อนไขของพูล</p>
    </aside>
</div>

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-js')
<script>
// ฟอร์ม Stake: ตรวจขั้นต่ำ/สูงสุด/ยอดคงเหลือก่อนส่ง (เซิร์ฟเวอร์ตรวจซ้ำอีกชั้น)
function nvmDxStake(cfg) {
    return {
        cfg: cfg,
        amount: '',
        lockDays: 30,
        agreed: false,
        busy: false,
        fmt(n, d) { return window.nvmDxFmt(n, d); },
        lock() { return this.cfg.locks.find(l => l.days === Number(this.lockDays)) || this.cfg.locks[0]; },
        useMax() {
            let v = parseFloat(this.cfg.balance) || 0;
            if (this.cfg.max) v = Math.min(v, parseFloat(this.cfg.max));
            this.amount = String(v);
        },
        error() {
            const a = parseFloat(this.amount);
            if (this.amount === '' || isNaN(a)) return '';
            if (a <= 0) return 'จำนวนต้องมากกว่า 0';
            if (a < parseFloat(this.cfg.min)) return 'ต่ำกว่าขั้นต่ำ ' + this.fmt(this.cfg.min) + ' ' + this.cfg.symbol;
            if (this.cfg.max && a > parseFloat(this.cfg.max)) return 'เกินสูงสุด ' + this.fmt(this.cfg.max) + ' ' + this.cfg.symbol;
            if (a > parseFloat(this.cfg.balance)) return 'ยอดคงเหลือไม่พอ';
            return '';
        },
        estimate() {
            const a = parseFloat(this.amount) || 0;
            const days = Number(this.lockDays) || 30;
            return a * (this.lock().apy / 100) * days / 365;
        },
        unlockText() {
            if (! Number(this.lockDays)) return 'ถอนได้ทุกเมื่อ';
            const d = new Date(Date.now() + Number(this.lockDays) * 86400000);
            return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        canSubmit() { return parseFloat(this.amount) > 0 && ! this.error() && this.agreed; },
        async submit() {
            if (! this.canSubmit() || this.busy) return;
            this.busy = true;
            try {
                await window.nvmDxPost(@js(route('user.staking.stake')), { pool_id: this.cfg.poolId, amount: this.amount, lock_period_days: Number(this.lockDays) });
                window.showNotification('Stake สำเร็จ', 'success');
                window.location.href = @js(route('user.staking.show', $pool->id));
            } catch (e) {
                window.showNotification(e.message, 'error');
                this.busy = false;
            }
        }
    };
}
</script>
@endpush

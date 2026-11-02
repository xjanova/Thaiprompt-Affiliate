{{--
    ถอนสภาพคล่อง — TPIX DEX (ธีมโนวาเต็ม)
    - $position = ตำแหน่งของสมาชิกเอง (controller ค้นผ่าน auth()->user()->liquidityPositions() → ของคนอื่น 404)
    - เลือกเปอร์เซ็นต์ → แปลงเป็น lp_tokens แล้วส่งไป user.dex.liquidity.remove (session + CSRF)
--}}
@extends('layouts.user-nova')

@section('title', 'ถอนสภาพคล่อง - TPIX DEX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 6) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $abbr = fn (?string $symbol) => mb_strtoupper(mb_substr((string) $symbol, 0, 3));
    $pool = $position->pool;
    $symA = $pool->tokenA->symbol ?? '?';
    $symB = $pool->tokenB->symbol ?? '?';

    // คาดการณ์ที่ได้คืนจากสัดส่วน LP ต่อสภาพคล่องรวมของพูล (สูตรเดียวกับ DEXService::removeLiquidity)
    // $totalLiquidity มาจาก DEXService::effectiveTotalLiquidity (พูลเก่าที่ total_liquidity ค้าง 0 ใช้ผลรวม LP แทน)
    $totalLiquidity = (float) $totalLiquidity;
    $shareFraction = $totalLiquidity > 0 ? (float) $position->lp_tokens / $totalLiquidity : 0;
    $preview = [
        'lp' => (string) $position->lp_tokens,
        'a' => (float) $pool->reserve_a * $shareFraction,
        'b' => (float) $pool->reserve_b * $shareFraction,
    ];
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmDxRemove(@js($preview))">

<section class="nvm-ph" aria-labelledby="nvm-dx-rm-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dex.my-positions') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตำแหน่งของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Remove liquidity</div>
            <h1 id="nvm-dx-rm-h">ถอนสภาพคล่อง {{ $symA }} / {{ $symB }}</h1>
            <p class="nvm-ph__lead">เลือกสัดส่วนที่ต้องการถอน ระบบจะคืนเหรียญทั้งสองฝั่งตามส่วนแบ่งของคุณในพูล</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass">LP <span class="nvm-num">{{ $num($position->lp_tokens) }}</span></span>
                <span class="nvm-pill nvm-pill--glass">ส่วนแบ่ง <span class="nvm-num">{{ $num($position->share_percentage, 4) }}%</span></span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/withdraw.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-rmf-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Amount</small><h2 id="nvm-dx-rmf-h">จำนวนที่ถอน</h2></div></div>

        <form class="nvm-form" @submit.prevent="confirming = true" novalidate>
            @csrf
            <input type="hidden" name="position_id" value="{{ $position->id }}">
            <div class="nvm-dx-pct nvm-num" aria-live="polite"><span x-text="pct">50</span>%</div>
            <input type="range" class="nvm-dx-range" min="1" max="100" step="1" x-model.number="pct" aria-label="เปอร์เซ็นต์ที่ถอน">
            <div class="nvm-dx-quick" role="group" aria-label="เลือกเปอร์เซ็นต์ด่วน">
                <template x-for="v in [25, 50, 75, 100]" :key="v">
                    <button type="button" :class="{ 'is-on': pct === v }" @click="pct = v" x-text="v === 100 ? 'ทั้งหมด' : v + '%'"></button>
                </template>
            </div>

            <dl class="nvm-kv">
                <dt>LP Token ที่ถอน</dt><dd class="nvm-num" x-text="fmt(lpOut())"></dd>
                <dt>ได้รับ {{ $symA }} (ประมาณ)</dt><dd class="nvm-num" x-text="fmt(preview.a * pct / 100)"></dd>
                <dt>ได้รับ {{ $symB }} (ประมาณ)</dt><dd class="nvm-num" x-text="fmt(preview.b * pct / 100)"></dd>
            </dl>

            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="busy">
                <svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ถอนสภาพคล่อง
            </button>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-dx-pos-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Position</small><h2 id="nvm-dx-pos-h">ตำแหน่งปัจจุบัน</h2></div></div>
        <div class="nvm-dx-pool__head">
            <span class="nvm-dx-pair" aria-hidden="true"><span class="nvm-dx-coin">{{ $abbr($symA) }}</span><span class="nvm-dx-coin nvm-dx-coin--b">{{ $abbr($symB) }}</span></span>
            <div><b>{{ $symA }} / {{ $symB }}</b><small>เพิ่มเมื่อ {{ $position->created_at?->copy()->locale('th')->translatedFormat('j M Y') ?? '-' }}</small></div>
        </div>
        <dl class="nvm-kv nvm-gap-t">
            <dt>{{ $symA }} ที่ฝาก</dt><dd class="nvm-num">{{ $num($position->token_a_amount) }}</dd>
            <dt>{{ $symB }} ที่ฝาก</dt><dd class="nvm-num">{{ $num($position->token_b_amount) }}</dd>
            <dt>LP Token</dt><dd class="nvm-num">{{ $num($position->lp_tokens) }}</dd>
            <dt>มูลค่าปัจจุบัน</dt><dd class="nvm-num">{{ $num($position->getCurrentValueTPIX(), 4) }} TPIX</dd>
            <dt>ค่าธรรมเนียมที่ได้</dt><dd class="nvm-num">{{ $num($position->fees_earned) }}</dd>
        </dl>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>จำนวนที่ได้รับจริงคำนวณจากสำรองของพูล ณ ตอนที่ทำรายการ</p>
    </aside>
</div>

<div class="nvm-modal" x-show="confirming" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-dx-cf-h" @click.self="confirming = false" @keydown.escape.window="confirming = false">
    <div class="nvm-card nvm-modal__box">
        <h2 id="nvm-dx-cf-h">ยืนยันถอนสภาพคล่อง</h2>
        <p>ถอน <b class="nvm-num" x-text="pct + '%'"></b> ของตำแหน่ง {{ $symA }} / {{ $symB }}</p>
        <dl class="nvm-kv">
            <dt>{{ $symA }}</dt><dd class="nvm-num" x-text="fmt(preview.a * pct / 100)"></dd>
            <dt>{{ $symB }}</dt><dd class="nvm-num" x-text="fmt(preview.b * pct / 100)"></dd>
        </dl>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" @click="confirming = false" :disabled="busy">ยกเลิก</button>
            <button type="button" class="nvm-btn nvm-btn--gold" @click="submit()" :disabled="busy"><span x-text="busy ? 'กำลังถอน...' : 'ยืนยัน'">ยืนยัน</span></button>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-js')
<script>
// ถอนสภาพคล่องตามเปอร์เซ็นต์ — 100% ส่งจำนวน LP เต็มตามฐานข้อมูล (กันเศษทศนิยม)
function nvmDxRemove(preview) {
    return {
        preview: preview,
        pct: 50,
        busy: false,
        confirming: false,
        fmt(n, d) { return window.nvmDxFmt(n, d); },
        lpOut() {
            if (this.pct >= 100) return this.preview.lp;
            return (Math.floor(parseFloat(this.preview.lp) * this.pct / 100 * 1e8) / 1e8).toFixed(8);
        },
        async submit() {
            if (this.busy) return;
            this.busy = true;
            try {
                await window.nvmDxPost(@js(route('user.dex.liquidity.remove')), { position_id: {{ (int) $position->id }}, lp_tokens: this.lpOut() });
                window.showNotification('ถอนสภาพคล่องสำเร็จ', 'success');
                window.location.href = @js(route('user.dex.my-positions'));
            } catch (e) {
                window.showNotification(e.message, 'error');
                this.busy = false;
                this.confirming = false;
            }
        }
    };
}
</script>
@endpush

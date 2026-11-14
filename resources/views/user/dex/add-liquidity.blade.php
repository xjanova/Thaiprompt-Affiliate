{{--
    เพิ่มสภาพคล่อง — TPIX DEX (ธีมโนวาเต็ม)
    - $pool = พูลที่เลือกมาจากลิงก์ (?pool= หรือ ?pool_id=) อาจเป็น null · $pairs = พูลที่เปิดอยู่
    - $tokens = token ที่เลือกได้ + ยอดใช้ได้ของสมาชิก
    - ถ้าคู่ที่เลือกมีพูลและมีสำรองแล้ว จำนวนฝั่ง B คำนวณตามสัดส่วนพูลให้อัตโนมัติ
    - ส่งผ่าน route เว็บ user.dex.liquidity.add (session + CSRF)
--}}
@extends('layouts.user-nova')

@section('title', 'เพิ่มสภาพคล่อง - TPIX DEX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $abbr = fn (?string $symbol) => mb_strtoupper(mb_substr((string) $symbol, 0, 3));
    $topPools = collect($pairs)->take(5);
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmDxAdd(@js($tokens), @js($pairs), @js($pool?->token_a_id), @js($pool?->token_b_id))">

<section class="nvm-ph" aria-labelledby="nvm-dx-add-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dex.pools') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>พูลสภาพคล่อง</a>
            <div class="nvm-eyebrow nvm-latin">Add liquidity</div>
            <h1 id="nvm-dx-add-h">เพิ่มสภาพคล่อง</h1>
            <p class="nvm-ph__lead">ฝากเหรียญสองฝั่งเข้าพูลในสัดส่วนเดียวกับพูล แล้วรับ LP Token เป็นหลักฐานส่วนแบ่ง</p>
            @if($pool)
                <div class="nvm-pills"><span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>{{ $pool->tokenA->symbol }} / {{ $pool->tokenB->symbol }}</span></div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-addf-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Token pair</small><h2 id="nvm-dx-addf-h">เลือกคู่เหรียญและจำนวน</h2></div></div>

        @if(count($tokens) < 2)
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังมีเหรียญไม่พอสำหรับเปิดคู่</b>
                ต้องมีเหรียญอย่างน้อย 2 ชนิด (จากพูลที่เปิดอยู่หรือเหรียญที่คุณถือ)
                <div><a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.dex.pools') }}">กลับไปดูพูล</a></div>
            </div>
        @else
            <form class="nvm-form" @submit.prevent="submit()" novalidate>
                @csrf
                <div class="nvm-dx-leg">
                    <div class="nvm-dx-leg__top">
                        <label for="nvm-dx-a">เหรียญ A</label>
                        <span>คงเหลือ <b class="nvm-num" x-text="fmt(balanceOf(aId))"></b></span>
                    </div>
                    <div class="nvm-dx-leg__row">
                        <input id="nvm-dx-a" name="amount_a" type="number" inputmode="decimal" min="0" step="0.00000001" placeholder="0.0" class="nvm-dx-leg__amt nvm-num" x-model="amountA" @input="syncFromA()">
                        <select name="token_a_id" class="nvm-input" x-model.number="aId" @change="syncFromA()" aria-label="เหรียญ A">
                            <option value="">เลือก</option>
                            <template x-for="t in tokens" :key="t.id"><option :value="t.id" x-text="t.symbol" :selected="t.id === aId"></option></template>
                        </select>
                    </div>
                    <button type="button" class="nvm-dx-max" x-show="aId" @click="amountA = String(balanceOf(aId)); syncFromA()">ใช้ทั้งหมด</button>
                </div>

                <div class="nvm-dx-flip" aria-hidden="true"><button type="button" tabindex="-1" @click="flip()"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg></button></div>

                <div class="nvm-dx-leg">
                    <div class="nvm-dx-leg__top">
                        <label for="nvm-dx-b">เหรียญ B</label>
                        <span>คงเหลือ <b class="nvm-num" x-text="fmt(balanceOf(bId))"></b></span>
                    </div>
                    <div class="nvm-dx-leg__row">
                        <input id="nvm-dx-b" name="amount_b" type="number" inputmode="decimal" min="0" step="0.00000001" placeholder="0.0" class="nvm-dx-leg__amt nvm-num" x-model="amountB" @input="syncFromB()">
                        <select name="token_b_id" class="nvm-input" x-model.number="bId" @change="syncFromA()" aria-label="เหรียญ B">
                            <option value="">เลือก</option>
                            <template x-for="t in tokens" :key="t.id"><option :value="t.id" x-text="t.symbol" :selected="t.id === bId" :disabled="t.id === aId"></option></template>
                        </select>
                    </div>
                    <button type="button" class="nvm-dx-max" x-show="bId" @click="amountB = String(balanceOf(bId)); syncFromB()">ใช้ทั้งหมด</button>
                </div>

                <dl class="nvm-kv" x-show="aId && bId" x-cloak>
                    <dt>สถานะพูล</dt><dd x-text="pool() ? (hasReserves() ? 'มีพูลอยู่แล้ว' : 'มีพูลแต่ยังว่าง') : 'คู่ใหม่ (คุณเป็นผู้กำหนดราคาแรก)'"></dd>
                    <dt>อัตรา</dt><dd class="nvm-num" x-text="rateText()"></dd>
                    <dt>สัดส่วนในพูลโดยประมาณ</dt><dd class="nvm-num" x-text="shareText()"></dd>
                </dl>

                <p class="nvm-note is-bad" x-show="warning()" x-cloak role="alert"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span x-text="warning()"></span></p>

                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="! canSubmit() || busy">
                    <svg class="nvm-ico"><use href="#nvm-i-plus"/></svg><span x-text="busy ? 'กำลังเพิ่มสภาพคล่อง...' : 'เพิ่มสภาพคล่อง'">เพิ่มสภาพคล่อง</span>
                </button>
            </form>
        @endif
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-top-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Pools</small><h2 id="nvm-dx-top-h">พูลที่เปิดอยู่</h2></div></div>
            @if($topPools->isEmpty())
                <p class="nvm-help">ยังไม่มีพูลที่เปิดอยู่ คุณเปิดคู่ใหม่ได้จากฟอร์มนี้</p>
            @else
                <ul class="nvm-txs">
                    @foreach($topPools as $p)
                        <li class="nvm-tx">
                            <span class="nvm-dx-pair" aria-hidden="true"><span class="nvm-dx-coin">{{ $abbr($p['symbol_a']) }}</span><span class="nvm-dx-coin nvm-dx-coin--b">{{ $abbr($p['symbol_b']) }}</span></span>
                            <div class="nvm-tx__t">
                                <b>{{ $p['symbol_a'] }} / {{ $p['symbol_b'] }}</b>
                                <small class="nvm-num">{{ $num($p['reserve_a']) }} / {{ $num($p['reserve_b']) }}</small>
                            </div>
                            <div class="nvm-tx__r">
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="pick({{ (int) $p['a'] }}, {{ (int) $p['b'] }})">เลือก</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
        <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-how-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-dx-how-h">ควรรู้ก่อนฝาก</h2></div></div>
            <ol class="nvm-steps">
                <li>ฝากสองเหรียญในมูลค่าเท่ากันตามสัดส่วนของพูล</li>
                <li>ได้รับ LP Token แทนส่วนแบ่งของคุณในพูล</li>
                <li>รับส่วนแบ่งค่าธรรมเนียมจากการแลกในพูลตามสัดส่วน</li>
                <li>ถอนคืนได้ทุกเมื่อจากหน้า "ตำแหน่งของฉัน"</li>
            </ol>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ราคาเหรียญเปลี่ยนแล้วมูลค่าที่ถอนได้อาจน้อยกว่าการถือไว้เฉยๆ (Impermanent Loss)</p>
        </section>
    </aside>
</div>

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-js')
<script>
// ฟอร์มเพิ่มสภาพคล่อง: คำนวณจำนวนอีกฝั่งตามสัดส่วนสำรองของพูล (ถ้ามี)
function nvmDxAdd(tokens, pairs, presetA, presetB) {
    return {
        tokens: tokens,
        pairs: pairs,
        aId: presetA || (tokens[0] ? tokens[0].id : ''),
        bId: presetB || (tokens[1] ? tokens[1].id : ''),
        amountA: '',
        amountB: '',
        busy: false,
        fmt(n, d) { return window.nvmDxFmt(n, d); },
        token(id) { return this.tokens.find(t => t.id === Number(id)) || null; },
        balanceOf(id) { const t = this.token(id); return t ? t.balance : 0; },
        symbolOf(id) { const t = this.token(id); return t ? t.symbol : ''; },
        pick(a, b) { this.aId = a; this.bId = b; this.syncFromA(); },
        flip() { [this.aId, this.bId] = [this.bId, this.aId]; [this.amountA, this.amountB] = [this.amountB, this.amountA]; },
        pool() {
            const a = Number(this.aId), b = Number(this.bId);
            return this.pairs.find(p => (p.a === a && p.b === b) || (p.a === b && p.b === a)) || null;
        },
        // สำรองตามทิศที่ผู้ใช้เลือก [ฝั่ง A, ฝั่ง B]
        reserves() {
            const p = this.pool();
            if (! p) return [0, 0];
            const ra = parseFloat(p.reserve_a), rb = parseFloat(p.reserve_b);
            return p.a === Number(this.aId) ? [ra, rb] : [rb, ra];
        },
        hasReserves() { const [ra, rb] = this.reserves(); return ra > 0 && rb > 0; },
        syncFromA() {
            if (! this.hasReserves()) return;
            const [ra, rb] = this.reserves();
            const a = parseFloat(this.amountA);
            this.amountB = a > 0 ? (a * rb / ra).toFixed(8).replace(/\.?0+$/, '') : '';
        },
        syncFromB() {
            if (! this.hasReserves()) return;
            const [ra, rb] = this.reserves();
            const b = parseFloat(this.amountB);
            this.amountA = b > 0 ? (b * ra / rb).toFixed(8).replace(/\.?0+$/, '') : '';
        },
        rateText() {
            const a = parseFloat(this.amountA), b = parseFloat(this.amountB);
            if (this.hasReserves()) { const [ra, rb] = this.reserves(); return '1 ' + this.symbolOf(this.aId) + ' = ' + this.fmt(rb / ra) + ' ' + this.symbolOf(this.bId); }
            if (a > 0 && b > 0) return '1 ' + this.symbolOf(this.aId) + ' = ' + this.fmt(b / a) + ' ' + this.symbolOf(this.bId);
            return '-';
        },
        shareText() {
            const a = parseFloat(this.amountA);
            if (! (a > 0)) return '-';
            const [ra] = this.reserves();
            return this.fmt(a / (ra + a) * 100, 2) + '%';
        },
        warning() {
            if (this.aId && this.aId === this.bId) return 'เลือกเหรียญสองฝั่งให้ต่างกัน';
            if (parseFloat(this.amountA) > parseFloat(this.balanceOf(this.aId))) return 'ยอด ' + this.symbolOf(this.aId) + ' ไม่พอ';
            if (parseFloat(this.amountB) > parseFloat(this.balanceOf(this.bId))) return 'ยอด ' + this.symbolOf(this.bId) + ' ไม่พอ';
            return '';
        },
        canSubmit() {
            return this.aId && this.bId && this.aId !== this.bId
                && parseFloat(this.amountA) > 0 && parseFloat(this.amountB) > 0 && ! this.warning();
        },
        async submit() {
            if (! this.canSubmit() || this.busy) return;
            this.busy = true;
            try {
                await window.nvmDxPost(@js(route('user.dex.liquidity.add')), {
                    token_a_id: this.aId, token_b_id: this.bId, amount_a: this.amountA, amount_b: this.amountB
                });
                window.showNotification('เพิ่มสภาพคล่องสำเร็จ', 'success');
                window.location.href = @js(route('user.dex.my-positions'));
            } catch (e) {
                window.showNotification(e.message, 'error');
                this.busy = false;
            }
        }
    };
}
</script>
@endpush

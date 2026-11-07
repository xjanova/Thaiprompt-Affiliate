{{--
    แลกเปลี่ยน Token — TPIX DEX (ธีมโนวาเต็ม)
    - $tokens = token ที่มีพูลเปิดอยู่ + ยอดใช้ได้ของสมาชิก · $pairs = คู่ที่มีพูลจริง
    - $recentSwaps = การแลกเปลี่ยนล่าสุดของสมาชิกเอง
    - ขอราคา/ทำรายการผ่าน route เว็บ user.dex.quote / user.dex.swap.execute (session + CSRF)
--}}
@extends('layouts.user-nova')

@section('title', 'แลกเปลี่ยน Token - TPIX DEX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 6) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $swapChip = ['completed' => ['ok', 'สำเร็จ'], 'pending' => ['warn', 'รอดำเนินการ'], 'failed' => ['bad', 'ไม่สำเร็จ']];
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmDxSwap(@js($tokens), @js($pairs))">

<section class="nvm-ph" aria-labelledby="nvm-dx-sw-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">TPIX DEX · Swap</div>
            <h1 id="nvm-dx-sw-h">แลกเปลี่ยน Token</h1>
            <p class="nvm-ph__lead">แลกเหรียญระหว่างคู่ที่มีพูลสภาพคล่อง ราคาคำนวณจากพูลแบบอัตโนมัติ (AMM)</p>
            <div class="nvm-pills">
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.dex.pools') }}"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>พูลสภาพคล่อง</a>
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.dex.swap-history') }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติการแลก</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-form-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Swap</small><h2 id="nvm-dx-form-h">แลกเหรียญ</h2></div>
            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="showSettings = ! showSettings" :aria-expanded="showSettings.toString()">
                <svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>ตั้งค่า
            </button>
        </div>

        @if(empty($tokens))
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีพูลสภาพคล่องที่เปิดให้แลก</b>
                เมื่อมีพูลเปิดใช้งาน คุณจะแลกเหรียญได้จากหน้านี้
                <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.dex.pools') }}">ดูพูลสภาพคล่อง</a></div>
            </div>
        @else
            <form class="nvm-form" @submit.prevent="submit()" novalidate>
                @csrf

                <div class="nvm-dx-slip" x-show="showSettings" x-cloak>
                    <span class="nvm-label">Slippage ที่ยอมรับได้</span>
                    <div class="nvm-seg" role="group" aria-label="Slippage">
                        <template x-for="v in [0.5, 1, 3]" :key="v">
                            <button type="button" :class="{ 'is-on': slippage == v }" @click="slippage = v; quote()" x-text="v + '%'"></button>
                        </template>
                    </div>
                    <input type="number" class="nvm-input" min="0.1" max="50" step="0.1" x-model.number="slippage" @change="quote()" aria-label="กำหนด Slippage เอง (%)">
                </div>

                {{-- ฝั่งจ่าย --}}
                <div class="nvm-dx-leg">
                    <div class="nvm-dx-leg__top">
                        <label for="nvm-dx-in">จ่าย</label>
                        <span>คงเหลือ <b class="nvm-num" x-text="fmt(balanceOf(fromId))"></b></span>
                    </div>
                    <div class="nvm-dx-leg__row">
                        <input id="nvm-dx-in" name="amount_in" type="number" inputmode="decimal" min="0" step="0.00000001" placeholder="0.0" class="nvm-dx-leg__amt nvm-num" x-model="amountIn" @input.debounce.500ms="quote()">
                        <select name="token_in_id" class="nvm-input" x-model.number="fromId" @change="onFromChange()" aria-label="เหรียญที่จ่าย">
                            <option value="">เลือก</option>
                            <template x-for="t in tokens" :key="t.id">
                                <option :value="t.id" x-text="t.symbol" :selected="t.id === fromId"></option>
                            </template>
                        </select>
                    </div>
                    <button type="button" class="nvm-dx-max" @click="useMax()" x-show="fromId">ใช้ทั้งหมด</button>
                </div>

                <div class="nvm-dx-flip">
                    <button type="button" @click="flip()" aria-label="สลับฝั่งเหรียญ"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg></button>
                </div>

                {{-- ฝั่งรับ --}}
                <div class="nvm-dx-leg">
                    <div class="nvm-dx-leg__top">
                        <label for="nvm-dx-out">รับ (ประมาณ)</label>
                        <span>คงเหลือ <b class="nvm-num" x-text="fmt(balanceOf(toId))"></b></span>
                    </div>
                    <div class="nvm-dx-leg__row">
                        <input id="nvm-dx-out" type="text" readonly placeholder="0.0" class="nvm-dx-leg__amt nvm-num" :value="q ? fmt(q.amount_out) : ''">
                        <select name="token_out_id" class="nvm-input" x-model.number="toId" @change="quote()" aria-label="เหรียญที่รับ" :disabled="! fromId">
                            <option value="">เลือก</option>
                            <template x-for="t in targets()" :key="t.id">
                                <option :value="t.id" x-text="t.symbol" :selected="t.id === toId"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <dl class="nvm-kv" x-show="q" x-cloak>
                    <dt>อัตราแลก</dt><dd class="nvm-num" x-text="q ? ('1 ' + symbolOf(fromId) + ' ≈ ' + fmt(q.price) + ' ' + symbolOf(toId)) : '-'"></dd>
                    <dt>ผลกระทบต่อราคา</dt><dd class="nvm-num" x-text="q ? fmt(Math.abs(q.price_impact), 2) + '%' : '-'"></dd>
                    <dt>ค่าธรรมเนียม</dt><dd class="nvm-num" x-text="q ? fmt(q.fee) + ' ' + symbolOf(toId) : '-'"></dd>
                    <dt>Slippage</dt><dd class="nvm-num" x-text="slippage + '%'"></dd>
                    <dt class="nvm-kv__total">ได้รับอย่างน้อย</dt><dd class="nvm-kv__total nvm-num" x-text="q ? fmt(minOut()) + ' ' + symbolOf(toId) : '-'"></dd>
                </dl>

                <p class="nvm-note is-bad" x-show="warning" x-cloak role="alert"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span x-text="warning"></span></p>

                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="! canSubmit() || busy">
                    <svg class="nvm-ico"><use href="#nvm-i-swap"/></svg><span x-text="buttonLabel()">แลกเหรียญ</span>
                </button>
            </form>
        @endif
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-dx-recent-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Recent</small><h2 id="nvm-dx-recent-h">การแลกล่าสุดของฉัน</h2></div>
            @if($recentSwaps->isNotEmpty())
                <a href="{{ route('user.dex.swap-history') }}">ทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        @if($recentSwaps->isEmpty())
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีประวัติการแลกเปลี่ยน</b>
            </div>
        @else
            <ul class="nvm-txs">
                @foreach($recentSwaps as $swap)
                    @php [$tone, $label] = $swapChip[$swap->status] ?? ['', $swap->status]; @endphp
                    <li class="nvm-tx">
                        <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg></span>
                        <div class="nvm-tx__t">
                            <b>{{ $swap->tokenIn->symbol ?? '?' }} → {{ $swap->tokenOut->symbol ?? '?' }}</b>
                            <small class="nvm-num">{{ $num($swap->amount_in) }} → {{ $num($swap->amount_out) }}</small>
                        </div>
                        <div class="nvm-tx__r">
                            <span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $label }}</span>
                            <time datetime="{{ $swap->created_at?->toIso8601String() }}">{{ $thDate($swap->created_at) }}</time>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ราคาเปลี่ยนตามสภาพคล่องในพูล ระบบจะยกเลิกรายการเองถ้าได้รับน้อยกว่าค่า "ได้รับอย่างน้อย"</p>
    </aside>
</div>

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-js')
<script>
// ฟอร์มแลกเหรียญ: เลือกได้เฉพาะคู่ที่มีพูลจริง · ขอราคาหลังพิมพ์หยุด 0.5 วินาที
function nvmDxSwap(tokens, pairs) {
    return {
        tokens: tokens,
        pairs: pairs,
        fromId: tokens.length ? tokens[0].id : '',
        toId: '',
        amountIn: '',
        slippage: 0.5,
        q: null,
        warning: '',
        busy: false,
        quoting: false,
        showSettings: false,
        seq: 0,
        init() { this.onFromChange(); },
        fmt(n, d) { return window.nvmDxFmt(n, d); },
        token(id) { return this.tokens.find(t => t.id === Number(id)) || null; },
        symbolOf(id) { const t = this.token(id); return t ? t.symbol : ''; },
        balanceOf(id) { const t = this.token(id); return t ? t.balance : 0; },
        targets() {
            const from = Number(this.fromId);
            const ids = this.pairs.filter(p => p.a === from || p.b === from).map(p => (p.a === from ? p.b : p.a));
            return this.tokens.filter(t => ids.includes(t.id));
        },
        onFromChange() {
            const list = this.targets();
            if (! list.some(t => t.id === Number(this.toId))) this.toId = list.length ? list[0].id : '';
            this.quote();
        },
        flip() {
            if (! this.toId) return;
            [this.fromId, this.toId] = [this.toId, this.fromId];
            this.quote();
        },
        useMax() { this.amountIn = String(this.balanceOf(this.fromId)); this.quote(); },
        minOut() { return this.q ? parseFloat(this.q.amount_out) * (1 - this.slippage / 100) : 0; },
        canSubmit() {
            const amt = parseFloat(this.amountIn);
            return this.fromId && this.toId && amt > 0 && this.q && parseFloat(this.q.amount_out) > 0
                && amt <= parseFloat(this.balanceOf(this.fromId)) && ! this.quoting;
        },
        buttonLabel() {
            if (this.busy) return 'กำลังทำรายการ...';
            if (! this.fromId || ! this.toId) return 'เลือกเหรียญ';
            if (! (parseFloat(this.amountIn) > 0)) return 'ใส่จำนวน';
            if (parseFloat(this.amountIn) > parseFloat(this.balanceOf(this.fromId))) return 'ยอดคงเหลือไม่พอ';
            if (this.quoting) return 'กำลังคำนวณราคา...';
            return 'แลกเหรียญ';
        },
        async quote() {
            this.q = null;
            this.warning = '';
            const amt = parseFloat(this.amountIn);
            if (! this.fromId || ! this.toId || ! (amt > 0)) return;
            const mine = ++this.seq;
            this.quoting = true;
            try {
                const res = await window.nvmDxPost(@js(route('user.dex.quote')), { token_in_id: this.fromId, token_out_id: this.toId, amount_in: this.amountIn });
                if (mine !== this.seq) return;
                this.q = res.data || null;
                if (this.q && ! (parseFloat(this.q.amount_out) > 0)) this.warning = 'พูลนี้ยังไม่มีสภาพคล่องพอสำหรับการแลก';
                else if (this.q && Math.abs(this.q.price_impact) > 5) this.warning = 'ผลกระทบต่อราคาสูงกว่า 5% ลองลดจำนวนลง';
            } catch (e) {
                if (mine === this.seq) this.warning = 'คำนวณราคาไม่ได้: ' + e.message;
            } finally {
                if (mine === this.seq) this.quoting = false;
            }
        },
        async submit() {
            if (! this.canSubmit() || this.busy) return;
            this.busy = true;
            try {
                await window.nvmDxPost(@js(route('user.dex.swap.execute')), {
                    token_in_id: this.fromId,
                    token_out_id: this.toId,
                    amount_in: this.amountIn,
                    min_amount_out: this.minOut().toFixed(8),
                    slippage_tolerance: this.slippage
                });
                window.showNotification('แลกเหรียญสำเร็จ', 'success');
                window.location.reload();
            } catch (e) {
                window.showNotification(e.message, 'error');
                this.busy = false;
            }
        }
    };
}
</script>
@endpush

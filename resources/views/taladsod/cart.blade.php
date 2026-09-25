{{--
 | ตะกร้าตลาดสด (taladsod.cart) — แยกตามร้าน ชำระทีละร้าน — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\CartController@index
 | ตัวแปร: $cart {shops[{seller{id,shop_name,shop_image,is_mobile,is_open,closed_message}, seller_id, items[], items_count, subtotal, issues_count, can_checkout}], items_count, subtotal},
 |         $settings, $paymentMethods, $riderEnabled
 | แก้จำนวน: PUT taladsod.cart.items.update/{item} {quantity} (0 = ลบ) · ลบ: DELETE taladsod.cart.items.destroy/{item}
 | ล้างร้าน: DELETE taladsod.cart.clear {seller_id} — ทุกคำสั่งตอบตะกร้าทั้งใบ (JSON) → อัปเดตหน้าโดยไม่โหลดใหม่
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ตะกร้าตลาดสด')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $shops = $cart['shops'] ?? [];
    $cartCfg = [
        'cart' => $cart,
        'updateUrl' => route('taladsod.cart.items.update', ['item' => '__ID__']),
        'destroyUrl' => route('taladsod.cart.items.destroy', ['item' => '__ID__']),
        'clearUrl' => route('taladsod.cart.clear'),
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="taladsod" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;" x-data="tsCart({{ \Illuminate\Support\Js::from($cartCfg) }})">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => 'cart'])

        <div class="ts-row" style="justify-content:space-between; margin:6px 0 16px;">
            <div>
                <h1 class="sf-h1">ตะกร้าตลาดสด</h1>
                <p class="ts-muted" style="margin:6px 0 0; font-size:13.5px;">สินค้าแยกตามร้าน — ชำระเงินทีละร้าน แต่ละร้านได้ 1 ออเดอร์</p>
            </div>
            <a href="{{ route('taladsod.home') }}" class="tp-btn"><i class="fas fa-plus" aria-hidden="true"></i> เลือกซื้อต่อ</a>
        </div>

        @if(count($shops) === 0)
            <div class="tp-card ts-empty" style="padding:48px 18px;">
                <span class="em" aria-hidden="true">🧺</span>
                <b style="font-size:17px;">ตะกร้ายังว่างอยู่</b>
                <span class="ts-muted">ไปดูร้านที่เปิดอยู่ใกล้คุณ แล้วหยิบเมนูโปรดใส่ตะกร้าได้เลย</span>
                <a href="{{ route('taladsod.home') }}#near-me" class="ts-btn3d ts-tone-gold"><i class="fas fa-map-location-dot" aria-hidden="true"></i> ดูร้านใกล้ฉัน</a>
            </div>
        @else
            <div class="sf-2col">
                <div class="sf-stack">
                    @foreach($shops as $shop)
                        @php
                            $sid = (int) $shop['seller_id'];
                            $sInfo = $shop['seller'] ?? null;
                        @endphp
                        <section class="tp-card" style="padding:16px;" x-show="shop({{ $sid }})" x-transition.opacity aria-label="ตะกร้าร้าน {{ $sInfo['shop_name'] ?? '' }}">
                            {{-- หัวร้าน --}}
                            <div class="ts-row" style="justify-content:space-between; gap:10px; padding-bottom:10px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 16%, transparent);">
                                <a href="{{ route('taladsod.seller', $sid) }}" class="ts-row" style="gap:10px; text-decoration:none; color:var(--ink); flex-wrap:nowrap; min-width:0;">
                                    <span class="ts-avatar" style="width:42px; height:42px; border-radius:14px; font-size:16px;">
                                        @if(! empty($sInfo['shop_image']))
                                            <img src="{{ $sInfo['shop_image'] }}" alt="">
                                        @else
                                            {{ $ui::initial($sInfo['shop_name'] ?? '') }}
                                        @endif
                                    </span>
                                    <span style="min-width:0;">
                                        <b style="display:block; font-size:15px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $sInfo['shop_name'] ?? 'ร้านที่ปิดไปแล้ว' }}</b>
                                        @if(! empty($sInfo['is_open']))
                                            <span class="ts-pill ts-tone-ok" style="padding:4px 9px;"><span class="ts-dot"></span> เปิดอยู่</span>
                                        @else
                                            <span class="ts-pill ts-tone-muted" style="padding:4px 9px;"><i class="fas fa-moon" aria-hidden="true"></i> ปิดอยู่</span>
                                        @endif
                                        @if(! empty($sInfo['is_mobile']))
                                            <span class="ts-pill ts-tone-deep" style="padding:4px 9px;"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> รถเข็น</span>
                                        @endif
                                    </span>
                                </a>
                                <button type="button" class="tp-btn tp-btn-sm" x-on:click="askClear({{ $sid }}, @js($sInfo['shop_name'] ?? 'ร้านนี้'))" :disabled="busy">
                                    <i class="fas fa-trash-can" aria-hidden="true"></i> ล้างร้านนี้
                                </button>
                            </div>

                            {{-- รายการ --}}
                            @foreach($shop['items'] as $line)
                                @php $lid = (int) $line['id']; @endphp
                                <div class="ts-line" x-show="line({{ $lid }})">
                                    <a href="{{ $line['slug'] ? route('taladsod.listing', $line['slug']) : '#' }}" class="sf-thumb" style="width:72px; height:72px;">
                                        @if($line['image_url'])
                                            <img src="{{ $line['image_url'] }}" alt="" loading="lazy">
                                        @else
                                            <span aria-hidden="true">🥬</span>
                                        @endif
                                    </a>
                                    <div style="flex:1; min-width:0;" class="ts-stack" >
                                        <div style="display:flex; flex-direction:column; gap:3px;">
                                            <b style="font-size:14px; line-height:1.4; overflow-wrap:anywhere;">{{ $line['title'] }}</b>
                                            @if($line['options_label'] !== '')
                                                <span class="ts-muted ts-small"><i class="fas fa-sliders" aria-hidden="true"></i> {{ $line['options_label'] }}</span>
                                            @endif
                                            @if($line['note'])
                                                <span class="ts-muted ts-small"><i class="fas fa-note-sticky" aria-hidden="true"></i> {{ $line['note'] }}</span>
                                            @endif
                                            <span class="ts-small ts-muted">฿<span x-text="money(line({{ $lid }})?.unit_price)">{{ $ui::money($line['unit_price']) }}</span> / {{ $line['unit'] ?: 'ชิ้น' }}</span>
                                        </div>
                                        <p class="ts-err" style="margin:0;" x-show="line({{ $lid }})?.issue" x-text="line({{ $lid }})?.issue?.message" @if($line['is_valid']) x-cloak @endif>{{ $line['issue']['message'] ?? '' }}</p>
                                        <div class="ts-row" style="justify-content:space-between; gap:8px;">
                                            <div class="sf-qty" role="group" aria-label="จำนวน {{ $line['title'] }}">
                                                <button type="button" x-on:click="step({{ $lid }}, -1)" :disabled="busy" aria-label="ลดจำนวน">−</button>
                                                <input type="number" min="1" :max="line({{ $lid }})?.max_order_quantity || 999" inputmode="numeric"
                                                       :value="qty[{{ $lid }}] ?? line({{ $lid }})?.quantity" value="{{ (int) $line['quantity'] }}"
                                                       x-on:change="setQty({{ $lid }}, $event.target.value)" aria-label="จำนวน">
                                                <button type="button" x-on:click="step({{ $lid }}, 1)" :disabled="busy" aria-label="เพิ่มจำนวน">+</button>
                                            </div>
                                            <div class="ts-row" style="gap:8px;">
                                                <b class="ts-money" style="font-size:16px;">฿<span x-text="money(line({{ $lid }})?.line_total)">{{ $ui::money($line['line_total']) }}</span></b>
                                                <button type="button" class="ts-icon-btn" style="color:var(--ts-bad);" x-on:click="askRemove({{ $lid }}, @js($line['title']))" :disabled="busy" aria-label="ลบ {{ $line['title'] }}">
                                                    <i class="fas fa-trash-can" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- สรุปร้าน --}}
                            <div class="ts-stack" style="gap:10px; padding-top:12px;">
                                <div class="ts-kv"><span>ยอดสินค้า (<span x-text="shop({{ $sid }})?.items_count">{{ (int) $shop['items_count'] }}</span> ชิ้น)</span><b class="ts-money" style="font-size:18px;">฿<span x-text="money(shop({{ $sid }})?.subtotal)">{{ $ui::money($shop['subtotal']) }}</span></b></div>
                                <p class="ts-help" style="margin:0;">ค่าส่ง (ถ้าให้ไรเดอร์ส่ง) คำนวณตามระยะทางในหน้าถัดไป</p>
                                <template x-if="shop({{ $sid }}) && !shop({{ $sid }}).can_checkout">
                                    <div class="sf-note sf-note-warn" x-text="blockReason({{ $sid }})"></div>
                                </template>
                                <a href="{{ route('taladsod.checkout', ['seller' => $sid]) }}" class="ts-btn3d ts-tone-gold block"
                                   :class="shop({{ $sid }}) && !shop({{ $sid }}).can_checkout ? 'is-disabled' : ''"
                                   x-on:click="if (shop({{ $sid }}) && !shop({{ $sid }}).can_checkout) { $event.preventDefault(); window.ts.notify(blockReason({{ $sid }}), 'error'); }">
                                    <i class="fas fa-bag-shopping" aria-hidden="true"></i> สั่งซื้อจากร้านนี้
                                </a>
                            </div>
                        </section>
                    @endforeach

                    <div class="tp-card ts-empty" x-show="(cart.shops || []).length === 0" x-cloak>
                        <span class="em" aria-hidden="true">🧺</span>
                        <b>ตะกร้าว่างแล้ว</b>
                        <a href="{{ route('taladsod.home') }}" class="ts-btn3d ts-tone-gold sm">เลือกซื้อต่อ</a>
                    </div>
                </div>

                <aside class="sf-sticky" aria-label="สรุปตะกร้า">
                    <div class="tp-card ts-stack">
                        <h2 class="ts-h2"><i class="fas fa-basket-shopping" style="color:var(--accent2);" aria-hidden="true"></i> สรุปตะกร้า</h2>
                        <div class="ts-kv"><span>จำนวนร้าน</span><b x-text="(cart.shops || []).length">{{ count($shops) }}</b></div>
                        <div class="ts-kv"><span>จำนวนสินค้า</span><b x-text="cart.items_count">{{ (int) ($cart['items_count'] ?? 0) }}</b></div>
                        <div class="sf-total"><span class="ts-muted">ยอดสินค้ารวม</span><span class="tp-num">฿<span x-text="money(cart.subtotal)">{{ $ui::money($cart['subtotal'] ?? 0) }}</span></span></div>
                        <div class="ts-stack" style="gap:6px;">
                            @if(in_array('wallet', $paymentMethods, true))
                                <span class="ts-pill ts-tone-info" style="align-self:flex-start;"><i class="fas fa-wallet" aria-hidden="true"></i> จ่ายด้วยกระเป๋าเงิน (ระบบถือเงินไว้จนได้รับของ)</span>
                            @endif
                            @if(in_array('cod', $paymentMethods, true))
                                <span class="ts-pill ts-tone-ok" style="align-self:flex-start;"><i class="fas fa-money-bill-wave" aria-hidden="true"></i> เก็บเงินปลายทาง</span>
                            @endif
                            @if($riderEnabled)
                                <span class="ts-pill ts-tone-deep" style="align-self:flex-start;"><i class="fas fa-motorcycle" aria-hidden="true"></i> ไรเดอร์ส่งถึงบ้าน หรือรับเองที่ร้าน</span>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>

    {{-- กล่องยืนยันลบ --}}
    <div class="ts-dialog-bg" x-show="confirm" x-cloak x-transition.opacity x-on:keydown.escape.window="confirm = null" x-on:click.self="confirm = null">
        <div class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="ts-cart-confirm-h">
            <h2 id="ts-cart-confirm-h" class="ts-h2"><i class="fas fa-trash-can" style="color:var(--ts-bad);" aria-hidden="true"></i> <span x-text="confirm ? confirm.title : ''"></span></h2>
            <p class="ts-muted" style="margin:0;" x-text="confirm ? confirm.text : ''"></p>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="confirm = null">ยกเลิก</button>
                <button type="button" class="ts-btn3d ts-tone-bad sm" x-on:click="runConfirm()" :disabled="busy"><i class="fas fa-trash-can" aria-hidden="true"></i> ลบเลย</button>
            </div>
        </div>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * ตะกร้า: หน้าเรนเดอร์จากเซิร์ฟเวอร์ แล้ว Alpine ผูกตัวเลขเข้ากับข้อมูลตะกร้าล่าสุด (ทุกคำสั่งตอบตะกร้าทั้งใบ)
     * บรรทัด/ร้านที่หายไปจากข้อมูลล่าสุดถูกซ่อน — ไม่ต้องโหลดหน้าใหม่
     */
    window.tsCart = function (cfg) {
        const timers = {};

        return {
            cart: cfg.cart, busy: false, qty: {}, confirm: null,

            money(n) { return window.ts.money(n || 0); },

            shop(id) { return (this.cart.shops || []).find((s) => Number(s.seller_id) === Number(id)) || null; },

            line(id) {
                for (const s of (this.cart.shops || [])) {
                    const l = (s.items || []).find((x) => Number(x.id) === Number(id));
                    if (l) { return l; }
                }
                return null;
            },

            blockReason(sid) {
                const s = this.shop(sid);
                if (!s) { return ''; }
                if (s.seller && !s.seller.is_available) { return 'ร้านนี้ปิดรับออเดอร์ชั่วคราว'; }
                if (!s.is_open) { return (s.seller && s.seller.closed_message) || 'ร้านปิดอยู่ — สั่งได้เมื่อร้านเปิด'; }
                if (s.issues_count > 0) { return 'มีสินค้าที่สั่งไม่ได้ในตะกร้า กรุณาลบหรือแก้จำนวนก่อน'; }
                return 'ยังสั่งซื้อจากร้านนี้ไม่ได้';
            },

            apply(r) {
                if (r.data && Array.isArray(r.data.shops)) {
                    this.cart = r.data;
                    this.qty = {};
                    window.dispatchEvent(new CustomEvent('ts-cart-count', { detail: { count: r.data.items_count || 0 } }));
                }
            },

            step(id, delta) {
                const l = this.line(id);
                if (!l) { return; }
                const next = (this.qty[id] ?? l.quantity) + delta;
                if (next < 1) {
                    this.askRemove(id, l.title);
                    return;
                }
                this.setQty(id, next);
            },

            setQty(id, value) {
                const l = this.line(id);
                if (!l) { return; }
                const max = l.max_order_quantity > 0 ? l.max_order_quantity : 999;
                const q = Math.min(max, Math.max(1, parseInt(value, 10) || 1));
                this.qty[id] = q;
                clearTimeout(timers[id]);
                // รวบการกดรัวๆ เป็นคำขอเดียว
                timers[id] = setTimeout(() => this.saveQty(id, q), 450);
            },

            async saveQty(id, q) {
                this.busy = true;
                const r = await window.ts.put(cfg.updateUrl.replace('__ID__', id), { quantity: q });
                this.busy = false;
                if (!r.ok) {
                    delete this.qty[id];
                    window.ts.notify(r.message, 'error');
                    return;
                }
                this.apply(r);
            },

            askRemove(id, title) {
                this.confirm = { kind: 'line', id: id, title: 'ลบออกจากตะกร้า?', text: 'ลบ "' + title + '" ออกจากตะกร้า' };
            },

            askClear(sid, name) {
                this.confirm = { kind: 'shop', id: sid, title: 'ล้างตะกร้าร้านนี้?', text: 'ลบสินค้าทั้งหมดของ "' + name + '" ออกจากตะกร้า' };
            },

            async runConfirm() {
                const c = this.confirm;
                if (!c) { return; }
                this.busy = true;
                const r = c.kind === 'line'
                    ? await window.ts.del(cfg.destroyUrl.replace('__ID__', c.id))
                    : await window.ts.del(cfg.clearUrl, { seller_id: c.id });
                this.busy = false;
                this.confirm = null;
                if (!r.ok) {
                    window.ts.notify(r.message, 'error');
                    return;
                }
                this.apply(r);
                window.ts.notify(r.message || 'อัปเดตตะกร้าแล้ว', 'success');
            }
        };
    };
</script>
@endpush

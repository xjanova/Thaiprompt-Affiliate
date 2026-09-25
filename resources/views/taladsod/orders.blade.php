{{--
 | ออเดอร์ตลาดสดของฉัน (taladsod.orders) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@orders
 | ตัวแปร: $orders (paginator + seller, listing, items), $statusFilter (?string), $statuses [status => ป้ายไทย]
 | ยกเลิก: PUT taladsod.orders.cancel {reason?} — แสดงเมื่อ $order->canBeCancelled('buyer') (มีกล่องยืนยัน)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ออเดอร์ตลาดสดของฉัน')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $filters = array_merge(['all' => 'ทั้งหมด'], $statuses);
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="orders" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;" x-data="{ cancelFor: null, cancelUrl: '', reason: '', sending: false }">
    <div class="sf-wrap" style="max-width:980px;">
        @include('taladsod.partials.nav', ['active' => 'orders'])

        <div class="ts-row" style="justify-content:space-between; margin:6px 0 12px;">
            <h1 class="sf-h1">ออเดอร์ตลาดสดของฉัน</h1>
            <a href="{{ route('taladsod.home') }}" class="tp-btn"><i class="fas fa-basket-shopping" aria-hidden="true"></i> สั่งเพิ่ม</a>
        </div>

        <div class="sf-scroll" role="tablist" aria-label="กรองตามสถานะ">
            @foreach($filters as $key => $label)
                @php $isOn = ($key === 'all' && ! $statusFilter) || $key === $statusFilter; @endphp
                <a href="{{ route('taladsod.orders', $key === 'all' ? [] : ['status' => $key]) }}" class="sf-chip {{ $isOn ? 'is-on' : '' }}" role="tab" aria-selected="{{ $isOn ? 'true' : 'false' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="ts-stack" style="margin-top:10px;">
            @forelse($orders as $order)
                @php
                    $lines = $order->lineItems();
                    $first = $lines->first();
                    $more = max(0, $lines->count() - 1);
                    $tone = $ui::orderTone($order->order_status);
                @endphp
                <article class="tp-card tp-card-hover" style="padding:16px;">
                    <div class="ts-row" style="justify-content:space-between; gap:8px;">
                        <div class="ts-row" style="gap:8px; min-width:0;">
                            <span class="ts-avatar" style="width:38px; height:38px; border-radius:12px; font-size:14px;">{{ $ui::initial($order->seller?->shop_name) }}</span>
                            <div style="min-width:0;">
                                <b style="display:block; font-size:14.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $order->seller?->shop_name ?? 'ร้านตลาดสด' }}</b>
                                <span class="ts-muted ts-small">#{{ $order->order_number }} · {{ $ui::shortDate($order->created_at) }}</span>
                            </div>
                        </div>
                        <span class="ts-pill ts-tone-{{ $tone }}"><i class="fas {{ $ui::orderIcon($order->order_status) }}" aria-hidden="true"></i> {{ $order->status_label }}</span>
                    </div>

                    <a href="{{ route('taladsod.orders.show', $order) }}" class="ts-row" style="gap:12px; flex-wrap:nowrap; margin-top:12px; text-decoration:none; color:var(--ink);">
                        <span class="sf-thumb">
                            @if($first?->image_url)
                                <img src="{{ $first->image_url }}" alt="" loading="lazy">
                            @else
                                <span aria-hidden="true">🥬</span>
                            @endif
                        </span>
                        <span style="flex:1; min-width:0;">
                            <b style="display:block; font-size:14px; overflow-wrap:anywhere;">{{ $first?->title ?? 'สินค้าตลาดสด' }} × {{ (int) ($first?->quantity ?? $order->quantity) }}</b>
                            @if($first && $first->optionsLabel() !== '')
                                <span class="ts-muted ts-small" style="display:block;">{{ $first->optionsLabel() }}</span>
                            @endif
                            @if($more > 0)
                                <span class="ts-muted ts-small">และอีก {{ $more }} รายการ</span>
                            @endif
                        </span>
                        <span style="text-align:right;">
                            <b class="ts-money" style="font-size:17px; display:block;">฿{{ $ui::money($order->grand_total) }}</b>
                            <span class="ts-muted ts-small">{{ $ui::deliveryLabel($order->delivery_type) }}</span>
                        </span>
                    </a>

                    <div class="ts-row" style="justify-content:space-between; gap:8px; margin-top:12px;">
                        <span class="ts-muted ts-small"><i class="fas {{ $order->payment_method === 'cod' ? 'fa-money-bill-wave' : 'fa-wallet' }}" aria-hidden="true"></i> {{ $ui::paymentShortLabel($order->payment_method) }} · {{ $order->payment_status_label }}</span>
                        <div class="ts-row" style="gap:8px;">
                            @if($order->canBeCancelled('buyer'))
                                <button type="button" class="tp-btn tp-btn-sm ts-btn-ghost ts-tone-bad"
                                        x-on:click="cancelFor = @js($order->order_number); cancelUrl = @js(route('taladsod.orders.cancel', $order)); reason = ''">
                                    <i class="fas fa-xmark" aria-hidden="true"></i> ยกเลิก
                                </button>
                            @endif
                            @if($order->canBeReviewed())
                                <a href="{{ route('taladsod.orders.show', $order) }}#review" class="tp-btn tp-btn-sm"><i class="fas fa-star" aria-hidden="true"></i> ให้คะแนน</a>
                            @endif
                            <a href="{{ route('taladsod.orders.show', $order) }}" class="ts-btn3d sm ts-tone-gold">ดูรายละเอียด <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="tp-card ts-empty" style="padding:44px 18px;">
                    <span class="em" aria-hidden="true">🧾</span>
                    <b style="font-size:16px;">{{ $statusFilter ? 'ไม่มีออเดอร์ในสถานะนี้' : 'ยังไม่มีออเดอร์ตลาดสด' }}</b>
                    <span class="ts-muted">ร้านอร่อยใกล้บ้านรออยู่ สั่งครั้งแรกได้เลย</span>
                    <a href="{{ route('taladsod.home') }}" class="ts-btn3d ts-tone-gold"><i class="fas fa-carrot" aria-hidden="true"></i> ไปตลาดสด</a>
                </div>
            @endforelse
        </div>

        @if($orders->hasPages())
            <div style="margin-top:20px;">{{ $orders->links('vendor.pagination.tp-v4') }}</div>
        @endif
    </div>

    {{-- กล่องยืนยันยกเลิก --}}
    <div class="ts-dialog-bg" x-show="cancelFor" x-cloak x-transition.opacity x-on:keydown.escape.window="cancelFor = null" x-on:click.self="cancelFor = null">
        <form method="POST" :action="cancelUrl" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="ts-cancel-h" x-on:submit="sending = true">
            @csrf
            @method('PUT')
            <h2 id="ts-cancel-h" class="ts-h2"><i class="fas fa-circle-xmark" style="color:var(--ts-bad);" aria-hidden="true"></i> ยกเลิกออเดอร์ #<span x-text="cancelFor"></span>?</h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;">ยกเลิกได้เฉพาะตอนร้านยังไม่รับออเดอร์ — ถ้าจ่ายด้วยกระเป๋าเงินไว้ ระบบคืนเงินให้ทันที</p>
            <div>
                <label class="ts-label" for="ts-cancel-reason">เหตุผล <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></label>
                <input id="ts-cancel-reason" type="text" name="reason" class="tp-input" maxlength="500" x-model="reason" placeholder="เช่น สั่งผิดร้าน เปลี่ยนใจ">
            </div>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="cancelFor = null">ไม่ยกเลิก</button>
                <button type="submit" class="ts-btn3d ts-tone-bad sm" :disabled="sending"><i class="fas fa-xmark" aria-hidden="true"></i> ยืนยันยกเลิก</button>
            </div>
        </form>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

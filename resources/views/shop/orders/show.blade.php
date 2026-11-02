{{--
    รายละเอียดคำสั่งซื้อ (ผู้ซื้อ) — ธีมโนวาเต็ม
    ข้อมูลจาก OrderController::show: $order (items.product, items.reviews, shippingAddress, shippingProviderRelation, trackingHistory)
    - ไทม์ไลน์สถานะ · ลิงก์ติดตามพัสดุที่เว็บขนส่ง · ติดตามพัสดุเรียลไทม์ (orders.tracking.realtime)
    - ส่งด้วยไรเดอร์: การ์ดไรเดอร์ + ลิงก์หน้าติดตาม + แผนที่ตำแหน่งไรเดอร์แบบสด
      (API ของผู้ซื้อ taladsod.delivery.rider-location ตรวจเจ้าของออเดอร์ + ความยินยอมแชร์ตำแหน่งของไรเดอร์ฝั่งเซิร์ฟเวอร์
       ไม่มี route นี้ → สำรองด้วย taladsod.track.location ผ่าน token ของงาน)
    - ชำระใหม่ POST orders.retry-payment · จ่ายด้วย TPIX POST tpix-pay.order · ยืนยันรับของ POST orders.confirm-received
    - ยกเลิก POST orders.cancel {reason} (ชำระแล้ว = คืนเงินเข้ากระเป๋าอัตโนมัติ)
--}}
@extends('layouts.user-nova')

@section('title', 'คำสั่งซื้อ #'.$order->order_number)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    $odShipping = \App\Services\Shop\ShopPresenter::shipping($order);
    $odRider = \App\Services\Shop\ShopPresenter::riderSummary($order);
    $odIsCod = $order->isCod();
    $odCanPay = $order->canRetryPayment() && ! $odIsCod;
    $odCancelled = in_array($order->status, ['cancelled', 'refunded'], true);

    // งานไรเดอร์ที่ยังวิ่งอยู่ → แผนที่สด
    $odJob = $order->isRiderDelivery() ? \App\Models\RiderJob::forSource($order)->latest('id')->first() : null;
    $odActiveStatuses = ['accepted', 'picking_up', 'picked_up', 'delivering'];
    $odLive = $odJob && $odJob->isTrackable() && in_array($odJob->status, $odActiveStatuses, true);
    $odStore = $order->store;
    $odHasApi = RouteFacade::has('taladsod.delivery.rider-location');
    $odMapCfg = null;
    if ($odLive && ($odHasApi || $odJob->tracking_token)) {
        $odMapCfg = [
            'mode' => $odHasApi ? 'order' : 'token',
            'locationUrl' => $odHasApi
                ? route('taladsod.delivery.rider-location', ['source' => 'shop', 'id' => $order->id])
                : route('taladsod.track.location', $odJob->tracking_token),
            'shareUrl' => RouteFacade::has('taladsod.delivery.share-location')
                ? route('taladsod.delivery.share-location', ['source' => 'shop', 'id' => $order->id])
                : null,
            'drop' => ($odShipping && $odShipping['latitude'] !== null) ? [$odShipping['latitude'], $odShipping['longitude']] : null,
            'pickup' => ($odStore && $odStore->pickup_latitude) ? [(float) $odStore->pickup_latitude, (float) $odStore->pickup_longitude] : null,
        ];
    }
    $odLive = $odMapCfg !== null;

    // ไทม์ไลน์
    $odTracking = $order->trackingHistory ? $order->trackingHistory->sortBy('tracked_at') : collect();
    $odTransit = $odTracking->filter(fn ($e) => in_array($e->status, ['in_transit', 'at_sorting_center', 'out_for_delivery'], true));
    $odProcessingAt = optional($odTracking->firstWhere('status', 'processing'))->tracked_at ?? ($order->paid_at ?? null);
    $odIsPrepared = in_array($order->status, ['processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'completed'], true);
    $odTrackingUrl = null;
    if ($order->tracking_number) {
        $odTrackingUrl = $order->shippingProviderRelation
            ? $order->shippingProviderRelation->getTrackingLink($order->tracking_number)
            : ($order->tracking_url ?: null);
    }
    $odTransitLabels = ['in_transit' => 'กำลังขนส่ง', 'at_sorting_center' => 'ถึงศูนย์กระจายสินค้า', 'out_for_delivery' => 'กำลังนำส่ง'];
    $odFmt = fn ($d) => $d ? $d->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') : null;

    // ขั้นตอน: icon = ชื่อไอคอนในสไปรต์ #nvm-i-*
    $odSteps = [];
    $odSteps[] = ['icon' => 'plus', 'title' => 'สร้างคำสั่งซื้อ', 'time' => $odFmt($order->created_at), 'done' => true];
    if ($odIsCod) {
        $odSteps[] = ['icon' => 'cash', 'title' => $order->payment_status === 'paid' ? 'ชำระเงินปลายทางแล้ว' : 'ชำระเงินปลายทาง (จ่ายตอนรับของ)', 'time' => $odFmt($order->paid_at), 'done' => $order->payment_status === 'paid'];
    } else {
        $odSteps[] = ['icon' => 'wallet', 'title' => $order->paid_at ? 'ชำระเงินแล้ว' : 'รอชำระเงิน', 'time' => $odFmt($order->paid_at), 'done' => (bool) $order->paid_at, 'sub' => $order->payment_reference ? 'อ้างอิง: '.$order->payment_reference : null];
    }
    $odSteps[] = ['icon' => 'store', 'title' => 'ร้านกำลังเตรียมสินค้า', 'time' => $odIsPrepared ? $odFmt($odProcessingAt) : null, 'done' => $odIsPrepared];
    if ($order->isRiderDelivery()) {
        $odRiderStarted = $odJob && in_array($odJob->status, ['picked_up', 'delivering', 'awaiting_release', 'delivered', 'completed'], true);
        $odSteps[] = ['icon' => 'scooter', 'title' => $odRider['status_label'] ?? 'รอร้านเรียกไรเดอร์', 'time' => $odJob ? $odFmt($odJob->accepted_at) : null, 'done' => $odRiderStarted, 'now' => $odLive];
    } else {
        $odSteps[] = ['icon' => 'send', 'title' => $order->shipped_at ? 'จัดส่งสินค้าแล้ว' : 'รอจัดส่ง', 'time' => $odFmt($order->shipped_at), 'done' => (bool) $order->shipped_at,
            'sub' => $order->tracking_number ? 'เลขพัสดุ '.$order->tracking_number.($order->shippingProviderRelation ? ' · '.$order->shippingProviderRelation->name : '') : null];
        foreach ($odTransit as $u) {
            $odSteps[] = ['icon' => 'pin', 'title' => $u->title ?: ($odTransitLabels[$u->status] ?? $u->status), 'time' => $odFmt($u->tracked_at), 'done' => true, 'sub' => trim(($u->description ?? '').' '.($u->location ? '· '.$u->location : '')) ?: null, 'small' => true];
        }
    }
    $odSteps[] = ['icon' => 'home', 'title' => 'ส่งถึงแล้ว', 'time' => $odFmt($order->delivered_at), 'done' => (bool) $order->delivered_at];
    $odSteps[] = ['icon' => 'award', 'title' => 'สำเร็จ', 'time' => $order->status === 'completed' ? $odFmt($order->completed_at ?? $order->updated_at) : null, 'done' => $order->status === 'completed'];

    $odPill = match ((string) $order->status) {
        'pending' => 'warn',
        'delivered', 'completed' => 'ok',
        'cancelled', 'refunded' => 'bad',
        default => 'glass',
    };
    $odPaidLabel = $odIsCod && $order->payment_status !== 'paid' ? 'จ่ายเมื่อรับของ' : \App\Services\Shop\ShopPresenter::paymentStatusLabel($order->payment_status);
@endphp

@section('content')
@if($odLive)
    @include('shop.orders.partials.map')
@endif

<section class="nvm-ph" aria-labelledby="nvm-os-od-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('orders.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>คำสั่งซื้อของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Order detail</div>
            <h1 id="nvm-os-od-h" class="nvm-num">#{{ $order->order_number }}</h1>
            <p class="nvm-ph__lead nvm-num">สั่งเมื่อ {{ $odFmt($order->created_at) }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $odPill }}">{{ $order->status_label }}</span>
                @if($order->isRiderDelivery())
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>ส่งด่วนด้วยไรเดอร์</span>
                @endif
            </div>
            <div class="nvm-os-ph-amt">
                <small>ยอดรวมทั้งหมด</small>
                <b class="nvm-foil nvm-num">฿{{ number_format((float) $order->total_amount, 2) }}</b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ข้อความสำเร็จ/ผิดพลาด (session flash) แสดงเป็น toast จาก layouts.user-nova ที่เดียว ไม่ซ้ำในหน้า --}}

<div class="nvm-split">
    <div class="nvm-stack">

        {{-- ── ไรเดอร์ (ส่งด่วน) ── --}}
        @if($odRider)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-od-rd-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Rider</small><h2 id="nvm-os-od-rd-h">ไรเดอร์ส่งของ</h2></div></div>
                <div class="nvm-os-who">
                    <span class="nvm-os-who__av"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg></span>
                    <div class="nvm-os-who__t">
                        <b>{{ $odRider['status_label'] ?? 'รอร้านเรียกไรเดอร์' }}</b>
                        @if(! empty($odRider['rider']))
                            <small>
                                {{ $odRider['rider']['name'] ?? 'ไรเดอร์' }}
                                @if(! empty($odRider['rider']['vehicle_plate'])) · ทะเบียน {{ $odRider['rider']['vehicle_plate'] }} @endif
                            </small>
                        @elseif(($odRider['status'] ?? '') === 'not_requested')
                            <small>ร้านจะเรียกไรเดอร์เมื่อเตรียมสินค้าเสร็จ</small>
                        @endif
                    </div>
                </div>
                @if(! empty($odRider['rider']['phone']) || ! empty($odRider['tracking_url']))
                    <div class="nvm-os-acts nvm-gap-t">
                        @if(! empty($odRider['rider']['phone']))
                            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $odRider['rider']['phone']) }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>โทรหาไรเดอร์</a>
                        @endif
                        @if(! empty($odRider['tracking_url']))
                            <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ $odRider['tracking_url'] }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>หน้าติดตามไรเดอร์</a>
                        @endif
                    </div>
                @endif

                @if($odLive)
                    <div class="nvm-gap-t" x-data="nvmOsRiderLive(@js($odMapCfg))">
                        <div class="nvm-os-map" x-ref="map" role="img" aria-label="แผนที่ตำแหน่งไรเดอร์"></div>
                        <div class="nvm-os-live">
                            <span><i class="nvm-os-dot" :class="live && 'is-on'"></i><span x-text="statusText"></span></span>
                            <span x-show="updatedAgo" x-cloak x-text="'อัปเดต ' + updatedAgo"></span>
                        </div>

                        {{-- ผู้ซื้อเลือกแชร์ตำแหน่งตัวเองให้ไรเดอร์ (หยุดเองเมื่อส่งเสร็จ/ยกเลิก) --}}
                        <template x-if="cfg.shareUrl && canShare">
                            <div class="nvm-os-share">
                                <div>
                                    <b><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>แชร์ตำแหน่งของฉันให้ไรเดอร์</b>
                                    <small x-text="sharing ? 'ไรเดอร์เห็นตำแหน่งของคุณระหว่างมาส่ง · หยุดแชร์เองเมื่อส่งเสร็จ' : 'ช่วยให้ไรเดอร์หาคุณเจอง่ายขึ้น (ส่งเฉพาะตอนเปิดหน้านี้)'"></small>
                                </div>
                                <button type="button" class="nvm-btn nvm-btn--sm" :class="sharing ? 'nvm-btn--gold' : 'nvm-btn--line'" @click="toggleShare()" :disabled="shareBusy">
                                    <span x-text="shareBusy ? 'กำลังดำเนินการ…' : (sharing ? 'กำลังแชร์ — หยุด' : 'เปิดแชร์ตำแหน่ง')"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                @endif
            </section>
        @endif

        {{-- ── ไทม์ไลน์ ── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-od-tl-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Status</small><h2 id="nvm-os-od-tl-h">สถานะคำสั่งซื้อ</h2></div></div>
            @if($odCancelled)
                <div class="nvm-status is-bad">
                    <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
                    <span>{{ $order->status === 'refunded' ? 'คืนเงินแล้ว' : 'ยกเลิกคำสั่งซื้อแล้ว' }}@if($order->cancelled_at) · <span class="nvm-num">{{ $odFmt($order->cancelled_at) }}</span>@endif
                        @if($order->cancellation_reason)<small>เหตุผล: {{ $order->cancellation_reason }}</small>@endif
                    </span>
                </div>
            @endif
            <ol class="nvm-os-tl {{ $odCancelled ? 'nvm-gap-t' : '' }}">
                @foreach($odSteps as $i => $step)
                    @php $odNext = ! $step['done'] && ($i === 0 || $odSteps[$i - 1]['done']) && ! $odCancelled; @endphp
                    <li class="{{ $step['done'] ? 'is-done' : '' }} {{ ! empty($step['now']) || $odNext ? 'is-now' : '' }} {{ ! empty($step['small']) ? 'is-small' : '' }}">
                        <span class="nvm-os-tl__dot"><svg class="nvm-ico"><use href="#nvm-i-{{ $step['done'] ? ($step['icon'] === 'award' ? 'award' : 'check') : $step['icon'] }}"/></svg></span>
                        <div class="nvm-os-tl__b">
                            <b>{{ $step['title'] }}</b>
                            @if(! empty($step['time']))<small class="nvm-num">{{ $step['time'] }}</small>@endif
                            @if(! empty($step['sub']))<small>{{ $step['sub'] }}</small>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if($odTrackingUrl || $order->status === 'delivered')
                <div class="nvm-os-acts nvm-gap-t">
                    @if($odTrackingUrl)
                        <a class="nvm-btn nvm-btn--line" href="{{ $odTrackingUrl }}" target="_blank" rel="noopener noreferrer"><svg class="nvm-ico"><use href="#nvm-i-out"/></svg>ติดตามพัสดุที่เว็บขนส่ง</a>
                    @endif
                    @if($order->status === 'delivered')
                        <form method="POST" action="{{ route('orders.confirm-received', $order->id) }}" x-data="{ busy: false }"
                              @submit="if (busy || ! confirm(@js('ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?'))) { $event.preventDefault(); return; } busy = true">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ยืนยันว่าได้รับสินค้าแล้ว</button>
                        </form>
                    @endif
                </div>
            @endif
        </section>

        {{-- ── ติดตามพัสดุเรียลไทม์ (มีเลขพัสดุ) ── --}}
        @if($order->tracking_number && in_array($order->status, ['shipped', 'in_transit', 'out_for_delivery', 'delivered'], true))
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-od-pt-h" x-data="nvmOsParcel(@js(route('orders.tracking.realtime', $order->id)))">
                <div class="nvm-sec-h">
                    <div><small class="nvm-latin">Parcel tracking</small><h2 id="nvm-os-od-pt-h">ติดตามพัสดุเรียลไทม์</h2></div>
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="load(true)" :disabled="loading"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg><span x-text="loading ? 'กำลังโหลด…' : 'รีเฟรช'">รีเฟรช</span></button>
                </div>
                <p class="nvm-help" x-show="loading && ! data">กำลังดึงข้อมูลจากขนส่ง…</p>
                <p class="nvm-note is-bad" x-show="error" x-cloak><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span x-text="error"></span></p>
                <template x-if="data">
                    <div>
                        <div class="nvm-os-acts">
                            <span class="nvm-chip nvm-chip--gold" x-text="data.current_status_label || 'กำลังตรวจสอบ'"></span>
                            <span class="nvm-help" x-text="(data.provider && data.provider.name) ? data.provider.name : ''"></span>
                        </div>
                        <template x-if="data.carrier_data && data.carrier_data.message">
                            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span x-text="data.carrier_data.message"></span></p>
                        </template>
                        <ol class="nvm-os-tl nvm-os-tl--scroll nvm-gap-t">
                            <template x-for="(ev, k) in (data.timeline || [])" :key="k">
                                <li class="is-done is-small">
                                    <span class="nvm-os-tl__dot"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg></span>
                                    <div class="nvm-os-tl__b">
                                        <b x-text="ev.title"></b>
                                        <small x-show="ev.description" x-text="ev.description"></small>
                                        <small x-show="ev.location" x-text="ev.location"></small>
                                        <small class="nvm-num" x-text="ev.timestamp_display || ev.timestamp"></small>
                                    </div>
                                </li>
                            </template>
                        </ol>
                    </div>
                </template>
            </section>
        @endif

        {{-- ── รายการสินค้า ── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-od-it-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Items</small><h2 id="nvm-os-od-it-h">รายการสินค้า ({{ $order->items->count() }})</h2></div></div>
            @foreach($order->items as $item)
                @php
                    $odImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image);
                    $odReviewed = $item->relationLoaded('reviews') ? $item->reviews->isNotEmpty() : $item->hasReview();
                @endphp
                <div class="nvm-os-lineitem">
                    <span class="nvm-os-thumb nvm-os-thumb--lg">@if($odImg)<img src="{{ $odImg }}" alt="" loading="lazy">@else<svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>@endif</span>
                    <div class="nvm-os-lineitem__b">
                        <b>{{ $item->product_name }}</b>
                        @if(is_array($item->product_attributes) && $item->product_attributes !== [])
                            <small>
                                @foreach($item->product_attributes as $ak => $av)
                                    @if(is_scalar($av)){{ $ak }}: {{ $av }}@if(! $loop->last), @endif @endif
                                @endforeach
                            </small>
                        @endif
                        <div class="nvm-os-lineitem__p">
                            <span class="nvm-num">{{ (int) $item->quantity }} × ฿{{ number_format((float) $item->unit_price, 2) }}</span>
                            <b class="nvm-num">฿{{ number_format((float) $item->total, 2) }}</b>
                        </div>
                        @if(in_array($order->status, ['delivered', 'completed'], true))
                            <div class="nvm-os-acts">
                                @if($odReviewed)
                                    <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>รีวิวแล้ว</span>
                                @else
                                    <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('orders.review.form', [$order->id, $item->id]) }}"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>รีวิวสินค้า</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    {{-- ── สรุป / ที่อยู่ / ชำระ / ยกเลิก ── --}}
    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-od-sum-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Summary</small><h2 id="nvm-os-od-sum-h">สรุปคำสั่งซื้อ</h2></div></div>
            <dl class="nvm-kv">
                <dt>ยอดรวมสินค้า</dt><dd class="nvm-num">฿{{ number_format((float) $order->subtotal, 2) }}</dd>
                @if((float) $order->discount_amount > 0)
                    <dt>ส่วนลด</dt><dd class="nvm-num">-฿{{ number_format((float) $order->discount_amount, 2) }}</dd>
                @endif
                <dt>ค่าจัดส่ง</dt><dd class="nvm-num">{{ (float) $order->shipping_fee > 0 ? '฿'.number_format((float) $order->shipping_fee, 2) : 'ฟรี' }}</dd>
                @if((float) ($order->tax_amount ?? 0) > 0)
                    <dt>ภาษี</dt><dd class="nvm-num">฿{{ number_format((float) $order->tax_amount, 2) }}</dd>
                @endif
                <dt class="nvm-kv__total">รวมทั้งหมด</dt><dd class="nvm-kv__total nvm-num">฿{{ number_format((float) $order->total_amount, 2) }}</dd>
                <dt>ชำระด้วย</dt><dd>{{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}</dd>
                <dt>สถานะการชำระ</dt><dd>{{ $odPaidLabel }}</dd>
            </dl>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-od-ad-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Ship to</small><h2 id="nvm-os-od-ad-h">ที่อยู่จัดส่ง</h2></div></div>
            @if($odShipping)
                <ul class="nvm-os-lines">
                    <li><svg class="nvm-ico"><use href="#nvm-i-user"/></svg><b>{{ $odShipping['name'] }}</b></li>
                    @if($odShipping['phone'])<li><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span class="nvm-num">{{ $odShipping['phone'] }}</span></li>@endif
                    <li><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span>{{ $odShipping['full_address'] ?: trim(implode(' ', array_filter([$odShipping['address'], $odShipping['address_line_2'] ?? null, $odShipping['subdistrict'], $odShipping['district'], $odShipping['province'], $odShipping['postal_code']]))) }}</span></li>
                    @if($odShipping['notes'])<li class="is-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>หมายเหตุ: {{ $odShipping['notes'] }}</span></li>@endif
                </ul>
            @else
                <p class="nvm-desc">สินค้าดิจิทัล — ไม่ต้องจัดส่ง</p>
            @endif
            @if($order->customer_notes)
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span><b>หมายเหตุถึงร้าน:</b> {{ $order->customer_notes }}</span></p>
            @endif
        </section>

        @if($odCanPay)
            <section class="nvm-card nvm-alert" aria-labelledby="nvm-os-od-pay-h">
                <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="56" height="56" loading="lazy">
                <div class="nvm-alert__txt">
                    <b id="nvm-os-od-pay-h">รอชำระเงิน</b>
                    <p>คำสั่งซื้อนี้ยังไม่ได้ชำระ กดเพื่อชำระเงินต่อ</p>
                </div>
                <form class="nvm-w-full" method="POST" action="{{ route('orders.retry-payment', $order->id) }}" x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
                    @csrf
                    <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>ชำระเงินตอนนี้</button>
                </form>
                {{-- 🪙 จ่ายด้วยเหรียญ TPIX (แสดงเมื่อแอดมินเปิดรับ) — ยอดคิดตามราคาจริงจากพูล TPIX DEX --}}
                @if(! $odIsCod && RouteFacade::has('tpix-pay.order') && app(\App\Services\TpixPay\TpixPaySettings::class)->paymentsEnabled())
                    <form class="nvm-w-full" method="POST" action="{{ route('tpix-pay.order', $order->id) }}" x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
                        @csrf
                        <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--block" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>จ่ายด้วยเหรียญ TPIX</button>
                    </form>
                    <x-tpix-price :thb="$order->total_amount" size="lg" />
                @endif
            </section>
        @endif

        @if($order->canBeCancelled())
            <section class="nvm-card nvm-box" id="cancel" aria-labelledby="nvm-os-od-cc-h" x-data="{ reason: @js((string) old('reason', '')), busy: false }">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Cancel</small><h2 id="nvm-os-od-cc-h">ยกเลิกคำสั่งซื้อ</h2></div></div>
                @if($order->payment_status === 'paid')
                    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>คำสั่งซื้อนี้ชำระเงินแล้ว — หากยกเลิก ระบบจะคืนเงินเข้ากระเป๋าเงินอัตโนมัติ</p>
                @endif
                <form method="POST" action="{{ route('orders.cancel', $order->id) }}" class="nvm-form nvm-gap-t"
                      @submit="if (busy || ! confirm(@js($order->payment_status === 'paid' ? 'ยืนยันยกเลิกคำสั่งซื้อ? ระบบจะคืนเงินเข้ากระเป๋าให้อัตโนมัติ' : 'ยืนยันยกเลิกคำสั่งซื้อนี้?'))) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <div class="nvm-field">
                        <label class="nvm-label" for="od-cancel-reason">เหตุผลในการยกเลิก <em>*</em></label>
                        <textarea id="od-cancel-reason" name="reason" rows="3" maxlength="500" required class="nvm-input @error('reason') is-bad @enderror" x-model="reason" placeholder="เช่น สั่งผิด ต้องการเปลี่ยนที่อยู่"></textarea>
                        @error('reason')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="busy || reason.trim().length === 0"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยืนยันยกเลิก</button>
                </form>
            </section>
        @endif
    </aside>
</div>
@endsection

@push('scripts')
<script>
    /**
     * ติดตามพัสดุเรียลไทม์จากขนส่ง (orders.tracking.realtime)
     */
    function nvmOsParcel(url) {
        return {
            loading: false,
            data: null,
            error: '',
            init() { this.load(false); },
            async load(force) {
                if (this.loading) { return; }
                this.loading = true;
                this.error = '';
                try {
                    const res = await fetch(url + (force ? '?refresh=1' : ''), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const d = await res.json().catch(() => null);
                    if (d && d.success) { this.data = d; } else { this.error = (d && d.message) || 'ยังดึงข้อมูลจากขนส่งไม่ได้ ลองใหม่ภายหลัง'; }
                } catch (e) {
                    this.error = 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่';
                } finally {
                    this.loading = false;
                }
            }
        };
    }

    /**
     * แผนที่ไรเดอร์แบบสด — โพลตำแหน่งทุก 15 วินาทีระหว่างงานยังวิ่งอยู่
     * (เซิร์ฟเวอร์ส่งตำแหน่งเฉพาะเมื่อไรเดอร์ยินยอมแชร์และงานยังไม่จบ)
     */
    function nvmOsRiderLive(cfg) {
        const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        const notify = (message, type) => window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));

        return {
            cfg: cfg,
            map: null,
            riderMarker: null,
            live: false,
            statusText: 'กำลังค้นหาตำแหน่งไรเดอร์…',
            updatedAgo: '',
            timer: null,
            canShare: false,
            sharing: false,
            shareBusy: false,
            shareTimer: null,
            init() {
                this.$nextTick(() => {
                    if (! window.nvmOsMap || ! window.nvmOsMap.ready()) { this.statusText = 'โหลดแผนที่ไม่สำเร็จ'; return; }
                    const center = cfg.drop || cfg.pickup || [13.7563, 100.5018];
                    this.map = window.nvmOsMap.create(this.$refs.map, center[0], center[1], 14);
                    if (cfg.drop) { window.nvmOsMap.pin(this.map, cfg.drop[0], cfg.drop[1], 'home'); }
                    if (cfg.pickup) { window.nvmOsMap.pin(this.map, cfg.pickup[0], cfg.pickup[1], 'shop'); }
                    this.poll();
                    this.timer = setInterval(() => this.poll(), 15000);
                });
                // ออกจากหน้า → หยุดโพลและหยุดส่งตำแหน่ง
                window.addEventListener('pagehide', () => { clearInterval(this.timer); this.stopSharingTimer(); });
            },
            placeRider(lat, lng) {
                const ll = [Number(lat), Number(lng)];
                if (! this.riderMarker) {
                    this.riderMarker = window.nvmOsMap.pin(this.map, ll[0], ll[1], 'rider');
                    const pts = [ll];
                    if (cfg.drop) { pts.push(cfg.drop); }
                    this.map.fitBounds(pts, { padding: [40, 40], maxZoom: 16 });
                } else {
                    this.riderMarker.setLatLng(ll);
                }
            },
            stop(message) {
                clearInterval(this.timer);
                this.stopSharingTimer();
                this.live = false;
                this.canShare = false;
                if (message) { this.statusText = message; }
            },
            async poll() {
                try {
                    const res = await fetch(cfg.locationUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const d = await res.json().catch(() => null);
                    if (! res.ok || ! d || ! d.success) {
                        this.stop((d && d.message) || 'ดึงตำแหน่งไรเดอร์ไม่สำเร็จ');
                        return;
                    }
                    if (cfg.mode === 'order') {
                        // API ติดตามของผู้ซื้อ: {job{is_active,status_text}, rider_location{latitude,longitude}, reason_text, customer_sharing, can_share_location}
                        const data = d.data || {};
                        const job = data.job || {};
                        this.statusText = data.reason_text || job.status_text || '';
                        this.canShare = !! data.can_share_location;
                        this.sharing = !! (data.customer_sharing && data.customer_sharing.enabled);
                        if (this.sharing && ! this.shareTimer) { this.startSharingTimer(); }
                        if (data.rider_location && data.rider_location.latitude) {
                            this.placeRider(data.rider_location.latitude, data.rider_location.longitude);
                            this.live = !! data.location_available;
                            this.updatedAgo = data.rider_location.updated_at ? new Date(data.rider_location.updated_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.' : '';
                        } else {
                            this.live = false;
                        }
                        if (job && job.is_active === false) { this.stop(this.statusText || 'การจัดส่งจบแล้ว'); }
                        return;
                    }
                    // สำรอง: endpoint ของลิงก์ติดตาม (token)
                    const loc = d.location || {};
                    this.statusText = d.job_status_text || loc.job_status_text || '';
                    if (loc.latitude && loc.longitude) {
                        this.placeRider(loc.latitude, loc.longitude);
                        this.live = !! loc.available;
                        this.updatedAgo = loc.updated_ago || '';
                        if (! loc.available) { this.statusText += ' · สัญญาณ GPS ของไรเดอร์ขาดช่วง'; }
                    } else {
                        this.live = false;
                        if (loc.reason === 'consent_missing') { this.statusText = 'ไรเดอร์ยังไม่ได้ยินยอมแชร์ตำแหน่ง'; }
                    }
                    if (! d.is_active) { this.stop(this.statusText); }
                } catch (e) {
                    this.live = false;
                }
            },
            // ── แชร์ตำแหน่งของผู้ซื้อ ──
            position() {
                return new Promise((resolve, reject) => {
                    if (! navigator.geolocation) { reject(new Error('no_geo')); return; }
                    navigator.geolocation.getCurrentPosition(
                        (p) => resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude }),
                        () => reject(new Error('denied')),
                        { enableHighAccuracy: true, timeout: 12000, maximumAge: 20000 }
                    );
                });
            },
            async sendShare(share, point) {
                const body = Object.assign({ share: share }, point || {});
                const res = await fetch(cfg.shareUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(body)
                });
                const d = await res.json().catch(() => null);
                if (! res.ok || ! d || ! d.success) { throw new Error((d && d.message) || 'แชร์ตำแหน่งไม่สำเร็จ กรุณาลองใหม่'); }
                return d;
            },
            startSharingTimer() {
                this.stopSharingTimer();
                this.shareTimer = setInterval(async () => {
                    try {
                        const point = await this.position();
                        await this.sendShare(true, point);
                    } catch (e) {
                        // งานจบ/ไม่อนุญาต → หยุดส่งเงียบๆ (GPS ปิดชั่วคราวไม่หยุด)
                        if (e && e.message && e.message !== 'denied' && e.message !== 'no_geo') { this.stopSharingTimer(); this.sharing = false; }
                    }
                }, 30000);
            },
            stopSharingTimer() { clearInterval(this.shareTimer); this.shareTimer = null; },
            async toggleShare() {
                if (this.shareBusy) { return; }
                this.shareBusy = true;
                try {
                    if (this.sharing) {
                        await this.sendShare(false);
                        this.sharing = false;
                        this.stopSharingTimer();
                        notify('หยุดแชร์ตำแหน่งแล้ว', 'success');
                    } else {
                        let point = null;
                        try { point = await this.position(); } catch (e) {
                            notify('เปิดสิทธิ์ตำแหน่ง (GPS) ในเบราว์เซอร์ก่อน แล้วลองใหม่', 'error');
                            return;
                        }
                        await this.sendShare(true, point);
                        this.sharing = true;
                        this.startSharingTimer();
                        notify('ไรเดอร์เห็นตำแหน่งของคุณแล้ว', 'success');
                    }
                } catch (e) {
                    notify(e.message || 'แชร์ตำแหน่งไม่สำเร็จ', 'error');
                } finally {
                    this.shareBusy = false;
                }
            }
        };
    }
</script>
@endpush

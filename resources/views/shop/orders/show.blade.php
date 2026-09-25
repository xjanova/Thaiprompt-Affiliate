{{--
 | รายละเอียดคำสั่งซื้อ (ผู้ซื้อ) — ธีม V4 (user-v4)
 | ข้อมูลจาก OrderController@show: $order (items.product, items.reviews, shippingAddress, shippingProviderRelation, trackingHistory)
 | - ไทม์ไลน์สถานะ · ลิงก์ติดตามพัสดุ · ติดตามพัสดุแบบเรียลไทม์ (orders.tracking.realtime)
 | - ส่งด้วยไรเดอร์: การ์ดไรเดอร์ + ลิงก์หน้าติดตาม + แผนที่ตำแหน่งไรเดอร์แบบสด (โพล taladsod.track.location ด้วย token ของงาน
 |   ซึ่งตรวจสิทธิ์/ความยินยอมแชร์ตำแหน่งของไรเดอร์ที่ฝั่งเซิร์ฟเวอร์แล้ว)
 | - ชำระใหม่ POST orders.retry-payment · ยืนยันรับของ POST orders.confirm-received · ยกเลิก POST orders.cancel {reason}
 --}}
@extends('layouts.user-v4')

@section('title', 'คำสั่งซื้อ #'.$order->order_number)

@php
    $odShipping = \App\Services\Shop\ShopPresenter::shipping($order);
    $odRider = \App\Services\Shop\ShopPresenter::riderSummary($order);
    $odIsCod = $order->isCod();
    $odCanPay = $order->canRetryPayment() && ! $odIsCod;
    $odCancelled = in_array($order->status, ['cancelled', 'refunded'], true);

    // งานไรเดอร์ที่ยังวิ่งอยู่ → แผนที่สด
    //   ใช้ API ติดตามของผู้ซื้อ (session, ตรวจเจ้าของออเดอร์ + ความยินยอมแชร์ตำแหน่งของไรเดอร์) ถ้ามี
    //   ไม่มี → สำรองด้วย endpoint ของลิงก์ติดตาม (token ของงาน)
    $odJob = $order->isRiderDelivery() ? \App\Models\RiderJob::forSource($order)->latest('id')->first() : null;
    $odActiveStatuses = ['accepted', 'picking_up', 'picked_up', 'delivering'];
    $odLive = $odJob && $odJob->isTrackable() && in_array($odJob->status, $odActiveStatuses, true);
    $odStore = $order->store;
    $odHasApi = \Illuminate\Support\Facades\Route::has('taladsod.delivery.rider-location');
    $odMapCfg = null;
    if ($odLive && ($odHasApi || $odJob->tracking_token)) {
        $odMapCfg = [
            'mode' => $odHasApi ? 'order' : 'token',
            'locationUrl' => $odHasApi
                ? route('taladsod.delivery.rider-location', ['source' => 'shop', 'id' => $order->id])
                : route('taladsod.track.location', $odJob->tracking_token),
            'shareUrl' => \Illuminate\Support\Facades\Route::has('taladsod.delivery.share-location')
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
    $odFmt = fn ($d) => $d ? $d->timezone('Asia/Bangkok')->format('d/m/Y H:i') : null;

    $odSteps = [];
    $odSteps[] = ['icon' => 'fa-file-circle-plus', 'title' => 'สร้างคำสั่งซื้อ', 'time' => $odFmt($order->created_at), 'done' => true];
    if ($odIsCod) {
        $odSteps[] = ['icon' => 'fa-money-bill-wave', 'title' => $order->payment_status === 'paid' ? 'ชำระเงินปลายทางแล้ว' : 'ชำระเงินปลายทาง (จ่ายกับไรเดอร์)', 'time' => $odFmt($order->paid_at), 'done' => $order->payment_status === 'paid'];
    } else {
        $odSteps[] = ['icon' => 'fa-wallet', 'title' => $order->paid_at ? 'ชำระเงินแล้ว' : 'รอชำระเงิน', 'time' => $odFmt($order->paid_at), 'done' => (bool) $order->paid_at, 'sub' => $order->payment_reference ? 'อ้างอิง: '.$order->payment_reference : null];
    }
    $odSteps[] = ['icon' => 'fa-box-open', 'title' => 'ร้านกำลังเตรียมสินค้า', 'time' => $odIsPrepared ? $odFmt($odProcessingAt) : null, 'done' => $odIsPrepared];
    if ($order->isRiderDelivery()) {
        $odRiderStarted = $odJob && in_array($odJob->status, ['picked_up', 'delivering', 'delivered', 'completed'], true);
        $odSteps[] = ['icon' => 'fa-motorcycle', 'title' => $odRider['status_label'] ?? 'รอร้านเรียกไรเดอร์', 'time' => $odJob ? $odFmt($odJob->accepted_at) : null, 'done' => $odRiderStarted, 'now' => $odLive];
    } else {
        $odSteps[] = ['icon' => 'fa-truck-fast', 'title' => $order->shipped_at ? 'จัดส่งสินค้าแล้ว' : 'รอจัดส่ง', 'time' => $odFmt($order->shipped_at), 'done' => (bool) $order->shipped_at,
            'sub' => $order->tracking_number ? 'เลขพัสดุ '.$order->tracking_number.($order->shippingProviderRelation ? ' · '.$order->shippingProviderRelation->name : '') : null];
        foreach ($odTransit as $u) {
            $odSteps[] = ['icon' => 'fa-route', 'title' => $u->title ?: ($odTransitLabels[$u->status] ?? $u->status), 'time' => $odFmt($u->tracked_at), 'done' => true, 'sub' => trim(($u->description ?? '').' '.($u->location ? '· '.$u->location : '')) ?: null, 'small' => true];
        }
    }
    $odSteps[] = ['icon' => 'fa-house-circle-check', 'title' => 'ส่งถึงแล้ว', 'time' => $odFmt($order->delivered_at), 'done' => (bool) $order->delivered_at];
    $odSteps[] = ['icon' => 'fa-star', 'title' => 'สำเร็จ', 'time' => $order->status === 'completed' ? $odFmt($order->completed_at ?? $order->updated_at) : null, 'done' => $order->status === 'completed'];

    $odTone = match ((string) $order->status) {
        'pending' => 'color:var(--deep2); background:var(--a2soft);',
        'delivered', 'completed' => 'color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467));',
        'cancelled', 'refunded' => 'color:var(--on-accent, #fff); background:color-mix(in srgb, var(--ink2) 80%, transparent);',
        default => 'color:var(--deep1); background:var(--a1soft);',
    };
@endphp

@section('content')
<x-theme-v4.shop-kit />
@if($odLive)
    <x-theme-v4.leaflet />
@endif

<div style="max-width:1140px; margin:0 auto; display:flex; flex-direction:column; gap:16px;">
    <nav class="sf-breadcrumb" aria-label="เส้นทาง">
        <a href="{{ route('orders.index') }}">คำสั่งซื้อของฉัน</a>
        <span aria-hidden="true">/</span>
        <span style="color:var(--ink); font-weight:600;">#{{ $order->order_number }}</span>
    </nav>

    <div class="tp-card" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; background:linear-gradient(135deg, var(--a1soft), var(--a2soft));">
        <div style="min-width:0;">
            <div class="tp-muted" style="font-size:12.5px;">คำสั่งซื้อ</div>
            <h1 class="tp-num" style="margin:2px 0 6px; font-size:clamp(20px, 3.6vw, 28px); font-weight:800; color:var(--ink); overflow-wrap:anywhere;">#{{ $order->order_number }}</h1>
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <span class="tp-pill" style="{{ $odTone }} padding:6px 12px;">{{ $order->status_label }}</span>
                <span class="tp-muted" style="font-size:12.5px;">{{ $odFmt($order->created_at) }}</span>
                @if($order->isRiderDelivery())
                    <span class="tp-pill tp-pill-soft" style="padding:6px 12px;"><i class="fas fa-motorcycle"></i> ส่งด่วนด้วยไรเดอร์</span>
                @endif
            </div>
        </div>
        <div style="text-align:right;">
            <div class="tp-muted" style="font-size:12.5px;">ยอดรวมทั้งหมด</div>
            <div class="tp-num" style="font-size:clamp(24px, 4vw, 32px); font-weight:800; color:var(--deep1);">฿{{ number_format((float) $order->total_amount, 2) }}</div>
        </div>
    </div>

    @if(session('error'))
        <div class="sf-note sf-note-err" role="alert"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="sf-note sf-note-ok" role="status"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
    @endif

    <div class="sf-2col">
        <div class="sf-stack">

            {{-- ── ไรเดอร์ (ส่งด่วน) ── --}}
            @if($odRider)
                <div class="tp-card sf-stack" style="gap:12px;">
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <span class="tp-tile" style="width:48px; height:48px; font-size:20px; background:linear-gradient(135deg, var(--accent2), var(--deep2));"><i class="fas fa-motorcycle"></i></span>
                        <div style="flex:1; min-width:200px;">
                            <div style="font-weight:800; color:var(--ink);">{{ $odRider['status_label'] ?? 'รอร้านเรียกไรเดอร์' }}</div>
                            @if(! empty($odRider['rider']))
                                <div class="tp-muted" style="font-size:12.5px;">
                                    {{ $odRider['rider']['name'] ?? 'ไรเดอร์' }}
                                    @if(! empty($odRider['rider']['vehicle_plate'])) · ทะเบียน {{ $odRider['rider']['vehicle_plate'] }} @endif
                                </div>
                            @elseif(($odRider['status'] ?? '') === 'not_requested')
                                <div class="tp-muted" style="font-size:12.5px;">ร้านจะเรียกไรเดอร์เมื่อเตรียมสินค้าเสร็จ</div>
                            @endif
                        </div>
                        @if(! empty($odRider['rider']['phone']))
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $odRider['rider']['phone']) }}" class="tp-btn" style="text-decoration:none; height:44px;"><i class="fas fa-phone"></i> โทรหาไรเดอร์</a>
                        @endif
                        @if(! empty($odRider['tracking_url']))
                            <a href="{{ $odRider['tracking_url'] }}" class="sf-btn3d is-alt" style="min-height:44px;" target="_blank" rel="noopener"><i class="fas fa-location-arrow"></i> หน้าติดตามไรเดอร์</a>
                        @endif
                    </div>

                    @if($odLive)
                        <div x-data="tpRiderLive({{ \Illuminate\Support\Js::from($odMapCfg) }})">
                            <div id="od-rider-map" class="sf-map" style="height:300px;"></div>
                            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px; margin-top:8px; font-size:12.5px;">
                                <span class="tp-muted"><i class="fas fa-circle" :style="live ? 'color:var(--sf-ok, #4f9e7e)' : 'color:var(--ink2)'"></i> <span x-text="statusText"></span></span>
                                <span class="tp-muted" x-show="updatedAgo" x-text="'อัปเดต ' + updatedAgo"></span>
                            </div>

                            {{-- ผู้ซื้อเลือกแชร์ตำแหน่งตัวเองให้ไรเดอร์ (หยุดเองเมื่อส่งเสร็จ/ยกเลิก) --}}
                            <template x-if="cfg.shareUrl && canShare">
                                <div style="margin-top:12px; padding:12px 14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm); display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                    <span style="flex:1; min-width:200px;">
                                        <strong style="display:block; color:var(--ink); font-size:13.5px;"><i class="fas fa-location-crosshairs" style="color:var(--deep1);"></i> แชร์ตำแหน่งของฉันให้ไรเดอร์</strong>
                                        <span class="tp-muted" style="font-size:12px;" x-text="sharing ? 'ไรเดอร์เห็นตำแหน่งของคุณระหว่างมาส่ง · หยุดแชร์เองเมื่อส่งเสร็จ' : 'ช่วยให้ไรเดอร์หาคุณเจอง่ายขึ้น (ส่งเฉพาะตอนเปิดหน้านี้)'"></span>
                                    </span>
                                    <button type="button" class="tp-btn" :class="sharing && 'tp-btn-primary'" style="height:44px;" @click="toggleShare()" :disabled="shareBusy">
                                        <i class="fas" :class="shareBusy ? 'fa-spinner fa-spin' : (sharing ? 'fa-toggle-on' : 'fa-toggle-off')"></i>
                                        <span x-text="sharing ? 'กำลังแชร์ — หยุด' : 'เปิดแชร์ตำแหน่ง'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── ไทม์ไลน์ ── --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-timeline" style="color:var(--deep1);"></i> สถานะคำสั่งซื้อ</div>
                @if($odCancelled)
                    <div class="sf-note sf-note-err" style="margin-bottom:14px;">
                        <strong>{{ $order->status === 'refunded' ? 'คืนเงินแล้ว' : 'ยกเลิกคำสั่งซื้อแล้ว' }}</strong>
                        @if($order->cancelled_at) · {{ $odFmt($order->cancelled_at) }} @endif
                        @if($order->cancellation_reason)<br>เหตุผล: {{ $order->cancellation_reason }}@endif
                    </div>
                @endif
                <ol class="sf-tl">
                    @foreach($odSteps as $i => $step)
                        @php $odNext = ! $step['done'] && ($i === 0 || $odSteps[$i - 1]['done']) && ! $odCancelled; @endphp
                        <li class="sf-tl-item {{ $step['done'] ? '' : 'is-todo' }} {{ ! empty($step['now']) || $odNext ? 'is-now' : '' }}">
                            <span class="sf-tl-dot" style="{{ ! empty($step['small']) ? 'width:32px; height:32px; margin:4px;' : '' }}"><i class="fas {{ $step['icon'] }}"></i></span>
                            <div style="min-width:0; padding-top:8px;">
                                <div style="font-weight:{{ $step['done'] ? 800 : 600 }}; color:{{ $step['done'] ? 'var(--ink)' : 'var(--ink2)' }}; font-size:{{ ! empty($step['small']) ? '13px' : '14px' }};">{{ $step['title'] }}</div>
                                @if(! empty($step['time']))<div class="tp-muted tp-num" style="font-size:12px;">{{ $step['time'] }}</div>@endif
                                @if(! empty($step['sub']))<div class="tp-muted" style="font-size:12px; overflow-wrap:anywhere;">{{ $step['sub'] }}</div>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>

                @if($odTrackingUrl)
                    <a href="{{ $odTrackingUrl }}" target="_blank" rel="noopener noreferrer" class="tp-btn" style="text-decoration:none; height:44px;"><i class="fas fa-up-right-from-square"></i> ติดตามพัสดุที่เว็บขนส่ง</a>
                @endif
                @if($order->status === 'delivered')
                    <form method="POST" action="{{ route('orders.confirm-received', $order->id) }}" style="margin:12px 0 0;" onsubmit="return confirm(@js('ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?'))">
                        @csrf
                        <button type="submit" class="sf-btn3d"><i class="fas fa-check"></i> ยืนยันว่าได้รับสินค้าแล้ว</button>
                    </form>
                @endif
            </div>

            {{-- ── ติดตามพัสดุเรียลไทม์ (มีเลขพัสดุ) ── --}}
            @if($order->tracking_number && in_array($order->status, ['shipped', 'in_transit', 'out_for_delivery', 'delivered'], true))
                <div class="tp-card sf-stack" style="gap:10px;" x-data="tpParcelTracking(@js(route('orders.tracking.realtime', $order->id)))">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div class="tp-section-h"><i class="fas fa-map-location-dot" style="color:var(--deep1);"></i> ติดตามพัสดุเรียลไทม์</div>
                        <button type="button" class="tp-icon-btn" @click="load(true)" :disabled="loading" aria-label="รีเฟรช"><i class="fas fa-rotate" :class="loading && 'fa-spin'"></i></button>
                    </div>
                    <p x-show="loading && !data" class="tp-muted" style="margin:0; font-size:13px;"><i class="fas fa-spinner fa-spin"></i> กำลังดึงข้อมูลจากขนส่ง...</p>
                    <p x-show="error" x-cloak x-text="error" class="sf-note sf-note-err" style="margin:0;"></p>
                    <template x-if="data">
                        <div class="sf-stack" style="gap:10px;">
                            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                                <span class="tp-pill tp-pill-gold" x-text="data.current_status_label || 'กำลังตรวจสอบ'"></span>
                                <span class="tp-muted" style="font-size:12.5px;" x-text="(data.provider && data.provider.name) ? data.provider.name : ''"></span>
                            </div>
                            <template x-if="data.carrier_data && data.carrier_data.message">
                                <div class="sf-note sf-note-warn" style="font-size:12.5px;" x-text="data.carrier_data.message"></div>
                            </template>
                            <ol class="sf-tl" style="max-height:280px; overflow-y:auto;">
                                <template x-for="(ev, k) in (data.timeline || [])" :key="k">
                                    <li class="sf-tl-item">
                                        <span class="sf-tl-dot" style="width:28px; height:28px; margin:6px;"><i class="fas fa-circle" style="font-size:8px;"></i></span>
                                        <div style="padding-top:6px; min-width:0;">
                                            <div style="font-weight:700; font-size:13px; color:var(--ink);" x-text="ev.title"></div>
                                            <div class="tp-muted" style="font-size:12px;" x-show="ev.description" x-text="ev.description"></div>
                                            <div class="tp-muted" style="font-size:12px;" x-show="ev.location" x-text="ev.location"></div>
                                            <div class="tp-muted tp-num" style="font-size:11.5px;" x-text="ev.timestamp_display || ev.timestamp"></div>
                                        </div>
                                    </li>
                                </template>
                            </ol>
                        </div>
                    </template>
                </div>
            @endif

            {{-- ── รายการสินค้า ── --}}
            <div class="tp-card sf-stack" style="gap:12px;">
                <div class="tp-section-h"><i class="fas fa-bag-shopping" style="color:var(--deep1);"></i> รายการสินค้า ({{ $order->items->count() }})</div>
                @foreach($order->items as $item)
                    @php
                        $odImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image);
                        $odReviewed = $item->relationLoaded('reviews') ? $item->reviews->isNotEmpty() : $item->hasReview();
                    @endphp
                    <div style="display:flex; gap:12px; align-items:flex-start; padding:12px; border-radius:16px; background:var(--surf); box-shadow:var(--raise);">
                        <span class="sf-thumb" style="width:70px; height:70px;">@if($odImg)<img src="{{ $odImg }}" alt="" loading="lazy">@else 📦 @endif</span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; color:var(--ink); overflow-wrap:anywhere;">{{ $item->product_name }}</div>
                            @if(is_array($item->product_attributes) && $item->product_attributes !== [])
                                <div class="tp-muted" style="font-size:12px;">
                                    @foreach($item->product_attributes as $ak => $av)
                                        @if(is_scalar($av)){{ $ak }}: {{ $av }}@if(! $loop->last), @endif @endif
                                    @endforeach
                                </div>
                            @endif
                            <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-top:4px;">
                                <span class="tp-muted tp-num" style="font-size:12.5px;">{{ (int) $item->quantity }} × ฿{{ number_format((float) $item->unit_price, 2) }}</span>
                                <strong class="tp-num" style="color:var(--deep1);">฿{{ number_format((float) $item->total, 2) }}</strong>
                            </div>
                            @if(in_array($order->status, ['delivered', 'completed'], true))
                                <div style="margin-top:8px;">
                                    @if($odReviewed)
                                        <span class="tp-pill tp-pill-soft"><i class="fas fa-circle-check"></i> รีวิวแล้ว</span>
                                    @else
                                        <a href="{{ route('orders.review.form', [$order->id, $item->id]) }}" class="tp-btn tp-btn-sm tp-btn-primary" style="text-decoration:none;"><i class="fas fa-star"></i> รีวิวสินค้า</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── สรุป / ที่อยู่ / ชำระ / ยกเลิก ── --}}
        <aside class="sf-sticky sf-stack">
            <div class="tp-card sf-stack" style="gap:10px;">
                <div class="tp-section-h">สรุปคำสั่งซื้อ</div>
                <div class="sf-row"><span>ยอดรวมสินค้า</span><strong class="tp-num">฿{{ number_format((float) $order->subtotal, 2) }}</strong></div>
                @if((float) $order->discount_amount > 0)
                    <div class="sf-row"><span>ส่วนลด</span><strong class="tp-num" style="color:var(--sf-ok, #4f9e7e);">-฿{{ number_format((float) $order->discount_amount, 2) }}</strong></div>
                @endif
                <div class="sf-row"><span>ค่าจัดส่ง</span><strong class="tp-num">{{ (float) $order->shipping_fee > 0 ? '฿'.number_format((float) $order->shipping_fee, 2) : 'ฟรี' }}</strong></div>
                @if((float) ($order->tax_amount ?? 0) > 0)
                    <div class="sf-row"><span>ภาษี</span><strong class="tp-num">฿{{ number_format((float) $order->tax_amount, 2) }}</strong></div>
                @endif
                <div class="sf-total"><span style="font-weight:800; color:var(--ink);">รวมทั้งหมด</span><span class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</span></div>
                <div class="sf-row" style="font-size:12.5px;"><span>ชำระด้วย</span><strong>{{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}</strong></div>
                <div class="sf-row" style="font-size:12.5px;"><span>สถานะการชำระ</span><strong>{{ $odIsCod && $order->payment_status !== 'paid' ? 'จ่ายเมื่อรับของ' : \App\Services\Shop\ShopPresenter::paymentStatusLabel($order->payment_status) }}</strong></div>
            </div>

            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-location-dot" style="color:var(--deep1);"></i> ที่อยู่จัดส่ง</div>
                @if($odShipping)
                    <div style="font-size:13.5px; line-height:1.65; color:var(--ink2); overflow-wrap:anywhere;">
                        <strong style="color:var(--ink);">{{ $odShipping['name'] }}</strong><br>
                        {{ $odShipping['phone'] }}<br>
                        {{ $odShipping['full_address'] ?: trim(implode(' ', array_filter([$odShipping['address'], $odShipping['address_line_2'], $odShipping['subdistrict'], $odShipping['district'], $odShipping['province'], $odShipping['postal_code']]))) }}
                        @if($odShipping['notes'])<br><span style="font-size:12.5px;">หมายเหตุ: {{ $odShipping['notes'] }}</span>@endif
                    </div>
                @else
                    <div class="tp-muted" style="font-size:13px;"><i class="fas fa-cloud-arrow-down"></i> สินค้าดิจิทัล — ไม่ต้องจัดส่ง</div>
                @endif
                @if($order->customer_notes)
                    <div class="sf-note sf-note-info" style="margin-top:10px; font-size:12.5px; overflow-wrap:anywhere;"><strong>หมายเหตุถึงร้าน:</strong> {{ $order->customer_notes }}</div>
                @endif
            </div>

            @if($odCanPay)
                <div class="tp-card sf-stack" style="gap:10px;">
                    <div class="tp-section-h"><i class="fas fa-hourglass-half" style="color:var(--deep2);"></i> รอชำระเงิน</div>
                    <p class="tp-muted" style="margin:0; font-size:13px;">คำสั่งซื้อนี้ยังไม่ได้ชำระ กดเพื่อชำระเงินต่อ</p>
                    <form method="POST" action="{{ route('orders.retry-payment', $order->id) }}" style="margin:0;" x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
                        @csrf
                        <button type="submit" class="sf-btn3d is-block" :disabled="busy"><i class="fas fa-credit-card"></i> ชำระเงินตอนนี้</button>
                    </form>
                </div>
            @endif

            @if($order->canBeCancelled())
                <div class="tp-card sf-stack" id="cancel" style="gap:10px;" x-data="{ reason: @js(old('reason', '')), busy: false }">
                    <div class="tp-section-h" style="color:var(--sf-sale, #e0564f);"><i class="fas fa-ban"></i> ยกเลิกคำสั่งซื้อ</div>
                    @if($order->payment_status === 'paid')
                        <div class="sf-note sf-note-warn" style="font-size:12.5px;">คำสั่งซื้อนี้ชำระเงินแล้ว — หากยกเลิก ระบบจะคืนเงินเข้ากระเป๋าเงินอัตโนมัติ</div>
                    @endif
                    <form method="POST" action="{{ route('orders.cancel', $order->id) }}" class="sf-stack" style="gap:10px;"
                          @submit="if (busy || !confirm(@js($order->payment_status === 'paid' ? 'ยืนยันยกเลิกคำสั่งซื้อ? ระบบจะคืนเงินเข้ากระเป๋าให้อัตโนมัติ' : 'ยืนยันยกเลิกคำสั่งซื้อนี้?'))) { $event.preventDefault(); return; } busy = true">
                        @csrf
                        <label for="od-cancel-reason" class="tp-muted" style="font-size:12.5px; font-weight:600;">เหตุผลในการยกเลิก <span style="color:var(--sf-sale, #e0564f);">*</span></label>
                        <textarea id="od-cancel-reason" name="reason" rows="3" maxlength="500" required class="tp-input" x-model="reason" placeholder="เช่น สั่งผิด ต้องการเปลี่ยนที่อยู่"></textarea>
                        @error('reason')
                            <div class="sf-note sf-note-err" style="padding:8px 12px;">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="tp-btn" style="height:46px; color:var(--sf-sale, #e0564f);" :disabled="busy || reason.trim().length === 0"><i class="fas fa-ban"></i> ยืนยันยกเลิก</button>
                    </form>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /**
     * ติดตามพัสดุเรียลไทม์จากขนส่ง (orders.tracking.realtime)
     */
    function tpParcelTracking(url) {
        return {
            loading: false,
            data: null,
            error: '',
            init() { this.load(false); },
            async load(force) {
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
     * แผนที่ไรเดอร์แบบสด — โพลตำแหน่งทุก 10 วินาทีระหว่างงานยังวิ่งอยู่
     * (เซิร์ฟเวอร์ส่งตำแหน่งเฉพาะเมื่อไรเดอร์ยินยอมแชร์และงานยังไม่จบ)
     */
    function tpRiderLive(cfg) {
        return {
            cfg: cfg,
            map: null,
            riderMarker: null,
            live: false,
            statusText: 'กำลังค้นหาตำแหน่งไรเดอร์...',
            updatedAgo: '',
            timer: null,
            canShare: false,
            sharing: false,
            shareBusy: false,
            shareTimer: null,
            init() {
                this.$nextTick(() => {
                    if (!window.tpMap || !window.tpMap.ready()) { this.statusText = 'โหลดแผนที่ไม่สำเร็จ'; return; }
                    const center = cfg.drop || cfg.pickup || [13.7563, 100.5018];
                    this.map = window.tpMap.create(document.getElementById('od-rider-map'), center[0], center[1], 14);
                    if (cfg.drop) { window.tpMap.pin(this.map, cfg.drop[0], cfg.drop[1], 'home'); }
                    if (cfg.pickup) { window.tpMap.pin(this.map, cfg.pickup[0], cfg.pickup[1], 'shop'); }
                    this.poll();
                    this.timer = setInterval(() => this.poll(), 15000);
                });
                window.addEventListener('beforeunload', () => { clearInterval(this.shareTimer); });
            },
            placeRider(lat, lng) {
                const ll = [Number(lat), Number(lng)];
                if (!this.riderMarker) {
                    this.riderMarker = window.tpMap.pin(this.map, ll[0], ll[1], 'rider');
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
                    if (!res.ok || !d || !d.success) {
                        this.stop((d && d.message) || 'ดึงตำแหน่งไรเดอร์ไม่สำเร็จ');
                        return;
                    }
                    if (cfg.mode === 'order') {
                        // API ติดตามของผู้ซื้อ: {job{is_active,status_text}, rider_location{latitude,longitude,is_stale}, reason_text, customer_sharing, can_share_location}
                        const data = d.data || {};
                        const job = data.job || {};
                        this.statusText = data.reason_text || job.status_text || '';
                        this.canShare = !!data.can_share_location;
                        this.sharing = !!(data.customer_sharing && data.customer_sharing.enabled);
                        if (this.sharing && !this.shareTimer) { this.startSharingTimer(); }
                        if (data.rider_location && data.rider_location.latitude) {
                            this.placeRider(data.rider_location.latitude, data.rider_location.longitude);
                            this.live = !!data.location_available;
                            this.updatedAgo = data.rider_location.updated_at ? new Date(data.rider_location.updated_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.' : '';
                            if (!this.statusText) { this.statusText = job.status_text || ''; }
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
                        this.live = !!loc.available;
                        this.updatedAgo = loc.updated_ago || '';
                        if (!loc.available) { this.statusText += ' · สัญญาณ GPS ของไรเดอร์ขาดช่วง'; }
                    } else {
                        this.live = false;
                        if (loc.reason === 'consent_missing') { this.statusText = 'ไรเดอร์ยังไม่ได้ยินยอมแชร์ตำแหน่ง'; }
                    }
                    if (!d.is_active) { this.stop(this.statusText); }
                } catch (e) {
                    this.live = false;
                }
            },
            // ── แชร์ตำแหน่งของผู้ซื้อ ──
            position() {
                return new Promise((resolve, reject) => {
                    if (!navigator.geolocation) { reject(new Error('no_geo')); return; }
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
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.tpShop.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(body)
                });
                const d = await res.json().catch(() => null);
                if (!res.ok || !d || !d.success) { throw new Error((d && d.message) || 'แชร์ตำแหน่งไม่สำเร็จ กรุณาลองใหม่'); }
                return d;
            },
            startSharingTimer() {
                this.stopSharingTimer();
                this.shareTimer = setInterval(async () => {
                    try {
                        const point = await this.position();
                        await this.sendShare(true, point);
                    } catch (e) {
                        // งานจบ/ไม่อนุญาต GPS → หยุดส่งเงียบๆ
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
                        window.tpShop.notify('หยุดแชร์ตำแหน่งแล้ว', 'success');
                    } else {
                        let point = null;
                        try { point = await this.position(); } catch (e) {
                            window.tpShop.notify('เปิดสิทธิ์ตำแหน่ง (GPS) ในเบราว์เซอร์ก่อน แล้วลองใหม่', 'error');
                            return;
                        }
                        await this.sendShare(true, point);
                        this.sharing = true;
                        this.startSharingTimer();
                        window.tpShop.notify('ไรเดอร์เห็นตำแหน่งของคุณแล้ว', 'success');
                    }
                } catch (e) {
                    window.tpShop.notify(e.message || 'แชร์ตำแหน่งไม่สำเร็จ', 'error');
                } finally {
                    this.shareBusy = false;
                }
            }
        };
    }
</script>
@endpush

{{--
 | รายละเอียดออเดอร์ตลาดสด (ผู้ซื้อ — taladsod.orders.show) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@orderDetail
 | ตัวแปร: $order (seller, listing, riderJob.rider, items), $allowedActions (ของผู้ซื้อ: confirm|cancel), $canReview
 | ฟอร์ม: PUT taladsod.orders.confirm {buyer_rating?, buyer_review?, rider_rating?} · PUT taladsod.orders.cancel {reason?}
 |        POST taladsod.orders.review {buyer_rating*, buyer_review, rider_rating, rider_review}
 | สด: poll GET taladsod.orders.status-json (20 วิ — สถานะเปลี่ยน = โหลดหน้าใหม่)
 |     ส่งด้วยไรเดอร์: poll GET taladsod.delivery.rider-location (15 วิ) + แชร์ตำแหน่งของฉัน POST taladsod.delivery.share-location {share, latitude?, longitude?}
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ออเดอร์ #'.$order->order_number.' · ตลาดสด')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $rw = \App\Support\RiderWebUi::class;
    $lines = $order->lineItems();
    $status = (string) $order->order_status;
    $tone = $ui::orderTone($status);
    $steps = $ui::orderSteps($order);
    $job = $order->riderJob;
    $isRider = $order->delivery_type === 'rider';
    $terminal = in_array($status, [\App\Models\FreshMarketOrder::STATUS_COMPLETED, \App\Models\FreshMarketOrder::STATUS_CANCELLED], true);
    $showLive = $isRider && ! $terminal && $status !== \App\Models\FreshMarketOrder::STATUS_DELIVERY_FAILED;
    $contactVisible = ! in_array($status, [\App\Models\FreshMarketOrder::STATUS_PENDING, \App\Models\FreshMarketOrder::STATUS_CANCELLED], true);
    $shop = $order->seller;
    // จุดรับของที่ผู้ซื้อเห็นได้: ร้านเคลื่อนที่ = เฉพาะออเดอร์ที่ยังดำเนินอยู่และร้านเปิดอยู่ (ไม่เปิดเผยตำแหน่งล่าสุด/บ้านของร้าน)
    $pickupPoint = $order->buyerPickupPoint();
    $trackingUrl = null;
    try {
        $trackingUrl = $job ? $job->tracking_url : null;
    } catch (\Throwable $e) {
        $trackingUrl = null;
    }
    $hasBuyerPoint = $rw::validPoint($order->buyer_latitude, $order->buyer_longitude);

    // สถานะไรเดอร์ตอนเปิดหน้า (ข้อมูลชุดเดียวกับ API ที่ poll) — กันหน้าแสดง "รอร้านกดพร้อมส่ง"
    // + สวิตช์แชร์ปิด ระหว่างรอผลรอบแรก ทั้งที่ไรเดอร์กำลังมาส่งอยู่แล้ว
    $initialRider = null;
    if ($showLive) {
        try {
            $initialRider = app(\App\Services\DeliveryTrackingService::class)->riderLocation($order, 'fresh-market');
        } catch (\Throwable $e) {
            $initialRider = null;
        }
    }

    $liveCfg = [
        'statusUrl' => route('taladsod.orders.status-json', $order),
        'status' => $status,
        'updatedAt' => $order->updated_at?->toIso8601String(),
        'watch' => ! $terminal,
        'rider' => $showLive ? [
            'locationUrl' => route('taladsod.delivery.rider-location', ['fresh-market', $order->id]),
            'shareUrl' => route('taladsod.delivery.share-location', ['fresh-market', $order->id]),
            'dropoff' => $hasBuyerPoint ? ['lat' => (float) $order->buyer_latitude, 'lng' => (float) $order->buyer_longitude] : null,
            'initial' => $initialRider,
        ] : null,
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
@if($showLive)
    <x-theme-v4.leaflet />
@endif
<x-theme-v4.public-header active="orders" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;"
      x-data="tsOrderLive({{ \Illuminate\Support\Js::from($liveCfg) }})">
    <div class="sf-wrap" style="max-width:1080px;">
        @include('taladsod.partials.nav', ['active' => 'orders'])

        <nav class="sf-breadcrumb" aria-label="เส้นทางหน้า" style="margin:4px 0 12px;">
            <a href="{{ route('taladsod.orders') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> ออเดอร์ของฉัน</a>
        </nav>

        {{-- ════════ หัวออเดอร์ + ขั้นตอน ════════ --}}
        <section class="tp-card" style="padding:20px;">
            <div class="ts-row" style="justify-content:space-between; gap:12px;">
                <div style="min-width:0;">
                    <div class="sf-kicker">ออเดอร์ตลาดสด</div>
                    <h1 class="ts-h1" style="font-size:clamp(20px,4vw,27px);">#{{ $order->order_number }}</h1>
                    <div class="ts-muted ts-small" style="margin-top:4px;">สั่งเมื่อ {{ $ui::date($order->created_at) }} · {{ $ui::deliveryLabel($order->delivery_type) }}</div>
                </div>
                <span class="ts-pill solid ts-tone-{{ $tone }}" style="font-size:13px; padding:9px 14px;"><i class="fas {{ $ui::orderIcon($status) }}" aria-hidden="true"></i> {{ $order->status_label }}</span>
            </div>

            <ol class="sf-tl" style="margin-top:18px;">
                @foreach($steps as $step)
                    <li class="sf-tl-item {{ $step['state'] === 'todo' ? 'is-todo' : '' }} {{ $step['state'] === 'now' ? 'is-now' : '' }}">
                        <span class="sf-tl-dot" @if($step['state'] === 'stopped') style="background:linear-gradient(135deg, var(--ts-bad), color-mix(in srgb, var(--ts-bad) 70%, var(--ink)));" @endif>
                            <i class="fas {{ $step['state'] === 'stopped' ? 'fa-xmark' : $step['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <div style="padding-top:8px; min-width:0;">
                            <b style="font-size:14px; color:{{ $step['state'] === 'todo' ? 'var(--ink2)' : 'var(--ink)' }};">{{ $step['label'] }}</b>
                            @if($step['at'])
                                <span class="ts-muted ts-small"> · {{ $step['at'] }}</span>
                            @endif
                            @if($step['state'] === 'now' && $step['key'] === 'pending')
                                <p class="ts-muted ts-small" style="margin:3px 0 0;">รอร้านกดรับออเดอร์ — ถ้าร้านไม่รับภายในเวลาที่กำหนด ระบบยกเลิกและคืนเงินให้อัตโนมัติ</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if($status === \App\Models\FreshMarketOrder::STATUS_CANCELLED)
                <div class="sf-note sf-note-err">
                    <b>ออเดอร์ถูกยกเลิก</b>
                    @if($order->cancelled_by) โดย{{ ['buyer' => 'คุณ', 'seller' => 'ร้าน', 'admin' => 'ทีมงาน', 'system' => 'ระบบ'][$order->cancelled_by] ?? $order->cancelled_by }}@endif
                    @if($order->cancel_reason) — {{ $order->cancel_reason }}@endif
                    @if((float) $order->refunded_amount > 0)
                        <div style="margin-top:4px;">คืนเงินเข้ากระเป๋าแล้ว ฿{{ $ui::money($order->refunded_amount) }}</div>
                    @endif
                </div>
            @elseif($status === \App\Models\FreshMarketOrder::STATUS_DELIVERY_FAILED)
                <div class="sf-note sf-note-err"><b>จัดส่งไม่สำเร็จ</b> — ทีมงานกำลังตรวจสอบและจะติดต่อกลับ</div>
            @endif

            <div class="ts-refresh" x-show="changed" x-cloak style="margin-top:12px;">
                <span><i class="fas fa-bell" aria-hidden="true"></i> สถานะออเดอร์อัปเดตแล้ว</span>
                <button type="button" class="ts-btn3d soft sm" x-on:click="window.location.reload()">ดูสถานะล่าสุด</button>
            </div>
        </section>

        <div class="sf-2col" style="margin-top:16px;">
            <div class="sf-stack">
                {{-- ════════ ติดตามไรเดอร์สด ════════ --}}
                @if($showLive)
                    <section class="tp-card ts-stack" aria-labelledby="od-live-h">
                        <div class="ts-row" style="justify-content:space-between;">
                            <h2 id="od-live-h" class="ts-h2"><i class="fas fa-motorcycle" style="color:var(--accent2);" aria-hidden="true"></i> ติดตามไรเดอร์</h2>
                            @if($trackingUrl)
                                <a href="{{ $trackingUrl }}" class="ts-link ts-small" target="_blank" rel="noopener">เปิดหน้าติดตาม <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                            @endif
                        </div>

                        <div class="ts-map" x-ref="map" x-show="riderLoc" x-cloak aria-label="แผนที่ตำแหน่งไรเดอร์"></div>
                        <div class="ts-empty tp-inset" style="border-radius:18px; padding:22px 14px;" x-show="!riderLoc">
                            <span class="tp-tile" style="width:54px; height:54px; border-radius:18px; font-size:22px;"><i class="fas" :class="hasJob ? 'fa-satellite-dish' : 'fa-hourglass-half'" aria-hidden="true"></i></span>
                            <b x-text="reasonText">{{ $job ? $job->status_text : 'ร้านกำลังเตรียมสินค้า' }}</b>
                            <span class="ts-muted ts-small" x-show="!hasJob">ร้านกดพร้อมส่งเมื่อไร ระบบเรียกไรเดอร์ใกล้ร้านให้ทันที</span>
                        </div>

                        <template x-if="rider">
                            <div class="ts-row" style="justify-content:space-between; gap:10px;">
                                <div class="ts-row" style="gap:10px; flex-wrap:nowrap; min-width:0;">
                                    <span class="ts-avatar" style="width:46px; height:46px; border-radius:15px; font-size:18px;"><i class="fas fa-motorcycle" aria-hidden="true"></i></span>
                                    <span style="min-width:0;">
                                        <b style="display:block;" x-text="rider.name"></b>
                                        <span class="ts-muted ts-small" x-text="(rider.vehicle_type_text || '') + (rider.vehicle_plate ? ' · ' + rider.vehicle_plate : '')"></span>
                                    </span>
                                </div>
                                <a :href="'tel:' + (rider.phone || '').replace(/[^0-9+]/g, '')" x-show="rider.phone" class="ts-btn3d sm ts-tone-ok"><i class="fas fa-phone" aria-hidden="true"></i> โทร</a>
                            </div>
                        </template>
                        {{-- GPS ขาดช่วง (is_stale) = จุดสีเหลืองไม่กะพริบ + บอกเหตุผล (ไม่หลอกว่าเป็นตำแหน่งสด) --}}
                        <p class="ts-muted ts-small" style="margin:0;" x-show="riderLoc && updatedAt"><span class="ts-dot" :class="stale ? 'ts-tone-warn' : 'live ts-tone-ok'" style="display:inline-block; margin-right:6px;"></span>ตำแหน่งไรเดอร์อัปเดต <span x-text="window.ts.timeAgo(updatedAt)"></span><span x-show="stale && reasonText" x-text="' · ' + reasonText"></span></p>

                        {{-- แชร์ตำแหน่งของฉัน --}}
                        <div class="tp-inset ts-stack" style="border-radius:18px; padding:14px; gap:10px;">
                            <label class="ts-switch">
                                <input type="checkbox" x-ref="shareBox" :checked="sharing" x-on:change="toggleShare($event.target)" :disabled="shareBusy || !canShare">
                                <span class="track"></span>
                                <span style="font-size:14px; font-weight:700;">แชร์ตำแหน่งของฉันให้ไรเดอร์</span>
                            </label>
                            <p class="ts-help" style="margin:0;">
                                ไรเดอร์จะเห็นตำแหน่งของคุณ<b>เฉพาะออเดอร์นี้</b>ระหว่างมาส่งเท่านั้น ช่วยให้หาคุณเจอเร็วขึ้น
                                ระบบหยุดแชร์อัตโนมัติเมื่อส่งของเสร็จหรือยกเลิก ปิดเองได้ทุกเมื่อ (ต้องเปิดหน้านี้ไว้ระหว่างรอ)
                            </p>
                            <span class="ts-pill ts-tone-ok" x-show="sharing" style="align-self:flex-start;"><span class="ts-dot live"></span> กำลังแชร์ · ส่งล่าสุด <span x-text="lastShared ? window.ts.timeAgo(lastShared) : '—'"></span></span>
                            <span class="ts-help" x-show="!canShare" style="margin:0;">เปิดแชร์ได้เมื่อไรเดอร์รับงานแล้ว</span>
                            <p class="ts-err" x-show="shareError" x-text="shareError" x-cloak></p>
                        </div>
                    </section>
                @endif

                {{-- ════════ รายการสินค้า ════════ --}}
                <section class="tp-card" aria-labelledby="od-items-h">
                    <h2 id="od-items-h" class="ts-h2" style="margin-bottom:6px;"><i class="fas fa-bowl-food" style="color:var(--accent2);" aria-hidden="true"></i> รายการสินค้า</h2>
                    @foreach($lines as $item)
                        <div class="ts-line">
                            <span class="sf-thumb">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="" loading="lazy">
                                @else
                                    <span aria-hidden="true">🥬</span>
                                @endif
                            </span>
                            <div style="flex:1; min-width:0;">
                                <b style="font-size:14px; overflow-wrap:anywhere;">{{ $item->title }}</b>
                                @if($item->optionsLabel() !== '')
                                    <div class="ts-muted ts-small">{{ $item->optionsLabel() }}</div>
                                @endif
                                @if($item->note)
                                    <div class="ts-muted ts-small">“{{ $item->note }}”</div>
                                @endif
                                <div class="ts-muted ts-small">฿{{ $ui::money($item->unit_price) }} × {{ (int) $item->quantity }} {{ $item->unit }}</div>
                            </div>
                            <b class="ts-num">฿{{ $ui::money($item->line_total) }}</b>
                        </div>
                    @endforeach
                    <div style="margin-top:8px;">
                        <div class="ts-kv"><span>ยอดสินค้า</span><b>฿{{ $ui::money($order->total_amount) }}</b></div>
                        <div class="ts-kv"><span>ค่าส่ง{{ $order->delivery_distance_km ? ' ('.$ui::distance($order->delivery_distance_km).')' : '' }}</span><b>{{ $isRider ? '฿'.$ui::money($order->delivery_fee) : 'ฟรี (รับเอง)' }}</b></div>
                        @if((float) $order->cashback_amount > 0)
                            <div class="ts-kv"><span>เงินคืนที่จะได้รับ</span><b style="color:var(--ts-ok);">฿{{ $ui::money($order->cashback_amount) }}</b></div>
                        @endif
                        <div class="sf-total"><span class="ts-muted">รวมทั้งหมด</span><span class="tp-num">฿{{ $ui::money($order->grand_total) }}</span></div>
                    </div>
                </section>

                {{-- ════════ รีวิว ════════ --}}
                @if($order->buyer_rating)
                    <section class="tp-card ts-stack">
                        <h2 class="ts-h2"><i class="fas fa-star" style="color:var(--sf-star, #e6b347);" aria-hidden="true"></i> รีวิวของคุณ</h2>
                        <div class="ts-star-view" style="font-size:20px;">{{ str_repeat('★', (int) $order->buyer_rating) }}<span style="opacity:.25;">{{ str_repeat('★', max(0, 5 - (int) $order->buyer_rating)) }}</span></div>
                        @if($order->buyer_review)
                            <p style="margin:0; font-size:14px; line-height:1.6; overflow-wrap:anywhere;">{{ $order->buyer_review }}</p>
                        @endif
                    </section>
                @elseif($canReview && ! in_array('confirm', $allowedActions, true))
                    <section id="review" class="tp-card" x-data="{ rating: {{ (int) old('buyer_rating', 5) }}, riderRating: {{ (int) old('rider_rating', 0) }} }">
                        <form method="POST" action="{{ route('taladsod.orders.review', $order) }}" class="ts-stack" x-data="{ sending: false }" x-on:submit="sending = true">
                            @csrf
                            <h2 class="ts-h2"><i class="fas fa-star" style="color:var(--sf-star, #e6b347);" aria-hidden="true"></i> ให้คะแนนร้าน</h2>
                            <div class="ts-stars" role="radiogroup" aria-label="คะแนนร้าน">
                                @for($s = 1; $s <= 5; $s++)
                                    <button type="button" :class="rating >= {{ $s }} ? 'is-on' : ''" x-on:click="rating = {{ $s }}" aria-label="{{ $s }} ดาว">★</button>
                                @endfor
                            </div>
                            <input type="hidden" name="buyer_rating" :value="rating">
                            <textarea name="buyer_review" class="tp-input" rows="3" maxlength="1000" placeholder="เล่าให้ร้านฟังหน่อย อร่อยไหม ห่อดีไหม">{{ old('buyer_review') }}</textarea>
                            @if($isRider && $job?->rider)
                                <span class="ts-label" style="margin:0;">ให้คะแนนไรเดอร์ <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></span>
                                <div class="ts-stars" role="radiogroup" aria-label="คะแนนไรเดอร์">
                                    @for($s = 1; $s <= 5; $s++)
                                        <button type="button" :class="riderRating >= {{ $s }} ? 'is-on' : ''" x-on:click="riderRating = {{ $s }}" aria-label="ไรเดอร์ {{ $s }} ดาว">★</button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rider_rating" :value="riderRating || ''" :disabled="!riderRating">
                                <input type="text" name="rider_review" class="tp-input" maxlength="1000" placeholder="ชมไรเดอร์สักนิด (ไม่บังคับ)" value="{{ old('rider_review') }}">
                            @endif
                            <button type="submit" class="ts-btn3d ts-tone-gold" :disabled="sending"><i class="fas fa-paper-plane" aria-hidden="true"></i> ส่งรีวิว</button>
                        </form>
                    </section>
                @endif
            </div>

            {{-- ════════ ข้างขวา: ร้าน / จัดส่ง / จ่ายเงิน / ปุ่ม ════════ --}}
            <aside class="sf-sticky sf-stack" aria-label="ข้อมูลออเดอร์">
                {{-- ปุ่มของผู้ซื้อ --}}
                @if(in_array('confirm', $allowedActions, true) || in_array('cancel', $allowedActions, true))
                    <div class="tp-card ts-stack">
                        @if(in_array('confirm', $allowedActions, true))
                            <p style="margin:0; font-size:13.5px;">ได้รับสินค้าครบแล้ว? กดยืนยันเพื่อปิดออเดอร์และโอนเงินให้ร้าน</p>
                            <button type="button" class="ts-btn3d ts-tone-ok block lg" x-on:click="dialog = 'confirm'"><i class="fas fa-circle-check" aria-hidden="true"></i> ได้รับสินค้าแล้ว</button>
                        @endif
                        @if(in_array('cancel', $allowedActions, true))
                            <button type="button" class="tp-btn ts-btn-ghost ts-tone-bad" x-on:click="dialog = 'cancel'"><i class="fas fa-xmark" aria-hidden="true"></i> ยกเลิกออเดอร์</button>
                        @endif
                    </div>
                @endif

                {{-- ร้าน --}}
                <div class="tp-card ts-stack">
                    <h2 class="ts-h2"><i class="fas fa-store" style="color:var(--accent2);" aria-hidden="true"></i> ร้าน</h2>
                    @if($shop)
                        <a href="{{ route('taladsod.seller', $shop->id) }}" class="ts-row" style="gap:10px; flex-wrap:nowrap; text-decoration:none; color:var(--ink);">
                            <span class="ts-avatar" style="width:44px; height:44px; border-radius:14px; font-size:16px;">{{ $ui::initial($shop->shop_name) }}</span>
                            <span style="min-width:0;">
                                <b style="display:block;">{{ $shop->shop_name }}</b>
                                <span class="ts-muted ts-small">{{ $shop->isMobileShop() ? 'รถเข็น/ตลาดนัด' : 'ร้านประจำที่' }}</span>
                            </span>
                        </a>
                        @if($contactVisible && $shop->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $shop->phone) }}" class="tp-btn"><i class="fas fa-phone" aria-hidden="true"></i> โทรหาร้าน {{ $shop->phone }}</a>
                        @endif
                        @if(! $isRider && $pickupPoint)
                            <div class="tp-inset" style="border-radius:14px; padding:12px;">
                                <b style="font-size:13px;"><i class="fas fa-location-dot" style="color:var(--ts-ok);" aria-hidden="true"></i> จุดรับสินค้า</b>
                                <p class="ts-muted" style="margin:4px 0 8px; font-size:12.5px; overflow-wrap:anywhere;">{{ $pickupPoint['address'] }}</p>
                                @if($rw::validPoint($pickupPoint['latitude'] ?? null, $pickupPoint['longitude'] ?? null))
                                    <a href="{{ $rw::directionsUrl($pickupPoint['latitude'], $pickupPoint['longitude']) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm"><i class="fas fa-diamond-turn-right" aria-hidden="true"></i> นำทางไปร้าน</a>
                                @endif
                            </div>
                        @endif
                    @else
                        <span class="ts-muted">ไม่พบข้อมูลร้าน</span>
                    @endif
                </div>

                {{-- จัดส่ง --}}
                @if($isRider)
                    <div class="tp-card ts-stack">
                        <h2 class="ts-h2"><i class="fas fa-house" style="color:var(--accent2);" aria-hidden="true"></i> ส่งถึง</h2>
                        <p style="margin:0; font-size:13.5px; line-height:1.6; overflow-wrap:anywhere;">{{ $order->delivery_address ?: '—' }}</p>
                        @if($order->delivery_notes)
                            <p class="ts-muted" style="margin:0; font-size:12.5px;">โน้ต: {{ $order->delivery_notes }}</p>
                        @endif
                    </div>
                @elseif($order->delivery_notes)
                    <div class="tp-card"><span class="ts-muted ts-small">โน้ตถึงร้าน:</span> {{ $order->delivery_notes }}</div>
                @endif

                {{-- จ่ายเงิน --}}
                <div class="tp-card ts-stack" style="gap:4px;">
                    <h2 class="ts-h2" style="margin-bottom:6px;"><i class="fas fa-wallet" style="color:var(--accent2);" aria-hidden="true"></i> การชำระเงิน</h2>
                    <div class="ts-kv"><span>วิธีจ่าย</span><b>{{ $ui::paymentShortLabel($order->payment_method) }}</b></div>
                    <div class="ts-kv"><span>สถานะ</span><b>{{ $order->payment_status_label }}</b></div>
                    @if((float) $order->refunded_amount > 0)
                        <div class="ts-kv"><span>คืนเงินแล้ว</span><b style="color:var(--ts-ok);">฿{{ $ui::money($order->refunded_amount) }}</b></div>
                    @endif
                </div>
            </aside>
        </div>
    </div>

    {{-- กล่องยืนยันรับสินค้า (ให้คะแนนได้ในครั้งเดียว) --}}
    @if(in_array('confirm', $allowedActions, true))
        <div class="ts-dialog-bg" x-show="dialog === 'confirm'" x-cloak x-transition.opacity x-on:keydown.escape.window="dialog = null" x-on:click.self="dialog = null">
            <form method="POST" action="{{ route('taladsod.orders.confirm', $order) }}" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="od-confirm-h"
                  x-data="{ rating: 5, riderRating: 0, sending: false }" x-on:submit="sending = true">
                @csrf
                @method('PUT')
                <h2 id="od-confirm-h" class="ts-h2"><i class="fas fa-circle-check" style="color:var(--ts-ok);" aria-hidden="true"></i> ยืนยันว่าได้รับสินค้าแล้ว</h2>
                <p class="ts-muted" style="margin:0; font-size:13.5px;">เมื่อยืนยันแล้ว ระบบจะโอนเงินให้ร้านและปิดออเดอร์ (ยกเลิกไม่ได้)</p>
                <span class="ts-label" style="margin:0;">ให้คะแนนร้าน</span>
                <div class="ts-stars" role="radiogroup" aria-label="คะแนนร้าน">
                    @for($s = 1; $s <= 5; $s++)
                        <button type="button" :class="rating >= {{ $s }} ? 'is-on' : ''" x-on:click="rating = {{ $s }}" aria-label="{{ $s }} ดาว">★</button>
                    @endfor
                </div>
                <input type="hidden" name="buyer_rating" :value="rating">
                <textarea name="buyer_review" class="tp-input" rows="2" maxlength="1000" placeholder="รีวิวสั้นๆ (ไม่บังคับ)"></textarea>
                @if($isRider && $job?->rider)
                    <span class="ts-label" style="margin:0;">ให้คะแนนไรเดอร์ <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></span>
                    <div class="ts-stars" role="radiogroup" aria-label="คะแนนไรเดอร์">
                        @for($s = 1; $s <= 5; $s++)
                            <button type="button" :class="riderRating >= {{ $s }} ? 'is-on' : ''" x-on:click="riderRating = {{ $s }}" aria-label="ไรเดอร์ {{ $s }} ดาว">★</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rider_rating" :value="riderRating || ''" :disabled="!riderRating">
                @endif
                <div class="ts-row" style="justify-content:flex-end;">
                    <button type="button" class="tp-btn" x-on:click="dialog = null">ยังไม่ได้รับ</button>
                    <button type="submit" class="ts-btn3d ts-tone-ok sm" :disabled="sending"><i class="fas fa-check" aria-hidden="true"></i> ยืนยันได้รับสินค้า</button>
                </div>
            </form>
        </div>
    @endif

    {{-- กล่องยืนยันยกเลิก --}}
    @if(in_array('cancel', $allowedActions, true))
        <div class="ts-dialog-bg" x-show="dialog === 'cancel'" x-cloak x-transition.opacity x-on:keydown.escape.window="dialog = null" x-on:click.self="dialog = null">
            <form method="POST" action="{{ route('taladsod.orders.cancel', $order) }}" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="od-cancel-h"
                  x-data="{ sending: false }" x-on:submit="sending = true">
                @csrf
                @method('PUT')
                <h2 id="od-cancel-h" class="ts-h2"><i class="fas fa-circle-xmark" style="color:var(--ts-bad);" aria-hidden="true"></i> ยกเลิกออเดอร์นี้?</h2>
                <p class="ts-muted" style="margin:0; font-size:13.5px;">ยกเลิกได้เฉพาะตอนร้านยังไม่รับออเดอร์ — ถ้าจ่ายด้วยกระเป๋าเงิน ระบบคืนเงินให้ทันที</p>
                <input type="text" name="reason" class="tp-input" maxlength="500" placeholder="เหตุผล (ไม่บังคับ)" aria-label="เหตุผลที่ยกเลิก">
                <div class="ts-row" style="justify-content:flex-end;">
                    <button type="button" class="tp-btn" x-on:click="dialog = null">ไม่ยกเลิก</button>
                    <button type="submit" class="ts-btn3d ts-tone-bad sm" :disabled="sending"><i class="fas fa-xmark" aria-hidden="true"></i> ยืนยันยกเลิก</button>
                </div>
            </form>
        </div>
    @endif
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * รายละเอียดออเดอร์: ตรวจสถานะทุก 20 วิ (เปลี่ยน = แจ้งให้รีเฟรช) + ติดตามไรเดอร์สด 15 วิ + แชร์ตำแหน่งของฉัน
     * หยุดทำงานเมื่อแท็บถูกซ่อน / งานจบ
     */
    window.tsOrderLive = function (cfg) {
        let map = null;
        let riderMarker = null;
        let pickupMarker = null;
        let statusTimer = null;
        let riderTimer = null;
        let shareTimer = null;

        return {
            dialog: null, changed: false,
            rider: null, riderLoc: null, updatedAt: null, stale: false, hasJob: false, reasonText: '',
            sharing: false, canShare: false, shareBusy: false, lastShared: null, shareError: '',

            init() {
                if (cfg.watch) { statusTimer = setTimeout(() => this.pollStatus(), 20000); }
                // วาดสถานะไรเดอร์จากข้อมูลที่เซิร์ฟเวอร์ส่งมากับหน้าก่อน แล้วค่อย poll ต่อ (ไม่กระพริบสถานะผิด)
                const liveRider = cfg.rider && (!cfg.rider.initial || this.applyRider(cfg.rider.initial));
                if (liveRider) { this.pollRider(); }
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        if (cfg.watch && !this.changed) { this.pollStatus(); }
                        if (cfg.rider) { this.pollRider(); }
                    }
                });
            },

            async pollStatus() {
                clearTimeout(statusTimer);
                if (document.visibilityState !== 'visible') { return; }
                const r = await window.ts.get(cfg.statusUrl);
                if (r.ok && r.data && r.data.order_status !== cfg.status) {
                    this.changed = true;
                    // ไม่มีกล่องเปิดอยู่ → โหลดหน้าใหม่ให้เลย
                    if (!this.dialog) { setTimeout(() => window.location.reload(), 1200); }
                    return;
                }
                statusTimer = setTimeout(() => this.pollStatus(), 20000);
            },

            async pollRider() {
                clearTimeout(riderTimer);
                if (document.visibilityState !== 'visible') { return; }
                const r = await window.ts.get(cfg.rider.locationUrl);
                let next = 15000;
                if (r.ok && r.data) {
                    next = Math.max(10, Number(r.data.poll_interval_seconds) || 15) * 1000;
                    if (!this.applyRider(r.data)) { return; }
                }
                riderTimer = setTimeout(() => this.pollRider(), next);
            },

            /** ใส่ข้อมูลไรเดอร์ลงหน้าจอ — คืน false เมื่องานจบแล้ว (หยุด poll) */
            applyRider(d) {
                this.hasJob = !!d.has_rider_job;
                this.rider = d.rider;
                this.reasonText = d.reason_text || (d.job ? d.job.status_text : 'ร้านกำลังเตรียมสินค้า');
                this.canShare = !!d.can_share_location;
                this.sharing = !!(d.customer_sharing && d.customer_sharing.enabled);
                // สวิตช์บนจอตรงกับสถานะจริงเสมอ (ระบบหยุดแชร์เองตอนงานจบ/เปลี่ยนไรเดอร์)
                if (this.$refs.shareBox && !this.shareBusy) { this.$refs.shareBox.checked = this.sharing; }
                this.lastShared = d.customer_sharing ? d.customer_sharing.last_shared_at : this.lastShared;
                if (this.sharing && !shareTimer) { this.startShareLoop(); }
                if (!this.sharing) { this.stopShareLoop(); }
                if (d.rider_location) {
                    this.riderLoc = { lat: Number(d.rider_location.latitude), lng: Number(d.rider_location.longitude) };
                    this.updatedAt = d.rider_location.updated_at;
                    this.stale = !!d.rider_location.is_stale;
                    this.$nextTick(() => this.drawMap(d.pickup));
                } else {
                    this.riderLoc = null;
                }
                if (d.job && !d.job.is_active && d.job.status !== 'pending') {
                    // งานจบแล้ว — ไม่ต้องติดตามต่อ
                    this.stopShareLoop();
                    return false;
                }
                return true;
            },

            drawMap(pickup) {
                if (!this.riderLoc || !window.tpMap || !window.tpMap.ready()) { return; }
                if (!map) {
                    map = window.tpMap.create(this.$refs.map, this.riderLoc.lat, this.riderLoc.lng, 15);
                    if (!map) { return; }
                    riderMarker = window.tpMap.pin(map, this.riderLoc.lat, this.riderLoc.lng, 'rider');
                    const bounds = [[this.riderLoc.lat, this.riderLoc.lng]];
                    if (cfg.rider.dropoff) {
                        window.tpMap.pin(map, cfg.rider.dropoff.lat, cfg.rider.dropoff.lng, 'home');
                        bounds.push([cfg.rider.dropoff.lat, cfg.rider.dropoff.lng]);
                    }
                    if (pickup) {
                        pickupMarker = window.tpMap.pin(map, pickup.latitude, pickup.longitude, 'shop');
                        bounds.push([pickup.latitude, pickup.longitude]);
                    }
                    if (bounds.length > 1) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 }); }
                    [250, 900].forEach((ms) => setTimeout(() => map && map.invalidateSize(), ms));
                    return;
                }
                riderMarker.setLatLng([this.riderLoc.lat, this.riderLoc.lng]);
                if (pickup && pickupMarker) { pickupMarker.setLatLng([pickup.latitude, pickup.longitude]); }
                map.invalidateSize();
            },

            // el = สวิตช์ที่ผู้ใช้กด — ทำไม่สำเร็จต้องตั้งสวิตช์กลับเอง
            // (:checked ไม่วาดใหม่ถ้าค่า sharing ไม่เปลี่ยน → สวิตช์ค้าง "เปิด" ทั้งที่ไม่ได้แชร์)
            async toggleShare(el) {
                const on = !!el.checked;
                this.shareBusy = true;
                this.shareError = '';
                if (!on) {
                    this.stopShareLoop();
                    const r = await window.ts.post(cfg.rider.shareUrl, { share: 0 });
                    this.shareBusy = false;
                    this.sharing = false;
                    el.checked = false;
                    if (!r.ok) { this.shareError = r.message; }
                    return;
                }
                try {
                    const p = await window.ts.geo();
                    const r = await window.ts.post(cfg.rider.shareUrl, { share: 1, latitude: p.lat, longitude: p.lng });
                    if (r.ok) {
                        this.sharing = true;
                        this.lastShared = new Date().toISOString();
                        this.startShareLoop();
                        window.ts.notify(r.message, 'success');
                    } else {
                        this.sharing = false;
                        this.shareError = r.message;
                    }
                } catch (e) {
                    this.sharing = false;
                    this.shareError = (e && e.message) || 'หาตำแหน่งไม่ได้';
                } finally {
                    this.shareBusy = false;
                    el.checked = this.sharing;
                }
            },

            startShareLoop() {
                clearInterval(shareTimer);
                shareTimer = setInterval(async () => {
                    if (!this.sharing || document.visibilityState !== 'visible') { return; }
                    try {
                        const p = await window.ts.geo({ maximumAge: 15000 });
                        const r = await window.ts.post(cfg.rider.shareUrl, { share: 1, latitude: p.lat, longitude: p.lng });
                        if (r.ok) {
                            this.lastShared = new Date().toISOString();
                        } else if (r.status === 409) {
                            this.sharing = false;
                            this.stopShareLoop();
                        }
                    } catch (e) {
                        // หาตำแหน่งไม่ได้รอบนี้ — ลองใหม่รอบหน้า
                    }
                }, 30000);
            },

            stopShareLoop() {
                clearInterval(shareTimer);
                shareTimer = null;
            }
        };
    };
</script>
@endpush

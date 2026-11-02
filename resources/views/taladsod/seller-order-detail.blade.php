{{--
 | รายละเอียดออเดอร์ (ฝั่งร้าน — taladsod.seller.orders.show) — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@sellerOrderShow (ออเดอร์ของร้านอื่น = 403)
 | ตัวแปร: $seller, $order (buyer, listing, riderJob.rider, items), $allowedActions (ของร้าน), $actionLabels [action => ป้ายไทย]
 | ฟอร์ม: PUT taladsod.seller.orders.status {action: accept|prepare|ready|handover|cancel, reason (บังคับเมื่อ cancel)} — ยกเลิกมีกล่องยืนยัน
 | ตรวจการเปลี่ยนแปลง: poll GET taladsod.seller.orders.poll ทุก 20 วินาที (มีอัปเดต = ป้ายให้กดรีเฟรช)
 | ความเป็นส่วนตัว: เบอร์ลูกค้าแสดงเฉพาะหลังร้านรับออเดอร์ (ไม่แสดงตอนรอรับ/ยกเลิก)
 --}}
@extends('layouts.user-nova')

@section('title', 'ออเดอร์ #'.$order->order_number.' · ร้านตลาดสด')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $lines = $order->lineItems();
    $status = (string) $order->order_status;
    $steps = $ui::orderSteps($order);
    $job = $order->riderJob;
    $isRider = $order->delivery_type === 'rider';
    $contactVisible = ! in_array($status, [\App\Models\FreshMarketOrder::STATUS_PENDING, \App\Models\FreshMarketOrder::STATUS_CANCELLED], true);
    $buyerPhone = $contactVisible ? ($order->buyer?->phone ?? null) : null;
    $trackingUrl = null;
    try {
        $trackingUrl = $job ? $job->tracking_url : null;
    } catch (\Throwable $e) {
        $trackingUrl = null;
    }
    $history = collect($order->status_history ?? [])->reverse()->values();
    $byLabels = ['buyer' => 'ลูกค้า', 'seller' => 'ร้าน', 'admin' => 'ทีมงาน', 'system' => 'ระบบ', 'rider' => 'ไรเดอร์'];
    $cancelReasons = ['ของหมด', 'ร้านใกล้ปิดแล้ว', 'อยู่นอกพื้นที่ส่ง', 'ลูกค้าขอยกเลิก', 'ติดต่อลูกค้าไม่ได้'];
    $lastUpdatedRaw = \App\Models\FreshMarketOrder::where('seller_id', $seller->id)->max('updated_at');
    $tone = $ui::orderTone($status);

    $cfg = [
        'pollUrl' => route('taladsod.seller.orders.poll'),
        'lastUpdated' => $lastUpdatedRaw ? \Illuminate\Support\Carbon::parse($lastUpdatedRaw)->toIso8601String() : null,
    ];
@endphp

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')

<div class="nvm-stack" x-data="tsSellerOrderDetail({{ \Illuminate\Support\Js::from($cfg) }})">

<section class="nvm-ph" aria-labelledby="nvm-td-sod-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('taladsod.seller.orders') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ออเดอร์ทั้งหมด</a>
            <div class="nvm-eyebrow nvm-latin">Shop order</div>
            <h1 id="nvm-td-sod-h">#{{ $order->order_number }}</h1>
            <p class="nvm-ph__lead">{{ $ui::date($order->created_at) }} · {{ $ui::deliveryLabel($order->delivery_type) }} · {{ $ui::paymentShortLabel($order->payment_method) }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill {{ in_array($tone, ['ok', 'warn', 'bad'], true) ? 'nvm-pill--'.$tone : ($tone === 'gold' ? 'nvm-pill--gold' : 'nvm-pill--glass') }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaOrderIcon($status) }}"/></svg>{{ $order->status_label }}</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset($isRider ? 'images/nova/icons/scooter.webp' : 'images/nova/icons/bag.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'orders'])

<section class="nvm-card nvm-alert nvm-alert--info" x-show="changed" x-cloak role="status">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
    <div class="nvm-alert__txt"><b>มีอัปเดตออเดอร์ใหม่</b><p>กดรีเฟรชเพื่อดูสถานะล่าสุด</p></div>
    <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" x-on:click="window.location.reload()">รีเฟรช</button>
</section>

{{-- ════════ ปุ่มจัดการ + ไทม์ไลน์ ════════ --}}
<section class="nvm-card nvm-box" aria-labelledby="sod-flow-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Progress</small><h2 id="sod-flow-h">ขั้นตอนออเดอร์</h2></div></div>

    @if(count($allowedActions) > 0)
        <div class="nvm-td-acts">
            @foreach($allowedActions as $action)
                @continue($action === 'cancel')
                <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}" x-on:submit="lock($event)" class="nvm-td-grow">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="{{ $action }}">
                    <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaActionIcon($action) }}"/></svg>{{ $actionLabels[$action] ?? $action }}</button>
                </form>
            @endforeach
            @if(in_array('cancel', $allowedActions, true))
                <button type="button" class="nvm-btn nvm-btn--line" x-on:click="cancelOpen = true"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิกออเดอร์</button>
            @endif
        </div>
        @if(in_array('ready', $allowedActions, true) && $isRider)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>กด "สินค้าพร้อมส่ง" แล้วระบบเรียกไรเดอร์ใกล้ร้านมารับของให้ทันที</p>
        @elseif(in_array('handover', $allowedActions, true) && $order->payment_method === 'cod')
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>กด "ส่งมอบสินค้าแล้ว" เมื่อลูกค้ามารับของและจ่ายเงินสด ฿{{ $ui::money($order->grand_total) }} เรียบร้อย</p>
        @endif
    @endif

    <ol class="nvm-td-tl nvm-gap-t">
        @foreach($steps as $step)
            <li class="is-{{ $step['state'] }}">
                <span class="nvm-td-tl__dot"><svg class="nvm-ico"><use href="#nvm-i-{{ $step['state'] === 'stopped' ? 'x' : $ui::novaStepIcon($step['key']) }}"/></svg></span>
                <span><b>{{ $step['label'] }}</b>@if($step['at'])<small> · {{ $step['at'] }}</small>@endif</span>
            </li>
        @endforeach
    </ol>

    @if($status === \App\Models\FreshMarketOrder::STATUS_CANCELLED)
        <div class="nvm-status is-bad nvm-gap-t">
            <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
            <span>ยกเลิกแล้ว@if($order->cancelled_by) โดย{{ $byLabels[$order->cancelled_by] ?? $order->cancelled_by }}@endif
                @if($order->cancel_reason)<small>{{ $order->cancel_reason }}</small>@endif</span>
        </div>
    @endif
</section>

<div class="nvm-split">
    {{-- ════════ รายการที่ต้องเตรียม ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="sod-items-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">To prepare</small><h2 id="sod-items-h">รายการที่ต้องเตรียม</h2></div></div>
        <ul class="nvm-td-prep">
            @foreach($lines as $item)
                <li>
                    <span class="nvm-td-thumb">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="" loading="lazy">
                        @else
                            <svg class="nvm-ico"><use href="#nvm-i-basket"/></svg>
                        @endif
                    </span>
                    <div>
                        <b><em class="nvm-num">{{ (int) $item->quantity }}×</em> {{ $item->title }}</b>
                        @if($item->optionsLabel() !== '')<span class="nvm-td-opt-txt">{{ $item->optionsLabel() }}</span>@endif
                        @if($item->note)<span class="nvm-td-note">โน้ต: {{ $item->note }}</span>@endif
                        <span class="nvm-td-muted">฿{{ $ui::money($item->unit_price) }} / {{ $item->unit }}</span>
                    </div>
                    <b class="nvm-num">฿{{ $ui::money($item->line_total) }}</b>
                </li>
            @endforeach
        </ul>
        @if($order->delivery_notes)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>โน้ตจากลูกค้า: {{ $order->delivery_notes }}</p>
        @endif
    </section>

    <div class="nvm-stack">
        {{-- ════════ ยอดเงิน ════════ --}}
        <section class="nvm-card nvm-box" aria-labelledby="sod-money-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Money</small><h2 id="sod-money-h">ยอดเงิน</h2></div></div>
            <dl class="nvm-kv">
                <dt>ยอดสินค้า</dt><dd class="nvm-num">฿{{ $ui::money($order->total_amount) }}</dd>
                <dt>GP {{ $order->gp_rate !== null ? rtrim(rtrim(number_format((float) $order->gp_rate, 2), '0'), '.').'%' : '' }}</dt><dd class="nvm-num">−฿{{ $ui::money($order->platform_fee) }}</dd>
                <dt class="nvm-kv__total">ร้านได้รับ</dt><dd class="nvm-kv__total nvm-num">฿{{ $ui::money($order->seller_earning) }}</dd>
                @if($isRider)
                    <dt>ค่าส่ง (ลูกค้าจ่ายให้ไรเดอร์)</dt><dd class="nvm-num">฿{{ $ui::money($order->delivery_fee) }}</dd>
                @endif
                <dt>การชำระเงิน</dt><dd>{{ $order->payment_status_label }}</dd>
            </dl>
            @if($order->payment_method === 'cod')
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>{{ $isRider ? 'ไรเดอร์เก็บเงินสดจากลูกค้าแล้วนำส่งเข้าระบบ — เงินเข้ากระเป๋าร้านหลังลูกค้าได้รับของ' : 'รับเงินสดจากลูกค้าตอนส่งมอบ (฿'.$ui::money($order->grand_total).') — ค่า GP ระบบหักจากกระเป๋าร้านภายหลัง' }}</p>
            @else
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ลูกค้าจ่ายผ่านกระเป๋าเงินแล้ว ระบบถือเงินไว้และโอนให้ร้านเมื่อลูกค้าได้รับของ</p>
            @endif
        </section>

        {{-- ════════ ลูกค้า / จัดส่ง ════════ --}}
        <section class="nvm-card nvm-box" aria-labelledby="sod-buyer-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Customer</small><h2 id="sod-buyer-h">ลูกค้า</h2></div></div>
            <dl class="nvm-kv">
                <dt>ชื่อ</dt><dd>{{ $order->buyer?->name ?? 'ลูกค้า' }}</dd>
                @if($isRider)
                    <dt>ส่งถึง</dt><dd>{{ $order->delivery_address ?: '—' }}</dd>
                    @if($order->delivery_distance_km)
                        <dt>ระยะจากร้าน</dt><dd class="nvm-num">{{ $ui::distance($order->delivery_distance_km) }}</dd>
                    @endif
                @else
                    <dt>รับสินค้า</dt><dd>ลูกค้ามารับเองที่ร้าน</dd>
                @endif
            </dl>
            @if($buyerPhone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $buyerPhone) }}" class="nvm-btn nvm-btn--line nvm-gap-t">โทรหาลูกค้า {{ $buyerPhone }}</a>
            @elseif($status === \App\Models\FreshMarketOrder::STATUS_PENDING)
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>เบอร์ลูกค้าจะแสดงหลังกดรับออเดอร์</p>
            @endif
        </section>

        {{-- ════════ ไรเดอร์ ════════ --}}
        @if($isRider)
            <section class="nvm-card nvm-box" aria-labelledby="sod-rider-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Rider</small><h2 id="sod-rider-h">ไรเดอร์</h2></div></div>
                @if($job)
                    <dl class="nvm-kv">
                        <dt>สถานะงาน</dt><dd>{{ $job->status_text }}</dd>
                        @if($job->rider)
                            <dt>ไรเดอร์</dt><dd>{{ $job->rider->full_name }}{{ $job->rider->vehicle_plate ? ' · '.$job->rider->vehicle_plate : '' }}</dd>
                        @endif
                    </dl>
                    @if($job->rider)
                        @if($job->isTrackable() && $job->rider->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $job->rider->phone) }}" class="nvm-btn nvm-btn--line nvm-gap-t">โทรหาไรเดอร์</a>
                        @endif
                    @else
                        <p class="nvm-note"><span class="nvm-td-dot is-warn is-live"></span>กำลังหาไรเดอร์ใกล้ร้าน</p>
                    @endif
                    @if($trackingUrl)
                        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><a class="nvm-link" href="{{ $trackingUrl }}" target="_blank" rel="noopener">เปิดหน้าติดตามไรเดอร์</a></p>
                    @endif
                @else
                    <p class="nvm-desc">ระบบเรียกไรเดอร์ให้เมื่อร้านกด "สินค้าพร้อมส่ง"</p>
                @endif
            </section>
        @endif

        {{-- ════════ ประวัติ ════════ --}}
        @if($history->isNotEmpty())
            <section class="nvm-card nvm-box" aria-labelledby="sod-hist-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">History</small><h2 id="sod-hist-h">ประวัติ</h2></div></div>
                <dl class="nvm-kv nvm-kv--legend">
                    @foreach($history->take(12) as $entry)
                        <dt>{{ $ui::shortDate($entry['at'] ?? null) }} · {{ \App\Models\FreshMarketOrder::statusLabel($entry['to'] ?? null) }}@if(! empty($entry['reason'])) — {{ \Illuminate\Support\Str::limit((string) $entry['reason'], 60) }}@endif</dt>
                        <dd>{{ $byLabels[$entry['by'] ?? ''] ?? '' }}</dd>
                    @endforeach
                </dl>
            </section>
        @endif
    </div>
</div>

{{-- กล่องยกเลิก (ต้องมีเหตุผล) --}}
@if(in_array('cancel', $allowedActions, true))
    <div class="nvm-modal" x-show="cancelOpen" x-cloak x-transition.opacity x-on:click.self="cancelOpen = false" x-on:keydown.escape.window="cancelOpen = false">
        <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}" class="nvm-card nvm-modal__box nvm-form" role="dialog" aria-modal="true" aria-labelledby="sod-cancel-h"
              x-data="{ reason: '' }" x-on:submit="lock($event)">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="cancel">
            <h2 id="sod-cancel-h">ยกเลิกออเดอร์ #{{ $order->order_number }}</h2>
            <p class="nvm-desc">ลูกค้าจะได้รับแจ้งเหตุผล และได้เงินคืนอัตโนมัติ (ถ้าจ่ายแล้ว){{ $job && $job->isTrackable() ? ' — งานไรเดอร์จะถูกยกเลิกด้วย' : '' }}</p>
            <div class="nvm-picks">
                @foreach($cancelReasons as $reason)
                    <button type="button" :class="reason === @js($reason) ? 'is-on' : ''" x-on:click="reason = @js($reason)">{{ $reason }}</button>
                @endforeach
            </div>
            <textarea name="reason" class="nvm-input nvm-textarea" rows="2" maxlength="500" required x-model="reason" placeholder="เหตุผลที่ยกเลิก" aria-label="เหตุผลที่ยกเลิก"></textarea>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" x-on:click="cancelOpen = false">ไม่ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="!reason.trim()"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยืนยันยกเลิก</button>
            </div>
        </form>
    </div>
@endif

</div>
@endsection

@push('scripts')
<script>
    /**
     * ออเดอร์ร้าน (รายละเอียด): กันกดปุ่มซ้ำ + ตรวจอัปเดตทุก 20 วินาที (ขึ้นป้ายให้กดรีเฟรช ไม่โหลดหน้าเองระหว่างอ่าน)
     */
    window.tsSellerOrderDetail = function (cfg) {
        let timer = null;

        return {
            changed: false, cancelOpen: false, submitting: false,

            init() {
                timer = setTimeout(() => this.poll(), 20000);
                // กลับมาด้วยปุ่มย้อนกลับของเบราว์เซอร์ (bfcache) → ปลดล็อกปุ่ม
                window.addEventListener('pageshow', (e) => {
                    if (!e.persisted) { return; }
                    this.submitting = false;
                    document.querySelectorAll('[data-td-locked]').forEach((b) => { b.disabled = false; b.removeAttribute('data-td-locked'); });
                });
            },

            lock(e) {
                if (this.submitting) { e.preventDefault(); return; }
                this.submitting = true;
                const btn = e.target.querySelector('button[type="submit"]');
                if (btn) { btn.disabled = true; btn.setAttribute('data-td-locked', '1'); }
            },

            async poll() {
                clearTimeout(timer);
                if (document.visibilityState === 'visible') {
                    const r = await window.ts.get(cfg.pollUrl);
                    if (r.ok && r.data) {
                        window.dispatchEvent(new CustomEvent('ts-pending-count', { detail: { count: r.data.pending } }));
                        const updated = r.data.last_updated_at ? new Date(r.data.last_updated_at).getTime() : 0;
                        const known = cfg.lastUpdated ? new Date(cfg.lastUpdated).getTime() : 0;
                        if (updated && known && updated > known + 1000) {
                            this.changed = true;
                            return;
                        }
                    }
                }
                timer = setTimeout(() => this.poll(), 20000);
            }
        };
    };
</script>
@endpush

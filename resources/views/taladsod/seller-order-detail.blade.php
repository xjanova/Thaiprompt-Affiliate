{{--
 | รายละเอียดออเดอร์ (ฝั่งร้าน — taladsod.seller.orders.show) — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@sellerOrderShow
 | ตัวแปร: $seller, $order (buyer, listing, riderJob.rider, items), $allowedActions (ของร้าน), $actionLabels [action => ป้ายไทย]
 | ฟอร์ม: PUT taladsod.seller.orders.status {action: accept|prepare|ready|handover|cancel, reason (บังคับเมื่อ cancel)} — ยกเลิกมีกล่องยืนยัน
 | ตรวจการเปลี่ยนแปลง: poll GET taladsod.seller.orders.poll ทุก 20 วินาที (มีอัปเดต = ป้ายให้กดรีเฟรช)
 --}}
@extends('layouts.user-v4')

@section('title', 'ออเดอร์ #'.$order->order_number.' · ร้านตลาดสด')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $rw = \App\Support\RiderWebUi::class;
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

    $cfg = [
        'pollUrl' => route('taladsod.seller.orders.poll'),
        'lastUpdated' => $lastUpdatedRaw ? \Illuminate\Support\Carbon::parse($lastUpdatedRaw)->toIso8601String() : null,
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')

<div class="ts-scope ts-stack" style="gap:16px;" x-data="tsSellerOrderDetail({{ \Illuminate\Support\Js::from($cfg) }})">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'orders'])

    <div class="ts-refresh" x-show="changed" x-cloak>
        <span><i class="fas fa-bell" aria-hidden="true"></i> มีอัปเดตออเดอร์ใหม่</span>
        <button type="button" class="ts-btn3d soft sm" x-on:click="window.location.reload()">รีเฟรช</button>
    </div>

    <nav class="sf-breadcrumb" aria-label="เส้นทางหน้า">
        <a href="{{ route('taladsod.seller.orders') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> ออเดอร์ทั้งหมด</a>
    </nav>

    {{-- ════════ หัว + ปุ่มจัดการ ════════ --}}
    <section class="tp-card ts-stack" style="padding:20px;">
        <div class="ts-row" style="justify-content:space-between; gap:12px;">
            <div style="min-width:0;">
                <div class="sf-kicker">ออเดอร์ร้าน</div>
                <h1 class="ts-h1" style="font-size:clamp(20px,4vw,26px);">#{{ $order->order_number }}</h1>
                <div class="ts-muted ts-small" style="margin-top:4px;">{{ $ui::date($order->created_at) }} · {{ $ui::deliveryLabel($order->delivery_type) }} · {{ $ui::paymentShortLabel($order->payment_method) }}</div>
            </div>
            <span class="ts-pill solid ts-tone-{{ $ui::orderTone($status) }}" style="font-size:13px; padding:9px 14px;"><i class="fas {{ $ui::orderIcon($status) }}" aria-hidden="true"></i> {{ $order->status_label }}</span>
        </div>

        @if(count($allowedActions) > 0)
            <div class="ts-row" style="gap:10px;">
                @foreach($allowedActions as $action)
                    @continue($action === 'cancel')
                    @php $style = $ui::actionStyle($action); @endphp
                    <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}" x-on:submit="lock($event)" style="flex:1 1 200px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="{{ $action }}">
                        <button type="submit" class="ts-btn3d lg block ts-tone-{{ $style['tone'] }}"><i class="fas {{ $style['icon'] }}" aria-hidden="true"></i> {{ $actionLabels[$action] ?? $action }}</button>
                    </form>
                @endforeach
                @if(in_array('cancel', $allowedActions, true))
                    <button type="button" class="tp-btn ts-btn-ghost ts-tone-bad" x-on:click="cancelOpen = true"><i class="fas fa-xmark" aria-hidden="true"></i> ยกเลิกออเดอร์</button>
                @endif
            </div>
            @if(in_array('ready', $allowedActions, true) && $isRider)
                <p class="ts-help" style="margin:0;">กด "สินค้าพร้อมส่ง" แล้วระบบเรียกไรเดอร์ใกล้ร้านมารับของให้ทันที</p>
            @elseif(in_array('handover', $allowedActions, true) && $order->payment_method === 'cod')
                <p class="ts-help" style="margin:0;">กด "ส่งมอบสินค้าแล้ว" เมื่อลูกค้ามารับของและจ่ายเงินสด ฿{{ $ui::money($order->grand_total) }} เรียบร้อย</p>
            @endif
        @endif

        <ol class="sf-tl" style="margin-top:6px;">
            @foreach($steps as $step)
                <li class="sf-tl-item {{ $step['state'] === 'todo' ? 'is-todo' : '' }} {{ $step['state'] === 'now' ? 'is-now' : '' }}">
                    <span class="sf-tl-dot" @if($step['state'] === 'stopped') style="background:linear-gradient(135deg, var(--ts-bad), color-mix(in srgb, var(--ts-bad) 70%, var(--ink)));" @endif>
                        <i class="fas {{ $step['state'] === 'stopped' ? 'fa-xmark' : $step['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div style="padding-top:8px;">
                        <b style="font-size:13.5px; color:{{ $step['state'] === 'todo' ? 'var(--ink2)' : 'var(--ink)' }};">{{ $step['label'] }}</b>
                        @if($step['at'])<span class="ts-muted ts-small"> · {{ $step['at'] }}</span>@endif
                    </div>
                </li>
            @endforeach
        </ol>

        @if($status === \App\Models\FreshMarketOrder::STATUS_CANCELLED)
            <div class="sf-note sf-note-err"><b>ยกเลิกแล้ว</b>@if($order->cancelled_by) โดย{{ $byLabels[$order->cancelled_by] ?? $order->cancelled_by }}@endif @if($order->cancel_reason)— {{ $order->cancel_reason }}@endif</div>
        @endif
    </section>

    <div class="ts-grid" style="--ts-min:320px; align-items:start;">
        {{-- ════════ รายการที่ต้องทำ ════════ --}}
        <section class="tp-card" aria-labelledby="sod-items-h">
            <h2 id="sod-items-h" class="ts-h2" style="margin-bottom:6px;"><i class="fas fa-fire-burner" style="color:var(--accent2);" aria-hidden="true"></i> รายการที่ต้องเตรียม</h2>
            @foreach($lines as $item)
                <div class="ts-line">
                    <span class="sf-thumb" style="width:56px; height:56px;">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="" loading="lazy">
                        @else
                            <span aria-hidden="true">🥬</span>
                        @endif
                    </span>
                    <div style="flex:1; min-width:0;">
                        <b style="font-size:14.5px; overflow-wrap:anywhere;"><span class="ts-num" style="color:var(--deep1);">{{ (int) $item->quantity }}×</span> {{ $item->title }}</b>
                        @if($item->optionsLabel() !== '')
                            <div style="font-size:13.5px; font-weight:700; color:var(--deep2);">{{ $item->optionsLabel() }}</div>
                        @endif
                        @if($item->note)
                            <div class="ts-small" style="color:var(--ts-bad); font-weight:700;"><i class="fas fa-note-sticky" aria-hidden="true"></i> {{ $item->note }}</div>
                        @endif
                        <div class="ts-muted ts-small">฿{{ $ui::money($item->unit_price) }} / {{ $item->unit }}</div>
                    </div>
                    <b class="ts-num">฿{{ $ui::money($item->line_total) }}</b>
                </div>
            @endforeach
            @if($order->delivery_notes)
                <div class="sf-note sf-note-info" style="margin-top:8px;"><i class="fas fa-comment" aria-hidden="true"></i> โน้ตจากลูกค้า: {{ $order->delivery_notes }}</div>
            @endif
        </section>

        <div class="ts-stack">
            {{-- ════════ เงิน ════════ --}}
            <section class="tp-card ts-stack" style="gap:4px;" aria-labelledby="sod-money-h">
                <h2 id="sod-money-h" class="ts-h2" style="margin-bottom:6px;"><i class="fas fa-coins" style="color:var(--accent2);" aria-hidden="true"></i> ยอดเงิน</h2>
                <div class="ts-kv"><span>ยอดสินค้า</span><b>฿{{ $ui::money($order->total_amount) }}</b></div>
                <div class="ts-kv"><span>GP {{ $order->gp_rate !== null ? rtrim(rtrim(number_format((float) $order->gp_rate, 2), '0'), '.').'%' : '' }}</span><b>−฿{{ $ui::money($order->platform_fee) }}</b></div>
                <div class="sf-total"><span class="ts-muted">ร้านได้รับ</span><span class="tp-num">฿{{ $ui::money($order->seller_earning) }}</span></div>
                @if($isRider)
                    <div class="ts-kv"><span>ค่าส่ง (ลูกค้าจ่ายให้ไรเดอร์)</span><b>฿{{ $ui::money($order->delivery_fee) }}</b></div>
                @endif
                <div class="ts-kv"><span>การชำระเงิน</span><b>{{ $order->payment_status_label }}</b></div>
                @if($order->payment_method === 'cod')
                    <p class="ts-help" style="margin:4px 0 0;">
                        {{ $isRider ? 'ไรเดอร์เก็บเงินสดจากลูกค้าแล้วนำส่งเข้าระบบ — เงินเข้ากระเป๋าร้านหลังลูกค้าได้รับของ' : 'รับเงินสดจากลูกค้าตอนส่งมอบ ('.'฿'.$ui::money($order->grand_total).') — ค่า GP ระบบหักจากกระเป๋าร้านภายหลัง' }}
                    </p>
                @else
                    <p class="ts-help" style="margin:4px 0 0;">ลูกค้าจ่ายผ่านกระเป๋าเงินแล้ว ระบบถือเงินไว้และโอนให้ร้านเมื่อลูกค้าได้รับของ</p>
                @endif
            </section>

            {{-- ════════ ลูกค้า / จัดส่ง ════════ --}}
            <section class="tp-card ts-stack" aria-labelledby="sod-buyer-h">
                <h2 id="sod-buyer-h" class="ts-h2"><i class="fas fa-user" style="color:var(--accent2);" aria-hidden="true"></i> ลูกค้า</h2>
                <div class="ts-kv"><span>ชื่อ</span><b>{{ $order->buyer?->name ?? 'ลูกค้า' }}</b></div>
                @if($buyerPhone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $buyerPhone) }}" class="tp-btn"><i class="fas fa-phone" aria-hidden="true"></i> โทรหาลูกค้า {{ $buyerPhone }}</a>
                @elseif($status === \App\Models\FreshMarketOrder::STATUS_PENDING)
                    <p class="ts-help" style="margin:0;">เบอร์ลูกค้าจะแสดงหลังกดรับออเดอร์</p>
                @endif
                @if($isRider)
                    <hr class="ts-divider">
                    <b style="font-size:13.5px;"><i class="fas fa-house" style="color:var(--accent2);" aria-hidden="true"></i> ส่งถึง</b>
                    <p style="margin:0; font-size:13.5px; line-height:1.6; overflow-wrap:anywhere;">{{ $order->delivery_address ?: '—' }}</p>
                    @if($order->delivery_distance_km)
                        <span class="ts-muted ts-small">ระยะจากร้าน {{ $ui::distance($order->delivery_distance_km) }}</span>
                    @endif
                @else
                    <div class="sf-note sf-note-info"><i class="fas fa-person-walking" aria-hidden="true"></i> ลูกค้ามารับเองที่ร้าน</div>
                @endif
            </section>

            {{-- ════════ ไรเดอร์ ════════ --}}
            @if($isRider)
                <section class="tp-card ts-stack" aria-labelledby="sod-rider-h">
                    <h2 id="sod-rider-h" class="ts-h2"><i class="fas fa-motorcycle" style="color:var(--accent2);" aria-hidden="true"></i> ไรเดอร์</h2>
                    @if($job)
                        <div class="ts-kv"><span>สถานะงาน</span><b>{{ $job->status_text }}</b></div>
                        @if($job->rider)
                            <div class="ts-kv"><span>ไรเดอร์</span><b>{{ $job->rider->full_name }}{{ $job->rider->vehicle_plate ? ' · '.$job->rider->vehicle_plate : '' }}</b></div>
                            @if($job->isTrackable() && $job->rider->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $job->rider->phone) }}" class="tp-btn"><i class="fas fa-phone" aria-hidden="true"></i> โทรหาไรเดอร์</a>
                            @endif
                        @else
                            <p class="ts-muted" style="margin:0; font-size:13px;"><span class="ts-dot live ts-tone-warn" style="display:inline-block; margin-right:6px;"></span>กำลังหาไรเดอร์ใกล้ร้าน</p>
                        @endif
                        @if($trackingUrl)
                            <a href="{{ $trackingUrl }}" target="_blank" rel="noopener" class="ts-link ts-small">เปิดหน้าติดตามไรเดอร์ <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        @endif
                    @else
                        <p class="ts-muted" style="margin:0; font-size:13px;">ระบบเรียกไรเดอร์ให้เมื่อร้านกด "สินค้าพร้อมส่ง"</p>
                    @endif
                </section>
            @endif

            {{-- ════════ ประวัติ ════════ --}}
            @if($history->isNotEmpty())
                <section class="tp-card ts-stack" style="gap:6px;" aria-labelledby="sod-hist-h">
                    <h2 id="sod-hist-h" class="ts-h2" style="margin-bottom:4px;"><i class="fas fa-clock-rotate-left" style="color:var(--accent2);" aria-hidden="true"></i> ประวัติ</h2>
                    @foreach($history->take(12) as $entry)
                        <div class="ts-kv" style="font-size:12.5px;">
                            <span>{{ $ui::shortDate($entry['at'] ?? null) }} · {{ \App\Models\FreshMarketOrder::statusLabel($entry['to'] ?? null) }}@if(! empty($entry['reason'])) — {{ \Illuminate\Support\Str::limit((string) $entry['reason'], 60) }}@endif</span>
                            <b>{{ $byLabels[$entry['by'] ?? ''] ?? '' }}</b>
                        </div>
                    @endforeach
                </section>
            @endif
        </div>
    </div>

    {{-- กล่องยกเลิก (ต้องมีเหตุผล) --}}
    @if(in_array('cancel', $allowedActions, true))
        <div class="ts-dialog-bg" x-show="cancelOpen" x-cloak x-transition.opacity x-on:click.self="cancelOpen = false" x-on:keydown.escape.window="cancelOpen = false">
            <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="sod-cancel-h"
                  x-data="{ reason: '' }" x-on:submit="lock($event)">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="cancel">
                <h2 id="sod-cancel-h" class="ts-h2"><i class="fas fa-circle-xmark" style="color:var(--ts-bad);" aria-hidden="true"></i> ยกเลิกออเดอร์ #{{ $order->order_number }}</h2>
                <p class="ts-muted" style="margin:0; font-size:13.5px;">ลูกค้าจะได้รับแจ้งเหตุผล และได้เงินคืนอัตโนมัติ (ถ้าจ่ายแล้ว){{ $job && $job->isTrackable() ? ' — งานไรเดอร์จะถูกยกเลิกด้วย' : '' }}</p>
                <div class="ts-row" style="gap:6px;">
                    @foreach($cancelReasons as $reason)
                        <button type="button" class="sf-chip" :class="reason === @js($reason) ? 'is-on' : ''" x-on:click="reason = @js($reason)">{{ $reason }}</button>
                    @endforeach
                </div>
                <textarea name="reason" class="tp-input" rows="2" maxlength="500" required x-model="reason" placeholder="เหตุผลที่ยกเลิก" aria-label="เหตุผลที่ยกเลิก"></textarea>
                <div class="ts-row" style="justify-content:flex-end;">
                    <button type="button" class="tp-btn" x-on:click="cancelOpen = false">ไม่ยกเลิก</button>
                    <button type="submit" class="ts-btn3d ts-tone-bad sm" :disabled="!reason.trim()"><i class="fas fa-xmark" aria-hidden="true"></i> ยืนยันยกเลิก</button>
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
            },

            lock(e) {
                if (this.submitting) { e.preventDefault(); return; }
                this.submitting = true;
                const btn = e.target.querySelector('button[type="submit"]');
                if (btn) { btn.disabled = true; btn.insertAdjacentHTML('afterbegin', '<i class="fas fa-circle-notch ts-spin" aria-hidden="true"></i> '); }
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

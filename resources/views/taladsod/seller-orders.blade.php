{{--
 | ออเดอร์ของร้าน (taladsod.seller.orders) — รับ / เตรียม / พร้อมส่ง / ส่งมอบ / ยกเลิก — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@sellerOrders
 | ตัวแปร: $seller, $orders (paginator + buyer, listing, riderJob, items), $statusFilter, $statusCounts [status => n], $statuses [status => ป้ายไทย]
 | ปุ่ม: PUT taladsod.seller.orders.status {action: accept|prepare|ready|handover|cancel, reason (บังคับเมื่อ cancel), return_to=list, return_status}
 | รีเฟรชอัตโนมัติ: poll GET taladsod.seller.orders.poll ทุก 20 วินาที → มีออเดอร์ใหม่/เปลี่ยนสถานะ = แจ้ง + โหลดหน้าใหม่ (ถ้าไม่ได้เปิดกล่องอยู่)
 --}}
@extends('layouts.user-v4')

@section('title', 'ออเดอร์ร้าน · '.$seller->shop_name)

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $allCount = array_sum($statusCounts);
    $filterOrder = ['pending', 'accepted', 'preparing', 'ready', 'delivering', 'delivered', 'completed', 'cancelled', 'delivery_failed'];
    $cancelReasons = ['ของหมด', 'ร้านใกล้ปิดแล้ว', 'อยู่นอกพื้นที่ส่ง', 'ลูกค้าขอยกเลิก', 'ติดต่อลูกค้าไม่ได้'];

    // จุดอ้างอิงของตัวตรวจออเดอร์ใหม่ (เวลาเป็น ISO-8601 ชุดเดียวกับ endpoint poll)
    $lastUpdatedRaw = \App\Models\FreshMarketOrder::where('seller_id', $seller->id)->max('updated_at');
    $pollCfg = [
        'pollUrl' => route('taladsod.seller.orders.poll'),
        'latestOrderId' => (int) (\App\Models\FreshMarketOrder::where('seller_id', $seller->id)->max('id') ?? 0),
        'lastUpdated' => $lastUpdatedRaw ? \Illuminate\Support\Carbon::parse($lastUpdatedRaw)->toIso8601String() : null,
        'pending' => (int) ($statusCounts['pending'] ?? 0),
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')

<div class="ts-scope ts-stack" style="gap:16px;" x-data="tsSellerOrders({{ \Illuminate\Support\Js::from($pollCfg) }})">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'orders'])

    <div class="ts-refresh" x-show="changed" x-cloak>
        <span><i class="fas fa-bell" aria-hidden="true"></i> <span x-text="changeText"></span></span>
        <button type="button" class="ts-btn3d soft sm" x-on:click="window.location.reload()">รีเฟรชรายการ</button>
    </div>

    <div class="ts-row" style="justify-content:space-between;">
        <div>
            <h1 class="ts-h1">ออเดอร์ของร้าน</h1>
            <p class="ts-muted" style="margin:6px 0 0; font-size:13px;"><span class="ts-dot live ts-tone-ok" style="display:inline-block; margin-right:6px;"></span>อัปเดตอัตโนมัติทุก 20 วินาที</p>
        </div>
        <a href="{{ route('taladsod.seller.dashboard') }}" class="tp-btn"><i class="fas fa-store" aria-hidden="true"></i> หน้าร้านวันนี้</a>
    </div>

    <div class="sf-scroll" role="tablist" aria-label="กรองตามสถานะ">
        <a href="{{ route('taladsod.seller.orders') }}" class="sf-chip {{ ! $statusFilter ? 'is-on' : '' }}" role="tab" aria-selected="{{ ! $statusFilter ? 'true' : 'false' }}">ทั้งหมด <span class="ts-num" style="opacity:.75;">{{ $allCount }}</span></a>
        @foreach($filterOrder as $key)
            @if(isset($statuses[$key]) && (($statusCounts[$key] ?? 0) > 0 || $statusFilter === $key || in_array($key, ['pending', 'accepted', 'preparing', 'ready'], true)))
                <a href="{{ route('taladsod.seller.orders', ['status' => $key]) }}" class="sf-chip {{ $statusFilter === $key ? 'is-on' : '' }}" role="tab" aria-selected="{{ $statusFilter === $key ? 'true' : 'false' }}">
                    {{ $statuses[$key] }}
                    <span class="ts-num" style="opacity:.75;">{{ (int) ($statusCounts[$key] ?? 0) }}</span>
                </a>
            @endif
        @endforeach
    </div>

    <div class="ts-stack">
        @forelse($orders as $order)
            @php
                $actions = $order->allowedActions('seller');
                $lines = $order->lineItems();
                $tone = $ui::orderTone($order->order_status);
                $isNew = $order->order_status === \App\Models\FreshMarketOrder::STATUS_PENDING;
                $job = $order->riderJob;
            @endphp
            <article class="tp-card ts-stack" style="padding:16px; gap:12px; {{ $isNew ? 'box-shadow:var(--card-shadow), 0 0 0 2px color-mix(in srgb, var(--ts-warn) 55%, transparent);' : '' }}">
                <div class="ts-row" style="justify-content:space-between; gap:8px;">
                    <div style="min-width:0;">
                        <a href="{{ route('taladsod.seller.orders.show', $order) }}" class="ts-link" style="font-size:15px;">#{{ $order->order_number }}</a>
                        <span class="ts-muted ts-small"> · {{ $ui::shortDate($order->created_at) }} · {{ $order->created_at?->diffForHumans() }}</span>
                        <div class="ts-muted ts-small" style="margin-top:2px;"><i class="fas fa-user" aria-hidden="true"></i> {{ $order->buyer?->name ?? 'ลูกค้า' }}</div>
                    </div>
                    <span class="ts-pill solid ts-tone-{{ $tone }}"><i class="fas {{ $ui::orderIcon($order->order_status) }}" aria-hidden="true"></i> {{ $order->status_label }}</span>
                </div>

                <div class="ts-row" style="gap:6px;">
                    <span class="ts-pill {{ $order->delivery_type === 'rider' ? 'ts-tone-deep' : 'ts-tone-muted' }}"><i class="fas {{ $order->delivery_type === 'rider' ? 'fa-motorcycle' : 'fa-person-walking' }}" aria-hidden="true"></i> {{ $ui::deliveryLabel($order->delivery_type) }}</span>
                    <span class="ts-pill {{ $order->payment_method === 'cod' ? 'ts-tone-warn' : 'ts-tone-ok' }}"><i class="fas {{ $order->payment_method === 'cod' ? 'fa-money-bill-wave' : 'fa-wallet' }}" aria-hidden="true"></i> {{ $order->payment_method === 'cod' ? 'เก็บเงินปลายทาง' : 'จ่ายแล้วผ่านกระเป๋าเงิน' }}</span>
                    @if($job)
                        <span class="ts-pill ts-tone-info"><i class="fas fa-motorcycle" aria-hidden="true"></i> {{ $job->status_text }}</span>
                    @endif
                </div>

                <div class="tp-inset" style="border-radius:14px; padding:10px 12px;">
                    @foreach($lines as $item)
                        <div class="ts-row" style="gap:8px; flex-wrap:nowrap; align-items:flex-start; padding:4px 0;">
                            <b class="ts-num" style="min-width:30px; color:var(--deep1);">{{ (int) $item->quantity }}×</b>
                            <div style="flex:1; min-width:0;">
                                <b style="font-size:14px; overflow-wrap:anywhere;">{{ $item->title }}</b>
                                @if($item->optionsLabel() !== '')
                                    <div style="font-size:13px; font-weight:700; color:var(--deep2);">{{ $item->optionsLabel() }}</div>
                                @endif
                                @if($item->note)
                                    <div class="ts-small" style="color:var(--ts-bad); font-weight:700;"><i class="fas fa-note-sticky" aria-hidden="true"></i> {{ $item->note }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if($order->delivery_notes)
                        <div class="ts-muted ts-small" style="margin-top:4px;"><i class="fas fa-comment" aria-hidden="true"></i> {{ $order->delivery_notes }}</div>
                    @endif
                </div>

                <div class="ts-row" style="justify-content:space-between; gap:10px;">
                    <div>
                        <b class="ts-money" style="font-size:18px;">฿{{ $ui::money($order->total_amount) }}</b>
                        <span class="ts-muted ts-small"> · ร้านได้ ฿{{ $ui::money($order->seller_earning) }}</span>
                    </div>
                    <div class="ts-row" style="gap:8px;">
                        @foreach($actions as $action)
                            @continue($action === 'cancel')
                            @php $style = $ui::actionStyle($action); @endphp
                            <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}" x-on:submit="lock($event)">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="{{ $action }}">
                                <input type="hidden" name="return_to" value="list">
                                <input type="hidden" name="return_status" value="{{ $statusFilter }}">
                                <button type="submit" class="ts-btn3d sm ts-tone-{{ $style['tone'] }}"><i class="fas {{ $style['icon'] }}" aria-hidden="true"></i> {{ \App\Models\FreshMarketOrder::actionLabel($action) }}</button>
                            </form>
                        @endforeach
                        @if(in_array('cancel', $actions, true))
                            <button type="button" class="tp-btn tp-btn-sm ts-btn-ghost ts-tone-bad"
                                    x-on:click="openCancel(@js($order->order_number), @js(route('taladsod.seller.orders.status', $order)))">
                                <i class="fas fa-xmark" aria-hidden="true"></i> ยกเลิก
                            </button>
                        @endif
                        <a href="{{ route('taladsod.seller.orders.show', $order) }}" class="tp-btn tp-btn-sm">รายละเอียด</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="tp-card ts-empty" style="padding:44px 18px;">
                <span class="em" aria-hidden="true">📭</span>
                <b style="font-size:16px;">{{ $statusFilter ? 'ไม่มีออเดอร์ในสถานะนี้' : 'ยังไม่มีออเดอร์' }}</b>
                <span class="ts-muted">เปิดร้านไว้ แล้วออเดอร์ใหม่จะเด้งขึ้นที่นี่ทันที</span>
                <a href="{{ route('taladsod.seller.dashboard') }}" class="ts-btn3d ts-tone-gold sm"><i class="fas fa-store" aria-hidden="true"></i> ไปหน้าร้านวันนี้</a>
            </div>
        @endforelse
    </div>

    @if($orders->hasPages())
        <div>{{ $orders->links('vendor.pagination.tp-v4') }}</div>
    @endif

    {{-- กล่องยกเลิก (ต้องมีเหตุผล) --}}
    <div class="ts-dialog-bg" x-show="cancel.open" x-cloak x-transition.opacity x-on:click.self="cancel.open = false" x-on:keydown.escape.window="cancel.open = false">
        <form method="POST" :action="cancel.url" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="so-cancel-h" x-on:submit="lock($event)">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="return_to" value="list">
            <input type="hidden" name="return_status" value="{{ $statusFilter }}">
            <h2 id="so-cancel-h" class="ts-h2"><i class="fas fa-circle-xmark" style="color:var(--ts-bad);" aria-hidden="true"></i> ยกเลิกออเดอร์ #<span x-text="cancel.number"></span></h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;">ลูกค้าจะได้รับแจ้งเหตุผล และได้เงินคืนอัตโนมัติ (ถ้าจ่ายแล้ว)</p>
            <div class="ts-row" style="gap:6px;">
                @foreach($cancelReasons as $reason)
                    <button type="button" class="sf-chip" :class="cancel.reason === @js($reason) ? 'is-on' : ''" x-on:click="cancel.reason = @js($reason)">{{ $reason }}</button>
                @endforeach
            </div>
            <div>
                <label class="ts-label" for="so-reason">เหตุผล <span class="req">*</span></label>
                <textarea id="so-reason" name="reason" class="tp-input" rows="2" maxlength="500" required x-model="cancel.reason" placeholder="บอกลูกค้าสั้นๆ ว่าทำไมต้องยกเลิก"></textarea>
            </div>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="cancel.open = false">ไม่ยกเลิก</button>
                <button type="submit" class="ts-btn3d ts-tone-bad sm" :disabled="!cancel.reason.trim()"><i class="fas fa-xmark" aria-hidden="true"></i> ยืนยันยกเลิก</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /**
     * ออเดอร์ร้าน: กันกดซ้ำ + กล่องยกเลิกพร้อมเหตุผล + ตรวจออเดอร์ใหม่ทุก 20 วิ (โหลดหน้าใหม่ให้เองเมื่อไม่ได้พิมพ์/เปิดกล่องอยู่)
     */
    window.tsSellerOrders = function (cfg) {
        let timer = null;

        return {
            changed: false, changeText: '', submitting: false,
            cancel: { open: false, number: '', url: '', reason: '' },

            init() {
                timer = setTimeout(() => this.poll(), 20000);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible' && !this.changed) { this.poll(); }
                });
            },

            lock(e) {
                if (this.submitting) { e.preventDefault(); return; }
                this.submitting = true;
                const btn = e.target.querySelector('button[type="submit"]');
                if (btn) { btn.disabled = true; btn.insertAdjacentHTML('afterbegin', '<i class="fas fa-circle-notch ts-spin" aria-hidden="true"></i> '); }
            },

            openCancel(number, url) {
                this.cancel = { open: true, number: number, url: url, reason: '' };
            },

            async poll() {
                clearTimeout(timer);
                if (document.visibilityState === 'visible' && !this.submitting) {
                    const r = await window.ts.get(cfg.pollUrl);
                    if (r.ok && r.data) {
                        const newest = Number(r.data.latest_order_id) || 0;
                        const updated = r.data.last_updated_at ? new Date(r.data.last_updated_at).getTime() : 0;
                        const known = cfg.lastUpdated ? new Date(cfg.lastUpdated).getTime() : 0;
                        window.dispatchEvent(new CustomEvent('ts-pending-count', { detail: { count: r.data.pending } }));
                        if (newest > cfg.latestOrderId || (updated && known && updated > known + 1000)) {
                            this.changed = true;
                            this.changeText = newest > cfg.latestOrderId ? 'มีออเดอร์ใหม่เข้ามา!' : 'มีออเดอร์เปลี่ยนสถานะ';
                            window.ts.notify(this.changeText, 'success');
                            const typing = document.activeElement && ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName);
                            if (!this.cancel.open && !typing) { setTimeout(() => window.location.reload(), 2500); }
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

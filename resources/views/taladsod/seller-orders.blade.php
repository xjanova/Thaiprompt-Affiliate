{{--
 | ออเดอร์ของร้าน (taladsod.seller.orders) — รับ / เตรียม / พร้อมส่ง / ส่งมอบ / ยกเลิก — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@sellerOrders
 | ตัวแปร: $seller, $orders (paginator + buyer, listing, riderJob, items), $statusFilter, $statusCounts [status => n], $statuses [status => ป้ายไทย]
 | ปุ่ม: PUT taladsod.seller.orders.status {action: accept|prepare|ready|handover|cancel, reason (บังคับเมื่อ cancel), return_to=list, return_status}
 | รีเฟรชอัตโนมัติ: poll GET taladsod.seller.orders.poll ทุก 20 วินาที → มีออเดอร์ใหม่/เปลี่ยนสถานะ = แจ้ง + โหลดหน้าใหม่ (ถ้าไม่ได้เปิดกล่องอยู่)
 | ความเป็นส่วนตัว: รายการแสดงแค่ชื่อลูกค้า (เบอร์โทร/ที่อยู่ดูได้ที่หน้ารายละเอียดหลังรับออเดอร์)
 --}}
@extends('layouts.user-nova')

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

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')

<div class="nvm-stack" x-data="tsSellerOrders({{ \Illuminate\Support\Js::from($pollCfg) }})">

<section class="nvm-ph" aria-labelledby="nvm-td-so-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Shop orders</div>
            <h1 id="nvm-td-so-h">ออเดอร์ของร้าน</h1>
            <p class="nvm-ph__lead"><span class="nvm-td-dot is-ok is-live"></span> อัปเดตอัตโนมัติทุก 20 วินาที</p>
            <div class="nvm-hero__actions nvm-td-acts">
                <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ route('taladsod.seller.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>หน้าร้านวันนี้</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'orders'])

<section class="nvm-card nvm-alert nvm-alert--info" x-show="changed" x-cloak role="status">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
    <div class="nvm-alert__txt"><b x-text="changeText"></b><p>กดรีเฟรชเพื่อดูรายการล่าสุด</p></div>
    <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" x-on:click="window.location.reload()">รีเฟรชรายการ</button>
</section>

<nav class="nvm-tabs" aria-label="กรองตามสถานะ">
    <a href="{{ route('taladsod.seller.orders') }}" class="{{ ! $statusFilter ? 'is-on' : '' }}" @if(! $statusFilter) aria-current="page" @endif>ทั้งหมด <span class="nvm-chip nvm-num">{{ $allCount }}</span></a>
    @foreach($filterOrder as $key)
        @if(isset($statuses[$key]) && (($statusCounts[$key] ?? 0) > 0 || $statusFilter === $key || in_array($key, ['pending', 'accepted', 'preparing', 'ready'], true)))
            <a href="{{ route('taladsod.seller.orders', ['status' => $key]) }}" class="{{ $statusFilter === $key ? 'is-on' : '' }}" @if($statusFilter === $key) aria-current="page" @endif>
                {{ $statuses[$key] }} <span class="nvm-chip nvm-num">{{ (int) ($statusCounts[$key] ?? 0) }}</span>
            </a>
        @endif
    @endforeach
</nav>

@forelse($orders as $order)
    @php
        $actions = $order->allowedActions('seller');
        $lines = $order->lineItems();
        $isNew = $order->order_status === \App\Models\FreshMarketOrder::STATUS_PENDING;
        $job = $order->riderJob;
    @endphp
    <article class="nvm-card nvm-td-order {{ $isNew ? 'is-new' : '' }}">
        <div class="nvm-td-order__head">
            <div>
                <a href="{{ route('taladsod.seller.orders.show', $order) }}">#{{ $order->order_number }}</a>
                <small>{{ $ui::shortDate($order->created_at) }} · {{ $order->created_at?->diffForHumans() }} · ลูกค้า {{ $order->buyer?->name ?? 'ลูกค้า' }}</small>
            </div>
            <span class="{{ $ui::novaChip($ui::orderTone($order->order_status)) }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaOrderIcon($order->order_status) }}"/></svg>{{ $order->status_label }}</span>
        </div>

        <div class="nvm-td-chips">
            <span class="{{ $order->delivery_type === 'rider' ? 'nvm-chip nvm-chip--info' : 'nvm-chip' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $order->delivery_type === 'rider' ? 'scooter' : 'store' }}"/></svg>{{ $ui::deliveryLabel($order->delivery_type) }}</span>
            <span class="{{ $order->payment_method === 'cod' ? 'nvm-chip nvm-chip--warn' : 'nvm-chip nvm-chip--ok' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $order->payment_method === 'cod' ? 'cash' : 'wallet' }}"/></svg>{{ $order->payment_method === 'cod' ? 'เก็บเงินปลายทาง' : 'จ่ายแล้วผ่านกระเป๋าเงิน' }}</span>
            @if($job)
                <span class="nvm-chip nvm-chip--info"><svg class="nvm-ico"><use href="#nvm-i-scooter"/></svg>{{ $job->status_text }}</span>
            @endif
        </div>

        <div class="nvm-td-lines">
            @foreach($lines as $item)
                <div class="nvm-td-line">
                    <b class="nvm-num">{{ (int) $item->quantity }}×</b>
                    <div>
                        <b>{{ $item->title }}</b>
                        @if($item->optionsLabel() !== '')<span class="nvm-td-opt-txt">{{ $item->optionsLabel() }}</span>@endif
                        @if($item->note)<span class="nvm-td-note">โน้ต: {{ $item->note }}</span>@endif
                    </div>
                </div>
            @endforeach
            @if($order->delivery_notes)
                <span class="nvm-td-muted">ข้อความจากลูกค้า: {{ $order->delivery_notes }}</span>
            @endif
        </div>

        <div class="nvm-td-order__foot">
            <div>
                <span class="nvm-td-price nvm-num">฿{{ $ui::money($order->total_amount) }}</span>
                <small> · ร้านได้ ฿{{ $ui::money($order->seller_earning) }}</small>
            </div>
            <div class="nvm-td-acts">
                @foreach($actions as $action)
                    @continue($action === 'cancel')
                    <form method="POST" action="{{ route('taladsod.seller.orders.status', $order) }}" x-on:submit="lock($event)">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="{{ $action }}">
                        <input type="hidden" name="return_to" value="list">
                        <input type="hidden" name="return_status" value="{{ $statusFilter }}">
                        <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaActionIcon($action) }}"/></svg>{{ \App\Models\FreshMarketOrder::actionLabel($action) }}</button>
                    </form>
                @endforeach
                @if(in_array('cancel', $actions, true))
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm"
                            x-on:click="openCancel(@js($order->order_number), @js(route('taladsod.seller.orders.status', $order)))">
                        <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิก
                    </button>
                @endif
                <a href="{{ route('taladsod.seller.orders.show', $order) }}" class="nvm-btn nvm-btn--line nvm-btn--sm">รายละเอียด</a>
            </div>
        </div>
    </article>
@empty
    <section class="nvm-card nvm-empty">
        <img src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="86" height="86" loading="lazy">
        <b>{{ $statusFilter ? 'ไม่มีออเดอร์ในสถานะนี้' : 'ยังไม่มีออเดอร์' }}</b>
        เปิดร้านไว้ แล้วออเดอร์ใหม่จะเด้งขึ้นที่นี่ทันที
        <div><a href="{{ route('taladsod.seller.dashboard') }}" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>ไปหน้าร้านวันนี้</a></div>
    </section>
@endforelse

@if($orders->hasPages())
    <div class="nvm-pager">{{ $orders->links() }}</div>
@endif

{{-- กล่องยกเลิก (ต้องมีเหตุผล) --}}
<div class="nvm-modal" x-show="cancel.open" x-cloak x-transition.opacity x-on:click.self="cancel.open = false" x-on:keydown.escape.window="cancel.open = false">
    <form method="POST" :action="cancel.url" class="nvm-card nvm-modal__box nvm-form" role="dialog" aria-modal="true" aria-labelledby="so-cancel-h" x-on:submit="lock($event)">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="return_to" value="list">
        <input type="hidden" name="return_status" value="{{ $statusFilter }}">
        <h2 id="so-cancel-h">ยกเลิกออเดอร์ #<span x-text="cancel.number"></span></h2>
        <p class="nvm-desc">ลูกค้าจะได้รับแจ้งเหตุผล และได้เงินคืนอัตโนมัติ (ถ้าจ่ายแล้ว)</p>
        <div class="nvm-picks">
            @foreach($cancelReasons as $reason)
                <button type="button" :class="cancel.reason === @js($reason) ? 'is-on' : ''" x-on:click="cancel.reason = @js($reason)">{{ $reason }}</button>
            @endforeach
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="so-reason">เหตุผล <em>*</em></label>
            <textarea id="so-reason" name="reason" class="nvm-input nvm-textarea" rows="2" maxlength="500" required x-model="cancel.reason" placeholder="บอกลูกค้าสั้นๆ ว่าทำไมต้องยกเลิก"></textarea>
        </div>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" x-on:click="cancel.open = false">ไม่ยกเลิก</button>
            <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="!cancel.reason.trim()"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยืนยันยกเลิก</button>
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

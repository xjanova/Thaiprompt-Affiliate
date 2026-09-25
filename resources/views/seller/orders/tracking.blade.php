@extends('layouts.seller-v4')

@section('title', 'การจัดส่ง #' . $order->order_number)

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    // แท็บเริ่มต้นจาก ?tab= (ลิงก์ "แชท" จากหน้าอื่นส่ง tab=chat มา)
    $initialTab = in_array(request('tab'), ['tracking', 'history', 'chat'], true) ? request('tab') : 'tracking';
    $unreadCount = $order->messages->where('is_read', false)->where('sender_type', 'customer')->count();
    $isRider = $order->isRiderDelivery();
    $actions = $allowedActions ?? [];
    $canShip = in_array('ship', $actions, true);
    $riderStatus = $riderSummary['status'] ?? null;
    $historyLabels = ['in_transit' => 'อยู่ระหว่างขนส่ง', 'out_for_delivery' => 'กำลังนำส่ง', 'delivered' => 'ส่งถึงแล้ว'];
@endphp

@section('content')
<div class="sv4-page" x-data="orderTracking(@js($initialTab), @js(route('seller.orders.messages.read', $order->id)), {{ $unreadCount }})">

    <x-seller-v4.header :title="'การจัดส่ง #' . $order->order_number"
                        :subtitle="'ลูกค้า: ' . ($order->user->name ?? 'ไม่ระบุ')"
                        icon="📍" :back="route('seller.orders.show', $order)">
        <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::orderStatusColor($order->status)) }} font-size:12px; padding:7px 12px;">{{ $order->status_label }}</span>
        <a href="{{ route('seller.orders.print', $order) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm">🖨️ พิมพ์ใบส่งของ</a>
    </x-seller-v4.header>

    <x-seller-v4.errors />

    <nav class="sv4-tabs" role="tablist" aria-label="เมนูการจัดส่ง">
        <button type="button" class="sv4-tab" :class="tab === 'tracking' && 'on'" @click="go('tracking')" role="tab">🚚 ข้อมูลการจัดส่ง</button>
        <button type="button" class="sv4-tab" :class="tab === 'history' && 'on'" @click="go('history')" role="tab">🕑 ประวัติการจัดส่ง</button>
        <button type="button" class="sv4-tab" :class="tab === 'chat' && 'on'" @click="go('chat')" role="tab">
            💬 แชทกับลูกค้า
            <span class="sv4-count" x-show="unread > 0" x-text="unread" style="background:{{ SellerUi::BAD }}; color:var(--tp-on-accent, #fff);"></span>
        </button>
    </nav>

    {{-- ── แท็บข้อมูลการจัดส่ง ─────────────────────────────── --}}
    <div x-show="tab === 'tracking'" class="flex" style="flex-wrap:wrap; gap:18px; align-items:flex-start;">

        <div class="tp-card" style="padding:20px; flex:1 1 360px; min-width:0;">
            @if($isRider)
                <div class="sv4-h2">🛵 ส่งด้วยไรเดอร์</div>
                <div class="sv4-sub">ออเดอร์นี้ลูกค้าเลือกส่งด้วยไรเดอร์ ไม่ต้องกรอกเลขพัสดุ</div>
                @if($riderSummary)
                    <div class="sv4-well" style="margin-top:14px;">
                        <div class="sv4-kv"><span>สถานะไรเดอร์</span>
                            <span><span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::riderStatusColor($riderStatus)) }}">{{ $riderSummary['status_label'] ?? '-' }}</span></span>
                        </div>
                        @if(! empty($riderSummary['rider']['name']))
                            <div class="sv4-kv"><span>ไรเดอร์</span><span>{{ $riderSummary['rider']['name'] }}</span></div>
                        @endif
                        @if(! empty($riderSummary['rider']['vehicle_plate']))
                            <div class="sv4-kv"><span>ทะเบียนรถ</span><span class="tp-num">{{ $riderSummary['rider']['vehicle_plate'] }}</span></div>
                        @endif
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:14px;">
                        @if(! empty($riderSummary['tracking_url']))
                            <a href="{{ $riderSummary['tracking_url'] }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm">🗺️ ติดตามไรเดอร์แบบสด</a>
                        @endif
                        @if(in_array('request_rider', $actions, true))
                            <a href="{{ route('seller.orders.show', $order) }}" class="tp-btn tp-btn-sm tp-btn-primary">🛵 ไปเรียกไรเดอร์</a>
                        @endif
                    </div>
                @endif
            @elseif($canShip)
                <div class="sv4-h2">📝 กรอกข้อมูลการจัดส่ง</div>
                <div class="sv4-sub">บันทึกแล้วสถานะจะเปลี่ยนเป็น "จัดส่งแล้ว" และแจ้งลูกค้าอัตโนมัติ</div>
                <form action="{{ route('seller.orders.add-tracking', $order) }}" method="POST"
                      x-data="{ busy: false }" @submit="busy = true"
                      style="display:flex; flex-direction:column; gap:14px; margin-top:16px;">
                    @csrf
                    <div>
                        <label for="shipping_provider_id" class="sv4-label">บริษัทขนส่ง <span class="req">*</span></label>
                        <select name="shipping_provider_id" id="shipping_provider_id" required class="tp-input">
                            <option value="">— เลือกบริษัทขนส่ง —</option>
                            @foreach($shippingProviders as $provider)
                                <option value="{{ $provider->id }}" @selected(old('shipping_provider_id', $order->shipping_provider_id) == $provider->id)>
                                    {{ $provider->name }}@if($provider->name_en) ({{ $provider->name_en }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('shipping_provider_id')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="tracking_number" class="sv4-label">หมายเลขพัสดุ <span class="req">*</span></label>
                        <input type="text" name="tracking_number" id="tracking_number" required maxlength="100" autocomplete="off"
                               value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="เช่น TH123456789" class="tp-input tp-num">
                        @error('tracking_number')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="estimated_delivery_at" class="sv4-label">วันที่คาดว่าจะถึง</label>
                        <input type="date" name="estimated_delivery_at" id="estimated_delivery_at" min="{{ now()->toDateString() }}"
                               value="{{ old('estimated_delivery_at', $order->estimated_delivery_at ? \Illuminate\Support\Carbon::parse($order->estimated_delivery_at)->format('Y-m-d') : '') }}"
                               class="tp-input">
                        @error('estimated_delivery_at')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="tp-btn tp-btn-primary sv4-btn-block" :disabled="busy">
                        <span x-show="!busy">💾 บันทึกข้อมูลการจัดส่ง</span>
                        <span x-show="busy" x-cloak>กำลังบันทึก…</span>
                    </button>
                </form>
            @else
                <div class="sv4-h2">🚚 ข้อมูลการจัดส่ง</div>
                @if($order->tracking_number)
                    <div class="sv4-sub">ออเดอร์นี้จัดส่งแล้ว ติดตามความคืบหน้าในแท็บ "ประวัติการจัดส่ง"</div>
                @elseif(! $order->isReadyForFulfilment())
                    <div class="sv4-note" style="margin-top:12px; --c:{{ SellerUi::WARN }};">⏳ ยังจัดส่งไม่ได้ — ออเดอร์ต้องชำระเงินแล้ว (หรือเป็นเก็บเงินปลายทาง) และยังไม่ถูกยกเลิก</div>
                @else
                    <div class="sv4-note" style="margin-top:12px; --c:{{ SellerUi::INFO }};">กดยืนยันรับคำสั่งซื้อในหน้ารายละเอียดก่อน แล้วจึงกรอกเลขพัสดุ</div>
                    <a href="{{ route('seller.orders.show', $order) }}" class="tp-btn tp-btn-sm" style="margin-top:12px;">ไปหน้ารายละเอียด</a>
                @endif
            @endif
        </div>

        <div style="display:flex; flex-direction:column; gap:18px; flex:1 1 300px; min-width:0;">
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📋 สถานะปัจจุบัน</div>
                <div style="margin-top:10px;">
                    <div class="sv4-kv"><span>สถานะ</span><span><span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::orderStatusColor($order->status)) }}">{{ $order->status_label }}</span></span></div>
                    @if($order->tracking_number)
                        <div class="sv4-kv"><span>หมายเลขพัสดุ</span><span class="tp-num">{{ $order->tracking_number }}</span></div>
                    @endif
                    @if($order->shippingProvider)
                        <div class="sv4-kv"><span>ขนส่ง</span><span>{{ $order->shippingProvider->name }}</span></div>
                    @endif
                    @if($order->shipped_at)
                        <div class="sv4-kv"><span>จัดส่งเมื่อ</span><span>{{ $order->shipped_at->format('d/m/Y H:i') }}</span></div>
                    @endif
                </div>
                @if($order->tracking_number && $order->shippingProvider && $order->shippingProvider->getTrackingLink($order->tracking_number))
                    <a href="{{ $order->shippingProvider->getTrackingLink($order->tracking_number) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:12px;">🔍 ติดตามพัสดุ</a>
                @endif
            </div>

            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📍 ที่อยู่จัดส่ง</div>
                @if($shipping)
                    <div style="margin-top:10px; font-size:13px; line-height:1.7;">
                        <div style="font-weight:800;">{{ $shipping['name'] }}</div>
                        @if($shipping['phone'])<div class="tp-num" style="color:var(--ink2);">{{ $shipping['phone'] }}</div>@endif
                        <div style="margin-top:4px;">
                            {{ $shipping['address'] }}
                            @if($shipping['address_line_2'])<br>{{ $shipping['address_line_2'] }}@endif
                            <br>{{ trim(($shipping['subdistrict'] ?? '') . ' ' . ($shipping['district'] ?? '')) }}
                            <br>{{ $shipping['province'] }} {{ $shipping['postal_code'] }}
                        </div>
                    </div>
                @else
                    <div class="sv4-sub" style="margin-top:8px;">ไม่มีข้อมูลที่อยู่จัดส่ง</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── แท็บประวัติการจัดส่ง ───────────────────────────── --}}
    <div x-show="tab === 'history'" x-cloak class="flex" style="flex-wrap:wrap; gap:18px; align-items:flex-start;">
        <div class="tp-card" style="padding:20px; flex:1 1 340px; min-width:0;">
            <div class="sv4-h2">➕ เพิ่มความคืบหน้า</div>
            @if($isRider)
                <div class="sv4-note" style="margin-top:12px; --c:{{ SellerUi::INFO }};">ออเดอร์ไรเดอร์ ระบบอัปเดตความคืบหน้าให้อัตโนมัติตามสถานะไรเดอร์</div>
            @elseif(! $order->tracking_number)
                <div class="sv4-note" style="margin-top:12px; --c:{{ SellerUi::WARN }};">เพิ่มความคืบหน้าได้หลังกรอกเลขพัสดุแล้ว</div>
            @else
                <form action="{{ route('seller.orders.tracking.history', $order) }}" method="POST"
                      x-data="{ busy: false, st: @js(old('status', 'in_transit')) }" @submit="busy = true"
                      style="display:flex; flex-direction:column; gap:14px; margin-top:14px;">
                    @csrf
                    <div>
                        <label for="history_status" class="sv4-label">สถานะ <span class="req">*</span></label>
                        <select name="status" id="history_status" required class="tp-input" x-model="st">
                            @foreach($historyLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="sv4-hint" x-show="st === 'delivered'">เลือก "ส่งถึงแล้ว" = ยืนยันว่าลูกค้าได้รับสินค้า ระบบจะเริ่มนับวันปล่อยรายได้</div>
                    </div>
                    <div>
                        <label for="history_description" class="sv4-label">รายละเอียด <span class="req">*</span></label>
                        <textarea name="description" id="history_description" rows="3" required maxlength="500" class="tp-input"
                                  placeholder="เช่น สินค้าถึงศูนย์กระจายสินค้า กรุงเทพฯ">{{ old('description') }}</textarea>
                        @error('description')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="history_location" class="sv4-label">สถานที่</label>
                        <input type="text" name="location" id="history_location" maxlength="255" class="tp-input"
                               value="{{ old('location') }}" placeholder="เช่น ศูนย์กระจายสินค้า กรุงเทพฯ">
                    </div>
                    <button type="submit" class="tp-btn tp-btn-primary sv4-btn-block" :disabled="busy">
                        <span x-show="!busy">➕ เพิ่มประวัติ</span>
                        <span x-show="busy" x-cloak>กำลังบันทึก…</span>
                    </button>
                </form>
            @endif
        </div>

        <div class="tp-card" style="padding:20px; flex:1 1 340px; min-width:0;">
            <div class="sv4-h2">🕑 ประวัติการจัดส่ง</div>
            @if($order->trackingHistory->isEmpty())
                <x-seller-v4.empty icon="🕑" title="ยังไม่มีประวัติ" text="ความคืบหน้าการจัดส่งจะแสดงที่นี่" />
            @else
                <div style="margin-top:14px; display:flex; flex-direction:column;">
                    @foreach($order->trackingHistory as $history)
                        <div style="display:flex; gap:12px;">
                            <div style="display:flex; flex-direction:column; align-items:center;">
                                <span style="width:12px; height:12px; border-radius:50%; margin-top:4px; flex:none; background:{{ $loop->first ? 'var(--accent1)' : 'color-mix(in srgb, var(--ink2) 40%, transparent)' }}; box-shadow:var(--raise);"></span>
                                @if(! $loop->last)
                                    <span style="width:2px; flex:1; background:color-mix(in srgb, var(--ink2) 22%, transparent); margin:3px 0;"></span>
                                @endif
                            </div>
                            <div style="flex:1; min-width:0; padding-bottom:16px;">
                                <div style="font-weight:800; font-size:13px;">{{ $history->title ?: ($historyLabels[$history->status] ?? $history->status) }}</div>
                                @if($history->description)
                                    <div style="font-size:12.5px; margin-top:2px;">{{ $history->description }}</div>
                                @endif
                                @if($history->location)
                                    <div style="font-size:12px; color:var(--ink2);">📍 {{ $history->location }}</div>
                                @endif
                                <div class="tp-num" style="font-size:11px; color:var(--ink2); margin-top:3px;">
                                    {{ ($history->tracked_at ?? $history->created_at)?->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── แท็บแชทกับลูกค้า ─────────────────────────────────── --}}
    <div x-show="tab === 'chat'" x-cloak class="tp-card" style="padding:20px;">
        <div class="sv4-h2">💬 แชทกับลูกค้า</div>
        <div class="sv4-sub">ข้อความในออเดอร์นี้ ลูกค้าจะได้รับแจ้งเตือนเมื่อคุณตอบ</div>

        <div x-ref="chatBox" style="height:min(60vh, 420px); overflow-y:auto; margin-top:14px; padding:14px; border-radius:16px; box-shadow:var(--inset-sm); display:flex; flex-direction:column; gap:10px;">
            @forelse($order->messages as $message)
                @php
                    $mine = $message->sender_type === 'seller';
                    $system = (bool) $message->is_system_message;
                @endphp
                <div style="display:flex; justify-content:{{ $system ? 'center' : ($mine ? 'flex-end' : 'flex-start') }};">
                    <div style="max-width:78%; padding:10px 14px; border-radius:16px; font-size:13px; line-height:1.55; overflow-wrap:anywhere;
                        {{ $system
                            ? 'background:color-mix(in srgb, var(--ink2) 14%, transparent); color:var(--ink2); font-size:12px;'
                            : ($mine
                                ? 'background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:var(--tp-on-accent, #fff); border-bottom-right-radius:5px;'
                                : 'background:var(--card-bg); box-shadow:var(--card-shadow-sm); color:var(--ink); border-bottom-left-radius:5px;') }}">
                        @if(! $mine && ! $system)
                            <div style="font-size:11px; font-weight:700; color:var(--ink2); margin-bottom:3px;">{{ $message->sender?->name ?? 'ลูกค้า' }}</div>
                        @endif
                        <div style="white-space:pre-line;">{{ $message->message }}</div>
                        <div class="tp-num" style="font-size:10.5px; margin-top:4px; text-align:right; opacity:.75;">{{ $message->created_at->format('d/m H:i') }}</div>
                    </div>
                </div>
            @empty
                <x-seller-v4.empty icon="💬" title="ยังไม่มีข้อความ" text="เริ่มพูดคุยกับลูกค้าได้เลย เช่น แจ้งกำหนดส่งหรือสอบถามรายละเอียด" />
            @endforelse
        </div>

        <form action="{{ route('seller.orders.messages.send', $order) }}" method="POST"
              x-data="{ busy: false }" @submit="busy = true"
              style="display:flex; gap:10px; margin-top:14px;">
            @csrf
            <input type="text" name="message" required maxlength="2000" autocomplete="off" class="tp-input" placeholder="พิมพ์ข้อความถึงลูกค้า…" style="flex:1;">
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy">ส่ง ➤</button>
        </form>
        @error('message')<div class="sv4-err">{{ $message }}</div>@enderror
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * หน้าจัดการการจัดส่ง — สลับแท็บ + ทำเครื่องหมายอ่านแชทเมื่อเปิดแท็บแชท (ครั้งเดียว)
 */
function orderTracking(initialTab, readUrl, unreadCount) {
    return {
        tab: initialTab,
        unread: unreadCount,
        marked: false,
        init() {
            this.onTab();
        },
        go(name) {
            this.tab = name;
            this.onTab();
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', name);
                window.history.replaceState({}, '', url);
            } catch (e) {}
        },
        onTab() {
            if (this.tab !== 'chat') return;
            this.$nextTick(() => {
                const box = this.$refs.chatBox;
                if (box) box.scrollTop = box.scrollHeight;
            });
            if (this.marked || this.unread <= 0) return;
            this.marked = true;
            const token = document.querySelector('meta[name="csrf-token"]');
            fetch(readUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then((r) => { if (r.ok) this.unread = 0; }).catch(() => { this.marked = false; });
        }
    };
}
</script>
@endpush

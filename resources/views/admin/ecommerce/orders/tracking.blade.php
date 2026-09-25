@extends('layouts.admin-v4')

@section('title', 'จัดการการจัดส่ง #' . $order->order_number)

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'info' => 'var(--tp-info,#5689b8)',
    ];
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $row = 'display:flex; justify-content:space-between; gap:12px; font-size:13px; padding:6px 0;';
    $shipping = \App\Services\Shop\ShopPresenter::shipping($order);
    $unreadCount = $order->messages->where('is_read', false)->where('sender_type', '!=', 'admin')->count();
    $trackingLink = ($order->tracking_number && $order->shippingProvider) ? $order->shippingProvider->getTrackingLink($order->tracking_number) : null;
    $historyStatuses = [
        'processing' => 'กำลังเตรียมสินค้า',
        'shipped' => 'จัดส่งแล้ว',
        'in_transit' => 'อยู่ระหว่างขนส่ง',
        'out_for_delivery' => 'กำลังนำส่ง',
        'delivered' => 'ส่งถึงแล้ว',
        'pending' => 'รอดำเนินการ',
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{ tab: @js(request('tab') === 'chat' ? 'chat' : 'tracking'), readMarked: false,
               openChat() {
                   this.tab = 'chat';
                   if (this.readMarked) return;
                   this.readMarked = true;
                   fetch(@js(route('admin.ecommerce.orders.messages.read', $order)), {
                       method: 'POST',
                       headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                   }).catch(() => {});
               } }"
     x-init="if (tab === 'chat') { tab = 'tracking'; openChat(); }">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · การจัดส่ง</div>
            <h1 class="tp-num" style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0;">จัดการการจัดส่ง #{{ $order->order_number }}</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สถานะ: <strong style="color:var(--ink);">{{ $order->status_label }}</strong> · ลูกค้า: {{ $order->user?->name ?? 'ไม่ระบุ' }}</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="tp-btn tp-btn-sm"><i class="fas fa-receipt"></i> รายละเอียดออเดอร์</a>
            <a href="{{ route('admin.ecommerce.orders.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> รายการออเดอร์</a>
        </div>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $c['bad'] }};">
            <ul style="margin:0; padding-left:18px; font-size:13px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ===== แท็บ ===== --}}
    <div class="tp-card tp-inset-sm" style="padding:6px; display:flex; gap:6px; max-width:420px;">
        <button type="button" class="tp-seg" @click="tab = 'tracking'" :style="{ background: tab === 'tracking' ? 'var(--card-bg)' : '', boxShadow: tab === 'tracking' ? 'var(--raise)' : '' }">
            <i class="fas fa-truck"></i> ข้อมูลการจัดส่ง
        </button>
        <button type="button" class="tp-seg" @click="openChat()" :style="{ background: tab === 'chat' ? 'var(--card-bg)' : '', boxShadow: tab === 'chat' ? 'var(--raise)' : '' }">
            <i class="fas fa-comments"></i> แชทกับลูกค้า
            @if($unreadCount > 0)<span class="tp-pill tp-pill-gold tp-num" style="margin-left:4px;">{{ $unreadCount }}</span>@endif
        </button>
    </div>

    {{-- ===== แท็บจัดส่ง ===== --}}
    <div x-show="tab === 'tracking'" class="flex" style="flex-direction:column; gap:16px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:16px;">
            {{-- ฟอร์มเลขพัสดุ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-barcode" style="color:var(--accent1);"></i> ข้อมูลพัสดุ</div>
                <form method="POST" action="{{ route('admin.ecommerce.orders.tracking.update', $order) }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <label style="{{ $lbl }}">บริษัทขนส่ง <span style="color:{{ $c['bad'] }};">*</span></label>
                    <select name="shipping_provider_id" class="tp-input" required>
                        <option value="">-- เลือกบริษัทขนส่ง --</option>
                        @foreach($shippingProviders as $provider)
                            <option value="{{ $provider->id }}" @selected((int) old('shipping_provider_id', $order->shipping_provider_id) === (int) $provider->id)>
                                {{ $provider->name }}{{ $provider->name_en ? ' ('.$provider->name_en.')' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <label style="{{ $lbl }} margin-top:12px;">หมายเลขพัสดุ <span style="color:{{ $c['bad'] }};">*</span></label>
                    <input type="text" name="tracking_number" class="tp-input tp-num" maxlength="100" required
                           value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="เช่น TH1234567890">

                    <label style="{{ $lbl }} margin-top:12px;">วันที่คาดว่าจะถึง</label>
                    <input type="date" name="estimated_delivery_at" class="tp-input"
                           value="{{ old('estimated_delivery_at', $order->estimated_delivery_at?->format('Y-m-d')) }}">

                    <label style="{{ $lbl }} margin-top:12px;">เพิ่มบันทึกของแอดมิน <span style="font-weight:500;">(ต่อท้ายประวัติเดิม)</span></label>
                    @if($order->admin_notes)
                        <div class="tp-well" style="padding:10px 12px; margin-bottom:8px; font-size:12px; color:var(--ink2); white-space:pre-wrap; overflow-wrap:anywhere; max-height:140px; overflow:auto;">{{ $order->admin_notes }}</div>
                    @endif
                    <textarea name="admin_notes" rows="3" maxlength="500" class="tp-input" placeholder="ไม่บังคับ — ข้อความใหม่จะต่อท้ายบันทึกเดิม">{{ old('admin_notes') }}</textarea>

                    <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; margin-top:12px;" :disabled="busy">
                        <i class="fas fa-floppy-disk"></i> บันทึกข้อมูลการจัดส่ง
                    </button>
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">ออเดอร์ที่ยังไม่ส่งจะเปลี่ยนเป็น "จัดส่งแล้ว" อัตโนมัติ</div>
                </form>
            </div>

            {{-- สรุปการจัดส่ง --}}
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="tp-card">
                    <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-box" style="color:var(--accent1);"></i> สถานะปัจจุบัน</div>
                    <div style="{{ $row }}"><span style="color:var(--ink2);">สถานะ</span><span class="tp-pill tp-pill-soft">{{ $order->status_label }}</span></div>
                    <div style="{{ $row }}"><span style="color:var(--ink2);">เลขพัสดุ</span><span class="tp-num" style="font-weight:700;">{{ $order->tracking_number ?: '-' }}</span></div>
                    <div style="{{ $row }}"><span style="color:var(--ink2);">ขนส่ง</span><span>{{ $order->shippingProvider?->name ?? ($order->shipping_provider ?: '-') }}</span></div>
                    @if($order->shipped_at)<div style="{{ $row }}"><span style="color:var(--ink2);">ส่งเมื่อ</span><span class="tp-num">{{ $order->shipped_at->format('d/m/Y H:i') }}</span></div>@endif
                    @if($order->estimated_delivery_at)<div style="{{ $row }}"><span style="color:var(--ink2);">คาดว่าถึง</span><span class="tp-num">{{ $order->estimated_delivery_at->format('d/m/Y') }}</span></div>@endif
                    @if($trackingLink)
                        <a href="{{ $trackingLink }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:8px;"><i class="fas fa-up-right-from-square"></i> เปิดหน้าติดตามของขนส่ง</a>
                    @endif
                </div>

                <div class="tp-card">
                    <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-location-dot" style="color:var(--accent1);"></i> ที่อยู่จัดส่ง</div>
                    @if($shipping)
                        <div style="font-size:13px; line-height:1.7;">
                            <div style="font-weight:700;">{{ $shipping['name'] ?? '-' }} <span class="tp-num" style="font-weight:500; color:var(--ink2);">{{ $shipping['phone'] ?? '' }}</span></div>
                            <div>{{ $shipping['full_address'] ?? trim(implode(' ', array_filter([$shipping['address'] ?? null, $shipping['address_line_2'] ?? null, $shipping['subdistrict'] ?? null, $shipping['district'] ?? null, $shipping['province'] ?? null, $shipping['postal_code'] ?? null]))) }}</div>
                        </div>
                    @elseif($order->shippingAddress)
                        <div style="font-size:13px; line-height:1.7;">
                            <div style="font-weight:700;">{{ $order->shippingAddress->recipient_name }} <span class="tp-num" style="font-weight:500; color:var(--ink2);">{{ $order->shippingAddress->phone_number }}</span></div>
                            <div>{{ $order->shippingAddress->full_address }}</div>
                        </div>
                    @else
                        <div style="font-size:13px; color:var(--ink2);">ไม่มีข้อมูลที่อยู่จัดส่ง</div>
                    @endif
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:16px;">
            {{-- เพิ่มประวัติ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-plus" style="color:var(--accent1);"></i> เพิ่มประวัติการจัดส่ง</div>
                <form method="POST" action="{{ route('admin.ecommerce.orders.tracking.history', $order) }}"
                      x-data="{ st: 'in_transit', busy: false }"
                      @submit="if (st === 'delivered' && !confirm('ยืนยันว่าสินค้าส่งถึงลูกค้าแล้ว? ระบบจะเปลี่ยนสถานะออเดอร์เป็น ส่งถึงแล้ว')) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <label style="{{ $lbl }}">สถานะ <span style="color:{{ $c['bad'] }};">*</span></label>
                    <select name="status" x-model="st" class="tp-input" required>
                        @foreach($historyStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label style="{{ $lbl }} margin-top:12px;">รายละเอียด <span style="color:{{ $c['bad'] }};">*</span></label>
                    <textarea name="description" rows="3" maxlength="500" required class="tp-input" placeholder="เช่น สินค้าถึงศูนย์กระจายสินค้า กรุงเทพฯ"></textarea>
                    <label style="{{ $lbl }} margin-top:12px;">สถานที่</label>
                    <input type="text" name="location" maxlength="255" class="tp-input" placeholder="เช่น ศูนย์กระจายสินค้า กรุงเทพฯ">
                    <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; margin-top:12px;" :disabled="busy"><i class="fas fa-plus"></i> เพิ่มประวัติ</button>
                </form>
            </div>

            {{-- ไทม์ไลน์ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-timeline" style="color:var(--accent1);"></i> ประวัติการจัดส่ง</div>
                @forelse($order->trackingHistory as $history)
                    <div style="display:flex; gap:12px;">
                        <div style="display:flex; flex-direction:column; align-items:center;">
                            <span style="width:12px; height:12px; border-radius:50%; margin-top:4px; flex:none; background:{{ $loop->first ? 'linear-gradient(135deg,var(--accent1),var(--accent2))' : 'color-mix(in srgb, var(--ink2) 40%, transparent)' }};"></span>
                            @unless($loop->last)<span style="width:2px; flex:1; background:color-mix(in srgb, var(--ink2) 22%, transparent);"></span>@endunless
                        </div>
                        <div style="flex:1; padding-bottom:14px; min-width:0;">
                            <div style="font-weight:700; font-size:13.5px;">{{ $history->status_icon }} {{ $history->title }}</div>
                            @if($history->description)<div style="font-size:13px; overflow-wrap:anywhere;">{{ $history->description }}</div>@endif
                            @if($history->location)<div style="font-size:12px; color:var(--ink2);">📍 {{ $history->location }}</div>@endif
                            <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">
                                <span class="tp-num">{{ ($history->tracked_at ?? $history->created_at)?->format('d/m/Y H:i') }}</span>
                                @if($history->creator) · {{ $history->creator->name }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center; color:var(--ink2); padding:26px 0; font-size:13px;">ยังไม่มีประวัติการจัดส่ง</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== แท็บแชท ===== --}}
    <div x-show="tab === 'chat'" x-cloak class="tp-card">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-comments" style="color:var(--accent1);"></i> แชทกับลูกค้า</div>
        <div class="tp-well" style="height:380px; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:10px;">
            @forelse($order->messages as $message)
                @php $mine = $message->sender_type === 'admin'; @endphp
                <div style="display:flex; justify-content:{{ $mine ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width:78%; padding:10px 14px; border-radius:16px; {{ $mine ? 'background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:var(--tp-on-accent,#fff);' : 'background:var(--card-bg); box-shadow:var(--raise); color:var(--ink);' }}">
                        @unless($mine)
                            <div style="font-size:11px; font-weight:700; color:var(--ink2); margin-bottom:3px;">{{ $message->sender_name }}</div>
                        @endunless
                        <div style="font-size:13.5px; white-space:pre-wrap; overflow-wrap:anywhere;">{{ $message->message }}</div>
                        <div class="tp-num" style="font-size:10.5px; opacity:.75; text-align:right; margin-top:3px;">{{ $message->created_at?->format('d/m H:i') }}</div>
                    </div>
                </div>
            @empty
                <div style="margin:auto; text-align:center; color:var(--ink2); font-size:13px;">
                    <i class="fas fa-comment-slash" style="font-size:28px; display:block; margin-bottom:8px; opacity:.5;"></i>
                    ยังไม่มีข้อความ
                </div>
            @endforelse
        </div>
        <form method="POST" action="{{ route('admin.ecommerce.orders.messages.send', $order) }}"
              style="display:flex; gap:10px; margin-top:12px;" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <input type="text" name="message" maxlength="2000" required class="tp-input" placeholder="พิมพ์ข้อความถึงลูกค้า...">
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy"><i class="fas fa-paper-plane"></i> ส่ง</button>
        </form>
    </div>
</div>
@endsection

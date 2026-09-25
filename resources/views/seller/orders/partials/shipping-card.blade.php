{{--
 | การ์ดออเดอร์ในหน้าจัดการการจัดส่ง (ธีม V4)
 | ตัวแปร: $order, $mode = pending | shipped | delivered
 |         $actions = ปุ่มที่ร้านกดได้จาก SellerOrderService::allowedActions (หน้ารอจัดส่งส่งมาให้)
 --}}
@php
    $actions = $actions ?? [];
    $sellerItems = $order->items->where('seller_id', auth()->id());
    $sellerNet = $sellerItems->sum('seller_earning');
    $ship = \App\Services\Shop\ShopPresenter::shipping($order);
    $isRider = $order->isRiderDelivery();
    $rider = $isRider ? \App\Services\Shop\ShopPresenter::riderSummary($order) : null;
    $riderStatus = $rider['status'] ?? null;
    $ui = \App\Support\Seller\SellerUi::class;
    $providerName = $order->shippingProvider->name ?? $order->shipping_provider ?? null;
    $trackingLink = $order->tracking_number ? $order->tracking_url : null;
    $addressText = $ship
        ? ($ship['full_address'] ?: trim(implode(' ', array_filter([$ship['address'], $ship['address_line_2'], $ship['subdistrict'], $ship['district'], $ship['province'], $ship['postal_code']]))))
        : null;
@endphp

<div style="padding:18px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">

        {{-- ข้อมูลออเดอร์ --}}
        <div style="flex:1 1 320px; min-width:0;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:7px;">
                <a href="{{ route('seller.orders.show', $order) }}" class="sv4-link tp-num" style="font-size:15px;">#{{ $order->order_number }}</a>
                @if($mode === 'pending')
                    <span class="sv4-pill" style="{{ $ui::pill($ui::WARN) }}">{{ $isRider ? 'รอเรียกไรเดอร์' : 'รอกรอกเลขพัสดุ' }}</span>
                    @if($order->payment_status === 'paid')
                        <span class="sv4-pill" style="{{ $ui::pill($ui::OK) }}">✓ ชำระแล้ว</span>
                    @elseif($order->isCod())
                        <span class="sv4-pill" style="{{ $ui::pill($ui::WARN) }}">💵 เก็บเงินปลายทาง</span>
                    @endif
                @elseif($mode === 'shipped')
                    <span class="sv4-pill" style="{{ $ui::pill($ui::VIOLET) }}">🚚 กำลังจัดส่ง</span>
                @else
                    <span class="sv4-pill" style="{{ $ui::pill($ui::OK) }}">✅ {{ $order->status_label }}</span>
                @endif
                @if($isRider)
                    <span class="sv4-pill" style="{{ $ui::pill($ui::INFO) }}">🛵 ไรเดอร์</span>
                @endif
                @if($order->has_unread_messages)
                    <span class="sv4-pill" style="{{ $ui::pill($ui::BAD) }}">💬 ข้อความใหม่</span>
                @endif
            </div>

            {{-- พัสดุ / สถานะไรเดอร์ --}}
            @if($isRider && $rider)
                <div class="sv4-well" style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span style="font-size:20px;">🛵</span>
                    <span class="sv4-pill" style="{{ $ui::pill($ui::riderStatusColor($riderStatus)) }}">{{ $rider['status_label'] ?? '-' }}</span>
                    @if(! empty($rider['rider']['name']))
                        <span style="font-size:12.5px;">{{ $rider['rider']['name'] }}</span>
                    @endif
                    @if(! empty($rider['tracking_url']))
                        <a href="{{ $rider['tracking_url'] }}" target="_blank" rel="noopener" class="sv4-link" style="font-size:12.5px;">ติดตามสด →</a>
                    @endif
                </div>
            @elseif($order->tracking_number)
                <div class="sv4-well" style="margin-top:10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    @if($order->shippingProvider && $order->shippingProvider->logo)
                        <img src="{{ asset('storage/' . $order->shippingProvider->logo) }}" alt="{{ $providerName }}" style="width:40px; height:40px; object-fit:contain; border-radius:10px;">
                    @else
                        <span style="font-size:22px;">🚚</span>
                    @endif
                    <div style="min-width:0;">
                        <div style="font-size:12px; color:var(--ink2);">{{ $providerName ?: 'ขนส่ง' }}</div>
                        <div class="tp-num" style="font-weight:800; color:var(--deep1); overflow-wrap:anywhere;">{{ $order->tracking_number }}</div>
                    </div>
                    @if($trackingLink)
                        <a href="{{ $trackingLink }}" target="_blank" rel="noopener" class="sv4-link" style="font-size:12.5px; margin-left:auto;">🔍 ติดตามพัสดุ</a>
                    @endif
                </div>
            @endif

            <div style="display:flex; flex-direction:column; gap:5px; margin-top:10px; font-size:12.5px; color:var(--ink2);">
                <div>👤 <span style="color:var(--ink); font-weight:700;">{{ $order->user->name ?? 'ลูกค้า' }}</span>
                    @if($order->user?->phone) · <span class="tp-num">{{ $order->user->phone }}</span> @endif
                </div>
                @if($addressText && $mode === 'pending')
                    <div>📍 {{ $addressText }}</div>
                @endif
                <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                    <span>📦</span>
                    @foreach($sellerItems->take(3) as $item)
                        <span class="sv4-pill" style="background:color-mix(in srgb, var(--ink2) 13%, transparent); color:var(--ink); font-weight:600;">
                            {{ \Illuminate\Support\Str::limit($item->product_name ?: ($item->product->name ?? 'สินค้า'), 22) }} ×{{ $item->quantity }}
                        </span>
                    @endforeach
                    @if($sellerItems->count() > 3)
                        <span>+{{ $sellerItems->count() - 3 }} รายการ</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- รายได้ + ปุ่ม --}}
        <div style="flex:0 1 230px; min-width:200px; display:flex; flex-direction:column; gap:8px;">
            <div style="text-align:right;">
                <div style="font-size:11px; color:var(--ink2);">รายได้ร้าน</div>
                <div class="tp-num" style="font-size:19px; font-weight:800; color:{{ $ui::OK }};">฿{{ number_format($sellerNet, 2) }}</div>
            </div>

            @if($mode === 'pending')
                @if($isRider)
                    @if(in_array('request_rider', $actions, true))
                        {{-- งานเดิมยกเลิก/ล้มเหลว/หมดเวลา → เรียกใหม่ได้ (allowedActions ไม่มีงานที่ยังไม่จบ) --}}
                        @php $riderAgain = $riderStatus !== null && $riderStatus !== 'not_requested'; @endphp
                        <x-seller-v4.confirm-form :action="route('seller.orders.action', $order->id)"
                                                  :title="$riderAgain ? 'เรียกไรเดอร์อีกครั้ง?' : 'เรียกไรเดอร์ตอนนี้?'"
                                                  message="ระบบจะแจ้งไรเดอร์ใกล้ร้านให้มารับสินค้า กรุณาเตรียมสินค้าให้พร้อมก่อนกด"
                                                  confirm-label="🛵 เรียกไรเดอร์">
                            <input type="hidden" name="action" value="request_rider">
                            <button type="submit" class="tp-btn sv4-btn-ok sv4-btn-block">🛵 {{ $riderAgain ? 'เรียกไรเดอร์อีกครั้ง' : 'เรียกไรเดอร์' }}</button>
                        </x-seller-v4.confirm-form>
                    @elseif($riderStatus === 'not_requested' || in_array($riderStatus, \App\Models\RiderJob::TERMINAL_STATUSES, true))
                        {{-- ยังไม่มีงานที่วิ่งอยู่แต่ร้านเรียกไม่ได้ (ยังไม่เปิดส่งด้วยไรเดอร์/ยังไม่ปักหมุดจุดรับของ) --}}
                        <div class="sv4-well" style="font-size:12px; line-height:1.55; color:var(--ink2);">
                            ยังเรียกไรเดอร์ไม่ได้ — เปิด "ส่งด้วยไรเดอร์" และปักหมุดจุดรับของก่อน
                            <a href="{{ route('seller.store.settings') }}#rider" class="sv4-link" style="display:block; margin-top:4px;">ไปตั้งค่าไรเดอร์ →</a>
                        </div>
                    @endif
                @else
                    <a href="{{ route('seller.orders.tracking', $order->id) }}" class="tp-btn tp-btn-primary sv4-btn-block">📝 กรอกเลขพัสดุ</a>
                @endif
                <a href="{{ route('seller.orders.print', $order) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm sv4-btn-block">🖨️ พิมพ์ใบส่งของ</a>
            @elseif($mode === 'shipped')
                <a href="{{ route('seller.orders.tracking', $order->id) }}" class="tp-btn tp-btn-primary sv4-btn-block">📍 อัปเดตสถานะ</a>
            @else
                <a href="{{ route('seller.orders.tracking', $order->id) }}" class="tp-btn tp-btn-sm sv4-btn-block">📍 ประวัติการส่ง</a>
            @endif
            <a href="{{ route('seller.orders.tracking', ['orderId' => $order->id, 'tab' => 'chat']) }}" class="tp-btn tp-btn-sm sv4-btn-block">💬 แชท</a>
            <a href="{{ route('seller.orders.show', $order) }}" class="tp-btn tp-btn-sm sv4-btn-block">ดูรายละเอียด</a>
        </div>
    </div>

    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px; margin-top:12px; font-size:11.5px; color:var(--ink2);">
        @if($mode === 'pending')
            <span>สั่งซื้อเมื่อ {{ $order->created_at->format('d/m/Y H:i') }}</span>
            <span style="color:{{ $ui::WARN }}; font-weight:700;">รอมาแล้ว {{ $order->created_at->diffForHumans(null, true) }}</span>
        @elseif($mode === 'shipped')
            <span>จัดส่งเมื่อ {{ $order->shipped_at ? $order->shipped_at->format('d/m/Y H:i') : '-' }}</span>
            @if($order->estimated_delivery_at)
                <span style="color:{{ $ui::INFO }}; font-weight:700;">คาดว่าจะถึง {{ \Illuminate\Support\Carbon::parse($order->estimated_delivery_at)->format('d/m/Y') }}</span>
            @endif
        @else
            <span>สั่งซื้อเมื่อ {{ $order->created_at->format('d/m/Y') }}</span>
            <span style="color:{{ $ui::OK }}; font-weight:700;">ส่งถึงเมื่อ {{ $order->delivered_at ? $order->delivered_at->format('d/m/Y H:i') : '-' }}</span>
        @endif
    </div>
</div>

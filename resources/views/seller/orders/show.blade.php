@extends('layouts.seller-v4')

@section('title', 'คำสั่งซื้อ #' . $order->order_number)

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Services\Shop\ShopPresenter;
    use App\Support\Seller\SellerUi;

    $ship = ShopPresenter::shipping($order);
    $statusColor = SellerUi::orderStatusColor($order->status);
    $isRider = $order->isRiderDelivery();
    $actions = $allowedActions ?? [];
    $riderStatus = $riderSummary['status'] ?? null;
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header :title="'คำสั่งซื้อ #' . $order->order_number"
                        :subtitle="'สั่งเมื่อ ' . $order->created_at->format('d/m/Y H:i') . ' · ' . $paymentMethodLabel"
                        icon="🧾" :back="route('seller.orders.index')">
        <span class="sv4-pill" style="{{ SellerUi::pill($statusColor) }} font-size:12px; padding:7px 12px;">{{ $order->status_label }}</span>
        <a href="{{ route('seller.orders.tracking', $order->id) }}" class="tp-btn tp-btn-sm">📍 การจัดส่ง & แชท</a>
        <a href="{{ route('seller.orders.print', $order) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm">🖨️ พิมพ์ใบส่งของ</a>
    </x-seller-v4.header>

    <x-seller-v4.errors />

    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">

        {{-- ── คอลัมน์ซ้าย: สินค้า + การจัดการ ───────────────── --}}
        <div style="display:flex; flex-direction:column; gap:18px; min-width:0; flex:2 1 460px;">

            {{-- ปุ่มจัดการออเดอร์ (แสดงเฉพาะปุ่มที่ทำได้จริงตามสถานะ — ตรวจซ้ำฝั่งเซิร์ฟเวอร์) --}}
            @if(! empty($actions))
                <div class="tp-card" style="padding:20px;">
                    <div class="sv4-h2">⚡ จัดการคำสั่งซื้อ</div>
                    <div class="sv4-sub">ทำตามขั้นตอน: ยืนยันรับออเดอร์ → {{ $isRider ? 'เรียกไรเดอร์มารับของ' : 'กรอกเลขพัสดุ' }} → ส่งถึงลูกค้า</div>

                    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:14px;">
                        @if(in_array('confirm', $actions, true))
                            <form action="{{ route('seller.orders.action', $order->id) }}" method="POST"
                                  x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy">✅ ยืนยันรับคำสั่งซื้อ</button>
                            </form>
                        @endif

                        @if(in_array('request_rider', $actions, true))
                            <x-seller-v4.confirm-form :action="route('seller.orders.action', $order->id)"
                                                      title="เรียกไรเดอร์ตอนนี้?"
                                                      message="ระบบจะแจ้งไรเดอร์ที่อยู่ใกล้ร้านให้มารับสินค้าที่จุดรับของ กรุณาเตรียมสินค้าให้พร้อมก่อนกด"
                                                      confirm-label="🛵 เรียกไรเดอร์">
                                <input type="hidden" name="action" value="request_rider">
                                <button type="submit" class="tp-btn sv4-btn-ok">🛵 เรียกไรเดอร์มารับของ</button>
                            </x-seller-v4.confirm-form>
                        @endif

                        @if(in_array('deliver', $actions, true))
                            <x-seller-v4.confirm-form :action="route('seller.orders.action', $order->id)"
                                                      title="ยืนยันว่าลูกค้าได้รับสินค้าแล้ว?"
                                                      message="หลังยืนยัน ระบบจะเริ่มนับวันปล่อยรายได้เข้ากระเป๋าของร้าน"
                                                      confirm-label="📦 ยืนยันส่งถึง">
                                <input type="hidden" name="action" value="deliver">
                                <button type="submit" class="tp-btn">📦 ยืนยันส่งถึงแล้ว</button>
                            </x-seller-v4.confirm-form>
                        @endif
                    </div>

                    @if(in_array('ship', $actions, true))
                        <hr class="sv4-divider" style="margin:18px 0;">
                        <form action="{{ route('seller.orders.action', $order->id) }}" method="POST"
                              x-data="{ busy: false }" @submit="busy = true" style="display:flex; flex-direction:column; gap:14px;">
                            @csrf
                            <input type="hidden" name="action" value="ship">
                            <div class="sv4-h2" style="font-size:14px;">🚚 กรอกเลขพัสดุและเปลี่ยนเป็น "จัดส่งแล้ว"</div>
                            <div class="sv4-grid">
                                <div>
                                    <label for="shipping_provider_id" class="sv4-label">บริษัทขนส่ง <span class="req">*</span></label>
                                    <select name="shipping_provider_id" id="shipping_provider_id" required class="tp-input">
                                        <option value="">— เลือกบริษัทขนส่ง —</option>
                                        @foreach($shippingProviders as $provider)
                                            <option value="{{ $provider->id }}" @selected(old('shipping_provider_id') == $provider->id)>{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('shipping_provider_id')<div class="sv4-err">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="tracking_number" class="sv4-label">เลขพัสดุ <span class="req">*</span></label>
                                    <input type="text" name="tracking_number" id="tracking_number" required maxlength="100"
                                           value="{{ old('tracking_number') }}" class="tp-input tp-num" placeholder="เช่น TH1234567890" autocomplete="off">
                                    @error('tracking_number')<div class="sv4-err">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="estimated_delivery_at" class="sv4-label">วันที่คาดว่าจะถึง</label>
                                    <input type="date" name="estimated_delivery_at" id="estimated_delivery_at" min="{{ now()->toDateString() }}"
                                           value="{{ old('estimated_delivery_at') }}" class="tp-input">
                                    @error('estimated_delivery_at')<div class="sv4-err">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="tp-btn tp-btn-primary" :disabled="busy">
                                    <span x-show="!busy">💾 บันทึกเลขพัสดุ</span>
                                    <span x-show="busy" x-cloak>กำลังบันทึก…</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    @if(in_array('cancel', $actions, true))
                        <hr class="sv4-divider" style="margin:18px 0;">
                        <x-seller-v4.confirm-form :action="route('seller.orders.action', $order->id)"
                                                  title="ยกเลิกคำสั่งซื้อนี้?"
                                                  message="ถ้าลูกค้าชำระเงินแล้ว ระบบจะคืนเงินให้ลูกค้าทันที และคืนสต็อกสินค้า การยกเลิกย้อนกลับไม่ได้"
                                                  confirm-label="ยกเลิกคำสั่งซื้อ" danger
                                                  style="display:flex; flex-direction:column; gap:10px;">
                            <input type="hidden" name="action" value="cancel">
                            <label for="cancel_reason" class="sv4-label" style="margin:0;">ยกเลิกคำสั่งซื้อ <span style="font-weight:500; color:var(--ink2);">(เช่น สินค้าหมด)</span></label>
                            <input type="text" name="reason" id="cancel_reason" required minlength="3" maxlength="500"
                                   value="{{ old('reason') }}" class="tp-input" placeholder="ระบุเหตุผลที่ยกเลิก (ลูกค้าจะเห็นข้อความนี้)">
                            @error('reason')<div class="sv4-err">{{ $message }}</div>@enderror
                            <div><button type="submit" class="tp-btn sv4-btn-danger">✕ ยกเลิกคำสั่งซื้อ</button></div>
                        </x-seller-v4.confirm-form>
                    @endif
                </div>
            @elseif(! in_array($order->status, ['cancelled', 'refunded', 'completed', 'delivered', 'shipped'], true) && $order->payment_status !== 'paid' && ! $order->isCod())
                <div class="sv4-note" style="--c:{{ SellerUi::WARN }};">
                    ⏳ คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน — รอลูกค้าชำระก่อน จึงเตรียมและจัดส่งสินค้าได้
                </div>
            @endif

            {{-- ส่งด้วยไรเดอร์ --}}
            @if($riderSummary)
                <div class="tp-card" style="padding:20px;">
                    <div class="sv4-row" style="flex-wrap:wrap;">
                        <div class="sv4-h2">🛵 ส่งด้วยไรเดอร์</div>
                        <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::riderStatusColor($riderStatus)) }}">{{ $riderSummary['status_label'] ?? '-' }}</span>
                    </div>
                    @if($riderStatus === 'not_requested')
                        <div class="sv4-sub" style="margin-top:8px;">เตรียมสินค้าให้พร้อม แล้วกด "เรียกไรเดอร์มารับของ" ด้านบน ระบบจะหาไรเดอร์ใกล้ร้านให้อัตโนมัติ</div>
                    @endif
                    @if(! empty($riderSummary['job_number']))
                        <div class="sv4-kv" style="margin-top:10px;"><span>เลขงาน</span><span class="tp-num">{{ $riderSummary['job_number'] }}</span></div>
                    @endif
                    @if(! empty($riderSummary['rider']))
                        <div class="sv4-well" style="margin-top:10px; display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:20px;">🛵</span>
                            <div style="min-width:0; flex:1;">
                                <div style="font-weight:800;">{{ $riderSummary['rider']['name'] ?? 'ไรเดอร์' }}</div>
                                <div style="font-size:12px; color:var(--ink2);">
                                    {{ $riderSummary['rider']['vehicle_type'] ?? '' }}
                                    @if(! empty($riderSummary['rider']['vehicle_plate'])) · ทะเบียน {{ $riderSummary['rider']['vehicle_plate'] }} @endif
                                </div>
                            </div>
                            @if(! empty($riderSummary['rider']['phone']))
                                <a href="tel:{{ $riderSummary['rider']['phone'] }}" class="tp-btn tp-btn-sm">📞 โทร</a>
                            @endif
                        </div>
                    @endif
                    @if(! empty($riderSummary['tracking_url']))
                        <a href="{{ $riderSummary['tracking_url'] }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:12px;">🗺️ ติดตามไรเดอร์แบบสด</a>
                    @endif
                    @if(! empty($riderSummary['delivered_at']))
                        <div class="sv4-kv" style="margin-top:8px;"><span>ส่งถึงเมื่อ</span><span>{{ \Illuminate\Support\Carbon::parse($riderSummary['delivered_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span></div>
                    @endif
                </div>
            @endif

            {{-- รายการสินค้าของร้าน --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📦 รายการสินค้าของร้านคุณ</div>
                <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
                    @foreach($sellerItems as $item)
                        @php
                            $itemImage = ShopPresenter::imageUrl($item->product_image ?: $item->product?->main_image_url);
                        @endphp
                        <div class="sv4-well" style="display:flex; gap:13px; align-items:center;">
                            @if($itemImage)
                                <img src="{{ $itemImage }}" alt="รูปสินค้า {{ $item->product_name }}" class="sv4-thumb" style="width:60px; height:60px;" loading="lazy">
                            @else
                                <span class="sv4-thumb" style="width:60px; height:60px;">📦</span>
                            @endif
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:800; overflow-wrap:anywhere;">{{ $item->product_name }}</div>
                                @if($item->product_sku)
                                    <div style="font-size:11px; color:var(--ink2);">SKU: {{ $item->product_sku }}</div>
                                @endif
                                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px; font-size:12.5px; color:var(--ink2);">
                                    <span class="tp-num">฿{{ number_format((float) $item->unit_price, 2) }} × {{ $item->quantity }}</span>
                                    <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::orderStatusColor($item->status)) }}">{{ SellerUi::itemStatusLabel($item->status) }}</span>
                                </div>
                            </div>
                            <div class="tp-num" style="font-weight:800; white-space:nowrap;">฿{{ number_format((float) $item->total, 2) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="sv4-well" style="margin-top:16px;">
                    <div class="sv4-kv"><span>ยอดขายของร้าน</span><span class="tp-num">฿{{ number_format($sellerTotal, 2) }}</span></div>
                    <div class="sv4-kv"><span>ค่า GP แพลตฟอร์ม</span>
                        <span class="tp-num" style="color:{{ $sellerCommission > 0 ? SellerUi::BAD : SellerUi::OK }};">
                            {{ $sellerCommission > 0 ? '−฿' . number_format($sellerCommission, 2) : 'ฟรี ฿0.00' }}
                        </span>
                    </div>
                    <hr class="sv4-divider" style="margin:6px 0;">
                    <div class="sv4-kv" style="font-size:15px;"><span style="color:var(--ink); font-weight:800;">รายได้สุทธิของร้าน</span>
                        <span class="tp-num" style="color:{{ SellerUi::OK }}; font-size:18px;">฿{{ number_format($sellerEarning, 2) }}</span>
                    </div>
                    <div class="sv4-hint">รายได้เข้ากระเป๋าหลังลูกค้าได้รับสินค้าและครบระยะพักเงิน ดูได้ที่ <a href="{{ route('seller.wallet.index') }}" class="sv4-link">กระเป๋าเงิน</a></div>
                </div>
            </div>

            {{-- ข้อมูลพัสดุ --}}
            @if($order->tracking_number)
                <div class="tp-card" style="padding:20px;">
                    <div class="sv4-h2">🚚 ข้อมูลการจัดส่ง</div>
                    <div style="margin-top:10px;">
                        <div class="sv4-kv"><span>บริษัทขนส่ง</span><span>{{ $order->shipping_provider ?: '-' }}</span></div>
                        <div class="sv4-kv"><span>เลขพัสดุ</span><span class="tp-num">{{ $order->tracking_number }}</span></div>
                        @if($order->shipped_at)
                            <div class="sv4-kv"><span>วันที่จัดส่ง</span><span>{{ $order->shipped_at->format('d/m/Y H:i') }}</span></div>
                        @endif
                    </div>
                    @if($order->tracking_url)
                        <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:10px;">🔍 ติดตามพัสดุ</a>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── คอลัมน์ขวา: ลูกค้า / ที่อยู่ / สถานะ ────────────── --}}
        <div style="display:flex; flex-direction:column; gap:18px; min-width:0; flex:1 1 300px;">
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">👤 ข้อมูลลูกค้า</div>
                <div style="margin-top:10px;">
                    <div class="sv4-kv"><span>ชื่อ</span><span>{{ $order->user->name ?? '-' }}</span></div>
                    @if($order->user?->phone)
                        <div class="sv4-kv"><span>เบอร์โทร</span><span><a href="tel:{{ $order->user->phone }}" class="sv4-link tp-num">{{ $order->user->phone }}</a></span></div>
                    @endif
                    @if($order->user?->email)
                        <div class="sv4-kv"><span>อีเมล</span><span style="font-weight:500;">{{ $order->user->email }}</span></div>
                    @endif
                </div>
                <a href="{{ route('seller.orders.tracking', ['orderId' => $order->id, 'tab' => 'chat']) }}" class="tp-btn tp-btn-sm" style="margin-top:12px;">💬 แชทกับลูกค้า</a>
            </div>

            {{-- ที่อยู่จัดส่ง (snapshot ณ เวลาสั่ง — ลูกค้าแก้ที่อยู่ทีหลังไม่กระทบออเดอร์นี้) --}}
            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📍 ที่อยู่จัดส่ง</div>
                @if($ship)
                    <div style="margin-top:10px; font-size:13px; line-height:1.7;">
                        <div style="font-weight:800;">{{ $ship['name'] }}</div>
                        @if($ship['phone'])<div class="tp-num" style="color:var(--ink2);">{{ $ship['phone'] }}</div>@endif
                        <div style="margin-top:6px;">
                            {{ $ship['address'] }}
                            @if($ship['address_line_2'])<br>{{ $ship['address_line_2'] }}@endif
                            <br>{{ trim(($ship['subdistrict'] ?? '') . ' ' . ($ship['district'] ?? '')) }}
                            <br>{{ $ship['province'] }} {{ $ship['postal_code'] }}
                        </div>
                        @if($ship['notes'])
                            <div class="sv4-note" style="margin-top:10px; --c:{{ SellerUi::INFO }};">📝 {{ $ship['notes'] }}</div>
                        @endif
                        @if($ship['latitude'] && $ship['longitude'])
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $ship['latitude'] }},{{ $ship['longitude'] }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:10px;">🗺️ เปิดแผนที่</a>
                        @endif
                    </div>
                @else
                    <div class="sv4-sub" style="margin-top:8px;">ไม่มีข้อมูลที่อยู่จัดส่ง</div>
                @endif
            </div>

            <div class="tp-card" style="padding:20px;">
                <div class="sv4-h2">📋 สถานะคำสั่งซื้อ</div>
                <div style="margin-top:10px;">
                    <div class="sv4-kv"><span>สถานะ</span><span><span class="sv4-pill" style="{{ SellerUi::pill($statusColor) }}">{{ $order->status_label }}</span></span></div>
                    <div class="sv4-kv"><span>วันที่สั่งซื้อ</span><span>{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
                    <div class="sv4-kv"><span>วิธีชำระเงิน</span><span>{{ $paymentMethodLabel }}</span></div>
                    <div class="sv4-kv"><span>การชำระเงิน</span>
                        <span>
                            @if($order->payment_status === 'paid')
                                <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::OK) }}">✓ ชำระแล้ว</span>
                            @elseif($order->isCod())
                                <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::WARN) }}">💵 เก็บเงินปลายทาง</span>
                            @else
                                <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::WARN) }}">{{ ShopPresenter::paymentStatusLabel($order->payment_status) }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="sv4-kv"><span>การจัดส่ง</span><span>{{ $isRider ? '🛵 ไรเดอร์' : '📦 พัสดุ' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

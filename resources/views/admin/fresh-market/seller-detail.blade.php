{{--
 | รายละเอียดผู้ขายตลาดสด (admin.fresh-market.sellers.show) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@showSeller:
 |   $seller (with user, listings 20 ล่าสุด, orders 20 ล่าสุด + buyer), $orderStats [order_status => count], $gpDebt (float),
 |   $gpDebts (WalletDebt ค่า GP 20 ล่าสุด), $payoutTotal (float — โอนให้ร้านแล้วทั้งหมด)
 | การกระทำ (ฟอร์ม POST ผ่านโมดัลยืนยัน): sellers.verify / sellers.suspend {reason?} / sellers.activate
--}}
@extends('layouts.admin-v4')

@section('title', $seller->shop_name.' · ผู้ขายตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $statusOrder = ['pending', 'accepted', 'preparing', 'ready', 'delivering', 'delivered', 'completed', 'cancelled', 'delivery_failed'];
    $totalOrders = array_sum($orderStats);
    $subscriptionLabel = ['free' => 'ฟรี', 'monthly' => 'รายเดือน', 'trial' => 'ทดลองใช้'][$seller->subscription_type] ?? ($seller->subscription_type ?: '-');
    $infoRows = [
        ['เจ้าของ', ($seller->user?->name ?? '-').' · ผู้ใช้ #'.$seller->user_id],
        ['อีเมล', $seller->user?->email ?? '-'],
        ['ที่อยู่ร้าน', $seller->address ?: '-'],
        ['พื้นที่', collect([$seller->sub_district, $seller->district, $seller->province])->filter()->implode(', ') ?: '-'],
        ['พิกัดรับของ', $seller->hasPickupLocation() ? number_format((float) $seller->latitude, 5).', '.number_format((float) $seller->longitude, 5) : 'ยังไม่ตั้ง (เรียกไรเดอร์ไม่ได้)'],
        ['LINE', $seller->line_display_name ?: ($seller->line_user_id ? 'เชื่อมแล้ว' : 'ยังไม่เชื่อม')],
        ['สมาชิก', $subscriptionLabel.($seller->subscription_expires_at ? ' · หมดอายุ '.$seller->subscription_expires_at->thaidate('j M Y') : '')],
        ['รหัสแนะนำ', $seller->referral_code ?: '-'],
        ['สมัครเมื่อ', $seller->created_at?->thaidate('j M Y H:i') ?? '-'],
    ];
    // ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด) — เปิดร้านตามจุดที่ตั้งวันนี้ + แชร์ตำแหน่งสดได้
    if (method_exists($seller, 'isMobileShop')) {
        $isMobileShop = $seller->isMobileShop();
        $infoRows[] = ['รูปแบบร้าน', $isMobileShop ? 'ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)' : 'ร้านประจำที่'];
        if ($isMobileShop) {
            $openNow = method_exists($seller, 'isOpenNow') && $seller->isOpenNow();
            $infoRows[] = ['เปิดร้านตอนนี้', $openNow
                ? 'เปิดอยู่'.($seller->location_label ? ' ที่ '.$seller->location_label : '').($seller->closes_at ? ' · ปิด '.$seller->closes_at->format('H:i').' น.' : '')
                : 'ปิดอยู่'];
            $infoRows[] = ['แชร์ตำแหน่งสด', $seller->live_location_sharing ? 'เปิด' : 'ปิด'];
        }
    }
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
            <a href="{{ route('admin.fresh-market.sellers') }}" class="tp-icon-btn" title="กลับรายชื่อผู้ขาย"><i class="fas fa-arrow-left"></i></a>
            @if ($seller->shop_image)
                <img src="{{ \Illuminate\Support\Str::startsWith($seller->shop_image, ['http://', 'https://']) ? $seller->shop_image : asset('storage/'.$seller->shop_image) }}" alt=""
                     style="width:54px; height:54px; border-radius:16px; object-fit:cover; box-shadow:var(--raise);">
            @else
                <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:22px; font-weight:800;">{{ mb_substr($seller->shop_name ?: 'ร', 0, 1) }}</span>
            @endif
            <div style="min-width:0;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ร้าน #{{ $seller->id }}</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0; display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
                    {{ $seller->shop_name }}
                    @include('admin.riders.partials.status', ['statusKind' => 'seller', 'statusValue' => $seller->status_key, 'statusLabel' => $seller->status_label])
                    @if ($seller->is_verified)
                        @include('admin.riders.partials.pill', ['pillTone' => 'info', 'pillText' => 'ยืนยันแล้ว', 'pillIcon' => 'fa-certificate', 'pillTitle' => null])
                    @endif
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    @if ($seller->phone)<a href="tel:{{ $seller->phone }}" class="w1-link">{{ $seller->phone }}</a> · @endif
                    {{ $seller->isVisibleToBuyers() ? 'ผู้ซื้อเห็นร้านอยู่' : 'ผู้ซื้อไม่เห็นร้านนี้ตอนนี้' }}
                </div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <a href="{{ route('admin.fresh-market.listings', ['seller_id' => $seller->id]) }}" class="tp-btn tp-btn-sm"><i class="fas fa-carrot"></i> สินค้าทั้งหมด</a>
            <a href="{{ route('admin.fresh-market.orders', ['search' => $seller->shop_name]) }}" class="tp-btn tp-btn-sm"><i class="fas fa-receipt"></i> ออเดอร์ทั้งหมด</a>
            @if (! $seller->is_verified)
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-on); background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 72%, var(--ink)));"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.sellers.verify', $seller), 'title' => 'ยืนยันร้าน '.$seller->shop_name, 'message' => 'ร้านจะได้รับแจ้งเตือน และสินค้าจะแสดงให้ผู้ซื้อเห็น', 'reason' => 'none', 'confirm' => 'ยืนยันร้าน', 'tone' => 'ok', 'icon' => 'fa-certificate']))">
                    <i class="fas fa-certificate"></i> ยืนยันร้าน
                </button>
            @endif
            @if ($seller->is_suspended || ! $seller->is_active)
                <button type="button" class="tp-btn tp-btn-sm tp-btn-primary"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.sellers.activate', $seller), 'title' => 'เปิดใช้งานร้าน', 'message' => 'ร้านกลับมาขายได้ตามปกติ ร้านจะได้รับแจ้งเตือน', 'reason' => 'none', 'confirm' => 'เปิดใช้งาน', 'tone' => 'ok', 'icon' => 'fa-store']))">
                    <i class="fas fa-store"></i> เปิดใช้งานร้าน
                </button>
            @else
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-bad);"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.sellers.suspend', $seller), 'title' => 'ระงับร้าน '.$seller->shop_name, 'message' => 'สินค้าของร้านจะถูกซ่อนจากผู้ซื้อทันที ออเดอร์ที่ค้างอยู่ยังต้องจัดการต่อ', 'reason' => 'optional', 'reasonLabel' => 'เหตุผลที่ระงับ (ร้านจะเห็น)', 'confirm' => 'ระงับร้าน', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                    <i class="fas fa-ban"></i> ระงับร้าน
                </button>
            @endif
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== ตัวเลข ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-carrot', 'kpiValue' => number_format((int) $seller->total_listings), 'kpiLabel' => 'สินค้า', 'kpiTone' => null, 'kpiHref' => route('admin.fresh-market.listings', ['seller_id' => $seller->id]), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-receipt', 'kpiValue' => number_format($totalOrders), 'kpiLabel' => 'ออเดอร์ทั้งหมด', 'kpiTone' => 'info', 'kpiHref' => null, 'kpiHint' => 'สำเร็จ '.number_format($orderStats['completed'] ?? 0), 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-sack-dollar', 'kpiValue' => '฿'.number_format((float) $seller->total_revenue, 2), 'kpiLabel' => 'รายได้สุทธิร้าน (สำเร็จ)', 'kpiTone' => 'ok', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-money-bill-transfer', 'kpiValue' => '฿'.number_format($payoutTotal, 2), 'kpiLabel' => 'โอนเข้าวอลเลตร้านแล้ว', 'kpiTone' => 'violet', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-file-invoice-dollar', 'kpiValue' => '฿'.number_format($gpDebt, 2), 'kpiLabel' => 'ค่า GP ค้างชำระ', 'kpiTone' => $gpDebt > 0 ? 'warn' : null, 'kpiHref' => null, 'kpiHint' => $gpDebt > 0 ? 'หักอัตโนมัติเมื่อมีเงินเข้าวอลเลต' : null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-star', 'kpiValue' => number_format((float) $seller->rating_average, 2), 'kpiLabel' => 'คะแนนร้าน', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => number_format((int) $seller->rating_count).' รีวิว', 'kpiPulse' => false])
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr)); gap:16px; align-items:start;">
        {{-- ข้อมูลร้าน --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-circle-info"></i> ข้อมูลร้าน</div>
            @if ($seller->shop_description)
                <p style="font-size:13px; color:var(--ink2); margin:0 0 12px; white-space:pre-line;">{{ $seller->shop_description }}</p>
            @endif
            <div style="display:grid; grid-template-columns:minmax(90px,auto) 1fr; gap:8px 14px; font-size:13px;">
                @foreach ($infoRows as [$infoLabel, $infoValue])
                    <span style="color:var(--ink2);">{{ $infoLabel }}</span>
                    <span style="font-weight:600; word-break:break-word;">{{ $infoValue }}</span>
                @endforeach
            </div>
            @if ($seller->hasPickupLocation())
                <a href="https://www.openstreetmap.org/?mlat={{ (float) $seller->latitude }}&mlon={{ (float) $seller->longitude }}#map=17/{{ (float) $seller->latitude }}/{{ (float) $seller->longitude }}"
                   target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:12px;"><i class="fas fa-map-location-dot"></i> ดูตำแหน่งร้านบนแผนที่</a>
            @endif
        </div>

        {{-- ออเดอร์ตามสถานะ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-chart-pie"></i> ออเดอร์ตามสถานะ</div>
            @if ($totalOrders === 0)
                <p style="font-size:13px; color:var(--ink2); margin:0;">ยังไม่มีออเดอร์</p>
            @else
                <div style="display:flex; flex-direction:column; gap:9px;">
                    @foreach ($statusOrder as $statusKey)
                        @continue(empty($orderStats[$statusKey]))
                        <a href="{{ route('admin.fresh-market.orders', ['status' => $statusKey, 'search' => $seller->shop_name]) }}" style="text-decoration:none; color:var(--ink);">
                            <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:4px;">
                                <span>{{ \App\Models\FreshMarketOrder::statusLabel($statusKey) }}</span>
                                <b class="tp-num">{{ number_format($orderStats[$statusKey]) }}</b>
                            </div>
                            <div class="tp-inset-sm" style="height:8px; border-radius:8px; overflow:hidden;">
                                <div style="height:100%; width:{{ round($orderStats[$statusKey] / $totalOrders * 100, 1) }}%; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- หนี้ค่า GP --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-file-invoice-dollar"></i> ค่า GP จากออเดอร์เก็บเงินปลายทาง</div>
            <div style="font-size:12px; color:var(--ink2); margin-bottom:10px;">ออเดอร์ COD ร้านรับเงินสดเอง ระบบจึงตั้งเป็นยอดค้างแล้วหักจากวอลเลตร้านภายหลัง</div>
            @forelse ($gpDebts as $debt)
                <div class="tp-well" style="padding:9px 12px; margin-bottom:7px; display:flex; justify-content:space-between; gap:10px; align-items:center; font-size:12.5px;">
                    <div style="min-width:0;">
                        @if ($debt->source_id)
                            <a href="{{ route('admin.fresh-market.orders.show', $debt->source_id) }}" class="w1-link">ออเดอร์ #{{ $debt->source_id }}</a>
                        @endif
                        <div style="color:var(--ink2); font-size:11.5px;">{{ $debt->created_at?->thaidate('j M Y H:i') }}</div>
                    </div>
                    <div style="text-align:right;">
                        <b class="tp-num">฿{{ number_format((float) $debt->remaining_amount, 2) }}</b>
                        <div style="font-size:11px; color:var(--ink2);">จาก ฿{{ number_format((float) $debt->original_amount, 2) }} · {{ $debt->status_label }}</div>
                    </div>
                </div>
            @empty
                <p style="font-size:13px; color:var(--ink2); margin:0;">ไม่มีค่า GP ค้าง</p>
            @endforelse
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,460px),1fr)); gap:16px; align-items:start;">
        {{-- สินค้าล่าสุด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
                <div class="tp-section-h"><i class="fas fa-carrot"></i> สินค้าล่าสุด</div>
                <a href="{{ route('admin.fresh-market.listings', ['seller_id' => $seller->id]) }}" class="w1-link" style="font-size:12.5px;">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:440px; border-collapse:collapse; font-size:13px;">
                    <tbody>
                        @forelse ($seller->listings as $listing)
                            <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                <td style="padding:10px 18px;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        @if ($listing->primary_image)
                                            <img src="{{ $listing->primary_image }}" alt="" loading="lazy" style="width:38px; height:38px; border-radius:10px; object-fit:cover; flex:none;">
                                        @else
                                            <span class="tp-well" style="width:38px; height:38px; border-radius:10px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                                        @endif
                                        <div style="min-width:0;">
                                            <a href="{{ route('admin.fresh-market.listings.show', $listing) }}" class="w1-link" style="color:var(--ink);">{{ $listing->title }}</a>
                                            <div style="font-size:11.5px; color:var(--ink2);">฿{{ number_format((float) $listing->price, 2) }} / {{ $listing->unit }} · เหลือ {{ number_format((int) $listing->quantity_available) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:10px 18px; text-align:right;">@include('admin.riders.partials.status', ['statusKind' => 'listing', 'statusValue' => $listing->status, 'statusLabel' => null])</td>
                            </tr>
                        @empty
                            <tr><td style="padding:28px; text-align:center; color:var(--ink2);">ยังไม่มีสินค้า</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ออเดอร์ล่าสุด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
                <div class="tp-section-h"><i class="fas fa-receipt"></i> ออเดอร์ล่าสุด</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:460px; border-collapse:collapse; font-size:13px;">
                    <tbody>
                        @forelse ($seller->orders as $order)
                            <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                <td style="padding:10px 18px;">
                                    <a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="w1-link tp-num">#{{ $order->order_number }}</a>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $order->buyer?->name ?? '-' }} · {{ $order->created_at?->thaidate('j M H:i') }}</div>
                                </td>
                                <td style="padding:10px 12px; text-align:right;" class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td style="padding:10px 18px; text-align:right;">@include('admin.riders.partials.status', ['statusKind' => 'fm_order', 'statusValue' => $order->order_status, 'statusLabel' => null])</td>
                            </tr>
                        @empty
                            <tr><td style="padding:28px; text-align:center; color:var(--ink2);">ยังไม่มีออเดอร์</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

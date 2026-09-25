{{--
 | รายละเอียดสินค้าตลาดสด (admin.fresh-market.listings.show) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@showListing: $listing (with seller.user, category, orders 20 ล่าสุด + buyer), $isVisibleToBuyers
 | การกระทำ: POST listings.approve / listings.suspend {reason?} ผ่านโมดัลยืนยัน
--}}
@extends('layouts.admin-v4')

@section('title', $listing->title.' · สินค้าตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.doc-viewer')
@php
    $gallery = collect([$listing->main_image_url])
        ->merge(is_array($listing->images) ? $listing->images : [])
        ->filter(fn ($url) => is_string($url) && $url !== '')
        ->unique()
        ->values()
        ->map(fn ($url, $i) => ['label' => $listing->title.' รูปที่ '.($i + 1), 'url' => $url])
        ->all();
    try {
        $gpRate = app(\App\Services\Pricing\PricingEngine::class)->gpRateForFreshListing($listing);
    } catch (\Throwable) {
        $gpRate = null;
    }
    // กลุ่มตัวเลือก (เช่น "เลือกเนื้อสัตว์" / "เพิ่มเติม") + สินค้าทำตามสั่งที่ไม่ตัดสต็อก
    $optionGroups = method_exists($listing, 'optionGroups') ? $listing->optionGroups()->with('options')->get() : collect();
    $tracksStock = method_exists($listing, 'tracksStock') ? $listing->tracksStock() : true;
    $price = (float) $listing->price;
    $sellerGets = $gpRate !== null ? round($price - round($price * $gpRate / 100, 2), 2) : null;
    $details = [
        ['หมวดหมู่', $listing->category ? trim(($listing->category->icon ?? '').' '.$listing->category->name) : 'ไม่มีหมวด'],
        ['ความสด', $listing->freshness_level ?: '-'],
        ['ออร์แกนิก', $listing->is_organic ? 'ใช่ 🌱' : 'ไม่ใช่'],
        ['ช่วงเวลาขาย', ($listing->available_from || $listing->available_until) ? trim(($listing->available_from ?? '').' – '.($listing->available_until ?? '')) : 'ตลอดวัน'],
        ['รัศมีส่ง', $listing->delivery_radius_km ? number_format((float) $listing->delivery_radius_km, 1).' กม.' : '-'],
        ['ลงขายผ่าน', ['web' => 'เว็บ', 'line' => 'LINE', 'app' => 'แอป', 'api' => 'แอป'][$listing->created_via] ?? ($listing->created_via ?: '-')],
        ['แคชแบ็ค', (float) $listing->cashback_percentage > 0 ? number_format((float) $listing->cashback_percentage, 2).'%' : ((float) $listing->cashback_amount > 0 ? '฿'.number_format((float) $listing->cashback_amount, 2) : 'ไม่มี')],
        ['ลงขายเมื่อ', $listing->created_at?->thaidate('j M Y H:i') ?? '-'],
        ['แก้ไขล่าสุด', $listing->updated_at?->thaidate('j M Y H:i') ?? '-'],
    ];
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
            <a href="{{ route('admin.fresh-market.listings') }}" class="tp-icon-btn" title="กลับรายการสินค้า"><i class="fas fa-arrow-left"></i></a>
            <div style="min-width:0;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · สินค้า #{{ $listing->id }}</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0; display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
                    {{ $listing->title }}
                    @include('admin.riders.partials.status', ['statusKind' => 'listing', 'statusValue' => $listing->status, 'statusLabel' => null])
                    @include('admin.riders.partials.pill', ['pillTone' => $isVisibleToBuyers ? 'ok' : 'mute', 'pillText' => $isVisibleToBuyers ? 'ผู้ซื้อเห็นอยู่' : 'ผู้ซื้อไม่เห็น', 'pillIcon' => $isVisibleToBuyers ? 'fa-eye' : 'fa-eye-slash', 'pillTitle' => null])
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    ร้าน @if ($listing->seller)<a href="{{ route('admin.fresh-market.sellers.show', $listing->seller_id) }}" class="w1-link">{{ $listing->seller->shop_name }}</a>@else - @endif
                    @if ($listing->slug) · <a href="{{ route('taladsod.listing', $listing->slug) }}" target="_blank" rel="noopener" class="w1-link">ดูหน้าร้าน <i class="fas fa-up-right-from-square" style="font-size:10px;"></i></a>@endif
                </div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            @if ($listing->status !== 'active')
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-on); background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 72%, var(--ink)));"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.listings.approve', $listing), 'title' => 'อนุมัติ / เปิดขาย', 'message' => (int) $listing->quantity_available > 0 ? 'สินค้าจะกลับมาแสดงให้ผู้ซื้อเห็น' : 'สินค้าหมดสต็อก ระบบจะตั้งเป็น "สินค้าหมด" จนกว่าร้านจะเติมของ', 'reason' => 'none', 'confirm' => 'อนุมัติ', 'tone' => 'ok', 'icon' => 'fa-circle-check']))">
                    <i class="fas fa-check"></i> อนุมัติ / เปิดขาย
                </button>
            @endif
            @if ($listing->status !== 'suspended')
                <button type="button" class="tp-btn tp-btn-sm" style="color:var(--w-bad);"
                        @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.listings.suspend', $listing), 'title' => 'ระงับสินค้า', 'message' => 'สินค้าจะถูกซ่อนจากผู้ซื้อทันที (ออเดอร์เดิมไม่ถูกยกเลิก)', 'reason' => 'optional', 'reasonLabel' => 'เหตุผล (บันทึกในประวัติ)', 'confirm' => 'ระงับสินค้า', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                    <i class="fas fa-ban"></i> ระงับสินค้า
                </button>
            @endif
        </div>
    </div>

    @include('admin.riders.partials.flash')

    @if (! $isVisibleToBuyers)
        <div class="tp-card" style="padding:12px 18px; border-left:4px solid var(--w-warn); font-size:13px;">
            <i class="fas fa-circle-info" style="color:var(--w-warn);"></i>
            ผู้ซื้อยังไม่เห็นสินค้านี้ เพราะ
            @if ($listing->status !== 'active') สถานะสินค้าไม่ใช่ "เปิดขาย"
            @elseif (! $listing->is_available) ร้านปิดการขายสินค้านี้ชั่วคราว
            @elseif ($tracksStock && (int) $listing->quantity_available <= 0) สินค้าหมดสต็อก
            @else ร้านถูกระงับ/ปิดร้าน หรือยังไม่ได้รับการยืนยัน
            @endif
        </div>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        {{-- รูป + ราคา --}}
        <div style="flex:1 1 320px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card" style="padding:14px;">
                @if (count($gallery) > 0)
                    <button type="button" style="border:0; padding:0; background:none; cursor:zoom-in; width:100%;"
                            @click="$dispatch('w1-doc', @js(['items' => $gallery, 'index' => 0]))">
                        <img src="{{ $gallery[0]['url'] }}" alt="{{ $listing->title }}" style="width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:16px; display:block; box-shadow:var(--raise);">
                    </button>
                    @if (count($gallery) > 1)
                        <div style="display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin-top:10px;">
                            @foreach ($gallery as $galleryIndex => $image)
                                @continue($galleryIndex === 0)
                                <button type="button" style="border:0; padding:0; background:none; cursor:zoom-in;" @click="$dispatch('w1-doc', @js(['items' => $gallery, 'index' => $galleryIndex]))">
                                    <img src="{{ $image['url'] }}" alt="" loading="lazy" style="width:100%; aspect-ratio:1; object-fit:cover; border-radius:10px; display:block;">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="tp-well" style="aspect-ratio:4/3; border-radius:16px; display:grid; place-items:center; color:var(--ink2);">
                        <span style="text-align:center;"><i class="fas fa-image" style="font-size:30px; display:block; margin-bottom:6px;"></i> ไม่มีรูปสินค้า</span>
                    </div>
                @endif
            </div>

            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-tag"></i> ราคาและส่วนแบ่ง</div>
                <div style="display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;">
                    <span class="tp-num" style="font-size:28px; font-weight:800;">฿{{ number_format($price, 2) }}</span>
                    <span style="color:var(--ink2);">/ {{ $listing->unit }}</span>
                    @if ($listing->compare_at_price && (float) $listing->compare_at_price > $price)
                        <span style="color:var(--ink2); text-decoration:line-through;">฿{{ number_format((float) $listing->compare_at_price, 2) }}</span>
                        @include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'ลด '.$listing->discount_percentage.'%', 'pillIcon' => null, 'pillTitle' => null])
                    @endif
                </div>
                @if ($gpRate !== null)
                    <div class="tp-well" style="padding:10px 12px; margin-top:12px; display:grid; grid-template-columns:1fr auto; gap:5px 10px; font-size:13px;">
                        <span style="color:var(--ink2);">ค่า GP ตลาดสดตอนนี้</span><b class="tp-num">{{ rtrim(rtrim(number_format($gpRate, 2), '0'), '.') }}%</b>
                        <span style="color:var(--ink2);">ร้านได้รับต่อ 1 {{ $listing->unit }}</span><b class="tp-num">฿{{ number_format($sellerGets, 2) }}</b>
                    </div>
                @endif
                <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin-top:12px; text-align:center;">
                    @if ($tracksStock)
                        <div class="tp-well" style="padding:10px 6px;"><div class="tp-num" style="font-weight:800; font-size:18px; {{ (int) $listing->quantity_available <= 0 ? 'color:var(--w-bad);' : '' }}">{{ number_format((int) $listing->quantity_available) }}</div><div style="font-size:11px; color:var(--ink2);">คงเหลือ</div></div>
                    @else
                        <div class="tp-well" style="padding:10px 6px;"><div style="font-weight:800; font-size:14px; line-height:1.6;">ทำตามสั่ง</div><div style="font-size:11px; color:var(--ink2);">ไม่ตัดสต็อก</div></div>
                    @endif
                    <div class="tp-well" style="padding:10px 6px;"><div class="tp-num" style="font-weight:800; font-size:18px;">{{ number_format((int) $listing->order_count) }}</div><div style="font-size:11px; color:var(--ink2);">ออเดอร์</div></div>
                    <div class="tp-well" style="padding:10px 6px;"><div class="tp-num" style="font-weight:800; font-size:18px;">{{ number_format((int) $listing->view_count) }}</div><div style="font-size:11px; color:var(--ink2);">เข้าชม</div></div>
                </div>
            </div>
        </div>

        {{-- รายละเอียด + ออเดอร์ --}}
        <div style="flex:2 1 460px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            @if ($optionGroups->isNotEmpty())
                <div class="tp-card" style="padding:20px;">
                    <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-list-check"></i> ตัวเลือกสินค้า</div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:12px;">
                        @foreach ($optionGroups as $group)
                            <div class="tp-well" style="padding:12px 14px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px;">
                                    <b style="font-size:13.5px;">{{ $group->name }}</b>
                                    @include('admin.riders.partials.pill', ['pillTone' => $group->is_required ? 'warn' : 'mute', 'pillText' => method_exists($group, 'ruleLabel') ? $group->ruleLabel() : ($group->is_required ? 'ต้องเลือก' : 'ไม่บังคับ'), 'pillIcon' => null, 'pillTitle' => null])
                                </div>
                                @foreach ($group->options as $option)
                                    <div style="display:flex; justify-content:space-between; gap:8px; font-size:13px; padding:4px 0; {{ $option->is_available ? '' : 'opacity:.55;' }}">
                                        <span>{{ $option->name }} @if (! $option->is_available)<span style="font-size:11px; color:var(--w-bad);">(หมด)</span>@endif</span>
                                        <span class="tp-num" style="color:var(--ink2);">{{ (float) $option->price_delta > 0 ? '+฿'.number_format((float) $option->price_delta, 2) : ((float) $option->price_delta < 0 ? '−฿'.number_format(abs((float) $option->price_delta), 2) : 'ราคาเดิม') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-circle-info"></i> รายละเอียดสินค้า</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:10px;">
                    @foreach ($details as [$detailLabel, $detailValue])
                        <div class="tp-well" style="padding:10px 12px;">
                            <div style="font-size:11px; color:var(--ink2);">{{ $detailLabel }}</div>
                            <div style="font-weight:600; font-size:13px; word-break:break-word;">{{ $detailValue }}</div>
                        </div>
                    @endforeach
                </div>
                @if (is_array($listing->tags) && count($listing->tags) > 0)
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:12px;">
                        @foreach ($listing->tags as $tag)
                            <span class="tp-pill tp-pill-soft">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
                <div class="tp-divider" style="margin:14px 0;"></div>
                <div style="font-size:13px; line-height:1.7; white-space:pre-line;">{{ $listing->description ?: 'ร้านไม่ได้ใส่คำอธิบาย' }}</div>
            </div>

            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div class="tp-section-h" style="padding:14px 18px;"><i class="fas fa-receipt"></i> ออเดอร์ล่าสุดของสินค้านี้</div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; min-width:520px; border-collapse:collapse; font-size:13px;">
                        <tbody>
                            @forelse ($listing->orders as $order)
                                <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                    <td style="padding:10px 18px;">
                                        <a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="w1-link tp-num">#{{ $order->order_number }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);">{{ $order->buyer?->name ?? '-' }} · {{ $order->created_at?->thaidate('j M H:i') }}</div>
                                    </td>
                                    <td style="padding:10px 12px; text-align:right;" class="tp-num">× {{ $order->quantity }}</td>
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
</div>
@endsection

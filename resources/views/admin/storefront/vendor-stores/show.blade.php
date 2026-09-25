@extends('layouts.admin-v4')

@section('title', 'ร้าน ' . $store->store_name)

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $row = 'display:flex; justify-content:space-between; gap:12px; font-size:13px; padding:7px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 16%, transparent);';
    $th = 'padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); white-space:nowrap;';
    $td = 'padding:10px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $money = fn ($v) => '฿' . number_format((float) $v, 2);
    $statusMeta = [
        'active' => ['เปิดอยู่', $c['ok']],
        'pending' => ['รออนุมัติ', $c['warn']],
        'suspended' => ['ถูกระงับ', $c['bad']],
        'closed' => [$store->user ? 'ปิดถาวร · ใบสมัครถูกปฏิเสธ' : 'ปิดถาวร · บัญชีเจ้าของถูกลบ', $c['mute']],
    ];
    [$sLabel, $sColor] = $statusMeta[$store->status] ?? [$store->status ?: 'ไม่ระบุ', $c['mute']];
    $owner = $store->user;
    $recentOrders = $recentOrders ?? collect();
    $earnings = $earnings ?? null;

    // อัตรา GP ที่สินค้าของร้านนี้จะโดนจริง (ใช้สินค้าจำลองของร้าน)
    $gpInfo = null;
    try {
        $probe = new \App\Models\Product();
        $probe->seller_id = $store->user_id;
        $probe->store_id = $store->id;
        $gpInfo = app(\App\Services\Pricing\PricingEngine::class)->gpRateInfoForProduct($probe);
    } catch (\Throwable $e) {
        $gpInfo = null;
    }
    // ถูกระงับ = เปิดคืนได้ · ปิดถาวร (closed) = ใบสมัครถูกปฏิเสธ หรือเจ้าของลบบัญชีตาม PDPA → ไม่มีปุ่มเปิดร้าน
    $isBlocked = $store->status === 'suspended';
    $isClosed = $store->status === 'closed';
    $ownerDeleted = $isClosed && ! $store->user;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวร้าน ===== --}}
    <div class="tp-card" style="padding:22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, var(--card-bg)), var(--card-bg) 70%);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:16px; align-items:flex-start;">
            <div style="display:flex; gap:14px; align-items:center; min-width:0;">
                @if($store->logo_url)
                    <img src="{{ $store->logo_url }}" alt="" style="width:64px; height:64px; border-radius:18px; object-fit:cover; box-shadow:var(--inset-sm); flex:none;">
                @else
                    <span class="tp-tile" style="width:64px; height:64px; border-radius:18px; font-size:26px;">🏪</span>
                @endif
                <div style="min-width:0;">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600;">หลังบ้าน · ร้านค้า · #{{ $store->id }}</div>
                    <h1 style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:2px 0 0; overflow-wrap:anywhere;">{{ $store->store_name }}</h1>
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:7px;">
                        <span class="tp-pill" style="{{ $pill($sColor) }}">{{ $sLabel }}</span>
                        <span class="tp-pill" style="{{ $pill($store->is_active ? $c['ok'] : $c['mute']) }}">{{ $store->is_active ? 'เปิดขาย' : 'ปิดขาย' }}</span>
                        @if($store->is_verified)<span class="tp-pill" style="{{ $pill($c['info']) }}">✓ ยืนยันแล้ว</span>@endif
                        @if($store->is_featured_home)<span class="tp-pill tp-pill-gold">⭐ ร้านแนะนำ</span>@endif
                        <span class="tp-pill tp-pill-soft tp-num">{{ $store->store_slug }}</span>
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @if($store->store_slug && \Illuminate\Support\Facades\Route::has('store.show'))
                    <a href="{{ route('store.show', $store->store_slug) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm"><i class="fas fa-up-right-from-square"></i> ดูหน้าร้าน</a>
                @endif
                <a href="{{ route('admin.storefront.vendor-stores.edit', $store) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-pen"></i> แก้ไข / แพ็กเกจ</a>
                <a href="{{ route('admin.storefront.vendor-stores.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> รายการร้าน</a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $c['bad'] }};">
            <ul style="margin:0; padding-left:18px; font-size:13px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ===== คำขอเปิดร้าน (รออนุมัติ) ===== --}}
    @if($store->status === 'pending')
        <div class="tp-card" style="border-left:4px solid {{ $c['warn'] }};" x-data="{ showReject: false, busy: false }">
            <div class="tp-section-h">⏳ ร้านนี้รออนุมัติ</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">อนุมัติแล้วเจ้าของจะได้บทบาทผู้ขายและเข้าหลังร้านได้ทันที</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                <form method="POST" action="{{ route('admin.seller-applications.approve', $store->id) }}"
                      @submit="if (!confirm(@js('อนุมัติร้าน '.$store->store_name.' ?'))) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary" :disabled="busy"><i class="fas fa-check"></i> อนุมัติ</button>
                </form>
                <button type="button" class="tp-btn tp-btn-sm" @click="showReject = !showReject"><i class="fas fa-xmark"></i> ปฏิเสธ</button>
            </div>
            <form x-show="showReject" x-cloak method="POST" action="{{ route('admin.seller-applications.reject', $store->id) }}" class="flex"
                  style="margin-top:12px; gap:10px; flex-wrap:wrap; align-items:flex-start;" @submit="busy = true">
                @csrf
                <textarea name="reason" rows="2" maxlength="500" required class="tp-input" style="flex:1; min-width:220px;" placeholder="เหตุผลที่ปฏิเสธ (ผู้สมัครจะเห็น)"></textarea>
                <button type="submit" class="tp-btn tp-btn-sm" style="background:{{ $c['bad'] }}; color:var(--tp-on-accent,#fff);" :disabled="busy">ยืนยันปฏิเสธ</button>
            </form>
        </div>
    @endif

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
        @foreach([
            ['สินค้า', number_format((int) ($stats['products_count'] ?? 0)), 'fa-box', null, route('admin.ecommerce.products.index', ['store_id' => $store->id])],
            ['ออเดอร์', number_format((int) ($stats['orders_count'] ?? 0)), 'fa-receipt', $c['info'], null],
            ['ยอดขาย (ชำระแล้ว)', $money($stats['total_revenue'] ?? 0), 'fa-sack-dollar', $c['ok'], null],
            ['คะแนนรีวิว', number_format((float) ($stats['avg_rating'] ?? 0), 1).' ★ ('.number_format((int) ($stats['reviews_count'] ?? 0)).')', 'fa-star', $c['warn'], null],
        ] as [$label, $value, $icon, $color, $url])
            @if($url)<a href="{{ $url }}" class="tp-card tp-card-hover" style="padding:16px; text-decoration:none; color:inherit;">@else<div class="tp-card" style="padding:16px;">@endif
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:40px; height:40px; font-size:16px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div style="min-width:0;">
                        <div class="tp-num" style="font-size:19px; font-weight:800; line-height:1.15; overflow-wrap:anywhere;">{{ $value }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            @if($url)</a>@else</div>@endif
        @endforeach
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); gap:16px; align-items:start;">
        {{-- ส่วนแบ่งรายได้ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-scale-balanced" style="color:var(--accent1);"></i> GP และรายได้ของร้าน</div>
            @if($gpInfo)
                <div class="tp-well" style="padding:12px 14px; margin-bottom:10px;">
                    <div style="display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;">
                        <span class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep1);">{{ rtrim(rtrim(number_format((float) $gpInfo['rate'], 2), '0'), '.') }}%</span>
                        <span style="font-size:12.5px; color:var(--ink2);">{{ $gpInfo['label_th'] }}</span>
                    </div>
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">อัตรา GP ที่สินค้าทั่วไปของร้านนี้โดนตอนนี้ (สินค้าที่แอดมินตั้งอัตราเฉพาะจะต่างออกไป)</div>
                </div>
            @endif
            @if($earnings)
                <div style="{{ $row }}"><span style="color:var(--ink2);">ยอดขายที่แบ่งเงินแล้ว</span><span class="tp-num">{{ $money($earnings['gross']) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">GP ที่แพลตฟอร์มได้</span><span class="tp-num" style="color:{{ $c['info'] }};">{{ $money($earnings['gp']) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">VAT ที่หัก</span><span class="tp-num">{{ $money($earnings['vat']) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">ค่าแนะนำ (กองทุน)</span><span class="tp-num">{{ $money($earnings['pool']) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">รอโอนให้ร้าน</span><span class="tp-num" style="color:{{ $c['warn'] }}; font-weight:700;">{{ $money($earnings['pending']) }}</span></div>
                <div style="{{ $row }}"><span style="color:var(--ink2);">โอนเข้ากระเป๋าแล้ว</span><span class="tp-num" style="color:{{ $c['ok'] }}; font-weight:700;">{{ $money($earnings['paid']) }}</span></div>
            @endif
        </div>

        {{-- ข้อมูลร้าน --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-id-card" style="color:var(--accent1);"></i> ข้อมูลร้านและเจ้าของ</div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">เจ้าของ</span>
                <span style="text-align:right;">
                    @if($owner)
                        <a href="{{ route('admin.users.show', $owner->id) }}" style="color:var(--deep1);">{{ $owner->name }}</a>
                        <div style="font-size:11.5px; color:var(--ink2);">{{ $owner->email }}</div>
                    @else
                        ไม่พบบัญชี
                    @endif
                </span>
            </div>
            @if($owner)
                <div style="{{ $row }}"><span style="color:var(--ink2);">KYC เจ้าของ</span><span class="tp-pill {{ $owner->kyc_status === 'approved' ? 'tp-pill-gold' : 'tp-pill-soft' }}">{{ $owner->kyc_status ?? 'ยังไม่ส่ง' }}</span></div>
            @endif
            <div style="{{ $row }}"><span style="color:var(--ink2);">ประเภทธุรกิจ</span><span>{{ $store->business_type === 'company' ? 'นิติบุคคล' : 'บุคคลธรรมดา' }}{{ $store->company_name ? ' · '.$store->company_name : '' }}</span></div>
            @if($store->tax_id)<div style="{{ $row }}"><span style="color:var(--ink2);">เลขผู้เสียภาษี</span><span class="tp-num">{{ $store->tax_id }}</span></div>@endif
            <div style="{{ $row }}"><span style="color:var(--ink2);">จด VAT</span><span>{{ $store->vat_registered ? 'จดแล้ว (หัก VAT 7/107)' : 'ไม่ได้จด' }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">แพ็กเกจ</span><span>{{ $store->package?->display_name ?? $store->package?->package_name ?? 'ไม่มี' }}{{ $store->subscription_status ? ' · '.$store->subscription_status : '' }}</span></div>
            @if($store->subscription_expires_at)<div style="{{ $row }}"><span style="color:var(--ink2);">หมดอายุแพ็กเกจ</span><span class="tp-num">{{ $store->subscription_expires_at->format('d/m/Y') }}</span></div>@endif
            <div style="{{ $row }}"><span style="color:var(--ink2);">ติดต่อ</span><span style="text-align:right;">{{ $store->store_phone ?: '-' }}<div style="font-size:11.5px; color:var(--ink2);">{{ $store->store_email }}</div></span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">ที่อยู่</span><span style="text-align:right; max-width:60%;">{{ trim(implode(' ', array_filter([$store->store_address, $store->store_city, $store->store_state, $store->store_postal_code]))) ?: '-' }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">ส่งด้วยไรเดอร์</span>
                <span style="text-align:right;">
                    {{ $store->rider_delivery_enabled ? 'เปิด' : 'ปิด' }}
                    <div style="font-size:11.5px; color:var(--ink2);">{{ $store->hasPickupLocation() ? 'ตั้งจุดรับของแล้ว' : 'ยังไม่ตั้งจุดรับของ' }}</div>
                </span>
            </div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">สมัครเมื่อ</span><span class="tp-num">{{ $store->created_at?->format('d/m/Y') }}</span></div>
        </div>
    </div>

    {{-- ===== ระงับ / เปิดร้าน ===== --}}
    @if($store->status !== 'pending' && ! $store->isPlatformStore())
        <div class="tp-card" style="border-left:4px solid {{ $isBlocked ? $c['bad'] : ($isClosed ? $c['mute'] : 'transparent') }};" x-data="{ open: false, busy: false }">
            @if($isBlocked)
                <div class="tp-section-h" style="color:{{ $c['bad'] }};">⛔ ร้านนี้ถูกระงับอยู่</div>
                <div style="font-size:13px; margin-top:6px;">เหตุผล: {{ $store->suspension_reason ?: '-' }}</div>
                <form method="POST" action="{{ route('admin.storefront.vendor-stores.unsuspend', $store) }}" style="margin-top:12px;"
                      @submit="if (!confirm(@js('เปิดร้าน '.$store->store_name.' ให้กลับมาขายได้?'))) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary" :disabled="busy"><i class="fas fa-lock-open"></i> ยกเลิกการระงับ / เปิดร้าน</button>
                </form>
            @elseif($isClosed)
                <div class="tp-section-h" style="color:var(--ink2);"><i class="fas fa-store-slash"></i> ร้านนี้ปิดถาวร</div>
                @if($ownerDeleted)
                    <div style="font-size:13px; margin-top:6px; line-height:1.6;">เจ้าของร้านลบบัญชีแล้ว (PDPA) — ร้านนี้เปิดขายหรือกลับมาแสดงต่อสาธารณะไม่ได้</div>
                @else
                    <div style="font-size:13px; margin-top:6px; line-height:1.6;">ใบสมัครเปิดร้านถูกปฏิเสธ · เหตุผล: {{ $store->suspension_reason ?: '-' }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:6px; line-height:1.6;">
                        ถ้าจะให้เปิดร้าน ผู้สมัครต้องแก้ไขและยื่นคำขอใหม่ แล้วอนุมัติผ่านหน้าคำขอเปิดร้าน (ได้บทบาทผู้ขายและแพ็กเกจครบ)
                    </div>
                    <a href="{{ route('admin.seller-applications.index') }}" class="tp-btn tp-btn-sm" style="margin-top:10px;"><i class="fas fa-inbox"></i> ไปหน้าคำขอเปิดร้าน</a>
                @endif
            @else
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
                    <div>
                        <div class="tp-section-h"><i class="fas fa-shield-halved" style="color:var(--accent1);"></i> ระงับร้าน</div>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ร้านจะขายและเข้าหลังร้านไม่ได้จนกว่าจะเปิดอีกครั้ง เจ้าของร้านได้รับแจ้งพร้อมเหตุผล</div>
                    </div>
                    <button type="button" class="tp-btn tp-btn-sm" style="color:{{ $c['bad'] }};" @click="open = !open"><i class="fas fa-ban"></i> ระงับร้านนี้</button>
                </div>
                <form x-show="open" x-cloak method="POST" action="{{ route('admin.storefront.vendor-stores.suspend', $store) }}" class="flex"
                      style="margin-top:12px; gap:10px; flex-wrap:wrap; align-items:flex-start;"
                      @submit="if (!confirm(@js('ยืนยันระงับร้าน '.$store->store_name.' ?'))) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <textarea name="reason" rows="2" minlength="5" maxlength="500" required class="tp-input" style="flex:1; min-width:220px;" placeholder="เหตุผลที่ระงับ (เจ้าของร้านจะเห็น)">{{ old('reason') }}</textarea>
                    <button type="submit" class="tp-btn tp-btn-sm" style="background:{{ $c['bad'] }}; color:var(--tp-on-accent,#fff);" :disabled="busy">ยืนยันระงับ</button>
                </form>
            @endif
        </div>
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr)); gap:16px;">
        {{-- ออเดอร์ล่าสุด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:14px 18px; box-shadow:var(--inset-sm);"><div class="tp-section-h">ออเดอร์ล่าสุด</div></div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:420px; border-collapse:collapse;">
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="{{ $td }}">
                                    <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">#{{ $order->order_number }}</a>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $order->user?->name ?? '-' }} · {{ $order->created_at?->format('d/m H:i') }}</div>
                                </td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <div class="tp-num" style="font-weight:700;">{{ $money($order->total_amount) }}</div>
                                    <span class="tp-pill tp-pill-soft">{{ $order->status_label }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td style="padding:30px; text-align:center; color:var(--ink2);">ร้านนี้ยังไม่มีออเดอร์</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- สินค้าล่าสุด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--inset-sm);">
                <div class="tp-section-h">สินค้าล่าสุด</div>
                <a href="{{ route('admin.ecommerce.products.index', ['store_id' => $store->id]) }}" style="font-size:12.5px; color:var(--deep1);">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:420px; border-collapse:collapse;">
                    <thead><tr><th style="{{ $th }}">สินค้า</th><th style="{{ $th }} text-align:right;">ราคา</th><th style="{{ $th }}">สถานะ</th></tr></thead>
                    <tbody>
                        @forelse($store->products as $product)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="{{ $td }}">
                                    <a href="{{ route('admin.ecommerce.products.show', $product) }}" style="color:var(--ink); text-decoration:none; display:flex; align-items:center; gap:8px;">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="" loading="lazy" style="width:34px; height:34px; border-radius:9px; object-fit:cover; flex:none;">
                                        @endif
                                        <span style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</span>
                                    </a>
                                </td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money($product->price) }}</td>
                                <td style="{{ $td }}">
                                    @if($product->is_blocked)
                                        <span class="tp-pill" style="{{ $pill($c['bad']) }}">บล็อก</span>
                                    @else
                                        <span class="tp-pill" style="{{ $pill($product->is_active ? $c['ok'] : $c['mute']) }}">{{ $product->is_active ? 'เปิดขาย' : 'ปิด' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding:30px; text-align:center; color:var(--ink2);">ยังไม่มีสินค้า</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($store->store_description)
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-align-left" style="color:var(--accent1);"></i> คำอธิบายร้าน</div>
            <div style="font-size:13px; line-height:1.7; white-space:pre-line; overflow-wrap:anywhere;">{{ $store->store_description }}</div>
        </div>
    @endif

    {{-- ===== ลบร้าน ===== --}}
    @unless($store->isPlatformStore())
        <div style="display:flex; justify-content:flex-end;">
            <form method="POST" action="{{ route('admin.storefront.vendor-stores.destroy', $store) }}"
                  @submit="if (!confirm(@js('ลบร้าน '.$store->store_name.' ? (ซ่อนร้านออกจากระบบ กู้คืนได้จากฐานข้อมูลเท่านั้น) — ถ้าต้องการหยุดชั่วคราวให้ใช้ ระงับร้าน แทน'))) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ $c['bad'] }};"><i class="fas fa-trash"></i> ลบร้าน</button>
            </form>
        </div>
    @endunless
</div>
@endsection

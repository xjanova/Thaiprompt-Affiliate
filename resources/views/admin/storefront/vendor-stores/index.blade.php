@extends('layouts.admin-v4')

@section('title', 'ร้านค้าทั้งหมด')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $statusMeta = [
        'active' => ['เปิดอยู่', $c['ok']],
        'pending' => ['รออนุมัติ', $c['warn']],
        'suspended' => ['ถูกระงับ', $c['bad']],
        'closed' => ['ปิดถาวร', $c['mute']],
    ];
    // ร้าน closed มี 2 ที่มา: ใบสมัครถูกปฏิเสธ (เจ้าของยังอยู่) / เจ้าของลบบัญชีตาม PDPA (user ถูก soft delete → relation ว่าง)
    $closedLabel = fn ($store) => $store->user ? 'ใบสมัครถูกปฏิเสธ' : 'บัญชีเจ้าของถูกลบ';
    // สถานะที่เปิดขายจากสวิตช์ไม่ได้ (ต้องเปิดคืน/อนุมัติที่หน้ารายละเอียด)
    $switchLocked = ['suspended', 'closed', 'pending'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{
        statusUrl: @js(route('admin.storefront.vendor-stores.toggle-status', ['store' => '__ID__'])),
        featuredUrl: @js(route('admin.storefront.vendor-stores.toggle-featured', ['store' => '__ID__'])),
        busy: {},
        async flip(kind, id) {
            if (this.busy[id]) return;
            this.busy[id] = true;
            const url = (kind === 'status' ? this.statusUrl : this.featuredUrl).replace('__ID__', id);
            try {
                const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    const err = new Error('fail');
                    err.userMessage = data.message || null;
                    throw err;
                }
                this.$dispatch('notify', { type: 'success', message: data.message || 'บันทึกแล้ว' });
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                this.$dispatch('notify', { type: 'error', message: e.userMessage || 'บันทึกไม่สำเร็จ กรุณาลองใหม่' });
            } finally {
                this.busy[id] = false;
            }
        }
     }">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ร้านค้าทั้งหมด 🏪</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ดูข้อมูลร้าน เปลี่ยนแพ็กเกจ/GP ตั้งค่า VAT ระงับหรือเปิดร้าน</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.seller-applications.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-inbox"></i> คำขอเปิดร้าน
                @if(($stats['pending'] ?? 0) > 0)<span class="tp-pill tp-pill-gold tp-num">{{ number_format($stats['pending']) }}</span>@endif
            </a>
            <a href="{{ route('admin.featured-stores.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-award"></i> ร้านแนะนำ</a>
            <a href="{{ route('admin.storefront.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-gear"></i> ตั้งค่าหน้าร้าน</a>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
        @foreach([
            ['ทั้งหมด', $stats['total'] ?? 0, 'fa-store', null, ''],
            ['เปิดใช้งาน', $stats['active'] ?? 0, 'fa-circle-check', $c['ok'], 'active'],
            ['รออนุมัติ', $stats['pending'] ?? 0, 'fa-hourglass-half', $c['warn'], 'pending'],
            ['ถูกระงับ', $stats['suspended'] ?? 0, 'fa-ban', $c['bad'], 'suspended'],
            ['ปิดถาวร', $stats['closed'] ?? 0, 'fa-store-slash', null, 'closed'],
            ['ยืนยันแล้ว', $stats['verified'] ?? 0, 'fa-certificate', $c['info'], 'verified'],
            ['ร้านแนะนำ', $stats['featured'] ?? 0, 'fa-award', null, 'featured'],
        ] as [$label, $value, $icon, $color, $status])
            <a href="{{ route('admin.storefront.vendor-stores.index', $status ? ['status' => $status] : []) }}" class="tp-card tp-card-hover" style="padding:14px 16px; text-decoration:none; color:inherit; {{ request('status', '') === $status ? 'box-shadow:var(--inset);' : '' }}">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="tp-tile" style="width:36px; height:36px; font-size:14px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:21px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.storefront.vendor-stores.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; align-items:end;">
            <div style="grid-column:1 / -1;">
                <label style="{{ $lbl }}">🔍 ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" class="tp-input" placeholder="ชื่อร้าน slug ชื่อหรืออีเมลเจ้าของ">
            </div>
            <div>
                <label style="{{ $lbl }}">สถานะ</label>
                <select name="status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="active" @selected(request('status') === 'active')>เปิดใช้งาน</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>ปิดใช้งาน</option>
                    <option value="pending" @selected(request('status') === 'pending')>รออนุมัติ</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>ถูกระงับ</option>
                    <option value="closed" @selected(request('status') === 'closed')>ปิดถาวร (ใบสมัครถูกปฏิเสธ/บัญชีถูกลบ)</option>
                    <option value="verified" @selected(request('status') === 'verified')>ยืนยันแล้ว</option>
                    <option value="featured" @selected(request('status') === 'featured')>ร้านแนะนำ</option>
                    <option value="rider" @selected(request('status') === 'rider')>ส่งด้วยไรเดอร์</option>
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">เรียงตาม</label>
                <select name="sort_by" class="tp-input">
                    <option value="created_at" @selected(request('sort_by', 'created_at') === 'created_at')>สมัครล่าสุด</option>
                    <option value="store_name" @selected(request('sort_by') === 'store_name')>ชื่อร้าน</option>
                    <option value="orders_count" @selected(request('sort_by') === 'orders_count')>จำนวนออเดอร์</option>
                    <option value="products_count" @selected(request('sort_by') === 'products_count')>จำนวนสินค้า</option>
                    <option value="rating_average" @selected(request('sort_by') === 'rating_average')>คะแนนรีวิว</option>
                </select>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.storefront.vendor-stores.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:980px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">ร้าน</th>
                        <th style="{{ $th }}">เจ้าของ</th>
                        <th style="{{ $th }}">แพ็กเกจ / GP</th>
                        <th style="{{ $th }} text-align:right;">สินค้า / ออเดอร์</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }} text-align:center;">เปิดขาย</th>
                        <th style="{{ $th }} text-align:center;">แนะนำ</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $store)
                        @php [$sLabel, $sColor] = $statusMeta[$store->status] ?? [$store->status ?: 'ไม่ระบุ', $c['mute']]; @endphp
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    @if($store->logo_url)
                                        <img src="{{ $store->logo_url }}" alt="" loading="lazy" style="width:40px; height:40px; border-radius:12px; object-fit:cover; flex:none;">
                                    @else
                                        <span class="tp-tile" style="width:40px; height:40px; font-size:15px; font-weight:800;">{{ mb_strtoupper(mb_substr($store->store_name ?: '?', 0, 1)) }}</span>
                                    @endif
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" style="font-weight:700; color:var(--ink); text-decoration:none; display:block; max-width:210px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $store->store_name }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);" class="tp-num">{{ $store->store_slug }}</div>
                                        <div style="display:flex; gap:4px; flex-wrap:wrap; margin-top:3px;">
                                            @if($store->is_verified)<span class="tp-pill" style="{{ $pill($c['info']) }}">✓ ยืนยันแล้ว</span>@endif
                                            @if($store->vat_registered)<span class="tp-pill tp-pill-soft">VAT</span>@endif
                                            @if($store->rider_delivery_enabled)<span class="tp-pill tp-pill-soft">🛵 ไรเดอร์</span>@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }}">
                                <div style="font-weight:600; max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $store->user?->name ?? '-' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2); max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $store->user?->email }}</div>
                            </td>
                            <td style="{{ $td }}">
                                <div>{{ $store->package?->display_name ?? $store->package?->package_name ?? 'ไม่มีแพ็กเกจ' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);" class="tp-num">
                                    GP {{ rtrim(rtrim(number_format((float) ($store->package?->commission_rate ?? $store->commission_rate ?? 0), 2), '0'), '.') }}%
                                </div>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;" class="tp-num">
                                <a href="{{ route('admin.ecommerce.products.index', ['store_id' => $store->id]) }}" style="color:var(--ink); text-decoration:none;">{{ number_format((int) $store->products_count) }}</a>
                                <span style="color:var(--ink2);"> / {{ number_format((int) $store->orders_count) }}</span>
                            </td>
                            <td style="{{ $td }}">
                                <span class="tp-pill" style="{{ $pill($sColor) }}">{{ $sLabel }}</span>
                                @if($store->status === 'closed')
                                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $closedLabel($store) }}</div>
                                @endif
                                @if($store->suspension_reason && in_array($store->status, ['suspended', 'closed'], true))
                                    <div style="font-size:11px; color:var(--ink2); max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $store->suspension_reason }}">{{ $store->suspension_reason }}</div>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:center;">
                                @if(! $store->is_active && in_array($store->status, $switchLocked, true))
                                    {{-- ถูกระงับ/ปิดถาวร/รออนุมัติ: เปิดขายจากสวิตช์ไม่ได้ → ไปจัดการที่หน้ารายละเอียด --}}
                                    <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" class="tp-icon-btn" style="width:34px; height:34px; margin:auto; color:{{ $c['mute'] }};"
                                       title="{{ $store->status === 'pending' ? 'รออนุมัติ — อนุมัติที่หน้ารายละเอียด' : ($store->status === 'suspended' ? 'ถูกระงับ — เปิดร้านคืนที่หน้ารายละเอียด' : 'ปิดถาวร — เปิดขายไม่ได้') }}">
                                        <i class="fas fa-lock" style="font-size:14px;"></i>
                                    </a>
                                @else
                                    <button type="button" class="tp-icon-btn" style="width:34px; height:34px; margin:auto; color:{{ $store->is_active ? $c['ok'] : $c['mute'] }};"
                                            title="{{ $store->is_active ? 'ปิดการขายชั่วคราว' : 'เปิดการขาย' }}"
                                            @click="if (confirm(@js(($store->is_active ? 'ปิด' : 'เปิด').'การขายของร้าน "'.$store->store_name.'" ?'))) flip('status', {{ $store->id }})" :disabled="busy[{{ $store->id }}]">
                                        <i class="fas {{ $store->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}" style="font-size:18px;"></i>
                                    </button>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:center;">
                                <button type="button" class="tp-icon-btn" style="width:34px; height:34px; margin:auto; color:{{ $store->is_featured_home ? 'var(--accent1)' : $c['mute'] }};"
                                        title="{{ $store->is_featured_home ? 'เอาออกจากร้านแนะนำ' : 'ตั้งเป็นร้านแนะนำ' }}"
                                        @click="flip('featured', {{ $store->id }})" :disabled="busy[{{ $store->id }}]">
                                    <i class="{{ $store->is_featured_home ? 'fas' : 'far' }} fa-star"></i>
                                </button>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="ดูรายละเอียด"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.storefront.vendor-stores.edit', $store) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="แก้ไข"><i class="fas fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-store-slash" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ไม่พบร้านค้าตามเงื่อนไขนี้
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($stores->hasPages())
        <div>{{ $stores->links() }}</div>
    @endif
</div>
@endsection

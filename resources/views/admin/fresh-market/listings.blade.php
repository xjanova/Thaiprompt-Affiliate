{{--
 | สินค้าตลาดสด (admin.fresh-market.listings) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@listings: $listings (paginator, with seller, category), $categories (หมวดที่เปิดอยู่)
 | ตัวกรอง GET: status (draft|active|sold_out|expired|suspended), category_id, seller_id, search
 | การกระทำ: POST listings.approve (เปิดขาย/ของหมด→sold_out) / listings.suspend {reason?} ผ่านโมดัลยืนยัน
 | ใช้คอลัมน์จริง: main_image_url/images (ผ่าน primary_image), compare_at_price
--}}
@extends('layouts.admin-v4')

@section('title', 'สินค้าตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $listingStatuses = ['' => 'ทั้งหมด', 'active' => 'เปิดขาย', 'sold_out' => 'สินค้าหมด', 'suspended' => 'ถูกระงับ', 'draft' => 'แบบร่าง', 'expired' => 'หมดอายุ'];
    $sellerFilter = request('seller_id') ? \App\Models\FreshMarketSeller::withTrashed()->find((int) request('seller_id'), ['id', 'shop_name']) : null;
    $hasFilter = request()->hasAny(['status', 'category_id', 'seller_id', 'search']);
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.dashboard') }}" class="tp-icon-btn" title="กลับแดชบอร์ดตลาดสด"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · สินค้า</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สินค้าตลาดสด 🥬</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตรวจสินค้า อนุมัติเปิดขาย หรือระงับสินค้าที่ไม่เหมาะสม</div>
            </div>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
        <div style="display:flex; flex-wrap:wrap; gap:6px;">
            @foreach ($listingStatuses as $value => $label)
                <a href="{{ route('admin.fresh-market.listings', array_filter(['status' => $value, 'category_id' => request('category_id'), 'seller_id' => request('seller_id'), 'search' => request('search')])) }}"
                   class="tp-btn tp-btn-sm {{ (string) request('status', '') === (string) $value ? 'tp-btn-primary' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.fresh-market.listings') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:10px; align-items:end;">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            @if (request('seller_id'))<input type="hidden" name="seller_id" value="{{ request('seller_id') }}">@endif
            <input type="search" name="search" value="{{ request('search') }}" class="tp-input" placeholder="ค้นหาชื่อสินค้า">
            <select name="category_id" class="tp-input">
                <option value="">ทุกหมวดหมู่</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->icon }} {{ $category->name }}</option>
                @endforeach
            </select>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
                @if ($hasFilter)
                    <a href="{{ route('admin.fresh-market.listings') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
                @endif
            </div>
        </form>
        @if ($sellerFilter)
            <div style="font-size:12.5px;">
                <span class="tp-pill tp-pill-soft"><i class="fas fa-store"></i> เฉพาะร้าน {{ $sellerFilter->shop_name }}</span>
                <a href="{{ route('admin.fresh-market.listings', array_filter(['status' => request('status'), 'category_id' => request('category_id'), 'search' => request('search')])) }}" class="w1-link" style="margin-left:6px;">เอาออก</a>
            </div>
        @endif
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
            <div class="tp-section-h"><i class="fas fa-list"></i> รายการสินค้า</div>
            <span style="font-size:12px; color:var(--ink2);">ทั้งหมด {{ number_format($listings->total()) }} รายการ</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:940px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px;">สินค้า</th>
                        <th style="padding:10px 12px; text-align:right;">ราคา</th>
                        <th style="padding:10px 12px;">ร้าน</th>
                        <th style="padding:10px 12px; text-align:right;">คงเหลือ</th>
                        <th style="padding:10px 12px; text-align:right;">เข้าชม / ขาย</th>
                        <th style="padding:10px 12px;">สถานะ</th>
                        <th style="padding:10px 18px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listings as $listing)
                        <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:12px 18px;">
                                <div style="display:flex; align-items:center; gap:11px;">
                                    @if ($listing->primary_image)
                                        <img src="{{ $listing->primary_image }}" alt="" loading="lazy" style="width:46px; height:46px; border-radius:12px; object-fit:cover; flex:none; box-shadow:var(--raise);">
                                    @else
                                        <span class="tp-well" style="width:46px; height:46px; border-radius:12px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                                    @endif
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.fresh-market.listings.show', $listing) }}" class="w1-link" style="color:var(--ink); display:block; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $listing->title }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);">
                                            {{ $listing->category ? trim(($listing->category->icon ?? '').' '.$listing->category->name) : 'ไม่มีหมวด' }}
                                            @if ($listing->is_organic) · 🌱 ออร์แกนิก @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px; text-align:right; white-space:nowrap;" class="tp-num">
                                <b>฿{{ number_format((float) $listing->price, 2) }}</b><span style="color:var(--ink2);"> / {{ $listing->unit }}</span>
                                @if ($listing->compare_at_price && (float) $listing->compare_at_price > (float) $listing->price)
                                    <div style="font-size:11.5px; color:var(--ink2); text-decoration:line-through;">฿{{ number_format((float) $listing->compare_at_price, 2) }}</div>
                                @endif
                            </td>
                            <td style="padding:12px;">
                                @if ($listing->seller)
                                    <a href="{{ route('admin.fresh-market.sellers.show', $listing->seller_id) }}" class="w1-link">{{ $listing->seller->shop_name }}</a>
                                    @if ($listing->seller->is_suspended)
                                        <div>@include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'ร้านถูกระงับ', 'pillIcon' => null, 'pillTitle' => null])</div>
                                    @elseif (! $listing->seller->is_verified)
                                        <div style="font-size:11px; color:var(--ink2);">ร้านยังไม่ยืนยัน</div>
                                    @endif
                                @else
                                    <span style="color:var(--ink2);">-</span>
                                @endif
                            </td>
                            <td style="padding:12px; text-align:right;" class="tp-num">
                                <span style="{{ (int) $listing->quantity_available <= 0 ? 'color:var(--w-bad); font-weight:700;' : '' }}">{{ number_format((int) $listing->quantity_available) }}</span>
                            </td>
                            <td style="padding:12px; text-align:right; color:var(--ink2);" class="tp-num">{{ number_format((int) $listing->view_count) }} / {{ number_format((int) $listing->order_count) }}</td>
                            <td style="padding:12px;">@include('admin.riders.partials.status', ['statusKind' => 'listing', 'statusValue' => $listing->status, 'statusLabel' => null])</td>
                            <td style="padding:12px 18px;">
                                <div style="display:flex; justify-content:flex-end; gap:7px;">
                                    <a href="{{ route('admin.fresh-market.listings.show', $listing) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-eye"></i> ดู</a>
                                    @if ($listing->status !== 'active')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-ok);" title="อนุมัติ / เปิดขาย"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.listings.approve', $listing), 'title' => 'อนุมัติ / เปิดขาย '.$listing->title, 'message' => (int) $listing->quantity_available > 0 ? 'สินค้าจะกลับมาแสดงให้ผู้ซื้อเห็น' : 'สินค้าหมดสต็อก ระบบจะตั้งเป็น "สินค้าหมด" จนกว่าร้านจะเติมของ', 'reason' => 'none', 'confirm' => 'อนุมัติ', 'tone' => 'ok', 'icon' => 'fa-circle-check']))">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    @if ($listing->status !== 'suspended')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ระงับสินค้า"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.listings.suspend', $listing), 'title' => 'ระงับสินค้า '.$listing->title, 'message' => 'สินค้าจะถูกซ่อนจากผู้ซื้อทันที (ออเดอร์เดิมไม่ถูกยกเลิก)', 'reason' => 'optional', 'reasonLabel' => 'เหตุผล (บันทึกในประวัติ)', 'confirm' => 'ระงับสินค้า', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:44px 18px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-box-open" style="font-size:30px; opacity:.5; display:block; margin-bottom:10px;"></i>
                                {{ $hasFilter ? 'ไม่พบสินค้าตามเงื่อนไขที่เลือก' : 'ยังไม่มีสินค้าในตลาดสด' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($listings->hasPages())
        <div>{{ $listings->links() }}</div>
    @endif
</div>
@endsection

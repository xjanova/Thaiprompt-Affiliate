{{--
 | ผู้ขายตลาดสด (admin.fresh-market.sellers) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@sellers: $sellers (paginator, with user, withCount listings/orders)
 | ตัวกรอง GET: status (active|suspended|unverified|verified), search
 | สถานะร้านใช้ $seller->status_key (suspended|inactive|unverified|active) — ไม่มีคอลัมน์ status จริง
 | การกระทำ: POST sellers.verify / sellers.suspend {reason?} / sellers.activate (ผ่านโมดัลยืนยัน)
--}}
@extends('layouts.admin-v4')

@section('title', 'ผู้ขายตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $statusFilters = ['' => 'ทั้งหมด', 'active' => 'เปิดขาย', 'unverified' => 'รอยืนยัน', 'verified' => 'ยืนยันแล้ว', 'suspended' => 'ถูกระงับ'];
    $hasFilter = request()->hasAny(['status', 'search']);
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.dashboard') }}" class="tp-icon-btn" title="กลับแดชบอร์ดตลาดสด"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ผู้ขาย</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ผู้ขายตลาดสด 👨‍🌾</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ยืนยันร้าน ระงับร้านที่ผิดกฎ และดูยอดขาย/ค่า GP ค้างของแต่ละร้าน</div>
            </div>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
        <div style="display:flex; flex-wrap:wrap; gap:6px;">
            @foreach ($statusFilters as $value => $label)
                <a href="{{ route('admin.fresh-market.sellers', array_filter(['status' => $value, 'search' => request('search')])) }}"
                   class="tp-btn tp-btn-sm {{ (string) request('status', '') === (string) $value ? 'tp-btn-primary' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.fresh-market.sellers') }}" style="display:flex; flex-wrap:wrap; gap:8px;">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="search" name="search" value="{{ request('search') }}" class="tp-input" style="flex:1; min-width:200px;" placeholder="ชื่อร้าน เบอร์โทร หรือชื่อเจ้าของ">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
            @if ($hasFilter)
                <a href="{{ route('admin.fresh-market.sellers') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            @endif
        </form>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
            <div class="tp-section-h"><i class="fas fa-store"></i> รายชื่อร้าน</div>
            <span style="font-size:12px; color:var(--ink2);">ทั้งหมด {{ number_format($sellers->total()) }} ร้าน</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:900px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px;">ร้าน</th>
                        <th style="padding:10px 12px;">เจ้าของ / ติดต่อ</th>
                        <th style="padding:10px 12px; text-align:right;">สินค้า / ออเดอร์</th>
                        <th style="padding:10px 12px; text-align:right;">ขายสำเร็จ</th>
                        <th style="padding:10px 12px; text-align:center;">คะแนน</th>
                        <th style="padding:10px 12px;">สถานะ</th>
                        <th style="padding:10px 18px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sellers as $seller)
                        <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:12px 18px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="tp-tile" style="width:38px; height:38px; border-radius:50%; font-weight:800;">{{ mb_substr($seller->shop_name ?: 'ร', 0, 1) }}</span>
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.fresh-market.sellers.show', $seller) }}" class="w1-link" style="color:var(--ink);">{{ $seller->shop_name }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);">#{{ $seller->id }} · สมัคร {{ $seller->created_at?->thaidate('j M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px;">
                                <div>{{ $seller->user?->name ?? '-' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">
                                    @if ($seller->phone)<a href="tel:{{ $seller->phone }}" class="w1-link">{{ $seller->phone }}</a>@else - @endif
                                    · {{ collect([$seller->district, $seller->province])->filter()->implode(', ') ?: 'ไม่ระบุพื้นที่' }}
                                </div>
                            </td>
                            <td style="padding:12px; text-align:right;" class="tp-num">
                                <a href="{{ route('admin.fresh-market.listings', ['seller_id' => $seller->id]) }}" class="w1-link">{{ number_format((int) $seller->listings_count) }}</a>
                                <span style="color:var(--ink2);">/ {{ number_format((int) $seller->orders_count) }}</span>
                            </td>
                            <td style="padding:12px; text-align:right;" class="tp-num">
                                {{ number_format((int) $seller->total_sales) }} ออเดอร์
                                <div style="font-size:11.5px; color:var(--ink2);">฿{{ number_format((float) $seller->total_revenue, 2) }}</div>
                            </td>
                            <td style="padding:12px; text-align:center; white-space:nowrap;" class="tp-num">
                                @if ((int) $seller->rating_count > 0)
                                    <i class="fas fa-star" style="color:var(--accent1);"></i> {{ number_format((float) $seller->rating_average, 1) }}
                                    <span style="font-size:11px; color:var(--ink2);">({{ number_format((int) $seller->rating_count) }})</span>
                                @else
                                    <span style="color:var(--ink2);">-</span>
                                @endif
                            </td>
                            <td style="padding:12px;">
                                <div style="display:flex; flex-direction:column; align-items:flex-start; gap:4px;">
                                    @include('admin.riders.partials.status', ['statusKind' => 'seller', 'statusValue' => $seller->status_key, 'statusLabel' => null])
                                    @if ($seller->is_verified && $seller->status_key !== 'unverified')
                                        <span style="font-size:11px; color:var(--ink2);"><i class="fas fa-certificate"></i> ยืนยันแล้ว</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding:12px 18px;">
                                <div style="display:flex; justify-content:flex-end; gap:7px; flex-wrap:wrap;">
                                    <a href="{{ route('admin.fresh-market.sellers.show', $seller) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-eye"></i> ดู</a>
                                    @if (! $seller->is_verified)
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-ok);" title="ยืนยันร้าน"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.sellers.verify', $seller), 'title' => 'ยืนยันร้าน '.$seller->shop_name, 'message' => 'ร้านจะได้รับแจ้งเตือน และสินค้าจะแสดงให้ผู้ซื้อเห็น', 'reason' => 'none', 'confirm' => 'ยืนยันร้าน', 'tone' => 'ok', 'icon' => 'fa-certificate']))">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    @if ($seller->is_suspended || ! $seller->is_active)
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-ok);" title="เปิดใช้งานร้าน"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.sellers.activate', $seller), 'title' => 'เปิดใช้งานร้าน '.$seller->shop_name, 'message' => 'ร้านกลับมาขายได้ตามปกติ ร้านจะได้รับแจ้งเตือน', 'reason' => 'none', 'confirm' => 'เปิดใช้งาน', 'tone' => 'ok', 'icon' => 'fa-store']))">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                    @else
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ระงับร้าน"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.sellers.suspend', $seller), 'title' => 'ระงับร้าน '.$seller->shop_name, 'message' => 'สินค้าของร้านจะถูกซ่อนจากผู้ซื้อทันที ร้านจะเห็นเหตุผลในการแจ้งเตือน', 'reason' => 'optional', 'reasonLabel' => 'เหตุผลที่ระงับ (ร้านจะเห็น)', 'confirm' => 'ระงับร้าน', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:44px 18px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-store" style="font-size:30px; opacity:.5; display:block; margin-bottom:10px;"></i>
                                {{ $hasFilter ? 'ไม่พบร้านตามเงื่อนไขที่เลือก' : 'ยังไม่มีร้านสมัครเข้ามา' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($sellers->hasPages())
        <div>{{ $sellers->links() }}</div>
    @endif
</div>
@endsection

{{--
 | แก้ไขสินค้า (taladsod.listing.edit) — ร้านตลาดสด — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@editListing
 | ตัวแปร: $listing, $seller, $categories, $maxCashbackPercent, $optionGroups (API รวมตัวเลือกที่ปิดขาย), $gpRate, $gpFree
 | ส่งฟอร์ม PUT taladsod.listing.update (multipart) — ฟิลด์ตาม partials/listing-form + is_available, remove_images[], main_image, option_groups_present=1
 | ลบสินค้า: DELETE taladsod.listing.destroy {return_to=listings} (มีกล่องยืนยัน)
 --}}
@extends('layouts.user-v4')

@section('title', 'แก้ไข '.$listing->title.' · ร้านตลาดสด')

@php
    $status = \App\Support\TaladsodWebUi::listingStatus($listing);
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')

<div class="ts-scope ts-stack" style="gap:16px; max-width:920px; margin:0 auto;" x-data="{ confirmDelete: false }">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'listings'])

    <div class="ts-row" style="justify-content:space-between;">
        <div style="min-width:0;">
            <h1 class="ts-h1">แก้ไขสินค้า</h1>
            <div class="ts-row" style="gap:6px; margin-top:6px;">
                <span class="ts-pill ts-tone-{{ $status['tone'] }}">{{ $status['label'] }}</span>
                <span class="ts-muted ts-small">ดู {{ number_format((int) $listing->view_count) }} ครั้ง · สั่ง {{ number_format((int) $listing->order_count) }} ครั้ง</span>
            </div>
        </div>
        <div class="ts-row" style="gap:8px;">
            <a href="{{ route('taladsod.listing', $listing->slug) }}" class="tp-btn" target="_blank" rel="noopener"><i class="fas fa-eye" aria-hidden="true"></i> ดูหน้าสินค้า</a>
            <a href="{{ route('taladsod.seller.listings') }}" class="tp-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i> รายการสินค้า</a>
        </div>
    </div>

    <form method="POST" action="{{ route('taladsod.listing.update', $listing) }}" enctype="multipart/form-data" class="ts-stack" style="gap:16px;"
          x-data="{ sending: false }" x-on:submit="sending = true">
        @csrf
        @method('PUT')
        @include('taladsod.partials.listing-form', ['mode' => 'edit', 'listing' => $listing, 'optionGroups' => $optionGroups])

        <div class="tp-card ts-row" style="justify-content:space-between; position:sticky; bottom:12px; z-index:20;">
            <button type="button" class="tp-btn ts-btn-ghost ts-tone-bad" x-on:click="confirmDelete = true"><i class="fas fa-trash-can" aria-hidden="true"></i> ลบสินค้า</button>
            <button type="submit" class="ts-btn3d ts-tone-gold" :disabled="sending">
                <i class="fas" :class="sending ? 'fa-circle-notch ts-spin' : 'fa-floppy-disk'" aria-hidden="true"></i> บันทึกการแก้ไข
            </button>
        </div>
    </form>

    {{-- กล่องยืนยันลบ (ฟอร์มแยก — ไม่ซ้อนในฟอร์มแก้ไข) --}}
    <div class="ts-dialog-bg" x-show="confirmDelete" x-cloak x-transition.opacity x-on:keydown.escape.window="confirmDelete = false" x-on:click.self="confirmDelete = false">
        <form method="POST" action="{{ route('taladsod.listing.destroy', $listing) }}" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="el-del-h"
              x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf
            @method('DELETE')
            <input type="hidden" name="return_to" value="listings">
            <h2 id="el-del-h" class="ts-h2"><i class="fas fa-trash-can" style="color:var(--ts-bad);" aria-hidden="true"></i> ลบ "{{ $listing->title }}"?</h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;">สินค้าจะหายจากหน้าร้านทันที ออเดอร์เก่ายังเห็นชื่อสินค้าได้ตามปกติ — ถ้ามีออเดอร์ที่ยังไม่เสร็จ ระบบจะไม่ให้ลบ</p>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="confirmDelete = false">ไม่ลบ</button>
                <button type="submit" class="ts-btn3d ts-tone-bad sm" :disabled="sending"><i class="fas fa-trash-can" aria-hidden="true"></i> ลบสินค้า</button>
            </div>
        </form>
    </div>
</div>
@endsection

{{--
 | ลงขายสินค้าใหม่ (taladsod.listing.create) — ร้านตลาดสด — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@createListing
 | ตัวแปร: $seller, $categories, $maxCashbackPercent, $gpRate (%), $gpFree (bool)
 | ส่งฟอร์ม POST taladsod.listing.store (multipart) — ฟิลด์ตาม partials/listing-form
 --}}
@extends('layouts.user-v4')

@section('title', 'ลงขายสินค้าใหม่ · ร้านตลาดสด')

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')

<div class="ts-scope ts-stack" style="gap:16px; max-width:920px; margin:0 auto;">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'listings'])

    <div class="ts-row" style="justify-content:space-between;">
        <div>
            <h1 class="ts-h1">ลงขายสินค้าใหม่</h1>
            <p class="ts-muted" style="margin:6px 0 0; font-size:13.5px;">ร้าน {{ $seller->shop_name }} · ใส่รูปสวยๆ และตัวเลือกให้ครบ ลูกค้าสั่งง่ายขึ้น</p>
        </div>
        <a href="{{ route('taladsod.seller.listings') }}" class="tp-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i> กลับไปรายการสินค้า</a>
    </div>

    <form method="POST" action="{{ route('taladsod.listing.store') }}" enctype="multipart/form-data" class="ts-stack" style="gap:16px;"
          x-data="{ sending: false }" x-on:submit="sending = true">
        @csrf
        @include('taladsod.partials.listing-form', ['mode' => 'create', 'listing' => null, 'optionGroups' => []])

        <div class="tp-card ts-row" style="justify-content:space-between; position:sticky; bottom:12px; z-index:20;">
            <span class="ts-muted ts-small">สินค้าขึ้นหน้าร้านทันทีหลังบันทึก{{ $seller->is_verified ? '' : ' (เมื่อร้านได้รับการยืนยัน)' }}</span>
            <button type="submit" class="ts-btn3d ts-tone-gold" :disabled="sending">
                <i class="fas" :class="sending ? 'fa-circle-notch ts-spin' : 'fa-circle-check'" aria-hidden="true"></i> ลงขายสินค้า
            </button>
        </div>
    </form>
</div>
@endsection

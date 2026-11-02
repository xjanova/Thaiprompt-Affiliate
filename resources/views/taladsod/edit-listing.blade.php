{{--
 | แก้ไขสินค้า (taladsod.listing.edit) — ร้านตลาดสด — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@editListing (สินค้าของร้านอื่น = 403)
 | ตัวแปร: $listing, $seller, $categories, $maxCashbackPercent, $optionGroups (API รวมตัวเลือกที่ปิดขาย), $gpRate, $gpFree
 | ส่งฟอร์ม PUT taladsod.listing.update (multipart) — ฟิลด์ตาม partials/listing-form + is_available, remove_images[], main_image, option_groups_present=1
 | ลบสินค้า: DELETE taladsod.listing.destroy {return_to=listings} (มีกล่องยืนยัน)
 --}}
@extends('layouts.user-nova')

@section('title', 'แก้ไข '.$listing->title.' · ร้านตลาดสด')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $status = $ui::listingStatus($listing);
@endphp

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')

<div class="nvm-stack" x-data="{ confirmDelete: false }">

<section class="nvm-ph" aria-labelledby="nvm-td-el-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('taladsod.seller.listings') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>รายการสินค้า</a>
            <div class="nvm-eyebrow nvm-latin">Edit listing</div>
            <h1 id="nvm-td-el-h">แก้ไขสินค้า</h1>
            <p class="nvm-ph__lead">{{ $listing->title }} · ดู <span class="nvm-num">{{ number_format((int) $listing->view_count) }}</span> ครั้ง · สั่ง <span class="nvm-num">{{ number_format((int) $listing->order_count) }}</span> ครั้ง</p>
            <div class="nvm-pills">
                <span class="nvm-pill {{ in_array($status['tone'], ['ok', 'warn', 'bad'], true) ? 'nvm-pill--'.$status['tone'] : 'nvm-pill--glass' }}">{{ $status['label'] }}</span>
            </div>
            <div class="nvm-hero__actions nvm-td-acts">
                <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ route('taladsod.listing', $listing->slug) }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูหน้าสินค้า</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/basket.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'listings'])

<form method="POST" action="{{ route('taladsod.listing.update', $listing) }}" enctype="multipart/form-data" class="nvm-stack"
      x-data="{ sending: false }" x-on:submit="sending = true" @pageshow.window="sending = false">
    @csrf
    @method('PUT')
    @include('taladsod.partials.listing-form', ['mode' => 'edit', 'listing' => $listing, 'optionGroups' => $optionGroups])

    <div class="nvm-card nvm-td-bar">
        <button type="button" class="nvm-btn nvm-btn--line" x-on:click="confirmDelete = true"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบสินค้า</button>
        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="sending">
            <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="sending ? 'กำลังบันทึก…' : 'บันทึกการแก้ไข'">บันทึกการแก้ไข</span>
        </button>
    </div>
</form>

{{-- กล่องยืนยันลบ (ฟอร์มแยก — ไม่ซ้อนในฟอร์มแก้ไข) --}}
<div class="nvm-modal" x-show="confirmDelete" x-cloak x-transition.opacity x-on:keydown.escape.window="confirmDelete = false" x-on:click.self="confirmDelete = false">
    <form method="POST" action="{{ route('taladsod.listing.destroy', $listing) }}" class="nvm-card nvm-modal__box" role="dialog" aria-modal="true" aria-labelledby="el-del-h"
          x-data="{ sending: false }" x-on:submit="sending = true">
        @csrf
        @method('DELETE')
        <input type="hidden" name="return_to" value="listings">
        <h2 id="el-del-h">ลบ "{{ $listing->title }}"?</h2>
        <p>สินค้าจะหายจากหน้าร้านทันที ออเดอร์เก่ายังเห็นชื่อสินค้าได้ตามปกติ — ถ้ามีออเดอร์ที่ยังไม่เสร็จ ระบบจะไม่ให้ลบ</p>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" x-on:click="confirmDelete = false">ไม่ลบ</button>
            <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="sending"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบสินค้า</button>
        </div>
    </form>
</div>

</div>
@endsection

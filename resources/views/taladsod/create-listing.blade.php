{{--
 | ลงขายสินค้าใหม่ (taladsod.listing.create) — ร้านตลาดสด — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@createListing
 | ตัวแปร: $seller, $categories, $maxCashbackPercent, $gpRate (%), $gpFree (bool)
 | ส่งฟอร์ม POST taladsod.listing.store (multipart) — ฟิลด์ตาม partials/listing-form
 --}}
@extends('layouts.user-nova')

@section('title', 'ลงขายสินค้าใหม่ · ร้านตลาดสด')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-td-cl-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('taladsod.seller.listings') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>รายการสินค้า</a>
            <div class="nvm-eyebrow nvm-latin">New listing</div>
            <h1 id="nvm-td-cl-h">ลงขายสินค้าใหม่</h1>
            <p class="nvm-ph__lead">ร้าน {{ $seller->shop_name }} · ใส่รูปสวยๆ และตัวเลือกให้ครบ ลูกค้าสั่งง่ายขึ้น</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/basket.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'listings'])

<form method="POST" action="{{ route('taladsod.listing.store') }}" enctype="multipart/form-data" class="nvm-stack"
      x-data="{ sending: false }" x-on:submit="sending = true" @pageshow.window="sending = false">
    @csrf
    @include('taladsod.partials.listing-form', ['mode' => 'create', 'listing' => null, 'optionGroups' => []])

    <div class="nvm-card nvm-td-bar">
        <span>สินค้าขึ้นหน้าร้านทันทีหลังบันทึก{{ $seller->is_verified ? '' : ' (เมื่อร้านได้รับการยืนยัน)' }}</span>
        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="sending">
            <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="sending ? 'กำลังบันทึก…' : 'ลงขายสินค้า'">ลงขายสินค้า</span>
        </button>
    </div>
</form>

</div>
@endsection

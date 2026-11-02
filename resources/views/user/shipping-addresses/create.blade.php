{{--
    เพิ่มที่อยู่จัดส่ง (ธีมโนวาเต็ม) — POST shipping-addresses.store (ShippingAddressController::store)
    ที่อยู่แรกของสมาชิกถูกตั้งเป็นค่าเริ่มต้นให้อัตโนมัติ
--}}
@extends('layouts.user-nova')

@section('title', 'เพิ่มที่อยู่จัดส่ง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@section('content')

<div class="nvm-stack nvm-os-narrow">

<section class="nvm-ph" aria-labelledby="nvm-os-sac-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('shipping-addresses.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ที่อยู่จัดส่ง</a>
            <div class="nvm-eyebrow nvm-latin">New address</div>
            <h1 id="nvm-os-sac-h">เพิ่มที่อยู่จัดส่งใหม่</h1>
            <p class="nvm-ph__lead">กรอกชื่อผู้รับ เบอร์โทร และที่อยู่ให้ครบ เพื่อให้ขนส่งหรือไรเดอร์ส่งถึงมือคุณได้ถูกต้อง</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-os-sac-f">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Address</small><h2 id="nvm-os-sac-f">ข้อมูลที่อยู่</h2></div></div>
    @include('user.shipping-addresses.partials.form', [
        'address' => null,
        'action' => route('shipping-addresses.store'),
        'method' => 'POST',
        'submitLabel' => 'บันทึกที่อยู่',
    ])
</section>

</div>
@endsection

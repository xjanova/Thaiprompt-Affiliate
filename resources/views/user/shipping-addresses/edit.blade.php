{{--
    แก้ไขที่อยู่จัดส่ง (ธีมโนวาเต็ม) — PUT shipping-addresses.update (ShippingAddressController::update)
    $address = ที่อยู่ของสมาชิกคนนี้เท่านั้น (คอนโทรลเลอร์กรอง user_id → ของคนอื่นได้ 404)
--}}
@extends('layouts.user-nova')

@section('title', 'แก้ไขที่อยู่จัดส่ง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@section('content')

<div class="nvm-stack nvm-os-narrow">

<section class="nvm-ph" aria-labelledby="nvm-os-sae-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('shipping-addresses.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ที่อยู่จัดส่ง</a>
            <div class="nvm-eyebrow nvm-latin">Edit address</div>
            <h1 id="nvm-os-sae-h">แก้ไขที่อยู่จัดส่ง</h1>
            <p class="nvm-ph__lead">แก้แล้วมีผลกับคำสั่งซื้อครั้งถัดไป คำสั่งซื้อที่สั่งไปแล้วยังส่งตามที่อยู่เดิม</p>
            @if($address->is_default)
                <div class="nvm-pills"><span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ที่อยู่เริ่มต้น</span></div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-os-sae-f">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Address</small><h2 id="nvm-os-sae-f">ข้อมูลที่อยู่</h2></div></div>
    @include('user.shipping-addresses.partials.form', [
        'address' => $address,
        'action' => route('shipping-addresses.update', $address->id),
        'method' => 'PUT',
        'submitLabel' => 'บันทึกการแก้ไข',
    ])
</section>

</div>
@endsection

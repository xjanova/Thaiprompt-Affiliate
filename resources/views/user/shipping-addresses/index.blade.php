{{--
    ที่อยู่จัดส่งของฉัน (ธีมโนวาเต็ม)
    ข้อมูลจาก ShippingAddressController::index — $addresses (ของสมาชิกคนนี้เท่านั้น เรียงค่าเริ่มต้นก่อน)
    ตั้งค่าเริ่มต้น POST shipping-addresses.set-default · ลบ DELETE shipping-addresses.destroy (ยืนยันในหน้าต่างก่อนลบ)
--}}
@extends('layouts.user-nova')

@section('title', 'ที่อยู่จัดส่ง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@section('content')

<div class="nvm-stack" x-data="{ del: null }" @keydown.escape.window="del = null">

<section class="nvm-ph" aria-labelledby="nvm-os-sa-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.profile') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>โปรไฟล์</a>
            <div class="nvm-eyebrow nvm-latin">Shipping</div>
            <h1 id="nvm-os-sa-h">ที่อยู่จัดส่ง</h1>
            <p class="nvm-ph__lead">จัดการที่อยู่สำหรับรับสินค้า ที่อยู่เริ่มต้นจะถูกเลือกให้อัตโนมัติตอนสั่งซื้อ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span class="nvm-num">{{ number_format($addresses->count()) }}</span> ที่อยู่</span>
            </div>
            <div class="nvm-os-acts nvm-os-acts--ph">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('shipping-addresses.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มที่อยู่ใหม่</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($addresses->isEmpty())
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีที่อยู่จัดส่ง</b>
            เพิ่มที่อยู่ไว้ก่อน สั่งซื้อครั้งถัดไปจะได้ไม่ต้องกรอกใหม่
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('shipping-addresses.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มที่อยู่จัดส่ง</a></div>
        </div>
    </section>
@else
    <div class="nvm-os-addrs">
        @foreach($addresses as $address)
            <article class="nvm-card nvm-os-addr {{ $address->is_default ? 'is-default' : '' }}" aria-label="ที่อยู่ของ {{ $address->recipient_name }}">
                <div class="nvm-os-addr__head">
                    <b>{{ $address->recipient_name }}</b>
                    @if($address->is_default)
                        <span class="nvm-chip nvm-chip--gold"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ค่าเริ่มต้น</span>
                    @endif
                </div>
                <ul class="nvm-os-lines">
                    <li><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span class="nvm-num">{{ $address->phone_number }}</span></li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span>{{ $address->full_address }}</span></li>
                    @if($address->notes)
                        <li class="is-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>{{ $address->notes }}</span></li>
                    @endif
                </ul>
                <div class="nvm-os-acts">
                    @unless($address->is_default)
                        <form method="POST" action="{{ route('shipping-addresses.set-default', $address->id) }}">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ตั้งเป็นค่าเริ่มต้น</button>
                        </form>
                    @endunless
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('shipping-addresses.edit', $address->id) }}"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>แก้ไข</a>
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm nvm-os-btn-bad"
                            @click="del = { url: @js(route('shipping-addresses.destroy', $address->id)), name: @js($address->recipient_name), def: {{ $address->is_default ? 'true' : 'false' }} }">
                        <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบ
                    </button>
                </div>
            </article>
        @endforeach
    </div>
@endif

{{-- หน้าต่างยืนยันการลบ --}}
<div class="nvm-modal nvm-os-modal" x-show="del" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-os-sa-del-h" @click.self="del = null">
    <div class="nvm-card nvm-modal__box">
        <h2 id="nvm-os-sa-del-h"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ลบที่อยู่นี้?</h2>
        <p class="nvm-desc nvm-gap-t">ที่อยู่ของ <b x-text="del && del.name"></b> จะถูกลบออกจากรายการ คำสั่งซื้อเดิมไม่ได้รับผลกระทบ</p>
        <p class="nvm-note" x-show="del && del.def"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>นี่คือที่อยู่เริ่มต้น ระบบจะตั้งที่อยู่อื่นเป็นค่าเริ่มต้นแทนให้อัตโนมัติ</p>
        <form method="POST" :action="del ? del.url : ''" x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
            @csrf
            @method('DELETE')
            <div class="nvm-filter__btns nvm-gap-t">
                <button type="button" class="nvm-btn nvm-btn--line" @click="del = null">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบที่อยู่</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection

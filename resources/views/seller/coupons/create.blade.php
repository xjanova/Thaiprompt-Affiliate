@extends('layouts.seller-v4')

@section('title', 'สร้างคูปองใหม่')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="สร้างคูปองใหม่" icon="🎟️" crumb="ร้านค้า · การตลาด · คูปองร้าน"
                         subtitle="ระบบจะสร้างรหัสคูปองให้อัตโนมัติหลังบันทึก">
        <a href="{{ route('seller.coupons.index') }}" class="tp-btn tp-btn-sm">← คูปองทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <x-seller-kit.errors />

    @include('seller.coupons.partials.form', [
        'coupon' => null,
        'action' => route('seller.coupons.store'),
        'method' => 'POST',
        'submitLabel' => '🎟️ สร้างคูปอง',
    ])
</div>
@endsection

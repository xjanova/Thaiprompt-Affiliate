@extends('layouts.seller-v4')

@section('title', 'แก้ไขคูปอง '.$coupon->code)

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="'แก้ไขคูปอง '.$coupon->code" icon="🎟️" crumb="ร้านค้า · การตลาด · คูปองร้าน"
                         :subtitle="'ใช้ไปแล้ว '.number_format((int) $coupon->used_count).($coupon->usage_limit ? ' / '.number_format((int) $coupon->usage_limit) : '').' ครั้ง · รหัสคูปองเปลี่ยนไม่ได้'">
        <a href="{{ route('seller.coupons.index') }}" class="tp-btn tp-btn-sm">← คูปองทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <x-seller-kit.errors />

    @if((int) $coupon->used_count > 0)
        <div class="tp-card" style="border-left:4px solid var(--tp-warn, #e0a52e); padding:12px 16px; font-size:12.5px;">
            ⚠️ คูปองนี้มีลูกค้าใช้ไปแล้ว การแก้ไขมูลค่าส่วนลดจะมีผลกับการใช้ครั้งถัดไปเท่านั้น ออเดอร์เดิมไม่เปลี่ยน
        </div>
    @endif

    @include('seller.coupons.partials.form', [
        'coupon' => $coupon,
        'action' => route('seller.coupons.update', $coupon),
        'method' => 'PUT',
        'submitLabel' => '💾 บันทึกการแก้ไข',
    ])
</div>
@endsection

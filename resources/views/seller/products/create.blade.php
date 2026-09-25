@extends('layouts.seller-v4')

@section('title', 'เพิ่มสินค้าใหม่')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">
    <x-seller-v4.header title="เพิ่มสินค้าใหม่" subtitle="กรอกข้อมูลให้ครบ แล้วดูรายได้สุทธิแบบสดที่แผงด้านขวา" icon="➕"
                        :back="route('seller.products.index')" />

    <x-seller-v4.errors />

    @include('seller.products.partials.form', ['product' => null])
</div>
@endsection

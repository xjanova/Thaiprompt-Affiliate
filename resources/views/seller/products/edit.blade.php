@extends('layouts.seller-v4')

@section('title', 'แก้ไขสินค้า - ' . $product->name)

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">
    <x-seller-v4.header :title="'แก้ไขสินค้า'" :subtitle="$product->name" icon="✏️" :back="route('seller.products.index')">
        <span class="sv4-pill" style="{{ \App\Support\Seller\SellerUi::pill($product->is_active ? \App\Support\Seller\SellerUi::OK : \App\Support\Seller\SellerUi::MUTED) }} font-size:12px; padding:7px 12px;">
            {{ $product->is_active ? '● เปิดขายอยู่' : '○ ปิดการขาย' }}
        </span>
        <a href="{{ route('seller.pricing.planner', ['product_id' => $product->id]) }}" class="tp-btn tp-btn-sm">💡 วางแผนราคา</a>
    </x-seller-v4.header>

    @if($product->is_blocked)
        <div class="sv4-note" style="--c:{{ \App\Support\Seller\SellerUi::BAD }};">
            ⛔ สินค้านี้ถูกระงับโดยแอดมิน{{ $product->block_reason ? ' — ' . $product->block_reason : '' }} ลูกค้าจะยังไม่เห็นสินค้าจนกว่าจะปลดระงับ
        </div>
    @endif

    <x-seller-v4.errors />

    @include('seller.products.partials.form', ['product' => $product])
</div>
@endsection

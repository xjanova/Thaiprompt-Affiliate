@extends('layouts.admin-v4')

@section('title', 'แก้ไขสินค้า Official Shop')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · Official Shop · แก้ไขสินค้า</div>
            <h1 style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">{{ $product->name }}</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ขายแล้ว <span class="tp-num">{{ number_format((int) $product->sales_count) }}</span> ชิ้น · เข้าชม <span class="tp-num">{{ number_format((int) $product->view_count) }}</span> ครั้ง</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.official-shop.products.show', $product) }}" class="tp-btn tp-btn-sm"><i class="fas fa-eye"></i> ดูสินค้า</a>
            <a href="{{ route('admin.official-shop.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการ</a>
        </div>
    </div>

    @include('admin.official-shop.products.partials.form', [
        'product' => $product,
        'action' => route('admin.official-shop.products.update', $product),
        'method' => 'PUT',
    ])
</div>
@endsection

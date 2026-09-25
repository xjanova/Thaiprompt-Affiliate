@extends('layouts.admin-v4')

@section('title', 'เพิ่มสินค้า Official Shop')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · Official Shop · เพิ่มสินค้า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">เพิ่มสินค้าร้านทางการ 👑</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สินค้าที่แพลตฟอร์มขายเอง ไม่มีค่า GP</div>
        </div>
        <a href="{{ route('admin.official-shop.products.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการ</a>
    </div>

    @include('admin.official-shop.products.partials.form', [
        'product' => null,
        'action' => route('admin.official-shop.products.store'),
        'method' => 'POST',
    ])
</div>
@endsection

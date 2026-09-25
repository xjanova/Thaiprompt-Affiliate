@extends('layouts.admin-v4')

@section('title', 'เพิ่มแบนเนอร์หน้าร้าน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า · แบนเนอร์</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">เพิ่มแบนเนอร์ใหม่ ✨</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">แบนเนอร์บนหน้าแรกหรือหน้าหมวดหมู่ของตลาดรวมร้านค้า</div>
        </div>
        <a href="{{ route('admin.storefront.banners.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการแบนเนอร์</a>
    </div>

    @include('admin.storefront.banners.partials.form', [
        'banner' => null,
        'action' => route('admin.storefront.banners.store'),
        'method' => 'POST',
    ])
</div>
@endsection

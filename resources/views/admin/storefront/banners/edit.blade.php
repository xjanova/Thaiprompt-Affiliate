@extends('layouts.admin-v4')

@section('title', 'แก้ไขแบนเนอร์หน้าร้าน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า · แบนเนอร์</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">แก้ไขแบนเนอร์ {{ $banner->title ? '· '.$banner->title : '#'.$banner->id }}</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                แสดงแล้ว <span class="tp-num">{{ number_format((int) $banner->view_count) }}</span> ครั้ง ·
                คลิก <span class="tp-num">{{ number_format((int) $banner->click_count) }}</span> ครั้ง ·
                CTR <span class="tp-num">{{ $banner->ctr }}%</span>
            </div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.storefront.banners.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการแบนเนอร์</a>
            <form method="POST" action="{{ route('admin.storefront.banners.destroy', $banner->id) }}"
                  @submit="if (!confirm('ลบแบนเนอร์นี้ถาวร (รวมไฟล์รูป)?')) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad,#d9534f);"><i class="fas fa-trash"></i> ลบ</button>
            </form>
        </div>
    </div>

    @include('admin.storefront.banners.partials.form', [
        'banner' => $banner,
        'action' => route('admin.storefront.banners.update', $banner->id),
        'method' => 'PUT',
    ])
</div>
@endsection

{{--
 | รายการร้านค้าทั้งหมด — ธีม V4 (frontend-v4)
 | ข้อมูลจาก StorefrontController@stores: $stores (paginator, products 4 ชิ้น + products_count), $categories, $stats, $sortBy
 | ตัวกรอง: ?search= ?featured=1 ?sort_by=rating|newest|products|name
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ร้านค้าทั้งหมด')
@section('meta_description', 'รายการร้านค้าคุณภาพทั้งหมดในระบบ ช้อปสินค้าจากร้านค้าที่ไว้วางใจได้')

@php
    $stSearch = is_scalar(request('search')) ? (string) request('search') : '';
    $stFeatured = (bool) request('featured');
    $stSorts = ['rating' => 'คะแนนสูงสุด', 'newest' => 'ร้านใหม่ล่าสุด', 'products' => 'สินค้ามากสุด', 'name' => 'ชื่อร้าน (ก-ฮ)'];
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:24px;">
        <div style="position:relative; overflow:hidden; border-radius:26px; padding:clamp(22px, 4vw, 38px); color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow:0 14px 36px rgba(0,0,0,.16);">
            <div aria-hidden="true" style="position:absolute; inset:0; background:radial-gradient(520px 260px at 90% 0%, rgba(255,255,255,.25), transparent 60%);"></div>
            <div style="position:relative; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:18px;">
                <div>
                    <nav class="sf-breadcrumb" aria-label="เส้นทาง" style="color:rgba(255,255,255,.85);">
                        <a href="{{ route('storefront.index') }}" style="color:inherit;">ร้านค้า</a>
                        <span aria-hidden="true">/</span>
                        <span>ร้านค้าทั้งหมด</span>
                    </nav>
                    <h1 style="margin:8px 0 6px; font-size:clamp(24px, 4.4vw, 34px); font-weight:800; text-shadow:0 1px 3px rgba(0,0,0,.15);">🏪 ร้านค้าทั้งหมด</h1>
                    <p style="margin:0; opacity:.92;">ค้นพบร้านค้าคุณภาพ ช้อปตรงจากร้านที่ไว้ใจได้</p>
                </div>
                <div style="display:flex; gap:10px;">
                    <div style="padding:12px 18px; border-radius:18px; text-align:center; background:rgba(255,255,255,.18);">
                        <div class="tp-num" style="font-size:24px; font-weight:800;">{{ number_format($stats['total_stores'] ?? 0) }}</div>
                        <div style="font-size:12px; opacity:.9;">ร้านค้า</div>
                    </div>
                    <div style="padding:12px 18px; border-radius:18px; text-align:center; background:rgba(255,255,255,.18);">
                        <div class="tp-num" style="font-size:24px; font-weight:800;">{{ number_format($stats['total_products'] ?? 0) }}</div>
                        <div style="font-size:12px; opacity:.9;">สินค้า</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="sf-wrap sf-section">
        <div class="tp-card" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; justify-content:space-between;">
            <form method="GET" action="{{ route('storefront.stores') }}" role="search" style="flex:1 1 260px; display:flex; gap:8px; max-width:520px;">
                @if($stFeatured)
                    <input type="hidden" name="featured" value="1">
                @endif
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <label for="st-search" style="position:absolute; left:-9999px;">ค้นหาร้านค้า</label>
                <input id="st-search" type="search" name="search" value="{{ $stSearch }}" class="tp-input" placeholder="ค้นหาชื่อร้าน..." style="height:44px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="height:44px;"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
            </form>
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <a href="{{ route('storefront.stores', array_filter(['search' => $stSearch, 'sort_by' => $sortBy])) }}" class="sf-chip {{ $stFeatured ? '' : 'is-on' }}">ทั้งหมด</a>
                <a href="{{ route('storefront.stores', array_filter(['search' => $stSearch, 'sort_by' => $sortBy, 'featured' => 1])) }}" class="sf-chip {{ $stFeatured ? 'is-on' : '' }}"><i class="fas fa-star"></i> ร้านแนะนำ</a>
                <form method="GET" action="{{ route('storefront.stores') }}" style="display:flex; align-items:center; gap:6px;">
                    @if($stSearch !== '')
                        <input type="hidden" name="search" value="{{ $stSearch }}">
                    @endif
                    @if($stFeatured)
                        <input type="hidden" name="featured" value="1">
                    @endif
                    <label for="st-sort" class="tp-muted" style="font-size:12.5px; font-weight:600;">เรียง</label>
                    <select id="st-sort" name="sort_by" class="tp-input" style="height:44px; width:auto; padding:0 12px;" onchange="this.form.submit()">
                        @foreach($stSorts as $sk => $sl)
                            <option value="{{ $sk }}" @selected($sortBy === $sk)>{{ $sl }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </section>

    <section class="sf-wrap">
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:14px;">
            @if($stores->onFirstPage() && $stSearch === '' && ! $stFeatured)
                <x-theme-v4.store-card :official="true" />
            @endif
            @foreach($stores as $store)
                <x-theme-v4.store-card :store="$store" :products="$store->products" />
            @endforeach
        </div>

        @if($stores->isEmpty())
            <div class="tp-card" style="text-align:center; padding:40px 16px; margin-top:14px;">
                <div style="font-size:44px;" aria-hidden="true">🏪</div>
                <h2 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ไม่พบร้านค้า</h2>
                <p class="tp-muted" style="margin:0 0 14px; font-size:13.5px;">ลองค้นหาด้วยชื่ออื่น หรือดูร้านทั้งหมด</p>
                <a href="{{ route('storefront.stores') }}" class="sf-btn3d">ดูร้านทั้งหมด</a>
            </div>
        @endif

        @if($stores->hasPages())
            <div style="margin-top:20px;">
                {{ $stores->withQueryString()->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}
            </div>
        @endif
    </section>
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection

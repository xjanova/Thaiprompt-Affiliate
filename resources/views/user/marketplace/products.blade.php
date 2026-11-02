{{--
    สินค้าแนะนำสำหรับแอฟฟิลิเอท (ธีมโนวาเต็ม)
    MarketplaceController::products ส่งมา: $products (paginator 12 ชิ้น, withQueryString), $categories
    ตัวกรองเป็น GET: search · category · min_price · max_price · sort_by
--}}
@extends('layouts.user-nova')

@section('title', 'สินค้าแนะนำสำหรับแอฟฟิลิเอท')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $sorts = [
        'newest' => 'ใหม่ล่าสุด',
        'popular' => 'ขายดี',
        'price_low' => 'ราคาต่ำ-สูง',
        'price_high' => 'ราคาสูง-ต่ำ',
        'commission_high' => 'คอมมิชชั่นสูงสุด',
    ];
    $filtered = request()->hasAny(['search', 'category', 'min_price', 'max_price', 'sort_by']);
    $maxRate = (float) ($products->max('commission_rate') ?? 0);
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-um-mp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Marketplace affiliate</div>
            <h1 id="nvm-um-mp-h">แชร์สินค้า รับค่าคอมมิชชั่น</h1>
            <p class="nvm-ph__lead">เลือกสินค้าคุณภาพ แชร์ให้เพื่อน รับค่าคอมมิชชั่นทันที พร้อมโอกาสสร้างรายได้ไม่จำกัด</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>สินค้า {{ number_format($products->total()) }} รายการ</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>{{ number_format($categories->count()) }} หมวดหมู่</span>
                @if($maxRate > 0)
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>คอมมิชชั่นสูงสุด {{ number_format($maxRate) }}%</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/svc/market.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
<section class="nvm-card nvm-box" aria-labelledby="nvm-um-mp-f">
    <h2 class="nvm-sr" id="nvm-um-mp-f">ค้นหาและกรองสินค้า</h2>
    <form method="GET" action="{{ route('user.marketplace.products') }}" class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="mp-search">ค้นหา</label>
            <input type="search" id="mp-search" name="search" class="nvm-input" value="{{ is_string(request('search')) ? request('search') : '' }}" placeholder="ชื่อสินค้า…">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="mp-category">หมวดหมู่</label>
            <select id="mp-category" name="category" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <span class="nvm-label" id="mp-price-lbl">ราคา (฿)</span>
            <div class="nvm-um-range" role="group" aria-labelledby="mp-price-lbl">
                <input type="number" name="min_price" class="nvm-input nvm-num" min="0" value="{{ is_numeric(request('min_price')) ? request('min_price') : '' }}" placeholder="ต่ำสุด" aria-label="ราคาต่ำสุด">
                <input type="number" name="max_price" class="nvm-input nvm-num" min="0" value="{{ is_numeric(request('max_price')) ? request('max_price') : '' }}" placeholder="สูงสุด" aria-label="ราคาสูงสุด">
            </div>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="mp-sort">เรียงตาม</label>
            <select id="mp-sort" name="sort_by" class="nvm-input">
                @foreach($sorts as $value => $label)
                    <option value="{{ $value }}" @selected(request('sort_by') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ค้นหา</button>
            @if($filtered)
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.marketplace.products') }}">ล้าง</a>
            @endif
        </div>
    </form>
</section>

{{-- ── รายการสินค้า ─────────────────────────────────────── --}}
@if($products->count() > 0)
    <section class="nvm-um-mp" aria-label="รายการสินค้า">
        @foreach($products as $product)
            <article class="nvm-card nvm-um-mp__item">
                <div class="nvm-um-mp__img">
                    @if($product->primary_image)
                        <img src="{{ $product->primary_image }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <img class="is-ph" src="{{ asset('images/nova/icons/bag.webp') }}" alt="" loading="lazy">
                    @endif
                    @if($product->commission_rate > 0)
                        <span class="nvm-chip nvm-chip--gold nvm-um-mp__rate"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>{{ number_format((float) $product->commission_rate) }}%</span>
                    @endif
                    @if($product->is_featured)
                        <span class="nvm-chip nvm-chip--ok nvm-um-mp__feat">แนะนำ</span>
                    @endif
                </div>
                <div class="nvm-um-mp__body">
                    @if($product->category)<small class="nvm-um-mp__cat">{{ $product->category->name }}</small>@endif
                    <h3>{{ $product->name }}</h3>
                    @if($product->short_description)<p>{{ $product->short_description }}</p>@endif
                    <div class="nvm-um-mp__price">
                        <b class="nvm-num">{{ $baht($product->price) }}</b>
                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <s class="nvm-num">{{ $baht($product->compare_at_price) }}</s>
                            <span class="nvm-chip nvm-chip--bad">-{{ $product->discount_percentage }}%</span>
                        @endif
                    </div>
                    @unless($product->is_affiliate)
                        <x-tpix-price :product="$product" :thb="$product->price" class="nvm-um-mp__tpix" />
                    @endunless
                    @if($product->commission_rate > 0)
                        <div class="nvm-um-mp__earn">
                            <small>คอมมิชชั่นที่คุณจะได้รับ</small>
                            <b class="nvm-num">{{ $baht($product->calculateCommission((float) $product->price)) }}</b>
                        </div>
                    @endif
                    <div class="nvm-um-mp__meta">
                        <span><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>{{ number_format((int) $product->view_count) }}</span>
                        <span><svg class="nvm-ico"><use href="#nvm-i-cart"/></svg>{{ number_format((int) $product->sales_count) }} ขาย</span>
                        @if($product->rating_count > 0)
                            <span><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ number_format((float) $product->rating_average, 1) }}</span>
                        @endif
                    </div>
                    <a class="nvm-btn nvm-btn--gold nvm-btn--sm nvm-btn--block" href="{{ route('shop.show', $product->slug ?: $product->id) }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ดูสินค้าและแชร์</a>
                </div>
            </article>
        @endforeach
    </section>
    @if($products->hasPages())
        <div class="nvm-pager">{{ $products->links() }}</div>
    @endif
@else
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ไม่พบสินค้า</b>
            ลองปรับเกณฑ์การค้นหาใหม่อีกครั้ง
            @if($filtered)
                <div><a class="nvm-btn nvm-btn--line" href="{{ route('user.marketplace.products') }}">ดูสินค้าทั้งหมด</a></div>
            @endif
        </div>
    </section>
@endif

{{-- ── วิธีสร้างรายได้ ───────────────────────────────────── --}}
<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-um-mp-how">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How to earn</small><h2 id="nvm-um-mp-how">วิธีสร้างรายได้จากการแชร์สินค้า</h2></div></div>
        <ol class="nvm-steps">
            <li><span><b>เลือกสินค้า</b> — เลือกสินค้าที่คุณสนใจจากรายการด้านบน</span></li>
            <li><span><b>แชร์ลิงก์</b> — แชร์ลิงก์สินค้าให้เพื่อนและครอบครัว</span></li>
            <li><span><b>มีคนซื้อ</b> — เมื่อมีคนซื้อผ่านลิงก์ของคุณ</span></li>
            <li><span><b>รับค่าคอมฯ</b> — คุณจะได้รับค่าคอมมิชชั่นทันที</span></li>
        </ol>
    </section>
    <aside class="nvm-card nvm-box nvm-um-example" aria-labelledby="nvm-um-mp-ex">
        <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="72" height="72" loading="lazy">
        <h2 id="nvm-um-mp-ex">ตัวอย่างรายได้</h2>
        <p>แชร์สินค้าราคา <b>1,000฿</b> คอมมิชชั่น <b>10%</b> มีคนซื้อ <b>100</b> คน</p>
        <b class="nvm-um-example__sum nvm-num">= รายได้ 10,000฿</b>
    </aside>
</div>
@endsection

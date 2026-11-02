{{--
 | สินค้า/เมนูทั้งหมดของร้าน (taladsod.seller.listings) — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@sellerListings
 | ตัวแปร: $seller, $listings (paginator + category, option_groups_count), $statusFilter (all|selling|hidden|sold_out|suspended),
 |         $counts [filter => จำนวน], $canCreate, $gpRate
 | ลบ: DELETE taladsod.listing.destroy {return_to=listings} (มีกล่องยืนยัน)
 --}}
@extends('layouts.user-nova')

@section('title', 'สินค้าของร้าน · ตลาดสด')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $filters = [
        'all' => 'ทั้งหมด',
        'selling' => 'เปิดขาย',
        'hidden' => 'ซ่อนไว้',
        'sold_out' => 'ของหมด',
        'suspended' => 'ถูกระงับ',
    ];
@endphp

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')

<div class="nvm-stack" x-data="{ del: null, delUrl: '', sending: false }">

<section class="nvm-ph" aria-labelledby="nvm-td-sl-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">My menu</div>
            <h1 id="nvm-td-sl-h">สินค้าและเมนูของร้าน</h1>
            <p class="nvm-ph__lead">GP ตอนนี้ {{ rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.') }}% · ลูกค้าเห็นเฉพาะสินค้าที่ "เปิดขาย"</p>
            <div class="nvm-hero__actions nvm-td-acts">
                @if($canCreate)
                    <a class="nvm-btn nvm-btn--gold" href="{{ route('taladsod.listing.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>ลงขายสินค้าใหม่</a>
                @else
                    <span class="nvm-pill nvm-pill--warn">ลงขายครบโควต้าแล้ว — ต่ออายุสมาชิกที่หน้าร้านวันนี้</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/basket.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'listings'])

<nav class="nvm-tabs" aria-label="กรองสินค้า">
    @foreach($filters as $key => $label)
        <a href="{{ route('taladsod.seller.listings', $key === 'all' ? [] : ['status' => $key]) }}" class="{{ $statusFilter === $key ? 'is-on' : '' }}" @if($statusFilter === $key) aria-current="page" @endif>
            {{ $label }} <span class="nvm-chip nvm-num">{{ number_format((int) ($counts[$key] ?? 0)) }}</span>
        </a>
    @endforeach
</nav>

@if($listings->count() > 0)
    <div class="nvm-td-grid">
        @foreach($listings as $listing)
            @php $st = $ui::listingStatus($listing); @endphp
            <article class="nvm-card nvm-td-item">
                <div class="nvm-td-item__top">
                    <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="nvm-td-thumb nvm-td-thumb--lg" aria-label="แก้ไข {{ $listing->title }}">
                        @if($listing->primary_image)
                            <img src="{{ $listing->primary_image }}" alt="" loading="lazy">
                        @else
                            <svg class="nvm-ico"><use href="#nvm-i-basket"/></svg>
                        @endif
                    </a>
                    <div class="nvm-td-item__txt">
                        <b>{{ $listing->title }}</b>
                        <div class="nvm-td-price nvm-num">฿{{ $ui::money($listing->price) }} <small>/ {{ $listing->unit }}</small></div>
                        <div class="nvm-td-chips nvm-gap-t">
                            <span class="{{ $ui::novaChip($st['tone']) }}">{{ $st['label'] }}</span>
                            @if($listing->tracksStock())
                                <span class="{{ (int) $listing->quantity_available <= 5 ? 'nvm-chip nvm-chip--warn' : 'nvm-chip' }}">เหลือ {{ number_format((int) $listing->quantity_available) }}</span>
                            @else
                                <span class="nvm-chip nvm-chip--ok">ทำตามสั่ง</span>
                            @endif
                            @if((int) $listing->option_groups_count > 0)
                                <span class="nvm-chip nvm-chip--info">{{ (int) $listing->option_groups_count }} กลุ่มตัวเลือก</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="nvm-td-item__meta">
                    @if($listing->category)<span>{{ $listing->category->icon }} {{ $listing->category->name }}</span>@endif
                    <span>ดู {{ number_format((int) $listing->view_count) }} ครั้ง</span>
                    <span>สั่ง {{ number_format((int) $listing->order_count) }} ครั้ง</span>
                </div>
                <div class="nvm-td-acts">
                    <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="nvm-btn nvm-btn--gold nvm-btn--sm nvm-td-grow">แก้ไข</a>
                    <a href="{{ route('taladsod.listing', $listing->slug) }}" class="nvm-td-ib" target="_blank" rel="noopener" aria-label="ดูหน้าสินค้า {{ $listing->title }}"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg></a>
                    <button type="button" class="nvm-td-ib is-bad" aria-label="ลบ {{ $listing->title }}"
                            x-on:click="del = @js($listing->title); delUrl = @js(route('taladsod.listing.destroy', $listing->id))">
                        <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
                    </button>
                </div>
            </article>
        @endforeach
    </div>
    @if($listings->hasPages())
        <div class="nvm-pager">{{ $listings->links() }}</div>
    @endif
@else
    <section class="nvm-card nvm-empty">
        <img src="{{ asset('images/nova/icons/basket.webp') }}" alt="" width="86" height="86" loading="lazy">
        <b>{{ $statusFilter === 'all' ? 'ยังไม่มีสินค้าในร้าน' : 'ไม่มีสินค้าในหมวดนี้' }}</b>
        ลงเมนูแรกของร้าน ใส่รูปสวยๆ ตั้งตัวเลือก แล้วกดเปิดร้านได้เลย
        @if($canCreate)
            <div><a href="{{ route('taladsod.listing.create') }}" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>ลงขายสินค้าแรก</a></div>
        @endif
    </section>
@endif

{{-- กล่องยืนยันลบ --}}
<div class="nvm-modal" x-show="del" x-cloak x-transition.opacity x-on:keydown.escape.window="del = null" x-on:click.self="del = null">
    <form method="POST" :action="delUrl" class="nvm-card nvm-modal__box" role="dialog" aria-modal="true" aria-labelledby="sl-del-h" x-on:submit="sending = true">
        @csrf
        @method('DELETE')
        <input type="hidden" name="return_to" value="listings">
        <h2 id="sl-del-h">ลบ "<span x-text="del"></span>"?</h2>
        <p>สินค้าจะหายจากหน้าร้านทันที — ถ้ามีออเดอร์ที่ยังไม่เสร็จ ระบบจะไม่ให้ลบ</p>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" x-on:click="del = null">ไม่ลบ</button>
            <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="sending"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบสินค้า</button>
        </div>
    </form>
</div>

</div>
@endsection

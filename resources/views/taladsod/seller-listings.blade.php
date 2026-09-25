{{--
 | สินค้า/เมนูทั้งหมดของร้าน (taladsod.seller.listings) — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@sellerListings
 | ตัวแปร: $seller, $listings (paginator + category, option_groups_count), $statusFilter (all|selling|hidden|sold_out|suspended),
 |         $counts [filter => จำนวน], $canCreate, $gpRate
 | ลบ: DELETE taladsod.listing.destroy {return_to=listings} (มีกล่องยืนยัน)
 --}}
@extends('layouts.user-v4')

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

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')

<div class="ts-scope ts-stack" style="gap:16px;" x-data="{ del: null, delUrl: '', sending: false }">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'listings'])

    <div class="ts-row" style="justify-content:space-between;">
        <div>
            <h1 class="ts-h1">สินค้าและเมนูของร้าน</h1>
            <p class="ts-muted" style="margin:6px 0 0; font-size:13.5px;">GP ตอนนี้ {{ rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.') }}% · ลูกค้าเห็นเฉพาะสินค้าที่ "เปิดขาย"</p>
        </div>
        @if($canCreate)
            <a href="{{ route('taladsod.listing.create') }}" class="ts-btn3d ts-tone-gold"><i class="fas fa-plus" aria-hidden="true"></i> ลงขายสินค้าใหม่</a>
        @else
            <span class="sf-note sf-note-warn ts-small">ลงขายครบโควต้าแล้ว — ต่ออายุสมาชิกที่หน้าร้านวันนี้</span>
        @endif
    </div>

    <div class="sf-scroll" role="tablist" aria-label="กรองสินค้า">
        @foreach($filters as $key => $label)
            <a href="{{ route('taladsod.seller.listings', $key === 'all' ? [] : ['status' => $key]) }}" class="sf-chip {{ $statusFilter === $key ? 'is-on' : '' }}" role="tab" aria-selected="{{ $statusFilter === $key ? 'true' : 'false' }}">
                {{ $label }} <span class="ts-num" style="opacity:.75;">{{ number_format((int) ($counts[$key] ?? 0)) }}</span>
            </a>
        @endforeach
    </div>

    @if($listings->count() > 0)
        <div class="ts-grid" style="--ts-min:280px;">
            @foreach($listings as $listing)
                @php $st = $ui::listingStatus($listing); @endphp
                <article class="tp-card ts-stack" style="padding:14px; gap:12px;">
                    <div class="ts-row" style="gap:12px; flex-wrap:nowrap; align-items:flex-start;">
                        <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="sf-thumb" style="width:76px; height:76px;">
                            @if($listing->primary_image)
                                <img src="{{ $listing->primary_image }}" alt="" loading="lazy">
                            @else
                                <span aria-hidden="true">🥬</span>
                            @endif
                        </a>
                        <div style="flex:1; min-width:0;">
                            <b style="display:block; font-size:14.5px; line-height:1.4; overflow-wrap:anywhere;">{{ $listing->title }}</b>
                            <div class="ts-row" style="gap:6px; margin-top:4px;">
                                <span class="ts-money" style="font-size:16px;">฿{{ $ui::money($listing->price) }}</span>
                                <span class="ts-muted ts-small">/ {{ $listing->unit }}</span>
                            </div>
                            <div class="ts-row" style="gap:6px; margin-top:6px;">
                                <span class="ts-pill ts-tone-{{ $st['tone'] }}">{{ $st['label'] }}</span>
                                @if($listing->tracksStock())
                                    <span class="ts-pill {{ (int) $listing->quantity_available <= 5 ? 'ts-tone-warn' : 'ts-tone-muted' }}">เหลือ {{ number_format((int) $listing->quantity_available) }}</span>
                                @else
                                    <span class="ts-pill ts-tone-ok">ทำตามสั่ง</span>
                                @endif
                                @if((int) $listing->option_groups_count > 0)
                                    <span class="ts-pill ts-tone-info"><i class="fas fa-sliders" aria-hidden="true"></i> {{ (int) $listing->option_groups_count }} กลุ่มตัวเลือก</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="ts-row ts-muted ts-small" style="gap:12px;">
                        @if($listing->category)<span>{{ $listing->category->icon }} {{ $listing->category->name }}</span>@endif
                        <span><i class="fas fa-eye" aria-hidden="true"></i> {{ number_format((int) $listing->view_count) }}</span>
                        <span><i class="fas fa-bag-shopping" aria-hidden="true"></i> {{ number_format((int) $listing->order_count) }}</span>
                    </div>
                    <div class="ts-row" style="gap:8px;">
                        <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="ts-btn3d sm ts-tone-gold" style="flex:1;"><i class="fas fa-pen" aria-hidden="true"></i> แก้ไข</a>
                        <a href="{{ route('taladsod.listing', $listing->slug) }}" class="tp-btn" target="_blank" rel="noopener" aria-label="ดูหน้าสินค้า {{ $listing->title }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                        <button type="button" class="tp-btn" style="color:var(--ts-bad);" aria-label="ลบ {{ $listing->title }}"
                                x-on:click="del = @js($listing->title); delUrl = @js(route('taladsod.listing.destroy', $listing->id))">
                            <i class="fas fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
        @if($listings->hasPages())
            <div>{{ $listings->links('vendor.pagination.tp-v4') }}</div>
        @endif
    @else
        <div class="tp-card ts-empty" style="padding:44px 18px;">
            <span class="em" aria-hidden="true">🍳</span>
            <b style="font-size:16px;">{{ $statusFilter === 'all' ? 'ยังไม่มีสินค้าในร้าน' : 'ไม่มีสินค้าในหมวดนี้' }}</b>
            <span class="ts-muted">ลงเมนูแรกของร้าน ใส่รูปสวยๆ ตั้งตัวเลือก แล้วกดเปิดร้านได้เลย</span>
            @if($canCreate)
                <a href="{{ route('taladsod.listing.create') }}" class="ts-btn3d ts-tone-gold"><i class="fas fa-plus" aria-hidden="true"></i> ลงขายสินค้าแรก</a>
            @endif
        </div>
    @endif

    {{-- กล่องยืนยันลบ --}}
    <div class="ts-dialog-bg" x-show="del" x-cloak x-transition.opacity x-on:keydown.escape.window="del = null" x-on:click.self="del = null">
        <form method="POST" :action="delUrl" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="sl-del-h" x-on:submit="sending = true">
            @csrf
            @method('DELETE')
            <input type="hidden" name="return_to" value="listings">
            <h2 id="sl-del-h" class="ts-h2"><i class="fas fa-trash-can" style="color:var(--ts-bad);" aria-hidden="true"></i> ลบ "<span x-text="del"></span>"?</h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;">สินค้าจะหายจากหน้าร้านทันที — ถ้ามีออเดอร์ที่ยังไม่เสร็จ ระบบจะไม่ให้ลบ</p>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="del = null">ไม่ลบ</button>
                <button type="submit" class="ts-btn3d ts-tone-bad sm" :disabled="sending"><i class="fas fa-trash-can" aria-hidden="true"></i> ลบสินค้า</button>
            </div>
        </form>
    </div>
</div>
@endsection

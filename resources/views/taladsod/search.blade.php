{{--
 | ค้นหาสินค้าตลาดสด (taladsod.search) + สินค้าตามหมวด (taladsod.category) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@search / @category
 | ตัวแปร: $listings (Collection หรือ Paginator), $categories, $settings, $currentCategory (FreshMarketCategory|null)
 | ตัวกรอง (GET): q, category (slug|id), lat, lng, radius, sort (distance|newest|price_asc|price_desc|popular), organic
 --}}
@extends('layouts.frontend-v4')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $q = is_scalar(request('q')) ? trim((string) request('q')) : '';
    $catParam = is_scalar(request('category')) ? (string) request('category') : ($currentCategory->slug ?? '');
    $hasLocation = is_numeric(request('lat')) && is_numeric(request('lng'));
    $radius = is_numeric(request('radius')) ? (float) request('radius') : (float) ($settings->default_search_radius_km ?? 10);
    $maxRadius = (float) ($settings->max_search_radius_km ?? 50);
    $sort = is_scalar(request('sort')) ? (string) request('sort') : 'distance';
    $isPaginator = $listings instanceof \Illuminate\Contracts\Pagination\Paginator;
    $resultCount = $isPaginator && method_exists($listings, 'total') ? $listings->total() : $listings->count();
    $searched = $q !== '' || $currentCategory || $hasLocation;
    $onCategoryRoute = request()->routeIs('taladsod.category');
    $pageTitle = $currentCategory ? $currentCategory->name : ($q !== '' ? 'ผลการค้นหา "'.$q.'"' : 'ค้นหาของสดและอาหาร');
    $radiusOptions = array_values(array_filter([2, 5, 10, 20, 50], fn ($r) => $r <= max(2, $maxRadius)));
@endphp

@section('title', $pageTitle.' · ตลาดสดไทยพร้อม')
@section('meta_description', $currentCategory ? ('ซื้อ'.$currentCategory->name.'จากร้านและรถเข็นใกล้บ้าน ตลาดสดไทยพร้อม') : 'ค้นหาของสด อาหาร จากร้านและรถเข็นใกล้บ้าน ตลาดสดไทยพร้อม')

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="taladsod" :search="false" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => 'search'])

        {{-- ════════ ฟอร์มค้นหา ════════ --}}
        <section class="tp-card" style="padding:18px; margin-top:4px;"
                 x-data="{ locating: false, error: '', lat: @js($hasLocation ? (float) request('lat') : null), lng: @js($hasLocation ? (float) request('lng') : null),
                           nearMe() {
                               this.locating = true; this.error = '';
                               window.ts.geo().then((p) => { this.lat = p.lat.toFixed(6); this.lng = p.lng.toFixed(6); this.$nextTick(() => this.$refs.form.submit()); })
                                   .catch((e) => { this.error = e.message; this.locating = false; });
                           },
                           clearNear() { this.lat = null; this.lng = null; this.$nextTick(() => this.$refs.form.submit()); } }">
            <form method="GET" action="{{ route('taladsod.search') }}" x-ref="form" role="search" class="ts-stack" style="gap:12px;">
                <div class="ts-row" style="gap:10px; flex-wrap:nowrap;">
                    <div style="position:relative; flex:1; min-width:0;">
                        <i class="fas fa-magnifying-glass" aria-hidden="true" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--ink2);"></i>
                        <label for="ts-q" style="position:absolute; left:-9999px;">คำค้นหา</label>
                        <input id="ts-q" type="search" name="q" value="{{ $q }}" class="tp-input" maxlength="100"
                               placeholder="เช่น กะเพรา ผักบุ้ง ปลาทู ขนมครก" style="padding-left:42px; height:50px;">
                    </div>
                    <button type="submit" class="ts-btn3d ts-tone-gold" style="min-height:50px;"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><span class="sf-hide-sm"> ค้นหา</span></button>
                </div>

                <div class="ts-grid" style="--ts-min:180px; gap:10px;">
                    <div>
                        <label class="ts-label" for="ts-cat">หมวดหมู่</label>
                        <select id="ts-cat" name="category" class="tp-input" x-on:change="$refs.form.submit()">
                            <option value="">ทุกหมวด</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->slug }}" @selected($catParam === $cat->slug || $catParam === (string) $cat->id)>{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <template x-if="lat !== null">
                        <div>
                            <label class="ts-label" for="ts-radius">รัศมีจากตำแหน่งฉัน</label>
                            <select id="ts-radius" name="radius" class="tp-input" x-on:change="$refs.form.submit()">
                                @foreach($radiusOptions as $r)
                                    <option value="{{ $r }}" @selected((float) $r === $radius)>{{ $r }} กม.</option>
                                @endforeach
                            </select>
                        </div>
                    </template>
                    <template x-if="lat !== null">
                        <div>
                            <label class="ts-label" for="ts-sort">เรียงตาม</label>
                            <select id="ts-sort" name="sort" class="tp-input" x-on:change="$refs.form.submit()">
                                <option value="distance" @selected($sort === 'distance')>ใกล้ที่สุด</option>
                                <option value="newest" @selected($sort === 'newest')>มาใหม่</option>
                                <option value="popular" @selected($sort === 'popular')>ขายดี</option>
                                <option value="price_asc" @selected($sort === 'price_asc')>ราคาต่ำ → สูง</option>
                                <option value="price_desc" @selected($sort === 'price_desc')>ราคาสูง → ต่ำ</option>
                            </select>
                        </div>
                    </template>
                </div>

                <input type="hidden" name="lat" :value="lat" :disabled="lat === null">
                <input type="hidden" name="lng" :value="lng" :disabled="lng === null">

                <div class="ts-row" style="gap:8px;">
                    <button type="button" class="sf-chip" :class="lat !== null ? 'is-on' : ''" x-on:click="lat !== null ? clearNear() : nearMe()" :disabled="locating">
                        <i class="fas" :class="locating ? 'fa-circle-notch ts-spin' : 'fa-location-crosshairs'" aria-hidden="true"></i>
                        <span x-text="lat !== null ? 'ใกล้ฉัน (แตะเพื่อยกเลิก)' : 'ค้นหาใกล้ฉัน'">{{ $hasLocation ? 'ใกล้ฉัน (แตะเพื่อยกเลิก)' : 'ค้นหาใกล้ฉัน' }}</span>
                    </button>
                    <label class="sf-chip" x-show="lat !== null" style="cursor:pointer;">
                        <input type="checkbox" name="organic" value="1" @checked(request()->boolean('organic')) x-on:change="$refs.form.submit()" style="width:18px; height:18px; accent-color:var(--accent1);">
                        <i class="fas fa-leaf" aria-hidden="true"></i> อินทรีย์เท่านั้น
                    </label>
                    <span class="ts-err" x-show="error" x-text="error" x-cloak></span>
                </div>
            </form>
        </section>

        {{-- ════════ หมวดหมู่ ════════ --}}
        @if($categories->count() > 0)
            <div class="sf-scroll" style="margin-top:16px;">
                <a href="{{ route('taladsod.search', array_filter(['q' => $q ?: null])) }}" class="ts-cat {{ ! $currentCategory ? 'is-on' : '' }}">
                    <span class="ts-cat-ic">🧺</span><span>ทั้งหมด</span>
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('taladsod.category', $cat->slug) }}" class="ts-cat {{ $currentCategory && (int) $currentCategory->id === (int) $cat->id ? 'is-on' : '' }}">
                        <span class="ts-cat-ic">
                            @if($cat->image_url)
                                <img src="{{ $cat->image_url }}" alt="" loading="lazy">
                            @else
                                {{ $cat->icon ?: '🛒' }}
                            @endif
                        </span>
                        <span>{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ════════ ผลลัพธ์ ════════ --}}
        <section class="sf-section" aria-labelledby="ts-result-h">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker">{{ $hasLocation ? 'ใกล้ตำแหน่งของคุณ · รัศมี '.$radius.' กม.' : ($currentCategory ? 'หมวดหมู่' : 'ตลาดสดไทยพร้อม') }}</div>
                    <h1 id="ts-result-h" class="sf-title">{{ $pageTitle }}</h1>
                    @if($currentCategory && $currentCategory->description)
                        <p class="ts-muted" style="margin:6px 0 0; font-size:13.5px;">{{ $currentCategory->description }}</p>
                    @endif
                </div>
                @if($searched)
                    <span class="ts-pill ts-tone-gold">{{ number_format($resultCount) }} รายการ</span>
                @endif
            </div>

            @if(! $searched)
                <div class="tp-card ts-empty">
                    <span class="em" aria-hidden="true">🔎</span>
                    <b style="font-size:16px;">อยากกินอะไร หาอะไรดี?</b>
                    <span class="ts-muted">พิมพ์ชื่อเมนูหรือของสด เลือกหมวด หรือกด "ค้นหาใกล้ฉัน" เพื่อดูร้านรอบตัว</span>
                    <a href="{{ route('taladsod.home') }}#near-me" class="ts-btn3d ts-tone-gold sm"><i class="fas fa-map-location-dot" aria-hidden="true"></i> ดูร้านที่เปิดอยู่ใกล้ฉัน</a>
                </div>
            @elseif($listings->count() === 0)
                <div class="tp-card ts-empty">
                    <span class="em" aria-hidden="true">🥲</span>
                    <b style="font-size:16px;">ยังไม่พบสินค้าที่ตรงกับการค้นหา</b>
                    <span class="ts-muted">ลองคำอื่น เลือกหมวดอื่น{{ $hasLocation ? ' หรือขยายรัศมีค้นหา' : '' }} — ร้านรถเข็นจะโผล่เมื่อเปิดร้านอยู่เท่านั้น</span>
                    <a href="{{ route('taladsod.search') }}" class="tp-btn"><i class="fas fa-rotate-left" aria-hidden="true"></i> ล้างการค้นหา</a>
                </div>
            @else
                <div class="sf-grid">
                    @foreach($listings as $listing)
                        @include('taladsod.partials.listing-card', ['listing' => $listing])
                    @endforeach
                </div>
                @if($isPaginator && $listings->hasPages())
                    <div style="margin-top:20px;">{{ $listings->links('vendor.pagination.tp-v4') }}</div>
                @endif
            @endif
        </section>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

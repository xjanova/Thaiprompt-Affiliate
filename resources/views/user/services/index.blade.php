{{--
    ค้นหาบริการ (ธีมโนวาเต็ม) — ServiceBookingController::index
    ตัวกรองทั้งหมดเป็นฟอร์ม GET ชื่อพารามิเตอร์เดิม: q · category · min_price · max_price · min_rating · sort
    - เลิกช่องแนะนำคำค้นอัตโนมัติ: เดิมยิงไป /api/v1/services/search-suggestions ที่ไม่มี route อยู่จริง (404 ทุกครั้ง)
    - เลิกปุ่ม "หาตำแหน่ง/ใกล้ฉัน": เดิมขอตำแหน่งแล้วไม่ได้ใช้กรองอะไรเลย
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'ค้นหาบริการ')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $f = $filters ?? [];
    $hasFilter = collect($f)->except('sort')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty() || (($f['sort'] ?? 'featured') !== 'featured');
    $sorts = [
        'featured' => 'แนะนำ',
        'popular' => 'ยอดนิยม',
        'rating' => 'คะแนนสูง',
        'price_low' => 'ราคาต่ำ → สูง',
        'price_high' => 'ราคาสูง → ต่ำ',
        'newest' => 'ใหม่ล่าสุด',
    ];
    $hex = fn ($c) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $c) ? $c : null;
@endphp

@section('content')
<div class="nvm-stack" x-data="{ view: 'grid' }">

<section class="nvm-ph" aria-labelledby="nvm-sn-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Services</div>
            <h1 id="nvm-sn-h">บริการถึงที่ ง่าย ครบ จบในแอปเดียว</h1>
            <p class="nvm-ph__lead">นวด ตัดผม ซ่อมแอร์ ทำความสะอาด สอนพิเศษ และอีกมากมาย จากผู้ให้บริการมืออาชีพ</p>
            <form class="nvm-sn-search" action="{{ route('user.services.index') }}" method="GET" role="search">
                @foreach(['category', 'min_price', 'max_price', 'min_rating', 'sort'] as $keep)
                    @if(($f[$keep] ?? '') !== '' && ($f[$keep] ?? null) !== null)
                        <input type="hidden" name="{{ $keep }}" value="{{ $f[$keep] }}">
                    @endif
                @endforeach
                <label class="nvm-sr" for="nvm-sn-q">ค้นหาบริการ</label>
                <input id="nvm-sn-q" class="nvm-input" type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="ค้นหาบริการ เช่น นวด ตัดผม ล้างแอร์" maxlength="100" autocomplete="off">
                <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ค้นหา</button>
            </form>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg><span class="nvm-num">{{ number_format($stats['total_services'] ?? 0) }}</span>&nbsp;บริการ</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg><span class="nvm-num">{{ number_format($stats['total_providers'] ?? 0) }}</span>&nbsp;ผู้ให้บริการ</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span class="nvm-num">{{ number_format($stats['total_bookings'] ?? 0) }}</span>&nbsp;การจอง</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/svc/shop.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($categories->isNotEmpty())
    <section aria-labelledby="nvm-sn-cat-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Categories</small><h2 id="nvm-sn-cat-h">หมวดหมู่บริการ</h2></div>
        </div>
        <nav class="nvm-sn-cats" aria-label="หมวดหมู่บริการ">
            <a class="nvm-sn-cat {{ empty($f['category']) ? 'is-on' : '' }}" href="{{ route('user.services.index', array_filter(['q' => $f['q'] ?? null])) }}">
                <span class="nvm-sn-cat__ic"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg></span>
                <b>ทั้งหมด</b>
                <small class="nvm-num">{{ number_format($stats['total_services'] ?? 0) }}</small>
            </a>
            @foreach($categories as $category)
                <a class="nvm-sn-cat {{ (string) ($f['category'] ?? '') === (string) $category->id ? 'is-on' : '' }}"
                   href="{{ route('user.services.index', array_filter(['q' => $f['q'] ?? null, 'category' => $category->id])) }}"
                   @if($hex($category->color)) style="--c: {{ $hex($category->color) }}" @endif>
                    <span class="nvm-sn-cat__ic">
                        @if($category->icon && str_ends_with($category->icon, '.svg'))
                            <img src="{{ asset($category->icon) }}" alt="" loading="lazy">
                        @else
                            <svg class="nvm-ico"><use href="#nvm-i-store"/></svg>
                        @endif
                    </span>
                    <b>{{ $category->name }}</b>
                    <small class="nvm-num">{{ number_format($category->services_count ?? 0) }}</small>
                </a>
            @endforeach
        </nav>
    </section>
@endif

@if($featuredServices->isNotEmpty() && ! $hasFilter)
    <section aria-labelledby="nvm-sn-ft-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Featured</small><h2 id="nvm-sn-ft-h">บริการแนะนำ</h2></div>
        </div>
        <div class="nvm-svc">
            @foreach($featuredServices as $service)
                @php
                    $ftImg = $service->main_image;
                    $ftUrl = $ftImg ? (preg_match('#^(https?:)?//#', $ftImg) ? $ftImg : asset(str_starts_with($ftImg, '/') ? ltrim($ftImg, '/') : 'storage/'.$ftImg)) : asset('images/nova/svc/store.webp');
                @endphp
                <a href="{{ route('user.services.show', $service) }}">
                    <span class="nvm-svc__tag">แนะนำ</span>
                    <div class="nvm-svc__img"><img src="{{ $ftUrl }}" alt="" loading="lazy"></div>
                    <div class="nvm-svc__body">
                        <span class="nvm-svc__ic"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg></span>
                        <b>{{ $service->name }}</b>
                        <small class="nvm-latin">{{ $service->category->name ?? 'Service' }}</small>
                        <span class="nvm-svc__go nvm-num">เริ่ม ฿{{ number_format((float) $service->base_price, 0) }}<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-sn-f-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Filter</small><h2 id="nvm-sn-f-h">กรองและเรียงลำดับ</h2></div>
        @if($hasFilter)
            <a href="{{ route('user.services.index') }}"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ล้างตัวกรอง</a>
        @endif
    </div>
    <form class="nvm-filter" action="{{ route('user.services.index') }}" method="GET">
        @if(($f['q'] ?? '') !== '')<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-sn-fc">หมวดหมู่</label>
            <select id="nvm-sn-fc" class="nvm-input" name="category" @change="$el.form.requestSubmit()">
                <option value="">ทุกหมวดหมู่</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($f['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-sn-fmin">ราคาต่ำสุด (บาท)</label>
            <input id="nvm-sn-fmin" class="nvm-input nvm-num" type="number" name="min_price" min="0" step="1" inputmode="numeric" value="{{ $f['min_price'] ?? '' }}" placeholder="0">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-sn-fmax">ราคาสูงสุด (บาท)</label>
            <input id="nvm-sn-fmax" class="nvm-input nvm-num" type="number" name="max_price" min="0" step="1" inputmode="numeric" value="{{ $f['max_price'] ?? '' }}" placeholder="ไม่จำกัด">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-sn-fr">คะแนนขั้นต่ำ</label>
            <select id="nvm-sn-fr" class="nvm-input" name="min_rating" @change="$el.form.requestSubmit()">
                <option value="">ทุกคะแนน</option>
                @foreach(['4.5', '4', '3.5'] as $r)
                    <option value="{{ $r }}" @selected((string) ($f['min_rating'] ?? '') === $r)>★ {{ $r }} ขึ้นไป</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-sn-fs">เรียงตาม</label>
            <select id="nvm-sn-fs" class="nvm-input" name="sort" @change="$el.form.requestSubmit()">
                @foreach($sorts as $value => $label)
                    <option value="{{ $value }}" @selected(($f['sort'] ?? 'featured') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ใช้ตัวกรอง</button>
        </div>
    </form>
</section>

<section aria-labelledby="nvm-sn-r-h">
    <div class="nvm-sec-h">
        <div>
            <small class="nvm-latin">Results</small>
            <h2 id="nvm-sn-r-h">
                @if($services->total() > 0)
                    พบ <span class="nvm-num">{{ number_format($services->total()) }}</span> บริการ@if(($f['q'] ?? '') !== '') สำหรับ “{{ $f['q'] }}”@endif
                @else
                    ไม่พบบริการ
                @endif
            </h2>
        </div>
        @if($services->count() > 0)
            <div class="nvm-seg" role="group" aria-label="รูปแบบการแสดงผล">
                <button type="button" :class="{ 'is-on': view === 'grid' }" :aria-pressed="(view === 'grid').toString()" @click="view = 'grid'">ตาราง</button>
                <button type="button" :class="{ 'is-on': view === 'list' }" :aria-pressed="(view === 'list').toString()" @click="view = 'list'">รายการ</button>
            </div>
        @endif
    </div>

    @if($services->count() > 0)
        <div class="nvm-sn-grid" :class="{ 'is-list': view === 'list' }">
            @foreach($services as $service)
                @include('user.services.partials.nova-card', ['service' => $service])
            @endforeach
        </div>
        @if($services->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $services->links() }}</div>
        @endif
    @else
        <div class="nvm-card nvm-box">
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ไม่พบบริการที่ค้นหา</b>
                ลองเปลี่ยนคำค้นหา หรือล้างตัวกรองเพื่อดูบริการทั้งหมด
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.services.index') }}">ดูบริการทั้งหมด</a></div>
            </div>
        </div>
    @endif
</section>

<section class="nvm-row3" aria-label="ทำไมต้องจองบริการกับเรา">
    <div class="nvm-card nvm-box">
        <div class="nvm-status"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg><span>ปลอดภัย มั่นใจ<small>ผู้ให้บริการผ่านการตรวจสอบก่อนรับงาน</small></span></div>
    </div>
    <div class="nvm-card nvm-box">
        <div class="nvm-status"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span>ติดตามสถานะได้<small>ดูความคืบหน้าการจองได้จากหน้าการจองของฉัน</small></span></div>
    </div>
    <div class="nvm-card nvm-box">
        <div class="nvm-status"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg><span>รับ PV และเงินคืน<small>บริการที่ร่วมรายการได้ PV และ Cashback เข้ากระเป๋า</small></span></div>
    </div>
</section>

</div>
@endsection

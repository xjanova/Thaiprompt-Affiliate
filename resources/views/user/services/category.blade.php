{{--
    บริการในหมวดหมู่ (ธีมโนวาเต็ม) — ServiceBookingController::category
    - เรียงลำดับส่ง ?sort= ไปให้เซิร์ฟเวอร์ (เดิม dropdown ไม่มีผลอะไร)
    - ช่องค้นหากรองการ์ดในหน้านี้ทันทีฝั่งเบราว์เซอร์ (เฉพาะบริการที่โหลดอยู่ในหน้าปัจจุบัน)
--}}
@extends('layouts.user-nova')

@section('title', $category->name.' · บริการ')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $sort = $sort ?? 'popular';
    $sorts = [
        'popular' => 'ยอดนิยม',
        'name' => 'ชื่อ ก-ฮ / A-Z',
        'price_asc' => 'ราคาต่ำ → สูง',
        'price_desc' => 'ราคาสูง → ต่ำ',
        'rating' => 'คะแนนสูงสุด',
    ];
    $hex = fn ($c) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $c) ? $c : null;
@endphp

@section('content')
<div class="nvm-stack" x-data="{ q: '', match(name) { return ! this.q.trim() || name.includes(this.q.trim().toLowerCase()); } }">

<section class="nvm-ph" aria-labelledby="nvm-snc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.services.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>บริการทั้งหมด</a>
            <div class="nvm-eyebrow nvm-latin">Category</div>
            <h1 id="nvm-snc-h">{{ $category->name }}</h1>
            @if($category->description)
                <p class="nvm-ph__lead">{{ $category->description }}</p>
            @endif
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg><span class="nvm-num">{{ number_format($services->total()) }}</span>&nbsp;บริการ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/svc/store.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-label="ค้นหาและเรียงลำดับ">
    <form class="nvm-filter" action="{{ route('user.services.category', $category) }}" method="GET" @submit.prevent>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-snc-q">ค้นหาในหมวดนี้</label>
            <input id="nvm-snc-q" class="nvm-input" type="search" x-model="q" placeholder="พิมพ์ชื่อบริการ" autocomplete="off">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-snc-s">เรียงตาม</label>
            <select id="nvm-snc-s" class="nvm-input" name="sort" @change="$el.form.submit()">
                @foreach($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>
</section>

@if($services->count() > 0)
    <div class="nvm-sn-grid">
        @foreach($services as $service)
            <div class="nvm-sn-cell" x-show="match(@js(mb_strtolower($service->name)))">
                @include('user.services.partials.nova-card', ['service' => $service])
            </div>
        @endforeach
    </div>
    @if($services->hasPages())
        <div class="nvm-pager">{{ $services->links() }}</div>
    @endif
@else
    <div class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีบริการในหมวดนี้</b>
            ยังไม่มีบริการในหมวด “{{ $category->name }}” ในขณะนี้ ลองดูหมวดอื่นก่อนได้
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.services.index') }}">ดูบริการทั้งหมด</a></div>
        </div>
    </div>
@endif

@if(isset($relatedCategories) && $relatedCategories->isNotEmpty())
    <section aria-labelledby="nvm-snc-rel-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">More categories</small><h2 id="nvm-snc-rel-h">หมวดหมู่อื่น</h2></div></div>
        <nav class="nvm-sn-cats" aria-label="หมวดหมู่อื่น">
            @foreach($relatedCategories as $related)
                <a class="nvm-sn-cat" href="{{ route('user.services.category', $related) }}" @if($hex($related->color)) style="--c: {{ $hex($related->color) }}" @endif>
                    <span class="nvm-sn-cat__ic">
                        @if($related->icon && str_ends_with($related->icon, '.svg'))
                            <img src="{{ asset($related->icon) }}" alt="" loading="lazy">
                        @else
                            <svg class="nvm-ico"><use href="#nvm-i-store"/></svg>
                        @endif
                    </span>
                    <b>{{ $related->name }}</b>
                    <small class="nvm-num">{{ number_format($related->services_count ?? 0) }}</small>
                </a>
            @endforeach
        </nav>
    </section>
@endif

</div>
@endsection

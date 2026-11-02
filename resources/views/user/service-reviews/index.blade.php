{{--
    รีวิวบริการของฉัน (ธีมโนวาเต็ม) — ServiceBookingController::myReviews
    $reviews = การจองของสมาชิกเองที่ให้คะแนนแล้ว (service_bookings.rating ไม่ว่าง) แบ่งหน้า 10
    - ลบรีวิวต้องยืนยันก่อน (กล่องยืนยันในการ์ด แทน confirm() ของเบราว์เซอร์)
    - ชื่อผู้ให้บริการใช้ชื่อที่ตั้งไว้แสดงต่อลูกค้า (display_name) ก่อนชื่อจริง
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'รีวิวบริการของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-snr-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.bookings.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>การจองของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">My reviews</div>
            <h1 id="nvm-snr-h">{{ $pageTitle ?? 'รีวิวบริการของฉัน' }}</h1>
            <p class="nvm-ph__lead">คะแนนและความคิดเห็นที่คุณให้ไว้กับบริการที่ใช้ แก้ไขหรือลบได้ทุกเมื่อ</p>
            @if($reviews->total() > 0)
                <div class="nvm-pills"><span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg><span class="nvm-num">{{ number_format($reviews->total()) }}</span>&nbsp;รีวิว</span></div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/account/chat.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($reviews->count() > 0)
    @foreach($reviews as $review)
        @php
            $providerName = $review->provider?->display_name ?: ($review->provider?->name ?? null);
            $stars = (int) ($review->rating ?? 0);
        @endphp
        <article class="nvm-card nvm-box nvm-sn-review" x-data="{ ask: false }" aria-label="รีวิว {{ $review->service->name ?? 'บริการ' }}">
            <div class="nvm-sn-review__head">
                <div class="nvm-sn-review__who">
                    <span class="nvm-sn-review__av" aria-hidden="true">{{ mb_substr($review->service->name ?? 'บ', 0, 1) }}</span>
                    <div>
                        <b>{{ $review->service->name ?? 'บริการที่ถูกลบแล้ว' }}</b>
                        <small>{{ $providerName ? 'ผู้ให้บริการ: '.$providerName : 'ไม่ระบุผู้ให้บริการ' }}</small>
                        <small class="nvm-num">เลขที่จอง {{ $review->booking_number }}</small>
                    </div>
                </div>
                <div class="nvm-sn-review__rate">
                    <span class="nvm-sn-stars nvm-sn-stars--lg" aria-label="ให้ {{ $stars }} จาก 5 ดาว">
                        @for($i = 1; $i <= 5; $i++)<span class="{{ $i <= $stars ? 'is-on' : '' }}" aria-hidden="true">★</span>@endfor
                        <b class="nvm-num">{{ $stars }}/5</b>
                    </span>
                    <time class="nvm-num" datetime="{{ $review->rated_at?->toIso8601String() }}">{{ $thDate($review->rated_at) }}</time>
                </div>
            </div>

            @if($review->review)
                <blockquote>{{ $review->review }}</blockquote>
            @endif

            <div class="nvm-sn-review__acts" x-show="! ask">
                <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.service-reviews.edit', $review) }}"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>แก้ไขรีวิว</a>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบ</button>
            </div>
            <form class="nvm-confirm nvm-confirm--row" x-show="ask" x-cloak action="{{ route('user.service-reviews.destroy', $review) }}" method="POST">
                @csrf
                @method('DELETE')
                <p>ลบรีวิวนี้? คะแนนจะถูกนำออกจากคะแนนเฉลี่ยของผู้ให้บริการ</p>
                <div class="nvm-filter__btns">
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false">ยกเลิก</button>
                    <button type="submit" class="nvm-btn nvm-btn--bad nvm-btn--sm">ยืนยันลบ</button>
                </div>
            </form>
        </article>
    @endforeach

    @if($reviews->hasPages())
        <div class="nvm-pager">{{ $reviews->links() }}</div>
    @endif
@else
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/account/chat.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีรีวิว</b>
            คะแนนและรีวิวที่คุณให้กับบริการที่ใช้เสร็จแล้วจะแสดงที่นี่
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.services.index') }}"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>ดูบริการ</a></div>
        </div>
    </section>
@endif

</div>
@endsection

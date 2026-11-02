{{--
    แก้ไขรีวิวบริการ (ธีมโนวาเต็ม) — ServiceBookingController::editReview
    $booking = การจองของสมาชิกเองที่รีวิวไว้แล้ว (controller ตอบ 403/404 ให้ก่อนถึงหน้านี้)
    ส่งไป PUT user.service-reviews.update: rating (1-5) · review (ไม่เกิน 1000 ตัวอักษร)
    - ปุ่มดาวเป็นตัวช่วยเลือก ค่าจริงอยู่ใน <input type="hidden" name="rating"> ที่มีค่าเริ่มต้นจากเซิร์ฟเวอร์
      (ไม่มี JavaScript ก็ยังส่งคะแนนเดิมได้)
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'แก้ไขรีวิว')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $initial = (int) old('rating', $booking->rating ?? 5);
    $initial = max(1, min(5, $initial));
    $providerName = $booking->provider?->display_name ?: ($booking->provider?->name ?? null);
    $labels = [1 => 'ต้องปรับปรุง', 2 => 'พอใช้', 3 => 'ดี', 4 => 'ดีมาก', 5 => 'ยอดเยี่ยม'];
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-sne-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.service-reviews.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>รีวิวของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Edit review</div>
            <h1 id="nvm-sne-h">{{ $pageTitle ?? 'แก้ไขรีวิว' }}</h1>
            <p class="nvm-ph__lead">ปรับคะแนนและความคิดเห็นสำหรับ {{ $booking->service->name ?? 'บริการนี้' }}</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/account/chat.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-sne-f-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Your review</small><h2 id="nvm-sne-f-h">คะแนนและความคิดเห็น</h2></div></div>

        <form class="nvm-form" action="{{ route('user.service-reviews.update', $booking) }}" method="POST"
              x-data="{ rating: {{ $initial }}, labels: @js($labels), busy: false }" @submit="if (busy) { $event.preventDefault() } else { busy = true }" @pageshow.window="busy = false">
            @csrf
            @method('PUT')

            <div class="nvm-field">
                <span class="nvm-label" id="nvm-sne-r">ให้คะแนน <em>*</em></span>
                <div class="nvm-sn-rate" role="radiogroup" aria-labelledby="nvm-sne-r">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" role="radio" :aria-checked="(rating === {{ $i }}).toString()" aria-label="{{ $i }} ดาว · {{ $labels[$i] }}"
                                class="{{ $i <= $initial ? 'is-on' : '' }}" :class="{ 'is-on': rating >= {{ $i }} }" @click="rating = {{ $i }}">★</button>
                    @endfor
                    <output class="nvm-num" x-text="rating + '/5 · ' + labels[rating]">{{ $initial }}/5 · {{ $labels[$initial] }}</output>
                </div>
                <input type="hidden" name="rating" value="{{ $initial }}" :value="rating">
                @error('rating')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="nvm-sne-t">ความคิดเห็น</label>
                <textarea id="nvm-sne-t" class="nvm-input nvm-textarea @error('review') is-bad @enderror" name="review" rows="5" maxlength="1000" placeholder="เล่าประสบการณ์การใช้บริการของคุณ">{{ old('review', $booking->review) }}</textarea>
                @error('review')<span class="nvm-err">{{ $message }}</span>@else<small class="nvm-note">ไม่เกิน 1,000 ตัวอักษร</small>@enderror
            </div>

            <div class="nvm-filter__btns">
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.service-reviews.index') }}">ยกเลิก</a>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : 'บันทึกการแก้ไข'">บันทึกการแก้ไข</span></button>
            </div>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-sne-b-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Booking</small><h2 id="nvm-sne-b-h">ข้อมูลการจอง</h2></div></div>
        <dl class="nvm-kv">
            <dt>บริการ</dt><dd>{{ $booking->service->name ?? '-' }}</dd>
            <dt>ผู้ให้บริการ</dt><dd>{{ $providerName ?? '-' }}</dd>
            <dt>เลขที่จอง</dt><dd class="nvm-num">{{ $booking->booking_number }}</dd>
            <dt>ใช้บริการเสร็จ</dt><dd class="nvm-num">{{ $booking->completed_at ? $booking->completed_at->copy()->locale('th')->translatedFormat('j M Y') : '-' }}</dd>
            <dt>รีวิวเมื่อ</dt><dd class="nvm-num">{{ $booking->rated_at ? $booking->rated_at->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-' }}</dd>
        </dl>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>คะแนนของคุณนำไปคิดคะแนนเฉลี่ยของผู้ให้บริการทันทีที่บันทึก</p>
    </aside>
</div>

</div>
@endsection

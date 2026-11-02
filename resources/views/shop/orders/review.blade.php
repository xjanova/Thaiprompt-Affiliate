{{--
    เขียนรีวิวสินค้าในคำสั่งซื้อ — ธีมโนวาเต็ม
    ข้อมูลจาก OrderController::showReviewForm: $order (ของผู้ซื้อคนนี้ สถานะ delivered/completed เท่านั้น), $item
    ฟอร์ม POST orders.review.submit (multipart): rating (1-5)*, title, comment*, images[] (≤5 รูป, รูปละ ≤2MB) + turnstile ถ้าเปิด
--}}
@extends('layouts.user-nova')

@section('title', 'รีวิวสินค้า — '.$item->product_name)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@php
    $rvImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image);
@endphp

@section('content')

<div class="nvm-stack nvm-os-narrow">

<section class="nvm-ph" aria-labelledby="nvm-os-rv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('orders.show', $order->id) }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>คำสั่งซื้อ #{{ $order->order_number }}</a>
            <div class="nvm-eyebrow nvm-latin">Write a review</div>
            <h1 id="nvm-os-rv-h">แบ่งปันประสบการณ์ของคุณ</h1>
            <p class="nvm-ph__lead">รีวิวของคุณช่วยให้ผู้ซื้อรายอื่นตัดสินใจได้ดีขึ้น และช่วยให้ร้านพัฒนาสินค้า</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box">
    <div class="nvm-os-who">
        <span class="nvm-os-thumb nvm-os-thumb--xl">@if($rvImg)<img src="{{ $rvImg }}" alt="{{ $item->product_name }}">@else<svg class="nvm-ico"><use href="#nvm-i-bag"/></svg>@endif</span>
        <div class="nvm-os-who__t">
            <b>{{ $item->product_name }}</b>
            @if($item->product_sku)<small class="nvm-num">SKU: {{ $item->product_sku }}</small>@endif
            <small class="nvm-num">คำสั่งซื้อ #{{ $order->order_number }}</small>
        </div>
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ส่งรีวิวไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-os-rv-f">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Your review</small><h2 id="nvm-os-rv-f">รีวิวสินค้า</h2></div></div>

    <form method="POST" action="{{ route('orders.review.submit', [$order->id, $item->id]) }}" enctype="multipart/form-data" class="nvm-form" novalidate
          x-data="nvmOsReviewForm({{ (int) old('rating', 0) }}, @js((string) old('comment', '')))" @submit="submit($event)" @pageshow.window="busy = false">
        @csrf

        <div class="nvm-field">
            <span class="nvm-label" id="rv-rating-lbl">ให้คะแนนสินค้านี้ <em>*</em></span>
            <div class="nvm-os-stars" role="radiogroup" aria-labelledby="rv-rating-lbl">
                <template x-for="star in 5" :key="star">
                    <button type="button" role="radio" @click="rating = star" :aria-label="star + ' ดาว'" :aria-checked="(rating === star).toString()" :class="star <= rating && 'is-on'">★</button>
                </template>
                <span x-show="rating > 0" x-cloak class="nvm-num" x-text="rating + ' ดาว — ' + ['', 'แย่', 'พอใช้', 'ดี', 'ดีมาก', 'ยอดเยี่ยม'][rating]"></span>
            </div>
            <input type="hidden" name="rating" :value="rating">
            <span class="nvm-err" x-show="errors.rating" x-text="errors.rating" x-cloak></span>
            @error('rating')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>

        <div class="nvm-field">
            <label class="nvm-label" for="rv-title">หัวข้อ (ไม่บังคับ)</label>
            <input id="rv-title" type="text" name="title" maxlength="200" value="{{ old('title') }}" class="nvm-input @error('title') is-bad @enderror" placeholder="เช่น สินค้าดีมาก คุ้มราคา">
            @error('title')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>

        <div class="nvm-field">
            <label class="nvm-label" for="rv-comment">รีวิว <em>*</em></label>
            <textarea id="rv-comment" name="comment" rows="6" maxlength="1000" required class="nvm-input nvm-textarea @error('comment') is-bad @enderror" x-model="comment"
                      placeholder="เล่าประสบการณ์การใช้งาน คุณภาพ การจัดส่ง หรือสิ่งที่ประทับใจ">{{ old('comment') }}</textarea>
            <div class="nvm-os-count nvm-num"><span x-text="comment.length"></span>/1000</div>
            <span class="nvm-err" x-show="errors.comment" x-text="errors.comment" x-cloak></span>
            @error('comment')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>

        <div class="nvm-field">
            <span class="nvm-label">รูปภาพ (ไม่บังคับ)</span>
            <span class="nvm-help">สูงสุด 5 รูป รูปละไม่เกิน 2MB (PNG / JPG)</span>
            {{-- ช่องไฟล์จริงที่ส่งไปกับฟอร์ม (เติมไฟล์ผ่าน DataTransfer) --}}
            <input type="file" name="images[]" multiple x-ref="files" accept="image/png,image/jpeg" class="nvm-sr" tabindex="-1" aria-hidden="true">
            <label class="nvm-drop-zone nvm-os-drop" :class="images.length >= 5 && 'is-full'">
                <img src="{{ asset('images/nova/account/profile.webp') }}" alt="" width="72" height="72" loading="lazy">
                <b>แตะเพื่อเลือกรูปภาพ</b>
                <small class="nvm-num" x-text="images.length + '/5 รูป'">0/5 รูป</small>
                <input type="file" accept="image/png,image/jpeg" multiple class="nvm-sr" @change="pick($event)" :disabled="images.length >= 5">
            </label>
            <div class="nvm-os-shots" x-show="images.length > 0" x-cloak>
                <template x-for="(img, k) in images" :key="img.key">
                    <div class="nvm-os-shot">
                        <img :src="img.url" :alt="img.name">
                        <button type="button" @click="remove(k)" aria-label="ลบรูป"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></button>
                    </div>
                </template>
            </div>
            @error('images')<span class="nvm-err">{{ $message }}</span>@enderror
            @error('images.*')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>

        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span><b>แนวทางการเขียนรีวิว</b> — เขียนตามจริงจากประสบการณ์ใช้งาน สุภาพ และไม่ใส่ข้อมูลส่วนตัว (เบอร์โทร ที่อยู่)</span></p>

        <x-turnstile point="review" class="nvm-turnstile" />

        <div class="nvm-os-form-foot">
            <a class="nvm-btn nvm-btn--line" href="{{ route('orders.show', $order->id) }}">ยกเลิก</a>
            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่ง…' : 'ส่งรีวิว'">ส่งรีวิว</span></button>
        </div>
    </form>
</section>

</div>
@endsection

@push('scripts')
<script>
    /**
     * ฟอร์มรีวิว: ดาว + ข้อความ + รูป (เก็บไฟล์ไว้ใน input จริงผ่าน DataTransfer เพื่อให้ส่งไปกับฟอร์มได้)
     */
    function nvmOsReviewForm(initialRating, initialComment) {
        const notify = (message, type) => window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));

        return {
            rating: initialRating || 0,
            comment: initialComment || '',
            images: [],
            errors: { rating: '', comment: '' },
            busy: false,
            init() {
                // ออกจากหน้า → คืนหน่วยความจำรูปตัวอย่าง
                window.addEventListener('pagehide', () => this.images.forEach((i) => URL.revokeObjectURL(i.url)));
            },
            sync() {
                try {
                    const dt = new DataTransfer();
                    this.images.forEach((i) => dt.items.add(i.file));
                    this.$refs.files.files = dt.files;
                } catch (e) {
                    notify('เบราว์เซอร์นี้แนบรูปไม่ได้ ส่งรีวิวแบบไม่มีรูปได้ตามปกติ', 'info');
                }
            },
            pick(ev) {
                const files = Array.from(ev.target.files || []);
                ev.target.value = '';
                for (const file of files) {
                    if (this.images.length >= 5) { notify('อัปโหลดได้สูงสุด 5 รูป', 'info'); break; }
                    if (! /^image\/(png|jpe?g)$/i.test(file.type)) { notify('รองรับเฉพาะไฟล์ PNG หรือ JPG', 'error'); continue; }
                    if (file.size > 2048 * 1024) { notify('ไฟล์ ' + file.name + ' ใหญ่เกิน 2MB', 'error'); continue; }
                    this.images.push({ key: Date.now() + '-' + Math.random(), name: file.name, file: file, url: URL.createObjectURL(file) });
                }
                this.sync();
            },
            remove(k) {
                const img = this.images[k];
                if (img) { URL.revokeObjectURL(img.url); }
                this.images.splice(k, 1);
                this.sync();
            },
            submit(e) {
                if (this.busy) { e.preventDefault(); return; }
                this.errors.rating = this.rating < 1 ? 'กรุณาให้คะแนนดาวก่อนส่งรีวิว' : '';
                this.errors.comment = this.comment.trim() === '' ? 'กรุณาเขียนรีวิว' : '';
                if (this.errors.rating || this.errors.comment) { e.preventDefault(); return; }
                this.busy = true;
            }
        };
    }
</script>
@endpush

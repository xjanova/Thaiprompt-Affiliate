{{--
 | เขียนรีวิวสินค้าในคำสั่งซื้อ — ธีม V4 (user-v4)
 | ข้อมูลจาก OrderController@showReviewForm: $order, $item
 | ฟอร์ม POST orders.review.submit (multipart): rating (1-5)*, title, comment*, images[] (≤5 รูป, รูปละ ≤2MB) + turnstile ถ้าเปิด
 --}}
@extends('layouts.user-v4')

@section('title', 'รีวิวสินค้า — '.$item->product_name)

@php
    $rvImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image);
@endphp

@section('content')
<x-theme-v4.shop-kit />

<div style="max-width:760px; margin:0 auto; display:flex; flex-direction:column; gap:16px;">
    <nav class="sf-breadcrumb" aria-label="เส้นทาง">
        <a href="{{ route('orders.index') }}">คำสั่งซื้อของฉัน</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('orders.show', $order->id) }}">#{{ $order->order_number }}</a>
        <span aria-hidden="true">/</span>
        <span style="color:var(--ink); font-weight:600;">รีวิวสินค้า</span>
    </nav>

    <div>
        <h1 style="margin:0; font-size:clamp(22px, 3.6vw, 28px); font-weight:800; color:var(--ink);"><i class="fas fa-star" style="color:var(--deep2);"></i> แบ่งปันประสบการณ์ของคุณ</h1>
        <p class="tp-muted" style="margin:4px 0 0; font-size:13.5px;">รีวิวของคุณช่วยให้ผู้ซื้อรายอื่นตัดสินใจได้ดีขึ้น</p>
    </div>

    <div class="tp-card" style="display:flex; gap:14px; align-items:center;">
        <span class="sf-thumb" style="width:84px; height:84px; font-size:26px;">@if($rvImg)<img src="{{ $rvImg }}" alt="{{ $item->product_name }}">@else 📦 @endif</span>
        <div style="min-width:0;">
            <div style="font-weight:800; color:var(--ink); overflow-wrap:anywhere;">{{ $item->product_name }}</div>
            @if($item->product_sku)<div class="tp-muted" style="font-size:12.5px;">SKU: {{ $item->product_sku }}</div>@endif
            <div class="tp-muted" style="font-size:12.5px;">คำสั่งซื้อ #{{ $order->order_number }}</div>
        </div>
    </div>

    @if(session('error'))
        <div class="sf-note sf-note-err" role="alert">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('orders.review.submit', [$order->id, $item->id]) }}" enctype="multipart/form-data"
          class="tp-card sf-stack" style="gap:18px;"
          x-data="tpReviewForm({{ (int) old('rating', 0) }}, @js((string) old('comment', '')))" @submit="submit($event)">
        @csrf

        <div>
            <div style="font-weight:800; color:var(--ink); margin-bottom:8px;">ให้คะแนนสินค้านี้ <span style="color:var(--sf-sale, #e0564f);">*</span></div>
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;" role="radiogroup" aria-label="คะแนน">
                <template x-for="star in 5" :key="star">
                    <button type="button" @click="rating = star" :aria-label="star + ' ดาว'" :aria-checked="(rating === star).toString()" role="radio"
                            style="border:0; background:transparent; cursor:pointer; padding:4px; font-size:36px; line-height:1; transition:transform .12s ease;"
                            :style="star <= rating ? 'color:var(--sf-star, #e6b347); transform:scale(1.08);' : 'color:color-mix(in srgb, var(--ink2) 45%, transparent);'">★</button>
                </template>
                <span x-show="rating > 0" x-cloak class="tp-num" style="font-weight:800; font-size:18px; color:var(--deep2); margin-left:6px;" x-text="rating + ' ดาว — ' + ['', 'แย่', 'พอใช้', 'ดี', 'ดีมาก', 'ยอดเยี่ยม'][rating]"></span>
            </div>
            <input type="hidden" name="rating" :value="rating">
            @error('rating')
                <div class="sf-note sf-note-err" style="padding:8px 12px; margin-top:8px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="rv-title" style="display:block; font-weight:700; color:var(--ink); margin-bottom:6px;">หัวข้อ (ไม่บังคับ)</label>
            <input id="rv-title" type="text" name="title" maxlength="200" value="{{ old('title') }}" class="tp-input" placeholder="เช่น สินค้าดีมาก คุ้มราคา">
            @error('title')
                <div class="sf-note sf-note-err" style="padding:8px 12px; margin-top:8px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="rv-comment" style="display:block; font-weight:700; color:var(--ink); margin-bottom:6px;">รีวิว <span style="color:var(--sf-sale, #e0564f);">*</span></label>
            <textarea id="rv-comment" name="comment" rows="6" maxlength="1000" required class="tp-input" x-model="comment"
                      placeholder="เล่าประสบการณ์การใช้งาน คุณภาพ การจัดส่ง หรือสิ่งที่ประทับใจ">{{ old('comment') }}</textarea>
            <div class="tp-muted tp-num" style="text-align:right; font-size:12px; margin-top:4px;"><span x-text="comment.length"></span>/1000</div>
            @error('comment')
                <div class="sf-note sf-note-err" style="padding:8px 12px;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <div style="font-weight:700; color:var(--ink); margin-bottom:4px;">รูปภาพ (ไม่บังคับ)</div>
            <p class="tp-muted" style="margin:0 0 10px; font-size:12.5px;">สูงสุด 5 รูป รูปละไม่เกิน 2MB (PNG / JPG)</p>
            {{-- ช่องไฟล์จริงที่ส่งไปกับฟอร์ม (เติมไฟล์ผ่าน DataTransfer) --}}
            <input type="file" name="images[]" multiple x-ref="files" accept="image/png,image/jpeg" style="display:none;">
            <label style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; min-height:110px; padding:18px; border-radius:18px; cursor:pointer; text-align:center; background:var(--surf); box-shadow:var(--inset-sm);"
                   :style="images.length >= 5 ? 'opacity:.5; cursor:not-allowed;' : ''">
                <i class="fas fa-camera" style="font-size:24px; color:var(--deep1);"></i>
                <span style="font-weight:700; color:var(--ink); font-size:13.5px;">แตะเพื่อเลือกรูปภาพ</span>
                <span class="tp-muted tp-num" style="font-size:12px;" x-text="images.length + '/5 รูป'"></span>
                <input type="file" accept="image/png,image/jpeg" multiple style="display:none;" @change="pick($event)" :disabled="images.length >= 5">
            </label>
            <div x-show="images.length > 0" x-cloak class="grid" style="grid-template-columns:repeat(auto-fill, minmax(96px, 1fr)); gap:10px; margin-top:10px;">
                <template x-for="(img, k) in images" :key="img.key">
                    <div style="position:relative; aspect-ratio:1/1; border-radius:14px; overflow:hidden; box-shadow:var(--raise);">
                        <img :src="img.url" :alt="img.name" style="width:100%; height:100%; object-fit:cover;">
                        <button type="button" @click="remove(k)" aria-label="ลบรูป" class="tp-icon-btn" style="position:absolute; top:6px; right:6px; width:32px; height:32px; color:var(--sf-sale, #e0564f);"><i class="fas fa-xmark"></i></button>
                    </div>
                </template>
            </div>
            @error('images')
                <div class="sf-note sf-note-err" style="padding:8px 12px; margin-top:8px;">{{ $message }}</div>
            @enderror
            @error('images.*')
                <div class="sf-note sf-note-err" style="padding:8px 12px; margin-top:8px;">{{ $message }}</div>
            @enderror
        </div>

        <div class="sf-note sf-note-info" style="font-size:12.5px;">
            <strong>แนวทางการเขียนรีวิว</strong> — เขียนตามจริงจากประสบการณ์ใช้งาน สุภาพ และไม่ใส่ข้อมูลส่วนตัว (เบอร์โทร ที่อยู่)
        </div>

        <x-turnstile point="review" />

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
            <button type="submit" class="sf-btn3d" :disabled="busy" :class="(rating === 0 || comment.trim() === '') && 'is-disabled'">
                <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> ส่งรีวิว
            </button>
            <a href="{{ route('orders.show', $order->id) }}" class="sf-btn3d is-soft">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    /**
     * ฟอร์มรีวิว: ดาว + ข้อความ + รูป (เก็บไฟล์ไว้ใน input จริงผ่าน DataTransfer เพื่อให้ส่งไปกับฟอร์มได้)
     */
    function tpReviewForm(initialRating, initialComment) {
        return {
            rating: initialRating || 0,
            comment: initialComment || '',
            images: [],
            busy: false,
            sync() {
                try {
                    const dt = new DataTransfer();
                    this.images.forEach(i => dt.items.add(i.file));
                    this.$refs.files.files = dt.files;
                } catch (e) {
                    window.tpShop.notify('เบราว์เซอร์นี้แนบรูปไม่ได้ ส่งรีวิวแบบไม่มีรูปได้ตามปกติ', 'info');
                }
            },
            pick(ev) {
                const files = Array.from(ev.target.files || []);
                ev.target.value = '';
                for (const file of files) {
                    if (this.images.length >= 5) { window.tpShop.notify('อัปโหลดได้สูงสุด 5 รูป', 'info'); break; }
                    if (!/^image\/(png|jpe?g)$/i.test(file.type)) { window.tpShop.notify('รองรับเฉพาะไฟล์ PNG หรือ JPG', 'error'); continue; }
                    if (file.size > 2048 * 1024) { window.tpShop.notify('ไฟล์ ' + file.name + ' ใหญ่เกิน 2MB', 'error'); continue; }
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
                if (this.rating < 1) { e.preventDefault(); window.tpShop.notify('กรุณาให้คะแนนดาวก่อนส่งรีวิว', 'error'); return; }
                if (this.comment.trim() === '') { e.preventDefault(); window.tpShop.notify('กรุณาเขียนรีวิว', 'error'); return; }
                this.busy = true;
            }
        };
    }
</script>
@endpush

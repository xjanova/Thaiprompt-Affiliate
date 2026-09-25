{{--
 | แบนเนอร์โปรโมชั่นของร้าน — ธีม V4
 | ตัวแปร: $layoutSettings (show_promotion_banner, promotion_image, promotion_link, promotion_text), $isPreview
 --}}
@php
    $vpImage = \App\Support\Shop\StoreTheme::image($layoutSettings->promotion_image);
    $vpLink = ($isPreview ?? false) ? null : \App\Support\Shop\StoreTheme::url($layoutSettings->promotion_link);
    $vpText = trim((string) ($layoutSettings->promotion_text ?? ''));
@endphp

@if($layoutSettings->show_promotion_banner && ($vpImage || $vpText !== ''))
    <section class="sf-wrap sf-section">
        @if($vpImage)
            <a @if($vpLink) href="{{ $vpLink }}" @endif style="position:relative; display:block; overflow:hidden; border-radius:24px; box-shadow:var(--card-shadow); text-decoration:none;">
                <img src="{{ $vpImage }}" alt="{{ $vpText ?: 'โปรโมชั่น' }}" loading="lazy" style="width:100%; height:clamp(160px, 26vw, 300px); object-fit:cover; display:block;">
                @if($vpText !== '')
                    <span style="position:absolute; inset:0; display:grid; place-items:center; padding:16px; text-align:center; font-size:clamp(20px, 4vw, 34px); font-weight:800; color:var(--on-accent, #fff); background:rgba(0,0,0,.3); text-shadow:0 2px 10px rgba(0,0,0,.3);">{{ $vpText }}</span>
                @endif
            </a>
        @else
            <div style="position:relative; overflow:hidden; border-radius:24px; padding:clamp(22px, 4vw, 36px); text-align:center; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--store-a), var(--store-c)); box-shadow:var(--card-shadow);">
                <p style="margin:0; font-size:clamp(18px, 3vw, 26px); font-weight:800; text-shadow:0 1px 4px rgba(0,0,0,.2);">{{ $vpText }}</p>
                @if($vpLink)
                    <a href="{{ $vpLink }}" class="sf-btn3d is-soft" style="margin-top:14px; color:var(--deep1);">ดูรายละเอียด <i class="fas fa-arrow-right"></i></a>
                @endif
            </div>
        @endif
    </section>
@endif

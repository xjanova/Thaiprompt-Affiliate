{{--
 | สไลด์แบนเนอร์ของร้าน — ธีม V4 (Alpine ล้วน ไม่ต้องโหลด Swiper)
 | ตัวแปร: $layoutSettings (slider_enabled, slider_images[{image, link, title, subtitle}], slider_autoplay_speed, slider_show_arrows, slider_show_dots), $isPreview
 --}}
@php
    $vsSlides = collect(is_array($layoutSettings->slider_images) ? $layoutSettings->slider_images : [])
        ->filter(fn ($s) => is_array($s) && ! empty($s['image']))
        ->map(fn ($s) => [
            'image' => \App\Support\Shop\StoreTheme::image($s['image']),
            'link' => ($isPreview ?? false) ? null : \App\Support\Shop\StoreTheme::url($s['link'] ?? null),
            'title' => (string) ($s['title'] ?? ''),
            'subtitle' => (string) ($s['subtitle'] ?? ''),
        ])
        ->filter(fn ($s) => $s['image'])
        ->values();
    $vsSpeed = max(2000, (int) ($layoutSettings->slider_autoplay_speed ?? 5000));
@endphp

@if($layoutSettings->slider_enabled && $vsSlides->isNotEmpty())
    <section class="sf-wrap" style="padding-top:16px;"
             x-data="{ i: 0, n: {{ $vsSlides->count() }}, t: null,
                       go(k) { this.i = (k + this.n) % this.n; },
                       start() { if (this.n > 1) { this.t = setInterval(() => this.go(this.i + 1), {{ $vsSpeed }}); } },
                       stop() { clearInterval(this.t); } }"
             x-init="start()" @mouseenter="stop()" @mouseleave="start()">
        <div style="position:relative; overflow:hidden; border-radius:24px; aspect-ratio:21/8; min-height:180px; box-shadow:var(--card-shadow); background:var(--surf);">
            @foreach($vsSlides as $k => $slide)
                <a @if($slide['link']) href="{{ $slide['link'] }}" @endif x-show="i === {{ $k }}" x-transition.opacity.duration.500ms @if($k > 0) x-cloak @endif
                   style="position:absolute; inset:0; display:block; text-decoration:none;">
                    <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?: 'แบนเนอร์ร้าน' }}" @if($k > 0) loading="lazy" @endif style="width:100%; height:100%; object-fit:cover;">
                    @if($slide['title'] !== '')
                        <span style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:16px; color:var(--on-accent, #fff); background:rgba(0,0,0,.3);">
                            <span style="font-size:clamp(20px, 4vw, 36px); font-weight:800; text-shadow:0 2px 10px rgba(0,0,0,.35);">{{ $slide['title'] }}</span>
                            @if($slide['subtitle'] !== '')
                                <span style="margin-top:6px; font-size:clamp(13px, 2vw, 17px); opacity:.95;">{{ $slide['subtitle'] }}</span>
                            @endif
                        </span>
                    @endif
                </a>
            @endforeach
            @if($vsSlides->count() > 1 && $layoutSettings->slider_show_arrows)
                <button type="button" @click="go(i - 1)" class="tp-icon-btn" aria-label="สไลด์ก่อนหน้า" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); z-index:2;"><i class="fas fa-chevron-left"></i></button>
                <button type="button" @click="go(i + 1)" class="tp-icon-btn" aria-label="สไลด์ถัดไป" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); z-index:2;"><i class="fas fa-chevron-right"></i></button>
            @endif
            @if($vsSlides->count() > 1 && $layoutSettings->slider_show_dots)
                <div style="position:absolute; left:0; right:0; bottom:12px; z-index:2; display:flex; justify-content:center; gap:6px;">
                    @foreach($vsSlides as $k => $slide)
                        <button type="button" @click="go({{ $k }})" aria-label="สไลด์ที่ {{ $k + 1 }}"
                                :style="i === {{ $k }} ? 'width:24px; background:var(--on-accent, #fff);' : 'width:10px; background:rgba(255,255,255,.55);'"
                                style="height:10px; border:0; border-radius:10px; cursor:pointer; transition:width .25s ease;"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

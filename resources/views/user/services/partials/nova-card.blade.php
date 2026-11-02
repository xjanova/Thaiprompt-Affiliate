{{--
    การ์ดบริการ 1 ใบ (ธีมโนวาเต็ม) — ใช้ร่วมกันระหว่างหน้าค้นหาบริการและหน้าหมวดหมู่
    รับ: $service (โหลด category มาแล้ว)
    - รูปบริการมาจากคอลัมน์ images (JSON) ตัวแรก — คอลัมน์ image_path ที่หน้าเดิมอ่านไม่มีอยู่จริง รูปจึงไม่เคยขึ้น
    - สีหมวดส่งเข้า CSS ผ่านตัวแปร --c เฉพาะค่าสี hex เท่านั้น (กันค่าแปลกปลอมหลุดเข้า style)
--}}
@php
    $snImg = $service->main_image;
    $snImgUrl = $snImg ? (preg_match('#^(https?:)?//#', $snImg) ? $snImg : asset(str_starts_with($snImg, '/') ? ltrim($snImg, '/') : 'storage/'.$snImg)) : null;
    $snColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $service->category?->color) ? $service->category->color : null;
    $snRating = (float) ($service->average_rating ?? 0);
@endphp
<a class="nvm-card nvm-sn-svc" href="{{ route('user.services.show', $service) }}" @if($snColor) style="--c: {{ $snColor }}" @endif>
    <div class="nvm-sn-svc__img">
        @if($snImgUrl)
            <img src="{{ $snImgUrl }}" alt="{{ $service->name }}" loading="lazy">
        @else
            <img class="is-art" src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" loading="lazy">
        @endif
        @if($service->category)
            <span class="nvm-sn-svc__tag">{{ $service->category->name }}</span>
        @endif
        @if($service->is_featured)
            <span class="nvm-sn-svc__hot">แนะนำ</span>
        @endif
    </div>
    <div class="nvm-sn-svc__body">
        <b>{{ $service->name }}</b>
        @if($service->short_description || $service->description)
            <p>{{ $service->short_description ?: $service->description }}</p>
        @endif
        <div class="nvm-sn-meta">
            <span><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>{{ $service->duration_minutes ? $service->duration_text : '60 นาที' }}</span>
            @if($service->requires_location)
                <span><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>บริการถึงที่</span>
            @endif
        </div>
        <div class="nvm-sn-svc__foot">
            <div class="nvm-sn-price nvm-num"><small>เริ่มต้น</small>฿{{ number_format((float) $service->base_price, 0) }}<x-tpix-price :thb="$service->base_price" /></div>
            <span class="nvm-sn-stars" aria-label="คะแนน {{ number_format($snRating, 1) }} จาก 5">
                <span class="is-on" aria-hidden="true">★</span><b class="nvm-num">{{ number_format($snRating, 1) }}</b>
            </span>
        </div>
        @if(($service->pv_value ?? 0) > 0 || ($service->cashback_percentage ?? 0) > 0)
            <div class="nvm-sn-chips">
                @if(($service->pv_value ?? 0) > 0)<span class="nvm-chip nvm-chip--warn">{{ number_format((float) $service->pv_value) }} PV</span>@endif
                @if(($service->cashback_percentage ?? 0) > 0)<span class="nvm-chip nvm-chip--ok">เงินคืน {{ rtrim(rtrim(number_format((float) $service->cashback_percentage, 2), '0'), '.') }}%</span>@endif
            </div>
        @endif
    </div>
</a>

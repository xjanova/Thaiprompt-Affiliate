{{--
 | ส่วนท้ายของร้าน (ผู้ขายปรับแต่งได้) — ธีม V4
 | ตัวแปร: $store, $layoutSettings (show_footer, show_contact_info, show_social_links, social_links, footer_content), $isPreview
 | footer_content เป็น HTML ที่ผู้ขายพิมพ์เอง → ผ่าน SafeHtml::clean ก่อนแสดงเสมอ (กัน stored XSS)
 --}}
@php
    $vftPreview = (bool) ($isPreview ?? false);
    $vftSocialMap = ['facebook' => 'fa-facebook', 'line' => 'fa-line', 'instagram' => 'fa-instagram', 'tiktok' => 'fa-tiktok', 'youtube' => 'fa-youtube'];
    $vftSocial = [];
    if ($layoutSettings->show_social_links && is_array($layoutSettings->social_links)) {
        foreach ($vftSocialMap as $net => $icon) {
            $raw = trim((string) ($layoutSettings->social_links[$net] ?? ''));
            if ($raw === '') {
                continue;
            }
            $link = $net === 'line' && ! preg_match('#^https?://#i', $raw)
                ? 'https://line.me/R/ti/p/'.rawurlencode(ltrim($raw, '@'))
                : \App\Support\Shop\StoreTheme::url($raw);
            if ($link) {
                $vftSocial[$net] = ['href' => $link, 'icon' => $icon];
            }
        }
    }
    $vftContent = \App\Support\Shop\SafeHtml::clean($layoutSettings->footer_content ?? '');
@endphp

@if($layoutSettings->show_footer)
    <section class="sf-wrap sf-section">
        <div class="tp-card" style="padding:clamp(18px, 3vw, 28px);">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px;">
                <div>
                    <div style="font-weight:800; font-size:17px; color:var(--store-a);">{{ $store->store_name }}</div>
                    @if($store->store_description ?? null)
                        <p class="tp-muted" style="margin:8px 0 0; font-size:13.5px; line-height:1.7;">{{ \Illuminate\Support\Str::limit($store->store_description, 220) }}</p>
                    @endif
                </div>
                @if($layoutSettings->show_contact_info)
                    <div>
                        <div class="tp-section-h" style="margin-bottom:8px;">ติดต่อร้าน</div>
                        <div style="display:flex; flex-direction:column; gap:6px; font-size:13.5px; color:var(--ink2);">
                            @if($store->store_email ?? null)<span><i class="fas fa-envelope" style="width:18px;"></i> {{ $store->store_email }}</span>@endif
                            @if($store->store_phone ?? null)<span><i class="fas fa-phone" style="width:18px;"></i> {{ $store->store_phone }}</span>@endif
                            @if($store->store_address ?? null)<span><i class="fas fa-location-dot" style="width:18px;"></i> {{ $store->store_address }}</span>@endif
                        </div>
                    </div>
                @endif
                @if($vftSocial !== [])
                    <div>
                        <div class="tp-section-h" style="margin-bottom:8px;">ติดตามร้าน</div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            @foreach($vftSocial as $net => $s)
                                <a href="{{ $vftPreview ? '#' : $s['href'] }}" @if(! $vftPreview) target="_blank" rel="noopener nofollow" @endif class="tp-icon-btn" style="width:44px; height:44px; text-decoration:none; color:var(--store-a);" aria-label="{{ $net }}"><i class="fab {{ $s['icon'] }}"></i></a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            @if($vftContent !== '')
                <div class="sf-prose" style="margin-top:18px; padding-top:16px; border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent); font-size:13.5px;">{!! $vftContent !!}</div>
            @endif
            <div class="tp-muted" style="margin-top:16px; padding-top:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent); text-align:center; font-size:12px;">
                © {{ date('Y') + 543 }} {{ $store->store_name }} · ขายบนไทยพร๊อมท์
            </div>
        </div>
    </section>
@endif

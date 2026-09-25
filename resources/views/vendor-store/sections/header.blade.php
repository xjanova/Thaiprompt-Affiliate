{{--
 | ส่วนหัวร้าน (ผู้ขายปรับแต่งได้) — ธีม V4 · ใช้ร่วม: หน้าร้านจริง (vendor-store.show-custom) + หน้าตัวอย่างของผู้ขาย
 | ตัวแปร: $store, $layoutSettings, $stats, $isPreview · สีร้านมาจาก CSS var --store-a / --store-b ที่หน้าแม่ตั้งไว้
 --}}
@php
    $vhStyle = $layoutSettings->header_style ?? 'gradient';
    $vhHeight = max(160, min(420, (int) ($layoutSettings->header_height ?? 200)));
    $vhImage = $vhStyle === 'image' ? \App\Support\Shop\StoreTheme::image($layoutSettings->header_image) : null;
    $vhTransparent = $vhStyle === 'transparent';
    $vhBackground = match (true) {
        $vhImage !== null => 'var(--ink)',
        $vhStyle === 'solid' => 'var(--store-a)',
        $vhTransparent => 'var(--card-bg)',
        default => 'linear-gradient(135deg, var(--store-a), var(--store-b))',
    };
    $vhLogo = \App\Services\Shop\ShopPresenter::imageUrl($store->store_logo ?? null);
    $vhSocialMap = ['facebook' => 'fa-facebook', 'line' => 'fa-line', 'instagram' => 'fa-instagram', 'tiktok' => 'fa-tiktok', 'youtube' => 'fa-youtube'];
    $vhSocial = [];
    if ($layoutSettings->show_social_links && is_array($layoutSettings->social_links)) {
        foreach ($vhSocialMap as $net => $icon) {
            $raw = trim((string) ($layoutSettings->social_links[$net] ?? ''));
            if ($raw === '') {
                continue;
            }
            $link = $net === 'line' && ! preg_match('#^https?://#i', $raw)
                ? 'https://line.me/R/ti/p/'.rawurlencode(ltrim($raw, '@'))
                : \App\Support\Shop\StoreTheme::url($raw);
            if ($link) {
                $vhSocial[$net] = ['href' => $link, 'icon' => $icon];
            }
        }
    }
    $vhInk = $vhTransparent ? 'var(--ink)' : 'var(--on-accent, #fff)';
    $vhRider = $store instanceof \App\Models\VendorStore && $store->canUseRiderDelivery();
@endphp

<section class="sf-wrap" style="padding-top:20px;">
    <div style="position:relative; overflow:hidden; border-radius:28px; min-height:{{ $vhHeight }}px; background:{{ $vhBackground }}; box-shadow:var(--card-shadow); display:flex; align-items:flex-end;">
        @if($vhImage)
            <img src="{{ $vhImage }}" alt="" aria-hidden="true" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
            <div aria-hidden="true" style="position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.15) 0%, rgba(0,0,0,.6) 100%);"></div>
        @elseif(! $vhTransparent)
            <div aria-hidden="true" style="position:absolute; inset:0; background:radial-gradient(520px 260px at 90% 0%, rgba(255,255,255,.24), transparent 60%);"></div>
        @endif

        <div style="position:relative; width:100%; padding:clamp(20px, 4vw, 36px); color:{{ $vhInk }}; display:flex; flex-wrap:wrap; align-items:center; gap:18px;">
            @if($layoutSettings->show_store_logo)
                <span style="width:clamp(76px, 12vw, 104px); height:clamp(76px, 12vw, 104px); flex:none; border-radius:26px; overflow:hidden; display:grid; place-items:center; font-size:42px; background:rgba(255,255,255,.2); box-shadow:0 10px 26px rgba(0,0,0,.2); border:3px solid rgba(255,255,255,.45);">
                    @if($vhLogo)
                        <img src="{{ $vhLogo }}" alt="{{ $store->store_name }}" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                    @else
                        🏪
                    @endif
                </span>
            @endif
            <div style="flex:1; min-width:220px;">
                <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px;">
                    @if($store->is_verified ?? false)
                        <span class="tp-pill" style="padding:6px 12px; color:{{ $vhInk }}; background:rgba(255,255,255,.2);"><i class="fas fa-circle-check"></i> ร้านค้ายืนยันตัวตน</span>
                    @endif
                    @if($vhRider)
                        <span class="tp-pill" style="padding:6px 12px; color:{{ $vhInk }}; background:rgba(255,255,255,.2);"><i class="fas fa-motorcycle"></i> ส่งด่วนด้วยไรเดอร์</span>
                    @endif
                </div>
                @if($layoutSettings->show_store_name)
                    <h1 style="margin:0; font-size:clamp(24px, 4.6vw, 40px); font-weight:800; line-height:1.15; letter-spacing:-.4px; text-shadow:{{ $vhTransparent ? 'none' : '0 2px 10px rgba(0,0,0,.25)' }}; overflow-wrap:anywhere;">{{ $store->store_name }}</h1>
                @endif
                @if($layoutSettings->show_store_description && ($store->store_description ?? null))
                    <p style="margin:8px 0 0; font-size:clamp(13.5px, 1.8vw, 16px); line-height:1.6; opacity:.94; max-width:720px;">{{ \Illuminate\Support\Str::limit($store->store_description, 180) }}</p>
                @endif
                @if($layoutSettings->show_store_stats)
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
                        <span class="tp-pill" style="padding:7px 12px; color:{{ $vhInk }}; background:rgba(255,255,255,.18);"><i class="fas fa-box"></i> {{ number_format((int) ($stats['total_products'] ?? 0)) }} สินค้า</span>
                        <span class="tp-pill" style="padding:7px 12px; color:{{ $vhInk }}; background:rgba(255,255,255,.18);"><i class="fas fa-cart-shopping"></i> {{ number_format((int) ($stats['total_sales'] ?? 0)) }} ยอดขาย</span>
                        @if(($stats['rating_count'] ?? 0) > 0)
                            <span class="tp-pill" style="padding:7px 12px; color:{{ $vhInk }}; background:rgba(255,255,255,.18);">★ {{ number_format((float) ($stats['rating'] ?? 0), 1) }} ({{ number_format((int) $stats['rating_count']) }})</span>
                        @endif
                    </div>
                @endif
            </div>
            @if($vhSocial !== [])
                <div style="display:flex; gap:8px;">
                    @foreach($vhSocial as $net => $s)
                        <a href="{{ ($isPreview ?? false) ? '#' : $s['href'] }}" @if(! ($isPreview ?? false)) target="_blank" rel="noopener nofollow" @endif aria-label="{{ $net }}"
                           style="width:44px; height:44px; border-radius:14px; display:grid; place-items:center; text-decoration:none; color:{{ $vhInk }}; background:rgba(255,255,255,.2);">
                            <i class="fab {{ $s['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>

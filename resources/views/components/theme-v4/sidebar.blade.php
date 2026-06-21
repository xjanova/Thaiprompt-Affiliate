{{--
 | Sidebar — ธีมนวลทองคำ V4
 | เมนูมาจาก config/menus.php → MenuService::getMenuForRole('admin') (single source of truth)
 | อยู่ใน x-data="tpShell" จึงใช้ isMobile / drawer / sidebarHidden / closeDrawer ได้
 --}}
@php
    $menuService = app(\App\Services\MenuService::class);
    $menus = $menuService->getMenuForRole('admin', auth()->user());

    $curRoute = request()->route() ? request()->route()->getName() : null;
    $curUrl   = rtrim(url()->current(), '/');

    $itemActive = function (array $it) use ($curRoute, $curUrl) {
        $r = $it['route'] ?? null;
        $u = $it['url'] ?? null;
        if ($r && $curRoute === $r) return true;
        if ($u && $u !== '#' && rtrim($u, '/') === $curUrl) return true;
        return false;
    };
    $groupActive = function (array $g) use ($itemActive) {
        if ($itemActive($g)) return true;
        foreach (($g['submenu'] ?? []) as $s) {
            if (is_array($s) && $itemActive($s)) return true;
        }
        return false;
    };

    $activeGroupId = null;
    foreach ($menus as $g) {
        if ($groupActive($g)) { $activeGroupId = $g['id'] ?? null; break; }
    }
@endphp

<aside
    :style="isMobile
        ? `position:fixed; top:0; bottom:0; left:${drawer ? '0' : '-320px'}; z-index:60; width:288px; background:var(--bg); box-shadow:8px 0 30px rgba(0,0,0,.22); padding:14px 12px; transition:left .26s ease; display:flex; flex-direction:column; gap:8px;`
        : `position:sticky; top:84px; align-self:flex-start; width:${sidebarHidden ? '0px' : 'var(--tp-side-w)'}; ${sidebarHidden ? 'opacity:0; pointer-events:none; overflow:hidden;' : ''} max-height:calc(100vh - 100px); display:flex; flex-direction:column; gap:8px; transition:width .22s ease, opacity .22s ease;`">

    {{-- หัว drawer (มือถือ) --}}
    <div x-show="isMobile" x-cloak style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2px;">
        <span style="font-weight:700; font-size:15px;">เมนูหลังบ้าน</span>
        <button @click="closeDrawer()" type="button" class="tp-icon-btn" style="width:34px; height:34px; border-radius:11px; color:var(--ink2);"><i class="fas fa-xmark"></i></button>
    </div>

    {{-- รายการเมนู (เลื่อนได้) --}}
    <div x-data="{ openId: @js($activeGroupId) }"
         style="position:relative; flex:1; min-height:0; overflow-y:auto; overflow-x:visible; display:flex; flex-direction:column; gap:7px; padding:2px;">

        @foreach($menus as $group)
            @php
                $gid     = $group['id'] ?? \Illuminate\Support\Str::slug($group['label'] ?? 'g');
                $gicon   = $group['icon'] ?? '•';
                $glabel  = $group['label'] ?? '';
                $gen     = \Illuminate\Support\Str::headline($gid);
                $gbadge  = $group['badge'] ?? null;
                $subs    = $group['submenu'] ?? [];
                $hasSubs = is_array($subs) && count($subs) > 0;
                $gurl    = $group['url'] ?? null;
                $gActive = $groupActive($group);
            @endphp

            <div>
                @if($hasSubs)
                    {{-- กลุ่มที่มีเมนูย่อย → accordion --}}
                    <button type="button"
                            @click="openId = openId === '{{ $gid }}' ? '' : '{{ $gid }}'"
                            class="tp-card"
                            style="cursor:pointer; width:100%; text-align:left; display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:15px; {{ $gActive ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);' }}">
                        <span class="tp-tile" style="width:33px; height:33px; border-radius:11px; font-size:15px;">
                            @if(str_contains($gicon,'fa-'))<i class="{{ $gicon }}"></i>@else{!! $gicon !!}@endif
                        </span>
                        <span style="line-height:1.15; flex:1; min-width:0;">
                            <span style="display:block; font-weight:600; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $glabel }}</span>
                            <span style="display:block; font-size:10px; color:var(--ink2); font-weight:500; letter-spacing:.2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $gen }}</span>
                        </span>
                        @if($gbadge)<span class="tp-pill tp-pill-gold" style="font-size:9px; padding:2px 7px;">{{ $gbadge }}</span>@endif
                        <span style="font-size:10px; color:var(--ink2); width:12px; text-align:center; flex:none; transition:transform .2s ease;" :style="openId === '{{ $gid }}' && 'transform:rotate(90deg)'">▸</span>
                    </button>

                    <div x-show="openId === '{{ $gid }}'" x-cloak
                         style="position:relative; margin:4px 0 6px 22px; padding-left:16px; display:flex; flex-direction:column; gap:3px; overflow:hidden; animation:tpSub .26s cubic-bezier(.2,.8,.3,1) both;">
                        {{-- เส้น RGB วิ่ง --}}
                        <span style="position:absolute; left:0; top:5px; bottom:5px; width:3px; border-radius:3px; background:linear-gradient(180deg,#ff5a5a,#ffb24d,#ffe24d,#5ad65a,#4dd2ff,#7a8cff,#c44dff,#ff5a5a); background-size:100% 220%; box-shadow:0 0 7px rgba(120,140,255,.4); animation:tpHue 5s linear infinite, tpFlow 2.6s linear infinite;"></span>

                        @foreach($subs as $sub)
                            @php
                                if (!is_array($sub)) continue;
                                $slabel = $sub['label'] ?? '';
                                $surl   = $sub['url'] ?? null;
                                $sActive = $itemActive($sub);
                                $isDivider = ($slabel === '---' || $slabel === '');
                                $isHeader  = !$isDivider && empty($surl);
                            @endphp

                            @if($isDivider)
                                <hr class="tp-divider" style="margin:5px 6px;">
                            @elseif($isHeader)
                                <div style="font-size:10.5px; font-weight:700; color:var(--ink2); padding:7px 11px 3px; letter-spacing:.2px;">{{ $slabel }}</div>
                            @else
                                <a href="{{ $surl }}"
                                   @click="if (isMobile) closeDrawer()"
                                   class="tp-sub-link"
                                   style="{{ $sActive ? 'box-shadow:var(--inset-sm); color:var(--ink);' : 'color:var(--ink2);' }}">
                                    <span style="width:6px; height:6px; flex:none; border-radius:50%; background:{{ $sActive ? 'linear-gradient(135deg,var(--accent1),var(--accent2))' : 'var(--ink2)' }};"></span>
                                    <span style="font-weight:{{ $sActive ? 700 : 500 }}; font-size:12.5px; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $slabel }}</span>
                                    @if(!empty($sub['badge']))<span class="tp-pill tp-pill-soft" style="font-size:8.5px; padding:2px 6px;">{{ $sub['badge'] }}</span>@endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    {{-- เมนูเดี่ยว (ลิงก์ตรง) --}}
                    <a href="{{ $gurl ?? '#' }}"
                       @click="if (isMobile) closeDrawer()"
                       class="tp-card"
                       style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:15px; text-decoration:none; color:var(--ink); {{ $gActive ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);' }}">
                        <span class="tp-tile" style="width:33px; height:33px; border-radius:11px; font-size:15px;">
                            @if(str_contains($gicon,'fa-'))<i class="{{ $gicon }}"></i>@else{!! $gicon !!}@endif
                        </span>
                        <span style="line-height:1.15; flex:1; min-width:0;">
                            <span style="display:block; font-weight:600; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $glabel }}</span>
                            <span style="display:block; font-size:10px; color:var(--ink2); font-weight:500; letter-spacing:.2px;">{{ $gen }}</span>
                        </span>
                        @if($gbadge)<span class="tp-pill tp-pill-gold" style="font-size:9px; padding:2px 7px;">{{ $gbadge }}</span>@endif
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    {{-- สถานะระบบ — ปักอยู่ล่าง sidebar เสมอ (อยู่นอก scroll container จึงไม่เลื่อนหาย) --}}
    <div class="tp-card" style="flex:none; padding:13px 16px; border-radius:18px; margin-top:2px;">
        <div style="font-size:10.5px; color:var(--ink2); font-weight:700; letter-spacing:.3px;">สถานะระบบ · SYSTEM</div>
        <div style="display:flex; align-items:center; gap:8px; margin-top:9px;">
            <span style="width:9px; height:9px; border-radius:50%; background:#5aa07e; box-shadow:0 0 0 4px rgba(90,160,126,.18);"></span>
            <span style="font-size:12px; color:var(--ink);">ออนไลน์ปกติ</span>
            <span class="tp-num" style="margin-left:auto; font-size:11px; color:var(--ink2);">v{{ trim(@file_get_contents(base_path('VERSION')) ?: '4.0.0') }}</span>
        </div>
    </div>
</aside>
{{-- หมายเหตุ: คลาส .tp-sub-link อยู่ใน resources/css/theme-v4.css --}}

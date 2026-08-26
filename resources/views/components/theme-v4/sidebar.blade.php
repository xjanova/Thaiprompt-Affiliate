{{--
 | Sidebar — ธีมนวลทองคำ V4
 | เมนูมาจาก config/menus.php → MenuService::getMenuForRole($type) (single source of truth)
 | $type = ประเภทแดชบอร์ด (admin/user/seller) — เลือกชุดเมนู + namespace ปักหมุด
 | อยู่ใน x-data="tpShell" จึงใช้ isMobile / drawer / sidebarHidden / closeDrawer ได้
 | + ระบบปักหมุด (Alpine $store.pinnedMenus, localStorage) + active หน้าย่อยแบบ URL-prefix
 --}}
@props(['type' => 'admin'])
@php
    $menuService = app(\App\Services\MenuService::class);
    $menus = $menuService->getMenuForRole($type, auth()->user());

    // ป้ายหัว drawer (มือถือ) ตามประเภทแดชบอร์ด
    $tpSideTitle = $type === 'seller' ? 'เมนูร้านค้า' : ($type === 'user' ? 'เมนูของฉัน' : 'เมนูหลังบ้าน');

    $curRoute = request()->route() ? request()->route()->getName() : null;
    $curPath  = rtrim(parse_url(url()->current(), PHP_URL_PATH) ?? '', '/');

    /**
     * ให้ "คะแนนความตรง" ของเมนูกับหน้าปัจจุบัน — 0 = ไม่ตรง, ยิ่งเจาะจงยิ่งสูง
     *
     * ⚠️ ห้ามคืนค่าเป็น bool แบบเดิม — กฎ "URL เป็น prefix = active" ทำให้เมนูติดพร้อมกันหลายอัน
     *    (เมนูแอดมินมี 113 คู่ที่ URL ซ้อนกัน เช่น /admin/fortune/commissions
     *     กับ /admin/fortune/commissions/manage) แล้วตัวเลื่อนหน้าจอก็จะเลื่อนไปผิดแถว
     *    คะแนนทำให้ "ตัวที่เจาะจงที่สุดชนะ" เหลือ active ตัวเดียวเสมอ
     *
     * @param  array  $it  เมนู 1 รายการ
     * @return int คะแนน (0 = ไม่ใช่หน้านี้)
     */
    $matchScore = function (array $it) use ($curRoute, $curPath) {
        // ลิงก์ออกนอกเว็บ (เว็บจันทรา ฯลฯ) ไม่มีวันเป็นหน้าปัจจุบัน
        if (! empty($it['external'])) return 0;

        $best = 0;

        // 1) เทียบชื่อ route — แม่นที่สุด และรองรับหน้าย่อย เช่น admin.fortune.users.show
        $r = $it['route'] ?? null;
        if ($r && $curRoute) {
            $base = preg_replace('/\.(index|show|edit|create)$/', '', $r);
            if ($curRoute === $r) {
                $best = 300000;
            } elseif ($base !== '' && str_starts_with($curRoute, $base . '.')) {
                $best = max($best, 200000 + strlen($base));
            }
        }

        // 2) เทียบ path ของ URL — ตัดโฮสต์/คิวรีทิ้งก่อน (config บางรายการเก็บ url แบบ relative)
        $u = $it['url'] ?? null;
        if ($u && $u !== '#') {
            $iu = rtrim(parse_url($u, PHP_URL_PATH) ?? '', '/');
            if ($iu !== '' && $curPath !== '') {
                if ($iu === $curPath) {
                    $best = max($best, 100000 + strlen($iu));
                } elseif (str_starts_with($curPath . '/', $iu . '/')) {
                    // หน้าปัจจุบันอยู่ "ใต้" เมนูนี้ — prefix ที่ยาวกว่า = เจาะจงกว่า = ชนะ
                    $best = max($best, strlen($iu));
                }
            }
        }

        return $best;
    };

    // หา "ผู้ชนะ" เพียงตัวเดียวทั้ง sidebar → ใช้ทั้งไฮไลต์ active, เปิด accordion และเลื่อนหน้าจอ
    $bestScore     = 0;
    $activeGroupId = null;
    $activeKey     = null;
    foreach ($menus as $gi => $g) {
        if (($s = $matchScore($g)) > $bestScore) {
            $bestScore = $s;
            $activeGroupId = $g['id'] ?? \Illuminate\Support\Str::slug($g['label'] ?? 'g');
            $activeKey = "g{$gi}";
        }
        foreach (($g['submenu'] ?? []) as $si => $sub) {
            if (! is_array($sub)) continue;
            if (($s = $matchScore($sub)) > $bestScore) {
                $bestScore = $s;
                $activeGroupId = $g['id'] ?? \Illuminate\Support\Str::slug($g['label'] ?? 'g');
                $activeKey = "g{$gi}s{$si}";
            }
        }
    }
@endphp

<aside
    :style="isMobile
        ? `position:fixed; top:0; bottom:0; left:${drawer ? '0' : '-320px'}; z-index:60; width:288px; background:var(--bg); box-shadow:8px 0 30px rgba(0,0,0,.22); padding:14px 12px; transition:left .26s ease; display:flex; flex-direction:column; gap:8px;`
        : `position:sticky; top:84px; align-self:flex-start; width:${sidebarHidden ? '0px' : 'var(--tp-side-w)'}; ${sidebarHidden ? 'opacity:0; pointer-events:none; overflow:hidden;' : ''} max-height:calc(100vh - 100px); display:flex; flex-direction:column; gap:8px; transition:width .22s ease, opacity .22s ease;`">

    {{-- หัว drawer (มือถือ) --}}
    <div x-show="isMobile" x-cloak style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2px;">
        <span style="font-weight:700; font-size:15px;">{{ $tpSideTitle }}</span>
        <button @click="closeDrawer()" type="button" class="tp-icon-btn" style="width:34px; height:34px; border-radius:11px; color:var(--ink2);"><i class="fas fa-xmark"></i></button>
    </div>

    {{-- รายการเมนู (เลื่อนได้) --}}
    <div x-data="{ openId: @js($activeGroupId), pinActive(u) { if (!u) return false; const c = location.origin + location.pathname; const x = String(u).replace(/\/+$/, ''); return c === x || c.startsWith(x + '/'); } }"
         x-init="const tpScroll = () => { const a = $el.querySelector('[data-tpactive]'); if (!a) return; const ar = a.getBoundingClientRect(); if (!ar.height) return; const cr = $el.getBoundingClientRect(); $el.scrollTop += (ar.top - cr.top) - $el.clientHeight / 2 + ar.height / 2; }; $nextTick(tpScroll); [320, 700, 1200].forEach(t => setTimeout(tpScroll, t))"
         style="position:relative; flex:1; min-height:0; overflow-y:auto; overflow-x:visible; display:flex; flex-direction:column; gap:7px; padding:2px;">

        {{-- ===== 📌 เมนูที่ปักหมุด (ดึงจาก $store.pinnedMenus — localStorage) ===== --}}
        <div x-show="$store.pinnedMenus.getPinnedMenus(@js($type)).length > 0" x-cloak
             style="display:flex; flex-direction:column; gap:6px;">
            <div style="display:flex; align-items:center; gap:7px; padding:2px 6px 2px;">
                <i class="fas fa-thumbtack" style="color:var(--accent1); font-size:11px;"></i>
                <span style="font-size:10.5px; font-weight:700; color:var(--ink2); letter-spacing:.3px;">ปักหมุด</span>
                <span class="tp-num" style="font-size:10px; color:var(--ink2);"
                      x-text="'(' + $store.pinnedMenus.getPinnedMenus(@js($type)).length + ')'"></span>
                <button @click="if (confirm('ยกเลิกปักหมุดทั้งหมด?')) $store.pinnedMenus.clearAll(@js($type))" type="button"
                        title="ยกเลิกปักหมุดทั้งหมด"
                        style="margin-left:auto; border:0; background:transparent; cursor:pointer; color:var(--ink2); opacity:.55; font-size:11px; padding:2px 4px;">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <template x-for="p in $store.pinnedMenus.getPinnedMenus(@js($type))" :key="p.key">
                <div style="position:relative;">
                    <a :href="p.url" @click="if (isMobile) closeDrawer()" class="tp-card"
                       :style="{ boxShadow: pinActive(p.url) ? 'var(--inset)' : 'var(--raise)' }"
                       style="display:flex; align-items:center; gap:10px; padding:8px 30px 8px 10px; border-radius:13px; text-decoration:none; color:var(--ink);">
                        <span class="tp-tile" style="width:28px; height:28px; border-radius:9px; font-size:12px;">
                            <template x-if="p.icon && p.icon.includes('fa-')"><i :class="p.icon"></i></template>
                            <template x-if="!(p.icon && p.icon.includes('fa-'))"><span x-text="p.icon || '📌'"></span></template>
                        </span>
                        <span style="font-size:12.5px; font-weight:600; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="p.label"></span>
                    </a>
                    <button type="button" @click.prevent.stop="$store.pinnedMenus.unpin(@js($type), p.key)" title="ยกเลิกปักหมุด"
                            style="position:absolute; right:7px; top:50%; transform:translateY(-50%); border:0; background:transparent; cursor:pointer; color:#d9534f; opacity:.55; font-size:11px; padding:4px;">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </template>

            <hr class="tp-divider" style="margin:6px 4px 2px;">
        </div>

        @foreach($menus as $gi => $group)
            @php
                $gid     = $group['id'] ?? \Illuminate\Support\Str::slug($group['label'] ?? 'g');
                $gicon   = $group['icon'] ?? '•';
                $glabel  = $group['label'] ?? '';
                $gen     = \Illuminate\Support\Str::headline($gid);
                $gbadge  = $group['badge'] ?? null;
                $subs    = $group['submenu'] ?? [];
                $hasSubs = is_array($subs) && count($subs) > 0;
                $gurl    = $group['url'] ?? null;
                $gExternal = ! empty($group['external']);
                // กลุ่มที่มีเมนูย่อย → ไฮไลต์เมื่อ "ผู้ชนะ" อยู่ในกลุ่มนี้ / เมนูเดี่ยว → ต้องเป็นผู้ชนะเอง
                $gIsWinner = ($activeKey === "g{$gi}");
                $gActive   = $hasSubs ? ($gid === $activeGroupId) : $gIsWinner;
            @endphp

            <div>
                @if($hasSubs)
                    {{-- กลุ่มที่มีเมนูย่อย → accordion --}}
                    <button type="button"
                            @click="openId = openId === '{{ $gid }}' ? '' : '{{ $gid }}'"
                            class="tp-card" @if($gIsWinner) data-tpactive @endif
                            style="cursor:pointer; width:100%; text-align:left; display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:15px; {{ $gActive ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);' }}">
                        <span class="tp-tile" style="width:33px; height:33px; border-radius:11px; font-size:15px;">
                            @if(str_contains($gicon,'fa-'))<i class="{{ $gicon }}"></i>@else{!! $gicon !!}@endif
                        </span>
                        <span style="line-height:1.15; flex:1; min-width:0;">
                            <span style="display:block; font-weight:600; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $glabel }}</span>
                            <span style="display:block; font-size:10px; color:var(--ink2); font-weight:500; letter-spacing:.2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $gen }}</span>
                        </span>
                        @if($gbadge)<span class="tp-pill tp-pill-gold" style="font-size:9px; padding:2px 7px;">{{ $gbadge }}</span>@endif
                        <span style="font-size:10px; color:var(--ink2); width:12px; text-align:center; flex:none; transition:transform .2s ease;" :style="{ transform: openId === '{{ $gid }}' ? 'rotate(90deg)' : 'none' }">▸</span>
                    </button>

                    <div x-show="openId === '{{ $gid }}'" x-cloak
                         style="position:relative; margin:4px 0 6px 22px; padding-left:16px; display:flex; flex-direction:column; gap:3px; overflow:hidden; animation:tpSub .26s cubic-bezier(.2,.8,.3,1) backwards;">
                        {{-- เส้น RGB วิ่ง --}}
                        <span style="position:absolute; left:0; top:5px; bottom:5px; width:3px; border-radius:3px; background:linear-gradient(180deg,#ff5a5a,#ffb24d,#ffe24d,#5ad65a,#4dd2ff,#7a8cff,#c44dff,#ff5a5a); background-size:100% 220%; box-shadow:0 0 7px rgba(120,140,255,.4); animation:tpHue 5s linear infinite, tpFlow 2.6s linear infinite;"></span>

                        @foreach($subs as $si => $sub)
                            @php
                                if (!is_array($sub)) continue;
                                $slabel = $sub['label'] ?? '';
                                $surl   = $sub['url'] ?? null;
                                $sActive = ($activeKey === "g{$gi}s{$si}");
                                $isDivider = ($slabel === '---' || $slabel === '');
                                $isHeader  = !$isDivider && empty($surl);
                                $sPinData  = ['label' => $slabel, 'url' => $surl, 'icon' => $gicon];
                            @endphp

                            @if($isDivider)
                                <hr class="tp-divider" style="margin:5px 6px;">
                            @elseif($isHeader)
                                <div style="font-size:10.5px; font-weight:700; color:var(--ink2); padding:7px 11px 3px; letter-spacing:.2px;">{{ $slabel }}</div>
                            @else
                                {{-- ลิงก์ย่อย + ปุ่มปักหมุด (ปุ่มเป็น sibling ของ <a> เพราะห้าม button ซ้อนใน a) --}}
                                @php $sExternal = ! empty($sub['external']); @endphp
                                <div class="tp-mrow" style="position:relative;">
                                    <a href="{{ $surl }}"
                                       @if($sExternal) target="_blank" rel="noopener" @else @click="if (isMobile) closeDrawer()" @endif
                                       class="tp-sub-link" @if($sActive) data-tpactive @endif
                                       style="padding-right:28px; {{ $sActive ? 'box-shadow:var(--inset-sm); color:var(--ink);' : 'color:var(--ink2);' }}">
                                        <span style="width:6px; height:6px; flex:none; border-radius:50%; background:{{ $sActive ? 'linear-gradient(135deg,var(--accent1),var(--accent2))' : 'var(--ink2)' }};"></span>
                                        <span style="font-weight:{{ $sActive ? 700 : 500 }}; font-size:12.5px; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $slabel }}</span>
                                        @if($sExternal)<i class="fas fa-arrow-up-right-from-square" style="font-size:8.5px; color:var(--ink2); flex:none;" title="เปิดเว็บจันทรา"></i>@endif
                                        @if(!empty($sub['badge']))<span class="tp-pill tp-pill-soft" style="font-size:8.5px; padding:2px 6px;">{{ $sub['badge'] }}</span>@endif
                                    </a>
                                    <button type="button" title="ปักหมุด" class="tp-pin-btn"
                                            @click.prevent.stop="$store.pinnedMenus.toggle(@js($type),@js($surl), @js($sPinData))"
                                            :class="$store.pinnedMenus.isPinned(@js($type),@js($surl)) ? 'on' : ''"
                                            style="position:absolute; right:6px; top:50%; transform:translateY(-50%); border:0; background:transparent; cursor:pointer; padding:4px; font-size:9.5px; line-height:1;">
                                        <i class="fas fa-thumbtack"></i>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    {{-- เมนูเดี่ยว (ลิงก์ตรง) + ปุ่มปักหมุด --}}
                    @php $gPinData = ['label' => $glabel, 'url' => $gurl, 'icon' => $gicon]; @endphp
                    <div class="tp-mrow" style="position:relative;">
                        <a href="{{ $gurl ?? '#' }}"
                           @if($gExternal) target="_blank" rel="noopener" @else @click="if (isMobile) closeDrawer()" @endif
                           class="tp-card" @if($gActive) data-tpactive @endif
                           style="display:flex; align-items:center; gap:12px; padding:10px 34px 10px 12px; border-radius:15px; text-decoration:none; color:var(--ink); {{ $gActive ? 'box-shadow:var(--inset);' : 'box-shadow:var(--raise);' }}">
                            <span class="tp-tile" style="width:33px; height:33px; border-radius:11px; font-size:15px;">
                                @if(str_contains($gicon,'fa-'))<i class="{{ $gicon }}"></i>@else{!! $gicon !!}@endif
                            </span>
                            <span style="line-height:1.15; flex:1; min-width:0;">
                                <span style="display:block; font-weight:600; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $glabel }}</span>
                                <span style="display:block; font-size:10px; color:var(--ink2); font-weight:500; letter-spacing:.2px;">{{ $gen }}</span>
                            </span>
                            @if($gbadge)<span class="tp-pill tp-pill-gold" style="font-size:9px; padding:2px 7px;">{{ $gbadge }}</span>@endif
                        </a>
                        @if($gurl && $gurl !== '#')
                            <button type="button" title="ปักหมุด" class="tp-pin-btn"
                                    @click.prevent.stop="$store.pinnedMenus.toggle(@js($type),@js($gurl), @js($gPinData))"
                                    :class="$store.pinnedMenus.isPinned(@js($type),@js($gurl)) ? 'on' : ''"
                                    style="position:absolute; right:9px; top:50%; transform:translateY(-50%); border:0; background:transparent; cursor:pointer; padding:4px; font-size:10px; line-height:1;">
                                <i class="fas fa-thumbtack"></i>
                            </button>
                        @endif
                    </div>
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

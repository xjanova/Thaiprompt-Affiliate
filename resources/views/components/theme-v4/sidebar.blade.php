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
    <div x-show="isMobile" x-cloak class="flex" style="align-items:center; justify-content:space-between; margin-bottom:2px;">
        <span style="font-weight:700; font-size:15px;">{{ $tpSideTitle }}</span>
        <button @click="closeDrawer()" type="button" class="tp-icon-btn" style="width:34px; height:34px; border-radius:11px; color:var(--ink2);"><i class="fas fa-xmark"></i></button>
    </div>

    {{-- รายการเมนู (เลื่อนได้)
     | ⚡ ทำให้เบา (2026-11-04): เดิม 201 แถว × ~1.9KB = 423KB ต่อหน้า → หลังบ้านหน่วง
     |   - สไตล์ซ้ำ ๆ ย้ายไปเป็นคลาส .tp-grp / .tp-sub-* ใน resources/css/theme-v4.css
     |   - ปุ่มปักหมุดไม่มี binding ต่อแถวแล้ว: @click ตัวเดียวบนกล่องนี้ (event delegation)
     |     อ่าน URL/ชื่อจาก <a> ในแถวเดียวกัน · ไอคอนจาก data-icon ของกลุ่ม
     |   - สถานะ "ปักแล้ว" (.on) ตั้งด้วย x-effect ตัวเดียว เมื่อ $store.pinnedMenus เปลี่ยน
     |   ⚠️ key ปักหมุด = ค่า href ตามที่ render (เหมือนเดิม) — ห้ามเปลี่ยนเป็น a.href (absolute) ไม่งั้นหมุดเก่าใน localStorage หาย
     --}}
    <div x-data="{ openId: @js($activeGroupId), pinActive(u) { if (!u) return false; const c = location.origin + location.pathname; const x = String(u).replace(/\/+$/, ''); return c === x || c.startsWith(x + '/'); } }"
         x-init="const tpScroll = () => { const a = $el.querySelector('[data-tpactive]'); if (!a) return; const ar = a.getBoundingClientRect(); if (!ar.height) return; const cr = $el.getBoundingClientRect(); $el.scrollTop += (ar.top - cr.top) - $el.clientHeight / 2 + ar.height / 2; }; $nextTick(tpScroll); [320, 700, 1200].forEach(t => setTimeout(tpScroll, t))"
         x-effect="const keys = new Set($store.pinnedMenus.getPinnedMenus(@js($type)).map(p => p.key)); $el.querySelectorAll('[data-pin]').forEach(b => { const a = b.parentElement.querySelector('a'); b.classList.toggle('on', !!a && keys.has(a.getAttribute('href'))); })"
         @click="const t = $event.target, pin = t.closest('[data-pin]'), grp = t.closest('[data-gid]');
                 if (pin) { $event.preventDefault(); $event.stopPropagation(); const a = pin.parentElement.querySelector('a'), u = a.getAttribute('href'), n = a.querySelector('[data-name]'); $store.pinnedMenus.toggle(@js($type), u, { label: (n ? n.textContent : a.textContent).trim(), url: u, icon: (pin.closest('[data-icon]') || {}).dataset?.icon || '' }); }
                 else if (grp) { openId = openId === grp.dataset.gid ? '' : grp.dataset.gid; }
                 else if (isMobile && t.closest('a[href]:not([target])')) { closeDrawer(); }"
         class="tp-side-list">

        {{-- ===== 📌 เมนูที่ปักหมุด (ดึงจาก $store.pinnedMenus — localStorage) ===== --}}
        <div x-show="$store.pinnedMenus.getPinnedMenus(@js($type)).length > 0" x-cloak class="flex"
             style="flex-direction:column; gap:6px;">
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
                    <a :href="p.url" class="tp-card"
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

        {{-- ===== เมนูจาก config/menus.php — markup ต่อแถวต้องสั้น (มี ~200 แถว ทุกหน้า) ===== --}}
        @foreach($menus as $gi => $group)
@php
    $gid     = $group['id'] ?? \Illuminate\Support\Str::slug($group['label'] ?? 'g');
    $gicon   = $group['icon'] ?? '•';
    $gbadge  = $group['badge'] ?? null;
    $subs    = $group['submenu'] ?? [];
    $hasSubs = is_array($subs) && count($subs) > 0;
    $gurl    = $group['url'] ?? null;
    // กลุ่มที่มีเมนูย่อย → ไฮไลต์เมื่อ "ผู้ชนะ" อยู่ในกลุ่มนี้ / เมนูเดี่ยว → ต้องเป็นผู้ชนะเอง
    $gIsWinner = ($activeKey === "g{$gi}");
    $gActive   = $hasSubs ? ($gid === $activeGroupId) : $gIsWinner;
    // หัวกลุ่ม (ไอคอน + ชื่อไทย + ชื่ออังกฤษ + ป้าย) — ใช้ทั้งปุ่ม accordion และลิงก์เดี่ยว
    $gHead = '<span class="tp-tile tp-grp-tile">'.(str_contains($gicon, 'fa-') ? '<i class="'.e($gicon).'"></i>' : $gicon).'</span>'
        .'<span class="tp-grp-txt"><span class="tp-grp-name" data-name>'.e($group['label'] ?? '').'</span><span class="tp-grp-en">'.e(\Illuminate\Support\Str::headline($gid)).'</span></span>'
        .($gbadge ? '<span class="tp-pill tp-pill-gold tp-grp-badge">'.e($gbadge).'</span>' : '');
@endphp
<div data-icon="{{ $gicon }}">
@if($hasSubs)
<button type="button" data-gid="{{ $gid }}" :aria-expanded="openId === '{{ $gid }}'" class="tp-card tp-grp{{ $gActive ? ' is-active' : '' }}"@if($gIsWinner) data-tpactive @endif>{!! $gHead !!}<span class="tp-grp-caret">▸</span></button>
<div x-show="openId === '{{ $gid }}'" x-cloak class="tp-subwrap">
@foreach($subs as $si => $sub)
@php
    if (! is_array($sub)) continue;
    $slabel = $sub['label'] ?? '';
    $surl   = $sub['url'] ?? null;
    $sActive = ($activeKey === "g{$gi}s{$si}");
@endphp
@if($slabel === '---' || $slabel === '')
<hr class="tp-divider tp-sub-hr">
@elseif(empty($surl))
<div class="tp-sub-head">{{ $slabel }}</div>
@else
{{-- ลิงก์ย่อย + ปุ่มปักหมุด (ปุ่มเป็น sibling ของ <a> เพราะห้าม button ซ้อนใน a) --}}
<div class="tp-mrow"><a href="{{ $surl }}" class="tp-sub-link{{ $sActive ? ' is-active' : '' }}"@if($sActive) data-tpactive @endif @if(! empty($sub['external'])) target="_blank" rel="noopener" @endif><span class="tp-sub-dot"></span><span class="tp-sub-label" data-name>{{ $slabel }}</span>@if(! empty($sub['external']))<i class="fas fa-arrow-up-right-from-square tp-sub-ext" title="เปิดเว็บจันทรา"></i>@endif @if(! empty($sub['badge']))<span class="tp-pill tp-pill-soft tp-sub-badge">{{ $sub['badge'] }}</span>@endif</a><button type="button" title="ปักหมุด" class="tp-pin-btn" data-pin><i class="fas fa-thumbtack"></i></button></div>
@endif
@endforeach
</div>
@else
{{-- เมนูเดี่ยว (ลิงก์ตรง) + ปุ่มปักหมุด --}}
<div class="tp-mrow"><a href="{{ $gurl ?? '#' }}" class="tp-card tp-grp tp-grp-link{{ $gActive ? ' is-active' : '' }}"@if($gActive) data-tpactive @endif @if(! empty($group['external'])) target="_blank" rel="noopener" @endif>{!! $gHead !!}</a>@if($gurl && $gurl !== '#')<button type="button" title="ปักหมุด" class="tp-pin-btn tp-pin-btn-grp" data-pin><i class="fas fa-thumbtack"></i></button>@endif</div>
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
{{-- หมายเหตุ: คลาส .tp-side-list / .tp-grp* / .tp-sub-* / .tp-pin-btn อยู่ใน resources/css/theme-v4.css (หมวด 6) --}}

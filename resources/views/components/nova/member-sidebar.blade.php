{{--
 | เมนูข้างพื้นที่สมาชิก — ธีมโนวา (กรมท่า + ทอง)
 | 🚨 เมนูมาจาก config/menus.php → MenuService::getMenuForRole('user') เท่านั้น — ห้ามเขียนเมนูลงไฟล์นี้ (.claude/MENU_RULES.md)
 | จอแคบ (< 1100px) กลายเป็นลิ้นชักเลื่อนจากซ้าย คุมด้วย menuOpen / closeMenu() ของ x-data="nvmShell" ใน layout
 | เมนูปักหมุด อ่านจาก $store.pinnedMenus (namespace 'user' ชุดเดียวกับเมนูข้างธีม V4) — ปักเพิ่มได้จากหน้าอื่นที่ใช้ธีม V4
 |
 | props: menus  array  เมนูที่ MenuService คืนมา (layout ส่งให้)
 --}}
@props(['menus' => []])
@php
    $nvmCurRoute = request()->route()?->getName();
    $nvmCurPath = rtrim(parse_url(url()->current(), PHP_URL_PATH) ?? '', '/');

    /**
     * ให้คะแนนความตรงของเมนูกับหน้าปัจจุบัน (0 = ไม่ใช่หน้านี้ ยิ่งเจาะจงยิ่งสูง)
     * หลักเดียวกับ components/theme-v4/sidebar — ตัวที่เจาะจงที่สุดชนะ จึงติด active ได้ทีละอันเสมอ
     */
    $nvmScore = function (array $it) use ($nvmCurRoute, $nvmCurPath): int {
        if (! empty($it['external'])) {
            return 0;
        }
        $best = 0;
        $r = $it['route'] ?? null;
        if ($r && $nvmCurRoute) {
            $base = preg_replace('/\.(index|show|edit|create)$/', '', $r);
            if ($nvmCurRoute === $r) {
                $best = 300000;
            } elseif ($base !== '' && str_starts_with($nvmCurRoute, $base.'.')) {
                $best = 200000 + strlen($base);
            }
        }
        $u = $it['url'] ?? null;
        if ($u && $u !== '#') {
            $iu = rtrim(parse_url($u, PHP_URL_PATH) ?? '', '/');
            if ($iu !== '' && $nvmCurPath !== '') {
                if ($iu === $nvmCurPath) {
                    $best = max($best, 100000 + strlen($iu));
                } elseif (str_starts_with($nvmCurPath.'/', $iu.'/')) {
                    $best = max($best, strlen($iu));
                }
            }
        }

        return $best;
    };

    $nvmBest = 0;
    $nvmActiveGroup = null;
    $nvmActiveKey = null;
    foreach ($menus as $gi => $g) {
        $gid = $g['id'] ?? \Illuminate\Support\Str::slug($g['label'] ?? 'g');
        if (($s = $nvmScore($g)) > $nvmBest) {
            [$nvmBest, $nvmActiveGroup, $nvmActiveKey] = [$s, $gid, "g{$gi}"];
        }
        foreach (($g['submenu'] ?? []) as $si => $sub) {
            if (is_array($sub) && ($s = $nvmScore($sub)) > $nvmBest) {
                [$nvmBest, $nvmActiveGroup, $nvmActiveKey] = [$s, $gid, "g{$gi}s{$si}"];
            }
        }
    }

    // ไอคอนใน config เป็นอีโมจิหรือคลาส Font Awesome — แสดงได้ทั้งสองแบบ
    $nvmIcon = fn ($icon) => str_contains((string) $icon, 'fa-')
        ? '<i class="'.e($icon).'" aria-hidden="true"></i>'
        : e($icon ?: '•');
@endphp

<nav id="nvm-side" class="nvm-side" :class="menuOpen ? 'is-open' : ''" aria-label="เมนูสมาชิก"
     x-data="{ openId: @js($nvmActiveGroup) }"
     x-init="$nextTick(() => {
         {{-- เลื่อนเฉพาะในเมนู (ไม่ใช้ scrollIntoView เพราะจะลากทั้งหน้าเลื่อนตาม) และเลื่อนเมื่อเมนูที่เปิดอยู่ตกขอบล่างเท่านั้น --}}
         const a = $el.querySelector('[data-nvm-active]');
         if (! a) return;
         const top = a.getBoundingClientRect().top - $el.getBoundingClientRect().top + $el.scrollTop;
         if (top > $el.clientHeight * 0.6) $el.scrollTop = top - $el.clientHeight / 2;
     })">

    <div class="nvm-side__head">
        <x-theme-v4.brand-logo :height="30" variant="dark" />
        <button class="nvm-ibtn" type="button" @click="closeMenu()" aria-label="ปิดเมนู"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg></button>
    </div>

    {{-- เมนูปักหมุด --}}
    <template x-if="$store.pinnedMenus && $store.pinnedMenus.getPinnedMenus('user').length > 0">
        <div>
            <div class="nvm-side__label">Pinned · ปักหมุด</div>
            <template x-for="p in $store.pinnedMenus.getPinnedMenus('user')" :key="p.key">
                <a class="nvm-side__item" :href="p.url" @click="closeMenu()">
                    <span class="nvm-side__ic"><svg class="nvm-ico" style="width:15px;height:15px"><use href="#nvm-i-pin"/></svg></span>
                    <span class="nvm-side__txt"><span x-text="p.label"></span></span>
                </a>
            </template>
            <hr class="nvm-side__hr">
        </div>
    </template>

    <div class="nvm-side__label">Member · เมนูของฉัน</div>

    @foreach($menus as $gi => $group)
        @php
            $gid = $group['id'] ?? \Illuminate\Support\Str::slug($group['label'] ?? 'g');
            $subs = is_array($group['submenu'] ?? null) ? $group['submenu'] : [];
            $gUrl = $group['url'] ?? '#';
            $gExternal = ! empty($group['external']);
            $gOn = $subs ? ($gid === $nvmActiveGroup) : ($nvmActiveKey === "g{$gi}");
            $gLatin = \Illuminate\Support\Str::headline($gid);
        @endphp

        @if($subs)
            <button type="button" class="nvm-side__item {{ $gOn ? 'is-on' : '' }}"
                    @click="openId = openId === @js($gid) ? '' : @js($gid)"
                    :aria-expanded="openId === @js($gid)" aria-controls="nvm-sub-{{ $gid }}">
                <span class="nvm-side__ic">{!! $nvmIcon($group['icon'] ?? null) !!}</span>
                <span class="nvm-side__txt"><span>{{ $group['label'] ?? '' }}</span><small>{{ $gLatin }}</small></span>
                @if(! empty($group['badge']))<span class="nvm-side__tag">{{ $group['badge'] }}</span>@endif
                <svg class="nvm-ico nvm-side__chev" :class="openId === @js($gid) ? 'is-open' : ''"><use href="#nvm-i-chev"/></svg>
            </button>
            <div class="nvm-side__sub" id="nvm-sub-{{ $gid }}" x-show="openId === @js($gid)" @if(! $gOn) x-cloak @endif>
                @foreach($subs as $si => $sub)
                    @php
                        if (! is_array($sub)) {
                            continue;
                        }
                        $sLabel = $sub['label'] ?? '';
                        $sUrl = $sub['url'] ?? null;
                        $sOn = $nvmActiveKey === "g{$gi}s{$si}";
                        $sExternal = ! empty($sub['external']);
                    @endphp
                    @if($sLabel === '---' || $sLabel === '')
                        <hr class="nvm-side__hr">
                    @elseif(! $sUrl || $sUrl === '#')
                        <div class="nvm-side__subhead">{{ $sLabel }}</div>
                    @else
                        <a class="nvm-side__sublink {{ $sOn ? 'is-on' : '' }}" href="{{ $sUrl }}"
                           @if($sOn) aria-current="page" data-nvm-active @endif
                           @if($sExternal) target="_blank" rel="noopener" @else @click="closeMenu()" @endif>
                            <span>{{ $sLabel }}</span>
                            @if(! empty($sub['badge']))<span class="nvm-side__tag">{{ $sub['badge'] }}</span>@endif
                            @if($sExternal)<svg class="nvm-ico"><use href="#nvm-i-up"/></svg>@endif
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <a class="nvm-side__item {{ $gOn ? 'is-on' : '' }}" href="{{ $gUrl }}"
               @if($gOn) aria-current="page" data-nvm-active @endif
               @if($gExternal) target="_blank" rel="noopener" @else @click="closeMenu()" @endif>
                <span class="nvm-side__ic">{!! $nvmIcon($group['icon'] ?? null) !!}</span>
                <span class="nvm-side__txt"><span>{{ $group['label'] ?? '' }}</span><small>{{ $gLatin }}</small></span>
                @if(! empty($group['badge']))<span class="nvm-side__tag">{{ $group['badge'] }}</span>@endif
            </a>
        @endif
    @endforeach

    <div class="nvm-side__spacer"></div>

    {{-- ชวนโหลดแอป (ไฟล์ APK จากเซิร์ฟเวอร์เราเอง — ลิงก์เดียวกับแถบยืนยันตัวตน) --}}
    <div class="nvm-side__app">
        <b>แอป Thai Prompt</b>
        <p>ครบทุกบริการในมือ แจ้งเตือนรายได้ทันที</p>
        <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ \App\Services\Ekyc\EkycGate::downloadUrl() }}"><svg class="nvm-ico"><use href="#nvm-i-out" transform="rotate(180 12 12)"/></svg>โหลดแอป Android</a>
        <img src="{{ asset('images/nova/mascot/welcome.webp') }}" alt="" width="92" height="92">
    </div>
</nav>

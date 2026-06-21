@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{--
    🎴 บุคลิกลูกค้า (Persona Memory) — RPG Card Grid (ธีม V4 นวลทองคำ)
    คงตรรกะเดิม 100%: rarity / level / XP / radar logic, ฟอร์มกรอง GET, chips rarity+tag,
    ลิงก์ personas.show({id}). ย้าย CSS จาก @push('styles') → <style> ใน @push('scripts')
    ตัวแปร controller: $personas (paginator), $stats, $topTags, $filters, $pageTitle
--}}

@php
    // 🎨 แมป rarity → สี/ป้ายในธีม V4 (อ้าง CSS var/STATUS COLORS — ไม่ใช้ Tailwind class จาก getRarityConfig)
    // คงการคำนวณ rarity เดิม ($p->getRarity()) เพียงแต่ render ด้วยสีธีมทอง
    $rarityV4 = [
        'legendary' => ['label' => 'ตำนาน',   'icon' => '👑', 'color' => '#e0a52e', 'soft' => 'rgba(224,165,46,.16)'],
        'epic'      => ['label' => 'มหากาฬ',  'icon' => '💜', 'color' => '#b79ae8', 'soft' => 'rgba(183,154,232,.16)'],
        'rare'      => ['label' => 'หายาก',   'icon' => '💎', 'color' => '#5689b8', 'soft' => 'rgba(86,137,184,.16)'],
        'common'    => ['label' => 'ทั่วไป',   'icon' => '🌿', 'color' => '#9a8f7c', 'soft' => 'rgba(154,143,124,.14)'],
    ];

    // ลำดับ + ป้ายของ rarity distribution (hero) — ใช้สีธีมเดียวกัน
    $rarTotal = max(1, array_sum($stats['rarity_dist']));
    $rarOrder = [
        'legendary' => ['👑 ตำนาน',  '#e0a52e'],
        'epic'      => ['💜 มหากาฬ', '#b79ae8'],
        'rare'      => ['💎 หายาก',  '#5689b8'],
        'common'    => ['🌿 ทั่วไป',  '#9a8f7c'],
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · บุคลิกลูกค้า</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">Persona Codex 🎴</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px; max-width:560px;">
                ระบบจดจำบุคลิก/นิสัยลูกค้าระยะยาว — แต่ละการ์ดคือ "ตัวละคร" ที่บอทเรียนรู้สะสมไว้ใช้ปรับ tone การคุย
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.dashboard'))
                <a href="{{ route('admin.fortune.dashboard') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </a>
            @endif
            @if(Route::has('admin.fortune.users.index'))
                <a href="{{ route('admin.fortune.users.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-users"></i> ผู้ใช้ดูดวง
                </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI grid ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        {{-- ตัวละครทั้งหมด --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-id-badge"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ตัวละครทั้งหมด</div>
                </div>
            </div>
        </div>
        {{-- เกิดใหม่วันนี้ --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5aa07e;">
                    <i class="fas fa-seedling"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['today']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เกิดใหม่วันนี้</div>
                </div>
            </div>
        </div>
        {{-- Active 7 วัน --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5689b8;">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['active_week']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Active ใน 7 วัน</div>
                </div>
            </div>
        </div>
        {{-- เฉลี่ย observations --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:44px; height:44px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#b79ae8;">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['avg_observations']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เฉลี่ย observations/คน</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Rarity Distribution bar (CSS) ===== --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <i class="fas fa-gem" style="color:var(--accent1);"></i>
            การกระจาย Rarity
            <span style="font-weight:400; color:var(--ink2); font-size:12px;">(จาก {{ number_format(min($stats['total'], 500)) }} ตัวล่าสุด)</span>
        </div>

        {{-- แท่งสัดส่วน Rarity --}}
        <div style="display:flex; height:14px; border-radius:9999px; overflow:hidden; box-shadow:var(--inset-sm); background:var(--bg);">
            @foreach($rarOrder as $key => [$label, $col])
                @php $pct = ($stats['rarity_dist'][$key] / $rarTotal) * 100; @endphp
                @if($pct > 0)
                    <div style="height:100%; width:{{ $pct }}%; background:{{ $col }};"
                         title="{{ $label }}: {{ $stats['rarity_dist'][$key] }}"></div>
                @endif
            @endforeach
        </div>

        {{-- legend --}}
        <div style="display:flex; flex-wrap:wrap; gap:14px; margin-top:12px;">
            @foreach($rarOrder as $key => [$label, $col])
                <span style="display:inline-flex; align-items:center; gap:7px; font-size:12.5px; color:var(--ink2);">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:9999px; background:{{ $col }};"></span>
                    {{ $label }}
                    <span class="tp-num" style="font-weight:700; color:var(--ink);">{{ number_format($stats['rarity_dist'][$key]) }}</span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- ===== Filter card (ฟอร์ม GET — คง name/field ทุกตัว) ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.fortune.personas.index') }}" style="display:flex; flex-direction:column; gap:14px;">

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                {{-- ค้นหา --}}
                <div style="grid-column:1 / -1;">
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        <i class="fas fa-magnifying-glass"></i> ค้นหา
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="search" value="{{ $filters['search'] }}"
                               placeholder="ชื่อ / User ID / topic tag..."
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>

                {{-- ช่องทาง --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        <i class="fas fa-mobile-screen"></i> ช่องทาง
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform" style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="">ทั้งหมด</option>
                            <option value="facebook" {{ $filters['platform'] === 'facebook' ? 'selected' : '' }}>📘 Facebook</option>
                            <option value="line" {{ $filters['platform'] === 'line' ? 'selected' : '' }}>🟢 LINE</option>
                        </select>
                    </div>
                </div>

                {{-- เรียง --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        <i class="fas fa-arrow-down-wide-short"></i> เรียงลำดับ
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="sort" style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="recent" {{ $filters['sort'] === 'recent' ? 'selected' : '' }}>ล่าสุดก่อน</option>
                            <option value="most_observed" {{ $filters['sort'] === 'most_observed' ? 'selected' : '' }}>คุยเยอะสุด</option>
                            <option value="oldest" {{ $filters['sort'] === 'oldest' ? 'selected' : '' }}>เก่าก่อน</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 🔒 hidden: คง rarity + tag ไว้ใน query เวลากดค้นหา/เรียง (ไม่ให้ chip ปัจจุบันหลุด) --}}
            <input type="hidden" name="rarity" value="{{ $filters['rarity'] }}">
            <input type="hidden" name="tag" value="{{ $filters['tag'] }}">

            {{-- ปุ่ม action --}}
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
                <a href="{{ route('admin.fortune.personas.index') }}" class="tp-btn">
                    <i class="fas fa-rotate-left"></i> ล้างตัวกรอง
                </a>
            </div>

            <div class="tp-divider"></div>

            {{-- ===== Rarity chips ===== --}}
            @php
                $rarChips = [
                    ['key' => '',          'label' => 'ทั้งหมด',  'icon' => '',   'color' => 'var(--ink2)'],
                    ['key' => 'legendary', 'label' => 'ตำนาน',    'icon' => '👑', 'color' => '#e0a52e'],
                    ['key' => 'epic',      'label' => 'มหากาฬ',   'icon' => '💜', 'color' => '#b79ae8'],
                    ['key' => 'rare',      'label' => 'หายาก',    'icon' => '💎', 'color' => '#5689b8'],
                    ['key' => 'common',    'label' => 'ทั่วไป',    'icon' => '🌿', 'color' => '#9a8f7c'],
                ];
            @endphp
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                <span style="font-size:12px; color:var(--ink2); font-weight:600;">Rarity:</span>
                @foreach($rarChips as $chip)
                    @php
                        $params = array_filter([
                            'search' => $filters['search'],
                            'platform' => $filters['platform'],
                            'sort' => $filters['sort'],
                            'rarity' => $chip['key'] ?: null,
                            'tag' => $filters['tag'],
                        ]);
                        $isActive = $filters['rarity'] === $chip['key'] || (! $filters['rarity'] && ! $chip['key']);
                    @endphp
                    <a href="{{ route('admin.fortune.personas.index', $params) }}"
                       class="tp-pill"
                       style="text-decoration:none; cursor:pointer;
                              {{ $isActive
                                 ? 'background:' . $chip['color'] . '; color:#fff; box-shadow:var(--raise);'
                                 : 'background:var(--surf); color:var(--ink2); box-shadow:var(--inset-sm);' }}">
                        @if($chip['icon']) {{ $chip['icon'] }} @endif {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- ===== Top tag chips ===== --}}
            @if(! empty($topTags))
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                    <span style="font-size:12px; color:var(--ink2); font-weight:600;">🏷️ Tag ยอดฮิต:</span>
                    @foreach(array_slice($topTags, 0, 14, true) as $tag => $cnt)
                        @php
                            $tagParams = array_filter([
                                'search' => $filters['search'],
                                'platform' => $filters['platform'],
                                'sort' => $filters['sort'],
                                'rarity' => $filters['rarity'],
                                'tag' => $tag,
                            ]);
                            $isTagActive = $filters['tag'] === $tag;
                        @endphp
                        <a href="{{ route('admin.fortune.personas.index', $tagParams) }}"
                           class="tp-pill"
                           style="text-decoration:none; cursor:pointer; gap:5px;
                                  {{ $isTagActive
                                     ? 'background:var(--accent1); color:#fff; box-shadow:var(--raise);'
                                     : 'background:var(--a1soft); color:var(--deep1); box-shadow:var(--inset-sm);' }}">
                            #{{ $tag }}
                            <span class="tp-num" style="opacity:.75;">×{{ $cnt }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </form>
    </div>

    {{-- ===== RPG Card Grid ===== --}}
    @if($personas->isEmpty())
        {{-- Empty state --}}
        <div class="tp-card" style="padding:48px 24px; text-align:center;">
            <div style="font-size:46px; margin-bottom:10px;">
                <i class="fas fa-inbox" style="color:var(--ink2);"></i>
            </div>
            <div style="font-size:18px; font-weight:800; color:var(--ink); margin-bottom:6px;">ยังไม่มี Persona</div>
            <div style="font-size:13px; color:var(--ink2); max-width:420px; margin:0 auto;">
                ระบบจะเริ่มสร้าง persona อัตโนมัติเมื่อมีลูกค้าคุยกับบอท — รอประมาณ 30 นาทีหลังคุย
            </div>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px;">
            @foreach($personas as $p)
                @php
                    // 🎮 คงตรรกะเดิม: rarity / level / XP / traits
                    $rkey   = $p->getRarity();                       // common|rare|epic|legendary
                    $rcfg   = $rarityV4[$rkey] ?? $rarityV4['common'];
                    $plat   = $p->getPlatformConfig();              // icon/label (ใช้แค่ icon)
                    $level  = $p->getLevel();
                    $xp     = $p->getXpProgress();
                    $traitsTop = array_slice($p->traits ?? [], -3);
                    $isLegend  = $rkey === 'legendary';
                @endphp

                <a href="{{ route('admin.fortune.personas.show', $p->id) }}"
                   class="tp-card tp-card-hover rpg-card"
                   style="display:block; text-decoration:none; color:inherit; padding:0; overflow:hidden; position:relative; border-top:3px solid {{ $rcfg['color'] }};">

                    {{-- ป้าย rarity มุมขวาบน --}}
                    <div style="position:absolute; top:12px; right:12px; z-index:2;">
                        <span class="tp-pill" style="background:{{ $rcfg['color'] }}; color:#fff; box-shadow:var(--raise); font-size:10.5px; letter-spacing:.3px;">
                            {{ $rcfg['icon'] }} {{ $rcfg['label'] }}
                        </span>
                    </div>

                    <div style="padding:18px;">
                        {{-- Avatar + ชื่อ --}}
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                            <div style="position:relative; flex-shrink:0;">
                                <div class="tp-num"
                                     style="width:54px; height:54px; border-radius:16px; display:flex; align-items:center; justify-content:center;
                                            font-size:19px; font-weight:800; color:#fff; background:{{ $rcfg['color'] }}; box-shadow:var(--raise);">
                                    {{ $p->initials }}
                                </div>
                                {{-- platform mini badge --}}
                                <div style="position:absolute; bottom:-4px; right:-4px; width:22px; height:22px; border-radius:9999px;
                                            background:var(--surf); box-shadow:var(--raise); display:flex; align-items:center; justify-content:center; font-size:11px;">
                                    {{ $plat['icon'] }}
                                </div>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:800; font-size:14.5px; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                                            {{ $isLegend ? 'text-shadow:0 0 12px rgba(224,165,46,.45);' : '' }}">
                                    {{ $p->display_name ?: 'ไม่ระบุชื่อ' }}
                                </div>
                                <div class="tp-num" style="font-size:11px; color:var(--ink2); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ Str::limit($p->platform_user_id, 18) }}
                                </div>
                            </div>
                        </div>

                        {{-- Level + XP bar --}}
                        <div style="margin-bottom:14px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                                <span class="tp-num" style="display:inline-flex; align-items:center; gap:4px; font-size:12.5px; font-weight:800; color:{{ $rcfg['color'] }};">
                                    ⚡ Lv.{{ $level }}
                                </span>
                                <span class="tp-num" style="font-size:10.5px; color:var(--ink2);">
                                    {{ $p->observation_count ?? 0 }} obs · XP {{ $xp }}%
                                </span>
                            </div>
                            <div style="position:relative; height:8px; border-radius:9999px; box-shadow:var(--inset-sm); background:var(--bg); overflow:hidden;">
                                <div class="rpg-xp-fill"
                                     style="position:absolute; inset:0 auto 0 0; width:{{ $xp }}%; border-radius:9999px; background:{{ $rcfg['color'] }};"></div>
                            </div>
                        </div>

                        {{-- Top traits --}}
                        @if(! empty($traitsTop))
                            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; min-height:26px;">
                                @foreach($traitsTop as $trait)
                                    <span class="tp-pill" style="background:{{ $rcfg['soft'] }}; color:{{ $rcfg['color'] }}; font-size:11px;">
                                        {{ Str::limit($trait, 16) }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div style="font-size:11px; color:var(--ink2); font-style:italic; margin-bottom:14px; min-height:26px;">
                                ยังไม่มีข้อมูลบุคลิก
                            </div>
                        @endif

                        {{-- Footer stats --}}
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;
                                    padding-top:12px; box-shadow:inset 0 1px 0 var(--sd); font-size:11px; color:var(--ink2);">
                            <span class="tp-num" title="หัวข้อที่เคยคุย">💬 {{ count($p->conversation_themes ?? []) }}</span>
                            <span class="tp-num" title="สิ่งที่ชอบ">❤️ {{ count($p->likes ?? []) }}</span>
                            <span class="tp-num" title="แท็ก">🏷️ {{ count($p->topic_tags_array) }}</span>
                            <span class="tp-num" title="คุยล่าสุด" style="text-align:right;">
                                {{ $p->last_observed_at?->diffForHumans(short: true) ?? '-' }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- ===== Pagination ===== --}}
        @if($personas->hasPages())
            <div>
                {{ $personas->links() }}
            </div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
{{-- 🔴 CSS เฉพาะหน้า — ย้ายจาก @push('styles') มาไว้ที่ @push('scripts') (ท้าย body) ตามสัญญา V4 --}}
<style>
    /* 🎮 RPG card hover lift (เสริมจาก .tp-card-hover) */
    .rpg-card {
        transition: transform 220ms cubic-bezier(.2,.9,.2,1), box-shadow 220ms ease;
        will-change: transform;
    }
    .rpg-card:hover {
        transform: translateY(-5px) scale(1.012);
    }

    /* 🎯 XP bar — ลายเส้นวิ่ง */
    .rpg-xp-fill {
        background-image: linear-gradient(45deg,
            rgba(255,255,255,.22) 25%, transparent 25%,
            transparent 50%, rgba(255,255,255,.22) 50%,
            rgba(255,255,255,.22) 75%, transparent 75%, transparent);
        background-size: 14px 14px;
        animation: rpg-xp-move 2s linear infinite;
        transition: width 320ms ease;
    }
    @keyframes rpg-xp-move {
        from { background-position: 0 0; }
        to   { background-position: 28px 0; }
    }

    @media (max-width: 480px) {
        .rpg-card:hover { transform: none; }
    }
</style>
@endpush

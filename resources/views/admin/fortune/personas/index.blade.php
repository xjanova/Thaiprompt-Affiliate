@extends('layouts.admin-v3')

@section('title', $pageTitle)

@push('styles')
<style>
    /* 🎮 RPG card hover lift */
    .rpg-card {
        transition: transform 220ms cubic-bezier(.2,.9,.2,1), box-shadow 220ms ease;
        will-change: transform;
    }
    .rpg-card:hover {
        transform: translateY(-6px) scale(1.015);
    }

    /* ✨ Hero animated orbs */
    .orb {
        position: absolute;
        border-radius: 9999px;
        filter: blur(48px);
        opacity: 0.55;
        pointer-events: none;
        animation: orb-float 9s ease-in-out infinite;
    }
    .orb-1 { top: -40px; right: -40px; width: 280px; height: 280px; background: #f472b6; }
    .orb-2 { bottom: -60px; left: 10%; width: 240px; height: 240px; background: #818cf8; animation-delay: -3s; }
    .orb-3 { top: 30%; left: 45%; width: 160px; height: 160px; background: #fbbf24; animation-delay: -6s; opacity: .4; }
    @keyframes orb-float {
        0%, 100% { transform: translate(0,0) scale(1); }
        50%      { transform: translate(20px,-30px) scale(1.08); }
    }

    /* 💎 Rarity shimmer on legendary/epic */
    @keyframes shimmer {
        0%   { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .shimmer-text {
        background: linear-gradient(110deg, currentColor 0%, #fff 45%, currentColor 60%);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 3.2s linear infinite;
    }

    /* 🎯 XP bar stripes */
    .xp-stripes {
        background-image: linear-gradient(45deg,
            rgba(255,255,255,.15) 25%, transparent 25%,
            transparent 50%, rgba(255,255,255,.15) 50%,
            rgba(255,255,255,.15) 75%, transparent 75%, transparent);
        background-size: 14px 14px;
        animation: xp-stripes-move 2s linear infinite;
    }
    @keyframes xp-stripes-move {
        from { background-position: 0 0; }
        to   { background-position: 28px 0; }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6 lg:py-8 space-y-6">

    {{-- 🎨 PREMIUM HERO HEADER --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 via-fuchsia-600 to-rose-500 text-white shadow-2xl">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <div class="relative z-10 px-6 py-8 lg:px-10 lg:py-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm text-3xl shadow-lg">🎴</span>
                        <h1 class="text-2xl lg:text-4xl font-extrabold tracking-tight drop-shadow">
                            Persona Codex
                        </h1>
                    </div>
                    <p class="text-white/90 text-sm lg:text-base max-w-2xl">
                        ระบบจดจำบุคลิก/นิสัยลูกค้าระยะยาว — แต่ละการ์ดคือ "ตัวละคร" ที่บอทเรียนรู้สะสมไว้ใช้ปรับ tone การคุย
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.fortune.dashboard') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-sm text-white text-sm font-medium transition border border-white/20">
                        <span>📊</span> Dashboard
                    </a>
                    <a href="{{ route('admin.fortune.users.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-sm text-white text-sm font-medium transition border border-white/20">
                        <span>👥</span> ผู้ใช้ดูดวง
                    </a>
                </div>
            </div>

            {{-- Hero Stats Row --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mt-8">
                <div class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 p-4 lg:p-5">
                    <div class="text-3xl lg:text-4xl font-extrabold">{{ number_format($stats['total']) }}</div>
                    <div class="text-white/80 text-xs lg:text-sm mt-1">ตัวละครทั้งหมด</div>
                </div>
                <div class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 p-4 lg:p-5">
                    <div class="text-3xl lg:text-4xl font-extrabold">{{ number_format($stats['today']) }}</div>
                    <div class="text-white/80 text-xs lg:text-sm mt-1">เกิดใหม่วันนี้</div>
                </div>
                <div class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 p-4 lg:p-5">
                    <div class="text-3xl lg:text-4xl font-extrabold">{{ number_format($stats['active_week']) }}</div>
                    <div class="text-white/80 text-xs lg:text-sm mt-1">Active ใน 7 วัน</div>
                </div>
                <div class="rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 p-4 lg:p-5">
                    <div class="text-3xl lg:text-4xl font-extrabold">{{ number_format($stats['avg_observations']) }}</div>
                    <div class="text-white/80 text-xs lg:text-sm mt-1">เฉลี่ย observations/คน</div>
                </div>
            </div>

            {{-- Rarity Distribution Bar --}}
            @php
                $rarTotal = max(1, array_sum($stats['rarity_dist']));
                $rarOrder = ['legendary' => ['👑 ตำนาน', 'from-amber-400 to-orange-500'],
                             'epic'      => ['💜 มหากาฬ', 'from-purple-500 to-pink-500'],
                             'rare'      => ['💎 หายาก', 'from-sky-500 to-blue-500'],
                             'common'    => ['🌿 ทั่วไป', 'from-slate-400 to-zinc-500']];
            @endphp
            <div class="mt-6">
                <div class="text-white/80 text-xs mb-2">การกระจาย Rarity (จาก {{ number_format(min($stats['total'], 500)) }} ตัวล่าสุด)</div>
                <div class="flex h-3 rounded-full overflow-hidden bg-white/10">
                    @foreach($rarOrder as $key => [$label, $grad])
                        @php $pct = ($stats['rarity_dist'][$key] / $rarTotal) * 100; @endphp
                        @if($pct > 0)
                            <div class="h-full bg-gradient-to-r {{ $grad }}"
                                 style="width: {{ $pct }}%"
                                 title="{{ $label }}: {{ $stats['rarity_dist'][$key] }}"></div>
                        @endif
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-3 mt-2 text-xs">
                    @foreach($rarOrder as $key => [$label, $grad])
                        <span class="inline-flex items-center gap-1.5 text-white/85">
                            <span class="inline-block w-2 h-2 rounded-full bg-gradient-to-r {{ $grad }}"></span>
                            {{ $label }}: {{ $stats['rarity_dist'][$key] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- 🔍 FILTER CARD (Glassmorphism) --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/60 dark:border-gray-700/60 p-5 lg:p-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                {{-- Search --}}
                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">🔍 ค้นหา</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           placeholder="ชื่อ / User ID / topic tag..."
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/60 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition">
                </div>

                {{-- Platform --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">📱 ช่องทาง</label>
                    <select name="platform" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/60 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-violet-500">
                        <option value="">ทั้งหมด</option>
                        <option value="facebook" {{ $filters['platform'] === 'facebook' ? 'selected' : '' }}>📘 Facebook</option>
                        <option value="line" {{ $filters['platform'] === 'line' ? 'selected' : '' }}>🟢 LINE</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wide">↕ เรียง</label>
                    <select name="sort" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/60 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-violet-500">
                        <option value="recent" {{ $filters['sort'] === 'recent' ? 'selected' : '' }}>ล่าสุดก่อน</option>
                        <option value="most_observed" {{ $filters['sort'] === 'most_observed' ? 'selected' : '' }}>คุยเยอะสุด</option>
                        <option value="oldest" {{ $filters['sort'] === 'oldest' ? 'selected' : '' }}>เก่าก่อน</option>
                    </select>
                </div>

                {{-- Submit + Reset --}}
                <div class="md:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-700 hover:to-fuchsia-700 text-white font-medium shadow-md hover:shadow-lg transition">
                        ค้นหา
                    </button>
                    <a href="{{ route('admin.fortune.personas.index') }}"
                       class="px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition text-sm">
                        ↺
                    </a>
                </div>
            </div>

            {{-- Rarity chips --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wide">Rarity:</span>
                @php
                    $rarChips = [
                        ['key' => '', 'label' => 'ทั้งหมด', 'cls' => 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'],
                        ['key' => 'legendary', 'label' => '👑 ตำนาน', 'cls' => 'bg-gradient-to-r from-amber-100 to-orange-100 hover:from-amber-200 hover:to-orange-200 dark:from-amber-900/40 dark:to-orange-900/40 text-amber-700 dark:text-amber-300 border border-amber-300/50 dark:border-amber-700'],
                        ['key' => 'epic', 'label' => '💜 มหากาฬ', 'cls' => 'bg-gradient-to-r from-purple-100 to-pink-100 hover:from-purple-200 hover:to-pink-200 dark:from-purple-900/40 dark:to-pink-900/40 text-purple-700 dark:text-purple-300 border border-purple-300/50 dark:border-purple-700'],
                        ['key' => 'rare', 'label' => '💎 หายาก', 'cls' => 'bg-gradient-to-r from-sky-100 to-blue-100 hover:from-sky-200 hover:to-blue-200 dark:from-sky-900/40 dark:to-blue-900/40 text-sky-700 dark:text-sky-300 border border-sky-300/50 dark:border-sky-700'],
                        ['key' => 'common', 'label' => '🌿 ทั่วไป', 'cls' => 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600'],
                    ];
                @endphp
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
                       class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition {{ $chip['cls'] }} {{ $isActive ? 'ring-2 ring-violet-500 ring-offset-2 dark:ring-offset-gray-800' : '' }}">
                        {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Top tag chips --}}
            @if(! empty($topTags))
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wide">🏷️ Tag ยอดฮิต:</span>
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
                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs transition
                                  {{ $isTagActive
                                     ? 'bg-violet-600 text-white shadow-md'
                                     : 'bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:hover:bg-violet-900/50 text-violet-700 dark:text-violet-300' }}">
                            #{{ $tag }}
                            <span class="opacity-70">×{{ $cnt }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </form>
    </div>

    {{-- 🎴 RPG CARD GRID --}}
    @if($personas->isEmpty())
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-12 text-center">
            <div class="text-6xl mb-4 opacity-50">🎴</div>
            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มี Persona</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                ระบบจะเริ่มสร้าง persona อัตโนมัติเมื่อมีลูกค้าคุยกับบอท — รอประมาณ 30 นาทีหลังคุย
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($personas as $p)
                @php
                    $rarity = $p->getRarityConfig();
                    $plat = $p->getPlatformConfig();
                    $level = $p->getLevel();
                    $xp = $p->getXpProgress();
                    $traitsTop = array_slice($p->traits ?? [], -3);
                @endphp

                <a href="{{ route('admin.fortune.personas.show', $p->id) }}"
                   class="rpg-card block relative rounded-2xl overflow-hidden bg-white dark:bg-gray-800 border-2 {{ $rarity['border'] }} {{ $rarity['glow'] }}">

                    {{-- Rarity gradient stripe (top) --}}
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r {{ $rarity['gradient'] }}"></div>

                    {{-- Rarity badge (top-right corner) --}}
                    <div class="absolute top-3 right-3 z-10">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gradient-to-r {{ $rarity['gradient'] }} text-white shadow-md">
                            <span>{{ $rarity['icon'] }}</span>
                            <span>{{ $rarity['label'] }}</span>
                        </span>
                    </div>

                    <div class="p-5 pt-7">
                        {{-- Avatar + name --}}
                        <div class="flex items-center gap-3 mb-4">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br {{ $rarity['gradient'] }} flex items-center justify-center text-white text-xl font-extrabold shadow-lg {{ $rarity['ring'] }}">
                                    {{ $p->initials }}
                                </div>
                                {{-- Platform mini badge --}}
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-xs shadow">
                                    {{ $plat['icon'] }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-900 dark:text-white truncate {{ $p->getRarity() === 'legendary' ? 'shimmer-text ' . $rarity['text'] : '' }}">
                                    {{ $p->display_name ?: 'ไม่ระบุชื่อ' }}
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 font-mono truncate">
                                    {{ Str::limit($p->platform_user_id, 18) }}
                                </div>
                            </div>
                        </div>

                        {{-- Level + XP bar --}}
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="inline-flex items-center gap-1 text-xs font-bold {{ $rarity['text'] }}">
                                    <span class="text-base">⚡</span>
                                    Lv.{{ $level }}
                                </span>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $p->observation_count ?? 0 }} obs · XP {{ $xp }}%
                                </span>
                            </div>
                            <div class="relative h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-gradient-to-r {{ $rarity['gradient'] }} xp-stripes transition-all"
                                     style="width: {{ $xp }}%"></div>
                            </div>
                        </div>

                        {{-- Top traits --}}
                        @if(! empty($traitsTop))
                            <div class="flex flex-wrap gap-1.5 mb-4 min-h-[28px]">
                                @foreach($traitsTop as $trait)
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[11px] {{ $rarity['bg_soft'] }} {{ $rarity['text'] }} border border-current/10">
                                        {{ Str::limit($trait, 16) }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-[11px] text-gray-400 dark:text-gray-500 italic mb-4 min-h-[28px]">
                                ยังไม่มีข้อมูลบุคลิก
                            </div>
                        @endif

                        {{-- Footer stats --}}
                        <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <span class="inline-flex items-center gap-1" title="หัวข้อที่เคยคุย">
                                💬 {{ count($p->conversation_themes ?? []) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="สิ่งที่ชอบ">
                                ❤️ {{ count($p->likes ?? []) }}
                            </span>
                            <span class="inline-flex items-center gap-1" title="แท็ก">
                                🏷️ {{ count($p->topic_tags_array) }}
                            </span>
                            <span class="text-right" title="คุยล่าสุด">
                                {{ $p->last_observed_at?->diffForHumans(short: true) ?? '-' }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-2">
            {{ $personas->links() }}
        </div>
    @endif

</div>
@endsection

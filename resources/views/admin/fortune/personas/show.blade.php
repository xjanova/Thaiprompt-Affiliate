@extends('layouts.admin-v3')

@section('title', $pageTitle)

@push('styles')
<style>
    /* ✨ Hero animated orbs */
    .orb {
        position: absolute;
        border-radius: 9999px;
        filter: blur(60px);
        opacity: 0.5;
        pointer-events: none;
        animation: orb-float 10s ease-in-out infinite;
    }
    .orb-1 { top: -60px; right: -30px; width: 320px; height: 320px; }
    .orb-2 { bottom: -80px; left: 5%; width: 280px; height: 280px; animation-delay: -3.5s; }
    @keyframes orb-float {
        0%, 100% { transform: translate(0,0) scale(1); }
        50%      { transform: translate(20px,-25px) scale(1.05); }
    }

    /* 💎 Shimmer (legendary) */
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
        animation: shimmer 3.5s linear infinite;
    }

    /* 🎯 XP bar stripes */
    .xp-stripes {
        background-image: linear-gradient(45deg,
            rgba(255,255,255,.18) 25%, transparent 25%,
            transparent 50%, rgba(255,255,255,.18) 50%,
            rgba(255,255,255,.18) 75%, transparent 75%, transparent);
        background-size: 16px 16px;
        animation: xp-stripes-move 2.2s linear infinite;
    }
    @keyframes xp-stripes-move {
        from { background-position: 0 0; }
        to   { background-position: 32px 0; }
    }

    /* 📊 Stat bar fill anim */
    .stat-fill {
        animation: stat-fill-in 1.1s cubic-bezier(.2,.9,.2,1) both;
    }
    @keyframes stat-fill-in {
        from { width: 0; }
    }

    /* 🎴 Section card hover */
    .sheet-card {
        transition: transform 200ms ease, box-shadow 200ms ease;
    }
    .sheet-card:hover {
        transform: translateY(-2px);
    }

    /* 🔍 Markdown preview */
    .md-preview {
        font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, monospace;
        font-size: 12px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
@php
    $isLegendary = $persona->getRarity() === 'legendary';
@endphp
<div class="container mx-auto px-4 py-6 lg:py-8 space-y-6">

    {{-- 🔙 Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('admin.fortune.personas.index') }}"
           class="inline-flex items-center gap-1.5 text-violet-600 dark:text-violet-400 hover:underline">
            <span>←</span> กลับไปรายการ Persona
        </a>
    </div>

    {{-- 🎴 HERO CHARACTER HEADER --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br {{ $rarity['gradient'] }} text-white shadow-2xl {{ $rarity['glow'] }}">
        <div class="orb orb-1" style="background: rgba(255,255,255,0.35);"></div>
        <div class="orb orb-2" style="background: rgba(0,0,0,0.25);"></div>

        <div class="relative z-10 px-6 py-7 lg:px-10 lg:py-9">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                {{-- Avatar (large) --}}
                <div class="relative flex-shrink-0 mx-auto lg:mx-0">
                    <div class="w-28 h-28 lg:w-32 lg:h-32 rounded-3xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-5xl lg:text-6xl font-black shadow-2xl ring-4 ring-white/30">
                        {{ $persona->initials }}
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-12 h-12 rounded-2xl bg-white dark:bg-gray-900 flex items-center justify-center text-2xl shadow-lg ring-2 ring-white/50">
                        {{ $platform['icon'] }}
                    </div>
                </div>

                {{-- Name + meta --}}
                <div class="flex-1 text-center lg:text-left">
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 mb-2">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest bg-white/25 backdrop-blur-sm border border-white/30 shadow">
                            {{ $rarity['icon'] }} {{ $rarity['label'] }} ({{ $rarity['label_en'] }})
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-black/25 backdrop-blur-sm border border-white/20">
                            ⚡ Lv. {{ $level }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{ $platform['color'] }}">
                            {{ $platform['icon'] }} {{ $platform['label'] }}
                        </span>
                    </div>
                    <h1 class="text-3xl lg:text-5xl font-extrabold drop-shadow {{ $isLegendary ? 'shimmer-text text-white' : '' }}">
                        {{ $persona->display_name ?: 'ตัวละครไม่ระบุชื่อ' }}
                    </h1>
                    <div class="mt-2 text-white/85 text-sm font-mono break-all">
                        ID: {{ $persona->platform_user_id }}
                    </div>

                    {{-- XP bar --}}
                    <div class="mt-5 max-w-xl mx-auto lg:mx-0">
                        <div class="flex items-center justify-between text-xs text-white/90 mb-1.5">
                            <span class="font-bold">EXP — Lv.{{ $level }} → Lv.{{ $level + 1 }}</span>
                            <span>{{ $xpProgress }}% · {{ $persona->observation_count ?? 0 }} observations</span>
                        </div>
                        <div class="relative h-3 rounded-full bg-black/30 overflow-hidden ring-1 ring-white/20">
                            <div class="absolute inset-y-0 left-0 bg-white xp-stripes transition-all"
                                 style="width: {{ $xpProgress }}%; mix-blend-mode: overlay; opacity: 0.85;"></div>
                            <div class="absolute inset-y-0 left-0 bg-white/60"
                                 style="width: {{ $xpProgress }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick stats bar --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mt-7">
                <div class="rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 px-4 py-3">
                    <div class="text-2xl font-extrabold">{{ number_format($persona->observation_count ?? 0) }}</div>
                    <div class="text-white/80 text-xs">Observations</div>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 px-4 py-3">
                    <div class="text-2xl font-extrabold">{{ $completeness }}%</div>
                    <div class="text-white/80 text-xs">ข้อมูลครบ</div>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 px-4 py-3">
                    <div class="text-base font-bold">{{ $persona->created_at?->format('d/m/Y') ?? '-' }}</div>
                    <div class="text-white/80 text-xs">เจอครั้งแรก</div>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 px-4 py-3">
                    <div class="text-base font-bold">{{ $persona->last_observed_at?->diffForHumans() ?? '-' }}</div>
                    <div class="text-white/80 text-xs">คุยล่าสุด</div>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 px-4 py-3">
                    <div class="text-base font-bold">฿{{ number_format($readingStats['total_spent'], 0) }}</div>
                    <div class="text-white/80 text-xs">จ่ายดูดวงรวม</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ⚡ MAIN GRID — Radar Chart + Stat Bars + Right Column --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- 📡 RADAR CHART (Communication Style) --}}
        <div class="sheet-card lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2">
                    <span>📡</span> สไตล์การสื่อสาร (Radar)
                </h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">6 แกน · 0-100</span>
            </div>
            <div class="relative" style="height: 360px;">
                <canvas id="radarChart"></canvas>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-4 text-xs">
                @foreach($radarStats as $axis)
                    <div class="text-center px-2 py-2 rounded-lg bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:from-violet-900/20 dark:to-fuchsia-900/20 border border-violet-100 dark:border-violet-800/40">
                        <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $axis['axis'] }}</div>
                        <div class="text-lg font-bold text-violet-700 dark:text-violet-300">{{ $axis['value'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 📊 STAT BARS --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-5">
                <span>📊</span> สรุปข้อมูล
            </h2>
            <div class="space-y-4">
                @foreach($statBars as $key => $bar)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $bar['label'] }}</span>
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $bar['count'] }}/{{ $bar['cap'] }}</span>
                        </div>
                        <div class="relative h-3 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="stat-fill absolute inset-y-0 left-0 bg-gradient-to-r {{ $bar['color'] }} rounded-full"
                                 style="width: {{ $bar['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Data Completeness donut --}}
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700 text-center">
                <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">ความครบของข้อมูล</div>
                <div class="relative inline-flex items-center justify-center">
                    <svg width="120" height="120" class="transform -rotate-90">
                        <circle cx="60" cy="60" r="50" stroke="currentColor" stroke-width="10" fill="none" class="text-gray-200 dark:text-gray-700" />
                        <circle cx="60" cy="60" r="50" stroke="url(#completeGrad)" stroke-width="10" fill="none"
                                stroke-dasharray="{{ 2 * M_PI * 50 }}"
                                stroke-dashoffset="{{ 2 * M_PI * 50 * (1 - $completeness / 100) }}"
                                stroke-linecap="round" />
                        <defs>
                            <linearGradient id="completeGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#a855f7" />
                                <stop offset="100%" stop-color="#ec4899" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $completeness }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 👥 DEMOGRAPHICS + COMMUNICATION STYLE --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Demographics --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>👥</span> Demographics
            </h2>
            @php $demo = $persona->demographics ?? []; @endphp
            @if(empty($demo))
                <div class="text-sm text-gray-400 dark:text-gray-500 italic py-8 text-center">ยังไม่มีข้อมูล</div>
            @else
                <div class="grid grid-cols-2 gap-3">
                    @foreach($demo as $k => $v)
                        @if($v && $v !== 'unknown')
                            <div class="rounded-xl bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:from-violet-900/20 dark:to-fuchsia-900/20 border border-violet-100 dark:border-violet-800/40 p-3">
                                @php
                                    $iconMap = ['age_range' => '🎂', 'gender_hint' => '⚧', 'job_hint' => '💼', 'location_hint' => '📍'];
                                    $labelMap = ['age_range' => 'อายุ', 'gender_hint' => 'เพศ', 'job_hint' => 'งาน', 'location_hint' => 'ที่อยู่'];
                                @endphp
                                <div class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ $iconMap[$k] ?? '•' }} {{ $labelMap[$k] ?? $k }}
                                </div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $v }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Communication style --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>🗣️</span> Communication Style
            </h2>
            @php $style = $persona->communication_style ?? []; @endphp
            @if(empty($style))
                <div class="text-sm text-gray-400 dark:text-gray-500 italic py-8 text-center">ยังไม่มีข้อมูล</div>
            @else
                <div class="space-y-2">
                    @foreach($style as $k => $v)
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <span class="text-sm text-gray-600 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $k) }}</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">{{ $v }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- 🎭 TRAITS + ❤️ LIKES + ❌ DISLIKES + 💬 THEMES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Traits --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>🎭</span> บุคลิก (Traits)
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($persona->traits ?? []) }} รายการ</span>
            </h2>
            @if(empty($persona->traits))
                <div class="text-sm text-gray-400 dark:text-gray-500 italic py-6 text-center">ยังไม่มีข้อมูล</div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($persona->traits as $t)
                        <span class="inline-block px-3 py-1.5 rounded-lg text-sm bg-gradient-to-r from-rose-100 to-pink-100 dark:from-rose-900/30 dark:to-pink-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40">
                            {{ $t }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Conversation Themes --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>💬</span> หัวข้อที่คุย
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($persona->conversation_themes ?? []) }} รายการ</span>
            </h2>
            @if(empty($persona->conversation_themes))
                <div class="text-sm text-gray-400 dark:text-gray-500 italic py-6 text-center">ยังไม่มีข้อมูล</div>
            @else
                <div class="space-y-2">
                    @foreach(array_reverse($persona->conversation_themes) as $i => $theme)
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-sky-500 to-blue-500 text-white text-xs font-bold flex items-center justify-center">
                                {{ $i + 1 }}
                            </div>
                            <div class="flex-1 text-sm text-gray-700 dark:text-gray-300 pt-0.5">{{ $theme }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Likes --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>❤️</span> ชอบ
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($persona->likes ?? []) }} รายการ</span>
            </h2>
            @if(empty($persona->likes))
                <div class="text-sm text-gray-400 dark:text-gray-500 italic py-6 text-center">ยังไม่มีข้อมูล</div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($persona->likes as $like)
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                            <span class="text-emerald-500">+</span>{{ $like }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Dislikes --}}
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>❌</span> ไม่ชอบ
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($persona->dislikes ?? []) }} รายการ</span>
            </h2>
            @if(empty($persona->dislikes))
                <div class="text-sm text-gray-400 dark:text-gray-500 italic py-6 text-center">ยังไม่มีข้อมูล</div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($persona->dislikes as $dl)
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/40">
                            <span class="text-red-500">−</span>{{ $dl }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- 🏷️ TOPIC TAGS --}}
    @if(! empty($persona->topic_tags_array))
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>🏷️</span> Topic Tags (Obsidian-style)
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($persona->topic_tags_array) }} แท็ก</span>
            </h2>
            <div class="flex flex-wrap gap-2">
                @foreach($persona->topic_tags_array as $tag)
                    <a href="{{ route('admin.fortune.personas.index', ['tag' => $tag]) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/40 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition">
                        #{{ $tag }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 📜 LINKED READINGS --}}
    @if($readings->isNotEmpty())
        <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2 mb-4">
                <span>📜</span> ประวัติการดูดวง (10 ล่าสุด)
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $readingStats['paid'] }}/{{ $readingStats['total'] }} จ่ายแล้ว</span>
            </h2>
            <div class="overflow-x-auto -mx-2">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="px-3 py-2">#</th>
                            <th class="px-3 py-2">ประเภท</th>
                            <th class="px-3 py-2">สถานะ</th>
                            <th class="px-3 py-2 text-right">ราคา</th>
                            <th class="px-3 py-2">เมื่อ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($readings as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-3 py-2 font-mono text-xs text-gray-500">{{ $r->id }}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $typeBadge = match($r->reading_type) {
                                            'deep' => ['🌟 เชิงลึก', 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                                            'celtic_cross' => ['🃏 Celtic', 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
                                            default => ['🔮 พื้นฐาน', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeBadge[1] }}">{{ $typeBadge[0] }}</span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{{ $r->conversation_status }}</td>
                                <td class="px-3 py-2 text-right font-mono text-xs {{ $r->is_paid ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                    @if($r->is_paid)
                                        ฿{{ number_format($r->amount_paid, 0) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $r->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- 📝 MARKDOWN EXPORT --}}
    <div class="sheet-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6"
         x-data="{ open: false, copyOk: false }">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white inline-flex items-center gap-2">
                <span>📝</span> ObsidianX Markdown Export
            </h2>
            <div class="flex items-center gap-2">
                <button @click="navigator.clipboard.writeText(document.getElementById('mdContent').innerText); copyOk = true; setTimeout(()=>copyOk=false, 1800);"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:hover:bg-violet-900/50 text-violet-700 dark:text-violet-300 transition border border-violet-200 dark:border-violet-800/40">
                    <span x-show="!copyOk">📋 Copy</span>
                    <span x-show="copyOk" x-cloak class="text-emerald-600 dark:text-emerald-400">✓ คัดลอกแล้ว</span>
                </button>
                <a href="{{ route('admin.fortune.personas.export', $persona->id) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 transition border border-emerald-200 dark:border-emerald-800/40">
                    📥 Download .md
                </a>
                <button @click="open = !open" type="button"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition">
                    <span x-text="open ? '▲ ซ่อน' : '▼ แสดง'"></span>
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak x-transition>
            <pre id="mdContent" class="md-preview p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 max-h-[480px] overflow-auto">{{ $markdownExport }}</pre>
        </div>
        <div x-show="!open" class="text-sm text-gray-500 dark:text-gray-400">
            มี YAML frontmatter + tags พร้อม import เข้า ObsidianX vault
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radarLabels = @json(array_column($radarStats, 'axis'));
    const radarValues = @json(array_column($radarStats, 'value'));
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.10)' : 'rgba(0,0,0,0.08)';
    const tickColor = isDark ? '#94a3b8' : '#475569';

    new Chart(document.getElementById('radarChart'), {
        type: 'radar',
        data: {
            labels: radarLabels,
            datasets: [{
                label: 'คะแนน',
                data: radarValues,
                fill: true,
                backgroundColor: 'rgba(168, 85, 247, 0.25)',
                borderColor: '#a855f7',
                borderWidth: 2.5,
                pointBackgroundColor: '#ec4899',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    suggestedMin: 0,
                    suggestedMax: 100,
                    grid: { color: gridColor },
                    angleLines: { color: gridColor },
                    pointLabels: {
                        color: tickColor,
                        font: { size: 13, weight: '600', family: 'system-ui, -apple-system, sans-serif' }
                    },
                    ticks: {
                        color: tickColor,
                        backdropColor: 'transparent',
                        font: { size: 10 },
                        stepSize: 25,
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.r}/100`
                    }
                }
            }
        }
    });
});
</script>
@endpush

{{--
    Genealogy Nexus — ผังสายงาน MLM (V3 Redesign)

    ดีไซน์ใหม่ทั้งหน้า: canvas เต็มจอแบบ immersive
    - การ์ดสมาชิก: avatar + rank ring + PV chips + สถานะ pulse
    - ลากย้ายโหนด / แพน / ซูม (เมาส์+ทัช) ลื่นด้วย rAF tween
    - คลิกการ์ด → detail panel / ย่อ-ขยายสายงาน / re-root + breadcrumb
    - Minimap + stats HUD + ค้นหาสมาชิกแบบ combobox

    ใช้ API เดิม: admin.mlm.members.tree-data (ไม่แตะ backend)
    Renderer: public/assets/js/genealogy-nexus.js (vanilla JS)

    @author TP-Affiliate Team
    @version 4.0.0
--}}

@extends('layouts.admin-v3')

@section('title', 'ผังสายงาน MLM')

@push('styles')
<style>
    /* ============================================================
       Genealogy Nexus — โทนสีผ่าน CSS variables (รองรับ dark/light)
       ============================================================ */
    :root {
        --gnx-surface: #f1f5f9;
        --gnx-grid: rgba(100, 116, 139, .14);
        --gnx-card-bg: rgba(255, 255, 255, .88);
        --gnx-card-border: rgba(148, 163, 184, .45);
        --gnx-text: #0f172a;
        --gnx-text-dim: #64748b;
        --gnx-edge: rgba(100, 116, 139, .5);
        --gnx-edge-hot: #10b981;
        --gnx-glow: rgba(16, 185, 129, .35);
    }
    .dark {
        --gnx-surface: #070d1a;
        --gnx-grid: rgba(94, 234, 212, .07);
        --gnx-card-bg: rgba(15, 23, 42, .82);
        --gnx-card-border: rgba(94, 234, 212, .22);
        --gnx-text: #f1f5f9;
        --gnx-text-dim: #94a3b8;
        --gnx-edge: rgba(94, 234, 212, .35);
        --gnx-edge-hot: #34d399;
        --gnx-glow: rgba(52, 211, 153, .45);
    }

    /* ---------- Canvas ---------- */
    /* หมายเหตุ: ไม่ตั้ง position ที่นี่ — ใช้ absolute จาก Tailwind ของ #nexus-canvas
       (style ที่ push มาทีหลัง app.css จะ override utility ถ้าตั้งซ้ำ) */
    .gnx-container {
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: var(--gnx-surface);
        touch-action: none;
        user-select: none;
        cursor: grab;
        border-radius: 1rem;
    }
    .gnx-container.gnx-panning { cursor: grabbing; }
    .gnx-bg {
        position: absolute;
        inset: -200px;
        background-image:
            radial-gradient(ellipse 60% 50% at 30% 20%, var(--gnx-glow) 0%, transparent 60%),
            linear-gradient(var(--gnx-grid) 1px, transparent 1px),
            linear-gradient(90deg, var(--gnx-grid) 1px, transparent 1px);
        background-size: 100% 100%, 44px 44px, 44px 44px;
        opacity: .8;
        pointer-events: none;
    }
    .gnx-edges {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        overflow: visible;
        pointer-events: none;
    }
    .gnx-world {
        position: absolute;
        top: 0;
        left: 0;
        transform-origin: 0 0;
        will-change: transform;
    }

    /* ---------- เส้นเชื่อม ---------- */
    .gnx-edge {
        fill: none;
        stroke: var(--gnx-edge);
        stroke-width: 2;
        transition: stroke .25s;
    }
    .gnx-edge-hot {
        stroke: var(--gnx-edge-hot);
        stroke-width: 2.5;
        stroke-dasharray: 7 5;
        animation: gnx-flow 1s linear infinite;
        filter: drop-shadow(0 0 4px var(--gnx-glow));
    }
    .gnx-edge-ghost { stroke-dasharray: 4 6; opacity: .45; }
    @keyframes gnx-flow { to { stroke-dashoffset: -12; } }

    /* ---------- การ์ดสมาชิก ---------- */
    .gnx-node {
        position: absolute;
        top: 0;
        left: 0;
        will-change: transform;
        cursor: pointer;
        transition: opacity .25s, filter .25s;
    }
    .gnx-node.gnx-entering { opacity: 0; }
    .gnx-node.gnx-leaving { opacity: 0; pointer-events: none; }
    .gnx-node.gnx-dragging { z-index: 30; }
    .gnx-node.gnx-dragging .gnx-card {
        box-shadow: 0 18px 44px -8px var(--gnx-glow);
        transform: scale(1.04);
    }
    .gnx-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        height: 96px;
        border-radius: 16px;
        background: var(--gnx-card-bg);
        border: 1px solid var(--gnx-card-border);
        backdrop-filter: blur(10px);
        box-shadow: 0 6px 20px -6px rgba(2, 6, 23, .35);
        transition: box-shadow .25s, transform .25s, border-color .25s;
        overflow: hidden;
    }
    .gnx-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: linear-gradient(90deg, var(--rank, #10b981), transparent 80%);
        opacity: .9;
    }
    .gnx-node:hover .gnx-card {
        border-color: var(--rank, #10b981);
        box-shadow: 0 10px 30px -6px var(--gnx-glow);
    }
    .gnx-node.gnx-selected .gnx-card {
        border-color: var(--gnx-edge-hot);
        box-shadow: 0 0 0 2px var(--gnx-edge-hot), 0 14px 38px -8px var(--gnx-glow);
    }

    .gnx-avatar {
        flex-shrink: 0;
        width: 52px;
        height: 52px;
        border-radius: 9999px;
        padding: 2px;
        background: conic-gradient(from 210deg, var(--rank, #10b981), transparent 65%, var(--rank, #10b981));
    }
    .gnx-avatar img, .gnx-avatar .gnx-initial {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        border-radius: 9999px;
        object-fit: cover;
        background: linear-gradient(135deg, #334155, #0f172a);
        color: #fff;
        font-weight: 800;
        font-size: 20px;
    }
    .gnx-info { min-width: 0; flex: 1; }
    .gnx-name {
        font-weight: 700;
        font-size: 13.5px;
        color: var(--gnx-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .gnx-code {
        font-size: 11px;
        color: var(--gnx-text-dim);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .gnx-code i { font-style: normal; font-weight: 600; }
    .gnx-chips { display: flex; gap: 4px; margin-top: 5px; }
    .gnx-chip {
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
        padding: 4px 7px;
        border-radius: 8px;
        white-space: nowrap;
    }
    .gnx-chip-l { background: rgba(16, 185, 129, .16); color: #059669; }
    .gnx-chip-r { background: rgba(59, 130, 246, .16); color: #2563eb; }
    .dark .gnx-chip-l { color: #34d399; }
    .dark .gnx-chip-r { color: #60a5fa; }

    .gnx-status {
        position: absolute;
        top: 9px;
        right: 10px;
        width: 9px;
        height: 9px;
        border-radius: 9999px;
    }
    .gnx-status.is-active { background: #22c55e; box-shadow: 0 0 0 0 rgba(34,197,94,.6); animation: gnx-pulse 2s infinite; }
    .gnx-status.is-grace { background: #f59e0b; }
    .gnx-status.is-inactive { background: #ef4444; }
    @keyframes gnx-pulse {
        0% { box-shadow: 0 0 0 0 rgba(34,197,94,.55); }
        70% { box-shadow: 0 0 0 7px rgba(34,197,94,0); }
        100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }

    .gnx-toggle {
        position: absolute;
        left: 50%;
        bottom: -13px;
        transform: translateX(-50%);
        min-width: 26px;
        height: 26px;
        padding: 0 7px;
        border-radius: 9999px;
        border: 1.5px solid var(--gnx-edge-hot);
        background: var(--gnx-card-bg);
        color: var(--gnx-edge-hot);
        font-size: 12px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        backdrop-filter: blur(6px);
        transition: transform .2s, box-shadow .2s;
        z-index: 5;
    }
    .gnx-toggle:hover { transform: translateX(-50%) scale(1.15); box-shadow: 0 0 12px var(--gnx-glow); }
    .gnx-toggle-minus { font-size: 15px; line-height: 1; }

    /* ---------- ช่องว่าง binary (ghost slot) ---------- */
    .gnx-ghost { cursor: default; }
    .gnx-ghost-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        height: 96px;
        border-radius: 16px;
        border: 1.5px dashed var(--gnx-card-border);
        color: var(--gnx-text-dim);
        font-size: 11px;
        opacity: .65;
    }
    .gnx-ghost-plus { font-size: 18px; opacity: .7; }

    /* ---------- Loading / Minimap ---------- */
    .gnx-loading {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        background: color-mix(in srgb, var(--gnx-surface) 72%, transparent);
        backdrop-filter: blur(4px);
        color: var(--gnx-text-dim);
        font-size: 14px;
        z-index: 40;
    }
    .gnx-loading.hidden { display: none; }
    .gnx-loading .gnx-error { color: #f87171; font-weight: 600; }
    .gnx-orbit { position: relative; width: 54px; height: 54px; }
    .gnx-orbit span {
        position: absolute;
        inset: 0;
        border: 2.5px solid transparent;
        border-top-color: var(--gnx-edge-hot);
        border-radius: 9999px;
        animation: gnx-spin 1.1s linear infinite;
    }
    .gnx-orbit span:nth-child(2) { inset: 8px; animation-duration: .85s; animation-direction: reverse; opacity: .7; }
    .gnx-orbit span:nth-child(3) { inset: 16px; animation-duration: .65s; opacity: .45; }
    @keyframes gnx-spin { to { transform: rotate(360deg); } }

    .gnx-minimap {
        position: absolute;
        right: 14px;
        bottom: 14px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--gnx-surface) 80%, transparent);
        border: 1px solid var(--gnx-card-border);
        backdrop-filter: blur(8px);
        cursor: crosshair;
        z-index: 20;
    }
    @media (max-width: 640px) { .gnx-minimap { display: none; } }

    /* ---------- ความสูง canvas ---------- */
    .gnx-stage { height: calc(100vh - 240px); min-height: 540px; }
    @media (max-width: 768px) { .gnx-stage { height: calc(100vh - 200px); min-height: 460px; } }

    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-4"
     x-data="genealogyNexus()"
     x-init="init()">

    {{-- ============ Header แถบบาง + สลับโหมดมุมมอง ============ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v3m0 0a3 3 0 100 6 3 3 0 000-6zm-6 13a3 3 0 116 0m0 0a3 3 0 116 0M6 14v-1.5M18 14v-1.5"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                    ผังสายงาน <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 to-cyan-500">Nexus</span>
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">ลากย้ายโหนด · ซูม · คลิกดูข้อมูล · เจาะสายงานได้ทุกคน</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.mlm.genealogy.workflow') }}"
               class="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all font-medium">
                Workflow
            </a>
            <a href="{{ route('admin.mlm.genealogy.bloodline') }}"
               class="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all font-medium">
                ผังสายเลือด
            </a>
            <a href="{{ route('admin.mlm.members.index') }}"
               class="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all font-medium">
                รายชื่อสมาชิก
            </a>
        </div>
    </div>

    {{-- ============ แถบควบคุม (glass) ============ --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-3 flex flex-col md:flex-row gap-3 md:items-center relative z-30">

        {{-- ค้นหาสมาชิก (combobox) --}}
        <div class="relative flex-1 min-w-[220px]" @click.outside="searchOpen = false">
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text"
                   x-model="search"
                   @focus="searchOpen = true"
                   @input="searchOpen = true"
                   placeholder="ค้นหาสมาชิก… (ชื่อ หรือ รหัส)"
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">

            {{-- dropdown ผลค้นหา --}}
            <div x-show="searchOpen && filteredMembers.length"
                 x-cloak
                 x-transition.origin.top
                 class="absolute z-50 mt-2 w-full max-h-72 overflow-y-auto rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xl divide-y divide-gray-100 dark:divide-gray-700/60">
                <template x-for="m in filteredMembers.slice(0, 30)" :key="m.id">
                    <button type="button"
                            @click="pickMember(m)"
                            class="w-full px-4 py-2.5 flex items-center gap-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0"
                              x-text="(m.name || '?').charAt(0).toUpperCase()"></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="m.name"></span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="m.code"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        {{-- ประเภทผัง (segmented) --}}
        <div class="flex items-center bg-gray-100 dark:bg-gray-900/60 rounded-xl p-1 text-sm font-semibold">
            <button type="button"
                    @click="setTreeType('binary')"
                    :class="treeType === 'binary' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow' : 'text-gray-500 dark:text-gray-400'"
                    class="px-4 py-1.5 rounded-lg transition-all">Binary</button>
            <button type="button"
                    @click="setTreeType('unilevel')"
                    :class="treeType === 'unilevel' ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow' : 'text-gray-500 dark:text-gray-400'"
                    class="px-4 py-1.5 rounded-lg transition-all">Unilevel</button>
        </div>

        {{-- ความลึก --}}
        <select x-model="depth" @change="reload()"
                class="px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 transition-all">
            <option value="3">ลึก 3 ชั้น</option>
            <option value="5">ลึก 5 ชั้น</option>
            <option value="7">ลึก 7 ชั้น</option>
            <option value="10">ลึก 10 ชั้น</option>
        </select>

        {{-- ปุ่มจัดการมุมมอง --}}
        <div class="flex items-center gap-1.5">
            <button type="button" @click="nexus?.zoomBy(1.25)" title="ซูมเข้า"
                    class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-emerald-500 hover:border-emerald-400 transition-all font-bold">+</button>
            <button type="button" @click="nexus?.zoomBy(0.8)" title="ซูมออก"
                    class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-emerald-500 hover:border-emerald-400 transition-all font-bold">−</button>
            <button type="button" @click="nexus?.fitToScreen()" title="จัดให้พอดีจอ (หรือดับเบิลคลิกพื้นหลัง)"
                    class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-emerald-500 hover:border-emerald-400 transition-all flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V5a1 1 0 011-1h3m8 0h3a1 1 0 011 1v3m0 8v3a1 1 0 01-1 1h-3m-8 0H5a1 1 0 01-1-1v-3"/>
                </svg>
            </button>
            <button type="button" @click="nexus?.resetPositions()" title="รีเซ็ตตำแหน่งโหนดที่ลากย้าย"
                    class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-emerald-500 hover:border-emerald-400 transition-all flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0114-3m2 8a8 8 0 01-14 3"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ============ Canvas + Breadcrumb + HUD + Detail Panel ============ --}}
    <div class="relative gnx-stage rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

        {{-- breadcrumb สาย re-root (ลอยบน canvas) --}}
        <div class="absolute top-3 left-3 z-30 flex flex-wrap items-center gap-1.5 max-w-[70%]" x-show="trail.length || rootName" x-cloak>
            <template x-for="(t, i) in trail" :key="t.id">
                <button type="button"
                        @click="jumpTrail(i)"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:text-emerald-500 hover:border-emerald-400 transition-all flex items-center gap-1">
                    <span x-text="t.name"></span>
                    <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </template>
            <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-500/90 text-white shadow-lg shadow-emerald-500/30 backdrop-blur"
                  x-text="rootName"></span>
        </div>

        {{-- stats HUD --}}
        <div class="absolute top-3 right-3 z-30 flex items-center gap-1.5" x-show="stats.nodes" x-cloak>
            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                👥 <span x-text="stats.nodes"></span> คน
            </span>
            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                🌿 <span x-text="stats.depth"></span> ชั้น
            </span>
            <span class="hidden sm:inline-flex px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                ⚡ PV รวม <span class="ml-1 text-emerald-500 font-bold" x-text="formatPv(stats.totalPv)"></span>
            </span>
        </div>

        {{-- ตัว canvas --}}
        <div id="nexus-canvas" class="absolute inset-0"></div>

        {{-- empty state เมื่อยังไม่มีสมาชิกในระบบ --}}
        <div x-show="!members.length" x-cloak
             class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 text-gray-400 dark:text-gray-500">
            <svg class="w-14 h-14 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-sm font-medium">ยังไม่มีสมาชิก MLM ในระบบ</p>
        </div>

        {{-- ============ Detail Panel (slide-in ขวา) ============ --}}
        <div x-show="selected"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0"
             class="absolute top-0 right-0 bottom-0 w-full sm:w-[340px] z-40 p-3">
            <div class="h-full rounded-2xl bg-white/90 dark:bg-gray-900/90 backdrop-blur-2xl border border-gray-200 dark:border-gray-700 shadow-2xl flex flex-col overflow-hidden">

                {{-- หัว panel + ปุ่มปิด --}}
                <div class="relative p-5 pb-4"
                     :style="`background: linear-gradient(135deg, ${selected?.rank_color || '#10b981'}22, transparent 70%)`">
                    <button type="button" @click="selected = null"
                            class="absolute top-3 right-3 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full p-0.5 flex-shrink-0"
                             :style="`background: conic-gradient(from 210deg, ${selected?.rank_color || '#10b981'}, transparent 65%, ${selected?.rank_color || '#10b981'})`">
                            <template x-if="selected?.avatar_url">
                                <img :src="selected.avatar_url" class="w-full h-full rounded-full object-cover" alt="">
                            </template>
                            <template x-if="!selected?.avatar_url">
                                <span class="w-full h-full rounded-full bg-gradient-to-br from-slate-600 to-slate-900 text-white text-2xl font-extrabold flex items-center justify-center"
                                      x-text="(selected?.name || '?').charAt(0).toUpperCase()"></span>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white truncate" x-text="selected?.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="selected?.member_code"></p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span x-show="selected?.rank_name"
                                      class="px-2.5 py-0.5 rounded-full text-[11px] font-bold text-white shadow"
                                      :style="`background: ${selected?.rank_color || '#10b981'}`"
                                      x-text="selected?.rank_name"></span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                      :class="statusBadgeClass(selected)"
                                      x-text="statusLabel(selected)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- เนื้อหา scroll ได้ --}}
                <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-4">

                    {{-- กริดสถิติ PV --}}
                    <div class="grid grid-cols-2 gap-2.5">
                        <template x-for="card in statCards" :key="card.label">
                            <div class="rounded-xl p-3 bg-gray-50 dark:bg-gray-800/70 border border-gray-100 dark:border-gray-700/60">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium" x-text="card.label"></p>
                                <p class="text-lg font-extrabold mt-0.5" :class="card.color" x-text="card.value"></p>
                            </div>
                        </template>
                    </div>

                    {{-- ข้อมูลทั่วไป --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400 text-xs">อีเมล</span>
                            <span class="text-gray-900 dark:text-white font-medium text-xs truncate max-w-[180px]" x-text="selected?.email || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400 text-xs">สมัครเมื่อ</span>
                            <span class="text-gray-900 dark:text-white font-medium text-xs" x-text="selected?.joined_at || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-gray-500 dark:text-gray-400 text-xs">แนะนำตรง</span>
                            <span class="text-gray-900 dark:text-white font-bold text-xs"><span x-text="selected?.direct_referrals ?? 0"></span> คน</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-800"
                             x-show="selected?.retention_days_left !== null && selected?.retention_days_left !== undefined">
                            <span class="text-gray-500 dark:text-gray-400 text-xs">รักษายอดเหลือ</span>
                            <span class="font-bold text-xs"
                                  :class="(selected?.retention_days_left ?? 99) <= 3 ? 'text-red-500' : 'text-emerald-500'">
                                <span x-text="selected?.retention_days_left"></span> วัน
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ปุ่ม action --}}
                <div class="p-4 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                    <button type="button"
                            @click="drillDown()"
                            class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white text-sm font-bold shadow-lg shadow-emerald-500/30 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        เจาะดูสายงานคนนี้
                    </button>
                    <a :href="memberShowUrl(selected)"
                       class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-bold transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        โปรไฟล์สมาชิก
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ แถบช่วยเหลือสั้นๆ ============ --}}
    <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-1 text-xs text-gray-400 dark:text-gray-500 pb-2">
        <span>🖱️ ลากพื้นหลัง = เลื่อน</span>
        <span>🧲 ลากการ์ด = ย้ายโหนด</span>
        <span>🔍 scroll / บีบนิ้ว = ซูม</span>
        <span>👆 คลิกการ์ด = ดูข้อมูล</span>
        <span>➖ ปุ่มใต้การ์ด = ย่อ/ขยายสายงาน</span>
        <span>⛶ ดับเบิลคลิกพื้นหลัง = พอดีจอ</span>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/genealogy-nexus.js') }}?v=4.0.0"></script>
<script>
/**
 * Alpine component ควบคุมหน้า Genealogy Nexus
 * - จัดการ search/combobox, tree type, depth, detail panel, breadcrumb
 * - ตัว render จริงอยู่ใน GenealogyNexus (genealogy-nexus.js)
 */
function genealogyNexus() {
    return {
        // ข้อมูลสมาชิกสำหรับช่องค้นหา (มาจาก controller — 100 รายล่าสุด)
        members: @json($members->map(fn ($m) => [
            'id' => $m->id,
            'code' => $m->member_code,
            'name' => $m->user->name ?? 'ไม่ระบุชื่อ',
        ])->values()),

        nexus: null,
        search: '',
        searchOpen: false,
        treeType: 'binary',
        depth: '5',
        selected: null,
        stats: { nodes: 0, depth: 0, totalPv: 0 },
        trail: [],
        rootName: '',
        currentRootId: null,

        /** เริ่มต้น: สร้าง renderer + โหลดสมาชิกคนแรก */
        init() {
            this.nexus = new GenealogyNexus(document.getElementById('nexus-canvas'), {
                treeDataUrlTemplate: @json(route('admin.mlm.members.tree-data', ['member' => '__ID__'])).replace('__ID__', ':id'),
                treeType: this.treeType,
                depth: parseInt(this.depth),
                onSelect: (node) => {
                    // คัดเฉพาะ field ข้อมูล — ตัด reference ภายใน (parent/children/DOM) กัน Alpine proxy หนัก
                    const clean = {};
                    Object.keys(node).forEach((k) => {
                        if (!k.startsWith('_') && !['parent', 'children', 'slots', 'left', 'right'].includes(k)) {
                            clean[k] = node[k];
                        }
                    });
                    this.selected = clean;
                },
                onRootChange: (root, trail) => {
                    this.rootName = root.name || '';
                    this.currentRootId = root.id;
                    this.trail = trail.map((t) => ({ id: t.id, name: t.name }));
                },
                onStats: (s) => { this.stats = s; },
            });

            // โหลดคนแรกในลิสต์อัตโนมัติ
            if (this.members.length) {
                this.loadMember(this.members[0].id);
            }
        },

        /** รายชื่อที่กรองตามคำค้น */
        get filteredMembers() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.members;
            return this.members.filter((m) =>
                (m.name || '').toLowerCase().includes(q) || (m.code || '').toLowerCase().includes(q)
            );
        },

        /** การ์ดสถิติใน detail panel — สลับชุดตามประเภทผัง */
        get statCards() {
            const s = this.selected || {};
            const f = (v) => this.formatPv(v);
            if (this.treeType === 'binary') {
                return [
                    { label: 'PV สะสม', value: f(s.total_pv), color: 'text-emerald-500' },
                    { label: 'PV เดือนนี้', value: f(s.monthly_pv), color: 'text-teal-500' },
                    { label: 'PV ขาซ้าย', value: f(s.left_leg_pv), color: 'text-cyan-500' },
                    { label: 'PV ขาขวา', value: f(s.right_leg_pv), color: 'text-blue-500' },
                ];
            }
            return [
                { label: 'PV สะสม', value: f(s.total_pv), color: 'text-emerald-500' },
                { label: 'PV เดือนนี้', value: f(s.monthly_pv), color: 'text-teal-500' },
                { label: 'PV ทีมรวม', value: f(s.total_team_pv), color: 'text-cyan-500' },
                { label: 'รายได้รวม', value: '฿' + f(s.total_earnings), color: 'text-amber-500' },
            ];
        },

        loadMember(id, pushTrail = false) {
            this.selected = null;
            this.nexus.load(id, pushTrail);
        },

        pickMember(m) {
            this.search = `${m.code} — ${m.name}`;
            this.searchOpen = false;
            this.loadMember(m.id);
        },

        /** เจาะดูสายงานจากโหนดที่เลือก (re-root + เก็บ breadcrumb) */
        drillDown() {
            if (!this.selected || this.selected.id === this.currentRootId) {
                this.selected = null;
                return;
            }
            this.loadMember(this.selected.id, true);
        },

        /** กระโดดกลับตาม breadcrumb */
        jumpTrail(index) {
            const target = this.trail[index];
            if (!target) return;
            // ตัด trail ให้เหลือก่อนตำแหน่งที่กด แล้วโหลด root นั้น (คง trail ที่ตัดแล้วไว้)
            this.trail = this.trail.slice(0, index);
            this.nexus.trail = [...this.trail];
            this.selected = null;
            this.nexus.load(target.id, false, false);
        },

        setTreeType(type) {
            if (this.treeType === type) return;
            this.treeType = type;
            this.nexus.setTreeType(type);
            this.reload();
        },

        reload() {
            this.nexus.setDepth(parseInt(this.depth));
            if (this.currentRootId) this.loadMember(this.currentRootId);
        },

        memberShowUrl(node) {
            if (!node) return '#';
            return @json(route('admin.mlm.members.show', ['member' => '__ID__'])).replace('__ID__', node.id);
        },

        statusLabel(node) {
            const s = node?.retention_status || node?.status;
            if (s === 'active') return '● รักษายอด';
            if (s === 'grace_period') return '● ผ่อนผัน';
            return '● ไม่รักษายอด';
        },

        statusBadgeClass(node) {
            const s = node?.retention_status || node?.status;
            if (s === 'active') return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400';
            if (s === 'grace_period') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
            return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400';
        },

        formatPv(v) {
            const n = parseFloat(v) || 0;
            if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
            if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
            return n % 1 === 0 ? n.toLocaleString() : n.toFixed(1);
        },
    };
}
</script>
@endpush

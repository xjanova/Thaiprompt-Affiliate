@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
@php
    // 🎴 เตรียมตัวแปรช่วย (ใช้ทั้งหน้า) — คำนวณ rarity เพื่อเลือกสีไอคอน/พิลล์
    $rarityKey = $persona->getRarity();
    // 🎨 จับคู่ rarity → สีสถานะ V4 (ไม่ใช้สี tailwind จาก config เดิม เพื่อให้เข้าธีมนวลทองคำ)
    $rarityColor = match ($rarityKey) {
        'legendary' => 'var(--accent1)',  // ทอง
        'epic'      => '#b79ae8',          // ม่วง
        'rare'      => '#5689b8',          // ฟ้า
        default     => '#9a8f7c',          // กลาง
    };

    // 📡 เตรียมจุดสำหรับ radar/spider chart แบบ SVG (แทน Chart.js เดิม)
    // วาดเป็นรูปหลายเหลี่ยม (polygon) บนวงกลมรัศมี โดยกระจายแกนเท่า ๆ กัน
    $radarCount = max(1, count($radarStats));
    $radarCx = 160; $radarCy = 160; $radarR = 120;
    // มุมเริ่มต้นชี้ขึ้นบน (-90°) แล้วไล่ตามเข็มนาฬิกา
    $radarGridLevels = [0.25, 0.5, 0.75, 1.0];

    // ฟังก์ชันช่วยคำนวณพิกัด (x,y) จากดัชนีแกน + อัตราส่วนรัศมี (0-1)
    $radarPoint = function (int $i, float $ratio) use ($radarCount, $radarCx, $radarCy, $radarR) {
        $angle = (-90 + ($i * (360 / $radarCount))) * M_PI / 180;
        return [
            'x' => $radarCx + cos($angle) * $radarR * $ratio,
            'y' => $radarCy + sin($angle) * $radarR * $ratio,
        ];
    };

    // สร้างชุดจุดของรูปข้อมูลจริง (ค่า value 0-100 → ratio)
    $radarDataPoints = [];
    foreach (array_values($radarStats) as $i => $axis) {
        $ratio = max(0, min(100, (int) $axis['value'])) / 100;
        $radarDataPoints[] = $radarPoint($i, $ratio);
    }
    $radarDataPolygon = implode(' ', array_map(fn ($p) => round($p['x'], 1) . ',' . round($p['y'], 1), $radarDataPoints));
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ╔═══ HEADER ═══╗ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · บุคลิกลูกค้า (RPG)
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                {{ $persona->display_name ?: 'ตัวละครไม่ระบุชื่อ' }} 🔮
            </h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            @if(Route::has('admin.fortune.personas.index'))
                <a href="{{ route('admin.fortune.personas.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-arrow-left"></i> กลับรายการ
                </a>
            @endif
            <a href="{{ route('admin.fortune.personas.export', $persona->id) }}" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-download"></i> ดาวน์โหลด .md
            </a>
        </div>
    </div>

    {{-- ╔═══ CHARACTER CARD (Hero) ═══╗ --}}
    <div class="tp-card tp-raise" style="padding:22px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:22px;">
            {{-- 🎴 Avatar + แพลตฟอร์ม --}}
            <div style="position:relative; flex-shrink:0;">
                <div class="tp-tile" style="width:96px; height:96px; border-radius:24px; display:flex; align-items:center; justify-content:center; font-size:34px; font-weight:800; color:var(--deep1);">
                    {{ $persona->initials }}
                </div>
                <div class="tp-card" style="position:absolute; bottom:-8px; right:-8px; width:36px; height:36px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:var(--raise);">
                    {{ $platform['icon'] }}
                </div>
            </div>

            {{-- 📛 ชื่อ + พิลล์ rarity/level/platform --}}
            <div style="flex:1; min-width:230px;">
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                    {{-- พิลล์ rarity — สีตาม rarityColor --}}
                    <span class="tp-pill" style="background:{{ $rarityColor }}22; color:{{ $rarityColor }}; border-color:{{ $rarityColor }}55;">
                        {{ $rarity['icon'] }} {{ $rarity['label'] }} · {{ $rarity['label_en'] }}
                    </span>
                    <span class="tp-pill tp-pill-gold">⚡ Lv. {{ $level }}</span>
                    <span class="tp-pill tp-pill-soft">{{ $platform['icon'] }} {{ $platform['label'] }}</span>
                </div>
                <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--ink);">
                    {{ $persona->display_name ?: 'ตัวละครไม่ระบุชื่อ' }}
                </div>
                <div class="tp-muted" style="font-size:12px; margin-top:4px; word-break:break-all;">
                    <i class="fas fa-fingerprint"></i> ID: {{ $persona->platform_user_id }}
                </div>

                {{-- 🎯 XP bar — Lv ปัจจุบัน → ถัดไป --}}
                <div style="margin-top:14px; max-width:460px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--ink2); margin-bottom:6px;">
                        <span style="font-weight:700;">EXP — Lv.{{ $level }} → Lv.{{ $level + 1 }}</span>
                        <span class="tp-num">{{ $xpProgress }}% · {{ number_format($persona->observation_count ?? 0) }} ครั้ง</span>
                    </div>
                    <div class="tp-inset-sm" style="position:relative; height:12px; border-radius:999px; overflow:hidden;">
                        <div class="tp-xp-fill" style="position:absolute; inset-block:0; left:0; width:{{ $xpProgress }}%; border-radius:999px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 📊 KPI mini grid --}}
        <div class="tp-divider" style="margin:18px 0;"></div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
            @php
                $heroKpis = [
                    ['icon' => 'fa-eye',          'val' => number_format($persona->observation_count ?? 0), 'lbl' => 'จำนวนสังเกต'],
                    ['icon' => 'fa-database',     'val' => $completeness . '%',                              'lbl' => 'ข้อมูลครบ'],
                    ['icon' => 'fa-calendar-plus','val' => $persona->created_at?->format('d/m/Y') ?? '-',   'lbl' => 'เจอครั้งแรก'],
                    ['icon' => 'fa-clock',        'val' => $persona->last_observed_at?->diffForHumans() ?? '-', 'lbl' => 'คุยล่าสุด'],
                    ['icon' => 'fa-coins',        'val' => '฿' . number_format($readingStats['total_spent'], 0), 'lbl' => 'จ่ายดูดวงรวม'],
                ];
            @endphp
            @foreach($heroKpis as $kpi)
                <div class="tp-inset" style="padding:12px 14px; border-radius:14px;">
                    <div style="display:flex; align-items:center; gap:8px; color:var(--ink2); font-size:11px; margin-bottom:4px;">
                        <i class="fas {{ $kpi['icon'] }}" style="color:var(--accent1);"></i> {{ $kpi['lbl'] }}
                    </div>
                    <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--ink);">{{ $kpi['val'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ╔═══ RADAR (SVG) + STAT BARS ═══╗ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:18px;">

        {{-- 📡 RADAR / SPIDER CHART — SVG polygon (แทน Chart.js) --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="display:flex; align-items:center; justify-content:space-between;">
                <span><i class="fas fa-satellite-dish" style="color:var(--accent1);"></i> สไตล์การสื่อสาร (Radar)</span>
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ $radarCount }} แกน · 0–100</span>
            </div>

            <div style="display:flex; justify-content:center; padding:8px 0 4px;">
                <svg viewBox="0 0 320 320" width="100%" style="max-width:340px; height:auto;" role="img" aria-label="กราฟเรดาร์สไตล์การสื่อสาร">
                    {{-- เส้นกริดวงหลายเหลี่ยมซ้อนชั้น --}}
                    @foreach($radarGridLevels as $lvl)
                        @php
                            $gridPts = [];
                            for ($gi = 0; $gi < $radarCount; $gi++) {
                                $p = $radarPoint($gi, $lvl);
                                $gridPts[] = round($p['x'], 1) . ',' . round($p['y'], 1);
                            }
                        @endphp
                        <polygon points="{{ implode(' ', $gridPts) }}"
                                 fill="none" stroke="var(--ink2)" stroke-opacity="0.18" stroke-width="1" />
                    @endforeach

                    {{-- เส้นแกนจากจุดศูนย์กลาง --}}
                    @for($ai = 0; $ai < $radarCount; $ai++)
                        @php $edge = $radarPoint($ai, 1.0); @endphp
                        <line x1="{{ $radarCx }}" y1="{{ $radarCy }}"
                              x2="{{ round($edge['x'], 1) }}" y2="{{ round($edge['y'], 1) }}"
                              stroke="var(--ink2)" stroke-opacity="0.16" stroke-width="1" />
                    @endfor

                    {{-- รูปข้อมูลจริง (โปร่งแสงทอง) --}}
                    <polygon class="tp-radar-shape" points="{{ $radarDataPolygon }}"
                             fill="var(--accent1)" fill-opacity="0.22"
                             stroke="var(--accent1)" stroke-width="2.5" stroke-linejoin="round" />

                    {{-- จุดหัวมุมของข้อมูล + ป้ายชื่อแกน --}}
                    @foreach(array_values($radarStats) as $li => $axis)
                        @php
                            $dp = $radarDataPoints[$li];
                            $lp = $radarPoint($li, 1.16); // ป้ายชื่อนอกวงเล็กน้อย
                            // จัด anchor ข้อความตามตำแหน่งแนวนอน
                            $anchor = abs($lp['x'] - $radarCx) < 8 ? 'middle' : ($lp['x'] < $radarCx ? 'end' : 'start');
                        @endphp
                        <circle cx="{{ round($dp['x'], 1) }}" cy="{{ round($dp['y'], 1) }}" r="4"
                                fill="var(--accent2)" stroke="var(--surf)" stroke-width="2" />
                        <text x="{{ round($lp['x'], 1) }}" y="{{ round($lp['y'] + 3, 1) }}"
                              text-anchor="{{ $anchor }}" font-size="11" font-weight="600" fill="var(--ink2)">{{ $axis['axis'] }}</text>
                    @endforeach
                </svg>
            </div>

            {{-- รายการค่าแต่ละแกน --}}
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:6px;">
                @foreach($radarStats as $axis)
                    <div class="tp-inset-sm" style="text-align:center; padding:8px 6px; border-radius:12px;">
                        <div class="tp-muted" style="font-size:10px;">{{ $axis['axis'] }}</div>
                        <div class="tp-num" style="font-size:16px; font-weight:800; color:var(--accent1);">{{ $axis['value'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 📊 STAT BARS + donut ความครบ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h"><i class="fas fa-chart-simple" style="color:var(--accent1);"></i> สรุปข้อมูล</div>

            <div style="display:flex; flex-direction:column; gap:14px;">
                @foreach($statBars as $bar)
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px; margin-bottom:6px;">
                            <span style="font-weight:600; color:var(--ink);">{{ $bar['label'] }}</span>
                            <span class="tp-num tp-muted" style="font-size:11px;">{{ $bar['count'] }}/{{ $bar['cap'] }}</span>
                        </div>
                        <div class="tp-inset-sm" style="position:relative; height:12px; border-radius:999px; overflow:hidden;">
                            <div class="tp-stat-fill" style="position:absolute; inset-block:0; left:0; width:{{ $bar['percent'] }}%; border-radius:999px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- 🍩 Donut ความครบของข้อมูล (conic-gradient + mask) --}}
            <div class="tp-divider" style="margin:18px 0 14px;"></div>
            <div style="text-align:center;">
                <div class="tp-muted" style="font-size:11px; margin-bottom:10px;">ความครบของข้อมูล</div>
                <div style="position:relative; width:128px; height:128px; margin:0 auto; border-radius:50%;
                            background:conic-gradient(var(--accent1) 0% {{ $completeness }}%, var(--ink2) {{ $completeness }}% 100%);
                            -webkit-mask:radial-gradient(circle 64px at center, transparent 41px, #000 42px);
                            mask:radial-gradient(circle 64px at center, transparent 41px, #000 42px);">
                </div>
                <div style="position:relative; margin-top:-86px; height:44px;">
                    <span class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink);">{{ $completeness }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ╔═══ DEMOGRAPHICS + COMMUNICATION STYLE ═══╗ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:18px;">

        {{-- 👥 Demographics --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h"><i class="fas fa-users" style="color:var(--accent1);"></i> ข้อมูลประชากร</div>
            @php $demo = $persona->demographics ?? []; @endphp
            @php
                $demoIcon  = ['age_range' => '🎂', 'gender_hint' => '⚧', 'job_hint' => '💼', 'location_hint' => '📍'];
                $demoLabel = ['age_range' => 'อายุ', 'gender_hint' => 'เพศ', 'job_hint' => 'งาน', 'location_hint' => 'ที่อยู่'];
                $demoHas   = ! empty(array_filter($demo, fn ($v) => $v && $v !== 'unknown'));
            @endphp
            @if(! $demoHas)
                <div class="tp-muted" style="text-align:center; padding:26px 0; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:22px; display:block; margin-bottom:8px; opacity:.6;"></i>
                    ยังไม่มีข้อมูล
                </div>
            @else
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px;">
                    @foreach($demo as $k => $v)
                        @if($v && $v !== 'unknown')
                            <div class="tp-inset" style="padding:11px 13px; border-radius:14px;">
                                <div class="tp-muted" style="font-size:10px;">{{ $demoIcon[$k] ?? '•' }} {{ $demoLabel[$k] ?? $k }}</div>
                                <div style="font-size:14px; font-weight:700; color:var(--ink); margin-top:3px;">{{ $v }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 🗣️ Communication style --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h"><i class="fas fa-comment-dots" style="color:var(--accent1);"></i> สไตล์การสื่อสาร</div>
            @php $style = $persona->communication_style ?? []; @endphp
            @if(empty($style))
                <div class="tp-muted" style="text-align:center; padding:26px 0; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:22px; display:block; margin-bottom:8px; opacity:.6;"></i>
                    ยังไม่มีข้อมูล
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($style as $k => $v)
                        <div class="tp-inset-sm" style="display:flex; align-items:center; justify-content:space-between; padding:9px 13px; border-radius:12px;">
                            <span style="font-size:13px; color:var(--ink2); text-transform:capitalize;">{{ str_replace('_', ' ', $k) }}</span>
                            <span class="tp-pill tp-pill-soft" style="font-size:11px;">{{ $v }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ╔═══ TRAITS · THEMES · LIKES · DISLIKES ═══╗ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:18px;">

        {{-- 🎭 Traits --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h">
                <i class="fas fa-masks-theater" style="color:var(--accent1);"></i> บุคลิก (Traits)
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ count($persona->traits ?? []) }} รายการ</span>
            </div>
            @if(empty($persona->traits))
                <div class="tp-muted" style="text-align:center; padding:22px 0; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:20px; display:block; margin-bottom:6px; opacity:.6;"></i> ยังไม่มีข้อมูล
                </div>
            @else
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    @foreach($persona->traits as $t)
                        <span class="tp-pill" style="background:#d6824a22; color:#d6824a; border-color:#d6824a44;">{{ $t }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 💬 Conversation themes --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h">
                <i class="fas fa-comments" style="color:var(--accent1);"></i> หัวข้อที่คุย
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ count($persona->conversation_themes ?? []) }} รายการ</span>
            </div>
            @if(empty($persona->conversation_themes))
                <div class="tp-muted" style="text-align:center; padding:22px 0; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:20px; display:block; margin-bottom:6px; opacity:.6;"></i> ยังไม่มีข้อมูล
                </div>
            @else
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach(array_reverse($persona->conversation_themes) as $i => $theme)
                        <div style="display:flex; align-items:flex-start; gap:11px;">
                            <div class="tp-num" style="flex-shrink:0; width:24px; height:24px; border-radius:999px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:var(--deep1); font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center;">
                                {{ $i + 1 }}
                            </div>
                            <div style="flex:1; font-size:13px; color:var(--ink2); padding-top:3px;">{{ $theme }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ❤️ Likes --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h">
                <i class="fas fa-heart" style="color:#5aa07e;"></i> ชอบ
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ count($persona->likes ?? []) }} รายการ</span>
            </div>
            @if(empty($persona->likes))
                <div class="tp-muted" style="text-align:center; padding:22px 0; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:20px; display:block; margin-bottom:6px; opacity:.6;"></i> ยังไม่มีข้อมูล
                </div>
            @else
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    @foreach($persona->likes as $like)
                        <span class="tp-pill" style="background:#5aa07e22; color:#5aa07e; border-color:#5aa07e44;">
                            <i class="fas fa-plus" style="font-size:9px;"></i> {{ $like }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ❌ Dislikes --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h">
                <i class="fas fa-heart-crack" style="color:#d9534f;"></i> ไม่ชอบ
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ count($persona->dislikes ?? []) }} รายการ</span>
            </div>
            @if(empty($persona->dislikes))
                <div class="tp-muted" style="text-align:center; padding:22px 0; font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:20px; display:block; margin-bottom:6px; opacity:.6;"></i> ยังไม่มีข้อมูล
                </div>
            @else
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    @foreach($persona->dislikes as $dl)
                        <span class="tp-pill" style="background:#d9534f22; color:#d9534f; border-color:#d9534f44;">
                            <i class="fas fa-minus" style="font-size:9px;"></i> {{ $dl }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ╔═══ TOPIC TAGS ═══╗ --}}
    @if(! empty($persona->topic_tags_array))
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h">
                <i class="fas fa-tags" style="color:var(--accent1);"></i> Topic Tags (Obsidian-style)
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ count($persona->topic_tags_array) }} แท็ก</span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @foreach($persona->topic_tags_array as $tag)
                    @if(Route::has('admin.fortune.personas.index'))
                        <a href="{{ route('admin.fortune.personas.index', ['tag' => $tag]) }}" class="tp-pill tp-pill-gold" style="text-decoration:none;">
                            #{{ $tag }}
                        </a>
                    @else
                        <span class="tp-pill tp-pill-gold">#{{ $tag }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- ╔═══ LINKED READINGS ═══╗ --}}
    @if($readings->isNotEmpty())
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h">
                <i class="fas fa-scroll" style="color:var(--accent1);"></i> ประวัติการดูดวง (10 ล่าสุด)
                <span class="tp-muted" style="font-size:11px; font-weight:500;">{{ $readingStats['paid'] }}/{{ $readingStats['total'] }} จ่ายแล้ว</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:separate; border-spacing:0 6px; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); font-size:11px; text-transform:uppercase; letter-spacing:.4px;">
                            <th style="padding:6px 12px;">#</th>
                            <th style="padding:6px 12px;">ประเภท</th>
                            <th style="padding:6px 12px;">สถานะ</th>
                            <th style="padding:6px 12px; text-align:right;">ราคา</th>
                            <th style="padding:6px 12px;">เมื่อ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readings as $r)
                            @php
                                $typeMap = match($r->reading_type) {
                                    'deep'         => ['🌟 เชิงลึก', '#b79ae8'],
                                    'celtic_cross' => ['🃏 Celtic', 'var(--accent1)'],
                                    default        => ['🔮 พื้นฐาน', '#5689b8'],
                                };
                            @endphp
                            <tr>
                                <td class="tp-num" style="padding:10px 12px; box-shadow:var(--inset-sm); border-radius:10px 0 0 10px; color:var(--ink2); font-size:11px;">{{ $r->id }}</td>
                                <td style="padding:10px 12px; box-shadow:var(--inset-sm);">
                                    <span class="tp-pill" style="background:{{ $typeMap[1] }}22; color:{{ $typeMap[1] }}; border-color:{{ $typeMap[1] }}44; font-size:11px;">{{ $typeMap[0] }}</span>
                                </td>
                                <td style="padding:10px 12px; box-shadow:var(--inset-sm); color:var(--ink2); font-size:12px;">{{ $r->conversation_status }}</td>
                                <td class="tp-num" style="padding:10px 12px; box-shadow:var(--inset-sm); text-align:right; font-size:12px; color:{{ $r->is_paid ? '#5aa07e' : 'var(--ink2)' }};">
                                    @if($r->is_paid)
                                        ฿{{ number_format($r->amount_paid, 0) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="padding:10px 12px; box-shadow:var(--inset-sm); border-radius:0 10px 10px 0; color:var(--ink2); font-size:12px;">{{ $r->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ╔═══ MARKDOWN EXPORT ═══╗ --}}
    <div class="tp-card" style="padding:20px;" x-data="{ open: false, copyOk: false }">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h" style="margin:0;">
                <i class="fas fa-file-lines" style="color:var(--accent1);"></i> ObsidianX Markdown Export
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                {{-- 📋 ปุ่มคัดลอก — อ่าน innerText จาก #mdContent (logic เดิม) --}}
                <button type="button" class="tp-btn tp-btn-sm"
                        @click="navigator.clipboard.writeText(document.getElementById('mdContent').innerText); copyOk = true; $dispatch('notify', { type: 'success', message: 'คัดลอก Markdown แล้ว' }); setTimeout(() => copyOk = false, 1800);">
                    <span x-show="!copyOk"><i class="fas fa-copy"></i> คัดลอก</span>
                    <span x-show="copyOk" x-cloak style="color:#5aa07e;"><i class="fas fa-check"></i> คัดลอกแล้ว</span>
                </button>
                <a href="{{ route('admin.fortune.personas.export', $persona->id) }}" class="tp-btn tp-btn-primary tp-btn-sm">
                    <i class="fas fa-download"></i> .md
                </a>
                <button type="button" class="tp-icon-btn" @click="open = !open">
                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak x-transition>
            <pre id="mdContent" class="tp-inset tp-md-preview">{{ $markdownExport }}</pre>
        </div>
        <div x-show="!open" class="tp-muted" style="font-size:13px;">
            มี YAML frontmatter + tags พร้อม import เข้า ObsidianX vault
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- 🎨 CSS เฉพาะหน้า — โหลดท้าย body ผ่าน scripts stack (styles stack จาก section content จะหลุด ใช้ไม่ได้) --}}
<style>
    /* 🎯 อนิเมชันแถบ XP/Stat ค่อย ๆ เติม */
    @keyframes tp-fill-in { from { width: 0; } }
    .tp-xp-fill, .tp-stat-fill { animation: tp-fill-in 1.05s cubic-bezier(.2,.9,.2,1) both; }

    /* 📡 รูปเรดาร์ค่อย ๆ ปรากฏ */
    @keyframes tp-radar-in { from { opacity: 0; transform: scale(.85); } to { opacity: 1; transform: scale(1); } }
    .tp-radar-shape { transform-origin: 160px 160px; animation: tp-radar-in .7s cubic-bezier(.2,.9,.2,1) both; }

    /* 🔍 พรีวิว markdown — ฟอนต์ monospace อ่านง่าย */
    .tp-md-preview {
        font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, monospace;
        font-size: 12px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
        color: var(--ink);
        padding: 16px;
        border-radius: 14px;
        max-height: 480px;
        overflow: auto;
        margin: 0;
    }

    [x-cloak] { display: none !important; }
</style>
@endpush

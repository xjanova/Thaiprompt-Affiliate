@extends('layouts.admin-v4')

@section('title', 'RAG Admin Q&A')

@section('content')
{{-- ===== หน้า "RAG Admin Q&A — รายการ" ธีม V4 นวลทองคำ ===== --}}
{{-- container หลัก: เว้นระยะแนวตั้งสม่ำเสมอ --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header: ชื่อหน้า + คำอธิบาย ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · RAG Admin Q&amp;A
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                📚 RAG Admin Q&amp;A
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px; max-width:680px; line-height:1.55;">
                จับคู่ "คำถามลูกค้า ↔ คำตอบแอดมิน" จาก FB Page Inbox → AI เรียนสไตล์ตอบของแอดมินไว้ใช้ตอบลูกค้าเมื่อบริบทคล้ายกัน
            </p>
        </div>
    </div>

    {{-- ===== KPI สรุป (จาก $stats — รวมทุกหน้า) ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        {{-- การ์ด: ทั้งหมด --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto;">
                <i class="fas fa-database"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total']) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">คู่ Q&amp;A ทั้งหมด</div>
            </div>
        </div>
        {{-- การ์ด: เปิดใช้งาน --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5aa07e; box-shadow:var(--raise);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['active']) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เปิดใช้งาน</div>
            </div>
        </div>
        {{-- การ์ด: มี embedding --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5689b8; box-shadow:var(--raise);">
                <i class="fas fa-vector-square"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['with_embedding']) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">มี embedding</div>
            </div>
        </div>
        {{-- การ์ด: ถูก retrieve รวม --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#b79ae8; box-shadow:var(--raise);">
                <i class="fas fa-bullseye"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total_hits']) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ถูก retrieve รวม</div>
            </div>
        </div>
    </div>

    {{-- ===== 🏷 กระจายตามหมวด (collapsible — ใช้ Alpine x-data ของตัวเอง) ===== --}}
    @if (! empty($stats['category_breakdown']))
        <div class="tp-card" style="padding:0; overflow:hidden;" x-data="{ open:false }">
            {{-- หัวการ์ด คลิกเพื่อกาง/พับ --}}
            <button type="button" @click="open = !open"
                    style="width:100%; border:0; background:transparent; cursor:pointer; padding:16px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; color:var(--ink); box-shadow:var(--inset-sm);">
                <span class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700;">
                    <i class="fas fa-tags" style="color:var(--accent1);"></i>
                    กระจายตามหมวด ({{ count($stats['category_breakdown']) }} หมวด)
                </span>
                <i class="fas fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .2s; color:var(--ink2);"></i>
            </button>
            {{-- เนื้อหา: ไทล์ลิงก์ไปกรองตามหมวด --}}
            <div x-show="open" x-collapse style="padding:16px 18px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px;">
                    @foreach ($categoryOptions as $catKey => $catLabel)
                        @php $catCount = $stats['category_breakdown'][$catKey] ?? 0; @endphp
                        <a href="{{ route('admin.fortune.admin-qa.index', ['category' => $catKey]) }}"
                           class="tp-well tp-card-hover"
                           style="display:block; padding:12px 14px; border-radius:12px; text-decoration:none; box-shadow:var(--inset-sm);">
                            <div style="font-size:11.5px; color:var(--ink2); line-height:1.4;">{{ $catLabel }}</div>
                            <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1); margin-top:3px;">{{ number_format($catCount) }}</div>
                        </a>
                    @endforeach
                    @if (isset($stats['category_breakdown']['__null__']))
                        <a href="{{ route('admin.fortune.admin-qa.index', ['category' => '__null__']) }}"
                           class="tp-well tp-card-hover"
                           style="display:block; padding:12px 14px; border-radius:12px; text-decoration:none; box-shadow:var(--inset-sm); border-left:3px solid #e0a52e;">
                            <div style="font-size:11.5px; color:#e0a52e; line-height:1.4;">⚠️ ยังไม่จัดหมวด (legacy)</div>
                            <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1); margin-top:3px;">{{ number_format($stats['category_breakdown']['__null__']) }}</div>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ===== ⚙️ ตั้งค่าระบบ RAG (collapsible) ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;" x-data="{ open:false }">
        {{-- หัวการ์ด คลิกเพื่อกาง/พับ --}}
        <button type="button" @click="open = !open"
                style="width:100%; border:0; background:transparent; cursor:pointer; padding:16px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; color:var(--ink); box-shadow:var(--inset-sm);">
            <span class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700;">
                <i class="fas fa-sliders" style="color:var(--accent1);"></i>
                ตั้งค่าระบบ RAG
            </span>
            <i class="fas fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .2s; color:var(--ink2);"></i>
        </button>
        {{-- ฟอร์มตั้งค่า — คง action/method/@csrf/field เดิม 100% --}}
        <div x-show="open" x-collapse style="padding:18px;">
            <form action="{{ route('admin.fortune.admin-qa.settings') }}" method="POST" style="display:flex; flex-direction:column; gap:16px;">
                @csrf
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">

                    {{-- toggle: เปิดการเก็บข้อมูล (Capture) --}}
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                        <input type="hidden" name="admin_qa_capture_enabled" value="0">
                        <input type="checkbox" name="admin_qa_capture_enabled" value="1"
                               @checked($settings->admin_qa_capture_enabled ?? true)
                               style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); cursor:pointer;">
                        <div>
                            <div style="font-weight:700; color:var(--ink); font-size:13.5px;">📥 เปิดการเก็บข้อมูล (Capture)</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:2px; line-height:1.5;">เก็บคู่ Q&amp;A อัตโนมัติเมื่อแอดมินตอบใน FB Page Inbox</div>
                        </div>
                    </label>

                    {{-- toggle: เปิดการใช้งาน (RAG inject) --}}
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                        <input type="hidden" name="admin_qa_rag_enabled" value="0">
                        <input type="checkbox" name="admin_qa_rag_enabled" value="1"
                               @checked($settings->admin_qa_rag_enabled ?? true)
                               style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); cursor:pointer;">
                        <div>
                            <div style="font-weight:700; color:var(--ink); font-size:13.5px;">🤖 เปิดการใช้งาน (RAG inject)</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:2px; line-height:1.5;">AI ใช้ Q&amp;A คล้ายเป็น few-shot ตอนตอบลูกค้า</div>
                        </div>
                    </label>

                    {{-- Threshold --}}
                    <label style="display:block;">
                        <div style="font-weight:700; color:var(--ink2); font-size:13px; margin-bottom:6px;">🎯 Threshold (0.0-1.0)</div>
                        <div class="tp-well tp-input">
                            <input type="number" step="0.01" min="0" max="1" name="admin_qa_rag_threshold"
                                   value="{{ $settings->admin_qa_rag_threshold ?? 0.78 }}"
                                   style="width:100%; background:transparent; border:0; outline:0; padding:0; color:var(--ink); font-size:14px;">
                        </div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:5px; line-height:1.5;">cosine similarity ≥ ค่านี้ ถึงจะถูกหยิบมาใช้ (แนะนำ 0.78)</div>
                    </label>

                    {{-- Top-K --}}
                    <label style="display:block;">
                        <div style="font-weight:700; color:var(--ink2); font-size:13px; margin-bottom:6px;">🔢 Top-K examples</div>
                        <div class="tp-well tp-input">
                            <input type="number" min="1" max="10" name="admin_qa_rag_top_k"
                                   value="{{ $settings->admin_qa_rag_top_k ?? 3 }}"
                                   style="width:100%; background:transparent; border:0; outline:0; padding:0; color:var(--ink); font-size:14px;">
                        </div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:5px; line-height:1.5;">จำนวนตัวอย่างที่ inject ใน prompt (แนะนำ 3)</div>
                    </label>
                </div>
                <div>
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="fas fa-floppy-disk"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== 🔍 ตัวกรอง/ค้นหา — คง GET method + field เดิม 100% ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; align-items:end;">
            {{-- ช่องค้นหา Q/A --}}
            <div style="grid-column:1 / -1;">
                <div style="font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค้นหา</div>
                <div class="tp-well tp-input" style="display:flex; align-items:center; gap:9px;">
                    <i class="fas fa-magnifying-glass" style="color:var(--ink2); font-size:13px;"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="ค้น Q หรือ A..."
                           style="flex:1; background:transparent; border:0; outline:0; padding:0; color:var(--ink); font-size:14px;">
                </div>
            </div>

            {{-- หมวด --}}
            <div>
                <div style="font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมวด</div>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="category" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="">— ทุกหมวด —</option>
                        @foreach ($categoryOptions as $catKey => $catLabel)
                            <option value="{{ $catKey }}" @selected($categoryFilter === $catKey)>{{ $catLabel }}</option>
                        @endforeach
                        <option value="__null__" @selected($categoryFilter === '__null__')>⚠️ ยังไม่จัดหมวด</option>
                    </select>
                </div>
            </div>

            {{-- platform --}}
            <div>
                <div style="font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Platform</div>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="platform" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="">— ทุก platform —</option>
                        <option value="facebook" @selected($platform === 'facebook')>Facebook</option>
                        <option value="line" @selected($platform === 'line')>LINE</option>
                        <option value="manual" @selected($platform === 'manual')>Manual</option>
                    </select>
                </div>
            </div>

            {{-- สถานะ --}}
            <div>
                <div style="font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</div>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="active" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="">— ทุกสถานะ —</option>
                        <option value="1" @selected($activeFilter === '1')>✅ เปิด</option>
                        <option value="0" @selected($activeFilter === '0')>⏸ ปิด</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่มค้นหา + ล้าง --}}
            <div style="display:flex; gap:9px; grid-column:1 / -1;">
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:0 0 auto;">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
                <a href="{{ route('admin.fortune.admin-qa.index') }}" class="tp-btn">
                    <i class="fas fa-rotate-left"></i> ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ===== ตารางรายการ Q&A ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:separate; border-spacing:0 8px; padding:0 14px; min-width:920px;">
                {{-- หัวตาราง: สี var(--ink2) --}}
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">#</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Q / A</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">หมวด</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">แหล่ง</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Embed</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">Hits</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $qa)
                        {{-- 1 แถว = 1 คู่ Q&A (มี x-data ของตัวเองสำหรับฟอร์ม @click) --}}
                        <tr style="box-shadow:var(--inset-sm); border-radius:12px;">
                            {{-- # id --}}
                            <td style="padding:14px 12px; vertical-align:top; font-size:13px; color:var(--ink2);" class="tp-num">{{ $qa->id }}</td>

                            {{-- Q / A --}}
                            <td style="padding:14px 12px; vertical-align:top; max-width:420px;">
                                <div style="margin-bottom:10px;">
                                    <div style="font-size:10.5px; font-weight:700; color:#5689b8; letter-spacing:.3px; margin-bottom:2px;">
                                        Q · {{ Str::length($qa->q_text) }} chars
                                    </div>
                                    <div style="font-size:13px; color:var(--ink); line-height:1.5;">{{ Str::limit($qa->q_text, 120) }}</div>
                                </div>
                                <div>
                                    <div style="font-size:10.5px; font-weight:700; color:#5aa07e; letter-spacing:.3px; margin-bottom:2px;">
                                        A · {{ Str::length($qa->a_text) }} chars
                                    </div>
                                    <div style="font-size:13px; color:var(--ink2); line-height:1.5;">{{ Str::limit($qa->a_text, 150) }}</div>
                                </div>
                            </td>

                            {{-- หมวด + reading_type --}}
                            <td style="padding:14px 12px; vertical-align:top;">
                                @if ($qa->category)
                                    @php
                                        // สีป้ายหมวดจาก palette V4 (ใช้ array lookup — เลี่ยง match() ที่วงเล็บชน Blade @php parser)
                                        $catColorMap = [
                                            'pre_purchase'    => '#5689b8',
                                            'birthdate_help'  => '#b79ae8',
                                            'question_input'  => '#b79ae8',
                                            'pre_payment'     => '#e0a52e',
                                            'payment_confirm' => '#5aa07e',
                                            'post_reading'    => '#b79ae8',
                                            'celtic_picking'  => '#d6824a',
                                        ];
                                        $catColor = $catColorMap[$qa->category] ?? '#9a8f7c';
                                    @endphp
                                    <span class="tp-pill" style="background:{{ $catColor }}1f; color:{{ $catColor }}; box-shadow:var(--inset-sm); white-space:nowrap;">
                                        {{ $qa->categoryLabel() }}
                                    </span>
                                @else
                                    <span class="tp-pill" style="background:#e0a52e1f; color:#e0a52e; box-shadow:var(--inset-sm); white-space:nowrap;">
                                        ⚠️ ไม่ระบุ
                                    </span>
                                @endif
                                @if ($qa->reading_type)
                                    <div style="font-size:11px; color:var(--ink2); margin-top:6px;">📦 {{ $qa->reading_type }}</div>
                                @endif
                            </td>

                            {{-- แหล่ง --}}
                            <td style="padding:14px 12px; vertical-align:top; font-size:13px;">
                                <div style="color:var(--ink); font-weight:600;">{{ $qa->source_platform }}</div>
                                @if ($qa->source_user_id)
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2); font-family:monospace;">{{ Str::limit($qa->source_user_id, 12) }}</div>
                                @endif
                                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $qa->created_at?->diffForHumans() }}</div>
                            </td>

                            {{-- Embed --}}
                            <td style="padding:14px 12px; vertical-align:top; font-size:13px;">
                                @if ($qa->q_embedding)
                                    <span class="tp-num" style="color:#5aa07e; font-weight:700;">
                                        <i class="fas fa-circle-check"></i> {{ count($qa->q_embedding) }}d
                                    </span>
                                @else
                                    {{-- ฟอร์ม re-embed — คง action/@csrf เดิม --}}
                                    <form action="{{ route('admin.fortune.admin-qa.reembed', $qa) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm" style="color:#d6824a; white-space:nowrap;">
                                            <i class="fas fa-triangle-exclamation"></i> ว่าง — re-embed
                                        </button>
                                    </form>
                                @endif
                            </td>

                            {{-- Hits --}}
                            <td style="padding:14px 12px; vertical-align:top; text-align:right; font-size:14px; font-weight:700; color:var(--deep1);" class="tp-num">{{ number_format($qa->hit_count) }}</td>

                            {{-- สถานะ toggle — คง action/@csrf เดิม --}}
                            <td style="padding:14px 12px; vertical-align:top;">
                                <form action="{{ route('admin.fortune.admin-qa.toggle', $qa) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @if ($qa->is_active)
                                        <button type="submit" class="tp-pill" style="background:#5aa07e; color:#fff; border:0; cursor:pointer;">
                                            <i class="fas fa-circle" style="font-size:7px;"></i> เปิด
                                        </button>
                                    @else
                                        <button type="submit" class="tp-pill" style="background:var(--surf); color:var(--ink2); box-shadow:var(--inset-sm); border:0; cursor:pointer;">
                                            <i class="fas fa-circle" style="font-size:7px;"></i> ปิด
                                        </button>
                                    @endif
                                </form>
                            </td>

                            {{-- จัดการ: แก้ไข + ลบ --}}
                            <td style="padding:14px 12px; vertical-align:top; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; gap:8px; align-items:center;">
                                    {{-- ปุ่มแก้ไข (ลิงก์ไปหน้า edit) --}}
                                    <a href="{{ route('admin.fortune.admin-qa.edit', $qa) }}" class="tp-icon-btn" title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    {{-- ฟอร์มลบ (DELETE + ยืนยันก่อนลบ) --}}
                                    <form action="{{ route('admin.fortune.admin-qa.destroy', $qa) }}" method="POST"
                                          style="display:inline;" onsubmit="return confirm('ลบ Q&A นี้ใช่ไหม?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state --}}
                        <tr>
                            <td colspan="8" style="padding:48px 24px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:34px; display:block; margin-bottom:12px; opacity:.5;"></i>
                                <div class="tp-num" style="font-size:16px; font-weight:700; color:var(--ink); margin-bottom:6px;">
                                    ยังไม่มีข้อมูล
                                </div>
                                <div style="font-size:13px;">
                                    แอดมินตอบลูกค้าใน FB Page Inbox แล้วระบบจะเก็บอัตโนมัติ
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if ($rows->hasPages())
        <div class="tp-card" style="padding:12px 16px;">
            {{ $rows->links() }}
        </div>
    @endif

</div>
@endsection

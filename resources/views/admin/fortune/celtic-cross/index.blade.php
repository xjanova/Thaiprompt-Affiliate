@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- 🔮 Celtic Cross Tarot Mode (ธีม V4 นวลทองคำ) — คงทุก field/route/logic เดิม 100% --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · Celtic Cross Tarot</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">Celtic Cross Tarot Mode 🔮</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross — ค่าครู 99 บาท / 3 คำถาม / 1 ชั่วโมง window</div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.celtic-cross.emergency-recover'))
                <a href="{{ route('admin.fortune.celtic-cross.emergency-recover') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-kit-medical"></i> กู้บิลด่วน
                </a>
            @endif
            @if(Route::has('admin.fortune.settings.index'))
                <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-gear"></i> ตั้งค่าดูดวง
                </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI grid ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @php
            // [label, value, icon, สีไอคอน, prefix]
            $kpis = [
                ['Readings ทั้งหมด', number_format($stats['total_readings']), 'fa-layer-group', null, ''],
                ['ชำระแล้ว', number_format($stats['paid_readings']), 'fa-circle-check', '#5aa07e', ''],
                ['เสร็จวันนี้', number_format($stats['completed_today']), 'fa-calendar-day', '#b79ae8', ''],
                ['รายได้รวม', number_format($stats['total_revenue'], 0), 'fa-coins', '#e0a52e', '฿'],
                ['คำถามรวม', number_format($stats['total_questions']), 'fa-comments', '#5689b8', ''],
            ];
        @endphp
        @foreach ($kpis as [$label, $value, $icon, $iconBg, $prefix])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;@if($iconBg) background:{{ $iconBg }};@endif">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1;">{{ $prefix }}{{ $value }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== ฟอร์มตั้งค่า ===== --}}
    <form action="{{ route('admin.fortune.celtic-cross.settings.update') }}" method="POST" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ── ข้อผิดพลาดการตรวจสอบ (validation errors) — layout admin-v4 ไม่ render $errors ให้ ── --}}
        @if ($errors->any())
            <div class="tp-card" style="padding:16px 18px; border-left:4px solid var(--accent1);">
                <div style="font-weight:700; color:var(--accent1); font-size:13.5px; margin-bottom:8px;">
                    <i class="fas fa-triangle-exclamation"></i> กรุณาแก้ไขข้อมูลต่อไปนี้
                </div>
                <ul style="margin:0; padding-left:20px; font-size:12.5px; color:var(--ink2); line-height:1.8; list-style:disc;">
                    @foreach ($errors->all() as $celticError)
                        <li>{{ $celticError }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── สถานะระบบ (toggles) ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-sliders"></i> สถานะระบบ</div>

            {{-- เปิดบริการ Celtic Cross --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-bottom:12px; border-left:4px solid var(--accent2);">
                <input type="hidden" name="enable_celtic_cross" value="0">
                <input type="checkbox" name="enable_celtic_cross" value="1"
                       {{ $settings->enable_celtic_cross ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent2); cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">🔮 เปิดบริการ Celtic Cross Tarot Mode</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        เมื่อเปิด — ลูกค้าจะเลือกเมนูนี้ได้ตอนทำนายเสร็จพื้นฐาน หรือพิมพ์ "celtic cross" เพื่อเริ่ม
                    </div>
                </div>
            </label>

            {{-- AI proactive --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer;">
                <input type="hidden" name="celtic_cross_proactive_enabled" value="0">
                <input type="checkbox" name="celtic_cross_proactive_enabled" value="1"
                       {{ $settings->celtic_cross_proactive_enabled ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent2); cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">🤖 AI เชิญชวนเองเมื่อลูกค้าทุกข์มาก</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        AI จะแนะนำ Celtic Cross เมื่อตรวจพบสัญญาณความทุกข์ในแชทปกติ (throttled — เสนอครั้งเดียวต่อ session)
                    </div>
                </div>
            </label>

            {{-- 🪬 (2026-06-24) โหมดดูคุณไสย์ / มนต์ดำ — gate ทั้งปุ่มฝั่งลูกค้า + toggle ฝั่งแอดมิน --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-top:12px; border-left:4px solid #9b59b6;">
                <input type="hidden" name="enable_celtic_black_magic_mode" value="0">
                <input type="checkbox" name="enable_celtic_black_magic_mode" value="1"
                       {{ ($settings->enable_celtic_black_magic_mode ?? true) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#9b59b6; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">🪬 เปิดโหมดดูคุณไสย์ / มนต์ดำ (99฿)</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        เปิด — ลูกค้าเห็นปุ่ม "ดูคุณไสย 99฿" ตอนเลือกแพคเกจ + แอดมินสลับโหมดได้ที่หน้า Admin Ask AI ของแต่ละบิล
                        (AI ล็อกคำตอบเทเรื่องของ/มนต์ดำ ตรงตามไพ่ ไม่มั่ว ไม่ตอบหมวดอื่น). ปิด — ซ่อนปุ่ม + ปิดโหมดทุกที่
                    </div>
                </div>
            </label>

            {{-- ⚡ (2026-06-26) Bypass — ข้ามกล่องกติกา/รหัสเสียงทั้งหมด → สร้างบิลทันที --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-top:12px; border-left:4px solid #d9534f;">
                <input type="hidden" name="consent_gate_bypass" value="0">
                <input type="checkbox" name="consent_gate_bypass" value="1"
                       {{ ($settings->consent_gate_bypass ?? false) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#d9534f; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">⚡ ข้ามกล่องกติกาทั้งหมด → สร้างบิลทันที</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        เปิด — เลือกแพคเกจแล้ว<b>ข้ามกล่องกติกา + รหัสเสียงทั้งหมด</b> ออก QR/บิลทันที (flow ลื่นสุด).
                        <b>มีศักดิ์เหนือทุกตัวเลือกด้านล่าง</b> (เปิดอันนี้ = การตั้งค่าเสียง/รหัสด้านล่างไม่ทำงาน). ปิด — กล่องกติกาทำงานปกติ
                    </div>
                </div>
            </label>

            {{-- 🔊 (2026-06-26) บังคับฟังเสียงกติกา + กรอกรหัสท้ายคลิป ก่อนสร้างบิล --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-top:12px; border-left:4px solid #e0a52e;">
                <input type="hidden" name="enable_consent_audio_code" value="0">
                <input type="checkbox" name="enable_consent_audio_code" value="1"
                       {{ ($settings->enable_consent_audio_code ?? false) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#e0a52e; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">🔊 บังคับฟังเสียงกติกา + กรอกรหัสยืนยัน (ก่อนสร้างบิล)</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        เปิด — ตอนกล่องกติกา ระบบส่ง "เสียงกติกา + รหัส 4 หลักท้ายคลิป" (สุ่มต่อครั้ง) ลูกค้าต้องฟังให้จบแล้วพิมพ์รหัสจึงจะออก QR.
                        มี fallback กันลูกค้าติด (พิมพ์ผิด 3 ครั้ง / "ไม่ได้ยิน-ทำไม่เป็น" → เฉลยรหัสให้). ปิด — กล่องกติกาปกติ (ไม่ต้องกรอกรหัส)
                    </div>
                    <div style="margin-top:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">โมเดลเสียงที่ใช้เจน "รหัส":</span>
                        <select name="consent_audio_code_voice_provider"
                                onclick="event.preventDefault();" onmousedown="event.stopPropagation();"
                                style="padding:6px 10px; border-radius:8px; font-size:12.5px; border:1px solid var(--sd); background:var(--bg);">
                            @php $bmVp = $settings->consent_audio_code_voice_provider ?? 'minimax'; @endphp
                            <option value="minimax" {{ $bmVp === 'minimax' ? 'selected' : '' }}>MiniMax (32kHz — แนะนำ ตรงกับเสียงกติกา)</option>
                            <option value="openai_tts" {{ $bmVp === 'openai_tts' ? 'selected' : '' }}>OpenAI TTS</option>
                            <option value="google_tts" {{ $bmVp === 'google_tts' ? 'selected' : '' }}>Google Cloud TTS</option>
                            <option value="gtts" {{ $bmVp === 'gtts' ? 'selected' : '' }}>gTTS (ฟรี 24kHz)</option>
                        </select>
                        <span style="font-size:11px; color:var(--ink2);">(ffmpeg รวมไฟล์ให้เนียน — เลือก provider ไหนก็ได้)</span>
                    </div>
                    <div style="margin-top:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">บังคับเฉพาะคนที่มีบิลค้างไม่จ่าย ≥</span>
                        <input type="number" name="consent_audio_code_min_unpaid_bills" min="0" max="99" step="1"
                               value="{{ (int) ($settings->consent_audio_code_min_unpaid_bills ?? 0) }}"
                               onclick="event.preventDefault();" onmousedown="event.stopPropagation();"
                               style="width:70px; padding:6px 10px; border-radius:8px; font-size:12.5px; border:1px solid var(--sd); background:var(--bg);">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">บิล</span>
                        <span style="font-size:11px; color:var(--ink2);">(0 = บังคับ <b>ทุกบิลทุกคน</b> / เช่น 2 = เฉพาะคนเคยสร้างบิลแล้วไม่จ่าย ≥ 2 บิล — ลูกค้าใหม่/ดีไม่ต้องกรอก)</span>
                    </div>
                </div>
            </label>
        </div>

        {{-- ── ราคา & เงื่อนไข ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-coins"></i> ราคา &amp; เงื่อนไข</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                {{-- ค่าครู --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค่าครู (บาท)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="celtic_cross_price" step="0.01"
                               value="{{ $settings->celtic_cross_price ?? 99 }}" min="1" max="9999"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>

                {{-- จำนวนคำถาม --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        จำนวนคำถาม/บิล <span style="color:var(--accent2); font-size:11px;">(0 = ไม่จำกัด)</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="celtic_cross_max_questions"
                               value="{{ $settings->celtic_cross_max_questions ?? 5 }}" min="0" max="50"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">📊 บังคับ enforcement (2026-05-03) — ครบโควต้าจะจบ session ให้คำตอบสุดท้าย</div>
                </div>

                {{-- เวลาถามต่อ --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เวลาถามต่อ (นาที)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="celtic_cross_qa_window_minutes"
                               value="{{ $settings->celtic_cross_qa_window_minutes ?? 15 }}" min="5" max="1440"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">นับจากคำทำนายแรก — เกินเวลา session จบอัตโนมัติ (default 15 นาที — 2026-05-23 v3)</div>
                </div>
            </div>
        </div>

        {{-- ── AI Prompts (ขั้นสูง) ── --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-robot"></i> AI Prompts (ขั้นสูง)</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:16px; line-height:1.7;">
                เว้นว่างเพื่อใช้ default — แก้ที่นี่ถ้าอยากปรับโทน/สไตล์ AI<br>
                ตัวแปรที่ใช้ได้:
                <span class="tp-pill tp-pill-soft" style="font-family:monospace;">&#123;question&#125;</span>
                <span class="tp-pill tp-pill-soft" style="font-family:monospace;">&#123;cards&#125;</span>
                <span class="tp-pill tp-pill-soft" style="font-family:monospace;">&#123;brand_name&#125;</span>
                <span class="tp-pill tp-pill-soft" style="font-family:monospace;">&#123;sequence&#125;</span>
                <br><span style="color:var(--accent1); font-size:12px;">⚠️ แม่หมอจันทรา ไม่ใช้วันเกิด — ใช้พลังจักรวาล + จิตเจ้าชะตาเท่านั้น</span>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">📝 Main Prompt — Q1 (full storytelling)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="celtic_cross_main_prompt" rows="10"
                              placeholder="เว้นว่างใช้ default — แต่งคำสั่ง AI ที่นี่ ใช้ตัวแปร {question}, {cards}, {brand_name}"
                              style="width:100%; background:transparent; border:none; outline:none; padding:12px 14px; color:var(--ink); font-size:13px; font-family:monospace; resize:vertical;">{{ $settings->celtic_cross_main_prompt }}</textarea>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">📝 Followup Prompt — Q2/Q3 (no card explain)</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="celtic_cross_followup_prompt" rows="8"
                              placeholder="เว้นว่างใช้ default"
                              style="width:100%; background:transparent; border:none; outline:none; padding:12px 14px; color:var(--ink); font-size:13px; font-family:monospace; resize:vertical;">{{ $settings->celtic_cross_followup_prompt }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── ปุ่มบันทึก ── --}}
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- ===== Celtic Cross Readings ===== --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-scroll"></i> Celtic Cross Readings</div>

        {{-- ── ตัวกรอง (Filter) ── --}}
        <form method="GET" style="display:flex; flex-direction:column; gap:14px; margin-bottom:18px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                {{-- ค้นหา --}}
                <div style="grid-column:span 2; min-width:0;">
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">🔍 ค้นหา (ชื่อ / Bill / FB ID / #ID)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                               placeholder="ค้นชื่อ / Bill / FB ID / #ID"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>

                {{-- สถานะ --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="status" style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="">— ทุกสถานะ —</option>
                            <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                            <option value="unpaid" {{ ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' }}>ยังไม่จ่าย</option>
                            <option value="stuck" {{ ($filters['status'] ?? '') === 'stuck' ? 'selected' : '' }}>ค้าง (paid + ไม่ครบ 10)</option>
                            <option value="celtic_pending_payment" {{ ($filters['status'] ?? '') === 'celtic_pending_payment' ? 'selected' : '' }}>รอชำระ</option>
                            <option value="celtic_picking" {{ ($filters['status'] ?? '') === 'celtic_picking' ? 'selected' : '' }}>กำลังเลือกไพ่</option>
                            <option value="celtic_awaiting_question" {{ ($filters['status'] ?? '') === 'celtic_awaiting_question' ? 'selected' : '' }}>รอคำถาม</option>
                            <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>จบแล้ว</option>
                            <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                        </select>
                    </div>
                </div>

                {{-- วันที่เริ่มต้น --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่เริ่มต้น</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                               style="width:100%; background:transparent; border:none; outline:none; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
                <a href="{{ route('admin.fortune.celtic-cross.index') }}" class="tp-btn">
                    <i class="fas fa-rotate-left"></i> ล้างตัวกรอง
                </a>
            </div>
        </form>

        @if($recentReadings->isEmpty())
            {{-- Empty state --}}
            <div style="text-align:center; padding:48px 20px; color:var(--ink2);">
                <i class="fas fa-inbox" style="font-size:42px; opacity:.45;"></i>
                <div style="margin-top:14px; font-size:14px;">ยังไม่มี Celtic Cross reading</div>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="min-width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2);">
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">Bill</th>
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">ลูกค้า</th>
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">สถานะ</th>
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">ค่าครู</th>
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">Q ใช้</th>
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase;">เวลาเหลือ</th>
                            <th style="padding:10px 12px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentReadings as $reading)
                            <tr style="border-top:1px solid var(--sd);">
                                {{-- Bill --}}
                                <td style="padding:11px 12px; font-family:monospace; font-size:12px; color:var(--ink);">
                                    {{ $reading->bill_reference ?? '#'.$reading->id }}
                                    {{-- 🪬 (2026-06-25) ป้ายบอกว่าลูกค้าใช้ "โหมดดูคุณไสย์" รอบนี้ (ล็อกทำนายเรื่องของ/มนต์ดำ 100%) --}}
                                    @if(data_get($reading->conversation_state, 'black_magic_mode'))
                                        <span class="tp-pill" title="ลูกค้าเลือกโหมดดูคุณไสย์ / มนต์ดำ — ทำนายล็อกเรื่องของ/คุณไสย์ทั้งรอบ"
                                              style="display:inline-block; margin-top:4px; background:rgba(123,80,168,.16); color:#7b50a8; font-size:10px; font-weight:700;">🪬 คุณไสย</span>
                                    @endif
                                </td>

                                {{-- ลูกค้า --}}
                                <td style="padding:11px 12px; color:var(--ink);">{{ $reading->facebook_user_name ?? '-' }}</td>

                                {{-- สถานะ --}}
                                <td style="padding:11px 12px;">
                                    @php
                                        // 🏷️ (2026-05-25) Cancellation overrides raw status display
                                        $celticCancellationLabel = $reading->getCancellationReasonLabelOrNull();
                                    @endphp
                                    @if($celticCancellationLabel)
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;"
                                              title="reason: {{ data_get($reading->conversation_state, 'cancellation_reason') }}">
                                            ❌ {{ $celticCancellationLabel }}
                                        </span>
                                    @else
                                        @php
                                            $celticIsDone = $reading->conversation_status === 'completed';
                                        @endphp
                                        <span class="tp-pill" style="{{ $celticIsDone ? 'background:rgba(90,160,126,.18); color:#3f7a5c;' : 'background:rgba(224,165,46,.18); color:#a9791a;' }}">
                                            {{ $reading->conversation_status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- ค่าครู --}}
                                <td style="padding:11px 12px;">
                                    @if($reading->is_paid)
                                        <span style="color:#5aa07e; font-weight:600;">฿{{ number_format((float) ($reading->amount_received ?? $reading->amount_paid), 0) }} ✓</span>
                                        @if($reading->amount_received !== null && abs((float) $reading->amount_received - (float) $reading->amount_paid) >= 0.01)
                                            <span style="display:block; font-size:10px; color:var(--ink2);">บิล ฿{{ number_format((float) $reading->amount_paid, 0) }}</span>
                                        @endif
                                    @else
                                        <span style="color:var(--ink2);">รอ</span>
                                    @endif
                                </td>

                                {{-- Q ใช้ --}}
                                <td style="padding:11px 12px; color:var(--ink);">{{ $reading->celtic_questions_used }} คำถาม</td>

                                {{-- เวลาเหลือ --}}
                                <td style="padding:11px 12px; font-size:12px; color:var(--ink2);">
                                    @php
                                        // 🆕 (2026-05-22) อ่าน Pro Session state ก่อน (รองรับ admin extend)
                                        // fallback: สูตรเก่า celtic_first_answered_at + setting (legacy 3Q flow)
                                        $_proActive = (bool) $reading->getConversationState('pro_session_active', false);
                                        $_proStartedAt = $reading->getConversationState('pro_session_started_at');
                                        $_proWindowMin = (int) $reading->getConversationState('pro_session_window_minutes', $settings->celtic_cross_qa_window_minutes ?? 15);
                                        $_minRemaining = null;
                                        $_isExtended = false;

                                        if ($_proActive && ! empty($_proStartedAt)) {
                                            try {
                                                $_elapsed = (int) \Carbon\Carbon::parse($_proStartedAt)->diffInMinutes(now(), true);
                                                $_minRemaining = max(0, $_proWindowMin - $_elapsed);
                                                $_isExtended = $_proWindowMin > ($settings->celtic_cross_qa_window_minutes ?? 15);
                                            } catch (\Throwable $e) {
                                                $_minRemaining = null;
                                            }
                                        }

                                        if ($_minRemaining === null && $reading->celtic_first_answered_at) {
                                            $_deadline = $reading->celtic_first_answered_at->copy()->addMinutes($settings->celtic_cross_qa_window_minutes ?? 15);
                                            $_minRemaining = max(0, (int) ceil(now()->diffInSeconds($_deadline, false) / 60));
                                        }
                                    @endphp
                                    @if($_minRemaining === null)
                                        <span>—</span>
                                    @elseif($_minRemaining > 0)
                                        <span style="color:#5aa07e;">{{ $_minRemaining }} นาที</span>
                                        @if($_isExtended)
                                            <span style="color:#5689b8; margin-left:4px;" title="แอดมินขยายเวลาแล้ว (window {{ $_proWindowMin }}m)">⏰+</span>
                                        @endif
                                    @else
                                        <span style="color:#d9534f;">หมดเวลา</span>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td style="padding:11px 12px; text-align:right; white-space:nowrap;">
                                    <a href="{{ route('admin.fortune.celtic-cross.show', $reading) }}"
                                       class="tp-btn tp-btn-sm" style="display:inline-flex;">
                                        <i class="fas fa-eye"></i> ดู
                                    </a>
                                    @if($reading->is_paid && $reading->celticQuestions_count == 0 && $reading->getCelticPickedCount() < 10)
                                        {{-- 🔄 Quick reset for stuck readings --}}
                                        <form action="{{ route('admin.fortune.celtic-cross.reset', $reading) }}" method="POST"
                                              onsubmit="return confirm('Reset reading #{{ $reading->id }}?');"
                                              style="display:inline-block; margin-left:6px;">
                                            @csrf
                                            <button type="submit" class="tp-btn tp-btn-sm" style="color:#a9791a;">
                                                <i class="fas fa-rotate"></i> reset
                                            </button>
                                        </form>
                                    @elseif(! $reading->is_paid && (int) ($reading->celtic_questions_used ?? 0) === 0)
                                        {{-- 🗑️ (2026-05-04) Quick cancel for pending-payment bills (ขัดกัน) --}}
                                        <form action="{{ route('admin.fortune.celtic-cross.cancel', $reading) }}" method="POST"
                                              onsubmit="return confirm('ยกเลิกบิล {{ $reading->bill_reference ?? '#' . $reading->id }}? (ปลอดภัยเพราะยังไม่จ่าย)');"
                                              style="display:inline-block; margin-left:6px;">
                                            @csrf
                                            <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f;">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
                                        </form>
                                    @endif
                                    @if($reading->is_paid)
                                        {{-- ⛔ (2026-06-08) Void Approval — ยกเลิกการอนุมัติบิลที่กดผิด/ลูกค้าไม่ได้จ่ายจริง --}}
                                        @php $celticConsumed = ($reading->getCelticPickedCount() > 0) || ((int) ($reading->celtic_questions_used ?? 0) > 0); @endphp
                                        <form action="{{ route('admin.fortune.celtic-cross.void-approval', $reading) }}" method="POST"
                                              onsubmit="return confirm('⛔ ยกเลิกการอนุมัติบิล {{ $reading->bill_reference ?? '#'.$reading->id }} ? จะคืนเป็นยังไม่จ่าย + ปลดยอด/SMS + ดึงคืนคอมมิชชั่น @if($celticConsumed)(⚠️ ลูกค้าเปิดไพ่/ถามไปแล้ว) @endif— ใช้เฉพาะกรณีอนุมัติผิด/ลูกค้าไม่ได้จ่ายจริง');"
                                              style="display:inline-block; margin-left:6px;">
                                            @csrf
                                            @if($celticConsumed)<input type="hidden" name="force" value="1">@endif
                                            <button type="submit" class="tp-btn tp-btn-sm" style="color:#d9534f; font-weight:700;">
                                                <i class="fas fa-ban"></i> ยกเลิกอนุมัติ
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div style="margin-top:18px;">
                {{ $recentReadings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

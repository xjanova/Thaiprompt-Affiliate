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
                // ⏳ (2026-08-07) KPI ใหม่ — บิลที่ยังลุ้นได้เงิน (เดิมไม่มีตัวเลขนี้ที่ไหนเลย)
                ['รอชำระ', number_format($stats['pending_readings']), 'fa-hourglass-half', '#a9791a', ''],
                // 🏷️ ป้ายเดิม "เสร็จวันนี้" ไม่ตรงกับสิ่งที่นับ (celtic_first_answered_at = ตอบคำถามแรก)
                ['เริ่มทำนายวันนี้', number_format($stats['answered_today']), 'fa-calendar-day', '#b79ae8', ''],
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

            {{-- 📋 (2026-07-11) แบบสอบถามยืนยันเจตนา 5 ข้อ ก่อนสร้างบิล (เฉพาะคนสร้างบิลแล้วไม่จ่าย "หลอด") --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-top:12px; border-left:4px solid #c0392b;">
                <input type="hidden" name="enable_consent_quiz" value="0">
                <input type="checkbox" name="enable_consent_quiz" value="1"
                       {{ ($settings->enable_consent_quiz ?? false) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#c0392b; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">📋 แบบสอบถามยืนยันเจตนา 5 ข้อ ก่อนสร้างบิล (กันคน "สร้างบิลไม่จ่าย")</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">
                        เปิด — คนที่เข้าเกณฑ์ "บิลค้างไม่จ่าย" จะต้องตอบ <b>ใช่/ไม่ใช่ 5 ข้อ</b> เชิงจิตวิทยา (ตั้งใจดูดวงจริง / เข้าใจว่าต้องจ่าย /
                        <b>ยอมรับว่าถ้าไม่จ่าย = งดใช้งานเพจ N วัน</b>) จึงจะออกบิล. ตอบ "ไม่ใช่" = ปิดเงียบ ไม่สร้างบิล ไม่แบน.
                        ยอมรับครบแล้วยังไม่จ่าย → แบนอัตโนมัติ N วัน (หมดอายุเอง). ปิด — ไม่ถาม (กล่องกติกาปกติ)
                    </div>
                    <div style="margin-top:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">บังคับเฉพาะคนที่มีบิลค้างไม่จ่าย ≥</span>
                        <input type="number" name="consent_quiz_min_unpaid_bills" min="0" max="99" step="1"
                               value="{{ (int) ($settings->consent_quiz_min_unpaid_bills ?? 2) }}"
                               onclick="event.preventDefault();" onmousedown="event.stopPropagation();"
                               style="width:70px; padding:6px 10px; border-radius:8px; font-size:12.5px; border:1px solid var(--sd); background:var(--bg);">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">บิล</span>
                        <span style="font-size:11px; color:var(--ink2);">(0 = ทุกคน / เช่น 2 = เฉพาะคนเคยสร้างบิลแล้วไม่จ่าย ≥ 2 บิล)</span>
                    </div>
                    <div style="margin-top:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">ยอมรับแล้วไม่จ่าย → แบน</span>
                        <input type="number" name="consent_quiz_ban_days" min="1" max="365" step="1"
                               value="{{ (int) ($settings->consent_quiz_ban_days ?? 7) }}"
                               onclick="event.preventDefault();" onmousedown="event.stopPropagation();"
                               style="width:70px; padding:6px 10px; border-radius:8px; font-size:12.5px; border:1px solid var(--sd); background:var(--bg);">
                        <span style="font-size:12px; color:var(--ink2); font-weight:600;">วัน</span>
                        <span style="font-size:11px; color:var(--ink2);">(เลขนี้จะโชว์ในคำถามข้อ 5 ด้วย — แบนหมดอายุเองอัตโนมัติ)</span>
                    </div>
                    <div style="font-size:11px; color:#c0392b; margin-top:8px;">
                        ⚠️ การแบนอัตโนมัติใช้ระบบเดียวกับ "แบนคนสร้างบิลเล่นๆ" (<code>enable_bill_troll_ban</code>) — ถ้าปิดระบบแบนนั้น คำถามจะยังถามได้แต่จะไม่แบนจริง
                    </div>
                </div>
            </label>
        </div>

        {{-- 🎚️ (2026-06-26) สวิตช์พฤติกรรมเชิงรุกของบอท — รวมไว้หน้าเดียว ตั้งค่าง่าย --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-sliders-h"></i> สวิตช์พฤติกรรมบอท (เปิด/ปิด)</div>
            <div style="font-size:12px; color:var(--ink2); margin-bottom:12px;">ปิดสวิตช์ = บอทหยุดทำพฤติกรรมนั้น (default เปิดทั้งหมด = เหมือนเดิม)</div>

            {{-- 🛒 กระตุ้นการขาย --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; border-left:4px solid #5aa07e;">
                <input type="hidden" name="enable_sales_pitch" value="0">
                <input type="checkbox" name="enable_sales_pitch" value="1"
                       {{ ($settings->enable_sales_pitch ?? true) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#5aa07e; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">🛒 กระตุ้นการขาย (AI เสนอเริ่มดูดวงเอง)</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">เปิด — AI ชวนลูกค้าเริ่มดูดวงเองหลังคุยสร้างความสนิท. ปิด — AI ตอบตามที่ลูกค้าถาม ไม่ชวนขาย</div>
                </div>
            </label>

            {{-- 💸 กระตุ้นจ่ายบิล --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-top:12px; border-left:4px solid #e0a52e;">
                <input type="hidden" name="enable_bill_payment_nudge" value="0">
                <input type="checkbox" name="enable_bill_payment_nudge" value="1"
                       {{ ($settings->enable_bill_payment_nudge ?? true) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#e0a52e; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">💸 กระตุ้นจ่ายบิล (เตือนบิลค้าง + nudge "กดพร้อมบูชาครู")</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">เปิด — เตือนลูกค้าที่มีบิลค้างเป็นระยะ + กระตุ้นให้กดยืนยันตอนเงียบ. ปิด — ไม่ทวง/ไม่กระตุ้น (บิลยังหมดอายุเองตามปกติ)</div>
                </div>
            </label>

            {{-- 💭 ถามก่อนยกเลิกบิล (ของเดิม fortune_consent_cancel_enabled — ย้ายมาคุมที่นี่ด้วย) --}}
            <label class="tp-inset" style="display:flex; align-items:flex-start; gap:13px; padding:16px; border-radius:14px; cursor:pointer; margin-top:12px; border-left:4px solid #9b59b6;">
                <input type="hidden" name="fortune_consent_cancel_enabled" value="0">
                <input type="checkbox" name="fortune_consent_cancel_enabled" value="1"
                       {{ ($settings->fortune_consent_cancel_enabled ?? true) ? 'checked' : '' }}
                       style="width:18px; height:18px; margin-top:2px; accent-color:#9b59b6; cursor:pointer;">
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--ink); font-size:14.5px;">💭 ถามก่อนยกเลิกบิล (เตือนสติตอนลูกค้ายกเลิก)</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:4px;">เปิด — ตอนลูกค้ากดยกเลิก ส่งรูป+ข้อความเตือนสติ (แยกเจตนาเบี้ยว vs ติดขัดจริง). ปิด — ยกเลิกได้เลย ไม่เตือน. <i>(สวิตช์เดียวกับหน้า "กล่องกติกา")</i></div>
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
                               value="{{ $settings->celtic_cross_max_questions ?? 0 }}" min="0" max="50"
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

    {{-- ===== รายการบิล ย้ายไปศูนย์รวมบิลแล้ว ===== --}}
    {{-- 🧾 (2026-08-07) หน้านี้เหลือ "ตั้งค่า" อย่างเดียว
         เจ้าของ: "การตั้งค่าก็แยกไว้ว่าคือการตั้งค่า ต้องไม่งง แบ่งแยกชัดเจน"
         รายการบิล Celtic ย้ายไป /admin/fortune/bills?package=celtic ซึ่งรวมทุกแพคเกจ
         ทุกช่องทาง และมีปุ่มจัดการครบกว่าเดิม --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-scroll"></i> รายการบิล Celtic</div>
        <p style="font-size:13px; color:var(--ink2); margin:0 0 14px; line-height:1.7;">
            รายการบิลย้ายไปรวมที่ <strong>ศูนย์รวมบิลดูดวง</strong> แล้ว —
            ที่นั่นดูได้ทุกแพคเกจ กรองตามช่องทาง (Facebook / LINE) เห็นอายุบิลที่รอชำระ
            และมีปุ่มจัดการครบกว่าเดิม
        </p>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.bills.index'))
                <a href="{{ route('admin.fortune.bills.index', ['package' => 'celtic']) }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-receipt"></i> ดูบิล Celtic ทั้งหมด
                </a>
                <a href="{{ route('admin.fortune.bills.index', ['package' => 'celtic', 'status' => 'pending']) }}" class="tp-btn">
                    <i class="fas fa-hourglass-half"></i> Celtic ที่รอชำระ
                </a>
                <a href="{{ route('admin.fortune.bills.index', ['package' => 'celtic', 'status' => 'stuck_celtic']) }}" class="tp-btn">
                    <i class="fas fa-cube"></i> Celtic ค้าง (ไพ่ไม่ครบ)
                </a>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin-v4')

@section('title', 'ประวัติการทำนาย — รายการ')

@section('content')
{{-- 🔮 ประวัติการทำนาย (ธีม V4 นวลทองคำ) — คงทุก filter, ปุ่ม Export CSV, ลิงก์ดู ตามของเดิม 100% --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ประวัติการทำนาย</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ประวัติการทำนาย 🔮</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ดูประวัติและสถิติการทำนายทั้งหมด</div>
        </div>
        <div style="display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
            @if(Route::has('admin.fortune.dashboard'))
                <a href="{{ route('admin.fortune.dashboard') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </a>
            @endif
            @if(Route::has('admin.fortune.settings.index'))
                <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-gear"></i> ตั้งค่า
                </a>
            @endif
            @if(Route::has('admin.fortune.astrology.index'))
                <a href="{{ route('admin.fortune.astrology.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-star"></i> โหราศาสตร์
                </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI grid ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:16px;">
        {{-- ทั้งหมด --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ทั้งหมด</div>
                </div>
            </div>
        </div>
        {{-- วันนี้ --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5aa07e;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['today']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">วันนี้</div>
                </div>
            </div>
        </div>
        {{-- เชิงลึก --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#b79ae8;">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['deep'] ?? 0) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">🌟 เชิงลึก</div>
                </div>
            </div>
        </div>
        {{-- พื้นฐาน --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5689b8;">
                    <i class="fas fa-hat-wizard"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['basic'] ?? 0) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">🔮 พื้นฐาน</div>
                </div>
            </div>
        </div>
        {{-- ชำระเงินแล้ว --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#d6824a;">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['paid']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ชำระเงินแล้ว</div>
                </div>
            </div>
        </div>
        {{-- ฟรี --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#9a8f7c;">
                    <i class="fas fa-gift"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['free']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ฟรี</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ⚠️ Warning: บิลที่ชำระแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ ===== --}}
    @if(($stats['stuck_paid'] ?? 0) > 0)
        <div class="tp-card" style="padding:18px; border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#d9534f; flex-shrink:0;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p style="font-weight:800; font-size:16px; margin:0; color:#d9534f;">
                        มี {{ $stats['stuck_paid'] }} บิลที่ชำระเงินแล้วแต่ยังไม่มีคำทำนาย!
                    </p>
                    <p style="font-size:12.5px; color:var(--ink2); margin:5px 0 0;">
                        ลูกค้าจ่ายเงินแล้วแต่ยังไม่ได้รับคำทำนาย อาจเกิดจาก: AI quota หมด, API key หมดอายุ, เครือข่ายขัดข้อง
                    </p>
                    <p style="font-size:12.5px; color:var(--ink2); margin:5px 0 0;">
                        กรุณาเข้าไปกดปุ่ม "สร้างคำทำนายเชิงลึก" ในแต่ละรายการ หรือตรวจสอบ AI API keys ใน
                        @if(Route::has('admin.fortune.settings.index'))
                            <a href="{{ route('admin.fortune.settings.index') }}" style="color:var(--deep1); font-weight:700; text-decoration:underline;">ตั้งค่าระบบดูดวง</a>
                        @else
                            ตั้งค่าระบบดูดวง
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Filters ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-filter"></i> ตัวกรองข้อมูล</div>
        <form method="GET" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
            {{-- ค้นหา (ชื่อ / รหัสบิล / platform user id) --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    🔍 ค้นหา (ชื่อ / รหัสบิล)
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ชื่อ หรือ FTU-260425-T4022..."
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            {{-- หมวดคำทำนาย --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    📂 หมวดคำทำนาย
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="category" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="ความรัก" {{ request('category') === 'ความรัก' ? 'selected' : '' }}>💕 ความรัก</option>
                        <option value="การงาน" {{ request('category') === 'การงาน' ? 'selected' : '' }}>💼 การงาน</option>
                        <option value="การเงิน" {{ request('category') === 'การเงิน' ? 'selected' : '' }}>💰 การเงิน</option>
                        <option value="สุขภาพ" {{ request('category') === 'สุขภาพ' ? 'selected' : '' }}>🏥 สุขภาพ</option>
                        <option value="โชคลาภ" {{ request('category') === 'โชคลาภ' ? 'selected' : '' }}>🎰 โชคลาภ</option>
                        <option value="การเรียน" {{ request('category') === 'การเรียน' ? 'selected' : '' }}>📚 การเรียน</option>
                    </select>
                </div>
            </div>

            {{-- AI Provider --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    AI Provider
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="ai_provider" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="gemini" {{ request('ai_provider') === 'gemini' ? 'selected' : '' }}>Gemini</option>
                        <option value="groq" {{ request('ai_provider') === 'groq' ? 'selected' : '' }}>Groq</option>
                        <option value="qwen" {{ request('ai_provider') === 'qwen' ? 'selected' : '' }}>Qwen</option>
                        <option value="grok" {{ request('ai_provider') === 'grok' ? 'selected' : '' }}>Grok</option>
                        <option value="deepseek" {{ request('ai_provider') === 'deepseek' ? 'selected' : '' }}>DeepSeek</option>
                        <option value="openrouter" {{ request('ai_provider') === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                        <option value="typhoon" {{ request('ai_provider') === 'typhoon' ? 'selected' : '' }}>Typhoon</option>
                    </select>
                </div>
            </div>

            {{-- สถานะ Conversation --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    สถานะ Conversation
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="conversation_status" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="new" {{ request('conversation_status') === 'new' ? 'selected' : '' }}>🆕 New</option>
                        <option value="basic_done" {{ request('conversation_status') === 'basic_done' ? 'selected' : '' }}>🔮 Basic Done</option>
                        <option value="collecting_birthdate" {{ request('conversation_status') === 'collecting_birthdate' ? 'selected' : '' }}>📅 Collecting Birthdate</option>
                        <option value="collecting_questions" {{ request('conversation_status') === 'collecting_questions' ? 'selected' : '' }}>❓ Collecting Questions</option>
                        <option value="pending_payment" {{ request('conversation_status') === 'pending_payment' ? 'selected' : '' }}>💳 Pending Payment</option>
                        <option value="paid" {{ request('conversation_status') === 'paid' ? 'selected' : '' }}>✅ Paid</option>
                        <option value="completed" {{ request('conversation_status') === 'completed' ? 'selected' : '' }}>🏁 Completed</option>
                    </select>
                </div>
            </div>

            {{-- สถานะชำระเงิน --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    สถานะชำระเงิน
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="is_paid" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="1" {{ request('is_paid') === '1' ? 'selected' : '' }}>💰 ชำระเงินแล้ว</option>
                        <option value="0" {{ request('is_paid') === '0' ? 'selected' : '' }}>🆓 ฟรี</option>
                    </select>
                </div>
            </div>

            {{-- ประเภทคำทำนาย --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    ประเภทคำทำนาย
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="reading_type" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="basic" {{ request('reading_type') === 'basic' ? 'selected' : '' }}>🔮 พื้นฐาน</option>
                        <option value="deep" {{ request('reading_type') === 'deep' ? 'selected' : '' }}>🌟 เชิงลึก</option>
                    </select>
                </div>
            </div>

            {{-- วันที่เริ่มต้น --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    วันที่เริ่มต้น
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            {{-- วันที่สิ้นสุด --}}
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                    วันที่สิ้นสุด
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            {{-- ปุ่ม action --}}
            <div style="grid-column:1 / -1; display:flex; flex-wrap:wrap; gap:10px; padding-top:4px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> กรองข้อมูล
                </button>
                <a href="{{ route('admin.fortune.readings.index') }}" class="tp-btn">
                    <i class="fas fa-rotate-left"></i> ล้างตัวกรอง
                </a>
                <a href="{{ route('admin.fortune.readings.export', request()->all()) }}" class="tp-btn"
                   style="color:#5aa07e;">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </form>
    </div>

    {{-- ===== Readings Table ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">วันที่</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ผู้ใช้ / รหัสบิล</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">หมวด</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ประเภท</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">AI</th>
                        <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($readings as $reading)
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- วันที่ --}}
                            <td style="padding:14px 16px; font-size:13.5px; color:var(--ink); white-space:nowrap;">
                                <div>{{ $reading->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $reading->created_at->format('H:i') }}</div>
                            </td>
                            {{-- ผู้ใช้ / รหัสบิล --}}
                            <td style="padding:14px 16px; font-size:13.5px; color:var(--ink);">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="tp-tile" style="width:30px; height:30px; border-radius:50%; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        {{ mb_substr($reading->facebook_user_name ?? '?', 0, 1) }}
                                    </span>
                                    <div style="min-width:0;">
                                        <div style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;">{{ $reading->facebook_user_name ?? 'ไม่ระบุชื่อ' }}</div>
                                        <div style="font-size:11.5px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;">{{ Str::limit($reading->facebook_user_id, 15) }}</div>
                                        @if($reading->bill_reference)
                                            {{-- 🧾 รหัสบิล — คลิก copy ได้ (คง logic เดิม) --}}
                                            <button type="button"
                                                    onclick="navigator.clipboard.writeText('{{ $reading->bill_reference }}'); this.innerText='✅ คัดลอกแล้ว'; setTimeout(() => this.innerText='🧾 {{ $reading->bill_reference }}', 1500);"
                                                    style="margin-top:2px; font-size:11.5px; font-family:monospace; color:var(--deep1); background:none; border:none; padding:0; cursor:pointer; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;"
                                                    title="คลิกเพื่อคัดลอกรหัสบิล">
                                                🧾 {{ $reading->bill_reference }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            {{-- หมวด --}}
                            <td style="padding:14px 16px; font-size:13.5px; white-space:nowrap;">
                                @php $cats = $reading->categories ?? []; @endphp
                                @if(!empty($cats))
                                    @foreach(array_slice($cats, 0, 2) as $cat)
                                        <span class="tp-pill tp-pill-soft" style="margin-right:3px;">{{ $cat }}</span>
                                    @endforeach
                                @else
                                    <span style="color:var(--ink2); font-size:12px;">-</span>
                                @endif
                            </td>
                            {{-- ประเภท --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($reading->reading_type === 'deep')
                                    <span class="tp-pill" style="background:rgba(183,154,232,.18); color:#7a5db8;">🌟 เชิงลึก</span>
                                @else
                                    <span class="tp-pill" style="background:rgba(86,137,184,.18); color:#3f6a96;">🔮 พื้นฐาน</span>
                                @endif
                            </td>
                            {{-- AI --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill tp-pill-gold">{{ strtoupper($reading->ai_provider) }}</span>
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($reading->is_paid)
                                    <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">💰 ฿{{ number_format($reading->amount_paid, 2) }}</span>
                                @else
                                    <span class="tp-pill" style="background:rgba(154,143,124,.18); color:var(--ink2);">🆓 ฟรี</span>
                                @endif
                                @php
                                    // 🏷️ (2026-05-25) Cancellation badge — ก่อน badge อื่นๆ (คง logic เดิม)
                                    $readingCancellationLabel = $reading->getCancellationReasonLabelOrNull();
                                @endphp
                                @if($readingCancellationLabel)
                                    <div style="margin-top:5px;">
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;"
                                              title="reason: {{ data_get($reading->conversation_state, 'cancellation_reason') }}">
                                            ❌ {{ $readingCancellationLabel }}
                                        </span>
                                    </div>
                                {{-- 🛟 (2026-05-14) Pay-First incomplete — ลูกค้าจ่ายแล้วแต่ไม่กรอกข้อมูล --}}
                                @elseif($reading->is_paid && $reading->reading_type === 'deep' && empty($reading->birth_date) && empty($reading->deep_response))
                                    <div style="margin-top:5px;">
                                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;" title="ลูกค้าจ่ายแล้วแต่ยังไม่กรอกวันเกิด">
                                            🛟 รอวันเกิด
                                        </span>
                                    </div>
                                @elseif($reading->is_paid && empty($reading->deep_response) && $reading->reading_type === 'deep')
                                    <div style="margin-top:5px;">
                                        <span class="tp-pill" style="background:rgba(214,130,74,.18); color:#b06330;" title="จ่ายแล้ว — ยังไม่ส่งคำทำนาย">
                                            ⏳ ยังไม่ส่งคำทำนาย
                                        </span>
                                    </div>
                                @elseif($reading->conversation_status && $reading->conversation_status !== 'completed')
                                    {{-- Conversation status badge (เคสอื่นๆ) --}}
                                    <div style="margin-top:5px;">
                                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e;">
                                            {{ $reading->conversation_status }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.fortune.readings.show', $reading) }}" class="tp-btn tp-btn-sm tp-btn-primary">
                                    <i class="fas fa-eye"></i> ดู
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:0;">
                                {{-- Empty state --}}
                                <div style="text-align:center; color:var(--ink2); padding:40px 0;">
                                    <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                    ไม่พบประวัติการทำนาย
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($readings->hasPages())
        <div>
            {{ $readings->links() }}
        </div>
    @endif

</div>
@endsection

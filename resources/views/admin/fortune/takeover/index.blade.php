@extends('layouts.admin-v4')

@section('title', 'เทคโอเวอร์ — ระบบดูดวง')

@section('content')
{{--
    หน้ารายการเทคโอเวอร์ (แม่หมอ/แอดมินคุยเอง) — ธีม V4 "นวลทองคำ"
    คงฟังก์ชันเดิม 100%: ฟิลเตอร์ GET, ปุ่มเปิดคุย (show), modal แบน user (POST .../ban)
    Alpine x-data root อยู่ที่ container เพื่อคุม modal แบน
--}}
<div x-data="{
        showBan: false,
        banForm: { id: null, name: '', platform: 'facebook', userId: '', duration: 'permanent' },
        openBan(id, name, platform, userId) {
            // เปิด modal แบน — เตรียมข้อมูล user ที่จะแบน
            this.banForm = { id: id, name: name || '(ไม่ระบุชื่อ)', platform: platform, userId: userId, duration: 'permanent' };
            this.showBan = true;
        },
        closeBan() { this.showBan = false; }
     }"
     style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · เทคโอเวอร์
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                เทคโอเวอร์ — แม่หมอคุยแทน AI 🎯
            </h1>
            <p class="tp-muted" style="font-size:13px; margin:6px 0 0; max-width:560px;">
                กดเทคโอเวอร์เพื่อให้ AI หยุด แล้วแอดมินคุยเองได้ทั้ง LINE และ Facebook
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.settings.index') }}#takeover"
               class="tp-btn tp-btn-primary">
                <i class="fas fa-sliders-h"></i> ตั้งค่าระบบเทคโอเวอร์
            </a>
        </div>
    </div>

    {{-- ===== ข้อผิดพลาดจากการตรวจสอบฟอร์ม (เช่น แบน user) ===== --}}
    {{-- layout admin-v4 auto-toast เฉพาะ session flash — ไม่ render $errors bag จึงต้องแสดงเอง --}}
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:center; gap:9px; font-weight:700; color:#d9534f; margin-bottom:8px;">
                <i class="fas fa-circle-exclamation"></i> กรอกข้อมูลไม่ถูกต้อง
            </div>
            <ul style="margin:0; padding-left:20px; font-size:13px; color:var(--ink); list-style:disc;">
                @foreach($errors->all() as $error)
                    <li style="margin:2px 0;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== ระบบปิดอยู่ (แจ้งเตือน) ===== --}}
    @if(! $settings->isTakeoverEnabled())
    <div class="tp-card" style="border-left:4px solid #e0a52e; display:flex; align-items:flex-start; gap:14px;">
        <div class="tp-tile" style="background:linear-gradient(135deg, #e0a52e, #d6824a); flex:0 0 auto;">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="font-weight:700; color:var(--ink); font-size:15px;">ระบบเทคโอเวอร์ถูกปิดอยู่</div>
            <div class="tp-muted" style="font-size:13px; margin-top:4px;">
                เปิดใช้งานที่
                <a href="{{ route('admin.fortune.settings.index') }}#takeover"
                   style="color:var(--accent1); font-weight:600; text-decoration:underline;">
                    ตั้งค่าระบบดูดวง → ระบบเทคโอเวอร์
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:14px;">
        {{-- กำลังเทคโอเวอร์ --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile"><i class="fas fa-bullseye"></i></div>
            <div style="min-width:0;">
                <div class="tp-muted" style="font-size:12px; font-weight:600;">กำลังเทคโอเวอร์</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink); line-height:1.15;">
                    {{ number_format($stats['total_taken_over']) }}
                </div>
            </div>
        </div>
        {{-- บทสนทนา active 7 วัน --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="background:linear-gradient(135deg, #5689b8, #5aa07e);">
                <i class="fas fa-comments"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-muted" style="font-size:12px; font-weight:600;">บทสนทนา Active (7 วัน)</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink); line-height:1.15;">
                    {{ number_format($stats['total_active']) }}
                </div>
            </div>
        </div>
        {{-- เทคโอเวอร์วันนี้ --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="background:linear-gradient(135deg, #5aa07e, #e0a52e);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div style="min-width:0;">
                <div class="tp-muted" style="font-size:12px; font-weight:600;">เทคโอเวอร์วันนี้</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink); line-height:1.15;">
                    {{ number_format($stats['takeovers_today']) }}
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ฟิลเตอร์ (GET form — คง name/value เดิม) ===== --}}
    <form method="GET" class="tp-card">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:12px; align-items:end;">
            {{-- สถานะ --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">สถานะ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="filter"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="active" @selected($filter === 'active')>กำลังสนทนา (7 วัน)</option>
                        <option value="taken_over" @selected($filter === 'taken_over')>🎯 ถูกเทคโอเวอร์</option>
                        <option value="all" @selected($filter === 'all')>ทั้งหมด</option>
                    </select>
                </div>
            </div>
            {{-- Platform --}}
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">Platform</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="platform"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="">ทุก Platform</option>
                        <option value="line" @selected($platform === 'line')>💚 LINE</option>
                        <option value="facebook" @selected($platform === 'facebook')>🔵 Facebook</option>
                    </select>
                </div>
            </div>
            {{-- ค้นหา --}}
            <div style="grid-column:1 / -1;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ค้นหา</label>
                <div style="display:flex; gap:10px;">
                    <div class="tp-well tp-input" style="flex:1; padding:0;">
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="ชื่อ, user id, bill reference..."
                               style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="fas fa-magnifying-glass"></i> ค้นหา
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- ===== รายการบทสนทนา ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($readings->count() > 0)
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:760px; border-collapse:separate; border-spacing:0 8px; padding:0 14px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ผู้ใช้</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Platform</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Takeover</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">อัพเดตล่าสุด</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($readings as $reading)
                        {{-- แต่ละแถวมี x-data ของตัวเอง (isActive = ถูกเทคโอเวอร์อยู่หรือไม่) --}}
                        <tr x-data="{ isActive: {{ $reading->isAdminTakenOver() ? 'true' : 'false' }} }"
                            style="background:var(--surf); box-shadow:var(--inset-sm); border-radius:12px;">
                            {{-- ผู้ใช้ --}}
                            <td style="padding:14px 12px; border-radius:12px 0 0 12px;">
                                <div style="font-size:14px; font-weight:600; color:var(--ink);">
                                    {{ $reading->facebook_user_name ?? 'ไม่ระบุชื่อ' }}
                                </div>
                                <div class="tp-num" style="font-size:11px; color:var(--ink2); margin-top:2px;">
                                    {{ Str::limit($reading->platform_user_id ?: $reading->facebook_user_id, 20) }}
                                </div>
                            </td>
                            {{-- Platform --}}
                            <td style="padding:14px 12px;">
                                @if($reading->platform === 'line')
                                    <span class="tp-pill" style="color:#5aa07e; background:rgba(90,160,126,.12);">
                                        💚 LINE
                                    </span>
                                @else
                                    <span class="tp-pill" style="color:#5689b8; background:rgba(86,137,184,.12);">
                                        🔵 Facebook
                                    </span>
                                @endif
                            </td>
                            {{-- สถานะ conversation --}}
                            <td style="padding:14px 12px;">
                                @php
                                    // map สถานะ → ป้าย + สี (จาก palette V4)
                                    $statusMap = match($reading->conversation_status) {
                                        'new' => ['เริ่มต้น', '#9a8f7c'],
                                        'awaiting_confirmation' => ['รอยืนยัน', '#e0a52e'],
                                        'basic_done' => ['พื้นฐานเสร็จ', '#5689b8'],
                                        'collecting_birthdate' => ['กำลังเก็บวันเกิด', '#e0a52e'],
                                        'collecting_questions' => ['กำลังเก็บคำถาม', '#e0a52e'],
                                        'collecting_tarot' => ['กำลังเก็บไพ่', '#e0a52e'],
                                        'pending_payment' => ['รอชำระเงิน', '#d6824a'],
                                        'paid' => ['จ่ายแล้ว', '#b79ae8'],
                                        'completed' => ['เสร็จสิ้น', '#5aa07e'],
                                        default => [$reading->conversation_status, '#9a8f7c'],
                                    };
                                @endphp
                                <span class="tp-pill"
                                      style="color:{{ $statusMap[1] }}; background:{{ $statusMap[1] }}1f;">
                                    {{ $statusMap[0] }}
                                </span>
                            </td>
                            {{-- Takeover state --}}
                            <td style="padding:14px 12px;">
                                <template x-if="isActive">
                                    <span class="tp-pill tp-pill-gold" style="font-weight:700;">
                                        🎯 เทคโอเวอร์อยู่
                                        <span style="opacity:.7; font-size:10px;">({{ $reading->takeoverRemainingMinutes() }}m)</span>
                                    </span>
                                </template>
                                <template x-if="! isActive">
                                    <span class="tp-pill tp-pill-soft">
                                        🤖 AI ทำงาน
                                    </span>
                                </template>
                            </td>
                            {{-- อัพเดตล่าสุด --}}
                            <td style="padding:14px 12px; font-size:12px; color:var(--ink2);">
                                {{ $reading->updated_at->diffForHumans() }}
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 12px; text-align:right; white-space:nowrap; border-radius:0 12px 12px 0;">
                                <div style="display:inline-flex; align-items:center; gap:8px; justify-content:flex-end;">
                                    <a href="{{ route('admin.fortune.takeover.show', $reading) }}"
                                       class="tp-btn tp-btn-primary tp-btn-sm">
                                        เปิดคุย <i class="fas fa-arrow-right"></i>
                                    </a>
                                    {{-- 🚫 (2026-05-23) ปุ่มแบนด่วน — เปิด modal เลือก duration --}}
                                    <button type="button"
                                            @click="openBan({{ $reading->id }}, @js($reading->facebook_user_name), @js($reading->platform ?? 'facebook'), @js($reading->platform_user_id ?: $reading->facebook_user_id))"
                                            class="tp-icon-btn"
                                            style="color:#d9534f;"
                                            title="แบน user คนนี้">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            {{-- empty state --}}
            <div style="padding:56px 20px; text-align:center;">
                <i class="fas fa-inbox" style="font-size:42px; color:var(--ink2); opacity:.6;"></i>
                <p class="tp-muted" style="margin-top:12px; font-size:14px;">ไม่พบบทสนทนา</p>
            </div>
        @endif
    </div>

    {{-- ===== Pagination ===== --}}
    @if($readings->hasPages())
    <div>
        {{ $readings->links() }}
    </div>
    @endif

    {{-- ===== 🚫 (2026-05-23) Modal แบน user — เลือก preset duration ===== --}}
    <div x-show="showBan" x-cloak
         x-transition.opacity
         style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,.6);"
         @keydown.escape.window="closeBan()"
         @click.self="closeBan()">
        <div class="tp-card tp-raise"
             style="max-width:440px; width:100%; padding:0; overflow:hidden;"
             x-transition.scale.95.duration.150ms>
            {{-- Header --}}
            <div style="background:linear-gradient(135deg, #d9534f, #b8413d); padding:18px 20px; color:#fff;">
                <h3 style="font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px; margin:0;">
                    <i class="fas fa-ban"></i> แบน user คนนี้
                </h3>
                <p style="font-size:12px; color:rgba(255,255,255,.85); margin:6px 0 0;">
                    บอทจะไม่ตอบ user คนนี้อีก (admin ยังคุยผ่าน Page Inbox / LINE OA ได้)
                </p>
            </div>

            {{-- Body — ฟอร์มแบน (action/method/@csrf/field คงเดิม 100%) --}}
            <form method="POST" :action="'{{ url('admin/fortune/takeover') }}/' + banForm.id + '/ban'">
                @csrf
                <input type="hidden" name="from" value="index">

                <div style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                    {{-- ข้อมูล user --}}
                    <div class="tp-well" style="padding:14px;">
                        <div style="font-weight:600; color:var(--ink); font-size:14px;" x-text="banForm.name"></div>
                        <div class="tp-num" style="font-size:12px; color:var(--ink2); margin-top:3px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <span x-text="banForm.platform === 'line' ? '💚 LINE' : '🔵 Facebook'"></span>
                            <span style="opacity:.6;">•</span>
                            <span x-text="banForm.userId"></span>
                        </div>
                    </div>

                    {{-- preset ระยะเวลาแบน --}}
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:var(--ink2); margin-bottom:8px;">
                            ระยะเวลาแบน
                        </label>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <template x-for="opt in [
                                {value: '10m', label: '⏱ 10 นาที — เตือนสั้นๆ', danger: false},
                                {value: '1h', label: '⏰ 1 ชั่วโมง', danger: false},
                                {value: '24h', label: '📅 24 ชั่วโมง', danger: false},
                                {value: '7d', label: '🗓️ 7 วัน', danger: false},
                                {value: 'permanent', label: '🔒 ถาวร', danger: true}
                            ]" :key="opt.value">
                                <label style="display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; border:2px solid; cursor:pointer; transition:all .15s;"
                                       :style="banForm.duration === opt.value
                                            ? 'border-color:#d9534f; background:rgba(217,83,79,.10);'
                                            : 'border-color:var(--inset-sm-border, rgba(154,143,124,.25)); background:transparent;'">
                                    <input type="radio" name="duration" :value="opt.value" x-model="banForm.duration"
                                           style="accent-color:#d9534f; width:16px; height:16px;">
                                    <span style="font-size:14px;"
                                          :style="opt.danger ? 'font-weight:600; color:#d9534f;' : 'color:var(--ink);'"
                                          x-text="opt.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="tp-inset" style="padding:14px 20px; display:flex; align-items:center; justify-content:flex-end; gap:10px;">
                    <button type="button" @click="closeBan()" class="tp-btn">
                        ยกเลิก
                    </button>
                    <button type="submit" class="tp-btn"
                            style="background:#d9534f; color:#fff; box-shadow:var(--raise);">
                        <i class="fas fa-ban"></i> ยืนยันแบน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

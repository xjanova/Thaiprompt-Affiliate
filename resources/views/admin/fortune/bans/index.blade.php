{{--
    ระบบคุก (Ban/Jail) — ห้ามบอทคุยกับ user ที่ไม่เหมาะสม
    ธีม V4 "นวลทองคำ" (Nuan Gold Clay)

    ตัวแปรจาก FortuneBanController@index:
    - $bans     : paginator ของ FortuneUserBan (with bannedBy)
    - $stats    : ['total_active','permanent','temporary','total_expired']
    - $filter   : 'active'|'expired'|'all'
    - $platform : 'facebook'|'line'|null
    - $search   : string

    คงฟังก์ชันเดิม 100%:
    - ฟอร์มแบนใหม่ POST admin.fortune.bans.store (platform, platform_user_id, display_name, duration_type, duration_value, reason)
    - ฟอร์มกรอง GET (filter, platform, search)
    - ปุ่มปลดแบน DELETE admin.fortune.bans.destroy
--}}
@extends('layouts.admin-v4')

@section('title', 'ระบบคุก — ระบบดูดวง')

@section('content')
{{-- container หลัก: x-data ครอบทั้งหน้าเพื่อคุมการเปิด/ปิดฟอร์มแบน
     เปิดฟอร์มอัตโนมัติถ้ามี validation error (จะได้เห็นค่า old() ที่กรอกไว้) --}}
<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="{ showBanForm: {{ $errors->any() ? 'true' : 'false' }}, durationType: '{{ old('duration_type', 'permanent') }}' }">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · ความปลอดภัย
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                ระบบคุก 🚫
            </h1>
            <p class="tp-muted" style="font-size:13px; margin:6px 0 0; max-width:560px;">
                แบน user ไม่ให้บอทคุยด้วย (สแปม, ก่อกวน) — แอดมินยังคุยผ่าน Page Inbox / LINE OA ได้ตามปกติ
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- ปุ่มเปิด/ปิดฟอร์มแบนใหม่ --}}
            <button type="button" class="tp-btn tp-btn-primary" @click="showBanForm = !showBanForm">
                <i class="fas" :class="showBanForm ? 'fa-xmark' : 'fa-ban'"></i>
                <span x-text="showBanForm ? 'ปิดฟอร์ม' : 'แบน user ใหม่'"></span>
            </button>
        </div>
    </div>

    {{-- ===== Validation errors =====
         layout admin-v4 แปลงเฉพาะ session flash เป็น toast — ไม่จัดการ $errors
         จึงต้องแสดง validation error ของฟอร์มแบนเองที่หน้านี้ --}}
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

    {{-- ===== KPI GRID ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        {{-- ติดแบนอยู่ --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-ban"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1); line-height:1;">
                    {{ number_format($stats['total_active']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ติดแบนอยู่</div>
            </div>
        </div>

        {{-- แบนถาวร --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#d9534f; line-height:1;">
                    {{ number_format($stats['permanent']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">แบนถาวร</div>
            </div>
        </div>

        {{-- แบนชั่วคราว --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#e0a52e; line-height:1;">
                    {{ number_format($stats['temporary']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">แบนชั่วคราว</div>
            </div>
        </div>

        {{-- หมดอายุแล้ว --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-scroll"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink); line-height:1;">
                    {{ number_format($stats['total_expired']) }}
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">หมดอายุแล้ว</div>
            </div>
        </div>
    </div>

    {{-- ===== ฟอร์มแบน user ใหม่ (เปิด/ปิดด้วย showBanForm) ===== --}}
    <div class="tp-card" x-show="showBanForm" x-transition x-cloak style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:16px; display:flex; align-items:center; gap:9px;">
            <i class="fas fa-ban" style="color:#d9534f;"></i> แบน user ใหม่
        </div>

        <form method="POST" action="{{ route('admin.fortune.bans.store') }}">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px;">
                {{-- แพลตฟอร์ม --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        แพลตฟอร์ม <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform" required
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="facebook">🔵 Facebook</option>
                            <option value="line">💚 LINE</option>
                        </select>
                    </div>
                </div>

                {{-- User ID --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        User ID <span style="color:#d9534f;">*</span>
                        <span class="tp-muted" style="font-weight:400;">(PSID หรือ LINE userId)</span>
                    </label>
                    <input type="text" name="platform_user_id" required
                        value="{{ old('platform_user_id') }}"
                        placeholder="เช่น 28046354201620818"
                        class="tp-well tp-input" style="font-family:ui-monospace,monospace;">
                </div>

                {{-- ชื่อแสดง (optional) --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        ชื่อ (แสดงใน list)
                        <span class="tp-muted" style="font-weight:400;">— optional</span>
                    </label>
                    <input type="text" name="display_name"
                        value="{{ old('display_name') }}"
                        placeholder="ดึงจาก reading อัตโนมัติถ้าไม่กรอก"
                        class="tp-well tp-input">
                </div>

                {{-- ระยะเวลา --}}
                <div>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        ระยะเวลา <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="duration_type" x-model="durationType" required
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="permanent">🔒 ถาวร</option>
                            <option value="minutes">⏱ นาที</option>
                            <option value="hours">⏰ ชั่วโมง</option>
                            <option value="days">📅 วัน</option>
                        </select>
                    </div>
                </div>

                {{-- จำนวน (แสดงเมื่อไม่ใช่ถาวร) --}}
                <div x-show="durationType !== 'permanent'" x-transition>
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        จำนวน <span style="color:#d9534f;">*</span>
                    </label>
                    <input type="number" name="duration_value" min="1" max="100000"
                        value="{{ old('duration_value', 7) }}"
                        x-bind:required="durationType !== 'permanent'"
                        class="tp-well tp-input">
                </div>

                {{-- เหตุผล (เต็มแถว) --}}
                <div style="grid-column:1 / -1;">
                    <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        เหตุผล <span class="tp-muted" style="font-weight:400;">— สำหรับ audit</span>
                    </label>
                    <textarea name="reason" rows="2"
                        placeholder="เช่น สแปม, ก่อกวน, ขู่/คุกคาม..."
                        class="tp-well tp-input">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="tp-divider" style="margin:18px 0;"></div>

            <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                <button type="button" class="tp-btn" @click="showBanForm = false">
                    <i class="fas fa-xmark"></i> ยกเลิก
                </button>
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-ban"></i> ยืนยันการแบน
                </button>
            </div>
        </form>
    </div>

    {{-- ===== ฟอร์มกรอง / ค้นหา ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; align-items:end;">
                {{-- สถานะ --}}
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="filter"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="active" @selected($filter === 'active')>🚫 ติดแบนอยู่</option>
                            <option value="expired" @selected($filter === 'expired')>📜 หมดอายุ</option>
                            <option value="all" @selected($filter === 'all')>ทั้งหมด</option>
                        </select>
                    </div>
                </div>

                {{-- แพลตฟอร์ม --}}
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">แพลตฟอร์ม</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="">ทุก Platform</option>
                            <option value="facebook" @selected($platform === 'facebook')>🔵 Facebook</option>
                            <option value="line" @selected($platform === 'line')>💚 LINE</option>
                        </select>
                    </div>
                </div>

                {{-- ค้นหา --}}
                <div style="grid-column:span 1; min-width:0;">
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค้นหา</label>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="ชื่อ, user id, เหตุผล..."
                        class="tp-well tp-input">
                </div>

                {{-- ปุ่มค้นหา / รีเซ็ต --}}
                <div style="display:flex; gap:9px;">
                    <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">
                        <i class="fas fa-magnifying-glass"></i> ค้นหา
                    </button>
                    <a href="{{ route('admin.fortune.bans.index') }}" class="tp-btn" style="flex:1; justify-content:center;">
                        <i class="fas fa-rotate-left"></i> รีเซ็ต
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ===== ตารางผู้ถูกแบน ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($bans->isEmpty())
            {{-- Empty state --}}
            <div style="text-align:center; color:var(--ink2); padding:48px 20px;">
                <i class="fas fa-dove" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                ไม่มี user ที่ถูกแบนในสถานะนี้
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="text-align:left;">
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">ผู้ใช้</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">Platform</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">สถานะ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">เหตุผล</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">พยายามทัก</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase;">แบนเมื่อ</th>
                            <th style="padding:13px 16px; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.4px; text-transform:uppercase; text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bans as $ban)
                            <tr style="box-shadow:var(--inset-sm);">
                                {{-- ผู้ใช้ --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    <div style="font-weight:700; color:var(--ink); font-size:14px;">
                                        {{ $ban->display_name ?: '(ไม่มีชื่อ)' }}
                                    </div>
                                    <div class="tp-muted" style="font-size:11.5px; font-family:ui-monospace,monospace; margin-top:3px; word-break:break-all;">
                                        {{ $ban->platform_user_id }}
                                    </div>
                                </td>

                                {{-- Platform --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    @if($ban->platform === 'facebook')
                                        <span class="tp-pill" style="background:rgba(86,137,184,.16); color:#5689b8;">🔵 Facebook</span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(90,160,126,.16); color:#5aa07e;">💚 LINE</span>
                                    @endif
                                </td>

                                {{-- สถานะ --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    @if($ban->isPermanent())
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f; font-weight:700;">🔒 ถาวร</span>
                                    @elseif($ban->isActive())
                                        <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#e0a52e;">⏳ {{ $ban->remainingHumanReadable() }}</span>
                                        <div class="tp-muted" style="font-size:11px; margin-top:5px;">
                                            ถึง: {{ $ban->banned_until->format('Y-m-d H:i') }}
                                        </div>
                                    @else
                                        <span class="tp-pill" style="background:rgba(154,143,124,.18); color:#9a8f7c;">📜 หมดอายุ</span>
                                    @endif
                                </td>

                                {{-- เหตุผล --}}
                                <td style="padding:14px 16px; vertical-align:top; max-width:240px; color:var(--ink); font-size:13px;">
                                    {{ $ban->reason ?: '—' }}
                                </td>

                                {{-- พยายามทัก --}}
                                <td style="padding:14px 16px; vertical-align:top;">
                                    <div class="tp-num" style="font-weight:700; color:var(--ink); font-size:14px;">
                                        {{ number_format($ban->attempt_count) }} ครั้ง
                                    </div>
                                    @if($ban->last_notified_at)
                                        <div class="tp-muted" style="font-size:11px; margin-top:3px;">
                                            แจ้งล่าสุด: {{ $ban->last_notified_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>

                                {{-- แบนเมื่อ --}}
                                <td style="padding:14px 16px; vertical-align:top; color:var(--ink2); font-size:13px;">
                                    <div style="color:var(--ink);">{{ $ban->created_at->format('Y-m-d') }}</div>
                                    <div class="tp-muted" style="font-size:11.5px;">{{ $ban->created_at->format('H:i') }}</div>
                                    @if($ban->bannedBy)
                                        <div class="tp-muted" style="font-size:11px; margin-top:4px;">โดย: {{ $ban->bannedBy->name }}</div>
                                    @endif
                                </td>

                                {{-- จัดการ (ปลดแบน) --}}
                                <td style="padding:14px 16px; vertical-align:top; text-align:right;">
                                    <form method="POST" action="{{ route('admin.fortune.bans.destroy', $ban) }}"
                                        onsubmit="return confirm('ปลดแบน {{ $ban->platform }}:{{ $ban->platform_user_id }} ?');"
                                        style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm"
                                            style="background:rgba(90,160,126,.16); color:#5aa07e;">
                                            <i class="fas fa-wand-magic-sparkles"></i> ปลดแบน
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Pagination ===== --}}
    @if($bans->hasPages())
        <div>
            {{ $bans->links() }}
        </div>
    @endif
</div>
@endsection

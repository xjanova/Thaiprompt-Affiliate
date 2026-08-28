@extends('layouts.admin-v4')

@section('title', 'ข้อความชวนดูดวง (สุ่ม)')

@section('content')
{{-- ภาชนะหลักของหน้า + Alpine root (รวม logic เดิม showAdd/filter/editing/openEdit) --}}
<div x-data="inviteMessages()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · คลังข้อความเชิญชวน</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ข้อความชวนดูดวง (สุ่ม) 💬</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">ส่งแทนรูปแบนเนอร์ เมื่อลูกค้าได้รูปไปแล้วในสัปดาห์นี้</p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <button type="button" class="tp-btn tp-btn-primary" @click="showAdd = !showAdd">
                <i class="fas fa-plus"></i> เพิ่มข้อความใหม่
            </button>
        </div>
    </div>

    {{-- ===== ⚠️ ข้อผิดพลาดจากการตรวจสอบ (validation) =====
         layout admin-v4 โชว์เฉพาะ session('success'/'error'/'warning') แบบ toast
         แต่ไม่ render $errors → ถ้าไม่ใส่ block นี้ ผู้ใช้กรอกผิด (เช่น หมวดยาวเกิน 50)
         จะ submit แล้วเงียบ ไม่เห็น error เลย (เดิม v3 มี block นี้) --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div class="tp-section-h" style="margin-bottom:8px; color:#d9534f;">
                <i class="fas fa-triangle-exclamation" style="color:#d9534f;"></i> กรอกข้อมูลไม่ถูกต้อง
            </div>
            <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:4px; font-size:13px; color:var(--ink2); line-height:1.55;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== ℹ️ วิธีทำงาน (กล่องอธิบาย) ===== --}}
    <div class="tp-card" style="border-left:4px solid var(--accent1);">
        <div class="tp-section-h" style="margin-bottom:8px;">
            <i class="fas fa-circle-info" style="color:var(--accent1);"></i> ระบบนี้ทำงานยังไง
        </div>
        <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:5px; font-size:13px; color:var(--ink2); line-height:1.55;">
            <li>ลูกค้าคอมเมนต์/กดไลก์ครั้งแรกในสัปดาห์ → บอท DM กลับพร้อม<strong style="color:var(--ink);">รูปแบนเนอร์</strong> (เหมือนเดิม)</li>
            <li>ครั้งถัดไป<strong style="color:var(--ink);">ในสัปดาห์เดียวกัน</strong> → ไม่ส่งรูปซ้ำ แต่<strong style="color:var(--ink);">สุ่ม</strong>ข้อความจากคลังนี้ส่งแทน + ปุ่มดูดวง</li>
            <li>พิมพ์ <code class="tp-pill tp-pill-soft" style="padding:1px 7px; font-size:12px;">{name}</code> เพื่อแทนชื่อลูกค้าอัตโนมัติ (เช่น "คุณ{name}")</li>
            <li>"สัปดาห์" รีเซ็ตทุกวันจันทร์ • ข้อความใช้เสียงแม่หมอ (ผู้หญิง)</li>
            {{-- ⏰ (2026-08-08) ช่วงเวลาส่ง — กันข้อความ "ดึกแล้ว...ก่อนนอน" ยิงตอนเที่ยง --}}
            <li>
                <strong style="color:var(--ink);">⏰ ช่วงเวลาส่ง</strong> — ข้อความที่เขียนผูกเวลา
                (เช่น "อรุณสวัสดิ์" หรือ "ดึกแล้ว...ก่อนนอน") ตั้งช่วงชั่วโมงไว้ได้
                บอทจะสุ่มเฉพาะข้อความที่ตรงเวลานั้น <span class="tp-muted">(เว้นว่าง = ส่งได้ทุกเวลา)</span>
            </li>
        </ul>
    </div>

    {{-- ===== 📊 KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        {{-- ข้อความทั้งหมด --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile"><i class="fas fa-comment-dots"></i></div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($totalMessages) }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:4px;">ข้อความทั้งหมด</div>
            </div>
        </div>
        {{-- เปิดใช้งาน --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="color:#5aa07e;"><i class="fas fa-circle-check"></i></div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1; color:#5aa07e;">{{ $activeCount }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:4px;">เปิดใช้งาน</div>
            </div>
        </div>
        {{-- ส่งไปแล้ว --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="color:#5689b8;"><i class="fas fa-paper-plane"></i></div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1; color:#5689b8;">{{ number_format($totalSent) }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:4px;">ส่งไปแล้ว (ครั้ง)</div>
            </div>
        </div>
    </div>

    {{-- ===== ⚙️ Master setting — เปิด/ปิดระบบสุ่มข้อความ ===== --}}
    <div class="tp-card">
        <form action="{{ route('admin.fortune.invite-messages.settings') }}" method="POST">
            @csrf
            <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                <input type="checkbox" name="enable_invite_rotation" value="1"
                       @checked($settings->enable_invite_rotation ?? true)
                       style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                <div>
                    <div style="font-weight:700; color:var(--ink);">เปิดใช้งานระบบสุ่มข้อความแทนรูป</div>
                    <div class="tp-muted" style="font-size:12px; margin-top:3px;">
                        ปิด = ส่งรูปแบนเนอร์ทุกครั้งตามเดิม (ไม่สลับเป็นข้อความ)
                    </div>
                </div>
            </label>

            {{-- 🌙 (2026-07-31) แนบกล่องดวงรายวันนำหน้า DM --}}
            <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; margin-top:16px; padding-top:16px; border-top:1px solid var(--line);">
                <input type="checkbox" name="dm_daily_horoscope_enabled" value="1"
                       @checked($settings->dm_daily_horoscope_enabled ?? false)
                       style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                <div>
                    <div style="font-weight:700; color:var(--ink);">🌙 ส่งดวงรายวันไปกับ DM ด้วย</div>
                    <div class="tp-muted" style="font-size:12px; margin-top:3px; line-height:1.6;">
                        เปิด = DM กลับหาลูกค้าจะมี<strong style="color:var(--ink);">กล่องดวงรายวัน</strong>นำหน้า
                        แล้วค่อยต่อด้วยข้อความ DM ปกติอีกหนึ่งกล่อง<br>
                        • ใช้บทความที่ AI สร้างทุกวัน <strong style="color:var(--ink);">06:00</strong> —
                        ส่งเฉพาะบทความ<strong style="color:var(--ink);">ของวันเดียวกัน</strong>เท่านั้น<br>
                        • ลูกค้าที่ระบบ<strong style="color:var(--ink);">ไม่มีวันเกิด</strong> → ส่งครบทั้ง 7 วันเกิด ให้เลือกอ่านเอง<br>
                        • ส่ง<strong style="color:var(--ink);">ครั้งแรกของวัน</strong>ครั้งเดียวต่อลูกค้า<br>
                        • วันไหนยังไม่มีบทความ → เหลือแค่ข้อความ DM ปกติ (ไม่มีกล่องดวง)
                    </div>
                </div>
            </label>

            {{-- 🎁 (2026-08-28) เตือนเมื่อสวิตช์ใหญ่ปิดอยู่ — ติ๊กช่องข้างบนแล้วไม่มีอะไรเกิดขึ้น
                 คือบั๊กที่หาสาเหตุยากที่สุดแบบหนึ่ง ต้องบอกตรงนี้ว่าตัวคุมจริงอยู่ที่ไหน --}}
            @unless($settings->daily_free_horoscope_enabled ?? true)
                <div class="tp-muted" style="margin-top:12px; padding:10px 12px; border:1px solid var(--line); border-radius:10px; font-size:12px; line-height:1.6;">
                    ⛔ <strong style="color:var(--ink);">ระบบชวนรับดวงรายวันฟรีถูกปิดอยู่</strong> —
                    ติ๊กช่องด้านบนไว้ก็จะยังไม่ส่งกล่องดวงรายวัน
                    <br>เปิดคืนได้ที่ <strong style="color:var(--ink);">ตั้งค่าดูดวง → โหมดบอท → 🎁 ระบบชวนรับดวงรายวันฟรี</strong>
                </div>
            @endunless

            <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="margin-top:16px;">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
        </form>
    </div>

    {{-- ===== 🌍 ตัวกรองกลุ่มเป้าหมาย DM กลับ (สัญชาติ + อายุ) ===== --}}
    <div class="tp-card"
         x-data="{
            sendForeigners: {{ ($settings->dm_send_to_foreigners ?? true) ? 'true' : 'false' }},
            ageEnabled: {{ ($settings->dm_filter_age_enabled ?? false) ? 'true' : 'false' }}
         }">
        <div class="tp-section-h" style="margin-bottom:4px;">
            <i class="fas fa-earth-asia" style="color:var(--accent1);"></i> กรองกลุ่มเป้าหมาย (DM กลับ)
        </div>
        <p class="tp-muted" style="font-size:12px; margin:0 0 16px;">
            เลือกได้ว่าจะ DM กลับหาคนที่คอมเมนต์/กดไลก์ ตามสัญชาติหรืออายุ —
            ใช้เฉพาะ DM อัตโนมัติ (ไม่กระทบคนที่ทักมาเอง/จ่ายเงิน)
        </p>

        {{-- ⚠️ ข้อจำกัดของ Facebook --}}
        <div class="tp-inset" style="padding:13px 15px; margin-bottom:16px; border-left:3px solid #e0a52e; font-size:12px; color:var(--ink2); line-height:1.55;">
            <span style="color:#e0a52e; font-weight:700;">⚠️ Facebook ไม่บอกสัญชาติ/อายุของคนคอมเมนต์โดยตรง</span> — ระบบจึง<strong style="color:var(--ink);">เดา</strong>ให้:
            <ul style="margin:6px 0 0; padding-left:18px; display:flex; flex-direction:column; gap:3px;">
                <li><strong style="color:var(--ink);">สัญชาติ</strong> → ดูจากตัวอักษรในชื่อ + ข้อความ (ไทย/ลาว/จีน/อังกฤษ ฯลฯ)</li>
                <li><strong style="color:var(--ink);">อายุ</strong> → รู้เฉพาะลูกค้าที่<strong style="color:var(--ink);">เคยกรอกวันเกิดตอนดูดวง</strong>มาก่อนเท่านั้น</li>
            </ul>
        </div>

        <form action="{{ route('admin.fortune.invite-messages.audience-filters') }}" method="POST">
            @csrf

            {{-- สัญชาติ --}}
            <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; margin-bottom:14px;">
                <input type="checkbox" name="dm_send_to_foreigners" value="1" x-model="sendForeigners"
                       style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                <div>
                    <div style="font-weight:700; color:var(--ink);">ส่ง DM ให้คนต่างชาติด้วย</div>
                    <div class="tp-muted" style="font-size:12px; margin-top:3px;">
                        ติ๊ก = ส่งทุกคน (ค่าเริ่มต้น) • ไม่ติ๊ก = ไม่ส่งให้คนที่ตรวจว่าเป็นต่างชาติ
                    </div>
                </div>
            </label>

            {{-- วิธีตรวจสัญชาติ (โชว์เมื่อเลือกไม่ส่งต่างชาติ) --}}
            <div x-show="!sendForeigners" x-cloak style="margin-left:32px; margin-bottom:18px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">วิธีตรวจว่าใคร "ต่างชาติ"</label>
                <div class="tp-well tp-input" style="padding:0; max-width:420px;">
                    <select name="dm_foreigner_detect_basis"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="script" @selected(($settings->dm_foreigner_detect_basis ?? 'script') === 'script')>
                            ดูจากชื่อเป็นภาษาต่างชาติ (ลาว/พม่า/เขมร/จีน/เกาหลี/อาหรับ ฯลฯ) ยกเว้นอังกฤษ — แนะนำ
                        </option>
                        <option value="no_thai" @selected(($settings->dm_foreigner_detect_basis ?? 'script') === 'no_thai')>
                            ไม่มีอักษรไทยเลย = ต่างชาติ (รวมชื่ออังกฤษ) — เข้มสุด
                        </option>
                        <option value="lao_only" @selected(($settings->dm_foreigner_detect_basis ?? 'script') === 'lao_only')>
                            เฉพาะคนลาว
                        </option>
                    </select>
                </div>
                <p class="tp-muted" style="font-size:12px; margin:6px 0 0;">
                    "แนะนำ" ปลอดภัยสุด — ไม่บล็อกคนไทยที่ตั้งชื่อ FB เป็นภาษาอังกฤษ
                </p>
            </div>

            <div class="tp-divider" style="margin:0 0 14px;"></div>

            {{-- อายุ --}}
            <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; margin-bottom:14px;">
                <input type="checkbox" name="dm_filter_age_enabled" value="1" x-model="ageEnabled"
                       style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                <div>
                    <div style="font-weight:700; color:var(--ink);">กรองตามอายุ</div>
                    <div class="tp-muted" style="font-size:12px; margin-top:3px;">
                        ส่งเฉพาะช่วงอายุที่กำหนด (มีผลเฉพาะคนที่เรารู้อายุ)
                    </div>
                </div>
            </label>

            <div x-show="ageEnabled" x-cloak style="margin-left:32px; margin-bottom:8px; display:flex; flex-direction:column; gap:14px;">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:14px; color:var(--ink);">
                    <span>อายุ</span>
                    <div class="tp-well tp-input" style="padding:0; width:90px;">
                        <input type="number" name="dm_age_min" min="0" max="120" value="{{ $settings->dm_age_min }}" placeholder="ต่ำสุด"
                               style="width:100%; background:transparent; border:0; outline:0; padding:9px 12px; color:var(--ink); font-size:14px;">
                    </div>
                    <span>ถึง</span>
                    <div class="tp-well tp-input" style="padding:0; width:90px;">
                        <input type="number" name="dm_age_max" min="0" max="120" value="{{ $settings->dm_age_max }}" placeholder="สูงสุด"
                               style="width:100%; background:transparent; border:0; outline:0; padding:9px 12px; color:var(--ink); font-size:14px;">
                    </div>
                    <span>ปี</span>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ถ้า "ไม่รู้อายุ" ของคนนั้น</label>
                    <div class="tp-well tp-input" style="padding:0; max-width:420px;">
                        <select name="dm_age_unknown_action"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                            <option value="send" @selected(($settings->dm_age_unknown_action ?? 'send') === 'send')>
                                ส่งตามปกติ (แนะนำ — คนส่วนใหญ่ไม่รู้อายุ)
                            </option>
                            <option value="skip" @selected(($settings->dm_age_unknown_action ?? 'send') === 'skip')>
                                ไม่ส่ง ⚠️ (บอทจะแทบไม่ DM ใครเลย)
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="margin-top:16px;">
                <i class="fas fa-floppy-disk"></i> บันทึกตัวกรอง
            </button>
        </form>
    </div>

    {{-- ===== 🗂️ เปิด/ปิดหมวดข้อความ ===== --}}
    @if($categoryStats->count() > 0)
    <div class="tp-card" x-data="{ open: {{ count($disabledCategories) > 0 ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open"
                style="width:100%; display:flex; align-items:center; justify-content:space-between; gap:12px; background:transparent; border:0; cursor:pointer; text-align:left; padding:0;">
            <div>
                <div class="tp-section-h" style="margin:0;">
                    <i class="fas fa-folder-tree" style="color:var(--accent1);"></i> เปิด/ปิดหมวดข้อความ
                </div>
                <p class="tp-muted" style="font-size:12px; margin:6px 0 0;">
                    ปิดหมวดไหน = บอทจะไม่สุ่มข้อความจากหมวดนั้นไปส่ง
                    @if(count($disabledCategories) > 0)
                        <span style="color:#e0a52e; font-weight:600;">• ปิดอยู่ {{ count($disabledCategories) }} หมวด</span>
                    @endif
                </p>
            </div>
            <span x-text="open ? '▲' : '▼'" style="color:var(--ink2); flex-shrink:0;"></span>
        </button>

        <div x-show="open" x-cloak x-transition style="margin-top:16px;">
            <form action="{{ route('admin.fortune.invite-messages.categories') }}" method="POST">
                @csrf
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px;">
                    <button type="button" class="tp-btn tp-btn-sm"
                            @click="$root.querySelectorAll('.cat-cb').forEach(c => c.checked = true)">
                        <i class="fas fa-circle-check"></i> เปิดทั้งหมด
                    </button>
                    <button type="button" class="tp-btn tp-btn-sm"
                            @click="$root.querySelectorAll('.cat-cb').forEach(c => c.checked = false)">
                        <i class="fas fa-circle-pause"></i> ปิดทั้งหมด
                    </button>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:9px;">
                    @foreach($categoryStats as $cat)
                        <label class="tp-inset-sm" style="display:flex; align-items:center; gap:9px; padding:10px 12px; border-radius:11px; cursor:pointer;">
                            <input type="checkbox" class="cat-cb"
                                   name="enabled_categories[]" value="{{ $cat['category'] }}"
                                   @checked($cat['enabled'])
                                   style="width:17px; height:17px; accent-color:var(--accent1); cursor:pointer;">
                            <span style="font-size:13px; color:var(--ink); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $cat['category'] }}">{{ $cat['category'] }}</span>
                            <span style="font-size:11px; flex-shrink:0; {{ $cat['enabled'] ? 'color:var(--ink2);' : 'color:#e0a52e; font-weight:600;' }}">
                                {{ $cat['active'] }}/{{ $cat['total'] }}{{ $cat['enabled'] ? '' : ' ⏸️' }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="margin-top:16px;">
                    <i class="fas fa-floppy-disk"></i> บันทึกหมวด
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ===== ➕ ฟอร์มเพิ่มข้อความใหม่ ===== --}}
    <div x-show="showAdd" x-cloak x-transition class="tp-card">
        <div class="tp-section-h" style="margin-bottom:14px;">
            <i class="fas fa-plus" style="color:var(--accent1);"></i> เพิ่มข้อความใหม่
        </div>
        <form action="{{ route('admin.fortune.invite-messages.store') }}" method="POST">
            @csrf
            <div class="tp-well tp-input" style="padding:0;">
                <textarea name="message" rows="3" maxlength="1000" required
                          placeholder="เช่น 🌙 ช่วงนี้ดาวกำลังเปลี่ยนผ่านนะคะคุณ{name} ถ้าอยากรู้ว่าควรไปทางไหน ทักมาหาแม่หมอได้เลยค่ะ"
                          style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
            </div>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:14px;">
                <div class="tp-well tp-input" style="padding:0; width:240px;">
                    <input type="text" name="category" list="categoryList" placeholder="หมวด (เช่น timing, love)"
                           style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                </div>
                {{-- 🔀 (2026-07-28) โหมดที่ใช้ข้อความนี้ — กันข้อความชวน "ทักมาในแชท" หลุดไปโหมดพาไปเว็บ --}}
                <div class="tp-well tp-input" style="padding:0; width:210px;">
                    <select name="mode" title="โหมดที่ใช้ข้อความนี้"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                        <option value="all">ใช้ได้ทุกโหมด</option>
                        <option value="classic">เฉพาะโหมดเดิม (คุยในแชท)</option>
                        <option value="transfer">เฉพาะโหมดพาไปเว็บ/LINE</option>
                        <option value="daily">เฉพาะโหมดดวงรายวัน (ขอวันเกิด)</option>
                    </select>
                </div>

                {{-- ⏰ (2026-08-08) ช่วงเวลาที่ส่งได้ — เว้นว่าง = ทุกเวลา (พฤติกรรมเดิม) --}}
                <div style="display:flex; align-items:center; gap:7px; font-size:13px; color:var(--ink2);">
                    <span>⏰ ส่งช่วง</span>
                    <div class="tp-well tp-input" style="padding:0; width:72px;">
                        <input type="number" name="hour_from" min="0" max="23" placeholder="—"
                               style="width:100%; background:transparent; border:0; outline:0; padding:11px 10px; color:var(--ink); font-size:14px; text-align:center;">
                    </div>
                    <span>ถึง</span>
                    <div class="tp-well tp-input" style="padding:0; width:72px;">
                        <input type="number" name="hour_to" min="0" max="23" placeholder="—"
                               style="width:100%; background:transparent; border:0; outline:0; padding:11px 10px; color:var(--ink); font-size:14px; text-align:center;">
                    </div>
                    <span>น.</span>
                </div>

                <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:var(--ink); cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" checked
                           style="width:17px; height:17px; accent-color:var(--accent1); cursor:pointer;">
                    เปิดใช้งานทันที
                </label>
                <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm" style="margin-left:auto;">
                    <i class="fas fa-floppy-disk"></i> บันทึก
                </button>
            </div>
            <p class="tp-muted" style="font-size:12px; margin:10px 0 0; line-height:1.6;">
                ⏰ <strong style="color:var(--ink);">เว้นว่างทั้งคู่ = ส่งได้ทุกเวลา</strong> •
                ใส่ <code class="tp-pill tp-pill-soft" style="padding:1px 6px;">5</code> ถึง
                <code class="tp-pill tp-pill-soft" style="padding:1px 6px;">9</code> = ส่งเฉพาะ 05:00–09:59 •
                ใส่ <code class="tp-pill tp-pill-soft" style="padding:1px 6px;">21</code> ถึง
                <code class="tp-pill tp-pill-soft" style="padding:1px 6px;">2</code> = 21:00–02:59 (ข้ามคืนได้)
            </p>
        </form>
    </div>

    {{-- datalist หมวด (สำหรับ autocomplete ในฟอร์มเพิ่ม/แก้ไข) — $categories มาจาก controller (ทุกหมวด) --}}
    <datalist id="categoryList">
        @foreach($categories as $cat)
            <option value="{{ $cat }}"></option>
        @endforeach
    </datalist>

    {{-- ===== 🔎 ตัวกรองหมวด (pill — server-side, กรองครบทุกหน้า) ===== --}}
    @if($categories->count() > 1)
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <a href="{{ route('admin.fortune.invite-messages.index') }}"
               class="tp-pill {{ $curCategory === '' ? 'tp-pill-gold' : 'tp-pill-soft' }}"
               style="text-decoration:none; font-size:12px;">ทั้งหมด</a>
            @foreach($categories as $cat)
                <a href="{{ route('admin.fortune.invite-messages.index', ['category' => $cat]) }}"
                   class="tp-pill {{ $curCategory === $cat ? 'tp-pill-gold' : 'tp-pill-soft' }}"
                   style="text-decoration:none; font-size:12px;">{{ $cat }}</a>
            @endforeach
        </div>
    @endif

    {{-- ===== 📋 รายการข้อความ ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($messages->isEmpty())
            {{-- empty state --}}
            <div style="text-align:center; padding:48px 20px; color:var(--ink2);">
                <i class="fas fa-inbox" style="font-size:38px; opacity:.5;"></i>
                <p style="margin:14px 0 4px; font-weight:600;">ยังไม่มีข้อความ</p>
                <p style="font-size:12px; margin:0;">รัน <code class="tp-pill tp-pill-soft" style="padding:1px 7px;">php artisan db:seed --class=FortuneInviteMessageSeeder</code> เพื่อใส่ 100 ข้อความเริ่มต้น</p>
            </div>
        @else
            <div style="display:flex; flex-direction:column;">
                @foreach($messages as $msg)
                    {{-- ประกอบ payload ของปุ่มแก้ไขไว้ก่อน — ส่งเป็นก้อนเดียวเข้า openEdit()
                         (อาร์กิวเมนต์เรียงตำแหน่งเยอะเกินจะสลับกันเองโดยไม่รู้ตัว) --}}
                    @php
                        $editPayload = [
                            'id' => $msg->id,
                            'message' => $msg->message,
                            'category' => $msg->category,
                            'mode' => $msg->mode ?? 'all',
                            'hour_from' => $msg->hour_from,
                            'hour_to' => $msg->hour_to,
                            'is_active' => (bool) $msg->is_active,
                        ];
                        $windowLabel = $msg->timeWindowLabel();
                    @endphp
                    <div style="display:flex; align-items:flex-start; gap:13px; padding:16px 18px; box-shadow:var(--inset-sm);">
                        {{-- ลำดับ (เลขต่อเนื่องข้ามหน้า) --}}
                        <div class="tp-num" style="flex-shrink:0; width:32px; height:32px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center; font-size:12px; color:var(--ink2);">
                            {{ $messages->firstItem() + $loop->index }}
                        </div>

                        {{-- เนื้อหา --}}
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:14px; color:var(--ink); white-space:pre-wrap; word-break:break-word; line-height:1.55; {{ $msg->is_active ? '' : 'opacity:.5; text-decoration:line-through;' }}">
                                {{ $msg->message }}
                            </div>
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:9px; margin-top:9px; font-size:12px;">
                                <span class="tp-pill tp-pill-soft" style="font-size:11px;">{{ $msg->category }}</span>
                                {{-- 🔀 (2026-07-28) ป้ายโหมด — เห็นชัดว่าข้อความไหนใช้ตอนไหน --}}
                                @if(($msg->mode ?? 'all') === 'transfer')
                                    <span class="tp-pill" style="font-size:11px; color:#2e7d64; font-weight:600;">🔀 โหมดพาไปเว็บ/LINE</span>
                                @elseif(($msg->mode ?? 'all') === 'classic')
                                    <span class="tp-pill" style="font-size:11px; color:#8a6d3b; font-weight:600;">💬 โหมดเดิม</span>
                                @elseif(($msg->mode ?? 'all') === 'daily')
                                    <span class="tp-pill" style="font-size:11px; color:#5689b8; font-weight:600;">🌙 โหมดดวงรายวัน</span>
                                @endif
                                {{-- ⏰ (2026-08-08) ป้ายช่วงเวลา — ไม่มีป้าย = ส่งได้ทุกเวลา --}}
                                @if($windowLabel)
                                    <span class="tp-pill" style="font-size:11px; color:#8a5cb8; font-weight:600;" title="บอทจะสุ่มข้อความนี้เฉพาะช่วงเวลานี้">⏰ {{ $windowLabel }}</span>
                                @endif
                                <span class="tp-muted" style="font-size:11px;">
                                    <i class="fas fa-paper-plane" style="font-size:10px;"></i> ส่งไปแล้ว {{ number_format($msg->send_count) }} ครั้ง
                                </span>
                                @unless($msg->is_active)
                                    <span style="color:#e0a52e; font-weight:600; font-size:11px;">⏸️ ปิดอยู่</span>
                                @endunless
                            </div>
                        </div>

                        {{-- ปุ่ม action --}}
                        <div style="flex-shrink:0; display:flex; align-items:center; gap:5px;">
                            <button type="button" class="tp-icon-btn" title="แก้ไข"
                                    @click="openEdit(@js($editPayload))">
                                <i class="fas fa-pen" style="color:#5689b8;"></i>
                            </button>
                            <form action="{{ route('admin.fortune.invite-messages.toggle', $msg) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="tp-icon-btn" title="{{ $msg->is_active ? 'ปิด' : 'เปิด' }}">
                                    @if($msg->is_active)
                                        <i class="fas fa-pause" style="color:#e0a52e;"></i>
                                    @else
                                        <i class="fas fa-play" style="color:#5aa07e;"></i>
                                    @endif
                                </button>
                            </form>
                            <form action="{{ route('admin.fortune.invite-messages.destroy', $msg) }}" method="POST" style="display:inline;"
                                  onsubmit="return confirm('ลบข้อความนี้?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="tp-icon-btn" title="ลบ">
                                    <i class="fas fa-trash" style="color:#d9534f;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ===== 📄 แบ่งหน้า ===== --}}
    @if($messages->hasPages())
        <div class="tp-num" style="display:flex; justify-content:center;">{{ $messages->withQueryString()->links() }}</div>
    @endif

    {{-- ===== ✏️ Modal แก้ไขข้อความ ===== --}}
    <div x-show="editing.show" x-cloak
         style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,.5);"
         @click.self="editing.show = false">
        <div class="tp-card tp-raise" x-show="editing.show" x-transition
             style="width:100%; max-width:520px;">
            <div class="tp-section-h" style="margin-bottom:14px;">
                <i class="fas fa-pen" style="color:var(--accent1);"></i> แก้ไขข้อความ
            </div>
            <form :action="updateUrl" method="POST">
                @csrf
                @method('PUT')
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="message" rows="4" maxlength="1000" required x-model="editing.message"
                              style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
                </div>
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:14px;">
                    <div class="tp-well tp-input" style="padding:0; width:200px;">
                        <input type="text" name="category" list="categoryList" x-model="editing.category" placeholder="หมวด"
                               style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                    </div>
                    {{-- 🔀 (2026-07-28) โหมดที่ใช้ข้อความนี้ --}}
                    <div class="tp-well tp-input" style="padding:0; width:200px;">
                        <select name="mode" x-model="editing.mode"
                                style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;">
                            <option value="all">ใช้ได้ทุกโหมด</option>
                            <option value="classic">เฉพาะโหมดเดิม</option>
                            <option value="transfer">เฉพาะโหมดพาไปเว็บ/LINE</option>
                            <option value="daily">เฉพาะโหมดดวงรายวัน</option>
                        </select>
                    </div>
                    <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:var(--ink); cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" x-model="editing.is_active"
                               style="width:17px; height:17px; accent-color:var(--accent1); cursor:pointer;">
                        เปิดใช้งาน
                    </label>
                </div>

                {{-- ⏰ (2026-08-08) ช่วงเวลาที่ส่งได้ — เว้นว่าง = ทุกเวลา --}}
                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:7px; margin-top:14px; font-size:13px; color:var(--ink2);">
                    <span>⏰ ส่งช่วง</span>
                    <div class="tp-well tp-input" style="padding:0; width:72px;">
                        <input type="number" name="hour_from" min="0" max="23" placeholder="—" x-model="editing.hour_from"
                               style="width:100%; background:transparent; border:0; outline:0; padding:10px; color:var(--ink); font-size:14px; text-align:center;">
                    </div>
                    <span>ถึง</span>
                    <div class="tp-well tp-input" style="padding:0; width:72px;">
                        <input type="number" name="hour_to" min="0" max="23" placeholder="—" x-model="editing.hour_to"
                               style="width:100%; background:transparent; border:0; outline:0; padding:10px; color:var(--ink); font-size:14px; text-align:center;">
                    </div>
                    <span>น.</span>
                    <button type="button" class="tp-btn tp-btn-sm" style="margin-left:auto;"
                            @click="editing.hour_from = ''; editing.hour_to = ''">
                        ล้าง (ทุกเวลา)
                    </button>
                </div>
                <p class="tp-muted" style="font-size:12px; margin:8px 0 0; line-height:1.55;"
                   x-text="windowHint()"></p>
                <div style="display:flex; justify-content:flex-end; gap:9px; margin-top:20px;">
                    <button type="button" class="tp-btn tp-btn-sm" @click="editing.show = false">
                        ยกเลิก
                    </button>
                    <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm">
                        <i class="fas fa-floppy-disk"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Alpine component สำหรับหน้าคลังข้อความเชิญชวน (logic เดิมทั้งหมด — ย้ายมาไว้ scripts stack ท้าย body)
    function inviteMessages() {
        return {
            showAdd: false,
            filter: '',
            // base URL ที่มี __ID__ ไว้แทนด้วย id จริงตอนเปิด modal
            updateUrlBase: "{{ route('admin.fortune.invite-messages.update', '__ID__') }}",
            updateUrl: '',
            editing: { show: false, id: null, message: '', category: '', mode: 'all', hour_from: '', hour_to: '', is_active: true },

            // รับเป็นก้อนเดียว (@js($editPayload)) — เพิ่มฟิลด์ใหม่แล้วไม่ต้องแก้ลำดับอาร์กิวเมนต์
            openEdit(data) {
                this.editing = {
                    show: true,
                    id: data.id,
                    message: data.message || '',
                    category: data.category || '',
                    mode: data.mode || 'all',
                    // ⏰ null (= ทุกเวลา) ต้องกลายเป็นช่องว่าง ไม่ใช่คำว่า "null"
                    hour_from: data.hour_from ?? '',
                    hour_to: data.hour_to ?? '',
                    is_active: data.is_active,
                };
                this.updateUrl = this.updateUrlBase.replace('__ID__', data.id);
            },

            // ⏰ อธิบายช่วงเวลาที่กรอกอยู่ให้เป็นภาษาคน (กันแอดมินตีความ 21-2 ผิด)
            windowHint() {
                const from = this.editing.hour_from;
                const to = this.editing.hour_to;
                const blank = (v) => v === '' || v === null || v === undefined;

                if (blank(from) && blank(to)) {
                    return '⏰ ส่งได้ทุกเวลา (ไม่จำกัดช่วงเวลา)';
                }
                if (blank(from) || blank(to)) {
                    return '⚠️ กรอกไม่ครบทั้งสองช่อง — ระบบจะถือว่า "ส่งได้ทุกเวลา"';
                }

                const f = Number(from), t = Number(to);
                const pad = (n) => String(n).padStart(2, '0');
                const range = `${pad(f)}:00–${pad(t)}:59`;

                return f > t
                    ? `⏰ ส่งเฉพาะ ${range} (ข้ามคืน)`
                    : `⏰ ส่งเฉพาะ ${range}`;
            },
        };
    }
</script>
@endpush

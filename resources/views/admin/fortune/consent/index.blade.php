@extends('layouts.admin-v4')

@section('title', 'กติกาก่อนจองคิว')

@section('content')
@php
    // ===== ป้ายบอก scope ของแต่ละรูป (V4 — pill ทองคำ/สถานะ) =====
    // คงค่า key/scope เดิมทุกตัว (consent|cancel|expire|both|all) — ใช้สี STATUS V4
    $scopeBadges = [
        'consent' => ['📜 อ่านกติกา', '#b79ae8'],
        'cancel'  => ['🚫 ตอนยกเลิก', '#d9534f'],
        'expire'  => ['⏰ หมดเวลาเอง', '#e0a52e'],
        'both'    => ['🔁 กติกา+ยกเลิก', '#5aa07e'],
        'all'     => ['🌐 ทุกจังหวะ', '#5689b8'],
    ];
    // นับสรุปสำหรับ KPI
    $activeCount = $images->where('is_active', true)->count();
    $totalSent   = $images->sum('send_count');
@endphp

{{-- ภาชนะหลักของหน้า --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · กติกาก่อนจองคิว</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">กติกาก่อนจองคิว 📜</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">ข้อความกติกา + คลังรูปเตือน ที่เด้งก่อนสร้างบิลค่าครู</p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.consent.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i> อัปโหลดรูปใหม่
            </a>
        </div>
    </div>

    {{-- ===== ⚠️ ข้อผิดพลาดจากการตรวจสอบ (validation) =====
         layout admin-v4 โชว์เฉพาะ session flash แบบ toast แต่ไม่ render $errors
         → ใส่ block นี้กันผู้ใช้กรอกผิด (เช่น ข้อความยาวเกิน 5000) แล้ว submit เงียบ --}}
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

    {{-- ===== ℹ️ คำอธิบายการทำงาน ===== --}}
    <div class="tp-card" style="border-left:4px solid var(--accent1);">
        <div class="tp-section-h" style="margin-bottom:8px;">
            <i class="fas fa-circle-info" style="color:var(--accent1);"></i> ระบบนี้ทำงานยังไง
        </div>
        <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:5px; font-size:13px; color:var(--ink2); line-height:1.55;">
            <li>กล่องกติกาจะ<strong style="color:var(--ink);">เด้งทุกครั้งก่อนสร้างบิลค่าครู</strong> ให้ลูกค้าอ่าน + กดยืนยัน "พร้อมบูชาครู" ก่อนจึงออก QR</li>
            <li><strong style="color:var(--ink);">คำเตือนตอนยกเลิก</strong> จะส่งเฉพาะคนที่<strong style="color:var(--ink);">สร้างบิลแล้วกดยกเลิกแบบเบี้ยว</strong></li>
            <li>ถ้าเป็นเหตุสุดวิสัย (โอนไม่ได้/แอพมีปัญหา) บอท<strong style="color:var(--ink);">จะไม่เตือนแรง</strong></li>
        </ul>
    </div>

    {{-- ===== 📊 KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        {{-- รูปทั้งหมด --}}
        <div class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile"><i class="fas fa-images"></i></div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ $images->count() }}</div>
                <div class="tp-muted" style="font-size:12px; margin-top:4px;">รูปในคลังทั้งหมด</div>
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

    {{-- ===== ⚙️ ตั้งค่ากติกา ===== --}}
    {{-- คง action=consent.settings, method POST, @csrf, ทุก name field เดิมเป๊ะ --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:16px;">
            <i class="fas fa-sliders" style="color:var(--accent1);"></i> ตั้งค่ากติกา
        </div>

        <form action="{{ route('admin.fortune.consent.settings') }}" method="POST">
            @csrf

            {{-- toggle 3 ตัว (เปิดกล่อง / เตือนยกเลิก / เตือนหมดเวลา) --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:12px; margin-bottom:18px;">
                <label class="tp-inset" style="display:flex; align-items:flex-start; gap:12px; padding:14px; cursor:pointer;">
                    <input type="checkbox" name="fortune_consent_enabled" value="1"
                           @checked($settings->fortune_consent_enabled ?? true)
                           style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                    <div>
                        <div style="font-weight:700; color:var(--ink);">เปิดกล่องกติกาก่อนสร้างบิล</div>
                        <div class="tp-muted" style="font-size:12px; margin-top:3px;">เด้งให้ยืนยันก่อนออก QR ทุกบิล</div>
                    </div>
                </label>

                <label class="tp-inset" style="display:flex; align-items:flex-start; gap:12px; padding:14px; cursor:pointer;">
                    <input type="checkbox" name="fortune_consent_cancel_enabled" value="1"
                           @checked($settings->fortune_consent_cancel_enabled ?? true)
                           style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                    <div>
                        <div style="font-weight:700; color:var(--ink);">เตือน + ส่งรูปตอนยกเลิก</div>
                        <div class="tp-muted" style="font-size:12px; margin-top:3px;">เฉพาะเจตนาเบี้ยว (ไม่ใช่เหตุสุดวิสัย)</div>
                    </div>
                </label>

                <label class="tp-inset" style="display:flex; align-items:flex-start; gap:12px; padding:14px; cursor:pointer;">
                    <input type="checkbox" name="fortune_consent_expire_enabled" value="1"
                           @checked($settings->fortune_consent_expire_enabled ?? true)
                           style="width:20px; height:20px; margin-top:2px; accent-color:var(--accent1); cursor:pointer;">
                    <div>
                        <div style="font-weight:700; color:var(--ink);">เตือนตอนบิลหมดเวลาเอง</div>
                        <div class="tp-muted" style="font-size:12px; margin-top:3px;">โทนนุ่ม (อาจแค่ลืม) + รูป scope=หมดเวลา</div>
                    </div>
                </label>
            </div>

            {{-- กลยุทธ์การสุ่มรูป (select V4) --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:var(--ink2); margin-bottom:6px;">กลยุทธ์การสุ่มรูป</label>
                <div class="tp-well tp-input" style="padding:0; max-width:420px;">
                    <select name="fortune_consent_pick_strategy"
                            style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                        <option value="random" @selected(($settings->fortune_consent_pick_strategy ?? 'random') === 'random')>🎲 สุ่ม (random)</option>
                        <option value="rotation" @selected(($settings->fortune_consent_pick_strategy ?? '') === 'rotation')>🔄 วนรอบ (rotation)</option>
                        <option value="sequential" @selected(($settings->fortune_consent_pick_strategy ?? '') === 'sequential')>📋 ตามลำดับ (sequential)</option>
                    </select>
                </div>
            </div>

            {{-- ข้อความกติกา (เด้งก่อนสร้างบิล) --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    📜 ข้อความกติกา (เด้งก่อนสร้างบิล)
                    <span class="tp-muted" style="font-weight:400;">— เว้นว่าง = ใช้ข้อความเริ่มต้น</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="fortune_consent_text" rows="14"
                              style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:13px; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; line-height:1.6; resize:vertical;"
                              placeholder="{{ $defaultConsentText }}">{{ old('fortune_consent_text', $settings->fortune_consent_text) }}</textarea>
                </div>
            </div>

            {{-- ข้อความเตือนตอนยกเลิก (เจตนาเบี้ยว) --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    🚫 ข้อความเตือนตอนลูกค้ายกเลิกบิล (เจตนาเบี้ยว)
                    <span class="tp-muted" style="font-weight:400;">— เว้นว่าง = ใช้ข้อความเริ่มต้น</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="fortune_consent_cancel_text" rows="9"
                              style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:13px; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; line-height:1.6; resize:vertical;"
                              placeholder="{{ $defaultCancelText }}">{{ old('fortune_consent_cancel_text', $settings->fortune_consent_cancel_text) }}</textarea>
                </div>
            </div>

            {{-- ข้อความตอนบิลหมดเวลาเอง (โทนนุ่ม) --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    ⏰ ข้อความตอนบิลหมดเวลาเอง (โทนนุ่ม)
                    <span class="tp-muted" style="font-weight:400;">— เว้นว่าง = ใช้ข้อความเริ่มต้น</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="fortune_consent_expire_text" rows="8"
                              style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:13px; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; line-height:1.6; resize:vertical;"
                              placeholder="{{ $defaultExpireText }}">{{ old('fortune_consent_expire_text', $settings->fortune_consent_expire_text) }}</textarea>
                </div>
            </div>

            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึกการตั้งค่า
            </button>
        </form>
    </div>

    {{-- ===== 🖼️ คลังรูปกติกา ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:16px;">
            <i class="fas fa-photo-film" style="color:var(--accent1);"></i> คลังรูปกติกา
            <span class="tp-pill tp-pill-soft" style="margin-left:6px; font-size:12px;">{{ $images->count() }}</span>
        </div>

        @if($images->isEmpty())
            {{-- empty state --}}
            <div style="text-align:center; padding:48px 16px; color:var(--ink2);">
                <i class="fas fa-inbox" style="font-size:40px; opacity:.4;"></i>
                <p style="margin:14px 0 4px; font-size:14px;">ยังไม่มีรูป — กล่องกติกาจะส่งเฉพาะ "ข้อความ" (ไม่มีรูป)</p>
                <a href="{{ route('admin.fortune.consent.create') }}" class="tp-btn tp-btn-primary tp-btn-sm" style="margin-top:14px;">
                    <i class="fas fa-plus"></i> อัปโหลดรูปแรก
                </a>
            </div>
        @else
            {{-- การ์ดกริดรูป --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px;">
                @foreach($images as $image)
                    @php [$badgeLabel, $badgeColor] = $scopeBadges[$image->usage_scope] ?? $scopeBadges['both']; @endphp
                    <div class="tp-inset" style="overflow:hidden; display:flex; flex-direction:column; padding:0;">

                        {{-- รูป + overlay สถานะ --}}
                        <div style="position:relative; aspect-ratio:16/9; background:var(--sd); overflow:hidden;">
                            <img src="{{ $image->image_url }}" alt="{{ $image->name }}"
                                 style="width:100%; height:100%; object-fit:cover; display:block;" loading="lazy">

                            @if(! $image->is_active)
                                <div style="position:absolute; inset:0; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center;">
                                    <span style="color:#fff; font-weight:800; letter-spacing:.5px;">ปิดใช้งาน</span>
                                </div>
                            @endif

                            {{-- ลำดับ --}}
                            <div style="position:absolute; top:8px; left:8px; padding:2px 8px; border-radius:8px; background:rgba(0,0,0,.6); color:#fff; font-size:11px; font-weight:700;">#{{ $image->sort_order }}</div>
                            {{-- ป้าย scope --}}
                            <span style="position:absolute; top:8px; right:8px; padding:2px 9px; border-radius:8px; font-size:11px; font-weight:700; color:#fff; background:{{ $badgeColor }};">{{ $badgeLabel }}</span>
                        </div>

                        {{-- เนื้อหา --}}
                        <div style="padding:14px; display:flex; flex-direction:column; flex:1;">
                            <div style="font-weight:700; color:var(--ink); margin-bottom:4px;">{{ $image->name }}</div>
                            @if($image->description)
                                <div class="tp-muted" style="font-size:12px; margin-bottom:8px; line-height:1.5;">{{ Str::limit($image->description, 60) }}</div>
                            @endif
                            <div class="tp-muted" style="font-size:12px; margin-bottom:12px;">
                                📐 {{ $image->width }}×{{ $image->height }}
                                • 📤 ส่งไปแล้ว {{ number_format($image->send_count) }} ครั้ง
                            </div>

                            {{-- ปุ่มจัดการ: แก้ไข / เปิด-ปิด / ลบ (คง form action/method/@csrf/@method เดิม) --}}
                            <div style="display:flex; gap:8px; margin-top:auto;">
                                <a href="{{ route('admin.fortune.consent.edit', $image) }}"
                                   class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">
                                    <i class="fas fa-pen"></i> แก้ไข
                                </a>

                                <form action="{{ route('admin.fortune.consent.toggle', $image) }}" method="POST" style="flex:1;">
                                    @csrf
                                    <button type="submit" class="tp-btn tp-btn-sm" style="width:100%; justify-content:center; color:{{ $image->is_active ? '#e0a52e' : '#5aa07e' }};">
                                        @if($image->is_active)
                                            <i class="fas fa-pause"></i> ปิด
                                        @else
                                            <i class="fas fa-play"></i> เปิด
                                        @endif
                                    </button>
                                </form>

                                <form action="{{ route('admin.fortune.consent.destroy', $image) }}" method="POST"
                                      onsubmit="return confirm('ลบรูปนี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tp-icon-btn" style="color:#d9534f;" title="ลบรูป">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection

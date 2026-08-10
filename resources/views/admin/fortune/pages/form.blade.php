@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- 🏬 ฟอร์มสาขา — เพิ่ม/แก้ไขเพจแม่หมอ --}}
@php
    $isEdit = $page->exists;
    $action = $isEdit
        ? route('admin.fortune.pages.update', $page)
        : route('admin.fortune.pages.store');
    $inputStyle = 'width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;';
@endphp

<div style="display:flex; flex-direction:column; gap:18px; max-width:900px;">

    <div>
        <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · สาขา</div>
        <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">{{ $pageTitle }}</h1>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid #d9534f;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- ===== ข้อมูลสาขา ===== --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-store"></i> ข้อมูลสาขา</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Facebook Page ID *</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="external_page_id" required value="{{ old('external_page_id', $page->external_page_id) }}"
                               placeholder="ตัวเลขล้วน" style="{{ $inputStyle }} font-family:monospace;">
                    </div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">ต้องตรงกับ entry.id ที่ Facebook ส่งมาใน webhook</div>
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชื่อเพจ</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="name" value="{{ old('name', $page->name) }}"
                               placeholder="เว้นว่าง = ระบบตั้งให้" style="{{ $inputStyle }}">
                    </div>
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ช่องทาง *</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="platform" style="{{ $inputStyle }} cursor:pointer;">
                            <option value="facebook" {{ old('platform', $page->platform) === 'facebook' ? 'selected' : '' }}>Facebook</option>
                            <option value="line" {{ old('platform', $page->platform) === 'line' ? 'selected' : '' }}>LINE</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ⚙️ ของที่ปกติไม่ต้องแตะ — ซ่อนไว้ให้ฟอร์มไม่รก --}}
            <details style="margin-top:14px;">
                <summary style="cursor:pointer; font-size:12.5px; color:var(--ink2);">ตั้งค่าเพิ่มเติม (ปกติไม่ต้องแตะ)</summary>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-top:12px;">
                    <div>
                        <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">รหัสสาขา (a-z, 0-9, ขีดกลาง)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="code" value="{{ old('code', $page->code) }}"
                                   placeholder="เว้นว่าง = ระบบตั้งให้" style="{{ $inputStyle }}">
                        </div>
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชื่อแบรนด์ (ลูกค้าเห็น)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="brand_name" value="{{ old('brand_name', $page->brand_name) }}"
                                   placeholder="เว้นว่าง = ใช้ชื่อแบรนด์กลาง" style="{{ $inputStyle }}">
                        </div>
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">เจ้าของสาขา (user id)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="number" name="owner_user_id" value="{{ old('owner_user_id', $page->owner_user_id) }}"
                                   placeholder="เว้นว่างได้" style="{{ $inputStyle }}">
                        </div>
                    </div>
                </div>
            </details>

            <div style="display:flex; gap:20px; margin-top:16px; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active ?? true) ? 'checked' : '' }}>
                    เปิดใช้งานสาขานี้
                </label>
                <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; cursor:pointer;">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $page->is_default) ? 'checked' : '' }}>
                    ตั้งเป็นสาขาหลัก (fallback เมื่อหาเพจไม่เจอ)
                </label>
            </div>
        </div>

        {{-- ===== ความลับ ===== --}}
        <div class="tp-card" style="padding:22px;">
            <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-key"></i> กุญแจของเพจ</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:14px; line-height:1.9;">
                @if($isEdit)
                    🔒 เว้นว่าง = <strong>คงค่าเดิม</strong> (ระบบไม่แสดงค่าที่บันทึกไว้ออกมา)
                @endif
                <br>App Secret / Verify Token กรอกเฉพาะเมื่อเพจนี้อยู่ <strong>คนละ Meta App</strong> กับเพจอื่น
                — ถ้าอยู่แอปเดียวกันให้เว้นว่าง ระบบจะใช้ค่ากลาง
            </div>

            <div style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                        Page Access Token {{ $isEdit && $page->page_access_token ? '(ตั้งค่าไว้แล้ว)' : '*' }}
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="password" name="page_access_token" autocomplete="new-password"
                               placeholder="{{ $isEdit && $page->page_access_token ? 'เว้นว่าง = ไม่เปลี่ยน' : 'EAAB...' }}"
                               style="{{ $inputStyle }} font-family:monospace;">
                    </div>
                </div>

                <details>
                    <summary style="cursor:pointer; font-size:12.5px; color:var(--ink2);">เพจนี้อยู่คนละ Meta App (ปกติไม่ต้องแตะ)</summary>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-top:12px;">
                        <div>
                            <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                App Secret {{ $page->app_secret ? '(ตั้งค่าไว้แล้ว)' : '' }}
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="password" name="app_secret" autocomplete="new-password"
                                       placeholder="เว้นว่าง = ใช้ค่ากลาง" style="{{ $inputStyle }} font-family:monospace;">
                            </div>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                                Verify Token {{ $page->verify_token ? '(ตั้งค่าไว้แล้ว)' : '' }}
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="password" name="verify_token" autocomplete="new-password"
                                       placeholder="เว้นว่าง = ใช้ค่ากลาง" style="{{ $inputStyle }} font-family:monospace;">
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        {{-- ===== ค่าเฉพาะสาขา (ซ่อนไว้ ไม่ใช่ของที่ต้องใช้ทุกวัน) ===== --}}
        <details class="tp-card" style="padding:22px;">
            <summary style="cursor:pointer; font-weight:700;"><i class="fas fa-sliders"></i> ค่าที่ต่างจากสาขากลาง (ไม่ใส่ก็ได้)</summary>
            <div style="font-size:12.5px; color:var(--ink2); margin:14px 0; line-height:1.9;">
                ใส่เป็น JSON เฉพาะคีย์ที่อยากให้ต่าง — ที่เหลือใช้ค่าจากหน้า “ตั้งค่าระบบดูดวง” ทั้งหมด<br>
                ตัวอย่าง: <code>{"deep_reading_price": 49, "fortune_brand_name": "แม่หมอนิด", "is_enabled": true}</code><br>
                ⚠️ ชื่อคีย์ต้องตรงกับชื่อคอลัมน์จริงของตาราง <code>fortune_telling_settings</code> — คีย์ที่ไม่มีอยู่จริงจะถูกข้ามเงียบๆ
            </div>
            <div class="tp-well tp-input" style="padding:0;">
                <textarea name="settings_override_json" rows="6"
                          placeholder='{ }'
                          style="{{ $inputStyle }} font-family:monospace; resize:vertical;">{{ old('settings_override_json', $page->settings_override ? json_encode($page->settings_override, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
            </div>
        </details>

        {{-- ===== หมายเหตุ ===== --}}
        <div class="tp-card" style="padding:22px;">
            <label style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมายเหตุ</label>
            <div class="tp-well tp-input" style="padding:0;">
                <textarea name="notes" rows="3" style="{{ $inputStyle }} resize:vertical;">{{ old('notes', $page->notes) }}</textarea>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-floppy-disk"></i> บันทึก</button>
            <a href="{{ route('admin.fortune.pages.index') }}" class="tp-btn">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

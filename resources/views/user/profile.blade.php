@extends('layouts.user-v4')

@section('title', 'จัดการโปรไฟล์')

@php
    use Illuminate\Support\Facades\Route as RouteFacade;

    // ── สถานะ KYC → ป้ายสี (hex แทน dynamic tailwind) ──────────
    $kycVerified = $user->isKycVerified();
    $kycPending  = $user->isKycPending();
@endphp

@section('content')
{{--
/**
 * จัดการโปรไฟล์ผู้ใช้ — Theme V4 "นวลทองคำ"
 *
 * หน้าจัดการโปรไฟล์ผู้ใช้พร้อม:
 * - อัปโหลด Avatar พร้อมพรีวิวสด (WebP)
 * - แก้ไขข้อมูลส่วนตัว / ติดต่อ / ที่อยู่ / ที่อยู่จัดส่ง
 * - เปลี่ยนรหัสผ่าน
 * - ปุ่มบันทึกลอย (Floating Save)
 *
 * @version 4.0.0
 * @theme Theme V4 (นวลทองคำ)
 */
--}}

<div style="display:flex; flex-direction:column; gap:18px; padding-bottom:96px;" x-data="profileManager()" x-init="init()">

    {{-- ── การ์ดหัวเรื่อง (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; padding:22px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:26px;"><i class="fas fa-user-circle" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">บัญชีของฉัน · MY PROFILE</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">จัดการโปรไฟล์</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">แก้ไขข้อมูลส่วนตัวและการตั้งค่าของคุณ</div>
            </div>
        </div>

        {{-- สถานะย่อย 3 ใบ: KYC / LINE / รหัสสมาชิก --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; padding:16px 24px 20px;">
            {{-- สถานะ KYC --}}
            <div style="display:flex; align-items:center; gap:11px; padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm);">
                @if($kycVerified)
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:#5aa07e;"><i class="fas fa-shield-halved" style="color:#fff;"></i></span>
                    <div><div style="font-size:11px; color:var(--ink2);">สถานะ KYC</div><div style="font-size:14px; font-weight:700; color:#5aa07e;">ยืนยันแล้ว ✓</div></div>
                @elseif($kycPending)
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:#e0a52e;"><i class="fas fa-hourglass-half" style="color:#fff;"></i></span>
                    <div><div style="font-size:11px; color:var(--ink2);">สถานะ KYC</div><div style="font-size:14px; font-weight:700; color:#e0a52e;">รอตรวจสอบ</div></div>
                @else
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:#d9534f;"><i class="fas fa-triangle-exclamation" style="color:#fff;"></i></span>
                    <div><div style="font-size:11px; color:var(--ink2);">สถานะ KYC</div><div style="font-size:14px; font-weight:700; color:#d9534f;">ยังไม่ยืนยัน</div></div>
                @endif
            </div>

            {{-- สถานะการเชื่อมต่อ LINE --}}
            <div style="display:flex; align-items:center; gap:11px; padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm);">
                @if($user->line_user_id)
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:#5aa07e;">
                        <svg style="width:20px; height:20px; color:#fff;" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/></svg>
                    </span>
                    <div><div style="font-size:11px; color:var(--ink2);">LINE</div><div style="font-size:14px; font-weight:700; color:#5aa07e;">เชื่อมต่อแล้ว ✓</div></div>
                @else
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:var(--ink2);">
                        <svg style="width:20px; height:20px; color:#fff;" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/></svg>
                    </span>
                    <div><div style="font-size:11px; color:var(--ink2);">LINE</div><div style="font-size:14px; font-weight:700;">ยังไม่เชื่อมต่อ</div></div>
                @endif
            </div>

            {{-- รหัสสมาชิก --}}
            <div style="display:flex; align-items:center; gap:11px; padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm);">
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; background:#5689b8;"><i class="fas fa-id-card" style="color:#fff;"></i></span>
                <div><div style="font-size:11px; color:var(--ink2);">รหัสสมาชิก</div><div class="tp-num" style="font-size:14px; font-weight:700;">{{ $user->member_number ?? 'N/A' }}</div></div>
            </div>
        </div>
    </div>

    {{-- ── ข้อความแจ้งเตือน สำเร็จ/ผิดพลาด ───────────────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:#5aa07e;"><i class="fas fa-circle-check" style="color:#fff;"></i></span>
            <div style="flex:1; font-size:13px; font-weight:600;">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
            <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:#d9534f;"><i class="fas fa-circle-exclamation" style="color:#fff;"></i></span>
            <div style="flex:1; font-size:13px; font-weight:600;">{{ session('error') }}</div>
        </div>
    @endif

    {{-- ── ฟอร์มโปรไฟล์หลัก ──────────────────────────────────── --}}
    <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data" x-ref="profileForm" @submit="formChanged = false">
        @csrf
        @method('PUT')

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:18px; align-items:start;">
            {{-- ── คอลัมน์ซ้าย: การ์ด Avatar ──────────────────── --}}
            <div style="grid-column:span 1; position:sticky; top:18px;">
                <div class="tp-card" style="padding:22px;">
                    {{-- พรีวิว Avatar --}}
                    <div style="text-align:center;">
                        <div style="position:relative; display:inline-block;">
                            <div style="position:relative; width:150px; height:150px; margin:0 auto 16px;">
                                <div style="position:absolute; inset:0; border-radius:50%; box-shadow:var(--inset);"></div>
                                <div style="position:relative; width:100%; height:100%; border-radius:50%; padding:4px; background:linear-gradient(135deg, var(--accent1), var(--accent2));">
                                    <div style="width:100%; height:100%; border-radius:50%; padding:4px; background:var(--surf);">
                                        {{-- รองรับ Alpine.js preview + fallback เมื่อรูปโหลดไม่ได้ --}}
                                        <img :src="avatarPreview || '{{ $user->profile_picture_url }}'"
                                             alt="{{ $user->name }}"
                                             style="width:100%; height:100%; object-fit:cover; border-radius:50%;"
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=c8a24a&color=fff&size=200';">
                                    </div>
                                </div>
                            </div>

                            {{-- ปุ่มอัปโหลด --}}
                            <label for="avatar-upload" class="tp-btn tp-btn-primary" style="display:flex; width:100%; justify-content:center; cursor:pointer;">
                                <i class="fas fa-camera"></i> เปลี่ยนรูปโปรไฟล์
                            </label>

                            <input type="file"
                                   id="avatar-upload"
                                   name="profile_picture"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   style="display:none;"
                                   @change="handleAvatarChange($event)">

                            <p style="margin-top:10px; font-size:11px; color:var(--ink2); line-height:1.6;">
                                JPG, PNG, GIF หรือ WebP<br>
                                ขนาดไม่เกิน 5MB<br>
                                <span style="color:var(--deep1); font-weight:700;">จะแปลงเป็น WebP อัตโนมัติ</span>
                            </p>
                        </div>

                        {{-- ข้อมูลผู้ใช้ --}}
                        <div style="margin-top:18px; padding-top:18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                            <h3 style="font-size:16px; font-weight:700; margin:0;">{{ $user->name }}</h3>
                            <p style="font-size:12.5px; color:var(--ink2); margin:4px 0 0;">{{ $user->email }}</p>

                            {{-- ป้ายสถานะ KYC + LINE --}}
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:14px;">
                                @if($kycVerified)
                                    <a href="{{ route('user.kyc.index') }}" class="tp-pill" style="text-decoration:none; color:#fff; background:#5aa07e; padding:7px 14px;">
                                        <i class="fas fa-shield-halved"></i> ยืนยันตัวตนแล้ว <i class="fas fa-circle-check"></i>
                                    </a>
                                @elseif($kycPending)
                                    <a href="{{ route('user.kyc.index') }}" class="tp-pill" style="text-decoration:none; color:#fff; background:#e0a52e; padding:7px 14px;">
                                        <i class="fas fa-hourglass-half"></i> รอตรวจสอบ KYC
                                    </a>
                                @else
                                    <a href="{{ route('user.kyc.index') }}" class="tp-pill" style="text-decoration:none; color:#fff; background:#d9534f; padding:7px 14px;">
                                        <i class="fas fa-triangle-exclamation"></i> ยังไม่ได้ยืนยันตัวตน
                                    </a>
                                @endif

                                {{-- ป้ายสถานะการเชื่อมต่อ LINE --}}
                                @if($user->line_user_id)
                                    <span class="tp-pill" style="color:#fff; background:#5aa07e; padding:7px 14px;">
                                        <svg style="width:14px; height:14px; color:#fff;" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/></svg>
                                        LINE: {{ $user->line_display_name ?? 'เชื่อมต่อแล้ว' }} <i class="fas fa-circle-check"></i>
                                    </span>
                                @else
                                    @if(RouteFacade::has('line.link'))
                                    <a href="{{ route('line.link') }}" class="tp-pill" style="text-decoration:none; color:#fff; background:#00B900; padding:7px 14px;">
                                        <svg style="width:14px; height:14px; color:#fff;" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/></svg>
                                        เชื่อมต่อ LINE <i class="fas fa-up-right-from-square"></i>
                                    </a>
                                    @endif
                                @endif
                            </div>

                            @if($user->member_number)
                                <div style="margin-top:12px;">
                                    <span class="tp-pill tp-pill-soft"><i class="fas fa-id-card"></i> {{ $user->member_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── คอลัมน์ขวา: ฟอร์มข้อมูลโปรไฟล์ ─────────────── --}}
            <div style="grid-column:span 1; display:flex; flex-direction:column; gap:18px;">

                {{-- ข้อมูลส่วนตัว --}}
                <div class="tp-card" style="padding:20px;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px; background:#5689b8;"><i class="fas fa-user" style="color:#fff;"></i></span>
                        <h2 style="font-size:17px; font-weight:700; margin:0;">ข้อมูลส่วนตัว</h2>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                        {{-- ชื่อ-นามสกุล --}}
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-signature" style="color:var(--deep1);"></i> ชื่อ-นามสกุล <span style="color:#d9534f;">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required
                                   class="tp-input"
                                   style="width:100%;"
                                   @input="formChanged = true">
                            @error('name')
                                <p style="margin-top:5px; font-size:12px; color:#d9534f;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- อีเมล --}}
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-envelope" style="color:var(--deep1);"></i> อีเมล <span style="color:#d9534f;">*</span>
                            </label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   required
                                   class="tp-input"
                                   style="width:100%;"
                                   @input="formChanged = true">
                            @error('email')
                                <p style="margin-top:5px; font-size:12px; color:#d9534f;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- เบอร์โทรศัพท์ --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-phone" style="color:var(--deep1);"></i> เบอร์โทรศัพท์
                            </label>
                            <input type="tel"
                                   name="phone"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="0812345678"
                                   class="tp-input"
                                   style="width:100%;"
                                   @input="formChanged = true">
                            @error('phone')
                                <p style="margin-top:5px; font-size:12px; color:#d9534f;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- วันเกิด --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-cake-candles" style="color:var(--deep1);"></i> วันเกิด
                            </label>
                            <input type="date"
                                   name="date_of_birth"
                                   value="{{ old('date_of_birth', $user->date_of_birth) }}"
                                   class="tp-input"
                                   style="width:100%;"
                                   @input="formChanged = true">
                            @error('date_of_birth')
                                <p style="margin-top:5px; font-size:12px; color:#d9534f;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- เพศ --}}
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-venus-mars" style="color:var(--deep1);"></i> เพศ
                            </label>
                            <select name="gender"
                                    class="tp-input"
                                    style="width:100%;"
                                    @change="formChanged = true">
                                <option value="">เลือกเพศ</option>
                                <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>ชาย</option>
                                <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>หญิง</option>
                                <option value="other" {{ old('gender', $user->gender) === 'other' ? 'selected' : '' }}>อื่นๆ</option>
                                <option value="prefer_not_to_say" {{ old('gender', $user->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>ไม่ระบุ</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ที่อยู่ (สำหรับออกใบเสร็จ) --}}
                <div class="tp-card" style="padding:20px;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px; background:#5aa07e;"><i class="fas fa-map-marker-alt" style="color:#fff;"></i></span>
                        <h2 style="font-size:17px; font-weight:700; margin:0;">ที่อยู่ (สำหรับออกใบเสร็จ)</h2>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:14px;">
                        {{-- ที่อยู่ --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-home" style="color:#5aa07e;"></i> ที่อยู่
                            </label>
                            <textarea name="address"
                                      rows="2"
                                      placeholder="บ้านเลขที่ ถนน"
                                      class="tp-input"
                                      style="width:100%; resize:none;"
                                      @input="formChanged = true">{{ old('address', $user->address) }}</textarea>
                        </div>

                        {{-- ระบบเลือกที่อยู่อัจฉริยะ (จังหวัด → อำเภอ → รหัสไปรษณีย์) --}}
                        <x-thai-address-picker
                            province-field="state"
                            district-field="city"
                            sub-district-field=""
                            postal-code-field="postal_code"
                            country-field="country"
                            :country-value="old('country', $user->country ?? 'TH')"
                            :province-value="old('state', $user->state ?? '')"
                            :district-value="old('city', $user->city ?? '')"
                            :sub-district-value="''"
                            :postal-code-value="old('postal_code', $user->postal_code ?? '')"
                            :show-sub-district="false"
                        />
                    </div>
                </div>

                {{-- ที่อยู่จัดส่ง — ลิงก์ไปหน้าจัดการที่อยู่จัดส่ง --}}
                <div class="tp-card" style="padding:20px;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px; background:#e0a52e;"><i class="fas fa-shipping-fast" style="color:#fff;"></i></span>
                        <h2 style="font-size:17px; font-weight:700; margin:0;">ที่อยู่จัดส่ง</h2>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:16px 18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                        <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:19px; background:#e0a52e;"><i class="fas fa-map-marker-alt" style="color:#fff;"></i></span>
                        <div style="flex:1; min-width:200px;">
                            <p style="font-size:13px; color:var(--ink2); margin:0 0 10px;">
                                จัดการที่อยู่จัดส่งทั้งหมดได้ที่หน้าที่อยู่จัดส่ง เพิ่ม แก้ไข ลบ และตั้งค่าที่อยู่เริ่มต้นได้ในที่เดียว
                            </p>
                            @if(RouteFacade::has('shipping-addresses.index'))
                            <a href="{{ route('shipping-addresses.index') }}" class="tp-btn tp-btn-primary tp-btn-sm">
                                <i class="fas fa-up-right-from-square"></i> จัดการที่อยู่จัดส่ง
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ข้อมูลบัตรประชาชน (อ่านอย่างเดียว) — แสดงเมื่อ KYC ผ่านแล้ว --}}
                @if($kycVerified && $user->id_card_number)
                <div class="tp-card" style="padding:20px; border:1px solid color-mix(in srgb, #5aa07e 35%, transparent);">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px; background:#5aa07e;"><i class="fas fa-id-card" style="color:#fff;"></i></span>
                            <h2 style="font-size:17px; font-weight:700; margin:0;">ข้อมูลบัตรประชาชน</h2>
                        </div>
                        <span class="tp-pill" style="color:#fff; background:#5aa07e;"><i class="fas fa-shield-halved"></i> ยืนยันตัวตนแล้ว</span>
                    </div>

                    {{-- คำเตือน: ไม่สามารถแก้ไขได้ --}}
                    <div style="margin-bottom:16px; padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e; font-size:12.5px; color:#e0a52e;">
                        <i class="fas fa-lock"></i>
                        ข้อมูลบัตรประชาชนไม่สามารถแก้ไขได้ หากต้องการแก้ไขกรุณาติดต่อทีมงานผ่านระบบ Support Ticket
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                        {{-- เลขบัตรประชาชน --}}
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-fingerprint" style="color:#5aa07e;"></i> เลขบัตรประชาชน
                            </label>
                            <div class="tp-num" style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ substr($user->id_card_number, 0, 1) }}-{{ substr($user->id_card_number, 1, 4) }}-{{ substr($user->id_card_number, 5, 5) }}-{{ substr($user->id_card_number, 10, 2) }}-{{ substr($user->id_card_number, 12, 1) }}
                            </div>
                        </div>

                        {{-- ชื่อภาษาไทย --}}
                        @if($user->thai_first_name || $user->thai_last_name)
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-user" style="color:#5aa07e;"></i> ชื่อ-นามสกุล (ภาษาไทย)
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ $user->thai_first_name }} {{ $user->thai_last_name }}
                            </div>
                        </div>
                        @endif

                        {{-- ชื่อภาษาอังกฤษ --}}
                        @if($user->english_first_name || $user->english_last_name)
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-globe" style="color:#5aa07e;"></i> ชื่อ-นามสกุล (ภาษาอังกฤษ)
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ $user->english_first_name }} {{ $user->english_last_name }}
                            </div>
                        </div>
                        @endif

                        {{-- วันเกิด (จากบัตร) --}}
                        @if($user->id_card_birth_date)
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-cake-candles" style="color:#5aa07e;"></i> วันเกิด (จากบัตร)
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ \Carbon\Carbon::parse($user->id_card_birth_date)->locale('th')->translatedFormat('j F Y') }}
                            </div>
                        </div>
                        @endif

                        {{-- ศาสนา --}}
                        @if($user->id_card_religion)
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-pray" style="color:#5aa07e;"></i> ศาสนา
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ $user->id_card_religion }}
                            </div>
                        </div>
                        @endif

                        {{-- วันออกบัตร --}}
                        @if($user->id_card_issue_date)
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-calendar-plus" style="color:#5aa07e;"></i> วันออกบัตร
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ \Carbon\Carbon::parse($user->id_card_issue_date)->locale('th')->translatedFormat('j F Y') }}
                            </div>
                        </div>
                        @endif

                        {{-- วันหมดอายุ --}}
                        @if($user->id_card_expiry_date)
                        @php $idExpired = \Carbon\Carbon::parse($user->id_card_expiry_date)->isPast(); @endphp
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-calendar-times" style="color:#5aa07e;"></i> วันหมดอายุ
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:{{ $idExpired ? '#d9534f' : 'var(--ink2)' }};">
                                {{ \Carbon\Carbon::parse($user->id_card_expiry_date)->locale('th')->translatedFormat('j F Y') }}
                                @if($idExpired)
                                    <span style="margin-left:8px; color:#d9534f; font-size:12px;">(หมดอายุแล้ว)</span>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- ที่อยู่ตามบัตร --}}
                        @if($user->id_card_address)
                        <div style="grid-column:1 / -1;">
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-map-marker-alt" style="color:#5aa07e;"></i> ที่อยู่ตามบัตร
                            </label>
                            <div style="padding:11px 14px; border-radius:12px; box-shadow:var(--inset-sm); color:var(--ink2);">
                                {{ $user->id_card_address }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- เปลี่ยนรหัสผ่าน --}}
                <div class="tp-card" style="padding:20px;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                        <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px; background:#d9534f;"><i class="fas fa-key" style="color:#fff;"></i></span>
                        <h2 style="font-size:17px; font-weight:700; margin:0;">เปลี่ยนรหัสผ่าน</h2>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:14px;">
                        {{-- รหัสผ่านปัจจุบัน --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-lock" style="color:#d9534f;"></i> รหัสผ่านปัจจุบัน
                            </label>
                            <div style="position:relative;" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="current_password"
                                       placeholder="ใส่รหัสผ่านปัจจุบัน (ถ้าต้องการเปลี่ยน)"
                                       class="tp-input"
                                       style="width:100%; padding-right:44px;">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="tp-icon-btn"
                                        style="position:absolute; right:6px; top:50%; transform:translateY(-50%);">
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- รหัสผ่านใหม่ --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-key" style="color:#d9534f;"></i> รหัสผ่านใหม่
                            </label>
                            <div style="position:relative;" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="new_password"
                                       placeholder="ใส่รหัสผ่านใหม่"
                                       class="tp-input"
                                       style="width:100%; padding-right:44px;">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="tp-icon-btn"
                                        style="position:absolute; right:6px; top:50%; transform:translateY(-50%);">
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        {{-- ยืนยันรหัสผ่านใหม่ --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;">
                                <i class="fas fa-circle-check" style="color:#d9534f;"></i> ยืนยันรหัสผ่านใหม่
                            </label>
                            <div style="position:relative;" x-data="{ showPassword: false }">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="new_password_confirmation"
                                       placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง"
                                       class="tp-input"
                                       style="width:100%; padding-right:44px;">
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="tp-icon-btn"
                                        style="position:absolute; right:6px; top:50%; transform:translateY(-50%);">
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8; font-size:12.5px; color:#5689b8;">
                            <i class="fas fa-circle-info"></i>
                            รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ── ปุ่มบันทึกลอย (Floating Save) ──────────────────────── --}}
    <div x-show="formChanged"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         style="position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:50; display:none;">
        <div class="tp-card" style="display:flex; align-items:center; gap:10px; padding:10px; border-radius:999px;">
            <button type="button"
                    @click="$refs.profileForm.submit()"
                    class="tp-btn tp-btn-primary"
                    style="border-radius:999px; padding:12px 24px; font-size:15px;">
                <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
            </button>
            <button type="button"
                    @click="formChanged = false; window.location.reload()"
                    class="tp-btn"
                    style="border-radius:999px; padding:12px 18px;">
                <i class="fas fa-times"></i> ยกเลิก
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Profile Manager - Alpine.js Component
 */
function profileManager() {
    return {
        avatarPreview: null,
        formChanged: false,

        init() {
            // Track form changes
            const form = this.$refs.profileForm;
            if (form) {
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('input', () => {
                        this.formChanged = true;
                    });
                });
            }
        },

        /**
         * Handle Avatar Upload and Preview
         */
        handleAvatarChange(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, GIF, WebP) เท่านั้น');
                event.target.value = '';
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                event.target.value = '';
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                this.avatarPreview = e.target.result;
                this.formChanged = true;
            };
            reader.readAsDataURL(file);
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'success') {
            // Simple alert for now (can be enhanced with toast notification)
            alert(message);
        }
    };
}
</script>
@endpush
@endsection

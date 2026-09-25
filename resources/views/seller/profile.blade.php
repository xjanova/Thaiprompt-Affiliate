@extends('layouts.seller-v4')

@section('title', 'โปรไฟล์')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    $avatarFallback = 'https://ui-avatars.com/api/?name=' . urlencode(mb_substr($user->name ?? 'U', 0, 1)) . '&background=e6b347&color=fff&size=200';
@endphp

@section('content')
<div class="sv4-page" style="max-width:960px; margin-inline:auto; width:100%;">

    <x-seller-v4.header title="โปรไฟล์ของฉัน" subtitle="ข้อมูลบัญชีผู้ขาย รูปโปรไฟล์ และรหัสผ่าน" icon="👤" :back="route('seller.settings')" />

    <x-seller-v4.errors />

    <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data"
          x-data="sellerProfileManager()" @submit="busy = true" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- รูปโปรไฟล์ --}}
        <div class="tp-card" style="padding:20px; display:flex; flex-wrap:wrap; align-items:center; gap:18px;">
            <label for="profile_picture" style="position:relative; cursor:pointer; flex:none;" title="เปลี่ยนรูปโปรไฟล์">
                <img :src="avatarPreview || @js($user->profile_picture_url ?: $avatarFallback)" alt="รูปโปรไฟล์ {{ $user->name }}"
                     onerror="this.onerror=null; this.src=this.dataset.fallback;" data-fallback="{{ $avatarFallback }}"
                     style="width:104px; height:104px; border-radius:30px; object-fit:cover; box-shadow:var(--card-shadow);">
                <span class="tp-icon-btn" style="position:absolute; right:-6px; bottom:-6px; width:36px; height:36px; border-radius:12px; font-size:15px;">📷</span>
            </label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only"
                   style="position:absolute; width:1px; height:1px; opacity:0;" @change="handleAvatarChange($event)">
            <div style="min-width:0; flex:1;">
                <div style="font-size:19px; font-weight:800; overflow-wrap:anywhere;">{{ $user->name }}</div>
                <div style="font-size:12.5px; color:var(--ink2); overflow-wrap:anywhere;">{{ $user->email }}</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                    <span class="sv4-pill tp-pill-soft">🏪 ผู้ขาย</span>
                    <span class="sv4-pill tp-pill-soft">สมาชิกตั้งแต่ {{ $user->created_at?->format('d/m/Y') }}</span>
                </div>
                <div class="sv4-hint">แตะรูปเพื่อเปลี่ยน · JPG, PNG, GIF, WebP ไม่เกิน 5MB</div>
                <div class="sv4-err" x-show="avatarError" x-text="avatarError"></div>
                @error('profile_picture')<div class="sv4-err">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ข้อมูลส่วนตัว --}}
        <div class="tp-card" style="padding:20px;">
            <div class="sv4-h2">📝 ข้อมูลส่วนตัว</div>
            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="name" class="sv4-label">ชื่อ-นามสกุล <span class="req">*</span></label>
                    <input type="text" name="name" id="name" required maxlength="255" class="tp-input" value="{{ old('name', $user->name) }}">
                    @error('name')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="email" class="sv4-label">อีเมล <span class="req">*</span></label>
                    <input type="email" name="email" id="email" required maxlength="255" class="tp-input" value="{{ old('email', $user->email) }}">
                    @error('email')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="phone" class="sv4-label">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" id="phone" maxlength="20" class="tp-input tp-num" value="{{ old('phone', $user->phone) }}">
                    @error('phone')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- เปลี่ยนรหัสผ่าน --}}
        <div class="tp-card" style="padding:20px;" x-data="{ show: false }">
            <div class="sv4-row" style="flex-wrap:wrap;">
                <div>
                    <div class="sv4-h2">🔐 เปลี่ยนรหัสผ่าน</div>
                    <div class="sv4-sub">เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน · อย่างน้อย 8 ตัว มีตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข</div>
                </div>
                <button type="button" class="tp-btn tp-btn-sm" @click="show = !show" x-text="show ? '🙈 ซ่อนรหัสผ่าน' : '👁️ แสดงรหัสผ่าน'"></button>
            </div>
            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="current_password" class="sv4-label">รหัสผ่านปัจจุบัน</label>
                    <input :type="show ? 'text' : 'password'" name="current_password" id="current_password" autocomplete="current-password" class="tp-input">
                    @error('current_password')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="new_password" class="sv4-label">รหัสผ่านใหม่</label>
                    <input :type="show ? 'text' : 'password'" name="new_password" id="new_password" autocomplete="new-password" class="tp-input">
                    @error('new_password')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="new_password_confirmation" class="sv4-label">ยืนยันรหัสผ่านใหม่</label>
                    <input :type="show ? 'text' : 'password'" name="new_password_confirmation" id="new_password_confirmation" autocomplete="new-password" class="tp-input">
                </div>
            </div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end;">
            <a href="{{ route('seller.dashboard') }}" class="tp-btn">ยกเลิก</a>
            <button type="submit" class="tp-btn tp-btn-primary" style="padding:0 24px;" :disabled="busy">
                <span x-show="!busy">💾 บันทึกโปรไฟล์</span>
                <span x-show="busy" x-cloak>กำลังบันทึก…</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
/**
 * โปรไฟล์ผู้ขาย — พรีวิวรูปโปรไฟล์ก่อนอัปโหลด (ตรวจชนิดไฟล์/ขนาดฝั่งเบราว์เซอร์ก่อน เซิร์ฟเวอร์ตรวจซ้ำ)
 */
function sellerProfileManager() {
    return {
        avatarPreview: null,
        avatarError: '',
        busy: false,
        handleAvatarChange(event) {
            this.avatarError = '';
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowed.includes(file.type)) {
                this.avatarError = 'กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, GIF, WebP) เท่านั้น';
                event.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.avatarError = 'ขนาดไฟล์ต้องไม่เกิน 5MB';
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => { this.avatarPreview = e.target.result; };
            reader.readAsDataURL(file);
        }
    };
}
</script>
@endpush

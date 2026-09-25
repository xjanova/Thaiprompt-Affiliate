{{--
 | ทดสอบ LINE OA ของตลาดสด (admin.fresh-market.test-line) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@testLine: $settings (FreshMarketSetting — ใช้ดูแค่ว่ามีค่าหรือไม่ ไม่แสดงค่าจริง)
 | ตรวจการเชื่อมต่อ: POST verify-line (AJAX JSON {success, data{displayName,basicId,userId,pictureUrl,chatMode}, error})
 | ส่งข้อความทดสอบ: POST test-line.send {user_id*, message*} — ใช้ push 1 ครั้ง (นับโควต้า LINE ต่อเดือน)
--}}
@extends('layouts.admin-v4')

@section('title', 'ทดสอบ LINE ตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $configItems = [
        ['Channel ID', filled($settings->line_channel_id ?? null)],
        ['Channel Secret', filled($settings->line_channel_secret ?? null)],
        ['Access Token', filled($settings->line_channel_access_token ?? null)],
    ];
@endphp
<div x-data="w1LineTester()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.settings') }}#line" class="tp-icon-btn" title="กลับตั้งค่า LINE OA"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ทดสอบ LINE</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ทดสอบ LINE OA 💬</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตรวจว่า Access Token ใช้งานได้ และลองส่งข้อความหาบัญชีทดสอบ</div>
            </div>
        </div>
        <a href="{{ route('admin.fresh-market.settings') }}#line" class="tp-btn tp-btn-sm"><i class="fas fa-gear"></i> ตั้งค่า LINE OA</a>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== สถานะการเชื่อมต่อ ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h"><i class="fas fa-plug"></i> สถานะการเชื่อมต่อ</div>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="verify()" :disabled="verifying">
                <i class="fas" :class="verifying ? 'fa-spinner fa-spin' : 'fa-plug-circle-check'"></i>
                <span x-text="verifying ? 'กำลังตรวจสอบ...' : 'ตรวจสอบการเชื่อมต่อ'"></span>
            </button>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:10px;">
            @foreach ($configItems as [$configLabel, $configured])
                <div class="tp-well" style="padding:12px 14px; display:flex; align-items:center; gap:10px;">
                    <span style="width:10px; height:10px; border-radius:50%; background:var({{ $configured ? '--w-ok' : '--w-bad' }}); flex:none;"></span>
                    <div>
                        <div style="font-size:11.5px; color:var(--ink2);">{{ $configLabel }}</div>
                        <div style="font-size:13px; font-weight:600;">{{ $configured ? 'ตั้งค่าแล้ว' : 'ยังไม่ได้ตั้งค่า' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <template x-if="botInfo">
            <div class="tp-well" style="margin-top:14px; padding:14px; border-left:4px solid var(--w-ok); display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
                <template x-if="botInfo.pictureUrl">
                    <img :src="botInfo.pictureUrl" alt="" style="width:52px; height:52px; border-radius:50%; object-fit:cover;">
                </template>
                <div style="font-size:13px; display:grid; grid-template-columns:auto 1fr; gap:4px 12px;">
                    <b style="grid-column:1 / -1; color:color-mix(in srgb, var(--w-ok) 74%, var(--ink));"><i class="fas fa-circle-check"></i> เชื่อมต่อ LINE OA สำเร็จ</b>
                    <span style="color:var(--ink2);" x-show="botInfo.displayName">ชื่อบอท</span><span x-show="botInfo.displayName" x-text="botInfo.displayName"></span>
                    <span style="color:var(--ink2);" x-show="botInfo.basicId">Basic ID</span><span x-show="botInfo.basicId" x-text="'@' + botInfo.basicId"></span>
                    <span style="color:var(--ink2);" x-show="botInfo.chatMode">Chat mode</span><span x-show="botInfo.chatMode" x-text="botInfo.chatMode"></span>
                    <span style="color:var(--ink2);" x-show="botInfo.userId">Bot user ID</span><span x-show="botInfo.userId" class="tp-num" style="font-size:11.5px; word-break:break-all;" x-text="botInfo.userId"></span>
                </div>
            </div>
        </template>
        <template x-if="verifyError">
            <div class="tp-well" style="margin-top:14px; padding:12px 14px; border-left:4px solid var(--w-bad); font-size:13px;">
                <b style="color:var(--w-bad);"><i class="fas fa-circle-xmark"></i> เชื่อมต่อไม่สำเร็จ</b>
                <div style="margin-top:4px;" x-text="verifyError"></div>
            </div>
        </template>
    </div>

    {{-- ===== ส่งข้อความทดสอบ ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:4px;"><i class="fas fa-paper-plane"></i> ส่งข้อความทดสอบ</div>
        <div style="font-size:12.5px; color:var(--w-warn); margin-bottom:14px;"><i class="fas fa-triangle-exclamation"></i> ส่งแบบ push ครั้งละ 1 ข้อความ — นับรวมในโควต้าข้อความต่อเดือนของ LINE OA ใช้เท่าที่จำเป็น</div>
        <form method="POST" action="{{ route('admin.fresh-market.test-line.send') }}" @submit="sending = true" style="display:flex; flex-direction:column; gap:14px; max-width:680px;">
            @csrf
            <label>
                <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">LINE User ID <span style="color:var(--w-bad);">*</span></span>
                <input type="text" name="user_id" value="{{ old('user_id') }}" required class="tp-input tp-num" placeholder="เช่น U1234567890abcdef...">
                <span style="display:block; font-size:11.5px; color:var(--ink2); margin-top:4px;">ขึ้นต้นด้วย U ตามด้วยตัวอักษร 32 ตัว — ดูได้จาก webhook ตอนมีคนเพิ่มเพื่อนหรือทักมา (ผู้รับต้องเป็นเพื่อนกับ OA)</span>
                @error('user_id')<span style="display:block; font-size:12px; color:var(--w-bad); margin-top:4px;">{{ $message }}</span>@enderror
            </label>
            <label>
                <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ข้อความ <span style="color:var(--w-bad);">*</span></span>
                <textarea name="message" rows="4" required class="tp-input" style="resize:vertical;">{{ old('message', 'ทดสอบการส่งข้อความจากระบบตลาดสดไทยพร๊อม') }}</textarea>
                @error('message')<span style="display:block; font-size:12px; color:var(--w-bad); margin-top:4px;">{{ $message }}</span>@enderror
            </label>
            <div>
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="sending">
                    <i class="fas" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                    <span x-text="sending ? 'กำลังส่ง...' : 'ส่งข้อความทดสอบ'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /* ตรวจการเชื่อมต่อ LINE OA ของตลาดสด (ไม่ส่งข้อความ ไม่เสียโควต้า) */
    function w1LineTester() {
        return {
            verifyUrl: @js(route('admin.fresh-market.verify-line')),
            verifying: false,
            sending: false,
            botInfo: null,
            verifyError: '',
            async verify() {
                this.verifying = true;
                this.botInfo = null;
                this.verifyError = '';
                try {
                    const res = await fetch(this.verifyUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.success) {
                        this.botInfo = json.data || {};
                    } else {
                        this.verifyError = json.error || json.message || 'LINE ปฏิเสธการเชื่อมต่อ กรุณาตรวจ Access Token';
                    }
                } catch (e) {
                    this.verifyError = 'ติดต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่';
                } finally {
                    this.verifying = false;
                }
            },
        };
    }
</script>
@endpush

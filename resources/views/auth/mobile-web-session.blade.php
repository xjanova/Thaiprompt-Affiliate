{{--
 | หน้ายืนยัน "เปิดเว็บแบบล็อกอินจากแอป" (PLAY-16 / SHOP-15)
 | ตัวแปร: $token, $targetName, $targetEmail (ปกปิดแล้ว), $currentName|null, $currentEmail|null,
 |         $destinationLabel, $destinationPath, $autoSubmit (bool)
 | ฟอร์ม POST → route('mobile-web-session.consume') (มี CSRF) — token ใช้ได้ครั้งเดียว
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ยืนยันการเข้าสู่ระบบ')

@push('styles')
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
@endpush

@section('content')
<div style="flex:1; display:flex; align-items:center; justify-content:center; padding:32px 16px;">
    <div class="tp-card" style="width:100%; max-width:440px; padding:clamp(22px,5vw,32px);">

        <div style="text-align:center; margin-bottom:20px;">
            <div class="tp-tile" style="width:60px; height:60px; border-radius:18px; font-size:28px; margin:0 auto 14px;">🔐</div>
            <h1 style="font-size:1.35rem; font-weight:800; color:var(--ink); margin:0;">ยืนยันการเข้าสู่ระบบเว็บไซต์</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:.88rem;">เปิดจากแอปไทยพร๊อมท์ — ตรวจสอบชื่อบัญชีก่อนดำเนินการต่อ</p>
        </div>

        {{-- บัญชีที่จะเข้าสู่ระบบ --}}
        <div class="tp-inset-sm" style="display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:14px; margin-bottom:14px;">
            <div class="tp-tile" style="width:44px; height:44px; border-radius:50%; font-size:18px; flex-shrink:0;">
                {{ mb_strtoupper(mb_substr(trim((string) $targetName), 0, 1)) ?: 'U' }}
            </div>
            <div style="min-width:0;">
                <div style="font-size:.75rem; color:var(--ink2);">จะเข้าสู่ระบบในชื่อ</div>
                <div style="font-weight:700; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $targetName }}</div>
                @if($targetEmail)
                    <div style="font-size:.8rem; color:var(--ink2);">{{ $targetEmail }}</div>
                @endif
            </div>
        </div>

        {{-- มีบัญชีอื่นล็อกอินอยู่ในเบราว์เซอร์นี้ --}}
        @if($currentName)
            <div style="padding:12px 14px; border-radius:12px; margin-bottom:14px; font-size:.86rem; line-height:1.6; background:color-mix(in srgb, #e08a3c 14%, transparent); color:var(--ink);">
                ⚠️ เบราว์เซอร์นี้กำลังใช้งานบัญชี <strong>{{ $currentName }}</strong>
                @if($currentEmail)({{ $currentEmail }})@endif
                อยู่ หากดำเนินการต่อ ระบบจะออกจากบัญชีนั้นก่อน
            </div>
        @endif

        <div style="font-size:.86rem; color:var(--ink2); margin-bottom:18px;">
            ปลายทาง: <strong style="color:var(--ink);">{{ $destinationLabel }}</strong>
        </div>

        <form id="mws-form" method="POST" action="{{ route('mobile-web-session.consume') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; min-height:48px; justify-content:center;">
                <i class="fas fa-right-to-bracket"></i> <span>{{ $currentName ? 'สลับบัญชีและไปต่อ' : 'เข้าสู่ระบบและไปต่อ' }}</span>
            </button>
        </form>

        <div style="text-align:center; margin-top:14px;">
            <a href="{{ url('/') }}" style="font-size:.86rem; color:var(--ink2);">ไม่ใช่บัญชีของฉัน — ยกเลิก</a>
        </div>

        <p class="tp-muted" style="margin:18px 0 0; font-size:.75rem; text-align:center; line-height:1.6;">
            ลิงก์นี้ใช้ได้ครั้งเดียวและหมดอายุภายใน 5 นาที ห้ามส่งต่อลิงก์นี้ให้ผู้อื่น
        </p>
    </div>
</div>

@if($autoSubmit)
    {{-- ยังไม่ได้ล็อกอิน + เครื่องเดียวกับที่ขอลิงก์ → ยืนยันให้อัตโนมัติ (ปุ่มด้านบนยังใช้ได้ถ้า JS ปิด) --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('mws-form');
                if (form) { form.submit(); }
            });
        </script>
    @endpush
@endif
@endsection

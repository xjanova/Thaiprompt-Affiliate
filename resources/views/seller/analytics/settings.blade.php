@extends('layouts.seller-v4')

@section('title', 'ตั้งค่าการวิเคราะห์')

@php
    $groups = [
        ['🔍 การเก็บสถิติ', [
            ['track_visits', 'นับการเข้าชมหน้าร้าน', 'จำนวนครั้งที่มีคนเปิดดูร้านและสินค้า'],
            ['track_unique_visitors', 'นับผู้เยี่ยมชมไม่ซ้ำ', 'แยกผู้เข้าชมแต่ละคน (ไม่นับซ้ำในวันเดียวกัน)'],
            ['track_session_duration', 'จับเวลาที่อยู่ในร้าน', 'ใช้คำนวณอัตราตีกลับและความสนใจ'],
        ]],
        ['🔒 ความเป็นส่วนตัว', [
            ['anonymize_ip', 'ซ่อน IP ของผู้เข้าชม', 'เก็บเป็นค่าแฮชแทน IP จริง (แนะนำให้เปิด)'],
            ['respect_dnt', 'เคารพการตั้งค่า Do Not Track', 'ไม่เก็บสถิติของผู้ที่ตั้งเบราว์เซอร์ว่าไม่ต้องการให้ติดตาม'],
        ]],
        ['📧 รายงานทางอีเมล', [
            ['email_weekly_reports', 'ส่งสรุปรายสัปดาห์', 'สรุปยอดเข้าชมและยอดขายทุกสัปดาห์'],
            ['email_monthly_reports', 'ส่งสรุปรายเดือน', 'สรุปภาพรวมทุกต้นเดือน'],
        ]],
    ];
    $on = fn ($key) => (bool) old($key, $settings->{$key});
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ตั้งค่าการวิเคราะห์" icon="⚙️" crumb="ร้านค้า · วิเคราะห์"
                         subtitle="เลือกข้อมูลที่ต้องการเก็บ ระยะเวลาเก็บ และรายงานทางอีเมล" />

    @include('seller.analytics.partials.nav')

    <x-seller-kit.errors />

    <form method="POST" action="{{ route('seller.analytics.settings.update') }}" class="tp-card" style="display:flex; flex-direction:column; gap:18px;"
          x-data="{ saving: false }" @submit="saving = true">
        @csrf
        @method('PUT')

        <div>
            <div class="tp-section-h">🗄️ การเก็บข้อมูล</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-top:10px; align-items:end;">
                <div>
                    <label for="as-retention" style="font-size:12.5px; font-weight:700;">เก็บข้อมูลย้อนหลัง (วัน)</label>
                    <input id="as-retention" type="number" name="retention_days" min="7" max="730" required value="{{ old('retention_days', $settings->retention_days) }}" class="tp-input tp-num" style="margin-top:6px;">
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">ขั้นต่ำ 7 วัน สูงสุด 730 วัน</div>
                </div>
                <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:14px; cursor:pointer;">
                    <input type="hidden" name="auto_cleanup_enabled" value="0">
                    <input type="checkbox" name="auto_cleanup_enabled" value="1" @checked($on('auto_cleanup_enabled')) style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent1);">
                    <span><span style="display:block; font-size:13px; font-weight:700;">ลบข้อมูลเก่าอัตโนมัติ</span><span style="display:block; font-size:11.5px; color:var(--ink2);">ลบสถิติที่เก่ากว่าจำนวนวันที่กำหนด</span></span>
                </label>
            </div>
        </div>

        @foreach($groups as [$gTitle, $gItems])
            <div>
                <div class="tp-section-h">{{ $gTitle }}</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:10px; margin-top:10px;">
                    @foreach($gItems as [$key, $label, $hint])
                        <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:14px; cursor:pointer;">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked($on($key)) style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent1);">
                            <span><span style="display:block; font-size:13px; font-weight:700;">{{ $label }}</span><span style="display:block; font-size:11.5px; color:var(--ink2);">{{ $hint }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">
                <span x-text="saving ? 'กำลังบันทึก…' : '💾 บันทึกการตั้งค่า'"></span>
            </button>
        </div>
    </form>

    {{-- ล้างข้อมูลด้วยตัวเอง --}}
    <form method="POST" action="{{ route('seller.analytics.cleanup') }}" class="tp-card" style="display:flex; flex-direction:column; gap:12px; border-left:4px solid var(--tp-bad, #d9534f);"
          onsubmit="return confirm('ต้องการลบสถิติที่เก่ากว่าจำนวนวันที่กำหนดใช่หรือไม่? ลบแล้วกู้คืนไม่ได้');">
        @csrf
        <div class="tp-section-h">🗑️ ล้างสถิติเก่าด้วยตัวเอง</div>
        <div style="font-size:12.5px; color:var(--ink2); line-height:1.6;">ลบข้อมูลสถิติรายวันและประวัติการเข้าชมที่เก่ากว่าที่กำหนด — <strong style="color:var(--tp-bad, #d9534f);">ลบแล้วกู้คืนไม่ได้</strong> (ไม่กระทบออเดอร์และยอดเงิน)</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; align-items:end;">
            <div>
                <label for="as-older" style="font-size:12.5px; font-weight:700;">ลบข้อมูลที่เก่ากว่า (วัน)</label>
                <input id="as-older" type="number" name="older_than_days" value="{{ old('older_than_days', 90) }}" min="7" max="730" required class="tp-input tp-num" style="margin-top:6px;">
            </div>
            <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                <input type="checkbox" name="confirm" value="1" required style="width:18px; height:18px; accent-color:var(--accent1);">
                ฉันเข้าใจและต้องการลบข้อมูลเก่า
            </label>
            <button type="submit" class="tp-btn" style="color:var(--tp-bad, #d9534f);">🗑️ ล้างข้อมูลเก่า</button>
        </div>
        <div style="font-size:12px; color:var(--ink2);">ตอนนี้: เก็บข้อมูล {{ number_format((int) $settings->retention_days) }} วัน · ลบอัตโนมัติ {{ $settings->auto_cleanup_enabled ? 'เปิด' : 'ปิด' }}</div>
    </form>
</div>
@endsection

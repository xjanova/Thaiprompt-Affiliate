@extends('layouts.user-v4')

@section('title', 'สร้างลิงก์เชิญ')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:920px; margin-inline:auto;">

    @if(isset($notMlmMember) && $notMlmMember)
    {{-- ไม่ได้เป็นสมาชิก MLM --}}
    <div class="tp-card" style="padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; background:#e0a52e;"><i class="fas fa-triangle-exclamation" style="color:#fff;"></i></span>
        <div style="flex:1; min-width:200px;">
            <div style="font-weight:700;">คุณยังไม่ได้เป็นสมาชิก MLM</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">สมัครเป็นสมาชิก MLM ก่อนเพื่อสร้างลิงก์เชิญและเริ่มสร้างทีม</div>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('user.mlm.dashboard') }}" class="tp-btn tp-btn-primary">ไปหน้า MLM Dashboard</a>
            <a href="{{ route('user.prospects.index') }}" class="tp-btn">← กลับ</a>
        </div>
    </div>
    @else

    {{-- หัวข้อ --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-link" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:180px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สร้างลิงก์เชิญสมาชิก</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">สร้างลิงก์เพื่อเชิญเพื่อนของคุณสมัครผ่าน LINE</div>
            </div>
            <a href="{{ route('user.prospects.index') }}" class="tp-btn">← กลับ</a>
        </div>
    </div>

    {{-- วิธีใช้งาน --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">ℹ️ วิธีการใช้งาน</div>
        <div style="display:flex; flex-direction:column; gap:11px;">
            @php
                $steps = [
                    'กดปุ่ม "สร้างลิงก์เชิญ" ด้านล่างเพื่อสร้างลิงก์พิเศษสำหรับเชิญเพื่อน',
                    'แชร์ลิงก์หรือ QR Code ให้เพื่อนผ่าน LINE, Facebook หรือช่องทางอื่นๆ',
                    'เมื่อเพื่อนคลิกลิงก์ ระบบจะนำไปเพิ่มเพื่อน LINE OA เพื่อเริ่มสมัครสมาชิก',
                    'ติดตามสถานะการสมัครได้ที่หน้า "ผู้มุ่งหวังของฉัน"',
                ];
            @endphp
            @foreach($steps as $i => $step)
                <div style="display:flex; gap:11px; align-items:flex-start;">
                    <span class="tp-tile" style="width:28px; height:28px; border-radius:9px; font-size:12px; font-weight:800;">{{ $i + 1 }}</span>
                    <div style="font-size:13.5px; padding-top:3px;">{{ $step }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- จุดเด่น --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
        @php
            $features = [
                ['💰', 'รายได้จากการแนะนำ', 'รับค่าคอมมิชชั่นเมื่อเพื่อนที่แนะนำสมัครสำเร็จ'],
                ['📊', 'ติดตามสถานะ', 'ติดตามความคืบหน้าของผู้มุ่งหวังแต่ละคนแบบ Real-time'],
                ['🤝', 'สร้างทีมของคุณ', 'สร้างเครือข่ายและทีมของคุณเองผ่านระบบ MLM'],
            ];
        @endphp
        @foreach($features as [$emoji, $head, $desc])
            <div class="tp-card" style="padding:18px; text-align:center;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px; margin:0 auto;">{{ $emoji }}</span>
                <div style="font-weight:700; font-size:14px; margin-top:10px;">{{ $head }}</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">{{ $desc }}</div>
            </div>
        @endforeach
    </div>

    {{-- ปุ่มสร้าง --}}
    <div class="tp-card" style="padding:28px 20px; text-align:center;">
        <span class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:30px; margin:0 auto;"><i class="fas fa-link" style="color:#fff;"></i></span>
        <div style="font-size:20px; font-weight:800; margin-top:14px;">พร้อมเริ่มต้นแล้วใช่ไหม?</div>
        <div style="font-size:13px; color:var(--ink2); margin-top:4px;">คลิกปุ่มด้านล่างเพื่อสร้างลิงก์เชิญพิเศษของคุณ</div>
        <form method="POST" action="{{ route('user.prospects.store') }}" style="margin-top:18px;">
            @csrf
            <button type="submit" class="tp-btn tp-btn-primary" style="height:48px; padding:0 26px; font-size:15px;"><i class="fas fa-plus"></i> สร้างลิงก์เชิญเลย</button>
        </form>
        <div style="font-size:11px; color:var(--ink2); margin-top:12px;">* ลิงก์เชิญมีอายุ 7 วัน หลังจากนั้นจะหมดอายุอัตโนมัติ</div>
    </div>

    {{-- หมายเหตุ --}}
    <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <div style="font-weight:700; font-size:13.5px; margin-bottom:8px;">⚠️ หมายเหตุสำคัญ</div>
        <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink2); display:flex; flex-direction:column; gap:5px;">
            <li>แต่ละลิงก์เชิญใช้ได้กับบุคคลหนึ่งคนเท่านั้น</li>
            <li>สร้างลิงก์เชิญได้ไม่จำกัดจำนวน</li>
            <li>ลิงก์หมดอายุหลัง 7 วัน หรือเมื่อมีการสมัครสำเร็จ</li>
            <li>ผู้คลิกลิงก์ต้องเพิ่มเพื่อน LINE OA ของเราเพื่อเริ่มสมัคร</li>
        </ul>
    </div>
    @endif
</div>
@endsection

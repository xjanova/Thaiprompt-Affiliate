@extends('layouts.admin-v4')

@section('title', 'เพิ่มองค์ความรู้')

@section('content')
{{-- หน้าเพิ่มองค์ความรู้แม่หมอ (RAG) — ธีม V4 นวลทองคำ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · คลังความรู้
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:10px;">
                <i class="fas fa-brain" style="color:var(--accent1);"></i>
                เพิ่มองค์ความรู้
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:4px;">
                เพิ่มองค์ความรู้ใหม่ที่ AI จะดึงไปใช้ทำนาย (สุขภาพ · ฮวงจุ้ย · เจ้าที่ · องค์เทพ · ไสยศาสตร์)
            </div>
        </div>
        <a href="{{ route('admin.fortune.knowledge.index') }}" class="tp-btn tp-btn-sm">
            <i class="fas fa-arrow-left"></i>
            กลับคลังความรู้
        </a>
    </div>

    {{-- ===== แจ้งเตือน error ===== --}}
    @if($errors->any())
        <div class="tp-card tp-inset" style="padding:16px 18px;border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#d9534f;margin-bottom:8px;">
                <i class="fas fa-circle-exclamation"></i>
                กรุณาตรวจสอบข้อมูล
            </div>
            <ul style="margin:0;padding-left:20px;font-size:13px;color:var(--ink2);line-height:1.7;">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== การ์ดฟอร์ม ===== --}}
    <form method="POST" action="{{ route('admin.fortune.knowledge.store') }}" class="tp-card tp-raise" style="padding:24px;">
        @csrf

        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:18px;">
            <i class="fas fa-feather-pointed" style="color:var(--accent2);"></i>
            <span style="font-weight:700;color:var(--ink);">รายละเอียดองค์ความรู้</span>
        </div>

        {{-- partial ฟอร์มร่วม create/edit — คงไว้ ไม่ inline ซ้ำ --}}
        @include('admin.fortune.knowledge._form', ['item' => null])

        <div class="tp-divider" style="margin:22px 0 18px;"></div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i>
                บันทึก
            </button>
            <a href="{{ route('admin.fortune.knowledge.index') }}" class="tp-btn">
                ยกเลิก
            </a>
        </div>
    </form>

</div>
@endsection

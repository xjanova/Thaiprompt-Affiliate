@extends('layouts.admin-v4')

@section('title', 'แก้ไของค์ความรู้ #' . $item->id)

@section('content')
{{-- หน้าเต็มธีม V4 "นวลทองคำ" — แก้ไของค์ความรู้คลัง RAG แม่หมอ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัว: eyebrow + ชื่อหน้า + ปุ่มกลับ --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · คลังความรู้แม่หมอ (RAG)
            </div>
            <h1 class="tp-num" style="font-size:24px;font-weight:800;color:var(--ink);margin:0;">
                <i class="fas fa-brain" style="color:var(--accent1);margin-right:8px;"></i>
                แก้ไของค์ความรู้ #{{ $item->id }}
            </h1>
            @if(!empty($item->title))
                <div class="tp-muted" style="font-size:13px;margin-top:6px;">{{ $item->title }}</div>
            @endif
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            @if(Route::has('admin.fortune.knowledge.index'))
                <a href="{{ route('admin.fortune.knowledge.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-arrow-left"></i> กลับคลังความรู้
                </a>
            @endif
        </div>
    </div>

    {{-- กล่องแสดง error validation --}}
    @if($errors->any())
        <div class="tp-card tp-inset" style="border-left:4px solid #d9534f;padding:16px;">
            <div style="display:flex;align-items:center;gap:8px;color:#d9534f;font-weight:700;margin-bottom:8px;">
                <i class="fas fa-circle-exclamation"></i> พบข้อผิดพลาด กรุณาตรวจสอบ
            </div>
            <ul style="list-style:disc;padding-left:20px;margin:0;color:var(--ink2);font-size:13px;line-height:1.7;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มแก้ไข — คง action/method/@csrf/@method/partial เดิม 100% --}}
    <form method="POST" action="{{ route('admin.fortune.knowledge.update', $item) }}">
        @csrf
        @method('PUT')

        <div class="tp-card tp-raise" style="padding:24px;">
            <div class="tp-section-h" style="margin-bottom:18px;">
                <i class="fas fa-pen-to-square" style="color:var(--accent1);"></i> รายละเอียดองค์ความรู้
            </div>

            {{-- partial ฟอร์มร่วม create/edit (theme input อยู่ใน _form) --}}
            <div style="display:flex;flex-direction:column;gap:16px;">
                @include('admin.fortune.knowledge._form', ['item' => $item])
            </div>
        </div>

        {{-- แถบปุ่ม action --}}
        <div style="display:flex;gap:12px;padding-top:18px;flex-wrap:wrap;">
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึกการแก้ไข
            </button>
            @if(Route::has('admin.fortune.knowledge.index'))
                <a href="{{ route('admin.fortune.knowledge.index') }}" class="tp-btn">
                    <i class="fas fa-xmark"></i> ยกเลิก
                </a>
            @endif
        </div>
    </form>

</div>
@endsection

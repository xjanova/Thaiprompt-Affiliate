@extends('layouts.admin-v4')

@section('title', 'จัดการหมวดหมู่การทำนาย')

@section('content')
{{-- ===== หน้า "หมวดหมู่การทำนาย — รายการ" ธีม V4 นวลทองคำ ===== --}}
{{-- container หลัก: เว้นระยะแนวตั้งสม่ำเสมอ --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header: ชื่อหน้า + ปุ่มเพิ่มหมวดหมู่ใหม่ ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · หมวดหมู่การทำนาย
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                จัดการหมวดหมู่การทำนาย 📂
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">
                จัดการหมวดหมู่ที่ใช้ในการทำนาย เช่น ความรัก, การเงิน, สุขภาพ
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- ปุ่มเพิ่มหมวดหมู่ใหม่ (ลิงก์ไปหน้า create) --}}
            <a href="{{ route('admin.fortune.categories.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i>
                <span>เพิ่มหมวดหมู่ใหม่</span>
            </a>
        </div>
    </div>

    {{-- ===== KPI สรุป (คำนวณจาก collection ในหน้าปัจจุบัน) ===== --}}
    @php
        // นับสถิติจากรายการในหน้านี้ (paginate) — แสดงภาพรวมคร่าวๆ
        $totalOnPage = $categories->count();
        $activeOnPage = $categories->where('is_active', true)->count();
        $inactiveOnPage = $totalOnPage - $activeOnPage;
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        {{-- การ์ด: หมวดหมู่ในหน้านี้ --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto;">
                <i class="fas fa-folder-open"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($totalOnPage) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">หมวดในหน้านี้</div>
            </div>
        </div>
        {{-- การ์ด: เปิดใช้งาน --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5aa07e; box-shadow:var(--raise);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($activeOnPage) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">เปิดใช้งาน</div>
            </div>
        </div>
        {{-- การ์ด: ปิดใช้งาน --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#9a8f7c; box-shadow:var(--raise);">
                <i class="fas fa-circle-pause"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($inactiveOnPage) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ปิดใช้งาน</div>
            </div>
        </div>
        {{-- การ์ด: ทั้งหมด (รวมทุกหน้า) --}}
        <div class="tp-card" style="padding:18px; display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($categories->total()) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">หมวดทั้งหมด</div>
            </div>
        </div>
    </div>

    {{-- ===== Grid การ์ดหมวดหมู่ ===== --}}
    @if($categories->count())
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px;">
            @foreach($categories as $category)
                {{-- การ์ดหมวดหมู่ 1 ใบ --}}
                <div class="tp-card tp-card-hover" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">

                    {{-- แถบหัวการ์ด: ไอคอน + ชื่อ + สถานะ (ใช้สีหมวดเป็น accent) --}}
                    <div style="padding:18px 18px 16px; position:relative; border-bottom:1px solid transparent; box-shadow:var(--inset-sm);">
                        {{-- แถบสีหมวดด้านบน --}}
                        <div style="position:absolute; top:0; left:0; right:0; height:4px; background:{{ $category->color }};"></div>

                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                            {{-- ไทล์ไอคอน emoji ของหมวด (พื้นจางตามสีหมวด) --}}
                            <div style="width:54px; height:54px; border-radius:16px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; font-size:28px; box-shadow:var(--raise); background:linear-gradient(135deg, {{ $category->color }}26 0%, {{ $category->color }}3d 100%);">
                                {{ $category->icon }}
                            </div>
                            {{-- ป้ายสถานะ is_active --}}
                            @if($category->is_active)
                                <span class="tp-pill" style="background:#5aa07e; color:#fff;">
                                    <i class="fas fa-circle" style="font-size:7px;"></i> เปิดใช้งาน
                                </span>
                            @else
                                <span class="tp-pill" style="background:var(--surf); color:var(--ink2); box-shadow:var(--inset-sm);">
                                    <i class="fas fa-circle" style="font-size:7px;"></i> ปิดใช้งาน
                                </span>
                            @endif
                        </div>

                        {{-- ชื่อหมวด --}}
                        <h3 class="tp-num" style="font-size:18px; font-weight:800; margin:14px 0 0; line-height:1.3;">
                            {{ $category->name }}
                        </h3>
                        {{-- คำอธิบาย --}}
                        <p class="tp-muted" style="margin:6px 0 0; font-size:13px; line-height:1.5; min-height:20px;">
                            {{ $category->description ? Str::limit($category->description, 80) : 'ยังไม่มีคำอธิบาย' }}
                        </p>
                    </div>

                    {{-- ส่วนล่าง: slug / สี / ลำดับ + ปุ่ม action --}}
                    <div style="padding:16px 18px; display:flex; flex-direction:column; gap:12px; flex:1 1 auto;">

                        {{-- meta: slug --}}
                        <div style="display:flex; align-items:center; gap:8px; font-size:12.5px;">
                            <span style="color:var(--ink2); min-width:46px;">Slug</span>
                            <code class="tp-well" style="padding:3px 9px; border-radius:8px; font-size:11.5px; color:var(--deep1); box-shadow:var(--inset-sm); word-break:break-all;">{{ $category->slug }}</code>
                        </div>

                        {{-- meta: สี + ลำดับ --}}
                        <div style="display:flex; align-items:center; gap:18px; font-size:12.5px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="color:var(--ink2); min-width:46px;">สี</span>
                                <span style="display:inline-flex; align-items:center; gap:6px;">
                                    <span style="width:16px; height:16px; border-radius:5px; display:inline-block; background:{{ $category->color }}; box-shadow:var(--raise);"></span>
                                    <code style="font-size:11.5px; color:var(--ink2);">{{ $category->color }}</code>
                                </span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="color:var(--ink2);">ลำดับ</span>
                                <span class="tp-num" style="font-weight:700; color:var(--deep1);">{{ $category->order }}</span>
                            </div>
                        </div>

                        {{-- เส้นคั่น --}}
                        <div class="tp-divider" style="margin:2px 0;"></div>

                        {{-- ปุ่ม action: แก้ไข + ลบ --}}
                        <div style="display:flex; gap:9px; align-items:center;">
                            {{-- ปุ่มแก้ไข (ลิงก์ไปหน้า edit) --}}
                            <a href="{{ route('admin.fortune.categories.edit', $category) }}"
                               class="tp-btn tp-btn-sm"
                               style="flex:1 1 auto; justify-content:center;">
                                <i class="fas fa-pen"></i> แก้ไข
                            </a>

                            {{-- ฟอร์มลบ (DELETE + ยืนยันก่อนลบ) — คงฟังก์ชันเดิม 100% --}}
                            <form action="{{ route('admin.fortune.categories.destroy', $category) }}"
                                  method="POST"
                                  onsubmit="return confirm('แน่ใจหรือไม่ที่จะลบหมวดหมู่นี้?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="tp-icon-btn"
                                        title="ลบหมวดหมู่"
                                        style="color:#d9534f;">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== Pagination ===== --}}
        @if($categories->hasPages())
            <div class="tp-card" style="padding:12px 16px;">
                {{ $categories->links() }}
            </div>
        @endif
    @else
        {{-- ===== Empty state ===== --}}
        <div class="tp-card" style="padding:48px 24px;">
            <div style="text-align:center; color:var(--ink2);">
                <i class="fas fa-folder-open" style="font-size:34px; display:block; margin-bottom:12px; opacity:.5;"></i>
                <div class="tp-num" style="font-size:17px; font-weight:700; color:var(--ink); margin-bottom:6px;">
                    ยังไม่มีหมวดหมู่การทำนาย
                </div>
                <div style="font-size:13px; margin-bottom:18px;">
                    กดปุ่มด้านล่างเพื่อสร้างหมวดหมู่แรกของคุณ
                </div>
                <a href="{{ route('admin.fortune.categories.create') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-plus"></i> เพิ่มหมวดหมู่ใหม่
                </a>
            </div>
        </div>
    @endif

</div>
@endsection

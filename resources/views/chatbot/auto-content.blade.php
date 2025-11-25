@extends('layouts.app')

@section('title', 'Auto Content - ' . $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-magic text-primary me-2"></i>
                        Auto Content
                    </h2>
                    <p class="text-muted mb-0">{{ $bot->display_name }}</p>
                </div>
                <div>
                    <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- คำอธิบาย -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Auto Content</strong> - ตั้งค่าให้ Bot สร้างและส่งเนื้อหาอัตโนมัติตามกำหนดเวลา เช่น ข้อความต้อนรับ, โปรโมชั่นประจำวัน, หรือเนื้อหาที่กำหนดเอง
            </div>
        </div>
    </div>

    <!-- Auto Content Settings -->
    <div class="row">
        <div class="col-lg-8">
            <!-- ข้อความต้อนรับ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-hand-wave text-primary me-2"></i>
                            ข้อความต้อนรับ
                        </h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="welcomeEnabled">
                            <label class="form-check-label" for="welcomeEnabled">เปิดใช้งาน</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ข้อความต้อนรับ</label>
                        <textarea class="form-control" rows="3" placeholder="สวัสดีครับ! ยินดีต้อนรับสู่..."></textarea>
                        <div class="form-text">ข้อความที่จะส่งเมื่อผู้ใช้เริ่มสนทนาครั้งแรก</div>
                    </div>
                </div>
            </div>

            <!-- ข้อความตามกำหนดเวลา -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-primary me-2"></i>
                            ข้อความตามกำหนดเวลา
                        </h5>
                        <button class="btn btn-sm btn-primary" onclick="alert('ฟีเจอร์นี้กำลังพัฒนา')">
                            <i class="fas fa-plus me-2"></i>เพิ่ม
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
                        <h5>ยังไม่มีข้อความตามกำหนดเวลา</h5>
                        <p class="text-muted">เพิ่มข้อความที่จะส่งอัตโนมัติตามเวลาที่กำหนด</p>
                    </div>
                </div>
            </div>

            <!-- Quick Replies -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-reply text-primary me-2"></i>
                            Quick Replies
                        </h5>
                        <button class="btn btn-sm btn-primary" onclick="alert('ฟีเจอร์นี้กำลังพัฒนา')">
                            <i class="fas fa-plus me-2"></i>เพิ่ม
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        <h5>ยังไม่มี Quick Replies</h5>
                        <p class="text-muted">เพิ่มปุ่มตอบกลับด่วนเพื่อให้ผู้ใช้เลือกได้ง่าย</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        เคล็ดลับ
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="fas fa-check text-success me-2"></i>
                            ใช้ข้อความต้อนรับเพื่อแนะนำ Bot
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check text-success me-2"></i>
                            ตั้งเวลาส่งโปรโมชั่นในช่วงที่ลูกค้าออนไลน์มาก
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check text-success me-2"></i>
                            ใช้ Quick Replies เพื่อนำทางผู้ใช้
                        </li>
                        <li>
                            <i class="fas fa-check text-success me-2"></i>
                            ทดสอบข้อความก่อนเปิดใช้งานจริง
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ปุ่มบันทึก -->
    <div class="row mt-4">
        <div class="col-12">
            <button class="btn btn-primary" onclick="alert('ฟีเจอร์นี้กำลังพัฒนา')">
                <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </div>
</div>
@endsection

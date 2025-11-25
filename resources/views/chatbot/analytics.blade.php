@extends('layouts.app')

@section('title', 'Analytics - ' . $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Analytics
                    </h2>
                    <p class="text-muted mb-0">{{ $bot->display_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select" style="width: auto;">
                        <option value="7">7 วันที่ผ่านมา</option>
                        <option value="30" selected>30 วันที่ผ่านมา</option>
                        <option value="90">90 วันที่ผ่านมา</option>
                    </select>
                    <a href="{{ route('chatbot.show', $bot->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">บทสนทนาทั้งหมด</p>
                            <h3 class="mb-0">{{ number_format($stats['total_conversations'] ?? 0) }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-comments fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">ข้อความทั้งหมด</p>
                            <h3 class="mb-0">{{ number_format($stats['total_messages'] ?? 0) }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-paper-plane fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Tokens ที่ใช้</p>
                            <h3 class="mb-0">{{ number_format($stats['total_tokens'] ?? 0) }}</h3>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-microchip fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">ค่าใช้จ่าย</p>
                            <h3 class="mb-0">${{ number_format($stats['total_cost'] ?? 0, 4) }}</h3>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-lg-8">
            <!-- กราฟบทสนทนา -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area text-primary me-2"></i>
                        บทสนทนารายวัน
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                        <h5>กราฟบทสนทนา</h5>
                        <p class="text-muted">ข้อมูลกราฟจะแสดงที่นี่</p>
                    </div>
                </div>
            </div>

            <!-- กราฟ Token Usage -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        การใช้ Token
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                        <h5>กราฟการใช้ Token</h5>
                        <p class="text-muted">ข้อมูลกราฟจะแสดงที่นี่</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- ประสิทธิภาพ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-tachometer-alt text-primary me-2"></i>
                        ประสิทธิภาพ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">เวลาตอบกลับเฉลี่ย</span>
                            <span class="fw-semibold">{{ number_format($stats['avg_response_time'] ?? 0) }} ms</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 85%;"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">อัตราความสำเร็จ</span>
                            <span class="fw-semibold">98.5%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 98.5%;"></div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ความพึงพอใจ</span>
                            <span class="fw-semibold">4.5/5</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 90%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Keywords -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-hashtag text-primary me-2"></i>
                        Keywords ยอดนิยม
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">ยังไม่มีข้อมูล Keywords</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

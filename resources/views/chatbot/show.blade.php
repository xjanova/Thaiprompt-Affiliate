@extends('layouts.app')

@section('title', $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4" x-data="botDetail()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-robot fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="mb-1">{{ $bot->display_name }}</h2>
                        <p class="text-muted mb-0">
                            <code>{{ $bot->name }}</code>
                            @if($bot->is_active)
                                <span class="badge bg-success ms-2">ใช้งาน</span>
                            @else
                                <span class="badge bg-secondary ms-2">ปิดใช้งาน</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('chatbot.edit', $bot->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>แก้ไข
                    </a>
                    <a href="{{ route('chatbot.index') }}" class="btn btn-outline-secondary">
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
                            <p class="text-muted mb-1 small">บทสนทนา</p>
                            <h3 class="mb-0">{{ number_format($bot->conversations_count ?? 0) }}</h3>
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
                            <p class="text-muted mb-1 small">การเช่า</p>
                            <h3 class="mb-0">{{ number_format($bot->active_rentals_count ?? 0) }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-handshake fa-2x"></i>
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
                            <p class="text-muted mb-1 small">AI Provider</p>
                            <h5 class="mb-0">{{ $bot->provider->display_name ?? '-' }}</h5>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-server fa-2x"></i>
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
                            <p class="text-muted mb-1 small">AI Model</p>
                            <h5 class="mb-0">{{ $bot->model->display_name ?? '-' }}</h5>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-brain fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ข้อมูล Bot -->
        <div class="col-lg-8">
            <!-- ข้อมูลพื้นฐาน -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        ข้อมูล Bot
                    </h5>
                </div>
                <div class="card-body">
                    @if($bot->description)
                    <div class="row mb-3">
                        <div class="col-4 fw-semibold text-muted">คำอธิบาย</div>
                        <div class="col-8">{{ $bot->description }}</div>
                    </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-4 fw-semibold text-muted">Temperature</div>
                        <div class="col-8">{{ $bot->temperature ?? '0.7' }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4 fw-semibold text-muted">Max Tokens</div>
                        <div class="col-8">{{ number_format($bot->max_tokens ?? 2000) }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4 fw-semibold text-muted">Top P</div>
                        <div class="col-8">{{ $bot->top_p ?? '1.0' }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4 fw-semibold text-muted">สร้างเมื่อ</div>
                        <div class="col-8">{{ $bot->created_at->format('d M Y H:i') }}</div>
                    </div>

                    <div class="row">
                        <div class="col-4 fw-semibold text-muted">อัปเดตล่าสุด</div>
                        <div class="col-8">{{ $bot->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>

            <!-- System Prompt -->
            @if($bot->system_prompt)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-terminal text-primary me-2"></i>
                        System Prompt
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="bg-dark text-light p-3 rounded mb-0" style="white-space: pre-wrap;">{{ $bot->system_prompt }}</pre>
                </div>
            </div>
            @endif
        </div>

        <!-- แถบด้านข้าง -->
        <div class="col-lg-4">
            <!-- เมนูจัดการ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-cog text-primary me-2"></i>
                        การจัดการ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('chatbot.keywords', $bot->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-key me-2"></i>จัดการ Keywords
                        </a>
                        <a href="{{ route('chatbot.integrations', $bot->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-plug me-2"></i>Platform Integrations
                        </a>
                        <a href="{{ route('chatbot.auto-content', $bot->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-magic me-2"></i>Auto Content
                        </a>
                        <a href="{{ route('chatbot.analytics', $bot->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line me-2"></i>Analytics
                        </a>
                        <a href="{{ route('chatbot.playground', $bot->id) }}" class="btn btn-outline-success">
                            <i class="fas fa-vial me-2"></i>ทดสอบ Bot
                        </a>
                    </div>
                </div>
            </div>

            <!-- LINE OA -->
            @if($bot->line_oa_channel_id)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fab fa-line text-success me-2"></i>
                        LINE OA Integration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>เชื่อมต่อกับ LINE OA แล้ว</strong>
                        <hr>
                        <small>
                            <strong>Channel ID:</strong> {{ $bot->line_oa_channel_id }}
                        </small>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function botDetail() {
    return {
        // สามารถเพิ่ม logic เพิ่มเติมได้ที่นี่
    }
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'Platform Integrations - ' . $bot->display_name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plug text-primary me-2"></i>
                        Platform Integrations
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
                <strong>Platform Integrations</strong> - เชื่อมต่อ Bot ของคุณกับแพลตฟอร์มต่างๆ เพื่อให้ลูกค้าสามารถสนทนากับ Bot ได้จากทุกช่องทาง
            </div>
        </div>
    </div>

    <!-- รายการ Platforms -->
    <div class="row">
        @foreach($platforms as $key => $platform)
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="{{ $platform['icon'] }} fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $platform['name'] }}</h5>
                        </div>
                    </div>

                    @php
                        $integration = $bot->platformIntegrations->where('platform', $key)->first();
                    @endphp

                    @if($integration && $integration->is_active)
                        <div class="alert alert-success py-2 mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            เชื่อมต่อแล้ว
                        </div>
                        <button class="btn btn-outline-primary w-100" onclick="alert('ฟีเจอร์นี้กำลังพัฒนา')">
                            <i class="fas fa-cog me-2"></i>จัดการ
                        </button>
                    @else
                        <div class="alert alert-secondary py-2 mb-3">
                            <i class="fas fa-times-circle me-2"></i>
                            ยังไม่ได้เชื่อมต่อ
                        </div>
                        <button class="btn btn-primary w-100" onclick="alert('ฟีเจอร์นี้กำลังพัฒนา')">
                            <i class="fas fa-link me-2"></i>เชื่อมต่อ
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Webhook URL -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-link text-primary me-2"></i>
                        Webhook URL
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">ใช้ URL นี้สำหรับตั้งค่า Webhook ในแต่ละแพลตฟอร์ม:</p>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ url('/webhook/chatbot/' . $bot->id) }}" readonly id="webhookUrl">
                        <button class="btn btn-outline-primary" onclick="copyWebhookUrl()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrl');
    input.select();
    document.execCommand('copy');
    alert('คัดลอก URL แล้ว');
}
</script>
@endpush

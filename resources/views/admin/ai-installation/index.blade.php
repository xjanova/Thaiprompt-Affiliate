@extends('layouts.admin')

@section('title', 'ติดตั้ง AI ของตัวเอง (Self-Hosted)')

@push('styles')
<style>
/* LINE OA Color Scheme */
:root {
    --line-green: #06C755;
    --line-green-dark: #00B900;
    --line-green-light: #E8F7EF;
    --line-gray: #F7F7F7;
    --line-border: #E0E0E0;
    --line-text: #333333;
    --line-text-light: #6C6C6C;
}

/* Main Container */
.ai-installation-container {
    background: #FAFAFA;
    min-height: 100vh;
}

/* Navigation Tabs */
.line-tabs {
    background: white;
    border-bottom: 1px solid var(--line-border);
    margin-bottom: 0;
}

.line-tabs .nav-link {
    color: var(--line-text-light);
    border: none;
    border-bottom: 3px solid transparent;
    padding: 15px 24px;
    font-weight: 500;
    transition: all 0.2s;
}

.line-tabs .nav-link:hover {
    color: var(--line-green);
    border-bottom-color: var(--line-green-light);
}

.line-tabs .nav-link.active {
    color: var(--line-green);
    border-bottom-color: var(--line-green);
    background: transparent;
}

/* Header Section */
.line-header {
    background: white;
    padding: 24px;
    border-bottom: 1px solid var(--line-border);
    margin-bottom: 24px;
}

.line-header h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--line-text);
    margin-bottom: 8px;
}

.line-header p {
    color: var(--line-text-light);
    font-size: 14px;
    margin: 0;
}

/* Info Banner */
.line-info-banner {
    background: var(--line-green-light);
    border: 1px solid #C8E6D5;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}

.line-info-banner .icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--line-green);
    font-size: 20px;
}

/* Feature Cards */
.line-feature-card {
    background: white;
    border: 1px solid var(--line-border);
    border-radius: 8px;
    padding: 20px;
    height: 100%;
    transition: all 0.2s;
}

.line-feature-card:hover {
    border-color: var(--line-green);
    box-shadow: 0 2px 8px rgba(6, 199, 85, 0.1);
}

.line-feature-card .icon {
    width: 48px;
    height: 48px;
    background: var(--line-green-light);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--line-green);
    font-size: 24px;
    margin-bottom: 16px;
}

.line-feature-card h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--line-text);
    margin-bottom: 8px;
}

.line-feature-card p {
    font-size: 14px;
    color: var(--line-text-light);
    margin: 0;
    line-height: 1.5;
}

/* Comparison Table */
.line-comparison-card {
    background: white;
    border: 1px solid var(--line-border);
    border-radius: 8px;
    padding: 24px;
    height: 100%;
    transition: all 0.2s;
}

.line-comparison-card.highlight {
    border: 2px solid var(--line-green);
    position: relative;
}

.line-comparison-card.highlight::before {
    content: '推奨';
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--line-green);
    color: white;
    padding: 4px 16px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.line-comparison-card h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--line-text);
    margin-bottom: 16px;
}

.line-comparison-card .price {
    font-size: 32px;
    font-weight: 700;
    color: var(--line-green);
    margin-bottom: 20px;
}

.line-comparison-card .price small {
    font-size: 14px;
    font-weight: 400;
    color: var(--line-text-light);
}

/* Wizard Steps */
.line-wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 32px;
    position: relative;
    padding: 0 60px;
}

.line-wizard-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 60px;
    right: 60px;
    height: 2px;
    background: var(--line-border);
    z-index: 0;
}

.line-wizard-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.line-wizard-step .circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--line-border);
    color: var(--line-text-light);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
    transition: all 0.2s;
}

.line-wizard-step.active .circle {
    background: var(--line-green);
    border-color: var(--line-green);
    color: white;
}

.line-wizard-step.completed .circle {
    background: var(--line-green);
    border-color: var(--line-green);
    color: white;
}

.line-wizard-step .label {
    font-size: 13px;
    color: var(--line-text-light);
}

.line-wizard-step.active .label {
    color: var(--line-green);
    font-weight: 600;
}

/* Stats Card */
.line-stats-card {
    background: white;
    border: 1px solid var(--line-border);
    border-radius: 8px;
    padding: 20px;
    height: 100%;
}

.line-stats-card .header {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.line-stats-card .icon {
    width: 40px;
    height: 40px;
    background: var(--line-green-light);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--line-green);
    margin-right: 12px;
}

.line-stats-card h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--line-text);
    margin: 0;
}

.line-stats-card .value {
    font-size: 24px;
    font-weight: 700;
    color: var(--line-text);
    margin-bottom: 4px;
}

.line-stats-card .description {
    font-size: 13px;
    color: var(--line-text-light);
}

/* Model Card */
.line-model-card {
    background: white;
    border: 1px solid var(--line-border);
    border-radius: 8px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s;
    height: 100%;
}

.line-model-card:hover {
    border-color: var(--line-green);
    box-shadow: 0 2px 8px rgba(6, 199, 85, 0.1);
}

.line-model-card.selected {
    border: 2px solid var(--line-green);
    background: var(--line-green-light);
}

.line-model-card h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--line-text);
    margin-bottom: 8px;
}

.line-model-card .badge {
    background: var(--line-green);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.line-model-card .badge.secondary {
    background: #F0F0F0;
    color: var(--line-text);
}

/* Progress Bar */
.line-progress {
    height: 8px;
    background: var(--line-gray);
    border-radius: 4px;
    overflow: hidden;
}

.line-progress .bar {
    height: 100%;
    background: var(--line-green);
    transition: width 0.3s ease;
}

/* Buttons */
.btn-line {
    background: var(--line-green);
    border: none;
    color: white;
    padding: 10px 24px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-line:hover {
    background: var(--line-green-dark);
    color: white;
}

.btn-line-outline {
    background: white;
    border: 1px solid var(--line-border);
    color: var(--line-text);
    padding: 10px 24px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-line-outline:hover {
    border-color: var(--line-green);
    color: var(--line-green);
}

.btn-line-lg {
    padding: 12px 32px;
    font-size: 16px;
}

/* Installation Log */
.line-log {
    background: #2D2D2D;
    border: 1px solid #404040;
    border-radius: 8px;
    padding: 16px;
    max-height: 300px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #E0E0E0;
}

.line-log::-webkit-scrollbar {
    width: 6px;
}

.line-log::-webkit-scrollbar-track {
    background: #1e1e1e;
}

.line-log::-webkit-scrollbar-thumb {
    background: var(--line-green);
    border-radius: 3px;
}

/* Alerts */
.line-alert {
    border: 1px solid;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}

.line-alert.success {
    background: var(--line-green-light);
    border-color: #C8E6D5;
    color: var(--line-text);
}

.line-alert.info {
    background: #E3F2FD;
    border-color: #BBDEFB;
    color: var(--line-text);
}

.line-alert.warning {
    background: #FFF3E0;
    border-color: #FFE0B2;
    color: var(--line-text);
}

.line-alert.error {
    background: #FFEBEE;
    border-color: #FFCDD2;
    color: var(--line-text);
}

/* Content Section */
.line-section {
    background: white;
    border: 1px solid var(--line-border);
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 16px;
}

.line-section-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 20px;
}

.line-section-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: var(--line-text);
    margin: 0;
}

/* Table */
.line-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.line-table thead th {
    background: var(--line-gray);
    color: var(--line-text);
    font-weight: 600;
    font-size: 13px;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--line-border);
}

.line-table tbody td {
    padding: 16px;
    border-bottom: 1px solid var(--line-border);
    font-size: 14px;
    color: var(--line-text);
}

.line-table tbody tr:hover {
    background: var(--line-gray);
}

/* Badge */
.line-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.line-badge.success {
    background: var(--line-green-light);
    color: var(--line-green-dark);
}

.line-badge.warning {
    background: #FFF3E0;
    color: #F57C00;
}

.line-badge.error {
    background: #FFEBEE;
    color: #D32F2F;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.pulse-animation {
    animation: pulse 2s infinite;
}
</style>
@endpush

@section('content')
<div class="ai-installation-container">
    <!-- Navigation Tabs -->
    <ul class="nav line-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="#">
                <i class="fas fa-home me-2"></i>ภาพรวม
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="startNewInstallation(); return false;">
                <i class="fas fa-download me-2"></i>ติดตั้งโมเดล
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-cog me-2"></i>ตั้งค่า
            </a>
        </li>
    </ul>

    <div class="container-fluid" style="padding: 24px;">
        <!-- Header -->
        <div class="line-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1>ติดตั้ง AI ของตัวเอง (Self-Hosted)</h1>
                    <p>ติดตั้ง DeepSeek AI บนเซิร์ฟเวอร์ของคุณเอง ใช้งานฟรี ไม่จำกัด ไม่มีค่าใช้จ่ายรายเดือน</p>
                </div>
                <button type="button" class="btn-line btn-line-lg" onclick="startNewInstallation()">
                    <i class="fas fa-plus me-2"></i>ติดตั้งโมเดลใหม่
                </button>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="line-info-banner">
            <div class="d-flex align-items-center">
                <div class="icon">
                    <i class="fas fa-info"></i>
                </div>
                <div class="ms-3">
                    <strong>ประหยัดค่าใช้จ่ายได้มากกว่า 90%</strong>
                    <p class="mb-0" style="font-size: 14px;">เมื่อเปรียบเทียบกับ ChatGPT API หรือ Claude API พร้อมความเป็นส่วนตัวและปลอดภัย 100%</p>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="line-feature-card">
                    <div class="icon">
                        <i class="fas fa-infinity"></i>
                    </div>
                    <h3>ใช้งานไม่จำกัด</h3>
                    <p>ไม่มีข้อจำกัดจำนวนคำถาม ไม่มีค่าใช้จ่ายรายเดือน ใช้งานได้เท่าที่ต้องการ</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="line-feature-card">
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>ปลอดภัย 100%</h3>
                    <p>ข้อมูลอยู่บนเซิร์ฟเวอร์ของคุณ ไม่ส่งข้อมูลออกไปภายนอก รับรองความเป็นส่วนตัว</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="line-feature-card">
                    <div class="icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h3>ประหยัดงบ 90%+</h3>
                    <p>เทียบกับ ChatGPT API หรือ Claude ประหยัดได้หลักหมื่นบาทต่อเดือน</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="line-feature-card">
                    <div class="icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>รวดเร็ว ทันใจ</h3>
                    <p>ติดตั้งบนเซิร์ฟเวอร์ของคุณ ไม่ต้องรอคิว ตอบสนองเร็วกว่า Cloud API</p>
                </div>
            </div>
        </div>

        <!-- Pricing Comparison -->
        <div class="line-section">
            <h2 class="mb-4" style="font-size: 18px; font-weight: 600;">เปรียบเทียบค่าใช้จ่าย</h2>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="line-comparison-card">
                        <h3>ChatGPT API</h3>
                        <div class="price">$20-200<small>/เดือน</small></div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>จำกัดจำนวนคำถาม</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ค่าใช้จ่ายสูง</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ข้อมูลถูกส่งออกไป</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ต้องพึ่งพา API ภายนอก</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="line-comparison-card highlight">
                        <h3>Self-Hosted AI</h3>
                        <div class="price">฿0<small>/เดือน</small></div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="fas fa-check me-2" style="color: var(--line-green);"></i>ใช้ไม่จำกัด ฟรี!</li>
                            <li class="mb-2"><i class="fas fa-check me-2" style="color: var(--line-green);"></i>ไม่มีค่าใช้จ่ายรายเดือน</li>
                            <li class="mb-2"><i class="fas fa-check me-2" style="color: var(--line-green);"></i>ข้อมูลอยู่กับคุณ 100%</li>
                            <li class="mb-2"><i class="fas fa-check me-2" style="color: var(--line-green);"></i>ควบคุมได้เองทั้งหมด</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="line-comparison-card">
                        <h3>Claude API</h3>
                        <div class="price">$15-150<small>/เดือน</small></div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>คิดตามจำนวน tokens</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ราคาแพง</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ข้อมูลส่งไป Anthropic</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>มีข้อจำกัด rate limit</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="line-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>จัดการ AI โมเดล</h2>
                    <p class="text-muted mb-0" style="font-size: 14px;">ติดตั้งและจัดการโมเดล AI ที่คุณต้องการ</p>
                </div>
            </div>
            <!-- Ollama Status -->
            <div id="ollama-status" class="line-alert info">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>กำลังตรวจสอบสถานะ Ollama...</span>
                </div>
            </div>

            <!-- Installed Models -->
            <div class="mt-4">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">โมเดลที่ติดตั้งแล้ว</h3>
                <div id="installed-models">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status" style="color: var(--line-green);"></div>
                        <p class="mt-2 text-muted">กำลังโหลด...</p>
                    </div>
                </div>
            </div>

            <!-- Recent Installation Logs -->
            @if($recentLogs->count() > 0)
            <div class="mt-4">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">ประวัติการติดตั้งล่าสุด</h3>
                <div class="table-responsive">
                    <table class="line-table">
                        <thead>
                            <tr>
                                <th>เวลา</th>
                                <th>โมเดล</th>
                                <th>สถานะ</th>
                                <th>Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                                <td>{{ $log->config['model_id'] ?? 'N/A' }}</td>
                                <td>
                                    <span class="line-badge {{ $log->isCompleted() ? 'success' : ($log->isFailed() ? 'error' : 'warning') }}">
                                        {{ $log->getStatusMessage() }}
                                    </span>
                                </td>
                                <td>{{ $log->progress_percentage }}%</td>
                                <td>
                                    <button class="btn-line-outline" style="padding: 6px 12px; font-size: 13px;" onclick="viewLog({{ $log->id }})">
                                        <i class="fas fa-eye me-1"></i>ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</div>

<!-- Installation Wizard Modal -->
<div class="modal fade" id="installationWizard" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 8px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--line-border); padding: 24px;">
                <div class="w-100">
                    <h4 class="modal-title mb-0" style="font-size: 20px; font-weight: 600; color: var(--line-text);">ติดตั้ง AI โมเดล</h4>
                    <p class="text-muted mb-4 mt-2" style="font-size: 14px;">ติดตั้งและกำหนดค่า AI โมเดลของคุณภายใน 4 ขั้นตอน</p>

                    <!-- Step Indicator -->
                    <div class="line-wizard-steps">
                        <div class="line-wizard-step" id="wizard-step-1">
                            <div class="circle">1</div>
                            <div class="label">ตรวจสอบระบบ</div>
                        </div>
                        <div class="line-wizard-step" id="wizard-step-2">
                            <div class="circle">2</div>
                            <div class="label">เลือกโมเดล</div>
                        </div>
                        <div class="line-wizard-step" id="wizard-step-3">
                            <div class="circle">3</div>
                            <div class="label">กำลังติดตั้ง</div>
                        </div>
                        <div class="line-wizard-step" id="wizard-step-4">
                            <div class="circle">4</div>
                            <div class="label">เสร็จสิ้น</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelWizard()"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <!-- Step 1: Check Requirements -->
                <div id="step-requirements" class="wizard-step">
                    <div id="requirements-result">
                        <div class="text-center py-5">
                            <div style="width: 80px; height: 80px; background: var(--line-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                                <i class="fas fa-server" style="font-size: 40px; color: var(--line-green);"></i>
                            </div>
                            <h5 class="mb-3" style="font-size: 18px; font-weight: 600; color: var(--line-text);">ตรวจสอบความพร้อมของระบบ</h5>
                            <p class="text-muted mb-4" style="font-size: 14px;">เราจะตรวจสอบ CPU, RAM, GPU, และพื้นที่ว่างเพื่อแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ</p>
                            <button class="btn-line btn-line-lg" onclick="checkRequirements()">
                                <i class="fas fa-check-circle me-2"></i>
                                เริ่มตรวจสอบระบบ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Model -->
                <div id="step-model" class="wizard-step" style="display:none;">
                    <div class="mb-4">
                        <h5 style="font-size: 18px; font-weight: 600; color: var(--line-text);">เลือกโมเดล AI ที่ต้องการติดตั้ง</h5>
                        <p class="text-muted" style="font-size: 14px;">เราแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ คุณสามารถเลือกโมเดลอื่นได้ตามต้องการ</p>
                    </div>
                    <div id="model-recommendations"></div>
                </div>

                <!-- Step 3: Installing -->
                <div id="step-installing" class="wizard-step" style="display:none;">
                    <div class="text-center mb-4">
                        <div style="width: 80px; height: 80px; background: var(--line-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                            <i class="fas fa-cog fa-spin" style="font-size: 40px; color: var(--line-green);"></i>
                        </div>
                        <h5 class="mb-2" style="font-size: 18px; font-weight: 600; color: var(--line-text);">กำลังติดตั้งโมเดล AI</h5>
                        <p class="text-muted" style="font-size: 14px;">กรุณารอสักครู่ การติดตั้งอาจใช้เวลาหลายนาทีขึ้นอยู่กับขนาดโมเดล</p>
                    </div>
                    <div id="installation-progress"></div>
                </div>

                <!-- Step 4: Complete -->
                <div id="step-complete" class="wizard-step" style="display:none;">
                    <div class="text-center py-5">
                        <div style="width: 100px; height: 100px; background: var(--line-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                            <i class="fas fa-check-circle" style="font-size: 50px; color: var(--line-green);"></i>
                        </div>
                        <h3 class="mb-3" style="font-size: 24px; font-weight: 600; color: var(--line-text);">ติดตั้งสำเร็จ!</h3>
                        <p class="text-muted mb-4" style="font-size: 14px;">โมเดล AI ของคุณพร้อมใช้งานแล้ว คุณสามารถเริ่มใช้งาน AI ในระบบได้ทันที</p>
                        <div class="d-flex justify-content-center gap-3">
                            <button class="btn-line btn-line-lg" onclick="closeWizard()">
                                <i class="fas fa-check me-2"></i>
                                เสร็จสิ้น
                            </button>
                            <button class="btn-line-outline btn-line-lg" onclick="startNewInstallation()">
                                <i class="fas fa-plus me-2"></i>
                                ติดตั้งโมเดลอื่น
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let installationId = null;
let progressInterval = null;

// Load initial data
$(document).ready(function() {
    checkOllamaStatus();
    loadInstalledModels();
});

function checkOllamaStatus() {
    $.get('{{ route('admin.ai-installation.ollama-status') }}', function(response) {
        const status = response.data;
        let html = '';

        if (status.installed && status.running) {
            html = `
                <div class="line-alert success">
                    <i class="fas fa-check-circle me-2"></i>
                    Ollama กำลังทำงาน (${status.version})
                </div>
            `;
        } else if (status.installed && !status.running) {
            html = `
                <div class="line-alert warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ollama ติดตั้งแล้วแต่ไม่ได้ทำงาน กรุณาเริ่ม Ollama service
                </div>
            `;
        } else {
            html = `
                <div class="line-alert info">
                    <i class="fas fa-info-circle me-2"></i>
                    Ollama ยังไม่ได้ติดตั้ง จะติดตั้งอัตโนมัติเมื่อเริ่มติดตั้งโมเดล
                </div>
            `;
        }

        $('#ollama-status').html(html);
    });
}

function loadInstalledModels() {
    $.get('{{ route('admin.ai-installation.installed-models') }}', function(response) {
        const models = response.data;

        if (models.length === 0) {
            $('#installed-models').html(`
                <div class="line-alert info">
                    <i class="fas fa-info-circle me-2"></i>
                    ยังไม่มีโมเดลที่ติดตั้ง
                </div>
            `);
            return;
        }

        let html = `
            <table class="line-table">
                <thead>
                    <tr>
                        <th>โมเดล</th>
                        <th>ID</th>
                        <th>ขนาด</th>
                        <th>แก้ไขล่าสุด</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
        `;

        models.forEach(model => {
            html += `
                <tr>
                    <td><strong>${model.name}</strong></td>
                    <td><small class="text-muted">${model.id}</small></td>
                    <td>${model.size}</td>
                    <td>${model.modified}</td>
                    <td>
                        <button class="btn-line-outline" style="padding: 6px 12px; font-size: 13px;" onclick="uninstallModel('${model.name}')">
                            <i class="fas fa-trash me-1"></i> ลบ
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        $('#installed-models').html(html);
    });
}

function startNewInstallation() {
    // Reset wizard
    $('.wizard-step').hide();
    $('#step-requirements').show();
    updateWizardStep(1);

    // Reset requirements result
    $('#requirements-result').html(`
        <div class="text-center py-5">
            <div style="width: 80px; height: 80px; background: var(--line-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="fas fa-server" style="font-size: 40px; color: var(--line-green);"></i>
            </div>
            <h5 class="mb-3" style="font-size: 18px; font-weight: 600; color: var(--line-text);">ตรวจสอบความพร้อมของระบบ</h5>
            <p class="text-muted mb-4" style="font-size: 14px;">เราจะตรวจสอบ CPU, RAM, GPU, และพื้นที่ว่างเพื่อแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ</p>
            <button class="btn-line btn-line-lg" onclick="checkRequirements()">
                <i class="fas fa-check-circle me-2"></i>
                เริ่มตรวจสอบระบบ
            </button>
        </div>
    `);

    $('#installationWizard').modal('show');
}

function updateWizardStep(step) {
    // Reset all steps
    $('.line-wizard-step').removeClass('active completed');

    // Mark completed steps
    for (let i = 1; i < step; i++) {
        $(`#wizard-step-${i}`).addClass('completed');
    }

    // Mark active step
    $(`#wizard-step-${step}`).addClass('active');
}

function closeWizard() {
    $('#installationWizard').modal('hide');
    loadInstalledModels();
    checkOllamaStatus();
}

function cancelWizard() {
    if (installationId && confirm('คุณต้องการยกเลิกการติดตั้งหรือไม่?')) {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
        $('#installationWizard').modal('hide');
    } else if (!installationId) {
        $('#installationWizard').modal('hide');
    }
}

function checkRequirements() {
    $('#requirements-result').html(`
        <div class="text-center py-4">
            <div class="spinner-border" role="status"></div>
            <p class="mt-2">กำลังตรวจสอบ...</p>
        </div>
    `);

    $.get('{{ route('admin.ai-installation.check-requirements') }}', function(response) {
        const data = response.data;
        displayRequirements(data);

        if (data.overall_status.ready) {
            loadModelRecommendations();
        }
    });
}

function displayRequirements(data) {
    let html = '<div class="row">';

    // CPU
    const cpuStatusColor = data.cpu.status === 'excellent' ? 'success' : (data.cpu.status === 'good' ? 'info' : 'warning');
    html += `
        <div class="col-lg-6 mb-3">
            <div class="stats-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon icon-purple me-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">CPU</h6>
                        <small class="text-muted">${data.cpu.model}</small>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-server me-1"></i> Cores</span>
                        <strong>${data.cpu.cores} cores</strong>
                    </div>
                </div>
                <span class="badge bg-${cpuStatusColor}">${data.cpu.message}</span>
            </div>
        </div>
    `;

    // RAM
    const ramStatusColor = data.ram.status === 'excellent' ? 'success' : (data.ram.status === 'good' ? 'info' : 'warning');
    const ramPercentUsed = ((data.ram.total_gb - data.ram.available_gb) / data.ram.total_gb * 100).toFixed(1);
    html += `
        <div class="col-lg-6 mb-3">
            <div class="stats-card" style="border-left-color: #11998e;">
                <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon icon-green me-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-memory"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">RAM</h6>
                        <small class="text-muted">${data.ram.available_gb} GB / ${data.ram.total_gb} GB พร้อมใช้งาน</small>
                    </div>
                </div>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-${ramStatusColor}" style="width: ${ramPercentUsed}%"></div>
                </div>
                <span class="badge bg-${ramStatusColor}">${data.ram.message}</span>
            </div>
        </div>
    `;

    // GPU
    if (data.gpu.available) {
        data.gpu.gpus.forEach((gpu, index) => {
            const gpuPercentUsed = ((gpu.memory_total_gb - gpu.memory_free_gb) / gpu.memory_total_gb * 100).toFixed(1);
            html += `
                <div class="col-lg-6 mb-3">
                    <div class="stats-card" style="border-left-color: #f5576c;">
                        <div class="d-flex align-items-center mb-3">
                            <div class="feature-icon icon-orange me-3" style="width: 50px; height: 50px;">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">GPU ${index + 1}</h6>
                                <small class="text-muted">${gpu.name}</small>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-memory me-1"></i> VRAM</span>
                                <strong>${gpu.memory_free_gb} GB / ${gpu.memory_total_gb} GB</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${gpuPercentUsed}%"></div>
                            </div>
                        </div>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>พร้อมใช้งาน</span>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="col-lg-6 mb-3">
                <div class="stats-card" style="border-left-color: #f5576c;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="feature-icon icon-orange me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">GPU</h6>
                            <small class="text-muted">ไม่พบการ์ดจอ</small>
                        </div>
                    </div>
                    <p class="text-muted mb-2">ไม่พบ GPU - จะใช้ CPU inference แทน</p>
                    <span class="badge bg-secondary">ใช้งาน CPU</span>
                </div>
            </div>
        `;
    }

    // Disk
    const diskStatusColor = data.disk.status === 'excellent' ? 'success' : (data.disk.status === 'good' ? 'info' : 'warning');
    html += `
        <div class="col-lg-6 mb-3">
            <div class="stats-card" style="border-left-color: #4facfe;">
                <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon icon-blue me-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Disk Space</h6>
                        <small class="text-muted">${data.disk.available_gb} GB พร้อมใช้งาน</small>
                    </div>
                </div>
                <span class="badge bg-${diskStatusColor}">${data.disk.message}</span>
            </div>
        </div>
    `;

    html += '</div>';

    // Overall Status
    if (data.overall_status.ready) {
        html += `
            <div class="alert alert-success mt-3" style="border-radius: 12px; border-left: 4px solid #28a745;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 24px;"></i>
                    <div>
                        <h6 class="mb-0">ระบบพร้อมใช้งาน!</h6>
                        <small>เซิร์ฟเวอร์ของคุณมีทรัพยากรเพียงพอสำหรับการติดตั้ง AI</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <button class="btn btn-gradient btn-lg" onclick="showStep('step-model'); updateWizardStep(2);">
                    <i class="fas fa-arrow-right me-2"></i>
                    ถัดไป: เลือกโมเดล AI
                </button>
            </div>
        `;
    } else {
        html += `
            <div class="alert alert-danger mt-3" style="border-radius: 12px; border-left: 4px solid #dc3545;">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 24px;"></i>
                    <div>
                        <h6 class="mb-2">พบปัญหากับระบบ</h6>
                        <p class="mb-2">${data.overall_status.message}</p>
                        <ul class="mb-0 ps-3">
                            ${data.overall_status.errors.map(e => `<li>${e}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>
        `;
    }

    $('#requirements-result').html(html);
}

function loadModelRecommendations() {
    $.get('{{ route('admin.ai-installation.recommendations') }}', function(response) {
        const data = response.data;
        displayModelRecommendations(data);
    });
}

function displayModelRecommendations(data) {
    let html = '<div class="row">';

    // Recommended Models
    if (data.recommended.length > 0) {
        html += '<h6 class="text-success">แนะนำสำหรับคุณ:</h6>';
        data.recommended.forEach(model => {
            html += createModelCard(model);
        });
    }

    // Possible Models
    if (data.possible.length > 0) {
        html += '<h6 class="text-warning mt-3">ติดตั้งได้แต่อาจช้า:</h6>';
        data.possible.forEach(model => {
            html += createModelCard(model);
        });
    }

    html += '</div>';
    $('#model-recommendations').html(html);
}

function createModelCard(model) {
    const isRecommended = model.category === 'recommended';
    const badgeClass = isRecommended ? 'badge-recommended' : 'badge-possible';
    const badgeText = isRecommended ? '⭐ แนะนำ' : '⚡ เป็นไปได้';

    return `
        <div class="col-lg-6 mb-3">
            <div class="model-card" onclick="selectModelCard(this, '${model.model_id}', '${model.best_quantization.name}')">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">${model.model_name}</h5>
                        <span class="${badgeClass}">${badgeText}</span>
                    </div>
                    <i class="fas fa-robot" style="font-size: 32px; color: #667eea; opacity: 0.3;"></i>
                </div>

                <p class="text-muted mb-3" style="font-size: 14px;">${model.description}</p>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-microchip me-2" style="color: #667eea;"></i>
                            <div>
                                <small class="text-muted d-block">Quantization</small>
                                <strong style="font-size: 13px;">${model.best_quantization.name}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-${model.best_quantization.use_gpu ? 'bolt' : 'server'} me-2" style="color: ${model.best_quantization.use_gpu ? '#f5576c' : '#4facfe'};"></i>
                            <div>
                                <small class="text-muted d-block">Device</small>
                                <strong style="font-size: 13px;">${model.best_quantization.use_gpu ? 'GPU' : 'CPU'}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tachometer-alt me-2" style="color: #11998e;"></i>
                            <div>
                                <small class="text-muted d-block">Speed</small>
                                <strong style="font-size: 13px;">~${model.estimated_speed.tokens_per_second} tokens/s</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-hdd me-2" style="color: #667eea;"></i>
                            <div>
                                <small class="text-muted d-block">Size</small>
                                <strong style="font-size: 13px;">${model.best_quantization.disk_space_gb || 'N/A'} GB</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-gradient w-100" onclick="event.stopPropagation(); installModel('${model.model_id}', '${model.best_quantization.name}')">
                    <i class="fas fa-download me-2"></i>
                    ติดตั้งโมเดลนี้
                </button>
            </div>
        </div>
    `;
}

function selectModelCard(element, modelId, quantization) {
    // Remove selected class from all cards
    $('.model-card').removeClass('selected');
    // Add selected class to clicked card
    $(element).addClass('selected');
}

function installModel(modelId, quantization) {
    showStep('step-installing');
    updateWizardStep(3);

    $.post('{{ route('admin.ai-installation.start') }}', {
        installation_type: 'deepseek',
        model_id: modelId,
        quantization: quantization,
        _token: '{{ csrf_token() }}'
    }, function(response) {
        installationId = response.installation_id;
        startProgressTracking();
    }).fail(function(xhr) {
        alert('เกิดข้อผิดพลาด: ' + xhr.responseJSON.message);
        showStep('step-model');
        updateWizardStep(2);
    });
}

function startProgressTracking() {
    progressInterval = setInterval(function() {
        $.get(`/admin/ai-installation/progress/${installationId}`, function(response) {
            const data = response.data;
            displayProgress(data);

            if (data.is_completed || data.is_failed) {
                clearInterval(progressInterval);

                if (data.is_completed) {
                    showStep('step-complete');
                    updateWizardStep(4);
                    loadInstalledModels();
                } else {
                    alert('การติดตั้งล้มเหลว: ' + data.error_message);
                    showStep('step-model');
                    updateWizardStep(2);
                }
            }
        });
    }, 2000);
}

function displayProgress(data) {
    const statusIcons = {
        'pending': 'fa-clock',
        'downloading': 'fa-download',
        'installing': 'fa-cog fa-spin',
        'configuring': 'fa-wrench',
        'testing': 'fa-check-circle',
        'completed': 'fa-check-circle',
        'failed': 'fa-times-circle'
    };

    const statusIcon = statusIcons[data.status] || 'fa-cog fa-spin';

    const html = `
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="mb-0">
                        <i class="fas ${statusIcon} me-2" style="color: #667eea;"></i>
                        ${data.current_step || 'กำลังดำเนินการ...'}
                    </h6>
                </div>
                <div>
                    <span class="badge bg-primary">${data.progress_percentage}%</span>
                </div>
            </div>
            <div class="progress-modern">
                <div class="progress-bar" role="progressbar" style="width: ${data.progress_percentage}%">
                    ${data.progress_percentage}%
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">
                    <i class="fas fa-terminal me-2"></i>
                    Installation Log
                </h6>
                <small class="text-muted">Real-time output</small>
            </div>
            <div class="installation-log">
                ${data.log_output || '<span class="pulse-animation">กำลังเริ่มต้นการติดตั้ง...</span>'}
            </div>
        </div>

        ${data.is_failed ? `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>เกิดข้อผิดพลาด:</strong> ${data.error_message || 'Unknown error'}
            </div>
        ` : ''}
    `;

    $('#installation-progress').html(html);

    // Auto scroll log to bottom
    const logElement = document.querySelector('.installation-log');
    if (logElement) {
        logElement.scrollTop = logElement.scrollHeight;
    }
}

function showStep(stepId) {
    $('.wizard-step').hide();
    $(`#${stepId}`).show();
}

function uninstallModel(modelName) {
    if (!confirm(`คุณต้องการลบโมเดล ${modelName} หรือไม่?`)) {
        return;
    }

    $.post('{{ route('admin.ai-installation.uninstall') }}', {
        model_id: modelName,
        _token: '{{ csrf_token() }}'
    }, function(response) {
        alert('ลบโมเดลเรียบร้อยแล้ว');
        loadInstalledModels();
    }).fail(function() {
        alert('เกิดข้อผิดพลาดในการลบโมเดล');
    });
}

function viewLog(logId) {
    window.location.href = `/admin/ai-installation/logs/${logId}`;
}
</script>
@endpush

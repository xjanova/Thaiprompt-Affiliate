@extends('layouts.admin')

@section('title', 'ติดตั้ง AI ของตัวเอง (Self-Hosted)')

@push('styles')
<style>
.ai-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 40px;
    color: white;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.ai-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(50%, -50%);
}

.ai-hero h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.ai-hero p {
    font-size: 1.1rem;
    opacity: 0.95;
    margin-bottom: 25px;
}

.feature-card {
    border: none;
    border-radius: 15px;
    transition: all 0.3s ease;
    height: 100%;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 15px;
}

.icon-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.icon-green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.icon-orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.icon-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }

.pricing-card {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    transition: all 0.3s ease;
    height: 100%;
}

.pricing-card.highlight {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);
    position: relative;
}

.pricing-card.highlight::before {
    content: 'แนะนำ';
    position: absolute;
    top: -12px;
    right: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.price-tag {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin: 20px 0;
}

.price-tag small {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 400;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    position: relative;
}

.wizard-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 50px;
    right: 50px;
    height: 2px;
    background: #e9ecef;
    z-index: 0;
}

.wizard-step-item {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.wizard-step-item.active .step-circle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 3px 10px rgba(102,126,234,0.4);
}

.wizard-step-item.completed .step-circle {
    background: #28a745;
    color: white;
}

.step-label {
    font-size: 13px;
    color: #6c757d;
}

.wizard-step-item.active .step-label {
    color: #667eea;
    font-weight: 600;
}

.model-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
}

.model-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102,126,234,0.2);
    transform: translateY(-3px);
}

.model-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
}

.badge-recommended {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-possible {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.progress-modern {
    height: 30px;
    border-radius: 15px;
    background: #e9ecef;
    overflow: hidden;
}

.progress-modern .progress-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.stats-value {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
}

.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    color: white;
}

.installation-log {
    background: #1e1e1e;
    color: #d4d4d4;
    border-radius: 8px;
    padding: 15px;
    max-height: 300px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.installation-log::-webkit-scrollbar {
    width: 8px;
}

.installation-log::-webkit-scrollbar-track {
    background: #2d2d2d;
    border-radius: 4px;
}

.installation-log::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 4px;
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
<div class="container-fluid py-4">
    <!-- Hero Section -->
    <div class="ai-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>ติดตั้ง AI ของตัวเอง (Self-Hosted)</h1>
                <p class="mb-4">
                    ติดตั้ง DeepSeek AI บนเซิร์ฟเวอร์ของคุณเอง ใช้งานฟรี ไม่จำกัด ไม่มีค่าใช้จ่ายรายเดือน
                    ควบคุมข้อมูลของคุณได้เอง 100% และประหยัดค่าใช้จ่ายได้มากกว่า 90%
                </p>
                <button type="button" class="btn btn-light btn-lg" onclick="startNewInstallation()" style="border-radius: 25px; padding: 12px 35px;">
                    <i class="fas fa-rocket me-2"></i>
                    เริ่มติดตั้งเลย
                </button>
            </div>
            <div class="col-lg-4 text-end">
                <i class="fas fa-server" style="font-size: 120px; opacity: 0.2;"></i>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">ทำไมต้อง Self-Hosted AI?</h4>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="feature-card">
                <div class="card-body">
                    <div class="feature-icon icon-purple">
                        <i class="fas fa-infinity"></i>
                    </div>
                    <h5>ใช้งานไม่จำกัด</h5>
                    <p class="text-muted mb-0">ไม่มีข้อจำกัดจำนวนคำถาม ไม่มีค่าใช้จ่ายรายเดือน ใช้งานได้เท่าที่ต้องการ</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="feature-card">
                <div class="card-body">
                    <div class="feature-icon icon-green">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5>ปลอดภัย 100%</h5>
                    <p class="text-muted mb-0">ข้อมูลอยู่บนเซิร์ฟเวอร์ของคุณ ไม่ส่งข้อมูลออกไปภายนอก รับรองความเป็นส่วนตัว</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="feature-card">
                <div class="card-body">
                    <div class="feature-icon icon-orange">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h5>ประหยัดงบ 90%+</h5>
                    <p class="text-muted mb-0">เทียบกับ ChatGPT API หรือ Claude ประหยัดได้หลักหมื่นบาทต่อเดือน</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="feature-card">
                <div class="card-body">
                    <div class="feature-icon icon-blue">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h5>รวดเร็ว ทันใจ</h5>
                    <p class="text-muted mb-0">ติดตั้งบนเซิร์ฟเวอร์ของคุณ ไม่ต้องรอคิว ตอบสนองเร็วกว่า Cloud API</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Comparison -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">เปรียบเทียบค่าใช้จ่าย</h4>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="pricing-card">
                <h5>ChatGPT API</h5>
                <div class="price-tag">$20-200<small>/เดือน</small></div>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>จำกัดจำนวนคำถาม</li>
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ค่าใช้จ่ายสูง</li>
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ข้อมูลถูกส่งออกไป</li>
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ต้องพึ่งพา API ภายนอก</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="pricing-card highlight">
                <h5>Self-Hosted AI</h5>
                <div class="price-tag">฿0<small>/เดือน</small></div>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>ใช้ไม่จำกัด ฟรี!</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>ไม่มีค่าใช้จ่ายรายเดือน</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>ข้อมูลอยู่กับคุณ 100%</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>ควบคุมได้เองทั้งหมด</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="pricing-card">
                <h5>Claude API</h5>
                <div class="price-tag">$15-150<small>/เดือน</small></div>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>คิดตามจำนวน tokens</li>
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ราคาแพง</li>
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>ข้อมูลส่งไป Anthropic</li>
                    <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>มีข้อจำกัด rate limit</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card" style="border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <div class="card-header pb-0" style="background: transparent; border: none;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5>จัดการ AI โมเดล</h5>
                            <p class="text-sm mb-0 text-muted">ติดตั้งและจัดการโมเดล AI ที่คุณต้องการ</p>
                        </div>
                        <button type="button" class="btn btn-gradient" onclick="startNewInstallation()">
                            <i class="fas fa-download me-1"></i>
                            ติดตั้งโมเดลใหม่
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Ollama Status -->
                    <div class="alert alert-info" id="ollama-status">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            <span>กำลังตรวจสอบสถานะ Ollama...</span>
                        </div>
                    </div>

                    <!-- Installed Models -->
                    <div class="mt-4">
                        <h6>โมเดลที่ติดตั้งแล้ว</h6>
                        <div id="installed-models" class="table-responsive">
                            <div class="text-center py-4">
                                <div class="spinner-border" role="status"></div>
                                <p class="mt-2">กำลังโหลด...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Installation Logs -->
                    @if($recentLogs->count() > 0)
                    <div class="mt-4">
                        <h6>ประวัติการติดตั้งล่าสุด</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                                            <span class="badge badge-{{ $log->isCompleted() ? 'success' : ($log->isFailed() ? 'danger' : 'warning') }}">
                                                {{ $log->getStatusMessage() }}
                                            </span>
                                        </td>
                                        <td>{{ $log->progress_percentage }}%</td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewLog({{ $log->id }})">
                                                <i class="fas fa-eye"></i>
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
</div>

<!-- Installation Wizard Modal -->
<div class="modal fade" id="installationWizard" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header" style="border: none; padding: 30px 30px 20px;">
                <div class="w-100">
                    <h4 class="modal-title mb-0">ติดตั้ง AI โมเดล</h4>
                    <p class="text-muted mb-3 mt-1">ติดตั้งและกำหนดค่า AI โมเดลของคุณภายใน 3 ขั้นตอน</p>

                    <!-- Step Indicator -->
                    <div class="wizard-steps">
                        <div class="wizard-step-item" id="wizard-step-1">
                            <div class="step-circle">1</div>
                            <div class="step-label">ตรวจสอบระบบ</div>
                        </div>
                        <div class="wizard-step-item" id="wizard-step-2">
                            <div class="step-circle">2</div>
                            <div class="step-label">เลือกโมเดล</div>
                        </div>
                        <div class="wizard-step-item" id="wizard-step-3">
                            <div class="step-circle">3</div>
                            <div class="step-label">กำลังติดตั้ง</div>
                        </div>
                        <div class="wizard-step-item" id="wizard-step-4">
                            <div class="step-circle">4</div>
                            <div class="step-label">เสร็จสิ้น</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelWizard()"></button>
            </div>
            <div class="modal-body" style="padding: 20px 30px 30px;">
                <!-- Step 1: Check Requirements -->
                <div id="step-requirements" class="wizard-step">
                    <div id="requirements-result">
                        <div class="text-center py-5">
                            <i class="fas fa-server" style="font-size: 80px; color: #667eea; opacity: 0.3;"></i>
                            <h5 class="mt-4 mb-3">ตรวจสอบความพร้อมของระบบ</h5>
                            <p class="text-muted mb-4">เราจะตรวจสอบ CPU, RAM, GPU, และพื้นที่ว่างเพื่อแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ</p>
                            <button class="btn btn-gradient btn-lg" onclick="checkRequirements()">
                                <i class="fas fa-check-circle me-2"></i>
                                เริ่มตรวจสอบระบบ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Model -->
                <div id="step-model" class="wizard-step" style="display:none;">
                    <div class="mb-4">
                        <h5>เลือกโมเดล AI ที่ต้องการติดตั้ง</h5>
                        <p class="text-muted">เราแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ คุณสามารถเลือกโมเดลอื่นได้ตามต้องการ</p>
                    </div>
                    <div id="model-recommendations"></div>
                </div>

                <!-- Step 3: Installing -->
                <div id="step-installing" class="wizard-step" style="display:none;">
                    <div class="text-center mb-4">
                        <i class="fas fa-cog fa-spin" style="font-size: 60px; color: #667eea;"></i>
                        <h5 class="mt-3 mb-2">กำลังติดตั้งโมเดล AI</h5>
                        <p class="text-muted">กรุณารอสักครู่ การติดตั้งอาจใช้เวลาหลายนาทีขึ้นอยู่กับขนาดโมเดล</p>
                    </div>
                    <div id="installation-progress"></div>
                </div>

                <!-- Step 4: Complete -->
                <div id="step-complete" class="wizard-step" style="display:none;">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle" style="font-size: 80px; color: #28a745;"></i>
                        </div>
                        <h3 class="mb-3">ติดตั้งสำเร็จ!</h3>
                        <p class="text-muted mb-4">โมเดล AI ของคุณพร้อมใช้งานแล้ว คุณสามารถเริ่มใช้งาน AI ในระบบได้ทันที</p>
                        <div class="d-flex justify-content-center gap-3">
                            <button class="btn btn-gradient" onclick="closeWizard()">
                                <i class="fas fa-check me-2"></i>
                                เสร็จสิ้น
                            </button>
                            <button class="btn btn-outline-primary" onclick="startNewInstallation()">
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
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Ollama กำลังทำงาน (${status.version})
                </div>
            `;
        } else if (status.installed && !status.running) {
            html = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ollama ติดตั้งแล้วแต่ไม่ได้ทำงาน กรุณาเริ่ม Ollama service
                </div>
            `;
        } else {
            html = `
                <div class="alert alert-info">
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
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    ยังไม่มีโมเดลที่ติดตั้ง
                </div>
            `);
            return;
        }

        let html = `
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>โมเดล</th>
                        <th>ID</th>
                        <th>ขนาด</th>
                        <th>แก้ไขล่าสุด</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        models.forEach(model => {
            html += `
                <tr>
                    <td>${model.name}</td>
                    <td><small class="text-muted">${model.id}</small></td>
                    <td>${model.size}</td>
                    <td>${model.modified}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="uninstallModel('${model.name}')">
                            <i class="fas fa-trash"></i> ลบ
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
            <i class="fas fa-server" style="font-size: 80px; color: #667eea; opacity: 0.3;"></i>
            <h5 class="mt-4 mb-3">ตรวจสอบความพร้อมของระบบ</h5>
            <p class="text-muted mb-4">เราจะตรวจสอบ CPU, RAM, GPU, และพื้นที่ว่างเพื่อแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ</p>
            <button class="btn btn-gradient btn-lg" onclick="checkRequirements()">
                <i class="fas fa-check-circle me-2"></i>
                เริ่มตรวจสอบระบบ
            </button>
        </div>
    `);

    $('#installationWizard').modal('show');
}

function updateWizardStep(step) {
    // Reset all steps
    $('.wizard-step-item').removeClass('active completed');

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

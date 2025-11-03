@extends('layouts.admin')

@section('title', 'ติดตั้ง AI ของตัวเอง (Self-Hosted)')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5>ติดตั้ง AI ของตัวเอง (Self-Hosted)</h5>
                            <p class="text-sm mb-0">ติดตั้ง DeepSeek AI บนเซิร์ฟเวอร์ของคุณเอง ใช้งานฟรีไม่มีค่าใช้จ่าย</p>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="startNewInstallation()">
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ติดตั้ง AI โมเดล</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Check Requirements -->
                <div id="step-requirements" class="wizard-step">
                    <h6>ขั้นตอนที่ 1: ตรวจสอบทรัพยากรระบบ</h6>
                    <div id="requirements-result"></div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" onclick="checkRequirements()">
                            <i class="fas fa-check-circle me-1"></i>
                            ตรวจสอบระบบ
                        </button>
                    </div>
                </div>

                <!-- Step 2: Select Model -->
                <div id="step-model" class="wizard-step" style="display:none;">
                    <h6>ขั้นตอนที่ 2: เลือกโมเดล</h6>
                    <div id="model-recommendations"></div>
                </div>

                <!-- Step 3: Installing -->
                <div id="step-installing" class="wizard-step" style="display:none;">
                    <h6>ขั้นตอนที่ 3: กำลังติดตั้ง</h6>
                    <div id="installation-progress"></div>
                </div>

                <!-- Step 4: Complete -->
                <div id="step-complete" class="wizard-step" style="display:none;">
                    <h6>เสร็จสิ้น!</h6>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        ติดตั้งโมเดลเรียบร้อยแล้ว!
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
    $('#installationWizard').modal('show');
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
    html += `
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6>CPU</h6>
                    <p class="mb-1"><strong>Cores:</strong> ${data.cpu.cores}</p>
                    <p class="mb-1"><strong>Model:</strong> ${data.cpu.model}</p>
                    <span class="badge badge-${data.cpu.status === 'excellent' ? 'success' : 'warning'}">${data.cpu.message}</span>
                </div>
            </div>
        </div>
    `;

    // RAM
    html += `
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6>RAM</h6>
                    <p class="mb-1"><strong>Total:</strong> ${data.ram.total_gb} GB</p>
                    <p class="mb-1"><strong>Available:</strong> ${data.ram.available_gb} GB</p>
                    <span class="badge badge-${data.ram.status}">${data.ram.message}</span>
                </div>
            </div>
        </div>
    `;

    // GPU
    if (data.gpu.available) {
        data.gpu.gpus.forEach((gpu, index) => {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6>GPU ${index + 1}</h6>
                            <p class="mb-1"><strong>Name:</strong> ${gpu.name}</p>
                            <p class="mb-1"><strong>VRAM:</strong> ${gpu.memory_total_gb} GB</p>
                            <p class="mb-1"><strong>Free:</strong> ${gpu.memory_free_gb} GB</p>
                            <span class="badge badge-success">พร้อมใช้งาน</span>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6>GPU</h6>
                        <p class="text-muted">ไม่พบ GPU - จะใช้ CPU inference</p>
                    </div>
                </div>
            </div>
        `;
    }

    // Disk
    html += `
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6>Disk Space</h6>
                    <p class="mb-1"><strong>Available:</strong> ${data.disk.available_gb} GB</p>
                    <span class="badge badge-${data.disk.status}">${data.disk.message}</span>
                </div>
            </div>
        </div>
    `;

    html += '</div>';

    // Overall Status
    if (data.overall_status.ready) {
        html += `
            <div class="alert alert-success mt-3">
                <i class="fas fa-check-circle me-2"></i>
                ระบบพร้อมสำหรับการติดตั้ง AI
            </div>
            <div class="text-center">
                <button class="btn btn-primary" onclick="showStep('step-model')">
                    ถัดไป: เลือกโมเดล
                </button>
            </div>
        `;
    } else {
        html += `
            <div class="alert alert-danger mt-3">
                <i class="fas fa-times-circle me-2"></i>
                ${data.overall_status.message}
                <ul class="mt-2 mb-0">
                    ${data.overall_status.errors.map(e => `<li>${e}</li>`).join('')}
                </ul>
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
    return `
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6>${model.model_name}</h6>
                    <p class="text-sm mb-2">${model.description}</p>
                    <p class="mb-1">
                        <strong>Quantization:</strong> ${model.best_quantization.name}<br>
                        <strong>Device:</strong> ${model.best_quantization.use_gpu ? 'GPU' : 'CPU'}<br>
                        <strong>Speed:</strong> ~${model.estimated_speed.tokens_per_second} tokens/s
                    </p>
                    <button class="btn btn-sm btn-primary" onclick="installModel('${model.model_id}', '${model.best_quantization.name}')">
                        <i class="fas fa-download me-1"></i>
                        ติดตั้ง
                    </button>
                </div>
            </div>
        </div>
    `;
}

function installModel(modelId, quantization) {
    showStep('step-installing');

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
                    loadInstalledModels();
                } else {
                    alert('การติดตั้งล้มเหลว: ' + data.error_message);
                }
            }
        });
    }, 2000);
}

function displayProgress(data) {
    const html = `
        <div class="mb-3">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: ${data.progress_percentage}%">
                    ${data.progress_percentage}%
                </div>
            </div>
        </div>
        <p><strong>สถานะ:</strong> ${data.current_step}</p>
        <div class="card">
            <div class="card-body">
                <h6>Log:</h6>
                <pre style="max-height: 200px; overflow-y: auto; font-size: 11px;">${data.log_output || 'กำลังเริ่มต้น...'}</pre>
            </div>
        </div>
    `;

    $('#installation-progress').html(html);
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

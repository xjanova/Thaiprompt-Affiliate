@extends('layouts.admin')

@section('title', 'ติดตั้ง AI ของตัวเอง (Self-Hosted)')

@push('styles')
<style>
/* Premium Modern Color Palette */
:root {
    --primary: #6366F1;
    --primary-dark: #4F46E5;
    --primary-light: #818CF8;
    --accent: #F59E0B;
    --accent-light: #FBBF24;
    --success: #10B981;
    --success-light: #34D399;
    --danger: #EF4444;
    --warning: #F59E0B;
    --info: #3B82F6;

    --bg-dark: #0F172A;
    --bg-light: #F8FAFC;
    --text-dark: #1E293B;
    --text-light: #64748B;
    --border: #E2E8F0;

    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
}

/* Animated Gradient Background */
.premium-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.premium-container::before {
    content: '';
    position: absolute;
    width: 200%;
    height: 200%;
    top: -50%;
    left: -50%;
    background: linear-gradient(
        45deg,
        transparent 0%,
        rgba(255,255,255,0.05) 25%,
        transparent 50%,
        rgba(255,255,255,0.05) 75%,
        transparent 100%
    );
    animation: shimmer 15s linear infinite;
    pointer-events: none;
}

@keyframes shimmer {
    0% { transform: translateX(-50%) translateY(-50%) rotate(0deg); }
    100% { transform: translateX(-50%) translateY(-50%) rotate(360deg); }
}

/* Glass Morphism Card */
.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow:
        0 8px 32px 0 rgba(31, 38, 135, 0.15),
        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Premium Hero */
.premium-hero {
    position: relative;
    padding: 60px 0 40px;
    text-align: center;
    color: white;
    z-index: 1;
}

.premium-hero h1 {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.premium-hero p {
    font-size: 1.1rem;
    opacity: 0.95;
    max-width: 700px;
    margin: 0 auto 30px;
    line-height: 1.6;
}

/* Premium Button */
.btn-premium {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
    border: none;
    color: white;
    padding: 14px 36px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 15px;
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.btn-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn-premium:hover::before {
    left: 100%;
}

.btn-premium:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 15px 40px rgba(245, 158, 11, 0.5);
    color: white;
}

.btn-premium-outline {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 12px 32px;
    border-radius: 50px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-premium-outline:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    color: white;
}

/* Model Card - Enhanced Design */
.model-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 2px solid transparent;
    position: relative;
}

.model-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 20px;
    padding: 2px;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.4s;
}

.model-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(99, 102, 241, 0.25);
}

.model-card:hover::before {
    opacity: 1;
}

.model-card.recommended {
    border-color: var(--success);
    box-shadow: 0 8px 32px rgba(16, 185, 129, 0.2);
}

.model-card.recommended .model-header {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.05) 100%);
}

.model-header {
    padding: 24px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(129, 140, 248, 0.04) 100%);
    border-bottom: 1px solid var(--border);
    position: relative;
}

.model-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.model-name {
    font-size: 20px;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 4px;
}

.model-size {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-light);
}

.model-body {
    padding: 20px 24px;
    flex: 1;
}

.model-description {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 16px;
}

.model-capabilities {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.capability-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(99, 102, 241, 0.1);
    color: var(--primary);
}

.model-specs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.spec-item {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: var(--text-light);
}

.spec-item i {
    margin-right: 6px;
    color: var(--primary);
}

.model-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--border);
    background: rgba(99, 102, 241, 0.02);
}

.install-btn {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}

.install-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 24px rgba(99, 102, 241, 0.4);
}

.install-btn:disabled {
    background: linear-gradient(135deg, var(--text-light) 0%, var(--text-light) 100%);
    cursor: not-allowed;
    opacity: 0.5;
}

.install-btn.installed {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
}

.badge-recommended {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    z-index: 1;
}

.badge-warning {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, var(--warning) 0%, var(--accent-light) 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    z-index: 1;
}

.badge-not-recommended {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    z-index: 1;
}

/* System Requirements Section */
.system-check {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    border: 2px solid var(--border);
}

.system-check.checking {
    border-color: var(--info);
}

.system-check.ready {
    border-color: var(--success);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.02) 100%);
}

.system-check.warning {
    border-color: var(--warning);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(251, 191, 36, 0.02) 100%);
}

.system-stat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: var(--bg-light);
    border-radius: 12px;
    margin-bottom: 12px;
}

.system-stat:last-child {
    margin-bottom: 0;
}

.system-stat-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: var(--text-dark);
}

.system-stat-label i {
    margin-right: 10px;
    font-size: 18px;
    color: var(--primary);
}

.system-stat-value {
    font-weight: 700;
    color: var(--primary);
}

/* Loading Spinner */
.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
    border-width: 0.2em;
}

/* Content Wrapper */
.content-wrapper {
    position: relative;
    z-index: 1;
    padding: 0 0 60px;
}

/* Progress Bar */
.progress-bar-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    z-index: 9999;
    display: none;
}

.progress-bar-container.active {
    display: block;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transition: width 0.3s ease;
    width: 0%;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: none;
}

.modal-backdrop.show {
    display: block;
}

.modal {
    position: fixed;
    inset: 0;
    z-index: 1055;
    display: none;
    overflow-y: auto;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    max-width: 600px;
    margin: 20px;
    width: 100%;
}

.modal-content {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--text-dark);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--text-light);
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: var(--bg-light);
    color: var(--text-dark);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Alert */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: start;
}

.alert i {
    margin-right: 12px;
    font-size: 20px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

/* Floating Animation */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.float-animation {
    animation: float 3s ease-in-out infinite;
}

/* Responsive */
@media (max-width: 768px) {
    .premium-hero h1 {
        font-size: 2rem;
    }

    .premium-hero p {
        font-size: 0.95rem;
    }

    .model-card {
        margin-bottom: 20px;
    }
}
</style>
@endpush

@section('content')
<div class="premium-container">
    <!-- Progress Bar -->
    <div class="progress-bar-container" id="installProgressBar">
        <div class="progress-bar-fill"></div>
    </div>

    <!-- Premium Hero -->
    <div class="premium-hero">
        <div class="container">
            <div class="float-animation" style="display: inline-block;">
                <i class="fas fa-brain" style="font-size: 70px; margin-bottom: 20px; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));"></i>
            </div>
            <h1>ติดตั้ง AI ของตัวเอง</h1>
            <p>
                ติดตั้ง AI Model บนเซิร์ฟเวอร์ของคุณเอง ใช้งานฟรี ไม่จำกัด ไม่มีค่าใช้จ่ายรายเดือน<br>
                คลิกเลือกโมเดลเพื่อเริ่มติดตั้งทันที - ระบบจะแนะนำโมเดลที่เหมาะกับคุณโดยอัตโนมัติ
            </p>
        </div>
    </div>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="container">
            <!-- System Check -->
            <div class="glass-card p-4 mb-4">
                <div id="systemCheckContainer">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0;">
                            <i class="fas fa-server me-2" style="color: var(--primary);"></i>
                            ตรวจสอบทรัพยากรระบบ
                        </h3>
                        <button type="button" class="btn-premium-outline" style="padding: 8px 20px; font-size: 13px;" onclick="checkSystem()">
                            <i class="fas fa-sync-alt me-1"></i> รีเฟรช
                        </button>
                    </div>

                    <div id="systemCheckContent">
                        <div class="text-center py-4">
                            <div class="spinner-border" style="color: var(--primary);" role="status"></div>
                            <p class="mt-2 mb-0" style="color: var(--text-light); font-size: 14px;">กำลังตรวจสอบระบบ...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Models Section -->
            <div class="glass-card p-5">
                <div class="text-center mb-4">
                    <h2 style="font-size: 28px; font-weight: 800; color: var(--text-dark);">เลือกโมเดล AI</h2>
                    <p style="color: var(--text-light); font-size: 15px; margin: 8px 0 0;">คลิกที่โมเดลเพื่อดูรายละเอียดและเริ่มติดตั้ง</p>
                </div>

                <div id="modelsContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border" style="color: var(--primary);" role="status"></div>
                        <p class="mt-3 mb-0" style="color: var(--text-light);">กำลังโหลดโมเดล...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Installation Modal -->
<div class="modal" id="installModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="installModalTitle">ติดตั้งโมเดล</h5>
                <button type="button" class="modal-close" onclick="closeInstallModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="installModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Backdrop -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeInstallModal()"></div>
@endsection

@push('scripts')
<script>
let systemInfo = null;
let recommendations = null;
let installedModels = [];

// Load everything on page load
document.addEventListener('DOMContentLoaded', function() {
    checkSystem();
    loadRecommendations();
    checkOllamaStatus();
});

// Check system requirements
async function checkSystem() {
    try {
        const response = await fetch('{{ route("admin.ai-installation.check-requirements") }}');
        const data = await response.json();

        if (data.success) {
            systemInfo = data.data;
            displaySystemInfo(systemInfo);
        }
    } catch (error) {
        console.error('Error checking system:', error);
        showSystemError();
    }
}

// Display system information
function displaySystemInfo(info) {
    const container = document.getElementById('systemCheckContent');
    const cpu = info.cpu;
    const ram = info.ram;
    const gpu = info.gpu;
    const disk = info.disk;

    const hasGpu = gpu.available && gpu.gpus && gpu.gpus.length > 0;
    const totalVram = hasGpu ? gpu.gpus.reduce((sum, g) => sum + g.memory_total_gb, 0) : 0;

    let statusClass = 'ready';
    let statusIcon = 'fa-check-circle';
    let statusColor = 'var(--success)';
    let statusText = 'พร้อมใช้งาน';

    if (!hasGpu) {
        statusClass = 'warning';
        statusIcon = 'fa-exclamation-circle';
        statusColor = 'var(--warning)';
        statusText = 'ใช้งาน CPU เท่านั้น (ช้ากว่า GPU)';
    } else if (totalVram < 8) {
        statusClass = 'warning';
        statusIcon = 'fa-exclamation-circle';
        statusColor = 'var(--warning)';
        statusText = 'VRAM น้อย - แนะนำโมเดลขนาดเล็ก';
    }

    container.innerHTML = `
        <div class="alert alert-${statusClass === 'ready' ? 'success' : 'warning'}" style="margin-bottom: 20px;">
            <i class="fas ${statusIcon}"></i>
            <div>
                <strong>สถานะระบบ: ${statusText}</strong>
                <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.9;">
                    ${hasGpu ? 'ระบบตรวจพบ GPU - คุณสามารถใช้โมเดลขนาดใหญ่ได้' : 'ไม่พบ GPU - แนะนำให้ใช้โมเดลขนาดเล็ก (1B-7B) หรือ Quantization ต่ำ'}
                </p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="system-stat">
                    <span class="system-stat-label">
                        <i class="fas fa-microchip"></i>
                        CPU
                    </span>
                    <span class="system-stat-value">${cpu.cores} Cores</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="system-stat">
                    <span class="system-stat-label">
                        <i class="fas fa-memory"></i>
                        RAM
                    </span>
                    <span class="system-stat-value">${ram.available_gb.toFixed(1)} GB</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="system-stat">
                    <span class="system-stat-label">
                        <i class="fas fa-server"></i>
                        GPU
                    </span>
                    <span class="system-stat-value">
                        ${hasGpu ? gpu.gpus[0].name : 'ไม่พบ'}
                    </span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="system-stat">
                    <span class="system-stat-label">
                        <i class="fas fa-hdd"></i>
                        VRAM
                    </span>
                    <span class="system-stat-value">
                        ${hasGpu ? totalVram.toFixed(1) + ' GB' : 'N/A'}
                    </span>
                </div>
            </div>
        </div>

        ${disk.available_gb < 50 ? `
        <div class="alert alert-warning" style="margin-top: 16px; margin-bottom: 0;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>คำเตือน:</strong> พื้นที่ดิสก์เหลือน้อย (${disk.available_gb.toFixed(0)} GB)<br>
                <small>แนะนำให้มีพื้นที่ว่างอย่างน้อย 50 GB สำหรับติดตั้งโมเดล</small>
            </div>
        </div>
        ` : ''}
    `;
}

// Show system error
function showSystemError() {
    const container = document.getElementById('systemCheckContent');
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i>
            <div>
                <strong>เกิดข้อผิดพลาด</strong><br>
                <small>ไม่สามารถตรวจสอบทรัพยากรระบบได้ กรุณาลองใหม่อีกครั้ง</small>
            </div>
        </div>
    `;
}

// Load recommendations
async function loadRecommendations() {
    try {
        const response = await fetch('{{ route("admin.ai-installation.recommendations") }}');
        const data = await response.json();

        if (data.success) {
            recommendations = data.data;
            displayModels(recommendations);
        }
    } catch (error) {
        console.error('Error loading recommendations:', error);
        showModelsError();
    }
}

// Display models
function displayModels(recommendations) {
    const container = document.getElementById('modelsContainer');

    const allModels = [
        ...recommendations.recommended,
        ...recommendations.possible,
        ...recommendations.not_recommended
    ];

    if (allModels.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>ไม่พบโมเดลที่รองรับ</div>
            </div>
        `;
        return;
    }

    const html = `
        ${recommendations.recommended.length > 0 ? `
            <div class="mb-4">
                <h4 style="font-size: 18px; font-weight: 700; color: var(--success); margin-bottom: 16px;">
                    <i class="fas fa-star me-2"></i>แนะนำสำหรับคุณ
                </h4>
                <div class="row g-4">
                    ${recommendations.recommended.map(model => createModelCard(model, 'recommended')).join('')}
                </div>
            </div>
        ` : ''}

        ${recommendations.possible.length > 0 ? `
            <div class="mb-4">
                <h4 style="font-size: 18px; font-weight: 700; color: var(--warning); margin-bottom: 16px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>ใช้งานได้ แต่อาจช้า
                </h4>
                <div class="row g-4">
                    ${recommendations.possible.map(model => createModelCard(model, 'possible')).join('')}
                </div>
            </div>
        ` : ''}

        ${recommendations.not_recommended.length > 0 ? `
            <div>
                <h4 style="font-size: 18px; font-weight: 700; color: var(--danger); margin-bottom: 16px;">
                    <i class="fas fa-times-circle me-2"></i>ไม่แนะนำ (ทรัพยากรไม่เพียงพอ)
                </h4>
                <div class="row g-4">
                    ${recommendations.not_recommended.map(model => createModelCard(model, 'not_recommended')).join('')}
                </div>
            </div>
        ` : ''}
    `;

    container.innerHTML = html;
}

// Create model card
function createModelCard(model, status) {
    const icons = {
        'deepseek-coder-33b-instruct': '💻',
        'deepseek-coder-6.7b-instruct': '⚡',
        'deepseek-coder-1.3b-instruct': '🚀',
        'deepseek-llm-7b-chat': '💬',
        'deepseek-llm-67b-chat': '🧠'
    };

    const icon = icons[model.model_id] || '🤖';

    const badgeHtml = status === 'recommended' ?
        '<span class="badge-recommended">⭐ แนะนำ</span>' :
        status === 'possible' ?
        '<span class="badge-warning">⚠️ อาจช้า</span>' :
        '<span class="badge-not-recommended">❌ ไม่แนะนำ</span>';

    const capabilityLabels = {
        'coding': '💻 Coding',
        'reasoning': '🧠 Reasoning',
        'multilingual': '🌏 หลายภาษา',
        'chat': '💬 สนทนา',
        'basic_reasoning': '🤔 คิดวิเคราะห์',
        'complex_tasks': '⚙️ งานซับซ้อน'
    };

    const capabilities = model.capabilities.map(cap =>
        `<span class="capability-badge">${capabilityLabels[cap] || cap}</span>`
    ).join('');

    const speedRating = model.estimated_speed ? model.estimated_speed.rating : 'ไม่ทราบ';
    const tokensPerSec = model.estimated_speed ? model.estimated_speed.tokens_per_second : 0;

    const vramReq = model.best_quantization ? model.best_quantization.vram_required : 0;
    const ramReq = model.best_quantization ? model.best_quantization.ram_required : 0;
    const useGpu = model.best_quantization ? model.best_quantization.use_gpu : false;

    const canInstall = status !== 'not_recommended';

    return `
        <div class="col-lg-4 col-md-6">
            <div class="model-card ${status === 'recommended' ? 'recommended' : ''}" onclick="${canInstall ? `openInstallModal('${model.model_id}')` : ''}">
                ${badgeHtml}

                <div class="model-header">
                    <div class="model-icon" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);">
                        ${icon}
                    </div>
                    <div class="model-name">${model.model_name}</div>
                    <div class="model-size">${model.model_size} Parameters</div>
                </div>

                <div class="model-body">
                    <div class="model-description">${model.description}</div>

                    <div class="model-capabilities">
                        ${capabilities}
                    </div>

                    <div class="model-specs">
                        <div class="spec-item">
                            <i class="fas fa-bolt"></i>
                            <span><strong>${speedRating}</strong> (${tokensPerSec} t/s)</span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-server"></i>
                            <span>${useGpu ? '🎮 GPU' : '🖥️ CPU'}</span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-memory"></i>
                            <span>${useGpu ? vramReq + ' GB VRAM' : ramReq + ' GB RAM'}</span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-window-maximize"></i>
                            <span>${(model.context_window || 0).toLocaleString()} tokens</span>
                        </div>
                    </div>

                    ${model.reasons && model.reasons.length > 0 ? `
                        <div style="margin-top: 12px; padding: 10px; background: rgba(99, 102, 241, 0.05); border-radius: 8px; font-size: 12px; color: var(--text-light);">
                            <ul style="margin: 0; padding-left: 20px;">
                                ${model.reasons.map(reason => `<li>${reason}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>

                <div class="model-footer">
                    <button class="install-btn" ${!canInstall ? 'disabled' : ''}>
                        <i class="fas ${canInstall ? 'fa-download' : 'fa-ban'} me-2"></i>
                        ${canInstall ? 'คลิกเพื่อติดตั้ง' : 'ทรัพยากรไม่เพียงพอ'}
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Show models error
function showModelsError() {
    const container = document.getElementById('modelsContainer');
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i>
            <div>
                <strong>เกิดข้อผิดพลาด</strong><br>
                <small>ไม่สามารถโหลดรายการโมเดลได้ กรุณาลองใหม่อีกครั้ง</small>
            </div>
        </div>
    `;
}

// Open install modal
async function openInstallModal(modelId) {
    const model = [...recommendations.recommended, ...recommendations.possible, ...recommendations.not_recommended]
        .find(m => m.model_id === modelId);

    if (!model) return;

    const modal = document.getElementById('installModal');
    const backdrop = document.getElementById('modalBackdrop');
    const title = document.getElementById('installModalTitle');
    const body = document.getElementById('installModalBody');

    title.textContent = `ติดตั้ง ${model.model_name}`;

    const quantizations = model.all_quantizations.filter(q => q.can_run);

    body.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>เลือก Quantization</strong><br>
                <small>Quantization ที่สูงกว่าจะให้คุณภาพดีกว่า แต่ใช้ทรัพยากรมากกว่า</small>
            </div>
        </div>

        <div style="max-height: 400px; overflow-y: auto;">
            ${quantizations.map(quant => `
                <div class="model-card" style="margin-bottom: 16px; cursor: pointer;" onclick="startInstallation('${modelId}', '${quant.name}')">
                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <h5 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">${quant.name}</h5>
                                <p style="font-size: 13px; color: var(--text-light); margin: 0;">
                                    ${quant.use_gpu ? '🎮 GPU Mode' : '🖥️ CPU Mode'}
                                </p>
                            </div>
                            <span class="capability-badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                                คุณภาพ ${quant.quality}%
                            </span>
                        </div>

                        <div class="row g-2" style="font-size: 12px;">
                            <div class="col-6">
                                <div class="spec-item">
                                    <i class="fas fa-memory"></i>
                                    ${quant.use_gpu ? `${quant.vram_required} GB VRAM` : `${quant.ram_required} GB RAM`}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="spec-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    ${quant.speed_estimate.rating}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="spec-item">
                                    <i class="fas fa-bolt"></i>
                                    ${quant.speed_estimate.tokens_per_second} t/s
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="spec-item">
                                    <i class="fas fa-clock"></i>
                                    ${quant.speed_estimate.time_for_500_tokens} (500 tokens)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>

        ${quantizations.length === 0 ? `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>ไม่มี Quantization ที่รองรับสำหรับระบบของคุณ</div>
            </div>
        ` : ''}
    `;

    modal.classList.add('show');
    backdrop.classList.add('show');
}

// Close install modal
function closeInstallModal() {
    const modal = document.getElementById('installModal');
    const backdrop = document.getElementById('modalBackdrop');
    modal.classList.remove('show');
    backdrop.classList.remove('show');
}

// Start installation
async function startInstallation(modelId, quantization) {
    closeInstallModal();

    // Show progress bar
    const progressBar = document.getElementById('installProgressBar');
    progressBar.classList.add('active');

    try {
        const response = await fetch('{{ route("admin.ai-installation.start") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                installation_type: 'deepseek',
                model_id: modelId,
                quantization: quantization
            })
        });

        const data = await response.json();

        if (data.success) {
            // Monitor installation progress
            monitorInstallation(data.log_id);
        } else {
            throw new Error(data.message || 'Installation failed');
        }
    } catch (error) {
        console.error('Installation error:', error);
        alert('เกิดข้อผิดพลาดในการติดตั้ง: ' + error.message);
        progressBar.classList.remove('active');
    }
}

// Monitor installation progress
function monitorInstallation(logId) {
    const progressBar = document.getElementById('installProgressBar');
    const progressFill = progressBar.querySelector('.progress-bar-fill');

    const interval = setInterval(async () => {
        try {
            const response = await fetch(`{{ route('admin.ai-installation.progress', ['installationId' => '__ID__']) }}`.replace('__ID__', logId));
            const data = await response.json();

            if (data.success) {
                const progress = data.data.progress_percentage || 0;
                progressFill.style.width = progress + '%';

                const status = data.data.status;

                if (status === 'completed') {
                    clearInterval(interval);
                    progressBar.classList.remove('active');
                    alert('ติดตั้งสำเร็จ! 🎉');
                    loadRecommendations(); // Reload to update status
                } else if (status === 'failed' || status === 'cancelled') {
                    clearInterval(interval);
                    progressBar.classList.remove('active');
                    alert('การติดตั้งล้มเหลว: ' + (data.data.error_message || 'Unknown error'));
                }
            }
        } catch (error) {
            console.error('Progress check error:', error);
        }
    }, 2000);
}

// Check Ollama status
async function checkOllamaStatus() {
    try {
        const response = await fetch('{{ route("admin.ai-installation.ollama-status") }}');
        const data = await response.json();

        if (data.success && data.data.installed) {
            console.log('Ollama is installed and running');
        }
    } catch (error) {
        console.error('Ollama status check error:', error);
    }
}
</script>
@endpush

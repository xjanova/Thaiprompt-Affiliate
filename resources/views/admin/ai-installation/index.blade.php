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

.model-card.not-recommended {
    opacity: 0.7;
    cursor: not-allowed;
}

.model-header {
    padding: 24px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(129, 140, 248, 0.04) 100%);
    border-bottom: 1px solid var(--border);
    position: relative;
}

.model-logo {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    overflow: hidden;
    background: white;
}

.model-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
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

.spec-item strong {
    color: var(--text-dark);
}

.requirements-box {
    background: rgba(99, 102, 241, 0.05);
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 16px;
}

.requirements-box h6 {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.requirements-box .spec-item {
    margin-bottom: 4px;
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

.install-btn.force-install {
    background: linear-gradient(135deg, var(--warning) 0%, var(--accent-light) 100%);
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

/* Models Grid - Center Alignment */
.models-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    justify-content: center;
    align-items: stretch;
}

.models-grid .model-card-wrapper {
    width: calc(33.333% - 16px);
    min-width: 320px;
    max-width: 400px;
}

/* Installed Model Card */
.installed-model-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 2px solid var(--success);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

.installed-model-card:hover {
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
    transform: translateY(-2px);
}

.installed-model-info {
    flex: 1;
}

.installed-model-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 4px;
}

.installed-model-details {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 13px;
    color: var(--text-light);
}

.installed-model-details span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.uninstall-btn {
    background: white;
    color: var(--danger);
    border: 2px solid var(--danger);
    padding: 10px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.uninstall-btn:hover {
    background: var(--danger);
    color: white;
    transform: scale(1.05);
}

.uninstall-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 1200px) {
    .models-grid .model-card-wrapper {
        width: calc(50% - 12px);
    }
}

@media (max-width: 768px) {
    .premium-hero h1 {
        font-size: 2rem;
    }

    .premium-hero p {
        font-size: 0.95rem;
    }

    .models-grid .model-card-wrapper {
        width: 100%;
        max-width: 100%;
    }

    .installed-model-card {
        flex-direction: column;
        align-items: stretch;
    }

    .installed-model-details {
        flex-wrap: wrap;
    }

    .uninstall-btn {
        width: 100%;
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

            <!-- Installed Models Section -->
            <div class="glass-card p-4 mb-4" id="installedModelsSection" style="display: none;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0;">
                        <i class="fas fa-check-circle me-2" style="color: var(--success);"></i>
                        โมเดลที่ติดตั้งแล้ว
                    </h3>
                    <button type="button" class="btn-premium-outline" style="padding: 8px 20px; font-size: 13px;" onclick="loadInstalledModels()">
                        <i class="fas fa-sync-alt me-1"></i> รีเฟรช
                    </button>
                </div>

                <div id="installedModelsContainer">
                    <div class="text-center py-3">
                        <div class="spinner-border" style="color: var(--primary); width: 30px; height: 30px;" role="status"></div>
                        <p class="mt-2 mb-0" style="color: var(--text-light); font-size: 13px;">กำลังโหลดโมเดลที่ติดตั้ง...</p>
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
let isInstalling = false;

// DeepSeek Logo SVG
const DEEPSEEK_LOGO = `<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="deepseek-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#212555;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#424aaa;stop-opacity:1" />
        </linearGradient>
    </defs>
    <circle cx="100" cy="100" r="90" fill="url(#deepseek-gradient)"/>
    <path d="M60 80 L100 100 L140 80 M60 120 L100 100 L140 120" stroke="white" stroke-width="8" fill="none" stroke-linecap="round"/>
    <circle cx="100" cy="100" r="12" fill="white"/>
</svg>`;

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
        statusText = 'ใช้งาน CPU เท่านั้น (ช้ากว่า GPU มาก แต่ยังใช้งานได้)';
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
            <div class="mb-5">
                <h4 style="font-size: 18px; font-weight: 700; color: var(--success); margin-bottom: 24px; text-align: center;">
                    <i class="fas fa-star me-2"></i>แนะนำสำหรับคุณ
                </h4>
                <div class="models-grid">
                    ${recommendations.recommended.map(model => createModelCard(model, 'recommended')).join('')}
                </div>
            </div>
        ` : ''}

        ${recommendations.possible.length > 0 ? `
            <div class="mb-5">
                <h4 style="font-size: 18px; font-weight: 700; color: var(--warning); margin-bottom: 24px; text-align: center;">
                    <i class="fas fa-exclamation-triangle me-2"></i>ใช้งานได้ แต่อาจช้า
                </h4>
                <div class="models-grid">
                    ${recommendations.possible.map(model => createModelCard(model, 'possible')).join('')}
                </div>
            </div>
        ` : ''}

        ${recommendations.not_recommended.length > 0 ? `
            <div>
                <h4 style="font-size: 18px; font-weight: 700; color: var(--danger); margin-bottom: 24px; text-align: center;">
                    <i class="fas fa-times-circle me-2"></i>ทรัพยากรไม่เพียงพอ (แต่สามารถติดตั้งแบบบังคับได้)
                </h4>
                <div class="models-grid">
                    ${recommendations.not_recommended.map(model => createModelCard(model, 'not_recommended')).join('')}
                </div>
            </div>
        ` : ''}
    `;

    container.innerHTML = html;
}

// Create model card
function createModelCard(model, status) {
    const badgeHtml = status === 'recommended' ?
        '<span class="badge-recommended">⭐ แนะนำ</span>' :
        status === 'possible' ?
        '<span class="badge-warning">⚠️ อาจช้า</span>' :
        '<span class="badge-not-recommended">⚠️ ทรัพยากรไม่เพียงพอ</span>';

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

    const canInstall = true; // อนุญาตให้ติดตั้งได้ทุกโมเดล
    const isForceInstall = status === 'not_recommended';

    // Get minimum requirements
    const allQuants = model.all_quantizations || [];
    const minQuant = allQuants.length > 0 ? allQuants[allQuants.length - 1] : null;
    const minRam = minQuant ? minQuant.ram_required : ramReq;
    const minVram = minQuant ? minQuant.vram_required : vramReq;

    return `
        <div class="model-card-wrapper">
            <div class="model-card ${status === 'recommended' ? 'recommended' : ''} ${status === 'not_recommended' ? 'not-recommended' : ''}" onclick="openInstallModal('${model.model_id}')">
                ${badgeHtml}

                <div class="model-header">
                    <div class="model-logo">
                        ${DEEPSEEK_LOGO}
                    </div>
                    <div class="model-name">${model.model_name}</div>
                    <div class="model-size">${model.model_size} Parameters</div>
                </div>

                <div class="model-body">
                    <div class="model-description">${model.description}</div>

                    <div class="model-capabilities">
                        ${capabilities}
                    </div>

                    <div class="requirements-box">
                        <h6><i class="fas fa-exclamation-circle me-1"></i> สเปคขั้นต่ำ</h6>
                        <div class="spec-item">
                            <i class="fas fa-memory"></i>
                            <span><strong>RAM:</strong> ${minRam} GB ขึ้นไป</span>
                        </div>
                        ${useGpu ? `
                        <div class="spec-item">
                            <i class="fas fa-server"></i>
                            <span><strong>VRAM:</strong> ${minVram} GB (หรือใช้ CPU)</span>
                        </div>
                        ` : ''}
                        <div class="spec-item">
                            <i class="fas fa-hdd"></i>
                            <span><strong>พื้นที่:</strong> ${Math.ceil(minRam * 1.5)} GB</span>
                        </div>
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

                    ${model.features && model.features.length > 0 ? `
                        <div style="margin-top: 12px; padding: 10px; background: rgba(99, 102, 241, 0.05); border-radius: 8px; font-size: 12px; color: var(--text-light);">
                            <strong style="display: block; margin-bottom: 6px; color: var(--text-dark);">✨ ความสามารถ:</strong>
                            <ul style="margin: 0; padding-left: 20px;">
                                ${model.features.slice(0, 3).map(feature => `<li>${feature}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>

                <div class="model-footer">
                    <button class="install-btn ${isForceInstall ? 'force-install' : ''}">
                        <i class="fas ${isForceInstall ? 'fa-exclamation-triangle' : 'fa-download'} me-2"></i>
                        ${isForceInstall ? 'ติดตั้งแบบบังคับ (จะช้า)' : 'คลิกเพื่อติดตั้ง'}
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
    if (isInstalling) {
        alert('⚠️ กำลังติดตั้งโมเดลอยู่ กรุณารอให้เสร็จก่อน');
        return;
    }

    const model = [...recommendations.recommended, ...recommendations.possible, ...recommendations.not_recommended]
        .find(m => m.model_id === modelId);

    if (!model) return;

    const modal = document.getElementById('installModal');
    const backdrop = document.getElementById('modalBackdrop');
    const title = document.getElementById('installModalTitle');
    const body = document.getElementById('installModalBody');

    title.textContent = `ติดตั้ง ${model.model_name}`;

    const quantizations = model.all_quantizations || [];

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
                <div class="model-card" style="margin-bottom: 16px; cursor: pointer; ${!quant.can_run ? 'opacity: 0.6;' : ''}" onclick="startInstallation('${modelId}', '${quant.name}', ${quant.can_run})">
                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <h5 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">${quant.name}</h5>
                                <p style="font-size: 13px; color: var(--text-light); margin: 0;">
                                    ${quant.use_gpu ? '🎮 GPU Mode' : '🖥️ CPU Mode'}
                                    ${!quant.can_run ? ' - ⚠️ ทรัพยากรไม่เพียงพอ' : ''}
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
                                    ${quant.speed_estimate.time_for_500_tokens}
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
async function startInstallation(modelId, quantization, canRun = true) {
    if (isInstalling) {
        alert('⚠️ กำลังติดตั้งโมเดลอยู่ กรุณารอให้เสร็จก่อน');
        return;
    }

    if (!canRun) {
        const confirmed = confirm('⚠️ ทรัพยากรไม่เพียงพอ แต่คุณยังสามารถติดตั้งได้ (อาจช้ามาก)\n\nต้องการดำเนินการต่อหรือไม่?');
        if (!confirmed) return;
    }

    closeInstallModal();
    isInstalling = true;

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
        isInstalling = false;
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
                    isInstalling = false;

                    // Show post-installation instructions
                    showPostInstallInstructions(data.data);

                    loadRecommendations(); // Reload to update status
                } else if (status === 'failed' || status === 'cancelled') {
                    clearInterval(interval);
                    progressBar.classList.remove('active');
                    isInstalling = false;
                    alert('การติดตั้งล้มเหลว: ' + (data.data.error_message || 'Unknown error'));
                }
            }
        } catch (error) {
            console.error('Progress check error:', error);
        }
    }, 2000);
}

// Show post-installation instructions
function showPostInstallInstructions(installData) {
    const modal = document.getElementById('installModal');
    const backdrop = document.getElementById('modalBackdrop');
    const title = document.getElementById('installModalTitle');
    const body = document.getElementById('installModalBody');

    title.textContent = '🎉 ติดตั้งสำเร็จ!';

    body.innerHTML = `
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>ติดตั้งโมเดลเรียบร้อยแล้ว!</strong><br>
                <small>โมเดลพร้อมใช้งาน ทำตามขั้นตอนด้านล่างเพื่อตั้งค่า</small>
            </div>
        </div>

        <h6 style="font-weight: 700; margin-bottom: 16px;">📋 ขั้นตอนการตั้งค่า:</h6>

        <div style="background: var(--bg-light); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
            <p style="margin: 0 0 12px; font-weight: 600;">1. ไปที่หน้า AI Providers</p>
            <a href="{{ route('admin.ai-providers.index') }}" class="btn-premium" style="display: inline-block; text-decoration: none; padding: 10px 24px;">
                <i class="fas fa-cog me-2"></i>เปิดหน้า AI Providers
            </a>
        </div>

        <div style="background: var(--bg-light); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
            <p style="margin: 0 0 8px; font-weight: 600;">2. เปิดใช้งาน Local AI (Ollama)</p>
            <p style="margin: 0; font-size: 13px; color: var(--text-light);">
                คลิกปุ่ม "Start" เพื่อเริ่ม Ollama service
            </p>
        </div>

        <div style="background: var(--bg-light); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
            <p style="margin: 0 0 8px; font-weight: 600;">3. สร้าง AI Bot Profile</p>
            <p style="margin: 0; font-size: 13px; color: var(--text-light);">
                ไปที่เมนู "AI Bots" เพื่อสร้าง Bot และเลือกโมเดลที่ติดตั้ง
            </p>
        </div>

        <div style="background: var(--bg-light); padding: 16px; border-radius: 12px;">
            <p style="margin: 0 0 8px; font-weight: 600;">4. ทดสอบการทำงาน</p>
            <p style="margin: 0; font-size: 13px; color: var(--text-light);">
                ใช้ฟีเจอร์ "Test" ใน AI Bot เพื่อทดสอบว่าโมเดลทำงานปกติ
            </p>
        </div>

        <div style="margin-top: 20px; padding: 16px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border-left: 4px solid var(--info);">
            <p style="margin: 0; font-size: 13px; color: var(--info);">
                <i class="fas fa-info-circle me-2"></i>
                <strong>เคล็ดลับ:</strong> ระบบ Monitoring จะเริ่มทำงานอัตโนมัติหลังจากคุณเริ่มใช้งาน Bot
            </p>
        </div>
    `;

    modal.classList.add('show');
    backdrop.classList.add('show');
}

// Check Ollama status
async function checkOllamaStatus() {
    try {
        const response = await fetch('{{ route("admin.ai-installation.ollama-status") }}');
        const data = await response.json();

        if (data.success && data.data.installed) {
            console.log('Ollama is installed and running');
            // Load installed models if Ollama is running
            loadInstalledModels();
        }
    } catch (error) {
        console.error('Ollama status check error:', error);
    }
}

// Load installed models
async function loadInstalledModels() {
    try {
        const response = await fetch('{{ route("admin.ai-installation.installed-models") }}');
        const data = await response.json();

        if (data.success && data.data && data.data.length > 0) {
            installedModels = data.data;
            displayInstalledModels(installedModels);
        } else {
            // Hide section if no installed models
            document.getElementById('installedModelsSection').style.display = 'none';
        }
    } catch (error) {
        console.error('Failed to load installed models:', error);
        document.getElementById('installedModelsSection').style.display = 'none';
    }
}

// Display installed models
function displayInstalledModels(models) {
    const container = document.getElementById('installedModelsContainer');
    const section = document.getElementById('installedModelsSection');

    if (!models || models.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';

    container.innerHTML = models.map(model => `
        <div class="installed-model-card">
            <div class="installed-model-info">
                <div class="installed-model-name">
                    <i class="fas fa-brain me-2" style="color: var(--primary);"></i>
                    ${model.name}
                </div>
                <div class="installed-model-details">
                    <span>
                        <i class="fas fa-tag"></i>
                        ID: ${model.id}
                    </span>
                    <span>
                        <i class="fas fa-hdd"></i>
                        ${model.size}
                    </span>
                    <span>
                        <i class="fas fa-clock"></i>
                        ${model.modified || 'ไม่ทราบ'}
                    </span>
                </div>
            </div>
            <button class="uninstall-btn" onclick="confirmUninstall('${model.name}')">
                <i class="fas fa-trash-alt me-2"></i>
                ถอนการติดตั้ง
            </button>
        </div>
    `).join('');
}

// Confirm uninstall
function confirmUninstall(modelName) {
    if (isInstalling) {
        alert('⚠️ กำลังติดตั้งโมเดลอยู่ กรุณารอให้เสร็จก่อน');
        return;
    }

    const modal = document.getElementById('installModal');
    const backdrop = document.getElementById('modalBackdrop');
    const title = document.getElementById('installModalTitle');
    const body = document.getElementById('installModalBody');

    title.innerHTML = '<i class="fas fa-exclamation-triangle me-2" style="color: var(--warning);"></i>ยืนยันการถอนการติดตั้ง';

    body.innerHTML = `
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>คำเตือน!</strong><br>
                <small>คุณกำลังจะถอนการติดตั้งโมเดล <strong>${modelName}</strong></small>
            </div>
        </div>

        <div style="background: var(--bg-light); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
            <h6 style="font-weight: 700; margin-bottom: 12px; color: var(--text-dark);">
                <i class="fas fa-info-circle me-2"></i>สิ่งที่จะเกิดขึ้น:
            </h6>
            <ul style="margin: 0; padding-left: 24px; color: var(--text-light); font-size: 14px;">
                <li>โมเดลจะถูกลบออกจากระบบโดยสมบูรณ์</li>
                <li>พื้นที่ดิสก์จะถูกคืนกลับ</li>
                <li>AI Bot ที่ใช้โมเดลนี้จะไม่สามารถทำงานได้</li>
                <li>สามารถติดตั้งใหม่ได้ทุกเมื่อ</li>
            </ul>
        </div>

        <div style="background: rgba(239, 68, 68, 0.1); padding: 16px; border-radius: 12px; border-left: 4px solid var(--danger);">
            <p style="margin: 0; font-size: 13px; color: var(--danger);">
                <i class="fas fa-shield-alt me-2"></i>
                <strong>ความปลอดภัย:</strong> การถอนการติดตั้งจะไม่ทำให้ระบบอื่นเสียหาย
            </p>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
            <button class="btn-premium-outline" onclick="closeInstallModal()">
                <i class="fas fa-times me-2"></i>ยกเลิก
            </button>
            <button class="uninstall-btn" onclick="uninstallModel('${modelName}')">
                <i class="fas fa-trash-alt me-2"></i>ยืนยันถอนการติดตั้ง
            </button>
        </div>
    `;

    modal.classList.add('show');
    backdrop.classList.add('show');
}

// Uninstall model
async function uninstallModel(modelName) {
    closeInstallModal();

    // Show progress
    const progressBar = document.getElementById('installProgressBar');
    progressBar.classList.add('active');
    const progressFill = progressBar.querySelector('.progress-bar-fill');
    progressFill.style.width = '30%';

    try {
        const response = await fetch('{{ route("admin.ai-installation.uninstall") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                model_id: modelName
            })
        });

        const data = await response.json();

        progressFill.style.width = '100%';

        if (data.success) {
            // Success
            setTimeout(() => {
                progressBar.classList.remove('active');
                alert('✅ ถอนการติดตั้งโมเดลเรียบร้อยแล้ว!\n\n' + modelName + ' ถูกลบออกจากระบบแล้ว');

                // Reload installed models
                loadInstalledModels();

                // Reload recommendations to update status
                loadRecommendations();
            }, 500);
        } else {
            // Error
            progressBar.classList.remove('active');

            let errorMessage = data.message || 'ไม่สามารถถอนการติดตั้งได้';

            // Handle specific error codes
            if (data.code === 'MODEL_IN_USE') {
                errorMessage = '❌ ไม่สามารถถอนการติดตั้งได้\n\n' +
                    data.message + '\n\n' +
                    'กรุณาปิดการใช้งาน หรือเปลี่ยนโมเดลของ AI Bot เหล่านั้นก่อน';
            } else if (data.code === 'OLLAMA_NOT_RUNNING') {
                errorMessage = '❌ Ollama Service ไม่ได้ทำงานอยู่\n\n' + data.message;
            }

            alert(errorMessage);
        }
    } catch (error) {
        progressBar.classList.remove('active');
        console.error('Uninstall error:', error);
        alert('เกิดข้อผิดพลาดในการถอนการติดตั้ง: ' + error.message);
    }
}
</script>
@endpush

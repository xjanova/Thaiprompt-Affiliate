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

.glass-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 20px 60px 0 rgba(31, 38, 135, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2) inset;
}

/* Premium Hero */
.premium-hero {
    position: relative;
    padding: 60px 0;
    text-align: center;
    color: white;
    z-index: 1;
}

.premium-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.premium-hero p {
    font-size: 1.25rem;
    opacity: 0.95;
    max-width: 800px;
    margin: 0 auto 40px;
    line-height: 1.8;
}

/* Premium Button */
.btn-premium {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
    border: none;
    color: white;
    padding: 16px 48px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 16px;
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
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
    padding: 14px 40px;
    border-radius: 50px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.btn-premium-outline:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    color: white;
}

/* Feature Card */
.feature-card-premium {
    background: white;
    border-radius: 20px;
    padding: 32px;
    height: 100%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}

.feature-card-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.feature-card-premium:hover::before {
    transform: scaleX(1);
}

.feature-card-premium:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(99, 102, 241, 0.15);
}

.feature-icon-premium {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 24px;
    position: relative;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.icon-gradient-1 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.icon-gradient-2 {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.icon-gradient-3 {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.icon-gradient-4 {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

/* Stats Card */
.stats-card-premium {
    background: white;
    border-radius: 16px;
    padding: 24px;
    height: 100%;
    border-left: 4px solid var(--primary);
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.stats-card-premium:hover {
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

/* Model Card */
.model-card-premium {
    background: white;
    border: 2px solid var(--border);
    border-radius: 20px;
    padding: 28px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    position: relative;
}

.model-card-premium:hover {
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(99, 102, 241, 0.2);
}

.model-card-premium.selected {
    border-color: var(--primary);
    border-width: 3px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(129, 140, 248, 0.05) 100%);
}

.model-card-premium .badge-recommended {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Progress Bar */
.progress-premium {
    height: 12px;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-premium .progress-bar-premium {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 10px;
    transition: width 0.4s ease;
    position: relative;
    overflow: hidden;
}

.progress-premium .progress-bar-premium::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    animation: progress-shimmer 2s infinite;
}

@keyframes progress-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Wizard Steps */
.wizard-steps-premium {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
    padding: 0 60px;
}

.wizard-steps-premium::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 80px;
    right: 80px;
    height: 3px;
    background: linear-gradient(90deg, var(--border) 0%, var(--border) 100%);
    z-index: 0;
}

.wizard-step-premium {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.wizard-step-premium .circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    border: 3px solid var(--border);
    color: var(--text-light);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.wizard-step-premium.active .circle {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    border-color: var(--primary);
    color: white;
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
    transform: scale(1.1);
}

.wizard-step-premium.completed .circle {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
    border-color: var(--success);
    color: white;
}

.wizard-step-premium .label {
    font-size: 14px;
    color: var(--text-light);
    font-weight: 500;
}

.wizard-step-premium.active .label {
    color: var(--primary);
    font-weight: 700;
}

/* Installation Log */
.installation-log-premium {
    background: #1e1e1e;
    border: 1px solid #333;
    border-radius: 12px;
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
    font-size: 13px;
    color: #e0e0e0;
    line-height: 1.6;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2) inset;
}

.installation-log-premium::-webkit-scrollbar {
    width: 8px;
}

.installation-log-premium::-webkit-scrollbar-track {
    background: #2d2d2d;
    border-radius: 4px;
}

.installation-log-premium::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 4px;
}

/* Alert Premium */
.alert-premium {
    border-radius: 16px;
    padding: 20px 24px;
    border: none;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.alert-premium.success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
    border-left: 4px solid var(--success);
}

.alert-premium.info {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(96, 165, 250, 0.1) 100%);
    border-left: 4px solid var(--info);
}

.alert-premium.warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);
    border-left: 4px solid var(--warning);
}

.alert-premium.danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(248, 113, 113, 0.1) 100%);
    border-left: 4px solid var(--danger);
}

/* Table Premium */
.table-premium {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.table-premium thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: var(--text-dark);
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 20px;
    text-align: left;
    border-bottom: 2px solid var(--border);
}

.table-premium tbody td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
    color: var(--text-dark);
}

.table-premium tbody tr:last-child td {
    border-bottom: none;
}

.table-premium tbody tr {
    transition: all 0.2s ease;
}

.table-premium tbody tr:hover {
    background: rgba(99, 102, 241, 0.02);
}

/* Badge Premium */
.badge-premium {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.badge-premium.success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(52, 211, 153, 0.15) 100%);
    color: var(--success);
}

.badge-premium.warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(251, 191, 36, 0.15) 100%);
    color: var(--warning);
}

.badge-premium.danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(248, 113, 113, 0.15) 100%);
    color: var(--danger);
}

/* Comparison Card */
.comparison-card-premium {
    background: white;
    border: 2px solid var(--border);
    border-radius: 20px;
    padding: 32px;
    height: 100%;
    transition: all 0.3s ease;
    position: relative;
}

.comparison-card-premium.highlight {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(129, 140, 248, 0.03) 100%);
    transform: scale(1.05);
    box-shadow: 0 12px 32px rgba(99, 102, 241, 0.15);
}

.comparison-card-premium.highlight::before {
    content: '⭐ แนะนำ';
    position: absolute;
    top: -16px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);
}

.comparison-card-premium .price {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 20px 0;
}

/* Floating Animation */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.float-animation {
    animation: float 3s ease-in-out infinite;
}

/* Pulse Animation */
@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.4); }
    50% { box-shadow: 0 0 40px rgba(99, 102, 241, 0.8); }
}

.content-wrapper {
    position: relative;
    z-index: 1;
    padding: 40px 0;
}
</style>
@endpush

@section('content')
<div class="premium-container">
    <!-- Premium Hero -->
    <div class="premium-hero">
        <div class="container">
            <div class="float-animation" style="display: inline-block;">
                <i class="fas fa-server" style="font-size: 80px; margin-bottom: 30px; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));"></i>
            </div>
            <h1>ติดตั้ง AI ของตัวเอง</h1>
            <p>
                ติดตั้ง DeepSeek AI บนเซิร์ฟเวอร์ของคุณเอง ใช้งานฟรี ไม่จำกัด ไม่มีค่าใช้จ่ายรายเดือน<br>
                ควบคุมข้อมูลของคุณได้เอง 100% และประหยัดค่าใช้จ่ายได้มากกว่า 90%
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <button type="button" class="btn-premium" onclick="startNewInstallation()">
                    <i class="fas fa-rocket me-2"></i>
                    เริ่มติดตั้งเลย
                </button>
                <button type="button" class="btn-premium-outline" onclick="document.getElementById('features').scrollIntoView({behavior: 'smooth'})">
                    <i class="fas fa-info-circle me-2"></i>
                    เรียนรู้เพิ่มเติม
                </button>
            </div>
        </div>
    </div>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="container">
            <!-- Features Section -->
            <div id="features" class="row g-4 mb-5">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card-premium">
                        <div class="feature-icon-premium icon-gradient-1">
                            <i class="fas fa-infinity"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: var(--text-dark);">ใช้งานไม่จำกัด</h3>
                        <p style="color: var(--text-light); margin: 0; line-height: 1.6;">ไม่มีข้อจำกัดจำนวนคำถาม ไม่มีค่าใช้จ่ายรายเดือน ใช้งานได้เท่าที่ต้องการ</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card-premium">
                        <div class="feature-icon-premium icon-gradient-2">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: var(--text-dark);">ปลอดภัย 100%</h3>
                        <p style="color: var(--text-light); margin: 0; line-height: 1.6;">ข้อมูลอยู่บนเซิร์ฟเวอร์ของคุณ ไม่ส่งข้อมูลออกไปภายนอก รับรองความเป็นส่วนตัว</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card-premium">
                        <div class="feature-icon-premium icon-gradient-3">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: var(--text-dark);">ประหยัดงบ 90%+</h3>
                        <p style="color: var(--text-light); margin: 0; line-height: 1.6;">เทียบกับ ChatGPT API หรือ Claude ประหยัดได้หลักหมื่นบาทต่อเดือน</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card-premium">
                        <div class="feature-icon-premium icon-gradient-4">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: var(--text-dark);">รวดเร็ว ทันใจ</h3>
                        <p style="color: var(--text-light); margin: 0; line-height: 1.6;">ติดตั้งบนเซิร์ฟเวอร์ของคุณ ไม่ต้องรอคิว ตอบสนองเร็วกว่า Cloud API</p>
                    </div>
                </div>
            </div>

            <!-- Pricing Comparison -->
            <div class="glass-card p-5 mb-5">
                <h2 class="text-center mb-4" style="font-size: 32px; font-weight: 800; color: var(--text-dark);">เปรียบเทียบค่าใช้จ่าย</h2>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="comparison-card-premium">
                            <h3 style="font-size: 22px; font-weight: 700; color: var(--text-dark);">ChatGPT API</h3>
                            <div class="price">$20-200<small style="font-size: 1rem; opacity: 0.6;">/เดือน</small></div>
                            <ul class="list-unstyled" style="line-height: 2;">
                                <li><i class="fas fa-times-circle text-danger me-2"></i>จำกัดจำนวนคำถาม</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>ค่าใช้จ่ายสูง</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>ข้อมูลถูกส่งออกไป</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>ต้องพึ่งพา API ภายนอก</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="comparison-card-premium highlight">
                            <h3 style="font-size: 22px; font-weight: 700; color: var(--text-dark);">Self-Hosted AI</h3>
                            <div class="price">฿0<small style="font-size: 1rem; opacity: 0.6;">/เดือน</small></div>
                            <ul class="list-unstyled" style="line-height: 2;">
                                <li><i class="fas fa-check-circle me-2" style="color: var(--success);"></i>ใช้ไม่จำกัด ฟรี!</li>
                                <li><i class="fas fa-check-circle me-2" style="color: var(--success);"></i>ไม่มีค่าใช้จ่ายรายเดือน</li>
                                <li><i class="fas fa-check-circle me-2" style="color: var(--success);"></i>ข้อมูลอยู่กับคุณ 100%</li>
                                <li><i class="fas fa-check-circle me-2" style="color: var(--success);"></i>ควบคุมได้เองทั้งหมด</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="comparison-card-premium">
                            <h3 style="font-size: 22px; font-weight: 700; color: var(--text-dark);">Claude API</h3>
                            <div class="price">$15-150<small style="font-size: 1rem; opacity: 0.6;">/เดือน</small></div>
                            <ul class="list-unstyled" style="line-height: 2;">
                                <li><i class="fas fa-times-circle text-danger me-2"></i>คิดตามจำนวน tokens</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>ราคาแพง</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>ข้อมูลส่งไป Anthropic</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i>มีข้อจำกัด rate limit</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Section -->
            <div class="glass-card p-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 style="font-size: 28px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">จัดการ AI โมเดล</h2>
                        <p style="color: var(--text-light); margin: 0;">ติดตั้งและจัดการโมเดล AI ที่คุณต้องการ</p>
                    </div>
                    <button type="button" class="btn-premium" onclick="startNewInstallation()">
                        <i class="fas fa-plus me-2"></i>ติดตั้งโมเดลใหม่
                    </button>
                </div>

                <!-- Ollama Status -->
                <div id="ollama-status" class="alert-premium info">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-3" role="status"></div>
                        <span>กำลังตรวจสอบสถานะ Ollama...</span>
                    </div>
                </div>

                <!-- Installed Models -->
                <div class="mt-4">
                    <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">โมเดลที่ติดตั้งแล้ว</h3>
                    <div id="installed-models">
                        <div class="text-center py-5">
                            <div class="spinner-border" style="color: var(--primary);" role="status"></div>
                            <p class="mt-3" style="color: var(--text-light);">กำลังโหลด...</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Installation Logs -->
                @if($recentLogs->count() > 0)
                <div class="mt-5">
                    <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">ประวัติการติดตั้งล่าสุด</h3>
                    <div class="table-responsive">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>เวลา</th>
                                    <th>โมเดล</th>
                                    <th>สถานะ</th>
                                    <th>Progress</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->diffForHumans() }}</td>
                                    <td><strong>{{ $log->config['model_id'] ?? 'N/A' }}</strong></td>
                                    <td>
                                        <span class="badge-premium {{ $log->isCompleted() ? 'success' : ($log->isFailed() ? 'danger' : 'warning') }}">
                                            {{ $log->getStatusMessage() }}
                                        </span>
                                    </td>
                                    <td>{{ $log->progress_percentage }}%</td>
                                    <td>
                                        <button class="btn-premium-outline" style="padding: 8px 16px; font-size: 13px;" onclick="viewLog({{ $log->id }})">
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
        <div class="modal-content" style="border: none; border-radius: 24px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; border: none; padding: 32px;">
                <div class="w-100">
                    <h4 class="modal-title mb-0" style="font-size: 28px; font-weight: 800;">ติดตั้ง AI โมเดล</h4>
                    <p class="mb-4 mt-2" style="opacity: 0.9; font-size: 16px;">ติดตั้งและกำหนดค่า AI โมเดลของคุณภายใน 4 ขั้นตอน</p>

                    <!-- Step Indicator -->
                    <div class="wizard-steps-premium">
                        <div class="wizard-step-premium" id="wizard-step-1">
                            <div class="circle">1</div>
                            <div class="label">ตรวจสอบระบบ</div>
                        </div>
                        <div class="wizard-step-premium" id="wizard-step-2">
                            <div class="circle">2</div>
                            <div class="label">เลือกโมเดล</div>
                        </div>
                        <div class="wizard-step-premium" id="wizard-step-3">
                            <div class="circle">3</div>
                            <div class="label">กำลังติดตั้ง</div>
                        </div>
                        <div class="wizard-step-premium" id="wizard-step-4">
                            <div class="circle">4</div>
                            <div class="label">เสร็จสิ้น</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="cancelWizard()"></button>
            </div>
            <div class="modal-body" style="padding: 40px;">
                <!-- Step 1: Check Requirements -->
                <div id="step-requirements" class="wizard-step">
                    <div id="requirements-result">
                        <div class="text-center py-5">
                            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(129, 140, 248, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                                <i class="fas fa-server" style="font-size: 48px; color: var(--primary);"></i>
                            </div>
                            <h5 class="mb-3" style="font-size: 24px; font-weight: 700; color: var(--text-dark);">ตรวจสอบความพร้อมของระบบ</h5>
                            <p class="text-muted mb-4" style="font-size: 16px; max-width: 600px; margin: 0 auto 32px;">เราจะตรวจสอบ CPU, RAM, GPU, และพื้นที่ว่างเพื่อแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ</p>
                            <button class="btn-premium" onclick="checkRequirements()">
                                <i class="fas fa-check-circle me-2"></i>
                                เริ่มตรวจสอบระบบ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Model -->
                <div id="step-model" class="wizard-step" style="display:none;">
                    <div class="mb-4">
                        <h5 style="font-size: 24px; font-weight: 700; color: var(--text-dark);">เลือกโมเดล AI ที่ต้องการติดตั้ง</h5>
                        <p class="text-muted" style="font-size: 16px;">เราแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ คุณสามารถเลือกโมเดลอื่นได้ตามต้องการ</p>
                    </div>
                    <div id="model-recommendations"></div>
                </div>

                <!-- Step 3: Installing -->
                <div id="step-installing" class="wizard-step" style="display:none;">
                    <div class="text-center mb-4">
                        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(129, 140, 248, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                            <i class="fas fa-cog fa-spin" style="font-size: 48px; color: var(--primary);"></i>
                        </div>
                        <h5 class="mb-2" style="font-size: 24px; font-weight: 700; color: var(--text-dark);">กำลังติดตั้งโมเดล AI</h5>
                        <p class="text-muted" style="font-size: 16px;">กรุณารอสักครู่ การติดตั้งอาจใช้เวลาหลายนาทีขึ้นอยู่กับขนาดโมเดล</p>
                    </div>
                    <div id="installation-progress"></div>
                </div>

                <!-- Step 4: Complete -->
                <div id="step-complete" class="wizard-step" style="display:none;">
                    <div class="text-center py-5">
                        <div style="width: 120px; height: 120px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                            <i class="fas fa-check-circle" style="font-size: 60px; color: var(--success);"></i>
                        </div>
                        <h3 class="mb-3" style="font-size: 32px; font-weight: 800; color: var(--text-dark);">ติดตั้งสำเร็จ!</h3>
                        <p class="text-muted mb-5" style="font-size: 18px;">โมเดล AI ของคุณพร้อมใช้งานแล้ว คุณสามารถเริ่มใช้งาน AI ในระบบได้ทันที</p>
                        <div class="d-flex justify-content-center gap-3">
                            <button class="btn-premium" onclick="closeWizard()">
                                <i class="fas fa-check me-2"></i>
                                เสร็จสิ้น
                            </button>
                            <button class="btn-premium-outline" onclick="startNewInstallation()" style="color: var(--primary); border-color: var(--primary);">
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
                <div class="alert-premium success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Ollama กำลังทำงาน</strong> (${status.version})
                </div>
            `;
        } else if (status.installed && !status.running) {
            html = `
                <div class="alert-premium warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Ollama ติดตั้งแล้วแต่ไม่ได้ทำงาน</strong> กรุณาเริ่ม Ollama service
                </div>
            `;
        } else {
            html = `
                <div class="alert-premium info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Ollama ยังไม่ได้ติดตั้ง</strong> จะติดตั้งอัตโนมัติเมื่อเริ่มติดตั้งโมเดล
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
                <div class="alert-premium info">
                    <i class="fas fa-info-circle me-2"></i>
                    ยังไม่มีโมเดลที่ติดตั้ง
                </div>
            `);
            return;
        }

        let html = `
            <table class="table-premium">
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
                        <button class="btn-premium-outline" style="padding: 8px 16px; font-size: 13px; color: var(--danger); border-color: var(--danger);" onclick="uninstallModel('${model.name}')">
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
            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(129, 140, 248, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                <i class="fas fa-server" style="font-size: 48px; color: var(--primary);"></i>
            </div>
            <h5 class="mb-3" style="font-size: 24px; font-weight: 700; color: var(--text-dark);">ตรวจสอบความพร้อมของระบบ</h5>
            <p class="text-muted mb-4" style="font-size: 16px; max-width: 600px; margin: 0 auto 32px;">เราจะตรวจสอบ CPU, RAM, GPU, และพื้นที่ว่างเพื่อแนะนำโมเดลที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ</p>
            <button class="btn-premium" onclick="checkRequirements()">
                <i class="fas fa-check-circle me-2"></i>
                เริ่มตรวจสอบระบบ
            </button>
        </div>
    `);

    $('#installationWizard').modal('show');
}

function updateWizardStep(step) {
    // Reset all steps
    $('.wizard-step-premium').removeClass('active completed');

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
        <div class="text-center py-5">
            <div class="spinner-border" style="color: var(--primary);" role="status"></div>
            <p class="mt-3" style="color: var(--text-light);">กำลังตรวจสอบ...</p>
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
    let html = '<div class="row g-3">';

    // CPU
    const cpuColor = data.cpu.status === 'excellent' ? 'success' : 'warning';
    html += `
        <div class="col-lg-6">
            <div class="stats-card-premium">
                <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon-premium icon-gradient-1 me-3" style="width: 56px; height: 56px; font-size: 24px;">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div>
                        <h6 class="mb-0" style="font-weight: 700;">CPU</h6>
                        <small class="text-muted">${data.cpu.model}</small>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Cores</span>
                        <strong>${data.cpu.cores} cores</strong>
                    </div>
                </div>
                <span class="badge-premium ${cpuColor}">${data.cpu.message}</span>
            </div>
        </div>
    `;

    // RAM
    const ramColor = data.ram.status === 'excellent' ? 'success' : (data.ram.status === 'good' ? 'warning' : 'danger');
    const ramPercent = ((data.ram.total_gb - data.ram.available_gb) / data.ram.total_gb * 100).toFixed(1);
    html += `
        <div class="col-lg-6">
            <div class="stats-card-premium" style="border-left-color: var(--success);">
                <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon-premium icon-gradient-2 me-3" style="width: 56px; height: 56px; font-size: 24px;">
                        <i class="fas fa-memory"></i>
                    </div>
                    <div>
                        <h6 class="mb-0" style="font-weight: 700;">RAM</h6>
                        <small class="text-muted">${data.ram.available_gb} GB / ${data.ram.total_gb} GB พร้อมใช้งาน</small>
                    </div>
                </div>
                <div class="progress-premium mb-2">
                    <div class="progress-bar-premium" style="width: ${ramPercent}%"></div>
                </div>
                <span class="badge-premium ${ramColor}">${data.ram.message}</span>
            </div>
        </div>
    `;

    // GPU
    if (data.gpu.available) {
        data.gpu.gpus.forEach((gpu, index) => {
            const gpuPercent = ((gpu.memory_total_gb - gpu.memory_free_gb) / gpu.memory_total_gb * 100).toFixed(1);
            html += `
                <div class="col-lg-6">
                    <div class="stats-card-premium" style="border-left-color: var(--warning);">
                        <div class="d-flex align-items-center mb-3">
                            <div class="feature-icon-premium icon-gradient-3 me-3" style="width: 56px; height: 56px; font-size: 24px;">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" style="font-weight: 700;">GPU ${index + 1}</h6>
                                <small class="text-muted">${gpu.name}</small>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span>VRAM</span>
                                <strong>${gpu.memory_free_gb} GB / ${gpu.memory_total_gb} GB</strong>
                            </div>
                            <div class="progress-premium">
                                <div class="progress-bar-premium" style="width: ${gpuPercent}%"></div>
                            </div>
                        </div>
                        <span class="badge-premium success"><i class="fas fa-check me-1"></i>พร้อมใช้งาน</span>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="col-lg-6">
                <div class="stats-card-premium" style="border-left-color: var(--text-light);">
                    <div class="d-flex align-items-center mb-3">
                        <div class="feature-icon-premium icon-gradient-3 me-3" style="width: 56px; height: 56px; font-size: 24px; opacity: 0.5;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h6 class="mb-0" style="font-weight: 700;">GPU</h6>
                            <small class="text-muted">ไม่พบการ์ดจอ</small>
                        </div>
                    </div>
                    <p class="text-muted mb-0">ไม่พบ GPU - จะใช้ CPU inference แทน</p>
                </div>
            </div>
        `;
    }

    // Disk
    const diskColor = data.disk.status === 'excellent' ? 'success' : 'warning';
    html += `
        <div class="col-lg-6">
            <div class="stats-card-premium" style="border-left-color: var(--info);">
                <div class="d-flex align-items-center mb-3">
                    <div class="feature-icon-premium icon-gradient-4 me-3" style="width: 56px; height: 56px; font-size: 24px;">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div>
                        <h6 class="mb-0" style="font-weight: 700;">Disk Space</h6>
                        <small class="text-muted">${data.disk.available_gb} GB พร้อมใช้งาน</small>
                    </div>
                </div>
                <span class="badge-premium ${diskColor}">${data.disk.message}</span>
            </div>
        </div>
    `;

    html += '</div>';

    // Overall Status
    if (data.overall_status.ready) {
        html += `
            <div class="alert-premium success mt-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 28px; color: var(--success);"></i>
                    <div>
                        <h6 class="mb-0" style="font-weight: 700;">ระบบพร้อมใช้งาน!</h6>
                        <small>เซิร์ฟเวอร์ของคุณมีทรัพยากรเพียงพอสำหรับการติดตั้ง AI</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <button class="btn-premium" onclick="showStep('step-model'); updateWizardStep(2);">
                    ถัดไป: เลือกโมเดล AI
                    <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        `;
    } else {
        html += `
            <div class="alert-premium danger mt-4">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 28px; color: var(--danger);"></i>
                    <div>
                        <h6 class="mb-2" style="font-weight: 700;">พบปัญหากับระบบ</h6>
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
    let html = '<div class="row g-4">';

    // Recommended Models
    if (data.recommended && data.recommended.length > 0) {
        html += '<div class="col-12"><h6 style="font-size: 18px; font-weight: 700; color: var(--success);">⭐ แนะนำสำหรับคุณ:</h6></div>';
        data.recommended.forEach(model => {
            html += createModelCard(model, true);
        });
    }

    // Possible Models
    if (data.possible && data.possible.length > 0) {
        html += '<div class="col-12 mt-3"><h6 style="font-size: 18px; font-weight: 700; color: var(--warning);">⚡ ติดตั้งได้แต่อาจช้า:</h6></div>';
        data.possible.forEach(model => {
            html += createModelCard(model, false);
        });
    }

    html += '</div>';
    $('#model-recommendations').html(html);
}

function createModelCard(model, isRecommended) {
    return `
        <div class="col-lg-6">
            <div class="model-card-premium" onclick="selectModelCard(this, '${model.model_id}', '${model.best_quantization.name}')">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 style="font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-dark);">${model.model_name}</h5>
                        ${isRecommended ? '<span class="badge-recommended">⭐ แนะนำ</span>' : '<span class="badge-premium warning">⚡ เป็นไปได้</span>'}
                    </div>
                    <i class="fas fa-robot" style="font-size: 36px; color: var(--primary); opacity: 0.2;"></i>
                </div>

                <p class="text-muted mb-3" style="font-size: 14px; line-height: 1.6;">${model.description}</p>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-microchip me-2" style="color: var(--primary);"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 11px;">Quantization</small>
                                <strong style="font-size: 13px;">${model.best_quantization.name}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-${model.best_quantization.use_gpu ? 'bolt' : 'server'} me-2" style="color: ${model.best_quantization.use_gpu ? 'var(--warning)' : 'var(--info)'};"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 11px;">Device</small>
                                <strong style="font-size: 13px;">${model.best_quantization.use_gpu ? 'GPU' : 'CPU'}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tachometer-alt me-2" style="color: var(--success);"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 11px;">Speed</small>
                                <strong style="font-size: 13px;">~${model.estimated_speed.tokens_per_second} tok/s</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-hdd me-2" style="color: var(--primary);"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 11px;">Size</small>
                                <strong style="font-size: 13px;">${model.best_quantization.disk_space_gb || 'N/A'} GB</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn-premium w-100" onclick="event.stopPropagation(); installModel('${model.model_id}', '${model.best_quantization.name}')">
                    <i class="fas fa-download me-2"></i>
                    ติดตั้งโมเดลนี้
                </button>
            </div>
        </div>
    `;
}

function selectModelCard(element, modelId, quantization) {
    $('.model-card-premium').removeClass('selected');
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-0" style="font-size: 18px; font-weight: 700;">
                        <i class="fas ${statusIcon} me-2" style="color: var(--primary);"></i>
                        ${data.current_step || 'กำลังดำเนินการ...'}
                    </h6>
                </div>
                <div>
                    <span class="badge-premium" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; padding: 8px 16px; font-size: 14px;">${data.progress_percentage}%</span>
                </div>
            </div>
            <div class="progress-premium" style="height: 16px;">
                <div class="progress-bar-premium" role="progressbar" style="width: ${data.progress_percentage}%"></div>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0" style="font-weight: 700;">
                    <i class="fas fa-terminal me-2"></i>
                    Installation Log
                </h6>
                <small class="text-muted">Real-time output</small>
            </div>
            <div class="installation-log-premium">
                ${data.log_output || '<span style="opacity: 0.5;">กำลังเริ่มต้นการติดตั้ง...</span>'}
            </div>
        </div>

        ${data.is_failed ? `
            <div class="alert-premium danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>เกิดข้อผิดพลาด:</strong> ${data.error_message || 'Unknown error'}
            </div>
        ` : ''}
    `;

    $('#installation-progress').html(html);

    // Auto scroll log to bottom
    const logElement = document.querySelector('.installation-log-premium');
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

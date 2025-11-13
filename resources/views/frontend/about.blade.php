@extends('layouts.app')

@section('title', 'เกี่ยวกับเรา - Thaiprompt Affiliate Platform')

@section('content')
@php
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
@endphp

@push('styles')
<style>
:root {
    --primary: {{ $primaryColor }};
    --secondary: {{ $secondaryColor }};
    --accent: {{ $accentColor }};
}

/* Modern Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
    background-size: 200% 200%;
    animation: gradientFlow 15s ease infinite;
    position: relative;
    overflow: hidden;
    padding: 6rem 0;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 10;
}

.hero-title {
    font-size: 4rem;
    font-weight: 900;
    color: white;
    text-shadow: 2px 2px 20px rgba(0,0,0,0.3);
    margin-bottom: 1.5rem;
    animation: fadeInUp 1s ease-out;
}

.hero-subtitle {
    font-size: 1.5rem;
    color: rgba(255,255,255,0.95);
    margin-bottom: 2rem;
    animation: fadeInUp 1s ease-out 0.2s backwards;
}

.version-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.3);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    color: white;
    font-weight: 700;
    font-size: 1.125rem;
    animation: pulse 2s ease-in-out infinite, fadeInUp 1s ease-out 0.4s backwards;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

.version-badge .version-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 3rem;
    animation: fadeInUp 1s ease-out 0.6s backwards;
}

.stat-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.25);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 900;
    color: white;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: rgba(255,255,255,0.9);
    font-size: 1rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Content Sections */
.section {
    padding: 5rem 0;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    text-align: center;
}

.section-subtitle {
    text-align: center;
    color: #6b7280;
    font-size: 1.125rem;
    margin-bottom: 3rem;
}

/* Feature Cards */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.feature-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    border-color: var(--primary);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 1.5rem;
    display: block;
}

.feature-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1rem;
}

.feature-description {
    color: #6b7280;
    line-height: 1.6;
}

/* Vision & Mission Section */
.vision-mission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.vm-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border-radius: 20px;
    padding: 3rem;
    border: 2px solid var(--primary);
}

.vm-icon {
    font-size: 3rem;
    margin-bottom: 1.5rem;
}

.vm-title {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 1.5rem;
}

.vm-content {
    color: #374151;
    font-size: 1.125rem;
    line-height: 1.8;
}

/* Mindmap Section */
#mindmap-container {
    width: 100%;
    height: 600px;
    border: 2px solid #e5e7eb;
    border-radius: 20px;
    background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.mindmap-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.mindmap-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    border: 2px solid var(--primary);
    background: white;
    color: var(--primary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mindmap-btn:hover {
    background: var(--primary);
    color: white;
    transform: scale(1.05);
}

.mindmap-btn-share {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border: none;
}

.mindmap-btn-share:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

/* Share Modal Styles */
.share-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.share-modal-content {
    background: white;
    border-radius: 24px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.dark .share-modal-content {
    background: #1f2937;
}

.share-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 2px solid #e5e7eb;
}

.dark .share-modal-header {
    border-bottom-color: #374151;
}

.share-modal-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
}

.dark .share-modal-title {
    color: #f9fafb;
}

.share-modal-close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.share-modal-close:hover {
    background: #e5e7eb;
    color: #111827;
    transform: rotate(90deg);
}

.dark .share-modal-close {
    background: #374151;
    color: #9ca3af;
}

.dark .share-modal-close:hover {
    background: #4b5563;
    color: #f9fafb;
}

.share-modal-body {
    padding: 2rem;
}

.share-buttons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.share-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1rem;
    border-radius: 16px;
    border: 2px solid #e5e7eb;
    background: white;
    color: #374151;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.share-button:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.dark .share-button {
    background: #111827;
    border-color: #374151;
    color: #e5e7eb;
}

.share-facebook {
    border-color: #1877f2;
    color: #1877f2;
}

.share-facebook:hover {
    background: #1877f2;
    color: white;
}

.share-twitter {
    border-color: #1da1f2;
    color: #1da1f2;
}

.share-twitter:hover {
    background: #1da1f2;
    color: white;
}

.share-line {
    border-color: #00b900;
    color: #00b900;
}

.share-line:hover {
    background: #00b900;
    color: white;
}

.share-link {
    border-color: #6366f1;
    color: #6366f1;
}

.share-link:hover {
    background: #6366f1;
    color: white;
}

.share-download {
    border-color: #10b981;
    color: #10b981;
}

.share-download:hover {
    background: #10b981;
    color: white;
}

.share-email {
    border-color: #f59e0b;
    color: #f59e0b;
}

.share-email:hover {
    background: #f59e0b;
    color: white;
}

.share-url-container {
    position: relative;
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.share-url-input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    background: #f9fafb;
    color: #374151;
    font-family: 'Courier New', monospace;
}

.dark .share-url-input {
    background: #111827;
    border-color: #374151;
    color: #e5e7eb;
}

.share-url-copy-btn {
    padding: 1rem;
    border: 2px solid var(--primary);
    border-radius: 12px;
    background: white;
    color: var(--primary);
    cursor: pointer;
    transition: all 0.3s ease;
}

.share-url-copy-btn:hover {
    background: var(--primary);
    color: white;
}

.dark .share-url-copy-btn {
    background: #111827;
}

.dark .share-url-copy-btn:hover {
    background: var(--primary);
}

.copy-success-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border-radius: 12px;
    font-weight: 600;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.dark .copy-success-message {
    background: linear-gradient(135deg, #065f46 0%, #047857 100%);
    color: #d1fae5;
}

@media (max-width: 640px) {
    .share-buttons-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .share-modal-content {
        border-radius: 16px;
    }

    .share-modal-header,
    .share-modal-body {
        padding: 1.5rem;
    }
}

/* Timeline */
.timeline {
    position: relative;
    padding: 2rem 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
    transform: translateX(-50%);
}

.timeline-item {
    margin-bottom: 3rem;
    position: relative;
    width: 45%;
}

.timeline-item:nth-child(even) {
    margin-left: 55%;
}

.timeline-content {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 2px solid var(--primary);
}

.timeline-date {
    font-weight: 800;
    color: var(--primary);
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
}

.timeline-text {
    color: #6b7280;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }

    .hero-subtitle {
        font-size: 1.125rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .timeline::before {
        left: 20px;
    }

    .timeline-item,
    .timeline-item:nth-child(even) {
        width: calc(100% - 60px);
        margin-left: 60px;
    }
}

.dark {
    .feature-card {
        background: #1f2937;
        color: #f9fafb;
    }

    .feature-title {
        color: #f9fafb;
    }

    .timeline-content {
        background: #1f2937;
        color: #f9fafb;
    }
}
</style>
@endpush

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="hero-content text-center">
            <h1 class="hero-title">{{ $appName }}</h1>
            <p class="hero-subtitle">แพลตฟอร์ม MLM & E-Commerce แห่งอนาคต</p>

            <div class="flex justify-center gap-4 mb-8">
                <div class="version-badge">
                    <span class="version-label">Version</span>
                    <span class="font-mono">{{ $stats['version'] }}</span>
                    <span class="text-xs opacity-75">• Production Ready</span>
                </div>
            </div>

            <div class="stats-grid max-w-5xl mx-auto">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['total_users']) }}</div>
                    <div class="stat-label">ผู้ใช้งานทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['total_affiliates']) }}</div>
                    <div class="stat-label">Affiliates</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['database_models'] }}+</div>
                    <div class="stat-label">Database Models</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">฿{{ number_format($stats['total_earnings'], 2) }}</div>
                    <div class="stat-label">ยอดคอมมิชชั่นทั้งหมด</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vision & Mission Section -->
<section class="section bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="section-title">วิสัยทัศน์และพันธกิจ</h2>
        <p class="section-subtitle">มุ่งสู่การเป็นแพลตฟอร์ม MLM อันดับ 1 ของไทย</p>

        <div class="vision-mission-grid">
            <div class="vm-card">
                <div class="vm-icon">🎯</div>
                <h3 class="vm-title">วิสัยทัศน์</h3>
                <p class="vm-content">
                    มุ่งสู่การเป็นแพลตฟอร์ม MLM & E-Commerce อันดับ 1 ของไทยที่ใช้เทคโนโลยี AI
                    และระบบอัตโนมัติขั้นสูง เพื่อสร้างโอกาสทางธุรกิจที่ยุติธรรมและโปร่งใสสำหรับคนไทยทุกคน
                </p>
            </div>

            <div class="vm-card">
                <div class="vm-icon">💡</div>
                <h3 class="vm-title">พันธกิจ</h3>
                <p class="vm-content">
                    นำเทคโนโลยี Enterprise มาให้ SME และผู้ประกอบการรายย่อยใช้งานได้ง่าย
                    พร้อมระบบคำนวณค่าคอมมิชชั่นที่โปร่งใส ตรวจสอบได้ทุกขั้นตอน
                    ด้วยระบบรักษาความปลอดภัยระดับ Banking-grade
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Key Features Section -->
<section class="section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="section-title">คุณสมบัติหลัก</h2>
        <p class="section-subtitle">ฟีเจอร์ครบครัน ตอบโจทย์ทุกการใช้งาน</p>

        <div class="feature-grid">
            <div class="feature-card">
                <span class="feature-icon">🚀</span>
                <h3 class="feature-title">ระบบ MLM ครบวงจร</h3>
                <p class="feature-description">
                    รองรับ Unilevel และ Binary Plan พร้อมระบบคำนวณค่าคอมมิชชั่นอัตโนมัติแบบ Real-time
                    ตรวจสอบได้ทุกขั้นตอน โปร่งใส ยุติธรรม
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🛒</span>
                <h3 class="feature-title">E-Commerce แบบ Multi-Vendor</h3>
                <p class="feature-description">
                    ระบบร้านค้าออนไลน์ที่รองรับผู้ขายหลายราย พร้อมระบบจัดการสินค้า สต็อก
                    และการชำระเงินที่หลากหลาย ทั้ง PromptPay, บัตรเครดิต และ Crypto
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">💰</span>
                <h3 class="feature-title">Digital Wallet & Crypto</h3>
                <p class="feature-description">
                    กระเป๋าเงินดิจิทัลที่รองรับทั้ง THB, Points และ Cryptocurrency 20+ สกุล
                    พร้อมระบบแลกเปลี่ยนและถอนเงินที่รวดเร็วปลอดภัย
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🤖</span>
                <h3 class="feature-title">AI Integration</h3>
                <p class="feature-description">
                    ผสานเทคโนโลยี AI จาก OpenAI, Claude, Gemini และ LINE Official Account
                    ช่วยดูแลลูกค้า วิเคราะห์ข้อมูล และสร้างเนื้อหาอัตโนมัติ 24/7
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">📈</span>
                <h3 class="feature-title">Investment & Staking</h3>
                <p class="feature-description">
                    ระบบลงทุนและ Staking ที่โปร่งใส คำนวณ ROI อัตโนมัติ
                    พร้อมระบบจ่ายผลตอบแทนรายวัน รายสัปดาห์ และรายเดือน
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🔒</span>
                <h3 class="feature-title">ความปลอดภัยระดับสูง</h3>
                <p class="feature-description">
                    ระบบรักษาความปลอดภัยแบบ Banking-grade พร้อม Two-Factor Authentication,
                    KYC/OCR Verification และ Security Logging ครบถ้วน
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🏨</span>
                <h3 class="feature-title">Hotel Booking System</h3>
                <p class="feature-description">
                    ระบบจองโรงแรมออนไลน์ พร้อมบูรณาการกับระบบ MLM
                    สร้างรายได้จากการแนะนำและการจองที่พัก
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🎓</span>
                <h3 class="feature-title">Academy & Learning</h3>
                <p class="feature-description">
                    ระบบเรียนการสอนออนไลน์แบบครบวงจร รองรับ Video Streaming,
                    Progress Tracking และระบบออกใบประกาศนียบัตร
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">👥</span>
                <h3 class="feature-title">HRM System</h3>
                <p class="feature-description">
                    ระบบบริหารจัดการทรัพยากรบุคคล ครอบคลุมทั้งการจัดการพนักงาน
                    ลาออนไลน์ และระบบเงินเดือน
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Platform Mindmap Section -->
<section class="section bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="section-title">ผังความคิดแพลตฟอร์ม</h2>
        <p class="section-subtitle">ภาพรวมระบบและความเชื่อมโยงของฟีเจอร์ต่างๆ</p>

        <div class="mindmap-controls">
            <button class="mindmap-btn" onclick="resetMindmap()">🔄 รีเซ็ต</button>
            <button class="mindmap-btn" onclick="zoomIn()">🔍 ซูมเข้า</button>
            <button class="mindmap-btn" onclick="zoomOut()">🔎 ซูมออก</button>
            <button class="mindmap-btn" onclick="fitNetwork()">📐 ปรับให้พอดี</button>
            <button class="mindmap-btn mindmap-btn-share" onclick="openShareModal()">
                <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                แชร์
            </button>
        </div>

        <div id="mindmap-container"></div>

        <!-- Share Modal -->
        <div id="shareModal" class="share-modal" style="display: none;">
            <div class="share-modal-content">
                <div class="share-modal-header">
                    <h3 class="share-modal-title">
                        <svg class="inline-block w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        แชร์ Mind Map
                    </h3>
                    <button class="share-modal-close" onclick="closeShareModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="share-modal-body">
                    <p class="text-gray-600 dark:text-gray-400 mb-6 text-center">
                        แชร์ผังความคิดระบบแพลตฟอร์มนี้ให้เพื่อนๆ ของคุณ
                    </p>

                    <div class="share-buttons-grid">
                        <!-- Facebook -->
                        <button class="share-button share-facebook" onclick="shareToFacebook()">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Facebook</span>
                        </button>

                        <!-- Twitter/X -->
                        <button class="share-button share-twitter" onclick="shareToTwitter()">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                            <span>Twitter</span>
                        </button>

                        <!-- LINE -->
                        <button class="share-button share-line" onclick="shareToLine()">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                            <span>LINE</span>
                        </button>

                        <!-- Copy Link -->
                        <button class="share-button share-link" onclick="copyShareLink()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span>Copy Link</span>
                        </button>

                        <!-- Download Image -->
                        <button class="share-button share-download" onclick="downloadMindmapImage()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>ดาวน์โหลดรูป</span>
                        </button>

                        <!-- Email -->
                        <button class="share-button share-email" onclick="shareViaEmail()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Email</span>
                        </button>
                    </div>

                    <!-- Share URL Input -->
                    <div class="share-url-container">
                        <input
                            type="text"
                            id="shareUrlInput"
                            readonly
                            value=""
                            class="share-url-input"
                            onclick="this.select()"
                        />
                        <button class="share-url-copy-btn" onclick="copyShareLink()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>

                    <div id="copySuccessMessage" class="copy-success-message" style="display: none;">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        คัดลอกลิงก์สำเร็จ!
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MLM Organization Chart Section -->
<section class="section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="section-title">ผังสายงาน MLM</h2>
        <p class="section-subtitle">โครงสร้างองค์กร Multi-Level Marketing แบบเรียลไทม์</p>

        <!-- Stats Cards -->
        <div class="stats-grid max-w-4xl mx-auto mb-8" style="animation: none;">
            <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-700 border-0">
                <div class="stat-number">{{ number_format($mlmOrgStats['total_members']) }}</div>
                <div class="stat-label">สมาชิกทั้งหมด</div>
            </div>
            <div class="stat-card bg-gradient-to-br from-green-500 to-green-700 border-0">
                <div class="stat-number">{{ number_format($mlmOrgStats['active_members']) }}</div>
                <div class="stat-label">สมาชิกที่ Active</div>
            </div>
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-700 border-0">
                <div class="stat-number">{{ $mlmOrgStats['total_levels'] }}</div>
                <div class="stat-label">จำนวนระดับ</div>
            </div>
            <div class="stat-card bg-gradient-to-br from-orange-500 to-orange-700 border-0">
                <div class="stat-number">+{{ number_format($mlmOrgStats['monthly_growth']) }}</div>
                <div class="stat-label">เติบโตเดือนนี้</div>
            </div>
        </div>

        <div class="mindmap-controls">
            <button class="mindmap-btn" onclick="resetMlmOrg()">🔄 รีเซ็ต</button>
            <button class="mindmap-btn" onclick="zoomInMlmOrg()">🔍 ซูมเข้า</button>
            <button class="mindmap-btn" onclick="zoomOutMlmOrg()">🔎 ซูมออก</button>
            <button class="mindmap-btn" onclick="fitMlmOrg()">📐 ปรับให้พอดี</button>
            <button class="mindmap-btn" onclick="toggleMlmOrgLayout()">🔀 เปลี่ยนมุมมอง</button>
        </div>

        <div id="mlm-org-container" style="width: 100%; height: 700px; border: 2px solid #e5e7eb; border-radius: 20px; background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.08);"></div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                💡 <strong>คำแนะนำ:</strong> คลิกที่โหนดเพื่อดูรายละเอียด | ลากเพื่อเลื่อนดู | ใช้ล้อเมาส์เพื่อซูม
            </p>
        </div>
    </div>
</section>

<!-- Timeline Section -->
<section class="section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="section-title">ประวัติความเป็นมา</h2>
        <p class="section-subtitle">เส้นทางการพัฒนาแพลตฟอร์ม</p>

        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q1 2024 - เริ่มต้นโครงการ</div>
                    <p class="timeline-text">
                        ทีมพัฒนาเริ่มวิจัยและออกแบบระบบ MLM ที่ตอบโจทย์คนไทย
                        วิเคราะห์ปัญหาและความต้องการของตลาด
                    </p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q2 2024 - พัฒนา Core System</div>
                    <p class="timeline-text">
                        สร้าง Laravel 11 foundation พัฒนาระบบ MLM Dual Plan
                        และระบบ E-Commerce พื้นฐาน
                    </p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q3 2024 - AI Integration</div>
                    <p class="timeline-text">
                        ผสาน OpenAI, Claude, Gemini และ LINE Official Account
                        เพิ่มความสามารถด้าน AI และ Automation
                    </p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-date">Q4 2024 - Production Ready</div>
                    <p class="timeline-text">
                        Version {{ $stats['version'] }} พร้อมใช้งานจริง
                        พร้อม {{ $stats['database_models'] }}+ Models และ {{ $stats['database_tables'] }} Tables
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technology Stack Section -->
<section class="section bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="section-title">เทคโนโลยีที่ใช้</h2>
        <p class="section-subtitle">สร้างด้วยเทคโนโลยีที่ทันสมัยและเสถียร</p>

        <div class="feature-grid">
            <div class="feature-card">
                <span class="feature-icon">⚡</span>
                <h3 class="feature-title">Laravel 11</h3>
                <p class="feature-description">
                    PHP Framework ที่ทันสมัยและมีประสิทธิภาพสูง
                    พร้อมระบบ Security และ Performance ที่ดีเยี่ยม
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🎨</span>
                <h3 class="feature-title">Tailwind CSS</h3>
                <p class="feature-description">
                    Utility-first CSS Framework ที่ยืดหยุ่นและปรับแต่งได้ง่าย
                    สร้าง UI ที่สวยงามและทันสมัย
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">💾</span>
                <h3 class="feature-title">MySQL Database</h3>
                <p class="feature-description">
                    ฐานข้อมูลที่เสถียรและรวดเร็ว {{ $stats['database_tables'] }} Tables
                    ออกแบบอย่างมีประสิทธิภาพ
                </p>
            </div>

            <div class="feature-card">
                <span class="feature-icon">🔗</span>
                <h3 class="feature-title">Web3 Integration</h3>
                <p class="feature-description">
                    รองรับ Blockchain และ Cryptocurrency พร้อม Smart Contract
                    และ MetaMask Wallet Integration
                </p>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
<script>
let network;
let nodes;
let edges;

document.addEventListener('DOMContentLoaded', function() {
    initMindmap();
});

function initMindmap() {
    // Define nodes with Wiki links
    nodes = new vis.DataSet([
        // Center
        {
            id: 1,
            label: 'Thaiprompt\nAffiliate',
            level: 0,
            color: { background: '#3B82F6', border: '#1E40AF' },
            font: { color: '#FFFFFF', size: 20, bold: true },
            wikiUrl: '/wiki',
            title: 'คลิกเพื่อดูเอกสารทั้งหมด'
        },

        // 🔗 Blockchain & TPIX - Level 1 (NEW!)
        {
            id: 2,
            label: '🔗 Blockchain\n& TPIX',
            level: 1,
            color: { background: '#7C3AED', border: '#5B21B6' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/tpix-blockchain/README.md',
            title: 'Native Blockchain & Token Ecosystem\nคลิกเพื่ออ่านเอกสาร'
        },

        // 🌱 Food Passport - Level 1 (NEW!)
        {
            id: 3,
            label: '🌱 Food\nPassport',
            level: 1,
            color: { background: '#059669', border: '#047857' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/FOOD_PASSPORT_README.md',
            title: 'Food Traceability & Carbon Credit\nคลิกเพื่ออ่านเอกสาร'
        },

        // 🎮 Games - Level 1 (NEW!)
        {
            id: 4,
            label: '🎮 Games &\nEntertainment',
            level: 1,
            color: { background: '#DC2626', border: '#991B1B' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/DEMO_GAMES.md',
            title: 'Tetris, Space Shooter, 3D Navigation\nคลิกเพื่ออ่านเอกสาร'
        },

        // 💎 MLM System - Level 1
        {
            id: 5,
            label: '💎 MLM\nSystem',
            level: 1,
            color: { background: '#8B5CF6', border: '#6D28D9' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MLM_SYSTEM_DOCUMENTATION.md',
            title: 'Multi-Level Marketing System\nคลิกเพื่ออ่านเอกสาร'
        },

        // 🛒 E-Commerce - Level 1
        {
            id: 6,
            label: '🛒 E-Commerce\nMulti-Vendor',
            level: 1,
            color: { background: '#EC4899', border: '#BE185D' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MULTIVENDOR_DESIGN.md',
            title: 'Multi-Vendor Marketplace\nคลิกเพื่ออ่านเอกสาร'
        },

        // 💰 Wallet - Level 1
        {
            id: 7,
            label: '💰 Digital\nWallet',
            level: 1,
            color: { background: '#10B981', border: '#047857' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/WALLET_SYSTEM.md',
            title: 'Digital Wallet & Crypto Exchange\nคลิกเพื่ออ่านเอกสาร'
        },

        // 🤖 AI - Level 1
        {
            id: 8,
            label: '🤖 AI\nIntegration',
            level: 1,
            color: { background: '#F59E0B', border: '#D97706' },
            font: { color: '#FFFFFF', size: 16 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/LINE_BOT_AI_IMPLEMENTATION.md',
            title: 'AI Chatbot & Automation\nคลิกเพื่ออ่านเอกสาร'
        },

        // 📈 Investment - Level 1
        {
            id: 9,
            label: '📈 Investment\n& Staking',
            level: 1,
            color: { background: '#6366F1', border: '#4338CA' },
            font: { color: '#FFFFFF', size: 14 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/TPIX_TOKEN_SYSTEM.md#-staking-pools',
            title: 'Investment & Staking Pools\nคลิกเพื่ออ่านเอกสาร'
        },

        // 🏨 Hotel - Level 1
        {
            id: 10,
            label: '🏨 Hotel\nBooking',
            level: 1,
            color: { background: '#EF4444', border: '#DC2626' },
            font: { color: '#FFFFFF', size: 14 },
            wikiUrl: '/wiki',
            title: 'Hotel Booking System\nคลิกเพื่ออ่านเอกสาร'
        },

        // 🎓 Academy - Level 1
        {
            id: 11,
            label: '🎓 Academy\n& Learning',
            level: 1,
            color: { background: '#14B8A6', border: '#0D9488' },
            font: { color: '#FFFFFF', size: 14 },
            wikiUrl: '/wiki',
            title: 'Online Learning Platform\nคลิกเพื่ออ่านเอกสาร'
        },

        // 👥 HRM - Level 1
        {
            id: 12,
            label: '👥 HRM\nSystem',
            level: 1,
            color: { background: '#F97316', border: '#EA580C' },
            font: { color: '#FFFFFF', size: 14 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/HRM_SYSTEM_README.md',
            title: 'Human Resource Management\nคลิกเพื่ออ่านเอกสาร'
        },

        // ===== BLOCKCHAIN SUB-FEATURES (Level 2) =====
        {
            id: 20,
            label: 'TPIX Coin\n(Native)',
            level: 2,
            color: { background: '#A78BFA', border: '#7C3AED' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/tpix-blockchain/README.md#-specifications',
            title: '7B Total Supply | 2s Block Time'
        },
        {
            id: 21,
            label: 'Smart\nContracts',
            level: 2,
            color: { background: '#A78BFA', border: '#7C3AED' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/tpix-blockchain/docs/SMART_CONTRACTS.md',
            title: 'EVM-Compatible | Solidity ^0.8.20'
        },
        {
            id: 22,
            label: 'Token\nFactory',
            level: 2,
            color: { background: '#A78BFA', border: '#7C3AED' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/TPIX_TOKEN_SYSTEM.md#1-token-management',
            title: 'Create & Deploy Custom Tokens'
        },
        {
            id: 23,
            label: 'Block\nExplorer',
            level: 2,
            color: { background: '#A78BFA', border: '#7C3AED' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/tpix-blockchain/README.md#access-services',
            title: 'Blockscout Explorer'
        },

        // ===== FOOD PASSPORT SUB-FEATURES (Level 2) =====
        {
            id: 30,
            label: 'Farm to Fork\nTraceability',
            level: 2,
            color: { background: '#34D399', border: '#059669' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/FOOD_PASSPORT_README.md#1--traceability-system',
            title: 'Track Food Journey'
        },
        {
            id: 31,
            label: 'Quality\nControl',
            level: 2,
            color: { background: '#34D399', border: '#059669' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/FOOD_PASSPORT_README.md#2--quality-control',
            title: 'Quality Checkpoints & Certifications'
        },
        {
            id: 32,
            label: 'Carbon\nFootprint',
            level: 2,
            color: { background: '#34D399', border: '#059669' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/FOOD_PASSPORT_README.md#3--carbon-footprint-tracking',
            title: 'Calculate & Track Carbon Emissions'
        },
        {
            id: 33,
            label: 'Carbon\nCredit',
            level: 2,
            color: { background: '#34D399', border: '#059669' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/FOOD_PASSPORT_README.md#4--carbon-credit-system',
            title: 'Earn & Trade Carbon Credits'
        },

        // ===== GAMES SUB-FEATURES (Level 2) =====
        {
            id: 40,
            label: 'Tetris\nGame',
            level: 2,
            color: { background: '#F87171', border: '#DC2626' },
            font: { size: 12 },
            wikiUrl: '/demo/tetris',
            title: 'Play Tetris with Top Scores'
        },
        {
            id: 41,
            label: 'Space\nShooter',
            level: 2,
            color: { background: '#F87171', border: '#DC2626' },
            font: { size: 12 },
            wikiUrl: '/demo/space-shooter',
            title: '3D Space Shooting Game'
        },
        {
            id: 42,
            label: '3D\nNavigation',
            level: 2,
            color: { background: '#F87171', border: '#DC2626' },
            font: { size: 12 },
            wikiUrl: '/demo/3d-navigation',
            title: 'Interactive 3D Environment'
        },
        {
            id: 43,
            label: 'Tarot\n3D WebGL',
            level: 2,
            color: { background: '#F87171', border: '#DC2626' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/TAROT_3D_WEBGL_SYSTEM.md',
            title: '3D Tarot Card System'
        },

        // ===== MLM SUB-FEATURES (Level 2) =====
        {
            id: 50,
            label: 'Unilevel\nPlan',
            level: 2,
            color: { background: '#C4B5FD', border: '#8B5CF6' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MLM_SYSTEM_DOCUMENTATION.md#unilevel-plan',
            title: 'Multi-Level Commission Structure'
        },
        {
            id: 51,
            label: 'Binary\nPlan',
            level: 2,
            color: { background: '#C4B5FD', border: '#8B5CF6' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MLM_SYSTEM_DOCUMENTATION.md#binary-plan',
            title: 'Binary Tree Matching Bonus'
        },
        {
            id: 52,
            label: 'Commission\nEngine',
            level: 2,
            color: { background: '#C4B5FD', border: '#8B5CF6' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MLM_SYSTEM_DOCUMENTATION.md#commission-calculation',
            title: 'Real-time Commission Calculation'
        },

        // ===== E-COMMERCE SUB-FEATURES (Level 2) =====
        {
            id: 60,
            label: 'Multi-Vendor\nMarketplace',
            level: 2,
            color: { background: '#F9A8D4', border: '#EC4899' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/MULTIVENDOR_SETUP.md',
            title: 'Multiple Sellers Platform'
        },
        {
            id: 61,
            label: 'Product\nManagement',
            level: 2,
            color: { background: '#F9A8D4', border: '#EC4899' },
            font: { size: 12 },
            wikiUrl: '/wiki',
            title: 'Inventory & Catalog Management'
        },
        {
            id: 62,
            label: 'Payment\nGateway',
            level: 2,
            color: { background: '#F9A8D4', border: '#EC4899' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/CRYPTO_GATEWAY_README.md',
            title: 'PromptPay, Credit Card, Crypto'
        },

        // ===== WALLET SUB-FEATURES (Level 2) =====
        {
            id: 70,
            label: 'THB\nWallet',
            level: 2,
            color: { background: '#6EE7B7', border: '#10B981' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/WALLET_SYSTEM.md#thb-wallet',
            title: 'Thai Baht Digital Wallet'
        },
        {
            id: 71,
            label: 'Crypto\nExchange',
            level: 2,
            color: { background: '#6EE7B7', border: '#10B981' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/CRYPTO_GATEWAY_README.md',
            title: '20+ Cryptocurrencies Supported'
        },
        {
            id: 72,
            label: 'Withdrawal\nSystem',
            level: 2,
            color: { background: '#6EE7B7', border: '#10B981' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/WALLET_SYSTEM.md#withdrawal-system',
            title: 'Fast & Secure Withdrawals'
        },

        // ===== AI SUB-FEATURES (Level 2) =====
        {
            id: 80,
            label: 'OpenAI\nGPT-4',
            level: 2,
            color: { background: '#FCD34D', border: '#F59E0B' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/AI_GEN_SYSTEM.md',
            title: 'GPT-4 Integration'
        },
        {
            id: 81,
            label: 'Claude\nAI',
            level: 2,
            color: { background: '#FCD34D', border: '#F59E0B' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/docs/AI_GEN_SYSTEM.md',
            title: 'Claude AI Integration'
        },
        {
            id: 82,
            label: 'LINE\nBot AI',
            level: 2,
            color: { background: '#FCD34D', border: '#F59E0B' },
            font: { size: 12 },
            wikiUrl: 'https://github.com/xjanova/Thaiprompt-Affiliate/blob/main/LINE_BOT_AI_IMPLEMENTATION.md',
            title: 'AI-Powered LINE Official Account'
        },
    ]);

    // Define edges (connections between nodes)
    edges = new vis.DataSet([
        // Main Level 1 connections from center
        { from: 1, to: 2, width: 3, color: { color: '#7C3AED' } },  // Blockchain
        { from: 1, to: 3, width: 3, color: { color: '#059669' } },  // Food Passport
        { from: 1, to: 4, width: 3, color: { color: '#DC2626' } },  // Games
        { from: 1, to: 5, width: 3, color: { color: '#8B5CF6' } },  // MLM
        { from: 1, to: 6, width: 3, color: { color: '#EC4899' } },  // E-Commerce
        { from: 1, to: 7, width: 3, color: { color: '#10B981' } },  // Wallet
        { from: 1, to: 8, width: 3, color: { color: '#F59E0B' } },  // AI
        { from: 1, to: 9, width: 2, color: { color: '#6366F1' } },  // Investment
        { from: 1, to: 10, width: 2, color: { color: '#EF4444' } }, // Hotel
        { from: 1, to: 11, width: 2, color: { color: '#14B8A6' } }, // Academy
        { from: 1, to: 12, width: 2, color: { color: '#F97316' } }, // HRM

        // Blockchain sub-features
        { from: 2, to: 20, width: 2, color: { color: '#A78BFA' } }, // TPIX Coin
        { from: 2, to: 21, width: 2, color: { color: '#A78BFA' } }, // Smart Contracts
        { from: 2, to: 22, width: 2, color: { color: '#A78BFA' } }, // Token Factory
        { from: 2, to: 23, width: 2, color: { color: '#A78BFA' } }, // Block Explorer

        // Food Passport sub-features
        { from: 3, to: 30, width: 2, color: { color: '#34D399' } }, // Traceability
        { from: 3, to: 31, width: 2, color: { color: '#34D399' } }, // Quality Control
        { from: 3, to: 32, width: 2, color: { color: '#34D399' } }, // Carbon Footprint
        { from: 3, to: 33, width: 2, color: { color: '#34D399' } }, // Carbon Credit

        // Games sub-features
        { from: 4, to: 40, width: 2, color: { color: '#F87171' } }, // Tetris
        { from: 4, to: 41, width: 2, color: { color: '#F87171' } }, // Space Shooter
        { from: 4, to: 42, width: 2, color: { color: '#F87171' } }, // 3D Navigation
        { from: 4, to: 43, width: 2, color: { color: '#F87171' } }, // Tarot 3D

        // MLM sub-features
        { from: 5, to: 50, width: 2, color: { color: '#C4B5FD' } }, // Unilevel
        { from: 5, to: 51, width: 2, color: { color: '#C4B5FD' } }, // Binary
        { from: 5, to: 52, width: 2, color: { color: '#C4B5FD' } }, // Commission

        // E-Commerce sub-features
        { from: 6, to: 60, width: 2, color: { color: '#F9A8D4' } }, // Multi-Vendor
        { from: 6, to: 61, width: 2, color: { color: '#F9A8D4' } }, // Product Mgmt
        { from: 6, to: 62, width: 2, color: { color: '#F9A8D4' } }, // Payment

        // Wallet sub-features
        { from: 7, to: 70, width: 2, color: { color: '#6EE7B7' } }, // THB Wallet
        { from: 7, to: 71, width: 2, color: { color: '#6EE7B7' } }, // Crypto Exchange
        { from: 7, to: 72, width: 2, color: { color: '#6EE7B7' } }, // Withdrawal

        // AI sub-features
        { from: 8, to: 80, width: 2, color: { color: '#FCD34D' } }, // OpenAI
        { from: 8, to: 81, width: 2, color: { color: '#FCD34D' } }, // Claude
        { from: 8, to: 82, width: 2, color: { color: '#FCD34D' } }, // LINE Bot
    ]);

    // Create network
    const container = document.getElementById('mindmap-container');
    const data = { nodes: nodes, edges: edges };
    const options = {
        layout: {
            hierarchical: {
                enabled: false
            }
        },
        physics: {
            enabled: true,
            barnesHut: {
                gravitationalConstant: -3000,
                centralGravity: 0.3,
                springLength: 150,
                springConstant: 0.04,
                damping: 0.09,
                avoidOverlap: 0.5
            },
            stabilization: {
                iterations: 200
            }
        },
        nodes: {
            shape: 'box',
            margin: 10,
            widthConstraint: {
                maximum: 150
            },
            font: {
                face: 'Arial',
                multi: true,
                bold: {
                    size: 14
                }
            },
            borderWidth: 2,
            shadow: true
        },
        edges: {
            smooth: {
                type: 'continuous',
                roundness: 0.5
            },
            shadow: true
        },
        interaction: {
            hover: true,
            tooltipDelay: 100,
            zoomView: true,
            dragView: true
        }
    };

    network = new vis.Network(container, data, options);

    // Add click event to open Wiki/Documentation
    network.on('click', function(params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = nodes.get(nodeId);

            if (node && node.wikiUrl) {
                // Open Wiki/Documentation in new tab
                window.open(node.wikiUrl, '_blank');
            }
        }
    });

    // Add hover effect to show cursor pointer
    network.on('hoverNode', function() {
        document.getElementById('mindmap-container').style.cursor = 'pointer';
    });

    network.on('blurNode', function() {
        document.getElementById('mindmap-container').style.cursor = 'default';
    });

    // Stabilize and then fit
    network.once('stabilizationIterationsDone', function() {
        network.fit({
            animation: {
                duration: 1000,
                easingFunction: 'easeInOutQuad'
            }
        });
    });
}

function resetMindmap() {
    network.fit({
        animation: {
            duration: 500,
            easingFunction: 'easeInOutQuad'
        }
    });
}

function zoomIn() {
    const scale = network.getScale() * 1.2;
    network.moveTo({ scale: scale, animation: { duration: 300 } });
}

function zoomOut() {
    const scale = network.getScale() * 0.8;
    network.moveTo({ scale: scale, animation: { duration: 300 } });
}

function fitNetwork() {
    network.fit({
        animation: {
            duration: 500,
            easingFunction: 'easeInOutQuad'
        }
    });
}

// ============================================
// Share Functionality
// ============================================

// Get current page URL
function getShareUrl() {
    return window.location.origin + window.location.pathname + '#mindmap';
}

// Get share text
function getShareText() {
    return 'ดูผังความคิดระบบแพลตฟอร์ม Thaiprompt Affiliate - ครอบคลุม Blockchain, TPIX, Food Passport, Games และอีกมากมาย!';
}

// Open Share Modal
function openShareModal() {
    const modal = document.getElementById('shareModal');
    const urlInput = document.getElementById('shareUrlInput');

    modal.style.display = 'flex';
    urlInput.value = getShareUrl();

    // Add escape key handler
    document.addEventListener('keydown', closeShareModalOnEscape);
}

// Close Share Modal
function closeShareModal() {
    const modal = document.getElementById('shareModal');
    modal.style.display = 'none';

    // Hide success message
    document.getElementById('copySuccessMessage').style.display = 'none';

    // Remove escape key handler
    document.removeEventListener('keydown', closeShareModalOnEscape);
}

// Close modal on Escape key
function closeShareModalOnEscape(e) {
    if (e.key === 'Escape') {
        closeShareModal();
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('shareModal');
    if (e.target === modal) {
        closeShareModal();
    }
});

// Share to Facebook
function shareToFacebook() {
    const url = encodeURIComponent(getShareUrl());
    const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

// Share to Twitter
function shareToTwitter() {
    const url = encodeURIComponent(getShareUrl());
    const text = encodeURIComponent(getShareText());
    const shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

// Share to LINE
function shareToLine() {
    const url = encodeURIComponent(getShareUrl());
    const text = encodeURIComponent(getShareText());
    const shareUrl = `https://social-plugins.line.me/lineit/share?url=${url}&text=${text}`;
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

// Copy Link to Clipboard
function copyShareLink() {
    const urlInput = document.getElementById('shareUrlInput');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices

    try {
        document.execCommand('copy');
        showCopySuccess();
    } catch (err) {
        // Fallback for modern browsers
        navigator.clipboard.writeText(getShareUrl()).then(function() {
            showCopySuccess();
        }).catch(function(err) {
            alert('ไม่สามารถคัดลอกลิงก์ได้: ' + err);
        });
    }
}

// Show copy success message
function showCopySuccess() {
    const successMsg = document.getElementById('copySuccessMessage');
    successMsg.style.display = 'flex';

    setTimeout(function() {
        successMsg.style.display = 'none';
    }, 3000);
}

// Share via Email
function shareViaEmail() {
    const subject = encodeURIComponent('ผังความคิดระบบ Thaiprompt Affiliate');
    const body = encodeURIComponent(getShareText() + '\n\n' + getShareUrl());
    const mailtoUrl = `mailto:?subject=${subject}&body=${body}`;
    window.location.href = mailtoUrl;
}

// Download Mind map as Image
function downloadMindmapImage() {
    const container = document.getElementById('mindmap-container');
    const canvas = container.querySelector('canvas');

    if (canvas) {
        try {
            // Create a link element
            const link = document.createElement('a');
            link.download = 'thaiprompt-affiliate-mindmap.png';

            // Convert canvas to blob and download
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                link.href = url;
                link.click();
                URL.revokeObjectURL(url);
            });

            // Show success message
            showDownloadSuccess();
        } catch (err) {
            alert('ไม่สามารถดาวน์โหลดรูปภาพได้: ' + err.message);
        }
    } else {
        alert('ไม่พบ Mind map กรุณารอให้โหลดเสร็จก่อน');
    }
}

// Show download success message
function showDownloadSuccess() {
    const successMsg = document.getElementById('copySuccessMessage');
    const originalText = successMsg.innerHTML;

    successMsg.innerHTML = `
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        ดาวน์โหลดรูปภาพสำเร็จ!
    `;
    successMsg.style.display = 'flex';

    setTimeout(function() {
        successMsg.style.display = 'none';
        successMsg.innerHTML = originalText;
    }, 3000);
}

// ============================================
// MLM Organization Chart Visualization
// ============================================
let mlmOrgNetwork;
let mlmOrgNodes;
let mlmOrgEdges;
let mlmOrgLayoutMode = 'hierarchical'; // 'hierarchical' or 'physics'

// Initialize MLM Organization Chart when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initMlmOrgChart();
});

function initMlmOrgChart() {
    // Sample MLM organization data (in production, this would come from API)
    // Creating a realistic MLM tree structure
    mlmOrgNodes = new vis.DataSet([
        // Level 0 - Root/Founder
        {
            id: 1,
            label: 'CEO/Founder\nรหัส: ML001',
            level: 0,
            color: { background: '#7C3AED', border: '#5B21B6' },
            font: { color: '#FFFFFF', size: 14, bold: true },
            title: 'CEO/Founder<br>รหัส: ML001<br>PV: 25,000<br>สมาชิกในสาย: 150<br>สถานะ: Diamond',
            shape: 'box',
            margin: 12
        },

        // Level 1 - Direct Team (3 members)
        {
            id: 2,
            label: 'ผู้จัดการเขต ภาคกลาง\nรหัส: ML002',
            level: 1,
            color: { background: '#8B5CF6', border: '#7C3AED' },
            font: { color: '#FFFFFF', size: 12 },
            title: 'ผู้จัดการเขต ภาคกลาง<br>รหัส: ML002<br>PV: 12,500<br>สมาชิกในสาย: 65<br>สถานะ: Platinum',
            shape: 'box'
        },
        {
            id: 3,
            label: 'ผู้จัดการเขต ภาคเหนือ\nรหัส: ML003',
            level: 1,
            color: { background: '#8B5CF6', border: '#7C3AED' },
            font: { color: '#FFFFFF', size: 12 },
            title: 'ผู้จัดการเขต ภาคเหนือ<br>รหัส: ML003<br>PV: 8,900<br>สมาชิกในสาย: 45<br>สถานะ: Gold',
            shape: 'box'
        },
        {
            id: 4,
            label: 'ผู้จัดการเขต ภาคใต้\nรหัส: ML004',
            level: 1,
            color: { background: '#8B5CF6', border: '#7C3AED' },
            font: { color: '#FFFFFF', size: 12 },
            title: 'ผู้จัดการเขต ภาคใต้<br>รหัส: ML004<br>PV: 10,200<br>สมาชิกในสาย: 40<br>สถานะ: Gold',
            shape: 'box'
        },

        // Level 2 - Team Leaders under ML002 (3 members)
        {
            id: 5,
            label: 'ผู้นำทีม กรุงเทพ A\nรหัส: ML005',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม กรุงเทพ A<br>รหัส: ML005<br>PV: 4,500<br>สมาชิกในสาย: 22<br>สถานะ: Silver',
            shape: 'box'
        },
        {
            id: 6,
            label: 'ผู้นำทีม กรุงเทพ B\nรหัส: ML006',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม กรุงเทพ B<br>รหัส: ML006<br>PV: 3,800<br>สมาชิกในสาย: 18<br>สถานะ: Silver',
            shape: 'box'
        },
        {
            id: 7,
            label: 'ผู้นำทีม ปริมณฑล\nรหัส: ML007',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม ปริมณฑล<br>รหัส: ML007<br>PV: 4,200<br>สมาชิกในสาย: 25<br>สถานะ: Silver',
            shape: 'box'
        },

        // Level 2 - Team Leaders under ML003 (2 members)
        {
            id: 8,
            label: 'ผู้นำทีม เชียงใหม่\nรหัส: ML008',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม เชียงใหม่<br>รหัส: ML008<br>PV: 4,900<br>สมาชิกในสาย: 23<br>สถานะ: Silver',
            shape: 'box'
        },
        {
            id: 9,
            label: 'ผู้นำทีม เชียงราย\nรหัส: ML009',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม เชียงราย<br>รหัส: ML009<br>PV: 4,000<br>สมาชิกในสาย: 22<br>สถานะ: Bronze',
            shape: 'box'
        },

        // Level 2 - Team Leaders under ML004 (2 members)
        {
            id: 10,
            label: 'ผู้นำทีม ภูเก็ต\nรหัส: ML010',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม ภูเก็ต<br>รหัส: ML010<br>PV: 5,200<br>สมาชิกในสาย: 20<br>สถานะ: Silver',
            shape: 'box'
        },
        {
            id: 11,
            label: 'ผู้นำทีม สงขลา\nรหัส: ML011',
            level: 2,
            color: { background: '#A78BFA', border: '#8B5CF6' },
            font: { size: 11 },
            title: 'ผู้นำทีม สงขลา<br>รหัส: ML011<br>PV: 5,000<br>สมาชิกในสาย: 20<br>สถานะ: Silver',
            shape: 'box'
        },

        // Level 3 - Active Members (distributed under team leaders)
        { id: 12, label: 'สมาชิก ML012\nPV: 850', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 13, label: 'สมาชิก ML013\nPV: 720', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 14, label: 'สมาชิก ML014\nPV: 920', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 15, label: 'สมาชิก ML015\nPV: 680', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 16, label: 'สมาชิก ML016\nPV: 790', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 17, label: 'สมาชิก ML017\nPV: 850', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 18, label: 'สมาชิก ML018\nPV: 640', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 19, label: 'สมาชิก ML019\nPV: 910', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 20, label: 'สมาชิก ML020\nPV: 770', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 21, label: 'สมาชิก ML021\nPV: 830', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 22, label: 'สมาชิก ML022\nPV: 700', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 23, label: 'สมาชิก ML023\nPV: 890', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 24, label: 'สมาชิก ML024\nPV: 750', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
        { id: 25, label: 'สมาชิก ML025\nPV: 820', level: 3, color: { background: '#C4B5FD', border: '#A78BFA' }, font: { size: 10 }, shape: 'box' },
    ]);

    // Define edges (connections)
    mlmOrgEdges = new vis.DataSet([
        // Level 0 to Level 1
        { from: 1, to: 2, width: 3, color: { color: '#8B5CF6' }, arrows: 'to' },
        { from: 1, to: 3, width: 3, color: { color: '#8B5CF6' }, arrows: 'to' },
        { from: 1, to: 4, width: 3, color: { color: '#8B5CF6' }, arrows: 'to' },

        // Level 1 to Level 2
        { from: 2, to: 5, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },
        { from: 2, to: 6, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },
        { from: 2, to: 7, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },
        { from: 3, to: 8, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },
        { from: 3, to: 9, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },
        { from: 4, to: 10, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },
        { from: 4, to: 11, width: 2, color: { color: '#A78BFA' }, arrows: 'to' },

        // Level 2 to Level 3 (distributed members)
        { from: 5, to: 12, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 5, to: 13, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 6, to: 14, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 6, to: 15, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 7, to: 16, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 7, to: 17, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 8, to: 18, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 8, to: 19, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 9, to: 20, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 9, to: 21, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 10, to: 22, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 10, to: 23, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 11, to: 24, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
        { from: 11, to: 25, width: 1, color: { color: '#C4B5FD' }, arrows: 'to' },
    ]);

    // Create network
    const container = document.getElementById('mlm-org-container');
    const data = { nodes: mlmOrgNodes, edges: mlmOrgEdges };
    const options = {
        layout: {
            hierarchical: {
                enabled: true,
                direction: 'UD', // Up-Down
                sortMethod: 'directed',
                levelSeparation: 150,
                nodeSpacing: 150,
                treeSpacing: 200
            }
        },
        physics: {
            enabled: false, // Disable physics for hierarchical layout
        },
        nodes: {
            shape: 'box',
            margin: 10,
            widthConstraint: {
                minimum: 120,
                maximum: 180
            },
            font: {
                face: 'Arial',
                multi: true,
                bold: {
                    size: 12
                }
            },
            borderWidth: 2,
            shadow: {
                enabled: true,
                color: 'rgba(0,0,0,0.2)',
                size: 8,
                x: 3,
                y: 3
            }
        },
        edges: {
            smooth: {
                type: 'cubicBezier',
                forceDirection: 'vertical',
                roundness: 0.4
            },
            shadow: true,
            arrows: {
                to: {
                    enabled: true,
                    scaleFactor: 0.8
                }
            }
        },
        interaction: {
            hover: true,
            tooltipDelay: 100,
            zoomView: true,
            dragView: true,
            navigationButtons: true
        }
    };

    mlmOrgNetwork = new vis.Network(container, data, options);

    // Add click event for nodes
    mlmOrgNetwork.on('click', function(params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = mlmOrgNodes.get(nodeId);

            // Show node details in alert (in production, use modal)
            let details = `<div style="text-align: left;">
                <h3 style="margin-bottom: 10px; color: #7C3AED;">${node.label.replace('\n', ' ')}</h3>
                <p><strong>ระดับ:</strong> ${node.level}</p>
            `;

            if (node.title) {
                details += `<p>${node.title.replace(/<br>/g, '\n')}</p>`;
            }

            details += `</div>`;

            // Simple alert for now (replace with modal in production)
            alert(node.title ? node.title.replace(/<br>/g, '\n') : node.label);
        }
    });

    // Fit network after initialization
    setTimeout(() => {
        mlmOrgNetwork.fit({
            animation: {
                duration: 1000,
                easingFunction: 'easeInOutQuad'
            }
        });
    }, 500);
}

function resetMlmOrg() {
    mlmOrgNetwork.fit({
        animation: {
            duration: 500,
            easingFunction: 'easeInOutQuad'
        }
    });
}

function zoomInMlmOrg() {
    const scale = mlmOrgNetwork.getScale() * 1.2;
    mlmOrgNetwork.moveTo({ scale: scale, animation: { duration: 300 } });
}

function zoomOutMlmOrg() {
    const scale = mlmOrgNetwork.getScale() * 0.8;
    mlmOrgNetwork.moveTo({ scale: scale, animation: { duration: 300 } });
}

function fitMlmOrg() {
    mlmOrgNetwork.fit({
        animation: {
            duration: 500,
            easingFunction: 'easeInOutQuad'
        }
    });
}

function toggleMlmOrgLayout() {
    if (mlmOrgLayoutMode === 'hierarchical') {
        // Switch to physics-based layout
        mlmOrgLayoutMode = 'physics';
        mlmOrgNetwork.setOptions({
            layout: {
                hierarchical: {
                    enabled: false
                }
            },
            physics: {
                enabled: true,
                barnesHut: {
                    gravitationalConstant: -2000,
                    centralGravity: 0.3,
                    springLength: 150,
                    springConstant: 0.04,
                    damping: 0.09,
                    avoidOverlap: 0.5
                },
                stabilization: {
                    iterations: 200
                }
            }
        });

        // Stabilize and fit
        mlmOrgNetwork.once('stabilizationIterationsDone', function() {
            mlmOrgNetwork.fit({
                animation: {
                    duration: 1000,
                    easingFunction: 'easeInOutQuad'
                }
            });
        });

        // Stop physics after stabilization
        setTimeout(() => {
            mlmOrgNetwork.setOptions({ physics: { enabled: false } });
        }, 3000);

    } else {
        // Switch back to hierarchical layout
        mlmOrgLayoutMode = 'hierarchical';
        mlmOrgNetwork.setOptions({
            layout: {
                hierarchical: {
                    enabled: true,
                    direction: 'UD',
                    sortMethod: 'directed',
                    levelSeparation: 150,
                    nodeSpacing: 150,
                    treeSpacing: 200
                }
            },
            physics: {
                enabled: false
            }
        });

        setTimeout(() => {
            mlmOrgNetwork.fit({
                animation: {
                    duration: 1000,
                    easingFunction: 'easeInOutQuad'
                }
            });
        }, 500);
    }
}
</script>
@endpush
@endsection

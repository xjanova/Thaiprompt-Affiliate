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
}

.mindmap-btn:hover {
    background: var(--primary);
    color: white;
    transform: scale(1.05);
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
        </div>

        <div id="mindmap-container"></div>
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
    // Define nodes
    nodes = new vis.DataSet([
        // Center
        { id: 1, label: 'Thaiprompt\nAffiliate', level: 0, color: { background: '#3B82F6', border: '#1E40AF' }, font: { color: '#FFFFFF', size: 20, bold: true } },

        // Main Features - Level 1
        { id: 2, label: 'MLM System', level: 1, color: { background: '#8B5CF6', border: '#6D28D9' }, font: { color: '#FFFFFF', size: 16 } },
        { id: 3, label: 'E-Commerce', level: 1, color: { background: '#EC4899', border: '#BE185D' }, font: { color: '#FFFFFF', size: 16 } },
        { id: 4, label: 'Wallet & Crypto', level: 1, color: { background: '#10B981', border: '#047857' }, font: { color: '#FFFFFF', size: 16 } },
        { id: 5, label: 'AI Integration', level: 1, color: { background: '#F59E0B', border: '#D97706' }, font: { color: '#FFFFFF', size: 16 } },

        // MLM Sub-features
        { id: 6, label: 'Unilevel Plan', level: 2, color: { background: '#A78BFA', border: '#7C3AED' }, font: { size: 12 } },
        { id: 7, label: 'Binary Plan', level: 2, color: { background: '#A78BFA', border: '#7C3AED' }, font: { size: 12 } },
        { id: 8, label: 'Commission\nEngine', level: 2, color: { background: '#A78BFA', border: '#7C3AED' }, font: { size: 12 } },

        // E-Commerce Sub-features
        { id: 9, label: 'Multi-Vendor', level: 2, color: { background: '#F472B6', border: '#DB2777' }, font: { size: 12 } },
        { id: 10, label: 'Product\nManagement', level: 2, color: { background: '#F472B6', border: '#DB2777' }, font: { size: 12 } },
        { id: 11, label: 'Payment\nGateway', level: 2, color: { background: '#F472B6', border: '#DB2777' }, font: { size: 12 } },

        // Wallet Sub-features
        { id: 12, label: 'THB Wallet', level: 2, color: { background: '#34D399', border: '#059669' }, font: { size: 12 } },
        { id: 13, label: 'Crypto\nExchange', level: 2, color: { background: '#34D399', border: '#059669' }, font: { size: 12 } },
        { id: 14, label: 'Withdrawal\nSystem', level: 2, color: { background: '#34D399', border: '#059669' }, font: { size: 12 } },

        // AI Sub-features
        { id: 15, label: 'OpenAI GPT', level: 2, color: { background: '#FBBF24', border: '#F59E0B' }, font: { size: 12 } },
        { id: 16, label: 'Claude AI', level: 2, color: { background: '#FBBF24', border: '#F59E0B' }, font: { size: 12 } },
        { id: 17, label: 'LINE Bot', level: 2, color: { background: '#FBBF24', border: '#F59E0B' }, font: { size: 12 } },

        // Additional Features
        { id: 18, label: 'Investment\n& Staking', level: 1, color: { background: '#6366F1', border: '#4338CA' }, font: { color: '#FFFFFF', size: 14 } },
        { id: 19, label: 'Hotel\nBooking', level: 1, color: { background: '#EF4444', border: '#DC2626' }, font: { color: '#FFFFFF', size: 14 } },
        { id: 20, label: 'Academy\nLearning', level: 1, color: { background: '#14B8A6', border: '#0D9488' }, font: { color: '#FFFFFF', size: 14 } },
        { id: 21, label: 'HRM\nSystem', level: 1, color: { background: '#F97316', border: '#EA580C' }, font: { color: '#FFFFFF', size: 14 } },
    ]);

    // Define edges
    edges = new vis.DataSet([
        // Main connections
        { from: 1, to: 2, width: 3, color: { color: '#8B5CF6' } },
        { from: 1, to: 3, width: 3, color: { color: '#EC4899' } },
        { from: 1, to: 4, width: 3, color: { color: '#10B981' } },
        { from: 1, to: 5, width: 3, color: { color: '#F59E0B' } },
        { from: 1, to: 18, width: 2, color: { color: '#6366F1' } },
        { from: 1, to: 19, width: 2, color: { color: '#EF4444' } },
        { from: 1, to: 20, width: 2, color: { color: '#14B8A6' } },
        { from: 1, to: 21, width: 2, color: { color: '#F97316' } },

        // MLM connections
        { from: 2, to: 6, width: 2, color: { color: '#A78BFA' } },
        { from: 2, to: 7, width: 2, color: { color: '#A78BFA' } },
        { from: 2, to: 8, width: 2, color: { color: '#A78BFA' } },

        // E-Commerce connections
        { from: 3, to: 9, width: 2, color: { color: '#F472B6' } },
        { from: 3, to: 10, width: 2, color: { color: '#F472B6' } },
        { from: 3, to: 11, width: 2, color: { color: '#F472B6' } },

        // Wallet connections
        { from: 4, to: 12, width: 2, color: { color: '#34D399' } },
        { from: 4, to: 13, width: 2, color: { color: '#34D399' } },
        { from: 4, to: 14, width: 2, color: { color: '#34D399' } },

        // AI connections
        { from: 5, to: 15, width: 2, color: { color: '#FBBF24' } },
        { from: 5, to: 16, width: 2, color: { color: '#FBBF24' } },
        { from: 5, to: 17, width: 2, color: { color: '#FBBF24' } },
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

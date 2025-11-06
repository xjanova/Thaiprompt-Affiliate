@extends('layouts.app')

@section('content')
@php
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');
@endphp

@push('styles')
<style>
:root {
    --primary-color: {{ $primaryColor }};
    --secondary-color: {{ $secondaryColor }};
    --accent-color: {{ $accentColor }};
    --gradient-primary: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
    --gradient-accent: linear-gradient(135deg, {{ $secondaryColor }} 0%, {{ $accentColor }} 100%);
}

.hero-gradient {
    background: linear-gradient(135deg, {{ $primaryColor }}15 0%, {{ $secondaryColor }}15 100%);
}

.card-premium {
    background: linear-gradient(145deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}

.text-gradient {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.icon-gradient {
    background: var(--gradient-accent);
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.section-title {
    position: relative;
    display: inline-block;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 60px;
    height: 4px;
    background: var(--gradient-accent);
    border-radius: 2px;
}

.timeline-item {
    position: relative;
    padding-left: 60px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: -40px;
    width: 2px;
    background: linear-gradient(to bottom, {{ $primaryColor }}, {{ $secondaryColor }});
}

.timeline-dot {
    position: absolute;
    left: 8px;
    top: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--gradient-primary);
    border: 4px solid white;
    box-shadow: 0 0 0 4px {{ $primaryColor }}20;
}

.feature-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 30px 80px rgba(0,0,0,0.15);
}

.floating-animation {
    animation: floating 3s ease-in-out infinite;
}

@keyframes floating {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.pulse-animation {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.chart-container {
    position: relative;
    height: 400px;
    margin: 30px 0;
}

.flowchart-node {
    padding: 20px 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 2px solid {{ $primaryColor }}40;
    transition: all 0.3s ease;
}

.flowchart-node:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    border-color: {{ $primaryColor }};
}

.arrow {
    stroke: {{ $primaryColor }};
    stroke-width: 2;
    fill: none;
    marker-end: url(#arrowhead);
}

.investment-highlight {
    background: linear-gradient(135deg, {{ $primaryColor }}10 0%, {{ $accentColor }}10 100%);
    border-left: 4px solid var(--primary-color);
}

.premium-badge {
    background: var(--gradient-accent);
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .stat-number { font-size: 2rem; }
    .chart-container { height: 300px; }
}
</style>
@endpush

<!-- Hero Section -->
<div class="hero-gradient py-24 px-4">
    <div class="max-w-7xl mx-auto text-center">
        <div class="inline-block mb-6">
            <span class="premium-badge">🚀 Enterprise MLM Platform</span>
        </div>
        <h1 class="text-5xl md:text-7xl font-bold mb-6">
            <span class="text-gradient">Thaiprompt Affiliate</span>
        </h1>
        <p class="text-xl md:text-2xl text-gray-700 max-w-4xl mx-auto mb-8">
            ระบบ Multi-Level Marketing และ E-Commerce แบบครบวงจร ที่ทันสมัยที่สุด<br>
            สำหรับองค์กรระดับ Enterprise พร้อมเทคโนโลยี AI และระบบอัตโนมัติขั้นสูง
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <div class="bg-white px-8 py-4 rounded-full shadow-lg">
                <span class="text-sm text-gray-600">เวอร์ชัน</span>
                <span class="ml-2 font-bold" style="color: {{ $primaryColor }}">1.159.0</span>
            </div>
            <div class="bg-white px-8 py-4 rounded-full shadow-lg">
                <span class="text-sm text-gray-600">สถานะ</span>
                <span class="ml-2 font-bold text-green-600">Production Ready ✓</span>
            </div>
            <div class="bg-white px-8 py-4 rounded-full shadow-lg">
                <span class="text-sm text-gray-600">ใบอนุญาต</span>
                <span class="ml-2 font-bold text-blue-600">MIT License</span>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="max-w-7xl mx-auto px-4 py-16 -mt-16">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="card-premium rounded-2xl p-8 text-center">
            <div class="stat-number">113+</div>
            <div class="text-gray-600 font-medium mt-2">Database Models</div>
        </div>
        <div class="card-premium rounded-2xl p-8 text-center">
            <div class="stat-number">105</div>
            <div class="text-gray-600 font-medium mt-2">Database Tables</div>
        </div>
        <div class="card-premium rounded-2xl p-8 text-center">
            <div class="stat-number">91</div>
            <div class="text-gray-600 font-medium mt-2">HTTP Controllers</div>
        </div>
        <div class="card-premium rounded-2xl p-8 text-center">
            <div class="stat-number">136</div>
            <div class="text-gray-600 font-medium mt-2">Migrations</div>
        </div>
    </div>
</div>

<!-- Business Model Section -->
<div class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">โมเดลธุรกิจ</h2>
        <p class="text-xl text-gray-600 mt-8">ระบบรายได้หลากหลายช่องทาง พร้อมกลไกการจ่ายค่าคอมมิชชั่นอัตโนมัติ</p>
    </div>

    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <div class="card-premium rounded-2xl p-8 feature-card">
            <div class="icon-gradient w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl mb-6">
                💰
            </div>
            <h3 class="text-2xl font-bold mb-4">MLM Commission System</h3>
            <ul class="space-y-3 text-gray-700">
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>Unilevel Plan:</strong> รองรับสายงานไม่จำกัดระดับ พร้อมระบบ Compression</span>
                </li>
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>Binary Plan:</strong> Pair Matching อัตโนมัติ พร้อม Spillover & Auto-placement</span>
                </li>
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>PV System:</strong> คำนวณ Point Value แบบเรียลไทม์</span>
                </li>
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>Rank Bonus:</strong> โบนัสตามยศ พร้อมระบบเลื่อนยศอัตโนมัติ</span>
                </li>
            </ul>
        </div>

        <div class="card-premium rounded-2xl p-8 feature-card">
            <div class="icon-gradient w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl mb-6">
                🛒
            </div>
            <h3 class="text-2xl font-bold mb-4">E-Commerce Revenue</h3>
            <ul class="space-y-3 text-gray-700">
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>Platform Commission:</strong> ค่าธรรมเนียมการขายผ่านแพลตฟอร์ม</span>
                </li>
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>Vendor Subscriptions:</strong> แพ็กเกจสมาชิกสำหรับผู้ขาย</span>
                </li>
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>Payment Gateway Fees:</strong> ค่าธรรมเนียมช่องทางชำระเงิน</span>
                </li>
                <li class="flex items-start">
                    <span style="color: {{ $primaryColor }}" class="mr-2">✓</span>
                    <span><strong>AI Bot Rentals:</strong> รายได้จากการเช่า AI Chatbot</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Revenue Flow Chart -->
    <div class="card-premium rounded-2xl p-8">
        <h3 class="text-2xl font-bold mb-6 text-center">โครงสร้างการไหลของรายได้</h3>
        <div class="chart-container">
            <canvas id="revenueFlowChart"></canvas>
        </div>
    </div>
</div>

<!-- MLM Flow Chart Section -->
<div class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">กระบวนการ MLM Commission</h2>
            <p class="text-xl text-gray-600 mt-8">ระบบคำนวณค่าคอมมิชชั่นอัตโนมัติแบบเรียลไทม์</p>
        </div>

        <!-- Flowchart SVG -->
        <div class="card-premium rounded-2xl p-8 mb-8">
            <svg class="w-full" viewBox="0 0 800 600" style="max-width: 100%;">
                <defs>
                    <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                        <polygon points="0 0, 10 3, 0 6" fill="{{ $primaryColor }}" />
                    </marker>
                    <linearGradient id="nodeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:{{ $primaryColor }};stop-opacity:0.1" />
                        <stop offset="100%" style="stop-color:{{ $secondaryColor }};stop-opacity:0.1" />
                    </linearGradient>
                </defs>

                <!-- Order Created -->
                <foreignObject x="300" y="20" width="200" height="60">
                    <div class="flowchart-node text-center">
                        <div class="font-bold">📦 Order Created</div>
                        <div class="text-sm text-gray-600">ลูกค้าสั่งซื้อสินค้า</div>
                    </div>
                </foreignObject>
                <line class="arrow" x1="400" y1="80" x2="400" y2="120" />

                <!-- Check MLM Membership -->
                <foreignObject x="300" y="120" width="200" height="60">
                    <div class="flowchart-node text-center">
                        <div class="font-bold">👤 Check Membership</div>
                        <div class="text-sm text-gray-600">ตรวจสอบสมาชิก MLM</div>
                    </div>
                </foreignObject>
                <line class="arrow" x1="400" y1="180" x2="400" y2="220" />

                <!-- Calculate PV -->
                <foreignObject x="300" y="220" width="200" height="60">
                    <div class="flowchart-node text-center">
                        <div class="font-bold">🧮 Calculate PV</div>
                        <div class="text-sm text-gray-600">คำนวณ Point Value</div>
                    </div>
                </foreignObject>

                <!-- Split to Unilevel and Binary -->
                <line class="arrow" x1="350" y1="280" x2="200" y2="320" />
                <line class="arrow" x1="450" y1="280" x2="600" y2="320" />

                <!-- Unilevel Branch -->
                <foreignObject x="100" y="320" width="200" height="80">
                    <div class="flowchart-node text-center">
                        <div class="font-bold" style="color: {{ $primaryColor }}">🔀 Unilevel Plan</div>
                        <div class="text-xs text-gray-600 mt-1">Track Upline</div>
                        <div class="text-xs text-gray-600">Apply % per Level</div>
                        <div class="text-xs text-gray-600">Check Compression</div>
                    </div>
                </foreignObject>

                <!-- Binary Branch -->
                <foreignObject x="500" y="320" width="200" height="80">
                    <div class="flowchart-node text-center">
                        <div class="font-bold" style="color: {{ $secondaryColor }}">⚖️ Binary Plan</div>
                        <div class="text-xs text-gray-600 mt-1">Match Pairs</div>
                        <div class="text-xs text-gray-600">Check Daily Limits</div>
                        <div class="text-xs text-gray-600">Apply Spillover</div>
                    </div>
                </foreignObject>

                <!-- Merge -->
                <line class="arrow" x1="200" y1="400" x2="350" y2="440" />
                <line class="arrow" x1="600" y1="400" x2="450" y2="440" />

                <!-- Create Commission Records -->
                <foreignObject x="300" y="440" width="200" height="60">
                    <div class="flowchart-node text-center">
                        <div class="font-bold">💳 Commission Records</div>
                        <div class="text-sm text-gray-600">Status: Pending</div>
                    </div>
                </foreignObject>
                <line class="arrow" x1="400" y1="500" x2="400" y2="540" />

                <!-- Admin Approval -->
                <foreignObject x="300" y="540" width="200" height="60">
                    <div class="flowchart-node text-center">
                        <div class="font-bold">✅ Admin Approval</div>
                        <div class="text-sm text-gray-600">อนุมัติ/ปฏิเสธ</div>
                    </div>
                </foreignObject>
            </svg>
        </div>
    </div>
</div>

<!-- Technology Stack Section -->
<div class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">เทคโนโลยีที่ใช้</h2>
        <p class="text-xl text-gray-600 mt-8">สร้างด้วยเทคโนโลยีระดับ Enterprise ที่ทันสมัยที่สุด</p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        <!-- Backend -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center">
                <span class="icon-gradient w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl mr-3">🔧</span>
                Backend
            </h3>
            <div class="space-y-3">
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Laravel 11</span>
                    <span class="ml-auto text-sm text-gray-600">PHP 8.1+</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">MySQL 8.0</span>
                    <span class="ml-auto text-sm text-gray-600">Database</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Eloquent ORM</span>
                    <span class="ml-auto text-sm text-gray-600">Advanced</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Sanctum API</span>
                    <span class="ml-auto text-sm text-gray-600">Auth</span>
                </div>
            </div>
        </div>

        <!-- Frontend -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center">
                <span class="icon-gradient w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl mr-3">🎨</span>
                Frontend
            </h3>
            <div class="space-y-3">
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Tailwind CSS 3.4</span>
                    <span class="ml-auto text-sm text-gray-600">Utility-First</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Alpine.js 3.13</span>
                    <span class="ml-auto text-sm text-gray-600">Reactive</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Vite 5.0</span>
                    <span class="ml-auto text-sm text-gray-600">Build Tool</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Chart.js 4.4</span>
                    <span class="ml-auto text-sm text-gray-600">Visualization</span>
                </div>
            </div>
        </div>

        <!-- Integrations -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center">
                <span class="icon-gradient w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl mr-3">🔌</span>
                Integrations
            </h3>
            <div class="space-y-3">
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">OpenAI GPT</span>
                    <span class="ml-auto text-sm text-gray-600">AI Chat</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">Google Cloud</span>
                    <span class="ml-auto text-sm text-gray-600">OCR/Vision</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">LINE Official</span>
                    <span class="ml-auto text-sm text-gray-600">Messaging</span>
                </div>
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <span class="font-semibold text-gray-800">PromptPay</span>
                    <span class="ml-auto text-sm text-gray-600">Payment</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Overview -->
<div class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">ฟีเจอร์ครบครัน</h2>
            <p class="text-xl text-gray-600 mt-8">ระบบที่ครอบคลุมทุกความต้องการของธุรกิจ MLM และ E-Commerce</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['icon' => '🌐', 'title' => 'MLM System', 'desc' => 'Unilevel & Binary Plans พร้อม Auto Commission'],
                ['icon' => '🛍️', 'title' => 'E-Commerce', 'desc' => 'ระบบขายสินค้าครบวงจร Multi-Vendor'],
                ['icon' => '💰', 'title' => 'Wallet System', 'desc' => 'กระเป๋าเงินดิจิทัล พร้อม 2FA และ PIN'],
                ['icon' => '📊', 'title' => 'Analytics Dashboard', 'desc' => 'รายงานและสถิติแบบเรียลไทม์'],
                ['icon' => '🤖', 'title' => 'AI Chatbot', 'desc' => 'LINE Bot พร้อม RAG Knowledge Base'],
                ['icon' => '🔐', 'title' => 'KYC/OCR', 'desc' => 'ตรวจสอบบัตรประชาชนด้วย Google Vision AI'],
                ['icon' => '📱', 'title' => 'LINE Integration', 'desc' => 'Flex Messages, Rich Menu, Broadcast'],
                ['icon' => '🎖️', 'title' => 'Rank System', 'desc' => 'ระบบยศ พร้อมโบนัสและเลื่อนยศอัตโนมัติ'],
                ['icon' => '🌍', 'title' => 'Multi-Language', 'desc' => 'รองรับหลายภาษา พร้อม Auto Translation'],
                ['icon' => '💳', 'title' => 'Payment Gateway', 'desc' => 'PromptPay, Bank Transfer, Credit Card'],
                ['icon' => '🛡️', 'title' => 'Security', 'desc' => 'IP Blocking, Threat Detection, Auto-Ban'],
                ['icon' => '📧', 'title' => 'Email System', 'desc' => 'Multi-Provider Email Campaigns'],
            ] as $feature)
            <div class="card-premium rounded-xl p-6 feature-card">
                <div class="text-4xl mb-4">{{ $feature['icon'] }}</div>
                <h4 class="text-xl font-bold mb-2">{{ $feature['title'] }}</h4>
                <p class="text-gray-600">{{ $feature['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Development Phases -->
<div class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">เฟสการพัฒนา</h2>
        <p class="text-xl text-gray-600 mt-8">จากแนวคิดสู่ระบบที่สมบูรณ์แบบ</p>
    </div>

    <div class="space-y-8">
        @foreach([
            ['phase' => 'Phase 1', 'title' => 'Foundation & Core System', 'period' => 'Q1 2024', 'features' => ['Laravel 11 Setup', 'Database Architecture (105 Tables)', 'Authentication & Authorization', 'Admin Panel Foundation']],
            ['phase' => 'Phase 2', 'title' => 'MLM Engine Development', 'period' => 'Q2 2024', 'features' => ['Unilevel MLM Implementation', 'Binary MLM Implementation', 'Commission Calculation Engine', 'PV Transaction System']],
            ['phase' => 'Phase 3', 'title' => 'E-Commerce Integration', 'period' => 'Q2-Q3 2024', 'features' => ['Product Management', 'Shopping Cart & Checkout', 'Multi-Vendor System', 'Payment Gateway Integration']],
            ['phase' => 'Phase 4', 'title' => 'Advanced Features', 'period' => 'Q3 2024', 'features' => ['Wallet System with 2FA', 'Rank & Promotion System', 'LINE OA Integration', 'AI Chatbot with RAG']],
            ['phase' => 'Phase 5', 'title' => 'Intelligence & Automation', 'period' => 'Q4 2024', 'features' => ['Google Vision OCR/KYC', 'Multi-AI Provider Support', 'Knowledge Base System', 'Automated Marketing Tools']],
            ['phase' => 'Phase 6', 'title' => 'Production Ready', 'period' => 'Q4 2024 - Present', 'features' => ['Setup Wizard', 'License System', 'Performance Optimization', 'Security Hardening']],
        ] as $index => $phase)
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="card-premium rounded-xl p-8">
                <div class="flex flex-wrap items-center justify-between mb-4">
                    <div>
                        <span class="premium-badge text-sm">{{ $phase['phase'] }}</span>
                        <h3 class="text-2xl font-bold mt-2">{{ $phase['title'] }}</h3>
                    </div>
                    <div class="text-gray-600 font-semibold">{{ $phase['period'] }}</div>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    @foreach($phase['features'] as $feature)
                    <div class="flex items-center text-gray-700">
                        <span style="color: {{ $primaryColor }}" class="mr-2 text-lg">✓</span>
                        <span>{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Investment Opportunity Section -->
<div class="bg-gradient-to-br from-blue-50 to-purple-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">โอกาสการลงทุน</h2>
            <p class="text-xl text-gray-600 mt-8">แพลตฟอร์มที่พร้อมสร้างผลตอบแทนระยะยาว</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- Investment Highlights -->
            <div class="card-premium rounded-2xl p-8">
                <h3 class="text-2xl font-bold mb-6">💎 จุดเด่นสำหรับนักลงทุน</h3>
                <div class="space-y-4">
                    <div class="investment-highlight p-4 rounded-lg">
                        <div class="font-bold mb-1">🚀 Technology Value</div>
                        <div class="text-gray-700">มูลค่าการพัฒนาเทียบเท่าโครงการหลายล้านบาท</div>
                        <div class="text-sm text-gray-600">113+ Models, 105 Tables, 136 Migrations</div>
                    </div>
                    <div class="investment-highlight p-4 rounded-lg">
                        <div class="font-bold mb-1">💰 Multiple Revenue Streams</div>
                        <div class="text-gray-700">รายได้จาก Platform Fees, Subscriptions, Commissions</div>
                        <div class="text-sm text-gray-600">4+ แหล่งรายได้หลัก</div>
                    </div>
                    <div class="investment-highlight p-4 rounded-lg">
                        <div class="font-bold mb-1">📈 Scalable Architecture</div>
                        <div class="text-gray-700">ออกแบบรองรับการเติบโตระดับ Enterprise</div>
                        <div class="text-sm text-gray-600">Cloud-ready, Auto-scaling capable</div>
                    </div>
                    <div class="investment-highlight p-4 rounded-lg">
                        <div class="font-bold mb-1">🔒 Production Ready</div>
                        <div class="text-gray-700">พร้อม Deploy ทันที มีระบบ License และ Setup Wizard</div>
                        <div class="text-sm text-gray-600">Version 1.159.0 - Stable</div>
                    </div>
                </div>
            </div>

            <!-- Market Opportunity -->
            <div class="card-premium rounded-2xl p-8">
                <h3 class="text-2xl font-bold mb-6">🎯 โอกาสทางการตลาด</h3>
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold">MLM Market (Thailand)</span>
                            <span class="text-sm font-bold" style="color: {{ $primaryColor }}">฿50B+/year</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full" style="width: 85%; background: {{ $primaryColor }}"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold">E-Commerce Growth</span>
                            <span class="text-sm font-bold" style="color: {{ $secondaryColor }}">+25%/year</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full" style="width: 75%; background: {{ $secondaryColor }}"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold">AI Chatbot Adoption</span>
                            <span class="text-sm font-bold" style="color: {{ $accentColor }}">+150%/year</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full" style="width: 95%; background: {{ $accentColor }}"></div>
                        </div>
                    </div>
                    <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg">
                        <div class="text-sm font-semibold mb-2">💡 Competitive Advantage</div>
                        <ul class="text-sm space-y-1 text-gray-700">
                            <li>✓ เป็นระบบที่ครบถ้วนที่สุดในตลาด</li>
                            <li>✓ รองรับทั้ง MLM + E-Commerce + AI</li>
                            <li>✓ มี LINE Integration แบบ Native</li>
                            <li>✓ Open Source ปรับแต่งได้ไม่จำกัด</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROI Projection Chart -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 text-center">📊 ROI Projection (3 Years)</h3>
            <div class="chart-container">
                <canvas id="roiProjectionChart"></canvas>
            </div>
            <div class="mt-6 text-center text-sm text-gray-600">
                *ตัวเลขเป็นการประมาณการตามข้อมูลตลาดและ benchmark จากโครงการใกล้เคียง
            </div>
        </div>
    </div>
</div>

<!-- Architecture Diagram -->
<div class="max-w-7xl mx-auto px-4 py-20">
    <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title">สถาปัตยกรรมระบบ</h2>
        <p class="text-xl text-gray-600 mt-8">ออกแบบแบบ Layered Architecture เพื่อความยืดหยุ่นและขยายได้</p>
    </div>

    <div class="card-premium rounded-2xl p-8">
        <div class="grid md:grid-cols-4 gap-4">
            <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                <div class="text-3xl mb-3">🖥️</div>
                <div class="font-bold mb-2">Presentation Layer</div>
                <div class="text-sm text-gray-600">Blade Views<br>Alpine.js<br>Tailwind CSS</div>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                <div class="text-3xl mb-3">⚙️</div>
                <div class="font-bold mb-2">Business Logic</div>
                <div class="text-sm text-gray-600">30+ Services<br>91 Controllers<br>Domain Logic</div>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl">
                <div class="text-3xl mb-3">🗄️</div>
                <div class="font-bold mb-2">Data Layer</div>
                <div class="text-sm text-gray-600">113+ Models<br>Eloquent ORM<br>Repository Pattern</div>
            </div>
            <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                <div class="text-3xl mb-3">🔌</div>
                <div class="font-bold mb-2">Integration Layer</div>
                <div class="text-sm text-gray-600">APIs<br>Webhooks<br>Third-party Services</div>
            </div>
        </div>

        <div class="mt-8 p-6 bg-gray-50 rounded-xl">
            <h4 class="font-bold mb-4 text-center">Database Schema Overview</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="text-center">
                    <div class="font-bold text-2xl" style="color: {{ $primaryColor }}">11</div>
                    <div class="text-gray-600">MLM Tables</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-2xl" style="color: {{ $secondaryColor }}">15</div>
                    <div class="text-gray-600">E-Commerce Tables</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-2xl" style="color: {{ $accentColor }}">20</div>
                    <div class="text-gray-600">AI/LINE Tables</div>
                </div>
                <div class="text-center">
                    <div class="font-bold text-2xl text-green-600">59</div>
                    <div class="text-gray-600">Supporting Tables</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="hero-gradient py-20">
    <div class="max-w-4xl mx-auto text-center px-4">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">พร้อมเริ่มต้นแล้วหรือยัง?</h2>
        <p class="text-xl text-gray-700 mb-8">
            แพลตฟอร์ม MLM และ E-Commerce ระดับ Enterprise<br>
            ที่สมบูรณ์แบบและพร้อมใช้งานทันที
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('register') }}" class="px-8 py-4 rounded-full font-bold text-white shadow-lg hover:shadow-xl transition-all" style="background: {{ $primaryColor }}">
                เริ่มต้นใช้งาน
            </a>
            <a href="{{ route('contact') }}" class="px-8 py-4 bg-white rounded-full font-bold shadow-lg hover:shadow-xl transition-all" style="color: {{ $primaryColor }}">
                ติดต่อเรา
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script>
// Revenue Flow Chart
const revenueFlowCtx = document.getElementById('revenueFlowChart').getContext('2d');
new Chart(revenueFlowCtx, {
    type: 'sankey',
    data: {
        datasets: [{
            label: 'Revenue Flow',
            data: [
                {from: 'Customer Orders', to: 'Platform', flow: 100},
                {from: 'Platform', to: 'MLM Commission', flow: 30},
                {from: 'Platform', to: 'Seller Payment', flow: 50},
                {from: 'Platform', to: 'Platform Fee', flow: 20},
                {from: 'MLM Commission', to: 'Direct Sponsor', flow: 15},
                {from: 'MLM Commission', to: 'Upline Levels', flow: 10},
                {from: 'MLM Commission', to: 'Binary Bonus', flow: 5},
            ],
            colorFrom: (c) => '{{ $primaryColor }}',
            colorTo: (c) => '{{ $secondaryColor }}',
            colorMode: 'gradient',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
    }
});

// Fallback to Bar Chart if Sankey not available
if (!Chart.registry.getController('sankey')) {
    const revenueCtx = document.getElementById('revenueFlowChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['MLM Commission', 'Seller Earnings', 'Platform Fees', 'Payment Fees', 'AI Bot Rentals', 'Subscriptions'],
            datasets: [{
                label: 'รายได้ (เปอร์เซ็นต์)',
                data: [30, 50, 15, 3, 10, 7],
                backgroundColor: [
                    '{{ $primaryColor }}',
                    '{{ $secondaryColor }}',
                    '{{ $accentColor }}',
                    '#10B981',
                    '#F59E0B',
                    '#6366F1'
                ],
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Revenue Distribution by Source',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// ROI Projection Chart
const roiCtx = document.getElementById('roiProjectionChart').getContext('2d');
new Chart(roiCtx, {
    type: 'line',
    data: {
        labels: ['Month 0', 'Month 6', 'Month 12', 'Month 18', 'Month 24', 'Month 30', 'Month 36'],
        datasets: [
            {
                label: 'Conservative Scenario',
                data: [0, 25, 60, 100, 150, 210, 280],
                borderColor: '{{ $primaryColor }}',
                backgroundColor: '{{ $primaryColor }}20',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Moderate Scenario',
                data: [0, 40, 95, 160, 240, 340, 460],
                borderColor: '{{ $secondaryColor }}',
                backgroundColor: '{{ $secondaryColor }}20',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Optimistic Scenario',
                data: [0, 60, 140, 240, 370, 530, 720],
                borderColor: '{{ $accentColor }}',
                backgroundColor: '{{ $accentColor }}20',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});

// Simple animations without ScrollTrigger (not included in base GSAP)
// Animate stat numbers on page load
document.addEventListener('DOMContentLoaded', function() {
    // Counter animation for stat numbers
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach((stat, index) => {
        const target = parseInt(stat.textContent.replace(/\D/g, ''));
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                stat.textContent = target + (stat.textContent.includes('+') ? '+' : '');
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(current) + (stat.textContent.includes('+') ? '+' : '');
            }
        }, 30);
    });
});
</script>
@endpush

@endsection

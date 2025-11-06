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
    --primary: {{ $primaryColor }};
    --secondary: {{ $secondaryColor }};
    --accent: {{ $accentColor }};
}

.hero-gradient {
    background: linear-gradient(135deg, {{ $primaryColor }}15 0%, {{ $secondaryColor }}15 100%);
}

.card-premium {
    background: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.6);
    box-shadow: 0 25px 80px rgba(0,0,0,0.12);
    transition: all 0.3s ease;
}

.card-premium:hover {
    transform: translateY(-5px);
    box-shadow: 0 35px 100px rgba(0,0,0,0.18);
}

.text-gradient {
    background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Mind Map Container - Google Maps Style */
.diagram-container {
    width: 100%;
    min-height: 800px;
    background: linear-gradient(to bottom, #f0f4f8 0%, #ffffff 100%);
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.diagram-wrapper {
    width: 100%;
    height: 100%;
    min-height: 800px;
    position: relative;
    cursor: move;
    transform-origin: center center;
    transition: transform 0.3s ease;
}

.zoom-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: white;
    padding: 8px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.zoom-btn {
    width: 40px;
    height: 40px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 20px;
    font-weight: bold;
    transition: all 0.2s;
    color: #374151;
}

.zoom-btn:hover {
    background: {{ $primaryColor }};
    color: white;
    border-color: {{ $primaryColor }};
    transform: scale(1.1);
}

/* Node Styles */
.node {
    cursor: pointer;
    transition: all 0.3s ease;
}

.node:hover {
    transform: scale(1.05);
    filter: brightness(1.1);
}

.node rect {
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
}

.node:hover rect {
    filter: drop-shadow(0 8px 16px rgba(0,0,0,0.2));
}

.node text {
    pointer-events: none;
    user-select: none;
}

.connection-line {
    stroke-width: 2;
    fill: none;
    opacity: 0.6;
}

.connection-line-dashed {
    stroke-dasharray: 5,5;
}

.section-title {
    position: relative;
    display: inline-block;
    padding-bottom: 16px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, {{ $primaryColor }}, {{ $secondaryColor }});
    border-radius: 2px;
}

.badge-version {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 18px;
    display: inline-block;
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
    animation: pulse-badge 2s ease-in-out infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.stat-box {
    background: linear-gradient(135deg, {{ $primaryColor }}10 0%, {{ $secondaryColor }}10 100%);
    border-left: 4px solid {{ $primaryColor }};
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}

.stat-box:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.info-panel {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background: white;
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    max-width: 300px;
}

@media (max-width: 768px) {
    .diagram-container {
        min-height: 500px;
    }
    .diagram-wrapper {
        min-height: 500px;
    }
}
</style>
@endpush

<!-- Hero Section -->
<div class="hero-gradient py-24 px-4">
    <div class="max-w-7xl mx-auto text-center">
        <div class="mb-6">
            <span class="badge-version">v{{ $stats['version'] ?? '1.159.0' }}</span>
            <div class="mt-2 text-sm text-gray-600">Last Updated: {{ $stats['last_updated'] ?? date('Y-m-d') }}</div>
        </div>
        <h1 class="text-5xl md:text-7xl font-bold mb-6">
            <span class="text-gradient">Thaiprompt Affiliate</span>
        </h1>
        <p class="text-xl md:text-2xl text-gray-700 max-w-4xl mx-auto mb-8">
            แพลตฟอร์ม Multi-Level Marketing และ E-Commerce ระดับ Enterprise<br>
            ที่ครบวงจรและทันสมัยที่สุด พร้อม AI Integration และระบบอัตโนมัติขั้นสูง
        </p>

        <!-- Real-time Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-5xl mx-auto">
            <div class="card-premium rounded-2xl p-6">
                <div class="text-4xl font-bold text-gradient">{{ $stats['database_models'] ?? 113 }}+</div>
                <div class="text-sm text-gray-600 mt-2">Database Models</div>
            </div>
            <div class="card-premium rounded-2xl p-6">
                <div class="text-4xl font-bold text-gradient">{{ $stats['database_tables'] ?? 105 }}</div>
                <div class="text-sm text-gray-600 mt-2">Database Tables</div>
            </div>
            <div class="card-premium rounded-2xl p-6">
                <div class="text-4xl font-bold text-gradient">{{ $stats['http_controllers'] ?? 91 }}</div>
                <div class="text-sm text-gray-600 mt-2">Controllers</div>
            </div>
            <div class="card-premium rounded-2xl p-6">
                <div class="text-4xl font-bold text-gradient">{{ $stats['migrations_count'] ?? 136 }}</div>
                <div class="text-sm text-gray-600 mt-2">Migrations</div>
            </div>
        </div>
    </div>
</div>

<!-- Table of Contents -->
<div class="bg-white py-12 border-y">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold mb-8 text-center">📑 สารบัญ</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['icon' => '🎯', 'title' => 'ภาพรวมโครงการ', 'href' => '#overview'],
                ['icon' => '🧠', 'title' => 'Mind Map ระบบ', 'href' => '#mindmap'],
                ['icon' => '🏗️', 'title' => 'สถาปัตยกรรม', 'href' => '#architecture'],
                ['icon' => '💰', 'title' => 'โมเดลธุรกิจ', 'href' => '#business-model'],
                ['icon' => '⚙️', 'title' => 'ฟีเจอร์ทั้งหมด', 'href' => '#features'],
                ['icon' => '🛠️', 'title' => 'เทคโนโลยี', 'href' => '#technology'],
            ] as $item)
            <a href="{{ $item['href'] }}" class="flex items-center gap-3 p-4 rounded-lg border-2 border-gray-200 hover:border-[var(--primary)] hover:bg-gray-50 transition-all">
                <span class="text-3xl">{{ $item['icon'] }}</span>
                <span class="font-semibold">{{ $item['title'] }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Project Overview Section -->
<div id="overview" class="max-w-7xl mx-auto px-4 py-20">
    <h2 class="text-4xl md:text-5xl font-bold mb-8 section-title">🎯 ภาพรวมโครงการ</h2>

    <div class="prose prose-lg max-w-none">
        <div class="card-premium rounded-2xl p-8 mb-8">
            <h3 class="text-2xl font-bold mb-4">ชื่อโครงการ</h3>
            <p class="text-xl"><strong>Thaiprompt Affiliate Marketing Platform (TP-Affiliate)</strong></p>
            <p class="text-gray-600 mt-2">แพลตฟอร์มระบบ Affiliate Marketing และ Multi-Level Marketing แบบครบวงจร สำหรับองค์กรธุรกิจขนาดใหญ่</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="stat-box">
                <h4 class="font-bold text-lg mb-2">📦 Repository</h4>
                <p class="text-gray-700">xjanova/Thaiprompt-Affiliate (Private)</p>
            </div>
            <div class="stat-box">
                <h4 class="font-bold text-lg mb-2">📜 License</h4>
                <p class="text-gray-700">MIT License</p>
            </div>
            <div class="stat-box">
                <h4 class="font-bold text-lg mb-2">🏷️ Version</h4>
                <p class="text-gray-700 font-mono">v{{ $stats['version'] ?? '1.159.0' }}</p>
            </div>
            <div class="stat-box">
                <h4 class="font-bold text-lg mb-2">✅ Status</h4>
                <p class="text-green-600 font-bold">Production Ready</p>
            </div>
        </div>

        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-4">🌟 จุดเด่นหลัก</h3>
            <ul class="space-y-3">
                <li class="flex items-start gap-3">
                    <span class="text-green-500 text-xl">✓</span>
                    <span><strong>ระบบ MLM ครบวงจร</strong> - รองรับทั้ง Unilevel และ Binary Plan พร้อมระบบคำนวณค่าคอมมิชชั่นอัตโนมัติ</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-green-500 text-xl">✓</span>
                    <span><strong>E-Commerce Platform</strong> - ระบบขายสินค้าแบบ Multi-Vendor พร้อม Payment Gateway หลากหลาย</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-green-500 text-xl">✓</span>
                    <span><strong>AI Integration</strong> - ผสานระบบ AI Chatbot พร้อม LINE Official Account และ RAG Knowledge Base</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-green-500 text-xl">✓</span>
                    <span><strong>Security Features</strong> - KYC/OCR ด้วย Google Vision API, IP Blocking, 2FA, Auto-ban</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-green-500 text-xl">✓</span>
                    <span><strong>Multi-Language</strong> - รองรับหลายภาษาพร้อม Google Translate API</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-green-500 text-xl">✓</span>
                    <span><strong>Production Ready</strong> - พร้อม Setup Wizard, License System, และ Deployment Scripts</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Mind Map Section -->
<div id="mindmap" class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">🧠 Mind Map: ระบบเชื่อมต่อทั้งหมด</h2>
        <p class="text-xl text-gray-600 text-center mb-12">แผนภาพแสดงความเชื่อมโยงของระบบทั้งหมดแบบ Interactive (คลิกลากเพื่อดู, Scroll เพื่อ Zoom)</p>

        <div class="card-premium rounded-2xl p-4 mb-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="text-sm text-gray-600">
                    💡 <strong>คำแนะนำ:</strong> คลิกลากเพื่อเลื่อนดูผัง, ใช้ปุ่มด้านขวาเพื่อ Zoom In/Out
                </div>
                <div class="flex gap-2 text-xs">
                    <span class="px-3 py-1 bg-purple-100 rounded-full">🟣 MLM</span>
                    <span class="px-3 py-1 bg-pink-100 rounded-full">🔴 E-Commerce</span>
                    <span class="px-3 py-1 bg-green-100 rounded-full">🟢 Wallet</span>
                    <span class="px-3 py-1 bg-yellow-100 rounded-full">🟠 LINE</span>
                    <span class="px-3 py-1 bg-cyan-100 rounded-full">🔵 AI</span>
                </div>
            </div>
        </div>

        <!-- Interactive Mind Map -->
        <div class="diagram-container" id="mindmap-container">
            <div class="zoom-controls">
                <button onclick="zoomIn()" class="zoom-btn" title="Zoom In">+</button>
                <button onclick="zoomOut()" class="zoom-btn" title="Zoom Out">−</button>
                <button onclick="resetZoom()" class="zoom-btn" title="Reset">⟲</button>
            </div>

            <div class="info-panel">
                <div class="text-sm font-semibold mb-2">📍 ระบบทั้งหมด</div>
                <div class="text-xs text-gray-600">
                    • 7 โมดูลหลัก<br>
                    • 40+ ระบบย่อย<br>
                    • เชื่อมต่อกันครบถ้วน
                </div>
            </div>

            <svg id="mindmap-svg" class="diagram-wrapper" viewBox="0 0 1600 1200" preserveAspectRatio="xMidYMid meet">
                <defs>
                    <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                        <polygon points="0 0, 10 3, 0 6" fill="#94A3B8" />
                    </marker>

                    <!-- Gradients -->
                    <linearGradient id="grad-primary" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:{{ $primaryColor }};stop-opacity:1" />
                        <stop offset="100%" style="stop-color:{{ $secondaryColor }};stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="grad-mlm" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#7C3AED;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="grad-ecommerce" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#EC4899;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#DB2777;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="grad-wallet" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#10B981;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="grad-line" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#F59E0B;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#D97706;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="grad-ai" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#06B6D4;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#0891B2;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="grad-security" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#EF4444;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#DC2626;stop-opacity:1" />
                    </linearGradient>
                </defs>

                <!-- Core Platform (Center) -->
                <g class="node" onclick="showNodeInfo('TP-Affiliate Platform', 'แพลตฟอร์มหลัก MLM + E-Commerce')">
                    <rect x="650" y="550" width="300" height="100" rx="15" fill="url(#grad-primary)" />
                    <text x="800" y="590" text-anchor="middle" fill="white" font-size="24" font-weight="bold">TP-Affiliate</text>
                    <text x="800" y="620" text-anchor="middle" fill="white" font-size="16">Platform</text>
                </g>

                <!-- MLM System (Top Left) -->
                <line x1="750" y1="550" x2="300" y2="150" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('MLM System', 'ระบบ Multi-Level Marketing แบบครบวงจร')">
                    <rect x="150" y="50" width="300" height="200" rx="12" fill="url(#grad-mlm)" />
                    <text x="300" y="90" text-anchor="middle" fill="white" font-size="20" font-weight="bold">MLM System</text>
                    <text x="300" y="120" text-anchor="middle" fill="white" font-size="13">• Unilevel Plan</text>
                    <text x="300" y="140" text-anchor="middle" fill="white" font-size="13">• Binary Plan</text>
                    <text x="300" y="160" text-anchor="middle" fill="white" font-size="13">• Commission Engine</text>
                    <text x="300" y="180" text-anchor="middle" fill="white" font-size="13">• PV System</text>
                    <text x="300" y="200" text-anchor="middle" fill="white" font-size="13">• Rank Management</text>
                    <text x="300" y="220" text-anchor="middle" fill="white" font-size="13">• Genealogy Tree</text>
                </g>

                <!-- E-Commerce (Top Right) -->
                <line x1="850" y1="550" x2="1300" y2="150" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('E-Commerce', 'ระบบขายสินค้าแบบ Multi-Vendor')">
                    <rect x="1150" y="50" width="300" height="200" rx="12" fill="url(#grad-ecommerce)" />
                    <text x="1300" y="90" text-anchor="middle" fill="white" font-size="20" font-weight="bold">E-Commerce</text>
                    <text x="1300" y="120" text-anchor="middle" fill="white" font-size="13">• Product Management</text>
                    <text x="1300" y="140" text-anchor="middle" fill="white" font-size="13">• Shopping Cart</text>
                    <text x="1300" y="160" text-anchor="middle" fill="white" font-size="13">• Order Processing</text>
                    <text x="1300" y="180" text-anchor="middle" fill="white" font-size="13">• Multi-Vendor</text>
                    <text x="1300" y="200" text-anchor="middle" fill="white" font-size="13">• Inventory System</text>
                    <text x="1300" y="220" text-anchor="middle" fill="white" font-size="13">• Reviews & Ratings</text>
                </g>

                <!-- Wallet System (Left) -->
                <line x1="650" y1="600" x2="300" y2="600" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('Wallet System', 'ระบบกระเป๋าเงินดิจิทัล')">
                    <rect x="50" y="500" width="250" height="200" rx="12" fill="url(#grad-wallet)" />
                    <text x="175" y="540" text-anchor="middle" fill="white" font-size="20" font-weight="bold">Wallet System</text>
                    <text x="175" y="570" text-anchor="middle" fill="white" font-size="13">• Balance Management</text>
                    <text x="175" y="590" text-anchor="middle" fill="white" font-size="13">• Transactions</text>
                    <text x="175" y="610" text-anchor="middle" fill="white" font-size="13">• Withdrawals</text>
                    <text x="175" y="630" text-anchor="middle" fill="white" font-size="13">• Payment Methods</text>
                    <text x="175" y="650" text-anchor="middle" fill="white" font-size="13">• 2FA & PIN Security</text>
                </g>

                <!-- LINE Integration (Right) -->
                <line x1="950" y1="600" x2="1300" y2="600" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('LINE Integration', 'LINE Official Account + AI Bot')">
                    <rect x="1300" y="500" width="250" height="200" rx="12" fill="url(#grad-line)" />
                    <text x="1425" y="540" text-anchor="middle" fill="white" font-size="20" font-weight="bold">LINE Integration</text>
                    <text x="1425" y="570" text-anchor="middle" fill="white" font-size="13">• LINE Bot AI</text>
                    <text x="1425" y="590" text-anchor="middle" fill="white" font-size="13">• Flex Messages</text>
                    <text x="1425" y="610" text-anchor="middle" fill="white" font-size="13">• Rich Menu Builder</text>
                    <text x="1425" y="630" text-anchor="middle" fill="white" font-size="13">• Broadcast System</text>
                    <text x="1425" y="650" text-anchor="middle" fill="white" font-size="13">• Chat Widget</text>
                </g>

                <!-- AI Services (Bottom Left) -->
                <line x1="750" y1="650" x2="300" y2="1000" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('AI Services', 'Multi-AI Provider Integration')">
                    <rect x="150" y="900" width="300" height="200" rx="12" fill="url(#grad-ai)" />
                    <text x="300" y="940" text-anchor="middle" fill="white" font-size="20" font-weight="bold">AI Services</text>
                    <text x="300" y="970" text-anchor="middle" fill="white" font-size="13">• OpenAI GPT</text>
                    <text x="300" y="990" text-anchor="middle" fill="white" font-size="13">• Claude AI</text>
                    <text x="300" y="1010" text-anchor="middle" fill="white" font-size="13">• Google Gemini</text>
                    <text x="300" y="1030" text-anchor="middle" fill="white" font-size="13">• RAG System</text>
                    <text x="300" y="1050" text-anchor="middle" fill="white" font-size="13">• Knowledge Base</text>
                </g>

                <!-- Security System (Bottom Center) -->
                <line x1="800" y1="650" x2="800" y2="900" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('Security', 'Enterprise Security Features')">
                    <rect x="650" y="900" width="300" height="170" rx="12" fill="url(#grad-security)" />
                    <text x="800" y="940" text-anchor="middle" fill="white" font-size="20" font-weight="bold">Security</text>
                    <text x="800" y="970" text-anchor="middle" fill="white" font-size="13">• KYC/OCR (Google Vision)</text>
                    <text x="800" y="990" text-anchor="middle" fill="white" font-size="13">• IP Blocking & CIDR</text>
                    <text x="800" y="1010" text-anchor="middle" fill="white" font-size="13">• Threat Detection</text>
                    <text x="800" y="1030" text-anchor="middle" fill="white" font-size="13">• Auto-Ban System</text>
                </g>

                <!-- Admin Panel (Bottom Right) -->
                <line x1="850" y1="650" x2="1300" y2="1000" class="connection-line" stroke="{{ $primaryColor }}" stroke-width="3" />
                <g class="node" onclick="showNodeInfo('Admin Panel', '50+ Controllers สำหรับจัดการ')">
                    <rect x="1150" y="900" width="300" height="170" rx="12" fill="#6366F1" />
                    <text x="1300" y="940" text-anchor="middle" fill="white" font-size="20" font-weight="bold">Admin Panel</text>
                    <text x="1300" y="970" text-anchor="middle" fill="white" font-size="13">• Dashboard & Analytics</text>
                    <text x="1300" y="990" text-anchor="middle" fill="white" font-size="13">• User Management</text>
                    <text x="1300" y="1010" text-anchor="middle" fill="white" font-size="13">• System Settings</text>
                    <text x="1300" y="1030" text-anchor="middle" fill="white" font-size="13">• Reports & Monitoring</text>
                </g>

                <!-- Cross-Module Connections (Dashed Lines) -->
                <line x1="300" y1="250" x2="175" y2="500" class="connection-line connection-line-dashed" stroke="#94A3B8" marker-end="url(#arrowhead)" />
                <text x="220" y="380" fill="#64748B" font-size="11">Commission → Wallet</text>

                <line x1="1300" y1="250" x2="300" y2="900" class="connection-line connection-line-dashed" stroke="#94A3B8" marker-end="url(#arrowhead)" />
                <text x="750" y="570" fill="#64748B" font-size="11">Order → Commission → AI</text>

                <line x1="1425" y1="700" x2="300" y2="950" class="connection-line connection-line-dashed" stroke="#94A3B8" marker-end="url(#arrowhead)" />
                <text x="850" y="820" fill="#64748B" font-size="11">LINE Bot → AI Knowledge</text>
            </svg>
        </div>
    </div>
</div>

<!-- Architecture Details -->
<div id="architecture" class="max-w-7xl mx-auto px-4 py-20">
    <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">🏗️ System Architecture</h2>
    <p class="text-xl text-gray-600 text-center mb-12">สถาปัตยกรรมระบบแบบ Layered Architecture พร้อม Integration ครบวงจร</p>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <span class="text-4xl">🖥️</span>
                <span>Presentation Layer</span>
            </h3>
            <ul class="space-y-3 text-gray-700">
                <li>• <strong>Blade Templates:</strong> 100+ responsive views</li>
                <li>• <strong>Alpine.js:</strong> Reactive components</li>
                <li>• <strong>Tailwind CSS:</strong> Utility-first styling</li>
                <li>• <strong>Chart.js & D3.js:</strong> Data visualization</li>
                <li>• <strong>Vite:</strong> Fast build system</li>
            </ul>
        </div>

        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <span class="text-4xl">⚙️</span>
                <span>Business Logic Layer</span>
            </h3>
            <ul class="space-y-3 text-gray-700">
                <li>• <strong>Controllers:</strong> 91 HTTP controllers</li>
                <li>• <strong>Services:</strong> 30+ business services</li>
                <li>• <strong>MLM Engine:</strong> Commission calculation</li>
                <li>• <strong>Payment Processing:</strong> Multi-gateway support</li>
                <li>• <strong>AI Services:</strong> Multi-provider integration</li>
            </ul>
        </div>

        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <span class="text-4xl">🗄️</span>
                <span>Data Layer</span>
            </h3>
            <ul class="space-y-3 text-gray-700">
                <li>• <strong>Eloquent Models:</strong> 113+ models</li>
                <li>• <strong>Database Tables:</strong> 105 tables</li>
                <li>• <strong>Migrations:</strong> 136 versioned migrations</li>
                <li>• <strong>Relationships:</strong> Complex associations</li>
                <li>• <strong>Query Optimization:</strong> Eager loading</li>
            </ul>
        </div>

        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <span class="text-4xl">🔌</span>
                <span>Integration Layer</span>
            </h3>
            <ul class="space-y-3 text-gray-700">
                <li>• <strong>REST API:</strong> 20+ endpoints</li>
                <li>• <strong>LINE Official Account:</strong> Messaging API</li>
                <li>• <strong>Google Cloud:</strong> Vision & Translate</li>
                <li>• <strong>Payment Gateways:</strong> Multiple providers</li>
                <li>• <strong>AI Providers:</strong> OpenAI, Claude, Gemini</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentZoom = 1;
let isDragging = false;
let startX, startY;
let translateX = 0, translateY = 0;

// SVG Pan and Zoom functionality
document.addEventListener('DOMContentLoaded', function() {
    const svg = document.getElementById('mindmap-svg');
    const container = document.getElementById('mindmap-container');

    // Mouse drag to pan
    container.addEventListener('mousedown', function(e) {
        if (e.target.closest('.node') || e.target.closest('.zoom-btn')) return;
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        container.style.cursor = 'grabbing';
    });

    container.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        e.preventDefault();
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    });

    container.addEventListener('mouseup', function() {
        isDragging = false;
        container.style.cursor = 'move';
    });

    container.addEventListener('mouseleave', function() {
        isDragging = false;
        container.style.cursor = 'move';
    });

    // Mouse wheel to zoom
    container.addEventListener('wheel', function(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        currentZoom *= delta;
        currentZoom = Math.max(0.5, Math.min(3, currentZoom));
        updateTransform();
    });

    // Touch support for mobile
    let touchStartDistance = 0;
    container.addEventListener('touchstart', function(e) {
        if (e.touches.length === 2) {
            touchStartDistance = Math.hypot(
                e.touches[0].pageX - e.touches[1].pageX,
                e.touches[0].pageY - e.touches[1].pageY
            );
        } else if (e.touches.length === 1) {
            isDragging = true;
            startX = e.touches[0].clientX - translateX;
            startY = e.touches[0].clientY - translateY;
        }
    });

    container.addEventListener('touchmove', function(e) {
        if (e.touches.length === 2) {
            e.preventDefault();
            const touchDistance = Math.hypot(
                e.touches[0].pageX - e.touches[1].pageX,
                e.touches[0].pageY - e.touches[1].pageY
            );
            const delta = touchDistance / touchStartDistance;
            currentZoom *= delta;
            currentZoom = Math.max(0.5, Math.min(3, currentZoom));
            touchStartDistance = touchDistance;
            updateTransform();
        } else if (isDragging && e.touches.length === 1) {
            e.preventDefault();
            translateX = e.touches[0].clientX - startX;
            translateY = e.touches[0].clientY - startY;
            updateTransform();
        }
    });

    container.addEventListener('touchend', function() {
        isDragging = false;
    });
});

function updateTransform() {
    const svg = document.getElementById('mindmap-svg');
    svg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom})`;
}

function zoomIn() {
    currentZoom *= 1.2;
    currentZoom = Math.min(3, currentZoom);
    updateTransform();
}

function zoomOut() {
    currentZoom *= 0.8;
    currentZoom = Math.max(0.5, currentZoom);
    updateTransform();
}

function resetZoom() {
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    updateTransform();
}

function showNodeInfo(title, description) {
    alert(`📍 ${title}\n\n${description}\n\nคลิก OK เพื่อปิด`);
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>
@endpush

@endsection

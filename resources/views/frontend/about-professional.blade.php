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
                ['icon' => '🗄️', 'title' => 'Database Schema', 'href' => '#database'],
                ['icon' => '📅', 'title' => 'Development Timeline', 'href' => '#timeline'],
                ['icon' => '💎', 'title' => 'Investment Opportunity', 'href' => '#investment'],
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

<!-- Business Model Section -->
<div id="business-model" class="bg-gradient-to-br from-purple-50 to-pink-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">💰 โมเดลธุรกิจ</h2>
        <p class="text-xl text-gray-600 text-center mb-12">ระบบสร้างรายได้หลากหลายช่องทาง พร้อม MLM Commission Engine ที่ทรงพลัง</p>

        <!-- Revenue Streams -->
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <div class="card-premium rounded-2xl p-8 text-center">
                <div class="text-5xl mb-4">🛒</div>
                <h3 class="text-2xl font-bold mb-3">E-Commerce Sales</h3>
                <p class="text-gray-600 mb-4">รายได้จากการขายสินค้าผ่านระบบ Multi-Vendor Marketplace</p>
                <ul class="text-sm text-gray-700 text-left space-y-2">
                    <li>• Admin Store (Official Products)</li>
                    <li>• Vendor Commissions (10-30%)</li>
                    <li>• Shipping Fees</li>
                    <li>• Product Listing Fees</li>
                </ul>
            </div>

            <div class="card-premium rounded-2xl p-8 text-center">
                <div class="text-5xl mb-4">🔄</div>
                <h3 class="text-2xl font-bold mb-3">MLM Commissions</h3>
                <p class="text-gray-600 mb-4">รายได้จากระบบ Network Marketing แบบ 2 Plan</p>
                <ul class="text-sm text-gray-700 text-left space-y-2">
                    <li>• Unilevel Plan (7 ระดับ)</li>
                    <li>• Binary Plan (Left/Right)</li>
                    <li>• Rank Bonuses</li>
                    <li>• Matching Bonuses</li>
                </ul>
            </div>

            <div class="card-premium rounded-2xl p-8 text-center">
                <div class="text-5xl mb-4">💎</div>
                <h3 class="text-2xl font-bold mb-3">Premium Services</h3>
                <p class="text-gray-600 mb-4">รายได้จากบริการเสริมและ Subscription</p>
                <ul class="text-sm text-gray-700 text-left space-y-2">
                    <li>• Bot Rental Marketplace</li>
                    <li>• Premium AI Features</li>
                    <li>• Training & Certifications</li>
                    <li>• API Access Fees</li>
                </ul>
            </div>
        </div>

        <!-- Commission Flow Diagram -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-8 text-center">📊 Commission Flow Diagram</h3>
            <div class="bg-white rounded-xl p-8 shadow-inner">
                <svg viewBox="0 0 1000 600" class="w-full" style="max-height: 600px;">
                    <defs>
                        <marker id="arrow" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth">
                            <path d="M0,0 L0,6 L9,3 z" fill="#6366F1" />
                        </marker>
                        <linearGradient id="grad-flow-1" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#EC4899;stop-opacity:1" />
                        </linearGradient>
                    </defs>

                    <!-- Customer Purchase -->
                    <rect x="50" y="50" width="180" height="80" rx="10" fill="#10B981" />
                    <text x="140" y="85" text-anchor="middle" fill="white" font-size="16" font-weight="bold">ลูกค้าซื้อสินค้า</text>
                    <text x="140" y="110" text-anchor="middle" fill="white" font-size="14">฿10,000</text>

                    <!-- Arrow to System -->
                    <line x1="230" y1="90" x2="350" y2="90" stroke="#6366F1" stroke-width="3" marker-end="url(#arrow)" />
                    <text x="290" y="80" text-anchor="middle" fill="#6366F1" font-size="12" font-weight="bold">Order Created</text>

                    <!-- System Processing -->
                    <rect x="350" y="50" width="200" height="80" rx="10" fill="url(#grad-flow-1)" />
                    <text x="450" y="85" text-anchor="middle" fill="white" font-size="16" font-weight="bold">ระบบคำนวณ</text>
                    <text x="450" y="110" text-anchor="middle" fill="white" font-size="14">Commission Engine</text>

                    <!-- Unilevel Distribution -->
                    <line x1="450" y1="130" x2="250" y2="250" stroke="#8B5CF6" stroke-width="3" marker-end="url(#arrow)" />
                    <text x="350" y="180" text-anchor="middle" fill="#8B5CF6" font-size="12" font-weight="bold">Unilevel Plan</text>

                    <rect x="100" y="250" width="300" height="120" rx="10" fill="#8B5CF6" opacity="0.1" stroke="#8B5CF6" stroke-width="2" />
                    <text x="250" y="275" text-anchor="middle" fill="#8B5CF6" font-size="14" font-weight="bold">Unilevel Distribution</text>
                    <text x="250" y="300" text-anchor="middle" fill="#6B21A8" font-size="12">Level 1: 10% = ฿1,000</text>
                    <text x="250" y="320" text-anchor="middle" fill="#6B21A8" font-size="12">Level 2: 5% = ฿500</text>
                    <text x="250" y="340" text-anchor="middle" fill="#6B21A8" font-size="12">Level 3-7: 2% = ฿200 each</text>
                    <text x="250" y="360" text-anchor="middle" fill="#7C3AED" font-size="13" font-weight="bold">Total: ฿2,500</text>

                    <!-- Binary Distribution -->
                    <line x1="450" y1="130" x2="650" y2="250" stroke="#EC4899" stroke-width="3" marker-end="url(#arrow)" />
                    <text x="550" y="180" text-anchor="middle" fill="#EC4899" font-size="12" font-weight="bold">Binary Plan</text>

                    <rect x="500" y="250" width="300" height="120" rx="10" fill="#EC4899" opacity="0.1" stroke="#EC4899" stroke-width="2" />
                    <text x="650" y="275" text-anchor="middle" fill="#EC4899" font-size="14" font-weight="bold">Binary Distribution</text>
                    <text x="650" y="300" text-anchor="middle" fill="#BE185D" font-size="12">Left Leg PV: 500</text>
                    <text x="650" y="320" text-anchor="middle" fill="#BE185D" font-size="12">Right Leg PV: 500</text>
                    <text x="650" y="340" text-anchor="middle" fill="#BE185D" font-size="12">Pair Bonus: 10% = ฿100</text>
                    <text x="650" y="360" text-anchor="middle" fill="#DB2777" font-size="13" font-weight="bold">Total: ฿100 per pair</text>

                    <!-- Wallet Crediting -->
                    <line x1="250" y1="370" x2="450" y2="470" stroke="#10B981" stroke-width="3" marker-end="url(#arrow)" />
                    <line x1="650" y1="370" x2="450" y2="470" stroke="#10B981" stroke-width="3" marker-end="url(#arrow)" />

                    <rect x="300" y="470" width="300" height="80" rx="10" fill="#10B981" />
                    <text x="450" y="505" text-anchor="middle" fill="white" font-size="16" font-weight="bold">💰 Wallet System</text>
                    <text x="450" y="530" text-anchor="middle" fill="white" font-size="14">ยอดเงินถูกโอนเข้า Wallet</text>
                </svg>
            </div>

            <div class="mt-6 grid md:grid-cols-2 gap-4">
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-bold text-purple-900 mb-2">🔄 Unilevel Plan Features</h4>
                    <ul class="text-sm text-purple-800 space-y-1">
                        <li>• รองรับถึง 7 ระดับ (Configurable)</li>
                        <li>• คำนวณ Commission อัตโนมัติ</li>
                        <li>• Support Rank-based Percentages</li>
                        <li>• Real-time Balance Update</li>
                    </ul>
                </div>
                <div class="bg-pink-50 rounded-lg p-4">
                    <h4 class="font-bold text-pink-900 mb-2">⚖️ Binary Plan Features</h4>
                    <ul class="text-sm text-pink-800 space-y-1">
                        <li>• Left & Right Leg Tracking</li>
                        <li>• PV (Point Value) System</li>
                        <li>• Auto Pair Matching</li>
                        <li>• Spillover Support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Features Section -->
<div id="features" class="max-w-7xl mx-auto px-4 py-20">
    <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">⚙️ ฟีเจอร์ทั้งหมด</h2>
    <p class="text-xl text-gray-600 text-center mb-12">รายละเอียดฟีเจอร์ครบถ้วน 12 หมวดหลัก พร้อมระบบย่อยมากกว่า 100 ฟีเจอร์</p>

    <div class="space-y-6">
        <!-- MLM Features -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🔄</span>
                <span class="text-gradient">1. Multi-Level Marketing System</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Unilevel Plan', 'desc' => 'รองรับ 7 ระดับ พร้อม Rank Bonus'],
                    ['name' => 'Binary Plan', 'desc' => 'Left/Right Leg พร้อม Pair Matching'],
                    ['name' => 'Commission Engine', 'desc' => 'คำนวณค่าคอมมิชชั่นอัตโนมัติ'],
                    ['name' => 'PV System', 'desc' => 'Point Value Tracking & Management'],
                    ['name' => 'Rank Management', 'desc' => 'ระบบ Rank อัตโนมัติตาม Criteria'],
                    ['name' => 'Genealogy Tree', 'desc' => 'แผนผังโครงสร้างเครือข่าย Visual'],
                    ['name' => 'Spillover System', 'desc' => 'จัดสายงานอัตโนมัติ'],
                    ['name' => 'Matching Bonus', 'desc' => 'โบนัสจากทีม Downline'],
                    ['name' => 'Referral Tracking', 'desc' => 'ติดตาม Referral Code แบบ Real-time'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- E-Commerce Features -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🛒</span>
                <span class="text-gradient">2. E-Commerce Platform</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Product Management', 'desc' => 'จัดการสินค้า Variants, SKU, Stock'],
                    ['name' => 'Multi-Vendor', 'desc' => 'รองรับผู้ขายหลายราย'],
                    ['name' => 'Shopping Cart', 'desc' => 'ตะกร้าสินค้าแบบ Session & DB'],
                    ['name' => 'Order Processing', 'desc' => 'ระบบจัดการคำสั่งซื้อครบวงจร'],
                    ['name' => 'Inventory System', 'desc' => 'Stock Management & Auto-alerts'],
                    ['name' => 'Shipping Management', 'desc' => 'จัดการที่อยู่จัดส่งหลายที่'],
                    ['name' => 'Reviews & Ratings', 'desc' => 'รีวิวสินค้าพร้อม Image Upload'],
                    ['name' => 'Product Categories', 'desc' => 'หมวดหมู่แบบ Nested Unlimited'],
                    ['name' => 'Search & Filters', 'desc' => 'ค้นหาและกรองสินค้าขั้นสูง'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Wallet System -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">💰</span>
                <span class="text-gradient">3. Digital Wallet System</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Balance Management', 'desc' => 'ยอดเงินแบบ Real-time'],
                    ['name' => 'Transaction History', 'desc' => 'ประวัติทุกธุรกรรมแบบละเอียด'],
                    ['name' => 'Withdrawal System', 'desc' => 'ถอนเงินพร้อม Approval Flow'],
                    ['name' => 'Payment Methods', 'desc' => 'รองรับหลาย Payment Gateway'],
                    ['name' => 'PIN Security', 'desc' => '6-Digit PIN สำหรับทุกธุรกรรม'],
                    ['name' => '2FA Protection', 'desc' => 'Two-Factor Authentication'],
                    ['name' => 'Auto Credits', 'desc' => 'โอนเงิน Commission อัตโนมัติ'],
                    ['name' => 'Transfer System', 'desc' => 'โอนเงินระหว่างสมาชิก'],
                    ['name' => 'Audit Logs', 'desc' => 'Log ทุกการเปลี่ยนแปลง'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- AI Services -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🤖</span>
                <span class="text-gradient">4. AI Integration & Services</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Multi-AI Provider', 'desc' => 'OpenAI, Claude, Gemini, Groq'],
                    ['name' => 'LINE AI Bot', 'desc' => 'Chatbot อัจฉริยะบน LINE OA'],
                    ['name' => 'RAG System', 'desc' => 'Retrieval-Augmented Generation'],
                    ['name' => 'Knowledge Base', 'desc' => 'ฐานความรู้สำหรับ AI Training'],
                    ['name' => 'Prompt Management', 'desc' => 'จัดการ System Prompts'],
                    ['name' => 'Chat History', 'desc' => 'บันทึกประวัติการสนทนา'],
                    ['name' => 'Bot Marketplace', 'desc' => 'ให้เช่า AI Bot แบบรายเดือน'],
                    ['name' => 'Auto-Responses', 'desc' => 'ตอบกลับอัตโนมัติตาม Context'],
                    ['name' => 'Multi-Language', 'desc' => 'รองรับหลายภาษา'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- LINE Integration -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">💬</span>
                <span class="text-gradient">5. LINE Official Account</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'LINE Login', 'desc' => 'เข้าสู่ระบบด้วย LINE Account'],
                    ['name' => 'Account Linking', 'desc' => 'เชื่อมบัญชี LINE กับระบบ'],
                    ['name' => 'Flex Messages', 'desc' => 'ข้อความรูปแบบสวยงาม'],
                    ['name' => 'Rich Menu', 'desc' => 'สร้าง Rich Menu แบบ Visual'],
                    ['name' => 'Broadcast System', 'desc' => 'ส่งข้อความหมู่'],
                    ['name' => 'Webhook Handler', 'desc' => 'รับ Events จาก LINE'],
                    ['name' => 'Quick Replies', 'desc' => 'ปุ่มตอบกลับด่วน'],
                    ['name' => 'Image/Video Support', 'desc' => 'รองรับสื่อหลากหลาย'],
                    ['name' => 'Chat Widget', 'desc' => 'Widget สำหรับเว็บไซต์'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Security Features -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🔒</span>
                <span class="text-gradient">6. Security & Protection</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'KYC/OCR', 'desc' => 'ยืนยันตัวตนด้วย Google Vision API'],
                    ['name' => 'IP Blocking', 'desc' => 'บล็อก IP & CIDR Ranges'],
                    ['name' => 'Threat Detection', 'desc' => 'ตรวจจับภัยคุกคามอัตโนมัติ'],
                    ['name' => 'Auto-Ban System', 'desc' => 'แบนผู้ใช้ที่ผิดกฎอัตโนมัติ'],
                    ['name' => '2FA Authentication', 'desc' => 'ยืนยันตัวตน 2 ชั้น'],
                    ['name' => 'CSRF Protection', 'desc' => 'ป้องกัน Cross-Site Request'],
                    ['name' => 'XSS Prevention', 'desc' => 'ป้องกัน Cross-Site Scripting'],
                    ['name' => 'Rate Limiting', 'desc' => 'จำกัดจำนวน Request'],
                    ['name' => 'Audit Logging', 'desc' => 'บันทึก Activity ทุกอย่าง'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Admin Panel -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">⚡</span>
                <span class="text-gradient">7. Admin Control Panel</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Dashboard Analytics', 'desc' => 'สถิติแบบ Real-time พร้อม Charts'],
                    ['name' => 'User Management', 'desc' => 'จัดการผู้ใช้ทุก Role'],
                    ['name' => 'Settings Manager', 'desc' => 'ตั้งค่าระบบทุกส่วน'],
                    ['name' => 'Commission Settings', 'desc' => 'ปรับค่า Commission แต่ละระดับ'],
                    ['name' => 'Product Approval', 'desc' => 'อนุมัติสินค้าจาก Vendor'],
                    ['name' => 'Withdrawal Approval', 'desc' => 'อนุมัติคำขอถอนเงิน'],
                    ['name' => 'Reports Generator', 'desc' => 'สร้างรายงานทุกประเภท'],
                    ['name' => 'Email Templates', 'desc' => 'จัดการ Email Templates'],
                    ['name' => 'System Monitoring', 'desc' => 'ตรวจสอบสุขภาพระบบ'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- User Features -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">👤</span>
                <span class="text-gradient">8. User Dashboard</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Personal Dashboard', 'desc' => 'แดชบอร์ดส่วนตัวพร้อมสถิติ'],
                    ['name' => 'Earning Reports', 'desc' => 'รายงานรายได้แบบละเอียด'],
                    ['name' => 'Network View', 'desc' => 'ดูเครือข่าย Downline'],
                    ['name' => 'My Orders', 'desc' => 'ประวัติคำสั่งซื้อทั้งหมด'],
                    ['name' => 'Profile Management', 'desc' => 'จัดการข้อมูลส่วนตัว'],
                    ['name' => 'KYC Verification', 'desc' => 'ยืนยันตัวตนด้วยบัตร ID'],
                    ['name' => 'Bank Accounts', 'desc' => 'จัดการบัญชีธนาคาร'],
                    ['name' => 'Referral Links', 'desc' => 'ลิงก์แนะนำพร้อม QR Code'],
                    ['name' => 'Notifications', 'desc' => 'แจ้งเตือนแบบ Real-time'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Seller/Vendor Features -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🏪</span>
                <span class="text-gradient">9. Seller/Vendor Panel</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Store Management', 'desc' => 'จัดการร้านค้าส่วนตัว'],
                    ['name' => 'Product Listing', 'desc' => 'เพิ่มสินค้าได้ไม่จำกัด'],
                    ['name' => 'Order Management', 'desc' => 'จัดการคำสั่งซื้อ'],
                    ['name' => 'Inventory Control', 'desc' => 'ควบคุม Stock สินค้า'],
                    ['name' => 'Sales Analytics', 'desc' => 'วิเคราะห์ยอดขาย'],
                    ['name' => 'Earnings Reports', 'desc' => 'รายงานรายได้จากขายสินค้า'],
                    ['name' => 'Shipping Settings', 'desc' => 'ตั้งค่าการจัดส่ง'],
                    ['name' => 'Store Customization', 'desc' => 'ปรับแต่งหน้าร้าน'],
                    ['name' => 'Customer Reviews', 'desc' => 'จัดการรีวิวลูกค้า'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- System Features -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🔧</span>
                <span class="text-gradient">10. System & Infrastructure</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Setup Wizard', 'desc' => 'ติดตั้งระบบง่ายๆ ครั้งแรก'],
                    ['name' => 'License System', 'desc' => 'ตรวจสอบ License Key'],
                    ['name' => 'Multi-Language', 'desc' => 'รองรับหลายภาษา'],
                    ['name' => 'Email System', 'desc' => 'ส่ง Email อัตโนมัติ'],
                    ['name' => 'Notification System', 'desc' => 'แจ้งเตือนหลายช่องทาง'],
                    ['name' => 'Backup System', 'desc' => 'สำรองข้อมูลอัตโนมัติ'],
                    ['name' => 'Cache Management', 'desc' => 'ระบบ Cache เพิ่มความเร็ว'],
                    ['name' => 'Queue System', 'desc' => 'ประมวลผล Jobs แบบ Async'],
                    ['name' => 'Error Logging', 'desc' => 'บันทึก Error แบบละเอียด'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- API & Integration -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">🔌</span>
                <span class="text-gradient">11. API & External Integration</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'REST API', 'desc' => '20+ Endpoints สำหรับ Integration'],
                    ['name' => 'Translation API', 'desc' => 'Google Translate Integration'],
                    ['name' => 'Payment Gateways', 'desc' => 'รองรับหลาย Gateway'],
                    ['name' => 'SMS Gateway', 'desc' => 'ส่ง SMS OTP'],
                    ['name' => 'Google Vision API', 'desc' => 'OCR & Image Recognition'],
                    ['name' => 'Social Login', 'desc' => 'Login ผ่าน LINE, Google, FB'],
                    ['name' => 'Webhook Support', 'desc' => 'รับ-ส่ง Webhooks'],
                    ['name' => 'OAuth2', 'desc' => 'OAuth2 Authentication'],
                    ['name' => 'API Documentation', 'desc' => 'เอกสาร API ครบถ้วน'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Mobile & Responsive -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                <span class="text-5xl">📱</span>
                <span class="text-gradient">12. Mobile & UX Features</span>
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Responsive Design', 'desc' => 'ใช้งานได้ทุกอุปกรณ์'],
                    ['name' => 'PWA Support', 'desc' => 'Progressive Web App'],
                    ['name' => 'Touch Optimized', 'desc' => 'เหมาะกับ Touch Screen'],
                    ['name' => 'Fast Loading', 'desc' => 'โหลดเร็วด้วย Vite'],
                    ['name' => 'Lazy Loading', 'desc' => 'โหลดรูปภาพแบบ Lazy'],
                    ['name' => 'Dark Mode', 'desc' => 'โหมดมืด (Optional)'],
                    ['name' => 'Accessibility', 'desc' => 'รองรับผู้พิการ'],
                    ['name' => 'SEO Optimized', 'desc' => 'เหมาะกับ Search Engine'],
                    ['name' => 'Analytics', 'desc' => 'ติดตาม User Behavior'],
                ] as $feature)
                <div class="stat-box">
                    <div class="font-bold text-lg">{{ $feature['name'] }}</div>
                    <div class="text-sm text-gray-600 mt-1">{{ $feature['desc'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Technology Stack Section -->
<div id="technology" class="bg-gradient-to-br from-blue-50 to-indigo-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">🛠️ Technology Stack</h2>
        <p class="text-xl text-gray-600 text-center mb-12">เทคโนโลยีที่ใช้ในการพัฒนาระบบแบบครบวงจร</p>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Backend -->
            <div class="card-premium rounded-2xl p-8">
                <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                    <span class="text-4xl">⚙️</span>
                    <span>Backend Technologies</span>
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-red-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🐘</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Laravel 11.x</h4>
                            <p class="text-sm text-gray-600">PHP Framework with Eloquent ORM, Queue System, Events</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-purple-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">💾</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">MySQL 8.0+</h4>
                            <p class="text-sm text-gray-600">Relational Database with 105 Tables, Optimized Queries</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-red-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🔴</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Redis</h4>
                            <p class="text-sm text-gray-600">Caching, Session Storage, Queue Driver</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-blue-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🔵</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">PHP 8.1+</h4>
                            <p class="text-sm text-gray-600">Modern PHP with Type Hints, Enums, Attributes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Frontend -->
            <div class="card-premium rounded-2xl p-8">
                <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                    <span class="text-4xl">🎨</span>
                    <span>Frontend Technologies</span>
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-green-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🌿</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Alpine.js 3.x</h4>
                            <p class="text-sm text-gray-600">Lightweight Reactive Framework for Interactivity</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-cyan-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🎨</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Tailwind CSS</h4>
                            <p class="text-sm text-gray-600">Utility-First CSS Framework for Rapid Development</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-orange-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">⚡</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Vite</h4>
                            <p class="text-sm text-gray-600">Next Generation Frontend Build Tool</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-pink-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">📊</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Chart.js & D3.js</h4>
                            <p class="text-sm text-gray-600">Data Visualization Libraries</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI & Cloud Services -->
            <div class="card-premium rounded-2xl p-8">
                <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                    <span class="text-4xl">☁️</span>
                    <span>Cloud & AI Services</span>
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-green-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🤖</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">OpenAI GPT-4</h4>
                            <p class="text-sm text-gray-600">Advanced AI for Chatbot & Text Generation</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-purple-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🧠</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Anthropic Claude</h4>
                            <p class="text-sm text-gray-600">Claude AI for Complex Reasoning</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-blue-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🔷</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Google Cloud</h4>
                            <p class="text-sm text-gray-600">Vision API (OCR), Translate API, Gemini AI</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-green-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">💬</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">LINE Messaging API</h4>
                            <p class="text-sm text-gray-600">Official LINE Platform Integration</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DevOps & Tools -->
            <div class="card-premium rounded-2xl p-8">
                <h3 class="text-3xl font-bold mb-6 flex items-center gap-3">
                    <span class="text-4xl">🚀</span>
                    <span>DevOps & Tools</span>
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-orange-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🐙</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Git & GitHub</h4>
                            <p class="text-sm text-gray-600">Version Control with Private Repository</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-blue-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🐳</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Docker (Optional)</h4>
                            <p class="text-sm text-gray-600">Containerization for Deployment</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-green-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">📦</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Composer & NPM</h4>
                            <p class="text-sm text-gray-600">Dependency Management</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 flex items-center justify-center bg-yellow-100 rounded-lg flex-shrink-0">
                            <span class="text-3xl">🔧</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Laravel Forge/Vapor</h4>
                            <p class="text-sm text-gray-600">Server Management & Deployment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Specifications -->
        <div class="card-premium rounded-2xl p-8 mt-8">
            <h3 class="text-2xl font-bold mb-6 text-center">📋 Technical Specifications</h3>
            <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">113+</div>
                    <div class="text-sm text-gray-600 mt-1">Eloquent Models</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">105</div>
                    <div class="text-sm text-gray-600 mt-1">Database Tables</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">91</div>
                    <div class="text-sm text-gray-600 mt-1">HTTP Controllers</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">136</div>
                    <div class="text-sm text-gray-600 mt-1">Migrations</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">30+</div>
                    <div class="text-sm text-gray-600 mt-1">Services</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">20+</div>
                    <div class="text-sm text-gray-600 mt-1">API Endpoints</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">100+</div>
                    <div class="text-sm text-gray-600 mt-1">Blade Views</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gradient">12</div>
                    <div class="text-sm text-gray-600 mt-1">Feature Modules</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Database Schema Section -->
<div id="database" class="max-w-7xl mx-auto px-4 py-20">
    <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">🗄️ Database Architecture</h2>
    <p class="text-xl text-gray-600 text-center mb-12">โครงสร้างฐานข้อมูล 105 ตาราง แบบ Normalized & Optimized</p>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Core Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">👥</span>
                <span>User & Authentication (8)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• users</li>
                <li>• password_reset_tokens</li>
                <li>• sessions</li>
                <li>• personal_access_tokens</li>
                <li>• failed_jobs</li>
                <li>• jobs</li>
                <li>• job_batches</li>
                <li>• cache</li>
            </ul>
        </div>

        <!-- MLM Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">🔄</span>
                <span>MLM System (15+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• affiliates</li>
                <li>• commissions</li>
                <li>• mlm_settings</li>
                <li>• unilevel_settings</li>
                <li>• binary_settings</li>
                <li>• ranks</li>
                <li>• rank_achievements</li>
                <li>• genealogy_trees</li>
                <li>• pv_transactions</li>
                <li>• matching_bonuses</li>
                <li>• + 5 more tables...</li>
            </ul>
        </div>

        <!-- E-Commerce Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">🛒</span>
                <span>E-Commerce (25+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• products</li>
                <li>• product_variants</li>
                <li>• product_categories</li>
                <li>• orders</li>
                <li>• order_items</li>
                <li>• carts</li>
                <li>• cart_items</li>
                <li>• vendors</li>
                <li>• vendor_stores</li>
                <li>• reviews</li>
                <li>• inventory</li>
                <li>• shipping_addresses</li>
                <li>• + 13 more tables...</li>
            </ul>
        </div>

        <!-- Wallet Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">💰</span>
                <span>Wallet System (10+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• wallets</li>
                <li>• wallet_transactions</li>
                <li>• withdrawals</li>
                <li>• withdrawal_methods</li>
                <li>• bank_accounts</li>
                <li>• payment_gateways</li>
                <li>• payment_logs</li>
                <li>• wallet_pins</li>
                <li>• transfer_history</li>
                <li>• + 2 more tables...</li>
            </ul>
        </div>

        <!-- AI & LINE Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">🤖</span>
                <span>AI & LINE (12+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• ai_bots</li>
                <li>• bot_conversations</li>
                <li>• bot_messages</li>
                <li>• knowledge_base</li>
                <li>• bot_rentals</li>
                <li>• line_users</li>
                <li>• line_rich_menus</li>
                <li>• line_broadcasts</li>
                <li>• ai_providers</li>
                <li>• prompt_templates</li>
                <li>• + 3 more tables...</li>
            </ul>
        </div>

        <!-- System Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">⚙️</span>
                <span>System & Settings (20+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• settings</li>
                <li>• pages</li>
                <li>• notifications</li>
                <li>• activity_logs</li>
                <li>• ip_blocks</li>
                <li>• threat_detections</li>
                <li>• kyc_verifications</li>
                <li>• email_templates</li>
                <li>• translations</li>
                <li>• language_packs</li>
                <li>• migrations</li>
                <li>• + 9 more tables...</li>
            </ul>
        </div>

        <!-- Security Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">🔒</span>
                <span>Security (8+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• two_factor_auth</li>
                <li>• login_attempts</li>
                <li>• banned_users</li>
                <li>• security_logs</li>
                <li>• ip_whitelist</li>
                <li>• api_tokens</li>
                <li>• rate_limits</li>
                <li>• + 2 more tables...</li>
            </ul>
        </div>

        <!-- Reports & Analytics -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">📊</span>
                <span>Reports & Analytics (7+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• sales_reports</li>
                <li>• commission_reports</li>
                <li>• user_analytics</li>
                <li>• product_analytics</li>
                <li>• earning_summaries</li>
                <li>• performance_metrics</li>
                <li>• + 2 more tables...</li>
            </ul>
        </div>

        <!-- Misc Tables -->
        <div class="card-premium rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="text-2xl">📦</span>
                <span>Others (5+)</span>
            </h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li>• media</li>
                <li>• tags</li>
                <li>• faqs</li>
                <li>• support_tickets</li>
                <li>• + 2 more tables...</li>
            </ul>
        </div>
    </div>

    <div class="card-premium rounded-2xl p-8 mt-8">
        <h3 class="text-2xl font-bold mb-6 text-center">🔗 Key Relationships</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-purple-50 rounded-lg p-4">
                <h4 class="font-bold text-purple-900 mb-3">👤 User Relations</h4>
                <ul class="text-sm text-purple-800 space-y-2">
                    <li>→ hasOne: Affiliate, Wallet, Vendor</li>
                    <li>→ hasMany: Orders, Transactions, Notifications</li>
                    <li>→ belongsToMany: Roles, Permissions</li>
                </ul>
            </div>
            <div class="bg-pink-50 rounded-lg p-4">
                <h4 class="font-bold text-pink-900 mb-3">🔄 Affiliate Relations</h4>
                <ul class="text-sm text-pink-800 space-y-2">
                    <li>→ belongsTo: User, Parent (Sponsor)</li>
                    <li>→ hasMany: Children, Commissions</li>
                    <li>→ hasManyThrough: Downlines</li>
                </ul>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-bold text-blue-900 mb-3">🛒 Product Relations</h4>
                <ul class="text-sm text-blue-800 space-y-2">
                    <li>→ belongsTo: Vendor, Category</li>
                    <li>→ hasMany: Variants, Reviews, OrderItems</li>
                    <li>→ morphMany: Media</li>
                </ul>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <h4 class="font-bold text-green-900 mb-3">💰 Order Relations</h4>
                <ul class="text-sm text-green-800 space-y-2">
                    <li>→ belongsTo: User, ShippingAddress</li>
                    <li>→ hasMany: OrderItems, Commissions</li>
                    <li>→ hasOne: PaymentLog</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Development Timeline -->
<div id="timeline" class="bg-gradient-to-br from-indigo-50 to-purple-50 py-20">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">📅 Development Timeline</h2>
        <p class="text-xl text-gray-600 text-center mb-12">พัฒนาอย่างต่อเนื่องด้วย 159 เวอร์ชัน และนับต่อไป</p>

        <div class="relative">
            <!-- Timeline Line -->
            <div class="absolute left-1/2 transform -translate-x-1/2 w-1 h-full bg-gradient-to-b from-[var(--primary)] to-[var(--secondary)]"></div>

            <!-- Phase 1 -->
            <div class="mb-16 flex items-center">
                <div class="w-1/2 pr-8 text-right">
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-2xl font-bold mb-2">Phase 1: Foundation</h3>
                        <p class="text-sm text-gray-600 mb-3">v1.0.0 - v1.30.0</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ Laravel 11 Setup</li>
                            <li>✓ Authentication System</li>
                            <li>✓ Database Schema Design</li>
                            <li>✓ Basic User Management</li>
                            <li>✓ Role & Permission System</li>
                        </ul>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-[var(--primary)] to-[var(--secondary)] rounded-full flex items-center justify-center text-white font-bold text-xl z-10">
                    1
                </div>
                <div class="w-1/2"></div>
            </div>

            <!-- Phase 2 -->
            <div class="mb-16 flex items-center">
                <div class="w-1/2"></div>
                <div class="w-12 h-12 bg-gradient-to-br from-[var(--primary)] to-[var(--secondary)] rounded-full flex items-center justify-center text-white font-bold text-xl z-10">
                    2
                </div>
                <div class="w-1/2 pl-8">
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-2xl font-bold mb-2">Phase 2: MLM Core</h3>
                        <p class="text-sm text-gray-600 mb-3">v1.31.0 - v1.60.0</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ Unilevel Plan Implementation</li>
                            <li>✓ Binary Plan Implementation</li>
                            <li>✓ Commission Calculation Engine</li>
                            <li>✓ PV System</li>
                            <li>✓ Genealogy Tree Visualization</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Phase 3 -->
            <div class="mb-16 flex items-center">
                <div class="w-1/2 pr-8 text-right">
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-2xl font-bold mb-2">Phase 3: E-Commerce</h3>
                        <p class="text-sm text-gray-600 mb-3">v1.61.0 - v1.90.0</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ Product Management System</li>
                            <li>✓ Multi-Vendor Marketplace</li>
                            <li>✓ Shopping Cart & Checkout</li>
                            <li>✓ Order Processing</li>
                            <li>✓ Payment Gateway Integration</li>
                            <li>✓ Inventory Management</li>
                        </ul>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-[var(--primary)] to-[var(--secondary)] rounded-full flex items-center justify-center text-white font-bold text-xl z-10">
                    3
                </div>
                <div class="w-1/2"></div>
            </div>

            <!-- Phase 4 -->
            <div class="mb-16 flex items-center">
                <div class="w-1/2"></div>
                <div class="w-12 h-12 bg-gradient-to-br from-[var(--primary)] to-[var(--secondary)] rounded-full flex items-center justify-center text-white font-bold text-xl z-10">
                    4
                </div>
                <div class="w-1/2 pl-8">
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-2xl font-bold mb-2">Phase 4: Wallet & Finance</h3>
                        <p class="text-sm text-gray-600 mb-3">v1.91.0 - v1.110.0</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ Digital Wallet System</li>
                            <li>✓ Transaction Processing</li>
                            <li>✓ Withdrawal System</li>
                            <li>✓ PIN & 2FA Security</li>
                            <li>✓ Bank Account Management</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Phase 5 -->
            <div class="mb-16 flex items-center">
                <div class="w-1/2 pr-8 text-right">
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-2xl font-bold mb-2">Phase 5: AI & LINE</h3>
                        <p class="text-sm text-gray-600 mb-3">v1.111.0 - v1.140.0</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ LINE Official Account Integration</li>
                            <li>✓ AI Chatbot (Multi-Provider)</li>
                            <li>✓ RAG Knowledge Base</li>
                            <li>✓ Rich Menu Builder</li>
                            <li>✓ Bot Rental Marketplace</li>
                            <li>✓ Flex Message Templates</li>
                        </ul>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-[var(--primary)] to-[var(--secondary)] rounded-full flex items-center justify-center text-white font-bold text-xl z-10">
                    5
                </div>
                <div class="w-1/2"></div>
            </div>

            <!-- Phase 6 -->
            <div class="flex items-center">
                <div class="w-1/2"></div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-xl z-10 animate-pulse">
                    6
                </div>
                <div class="w-1/2 pl-8">
                    <div class="card-premium rounded-2xl p-6 border-2 border-green-500">
                        <h3 class="text-2xl font-bold mb-2 text-green-700">Phase 6: Security & Polish</h3>
                        <p class="text-sm text-gray-600 mb-3">v1.141.0 - v1.159.0 (Current)</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ KYC/OCR with Google Vision</li>
                            <li>✓ Advanced Security Features</li>
                            <li>✓ IP Blocking & Threat Detection</li>
                            <li>✓ Setup Wizard</li>
                            <li>✓ Multi-Language Support</li>
                            <li>✓ Performance Optimization</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Investment Opportunity -->
<div id="investment" class="max-w-7xl mx-auto px-4 py-20">
    <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">💎 Investment Opportunity</h2>
    <p class="text-xl text-gray-600 text-center mb-12">โอกาสการลงทุนในแพลตฟอร์ม MLM & E-Commerce ระดับ Enterprise</p>

    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <!-- Value Proposition -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6">🎯 มูลค่าโครงการ</h3>
            <div class="space-y-6">
                <div>
                    <h4 class="font-bold text-xl mb-2">Development Investment</h4>
                    <p class="text-3xl font-bold text-gradient">฿5,000,000+</p>
                    <p class="text-sm text-gray-600 mt-1">ค่าใช้จ่ายในการพัฒนาจริง (1,000+ ชั่วโมง)</p>
                </div>
                <div>
                    <h4 class="font-bold text-xl mb-2">Technology Stack Value</h4>
                    <ul class="text-sm text-gray-700 space-y-2 mt-3">
                        <li>• Laravel Enterprise Framework</li>
                        <li>• Multi-AI Provider Integration</li>
                        <li>• Google Cloud Services</li>
                        <li>• LINE Official Account Premium</li>
                        <li>• 100+ Premium Libraries & Packages</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Revenue Potential -->
        <div class="card-premium rounded-2xl p-8">
            <h3 class="text-3xl font-bold mb-6">📈 ศักยภาพรายได้</h3>
            <div class="space-y-4">
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-bold text-green-900">E-Commerce Sales</h4>
                    <p class="text-2xl font-bold text-green-600 mt-2">฿500K - ฿2M/month</p>
                    <p class="text-xs text-green-700 mt-1">Based on 1,000-5,000 active users</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-bold text-purple-900">MLM Commissions</h4>
                    <p class="text-2xl font-bold text-purple-600 mt-2">฿200K - ฿1M/month</p>
                    <p class="text-xs text-purple-700 mt-1">Network growth & recurring sales</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-bold text-blue-900">Premium Services</h4>
                    <p class="text-2xl font-bold text-blue-600 mt-2">฿100K - ฿500K/month</p>
                    <p class="text-xs text-blue-700 mt-1">Bot rentals, AI features, training</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ROI Projection -->
    <div class="card-premium rounded-2xl p-8">
        <h3 class="text-3xl font-bold mb-6 text-center">📊 ROI Projection (3 Years)</h3>
        <div class="bg-white rounded-xl p-6">
            <div class="grid md:grid-cols-3 gap-6 mb-6">
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-2">Year 1</div>
                    <div class="text-3xl font-bold text-gradient">฿10M</div>
                    <div class="text-xs text-gray-500 mt-1">Building User Base</div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-2">Year 2</div>
                    <div class="text-3xl font-bold text-gradient">฿30M</div>
                    <div class="text-xs text-gray-500 mt-1">Network Expansion</div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-600 mb-2">Year 3</div>
                    <div class="text-3xl font-bold text-gradient">฿80M</div>
                    <div class="text-xs text-gray-500 mt-1">Market Leadership</div>
                </div>
            </div>

            <div class="h-64 flex items-end justify-around gap-4">
                <div class="flex-1 bg-gradient-to-t from-blue-500 to-blue-300 rounded-t-lg relative" style="height: 25%;">
                    <div class="absolute -top-8 left-0 right-0 text-center font-bold text-blue-600">10M</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-purple-500 to-purple-300 rounded-t-lg relative" style="height: 60%;">
                    <div class="absolute -top-8 left-0 right-0 text-center font-bold text-purple-600">30M</div>
                </div>
                <div class="flex-1 bg-gradient-to-t from-pink-500 to-pink-300 rounded-t-lg relative" style="height: 100%;">
                    <div class="absolute -top-8 left-0 right-0 text-center font-bold text-pink-600">80M</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Investment Benefits -->
    <div class="grid md:grid-cols-3 gap-6 mt-8">
        <div class="card-premium rounded-2xl p-6 text-center">
            <div class="text-5xl mb-4">🚀</div>
            <h3 class="text-xl font-bold mb-3">Ready to Launch</h3>
            <p class="text-gray-600 text-sm">ไม่ต้องรอพัฒนา ใช้งานได้ทันที พร้อม Setup Wizard</p>
        </div>
        <div class="card-premium rounded-2xl p-6 text-center">
            <div class="text-5xl mb-4">💰</div>
            <h3 class="text-xl font-bold mb-3">Multiple Revenue Streams</h3>
            <p class="text-gray-600 text-sm">สร้างรายได้จากหลายช่องทาง ลดความเสี่ยง</p>
        </div>
        <div class="card-premium rounded-2xl p-6 text-center">
            <div class="text-5xl mb-4">🔄</div>
            <h3 class="text-xl font-bold mb-3">Scalable Architecture</h3>
            <p class="text-gray-600 text-sm">ขยายธุรกิจได้ไม่จำกัด รองรับผู้ใช้ล้านคน</p>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="bg-gradient-to-r from-[var(--primary)] to-[var(--secondary)] py-20">
    <div class="max-w-4xl mx-auto px-4 text-center text-white">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">🎉 Ready to Build Your MLM Empire?</h2>
        <p class="text-xl mb-8 opacity-90">
            แพลตฟอร์มที่มีทุกอย่างพร้อม ไม่ต้องพัฒนาเอง ลดเวลา 12 เดือน ประหยัดงบ 5 ล้านบาท
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-[var(--primary)] font-bold rounded-full shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all">
                <span>เริ่มต้นใช้งานฟรี</span>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-full hover:bg-white hover:text-[var(--primary)] transition-all">
                <span>ติดต่อสอบถาม</span>
            </a>
        </div>
        <p class="text-sm mt-6 opacity-75">🔒 รับประกันความปลอดภัย 100% พร้อม Support 24/7</p>
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

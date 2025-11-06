@extends('layouts.app')

@section('content')
@php
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vis-network@10.0.2/dist/dist/vis-network.min.css">
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

/* Mind Map Container */
#mindmap-container {
    width: 100%;
    height: 800px;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
}

/* System Diagram Container */
#system-diagram {
    width: 100%;
    height: 1000px;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    background: #f9fafb;
}

.zoom-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 100;
    display: flex;
    gap: 8px;
}

.zoom-btn {
    width: 40px;
    height: 40px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-center;
    cursor: pointer;
    font-size: 20px;
    font-weight: bold;
    transition: all 0.2s;
}

.zoom-btn:hover {
    background: {{ $primaryColor }};
    color: white;
    border-color: {{ $primaryColor }};
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

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.feature-card-detailed {
    background: white;
    border-radius: 16px;
    padding: 24px;
    border: 2px solid #f3f4f6;
    transition: all 0.3s ease;
}

.feature-card-detailed:hover {
    border-color: {{ $primaryColor }};
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
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
}

.stat-box {
    background: linear-gradient(135deg, {{ $primaryColor }}10 0%, {{ $secondaryColor }}10 100%);
    border-left: 4px solid {{ $primaryColor }};
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 16px;
}

.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, {{ $primaryColor }}, {{ $secondaryColor }});
}

.timeline-item {
    position: relative;
    margin-bottom: 40px;
}

.timeline-dot {
    position: absolute;
    left: -32px;
    top: 8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: {{ $primaryColor }};
    border: 4px solid white;
    box-shadow: 0 0 0 4px {{ $primaryColor }}40;
}

.code-block {
    background: #1e293b;
    color: #e2e8f0;
    padding: 24px;
    border-radius: 12px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.6;
}

.table-responsive {
    overflow-x: auto;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.table-modern {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.table-modern thead {
    background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
    color: white;
}

.table-modern th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
}

.table-modern td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.table-modern tbody tr:hover {
    background: #f9fafb;
}

@media (max-width: 768px) {
    #mindmap-container,
    #system-diagram {
        height: 500px;
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
                ['icon' => '📊', 'title' => 'Database Schema', 'href' => '#database'],
                ['icon' => '🔌', 'title' => 'API & Integration', 'href' => '#api'],
                ['icon' => '📅', 'title' => 'Development Timeline', 'href' => '#timeline'],
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
        <p class="text-xl text-gray-600 text-center mb-12">แผนภาพแสดงความเชื่อมโยงของระบบทั้งหมดแบบ Interactive (Zoom/Pan ได้)</p>

        <div class="card-premium rounded-2xl p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    💡 <strong>คำแนะนำ:</strong> ใช้ Mouse Wheel เพื่อ Zoom, ลากเพื่อ Pan, คลิก Node เพื่อดูรายละเอียด
                </div>
                <div class="zoom-controls relative">
                    <button onclick="zoomIn()" class="zoom-btn">+</button>
                    <button onclick="zoomOut()" class="zoom-btn">−</button>
                    <button onclick="resetZoom()" class="zoom-btn">⟲</button>
                </div>
            </div>
        </div>

        <div id="mindmap-container"></div>
    </div>
</div>

<!-- System Architecture Diagram -->
<div id="architecture" class="max-w-7xl mx-auto px-4 py-20">
    <h2 class="text-4xl md:text-5xl font-bold mb-4 section-title text-center">🏗️ System Architecture</h2>
    <p class="text-xl text-gray-600 text-center mb-12">สถาปัตยกรรมระบบแบบ Layered Architecture พร้อม Integration ครบวงจร</p>

    <div id="system-diagram"></div>

    <!-- Architecture Details -->
    <div class="grid md:grid-cols-2 gap-6 mt-12">
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

{{-- Part 2 will continue with Business Model, Features, etc. --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vis-network@10.0.2/dist/dist/vis-network.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let network = null;

// Initialize Mind Map
document.addEventListener('DOMContentLoaded', function() {
    initMindMap();
    initSystemDiagram();
});

function initMindMap() {
    const container = document.getElementById('mindmap-container');

    // Define nodes (ระบบทั้งหมด)
    const nodes = new vis.DataSet([
        // Core System
        {id: 1, label: 'TP-Affiliate\nPlatform', level: 0, color: {background: '#3B82F6', border: '#2563EB'}, font: {color: 'white', size: 18, bold: true}, shape: 'box', margin: 15},

        // Main Modules (Level 1)
        {id: 10, label: 'MLM System', level: 1, color: {background: '#8B5CF6', border: '#7C3AED'}, font: {color: 'white', size: 14}, shape: 'box'},
        {id: 20, label: 'E-Commerce', level: 1, color: {background: '#EC4899', border: '#DB2777'}, font: {color: 'white', size: 14}, shape: 'box'},
        {id: 30, label: 'Wallet System', level: 1, color: {background: '#10B981', border: '#059669'}, font: {color: 'white', size: 14}, shape: 'box'},
        {id: 40, label: 'LINE Integration', level: 1, color: {background: '#F59E0B', border: '#D97706'}, font: {color: 'white', size: 14}, shape: 'box'},
        {id: 50, label: 'AI Services', level: 1, color: {background: '#06B6D4', border: '#0891B2'}, font: {color: 'white', size: 14}, shape: 'box'},
        {id: 60, label: 'Security', level: 1, color: {background: '#EF4444', border: '#DC2626'}, font: {color: 'white', size: 14}, shape: 'box'},
        {id: 70, label: 'Admin Panel', level: 1, color: {background: '#6366F1', border: '#4F46E5'}, font: {color: 'white', size: 14}, shape: 'box'},

        // MLM Sub-modules (Level 2)
        {id: 11, label: 'Unilevel Plan', level: 2, group: 'mlm'},
        {id: 12, label: 'Binary Plan', level: 2, group: 'mlm'},
        {id: 13, label: 'Commission\nCalculation', level: 2, group: 'mlm'},
        {id: 14, label: 'PV System', level: 2, group: 'mlm'},
        {id: 15, label: 'Rank System', level: 2, group: 'mlm'},
        {id: 16, label: 'Genealogy Tree', level: 2, group: 'mlm'},

        // E-Commerce Sub-modules
        {id: 21, label: 'Product\nManagement', level: 2, group: 'ecommerce'},
        {id: 22, label: 'Shopping Cart', level: 2, group: 'ecommerce'},
        {id: 23, label: 'Order Processing', level: 2, group: 'ecommerce'},
        {id: 24, label: 'Multi-Vendor', level: 2, group: 'ecommerce'},
        {id: 25, label: 'Inventory', level: 2, group: 'ecommerce'},
        {id: 26, label: 'Reviews & Ratings', level: 2, group: 'ecommerce'},

        // Wallet Sub-modules
        {id: 31, label: 'Balance\nManagement', level: 2, group: 'wallet'},
        {id: 32, label: 'Transactions', level: 2, group: 'wallet'},
        {id: 33, label: 'Withdrawals', level: 2, group: 'wallet'},
        {id: 34, label: 'Payment Methods', level: 2, group: 'wallet'},
        {id: 35, label: '2FA & PIN', level: 2, group: 'wallet'},

        // LINE Sub-modules
        {id: 41, label: 'LINE Bot AI', level: 2, group: 'line'},
        {id: 42, label: 'Flex Messages', level: 2, group: 'line'},
        {id: 43, label: 'Rich Menu', level: 2, group: 'line'},
        {id: 44, label: 'Broadcast', level: 2, group: 'line'},
        {id: 45, label: 'Chat Widget', level: 2, group: 'line'},

        // AI Sub-modules
        {id: 51, label: 'OpenAI GPT', level: 2, group: 'ai'},
        {id: 52, label: 'Claude', level: 2, group: 'ai'},
        {id: 53, label: 'Google Gemini', level: 2, group: 'ai'},
        {id: 54, label: 'RAG System', level: 2, group: 'ai'},
        {id: 55, label: 'Knowledge Base', level: 2, group: 'ai'},

        // Security Sub-modules
        {id: 61, label: 'KYC/OCR', level: 2, group: 'security'},
        {id: 62, label: 'IP Blocking', level: 2, group: 'security'},
        {id: 63, label: 'Threat Detection', level: 2, group: 'security'},
        {id: 64, label: 'Auto-Ban', level: 2, group: 'security'},

        // Admin Sub-modules
        {id: 71, label: 'Dashboard', level: 2, group: 'admin'},
        {id: 72, label: 'User Management', level: 2, group: 'admin'},
        {id: 73, label: 'Settings', level: 2, group: 'admin'},
        {id: 74, label: 'Reports', level: 2, group: 'admin'},
    ]);

    // Define edges (connections)
    const edges = new vis.DataSet([
        // Core to Main Modules
        {from: 1, to: 10, width: 3, color: {color: '#3B82F6'}},
        {from: 1, to: 20, width: 3, color: {color: '#3B82F6'}},
        {from: 1, to: 30, width: 3, color: {color: '#3B82F6'}},
        {from: 1, to: 40, width: 3, color: {color: '#3B82F6'}},
        {from: 1, to: 50, width: 3, color: {color: '#3B82F6'}},
        {from: 1, to: 60, width: 3, color: {color: '#3B82F6'}},
        {from: 1, to: 70, width: 3, color: {color: '#3B82F6'}},

        // MLM to sub-modules
        {from: 10, to: 11}, {from: 10, to: 12}, {from: 10, to: 13},
        {from: 10, to: 14}, {from: 10, to: 15}, {from: 10, to: 16},

        // E-Commerce to sub-modules
        {from: 20, to: 21}, {from: 20, to: 22}, {from: 20, to: 23},
        {from: 20, to: 24}, {from: 20, to: 25}, {from: 20, to: 26},

        // Wallet to sub-modules
        {from: 30, to: 31}, {from: 30, to: 32}, {from: 30, to: 33},
        {from: 30, to: 34}, {from: 30, to: 35},

        // LINE to sub-modules
        {from: 40, to: 41}, {from: 40, to: 42}, {from: 40, to: 43},
        {from: 40, to: 44}, {from: 40, to: 45},

        // AI to sub-modules
        {from: 50, to: 51}, {from: 50, to: 52}, {from: 50, to: 53},
        {from: 50, to: 54}, {from: 50, to: 55},

        // Security to sub-modules
        {from: 60, to: 61}, {from: 60, to: 62}, {from: 60, to: 63}, {from: 60, to: 64},

        // Admin to sub-modules
        {from: 70, to: 71}, {from: 70, to: 72}, {from: 70, to: 73}, {from: 70, to: 74},

        // Cross-module connections
        {from: 13, to: 30, dashes: true, color: {color: '#94A3B8'}}, // Commission to Wallet
        {from: 23, to: 13, dashes: true, color: {color: '#94A3B8'}}, // Order to Commission
        {from: 41, to: 55, dashes: true, color: {color: '#94A3B8'}}, // LINE Bot to Knowledge
        {from: 61, to: 72, dashes: true, color: {color: '#94A3B8'}}, // KYC to User Management
    ]);

    const data = {nodes: nodes, edges: edges};

    const options = {
        layout: {
            hierarchical: {
                direction: 'UD',
                sortMethod: 'directed',
                levelSeparation: 150,
                nodeSpacing: 150,
            }
        },
        physics: {
            enabled: false
        },
        interaction: {
            zoomView: true,
            dragView: true,
            hover: true,
        },
        groups: {
            mlm: {color: {background: '#DDD6FE', border: '#8B5CF6'}},
            ecommerce: {color: {background: '#FBCFE8', border: '#EC4899'}},
            wallet: {color: {background: '#D1FAE5', border: '#10B981'}},
            line: {color: {background: '#FEF3C7', border: '#F59E0B'}},
            ai: {color: {background: '#CFFAFE', border: '#06B6D4'}},
            security: {color: {background: '#FEE2E2', border: '#EF4444'}},
            admin: {color: {background: '#E0E7FF', border: '#6366F1'}},
        },
        nodes: {
            shape: 'box',
            margin: 10,
            widthConstraint: {
                maximum: 150
            },
            font: {
                size: 12,
            }
        }
    };

    network = new vis.Network(container, data, options);

    // Event handlers
    network.on('click', function(params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = nodes.get(nodeId);
            alert('Module: ' + node.label + '\n\nคลิก OK เพื่อดูรายละเอียดเพิ่มเติม');
        }
    });
}

function zoomIn() {
    if (network) {
        const scale = network.getScale();
        network.moveTo({scale: scale * 1.2});
    }
}

function zoomOut() {
    if (network) {
        const scale = network.getScale();
        network.moveTo({scale: scale * 0.8});
    }
}

function resetZoom() {
    if (network) {
        network.fit();
    }
}

function initSystemDiagram() {
    // System Architecture Diagram using vis-network
    const container = document.getElementById('system-diagram');

    const nodes = new vis.DataSet([
        // Layers
        {id: 'presentation', label: 'Presentation Layer\n(Blade, Alpine.js, Tailwind)', level: 0, color: {background: '#3B82F6'}, font: {color: 'white', size: 14}},
        {id: 'business', label: 'Business Logic Layer\n(Controllers, Services)', level: 1, color: {background: '#8B5CF6'}, font: {color: 'white', size: 14}},
        {id: 'data', label: 'Data Layer\n(Models, Database)', level: 2, color: {background: '#EC4899'}, font: {color: 'white', size: 14}},
        {id: 'integration', label: 'Integration Layer\n(APIs, Webhooks)', level: 3, color: {background: '#10B981'}, font: {color: 'white', size: 14}},
    ]);

    const edges = new vis.DataSet([
        {from: 'presentation', to: 'business', arrows: 'to;from', width: 2},
        {from: 'business', to: 'data', arrows: 'to;from', width: 2},
        {from: 'business', to: 'integration', arrows: 'to;from', width: 2, dashes: true},
    ]);

    const data = {nodes, edges};
    const options = {
        layout: {
            hierarchical: {
                direction: 'UD',
                sortMethod: 'directed',
                levelSeparation: 200,
            }
        },
        physics: false,
        nodes: {
            shape: 'box',
            size: 30,
            widthConstraint: {
                minimum: 300,
                maximum: 300
            },
            heightConstraint: {
                minimum: 80
            },
            margin: 15,
        }
    };

    new vis.Network(container, data, options);
}
</script>
@endpush

@endsection

@extends('layouts.admin-v3')

@section('title', 'TPIX Blockchain Deployment Tutorial')

@push('styles')
<style>
    /* โค้ด Syntax Highlighting */
    .code-block {
        background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
        border-radius: 12px;
        overflow: hidden;
        position: relative;
    }
    .code-block::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 40px;
        background: rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .code-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        position: relative;
        z-index: 1;
    }
    .code-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .code-content {
        padding: 16px;
        overflow-x: auto;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 13px;
        line-height: 1.6;
    }
    .code-content pre {
        margin: 0;
        color: #e4e4e7;
    }
    /* Syntax colors */
    .keyword { color: #c678dd; }
    .string { color: #98c379; }
    .number { color: #d19a66; }
    .comment { color: #5c6370; font-style: italic; }
    .function { color: #61afef; }
    .variable { color: #e5c07b; }
    .type { color: #56b6c2; }

    /* Timeline */
    .timeline-step {
        position: relative;
        padding-left: 3rem;
    }
    .timeline-step::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 40px;
        bottom: -20px;
        width: 2px;
        background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
    }
    .timeline-step:last-child::before {
        display: none;
    }
    .timeline-dot {
        position: absolute;
        left: 0;
        top: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        z-index: 1;
    }
</style>
@endpush

@section('content')
<div x-data="{
    activeSection: 'overview',
    copiedCode: null,
    copyCode(code, id) {
        navigator.clipboard.writeText(code);
        this.copiedCode = id;
        setTimeout(() => this.copiedCode = null, 2000);
    }
}" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur rounded-full text-white/80 text-sm mb-6">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                    คู่มือการ Deploy TPIX สู่ Blockchain จริง
                </div>

                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    🚀 TPIX Blockchain Deployment
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto mb-8">
                    เรียนรู้ขั้นตอนการนำเหรียญ TPIX เข้าสู่ Blockchain จริงตั้งแต่เริ่มต้นจนสมบูรณ์
                    พร้อม Source Code และคำอธิบายภาษาไทยทุกขั้นตอน
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('admin.tpix.deployment.index') }}"
                       class="px-6 py-3 bg-white text-purple-600 font-semibold rounded-xl hover:bg-gray-100 transition shadow-lg">
                        🛠️ เริ่ม Deploy จริง
                    </a>
                    <a href="#step1"
                       class="px-6 py-3 bg-white/10 backdrop-blur text-white font-semibold rounded-xl hover:bg-white/20 transition border border-white/20">
                        📖 อ่าน Tutorial
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {{-- Navigation Tabs --}}
        <div class="mb-12">
            <div class="flex flex-wrap gap-2 p-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                <button @click="activeSection = 'overview'"
                        :class="activeSection === 'overview' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    📋 ภาพรวม
                </button>
                <button @click="activeSection = 'contracts'"
                        :class="activeSection === 'contracts' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    📄 Smart Contracts
                </button>
                <button @click="activeSection = 'deployment'"
                        :class="activeSection === 'deployment' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    🚀 การ Deploy
                </button>
                <button @click="activeSection = 'integration'"
                        :class="activeSection === 'integration' ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    🔗 Laravel Integration
                </button>
            </div>
        </div>

        {{-- Section: Overview --}}
        <div x-show="activeSection === 'overview'" x-transition class="space-y-8">
            {{-- Architecture Diagram --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center text-white">🏗️</span>
                    สถาปัตยกรรมระบบ TPIX Blockchain
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Layer 1: Blockchain --}}
                    <div class="p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl border-2 border-purple-200 dark:border-purple-800">
                        <div class="text-center mb-4">
                            <div class="inline-flex w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl items-center justify-center text-3xl text-white shadow-lg mb-3">
                                ⛓️
                            </div>
                            <h3 class="text-lg font-bold text-purple-900 dark:text-purple-100">Layer 1: Blockchain</h3>
                            <p class="text-sm text-purple-700 dark:text-purple-300">Polygon Edge Network</p>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                4 Validator Nodes (IBFT Consensus)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                Chain ID: 7000
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                Block Time: 2 วินาที
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                Total Supply: 7B TPIX (Fixed)
                            </li>
                        </ul>
                    </div>

                    {{-- Layer 2: Smart Contracts --}}
                    <div class="p-6 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl border-2 border-blue-200 dark:border-blue-800">
                        <div class="text-center mb-4">
                            <div class="inline-flex w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl items-center justify-center text-3xl text-white shadow-lg mb-3">
                                📄
                            </div>
                            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100">Layer 2: Smart Contracts</h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300">Solidity Contracts</p>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                TPIXERC20.sol (Native Token)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                TPIXDEXFactory.sol (DEX Factory)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                TPIXDEXRouter02.sol (Swap Router)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                TPIXDEXPair.sol (Liquidity Pair)
                            </li>
                        </ul>
                    </div>

                    {{-- Layer 3: Laravel Backend --}}
                    <div class="p-6 bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-2xl border-2 border-red-200 dark:border-red-800">
                        <div class="text-center mb-4">
                            <div class="inline-flex w-16 h-16 bg-gradient-to-br from-red-500 to-orange-500 rounded-2xl items-center justify-center text-3xl text-white shadow-lg mb-3">
                                🔧
                            </div>
                            <h3 class="text-lg font-bold text-red-900 dark:text-red-100">Layer 3: Laravel Backend</h3>
                            <p class="text-sm text-red-700 dark:text-red-300">Application Layer</p>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                Web3Service (RPC Connection)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                DEXService (Swap/Liquidity)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                TokenFactoryService (Token Creation)
                            </li>
                            <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                Admin Dashboard (7-Step Wizard)
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Connection Flow --}}
                <div class="mt-8 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4">🔄 Data Flow</h4>
                    <div class="flex items-center justify-center gap-4 flex-wrap text-sm">
                        <span class="px-4 py-2 bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200 rounded-lg font-medium">User</span>
                        <span class="text-gray-400">→</span>
                        <span class="px-4 py-2 bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200 rounded-lg font-medium">Laravel</span>
                        <span class="text-gray-400">→</span>
                        <span class="px-4 py-2 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-lg font-medium">Web3/RPC</span>
                        <span class="text-gray-400">→</span>
                        <span class="px-4 py-2 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200 rounded-lg font-medium">Blockchain</span>
                    </div>
                </div>
            </div>

            {{-- Deployment Steps Overview --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center text-white">📋</span>
                    ขั้นตอนการ Deploy (7 Steps)
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach([
                        ['step' => 1, 'title' => 'ติดตั้ง Polygon Edge', 'desc' => 'ติดตั้ง Node และสร้าง Validators', 'icon' => '⚡', 'color' => 'blue'],
                        ['step' => 2, 'title' => 'เตรียม Environment', 'desc' => 'ตั้งค่า .env และ Dependencies', 'icon' => '⚙️', 'color' => 'purple'],
                        ['step' => 3, 'title' => 'Compile Contracts', 'desc' => 'Compile Smart Contracts ด้วย Hardhat', 'icon' => '🔨', 'color' => 'pink'],
                        ['step' => 4, 'title' => 'Deploy Contracts', 'desc' => 'Deploy ลง Blockchain จริง', 'icon' => '🚀', 'color' => 'red'],
                        ['step' => 5, 'title' => 'Verify Contracts', 'desc' => 'ยืนยันบน Block Explorer', 'icon' => '✅', 'color' => 'green'],
                        ['step' => 6, 'title' => 'Setup Liquidity', 'desc' => 'สร้าง Liquidity Pools เริ่มต้น', 'icon' => '💧', 'color' => 'cyan'],
                        ['step' => 7, 'title' => 'Laravel Integration', 'desc' => 'เชื่อมต่อกับ Laravel Backend', 'icon' => '🔗', 'color' => 'orange'],
                        ['step' => 8, 'title' => 'Testing & Launch', 'desc' => 'ทดสอบและเปิดใช้งานจริง', 'icon' => '🎉', 'color' => 'yellow'],
                    ] as $step)
                    <div class="p-4 bg-{{ $step['color'] }}-50 dark:bg-{{ $step['color'] }}-900/20 rounded-xl border border-{{ $step['color'] }}-200 dark:border-{{ $step['color'] }}-800 hover:shadow-lg transition">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl">{{ $step['icon'] }}</span>
                            <span class="text-xs font-bold text-{{ $step['color'] }}-600 dark:text-{{ $step['color'] }}-400 bg-{{ $step['color'] }}-100 dark:bg-{{ $step['color'] }}-900/50 px-2 py-1 rounded">
                                Step {{ $step['step'] }}
                            </span>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $step['title'] }}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $step['desc'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Requirements --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Software Requirements --}}
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        💻 Software Requirements
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600">✓</span>
                            <span><strong>Node.js</strong> >= 18.0.0</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600">✓</span>
                            <span><strong>npm</strong> >= 9.0.0</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600">✓</span>
                            <span><strong>Polygon Edge</strong> >= 1.0.0</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600">✓</span>
                            <span><strong>PHP</strong> >= 8.1 (Laravel 11)</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600">✓</span>
                            <span><strong>Linux/MacOS</strong> (แนะนำ Ubuntu 22.04)</span>
                        </li>
                    </ul>
                </div>

                {{-- Hardware Requirements --}}
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        🖥️ Hardware Requirements (Validator Node)
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600">⚡</span>
                            <span><strong>CPU:</strong> 4 cores (8 cores แนะนำ)</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600">💾</span>
                            <span><strong>RAM:</strong> 8GB minimum (16GB แนะนำ)</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600">💿</span>
                            <span><strong>Storage:</strong> 100GB SSD</span>
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600">🌐</span>
                            <span><strong>Network:</strong> 100 Mbps</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Section: Smart Contracts --}}
        <div x-show="activeSection === 'contracts'" x-transition class="space-y-8">
            {{-- TPIXERC20.sol --}}
            <div id="contract-erc20" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center text-white text-xl">
                                🪙
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">TPIXERC20.sol</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Native Token Contract (ERC-20 Standard)</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-full font-medium">
                            218 lines
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">📝 คำอธิบาย</h4>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            TPIXERC20 เป็น Smart Contract หลักสำหรับเหรียญ TPIX ที่ใช้มาตรฐาน ERC-20
                            รองรับฟังก์ชันพื้นฐานทั้งหมด เช่น transfer, approve, transferFrom รวมถึง mint และ burn tokens
                        </p>
                    </div>

                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🔧 ฟังก์ชันหลัก</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">totalSupply()</code>
                                <p class="text-xs text-gray-500 mt-1">ดึงจำนวน token ทั้งหมด (7B TPIX)</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">balanceOf(address)</code>
                                <p class="text-xs text-gray-500 mt-1">ตรวจสอบยอดคงเหลือของ address</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">transfer(to, amount)</code>
                                <p class="text-xs text-gray-500 mt-1">โอน token ไปยัง address ปลายทาง</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">approve(spender, amount)</code>
                                <p class="text-xs text-gray-500 mt-1">อนุญาตให้ spender ใช้จ่าย token</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-green-600 dark:text-green-400 font-mono text-sm">_mint(account, amount)</code>
                                <p class="text-xs text-gray-500 mt-1">สร้าง token ใหม่ (internal)</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-red-600 dark:text-red-400 font-mono text-sm">_burn(account, amount)</code>
                                <p class="text-xs text-gray-500 mt-1">ทำลาย token (internal)</p>
                            </div>
                        </div>
                    </div>

                    {{-- Code Block --}}
                    <div class="code-block">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">TPIXERC20.sol</span>
                            <button @click="copyCode(`// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract TPIXERC20 is IERC20 {
    mapping(address => uint256) private _balances;
    mapping(address => mapping(address => uint256)) private _allowances;

    uint256 private _totalSupply;
    string private _name;
    string private _symbol;
    uint8 private _decimals;

    constructor(string memory name_, string memory symbol_, uint8 decimals_) {
        _name = name_;
        _symbol = symbol_;
        _decimals = decimals_;
    }

    function transfer(address to, uint256 amount) public virtual override returns (bool) {
        address owner = msg.sender;
        _transfer(owner, to, amount);
        return true;
    }

    function _mint(address account, uint256 amount) internal virtual {
        require(account != address(0), \"ERC20: mint to the zero address\");
        _totalSupply += amount;
        unchecked {
            _balances[account] += amount;
        }
        emit Transfer(address(0), account, amount);
    }
}`, 'erc20')"
                                    class="ml-auto px-3 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded transition flex items-center gap-1">
                                <span x-text="copiedCode === 'erc20' ? '✓ Copied!' : '📋 Copy'"></span>
                            </button>
                        </div>
                        <div class="code-content">
<pre><span class="comment">// SPDX-License-Identifier: MIT</span>
<span class="keyword">pragma</span> <span class="type">solidity</span> ^0.8.20;

<span class="keyword">contract</span> <span class="type">TPIXERC20</span> <span class="keyword">is</span> <span class="type">IERC20</span> {
    <span class="comment">// การเก็บยอดคงเหลือของแต่ละ address</span>
    <span class="keyword">mapping</span>(<span class="type">address</span> => <span class="type">uint256</span>) <span class="keyword">private</span> <span class="variable">_balances</span>;

    <span class="comment">// การอนุญาตให้ใช้จ่าย (owner => spender => amount)</span>
    <span class="keyword">mapping</span>(<span class="type">address</span> => <span class="keyword">mapping</span>(<span class="type">address</span> => <span class="type">uint256</span>)) <span class="keyword">private</span> <span class="variable">_allowances</span>;

    <span class="type">uint256</span> <span class="keyword">private</span> <span class="variable">_totalSupply</span>;
    <span class="type">string</span> <span class="keyword">private</span> <span class="variable">_name</span>;
    <span class="type">string</span> <span class="keyword">private</span> <span class="variable">_symbol</span>;
    <span class="type">uint8</span> <span class="keyword">private</span> <span class="variable">_decimals</span>;

    <span class="comment">/**
     * @dev สร้าง token พร้อมตั้งค่าชื่อ, symbol และ decimals
     */</span>
    <span class="keyword">constructor</span>(<span class="type">string</span> <span class="keyword">memory</span> name_, <span class="type">string</span> <span class="keyword">memory</span> symbol_, <span class="type">uint8</span> decimals_) {
        <span class="variable">_name</span> = name_;
        <span class="variable">_symbol</span> = symbol_;
        <span class="variable">_decimals</span> = decimals_;
    }

    <span class="comment">/**
     * @dev โอน token ไปยัง address ปลายทาง
     */</span>
    <span class="keyword">function</span> <span class="function">transfer</span>(<span class="type">address</span> to, <span class="type">uint256</span> amount) <span class="keyword">public virtual override returns</span> (<span class="type">bool</span>) {
        <span class="type">address</span> owner = <span class="variable">msg.sender</span>;
        <span class="function">_transfer</span>(owner, to, amount);
        <span class="keyword">return</span> <span class="keyword">true</span>;
    }

    <span class="comment">/**
     * @dev สร้าง token ใหม่และเพิ่มให้ account
     */</span>
    <span class="keyword">function</span> <span class="function">_mint</span>(<span class="type">address</span> account, <span class="type">uint256</span> amount) <span class="keyword">internal virtual</span> {
        <span class="keyword">require</span>(account != <span class="function">address</span>(<span class="number">0</span>), <span class="string">"ERC20: mint to the zero address"</span>);
        <span class="variable">_totalSupply</span> += amount;
        <span class="keyword">unchecked</span> {
            <span class="variable">_balances</span>[account] += amount;
        }
        <span class="keyword">emit</span> <span class="function">Transfer</span>(<span class="function">address</span>(<span class="number">0</span>), account, amount);
    }
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TPIXDEXFactory.sol --}}
            <div id="contract-factory" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-xl">
                                🏭
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">TPIXDEXFactory.sol</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">DEX Factory - สร้าง Liquidity Pairs</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-full font-medium">
                            107 lines
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">📝 คำอธิบาย</h4>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            TPIXDEXFactory ใช้สำหรับสร้าง Trading Pairs (คู่เหรียญสำหรับ Swap)
                            โดยใช้ CREATE2 opcode เพื่อให้ได้ address ของ pair ที่คาดเดาได้ล่วงหน้า
                        </p>
                    </div>

                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🔧 ฟังก์ชันหลัก</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">createPair(tokenA, tokenB)</code>
                                <p class="text-xs text-gray-500 mt-1">สร้าง Liquidity Pair ใหม่</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">getPair(tokenA, tokenB)</code>
                                <p class="text-xs text-gray-500 mt-1">ดึง address ของ pair ที่มีอยู่</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">allPairsLength()</code>
                                <p class="text-xs text-gray-500 mt-1">จำนวน pairs ทั้งหมด</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                <code class="text-blue-600 dark:text-blue-400 font-mono text-sm">setFeeTo(address)</code>
                                <p class="text-xs text-gray-500 mt-1">ตั้งค่า address รับค่าธรรมเนียม</p>
                            </div>
                        </div>
                    </div>

                    {{-- Code Block --}}
                    <div class="code-block">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">TPIXDEXFactory.sol</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment">// SPDX-License-Identifier: MIT</span>
<span class="keyword">pragma</span> <span class="type">solidity</span> ^0.8.20;

<span class="keyword">import</span> <span class="string">'./TPIXDEXPair.sol'</span>;

<span class="comment">/**
 * @title TPIX DEX Factory
 * @notice Factory สำหรับสร้างและจัดการ Liquidity Pairs
 * @dev Based on Uniswap V2 Factory
 */</span>
<span class="keyword">contract</span> <span class="type">TPIXDEXFactory</span> {
    <span class="type">address</span> <span class="keyword">public</span> <span class="variable">feeTo</span>;           <span class="comment">// address รับค่าธรรมเนียม</span>
    <span class="type">address</span> <span class="keyword">public</span> <span class="variable">feeToSetter</span>;     <span class="comment">// address ที่มีสิทธิ์เปลี่ยน feeTo</span>

    <span class="comment">// Mapping: token0 => token1 => pair address</span>
    <span class="keyword">mapping</span>(<span class="type">address</span> => <span class="keyword">mapping</span>(<span class="type">address</span> => <span class="type">address</span>)) <span class="keyword">public</span> <span class="variable">getPair</span>;
    <span class="type">address</span>[] <span class="keyword">public</span> <span class="variable">allPairs</span>;

    <span class="keyword">event</span> <span class="function">PairCreated</span>(<span class="type">address</span> <span class="keyword">indexed</span> token0, <span class="type">address</span> <span class="keyword">indexed</span> token1, <span class="type">address</span> pair, <span class="type">uint256</span>);

    <span class="comment">/**
     * @notice สร้าง Liquidity Pair ใหม่
     * @param tokenA Address ของ token แรก
     * @param tokenB Address ของ token ที่สอง
     * @return pair Address ของ pair ที่สร้าง
     */</span>
    <span class="keyword">function</span> <span class="function">createPair</span>(<span class="type">address</span> tokenA, <span class="type">address</span> tokenB) <span class="keyword">external returns</span> (<span class="type">address</span> pair) {
        <span class="keyword">require</span>(tokenA != tokenB, <span class="string">'TPIX: IDENTICAL_ADDRESSES'</span>);

        <span class="comment">// เรียงลำดับ token (token0 < token1)</span>
        (<span class="type">address</span> token0, <span class="type">address</span> token1) = tokenA < tokenB
            ? (tokenA, tokenB)
            : (tokenB, tokenA);

        <span class="keyword">require</span>(token0 != <span class="function">address</span>(<span class="number">0</span>), <span class="string">'TPIX: ZERO_ADDRESS'</span>);
        <span class="keyword">require</span>(<span class="variable">getPair</span>[token0][token1] == <span class="function">address</span>(<span class="number">0</span>), <span class="string">'TPIX: PAIR_EXISTS'</span>);

        <span class="comment">// ใช้ CREATE2 เพื่อให้ได้ deterministic address</span>
        <span class="type">bytes</span> <span class="keyword">memory</span> bytecode = <span class="keyword">type</span>(TPIXDEXPair).<span class="variable">creationCode</span>;
        <span class="type">bytes32</span> salt = <span class="function">keccak256</span>(<span class="function">abi.encodePacked</span>(token0, token1));
        <span class="keyword">assembly</span> {
            pair := <span class="function">create2</span>(<span class="number">0</span>, <span class="function">add</span>(bytecode, <span class="number">32</span>), <span class="function">mload</span>(bytecode), salt)
        }

        <span class="comment">// Initialize pair ด้วย token addresses</span>
        <span class="type">TPIXDEXPair</span>(pair).<span class="function">initialize</span>(token0, token1);

        <span class="comment">// บันทึก mapping ทั้งสองทิศทาง</span>
        <span class="variable">getPair</span>[token0][token1] = pair;
        <span class="variable">getPair</span>[token1][token0] = pair;
        <span class="variable">allPairs</span>.<span class="function">push</span>(pair);

        <span class="keyword">emit</span> <span class="function">PairCreated</span>(token0, token1, pair, <span class="variable">allPairs</span>.<span class="variable">length</span>);
    }
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contract Files Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    📁 รายการ Smart Contract ทั้งหมด
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">ไฟล์</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">หน้าที่</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900 dark:text-white">บรรทัด</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-3 px-4 font-mono text-blue-600 dark:text-blue-400">TPIXERC20.sol</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Native Token (ERC-20)</td>
                                <td class="py-3 px-4 text-center">218</td>
                                <td class="py-3 px-4"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-xs">✅ พร้อม</span></td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-3 px-4 font-mono text-blue-600 dark:text-blue-400">TPIXDEXFactory.sol</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">DEX Factory (สร้าง Pairs)</td>
                                <td class="py-3 px-4 text-center">107</td>
                                <td class="py-3 px-4"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-xs">✅ พร้อม</span></td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-3 px-4 font-mono text-blue-600 dark:text-blue-400">TPIXDEXPair.sol</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Liquidity Pair (AMM)</td>
                                <td class="py-3 px-4 text-center">246</td>
                                <td class="py-3 px-4"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-xs">✅ พร้อม</span></td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-3 px-4 font-mono text-blue-600 dark:text-blue-400">TPIXDEXRouter02.sol</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">DEX Router (Swap Interface)</td>
                                <td class="py-3 px-4 text-center">298</td>
                                <td class="py-3 px-4"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-xs">✅ พร้อม</span></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <td class="py-3 px-4 font-bold" colspan="2">รวม</td>
                                <td class="py-3 px-4 text-center font-bold">869</td>
                                <td class="py-3 px-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section: Deployment --}}
        <div x-show="activeSection === 'deployment'" x-transition class="space-y-8">
            {{-- Step 1: Install Polygon Edge --}}
            <div id="step1" class="timeline-step">
                <div class="timeline-dot bg-gradient-to-br from-blue-500 to-purple-500">1</div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        ⚡ Step 1: ติดตั้ง Polygon Edge
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Polygon Edge คือ Blockchain framework สำหรับสร้าง Private/Permissioned blockchain
                    </p>

                    <div class="code-block mb-6">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">Terminal</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment"># ดาวน์โหลด Polygon Edge Binary</span>
wget https://github.com/0xPolygon/polygon-edge/releases/download/v1.0.0/polygon-edge-linux-amd64.tar.gz

<span class="comment"># แตกไฟล์</span>
tar -xzf polygon-edge-linux-amd64.tar.gz

<span class="comment"># ย้ายไปยัง PATH</span>
sudo mv polygon-edge /usr/local/bin/

<span class="comment"># ตรวจสอบการติดตั้ง</span>
polygon-edge version
<span class="comment"># Output: Polygon Edge version 1.0.0</span></pre>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-l-4 border-blue-500">
                        <p class="text-blue-800 dark:text-blue-200 text-sm">
                            💡 <strong>Tip:</strong> สำหรับ Production แนะนำใช้ Docker หรือ Kubernetes เพื่อจัดการ Nodes
                        </p>
                    </div>
                </div>
            </div>

            {{-- Step 2: Create Validator Nodes --}}
            <div class="timeline-step">
                <div class="timeline-dot bg-gradient-to-br from-purple-500 to-pink-500">2</div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        🔑 Step 2: สร้าง Validator Nodes (4 Nodes)
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        IBFT Consensus ต้องการ 4 Validators เป็นอย่างน้อยเพื่อ Byzantine Fault Tolerance
                    </p>

                    <div class="code-block mb-6">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">Terminal</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment"># สร้าง directory สำหรับ nodes</span>
mkdir -p ~/tpix-blockchain/nodes
cd ~/tpix-blockchain/nodes

<span class="comment"># สร้าง 4 validator nodes</span>
<span class="keyword">for</span> i <span class="keyword">in</span> {1..4}; <span class="keyword">do</span>
  polygon-edge secrets init --data-dir node-$i
<span class="keyword">done</span>

<span class="comment"># ดู validator addresses (เก็บไว้สำหรับ genesis)</span>
polygon-edge secrets output --data-dir node-1
polygon-edge secrets output --data-dir node-2
polygon-edge secrets output --data-dir node-3
polygon-edge secrets output --data-dir node-4</pre>
                        </div>
                    </div>

                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border-l-4 border-red-500">
                        <p class="text-red-800 dark:text-red-200 text-sm">
                            ⚠️ <strong>สำคัญ:</strong> เก็บ Private Keys ไว้ในที่ปลอดภัย! ห้าม commit เข้า Git
                        </p>
                    </div>
                </div>
            </div>

            {{-- Step 3: Create Genesis --}}
            <div class="timeline-step">
                <div class="timeline-dot bg-gradient-to-br from-pink-500 to-red-500">3</div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        📜 Step 3: สร้าง Genesis File
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Genesis กำหนดค่าเริ่มต้นของ blockchain รวมถึง Chain ID, Premine และ Consensus
                    </p>

                    <div class="code-block mb-6">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">Terminal</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment"># สร้าง genesis.json</span>
polygon-edge genesis \
  --consensus ibft \
  --ibft-validators-prefix-path node- \
  --bootnode /ip4/127.0.0.1/tcp/10001/p2p/&lt;NODE1_LIBP2P_ADDRESS&gt; \
  --bootnode /ip4/127.0.0.1/tcp/20001/p2p/&lt;NODE2_LIBP2P_ADDRESS&gt; \
  --chain-id <span class="number">7000</span> \
  --name <span class="string">"TPIX Blockchain"</span> \
  --premine=<span class="variable">0xYOUR_ADMIN_ADDRESS</span>:<span class="number">7000000000000000000000000000</span> \
  --block-gas-limit <span class="number">8000000</span> \
  --epoch-size <span class="number">100000</span></pre>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🔧 Genesis Configuration</h4>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• <strong>Chain ID:</strong> 7000 (TPIX Mainnet)</li>
                                <li>• <strong>Consensus:</strong> IBFT 2.0</li>
                                <li>• <strong>Block Time:</strong> 2 seconds</li>
                                <li>• <strong>Block Gas Limit:</strong> 8,000,000</li>
                            </ul>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">💰 Premine Allocation</h4>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>• <strong>Total Supply:</strong> 7 Billion TPIX</li>
                                <li>• <strong>Decimals:</strong> 18</li>
                                <li>• <strong>Premine To:</strong> Admin Wallet</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 4: Start Nodes --}}
            <div class="timeline-step">
                <div class="timeline-dot bg-gradient-to-br from-red-500 to-orange-500">4</div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        🚀 Step 4: เริ่ม Validator Nodes
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        เริ่มการทำงานของ 4 Validator Nodes (แต่ละ node ใน terminal แยกกัน)
                    </p>

                    <div class="code-block mb-6">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">Terminal (4 terminals)</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment"># Terminal 1 - Node 1 (RPC port: 8545)</span>
polygon-edge server --data-dir ./node-1 --chain genesis.json \
  --grpc-address :<span class="number">10000</span> --libp2p :<span class="number">10001</span> --jsonrpc :<span class="number">8545</span> \
  --seal --log-level DEBUG

<span class="comment"># Terminal 2 - Node 2</span>
polygon-edge server --data-dir ./node-2 --chain genesis.json \
  --grpc-address :<span class="number">20000</span> --libp2p :<span class="number">20001</span> --jsonrpc :<span class="number">8546</span> \
  --seal --log-level DEBUG

<span class="comment"># Terminal 3 - Node 3</span>
polygon-edge server --data-dir ./node-3 --chain genesis.json \
  --grpc-address :<span class="number">30000</span> --libp2p :<span class="number">30001</span> --jsonrpc :<span class="number">8547</span> \
  --seal --log-level DEBUG

<span class="comment"># Terminal 4 - Node 4</span>
polygon-edge server --data-dir ./node-4 --chain genesis.json \
  --grpc-address :<span class="number">40000</span> --libp2p :<span class="number">40001</span> --jsonrpc :<span class="number">8548</span> \
  --seal --log-level DEBUG</pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 5: Deploy Smart Contracts --}}
            <div class="timeline-step">
                <div class="timeline-dot bg-gradient-to-br from-orange-500 to-yellow-500">5</div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        📄 Step 5: Deploy Smart Contracts
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ใช้ Hardhat deploy script เพื่อ deploy contracts ทั้งหมดลง blockchain
                    </p>

                    <div class="code-block mb-6">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">Terminal</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment"># เข้าไปยัง tpix-blockchain directory</span>
cd /home/user/Thaiprompt-Affiliate/tpix-blockchain

<span class="comment"># ติดตั้ง dependencies</span>
npm install

<span class="comment"># สร้าง .env file</span>
cp .env.example .env

<span class="comment"># แก้ไข .env</span>
<span class="variable">TPIX_RPC_URL</span>=http://localhost:8545
<span class="variable">TPIX_CHAIN_ID</span>=7000
<span class="variable">PRIVATE_KEY</span>=your_deployer_private_key_here

<span class="comment"># Compile contracts</span>
npm run compile

<span class="comment"># Deploy to mainnet</span>
npm run deploy:mainnet</pre>
                        </div>
                    </div>

                    {{-- Deploy Script Explanation --}}
                    <div class="mt-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4">📜 deploy-all.js - Deployment Script</h4>
                        <div class="code-block">
                            <div class="code-header">
                                <div class="code-dot bg-red-500"></div>
                                <div class="code-dot bg-yellow-500"></div>
                                <div class="code-dot bg-green-500"></div>
                                <span class="text-gray-400 text-xs ml-3">scripts/deploy-all.js</span>
                            </div>
                            <div class="code-content">
<pre><span class="keyword">const</span> hre = <span class="function">require</span>(<span class="string">"hardhat"</span>);

<span class="keyword">async function</span> <span class="function">main</span>() {
  <span class="function">console.log</span>(<span class="string">"🚀 Starting TPIX Blockchain Deployment..."</span>);

  <span class="keyword">const</span> [deployer] = <span class="keyword">await</span> hre.ethers.<span class="function">getSigners</span>();
  <span class="function">console.log</span>(<span class="string">`📍 Deployer: ${deployer.address}`</span>);

  <span class="comment">// ========================================</span>
  <span class="comment">// STEP 1: Deploy TPIX Token (ERC20)</span>
  <span class="comment">// ========================================</span>
  <span class="keyword">const</span> TPIXERC20 = <span class="keyword">await</span> hre.ethers.<span class="function">getContractFactory</span>(<span class="string">"TPIXERC20"</span>);
  <span class="keyword">const</span> totalSupply = hre.ethers.utils.<span class="function">parseEther</span>(<span class="string">"7000000000"</span>); <span class="comment">// 7 billion</span>
  <span class="keyword">const</span> tpixToken = <span class="keyword">await</span> TPIXERC20.<span class="function">deploy</span>(<span class="string">"TPIX"</span>, <span class="string">"TPIX"</span>, totalSupply);
  <span class="keyword">await</span> tpixToken.<span class="function">deployed</span>();
  <span class="function">console.log</span>(<span class="string">`✅ TPIX Token: ${tpixToken.address}`</span>);

  <span class="comment">// ========================================</span>
  <span class="comment">// STEP 2: Deploy DEX Factory</span>
  <span class="comment">// ========================================</span>
  <span class="keyword">const</span> TPIXDEXFactory = <span class="keyword">await</span> hre.ethers.<span class="function">getContractFactory</span>(<span class="string">"TPIXDEXFactory"</span>);
  <span class="keyword">const</span> dexFactory = <span class="keyword">await</span> TPIXDEXFactory.<span class="function">deploy</span>(deployer.address);
  <span class="keyword">await</span> dexFactory.<span class="function">deployed</span>();
  <span class="function">console.log</span>(<span class="string">`✅ DEX Factory: ${dexFactory.address}`</span>);

  <span class="comment">// ========================================</span>
  <span class="comment">// STEP 3: Deploy DEX Router</span>
  <span class="comment">// ========================================</span>
  <span class="keyword">const</span> TPIXDEXRouter02 = <span class="keyword">await</span> hre.ethers.<span class="function">getContractFactory</span>(<span class="string">"TPIXDEXRouter02"</span>);
  <span class="keyword">const</span> dexRouter = <span class="keyword">await</span> TPIXDEXRouter02.<span class="function">deploy</span>(
    dexFactory.address,
    tpixToken.address <span class="comment">// WETH equivalent</span>
  );
  <span class="keyword">await</span> dexRouter.<span class="function">deployed</span>();
  <span class="function">console.log</span>(<span class="string">`✅ DEX Router: ${dexRouter.address}`</span>);

  <span class="comment">// ========================================</span>
  <span class="comment">// STEP 4: Create Initial Liquidity Pool</span>
  <span class="comment">// ========================================</span>
  <span class="keyword">const</span> createPairTx = <span class="keyword">await</span> dexFactory.<span class="function">createPair</span>(
    tpixToken.address,
    tpixToken.address
  );
  <span class="keyword">await</span> createPairTx.<span class="function">wait</span>();
  <span class="function">console.log</span>(<span class="string">"✅ Liquidity Pool created!"</span>);

  <span class="function">console.log</span>(<span class="string">"🎉 Deployment Complete!"</span>);
}

<span class="function">main</span>().<span class="function">catch</span>((error) => {
  <span class="function">console.error</span>(error);
  process.<span class="function">exit</span>(<span class="number">1</span>);
});</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 6: Verify & Test --}}
            <div class="timeline-step">
                <div class="timeline-dot bg-gradient-to-br from-green-500 to-emerald-500">6</div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        ✅ Step 6: Verify & Test Deployment
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ทดสอบการ deploy และ verify contracts บน Block Explorer
                    </p>

                    <div class="code-block mb-6">
                        <div class="code-header">
                            <div class="code-dot bg-red-500"></div>
                            <div class="code-dot bg-yellow-500"></div>
                            <div class="code-dot bg-green-500"></div>
                            <span class="text-gray-400 text-xs ml-3">Terminal</span>
                        </div>
                        <div class="code-content">
<pre><span class="comment"># ทดสอบ RPC connection</span>
curl -X POST http://localhost:8545 \
  -H <span class="string">"Content-Type: application/json"</span> \
  -d <span class="string">'{"jsonrpc":"2.0","method":"eth_blockNumber","params":[],"id":1}'</span>

<span class="comment"># Output: {"jsonrpc":"2.0","id":1,"result":"0x1a2b"}</span>

<span class="comment"># Verify contracts</span>
npm run verify

<span class="comment"># Run tests</span>
npm run test-deployment</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Laravel Integration --}}
        <div x-show="activeSection === 'integration'" x-transition class="space-y-8">
            {{-- Update .env --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center text-white">⚙️</span>
                    Step 1: Update Laravel .env
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    เพิ่มการตั้งค่า TPIX Blockchain ใน .env file
                </p>

                <div class="code-block">
                    <div class="code-header">
                        <div class="code-dot bg-red-500"></div>
                        <div class="code-dot bg-yellow-500"></div>
                        <div class="code-dot bg-green-500"></div>
                        <span class="text-gray-400 text-xs ml-3">.env</span>
                    </div>
                    <div class="code-content">
<pre><span class="comment"># TPIX Blockchain Configuration</span>
<span class="variable">TPIX_RPC_URL</span>=http://localhost:8545
<span class="variable">TPIX_CHAIN_ID</span>=7000
<span class="variable">TPIX_EXPLORER_URL</span>=http://explorer.tpix.com

<span class="comment"># Smart Contract Addresses (from deployment)</span>
<span class="variable">TPIX_TOKEN_ADDRESS</span>=0x1234567890abcdef...
<span class="variable">TPIX_DEX_FACTORY_ADDRESS</span>=0xabcdef1234567890...
<span class="variable">TPIX_DEX_ROUTER_ADDRESS</span>=0xfedcba0987654321...

<span class="comment"># Admin Wallet</span>
<span class="variable">TPIX_ADMIN_WALLET_ADDRESS</span>=0xYourAdminAddress...
<span class="variable">TPIX_ADMIN_WALLET_PRIVATE_KEY</span>=your_private_key_here</pre>
                    </div>
                </div>
            </div>

            {{-- Run Migrations --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center text-white">🗃️</span>
                    Step 2: Run Database Migrations
                </h3>

                <div class="code-block">
                    <div class="code-header">
                        <div class="code-dot bg-red-500"></div>
                        <div class="code-dot bg-yellow-500"></div>
                        <div class="code-dot bg-green-500"></div>
                        <span class="text-gray-400 text-xs ml-3">Terminal</span>
                    </div>
                    <div class="code-content">
<pre><span class="comment"># Run all migrations</span>
php artisan migrate

<span class="comment"># หรือ migrate เฉพาะ TPIX tables</span>
php artisan migrate --path=database/migrations/2025_01_*_tpix_*.php

<span class="comment"># Seed initial data</span>
php artisan db:seed --class=TpixSeeder</pre>
                    </div>
                </div>
            </div>

            {{-- Web3Service --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white">🔗</span>
                    Step 3: Web3Service - เชื่อมต่อกับ Blockchain
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Service สำหรับเชื่อมต่อและเรียกใช้ Smart Contracts จาก Laravel
                </p>

                <div class="code-block">
                    <div class="code-header">
                        <div class="code-dot bg-red-500"></div>
                        <div class="code-dot bg-yellow-500"></div>
                        <div class="code-dot bg-green-500"></div>
                        <span class="text-gray-400 text-xs ml-3">app/Services/TPIX/Web3Service.php</span>
                    </div>
                    <div class="code-content">
<pre><span class="keyword">&lt;?php</span>

<span class="keyword">namespace</span> App\Services\TPIX;

<span class="keyword">use</span> Illuminate\Support\Facades\Http;

<span class="comment">/**
 * Web3Service - เชื่อมต่อกับ TPIX Blockchain ผ่าน JSON-RPC
 */</span>
<span class="keyword">class</span> <span class="type">Web3Service</span>
{
    <span class="keyword">protected</span> <span class="type">string</span> <span class="variable">$rpcUrl</span>;
    <span class="keyword">protected</span> <span class="type">int</span> <span class="variable">$chainId</span>;

    <span class="keyword">public function</span> <span class="function">__construct</span>()
    {
        <span class="variable">$this</span>-><span class="variable">rpcUrl</span> = <span class="function">config</span>(<span class="string">'tpix.blockchain.rpc_url'</span>);
        <span class="variable">$this</span>-><span class="variable">chainId</span> = <span class="function">config</span>(<span class="string">'tpix.blockchain.chain_id'</span>);
    }

    <span class="comment">/**
     * ส่ง RPC request ไปยัง blockchain
     */</span>
    <span class="keyword">public function</span> <span class="function">rpcCall</span>(<span class="type">string</span> <span class="variable">$method</span>, <span class="type">array</span> <span class="variable">$params</span> = []): <span class="type">mixed</span>
    {
        <span class="variable">$response</span> = Http::<span class="function">post</span>(<span class="variable">$this</span>-><span class="variable">rpcUrl</span>, [
            <span class="string">'jsonrpc'</span> => <span class="string">'2.0'</span>,
            <span class="string">'method'</span> => <span class="variable">$method</span>,
            <span class="string">'params'</span> => <span class="variable">$params</span>,
            <span class="string">'id'</span> => <span class="number">1</span>,
        ]);

        <span class="keyword">return</span> <span class="variable">$response</span>-><span class="function">json</span>(<span class="string">'result'</span>);
    }

    <span class="comment">/**
     * ดึงยอดคงเหลือของ address
     */</span>
    <span class="keyword">public function</span> <span class="function">getBalance</span>(<span class="type">string</span> <span class="variable">$address</span>): <span class="type">string</span>
    {
        <span class="variable">$balance</span> = <span class="variable">$this</span>-><span class="function">rpcCall</span>(<span class="string">'eth_getBalance'</span>, [<span class="variable">$address</span>, <span class="string">'latest'</span>]);

        <span class="comment">// แปลงจาก hex Wei เป็น TPIX</span>
        <span class="variable">$wei</span> = <span class="function">hexdec</span>(<span class="variable">$balance</span>);
        <span class="keyword">return</span> <span class="function">bcdiv</span>(<span class="variable">$wei</span>, <span class="string">'1000000000000000000'</span>, <span class="number">18</span>);
    }

    <span class="comment">/**
     * ดึงหมายเลข block ปัจจุบัน
     */</span>
    <span class="keyword">public function</span> <span class="function">getBlockNumber</span>(): <span class="type">int</span>
    {
        <span class="variable">$hex</span> = <span class="variable">$this</span>-><span class="function">rpcCall</span>(<span class="string">'eth_blockNumber'</span>);
        <span class="keyword">return</span> <span class="function">hexdec</span>(<span class="variable">$hex</span>);
    }

    <span class="comment">/**
     * ตรวจสอบว่า RPC เชื่อมต่อได้หรือไม่
     */</span>
    <span class="keyword">public function</span> <span class="function">isConnected</span>(): <span class="type">bool</span>
    {
        <span class="keyword">try</span> {
            <span class="variable">$this</span>-><span class="function">getBlockNumber</span>();
            <span class="keyword">return</span> <span class="keyword">true</span>;
        } <span class="keyword">catch</span> (\Exception <span class="variable">$e</span>) {
            <span class="keyword">return</span> <span class="keyword">false</span>;
        }
    }
}</pre>
                    </div>
                </div>
            </div>

            {{-- Test Connection --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center text-white">🧪</span>
                    Step 4: ทดสอบการเชื่อมต่อ
                </h3>

                <div class="code-block">
                    <div class="code-header">
                        <div class="code-dot bg-red-500"></div>
                        <div class="code-dot bg-yellow-500"></div>
                        <div class="code-dot bg-green-500"></div>
                        <span class="text-gray-400 text-xs ml-3">php artisan tinker</span>
                    </div>
                    <div class="code-content">
<pre><span class="comment">// Test Web3 connection</span>
<span class="variable">$web3</span> = <span class="function">app</span>(App\Services\TPIX\Web3Service::<span class="keyword">class</span>);

<span class="comment">// ตรวจสอบการเชื่อมต่อ</span>
<span class="variable">$web3</span>-><span class="function">isConnected</span>();
<span class="comment">// => true</span>

<span class="comment">// ดึง block number ปัจจุบัน</span>
<span class="variable">$web3</span>-><span class="function">getBlockNumber</span>();
<span class="comment">// => 12345</span>

<span class="comment">// ดึงยอดคงเหลือ</span>
<span class="variable">$web3</span>-><span class="function">getBalance</span>(<span class="string">'0xYourAddress'</span>);
<span class="comment">// => "1000000.000000000000000000"</span></pre>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-3xl shadow-xl p-8 text-white">
                <h3 class="text-2xl font-bold mb-4 flex items-center gap-3">
                    🎉 Deployment Complete!
                </h3>
                <p class="text-white/80 mb-6">
                    ยินดีด้วย! ระบบ TPIX Blockchain พร้อมใช้งานแล้ว
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                        <div class="text-3xl mb-2">⛓️</div>
                        <div class="font-semibold">Blockchain</div>
                        <div class="text-sm text-white/70">4 Validators Running</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                        <div class="text-3xl mb-2">📄</div>
                        <div class="font-semibold">Smart Contracts</div>
                        <div class="text-sm text-white/70">4 Contracts Deployed</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-4 text-center">
                        <div class="text-3xl mb-2">🔗</div>
                        <div class="font-semibold">Laravel</div>
                        <div class="text-sm text-white/70">Fully Integrated</div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-4">
                    <a href="{{ route('admin.tpix.deployment.index') }}"
                       class="px-6 py-3 bg-white text-green-600 font-semibold rounded-xl hover:bg-gray-100 transition">
                        🛠️ ไปหน้า Deployment Wizard
                    </a>
                    <a href="{{ route('admin.tpix.dashboard') }}"
                       class="px-6 py-3 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition border border-white/30">
                        📊 TPIX Dashboard
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

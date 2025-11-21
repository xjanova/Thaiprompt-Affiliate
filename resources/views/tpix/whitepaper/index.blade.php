{{-- TPIX Whitepaper - Single Page Full Content --}}
{{-- Version: 6.0 - Complete Single Page Layout with All Details --}}
<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }" x-init="darkMode = localStorage.getItem('darkMode') === 'true'">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TPIX Whitepaper - Native Cryptocurrency with Its Own Blockchain - Complete Documentation">
    <meta name="keywords" content="TPIX, Blockchain, Cryptocurrency, Whitepaper, DeFi, Smart Contracts">
    <meta name="author" content="Thaiprompt Affiliate Team">

    <title>TPIX Whitepaper | Complete Documentation</title>

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>

    {{-- Mermaid.js for Mind Maps & Flow Diagrams --}}
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({
            startOnLoad: true,
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default'
        });
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        [x-cloak] { display: none !important; }

        * {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Modern Gradients */
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }

        .gradient-blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .gradient-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .gradient-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .gradient-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .gradient-pink {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        }

        .gradient-cyan {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        .gradient-teal {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        }

        .gradient-indigo {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        }

        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        .dark .glass {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        /* Hero Background */
        .hero-bg {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradient-animation 15s ease infinite;
        }

        @keyframes gradient-animation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating Animation */
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        /* Feature Card Hover */
        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Section Padding */
        .section-padding {
            padding: 80px 0;
        }

        @media (max-width: 768px) {
            .section-padding {
                padding: 60px 0;
            }
        }

        /* Timeline */
        .timeline-item {
            position: relative;
            padding-left: 40px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 9px;
            top: 20px;
            width: 2px;
            height: calc(100% + 20px);
            background: linear-gradient(180deg, #667eea 0%, transparent 100%);
        }

        /* Stats Animation */
        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: scale(1.05);
        }

        /* Scroll to Top Button */
        #scrollTop {
            transition: all 0.3s ease;
        }

        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
            .section-padding { padding: 40px 0; }
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 transition-colors duration-300">

    {{-- Fixed Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 glass no-print">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                {{-- Logo --}}
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl gradient-primary flex items-center justify-center shadow-2xl shadow-blue-500/50 floating">
                        <span class="text-2xl font-black text-white">T</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gradient">TPIX Whitepaper</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Complete Documentation</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                            class="p-2 rounded-lg glass hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-moon text-gray-700 dark:text-yellow-400" x-show="!darkMode"></i>
                        <i class="fas fa-sun text-yellow-500" x-show="darkMode" x-cloak></i>
                    </button>

                    {{-- Home Link --}}
                    <a href="{{ route('home') }}"
                       class="hidden md:flex items-center space-x-2 px-4 py-2 gradient-primary text-white rounded-lg shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-home"></i>
                        <span class="text-sm font-medium">หน้าแรก</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="pt-32 pb-20 hero-bg relative overflow-hidden">
        <div class="container mx-auto px-4">
            <div class="text-center text-white">
                <div class="mb-8">
                    <div class="inline-block w-24 h-24 rounded-3xl glass flex items-center justify-center shadow-2xl floating mb-6">
                        <span class="text-5xl font-black">T</span>
                    </div>
                </div>
                <h1 class="text-6xl md:text-7xl font-black mb-6">
                    TPIX
                </h1>
                <p class="text-2xl md:text-3xl font-bold mb-4">
                    Native Cryptocurrency with Its Own Blockchain
                </p>
                <p class="text-lg md:text-xl max-w-3xl mx-auto mb-8 opacity-90">
                    ระบบนิเวศแบบครบวงจร ที่ใช้ Blockchain ของตัวเอง พร้อม Use Cases มากกว่า 11+ แอพพลิเคชัน
                </p>

                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto mt-12">
                    <div class="glass rounded-2xl p-6 stat-card">
                        <div class="text-4xl mb-2">💎</div>
                        <div class="text-3xl font-bold">7B</div>
                        <div class="text-sm opacity-80 mt-1">Total Supply</div>
                    </div>
                    <div class="glass rounded-2xl p-6 stat-card">
                        <div class="text-4xl mb-2">⚡</div>
                        <div class="text-3xl font-bold">~1,500</div>
                        <div class="text-sm opacity-80 mt-1">TPS</div>
                    </div>
                    <div class="glass rounded-2xl p-6 stat-card">
                        <div class="text-4xl mb-2">⏱️</div>
                        <div class="text-3xl font-bold">2s</div>
                        <div class="text-sm opacity-80 mt-1">Block Time</div>
                    </div>
                    <div class="glass rounded-2xl p-6 stat-card">
                        <div class="text-4xl mb-2">🎯</div>
                        <div class="text-3xl font-bold">11+</div>
                        <div class="text-sm opacity-80 mt-1">Use Cases</div>
                    </div>
                </div>

                {{-- Scroll Down Indicator --}}
                <div class="mt-16 animate-bounce">
                    <a href="#blockchain" class="text-white opacity-80 hover:opacity-100">
                        <i class="fas fa-chevron-down text-3xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- 📄 Executive Summary --}}
    @include('tpix.whitepaper.partials.executive-summary')

    {{-- ⚠️ Problem & Solution --}}
    @include('tpix.whitepaper.partials.problem-solution')

    {{-- 1. Blockchain Specifications Section --}}
    <section id="blockchain" class="section-padding bg-gray-50 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-blue text-white rounded-full text-sm font-bold mb-4">
                    Blockchain
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    Blockchain Specifications
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    ข้อมูลทางเทคนิคของ TPIX Blockchain ที่ได้รับการออกแบบมาเพื่อ Performance และ Scalability
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Chain ID --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-hashtag text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Chain ID: 7000</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        รหัสเครือข่าย TPIX Blockchain ที่ใช้ระบุเครือข่ายเฉพาะ
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Chain ID: 7000</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Network Name: TPIX Mainnet</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ใช้เชื่อมต่อกับ MetaMask และ Wallet อื่นๆ</span>
                        </li>
                    </ul>
                </div>

                {{-- TPS --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-tachometer-alt text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">TPS: ~1,500</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        สามารถประมวลผลธุรกรรมได้ถึง ~1,500 ธุรกรรมต่อวินาที
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">TPIX</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">~1,500</div>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Bitcoin</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">~7</div>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Ethereum</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">~15-30</div>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Visa</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">~24,000</div>
                        </div>
                    </div>
                </div>

                {{-- Block Time --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-clock text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Block Time: 2 วินาที</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        เวลาในการสร้าง Block ใหม่เพียง 2 วินาที รวดเร็วและมีประสิทธิภาพ
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Block Time: 2 วินาที</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ยืนยันธุรกรรมรวดเร็ว (~10 วินาที)</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ประสบการณ์การใช้งานที่ยอดเยี่ยม</span>
                        </li>
                    </ul>
                </div>

                {{-- Consensus --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-shield-alt text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Consensus: IBFT</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Istanbul Byzantine Fault Tolerance - อัลกอริทึมฉันทามติที่ปลอดภัยและมีประสิทธิภาพ
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">IBFT 2.0 Consensus Algorithm</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ทนทานต่อการโจมตี Byzantine</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ประหยัดพลังงาน (ไม่ต้อง Mining)</span>
                        </li>
                    </ul>
                </div>

                {{-- EVM Compatible --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-code text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">EVM Compatible</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        รองรับ Ethereum Virtual Machine ทำให้สามารถใช้งาน Smart Contract ได้อย่างเต็มรูปแบบ
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ใช้ Smart Contract แบบ Ethereum ได้</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">เขียนด้วย Solidity</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">รองรับ Tools ทั้งหมดของ Ethereum</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ง่ายต่อการ Migrate DApps</span>
                        </li>
                    </ul>
                </div>

                {{-- Gas Price --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-gas-pump text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Low Gas Fees</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ค่าธรรมเนียมธุรกรรมที่ต่ำมาก เหมาะสำหรับการทำธุรกรรมขนาดเล็ก
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Min Gas Price</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">1 Gwei</div>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Avg Fee</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">&lt; $0.01</div>
                        </div>
                    </div>
                </div>

                {{-- Validators --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-server text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Validators: 4</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        โหนดตรวจสอบและยืนยันธุรกรรมบน TPIX Blockchain
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ระบบ Validator แบบ PoA</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Validator ได้รับ Rewards</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">จำนวน Validators เริ่มต้น: 4 โหนด</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">มีแผนเพิ่มเป็น Decentralized PoS</span>
                        </li>
                    </ul>
                </div>

                {{-- Instant Finality --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-check-double text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Instant Finality</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ธุรกรรมได้รับการยืนยันอย่างแน่นอนทันทีที่เข้า Block
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Instant Finality</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ไม่มี Block Reorganization</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ยืนยันธุรกรรมทันที</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ปลอดภัยและมั่นคง</span>
                        </li>
                    </ul>
                </div>

                {{-- Performance Summary --}}
                <div class="glass rounded-3xl p-8 feature-card md:col-span-2 lg:col-span-1">
                    <div class="w-16 h-16 gradient-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-chart-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Performance Summary</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        สรุปประสิทธิภาพโดยรวมของ TPIX Blockchain
                    </p>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">เร็วกว่า Bitcoin</span>
                            <span class="text-lg font-bold text-green-500">214x</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">เร็วกว่า Ethereum</span>
                            <span class="text-lg font-bold text-green-500">50-100x</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ค่าธรรมเนียมต่ำกว่า Ethereum</span>
                            <span class="text-lg font-bold text-green-500">99%+</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Use Cases Section --}}
    <section id="use-cases" class="section-padding bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-green text-white rounded-full text-sm font-bold mb-4">
                    Use Cases
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    กรณีการใช้งานจริง
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    TPIX มีการประยุกต์ใช้งานมากกว่า 11+ แอพพลิเคชัน ครอบคลุมหลายอุตสาหกรรม
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Affiliate Rewards --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-gift text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Affiliate Rewards</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ระบบรางวัล Affiliate แบบ MLM ที่จ่ายเป็น TPIX
                    </p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">รับ 100 TPIX เมื่อสมัครสมาชิกใหม่</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">รับ 50 TPIX เมื่อแนะนำเพื่อน</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Cashback 5% เป็น TPIX</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">MLM Commission เป็น TPIX</span>
                        </li>
                    </ul>
                    <a href="/affiliate" class="block w-full px-6 py-3 gradient-green text-white text-center rounded-xl font-medium shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-external-link-alt mr-2"></i>ดูรายละเอียด
                    </a>
                </div>

                {{-- FoodPassport --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-utensils text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">FoodPassport</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ระบบตรวจสอบย้อนกลับอาหารบน Blockchain
                    </p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ตรวจสอบที่มาอาหาร 100%</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">บันทึกบน Blockchain</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">QR Code Verification</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Carbon Credit Tracking</span>
                        </li>
                    </ul>
                    <a href="/foodpassport" class="block w-full px-6 py-3 gradient-green text-white text-center rounded-xl font-medium shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-external-link-alt mr-2"></i>ดูรายละเอียด
                    </a>
                </div>

                {{-- Delivery Platform --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-shipping-fast text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Delivery Platform</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        แพลตฟอร์มส่งอาหารรวมทุกค่าย
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">รวมบริการส่งอาหารทุกค่าย</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">จ่ายด้วย TPIX ได้ส่วนลด 5%</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Real-time Tracking</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Multi-vendor Support</span>
                        </li>
                    </ul>
                </div>

                {{-- Smart Farm IoT --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-leaf text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Smart Farm IoT</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ระบบฟาร์มอัจฉริยะด้วย IoT Sensors
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">IoT Sensors บนฟาร์ม</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ตรวจสอบสภาพแวดล้อม Real-time</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">AI Prediction</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">บันทึกข้อมูลบน Blockchain</span>
                        </li>
                    </ul>
                </div>

                {{-- Food Verification --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-certificate text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Food Verification</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ตรวจสอบคุณภาพอาหารด้วย AI
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">OCR + Google Cloud Vision</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ตรวจสอบฉลากอาหาร</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Halal/Organic Verification</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Transparency 100%</span>
                        </li>
                    </ul>
                </div>

                {{-- E-Commerce --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-shopping-cart text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">E-Commerce</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ตลาดกลางออนไลน์รองรับการจ่ายด้วย TPIX
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ซื้อขายสินค้าด้วย TPIX</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Multi-vendor Marketplace</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Escrow Payment</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Dispute Resolution</span>
                        </li>
                    </ul>
                </div>

                {{-- AI Bot Marketplace --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-robot text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">AI Bot Marketplace</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ตลาดซื้อขาย AI Bots สำหรับ LINE
                    </p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ซื้อขาย AI Bots</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">LINE AI Bot Integration</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Pay-per-use Model</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Revenue Share 70/30</span>
                        </li>
                    </ul>
                    <a href="/ai-bot-marketplace" class="block w-full px-6 py-3 gradient-green text-white text-center rounded-xl font-medium shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-external-link-alt mr-2"></i>ดูรายละเอียด
                    </a>
                </div>

                {{-- Hotel Booking --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-hotel text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Hotel Booking</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ระบบจองโรงแรมออนไลน์
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">จองโรงแรมด้วย TPIX</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ส่วนลด 10% เมื่อจ่ายด้วย TPIX</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Instant Confirmation</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">No Hidden Fees</span>
                        </li>
                    </ul>
                </div>

                {{-- Carbon Trading --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-globe-americas text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Carbon Trading</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ซื้อขาย Carbon Credits บน Blockchain
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Carbon Credit Tokenization</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Trade Carbon Credits</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">ESG Reporting</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Verified by FoodPassport</span>
                        </li>
                    </ul>
                </div>

                {{-- AI Autonomous --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-brain text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">AI Autonomous</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        AI Agents ที่ทำงานอัตโนมัติ
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">AI Agent ที่ทำงานอัตโนมัติ</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Machine Learning</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Decision Making</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Smart Automation</span>
                        </li>
                    </ul>
                </div>

                {{-- Smart Contracts DApps --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-green rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-cube text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Smart Contracts DApps</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        แอพพลิเคชันแบบ Decentralized
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Decentralized Applications</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Open Source</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Permissionless</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Composable</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Tokenomics Section --}}
    <section id="tokenomics" class="section-padding bg-gray-50 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-purple text-white rounded-full text-sm font-bold mb-4">
                    Tokenomics
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    โครงสร้างการกระจาย TPIX
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    การจัดสรร TPIX ทั้งหมด 7,000,000,000 โทเค็น แบบโปร่งใสและยุติธรรม
                </p>
            </div>

            {{-- Total Supply Card --}}
            <div class="max-w-4xl mx-auto mb-12">
                <div class="glass rounded-3xl p-10 text-center">
                    <div class="text-6xl mb-4">💎</div>
                    <h3 class="text-6xl font-black text-gradient mb-4">7,000,000,000</h3>
                    <p class="text-2xl text-gray-600 dark:text-gray-400 mb-6">Total Supply (Fixed)</p>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Inflation Rate</div>
                            <div class="text-3xl font-bold text-green-500">0%</div>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Burn Mechanism</div>
                            <div class="text-3xl font-bold text-orange-500">Active</div>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Supply Type</div>
                            <div class="text-3xl font-bold text-blue-500">Fixed</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Distribution Cards --}}
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Ecosystem: 30% --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-network-wired text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Ecosystem</h3>
                    <div class="text-4xl font-black text-gradient mb-4">30%</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        2,100,000,000 TPIX สำหรับพัฒนาระบบนิเวศ
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Development & Growth</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Partnerships</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Ecosystem Expansion</span>
                        </li>
                    </ul>
                </div>

                {{-- Rewards: 25% --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-trophy text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Rewards</h3>
                    <div class="text-4xl font-black text-gradient mb-4">25%</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        1,750,000,000 TPIX สำหรับรางวัลผู้ใช้
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Affiliate Rewards</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">User Incentives</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Community Rewards</span>
                        </li>
                    </ul>
                </div>

                {{-- Staking: 20% --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-lock text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Staking</h3>
                    <div class="text-4xl font-black text-gradient mb-4">20%</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        1,400,000,000 TPIX สำหรับ Staking Rewards
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Max APY</div>
                            <div class="text-xl font-bold text-green-500">120%</div>
                        </div>
                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Min Lock</div>
                            <div class="text-xl font-bold text-blue-500">30d</div>
                        </div>
                    </div>
                </div>

                {{-- Team: 15% --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-users text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Team</h3>
                    <div class="text-4xl font-black text-gradient mb-4">15%</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        1,050,000,000 TPIX สำหรับทีมงาน
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between p-2 bg-gray-100 dark:bg-gray-700 rounded">
                            <span class="text-gray-700 dark:text-gray-300">Vesting</span>
                            <span class="font-bold">4 years</span>
                        </div>
                        <div class="flex justify-between p-2 bg-gray-100 dark:bg-gray-700 rounded">
                            <span class="text-gray-700 dark:text-gray-300">Cliff</span>
                            <span class="font-bold">1 year</span>
                        </div>
                    </div>
                </div>

                {{-- Marketing: 10% --}}
                <div class="glass rounded-3xl p-8 feature-card md:col-span-2 lg:col-span-1">
                    <div class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-bullhorn text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Marketing</h3>
                    <div class="text-4xl font-black text-gradient mb-4">10%</div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        700,000,000 TPIX สำหรับการตลาด
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Marketing Campaigns</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">PR & Media</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Airdrops & Promotions</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- 📊 Charts & Calculators --}}
    @include('tpix.whitepaper.partials.charts-calculators')

    {{-- 📊 Mind Maps --}}
    @include('tpix.whitepaper.partials.mindmaps')

    {{-- 🔄 Flow Diagrams --}}
    @include('tpix.whitepaper.partials.flowdiagrams')

    {{-- 4. Roadmap Section --}}
    <section id="roadmap" class="section-padding bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-orange text-white rounded-full text-sm font-bold mb-4">
                    Roadmap
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    แผนการพัฒนา TPIX
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    เส้นทางการพัฒนาจากอดีตสู่อนาคต
                </p>
            </div>

            <div class="max-w-4xl mx-auto space-y-8">
                {{-- Q1-Q2 2023 --}}
                <div class="timeline-item">
                    <div class="glass rounded-3xl p-8">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 gradient-orange rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                                <i class="fas fa-flag text-3xl text-white"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Q1-Q2 2023: Foundation</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">ก่อตั้งและวางรากฐาน</p>
                                <div class="grid md:grid-cols-2 gap-3">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Team Formation</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Whitepaper Release</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Website Launch</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Initial Development</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Q3-Q4 2023 --}}
                <div class="timeline-item">
                    <div class="glass rounded-3xl p-8">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 gradient-orange rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                                <i class="fas fa-vial text-3xl text-white"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Q3-Q4 2023: Testnet</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">เปิดตัว Testnet และทดสอบ</p>
                                <div class="grid md:grid-cols-2 gap-3">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Testnet Launch</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Public Testing</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Security Audits</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Bug Bounty Program</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Q1-Q2 2024 --}}
                <div class="timeline-item">
                    <div class="glass rounded-3xl p-8 ring-4 ring-blue-500/50">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 gradient-orange rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg relative">
                                <i class="fas fa-rocket text-3xl text-white"></i>
                                <span class="absolute -top-2 -right-2 px-2 py-1 bg-blue-500 text-white text-xs rounded-full font-bold">NOW</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Q1-Q2 2024: Mainnet Launch</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">เปิดตัว Mainnet อย่างเป็นทางการ</p>
                                <div class="grid md:grid-cols-2 gap-3">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-spinner fa-spin text-blue-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Mainnet Launch</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-spinner fa-spin text-blue-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">DEX Integration</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-gray-700 dark:text-gray-300">First Use Cases Live</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-gray-400"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Exchange Listings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2025-2026 --}}
                <div class="timeline-item">
                    <div class="glass rounded-3xl p-8">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 gradient-orange rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                                <i class="fas fa-expand-arrows-alt text-3xl text-white"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">2025-2026: Global Expansion</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">ขยายระบบนิเวศทั่วโลก</p>
                                <div class="grid md:grid-cols-2 gap-3">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-gray-400"></i>
                                        <span class="text-gray-700 dark:text-gray-300">More Use Cases</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-gray-400"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Global Partnerships</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-gray-400"></i>
                                        <span class="text-gray-700 dark:text-gray-300">DeFi Ecosystem</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-gray-400"></i>
                                        <span class="text-gray-700 dark:text-gray-300">Cross-chain Bridges</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. Technology Stack & Security Section --}}
    <section id="technology" class="section-padding bg-gray-50 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-red text-white rounded-full text-sm font-bold mb-4">
                    Technology & Security
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    เทคโนโลยีและความปลอดภัย
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    เทคโนโลยีที่ทันสมัยและมาตรฐานความปลอดภัยระดับสูง
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                {{-- Geth --}}
                <div class="glass rounded-3xl p-8 feature-card text-center">
                    <div class="w-16 h-16 gradient-red rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-cube text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Geth (Go)</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Go-Ethereum Implementation
                    </p>
                </div>

                {{-- Solidity --}}
                <div class="glass rounded-3xl p-8 feature-card text-center">
                    <div class="w-16 h-16 gradient-red rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-file-code text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Solidity</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Smart Contract Language
                    </p>
                </div>

                {{-- IPFS --}}
                <div class="glass rounded-3xl p-8 feature-card text-center">
                    <div class="w-16 h-16 gradient-red rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-database text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">IPFS</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Decentralized Storage
                    </p>
                </div>

                {{-- Laravel --}}
                <div class="glass rounded-3xl p-8 feature-card text-center">
                    <div class="w-16 h-16 gradient-red rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-server text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Laravel 11</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        PHP Backend Framework
                    </p>
                </div>
            </div>

            {{-- Security Features --}}
            <div class="grid md:grid-cols-3 gap-6">
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-teal rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-search text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Smart Contract Audits</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        ตรวจสอบโดยผู้เชี่ยวชาญภายนอก
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Third-party Audits</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Public Reports</span>
                        </li>
                    </ul>
                </div>

                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-teal rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-users-lock text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Multi-sig Wallet</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        กระเป๋าแบบหลายลายเซ็น
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Multi Signatures</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">Enhanced Security</span>
                        </li>
                    </ul>
                </div>

                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-teal rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-lock text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Security Features</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        ความปลอดภัยหลายชั้น
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">DDoS Protection</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-check text-green-500"></i>
                            <span class="text-gray-700 dark:text-gray-300">2FA Support</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- 6. DEX Features Section --}}
    <section id="dex" class="section-padding bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-cyan text-white rounded-full text-sm font-bold mb-4">
                    DEX Features
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    คุณสมบัติ Decentralized Exchange
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    แลกเปลี่ยน TPIX แบบ Decentralized พร้อมฟีเจอร์ครบครัน
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- AMM Swap --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-cyan rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-sync-alt text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">AMM Swap</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        แลกเปลี่ยนอัตโนมัติ
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Instant Swap</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">No Order Book</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Low Slippage</span>
                        </li>
                    </ul>
                </div>

                {{-- Liquidity Pools --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-cyan rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-water text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Liquidity Pools</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        สระสภาพคล่อง
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Provide Liquidity</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Earn Trading Fees</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">LP Token Rewards</span>
                        </li>
                    </ul>
                </div>

                {{-- Yield Farming --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-cyan rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-seedling text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Yield Farming</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        เพาะปลูกผลตอบแทน
                    </p>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Farm LP Tokens</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">High APY</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-check text-green-500 mt-1"></i>
                            <span class="text-gray-700 dark:text-gray-300">Auto-compound</span>
                        </li>
                    </ul>
                </div>

                {{-- Staking --}}
                <div class="glass rounded-3xl p-8 feature-card">
                    <div class="w-16 h-16 gradient-cyan rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <i class="fas fa-percentage text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Staking</h3>
                    <div class="text-4xl font-black text-gradient mb-4">120% APY</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Max APY</div>
                            <div class="text-xl font-bold text-green-500">120%</div>
                        </div>
                        <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <div class="text-xs text-gray-600 dark:text-gray-400">Min Lock</div>
                            <div class="text-xl font-bold text-blue-500">30d</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 💼 Use Cases & Applications --}}
    @include('tpix.whitepaper.partials.usecases')

    {{-- 🤝 Partnerships & Integrations --}}
    @include('tpix.whitepaper.partials.partnerships')

    {{-- 👥 Team & Advisors --}}
    @include('tpix.whitepaper.partials.team')

    {{-- 7. Community Section --}}
    <section id="community" class="section-padding bg-gray-50 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4">
                    Community
                </div>
                <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                    เข้าร่วมชุมชน TPIX
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto mb-8">
                    ติดตามข่าวสารและร่วมเป็นส่วนหนึ่งของชุมชน
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                {{-- Telegram --}}
                <a href="https://t.me/tpix_official" target="_blank" class="glass rounded-3xl p-8 text-center feature-card group">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition">
                        <i class="fab fa-telegram text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Telegram</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        เข้าร่วมกลุ่ม Telegram เพื่อพูดคุยกับชุมชน
                    </p>
                </a>

                {{-- Discord --}}
                <a href="#" target="_blank" class="glass rounded-3xl p-8 text-center feature-card group">
                    <div class="w-20 h-20 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition">
                        <i class="fab fa-discord text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Discord</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        เข้าร่วม Discord Server สำหรับ Developer
                    </p>
                </a>

                {{-- Twitter --}}
                <a href="#" target="_blank" class="glass rounded-3xl p-8 text-center feature-card group">
                    <div class="w-20 h-20 bg-gradient-to-br from-sky-400 to-sky-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition">
                        <i class="fab fa-twitter text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Twitter</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        ติดตามข่าวสารและอัพเดทล่าสุด
                    </p>
                </a>
            </div>
        </div>
    </section>

    {{-- 🔧 Technical Specifications --}}
    @include('tpix.whitepaper.partials.technical-specs')

    {{-- 🔒 Security & Audits --}}
    @include('tpix.whitepaper.partials.security-audits')

    {{-- 📊 Competitive Analysis + Financial Projections + FAQ + Investment --}}
    @include('tpix.whitepaper.partials.final-sections')

    {{-- Legal & Disclaimer Section --}}
    @include('tpix.whitepaper.partials.legal')

    {{-- Scroll to Top Button --}}
    <button id="scrollTop"
            @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            x-data="{ show: false }"
            @scroll.window="show = window.pageYOffset > 300"
            x-show="show"
            x-cloak
            class="fixed bottom-8 right-8 w-14 h-14 gradient-primary rounded-full shadow-2xl flex items-center justify-center text-white cursor-pointer hover:scale-110 transition z-50 no-print">
        <i class="fas fa-chevron-up text-xl"></i>
    </button>

    {{-- Footer --}}
    <footer class="py-12 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <div class="inline-block w-16 h-16 rounded-2xl gradient-primary flex items-center justify-center shadow-2xl mb-6">
                    <span class="text-3xl font-black">T</span>
                </div>
                <h3 class="text-2xl font-bold mb-2">TPIX</h3>
                <p class="text-gray-400 mb-6 max-w-2xl mx-auto">
                    Native Cryptocurrency for Thaiprompt Affiliate Ecosystem<br>
                    ระบบนิเวศแบบครบวงจร ที่ใช้ Blockchain ของตัวเอง
                </p>
                <div class="flex justify-center space-x-6 mb-8">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-telegram text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-discord text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-github text-2xl"></i>
                    </a>
                </div>
                <p class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} Thaiprompt Affiliate. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

</body>
</html>

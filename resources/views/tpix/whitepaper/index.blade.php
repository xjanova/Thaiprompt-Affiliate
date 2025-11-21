{{-- TPIX Whitepaper - Interactive Mind Map - Enhanced Version --}}
{{-- Version: 5.0 - Full Featured Mind Map with vis-network.js --}}
<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }" x-init="darkMode = localStorage.getItem('darkMode') === 'true'">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TPIX Whitepaper - Native Cryptocurrency with Interactive Mind Map Visualization">
    <meta name="keywords" content="TPIX, Blockchain, Cryptocurrency, Whitepaper, Mind Map, Interactive">
    <meta name="author" content="Thaiprompt Affiliate Team">

    <title>TPIX Whitepaper | Interactive Mind Map</title>

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- vis-network.js --}}
    <script src="https://unpkg.com/vis-network@9.1.9/standalone/umd/vis-network.min.js"></script>

    {{-- html2canvas for export --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        [x-cloak] { display: none !important; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Modern Gradients */
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
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

        /* Mind Map Container */
        #mindmap {
            width: 100%;
            height: 80vh;
            min-height: 600px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .dark #mindmap {
            background: rgba(17, 24, 39, 0.8);
        }

        /* Animated gradient background */
        @keyframes gradient-animation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradient-animation 15s ease infinite;
        }

        /* Floating Animation */
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        /* Pulse Animation */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.5); }
            50% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.8), 0 0 60px rgba(102, 126, 234, 0.6); }
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        /* Modal Styles */
        .modal-backdrop {
            backdrop-filter: blur(10px);
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Stats Card */
        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Search Highlight */
        .search-highlight {
            animation: highlight-pulse 1s ease-in-out;
        }

        @keyframes highlight-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* Legend Items */
        .legend-item {
            transition: all 0.3s ease;
        }

        .legend-item:hover {
            transform: translateX(8px);
        }

        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
            #mindmap { height: 100vh; }
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.5);
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.8);
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 transition-colors duration-300"
      x-data="{
          showInfo: false,
          selectedNode: null,
          showTranslateToast: false,
          translateToastMessage: '',
          network: null,
          searchQuery: '',
          showModal: false,
          modalData: {},
          nodeDetails: {}
      }"
      x-init="
          // Initialize node details
          nodeDetails = window.nodeDetailsData || {};
      "
      @language-changed.window="
          showTranslateToast = true;
          translateToastMessage = 'กำลังแปลเป็น ' + $event.detail.language + '...';
      "
      @translation-complete.window="
          translateToastMessage = 'แปลเป็น ' + $event.detail.language + ' เรียบร้อย ✓';
          setTimeout(() => showTranslateToast = false, 2000);
      ">

    {{-- Toast Notification --}}
    <div x-show="showTranslateToast"
         x-transition
         class="fixed bottom-4 right-4 z-[9999] no-print">
        <div class="glass px-6 py-3 rounded-2xl shadow-2xl">
            <p class="font-medium text-gray-900 dark:text-white no-translate" x-text="translateToastMessage"></p>
        </div>
    </div>

    {{-- Modal Popup --}}
    <div x-show="showModal"
         x-cloak
         @click.self="showModal = false"
         class="fixed inset-0 z-[9998] flex items-center justify-center modal-backdrop no-print"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="relative w-full max-w-2xl mx-4 glass rounded-3xl shadow-2xl modal-content custom-scrollbar"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">

            {{-- Close Button --}}
            <button @click="showModal = false"
                    class="absolute top-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition z-10">
                <i class="fas fa-times text-gray-700 dark:text-gray-300"></i>
            </button>

            {{-- Modal Header --}}
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start space-x-4">
                    <div class="w-16 h-16 rounded-2xl gradient-primary flex items-center justify-center shadow-lg">
                        <i :class="modalData.icon || 'fa-info-circle'" class="fas text-3xl text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2" x-text="modalData.title"></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="modalData.label"></p>
                    </div>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-6">
                {{-- Description --}}
                <div x-show="modalData.description">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                        <i class="fas fa-align-left mr-2"></i>คำอธิบาย
                    </h4>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed" x-text="modalData.description"></p>
                </div>

                {{-- Features --}}
                <div x-show="modalData.features && modalData.features.length > 0">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">
                        <i class="fas fa-star mr-2"></i>ฟีเจอร์เด่น
                    </h4>
                    <ul class="space-y-2">
                        <template x-for="(feature, index) in modalData.features" :key="index">
                            <li class="flex items-start space-x-3">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span class="text-gray-700 dark:text-gray-300" x-text="feature"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Stats --}}
                <div x-show="modalData.stats && Object.keys(modalData.stats).length > 0">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">
                        <i class="fas fa-chart-bar mr-2"></i>สถิติ
                    </h4>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="(value, key) in modalData.stats" :key="key">
                            <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1" x-text="key"></div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="value"></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Link --}}
                <div x-show="modalData.link">
                    <a :href="modalData.link"
                       class="block w-full px-6 py-3 gradient-primary text-white text-center rounded-xl font-medium shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        ดูรายละเอียดเพิ่มเติม
                    </a>
                </div>
            </div>
        </div>
    </div>

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
                        <p class="text-xs text-gray-600 dark:text-gray-400">Interactive Mind Map</p>
                    </div>
                </div>

                {{-- Search Bar (Desktop) --}}
                <div class="hidden lg:block flex-1 max-w-md mx-8">
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input="handleSearch($event.target.value)"
                               placeholder="ค้นหา nodes..."
                               class="w-full px-4 py-2 pl-10 pr-10 glass rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <button x-show="searchQuery"
                                @click="searchQuery = ''; handleSearch('')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Export Dropdown --}}
                    <div x-data="{ exportOpen: false }" class="relative">
                        <button @click="exportOpen = !exportOpen"
                                type="button"
                                class="hidden md:flex items-center space-x-2 px-4 py-2 glass rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                title="ส่งออก">
                            <i class="fas fa-download text-gray-700 dark:text-gray-300"></i>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Export</span>
                        </button>

                        <div x-show="exportOpen"
                             @click.outside="exportOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 glass rounded-2xl shadow-2xl overflow-hidden"
                             x-cloak>
                            <button @click="exportAsPNG(); exportOpen = false"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm flex items-center space-x-3">
                                <i class="fas fa-image text-blue-500"></i>
                                <span class="text-gray-900 dark:text-white">ส่งออกเป็น PNG</span>
                            </button>
                            <button @click="window.print(); exportOpen = false"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm flex items-center space-x-3">
                                <i class="fas fa-file-pdf text-red-500"></i>
                                <span class="text-gray-900 dark:text-white">พิมพ์เป็น PDF</span>
                            </button>
                            <button @click="shareWhitepaper(); exportOpen = false"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm flex items-center space-x-3">
                                <i class="fas fa-share-alt text-green-500"></i>
                                <span class="text-gray-900 dark:text-white">แชร์</span>
                            </button>
                        </div>
                    </div>

                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                            class="p-2 rounded-lg glass hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-moon text-gray-700 dark:text-yellow-400" x-show="!darkMode"></i>
                        <i class="fas fa-sun text-yellow-500" x-show="darkMode" x-cloak></i>
                    </button>

                    {{-- Language Switcher --}}
                    <div x-data="{ langOpen: false }" class="relative">
                        <button @click="langOpen = !langOpen"
                                type="button"
                                class="p-2 rounded-lg glass hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                title="เลือกภาษา">
                            <i class="fas fa-globe text-gray-700 dark:text-gray-300"></i>
                        </button>

                        <div x-show="langOpen"
                             @click.outside="langOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-56 glass rounded-2xl shadow-2xl overflow-hidden"
                             x-cloak>
                            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-xs font-bold text-gray-900 dark:text-white">
                                    <i class="fas fa-globe mr-1"></i>
                                    เลือกภาษา
                                </h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto p-2">
                                <button @click="changeLanguage('th'); langOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm">
                                    🇹🇭 ไทย
                                </button>
                                <button @click="changeLanguage('en'); langOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm">
                                    🇬🇧 English
                                </button>
                                <button @click="changeLanguage('zh-CN'); langOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm">
                                    🇨🇳 简体中文
                                </button>
                                <button @click="changeLanguage('ja'); langOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition text-sm">
                                    🇯🇵 日本語
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Home Link --}}
                    <a href="{{ route('home') }}"
                       class="hidden md:flex items-center space-x-2 px-4 py-2 gradient-primary text-white rounded-lg shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-home"></i>
                        <span class="text-sm font-medium">หน้าแรก</span>
                    </a>
                </div>
            </div>

            {{-- Mobile Search --}}
            <div class="lg:hidden mt-3">
                <div class="relative">
                    <input type="text"
                           x-model="searchQuery"
                           @input="handleSearch($event.target.value)"
                           placeholder="ค้นหา nodes..."
                           class="w-full px-4 py-2 pl-10 pr-10 glass rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                    <button x-show="searchQuery"
                            @click="searchQuery = ''; handleSearch('')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <div class="pt-32 lg:pt-24 pb-12">
        <div class="container mx-auto px-4">
            {{-- Header --}}
            <div class="text-center mb-8">
                <h2 class="text-5xl font-black text-gradient mb-4">
                    TPIX Ecosystem
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                    Native Cryptocurrency with Its Own Blockchain<br>
                    <span class="text-base">Explore the interactive mind map below (Click, Zoom, Pan)</span>
                </p>
            </div>

            {{-- Stats Summary Panel --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8 no-print">
                <div class="stat-card glass rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">💎</div>
                    <div class="text-2xl font-bold text-gradient">7B</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total Supply</div>
                </div>
                <div class="stat-card glass rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">⚡</div>
                    <div class="text-2xl font-bold text-gradient">~1,500</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">TPS</div>
                </div>
                <div class="stat-card glass rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">⏱️</div>
                    <div class="text-2xl font-bold text-gradient">2s</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Block Time</div>
                </div>
                <div class="stat-card glass rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🎯</div>
                    <div class="text-2xl font-bold text-gradient">11+</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Use Cases</div>
                </div>
                <div class="stat-card glass rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🛡️</div>
                    <div class="text-2xl font-bold text-gradient">4</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Validators</div>
                </div>
                <div class="stat-card glass rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">📈</div>
                    <div class="text-2xl font-bold text-gradient">0%</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Inflation</div>
                </div>
            </div>

            {{-- Mind Map Container --}}
            <div class="glass p-4 rounded-3xl mb-8">
                <div id="mindmap" class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-900"></div>
            </div>

            {{-- Legend --}}
            <div class="glass rounded-2xl p-6 no-print">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-palette mr-2"></i>
                    Legend
                </h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #667eea, #764ba2);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Blockchain</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #10b981, #059669);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Use Cases</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Tokenomics</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #f59e0b, #d97706);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Roadmap</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #ef4444, #dc2626);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Technology</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #ec4899, #db2777);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Ecosystem</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #06b6d4, #0891b2);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">DEX Features</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #14b8a6, #0d9488);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Security</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #6366f1, #4f46e5);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Team & Partners</span>
                    </div>
                    <div class="legend-item flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #f97316, #ea580c);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">TPIX Core</span>
                    </div>
                </div>
            </div>

            {{-- Controls Info --}}
            <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400 no-print">
                <p>
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>คำแนะนำ:</strong> คลิกลากเพื่อย้าย | Scroll เพื่อ Zoom | คลิก Node เพื่อดูรายละเอียด | ค้นหาด้วยช่องค้นหาด้านบน
                </p>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="py-8 bg-gray-900 text-white no-print">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400 mb-2">
                TPIX - Native Cryptocurrency for Thaiprompt Affiliate Ecosystem
            </p>
            <p class="text-sm text-gray-500">
                &copy; {{ date('Y') }} Thaiprompt Affiliate. All rights reserved.
            </p>
        </div>
    </footer>

    {{-- Hidden Google Translate Element --}}
    <div id="google_translate_element" style="display: none;"></div>

    {{-- Mind Map Script --}}
    <script>
        // Node Details Data
        window.nodeDetailsData = {
            1: {
                title: 'TPIX Token',
                description: 'TPIX เป็น Native Cryptocurrency ที่ทำงานบน Blockchain ของตัวเอง ไม่ใช่ ERC-20 หรือ BEP-20',
                icon: 'fa-coins',
                features: [
                    'Blockchain ของตัวเอง (Layer 1)',
                    'ใช้งานได้ทั้ง On-chain และ Off-chain',
                    'รองรับ Smart Contracts',
                    'สามารถแลกเปลี่ยนกับ cryptocurrency อื่นได้'
                ],
                stats: {
                    'Total Supply': '7,000,000,000 TPIX',
                    'Decimals': '18',
                    'Chain ID': '7000'
                }
            },
            10: {
                title: 'Blockchain Specifications',
                description: 'ข้อมูลทางเทคนิคของ TPIX Blockchain',
                icon: 'fa-cube',
                features: [
                    'EVM Compatible - ใช้ Smart Contract ได้',
                    'High Performance - ~1,500 TPS',
                    'Fast Finality - Block time 2 วินาที',
                    'IBFT Consensus - ปลอดภัยและเสถียร'
                ]
            },
            11: {
                title: 'Chain ID: 7000',
                description: 'Chain ID ที่ใช้ระบุ TPIX Blockchain',
                icon: 'fa-hashtag',
                features: [
                    'Chain ID: 7000',
                    'Network Name: TPIX Mainnet',
                    'ใช้เชื่อมต่อกับ MetaMask และ Wallet อื่นๆ'
                ]
            },
            12: {
                title: 'TPS: ~1,500',
                description: 'ความเร็วในการประมวลผลธุรกรรม',
                icon: 'fa-tachometer-alt',
                features: [
                    'สามารถประมวลผลได้ ~1,500 ธุรกรรมต่อวินาที',
                    'เร็วกว่า Bitcoin และ Ethereum',
                    'เหมาะสำหรับการใช้งานจริง'
                ],
                stats: {
                    'TPS': '~1,500',
                    'เทียบ Bitcoin': '~7 TPS',
                    'เทียบ Ethereum': '~15-30 TPS'
                }
            },
            13: {
                title: 'Block Time: 2 วินาที',
                description: 'เวลาในการสร้าง Block ใหม่',
                icon: 'fa-clock',
                features: [
                    'Block Time 2 วินาที',
                    'ยืนยันธุรกรรมรวดเร็ว',
                    'ประสบการณ์การใช้งานที่ดี'
                ],
                stats: {
                    'Block Time': '2 วินาที',
                    'Confirmation': '~10 วินาที'
                }
            },
            14: {
                title: 'Consensus: IBFT',
                description: 'Istanbul Byzantine Fault Tolerance',
                icon: 'fa-shield-alt',
                features: [
                    'IBFT 2.0 Consensus Algorithm',
                    'ทนทานต่อการโจมตี Byzantine',
                    'Finality ทันที',
                    'ประหยัดพลังงาน (ไม่ต้อง Mining)'
                ]
            },
            15: {
                title: 'EVM Compatible',
                description: 'รองรับ Ethereum Virtual Machine',
                icon: 'fa-code',
                features: [
                    'ใช้ Smart Contract แบบ Ethereum ได้',
                    'เขียนด้วย Solidity',
                    'รองรับ Tools ทั้งหมดของ Ethereum',
                    'ง่ายต่อการ Migrate DApps'
                ]
            },
            16: {
                title: 'Gas Price',
                description: 'ค่าธรรมเนียมธุรกรรมที่ต่ำมาก',
                icon: 'fa-gas-pump',
                features: [
                    'Gas Price เริ่มต้น: 1 Gwei',
                    'ค่าธรรมเนียมต่ำกว่า Ethereum มาก',
                    'เหมาะสำหรับ Micro-transactions'
                ],
                stats: {
                    'Min Gas Price': '1 Gwei',
                    'Avg Transaction Fee': '< $0.01'
                }
            },
            17: {
                title: 'Validators',
                description: 'โหนดที่ตรวจสอบและยืนยันธุรกรรม',
                icon: 'fa-server',
                features: [
                    'ระบบ Validator แบบ PoA',
                    'Validator ได้รับ Rewards',
                    'จำนวน Validators เริ่มต้น: 4 โหนด',
                    'มีแผนเพิ่มเป็น Decentralized PoS'
                ],
                stats: {
                    'Active Validators': '4',
                    'Min Stake': 'TBA'
                }
            },
            18: {
                title: 'Finality',
                description: 'ความแน่นอนของธุรกรรม',
                icon: 'fa-check-double',
                features: [
                    'Instant Finality',
                    'ไม่มี Block Reorganization',
                    'ยืนยันธุรกรรมทันที',
                    'ปลอดภัยและมั่นคง'
                ]
            },
            20: {
                title: 'Use Cases',
                description: 'กรณีการใช้งาน TPIX ในระบบนิเวศ',
                icon: 'fa-briefcase',
                features: [
                    '11+ Use Cases หลัก',
                    'ครอบคลุมหลายอุตสาหกรรม',
                    'ใช้งานได้จริง',
                    'มี Product Market Fit'
                ]
            },
            21: {
                title: 'Affiliate Rewards',
                description: 'ระบบรางวัล Affiliate',
                icon: 'fa-gift',
                features: [
                    'รับ 100 TPIX เมื่อสมัครสมาชิกใหม่',
                    'รับ 50 TPIX เมื่อแนะนำเพื่อน',
                    'Cashback 5% เป็น TPIX',
                    'MLM Commission เป็น TPIX'
                ],
                link: '/affiliate'
            },
            22: {
                title: 'FoodPassport',
                description: 'ระบบตรวจสอบย้อนกลับอาหาร',
                icon: 'fa-utensils',
                features: [
                    'ตรวจสอบที่มาอาหาร 100%',
                    'บันทึกบน Blockchain',
                    'QR Code Verification',
                    'Carbon Credit Tracking'
                ],
                link: '/foodpassport'
            },
            23: {
                title: 'Delivery Platform',
                description: 'แพลตฟอร์มส่งอาหารหลายบริษัท',
                icon: 'fa-shipping-fast',
                features: [
                    'รวมบริการส่งอาหารทุกค่าย',
                    'จ่ายด้วย TPIX ได้ส่วนลด 5%',
                    'Real-time Tracking',
                    'Multi-vendor Support'
                ]
            },
            24: {
                title: 'Smart Farm IoT',
                description: 'ระบบฟาร์มอัจฉริยะ',
                icon: 'fa-leaf',
                features: [
                    'IoT Sensors บนฟาร์ม',
                    'ตรวจสอบสภาพแวดล้อม Real-time',
                    'AI Prediction',
                    'บันทึกข้อมูลบน Blockchain'
                ]
            },
            25: {
                title: 'Food Verification',
                description: 'ตรวจสอบคุณภาพอาหาร',
                icon: 'fa-certificate',
                features: [
                    'OCR + Google Cloud Vision',
                    'ตรวจสอบฉลากอาหาร',
                    'Halal/Organic Verification',
                    'Transparency 100%'
                ]
            },
            26: {
                title: 'E-Commerce',
                description: 'ตลาดกลางออนไลน์',
                icon: 'fa-shopping-cart',
                features: [
                    'ซื้อขายสินค้าด้วย TPIX',
                    'Multi-vendor Marketplace',
                    'Escrow Payment',
                    'Dispute Resolution'
                ]
            },
            27: {
                title: 'AI Bot Marketplace',
                description: 'ตลาดซื้อขาย AI Bots',
                icon: 'fa-robot',
                features: [
                    'ซื้อขาย AI Bots',
                    'LINE AI Bot Integration',
                    'Pay-per-use Model',
                    'Revenue Share 70/30'
                ],
                link: '/ai-bot-marketplace'
            },
            28: {
                title: 'Hotel Booking',
                description: 'ระบบจองโรงแรม',
                icon: 'fa-hotel',
                features: [
                    'จองโรงแรมด้วย TPIX',
                    'ส่วนลด 10% เมื่อจ่ายด้วย TPIX',
                    'Instant Confirmation',
                    'No Hidden Fees'
                ]
            },
            29: {
                title: 'Carbon Trading',
                description: 'ซื้อขาย Carbon Credits',
                icon: 'fa-globe-americas',
                features: [
                    'Carbon Credit Tokenization',
                    'Trade Carbon Credits',
                    'ESG Reporting',
                    'Verified by FoodPassport'
                ]
            },
            30: {
                title: 'Tokenomics',
                description: 'โครงสร้างการกระจาย TPIX',
                icon: 'fa-chart-pie',
                features: [
                    'Total Supply: 7B TPIX',
                    'Fixed Supply (ไม่มีการ Mint เพิ่ม)',
                    'Deflationary Model',
                    'Transparent Distribution'
                ]
            },
            31: {
                title: 'Total Supply: 7B TPIX',
                description: 'จำนวน TPIX ทั้งหมด',
                icon: 'fa-coins',
                features: [
                    'Total: 7,000,000,000 TPIX',
                    'Fixed Supply',
                    'No Inflation',
                    'Deflationary (Burn Mechanism)'
                ],
                stats: {
                    'Total': '7,000,000,000',
                    'Circulating': 'TBA',
                    'Burned': 'TBA'
                }
            },
            32: {
                title: 'Ecosystem: 30%',
                description: 'จัดสรรให้ระบบนิเวศ',
                icon: 'fa-network-wired',
                features: [
                    '2.1B TPIX (30%)',
                    'Development & Growth',
                    'Partnerships',
                    'Ecosystem Expansion'
                ],
                stats: {
                    'Amount': '2,100,000,000 TPIX',
                    'Percentage': '30%'
                }
            },
            33: {
                title: 'Rewards: 25%',
                description: 'รางวัลสำหรับผู้ใช้',
                icon: 'fa-trophy',
                features: [
                    '1.75B TPIX (25%)',
                    'Affiliate Rewards',
                    'User Incentives',
                    'Community Rewards'
                ],
                stats: {
                    'Amount': '1,750,000,000 TPIX',
                    'Percentage': '25%'
                }
            },
            34: {
                title: 'Staking: 20%',
                description: 'รางวัลสำหรับการ Stake',
                icon: 'fa-lock',
                features: [
                    '1.4B TPIX (20%)',
                    'Staking Rewards',
                    'APY up to 120%',
                    'Lock Period 30-365 days'
                ],
                stats: {
                    'Amount': '1,400,000,000 TPIX',
                    'Max APY': '120%'
                }
            },
            35: {
                title: 'Team: 15%',
                description: 'จัดสรรให้ทีมงาน',
                icon: 'fa-users',
                features: [
                    '1.05B TPIX (15%)',
                    'Vesting 4 years',
                    '1 year cliff',
                    'Linear release'
                ],
                stats: {
                    'Amount': '1,050,000,000 TPIX',
                    'Vesting': '4 years'
                }
            },
            36: {
                title: 'Marketing: 10%',
                description: 'งบการตลาดและประชาสัมพันธ์',
                icon: 'fa-bullhorn',
                features: [
                    '700M TPIX (10%)',
                    'Marketing Campaigns',
                    'PR & Media',
                    'Community Building',
                    'Airdrops & Promotions'
                ],
                stats: {
                    'Amount': '700,000,000 TPIX',
                    'Percentage': '10%'
                }
            },
            37: {
                title: 'Inflation Rate: 0%',
                description: 'ไม่มีการ Mint เพิ่ม',
                icon: 'fa-ban',
                features: [
                    'Fixed Supply - ไม่มีการเพิ่ม',
                    'Deflationary - มีการ Burn',
                    'Scarcity Increases Value',
                    'Transparent & Predictable'
                ],
                stats: {
                    'Inflation': '0%',
                    'Burn Rate': 'Variable'
                }
            },
            40: {
                title: 'Roadmap',
                description: 'แผนการพัฒนา TPIX',
                icon: 'fa-map',
                features: [
                    'Q1-Q2 2023: Foundation',
                    'Q3-Q4 2023: Testnet',
                    'Q1-Q2 2024: Mainnet',
                    '2025-2026: Expansion'
                ]
            },
            41: {
                title: 'Q1-Q2 2023: Foundation',
                description: 'ก่อตั้งและวางรากฐาน',
                icon: 'fa-foundation',
                features: [
                    'Team Formation',
                    'Whitepaper Release',
                    'Website Launch',
                    'Initial Development'
                ]
            },
            42: {
                title: 'Q3-Q4 2023: Testnet',
                description: 'เปิดตัว Testnet',
                icon: 'fa-vial',
                features: [
                    'Testnet Launch',
                    'Public Testing',
                    'Security Audits',
                    'Bug Bounty Program'
                ]
            },
            43: {
                title: 'Q1-Q2 2024: Mainnet',
                description: 'เปิดตัว Mainnet',
                icon: 'fa-rocket',
                features: [
                    'Mainnet Launch',
                    'DEX Integration',
                    'First Use Cases Live',
                    'Exchange Listings'
                ]
            },
            44: {
                title: '2025-2026: Expansion',
                description: 'ขยายระบบนิเวศ',
                icon: 'fa-expand-arrows-alt',
                features: [
                    'More Use Cases',
                    'Global Partnerships',
                    'DeFi Ecosystem',
                    'Cross-chain Bridges'
                ]
            },
            50: {
                title: 'Technology Stack',
                description: 'เทคโนโลยีที่ใช้พัฒนา',
                icon: 'fa-cogs',
                features: [
                    'Geth (Go-Ethereum)',
                    'Solidity Smart Contracts',
                    'IPFS Storage',
                    'Laravel Backend'
                ]
            },
            51: {
                title: 'Geth (Go)',
                description: 'Go-Ethereum Implementation',
                icon: 'fa-cube',
                features: [
                    'Based on Geth',
                    'IBFT Consensus',
                    'Custom Configuration',
                    'Optimized Performance'
                ]
            },
            52: {
                title: 'Solidity',
                description: 'ภาษาสำหรับ Smart Contracts',
                icon: 'fa-file-code',
                features: [
                    'Solidity ^0.8.0',
                    'ERC-20 Compatible',
                    'OpenZeppelin Libraries',
                    'Audited Contracts'
                ]
            },
            53: {
                title: 'IPFS',
                description: 'InterPlanetary File System',
                icon: 'fa-database',
                features: [
                    'Decentralized Storage',
                    'Content Addressing',
                    'High Availability',
                    'Low Cost'
                ]
            },
            54: {
                title: 'Laravel Backend',
                description: 'PHP Framework สำหรับ Backend',
                icon: 'fa-server',
                features: [
                    'Laravel 11',
                    'RESTful API',
                    'Queue Management',
                    'Real-time Events'
                ]
            },
            60: {
                title: 'Ecosystem',
                description: 'ระบบนิเวศของ TPIX',
                icon: 'fa-project-diagram',
                features: [
                    'Affiliate System',
                    'Multi-Delivery',
                    'Carbon Trading',
                    'และอีกมากมาย'
                ]
            },
            61: {
                title: 'Affiliate System',
                description: 'ระบบ MLM และ Affiliate',
                icon: 'fa-users-cog',
                features: [
                    'Multi-level Marketing',
                    'Binary Tree System',
                    'Real-time Commission',
                    'Transparent Tracking'
                ],
                link: '/affiliate'
            },
            62: {
                title: 'Multi-Delivery',
                description: 'รวมบริการส่งอาหารทุกค่าย',
                icon: 'fa-truck',
                features: [
                    'Grab, LINE MAN, FoodPanda, etc.',
                    'Single Platform',
                    'Best Price Comparison',
                    'TPIX Discount'
                ]
            },
            63: {
                title: 'Carbon Trading',
                description: 'ซื้อขาย Carbon Credits',
                icon: 'fa-leaf',
                features: [
                    'Tokenized Carbon Credits',
                    'Verified by FoodPassport',
                    'ESG Compliance',
                    'Trade on DEX'
                ]
            },
            64: {
                title: 'AI Autonomous',
                description: 'AI ที่ทำงานอัตโนมัติ',
                icon: 'fa-brain',
                features: [
                    'AI Agent ที่ทำงานอัตโนมัติ',
                    'Machine Learning',
                    'Decision Making',
                    'Smart Automation'
                ]
            },
            65: {
                title: 'Smart Contracts DApps',
                description: 'แอพพลิเคชันแบบ Decentralized',
                icon: 'fa-cube',
                features: [
                    'Decentralized Applications',
                    'Open Source',
                    'Permissionless',
                    'Composable'
                ]
            },
            70: {
                title: 'DEX Features',
                description: 'คุณสมบัติของ DEX',
                icon: 'fa-exchange-alt',
                features: [
                    'AMM Swap',
                    'Liquidity Pools',
                    'Yield Farming',
                    'High APY Staking'
                ]
            },
            71: {
                title: 'AMM Swap',
                description: 'Automated Market Maker',
                icon: 'fa-sync-alt',
                features: [
                    'Instant Swap',
                    'No Order Book',
                    'Low Slippage',
                    'Fair Pricing'
                ]
            },
            72: {
                title: 'Liquidity Pools',
                description: 'สระสภาพคล่อง',
                icon: 'fa-water',
                features: [
                    'Provide Liquidity',
                    'Earn Trading Fees',
                    'LP Token Rewards',
                    'Impermanent Loss Protection'
                ]
            },
            73: {
                title: 'Yield Farming',
                description: 'เพาะปลูกผลตอบแทน',
                icon: 'fa-seedling',
                features: [
                    'Farm LP Tokens',
                    'High APY',
                    'Multiple Pools',
                    'Auto-compound'
                ]
            },
            74: {
                title: 'Staking APY 120%',
                description: 'Stake TPIX รับดอกเบี้ยสูง',
                icon: 'fa-percentage',
                features: [
                    'APY up to 120%',
                    'Flexible Lock Periods',
                    'Auto Rewards',
                    'Compound Interest'
                ],
                stats: {
                    'Max APY': '120%',
                    'Min Lock': '30 days'
                }
            },
            80: {
                title: 'Security',
                description: 'ความปลอดภัยของ TPIX',
                icon: 'fa-shield-alt',
                features: [
                    'Smart Contract Audits',
                    'Multi-signature Wallet',
                    'Security Best Practices',
                    'Bug Bounty Program'
                ]
            },
            81: {
                title: 'Smart Contract Audits',
                description: 'ตรวจสอบ Smart Contract',
                icon: 'fa-search',
                features: [
                    'Third-party Audits',
                    'Security Analysis',
                    'Vulnerability Testing',
                    'Public Audit Reports'
                ]
            },
            82: {
                title: 'Multi-signature Wallet',
                description: 'กระเป๋าแบบหลายลายเซ็น',
                icon: 'fa-users-lock',
                features: [
                    'Require Multiple Signatures',
                    'Enhanced Security',
                    'Team Funds Protection',
                    'Transparent Governance'
                ]
            },
            83: {
                title: 'Security Features',
                description: 'ฟีเจอร์ด้านความปลอดภัย',
                icon: 'fa-lock',
                features: [
                    'Rate Limiting',
                    'DDoS Protection',
                    'Encryption',
                    '2FA Support'
                ]
            },
            90: {
                title: 'Team & Partners',
                description: 'ทีมงานและพันธมิตร',
                icon: 'fa-handshake',
                features: [
                    'Core Team',
                    'Advisors',
                    'Strategic Partners',
                    'Community Leaders'
                ]
            },
            91: {
                title: 'Core Team',
                description: 'ทีมงานหลัก',
                icon: 'fa-user-tie',
                features: [
                    'Experienced Developers',
                    'Blockchain Experts',
                    'Business Strategists',
                    'Marketing Professionals'
                ]
            },
            92: {
                title: 'Advisors',
                description: 'ที่ปรึกษา',
                icon: 'fa-user-graduate',
                features: [
                    'Industry Veterans',
                    'Technical Advisors',
                    'Legal Consultants',
                    'Financial Experts'
                ]
            },
            93: {
                title: 'Partners',
                description: 'พันธมิตรทางธุรกิจ',
                icon: 'fa-building',
                features: [
                    'Strategic Partnerships',
                    'Technology Partners',
                    'Business Collaborations',
                    'Ecosystem Integration'
                ]
            },
            100: {
                title: 'Community',
                description: 'ชุมชน TPIX',
                icon: 'fa-users',
                features: [
                    'Telegram Community',
                    'Discord Server',
                    'Twitter Updates',
                    'Active Engagement'
                ]
            },
            101: {
                title: 'Telegram',
                description: 'กลุ่ม Telegram ชุมชน TPIX',
                icon: 'fa-telegram',
                features: [
                    'Real-time Chat',
                    'Community Support',
                    'Announcements',
                    'Active Discussions'
                ],
                link: 'https://t.me/tpix_official'
            },
            102: {
                title: 'Discord',
                description: 'เซิร์ฟเวอร์ Discord',
                icon: 'fa-discord',
                features: [
                    'Developer Community',
                    'Technical Support',
                    'Voice Channels',
                    'Events & AMAs'
                ],
                link: '#'
            },
            103: {
                title: 'Twitter',
                description: 'อัพเดทข่าวสาร',
                icon: 'fa-twitter',
                features: [
                    'Latest News',
                    'Announcements',
                    'Community Engagement',
                    'Partnership Updates'
                ],
                link: '#'
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Mind Map Data with 60+ nodes
            const nodes = new vis.DataSet([
                // Center Node
                { id: 1, label: 'TPIX\nNative Cryptocurrency', level: 0, color: { background: '#f97316', border: '#ea580c' }, font: { size: 24, color: '#ffffff', bold: true }, shape: 'ellipse', size: 60 },

                // Blockchain Group (id: 10-19)
                { id: 10, label: 'Blockchain Specs', level: 1, group: 'blockchain', shape: 'box', title: 'ข้อมูลทางเทคนิค' },
                { id: 11, label: 'Chain ID: 7000', level: 2, group: 'blockchain', shape: 'box', title: 'รหัสเครือข่าย' },
                { id: 12, label: 'TPS: ~1,500', level: 2, group: 'blockchain', shape: 'box', title: 'ธุรกรรมต่อวินาที' },
                { id: 13, label: 'Block Time: 2s', level: 2, group: 'blockchain', shape: 'box', title: 'เวลาสร้าง Block' },
                { id: 14, label: 'Consensus: IBFT', level: 2, group: 'blockchain', shape: 'box', title: 'อัลกอริทึมฉันทามติ' },
                { id: 15, label: 'EVM Compatible', level: 2, group: 'blockchain', shape: 'box', title: 'รองรับ Ethereum' },
                { id: 16, label: 'Gas Price', level: 2, group: 'blockchain', shape: 'box', title: 'ค่าธรรมเนียม' },
                { id: 17, label: 'Validators: 4', level: 2, group: 'blockchain', shape: 'box', title: 'โหนดตรวจสอบ' },
                { id: 18, label: 'Instant Finality', level: 2, group: 'blockchain', shape: 'box', title: 'ยืนยันทันที' },

                // Use Cases Group (id: 20-29)
                { id: 20, label: 'Use Cases', level: 1, group: 'usecase', shape: 'box', title: 'กรณีการใช้งาน' },
                { id: 21, label: 'Affiliate Rewards', level: 2, group: 'usecase', shape: 'box', title: 'รางวัล Affiliate' },
                { id: 22, label: 'FoodPassport', level: 2, group: 'usecase', shape: 'box', title: 'ตรวจสอบอาหาร' },
                { id: 23, label: 'Delivery Platform', level: 2, group: 'usecase', shape: 'box', title: 'ส่งอาหาร' },
                { id: 24, label: 'Smart Farm IoT', level: 2, group: 'usecase', shape: 'box', title: 'ฟาร์มอัจฉริยะ' },
                { id: 25, label: 'Food Verification', level: 2, group: 'usecase', shape: 'box', title: 'ตรวจสอบคุณภาพ' },
                { id: 26, label: 'E-Commerce', level: 2, group: 'usecase', shape: 'box', title: 'ซื้อขายออนไลน์' },
                { id: 27, label: 'AI Bot Marketplace', level: 2, group: 'usecase', shape: 'box', title: 'ตลาด AI Bot' },
                { id: 28, label: 'Hotel Booking', level: 2, group: 'usecase', shape: 'box', title: 'จองโรงแรม' },
                { id: 29, label: 'Carbon Trading', level: 2, group: 'usecase', shape: 'box', title: 'ซื้อขาย Carbon' },
                { id: 64, label: 'AI Autonomous', level: 2, group: 'usecase', shape: 'box', title: 'AI อัตโนมัติ' },
                { id: 65, label: 'Smart Contracts DApps', level: 2, group: 'usecase', shape: 'box', title: 'DApps' },

                // Tokenomics Group (id: 30-39)
                { id: 30, label: 'Tokenomics', level: 1, group: 'tokenomics', shape: 'box', title: 'โครงสร้าง Token' },
                { id: 31, label: 'Total: 7B TPIX', level: 2, group: 'tokenomics', shape: 'box', title: 'จำนวนทั้งหมด' },
                { id: 32, label: 'Ecosystem: 30%', level: 2, group: 'tokenomics', shape: 'box', title: 'ระบบนิเวศ' },
                { id: 33, label: 'Rewards: 25%', level: 2, group: 'tokenomics', shape: 'box', title: 'รางวัล' },
                { id: 34, label: 'Staking: 20%', level: 2, group: 'tokenomics', shape: 'box', title: 'Stake' },
                { id: 35, label: 'Team: 15%', level: 2, group: 'tokenomics', shape: 'box', title: 'ทีมงาน' },
                { id: 36, label: 'Marketing: 10%', level: 2, group: 'tokenomics', shape: 'box', title: 'การตลาด' },
                { id: 37, label: 'Inflation: 0%', level: 2, group: 'tokenomics', shape: 'box', title: 'ไม่มี Inflation' },

                // Roadmap Group (id: 40-49)
                { id: 40, label: 'Roadmap', level: 1, group: 'roadmap', shape: 'box', title: 'แผนพัฒนา' },
                { id: 41, label: 'Q1-Q2 2023\nFoundation', level: 2, group: 'roadmap', shape: 'box', title: 'ก่อตั้ง' },
                { id: 42, label: 'Q3-Q4 2023\nTestnet', level: 2, group: 'roadmap', shape: 'box', title: 'Testnet' },
                { id: 43, label: 'Q1-Q2 2024\nMainnet', level: 2, group: 'roadmap', shape: 'box', title: 'Mainnet' },
                { id: 44, label: '2025-2026\nExpansion', level: 2, group: 'roadmap', shape: 'box', title: 'ขยายระบบ' },

                // Technology Group (id: 50-59)
                { id: 50, label: 'Technology', level: 1, group: 'tech', shape: 'box', title: 'เทคโนโลยี' },
                { id: 51, label: 'Geth (Go)', level: 2, group: 'tech', shape: 'box', title: 'Go-Ethereum' },
                { id: 52, label: 'Solidity', level: 2, group: 'tech', shape: 'box', title: 'ภาษา Smart Contract' },
                { id: 53, label: 'IPFS', level: 2, group: 'tech', shape: 'box', title: 'Storage' },
                { id: 54, label: 'Laravel Backend', level: 2, group: 'tech', shape: 'box', title: 'PHP Framework' },

                // Ecosystem Group (id: 60-69)
                { id: 60, label: 'Ecosystem', level: 1, group: 'ecosystem', shape: 'box', title: 'ระบบนิเวศ' },
                { id: 61, label: 'Affiliate System', level: 2, group: 'ecosystem', shape: 'box', title: 'ระบบ Affiliate' },
                { id: 62, label: 'Multi-Delivery', level: 2, group: 'ecosystem', shape: 'box', title: 'บริการส่งอาหาร' },
                { id: 63, label: 'Carbon Trading', level: 2, group: 'ecosystem', shape: 'box', title: 'ซื้อขาย Carbon' },

                // DEX Group (id: 70-79)
                { id: 70, label: 'DEX Features', level: 1, group: 'dex', shape: 'box', title: 'คุณสมบัติ DEX' },
                { id: 71, label: 'AMM Swap', level: 2, group: 'dex', shape: 'box', title: 'แลกเปลี่ยนอัตโนมัติ' },
                { id: 72, label: 'Liquidity Pools', level: 2, group: 'dex', shape: 'box', title: 'สระสภาพคล่อง' },
                { id: 73, label: 'Yield Farming', level: 2, group: 'dex', shape: 'box', title: 'เพาะปลูกผลตอบแทน' },
                { id: 74, label: 'Staking APY 120%', level: 2, group: 'dex', shape: 'box', title: 'Stake รับดอกเบี้ย' },

                // Security Group (id: 80-89)
                { id: 80, label: 'Security', level: 1, group: 'security', shape: 'box', title: 'ความปลอดภัย' },
                { id: 81, label: 'Smart Contract Audits', level: 2, group: 'security', shape: 'box', title: 'ตรวจสอบ Contract' },
                { id: 82, label: 'Multi-sig Wallet', level: 2, group: 'security', shape: 'box', title: 'กระเป๋าหลายลายเซ็น' },
                { id: 83, label: 'Security Features', level: 2, group: 'security', shape: 'box', title: 'ฟีเจอร์ปลอดภัย' },

                // Team & Partners Group (id: 90-99)
                { id: 90, label: 'Team & Partners', level: 1, group: 'team', shape: 'box', title: 'ทีมและพันธมิตร' },
                { id: 91, label: 'Core Team', level: 2, group: 'team', shape: 'box', title: 'ทีมหลัก' },
                { id: 92, label: 'Advisors', level: 2, group: 'team', shape: 'box', title: 'ที่ปรึกษา' },
                { id: 93, label: 'Partners', level: 2, group: 'team', shape: 'box', title: 'พันธมิตร' },

                // Community Group (id: 100-109)
                { id: 100, label: 'Community', level: 1, group: 'community', shape: 'box', title: 'ชุมชน' },
                { id: 101, label: 'Telegram', level: 2, group: 'community', shape: 'box', title: 'กลุ่ม Telegram' },
                { id: 102, label: 'Discord', level: 2, group: 'community', shape: 'box', title: 'เซิร์ฟเวอร์ Discord' },
                { id: 103, label: 'Twitter', level: 2, group: 'community', shape: 'box', title: 'Twitter' },
            ]);

            const edges = new vis.DataSet([
                // Blockchain connections (10-18)
                { from: 1, to: 10 },
                { from: 10, to: 11 }, { from: 10, to: 12 }, { from: 10, to: 13 },
                { from: 10, to: 14 }, { from: 10, to: 15 }, { from: 10, to: 16 },
                { from: 10, to: 17 }, { from: 10, to: 18 },

                // Use Cases connections (20-29, 64-65)
                { from: 1, to: 20 },
                { from: 20, to: 21 }, { from: 20, to: 22 }, { from: 20, to: 23 },
                { from: 20, to: 24 }, { from: 20, to: 25 }, { from: 20, to: 26 },
                { from: 20, to: 27 }, { from: 20, to: 28 }, { from: 20, to: 29 },
                { from: 20, to: 64 }, { from: 20, to: 65 },

                // Tokenomics connections (30-37)
                { from: 1, to: 30 },
                { from: 30, to: 31 }, { from: 30, to: 32 }, { from: 30, to: 33 },
                { from: 30, to: 34 }, { from: 30, to: 35 }, { from: 30, to: 36 },
                { from: 30, to: 37 },

                // Roadmap connections (40-44)
                { from: 1, to: 40 },
                { from: 40, to: 41 }, { from: 40, to: 42 },
                { from: 40, to: 43 }, { from: 40, to: 44 },

                // Technology connections (50-54)
                { from: 1, to: 50 },
                { from: 50, to: 51 }, { from: 50, to: 52 },
                { from: 50, to: 53 }, { from: 50, to: 54 },

                // Ecosystem connections (60-63)
                { from: 1, to: 60 },
                { from: 60, to: 61 }, { from: 60, to: 62 }, { from: 60, to: 63 },

                // DEX connections (70-74)
                { from: 1, to: 70 },
                { from: 70, to: 71 }, { from: 70, to: 72 },
                { from: 70, to: 73 }, { from: 70, to: 74 },

                // Security connections (80-83)
                { from: 1, to: 80 },
                { from: 80, to: 81 }, { from: 80, to: 82 }, { from: 80, to: 83 },

                // Team & Partners connections (90-93)
                { from: 1, to: 90 },
                { from: 90, to: 91 }, { from: 90, to: 92 }, { from: 90, to: 93 },

                // Community connections (100-103)
                { from: 1, to: 100 },
                { from: 100, to: 101 }, { from: 100, to: 102 }, { from: 100, to: 103 },
            ]);

            const container = document.getElementById('mindmap');
            const data = { nodes: nodes, edges: edges };

            const options = {
                nodes: {
                    shape: 'box',
                    margin: 10,
                    widthConstraint: { maximum: 200 },
                    font: {
                        size: 14,
                        face: 'Inter',
                        color: '#ffffff',
                        bold: { color: '#ffffff' }
                    },
                    borderWidth: 2,
                    borderWidthSelected: 4,
                    shadow: {
                        enabled: true,
                        color: 'rgba(0,0,0,0.3)',
                        size: 10,
                        x: 5,
                        y: 5
                    }
                },
                edges: {
                    width: 2,
                    color: { color: '#848484', highlight: '#667eea', hover: '#667eea' },
                    smooth: { type: 'cubicBezier', roundness: 0.5 },
                    arrows: { to: { enabled: true, scaleFactor: 0.5 } }
                },
                groups: {
                    blockchain: { color: { background: '#667eea', border: '#667eea' } },
                    usecase: { color: { background: '#10b981', border: '#10b981' } },
                    tokenomics: { color: { background: '#8b5cf6', border: '#8b5cf6' } },
                    roadmap: { color: { background: '#f59e0b', border: '#f59e0b' } },
                    tech: { color: { background: '#ef4444', border: '#ef4444' } },
                    ecosystem: { color: { background: '#ec4899', border: '#ec4899' } },
                    dex: { color: { background: '#06b6d4', border: '#06b6d4' } },
                    security: { color: { background: '#14b8a6', border: '#14b8a6' } },
                    team: { color: { background: '#6366f1', border: '#6366f1' } },
                    community: { color: { background: '#f97316', border: '#ea580c' } }
                },
                layout: {
                    hierarchical: {
                        enabled: true,
                        levelSeparation: 200,
                        nodeSpacing: 150,
                        treeSpacing: 200,
                        direction: 'UD',
                        sortMethod: 'directed'
                    }
                },
                physics: {
                    enabled: false
                },
                interaction: {
                    dragNodes: true,
                    dragView: true,
                    zoomView: true,
                    hover: true,
                    tooltipDelay: 200
                }
            };

            window.network = new vis.Network(container, data, options);

            // Event: Click node - Show Modal
            window.network.on('click', function(params) {
                if (params.nodes.length > 0) {
                    const nodeId = params.nodes[0];
                    const node = nodes.get(nodeId);
                    const details = window.nodeDetailsData[nodeId] || {};

                    // Prepare modal data
                    const modalData = {
                        label: node.label,
                        title: details.title || node.label,
                        description: details.description || 'คลิกเพื่อดูรายละเอียด',
                        icon: details.icon || 'fa-info-circle',
                        features: details.features || [],
                        stats: details.stats || {},
                        link: details.link || null
                    };

                    // Dispatch Alpine.js event to show modal
                    window.dispatchEvent(new CustomEvent('show-node-modal', {
                        detail: modalData
                    }));
                }
            });

            // Event: Hover effect
            window.network.on('hoverNode', function() {
                container.style.cursor = 'pointer';
            });

            window.network.on('blurNode', function() {
                container.style.cursor = 'default';
            });

            // Fit on load
            setTimeout(() => {
                window.network.fit({
                    animation: {
                        duration: 1000,
                        easingFunction: 'easeInOutQuad'
                    }
                });
            }, 500);

            // Listen for modal event
            window.addEventListener('show-node-modal', function(e) {
                // Update Alpine data
                const alpineData = Alpine.$data(document.body);
                alpineData.modalData = e.detail;
                alpineData.showModal = true;
            });
        });

        // Search Function
        function handleSearch(query) {
            if (!window.network) return;

            const nodes = window.network.body.data.nodes;
            const allNodes = nodes.get();

            if (!query || query.trim() === '') {
                // Reset all nodes
                allNodes.forEach(node => {
                    nodes.update({
                        id: node.id,
                        borderWidth: 2,
                        borderWidthSelected: 4
                    });
                });
                window.network.fit();
                return;
            }

            // Search and highlight
            const searchTerm = query.toLowerCase();
            const matchedNodes = allNodes.filter(node =>
                node.label.toLowerCase().includes(searchTerm) ||
                (node.title && node.title.toLowerCase().includes(searchTerm))
            );

            if (matchedNodes.length > 0) {
                // Highlight matched nodes
                allNodes.forEach(node => {
                    const isMatched = matchedNodes.some(m => m.id === node.id);
                    nodes.update({
                        id: node.id,
                        borderWidth: isMatched ? 6 : 1,
                        opacity: isMatched ? 1 : 0.3
                    });
                });

                // Focus on first matched node
                window.network.focus(matchedNodes[0].id, {
                    scale: 1.5,
                    animation: {
                        duration: 500,
                        easingFunction: 'easeInOutQuad'
                    }
                });
            }
        }

        // Export as PNG
        function exportAsPNG() {
            const mindmapElement = document.getElementById('mindmap');

            html2canvas(mindmapElement, {
                backgroundColor: null,
                scale: 2,
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'tpix-whitepaper-mindmap.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        // Share Function
        function shareWhitepaper() {
            const shareData = {
                title: 'TPIX Whitepaper',
                text: 'Explore the TPIX Ecosystem - Native Cryptocurrency with Its Own Blockchain',
                url: window.location.href
            };

            if (navigator.share) {
                navigator.share(shareData)
                    .catch(() => {
                        // Fallback: Copy to clipboard
                        fallbackShare();
                    });
            } else {
                fallbackShare();
            }
        }

        function fallbackShare() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('✓ คัดลอก URL ไปยัง Clipboard แล้ว!');
            });
        }

        // Language Change Function
        function changeLanguage(lang) {
            const langMap = {
                'th': { code: 'th', name: 'ไทย' },
                'en': { code: 'en', name: 'English' },
                'zh-CN': { code: 'zh-CN', name: '简体中文' },
                'ja': { code: 'ja', name: '日本語' }
            };

            const selected = langMap[lang];

            window.dispatchEvent(new CustomEvent('language-changed', {
                detail: { language: selected.name, code: selected.code }
            }));

            // Trigger Google Translate
            setTimeout(() => {
                const selectElement = document.querySelector('.goog-te-combo');
                if (selectElement) {
                    selectElement.value = selected.code;
                    selectElement.dispatchEvent(new Event('change'));

                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('translation-complete', {
                            detail: { language: selected.name }
                        }));
                    }, 1000);
                }
            }, 100);
        }
    </script>

    {{-- Google Translate Element --}}
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'th',
                includedLanguages: 'th,en,zh-CN,zh-TW,ja,ko,vi,my,lo,km,id,ms,tl,es,fr,de,it,pt,ru,ar',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>
</html>

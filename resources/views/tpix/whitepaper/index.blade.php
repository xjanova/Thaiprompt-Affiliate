{{-- TPIX Whitepaper - Interactive Mind Map - Modern 3D Design --}}
{{-- Version: 4.0 - Static Mind Map with vis-network.js --}}
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
            box-shadow: 0 8 px 32px 0 rgba(31, 38, 135, 0.15);
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

        /* Info Panel */
        .info-panel {
            transition: all 0.3s ease;
        }

        .info-panel.active {
            transform: translateY(0);
            opacity: 1;
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

        /* Floating Animation */
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 transition-colors duration-300"
      x-data="{
          showInfo: false,
          selectedNode: null,
          showTranslateToast: false,
          translateToastMessage: '',
          network: null
      }"
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

                {{-- Actions --}}
                <div class="flex items-center space-x-2">
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

                    {{-- Print Button --}}
                    <button @click="window.print()"
                            class="hidden md:flex items-center space-x-2 px-4 py-2 glass rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-print text-gray-700 dark:text-gray-300"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Print</span>
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

    {{-- Main Content --}}
    <div class="pt-24 pb-12">
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
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                        <div class="w-4 h-4 rounded-full" style="background: linear-gradient(135deg, #f97316, #ea580c);"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">TPIX Core</span>
                    </div>
                </div>
            </div>

            {{-- Controls Info --}}
            <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400 no-print">
                <p>
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>คำแนะนำ:</strong> คลิกลากเพื่อย้าย | Scroll เพื่อ Zoom | คลิก Node เพื่อดูรายละเอียด
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
        document.addEventListener('DOMContentLoaded', function() {
            // Mind Map Data (Static - ไม่ต้องดึงจาก Controller)
            const nodes = new vis.DataSet([
                // Center Node
                { id: 1, label: 'TPIX\nNative Cryptocurrency', level: 0, color: { background: '#f97316', border: '#ea580c' }, font: { size: 24, color: '#ffffff', bold: true }, shape: 'ellipse', size: 60 },

                // Blockchain Group
                { id: 10, label: 'Blockchain Specs', level: 1, group: 'blockchain', shape: 'box' },
                { id: 11, label: 'Chain ID: 7000', level: 2, group: 'blockchain', shape: 'box' },
                { id: 12, label: 'TPS: ~1,500', level: 2, group: 'blockchain', shape: 'box' },
                { id: 13, label: 'Block Time: 2s', level: 2, group: 'blockchain', shape: 'box' },
                { id: 14, label: 'Consensus: IBFT', level: 2, group: 'blockchain', shape: 'box' },
                { id: 15, label: 'EVM Compatible', level: 2, group: 'blockchain', shape: 'box' },

                // Use Cases Group
                { id: 20, label: 'Use Cases', level: 1, group: 'usecase', shape: 'box' },
                { id: 21, label: 'Affiliate Rewards', level: 2, group: 'usecase', shape: 'box' },
                { id: 22, label: 'FoodPassport', level: 2, group: 'usecase', shape: 'box' },
                { id: 23, label: 'Delivery Platform', level: 2, group: 'usecase', shape: 'box' },
                { id: 24, label: 'Smart Farm IoT', level: 2, group: 'usecase', shape: 'box' },
                { id: 25, label: 'Food Verification', level: 2, group: 'usecase', shape: 'box' },
                { id: 26, label: 'E-Commerce', level: 2, group: 'usecase', shape: 'box' },
                { id: 27, label: 'AI Bot Marketplace', level: 2, group: 'usecase', shape: 'box' },
                { id: 28, label: 'Hotel Booking', level: 2, group: 'usecase', shape: 'box' },

                // Tokenomics Group
                { id: 30, label: 'Tokenomics', level: 1, group: 'tokenomics', shape: 'box' },
                { id: 31, label: 'Total: 7B TPIX', level: 2, group: 'tokenomics', shape: 'box' },
                { id: 32, label: 'Ecosystem: 30%', level: 2, group: 'tokenomics', shape: 'box' },
                { id: 33, label: 'Rewards: 25%', level: 2, group: 'tokenomics', shape: 'box' },
                { id: 34, label: 'Staking: 20%', level: 2, group: 'tokenomics', shape: 'box' },
                { id: 35, label: 'Team: 15%', level: 2, group: 'tokenomics', shape: 'box' },

                // Roadmap Group
                { id: 40, label: 'Roadmap', level: 1, group: 'roadmap', shape: 'box' },
                { id: 41, label: 'Q1-Q2 2023\nFoundation', level: 2, group: 'roadmap', shape: 'box' },
                { id: 42, label: 'Q3-Q4 2023\nTestnet', level: 2, group: 'roadmap', shape: 'box' },
                { id: 43, label: 'Q1-Q2 2024\nMainnet', level: 2, group: 'roadmap', shape: 'box' },
                { id: 44, label: '2025-2026\nExpansion', level: 2, group: 'roadmap', shape: 'box' },

                // Technology Group
                { id: 50, label: 'Technology', level: 1, group: 'tech', shape: 'box' },
                { id: 51, label: 'Geth (Go)', level: 2, group: 'tech', shape: 'box' },
                { id: 52, label: 'Solidity', level: 2, group: 'tech', shape: 'box' },
                { id: 53, label: 'IPFS', level: 2, group: 'tech', shape: 'box' },
                { id: 54, label: 'Laravel Backend', level: 2, group: 'tech', shape: 'box' },

                // Ecosystem Group
                { id: 60, label: 'Ecosystem', level: 1, group: 'ecosystem', shape: 'box' },
                { id: 61, label: 'Affiliate System', level: 2, group: 'ecosystem', shape: 'box' },
                { id: 62, label: 'Multi-Delivery', level: 2, group: 'ecosystem', shape: 'box' },
                { id: 63, label: 'Carbon Trading', level: 2, group: 'ecosystem', shape: 'box' },

                // DEX Group
                { id: 70, label: 'DEX Features', level: 1, group: 'dex', shape: 'box' },
                { id: 71, label: 'AMM Swap', level: 2, group: 'dex', shape: 'box' },
                { id: 72, label: 'Liquidity Pools', level: 2, group: 'dex', shape: 'box' },
                { id: 73, label: 'Yield Farming', level: 2, group: 'dex', shape: 'box' },
                { id: 74, label: 'Staking APY 120%', level: 2, group: 'dex', shape: 'box' },
            ]);

            const edges = new vis.DataSet([
                // Blockchain connections
                { from: 1, to: 10 },
                { from: 10, to: 11 },
                { from: 10, to: 12 },
                { from: 10, to: 13 },
                { from: 10, to: 14 },
                { from: 10, to: 15 },

                // Use Cases connections
                { from: 1, to: 20 },
                { from: 20, to: 21 },
                { from: 20, to: 22 },
                { from: 20, to: 23 },
                { from: 20, to: 24 },
                { from: 20, to: 25 },
                { from: 20, to: 26 },
                { from: 20, to: 27 },
                { from: 20, to: 28 },

                // Tokenomics connections
                { from: 1, to: 30 },
                { from: 30, to: 31 },
                { from: 30, to: 32 },
                { from: 30, to: 33 },
                { from: 30, to: 34 },
                { from: 30, to: 35 },

                // Roadmap connections
                { from: 1, to: 40 },
                { from: 40, to: 41 },
                { from: 40, to: 42 },
                { from: 40, to: 43 },
                { from: 40, to: 44 },

                // Technology connections
                { from: 1, to: 50 },
                { from: 50, to: 51 },
                { from: 50, to: 52 },
                { from: 50, to: 53 },
                { from: 50, to: 54 },

                // Ecosystem connections
                { from: 1, to: 60 },
                { from: 60, to: 61 },
                { from: 60, to: 62 },
                { from: 60, to: 63 },

                // DEX connections
                { from: 1, to: 70 },
                { from: 70, to: 71 },
                { from: 70, to: 72 },
                { from: 70, to: 73 },
                { from: 70, to: 74 },
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
                    dex: { color: { background: '#06b6d4', border: '#06b6d4' } }
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
                    hover: true
                }
            };

            const network = new vis.Network(container, data, options);

            // Event: Click node
            network.on('click', function(params) {
                if (params.nodes.length > 0) {
                    const nodeId = params.nodes[0];
                    const node = nodes.get(nodeId);
                    alert('Node: ' + node.label + '\n\nClick to explore more details!');
                }
            });

            // Event: Hover effect
            network.on('hoverNode', function() {
                container.style.cursor = 'pointer';
            });

            network.on('blurNode', function() {
                container.style.cursor = 'default';
            });

            // Fit on load
            setTimeout(() => {
                network.fit({
                    animation: {
                        duration: 1000,
                        easingFunction: 'easeInOutQuad'
                    }
                });
            }, 500);
        });

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

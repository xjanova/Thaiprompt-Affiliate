<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Cashier - {{ $device->device_name }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- ZXing Library สำหรับ Barcode/QR Scanning -->
    <script src="https://unpkg.com/@AziCode/browser@0.0.10/dist/umd/index.min.js"></script>

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    <style>
        /* ============================================
         * POS CASHIER - MODERN V3 HIGH-TECH DESIGN
         * ใช้ Tailwind CSS + Custom Animations
         * ============================================ */

        /* Background System */
        .pos-bg-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }

        .pos-bg-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Grid Pattern Background */
        .pos-grid-pattern {
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Glassmorphism Cards */
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-panel-light {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Neon Glow Effects */
        .neon-green {
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.3), 0 0 40px rgba(34, 197, 94, 0.1);
        }

        .neon-blue {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3), 0 0 40px rgba(59, 130, 246, 0.1);
        }

        .neon-purple {
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.3), 0 0 40px rgba(168, 85, 247, 0.1);
        }

        /* Product Card 3D Effect */
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(34, 197, 94, 0.2);
        }

        .product-card:active {
            transform: scale(0.98);
        }

        /* Cart Item Animation */
        .cart-item-enter {
            animation: cartItemSlide 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes cartItemSlide {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Pulse Animation for Active Elements */
        .pulse-ring {
            animation: pulseRing 2s ease-out infinite;
        }

        @keyframes pulseRing {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6); }
            100% { box-shadow: 0 0 0 20px rgba(34, 197, 94, 0); }
        }

        /* Scanning Animation */
        .scan-line {
            position: absolute;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #22c55e, transparent);
            animation: scanMove 2s ease-in-out infinite;
        }

        @keyframes scanMove {
            0%, 100% { top: 10%; opacity: 0; }
            50% { top: 90%; opacity: 1; }
        }

        /* Camera Container */
        .camera-container {
            position: relative;
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
        }

        .camera-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .camera-target {
            width: 200px;
            height: 200px;
            border: 3px solid rgba(34, 197, 94, 0.8);
            border-radius: 1rem;
            position: relative;
        }

        .camera-target::before,
        .camera-target::after {
            content: '';
            position: absolute;
            background: #22c55e;
        }

        .camera-target::before {
            top: 50%;
            left: -10px;
            right: -10px;
            height: 2px;
            transform: translateY(-50%);
        }

        .camera-target::after {
            left: 50%;
            top: -10px;
            bottom: -10px;
            width: 2px;
            transform: translateX(-50%);
        }

        /* Total Amount Shine Effect */
        .shine-effect {
            position: relative;
            overflow: hidden;
        }

        .shine-effect::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shineMove 3s infinite;
        }

        @keyframes shineMove {
            0% { left: -100%; }
            50%, 100% { left: 100%; }
        }

        /* Floating Badge Animation */
        .float-badge {
            animation: floatBadge 3s ease-in-out infinite;
        }

        @keyframes floatBadge {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Success Animation */
        .success-pop {
            animation: successPop 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes successPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Scrollbar Styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Fullscreen Optimizations */
        :fullscreen .fullscreen-hide {
            display: none !important;
        }

        :fullscreen .fullscreen-show {
            display: flex !important;
        }

        :fullscreen .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }

        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body class="pos-bg-gradient pos-grid-pattern min-h-screen text-white overflow-hidden">
    <div class="h-screen flex flex-col" x-data="posSystem()" x-init="init()">

        <!-- ========================================
             HEADER BAR
             แสดงข้อมูลร้าน, ผู้ใช้, PV, คะแนน
             ======================================== -->
        <header class="glass-panel border-b border-white/10 px-4 py-3 no-print fullscreen-hide z-50">
            <div class="flex items-center justify-between">
                <!-- Left: Device & Store Info -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center neon-green">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-white">{{ $device->device_name }}</h1>
                            <p class="text-xs text-gray-400">{{ $device->store->name ?? 'ร้านค้า' }}</p>
                        </div>
                    </div>

                    @if($activeSession)
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-500/20 border border-green-500/30">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-sm text-green-400 font-medium">เซสชันเปิด</span>
                    </div>
                    @endif
                </div>

                <!-- Center: PV & Points Display -->
                <div class="hidden md:flex items-center gap-6">
                    <!-- PV Display -->
                    <div class="flex items-center gap-3 px-4 py-2 glass-panel-light rounded-xl">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center float-badge">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">PV รายการนี้</p>
                            <p class="text-lg font-bold text-cyan-400" x-text="cartPV.toLocaleString()">0</p>
                        </div>
                    </div>

                    <!-- Points Display -->
                    <div class="flex items-center gap-3 px-4 py-2 glass-panel-light rounded-xl">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center float-badge">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">คะแนนที่จะได้</p>
                            <p class="text-lg font-bold text-amber-400" x-text="cartPoints.toLocaleString()">0</p>
                        </div>
                    </div>
                </div>

                <!-- Right: User & Actions -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <div class="font-medium text-sm">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-400">{{ auth()->user()->email }}</div>
                    </div>

                    <!-- Camera Toggle Button -->
                    <button @click="toggleCamera()"
                            class="p-2.5 rounded-xl transition-all"
                            :class="cameraActive ? 'bg-green-500 text-white neon-green' : 'glass-panel-light text-gray-400 hover:text-white'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>

                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode()" class="p-2.5 glass-panel-light rounded-xl text-gray-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>

                    @if($activeSession)
                    <a href="{{ route('pos.session.close', $activeSession) }}"
                       class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-xl text-sm font-medium transition border border-red-500/30">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        ปิดเซสชัน
                    </a>
                    @endif
                </div>
            </div>
        </header>

        <!-- ========================================
             MAIN CONTENT
             ======================================== -->
        <div class="flex-1 flex overflow-hidden" :class="isFullscreen ? 'h-screen' : ''">

            <!-- ========================================
                 LEFT: PRODUCTS SECTION
                 ======================================== -->
            <div class="flex-1 flex flex-col overflow-hidden p-4">

                <!-- Search & Camera Section -->
                <div class="mb-4 space-y-4">
                    <!-- Search Bar with Camera Toggle -->
                    <div class="flex gap-3">
                        <div class="flex-1 relative">
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.300ms="searchProducts()"
                                   @keydown.enter="handleBarcodeInput()"
                                   placeholder="🔍 ค้นหาสินค้า หรือ สแกนบาร์โค้ด..."
                                   class="w-full px-4 py-3.5 pl-12 rounded-xl glass-panel-light text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500/50 border border-white/10">
                            <svg class="absolute left-4 top-4 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <!-- Scan Button (Mobile) -->
                        <button @click="toggleCamera()"
                                class="md:hidden px-4 py-3 rounded-xl transition-all"
                                :class="cameraActive ? 'bg-green-500 text-white' : 'glass-panel-light text-gray-400'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Camera Preview (Expandable) -->
                    <div x-show="cameraActive"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="camera-container h-64 rounded-2xl overflow-hidden">
                        <video x-ref="cameraVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                        <div class="camera-overlay">
                            <div class="camera-target">
                                <div class="scan-line"></div>
                            </div>
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 flex justify-between items-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium"
                                  :class="scanStatus === 'scanning' ? 'bg-green-500/80 text-white' : 'bg-gray-900/80 text-gray-300'"
                                  x-text="scanStatus === 'scanning' ? '🔍 กำลังสแกน...' : '📷 พร้อมสแกน'">
                            </span>
                            <button @click="switchCamera()" class="p-2 rounded-full bg-white/20 hover:bg-white/30 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="mb-4 flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                    <button @click="selectedCategory = null"
                            class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all"
                            :class="selectedCategory === null ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white neon-green' : 'glass-panel-light text-gray-300 hover:text-white'">
                        ทั้งหมด
                    </button>
                    @foreach($categories as $category)
                    <button @click="selectedCategory = {{ $category->id }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all"
                            :class="selectedCategory === {{ $category->id }} ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white neon-green' : 'glass-panel-light text-gray-300 hover:text-white'">
                        {{ $category->name }}
                    </button>
                    @endforeach
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 product-grid">
                        @foreach($categories as $category)
                            @foreach($category->products as $product)
                            <div x-show="selectedCategory === null || selectedCategory === {{ $category->id }}"
                                 x-transition
                                 @click="addToCart({{ json_encode([
                                     'id' => $product->id,
                                     'name' => $product->name,
                                     'sku' => $product->sku,
                                     'barcode' => $product->barcode ?? $product->sku,
                                     'price' => $product->price,
                                     'image' => $product->image_urls[0] ?? null,
                                     'stock_quantity' => $product->stock_quantity,
                                     'track_inventory' => $product->track_inventory,
                                     'pv' => $product->pv ?? 0,
                                     'points' => $product->points ?? floor($product->price * 0.01)
                                 ]) }})"
                                 class="product-card glass-panel rounded-xl overflow-hidden cursor-pointer group">

                                <!-- Product Image -->
                                <div class="relative h-28 sm:h-32 overflow-hidden">
                                    @if($product->image_urls && count($product->image_urls) > 0)
                                    <img src="{{ asset($product->image_urls[0]) }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    @else
                                    <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    @endif

                                    <!-- Stock Badge -->
                                    @if($product->track_inventory)
                                    <div class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $product->stock_quantity > 10 ? 'bg-green-500/80' : ($product->stock_quantity > 0 ? 'bg-amber-500/80' : 'bg-red-500/80') }}">
                                        {{ $product->stock_quantity }}
                                    </div>
                                    @endif

                                    <!-- PV Badge -->
                                    @if(($product->pv ?? 0) > 0)
                                    <div class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-500/80">
                                        PV {{ $product->pv }}
                                    </div>
                                    @endif
                                </div>

                                <!-- Product Info -->
                                <div class="p-3">
                                    <h3 class="font-medium text-sm text-white truncate mb-1">{{ $product->name }}</h3>
                                    <p class="text-xs text-gray-400 mb-2">{{ $product->sku }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-green-400 font-bold">฿{{ number_format($product->price, 2) }}</span>
                                        <span class="text-xs text-amber-400">+{{ floor($product->price * 0.01) }} pts</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ========================================
                 RIGHT: CART SECTION
                 ======================================== -->
            <div class="w-80 lg:w-96 glass-panel border-l border-white/10 flex flex-col">

                <!-- Cart Header with PV Summary (Mobile) -->
                <div class="p-4 border-b border-white/10">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            รายการสินค้า
                        </h2>
                        <button @click="clearCart()" x-show="cart.length > 0"
                                class="text-sm text-red-400 hover:text-red-300 transition">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            ล้าง
                        </button>
                    </div>

                    <!-- Mobile PV/Points Summary -->
                    <div class="md:hidden grid grid-cols-2 gap-2">
                        <div class="px-3 py-2 rounded-lg bg-cyan-500/10 border border-cyan-500/20">
                            <p class="text-xs text-cyan-400">PV</p>
                            <p class="text-lg font-bold text-cyan-400" x-text="cartPV.toLocaleString()">0</p>
                        </div>
                        <div class="px-3 py-2 rounded-lg bg-amber-500/10 border border-amber-500/20">
                            <p class="text-xs text-amber-400">คะแนน</p>
                            <p class="text-lg font-bold text-amber-400" x-text="cartPoints.toLocaleString()">0</p>
                        </div>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                    <template x-if="cart.length === 0">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-full glass-panel-light flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500">ไม่มีสินค้าในตะกร้า</p>
                            <p class="text-xs text-gray-600 mt-1">คลิกสินค้าเพื่อเพิ่มลงตะกร้า</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="cart-item-enter glass-panel-light rounded-xl p-3">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-sm text-white truncate" x-text="item.name"></h4>
                                    <p class="text-xs text-gray-400" x-text="item.sku"></p>
                                </div>
                                <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-300 ml-2 p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <button @click="updateQuantity(index, -1)"
                                            class="w-7 h-7 rounded-lg glass-panel flex items-center justify-center hover:bg-white/10 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <input type="number"
                                           x-model.number="item.quantity"
                                           @change="calculateTotals()"
                                           min="1"
                                           class="w-12 text-center px-1 py-1 rounded-lg glass-panel text-white text-sm focus:outline-none focus:ring-1 focus:ring-green-500">
                                    <button @click="updateQuantity(index, 1)"
                                            class="w-7 h-7 rounded-lg glass-panel flex items-center justify-center hover:bg-white/10 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400" x-text="'฿' + parseFloat(item.price).toFixed(2)"></div>
                                    <div class="font-bold text-green-400" x-text="'฿' + (item.price * item.quantity).toFixed(2)"></div>
                                </div>
                            </div>

                            <!-- Item PV/Points -->
                            <div class="flex justify-between mt-2 pt-2 border-t border-white/5 text-xs">
                                <span class="text-cyan-400">PV: <span x-text="((item.pv || 0) * item.quantity).toLocaleString()"></span></span>
                                <span class="text-amber-400">+<span x-text="((item.points || 0) * item.quantity).toLocaleString()"></span> pts</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Summary -->
                <div class="border-t border-white/10 p-4 space-y-3">
                    <!-- Discount -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">ส่วนลด (%)</span>
                        <input type="number"
                               x-model.number="discountPercentage"
                               @input="calculateTotals()"
                               min="0"
                               max="100"
                               step="0.01"
                               class="w-20 px-2 py-1.5 text-right rounded-lg glass-panel text-white text-sm focus:outline-none focus:ring-1 focus:ring-green-500">
                    </div>

                    <!-- Totals -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-300">
                            <span>ยอดรวม</span>
                            <span x-text="'฿' + subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-red-400" x-show="discount > 0">
                            <span>ส่วนลด</span>
                            <span x-text="'-฿' + discount.toFixed(2)"></span>
                        </div>
                        @if($settings && $settings->tax_enabled)
                        <div class="flex justify-between text-gray-400">
                            <span>ภาษี ({{ $settings->tax_percentage }}%)</span>
                            <span x-text="'฿' + tax.toFixed(2)"></span>
                        </div>
                        @endif
                        @if($settings && $settings->service_charge_enabled)
                        <div class="flex justify-between text-gray-400">
                            <span>ค่าบริการ ({{ $settings->service_charge_percentage }}%)</span>
                            <span x-text="'฿' + serviceCharge.toFixed(2)"></span>
                        </div>
                        @endif

                        <!-- Grand Total -->
                        <div class="flex justify-between items-center pt-3 border-t border-white/10">
                            <span class="text-lg font-bold">รวมทั้งสิ้น</span>
                            <div class="shine-effect px-4 py-2 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500">
                                <span class="text-2xl font-black text-white" x-text="'฿' + total.toFixed(2)"></span>
                            </div>
                        </div>

                        <!-- PV/Points Summary -->
                        <div class="flex justify-between text-sm pt-2">
                            <span class="text-cyan-400">รวม PV: <span x-text="cartPV.toLocaleString()" class="font-bold"></span></span>
                            <span class="text-amber-400">รวมคะแนน: <span x-text="cartPoints.toLocaleString()" class="font-bold"></span></span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">วิธีชำระเงิน</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button @click="paymentMethod = 'cash'"
                                    :class="paymentMethod === 'cash' ? 'bg-green-500 text-white' : 'glass-panel-light text-gray-400'"
                                    class="p-2 rounded-xl text-center transition">
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="text-xs">เงินสด</span>
                            </button>
                            <button @click="paymentMethod = 'card'"
                                    :class="paymentMethod === 'card' ? 'bg-blue-500 text-white' : 'glass-panel-light text-gray-400'"
                                    class="p-2 rounded-xl text-center transition">
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                                <span class="text-xs">บัตร</span>
                            </button>
                            <button @click="paymentMethod = 'qr'"
                                    :class="paymentMethod === 'qr' ? 'bg-purple-500 text-white' : 'glass-panel-light text-gray-400'"
                                    class="p-2 rounded-xl text-center transition">
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                </svg>
                                <span class="text-xs">QR</span>
                            </button>
                            <button @click="paymentMethod = 'e-wallet'"
                                    :class="paymentMethod === 'e-wallet' ? 'bg-orange-500 text-white' : 'glass-panel-light text-gray-400'"
                                    class="p-2 rounded-xl text-center transition">
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-xs">E-Wallet</span>
                            </button>
                        </div>
                    </div>

                    <!-- Cash Amount Input -->
                    <div x-show="paymentMethod === 'cash'" x-transition class="space-y-2">
                        <label class="block text-sm font-medium text-gray-400">รับเงิน</label>
                        <input type="number"
                               x-model.number="amountPaid"
                               step="0.01"
                               class="w-full px-3 py-2.5 rounded-xl glass-panel-light text-white text-lg font-bold focus:outline-none focus:ring-2 focus:ring-green-500/50">
                        <div class="flex justify-between text-sm" x-show="amountPaid >= total">
                            <span class="text-gray-400">เงินทอน</span>
                            <span class="font-bold text-yellow-400 text-lg" x-text="'฿' + (amountPaid - total).toFixed(2)"></span>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="grid grid-cols-4 gap-1">
                            <template x-for="amount in [20, 50, 100, 500, 1000]" :key="amount">
                                <button @click="amountPaid = Math.ceil(total / amount) * amount"
                                        class="px-2 py-1.5 rounded-lg glass-panel text-xs text-gray-300 hover:bg-white/10 transition"
                                        x-text="'฿' + amount">
                                </button>
                            </template>
                            <button @click="amountPaid = total"
                                    class="px-2 py-1.5 rounded-lg bg-green-500/20 text-xs text-green-400 hover:bg-green-500/30 transition">
                                พอดี
                            </button>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <button @click="checkout()"
                            :disabled="cart.length === 0 || (paymentMethod === 'cash' && amountPaid < total) || isProcessing"
                            :class="cart.length === 0 || (paymentMethod === 'cash' && amountPaid < total) ? 'bg-gray-600 cursor-not-allowed' : 'bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 neon-green'"
                            class="w-full py-4 rounded-xl font-bold text-lg text-white transition-all relative overflow-hidden">
                        <span x-show="!isProcessing" class="flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ชำระเงิน
                        </span>
                        <span x-show="isProcessing" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            กำลังประมวลผล...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================
             FULLSCREEN TOGGLE BUTTON (Floating)
             ======================================== -->
        <button @click="toggleFullscreen()"
                class="fixed bottom-6 right-6 w-14 h-14 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg transition-all hover:scale-110 no-print z-50 neon-green"
                :title="isFullscreen ? 'ออกจากโหมดเต็มจอ' : 'เต็มจอ'">
            <svg x-show="!isFullscreen" class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
            </svg>
            <svg x-show="isFullscreen" class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"></path>
            </svg>
        </button>

        <!-- ========================================
             SUCCESS MODAL
             ======================================== -->
        <div x-show="showSuccessModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50"
             @click.self="showSuccessModal = false">
            <div class="glass-panel rounded-3xl p-8 max-w-md mx-4 text-center success-pop">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center neon-green">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">ทำรายการสำเร็จ!</h3>
                <p class="text-gray-400 mb-6">รายการชำระเงินเสร็จสมบูรณ์</p>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="glass-panel-light rounded-xl p-4">
                        <p class="text-xs text-cyan-400 mb-1">PV ที่ได้รับ</p>
                        <p class="text-2xl font-bold text-cyan-400" x-text="lastTransactionPV.toLocaleString()">0</p>
                    </div>
                    <div class="glass-panel-light rounded-xl p-4">
                        <p class="text-xs text-amber-400 mb-1">คะแนนที่ได้รับ</p>
                        <p class="text-2xl font-bold text-amber-400" x-text="lastTransactionPoints.toLocaleString()">0</p>
                    </div>
                </div>

                <button @click="showSuccessModal = false"
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold hover:from-green-600 hover:to-emerald-600 transition">
                    ตกลง
                </button>
            </div>
        </div>
    </div>

    <script>
        /**
         * POS System Alpine.js Component
         *
         * ระบบ POS สมัยใหม่พร้อม:
         * - Camera Barcode/QR Scanning
         * - PV และคะแนนแสดงผล
         * - Real-time sync กับ Customer Display
         * - High-tech animations
         */
        function posSystem() {
            return {
                // ========================================
                // STATE VARIABLES
                // ========================================

                cart: [],
                searchQuery: '',
                selectedCategory: null,
                paymentMethod: 'cash',
                discountPercentage: 0,
                amountPaid: 0,
                isFullscreen: false,
                isProcessing: false,
                showSuccessModal: false,

                // Calculated values
                subtotal: 0,
                discount: 0,
                tax: 0,
                serviceCharge: 0,
                total: 0,
                cartPV: 0,
                cartPoints: 0,

                // Last transaction info (for success modal)
                lastTransactionPV: 0,
                lastTransactionPoints: 0,

                // Camera / Scanner
                cameraActive: false,
                cameraStream: null,
                scanStatus: 'ready',
                codeReader: null,
                currentCameraIndex: 0,
                availableCameras: [],

                // Settings
                taxEnabled: {{ $settings && $settings->tax_enabled ? 'true' : 'false' }},
                taxPercentage: {{ $settings ? $settings->tax_percentage : 0 }},
                taxInclusive: {{ $settings && $settings->tax_inclusive ? 'true' : 'false' }},
                serviceChargeEnabled: {{ $settings && $settings->service_charge_enabled ? 'true' : 'false' }},
                serviceChargePercentage: {{ $settings ? ($settings->service_charge_percentage ?? 0) : 0 }},

                // Device info
                deviceCode: '{{ $device->device_code }}',
                deviceId: {{ $device->id }},
                sessionId: {{ $activeSession ? $activeSession->id : 'null' }},

                // ========================================
                // INITIALIZATION
                // ========================================

                async init() {
                    this.calculateTotals();

                    // Listen for fullscreen changes
                    const fullscreenHandler = () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    };

                    document.addEventListener('fullscreenchange', fullscreenHandler);
                    document.addEventListener('webkitfullscreenchange', fullscreenHandler);
                    document.addEventListener('mozfullscreenchange', fullscreenHandler);
                    document.addEventListener('msfullscreenchange', fullscreenHandler);

                    // Initialize barcode reader if available
                    if (typeof ZXing !== 'undefined') {
                        this.codeReader = new ZXing.BrowserMultiFormatReader();
                        try {
                            this.availableCameras = await this.codeReader.listVideoInputDevices();
                        } catch (e) {
                            console.warn('Cannot list cameras:', e);
                        }
                    }

                    console.log('🖥️ POS System initialized');
                },

                // ========================================
                // CART MANAGEMENT
                // ========================================

                addToCart(product) {
                    const existingIndex = this.cart.findIndex(item => item.id === product.id);

                    if (existingIndex > -1) {
                        this.cart[existingIndex].quantity++;
                    } else {
                        this.cart.push({
                            ...product,
                            quantity: 1,
                            pv: product.pv || 0,
                            points: product.points || Math.floor(product.price * 0.01)
                        });
                    }

                    this.calculateTotals();
                    this.syncToCustomerDisplay();

                    // Visual feedback
                    this.playAddSound();
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.calculateTotals();
                    this.syncToCustomerDisplay();
                },

                updateQuantity(index, change) {
                    const newQuantity = this.cart[index].quantity + change;
                    if (newQuantity > 0) {
                        this.cart[index].quantity = newQuantity;
                        this.calculateTotals();
                        this.syncToCustomerDisplay();
                    }
                },

                clearCart() {
                    if (confirm('คุณต้องการล้างรายการทั้งหมดหรือไม่?')) {
                        this.cart = [];
                        this.calculateTotals();
                        this.syncToCustomerDisplay();
                    }
                },

                // ========================================
                // CALCULATIONS
                // ========================================

                calculateTotals() {
                    this.subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

                    this.discount = this.discountPercentage > 0
                        ? this.subtotal * (this.discountPercentage / 100)
                        : 0;

                    const afterDiscount = this.subtotal - this.discount;

                    if (this.taxEnabled) {
                        if (this.taxInclusive) {
                            this.tax = afterDiscount - (afterDiscount / (1 + this.taxPercentage / 100));
                        } else {
                            this.tax = afterDiscount * (this.taxPercentage / 100);
                        }
                    } else {
                        this.tax = 0;
                    }

                    this.serviceCharge = this.serviceChargeEnabled
                        ? afterDiscount * (this.serviceChargePercentage / 100)
                        : 0;

                    this.total = this.taxInclusive
                        ? afterDiscount + this.serviceCharge
                        : afterDiscount + this.tax + this.serviceCharge;

                    // Calculate PV and Points
                    this.cartPV = this.cart.reduce((sum, item) => sum + ((item.pv || 0) * item.quantity), 0);
                    this.cartPoints = this.cart.reduce((sum, item) => sum + ((item.points || 0) * item.quantity), 0);

                    if (this.paymentMethod !== 'cash') {
                        this.amountPaid = this.total;
                    }
                },

                // ========================================
                // CAMERA / BARCODE SCANNING
                // ========================================

                async toggleCamera() {
                    if (this.cameraActive) {
                        this.stopCamera();
                    } else {
                        await this.startCamera();
                    }
                },

                async startCamera() {
                    if (!this.codeReader) {
                        alert('ระบบสแกนบาร์โค้ดไม่พร้อมใช้งาน');
                        return;
                    }

                    try {
                        this.cameraActive = true;
                        this.scanStatus = 'scanning';

                        const videoElement = this.$refs.cameraVideo;
                        const cameraId = this.availableCameras[this.currentCameraIndex]?.deviceId;

                        await this.codeReader.decodeFromVideoDevice(cameraId, videoElement, (result, err) => {
                            if (result) {
                                this.onBarcodeScanned(result.text);
                            }
                        });

                        console.log('📷 Camera started');
                    } catch (error) {
                        console.error('Camera error:', error);
                        this.cameraActive = false;
                        alert('ไม่สามารถเปิดกล้องได้: ' + error.message);
                    }
                },

                stopCamera() {
                    if (this.codeReader) {
                        this.codeReader.reset();
                    }
                    this.cameraActive = false;
                    this.scanStatus = 'ready';
                    console.log('📷 Camera stopped');
                },

                async switchCamera() {
                    if (this.availableCameras.length > 1) {
                        this.currentCameraIndex = (this.currentCameraIndex + 1) % this.availableCameras.length;
                        this.stopCamera();
                        await this.startCamera();
                    }
                },

                onBarcodeScanned(barcode) {
                    console.log('🔍 Barcode scanned:', barcode);

                    // ค้นหาสินค้าจาก barcode/SKU
                    this.searchQuery = barcode;
                    this.handleBarcodeInput();

                    // เสียง feedback
                    this.playBeepSound();
                },

                handleBarcodeInput() {
                    // ค้นหาสินค้าจาก searchQuery (barcode/SKU)
                    const query = this.searchQuery.trim().toLowerCase();
                    if (!query) return;

                    // ค้นหาจาก products ที่โหลดไว้
                    const productsData = @json($categories->flatMap->products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                        'barcode' => $p->barcode ?? $p->sku,
                        'price' => $p->price,
                        'image' => $p->image_urls[0] ?? null,
                        'stock_quantity' => $p->stock_quantity,
                        'track_inventory' => $p->track_inventory,
                        'pv' => $p->pv ?? 0,
                        'points' => $p->points ?? floor($p->price * 0.01)
                    ]));

                    const found = productsData.find(p =>
                        p.sku?.toLowerCase() === query ||
                        p.barcode?.toLowerCase() === query
                    );

                    if (found) {
                        this.addToCart(found);
                        this.searchQuery = '';
                    }
                },

                // ========================================
                // FULLSCREEN
                // ========================================

                toggleFullscreen() {
                    const elem = document.documentElement;

                    if (!document.fullscreenElement) {
                        if (elem.requestFullscreen) {
                            elem.requestFullscreen();
                        } else if (elem.webkitRequestFullscreen) {
                            elem.webkitRequestFullscreen();
                        } else if (elem.mozRequestFullScreen) {
                            elem.mozRequestFullScreen();
                        } else if (elem.msRequestFullscreen) {
                            elem.msRequestFullscreen();
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        } else if (document.mozCancelFullScreen) {
                            document.mozCancelFullScreen();
                        } else if (document.msExitFullscreen) {
                            document.msExitFullscreen();
                        }
                    }
                },

                // ========================================
                // CUSTOMER DISPLAY SYNC
                // ========================================

                syncToCustomerDisplay() {
                    // Sync via localStorage (fallback)
                    const displayData = {
                        action: this.cart.length > 0 ? 'update' : 'clear',
                        items: this.cart.map(item => ({
                            name: item.name,
                            quantity: item.quantity,
                            price: item.price,
                            pv: item.pv || 0,
                            points: item.points || 0
                        })),
                        subtotal: this.subtotal,
                        discount: this.discount,
                        tax: this.tax,
                        serviceCharge: this.serviceCharge,
                        total: this.total,
                        cartPV: this.cartPV,
                        cartPoints: this.cartPoints
                    };

                    localStorage.setItem('pos_customer_display', JSON.stringify(displayData));

                    // Also try API sync if available
                    this.syncViaAPI(displayData);
                },

                async syncViaAPI(data) {
                    try {
                        await fetch('/pos/api/display/cart/update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                device_code: this.deviceCode,
                                ...data
                            })
                        });
                    } catch (error) {
                        // Silent fail - localStorage fallback will work
                    }
                },

                // ========================================
                // CHECKOUT
                // ========================================

                async checkout() {
                    if (this.cart.length === 0) {
                        alert('กรุณาเพิ่มสินค้าในตะกร้า');
                        return;
                    }

                    if (this.paymentMethod === 'cash' && this.amountPaid < this.total) {
                        alert('จำนวนเงินที่รับไม่เพียงพอ');
                        return;
                    }

                    this.isProcessing = true;

                    const data = {
                        device_id: this.deviceId,
                        session_id: this.sessionId,
                        items: this.cart.map(item => ({
                            product_id: item.id,
                            quantity: item.quantity,
                            price: item.price
                        })),
                        payment_method: this.paymentMethod,
                        amount_paid: this.paymentMethod === 'cash' ? this.amountPaid : this.total,
                        discount_percentage: this.discountPercentage
                    };

                    try {
                        const response = await fetch('{{ route("pos.checkout") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Store PV/Points for modal
                            this.lastTransactionPV = this.cartPV;
                            this.lastTransactionPoints = this.cartPoints;

                            // Open receipt
                            window.open(`/pos/receipt/${result.transaction.id}`, '_blank');

                            // Clear cart
                            this.cart = [];
                            this.discountPercentage = 0;
                            this.amountPaid = 0;
                            this.calculateTotals();

                            // Clear customer display
                            localStorage.setItem('pos_customer_display', JSON.stringify({ action: 'clear' }));

                            // Show success modal
                            this.showSuccessModal = true;

                            // Play success sound
                            this.playSuccessSound();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Checkout error:', error);
                        alert('เกิดข้อผิดพลาดในการทำรายการ');
                    } finally {
                        this.isProcessing = false;
                    }
                },

                // ========================================
                // SEARCH
                // ========================================

                async searchProducts() {
                    // Client-side filtering is already handled via x-show
                    // This method can be extended for server-side search
                },

                // ========================================
                // SOUND EFFECTS
                // ========================================

                playAddSound() {
                    // Simple click sound using Web Audio API
                    try {
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.value = 800;
                        oscillator.type = 'sine';
                        gainNode.gain.value = 0.1;

                        oscillator.start();
                        oscillator.stop(audioContext.currentTime + 0.1);
                    } catch (e) {
                        // Silent fail
                    }
                },

                playBeepSound() {
                    try {
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.value = 1000;
                        oscillator.type = 'sine';
                        gainNode.gain.value = 0.2;

                        oscillator.start();
                        oscillator.stop(audioContext.currentTime + 0.15);
                    } catch (e) {
                        // Silent fail
                    }
                },

                playSuccessSound() {
                    try {
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const notes = [523.25, 659.25, 783.99]; // C5, E5, G5

                        notes.forEach((freq, i) => {
                            const oscillator = audioContext.createOscillator();
                            const gainNode = audioContext.createGain();

                            oscillator.connect(gainNode);
                            gainNode.connect(audioContext.destination);

                            oscillator.frequency.value = freq;
                            oscillator.type = 'sine';
                            gainNode.gain.value = 0.1;

                            oscillator.start(audioContext.currentTime + i * 0.1);
                            oscillator.stop(audioContext.currentTime + i * 0.1 + 0.2);
                        });
                    } catch (e) {
                        // Silent fail
                    }
                },

                // ========================================
                // DARK MODE
                // ========================================

                toggleDarkMode() {
                    const isDark = document.documentElement.classList.contains('dark');
                    if (isDark) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('darkMode', 'light');
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('darkMode', 'dark');
                    }
                }
            };
        }
    </script>
</body>
</html>

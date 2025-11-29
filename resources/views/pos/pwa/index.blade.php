<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TP-POS">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">

    <title>TP-POS - ระบบขายหน้าร้าน</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/pos-manifest.json">

    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/images/pos/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/pos/icon-16x16.png">
    <link rel="apple-touch-icon" href="/images/pos/icon-192x192.png">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- ZXing Barcode Scanner -->
    <script src="https://unpkg.com/@AziCode/browser@0.0.10/dist/umd/index.min.js" defer></script>

    <!-- Alpine.js Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ============================================
         * POS PWA - MODERN V3 DESIGN
         * ============================================ */

        :root {
            --safe-area-inset-top: env(safe-area-inset-top, 0);
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0);
            --safe-area-inset-left: env(safe-area-inset-left, 0);
            --safe-area-inset-right: env(safe-area-inset-right, 0);
        }

        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }

        html, body {
            overscroll-behavior: none;
            touch-action: manipulation;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            padding-top: var(--safe-area-inset-top);
            padding-bottom: var(--safe-area-inset-bottom);
            padding-left: var(--safe-area-inset-left);
            padding-right: var(--safe-area-inset-right);
        }

        /* PWA Background */
        .pos-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            min-height: 100dvh;
        }

        .pos-bg::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-light {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Touch-friendly buttons */
        .touch-btn {
            min-height: 48px;
            min-width: 48px;
            transition: all 0.15s ease;
            -webkit-user-select: none;
            user-select: none;
        }

        .touch-btn:active {
            transform: scale(0.95);
            opacity: 0.9;
        }

        /* Product Card */
        .product-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            will-change: transform;
        }

        .product-card:active {
            transform: scale(0.96);
        }

        @media (hover: hover) {
            .product-card:hover {
                transform: translateY(-4px) scale(1.02);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
            }
        }

        /* Cart Item Animation */
        .cart-item-enter {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Neon Effects */
        .neon-green {
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.4);
        }

        .neon-blue {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }

        /* Scrollbar */
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

        /* Numpad */
        .numpad-btn {
            font-size: 1.5rem;
            font-weight: 600;
            min-height: 60px;
            transition: all 0.1s;
        }

        .numpad-btn:active {
            transform: scale(0.92);
            background: rgba(255, 255, 255, 0.2);
        }

        /* Loading Spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Status Bar */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-online { background: #22c55e; }
        .status-offline { background: #f59e0b; }
        .status-syncing { background: #3b82f6; animation: pulse 0.5s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Modal Backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }

        /* Quick amount buttons */
        .quick-amount {
            transition: all 0.15s;
        }

        .quick-amount:active {
            transform: scale(0.95);
            background: rgba(34, 197, 94, 0.3);
        }
    </style>
</head>
<body class="pos-bg text-white antialiased overflow-hidden">

    <!-- Main App Container -->
    <div
        x-data="posApp()"
        x-init="init()"
        @keydown.window="handleBarcodeInput($event)"
        class="relative z-10 h-screen h-[100dvh] flex flex-col"
    >
        <!-- Loading Screen -->
        <template x-if="loading">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900">
                <div class="text-center">
                    <div class="w-16 h-16 border-4 border-green-500/30 border-t-green-500 rounded-full spinner mx-auto"></div>
                    <p class="mt-4 text-gray-400">กำลังโหลดระบบ...</p>
                </div>
            </div>
        </template>

        <!-- ============================================
             TOP BAR
             ============================================ -->
        <header class="flex-shrink-0 glass border-b border-white/10 px-4 py-2 safe-area-top">
            <div class="flex items-center justify-between">
                <!-- Left: Logo & Status -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg leading-tight">TP-POS</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <span class="status-indicator" :class="isOnline ? 'status-online' : 'status-offline'"></span>
                            <span x-text="isOnline ? 'ออนไลน์' : 'ออฟไลน์'"></span>
                            <template x-if="isSyncing">
                                <span class="text-blue-400">| กำลังซิงค์...</span>
                            </template>
                            <template x-if="pendingSyncCount > 0 && !isSyncing">
                                <span class="text-amber-400">| รอซิงค์ <span x-text="pendingSyncCount"></span></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Center: Search -->
                <div class="hidden md:flex flex-1 max-w-md mx-4">
                    <div class="relative w-full">
                        <input
                            id="search-input"
                            type="text"
                            x-model="searchQuery"
                            @input.debounce.200ms="searchProducts()"
                            placeholder="ค้นหาสินค้า (F1) หรือสแกน barcode..."
                            class="w-full px-4 py-2.5 pl-10 rounded-xl bg-white/10 border border-white/10 text-white placeholder-gray-400 focus:outline-none focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20"
                        >
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2">
                    <!-- Sync Button -->
                    <button
                        @click="syncData()"
                        :disabled="isSyncing || !isOnline"
                        class="touch-btn p-2.5 rounded-xl bg-white/10 hover:bg-white/20 disabled:opacity-50"
                        title="ซิงค์ข้อมูล"
                    >
                        <svg class="w-5 h-5" :class="isSyncing ? 'spinner' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>

                    <!-- Settings -->
                    <button
                        @click="showSettingsModal = true"
                        class="touch-btn p-2.5 rounded-xl bg-white/10 hover:bg-white/20"
                        title="ตั้งค่า"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Search (shown below header on small screens) -->
            <div class="md:hidden mt-2">
                <div class="relative">
                    <input
                        type="text"
                        x-model="searchQuery"
                        @input.debounce.200ms="searchProducts()"
                        placeholder="ค้นหาหรือสแกน..."
                        class="w-full px-4 py-2.5 pl-10 rounded-xl bg-white/10 border border-white/10 text-white placeholder-gray-400 focus:outline-none focus:border-green-500/50"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </header>

        <!-- ============================================
             MAIN CONTENT
             ============================================ -->
        <main class="flex-1 flex overflow-hidden">

            <!-- ============================================
                 LEFT: PRODUCTS GRID
                 ============================================ -->
            <section class="flex-1 flex flex-col min-w-0 overflow-hidden">

                <!-- Categories -->
                <div class="flex-shrink-0 px-4 py-3 border-b border-white/5">
                    <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                        <!-- All Products -->
                        <button
                            @click="selectCategory(null)"
                            class="touch-btn flex-shrink-0 px-4 py-2 rounded-xl text-sm font-medium transition-all"
                            :class="!selectedCategory ? 'bg-green-500 text-white neon-green' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                        >
                            ทั้งหมด
                        </button>

                        <!-- Category Buttons -->
                        <template x-for="category in categories" :key="category.id">
                            <button
                                @click="selectCategory(category.id)"
                                class="touch-btn flex-shrink-0 px-4 py-2 rounded-xl text-sm font-medium transition-all"
                                :class="selectedCategory === category.id ? 'bg-green-500 text-white neon-green' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
                            >
                                <span x-text="category.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button
                                @click="addToCart(product)"
                                class="product-card glass-light rounded-2xl p-3 text-left focus:outline-none focus:ring-2 focus:ring-green-500/50"
                            >
                                <!-- Product Image -->
                                <div class="aspect-square rounded-xl bg-white/10 mb-2 overflow-hidden">
                                    <template x-if="product.image_url || product.image">
                                        <img
                                            :src="product.image_url || product.image"
                                            :alt="product.name"
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                        >
                                    </template>
                                    <template x-if="!product.image_url && !product.image">
                                        <div class="w-full h-full flex items-center justify-center text-gray-500">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                    </template>
                                </div>

                                <!-- Product Info -->
                                <h3 class="font-medium text-sm text-white truncate" x-text="product.name"></h3>
                                <p class="text-xs text-gray-400 truncate" x-text="product.sku"></p>
                                <p class="mt-1 font-bold text-green-400">
                                    <span x-text="settings.currency"></span><span x-text="formatCurrency(product.price)"></span>
                                </p>

                                <!-- Stock indicator -->
                                <template x-if="product.track_inventory">
                                    <p class="text-xs mt-1"
                                       :class="product.stock_quantity > product.low_stock_threshold ? 'text-gray-400' : 'text-amber-400'">
                                        คงเหลือ: <span x-text="product.stock_quantity"></span>
                                    </p>
                                </template>
                            </button>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <template x-if="filteredProducts.length === 0 && !loading">
                        <div class="flex flex-col items-center justify-center h-full text-center py-12">
                            <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-gray-400">ไม่พบสินค้า</p>
                            <button
                                @click="forceSyncProducts()"
                                class="mt-4 px-4 py-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30"
                            >
                                โหลดสินค้าใหม่
                            </button>
                        </div>
                    </template>
                </div>
            </section>

            <!-- ============================================
                 RIGHT: CART PANEL
                 ============================================ -->
            <aside class="w-80 lg:w-96 flex-shrink-0 glass-light flex flex-col border-l border-white/10">

                <!-- Cart Header -->
                <div class="flex-shrink-0 px-4 py-3 border-b border-white/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="font-semibold">ตะกร้าสินค้า</span>
                            <span class="px-2 py-0.5 bg-green-500/20 text-green-400 text-xs rounded-full" x-text="cartItemCount + ' รายการ'"></span>
                        </div>
                        <button
                            @click="clearCart()"
                            x-show="cart.length > 0"
                            class="text-xs text-red-400 hover:text-red-300"
                        >
                            ล้างทั้งหมด
                        </button>
                    </div>

                    <!-- Customer -->
                    <div class="mt-2">
                        <template x-if="customer">
                            <div class="flex items-center justify-between p-2 bg-blue-500/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="text-sm text-blue-300" x-text="customer.name"></span>
                                </div>
                                <button @click="clearCustomer()" class="text-blue-400 hover:text-blue-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="!customer">
                            <button
                                @click="openCustomerModal()"
                                class="w-full p-2 text-sm text-gray-400 border border-dashed border-gray-600 rounded-lg hover:border-gray-500 hover:text-gray-300 flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                เพิ่มลูกค้า (F3)
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-center">
                            <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <p class="text-gray-500">ยังไม่มีสินค้าในตะกร้า</p>
                            <p class="text-xs text-gray-600 mt-1">สแกนหรือเลือกสินค้าจากรายการ</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="item.id || index">
                        <div class="cart-item-enter glass rounded-xl p-3">
                            <div class="flex gap-3">
                                <!-- Image -->
                                <div class="w-14 h-14 rounded-lg bg-white/10 flex-shrink-0 overflow-hidden">
                                    <template x-if="item.product_image">
                                        <img :src="item.product_image" class="w-full h-full object-cover">
                                    </template>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-sm text-white truncate" x-text="item.product_name"></h4>
                                    <p class="text-xs text-gray-400" x-text="settings.currency + formatCurrency(item.unit_price) + ' / ชิ้น'"></p>
                                </div>

                                <!-- Remove -->
                                <button
                                    @click="removeFromCart(item.id)"
                                    class="text-red-400 hover:text-red-300 p-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Quantity & Subtotal -->
                            <div class="flex items-center justify-between mt-2">
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="updateCartItemQty(item, -1)"
                                        class="touch-btn w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center hover:bg-white/20"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="openNumpad('qty_' + item.id, item.quantity)"
                                        class="touch-btn w-12 h-8 rounded-lg bg-white/10 flex items-center justify-center font-medium hover:bg-white/20"
                                        x-text="item.quantity"
                                    ></button>
                                    <button
                                        @click="updateCartItemQty(item, 1)"
                                        class="touch-btn w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center hover:bg-white/20"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="font-semibold text-green-400">
                                    <span x-text="settings.currency"></span><span x-text="formatCurrency(item.unit_price * item.quantity)"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Summary & Checkout -->
                <div class="flex-shrink-0 border-t border-white/10 p-4 space-y-3">
                    <!-- Summary -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-400">
                            <span>ยอดรวม</span>
                            <span><span x-text="settings.currency"></span><span x-text="formatCurrency(cartSubtotal)"></span></span>
                        </div>
                        <template x-if="cartDiscount > 0">
                            <div class="flex justify-between text-red-400">
                                <span>ส่วนลด</span>
                                <span>-<span x-text="settings.currency"></span><span x-text="formatCurrency(cartDiscount)"></span></span>
                            </div>
                        </template>
                        <template x-if="settings.taxEnabled && cartTax > 0">
                            <div class="flex justify-between text-gray-400">
                                <span>ภาษี (<span x-text="settings.taxPercentage"></span>%)</span>
                                <span><span x-text="settings.currency"></span><span x-text="formatCurrency(cartTax)"></span></span>
                            </div>
                        </template>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center py-3 border-t border-white/10">
                        <span class="text-lg font-semibold">รวมสุทธิ</span>
                        <span class="text-2xl font-bold text-green-400">
                            <span x-text="settings.currency"></span><span x-text="formatCurrency(cartTotal)"></span>
                        </span>
                    </div>

                    <!-- Checkout Button -->
                    <button
                        @click="openPaymentModal()"
                        :disabled="cart.length === 0"
                        class="touch-btn w-full py-4 rounded-2xl font-bold text-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="cart.length > 0 ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 neon-green' : 'bg-gray-600'"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            ชำระเงิน (F2)
                        </span>
                    </button>
                </div>
            </aside>
        </main>

        <!-- ============================================
             PAYMENT MODAL
             ============================================ -->
        <template x-if="showPaymentModal">
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
                @click.self="showPaymentModal = false"
            >
                <div class="glass-light rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-hidden" @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                        <h2 class="text-xl font-bold">ชำระเงิน</h2>
                        <button @click="showPaymentModal = false" class="p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto max-h-[70vh]">
                        <!-- Total Display -->
                        <div class="text-center mb-6">
                            <p class="text-gray-400 text-sm mb-1">ยอดชำระ</p>
                            <p class="text-4xl font-bold text-green-400">
                                <span x-text="settings.currency"></span><span x-text="formatCurrency(cartTotal)"></span>
                            </p>
                        </div>

                        <!-- Payment Methods -->
                        <div class="grid grid-cols-4 gap-3 mb-6">
                            <button
                                @click="selectPaymentMethod('cash')"
                                class="touch-btn p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all"
                                :class="paymentMethod === 'cash' ? 'border-green-500 bg-green-500/20' : 'border-white/10 hover:border-white/30'"
                            >
                                <span class="text-2xl">💵</span>
                                <span class="text-sm">เงินสด</span>
                            </button>
                            <button
                                @click="selectPaymentMethod('card')"
                                class="touch-btn p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all"
                                :class="paymentMethod === 'card' ? 'border-green-500 bg-green-500/20' : 'border-white/10 hover:border-white/30'"
                            >
                                <span class="text-2xl">💳</span>
                                <span class="text-sm">บัตร</span>
                            </button>
                            <button
                                @click="selectPaymentMethod('qr')"
                                class="touch-btn p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all"
                                :class="paymentMethod === 'qr' ? 'border-green-500 bg-green-500/20' : 'border-white/10 hover:border-white/30'"
                            >
                                <span class="text-2xl">📱</span>
                                <span class="text-sm">QR</span>
                            </button>
                            <button
                                @click="selectPaymentMethod('wallet')"
                                class="touch-btn p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all"
                                :class="paymentMethod === 'wallet' ? 'border-green-500 bg-green-500/20' : 'border-white/10 hover:border-white/30'"
                            >
                                <span class="text-2xl">👛</span>
                                <span class="text-sm">Wallet</span>
                            </button>
                        </div>

                        <!-- Cash Payment -->
                        <template x-if="paymentMethod === 'cash'">
                            <div>
                                <!-- Cash Received Input -->
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-400 mb-2">รับเงิน</label>
                                    <div
                                        @click="openNumpad('cash', cashReceived)"
                                        class="w-full p-4 text-2xl font-bold text-center bg-white/10 border border-white/20 rounded-xl cursor-pointer hover:border-green-500/50"
                                    >
                                        <span x-text="settings.currency"></span><span x-text="formatCurrency(cashReceived)"></span>
                                    </div>
                                </div>

                                <!-- Quick Amount Buttons -->
                                <div class="grid grid-cols-4 gap-2 mb-4">
                                    <button
                                        @click="setCashReceived(Math.ceil(cartTotal))"
                                        class="quick-amount touch-btn py-3 rounded-xl bg-white/10 hover:bg-white/20 text-sm font-medium"
                                    >
                                        พอดี
                                    </button>
                                    <button
                                        @click="setCashReceived(Math.ceil(cartTotal / 100) * 100)"
                                        class="quick-amount touch-btn py-3 rounded-xl bg-white/10 hover:bg-white/20 text-sm font-medium"
                                        x-text="settings.currency + formatNumber(Math.ceil(cartTotal / 100) * 100)"
                                    ></button>
                                    <button
                                        @click="setCashReceived(Math.ceil(cartTotal / 500) * 500)"
                                        class="quick-amount touch-btn py-3 rounded-xl bg-white/10 hover:bg-white/20 text-sm font-medium"
                                        x-text="settings.currency + formatNumber(Math.ceil(cartTotal / 500) * 500)"
                                    ></button>
                                    <button
                                        @click="setCashReceived(Math.ceil(cartTotal / 1000) * 1000)"
                                        class="quick-amount touch-btn py-3 rounded-xl bg-white/10 hover:bg-white/20 text-sm font-medium"
                                        x-text="settings.currency + formatNumber(Math.ceil(cartTotal / 1000) * 1000)"
                                    ></button>
                                </div>

                                <!-- Change Display -->
                                <div class="p-4 rounded-xl" :class="changeAmount >= 0 ? 'bg-green-500/20' : 'bg-red-500/20'">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg">เงินทอน</span>
                                        <span class="text-2xl font-bold" :class="changeAmount >= 0 ? 'text-green-400' : 'text-red-400'">
                                            <span x-text="settings.currency"></span><span x-text="formatCurrency(changeAmount)"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- QR Payment -->
                        <template x-if="paymentMethod === 'qr'">
                            <div class="text-center py-8">
                                <div class="inline-block p-4 bg-white rounded-2xl mb-4">
                                    <div class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                                        <span class="text-gray-500">QR Code</span>
                                    </div>
                                </div>
                                <p class="text-gray-400">สแกน QR Code เพื่อชำระเงิน</p>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-white/10">
                        <button
                            @click="processPayment()"
                            :disabled="paymentMethod === 'cash' && cashReceived < cartTotal"
                            class="touch-btn w-full py-4 rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            ยืนยันการชำระเงิน
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- ============================================
             NUMPAD MODAL
             ============================================ -->
        <template x-if="showNumpad">
            <div
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 modal-backdrop"
                @click.self="showNumpad = false"
            >
                <div class="glass-light rounded-t-3xl sm:rounded-3xl w-full max-w-sm p-6" @click.stop>
                    <!-- Display -->
                    <div class="mb-4 p-4 bg-white/10 rounded-xl text-right">
                        <p class="text-3xl font-bold" x-text="numpadValue || '0'"></p>
                    </div>

                    <!-- Numpad -->
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="key in ['7', '8', '9', '←', '4', '5', '6', 'C', '1', '2', '3', '.', '00', '0', '', '✓']">
                            <button
                                @click="key === '✓' ? numpadConfirm() : (key !== '' && numpadPress(key))"
                                :disabled="key === ''"
                                class="numpad-btn rounded-xl"
                                :class="{
                                    'bg-green-500 text-white': key === '✓',
                                    'bg-red-500/50 text-white': key === 'C',
                                    'bg-amber-500/50 text-white': key === '←',
                                    'bg-white/10 hover:bg-white/20': !['✓', 'C', '←', ''].includes(key),
                                    'invisible': key === ''
                                }"
                                x-text="key"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <!-- ============================================
             TOAST NOTIFICATION
             ============================================ -->
        <div
            x-data="{ toasts: [] }"
            @show-toast.window="toasts.push({message: $event.detail.message, type: $event.detail.type, id: Date.now()}); setTimeout(() => toasts.shift(), 3000)"
            class="fixed bottom-4 right-4 z-50 space-y-2"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    class="px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 animate-slide-up"
                    :class="{
                        'bg-green-500': toast.type === 'success',
                        'bg-red-500': toast.type === 'error',
                        'bg-amber-500': toast.type === 'warning',
                        'bg-blue-500': toast.type === 'info'
                    }"
                >
                    <span x-text="toast.message"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- Service Worker Registration -->
    <script>
        // ลงทะเบียน Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/pos-sw.js', {
                        scope: '/pos/'
                    });
                    console.log('SW registered:', registration.scope);
                } catch (error) {
                    console.error('SW registration failed:', error);
                }
            });
        }

        // ป้องกัน double-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd < 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
    </script>

    <!-- POS App Module -->
    <script type="module">
        import { posApp, registerPosComponent } from '/resources/js/pos/app.js';

        // Register Alpine component
        document.addEventListener('alpine:init', () => {
            Alpine.data('posApp', posApp);
        });
    </script>

    <style>
        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-slide-up {
            animation: slide-up 0.3s ease;
        }
    </style>
</body>
</html>

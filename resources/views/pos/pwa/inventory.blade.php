<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <title>จัดการสต็อก - TP-POS</title>

    <link rel="manifest" href="/pos-manifest.json">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .pos-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .touch-btn {
            min-height: 48px;
            transition: all 0.15s ease;
        }
        .touch-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body class="pos-bg text-white antialiased">
    <div x-data="inventoryApp()" x-init="init()" class="min-h-screen">
        <!-- Header -->
        <header class="glass sticky top-0 z-40 px-4 py-3 border-b border-white/10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="/pos/app" class="p-2 rounded-lg hover:bg-white/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold">จัดการสต็อก</h1>
                        <p class="text-xs text-gray-400">Inventory Management</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="showStockInModal = true"
                        class="touch-btn px-4 py-2 bg-green-500 hover:bg-green-600 rounded-xl font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        รับสินค้าเข้า
                    </button>
                    <button
                        @click="showStockCountModal = true"
                        class="touch-btn px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-xl font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        นับสต็อก
                    </button>
                </div>
            </div>
        </header>

        <!-- Filters & Search -->
        <div class="px-4 py-4 border-b border-white/5">
            <div class="flex flex-wrap gap-3">
                <div class="flex-1 min-w-[250px]">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="ค้นหาสินค้า..."
                        class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/10 text-white placeholder-gray-400 focus:outline-none focus:border-green-500/50"
                    >
                </div>

                <select
                    x-model="filterStatus"
                    class="px-4 py-2.5 rounded-xl bg-white/10 border border-white/10 text-white focus:outline-none"
                >
                    <option value="all">ทั้งหมด</option>
                    <option value="low">สต็อกต่ำ</option>
                    <option value="out">หมดสต็อก</option>
                    <option value="normal">ปกติ</option>
                </select>

                <select
                    x-model="filterCategory"
                    class="px-4 py-2.5 rounded-xl bg-white/10 border border-white/10 text-white focus:outline-none"
                >
                    <option value="">ทุกหมวดหมู่</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                <div class="glass rounded-xl p-4">
                    <p class="text-sm text-gray-400">สินค้าทั้งหมด</p>
                    <p class="text-2xl font-bold text-white" x-text="products.length"></p>
                </div>
                <div class="glass rounded-xl p-4">
                    <p class="text-sm text-gray-400">สต็อกต่ำ</p>
                    <p class="text-2xl font-bold text-amber-400" x-text="lowStockCount"></p>
                </div>
                <div class="glass rounded-xl p-4">
                    <p class="text-sm text-gray-400">หมดสต็อก</p>
                    <p class="text-2xl font-bold text-red-400" x-text="outOfStockCount"></p>
                </div>
                <div class="glass rounded-xl p-4">
                    <p class="text-sm text-gray-400">มูลค่าสต็อก</p>
                    <p class="text-2xl font-bold text-green-400">฿<span x-text="formatNumber(totalStockValue)"></span></p>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="p-4">
            <div class="glass rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-white/5">
                            <tr class="text-left text-sm text-gray-400">
                                <th class="px-4 py-3">สินค้า</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3 text-right">ราคาทุน</th>
                                <th class="px-4 py-3 text-right">ราคาขาย</th>
                                <th class="px-4 py-3 text-center">สต็อก</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <template x-for="product in filteredProducts" :key="product.id">
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-white/10 overflow-hidden">
                                                <template x-if="product.image">
                                                    <img :src="product.image_url || product.image" class="w-full h-full object-cover">
                                                </template>
                                            </div>
                                            <span class="font-medium" x-text="product.name"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400" x-text="product.sku"></td>
                                    <td class="px-4 py-3 text-right text-gray-400">฿<span x-text="formatNumber(product.cost_price || 0)"></span></td>
                                    <td class="px-4 py-3 text-right text-green-400">฿<span x-text="formatNumber(product.price)"></span></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-bold" :class="{
                                            'text-red-400': product.stock_quantity === 0,
                                            'text-amber-400': product.stock_quantity > 0 && product.stock_quantity <= product.low_stock_threshold,
                                            'text-green-400': product.stock_quantity > product.low_stock_threshold
                                        }" x-text="product.stock_quantity"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-red-500/20 text-red-400': product.stock_quantity === 0,
                                                'bg-amber-500/20 text-amber-400': product.stock_quantity > 0 && product.stock_quantity <= product.low_stock_threshold,
                                                'bg-green-500/20 text-green-400': product.stock_quantity > product.low_stock_threshold
                                            }"
                                            x-text="product.stock_quantity === 0 ? 'หมด' : (product.stock_quantity <= product.low_stock_threshold ? 'ต่ำ' : 'ปกติ')">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="adjustStock(product, 'in')"
                                                class="p-2 rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500/30"
                                                title="รับเข้า"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                            </button>
                                            <button
                                                @click="adjustStock(product, 'out')"
                                                class="p-2 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/30"
                                                title="เบิกออก"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                            </button>
                                            <button
                                                @click="viewHistory(product)"
                                                class="p-2 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"
                                                title="ประวัติ"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <template x-if="showAdjustModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70" @click.self="showAdjustModal = false">
                <div class="glass rounded-2xl w-full max-w-md p-6" @click.stop>
                    <h3 class="text-xl font-bold mb-4" x-text="adjustType === 'in' ? 'รับสินค้าเข้า' : 'เบิกสินค้าออก'"></h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">สินค้า</label>
                            <p class="font-medium" x-text="selectedProduct?.name"></p>
                            <p class="text-sm text-gray-400">สต็อกปัจจุบัน: <span x-text="selectedProduct?.stock_quantity"></span></p>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">จำนวน</label>
                            <input
                                type="number"
                                x-model="adjustQuantity"
                                min="1"
                                class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white text-xl font-bold text-center"
                            >
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">หมายเหตุ</label>
                            <input
                                type="text"
                                x-model="adjustNote"
                                placeholder="เหตุผล..."
                                class="w-full px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-white"
                            >
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button
                            @click="showAdjustModal = false"
                            class="flex-1 py-3 rounded-xl bg-white/10 hover:bg-white/20 font-medium"
                        >
                            ยกเลิก
                        </button>
                        <button
                            @click="confirmAdjust()"
                            class="flex-1 py-3 rounded-xl font-medium"
                            :class="adjustType === 'in' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
                        >
                            ยืนยัน
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function inventoryApp() {
            return {
                products: [],
                categories: [],
                searchQuery: '',
                filterStatus: 'all',
                filterCategory: '',
                showStockInModal: false,
                showStockCountModal: false,
                showAdjustModal: false,
                selectedProduct: null,
                adjustType: 'in',
                adjustQuantity: 1,
                adjustNote: '',

                async init() {
                    await this.loadProducts();
                    await this.loadCategories();
                },

                async loadProducts() {
                    try {
                        const response = await fetch('/pos/api/products/all');
                        const data = await response.json();
                        if (data.success) {
                            this.products = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to load products:', error);
                    }
                },

                async loadCategories() {
                    try {
                        const response = await fetch('/pos/api/categories/all');
                        const data = await response.json();
                        if (data.success) {
                            this.categories = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to load categories:', error);
                    }
                },

                get filteredProducts() {
                    let result = this.products;

                    if (this.searchQuery) {
                        const q = this.searchQuery.toLowerCase();
                        result = result.filter(p =>
                            p.name?.toLowerCase().includes(q) ||
                            p.sku?.toLowerCase().includes(q)
                        );
                    }

                    if (this.filterCategory) {
                        result = result.filter(p => p.category_id == this.filterCategory);
                    }

                    if (this.filterStatus === 'low') {
                        result = result.filter(p => p.stock_quantity > 0 && p.stock_quantity <= p.low_stock_threshold);
                    } else if (this.filterStatus === 'out') {
                        result = result.filter(p => p.stock_quantity === 0);
                    } else if (this.filterStatus === 'normal') {
                        result = result.filter(p => p.stock_quantity > p.low_stock_threshold);
                    }

                    return result;
                },

                get lowStockCount() {
                    return this.products.filter(p => p.stock_quantity > 0 && p.stock_quantity <= p.low_stock_threshold).length;
                },

                get outOfStockCount() {
                    return this.products.filter(p => p.stock_quantity === 0).length;
                },

                get totalStockValue() {
                    return this.products.reduce((sum, p) => sum + ((p.cost_price || p.price) * p.stock_quantity), 0);
                },

                adjustStock(product, type) {
                    this.selectedProduct = product;
                    this.adjustType = type;
                    this.adjustQuantity = 1;
                    this.adjustNote = '';
                    this.showAdjustModal = true;
                },

                async confirmAdjust() {
                    try {
                        const response = await fetch('/pos/api/stock/movement', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                product_id: this.selectedProduct.id,
                                type: this.adjustType,
                                quantity: this.adjustQuantity,
                                reason: this.adjustNote
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // อัพเดทสต็อกใน local
                            const product = this.products.find(p => p.id === this.selectedProduct.id);
                            if (product) {
                                product.stock_quantity = data.data.new_stock;
                            }
                            this.showAdjustModal = false;
                        }
                    } catch (error) {
                        console.error('Failed to adjust stock:', error);
                    }
                },

                viewHistory(product) {
                    // TODO: Show history modal
                    alert('Coming soon: Stock history for ' + product.name);
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('th-TH').format(num);
                }
            };
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Cashier - {{ $device->device_name }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    <style>
        /* POS specific styles */
        .pos-container {
            height: 100vh;
            overflow: hidden;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }

        .cart-item {
            transition: all 0.2s;
        }

        .cart-item:hover {
            transform: translateX(-4px);
        }

        /* Fullscreen exit button */
        .fullscreen-exit-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: rgba(34, 197, 94, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
        }

        .fullscreen-exit-btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        .fullscreen-exit-btn:hover {
            background: rgba(34, 197, 94, 1);
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        /* Hide header in fullscreen */
        :fullscreen .fullscreen-hide {
            display: none !important;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="pos-container" x-data="posSystem()">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 no-print fullscreen-hide">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-cash-register text-green-600"></i>
                        {{ $device->device_name }}
                    </h1>
                    @if($activeSession)
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 rounded-full text-sm font-medium">
                            <i class="fas fa-circle text-green-500 text-xs animate-pulse"></i>
                            เซสชันเปิดอยู่
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right text-sm">
                        <div class="font-medium">{{ $device->store->name }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ auth()->user()->name }}</div>
                    </div>

                    <!-- Fullscreen Toggle -->
                    <button @click="toggleFullscreen()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600" title="โหมดเต็มหน้าจอ">
                        <i class="fas fa-expand" x-show="!isFullscreen"></i>
                        <i class="fas fa-compress" x-show="isFullscreen"></i>
                    </button>

                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600" title="โหมดมืด/สว่าง">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>

                    @if($activeSession)
                        <a href="{{ route('pos.session.close', $activeSession) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                            <i class="fas fa-sign-out-alt"></i>
                            ปิดเซสชัน
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex" :class="isFullscreen ? 'h-screen' : 'h-[calc(100vh-64px)]'">
            <!-- Products Section (Left) -->
            <div class="flex-1 overflow-y-auto p-4">
                <!-- Search Bar -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="searchProducts()"
                               placeholder="ค้นหาสินค้า (ชื่อ, SKU, บาร์โค้ด)"
                               class="w-full px-4 py-3 pl-12 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>
                    </div>
                </div>

                <!-- Categories -->
                <div class="mb-4 flex gap-2 overflow-x-auto pb-2">
                    <button @click="selectedCategory = null"
                            :class="selectedCategory === null ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition">
                        ทั้งหมด
                    </button>
                    @foreach($categories as $category)
                        <button @click="selectedCategory = {{ $category->id }}"
                                :class="selectedCategory === {{ $category->id }} ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <!-- Products Grid -->
                <div class="product-grid">
                    @foreach($categories as $category)
                        @foreach($category->products as $product)
                            <div x-show="selectedCategory === null || selectedCategory === {{ $category->id }}"
                                 @click="addToCart({{ json_encode([
                                     'id' => $product->id,
                                     'name' => $product->name,
                                     'sku' => $product->sku,
                                     'price' => $product->price,
                                     'image' => $product->image_urls[0] ?? null,
                                     'stock_quantity' => $product->stock_quantity,
                                     'track_inventory' => $product->track_inventory
                                 ]) }})"
                                 class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition cursor-pointer overflow-hidden">

                                @if($product->image_urls && count($product->image_urls) > 0)
                                    <img src="{{ asset($product->image_urls[0]) }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-32 object-cover">
                                @else
                                    <div class="w-full h-32 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <i class="fas fa-image text-4xl text-gray-400"></i>
                                    </div>
                                @endif

                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-1 truncate" :title="'{{ $product->name }}'">{{ $product->name }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $product->sku }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-green-600 dark:text-green-400 font-bold">฿{{ number_format($product->price, 2) }}</span>
                                        @if($product->track_inventory)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                <i class="fas fa-box"></i> {{ $product->stock_quantity }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <!-- Cart Section (Right) -->
            <div class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
                <!-- Cart Header -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold flex items-center justify-between">
                        <span><i class="fas fa-shopping-cart text-green-600"></i> รายการสินค้า</span>
                        <button @click="clearCart()"
                                x-show="cart.length > 0"
                                class="text-sm text-red-600 hover:text-red-700">
                            <i class="fas fa-trash"></i> ล้าง
                        </button>
                    </h2>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2">
                    <template x-if="cart.length === 0">
                        <div class="text-center py-12 text-gray-400">
                            <i class="fas fa-shopping-cart text-5xl mb-3"></i>
                            <p>ไม่มีสินค้าในตะกร้า</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="cart-item bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm" x-text="item.name"></h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="item.sku"></p>
                                </div>
                                <button @click="removeFromCart(index)" class="text-red-500 hover:text-red-600 ml-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button @click="updateQuantity(index, -1)"
                                            class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number"
                                           x-model.number="item.quantity"
                                           @change="calculateTotals()"
                                           min="1"
                                           class="w-16 text-center px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800">
                                    <button @click="updateQuantity(index, 1)"
                                            class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500" x-text="'฿' + parseFloat(item.price).toFixed(2)"></div>
                                    <div class="font-bold text-green-600 dark:text-green-400" x-text="'฿' + (item.price * item.quantity).toFixed(2)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Summary -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3">
                    <!-- Discount -->
                    <div class="flex items-center justify-between text-sm">
                        <span>ส่วนลด (%)</span>
                        <input type="number"
                               x-model.number="discountPercentage"
                               @input="calculateTotals()"
                               min="0"
                               max="100"
                               step="0.01"
                               class="w-24 px-2 py-1 text-right border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800">
                    </div>

                    <!-- Totals -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>ยอดรวม</span>
                            <span x-text="'฿' + subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-red-600" x-show="discount > 0">
                            <span>ส่วนลด</span>
                            <span x-text="'-฿' + discount.toFixed(2)"></span>
                        </div>
                        @if($settings && $settings->tax_enabled)
                        <div class="flex justify-between">
                            <span>ภาษี ({{ $settings->tax_percentage }}%)</span>
                            <span x-text="'฿' + tax.toFixed(2)"></span>
                        </div>
                        @endif
                        @if($settings && $settings->service_charge_enabled)
                        <div class="flex justify-between">
                            <span>ค่าบริการ ({{ $settings->service_charge_percentage }}%)</span>
                            <span x-text="'฿' + serviceCharge.toFixed(2)"></span>
                        </div>
                        @endif
                        <div class="flex justify-between text-lg font-bold text-green-600 dark:text-green-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span>รวมทั้งสิ้น</span>
                            <span x-text="'฿' + total.toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium mb-2">วิธีการชำระเงิน</label>
                        <select x-model="paymentMethod" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                            <option value="cash">เงินสด</option>
                            <option value="card">บัตรเครดิต/เดบิต</option>
                            <option value="qr">QR Code</option>
                            <option value="e-wallet">E-Wallet</option>
                        </select>
                    </div>

                    <!-- Amount Paid (for cash) -->
                    <div x-show="paymentMethod === 'cash'">
                        <label class="block text-sm font-medium mb-2">รับเงิน</label>
                        <input type="number"
                               x-model.number="amountPaid"
                               @input="calculateChange()"
                               step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                        <div class="mt-2 text-sm" x-show="amountPaid >= total">
                            <span>เงินทอน: </span>
                            <span class="font-bold text-green-600" x-text="'฿' + (amountPaid - total).toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <button @click="checkout()"
                            :disabled="cart.length === 0 || (paymentMethod === 'cash' && amountPaid < total)"
                            :class="cart.length === 0 || (paymentMethod === 'cash' && amountPaid < total) ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                            class="w-full py-3 text-white rounded-lg font-bold text-lg transition">
                        <i class="fas fa-check-circle"></i> ชำระเงิน
                    </button>
                </div>
            </div>
        </div>

        <!-- Fullscreen Exit Button (Bottom Corner) -->
        <button @click="toggleFullscreen()"
                :class="{'active': isFullscreen}"
                class="fullscreen-exit-btn no-print"
                title="ออกจากโหมดเต็มหน้าจอ">
            <i class="fas fa-compress text-xl"></i>
        </button>
    </div>

    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'dark');
            }
        }

        function posSystem() {
            return {
                cart: [],
                searchQuery: '',
                selectedCategory: null,
                paymentMethod: 'cash',
                discountPercentage: 0,
                amountPaid: 0,
                isFullscreen: false,

                // Calculated values
                subtotal: 0,
                discount: 0,
                tax: 0,
                serviceCharge: 0,
                total: 0,

                // Settings
                taxEnabled: {{ $settings && $settings->tax_enabled ? 'true' : 'false' }},
                taxPercentage: {{ $settings ? $settings->tax_percentage : 0 }},
                taxInclusive: {{ $settings && $settings->tax_inclusive ? 'true' : 'false' }},
                serviceChargeEnabled: {{ $settings && $settings->service_charge_enabled ? 'true' : 'false' }},
                serviceChargePercentage: {{ $settings ? ($settings->service_charge_percentage ?? 0) : 0 }},

                addToCart(product) {
                    const existingIndex = this.cart.findIndex(item => item.id === product.id);

                    if (existingIndex > -1) {
                        this.cart[existingIndex].quantity++;
                    } else {
                        this.cart.push({
                            ...product,
                            quantity: 1
                        });
                    }

                    this.calculateTotals();
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.calculateTotals();
                },

                updateQuantity(index, change) {
                    const newQuantity = this.cart[index].quantity + change;
                    if (newQuantity > 0) {
                        this.cart[index].quantity = newQuantity;
                        this.calculateTotals();
                    }
                },

                clearCart() {
                    if (confirm('คุณต้องการล้างรายการทั้งหมดหรือไม่?')) {
                        this.cart = [];
                        this.calculateTotals();
                    }
                },

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

                    if (this.paymentMethod !== 'cash') {
                        this.amountPaid = this.total;
                    }
                },

                calculateChange() {
                    // Change is calculated in template
                },

                toggleFullscreen() {
                    const elem = document.documentElement;

                    if (!document.fullscreenElement) {
                        // Enter fullscreen
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
                        // Exit fullscreen
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

                async searchProducts() {
                    // Implement product search via AJAX if needed
                },

                async checkout() {
                    if (this.cart.length === 0) {
                        alert('กรุณาเพิ่มสินค้าในตะกร้า');
                        return;
                    }

                    if (this.paymentMethod === 'cash' && this.amountPaid < this.total) {
                        alert('จำนวนเงินที่รับไม่เพียงพอ');
                        return;
                    }

                    const data = {
                        device_id: {{ $device->id }},
                        session_id: {{ $activeSession ? $activeSession->id : 'null' }},
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
                        const response = await fetch('{{ route('pos.checkout') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('ทำรายการสำเร็จ!');

                            // Open receipt in new window
                            window.open(`/pos/receipt/${result.transaction.id}`, '_blank');

                            // Clear cart
                            this.cart = [];
                            this.discountPercentage = 0;
                            this.amountPaid = 0;
                            this.calculateTotals();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Checkout error:', error);
                        alert('เกิดข้อผิดพลาดในการทำรายการ');
                    }
                },

                init() {
                    this.calculateTotals();

                    // Listen for fullscreen changes
                    const fullscreenChangeHandler = () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    };

                    document.addEventListener('fullscreenchange', fullscreenChangeHandler);
                    document.addEventListener('webkitfullscreenchange', fullscreenChangeHandler);
                    document.addEventListener('mozfullscreenchange', fullscreenChangeHandler);
                    document.addEventListener('msfullscreenchange', fullscreenChangeHandler);
                }
            }
        }
    </script>
</body>
</html>

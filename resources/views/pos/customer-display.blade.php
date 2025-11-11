<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Display - {{ $device->device_name }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }

        .welcome-animation {
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .slide-up {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .item-enter {
            animation: itemEnter 0.3s ease-out;
        }

        @keyframes itemEnter {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="text-white">
    <div class="h-screen flex flex-col" x-data="customerDisplay()">
        <!-- Header -->
        <div class="bg-white/10 backdrop-blur-md border-b border-white/20 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($device->store->logo ?? false)
                        <img src="{{ asset($device->store->logo) }}" alt="Logo" class="h-16 w-auto">
                    @else
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-store text-3xl"></i>
                        </div>
                    @endif
                    <div>
                        <h1 class="text-3xl font-bold">{{ $device->store->name ?? 'ร้านค้า' }}</h1>
                        <p class="text-white/80">{{ $device->device_name }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-5xl font-bold" x-text="currentTime"></div>
                    <div class="text-lg text-white/80" x-text="currentDate"></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex items-center justify-center p-8">
            <!-- Welcome Screen -->
            <div x-show="!hasItems" class="text-center welcome-animation">
                <div class="mb-8">
                    <div class="w-32 h-32 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6 pulse-slow">
                        <i class="fas fa-shopping-cart text-6xl"></i>
                    </div>
                    <h2 class="text-5xl font-bold mb-4">ยินดีต้อนรับ</h2>
                    <p class="text-2xl text-white/80">กรุณารอสักครู่ พนักงานกำลังให้บริการ</p>
                </div>

                @if($settings && $settings->customer_display_message)
                <div class="mt-12 p-6 bg-white/10 backdrop-blur-sm rounded-2xl max-w-2xl mx-auto">
                    <p class="text-xl">{{ $settings->customer_display_message }}</p>
                </div>
                @endif
            </div>

            <!-- Cart Display -->
            <div x-show="hasItems" class="w-full max-w-4xl slide-up">
                <!-- Items List -->
                <div class="bg-white/10 backdrop-blur-md rounded-3xl p-8 mb-6 max-h-[50vh] overflow-y-auto">
                    <h3 class="text-2xl font-bold mb-6">
                        <i class="fas fa-list"></i>
                        รายการสินค้า
                    </h3>
                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="bg-white/10 rounded-2xl p-6 flex items-center justify-between item-enter">
                                <div class="flex items-center gap-6 flex-1">
                                    <div class="text-3xl font-bold text-white/60" x-text="item.quantity + 'x'"></div>
                                    <div class="flex-1">
                                        <h4 class="text-2xl font-bold mb-1" x-text="item.name"></h4>
                                        <p class="text-lg text-white/70" x-text="'฿' + parseFloat(item.price).toFixed(2) + ' / ชิ้น'"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold" x-text="'฿' + (item.quantity * item.price).toFixed(2)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Total Display -->
                <div class="bg-white/20 backdrop-blur-md rounded-3xl p-8">
                    <div class="space-y-4">
                        <!-- Subtotal -->
                        <div class="flex items-center justify-between text-2xl">
                            <span>ยอดรวม</span>
                            <span class="font-bold" x-text="'฿' + subtotal.toFixed(2)"></span>
                        </div>

                        <!-- Discount -->
                        <div x-show="discount > 0" class="flex items-center justify-between text-2xl text-yellow-300">
                            <span>ส่วนลด</span>
                            <span class="font-bold" x-text="'-฿' + discount.toFixed(2)"></span>
                        </div>

                        <!-- Tax -->
                        @if($settings && $settings->tax_enabled)
                        <div class="flex items-center justify-between text-xl text-white/80">
                            <span>ภาษี ({{ $settings->tax_percentage }}%)</span>
                            <span x-text="'฿' + tax.toFixed(2)"></span>
                        </div>
                        @endif

                        <!-- Service Charge -->
                        @if($settings && $settings->service_charge_enabled)
                        <div class="flex items-center justify-between text-xl text-white/80">
                            <span>ค่าบริการ ({{ $settings->service_charge_percentage }}%)</span>
                            <span x-text="'฿' + serviceCharge.toFixed(2)"></span>
                        </div>
                        @endif

                        <!-- Total -->
                        <div class="pt-6 mt-6 border-t-2 border-white/30">
                            <div class="flex items-center justify-between">
                                <span class="text-3xl font-bold">รวมทั้งสิ้น</span>
                                <span class="text-5xl font-bold text-yellow-300" x-text="'฿' + total.toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-white/10 backdrop-blur-md border-t border-white/20 px-8 py-4">
            <div class="flex items-center justify-between text-sm text-white/70">
                <div>
                    <i class="fas fa-phone"></i>
                    {{ $device->store->phone ?? 'โทร: -' }}
                </div>
                <div>
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $device->store->address ?? 'ที่อยู่: -' }}
                </div>
                <div>
                    <i class="fas fa-heart"></i>
                    ขอบคุณที่ใช้บริการ
                </div>
            </div>
        </div>
    </div>

    <script>
        function customerDisplay() {
            return {
                items: [],
                subtotal: 0,
                discount: 0,
                tax: 0,
                serviceCharge: 0,
                total: 0,
                hasItems: false,

                currentTime: '',
                currentDate: '',

                // Settings
                taxEnabled: {{ $settings && $settings->tax_enabled ? 'true' : 'false' }},
                taxPercentage: {{ $settings ? $settings->tax_percentage : 0 }},
                taxInclusive: {{ $settings && $settings->tax_inclusive ? 'true' : 'false' }},
                serviceChargeEnabled: {{ $settings && $settings->service_charge_enabled ? 'true' : 'false' }},
                serviceChargePercentage: {{ $settings ? ($settings->service_charge_percentage ?? 0) : 0 }},

                updateClock() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.currentDate = now.toLocaleDateString('th-TH', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                updateDisplay(data) {
                    this.items = data.items || [];
                    this.hasItems = this.items.length > 0;

                    if (this.hasItems) {
                        this.calculateTotals(data);
                    }
                },

                calculateTotals(data) {
                    this.subtotal = data.subtotal || 0;
                    this.discount = data.discount || 0;
                    this.tax = data.tax || 0;
                    this.serviceCharge = data.serviceCharge || 0;
                    this.total = data.total || 0;
                },

                clearDisplay() {
                    this.items = [];
                    this.subtotal = 0;
                    this.discount = 0;
                    this.tax = 0;
                    this.serviceCharge = 0;
                    this.total = 0;
                    this.hasItems = false;
                },

                init() {
                    // Update clock every second
                    this.updateClock();
                    setInterval(() => {
                        this.updateClock();
                    }, 1000);

                    // Listen for updates from main POS (via localStorage or WebSocket)
                    window.addEventListener('storage', (e) => {
                        if (e.key === 'pos_customer_display') {
                            const data = JSON.parse(e.newValue);
                            if (data.action === 'update') {
                                this.updateDisplay(data);
                            } else if (data.action === 'clear') {
                                this.clearDisplay();
                            }
                        }
                    });

                    // Demo data for testing (remove in production)
                    // setTimeout(() => {
                    //     this.updateDisplay({
                    //         items: [
                    //             { name: 'สินค้าทดสอบ 1', quantity: 2, price: 150.00 },
                    //             { name: 'สินค้าทดสอบ 2', quantity: 1, price: 250.00 }
                    //         ],
                    //         subtotal: 550.00,
                    //         discount: 55.00,
                    //         tax: 34.65,
                    //         serviceCharge: 0,
                    //         total: 529.65
                    //     });
                    // }, 3000);
                }
            }
        }
    </script>
</body>
</html>

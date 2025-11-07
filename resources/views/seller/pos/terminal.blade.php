@extends('layouts.seller')

@section('title', 'POS ขายสินค้า')

@section('content')
<div x-data="posTerminal()" x-init="init()" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-[calc(100vh-120px)]">
        <!-- Left Side: Products -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 overflow-hidden flex flex-col">
            <!-- Search and Categories -->
            <div class="mb-4">
                <div class="relative mb-4">
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterProducts()"
                           placeholder="ค้นหาสินค้า (ชื่อ หรือ SKU)..."
                           class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Categories -->
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <button @click="selectedCategory = null; filterProducts()"
                            :class="selectedCategory === null ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg whitespace-nowrap transition-colors">
                        ทั้งหมด
                    </button>
                    @foreach($categories as $category)
                    <button @click="selectedCategory = {{ $category->id }}; filterProducts()"
                            :class="selectedCategory === {{ $category->id }} ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg whitespace-nowrap transition-colors">
                        {{ $category->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Products Grid -->
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)"
                             class="bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-lg p-3 cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-lg transition-all">
                            <div class="aspect-square bg-gray-100 dark:bg-gray-600 rounded-lg mb-2 overflow-hidden">
                                <img :src="product.main_image_url || '/images/no-image.png'"
                                     :alt="product.name"
                                     class="w-full h-full object-cover">
                            </div>
                            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-1 line-clamp-2" x-text="product.name"></h3>
                            <div class="flex justify-between items-center">
                                <span class="text-blue-600 dark:text-blue-400 font-bold" x-text="formatCurrency(product.price)"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'คงเหลือ: ' + product.stock_quantity"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Side: Cart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex flex-col">
            <!-- Cart Header -->
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">รายการสินค้า</h2>
                <button @click="clearCart()"
                        x-show="cart.length > 0"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm">
                    ล้างทั้งหมด
                </button>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto mb-4">
                <template x-if="cart.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500">
                        <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p>ไม่มีสินค้าในตะกร้า</p>
                    </div>
                </template>

                <div class="space-y-2">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="item.name"></h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatCurrency(item.price) + ' x ' + item.quantity"></p>
                                </div>
                                <button @click="removeFromCart(index)" class="text-red-600 hover:text-red-800 dark:text-red-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="decreaseQuantity(index)"
                                        class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 w-8 h-8 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <input type="number"
                                       x-model.number="item.quantity"
                                       @input="updateQuantity(index)"
                                       min="1"
                                       class="w-16 text-center px-2 py-1 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                <button @click="increaseQuantity(index)"
                                        class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 w-8 h-8 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                                <span class="ml-auto font-bold text-gray-900 dark:text-white" x-text="formatCurrency(item.total)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Summary -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>ยอดรวม:</span>
                    <span x-text="formatCurrency(subtotal)"></span>
                </div>
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>ส่วนลด:</span>
                    <input type="number"
                           x-model.number="discount"
                           @input="calculateTotals()"
                           min="0"
                           step="0.01"
                           class="w-24 text-right px-2 py-1 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                    <span>VAT ({{ $settings->tax_percentage ?? 7 }}%):</span>
                    <span x-text="formatCurrency(tax)"></span>
                </div>
                <div class="flex justify-between text-xl font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-gray-700">
                    <span>รวมทั้งสิ้น:</span>
                    <span x-text="formatCurrency(total)"></span>
                </div>
            </div>

            <!-- Payment Button -->
            <button @click="openPaymentModal()"
                    :disabled="cart.length === 0"
                    :class="cart.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                    class="w-full text-white py-4 rounded-lg font-bold text-lg mt-4 transition-colors">
                ชำระเงิน
            </button>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="closePaymentModal()">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">ชำระเงิน</h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดรวมที่ต้องชำระ</label>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="formatCurrency(total)"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วิธีชำระเงิน</label>
                <select x-model="paymentMethod" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="cash">เงินสด</option>
                    <option value="card">บัตรเครดิต/เดบิต</option>
                    <option value="qr">QR Code</option>
                    <option value="bank_transfer">โอนเงิน</option>
                    <option value="other">อื่นๆ</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินที่รับ</label>
                <input type="number"
                       x-model.number="paymentAmount"
                       @input="calculateChange()"
                       step="0.01"
                       class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="mb-4" x-show="paymentMethod === 'cash' && change > 0">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เงินทอน</label>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="formatCurrency(change)"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อลูกค้า (ไม่บังคับ)</label>
                <input type="text"
                       x-model="customerName"
                       class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เบอร์โทร (ไม่บังคับ)</label>
                <input type="text"
                       x-model="customerPhone"
                       class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="flex gap-3">
                <button @click="closePaymentModal()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg font-bold">
                    ยกเลิก
                </button>
                <button @click="processPayment()"
                        :disabled="paymentAmount < total"
                        :class="paymentAmount < total ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                        class="flex-1 text-white py-3 rounded-lg font-bold">
                    ยืนยัน
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function posTerminal() {
    return {
        products: @json($products),
        cart: [],
        searchQuery: '',
        selectedCategory: null,
        filteredProducts: [],
        subtotal: 0,
        discount: 0,
        tax: 0,
        total: 0,
        showPaymentModal: false,
        paymentMethod: 'cash',
        paymentAmount: 0,
        change: 0,
        customerName: '',
        customerPhone: '',
        deviceId: {{ $device->id }},
        sessionId: {{ $session->id }},
        taxRate: {{ $settings->tax_percentage ?? 7 }},

        init() {
            this.filteredProducts = this.products;
        },

        filterProducts() {
            this.filteredProducts = this.products.filter(product => {
                const matchesSearch = !this.searchQuery ||
                    product.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    (product.sku && product.sku.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchesCategory = this.selectedCategory === null ||
                    product.category_id === this.selectedCategory;

                return matchesSearch && matchesCategory;
            });
        },

        addToCart(product) {
            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                existingItem.quantity++;
                existingItem.total = existingItem.quantity * existingItem.price;
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    total: parseFloat(product.price),
                    discount: 0
                });
            }

            this.calculateTotals();
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.calculateTotals();
        },

        increaseQuantity(index) {
            this.cart[index].quantity++;
            this.cart[index].total = this.cart[index].quantity * this.cart[index].price - this.cart[index].discount;
            this.calculateTotals();
        },

        decreaseQuantity(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
                this.cart[index].total = this.cart[index].quantity * this.cart[index].price - this.cart[index].discount;
                this.calculateTotals();
            }
        },

        updateQuantity(index) {
            if (this.cart[index].quantity < 1) {
                this.cart[index].quantity = 1;
            }
            this.cart[index].total = this.cart[index].quantity * this.cart[index].price - this.cart[index].discount;
            this.calculateTotals();
        },

        calculateTotals() {
            this.subtotal = this.cart.reduce((sum, item) => sum + item.total, 0);
            this.tax = (this.subtotal - this.discount) * (this.taxRate / 100);
            this.total = this.subtotal - this.discount + this.tax;
        },

        clearCart() {
            if (confirm('ต้องการล้างรายการทั้งหมดใช่หรือไม่?')) {
                this.cart = [];
                this.discount = 0;
                this.calculateTotals();
            }
        },

        openPaymentModal() {
            this.showPaymentModal = true;
            this.paymentAmount = this.total;
            this.calculateChange();
        },

        closePaymentModal() {
            this.showPaymentModal = false;
            this.paymentAmount = 0;
            this.change = 0;
        },

        calculateChange() {
            this.change = Math.max(0, this.paymentAmount - this.total);
        },

        async processPayment() {
            const items = this.cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                unit_price: item.price,
                discount: item.discount
            }));

            const data = {
                device_id: this.deviceId,
                session_id: this.sessionId,
                items: items,
                subtotal: this.subtotal,
                discount_amount: this.discount,
                tax_amount: this.tax,
                total_amount: this.total,
                payment_method: this.paymentMethod,
                payment_amount: this.paymentAmount,
                customer_name: this.customerName,
                customer_phone: this.customerPhone,
            };

            try {
                const response = await fetch('{{ route("seller.pos.create-transaction") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Open receipt in new window
                    window.open(result.receipt_url, '_blank');

                    // Reset cart
                    this.cart = [];
                    this.discount = 0;
                    this.customerName = '';
                    this.customerPhone = '';
                    this.calculateTotals();
                    this.closePaymentModal();

                    alert('บันทึกการขายสำเร็จ!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message);
            }
        },

        formatCurrency(amount) {
            return '฿' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection

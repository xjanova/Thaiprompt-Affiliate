/**
 * TP-POS Main Application
 *
 * แอพพลิเคชัน POS หลักสำหรับ PWA
 * รวม Database, Sync, และ UI Components
 *
 * @version 1.0.0
 */

import db from './database.js';
import sync from './sync.js';

/**
 * สถานะ App
 */
const appState = {
    initialized: false,
    deviceId: null,
    sessionId: null,
    storeId: null,
    settings: {},
    cart: [],
    customer: null,
    isOnline: navigator.onLine
};

/**
 * Alpine.js Data สำหรับ POS
 */
export function posApp() {
    return {
        // สถานะ
        loading: true,
        isOnline: navigator.onLine,
        isSyncing: false,
        pendingSyncCount: 0,

        // ข้อมูลหลัก
        products: [],
        categories: [],
        cart: [],
        customer: null,

        // การค้นหา
        searchQuery: '',
        selectedCategory: null,
        searchResults: [],

        // หน้าจอ
        currentView: 'products', // products, cart, payment, customer, reports, settings
        showPaymentModal: false,
        showCustomerModal: false,
        showSettingsModal: false,
        showNumpad: false,
        numpadTarget: null,
        numpadValue: '',

        // Payment
        paymentMethod: 'cash',
        cashReceived: 0,
        paymentNote: '',

        // Settings
        settings: {
            taxEnabled: true,
            taxPercentage: 7,
            taxInclusive: true,
            serviceChargeEnabled: false,
            serviceChargePercentage: 10,
            currency: '฿',
            decimalPlaces: 2
        },

        // Session
        session: null,
        deviceInfo: null,

        // ============================================
        // INITIALIZATION
        // ============================================

        async init() {
            console.log('[POS App] Initializing...');

            try {
                // เปิด Database
                await db.initDatabase();

                // โหลดข้อมูลจาก IndexedDB
                await this.loadLocalData();

                // ตั้งค่า event listeners
                this.setupEventListeners();

                // ลงทะเบียน Service Worker
                await this.registerServiceWorker();

                // เริ่ม Sync
                await sync.initSync({
                    deviceId: this.deviceInfo?.id,
                    autoSync: true,
                    syncInterval: 5
                });

                // ติดตาม sync events
                sync.addSyncListener((event, data) => {
                    this.handleSyncEvent(event, data);
                });

                // Initial sync ถ้า online
                if (this.isOnline) {
                    this.syncData();
                }

                this.loading = false;
                console.log('[POS App] Initialized successfully');

            } catch (error) {
                console.error('[POS App] Initialization failed:', error);
                this.loading = false;
            }
        },

        async loadLocalData() {
            // โหลดสินค้า
            this.products = await db.getAll('products');

            // โหลดหมวดหมู่
            this.categories = await db.getAll('categories');

            // โหลด settings
            const settings = await db.getAllSettings();
            this.settings = { ...this.settings, ...settings };

            // โหลด session ปัจจุบัน
            const sessions = await db.getByIndex('sessions', 'status', 'open');
            if (sessions.length > 0) {
                this.session = sessions[0];
            }

            // โหลด cart items
            if (this.session) {
                this.cart = await db.getCartItems(this.session.id);
            }

            // โหลด pending sync count
            this.pendingSyncCount = await sync.getPendingCount();

            console.log(`[POS App] Loaded: ${this.products.length} products, ${this.categories.length} categories`);
        },

        setupEventListeners() {
            // Network status
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.showToast('กลับมาออนไลน์แล้ว', 'success');
                this.syncData();
            });

            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.showToast('ออฟไลน์ - ข้อมูลจะถูกบันทึกไว้', 'warning');
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                this.handleKeyboard(e);
            });

            // Prevent pull-to-refresh on mobile
            document.body.addEventListener('touchmove', (e) => {
                if (e.touches.length > 1) {
                    e.preventDefault();
                }
            }, { passive: false });

            // Handle visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && this.isOnline) {
                    this.syncData();
                }
            });
        },

        async registerServiceWorker() {
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.register('/pos-sw.js', {
                        scope: '/pos/'
                    });

                    console.log('[POS App] Service Worker registered:', registration.scope);

                    // รับ messages จาก SW
                    navigator.serviceWorker.addEventListener('message', (event) => {
                        this.handleServiceWorkerMessage(event.data);
                    });

                } catch (error) {
                    console.error('[POS App] Service Worker registration failed:', error);
                }
            }
        },

        handleServiceWorkerMessage(message) {
            switch (message.type) {
                case 'SYNC_SUCCESS':
                    this.pendingSyncCount = Math.max(0, this.pendingSyncCount - 1);
                    break;

                case 'PRODUCTS_UPDATED':
                    this.loadLocalData();
                    break;

                case 'CACHE_STATUS':
                    console.log('Cache status:', message.data);
                    break;
            }
        },

        handleSyncEvent(event, data) {
            switch (event) {
                case 'online':
                    this.isOnline = true;
                    break;

                case 'offline':
                    this.isOnline = false;
                    break;

                case 'sync_start':
                    this.isSyncing = true;
                    break;

                case 'sync_complete':
                    this.isSyncing = false;
                    this.loadLocalData();
                    break;

                case 'sync_error':
                    this.isSyncing = false;
                    console.error('Sync error:', data.error);
                    break;

                case 'products_synced':
                    console.log(`Synced ${data.count} products`);
                    break;

                case 'transaction_synced':
                    this.pendingSyncCount = Math.max(0, this.pendingSyncCount - 1);
                    break;
            }
        },

        handleKeyboard(e) {
            // F1 - ค้นหา
            if (e.key === 'F1') {
                e.preventDefault();
                document.querySelector('#search-input')?.focus();
            }

            // F2 - ชำระเงิน
            if (e.key === 'F2') {
                e.preventDefault();
                if (this.cart.length > 0) {
                    this.openPaymentModal();
                }
            }

            // F3 - ลูกค้า
            if (e.key === 'F3') {
                e.preventDefault();
                this.showCustomerModal = true;
            }

            // Escape - ปิด modal
            if (e.key === 'Escape') {
                this.closeAllModals();
            }

            // Delete - ลบรายการที่เลือก
            if (e.key === 'Delete' && this.selectedCartItem) {
                this.removeFromCart(this.selectedCartItem.id);
            }
        },

        // ============================================
        // SYNC
        // ============================================

        async syncData() {
            if (!this.isOnline || this.isSyncing) return;

            try {
                this.isSyncing = true;
                await sync.syncAll();
                this.pendingSyncCount = await sync.getPendingCount();
            } catch (error) {
                console.error('[POS App] Sync failed:', error);
            } finally {
                this.isSyncing = false;
            }
        },

        async forceSyncProducts() {
            try {
                this.isSyncing = true;
                await sync.syncProducts(true);
                this.products = await db.getAll('products');
                this.showToast('อัพเดทสินค้าสำเร็จ', 'success');
            } catch (error) {
                this.showToast('ไม่สามารถอัพเดทสินค้าได้', 'error');
            } finally {
                this.isSyncing = false;
            }
        },

        // ============================================
        // PRODUCTS & SEARCH
        // ============================================

        get filteredProducts() {
            let products = this.products;

            // กรองตาม category
            if (this.selectedCategory) {
                products = products.filter(p => p.category_id === this.selectedCategory);
            }

            // กรองตาม search query
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                products = products.filter(p =>
                    p.name?.toLowerCase().includes(query) ||
                    p.sku?.toLowerCase().includes(query) ||
                    p.barcode?.includes(query)
                );
            }

            return products.slice(0, 50);
        },

        async searchProducts() {
            if (!this.searchQuery) {
                this.searchResults = [];
                return;
            }

            // ค้นหาจาก IndexedDB ก่อน (offline-first)
            this.searchResults = await db.searchProducts(this.searchQuery, {
                categoryId: this.selectedCategory,
                limit: 20
            });
        },

        async getProductByBarcode(barcode) {
            const product = await db.getProductByBarcode(barcode);

            if (product) {
                this.addToCart(product);
                return product;
            }

            // ถ้าไม่เจอใน local - ลองหาจาก server
            if (this.isOnline) {
                try {
                    const response = await fetch(`/pos/api/products/search?q=${barcode}`);
                    const data = await response.json();

                    if (data.success && data.data.length > 0) {
                        const product = data.data[0];
                        // บันทึกลง local
                        await db.put('products', product);
                        this.addToCart(product);
                        return product;
                    }
                } catch (error) {
                    console.error('Failed to search product:', error);
                }
            }

            this.showToast('ไม่พบสินค้า', 'warning');
            return null;
        },

        selectCategory(categoryId) {
            this.selectedCategory = categoryId === this.selectedCategory ? null : categoryId;
        },

        // ============================================
        // CART
        // ============================================

        async addToCart(product, quantity = 1) {
            // เช็คสต็อก
            if (product.track_inventory && product.stock_quantity < quantity) {
                this.showToast('สินค้าหมด', 'warning');
                return;
            }

            // หา item ที่มีอยู่แล้ว
            const existingIndex = this.cart.findIndex(item => item.product_id === product.id);

            if (existingIndex >= 0) {
                // อัพเดทจำนวน
                const newQty = this.cart[existingIndex].quantity + quantity;

                if (product.track_inventory && newQty > product.stock_quantity) {
                    this.showToast('สินค้าไม่เพียงพอ', 'warning');
                    return;
                }

                this.cart[existingIndex].quantity = newQty;
                this.cart[existingIndex].subtotal = this.cart[existingIndex].unit_price * newQty;

                if (this.session) {
                    await db.updateCartItemQuantity(this.cart[existingIndex].id, newQty);
                }
            } else {
                // เพิ่มใหม่
                const cartItem = {
                    product_id: product.id,
                    product_name: product.name,
                    product_sku: product.sku,
                    product_image: product.image_url || product.image,
                    unit_price: product.price,
                    quantity: quantity,
                    discount: 0,
                    discount_type: 'fixed',
                    tax_rate: product.vat_percentage || 0,
                    subtotal: product.price * quantity
                };

                if (this.session) {
                    const id = await db.addToCart(this.session.id, product, quantity);
                    cartItem.id = id;
                }

                this.cart.push(cartItem);
            }

            // Haptic feedback
            this.vibrate(50);

            // แจ้ง customer display
            this.updateCustomerDisplay();
        },

        async updateCartItemQty(item, delta) {
            const newQty = item.quantity + delta;

            if (newQty <= 0) {
                this.removeFromCart(item.id);
                return;
            }

            // หา product เพื่อเช็คสต็อก
            const product = this.products.find(p => p.id === item.product_id);
            if (product?.track_inventory && newQty > product.stock_quantity) {
                this.showToast('สินค้าไม่เพียงพอ', 'warning');
                return;
            }

            item.quantity = newQty;
            item.subtotal = item.unit_price * newQty;

            if (item.id) {
                await db.updateCartItemQuantity(item.id, newQty);
            }

            this.updateCustomerDisplay();
        },

        async setCartItemQty(item, quantity) {
            if (quantity <= 0) {
                this.removeFromCart(item.id);
                return;
            }

            item.quantity = quantity;
            item.subtotal = item.unit_price * quantity;

            if (item.id) {
                await db.updateCartItemQuantity(item.id, quantity);
            }

            this.updateCustomerDisplay();
        },

        async removeFromCart(itemId) {
            const index = this.cart.findIndex(item => item.id === itemId);

            if (index >= 0) {
                this.cart.splice(index, 1);

                if (itemId) {
                    await db.removeFromCart(itemId);
                }

                this.vibrate(30);
                this.updateCustomerDisplay();
            }
        },

        async clearCart() {
            if (this.cart.length === 0) return;

            if (confirm('ต้องการล้างตะกร้าทั้งหมด?')) {
                if (this.session) {
                    await db.clearCart(this.session.id);
                }

                this.cart = [];
                this.customer = null;
                this.updateCustomerDisplay();
            }
        },

        // ============================================
        // CART CALCULATIONS
        // ============================================

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
        },

        get cartDiscount() {
            return this.cart.reduce((sum, item) => {
                const itemTotal = item.unit_price * item.quantity;
                if (item.discount_type === 'percent') {
                    return sum + (itemTotal * item.discount / 100);
                }
                return sum + (item.discount * item.quantity);
            }, 0);
        },

        get cartTax() {
            if (!this.settings.taxEnabled) return 0;

            const taxableAmount = this.cartSubtotal - this.cartDiscount;

            if (this.settings.taxInclusive) {
                // ภาษีรวมในราคาแล้ว
                return taxableAmount * this.settings.taxPercentage / (100 + this.settings.taxPercentage);
            } else {
                // ภาษีแยก
                return taxableAmount * this.settings.taxPercentage / 100;
            }
        },

        get cartServiceCharge() {
            if (!this.settings.serviceChargeEnabled) return 0;
            return (this.cartSubtotal - this.cartDiscount) * this.settings.serviceChargePercentage / 100;
        },

        get cartTotal() {
            let total = this.cartSubtotal - this.cartDiscount + this.cartServiceCharge;

            if (!this.settings.taxInclusive) {
                total += this.cartTax;
            }

            return total;
        },

        get cartItemCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },

        get changeAmount() {
            return Math.max(0, this.cashReceived - this.cartTotal);
        },

        // ============================================
        // PAYMENT
        // ============================================

        openPaymentModal() {
            if (this.cart.length === 0) {
                this.showToast('ไม่มีสินค้าในตะกร้า', 'warning');
                return;
            }

            this.showPaymentModal = true;
            this.cashReceived = Math.ceil(this.cartTotal);
            this.paymentMethod = 'cash';
            this.paymentNote = '';
        },

        selectPaymentMethod(method) {
            this.paymentMethod = method;

            if (method !== 'cash') {
                this.cashReceived = this.cartTotal;
            }
        },

        setCashReceived(amount) {
            this.cashReceived = amount;
        },

        addCashAmount(amount) {
            this.cashReceived += amount;
        },

        async processPayment() {
            if (this.cart.length === 0) return;

            // ตรวจสอบเงินสด
            if (this.paymentMethod === 'cash' && this.cashReceived < this.cartTotal) {
                this.showToast('เงินไม่เพียงพอ', 'warning');
                return;
            }

            try {
                // สร้าง transaction
                const transactionData = {
                    sessionId: this.session?.id,
                    deviceId: this.deviceInfo?.id,
                    customerId: this.customer?.id,
                    customerName: this.customer?.name,
                    customerPhone: this.customer?.phone,
                    subtotal: this.cartSubtotal,
                    discount: this.cartDiscount,
                    tax: this.cartTax,
                    serviceCharge: this.cartServiceCharge,
                    total: this.cartTotal,
                    paymentMethod: this.paymentMethod,
                    cashReceived: this.cashReceived,
                    change: this.changeAmount,
                    items: this.cart.map(item => ({
                        product_id: item.product_id,
                        product_name: item.product_name,
                        product_sku: item.product_sku,
                        unit_price: item.unit_price,
                        quantity: item.quantity,
                        discount: item.discount,
                        tax: item.unit_price * item.quantity * (item.tax_rate || 0) / 100,
                        subtotal: item.unit_price * item.quantity
                    }))
                };

                // บันทึก transaction
                const result = await db.createTransaction(transactionData);

                // อัพเดท pending count
                this.pendingSyncCount++;

                // แสดงใบเสร็จ
                this.showReceipt(result.transaction_number, transactionData);

                // ล้างตะกร้า
                if (this.session) {
                    await db.clearCart(this.session.id);
                }
                this.cart = [];
                this.customer = null;
                this.showPaymentModal = false;

                // Sync ถ้า online
                if (this.isOnline) {
                    sync.processSyncQueue();
                }

                // Haptic feedback
                this.vibrate([100, 50, 100]);

                // แจ้ง customer display
                this.showThankYouDisplay();

            } catch (error) {
                console.error('[POS App] Payment failed:', error);
                this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            }
        },

        showReceipt(transactionNumber, data) {
            // แสดง modal หรือพิมพ์ใบเสร็จ
            this.showToast(`✓ บันทึกรายการ ${transactionNumber}`, 'success');

            // ถ้ามีเครื่องพิมพ์ - พิมพ์อัตโนมัติ
            if (this.settings.autoPrint) {
                this.printReceipt(transactionNumber);
            }
        },

        async printReceipt(transactionNumber) {
            // ใช้ Web Print API หรือส่งไป thermal printer
            try {
                window.open(`/pos/receipt/${transactionNumber}?print=1`, '_blank');
            } catch (error) {
                console.error('Print failed:', error);
            }
        },

        // ============================================
        // CUSTOMER
        // ============================================

        openCustomerModal() {
            this.showCustomerModal = true;
        },

        async searchCustomer(query) {
            if (!query) return [];

            // ค้นหาจาก local
            const customers = await db.getByIndex('customers', 'phone', query);

            if (customers.length > 0) {
                return customers;
            }

            // ค้นหาจาก server
            if (this.isOnline) {
                try {
                    const response = await fetch(`/pos/api/customers/search?q=${query}`);
                    const data = await response.json();
                    return data.data || [];
                } catch (error) {
                    console.error('Customer search failed:', error);
                }
            }

            return [];
        },

        selectCustomer(customer) {
            this.customer = customer;
            this.showCustomerModal = false;
            this.showToast(`ลูกค้า: ${customer.name}`, 'success');
        },

        clearCustomer() {
            this.customer = null;
        },

        async createQuickCustomer(name, phone) {
            try {
                const customer = {
                    id: 'temp_' + Date.now(),
                    name: name,
                    phone: phone,
                    isTemp: true
                };

                // บันทึก local
                await db.add('customers', customer);

                // Queue for sync
                await db.addToSyncQueue('customer', customer);

                this.selectCustomer(customer);

            } catch (error) {
                this.showToast('ไม่สามารถบันทึกลูกค้าได้', 'error');
            }
        },

        // ============================================
        // NUMPAD
        // ============================================

        openNumpad(target, initialValue = '') {
            this.numpadTarget = target;
            this.numpadValue = String(initialValue);
            this.showNumpad = true;
        },

        numpadPress(key) {
            if (key === 'C') {
                this.numpadValue = '';
            } else if (key === '←') {
                this.numpadValue = this.numpadValue.slice(0, -1);
            } else if (key === '.') {
                if (!this.numpadValue.includes('.')) {
                    this.numpadValue += key;
                }
            } else if (key === '00') {
                this.numpadValue += '00';
            } else {
                this.numpadValue += key;
            }
        },

        numpadConfirm() {
            const value = parseFloat(this.numpadValue) || 0;

            if (this.numpadTarget === 'cash') {
                this.cashReceived = value;
            } else if (this.numpadTarget?.startsWith('qty_')) {
                const itemId = parseInt(this.numpadTarget.replace('qty_', ''));
                const item = this.cart.find(i => i.id === itemId);
                if (item) {
                    this.setCartItemQty(item, value);
                }
            }

            this.showNumpad = false;
            this.numpadTarget = null;
            this.numpadValue = '';
        },

        // ============================================
        // CUSTOMER DISPLAY
        // ============================================

        async updateCustomerDisplay() {
            if (!this.isOnline) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                await fetch('/pos/api/display/cart/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        items: this.cart,
                        subtotal: this.cartSubtotal,
                        discount: this.cartDiscount,
                        tax: this.cartTax,
                        total: this.cartTotal,
                        customer: this.customer
                    })
                });
            } catch (error) {
                // ไม่ต้อง handle - customer display optional
            }
        },

        async showThankYouDisplay() {
            if (!this.isOnline) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                await fetch('/pos/api/display/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        message: 'ขอบคุณที่ใช้บริการ',
                        type: 'thank_you'
                    })
                });
            } catch (error) {
                // ไม่ต้อง handle
            }
        },

        // ============================================
        // UTILITIES
        // ============================================

        formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: this.settings.decimalPlaces,
                maximumFractionDigits: this.settings.decimalPlaces
            }).format(amount);
        },

        formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num);
        },

        vibrate(pattern) {
            if ('vibrate' in navigator) {
                navigator.vibrate(pattern);
            }
        },

        showToast(message, type = 'info') {
            // ใช้ Alpine.js หรือ custom toast
            const event = new CustomEvent('show-toast', {
                detail: { message, type }
            });
            window.dispatchEvent(event);
        },

        closeAllModals() {
            this.showPaymentModal = false;
            this.showCustomerModal = false;
            this.showSettingsModal = false;
            this.showNumpad = false;
        },

        // ============================================
        // BARCODE SCANNER
        // ============================================

        barcodeBuffer: '',
        barcodeTimeout: null,

        handleBarcodeInput(e) {
            // ตรวจจับ barcode scanner input
            clearTimeout(this.barcodeTimeout);

            if (e.key === 'Enter') {
                if (this.barcodeBuffer.length > 3) {
                    this.getProductByBarcode(this.barcodeBuffer);
                }
                this.barcodeBuffer = '';
                return;
            }

            if (e.key.length === 1) {
                this.barcodeBuffer += e.key;
            }

            // Reset buffer after 100ms of no input
            this.barcodeTimeout = setTimeout(() => {
                this.barcodeBuffer = '';
            }, 100);
        },

        // Camera scanner
        cameraScanning: false,
        cameraStream: null,

        async startCameraScanner() {
            if (!('mediaDevices' in navigator)) {
                this.showToast('ไม่รองรับกล้อง', 'error');
                return;
            }

            try {
                this.cameraScanning = true;

                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });

                this.cameraStream = stream;

                // ใช้ ZXing หรือ QuaggaJS สำหรับ decode
                // ต้อง implement ใน component แยก

            } catch (error) {
                console.error('Camera error:', error);
                this.showToast('ไม่สามารถเปิดกล้องได้', 'error');
                this.cameraScanning = false;
            }
        },

        stopCameraScanner() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
                this.cameraStream = null;
            }
            this.cameraScanning = false;
        }
    };
}

/**
 * Register Alpine.js Component
 */
export function registerPosComponent() {
    if (typeof Alpine !== 'undefined') {
        Alpine.data('posApp', posApp);
        console.log('[POS App] Alpine.js component registered');
    }
}

// Auto-register when DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerPosComponent);
} else {
    registerPosComponent();
}

// Export for module usage
export default {
    posApp,
    registerPosComponent,
    db,
    sync
};

/**
 * TP-POS IndexedDB Database Service
 *
 * จัดการฐานข้อมูล offline สำหรับ POS
 * รองรับการเก็บและซิงค์ข้อมูลเมื่อ online
 *
 * @version 1.0.0
 */

const DB_NAME = 'tp-pos-db';
const DB_VERSION = 1;

/**
 * สถานะ Database
 */
let db = null;
let isInitialized = false;

/**
 * โครงสร้าง Object Stores
 */
const STORES = {
    // สินค้า
    products: {
        keyPath: 'id',
        indexes: [
            { name: 'sku', keyPath: 'sku', unique: true },
            { name: 'barcode', keyPath: 'barcode', unique: false },
            { name: 'name', keyPath: 'name', unique: false },
            { name: 'category_id', keyPath: 'category_id', unique: false },
            { name: 'updated_at', keyPath: 'updated_at', unique: false }
        ]
    },
    // หมวดหมู่สินค้า
    categories: {
        keyPath: 'id',
        indexes: [
            { name: 'name', keyPath: 'name', unique: false },
            { name: 'parent_id', keyPath: 'parent_id', unique: false },
            { name: 'sort_order', keyPath: 'sort_order', unique: false }
        ]
    },
    // ลูกค้า
    customers: {
        keyPath: 'id',
        indexes: [
            { name: 'phone', keyPath: 'phone', unique: false },
            { name: 'email', keyPath: 'email', unique: false },
            { name: 'name', keyPath: 'name', unique: false },
            { name: 'member_code', keyPath: 'member_code', unique: true }
        ]
    },
    // รายการขาย (transactions)
    transactions: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'transaction_number', keyPath: 'transaction_number', unique: true },
            { name: 'session_id', keyPath: 'session_id', unique: false },
            { name: 'customer_id', keyPath: 'customer_id', unique: false },
            { name: 'status', keyPath: 'status', unique: false },
            { name: 'synced', keyPath: 'synced', unique: false },
            { name: 'created_at', keyPath: 'created_at', unique: false }
        ]
    },
    // รายการสินค้าในบิล
    transaction_items: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'transaction_id', keyPath: 'transaction_id', unique: false },
            { name: 'product_id', keyPath: 'product_id', unique: false }
        ]
    },
    // กะขาย (sessions)
    sessions: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'device_id', keyPath: 'device_id', unique: false },
            { name: 'status', keyPath: 'status', unique: false },
            { name: 'opened_at', keyPath: 'opened_at', unique: false }
        ]
    },
    // ตะกร้าปัจจุบัน
    cart: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'session_id', keyPath: 'session_id', unique: false },
            { name: 'product_id', keyPath: 'product_id', unique: false }
        ]
    },
    // คิวรอซิงค์
    sync_queue: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'type', keyPath: 'type', unique: false },
            { name: 'status', keyPath: 'status', unique: false },
            { name: 'priority', keyPath: 'priority', unique: false },
            { name: 'created_at', keyPath: 'created_at', unique: false }
        ]
    },
    // การตั้งค่า
    settings: {
        keyPath: 'key'
    },
    // การเคลื่อนไหวสต็อก
    stock_movements: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'product_id', keyPath: 'product_id', unique: false },
            { name: 'type', keyPath: 'type', unique: false },
            { name: 'synced', keyPath: 'synced', unique: false },
            { name: 'created_at', keyPath: 'created_at', unique: false }
        ]
    },
    // ประวัติการชำระเงิน
    payments: {
        keyPath: 'id',
        autoIncrement: true,
        indexes: [
            { name: 'transaction_id', keyPath: 'transaction_id', unique: false },
            { name: 'method', keyPath: 'method', unique: false },
            { name: 'status', keyPath: 'status', unique: false }
        ]
    }
};

/**
 * เปิด/สร้าง Database
 */
export async function initDatabase() {
    if (isInitialized && db) {
        return db;
    }

    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => {
            console.error('[POS-DB] Failed to open database:', request.error);
            reject(request.error);
        };

        request.onsuccess = () => {
            db = request.result;
            isInitialized = true;

            // จัดการ error ของ database
            db.onerror = (event) => {
                console.error('[POS-DB] Database error:', event.target.error);
            };

            resolve(db);
        };

        request.onupgradeneeded = (event) => {
            const database = event.target.result;

            // สร้าง Object Stores
            for (const [storeName, config] of Object.entries(STORES)) {
                if (!database.objectStoreNames.contains(storeName)) {
                    const storeOptions = { keyPath: config.keyPath };

                    if (config.autoIncrement) {
                        storeOptions.autoIncrement = true;
                    }

                    const store = database.createObjectStore(storeName, storeOptions);

                    // สร้าง Indexes
                    if (config.indexes) {
                        for (const index of config.indexes) {
                            store.createIndex(index.name, index.keyPath, {
                                unique: index.unique || false
                            });
                        }
                    }

                }
            }
        };
    });
}

/**
 * ดึง Database instance
 */
export async function getDatabase() {
    if (!db || !isInitialized) {
        return initDatabase();
    }
    return db;
}

/**
 * Transaction Wrapper
 */
async function withTransaction(storeNames, mode, callback) {
    const database = await getDatabase();
    const stores = Array.isArray(storeNames) ? storeNames : [storeNames];

    return new Promise((resolve, reject) => {
        const tx = database.transaction(stores, mode);
        const storeObjects = {};

        for (const name of stores) {
            storeObjects[name] = tx.objectStore(name);
        }

        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(new Error('Transaction aborted'));

        try {
            const result = callback(stores.length === 1 ? storeObjects[stores[0]] : storeObjects, tx);

            if (result instanceof Promise) {
                result.then(resolve).catch(reject);
            }
        } catch (error) {
            reject(error);
        }
    });
}

// ============================================
// CRUD OPERATIONS
// ============================================

/**
 * เพิ่มข้อมูล
 */
export async function add(storeName, data) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);

        const request = store.add({
            ...data,
            created_at: data.created_at || new Date().toISOString(),
            updated_at: new Date().toISOString()
        });

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * อัพเดทข้อมูล
 */
export async function put(storeName, data) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);

        const request = store.put({
            ...data,
            updated_at: new Date().toISOString()
        });

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * ดึงข้อมูลตาม key
 */
export async function get(storeName, key) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);

        const request = store.get(key);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * ดึงข้อมูลทั้งหมด
 */
export async function getAll(storeName, query = null, count = null) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);

        const request = store.getAll(query, count);

        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

/**
 * ดึงข้อมูลตาม Index
 */
export async function getByIndex(storeName, indexName, value) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        const index = store.index(indexName);

        const request = index.getAll(value);

        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

/**
 * ลบข้อมูล
 */
export async function remove(storeName, key) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);

        const request = store.delete(key);

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

/**
 * ลบข้อมูลทั้งหมดใน store
 */
export async function clear(storeName) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);

        const request = store.clear();

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

/**
 * นับจำนวนข้อมูล
 */
export async function count(storeName, query = null) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);

        const request = store.count(query);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// ============================================
// PRODUCT OPERATIONS
// ============================================

/**
 * ค้นหาสินค้า
 */
export async function searchProducts(query, options = {}) {
    const products = await getAll('products');
    const searchTerm = query.toLowerCase().trim();

    if (!searchTerm) {
        // ถ้าไม่มี query - คืนทั้งหมด (หรือตาม category)
        if (options.categoryId) {
            return products.filter(p => p.category_id === options.categoryId);
        }
        return products.slice(0, options.limit || 50);
    }

    // ค้นหาตาม SKU, Barcode, หรือชื่อ
    const results = products.filter(product => {
        const matchName = product.name?.toLowerCase().includes(searchTerm);
        const matchSku = product.sku?.toLowerCase().includes(searchTerm);
        const matchBarcode = product.barcode?.includes(searchTerm);

        return matchName || matchSku || matchBarcode;
    });

    // กรองตาม category
    if (options.categoryId) {
        return results.filter(p => p.category_id === options.categoryId);
    }

    return results.slice(0, options.limit || 50);
}

/**
 * ดึงสินค้าตาม Barcode
 */
export async function getProductByBarcode(barcode) {
    const results = await getByIndex('products', 'barcode', barcode);
    return results[0] || null;
}

/**
 * ดึงสินค้าตาม SKU
 */
export async function getProductBySku(sku) {
    const results = await getByIndex('products', 'sku', sku);
    return results[0] || null;
}

/**
 * อัพเดทสต็อกสินค้า
 */
export async function updateProductStock(productId, quantity, type = 'subtract') {
    const product = await get('products', productId);

    if (!product) {
        throw new Error('Product not found');
    }

    const newStock = type === 'subtract'
        ? product.stock_quantity - quantity
        : product.stock_quantity + quantity;

    await put('products', {
        ...product,
        stock_quantity: Math.max(0, newStock)
    });

    // บันทึกการเคลื่อนไหวสต็อก
    await add('stock_movements', {
        product_id: productId,
        type: type === 'subtract' ? 'sale' : 'adjustment',
        quantity: type === 'subtract' ? -quantity : quantity,
        previous_stock: product.stock_quantity,
        new_stock: newStock,
        synced: false
    });

    return newStock;
}

// ============================================
// CART OPERATIONS
// ============================================

/**
 * ดึงรายการในตะกร้า
 */
export async function getCartItems(sessionId) {
    return getByIndex('cart', 'session_id', sessionId);
}

/**
 * เพิ่มสินค้าลงตะกร้า
 */
export async function addToCart(sessionId, product, quantity = 1, options = {}) {
    const cartItems = await getCartItems(sessionId);

    // เช็คว่ามีสินค้านี้ในตะกร้าหรือยัง
    const existingItem = cartItems.find(item =>
        item.product_id === product.id &&
        JSON.stringify(item.attributes || {}) === JSON.stringify(options.attributes || {})
    );

    if (existingItem) {
        // อัพเดทจำนวน
        return put('cart', {
            ...existingItem,
            quantity: existingItem.quantity + quantity
        });
    }

    // เพิ่มใหม่
    return add('cart', {
        session_id: sessionId,
        product_id: product.id,
        product_name: product.name,
        product_sku: product.sku,
        product_image: product.image,
        unit_price: options.price || product.price,
        original_price: product.price,
        quantity: quantity,
        discount: options.discount || 0,
        discount_type: options.discountType || 'fixed',
        tax_rate: product.vat_percentage || 0,
        attributes: options.attributes || {}
    });
}

/**
 * อัพเดทจำนวนสินค้าในตะกร้า
 */
export async function updateCartItemQuantity(itemId, quantity) {
    const item = await get('cart', itemId);

    if (!item) {
        throw new Error('Cart item not found');
    }

    if (quantity <= 0) {
        return remove('cart', itemId);
    }

    return put('cart', {
        ...item,
        quantity: quantity
    });
}

/**
 * ลบสินค้าจากตะกร้า
 */
export async function removeFromCart(itemId) {
    return remove('cart', itemId);
}

/**
 * ล้างตะกร้า
 */
export async function clearCart(sessionId) {
    const items = await getCartItems(sessionId);

    for (const item of items) {
        await remove('cart', item.id);
    }
}

/**
 * คำนวณยอดรวมตะกร้า
 */
export async function calculateCartTotal(sessionId) {
    const items = await getCartItems(sessionId);

    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;

    for (const item of items) {
        const itemTotal = item.unit_price * item.quantity;
        let itemDiscount = 0;

        // คำนวณส่วนลด
        if (item.discount > 0) {
            itemDiscount = item.discount_type === 'percent'
                ? itemTotal * (item.discount / 100)
                : item.discount * item.quantity;
        }

        const afterDiscount = itemTotal - itemDiscount;

        // คำนวณภาษี
        if (item.tax_rate > 0) {
            totalTax += afterDiscount * (item.tax_rate / 100);
        }

        subtotal += itemTotal;
        totalDiscount += itemDiscount;
    }

    const grandTotal = subtotal - totalDiscount + totalTax;

    return {
        items: items.length,
        itemCount: items.reduce((sum, item) => sum + item.quantity, 0),
        subtotal: subtotal,
        discount: totalDiscount,
        tax: totalTax,
        total: grandTotal
    };
}

// ============================================
// TRANSACTION OPERATIONS
// ============================================

/**
 * สร้างรายการขาย
 */
export async function createTransaction(data) {
    const transactionNumber = generateTransactionNumber();

    const transaction = await add('transactions', {
        transaction_number: transactionNumber,
        session_id: data.sessionId,
        device_id: data.deviceId,
        customer_id: data.customerId || null,
        customer_name: data.customerName || null,
        customer_phone: data.customerPhone || null,
        subtotal: data.subtotal,
        discount_amount: data.discount || 0,
        discount_type: data.discountType || 'fixed',
        discount_reason: data.discountReason || null,
        tax_amount: data.tax || 0,
        service_charge: data.serviceCharge || 0,
        total_amount: data.total,
        payment_method: data.paymentMethod,
        payment_status: 'completed',
        cash_received: data.cashReceived || data.total,
        change_amount: data.change || 0,
        status: 'completed',
        synced: false,
        items: data.items || []
    });

    // บันทึกรายการสินค้า
    if (data.items && data.items.length > 0) {
        for (const item of data.items) {
            await add('transaction_items', {
                transaction_id: transaction,
                product_id: item.product_id,
                product_name: item.product_name,
                product_sku: item.product_sku,
                unit_price: item.unit_price,
                quantity: item.quantity,
                discount: item.discount || 0,
                tax: item.tax || 0,
                subtotal: item.unit_price * item.quantity
            });

            // อัพเดทสต็อก
            try {
                await updateProductStock(item.product_id, item.quantity, 'subtract');
            } catch (e) {
                console.warn('[POS-DB] Failed to update stock:', e);
            }
        }
    }

    // เพิ่มลงคิวซิงค์
    await addToSyncQueue('transaction', {
        transaction_id: transaction,
        transaction_number: transactionNumber
    });

    return {
        id: transaction,
        transaction_number: transactionNumber
    };
}

/**
 * สร้างเลขที่รายการขาย
 */
function generateTransactionNumber() {
    const now = new Date();
    const date = now.toISOString().slice(0, 10).replace(/-/g, '');
    const time = now.getTime().toString(36).toUpperCase();
    const random = Math.random().toString(36).substring(2, 6).toUpperCase();

    return `POS-${date}-${time}${random}`;
}

/**
 * ดึงรายการขายที่ยังไม่ซิงค์
 */
export async function getUnsyncedTransactions() {
    return getByIndex('transactions', 'synced', false);
}

/**
 * อัพเดทสถานะซิงค์
 */
export async function markTransactionSynced(transactionId, serverTransactionId = null) {
    const transaction = await get('transactions', transactionId);

    if (transaction) {
        await put('transactions', {
            ...transaction,
            synced: true,
            server_id: serverTransactionId,
            synced_at: new Date().toISOString()
        });
    }
}

// ============================================
// SYNC QUEUE OPERATIONS
// ============================================

/**
 * เพิ่มข้อมูลลงคิวซิงค์
 */
export async function addToSyncQueue(type, data, priority = 5) {
    return add('sync_queue', {
        type: type,
        data: data,
        status: 'pending',
        priority: priority,
        attempts: 0,
        max_attempts: 5,
        last_error: null
    });
}

/**
 * ดึงรายการที่รอซิงค์
 */
export async function getPendingSyncItems(type = null) {
    const items = await getByIndex('sync_queue', 'status', 'pending');

    if (type) {
        return items.filter(item => item.type === type);
    }

    // เรียงตาม priority
    return items.sort((a, b) => a.priority - b.priority);
}

/**
 * อัพเดทสถานะ sync item
 */
export async function updateSyncItem(itemId, status, error = null) {
    const item = await get('sync_queue', itemId);

    if (item) {
        await put('sync_queue', {
            ...item,
            status: status,
            attempts: item.attempts + 1,
            last_error: error,
            last_attempt_at: new Date().toISOString()
        });
    }
}

/**
 * ลบ sync item ที่สำเร็จแล้ว
 */
export async function removeSyncedItems() {
    const items = await getByIndex('sync_queue', 'status', 'completed');

    for (const item of items) {
        await remove('sync_queue', item.id);
    }

    return items.length;
}

// ============================================
// SETTINGS OPERATIONS
// ============================================

/**
 * ดึงการตั้งค่า
 */
export async function getSetting(key, defaultValue = null) {
    const setting = await get('settings', key);
    return setting?.value ?? defaultValue;
}

/**
 * บันทึกการตั้งค่า
 */
export async function setSetting(key, value) {
    return put('settings', {
        key: key,
        value: value,
        updated_at: new Date().toISOString()
    });
}

/**
 * ดึงการตั้งค่าทั้งหมด
 */
export async function getAllSettings() {
    const settings = await getAll('settings');
    const result = {};

    for (const setting of settings) {
        result[setting.key] = setting.value;
    }

    return result;
}

// ============================================
// BULK OPERATIONS
// ============================================

/**
 * นำเข้าสินค้าจำนวนมาก
 */
export async function bulkImportProducts(products) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction('products', 'readwrite');
        const store = tx.objectStore('products');

        let imported = 0;
        let failed = 0;

        for (const product of products) {
            const request = store.put({
                ...product,
                updated_at: new Date().toISOString()
            });

            request.onsuccess = () => imported++;
            request.onerror = () => failed++;
        }

        tx.oncomplete = () => resolve({ imported, failed });
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * นำเข้าหมวดหมู่จำนวนมาก
 */
export async function bulkImportCategories(categories) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction('categories', 'readwrite');
        const store = tx.objectStore('categories');

        let imported = 0;

        for (const category of categories) {
            store.put({
                ...category,
                updated_at: new Date().toISOString()
            });
            imported++;
        }

        tx.oncomplete = () => resolve({ imported });
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * นำเข้าลูกค้าจำนวนมาก
 */
export async function bulkImportCustomers(customers) {
    const database = await getDatabase();

    return new Promise((resolve, reject) => {
        const tx = database.transaction('customers', 'readwrite');
        const store = tx.objectStore('customers');

        let imported = 0;

        for (const customer of customers) {
            store.put({
                ...customer,
                updated_at: new Date().toISOString()
            });
            imported++;
        }

        tx.oncomplete = () => resolve({ imported });
        tx.onerror = () => reject(tx.error);
    });
}

// ============================================
// DATABASE UTILITIES
// ============================================

/**
 * ดึงขนาด Database
 */
export async function getDatabaseSize() {
    if ('storage' in navigator && 'estimate' in navigator.storage) {
        const estimate = await navigator.storage.estimate();
        return {
            usage: estimate.usage || 0,
            quota: estimate.quota || 0,
            usagePercent: estimate.quota
                ? ((estimate.usage / estimate.quota) * 100).toFixed(2)
                : 0
        };
    }
    return { usage: 0, quota: 0, usagePercent: 0 };
}

/**
 * ลบ Database ทั้งหมด
 */
export async function deleteDatabase() {
    if (db) {
        db.close();
        db = null;
        isInitialized = false;
    }

    return new Promise((resolve, reject) => {
        const request = indexedDB.deleteDatabase(DB_NAME);

        request.onsuccess = () => {
            resolve();
        };

        request.onerror = () => reject(request.error);
    });
}

/**
 * Export Database เป็น JSON
 */
export async function exportDatabase() {
    const data = {};

    for (const storeName of Object.keys(STORES)) {
        data[storeName] = await getAll(storeName);
    }

    return {
        version: DB_VERSION,
        exported_at: new Date().toISOString(),
        data: data
    };
}

/**
 * Import Database จาก JSON
 */
export async function importDatabase(exportedData) {
    if (exportedData.version !== DB_VERSION) {
        throw new Error('Database version mismatch');
    }

    for (const [storeName, items] of Object.entries(exportedData.data)) {
        if (STORES[storeName]) {
            // Clear existing data
            await clear(storeName);

            // Import new data
            for (const item of items) {
                await put(storeName, item);
            }
        }
    }

    return { success: true };
}

// Export default
export default {
    initDatabase,
    getDatabase,
    add,
    put,
    get,
    getAll,
    getByIndex,
    remove,
    clear,
    count,
    searchProducts,
    getProductByBarcode,
    getProductBySku,
    updateProductStock,
    getCartItems,
    addToCart,
    updateCartItemQuantity,
    removeFromCart,
    clearCart,
    calculateCartTotal,
    createTransaction,
    getUnsyncedTransactions,
    markTransactionSynced,
    addToSyncQueue,
    getPendingSyncItems,
    updateSyncItem,
    getSetting,
    setSetting,
    getAllSettings,
    bulkImportProducts,
    bulkImportCategories,
    bulkImportCustomers,
    getDatabaseSize,
    deleteDatabase,
    exportDatabase,
    importDatabase
};

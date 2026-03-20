/**
 * TP-POS Sync Service
 *
 * จัดการการซิงค์ข้อมูลระหว่าง IndexedDB และ Server
 * รองรับ Offline-First architecture
 *
 * @version 1.0.0
 */

import db from './database.js';

/**
 * สถานะ Sync
 */
const syncState = {
    isOnline: navigator.onLine,
    isSyncing: false,
    lastSync: null,
    syncQueue: [],
    listeners: new Set()
};

/**
 * API Base URL
 */
const API_BASE = '/pos/api';

/**
 * CSRF Token
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// ============================================
// NETWORK STATUS
// ============================================

/**
 * ตรวจสอบสถานะ Online
 */
export function isOnline() {
    return syncState.isOnline;
}

/**
 * ลงทะเบียน Online/Offline listeners
 */
export function initNetworkListeners() {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // ตรวจสอบสถานะเริ่มต้น
    syncState.isOnline = navigator.onLine;
    notifyListeners('status', { online: syncState.isOnline });
}

function handleOnline() {
    syncState.isOnline = true;
    notifyListeners('online');

    // เริ่ม sync อัตโนมัติ
    syncAll();
}

function handleOffline() {
    syncState.isOnline = false;
    notifyListeners('offline');
}

// ============================================
// SYNC LISTENERS
// ============================================

/**
 * เพิ่ม listener สำหรับ sync events
 */
export function addSyncListener(callback) {
    syncState.listeners.add(callback);
    return () => syncState.listeners.delete(callback);
}

/**
 * แจ้ง listeners
 */
function notifyListeners(event, data = {}) {
    for (const listener of syncState.listeners) {
        try {
            listener(event, data);
        } catch (error) {
            console.error('[POS-Sync] Listener error:', error);
        }
    }
}

// ============================================
// API HELPERS
// ============================================

/**
 * Fetch wrapper with retry
 */
async function fetchWithRetry(url, options = {}, retries = 3) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    };

    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };

    for (let attempt = 1; attempt <= retries; attempt++) {
        try {
            const response = await fetch(url, mergedOptions);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.warn(`[POS-Sync] Attempt ${attempt}/${retries} failed:`, error.message);

            if (attempt === retries) {
                throw error;
            }

            // รอก่อน retry (exponential backoff)
            await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000));
        }
    }
}

// ============================================
// SYNC OPERATIONS
// ============================================

/**
 * Sync ทุกอย่าง
 */
export async function syncAll() {
    if (!syncState.isOnline) {
        return { success: false, reason: 'offline' };
    }

    if (syncState.isSyncing) {
        return { success: false, reason: 'already_syncing' };
    }

    try {
        syncState.isSyncing = true;
        notifyListeners('sync_start');

        // 1. ดึงข้อมูลจาก server
        await Promise.all([
            syncProducts(),
            syncCategories(),
            syncCustomers(),
            syncSettings()
        ]);

        // 2. ส่งข้อมูลที่ค้างไว้ไปยัง server
        await syncPendingTransactions();
        await syncPendingStockMovements();

        syncState.lastSync = new Date();
        await db.setSetting('last_sync', syncState.lastSync.toISOString());

        notifyListeners('sync_complete', { timestamp: syncState.lastSync });

        return { success: true };

    } catch (error) {
        console.error('[POS-Sync] Sync failed:', error);
        notifyListeners('sync_error', { error: error.message });

        return { success: false, error: error.message };

    } finally {
        syncState.isSyncing = false;
    }
}

/**
 * Sync สินค้า
 */
export async function syncProducts(force = false) {
    try {
        const lastSync = await db.getSetting('products_last_sync');
        const url = force || !lastSync
            ? `${API_BASE}/products/all`
            : `${API_BASE}/products/updated?since=${lastSync}`;

        const response = await fetchWithRetry(url);

        if (response.success && response.data) {
            const result = await db.bulkImportProducts(response.data);

            await db.setSetting('products_last_sync', new Date().toISOString());
            notifyListeners('products_synced', { count: result.imported });

            return result;
        }

        return { imported: 0 };

    } catch (error) {
        console.error('[POS-Sync] Products sync failed:', error);
        throw error;
    }
}

/**
 * Sync หมวดหมู่
 */
export async function syncCategories(force = false) {
    try {
        const lastSync = await db.getSetting('categories_last_sync');
        const url = force || !lastSync
            ? `${API_BASE}/categories/all`
            : `${API_BASE}/categories/updated?since=${lastSync}`;

        const response = await fetchWithRetry(url);

        if (response.success && response.data) {
            const result = await db.bulkImportCategories(response.data);

            await db.setSetting('categories_last_sync', new Date().toISOString());
            notifyListeners('categories_synced', { count: result.imported });

            return result;
        }

        return { imported: 0 };

    } catch (error) {
        console.error('[POS-Sync] Categories sync failed:', error);
        throw error;
    }
}

/**
 * Sync ลูกค้า
 */
export async function syncCustomers(force = false) {
    try {
        const lastSync = await db.getSetting('customers_last_sync');
        const url = force || !lastSync
            ? `${API_BASE}/customers/all`
            : `${API_BASE}/customers/updated?since=${lastSync}`;

        const response = await fetchWithRetry(url);

        if (response.success && response.data) {
            const result = await db.bulkImportCustomers(response.data);

            await db.setSetting('customers_last_sync', new Date().toISOString());
            notifyListeners('customers_synced', { count: result.imported });

            return result;
        }

        return { imported: 0 };

    } catch (error) {
        console.error('[POS-Sync] Customers sync failed:', error);
        throw error;
    }
}

/**
 * Sync การตั้งค่า
 */
export async function syncSettings() {
    try {
        const response = await fetchWithRetry(`${API_BASE}/settings`);

        if (response.success && response.data) {
            for (const [key, value] of Object.entries(response.data)) {
                await db.setSetting(`server_${key}`, value);
            }

            notifyListeners('settings_synced');
        }

    } catch (error) {
        console.error('[POS-Sync] Settings sync failed:', error);
        throw error;
    }
}

/**
 * Sync รายการขายที่ค้าง
 */
export async function syncPendingTransactions() {
    try {
        const pendingTransactions = await db.getUnsyncedTransactions();

        if (pendingTransactions.length === 0) {
            return { synced: 0 };
        }

        let synced = 0;
        let failed = 0;

        for (const transaction of pendingTransactions) {
            try {
                const response = await fetchWithRetry(`${API_BASE}/transactions/sync`, {
                    method: 'POST',
                    body: JSON.stringify({
                        transaction: transaction,
                        items: await db.getByIndex('transaction_items', 'transaction_id', transaction.id)
                    })
                });

                if (response.success) {
                    await db.markTransactionSynced(transaction.id, response.data?.id);
                    synced++;

                    notifyListeners('transaction_synced', {
                        localId: transaction.id,
                        serverId: response.data?.id
                    });
                } else {
                    failed++;
                }

            } catch (error) {
                console.error('[POS-Sync] Transaction sync failed:', transaction.id, error);
                failed++;
            }
        }

        return { synced, failed };

    } catch (error) {
        console.error('[POS-Sync] Pending transactions sync failed:', error);
        throw error;
    }
}

/**
 * Sync การเคลื่อนไหวสต็อกที่ค้าง
 */
export async function syncPendingStockMovements() {
    try {
        const pendingMovements = await db.getByIndex('stock_movements', 'synced', false);

        if (pendingMovements.length === 0) {
            return { synced: 0 };
        }

        const response = await fetchWithRetry(`${API_BASE}/stock/sync`, {
            method: 'POST',
            body: JSON.stringify({ movements: pendingMovements })
        });

        if (response.success) {
            // Mark as synced
            for (const movement of pendingMovements) {
                await db.put('stock_movements', {
                    ...movement,
                    synced: true,
                    synced_at: new Date().toISOString()
                });
            }

            notifyListeners('stock_synced', { count: pendingMovements.length });

            return { synced: pendingMovements.length };
        }

        return { synced: 0 };

    } catch (error) {
        console.error('[POS-Sync] Stock movements sync failed:', error);
        throw error;
    }
}

// ============================================
// SYNC QUEUE PROCESSING
// ============================================

/**
 * ประมวลผล Sync Queue
 */
export async function processSyncQueue() {
    if (!syncState.isOnline) {
        return { processed: 0, reason: 'offline' };
    }

    try {
        const pendingItems = await db.getPendingSyncItems();

        if (pendingItems.length === 0) {
            return { processed: 0 };
        }

        let processed = 0;
        let failed = 0;

        for (const item of pendingItems) {
            // เช็ค max attempts
            if (item.attempts >= item.max_attempts) {
                await db.updateSyncItem(item.id, 'failed', 'Max attempts reached');
                failed++;
                continue;
            }

            try {
                await processQueueItem(item);
                await db.updateSyncItem(item.id, 'completed');
                processed++;

            } catch (error) {
                await db.updateSyncItem(item.id, 'pending', error.message);
                failed++;
            }
        }

        // ลบรายการที่สำเร็จแล้ว
        await db.removeSyncedItems();

        return { processed, failed };

    } catch (error) {
        console.error('[POS-Sync] Queue processing failed:', error);
        throw error;
    }
}

/**
 * ประมวลผล Queue Item แต่ละรายการ
 */
async function processQueueItem(item) {
    switch (item.type) {
        case 'transaction':
            return syncSingleTransaction(item.data.transaction_id);

        case 'stock_movement':
            return syncSingleStockMovement(item.data);

        case 'customer':
            return syncSingleCustomer(item.data);

        default:
            console.warn(`[POS-Sync] Unknown queue item type: ${item.type}`);
            throw new Error(`Unknown item type: ${item.type}`);
    }
}

/**
 * Sync รายการขายเดี่ยว
 */
async function syncSingleTransaction(transactionId) {
    const transaction = await db.get('transactions', transactionId);

    if (!transaction) {
        throw new Error('Transaction not found');
    }

    const items = await db.getByIndex('transaction_items', 'transaction_id', transactionId);

    const response = await fetchWithRetry(`${API_BASE}/transactions/sync`, {
        method: 'POST',
        body: JSON.stringify({ transaction, items })
    });

    if (response.success) {
        await db.markTransactionSynced(transactionId, response.data?.id);
        return response.data;
    }

    throw new Error(response.message || 'Sync failed');
}

/**
 * Sync การเคลื่อนไหวสต็อกเดี่ยว
 */
async function syncSingleStockMovement(movementData) {
    const response = await fetchWithRetry(`${API_BASE}/stock/movement`, {
        method: 'POST',
        body: JSON.stringify(movementData)
    });

    return response;
}

/**
 * Sync ลูกค้าเดี่ยว
 */
async function syncSingleCustomer(customerData) {
    const response = await fetchWithRetry(`${API_BASE}/customers`, {
        method: 'POST',
        body: JSON.stringify(customerData)
    });

    return response;
}

// ============================================
// REAL-TIME SYNC (WebSocket)
// ============================================

let echoChannel = null;

/**
 * เริ่ม Real-time sync ผ่าน WebSocket
 */
export function initRealtimeSync(deviceId) {
    if (typeof Echo === 'undefined') {
        console.warn('[POS-Sync] Laravel Echo not available');
        return;
    }

    try {
        echoChannel = Echo.private(`pos.device.${deviceId}`);

        // รับการอัพเดทสินค้า
        echoChannel.listen('.product.updated', (event) => {
            db.put('products', event.product);
            notifyListeners('product_updated', event);
        });

        // รับการอัพเดทราคา
        echoChannel.listen('.price.updated', (event) => {
            updateProductPrice(event.product_id, event.new_price);
        });

        // รับการแจ้งเตือนสต็อกต่ำ
        echoChannel.listen('.stock.low', (event) => {
            notifyListeners('stock_alert', event);
        });

        // รับคำสั่งจาก server
        echoChannel.listen('.command', (event) => {
            handleServerCommand(event);
        });

    } catch (error) {
        console.error('[POS-Sync] Real-time sync failed:', error);
    }
}

/**
 * หยุด Real-time sync
 */
export function stopRealtimeSync() {
    if (echoChannel) {
        Echo.leave(echoChannel.name);
        echoChannel = null;
    }
}

/**
 * อัพเดทราคาสินค้า
 */
async function updateProductPrice(productId, newPrice) {
    const product = await db.get('products', productId);

    if (product) {
        await db.put('products', {
            ...product,
            price: newPrice
        });
        notifyListeners('price_updated', { productId, newPrice });
    }
}

/**
 * จัดการคำสั่งจาก server
 */
async function handleServerCommand(command) {
    switch (command.action) {
        case 'sync_products':
            await syncProducts(true);
            break;

        case 'sync_all':
            await syncAll();
            break;

        case 'clear_cache':
            await db.clear('products');
            await syncProducts(true);
            break;

        case 'reload':
            window.location.reload();
            break;

        default:
            console.warn(`[POS-Sync] Unknown command: ${command.action}`);
    }
}

// ============================================
// AUTO SYNC SCHEDULER
// ============================================

let syncIntervalId = null;

/**
 * เริ่ม Auto Sync
 */
export function startAutoSync(intervalMinutes = 5) {
    if (syncIntervalId) {
        clearInterval(syncIntervalId);
    }

    const intervalMs = intervalMinutes * 60 * 1000;

    syncIntervalId = setInterval(() => {
        if (syncState.isOnline && !syncState.isSyncing) {
            syncAll();
        }
    }, intervalMs);

}

/**
 * หยุด Auto Sync
 */
export function stopAutoSync() {
    if (syncIntervalId) {
        clearInterval(syncIntervalId);
        syncIntervalId = null;
    }
}

// ============================================
// SYNC STATUS
// ============================================

/**
 * ดึงสถานะ Sync
 */
export async function getSyncStatus() {
    const pendingTransactions = await db.getUnsyncedTransactions();
    const pendingQueue = await db.getPendingSyncItems();
    const lastSync = await db.getSetting('last_sync');

    return {
        isOnline: syncState.isOnline,
        isSyncing: syncState.isSyncing,
        lastSync: lastSync,
        pendingTransactions: pendingTransactions.length,
        pendingQueueItems: pendingQueue.length,
        autoSyncEnabled: syncIntervalId !== null
    };
}

/**
 * ดึงจำนวนรายการที่รอ sync
 */
export async function getPendingCount() {
    const transactions = await db.getUnsyncedTransactions();
    const queue = await db.getPendingSyncItems();

    return transactions.length + queue.length;
}

// ============================================
// CONFLICT RESOLUTION
// ============================================

/**
 * จัดการข้อมูลที่ขัดแย้ง
 */
export async function resolveConflict(localData, serverData, strategy = 'server_wins') {
    switch (strategy) {
        case 'server_wins':
            return serverData;

        case 'local_wins':
            return localData;

        case 'merge':
            // รวมข้อมูล โดยใช้ timestamp ใหม่กว่า
            const localTime = new Date(localData.updated_at || 0).getTime();
            const serverTime = new Date(serverData.updated_at || 0).getTime();
            return localTime > serverTime ? localData : serverData;

        case 'manual':
            // ส่งกลับทั้งสองเพื่อให้ user ตัดสินใจ
            return { conflict: true, local: localData, server: serverData };

        default:
            return serverData;
    }
}

// ============================================
// INITIALIZATION
// ============================================

/**
 * Initialize Sync Service
 */
export async function initSync(options = {}) {
    // เปิด Database
    await db.initDatabase();

    // ตั้ง Network listeners
    initNetworkListeners();

    // เริ่ม Auto Sync
    if (options.autoSync !== false) {
        startAutoSync(options.syncInterval || 5);
    }

    // เริ่ม Real-time sync
    if (options.deviceId) {
        initRealtimeSync(options.deviceId);
    }

    // Initial sync ถ้า online
    if (syncState.isOnline && options.initialSync !== false) {
        // รอสักครู่ก่อน initial sync
        setTimeout(() => syncAll(), 2000);
    }

}

// Export default
export default {
    initSync,
    isOnline,
    syncAll,
    syncProducts,
    syncCategories,
    syncCustomers,
    syncSettings,
    syncPendingTransactions,
    processSyncQueue,
    addSyncListener,
    getSyncStatus,
    getPendingCount,
    startAutoSync,
    stopAutoSync,
    initRealtimeSync,
    stopRealtimeSync
};

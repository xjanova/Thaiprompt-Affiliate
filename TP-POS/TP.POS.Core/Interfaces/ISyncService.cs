namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับ Sync ข้อมูลกับ Server
/// </summary>
public interface ISyncService
{
    /// <summary>
    /// Event เมื่อสถานะ Sync เปลี่ยน
    /// </summary>
    event EventHandler<SyncStatusEventArgs>? SyncStatusChanged;

    /// <summary>
    /// สถานะออนไลน์
    /// </summary>
    bool IsOnline { get; }

    /// <summary>
    /// กำลัง Sync อยู่หรือไม่
    /// </summary>
    bool IsSyncing { get; }

    /// <summary>
    /// Sync ครั้งล่าสุด
    /// </summary>
    DateTime? LastSyncTime { get; }

    /// <summary>
    /// ตรวจสอบการเชื่อมต่อ Server
    /// </summary>
    Task<bool> CheckConnectivityAsync();

    /// <summary>
    /// Sync ทุกอย่าง
    /// </summary>
    Task<SyncResult> SyncAllAsync();

    /// <summary>
    /// Sync สินค้าจาก Server
    /// </summary>
    Task<SyncResult> SyncProductsAsync();

    /// <summary>
    /// Sync หมวดหมู่จาก Server
    /// </summary>
    Task<SyncResult> SyncCategoriesAsync();

    /// <summary>
    /// Sync ลูกค้าจาก Server
    /// </summary>
    Task<SyncResult> SyncCustomersAsync();

    /// <summary>
    /// Sync รายการขายไป Server
    /// </summary>
    Task<SyncResult> SyncTransactionsAsync();

    /// <summary>
    /// Sync การเคลื่อนไหวสต็อกไป Server
    /// </summary>
    Task<SyncResult> SyncStockMovementsAsync();

    /// <summary>
    /// ดึงจำนวนรายการที่รอ Sync
    /// </summary>
    Task<PendingSyncCount> GetPendingCountAsync();

    /// <summary>
    /// ดาวน์โหลดข้อมูลทั้งหมดจาก Server (ครั้งแรก)
    /// </summary>
    Task<SyncResult> FullDownloadAsync(IProgress<SyncProgress>? progress = null);

    /// <summary>
    /// เริ่ม Auto-Sync (background)
    /// </summary>
    Task StartAutoSyncAsync(TimeSpan interval);

    /// <summary>
    /// หยุด Auto-Sync
    /// </summary>
    Task StopAutoSyncAsync();
}

/// <summary>
/// จำนวนรายการที่รอ Sync
/// </summary>
public class PendingSyncCount
{
    /// <summary>
    /// รายการขาย
    /// </summary>
    public int Transactions { get; set; }

    /// <summary>
    /// การเคลื่อนไหวสต็อก
    /// </summary>
    public int StockMovements { get; set; }

    /// <summary>
    /// ลูกค้าใหม่
    /// </summary>
    public int Customers { get; set; }

    /// <summary>
    /// รวมทั้งหมด
    /// </summary>
    public int Total => Transactions + StockMovements + Customers;
}

/// <summary>
/// ความคืบหน้าการ Sync
/// </summary>
public class SyncProgress
{
    /// <summary>
    /// ขั้นตอนปัจจุบัน
    /// </summary>
    public string CurrentStep { get; set; } = string.Empty;

    /// <summary>
    /// เปอร์เซ็นต์ความคืบหน้า
    /// </summary>
    public int Percentage { get; set; }

    /// <summary>
    /// จำนวนที่ดำเนินการแล้ว
    /// </summary>
    public int ProcessedCount { get; set; }

    /// <summary>
    /// จำนวนทั้งหมด
    /// </summary>
    public int TotalCount { get; set; }
}

/// <summary>
/// Event args สำหรับสถานะ Sync
/// </summary>
public class SyncStatusEventArgs : EventArgs
{
    /// <summary>
    /// สถานะออนไลน์
    /// </summary>
    public bool IsOnline { get; set; }

    /// <summary>
    /// กำลัง Sync
    /// </summary>
    public bool IsSyncing { get; set; }

    /// <summary>
    /// ข้อความสถานะ
    /// </summary>
    public string StatusMessage { get; set; } = string.Empty;

    /// <summary>
    /// จำนวนรอ Sync
    /// </summary>
    public PendingSyncCount PendingCount { get; set; } = new();
}

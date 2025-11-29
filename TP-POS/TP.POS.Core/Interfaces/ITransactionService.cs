using TP.POS.Core.Entities;
using TP.POS.Core.Enums;

namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับจัดการรายการขาย
/// </summary>
public interface ITransactionService
{
    /// <summary>
    /// สร้างรายการขายใหม่
    /// </summary>
    Task<Transaction> CreateAsync(Transaction transaction, List<TransactionItem> items);

    /// <summary>
    /// ดึงรายการขายตาม ID
    /// </summary>
    Task<Transaction?> GetByIdAsync(int id);

    /// <summary>
    /// ดึงรายการขายตามเลขที่ใบเสร็จ
    /// </summary>
    Task<Transaction?> GetByReceiptNumberAsync(string receiptNumber);

    /// <summary>
    /// ดึงรายการขายวันนี้
    /// </summary>
    Task<List<Transaction>> GetTodayTransactionsAsync();

    /// <summary>
    /// ดึงรายการขายตามช่วงวันที่
    /// </summary>
    Task<List<Transaction>> GetByDateRangeAsync(DateTime startDate, DateTime endDate);

    /// <summary>
    /// ดึงรายการขายล่าสุด
    /// </summary>
    Task<List<Transaction>> GetRecentAsync(int count = 20);

    /// <summary>
    /// ค้นหารายการขาย
    /// </summary>
    Task<List<Transaction>> SearchAsync(string keyword, DateTime? startDate = null, DateTime? endDate = null);

    /// <summary>
    /// ดึงรายการสินค้าของ Transaction
    /// </summary>
    Task<List<TransactionItem>> GetItemsAsync(int transactionId);

    /// <summary>
    /// ดึงรายการสินค้าของ Transaction ตาม UUID
    /// </summary>
    Task<List<TransactionItem>> GetItemsByUuidAsync(string transactionUuid);

    /// <summary>
    /// ยกเลิกรายการขาย
    /// </summary>
    Task<bool> CancelAsync(int transactionId, string reason);

    /// <summary>
    /// คืนเงิน
    /// </summary>
    Task<Transaction> RefundAsync(int transactionId, List<TransactionItem> itemsToRefund, string reason);

    /// <summary>
    /// สร้างเลขที่ใบเสร็จใหม่
    /// </summary>
    Task<string> GenerateReceiptNumberAsync();

    /// <summary>
    /// ดึงรายการที่ยังไม่ sync
    /// </summary>
    Task<List<Transaction>> GetUnsyncedAsync();

    /// <summary>
    /// อัพเดทสถานะ sync
    /// </summary>
    Task<bool> MarkAsSyncedAsync(int transactionId, int serverId);

    /// <summary>
    /// Sync ไป Server
    /// </summary>
    Task<SyncResult> SyncToServerAsync();
}

/// <summary>
/// ผลลัพธ์การ Sync
/// </summary>
public class SyncResult
{
    /// <summary>
    /// สำเร็จหรือไม่
    /// </summary>
    public bool Success { get; set; }

    /// <summary>
    /// จำนวนที่ sync สำเร็จ
    /// </summary>
    public int SyncedCount { get; set; }

    /// <summary>
    /// จำนวนที่ล้มเหลว
    /// </summary>
    public int FailedCount { get; set; }

    /// <summary>
    /// ข้อความ Error
    /// </summary>
    public string? ErrorMessage { get; set; }

    /// <summary>
    /// รายการ Error
    /// </summary>
    public List<string> Errors { get; set; } = new();
}

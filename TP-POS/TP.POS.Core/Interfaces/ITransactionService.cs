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
    /// ดึงสรุปยอดขายตามช่วงเวลา
    /// </summary>
    Task<SalesSummary> GetSalesSummaryAsync(DateTime startDate, DateTime endDate);

    /// <summary>
    /// ดึงยอดขายแยกตามช่องทางชำระเงิน
    /// </summary>
    Task<List<PaymentSummary>> GetPaymentBreakdownAsync(DateTime startDate, DateTime endDate);

    /// <summary>
    /// ดึงสินค้าขายดี
    /// </summary>
    Task<List<TopProductSummary>> GetTopProductsAsync(DateTime startDate, DateTime endDate, int limit = 10);

    /// <summary>
    /// ดึงยอดขายรายวัน
    /// </summary>
    Task<List<DailySalesSummary>> GetDailySalesAsync(DateTime startDate, DateTime endDate);

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

/// <summary>
/// สรุปยอดขาย
/// </summary>
public class SalesSummary
{
    /// <summary>
    /// ยอดขายรวม
    /// </summary>
    public decimal TotalSales { get; set; }

    /// <summary>
    /// จำนวน Transaction
    /// </summary>
    public int TransactionCount { get; set; }

    /// <summary>
    /// ยอดเฉลี่ยต่อ Transaction
    /// </summary>
    public decimal AverageSale { get; set; }

    /// <summary>
    /// ส่วนลดรวม
    /// </summary>
    public decimal TotalDiscount { get; set; }

    /// <summary>
    /// ภาษีรวม
    /// </summary>
    public decimal TotalTax { get; set; }
}

/// <summary>
/// สรุปยอดตามช่องทางชำระเงิน
/// </summary>
public class PaymentSummary
{
    /// <summary>
    /// ชื่อช่องทาง
    /// </summary>
    public string PaymentMethodName { get; set; } = string.Empty;

    /// <summary>
    /// รหัสช่องทาง
    /// </summary>
    public PaymentMethod PaymentMethod { get; set; }

    /// <summary>
    /// ยอดรวม
    /// </summary>
    public decimal TotalAmount { get; set; }

    /// <summary>
    /// จำนวน Transaction
    /// </summary>
    public int TransactionCount { get; set; }

    /// <summary>
    /// เปอร์เซ็นต์
    /// </summary>
    public decimal Percentage { get; set; }
}

/// <summary>
/// สินค้าขายดี
/// </summary>
public class TopProductSummary
{
    /// <summary>
    /// รหัสสินค้า
    /// </summary>
    public int ProductId { get; set; }

    /// <summary>
    /// ชื่อสินค้า
    /// </summary>
    public string ProductName { get; set; } = string.Empty;

    /// <summary>
    /// SKU
    /// </summary>
    public string Sku { get; set; } = string.Empty;

    /// <summary>
    /// จำนวนที่ขาย
    /// </summary>
    public int QuantitySold { get; set; }

    /// <summary>
    /// ยอดขาย
    /// </summary>
    public decimal TotalRevenue { get; set; }
}

/// <summary>
/// สรุปยอดขายรายวัน
/// </summary>
public class DailySalesSummary
{
    /// <summary>
    /// วันที่
    /// </summary>
    public DateTime Date { get; set; }

    /// <summary>
    /// ยอดขาย
    /// </summary>
    public decimal TotalSales { get; set; }

    /// <summary>
    /// จำนวน Transaction
    /// </summary>
    public int TransactionCount { get; set; }
}

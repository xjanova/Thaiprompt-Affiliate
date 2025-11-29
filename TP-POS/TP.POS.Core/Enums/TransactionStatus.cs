namespace TP.POS.Core.Enums;

/// <summary>
/// สถานะรายการขาย
/// </summary>
public enum TransactionStatus
{
    /// <summary>
    /// รอดำเนินการ (กำลังขาย)
    /// </summary>
    Pending = 0,

    /// <summary>
    /// เสร็จสมบูรณ์
    /// </summary>
    Completed = 1,

    /// <summary>
    /// ยกเลิก
    /// </summary>
    Cancelled = 2,

    /// <summary>
    /// คืนเงิน (บางส่วน)
    /// </summary>
    PartialRefund = 3,

    /// <summary>
    /// คืนเงิน (ทั้งหมด)
    /// </summary>
    FullRefund = 4,

    /// <summary>
    /// รอการชำระ
    /// </summary>
    AwaitingPayment = 5,

    /// <summary>
    /// พักไว้ (Hold)
    /// </summary>
    OnHold = 6
}

/// <summary>
/// Extension methods สำหรับ TransactionStatus
/// </summary>
public static class TransactionStatusExtensions
{
    /// <summary>
    /// แปลงเป็นชื่อภาษาไทย
    /// </summary>
    public static string ToThaiName(this TransactionStatus status)
    {
        return status switch
        {
            TransactionStatus.Pending => "รอดำเนินการ",
            TransactionStatus.Completed => "เสร็จสมบูรณ์",
            TransactionStatus.Cancelled => "ยกเลิก",
            TransactionStatus.PartialRefund => "คืนเงินบางส่วน",
            TransactionStatus.FullRefund => "คืนเงินทั้งหมด",
            TransactionStatus.AwaitingPayment => "รอชำระเงิน",
            TransactionStatus.OnHold => "พักไว้",
            _ => "ไม่ระบุ"
        };
    }

    /// <summary>
    /// ดึงสี
    /// </summary>
    public static string GetColor(this TransactionStatus status)
    {
        return status switch
        {
            TransactionStatus.Pending => "#F59E0B",     // Amber
            TransactionStatus.Completed => "#22C55E",    // Green
            TransactionStatus.Cancelled => "#EF4444",    // Red
            TransactionStatus.PartialRefund => "#F97316", // Orange
            TransactionStatus.FullRefund => "#DC2626",   // Dark Red
            TransactionStatus.AwaitingPayment => "#3B82F6", // Blue
            TransactionStatus.OnHold => "#6B7280",       // Gray
            _ => "#6B7280"
        };
    }
}

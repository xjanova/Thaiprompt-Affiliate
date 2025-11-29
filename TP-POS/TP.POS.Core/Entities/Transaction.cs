using SQLite;
using TP.POS.Core.Enums;

namespace TP.POS.Core.Entities;

/// <summary>
/// รายการขาย (Transaction)
/// </summary>
[Table("transactions")]
public class Transaction : BaseEntity
{
    /// <summary>
    /// เลขที่ใบเสร็จ
    /// </summary>
    [MaxLength(50)]
    [Indexed(Unique = true)]
    public string ReceiptNumber { get; set; } = string.Empty;

    /// <summary>
    /// รหัสลูกค้า (ถ้ามี)
    /// </summary>
    [Indexed]
    public int? CustomerId { get; set; }

    /// <summary>
    /// ชื่อลูกค้า (denormalized)
    /// </summary>
    [MaxLength(200)]
    public string? CustomerName { get; set; }

    /// <summary>
    /// วันที่ขาย
    /// </summary>
    [Indexed]
    public DateTime TransactionDate { get; set; } = DateTime.Now;

    /// <summary>
    /// ยอดรวมก่อนส่วนลด
    /// </summary>
    public decimal Subtotal { get; set; }

    /// <summary>
    /// ส่วนลด (บาท)
    /// </summary>
    public decimal DiscountAmount { get; set; }

    /// <summary>
    /// ส่วนลด (%)
    /// </summary>
    public decimal DiscountPercent { get; set; }

    /// <summary>
    /// ภาษีมูลค่าเพิ่ม
    /// </summary>
    public decimal TaxAmount { get; set; }

    /// <summary>
    /// ยอดรวมสุทธิ
    /// </summary>
    public decimal TotalAmount { get; set; }

    /// <summary>
    /// จำนวนเงินที่รับ
    /// </summary>
    public decimal ReceivedAmount { get; set; }

    /// <summary>
    /// เงินทอน
    /// </summary>
    public decimal ChangeAmount { get; set; }

    /// <summary>
    /// วิธีชำระเงิน
    /// </summary>
    public PaymentMethod PaymentMethod { get; set; } = PaymentMethod.Cash;

    /// <summary>
    /// รายละเอียดการชำระเงิน (เช่น เลขอ้างอิง QR)
    /// </summary>
    [MaxLength(200)]
    public string? PaymentReference { get; set; }

    /// <summary>
    /// สถานะ
    /// </summary>
    public TransactionStatus Status { get; set; } = TransactionStatus.Completed;

    /// <summary>
    /// หมายเหตุ
    /// </summary>
    public string? Notes { get; set; }

    /// <summary>
    /// รหัสพนักงานขาย
    /// </summary>
    public int? CashierId { get; set; }

    /// <summary>
    /// ชื่อพนักงานขาย
    /// </summary>
    [MaxLength(100)]
    public string? CashierName { get; set; }

    /// <summary>
    /// รหัส Session
    /// </summary>
    public int? SessionId { get; set; }

    /// <summary>
    /// รหัสอุปกรณ์
    /// </summary>
    [MaxLength(100)]
    public string? DeviceId { get; set; }

    /// <summary>
    /// คะแนนที่ได้รับ
    /// </summary>
    public int PointsEarned { get; set; }

    /// <summary>
    /// คะแนนที่ใช้
    /// </summary>
    public int PointsUsed { get; set; }

    /// <summary>
    /// สร้างใน Offline mode
    /// </summary>
    public bool IsOffline { get; set; }

    /// <summary>
    /// รายการสินค้า (ไม่เก็บใน database)
    /// </summary>
    [Ignore]
    public List<TransactionItem> Items { get; set; } = new();

    /// <summary>
    /// จำนวนสินค้าทั้งหมด
    /// </summary>
    [Ignore]
    public int TotalItems => Items.Sum(i => i.Quantity);
}

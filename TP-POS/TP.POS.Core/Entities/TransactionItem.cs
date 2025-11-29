using SQLite;

namespace TP.POS.Core.Entities;

/// <summary>
/// รายการสินค้าในใบเสร็จ
/// </summary>
[Table("transaction_items")]
public class TransactionItem : BaseEntity
{
    /// <summary>
    /// รหัส Transaction
    /// </summary>
    [Indexed]
    public int TransactionId { get; set; }

    /// <summary>
    /// UUID ของ Transaction (สำหรับ sync)
    /// </summary>
    [MaxLength(50)]
    [Indexed]
    public string TransactionUuid { get; set; } = string.Empty;

    /// <summary>
    /// รหัสสินค้า
    /// </summary>
    [Indexed]
    public int ProductId { get; set; }

    /// <summary>
    /// UUID ของสินค้า
    /// </summary>
    [MaxLength(50)]
    public string? ProductUuid { get; set; }

    /// <summary>
    /// รหัสสินค้า (SKU)
    /// </summary>
    [MaxLength(50)]
    public string Sku { get; set; } = string.Empty;

    /// <summary>
    /// บาร์โค้ด
    /// </summary>
    [MaxLength(50)]
    public string? Barcode { get; set; }

    /// <summary>
    /// ชื่อสินค้า (snapshot ณ เวลาขาย)
    /// </summary>
    [MaxLength(255)]
    public string ProductName { get; set; } = string.Empty;

    /// <summary>
    /// จำนวน
    /// </summary>
    public int Quantity { get; set; } = 1;

    /// <summary>
    /// ราคาต่อหน่วย (ราคาปกติ)
    /// </summary>
    public decimal UnitPrice { get; set; }

    /// <summary>
    /// ราคาต้นทุนต่อหน่วย
    /// </summary>
    public decimal CostPrice { get; set; }

    /// <summary>
    /// ส่วนลดต่อหน่วย (บาท)
    /// </summary>
    public decimal DiscountPerUnit { get; set; }

    /// <summary>
    /// ส่วนลดรวม (บาท)
    /// </summary>
    public decimal TotalDiscount { get; set; }

    /// <summary>
    /// ยอดรวมของรายการนี้
    /// </summary>
    public decimal LineTotal { get; set; }

    /// <summary>
    /// ภาษีของรายการนี้
    /// </summary>
    public decimal TaxAmount { get; set; }

    /// <summary>
    /// อัตราภาษี (%)
    /// </summary>
    public decimal TaxRate { get; set; } = 7;

    /// <summary>
    /// หมายเหตุ
    /// </summary>
    [MaxLength(500)]
    public string? Notes { get; set; }

    /// <summary>
    /// ราคาสุทธิต่อหน่วย
    /// </summary>
    [Ignore]
    public decimal NetUnitPrice => UnitPrice - DiscountPerUnit;

    /// <summary>
    /// กำไรต่อหน่วย
    /// </summary>
    [Ignore]
    public decimal ProfitPerUnit => NetUnitPrice - CostPrice;

    /// <summary>
    /// กำไรรวม
    /// </summary>
    [Ignore]
    public decimal TotalProfit => ProfitPerUnit * Quantity;

    /// <summary>
    /// คำนวณยอดรวม
    /// </summary>
    public void CalculateLineTotal()
    {
        TotalDiscount = DiscountPerUnit * Quantity;
        LineTotal = (UnitPrice * Quantity) - TotalDiscount;
        TaxAmount = LineTotal * (TaxRate / 100);
    }
}

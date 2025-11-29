using SQLite;
using TP.POS.Core.Enums;

namespace TP.POS.Core.Entities;

/// <summary>
/// การเคลื่อนไหวสต็อก
/// </summary>
[Table("stock_movements")]
public class StockMovement : BaseEntity
{
    /// <summary>
    /// รหัสสินค้า
    /// </summary>
    [Indexed]
    public int ProductId { get; set; }

    /// <summary>
    /// รหัสสินค้า (SKU)
    /// </summary>
    [MaxLength(50)]
    public string Sku { get; set; } = string.Empty;

    /// <summary>
    /// ชื่อสินค้า
    /// </summary>
    [MaxLength(255)]
    public string ProductName { get; set; } = string.Empty;

    /// <summary>
    /// ประเภทการเคลื่อนไหว
    /// </summary>
    public StockMovementType MovementType { get; set; }

    /// <summary>
    /// จำนวนที่เปลี่ยนแปลง (บวก = เพิ่ม, ลบ = ลด)
    /// </summary>
    public int Quantity { get; set; }

    /// <summary>
    /// สต็อกก่อนเปลี่ยนแปลง
    /// </summary>
    public int StockBefore { get; set; }

    /// <summary>
    /// สต็อกหลังเปลี่ยนแปลง
    /// </summary>
    public int StockAfter { get; set; }

    /// <summary>
    /// ราคาต่อหน่วย (กรณีรับเข้า)
    /// </summary>
    public decimal? UnitCost { get; set; }

    /// <summary>
    /// มูลค่ารวม
    /// </summary>
    public decimal? TotalValue { get; set; }

    /// <summary>
    /// เอกสารอ้างอิง (เลขที่ใบเสร็จ, PO, etc.)
    /// </summary>
    [MaxLength(100)]
    public string? ReferenceNumber { get; set; }

    /// <summary>
    /// ประเภทเอกสารอ้างอิง
    /// </summary>
    [MaxLength(50)]
    public string? ReferenceType { get; set; }

    /// <summary>
    /// เหตุผล
    /// </summary>
    [MaxLength(500)]
    public string? Reason { get; set; }

    /// <summary>
    /// หมายเหตุ
    /// </summary>
    public string? Notes { get; set; }

    /// <summary>
    /// ผู้ดำเนินการ
    /// </summary>
    public int? UserId { get; set; }

    /// <summary>
    /// ชื่อผู้ดำเนินการ
    /// </summary>
    [MaxLength(100)]
    public string? UserName { get; set; }

    /// <summary>
    /// วันที่เคลื่อนไหว
    /// </summary>
    [Indexed]
    public DateTime MovementDate { get; set; } = DateTime.Now;
}

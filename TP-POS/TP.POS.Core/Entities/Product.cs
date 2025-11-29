using SQLite;

namespace TP.POS.Core.Entities;

/// <summary>
/// สินค้าในระบบ POS
/// </summary>
[Table("products")]
public class Product : BaseEntity
{
    /// <summary>
    /// รหัสสินค้า (SKU)
    /// </summary>
    [MaxLength(50)]
    [Indexed]
    public string Sku { get; set; } = string.Empty;

    /// <summary>
    /// บาร์โค้ด
    /// </summary>
    [MaxLength(50)]
    [Indexed]
    public string? Barcode { get; set; }

    /// <summary>
    /// ชื่อสินค้า
    /// </summary>
    [MaxLength(255)]
    public string Name { get; set; } = string.Empty;

    /// <summary>
    /// รายละเอียดสินค้า
    /// </summary>
    public string? Description { get; set; }

    /// <summary>
    /// รหัสหมวดหมู่
    /// </summary>
    [Indexed]
    public int? CategoryId { get; set; }

    /// <summary>
    /// ชื่อหมวดหมู่ (denormalized สำหรับ offline)
    /// </summary>
    [MaxLength(100)]
    public string? CategoryName { get; set; }

    /// <summary>
    /// ราคาต้นทุน
    /// </summary>
    public decimal CostPrice { get; set; }

    /// <summary>
    /// ราคาขาย
    /// </summary>
    public decimal SalePrice { get; set; }

    /// <summary>
    /// จำนวนสต็อกปัจจุบัน
    /// </summary>
    public int StockQuantity { get; set; }

    /// <summary>
    /// จุดสั่งซื้อใหม่ (แจ้งเตือนเมื่อสต็อกต่ำ)
    /// </summary>
    public int ReorderLevel { get; set; } = 10;

    /// <summary>
    /// หน่วยนับ (ชิ้น, กล่อง, กก.)
    /// </summary>
    [MaxLength(20)]
    public string Unit { get; set; } = "ชิ้น";

    /// <summary>
    /// URL รูปภาพ
    /// </summary>
    [MaxLength(500)]
    public string? ImageUrl { get; set; }

    /// <summary>
    /// รูปภาพ (เก็บเป็น base64 สำหรับ offline)
    /// </summary>
    public string? ImageBase64 { get; set; }

    /// <summary>
    /// สถานะใช้งาน
    /// </summary>
    public bool IsActive { get; set; } = true;

    /// <summary>
    /// ติดตามสต็อกหรือไม่
    /// </summary>
    public bool TrackStock { get; set; } = true;

    /// <summary>
    /// อัตราภาษี (เช่น 7 = 7%)
    /// </summary>
    public decimal TaxRate { get; set; } = 7;

    /// <summary>
    /// คำนวณกำไรต่อชิ้น
    /// </summary>
    [Ignore]
    public decimal Profit => SalePrice - CostPrice;

    /// <summary>
    /// คำนวณ % กำไร
    /// </summary>
    [Ignore]
    public decimal ProfitMargin => CostPrice > 0 ? (Profit / CostPrice) * 100 : 0;
}

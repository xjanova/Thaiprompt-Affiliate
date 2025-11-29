using SQLite;

namespace TP.POS.Core.Entities;

/// <summary>
/// รายการสินค้าในตะกร้า (ใช้ระหว่างขาย)
/// </summary>
[Table("cart_items")]
public class CartItem : BaseEntity
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
    /// บาร์โค้ด
    /// </summary>
    [MaxLength(50)]
    public string? Barcode { get; set; }

    /// <summary>
    /// ชื่อสินค้า
    /// </summary>
    [MaxLength(255)]
    public string ProductName { get; set; } = string.Empty;

    /// <summary>
    /// รูปภาพ
    /// </summary>
    public string? ImageUrl { get; set; }

    /// <summary>
    /// จำนวน
    /// </summary>
    public int Quantity { get; set; } = 1;

    /// <summary>
    /// ราคาต่อหน่วย
    /// </summary>
    public decimal UnitPrice { get; set; }

    /// <summary>
    /// ราคาต้นทุน
    /// </summary>
    public decimal CostPrice { get; set; }

    /// <summary>
    /// ส่วนลดต่อหน่วย
    /// </summary>
    public decimal DiscountPerUnit { get; set; }

    /// <summary>
    /// อัตราภาษี
    /// </summary>
    public decimal TaxRate { get; set; } = 7;

    /// <summary>
    /// หมายเหตุ
    /// </summary>
    [MaxLength(500)]
    public string? Notes { get; set; }

    /// <summary>
    /// รหัส Session ปัจจุบัน
    /// </summary>
    public string? SessionToken { get; set; }

    /// <summary>
    /// ยอดรวมก่อนส่วนลด
    /// </summary>
    [Ignore]
    public decimal Subtotal => UnitPrice * Quantity;

    /// <summary>
    /// ส่วนลดรวม
    /// </summary>
    [Ignore]
    public decimal TotalDiscount => DiscountPerUnit * Quantity;

    /// <summary>
    /// ยอดรวมสุทธิ
    /// </summary>
    [Ignore]
    public decimal LineTotal => Subtotal - TotalDiscount;

    /// <summary>
    /// ภาษี
    /// </summary>
    [Ignore]
    public decimal TaxAmount => LineTotal * (TaxRate / 100);

    /// <summary>
    /// ราคาสุทธิต่อหน่วย
    /// </summary>
    [Ignore]
    public decimal NetUnitPrice => UnitPrice - DiscountPerUnit;

    /// <summary>
    /// สร้าง CartItem จาก Product
    /// </summary>
    public static CartItem FromProduct(Product product, int quantity = 1)
    {
        return new CartItem
        {
            ProductId = product.Id,
            Sku = product.Sku,
            Barcode = product.Barcode,
            ProductName = product.Name,
            ImageUrl = product.ImageUrl,
            Quantity = quantity,
            UnitPrice = product.SalePrice,
            CostPrice = product.CostPrice,
            TaxRate = product.TaxRate
        };
    }
}

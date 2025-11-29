using TP.POS.Core.Entities;
using TP.POS.Core.Enums;

namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับจัดการตะกร้าสินค้า
/// </summary>
public interface ICartService
{
    /// <summary>
    /// Event เมื่อตะกร้าเปลี่ยนแปลง
    /// </summary>
    event EventHandler<CartChangedEventArgs>? CartChanged;

    /// <summary>
    /// ดึงรายการในตะกร้าทั้งหมด
    /// </summary>
    Task<List<CartItem>> GetAllItemsAsync();

    /// <summary>
    /// เพิ่มสินค้าลงตะกร้า
    /// </summary>
    Task<CartItem> AddItemAsync(Product product, int quantity = 1);

    /// <summary>
    /// เพิ่มสินค้าลงตะกร้าด้วยบาร์โค้ด
    /// </summary>
    Task<CartItem?> AddByBarcodeAsync(string barcode, int quantity = 1);

    /// <summary>
    /// อัพเดทจำนวนสินค้า
    /// </summary>
    Task<CartItem?> UpdateQuantityAsync(int cartItemId, int quantity);

    /// <summary>
    /// เพิ่มจำนวนสินค้า
    /// </summary>
    Task<CartItem?> IncrementQuantityAsync(int cartItemId);

    /// <summary>
    /// ลดจำนวนสินค้า
    /// </summary>
    Task<CartItem?> DecrementQuantityAsync(int cartItemId);

    /// <summary>
    /// ลบสินค้าออกจากตะกร้า
    /// </summary>
    Task<bool> RemoveItemAsync(int cartItemId);

    /// <summary>
    /// ล้างตะกร้า
    /// </summary>
    Task ClearAsync();

    /// <summary>
    /// ใส่ส่วนลดทั้งตะกร้า (%)
    /// </summary>
    Task ApplyDiscountPercentAsync(decimal percent);

    /// <summary>
    /// ใส่ส่วนลดทั้งตะกร้า (บาท)
    /// </summary>
    Task ApplyDiscountAmountAsync(decimal amount);

    /// <summary>
    /// ใส่ส่วนลดรายการ
    /// </summary>
    Task ApplyItemDiscountAsync(int cartItemId, decimal discountPerUnit);

    /// <summary>
    /// ดึงยอดรวม
    /// </summary>
    Task<CartSummary> GetSummaryAsync();

    /// <summary>
    /// จำนวนรายการในตะกร้า
    /// </summary>
    Task<int> GetItemCountAsync();

    /// <summary>
    /// ชำระเงินและสร้าง Transaction
    /// </summary>
    Task<Transaction> CheckoutAsync(PaymentMethod paymentMethod, decimal receivedAmount, Customer? customer = null, string? paymentReference = null, string? notes = null);

    /// <summary>
    /// พักใบเสร็จ (Hold)
    /// </summary>
    Task<string> HoldAsync(string? notes = null);

    /// <summary>
    /// ดึงใบเสร็จที่พักไว้
    /// </summary>
    Task<List<HeldCart>> GetHeldCartsAsync();

    /// <summary>
    /// เรียกใบเสร็จที่พักไว้กลับมา
    /// </summary>
    Task<bool> RecallHeldCartAsync(string holdId);

    /// <summary>
    /// ลบใบเสร็จที่พักไว้
    /// </summary>
    Task<bool> DeleteHeldCartAsync(string holdId);
}

/// <summary>
/// ข้อมูลสรุปตะกร้า
/// </summary>
public class CartSummary
{
    /// <summary>
    /// จำนวนรายการ
    /// </summary>
    public int ItemCount { get; set; }

    /// <summary>
    /// จำนวนสินค้าทั้งหมด
    /// </summary>
    public int TotalQuantity { get; set; }

    /// <summary>
    /// ยอดรวมก่อนส่วนลด
    /// </summary>
    public decimal Subtotal { get; set; }

    /// <summary>
    /// ส่วนลดรวม
    /// </summary>
    public decimal DiscountAmount { get; set; }

    /// <summary>
    /// ส่วนลด % ทั้งตะกร้า
    /// </summary>
    public decimal DiscountPercent { get; set; }

    /// <summary>
    /// ภาษี
    /// </summary>
    public decimal TaxAmount { get; set; }

    /// <summary>
    /// ยอดสุทธิ
    /// </summary>
    public decimal TotalAmount { get; set; }
}

/// <summary>
/// Event args เมื่อตะกร้าเปลี่ยน
/// </summary>
public class CartChangedEventArgs : EventArgs
{
    public CartSummary Summary { get; set; } = new();
    public string ChangeType { get; set; } = "update";
}

/// <summary>
/// ตะกร้าที่พักไว้
/// </summary>
public class HeldCart
{
    public string HoldId { get; set; } = string.Empty;
    public List<CartItem> Items { get; set; } = new();
    public CartSummary Summary { get; set; } = new();
    public string? Notes { get; set; }
    public DateTime HeldAt { get; set; }
}

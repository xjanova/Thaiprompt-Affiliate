using SQLite;

namespace TP.POS.Core.Entities;

/// <summary>
/// ลูกค้า
/// </summary>
[Table("customers")]
public class Customer : BaseEntity
{
    /// <summary>
    /// รหัสลูกค้า
    /// </summary>
    [MaxLength(50)]
    [Indexed]
    public string? CustomerCode { get; set; }

    /// <summary>
    /// ชื่อ
    /// </summary>
    [MaxLength(100)]
    public string FirstName { get; set; } = string.Empty;

    /// <summary>
    /// นามสกุล
    /// </summary>
    [MaxLength(100)]
    public string? LastName { get; set; }

    /// <summary>
    /// ชื่อเต็ม
    /// </summary>
    [Ignore]
    public string FullName => string.IsNullOrEmpty(LastName)
        ? FirstName
        : $"{FirstName} {LastName}";

    /// <summary>
    /// เบอร์โทรศัพท์
    /// </summary>
    [MaxLength(20)]
    [Indexed]
    public string? Phone { get; set; }

    /// <summary>
    /// อีเมล
    /// </summary>
    [MaxLength(100)]
    public string? Email { get; set; }

    /// <summary>
    /// LINE ID
    /// </summary>
    [MaxLength(100)]
    public string? LineId { get; set; }

    /// <summary>
    /// ที่อยู่
    /// </summary>
    [MaxLength(500)]
    public string? Address { get; set; }

    /// <summary>
    /// เลขประจำตัวผู้เสียภาษี
    /// </summary>
    [MaxLength(20)]
    public string? TaxId { get; set; }

    /// <summary>
    /// คะแนนสะสม
    /// </summary>
    public int LoyaltyPoints { get; set; }

    /// <summary>
    /// ยอดซื้อสะสม
    /// </summary>
    public decimal TotalPurchases { get; set; }

    /// <summary>
    /// จำนวนครั้งที่ซื้อ
    /// </summary>
    public int PurchaseCount { get; set; }

    /// <summary>
    /// วันที่ซื้อครั้งล่าสุด
    /// </summary>
    public DateTime? LastPurchaseDate { get; set; }

    /// <summary>
    /// กลุ่มลูกค้า (VIP, Regular, etc.)
    /// </summary>
    [MaxLength(50)]
    public string CustomerGroup { get; set; } = "Regular";

    /// <summary>
    /// ส่วนลด % ประจำ
    /// </summary>
    public decimal DiscountPercent { get; set; }

    /// <summary>
    /// หมายเหตุ
    /// </summary>
    public string? Notes { get; set; }

    /// <summary>
    /// สถานะใช้งาน
    /// </summary>
    public bool IsActive { get; set; } = true;
}

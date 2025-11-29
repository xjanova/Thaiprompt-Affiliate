using SQLite;

namespace TP.POS.Core.Entities;

/// <summary>
/// หมวดหมู่สินค้า
/// </summary>
[Table("categories")]
public class Category : BaseEntity
{
    /// <summary>
    /// ชื่อหมวดหมู่
    /// </summary>
    [MaxLength(100)]
    [Indexed]
    public string Name { get; set; } = string.Empty;

    /// <summary>
    /// คำอธิบาย
    /// </summary>
    [MaxLength(500)]
    public string? Description { get; set; }

    /// <summary>
    /// รหัสหมวดหมู่แม่ (สำหรับ subcategory)
    /// </summary>
    public int? ParentId { get; set; }

    /// <summary>
    /// ไอคอน (emoji หรือ icon name)
    /// </summary>
    [MaxLength(50)]
    public string? Icon { get; set; }

    /// <summary>
    /// สี (hex color)
    /// </summary>
    [MaxLength(20)]
    public string Color { get; set; } = "#22C55E";

    /// <summary>
    /// ลำดับการแสดงผล
    /// </summary>
    public int SortOrder { get; set; }

    /// <summary>
    /// สถานะใช้งาน
    /// </summary>
    public bool IsActive { get; set; } = true;
}

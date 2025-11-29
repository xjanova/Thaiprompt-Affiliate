namespace TP.POS.Core.Enums;

/// <summary>
/// ประเภทการเคลื่อนไหวสต็อก
/// </summary>
public enum StockMovementType
{
    /// <summary>
    /// รับเข้าสต็อก (ซื้อเข้า)
    /// </summary>
    StockIn = 1,

    /// <summary>
    /// ขายออก
    /// </summary>
    SaleOut = 2,

    /// <summary>
    /// ปรับเพิ่ม (นับได้มากกว่าระบบ)
    /// </summary>
    AdjustmentIn = 3,

    /// <summary>
    /// ปรับลด (นับได้น้อยกว่าระบบ)
    /// </summary>
    AdjustmentOut = 4,

    /// <summary>
    /// โอนเข้า (จากสาขาอื่น)
    /// </summary>
    TransferIn = 5,

    /// <summary>
    /// โอนออก (ไปสาขาอื่น)
    /// </summary>
    TransferOut = 6,

    /// <summary>
    /// คืนสินค้า (รับคืนจากลูกค้า)
    /// </summary>
    Return = 7,

    /// <summary>
    /// เสียหาย/หมดอายุ
    /// </summary>
    Damage = 8,

    /// <summary>
    /// สินค้าสูญหาย
    /// </summary>
    Lost = 9,

    /// <summary>
    /// สินค้าตัวอย่าง/ทดลอง
    /// </summary>
    Sample = 10,

    /// <summary>
    /// ตั้งต้นสต็อก
    /// </summary>
    Initial = 11
}

/// <summary>
/// Extension methods สำหรับ StockMovementType
/// </summary>
public static class StockMovementTypeExtensions
{
    /// <summary>
    /// แปลงเป็นชื่อภาษาไทย
    /// </summary>
    public static string ToThaiName(this StockMovementType type)
    {
        return type switch
        {
            StockMovementType.StockIn => "รับเข้าสต็อก",
            StockMovementType.SaleOut => "ขายออก",
            StockMovementType.AdjustmentIn => "ปรับเพิ่ม",
            StockMovementType.AdjustmentOut => "ปรับลด",
            StockMovementType.TransferIn => "โอนเข้า",
            StockMovementType.TransferOut => "โอนออก",
            StockMovementType.Return => "รับคืน",
            StockMovementType.Damage => "เสียหาย/หมดอายุ",
            StockMovementType.Lost => "สูญหาย",
            StockMovementType.Sample => "ตัวอย่าง",
            StockMovementType.Initial => "ตั้งต้นสต็อก",
            _ => "ไม่ระบุ"
        };
    }

    /// <summary>
    /// เป็นการเพิ่มสต็อกหรือไม่
    /// </summary>
    public static bool IsIncrease(this StockMovementType type)
    {
        return type switch
        {
            StockMovementType.StockIn => true,
            StockMovementType.AdjustmentIn => true,
            StockMovementType.TransferIn => true,
            StockMovementType.Return => true,
            StockMovementType.Initial => true,
            _ => false
        };
    }

    /// <summary>
    /// ดึงสี
    /// </summary>
    public static string GetColor(this StockMovementType type)
    {
        return type.IsIncrease() ? "#22C55E" : "#EF4444";
    }

    /// <summary>
    /// ดึงไอคอน
    /// </summary>
    public static string GetIcon(this StockMovementType type)
    {
        return type switch
        {
            StockMovementType.StockIn => "📥",
            StockMovementType.SaleOut => "🛒",
            StockMovementType.AdjustmentIn => "➕",
            StockMovementType.AdjustmentOut => "➖",
            StockMovementType.TransferIn => "📦➡️",
            StockMovementType.TransferOut => "➡️📦",
            StockMovementType.Return => "↩️",
            StockMovementType.Damage => "💔",
            StockMovementType.Lost => "❓",
            StockMovementType.Sample => "🎁",
            StockMovementType.Initial => "🏁",
            _ => "📋"
        };
    }
}

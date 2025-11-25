namespace ThaipromptAffiliate.Models;

/// <summary>
/// โมเดลสำหรับรายการ Hub บนหน้า Hub Selection
///
/// แต่ละ Hub Item แทน 1 บริการหลักของแอพ
/// เช่น ช้อปปิ้ง, กระเป๋าเงิน, ลงทุน, MLM ฯลฯ
/// </summary>
public class HubItem
{
    /// <summary>
    /// ID เฉพาะของ Hub (ใช้สำหรับ navigation)
    /// เช่น "shopping", "wallet", "invest"
    /// </summary>
    public string Id { get; set; } = string.Empty;

    /// <summary>
    /// ไอคอน Emoji แสดงบน Card
    /// เช่น "🛒", "💰", "📈"
    /// </summary>
    public string Icon { get; set; } = "📦";

    /// <summary>
    /// ชื่อบริการ (ภาษาไทย)
    /// เช่น "ช้อปปิ้ง & บริการ"
    /// </summary>
    public string Title { get; set; } = string.Empty;

    /// <summary>
    /// คำอธิบายสั้นๆ ของบริการ
    /// เช่น "สั่งอาหาร, จองโรงแรม, หมอนวด"
    /// </summary>
    public string Subtitle { get; set; } = string.Empty;

    /// <summary>
    /// Route สำหรับ navigation
    /// เช่น "shopping", "wallet"
    /// </summary>
    public string Route { get; set; } = string.Empty;

    /// <summary>
    /// สี Gradient เริ่มต้น (Hex)
    /// เช่น "#6366F1"
    /// </summary>
    public string GradientStart { get; set; } = "#6366F1";

    /// <summary>
    /// สี Gradient สิ้นสุด (Hex)
    /// เช่น "#8B5CF6"
    /// </summary>
    public string GradientEnd { get; set; } = "#8B5CF6";

    /// <summary>
    /// แสดง Badge หรือไม่
    /// </summary>
    public bool HasBadge { get; set; } = false;

    /// <summary>
    /// ข้อความใน Badge
    /// เช่น "NEW", "HOT"
    /// </summary>
    public string BadgeText { get; set; } = string.Empty;

    /// <summary>
    /// ลำดับการแสดงผล
    /// </summary>
    public int SortOrder { get; set; } = 0;

    /// <summary>
    /// Hub นี้ต้อง login ก่อนใช้งานหรือไม่
    /// </summary>
    public bool RequiresLogin { get; set; } = false;

    /// <summary>
    /// Hub นี้เปิดให้ใช้งานหรือไม่
    /// </summary>
    public bool IsEnabled { get; set; } = true;

    /// <summary>
    /// คำอธิบายเต็มๆ ของบริการ (สำหรับหน้ารายละเอียด)
    /// </summary>
    public string Description { get; set; } = string.Empty;

    /// <summary>
    /// รายการฟีเจอร์ย่อยของบริการ
    /// </summary>
    public List<string> Features { get; set; } = new();
}

/// <summary>
/// Hub Category - หมวดหมู่ของ Hub
/// </summary>
public enum HubCategory
{
    /// <summary>บริการทั่วไป</summary>
    Services,

    /// <summary>การเงิน</summary>
    Finance,

    /// <summary>การลงทุน</summary>
    Investment,

    /// <summary>การตลาด</summary>
    Marketing,

    /// <summary>งาน/รายได้</summary>
    Work,

    /// <summary>เทคโนโลยี</summary>
    Technology,

    /// <summary>การศึกษา</summary>
    Education,

    /// <summary>บันเทิง</summary>
    Entertainment
}

/// <summary>
/// Extension methods สำหรับ HubItem
/// </summary>
public static class HubItemExtensions
{
    /// <summary>
    /// ดึง HubCategory จาก HubItem Id
    /// </summary>
    public static HubCategory GetCategory(this HubItem hub)
    {
        return hub.Id switch
        {
            "shopping" => HubCategory.Services,
            "wallet" => HubCategory.Finance,
            "invest" => HubCategory.Investment,
            "mlm" => HubCategory.Marketing,
            "rider" => HubCategory.Work,
            "aibot" => HubCategory.Technology,
            "academy" => HubCategory.Education,
            "gaming" => HubCategory.Entertainment,
            _ => HubCategory.Services
        };
    }

    /// <summary>
    /// ตรวจสอบว่า Hub ต้อง login หรือไม่
    /// </summary>
    public static bool NeedsAuthentication(this HubItem hub)
    {
        // Hub ที่ต้อง login: wallet, mlm, rider, invest
        return hub.Id is "wallet" or "mlm" or "rider" or "invest";
    }
}

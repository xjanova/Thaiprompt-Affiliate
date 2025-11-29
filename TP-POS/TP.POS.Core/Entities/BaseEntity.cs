namespace TP.POS.Core.Entities;

/// <summary>
/// คลาสหลักสำหรับ Entity ทุกตัว
/// มี Id และ timestamps สำหรับติดตามการสร้าง/แก้ไข
/// </summary>
public abstract class BaseEntity
{
    /// <summary>
    /// รหัสหลัก (Primary Key) - ใช้สำหรับ SQLite
    /// </summary>
    public int Id { get; set; }

    /// <summary>
    /// รหัสจาก Server (ถ้า sync แล้ว)
    /// </summary>
    public int? ServerId { get; set; }

    /// <summary>
    /// UUID สำหรับ sync ระหว่าง devices
    /// </summary>
    public string Uuid { get; set; } = Guid.NewGuid().ToString();

    /// <summary>
    /// วันที่สร้าง
    /// </summary>
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;

    /// <summary>
    /// วันที่แก้ไขล่าสุด
    /// </summary>
    public DateTime UpdatedAt { get; set; } = DateTime.UtcNow;

    /// <summary>
    /// สถานะการ sync กับ server
    /// </summary>
    public bool IsSynced { get; set; }

    /// <summary>
    /// วันที่ sync ล่าสุด
    /// </summary>
    public DateTime? LastSyncedAt { get; set; }
}

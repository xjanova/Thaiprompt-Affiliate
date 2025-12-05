namespace ThaipromptAffiliateApp.Services;

/// <summary>
/// Interface สำหรับจัดการ Push Notification
/// รองรับการลงทะเบียน, รับ, และจัดการการแจ้งเตือน
/// </summary>
public interface INotificationService
{
    /// <summary>
    /// Event เมื่อได้รับ Push Notification
    /// </summary>
    event EventHandler<NotificationEventArgs>? NotificationReceived;

    /// <summary>
    /// Event เมื่อผู้ใช้กดที่ Notification
    /// </summary>
    event EventHandler<NotificationEventArgs>? NotificationTapped;

    /// <summary>
    /// ลงทะเบียนเพื่อรับ Push Notifications
    /// </summary>
    /// <returns>Device Token สำหรับ Push</returns>
    Task<string?> RegisterForPushNotificationsAsync();

    /// <summary>
    /// ยกเลิกการลงทะเบียน Push Notifications
    /// </summary>
    Task UnregisterPushNotificationsAsync();

    /// <summary>
    /// ตรวจสอบว่าได้รับอนุญาตให้แจ้งเตือนหรือไม่
    /// </summary>
    Task<bool> CheckNotificationPermissionAsync();

    /// <summary>
    /// ขอสิทธิ์การแจ้งเตือน
    /// </summary>
    Task<bool> RequestNotificationPermissionAsync();

    /// <summary>
    /// แสดง Local Notification
    /// </summary>
    /// <param name="title">หัวข้อ</param>
    /// <param name="body">เนื้อหา</param>
    /// <param name="data">ข้อมูลเพิ่มเติม</param>
    Task ShowLocalNotificationAsync(string title, string body, Dictionary<string, string>? data = null);

    /// <summary>
    /// ยกเลิก Notification ทั้งหมด
    /// </summary>
    Task CancelAllNotificationsAsync();

    /// <summary>
    /// ดึง Device Token ปัจจุบัน
    /// </summary>
    string? GetDeviceToken();

    /// <summary>
    /// อัพเดท Badge Count
    /// </summary>
    /// <param name="count">จำนวน Badge</param>
    Task UpdateBadgeCountAsync(int count);
}

/// <summary>
/// EventArgs สำหรับ Notification Events
/// </summary>
public class NotificationEventArgs : EventArgs
{
    /// <summary>
    /// หัวข้อ Notification
    /// </summary>
    public string Title { get; set; } = string.Empty;

    /// <summary>
    /// เนื้อหา Notification
    /// </summary>
    public string Body { get; set; } = string.Empty;

    /// <summary>
    /// ข้อมูลเพิ่มเติม
    /// </summary>
    public Dictionary<string, string> Data { get; set; } = new();

    /// <summary>
    /// ประเภท Notification
    /// </summary>
    public NotificationType Type { get; set; } = NotificationType.General;

    /// <summary>
    /// วันเวลาที่ได้รับ
    /// </summary>
    public DateTime ReceivedAt { get; set; } = DateTime.Now;
}

/// <summary>
/// ประเภทของ Notification
/// </summary>
public enum NotificationType
{
    /// <summary>
    /// ทั่วไป
    /// </summary>
    General,

    /// <summary>
    /// อัพเดทคำสั่งซื้อ
    /// </summary>
    OrderUpdate,

    /// <summary>
    /// ค่าคอมมิชชั่น
    /// </summary>
    Commission,

    /// <summary>
    /// โปรโมชั่น
    /// </summary>
    Promotion,

    /// <summary>
    /// ข่าวสาร
    /// </summary>
    News,

    /// <summary>
    /// ระบบ
    /// </summary>
    System
}

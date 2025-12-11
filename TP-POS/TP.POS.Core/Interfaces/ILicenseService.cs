namespace TP.POS.Core.Interfaces;

/// <summary>
/// สถานะการลงทะเบียน License
/// </summary>
public enum LicenseStatus
{
    /// <summary>
    /// ยังไม่ได้ลงทะเบียน
    /// </summary>
    NotRegistered,

    /// <summary>
    /// รอการยืนยันจาก Server
    /// </summary>
    Pending,

    /// <summary>
    /// ลงทะเบียนสำเร็จ - ใช้งานได้
    /// </summary>
    Activated,

    /// <summary>
    /// License ไม่ถูกต้อง
    /// </summary>
    Invalid,

    /// <summary>
    /// License หมดอายุ
    /// </summary>
    Expired,

    /// <summary>
    /// ถูกระงับการใช้งาน
    /// </summary>
    Suspended
}

/// <summary>
/// ข้อมูล License ของเครื่อง POS
/// </summary>
public class LicenseInfo
{
    /// <summary>
    /// Product Key (รหัสประจำเครื่อง)
    /// </summary>
    public string ProductKey { get; set; } = string.Empty;

    /// <summary>
    /// API Key จากเว็บหลัก
    /// </summary>
    public string ApiKey { get; set; } = string.Empty;

    /// <summary>
    /// Device ID (Hardware fingerprint)
    /// </summary>
    public string DeviceId { get; set; } = string.Empty;

    /// <summary>
    /// ชื่อร้าน
    /// </summary>
    public string ShopName { get; set; } = string.Empty;

    /// <summary>
    /// รหัสร้าน (จาก Backend)
    /// </summary>
    public int ShopId { get; set; }

    /// <summary>
    /// รหัส POS Terminal
    /// </summary>
    public int PosTerminalId { get; set; }

    /// <summary>
    /// สถานะ License
    /// </summary>
    public LicenseStatus Status { get; set; } = LicenseStatus.NotRegistered;

    /// <summary>
    /// วันที่ลงทะเบียน
    /// </summary>
    public DateTime? RegisteredAt { get; set; }

    /// <summary>
    /// วันที่หมดอายุ (ถ้ามี)
    /// </summary>
    public DateTime? ExpiresAt { get; set; }

    /// <summary>
    /// Server URL หลัก
    /// </summary>
    public string ServerUrl { get; set; } = string.Empty;

    /// <summary>
    /// ข้อความ Error (ถ้ามี)
    /// </summary>
    public string? ErrorMessage { get; set; }

    /// <summary>
    /// License ใช้งานได้หรือไม่
    /// </summary>
    public bool IsValid => Status == LicenseStatus.Activated &&
                          (ExpiresAt == null || ExpiresAt > DateTime.UtcNow);
}

/// <summary>
/// Interface สำหรับจัดการ License และการลงทะเบียน POS
/// </summary>
public interface ILicenseService
{
    /// <summary>
    /// ข้อมูล License ปัจจุบัน
    /// </summary>
    LicenseInfo? CurrentLicense { get; }

    /// <summary>
    /// สถานะ License ปัจจุบัน
    /// </summary>
    LicenseStatus Status { get; }

    /// <summary>
    /// License ใช้งานได้หรือไม่
    /// </summary>
    bool IsActivated { get; }

    /// <summary>
    /// สร้าง Product Key ใหม่สำหรับเครื่องนี้
    /// </summary>
    /// <returns>Product Key ที่สร้างขึ้น</returns>
    string GenerateProductKey();

    /// <summary>
    /// ตรวจสอบ Product Key ว่าถูกต้องหรือไม่
    /// </summary>
    /// <param name="productKey">Product Key ที่ต้องการตรวจสอบ</param>
    /// <returns>true ถ้าถูกต้อง</returns>
    bool ValidateProductKey(string productKey);

    /// <summary>
    /// ลงทะเบียนเครื่อง POS กับ Server
    /// </summary>
    /// <param name="serverUrl">URL ของ Server หลัก</param>
    /// <param name="apiKey">API Key จาก Server</param>
    /// <returns>ผลการลงทะเบียน</returns>
    Task<LicenseActivationResult> RegisterAsync(string serverUrl, string apiKey);

    /// <summary>
    /// ตรวจสอบสถานะ License กับ Server
    /// </summary>
    /// <returns>true ถ้า License ยังใช้งานได้</returns>
    Task<bool> ValidateLicenseAsync();

    /// <summary>
    /// โหลด License ที่บันทึกไว้
    /// </summary>
    /// <returns>true ถ้ามี License ที่บันทึกไว้</returns>
    Task<bool> LoadSavedLicenseAsync();

    /// <summary>
    /// ล้าง License (สำหรับ Admin เท่านั้น)
    /// </summary>
    Task ClearLicenseAsync();

    /// <summary>
    /// ดึง Device ID ของเครื่องนี้
    /// </summary>
    /// <returns>Device ID</returns>
    string GetDeviceId();
}

/// <summary>
/// ผลการ Activate License
/// </summary>
public class LicenseActivationResult
{
    /// <summary>
    /// สำเร็จหรือไม่
    /// </summary>
    public bool Success { get; set; }

    /// <summary>
    /// ข้อความ
    /// </summary>
    public string Message { get; set; } = string.Empty;

    /// <summary>
    /// ข้อมูล License (ถ้าสำเร็จ)
    /// </summary>
    public LicenseInfo? License { get; set; }

    /// <summary>
    /// Error Code (ถ้าไม่สำเร็จ)
    /// </summary>
    public string? ErrorCode { get; set; }
}

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
    /// ลงทะเบียนแล้ว รอกรอก API Key จาก Admin
    /// </summary>
    PendingApiKey,

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
    /// ถูกระงับชั่วคราว
    /// </summary>
    Suspended,

    /// <summary>
    /// ถูกบล็อก
    /// </summary>
    Blocked
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
    /// API Key จาก Admin (ไม่ส่งกลับอัตโนมัติ)
    /// </summary>
    public string? ApiKey { get; set; }

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
    /// วันที่ยืนยัน API Key
    /// </summary>
    public DateTime? VerifiedAt { get; set; }

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
    /// ข้อความเมื่อถูกบล็อก
    /// </summary>
    public string? BlockedMessage { get; set; }

    /// <summary>
    /// เหตุผลที่ถูกบล็อก
    /// </summary>
    public string? BlockedReason { get; set; }

    /// <summary>
    /// License ใช้งานได้หรือไม่
    /// </summary>
    public bool IsValid => Status == LicenseStatus.Activated &&
                          (ExpiresAt == null || ExpiresAt > DateTime.UtcNow);
}

/// <summary>
/// Interface สำหรับจัดการ License และการลงทะเบียน POS
///
/// ระบบ 2 กุญแจ:
/// 1. Product Key: สร้างที่ POS device อัตโนมัติ
/// 2. API Key: Admin ให้กับลูกค้า
///
/// Flow (Single-step activation):
/// 1. ActivateAsync() - ลงทะเบียนและยืนยันด้วย Server URL + Shop Code + API Key
/// 2. ValidateLicenseAsync() - ตรวจสอบสถานะก่อน sync
/// 3. CheckStatusAsync() - ตรวจสอบสถานะเป็นระยะ (heartbeat)
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
    /// ต้องกรอก API Key หรือไม่
    /// </summary>
    bool NeedsApiKey { get; }

    /// <summary>
    /// API Key ถูกบล็อกหรือไม่
    /// </summary>
    bool IsBlocked { get; }

    /// <summary>
    /// ข้อความแจ้งเตือนเมื่อถูกบล็อก
    /// </summary>
    string? BlockedMessage { get; }

    /// <summary>
    /// Event เมื่อ API Key ถูกบล็อก
    /// </summary>
    event EventHandler<BlockedEventArgs>? OnApiKeyBlocked;

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
    /// Single-step activation: ลงทะเบียนและยืนยัน POS กับ Server
    /// รวมขั้นตอนทั้งหมดเป็นขั้นตอนเดียว
    /// </summary>
    /// <param name="serverUrl">URL ของ Server หลัก (เช่น https://api.example.com)</param>
    /// <param name="shopCode">รหัสร้านค้า</param>
    /// <param name="apiKey">API Key จาก Admin</param>
    /// <returns>ผลการ Activate</returns>
    Task<LicenseActivationResult> ActivateAsync(string serverUrl, string shopCode, string apiKey);

    /// <summary>
    /// (Legacy) ขั้นตอนที่ 1: ลงทะเบียน POS กับ Server
    /// ⚠️ สำคัญ: Server ไม่ส่ง API Key กลับ!
    /// </summary>
    /// <param name="serverUrl">URL ของ Server หลัก</param>
    /// <param name="shopCode">รหัสร้านค้า</param>
    /// <returns>ผลการลงทะเบียน</returns>
    Task<LicenseActivationResult> RegisterAsync(string serverUrl, string shopCode);

    /// <summary>
    /// (Legacy) ขั้นตอนที่ 2: ยืนยัน POS ด้วย API Key ที่ได้จาก Admin
    /// </summary>
    /// <param name="apiKey">API Key จาก Admin</param>
    /// <returns>ผลการยืนยัน</returns>
    Task<LicenseActivationResult> VerifyApiKeyAsync(string apiKey);

    /// <summary>
    /// ตรวจสอบสถานะ License กับ Server
    /// </summary>
    /// <returns>true ถ้า License ยังใช้งานได้</returns>
    Task<bool> ValidateLicenseAsync();

    /// <summary>
    /// ตรวจสอบสถานะเป็นระยะ (สำหรับแจ้งเตือนเมื่อถูกบล็อก)
    /// </summary>
    /// <returns>ผลการตรวจสอบสถานะ</returns>
    Task<StatusCheckResult> CheckStatusAsync();

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

    /// <summary>
    /// ตั้งค่า Secret Key สำหรับเข้ารหัส/ถอดรหัส
    /// ⚠️ ต้องตั้งค่าให้ตรงกับ Backend
    /// </summary>
    /// <param name="secretKey">Secret Key</param>
    void SetSecretKey(string secretKey);

    /// <summary>
    /// ตรวจสอบว่า Secret Key ถูกตั้งค่าแล้วหรือยัง
    /// </summary>
    bool HasSecretKey { get; }
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

    /// <summary>
    /// ต้องกรอก API Key หรือไม่
    /// </summary>
    public bool NeedsApiKey { get; set; }

    /// <summary>
    /// API Key ถูกบล็อกหรือไม่
    /// </summary>
    public bool IsBlocked { get; set; }

    /// <summary>
    /// เหตุผลที่ถูกบล็อก
    /// </summary>
    public string? BlockedReason { get; set; }
}

/// <summary>
/// ผลการตรวจสอบสถานะ
/// </summary>
public class StatusCheckResult
{
    /// <summary>
    /// สำเร็จหรือไม่
    /// </summary>
    public bool Success { get; set; }

    /// <summary>
    /// มีปัญหาหรือไม่
    /// </summary>
    public bool HasIssue { get; set; }

    /// <summary>
    /// ข้อความปัญหา
    /// </summary>
    public string? IssueMessage { get; set; }

    /// <summary>
    /// ต้องติดต่อ Admin หรือไม่
    /// </summary>
    public bool ContactAdmin { get; set; }
}

/// <summary>
/// Event Arguments เมื่อ API Key ถูกบล็อก
/// </summary>
public class BlockedEventArgs : EventArgs
{
    /// <summary>
    /// ข้อความแจ้งเตือน
    /// </summary>
    public string? Message { get; set; }

    /// <summary>
    /// เหตุผลที่ถูกบล็อก
    /// </summary>
    public string? Reason { get; set; }
}

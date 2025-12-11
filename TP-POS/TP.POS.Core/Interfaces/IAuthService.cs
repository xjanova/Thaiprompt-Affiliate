using TP.POS.Core.Entities;

namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับจัดการ Authentication
/// </summary>
public interface IAuthService
{
    /// <summary>
    /// ข้อมูล User ที่ Login อยู่
    /// </summary>
    UserSession? CurrentUser { get; }

    /// <summary>
    /// ตรวจสอบว่า Login อยู่หรือไม่
    /// </summary>
    bool IsLoggedIn { get; }

    /// <summary>
    /// Login เข้าระบบ
    /// </summary>
    Task<LoginResult> LoginAsync(string email, string password);

    /// <summary>
    /// Logout ออกจากระบบ
    /// </summary>
    Task LogoutAsync();

    /// <summary>
    /// ตรวจสอบ Session ที่เก็บไว้
    /// </summary>
    Task<bool> CheckSavedSessionAsync();

    /// <summary>
    /// Event เมื่อสถานะ Login เปลี่ยน
    /// </summary>
    event EventHandler<AuthStateChangedEventArgs>? AuthStateChanged;
}

/// <summary>
/// ผลลัพธ์การ Login
/// </summary>
public class LoginResult
{
    public bool Success { get; set; }
    public string? Message { get; set; }
    public UserSession? User { get; set; }
}

/// <summary>
/// ข้อมูล Session ของ User
/// </summary>
public class UserSession
{
    public int UserId { get; set; }
    public string Email { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Token { get; set; }

    /// <summary>
    /// ID ร้านค้าที่ User เป็นเจ้าของ
    /// </summary>
    public int? ShopId { get; set; }

    /// <summary>
    /// ชื่อร้านค้า
    /// </summary>
    public string? ShopName { get; set; }

    /// <summary>
    /// เป็น Admin หรือไม่
    /// </summary>
    public bool IsAdmin { get; set; }

    /// <summary>
    /// Role ของ User
    /// </summary>
    public string Role { get; set; } = "user";
}

/// <summary>
/// Event args สำหรับ Auth State Changed
/// </summary>
public class AuthStateChangedEventArgs : EventArgs
{
    public bool IsLoggedIn { get; set; }
    public UserSession? User { get; set; }
}

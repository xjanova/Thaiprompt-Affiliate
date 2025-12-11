using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Api;

namespace TP.POS.App.Services;

/// <summary>
/// Service จัดการ Authentication
/// รองรับการ Login ตาม Shop (ร้านค้า)
/// - User ปกติ: เข้าได้เฉพาะร้านตัวเอง
/// - Admin: เข้าได้เฉพาะร้านทางการ
/// </summary>
public class AuthService : IAuthService
{
    private readonly TpAffiliateApiClient _apiClient;
    private UserSession? _currentUser;

    // Keys สำหรับเก็บข้อมูลใน SecureStorage
    private const string TokenKey = "auth_token";
    private const string UserIdKey = "user_id";
    private const string UserEmailKey = "user_email";
    private const string UserNameKey = "user_name";
    private const string ShopIdKey = "shop_id";
    private const string ShopNameKey = "shop_name";
    private const string IsAdminKey = "is_admin";
    private const string RoleKey = "user_role";

    public UserSession? CurrentUser => _currentUser;
    public bool IsLoggedIn => _currentUser != null && !string.IsNullOrEmpty(_currentUser.Token);

    public event EventHandler<AuthStateChangedEventArgs>? AuthStateChanged;

    public AuthService(TpAffiliateApiClient apiClient)
    {
        _apiClient = apiClient;
    }

    /// <summary>
    /// Login เข้าระบบ
    /// </summary>
    public async Task<LoginResult> LoginAsync(string email, string password)
    {
        try
        {
            // เรียก API Login
            var response = await _apiClient.LoginAsync(email, password);

            if (response == null || string.IsNullOrEmpty(response.Token))
            {
                return new LoginResult
                {
                    Success = false,
                    Message = "อีเมลหรือรหัสผ่านไม่ถูกต้อง"
                };
            }

            // ดึงข้อมูล User เพิ่มเติม (Shop info)
            var userInfo = await GetUserInfoAsync();

            // สร้าง Session
            _currentUser = new UserSession
            {
                UserId = response.UserId,
                Email = response.Email ?? email,
                Name = response.UserName ?? email,
                Token = response.Token,
                ShopId = userInfo?.ShopId,
                ShopName = userInfo?.ShopName,
                IsAdmin = userInfo?.IsAdmin ?? false,
                Role = userInfo?.Role ?? "user"
            };

            // บันทึก Session
            await SaveSessionAsync();

            // แจ้ง Event
            OnAuthStateChanged(true, _currentUser);

            return new LoginResult
            {
                Success = true,
                Message = "เข้าสู่ระบบสำเร็จ",
                User = _currentUser
            };
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Login error: {ex.Message}");
            return new LoginResult
            {
                Success = false,
                Message = $"เกิดข้อผิดพลาด: {ex.Message}"
            };
        }
    }

    /// <summary>
    /// Logout ออกจากระบบ
    /// </summary>
    public async Task LogoutAsync()
    {
        try
        {
            // ลบข้อมูล Session
            SecureStorage.Remove(TokenKey);
            SecureStorage.Remove(UserIdKey);
            SecureStorage.Remove(UserEmailKey);
            SecureStorage.Remove(UserNameKey);
            SecureStorage.Remove(ShopIdKey);
            SecureStorage.Remove(ShopNameKey);
            SecureStorage.Remove(IsAdminKey);
            SecureStorage.Remove(RoleKey);

            _currentUser = null;

            // แจ้ง Event
            OnAuthStateChanged(false, null);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Logout error: {ex.Message}");
        }

        await Task.CompletedTask;
    }

    /// <summary>
    /// ตรวจสอบ Session ที่เก็บไว้
    /// </summary>
    public async Task<bool> CheckSavedSessionAsync()
    {
        try
        {
            var token = await SecureStorage.GetAsync(TokenKey);
            if (string.IsNullOrEmpty(token))
                return false;

            // โหลดข้อมูล Session
            var userIdStr = await SecureStorage.GetAsync(UserIdKey);
            var email = await SecureStorage.GetAsync(UserEmailKey);
            var name = await SecureStorage.GetAsync(UserNameKey);
            var shopIdStr = await SecureStorage.GetAsync(ShopIdKey);
            var shopName = await SecureStorage.GetAsync(ShopNameKey);
            var isAdminStr = await SecureStorage.GetAsync(IsAdminKey);
            var role = await SecureStorage.GetAsync(RoleKey);

            if (!int.TryParse(userIdStr, out int userId))
                return false;

            int? shopId = null;
            if (int.TryParse(shopIdStr, out int sid))
                shopId = sid;

            bool isAdmin = isAdminStr == "true";

            // ตั้งค่า API Client token
            _apiClient.SetAuthToken(token);

            // ตรวจสอบ token ยัง valid อยู่ไหม
            var isValid = await _apiClient.CheckConnectivityAsync();
            if (!isValid)
            {
                await LogoutAsync();
                return false;
            }

            _currentUser = new UserSession
            {
                UserId = userId,
                Email = email ?? "",
                Name = name ?? "",
                Token = token,
                ShopId = shopId,
                ShopName = shopName,
                IsAdmin = isAdmin,
                Role = role ?? "user"
            };

            OnAuthStateChanged(true, _currentUser);
            return true;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"CheckSavedSession error: {ex.Message}");
            return false;
        }
    }

    /// <summary>
    /// ดึงข้อมูล User เพิ่มเติม (Shop info)
    /// </summary>
    private async Task<UserInfoResponse?> GetUserInfoAsync()
    {
        try
        {
            // เรียก API /pos/api/user/info เพื่อดึงข้อมูล Shop
            var response = await _apiClient.GetUserInfoAsync();

            if (response?.Success == true && response.Data != null)
            {
                return new UserInfoResponse
                {
                    ShopId = response.Data.ShopId,
                    ShopName = response.Data.ShopName,
                    IsAdmin = response.Data.IsAdmin,
                    Role = response.Data.Role
                };
            }

            return null;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"GetUserInfo error: {ex.Message}");
            return null;
        }
    }

    /// <summary>
    /// บันทึก Session ลง SecureStorage
    /// </summary>
    private async Task SaveSessionAsync()
    {
        if (_currentUser == null) return;

        try
        {
            await SecureStorage.SetAsync(TokenKey, _currentUser.Token ?? "");
            await SecureStorage.SetAsync(UserIdKey, _currentUser.UserId.ToString());
            await SecureStorage.SetAsync(UserEmailKey, _currentUser.Email);
            await SecureStorage.SetAsync(UserNameKey, _currentUser.Name);
            await SecureStorage.SetAsync(ShopIdKey, _currentUser.ShopId?.ToString() ?? "");
            await SecureStorage.SetAsync(ShopNameKey, _currentUser.ShopName ?? "");
            await SecureStorage.SetAsync(IsAdminKey, _currentUser.IsAdmin ? "true" : "false");
            await SecureStorage.SetAsync(RoleKey, _currentUser.Role);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"SaveSession error: {ex.Message}");
        }
    }

    /// <summary>
    /// แจ้ง Event AuthStateChanged
    /// </summary>
    private void OnAuthStateChanged(bool isLoggedIn, UserSession? user)
    {
        AuthStateChanged?.Invoke(this, new AuthStateChangedEventArgs
        {
            IsLoggedIn = isLoggedIn,
            User = user
        });
    }
}

/// <summary>
/// Response จาก API /pos/api/user/info
/// </summary>
internal class UserInfoResponse
{
    public int? ShopId { get; set; }
    public string? ShopName { get; set; }
    public bool IsAdmin { get; set; }
    public string Role { get; set; } = "user";
}

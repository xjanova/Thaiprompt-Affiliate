using ThaipromptAffiliate.Helpers;
using ThaipromptAffiliate.Services;

namespace ThaipromptAffiliate.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้า Splash Screen
///
/// หน้าที่:
/// - โหลด Theme จาก Backend
/// - ตรวจสอบ Authentication status
/// - ตัดสินใจนำทางไปหน้าถัดไป
/// - แสดงสถานะการโหลด
/// </summary>
public class SplashViewModel : BaseViewModel
{
    private readonly ApiService _apiService;
    private readonly ThemeService _themeService;

    private string _loadingStatus = "กำลังเริ่มต้น...";
    private string _appVersion = "v1.0.0";

    /// <summary>
    /// สถานะการโหลดที่แสดงบนหน้าจอ
    /// </summary>
    public string LoadingStatus
    {
        get => _loadingStatus;
        set => SetProperty(ref _loadingStatus, value);
    }

    /// <summary>
    /// เวอร์ชันแอพที่แสดงด้านล่าง
    /// </summary>
    public string AppVersion
    {
        get => _appVersion;
        set => SetProperty(ref _appVersion, value);
    }

    /// <summary>
    /// Constructor - รับ services ผ่าน DI
    /// </summary>
    public SplashViewModel(ApiService apiService, ThemeService themeService)
    {
        _apiService = apiService;
        _themeService = themeService;

        // ดึงเวอร์ชันจาก App Info
        AppVersion = $"v{AppInfo.VersionString} ({AppInfo.BuildString})";
    }

    /// <summary>
    /// เริ่มต้นโหลดข้อมูลและนำทาง
    /// </summary>
    public async Task InitializeAsync()
    {
        try
        {
            // รอให้ animation เริ่มก่อน
            await Task.Delay(500);

            // ขั้นตอนที่ 1: ตรวจสอบการเชื่อมต่อ
            LoadingStatus = "กำลังตรวจสอบการเชื่อมต่อ...";
            await Task.Delay(300);

            var isConnected = Connectivity.Current.NetworkAccess == NetworkAccess.Internet;
            if (!isConnected)
            {
                LoadingStatus = "ไม่พบการเชื่อมต่ออินเทอร์เน็ต";
                await Task.Delay(1500);
                // ยังคงไปต่อได้ (offline mode)
            }

            // ขั้นตอนที่ 2: โหลด Theme
            LoadingStatus = "กำลังโหลดธีม...";
            await Task.Delay(300);

            try
            {
                await _themeService.LoadAndApplyThemeAsync();
            }
            catch
            {
                // ใช้ theme default ถ้าโหลดไม่ได้
            }

            // ขั้นตอนที่ 3: ตรวจสอบ Authentication
            LoadingStatus = "กำลังตรวจสอบผู้ใช้...";
            await Task.Delay(300);

            var isAuthenticated = await CheckAuthenticationAsync();

            // ขั้นตอนที่ 4: เตรียมพร้อม
            LoadingStatus = isAuthenticated ? "ยินดีต้อนรับกลับมา!" : "เตรียมพร้อมใช้งาน...";
            await Task.Delay(500);

            // ขั้นตอนที่ 5: นำทางไปหน้าถัดไป
            await NavigateToNextPageAsync(isAuthenticated);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Splash Initialize Error: {ex.Message}");

            // แสดง error และไปหน้า Hub
            LoadingStatus = "เกิดข้อผิดพลาด กำลังดำเนินการต่อ...";
            await Task.Delay(1000);
            await NavigateToNextPageAsync(false);
        }
    }

    /// <summary>
    /// ตรวจสอบว่า user ยังคง login อยู่หรือไม่
    /// </summary>
    private async Task<bool> CheckAuthenticationAsync()
    {
        try
        {
            // ดึง token จาก secure storage
            var token = await SecureStorage.GetAsync(Constants.StorageKeys.AuthToken);

            if (string.IsNullOrEmpty(token))
            {
                return false;
            }

            // ตรวจสอบ token validity กับ API
            var isValid = await _apiService.ValidateTokenAsync();

            return isValid;
        }
        catch
        {
            return false;
        }
    }

    /// <summary>
    /// นำทางไปหน้าถัดไป
    /// - ถ้า login แล้ว → ไปหน้า Hub (ให้เลือกบริการ)
    /// - ถ้ายังไม่ login → ไปหน้า Hub (แสดงปุ่ม login)
    /// </summary>
    private async Task NavigateToNextPageAsync(bool isAuthenticated)
    {
        // ทุกกรณีไปหน้า Hub Selection ก่อน
        // ถ้า login แล้วจะแสดงข้อมูล user
        // ถ้ายังไม่ login จะแสดงปุ่ม login/register

        await Shell.Current.GoToAsync("//hub", true);
    }
}

using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้า Login
/// </summary>
public partial class LoginViewModel : BaseViewModel
{
    #region Observable Properties

    /// <summary>
    /// ชื่อผู้ใช้
    /// </summary>
    [ObservableProperty]
    [NotifyPropertyChangedFor(nameof(CanLogin))]
    private string _username = string.Empty;

    /// <summary>
    /// รหัสผ่าน
    /// </summary>
    [ObservableProperty]
    [NotifyPropertyChangedFor(nameof(CanLogin))]
    private string _password = string.Empty;

    /// <summary>
    /// จำรหัสผ่าน
    /// </summary>
    [ObservableProperty]
    private bool _rememberMe = true;

    /// <summary>
    /// แสดงข้อความ Error
    /// </summary>
    [ObservableProperty]
    private string _errorMessage = string.Empty;

    /// <summary>
    /// แสดง Error
    /// </summary>
    [ObservableProperty]
    private bool _showError;

    /// <summary>
    /// เวอร์ชันแอป
    /// </summary>
    [ObservableProperty]
    private string _appVersion = "1.0.0";

    /// <summary>
    /// ชื่อร้าน (ถ้ามี)
    /// </summary>
    [ObservableProperty]
    private string _shopName = string.Empty;

    /// <summary>
    /// สามารถ Login ได้หรือไม่
    /// </summary>
    public bool CanLogin => !string.IsNullOrWhiteSpace(Username) && !string.IsNullOrWhiteSpace(Password);

    #endregion

    public LoginViewModel()
    {
        Title = "เข้าสู่ระบบ";
    }

    /// <summary>
    /// Initialize
    /// </summary>
    public Task InitializeAsync()
    {
        // โหลดข้อมูลที่บันทึกไว้
        ShopName = Preferences.Get("shop_name", string.Empty);
        AppVersion = AppInfo.Current.VersionString;

        // โหลด username ที่บันทึกไว้ (ถ้ามี)
        if (Preferences.Get("remember_me", false))
        {
            Username = Preferences.Get("saved_username", string.Empty);
            RememberMe = true;
        }

        return Task.CompletedTask;
    }

    #region Commands

    /// <summary>
    /// Login
    /// </summary>
    [RelayCommand]
    private async Task LoginAsync()
    {
        if (!CanLogin) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังเข้าสู่ระบบ...";
            ShowError = false;

            // รอสักครู่เพื่อให้ UI อัพเดท
            await Task.Delay(500);

            // TODO: ตรวจสอบ Login จาก Server หรือ Local
            // สำหรับตอนนี้ใช้ค่า default
            bool isValid = await ValidateLoginAsync(Username, Password);

            if (!isValid)
            {
                ErrorMessage = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                ShowError = true;
                return;
            }

            // บันทึกข้อมูล Login
            if (RememberMe)
            {
                Preferences.Set("remember_me", true);
                Preferences.Set("saved_username", Username);
            }
            else
            {
                Preferences.Remove("remember_me");
                Preferences.Remove("saved_username");
            }

            Preferences.Set("user_name", Username);
            Preferences.Set("user_token", Guid.NewGuid().ToString()); // TODO: ใช้ token จาก server

            // Haptic
            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);

            // ไปหน้าหลัก
            await Shell.Current.GoToAsync("//main");
        }
        catch (Exception ex)
        {
            ErrorMessage = $"เกิดข้อผิดพลาด: {ex.Message}";
            ShowError = true;
        }
        finally
        {
            IsBusy = false;
        }
    }

    /// <summary>
    /// ตรวจสอบ Login
    /// </summary>
    private async Task<bool> ValidateLoginAsync(string username, string password)
    {
        // TODO: ตรวจสอบ Login จาก Server
        // สำหรับตอนนี้ใช้ค่า default
        await Task.Delay(300); // จำลองการเรียก API

        // Default credentials สำหรับ development
        if (username.ToLower() == "admin" && password == "admin")
            return true;

        if (username.ToLower() == "cashier" && password == "1234")
            return true;

        // เช็คจาก Preferences (ถ้ามีการตั้งค่าไว้)
        var savedPassword = Preferences.Get($"user_password_{username.ToLower()}", string.Empty);
        if (!string.IsNullOrEmpty(savedPassword) && password == savedPassword)
            return true;

        return false;
    }

    /// <summary>
    /// Login ด้วย PIN
    /// </summary>
    [RelayCommand]
    private async Task LoginWithPinAsync()
    {
        // TODO: เปิด PIN Dialog
        await Shell.Current.DisplayAlert("Login with PIN", "ฟังก์ชันนี้จะพัฒนาในรุ่นถัดไป", "ตกลง");
    }

    /// <summary>
    /// ลืมรหัสผ่าน
    /// </summary>
    [RelayCommand]
    private async Task ForgotPasswordAsync()
    {
        await Shell.Current.DisplayAlert("ลืมรหัสผ่าน", "กรุณาติดต่อผู้ดูแลระบบเพื่อรีเซ็ตรหัสผ่าน", "ตกลง");
    }

    /// <summary>
    /// ไปหน้าตั้งค่า Server (ครั้งแรก)
    /// </summary>
    [RelayCommand]
    private async Task GoToSetupAsync()
    {
        await Shell.Current.GoToAsync("//settings");
    }

    /// <summary>
    /// เข้าใช้งานแบบ Offline
    /// </summary>
    [RelayCommand]
    private async Task OfflineModeAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "โหมด Offline",
            "คุณจะสามารถใช้งานได้โดยไม่ต้องเชื่อมต่อ Server แต่ข้อมูลจะไม่ถูก Sync ต้องการดำเนินการต่อ?",
            "ใช้งาน Offline", "ยกเลิก");

        if (!confirm) return;

        // บันทึก offline mode
        Preferences.Set("offline_mode", true);
        Preferences.Set("user_name", "Offline User");

        HapticFeedback.Default.Perform(HapticFeedbackType.Click);

        await Shell.Current.GoToAsync("//main");
    }

    #endregion
}

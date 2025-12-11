using System.Windows.Input;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้า Login
/// ใช้ manual implementation เพื่อหลีกเลี่ยง source generator conflicts
/// </summary>
public class LoginViewModel : BaseViewModel
{
    #region Private Fields

    private string _username = string.Empty;
    private string _password = string.Empty;
    private bool _rememberMe = true;
    private string _errorMessage = string.Empty;
    private bool _showError;
    private string _appVersion = "1.0.0";
    private string _shopName = string.Empty;
    private bool _isServerConnected;
    private Color _glowColor = Color.FromArgb("#6022C55E"); // Default: สีเขียว
    private string _connectionStatusText = "กำลังตรวจสอบการเชื่อมต่อ...";

    #endregion

    #region Properties

    /// <summary>
    /// ชื่อผู้ใช้
    /// </summary>
    public string Username
    {
        get => _username;
        set
        {
            if (SetProperty(ref _username, value))
                OnPropertyChanged(nameof(CanLogin));
        }
    }

    /// <summary>
    /// รหัสผ่าน
    /// </summary>
    public string Password
    {
        get => _password;
        set
        {
            if (SetProperty(ref _password, value))
                OnPropertyChanged(nameof(CanLogin));
        }
    }

    /// <summary>
    /// จำรหัสผ่าน
    /// </summary>
    public bool RememberMe
    {
        get => _rememberMe;
        set => SetProperty(ref _rememberMe, value);
    }

    /// <summary>
    /// แสดงข้อความ Error
    /// </summary>
    public string ErrorMessage
    {
        get => _errorMessage;
        set => SetProperty(ref _errorMessage, value);
    }

    /// <summary>
    /// แสดง Error
    /// </summary>
    public bool ShowError
    {
        get => _showError;
        set => SetProperty(ref _showError, value);
    }

    /// <summary>
    /// เวอร์ชันแอป
    /// </summary>
    public string AppVersion
    {
        get => _appVersion;
        set => SetProperty(ref _appVersion, value);
    }

    /// <summary>
    /// ชื่อร้าน (ถ้ามี)
    /// </summary>
    public string ShopName
    {
        get => _shopName;
        set => SetProperty(ref _shopName, value);
    }

    /// <summary>
    /// สามารถ Login ได้หรือไม่
    /// </summary>
    public bool CanLogin => !string.IsNullOrWhiteSpace(Username) && !string.IsNullOrWhiteSpace(Password);

    /// <summary>
    /// สถานะการเชื่อมต่อกับ Server
    /// </summary>
    public bool IsServerConnected
    {
        get => _isServerConnected;
        set
        {
            if (SetProperty(ref _isServerConnected, value))
            {
                // อัพเดทสีแสงเรืองตามสถานะการเชื่อมต่อ
                GlowColor = value
                    ? Color.FromArgb("#6022C55E")  // สีเขียว - เชื่อมต่อได้
                    : Color.FromArgb("#60EF4444"); // สีแดง - เชื่อมต่อไม่ได้
                ConnectionStatusText = value
                    ? "เชื่อมต่อ Server สำเร็จ"
                    : "ไม่สามารถเชื่อมต่อ Server ได้";
            }
        }
    }

    /// <summary>
    /// สีแสงเรือง (Glow) ของกรอบ Login
    /// สีเขียว = เชื่อมต่อได้, สีแดง = เชื่อมต่อไม่ได้
    /// </summary>
    public Color GlowColor
    {
        get => _glowColor;
        set => SetProperty(ref _glowColor, value);
    }

    /// <summary>
    /// ข้อความแสดงสถานะการเชื่อมต่อ
    /// </summary>
    public string ConnectionStatusText
    {
        get => _connectionStatusText;
        set => SetProperty(ref _connectionStatusText, value);
    }

    #endregion

    #region Commands

    public ICommand LoginCommand { get; }
    public ICommand LoginWithPinCommand { get; }
    public ICommand ForgotPasswordCommand { get; }
    public ICommand GoToSetupCommand { get; }
    public ICommand OfflineModeCommand { get; }

    #endregion

    public LoginViewModel()
    {
        Title = "เข้าสู่ระบบ";

        // Initialize commands
        LoginCommand = new Command(async () => await LoginAsync(), () => CanLogin);
        LoginWithPinCommand = new Command(async () => await LoginWithPinAsync());
        ForgotPasswordCommand = new Command(async () => await ForgotPasswordAsync());
        GoToSetupCommand = new Command(async () => await GoToSetupAsync());
        OfflineModeCommand = new Command(async () => await OfflineModeAsync());
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

        // ตรวจสอบการเชื่อมต่อ Server (ทำใน background)
        _ = CheckServerConnectionAsync();

        return Task.CompletedTask;
    }

    /// <summary>
    /// ตรวจสอบการเชื่อมต่อกับ Server
    /// อัพเดทสีแสงเรือง (Glow) ตามสถานะการเชื่อมต่อ
    /// </summary>
    private async Task CheckServerConnectionAsync()
    {
        try
        {
            ConnectionStatusText = "กำลังตรวจสอบการเชื่อมต่อ...";

            // ดึง Server URL จาก Preferences
            var serverUrl = Preferences.Get("server_url", string.Empty);

            if (string.IsNullOrEmpty(serverUrl))
            {
                // ยังไม่ได้ตั้งค่า Server URL
                IsServerConnected = false;
                ConnectionStatusText = "ยังไม่ได้ตั้งค่า Server";
                return;
            }

            // ทดสอบเชื่อมต่อ Server
            using var httpClient = new HttpClient();
            httpClient.Timeout = TimeSpan.FromSeconds(5); // timeout 5 วินาที

            // ลอง ping health check endpoint
            var healthUrl = serverUrl.TrimEnd('/') + "/api/health";
            var response = await httpClient.GetAsync(healthUrl);

            IsServerConnected = response.IsSuccessStatusCode;
        }
        catch (TaskCanceledException)
        {
            // Timeout
            IsServerConnected = false;
            ConnectionStatusText = "Server ไม่ตอบสนอง (Timeout)";
        }
        catch (HttpRequestException)
        {
            // ไม่สามารถเชื่อมต่อได้
            IsServerConnected = false;
            ConnectionStatusText = "ไม่สามารถเชื่อมต่อ Server ได้";
        }
        catch (Exception ex)
        {
            // ข้อผิดพลาดอื่นๆ
            IsServerConnected = false;
            ConnectionStatusText = $"เกิดข้อผิดพลาด: {ex.Message}";
            System.Diagnostics.Debug.WriteLine($"CheckServerConnection error: {ex}");
        }
    }

    #region Events

    /// <summary>
    /// Event เมื่อ Login สำเร็จ - ให้ View subscribe แล้วนำทางไป AppShell
    /// </summary>
    public event EventHandler? LoginSuccessful;

    /// <summary>
    /// Event เมื่อต้องการไปหน้า Setup
    /// </summary>
    public event EventHandler? NavigateToSetup;

    /// <summary>
    /// Raise event LoginSuccessful
    /// </summary>
    protected virtual void OnLoginSuccessful()
    {
        LoginSuccessful?.Invoke(this, EventArgs.Empty);
    }

    /// <summary>
    /// Raise event NavigateToSetup
    /// </summary>
    protected virtual void OnNavigateToSetup()
    {
        NavigateToSetup?.Invoke(this, EventArgs.Empty);
    }

    #endregion

    #region Helper Methods

    /// <summary>
    /// ดึง Page สำหรับแสดง Alert (รองรับทั้ง Shell และ NavigationPage)
    /// </summary>
    private Page? GetCurrentPage()
    {
        if (Shell.Current != null)
            return Shell.Current;

        // สำหรับ .NET 9 MAUI ใช้ Windows[0].Page
        if (Application.Current?.Windows.Count > 0)
            return Application.Current.Windows[0].Page;

        return null;
    }

    /// <summary>
    /// แสดง Alert Dialog
    /// </summary>
    private async Task ShowAlertAsync(string title, string message, string cancel = "ตกลง")
    {
        var page = GetCurrentPage();
        if (page != null)
            await page.DisplayAlert(title, message, cancel);
    }

    /// <summary>
    /// แสดง Confirm Dialog
    /// </summary>
    private async Task<bool> ShowConfirmAsync(string title, string message, string accept, string cancel)
    {
        var page = GetCurrentPage();
        if (page != null)
            return await page.DisplayAlert(title, message, accept, cancel);
        return false;
    }

    #endregion

    #region Command Implementations

    /// <summary>
    /// Login
    /// </summary>
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

            // ไปหน้าหลัก - ส่ง event ให้ View จัดการ
            OnLoginSuccessful();
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
    private async Task LoginWithPinAsync()
    {
        // TODO: เปิด PIN Dialog
        await ShowAlertAsync("Login with PIN", "ฟังก์ชันนี้จะพัฒนาในรุ่นถัดไป");
    }

    /// <summary>
    /// ลืมรหัสผ่าน
    /// </summary>
    private async Task ForgotPasswordAsync()
    {
        await ShowAlertAsync("ลืมรหัสผ่าน", "กรุณาติดต่อผู้ดูแลระบบเพื่อรีเซ็ตรหัสผ่าน");
    }

    /// <summary>
    /// ไปหน้าตั้งค่า Server (ครั้งแรก) - ส่ง event ให้ View จัดการ
    /// </summary>
    private Task GoToSetupAsync()
    {
        // ส่ง event ให้ View นำทางไปหน้า Setup
        OnNavigateToSetup();
        return Task.CompletedTask;
    }

    /// <summary>
    /// เข้าใช้งานแบบ Offline
    /// </summary>
    private async Task OfflineModeAsync()
    {
        bool confirm = await ShowConfirmAsync(
            "โหมด Offline",
            "คุณจะสามารถใช้งานได้โดยไม่ต้องเชื่อมต่อ Server แต่ข้อมูลจะไม่ถูก Sync ต้องการดำเนินการต่อ?",
            "ใช้งาน Offline", "ยกเลิก");

        if (!confirm) return;

        // บันทึก offline mode
        Preferences.Set("offline_mode", true);
        Preferences.Set("user_name", "Offline User");

        HapticFeedback.Default.Perform(HapticFeedbackType.Click);

        // ไปหน้าหลัก - ส่ง event ให้ View จัดการ
        OnLoginSuccessful();
    }

    #endregion
}

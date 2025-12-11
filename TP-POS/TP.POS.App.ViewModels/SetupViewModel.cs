using System.Windows.Input;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าตั้งค่าครั้งแรก (Setup / Registration)
/// </summary>
public class SetupViewModel : BaseViewModel
{
    #region Private Fields

    private readonly ILicenseService _licenseService;

    private string _serverUrl = string.Empty;
    private string _apiKey = string.Empty;
    private string _productKey = string.Empty;
    private string _deviceId = string.Empty;
    private string _statusMessage = string.Empty;
    private bool _showStatus;
    private bool _isSuccess;
    private bool _isRegistered;
    private string _shopName = string.Empty;
    private int _currentStep = 1; // 1 = กรอก Server URL, 2 = กรอก API Key, 3 = สำเร็จ

    #endregion

    #region Properties

    /// <summary>
    /// Server URL
    /// </summary>
    public string ServerUrl
    {
        get => _serverUrl;
        set
        {
            if (SetProperty(ref _serverUrl, value))
                OnPropertyChanged(nameof(CanProceedStep1));
        }
    }

    /// <summary>
    /// API Key จาก Server
    /// </summary>
    public string ApiKey
    {
        get => _apiKey;
        set
        {
            if (SetProperty(ref _apiKey, value))
                OnPropertyChanged(nameof(CanProceedStep2));
        }
    }

    /// <summary>
    /// Product Key (รหัสประจำเครื่อง) - แสดงให้ผู้ใช้เห็น
    /// </summary>
    public string ProductKey
    {
        get => _productKey;
        set => SetProperty(ref _productKey, value);
    }

    /// <summary>
    /// Device ID
    /// </summary>
    public string DeviceId
    {
        get => _deviceId;
        set => SetProperty(ref _deviceId, value);
    }

    /// <summary>
    /// ข้อความแสดงสถานะ
    /// </summary>
    public string StatusMessage
    {
        get => _statusMessage;
        set => SetProperty(ref _statusMessage, value);
    }

    /// <summary>
    /// แสดงข้อความสถานะหรือไม่
    /// </summary>
    public bool ShowStatus
    {
        get => _showStatus;
        set => SetProperty(ref _showStatus, value);
    }

    /// <summary>
    /// สถานะสำเร็จหรือไม่ (สำหรับสีของข้อความ)
    /// </summary>
    public bool IsSuccess
    {
        get => _isSuccess;
        set => SetProperty(ref _isSuccess, value);
    }

    /// <summary>
    /// ลงทะเบียนแล้วหรือไม่
    /// </summary>
    public bool IsRegistered
    {
        get => _isRegistered;
        set => SetProperty(ref _isRegistered, value);
    }

    /// <summary>
    /// ชื่อร้าน (หลังลงทะเบียนสำเร็จ)
    /// </summary>
    public string ShopName
    {
        get => _shopName;
        set => SetProperty(ref _shopName, value);
    }

    /// <summary>
    /// ขั้นตอนปัจจุบัน
    /// </summary>
    public int CurrentStep
    {
        get => _currentStep;
        set
        {
            if (SetProperty(ref _currentStep, value))
            {
                OnPropertyChanged(nameof(IsStep1));
                OnPropertyChanged(nameof(IsStep2));
                OnPropertyChanged(nameof(IsStep3));
            }
        }
    }

    // Step visibility
    public bool IsStep1 => CurrentStep == 1;
    public bool IsStep2 => CurrentStep == 2;
    public bool IsStep3 => CurrentStep == 3;

    // Can proceed
    public bool CanProceedStep1 => !string.IsNullOrWhiteSpace(ServerUrl) &&
                                   (ServerUrl.StartsWith("http://") || ServerUrl.StartsWith("https://"));
    public bool CanProceedStep2 => !string.IsNullOrWhiteSpace(ApiKey) && ApiKey.Length >= 10;

    #endregion

    #region Commands

    public ICommand NextStepCommand { get; }
    public ICommand PreviousStepCommand { get; }
    public ICommand RegisterCommand { get; }
    public ICommand CopyProductKeyCommand { get; }
    public ICommand TestConnectionCommand { get; }
    public ICommand FinishSetupCommand { get; }

    #endregion

    #region Events

    /// <summary>
    /// Event เมื่อ Setup เสร็จสมบูรณ์
    /// </summary>
    public event EventHandler? SetupCompleted;

    protected virtual void OnSetupCompleted()
    {
        SetupCompleted?.Invoke(this, EventArgs.Empty);
    }

    #endregion

    #region Constructor

    public SetupViewModel(ILicenseService licenseService)
    {
        _licenseService = licenseService;
        Title = "ตั้งค่าระบบ";

        // Initialize commands
        NextStepCommand = new Command(async () => await NextStepAsync());
        PreviousStepCommand = new Command(PreviousStep);
        RegisterCommand = new Command(async () => await RegisterAsync());
        CopyProductKeyCommand = new Command(async () => await CopyProductKeyAsync());
        TestConnectionCommand = new Command(async () => await TestConnectionAsync());
        FinishSetupCommand = new Command(FinishSetup);
    }

    #endregion

    #region Initialization

    /// <summary>
    /// Initialize
    /// </summary>
    public Task InitializeAsync()
    {
        // ดึง Device ID และสร้าง Product Key
        DeviceId = _licenseService.GetDeviceId();
        ProductKey = _licenseService.GenerateProductKey();

        // โหลด Server URL ที่เคยบันทึกไว้ (ถ้ามี)
        ServerUrl = Preferences.Get("server_url", "https://");

        // ตรวจสอบว่าลงทะเบียนแล้วหรือยัง
        if (_licenseService.IsActivated && _licenseService.CurrentLicense != null)
        {
            IsRegistered = true;
            ShopName = _licenseService.CurrentLicense.ShopName;
            CurrentStep = 3;
        }

        return Task.CompletedTask;
    }

    #endregion

    #region Command Implementations

    /// <summary>
    /// ไป Step ถัดไป
    /// </summary>
    private async Task NextStepAsync()
    {
        ShowStatus = false;

        if (CurrentStep == 1)
        {
            if (!CanProceedStep1)
            {
                StatusMessage = "กรุณาระบุ Server URL ให้ถูกต้อง";
                ShowStatus = true;
                IsSuccess = false;
                return;
            }

            // ทดสอบการเชื่อมต่อก่อน
            IsBusy = true;
            BusyMessage = "กำลังตรวจสอบการเชื่อมต่อ...";

            var canConnect = await TestConnectionInternalAsync();

            IsBusy = false;

            if (canConnect)
            {
                CurrentStep = 2;
            }
            else
            {
                StatusMessage = "ไม่สามารถเชื่อมต่อ Server ได้ กรุณาตรวจสอบ URL";
                ShowStatus = true;
                IsSuccess = false;
            }
        }
        else if (CurrentStep == 2)
        {
            if (!CanProceedStep2)
            {
                StatusMessage = "กรุณาระบุ API Key ให้ถูกต้อง";
                ShowStatus = true;
                IsSuccess = false;
                return;
            }

            await RegisterAsync();
        }
    }

    /// <summary>
    /// กลับ Step ก่อนหน้า
    /// </summary>
    private void PreviousStep()
    {
        ShowStatus = false;
        if (CurrentStep > 1 && CurrentStep < 3)
        {
            CurrentStep--;
        }
    }

    /// <summary>
    /// ลงทะเบียนกับ Server
    /// </summary>
    private async Task RegisterAsync()
    {
        if (string.IsNullOrWhiteSpace(ServerUrl) || string.IsNullOrWhiteSpace(ApiKey))
            return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังลงทะเบียน...";
            ShowStatus = false;

            var result = await _licenseService.RegisterAsync(ServerUrl, ApiKey);

            if (result.Success)
            {
                IsSuccess = true;
                IsRegistered = true;
                ShopName = result.License?.ShopName ?? "";
                StatusMessage = "ลงทะเบียนสำเร็จ!";
                ShowStatus = true;

                // ไป Step 3
                CurrentStep = 3;

                // Haptic feedback
                HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            }
            else
            {
                IsSuccess = false;
                StatusMessage = result.Message;
                ShowStatus = true;
            }
        }
        catch (Exception ex)
        {
            IsSuccess = false;
            StatusMessage = $"เกิดข้อผิดพลาด: {ex.Message}";
            ShowStatus = true;
        }
        finally
        {
            IsBusy = false;
        }
    }

    /// <summary>
    /// ทดสอบการเชื่อมต่อ
    /// </summary>
    private async Task TestConnectionAsync()
    {
        IsBusy = true;
        BusyMessage = "กำลังทดสอบการเชื่อมต่อ...";
        ShowStatus = false;

        var canConnect = await TestConnectionInternalAsync();

        IsBusy = false;

        if (canConnect)
        {
            IsSuccess = true;
            StatusMessage = "เชื่อมต่อสำเร็จ!";
        }
        else
        {
            IsSuccess = false;
            StatusMessage = "ไม่สามารถเชื่อมต่อได้";
        }
        ShowStatus = true;
    }

    private async Task<bool> TestConnectionInternalAsync()
    {
        try
        {
            using var httpClient = new HttpClient();
            httpClient.Timeout = TimeSpan.FromSeconds(10);

            var healthUrl = ServerUrl.TrimEnd('/') + "/api/health";
            var response = await httpClient.GetAsync(healthUrl);

            return response.IsSuccessStatusCode;
        }
        catch
        {
            return false;
        }
    }

    /// <summary>
    /// Copy Product Key ไปยัง Clipboard
    /// </summary>
    private async Task CopyProductKeyAsync()
    {
        try
        {
            await Clipboard.Default.SetTextAsync(ProductKey);

            StatusMessage = "คัดลอก Product Key แล้ว";
            IsSuccess = true;
            ShowStatus = true;

            HapticFeedback.Default.Perform(HapticFeedbackType.Click);

            // ซ่อนข้อความหลัง 2 วินาที
            await Task.Delay(2000);
            ShowStatus = false;
        }
        catch (Exception ex)
        {
            StatusMessage = $"ไม่สามารถคัดลอกได้: {ex.Message}";
            IsSuccess = false;
            ShowStatus = true;
        }
    }

    /// <summary>
    /// เสร็จสิ้นการตั้งค่า - กลับไปหน้า Login
    /// </summary>
    private void FinishSetup()
    {
        OnSetupCompleted();
    }

    #endregion

    #region Helper Methods

    /// <summary>
    /// ดึง Page สำหรับแสดง Alert
    /// </summary>
    private Page? GetCurrentPage()
    {
        if (Shell.Current != null)
            return Shell.Current;

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

    #endregion
}

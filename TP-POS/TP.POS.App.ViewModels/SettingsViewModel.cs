using System.Windows.Input;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าตั้งค่า
/// ใช้ manual implementation เพื่อหลีกเลี่ยง source generator conflicts
/// </summary>
public class SettingsViewModel : BaseViewModel
{
    private readonly ISyncService _syncService;
    private readonly IPrinterService _printerService;

    #region Private Fields

    private string _shopName = "ร้านค้า TP-POS";
    private string _shopAddress = string.Empty;
    private string _shopPhone = string.Empty;
    private string _taxId = string.Empty;
    private string _serverUrl = "https://your-server.com";
    private string _apiKey = string.Empty;
    private bool _enablePrinter = true;
    private string _printerName = string.Empty;
    private int _paperWidth = 80;
    private bool _autoPrintReceipt = true;
    private bool _enableSound = true;
    private bool _enableHaptic = true;
    private bool _isDarkMode;
    private string _language = "th";
    private bool _autoSync = true;
    private int _syncInterval = 15;
    private DateTime? _lastSyncTime;
    private string _lastSyncText = "ยังไม่เคย Sync";
    private int _productCount;
    private int _pendingSyncCount;
    private string _appVersion = "1.0.0";
    private string _currentUserName = "Admin";
    private string _branchCode = "HQ";
    private string _branchName = "สำนักงานใหญ่";
    private bool _isSyncing;
    private string _syncMessage = string.Empty;
    private bool _showPrinterModal;
    private bool _showServerModal;
    private List<PrinterInfo> _availablePrinters = new();
    private PrinterInfo? _selectedPrinter;

    #endregion

    #region Properties

    public string ShopName
    {
        get => _shopName;
        set => SetProperty(ref _shopName, value);
    }

    public string ShopAddress
    {
        get => _shopAddress;
        set => SetProperty(ref _shopAddress, value);
    }

    public string ShopPhone
    {
        get => _shopPhone;
        set => SetProperty(ref _shopPhone, value);
    }

    public string TaxId
    {
        get => _taxId;
        set => SetProperty(ref _taxId, value);
    }

    public string ServerUrl
    {
        get => _serverUrl;
        set => SetProperty(ref _serverUrl, value);
    }

    public string ApiKey
    {
        get => _apiKey;
        set => SetProperty(ref _apiKey, value);
    }

    public bool EnablePrinter
    {
        get => _enablePrinter;
        set => SetProperty(ref _enablePrinter, value);
    }

    public string PrinterName
    {
        get => _printerName;
        set => SetProperty(ref _printerName, value);
    }

    public int PaperWidth
    {
        get => _paperWidth;
        set => SetProperty(ref _paperWidth, value);
    }

    public bool AutoPrintReceipt
    {
        get => _autoPrintReceipt;
        set => SetProperty(ref _autoPrintReceipt, value);
    }

    public bool EnableSound
    {
        get => _enableSound;
        set => SetProperty(ref _enableSound, value);
    }

    public bool EnableHaptic
    {
        get => _enableHaptic;
        set => SetProperty(ref _enableHaptic, value);
    }

    public bool IsDarkMode
    {
        get => _isDarkMode;
        set => SetProperty(ref _isDarkMode, value);
    }

    public string Language
    {
        get => _language;
        set => SetProperty(ref _language, value);
    }

    public bool AutoSync
    {
        get => _autoSync;
        set => SetProperty(ref _autoSync, value);
    }

    public int SyncInterval
    {
        get => _syncInterval;
        set => SetProperty(ref _syncInterval, value);
    }

    public DateTime? LastSyncTime
    {
        get => _lastSyncTime;
        set => SetProperty(ref _lastSyncTime, value);
    }

    public string LastSyncText
    {
        get => _lastSyncText;
        set => SetProperty(ref _lastSyncText, value);
    }

    public int ProductCount
    {
        get => _productCount;
        set => SetProperty(ref _productCount, value);
    }

    public int PendingSyncCount
    {
        get => _pendingSyncCount;
        set => SetProperty(ref _pendingSyncCount, value);
    }

    public string AppVersion
    {
        get => _appVersion;
        set => SetProperty(ref _appVersion, value);
    }

    public string CurrentUserName
    {
        get => _currentUserName;
        set => SetProperty(ref _currentUserName, value);
    }

    public string BranchCode
    {
        get => _branchCode;
        set => SetProperty(ref _branchCode, value);
    }

    public string BranchName
    {
        get => _branchName;
        set => SetProperty(ref _branchName, value);
    }

    public bool IsSyncing
    {
        get => _isSyncing;
        set => SetProperty(ref _isSyncing, value);
    }

    public string SyncMessage
    {
        get => _syncMessage;
        set => SetProperty(ref _syncMessage, value);
    }

    public bool ShowPrinterModal
    {
        get => _showPrinterModal;
        set => SetProperty(ref _showPrinterModal, value);
    }

    public bool ShowServerModal
    {
        get => _showServerModal;
        set => SetProperty(ref _showServerModal, value);
    }

    public List<PrinterInfo> AvailablePrinters
    {
        get => _availablePrinters;
        set => SetProperty(ref _availablePrinters, value);
    }

    public PrinterInfo? SelectedPrinter
    {
        get => _selectedPrinter;
        set => SetProperty(ref _selectedPrinter, value);
    }

    #endregion

    #region Commands

    public ICommand SaveShopSettingsCommand { get; }
    public ICommand OpenServerSettingsCommand { get; }
    public ICommand CloseServerSettingsCommand { get; }
    public ICommand SaveServerSettingsCommand { get; }
    public ICommand TestConnectionCommand { get; }
    public ICommand OpenPrinterSettingsCommand { get; }
    public ICommand ClosePrinterSettingsCommand { get; }
    public ICommand SelectPrinterCommand { get; }
    public ICommand SavePrinterSettingsCommand { get; }
    public ICommand TestPrintCommand { get; }
    public ICommand ToggleSoundCommand { get; }
    public ICommand ToggleHapticCommand { get; }
    public ICommand ToggleDarkModeCommand { get; }
    public ICommand ToggleAutoSyncCommand { get; }
    public ICommand SyncNowCommand { get; }
    public ICommand ImportProductsCommand { get; }
    public ICommand ClearAllDataCommand { get; }
    public ICommand LogoutCommand { get; }
    public ICommand ViewAppInfoCommand { get; }

    #endregion

    public SettingsViewModel(
        ISyncService syncService,
        IPrinterService printerService)
    {
        _syncService = syncService;
        _printerService = printerService;

        Title = "ตั้งค่า";

        // Initialize commands
        SaveShopSettingsCommand = new Command(async () => await SaveShopSettingsAsync());
        OpenServerSettingsCommand = new Command(OpenServerSettings);
        CloseServerSettingsCommand = new Command(CloseServerSettings);
        SaveServerSettingsCommand = new Command(async () => await SaveServerSettingsAsync());
        TestConnectionCommand = new Command(async () => await TestConnectionAsync());
        OpenPrinterSettingsCommand = new Command(async () => await OpenPrinterSettingsAsync());
        ClosePrinterSettingsCommand = new Command(ClosePrinterSettings);
        SelectPrinterCommand = new Command<PrinterInfo>(SelectPrinter);
        SavePrinterSettingsCommand = new Command(async () => await SavePrinterSettingsAsync());
        TestPrintCommand = new Command(async () => await TestPrintAsync());
        ToggleSoundCommand = new Command(ToggleSound);
        ToggleHapticCommand = new Command(ToggleHaptic);
        ToggleDarkModeCommand = new Command(ToggleDarkMode);
        ToggleAutoSyncCommand = new Command(ToggleAutoSync);
        SyncNowCommand = new Command(async () => await SyncNowAsync());
        ImportProductsCommand = new Command(async () => await ImportProductsAsync());
        ClearAllDataCommand = new Command(async () => await ClearAllDataAsync());
        LogoutCommand = new Command(async () => await LogoutAsync());
        ViewAppInfoCommand = new Command(async () => await ViewAppInfoAsync());
    }

    /// <summary>
    /// Initialize
    /// </summary>
    public async Task InitializeAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังโหลดการตั้งค่า...";

            await LoadSettingsAsync();
            await LoadStatisticsAsync();
            await LoadPrintersAsync();
            UpdateLastSyncText();
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    #region Load Data

    private async Task LoadSettingsAsync()
    {
        await Task.Run(() =>
        {
            ShopName = Preferences.Get("shop_name", "ร้านค้า TP-POS");
            ShopAddress = Preferences.Get("shop_address", string.Empty);
            ShopPhone = Preferences.Get("shop_phone", string.Empty);
            TaxId = Preferences.Get("tax_id", string.Empty);
            ServerUrl = Preferences.Get("server_url", "https://your-server.com");
            ApiKey = Preferences.Get("api_key", string.Empty);
            BranchCode = Preferences.Get("branch_code", "HQ");
            BranchName = Preferences.Get("branch_name", "สำนักงานใหญ่");
            EnablePrinter = Preferences.Get("enable_printer", true);
            PrinterName = Preferences.Get("printer_name", string.Empty);
            PaperWidth = Preferences.Get("paper_width", 80);
            AutoPrintReceipt = Preferences.Get("auto_print_receipt", true);
            EnableSound = Preferences.Get("enable_sound", true);
            EnableHaptic = Preferences.Get("enable_haptic", true);
            IsDarkMode = Preferences.Get("is_dark_mode", false);
            Language = Preferences.Get("language", "th");
            AutoSync = Preferences.Get("auto_sync", true);
            SyncInterval = Preferences.Get("sync_interval", 15);

            var lastSyncTicks = Preferences.Get("last_sync_time", 0L);
            if (lastSyncTicks > 0)
            {
                LastSyncTime = new DateTime(lastSyncTicks);
            }
        });
    }

    private async Task LoadStatisticsAsync()
    {
        ProductCount = await _syncService.GetLocalProductCountAsync();
        PendingSyncCount = await _syncService.GetPendingSyncCountAsync();
        AppVersion = AppInfo.Current.VersionString;
    }

    private async Task LoadPrintersAsync()
    {
        try
        {
            var printers = await _printerService.GetAvailablePrintersAsync();
            AvailablePrinters = printers.Select(p => new PrinterInfo
            {
                Name = p,
                IsConnected = p == PrinterName
            }).ToList();

            SelectedPrinter = AvailablePrinters.FirstOrDefault(p => p.Name == PrinterName);
        }
        catch
        {
            AvailablePrinters = new List<PrinterInfo>();
        }
    }

    private void UpdateLastSyncText()
    {
        if (LastSyncTime == null)
        {
            LastSyncText = "ยังไม่เคย Sync";
            return;
        }

        var diff = DateTime.Now - LastSyncTime.Value;

        if (diff.TotalMinutes < 1)
        {
            LastSyncText = "เมื่อสักครู่";
        }
        else if (diff.TotalMinutes < 60)
        {
            LastSyncText = $"{(int)diff.TotalMinutes} นาทีที่แล้ว";
        }
        else if (diff.TotalHours < 24)
        {
            LastSyncText = $"{(int)diff.TotalHours} ชั่วโมงที่แล้ว";
        }
        else
        {
            LastSyncText = LastSyncTime.Value.ToString("dd/MM/yyyy HH:mm");
        }
    }

    #endregion

    #region Command Implementations

    private async Task SaveShopSettingsAsync()
    {
        try
        {
            IsBusy = true;
            BusyMessage = "กำลังบันทึก...";

            Preferences.Set("shop_name", ShopName);
            Preferences.Set("shop_address", ShopAddress);
            Preferences.Set("shop_phone", ShopPhone);
            Preferences.Set("tax_id", TaxId);

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", "บันทึกข้อมูลร้านค้าเรียบร้อย", "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private void OpenServerSettings()
    {
        ShowServerModal = true;
    }

    private void CloseServerSettings()
    {
        ShowServerModal = false;
    }

    private async Task SaveServerSettingsAsync()
    {
        try
        {
            IsBusy = true;
            BusyMessage = "กำลังบันทึก...";

            Preferences.Set("server_url", ServerUrl);
            Preferences.Set("api_key", ApiKey);
            Preferences.Set("branch_code", BranchCode);
            Preferences.Set("branch_name", BranchName);

            ShowServerModal = false;

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", "บันทึกการตั้งค่า Server เรียบร้อย", "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task TestConnectionAsync()
    {
        try
        {
            IsBusy = true;
            BusyMessage = "กำลังทดสอบการเชื่อมต่อ...";

            var isConnected = await _syncService.TestConnectionAsync(ServerUrl, ApiKey);

            if (isConnected)
            {
                await Shell.Current.DisplayAlert("สำเร็จ", "เชื่อมต่อ Server ได้สำเร็จ!", "ตกลง");
            }
            else
            {
                await Shell.Current.DisplayAlert("ล้มเหลว", "ไม่สามารถเชื่อมต่อ Server ได้ กรุณาตรวจสอบ URL และ API Key", "ตกลง");
            }
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ล้มเหลว", $"เชื่อมต่อไม่สำเร็จ: {ex.Message}", "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task OpenPrinterSettingsAsync()
    {
        await LoadPrintersAsync();
        ShowPrinterModal = true;
    }

    private void ClosePrinterSettings()
    {
        ShowPrinterModal = false;
    }

    private void SelectPrinter(PrinterInfo printer)
    {
        foreach (var p in AvailablePrinters)
        {
            p.IsConnected = p.Name == printer.Name;
        }
        SelectedPrinter = printer;
        PrinterName = printer.Name;
    }

    private async Task SavePrinterSettingsAsync()
    {
        try
        {
            IsBusy = true;
            BusyMessage = "กำลังบันทึก...";

            Preferences.Set("enable_printer", EnablePrinter);
            Preferences.Set("printer_name", PrinterName);
            Preferences.Set("paper_width", PaperWidth);
            Preferences.Set("auto_print_receipt", AutoPrintReceipt);

            ShowPrinterModal = false;

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", "บันทึกการตั้งค่าเครื่องพิมพ์เรียบร้อย", "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task TestPrintAsync()
    {
        try
        {
            IsBusy = true;
            BusyMessage = "กำลังพิมพ์ทดสอบ...";

            await _printerService.PrintTestAsync();

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", "ส่งงานพิมพ์ทดสอบเรียบร้อย", "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("พิมพ์ไม่สำเร็จ", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private void ToggleSound()
    {
        EnableSound = !EnableSound;
        Preferences.Set("enable_sound", EnableSound);
    }

    private void ToggleHaptic()
    {
        EnableHaptic = !EnableHaptic;
        Preferences.Set("enable_haptic", EnableHaptic);

        if (EnableHaptic)
        {
            HapticFeedback.Default.Perform(HapticFeedbackType.Click);
        }
    }

    private void ToggleDarkMode()
    {
        IsDarkMode = !IsDarkMode;
        Preferences.Set("is_dark_mode", IsDarkMode);
        Application.Current!.UserAppTheme = IsDarkMode ? AppTheme.Dark : AppTheme.Light;
    }

    private void ToggleAutoSync()
    {
        AutoSync = !AutoSync;
        Preferences.Set("auto_sync", AutoSync);
    }

    private async Task SyncNowAsync()
    {
        try
        {
            IsSyncing = true;
            SyncMessage = "กำลัง Sync ข้อมูล...";

            SyncMessage = "กำลังดึงข้อมูลสินค้า...";
            var productCount = await _syncService.SyncProductsAsync();

            SyncMessage = "กำลังส่ง Transaction...";
            var transactionCount = await _syncService.SyncTransactionsAsync();

            LastSyncTime = DateTime.Now;
            Preferences.Set("last_sync_time", LastSyncTime.Value.Ticks);
            UpdateLastSyncText();

            await LoadStatisticsAsync();

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("Sync สำเร็จ",
                $"อัพเดทสินค้า: {productCount} รายการ\nส่ง Transaction: {transactionCount} รายการ",
                "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("Sync ล้มเหลว", ex.Message, "ตกลง");
        }
        finally
        {
            IsSyncing = false;
            SyncMessage = string.Empty;
        }
    }

    private async Task ImportProductsAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "นำเข้าสินค้า",
            "ต้องการนำเข้าสินค้าทั้งหมดจาก Server? การดำเนินการนี้จะใช้เวลาสักครู่",
            "นำเข้า", "ยกเลิก");

        if (!confirm) return;

        try
        {
            IsSyncing = true;
            SyncMessage = "กำลังนำเข้าสินค้า...";

            var count = await _syncService.ImportAllProductsAsync();

            LastSyncTime = DateTime.Now;
            Preferences.Set("last_sync_time", LastSyncTime.Value.Ticks);
            UpdateLastSyncText();

            await LoadStatisticsAsync();

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", $"นำเข้าสินค้า {count} รายการเรียบร้อย", "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("นำเข้าล้มเหลว", ex.Message, "ตกลง");
        }
        finally
        {
            IsSyncing = false;
            SyncMessage = string.Empty;
        }
    }

    private async Task ClearAllDataAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "⚠️ ล้างข้อมูลทั้งหมด",
            "การดำเนินการนี้จะลบข้อมูลสินค้า, Transaction และการตั้งค่าทั้งหมด!\n\nต้องการดำเนินการต่อ?",
            "ล้างข้อมูล", "ยกเลิก");

        if (!confirm) return;

        bool doubleConfirm = await Shell.Current.DisplayAlert(
            "⚠️ ยืนยันการล้างข้อมูล",
            "คุณแน่ใจหรือไม่? ข้อมูลที่ถูกลบจะไม่สามารถกู้คืนได้!",
            "ยืนยัน", "ยกเลิก");

        if (!doubleConfirm) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังล้างข้อมูล...";

            await _syncService.ClearAllDataAsync();
            Preferences.Clear();

            await LoadSettingsAsync();
            await LoadStatisticsAsync();

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", "ล้างข้อมูลทั้งหมดเรียบร้อย", "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task LogoutAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "ออกจากระบบ",
            "ต้องการออกจากระบบ?",
            "ออกจากระบบ", "ยกเลิก");

        if (!confirm) return;

        Preferences.Remove("user_token");
        Preferences.Remove("user_name");

        await Shell.Current.GoToAsync("//login");
    }

    private async Task ViewAppInfoAsync()
    {
        string info = $"TP-POS v{AppVersion}\n" +
                     $"Powered by .NET MAUI\n\n" +
                     $"© 2024 Thaiprompt\n" +
                     $"All rights reserved.\n\n" +
                     $"Platform: {DeviceInfo.Platform}\n" +
                     $"Model: {DeviceInfo.Model}\n" +
                     $"OS: {DeviceInfo.VersionString}";

        await Shell.Current.DisplayAlert("เกี่ยวกับแอป", info, "ตกลง");
    }

    #endregion
}

#region Helper Models

/// <summary>
/// Model สำหรับข้อมูลเครื่องพิมพ์
/// </summary>
public class PrinterInfo : BaseViewModel
{
    public string Name { get; set; } = string.Empty;

    private bool _isConnected;
    public bool IsConnected
    {
        get => _isConnected;
        set
        {
            if (SetProperty(ref _isConnected, value))
            {
                UpdateColors();
            }
        }
    }

    private Color _backgroundColor = Color.FromArgb("#F3F4F6");
    public Color BackgroundColor
    {
        get => _backgroundColor;
        set => SetProperty(ref _backgroundColor, value);
    }

    private Color _borderColor = Color.FromArgb("#E5E7EB");
    public Color BorderColor
    {
        get => _borderColor;
        set => SetProperty(ref _borderColor, value);
    }

    private void UpdateColors()
    {
        if (IsConnected)
        {
            BackgroundColor = Color.FromArgb("#DCFCE7");
            BorderColor = Color.FromArgb("#22C55E");
        }
        else
        {
            BackgroundColor = Color.FromArgb("#F3F4F6");
            BorderColor = Color.FromArgb("#E5E7EB");
        }
    }
}

#endregion

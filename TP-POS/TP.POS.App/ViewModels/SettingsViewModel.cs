using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าตั้งค่า
/// </summary>
public partial class SettingsViewModel : BaseViewModel
{
    private readonly ISyncService _syncService;
    private readonly IPrinterService _printerService;

    #region Observable Properties

    /// <summary>
    /// ชื่อร้าน
    /// </summary>
    [ObservableProperty]
    private string _shopName = "ร้านค้า TP-POS";

    /// <summary>
    /// ที่อยู่ร้าน
    /// </summary>
    [ObservableProperty]
    private string _shopAddress = string.Empty;

    /// <summary>
    /// เบอร์โทรศัพท์ร้าน
    /// </summary>
    [ObservableProperty]
    private string _shopPhone = string.Empty;

    /// <summary>
    /// เลขประจำตัวผู้เสียภาษี
    /// </summary>
    [ObservableProperty]
    private string _taxId = string.Empty;

    /// <summary>
    /// URL Server
    /// </summary>
    [ObservableProperty]
    private string _serverUrl = "https://your-server.com";

    /// <summary>
    /// API Key
    /// </summary>
    [ObservableProperty]
    private string _apiKey = string.Empty;

    /// <summary>
    /// เปิดใช้งานเครื่องพิมพ์
    /// </summary>
    [ObservableProperty]
    private bool _enablePrinter = true;

    /// <summary>
    /// ชื่อเครื่องพิมพ์
    /// </summary>
    [ObservableProperty]
    private string _printerName = string.Empty;

    /// <summary>
    /// ความกว้างกระดาษ (mm)
    /// </summary>
    [ObservableProperty]
    private int _paperWidth = 80;

    /// <summary>
    /// พิมพ์ใบเสร็จอัตโนมัติ
    /// </summary>
    [ObservableProperty]
    private bool _autoPrintReceipt = true;

    /// <summary>
    /// เปิดเสียงแจ้งเตือน
    /// </summary>
    [ObservableProperty]
    private bool _enableSound = true;

    /// <summary>
    /// เปิด Haptic Feedback
    /// </summary>
    [ObservableProperty]
    private bool _enableHaptic = true;

    /// <summary>
    /// โหมดมืด
    /// </summary>
    [ObservableProperty]
    private bool _isDarkMode;

    /// <summary>
    /// ภาษาที่ใช้
    /// </summary>
    [ObservableProperty]
    private string _language = "th";

    /// <summary>
    /// Sync อัตโนมัติ
    /// </summary>
    [ObservableProperty]
    private bool _autoSync = true;

    /// <summary>
    /// ช่วงเวลา Sync (นาที)
    /// </summary>
    [ObservableProperty]
    private int _syncInterval = 15;

    /// <summary>
    /// เวลา Sync ล่าสุด
    /// </summary>
    [ObservableProperty]
    private DateTime? _lastSyncTime;

    /// <summary>
    /// ข้อความแสดงเวลา Sync ล่าสุด
    /// </summary>
    [ObservableProperty]
    private string _lastSyncText = "ยังไม่เคย Sync";

    /// <summary>
    /// จำนวนสินค้าในระบบ
    /// </summary>
    [ObservableProperty]
    private int _productCount;

    /// <summary>
    /// จำนวน Transaction ที่รอ Sync
    /// </summary>
    [ObservableProperty]
    private int _pendingSyncCount;

    /// <summary>
    /// เวอร์ชันแอป
    /// </summary>
    [ObservableProperty]
    private string _appVersion = "1.0.0";

    /// <summary>
    /// ชื่อผู้ใช้ที่ Login
    /// </summary>
    [ObservableProperty]
    private string _currentUserName = "Admin";

    /// <summary>
    /// รหัสสาขา
    /// </summary>
    [ObservableProperty]
    private string _branchCode = "HQ";

    /// <summary>
    /// ชื่อสาขา
    /// </summary>
    [ObservableProperty]
    private string _branchName = "สำนักงานใหญ่";

    /// <summary>
    /// กำลัง Sync อยู่
    /// </summary>
    [ObservableProperty]
    private bool _isSyncing;

    /// <summary>
    /// ข้อความ Sync
    /// </summary>
    [ObservableProperty]
    private string _syncMessage = string.Empty;

    /// <summary>
    /// แสดง Modal ตั้งค่าเครื่องพิมพ์
    /// </summary>
    [ObservableProperty]
    private bool _showPrinterModal;

    /// <summary>
    /// แสดง Modal ตั้งค่า Server
    /// </summary>
    [ObservableProperty]
    private bool _showServerModal;

    /// <summary>
    /// รายการเครื่องพิมพ์ที่เชื่อมต่อ
    /// </summary>
    [ObservableProperty]
    private List<PrinterInfo> _availablePrinters = new();

    /// <summary>
    /// เครื่องพิมพ์ที่เลือก
    /// </summary>
    [ObservableProperty]
    private PrinterInfo? _selectedPrinter;

    #endregion

    public SettingsViewModel(
        ISyncService syncService,
        IPrinterService printerService)
    {
        _syncService = syncService;
        _printerService = printerService;

        Title = "ตั้งค่า";
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

            // โหลดการตั้งค่าจาก Preferences
            await LoadSettingsAsync();

            // โหลดข้อมูลสถิติ
            await LoadStatisticsAsync();

            // โหลดรายการเครื่องพิมพ์
            await LoadPrintersAsync();

            // อัพเดทข้อความ Sync
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

    /// <summary>
    /// โหลดการตั้งค่าจาก Preferences
    /// </summary>
    private async Task LoadSettingsAsync()
    {
        await Task.Run(() =>
        {
            // ข้อมูลร้าน
            ShopName = Preferences.Get("shop_name", "ร้านค้า TP-POS");
            ShopAddress = Preferences.Get("shop_address", string.Empty);
            ShopPhone = Preferences.Get("shop_phone", string.Empty);
            TaxId = Preferences.Get("tax_id", string.Empty);

            // ข้อมูล Server
            ServerUrl = Preferences.Get("server_url", "https://your-server.com");
            ApiKey = Preferences.Get("api_key", string.Empty);

            // ข้อมูลสาขา
            BranchCode = Preferences.Get("branch_code", "HQ");
            BranchName = Preferences.Get("branch_name", "สำนักงานใหญ่");

            // การตั้งค่าเครื่องพิมพ์
            EnablePrinter = Preferences.Get("enable_printer", true);
            PrinterName = Preferences.Get("printer_name", string.Empty);
            PaperWidth = Preferences.Get("paper_width", 80);
            AutoPrintReceipt = Preferences.Get("auto_print_receipt", true);

            // การตั้งค่าทั่วไป
            EnableSound = Preferences.Get("enable_sound", true);
            EnableHaptic = Preferences.Get("enable_haptic", true);
            IsDarkMode = Preferences.Get("is_dark_mode", false);
            Language = Preferences.Get("language", "th");

            // การตั้งค่า Sync
            AutoSync = Preferences.Get("auto_sync", true);
            SyncInterval = Preferences.Get("sync_interval", 15);

            // เวลา Sync ล่าสุด
            var lastSyncTicks = Preferences.Get("last_sync_time", 0L);
            if (lastSyncTicks > 0)
            {
                LastSyncTime = new DateTime(lastSyncTicks);
            }
        });
    }

    /// <summary>
    /// โหลดข้อมูลสถิติ
    /// </summary>
    private async Task LoadStatisticsAsync()
    {
        // จำนวนสินค้า
        ProductCount = await _syncService.GetLocalProductCountAsync();

        // จำนวน Transaction ที่รอ Sync
        PendingSyncCount = await _syncService.GetPendingSyncCountAsync();

        // เวอร์ชันแอป
        AppVersion = AppInfo.Current.VersionString;
    }

    /// <summary>
    /// โหลดรายการเครื่องพิมพ์
    /// </summary>
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

            // เลือกเครื่องพิมพ์ที่ตั้งค่าไว้
            SelectedPrinter = AvailablePrinters.FirstOrDefault(p => p.Name == PrinterName);
        }
        catch
        {
            AvailablePrinters = new List<PrinterInfo>();
        }
    }

    /// <summary>
    /// อัพเดทข้อความ Sync ล่าสุด
    /// </summary>
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

    #region Commands

    /// <summary>
    /// บันทึกการตั้งค่าร้าน
    /// </summary>
    [RelayCommand]
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

    /// <summary>
    /// เปิด Modal ตั้งค่า Server
    /// </summary>
    [RelayCommand]
    private void OpenServerSettings()
    {
        ShowServerModal = true;
    }

    /// <summary>
    /// ปิด Modal ตั้งค่า Server
    /// </summary>
    [RelayCommand]
    private void CloseServerSettings()
    {
        ShowServerModal = false;
    }

    /// <summary>
    /// บันทึกการตั้งค่า Server
    /// </summary>
    [RelayCommand]
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

    /// <summary>
    /// ทดสอบการเชื่อมต่อ Server
    /// </summary>
    [RelayCommand]
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

    /// <summary>
    /// เปิด Modal ตั้งค่าเครื่องพิมพ์
    /// </summary>
    [RelayCommand]
    private async Task OpenPrinterSettingsAsync()
    {
        await LoadPrintersAsync();
        ShowPrinterModal = true;
    }

    /// <summary>
    /// ปิด Modal ตั้งค่าเครื่องพิมพ์
    /// </summary>
    [RelayCommand]
    private void ClosePrinterSettings()
    {
        ShowPrinterModal = false;
    }

    /// <summary>
    /// เลือกเครื่องพิมพ์
    /// </summary>
    [RelayCommand]
    private void SelectPrinter(PrinterInfo printer)
    {
        foreach (var p in AvailablePrinters)
        {
            p.IsConnected = p.Name == printer.Name;
        }
        SelectedPrinter = printer;
        PrinterName = printer.Name;
    }

    /// <summary>
    /// บันทึกการตั้งค่าเครื่องพิมพ์
    /// </summary>
    [RelayCommand]
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

    /// <summary>
    /// ทดสอบพิมพ์
    /// </summary>
    [RelayCommand]
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

    /// <summary>
    /// สลับเปิด/ปิดเสียง
    /// </summary>
    [RelayCommand]
    private void ToggleSound()
    {
        EnableSound = !EnableSound;
        Preferences.Set("enable_sound", EnableSound);
    }

    /// <summary>
    /// สลับเปิด/ปิด Haptic
    /// </summary>
    [RelayCommand]
    private void ToggleHaptic()
    {
        EnableHaptic = !EnableHaptic;
        Preferences.Set("enable_haptic", EnableHaptic);

        if (EnableHaptic)
        {
            HapticFeedback.Default.Perform(HapticFeedbackType.Click);
        }
    }

    /// <summary>
    /// สลับโหมดมืด/สว่าง
    /// </summary>
    [RelayCommand]
    private void ToggleDarkMode()
    {
        IsDarkMode = !IsDarkMode;
        Preferences.Set("is_dark_mode", IsDarkMode);

        // อัพเดท App Theme
        Application.Current!.UserAppTheme = IsDarkMode ? AppTheme.Dark : AppTheme.Light;
    }

    /// <summary>
    /// สลับเปิด/ปิด Auto Sync
    /// </summary>
    [RelayCommand]
    private void ToggleAutoSync()
    {
        AutoSync = !AutoSync;
        Preferences.Set("auto_sync", AutoSync);
    }

    /// <summary>
    /// Sync ข้อมูลทันที
    /// </summary>
    [RelayCommand]
    private async Task SyncNowAsync()
    {
        try
        {
            IsSyncing = true;
            SyncMessage = "กำลัง Sync ข้อมูล...";

            // Sync สินค้าจาก Server
            SyncMessage = "กำลังดึงข้อมูลสินค้า...";
            var productCount = await _syncService.SyncProductsAsync();

            // Sync Transaction ขึ้น Server
            SyncMessage = "กำลังส่ง Transaction...";
            var transactionCount = await _syncService.SyncTransactionsAsync();

            // อัพเดทเวลา Sync
            LastSyncTime = DateTime.Now;
            Preferences.Set("last_sync_time", LastSyncTime.Value.Ticks);
            UpdateLastSyncText();

            // โหลดสถิติใหม่
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

    /// <summary>
    /// นำเข้าสินค้าจาก Server ครั้งแรก
    /// </summary>
    [RelayCommand]
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

            // อัพเดทเวลา Sync
            LastSyncTime = DateTime.Now;
            Preferences.Set("last_sync_time", LastSyncTime.Value.Ticks);
            UpdateLastSyncText();

            // โหลดสถิติใหม่
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

    /// <summary>
    /// ล้างข้อมูลทั้งหมด
    /// </summary>
    [RelayCommand]
    private async Task ClearAllDataAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "⚠️ ล้างข้อมูลทั้งหมด",
            "การดำเนินการนี้จะลบข้อมูลสินค้า, Transaction และการตั้งค่าทั้งหมด!\n\nต้องการดำเนินการต่อ?",
            "ล้างข้อมูล", "ยกเลิก");

        if (!confirm) return;

        // ยืนยันอีกครั้ง
        bool doubleConfirm = await Shell.Current.DisplayAlert(
            "⚠️ ยืนยันการล้างข้อมูล",
            "คุณแน่ใจหรือไม่? ข้อมูลที่ถูกลบจะไม่สามารถกู้คืนได้!",
            "ยืนยัน", "ยกเลิก");

        if (!doubleConfirm) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังล้างข้อมูล...";

            // ล้าง Database
            await _syncService.ClearAllDataAsync();

            // ล้าง Preferences
            Preferences.Clear();

            // รีเซ็ตค่าเริ่มต้น
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

    /// <summary>
    /// ออกจากระบบ
    /// </summary>
    [RelayCommand]
    private async Task LogoutAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "ออกจากระบบ",
            "ต้องการออกจากระบบ?",
            "ออกจากระบบ", "ยกเลิก");

        if (!confirm) return;

        // ล้างข้อมูล Login
        Preferences.Remove("user_token");
        Preferences.Remove("user_name");

        // ไปหน้า Login
        await Shell.Current.GoToAsync("//login");
    }

    /// <summary>
    /// ดูข้อมูลแอป
    /// </summary>
    [RelayCommand]
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
public partial class PrinterInfo : ObservableObject
{
    public string Name { get; set; } = string.Empty;

    [ObservableProperty]
    private bool _isConnected;

    [ObservableProperty]
    private Color _backgroundColor = Color.FromArgb("#F3F4F6");

    [ObservableProperty]
    private Color _borderColor = Color.FromArgb("#E5E7EB");

    partial void OnIsConnectedChanged(bool value)
    {
        if (value)
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

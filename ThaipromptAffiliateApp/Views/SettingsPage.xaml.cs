using ThaipromptAffiliateApp.Services;

namespace ThaipromptAffiliateApp.Views;

/// <summary>
/// หน้าตั้งค่าแอปพลิเคชัน - Premium Settings Page
/// จัดการการแจ้งเตือน, ธีม, ความเป็นส่วนตัว, แคช และข้อมูลเกี่ยวกับแอป
/// </summary>
public partial class SettingsPage : ContentPage
{
    private readonly ISettingsService _settingsService;

    public SettingsPage()
    {
        InitializeComponent();

        // ใช้ DI หรือสร้าง instance ใหม่
        _settingsService = Application.Current?.Handler?.MauiContext?.Services
            .GetService<ISettingsService>() ?? new SettingsService();

        // โหลดค่าตั้งค่าปัจจุบัน
        LoadSettings();
    }

    protected override void OnAppearing()
    {
        base.OnAppearing();

        // อัพเดทข้อมูลที่อาจเปลี่ยนแปลง
        UpdateCacheSize();
        UpdateAppVersion();
    }

    // ============================================
    // LOAD SETTINGS
    // ============================================

    /// <summary>
    /// โหลดค่าตั้งค่าทั้งหมดจาก Storage
    /// </summary>
    private void LoadSettings()
    {
        // การแจ้งเตือน
        SwitchPushNotification.IsToggled = _settingsService.GetSetting("push_notification", true);
        SwitchNotificationSound.IsToggled = _settingsService.GetSetting("notification_sound", true);
        SwitchVibration.IsToggled = _settingsService.GetSetting("vibration", true);
        SwitchOrderUpdates.IsToggled = _settingsService.GetSetting("order_updates", true);
        SwitchCommissionAlerts.IsToggled = _settingsService.GetSetting("commission_alerts", true);

        // ธีมและการแสดงผล
        SwitchDarkMode.IsToggled = _settingsService.GetSetting("dark_mode", true);
        SwitchAnimations.IsToggled = _settingsService.GetSetting("animations", true);
        LblCurrentLanguage.Text = _settingsService.GetSetting("language", "ไทย");

        // ความเป็นส่วนตัว
        SwitchBiometric.IsToggled = _settingsService.GetSetting("biometric_login", false);
        SwitchAutoLogin.IsToggled = _settingsService.GetSetting("auto_login", true);
        SwitchAnalytics.IsToggled = _settingsService.GetSetting("analytics", true);

        // ข้อมูลและแคช
        SwitchWifiOnly.IsToggled = _settingsService.GetSetting("wifi_only", false);
    }

    /// <summary>
    /// อัพเดทขนาดแคช
    /// </summary>
    private async void UpdateCacheSize()
    {
        try
        {
            var cacheSize = await _settingsService.GetCacheSizeAsync();
            LblCacheSize.Text = $"ขนาด: {FormatFileSize(cacheSize)}";
        }
        catch
        {
            LblCacheSize.Text = "ขนาด: ไม่สามารถคำนวณได้";
        }
    }

    /// <summary>
    /// อัพเดทเวอร์ชันแอป
    /// </summary>
    private void UpdateAppVersion()
    {
        try
        {
            var version = AppInfo.Current.VersionString;
            var build = AppInfo.Current.BuildString;
            LblAppVersion.Text = $"{version} (Build {build})";
        }
        catch
        {
            LblAppVersion.Text = "1.0.0";
        }
    }

    /// <summary>
    /// แปลงขนาดไฟล์เป็นข้อความที่อ่านง่าย
    /// </summary>
    private static string FormatFileSize(long bytes)
    {
        string[] sizes = { "B", "KB", "MB", "GB" };
        double len = bytes;
        int order = 0;
        while (len >= 1024 && order < sizes.Length - 1)
        {
            order++;
            len /= 1024;
        }
        return $"{len:0.##} {sizes[order]}";
    }

    // ============================================
    // NOTIFICATION HANDLERS
    // ============================================

    /// <summary>
    /// เปิด/ปิดการแจ้งเตือนแบบ Push
    /// </summary>
    private async void OnPushNotificationToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("push_notification", e.Value);

        if (e.Value)
        {
            // ขอสิทธิ์และลงทะเบียน Push Notification
            var notificationService = Application.Current?.Handler?.MauiContext?.Services
                .GetService<INotificationService>();
            if (notificationService != null)
            {
                await notificationService.RegisterForPushNotificationsAsync();
            }
        }
    }

    /// <summary>
    /// เปิด/ปิดเสียงแจ้งเตือน
    /// </summary>
    private async void OnNotificationSoundToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("notification_sound", e.Value);
    }

    /// <summary>
    /// เปิด/ปิดการสั่น
    /// </summary>
    private async void OnVibrationToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("vibration", e.Value);

        if (e.Value)
        {
            // ทดสอบการสั่น
            try
            {
                Vibration.Default.Vibrate(TimeSpan.FromMilliseconds(100));
            }
            catch { }
        }
    }

    /// <summary>
    /// เปิด/ปิดการแจ้งเตือนอัพเดทคำสั่งซื้อ
    /// </summary>
    private async void OnOrderUpdatesToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("order_updates", e.Value);
    }

    /// <summary>
    /// เปิด/ปิดการแจ้งเตือนค่าคอมมิชชั่น
    /// </summary>
    private async void OnCommissionAlertsToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("commission_alerts", e.Value);
    }

    // ============================================
    // THEME & DISPLAY HANDLERS
    // ============================================

    /// <summary>
    /// เปิด/ปิดโหมดมืด
    /// </summary>
    private async void OnDarkModeToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("dark_mode", e.Value);

        // อัพเดท Theme ของแอป (ยังไม่ implement เต็มรูปแบบ)
        // Application.Current.UserAppTheme = e.Value ? AppTheme.Dark : AppTheme.Light;

        await DisplayAlert("โหมดมืด",
            e.Value ? "เปิดใช้งานโหมดมืด" : "แอปนี้ออกแบบมาสำหรับโหมดมืดโดยเฉพาะ",
            "ตกลง");
    }

    /// <summary>
    /// เปิด/ปิดอนิเมชั่น
    /// </summary>
    private async void OnAnimationsToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("animations", e.Value);
    }

    /// <summary>
    /// เลือกภาษา
    /// </summary>
    private async void OnLanguageTapped(object sender, EventArgs e)
    {
        var languages = new[] { "ไทย", "English", "中文", "日本語" };
        var result = await DisplayActionSheet("เลือกภาษา", "ยกเลิก", null, languages);

        if (result != null && result != "ยกเลิก")
        {
            _settingsService.SetSetting("language", result);
            LblCurrentLanguage.Text = result;
            await DisplayAlert("ภาษา", $"เปลี่ยนภาษาเป็น {result}\nจะมีผลหลังรีสตาร์ทแอป", "ตกลง");
        }
    }

    // ============================================
    // PRIVACY HANDLERS
    // ============================================

    /// <summary>
    /// เปิด/ปิดการเข้าสู่ระบบด้วยไบโอเมตริก
    /// </summary>
    private async void OnBiometricToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);

        if (e.Value)
        {
            // ตรวจสอบว่าอุปกรณ์รองรับไบโอเมตริกหรือไม่
            var isBiometricAvailable = await CheckBiometricAvailability();
            if (!isBiometricAvailable)
            {
                SwitchBiometric.IsToggled = false;
                await DisplayAlert("ไม่รองรับ",
                    "อุปกรณ์นี้ไม่รองรับการล็อกอินด้วยลายนิ้วมือ/Face ID",
                    "ตกลง");
                return;
            }
        }

        _settingsService.SetSetting("biometric_login", e.Value);
    }

    /// <summary>
    /// ตรวจสอบว่าอุปกรณ์รองรับไบโอเมตริก
    /// </summary>
    private async Task<bool> CheckBiometricAvailability()
    {
        // TODO: ใช้ Plugin.Fingerprint หรือ Native API
        await Task.Delay(100);
        return true; // Placeholder
    }

    /// <summary>
    /// เปิด/ปิดการจดจำการเข้าสู่ระบบ
    /// </summary>
    private async void OnAutoLoginToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("auto_login", e.Value);
    }

    /// <summary>
    /// เปิด/ปิดการส่งข้อมูลการใช้งาน
    /// </summary>
    private async void OnAnalyticsToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("analytics", e.Value);
    }

    // ============================================
    // DATA & CACHE HANDLERS
    // ============================================

    /// <summary>
    /// ล้างแคช
    /// </summary>
    private async void OnClearCacheTapped(object sender, EventArgs e)
    {
        var confirm = await DisplayAlert("ล้างแคช",
            "คุณต้องการล้างแคชทั้งหมดหรือไม่?\nรูปภาพและข้อมูลที่ cache ไว้จะถูกลบ",
            "ล้าง", "ยกเลิก");

        if (confirm)
        {
            try
            {
                await _settingsService.ClearCacheAsync();
                LblCacheSize.Text = "ขนาด: 0 B";
                await DisplayAlert("สำเร็จ", "ล้างแคชเรียบร้อยแล้ว", "ตกลง");
            }
            catch (Exception ex)
            {
                await DisplayAlert("ผิดพลาด", $"ไม่สามารถล้างแคชได้: {ex.Message}", "ตกลง");
            }
        }
    }

    /// <summary>
    /// ล้างประวัติการค้นหา
    /// </summary>
    private async void OnClearSearchHistoryTapped(object sender, EventArgs e)
    {
        var confirm = await DisplayAlert("ล้างประวัติการค้นหา",
            "คุณต้องการล้างประวัติการค้นหาทั้งหมดหรือไม่?",
            "ล้าง", "ยกเลิก");

        if (confirm)
        {
            _settingsService.SetSetting("search_history", new List<string>());
            await DisplayAlert("สำเร็จ", "ล้างประวัติการค้นหาเรียบร้อยแล้ว", "ตกลง");
        }
    }

    /// <summary>
    /// เปิด/ปิดการดาวน์โหลดเฉพาะ WiFi
    /// </summary>
    private async void OnWifiOnlyToggled(object sender, ToggledEventArgs e)
    {
        await AnimateToggle(sender as Switch);
        _settingsService.SetSetting("wifi_only", e.Value);
    }

    // ============================================
    // ABOUT HANDLERS
    // ============================================

    /// <summary>
    /// เปิดหน้าข้อกำหนดการใช้งาน
    /// </summary>
    private void OnTermsTapped(object sender, EventArgs e)
    {
        Application.Current!.MainPage = WebViewPage.CreateTermsPage();
    }

    /// <summary>
    /// เปิดหน้านโยบายความเป็นส่วนตัว
    /// </summary>
    private void OnPrivacyTapped(object sender, EventArgs e)
    {
        Application.Current!.MainPage = WebViewPage.CreatePrivacyPage();
    }

    /// <summary>
    /// เปิดหน้าติดต่อเรา
    /// </summary>
    private void OnContactTapped(object sender, EventArgs e)
    {
        Application.Current!.MainPage = WebViewPage.CreateContactPage();
    }

    /// <summary>
    /// เปิด Store เพื่อให้คะแนนแอป
    /// </summary>
    private async void OnRateAppTapped(object sender, EventArgs e)
    {
        try
        {
            // เปิด App Store/Play Store
#if ANDROID
            await Browser.OpenAsync(new Uri("market://details?id=com.thaiprompt.affiliate"));
#elif IOS
            await Browser.OpenAsync(new Uri("itms-apps://itunes.apple.com/app/idXXXXXXXXXX"));
#else
            await DisplayAlert("ให้คะแนน", "ขอบคุณที่สนับสนุนแอปของเรา!", "ตกลง");
#endif
        }
        catch
        {
            await DisplayAlert("ให้คะแนน", "ขอบคุณที่สนับสนุนแอปของเรา!", "ตกลง");
        }
    }

    // ============================================
    // HEADER BUTTON HANDLERS
    // ============================================

    /// <summary>
    /// กลับหน้าหลัก
    /// </summary>
    private async void OnBackTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnBack);
        Application.Current!.MainPage = new MainMenuPage();
    }

    /// <summary>
    /// รีเซ็ตการตั้งค่าทั้งหมด
    /// </summary>
    private async void OnResetTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnReset);

        var confirm = await DisplayAlert("รีเซ็ตการตั้งค่า",
            "คุณต้องการรีเซ็ตการตั้งค่าทั้งหมดเป็นค่าเริ่มต้นหรือไม่?",
            "รีเซ็ต", "ยกเลิก");

        if (confirm)
        {
            await _settingsService.ResetAllSettingsAsync();
            LoadSettings();
            await DisplayAlert("สำเร็จ", "รีเซ็ตการตั้งค่าเรียบร้อยแล้ว", "ตกลง");
        }
    }

    // ============================================
    // LOGOUT HANDLER
    // ============================================

    /// <summary>
    /// ออกจากระบบ
    /// </summary>
    private async void OnLogoutTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnLogout);

        var confirm = await DisplayAlert("ออกจากระบบ",
            "คุณต้องการออกจากระบบหรือไม่?",
            "ออกจากระบบ", "ยกเลิก");

        if (confirm)
        {
            // ล้างข้อมูล session
            var apiService = Application.Current?.Handler?.MauiContext?.Services
                .GetService<IApiService>();
            if (apiService != null)
            {
                apiService.ClearAuthentication();
            }

            // กลับไปหน้า Login
            Application.Current!.MainPage = new LoginPage();
        }
    }

    // ============================================
    // ANIMATION HELPERS
    // ============================================

    /// <summary>
    /// Animation กดปุ่ม
    /// </summary>
    private static async Task AnimateButton(View button)
    {
        await button.ScaleTo(0.9, 80, Easing.CubicIn);
        await button.ScaleTo(1.0, 80, Easing.CubicOut);
    }

    /// <summary>
    /// Animation Toggle Switch
    /// </summary>
    private static async Task AnimateToggle(Switch? toggle)
    {
        if (toggle == null) return;

        await toggle.ScaleTo(1.1, 50, Easing.CubicOut);
        await toggle.ScaleTo(1.0, 50, Easing.CubicIn);
    }
}

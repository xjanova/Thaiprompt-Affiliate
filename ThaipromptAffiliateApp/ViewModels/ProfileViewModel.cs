using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliateApp.Services;
using ThaipromptAffiliateApp.Views;

namespace ThaipromptAffiliateApp.ViewModels;

public class ProfileViewModel : BaseViewModel
{
    private readonly IApiService _apiService;
    private string _userName = "";
    private string _userEmail = "";
    private string _userInitial = "";
    private DateTime _memberSince;
    private int _totalCommissions;
    private string _userLevel = "Standard";
    private bool _notificationsEnabled = true;
    private bool _darkModeEnabled = false;

    public string UserName
    {
        get => _userName;
        set => SetProperty(ref _userName, value);
    }

    public string UserEmail
    {
        get => _userEmail;
        set => SetProperty(ref _userEmail, value);
    }

    public string UserInitial
    {
        get => _userInitial;
        set => SetProperty(ref _userInitial, value);
    }

    public DateTime MemberSince
    {
        get => _memberSince;
        set => SetProperty(ref _memberSince, value);
    }

    public int TotalCommissions
    {
        get => _totalCommissions;
        set => SetProperty(ref _totalCommissions, value);
    }

    public string UserLevel
    {
        get => _userLevel;
        set => SetProperty(ref _userLevel, value);
    }

    public bool NotificationsEnabled
    {
        get => _notificationsEnabled;
        set
        {
            SetProperty(ref _notificationsEnabled, value);
            _ = SaveNotificationSettingAsync(value);
        }
    }

    public bool DarkModeEnabled
    {
        get => _darkModeEnabled;
        set
        {
            SetProperty(ref _darkModeEnabled, value);
            _ = SaveDarkModeSettingAsync(value);
        }
    }

    public ICommand EditProfileCommand { get; }
    public ICommand ChangePasswordCommand { get; }
    public ICommand HelpCenterCommand { get; }
    public ICommand LogoutCommand { get; }

    public ProfileViewModel(IApiService apiService)
    {
        _apiService = apiService;

        EditProfileCommand = new AsyncRelayCommand(EditProfileAsync);
        ChangePasswordCommand = new AsyncRelayCommand(ChangePasswordAsync);
        HelpCenterCommand = new AsyncRelayCommand(OpenHelpCenterAsync);
        LogoutCommand = new AsyncRelayCommand(LogoutAsync);
    }

    public override async void OnAppearing()
    {
        base.OnAppearing();
        await LoadProfileAsync();
    }

    private async Task LoadProfileAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;

            var user = await _apiService.GetCurrentUserAsync();

            if (user != null)
            {
                UserName = user.Name;
                UserEmail = user.Email;
                UserInitial = !string.IsNullOrEmpty(user.Name)
                    ? user.Name[0].ToString().ToUpper()
                    : "?";
                MemberSince = user.CreatedAt;
                TotalCommissions = user.TotalCommissions;
                UserLevel = user.Level ?? "Standard";
            }
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถโหลดข้อมูลโปรไฟล์ได้: {ex.Message}",
                "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task EditProfileAsync()
    {
        await Shell.Current.DisplayAlert("แก้ไขโปรไฟล์",
            "ฟีเจอร์นี้กำลังพัฒนา",
            "ตกลง");
    }

    private async Task ChangePasswordAsync()
    {
        await Shell.Current.DisplayAlert("เปลี่ยนรหัสผ่าน",
            "ฟีเจอร์นี้กำลังพัฒนา",
            "ตกลง");
    }

    private async Task OpenHelpCenterAsync()
    {
        try
        {
            await Browser.OpenAsync("https://thaiprompt.com/help",
                BrowserLaunchMode.SystemPreferred);
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถเปิดศูนย์ช่วยเหลือได้: {ex.Message}",
                "ตกลง");
        }
    }

    private async Task LogoutAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "ยืนยันการออกจากระบบ",
            "คุณต้องการออกจากระบบใช่หรือไม่?",
            "ใช่",
            "ไม่");

        if (confirm)
        {
            await _apiService.LogoutAsync();
            App.Current!.MainPage = new NavigationPage(new LoginPage());
        }
    }

    private async Task SaveNotificationSettingAsync(bool enabled)
    {
        // Save notification setting to preferences
        Preferences.Set("notifications_enabled", enabled);
        await Task.CompletedTask;
    }

    private async Task SaveDarkModeSettingAsync(bool enabled)
    {
        // Save dark mode setting to preferences
        Preferences.Set("dark_mode_enabled", enabled);

        // Apply theme change (would need to implement theme switching)
        await Shell.Current.DisplayAlert("โหมดมืด",
            "ฟีเจอร์โหมดมืดกำลังพัฒนา",
            "ตกลง");
    }
}

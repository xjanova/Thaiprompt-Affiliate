using TP.POS.App.Views;
using TP.POS.App.ViewModels;
using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Data;

namespace TP.POS.App;

/// <summary>
/// Main Application Class
/// ตรวจสอบ Session และนำทางไปหน้าที่เหมาะสม
/// </summary>
public partial class App : Application
{
    private readonly PosDatabase _database;
    private readonly IAuthService _authService;
    private readonly IServiceProvider _serviceProvider;

    public App(PosDatabase database, IAuthService authService, IServiceProvider serviceProvider)
    {
        InitializeComponent();
        _database = database;
        _authService = authService;
        _serviceProvider = serviceProvider;

        // เริ่มต้น Database
        Task.Run(async () => await _database.InitializeAsync());

        // เริ่มต้นที่หน้า Login ก่อน แล้วตรวจสอบ Session
        MainPage = new NavigationPage(CreateLoginPage());

        // ตรวจสอบ Session ที่เก็บไว้
        CheckSessionAsync();
    }

    /// <summary>
    /// สร้างหน้า Login
    /// </summary>
    private LoginPage CreateLoginPage()
    {
        var viewModel = _serviceProvider.GetRequiredService<LoginViewModel>();
        return new LoginPage(viewModel);
    }

    /// <summary>
    /// ตรวจสอบ Session ที่เก็บไว้
    /// ถ้า Login อยู่แล้ว ไปหน้าหลักเลย
    /// </summary>
    private async void CheckSessionAsync()
    {
        try
        {
            var hasSession = await _authService.CheckSavedSessionAsync();
            if (hasSession && _authService.CurrentUser != null)
            {
                // มี Session อยู่แล้ว ไปหน้าหลัก
                await MainThread.InvokeOnMainThreadAsync(() =>
                {
                    MainPage = new AppShell();
                });
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"CheckSession error: {ex.Message}");
        }
    }

    /// <summary>
    /// เมื่อเริ่มแอป
    /// </summary>
    protected override void OnStart()
    {
        base.OnStart();
    }

    /// <summary>
    /// เมื่อ sleep
    /// </summary>
    protected override void OnSleep()
    {
        base.OnSleep();
    }

    /// <summary>
    /// เมื่อกลับมา
    /// </summary>
    protected override void OnResume()
    {
        base.OnResume();
    }
}

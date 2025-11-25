using Microsoft.Extensions.Logging;
using ThaipromptAffiliate.Services;
using ThaipromptAffiliate.ViewModels;
using ThaipromptAffiliate.Views;

namespace ThaipromptAffiliate;

/// <summary>
/// คลาสสำหรับตั้งค่าและสร้าง MAUI Application
///
/// หน้าที่:
/// - ตั้งค่า Dependency Injection
/// - ลงทะเบียน Services, ViewModels, Views
/// - ตั้งค่า Fonts และ Resources
/// </summary>
public static class MauiProgram
{
    /// <summary>
    /// สร้างและตั้งค่า MAUI App
    /// </summary>
    /// <returns>MauiApp instance</returns>
    public static MauiApp CreateMauiApp()
    {
        var builder = MauiApp.CreateBuilder();

        builder
            .UseMauiApp<App>()
            .ConfigureFonts(fonts =>
            {
                // ฟอนต์หลักของแอพ
                fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
                fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
                fonts.AddFont("OpenSans-Bold.ttf", "OpenSansBold");

                // ฟอนต์ไอคอน
                fonts.AddFont("MaterialIcons-Regular.ttf", "MaterialIcons");
                fonts.AddFont("FontAwesome6Free-Solid-900.otf", "FontAwesomeSolid");
                fonts.AddFont("FontAwesome6Free-Regular-400.otf", "FontAwesomeRegular");
            });

        // ลงทะเบียน Services (Singleton - ใช้ instance เดียวตลอด)
        builder.Services.AddSingleton<ApiService>();
        builder.Services.AddSingleton<ThemeService>();
        builder.Services.AddSingleton(SecureStorage.Default);
        builder.Services.AddSingleton(Preferences.Default);
        builder.Services.AddSingleton(Connectivity.Current);

        // ลงทะเบียน ViewModels (Transient - สร้างใหม่ทุกครั้ง)
        builder.Services.AddTransient<SplashViewModel>();
        builder.Services.AddTransient<HubSelectionViewModel>();
        builder.Services.AddTransient<LoginViewModel>();
        builder.Services.AddTransient<DashboardViewModel>();

        // ลงทะเบียน Views/Pages
        builder.Services.AddTransient<SplashPage>();
        builder.Services.AddTransient<HubSelectionPage>();
        builder.Services.AddTransient<LoginPage>();
        builder.Services.AddTransient<DashboardPage>();

#if DEBUG
        // เปิด logging ใน Debug mode
        builder.Logging.AddDebug();
#endif

        return builder.Build();
    }
}

using Microsoft.Extensions.Logging;
using ThaipromptAffiliateApp.Services;
using ThaipromptAffiliateApp.ViewModels;
using ThaipromptAffiliateApp.Views;

namespace ThaipromptAffiliateAppApp;

public static class MauiProgram
{
    public static MauiApp CreateMauiApp()
    {
        var builder = MauiApp.CreateBuilder();
        builder
            .UseMauiApp<App>()
            .ConfigureFonts(fonts =>
            {
                fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
                fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
                fonts.AddFont("OpenSans-Bold.ttf", "OpenSansBold");
            });

        // Register Services
        builder.Services.AddSingleton<ApiService>();
        builder.Services.AddSingleton<IConfigurationService, ConfigurationService>();
        builder.Services.AddSingleton<IThemeService, ThemeService>();

        // Register ViewModels
        builder.Services.AddTransient<HomeViewModel>();
        builder.Services.AddTransient<LoginViewModel>();
        builder.Services.AddTransient<DashboardViewModel>();
        builder.Services.AddTransient<CommissionsViewModel>();
        builder.Services.AddTransient<ReferralsViewModel>();
        builder.Services.AddTransient<ProfileViewModel>();

        // Register Views
        builder.Services.AddTransient<HomePage>();
        builder.Services.AddTransient<LoginPage>();
        builder.Services.AddTransient<DashboardPage>();
        builder.Services.AddTransient<CommissionsPage>();
        builder.Services.AddTransient<ReferralsPage>();
        builder.Services.AddTransient<ProfilePage>();

#if DEBUG
        builder.Logging.AddDebug();
#endif

        return builder.Build();
    }
}

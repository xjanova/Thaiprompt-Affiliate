using CommunityToolkit.Maui;
using Microsoft.Extensions.Logging;
using TP.POS.App.Services;
using TP.POS.App.ViewModels;
using TP.POS.App.Views;
using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Api;
using TP.POS.Infrastructure.Data;
using ZXing.Net.Maui.Controls;

namespace TP.POS.App;

/// <summary>
/// ตั้งค่า MAUI Application
/// </summary>
public static class MauiProgram
{
    public static MauiApp CreateMauiApp()
    {
        var builder = MauiApp.CreateBuilder();

        builder
            .UseMauiApp<App>()
            .UseMauiCommunityToolkit()
            .UseBarcodeReader()
            .ConfigureFonts(fonts =>
            {
                fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
                fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
                fonts.AddFont("Sarabun-Regular.ttf", "SarabunRegular");
                fonts.AddFont("Sarabun-Bold.ttf", "SarabunBold");
            });

        // ลงทะเบียน Services
        ConfigureServices(builder.Services);

#if DEBUG
        builder.Logging.AddDebug();
#endif

        return builder.Build();
    }

    /// <summary>
    /// ลงทะเบียน Services และ ViewModels
    /// </summary>
    private static void ConfigureServices(IServiceCollection services)
    {
        // Database
        var dbPath = Path.Combine(FileSystem.AppDataDirectory, "tppos.db");
        services.AddSingleton(new PosDatabase(dbPath));

        // API Client
        // TODO: อ่าน URL จาก Settings
        services.AddSingleton(new TpAffiliateApiClient("https://your-server.com"));

        // Services
        services.AddSingleton<IProductService, ProductService>();
        services.AddSingleton<ICartService, CartService>();
        services.AddSingleton<ITransactionService, TransactionService>();
        services.AddSingleton<ISyncService, SyncService>();
        services.AddSingleton<IPrinterService, PrinterService>();
        services.AddSingleton<IScannerService, ScannerService>();

        // ViewModels
        services.AddTransient<MainViewModel>();
        services.AddTransient<PosViewModel>();
        services.AddTransient<InventoryViewModel>();
        services.AddTransient<ReportsViewModel>();
        services.AddTransient<SettingsViewModel>();
        services.AddTransient<LoginViewModel>();

        // Pages
        services.AddTransient<MainPage>();
        services.AddTransient<PosPage>();
        services.AddTransient<InventoryPage>();
        services.AddTransient<ReportsPage>();
        services.AddTransient<SettingsPage>();
        services.AddTransient<LoginPage>();
    }
}

using CommunityToolkit.Maui;
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
                // ใช้ default fonts ก่อน - จะเพิ่ม custom fonts ในภายหลัง
                // fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
                // fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
                // fonts.AddFont("Sarabun-Regular.ttf", "SarabunRegular");
                // fonts.AddFont("Sarabun-Bold.ttf", "SarabunBold");
            });

        // ลงทะเบียน Services
        ConfigureServices(builder.Services);

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

        // API Client - เชื่อมต่อ TP-Affiliate Server
        services.AddSingleton<TpAffiliateApiClient>(new TpAffiliateApiClient("https://main.thaiprompt.online"));

        // Services
        services.AddSingleton<IAuthService, AuthService>();
        services.AddSingleton<IProductService, ProductService>();
        services.AddSingleton<ICartService, CartService>();
        services.AddSingleton<ITransactionService, TransactionService>();
        services.AddSingleton<ISyncService, SyncService>();
        services.AddSingleton<IPrinterService, PrinterService>();
        services.AddSingleton<IScannerService, ScannerService>();
        services.AddSingleton<IDualMonitorService, DualMonitorService>();

        // Connection Status Service - ตรวจสอบสถานะการเชื่อมต่อ Server
        services.AddSingleton<ConnectionStatusService>(sp =>
        {
            var apiClient = sp.GetRequiredService<TpAffiliateApiClient>();
            return new ConnectionStatusService(apiClient);
        });

        // ViewModels
        services.AddTransient<MainViewModel>();
        services.AddTransient<LoginViewModel>();
        services.AddTransient<PosViewModel>();
        services.AddTransient<CheckoutViewModel>();
        services.AddTransient<InventoryViewModel>();
        services.AddTransient<ReportsViewModel>();
        services.AddTransient<SettingsViewModel>();

        // Pages
        services.AddTransient<LoginPage>();
        services.AddTransient<MainPage>();
        services.AddTransient<PosPage>();
        services.AddTransient<CheckoutPage>();
        services.AddTransient<InventoryPage>();
        services.AddTransient<ReportsPage>();
        services.AddTransient<SettingsPage>();
        services.AddTransient<TransactionsPage>();
        services.AddTransient<ReceiptPage>();
        services.AddTransient<ProductDetailPage>();
        services.AddTransient<CustomerSelectPage>();
        services.AddTransient<StockAdjustmentPage>();
    }
}

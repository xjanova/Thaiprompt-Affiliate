using TP.POS.App.Services;

namespace TP.POS.App;

/// <summary>
/// Shell Navigation
/// </summary>
public partial class AppShell : Shell
{
    private ConnectionStatusService? _connectionService;

    public AppShell()
    {
        InitializeComponent();

        // ลงทะเบียน Routes
        Routing.RegisterRoute("login", typeof(Views.LoginPage));
        Routing.RegisterRoute("checkout", typeof(Views.CheckoutPage));
        Routing.RegisterRoute("receipt", typeof(Views.ReceiptPage));
        Routing.RegisterRoute("product-detail", typeof(Views.ProductDetailPage));
        Routing.RegisterRoute("customer-select", typeof(Views.CustomerSelectPage));
        Routing.RegisterRoute("stock-adjustment", typeof(Views.StockAdjustmentPage));

        // เริ่มตรวจสอบสถานะการเชื่อมต่อ
        InitializeConnectionStatus();
    }

    /// <summary>
    /// เริ่มตรวจสอบสถานะการเชื่อมต่อ
    /// </summary>
    private void InitializeConnectionStatus()
    {
        try
        {
            _connectionService = Application.Current?.Handler?.MauiContext?.Services
                .GetService<ConnectionStatusService>();

            if (_connectionService != null)
            {
                // Subscribe to status changes
                _connectionService.ConnectionStatusChanged += OnConnectionStatusChanged;

                // เริ่ม monitoring (ตรวจสอบทุก 30 วินาที)
                _connectionService.StartMonitoring(30);
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Failed to initialize connection status: {ex.Message}");
        }
    }

    /// <summary>
    /// Event handler เมื่อสถานะเปลี่ยน
    /// </summary>
    private void OnConnectionStatusChanged(object? sender, bool isOnline)
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            UpdateConnectionStatusUI(isOnline);
        });
    }

    /// <summary>
    /// อัพเดท UI แสดงสถานะการเชื่อมต่อ
    /// </summary>
    private void UpdateConnectionStatusUI(bool isOnline)
    {
        try
        {
            if (isOnline)
            {
                connectionStatusBorder.BackgroundColor = Color.FromArgb("#22C55E"); // Green
                statusIcon.Text = "●";
                statusText.Text = "Online";
            }
            else
            {
                connectionStatusBorder.BackgroundColor = Color.FromArgb("#EF4444"); // Red
                statusIcon.Text = "○";
                statusText.Text = "Offline";
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Failed to update connection UI: {ex.Message}");
        }
    }

    /// <summary>
    /// Cleanup เมื่อ Shell ถูกทำลาย
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();

        if (_connectionService != null)
        {
            _connectionService.ConnectionStatusChanged -= OnConnectionStatusChanged;
            _connectionService.StopMonitoring();
        }
    }
}

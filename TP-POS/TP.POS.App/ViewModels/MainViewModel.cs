using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าหลัก (Dashboard)
/// </summary>
public partial class MainViewModel : BaseViewModel
{
    private readonly ITransactionService _transactionService;
    private readonly IProductService _productService;
    private readonly ISyncService _syncService;

    #region Observable Properties

    /// <summary>
    /// ยอดขายวันนี้
    /// </summary>
    [ObservableProperty]
    private decimal _todaySales;

    /// <summary>
    /// จำนวน Transaction วันนี้
    /// </summary>
    [ObservableProperty]
    private int _todayTransactionCount;

    /// <summary>
    /// ยอดขายเฉลี่ยต่อ Transaction
    /// </summary>
    [ObservableProperty]
    private decimal _averageSale;

    /// <summary>
    /// จำนวนสินค้าทั้งหมด
    /// </summary>
    [ObservableProperty]
    private int _totalProductCount;

    /// <summary>
    /// จำนวนสินค้าที่สต็อกต่ำ
    /// </summary>
    [ObservableProperty]
    private int _lowStockCount;

    /// <summary>
    /// จำนวน Transaction ที่รอ Sync
    /// </summary>
    [ObservableProperty]
    private int _pendingSyncCount;

    /// <summary>
    /// ชื่อผู้ใช้ที่ Login
    /// </summary>
    [ObservableProperty]
    private string _userName = "Admin";

    /// <summary>
    /// ชื่อร้านค้า
    /// </summary>
    [ObservableProperty]
    private string _shopName = "ร้านค้า TP-POS";

    /// <summary>
    /// วันที่และเวลาปัจจุบัน
    /// </summary>
    [ObservableProperty]
    private string _currentDateTime = DateTime.Now.ToString("dddd, dd MMMM yyyy HH:mm", new System.Globalization.CultureInfo("th-TH"));

    /// <summary>
    /// สถานะการเชื่อมต่อ Server
    /// </summary>
    [ObservableProperty]
    private bool _isConnected;

    /// <summary>
    /// ข้อความสถานะ
    /// </summary>
    [ObservableProperty]
    private string _statusMessage = "พร้อมใช้งาน";

    /// <summary>
    /// กำลัง Sync
    /// </summary>
    [ObservableProperty]
    private bool _isSyncing;

    #endregion

    public MainViewModel(
        ITransactionService transactionService,
        IProductService productService,
        ISyncService syncService)
    {
        _transactionService = transactionService;
        _productService = productService;
        _syncService = syncService;

        Title = "หน้าหลัก";
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
            BusyMessage = "กำลังโหลดข้อมูล...";

            // โหลดข้อมูลร้าน
            ShopName = Preferences.Get("shop_name", "ร้านค้า TP-POS");
            UserName = Preferences.Get("user_name", "Admin");

            // อัพเดทวันที่/เวลา
            CurrentDateTime = DateTime.Now.ToString("dddd, dd MMMM yyyy HH:mm", new System.Globalization.CultureInfo("th-TH"));

            // โหลดสถิติ
            await LoadStatisticsAsync();

            // เช็คการเชื่อมต่อ
            await CheckConnectionAsync();
        }
        catch (Exception ex)
        {
            StatusMessage = $"เกิดข้อผิดพลาด: {ex.Message}";
        }
        finally
        {
            IsBusy = false;
        }
    }

    /// <summary>
    /// โหลดสถิติ
    /// </summary>
    private async Task LoadStatisticsAsync()
    {
        // ยอดขายวันนี้
        var todayStart = DateTime.Today;
        var todayEnd = todayStart.AddDays(1);
        var todayStats = await _transactionService.GetSalesSummaryAsync(todayStart, todayEnd);

        TodaySales = todayStats.TotalSales;
        TodayTransactionCount = todayStats.TransactionCount;
        AverageSale = todayStats.TransactionCount > 0
            ? todayStats.TotalSales / todayStats.TransactionCount
            : 0;

        // จำนวนสินค้า
        var allProducts = await _productService.GetAllAsync();
        TotalProductCount = allProducts.Count;

        // สินค้าสต็อกต่ำ
        var lowStock = await _productService.GetLowStockAsync();
        LowStockCount = lowStock.Count;

        // Transaction รอ Sync
        PendingSyncCount = await _syncService.GetPendingSyncCountAsync();
    }

    /// <summary>
    /// เช็คการเชื่อมต่อ Server
    /// </summary>
    private async Task CheckConnectionAsync()
    {
        try
        {
            var serverUrl = Preferences.Get("server_url", string.Empty);
            var apiKey = Preferences.Get("api_key", string.Empty);

            if (string.IsNullOrEmpty(serverUrl) || string.IsNullOrEmpty(apiKey))
            {
                IsConnected = false;
                StatusMessage = "ยังไม่ได้ตั้งค่า Server";
                return;
            }

            IsConnected = await _syncService.TestConnectionAsync(serverUrl, apiKey);
            StatusMessage = IsConnected ? "เชื่อมต่อ Server สำเร็จ" : "ไม่สามารถเชื่อมต่อ Server";
        }
        catch
        {
            IsConnected = false;
            StatusMessage = "ไม่สามารถเชื่อมต่อ Server";
        }
    }

    #region Commands

    /// <summary>
    /// ไปหน้าขาย
    /// </summary>
    [RelayCommand]
    private async Task GoToPosAsync()
    {
        await Shell.Current.GoToAsync("//pos");
    }

    /// <summary>
    /// ไปหน้าสต็อก
    /// </summary>
    [RelayCommand]
    private async Task GoToInventoryAsync()
    {
        await Shell.Current.GoToAsync("//inventory");
    }

    /// <summary>
    /// ไปหน้ารายงาน
    /// </summary>
    [RelayCommand]
    private async Task GoToReportsAsync()
    {
        await Shell.Current.GoToAsync("//reports");
    }

    /// <summary>
    /// ไปหน้าตั้งค่า
    /// </summary>
    [RelayCommand]
    private async Task GoToSettingsAsync()
    {
        await Shell.Current.GoToAsync("//settings");
    }

    /// <summary>
    /// Sync ข้อมูล
    /// </summary>
    [RelayCommand]
    private async Task SyncAsync()
    {
        if (IsSyncing) return;

        try
        {
            IsSyncing = true;
            StatusMessage = "กำลัง Sync...";

            // Sync สินค้า
            var productCount = await _syncService.SyncProductsAsync();

            // Sync Transaction
            var transactionCount = await _syncService.SyncTransactionsAsync();

            // อัพเดทเวลา Sync
            Preferences.Set("last_sync_time", DateTime.Now.Ticks);

            // รีโหลดสถิติ
            await LoadStatisticsAsync();

            StatusMessage = $"Sync สำเร็จ (สินค้า: {productCount}, Transaction: {transactionCount})";

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
        }
        catch (Exception ex)
        {
            StatusMessage = $"Sync ล้มเหลว: {ex.Message}";
        }
        finally
        {
            IsSyncing = false;
        }
    }

    /// <summary>
    /// รีเฟรชข้อมูล
    /// </summary>
    [RelayCommand]
    private async Task RefreshAsync()
    {
        await InitializeAsync();
    }

    #endregion
}

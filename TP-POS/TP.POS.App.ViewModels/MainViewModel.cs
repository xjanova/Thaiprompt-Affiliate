using System.Windows.Input;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าหลัก (Dashboard)
/// ใช้ manual implementation เพื่อหลีกเลี่ยง source generator conflicts
/// </summary>
public class MainViewModel : BaseViewModel
{
    private readonly ITransactionService _transactionService;
    private readonly IProductService _productService;
    private readonly ISyncService _syncService;

    #region Private Fields

    private decimal _todaySales;
    private int _todayTransactionCount;
    private decimal _averageSale;
    private int _totalProductCount;
    private int _lowStockCount;
    private int _pendingSyncCount;
    private string _userName = "Admin";
    private string _shopName = "ร้านค้า TP-POS";
    private string _currentDateTime = DateTime.Now.ToString("dddd, dd MMMM yyyy HH:mm", new System.Globalization.CultureInfo("th-TH"));
    private bool _isConnected;
    private string _statusMessage = "พร้อมใช้งาน";
    private bool _isSyncing;

    #endregion

    #region Properties

    public decimal TodaySales
    {
        get => _todaySales;
        set => SetProperty(ref _todaySales, value);
    }

    public int TodayTransactionCount
    {
        get => _todayTransactionCount;
        set => SetProperty(ref _todayTransactionCount, value);
    }

    public decimal AverageSale
    {
        get => _averageSale;
        set => SetProperty(ref _averageSale, value);
    }

    public int TotalProductCount
    {
        get => _totalProductCount;
        set => SetProperty(ref _totalProductCount, value);
    }

    public int LowStockCount
    {
        get => _lowStockCount;
        set => SetProperty(ref _lowStockCount, value);
    }

    public int PendingSyncCount
    {
        get => _pendingSyncCount;
        set => SetProperty(ref _pendingSyncCount, value);
    }

    public string UserName
    {
        get => _userName;
        set => SetProperty(ref _userName, value);
    }

    public string ShopName
    {
        get => _shopName;
        set => SetProperty(ref _shopName, value);
    }

    public string CurrentDateTime
    {
        get => _currentDateTime;
        set => SetProperty(ref _currentDateTime, value);
    }

    public bool IsConnected
    {
        get => _isConnected;
        set => SetProperty(ref _isConnected, value);
    }

    public string StatusMessage
    {
        get => _statusMessage;
        set => SetProperty(ref _statusMessage, value);
    }

    public bool IsSyncing
    {
        get => _isSyncing;
        set => SetProperty(ref _isSyncing, value);
    }

    #endregion

    #region Commands

    public ICommand GoToPosCommand { get; }
    public ICommand GoToInventoryCommand { get; }
    public ICommand GoToReportsCommand { get; }
    public ICommand GoToSettingsCommand { get; }
    public ICommand SyncCommand { get; }
    public ICommand RefreshCommand { get; }

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

        // Initialize commands
        GoToPosCommand = new Command(async () => await GoToPosAsync());
        GoToInventoryCommand = new Command(async () => await GoToInventoryAsync());
        GoToReportsCommand = new Command(async () => await GoToReportsAsync());
        GoToSettingsCommand = new Command(async () => await GoToSettingsAsync());
        SyncCommand = new Command(async () => await SyncAsync());
        RefreshCommand = new Command(async () => await RefreshAsync());
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

    #region Command Implementations

    private async Task GoToPosAsync()
    {
        await Shell.Current.GoToAsync("//pos");
    }

    private async Task GoToInventoryAsync()
    {
        await Shell.Current.GoToAsync("//inventory");
    }

    private async Task GoToReportsAsync()
    {
        await Shell.Current.GoToAsync("//reports");
    }

    private async Task GoToSettingsAsync()
    {
        await Shell.Current.GoToAsync("//settings");
    }

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

    private async Task RefreshAsync()
    {
        await InitializeAsync();
    }

    #endregion
}

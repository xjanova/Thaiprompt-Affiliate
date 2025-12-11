using System.Collections.ObjectModel;
using System.Windows.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้ารายงาน
/// ใช้ manual implementation เพื่อหลีกเลี่ยง source generator conflicts
/// </summary>
public class ReportsViewModel : BaseViewModel
{
    private readonly ITransactionService _transactionService;

    #region Private Fields

    private DateTime _startDate = DateTime.Today;
    private DateTime _endDate = DateTime.Today;
    private string _selectedPeriod = "today";
    private decimal _totalSales;
    private int _transactionCount;
    private decimal _averageSale;
    private int _totalItemsSold;
    private decimal _totalDiscount;
    private decimal _totalTax;
    private decimal _cashAmount;
    private decimal _cardQrAmount;
    private ObservableCollection<TransactionDisplayModel> _recentTransactions = new();
    private ObservableCollection<TopProductModel> _topProducts = new();
    private ObservableCollection<DailySalesModel> _dailySales = new();
    private ObservableCollection<PaymentBreakdownModel> _paymentBreakdown = new();
    private int _selectedTabIndex;

    #endregion

    #region Properties

    public DateTime StartDate
    {
        get => _startDate;
        set => SetProperty(ref _startDate, value);
    }

    public DateTime EndDate
    {
        get => _endDate;
        set => SetProperty(ref _endDate, value);
    }

    public string SelectedPeriod
    {
        get => _selectedPeriod;
        set => SetProperty(ref _selectedPeriod, value);
    }

    public decimal TotalSales
    {
        get => _totalSales;
        set => SetProperty(ref _totalSales, value);
    }

    public int TransactionCount
    {
        get => _transactionCount;
        set => SetProperty(ref _transactionCount, value);
    }

    public decimal AverageSale
    {
        get => _averageSale;
        set => SetProperty(ref _averageSale, value);
    }

    public int TotalItemsSold
    {
        get => _totalItemsSold;
        set => SetProperty(ref _totalItemsSold, value);
    }

    public decimal TotalDiscount
    {
        get => _totalDiscount;
        set => SetProperty(ref _totalDiscount, value);
    }

    public decimal TotalTax
    {
        get => _totalTax;
        set => SetProperty(ref _totalTax, value);
    }

    public decimal CashAmount
    {
        get => _cashAmount;
        set => SetProperty(ref _cashAmount, value);
    }

    public decimal CardQrAmount
    {
        get => _cardQrAmount;
        set => SetProperty(ref _cardQrAmount, value);
    }

    public ObservableCollection<TransactionDisplayModel> RecentTransactions
    {
        get => _recentTransactions;
        set => SetProperty(ref _recentTransactions, value);
    }

    public ObservableCollection<TopProductModel> TopProducts
    {
        get => _topProducts;
        set => SetProperty(ref _topProducts, value);
    }

    public ObservableCollection<DailySalesModel> DailySales
    {
        get => _dailySales;
        set => SetProperty(ref _dailySales, value);
    }

    public ObservableCollection<PaymentBreakdownModel> PaymentBreakdown
    {
        get => _paymentBreakdown;
        set => SetProperty(ref _paymentBreakdown, value);
    }

    public int SelectedTabIndex
    {
        get => _selectedTabIndex;
        set => SetProperty(ref _selectedTabIndex, value);
    }

    #endregion

    #region Commands

    public ICommand SelectPeriodCommand { get; }
    public ICommand RefreshCommand { get; }
    public ICommand ViewTransactionCommand { get; }
    public ICommand PrintXReportCommand { get; }
    public ICommand SelectTabCommand { get; }

    #endregion

    public ReportsViewModel(ITransactionService transactionService)
    {
        _transactionService = transactionService;
        Title = "รายงาน";

        // Initialize commands
        SelectPeriodCommand = new Command<string>(async (period) => await SelectPeriodAsync(period));
        RefreshCommand = new Command(async () => await RefreshAsync());
        ViewTransactionCommand = new Command<TransactionDisplayModel>(async (trans) => await ViewTransactionAsync(trans));
        PrintXReportCommand = new Command(async () => await PrintXReportAsync());
        SelectTabCommand = new Command<int>(SelectTab);
    }

    public async Task InitializeAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังโหลดรายงาน...";

            await LoadReportDataAsync();
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task LoadReportDataAsync()
    {
        var transactions = await _transactionService.GetByDateRangeAsync(StartDate, EndDate.AddDays(1).AddSeconds(-1));

        CalculateStatistics(transactions);
        LoadRecentTransactions(transactions);
        await LoadTopProductsAsync(transactions);
        LoadDailySales(transactions);
        LoadPaymentBreakdown(transactions);
    }

    private void CalculateStatistics(List<Transaction> transactions)
    {
        var completedTrans = transactions.Where(t => t.Status == TransactionStatus.Completed).ToList();

        TotalSales = completedTrans.Sum(t => t.TotalAmount);
        TransactionCount = completedTrans.Count;
        AverageSale = TransactionCount > 0 ? TotalSales / TransactionCount : 0;
        TotalDiscount = completedTrans.Sum(t => t.DiscountAmount);
        TotalTax = completedTrans.Sum(t => t.TaxAmount);
        TotalItemsSold = completedTrans.Sum(t => t.TotalItems);

        CashAmount = completedTrans
            .Where(t => t.PaymentMethod == PaymentMethod.Cash)
            .Sum(t => t.TotalAmount);

        CardQrAmount = completedTrans
            .Where(t => t.PaymentMethod != PaymentMethod.Cash)
            .Sum(t => t.TotalAmount);
    }

    private void LoadRecentTransactions(List<Transaction> transactions)
    {
        RecentTransactions.Clear();

        var recent = transactions
            .Where(t => t.Status == TransactionStatus.Completed)
            .OrderByDescending(t => t.TransactionDate)
            .Take(20);

        foreach (var trans in recent)
        {
            RecentTransactions.Add(new TransactionDisplayModel
            {
                Id = trans.Id,
                ReceiptNumber = trans.ReceiptNumber,
                TransactionDate = trans.TransactionDate,
                TotalAmount = trans.TotalAmount,
                PaymentMethodIcon = trans.PaymentMethod.GetIcon(),
                PaymentMethodName = trans.PaymentMethod.ToThaiName(),
                ItemCount = trans.TotalItems,
                CustomerName = trans.CustomerName ?? "ลูกค้าทั่วไป"
            });
        }
    }

    private async Task LoadTopProductsAsync(List<Transaction> transactions)
    {
        TopProducts.Clear();

        var allItems = new List<TransactionItem>();
        foreach (var trans in transactions.Where(t => t.Status == TransactionStatus.Completed))
        {
            var items = await _transactionService.GetItemsAsync(trans.Id);
            allItems.AddRange(items);
        }

        var topProducts = allItems
            .GroupBy(i => new { i.ProductId, i.ProductName })
            .Select(g => new TopProductModel
            {
                ProductName = g.Key.ProductName,
                QuantitySold = g.Sum(i => i.Quantity),
                TotalRevenue = g.Sum(i => i.LineTotal)
            })
            .OrderByDescending(p => p.QuantitySold)
            .Take(10);

        int rank = 1;
        foreach (var product in topProducts)
        {
            product.Rank = rank++;
            TopProducts.Add(product);
        }
    }

    private void LoadDailySales(List<Transaction> transactions)
    {
        DailySales.Clear();

        var dailyData = transactions
            .Where(t => t.Status == TransactionStatus.Completed)
            .GroupBy(t => t.TransactionDate.Date)
            .Select(g => new DailySalesModel
            {
                Date = g.Key,
                DateText = g.Key.ToString("dd/MM"),
                DayName = GetThaiDayName(g.Key.DayOfWeek),
                TotalSales = g.Sum(t => t.TotalAmount),
                TransactionCount = g.Count()
            })
            .OrderBy(d => d.Date);

        foreach (var day in dailyData)
        {
            DailySales.Add(day);
        }
    }

    private void LoadPaymentBreakdown(List<Transaction> transactions)
    {
        PaymentBreakdown.Clear();

        var completedTrans = transactions.Where(t => t.Status == TransactionStatus.Completed);
        var total = completedTrans.Sum(t => t.TotalAmount);

        var breakdown = completedTrans
            .GroupBy(t => t.PaymentMethod)
            .Select(g => new PaymentBreakdownModel
            {
                PaymentMethod = g.Key,
                Icon = g.Key.GetIcon(),
                Name = g.Key.ToThaiName(),
                Amount = g.Sum(t => t.TotalAmount),
                Count = g.Count(),
                Percentage = total > 0 ? (g.Sum(t => t.TotalAmount) / total) * 100 : 0
            })
            .OrderByDescending(p => p.Amount);

        foreach (var item in breakdown)
        {
            PaymentBreakdown.Add(item);
        }
    }

    private string GetThaiDayName(DayOfWeek day)
    {
        return day switch
        {
            DayOfWeek.Sunday => "อา.",
            DayOfWeek.Monday => "จ.",
            DayOfWeek.Tuesday => "อ.",
            DayOfWeek.Wednesday => "พ.",
            DayOfWeek.Thursday => "พฤ.",
            DayOfWeek.Friday => "ศ.",
            DayOfWeek.Saturday => "ส.",
            _ => ""
        };
    }

    #region Command Implementations

    private async Task SelectPeriodAsync(string period)
    {
        SelectedPeriod = period;

        switch (period)
        {
            case "today":
                StartDate = DateTime.Today;
                EndDate = DateTime.Today;
                break;
            case "yesterday":
                StartDate = DateTime.Today.AddDays(-1);
                EndDate = DateTime.Today.AddDays(-1);
                break;
            case "week":
                StartDate = DateTime.Today.AddDays(-7);
                EndDate = DateTime.Today;
                break;
            case "month":
                StartDate = new DateTime(DateTime.Today.Year, DateTime.Today.Month, 1);
                EndDate = DateTime.Today;
                break;
            case "custom":
                return;
        }

        await InitializeAsync();
    }

    private async Task RefreshAsync()
    {
        await InitializeAsync();
    }

    private async Task ViewTransactionAsync(TransactionDisplayModel transaction)
    {
        await Shell.Current.GoToAsync("receipt", new Dictionary<string, object>
        {
            { "TransactionId", transaction.Id }
        });
    }

    private async Task PrintXReportAsync()
    {
        await Shell.Current.DisplayAlert("พิมพ์รายงาน X", "กำลังพัฒนา...", "ตกลง");
    }

    private void SelectTab(int tabIndex)
    {
        SelectedTabIndex = tabIndex;
    }

    #endregion
}

#region Helper Models

/// <summary>
/// Model สำหรับแสดง Transaction
/// </summary>
public class TransactionDisplayModel
{
    public int Id { get; set; }
    public string ReceiptNumber { get; set; } = string.Empty;
    public DateTime TransactionDate { get; set; }
    public decimal TotalAmount { get; set; }
    public string PaymentMethodIcon { get; set; } = string.Empty;
    public string PaymentMethodName { get; set; } = string.Empty;
    public int ItemCount { get; set; }
    public string CustomerName { get; set; } = string.Empty;
}

/// <summary>
/// Model สำหรับสินค้าขายดี
/// </summary>
public class TopProductModel
{
    public int Rank { get; set; }
    public string ProductName { get; set; } = string.Empty;
    public int QuantitySold { get; set; }
    public decimal TotalRevenue { get; set; }
}

/// <summary>
/// Model สำหรับยอดขายรายวัน
/// </summary>
public class DailySalesModel
{
    public DateTime Date { get; set; }
    public string DateText { get; set; } = string.Empty;
    public string DayName { get; set; } = string.Empty;
    public decimal TotalSales { get; set; }
    public int TransactionCount { get; set; }
}

/// <summary>
/// Model สำหรับแบ่งตามช่องทางชำระ
/// </summary>
public class PaymentBreakdownModel
{
    public PaymentMethod PaymentMethod { get; set; }
    public string Icon { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public decimal Amount { get; set; }
    public int Count { get; set; }
    public decimal Percentage { get; set; }
}

#endregion

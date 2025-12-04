using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้ารายงาน
/// </summary>
public partial class ReportsViewModel : BaseViewModel
{
    private readonly ITransactionService _transactionService;

    #region Observable Properties

    /// <summary>
    /// วันที่เริ่มต้น
    /// </summary>
    [ObservableProperty]
    private DateTime _startDate = DateTime.Today;

    /// <summary>
    /// วันที่สิ้นสุด
    /// </summary>
    [ObservableProperty]
    private DateTime _endDate = DateTime.Today;

    /// <summary>
    /// ช่วงเวลาที่เลือก
    /// </summary>
    [ObservableProperty]
    private string _selectedPeriod = "today";

    /// <summary>
    /// ยอดขายรวม
    /// </summary>
    [ObservableProperty]
    private decimal _totalSales;

    /// <summary>
    /// จำนวนรายการขาย
    /// </summary>
    [ObservableProperty]
    private int _transactionCount;

    /// <summary>
    /// ยอดขายเฉลี่ยต่อรายการ
    /// </summary>
    [ObservableProperty]
    private decimal _averageSale;

    /// <summary>
    /// จำนวนสินค้าที่ขาย
    /// </summary>
    [ObservableProperty]
    private int _totalItemsSold;

    /// <summary>
    /// ส่วนลดรวม
    /// </summary>
    [ObservableProperty]
    private decimal _totalDiscount;

    /// <summary>
    /// ภาษีรวม
    /// </summary>
    [ObservableProperty]
    private decimal _totalTax;

    /// <summary>
    /// ยอดเงินสด
    /// </summary>
    [ObservableProperty]
    private decimal _cashAmount;

    /// <summary>
    /// ยอด QR/บัตร
    /// </summary>
    [ObservableProperty]
    private decimal _cardQrAmount;

    /// <summary>
    /// รายการขายล่าสุด
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<TransactionDisplayModel> _recentTransactions = new();

    /// <summary>
    /// สินค้าขายดี
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<TopProductModel> _topProducts = new();

    /// <summary>
    /// ยอดขายรายวัน (สำหรับ Chart)
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<DailySalesModel> _dailySales = new();

    /// <summary>
    /// รายการตามช่องทางชำระ
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<PaymentBreakdownModel> _paymentBreakdown = new();

    /// <summary>
    /// Tab ที่เลือก
    /// </summary>
    [ObservableProperty]
    private int _selectedTabIndex;

    #endregion

    public ReportsViewModel(ITransactionService transactionService)
    {
        _transactionService = transactionService;
        Title = "รายงาน";
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

    /// <summary>
    /// โหลดข้อมูลรายงาน
    /// </summary>
    private async Task LoadReportDataAsync()
    {
        // ดึงรายการขาย
        var transactions = await _transactionService.GetByDateRangeAsync(StartDate, EndDate.AddDays(1).AddSeconds(-1));

        // คำนวณสถิติ
        CalculateStatistics(transactions);

        // โหลดรายการล่าสุด
        LoadRecentTransactions(transactions);

        // โหลดสินค้าขายดี
        await LoadTopProductsAsync(transactions);

        // โหลดยอดขายรายวัน
        LoadDailySales(transactions);

        // โหลดแบ่งตามช่องทางชำระ
        LoadPaymentBreakdown(transactions);
    }

    /// <summary>
    /// คำนวณสถิติ
    /// </summary>
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

    /// <summary>
    /// โหลดรายการขายล่าสุด
    /// </summary>
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

    /// <summary>
    /// โหลดสินค้าขายดี
    /// </summary>
    private async Task LoadTopProductsAsync(List<Transaction> transactions)
    {
        TopProducts.Clear();

        // ดึงรายการสินค้าจากทุก transaction
        var allItems = new List<TransactionItem>();
        foreach (var trans in transactions.Where(t => t.Status == TransactionStatus.Completed))
        {
            var items = await _transactionService.GetItemsAsync(trans.Id);
            allItems.AddRange(items);
        }

        // Group by product และนับ
        var topProducts = allItems
            .GroupBy(i => new { i.ProductId, i.ProductName })
            .Select(g => new TopProductModel
            {
                ProductName = g.Key.ProductName,
                QuantitySold = g.Sum(i => i.Quantity),
                TotalRevenue = g.Sum(i => i.TotalPrice)
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

    /// <summary>
    /// โหลดยอดขายรายวัน
    /// </summary>
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

    /// <summary>
    /// โหลดแบ่งตามช่องทางชำระ
    /// </summary>
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

    /// <summary>
    /// แปลงชื่อวันเป็นภาษาไทย
    /// </summary>
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

    #region Commands

    /// <summary>
    /// เลือกช่วงเวลา
    /// </summary>
    [RelayCommand]
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
                // แสดง date picker ให้ผู้ใช้เลือก
                return;
        }

        await InitializeAsync();
    }

    /// <summary>
    /// รีเฟรชรายงาน
    /// </summary>
    [RelayCommand]
    private async Task RefreshAsync()
    {
        await InitializeAsync();
    }

    /// <summary>
    /// ดูรายละเอียด Transaction
    /// </summary>
    [RelayCommand]
    private async Task ViewTransactionAsync(TransactionDisplayModel transaction)
    {
        await Shell.Current.GoToAsync("receipt", new Dictionary<string, object>
        {
            { "TransactionId", transaction.Id }
        });
    }

    /// <summary>
    /// พิมพ์รายงาน X
    /// </summary>
    [RelayCommand]
    private async Task PrintXReportAsync()
    {
        await Shell.Current.DisplayAlert("พิมพ์รายงาน X", "กำลังพัฒนา...", "ตกลง");
    }

    /// <summary>
    /// เลือก Tab
    /// </summary>
    [RelayCommand]
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

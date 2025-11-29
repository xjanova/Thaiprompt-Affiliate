using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าขายสินค้า
/// </summary>
public partial class PosViewModel : BaseViewModel
{
    private readonly IProductService _productService;
    private readonly ICartService _cartService;
    private readonly ISyncService _syncService;
    private readonly IScannerService _scannerService;

    #region Observable Properties

    /// <summary>
    /// ข้อความค้นหา
    /// </summary>
    [ObservableProperty]
    private string _searchText = string.Empty;

    /// <summary>
    /// หมวดหมู่ที่เลือก
    /// </summary>
    [ObservableProperty]
    private Category? _selectedCategory;

    /// <summary>
    /// รายการสินค้า
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<ProductDisplayModel> _products = new();

    /// <summary>
    /// รายการหมวดหมู่
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<CategoryDisplayModel> _categories = new();

    /// <summary>
    /// รายการในตะกร้า
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<CartItem> _cartItems = new();

    /// <summary>
    /// ยอดรวมก่อนส่วนลด
    /// </summary>
    [ObservableProperty]
    private decimal _subtotal;

    /// <summary>
    /// ส่วนลด
    /// </summary>
    [ObservableProperty]
    private decimal _discountAmount;

    /// <summary>
    /// ภาษี
    /// </summary>
    [ObservableProperty]
    private decimal _taxAmount;

    /// <summary>
    /// ยอดสุทธิ
    /// </summary>
    [ObservableProperty]
    private decimal _totalAmount;

    /// <summary>
    /// มีสินค้าในตะกร้าหรือไม่
    /// </summary>
    [ObservableProperty]
    private bool _hasItems;

    /// <summary>
    /// สถานะออนไลน์
    /// </summary>
    [ObservableProperty]
    private bool _isOnline;

    /// <summary>
    /// สถานะ Sync
    /// </summary>
    [ObservableProperty]
    private string _syncStatusText = "ออฟไลน์";

    /// <summary>
    /// สี Sync Status
    /// </summary>
    [ObservableProperty]
    private Color _syncStatusColor = Colors.Gray;

    /// <summary>
    /// สีพื้นหลังปุ่ม "ทั้งหมด"
    /// </summary>
    [ObservableProperty]
    private Color _allCategoryBackground = Color.FromArgb("#22C55E");

    /// <summary>
    /// สีตัวอักษรปุ่ม "ทั้งหมด"
    /// </summary>
    [ObservableProperty]
    private Color _allCategoryTextColor = Colors.White;

    #endregion

    public PosViewModel(
        IProductService productService,
        ICartService cartService,
        ISyncService syncService,
        IScannerService scannerService)
    {
        _productService = productService;
        _cartService = cartService;
        _syncService = syncService;
        _scannerService = scannerService;

        Title = "ขายสินค้า";

        // รับ Event จาก Cart
        _cartService.CartChanged += OnCartChanged;

        // รับ Event จาก Scanner
        _scannerService.BarcodeScanned += OnBarcodeScanned;

        // รับ Event จาก Sync
        _syncService.SyncStatusChanged += OnSyncStatusChanged;
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

            // โหลดหมวดหมู่
            await LoadCategoriesAsync();

            // โหลดสินค้า
            await LoadProductsAsync();

            // โหลดตะกร้า
            await LoadCartAsync();

            // เริ่มฟังบาร์โค้ด
            await _scannerService.StartListeningAsync();

            // ตรวจสอบการเชื่อมต่อ
            IsOnline = await _syncService.CheckConnectivityAsync();
            UpdateSyncStatus();
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

    #region Load Data

    /// <summary>
    /// โหลดหมวดหมู่
    /// </summary>
    private async Task LoadCategoriesAsync()
    {
        var categories = await _productService.GetCategoriesAsync();
        Categories.Clear();

        foreach (var cat in categories)
        {
            Categories.Add(new CategoryDisplayModel
            {
                Id = cat.Id,
                Name = cat.Name,
                Icon = cat.Icon,
                Color = cat.Color,
                BackgroundColor = Color.FromArgb("#F3F4F6"),
                TextColor = Color.FromArgb("#374151")
            });
        }
    }

    /// <summary>
    /// โหลดสินค้า
    /// </summary>
    private async Task LoadProductsAsync()
    {
        var products = await _productService.SearchAsync(SearchText, SelectedCategory?.Id);
        Products.Clear();

        foreach (var product in products)
        {
            Products.Add(new ProductDisplayModel
            {
                Id = product.Id,
                Sku = product.Sku,
                Barcode = product.Barcode,
                Name = product.Name,
                SalePrice = product.SalePrice,
                CostPrice = product.CostPrice,
                StockQuantity = product.StockQuantity,
                ImageUrl = product.ImageUrl ?? "product_placeholder.png",
                TaxRate = product.TaxRate,
                StockStatusColor = product.StockQuantity > product.ReorderLevel
                    ? Color.FromArgb("#22C55E")
                    : product.StockQuantity > 0
                        ? Color.FromArgb("#F59E0B")
                        : Color.FromArgb("#EF4444")
            });
        }
    }

    /// <summary>
    /// โหลดตะกร้า
    /// </summary>
    private async Task LoadCartAsync()
    {
        var items = await _cartService.GetAllItemsAsync();
        CartItems = new ObservableCollection<CartItem>(items);

        await UpdateCartSummaryAsync();
    }

    /// <summary>
    /// อัพเดทสรุปตะกร้า
    /// </summary>
    private async Task UpdateCartSummaryAsync()
    {
        var summary = await _cartService.GetSummaryAsync();

        Subtotal = summary.Subtotal;
        DiscountAmount = summary.DiscountAmount;
        TaxAmount = summary.TaxAmount;
        TotalAmount = summary.TotalAmount;
        HasItems = summary.ItemCount > 0;
    }

    #endregion

    #region Commands

    /// <summary>
    /// ค้นหาสินค้า
    /// </summary>
    [RelayCommand]
    private async Task SearchAsync()
    {
        await LoadProductsAsync();
    }

    /// <summary>
    /// เลือกหมวดหมู่
    /// </summary>
    [RelayCommand]
    private async Task SelectCategoryAsync(CategoryDisplayModel? category)
    {
        // อัพเดท UI
        foreach (var cat in Categories)
        {
            cat.BackgroundColor = Color.FromArgb("#F3F4F6");
            cat.TextColor = Color.FromArgb("#374151");
        }

        if (category == null)
        {
            SelectedCategory = null;
            AllCategoryBackground = Color.FromArgb("#22C55E");
            AllCategoryTextColor = Colors.White;
        }
        else
        {
            SelectedCategory = new Category { Id = category.Id, Name = category.Name };
            AllCategoryBackground = Color.FromArgb("#F3F4F6");
            AllCategoryTextColor = Color.FromArgb("#374151");

            category.BackgroundColor = Color.FromArgb("#22C55E");
            category.TextColor = Colors.White;
        }

        await LoadProductsAsync();
    }

    /// <summary>
    /// สแกนบาร์โค้ด
    /// </summary>
    [RelayCommand]
    private async Task ScanBarcodeAsync()
    {
        var barcode = await _scannerService.ScanWithCameraAsync();
        if (!string.IsNullOrEmpty(barcode))
        {
            await AddByBarcodeAsync(barcode);
        }
    }

    /// <summary>
    /// เพิ่มสินค้าลงตะกร้า
    /// </summary>
    [RelayCommand]
    private async Task AddToCartAsync(ProductDisplayModel product)
    {
        if (product.StockQuantity <= 0)
        {
            await Shell.Current.DisplayAlert("สินค้าหมด", "สินค้านี้หมดสต็อกแล้ว", "ตกลง");
            return;
        }

        var fullProduct = await _productService.GetByIdAsync(product.Id);
        if (fullProduct != null)
        {
            await _cartService.AddItemAsync(fullProduct);

            // Haptic feedback
            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
        }
    }

    /// <summary>
    /// เพิ่มสินค้าด้วยบาร์โค้ด
    /// </summary>
    private async Task AddByBarcodeAsync(string barcode)
    {
        var result = await _cartService.AddByBarcodeAsync(barcode);
        if (result == null)
        {
            await Shell.Current.DisplayAlert("ไม่พบสินค้า", $"ไม่พบสินค้าบาร์โค้ด: {barcode}", "ตกลง");
        }
        else
        {
            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
        }
    }

    /// <summary>
    /// ลบสินค้าออกจากตะกร้า
    /// </summary>
    [RelayCommand]
    private async Task RemoveFromCartAsync(CartItem item)
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "ยืนยันการลบ",
            $"ต้องการลบ {item.ProductName} ออกจากตะกร้า?",
            "ลบ", "ยกเลิก");

        if (confirm)
        {
            await _cartService.RemoveItemAsync(item.Id);
        }
    }

    /// <summary>
    /// เพิ่มจำนวน
    /// </summary>
    [RelayCommand]
    private async Task IncrementAsync(CartItem item)
    {
        await _cartService.IncrementQuantityAsync(item.Id);
    }

    /// <summary>
    /// ลดจำนวน
    /// </summary>
    [RelayCommand]
    private async Task DecrementAsync(CartItem item)
    {
        if (item.Quantity > 1)
        {
            await _cartService.DecrementQuantityAsync(item.Id);
        }
        else
        {
            await RemoveFromCartAsync(item);
        }
    }

    /// <summary>
    /// ล้างตะกร้า
    /// </summary>
    [RelayCommand]
    private async Task ClearCartAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "ยืนยัน",
            "ต้องการล้างตะกร้าทั้งหมด?",
            "ล้าง", "ยกเลิก");

        if (confirm)
        {
            await _cartService.ClearAsync();
        }
    }

    /// <summary>
    /// ชำระเงิน
    /// </summary>
    [RelayCommand]
    private async Task CheckoutAsync()
    {
        if (!HasItems) return;

        await Shell.Current.GoToAsync("checkout", new Dictionary<string, object>
        {
            { "TotalAmount", TotalAmount }
        });
    }

    #endregion

    #region Event Handlers

    /// <summary>
    /// เมื่อตะกร้าเปลี่ยน
    /// </summary>
    private async void OnCartChanged(object? sender, CartChangedEventArgs e)
    {
        await MainThread.InvokeOnMainThreadAsync(async () =>
        {
            await LoadCartAsync();
        });
    }

    /// <summary>
    /// เมื่อสแกนบาร์โค้ด
    /// </summary>
    private async void OnBarcodeScanned(object? sender, BarcodeScannedEventArgs e)
    {
        await MainThread.InvokeOnMainThreadAsync(async () =>
        {
            await AddByBarcodeAsync(e.Barcode);
        });
    }

    /// <summary>
    /// เมื่อสถานะ Sync เปลี่ยน
    /// </summary>
    private void OnSyncStatusChanged(object? sender, SyncStatusEventArgs e)
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            IsOnline = e.IsOnline;
            UpdateSyncStatus();
        });
    }

    /// <summary>
    /// อัพเดทสถานะ Sync
    /// </summary>
    private void UpdateSyncStatus()
    {
        if (IsOnline)
        {
            SyncStatusText = "ออนไลน์";
            SyncStatusColor = Color.FromArgb("#22C55E");
        }
        else
        {
            SyncStatusText = "ออฟไลน์";
            SyncStatusColor = Color.FromArgb("#6B7280");
        }
    }

    #endregion
}

#region Display Models

/// <summary>
/// Model สำหรับแสดงสินค้า
/// </summary>
public partial class ProductDisplayModel : ObservableObject
{
    public int Id { get; set; }
    public string Sku { get; set; } = string.Empty;
    public string? Barcode { get; set; }
    public string Name { get; set; } = string.Empty;
    public decimal SalePrice { get; set; }
    public decimal CostPrice { get; set; }
    public int StockQuantity { get; set; }
    public string ImageUrl { get; set; } = "product_placeholder.png";
    public decimal TaxRate { get; set; }
    public Color StockStatusColor { get; set; } = Colors.Green;
}

/// <summary>
/// Model สำหรับแสดงหมวดหมู่
/// </summary>
public partial class CategoryDisplayModel : ObservableObject
{
    public int Id { get; set; }
    public string Name { get; set; } = string.Empty;
    public string? Icon { get; set; }
    public string Color { get; set; } = "#22C55E";

    [ObservableProperty]
    private Color _backgroundColor = Colors.White;

    [ObservableProperty]
    private Color _textColor = Colors.Black;
}

#endregion

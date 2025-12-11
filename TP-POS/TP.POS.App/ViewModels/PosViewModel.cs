using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Windows.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าขายสินค้า
/// ใช้ manual INotifyPropertyChanged implementation แทน source generators
/// </summary>
public class PosViewModel : BaseViewModel
{
    private readonly IProductService _productService;
    private readonly ICartService _cartService;
    private readonly ISyncService _syncService;
    private readonly IScannerService _scannerService;

    #region Observable Properties

    private string _searchText = string.Empty;
    public string SearchText
    {
        get => _searchText;
        set => SetProperty(ref _searchText, value);
    }

    private Category? _selectedCategory;
    public Category? SelectedCategory
    {
        get => _selectedCategory;
        set => SetProperty(ref _selectedCategory, value);
    }

    private ObservableCollection<ProductDisplayModel> _products = new();
    public ObservableCollection<ProductDisplayModel> Products
    {
        get => _products;
        set => SetProperty(ref _products, value);
    }

    private ObservableCollection<CategoryDisplayModel> _categories = new();
    public ObservableCollection<CategoryDisplayModel> Categories
    {
        get => _categories;
        set => SetProperty(ref _categories, value);
    }

    private ObservableCollection<CartItem> _cartItems = new();
    public ObservableCollection<CartItem> CartItems
    {
        get => _cartItems;
        set => SetProperty(ref _cartItems, value);
    }

    private decimal _subtotal;
    public decimal Subtotal
    {
        get => _subtotal;
        set => SetProperty(ref _subtotal, value);
    }

    private decimal _discountAmount;
    public decimal DiscountAmount
    {
        get => _discountAmount;
        set => SetProperty(ref _discountAmount, value);
    }

    private decimal _taxAmount;
    public decimal TaxAmount
    {
        get => _taxAmount;
        set => SetProperty(ref _taxAmount, value);
    }

    private decimal _totalAmount;
    public decimal TotalAmount
    {
        get => _totalAmount;
        set => SetProperty(ref _totalAmount, value);
    }

    private bool _hasItems;
    public bool HasItems
    {
        get => _hasItems;
        set => SetProperty(ref _hasItems, value);
    }

    private bool _isOnline;
    public bool IsOnline
    {
        get => _isOnline;
        set => SetProperty(ref _isOnline, value);
    }

    private string _syncStatusText = "ออฟไลน์";
    public string SyncStatusText
    {
        get => _syncStatusText;
        set => SetProperty(ref _syncStatusText, value);
    }

    private Color _syncStatusColor = Colors.Gray;
    public Color SyncStatusColor
    {
        get => _syncStatusColor;
        set => SetProperty(ref _syncStatusColor, value);
    }

    private Color _allCategoryBackground = Color.FromArgb("#22C55E");
    public Color AllCategoryBackground
    {
        get => _allCategoryBackground;
        set => SetProperty(ref _allCategoryBackground, value);
    }

    private Color _allCategoryTextColor = Colors.White;
    public Color AllCategoryTextColor
    {
        get => _allCategoryTextColor;
        set => SetProperty(ref _allCategoryTextColor, value);
    }

    #endregion

    #region Commands

    public ICommand SearchCommand { get; }
    public ICommand SelectCategoryCommand { get; }
    public ICommand ScanBarcodeCommand { get; }
    public ICommand AddToCartCommand { get; }
    public ICommand RemoveFromCartCommand { get; }
    public ICommand IncrementCommand { get; }
    public ICommand DecrementCommand { get; }
    public ICommand ClearCartCommand { get; }
    public ICommand CheckoutCommand { get; }

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

        // Initialize Commands
        SearchCommand = new Command(async () => await SearchAsync());
        SelectCategoryCommand = new Command<CategoryDisplayModel?>(async (cat) => await SelectCategoryAsync(cat));
        ScanBarcodeCommand = new Command(async () => await ScanBarcodeAsync());
        AddToCartCommand = new Command<ProductDisplayModel>(async (product) => await AddToCartAsync(product));
        RemoveFromCartCommand = new Command<CartItem>(async (item) => await RemoveFromCartAsync(item));
        IncrementCommand = new Command<CartItem>(async (item) => await IncrementAsync(item));
        DecrementCommand = new Command<CartItem>(async (item) => await DecrementAsync(item));
        ClearCartCommand = new Command(async () => await ClearCartAsync());
        CheckoutCommand = new Command(async () => await CheckoutAsync());

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

            await LoadCategoriesAsync();
            await LoadProductsAsync();
            await LoadCartAsync();
            await _scannerService.StartListeningAsync();

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

    private async Task LoadCartAsync()
    {
        var items = await _cartService.GetAllItemsAsync();
        CartItems = new ObservableCollection<CartItem>(items);

        await UpdateCartSummaryAsync();
    }

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

    #region Command Implementations

    private async Task SearchAsync()
    {
        await LoadProductsAsync();
    }

    private async Task SelectCategoryAsync(CategoryDisplayModel? category)
    {
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

    private async Task ScanBarcodeAsync()
    {
        var barcode = await _scannerService.ScanWithCameraAsync();
        if (!string.IsNullOrEmpty(barcode))
        {
            await AddByBarcodeAsync(barcode);
        }
    }

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
            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
        }
    }

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

    private async Task IncrementAsync(CartItem item)
    {
        await _cartService.IncrementQuantityAsync(item.Id);
    }

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

    private async void OnCartChanged(object? sender, CartChangedEventArgs e)
    {
        await MainThread.InvokeOnMainThreadAsync(async () =>
        {
            await LoadCartAsync();
        });
    }

    private async void OnBarcodeScanned(object? sender, BarcodeScannedEventArgs e)
    {
        await MainThread.InvokeOnMainThreadAsync(async () =>
        {
            await AddByBarcodeAsync(e.Barcode);
        });
    }

    private void OnSyncStatusChanged(object? sender, SyncStatusEventArgs e)
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            IsOnline = e.IsOnline;
            UpdateSyncStatus();
        });
    }

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
public class ProductDisplayModel : INotifyPropertyChanged
{
    public event PropertyChangedEventHandler? PropertyChanged;

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

    protected void OnPropertyChanged([CallerMemberName] string propertyName = "")
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }
}

/// <summary>
/// Model สำหรับแสดงหมวดหมู่
/// </summary>
public class CategoryDisplayModel : INotifyPropertyChanged
{
    public event PropertyChangedEventHandler? PropertyChanged;

    public int Id { get; set; }
    public string Name { get; set; } = string.Empty;
    public string? Icon { get; set; }
    public string Color { get; set; } = "#22C55E";

    private Color _backgroundColor = Colors.White;
    public Color BackgroundColor
    {
        get => _backgroundColor;
        set
        {
            if (_backgroundColor != value)
            {
                _backgroundColor = value;
                OnPropertyChanged();
            }
        }
    }

    private Color _textColor = Colors.Black;
    public Color TextColor
    {
        get => _textColor;
        set
        {
            if (_textColor != value)
            {
                _textColor = value;
                OnPropertyChanged();
            }
        }
    }

    protected void OnPropertyChanged([CallerMemberName] string propertyName = "")
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }
}

#endregion

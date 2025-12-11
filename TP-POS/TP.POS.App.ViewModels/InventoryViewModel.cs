using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Windows.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าจัดการสต็อก
/// ใช้ manual implementation เพื่อหลีกเลี่ยง source generator conflicts
/// </summary>
public class InventoryViewModel : BaseViewModel
{
    private readonly IProductService _productService;
    private readonly ISyncService _syncService;

    #region Private Fields

    private string _searchText = string.Empty;
    private ObservableCollection<InventoryItemModel> _products = new();
    private ObservableCollection<InventoryItemModel> _lowStockProducts = new();
    private ObservableCollection<CategoryDisplayModel> _categories = new();
    private Category? _selectedCategory;
    private InventoryItemModel? _selectedProduct;
    private int _totalProductCount;
    private int _lowStockCount;
    private int _outOfStockCount;
    private decimal _totalStockValue;
    private int _selectedTabIndex;
    private Color _allCategoryBackground = Color.FromArgb("#22C55E");
    private Color _allCategoryTextColor = Colors.White;
    private bool _showAdjustmentModal;
    private int _adjustmentQuantity;
    private StockMovementType _adjustmentType = StockMovementType.AdjustmentIn;
    private string _adjustmentReason = string.Empty;
    private bool _isSyncing;
    private string _syncMessage = string.Empty;

    #endregion

    #region Properties

    public string SearchText
    {
        get => _searchText;
        set => SetProperty(ref _searchText, value);
    }

    public ObservableCollection<InventoryItemModel> Products
    {
        get => _products;
        set => SetProperty(ref _products, value);
    }

    public ObservableCollection<InventoryItemModel> LowStockProducts
    {
        get => _lowStockProducts;
        set => SetProperty(ref _lowStockProducts, value);
    }

    public ObservableCollection<CategoryDisplayModel> Categories
    {
        get => _categories;
        set => SetProperty(ref _categories, value);
    }

    public Category? SelectedCategory
    {
        get => _selectedCategory;
        set => SetProperty(ref _selectedCategory, value);
    }

    public InventoryItemModel? SelectedProduct
    {
        get => _selectedProduct;
        set => SetProperty(ref _selectedProduct, value);
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

    public int OutOfStockCount
    {
        get => _outOfStockCount;
        set => SetProperty(ref _outOfStockCount, value);
    }

    public decimal TotalStockValue
    {
        get => _totalStockValue;
        set => SetProperty(ref _totalStockValue, value);
    }

    public int SelectedTabIndex
    {
        get => _selectedTabIndex;
        set
        {
            if (SetProperty(ref _selectedTabIndex, value))
            {
                OnPropertyChanged(nameof(ShowAllProducts));
                OnPropertyChanged(nameof(ShowLowStockOnly));
            }
        }
    }

    /// <summary>
    /// แสดง tab สินค้าทั้งหมด (tab index 0)
    /// </summary>
    public bool ShowAllProducts => SelectedTabIndex == 0;

    /// <summary>
    /// แสดง tab สินค้าสต็อกต่ำเท่านั้น (tab index 1)
    /// </summary>
    public bool ShowLowStockOnly => SelectedTabIndex == 1;

    public Color AllCategoryBackground
    {
        get => _allCategoryBackground;
        set => SetProperty(ref _allCategoryBackground, value);
    }

    public Color AllCategoryTextColor
    {
        get => _allCategoryTextColor;
        set => SetProperty(ref _allCategoryTextColor, value);
    }

    public bool ShowAdjustmentModal
    {
        get => _showAdjustmentModal;
        set => SetProperty(ref _showAdjustmentModal, value);
    }

    public int AdjustmentQuantity
    {
        get => _adjustmentQuantity;
        set => SetProperty(ref _adjustmentQuantity, value);
    }

    public StockMovementType AdjustmentType
    {
        get => _adjustmentType;
        set => SetProperty(ref _adjustmentType, value);
    }

    public string AdjustmentReason
    {
        get => _adjustmentReason;
        set => SetProperty(ref _adjustmentReason, value);
    }

    public bool IsSyncing
    {
        get => _isSyncing;
        set => SetProperty(ref _isSyncing, value);
    }

    public string SyncMessage
    {
        get => _syncMessage;
        set => SetProperty(ref _syncMessage, value);
    }

    #endregion

    #region Commands

    public ICommand SearchCommand { get; }
    public ICommand SelectCategoryCommand { get; }
    public ICommand RefreshCommand { get; }
    public ICommand SyncFromServerCommand { get; }
    public ICommand OpenAdjustmentModalCommand { get; }
    public ICommand CloseAdjustmentModalCommand { get; }
    public ICommand ConfirmAdjustmentCommand { get; }
    public ICommand ViewProductDetailCommand { get; }
    public ICommand SelectTabCommand { get; }

    #endregion

    public InventoryViewModel(
        IProductService productService,
        ISyncService syncService)
    {
        _productService = productService;
        _syncService = syncService;

        Title = "จัดการสต็อก";

        // Initialize commands
        SearchCommand = new Command(async () => await SearchAsync());
        SelectCategoryCommand = new Command<CategoryDisplayModel?>(async (cat) => await SelectCategoryAsync(cat));
        RefreshCommand = new Command(async () => await RefreshAsync());
        SyncFromServerCommand = new Command(async () => await SyncFromServerAsync());
        OpenAdjustmentModalCommand = new Command<InventoryItemModel>(OpenAdjustmentModal);
        CloseAdjustmentModalCommand = new Command(CloseAdjustmentModal);
        ConfirmAdjustmentCommand = new Command(async () => await ConfirmAdjustmentAsync());
        ViewProductDetailCommand = new Command<InventoryItemModel>(async (product) => await ViewProductDetailAsync(product));
        SelectTabCommand = new Command<int>(SelectTab);
    }

    public async Task InitializeAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังโหลดข้อมูล...";

            await LoadCategoriesAsync();
            await LoadProductsAsync();
            await LoadLowStockAsync();
            CalculateStatistics();
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
        var products = await _productService.SearchAsync(SearchText, SelectedCategory?.Id, 200);
        Products.Clear();

        foreach (var product in products)
        {
            Products.Add(CreateInventoryItem(product));
        }

        TotalProductCount = Products.Count;
    }

    private async Task LoadLowStockAsync()
    {
        var lowStock = await _productService.GetLowStockAsync();
        LowStockProducts.Clear();

        foreach (var product in lowStock)
        {
            LowStockProducts.Add(CreateInventoryItem(product));
        }

        LowStockCount = LowStockProducts.Count;
        OutOfStockCount = LowStockProducts.Count(p => p.StockQuantity <= 0);
    }

    private InventoryItemModel CreateInventoryItem(Product product)
    {
        return new InventoryItemModel
        {
            Id = product.Id,
            Sku = product.Sku,
            Barcode = product.Barcode,
            Name = product.Name,
            CategoryName = product.CategoryName ?? "ไม่มีหมวดหมู่",
            CostPrice = product.CostPrice,
            SalePrice = product.SalePrice,
            StockQuantity = product.StockQuantity,
            ReorderLevel = product.ReorderLevel,
            Unit = product.Unit,
            ImageUrl = product.ImageUrl ?? "product_placeholder.png",
            IsActive = product.IsActive,
            StockValue = product.CostPrice * product.StockQuantity,
            StockStatus = GetStockStatus(product.StockQuantity, product.ReorderLevel),
            StockStatusColor = GetStockStatusColor(product.StockQuantity, product.ReorderLevel),
            StockStatusText = GetStockStatusText(product.StockQuantity, product.ReorderLevel)
        };
    }

    private void CalculateStatistics()
    {
        TotalStockValue = Products.Sum(p => p.StockValue);
    }

    private string GetStockStatus(int quantity, int reorderLevel)
    {
        if (quantity <= 0) return "out";
        if (quantity <= reorderLevel) return "low";
        return "normal";
    }

    private Color GetStockStatusColor(int quantity, int reorderLevel)
    {
        if (quantity <= 0) return Color.FromArgb("#EF4444");
        if (quantity <= reorderLevel) return Color.FromArgb("#F59E0B");
        return Color.FromArgb("#22C55E");
    }

    private string GetStockStatusText(int quantity, int reorderLevel)
    {
        if (quantity <= 0) return "หมด";
        if (quantity <= reorderLevel) return "ต่ำ";
        return "ปกติ";
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

    private async Task RefreshAsync()
    {
        await InitializeAsync();
    }

    private async Task SyncFromServerAsync()
    {
        try
        {
            IsSyncing = true;
            SyncMessage = "กำลัง sync สินค้าจาก server...";

            var count = await _productService.SyncFromServerAsync();

            await Shell.Current.DisplayAlert("Sync สำเร็จ", $"อัพเดทสินค้า {count} รายการ", "ตกลง");

            await InitializeAsync();
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("Sync ล้มเหลว", ex.Message, "ตกลง");
        }
        finally
        {
            IsSyncing = false;
            SyncMessage = string.Empty;
        }
    }

    private void OpenAdjustmentModal(InventoryItemModel product)
    {
        SelectedProduct = product;
        AdjustmentQuantity = 0;
        AdjustmentType = StockMovementType.AdjustmentIn;
        AdjustmentReason = string.Empty;
        ShowAdjustmentModal = true;
    }

    private void CloseAdjustmentModal()
    {
        ShowAdjustmentModal = false;
        SelectedProduct = null;
    }

    private async Task ConfirmAdjustmentAsync()
    {
        if (SelectedProduct == null || AdjustmentQuantity <= 0)
        {
            await Shell.Current.DisplayAlert("ข้อมูลไม่ถูกต้อง", "กรุณากรอกจำนวนที่ถูกต้อง", "ตกลง");
            return;
        }

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังปรับสต็อก...";

            int newQuantity = AdjustmentType.IsIncrease()
                ? SelectedProduct.StockQuantity + AdjustmentQuantity
                : SelectedProduct.StockQuantity - AdjustmentQuantity;

            if (newQuantity < 0)
            {
                await Shell.Current.DisplayAlert("ไม่สามารถปรับได้", "สต็อกจะติดลบ กรุณาตรวจสอบจำนวน", "ตกลง");
                return;
            }

            await _productService.UpdateStockAsync(SelectedProduct.Id, newQuantity);

            ShowAdjustmentModal = false;

            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", $"ปรับสต็อกเป็น {newQuantity} {SelectedProduct.Unit}", "ตกลง");

            await InitializeAsync();
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

    private async Task ViewProductDetailAsync(InventoryItemModel product)
    {
        await Shell.Current.GoToAsync("product-detail", new Dictionary<string, object>
        {
            { "ProductId", product.Id }
        });
    }

    private void SelectTab(int tabIndex)
    {
        SelectedTabIndex = tabIndex;
    }

    #endregion
}

#region Helper Models

/// <summary>
/// Model สำหรับแสดงสินค้าในหน้า Inventory
/// </summary>
public class InventoryItemModel : INotifyPropertyChanged
{
    public event PropertyChangedEventHandler? PropertyChanged;

    public int Id { get; set; }
    public string Sku { get; set; } = string.Empty;
    public string? Barcode { get; set; }
    public string Name { get; set; } = string.Empty;
    public string CategoryName { get; set; } = string.Empty;
    public decimal CostPrice { get; set; }
    public decimal SalePrice { get; set; }
    public int StockQuantity { get; set; }
    public int ReorderLevel { get; set; }
    public string Unit { get; set; } = "ชิ้น";
    public string ImageUrl { get; set; } = "product_placeholder.png";
    public bool IsActive { get; set; }
    public decimal StockValue { get; set; }
    public string StockStatus { get; set; } = "normal";
    public Color StockStatusColor { get; set; } = Colors.Green;
    public string StockStatusText { get; set; } = "ปกติ";

    protected void OnPropertyChanged([CallerMemberName] string propertyName = "")
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }
}

#endregion

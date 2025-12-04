using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าจัดการสต็อก
/// </summary>
public partial class InventoryViewModel : BaseViewModel
{
    private readonly IProductService _productService;
    private readonly ISyncService _syncService;

    #region Observable Properties

    /// <summary>
    /// ข้อความค้นหา
    /// </summary>
    [ObservableProperty]
    private string _searchText = string.Empty;

    /// <summary>
    /// รายการสินค้า
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<InventoryItemModel> _products = new();

    /// <summary>
    /// รายการสินค้าสต็อกต่ำ
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<InventoryItemModel> _lowStockProducts = new();

    /// <summary>
    /// รายการหมวดหมู่
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<CategoryDisplayModel> _categories = new();

    /// <summary>
    /// หมวดหมู่ที่เลือก
    /// </summary>
    [ObservableProperty]
    private Category? _selectedCategory;

    /// <summary>
    /// สินค้าที่เลือก
    /// </summary>
    [ObservableProperty]
    private InventoryItemModel? _selectedProduct;

    /// <summary>
    /// จำนวนสินค้าทั้งหมด
    /// </summary>
    [ObservableProperty]
    private int _totalProductCount;

    /// <summary>
    /// จำนวนสินค้าสต็อกต่ำ
    /// </summary>
    [ObservableProperty]
    private int _lowStockCount;

    /// <summary>
    /// จำนวนสินค้าหมดสต็อก
    /// </summary>
    [ObservableProperty]
    private int _outOfStockCount;

    /// <summary>
    /// มูลค่าสต็อกรวม
    /// </summary>
    [ObservableProperty]
    private decimal _totalStockValue;

    /// <summary>
    /// Tab ที่เลือก (0 = ทั้งหมด, 1 = สต็อกต่ำ)
    /// </summary>
    [ObservableProperty]
    private int _selectedTabIndex;

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

    /// <summary>
    /// แสดง Modal ปรับสต็อก
    /// </summary>
    [ObservableProperty]
    private bool _showAdjustmentModal;

    /// <summary>
    /// จำนวนปรับ
    /// </summary>
    [ObservableProperty]
    private int _adjustmentQuantity;

    /// <summary>
    /// ประเภทการปรับ
    /// </summary>
    [ObservableProperty]
    private StockMovementType _adjustmentType = StockMovementType.AdjustmentIn;

    /// <summary>
    /// เหตุผลการปรับ
    /// </summary>
    [ObservableProperty]
    private string _adjustmentReason = string.Empty;

    /// <summary>
    /// กำลัง Sync
    /// </summary>
    [ObservableProperty]
    private bool _isSyncing;

    /// <summary>
    /// ข้อความ Sync
    /// </summary>
    [ObservableProperty]
    private string _syncMessage = string.Empty;

    #endregion

    public InventoryViewModel(
        IProductService productService,
        ISyncService syncService)
    {
        _productService = productService;
        _syncService = syncService;

        Title = "จัดการสต็อก";
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

            // โหลดสินค้าสต็อกต่ำ
            await LoadLowStockAsync();

            // คำนวณสถิติ
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
        var products = await _productService.SearchAsync(SearchText, SelectedCategory?.Id, 200);
        Products.Clear();

        foreach (var product in products)
        {
            Products.Add(CreateInventoryItem(product));
        }

        TotalProductCount = Products.Count;
    }

    /// <summary>
    /// โหลดสินค้าสต็อกต่ำ
    /// </summary>
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

    /// <summary>
    /// สร้าง InventoryItemModel จาก Product
    /// </summary>
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

    /// <summary>
    /// คำนวณสถิติ
    /// </summary>
    private void CalculateStatistics()
    {
        TotalStockValue = Products.Sum(p => p.StockValue);
    }

    /// <summary>
    /// ดึงสถานะสต็อก
    /// </summary>
    private string GetStockStatus(int quantity, int reorderLevel)
    {
        if (quantity <= 0) return "out";
        if (quantity <= reorderLevel) return "low";
        return "normal";
    }

    /// <summary>
    /// ดึงสีสถานะสต็อก
    /// </summary>
    private Color GetStockStatusColor(int quantity, int reorderLevel)
    {
        if (quantity <= 0) return Color.FromArgb("#EF4444");
        if (quantity <= reorderLevel) return Color.FromArgb("#F59E0B");
        return Color.FromArgb("#22C55E");
    }

    /// <summary>
    /// ดึงข้อความสถานะสต็อก
    /// </summary>
    private string GetStockStatusText(int quantity, int reorderLevel)
    {
        if (quantity <= 0) return "หมด";
        if (quantity <= reorderLevel) return "ต่ำ";
        return "ปกติ";
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
    /// รีเฟรช
    /// </summary>
    [RelayCommand]
    private async Task RefreshAsync()
    {
        await InitializeAsync();
    }

    /// <summary>
    /// Sync จาก Server
    /// </summary>
    [RelayCommand]
    private async Task SyncFromServerAsync()
    {
        try
        {
            IsSyncing = true;
            SyncMessage = "กำลัง sync สินค้าจาก server...";

            var count = await _productService.SyncFromServerAsync();

            await Shell.Current.DisplayAlert("Sync สำเร็จ", $"อัพเดทสินค้า {count} รายการ", "ตกลง");

            // โหลดข้อมูลใหม่
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

    /// <summary>
    /// เปิด Modal ปรับสต็อก
    /// </summary>
    [RelayCommand]
    private void OpenAdjustmentModal(InventoryItemModel product)
    {
        SelectedProduct = product;
        AdjustmentQuantity = 0;
        AdjustmentType = StockMovementType.AdjustmentIn;
        AdjustmentReason = string.Empty;
        ShowAdjustmentModal = true;
    }

    /// <summary>
    /// ปิด Modal ปรับสต็อก
    /// </summary>
    [RelayCommand]
    private void CloseAdjustmentModal()
    {
        ShowAdjustmentModal = false;
        SelectedProduct = null;
    }

    /// <summary>
    /// ยืนยันปรับสต็อก
    /// </summary>
    [RelayCommand]
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

            // คำนวณจำนวนใหม่
            int newQuantity = AdjustmentType.IsIncrease()
                ? SelectedProduct.StockQuantity + AdjustmentQuantity
                : SelectedProduct.StockQuantity - AdjustmentQuantity;

            if (newQuantity < 0)
            {
                await Shell.Current.DisplayAlert("ไม่สามารถปรับได้", "สต็อกจะติดลบ กรุณาตรวจสอบจำนวน", "ตกลง");
                return;
            }

            // อัพเดทสต็อก
            await _productService.UpdateStockAsync(SelectedProduct.Id, newQuantity);

            // ปิด Modal
            ShowAdjustmentModal = false;

            // แจ้งเตือน
            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
            await Shell.Current.DisplayAlert("สำเร็จ", $"ปรับสต็อกเป็น {newQuantity} {SelectedProduct.Unit}", "ตกลง");

            // โหลดข้อมูลใหม่
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

    /// <summary>
    /// ดูรายละเอียดสินค้า
    /// </summary>
    [RelayCommand]
    private async Task ViewProductDetailAsync(InventoryItemModel product)
    {
        await Shell.Current.GoToAsync("product-detail", new Dictionary<string, object>
        {
            { "ProductId", product.Id }
        });
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
/// Model สำหรับแสดงสินค้าในหน้า Inventory
/// </summary>
public partial class InventoryItemModel : ObservableObject
{
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
}

#endregion

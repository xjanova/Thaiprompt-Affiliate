using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าจัดการสต็อก
/// </summary>
public partial class InventoryPage : ContentPage
{
    private readonly InventoryViewModel _viewModel;

    public InventoryPage(InventoryViewModel viewModel)
    {
        InitializeComponent();
        BindingContext = _viewModel = viewModel;
    }

    /// <summary>
    /// เมื่อหน้าแสดงผล
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();
        await _viewModel.InitializeAsync();
    }
}

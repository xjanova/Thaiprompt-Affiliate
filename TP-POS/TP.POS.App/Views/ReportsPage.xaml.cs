using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้ารายงานยอดขาย
/// </summary>
public partial class ReportsPage : ContentPage
{
    private readonly ReportsViewModel _viewModel;

    public ReportsPage(ReportsViewModel viewModel)
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

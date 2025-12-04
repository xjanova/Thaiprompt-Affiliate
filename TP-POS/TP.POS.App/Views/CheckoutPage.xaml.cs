using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าชำระเงิน
/// </summary>
public partial class CheckoutPage : ContentPage
{
    private readonly CheckoutViewModel _viewModel;

    public CheckoutPage(CheckoutViewModel viewModel)
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

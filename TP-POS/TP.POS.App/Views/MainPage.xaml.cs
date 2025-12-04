using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าหลัก (Dashboard)
/// </summary>
public partial class MainPage : ContentPage
{
    private readonly MainViewModel _viewModel;

    public MainPage(MainViewModel viewModel)
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

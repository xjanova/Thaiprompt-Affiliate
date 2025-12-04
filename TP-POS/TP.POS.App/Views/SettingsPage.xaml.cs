using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าตั้งค่าระบบ
/// </summary>
public partial class SettingsPage : ContentPage
{
    private readonly SettingsViewModel _viewModel;

    public SettingsPage(SettingsViewModel viewModel)
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

    /// <summary>
    /// เลือกกระดาษ 58mm
    /// </summary>
    private void OnPaperWidth58Clicked(object sender, EventArgs e)
    {
        _viewModel.PaperWidth = 58;
    }

    /// <summary>
    /// เลือกกระดาษ 76mm
    /// </summary>
    private void OnPaperWidth76Clicked(object sender, EventArgs e)
    {
        _viewModel.PaperWidth = 76;
    }

    /// <summary>
    /// เลือกกระดาษ 80mm
    /// </summary>
    private void OnPaperWidth80Clicked(object sender, EventArgs e)
    {
        _viewModel.PaperWidth = 80;
    }
}

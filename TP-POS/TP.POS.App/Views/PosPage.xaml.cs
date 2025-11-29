using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าขายสินค้าหลัก
/// </summary>
public partial class PosPage : ContentPage
{
    public PosPage(PosViewModel viewModel)
    {
        InitializeComponent();
        BindingContext = viewModel;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();

        if (BindingContext is PosViewModel vm)
        {
            await vm.InitializeAsync();
        }
    }
}

using ThaipromptAffiliateApp.ViewModels;

namespace ThaipromptAffiliateApp.Views;

public partial class HomePage : ContentPage
{
    private readonly HomeViewModel _viewModel;

    public HomePage(HomeViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        if (_viewModel.InitializeCommand.CanExecute(null))
        {
            _viewModel.InitializeCommand.Execute(null);
        }
    }
}

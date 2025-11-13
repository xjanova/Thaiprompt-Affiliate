using ThaipromptAffiliateApp.ViewModels;

namespace ThaipromptAffiliateApp.Views;

public partial class CommissionsPage : ContentPage
{
    private readonly CommissionsViewModel _viewModel;

    public CommissionsPage(CommissionsViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    protected override void OnAppearing()
    {
        base.OnAppearing();
        _viewModel.OnAppearing();
    }
}

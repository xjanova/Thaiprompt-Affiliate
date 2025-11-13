using ThaipromptAffiliateApp.ViewModels;

namespace ThaipromptAffiliateApp.Views;

public partial class ReferralsPage : ContentPage
{
    private readonly ReferralsViewModel _viewModel;

    public ReferralsPage(ReferralsViewModel viewModel)
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

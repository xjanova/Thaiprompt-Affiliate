namespace ThaipromptAffiliateApp.Views;

public partial class WebViewPage : ContentPage
{
    private readonly WebViewViewModel _viewModel;

    public WebViewPage(WebViewViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();
        await _viewModel.InitializeAsync();
    }

    private void OnNavigating(object sender, WebNavigatingEventArgs e)
    {
        _viewModel.IsLoading = true;
    }

    private void OnNavigated(object sender, WebNavigatedEventArgs e)
    {
        _viewModel.IsLoading = false;
        _viewModel.CanGoBack = WebViewControl.CanGoBack;
        _viewModel.CanGoForward = WebViewControl.CanGoForward;
    }
}

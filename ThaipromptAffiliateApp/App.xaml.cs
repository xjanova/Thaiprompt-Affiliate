using ThaipromptAffiliateApp.Services;
using ThaipromptAffiliateApp.ViewModels;
using ThaipromptAffiliateApp.Views;

namespace ThaipromptAffiliateApp;

public partial class App : Application
{
    private readonly IApiService _apiService;
    private readonly IServiceProvider _serviceProvider;

    public App(IApiService apiService, IServiceProvider serviceProvider)
    {
        InitializeComponent();
        _apiService = apiService;
        _serviceProvider = serviceProvider;
    }

    protected override Window CreateWindow(IActivationState? activationState)
    {
        Page mainPage;

        // Check if user is already authenticated
        if (_apiService.IsAuthenticated)
        {
            mainPage = new AppShell();
        }
        else
        {
            var viewModel = _serviceProvider.GetRequiredService<LoginViewModel>();
            mainPage = new NavigationPage(new LoginPage(viewModel));
        }

        return new Window(mainPage)
        {
            MinimumWidth = 400,
            MinimumHeight = 600
        };
    }
}

using ThaipromptAffiliateApp.Services;
using ThaipromptAffiliateApp.Views;

namespace ThaipromptAffiliateAppApp;

public partial class App : Application
{
    private readonly IApiService _apiService;

    public App(IApiService apiService)
    {
        InitializeComponent();
        _apiService = apiService;

        // Check if user is already authenticated
        if (_apiService.IsAuthenticated)
        {
            MainPage = new AppShell();
        }
        else
        {
            MainPage = new NavigationPage(new LoginPage());
        }
    }

    protected override Window CreateWindow(IActivationState? activationState)
    {
        var window = base.CreateWindow(activationState);

        // Set minimum window size for desktop platforms
        window.MinimumWidth = 400;
        window.MinimumHeight = 600;

        return window;
    }
}

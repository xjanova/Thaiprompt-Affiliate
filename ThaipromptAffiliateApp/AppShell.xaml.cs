using ThaipromptAffiliateApp.Services;
using ThaipromptAffiliateApp.Views;

namespace ThaipromptAffiliateAppApp;

public partial class AppShell : Shell
{
    private readonly IApiService _apiService;

    public AppShell()
    {
        InitializeComponent();
        _apiService = App.Current!.Handler!.MauiContext!.Services.GetService<IApiService>()!;

        // Register routes for navigation
        Routing.RegisterRoute(nameof(LoginPage), typeof(LoginPage));
        Routing.RegisterRoute(nameof(DashboardPage), typeof(DashboardPage));
        Routing.RegisterRoute(nameof(CommissionsPage), typeof(CommissionsPage));
        Routing.RegisterRoute(nameof(ReferralsPage), typeof(ReferralsPage));
        Routing.RegisterRoute(nameof(ProfilePage), typeof(ProfilePage));
    }

    private async void OnLogoutClicked(object sender, EventArgs e)
    {
        bool confirm = await DisplayAlert(
            "ยืนยันการออกจากระบบ",
            "คุณต้องการออกจากระบบใช่หรือไม่?",
            "ใช่",
            "ไม่");

        if (confirm)
        {
            await _apiService.LogoutAsync();
            App.Current!.MainPage = new NavigationPage(new LoginPage());
        }
    }
}

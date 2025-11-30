using ThaipromptAffiliateApp.Services;
using ThaipromptAffiliateApp.ViewModels;
using ThaipromptAffiliateApp.Views;

namespace ThaipromptAffiliateApp;

public partial class AppShell : Shell
{
    public AppShell()
    {
        InitializeComponent();

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
            var apiService = Handler!.MauiContext!.Services.GetService<IApiService>()!;
            await apiService.LogoutAsync();

            var viewModel = Handler!.MauiContext!.Services.GetService<LoginViewModel>()!;
            App.Current!.Windows[0].Page = new NavigationPage(new LoginPage(viewModel));
        }
    }
}

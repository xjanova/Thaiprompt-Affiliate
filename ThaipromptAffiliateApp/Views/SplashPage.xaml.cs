namespace ThaipromptAffiliateApp.Views;

public partial class SplashPage : ContentPage
{
    public SplashPage()
    {
        InitializeComponent();
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // Simulate loading and initialization
        await Task.Delay(2000);

        // Navigate to main app
        Application.Current!.MainPage = new AppShell();
    }
}

namespace ThaipromptAffiliateApp.Views;

/// <summary>
/// หน้า Splash Screen - แสดงตอนเริ่มต้นแอป
/// หลังจากโหลดเสร็จจะไปหน้า MainMenuPage
/// </summary>
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
        await Task.Delay(2500);

        // Navigate to Main Menu Page (Premium UI)
        Application.Current!.MainPage = new MainMenuPage();
    }
}

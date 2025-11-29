namespace TP.POS.App.Views;

public partial class MainPage : ContentPage
{
    public MainPage()
    {
        InitializeComponent();
    }

    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // รอสักครู่แล้วไปหน้า POS
        await Task.Delay(1500);
        await Shell.Current.GoToAsync("//pos");
    }
}

using System.ComponentModel;
using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าเข้าสู่ระบบ
/// </summary>
public partial class LoginPage : ContentPage
{
    private readonly LoginViewModel _viewModel;

    public LoginPage(LoginViewModel viewModel)
    {
        InitializeComponent();
        BindingContext = _viewModel = viewModel;

        // Subscribe event เมื่อ Login สำเร็จ
        _viewModel.LoginSuccessful += OnLoginSuccessful;

        // Subscribe event เมื่อต้องการไปหน้า Setup
        _viewModel.NavigateToSetup += OnNavigateToSetup;

        // Subscribe PropertyChanged เพื่ออัพเดท Glow Color
        _viewModel.PropertyChanged += OnViewModelPropertyChanged;
    }

    /// <summary>
    /// เมื่อหน้าแสดงผล
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();
        await _viewModel.InitializeAsync();
    }

    /// <summary>
    /// อัพเดท UI เมื่อ ViewModel properties เปลี่ยน
    /// </summary>
    private void OnViewModelPropertyChanged(object? sender, PropertyChangedEventArgs e)
    {
        if (e.PropertyName == nameof(LoginViewModel.GlowColor) ||
            e.PropertyName == nameof(LoginViewModel.IsServerConnected))
        {
            MainThread.BeginInvokeOnMainThread(() =>
            {
                // อัพเดทสี Shadow ของ Login Card
                LoginCardShadow.Brush = new SolidColorBrush(_viewModel.GlowColor);

                // อัพเดทสี Indicator วงกลม
                ConnectionIndicator.Fill = new SolidColorBrush(
                    _viewModel.IsServerConnected
                        ? Color.FromArgb("#22C55E")  // สีเขียว
                        : Color.FromArgb("#EF4444")); // สีแดง
            });
        }
    }

    /// <summary>
    /// เมื่อ Login สำเร็จ - นำทางไป AppShell
    /// </summary>
    private void OnLoginSuccessful(object? sender, EventArgs e)
    {
        // Unsubscribe event ก่อน
        _viewModel.LoginSuccessful -= OnLoginSuccessful;

        // นำทางไป AppShell บน Main Thread
        MainThread.BeginInvokeOnMainThread(() =>
        {
            if (Application.Current != null)
            {
                Application.Current.MainPage = new AppShell();
            }
        });
    }

    /// <summary>
    /// เมื่อต้องการไปหน้า Setup - นำทางไป SetupPage
    /// </summary>
    private void OnNavigateToSetup(object? sender, EventArgs e)
    {
        // Unsubscribe event ก่อน
        _viewModel.NavigateToSetup -= OnNavigateToSetup;

        // นำทางไปหน้า Setup บน Main Thread
        MainThread.BeginInvokeOnMainThread(async () =>
        {
            // ดึง SetupPage จาก DI Container
            var setupViewModel = Application.Current?.Handler?.MauiContext?.Services.GetService<SetupViewModel>();
            if (setupViewModel != null)
            {
                var setupPage = new SetupPage(setupViewModel);
                await Navigation.PushAsync(setupPage);
            }
        });
    }

    /// <summary>
    /// เมื่อหน้าถูกทำลาย
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        // Unsubscribe event เพื่อป้องกัน memory leak
        _viewModel.LoginSuccessful -= OnLoginSuccessful;
        _viewModel.NavigateToSetup -= OnNavigateToSetup;
        _viewModel.PropertyChanged -= OnViewModelPropertyChanged;
    }
}

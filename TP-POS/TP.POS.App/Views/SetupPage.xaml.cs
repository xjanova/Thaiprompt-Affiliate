using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าตั้งค่าระบบครั้งแรก (Setup / Registration)
/// </summary>
public partial class SetupPage : ContentPage
{
    private readonly SetupViewModel _viewModel;

    public SetupPage(SetupViewModel viewModel)
    {
        InitializeComponent();
        BindingContext = _viewModel = viewModel;

        // Subscribe event เมื่อ Setup เสร็จ
        _viewModel.SetupCompleted += OnSetupCompleted;
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
    /// เมื่อ Setup เสร็จ - กลับไปหน้า Login หรือ Main
    /// </summary>
    private void OnSetupCompleted(object? sender, EventArgs e)
    {
        // Unsubscribe event
        _viewModel.SetupCompleted -= OnSetupCompleted;

        // นำทางกลับหน้า Login
        MainThread.BeginInvokeOnMainThread(async () =>
        {
            if (_viewModel.IsRegistered)
            {
                // ลงทะเบียนสำเร็จ - ไปหน้าหลัก
                if (Application.Current != null)
                {
                    Application.Current.MainPage = new AppShell();
                }
            }
            else
            {
                // ยกเลิก - กลับหน้า Login
                if (Navigation.NavigationStack.Count > 1)
                {
                    await Navigation.PopAsync();
                }
                else if (Application.Current != null)
                {
                    // ถ้าไม่มี Navigation stack ให้กลับไป Login ใหม่
                    var loginViewModel = Application.Current.Handler?.MauiContext?.Services.GetService<LoginViewModel>();
                    if (loginViewModel != null)
                    {
                        Application.Current.MainPage = new NavigationPage(new LoginPage(loginViewModel));
                    }
                }
            }
        });
    }

    /// <summary>
    /// เมื่อหน้าถูกทำลาย
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        _viewModel.SetupCompleted -= OnSetupCompleted;
    }
}

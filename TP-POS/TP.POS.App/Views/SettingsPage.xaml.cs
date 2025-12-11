using TP.POS.App.ViewModels;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าตั้งค่าระบบ
/// จำกัดสิทธิ์: เฉพาะ Admin หรือ Owner เท่านั้น
/// </summary>
public partial class SettingsPage : ContentPage
{
    private readonly SettingsViewModel _viewModel;
    private readonly IAuthService? _authService;

    /// <summary>
    /// Roles ที่อนุญาตให้เข้าหน้าตั้งค่า
    /// </summary>
    private static readonly string[] AllowedRoles = { "admin", "owner", "administrator", "manager" };

    public SettingsPage(SettingsViewModel viewModel)
    {
        InitializeComponent();
        BindingContext = _viewModel = viewModel;

        // ดึง AuthService จาก DI
        _authService = Application.Current?.Handler?.MauiContext?.Services.GetService<IAuthService>();
    }

    /// <summary>
    /// เมื่อหน้าแสดงผล
    /// ตรวจสอบสิทธิ์ก่อนแสดงหน้า
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // ตรวจสอบสิทธิ์ผู้ใช้
        if (!await CheckAccessPermissionAsync())
        {
            return;
        }

        await _viewModel.InitializeAsync();
    }

    /// <summary>
    /// ตรวจสอบสิทธิ์การเข้าถึงหน้าตั้งค่า
    /// อนุญาตเฉพาะ Admin/Owner เท่านั้น
    /// </summary>
    /// <returns>true ถ้ามีสิทธิ์เข้าถึง</returns>
    private async Task<bool> CheckAccessPermissionAsync()
    {
        var currentUser = _authService?.CurrentUser;

        if (currentUser == null)
        {
            // ไม่ได้ Login - กลับไปหน้า Login
            await ShowAccessDeniedAndNavigateBack("กรุณาเข้าสู่ระบบก่อน");
            return false;
        }

        // ตรวจสอบว่าเป็น Admin หรือ Role ที่อนุญาต
        bool hasAccess = currentUser.IsAdmin ||
                         AllowedRoles.Contains(currentUser.Role?.ToLower() ?? "");

        if (!hasAccess)
        {
            await ShowAccessDeniedAndNavigateBack(
                "ไม่มีสิทธิ์เข้าถึง\n\nหน้าตั้งค่าระบบสามารถเข้าได้เฉพาะ\nผู้ดูแลระบบ (Admin) หรือเจ้าของร้าน (Owner) เท่านั้น");
            return false;
        }

        return true;
    }

    /// <summary>
    /// แสดง Alert แจ้งเตือนและนำกลับไปหน้าก่อนหน้า
    /// </summary>
    private async Task ShowAccessDeniedAndNavigateBack(string message)
    {
        await MainThread.InvokeOnMainThreadAsync(async () =>
        {
            await DisplayAlert("⛔ ไม่มีสิทธิ์เข้าถึง", message, "ตกลง");

            // กลับไปหน้าก่อนหน้า
            if (Navigation.NavigationStack.Count > 1)
            {
                await Navigation.PopAsync();
            }
            else
            {
                // ถ้าไม่มี Navigation stack ให้ไปที่ Tab แรก (POS)
                if (Shell.Current != null)
                {
                    await Shell.Current.GoToAsync("//pos");
                }
            }
        });
    }

    /// <summary>
    /// เลือกกระดาษ 58mm
    /// </summary>
    private void OnPaperWidth58Clicked(object sender, EventArgs e)
    {
        _viewModel.PaperWidth = 58;
    }

    /// <summary>
    /// เลือกกระดาษ 76mm
    /// </summary>
    private void OnPaperWidth76Clicked(object sender, EventArgs e)
    {
        _viewModel.PaperWidth = 76;
    }

    /// <summary>
    /// เลือกกระดาษ 80mm
    /// </summary>
    private void OnPaperWidth80Clicked(object sender, EventArgs e)
    {
        _viewModel.PaperWidth = 80;
    }
}

using ThaipromptAffiliate.ViewModels;

namespace ThaipromptAffiliate.Views;

/// <summary>
/// หน้า Login - เข้าสู่ระบบ
///
/// ฟังก์ชันหลัก:
/// - รับ email และ password จากผู้ใช้
/// - ตรวจสอบและยืนยันตัวตนกับ API
/// - นำทางไปหน้า Hub หลังจาก login สำเร็จ
/// </summary>
public partial class LoginPage : ContentPage
{
    private readonly LoginViewModel _viewModel;

    /// <summary>
    /// Constructor - รับ ViewModel ผ่าน DI
    /// </summary>
    public LoginPage(LoginViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    /// <summary>
    /// เรียกเมื่อหน้าปรากฏ
    /// </summary>
    protected override void OnAppearing()
    {
        base.OnAppearing();

        // โฟกัสที่ช่อง email ถ้าว่าง
        // หรือช่อง password ถ้ามี email แล้ว
    }

    /// <summary>
    /// เรียกเมื่อหน้าหายไป
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();

        // ล้าง password field เพื่อความปลอดภัย
        _viewModel.Password = string.Empty;
    }
}

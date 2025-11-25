using ThaipromptAffiliate.ViewModels;

namespace ThaipromptAffiliate.Views;

/// <summary>
/// หน้า Dashboard - แดชบอร์ดหลักของผู้ใช้
///
/// ฟังก์ชันหลัก:
/// - แสดงข้อมูลสรุปของผู้ใช้
/// - แสดง commission ล่าสุด
/// - Quick actions สำหรับการดำเนินการต่างๆ
/// </summary>
public partial class DashboardPage : ContentPage
{
    private readonly DashboardViewModel _viewModel;

    /// <summary>
    /// Constructor - รับ ViewModel ผ่าน DI
    /// </summary>
    public DashboardPage(DashboardViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    /// <summary>
    /// เรียกเมื่อหน้าปรากฏ - โหลดข้อมูล
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // โหลดข้อมูล dashboard
        await _viewModel.LoadDataAsync();
    }
}

using ThaipromptAffiliate.ViewModels;

namespace ThaipromptAffiliate.Views;

/// <summary>
/// หน้า Hub Selection - เลือกบริการที่ต้องการใช้งาน
///
/// ฟังก์ชันหลัก:
/// - แสดง 8 บริการหลักให้เลือก
/// - แสดงข้อมูล user (ถ้า login แล้ว)
/// - Quick actions สำหรับการดำเนินการด่วน
/// - Footer แสดง login/register หรือ balance
/// </summary>
public partial class HubSelectionPage : ContentPage
{
    private readonly HubSelectionViewModel _viewModel;
    private bool _hasAnimated = false;

    /// <summary>
    /// Constructor - รับ ViewModel ผ่าน DI
    /// </summary>
    public HubSelectionPage(HubSelectionViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    /// <summary>
    /// เรียกเมื่อหน้าปรากฏ
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // โหลดข้อมูล user (ถ้ามี)
        await _viewModel.LoadUserDataAsync();

        // Animation สำหรับ cards (ครั้งแรกเท่านั้น)
        if (!_hasAnimated)
        {
            _hasAnimated = true;
            await AnimateCardsEntrance();
        }
    }

    /// <summary>
    /// Animation สำหรับ cards เข้าหน้าจอ
    /// </summary>
    private async Task AnimateCardsEntrance()
    {
        // รอให้ page render เสร็จก่อน
        await Task.Delay(100);

        // Animation จะทำผ่าน CollectionView item animations
        // (MAUI ไม่รองรับ stagger animation โดยตรง)
    }
}

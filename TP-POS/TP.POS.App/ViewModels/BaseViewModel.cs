using CommunityToolkit.Mvvm.ComponentModel;

namespace TP.POS.App.ViewModels;

/// <summary>
/// Base ViewModel สำหรับทุก ViewModel
/// </summary>
public partial class BaseViewModel : ObservableObject
{
    /// <summary>
    /// Title ของหน้า
    /// </summary>
    [ObservableProperty]
    private string _title = string.Empty;

    /// <summary>
    /// กำลัง Loading
    /// </summary>
    [ObservableProperty]
    [NotifyPropertyChangedFor(nameof(IsNotBusy))]
    private bool _isBusy;

    /// <summary>
    /// ไม่ได้ Loading
    /// </summary>
    public bool IsNotBusy => !IsBusy;

    /// <summary>
    /// ข้อความขณะ Loading
    /// </summary>
    [ObservableProperty]
    private string _busyMessage = "กรุณารอสักครู่...";
}

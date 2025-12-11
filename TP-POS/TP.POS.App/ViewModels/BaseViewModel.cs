using System.ComponentModel;
using System.Runtime.CompilerServices;

namespace TP.POS.App.ViewModels;

/// <summary>
/// Base ViewModel สำหรับทุก ViewModel
/// ใช้ manual INotifyPropertyChanged implementation แทน source generators
/// </summary>
public class BaseViewModel : INotifyPropertyChanged
{
    public event PropertyChangedEventHandler? PropertyChanged;

    /// <summary>
    /// Title ของหน้า
    /// </summary>
    private string _title = string.Empty;
    public string Title
    {
        get => _title;
        set => SetProperty(ref _title, value);
    }

    /// <summary>
    /// กำลัง Loading
    /// </summary>
    private bool _isBusy;
    public bool IsBusy
    {
        get => _isBusy;
        set
        {
            if (SetProperty(ref _isBusy, value))
            {
                OnPropertyChanged(nameof(IsNotBusy));
            }
        }
    }

    /// <summary>
    /// ไม่ได้ Loading
    /// </summary>
    public bool IsNotBusy => !IsBusy;

    /// <summary>
    /// ข้อความขณะ Loading
    /// </summary>
    private string _busyMessage = "กรุณารอสักครู่...";
    public string BusyMessage
    {
        get => _busyMessage;
        set => SetProperty(ref _busyMessage, value);
    }

    /// <summary>
    /// Helper method สำหรับ set property และ raise PropertyChanged
    /// </summary>
    protected bool SetProperty<T>(ref T backingStore, T value, [CallerMemberName] string propertyName = "")
    {
        if (EqualityComparer<T>.Default.Equals(backingStore, value))
            return false;

        backingStore = value;
        OnPropertyChanged(propertyName);
        return true;
    }

    /// <summary>
    /// Raise PropertyChanged event
    /// </summary>
    protected void OnPropertyChanged([CallerMemberName] string propertyName = "")
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }
}

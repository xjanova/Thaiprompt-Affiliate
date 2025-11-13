using System.ComponentModel;
using System.Runtime.CompilerServices;

namespace ThaipromptAffiliateApp.Helpers;

/// <summary>
/// Base class for objects that need to implement INotifyPropertyChanged
/// </summary>
public abstract class ObservableObject : INotifyPropertyChanged
{
    public event PropertyChangedEventHandler? PropertyChanged;

    protected virtual void OnPropertyChanged([CallerMemberName] string? propertyName = null)
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }

    protected bool SetProperty<T>(ref T field, T value, [CallerMemberName] string? propertyName = null)
    {
        if (EqualityComparer<T>.Default.Equals(field, value))
            return false;

        field = value;
        OnPropertyChanged(propertyName);
        return true;
    }
}

/// <summary>
/// Helper class for validating input fields
/// </summary>
public static class ValidationHelper
{
    public static bool IsValidEmail(string email)
    {
        if (string.IsNullOrWhiteSpace(email))
            return false;

        try
        {
            var addr = new System.Net.Mail.MailAddress(email);
            return addr.Address == email;
        }
        catch
        {
            return false;
        }
    }

    public static bool IsValidPassword(string password)
    {
        if (string.IsNullOrWhiteSpace(password))
            return false;

        return password.Length >= Constants.MinPasswordLength &&
               password.Length <= Constants.MaxPasswordLength;
    }

    public static bool IsValidPhoneNumber(string phoneNumber)
    {
        if (string.IsNullOrWhiteSpace(phoneNumber))
            return false;

        // Remove common formatting characters
        phoneNumber = phoneNumber.Replace("-", "").Replace(" ", "").Replace("(", "").Replace(")", "");

        // Check if it's all digits and has appropriate length
        return phoneNumber.All(char.IsDigit) && phoneNumber.Length >= 9 && phoneNumber.Length <= 15;
    }
}

/// <summary>
/// Helper class for formatting values
/// </summary>
public static class FormatHelper
{
    public static string FormatCurrency(decimal amount)
    {
        return $"฿{amount:N2}";
    }

    public static string FormatNumber(int number)
    {
        return number.ToString("N0");
    }

    public static string FormatDate(DateTime date)
    {
        return date.ToString("dd/MM/yyyy");
    }

    public static string FormatDateTime(DateTime dateTime)
    {
        return dateTime.ToString("dd/MM/yyyy HH:mm");
    }

    public static string FormatRelativeTime(DateTime dateTime)
    {
        var timeSpan = DateTime.Now - dateTime;

        if (timeSpan.TotalMinutes < 1)
            return "เมื่อสักครู่";
        if (timeSpan.TotalMinutes < 60)
            return $"{(int)timeSpan.TotalMinutes} นาทีที่แล้ว";
        if (timeSpan.TotalHours < 24)
            return $"{(int)timeSpan.TotalHours} ชั่วโมงที่แล้ว";
        if (timeSpan.TotalDays < 7)
            return $"{(int)timeSpan.TotalDays} วันที่แล้ว";
        if (timeSpan.TotalDays < 30)
            return $"{(int)(timeSpan.TotalDays / 7)} สัปดาห์ที่แล้ว";
        if (timeSpan.TotalDays < 365)
            return $"{(int)(timeSpan.TotalDays / 30)} เดือนที่แล้ว";

        return $"{(int)(timeSpan.TotalDays / 365)} ปีที่แล้ว";
    }

    public static string TruncateText(string text, int maxLength)
    {
        if (string.IsNullOrEmpty(text) || text.Length <= maxLength)
            return text;

        return text.Substring(0, maxLength - 3) + "...";
    }
}

/// <summary>
/// Helper class for device-specific operations
/// </summary>
public static class DeviceHelper
{
    public static bool IsAndroid => DeviceInfo.Platform == DevicePlatform.Android;
    public static bool IsIOS => DeviceInfo.Platform == DevicePlatform.iOS;
    public static bool IsWindows => DeviceInfo.Platform == DevicePlatform.WinUI;
    public static bool IsMacCatalyst => DeviceInfo.Platform == DevicePlatform.MacCatalyst;

    public static bool IsPhone => DeviceInfo.Idiom == DeviceIdiom.Phone;
    public static bool IsTablet => DeviceInfo.Idiom == DeviceIdiom.Tablet;
    public static bool IsDesktop => DeviceInfo.Idiom == DeviceIdiom.Desktop;

    public static string GetDeviceInfo()
    {
        return $"{DeviceInfo.Platform} {DeviceInfo.VersionString} ({DeviceInfo.Idiom})";
    }
}

/// <summary>
/// Helper class for navigation
/// </summary>
public static class NavigationHelper
{
    public static async Task NavigateToAsync(string route)
    {
        await Shell.Current.GoToAsync(route);
    }

    public static async Task NavigateBackAsync()
    {
        await Shell.Current.GoToAsync("..");
    }

    public static async Task NavigateToRootAsync()
    {
        await Shell.Current.GoToAsync("//");
    }
}

/// <summary>
/// Helper class for dialogs
/// </summary>
public static class DialogHelper
{
    public static async Task ShowAlertAsync(string title, string message, string cancel = "ตกลง")
    {
        await Shell.Current.DisplayAlert(title, message, cancel);
    }

    public static async Task<bool> ShowConfirmAsync(string title, string message, string accept = "ใช่", string cancel = "ไม่")
    {
        return await Shell.Current.DisplayAlert(title, message, accept, cancel);
    }

    public static async Task<string?> ShowPromptAsync(string title, string message, string accept = "ตกลง", string cancel = "ยกเลิก", string placeholder = "", int maxLength = -1, Keyboard? keyboard = null)
    {
        return await Shell.Current.DisplayPromptAsync(title, message, accept, cancel, placeholder, maxLength, keyboard);
    }

    public static async Task ShowActionSheetAsync(string title, string cancel, string? destruction, params string[] buttons)
    {
        await Shell.Current.DisplayActionSheet(title, cancel, destruction, buttons);
    }
}

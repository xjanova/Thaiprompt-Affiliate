using System.Globalization;

namespace ThaipromptAffiliateApp.Converters;

/// <summary>
/// Converter สำหรับแปลงสถานะเป็นสี
/// </summary>
public class StatusToColorConverter : IValueConverter
{
    public object Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is string status)
        {
            return status.ToLower() switch
            {
                "approved" or "อนุมัติแล้ว" => Color.FromArgb("#10B981"), // Green
                "pending" or "รออนุมัติ" => Color.FromArgb("#F59E0B"),    // Orange
                "rejected" or "ปฏิเสธ" => Color.FromArgb("#EF4444"),      // Red
                "active" or "ใช้งาน" => Color.FromArgb("#10B981"),        // Green
                _ => Color.FromArgb("#6B7280")                             // Gray
            };
        }
        return Color.FromArgb("#6B7280");
    }

    public object ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแปลงสถานะเป็นข้อความภาษาไทย
/// </summary>
public class StatusToTextConverter : IValueConverter
{
    public object Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is string status)
        {
            return status.ToLower() switch
            {
                "approved" => "อนุมัติแล้ว",
                "pending" => "รออนุมัติ",
                "rejected" => "ปฏิเสธ",
                "active" => "ใช้งาน",
                "inactive" => "ไม่ใช้งาน",
                _ => status
            };
        }
        return value?.ToString() ?? "";
    }

    public object ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแปลง Boolean เป็น Inverted Boolean
/// </summary>
public class InvertedBoolConverter : IValueConverter
{
    public object Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        return !(value is bool boolValue && boolValue);
    }

    public object ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        return !(value is bool boolValue && boolValue);
    }
}

/// <summary>
/// Converter สำหรับแปลง Decimal เป็น Currency Format
/// </summary>
public class CurrencyConverter : IValueConverter
{
    public object Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is decimal amount)
        {
            return $"฿{amount:N2}";
        }
        if (value is double doubleAmount)
        {
            return $"฿{doubleAmount:N2}";
        }
        return "฿0.00";
    }

    public object ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแปลง DateTime เป็น Thai Format
/// </summary>
public class DateTimeToThaiConverter : IValueConverter
{
    public object Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is DateTime dateTime)
        {
            var format = parameter?.ToString() ?? "dd/MM/yyyy HH:mm";
            return dateTime.ToString(format);
        }
        return "";
    }

    public object ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแสดงอักษรตัวแรกของชื่อ
/// </summary>
public class NameToInitialConverter : IValueConverter
{
    public object Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is string name && !string.IsNullOrEmpty(name))
        {
            var parts = name.Split(' ', StringSplitOptions.RemoveEmptyEntries);
            if (parts.Length >= 2)
            {
                // First letter of first name + first letter of last name
                return $"{parts[0][0]}{parts[1][0]}".ToUpper();
            }
            // Just first letter of name
            return name[0].ToString().ToUpper();
        }
        return "?";
    }

    public object ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

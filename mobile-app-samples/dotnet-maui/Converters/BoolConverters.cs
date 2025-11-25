using System.Globalization;

namespace ThaipromptAffiliate.Converters;

/// <summary>
/// Converter สำหรับกลับค่า Boolean
/// ใช้เมื่อต้องการแสดง element เมื่อ condition เป็น false
/// </summary>
public class InvertedBoolConverter : IValueConverter
{
    /// <summary>
    /// แปลง true เป็น false และกลับกัน
    /// </summary>
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue)
        {
            return !boolValue;
        }
        return value;
    }

    /// <summary>
    /// แปลงกลับ (เหมือน Convert)
    /// </summary>
    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue)
        {
            return !boolValue;
        }
        return value;
    }
}

/// <summary>
/// Converter สำหรับแปลง Boolean เป็น Visibility (show/hide)
/// </summary>
public class BoolToVisibilityConverter : IValueConverter
{
    /// <summary>
    /// แปลง true เป็น visible, false เป็น collapsed
    /// </summary>
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue)
        {
            // ถ้ามี parameter "invert" จะกลับค่า
            bool invert = parameter?.ToString()?.ToLower() == "invert";
            return invert ? !boolValue : boolValue;
        }
        return true;
    }

    /// <summary>
    /// ไม่รองรับการแปลงกลับ
    /// </summary>
    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแปลงสถานะ password visibility
/// </summary>
public class PasswordVisibilityIconConverter : IValueConverter
{
    /// <summary>
    /// แปลง boolean เป็น icon
    /// true (password visible) = 👁️
    /// false (password hidden) = 👁️‍🗨️
    /// </summary>
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool isPasswordVisible)
        {
            return isPasswordVisible ? "👁️" : "🔒";
        }
        return "🔒";
    }

    /// <summary>
    /// ไม่รองรับการแปลงกลับ
    /// </summary>
    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแปลง null/empty string เป็น boolean
/// </summary>
public class StringToBoolConverter : IValueConverter
{
    /// <summary>
    /// แปลง string ที่มีค่าเป็น true, null/empty เป็น false
    /// </summary>
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is string stringValue)
        {
            return !string.IsNullOrWhiteSpace(stringValue);
        }
        return false;
    }

    /// <summary>
    /// ไม่รองรับการแปลงกลับ
    /// </summary>
    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// Converter สำหรับแปลง decimal เป็น string สกุลเงินไทย
/// </summary>
public class CurrencyConverter : IValueConverter
{
    /// <summary>
    /// แปลง decimal เป็น string สกุลเงินไทย (฿)
    /// </summary>
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is decimal decimalValue)
        {
            return $"฿{decimalValue:N2}";
        }
        if (value is double doubleValue)
        {
            return $"฿{doubleValue:N2}";
        }
        if (value is int intValue)
        {
            return $"฿{intValue:N0}";
        }
        return "฿0.00";
    }

    /// <summary>
    /// ไม่รองรับการแปลงกลับ
    /// </summary>
    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

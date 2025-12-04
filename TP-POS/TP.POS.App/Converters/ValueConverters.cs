using System.Globalization;

namespace TP.POS.App.Converters;

/// <summary>
/// แปลง bool เป็น bool ตรงข้าม
/// </summary>
public class InverseBoolConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue)
            return !boolValue;
        return false;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue)
            return !boolValue;
        return false;
    }
}

/// <summary>
/// แปลง bool เป็น Color
/// ใช้ parameter format: "TrueColor|FalseColor"
/// </summary>
public class BoolToColorConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue && parameter is string colorParam)
        {
            var colors = colorParam.Split('|');
            if (colors.Length == 2)
            {
                var colorString = boolValue ? colors[0] : colors[1];
                return Color.FromArgb(colorString);
            }
        }
        return Colors.Transparent;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// แปลง bool เป็น string
/// ใช้ parameter format: "TrueText|FalseText"
/// </summary>
public class BoolToStringConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue && parameter is string textParam)
        {
            var texts = textParam.Split('|');
            if (texts.Length == 2)
            {
                return boolValue ? texts[0] : texts[1];
            }
        }
        return string.Empty;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// แปลง int เป็น bool (0 = false, อื่นๆ = true)
/// </summary>
public class IntToBoolConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is int intValue)
            return intValue != 0;
        return false;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is bool boolValue)
            return boolValue ? 1 : 0;
        return 0;
    }
}

/// <summary>
/// แปลง int เป็น Color
/// ใช้ parameter format: "CompareValue|MatchColor|NoMatchColor"
/// </summary>
public class IntToColorConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is int intValue && parameter is string param)
        {
            var parts = param.Split('|');
            if (parts.Length == 3)
            {
                if (int.TryParse(parts[0], out int compareValue))
                {
                    var colorString = intValue == compareValue ? parts[1] : parts[2];
                    return Color.FromArgb(colorString);
                }
            }
        }
        return Colors.Transparent;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// ตรวจสอบว่า string ไม่ว่างเปล่า
/// </summary>
public class StringNotNullOrEmptyConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is string str)
            return !string.IsNullOrWhiteSpace(str);
        return false;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// แปลง decimal เป็น string format สกุลเงิน
/// </summary>
public class CurrencyConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is decimal decimalValue)
            return $"฿{decimalValue:N0}";
        return "฿0";
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// แปลง DateTime เป็น string
/// </summary>
public class DateTimeConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is DateTime dateTime)
        {
            var format = parameter as string ?? "dd/MM/yyyy HH:mm";
            return dateTime.ToString(format, new CultureInfo("th-TH"));
        }
        return string.Empty;
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// แปลง Tab Index เป็น Background Color
/// ใช้ parameter format: "TabIndex" (0, 1, 2...)
/// </summary>
public class TabColorConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is int selectedIndex && parameter is string paramStr)
        {
            if (int.TryParse(paramStr, out int tabIndex))
            {
                // Tab ที่ถูกเลือก = สีเขียว, ไม่ถูกเลือก = สีเทาอ่อน
                return selectedIndex == tabIndex
                    ? Color.FromArgb("#22C55E")
                    : Color.FromArgb("#E5E7EB");
            }
        }
        return Color.FromArgb("#E5E7EB");
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

/// <summary>
/// แปลง Tab Index เป็น Text Color
/// ใช้ parameter format: "TabIndex" (0, 1, 2...)
/// </summary>
public class TabTextColorConverter : IValueConverter
{
    public object? Convert(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        if (value is int selectedIndex && parameter is string paramStr)
        {
            if (int.TryParse(paramStr, out int tabIndex))
            {
                // Tab ที่ถูกเลือก = ขาว, ไม่ถูกเลือก = เทาเข้ม
                return selectedIndex == tabIndex
                    ? Colors.White
                    : Color.FromArgb("#374151");
            }
        }
        return Color.FromArgb("#374151");
    }

    public object? ConvertBack(object? value, Type targetType, object? parameter, CultureInfo culture)
    {
        throw new NotImplementedException();
    }
}

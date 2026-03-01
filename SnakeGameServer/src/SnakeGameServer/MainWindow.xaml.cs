using System.Collections.Specialized;
using System.Globalization;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;

namespace SnakeGameServer;

/// <summary>
/// MainWindow code-behind — minimal (MVVM ใช้ ViewModel)
/// </summary>
public partial class MainWindow : Window
{
    public MainWindow()
    {
        InitializeComponent();

        // Auto-scroll log to bottom เมื่อ DataContext พร้อม
        DataContextChanged += (_, _) =>
        {
            if (LogListBox?.ItemsSource is INotifyCollectionChanged src)
            {
                src.CollectionChanged += (_, _) =>
                {
                    if (LogListBox.Items.Count > 0)
                    {
                        LogListBox.ScrollIntoView(LogListBox.Items[^1]);
                    }
                };
            }
        };
    }
}

/// <summary>
/// Converter: bool → inverse bool (สำหรับ IsEnabled เมื่อ server running)
/// </summary>
public class InverseBoolConverter : IValueConverter
{
    public static readonly InverseBoolConverter Instance = new();

    public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
    {
        return value is bool b ? !b : value;
    }

    public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
    {
        return value is bool b ? !b : value;
    }
}

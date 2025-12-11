using System.Runtime.InteropServices;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับจัดการ Dual Monitor (Windows เท่านั้น)
/// แสดงจอที่ 2 อัตโนมัติสำหรับ Customer Display
/// </summary>
public class DualMonitorService : IDualMonitorService
{
    private Window? _customerDisplayWindow;
    private bool _isEnabled;
    private int _selectedMonitor = 1; // 0 = Primary, 1+ = Secondary

    /// <summary>
    /// Event เมื่อ Monitor configuration เปลี่ยน
    /// </summary>
    public event EventHandler<MonitorChangedEventArgs>? MonitorConfigurationChanged;

    /// <summary>
    /// Event เมื่อต้องอัพเดท Cart บน Customer Display
    /// </summary>
    public event EventHandler<CartUpdateEventArgs>? CartUpdated;

    /// <summary>
    /// ตรวจสอบว่าเปิดใช้งาน Dual Monitor อยู่หรือไม่
    /// </summary>
    public bool IsEnabled => _isEnabled;

    /// <summary>
    /// Monitor ที่เลือก (0 = Primary, 1+ = Secondary)
    /// </summary>
    public int SelectedMonitor
    {
        get => _selectedMonitor;
        set
        {
            _selectedMonitor = value;
            Preferences.Set("dual_monitor_index", value);
        }
    }

    /// <summary>
    /// ดึงรายการ Monitor ที่มี
    /// </summary>
    public List<MonitorInfo> GetAvailableMonitors()
    {
        var monitors = new List<MonitorInfo>();

#if WINDOWS
        try
        {
            // ใช้ Windows API เพื่อดึงข้อมูล Monitor
            int monitorCount = 0;
            EnumDisplayMonitors(IntPtr.Zero, IntPtr.Zero,
                new EnumMonitorsDelegate((IntPtr hMonitor, IntPtr hdcMonitor, ref RECT lprcMonitor, IntPtr dwData) =>
                {
                    var info = new MONITORINFOEX();
                    info.Size = Marshal.SizeOf(typeof(MONITORINFOEX));

                    if (GetMonitorInfo(hMonitor, ref info))
                    {
                        monitors.Add(new MonitorInfo
                        {
                            Index = monitorCount,
                            Name = $"Monitor {monitorCount + 1}",
                            DeviceName = new string(info.DeviceName).TrimEnd('\0'),
                            IsPrimary = (info.Flags & MONITORINFOF_PRIMARY) != 0,
                            Bounds = new Rect(
                                info.Monitor.Left,
                                info.Monitor.Top,
                                info.Monitor.Right - info.Monitor.Left,
                                info.Monitor.Bottom - info.Monitor.Top)
                        });
                    }

                    monitorCount++;
                    return true;
                }), IntPtr.Zero);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Error getting monitors: {ex.Message}");
        }
#endif

        // ถ้าไม่พบ monitors ให้ใส่ default
        if (monitors.Count == 0)
        {
            monitors.Add(new MonitorInfo
            {
                Index = 0,
                Name = "Primary Monitor",
                DeviceName = "Display1",
                IsPrimary = true,
                Bounds = new Rect(0, 0, 1920, 1080)
            });
        }

        return monitors;
    }

    /// <summary>
    /// ตรวจสอบว่ามีจอที่ 2 หรือไม่
    /// </summary>
    public bool HasSecondaryMonitor()
    {
        var monitors = GetAvailableMonitors();
        return monitors.Count > 1;
    }

    /// <summary>
    /// เปิด Customer Display บนจอที่ 2
    /// </summary>
    public async Task OpenCustomerDisplayAsync()
    {
        if (_customerDisplayWindow != null)
        {
            // ถ้าเปิดอยู่แล้ว ให้ activate
            return;
        }

        var monitors = GetAvailableMonitors();
        if (monitors.Count <= _selectedMonitor)
        {
            System.Diagnostics.Debug.WriteLine("Selected monitor not available");
            return;
        }

        var monitor = monitors[_selectedMonitor];

#if WINDOWS
        await MainThread.InvokeOnMainThreadAsync(() =>
        {
            try
            {
                // สร้าง Window ใหม่สำหรับ Customer Display
                var customerDisplayPage = new Views.CustomerDisplayPage();
                _customerDisplayWindow = new Window(customerDisplayPage)
                {
                    Title = "TP POS - Customer Display",
                    Width = monitor.Bounds.Width,
                    Height = monitor.Bounds.Height,
                    X = monitor.Bounds.X,
                    Y = monitor.Bounds.Y
                };

                // เปิด Window
                Application.Current?.OpenWindow(_customerDisplayWindow);

                _isEnabled = true;
                Preferences.Set("dual_monitor_enabled", true);

                System.Diagnostics.Debug.WriteLine($"Customer Display opened on Monitor {_selectedMonitor + 1}");
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Error opening customer display: {ex.Message}");
            }
        });
#endif
    }

    /// <summary>
    /// ปิด Customer Display
    /// </summary>
    public void CloseCustomerDisplay()
    {
        if (_customerDisplayWindow != null)
        {
            try
            {
                Application.Current?.CloseWindow(_customerDisplayWindow);
                _customerDisplayWindow = null;
                _isEnabled = false;
                Preferences.Set("dual_monitor_enabled", false);
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Error closing customer display: {ex.Message}");
            }
        }
    }

    /// <summary>
    /// อัพเดท Cart บน Customer Display
    /// </summary>
    public void UpdateCart(List<CartItemDisplay> items, decimal subtotal, decimal discount, decimal tax, decimal total)
    {
        CartUpdated?.Invoke(this, new CartUpdateEventArgs
        {
            Items = items,
            Subtotal = subtotal,
            Discount = discount,
            Tax = tax,
            Total = total
        });
    }

    /// <summary>
    /// แสดงข้อความ "ขอบคุณ" หลังชำระเงิน
    /// </summary>
    public void ShowThankYouMessage()
    {
        CartUpdated?.Invoke(this, new CartUpdateEventArgs
        {
            ShowThankYou = true
        });
    }

    /// <summary>
    /// โหลดการตั้งค่าจาก Preferences
    /// </summary>
    public void LoadSettings()
    {
        _selectedMonitor = Preferences.Get("dual_monitor_index", 1);
        var wasEnabled = Preferences.Get("dual_monitor_enabled", false);

        // ถ้าเคยเปิดใช้งานและมีจอที่ 2 ให้เปิดอัตโนมัติ
        if (wasEnabled && HasSecondaryMonitor())
        {
            _ = OpenCustomerDisplayAsync();
        }
    }

#if WINDOWS
    // Windows API declarations
    private delegate bool EnumMonitorsDelegate(IntPtr hMonitor, IntPtr hdcMonitor, ref RECT lprcMonitor, IntPtr dwData);

    [DllImport("user32.dll")]
    private static extern bool EnumDisplayMonitors(IntPtr hdc, IntPtr lprcClip, EnumMonitorsDelegate lpfnEnum, IntPtr dwData);

    [DllImport("user32.dll", CharSet = CharSet.Auto)]
    private static extern bool GetMonitorInfo(IntPtr hMonitor, ref MONITORINFOEX lpmi);

    private const int MONITORINFOF_PRIMARY = 0x00000001;

    [StructLayout(LayoutKind.Sequential)]
    private struct RECT
    {
        public int Left;
        public int Top;
        public int Right;
        public int Bottom;
    }

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Auto)]
    private struct MONITORINFOEX
    {
        public int Size;
        public RECT Monitor;
        public RECT WorkArea;
        public uint Flags;
        [MarshalAs(UnmanagedType.ByValTStr, SizeConst = 32)]
        public string DeviceName;
    }
#endif
}

/// <summary>
/// Interface สำหรับ Dual Monitor Service
/// </summary>
public interface IDualMonitorService
{
    bool IsEnabled { get; }
    int SelectedMonitor { get; set; }
    List<MonitorInfo> GetAvailableMonitors();
    bool HasSecondaryMonitor();
    Task OpenCustomerDisplayAsync();
    void CloseCustomerDisplay();
    void UpdateCart(List<CartItemDisplay> items, decimal subtotal, decimal discount, decimal tax, decimal total);
    void ShowThankYouMessage();
    void LoadSettings();
    event EventHandler<MonitorChangedEventArgs>? MonitorConfigurationChanged;
    event EventHandler<CartUpdateEventArgs>? CartUpdated;
}

/// <summary>
/// ข้อมูล Monitor
/// </summary>
public class MonitorInfo
{
    public int Index { get; set; }
    public string Name { get; set; } = string.Empty;
    public string DeviceName { get; set; } = string.Empty;
    public bool IsPrimary { get; set; }
    public Rect Bounds { get; set; }
}

/// <summary>
/// Event args สำหรับ Monitor configuration changed
/// </summary>
public class MonitorChangedEventArgs : EventArgs
{
    public List<MonitorInfo> Monitors { get; set; } = new();
}

/// <summary>
/// Event args สำหรับ Cart update
/// </summary>
public class CartUpdateEventArgs : EventArgs
{
    public List<CartItemDisplay> Items { get; set; } = new();
    public decimal Subtotal { get; set; }
    public decimal Discount { get; set; }
    public decimal Tax { get; set; }
    public decimal Total { get; set; }
    public bool ShowThankYou { get; set; }
}

/// <summary>
/// รายการสินค้าสำหรับแสดงบน Customer Display
/// </summary>
public class CartItemDisplay
{
    public string Name { get; set; } = string.Empty;
    public int Quantity { get; set; }
    public decimal UnitPrice { get; set; }
    public decimal LineTotal { get; set; }
}

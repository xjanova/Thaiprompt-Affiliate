using System.ComponentModel;
using System.Runtime.CompilerServices;
using TP.POS.Infrastructure.Api;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับตรวจสอบและแสดงสถานะการเชื่อมต่อกับ Server
/// </summary>
public class ConnectionStatusService : INotifyPropertyChanged
{
    private readonly TpAffiliateApiClient _apiClient;
    private IDispatcherTimer? _timer;
    private bool _isOnline;
    private DateTime _lastChecked;
    private string _statusText = "กำลังตรวจสอบ...";
    private Color _statusColor = Colors.Gray;

    /// <summary>
    /// Event เมื่อ Property เปลี่ยนแปลง
    /// </summary>
    public event PropertyChangedEventHandler? PropertyChanged;

    /// <summary>
    /// Event เมื่อสถานะเปลี่ยน
    /// </summary>
    public event EventHandler<bool>? ConnectionStatusChanged;

    /// <summary>
    /// สถานะ Online
    /// </summary>
    public bool IsOnline
    {
        get => _isOnline;
        private set
        {
            if (_isOnline != value)
            {
                _isOnline = value;
                OnPropertyChanged();
                UpdateStatusDisplay();
                ConnectionStatusChanged?.Invoke(this, value);
            }
        }
    }

    /// <summary>
    /// ข้อความแสดงสถานะ
    /// </summary>
    public string StatusText
    {
        get => _statusText;
        private set
        {
            if (_statusText != value)
            {
                _statusText = value;
                OnPropertyChanged();
            }
        }
    }

    /// <summary>
    /// สีของสถานะ
    /// </summary>
    public Color StatusColor
    {
        get => _statusColor;
        private set
        {
            if (_statusColor != value)
            {
                _statusColor = value;
                OnPropertyChanged();
            }
        }
    }

    /// <summary>
    /// Icon สำหรับแสดงสถานะ
    /// </summary>
    public string StatusIcon => IsOnline ? "●" : "○";

    /// <summary>
    /// เวลาที่ตรวจสอบล่าสุด
    /// </summary>
    public DateTime LastChecked
    {
        get => _lastChecked;
        private set
        {
            _lastChecked = value;
            OnPropertyChanged();
        }
    }

    /// <summary>
    /// Constructor
    /// </summary>
    public ConnectionStatusService(TpAffiliateApiClient apiClient)
    {
        _apiClient = apiClient;
    }

    /// <summary>
    /// เริ่มตรวจสอบสถานะอัตโนมัติ
    /// </summary>
    public void StartMonitoring(int intervalSeconds = 30)
    {
        // ตรวจสอบทันที
        _ = CheckConnectionAsync();

        // ตั้ง Timer สำหรับตรวจสอบต่อเนื่อง
        _timer = Application.Current?.Dispatcher.CreateTimer();
        if (_timer != null)
        {
            _timer.Interval = TimeSpan.FromSeconds(intervalSeconds);
            _timer.Tick += async (s, e) => await CheckConnectionAsync();
            _timer.Start();
        }
    }

    /// <summary>
    /// หยุดตรวจสอบ
    /// </summary>
    public void StopMonitoring()
    {
        _timer?.Stop();
        _timer = null;
    }

    /// <summary>
    /// ตรวจสอบการเชื่อมต่อ
    /// </summary>
    public async Task<bool> CheckConnectionAsync()
    {
        try
        {
            StatusText = "กำลังตรวจสอบ...";

            var isConnected = await _apiClient.CheckConnectivityAsync();

            IsOnline = isConnected;
            LastChecked = DateTime.Now;

            return isConnected;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Connection check failed: {ex.Message}");
            IsOnline = false;
            LastChecked = DateTime.Now;
            return false;
        }
    }

    /// <summary>
    /// อัพเดทการแสดงผลสถานะ
    /// </summary>
    private void UpdateStatusDisplay()
    {
        if (IsOnline)
        {
            StatusText = "เชื่อมต่อแล้ว";
            StatusColor = Color.FromArgb("#22C55E"); // Green
        }
        else
        {
            StatusText = "ออฟไลน์";
            StatusColor = Color.FromArgb("#EF4444"); // Red
        }

        OnPropertyChanged(nameof(StatusIcon));
    }

    /// <summary>
    /// Notify property changed
    /// </summary>
    protected virtual void OnPropertyChanged([CallerMemberName] string? propertyName = null)
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }
}

namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับสแกนบาร์โค้ด
/// </summary>
public interface IScannerService
{
    /// <summary>
    /// Event เมื่อสแกนบาร์โค้ดได้
    /// </summary>
    event EventHandler<BarcodeScannedEventArgs>? BarcodeScanned;

    /// <summary>
    /// สถานะการเชื่อมต่อ
    /// </summary>
    bool IsConnected { get; }

    /// <summary>
    /// ประเภท Scanner ที่ใช้
    /// </summary>
    ScannerType CurrentScannerType { get; }

    /// <summary>
    /// เริ่มฟังการสแกน (keyboard wedge)
    /// </summary>
    Task StartListeningAsync();

    /// <summary>
    /// หยุดฟังการสแกน
    /// </summary>
    Task StopListeningAsync();

    /// <summary>
    /// เปิดกล้องสแกน (สำหรับมือถือ)
    /// </summary>
    Task<string?> ScanWithCameraAsync();

    /// <summary>
    /// เชื่อมต่อ Bluetooth Scanner
    /// </summary>
    Task<bool> ConnectBluetoothScannerAsync(string address);

    /// <summary>
    /// ค้นหา Bluetooth Scanner
    /// </summary>
    Task<List<ScannerInfo>> DiscoverBluetoothScannersAsync();

    /// <summary>
    /// ตัดการเชื่อมต่อ
    /// </summary>
    Task DisconnectAsync();
}

/// <summary>
/// ประเภท Scanner
/// </summary>
public enum ScannerType
{
    /// <summary>
    /// ไม่มี
    /// </summary>
    None,

    /// <summary>
    /// USB (Keyboard Wedge)
    /// </summary>
    USB,

    /// <summary>
    /// Bluetooth
    /// </summary>
    Bluetooth,

    /// <summary>
    /// กล้อง
    /// </summary>
    Camera
}

/// <summary>
/// ข้อมูล Scanner
/// </summary>
public class ScannerInfo
{
    /// <summary>
    /// ชื่อ
    /// </summary>
    public string Name { get; set; } = string.Empty;

    /// <summary>
    /// ที่อยู่ (Bluetooth address)
    /// </summary>
    public string Address { get; set; } = string.Empty;

    /// <summary>
    /// ประเภท
    /// </summary>
    public ScannerType Type { get; set; }

    /// <summary>
    /// ออนไลน์
    /// </summary>
    public bool IsOnline { get; set; }
}

/// <summary>
/// Event args เมื่อสแกนบาร์โค้ด
/// </summary>
public class BarcodeScannedEventArgs : EventArgs
{
    /// <summary>
    /// บาร์โค้ดที่สแกนได้
    /// </summary>
    public string Barcode { get; set; } = string.Empty;

    /// <summary>
    /// รูปแบบบาร์โค้ด
    /// </summary>
    public string BarcodeFormat { get; set; } = string.Empty;

    /// <summary>
    /// เวลาที่สแกน
    /// </summary>
    public DateTime ScannedAt { get; set; } = DateTime.Now;

    /// <summary>
    /// แหล่งที่มา (USB, Bluetooth, Camera)
    /// </summary>
    public ScannerType Source { get; set; }
}

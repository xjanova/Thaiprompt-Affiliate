using TP.POS.Core.Interfaces;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับสแกนบาร์โค้ด
/// รองรับ USB Scanner, Bluetooth Scanner, และ Camera
/// </summary>
public class ScannerService : IScannerService
{
    private bool _isListening;
    private string _barcodeBuffer = string.Empty;
    private DateTime _lastKeyTime = DateTime.MinValue;

    public event EventHandler<BarcodeScannedEventArgs>? BarcodeScanned;

    public bool IsConnected { get; private set; }
    public ScannerType CurrentScannerType { get; private set; } = ScannerType.None;

    public Task StartListeningAsync()
    {
        _isListening = true;
        CurrentScannerType = ScannerType.USB;
        IsConnected = true;

        // TODO: เริ่มฟัง keyboard events สำหรับ USB Scanner (Keyboard Wedge)
        // USB Scanners ทำตัวเป็น keyboard และส่ง characters มาเร็วมาก
        // เราจะตรวจจับจากความเร็วในการพิมพ์

        System.Diagnostics.Debug.WriteLine("Scanner: Started listening for USB scanner input");

        return Task.CompletedTask;
    }

    public Task StopListeningAsync()
    {
        _isListening = false;
        IsConnected = false;
        CurrentScannerType = ScannerType.None;

        System.Diagnostics.Debug.WriteLine("Scanner: Stopped listening");

        return Task.CompletedTask;
    }

    public async Task<string?> ScanWithCameraAsync()
    {
        CurrentScannerType = ScannerType.Camera;

        try
        {
            // TODO: ใช้ ZXing.Net.Maui สำหรับสแกนด้วยกล้อง
            // var result = await BarcodeScanner.ScanAsync();

            // สำหรับ demo
            var result = await Shell.Current.DisplayPromptAsync(
                "สแกนบาร์โค้ด",
                "กรุณาใส่บาร์โค้ด (Demo mode)",
                "ตกลง",
                "ยกเลิก",
                placeholder: "ใส่บาร์โค้ดที่นี่...");

            if (!string.IsNullOrEmpty(result))
            {
                RaiseBarcodeScanned(result, "Manual", ScannerType.Camera);
                return result;
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Camera scan error: {ex.Message}");
        }

        return null;
    }

    public async Task<bool> ConnectBluetoothScannerAsync(string address)
    {
        try
        {
            // TODO: เชื่อมต่อ Bluetooth Scanner
            CurrentScannerType = ScannerType.Bluetooth;
            IsConnected = true;

            System.Diagnostics.Debug.WriteLine($"Scanner: Connected to Bluetooth scanner at {address}");

            return true;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Bluetooth connect error: {ex.Message}");
            return false;
        }
    }

    public async Task<List<ScannerInfo>> DiscoverBluetoothScannersAsync()
    {
        var scanners = new List<ScannerInfo>();

        try
        {
            // TODO: ค้นหา Bluetooth Scanners
            // ใช้ Plugin.BLE หรือ Microsoft.Maui.Devices.Sensors

            System.Diagnostics.Debug.WriteLine("Scanner: Searching for Bluetooth scanners...");
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Bluetooth discovery error: {ex.Message}");
        }

        return scanners;
    }

    public Task DisconnectAsync()
    {
        IsConnected = false;
        CurrentScannerType = ScannerType.None;
        return Task.CompletedTask;
    }

    /// <summary>
    /// ประมวลผล input จาก keyboard (สำหรับ USB Scanner)
    /// เรียกจาก KeyDown event
    /// </summary>
    public void ProcessKeyInput(char key)
    {
        if (!_isListening) return;

        var now = DateTime.Now;

        // ถ้าเวลาห่างกันมากกว่า 50ms ให้เริ่ม buffer ใหม่
        // USB Scanner จะพิมพ์เร็วมาก (ทุกตัวอักษรห่างกันไม่เกิน 30-50ms)
        if ((now - _lastKeyTime).TotalMilliseconds > 50)
        {
            _barcodeBuffer = string.Empty;
        }

        _lastKeyTime = now;

        if (key == '\r' || key == '\n')
        {
            // Enter = จบบาร์โค้ด
            if (_barcodeBuffer.Length >= 4) // บาร์โค้ดต้องมีอย่างน้อย 4 ตัวอักษร
            {
                RaiseBarcodeScanned(_barcodeBuffer, "Code128", ScannerType.USB);
            }
            _barcodeBuffer = string.Empty;
        }
        else if (char.IsLetterOrDigit(key) || key == '-')
        {
            _barcodeBuffer += key;
        }
    }

    private void RaiseBarcodeScanned(string barcode, string format, ScannerType source)
    {
        BarcodeScanned?.Invoke(this, new BarcodeScannedEventArgs
        {
            Barcode = barcode,
            BarcodeFormat = format,
            ScannedAt = DateTime.Now,
            Source = source
        });

        System.Diagnostics.Debug.WriteLine($"Scanner: Barcode scanned - {barcode} ({format}) from {source}");
    }
}

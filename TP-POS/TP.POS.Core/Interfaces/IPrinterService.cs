using TP.POS.Core.Entities;

namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับพิมพ์ใบเสร็จ
/// </summary>
public interface IPrinterService
{
    /// <summary>
    /// ตรวจสอบว่ามีเครื่องพิมพ์เชื่อมต่ออยู่หรือไม่
    /// </summary>
    Task<bool> IsConnectedAsync();

    /// <summary>
    /// ค้นหาเครื่องพิมพ์ที่พร้อมใช้งาน
    /// </summary>
    Task<List<PrinterInfo>> DiscoverPrintersAsync();

    /// <summary>
    /// ดึงรายชื่อเครื่องพิมพ์
    /// </summary>
    Task<List<string>> GetAvailablePrintersAsync();

    /// <summary>
    /// เชื่อมต่อเครื่องพิมพ์
    /// </summary>
    Task<bool> ConnectAsync(string printerAddress);

    /// <summary>
    /// ตัดการเชื่อมต่อ
    /// </summary>
    Task DisconnectAsync();

    /// <summary>
    /// พิมพ์ใบเสร็จ
    /// </summary>
    Task<bool> PrintReceiptAsync(Transaction transaction, List<TransactionItem> items, ReceiptSettings? settings = null);

    /// <summary>
    /// พิมพ์รายงาน X
    /// </summary>
    Task<bool> PrintXReportAsync(DailySummary summary);

    /// <summary>
    /// พิมพ์รายงาน Z (ปิดวัน)
    /// </summary>
    Task<bool> PrintZReportAsync(DailySummary summary);

    /// <summary>
    /// พิมพ์ข้อความทดสอบ
    /// </summary>
    Task<bool> PrintTestAsync();

    /// <summary>
    /// เปิดลิ้นชักเก็บเงิน
    /// </summary>
    Task<bool> OpenCashDrawerAsync();

    /// <summary>
    /// ตัดกระดาษ
    /// </summary>
    Task<bool> CutPaperAsync();
}

/// <summary>
/// ข้อมูลเครื่องพิมพ์
/// </summary>
public class PrinterInfo
{
    /// <summary>
    /// ชื่อเครื่องพิมพ์
    /// </summary>
    public string Name { get; set; } = string.Empty;

    /// <summary>
    /// ที่อยู่ (IP, USB, Bluetooth)
    /// </summary>
    public string Address { get; set; } = string.Empty;

    /// <summary>
    /// ประเภทการเชื่อมต่อ
    /// </summary>
    public PrinterConnectionType ConnectionType { get; set; }

    /// <summary>
    /// สถานะออนไลน์
    /// </summary>
    public bool IsOnline { get; set; }
}

/// <summary>
/// ประเภทการเชื่อมต่อเครื่องพิมพ์
/// </summary>
public enum PrinterConnectionType
{
    USB,
    Network,
    Bluetooth,
    Serial
}

/// <summary>
/// การตั้งค่าใบเสร็จ
/// </summary>
public class ReceiptSettings
{
    /// <summary>
    /// ชื่อร้าน
    /// </summary>
    public string StoreName { get; set; } = "TP-POS";

    /// <summary>
    /// ที่อยู่ร้าน
    /// </summary>
    public string? StoreAddress { get; set; }

    /// <summary>
    /// เบอร์โทร
    /// </summary>
    public string? StorePhone { get; set; }

    /// <summary>
    /// เลขประจำตัวผู้เสียภาษี
    /// </summary>
    public string? TaxId { get; set; }

    /// <summary>
    /// โลโก้ (base64)
    /// </summary>
    public string? LogoBase64 { get; set; }

    /// <summary>
    /// ข้อความท้ายใบเสร็จ
    /// </summary>
    public string? FooterMessage { get; set; } = "ขอบคุณที่ใช้บริการ";

    /// <summary>
    /// แสดง QR Code
    /// </summary>
    public bool ShowQRCode { get; set; }

    /// <summary>
    /// QR Code URL
    /// </summary>
    public string? QRCodeUrl { get; set; }

    /// <summary>
    /// ความกว้างกระดาษ (mm)
    /// </summary>
    public int PaperWidth { get; set; } = 80;
}

/// <summary>
/// สรุปยอดขายประจำวัน
/// </summary>
public class DailySummary
{
    /// <summary>
    /// วันที่
    /// </summary>
    public DateTime Date { get; set; }

    /// <summary>
    /// จำนวนรายการขาย
    /// </summary>
    public int TransactionCount { get; set; }

    /// <summary>
    /// ยอดขายรวม
    /// </summary>
    public decimal TotalSales { get; set; }

    /// <summary>
    /// ส่วนลดรวม
    /// </summary>
    public decimal TotalDiscount { get; set; }

    /// <summary>
    /// ภาษีรวม
    /// </summary>
    public decimal TotalTax { get; set; }

    /// <summary>
    /// ยอดสุทธิ
    /// </summary>
    public decimal NetSales { get; set; }

    /// <summary>
    /// ยอดเงินสด
    /// </summary>
    public decimal CashAmount { get; set; }

    /// <summary>
    /// ยอดบัตร
    /// </summary>
    public decimal CardAmount { get; set; }

    /// <summary>
    /// ยอด QR
    /// </summary>
    public decimal QRAmount { get; set; }

    /// <summary>
    /// ยอดอื่นๆ
    /// </summary>
    public decimal OtherAmount { get; set; }

    /// <summary>
    /// จำนวนสินค้าที่ขาย
    /// </summary>
    public int TotalItemsSold { get; set; }

    /// <summary>
    /// จำนวนรายการยกเลิก
    /// </summary>
    public int CancelledCount { get; set; }

    /// <summary>
    /// ยอดยกเลิก
    /// </summary>
    public decimal CancelledAmount { get; set; }
}

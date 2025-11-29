using TP.POS.Core.Entities;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับพิมพ์ใบเสร็จ
/// รองรับ ESC/POS Thermal Printers
/// </summary>
public class PrinterService : IPrinterService
{
    private string? _connectedPrinterAddress;

    public async Task<bool> IsConnectedAsync()
    {
        return !string.IsNullOrEmpty(_connectedPrinterAddress);
    }

    public async Task<List<PrinterInfo>> DiscoverPrintersAsync()
    {
        // TODO: ค้นหาเครื่องพิมพ์ที่เชื่อมต่อ
        // - USB Printers
        // - Network Printers
        // - Bluetooth Printers

        return new List<PrinterInfo>
        {
            new PrinterInfo
            {
                Name = "Demo Printer",
                Address = "demo://printer",
                ConnectionType = PrinterConnectionType.USB,
                IsOnline = true
            }
        };
    }

    public async Task<bool> ConnectAsync(string printerAddress)
    {
        try
        {
            // TODO: เชื่อมต่อเครื่องพิมพ์จริง
            _connectedPrinterAddress = printerAddress;
            return true;
        }
        catch
        {
            return false;
        }
    }

    public Task DisconnectAsync()
    {
        _connectedPrinterAddress = null;
        return Task.CompletedTask;
    }

    public async Task<bool> PrintReceiptAsync(
        Transaction transaction,
        List<TransactionItem> items,
        ReceiptSettings? settings = null)
    {
        settings ??= new ReceiptSettings();

        try
        {
            // สร้างคำสั่ง ESC/POS
            var commands = new List<byte>();

            // Initialize printer
            commands.AddRange(new byte[] { 0x1B, 0x40 });

            // Center align
            commands.AddRange(new byte[] { 0x1B, 0x61, 0x01 });

            // Print store name (bold, double size)
            commands.AddRange(new byte[] { 0x1B, 0x21, 0x30 }); // Bold + Double
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes(settings.StoreName + "\n"));

            // Normal text
            commands.AddRange(new byte[] { 0x1B, 0x21, 0x00 });

            // Store address
            if (!string.IsNullOrEmpty(settings.StoreAddress))
            {
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes(settings.StoreAddress + "\n"));
            }

            // Store phone
            if (!string.IsNullOrEmpty(settings.StorePhone))
            {
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"โทร: {settings.StorePhone}\n"));
            }

            // Tax ID
            if (!string.IsNullOrEmpty(settings.TaxId))
            {
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"เลขประจำตัวผู้เสียภาษี: {settings.TaxId}\n"));
            }

            // Separator
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes("================================\n"));

            // Receipt number and date
            commands.AddRange(new byte[] { 0x1B, 0x61, 0x00 }); // Left align
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"เลขที่: {transaction.ReceiptNumber}\n"));
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"วันที่: {transaction.TransactionDate:dd/MM/yyyy HH:mm}\n"));

            // Customer
            if (!string.IsNullOrEmpty(transaction.CustomerName))
            {
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"ลูกค้า: {transaction.CustomerName}\n"));
            }

            // Cashier
            if (!string.IsNullOrEmpty(transaction.CashierName))
            {
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"พนักงาน: {transaction.CashierName}\n"));
            }

            commands.AddRange(System.Text.Encoding.UTF8.GetBytes("--------------------------------\n"));

            // Items
            foreach (var item in items)
            {
                // Product name
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"{item.ProductName}\n"));
                // Quantity x Price = Total
                var line = $"  {item.Quantity} x {item.UnitPrice:N2}";
                var total = $"{item.LineTotal:N2}";
                var spaces = 32 - line.Length - total.Length;
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes(line + new string(' ', Math.Max(1, spaces)) + total + "\n"));
            }

            commands.AddRange(System.Text.Encoding.UTF8.GetBytes("--------------------------------\n"));

            // Summary
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"ยอดรวม:{new string(' ', 17)}{transaction.Subtotal:N2}\n"));

            if (transaction.DiscountAmount > 0)
            {
                commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"ส่วนลด:{new string(' ', 16)}-{transaction.DiscountAmount:N2}\n"));
            }

            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"ภาษี 7%:{new string(' ', 16)}{transaction.TaxAmount:N2}\n"));

            commands.AddRange(System.Text.Encoding.UTF8.GetBytes("================================\n"));

            // Total (bold, double)
            commands.AddRange(new byte[] { 0x1B, 0x21, 0x30 });
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"ยอดสุทธิ:{new string(' ', 10)}{transaction.TotalAmount:N2}\n"));
            commands.AddRange(new byte[] { 0x1B, 0x21, 0x00 });

            commands.AddRange(System.Text.Encoding.UTF8.GetBytes("================================\n"));

            // Payment info
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"ชำระ: {transaction.PaymentMethod.ToThaiName()}\n"));
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"รับเงิน:{new string(' ', 16)}{transaction.ReceivedAmount:N2}\n"));
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes($"เงินทอน:{new string(' ', 16)}{transaction.ChangeAmount:N2}\n"));

            // Footer
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes("\n"));
            commands.AddRange(new byte[] { 0x1B, 0x61, 0x01 }); // Center
            commands.AddRange(System.Text.Encoding.UTF8.GetBytes(settings.FooterMessage + "\n\n"));

            // Cut paper
            commands.AddRange(new byte[] { 0x1D, 0x56, 0x42, 0x00 });

            // TODO: ส่งคำสั่งไปยังเครื่องพิมพ์จริง
            System.Diagnostics.Debug.WriteLine($"Printing receipt: {transaction.ReceiptNumber}");
            System.Diagnostics.Debug.WriteLine($"Commands: {commands.Count} bytes");

            return true;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Print error: {ex.Message}");
            return false;
        }
    }

    public async Task<bool> PrintXReportAsync(DailySummary summary)
    {
        // TODO: พิมพ์ X Report
        return true;
    }

    public async Task<bool> PrintZReportAsync(DailySummary summary)
    {
        // TODO: พิมพ์ Z Report
        return true;
    }

    public async Task<bool> PrintTestAsync()
    {
        // TODO: พิมพ์ทดสอบ
        return true;
    }

    public async Task<bool> OpenCashDrawerAsync()
    {
        try
        {
            // ESC/POS command to open cash drawer
            // Pulse to pin 2: 1B 70 00 19 FA
            // Pulse to pin 5: 1B 70 01 19 FA
            var commands = new byte[] { 0x1B, 0x70, 0x00, 0x19, 0xFA };

            // TODO: ส่งคำสั่งไปยังเครื่องพิมพ์
            System.Diagnostics.Debug.WriteLine("Opening cash drawer...");

            return true;
        }
        catch
        {
            return false;
        }
    }

    public async Task<bool> CutPaperAsync()
    {
        try
        {
            // ESC/POS command to cut paper
            var commands = new byte[] { 0x1D, 0x56, 0x42, 0x00 };

            // TODO: ส่งคำสั่งไปยังเครื่องพิมพ์
            return true;
        }
        catch
        {
            return false;
        }
    }
}

namespace TP.POS.Core.Enums;

/// <summary>
/// วิธีการชำระเงิน
/// </summary>
public enum PaymentMethod
{
    /// <summary>
    /// เงินสด
    /// </summary>
    Cash = 1,

    /// <summary>
    /// บัตรเครดิต
    /// </summary>
    CreditCard = 2,

    /// <summary>
    /// บัตรเดบิต
    /// </summary>
    DebitCard = 3,

    /// <summary>
    /// QR Code (พร้อมเพย์)
    /// </summary>
    QRCode = 4,

    /// <summary>
    /// โอนเงิน
    /// </summary>
    BankTransfer = 5,

    /// <summary>
    /// Wallet (กระเป๋าเงินในระบบ)
    /// </summary>
    Wallet = 6,

    /// <summary>
    /// เครดิต/ลูกหนี้
    /// </summary>
    Credit = 7,

    /// <summary>
    /// อื่นๆ
    /// </summary>
    Other = 99
}

/// <summary>
/// Extension methods สำหรับ PaymentMethod
/// </summary>
public static class PaymentMethodExtensions
{
    /// <summary>
    /// แปลงเป็นชื่อภาษาไทย
    /// </summary>
    public static string ToThaiName(this PaymentMethod method)
    {
        return method switch
        {
            PaymentMethod.Cash => "เงินสด",
            PaymentMethod.CreditCard => "บัตรเครดิต",
            PaymentMethod.DebitCard => "บัตรเดบิต",
            PaymentMethod.QRCode => "QR Code / พร้อมเพย์",
            PaymentMethod.BankTransfer => "โอนเงิน",
            PaymentMethod.Wallet => "กระเป๋าเงิน",
            PaymentMethod.Credit => "เครดิต",
            PaymentMethod.Other => "อื่นๆ",
            _ => "ไม่ระบุ"
        };
    }

    /// <summary>
    /// ดึงไอคอน
    /// </summary>
    public static string GetIcon(this PaymentMethod method)
    {
        return method switch
        {
            PaymentMethod.Cash => "💵",
            PaymentMethod.CreditCard => "💳",
            PaymentMethod.DebitCard => "💳",
            PaymentMethod.QRCode => "📱",
            PaymentMethod.BankTransfer => "🏦",
            PaymentMethod.Wallet => "👛",
            PaymentMethod.Credit => "📝",
            PaymentMethod.Other => "💰",
            _ => "💰"
        };
    }
}

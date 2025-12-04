using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าชำระเงิน
/// </summary>
[QueryProperty(nameof(TotalAmount), "TotalAmount")]
public partial class CheckoutViewModel : BaseViewModel
{
    private readonly ICartService _cartService;
    private readonly ITransactionService _transactionService;
    private readonly IPrinterService _printerService;

    #region Observable Properties

    /// <summary>
    /// ยอดที่ต้องชำระ
    /// </summary>
    [ObservableProperty]
    private decimal _totalAmount;

    /// <summary>
    /// รายการในตะกร้า
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<CartItem> _cartItems = new();

    /// <summary>
    /// วิธีชำระเงินที่เลือก
    /// </summary>
    [ObservableProperty]
    private PaymentMethod _selectedPaymentMethod = PaymentMethod.Cash;

    /// <summary>
    /// จำนวนเงินที่รับ
    /// </summary>
    [ObservableProperty]
    [NotifyPropertyChangedFor(nameof(ChangeAmount))]
    [NotifyPropertyChangedFor(nameof(CanComplete))]
    [NotifyPropertyChangedFor(nameof(IsReceivedEnough))]
    private decimal _receivedAmount;

    /// <summary>
    /// เงินทอน
    /// </summary>
    public decimal ChangeAmount => ReceivedAmount - TotalAmount;

    /// <summary>
    /// รับเงินพอหรือยัง
    /// </summary>
    public bool IsReceivedEnough => ReceivedAmount >= TotalAmount;

    /// <summary>
    /// สามารถชำระเงินได้หรือไม่
    /// </summary>
    public bool CanComplete => SelectedPaymentMethod != PaymentMethod.Cash || ReceivedAmount >= TotalAmount;

    /// <summary>
    /// เลขอ้างอิงการชำระ (สำหรับ QR/บัตร)
    /// </summary>
    [ObservableProperty]
    private string _paymentReference = string.Empty;

    /// <summary>
    /// หมายเหตุ
    /// </summary>
    [ObservableProperty]
    private string _notes = string.Empty;

    /// <summary>
    /// ลูกค้าที่เลือก
    /// </summary>
    [ObservableProperty]
    private Customer? _selectedCustomer;

    /// <summary>
    /// ชื่อลูกค้า
    /// </summary>
    [ObservableProperty]
    private string _customerName = "ลูกค้าทั่วไป";

    /// <summary>
    /// ข้อมูลสรุปตะกร้า
    /// </summary>
    [ObservableProperty]
    private CartSummary? _cartSummary;

    /// <summary>
    /// รายการวิธีชำระเงิน
    /// </summary>
    [ObservableProperty]
    private ObservableCollection<PaymentMethodOption> _paymentMethods = new();

    /// <summary>
    /// แสดง Quick Cash หรือไม่
    /// </summary>
    [ObservableProperty]
    private bool _showQuickCash = true;

    /// <summary>
    /// แสดงช่องกรอกเลขอ้างอิงหรือไม่
    /// </summary>
    [ObservableProperty]
    private bool _showReferenceInput;

    /// <summary>
    /// Transaction ที่สร้างเสร็จ
    /// </summary>
    [ObservableProperty]
    private Transaction? _completedTransaction;

    /// <summary>
    /// แสดงหน้าสำเร็จหรือไม่
    /// </summary>
    [ObservableProperty]
    private bool _showSuccess;

    #endregion

    public CheckoutViewModel(
        ICartService cartService,
        ITransactionService transactionService,
        IPrinterService printerService)
    {
        _cartService = cartService;
        _transactionService = transactionService;
        _printerService = printerService;

        Title = "ชำระเงิน";

        // เตรียมวิธีชำระเงิน
        InitializePaymentMethods();
    }

    /// <summary>
    /// Initialize
    /// </summary>
    public async Task InitializeAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังโหลดข้อมูล...";

            // โหลดตะกร้า
            var items = await _cartService.GetAllItemsAsync();
            CartItems = new ObservableCollection<CartItem>(items);

            // โหลดสรุป
            CartSummary = await _cartService.GetSummaryAsync();
            TotalAmount = CartSummary.TotalAmount;

            // ตั้งค่าเงินที่รับเริ่มต้น
            ReceivedAmount = 0;
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    /// <summary>
    /// เตรียมรายการวิธีชำระเงิน
    /// </summary>
    private void InitializePaymentMethods()
    {
        PaymentMethods = new ObservableCollection<PaymentMethodOption>
        {
            new() { Method = PaymentMethod.Cash, Name = "เงินสด", Icon = "💵", IsSelected = true },
            new() { Method = PaymentMethod.QRCode, Name = "QR Code", Icon = "📱", IsSelected = false },
            new() { Method = PaymentMethod.CreditCard, Name = "บัตรเครดิต", Icon = "💳", IsSelected = false },
            new() { Method = PaymentMethod.DebitCard, Name = "บัตรเดบิต", Icon = "💳", IsSelected = false },
            new() { Method = PaymentMethod.BankTransfer, Name = "โอนเงิน", Icon = "🏦", IsSelected = false },
            new() { Method = PaymentMethod.Wallet, Name = "กระเป๋าเงิน", Icon = "👛", IsSelected = false },
        };
    }

    #region Commands

    /// <summary>
    /// เลือกวิธีชำระเงิน
    /// </summary>
    [RelayCommand]
    private void SelectPaymentMethod(PaymentMethodOption option)
    {
        // อัพเดท UI
        foreach (var pm in PaymentMethods)
        {
            pm.IsSelected = pm.Method == option.Method;
        }

        SelectedPaymentMethod = option.Method;

        // แสดง/ซ่อน UI ตามวิธีชำระ
        ShowQuickCash = option.Method == PaymentMethod.Cash;
        ShowReferenceInput = option.Method != PaymentMethod.Cash;

        // ถ้าไม่ใช่เงินสด ให้ set เงินรับเท่ากับยอด
        if (option.Method != PaymentMethod.Cash)
        {
            ReceivedAmount = TotalAmount;
        }
        else
        {
            ReceivedAmount = 0;
        }
    }

    /// <summary>
    /// กดปุ่ม Quick Cash
    /// </summary>
    [RelayCommand]
    private void QuickCash(string amount)
    {
        if (decimal.TryParse(amount, out var value))
        {
            ReceivedAmount = value;
        }
    }

    /// <summary>
    /// กดปุ่มตัวเลข Numpad
    /// </summary>
    [RelayCommand]
    private void NumpadInput(string input)
    {
        if (input == "C")
        {
            ReceivedAmount = 0;
            return;
        }

        if (input == "⌫")
        {
            var str = ReceivedAmount.ToString("0");
            if (str.Length > 1)
            {
                ReceivedAmount = decimal.Parse(str[..^1]);
            }
            else
            {
                ReceivedAmount = 0;
            }
            return;
        }

        if (input == ".")
        {
            // ไม่รองรับทศนิยมใน numpad แบบนี้
            return;
        }

        var currentStr = ReceivedAmount.ToString("0");
        if (currentStr == "0")
        {
            ReceivedAmount = decimal.Parse(input);
        }
        else
        {
            ReceivedAmount = decimal.Parse(currentStr + input);
        }
    }

    /// <summary>
    /// รับเงินพอดี (Exact)
    /// </summary>
    [RelayCommand]
    private void ExactAmount()
    {
        ReceivedAmount = TotalAmount;
    }

    /// <summary>
    /// เลือกลูกค้า
    /// </summary>
    [RelayCommand]
    private async Task SelectCustomerAsync()
    {
        await Shell.Current.GoToAsync("customerselect");
    }

    /// <summary>
    /// ยืนยันชำระเงิน
    /// </summary>
    [RelayCommand]
    private async Task CompleteCheckoutAsync()
    {
        if (!CanComplete)
        {
            await Shell.Current.DisplayAlert("เงินไม่พอ", "กรุณารับเงินให้ครบถ้วน", "ตกลง");
            return;
        }

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังบันทึกรายการ...";

            // สร้าง Transaction
            var transaction = await _cartService.CheckoutAsync(
                SelectedPaymentMethod,
                ReceivedAmount,
                SelectedCustomer,
                PaymentReference,
                Notes
            );

            CompletedTransaction = transaction;

            // พิมพ์ใบเสร็จ
            BusyMessage = "กำลังพิมพ์ใบเสร็จ...";
            await PrintReceiptAsync(transaction);

            // แสดงหน้าสำเร็จ
            ShowSuccess = true;

            // Haptic
            HapticFeedback.Default.Perform(HapticFeedbackType.LongPress);
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("เกิดข้อผิดพลาด", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    /// <summary>
    /// พิมพ์ใบเสร็จ
    /// </summary>
    private async Task PrintReceiptAsync(Transaction transaction)
    {
        try
        {
            var items = await _transactionService.GetItemsAsync(transaction.Id);
            await _printerService.PrintReceiptAsync(transaction, items);
        }
        catch
        {
            // ถ้าพิมพ์ไม่ได้ก็ไม่เป็นไร
        }
    }

    /// <summary>
    /// พิมพ์ใบเสร็จซ้ำ
    /// </summary>
    [RelayCommand]
    private async Task ReprintReceiptAsync()
    {
        if (CompletedTransaction == null) return;

        try
        {
            IsBusy = true;
            BusyMessage = "กำลังพิมพ์ใบเสร็จ...";

            await PrintReceiptAsync(CompletedTransaction);
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("พิมพ์ไม่สำเร็จ", ex.Message, "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    /// <summary>
    /// ขายรายการใหม่
    /// </summary>
    [RelayCommand]
    private async Task NewSaleAsync()
    {
        await Shell.Current.GoToAsync("//pos");
    }

    /// <summary>
    /// กลับหน้า POS
    /// </summary>
    [RelayCommand]
    private async Task GoBackAsync()
    {
        await Shell.Current.GoToAsync("..");
    }

    /// <summary>
    /// ยกเลิก
    /// </summary>
    [RelayCommand]
    private async Task CancelAsync()
    {
        bool confirm = await Shell.Current.DisplayAlert(
            "ยกเลิก",
            "ต้องการยกเลิกการชำระเงิน?",
            "ยกเลิก", "กลับ");

        if (confirm)
        {
            await Shell.Current.GoToAsync("..");
        }
    }

    #endregion
}

#region Helper Models

/// <summary>
/// Model สำหรับแสดงวิธีชำระเงิน
/// </summary>
public partial class PaymentMethodOption : ObservableObject
{
    public PaymentMethod Method { get; set; }
    public string Name { get; set; } = string.Empty;
    public string Icon { get; set; } = string.Empty;

    [ObservableProperty]
    private bool _isSelected;

    [ObservableProperty]
    private Color _backgroundColor = Color.FromArgb("#F3F4F6");

    [ObservableProperty]
    private Color _textColor = Color.FromArgb("#374151");

    [ObservableProperty]
    private Color _borderColor = Color.FromArgb("#E5E7EB");

    partial void OnIsSelectedChanged(bool value)
    {
        if (value)
        {
            BackgroundColor = Color.FromArgb("#22C55E");
            TextColor = Colors.White;
            BorderColor = Color.FromArgb("#22C55E");
        }
        else
        {
            BackgroundColor = Color.FromArgb("#F3F4F6");
            TextColor = Color.FromArgb("#374151");
            BorderColor = Color.FromArgb("#E5E7EB");
        }
    }
}

#endregion

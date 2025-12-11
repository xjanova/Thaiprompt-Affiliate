using System.Collections.ObjectModel;
using System.Windows.Input;
using TP.POS.Core.Entities;
using TP.POS.Core.Enums;
using TP.POS.Core.Interfaces;

namespace TP.POS.App.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้าชำระเงิน
/// ใช้ manual implementation เพื่อหลีกเลี่ยง source generator conflicts
/// </summary>
[QueryProperty(nameof(TotalAmount), "TotalAmount")]
public class CheckoutViewModel : BaseViewModel
{
    private readonly ICartService _cartService;
    private readonly ITransactionService _transactionService;
    private readonly IPrinterService _printerService;

    #region Private Fields

    private decimal _totalAmount;
    private ObservableCollection<CartItem> _cartItems = new();
    private PaymentMethod _selectedPaymentMethod = PaymentMethod.Cash;
    private decimal _receivedAmount;
    private string _paymentReference = string.Empty;
    private string _notes = string.Empty;
    private Customer? _selectedCustomer;
    private string _customerName = "ลูกค้าทั่วไป";
    private CartSummary? _cartSummary;
    private ObservableCollection<PaymentMethodOption> _paymentMethods = new();
    private bool _showQuickCash = true;
    private bool _showReferenceInput;
    private Transaction? _completedTransaction;
    private bool _showSuccess;

    #endregion

    #region Properties

    public decimal TotalAmount
    {
        get => _totalAmount;
        set => SetProperty(ref _totalAmount, value);
    }

    public ObservableCollection<CartItem> CartItems
    {
        get => _cartItems;
        set => SetProperty(ref _cartItems, value);
    }

    public PaymentMethod SelectedPaymentMethod
    {
        get => _selectedPaymentMethod;
        set => SetProperty(ref _selectedPaymentMethod, value);
    }

    public decimal ReceivedAmount
    {
        get => _receivedAmount;
        set
        {
            if (SetProperty(ref _receivedAmount, value))
            {
                OnPropertyChanged(nameof(ChangeAmount));
                OnPropertyChanged(nameof(CanComplete));
                OnPropertyChanged(nameof(IsReceivedEnough));
            }
        }
    }

    public decimal ChangeAmount => ReceivedAmount - TotalAmount;
    public bool IsReceivedEnough => ReceivedAmount >= TotalAmount;
    public bool CanComplete => SelectedPaymentMethod != PaymentMethod.Cash || ReceivedAmount >= TotalAmount;

    public string PaymentReference
    {
        get => _paymentReference;
        set => SetProperty(ref _paymentReference, value);
    }

    public string Notes
    {
        get => _notes;
        set => SetProperty(ref _notes, value);
    }

    public Customer? SelectedCustomer
    {
        get => _selectedCustomer;
        set => SetProperty(ref _selectedCustomer, value);
    }

    public string CustomerName
    {
        get => _customerName;
        set => SetProperty(ref _customerName, value);
    }

    public CartSummary? CartSummary
    {
        get => _cartSummary;
        set => SetProperty(ref _cartSummary, value);
    }

    public ObservableCollection<PaymentMethodOption> PaymentMethods
    {
        get => _paymentMethods;
        set => SetProperty(ref _paymentMethods, value);
    }

    public bool ShowQuickCash
    {
        get => _showQuickCash;
        set => SetProperty(ref _showQuickCash, value);
    }

    public bool ShowReferenceInput
    {
        get => _showReferenceInput;
        set => SetProperty(ref _showReferenceInput, value);
    }

    public Transaction? CompletedTransaction
    {
        get => _completedTransaction;
        set => SetProperty(ref _completedTransaction, value);
    }

    public bool ShowSuccess
    {
        get => _showSuccess;
        set => SetProperty(ref _showSuccess, value);
    }

    #endregion

    #region Commands

    public ICommand SelectPaymentMethodCommand { get; }
    public ICommand QuickCashCommand { get; }
    public ICommand NumpadInputCommand { get; }
    public ICommand ExactAmountCommand { get; }
    public ICommand SelectCustomerCommand { get; }
    public ICommand CompleteCheckoutCommand { get; }
    public ICommand ReprintReceiptCommand { get; }
    public ICommand NewSaleCommand { get; }
    public ICommand GoBackCommand { get; }
    public ICommand CancelCommand { get; }

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

        // Initialize commands
        SelectPaymentMethodCommand = new Command<PaymentMethodOption>(SelectPaymentMethod);
        QuickCashCommand = new Command<string>(QuickCash);
        NumpadInputCommand = new Command<string>(NumpadInput);
        ExactAmountCommand = new Command(ExactAmount);
        SelectCustomerCommand = new Command(async () => await SelectCustomerAsync());
        CompleteCheckoutCommand = new Command(async () => await CompleteCheckoutAsync());
        ReprintReceiptCommand = new Command(async () => await ReprintReceiptAsync());
        NewSaleCommand = new Command(async () => await NewSaleAsync());
        GoBackCommand = new Command(async () => await GoBackAsync());
        CancelCommand = new Command(async () => await CancelAsync());

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

    #region Command Implementations

    private void SelectPaymentMethod(PaymentMethodOption option)
    {
        foreach (var pm in PaymentMethods)
        {
            pm.IsSelected = pm.Method == option.Method;
        }

        SelectedPaymentMethod = option.Method;
        ShowQuickCash = option.Method == PaymentMethod.Cash;
        ShowReferenceInput = option.Method != PaymentMethod.Cash;

        if (option.Method != PaymentMethod.Cash)
        {
            ReceivedAmount = TotalAmount;
        }
        else
        {
            ReceivedAmount = 0;
        }
    }

    private void QuickCash(string amount)
    {
        if (decimal.TryParse(amount, out var value))
        {
            ReceivedAmount = value;
        }
    }

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

    private void ExactAmount()
    {
        ReceivedAmount = TotalAmount;
    }

    private async Task SelectCustomerAsync()
    {
        await Shell.Current.GoToAsync("customerselect");
    }

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

            var transaction = await _cartService.CheckoutAsync(
                SelectedPaymentMethod,
                ReceivedAmount,
                SelectedCustomer,
                PaymentReference,
                Notes
            );

            CompletedTransaction = transaction;

            BusyMessage = "กำลังพิมพ์ใบเสร็จ...";
            await PrintReceiptAsync(transaction);

            ShowSuccess = true;
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

    private async Task NewSaleAsync()
    {
        await Shell.Current.GoToAsync("//pos");
    }

    private async Task GoBackAsync()
    {
        await Shell.Current.GoToAsync("..");
    }

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
public class PaymentMethodOption : BaseViewModel
{
    public PaymentMethod Method { get; set; }
    public string Name { get; set; } = string.Empty;
    public string Icon { get; set; } = string.Empty;

    private bool _isSelected;
    public bool IsSelected
    {
        get => _isSelected;
        set
        {
            if (SetProperty(ref _isSelected, value))
            {
                UpdateColors();
            }
        }
    }

    private Color _backgroundColor = Color.FromArgb("#F3F4F6");
    public Color BackgroundColor
    {
        get => _backgroundColor;
        set => SetProperty(ref _backgroundColor, value);
    }

    private Color _textColor = Color.FromArgb("#374151");
    public Color TextColor
    {
        get => _textColor;
        set => SetProperty(ref _textColor, value);
    }

    private Color _borderColor = Color.FromArgb("#E5E7EB");
    public Color BorderColor
    {
        get => _borderColor;
        set => SetProperty(ref _borderColor, value);
    }

    private void UpdateColors()
    {
        if (IsSelected)
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

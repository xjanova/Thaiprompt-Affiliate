using TP.POS.App.Services;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าจอ Customer Display (สำหรับแสดงบนจอที่ 2)
/// </summary>
public partial class CustomerDisplayPage : ContentPage
{
    private readonly IDualMonitorService? _dualMonitorService;
    private IDispatcherTimer? _clockTimer;

    public CustomerDisplayPage()
    {
        InitializeComponent();

        // ดึง Service จาก DI (ถ้ามี)
        _dualMonitorService = Application.Current?.Handler?.MauiContext?.Services
            .GetService<IDualMonitorService>();

        // ลงทะเบียน Event
        if (_dualMonitorService != null)
        {
            _dualMonitorService.CartUpdated += OnCartUpdated;
        }

        // เริ่ม Timer สำหรับนาฬิกา
        StartClockTimer();

        // อัพเดทวันที่
        UpdateDateTime();
    }

    /// <summary>
    /// เริ่ม Timer สำหรับอัพเดทนาฬิกา
    /// </summary>
    private void StartClockTimer()
    {
        _clockTimer = Application.Current?.Dispatcher.CreateTimer();
        if (_clockTimer != null)
        {
            _clockTimer.Interval = TimeSpan.FromSeconds(1);
            _clockTimer.Tick += (s, e) => UpdateDateTime();
            _clockTimer.Start();
        }
    }

    /// <summary>
    /// อัพเดทวันที่และเวลา
    /// </summary>
    private void UpdateDateTime()
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            var now = DateTime.Now;
            lblTime.Text = now.ToString("HH:mm:ss");

            // วันที่ภาษาไทย
            var thaiDayNames = new[] { "อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์" };
            var thaiMonthNames = new[] { "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                                         "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม" };

            var dayName = thaiDayNames[(int)now.DayOfWeek];
            var monthName = thaiMonthNames[now.Month - 1];
            var buddhistYear = now.Year + 543;

            lblDate.Text = $"วัน{dayName}ที่ {now.Day} {monthName} {buddhistYear}";
        });
    }

    /// <summary>
    /// Event handler สำหรับ Cart update
    /// </summary>
    private void OnCartUpdated(object? sender, CartUpdateEventArgs e)
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            if (e.ShowThankYou)
            {
                ShowThankYouMessage();
            }
            else
            {
                ShowCartItems(e);
            }
        });
    }

    /// <summary>
    /// แสดงรายการสินค้า
    /// </summary>
    private void ShowCartItems(CartUpdateEventArgs e)
    {
        // ซ่อน Thank You, แสดง Cart
        thankYouSection.IsVisible = false;
        cartSection.IsVisible = true;

        // อัพเดทรายการ
        cartItemsCollection.ItemsSource = e.Items;

        // อัพเดทยอดเงิน
        lblSubtotal.Text = $"฿{e.Subtotal:N2}";

        // แสดง/ซ่อนส่วนลด
        if (e.Discount > 0)
        {
            discountRow.IsVisible = true;
            lblDiscount.Text = $"-฿{e.Discount:N2}";
        }
        else
        {
            discountRow.IsVisible = false;
        }

        lblTax.Text = $"฿{e.Tax:N2}";
        lblTotal.Text = $"฿{e.Total:N2}";
    }

    /// <summary>
    /// แสดงข้อความขอบคุณ
    /// </summary>
    private void ShowThankYouMessage()
    {
        // ซ่อน Cart, แสดง Thank You
        cartSection.IsVisible = false;
        thankYouSection.IsVisible = true;

        // Reset ยอดเงิน
        lblSubtotal.Text = "฿0.00";
        discountRow.IsVisible = false;
        lblTax.Text = "฿0.00";
        lblTotal.Text = "฿0.00";

        // กลับไปแสดง Cart หลัง 5 วินาที
        _ = Task.Run(async () =>
        {
            await Task.Delay(5000);
            MainThread.BeginInvokeOnMainThread(() =>
            {
                thankYouSection.IsVisible = false;
                cartSection.IsVisible = true;
                cartItemsCollection.ItemsSource = null;
            });
        });
    }

    /// <summary>
    /// ตั้งชื่อร้าน
    /// </summary>
    public void SetShopName(string shopName)
    {
        MainThread.BeginInvokeOnMainThread(() =>
        {
            lblShopName.Text = shopName;
        });
    }

    protected override void OnDisappearing()
    {
        base.OnDisappearing();

        // หยุด Timer
        _clockTimer?.Stop();

        // ยกเลิก Event subscription
        if (_dualMonitorService != null)
        {
            _dualMonitorService.CartUpdated -= OnCartUpdated;
        }
    }
}

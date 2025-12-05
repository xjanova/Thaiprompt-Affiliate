namespace ThaipromptAffiliateApp.Views;

/// <summary>
/// หน้าเมนูหลัก - Premium Main Menu with Lava Lamp Effect
/// แสดงหลังจาก Splash Screen พร้อม Animation สวยงาม
/// </summary>
public partial class MainMenuPage : ContentPage
{
    private bool _isAnimating = true;
    private readonly Random _random = new();

    public MainMenuPage()
    {
        InitializeComponent();
    }

    protected override void OnAppearing()
    {
        base.OnAppearing();
        _isAnimating = true;

        // เริ่ม Lava Lamp Animation
        StartLavaLampAnimation();

        // เริ่ม Button Entrance Animation
        StartEntranceAnimation();
    }

    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        _isAnimating = false;
    }

    /// <summary>
    /// เริ่ม Animation แบบ Lava Lamp - Blob ลอยขึ้นลง
    /// </summary>
    private async void StartLavaLampAnimation()
    {
        // รัน Animation แยกสำหรับแต่ละ Blob
        _ = AnimateBlob(LavaBlob1, 8000, 0.15, 0.45, 0.1, 0.4);
        _ = AnimateBlob(LavaBlob2, 10000, 0.55, 0.75, 0.4, 0.7);
        _ = AnimateBlob(LavaBlob3, 7000, 0.7, 0.9, 0.1, 0.35);
        _ = AnimateBlob(LavaBlob4, 12000, 0.2, 0.5, 0.6, 0.85);
        _ = AnimateBlob(LavaBlob5, 9000, 0.05, 0.25, 0.4, 0.65);
        _ = AnimateBlob(LavaBlob6, 6000, 0.5, 0.75, 0.75, 0.95);

        // Scale Pulse Animation
        _ = AnimateBlobScale(LavaBlob1, 4000);
        _ = AnimateBlobScale(LavaBlob2, 5000);
        _ = AnimateBlobScale(LavaBlob3, 3500);
        _ = AnimateBlobScale(LavaBlob4, 6000);
        _ = AnimateBlobScale(LavaBlob5, 4500);
        _ = AnimateBlobScale(LavaBlob6, 3000);

        await Task.CompletedTask;
    }

    /// <summary>
    /// Animation สำหรับ Blob แต่ละตัว - เคลื่อนที่แบบ random ภายในขอบเขต
    /// </summary>
    private async Task AnimateBlob(View blob, uint duration,
        double minX, double maxX, double minY, double maxY)
    {
        while (_isAnimating)
        {
            try
            {
                // สุ่มตำแหน่งใหม่
                var targetX = minX + (_random.NextDouble() * (maxX - minX));
                var targetY = minY + (_random.NextDouble() * (maxY - minY));

                // คำนวณ Translation จากตำแหน่งปัจจุบัน
                var parentWidth = LavaLampContainer.Width > 0 ? LavaLampContainer.Width : 400;
                var parentHeight = LavaLampContainer.Height > 0 ? LavaLampContainer.Height : 800;

                var translateX = (targetX - 0.5) * parentWidth * 0.3;
                var translateY = (targetY - 0.5) * parentHeight * 0.3;

                // Animate ไปตำแหน่งใหม่
                await blob.TranslateTo(translateX, translateY, duration, Easing.SinInOut);

                // หน่วงเวลาเล็กน้อยก่อนเคลื่อนที่ต่อ
                await Task.Delay(_random.Next(500, 1500));
            }
            catch
            {
                // ถ้า animation ถูก cancel ก็ออกจาก loop
                break;
            }
        }
    }

    /// <summary>
    /// Animation Scale สำหรับ Blob - หายใจเข้าออก
    /// </summary>
    private async Task AnimateBlobScale(View blob, uint duration)
    {
        while (_isAnimating)
        {
            try
            {
                // Scale ขึ้น
                await blob.ScaleTo(1.15, duration, Easing.SinInOut);
                // Scale ลง
                await blob.ScaleTo(0.9, duration, Easing.SinInOut);
            }
            catch
            {
                break;
            }
        }
    }

    /// <summary>
    /// Animation เข้าหน้าสำหรับปุ่มต่างๆ
    /// </summary>
    private async void StartEntranceAnimation()
    {
        // ซ่อนปุ่มก่อน
        BtnShopping.Opacity = 0;
        BtnShopping.TranslationY = 50;
        BtnDashboard.Opacity = 0;
        BtnDashboard.TranslationY = 50;
        BtnCommission.Opacity = 0;
        BtnCommission.TranslationY = 50;
        BtnReferral.Opacity = 0;
        BtnReferral.TranslationY = 50;

        await Task.Delay(300);

        // Animate ทีละปุ่ม
        _ = AnimateButtonEntrance(BtnShopping, 0);
        _ = AnimateButtonEntrance(BtnDashboard, 100);
        _ = AnimateButtonEntrance(BtnCommission, 200);
        _ = AnimateButtonEntrance(BtnReferral, 300);
    }

    /// <summary>
    /// Animation เข้าสำหรับปุ่มแต่ละปุ่ม
    /// </summary>
    private async Task AnimateButtonEntrance(View button, int delay)
    {
        await Task.Delay(delay);
        await Task.WhenAll(
            button.FadeTo(1, 400, Easing.CubicOut),
            button.TranslateTo(0, 0, 400, Easing.CubicOut)
        );
    }

    /// <summary>
    /// Animation กดปุ่ม - Scale effect
    /// </summary>
    private async Task AnimateButtonPress(View button)
    {
        await button.ScaleTo(0.95, 100, Easing.CubicIn);
        await button.ScaleTo(1.0, 100, Easing.CubicOut);
    }

    // ============================================
    // NAVIGATION HANDLERS
    // ============================================

    /// <summary>
    /// ไปหน้า Shopping (WebView)
    /// </summary>
    private async void OnShoppingTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(BtnShopping);

        // TODO: Navigate to Shopping WebView Page
        // await Shell.Current.GoToAsync("//shopping");

        await DisplayAlert("ช๊อปปิ้ง", "กำลังเปิดหน้าร้านค้า...", "ตกลง");
    }

    /// <summary>
    /// ไปหน้า Dashboard
    /// </summary>
    private async void OnDashboardTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(BtnDashboard);

        // Navigate ไปยัง AppShell แล้วเปิดหน้า Dashboard
        Application.Current!.MainPage = new AppShell();
        await Shell.Current.GoToAsync("//dashboard");
    }

    /// <summary>
    /// ไปหน้า Commission
    /// </summary>
    private async void OnCommissionTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(BtnCommission);

        Application.Current!.MainPage = new AppShell();
        await Shell.Current.GoToAsync("//commissions");
    }

    /// <summary>
    /// ไปหน้า Referral
    /// </summary>
    private async void OnReferralTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(BtnReferral);

        Application.Current!.MainPage = new AppShell();
        await Shell.Current.GoToAsync("//referrals");
    }

    // ============================================
    // BOTTOM NAV HANDLERS
    // ============================================

    /// <summary>
    /// ปุ่ม Home - อยู่หน้านี้แล้ว
    /// </summary>
    private async void OnNavHomeTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(NavHome);
        // อยู่หน้านี้แล้ว - ทำ refresh animation
        await NavHome.RotateTo(360, 300, Easing.CubicOut);
        NavHome.Rotation = 0;
    }

    /// <summary>
    /// ปุ่ม Wallet
    /// </summary>
    private async void OnNavWalletTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(NavWallet);
        await DisplayAlert("กระเป๋าเงิน", "ยอดเงินคงเหลือ: ฿12,500.00", "ตกลง");
    }

    /// <summary>
    /// ปุ่ม Notification
    /// </summary>
    private async void OnNavNotificationTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(NavNotification);
        await DisplayAlert("การแจ้งเตือน", "คุณมี 3 การแจ้งเตือนใหม่", "ตกลง");
    }

    /// <summary>
    /// ปุ่ม Profile
    /// </summary>
    private async void OnNavProfileTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(NavProfile);

        Application.Current!.MainPage = new AppShell();
        await Shell.Current.GoToAsync("//profile");
    }

    /// <summary>
    /// ปุ่ม Settings
    /// </summary>
    private async void OnNavSettingsTapped(object sender, EventArgs e)
    {
        await AnimateButtonPress(NavSettings);
        await DisplayAlert("ตั้งค่า", "หน้าตั้งค่าจะเปิดให้บริการเร็วๆ นี้", "ตกลง");
    }
}

namespace ThaipromptAffiliateApp.Views;

/// <summary>
/// หน้า Shopping - แสดงร้านค้าผ่าน WebView
/// ใช้ Hybrid approach: Native shell + WebView content
/// </summary>
public partial class ShoppingPage : ContentPage
{
    /// <summary>
    /// URL หลักของร้านค้า
    /// </summary>
    private const string ShopHomeUrl = "https://thaiprompt.com/shop";

    /// <summary>
    /// URL ตะกร้าสินค้า
    /// </summary>
    private const string CartUrl = "https://thaiprompt.com/cart";

    /// <summary>
    /// URL ค้นหาสินค้า
    /// </summary>
    private const string SearchUrl = "https://thaiprompt.com/shop/search";

    private bool _canGoBack = false;
    private bool _canGoForward = false;
    private int _cartItemCount = 0;

    public ShoppingPage()
    {
        InitializeComponent();
    }

    protected override void OnAppearing()
    {
        base.OnAppearing();

        // เริ่ม Loading animation
        StartLoadingAnimation();

        // ดึงจำนวนสินค้าในตะกร้า (mock)
        UpdateCartBadge(2);
    }

    /// <summary>
    /// เริ่ม Animation สำหรับ Loading
    /// </summary>
    private async void StartLoadingAnimation()
    {
        LoadingOverlay.IsVisible = true;
        LoadingProgress.Progress = 0;

        // Animate progress bar
        await LoadingProgress.ProgressTo(0.3, 500, Easing.CubicOut);
    }

    /// <summary>
    /// อัพเดท Badge จำนวนสินค้าในตะกร้า
    /// </summary>
    private void UpdateCartBadge(int count)
    {
        _cartItemCount = count;
        CartBadgeCount.Text = count > 99 ? "99+" : count.ToString();
        CartBadge.IsVisible = count > 0;
    }

    /// <summary>
    /// อัพเดทสถานะปุ่ม Back/Forward
    /// </summary>
    private void UpdateNavigationButtons()
    {
        NavWebBack.Opacity = _canGoBack ? 1.0 : 0.5;
        NavWebForward.Opacity = _canGoForward ? 1.0 : 0.5;
    }

    /// <summary>
    /// แสดง URL ปัจจุบันใน Header
    /// </summary>
    private void UpdateUrlDisplay(string url)
    {
        try
        {
            var uri = new Uri(url);
            var displayUrl = uri.Host + uri.AbsolutePath;
            if (displayUrl.Length > 35)
            {
                displayUrl = displayUrl.Substring(0, 32) + "...";
            }
            LblUrl.Text = displayUrl;
        }
        catch
        {
            LblUrl.Text = "กำลังโหลด...";
        }
    }

    // ============================================
    // WEBVIEW EVENT HANDLERS
    // ============================================

    /// <summary>
    /// เมื่อ WebView กำลังโหลดหน้าใหม่
    /// </summary>
    private async void OnWebViewNavigating(object sender, WebNavigatingEventArgs e)
    {
        // แสดง Loading
        LoadingOverlay.IsVisible = true;
        ErrorOverlay.IsVisible = false;
        LoadingText.Text = "กำลังโหลด...";

        // Animate progress
        await LoadingProgress.ProgressTo(0.6, 300, Easing.CubicOut);

        // อัพเดท URL display
        UpdateUrlDisplay(e.Url);
    }

    /// <summary>
    /// เมื่อ WebView โหลดหน้าเสร็จ
    /// </summary>
    private async void OnWebViewNavigated(object sender, WebNavigatedEventArgs e)
    {
        // อัพเดท progress
        await LoadingProgress.ProgressTo(1.0, 200, Easing.CubicOut);

        // ซ่อน Loading
        await Task.Delay(200);
        LoadingOverlay.IsVisible = false;

        // ตรวจสอบ error
        if (e.Result != WebNavigationResult.Success)
        {
            ErrorOverlay.IsVisible = true;
            ErrorMessage.Text = GetErrorMessage(e.Result);
            return;
        }

        // อัพเดท URL display
        UpdateUrlDisplay(e.Url);

        // อัพเดทสถานะ navigation
        _canGoBack = ShopWebView.CanGoBack;
        _canGoForward = ShopWebView.CanGoForward;
        UpdateNavigationButtons();

        // ตรวจสอบ URL สำหรับ cart count (ในอนาคตอาจใช้ JavaScript injection)
        CheckCartFromUrl(e.Url);
    }

    /// <summary>
    /// แปลง error code เป็นข้อความภาษาไทย
    /// </summary>
    private string GetErrorMessage(WebNavigationResult result)
    {
        return result switch
        {
            WebNavigationResult.Timeout => "หมดเวลาการเชื่อมต่อ กรุณาลองใหม่",
            WebNavigationResult.Cancel => "การโหลดถูกยกเลิก",
            WebNavigationResult.Failure => "ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้",
            _ => "เกิดข้อผิดพลาด กรุณาลองใหม่"
        };
    }

    /// <summary>
    /// ตรวจสอบจำนวนสินค้าจาก URL (mock)
    /// ในอนาคตจะใช้ JavaScript injection
    /// </summary>
    private void CheckCartFromUrl(string url)
    {
        // TODO: ใช้ JavaScript injection เพื่อดึงจำนวนสินค้าจริง
        // await ShopWebView.EvaluateJavaScriptAsync("getCartCount()");
    }

    // ============================================
    // HEADER BUTTON HANDLERS
    // ============================================

    /// <summary>
    /// กลับไปหน้า MainMenu
    /// </summary>
    private async void OnBackTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnBack);

        // กลับไปหน้า MainMenu
        Application.Current!.MainPage = new MainMenuPage();
    }

    /// <summary>
    /// ไปหน้าตะกร้าสินค้า
    /// </summary>
    private async void OnCartTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnCart);

        // Navigate ไปหน้า Cart
        ShopWebView.Source = CartUrl;
    }

    // ============================================
    // BOTTOM NAV HANDLERS
    // ============================================

    /// <summary>
    /// ย้อนกลับใน WebView
    /// </summary>
    private async void OnWebBackTapped(object sender, EventArgs e)
    {
        if (!_canGoBack) return;

        await AnimateButton(NavWebBack);

        if (ShopWebView.CanGoBack)
        {
            ShopWebView.GoBack();
        }
    }

    /// <summary>
    /// ไปหน้าถัดไปใน WebView
    /// </summary>
    private async void OnWebForwardTapped(object sender, EventArgs e)
    {
        if (!_canGoForward) return;

        await AnimateButton(NavWebForward);

        if (ShopWebView.CanGoForward)
        {
            ShopWebView.GoForward();
        }
    }

    /// <summary>
    /// รีเฟรช WebView
    /// </summary>
    private async void OnRefreshTapped(object sender, EventArgs e)
    {
        await AnimateButton(NavRefresh);

        // หมุน icon
        await NavRefresh.RotateTo(360, 500, Easing.CubicInOut);
        NavRefresh.Rotation = 0;

        ShopWebView.Reload();
    }

    /// <summary>
    /// กลับไปหน้าแรกของ Shop
    /// </summary>
    private async void OnShopHomeTapped(object sender, EventArgs e)
    {
        await AnimateButton(NavHome);

        ShopWebView.Source = ShopHomeUrl;
    }

    /// <summary>
    /// ไปหน้าค้นหาสินค้า
    /// </summary>
    private async void OnSearchTapped(object sender, EventArgs e)
    {
        await AnimateButton(NavSearch);

        // เปิด search modal หรือไปหน้า search
        var searchQuery = await DisplayPromptAsync(
            "ค้นหาสินค้า",
            "พิมพ์ชื่อสินค้าที่ต้องการค้นหา",
            "ค้นหา",
            "ยกเลิก",
            "เช่น: AI Bot, Course, Template...",
            50,
            Keyboard.Text);

        if (!string.IsNullOrWhiteSpace(searchQuery))
        {
            var encodedQuery = Uri.EscapeDataString(searchQuery);
            ShopWebView.Source = $"{SearchUrl}?q={encodedQuery}";
        }
    }

    /// <summary>
    /// แชร์หน้าปัจจุบัน
    /// </summary>
    private async void OnShareTapped(object sender, EventArgs e)
    {
        await AnimateButton(NavShare);

        try
        {
            var currentUrl = ShopWebView.Source?.ToString() ?? ShopHomeUrl;

            await Share.Default.RequestAsync(new ShareTextRequest
            {
                Title = "แชร์สินค้าจาก Thaiprompt",
                Text = $"มาดูสินค้าน่าสนใจจาก Thaiprompt กัน!\n{currentUrl}",
                Uri = currentUrl
            });
        }
        catch (Exception ex)
        {
            await DisplayAlert("ผิดพลาด", $"ไม่สามารถแชร์ได้: {ex.Message}", "ตกลง");
        }
    }

    /// <summary>
    /// ลองโหลดใหม่เมื่อเกิด error
    /// </summary>
    private void OnRetryTapped(object sender, EventArgs e)
    {
        ErrorOverlay.IsVisible = false;
        LoadingOverlay.IsVisible = true;
        ShopWebView.Reload();
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /// <summary>
    /// Animation กดปุ่ม
    /// </summary>
    private async Task AnimateButton(View button)
    {
        await button.ScaleTo(0.9, 80, Easing.CubicIn);
        await button.ScaleTo(1.0, 80, Easing.CubicOut);
    }

    /// <summary>
    /// Inject JavaScript เพื่อดึงข้อมูลจาก WebView
    /// </summary>
    private async Task<string> InjectJavaScript(string script)
    {
        try
        {
            var result = await ShopWebView.EvaluateJavaScriptAsync(script);
            return result ?? string.Empty;
        }
        catch
        {
            return string.Empty;
        }
    }

    /// <summary>
    /// ดึงจำนวนสินค้าในตะกร้าผ่าน JavaScript
    /// </summary>
    public async Task RefreshCartCount()
    {
        var cartCountScript = @"
            (function() {
                var cartBadge = document.querySelector('.cart-count, .cart-badge, [data-cart-count]');
                if (cartBadge) {
                    return cartBadge.innerText || cartBadge.getAttribute('data-cart-count') || '0';
                }
                return '0';
            })();
        ";

        var result = await InjectJavaScript(cartCountScript);
        if (int.TryParse(result, out int count))
        {
            UpdateCartBadge(count);
        }
    }
}

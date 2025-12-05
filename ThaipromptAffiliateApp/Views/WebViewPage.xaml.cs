namespace ThaipromptAffiliateApp.Views;

/// <summary>
/// หน้า WebView ที่ใช้ซ้ำได้ - สำหรับ Wiki, Help, และหน้าอื่นๆ
/// ใช้ Factory method เพื่อสร้างหน้าสำหรับแต่ละ use case
/// </summary>
public partial class WebViewPage : ContentPage
{
    // ============================================
    // PREDEFINED PAGE TYPES
    // ============================================

    /// <summary>
    /// ประเภทหน้าที่กำหนดไว้ล่วงหน้า
    /// </summary>
    public enum PageType
    {
        Wiki,
        Help,
        Terms,
        Privacy,
        About,
        Contact,
        Custom
    }

    /// <summary>
    /// ข้อมูลการตั้งค่าสำหรับแต่ละประเภทหน้า
    /// </summary>
    private static readonly Dictionary<PageType, (string Title, string Icon, string Url)> PageConfigs = new()
    {
        { PageType.Wiki, ("Wiki", "📚", "https://thaiprompt.com/wiki") },
        { PageType.Help, ("ความช่วยเหลือ", "❓", "https://thaiprompt.com/help") },
        { PageType.Terms, ("ข้อกำหนดการใช้งาน", "📜", "https://thaiprompt.com/terms") },
        { PageType.Privacy, ("นโยบายความเป็นส่วนตัว", "🔒", "https://thaiprompt.com/privacy") },
        { PageType.About, ("เกี่ยวกับเรา", "ℹ️", "https://thaiprompt.com/about") },
        { PageType.Contact, ("ติดต่อเรา", "📧", "https://thaiprompt.com/contact") },
    };

    // ============================================
    // PROPERTIES
    // ============================================

    private string _pageTitle = "WebView";
    private string _pageIcon = "🌐";
    private string _homeUrl = "https://thaiprompt.com";
    private bool _canGoBack = false;
    private bool _canGoForward = false;

    // ============================================
    // FACTORY METHODS
    // ============================================

    /// <summary>
    /// สร้างหน้า Wiki
    /// </summary>
    public static WebViewPage CreateWikiPage()
    {
        return CreatePage(PageType.Wiki);
    }

    /// <summary>
    /// สร้างหน้า Help
    /// </summary>
    public static WebViewPage CreateHelpPage()
    {
        return CreatePage(PageType.Help);
    }

    /// <summary>
    /// สร้างหน้า Terms
    /// </summary>
    public static WebViewPage CreateTermsPage()
    {
        return CreatePage(PageType.Terms);
    }

    /// <summary>
    /// สร้างหน้า Privacy
    /// </summary>
    public static WebViewPage CreatePrivacyPage()
    {
        return CreatePage(PageType.Privacy);
    }

    /// <summary>
    /// สร้างหน้า About
    /// </summary>
    public static WebViewPage CreateAboutPage()
    {
        return CreatePage(PageType.About);
    }

    /// <summary>
    /// สร้างหน้า Contact
    /// </summary>
    public static WebViewPage CreateContactPage()
    {
        return CreatePage(PageType.Contact);
    }

    /// <summary>
    /// สร้างหน้าจาก PageType
    /// </summary>
    public static WebViewPage CreatePage(PageType type)
    {
        if (PageConfigs.TryGetValue(type, out var config))
        {
            return new WebViewPage(config.Title, config.Icon, config.Url);
        }
        return new WebViewPage();
    }

    /// <summary>
    /// สร้างหน้า Custom พร้อม URL
    /// </summary>
    public static WebViewPage CreateCustomPage(string title, string icon, string url)
    {
        return new WebViewPage(title, icon, url);
    }

    // ============================================
    // CONSTRUCTORS
    // ============================================

    /// <summary>
    /// Default constructor
    /// </summary>
    public WebViewPage()
    {
        InitializeComponent();
    }

    /// <summary>
    /// Constructor พร้อมพารามิเตอร์
    /// </summary>
    public WebViewPage(string title, string icon, string url)
    {
        InitializeComponent();

        _pageTitle = title;
        _pageIcon = icon;
        _homeUrl = url;

        // ตั้งค่า UI
        LblTitle.Text = title;
        LblIcon.Text = icon;
        ContentWebView.Source = url;
    }

    // ============================================
    // LIFECYCLE
    // ============================================

    protected override void OnAppearing()
    {
        base.OnAppearing();

        // ถ้ายังไม่ได้ตั้งค่า URL ให้โหลดค่า default
        if (ContentWebView.Source == null)
        {
            ContentWebView.Source = _homeUrl;
        }
    }

    // ============================================
    // WEBVIEW EVENT HANDLERS
    // ============================================

    /// <summary>
    /// เมื่อ WebView กำลังโหลดหน้าใหม่
    /// </summary>
    private void OnWebViewNavigating(object sender, WebNavigatingEventArgs e)
    {
        LoadingOverlay.IsVisible = true;
        ErrorOverlay.IsVisible = false;
        LoadingText.Text = "กำลังโหลด...";
        UpdateUrlDisplay(e.Url);
    }

    /// <summary>
    /// เมื่อ WebView โหลดหน้าเสร็จ
    /// </summary>
    private void OnWebViewNavigated(object sender, WebNavigatedEventArgs e)
    {
        LoadingOverlay.IsVisible = false;

        if (e.Result != WebNavigationResult.Success)
        {
            ErrorOverlay.IsVisible = true;
            ErrorMessage.Text = GetErrorMessage(e.Result);
            return;
        }

        UpdateUrlDisplay(e.Url);

        // อัพเดทสถานะ navigation
        _canGoBack = ContentWebView.CanGoBack;
        _canGoForward = ContentWebView.CanGoForward;
        UpdateNavigationButtons();
    }

    /// <summary>
    /// แสดง URL ใน Header
    /// </summary>
    private void UpdateUrlDisplay(string url)
    {
        try
        {
            var uri = new Uri(url);
            var displayUrl = uri.Host + uri.AbsolutePath;
            if (displayUrl.Length > 30)
            {
                displayUrl = displayUrl.Substring(0, 27) + "...";
            }
            LblUrl.Text = displayUrl;
        }
        catch
        {
            LblUrl.Text = "กำลังโหลด...";
        }
    }

    /// <summary>
    /// อัพเดทสถานะปุ่ม Navigation
    /// </summary>
    private void UpdateNavigationButtons()
    {
        FabBack.Opacity = _canGoBack ? 1.0 : 0.5;
        FabForward.Opacity = _canGoForward ? 1.0 : 0.5;
    }

    /// <summary>
    /// แปลง error เป็นข้อความภาษาไทย
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

    // ============================================
    // HEADER BUTTON HANDLERS
    // ============================================

    /// <summary>
    /// กลับไปหน้า MainMenu
    /// </summary>
    private async void OnBackTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnBack);
        Application.Current!.MainPage = new MainMenuPage();
    }

    /// <summary>
    /// ปุ่ม Action (Share)
    /// </summary>
    private async void OnActionTapped(object sender, EventArgs e)
    {
        await AnimateButton(BtnAction);

        try
        {
            var currentUrl = ContentWebView.Source?.ToString() ?? _homeUrl;

            await Share.Default.RequestAsync(new ShareTextRequest
            {
                Title = $"แชร์จาก {_pageTitle}",
                Text = $"ดูข้อมูลจาก {_pageTitle}!\n{currentUrl}",
                Uri = currentUrl
            });
        }
        catch (Exception ex)
        {
            await DisplayAlert("ผิดพลาด", $"ไม่สามารถแชร์ได้: {ex.Message}", "ตกลง");
        }
    }

    // ============================================
    // FLOATING ACTION BUTTON HANDLERS
    // ============================================

    /// <summary>
    /// ย้อนกลับใน WebView
    /// </summary>
    private async void OnWebBackTapped(object sender, EventArgs e)
    {
        if (!_canGoBack) return;

        await AnimateButton(FabBack);

        if (ContentWebView.CanGoBack)
        {
            ContentWebView.GoBack();
        }
    }

    /// <summary>
    /// ไปหน้าถัดไปใน WebView
    /// </summary>
    private async void OnWebForwardTapped(object sender, EventArgs e)
    {
        if (!_canGoForward) return;

        await AnimateButton(FabForward);

        if (ContentWebView.CanGoForward)
        {
            ContentWebView.GoForward();
        }
    }

    /// <summary>
    /// รีเฟรช
    /// </summary>
    private async void OnRefreshTapped(object sender, EventArgs e)
    {
        await AnimateButton(FabRefresh);

        // หมุน icon
        await FabRefresh.RotateTo(360, 500, Easing.CubicInOut);
        FabRefresh.Rotation = 0;

        ContentWebView.Reload();
    }

    /// <summary>
    /// กลับ Home URL
    /// </summary>
    private async void OnHomeTapped(object sender, EventArgs e)
    {
        await AnimateButton(FabHome);
        ContentWebView.Source = _homeUrl;
    }

    /// <summary>
    /// ลองโหลดใหม่
    /// </summary>
    private void OnRetryTapped(object sender, EventArgs e)
    {
        ErrorOverlay.IsVisible = false;
        LoadingOverlay.IsVisible = true;
        ContentWebView.Reload();
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
}

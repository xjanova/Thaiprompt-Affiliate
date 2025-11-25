using ThaipromptAffiliate.ViewModels;

namespace ThaipromptAffiliate.Views;

/// <summary>
/// หน้า Splash Screen - หน้าเปิดแอพพร้อม Animation
///
/// ฟังก์ชันหลัก:
/// - แสดง Logo พร้อม Animation
/// - โหลด Theme และตรวจสอบ Authentication
/// - นำทางไปหน้าถัดไป (Hub หรือ Dashboard)
/// </summary>
public partial class SplashPage : ContentPage
{
    private readonly SplashViewModel _viewModel;
    private bool _animationsStarted = false;

    /// <summary>
    /// Constructor - รับ ViewModel ผ่าน DI
    /// </summary>
    public SplashPage(SplashViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    /// <summary>
    /// เรียกเมื่อหน้าปรากฏ - เริ่ม animations
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // ป้องกันการเรียก animation ซ้ำ
        if (_animationsStarted)
            return;

        _animationsStarted = true;

        // เริ่ม animations แบบ sequential
        await StartEntranceAnimationsAsync();

        // เริ่ม loading dots animation
        _ = StartLoadingDotsAnimationAsync();

        // โหลดข้อมูลและนำทาง
        await _viewModel.InitializeAsync();
    }

    /// <summary>
    /// Animation ตอนเข้าหน้า - แสดงองค์ประกอบทีละส่วน
    /// </summary>
    private async Task StartEntranceAnimationsAsync()
    {
        // รีเซ็ต state
        LogoFrame.Opacity = 0;
        LogoFrame.Scale = 0.5;
        TitleSection.Opacity = 0;
        TitleSection.TranslationY = 30;
        LoadingSection.Opacity = 0;
        FooterSection.Opacity = 0;
        DividerLine.WidthRequest = 0;

        // 1. แสดง Logo พร้อม scale up และ fade in
        await Task.WhenAll(
            LogoFrame.FadeTo(1, 600, Easing.CubicOut),
            LogoFrame.ScaleTo(1, 800, Easing.SpringOut)
        );

        // เริ่ม glow ring animation
        _ = StartGlowRingAnimationAsync();

        // 2. แสดง Title Section
        await Task.WhenAll(
            TitleSection.FadeTo(1, 500, Easing.CubicOut),
            TitleSection.TranslateTo(0, 0, 500, Easing.CubicOut)
        );

        // 3. Animate divider line
        await DividerLine.WidthTo(120, 400, Easing.CubicOut);

        // 4. แสดง Loading Section
        await LoadingSection.FadeTo(1, 400, Easing.CubicOut);

        // 5. แสดง Footer
        await FooterSection.FadeTo(1, 400, Easing.CubicOut);
    }

    /// <summary>
    /// Animation สำหรับ Loading Dots - วนลูป
    /// </summary>
    private async Task StartLoadingDotsAnimationAsync()
    {
        var dots = new[] { LoadingDot1, LoadingDot2, LoadingDot3 };
        var colors = new[]
        {
            Color.FromArgb("#6366F1"), // Primary
            Color.FromArgb("#8B5CF6"), // Secondary
            Color.FromArgb("#06B6D4")  // Accent
        };

        while (true)
        {
            for (int i = 0; i < dots.Length; i++)
            {
                // Animate current dot
                var dot = dots[i];

                // Scale up and brighten
                await Task.WhenAll(
                    dot.ScaleTo(1.5, 200, Easing.CubicOut),
                    dot.FadeTo(1, 200, Easing.CubicOut)
                );

                // Scale down and dim
                await Task.WhenAll(
                    dot.ScaleTo(1, 200, Easing.CubicIn),
                    dot.FadeTo(0.3, 200, Easing.CubicIn)
                );

                // รอก่อนไป dot ถัดไป
                await Task.Delay(50);
            }
        }
    }

    /// <summary>
    /// Animation สำหรับ Glow Ring - pulse effect
    /// </summary>
    private async Task StartGlowRingAnimationAsync()
    {
        while (true)
        {
            // Pulse out
            await Task.WhenAll(
                GlowRing1.ScaleTo(1.2, 1500, Easing.SinInOut),
                GlowRing1.FadeTo(0.1, 1500, Easing.SinInOut),
                GlowRing2.ScaleTo(1.15, 1500, Easing.SinInOut),
                GlowRing2.FadeTo(0.2, 1500, Easing.SinInOut)
            );

            // Pulse in
            await Task.WhenAll(
                GlowRing1.ScaleTo(1, 1500, Easing.SinInOut),
                GlowRing1.FadeTo(0.3, 1500, Easing.SinInOut),
                GlowRing2.ScaleTo(1, 1500, Easing.SinInOut),
                GlowRing2.FadeTo(0.5, 1500, Easing.SinInOut)
            );
        }
    }

    /// <summary>
    /// เรียกเมื่อหน้าหายไป - หยุด animations
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        // Animations จะหยุดเองเมื่อหน้าถูก dispose
    }
}

/// <summary>
/// Extension method สำหรับ animate width
/// </summary>
public static class ViewExtensions
{
    /// <summary>
    /// Animate WidthRequest ของ View
    /// </summary>
    public static Task<bool> WidthTo(this VisualElement view, double width, uint length = 250, Easing? easing = null)
    {
        var tcs = new TaskCompletionSource<bool>();

        var animation = new Animation(
            v => view.WidthRequest = v,
            view.WidthRequest,
            width,
            easing ?? Easing.Linear
        );

        animation.Commit(
            view,
            "WidthAnimation",
            16,
            length,
            finished: (v, c) => tcs.SetResult(c)
        );

        return tcs.Task;
    }
}

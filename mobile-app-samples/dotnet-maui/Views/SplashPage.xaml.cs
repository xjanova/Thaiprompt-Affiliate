using ThaipromptAffiliate.ViewModels;

namespace ThaipromptAffiliate.Views;

/// <summary>
/// หน้า Splash Screen V2 - World-Class Animated Experience
///
/// ฟีเจอร์ Animation:
/// - Particle System ลอยตัว
/// - Rotating Rings รอบ Logo
/// - Glow Pulse Effect
/// - Progress Bar Animation
/// - Scan Line Effect (Cyberpunk)
/// - Aurora Background Animation
/// </summary>
public partial class SplashPage : ContentPage
{
    private readonly SplashViewModel _viewModel;
    private bool _animationsStarted = false;
    private CancellationTokenSource? _animationCts;

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
    /// เรียกเมื่อหน้าปรากฏ - เริ่ม animations ทั้งหมด
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        if (_animationsStarted)
            return;

        _animationsStarted = true;
        _animationCts = new CancellationTokenSource();

        // เริ่ม background animations (ทำงานตลอด)
        _ = StartBackgroundAnimationsAsync(_animationCts.Token);

        // เริ่ม entrance animations (ทำครั้งเดียว)
        await StartEntranceAnimationsAsync();

        // เริ่ม loading animations
        _ = StartLoadingAnimationsAsync(_animationCts.Token);

        // โหลดข้อมูลและนำทาง
        await _viewModel.InitializeAsync();
    }

    /// <summary>
    /// เรียกเมื่อหน้าหายไป - หยุด animations
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();
        _animationCts?.Cancel();
    }

    /// <summary>
    /// Background Animations - ทำงานตลอดเวลา
    /// </summary>
    private async Task StartBackgroundAnimationsAsync(CancellationToken ct)
    {
        // เริ่ม animations พร้อมกัน
        var tasks = new List<Task>
        {
            AnimateParticlesAsync(ct),
            AnimateAurorasAsync(ct),
            AnimateScanLineAsync(ct),
            AnimateRotatingRingsAsync(ct),
            AnimateGlowPulseAsync(ct),
            AnimateOrbitDotsAsync(ct)
        };

        try
        {
            await Task.WhenAll(tasks);
        }
        catch (OperationCanceledException)
        {
            // Animation cancelled, this is expected
        }
    }

    /// <summary>
    /// Entrance Animations - แสดงองค์ประกอบทีละส่วน
    /// </summary>
    private async Task StartEntranceAnimationsAsync()
    {
        // รีเซ็ต state
        LogoContainer.Opacity = 0;
        LogoContainer.Scale = 0.5;
        TitleSection.Opacity = 0;
        TitleSection.TranslationY = 30;
        LoadingSection.Opacity = 0;
        FooterSection.Opacity = 0;
        FooterSection.TranslationY = 30;

        // 1. แสดง Logo พร้อม bounce effect
        await Task.WhenAll(
            LogoContainer.FadeTo(1, 800, Easing.CubicOut),
            LogoContainer.ScaleTo(1.1, 600, Easing.CubicOut)
        );
        await LogoContainer.ScaleTo(1, 200, Easing.CubicIn);

        // 2. แสดง Title
        await Task.WhenAll(
            TitleSection.FadeTo(1, 600, Easing.CubicOut),
            TitleSection.TranslateTo(0, 0, 600, Easing.CubicOut)
        );

        // 3. Animate divider
        await DividerLine.WidthTo(200, 500, Easing.CubicOut);

        // 4. แสดง Loading Section
        await LoadingSection.FadeTo(1, 400, Easing.CubicOut);

        // 5. แสดง Footer
        await Task.WhenAll(
            FooterSection.FadeTo(1, 400, Easing.CubicOut),
            FooterSection.TranslateTo(0, 0, 400, Easing.CubicOut)
        );
    }

    /// <summary>
    /// Loading Animations - Progress bar และ dots
    /// </summary>
    private async Task StartLoadingAnimationsAsync(CancellationToken ct)
    {
        var dots = new[] { LoadingDot1, LoadingDot2, LoadingDot3 };

        // Animate progress bar
        _ = AnimateProgressBarAsync(ct);

        // Animate loading dots
        while (!ct.IsCancellationRequested)
        {
            for (int i = 0; i < dots.Length && !ct.IsCancellationRequested; i++)
            {
                var dot = dots[i];

                await Task.WhenAll(
                    dot.ScaleTo(1.4, 200, Easing.CubicOut),
                    dot.FadeTo(1, 200, Easing.CubicOut)
                );

                await Task.WhenAll(
                    dot.ScaleTo(1, 200, Easing.CubicIn),
                    dot.FadeTo(0.3, 200, Easing.CubicIn)
                );

                await Task.Delay(50, ct);
            }
        }
    }

    /// <summary>
    /// Progress Bar Animation
    /// </summary>
    private async Task AnimateProgressBarAsync(CancellationToken ct)
    {
        while (!ct.IsCancellationRequested)
        {
            // Reset
            LoadingBar.WidthRequest = 0;
            LoadingShine.TranslationX = 0;

            // Animate progress
            await Task.WhenAll(
                LoadingBar.WidthTo(180, 2000, Easing.CubicInOut),
                AnimateShineAsync(ct)
            );

            // รอก่อนเริ่มใหม่
            await Task.Delay(500, ct);
        }
    }

    /// <summary>
    /// Shine Effect บน Progress Bar
    /// </summary>
    private async Task AnimateShineAsync(CancellationToken ct)
    {
        for (int i = 0; i < 3 && !ct.IsCancellationRequested; i++)
        {
            LoadingShine.TranslationX = -30;
            await LoadingShine.TranslateTo(180, 0, 600, Easing.Linear);
            await Task.Delay(100, ct);
        }
    }

    /// <summary>
    /// Particle System Animation - อนุภาคลอยตัว
    /// </summary>
    private async Task AnimateParticlesAsync(CancellationToken ct)
    {
        var particles = new[] { Particle1, Particle2, Particle3, Particle4,
                                Particle5, Particle6, Particle7, Particle8 };
        var random = new Random();

        while (!ct.IsCancellationRequested)
        {
            var tasks = particles.Select(async (p, index) =>
            {
                var delay = random.Next(0, 500);
                await Task.Delay(delay, ct);

                var duration = (uint)(2000 + random.Next(1000));
                var yOffset = random.Next(-30, 30);
                var xOffset = random.Next(-20, 20);

                // Float up
                await Task.WhenAll(
                    p.TranslateTo(p.TranslationX + xOffset, p.TranslationY + yOffset - 40, duration, Easing.SinInOut),
                    p.FadeTo(0.8, duration / 2, Easing.SinIn).ContinueWith(async _ =>
                        await p.FadeTo(0.3, duration / 2, Easing.SinOut), ct)
                );

                // Float back
                await p.TranslateTo(p.TranslationX - xOffset, p.TranslationY - yOffset + 40, duration, Easing.SinInOut);
            });

            try
            {
                await Task.WhenAll(tasks);
            }
            catch { }
        }
    }

    /// <summary>
    /// Aurora Background Animation
    /// </summary>
    private async Task AnimateAurorasAsync(CancellationToken ct)
    {
        while (!ct.IsCancellationRequested)
        {
            // Pulse auroras
            await Task.WhenAll(
                Aurora1.FadeTo(0.12, 3000, Easing.SinInOut),
                Aurora2.FadeTo(0.1, 3500, Easing.SinInOut),
                Aurora3.FadeTo(0.08, 4000, Easing.SinInOut),
                Aurora1.ScaleTo(1.1, 3000, Easing.SinInOut),
                Aurora2.ScaleTo(1.15, 3500, Easing.SinInOut)
            );

            await Task.WhenAll(
                Aurora1.FadeTo(0.06, 3000, Easing.SinInOut),
                Aurora2.FadeTo(0.04, 3500, Easing.SinInOut),
                Aurora3.FadeTo(0.03, 4000, Easing.SinInOut),
                Aurora1.ScaleTo(1, 3000, Easing.SinInOut),
                Aurora2.ScaleTo(1, 3500, Easing.SinInOut)
            );
        }
    }

    /// <summary>
    /// Scan Line Animation - Cyberpunk Effect
    /// </summary>
    private async Task AnimateScanLineAsync(CancellationToken ct)
    {
        var screenHeight = DeviceDisplay.MainDisplayInfo.Height / DeviceDisplay.MainDisplayInfo.Density;

        while (!ct.IsCancellationRequested)
        {
            // รอ random delay
            await Task.Delay(3000 + new Random().Next(2000), ct);

            // Show และ animate scan line
            ScanLine.TranslationY = 0;
            ScanLine.Opacity = 0.15;

            await ScanLine.TranslateTo(0, screenHeight, 1500, Easing.Linear);

            ScanLine.Opacity = 0;
        }
    }

    /// <summary>
    /// Rotating Rings Animation
    /// </summary>
    private async Task AnimateRotatingRingsAsync(CancellationToken ct)
    {
        while (!ct.IsCancellationRequested)
        {
            await Task.WhenAll(
                OuterRing1.RelRotateTo(360, 15000, Easing.Linear),
                OuterRing2.RelRotateTo(-360, 12000, Easing.Linear),
                InnerRing.RelRotateTo(360, 10000, Easing.Linear)
            );
        }
    }

    /// <summary>
    /// Glow Pulse Animation
    /// </summary>
    private async Task AnimateGlowPulseAsync(CancellationToken ct)
    {
        while (!ct.IsCancellationRequested)
        {
            await Task.WhenAll(
                GlowPulse.ScaleTo(1.3, 1500, Easing.SinInOut),
                GlowPulse.FadeTo(0.2, 1500, Easing.SinInOut)
            );

            await Task.WhenAll(
                GlowPulse.ScaleTo(1, 1500, Easing.SinInOut),
                GlowPulse.FadeTo(0.5, 1500, Easing.SinInOut)
            );
        }
    }

    /// <summary>
    /// Orbit Dots Animation - จุดโคจรรอบ logo
    /// </summary>
    private async Task AnimateOrbitDotsAsync(CancellationToken ct)
    {
        while (!ct.IsCancellationRequested)
        {
            // Pulse orbit dots
            await Task.WhenAll(
                OrbitDot1.FadeTo(1, 500, Easing.CubicOut),
                OrbitDot2.FadeTo(1, 600, Easing.CubicOut),
                OrbitDot3.FadeTo(1, 700, Easing.CubicOut)
            );

            await Task.Delay(300, ct);

            await Task.WhenAll(
                OrbitDot1.FadeTo(0.4, 500, Easing.CubicIn),
                OrbitDot2.FadeTo(0.3, 600, Easing.CubicIn),
                OrbitDot3.FadeTo(0.2, 700, Easing.CubicIn)
            );

            await Task.Delay(500, ct);
        }
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
        var startWidth = view.WidthRequest > 0 ? view.WidthRequest : 0;

        var animation = new Animation(
            v => view.WidthRequest = v,
            startWidth,
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

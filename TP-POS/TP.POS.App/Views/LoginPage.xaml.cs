using System.ComponentModel;
using TP.POS.App.ViewModels;

namespace TP.POS.App.Views;

/// <summary>
/// หน้าเข้าสู่ระบบ - Premium Dark Theme พร้อม Firefly Animation
/// </summary>
public partial class LoginPage : ContentPage
{
    private readonly LoginViewModel _viewModel;
    private readonly Random _random = new();
    private CancellationTokenSource? _fireflyAnimationCts;
    private CancellationTokenSource? _pulseAnimationCts;

    public LoginPage(LoginViewModel viewModel)
    {
        InitializeComponent();
        BindingContext = _viewModel = viewModel;

        // Subscribe event เมื่อ Login สำเร็จ
        _viewModel.LoginSuccessful += OnLoginSuccessful;

        // Subscribe event เมื่อต้องการไปหน้า Setup
        _viewModel.NavigateToSetup += OnNavigateToSetup;

        // Subscribe PropertyChanged เพื่ออัพเดท Glow Color
        _viewModel.PropertyChanged += OnViewModelPropertyChanged;
    }

    /// <summary>
    /// เมื่อหน้าแสดงผล
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // เริ่ม Firefly Animation
        StartFireflyAnimation();

        // เริ่ม Pulse Animation สำหรับ Connection Indicator
        StartPulseAnimation();

        await _viewModel.InitializeAsync();
    }

    /// <summary>
    /// เริ่ม Animation หิ่งห้อยลอยกระพริบ
    /// </summary>
    private void StartFireflyAnimation()
    {
        _fireflyAnimationCts?.Cancel();
        _fireflyAnimationCts = new CancellationTokenSource();
        var token = _fireflyAnimationCts.Token;

        // สร้าง Animation สำหรับแต่ละ Firefly
        var fireflies = new[]
        {
            Firefly1, Firefly2, Firefly3, Firefly4, Firefly5,
            Firefly6, Firefly7, Firefly8, Firefly9, Firefly10
        };

        foreach (var firefly in fireflies)
        {
            AnimateFirefly(firefly, token);
        }
    }

    /// <summary>
    /// Animation สำหรับหิ่งห้อยแต่ละตัว
    /// </summary>
    private async void AnimateFirefly(Ellipse firefly, CancellationToken token)
    {
        // Random delay ก่อนเริ่ม
        await Task.Delay(_random.Next(0, 2000), token);

        while (!token.IsCancellationRequested)
        {
            try
            {
                // กำหนดระยะเวลา animation แบบสุ่ม
                var fadeInDuration = _random.Next(800, 1500);
                var stayDuration = _random.Next(1000, 3000);
                var fadeOutDuration = _random.Next(800, 1500);
                var moveDuration = fadeInDuration + stayDuration + fadeOutDuration;

                // สุ่มตำแหน่งใหม่
                var newX = _random.NextDouble();
                var newY = _random.NextDouble();

                // Animation: Fade In + Move
                var fadeInTask = firefly.FadeTo(0.8, (uint)fadeInDuration, Easing.SinIn);
                var moveTask = AnimateMoveFirefly(firefly, newX, newY, (uint)moveDuration, token);

                await fadeInTask;

                if (token.IsCancellationRequested) break;

                // Glow Effect - Pulse หลังจาก fade in
                await Task.Delay(stayDuration / 2, token);
                await firefly.ScaleTo(1.3, 200, Easing.SinOut);
                await firefly.ScaleTo(1.0, 200, Easing.SinIn);

                // รอให้ glow อีกนิด
                await Task.Delay(stayDuration / 2, token);

                if (token.IsCancellationRequested) break;

                // Animation: Fade Out
                await firefly.FadeTo(0, (uint)fadeOutDuration, Easing.SinOut);

                if (token.IsCancellationRequested) break;

                // รอก่อนจะ glow อีกครั้ง
                await Task.Delay(_random.Next(500, 2500), token);
            }
            catch (OperationCanceledException)
            {
                break;
            }
            catch (Exception)
            {
                // Ignore animation errors
            }
        }
    }

    /// <summary>
    /// Animation เคลื่อนย้ายหิ่งห้อย
    /// </summary>
    private async Task AnimateMoveFirefly(Ellipse firefly, double targetX, double targetY, uint duration, CancellationToken token)
    {
        try
        {
            // ใช้ Animation ของ MAUI เพื่อเคลื่อนย้ายใน AbsoluteLayout
            var animation = new Animation();

            // สุ่มทิศทางการเคลื่อนที่
            var deltaX = (_random.NextDouble() - 0.5) * 0.1; // เคลื่อนที่ประมาณ 10% ของหน้าจอ
            var deltaY = (_random.NextDouble() - 0.5) * 0.1;

            // ใช้ TranslateTo เพื่อให้ดูเหมือนลอย
            await firefly.TranslateTo(
                deltaX * Width,
                deltaY * Height,
                duration,
                Easing.SinInOut);

            // Reset translation
            await firefly.TranslateTo(0, 0, 100);
        }
        catch (Exception)
        {
            // Ignore
        }
    }

    /// <summary>
    /// Animation Pulse สำหรับ Connection Indicator
    /// </summary>
    private async void StartPulseAnimation()
    {
        _pulseAnimationCts?.Cancel();
        _pulseAnimationCts = new CancellationTokenSource();
        var token = _pulseAnimationCts.Token;

        while (!token.IsCancellationRequested)
        {
            try
            {
                // Pulse Ring Animation
                PulseRing.Scale = 1;
                PulseRing.Opacity = 0.6;

                await Task.WhenAll(
                    PulseRing.ScaleTo(2, 1000, Easing.SinOut),
                    PulseRing.FadeTo(0, 1000, Easing.SinOut)
                );

                if (token.IsCancellationRequested) break;

                // รอก่อนจะ pulse อีกครั้ง
                await Task.Delay(2000, token);
            }
            catch (OperationCanceledException)
            {
                break;
            }
            catch (Exception)
            {
                // Ignore animation errors
            }
        }
    }

    /// <summary>
    /// อัพเดท UI เมื่อ ViewModel properties เปลี่ยน
    /// </summary>
    private void OnViewModelPropertyChanged(object? sender, PropertyChangedEventArgs e)
    {
        if (e.PropertyName == nameof(LoginViewModel.GlowColor) ||
            e.PropertyName == nameof(LoginViewModel.IsServerConnected))
        {
            MainThread.BeginInvokeOnMainThread(() =>
            {
                try
                {
                    // อัพเดทสี Shadow ของ Login Card
                    LoginCardShadow.Brush = new SolidColorBrush(_viewModel.GlowColor);

                    // อัพเดทสี Indicator วงกลม
                    var statusColor = _viewModel.IsServerConnected
                        ? Color.FromArgb("#22C55E")  // สีเขียว
                        : Color.FromArgb("#EF4444"); // สีแดง

                    ConnectionIndicator.Fill = new SolidColorBrush(statusColor);
                    PulseRing.Stroke = new SolidColorBrush(statusColor);
                }
                catch (Exception)
                {
                    // Ignore UI update errors
                }
            });
        }
    }

    /// <summary>
    /// เมื่อ Login สำเร็จ - นำทางไป AppShell
    /// </summary>
    private void OnLoginSuccessful(object? sender, EventArgs e)
    {
        // Unsubscribe event ก่อน
        _viewModel.LoginSuccessful -= OnLoginSuccessful;

        // นำทางไป AppShell บน Main Thread
        MainThread.BeginInvokeOnMainThread(() =>
        {
            if (Application.Current != null)
            {
                Application.Current.MainPage = new AppShell();
            }
        });
    }

    /// <summary>
    /// เมื่อต้องการไปหน้า Setup - นำทางไป SetupPage
    /// </summary>
    private void OnNavigateToSetup(object? sender, EventArgs e)
    {
        // Unsubscribe event ก่อน
        _viewModel.NavigateToSetup -= OnNavigateToSetup;

        // นำทางไปหน้า Setup บน Main Thread
        MainThread.BeginInvokeOnMainThread(async () =>
        {
            // ดึง SetupPage จาก DI Container
            var setupViewModel = Application.Current?.Handler?.MauiContext?.Services.GetService<SetupViewModel>();
            if (setupViewModel != null)
            {
                var setupPage = new SetupPage(setupViewModel);
                await Navigation.PushAsync(setupPage);
            }
        });
    }

    /// <summary>
    /// เมื่อหน้าถูกทำลาย
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();

        // หยุด Animation
        _fireflyAnimationCts?.Cancel();
        _pulseAnimationCts?.Cancel();

        // Unsubscribe event เพื่อป้องกัน memory leak
        _viewModel.LoginSuccessful -= OnLoginSuccessful;
        _viewModel.NavigateToSetup -= OnNavigateToSetup;
        _viewModel.PropertyChanged -= OnViewModelPropertyChanged;
    }
}

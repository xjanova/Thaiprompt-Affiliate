using ThaipromptAffiliate.ViewModels;

namespace ThaipromptAffiliate.Views;

/// <summary>
/// หน้า Hub Selection - เลือกบริการที่ต้องการใช้งาน
///
/// ╔══════════════════════════════════════════════════════════════════╗
/// ║         🚀 THAIPROMPT HUB SELECTION V2 🚀                        ║
/// ║              World-Class Service Selection                       ║
/// ╚══════════════════════════════════════════════════════════════════╝
///
/// ฟังก์ชันหลัก:
/// - แสดง 8 บริการหลักให้เลือก พร้อม Glassmorphism UI
/// - Background Animations (Particles, Aurora, Avatar Glow)
/// - แสดงข้อมูล user (ถ้า login แล้ว)
/// - Quick actions สำหรับการดำเนินการด่วน
/// - Footer แสดง login/register หรือ balance
/// </summary>
public partial class HubSelectionPage : ContentPage
{
    private readonly HubSelectionViewModel _viewModel;
    private bool _hasAnimated = false;
    private CancellationTokenSource? _animationCts;

    /// <summary>
    /// Constructor - รับ ViewModel ผ่าน DI
    /// </summary>
    public HubSelectionPage(HubSelectionViewModel viewModel)
    {
        InitializeComponent();
        _viewModel = viewModel;
        BindingContext = _viewModel;
    }

    /// <summary>
    /// เรียกเมื่อหน้าปรากฏ - โหลดข้อมูลและเริ่ม animation
    /// </summary>
    protected override async void OnAppearing()
    {
        base.OnAppearing();

        // โหลดข้อมูล user (ถ้ามี)
        await _viewModel.LoadUserDataAsync();

        // Animation สำหรับ cards (ครั้งแรกเท่านั้น)
        if (!_hasAnimated)
        {
            _hasAnimated = true;
            await AnimateCardsEntrance();
        }

        // เริ่ม background animations
        StartBackgroundAnimations();
    }

    /// <summary>
    /// เรียกเมื่อหน้าหายไป - หยุด animation
    /// </summary>
    protected override void OnDisappearing()
    {
        base.OnDisappearing();

        // หยุด background animations เพื่อประหยัด battery
        StopBackgroundAnimations();
    }

    /// <summary>
    /// เริ่ม background animations ทั้งหมด
    /// </summary>
    private void StartBackgroundAnimations()
    {
        // ยกเลิก animation เก่า (ถ้ามี)
        StopBackgroundAnimations();

        // สร้าง CancellationTokenSource ใหม่
        _animationCts = new CancellationTokenSource();
        var ct = _animationCts.Token;

        // เริ่ม animations แบบ parallel
        _ = Task.Run(async () =>
        {
            try
            {
                var tasks = new List<Task>
                {
                    AnimateParticlesAsync(ct),
                    AnimateAurorasAsync(ct),
                    AnimateAvatarGlowAsync(ct)
                };

                await Task.WhenAll(tasks);
            }
            catch (OperationCanceledException)
            {
                // Animation ถูกยกเลิก - ปกติ
            }
        }, ct);
    }

    /// <summary>
    /// หยุด background animations ทั้งหมด
    /// </summary>
    private void StopBackgroundAnimations()
    {
        if (_animationCts != null)
        {
            _animationCts.Cancel();
            _animationCts.Dispose();
            _animationCts = null;
        }
    }

    /// <summary>
    /// Animation สำหรับ cards เข้าหน้าจอ - Fade + Scale
    /// </summary>
    private async Task AnimateCardsEntrance()
    {
        // รอให้ page render เสร็จก่อน
        await Task.Delay(100);

        // Animation จะทำผ่าน CollectionView item animations
        // MAUI ไม่รองรับ stagger animation โดยตรง
        // แต่เราสามารถ animate elements อื่นๆ ได้
    }

    /// <summary>
    /// Animation สำหรับ floating particles
    /// เคลื่อนไหวแบบ random float ขึ้น-ลง และ ซ้าย-ขวา
    /// </summary>
    private async Task AnimateParticlesAsync(CancellationToken ct)
    {
        var random = new Random();
        var particles = new[] { BgParticle1, BgParticle2, BgParticle3, BgParticle4 };

        // เก็บค่าเริ่มต้น
        var initialPositions = new (double X, double Y)[particles.Length];
        for (int i = 0; i < particles.Length; i++)
        {
            initialPositions[i] = (particles[i].TranslationX, particles[i].TranslationY);
        }

        while (!ct.IsCancellationRequested)
        {
            var animationTasks = new List<Task>();

            for (int i = 0; i < particles.Length; i++)
            {
                var particle = particles[i];
                var baseX = initialPositions[i].X;
                var baseY = initialPositions[i].Y;

                // สร้าง random offset
                var offsetX = random.Next(-30, 31);
                var offsetY = random.Next(-40, 41);

                // Animation duration แตกต่างกันให้ดูธรรมชาติ
                var duration = 3000 + random.Next(2000);

                // Dispatch to UI thread
                MainThread.BeginInvokeOnMainThread(() =>
                {
                    try
                    {
                        particle.TranslateTo(baseX + offsetX, baseY + offsetY, (uint)duration, Easing.SinInOut);
                        particle.FadeTo(0.2 + random.NextDouble() * 0.4, (uint)duration, Easing.SinInOut);
                    }
                    catch
                    {
                        // ignore animation errors
                    }
                });
            }

            // รอให้ animation จบ
            await Task.Delay(4000, ct);
        }
    }

    /// <summary>
    /// Animation สำหรับ Aurora glows
    /// ขยาย-หด และ fade in-out อย่างช้าๆ
    /// </summary>
    private async Task AnimateAurorasAsync(CancellationToken ct)
    {
        var random = new Random();
        bool toggle = false;

        while (!ct.IsCancellationRequested)
        {
            toggle = !toggle;

            // Dispatch to UI thread
            MainThread.BeginInvokeOnMainThread(() =>
            {
                try
                {
                    // Aurora 1 - ขยาย/หด + เคลื่อนไหว
                    var scale1 = toggle ? 1.2 : 0.9;
                    var opacity1 = toggle ? 0.08 : 0.04;
                    var transX1 = toggle ? 120 : 80;

                    Aurora1.ScaleTo(scale1, 4000, Easing.SinInOut);
                    Aurora1.FadeTo(opacity1, 4000, Easing.SinInOut);
                    Aurora1.TranslateTo(transX1, Aurora1.TranslationY, 4000, Easing.SinInOut);

                    // Aurora 2 - สลับกัน
                    var scale2 = toggle ? 0.85 : 1.15;
                    var opacity2 = toggle ? 0.03 : 0.06;
                    var transX2 = toggle ? -100 : -140;

                    Aurora2.ScaleTo(scale2, 5000, Easing.SinInOut);
                    Aurora2.FadeTo(opacity2, 5000, Easing.SinInOut);
                    Aurora2.TranslateTo(transX2, Aurora2.TranslationY, 5000, Easing.SinInOut);
                }
                catch
                {
                    // ignore animation errors
                }
            });

            // รอให้ animation จบ
            await Task.Delay(5000, ct);
        }
    }

    /// <summary>
    /// Animation สำหรับ Avatar Glow
    /// หมุนเบาๆ และ pulse
    /// </summary>
    private async Task AnimateAvatarGlowAsync(CancellationToken ct)
    {
        double rotation = 0;

        while (!ct.IsCancellationRequested)
        {
            rotation += 360;

            // Dispatch to UI thread
            MainThread.BeginInvokeOnMainThread(() =>
            {
                try
                {
                    // หมุน Avatar Glow Ring
                    AvatarGlow.RotateTo(rotation, 10000, Easing.Linear);

                    // Pulse effect - ขยาย/หด
                    _ = Task.Run(async () =>
                    {
                        await Task.Delay(0);
                        MainThread.BeginInvokeOnMainThread(() =>
                        {
                            AvatarGlow.ScaleTo(1.08, 2000, Easing.SinInOut);
                        });

                        await Task.Delay(2000);
                        MainThread.BeginInvokeOnMainThread(() =>
                        {
                            AvatarGlow.ScaleTo(1.0, 2000, Easing.SinInOut);
                        });
                    });
                }
                catch
                {
                    // ignore animation errors
                }
            });

            // รอให้หมุนครบรอบ
            await Task.Delay(10000, ct);
        }
    }

    /// <summary>
    /// Cleanup เมื่อ page ถูก unload
    /// </summary>
    ~HubSelectionPage()
    {
        StopBackgroundAnimations();
    }
}

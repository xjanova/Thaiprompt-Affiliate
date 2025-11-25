using System.Collections.ObjectModel;
using System.Windows.Input;
using ThaipromptAffiliate.Helpers;
using ThaipromptAffiliate.Models;
using ThaipromptAffiliate.Services;

namespace ThaipromptAffiliate.ViewModels;

/// <summary>
/// ViewModel สำหรับหน้า Hub Selection
///
/// หน้าที่:
/// - จัดการ 8 Hub Items หลัก
/// - จัดการข้อมูล User (login status, balance)
/// - นำทางไปยังบริการที่เลือก
/// </summary>
public class HubSelectionViewModel : BaseViewModel
{
    private readonly ApiService _apiService;

    private bool _isLoggedIn = false;
    private string _userName = "";
    private string _userInitial = "?";
    private decimal _balance = 0;
    private string _balanceDisplay = "฿0.00";
    private string _greetingText = "สวัสดี";
    private string _welcomeTitle = "ยินดีต้อนรับ";

    /// <summary>
    /// รายการ Hub Items ทั้ง 8 รายการ
    /// </summary>
    public ObservableCollection<HubItem> HubItems { get; }

    /// <summary>
    /// สถานะ Login
    /// </summary>
    public bool IsLoggedIn
    {
        get => _isLoggedIn;
        set
        {
            if (SetProperty(ref _isLoggedIn, value))
            {
                OnPropertyChanged(nameof(IsNotLoggedIn));
            }
        }
    }

    /// <summary>
    /// Inverse ของ IsLoggedIn สำหรับ binding
    /// </summary>
    public bool IsNotLoggedIn => !IsLoggedIn;

    /// <summary>
    /// ชื่อผู้ใช้
    /// </summary>
    public string UserName
    {
        get => _userName;
        set => SetProperty(ref _userName, value);
    }

    /// <summary>
    /// ตัวอักษรแรกของชื่อ (สำหรับ Avatar)
    /// </summary>
    public string UserInitial
    {
        get => _userInitial;
        set => SetProperty(ref _userInitial, value);
    }

    /// <summary>
    /// ยอดเงินคงเหลือ
    /// </summary>
    public decimal Balance
    {
        get => _balance;
        set
        {
            if (SetProperty(ref _balance, value))
            {
                BalanceDisplay = $"฿{value:N2}";
            }
        }
    }

    /// <summary>
    /// ยอดเงินแสดงผล
    /// </summary>
    public string BalanceDisplay
    {
        get => _balanceDisplay;
        set => SetProperty(ref _balanceDisplay, value);
    }

    /// <summary>
    /// ข้อความทักทาย (ตามเวลา)
    /// </summary>
    public string GreetingText
    {
        get => _greetingText;
        set => SetProperty(ref _greetingText, value);
    }

    /// <summary>
    /// หัวข้อต้อนรับ
    /// </summary>
    public string WelcomeTitle
    {
        get => _welcomeTitle;
        set => SetProperty(ref _welcomeTitle, value);
    }

    // ═══════════════════════════════════════════════════════════════
    // COMMANDS
    // ═══════════════════════════════════════════════════════════════

    public ICommand SelectHubCommand { get; }
    public ICommand LoginCommand { get; }
    public ICommand RegisterCommand { get; }
    public ICommand ProfileOrLoginCommand { get; }
    public ICommand TopUpCommand { get; }

    /// <summary>
    /// Constructor - สร้าง Hub Items และ Commands
    /// </summary>
    public HubSelectionViewModel(ApiService apiService)
    {
        _apiService = apiService;

        // สร้าง 8 Hub Items
        HubItems = new ObservableCollection<HubItem>(CreateHubItems());

        // สร้าง Commands
        SelectHubCommand = new Command<HubItem>(async (hub) => await OnHubSelected(hub));
        LoginCommand = new Command(async () => await OnLoginTapped());
        RegisterCommand = new Command(async () => await OnRegisterTapped());
        ProfileOrLoginCommand = new Command(async () => await OnProfileOrLoginTapped());
        TopUpCommand = new Command(async () => await OnTopUpTapped());

        // ตั้งค่าข้อความทักทายตามเวลา
        SetGreetingByTime();
    }

    /// <summary>
    /// สร้างรายการ Hub Items ทั้ง 8 รายการ
    /// </summary>
    private List<HubItem> CreateHubItems()
    {
        return new List<HubItem>
        {
            // 1. ช้อปปิ้ง & บริการ
            new HubItem
            {
                Id = "shopping",
                Icon = "🛒",
                Title = "ช้อปปิ้ง & บริการ",
                Subtitle = "สั่งอาหาร, จองโรงแรม, หมอนวด, E-commerce",
                Route = "shopping",
                GradientStart = "#F59E0B",
                GradientEnd = "#EF4444",
                SortOrder = 1
            },

            // 2. กระเป๋าเงิน
            new HubItem
            {
                Id = "wallet",
                Icon = "💰",
                Title = "กระเป๋าเงิน",
                Subtitle = "เติมเงิน, ถอนเงิน, โอนเงิน, Cashback",
                Route = "wallet",
                GradientStart = "#10B981",
                GradientEnd = "#059669",
                SortOrder = 2
            },

            // 3. ลงทุน & Trade
            new HubItem
            {
                Id = "invest",
                Icon = "📈",
                Title = "ลงทุน & Trade",
                Subtitle = "Crypto, Bot Trading, TPIX Token",
                Route = "invest",
                GradientStart = "#3B82F6",
                GradientEnd = "#1D4ED8",
                SortOrder = 3
            },

            // 4. MLM & Affiliate
            new HubItem
            {
                Id = "mlm",
                Icon = "🤝",
                Title = "MLM & Affiliate",
                Subtitle = "สมัครสมาชิก, Commission, Referral, Team",
                Route = "mlm",
                GradientStart = "#8B5CF6",
                GradientEnd = "#6D28D9",
                SortOrder = 4,
                HasBadge = true,
                BadgeText = "HOT"
            },

            // 5. เป็นไรเดอร์
            new HubItem
            {
                Id = "rider",
                Icon = "🚴",
                Title = "เป็นไรเดอร์",
                Subtitle = "รับงานส่งของ, Service Provider",
                Route = "rider",
                GradientStart = "#06B6D4",
                GradientEnd = "#0891B2",
                SortOrder = 5
            },

            // 6. AI Bot
            new HubItem
            {
                Id = "aibot",
                Icon = "🤖",
                Title = "AI Bot",
                Subtitle = "LINE Bot, Chatbot, Automation",
                Route = "aibot",
                GradientStart = "#EC4899",
                GradientEnd = "#BE185D",
                SortOrder = 6,
                HasBadge = true,
                BadgeText = "NEW"
            },

            // 7. Academy
            new HubItem
            {
                Id = "academy",
                Icon = "🎓",
                Title = "Academy",
                Subtitle = "เรียนรู้, หลักสูตร, Certificate",
                Route = "academy",
                GradientStart = "#14B8A6",
                GradientEnd = "#0D9488",
                SortOrder = 7
            },

            // 8. Gaming & Rewards
            new HubItem
            {
                Id = "gaming",
                Icon = "🎮",
                Title = "Gaming & Rewards",
                Subtitle = "เกม, Quest, Achievement, Rewards",
                Route = "gaming",
                GradientStart = "#F97316",
                GradientEnd = "#EA580C",
                SortOrder = 8
            }
        };
    }

    /// <summary>
    /// ตั้งค่าข้อความทักทายตามเวลา
    /// </summary>
    private void SetGreetingByTime()
    {
        var hour = DateTime.Now.Hour;

        GreetingText = hour switch
        {
            >= 5 and < 12 => "สวัสดีตอนเช้า ☀️",
            >= 12 and < 17 => "สวัสดีตอนบ่าย 🌤️",
            >= 17 and < 21 => "สวัสดีตอนเย็น 🌅",
            _ => "สวัสดีตอนกลางคืน 🌙"
        };
    }

    /// <summary>
    /// โหลดข้อมูล User (เรียกตอน OnAppearing)
    /// </summary>
    public async Task LoadUserDataAsync()
    {
        try
        {
            // ตรวจสอบว่ามี token หรือไม่
            var token = await SecureStorage.GetAsync(Constants.StorageKeys.AuthToken);

            if (string.IsNullOrEmpty(token))
            {
                IsLoggedIn = false;
                WelcomeTitle = "ยินดีต้อนรับ";
                return;
            }

            // ดึงข้อมูล user จาก API
            var user = await _apiService.GetCurrentUserAsync();

            if (user != null)
            {
                IsLoggedIn = true;
                UserName = user.Name ?? "User";
                UserInitial = string.IsNullOrEmpty(user.Name) ? "U" : user.Name[0].ToString().ToUpper();
                WelcomeTitle = user.Name ?? "User";

                // ดึง balance
                var dashboardStats = await _apiService.GetDashboardStatsAsync();
                if (dashboardStats != null)
                {
                    Balance = dashboardStats.TotalEarnings;
                }
            }
            else
            {
                IsLoggedIn = false;
                WelcomeTitle = "ยินดีต้อนรับ";
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"LoadUserData Error: {ex.Message}");
            IsLoggedIn = false;
            WelcomeTitle = "ยินดีต้อนรับ";
        }
    }

    /// <summary>
    /// เมื่อเลือก Hub Item
    /// </summary>
    private async Task OnHubSelected(HubItem hub)
    {
        if (hub == null) return;

        try
        {
            // บาง Hub ต้อง login ก่อน
            var requiresLogin = hub.Id is "wallet" or "mlm" or "rider" or "invest";

            if (requiresLogin && !IsLoggedIn)
            {
                // แสดง dialog ให้ login ก่อน
                var result = await Shell.Current.DisplayAlert(
                    "ต้องเข้าสู่ระบบ",
                    $"กรุณาเข้าสู่ระบบเพื่อใช้บริการ {hub.Title}",
                    "เข้าสู่ระบบ",
                    "ยกเลิก"
                );

                if (result)
                {
                    await Shell.Current.GoToAsync("login");
                }
                return;
            }

            // นำทางไปหน้าที่เลือก
            // await Shell.Current.GoToAsync(hub.Route);

            // ตอนนี้แสดง alert ก่อน (เพราะยังไม่มีหน้าจริง)
            await Shell.Current.DisplayAlert(
                hub.Title,
                $"กำลังพัฒนา: {hub.Subtitle}",
                "ตกลง"
            );
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Hub Navigation Error: {ex.Message}");
            await ShowErrorAsync("ไม่สามารถไปยังหน้าที่เลือกได้");
        }
    }

    /// <summary>
    /// ไปหน้า Login
    /// </summary>
    private async Task OnLoginTapped()
    {
        await Shell.Current.GoToAsync("login");
    }

    /// <summary>
    /// ไปหน้า Register
    /// </summary>
    private async Task OnRegisterTapped()
    {
        // await Shell.Current.GoToAsync("register");
        await Shell.Current.DisplayAlert(
            "สมัครสมาชิก",
            "หน้าสมัครสมาชิกกำลังพัฒนา",
            "ตกลง"
        );
    }

    /// <summary>
    /// เมื่อกด Avatar (ไป Profile หรือ Login)
    /// </summary>
    private async Task OnProfileOrLoginTapped()
    {
        if (IsLoggedIn)
        {
            // ไป Profile
            // await Shell.Current.GoToAsync("profile");
            await Shell.Current.DisplayAlert(
                "โปรไฟล์",
                $"ชื่อ: {UserName}\nยอดเงิน: {BalanceDisplay}",
                "ตกลง"
            );
        }
        else
        {
            await OnLoginTapped();
        }
    }

    /// <summary>
    /// ไปหน้าเติมเงิน
    /// </summary>
    private async Task OnTopUpTapped()
    {
        // await Shell.Current.GoToAsync("wallet/topup");
        await Shell.Current.DisplayAlert(
            "เติมเงิน",
            "หน้าเติมเงินกำลังพัฒนา",
            "ตกลง"
        );
    }
}

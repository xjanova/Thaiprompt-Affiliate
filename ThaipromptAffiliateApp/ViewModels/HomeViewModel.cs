using System.Collections.ObjectModel;
using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliateApp.Models;
using ThaipromptAffiliateApp.Services;

namespace ThaipromptAffiliateApp.ViewModels
{
    /// <summary>
    /// ViewModel for Home page
    /// ใช้ manual property implementation แทน source generators
    /// </summary>
    public class HomeViewModel : BaseViewModel
    {
        private readonly IConfigurationService _configService;
        private readonly ApiService _apiService;

        private string _welcomeTitle = "ยินดีต้อนรับสู่";
        private string _appName = "Thaiprompt Affiliate";
        private string _subtitle = "ระบบจัดการแอฟฟิลิเอทที่ทันสมัย";
        private string? _logoUrl;
        private string? _heroImageUrl;
        private bool _isOfflineMode;

        public string WelcomeTitle
        {
            get => _welcomeTitle;
            set => SetProperty(ref _welcomeTitle, value);
        }

        public string AppName
        {
            get => _appName;
            set => SetProperty(ref _appName, value);
        }

        public string Subtitle
        {
            get => _subtitle;
            set => SetProperty(ref _subtitle, value);
        }

        public string? LogoUrl
        {
            get => _logoUrl;
            set => SetProperty(ref _logoUrl, value);
        }

        public string? HeroImageUrl
        {
            get => _heroImageUrl;
            set => SetProperty(ref _heroImageUrl, value);
        }

        public bool IsOfflineMode
        {
            get => _isOfflineMode;
            set => SetProperty(ref _isOfflineMode, value);
        }

        public ObservableCollection<QuickAction> QuickActions { get; } = new();
        public ObservableCollection<StatCard> StatsCards { get; } = new();
        public ObservableCollection<FeaturedItem> FeaturedItems { get; } = new();
        public ObservableCollection<Announcement> Announcements { get; } = new();

        // Commands
        public ICommand InitializeCommand { get; }
        public ICommand RefreshCommand { get; }
        public ICommand NavigateToActionCommand { get; }
        public ICommand OpenFeaturedItemCommand { get; }

        public HomeViewModel(IConfigurationService configService, ApiService apiService)
        {
            _configService = configService;
            _apiService = apiService;
            Title = "หน้าแรก";

            // Initialize commands
            InitializeCommand = new AsyncRelayCommand(InitializeAsync);
            RefreshCommand = new AsyncRelayCommand(RefreshAsync);
            NavigateToActionCommand = new AsyncRelayCommand<QuickAction>(NavigateToActionAsync);
            OpenFeaturedItemCommand = new AsyncRelayCommand<FeaturedItem>(OpenFeaturedItemAsync);

            LoadDefaultContent();
        }

        /// <summary>
        /// Initialize home page with data from API
        /// </summary>
        private async Task InitializeAsync()
        {
            if (IsBusy) return;

            try
            {
                IsBusy = true;

                // Try to load configuration from API
                var config = await _configService.GetConfigurationAsync();

                if (config != null)
                {
                    ApplyConfiguration(config);
                    IsOfflineMode = false;
                }
                else
                {
                    IsOfflineMode = true;
                }

                // Load dashboard stats
                await LoadStatsAsync();

                // Load featured content
                await LoadFeaturedContentAsync();
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Initialize error: {ex.Message}");
                IsOfflineMode = true;
            }
            finally
            {
                IsBusy = false;
            }
        }

        /// <summary>
        /// Refresh all home content
        /// </summary>
        private async Task RefreshAsync()
        {
            await InitializeAsync();
        }

        /// <summary>
        /// Navigate to quick action target
        /// </summary>
        private async Task NavigateToActionAsync(QuickAction? action)
        {
            if (action == null || !action.IsEnabled) return;

            try
            {
                // Navigate to target page
                if (!string.IsNullOrEmpty(action.NavigationTarget))
                {
                    await Shell.Current.GoToAsync($"//{action.NavigationTarget}");
                }
            }
            catch (Exception ex)
            {
                await Shell.Current.DisplayAlert("ผิดพลาด",
                    $"ไม่สามารถเปิดหน้านี้ได้: {ex.Message}", "ตกลง");
            }
        }

        /// <summary>
        /// Open featured item
        /// </summary>
        private async Task OpenFeaturedItemAsync(FeaturedItem? item)
        {
            if (item == null) return;

            if (!string.IsNullOrEmpty(item.ActionUrl))
            {
                try
                {
                    await Browser.OpenAsync(item.ActionUrl, BrowserLaunchMode.SystemPreferred);
                }
                catch (Exception ex)
                {
                    await Shell.Current.DisplayAlert("ผิดพลาด",
                        $"ไม่สามารถเปิดลิงก์ได้: {ex.Message}", "ตกลง");
                }
            }
        }

        /// <summary>
        /// Load default/offline content
        /// </summary>
        private void LoadDefaultContent()
        {
            QuickActions.Clear();

            // Default quick actions
            QuickActions.Add(new QuickAction
            {
                Title = "แดชบอร์ด",
                Icon = "📊",
                Description = "ดูภาพรวมและสถิติ",
                Color = "#D4AF37",
                NavigationTarget = "Dashboard"
            });

            QuickActions.Add(new QuickAction
            {
                Title = "คอมมิชชั่น",
                Icon = "💰",
                Description = "รายได้และคอมมิชชั่น",
                Color = "#DC143C",
                NavigationTarget = "Commissions"
            });

            QuickActions.Add(new QuickAction
            {
                Title = "ผู้แนะนำ",
                Icon = "👥",
                Description = "จัดการผู้แนะนำ",
                Color = "#F1D95C",
                NavigationTarget = "Referrals"
            });

            QuickActions.Add(new QuickAction
            {
                Title = "โปรไฟล์",
                Icon = "⚙️",
                Description = "ตั้งค่าบัญชี",
                Color = "#2A2A2A",
                NavigationTarget = "Profile"
            });

            // Default stats
            LoadDefaultStats();
        }

        /// <summary>
        /// Load default stats for offline mode
        /// </summary>
        private void LoadDefaultStats()
        {
            StatsCards.Clear();

            StatsCards.Add(new StatCard
            {
                Title = "รายได้ทั้งหมด",
                Value = "-",
                Icon = "💰",
                Color = "#D4AF37",
                SubValue = "กำลังโหลด..."
            });

            StatsCards.Add(new StatCard
            {
                Title = "รออนุมัติ",
                Value = "-",
                Icon = "⏳",
                Color = "#DC143C",
                SubValue = "กำลังโหลด..."
            });

            StatsCards.Add(new StatCard
            {
                Title = "ผู้แนะนำ",
                Value = "-",
                Icon = "👥",
                Color = "#F1D95C",
                SubValue = "กำลังโหลด..."
            });
        }

        /// <summary>
        /// Apply configuration from API
        /// </summary>
        private void ApplyConfiguration(AppConfiguration config)
        {
            AppName = config.AppName;
            LogoUrl = config.LogoUrl;

            // Load custom quick actions if provided
            if (config.MenuItems?.Any() == true)
            {
                QuickActions.Clear();

                foreach (var item in config.MenuItems.Where(m => m.IsActive).OrderBy(m => m.Order))
                {
                    QuickActions.Add(new QuickAction
                    {
                        Title = item.Title,
                        Icon = item.Icon ?? "",
                        Description = item.Title,
                        NavigationTarget = item.Type == "native" ? item.Url : "",
                        IsEnabled = true
                    });
                }
            }

            // Load announcements if provided
            // This would come from API in future enhancement
        }

        /// <summary>
        /// Load stats from dashboard API
        /// </summary>
        private async Task LoadStatsAsync()
        {
            try
            {
                var dashboard = await _apiService.GetDashboardStatsAsync();

                if (dashboard != null)
                {
                    StatsCards.Clear();

                    StatsCards.Add(new StatCard
                    {
                        Title = "รายได้ทั้งหมด",
                        Value = dashboard.TotalEarnings.ToString("N2"),
                        Icon = "💰",
                        Color = "#D4AF37",
                        SubValue = "บาท",
                        Trend = "up"
                    });

                    StatsCards.Add(new StatCard
                    {
                        Title = "รออนุมัติ",
                        Value = dashboard.PendingEarnings.ToString("N2"),
                        Icon = "⏳",
                        Color = "#DC143C",
                        SubValue = "บาท",
                        Trend = "neutral"
                    });

                    StatsCards.Add(new StatCard
                    {
                        Title = "ผู้แนะนำ",
                        Value = dashboard.TotalReferrals.ToString(),
                        Icon = "👥",
                        Color = "#F1D95C",
                        SubValue = "คน",
                        Trend = "up"
                    });

                    StatsCards.Add(new StatCard
                    {
                        Title = "คลิกลิงก์",
                        Value = dashboard.TotalCommissions.ToString(),
                        Icon = "🔗",
                        Color = "#10B981",
                        SubValue = "ครั้ง",
                        Trend = "up"
                    });
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Load stats error: {ex.Message}");
                // Keep default stats
            }
        }

        /// <summary>
        /// Load featured content from API
        /// </summary>
        private async Task LoadFeaturedContentAsync()
        {
            try
            {
                // This would load from API in future
                // For now, use default featured items
                FeaturedItems.Clear();

                FeaturedItems.Add(new FeaturedItem
                {
                    Title = "🎉 เริ่มต้นใช้งาน",
                    Description = "เรียนรู้วิธีใช้งานระบบแอฟฟิลิเอท",
                    Icon = "📚",
                    ActionUrl = "https://thaiprompt.com/docs",
                    ActionText = "ดูคู่มือ",
                    Order = 1
                });

                FeaturedItems.Add(new FeaturedItem
                {
                    Title = "💡 เพิ่มรายได้",
                    Description = "เทคนิคการแชร์ลิงก์ให้ได้ผล",
                    Icon = "🚀",
                    ActionUrl = "https://thaiprompt.com/tips",
                    ActionText = "เรียนรู้เพิ่มเติม",
                    Order = 2
                });

                await Task.CompletedTask;
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Load featured content error: {ex.Message}");
            }
        }
    }
}

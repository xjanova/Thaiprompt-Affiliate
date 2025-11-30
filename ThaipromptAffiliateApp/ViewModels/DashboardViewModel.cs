using System.Collections.ObjectModel;
using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliateApp.Helpers;
using ThaipromptAffiliateApp.Models;
using ThaipromptAffiliateApp.Services;

namespace ThaipromptAffiliateApp.ViewModels
{
    /// <summary>
    /// ViewModel for dashboard page
    /// ใช้ manual property implementation แทน source generators
    /// </summary>
    public class DashboardViewModel : BaseViewModel
    {
        private readonly IApiService _apiService;

        private DashboardStats? _stats;
        private User? _currentUser;
        private string _welcomeMessage = string.Empty;
        private ObservableCollection<Commission> _recentCommissions = new();
        private string _totalEarnings = "฿0.00";
        private string _pendingEarnings = "฿0.00";
        private string _approvedEarnings = "฿0.00";
        private string _totalReferrals = "0";
        private string _growthPercentage = "0%";
        private bool _isGrowthPositive = true;

        public DashboardStats? Stats
        {
            get => _stats;
            set => SetProperty(ref _stats, value);
        }

        public User? CurrentUser
        {
            get => _currentUser;
            set => SetProperty(ref _currentUser, value);
        }

        public string WelcomeMessage
        {
            get => _welcomeMessage;
            set => SetProperty(ref _welcomeMessage, value);
        }

        public ObservableCollection<Commission> RecentCommissions
        {
            get => _recentCommissions;
            set => SetProperty(ref _recentCommissions, value);
        }

        public string TotalEarnings
        {
            get => _totalEarnings;
            set => SetProperty(ref _totalEarnings, value);
        }

        public string PendingEarnings
        {
            get => _pendingEarnings;
            set => SetProperty(ref _pendingEarnings, value);
        }

        public string ApprovedEarnings
        {
            get => _approvedEarnings;
            set => SetProperty(ref _approvedEarnings, value);
        }

        public string TotalReferrals
        {
            get => _totalReferrals;
            set => SetProperty(ref _totalReferrals, value);
        }

        public string GrowthPercentage
        {
            get => _growthPercentage;
            set => SetProperty(ref _growthPercentage, value);
        }

        public bool IsGrowthPositive
        {
            get => _isGrowthPositive;
            set => SetProperty(ref _isGrowthPositive, value);
        }

        // Commands
        public ICommand LoadDataCommand { get; }
        public ICommand RefreshCommand { get; }
        public ICommand ViewAllCommissionsCommand { get; }
        public ICommand ViewReferralsCommand { get; }
        public ICommand ViewProfileCommand { get; }
        public ICommand ShareReferralLinkCommand { get; }
        public ICommand LogoutCommand { get; }

        public DashboardViewModel(IApiService apiService)
        {
            _apiService = apiService;
            Title = "แดชบอร์ด";

            // Initialize commands
            LoadDataCommand = new AsyncRelayCommand(LoadDataAsync);
            RefreshCommand = new AsyncRelayCommand(RefreshAsync);
            ViewAllCommissionsCommand = new AsyncRelayCommand(ViewAllCommissionsAsync);
            ViewReferralsCommand = new AsyncRelayCommand(ViewReferralsAsync);
            ViewProfileCommand = new AsyncRelayCommand(ViewProfileAsync);
            ShareReferralLinkCommand = new AsyncRelayCommand(ShareReferralLinkAsync);
            LogoutCommand = new AsyncRelayCommand(LogoutAsync);
        }

        /// <summary>
        /// Initialize and load dashboard data
        /// </summary>
        public async Task InitializeAsync()
        {
            await LoadDataAsync();
        }

        /// <summary>
        /// Load dashboard data
        /// </summary>
        private async Task LoadDataAsync()
        {
            await ExecuteWithBusyAsync(async () =>
            {
                // Load user data
                CurrentUser = await _apiService.GetCurrentUserAsync();

                if (CurrentUser != null)
                {
                    WelcomeMessage = $"สวัสดี, {CurrentUser.Name}";
                }

                // Load dashboard stats
                Stats = await _apiService.GetDashboardStatsAsync();

                if (Stats != null)
                {
                    UpdateDisplayValues();
                }
            });
        }

        /// <summary>
        /// Refresh dashboard data
        /// </summary>
        private async Task RefreshAsync()
        {
            await ExecuteWithRefreshAsync(async () =>
            {
                Stats = await _apiService.GetDashboardStatsAsync();

                if (Stats != null)
                {
                    UpdateDisplayValues();
                }
            });
        }

        /// <summary>
        /// Update display values from stats
        /// </summary>
        private void UpdateDisplayValues()
        {
            if (Stats == null) return;

            TotalEarnings = Constants.FormatCurrency(Stats.TotalEarnings);
            PendingEarnings = Constants.FormatCurrency(Stats.PendingEarnings);
            ApprovedEarnings = Constants.FormatCurrency(Stats.ApprovedEarnings);
            TotalReferrals = Stats.TotalReferrals.ToString();

            // Growth percentage
            IsGrowthPositive = Stats.GrowthPercentage >= 0;
            GrowthPercentage = $"{(IsGrowthPositive ? "+" : "")}{Stats.GrowthPercentage:F1}%";

            // Recent commissions
            RecentCommissions.Clear();
            foreach (var commission in Stats.RecentCommissions)
            {
                RecentCommissions.Add(commission);
            }
        }

        /// <summary>
        /// Navigate to commissions page
        /// </summary>
        private async Task ViewAllCommissionsAsync()
        {
            await NavigateToAsync("commissions");
        }

        /// <summary>
        /// Navigate to referrals page
        /// </summary>
        private async Task ViewReferralsAsync()
        {
            await NavigateToAsync("referrals");
        }

        /// <summary>
        /// Navigate to profile page
        /// </summary>
        private async Task ViewProfileAsync()
        {
            await NavigateToAsync("profile");
        }

        /// <summary>
        /// Share referral link
        /// </summary>
        private async Task ShareReferralLinkAsync()
        {
            try
            {
                if (CurrentUser?.ReferralCode == null)
                {
                    await ShowSuccessAsync("แจ้งเตือน", "ไม่พบรหัสแนะนำของคุณ");
                    return;
                }

                var referralLink = $"https://your-domain.com/register?ref={CurrentUser.ReferralCode}";

                await Share.Default.RequestAsync(new ShareTextRequest
                {
                    Text = $"ร่วมเป็นพาร์ทเนอร์กับเรา! ลงทะเบียนผ่านลิงก์นี้: {referralLink}",
                    Title = "แชร์ลิงก์แนะนำ"
                });
            }
            catch (Exception ex)
            {
                await HandleErrorAsync(ex);
            }
        }

        /// <summary>
        /// Logout
        /// </summary>
        private async Task LogoutAsync()
        {
            var confirmed = await ShowConfirmationAsync(
                "ออกจากระบบ",
                "คุณต้องการออกจากระบบใช่หรือไม่?");

            if (!confirmed) return;

            await ExecuteWithBusyAsync(async () =>
            {
                await _apiService.LogoutAsync();
                await Shell.Current.GoToAsync("///login");
            });
        }
    }
}

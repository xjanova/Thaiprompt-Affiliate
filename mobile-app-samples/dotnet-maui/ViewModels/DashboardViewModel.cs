using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliate.Models;
using ThaipromptAffiliate.Services;

namespace ThaipromptAffiliate.ViewModels
{
    /// <summary>
    /// ViewModel for dashboard page
    /// </summary>
    public partial class DashboardViewModel : BaseViewModel
    {
        private readonly IApiService _apiService;

        [ObservableProperty]
        private DashboardStats? _stats;

        [ObservableProperty]
        private User? _currentUser;

        [ObservableProperty]
        private string _welcomeMessage = string.Empty;

        [ObservableProperty]
        private ObservableCollection<Commission> _recentCommissions = new();

        [ObservableProperty]
        private string _totalEarnings = "฿0.00";

        [ObservableProperty]
        private string _pendingEarnings = "฿0.00";

        [ObservableProperty]
        private string _approvedEarnings = "฿0.00";

        [ObservableProperty]
        private string _totalReferrals = "0";

        [ObservableProperty]
        private string _growthPercentage = "0%";

        [ObservableProperty]
        private bool _isGrowthPositive = true;

        public DashboardViewModel(IApiService apiService)
        {
            _apiService = apiService;
            Title = "แดชบอร์ด";
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
        [RelayCommand]
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
        [RelayCommand]
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
        [RelayCommand]
        private async Task ViewAllCommissionsAsync()
        {
            await NavigateToAsync("commissions");
        }

        /// <summary>
        /// Navigate to referrals page
        /// </summary>
        [RelayCommand]
        private async Task ViewReferralsAsync()
        {
            await NavigateToAsync("referrals");
        }

        /// <summary>
        /// Navigate to profile page
        /// </summary>
        [RelayCommand]
        private async Task ViewProfileAsync()
        {
            await NavigateToAsync("profile");
        }

        /// <summary>
        /// Share referral link
        /// </summary>
        [RelayCommand]
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
        [RelayCommand]
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

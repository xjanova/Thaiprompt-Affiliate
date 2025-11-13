using System.Collections.ObjectModel;
using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliateApp.Models;
using ThaipromptAffiliateApp.Services;

namespace ThaipromptAffiliateApp.ViewModels;

public class ReferralsViewModel : BaseViewModel
{
    private readonly IApiService _apiService;
    private ObservableCollection<Referral> _referrals;
    private bool _isRefreshing;
    private string _referralLink = "";
    private string _referralCode = "";
    private int _totalReferrals;
    private int _activeReferrals;
    private decimal _totalEarnings;

    public ObservableCollection<Referral> Referrals
    {
        get => _referrals;
        set => SetProperty(ref _referrals, value);
    }

    public bool IsRefreshing
    {
        get => _isRefreshing;
        set => SetProperty(ref _isRefreshing, value);
    }

    public string ReferralLink
    {
        get => _referralLink;
        set => SetProperty(ref _referralLink, value);
    }

    public string ReferralCode
    {
        get => _referralCode;
        set => SetProperty(ref _referralCode, value);
    }

    public int TotalReferrals
    {
        get => _totalReferrals;
        set => SetProperty(ref _totalReferrals, value);
    }

    public int ActiveReferrals
    {
        get => _activeReferrals;
        set => SetProperty(ref _activeReferrals, value);
    }

    public decimal TotalEarnings
    {
        get => _totalEarnings;
        set => SetProperty(ref _totalEarnings, value);
    }

    public ICommand RefreshCommand { get; }
    public ICommand CopyLinkCommand { get; }
    public ICommand ShareLinkCommand { get; }

    public ReferralsViewModel(IApiService apiService)
    {
        _apiService = apiService;
        _referrals = new ObservableCollection<Referral>();

        RefreshCommand = new AsyncRelayCommand(RefreshAsync);
        CopyLinkCommand = new AsyncRelayCommand(CopyLinkAsync);
        ShareLinkCommand = new AsyncRelayCommand(ShareLinkAsync);
    }

    public override async void OnAppearing()
    {
        base.OnAppearing();
        if (Referrals.Count == 0)
        {
            await LoadReferralsAsync();
        }
    }

    private async Task RefreshAsync()
    {
        IsRefreshing = true;
        await LoadReferralsAsync();
        IsRefreshing = false;
    }

    private async Task LoadReferralsAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;

            var result = await _apiService.GetReferralsAsync();

            if (result != null)
            {
                ReferralLink = result.ReferralLink ?? "";
                ReferralCode = result.ReferralCode ?? "";
                TotalReferrals = result.TotalReferrals;
                ActiveReferrals = result.ActiveReferrals;
                TotalEarnings = result.TotalEarnings;

                Referrals.Clear();
                if (result.Referrals != null)
                {
                    foreach (var referral in result.Referrals)
                    {
                        Referrals.Add(referral);
                    }
                }
            }
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถโหลดข้อมูลผู้แนะนำได้: {ex.Message}",
                "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task CopyLinkAsync()
    {
        try
        {
            await Clipboard.SetTextAsync(ReferralLink);
            await Shell.Current.DisplayAlert("สำเร็จ",
                "คัดลอกลิงก์แนะนำเรียบร้อยแล้ว",
                "ตกลง");
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถคัดลอกลิงก์ได้: {ex.Message}",
                "ตกลง");
        }
    }

    private async Task ShareLinkAsync()
    {
        try
        {
            await Share.RequestAsync(new ShareTextRequest
            {
                Text = ReferralLink,
                Title = "แชร์ลิงก์แนะนำ Thaiprompt Affiliate"
            });
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถแชร์ลิงก์ได้: {ex.Message}",
                "ตกลง");
        }
    }
}

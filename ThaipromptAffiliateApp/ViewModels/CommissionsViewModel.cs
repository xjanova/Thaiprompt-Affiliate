using System.Collections.ObjectModel;
using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliateApp.Models;
using ThaipromptAffiliateApp.Services;

namespace ThaipromptAffiliateApp.ViewModels;

public class CommissionsViewModel : BaseViewModel
{
    private readonly IApiService _apiService;
    private ObservableCollection<Commission> _commissions;
    private bool _isRefreshing;
    private bool _isLoadingMore;
    private bool _hasMore;
    private int _currentPage = 1;
    private string _selectedStatus = "ทั้งหมด";

    public ObservableCollection<Commission> Commissions
    {
        get => _commissions;
        set => SetProperty(ref _commissions, value);
    }

    public bool IsRefreshing
    {
        get => _isRefreshing;
        set => SetProperty(ref _isRefreshing, value);
    }

    public bool IsLoadingMore
    {
        get => _isLoadingMore;
        set => SetProperty(ref _isLoadingMore, value);
    }

    public bool HasMore
    {
        get => _hasMore;
        set => SetProperty(ref _hasMore, value);
    }

    public string SelectedStatus
    {
        get => _selectedStatus;
        set
        {
            SetProperty(ref _selectedStatus, value);
            _ = LoadCommissionsAsync();
        }
    }

    public List<string> StatusFilters { get; } = new()
    {
        "ทั้งหมด",
        "อนุมัติแล้ว",
        "รออนุมัติ",
        "ปฏิเสธ"
    };

    public ICommand RefreshCommand { get; }
    public ICommand LoadMoreCommand { get; }
    public ICommand FilterCommand { get; }

    public CommissionsViewModel(IApiService apiService)
    {
        _apiService = apiService;
        _commissions = new ObservableCollection<Commission>();

        RefreshCommand = new AsyncRelayCommand(RefreshAsync);
        LoadMoreCommand = new AsyncRelayCommand(LoadMoreAsync);
        FilterCommand = new AsyncRelayCommand(LoadCommissionsAsync);
    }

    public override async void OnAppearing()
    {
        base.OnAppearing();
        if (Commissions.Count == 0)
        {
            await LoadCommissionsAsync();
        }
    }

    private async Task RefreshAsync()
    {
        IsRefreshing = true;
        _currentPage = 1;
        await LoadCommissionsAsync();
        IsRefreshing = false;
    }

    private async Task LoadCommissionsAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            _currentPage = 1;

            var result = await _apiService.GetCommissionsAsync(_currentPage);

            if (result != null && result.Data != null)
            {
                Commissions.Clear();
                foreach (var commission in result.Data)
                {
                    Commissions.Add(commission);
                }

                HasMore = _currentPage < result.LastPage;
            }
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถโหลดข้อมูลคอมมิชชั่นได้: {ex.Message}",
                "ตกลง");
        }
        finally
        {
            IsBusy = false;
        }
    }

    private async Task LoadMoreAsync()
    {
        if (IsLoadingMore || !HasMore) return;

        try
        {
            IsLoadingMore = true;
            _currentPage++;

            var result = await _apiService.GetCommissionsAsync(_currentPage);

            if (result != null && result.Data != null)
            {
                foreach (var commission in result.Data)
                {
                    Commissions.Add(commission);
                }

                HasMore = _currentPage < result.LastPage;
            }
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถโหลดข้อมูลเพิ่มเติมได้: {ex.Message}",
                "ตกลง");
        }
        finally
        {
            IsLoadingMore = false;
        }
    }
}

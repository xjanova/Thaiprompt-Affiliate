using System.Collections.ObjectModel;
using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;

namespace ThaipromptAffiliateApp.ViewModels;

public class WebViewViewModel : BaseViewModel
{
    private readonly IConfigurationService _configService;
    private string _currentUrl = "";
    private string _pageTitle = "";
    private string _appName = "Thaiprompt";
    private string? _logoUrl;
    private bool _hasLogo;
    private bool _canGoBack;
    private bool _canGoForward;
    private bool _isLoading;
    private ObservableCollection<QuickLink> _quickLinks = new();

    public string CurrentUrl
    {
        get => _currentUrl;
        set => SetProperty(ref _currentUrl, value);
    }

    public string PageTitle
    {
        get => _pageTitle;
        set => SetProperty(ref _pageTitle, value);
    }

    public string AppName
    {
        get => _appName;
        set => SetProperty(ref _appName, value);
    }

    public string? LogoUrl
    {
        get => _logoUrl;
        set
        {
            SetProperty(ref _logoUrl, value);
            HasLogo = !string.IsNullOrEmpty(value);
        }
    }

    public bool HasLogo
    {
        get => _hasLogo;
        set => SetProperty(ref _hasLogo, value);
    }

    public bool CanGoBack
    {
        get => _canGoBack;
        set => SetProperty(ref _canGoBack, value);
    }

    public bool CanGoForward
    {
        get => _canGoForward;
        set => SetProperty(ref _canGoForward, value);
    }

    public bool IsLoading
    {
        get => _isLoading;
        set => SetProperty(ref _isLoading, value);
    }

    public ObservableCollection<QuickLink> QuickLinks
    {
        get => _quickLinks;
        set => SetProperty(ref _quickLinks, value);
    }

    public ICommand GoBackCommand { get; }
    public ICommand GoForwardCommand { get; }
    public ICommand RefreshCommand { get; }
    public ICommand GoHomeCommand { get; }
    public ICommand NavigateToCommand { get; }

    public WebViewViewModel(IConfigurationService configService)
    {
        _configService = configService;

        GoBackCommand = new RelayCommand(GoBack, () => CanGoBack);
        GoForwardCommand = new RelayCommand(GoForward, () => CanGoForward);
        RefreshCommand = new RelayCommand(Refresh);
        GoHomeCommand = new AsyncRelayCommand(GoHomeAsync);
        NavigateToCommand = new RelayCommand<string>(NavigateTo);
    }

    public async Task InitializeAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            IsLoading = true;

            // Load configuration
            var config = await _configService.GetConfigurationAsync();

            if (config != null)
            {
                AppName = config.AppName;
                LogoUrl = config.LogoUrl;
                PageTitle = config.AppName;

                // Set home URL
                if (!string.IsNullOrEmpty(config.HomeUrl))
                {
                    CurrentUrl = config.HomeUrl;
                }

                // Load quick links from menu items
                LoadQuickLinks(config.MenuItems);

                // Apply theme
                await ApplyThemeAsync(config.Theme);
            }
        }
        catch (Exception ex)
        {
            await Shell.Current.DisplayAlert("ข้อผิดพลาด",
                $"ไม่สามารถโหลดการตั้งค่าได้: {ex.Message}",
                "ตกลง");
        }
        finally
        {
            IsBusy = false;
            IsLoading = false;
        }
    }

    private void LoadQuickLinks(List<MenuItem>? menuItems)
    {
        QuickLinks.Clear();

        if (menuItems == null) return;

        foreach (var item in menuItems.Where(m => m.IsActive).OrderBy(m => m.Order))
        {
            QuickLinks.Add(new QuickLink
            {
                Title = item.Title,
                Icon = GetIconEmoji(item.Title),
                Url = item.Url,
                Badge = item.Badge,
                HasBadge = !string.IsNullOrEmpty(item.Badge)
            });
        }
    }

    private string GetIconEmoji(string title)
    {
        return title.ToLower() switch
        {
            var t when t.Contains("หน้าแรก") || t.Contains("home") => "🏠",
            var t when t.Contains("wiki") => "📚",
            var t when t.Contains("แดชบอร์ด") || t.Contains("dashboard") => "📊",
            var t when t.Contains("คอมมิชชั่น") || t.Contains("commission") => "💰",
            var t when t.Contains("ผู้แนะนำ") || t.Contains("referral") => "👥",
            var t when t.Contains("โปรไฟล์") || t.Contains("profile") => "👤",
            var t when t.Contains("การตั้งค่า") || t.Contains("settings") => "⚙️",
            _ => "📌"
        };
    }

    private async Task ApplyThemeAsync(AppTheme? theme)
    {
        if (theme == null) return;

        try
        {
            var resources = Application.Current?.Resources;
            if (resources == null) return;

            // Update dynamic resources
            resources["PrimaryColor"] = Color.FromArgb(theme.PrimaryColor);
            resources["SecondaryColor"] = Color.FromArgb(theme.SecondaryColor);
            resources["AccentColor"] = Color.FromArgb(theme.AccentColor);
            resources["BackgroundColor"] = Color.FromArgb(theme.BackgroundColor);
            resources["TextColor"] = Color.FromArgb(theme.TextColor);
            resources["CardBackground"] = Color.FromArgb(theme.CardBackground);
            resources["BorderColor"] = Color.FromArgb(theme.BorderColor);

            // Update gradient
            var gradient = new LinearGradientBrush
            {
                StartPoint = new Point(0, 0),
                EndPoint = new Point(1, 1)
            };
            gradient.GradientStops.Add(new GradientStop(Color.FromArgb(theme.GradientStart), 0.0f));
            gradient.GradientStops.Add(new GradientStop(Color.FromArgb(theme.GradientEnd), 1.0f));

            resources["PrimaryGradient"] = gradient;
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error applying theme: {ex.Message}");
        }

        await Task.CompletedTask;
    }

    private void GoBack()
    {
        // WebView will handle this via code-behind
    }

    private void GoForward()
    {
        // WebView will handle this via code-behind
    }

    private void Refresh()
    {
        // Trigger re-navigation
        var url = CurrentUrl;
        CurrentUrl = "";
        CurrentUrl = url;
    }

    private async Task GoHomeAsync()
    {
        var config = await _configService.GetConfigurationAsync();
        if (config != null && !string.IsNullOrEmpty(config.HomeUrl))
        {
            CurrentUrl = config.HomeUrl;
        }
    }

    private void NavigateTo(string? url)
    {
        if (string.IsNullOrEmpty(url)) return;

        // Check if it's a native route
        if (url.StartsWith("http://") || url.StartsWith("https://"))
        {
            CurrentUrl = url;
        }
        else
        {
            // Navigate to native page
            Shell.Current.GoToAsync($"//{url}");
        }
    }
}

/// <summary>
/// Quick Link Model for bottom navigation
/// </summary>
public class QuickLink
{
    public string Title { get; set; } = "";
    public string Icon { get; set; } = "";
    public string Url { get; set; } = "";
    public string? Badge { get; set; }
    public bool HasBadge { get; set; }
}

using System.Net.Http.Json;
using System.Text.Json;

namespace ThaipromptAffiliateApp.Services;

/// <summary>
/// Service สำหรับจัดการ Configuration จาก Control Panel
/// </summary>
public interface IConfigurationService
{
    Task<AppConfiguration?> GetConfigurationAsync(bool forceRefresh = false);
    Task<bool> SaveConfigurationAsync(AppConfiguration configuration);
    Task<AppConfiguration?> GetCachedConfigurationAsync();
    Task ClearCacheAsync();
    Task<AppTheme?> GetThemeAsync();
    Task<List<MenuItem>?> GetMenuItemsAsync();
}

public class ConfigurationService : IConfigurationService
{
    private readonly HttpClient _httpClient;
    private readonly string _cacheKey = "app_configuration";
    private AppConfiguration? _cachedConfig;
    private DateTime _cacheTime;

    public ConfigurationService(HttpClient httpClient)
    {
        _httpClient = httpClient;
        _httpClient.BaseAddress = new Uri(Constants.ApiBaseUrl);
    }

    /// <summary>
    /// ดึง Configuration จาก API
    /// </summary>
    public async Task<AppConfiguration?> GetConfigurationAsync(bool forceRefresh = false)
    {
        // Check cache
        if (!forceRefresh && _cachedConfig != null)
        {
            var cacheAge = DateTime.Now - _cacheTime;
            if (cacheAge.TotalMinutes < 60) // Cache 60 minutes
            {
                return _cachedConfig;
            }
        }

        try
        {
            var response = await _httpClient.GetFromJsonAsync<ConfigurationResponse>(
                "/app/configuration");

            if (response?.Success == true && response.Data != null)
            {
                _cachedConfig = response.Data;
                _cacheTime = DateTime.Now;

                // Save to local storage
                await SaveToLocalStorageAsync(response.Data);

                return response.Data;
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error fetching configuration: {ex.Message}");

            // Try to load from cache
            return await GetCachedConfigurationAsync();
        }

        return await GetCachedConfigurationAsync();
    }

    /// <summary>
    /// บันทึก Configuration (สำหรับ Admin)
    /// </summary>
    public async Task<bool> SaveConfigurationAsync(AppConfiguration configuration)
    {
        try
        {
            var response = await _httpClient.PostAsJsonAsync(
                "/app/configuration",
                configuration);

            if (response.IsSuccessStatusCode)
            {
                _cachedConfig = configuration;
                _cacheTime = DateTime.Now;
                await SaveToLocalStorageAsync(configuration);
                return true;
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error saving configuration: {ex.Message}");
        }

        return false;
    }

    /// <summary>
    /// ดึง Configuration จาก Cache/Local Storage
    /// </summary>
    public async Task<AppConfiguration?> GetCachedConfigurationAsync()
    {
        if (_cachedConfig != null)
        {
            return _cachedConfig;
        }

        try
        {
            var json = await SecureStorage.GetAsync(_cacheKey);
            if (!string.IsNullOrEmpty(json))
            {
                _cachedConfig = JsonSerializer.Deserialize<AppConfiguration>(json);
                return _cachedConfig;
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error loading cached configuration: {ex.Message}");
        }

        // Return default configuration
        return GetDefaultConfiguration();
    }

    /// <summary>
    /// Clear cache
    /// </summary>
    public async Task ClearCacheAsync()
    {
        _cachedConfig = null;
        SecureStorage.Remove(_cacheKey);
        await Task.CompletedTask;
    }

    /// <summary>
    /// ดึง Theme settings
    /// </summary>
    public async Task<AppTheme?> GetThemeAsync()
    {
        var config = await GetConfigurationAsync();
        return config?.Theme;
    }

    /// <summary>
    /// ดึง Menu Items
    /// </summary>
    public async Task<List<MenuItem>?> GetMenuItemsAsync()
    {
        var config = await GetConfigurationAsync();
        return config?.MenuItems?.Where(m => m.IsActive)
                                 .OrderBy(m => m.Order)
                                 .ToList();
    }

    /// <summary>
    /// บันทึกลง Local Storage
    /// </summary>
    private async Task SaveToLocalStorageAsync(AppConfiguration configuration)
    {
        try
        {
            var json = JsonSerializer.Serialize(configuration);
            await SecureStorage.SetAsync(_cacheKey, json);
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error saving to local storage: {ex.Message}");
        }
    }

    /// <summary>
    /// Configuration เริ่มต้น (ถ้าโหลดไม่ได้)
    /// </summary>
    private AppConfiguration GetDefaultConfiguration()
    {
        return new AppConfiguration
        {
            AppName = "Thaiprompt Affiliate",
            HomeUrl = "https://thaiprompt.com",
            Theme = new AppTheme
            {
                PrimaryColor = "#D4AF37",      // Gold
                SecondaryColor = "#DC143C",     // Crimson Red
                AccentColor = "#1A1A1A",        // Dark Black
                BackgroundColor = "#0A0A0A",    // Deep Black
                TextColor = "#FFFFFF",          // White
                GradientStart = "#D4AF37",      // Gold
                GradientEnd = "#DC143C",        // Red
                CardBackground = "#1A1A1A",     // Dark
                BorderColor = "#D4AF37",        // Gold
                UseDarkMode = true
            },
            MenuItems = new List<MenuItem>
            {
                new MenuItem
                {
                    Id = 1,
                    Title = "หน้าแรก",
                    Icon = "home_icon.png",
                    Url = "https://thaiprompt.com",
                    Type = "web",
                    Order = 1,
                    IsActive = true
                },
                new MenuItem
                {
                    Id = 2,
                    Title = "Wiki",
                    Icon = "wiki_icon.png",
                    Url = "https://thaiprompt.com/wiki",
                    Type = "web",
                    Order = 2,
                    IsActive = true
                },
                new MenuItem
                {
                    Id = 3,
                    Title = "แดชบอร์ด",
                    Icon = "dashboard_icon.png",
                    Url = "dashboard",
                    Type = "native",
                    Order = 3,
                    IsActive = true
                },
                new MenuItem
                {
                    Id = 4,
                    Title = "คอมมิชชั่น",
                    Icon = "commission_icon.png",
                    Url = "commissions",
                    Type = "native",
                    Order = 4,
                    IsActive = true
                }
            },
            Features = new AppFeatures
            {
                EnableWebView = true,
                EnableDashboard = true,
                EnableCommissions = true,
                EnableReferrals = true,
                EnableProfile = true,
                EnableNotifications = true,
                EnableDarkMode = true,
                CacheDurationMinutes = 60
            }
        };
    }
}

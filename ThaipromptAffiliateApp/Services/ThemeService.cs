using System.Net.Http.Json;
using System.Text.Json;
using ThaipromptAffiliateApp.Helpers;
using ThaipromptAffiliateApp.Models;

namespace ThaipromptAffiliateApp.Services
{
    public class ThemeService
    {
        private readonly HttpClient _httpClient;
        private CompleteThemeData _cachedTheme;
        private bool _isDarkMode;

        public ThemeService(HttpClient httpClient)
        {
            _httpClient = httpClient;
            _isDarkMode = Application.Current.RequestedTheme == AppTheme.Dark;

            // Subscribe to theme changes
            Application.Current.RequestedThemeChanged += (s, e) =>
            {
                _isDarkMode = e.RequestedTheme == AppTheme.Dark;
                if (_cachedTheme != null)
                {
                    ApplyTheme(_cachedTheme);
                }
            };
        }

        /// <summary>
        /// Fetch complete theme configuration from backend
        /// </summary>
        public async Task<CompleteThemeData> FetchThemeAsync()
        {
            try
            {
                var platform = DeviceInfo.Platform == DevicePlatform.Android ? "android" : "ios";
                var response = await _httpClient.GetFromJsonAsync<CompleteThemeResponse>(
                    $"{Constants.ApiBaseUrl}/v1/complete-theme?platform={platform}"
                );

                if (response?.Success == true && response.Data != null)
                {
                    _cachedTheme = response.Data;
                    await SaveThemeLocallyAsync(_cachedTheme);
                    return _cachedTheme;
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Error fetching theme: {ex.Message}");
            }

            // Return cached theme if available
            return _cachedTheme ?? await GetCachedThemeAsync();
        }

        /// <summary>
        /// Apply theme to the application
        /// </summary>
        public void ApplyTheme(CompleteThemeData themeData)
        {
            if (themeData?.Theme == null) return;

            var theme = themeData.Theme;
            var resources = Application.Current.Resources;

            // Primary Colors
            UpdateColor(resources, "Primary", theme.PrimaryColor);
            UpdateColor(resources, "PrimaryDark", theme.PrimaryDarkColor);
            UpdateColor(resources, "PrimaryLight", theme.PrimaryLightColor);

            // Secondary Colors
            UpdateColor(resources, "Secondary", theme.SecondaryColor);
            UpdateColor(resources, "SecondaryDark", theme.SecondaryDarkColor);
            UpdateColor(resources, "SecondaryLight", theme.SecondaryLightColor);

            // Background Colors
            var bgColor = _isDarkMode ? theme.BackgroundDarkColor : theme.BackgroundColor;
            UpdateColor(resources, "PageBackgroundColor", bgColor);

            var surfaceColor = _isDarkMode ? theme.SurfaceDarkColor : theme.SurfaceColor;
            UpdateColor(resources, "SurfaceColor", surfaceColor);

            var cardColor = _isDarkMode ? theme.CardBackgroundDark : theme.CardBackground;
            UpdateColor(resources, "CardBackgroundColor", cardColor);

            // Text Colors
            var primaryTextColor = _isDarkMode ? theme.TextPrimaryDarkColor : theme.TextPrimaryColor;
            UpdateColor(resources, "PrimaryTextColor", primaryTextColor);

            var secondaryTextColor = _isDarkMode ? theme.TextSecondaryDarkColor : theme.TextSecondaryColor;
            UpdateColor(resources, "SecondaryTextColor", secondaryTextColor);

            // Status Colors
            UpdateColor(resources, "SuccessColor", theme.SuccessColor);
            UpdateColor(resources, "WarningColor", theme.WarningColor);
            UpdateColor(resources, "ErrorColor", theme.ErrorColor);
            UpdateColor(resources, "InfoColor", theme.InfoColor);

            // Border Colors
            var borderColor = _isDarkMode ? theme.BorderDarkColor : theme.BorderColor;
            UpdateColor(resources, "BorderColor", borderColor);

            // Typography
            resources["FontSizeBase"] = (double)theme.FontSizeBase;
            resources["FontSizeSmall"] = (double)theme.FontSizeSmall;
            resources["FontSizeLarge"] = (double)theme.FontSizeLarge;
            resources["FontSizeXLarge"] = (double)theme.FontSizeXLarge;

            resources["Heading1Size"] = (double)theme.Heading1Size;
            resources["Heading2Size"] = (double)theme.Heading2Size;
            resources["Heading3Size"] = (double)theme.Heading3Size;
            resources["Heading4Size"] = (double)theme.Heading4Size;
            resources["BodySize"] = (double)theme.BodySize;
            resources["CaptionSize"] = (double)theme.CaptionSize;

            // Border Radius
            resources["BorderRadiusSmall"] = (double)theme.BorderRadiusSmall;
            resources["BorderRadiusMedium"] = (double)theme.BorderRadiusMedium;
            resources["BorderRadiusLarge"] = (double)theme.BorderRadiusLarge;
            resources["BorderRadiusXLarge"] = (double)theme.BorderRadiusXLarge;

            // Spacing
            resources["SpacingSmall"] = (double)theme.SpacingSmall;
            resources["SpacingMedium"] = (double)theme.SpacingMedium;
            resources["SpacingLarge"] = (double)theme.SpacingLarge;
            resources["SpacingXLarge"] = (double)theme.SpacingXLarge;

            // Icon Sizes
            resources["IconSizeSmall"] = (double)theme.IconSizeSmall;
            resources["IconSizeMedium"] = (double)theme.IconSizeMedium;
            resources["IconSizeLarge"] = (double)theme.IconSizeLarge;

            // Animation
            resources["AnimationDuration"] = (uint)theme.AnimationDuration;
            resources["TransitionFast"] = (uint)theme.TransitionFast;
            resources["TransitionBase"] = (uint)theme.TransitionBase;
            resources["TransitionSlow"] = (uint)theme.TransitionSlow;
        }

        /// <summary>
        /// Update color resource safely
        /// </summary>
        private void UpdateColor(ResourceDictionary resources, string key, string hexColor)
        {
            if (string.IsNullOrEmpty(hexColor)) return;

            try
            {
                resources[key] = Color.FromArgb(hexColor);
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Error updating color {key}: {ex.Message}");
            }
        }

        /// <summary>
        /// Save theme locally for offline use
        /// </summary>
        private async Task SaveThemeLocallyAsync(CompleteThemeData theme)
        {
            try
            {
                var json = JsonSerializer.Serialize(theme);
                await SecureStorage.SetAsync("cached_theme", json);
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Error saving theme: {ex.Message}");
            }
        }

        /// <summary>
        /// Get cached theme from local storage
        /// </summary>
        private async Task<CompleteThemeData> GetCachedThemeAsync()
        {
            try
            {
                var json = await SecureStorage.GetAsync("cached_theme");
                if (!string.IsNullOrEmpty(json))
                {
                    return JsonSerializer.Deserialize<CompleteThemeData>(json);
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Error loading cached theme: {ex.Message}");
            }

            return null;
        }

        /// <summary>
        /// Initialize theme - Load from cache first, then fetch from backend
        /// </summary>
        public async Task InitializeAsync()
        {
            // Load cached theme first for instant UI
            _cachedTheme = await GetCachedThemeAsync();
            if (_cachedTheme != null)
            {
                ApplyTheme(_cachedTheme);
            }

            // Fetch latest theme from backend
            var latestTheme = await FetchThemeAsync();
            if (latestTheme != null)
            {
                ApplyTheme(latestTheme);
            }
        }

        /// <summary>
        /// Get component style by component ID
        /// </summary>
        public ComponentStyle GetComponentStyle(string componentId)
        {
            if (_cachedTheme?.Components == null) return null;

            foreach (var componentList in _cachedTheme.Components.Values)
            {
                var component = componentList.FirstOrDefault(c => c.ComponentId == componentId);
                if (component != null) return component;
            }

            return null;
        }

        /// <summary>
        /// Get control section by section ID
        /// </summary>
        public ControlSection GetControlSection(string sectionId)
        {
            return _cachedTheme?.Sections?.FirstOrDefault(s => s.SectionId == sectionId);
        }

        /// <summary>
        /// Toggle dark mode
        /// </summary>
        public void ToggleDarkMode()
        {
            _isDarkMode = !_isDarkMode;
            Application.Current.UserAppTheme = _isDarkMode ? AppTheme.Dark : AppTheme.Light;
        }

        /// <summary>
        /// Get current theme data
        /// </summary>
        public CompleteThemeData GetCurrentTheme()
        {
            return _cachedTheme;
        }
    }
}

using System.Text.Json.Serialization;

namespace ThaipromptAffiliateApp.Models;

/// <summary>
/// App Configuration Model - รับการตั้งค่าจาก Control Panel
/// </summary>
public class AppConfiguration
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("app_name")]
    public string AppName { get; set; } = "Thaiprompt Affiliate";

    [JsonPropertyName("home_url")]
    public string HomeUrl { get; set; } = "https://thaiprompt.com";

    [JsonPropertyName("logo_url")]
    public string? LogoUrl { get; set; }

    [JsonPropertyName("splash_image_url")]
    public string? SplashImageUrl { get; set; }

    [JsonPropertyName("menu_items")]
    public List<MenuItem> MenuItems { get; set; } = new();

    [JsonPropertyName("theme")]
    public AppTheme Theme { get; set; } = new();

    [JsonPropertyName("features")]
    public AppFeatures Features { get; set; } = new();

    [JsonPropertyName("updated_at")]
    public DateTime UpdatedAt { get; set; }
}

/// <summary>
/// Menu Item Model - รายการเมนูที่ตั้งค่าได้
/// </summary>
public class MenuItem
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("title")]
    public string Title { get; set; } = string.Empty;

    [JsonPropertyName("icon")]
    public string? Icon { get; set; }

    [JsonPropertyName("url")]
    public string Url { get; set; } = string.Empty;

    [JsonPropertyName("type")]
    public string Type { get; set; } = "web"; // web, native, external

    [JsonPropertyName("order")]
    public int Order { get; set; }

    [JsonPropertyName("is_active")]
    public bool IsActive { get; set; } = true;

    [JsonPropertyName("badge")]
    public string? Badge { get; set; }

    // Helper property
    public string IconSource => !string.IsNullOrEmpty(Icon) ? Icon : "menu_icon.png";
}

/// <summary>
/// App Theme Model - Theme configuration
/// </summary>
public class AppTheme
{
    [JsonPropertyName("primary_color")]
    public string PrimaryColor { get; set; } = "#D4AF37"; // Gold

    [JsonPropertyName("secondary_color")]
    public string SecondaryColor { get; set; } = "#DC143C"; // Crimson Red

    [JsonPropertyName("accent_color")]
    public string AccentColor { get; set; } = "#1A1A1A"; // Dark Black

    [JsonPropertyName("background_color")]
    public string BackgroundColor { get; set; } = "#0A0A0A"; // Deep Black

    [JsonPropertyName("text_color")]
    public string TextColor { get; set; } = "#FFFFFF"; // White

    [JsonPropertyName("gradient_start")]
    public string GradientStart { get; set; } = "#D4AF37"; // Gold

    [JsonPropertyName("gradient_end")]
    public string GradientEnd { get; set; } = "#DC143C"; // Red

    [JsonPropertyName("card_background")]
    public string CardBackground { get; set; } = "#1A1A1A"; // Dark

    [JsonPropertyName("border_color")]
    public string BorderColor { get; set; } = "#D4AF37"; // Gold

    [JsonPropertyName("use_dark_mode")]
    public bool UseDarkMode { get; set; } = true;

    // Helper methods
    public Color GetPrimaryColor() => Color.FromArgb(PrimaryColor);
    public Color GetSecondaryColor() => Color.FromArgb(SecondaryColor);
    public Color GetAccentColor() => Color.FromArgb(AccentColor);
    public Color GetBackgroundColor() => Color.FromArgb(BackgroundColor);
    public Color GetTextColor() => Color.FromArgb(TextColor);
}

/// <summary>
/// App Features Model - ฟีเจอร์ที่เปิด/ปิดได้
/// </summary>
public class AppFeatures
{
    [JsonPropertyName("enable_webview")]
    public bool EnableWebView { get; set; } = true;

    [JsonPropertyName("enable_dashboard")]
    public bool EnableDashboard { get; set; } = true;

    [JsonPropertyName("enable_commissions")]
    public bool EnableCommissions { get; set; } = true;

    [JsonPropertyName("enable_referrals")]
    public bool EnableReferrals { get; set; } = true;

    [JsonPropertyName("enable_profile")]
    public bool EnableProfile { get; set; } = true;

    [JsonPropertyName("enable_notifications")]
    public bool EnableNotifications { get; set; } = true;

    [JsonPropertyName("enable_dark_mode")]
    public bool EnableDarkMode { get; set; } = true;

    [JsonPropertyName("cache_duration_minutes")]
    public int CacheDurationMinutes { get; set; } = 60;
}

/// <summary>
/// Configuration Response
/// </summary>
public class ConfigurationResponse
{
    [JsonPropertyName("success")]
    public bool Success { get; set; }

    [JsonPropertyName("data")]
    public AppConfiguration? Data { get; set; }

    [JsonPropertyName("message")]
    public string? Message { get; set; }
}

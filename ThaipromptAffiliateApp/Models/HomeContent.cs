namespace ThaipromptAffiliateApp.Models;

/// <summary>
/// Hero section content model for offline-capable homepage
/// </summary>
public class HomeContent
{
    public string WelcomeTitle { get; set; } = "ยินดีต้อนรับสู่";
    public string AppName { get; set; } = "Thaiprompt Affiliate";
    public string? LogoUrl { get; set; }
    public string Subtitle { get; set; } = "ระบบจัดการแอฟฟิลิเอทที่ทันสมัย";
    public string? HeroImageUrl { get; set; }
    public List<QuickAction> QuickActions { get; set; } = new();
    public List<FeaturedItem> FeaturedItems { get; set; } = new();
    public List<Announcement> Announcements { get; set; } = new();
}

/// <summary>
/// Quick action button on hero section
/// </summary>
public class QuickAction
{
    public string Title { get; set; } = "";
    public string Icon { get; set; } = "";
    public string Description { get; set; } = "";
    public string Color { get; set; } = "#D4AF37"; // Gold
    public string NavigationTarget { get; set; } = ""; // Page name or URL
    public bool IsEnabled { get; set; } = true;
}

/// <summary>
/// Featured content item on homepage
/// </summary>
public class FeaturedItem
{
    public string Title { get; set; } = "";
    public string Description { get; set; } = "";
    public string? ImageUrl { get; set; }
    public string Icon { get; set; } = "";
    public string? ActionUrl { get; set; }
    public string ActionText { get; set; } = "ดูเพิ่มเติม";
    public int Order { get; set; }
}

/// <summary>
/// Announcement banner
/// </summary>
public class Announcement
{
    public string Title { get; set; } = "";
    public string Message { get; set; } = "";
    public string Type { get; set; } = "info"; // info, warning, success
    public string Icon { get; set; } = "ℹ️";
    public DateTime? ExpiresAt { get; set; }
    public bool IsActive { get; set; } = true;

    public bool IsExpired => ExpiresAt.HasValue && ExpiresAt.Value < DateTime.Now;
    public bool ShouldDisplay => IsActive && !IsExpired;
}

/// <summary>
/// Stats card for quick overview
/// </summary>
public class StatCard
{
    public string Title { get; set; } = "";
    public string Value { get; set; } = "0";
    public string Icon { get; set; } = "";
    public string Color { get; set; } = "#D4AF37";
    public string? SubValue { get; set; }
    public string Trend { get; set; } = "neutral"; // up, down, neutral
}

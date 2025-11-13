using System.Text.Json.Serialization;

namespace ThaipromptAffiliateApp.Models
{
    public class ThemeConfig
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("theme_name")]
        public string ThemeName { get; set; }

        // Logo & Icons
        [JsonPropertyName("logo_url")]
        public string LogoUrl { get; set; }

        [JsonPropertyName("logo_dark_url")]
        public string LogoDarkUrl { get; set; }

        [JsonPropertyName("app_icon_url")]
        public string AppIconUrl { get; set; }

        [JsonPropertyName("splash_screen_url")]
        public string SplashScreenUrl { get; set; }

        [JsonPropertyName("splash_screen_dark_url")]
        public string SplashScreenDarkUrl { get; set; }

        // Primary Colors
        [JsonPropertyName("primary_color")]
        public string PrimaryColor { get; set; }

        [JsonPropertyName("primary_dark_color")]
        public string PrimaryDarkColor { get; set; }

        [JsonPropertyName("primary_light_color")]
        public string PrimaryLightColor { get; set; }

        [JsonPropertyName("button_primary_hover")]
        public string ButtonPrimaryHover { get; set; }

        [JsonPropertyName("button_primary_active")]
        public string ButtonPrimaryActive { get; set; }

        [JsonPropertyName("button_primary_disabled")]
        public string ButtonPrimaryDisabled { get; set; }

        // Secondary Colors
        [JsonPropertyName("secondary_color")]
        public string SecondaryColor { get; set; }

        [JsonPropertyName("secondary_dark_color")]
        public string SecondaryDarkColor { get; set; }

        [JsonPropertyName("secondary_light_color")]
        public string SecondaryLightColor { get; set; }

        // Background Colors
        [JsonPropertyName("background_color")]
        public string BackgroundColor { get; set; }

        [JsonPropertyName("background_dark_color")]
        public string BackgroundDarkColor { get; set; }

        [JsonPropertyName("surface_color")]
        public string SurfaceColor { get; set; }

        [JsonPropertyName("surface_dark_color")]
        public string SurfaceDarkColor { get; set; }

        // Card Colors
        [JsonPropertyName("card_background")]
        public string CardBackground { get; set; }

        [JsonPropertyName("card_background_dark")]
        public string CardBackgroundDark { get; set; }

        // Text Colors
        [JsonPropertyName("text_primary_color")]
        public string TextPrimaryColor { get; set; }

        [JsonPropertyName("text_primary_dark_color")]
        public string TextPrimaryDarkColor { get; set; }

        [JsonPropertyName("text_secondary_color")]
        public string TextSecondaryColor { get; set; }

        [JsonPropertyName("text_secondary_dark_color")]
        public string TextSecondaryDarkColor { get; set; }

        // Status Colors
        [JsonPropertyName("success_color")]
        public string SuccessColor { get; set; }

        [JsonPropertyName("warning_color")]
        public string WarningColor { get; set; }

        [JsonPropertyName("error_color")]
        public string ErrorColor { get; set; }

        [JsonPropertyName("info_color")]
        public string InfoColor { get; set; }

        // Border Colors
        [JsonPropertyName("border_color")]
        public string BorderColor { get; set; }

        [JsonPropertyName("border_dark_color")]
        public string BorderDarkColor { get; set; }

        // Typography
        [JsonPropertyName("font_family")]
        public string FontFamily { get; set; }

        [JsonPropertyName("font_family_en")]
        public string FontFamilyEn { get; set; }

        [JsonPropertyName("font_size_base")]
        public int FontSizeBase { get; set; }

        [JsonPropertyName("font_size_small")]
        public int FontSizeSmall { get; set; }

        [JsonPropertyName("font_size_large")]
        public int FontSizeLarge { get; set; }

        [JsonPropertyName("font_size_xlarge")]
        public int FontSizeXLarge { get; set; }

        // Heading Sizes
        [JsonPropertyName("heading1_size")]
        public int Heading1Size { get; set; }

        [JsonPropertyName("heading2_size")]
        public int Heading2Size { get; set; }

        [JsonPropertyName("heading3_size")]
        public int Heading3Size { get; set; }

        [JsonPropertyName("heading4_size")]
        public int Heading4Size { get; set; }

        [JsonPropertyName("body_size")]
        public int BodySize { get; set; }

        [JsonPropertyName("caption_size")]
        public int CaptionSize { get; set; }

        // Border Radius
        [JsonPropertyName("border_radius_small")]
        public int BorderRadiusSmall { get; set; }

        [JsonPropertyName("border_radius_medium")]
        public int BorderRadiusMedium { get; set; }

        [JsonPropertyName("border_radius_large")]
        public int BorderRadiusLarge { get; set; }

        [JsonPropertyName("border_radius_xlarge")]
        public int BorderRadiusXLarge { get; set; }

        // Spacing
        [JsonPropertyName("spacing_small")]
        public int SpacingSmall { get; set; }

        [JsonPropertyName("spacing_medium")]
        public int SpacingMedium { get; set; }

        [JsonPropertyName("spacing_large")]
        public int SpacingLarge { get; set; }

        [JsonPropertyName("spacing_xlarge")]
        public int SpacingXLarge { get; set; }

        // Icon Sizes
        [JsonPropertyName("icon_size_small")]
        public int IconSizeSmall { get; set; }

        [JsonPropertyName("icon_size_medium")]
        public int IconSizeMedium { get; set; }

        [JsonPropertyName("icon_size_large")]
        public int IconSizeLarge { get; set; }

        // Animation
        [JsonPropertyName("animation_duration")]
        public int AnimationDuration { get; set; }

        [JsonPropertyName("animation_curve")]
        public string AnimationCurve { get; set; }

        [JsonPropertyName("transition_fast")]
        public int TransitionFast { get; set; }

        [JsonPropertyName("transition_base")]
        public int TransitionBase { get; set; }

        [JsonPropertyName("transition_slow")]
        public int TransitionSlow { get; set; }

        // Dark Mode
        [JsonPropertyName("dark_mode_enabled")]
        public bool DarkModeEnabled { get; set; }

        [JsonPropertyName("dark_mode_default")]
        public bool DarkModeDefault { get; set; }

        [JsonPropertyName("is_active")]
        public bool IsActive { get; set; }
    }

    public class CompleteThemeResponse
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("data")]
        public CompleteThemeData Data { get; set; }
    }

    public class CompleteThemeData
    {
        [JsonPropertyName("theme")]
        public ThemeConfig Theme { get; set; }

        [JsonPropertyName("sections")]
        public List<ControlSection> Sections { get; set; }

        [JsonPropertyName("components")]
        public Dictionary<string, List<ComponentStyle>> Components { get; set; }
    }

    public class ControlSection
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("section_id")]
        public string SectionId { get; set; }

        [JsonPropertyName("section_name")]
        public string SectionName { get; set; }

        [JsonPropertyName("is_visible")]
        public bool IsVisible { get; set; }

        [JsonPropertyName("display_position")]
        public string DisplayPosition { get; set; }

        [JsonPropertyName("layout")]
        public LayoutConfig Layout { get; set; }

        [JsonPropertyName("style")]
        public SectionStyle Style { get; set; }

        [JsonPropertyName("spacing")]
        public SpacingConfig Spacing { get; set; }
    }

    public class ComponentStyle
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("component_id")]
        public string ComponentId { get; set; }

        [JsonPropertyName("component_name")]
        public string ComponentName { get; set; }

        [JsonPropertyName("component_type")]
        public string ComponentType { get; set; }

        [JsonPropertyName("size")]
        public SizeConfig Size { get; set; }

        [JsonPropertyName("spacing")]
        public SpacingConfig Spacing { get; set; }

        [JsonPropertyName("colors")]
        public ColorConfig Colors { get; set; }

        [JsonPropertyName("border")]
        public BorderConfig Border { get; set; }

        [JsonPropertyName("typography")]
        public TypographyConfig Typography { get; set; }
    }

    public class LayoutConfig
    {
        [JsonPropertyName("type")]
        public string Type { get; set; }

        [JsonPropertyName("min_height")]
        public int? MinHeight { get; set; }

        [JsonPropertyName("max_height")]
        public int? MaxHeight { get; set; }

        [JsonPropertyName("min_width")]
        public int? MinWidth { get; set; }

        [JsonPropertyName("max_width")]
        public int? MaxWidth { get; set; }
    }

    public class SectionStyle
    {
        [JsonPropertyName("background_color")]
        public string BackgroundColor { get; set; }

        [JsonPropertyName("background_dark_color")]
        public string BackgroundDarkColor { get; set; }

        [JsonPropertyName("border_color")]
        public string BorderColor { get; set; }

        [JsonPropertyName("border_width")]
        public int BorderWidth { get; set; }

        [JsonPropertyName("border_radius")]
        public int? BorderRadius { get; set; }

        [JsonPropertyName("elevation")]
        public int Elevation { get; set; }

        [JsonPropertyName("opacity")]
        public double Opacity { get; set; }
    }

    public class SpacingConfig
    {
        [JsonPropertyName("padding")]
        public EdgeInsets Padding { get; set; }

        [JsonPropertyName("margin")]
        public EdgeInsets Margin { get; set; }
    }

    public class EdgeInsets
    {
        [JsonPropertyName("top")]
        public int Top { get; set; }

        [JsonPropertyName("bottom")]
        public int Bottom { get; set; }

        [JsonPropertyName("left")]
        public int Left { get; set; }

        [JsonPropertyName("right")]
        public int Right { get; set; }
    }

    public class SizeConfig
    {
        [JsonPropertyName("min_height")]
        public int? MinHeight { get; set; }

        [JsonPropertyName("max_height")]
        public int? MaxHeight { get; set; }

        [JsonPropertyName("min_width")]
        public int? MinWidth { get; set; }

        [JsonPropertyName("max_width")]
        public int? MaxWidth { get; set; }

        [JsonPropertyName("fixed_height")]
        public int? FixedHeight { get; set; }

        [JsonPropertyName("fixed_width")]
        public int? FixedWidth { get; set; }
    }

    public class ColorConfig
    {
        [JsonPropertyName("background")]
        public string Background { get; set; }

        [JsonPropertyName("background_dark")]
        public string BackgroundDark { get; set; }

        [JsonPropertyName("text")]
        public string Text { get; set; }

        [JsonPropertyName("text_dark")]
        public string TextDark { get; set; }

        [JsonPropertyName("border")]
        public string Border { get; set; }

        [JsonPropertyName("border_dark")]
        public string BorderDark { get; set; }
    }

    public class BorderConfig
    {
        [JsonPropertyName("width")]
        public int Width { get; set; }

        [JsonPropertyName("corner_radius")]
        public CornerRadius CornerRadius { get; set; }
    }

    public class CornerRadius
    {
        [JsonPropertyName("top_left")]
        public int TopLeft { get; set; }

        [JsonPropertyName("top_right")]
        public int TopRight { get; set; }

        [JsonPropertyName("bottom_left")]
        public int BottomLeft { get; set; }

        [JsonPropertyName("bottom_right")]
        public int BottomRight { get; set; }
    }

    public class TypographyConfig
    {
        [JsonPropertyName("font_size")]
        public int? FontSize { get; set; }

        [JsonPropertyName("font_weight")]
        public string FontWeight { get; set; }

        [JsonPropertyName("line_height")]
        public double? LineHeight { get; set; }

        [JsonPropertyName("text_align")]
        public string TextAlign { get; set; }
    }
}

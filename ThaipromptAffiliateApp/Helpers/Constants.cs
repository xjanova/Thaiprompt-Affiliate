namespace ThaipromptAffiliateApp.Helpers
{
    /// <summary>
    /// Constants and configuration values for the application
    /// </summary>
    public static class Constants
    {
        // ============================================================
        // API Configuration
        // ============================================================

#if DEBUG
        // For Android Emulator (localhost = 10.0.2.2)
        public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1";

        // For iOS Simulator (localhost works directly)
        // public const string ApiBaseUrl = "http://localhost:8000/api/v1";

        // For Physical Device (use your computer's IP)
        // public const string ApiBaseUrl = "http://192.168.1.100:8000/api/v1";
#else
        // Production API
        public const string ApiBaseUrl = "https://your-domain.com/api/v1";
#endif

        // ============================================================
        // Secure Storage Keys
        // ============================================================

        public const string AuthTokenKey = "auth_token";
        public const string UserDataKey = "user_data";
        public const string UserIdKey = "user_id";
        public const string UserEmailKey = "user_email";
        public const string UserNameKey = "user_name";
        public const string ReferralCodeKey = "referral_code";

        // ============================================================
        // API Endpoints
        // ============================================================

        // Authentication
        public const string LoginEndpoint = "/login";
        public const string LogoutEndpoint = "/logout";
        public const string RegisterEndpoint = "/register";
        public const string MeEndpoint = "/me";

        // Dashboard
        public const string DashboardStatsEndpoint = "/dashboard/statistics";
        public const string DashboardChartsEndpoint = "/dashboard/charts";

        // Commissions
        public const string CommissionsEndpoint = "/dashboard/commissions";
        public const string CommissionDetailsEndpoint = "/dashboard/commissions/{0}";

        // Referrals
        public const string ReferralsEndpoint = "/dashboard/referrals";
        public const string ReferralLinkEndpoint = "/dashboard/referral-link";

        // Profile
        public const string ProfileEndpoint = "/profile";
        public const string UpdateProfileEndpoint = "/profile/update";
        public const string ChangePasswordEndpoint = "/profile/change-password";

        // Settings
        public const string SettingsEndpoint = "/settings";
        public const string ThemeEndpoint = "/settings/theme";

        // ============================================================
        // App Configuration
        // ============================================================

        public const string AppName = "Thaiprompt Affiliate";
        public const string AppVersion = "1.0.0";

        // Pagination
        public const int DefaultPageSize = 20;
        public const int MaxPageSize = 100;

        // Timeouts (in seconds)
        public const int ApiTimeout = 30;
        public const int FileUploadTimeout = 120;

        // Cache duration (in minutes)
        public const int CacheDuration = 5;

        // ============================================================
        // UI Constants
        // ============================================================

        // Colors (can be used as fallback)
        public const string PrimaryColor = "#3B82F6";
        public const string SecondaryColor = "#10B981";
        public const string AccentColor = "#8B5CF6";
        public const string ErrorColor = "#EF4444";
        public const string WarningColor = "#F59E0B";
        public const string SuccessColor = "#10B981";

        // Animation durations (in milliseconds)
        public const uint ShortAnimationDuration = 150;
        public const uint MediumAnimationDuration = 300;
        public const uint LongAnimationDuration = 500;

        // ============================================================
        // Validation
        // ============================================================

        public const int MinPasswordLength = 8;
        public const int MaxPasswordLength = 50;
        public const int MinUsernameLength = 3;
        public const int MaxUsernameLength = 50;

        // Email regex pattern
        public const string EmailPattern = @"^[^@\s]+@[^@\s]+\.[^@\s]+$";

        // ============================================================
        // Error Messages (Thai)
        // ============================================================

        public const string ErrorNetworkTitle = "ข้อผิดพลาดเครือข่าย";
        public const string ErrorNetworkMessage = "ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้ กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต";

        public const string ErrorServerTitle = "ข้อผิดพลาดเซิร์ฟเวอร์";
        public const string ErrorServerMessage = "เกิดข้อผิดพลาดจากเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง";

        public const string ErrorUnauthorizedTitle = "ไม่ได้รับอนุญาต";
        public const string ErrorUnauthorizedMessage = "กรุณาเข้าสู่ระบบใหม่อีกครั้ง";

        public const string ErrorValidationTitle = "ข้อมูลไม่ถูกต้อง";
        public const string ErrorValidationMessage = "กรุณาตรวจสอบข้อมูลที่กรอกและลองใหม่อีกครั้ง";

        // ============================================================
        // Success Messages (Thai)
        // ============================================================

        public const string SuccessLoginTitle = "เข้าสู่ระบบสำเร็จ";
        public const string SuccessLoginMessage = "ยินดีต้อนรับ";

        public const string SuccessLogoutTitle = "ออกจากระบบสำเร็จ";
        public const string SuccessLogoutMessage = "แล้วพบกันใหม่";

        public const string SuccessUpdateTitle = "อัปเดตสำเร็จ";
        public const string SuccessUpdateMessage = "ข้อมูลของคุณได้รับการอัปเดตแล้ว";

        // ============================================================
        // Helper Methods
        // ============================================================

        /// <summary>
        /// Build full API URL from endpoint
        /// </summary>
        public static string GetApiUrl(string endpoint)
        {
            if (endpoint.StartsWith("/"))
            {
                return ApiBaseUrl + endpoint;
            }
            return $"{ApiBaseUrl}/{endpoint}";
        }

        /// <summary>
        /// Format currency (Thai Baht)
        /// </summary>
        public static string FormatCurrency(decimal amount)
        {
            return $"฿{amount:N2}";
        }

        /// <summary>
        /// Validate email format
        /// </summary>
        public static bool IsValidEmail(string email)
        {
            if (string.IsNullOrWhiteSpace(email))
                return false;

            return System.Text.RegularExpressions.Regex.IsMatch(
                email,
                EmailPattern,
                System.Text.RegularExpressions.RegexOptions.IgnoreCase);
        }

        /// <summary>
        /// Validate password strength
        /// </summary>
        public static bool IsValidPassword(string password)
        {
            if (string.IsNullOrWhiteSpace(password))
                return false;

            return password.Length >= MinPasswordLength &&
                   password.Length <= MaxPasswordLength;
        }
    }
}

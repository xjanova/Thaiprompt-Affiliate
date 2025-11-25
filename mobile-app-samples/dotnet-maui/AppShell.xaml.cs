namespace ThaipromptAffiliate;

/// <summary>
/// AppShell - ระบบ Navigation หลักของแอพ
///
/// หน้าที่:
/// - ลงทะเบียน Routes ทั้งหมดของแอพ
/// - จัดการการนำทางระหว่างหน้า
/// - ตั้งค่า Shell properties
/// </summary>
public partial class AppShell : Shell
{
    /// <summary>
    /// Constructor - ลงทะเบียน routes ทั้งหมด
    /// </summary>
    public AppShell()
    {
        InitializeComponent();

        RegisterRoutes();
    }

    /// <summary>
    /// ลงทะเบียน Routes ทั้งหมดของแอพ
    ///
    /// Routes หลัก:
    /// - splash: หน้าเปิดแอพ
    /// - hub: หน้าเลือกบริการ
    /// - login: หน้าเข้าสู่ระบบ
    /// - register: หน้าสมัครสมาชิก
    /// - dashboard: หน้าหลักหลัง login
    ///
    /// Routes บริการ:
    /// - shopping: ช้อปปิ้ง & บริการ
    /// - wallet: กระเป๋าเงิน
    /// - invest: ลงทุน & Trade
    /// - mlm: MLM & Affiliate
    /// - rider: เป็นไรเดอร์
    /// - aibot: AI Bot
    /// - academy: เรียนรู้
    /// - gaming: Gaming & Rewards
    /// </summary>
    private void RegisterRoutes()
    {
        // หน้าหลัก
        Routing.RegisterRoute("splash", typeof(Views.SplashPage));
        Routing.RegisterRoute("hub", typeof(Views.HubSelectionPage));
        Routing.RegisterRoute("login", typeof(Views.LoginPage));
        Routing.RegisterRoute("dashboard", typeof(Views.DashboardPage));

        // หน้าบริการต่างๆ (จะพัฒนาในอนาคต)
        // Routing.RegisterRoute("shopping", typeof(Views.ShoppingPage));
        // Routing.RegisterRoute("wallet", typeof(Views.WalletPage));
        // Routing.RegisterRoute("invest", typeof(Views.InvestPage));
        // Routing.RegisterRoute("mlm", typeof(Views.MlmPage));
        // Routing.RegisterRoute("rider", typeof(Views.RiderPage));
        // Routing.RegisterRoute("aibot", typeof(Views.AiBotPage));
        // Routing.RegisterRoute("academy", typeof(Views.AcademyPage));
        // Routing.RegisterRoute("gaming", typeof(Views.GamingPage));

        // หน้าอื่นๆ
        // Routing.RegisterRoute("register", typeof(Views.RegisterPage));
        // Routing.RegisterRoute("forgotpassword", typeof(Views.ForgotPasswordPage));
        // Routing.RegisterRoute("profile", typeof(Views.ProfilePage));
        // Routing.RegisterRoute("settings", typeof(Views.SettingsPage));
        // Routing.RegisterRoute("commissions", typeof(Views.CommissionsPage));
        // Routing.RegisterRoute("referrals", typeof(Views.ReferralsPage));
    }
}

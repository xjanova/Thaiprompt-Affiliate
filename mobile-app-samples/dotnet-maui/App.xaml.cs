namespace ThaipromptAffiliate;

/// <summary>
/// คลาสหลักของแอพพลิเคชัน Thaiprompt Ultra App
///
/// หน้าที่:
/// - กำหนด MainPage เริ่มต้น (AppShell)
/// - จัดการ Application Lifecycle
/// - โหลด Theme และ Resources
/// </summary>
public partial class App : Application
{
    /// <summary>
    /// Constructor - สร้างแอพพลิเคชัน
    /// </summary>
    public App()
    {
        InitializeComponent();

        // กำหนด MainPage เป็น AppShell สำหรับ navigation
        MainPage = new AppShell();
    }

    /// <summary>
    /// เรียกเมื่อแอพเริ่มทำงาน
    /// </summary>
    protected override void OnStart()
    {
        base.OnStart();

        // ตั้งค่า theme ตาม system preference
        SetAppTheme();
    }

    /// <summary>
    /// เรียกเมื่อแอพกลับมาจาก background
    /// </summary>
    protected override void OnResume()
    {
        base.OnResume();
    }

    /// <summary>
    /// เรียกเมื่อแอพเข้าสู่ background
    /// </summary>
    protected override void OnSleep()
    {
        base.OnSleep();
    }

    /// <summary>
    /// ตั้งค่า theme ตามการตั้งค่าของระบบ
    /// </summary>
    private void SetAppTheme()
    {
        // อ่านค่า theme จาก Preferences (ถ้ามี)
        var savedTheme = Preferences.Get("app_theme", "system");

        UserAppTheme = savedTheme switch
        {
            "dark" => AppTheme.Dark,
            "light" => AppTheme.Light,
            _ => AppTheme.Unspecified // ใช้ตามระบบ
        };
    }

    /// <summary>
    /// เปลี่ยน theme ของแอพ
    /// </summary>
    /// <param name="theme">theme ที่ต้องการ (dark, light, system)</param>
    public void ChangeTheme(string theme)
    {
        Preferences.Set("app_theme", theme);
        SetAppTheme();
    }
}

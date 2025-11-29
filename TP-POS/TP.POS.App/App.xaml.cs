using TP.POS.Infrastructure.Data;

namespace TP.POS.App;

/// <summary>
/// Main Application Class
/// </summary>
public partial class App : Application
{
    private readonly PosDatabase _database;

    public App(PosDatabase database)
    {
        InitializeComponent();
        _database = database;

        // เริ่มต้น Database
        Task.Run(async () => await _database.InitializeAsync());

        MainPage = new AppShell();
    }

    /// <summary>
    /// เมื่อเริ่มแอป
    /// </summary>
    protected override void OnStart()
    {
        base.OnStart();
    }

    /// <summary>
    /// เมื่อ sleep
    /// </summary>
    protected override void OnSleep()
    {
        base.OnSleep();
    }

    /// <summary>
    /// เมื่อกลับมา
    /// </summary>
    protected override void OnResume()
    {
        base.OnResume();
    }
}

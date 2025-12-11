namespace TP.POS.App.WinUI;

/// <summary>
/// WinUI App class สำหรับ Windows platform
/// </summary>
public partial class App : Microsoft.Maui.MauiWinUIApplication
{
    /// <summary>
    /// Initializes the singleton application object.
    /// </summary>
    public App()
    {
        this.InitializeComponent();
    }

    /// <summary>
    /// สร้าง MauiApp
    /// </summary>
    protected override MauiApp CreateMauiApp() => MauiProgram.CreateMauiApp();
}

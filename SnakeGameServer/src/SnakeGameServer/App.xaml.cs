using System.Windows;

namespace SnakeGameServer;

/// <summary>
/// Application entry point
/// </summary>
public partial class App : Application
{
    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        // Global exception handling
        DispatcherUnhandledException += (_, args) =>
        {
            MessageBox.Show(
                $"An error occurred:\n{args.Exception.Message}",
                "Snake.io Server Error",
                MessageBoxButton.OK,
                MessageBoxImage.Error);
            args.Handled = true;
        };
    }
}

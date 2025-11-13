# 🛠️ Development Guide - Thaiprompt Affiliate Control App

คู่มือสำหรับนักพัฒนา

## 📋 Table of Contents

- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Building the App](#building-the-app)
- [Running the App](#running-the-app)
- [Debugging](#debugging)
- [Code Style](#code-style)
- [Testing](#testing)
- [Common Tasks](#common-tasks)

## 🚀 Development Setup

### Prerequisites

1. **Visual Studio 2022** (v17.8 or later)
   - Workload: .NET Multi-platform App UI development
   - Workload: Mobile development with .NET

2. **.NET 8.0 SDK**
   ```bash
   dotnet --version
   # Should show 8.0.x or later
   ```

3. **MAUI Workload**
   ```bash
   dotnet workload install maui
   ```

### Quick Setup

Run the setup script:
```bash
cd ThaipromptAffiliateApp
chmod +x setup-dev.sh
./setup-dev.sh
```

Or manually:
```bash
# Restore packages
dotnet restore

# Check workloads
dotnet workload list

# Build
dotnet build
```

## 📁 Project Structure

```
ThaipromptAffiliateApp/
├── Models/                 # Data models
│   ├── User.cs            # User model
│   ├── Dashboard.cs       # Dashboard & Commission models
│   ├── Referral.cs        # Referral models
│   └── ThemeConfig.cs     # Theme configuration
├── Services/              # Business logic
│   ├── ApiService.cs      # API client
│   └── ThemeService.cs    # Theme management
├── ViewModels/            # MVVM ViewModels
│   ├── BaseViewModel.cs   # Base class
│   ├── LoginViewModel.cs  # Login logic
│   ├── DashboardViewModel.cs
│   ├── CommissionsViewModel.cs
│   ├── ReferralsViewModel.cs
│   └── ProfileViewModel.cs
├── Views/                 # UI (XAML)
│   ├── LoginPage.xaml
│   ├── DashboardPage.xaml
│   ├── CommissionsPage.xaml
│   ├── ReferralsPage.xaml
│   └── ProfilePage.xaml
├── Helpers/               # Utilities
│   ├── Constants.cs       # App constants
│   └── AppHelpers.cs      # Helper methods
├── Converters/            # XAML Converters
│   └── ValueConverters.cs
├── Resources/             # Assets
│   ├── Styles/           # XAML styles
│   ├── Images/           # Images
│   ├── Fonts/            # Fonts
│   └── AppIcon/          # App icons
└── Platforms/             # Platform-specific
    ├── Android/
    ├── iOS/
    └── Windows/
```

## 🔨 Building the App

### Using Scripts

```bash
# Build for specific platform
./build.sh android       # Android
./build.sh ios          # iOS
./build.sh windows      # Windows
./build.sh all          # All platforms

# With configuration
./build.sh android Release
```

### Using dotnet CLI

```bash
# Android
dotnet build -f net8.0-android -c Debug

# iOS
dotnet build -f net8.0-ios -c Debug

# Windows
dotnet build -f net8.0-windows10.0.19041.0 -c Debug

# All platforms
dotnet build
```

### Using Visual Studio

1. Open `ThaipromptAffiliateApp.sln`
2. Select target framework from dropdown (e.g., `net8.0-android`)
3. Press **F6** or **Build > Build Solution**

## 🏃 Running the App

### Android

**Using Script:**
```bash
./run-android.sh
```

**Using dotnet CLI:**
```bash
dotnet run -f net8.0-android
```

**Using Visual Studio:**
1. Select Android Emulator or Device
2. Press **F5** or click **Run**

### iOS (macOS only)

```bash
dotnet run -f net8.0-ios
```

### Windows

```bash
dotnet run -f net8.0-windows10.0.19041.0
```

## 🐛 Debugging

### Visual Studio

1. Set breakpoints by clicking on line numbers
2. Press **F5** to start debugging
3. Use Debug windows:
   - **Locals** - View variables
   - **Call Stack** - View call stack
   - **Output** - View logs

### Logging

Add logging in your code:
```csharp
Console.WriteLine($"Debug: {variable}");
Debug.WriteLine($"Debug: {variable}");
```

View logs:
```bash
# Android
adb logcat | grep ThaipromptAffiliate

# iOS (macOS)
xcrun simctl spawn booted log stream --predicate 'process contains "ThaipromptAffiliate"'
```

## 🎨 Code Style

### Naming Conventions

```csharp
// Classes: PascalCase
public class MyClass { }

// Methods: PascalCase
public void MyMethod() { }

// Properties: PascalCase
public string MyProperty { get; set; }

// Private fields: _camelCase
private string _myField;

// Constants: PascalCase
public const string MyConstant = "value";

// Async methods: suffix with Async
public async Task LoadDataAsync() { }
```

### MVVM Pattern

```csharp
// ViewModel
public class MyViewModel : BaseViewModel
{
    private string _data;
    public string Data
    {
        get => _data;
        set => SetProperty(ref _data, value);
    }

    public ICommand LoadCommand { get; }

    public MyViewModel()
    {
        LoadCommand = new AsyncRelayCommand(LoadAsync);
    }

    private async Task LoadAsync()
    {
        if (IsBusy) return;

        try
        {
            IsBusy = true;
            // Load data
        }
        finally
        {
            IsBusy = false;
        }
    }
}
```

### Async/Await

```csharp
// Good
public async Task<User> GetUserAsync()
{
    var response = await _apiService.GetUserAsync();
    return response;
}

// Bad - blocking call
public User GetUser()
{
    var response = _apiService.GetUserAsync().Result;
    return response;
}
```

## 🧪 Testing

### Unit Tests

Create test project:
```bash
dotnet new xunit -n ThaipromptAffiliateApp.Tests
```

Example test:
```csharp
public class LoginViewModelTests
{
    [Fact]
    public async Task Login_WithValidCredentials_ShouldSucceed()
    {
        // Arrange
        var apiService = new MockApiService();
        var viewModel = new LoginViewModel(apiService);

        // Act
        viewModel.Email = "test@example.com";
        viewModel.Password = "password123";
        await viewModel.LoginCommand.ExecuteAsync(null);

        // Assert
        Assert.True(viewModel.IsLoggedIn);
    }
}
```

Run tests:
```bash
dotnet test
```

## 📝 Common Tasks

### Adding a New Page

1. **Create XAML in Views/**
```xml
<?xml version="1.0" encoding="utf-8" ?>
<ContentPage xmlns="http://schemas.microsoft.com/dotnet/2021/maui"
             xmlns:x="http://schemas.microsoft.com/winfx/2009/xaml"
             x:Class="ThaipromptAffiliateApp.Views.MyPage"
             Title="My Page">
    <StackLayout>
        <Label Text="Hello World!" />
    </StackLayout>
</ContentPage>
```

2. **Create ViewModel**
```csharp
public class MyViewModel : BaseViewModel
{
    public MyViewModel()
    {
        Title = "My Page";
    }
}
```

3. **Register in MauiProgram.cs**
```csharp
builder.Services.AddTransient<MyViewModel>();
builder.Services.AddTransient<MyPage>();
```

4. **Add to Shell (optional)**
```xml
<FlyoutItem Title="My Page">
    <ShellContent Route="mypage"
                  ContentTemplate="{DataTemplate local:MyPage}" />
</FlyoutItem>
```

### Adding API Endpoint

1. **Add method in ApiService**
```csharp
public async Task<MyData?> GetMyDataAsync()
{
    await EnsureAuthenticatedAsync();
    var response = await _httpClient.GetFromJsonAsync<ApiResponse<MyData>>(
        "/my-endpoint");
    return response?.Data;
}
```

2. **Update IApiService interface**
```csharp
Task<MyData?> GetMyDataAsync();
```

3. **Use in ViewModel**
```csharp
var data = await _apiService.GetMyDataAsync();
```

### Changing Theme Colors

Edit `Resources/Styles/Colors.xaml`:
```xml
<Color x:Key="Primary">#YOUR_COLOR</Color>
```

### Adding Fonts

1. Place font files in `Resources/Fonts/`
2. Register in `MauiProgram.cs`:
```csharp
.ConfigureFonts(fonts =>
{
    fonts.AddFont("YourFont.ttf", "YourFontName");
})
```

3. Use in XAML:
```xml
<Label FontFamily="YourFontName" />
```

## 🔄 Clean Build

Clean all build artifacts:
```bash
./clean.sh
```

Or manually:
```bash
dotnet clean
rm -rf bin obj
```

## 📦 NuGet Packages

Update packages:
```bash
dotnet list package --outdated
dotnet add package PackageName --version x.x.x
```

Restore packages:
```bash
dotnet restore
```

## 🚀 Deployment

### Android

Generate signed APK/AAB:
```bash
dotnet publish -f net8.0-android -c Release
```

### iOS

Archive for App Store:
```bash
dotnet publish -f net8.0-ios -c Release
```

## 💡 Tips & Tricks

1. **Hot Reload**: Press **Alt+F10** (Windows) or **Cmd+\\** (Mac) to enable XAML Hot Reload

2. **IntelliSense**: Press **Ctrl+Space** for code completion

3. **Find in Files**: **Ctrl+Shift+F** to search entire project

4. **Format Code**: **Ctrl+K, Ctrl+D** to format document

5. **Quick Actions**: **Ctrl+.** for quick fixes and refactoring

## 🆘 Troubleshooting

### Build Errors

```bash
# Clean and rebuild
./clean.sh
dotnet restore
dotnet build
```

### Android Emulator Issues

```bash
# List devices
adb devices

# Restart ADB
adb kill-server
adb start-server
```

### iOS Simulator Issues

```bash
# Reset simulator
xcrun simctl erase all
```

## 📚 Resources

- [.NET MAUI Docs](https://docs.microsoft.com/dotnet/maui/)
- [MVVM Toolkit](https://learn.microsoft.com/windows/communitytoolkit/mvvm/)
- [XAML Guide](https://docs.microsoft.com/dotnet/maui/xaml/)

---

**Happy Coding! 💻**

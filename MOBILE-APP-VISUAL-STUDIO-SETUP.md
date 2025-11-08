# การพัฒนา Mobile App ด้วย Visual Studio
## Thai Prompt Affiliate Marketing Platform

## สารบัญ
- [ภาพรวม](#ภาพรวม)
- [ข้อกำหนดเบื้องต้น](#ข้อกำหนดเบื้องต้น)
- [การติดตั้ง Visual Studio](#การติดตั้ง-visual-studio)
- [สร้างโครงการ .NET MAUI](#สร้างโครงการ-net-maui)
- [โครงสร้างโปรเจค](#โครงสร้างโปรเจค)
- [การตั้งค่า API Integration](#การตั้งค่า-api-integration)
- [การทดสอบ](#การทดสอบ)
- [การ Deploy](#การ-deploy)

---

## ภาพรวม

เอกสารนี้จะแนะนำการพัฒนา Mobile Application สำหรับ **Thai Prompt Affiliate Platform** โดยใช้ **.NET MAUI** (Multi-platform App UI) ซึ่งเป็น framework ล่าสุดจาก Microsoft สำหรับการพัฒนาแอปพลิเคชันข้ามแพลตฟอร์ม

### เทคโนโลยีที่ใช้
- **.NET MAUI** - Cross-platform UI framework
- **C#** - ภาษาโปรแกรม
- **Visual Studio 2022** - IDE
- **Laravel Sanctum** - Authentication (Backend)
- **REST API** - การสื่อสารกับ Backend

### Platform ที่รองรับ
- ✅ Android 5.0 (API 21) ขึ้นไป
- ✅ iOS 11.0 ขึ้นไป
- ✅ Windows 10/11 (เพิ่มเติม)
- ✅ macOS (เพิ่มเติม)

---

## ข้อกำหนดเบื้องต้น

### ฮาร์ดแวร์
- **Windows**:
  - Windows 10 version 1909 ขึ้นไป หรือ Windows 11
  - RAM: 8 GB ขึ้นไป (แนะนำ 16 GB)
  - Storage: 20 GB ว่างสำหรับ Visual Studio และ SDK
  - Processor: 1.8 GHz หรือเร็วกว่า

- **macOS** (สำหรับพัฒนา iOS):
  - macOS 12 Monterey ขึ้นไป
  - Xcode 14 ขึ้นไป

### ซอฟต์แวร์
1. **Visual Studio 2022** (version 17.3 ขึ้นไ)
2. **.NET 7.0 SDK** หรือใหม่กว่า
3. **Android SDK** (จะติดตั้งผ่าน Visual Studio)
4. **Xcode** (สำหรับ iOS - ต้องใช้ macOS)
5. **Git** สำหรับ version control

---

## การติดตั้ง Visual Studio

### ขั้นตอนที่ 1: ดาวน์โหลด Visual Studio 2022

1. ไปที่ [Visual Studio Downloads](https://visualstudio.microsoft.com/downloads/)
2. เลือก **Visual Studio 2022 Community** (ฟรี) หรือ Professional/Enterprise
3. ดาวน์โหลด Installer

### ขั้นตอนที่ 2: ติดตั้ง Workloads

เมื่อเปิด Visual Studio Installer ให้เลือก Workloads ดังนี้:

#### สำหรับ .NET MAUI Development:

1. ✅ **.NET Multi-platform App UI development**
   - ครอบคลุม: .NET MAUI templates, Android SDK, iOS support

2. ✅ **Mobile development with .NET**
   - Xamarin และ Android emulators

3. ✅ **ASP.NET and web development** (ถ้าต้องการพัฒนา API)

#### Individual Components ที่ควรเพิ่ม:

- ✅ Android SDK setup
- ✅ Android Emulator
- ✅ Intel Hardware Accelerated Execution Manager (HAXM)
- ✅ .NET 7.0 Runtime หรือใหม่กว่า

### ขั้นตอนที่ 3: ติดตั้ง Android SDK

Visual Studio จะติดตั้ง Android SDK อัตโนมัติ แต่คุณสามารถจัดการเพิ่มเติมได้ที่:

```
Tools > Android > Android SDK Manager
```

**Android SDK ที่แนะนำ:**
- Android 13.0 (API 33) - เวอร์ชันล่าสุด
- Android 11.0 (API 30) - รองรับเครื่องส่วนใหญ่
- Android 5.0 (API 21) - Minimum version

### ขั้นตอนที่ 4: ตั้งค่า iOS Development (สำหรับ macOS)

หากต้องการพัฒนาสำหรับ iOS:

1. ติดตั้ง **Xcode** จาก Mac App Store
2. เปิด Xcode และติดตั้ง Additional Components
3. ยอมรับ License Agreement:
```bash
sudo xcodebuild -license accept
```

4. ตั้งค่า Xcode Command Line Tools:
```bash
sudo xcode-select --switch /Applications/Xcode.app
```

### ขั้นตอนที่ 5: ตรวจสอบการติดตั้ง

เปิด Terminal/Command Prompt และรันคำสั่ง:

```bash
dotnet --version
# ควรแสดง 7.0.x หรือใหม่กว่า

dotnet workload list
# ควรเห็น maui, android, ios
```

---

## สร้างโครงการ .NET MAUI

### วิธีที่ 1: สร้างผ่าน Visual Studio (แนะนำ)

1. เปิด Visual Studio 2022
2. คลิก **Create a new project**
3. ค้นหา **".NET MAUI App"**
4. เลือก **.NET MAUI App** template
5. ตั้งค่าโครงการ:
   - **Project name**: `ThaipromptAffiliate`
   - **Location**: เลือกตำแหน่งที่ต้องการ
   - **Solution name**: `ThaipromptAffiliate`
   - **Framework**: .NET 7.0 หรือใหม่กว่า
6. คลิก **Create**

### วิธีที่ 2: สร้างผ่าน CLI

```bash
# สร้างโปรเจค .NET MAUI
dotnet new maui -n ThaipromptAffiliate

# เข้าไปในโฟลเดอร์
cd ThaipromptAffiliate

# เปิดด้วย Visual Studio
start ThaipromptAffiliate.sln
```

---

## โครงสร้างโปรเจค

หลังจากสร้างโปรเจค คุณจะเห็นโครงสร้างดังนี้:

```
ThaipromptAffiliate/
├── Platforms/              # Platform-specific code
│   ├── Android/           # Android specific
│   ├── iOS/              # iOS specific
│   ├── Windows/          # Windows specific
│   └── MacCatalyst/      # macOS specific
├── Resources/             # Shared resources
│   ├── Images/           # รูปภาพ
│   ├── Fonts/            # Fonts
│   ├── Styles/           # XAML styles
│   └── AppIcon/          # App icons
├── Models/               # Data models
├── ViewModels/           # MVVM ViewModels
├── Views/                # UI Pages
├── Services/             # API และ Business logic
├── App.xaml              # Application definition
├── AppShell.xaml         # Navigation shell
└── MauiProgram.cs        # App startup
```

### โครงสร้างที่แนะนำสำหรับโปรเจคนี้:

```
ThaipromptAffiliate/
├── Models/
│   ├── User.cs
│   ├── Commission.cs
│   ├── Referral.cs
│   └── DashboardStats.cs
├── Services/
│   ├── IApiService.cs
│   ├── ApiService.cs
│   ├── IAuthService.cs
│   └── AuthService.cs
├── ViewModels/
│   ├── BaseViewModel.cs
│   ├── LoginViewModel.cs
│   ├── DashboardViewModel.cs
│   ├── CommissionsViewModel.cs
│   └── ProfileViewModel.cs
├── Views/
│   ├── LoginPage.xaml
│   ├── DashboardPage.xaml
│   ├── CommissionsPage.xaml
│   ├── ReferralsPage.xaml
│   └── ProfilePage.xaml
├── Helpers/
│   ├── Constants.cs
│   └── SecureStorageHelper.cs
└── Converters/
    └── StatusToColorConverter.cs
```

---

## การตั้งค่า API Integration

### 1. ติดตั้ง NuGet Packages

เปิด Package Manager Console (`Tools > NuGet Package Manager > Package Manager Console`) และรัน:

```powershell
# HTTP Client
Install-Package System.Net.Http.Json

# JSON Serialization
Install-Package Newtonsoft.Json

# MVVM Toolkit
Install-Package CommunityToolkit.Mvvm

# HTTP Extensions
Install-Package Microsoft.Extensions.Http
```

### 2. สร้าง Constants และ Configuration

สร้างไฟล์ `Helpers/Constants.cs`:

```csharp
namespace ThaipromptAffiliate.Helpers
{
    public static class Constants
    {
        // API Configuration
#if DEBUG
        public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1"; // Android Emulator
        // public const string ApiBaseUrl = "http://localhost:8000/api/v1"; // iOS Simulator
#else
        public const string ApiBaseUrl = "https://your-domain.com/api/v1"; // Production
#endif

        // Storage Keys
        public const string AuthTokenKey = "auth_token";
        public const string UserDataKey = "user_data";

        // API Endpoints
        public const string LoginEndpoint = "/login";
        public const string LogoutEndpoint = "/logout";
        public const string MeEndpoint = "/me";
        public const string DashboardStatsEndpoint = "/dashboard/statistics";
        public const string CommissionsEndpoint = "/dashboard/commissions";
        public const string ReferralsEndpoint = "/dashboard/referrals";
    }
}
```

### 3. สร้าง Models

สร้างไฟล์ `Models/User.cs`:

```csharp
using System.Text.Json.Serialization;

namespace ThaipromptAffiliate.Models
{
    public class User
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("name")]
        public string Name { get; set; }

        [JsonPropertyName("email")]
        public string Email { get; set; }

        [JsonPropertyName("role")]
        public string Role { get; set; }

        [JsonPropertyName("permissions")]
        public List<string> Permissions { get; set; }
    }

    public class LoginResponse
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("message")]
        public string Message { get; set; }

        [JsonPropertyName("data")]
        public LoginData Data { get; set; }
    }

    public class LoginData
    {
        [JsonPropertyName("user")]
        public User User { get; set; }

        [JsonPropertyName("token")]
        public string Token { get; set; }
    }
}
```

สร้างไฟล์ `Models/DashboardStats.cs`:

```csharp
using System.Text.Json.Serialization;

namespace ThaipromptAffiliate.Models
{
    public class DashboardStats
    {
        [JsonPropertyName("total_earnings")]
        public decimal TotalEarnings { get; set; }

        [JsonPropertyName("pending_earnings")]
        public decimal PendingEarnings { get; set; }

        [JsonPropertyName("total_referrals")]
        public int TotalReferrals { get; set; }

        [JsonPropertyName("recent_commissions")]
        public List<Commission> RecentCommissions { get; set; }
    }

    public class Commission
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("amount")]
        public decimal Amount { get; set; }

        [JsonPropertyName("status")]
        public string Status { get; set; }

        [JsonPropertyName("description")]
        public string Description { get; set; }

        [JsonPropertyName("created_at")]
        public DateTime CreatedAt { get; set; }
    }
}
```

### 4. สร้าง API Service

สร้างไฟล์ `Services/IApiService.cs`:

```csharp
using ThaipromptAffiliate.Models;

namespace ThaipromptAffiliate.Services
{
    public interface IApiService
    {
        Task<LoginResponse> LoginAsync(string email, string password);
        Task<bool> LogoutAsync();
        Task<User> GetCurrentUserAsync();
        Task<DashboardStats> GetDashboardStatsAsync();
        Task<List<Commission>> GetCommissionsAsync(int page = 1);
    }
}
```

สร้างไฟล์ `Services/ApiService.cs`:

```csharp
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using ThaipromptAffiliate.Helpers;
using ThaipromptAffiliate.Models;

namespace ThaipromptAffiliate.Services
{
    public class ApiService : IApiService
    {
        private readonly HttpClient _httpClient;
        private string _authToken;

        public ApiService()
        {
            _httpClient = new HttpClient
            {
                BaseAddress = new Uri(Constants.ApiBaseUrl)
            };
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));
        }

        private void SetAuthToken(string token)
        {
            _authToken = token;
            _httpClient.DefaultRequestHeaders.Authorization =
                new AuthenticationHeaderValue("Bearer", token);
        }

        public async Task<LoginResponse> LoginAsync(string email, string password)
        {
            try
            {
                var loginData = new { email, password };
                var response = await _httpClient.PostAsJsonAsync(
                    Constants.LoginEndpoint, loginData);

                response.EnsureSuccessStatusCode();

                var result = await response.Content
                    .ReadFromJsonAsync<LoginResponse>();

                if (result?.Success == true && !string.IsNullOrEmpty(result.Data?.Token))
                {
                    SetAuthToken(result.Data.Token);
                    await SecureStorage.SetAsync(Constants.AuthTokenKey, result.Data.Token);
                    await SecureStorage.SetAsync(Constants.UserDataKey,
                        JsonSerializer.Serialize(result.Data.User));
                }

                return result;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Login error: {ex.Message}");
                throw;
            }
        }

        public async Task<bool> LogoutAsync()
        {
            try
            {
                var response = await _httpClient.PostAsync(Constants.LogoutEndpoint, null);
                response.EnsureSuccessStatusCode();

                SecureStorage.Remove(Constants.AuthTokenKey);
                SecureStorage.Remove(Constants.UserDataKey);
                _httpClient.DefaultRequestHeaders.Authorization = null;

                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Logout error: {ex.Message}");
                return false;
            }
        }

        public async Task<User> GetCurrentUserAsync()
        {
            try
            {
                await LoadAuthTokenAsync();
                var response = await _httpClient.GetFromJsonAsync<ApiResponse<User>>(
                    Constants.MeEndpoint);
                return response?.Data;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get user error: {ex.Message}");
                throw;
            }
        }

        public async Task<DashboardStats> GetDashboardStatsAsync()
        {
            try
            {
                await LoadAuthTokenAsync();
                var response = await _httpClient.GetFromJsonAsync<ApiResponse<DashboardStats>>(
                    Constants.DashboardStatsEndpoint);
                return response?.Data;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get dashboard stats error: {ex.Message}");
                throw;
            }
        }

        public async Task<List<Commission>> GetCommissionsAsync(int page = 1)
        {
            try
            {
                await LoadAuthTokenAsync();
                var response = await _httpClient.GetFromJsonAsync<CommissionsResponse>(
                    $"{Constants.CommissionsEndpoint}?page={page}");
                return response?.Data?.Data ?? new List<Commission>();
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Get commissions error: {ex.Message}");
                throw;
            }
        }

        private async Task LoadAuthTokenAsync()
        {
            if (string.IsNullOrEmpty(_authToken))
            {
                _authToken = await SecureStorage.GetAsync(Constants.AuthTokenKey);
                if (!string.IsNullOrEmpty(_authToken))
                {
                    SetAuthToken(_authToken);
                }
            }
        }
    }

    // Helper classes
    public class ApiResponse<T>
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("data")]
        public T Data { get; set; }
    }

    public class CommissionsResponse
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }

        [JsonPropertyName("data")]
        public PaginatedData Data { get; set; }
    }

    public class PaginatedData
    {
        [JsonPropertyName("data")]
        public List<Commission> Data { get; set; }

        [JsonPropertyName("current_page")]
        public int CurrentPage { get; set; }

        [JsonPropertyName("per_page")]
        public int PerPage { get; set; }

        [JsonPropertyName("total")]
        public int Total { get; set; }
    }
}
```

### 5. ลงทะเบียน Services ใน MauiProgram.cs

แก้ไขไฟล์ `MauiProgram.cs`:

```csharp
using Microsoft.Extensions.Logging;
using ThaipromptAffiliate.Services;
using ThaipromptAffiliate.ViewModels;
using ThaipromptAffiliate.Views;

namespace ThaipromptAffiliate;

public static class MauiProgram
{
    public static MauiApp CreateMauiApp()
    {
        var builder = MauiApp.CreateBuilder();
        builder
            .UseMauiApp<App>()
            .ConfigureFonts(fonts =>
            {
                fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
                fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
            });

        // Register Services
        builder.Services.AddSingleton<IApiService, ApiService>();

        // Register ViewModels
        builder.Services.AddTransient<LoginViewModel>();
        builder.Services.AddTransient<DashboardViewModel>();

        // Register Views
        builder.Services.AddTransient<LoginPage>();
        builder.Services.AddTransient<DashboardPage>();

#if DEBUG
        builder.Logging.AddDebug();
#endif

        return builder.Build();
    }
}
```

---

## การทดสอบ

### 1. ทดสอบบน Android Emulator

1. เปิด **Android Device Manager**: `Tools > Android > Android Device Manager`
2. สร้าง Virtual Device:
   - Device: Pixel 5
   - System Image: Android 13.0 (API 33)
   - AVD Name: Pixel_5_API_33
3. Start Emulator
4. ใน Visual Studio เลือก target เป็น Android Emulator
5. กด F5 หรือ Debug > Start Debugging

### 2. ทดสอบบน iOS Simulator (macOS)

1. ใน Visual Studio for Mac เลือก iOS Simulator
2. เลือกอุปกรณ์ (เช่น iPhone 14)
3. กด Run

### 3. ทดสอบบนอุปกรณ์จริง

#### Android:
1. เปิด Developer Options บนมือถือ:
   - Settings > About Phone > กด Build Number 7 ครั้ง
2. เปิด USB Debugging
3. เชื่อมต่อมือถือกับคอมพิวเตอร์ผ่าน USB
4. ยอมรับ USB Debugging prompt
5. เลือกอุปกรณ์ใน Visual Studio และกด Run

#### iOS:
1. ต้องมี Apple Developer Account (ฟรีก็ได้)
2. เชื่อมต่อ iPhone กับ Mac
3. เปิด Xcode > Settings > Accounts > เพิ่ม Apple ID
4. ใน Visual Studio for Mac เลือกอุปกรณ์และ Run

---

## การ Deploy

### Deploy ไป Google Play Store (Android)

1. สร้าง Keystore สำหรับ Signing:

```bash
keytool -genkey -v -keystore thaiprompt-affiliate.keystore -alias thaiprompt -keyalg RSA -keysize 2048 -validity 10000
```

2. แก้ไข `Platforms/Android/AndroidManifest.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <application
        android:allowBackup="true"
        android:icon="@mipmap/appicon"
        android:label="Thaiprompt Affiliate"
        android:roundIcon="@mipmap/appicon_round"
        android:supportsRtl="true">
    </application>
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
</manifest>
```

3. Build Release:
   - คลิกขวาที่โปรเจค > Properties
   - ไปที่ Android > Package Signing
   - เลือก keystore ที่สร้างไว้
   - Build > Build Solution (Release mode)

4. หาไฟล์ APK/AAB ที่:
   ```
   bin/Release/net7.0-android/thaiprompt-affiliate.aab
   ```

5. อัปโหลดไปที่ Google Play Console

### Deploy ไป App Store (iOS)

1. ต้องมี **Apple Developer Program** ($99/year)
2. สร้าง App ID ใน [Apple Developer Portal](https://developer.apple.com)
3. สร้าง Provisioning Profile
4. ใน Visual Studio for Mac:
   - Build > Archive for Publishing
   - Validate > Distribute
5. อัปโหลดผ่าน App Store Connect

---

## Tips และ Best Practices

### 1. Security
```csharp
// ใช้ SecureStorage สำหรับข้อมูลสำคัญ
await SecureStorage.SetAsync("token", token);

// HTTPS เท่านั้น
#if !DEBUG
if (!url.StartsWith("https://"))
    throw new SecurityException("HTTPS required");
#endif
```

### 2. Error Handling
```csharp
try
{
    var result = await _apiService.GetDashboardStatsAsync();
}
catch (HttpRequestException ex)
{
    await DisplayAlert("Error", "Network error", "OK");
}
catch (Exception ex)
{
    await DisplayAlert("Error", "An error occurred", "OK");
}
```

### 3. Loading Indicators
```csharp
public class BaseViewModel : ObservableObject
{
    private bool _isBusy;
    public bool IsBusy
    {
        get => _isBusy;
        set => SetProperty(ref _isBusy, value);
    }
}
```

### 4. MVVM Pattern
- ใช้ CommunityToolkit.Mvvm
- แยก Business Logic ออกจาก UI
- ใช้ Commands สำหรับ User Actions

---

## การแก้ปัญหาที่พบบ่อย

### 1. Android Emulator ช้า
- เปิด HAXM (Intel) หรือ WHPX (Windows)
- ลด RAM ของ Emulator
- ใช้ x86_64 image แทน ARM

### 2. iOS Build ไม่ผ่าน
- ตรวจสอบ Provisioning Profile
- ตรวจสอบ Bundle Identifier
- ลบ bin/obj แล้ว Rebuild

### 3. API ไม่เชื่อมต่อ
- ตรวจสอบ BaseUrl ใน Constants.cs
- Android Emulator ใช้ `10.0.2.2` แทน `localhost`
- iOS Simulator ใช้ `localhost` ได้ตรงๆ

---

## ทรัพยากรเพิ่มเติม

- [.NET MAUI Documentation](https://docs.microsoft.com/dotnet/maui/)
- [Thai Prompt API Docs](./MOBILE-APP-API.md)
- [CommunityToolkit.Mvvm](https://learn.microsoft.com/windows/communitytoolkit/mvvm/)
- [Visual Studio for Mac](https://visualstudio.microsoft.com/vs/mac/)

---

## การติดต่อและสนับสนุน

หากมีปัญหาหรือข้อสงสัย กรุณาติดต่อทีมพัฒนาหรือสร้าง Issue ใน Repository

---

**เวอร์ชัน**: 1.0
**อัปเดตล่าสุด**: พฤศจิกายน 2025
**ผู้เขียน**: Thai Prompt Development Team

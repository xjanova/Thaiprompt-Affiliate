# Thaiprompt Affiliate Control App

แอปพลิเคชัน Mobile สำหรับจัดการระบบ Affiliate Marketing ของ Thai Prompt ที่พัฒนาด้วย .NET MAUI

## 📱 ภาพรวม

แอปพลิเคชันนี้เป็น Control App สำหรับผู้ใช้งานระบบ Thai Prompt Affiliate ที่สามารถ:
- ✅ เข้าสู่ระบบและจัดการบัญชี
- 💰 ดูข้อมูลคอมมิชชั่นและรายได้
- 👥 จัดการผู้แนะนำ (Referrals)
- 📊 ดูสถิติและรายงานแดชบอร์ด
- 🔗 แชร์ลิงก์แนะนำ
- ⚙️ ตั้งค่าโปรไฟล์และระบบ

## 🛠️ เทคโนโลยี

- **.NET MAUI** - Cross-platform UI Framework
- **C# 12** - Programming Language
- **.NET 8.0** - Runtime
- **MVVM Pattern** - Architecture Pattern
- **CommunityToolkit.Mvvm** - MVVM Toolkit
- **Laravel Sanctum** - Authentication (Backend)

## 🎯 Platform ที่รองรับ

| Platform | Version | Status |
|----------|---------|--------|
| Android  | 5.0+ (API 21+) | ✅ Supported |
| iOS      | 11.0+ | ✅ Supported |
| Windows  | 10 (10.0.17763.0+) | ✅ Supported |
| macOS    | 10.15+ | ✅ Supported |

## 🚀 การเริ่มต้นใช้งาน

### ข้อกำหนดเบื้องต้น

1. **Visual Studio 2022** (version 17.8+) พร้อม Workloads:
   - .NET Multi-platform App UI development
   - Mobile development with .NET

2. **.NET 8.0 SDK** หรือใหม่กว่า

3. **สำหรับ Android:**
   - Android SDK (API 21-34)
   - Android Emulator หรืออุปกรณ์จริง

4. **สำหรับ iOS:**
   - macOS with Xcode 14+
   - iOS Simulator หรืออุปกรณ์จริง (ต้องมี Apple Developer Account)

### การติดตั้ง

1. **Clone Repository**
   ```bash
   cd Thaiprompt-Affiliate
   cd ThaipromptAffiliateApp
   ```

2. **เปิดโปรเจคใน Visual Studio**
   ```bash
   # Windows
   start ThaipromptAffiliateApp.sln

   # macOS/Linux
   open ThaipromptAffiliateApp.sln
   ```

3. **Restore NuGet Packages**
   ```bash
   dotnet restore
   ```

4. **ตั้งค่า API URL**

   แก้ไขไฟล์ `Helpers/Constants.cs`:
   ```csharp
   // สำหรับ Development
   public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1"; // Android Emulator
   // หรือ
   public const string ApiBaseUrl = "http://localhost:8000/api/v1"; // iOS Simulator

   // สำหรับ Production
   public const string ApiBaseUrl = "https://your-domain.com/api/v1";
   ```

5. **Build และ Run**

   **Android:**
   ```bash
   dotnet build -f net8.0-android
   dotnet run -f net8.0-android
   ```

   **iOS (ต้อง macOS):**
   ```bash
   dotnet build -f net8.0-ios
   dotnet run -f net8.0-ios
   ```

   **Windows:**
   ```bash
   dotnet build -f net8.0-windows10.0.19041.0
   dotnet run -f net8.0-windows10.0.19041.0
   ```

## 📁 โครงสร้างโปรเจค

```
ThaipromptAffiliateApp/
├── Models/                 # Data Models
│   ├── User.cs
│   ├── Dashboard.cs
│   ├── Commission.cs
│   ├── Referral.cs
│   └── ThemeConfig.cs
├── Services/              # Business Logic Services
│   ├── ApiService.cs     # HTTP API Client
│   └── ThemeService.cs   # Theme Management
├── ViewModels/           # MVVM ViewModels
│   ├── BaseViewModel.cs
│   ├── LoginViewModel.cs
│   ├── DashboardViewModel.cs
│   ├── CommissionsViewModel.cs
│   ├── ReferralsViewModel.cs
│   └── ProfileViewModel.cs
├── Views/                # UI Pages (XAML)
│   ├── LoginPage.xaml
│   ├── DashboardPage.xaml
│   ├── CommissionsPage.xaml
│   ├── ReferralsPage.xaml
│   └── ProfilePage.xaml
├── Helpers/              # Utility Classes
│   └── Constants.cs
├── Resources/            # App Resources
│   ├── Styles/          # XAML Styles
│   ├── Images/          # Images
│   └── Fonts/           # Custom Fonts
├── Platforms/            # Platform-specific code
│   ├── Android/
│   ├── iOS/
│   └── Windows/
├── App.xaml             # Application Definition
├── AppShell.xaml        # Navigation Shell
└── MauiProgram.cs       # App Entry Point
```

## 🎨 Features

### 1. Authentication
- เข้าสู่ระบบด้วย Email/Password
- จัดเก็บ Token อย่างปลอดภัยด้วย SecureStorage
- Auto-login เมื่อเปิดแอพ

### 2. Dashboard
- แสดงสถิติรายได้ทั้งหมด
- แสดงคอมมิชชั่นที่รออนุมัติ
- แสดงจำนวนผู้แนะนำ
- รายการคอมมิชชั่นล่าสุด
- รองรับ Pull-to-Refresh

### 3. Commissions
- รายการคอมมิชชั่นทั้งหมด
- กรองตามสถานะ (อนุมัติ/รออนุมัติ/ปฏิเสธ)
- Infinite Scroll (โหลดเพิ่มเติม)
- แสดงรายละเอียดคอมมิชชั่น

### 4. Referrals
- แสดงลิงก์แนะนำ
- คัดลอกลิงก์ไปคลิปบอร์ด
- แชร์ลิงก์ผ่าน Native Share
- รายการผู้ที่แนะนำ
- สถิติผู้แนะนำ

### 5. Profile
- แสดงข้อมูลโปรไฟล์
- แก้ไขโปรไฟล์
- เปลี่ยนรหัสผ่าน
- ตั้งค่าการแจ้งเตือน
- เลือกภาษา
- โหมดมืด (Dark Mode)
- ออกจากระบบ

## 🔐 Security

- ใช้ **HTTPS** สำหรับการสื่อสารทั้งหมด
- เก็บ Token ด้วย **SecureStorage** (Keychain บน iOS, EncryptedSharedPreferences บน Android)
- ไม่เก็บรหัสผ่านในเครื่อง
- Token expiration handling

## 🎨 UI/UX Design

- **Modern Gradient Design** - ใช้ Gradient สีสวยงาม
- **Card-based Layout** - ใช้ Card components ที่ทันสมัย
- **Smooth Animations** - มีแอนิเมชั่นที่ลื่นไหล
- **Responsive Design** - รองรับทุกขนาดหน้าจอ
- **Thai Language** - UI เป็นภาษาไทยทั้งหมด
- **Icon & Emoji** - ใช้ Icon และ Emoji ประกอบ

### Color Scheme

- **Primary**: Blue (#3B82F6 → #1D4ED8)
- **Secondary**: Green (#10B981 → #059669)
- **Accent**: Purple (#8B5CF6 → #6D28D9)
- **Success**: Green (#10B981)
- **Warning**: Orange (#F59E0B)
- **Error**: Red (#EF4444)

## 🔧 Configuration

### API Endpoints

แก้ไขไฟล์ `Helpers/Constants.cs` เพื่อตั้งค่า API:

```csharp
// Development
#if DEBUG
    public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1"; // Android
    // public const string ApiBaseUrl = "http://localhost:8000/api/v1"; // iOS
#else
    // Production
    public const string ApiBaseUrl = "https://your-domain.com/api/v1";
#endif
```

### App Configuration

```csharp
// App Information
public const string AppName = "Thaiprompt Affiliate";
public const string AppVersion = "1.0.0";

// Timeouts
public const int ApiTimeout = 30; // seconds

// Pagination
public const int DefaultPageSize = 20;
```

## 📱 การทดสอบ

### Android Emulator

1. เปิด Android Device Manager
2. สร้าง Virtual Device (แนะนำ Pixel 5, API 33)
3. เลือก target เป็น Android Emulator
4. กด F5 หรือ Debug > Start Debugging

### iOS Simulator (macOS)

1. เลือก iOS Simulator
2. เลือกอุปกรณ์ (เช่น iPhone 15)
3. กด Run

### Physical Device

**Android:**
1. เปิด Developer Options & USB Debugging
2. เชื่อมต่อผ่าน USB
3. เลือกอุปกรณ์ใน Visual Studio
4. Run

**iOS:**
1. ต้องมี Apple Developer Account
2. เชื่อมต่อ iPhone กับ Mac
3. Trust Developer Certificate
4. Run

## 🚢 การ Deploy

### Android (Google Play Store)

1. **สร้าง Keystore:**
   ```bash
   keytool -genkey -v -keystore thaiprompt.keystore \
     -alias thaiprompt -keyalg RSA -keysize 2048 -validity 10000
   ```

2. **Build Release:**
   - Project Properties > Android > Package Signing
   - เลือก Keystore
   - Build > Build Solution (Release)

3. **อัปโหลด AAB:**
   ```
   bin/Release/net8.0-android/thaiprompt-affiliate.aab
   ```

### iOS (App Store)

1. ต้องมี Apple Developer Program ($99/year)
2. สร้าง App ID และ Provisioning Profile
3. Build > Archive for Publishing
4. Validate & Distribute
5. อัปโหลดผ่าน App Store Connect

## 🐛 Troubleshooting

### Android Emulator ช้า
- เปิด Hardware Acceleration (HAXM/WHPX)
- ใช้ x86_64 image แทน ARM
- ลด RAM ของ Emulator

### API ไม่เชื่อมต่อ
- ตรวจสอบ URL ใน Constants.cs
- Android Emulator ใช้ `10.0.2.2` แทน `localhost`
- iOS Simulator ใช้ `localhost` ได้ตรง
- ตรวจสอบ CORS settings บน Backend

### Build Error
```bash
# Clean และ Rebuild
dotnet clean
dotnet restore
dotnet build
```

## 📚 เอกสารเพิ่มเติม

- [.NET MAUI Documentation](https://docs.microsoft.com/dotnet/maui/)
- [API Documentation](../MOBILE-APP-API.md)
- [Visual Studio Setup Guide](../MOBILE-APP-VISUAL-STUDIO-SETUP.md)
- [Backend API](../API_ARCHITECTURE_GUIDE.md)

## 👥 ทีมพัฒนา

- Thai Prompt Development Team

## 📄 License

Copyright © 2025 Thai Prompt. All rights reserved.

## 🔄 Version History

### Version 1.0.0 (2025-01-13)
- ✨ Initial Release
- 🎨 Modern UI with Gradient Design
- 🔐 Secure Authentication
- 💰 Commission Management
- 👥 Referral System
- 📊 Dashboard & Statistics
- ⚙️ Profile & Settings

---

**พัฒนาด้วย ❤️ โดย Thai Prompt Team**

# 🚀 Thaiprompt Ultra App - .NET MAUI

> **Ultra App ระดับเทพ** - แอพเดียวที่รวมทุกบริการไว้ในที่เดียว

## 📱 Overview

**Thaiprompt Ultra App** คือแอพพลิเคชัน .NET MAUI ที่พัฒนาขึ้นเพื่อเป็น **Super App** ครบวงจร รองรับทุกความต้องการของผู้ใช้งาน ไม่ว่าจะเป็น:

- 🛒 **ช้อปปิ้ง & บริการ** - E-commerce, สั่งอาหาร, จองโรงแรม
- 💰 **กระเป๋าเงิน** - Wallet, เติมเงิน, โอนเงิน
- 📈 **ลงทุน & Trade** - Crypto, Bot Trading, TPIX Token
- 🤝 **MLM & Affiliate** - Commission, Referral, Team Building
- 🚴 **เป็นไรเดอร์** - รับงาน, Service Provider
- 🤖 **AI Bot** - LINE Bot, Automation
- 🎓 **Academy** - หลักสูตร, Certificate
- 🎮 **Gaming & Rewards** - เกม, Quest, Achievement

## ✨ Features

### 🆕 Modern Splash Screen
- **3D Animated Logo** พร้อม Glow Effect
- **Gradient Background** สีเข้มสวยงาม
- **Loading Animation** แบบ pulse dots
- **Auto-navigation** ตรวจสอบ authentication

### 🎯 Hub Selection Page
- **8 บริการหลัก** ในรูปแบบ Grid Cards
- **Modern Glassmorphism Design**
- **Quick Actions** สำหรับการดำเนินการด่วน
- **User Info Bar** แสดงข้อมูลและยอดเงิน

### 🎨 Ultra Theme
- **Glassmorphism + 3D Effects**
- **Dark Mode เป็นหลัก** (สวยกว่า)
- **Gradient Backgrounds**
- **Smooth Animations**

## 📁 Project Structure

```
dotnet-maui/
├── 📂 Models/
│   ├── User.cs              # ข้อมูลผู้ใช้
│   ├── Dashboard.cs         # ข้อมูล Dashboard
│   ├── Referral.cs          # ข้อมูล Referral
│   ├── ThemeConfig.cs       # การตั้งค่า Theme
│   └── HubItem.cs           # 🆕 รายการ Hub
│
├── 📂 Services/
│   ├── ApiService.cs        # HTTP API client
│   └── ThemeService.cs      # จัดการ Theme
│
├── 📂 ViewModels/
│   ├── BaseViewModel.cs     # Base class
│   ├── SplashViewModel.cs   # 🆕 Splash logic
│   ├── HubSelectionViewModel.cs  # 🆕 Hub logic
│   ├── LoginViewModel.cs    # Login logic
│   └── DashboardViewModel.cs # Dashboard logic
│
├── 📂 Views/
│   ├── SplashPage.xaml      # 🆕 หน้า Splash
│   ├── HubSelectionPage.xaml # 🆕 หน้าเลือกบริการ
│   ├── LoginPage.xaml       # หน้า Login
│   └── DashboardPage.xaml   # หน้า Dashboard
│
├── 📂 Resources/
│   └── Styles/
│       ├── UltraTheme.xaml  # 🆕 Ultra Theme
│       └── ThemeResources.xaml
│
├── 📂 Converters/
│   └── BoolConverters.cs    # 🆕 Value Converters
│
├── 📂 Helpers/
│   └── Constants.cs         # ค่าคงที่
│
├── App.xaml                 # 🆕 Main App
├── AppShell.xaml            # 🆕 Navigation Shell
├── MauiProgram.cs           # 🆕 DI Setup
└── ThaipromptAffiliate.csproj # 🆕 Project File
```

## 🚀 Getting Started

### Prerequisites

- **.NET 8.0 SDK** หรือใหม่กว่า
- **Visual Studio 2022** (with MAUI workload)
- **Android SDK** (API 21+)
- **Xcode** (สำหรับ iOS)

### Installation

```bash
# 1. Clone repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git

# 2. เข้าไปที่โฟลเดอร์ mobile app
cd Thaiprompt-Affiliate/mobile-app-samples/dotnet-maui

# 3. Restore packages
dotnet restore

# 4. Build
dotnet build

# 5. Run (Android)
dotnet build -t:Run -f net8.0-android

# 6. Run (iOS - Mac only)
dotnet build -t:Run -f net8.0-ios
```

### Configuration

1. **อัพเดท API URL** ใน `Helpers/Constants.cs`:

```csharp
// For Production
public const string ApiBaseUrl = "https://your-domain.com/api/v1";
```

2. **สร้าง App Icons** ใน `Resources/AppIcon/`

3. **สร้าง Splash Image** ใน `Resources/Splash/`

## 📚 Architecture

### MVVM Pattern

```
┌─────────────────┐
│     View        │  ← XAML UI
├─────────────────┤
│   ViewModel     │  ← Business Logic
├─────────────────┤
│    Service      │  ← API Calls
├─────────────────┤
│     Model       │  ← Data Objects
└─────────────────┘
```

### Navigation Flow

```
Splash Screen
      │
      ▼
Hub Selection ──────────────────┐
      │                         │
      ├── 🛒 Shopping          │
      ├── 💰 Wallet            │
      ├── 📈 Invest            │
      ├── 🤝 MLM ◄─────────────┤
      ├── 🚴 Rider             │
      ├── 🤖 AI Bot            │
      ├── 🎓 Academy           │
      └── 🎮 Gaming            │
                               │
                      Need Login?
                         │
                         ▼
                   Login Page
                         │
                         ▼
                     Dashboard
```

## 🎨 Design System

### Colors

| Color | Hex | Usage |
|-------|-----|-------|
| Primary | `#6366F1` | ปุ่มหลัก, Accent |
| Secondary | `#8B5CF6` | ปุ่มรอง |
| Accent | `#06B6D4` | เน้น |
| Success | `#10B981` | สำเร็จ |
| Warning | `#F59E0B` | เตือน |
| Error | `#EF4444` | ผิดพลาด |
| Dark BG | `#0F0F23` | พื้นหลังหลัก |
| Dark Card | `#16213E` | พื้นหลังการ์ด |

### Typography

- **Display**: 40px Bold
- **H1**: 32px Bold
- **H2**: 28px Semibold
- **H3**: 24px Semibold
- **Body**: 16px Regular
- **Caption**: 12px Regular

### Spacing

- **XS**: 4px
- **SM**: 8px
- **MD**: 16px
- **LG**: 24px
- **XL**: 32px
- **XXL**: 48px

## 📱 Screenshots

```
┌─────────────────────────────────┐
│                                 │
│         ✨ 🚀 ✨                │
│                                 │
│        THAIPROMPT               │
│       ━━━━━━━━━━━━              │
│      Ultra Ecosystem            │
│                                 │
│         ◉ ◉ ◉                   │
│                                 │
│  "Powered by AI & Blockchain"  │
│                                 │
└─────────────────────────────────┘
        Splash Screen

┌─────────────────────────────────┐
│  สวัสดีตอนเช้า ☀️        👤    │
│  ยินดีต้อนรับ                   │
├─────────────────────────────────┤
│  ┌───────┐  ┌───────┐          │
│  │ 🛒    │  │ 💰    │          │
│  │ช้อปปิ้ง│  │กระเป๋า │          │
│  └───────┘  └───────┘          │
│  ┌───────┐  ┌───────┐          │
│  │ 📈    │  │ 🤝    │          │
│  │ลงทุน  │  │ MLM   │          │
│  └───────┘  └───────┘          │
│        ...more...               │
├─────────────────────────────────┤
│ [เข้าสู่ระบบ] [สมัครสมาชิก]      │
└─────────────────────────────────┘
       Hub Selection
```

## 🔧 Development

### Adding New Hub

1. เพิ่ม route ใน `AppShell.xaml.cs`:
```csharp
Routing.RegisterRoute("new-hub", typeof(Views.NewHubPage));
```

2. เพิ่ม HubItem ใน `HubSelectionViewModel.cs`:
```csharp
new HubItem
{
    Id = "new-hub",
    Icon = "🆕",
    Title = "New Service",
    Subtitle = "Description",
    Route = "new-hub",
    GradientStart = "#Color1",
    GradientEnd = "#Color2"
}
```

3. สร้าง Page และ ViewModel

### Testing

```bash
# Unit Tests
dotnet test

# UI Tests (Appium)
# Coming soon...
```

## 📄 License

Private - Thaiprompt Team

## 📞 Support

- **Documentation**: [CLAUDE.md](../../CLAUDE.md)
- **API Docs**: [MOBILE-APP-API.md](../../MOBILE-APP-API.md)
- **Setup Guide**: [MOBILE-APP-VISUAL-STUDIO-SETUP.md](../../MOBILE-APP-VISUAL-STUDIO-SETUP.md)

---

**Made with ❤️ by Thaiprompt Team**

*"Ultra App ระดับเทพของโลก"*

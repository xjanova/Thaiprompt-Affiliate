# คู่มือการติดตั้งและพัฒนาด้วย Visual Studio

## Thaiprompt Ultra App - .NET MAUI Mobile Application

---

## สารบัญ
1. [ความต้องการของระบบ](#1-ความต้องการของระบบ)
2. [ติดตั้ง Visual Studio](#2-ติดตั้ง-visual-studio)
3. [ดาวน์โหลดโปรเจค](#3-ดาวน์โหลดโปรเจค)
4. [ติดตั้ง Fonts](#4-ติดตั้ง-fonts)
5. [เปิดโปรเจคใน Visual Studio](#5-เปิดโปรเจคใน-visual-studio)
6. [ตั้งค่า Android Emulator](#6-ตั้งค่า-android-emulator)
7. [Build และ Run](#7-build-และ-run)
8. [การแก้ไขปัญหาที่พบบ่อย](#8-การแก้ไขปัญหาที่พบบ่อย)
9. [โครงสร้างโปรเจค](#9-โครงสร้างโปรเจค)
10. [การพัฒนาต่อ](#10-การพัฒนาต่อ)

---

## 1. ความต้องการของระบบ

### Windows:
- **OS**: Windows 10/11 (64-bit)
- **RAM**: 16GB ขึ้นไป (แนะนำ 32GB)
- **Storage**: SSD 50GB ว่าง
- **Visual Studio 2022** (v17.8+)

### macOS:
- **OS**: macOS 13 (Ventura) ขึ้นไป
- **RAM**: 16GB ขึ้นไป
- **Visual Studio 2022 for Mac** หรือ **JetBrains Rider**

---

## 2. ติดตั้ง Visual Studio

### Windows - Visual Studio 2022

1. **ดาวน์โหลด Visual Studio 2022:**
   - ไปที่: https://visualstudio.microsoft.com/downloads/
   - เลือก **Community** (ฟรี) หรือ Professional/Enterprise

2. **เลือก Workloads ที่ต้องติดตั้ง:**

   ✅ **บังคับติดตั้ง:**
   - `.NET Multi-platform App UI development`
   - `Mobile development with .NET`

   ✅ **แนะนำเพิ่มเติม:**
   - `.NET desktop development`
   - `ASP.NET and web development`

3. **Individual Components ที่ต้องมี:**
   - .NET 8.0 SDK
   - Android SDK (API 34)
   - Android Emulator
   - Intel HAXM (สำหรับ Intel CPU)

4. **คลิก Install และรอจนเสร็จ** (อาจใช้เวลา 30-60 นาที)

### macOS - Visual Studio 2022 for Mac

1. **ดาวน์โหลด:**
   - ไปที่: https://visualstudio.microsoft.com/vs/mac/
   - ดาวน์โหลดและติดตั้ง

2. **Workloads:**
   - .NET Multi-platform App UI development
   - iOS development
   - Android development

---

## 3. ดาวน์โหลดโปรเจค

### วิธีที่ 1: Clone จาก Git (แนะนำ)

```bash
# เปิด Command Prompt หรือ Terminal
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git

# เข้าไปที่โฟลเดอร์ mobile app
cd Thaiprompt-Affiliate/mobile-app-samples/dotnet-maui
```

### วิธีที่ 2: ดาวน์โหลด ZIP

1. ไปที่ GitHub repository
2. คลิก `Code` > `Download ZIP`
3. แตกไฟล์ไปที่โฟลเดอร์ที่ต้องการ
4. เข้าไปที่ `mobile-app-samples/dotnet-maui`

### โครงสร้างไฟล์ที่ควรเห็น:

```
dotnet-maui/
├── ThaipromptAffiliate.sln          ← Solution file (เปิดไฟล์นี้)
├── ThaipromptAffiliate.csproj       ← Project file
├── App.xaml                         ← Application entry
├── App.xaml.cs
├── AppShell.xaml                    ← Navigation shell
├── AppShell.xaml.cs
├── MauiProgram.cs                   ← DI configuration
├── Converters/
│   └── BoolConverters.cs
├── Helpers/
│   └── Constants.cs                 ← API endpoints
├── Models/
│   ├── Dashboard.cs
│   ├── HubItem.cs
│   ├── Referral.cs
│   ├── ThemeConfig.cs
│   └── User.cs
├── Resources/
│   ├── AppIcon/
│   │   ├── appicon.svg
│   │   └── appiconfg.svg
│   ├── Fonts/
│   │   └── (ต้องเพิ่ม OpenSans fonts)
│   ├── Images/
│   ├── Raw/
│   │   └── about.txt
│   ├── Splash/
│   │   └── splash.svg
│   └── Styles/
│       ├── ThemeResources.xaml
│       └── UltraTheme.xaml
├── Services/
│   ├── ApiService.cs
│   └── ThemeService.cs
├── ViewModels/
│   ├── BaseViewModel.cs
│   ├── DashboardViewModel.cs
│   ├── HubSelectionViewModel.cs
│   ├── LoginViewModel.cs
│   └── SplashViewModel.cs
└── Views/
    ├── DashboardPage.xaml
    ├── DashboardPage.xaml.cs
    ├── HubSelectionPage.xaml
    ├── HubSelectionPage.xaml.cs
    ├── LoginPage.xaml
    ├── LoginPage.xaml.cs
    ├── SplashPage.xaml
    └── SplashPage.xaml.cs
```

---

## 4. ติดตั้ง Fonts

**สำคัญ!** โปรเจคใช้ Open Sans font ต้องดาวน์โหลดและเพิ่มก่อน build

### ขั้นตอน:

1. **ดาวน์โหลด Open Sans:**
   - ไปที่: https://fonts.google.com/specimen/Open+Sans
   - คลิก `Download family`

2. **แตกไฟล์ ZIP**

3. **คัดลอกไฟล์ต่อไปนี้ไปที่ `Resources/Fonts/`:**
   - `OpenSans-Regular.ttf`
   - `OpenSans-SemiBold.ttf` (เปลี่ยนชื่อเป็น `OpenSans-Semibold.ttf`)
   - `OpenSans-Bold.ttf`

4. **ตรวจสอบ:**
   ```
   Resources/Fonts/
   ├── OpenSans-Regular.ttf
   ├── OpenSans-Semibold.ttf
   └── OpenSans-Bold.ttf
   ```

---

## 5. เปิดโปรเจคใน Visual Studio

### ขั้นตอน:

1. **เปิด Visual Studio 2022**

2. **เลือก `Open a project or solution`**

3. **นำทางไปที่:**
   ```
   Thaiprompt-Affiliate/mobile-app-samples/dotnet-maui/
   ```

4. **เลือกไฟล์ `ThaipromptAffiliate.sln`**

5. **รอให้ Visual Studio โหลดและ restore packages**
   - จะเห็นข้อความ "Restoring NuGet packages..."
   - รอจนเสร็จ (อาจใช้เวลา 1-5 นาที)

### ตรวจสอบ Solution Explorer:

หลังโหลดเสร็จควรเห็น:
```
Solution 'ThaipromptAffiliate' (1 project)
└── ThaipromptAffiliate
    ├── Dependencies
    ├── Platforms
    │   ├── Android
    │   └── iOS
    ├── Resources
    ├── Converters
    ├── Helpers
    ├── Models
    ├── Services
    ├── ViewModels
    ├── Views
    ├── App.xaml
    ├── AppShell.xaml
    └── MauiProgram.cs
```

---

## 6. ตั้งค่า Android Emulator

### สร้าง Android Emulator:

1. **เปิด Android Device Manager:**
   - เมนู `Tools` > `Android` > `Android Device Manager`

2. **คลิก `+ New`**

3. **เลือก Device:**
   - **Base Device:** Pixel 5 หรือ Pixel 7
   - **OS:** Android 14.0 (API 34) - Google APIs

4. **ตั้งค่า Hardware:**
   - **RAM:** 4096 MB
   - **VM Heap:** 512 MB
   - **Storage:** 8192 MB

5. **คลิก `Create`**

6. **รอดาวน์โหลด System Image** (อาจใช้เวลา 10-30 นาที)

### ทดสอบ Emulator:

1. คลิก ▶️ ที่ emulator ที่สร้าง
2. รอให้ boot เสร็จ
3. ควรเห็นหน้าจอ Android

---

## 7. Build และ Run

### Android:

1. **เลือก Target:**
   - ที่ toolbar เลือก `Android Emulator` หรือ `[ชื่อ Emulator]`

2. **เลือก Configuration:**
   - `Debug` (สำหรับพัฒนา)
   - `Release` (สำหรับ production)

3. **Build และ Run:**
   - กด `F5` หรือ คลิกปุ่ม ▶️ `Start`
   - รอ build (ครั้งแรกอาจใช้เวลา 3-5 นาที)

4. **ผลลัพธ์:**
   - แอพจะเปิดบน Emulator
   - จะเห็น Splash Screen → Hub Selection

### iOS (macOS เท่านั้น):

1. **เลือก Target:** iOS Simulator
2. **เลือก Device:** iPhone 15 Pro
3. **กด F5 หรือ ▶️**

---

## 8. การแก้ไขปัญหาที่พบบ่อย

### ปัญหา 1: NuGet restore failed

**วิธีแก้:**
```bash
# เปิด Terminal/Command Prompt
cd Thaiprompt-Affiliate/mobile-app-samples/dotnet-maui
dotnet restore
```

หรือใน Visual Studio:
- `Build` > `Rebuild Solution`

### ปัญหา 2: Android SDK not found

**วิธีแก้:**
1. `Tools` > `Options` > `Xamarin` > `Android Settings`
2. ตรวจสอบ Android SDK Location
3. ถ้าว่างให้คลิก `...` และเลือกที่ตั้ง SDK

### ปัญหา 3: Font file not found

**วิธีแก้:**
1. ตรวจสอบว่าไฟล์ font อยู่ใน `Resources/Fonts/`
2. ตรวจสอบชื่อไฟล์ตรงกับที่กำหนดใน `.csproj`
3. Right-click ไฟล์ font > Properties > Build Action = `MauiFont`

### ปัญหา 4: Emulator ช้ามาก

**วิธีแก้:**
1. เปิด HAXM/Hyper-V ใน BIOS
2. เพิ่ม RAM ให้ Emulator
3. ใช้ x86_64 system image แทน ARM

### ปัญหา 5: XAML Hot Reload ไม่ทำงาน

**วิธีแก้:**
1. `Tools` > `Options` > `Debugging` > `Hot Reload`
2. ติ๊ก `Enable XAML Hot Reload`
3. Restart Visual Studio

---

## 9. โครงสร้างโปรเจค

### Architecture: MVVM Pattern

```
┌─────────────────────────────────────────────────────────┐
│                        Views                            │
│     (SplashPage, HubSelectionPage, LoginPage, etc.)     │
│                     ↕ Data Binding                      │
├─────────────────────────────────────────────────────────┤
│                     ViewModels                          │
│  (SplashViewModel, HubSelectionViewModel, LoginViewModel)│
│                     ↕ Services                          │
├─────────────────────────────────────────────────────────┤
│                      Services                           │
│           (ApiService, ThemeService)                    │
│                     ↕ HTTP/Storage                      │
├─────────────────────────────────────────────────────────┤
│                       Models                            │
│       (User, HubItem, Dashboard, Referral)              │
└─────────────────────────────────────────────────────────┘
```

### ไฟล์สำคัญ:

| ไฟล์ | หน้าที่ |
|------|--------|
| `App.xaml` | Application resources และ theme |
| `AppShell.xaml` | Navigation structure |
| `MauiProgram.cs` | Dependency Injection setup |
| `SplashPage.xaml` | หน้า Splash ที่มี animation |
| `HubSelectionPage.xaml` | หน้าเลือก 8 บริการหลัก |
| `ApiService.cs` | เชื่อมต่อ Backend API |
| `Constants.cs` | API endpoints และ settings |

---

## 10. การพัฒนาต่อ

### การเพิ่มหน้าใหม่:

1. **สร้าง View:**
   - Right-click `Views` > `Add` > `New Item`
   - เลือก `.NET MAUI ContentPage (XAML)`
   - ตั้งชื่อ เช่น `ShoppingPage.xaml`

2. **สร้าง ViewModel:**
   - Right-click `ViewModels` > `Add` > `Class`
   - ตั้งชื่อ เช่น `ShoppingViewModel.cs`
   - Inherit จาก `BaseViewModel`

3. **Register ใน DI:**
   ```csharp
   // MauiProgram.cs
   builder.Services.AddTransient<ShoppingPage>();
   builder.Services.AddTransient<ShoppingViewModel>();
   ```

4. **Register Route:**
   ```csharp
   // AppShell.xaml.cs
   Routing.RegisterRoute("shopping", typeof(ShoppingPage));
   ```

### การแก้ไข API Endpoint:

1. เปิด `Helpers/Constants.cs`
2. แก้ไข `BaseUrl` ให้ชี้ไปที่ server จริง:
   ```csharp
   public const string BaseUrl = "https://your-api-domain.com";
   ```

### การเปลี่ยนสี Theme:

1. เปิด `Resources/Styles/UltraTheme.xaml`
2. แก้ไขสีตามต้องการ

---

## ติดต่อสอบถาม

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- GitHub Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues

---

**Happy Coding! 🚀**

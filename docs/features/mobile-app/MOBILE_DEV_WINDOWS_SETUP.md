# 📱 คู่มือการติดตั้งและพัฒนา Mobile App บน Windows

> **เอกสารนี้สำหรับนักพัฒนาที่ต้องการดึงเฉพาะส่วน Mobile App มาพัฒนาบน Windows**

---

## 📋 สารบัญ

1. [ความต้องการของระบบ](#1-ความต้องการของระบบ)
2. [ขั้นตอนการติดตั้งเครื่องมือ](#2-ขั้นตอนการติดตั้งเครื่องมือ)
3. [วิธีดึง Mobile App](#3-วิธีดึง-mobile-app)
4. [เปิดโปรเจคใน Visual Studio](#4-เปิดโปรเจคใน-visual-studio)
5. [การ Build และ Run](#5-การ-build-และ-run)
6. [การแก้ไขปัญหา](#6-การแก้ไขปัญหา)

---

## 1. ความต้องการของระบบ

### ฮาร์ดแวร์ขั้นต่ำ

| รายการ | ขั้นต่ำ | แนะนำ |
|--------|--------|-------|
| **OS** | Windows 10 (64-bit) | Windows 11 (64-bit) |
| **RAM** | 8 GB | 16 GB ขึ้นไป |
| **Storage** | 30 GB ว่าง (SSD) | 50 GB ว่าง (NVMe SSD) |
| **CPU** | Intel i5 / AMD Ryzen 5 | Intel i7 / AMD Ryzen 7 |

### ซอฟต์แวร์ที่ต้องติดตั้ง

- ✅ **Git for Windows** - สำหรับ clone repository
- ✅ **.NET 8.0 SDK** - สำหรับ build แอพ
- ✅ **Visual Studio 2022** - IDE หลักสำหรับพัฒนา
- ✅ **Android SDK** - สำหรับ build Android app

---

## 2. ขั้นตอนการติดตั้งเครื่องมือ

### 2.1 ติดตั้ง Git for Windows

1. ดาวน์โหลดจาก: https://git-scm.com/download/win
2. รัน installer และเลือก default options
3. ตรวจสอบการติดตั้ง:

```cmd
git --version
```

ควรเห็น: `git version 2.x.x`

### 2.2 ติดตั้ง .NET 8.0 SDK

1. ดาวน์โหลดจาก: https://dotnet.microsoft.com/download/dotnet/8.0
2. เลือก **SDK** (ไม่ใช่ Runtime)
3. รัน installer
4. ตรวจสอบการติดตั้ง:

```cmd
dotnet --version
```

ควรเห็น: `8.0.x`

### 2.3 ติดตั้ง Visual Studio 2022

1. ดาวน์โหลดจาก: https://visualstudio.microsoft.com/vs/

2. **เลือก Workloads ที่ต้องติดตั้ง:**

   ✅ **บังคับ:**
   - `.NET Multi-platform App UI development`
   - `Mobile development with .NET`

   ✅ **แนะนำเพิ่มเติม:**
   - `.NET desktop development`

3. **Individual Components ที่ต้องมี:**
   - .NET 8.0 SDK
   - Android SDK (API 34)
   - Android Emulator
   - Intel HAXM (สำหรับ Intel CPU) หรือ Windows Hypervisor Platform

4. คลิก **Install** และรอจนเสร็จ (~30-60 นาที)

### 2.4 ตรวจสอบ MAUI Workload

เปิด Command Prompt และรัน:

```cmd
dotnet workload list
```

ควรเห็น `maui` ในรายการ

ถ้าไม่มี ให้ติดตั้ง:

```cmd
dotnet workload install maui
```

---

## 3. วิธีดึง Mobile App

### 🚀 วิธีที่ 1: ใช้ Script อัตโนมัติ (แนะนำ)

1. **ดาวน์โหลด Script:**

   เปิด PowerShell แล้วรัน:

   ```powershell
   # ดาวน์โหลด script
   Invoke-WebRequest -Uri "https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/main/scripts/clone-mobile-app.bat" -OutFile "$env:USERPROFILE\Downloads\clone-mobile-app.bat"

   # รัน script
   Start-Process "$env:USERPROFILE\Downloads\clone-mobile-app.bat"
   ```

2. **เลือกตัวเลือก:**
   - กด `1` สำหรับ Sparse Checkout (เร็วกว่า)
   - กด `2` สำหรับ Clone แบบเต็ม

3. **รอจนเสร็จ** - Script จะดึงเฉพาะส่วน mobile app

### 📝 วิธีที่ 2: ใช้คำสั่ง Manual (Sparse Checkout)

```cmd
REM สร้างโฟลเดอร์สำหรับโปรเจค
mkdir %USERPROFILE%\Projects\ThaipromptAffiliate-Mobile
cd %USERPROFILE%\Projects\ThaipromptAffiliate-Mobile

REM Initialize git และตั้งค่า sparse checkout
git init
git remote add origin https://github.com/xjanova/Thaiprompt-Affiliate.git
git config core.sparseCheckout true

REM กำหนดโฟลเดอร์ที่ต้องการ
echo ThaipromptAffiliateApp/ > .git\info\sparse-checkout
echo mobile-app-samples/dotnet-maui/ >> .git\info\sparse-checkout
echo docs/features/mobile-app/ >> .git\info\sparse-checkout

REM ดึงข้อมูล
git pull origin main
```

### 📝 วิธีที่ 3: Clone ทั้งหมดแล้วเปิดเฉพาะ Mobile App

```cmd
REM Clone ทั้ง repository (shallow clone)
git clone --depth 1 https://github.com/xjanova/Thaiprompt-Affiliate.git

REM เข้าไปที่โฟลเดอร์ mobile app
cd Thaiprompt-Affiliate\ThaipromptAffiliateApp
```

---

## 4. เปิดโปรเจคใน Visual Studio

### 4.1 เปิด Solution

1. เปิด **Visual Studio 2022**
2. คลิก **Open a project or solution**
3. นำทางไปที่:
   ```
   %USERPROFILE%\Projects\ThaipromptAffiliate-Mobile\ThaipromptAffiliateApp\
   ```
4. เลือกไฟล์ **`ThaipromptAffiliateApp.sln`**
5. รอให้ Visual Studio โหลดและ restore packages

### 4.2 ติดตั้ง Fonts (สำคัญ!)

แอพใช้ **Open Sans** font ต้องดาวน์โหลดก่อน build:

1. ดาวน์โหลด Open Sans: https://fonts.google.com/specimen/Open+Sans
2. แตกไฟล์ ZIP
3. คัดลอกไฟล์ต่อไปนี้ไปที่ `Resources\Fonts\`:
   - `OpenSans-Regular.ttf`
   - `OpenSans-SemiBold.ttf`
   - `OpenSans-Bold.ttf`

### 4.3 โครงสร้างไฟล์ที่ควรเห็น

```
ThaipromptAffiliateApp/
├── ThaipromptAffiliateApp.sln    ← เปิดไฟล์นี้
├── ThaipromptAffiliateApp.csproj
├── App.xaml                      ← Application entry
├── AppShell.xaml                 ← Navigation
├── MauiProgram.cs                ← DI configuration
├── Converters/
│   └── ValueConverters.cs
├── Helpers/
│   ├── AppHelpers.cs
│   └── Constants.cs              ← ⚡ แก้ไข API URL ที่นี่
├── Models/
│   ├── User.cs
│   ├── Dashboard.cs
│   ├── Referral.cs
│   └── ...
├── Platforms/
│   ├── Android/
│   ├── iOS/
│   └── Windows/
├── Resources/
│   ├── AppIcon/
│   ├── Fonts/                    ← ใส่ fonts ที่นี่
│   ├── Images/
│   └── Styles/
├── Services/
│   ├── ApiService.cs             ← HTTP client
│   ├── ConfigurationService.cs
│   └── ThemeService.cs
├── ViewModels/
│   ├── BaseViewModel.cs
│   ├── LoginViewModel.cs
│   ├── DashboardViewModel.cs
│   └── ...
└── Views/
    ├── SplashPage.xaml
    ├── LoginPage.xaml
    ├── HomePage.xaml
    ├── DashboardPage.xaml
    └── ...
```

---

## 5. การ Build และ Run

### 5.1 ตั้งค่า API URL

แก้ไขไฟล์ `Helpers/Constants.cs`:

```csharp
// สำหรับ Android Emulator
public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1";

// สำหรับ Physical Device (ใส่ IP ของคอมพิวเตอร์)
// public const string ApiBaseUrl = "http://192.168.1.xxx:8000/api/v1";

// สำหรับ Production
// public const string ApiBaseUrl = "https://your-domain.com/api/v1";
```

**หา IP ของคอมพิวเตอร์:**

```cmd
ipconfig
```

ดูที่ `IPv4 Address` ของ network adapter ที่ใช้งาน

### 5.2 สร้าง Android Emulator

1. เปิด **Android Device Manager**: `Tools > Android > Android Device Manager`

2. คลิก **+ New**

3. **ตั้งค่า:**
   - **Base Device:** Pixel 7 หรือ Pixel 5
   - **OS:** Android 14.0 (API 34) - Google APIs
   - **RAM:** 4096 MB
   - **VM Heap:** 512 MB

4. คลิก **Create** และรอดาวน์โหลด

### 5.3 Build และ Run

1. **เลือก Target:**
   - ที่ toolbar เลือก `Android Emulator` หรือชื่อ emulator ที่สร้าง

2. **เลือก Configuration:**
   - `Debug` (สำหรับพัฒนา)

3. **Run:**
   - กด **F5** หรือคลิก ▶️ **Start**
   - รอ build ครั้งแรก (~3-5 นาที)

4. **ผลลัพธ์:**
   - แอพจะเปิดบน Emulator
   - จะเห็น Splash Screen → Login/Home

### 5.4 Build สำหรับ Windows

1. เลือก Target: `Windows Machine`
2. กด F5 เพื่อ Run บน Windows

### 5.5 คำสั่ง dotnet CLI

```cmd
REM เข้าไปที่โฟลเดอร์โปรเจค
cd %USERPROFILE%\Projects\ThaipromptAffiliate-Mobile\ThaipromptAffiliateApp

REM Restore packages
dotnet restore

REM Build
dotnet build

REM Run บน Android
dotnet build -t:Run -f net8.0-android

REM Run บน Windows
dotnet build -t:Run -f net8.0-windows10.0.19041.0
```

---

## 6. การแก้ไขปัญหา

### ❌ "NuGet restore failed"

**วิธีแก้:**

```cmd
cd ThaipromptAffiliateApp
dotnet restore --force
```

หรือใน Visual Studio: `Build > Rebuild Solution`

### ❌ "Android SDK not found"

**วิธีแก้:**

1. `Tools > Options > Xamarin > Android Settings`
2. ตรวจสอบ Android SDK Location
3. ถ้าว่างให้คลิก `...` และเลือกที่ตั้ง SDK

### ❌ "MAUI workload not installed"

**วิธีแก้:**

```cmd
dotnet workload install maui
```

### ❌ "Font file not found"

**วิธีแก้:**

1. ดาวน์โหลด Open Sans จาก Google Fonts
2. คัดลอกไฟล์ `.ttf` ไปที่ `Resources/Fonts/`
3. ตรวจสอบชื่อไฟล์ตรงกับที่กำหนดใน `.csproj`

### ❌ "Emulator ช้ามาก"

**วิธีแก้:**

1. **เปิด Hardware Acceleration:**
   - Intel CPU: ติดตั้ง HAXM
   - AMD CPU: เปิด Hyper-V และ Windows Hypervisor Platform

2. **เพิ่ม Performance:**
   - `Tools > Options > Xamarin > Android Settings`
   - ติ๊ก "Fast Deployment"

3. **ใช้ x86_64 system image แทน ARM**

### ❌ "Cannot connect to API"

**ตรวจสอบ:**

1. Laravel backend กำลังรันอยู่หรือไม่:
   ```bash
   php artisan serve
   ```

2. API URL ถูกต้องหรือไม่:
   - Android Emulator: `http://10.0.2.2:8000/api/v1`
   - Physical Device: `http://YOUR_IP:8000/api/v1`

3. Firewall ไม่บล็อก port 8000

### ❌ "XAML Hot Reload ไม่ทำงาน"

**วิธีแก้:**

1. `Tools > Options > Debugging > Hot Reload`
2. ติ๊ก "Enable XAML Hot Reload"
3. Restart Visual Studio

---

## 📚 ทรัพยากรเพิ่มเติม

### เอกสารโครงการ

- 📖 [README.md](../../../ThaipromptAffiliateApp/README.md) - ภาพรวมแอพ
- 📖 [QUICKSTART.md](../../../ThaipromptAffiliateApp/QUICKSTART.md) - เริ่มต้นเร็ว
- 📖 [DEVELOPMENT.md](../../../ThaipromptAffiliateApp/DEVELOPMENT.md) - คู่มือพัฒนา
- 📖 [MOBILE-APP-API.md](./MOBILE-APP-API.md) - API Endpoints

### เอกสาร Microsoft

- [.NET MAUI Documentation](https://docs.microsoft.com/dotnet/maui/)
- [Visual Studio Docs](https://docs.microsoft.com/visualstudio/)
- [CommunityToolkit.Mvvm](https://learn.microsoft.com/windows/communitytoolkit/mvvm/)

---

## ✅ Checklist - ก่อนเริ่มพัฒนา

- [ ] ติดตั้ง Git for Windows
- [ ] ติดตั้ง .NET 8.0 SDK
- [ ] ติดตั้ง Visual Studio 2022 พร้อม MAUI workload
- [ ] ดึง Mobile App โดยใช้ script หรือคำสั่ง
- [ ] ติดตั้ง Open Sans fonts
- [ ] อัพเดท API URL ใน Constants.cs
- [ ] สร้าง Android Emulator
- [ ] Build และ Run บน Emulator สำเร็จ
- [ ] เชื่อมต่อกับ Backend API ได้

---

## 🚀 คำสั่งด่วน (Copy & Paste)

### Clone และเปิดโปรเจค

```cmd
REM 1. Clone เฉพาะ mobile app (sparse checkout)
mkdir %USERPROFILE%\Projects\ThaipromptMobile && cd %USERPROFILE%\Projects\ThaipromptMobile
git init && git remote add origin https://github.com/xjanova/Thaiprompt-Affiliate.git
git config core.sparseCheckout true
echo ThaipromptAffiliateApp/ > .git\info\sparse-checkout
git pull origin main

REM 2. เข้าไปที่โฟลเดอร์แอพ
cd ThaipromptAffiliateApp

REM 3. Restore packages
dotnet restore

REM 4. Build
dotnet build

REM 5. เปิดใน Visual Studio
start ThaipromptAffiliateApp.sln
```

### Run บน Android

```cmd
dotnet build -t:Run -f net8.0-android
```

### Run บน Windows

```cmd
dotnet build -t:Run -f net8.0-windows10.0.19041.0
```

---

**เวอร์ชัน**: 1.0
**อัปเดตล่าสุด**: พฤศจิกายน 2025
**ผู้เขียน**: Thaiprompt Development Team

**Happy Coding! 🚀**

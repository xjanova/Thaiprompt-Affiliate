# Quick Start Guide - Mobile App Development
## Thai Prompt Affiliate Platform

> คู่มือเริ่มต้นอย่างเร็วสำหรับพัฒนา Mobile App ด้วย Visual Studio

---

## 🚀 เริ่มต้นอย่างรวดเร็ว (15 นาที)

### ขั้นตอนที่ 1: ติดตั้ง Visual Studio 2022

1. **ดาวน์โหลด**: [Visual Studio 2022 Community](https://visualstudio.microsoft.com/downloads/)

2. **เลือก Workloads ในตอนติดตั้ง**:
   - ✅ .NET Multi-platform App UI development
   - ✅ Mobile development with .NET

3. **ติดตั้งและรอสักครู่** (~20-30 นาที)

### ขั้นตอนที่ 2: ตรวจสอบการติดตั้ง

เปิด **Command Prompt** หรือ **PowerShell** และรัน:

```bash
dotnet --version
# ควรแสดง 7.0.x หรือสูงกว่า

dotnet workload list
# ควรเห็น: maui, android, ios
```

### ขั้นตอนที่ 3: สร้างโปรเจคใหม่

#### วิธีที่ 1: ผ่าน Visual Studio (แนะนำ)

1. เปิด **Visual Studio 2022**
2. คลิก **Create a new project**
3. ค้นหา **".NET MAUI App"**
4. ตั้งชื่อโปรเจค: `ThaipromptAffiliate`
5. เลือก Framework: **.NET 7.0** ขึ้นไป
6. คลิก **Create**

#### วิธีที่ 2: ผ่าน Command Line

```bash
# สร้างโปรเจค
dotnet new maui -n ThaipromptAffiliate

# เข้าโฟลเดอร์
cd ThaipromptAffiliate

# เปิด Visual Studio
start ThaipromptAffiliate.sln
```

### ขั้นตอนที่ 4: คัดลอกไฟล์ตัวอย่าง

1. **ดาวน์โหลด Sample Code** จาก:
   ```
   mobile-app-samples/dotnet-maui/
   ```

2. **คัดลอกไฟล์** ไปยังโปรเจคของคุณ:

   | จาก | ไปที่ |
   |-----|------|
   | `mobile-app-samples/dotnet-maui/Models/` | `ThaipromptAffiliate/Models/` |
   | `mobile-app-samples/dotnet-maui/Services/` | `ThaipromptAffiliate/Services/` |
   | `mobile-app-samples/dotnet-maui/ViewModels/` | `ThaipromptAffiliate/ViewModels/` |
   | `mobile-app-samples/dotnet-maui/Views/` | `ThaipromptAffiliate/Views/` |
   | `mobile-app-samples/dotnet-maui/Helpers/` | `ThaipromptAffiliate/Helpers/` |

### ขั้นตอนที่ 5: ติดตั้ง NuGet Packages

1. คลิกขวาที่โปรเจค → **Manage NuGet Packages**

2. ค้นหาและติดตั้ง:
   - `CommunityToolkit.Mvvm`
   - `System.Net.Http.Json`
   - `Newtonsoft.Json`

**หรือ** ใช้ Package Manager Console:

```powershell
Install-Package CommunityToolkit.Mvvm
Install-Package System.Net.Http.Json
Install-Package Newtonsoft.Json
```

### ขั้นตอนที่ 6: อัปเดต API URL

แก้ไขไฟล์ `Helpers/Constants.cs`:

```csharp
// For Android Emulator
public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1";

// For iOS Simulator
// public const string ApiBaseUrl = "http://localhost:8000/api/v1";

// For Physical Device (ใส่ IP ของคอมพิวเตอร์คุณ)
// public const string ApiBaseUrl = "http://192.168.1.100:8000/api/v1";
```

**หา IP ของคอมพิวเตอร์:**

```bash
# Windows
ipconfig

# macOS/Linux
ifconfig
```

### ขั้นตอนที่ 7: ลงทะเบียน Services

แก้ไขไฟล์ `MauiProgram.cs`:

```csharp
using ThaipromptAffiliate.Services;
using ThaipromptAffiliate.ViewModels;
using ThaipromptAffiliate.Views;

public static class MauiProgram
{
    public static MauiApp CreateMauiApp()
    {
        var builder = MauiApp.CreateBuilder();
        builder.UseMauiApp<App>();

        // Register Services
        builder.Services.AddSingleton<IApiService, ApiService>();

        // Register ViewModels
        builder.Services.AddTransient<LoginViewModel>();
        builder.Services.AddTransient<DashboardViewModel>();

        // Register Views
        builder.Services.AddTransient<LoginPage>();
        builder.Services.AddTransient<DashboardPage>();

        return builder.Build();
    }
}
```

### ขั้นตอนที่ 8: ทดสอบแอป

#### สำหรับ Android:

1. เปิด **Android Device Manager**: `Tools > Android > Android Device Manager`

2. สร้าง Virtual Device:
   - Device: **Pixel 5**
   - System Image: **Android 13 (API 33)**
   - Click **Create**

3. เลือก Android Emulator ใน toolbar

4. กด **F5** หรือ **Debug > Start Debugging**

#### สำหรับ iOS (ต้องใช้ macOS):

1. เลือก iOS Simulator ใน toolbar

2. เลือกอุปกรณ์ (เช่น **iPhone 14**)

3. กด **F5** หรือ **Run**

---

## 📱 ทดสอบบนอุปกรณ์จริง

### Android:

1. **เปิด Developer Options** บนมือถือ:
   - Settings > About Phone
   - กด **Build Number** 7 ครั้ง

2. **เปิด USB Debugging**:
   - Settings > Developer Options
   - เปิด **USB Debugging**

3. **เชื่อมต่อมือถือ** กับคอมพิวเตอร์ผ่าน USB

4. **ยอมรับ** USB Debugging prompt บนมือถือ

5. **เลือกอุปกรณ์** ใน Visual Studio และ Run

### iOS:

1. **ต้องมี Apple Developer Account** (ฟรีก็ได้)

2. **เชื่อมต่อ iPhone** กับ Mac

3. **เปิด Xcode** > Settings > Accounts > เพิ่ม Apple ID

4. **Trust Certificate** บน iPhone:
   - Settings > General > Device Management
   - Trust ใบรับรอง

5. **เลือกอุปกรณ์** ใน Visual Studio for Mac และ Run

---

## 🔧 การแก้ปัญหาเบื้องต้น

### ❌ "Android SDK not found"

**วิธีแก้:**
```
Tools > Android > Android SDK Manager
ติดตั้ง Android SDK Platform ที่ต้องการ
```

### ❌ "Emulator is slow"

**วิธีแก้:**
- เปิด Hardware Acceleration (HAXM/WHPX)
- ลด RAM ของ Emulator เหลือ 2048 MB
- ใช้ x86_64 image แทน ARM

### ❌ "Cannot connect to API"

**ตรวจสอบ:**

1. **Laravel backend กำลังรันอยู่หรือไม่:**
   ```bash
   php artisan serve
   ```

2. **API URL ถูกต้องหรือไม่:**
   - Android Emulator: `http://10.0.2.2:8000/api/v1`
   - iOS Simulator: `http://localhost:8000/api/v1`
   - Physical Device: `http://YOUR_IP:8000/api/v1`

3. **Firewall ไม่บล็อกหรือ:**
   - Windows: Allow port 8000 ใน Windows Defender
   - macOS: Allow ใน Security & Privacy

### ❌ "Build failed"

**วิธีแก้:**

1. **Clean Solution:**
   ```
   Build > Clean Solution
   Build > Rebuild Solution
   ```

2. **ลบ bin/obj folders:**
   ```bash
   # PowerShell
   Remove-Item -Recurse -Force bin,obj
   ```

3. **Restore NuGet Packages:**
   ```
   คลิกขวาที่ Solution > Restore NuGet Packages
   ```

---

## 📚 ทรัพยากรเพิ่มเติม

### เอกสารโครงการ:
- 📖 [Full Setup Guide](./MOBILE-APP-VISUAL-STUDIO-SETUP.md) - คู่มือแบบละเอียด
- 📖 [API Documentation](./MOBILE-APP-API.md) - รายละเอียด API Endpoints
- 📖 [Sample Code](./mobile-app-samples/dotnet-maui/) - Code ตัวอย่างพร้อมใช้

### เอกสารภายนอก:
- [.NET MAUI Documentation](https://docs.microsoft.com/dotnet/maui/)
- [CommunityToolkit.Mvvm](https://learn.microsoft.com/windows/communitytoolkit/mvvm/)
- [Visual Studio Docs](https://docs.microsoft.com/visualstudio/)

### วิดีโอสอน (ภาษาอังกฤษ):
- [.NET MAUI for Beginners](https://www.youtube.com/playlist?list=PLdo4fOcmZ0oUBAdL2NwBpDs32zwGqb9DY)
- [Building Mobile Apps with .NET MAUI](https://www.youtube.com/watch?v=DuNLR_NJv8U)

---

## ✅ Checklist - พร้อมใช้งาน

ตรวจสอบว่าคุณทำครบทุกขั้นตอนแล้วหรือยัง:

- [ ] ติดตั้ง Visual Studio 2022 พร้อม .NET MAUI workload
- [ ] ตรวจสอบการติดตั้งด้วย `dotnet --version`
- [ ] สร้างโปรเจค .NET MAUI ใหม่
- [ ] คัดลอก sample code จาก `mobile-app-samples/`
- [ ] ติดตั้ง NuGet packages ที่จำเป็น
- [ ] อัปเดต API URL ใน Constants.cs
- [ ] ลงทะเบียน services ใน MauiProgram.cs
- [ ] ทดสอบ run บน Emulator/Simulator
- [ ] ทดสอบเชื่อมต่อ API กับ Laravel backend

---

## 🎯 ขั้นตอนถัดไป

หลังจากติดตั้งและทดสอบสำเร็จแล้ว:

1. **ปรับแต่ง UI** ตามต้องการ
2. **เพิ่ม Features** เพิ่มเติม (Profile, Notifications, etc.)
3. **ทดสอบ** บนอุปกรณ์จริงหลายรุ่น
4. **เพิ่ม Error Handling** และ Validation
5. **เตรียม Deploy** ไป Play Store/App Store

---

## 💡 Tips สำหรับมือใหม่

1. **ใช้ Hot Reload**: แก้ XAML และดูผลลัพธ์ทันทีโดยไม่ต้อง rebuild

2. **ศึกษา MVVM Pattern**: ทำให้โค้ดจัดการง่ายและทดสอบได้

3. **ใช้ Dependency Injection**: ทำให้ code maintainable และ testable

4. **ทดสอบบนหลาย Platform**: อย่าลืมทดสอบทั้ง Android และ iOS

5. **อ่าน Documentation**: Microsoft Docs มีข้อมูลครบถ้วน

---

## 🆘 ต้องการความช่วยเหลือ?

หากพบปัญหา:

1. ตรวจสอบ [Troubleshooting Section](#🔧-การแก้ปัญหาเบื้องต้น)
2. อ่าน [Full Setup Guide](./MOBILE-APP-VISUAL-STUDIO-SETUP.md)
3. ติดต่อทีมพัฒนา
4. สร้าง Issue ใน GitHub Repository

---

**เวอร์ชัน**: 1.0
**อัปเดตล่าสุด**: พฤศจิกายน 2025
**ผู้เขียน**: Thai Prompt Development Team

**Happy Coding! 🚀**

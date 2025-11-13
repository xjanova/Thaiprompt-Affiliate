# 🚀 Quick Start Guide - Thaiprompt Affiliate Control App

คู่มือเริ่มต้นใช้งานอย่างรวดเร็วสำหรับนักพัฒนา

## ⚡ เริ่มต้นภายใน 5 นาที

### 1. เปิดโปรเจคใน Visual Studio

**Windows:**
```bash
cd ThaipromptAffiliateApp
start ThaipromptAffiliateApp.sln
```

**macOS:**
```bash
cd ThaipromptAffiliateApp
open ThaipromptAffiliateApp.sln
```

### 2. ตั้งค่า API URL

แก้ไขไฟล์ `Helpers/Constants.cs` (บรรทัดที่ 14):

```csharp
// สำหรับ Android Emulator
public const string ApiBaseUrl = "http://10.0.2.2:8000/api/v1";

// สำหรับ iOS Simulator
public const string ApiBaseUrl = "http://localhost:8000/api/v1";

// สำหรับ Physical Device (ใช้ IP ของคอมพิวเตอร์)
public const string ApiBaseUrl = "http://192.168.1.100:8000/api/v1";
```

### 3. Restore Packages

ใน Visual Studio:
- Tools → NuGet Package Manager → Restore NuGet Packages

หรือใช้ Terminal:
```bash
dotnet restore
```

### 4. เลือก Platform และ Run

**Android:**
1. เลือก Framework: `net8.0-android`
2. เลือก Device: Android Emulator หรืออุปกรณ์จริง
3. กด **F5** หรือ **Debug > Start Debugging**

**iOS (ต้อง macOS):**
1. เลือก Framework: `net8.0-ios`
2. เลือก Device: iOS Simulator หรืออุปกรณ์จริง
3. กด **Run**

**Windows:**
1. เลือก Framework: `net8.0-windows10.0.19041.0`
2. กด **F5**

## 📝 ข้อมูลสำหรับทดสอบ

เมื่อแอพเปิดขึ้นมา ให้ใช้ข้อมูลเข้าสู่ระบบจาก Backend ของคุณ:

```
Email: admin@thaiprompt.com
Password: password123
```

## 🎨 หน้าที่มีในแอพ

1. **LoginPage** - หน้าเข้าสู่ระบบ
2. **DashboardPage** - แดชบอร์ดหลัก
3. **CommissionsPage** - รายการคอมมิชชั่น
4. **ReferralsPage** - จัดการผู้แนะนำ
5. **ProfilePage** - โปรไฟล์และการตั้งค่า

## 🔧 การแก้ไขและปรับแต่ง

### เปลี่ยนสีธีม

แก้ไขไฟล์ `Resources/Styles/Colors.xaml`:

```xml
<Color x:Key="Primary">#3B82F6</Color>      <!-- สีหลัก -->
<Color x:Key="Secondary">#10B981</Color>    <!-- สีรอง -->
<Color x:Key="Accent">#8B5CF6</Color>       <!-- สีเน้น -->
```

### เพิ่ม Page ใหม่

1. สร้าง XAML ใน `Views/YourPage.xaml`
2. สร้าง ViewModel ใน `ViewModels/YourViewModel.cs`
3. ลงทะเบียนใน `MauiProgram.cs`:

```csharp
builder.Services.AddTransient<YourViewModel>();
builder.Services.AddTransient<YourPage>();
```

4. เพิ่มใน `AppShell.xaml`:

```xml
<FlyoutItem Title="Your Page" Icon="icon.png">
    <ShellContent Route="yourpage" ContentTemplate="{DataTemplate local:YourPage}" />
</FlyoutItem>
```

### เพิ่ม API Endpoint

แก้ไขไฟล์ `Services/ApiService.cs`:

```csharp
public async Task<YourModel?> GetYourDataAsync()
{
    await EnsureAuthenticatedAsync();
    var response = await _httpClient.GetFromJsonAsync<ApiResponse<YourModel>>("/your-endpoint");
    return response?.Data;
}
```

## 🐛 แก้ปัญหาเบื้องต้น

### ปัญหา: Build Failed

**วิธีแก้:**
```bash
dotnet clean
dotnet restore
dotnet build
```

### ปัญหา: API ไม่เชื่อมต่อ (Android)

**วิธีแก้:**
- ใช้ `10.0.2.2` แทน `localhost`
- ตรวจสอบว่า Backend รันอยู่
- ตรวจสอบ Firewall

### ปัญหา: iOS Build Failed

**วิธีแก้:**
- ตรวจสอบ Xcode ติดตั้งแล้ว
- รัน: `sudo xcode-select --switch /Applications/Xcode.app`
- ลบ `bin/` และ `obj/` แล้ว rebuild

### ปัญหา: Android Emulator ช้า

**วิธีแก้:**
- เปิด Hardware Acceleration (HAXM/WHPX)
- ใช้ x86_64 image
- ลด RAM ของ Emulator เหลือ 2048MB

## 📱 คำแนะนำสำหรับการพัฒนา

### Hot Reload

.NET MAUI รองรับ Hot Reload:
1. กด **Alt+F10** (Windows) หรือ **Cmd+\** (Mac)
2. แก้ไข XAML และเห็นการเปลี่ยนแปลงทันที

### Debugging

- ตั้ง Breakpoint ด้วยการกด **F9**
- ดู Console Output ใน Output Window
- ใช้ Debug Console เพื่อทดสอบ expressions

### XAML Hot Reload

เมื่อแก้ไข XAML ระหว่าง Debug:
- ไม่ต้อง restart แอพ
- การเปลี่ยนแปลงจะปรากฏทันที
- ไม่รองรับการเปลี่ยน code-behind

## 📚 Resources

- [README.md](README.md) - เอกสารฉบับเต็ม
- [API Documentation](../MOBILE-APP-API.md) - เอกสาร API
- [.NET MAUI Docs](https://docs.microsoft.com/dotnet/maui/)

## 💡 Tips & Tricks

1. **ใช้ Shell Navigation:**
   ```csharp
   await Shell.Current.GoToAsync("//dashboard");
   ```

2. **ใช้ Dependency Injection:**
   ```csharp
   public MyPage(IApiService apiService)
   {
       _apiService = apiService;
   }
   ```

3. **Async/Await เสมอ:**
   ```csharp
   public async Task LoadDataAsync()
   {
       var data = await _apiService.GetDataAsync();
   }
   ```

4. **ใช้ Try-Catch:**
   ```csharp
   try
   {
       await LoadDataAsync();
   }
   catch (Exception ex)
   {
       await DisplayAlert("Error", ex.Message, "OK");
   }
   ```

## 🎯 Next Steps

1. ✅ เปิดโปรเจคใน Visual Studio
2. ✅ ตั้งค่า API URL
3. ✅ Run แอพบน Emulator
4. ✅ ทดสอบ Login
5. ✅ สำรวจ Features ต่างๆ
6. 🚀 เริ่มพัฒนาฟีเจอร์ของคุณ!

---

**Happy Coding! 💻✨**

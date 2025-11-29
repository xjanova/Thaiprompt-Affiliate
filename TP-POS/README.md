# 🏪 TP-POS - ระบบขายหน้าร้าน

## 📋 รายละเอียดโปรเจค

**TP-POS** คือระบบ Point of Sale (POS) แบบ Cross-Platform ที่พัฒนาด้วย **.NET MAUI** และ **C#** สามารถรันได้บน:

- ✅ **Windows** - Desktop Application (.exe)
- ✅ **iOS** - iPhone/iPad Application
- ✅ **Android** - Mobile Application (.apk)

---

## 🚀 เริ่มต้นใช้งาน (Quick Start)

### ความต้องการของระบบ

| รายการ | เวอร์ชันขั้นต่ำ |
|--------|---------------|
| **Visual Studio 2022/2026** | 17.8+ |
| **.NET SDK** | 8.0+ |
| **Windows** | 10 (19041) ขึ้นไป |
| **RAM** | 8GB+ (แนะนำ 16GB) |
| **พื้นที่ว่าง** | 20GB+ |

### ติดตั้ง Workloads ใน Visual Studio

เปิด **Visual Studio Installer** และติดตั้ง:

1. ✅ **.NET Multi-platform App UI development** (MAUI)
2. ✅ **Mobile development with .NET**
3. ✅ **.NET desktop development** (สำหรับ Windows)

หรือใช้คำสั่ง:

```powershell
# ติดตั้ง MAUI workload
dotnet workload install maui

# ติดตั้ง Android
dotnet workload install android

# ติดตั้ง iOS (ต้องใช้ Mac)
dotnet workload install ios
```

---

## 📂 โครงสร้างโปรเจค

```
📁 TP-POS/
├── 📁 TP.POS.App/              # .NET MAUI Application
│   ├── 📁 Views/               # หน้าจอ UI (XAML)
│   ├── 📁 ViewModels/          # Business Logic (MVVM)
│   ├── 📁 Services/            # Service Implementations
│   ├── 📁 Models/              # Display Models
│   ├── 📁 Resources/           # Resources (Images, Fonts, Styles)
│   └── App.xaml                # Application Entry
│
├── 📁 TP.POS.Core/             # Core Library
│   ├── 📁 Entities/            # Data Models (Product, Transaction, etc.)
│   ├── 📁 Interfaces/          # Service Contracts
│   └── 📁 Enums/               # Enumerations
│
├── 📁 TP.POS.Infrastructure/   # Infrastructure Layer
│   ├── 📁 Data/                # SQLite Database
│   ├── 📁 Api/                 # HTTP API Client
│   ├── 📁 Hardware/            # Printer, Scanner
│   └── 📁 Sync/                # Offline Sync
│
└── TP-POS.sln                  # Visual Studio Solution
```

---

## 🔧 การเปิดโปรเจคใน Visual Studio

### วิธีที่ 1: เปิดจาก File Explorer

1. ไปที่โฟลเดอร์ `TP-POS`
2. ดับเบิลคลิกที่ไฟล์ **`TP-POS.sln`**
3. Visual Studio จะเปิดโปรเจคโดยอัตโนมัติ

### วิธีที่ 2: เปิดจาก Visual Studio

1. เปิด Visual Studio
2. เลือก **Open a project or solution**
3. เลือกไฟล์ `TP-POS.sln`

---

## ▶️ การรันโปรเจค

### รันบน Windows

1. ที่ Toolbar เลือก Target: **Windows Machine**
2. เลือก Configuration: **Debug** หรือ **Release**
3. กด **F5** หรือคลิก **▶ Start**

```
[Windows Machine] ▼  |  Debug ▼  |  ▶ Start
```

### รันบน Android Emulator

1. เปิด **Android Device Manager** (Tools > Android > Android Device Manager)
2. สร้าง Emulator ใหม่ หรือเลือกที่มีอยู่
3. ที่ Toolbar เลือก Target: **Android Emulator - [ชื่อ Emulator]**
4. กด **F5**

### รันบน Android Device จริง

1. เปิด **Developer Mode** และ **USB Debugging** บน Android
2. เชื่อมต่อมือถือกับคอมพิวเตอร์ผ่าน USB
3. ที่ Toolbar เลือก Target: **[ชื่อ Device]**
4. กด **F5**

### รันบน iOS (ต้องใช้ Mac)

1. เชื่อมต่อกับ Mac ผ่าน **Pair to Mac**
2. เลือก iOS Simulator หรือ Device
3. กด **F5**

---

## 💾 การตั้งค่า API Server

แก้ไขไฟล์ `TP.POS.App/MauiProgram.cs`:

```csharp
// เปลี่ยน URL เป็น Server ของคุณ
services.AddSingleton(new TpAffiliateApiClient("https://your-server.com"));
```

หรือสร้างไฟล์ `appsettings.json`:

```json
{
  "ApiSettings": {
    "BaseUrl": "https://your-server.com",
    "Timeout": 30
  }
}
```

---

## 📱 ฟีเจอร์หลัก

### ✅ ฟีเจอร์ที่พร้อมใช้งาน

| ฟีเจอร์ | สถานะ |
|---------|-------|
| 🛒 หน้าขายสินค้า (POS) | ✅ |
| 📦 จัดการสินค้า/สต็อก | ✅ |
| 👥 จัดการลูกค้า | ✅ |
| 🧾 พิมพ์ใบเสร็จ | ✅ |
| 📷 สแกนบาร์โค้ด | ✅ |
| 📴 ทำงาน Offline | ✅ |
| 🔄 Sync ข้อมูลอัตโนมัติ | ✅ |
| 📊 รายงานยอดขาย | ✅ |

### 🔲 ฟีเจอร์ในอนาคต

- [ ] ระบบส่วนลด/คูปอง
- [ ] สะสมแต้ม Loyalty
- [ ] หลายสาขา (Multi-store)
- [ ] จัดการพนักงาน
- [ ] Export ข้อมูล Excel/PDF

---

## 🖨️ การเชื่อมต่อเครื่องพิมพ์

### Thermal Printer (ESC/POS)

รองรับเครื่องพิมพ์ที่ใช้โปรโตคอล ESC/POS:
- USB Printers
- Network/Ethernet Printers
- Bluetooth Printers (Mobile)

### การตั้งค่า

ไปที่ **ตั้งค่า > เครื่องพิมพ์** และเลือกเครื่องพิมพ์ที่ต้องการ

---

## 📖 โครงสร้าง MVVM

โปรเจคใช้ **MVVM Pattern** (Model-View-ViewModel):

```
┌─────────────┐      ┌─────────────┐      ┌─────────────┐
│    View     │ ←──→ │  ViewModel  │ ←──→ │   Service   │
│   (XAML)    │      │   (C#)      │      │   (C#)      │
└─────────────┘      └─────────────┘      └─────────────┘
                            ↓
                     ┌─────────────┐
                     │   Database  │
                     │  (SQLite)   │
                     └─────────────┘
```

### ตัวอย่างการเพิ่มหน้าใหม่

1. **สร้าง View** (XAML):
```xml
<!-- Views/NewPage.xaml -->
<ContentPage xmlns="..." x:Class="TP.POS.App.Views.NewPage">
    <Label Text="Hello" />
</ContentPage>
```

2. **สร้าง ViewModel**:
```csharp
// ViewModels/NewPageViewModel.cs
public partial class NewPageViewModel : BaseViewModel
{
    [ObservableProperty]
    private string _message = "Hello";
}
```

3. **ลงทะเบียนใน MauiProgram.cs**:
```csharp
services.AddTransient<NewPage>();
services.AddTransient<NewPageViewModel>();
```

4. **เพิ่ม Route** (ถ้าต้องการ):
```csharp
Routing.RegisterRoute("new-page", typeof(NewPage));
```

---

## 🔌 การเชื่อมต่อกับ TP-Affiliate API

### Endpoints ที่ใช้

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/pos/api/products/all` | GET | ดึงสินค้าทั้งหมด |
| `/pos/api/categories/all` | GET | ดึงหมวดหมู่ |
| `/pos/api/customers/all` | GET | ดึงลูกค้า |
| `/pos/api/transactions/sync` | POST | Sync รายการขาย |
| `/pos/api/stock/sync` | POST | Sync สต็อก |

### Authentication

ใช้ **Laravel Sanctum Token**:

```csharp
var client = new TpAffiliateApiClient("https://your-server.com");
await client.LoginAsync("email@example.com", "password");
// Token จะถูกตั้งค่าอัตโนมัติ
```

---

## 🐛 การ Debug

### Debug บน Windows

1. ตั้ง Breakpoint ในโค้ด (คลิกที่ขอบซ้าย)
2. กด **F5** เพื่อเริ่ม Debug
3. ใช้ **Debug Console** ดู Output

### Debug บน Android

1. เปิด **Logcat** (View > Other Windows > Device Log)
2. Filter ด้วย Tag: `TP.POS`
3. ดู Log จากแอป

### Hot Reload

เปิดใช้งาน **XAML Hot Reload** เพื่อดูการเปลี่ยนแปลง UI แบบ Real-time:

1. ไปที่ **Tools > Options > Debugging > Hot Reload**
2. เปิด **Enable XAML Hot Reload**

---

## 📦 การ Build และ Publish

### Build สำหรับ Windows

```bash
dotnet publish -f net8.0-windows10.0.19041.0 -c Release
```

### Build สำหรับ Android (.apk)

```bash
dotnet publish -f net8.0-android -c Release
```

### Build สำหรับ iOS

```bash
dotnet publish -f net8.0-ios -c Release
```

---

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:

1. ตรวจสอบ **Issues** ใน GitHub
2. อ่าน **Documentation** ใน `.claude/` folder
3. ติดต่อทีมพัฒนา

---

## 📄 License

© 2024 TP-Affiliate. All rights reserved.

---

**Version:** 1.0.0
**Last Updated:** 2024-11-29

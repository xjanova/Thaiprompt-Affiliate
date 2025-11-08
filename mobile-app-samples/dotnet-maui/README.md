# .NET MAUI Sample Code for Thaiprompt Affiliate

## Overview
ไฟล์ตัวอย่างสำหรับการพัฒนา Mobile App ด้วย .NET MAUI สำหรับ Thai Prompt Affiliate Platform

## โครงสร้าง
```
dotnet-maui/
├── Models/              # Data models สำหรับ API responses
├── Services/            # API services และ business logic
├── ViewModels/          # MVVM ViewModels
├── Views/               # XAML UI pages
├── Helpers/             # Utility classes และ constants
└── README.md           # เอกสารนี้
```

## วิธีใช้งาน

1. **สร้างโปรเจค .NET MAUI ใหม่** ตามขั้นตอนใน [MOBILE-APP-VISUAL-STUDIO-SETUP.md](../../MOBILE-APP-VISUAL-STUDIO-SETUP.md)

2. **คัดลอกไฟล์ตัวอย่าง** ไปยังโปรเจคของคุณ:
   - Models → โปรเจค/Models/
   - Services → โปรเจค/Services/
   - ViewModels → โปรเจค/ViewModels/
   - Views → โปรเจค/Views/
   - Helpers → โปรเจค/Helpers/

3. **ปรับแต่ง namespace** ให้ตรงกับโปรเจคของคุณ

4. **ติดตั้ง NuGet packages** ที่จำเป็น:
   ```
   CommunityToolkit.Mvvm
   System.Net.Http.Json
   Newtonsoft.Json
   ```

5. **อัปเดต API URL** ใน `Helpers/Constants.cs`

## Features ที่รวมไว้

### Models
- ✅ User model
- ✅ LoginResponse model
- ✅ DashboardStats model
- ✅ Commission model
- ✅ Referral model

### Services
- ✅ ApiService - HTTP client wrapper
- ✅ AuthService - Authentication management
- ✅ Secure storage integration

### ViewModels
- ✅ BaseViewModel - Base class with IsBusy
- ✅ LoginViewModel - Login logic
- ✅ DashboardViewModel - Dashboard data

### Views
- ✅ LoginPage - Login UI
- ✅ DashboardPage - Dashboard UI
- ✅ Sample XAML layouts

## การทดสอบ

1. รัน Laravel backend ที่ `http://localhost:8000`
2. อัปเดต API URL ใน Constants.cs
3. Run แอปบน emulator/simulator
4. ทดสอบการ login ด้วย credentials ที่มีอยู่

## หมายเหตุ

- ไฟล์เหล่านี้เป็น **ตัวอย่าง** เท่านั้น
- ควรปรับแต่งให้เหมาะกับความต้องการของโปรเจค
- อย่าลืม implement error handling และ validation
- ทดสอบให้ครบทุก platform (Android, iOS)

## เอกสารเพิ่มเติม

- [Installation Guide](../../MOBILE-APP-VISUAL-STUDIO-SETUP.md)
- [API Documentation](../../MOBILE-APP-API.md)
- [.NET MAUI Docs](https://docs.microsoft.com/dotnet/maui/)

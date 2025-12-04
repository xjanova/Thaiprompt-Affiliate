# 📱 Thaiprompt Affiliate Mobile App

> React Native + Expo Mobile App สำหรับ Thaiprompt Affiliate Platform

---

## 🚀 Quick Start (5 นาที)

### ขั้นตอนที่ 1: สมัคร Expo Account (ฟรี)

```bash
# ไปที่
https://expo.dev/signup

# สมัครด้วย Email หรือ GitHub
```

### ขั้นตอนที่ 2: สร้าง Access Token

```bash
# 1. Login ที่ https://expo.dev
# 2. ไปที่ Settings → Access Tokens
# 3. กด "Create Token"
# 4. ตั้งชื่อ: "GitHub Actions"
# 5. Copy token ไว้
```

### ขั้นตอนที่ 3: เพิ่ม Token ใน GitHub

```bash
# 1. ไปที่ GitHub repo → Settings → Secrets and variables → Actions
# 2. กด "New repository secret"
# 3. Name: EXPO_TOKEN
# 4. Value: [paste token จากขั้นตอน 2]
# 5. กด "Add secret"
```

### ขั้นตอนที่ 4: Build App! 🎉

```bash
# สร้าง tag เพื่อ trigger build
git tag mobile-v1.0.0
git push origin mobile-v1.0.0

# หรือไปที่ GitHub Actions → Mobile App Build → Run workflow
```

---

## 📦 โครงสร้างโปรเจค

```
mobile-app/
├── app/                      # Screens (Expo Router)
│   ├── _layout.tsx          # Root layout
│   ├── index.tsx            # Hub Selection (หน้าหลัก)
│   ├── login.tsx            # Login
│   └── dashboard.tsx        # Dashboard
├── components/              # Reusable components
│   ├── Button.tsx
│   ├── Input.tsx
│   ├── HubCard.tsx
│   └── LoadingScreen.tsx
├── constants/               # Constants & config
│   └── index.ts
├── services/                # API services
│   └── api.ts
├── stores/                  # State management (Zustand)
│   ├── authStore.ts
│   └── appStore.ts
├── types/                   # TypeScript types
│   └── index.ts
├── assets/                  # Images, fonts
├── app.json                 # Expo config
├── eas.json                 # EAS Build config
├── tailwind.config.js       # NativeWind config
└── package.json
```

---

## 🛠️ Development

### ติดตั้ง Dependencies

```bash
cd mobile-app
npm install
```

### รัน Development Server

```bash
# Start Expo
npm start

# หรือ
npx expo start
```

### ทดสอบบนมือถือ

```bash
# 1. ติดตั้ง "Expo Go" app บนมือถือ
#    - Android: Play Store
#    - iOS: App Store

# 2. Scan QR Code จาก terminal
```

---

## 🏗️ Build Options

### Option 1: EAS Cloud Build (แนะนำ)

```bash
# ติดตั้ง EAS CLI
npm install -g eas-cli

# Login
eas login

# Build Android APK
eas build --platform android --profile preview

# Build iOS
eas build --platform ios --profile preview

# Build ทั้งสอง
eas build --platform all --profile preview
```

### Option 2: GitHub Actions (Auto)

```bash
# สร้าง tag
git tag mobile-v1.0.0
git push origin mobile-v1.0.0

# GitHub Actions จะ build อัตโนมัติ
# ดาวน์โหลด APK จาก https://expo.dev
```

### Option 3: Local Build

```bash
# Android (ต้องมี Android Studio)
npx expo prebuild --platform android
cd android && ./gradlew assembleRelease

# iOS (ต้องมี Mac + Xcode)
npx expo prebuild --platform ios
cd ios && xcodebuild -workspace ...
```

---

## 📱 Build Profiles

| Profile | ใช้สำหรับ | Output |
|---------|----------|--------|
| `development` | Development/Debug | APK (debug) |
| `preview` | ทดสอบ/แชร์ทีม | APK (release) |
| `production` | Production/Store | AAB (Android) / IPA (iOS) |

```bash
# ตัวอย่าง
eas build --platform android --profile preview
eas build --platform android --profile production
```

---

## ⚙️ Configuration

### เปลี่ยน API URL

แก้ไขไฟล์ `constants/index.ts`:

```typescript
export const API_BASE_URL = __DEV__
  ? 'http://10.0.2.2:8000/api/v1'  // Development
  : 'https://your-domain.com/api/v1';  // Production ← เปลี่ยนตรงนี้
```

### เปลี่ยน App ID

แก้ไขไฟล์ `app.json`:

```json
{
  "expo": {
    "ios": {
      "bundleIdentifier": "com.yourcompany.yourapp"
    },
    "android": {
      "package": "com.yourcompany.yourapp"
    }
  }
}
```

### เพิ่ม App Icon

1. สร้างไฟล์รูปภาพ:
   - `assets/images/icon.png` (1024x1024)
   - `assets/images/splash-icon.png` (288x288)
   - `assets/images/adaptive-icon.png` (1024x1024)

2. ใช้เครื่องมือ: https://icon.kitchen หรือ https://appicon.co

---

## 📋 Features

### ✅ Implemented
- [x] Hub Selection (8 services)
- [x] Login / Authentication
- [x] Dashboard
- [x] JWT Token Management
- [x] Dark Mode Support
- [x] Thai Language UI
- [x] Pull to Refresh

### 🚧 Coming Soon
- [ ] Register
- [ ] Profile
- [ ] Wallet
- [ ] Commissions List
- [ ] Referrals Tree
- [ ] Push Notifications
- [ ] Biometric Auth
- [ ] Offline Support

---

## 🔧 Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| React Native | 0.76 | Mobile framework |
| Expo | 52 | Development platform |
| Expo Router | 4 | File-based navigation |
| NativeWind | 4 | Tailwind CSS for RN |
| Zustand | 4 | State management |
| Axios | 1.6 | HTTP client |

---

## 📞 Support

- **Issues**: https://github.com/xjanova/Thaiprompt-Affiliate/issues
- **Docs**: https://docs.expo.dev

---

## 📄 License

Private - Thaiprompt © 2024

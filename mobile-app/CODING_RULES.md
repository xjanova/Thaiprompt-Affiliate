# Mobile App Coding Rules

## กฎสำคัญสำหรับการพัฒนา Mobile App (React Native + Expo)

---

## 1. LinearGradient - ห้ามใช้ className

**ปัญหา:** NativeWind v2 ไม่รองรับ `className` บน third-party components เช่น `LinearGradient` จาก `expo-linear-gradient`

**อาการ:** หน้าจอขาว (White Screen) เมื่อเปิดหน้าที่มี LinearGradient ใช้ className

### ❌ ห้ามทำ (Wrong)
```tsx
<LinearGradient
  colors={['#3B82F6', '#2563EB']}
  className="px-6 py-4 rounded-2xl"
>
  <Text>Content</Text>
</LinearGradient>
```

### ✅ ต้องทำ (Correct)
```tsx
<LinearGradient
  colors={['#3B82F6', '#2563EB']}
  style={{
    paddingHorizontal: 24,
    paddingVertical: 16,
    borderRadius: 16,
  }}
>
  <Text>Content</Text>
</LinearGradient>
```

### การแปลง Tailwind Classes เป็น Style

| Tailwind Class | Style Property |
|----------------|----------------|
| `px-6` | `paddingHorizontal: 24` |
| `py-4` | `paddingVertical: 16` |
| `p-4` | `padding: 16` |
| `rounded-2xl` | `borderRadius: 16` |
| `rounded-full` | `borderRadius: 9999` หรือ `borderRadius: width/2` |
| `w-12` | `width: 48` |
| `h-12` | `height: 48` |
| `items-center` | `alignItems: 'center'` |
| `justify-center` | `justifyContent: 'center'` |
| `flex-row` | `flexDirection: 'row'` |
| `mb-4` | `marginBottom: 16` |
| `mt-6` | `marginTop: 24` |

### Components ที่ต้องใช้ style แทน className

- `LinearGradient` (expo-linear-gradient)
- `BlurView` (expo-blur)
- Components อื่นๆ จาก third-party libraries

---

## 2. Third-Party Components - ใช้ style เสมอ

Components จาก libraries ภายนอก (ไม่ใช่ React Native core) ควรใช้ `style` prop เสมอ:

```tsx
// expo-blur
<BlurView intensity={50} style={{ borderRadius: 16, padding: 20 }}>

// expo-linear-gradient
<LinearGradient colors={[...]} style={{ borderRadius: 16 }}>

// react-native-maps (ถ้าใช้)
<MapView style={{ flex: 1, height: 300 }}>
```

---

## 3. Dark Mode Support

ทุกหน้าต้องรองรับ Dark Mode:

```tsx
const { resolvedTheme } = useAppStore();
const isDark = resolvedTheme === 'dark';

// ใช้กับ className
<View className={`${isDark ? 'bg-dark' : 'bg-gray-50'}`}>

// ใช้กับ style
<LinearGradient
  colors={isDark ? ['#1E3A8A', '#1E40AF'] : ['#3B82F6', '#2563EB']}
  style={{ ... }}
>
```

---

## 4. Safe Area

ใช้ `SafeAreaView` จาก `react-native-safe-area-context` เสมอ:

```tsx
import { SafeAreaView } from 'react-native-safe-area-context';

<SafeAreaView className="flex-1" edges={['top']}>
  {/* Content */}
</SafeAreaView>
```

---

## 5. Navigation

ใช้ Expo Router สำหรับ navigation:

```tsx
import { router } from 'expo-router';

// Navigate
router.push('/dashboard');
router.push('/shopping');
router.back();

// Routes ต้องตรงกับ file path
// /app/dashboard.tsx → '/dashboard'
// /app/(tabs)/wallet.tsx → '/(tabs)/wallet'
```

---

## 6. Animations

ใช้ `react-native-reanimated` สำหรับ animations:

```tsx
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';

<Animated.View entering={FadeInDown.delay(200)}>
  {/* Animated content */}
</Animated.View>
```

---

## 7. Icons

ใช้ `@expo/vector-icons` (Ionicons):

```tsx
import { Ionicons } from '@expo/vector-icons';

<Ionicons name="home" size={24} color="#3B82F6" />
<Ionicons name="settings-outline" size={24} color={isDark ? '#fff' : '#000'} />
```

---

## 8. State Management

ใช้ Zustand stores:

```tsx
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';

const { resolvedTheme, themeMode, setThemeMode } = useAppStore();
const { user, isAuthenticated, login, logout } = useAuthStore();
```

---

## 9. API Calls

ใช้ functions จาก `@/services/api`:

```tsx
import { getDashboardStats, getProducts } from '@/services/api';

const stats = await getDashboardStats();
const products = await getProducts();
```

---

## 10. Permissions (app.json)

เมื่อใช้ features ที่ต้องการ permissions:

- **Camera:** ต้องเพิ่ม `expo-camera` และ `expo-image-picker` plugins
- **Location:** ต้องเพิ่ม `expo-location` plugin
- **Notifications:** ต้องเพิ่ม `expo-notifications` plugin

หลังแก้ไข `app.json` ต้อง rebuild app ใหม่เสมอ!

---

## Quick Reference - Tailwind to Style

```
4 = 16px
5 = 20px
6 = 24px
8 = 32px
10 = 40px
12 = 48px
14 = 56px
16 = 64px

rounded-lg = 8
rounded-xl = 12
rounded-2xl = 16
rounded-3xl = 24
rounded-full = 9999
```

---

**อัพเดทล่าสุด:** 2025-12-06

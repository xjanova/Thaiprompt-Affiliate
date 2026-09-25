/**
 * App Configuration — ค่าคงที่ของแอป + Feature Flags
 *
 * Feature Flags ถูกอ่านจริงในแอป (แท็บ / หน้าแรก / โปรไฟล์ / กระเป๋าเงิน) ผ่าน isFeatureEnabled()
 * เปลี่ยนค่าแล้วต้องออก build ใหม่
 *
 * ⚠️ นโยบาย Google Play (build ที่ขึ้นสโตร์):
 *   - ห้ามมีเนื้อหา MLM / สายงาน / rank / PV / คอมมิชชั่นหลายชั้น / คริปโต / ลงทุน / ดูคลิปได้เงิน ในแอป
 *   - ทุกอย่างที่เกี่ยวกับเครือข่ายอยู่บนเว็บไซต์ แอปมีแค่ปุ่ม "จัดการบนเว็บไซต์" (WebsiteButton)
 *   - ห้ามขายสินค้าดิจิทัล (เช่น ดูดวงแบบเสียเงิน) ผ่านกระเป๋าเงิน/ช่องทางอื่นที่ไม่ใช่ Play Billing
 */

// =====================================================
// App Info
// =====================================================

export const APP_INFO = {
  NAME: 'TP UltraAPP',
  VERSION: '3.384.0',
  BUILD_NUMBER: 41,
  BUILD_DATE: '2026-09-25',
  BUNDLE_ID: 'com.thaiprompt.affiliate',

  // App URLs
  WEBSITE: 'https://main.thaiprompt.online',
  SUPPORT_EMAIL: 'support@thaiprompt.online',
  TERMS_URL: 'https://main.thaiprompt.online/terms-of-service',
  PRIVACY_URL: 'https://main.thaiprompt.online/privacy-policy',
  /** หน้าอธิบายการลบบัญชี (กรอกใน Play Console > Data safety) */
  ACCOUNT_DELETION_URL: 'https://main.thaiprompt.online/account/delete',
};

// =====================================================
// Feature Flags (Local)
// =====================================================

export const FEATURES = {
  // Authentication
  LINE_LOGIN_ENABLED: true,
  FACEBOOK_LOGIN_ENABLED: false,
  GOOGLE_LOGIN_ENABLED: false,
  APPLE_LOGIN_ENABLED: false,

  // บริการหลักในแอป
  WALLET_ENABLED: true,
  KYC_ENABLED: true,
  RIDER_ENABLED: true,
  SHOPPING_ENABLED: true,
  TALADSOD_ENABLED: true,
  MERCHANT_ENABLED: true,
  /** ชวนเพื่อนแบบชั้นเดียว (รหัส + QR + ลิงก์) */
  REFERRAL_ENABLED: true,
  /** ดูดวงไพ่ทาโรต์ — ฟรีในแอปเท่านั้น */
  TAROT_ENABLED: true,
  SUPPORT_TICKETS_ENABLED: true,
  PUSH_NOTIFICATIONS_ENABLED: true,

  // ปิดถาวรใน build สโตร์ (นโยบาย Google Play)
  /** MLM / สายงาน / rank / คอมมิชชั่นหลายชั้น → อยู่บนเว็บเท่านั้น */
  MLM_ENABLED: false,
  /** โอนเงินระหว่างผู้ใช้ (P2P) — ต้องทำ Financial features declaration ก่อนเปิด */
  P2P_TRANSFER_ENABLED: false,
  /** ดูดวงแบบเสียเงินผ่านกระเป๋าเงิน (ต้องใช้ Play Billing) */
  PAID_TAROT_ENABLED: false,
  /** แชร์ตำแหน่งให้แอดมิน (เลิกใช้ — ไรเดอร์ใช้ระบบติดตามระหว่างส่งงานแทน) */
  ADMIN_GPS_SHARING_ENABLED: false,
  /** คริปโต / โทเคน / ลงทุน / ดูคลิปได้เงิน */
  CRYPTO_ENABLED: false,

  // App Behaviors
  OFFLINE_MODE_ENABLED: true,
  DARK_MODE_ENABLED: true,
};

export type FeatureFlag = keyof typeof FEATURES;

/**
 * ตรวจสอบว่าฟีเจอร์เปิดใช้งานหรือไม่
 *
 * @example if (isFeatureEnabled('P2P_TRANSFER_ENABLED')) { ... }
 */
export const isFeatureEnabled = (feature: FeatureFlag): boolean => FEATURES[feature] ?? false;

// =====================================================
// Theme Configuration (Legacy — หน้าจอใหม่ใช้ '@/theme')
// =====================================================

export const THEME_CONFIG = {
  PRIMARY: '#E6B347',
  PRIMARY_DARK: '#B8892A',
  PRIMARY_LIGHT: '#F6ECCF',

  SECONDARY: '#10B981',
  SECONDARY_DARK: '#059669',
  SECONDARY_LIGHT: '#34D399',

  ACCENT: '#D98E3F',
  ACCENT_DARK: '#B06F2F',
  ACCENT_LIGHT: '#F4E3CC',

  SUCCESS: '#34A96A',
  WARNING: '#D98324',
  ERROR: '#D9483B',
  INFO: '#3A7BC0',

  DARK: {
    BACKGROUND: '#1D1912',
    SURFACE: '#272118',
    CARD: '#2D261C',
    BORDER: '#352B1D',
    TEXT_PRIMARY: '#ECE3D2',
    TEXT_SECONDARY: '#A89C84',
    TEXT_MUTED: '#7D7260',
  },

  LIGHT: {
    BACKGROUND: '#F3EEE4',
    SURFACE: '#ECE5D8',
    CARD: '#F8F4EC',
    BORDER: '#CDC1AD',
    TEXT_PRIMARY: '#544C40',
    TEXT_SECONDARY: '#857A67',
    TEXT_MUTED: '#A89E8B',
  },
};

/** ดึงชุดสีตามโหมด (legacy) */
export const getThemeColors = (isDark: boolean) => (isDark ? THEME_CONFIG.DARK : THEME_CONFIG.LIGHT);

// =====================================================
// API Configuration
// =====================================================

export const API_CONFIG = {
  PRODUCTION_URL: 'https://main.thaiprompt.online/api/v1',
  DEVELOPMENT_URL: 'https://member123.thaiprompt.online/api/v1',

  DEFAULT_TIMEOUT: 30000,
  UPLOAD_TIMEOUT: 120000,

  MAX_RETRIES: 3,
  RETRY_DELAY: 1000,

  CACHE_DURATION: 5 * 60 * 1000,
  OFFLINE_CACHE_DURATION: 24 * 60 * 60 * 1000,
};

// =====================================================
// UI Configuration
// =====================================================

export const UI_CONFIG = {
  ANIMATION_SHORT: 150,
  ANIMATION_MEDIUM: 300,
  ANIMATION_LONG: 500,
  MIN_TOUCH_SIZE: 44,
  CARD_BORDER_RADIUS: 16,
  SPACING_XS: 4,
  SPACING_SM: 8,
  SPACING_MD: 16,
  SPACING_LG: 24,
  SPACING_XL: 32,
};

// =====================================================
// Validation Rules
// =====================================================

export const VALIDATION = {
  PASSWORD_MIN_LENGTH: 8,
  PASSWORD_MAX_LENGTH: 50,
  USERNAME_MIN_LENGTH: 3,
  USERNAME_MAX_LENGTH: 50,
  PHONE_PATTERN: /^[0-9]{10}$/,
  EMAIL_PATTERN: /^[^@\s]+@[^@\s]+\.[^@\s]+$/,
  ID_CARD_PATTERN: /^[0-9]{13}$/,
};

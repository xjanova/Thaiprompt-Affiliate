/**
 * App Configuration - ตั้งค่าแอพแบบ Standalone
 *
 * =====================================================
 * 🎯 หลักการออกแบบ: แอพอิสระ (Standalone App)
 * =====================================================
 *
 * แอพนี้ถูกออกแบบให้เป็นอิสระจากระบบควบคุมจากเซิร์ฟเวอร์
 * การตั้งค่าทั้งหมดอยู่ในไฟล์นี้ สามารถแก้ไขได้ในแอพโดยตรง
 *
 * =====================================================
 * 🔐 สิ่งที่ Admin ควบคุมได้ (จำกัดเฉพาะ 3 อย่างเท่านั้น)
 * =====================================================
 *
 * 1. 📢 Push Notifications
 *    - ส่งข้อความแจ้งเตือนไปยังผู้ใช้
 *    - ส่งข้อความโปรโมชั่น, ข่าวสาร
 *    - กำหนดกลุ่มเป้าหมาย
 *
 * 2. 🖼️ Banner โฆษณา (หน้าช้อปปิ้ง)
 *    - เพิ่ม/แก้ไข/ลบ banner โฆษณา
 *    - กำหนดตำแหน่ง, ลิงก์, ระยะเวลาแสดง
 *    - ติดตามการคลิก
 *
 * 3. 📊 Device Analytics Dashboard
 *    - ดูจำนวนเครื่องที่ลงทะเบียน
 *    - สถิติ iOS/Android
 *    - Daily/Monthly Active Users
 *    - Retention Rate
 *
 * =====================================================
 * ❌ สิ่งที่ Admin ควบคุมไม่ได้
 * =====================================================
 *
 * - Feature Flags (เปิด/ปิดฟีเจอร์) - ควบคุมในแอพ
 * - Rank Configuration - ตั้งค่าในแอพ
 * - Theme/Colors - ตั้งค่าในแอพ
 * - MLM Configuration - ตั้งค่าในแอพ
 * - UI Configuration - ตั้งค่าในแอพ
 *
 * การเปลี่ยนแปลงเหล่านี้ต้องออก App Update ใหม่
 */

// =====================================================
// App Info
// =====================================================

export const APP_INFO = {
  NAME: 'Thaiprompt Affiliate',
  VERSION: '1.3.1',
  BUILD_NUMBER: 8,
  BUILD_DATE: '2025-12-08-STABLE', // STABLE VERSION - แก้ crash on resume
  BUNDLE_ID: 'com.thaiprompt.affiliate',

  // App URLs
  WEBSITE: 'https://main.thaiprompt.online',
  SUPPORT_EMAIL: 'support@thaiprompt.online',
  TERMS_URL: 'https://main.thaiprompt.online/terms',
  PRIVACY_URL: 'https://main.thaiprompt.online/privacy',
};

// =====================================================
// Feature Flags (Local) - ควบคุมฟีเจอร์ในแอพ
// =====================================================

export const FEATURES = {
  // Authentication
  LINE_LOGIN_ENABLED: true,
  FACEBOOK_LOGIN_ENABLED: false,
  GOOGLE_LOGIN_ENABLED: false,
  APPLE_LOGIN_ENABLED: false,

  // Features
  WALLET_ENABLED: true,
  KYC_ENABLED: true,
  RIDER_ENABLED: true,
  SHOPPING_ENABLED: true,
  MLM_ENABLED: true,
  RANK_SYSTEM_ENABLED: true,
  LEADERBOARD_ENABLED: true,
  SUPPORT_TICKETS_ENABLED: true,
  PUSH_NOTIFICATIONS_ENABLED: true,

  // Coming Soon Features
  CRYPTO_TRADING_ENABLED: false,
  AI_BOT_ENABLED: false,
  ACADEMY_ENABLED: false,
  GAMING_ENABLED: false,
  HOTEL_BOOKING_ENABLED: false,
  FOOD_DELIVERY_ENABLED: false,

  // App Behaviors
  OFFLINE_MODE_ENABLED: true,
  BIOMETRIC_AUTH_ENABLED: true,
  DARK_MODE_ENABLED: true,
  ANALYTICS_ENABLED: true,
};

// =====================================================
// Rank Configuration (Local)
// =====================================================

export const RANK_CONFIG = {
  // สีและไอคอนสำหรับแต่ละ rank
  RANKS: [
    {
      id: 1,
      name: 'bronze',
      nameTh: 'บรอนซ์',
      icon: '🥉',
      color: '#CD7F32',
      level: 1,
      minPoints: 0,
      commissionRate: 0.05,
    },
    {
      id: 2,
      name: 'silver',
      nameTh: 'ซิลเวอร์',
      icon: '🥈',
      color: '#C0C0C0',
      level: 2,
      minPoints: 1000,
      commissionRate: 0.08,
    },
    {
      id: 3,
      name: 'gold',
      nameTh: 'โกลด์',
      icon: '🥇',
      color: '#FFD700',
      level: 3,
      minPoints: 5000,
      commissionRate: 0.10,
    },
    {
      id: 4,
      name: 'platinum',
      nameTh: 'แพลทินัม',
      icon: '💎',
      color: '#E5E4E2',
      level: 4,
      minPoints: 15000,
      commissionRate: 0.12,
    },
    {
      id: 5,
      name: 'diamond',
      nameTh: 'ไดมอนด์',
      icon: '💠',
      color: '#B9F2FF',
      level: 5,
      minPoints: 50000,
      commissionRate: 0.15,
    },
    {
      id: 6,
      name: 'crown',
      nameTh: 'คราวน์',
      icon: '👑',
      color: '#FFD700',
      level: 6,
      minPoints: 100000,
      commissionRate: 0.18,
    },
    {
      id: 7,
      name: 'legend',
      nameTh: 'เลเจนด์',
      icon: '🏆',
      color: '#FF4500',
      level: 7,
      minPoints: 500000,
      commissionRate: 0.20,
    },
  ],
};

// =====================================================
// Theme Configuration (Local)
// =====================================================

export const THEME_CONFIG = {
  // Primary Colors
  PRIMARY: '#3B82F6',
  PRIMARY_DARK: '#1D4ED8',
  PRIMARY_LIGHT: '#60A5FA',

  // Secondary Colors
  SECONDARY: '#10B981',
  SECONDARY_DARK: '#059669',
  SECONDARY_LIGHT: '#34D399',

  // Accent Colors
  ACCENT: '#8B5CF6',
  ACCENT_DARK: '#6D28D9',
  ACCENT_LIGHT: '#A78BFA',

  // Status Colors
  SUCCESS: '#10B981',
  WARNING: '#F59E0B',
  ERROR: '#EF4444',
  INFO: '#3B82F6',

  // Dark Theme
  DARK: {
    BACKGROUND: '#0F0F23',
    SURFACE: '#1A1A2E',
    CARD: '#16213E',
    BORDER: '#374151',
    TEXT_PRIMARY: '#FFFFFF',
    TEXT_SECONDARY: '#9CA3AF',
    TEXT_MUTED: '#6B7280',
  },

  // Light Theme
  LIGHT: {
    BACKGROUND: '#F9FAFB',
    SURFACE: '#FFFFFF',
    CARD: '#FFFFFF',
    BORDER: '#E5E7EB',
    TEXT_PRIMARY: '#1F2937',
    TEXT_SECONDARY: '#6B7280',
    TEXT_MUTED: '#9CA3AF',
  },

  // Gradients
  GRADIENTS: {
    PRIMARY: ['#3B82F6', '#1D4ED8'],
    SECONDARY: ['#10B981', '#059669'],
    ACCENT: ['#8B5CF6', '#6D28D9'],
    GOLD: ['#FFD700', '#FFA500'],
    SILVER: ['#C0C0C0', '#A8A8A8'],
    BRONZE: ['#CD7F32', '#8B4513'],
    DARK: ['#0F0F23', '#1A1A2E'],
    PREMIUM: ['#1E3A8A', '#1E40AF'],
  },
};

// =====================================================
// Commission Types (Local Configuration)
// =====================================================

export const COMMISSION_TYPES = {
  UNILEVEL_DIRECT: {
    key: 'unilevel_direct',
    name: 'คอมมิชชันขายตรง',
    icon: 'cart',
    color: '#10B981',
  },
  UNILEVEL_INDIRECT: {
    key: 'unilevel_indirect',
    name: 'คอมมิชชันสายงาน',
    icon: 'git-network',
    color: '#8B5CF6',
  },
  BINARY_PAIR: {
    key: 'binary_pair',
    name: 'โบนัสคู่ไบนารี่',
    icon: 'git-branch',
    color: '#3B82F6',
  },
  SPONSOR_BONUS: {
    key: 'sponsor_bonus',
    name: 'โบนัสผู้แนะนำ',
    icon: 'person-add',
    color: '#F59E0B',
  },
  RANK_BONUS: {
    key: 'rank_bonus',
    name: 'โบนัสระดับตำแหน่ง',
    icon: 'trophy',
    color: '#EC4899',
  },
  LEADERSHIP_BONUS: {
    key: 'leadership_bonus',
    name: 'โบนัสผู้นำ',
    icon: 'star',
    color: '#EF4444',
  },
};

// =====================================================
// Chart Configuration
// =====================================================

export const CHART_CONFIG = {
  COLORS: {
    EARNINGS: '#3B82F6',
    COMMISSIONS: '#10B981',
    SALES: '#F59E0B',
    REFERRALS: '#8B5CF6',
    TEAM: '#EC4899',
  },

  // สีสำหรับ Pie Chart
  PIE_COLORS: [
    '#3B82F6',
    '#10B981',
    '#F59E0B',
    '#8B5CF6',
    '#EC4899',
    '#EF4444',
  ],
};

// =====================================================
// API Configuration (อิสระจาก server control)
// =====================================================

export const API_CONFIG = {
  // Base URLs - สามารถเปลี่ยนได้เอง
  PRODUCTION_URL: 'https://main.thaiprompt.online/api/v1',
  DEVELOPMENT_URL: 'https://member123.thaiprompt.online/api/v1',

  // Timeouts
  DEFAULT_TIMEOUT: 30000,
  UPLOAD_TIMEOUT: 120000,

  // Retry
  MAX_RETRIES: 3,
  RETRY_DELAY: 1000,

  // Cache
  CACHE_DURATION: 5 * 60 * 1000, // 5 นาที
  OFFLINE_CACHE_DURATION: 24 * 60 * 60 * 1000, // 24 ชั่วโมง
};

// =====================================================
// MLM Configuration
// =====================================================

export const MLM_CONFIG = {
  // Unilevel Levels
  MAX_UNILEVEL_DEPTH: 10,
  UNILEVEL_PERCENTAGES: [10, 5, 3, 2, 1, 0.5, 0.5, 0.5, 0.25, 0.25],

  // Binary
  BINARY_ENABLED: true,
  BINARY_PAIR_BONUS_PERCENT: 10,
  MAX_BINARY_PAIRS_PER_DAY: 50,
};

// =====================================================
// UI Configuration
// =====================================================

export const UI_CONFIG = {
  // Animation Durations
  ANIMATION_SHORT: 150,
  ANIMATION_MEDIUM: 300,
  ANIMATION_LONG: 500,

  // Touch
  MIN_TOUCH_SIZE: 44,

  // Cards
  CARD_BORDER_RADIUS: 16,
  CARD_SHADOW: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
  },

  // Spacing
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

// =====================================================
// Helper Functions
// =====================================================

/**
 * ดึงข้อมูล Rank จาก ID
 */
export const getRankById = (rankId: number) => {
  return RANK_CONFIG.RANKS.find(r => r.id === rankId) || RANK_CONFIG.RANKS[0];
};

/**
 * ดึงข้อมูล Rank จากชื่อ
 */
export const getRankByName = (name: string) => {
  return RANK_CONFIG.RANKS.find(r => r.name.toLowerCase() === name.toLowerCase()) || RANK_CONFIG.RANKS[0];
};

/**
 * ดึง Commission Type Config
 */
export const getCommissionTypeConfig = (type: string) => {
  const key = type.toUpperCase().replace(/-/g, '_') as keyof typeof COMMISSION_TYPES;
  return COMMISSION_TYPES[key] || COMMISSION_TYPES.UNILEVEL_DIRECT;
};

/**
 * ตรวจสอบว่าฟีเจอร์เปิดใช้งานหรือไม่
 */
export const isFeatureEnabled = (feature: keyof typeof FEATURES): boolean => {
  return FEATURES[feature] ?? false;
};

/**
 * ดึง Theme Colors ตาม mode
 */
export const getThemeColors = (isDark: boolean) => {
  return isDark ? THEME_CONFIG.DARK : THEME_CONFIG.LIGHT;
};

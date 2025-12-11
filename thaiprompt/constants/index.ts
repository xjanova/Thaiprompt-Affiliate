/**
 * Constants สำหรับ Thaiprompt Affiliate App
 * แปลงจาก .NET MAUI Constants.cs
 */

// =====================================================
// API Configuration
// =====================================================

// Production API URL - ใช้ production เสมอ
// เพราะ dev server อาจมีปัญหา และ production มีข้อมูลจริง
export const API_BASE_URL = 'https://main.thaiprompt.online/api/v1';

// API Endpoints
export const API_ENDPOINTS = {
  // Authentication
  LOGIN: '/login',
  LOGOUT: '/logout',
  REGISTER: '/register',
  ME: '/me',

  // LINE Login (Mobile App)
  LINE_LOGIN_URL: '/auth/line/mobile-url',
  LINE_LOGIN_CALLBACK: '/auth/line/mobile-callback',

  // Web-Based Mobile Authentication (PKCE) - ปลอดภัยกว่า direct login
  WEB_AUTH_INIT: '/auth/mobile/init',
  WEB_AUTH_EXCHANGE: '/auth/mobile/exchange',
  WEB_AUTH_STATUS: '/auth/mobile/status',
  WEB_AUTH_CANCEL: '/auth/mobile/cancel',

  // Dashboard
  DASHBOARD_STATS: '/dashboard/statistics',
  DASHBOARD_CHARTS: '/dashboard/charts',

  // Commissions
  COMMISSIONS: '/dashboard/commissions',
  COMMISSION_DETAILS: '/dashboard/commissions/{id}',

  // Referrals
  REFERRALS: '/dashboard/referrals',
  REFERRAL_LINK: '/dashboard/referral-link',

  // Products
  PRODUCTS: '/products',
  PRODUCT_CATEGORIES: '/products/categories',
  PRODUCT_DETAIL: '/products/{id}',

  // Cart
  CART: '/cart',
  CART_ADD: '/cart/add',
  CART_REMOVE: '/cart/remove',

  // Wallet (Mobile App)
  WALLET: '/wallet',
  WALLET_TRANSACTIONS: '/wallet/transactions',
  WALLET_WITHDRAW: '/wallet/withdraw',
  WALLET_TRANSFER: '/wallet/transfer',
  WALLET_LOOKUP: '/wallet/lookup',

  // KYC (Mobile App)
  KYC_STATUS: '/kyc/status',
  KYC_SUBMIT: '/kyc/submit',
  KYC_UPLOAD: '/kyc/upload',
  KYC_CONFIRM: '/kyc/confirm',

  // Rider (Mobile App)
  RIDER_STATUS: '/rider/status',
  RIDER_REGISTER: '/rider/register',
  RIDER_DOCUMENT: '/rider/document',
  RIDER_PERMISSIONS: '/rider/permissions',
  RIDER_AVAILABILITY: '/rider/availability',
  RIDER_LOCATION: '/rider/location',
  RIDER_JOBS_AVAILABLE: '/rider/jobs/available',
  RIDER_JOBS_CURRENT: '/rider/jobs/current',
  RIDER_JOB_ACCEPT: '/rider/jobs', // + /{jobId}/accept
  RIDER_JOB_STATUS: '/rider/jobs', // + /{jobId}/status

  // Support Tickets (Mobile App)
  TICKETS: '/tickets',
  TICKET_DETAIL: '/tickets', // + /{ticketId}
  TICKET_REPLY: '/tickets', // + /{ticketId}/reply
  TICKET_RATE: '/tickets', // + /{ticketId}/rate

  // Notifications (Mobile App)
  NOTIFICATIONS: '/notifications',
  NOTIFICATIONS_UNREAD_COUNT: '/notifications/unread-count',
  NOTIFICATIONS_MARK_ALL_READ: '/notifications/mark-all-read',
  NOTIFICATION_READ: '/notifications', // + /{notificationId}/read
  NOTIFICATION_DELETE: '/notifications', // + /{notificationId}

  // Push Notification Token (Mobile App)
  PUSH_TOKEN: '/mobile/push-token',

  // Push Notification Delivery Tracking (สำหรับ retry mechanism)
  PUSH_CONFIRM: '/mobile/push/confirm',
  PUSH_PENDING: '/mobile/push/pending',
  PUSH_BULK_CONFIRM: '/mobile/push/bulk-confirm',
  PUSH_ANALYTICS: '/mobile/push/analytics',

  // Tarot / Fortune Telling (Mobile App)
  TAROT_CATEGORIES: '/tarot/categories',
  TAROT_SPREAD_TYPES: '/tarot/spread-types',
  TAROT_START_READING: '/tarot/start',
  TAROT_CARDS: '/tarot/cards',
  TAROT_CARD_BACKS: '/tarot/card-backs',
  TAROT_SAVE_SELECTION: '/tarot/save-selection',
  TAROT_READING: '/tarot/reading', // + /{readingId}
  TAROT_HISTORY: '/tarot/history',

  // Rank System (Mobile App)
  RANKS: '/mobile/ranks',
  RANK_DETAIL: '/mobile/ranks', // + /{rankId}
  RANK_PROGRESS: '/mobile/ranks/progress',
  RANK_LEADERBOARD: '/mobile/ranks/leaderboard',

  // MLM / Affiliate Network (Mobile App)
  AFFILIATE: '/mobile/affiliate',
  AFFILIATE_REFERRALS: '/mobile/affiliate/referrals',
  AFFILIATE_TEAM_TREE: '/mobile/affiliate/team-tree',

  // Commission System (Mobile App)
  COMMISSIONS_LIST: '/mobile/commissions',
  COMMISSIONS_EARNINGS: '/mobile/commissions/earnings',

  // Profile
  PROFILE: '/profile',
  UPDATE_PROFILE: '/profile', // ใช้ PUT method
  PROFILE_UPDATE: '/profile', // ใช้ PUT method
  AVATAR_UPLOAD: '/mobile/profile/avatar',
  AVATAR_DELETE: '/mobile/profile/avatar',
  CHANGE_PASSWORD: '/profile/change-password',
  REFERRAL_CODE: '/profile/referral-code',

  // MLM Tree (Mobile App)
  MLM_TREE: '/mobile/mlm/tree',
  MLM_SEARCH: '/mobile/mlm/search',
  MLM_MEMBER: '/mobile/mlm/member', // + /{memberId}

  // =====================================================
  // Admin Control (3 อย่างหลัก)
  // Admin สามารถดู/จัดการได้เฉพาะส่วนนี้เท่านั้น
  // =====================================================

  // 1. Banner โฆษณา (Admin ส่งมา)
  BANNERS: '/mobile/banners',
  BANNER_CLICK: '/mobile/banners', // + /{bannerId}/click

  // 2. Push Notification (Admin ส่งไปยังเครื่องลูกค้า)
  REGISTER_PUSH_TOKEN: '/mobile/push-token',

  // 3. Device Analytics (Admin ดู Dashboard สถิติเครื่อง)
  DEVICE_REGISTER: '/mobile/device/register',
  DEVICE_HEARTBEAT: '/mobile/device/heartbeat',

  // 4. GPS Sharing (User แชร์ตำแหน่งให้ Admin ดู GPS Monitor)
  GPS_SHARE: '/mobile/gps/share',
  GPS_STOP: '/mobile/gps/stop',
} as const;

// =====================================================
// Storage Keys
// =====================================================

export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  USER_DATA: 'user_data',
  USER_ID: 'user_id',
  USER_EMAIL: 'user_email',
  USER_NAME: 'user_name',
  REFERRAL_CODE: 'referral_code',
  THEME_CACHE: 'theme_cache',
  LAST_HUB_SELECTED: 'last_hub_selected',
  APP_LANGUAGE: 'app_language',
  REMEMBER_ME: 'remember_me',
  GPS_SHARING: 'gps_sharing',
} as const;

// =====================================================
// App Configuration
// =====================================================

export const APP_CONFIG = {
  NAME: 'TP UltraAPP',
  VERSION: '1.5.0',
  DEFAULT_PAGE_SIZE: 20,
  MAX_PAGE_SIZE: 100,
  API_TIMEOUT: 30000, // 30 seconds
  FILE_UPLOAD_TIMEOUT: 120000, // 2 minutes
  CACHE_DURATION: 5 * 60 * 1000, // 5 minutes
} as const;

// =====================================================
// Colors
// =====================================================

export const COLORS = {
  PRIMARY: '#3B82F6',
  SECONDARY: '#10B981',
  ACCENT: '#8B5CF6',
  ERROR: '#EF4444',
  WARNING: '#F59E0B',
  SUCCESS: '#10B981',
  INFO: '#3B82F6',

  // Dark Theme
  DARK_BG: '#0F0F23',
  DARK_SURFACE: '#1A1A2E',
  DARK_CARD: '#16213E',

  // Light Theme
  LIGHT_BG: '#FFFFFF',
  LIGHT_SURFACE: '#F9FAFB',
  LIGHT_CARD: '#FFFFFF',

  // Text
  TEXT_PRIMARY: '#1F2937',
  TEXT_SECONDARY: '#6B7280',
  TEXT_LIGHT: '#FFFFFF',
  TEXT_MUTED: '#9CA3AF',
} as const;

// =====================================================
// Animation Durations
// =====================================================

export const ANIMATIONS = {
  SHORT: 150,
  MEDIUM: 300,
  LONG: 500,
} as const;

// =====================================================
// Validation
// =====================================================

export const VALIDATION = {
  MIN_PASSWORD_LENGTH: 8,
  MAX_PASSWORD_LENGTH: 50,
  MIN_USERNAME_LENGTH: 3,
  MAX_USERNAME_LENGTH: 50,
  EMAIL_PATTERN: /^[^@\s]+@[^@\s]+\.[^@\s]+$/,
} as const;

// =====================================================
// Error Messages (Thai)
// =====================================================

export const ERROR_MESSAGES = {
  NETWORK_TITLE: 'ข้อผิดพลาดเครือข่าย',
  NETWORK_MESSAGE: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้ กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต',
  SERVER_TITLE: 'ข้อผิดพลาดเซิร์ฟเวอร์',
  SERVER_MESSAGE: 'เกิดข้อผิดพลาดจากเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง',
  UNAUTHORIZED_TITLE: 'ไม่ได้รับอนุญาต',
  UNAUTHORIZED_MESSAGE: 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง',
  VALIDATION_TITLE: 'ข้อมูลไม่ถูกต้อง',
  VALIDATION_MESSAGE: 'กรุณาตรวจสอบข้อมูลที่กรอกและลองใหม่อีกครั้ง',
} as const;

// =====================================================
// Success Messages (Thai)
// =====================================================

export const SUCCESS_MESSAGES = {
  LOGIN_TITLE: 'เข้าสู่ระบบสำเร็จ',
  LOGIN_MESSAGE: 'ยินดีต้อนรับ',
  LOGOUT_TITLE: 'ออกจากระบบสำเร็จ',
  LOGOUT_MESSAGE: 'แล้วพบกันใหม่',
  UPDATE_TITLE: 'อัปเดตสำเร็จ',
  UPDATE_MESSAGE: 'ข้อมูลของคุณได้รับการอัปเดตแล้ว',
} as const;

// =====================================================
// Hub Items - 8 บริการหลัก
// =====================================================

export const HUB_ITEMS = [
  {
    id: 'shopping',
    icon: '🛒',
    title: 'ช้อปปิ้ง & บริการ',
    subtitle: 'สั่งอาหาร, จองโรงแรม, หมอนวด, E-commerce',
    route: '/shopping',
    gradientStart: '#F59E0B',
    gradientEnd: '#EF4444',
    sortOrder: 1,
    requiresLogin: false,
  },
  {
    id: 'wallet',
    icon: '💰',
    title: 'กระเป๋าเงิน',
    subtitle: 'เติมเงิน, ถอนเงิน, โอนเงิน, Cashback',
    route: '/(tabs)/wallet',
    gradientStart: '#10B981',
    gradientEnd: '#059669',
    sortOrder: 2,
    requiresLogin: true,
  },
  {
    id: 'invest',
    icon: '📈',
    title: 'ลงทุน & Trade',
    subtitle: 'Crypto, Bot Trading, TPIX Token',
    route: '/coming-soon',
    gradientStart: '#3B82F6',
    gradientEnd: '#1D4ED8',
    sortOrder: 3,
    requiresLogin: true,
  },
  {
    id: 'mlm',
    icon: '🤝',
    title: 'MLM & Affiliate',
    subtitle: 'สมัครสมาชิก, Commission, Referral, Team',
    route: '/referral',
    gradientStart: '#8B5CF6',
    gradientEnd: '#6D28D9',
    sortOrder: 4,
    hasBadge: true,
    badgeText: 'HOT',
    requiresLogin: true,
  },
  {
    id: 'rider',
    icon: '🚴',
    title: 'เป็นไรเดอร์',
    subtitle: 'รับงานส่งของ, Service Provider',
    route: '/rider',
    gradientStart: '#06B6D4',
    gradientEnd: '#0891B2',
    sortOrder: 5,
    requiresLogin: true,
  },
  {
    id: 'aibot',
    icon: '🤖',
    title: 'AI Bot',
    subtitle: 'LINE Bot, Chatbot, Automation',
    route: '/coming-soon',
    gradientStart: '#EC4899',
    gradientEnd: '#BE185D',
    sortOrder: 6,
    hasBadge: true,
    badgeText: 'NEW',
    requiresLogin: false,
  },
  {
    id: 'academy',
    icon: '🎓',
    title: 'Academy',
    subtitle: 'เรียนรู้, หลักสูตร, Certificate',
    route: '/wiki',
    gradientStart: '#14B8A6',
    gradientEnd: '#0D9488',
    sortOrder: 7,
    requiresLogin: false,
  },
  {
    id: 'gaming',
    icon: '🎮',
    title: 'Gaming & Rewards',
    subtitle: 'เกม, Quest, Achievement, Rewards',
    route: '/coming-soon',
    gradientStart: '#F97316',
    gradientEnd: '#EA580C',
    sortOrder: 8,
    requiresLogin: false,
  },
] as const;

// =====================================================
// Helper Functions
// =====================================================

/**
 * สร้าง API URL จาก endpoint
 */
export const getApiUrl = (endpoint: string): string => {
  if (endpoint.startsWith('/')) {
    return API_BASE_URL + endpoint;
  }
  return `${API_BASE_URL}/${endpoint}`;
};

/**
 * Format เงิน (Thai Baht)
 */
export const formatCurrency = (amount: number): string => {
  return `฿${amount.toLocaleString('th-TH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};

/**
 * ตรวจสอบ email
 */
export const isValidEmail = (email: string): boolean => {
  return VALIDATION.EMAIL_PATTERN.test(email);
};

/**
 * ตรวจสอบ password
 */
export const isValidPassword = (password: string): boolean => {
  return (
    password.length >= VALIDATION.MIN_PASSWORD_LENGTH &&
    password.length <= VALIDATION.MAX_PASSWORD_LENGTH
  );
};

/**
 * รับข้อความทักทายตามเวลา
 */
export const getGreetingByTime = (): string => {
  const hour = new Date().getHours();

  if (hour >= 5 && hour < 12) {
    return 'สวัสดีตอนเช้า ☀️';
  } else if (hour >= 12 && hour < 17) {
    return 'สวัสดีตอนบ่าย 🌤️';
  } else if (hour >= 17 && hour < 21) {
    return 'สวัสดีตอนเย็น 🌅';
  } else {
    return 'สวัสดีตอนกลางคืน 🌙';
  }
};

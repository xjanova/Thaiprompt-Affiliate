/**
 * Types สำหรับ Thaiprompt Affiliate App
 * แปลงจาก .NET MAUI Models
 */

// =====================================================
// User Types
// =====================================================

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar?: string;
  referralCode?: string;
  wallet_address?: string;
  permissions: string[];
  createdAt: string;
}

// =====================================================
// Authentication Types
// =====================================================

export interface LoginRequest {
  email: string;
  password: string;
  remember?: boolean;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data?: {
    token: string;
    user: User;
  };
  errors?: Record<string, string[]>;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
}

// =====================================================
// Dashboard Types
// =====================================================

export interface DashboardStats {
  totalEarnings: number;
  pendingEarnings: number;
  approvedEarnings: number;
  monthlyEarnings: number;
  directSales: number;
  teamBonus: number;
  totalReferrals: number;
  growthPercentage: number;
  recentCommissions: Commission[];
}

export interface Commission {
  id: number;
  amount: number;
  status: 'pending' | 'approved' | 'paid' | 'cancelled';
  type: 'direct' | 'team' | 'referral' | 'bonus';
  level?: number;
  description: string;
  date: string;
  from_user?: {
    id: number;
    name: string;
    email: string;
  };
  createdAt: string;
}

export interface PaginatedCommissions {
  data: Commission[];
  currentPage: number;
  lastPage: number;
  total: number;
}

// =====================================================
// Earnings Summary Types
// =====================================================

export interface EarningsSummary {
  totalEarnings: number;
  thisMonthEarnings: number;
  lastMonthEarnings: number;
  pendingEarnings: number;
  growthPercent: number;
  earningsByType: {
    unilevelDirect: number;
    unilevelIndirect: number;
    binaryPair: number;
    sponsorBonus: number;
    rankBonus: number;
    leadershipBonus: number;
  };
  chart: {
    date: string;
    day: string;
    amount: number;
  }[];
}

// =====================================================
// Product Types
// =====================================================

export interface Product {
  id: string;
  name: string;
  description?: string;
  price: number;
  discount_price?: number;
  image?: string;
  images?: string[];
  category_id?: string;
  category?: ProductCategory;
  commission_rate?: number;
  rating?: number;
  review_count?: number;
  stock?: number;
  is_active: boolean;
  created_at: string;
}

export interface ProductCategory {
  id: string;
  name: string;
  slug: string;
  image?: string;
  parent_id?: string;
  products_count?: number;
}

// =====================================================
// Referral Stats Types
// =====================================================

export interface ReferralStats {
  totalReferrals: number;
  activeReferrals: number;
  pendingReferrals: number;
  totalEarnings: number;
  monthlyEarnings: number;
  referralCode: string;
  referralLink: string;
}

// =====================================================
// Referral Types
// =====================================================

export interface Referral {
  id: number;
  user: ReferralUser;
  earnings: number;
  level: number;
  status: 'active' | 'inactive';
  createdAt: string;
}

export interface ReferralUser {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

export interface ReferralTreeNode {
  id: number;
  name: string;
  level: number;
  children: ReferralTreeNode[];
}

export interface ReferralsData {
  referrals: Referral[];
  totalReferrals: number;
  totalEarnings: number;
  tree: ReferralTreeNode;
}

// =====================================================
// Hub Types
// =====================================================

export interface HubItem {
  id: string;
  icon: string;
  title: string;
  subtitle: string;
  route: string;
  gradientStart: string;
  gradientEnd: string;
  sortOrder: number;
  hasBadge?: boolean;
  badgeText?: string;
  features?: string[];
  requiresLogin?: boolean;
}

// =====================================================
// Theme Types
// =====================================================

export interface ThemeConfig {
  colors: {
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    surface: string;
    text: string;
    textSecondary: string;
    border: string;
    error: string;
    success: string;
    warning: string;
  };
  typography: {
    fontFamily: string;
    fontSize: {
      xs: number;
      sm: number;
      base: number;
      lg: number;
      xl: number;
      '2xl': number;
      '3xl': number;
    };
  };
  spacing: {
    xs: number;
    sm: number;
    md: number;
    lg: number;
    xl: number;
  };
  borderRadius: {
    sm: number;
    md: number;
    lg: number;
    full: number;
  };
}

// =====================================================
// Navigation Types
// =====================================================

export type RootStackParamList = {
  index: undefined;
  login: undefined;
  register: undefined;
  dashboard: undefined;
  profile: undefined;
  hub: { hubId: string };
};

// =====================================================
// Store Types
// =====================================================

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

export interface AppState {
  theme: 'light' | 'dark';
  language: 'th' | 'en';
  isOnline: boolean;
}

// =====================================================
// Admin Control Types (เฉพาะ 2 อย่างเท่านั้น)
// =====================================================

/**
 * Banner โฆษณา - ควบคุมโดย Admin
 */
export interface Banner {
  id: number;
  title: string;
  image: string;
  link?: string;
  linkType?: 'internal' | 'external' | 'product' | 'category';
  linkTarget?: string;
  position: 'top' | 'middle' | 'bottom';
  isActive: boolean;
  startDate?: string;
  endDate?: string;
  sortOrder: number;
}

/**
 * Push Notification - ส่งโดย Admin
 */
export interface PushNotification {
  id: number;
  title: string;
  body: string;
  data?: Record<string, string>;
  image?: string;
  sentAt: string;
  readAt?: string;
}

// =====================================================
// Device Analytics Types (สำหรับ Admin Dashboard)
// =====================================================

/**
 * ข้อมูลเครื่องที่ลงทะเบียน - เก็บไว้สำหรับ Analytics
 */
export interface DeviceRegistration {
  id: string;
  userId?: number;
  deviceId: string;
  platform: 'ios' | 'android';
  deviceModel: string;
  deviceBrand: string;
  osVersion: string;
  appVersion: string;
  pushToken?: string;
  locale: string;
  timezone: string;
  isActive: boolean;
  lastActiveAt: string;
  registeredAt: string;
}

/**
 * Device Info สำหรับส่งตอนลงทะเบียน
 */
export interface DeviceInfo {
  deviceId: string;
  platform: 'ios' | 'android';
  deviceModel: string;
  deviceBrand: string;
  osVersion: string;
  appVersion: string;
  pushToken?: string;
  locale: string;
  timezone: string;
}

/**
 * สถิติเครื่องสำหรับ Admin Dashboard
 */
export interface DeviceAnalytics {
  totalDevices: number;
  activeDevices: number;
  newDevicesToday: number;
  newDevicesThisWeek: number;
  newDevicesThisMonth: number;
  byPlatform: {
    ios: number;
    android: number;
  };
  byAppVersion: {
    version: string;
    count: number;
    percentage: number;
  }[];
  byOsVersion: {
    version: string;
    platform: 'ios' | 'android';
    count: number;
  }[];
  dailyActiveUsers: {
    date: string;
    count: number;
  }[];
  retentionRate: number;
}

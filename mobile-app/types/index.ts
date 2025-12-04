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

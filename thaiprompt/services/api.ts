/**
 * API Service สำหรับติดต่อกับ Backend
 * แปลงจาก .NET MAUI ApiService.cs
 */

import axios, { AxiosInstance, AxiosError } from 'axios';
import * as SecureStore from 'expo-secure-store';
import {
  API_BASE_URL,
  API_ENDPOINTS,
  STORAGE_KEYS,
  APP_CONFIG,
  ERROR_MESSAGES,
} from '@/constants';
import type {
  LoginRequest,
  LoginResponse,
  ApiResponse,
  User,
  DashboardStats,
  Commission,
  PaginatedCommissions,
  ReferralsData,
  ReferralStats,
  Product,
  ProductCategory,
} from '@/types';

// สร้าง Axios instance
const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: APP_CONFIG.API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// =====================================================
// Token Management
// =====================================================

/**
 * โหลด token จาก SecureStore
 */
export const loadAuthToken = async (): Promise<string | null> => {
  try {
    return await SecureStore.getItemAsync(STORAGE_KEYS.AUTH_TOKEN);
  } catch (error) {
    console.error('Error loading auth token:', error);
    return null;
  }
};

/**
 * บันทึก token ไปยัง SecureStore
 */
export const saveAuthToken = async (token: string): Promise<void> => {
  try {
    await SecureStore.setItemAsync(STORAGE_KEYS.AUTH_TOKEN, token);
    apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  } catch (error) {
    console.error('Error saving auth token:', error);
  }
};

/**
 * ลบ token จาก SecureStore
 */
export const clearAuthToken = async (): Promise<void> => {
  try {
    await SecureStore.deleteItemAsync(STORAGE_KEYS.AUTH_TOKEN);
    await SecureStore.deleteItemAsync(STORAGE_KEYS.USER_DATA);
    delete apiClient.defaults.headers.common['Authorization'];
  } catch (error) {
    console.error('Error clearing auth token:', error);
  }
};

/**
 * ตั้งค่า Authorization header
 */
export const setAuthHeader = (token: string): void => {
  apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

// =====================================================
// Request Interceptor
// =====================================================

apiClient.interceptors.request.use(
  async (config) => {
    // ถ้ายังไม่มี token ใน header ให้โหลดจาก storage
    if (!config.headers['Authorization']) {
      const token = await loadAuthToken();
      if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// =====================================================
// Response Interceptor
// =====================================================

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Token หมดอายุ - ล้างข้อมูล
      await clearAuthToken();
    }
    return Promise.reject(error);
  }
);

// =====================================================
// Authentication APIs
// =====================================================

/**
 * เข้าสู่ระบบ
 */
export const login = async (
  email: string,
  password: string
): Promise<LoginResponse> => {
  try {
    const response = await apiClient.post<LoginResponse>(API_ENDPOINTS.LOGIN, {
      email,
      password,
      remember: true,
    } as LoginRequest);

    const result = response.data;

    if (result.success && result.data?.token) {
      await saveAuthToken(result.data.token);

      // บันทึกข้อมูล user
      if (result.data.user) {
        await SecureStore.setItemAsync(
          STORAGE_KEYS.USER_DATA,
          JSON.stringify(result.data.user)
        );
      }
    }

    return result;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (!error.response) {
        return {
          success: false,
          message: ERROR_MESSAGES.NETWORK_MESSAGE,
        };
      }
      return {
        success: false,
        message: error.response.data?.message || ERROR_MESSAGES.SERVER_MESSAGE,
        errors: error.response.data?.errors,
      };
    }
    return {
      success: false,
      message: ERROR_MESSAGES.SERVER_MESSAGE,
    };
  }
};

/**
 * ออกจากระบบ
 */
export const logout = async (): Promise<boolean> => {
  try {
    await apiClient.post(API_ENDPOINTS.LOGOUT);
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    await clearAuthToken();
  }
  return true;
};

/**
 * ตรวจสอบ token ยังใช้ได้หรือไม่
 */
export const validateToken = async (): Promise<boolean> => {
  try {
    const token = await loadAuthToken();
    if (!token) return false;

    setAuthHeader(token);
    const response = await apiClient.get(API_ENDPOINTS.ME);
    return response.status === 200;
  } catch (error) {
    return false;
  }
};

// =====================================================
// User APIs
// =====================================================

/**
 * ดึงข้อมูล user ปัจจุบัน
 */
export const getCurrentUser = async (): Promise<User | null> => {
  try {
    const response = await apiClient.get<ApiResponse<User>>(API_ENDPOINTS.ME);
    return response.data.data || null;
  } catch (error) {
    console.error('Get current user error:', error);
    return null;
  }
};

// =====================================================
// Dashboard APIs
// =====================================================

/**
 * ดึงสถิติ Dashboard
 */
export const getDashboardStats = async (): Promise<DashboardStats | null> => {
  try {
    const response = await apiClient.get<ApiResponse<DashboardStats>>(
      API_ENDPOINTS.DASHBOARD_STATS
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get dashboard stats error:', error);
    return null;
  }
};

/**
 * ดึงรายการ commissions พร้อม pagination
 */
export const getCommissions = async (
  page: number = 1
): Promise<Commission[] | null> => {
  try {
    const response = await apiClient.get<ApiResponse<PaginatedCommissions>>(
      `${API_ENDPOINTS.COMMISSIONS}?page=${page}`
    );
    return response.data.data?.data || null;
  } catch (error) {
    console.error('Get commissions error:', error);
    return null;
  }
};

// =====================================================
// Referrals APIs
// =====================================================

/**
 * ดึงข้อมูล referrals
 */
export const getReferrals = async (): Promise<ReferralsData | null> => {
  try {
    const response = await apiClient.get<ApiResponse<ReferralsData>>(
      API_ENDPOINTS.REFERRALS
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get referrals error:', error);
    return null;
  }
};

// =====================================================
// Settings APIs
// =====================================================

/**
 * หมายเหตุ: แอพใช้ local config จาก @/config/appConfig แทน
 * เพื่อความเป็นอิสระและไม่พึ่งพา server control
 *
 * ดู: mobile-app/config/appConfig.ts
 */

// =====================================================
// Products APIs
// =====================================================

/**
 * ดึงรายการสินค้า (รองรับ Pagination)
 */
export const getProducts = async (
  params?: {
    category?: string | null;
    search?: string;
    page?: number;
    limit?: number;
  }
): Promise<Product[] | null> => {
  try {
    const queryParams = new URLSearchParams();
    if (params?.category) queryParams.append('category', params.category);
    if (params?.search) queryParams.append('search', params.search);
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.limit) queryParams.append('limit', params.limit.toString());

    const response = await apiClient.get<ApiResponse<Product[]>>(
      `${API_ENDPOINTS.PRODUCTS}?${queryParams.toString()}`
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get products error:', error);
    return null;
  }
};

// Store Type for API
interface Store {
  id: string;
  name: string;
  logo?: string;
  rating: number;
  isOfficial: boolean;
  isFeatured: boolean;
  productCount: number;
}

/**
 * ดึงรายละเอียดร้านค้า
 */
export const getStoreDetail = async (
  storeId: string
): Promise<{
  success: boolean;
  data?: {
    id: string;
    name: string;
    description?: string;
    logo?: string;
    banner?: string;
    rating: number;
    isOfficial: boolean;
    isFeatured: boolean;
    productCount: number;
    followerCount: number;
    responseRate?: number;
    joinedAt?: string;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get<ApiResponse<any>>(
      `/mobile/stores/${storeId}`
    );
    return {
      success: true,
      data: response.data.data,
    };
  } catch (error) {
    console.error('Get store detail error:', error);
    return {
      success: false,
      message: 'ไม่สามารถโหลดข้อมูลร้านค้าได้',
    };
  }
};

/**
 * ดึงสินค้าของร้านค้า
 */
export const getStoreProducts = async (
  storeId: string,
  page: number = 1,
  perPage: number = 20
): Promise<{
  success: boolean;
  data?: {
    items: Product[];
    hasMore: boolean;
    total: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get<ApiResponse<any>>(
      `/mobile/stores/${storeId}/products`,
      {
        params: { page, per_page: perPage },
      }
    );
    return {
      success: true,
      data: response.data.data,
    };
  } catch (error) {
    console.error('Get store products error:', error);
    return {
      success: false,
      message: 'ไม่สามารถโหลดสินค้าได้',
    };
  }
};

/**
 * ดึงรายการร้านค้าทางการ
 * (Admin จัดการจากเมนูจัดการแอพ)
 */
export const getOfficialStores = async (): Promise<Store[] | null> => {
  try {
    const response = await apiClient.get<ApiResponse<Store[]>>(
      '/mobile/stores/official'
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get official stores error:', error);
    return null;
  }
};

/**
 * ดึงรายการร้านแนะนำติดดาว
 * (Admin จัดการจากเมนูจัดการแอพ)
 */
export const getFeaturedStores = async (): Promise<Store[] | null> => {
  try {
    const response = await apiClient.get<ApiResponse<Store[]>>(
      '/mobile/stores/featured'
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get featured stores error:', error);
    return null;
  }
};

// Premium Store Type
interface PremiumStore {
  id: string;
  sellerId: number;
  name: string;
  description: string;
  logo?: string;
  banner?: string;
  rating: number;
  ratingCount: number;
  isOfficial: boolean;
  isPremium: boolean;
  productCount: number;
  featuredCount: number;
  verified: boolean;
  features: string[];
}

/**
 * ดึงข้อมูลร้านพรีเมี่ยม (Official Shop)
 * มีร้านเดียวในระบบ - เชื่อมต่อกับ admin/official-shop
 */
export const getPremiumStore = async (): Promise<PremiumStore | null> => {
  try {
    const response = await apiClient.get<ApiResponse<PremiumStore>>(
      '/mobile/premium-store'
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get premium store error:', error);
    return null;
  }
};

/**
 * ดึงสินค้าจากร้านพรีเมี่ยม
 */
export const getPremiumStoreProducts = async (
  params?: {
    category?: string | null;
    featured?: boolean;
    search?: string;
    page?: number;
    limit?: number;
  }
): Promise<{
  products: Product[];
  pagination: {
    total: number;
    currentPage: number;
    lastPage: number;
    hasMore: boolean;
  };
} | null> => {
  try {
    const queryParams = new URLSearchParams();
    if (params?.category) queryParams.append('category', params.category);
    if (params?.featured) queryParams.append('featured', 'true');
    if (params?.search) queryParams.append('search', params.search);
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.limit) queryParams.append('limit', params.limit.toString());

    const response = await apiClient.get<ApiResponse<Product[]>>(
      `/mobile/premium-store/products?${queryParams.toString()}`
    );

    return {
      products: response.data.data || [],
      pagination: (response.data as any).pagination || {
        total: 0,
        currentPage: 1,
        lastPage: 1,
        hasMore: false,
      },
    };
  } catch (error) {
    console.error('Get premium store products error:', error);
    return null;
  }
};

/**
 * ดึงรายการหมวดหมู่สินค้า
 */
export const getProductCategories = async (): Promise<ProductCategory[] | null> => {
  try {
    const response = await apiClient.get<ApiResponse<ProductCategory[]>>(
      API_ENDPOINTS.PRODUCT_CATEGORIES
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get product categories error:', error);
    return null;
  }
};

/**
 * ดึงรายละเอียดสินค้า
 */
export const getProductDetail = async (id: string): Promise<Product | null> => {
  try {
    const response = await apiClient.get<ApiResponse<Product>>(
      `${API_ENDPOINTS.PRODUCTS}/${id}`
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get product detail error:', error);
    return null;
  }
};

// =====================================================
// Referral Stats APIs
// =====================================================

/**
 * ดึงสถิติการแนะนำ
 */
export const getReferralStats = async (): Promise<ReferralStats | null> => {
  try {
    const response = await apiClient.get<ApiResponse<ReferralStats>>(
      `${API_ENDPOINTS.REFERRALS}/stats`
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get referral stats error:', error);
    return null;
  }
};

// =====================================================
// LINE Login APIs
// =====================================================

/**
 * ดึง LINE Login URL
 */
export const getLineLoginUrl = async (): Promise<{
  success: boolean;
  data?: { authUrl: string; state: string };
  message?: string;
}> => {
  try {
    const response = await apiClient.get<{
      success: boolean;
      data: { authUrl: string; state: string };
      message?: string;
    }>(API_ENDPOINTS.LINE_LOGIN_URL);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ไม่สามารถเชื่อมต่อ LINE ได้',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * เข้าสู่ระบบด้วย LINE
 */
export const lineLoginCallback = async (
  code: string,
  state: string,
  referralCode?: string
): Promise<LoginResponse> => {
  try {
    const response = await apiClient.post<LoginResponse>(
      API_ENDPOINTS.LINE_LOGIN_CALLBACK,
      {
        code,
        state,
        referral_code: referralCode,
      }
    );

    const result = response.data;

    if (result.success && result.data?.token) {
      await saveAuthToken(result.data.token);

      // บันทึกข้อมูล user
      if (result.data.user) {
        await SecureStore.setItemAsync(
          STORAGE_KEYS.USER_DATA,
          JSON.stringify(result.data.user)
        );
      }
    }

    return result;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (!error.response) {
        return {
          success: false,
          message: ERROR_MESSAGES.NETWORK_MESSAGE,
        };
      }
      return {
        success: false,
        message: error.response.data?.message || 'เข้าสู่ระบบด้วย LINE ไม่สำเร็จ',
        errors: error.response.data?.errors,
      };
    }
    return {
      success: false,
      message: ERROR_MESSAGES.SERVER_MESSAGE,
    };
  }
};

// =====================================================
// Web-Based Mobile Authentication (PKCE)
// =====================================================

/**
 * สร้าง code_verifier สำหรับ PKCE
 * code_verifier ต้องมีความยาว 43-128 characters
 */
export const generateCodeVerifier = (): string => {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return Array.from(array, (byte) => byte.toString(16).padStart(2, '0')).join('');
};

/**
 * ขั้นตอนที่ 1: เริ่มต้น web-based login
 * สร้าง login_token และ login_url สำหรับเปิดใน browser
 */
export const initWebAuth = async (
  deviceId: string,
  deviceName: string,
  codeVerifier: string
): Promise<{
  success: boolean;
  data?: {
    login_url: string;
    login_token: string;
    state: string;
    expires_in: number;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.WEB_AUTH_INIT, {
      device_id: deviceId,
      device_name: deviceName,
      code_verifier: codeVerifier,
    });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (!error.response) {
        return {
          success: false,
          message: ERROR_MESSAGES.NETWORK_MESSAGE,
        };
      }
      return {
        success: false,
        message: error.response.data?.message || 'ไม่สามารถเริ่มต้นการเข้าสู่ระบบได้',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ขั้นตอนที่ 5: แลก auth_code เป็น access_token
 * ใช้หลังจากได้รับ auth_code จาก deep link callback
 */
export const exchangeWebAuthCode = async (
  authCode: string,
  codeVerifier: string,
  state: string
): Promise<{
  success: boolean;
  data?: {
    token: string;
    user: User;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.WEB_AUTH_EXCHANGE, {
      auth_code: authCode,
      code_verifier: codeVerifier,
      state: state,
    });

    const result = response.data;

    if (result.success && result.data?.token) {
      await saveAuthToken(result.data.token);

      // บันทึกข้อมูล user
      if (result.data.user) {
        await SecureStore.setItemAsync(
          STORAGE_KEYS.USER_DATA,
          JSON.stringify(result.data.user)
        );
      }
    }

    return result;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (!error.response) {
        return {
          success: false,
          message: ERROR_MESSAGES.NETWORK_MESSAGE,
        };
      }
      return {
        success: false,
        message: error.response.data?.message || 'ไม่สามารถยืนยันตัวตนได้',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ตรวจสอบสถานะการ login (สำหรับ polling)
 */
export const checkWebAuthStatus = async (
  loginToken: string,
  state: string
): Promise<{
  success: boolean;
  status?: 'pending' | 'authenticated' | 'expired';
  message?: string;
}> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.WEB_AUTH_STATUS, {
      params: { login_token: loginToken, state },
    });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ไม่สามารถตรวจสอบสถานะได้',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด',
    };
  }
};

/**
 * ยกเลิกการ login
 */
export const cancelWebAuth = async (
  loginToken: string,
  state: string
): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.WEB_AUTH_CANCEL, {
      login_token: loginToken,
      state: state,
    });
    return response.data;
  } catch (error) {
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด',
    };
  }
};

// =====================================================
// Wallet APIs
// =====================================================

/**
 * ดึงข้อมูลกระเป๋าเงิน
 */
export const getWallet = async (): Promise<{
  success: boolean;
  data?: {
    balance: number;
    availableBalance: number;
    pendingBalance: number;
    totalIncome: number;
    totalExpense: number;
    thisMonthIncome: number;
    thisMonthExpense: number;
    currency: string;
    walletAddress?: string;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.WALLET);
    return response.data;
  } catch (error) {
    console.error('Get wallet error:', error);
    return null;
  }
};

/**
 * ดึงประวัติธุรกรรม
 */
export const getWalletTransactions = async (
  page: number = 1,
  type?: 'in' | 'out' | 'all',
  perPage: number = 15
): Promise<{
  success: boolean;
  data?: {
    items: Array<{
      id: number;
      type: 'in' | 'out';
      amount: number;
      title: string;
      status: string;
      date: string;
      dateRelative: string;
      referenceType?: string;
      referenceId?: number;
    }>;
    pagination: {
      currentPage: number;
      lastPage: number;
      perPage: number;
      total: number;
    };
  };
  message?: string;
} | null> => {
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: perPage.toString(),
    });
    if (type && type !== 'all') {
      params.append('type', type);
    }

    const response = await apiClient.get(
      `${API_ENDPOINTS.WALLET_TRANSACTIONS}?${params.toString()}`
    );
    return response.data;
  } catch (error) {
    console.error('Get wallet transactions error:', error);
    return null;
  }
};

/**
 * ส่งคำขอถอนเงิน
 */
export const createWithdrawalRequest = async (data: {
  amount: number;
  payment_method?: string;
  pin: string;
  note?: string;
}): Promise<{
  success: boolean;
  data?: {
    request_id: string;
    amount: number;
    fee: number;
    tax: number;
    net_amount: number;
    status: string;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.WALLET_WITHDRAW, data);
    return response.data;
  } catch (error: any) {
    console.error('Create withdrawal request error:', error);
    if (error.response?.data?.message) {
      return { success: false, message: error.response.data.message };
    }
    return null;
  }
};

/**
 * ค้นหา Wallet Address
 */
export const lookupWalletAddress = async (
  walletAddress: string
): Promise<{
  success: boolean;
  data?: {
    user_id: number;
    name: string;
    avatar?: string;
    wallet_address: string;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(
      `${API_ENDPOINTS.WALLET_LOOKUP}?address=${encodeURIComponent(walletAddress)}`
    );
    return response.data;
  } catch (error: any) {
    console.error('Lookup wallet address error:', error);
    if (error.response?.data?.message) {
      return { success: false, message: error.response.data.message };
    }
    return null;
  }
};

/**
 * โอนเงิน
 */
export const transferMoney = async (data: {
  wallet_address: string;
  amount: number;
  pin: string;
  note?: string;
}): Promise<{
  success: boolean;
  data?: {
    transaction_id: string;
    amount: number;
    fee: number;
    total_deduction: number;
    to_name: string;
    to_address: string;
    balance_after: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.WALLET_TRANSFER, data);
    return response.data;
  } catch (error: any) {
    console.error('Transfer money error:', error);
    if (error.response?.data?.message) {
      return { success: false, message: error.response.data.message };
    }
    return null;
  }
};

// =====================================================
// KYC APIs
// =====================================================

/**
 * ดึงสถานะ KYC
 */
export const getKycStatus = async (): Promise<{
  success: boolean;
  data?: {
    status: 'not_submitted' | 'pending' | 'approved' | 'rejected';
    verifiedAt?: string;
    submission?: {
      id: number;
      status: string;
      submittedAt?: string;
      reviewedAt?: string;
      rejectionReason?: string;
      hasIdCard: boolean;
      hasSelfie: boolean;
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.KYC_STATUS);
    return response.data;
  } catch (error) {
    console.error('Get KYC status error:', error);
    return null;
  }
};

/**
 * อัพโหลดรูปภาพ KYC
 */
export const uploadKycImage = async (
  imageUri: string,
  type: 'id_card' | 'selfie'
): Promise<{
  success: boolean;
  data?: {
    kycId: number;
    type: string;
    hasIdCard: boolean;
    hasSelfie: boolean;
    canSubmit: boolean;
  };
  message?: string;
}> => {
  try {
    const formData = new FormData();

    // แปลง URI ให้ถูกต้องสำหรับ React Native
    let fileUri = imageUri;
    // ถ้าเป็น content:// หรือ file:// ใช้ได้เลย
    // ถ้าไม่มี scheme ให้เพิ่ม file://
    if (!fileUri.startsWith('file://') && !fileUri.startsWith('content://')) {
      fileUri = `file://${fileUri}`;
    }

    // สร้าง file object จาก uri
    const filename = imageUri.split('/').pop() || `${type}.jpg`;
    // ตรวจสอบ extension โดยละเลย query string
    const cleanFilename = filename.split('?')[0];
    const match = /\.(\w+)$/.exec(cleanFilename);
    let fileType = 'image/jpeg';
    if (match) {
      const ext = match[1].toLowerCase();
      if (ext === 'png') fileType = 'image/png';
      else if (ext === 'gif') fileType = 'image/gif';
      else if (ext === 'webp') fileType = 'image/webp';
      else if (ext === 'heic' || ext === 'heif') fileType = 'image/heic';
      else fileType = `image/${ext}`;
    }

    formData.append('image', {
      uri: fileUri,
      name: cleanFilename || `${type}.jpg`,
      type: fileType,
    } as unknown as Blob);
    formData.append('type', type);

    console.log('🪪 Uploading KYC image:', { uri: fileUri, name: cleanFilename, type: fileType, kycType: type });

    // สำคัญ: ไม่ต้องตั้ง Content-Type เอง ให้ axios สร้าง boundary ให้
    const response = await apiClient.post(API_ENDPOINTS.KYC_UPLOAD, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      transformRequest: (data) => data, // ป้องกัน axios แปลง FormData
    });

    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'อัพโหลดรูปภาพไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ยืนยันส่ง KYC
 */
export const confirmKycSubmission = async (): Promise<{
  success: boolean;
  data?: {
    kycId: number;
    status: string;
    submittedAt: string;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.KYC_CONFIRM);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ส่งเอกสารไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

// =====================================================
// Rider APIs
// =====================================================

/**
 * ดึงสถานะการสมัครเป็นไรเดอร์
 */
export const getRiderStatus = async (): Promise<{
  success: boolean;
  data?: {
    isRider: boolean;
    riderId?: number;
    status?: 'pending' | 'approved' | 'rejected' | 'suspended' | 'inactive';
    statusText?: string;
    availability?: 'online' | 'offline' | 'busy';
    availabilityText?: string;
    vehicleType?: string;
    vehicleTypeText?: string;
    rating?: number;
    totalJobs?: number;
    completedJobs?: number;
    completionRate?: number;
    totalEarnings?: number;
    permissions?: {
      gps: boolean;
      camera: boolean;
      microphone: boolean;
      notification: boolean;
      allGranted: boolean;
    };
    rejectionReason?: string;
    approvedAt?: string;
    message?: string;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.RIDER_STATUS);
    return response.data;
  } catch (error) {
    console.error('Get rider status error:', error);
    return null;
  }
};

/**
 * สมัครเป็นไรเดอร์
 */
export const registerRider = async (data: {
  full_name: string;
  phone: string;
  id_card_number?: string;
  birth_date?: string;
  address?: string;
  province?: string;
  district?: string;
  vehicle_type: 'motorcycle' | 'car' | 'bicycle' | 'walk';
  vehicle_plate?: string;
  vehicle_brand?: string;
  vehicle_color?: string;
}): Promise<{
  success: boolean;
  data?: {
    riderId: number;
    status: string;
    statusText: string;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.RIDER_REGISTER, data);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'สมัครไรเดอร์ไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * อัพโหลดเอกสารไรเดอร์
 */
export const uploadRiderDocument = async (
  imageUri: string,
  type: 'id_card' | 'driver_license' | 'vehicle_registration' | 'profile'
): Promise<{
  success: boolean;
  data?: {
    type: string;
    uploaded: boolean;
  };
  message?: string;
}> => {
  try {
    const formData = new FormData();

    const filename = imageUri.split('/').pop() || `${type}.jpg`;
    const match = /\.(\w+)$/.exec(filename);
    const fileType = match ? `image/${match[1]}` : 'image/jpeg';

    formData.append('image', {
      uri: imageUri,
      name: filename,
      type: fileType,
    } as unknown as Blob);
    formData.append('type', type);

    const response = await apiClient.post(API_ENDPOINTS.RIDER_DOCUMENT, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'อัพโหลดเอกสารไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * บันทึกสิทธิ์ที่ได้รับจากผู้ใช้
 */
export const updateRiderPermissions = async (permissions: {
  gps?: boolean;
  camera?: boolean;
  microphone?: boolean;
  notification?: boolean;
}): Promise<{
  success: boolean;
  data?: {
    permissions: {
      gps: boolean;
      camera: boolean;
      microphone: boolean;
      notification: boolean;
      allGranted: boolean;
    };
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.RIDER_PERMISSIONS, permissions);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'บันทึกสิทธิ์ไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ตั้งค่าสถานะออนไลน์/ออฟไลน์
 */
export const setRiderAvailability = async (
  availability: 'online' | 'offline'
): Promise<{
  success: boolean;
  data?: {
    availability: string;
    availabilityText: string;
  };
  requirePermission?: string;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.RIDER_AVAILABILITY, { availability });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'เปลี่ยนสถานะไม่สำเร็จ',
        requirePermission: error.response?.data?.requirePermission,
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * อัพเดทตำแหน่ง GPS
 */
export const updateRiderLocation = async (location: {
  latitude: number;
  longitude: number;
  altitude?: number;
  accuracy?: number;
  speed?: number;
  heading?: number;
  battery_level?: number;
  is_charging?: boolean;
  activity_type?: 'still' | 'walking' | 'running' | 'cycling' | 'driving' | 'unknown';
  device_model?: string;
  os_version?: string;
}): Promise<{
  success: boolean;
  data?: {
    hasActiveJob: boolean;
    jobId?: number;
    isTracking: boolean;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.RIDER_LOCATION, location);
    return response.data;
  } catch (error) {
    console.error('Update rider location error:', error);
    return null;
  }
};

// =====================================================
// ⭐ GPS Sharing - แชร์ตำแหน่งให้ Admin GPS Monitor
// =====================================================

/**
 * แชร์ตำแหน่ง GPS ไปยัง Admin GPS Monitor
 */
export const shareGpsLocation = async (location: {
  latitude: number;
  longitude: number;
  altitude?: number;
  accuracy?: number;
  speed?: number;
  heading?: number;
  battery_level?: number;
  device_model?: string;
  os_version?: string;
}): Promise<{
  success: boolean;
  message?: string;
} | null> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.GPS_SHARE, location);
    return response.data;
  } catch (error) {
    console.error('Share GPS location error:', error);
    return null;
  }
};

/**
 * หยุดแชร์ตำแหน่ง GPS
 */
export const stopGpsSharing = async (): Promise<{
  success: boolean;
  message?: string;
} | null> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.GPS_STOP);
    return response.data;
  } catch (error) {
    console.error('Stop GPS sharing error:', error);
    return null;
  }
};

/**
 * ดึงงานที่รอไรเดอร์
 */
export const getAvailableJobs = async (): Promise<{
  success: boolean;
  data?: {
    jobs: Array<{
      id: number;
      jobNumber: string;
      jobType: string;
      jobTypeText: string;
      title: string;
      pickup: {
        address: string;
        latitude: number;
        longitude: number;
      };
      delivery: {
        address: string;
        latitude: number;
        longitude: number;
      };
      distanceKm?: number;
      totalFee: number;
      riderEarnings: number;
      createdAt: string;
    }>;
    riderLocation: {
      latitude?: number;
      longitude?: number;
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.RIDER_JOBS_AVAILABLE);
    return response.data;
  } catch (error) {
    console.error('Get available jobs error:', error);
    return null;
  }
};

/**
 * ดึงงานปัจจุบันของไรเดอร์
 */
export const getCurrentJob = async (): Promise<{
  success: boolean;
  data?: {
    hasJob: boolean;
    job?: {
      id: number;
      jobNumber: string;
      jobType: string;
      jobTypeText: string;
      title: string;
      description?: string;
      status: string;
      statusText: string;
      pickup: {
        address: string;
        latitude: number;
        longitude: number;
        contactName?: string;
        contactPhone?: string;
        notes?: string;
      };
      delivery: {
        address: string;
        latitude: number;
        longitude: number;
        contactName?: string;
        contactPhone?: string;
        notes?: string;
      };
      distanceKm?: number;
      totalFee: number;
      riderEarnings: number;
      acceptedAt?: string;
      pickedUpAt?: string;
    };
    isTracking: boolean;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.RIDER_JOBS_CURRENT);
    return response.data;
  } catch (error) {
    console.error('Get current job error:', error);
    return null;
  }
};

/**
 * รับงาน
 */
export const acceptJob = async (jobId: number): Promise<{
  success: boolean;
  data?: {
    job: {
      id: number;
      jobNumber: string;
      status: string;
      statusText: string;
      pickup: {
        address: string;
        latitude: number;
        longitude: number;
        contactName?: string;
        contactPhone?: string;
        notes?: string;
      };
      delivery: {
        address: string;
        latitude: number;
        longitude: number;
        contactName?: string;
        contactPhone?: string;
        notes?: string;
      };
    };
    trackingEnabled: boolean;
    message: string;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(`${API_ENDPOINTS.RIDER_JOB_ACCEPT}/${jobId}/accept`);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'รับงานไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * อัพเดทสถานะงาน
 */
export const updateJobStatus = async (
  jobId: number,
  status: 'picking_up' | 'picked_up' | 'delivering' | 'delivered' | 'completed' | 'cancelled',
  options?: {
    proofImage?: string;
    signatureImage?: string;
    cancellationReason?: string;
  }
): Promise<{
  success: boolean;
  data?: {
    jobId: number;
    status: string;
    statusText: string;
    isTracking: boolean;
  };
  message?: string;
}> => {
  try {
    const formData = new FormData();
    formData.append('status', status);

    if (options?.proofImage) {
      const filename = options.proofImage.split('/').pop() || 'proof.jpg';
      const match = /\.(\w+)$/.exec(filename);
      const fileType = match ? `image/${match[1]}` : 'image/jpeg';
      formData.append('proof_image', {
        uri: options.proofImage,
        name: filename,
        type: fileType,
      } as unknown as Blob);
    }

    if (options?.signatureImage) {
      const filename = options.signatureImage.split('/').pop() || 'signature.jpg';
      const match = /\.(\w+)$/.exec(filename);
      const fileType = match ? `image/${match[1]}` : 'image/jpeg';
      formData.append('signature_image', {
        uri: options.signatureImage,
        name: filename,
        type: fileType,
      } as unknown as Blob);
    }

    if (options?.cancellationReason) {
      formData.append('cancellation_reason', options.cancellationReason);
    }

    const response = await apiClient.post(
      `${API_ENDPOINTS.RIDER_JOB_STATUS}/${jobId}/status`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'อัพเดทสถานะไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

// =====================================================
// Support Tickets
// =====================================================

export interface Ticket {
  id: number;
  ticketNumber: string;
  subject: string;
  category: string;
  categoryText: string;
  priority: string;
  priorityText: string;
  status: string;
  statusText: string;
  hasUnreadAdminMessage: boolean;
  messageCount: number;
  createdAt: string;
  lastMessageAt?: string;
}

export interface TicketMessage {
  id: number;
  message: string;
  isFromAdmin: boolean;
  userName: string;
  attachments?: string[];
  isRead: boolean;
  createdAt: string;
}

/**
 * ดึงรายการ tickets ของผู้ใช้
 */
export const getTickets = async (): Promise<{
  success: boolean;
  data?: {
    tickets: Ticket[];
    pagination: {
      total: number;
      currentPage: number;
      lastPage: number;
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.TICKETS);
    return response.data;
  } catch (error) {
    console.error('Get tickets error:', error);
    return null;
  }
};

/**
 * สร้าง ticket ใหม่
 */
export const createTicket = async (data: {
  subject: string;
  category?: string;
  priority?: string;
  message: string;
}): Promise<{
  success: boolean;
  data?: {
    ticketId: number;
    ticketNumber: string;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.TICKETS, data);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'สร้าง Ticket ไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ดูรายละเอียด ticket
 */
export const getTicket = async (ticketId: number): Promise<{
  success: boolean;
  data?: {
    ticket: {
      id: number;
      ticketNumber: string;
      subject: string;
      category: string;
      categoryText: string;
      priority: string;
      priorityText: string;
      status: string;
      statusText: string;
      satisfactionRating?: number;
      createdAt: string;
      resolvedAt?: string;
      closedAt?: string;
    };
    messages: TicketMessage[];
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(`${API_ENDPOINTS.TICKET_DETAIL}/${ticketId}`);
    return response.data;
  } catch (error) {
    console.error('Get ticket error:', error);
    return null;
  }
};

/**
 * ส่งข้อความใน ticket
 */
export const replyTicket = async (ticketId: number, message: string): Promise<{
  success: boolean;
  data?: {
    messageId: number;
    createdAt: string;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(`${API_ENDPOINTS.TICKET_REPLY}/${ticketId}/reply`, { message });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ส่งข้อความไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ให้คะแนนความพึงพอใจ
 */
export const rateTicket = async (ticketId: number, rating: number, comment?: string): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(`${API_ENDPOINTS.TICKET_RATE}/${ticketId}/rate`, { rating, comment });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ให้คะแนนไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

// =====================================================
// Notifications
// =====================================================

export interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  typeText: string;
  icon?: string;
  image?: string;
  actionUrl?: string;
  isRead: boolean;
  data?: Record<string, unknown>;
  createdAt: string;
  timeAgo: string;
}

/**
 * ดึงรายการการแจ้งเตือน
 */
export const getNotifications = async (): Promise<{
  success: boolean;
  data?: {
    unreadCount: number;
    notifications: Notification[];
    pagination: {
      total: number;
      currentPage: number;
      lastPage: number;
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.NOTIFICATIONS);
    return response.data;
  } catch (error) {
    console.error('Get notifications error:', error);
    return null;
  }
};

/**
 * ดึงจำนวนการแจ้งเตือนที่ยังไม่อ่าน
 */
export const getUnreadNotificationCount = async (): Promise<{
  success: boolean;
  data?: {
    unreadCount: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.NOTIFICATIONS_UNREAD_COUNT);
    return response.data;
  } catch (error) {
    console.error('Get unread notification count error:', error);
    return null;
  }
};

/**
 * ทำเครื่องหมายการแจ้งเตือนว่าอ่านแล้ว
 */
export const markNotificationRead = async (notificationId: number): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(`${API_ENDPOINTS.NOTIFICATION_READ}/${notificationId}/read`);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ทำเครื่องหมายไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว
 */
export const markAllNotificationsRead = async (): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.NOTIFICATIONS_MARK_ALL_READ);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ทำเครื่องหมายไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ลบการแจ้งเตือน
 */
export const deleteNotification = async (notificationId: number): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.delete(`${API_ENDPOINTS.NOTIFICATION_DELETE}/${notificationId}`);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ลบการแจ้งเตือนไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

// =====================================================
// Push Notification Token
// =====================================================

/**
 * บันทึก Push Token
 */
export const registerPushToken = async (data: {
  token: string;
  platform?: 'android' | 'ios' | 'web';
  device_id?: string;
  device_name?: string;
  app_version?: string;
}): Promise<{
  success: boolean;
  data?: {
    tokenId: number;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.PUSH_TOKEN, data);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'บันทึก Push Token ไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ลบ Push Token
 */
export const removePushToken = async (token: string): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.delete(API_ENDPOINTS.PUSH_TOKEN, {
      data: { token },
    });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ลบ Push Token ไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

// =====================================================
// Rank System
// =====================================================

export interface Rank {
  id: number;
  name: string;
  nameTh: string;
  description?: string;
  descriptionTh?: string;
  level: number;
  icon?: string;
  color?: string;
  badgeIcon?: string;
  avatarFrame?: string;
  frameAnimation?: string;
  commissionRate?: number;
  bonusMultiplier?: number;
  promotionBonus?: number;
  maxDownlineLevelBonus?: number;
  unilevelCommissionLevels?: number[];
  privileges?: string[];
  minPoints?: number;
  minReferrals?: number;
  minSales?: number;
  monthlyMaintenancePv?: number;
  withdrawalFeeDiscount?: number;
  maxWithdrawalsPerMonth?: number;
  isDefault?: boolean;
  isTopTier?: boolean;
}

export interface RankRequirement {
  id: number;
  type: string;
  typeText: string;
  value: number;
  operator: string;
  description?: string;
}

export interface RankBonus {
  id: number;
  type: string;
  rewardType: string;
  amount?: number;
  percentage?: number;
  description?: string;
}

export interface RankProgress {
  type: string;
  typeText: string;
  currentValue: number;
  requiredValue: number;
  progress: number;
  completed: boolean;
}

/**
 * ดึงรายการ Rank ทั้งหมด
 */
export const getRanks = async (): Promise<{
  success: boolean;
  data?: {
    ranks: Rank[];
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.RANKS);
    return response.data;
  } catch (error) {
    console.error('Get ranks error:', error);
    return null;
  }
};

/**
 * ดึงรายละเอียด Rank
 */
export const getRankDetail = async (rankId: number): Promise<{
  success: boolean;
  data?: {
    rank: Rank;
    requirements: RankRequirement[];
    bonuses: RankBonus[];
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(`${API_ENDPOINTS.RANK_DETAIL}/${rankId}`);
    return response.data;
  } catch (error) {
    console.error('Get rank detail error:', error);
    return null;
  }
};

/**
 * ดึงความคืบหน้า Rank ของผู้ใช้
 */
export const getUserRankProgress = async (): Promise<{
  success: boolean;
  data?: {
    currentRank: Rank | null;
    nextRank: Rank | null;
    statistics: {
      rankPoints: number;
      totalReferrals: number;
      activeReferrals: number;
      totalSales: number;
      teamSales: number;
    };
    progress: RankProgress[];
    overallProgress: number;
    isMaxRank: boolean;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.RANK_PROGRESS);
    return response.data;
  } catch (error) {
    console.error('Get rank progress error:', error);
    return null;
  }
};

/**
 * ดึง Leaderboard
 */
export const getLeaderboard = async (params?: {
  type?: 'referrals' | 'sales' | 'earnings';
  period?: 'all' | 'month' | 'week';
  limit?: number;
}): Promise<{
  success: boolean;
  data?: {
    type: string;
    period: string;
    leaders: {
      position: number;
      userId: number;
      name: string;
      avatar?: string;
      rank?: {
        name: string;
        nameTh: string;
        icon?: string;
        color?: string;
      };
      value: number;
    }[];
    currentUserPosition?: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.RANK_LEADERBOARD, { params });
    return response.data;
  } catch (error) {
    console.error('Get leaderboard error:', error);
    return null;
  }
};

// =====================================================
// MLM / Affiliate Network
// =====================================================

export interface AffiliateData {
  referralCode: string;
  referralLink: string;
  statistics: {
    directReferrals: number;
    totalTeamMembers: number;
    activeMembers: number;
  };
  earnings: {
    total: number;
    thisMonth: number;
    pending: number;
  };
  rank?: {
    id: number;
    name: string;
    nameTh: string;
    icon?: string;
    color?: string;
    commissionRate?: number;
  };
}

export interface TeamMember {
  id: number;
  name: string;
  email?: string;
  avatar?: string;
  level?: number;
  rank?: {
    name: string;
    nameTh: string;
    icon?: string;
    color?: string;
  };
  totalReferrals?: number;
  childrenCount?: number;
  isActive: boolean;
  joinedAt: string;
  daysAgo?: number;
  children?: TeamMember[];
}

/**
 * ดึงข้อมูล Affiliate ของผู้ใช้
 */
export const getMyAffiliate = async (): Promise<{
  success: boolean;
  data?: AffiliateData;
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.AFFILIATE);
    return response.data;
  } catch (error) {
    console.error('Get my affiliate error:', error);
    return null;
  }
};

/**
 * ดึงรายชื่อลูกทีมโดยตรง
 */
export const getDirectReferrals = async (params?: {
  page?: number;
  per_page?: number;
}): Promise<{
  success: boolean;
  data?: {
    referrals: TeamMember[];
    pagination: {
      total: number;
      currentPage: number;
      lastPage: number;
      perPage: number;
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.AFFILIATE_REFERRALS, { params });
    return response.data;
  } catch (error) {
    console.error('Get direct referrals error:', error);
    return null;
  }
};

/**
 * ดึงผังทีม (Unilevel Tree)
 */
export const getTeamTree = async (depth: number = 3): Promise<{
  success: boolean;
  data?: {
    root: {
      id: number;
      name: string;
      avatar?: string;
      rank?: {
        name: string;
        nameTh: string;
        icon?: string;
        color?: string;
      };
    };
    children: TeamMember[];
    totalMembers: number;
    depth: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.AFFILIATE_TEAM_TREE, {
      params: { depth },
    });
    return response.data;
  } catch (error) {
    console.error('Get team tree error:', error);
    return null;
  }
};

// =====================================================
// Commission System
// =====================================================

export interface Commission {
  id: number;
  type: string;
  typeText: string;
  level?: number;
  amount: number;
  pvAmount?: number;
  salesAmount?: number;
  percentage?: number;
  status: string;
  statusText: string;
  fromMember?: {
    id: number;
    name: string;
  };
  approvedAt?: string;
  paidAt?: string;
  createdAt: string;
}

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

/**
 * ดึงรายการคอมมิชชันพร้อมตัวกรอง
 */
export const getCommissionsList = async (params?: {
  status?: 'pending' | 'approved' | 'paid' | 'rejected';
  type?: string;
  per_page?: number;
  page?: number;
}): Promise<{
  success: boolean;
  data?: {
    summary: {
      pending: number;
      approved: number;
      paid: number;
    };
    commissions: Commission[];
    pagination: {
      total: number;
      currentPage: number;
      lastPage: number;
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.COMMISSIONS_LIST, { params });
    return response.data;
  } catch (error) {
    console.error('Get commissions error:', error);
    return null;
  }
};

/**
 * ดึงสรุปรายได้
 */
export const getEarningsSummary = async (): Promise<{
  success: boolean;
  data?: EarningsSummary;
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.COMMISSIONS_EARNINGS);
    return response.data;
  } catch (error) {
    console.error('Get earnings summary error:', error);
    return null;
  }
};

// =====================================================
// Admin Control APIs (เฉพาะ 2 อย่างเท่านั้น)
// =====================================================

/**
 * Banner Type
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
  sortOrder: number;
}

/**
 * 1. ดึง Banners โฆษณา (Admin ส่งมา)
 *
 * Admin สามารถ:
 * - เพิ่ม/แก้ไข/ลบ banner
 * - กำหนดตำแหน่ง (top, middle, bottom)
 * - กำหนด link ปลายทาง
 * - กำหนดระยะเวลาแสดง
 */
export const getBanners = async (position?: 'top' | 'middle' | 'bottom'): Promise<{
  success: boolean;
  data?: Banner[];
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.BANNERS, {
      params: { position },
    });
    return response.data;
  } catch (error) {
    console.error('Get banners error:', error);
    return null;
  }
};

/**
 * บันทึกการคลิก banner
 */
export const trackBannerClick = async (bannerId: number): Promise<void> => {
  try {
    await apiClient.post(`${API_ENDPOINTS.BANNER_CLICK}/${bannerId}/click`);
  } catch (error) {
    console.error('Track banner click error:', error);
  }
};

/**
 * 2. ลงทะเบียน Push Token (สำหรับรับ Push จาก Admin)
 *
 * Admin สามารถ:
 * - ส่ง push notification ไปยังผู้ใช้ทั้งหมดหรือเฉพาะกลุ่ม
 * - ส่งข้อความโปรโมชั่น, ข่าวสาร, แจ้งเตือนพิเศษ
 */
export const registerAdminPushToken = async (
  token: string,
  platform: 'ios' | 'android'
): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.REGISTER_PUSH_TOKEN, {
      token,
      platform,
    });
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ลงทะเบียน push token ไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด',
    };
  }
};

/**
 * 3. ลงทะเบียนเครื่อง (สำหรับ Admin Analytics Dashboard)
 *
 * Admin สามารถดู:
 * - จำนวนเครื่องที่ติดตั้งแอพ
 * - สถิติตาม Platform (iOS/Android)
 * - สถิติตาม App Version
 * - Daily Active Users (DAU)
 * - Retention Rate
 *
 * ข้อมูลนี้ใช้สำหรับวิเคราะห์เท่านั้น ไม่มีผลต่อการทำงานของแอพ
 */
export const registerDevice = async (deviceInfo: {
  deviceId: string;
  platform: 'ios' | 'android';
  deviceModel: string;
  deviceBrand: string;
  osVersion: string;
  appVersion: string;
  pushToken?: string;
  locale: string;
  timezone: string;
}): Promise<{
  success: boolean;
  data?: {
    deviceId: string;
    isNewDevice: boolean;
  };
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.DEVICE_REGISTER, deviceInfo);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ลงทะเบียนเครื่องไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด',
    };
  }
};

/**
 * ส่ง heartbeat เพื่อบันทึกว่าเครื่องยังใช้งานอยู่
 * ใช้สำหรับคำนวณ DAU/MAU และ Retention Rate
 */
export const sendDeviceHeartbeat = async (deviceId: string): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.post(API_ENDPOINTS.DEVICE_HEARTBEAT, { deviceId });
    return response.data;
  } catch (error) {
    // Silent fail - ไม่แสดง error ให้ user เห็น
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด',
    };
  }
};

// =====================================================
// Profile APIs
// =====================================================

/**
 * อัพเดทข้อมูลโปรไฟล์
 */
export const updateProfile = async (data: {
  name?: string;
  phone?: string;
  address?: string;
  bio?: string;
  bank_name?: string;
  bank_account?: string;
  bank_account_name?: string;
}): Promise<{
  success: boolean;
  data?: User;
  message?: string;
}> => {
  try {
    const response = await apiClient.put(API_ENDPOINTS.PROFILE_UPDATE, data);

    // อัพเดท user data ใน storage
    if (response.data.success && response.data.data) {
      await SecureStore.setItemAsync(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(response.data.data)
      );
    }

    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'อัพเดทข้อมูลไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * อัพโหลดรูปโปรไฟล์ (Avatar)
 */
export const uploadAvatar = async (imageUri: string): Promise<{
  success: boolean;
  data?: {
    avatarUrl: string;
    user: User;
  };
  message?: string;
}> => {
  try {
    const formData = new FormData();

    // แปลง URI ให้ถูกต้องสำหรับ React Native
    let fileUri = imageUri;
    // ถ้าเป็น content:// หรือ file:// ใช้ได้เลย
    // ถ้าไม่มี scheme ให้เพิ่ม file://
    if (!fileUri.startsWith('file://') && !fileUri.startsWith('content://')) {
      fileUri = `file://${fileUri}`;
    }

    const filename = imageUri.split('/').pop() || 'avatar.jpg';
    // ตรวจสอบ extension โดยละเลย query string
    const cleanFilename = filename.split('?')[0];
    const match = /\.(\w+)$/.exec(cleanFilename);
    let fileType = 'image/jpeg';
    if (match) {
      const ext = match[1].toLowerCase();
      if (ext === 'png') fileType = 'image/png';
      else if (ext === 'gif') fileType = 'image/gif';
      else if (ext === 'webp') fileType = 'image/webp';
      else if (ext === 'heic' || ext === 'heif') fileType = 'image/heic';
      else fileType = `image/${ext}`;
    }

    formData.append('avatar', {
      uri: fileUri,
      name: cleanFilename || 'avatar.jpg',
      type: fileType,
    } as unknown as Blob);

    console.log('📸 Uploading avatar:', { uri: fileUri, name: cleanFilename, type: fileType });

    // สำคัญ: ไม่ต้องตั้ง Content-Type เอง ให้ axios สร้าง boundary ให้
    const response = await apiClient.post(API_ENDPOINTS.AVATAR_UPLOAD, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      transformRequest: (data) => data, // ป้องกัน axios แปลง FormData
    });

    // อัพเดท user data ใน storage
    if (response.data.success && response.data.data?.user) {
      await SecureStore.setItemAsync(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(response.data.data.user)
      );
    }

    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'อัพโหลดรูปภาพไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * ลบรูปโปรไฟล์
 */
export const deleteAvatar = async (): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    const response = await apiClient.delete(API_ENDPOINTS.AVATAR_DELETE);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'ลบรูปภาพไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

/**
 * เปลี่ยนรหัสผ่าน
 */
export const changePassword = async (data: {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
}): Promise<{
  success: boolean;
  message?: string;
}> => {
  try {
    // Backend ต้องการ field ชื่อ 'password' และ 'password_confirmation'
    // แต่ Mobile App ใช้ 'new_password' และ 'new_password_confirmation'
    const requestData = {
      current_password: data.current_password,
      password: data.new_password,
      password_confirmation: data.new_password_confirmation,
    };
    const response = await apiClient.post(API_ENDPOINTS.CHANGE_PASSWORD, requestData);
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        message: error.response?.data?.message || 'เปลี่ยนรหัสผ่านไม่สำเร็จ',
      };
    }
    return {
      success: false,
      message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
    };
  }
};

// =====================================================
// MLM Tree APIs
// =====================================================

export interface TreeNode {
  id: number;
  name: string;
  email?: string;
  avatar?: string;
  level: number;
  rank?: {
    id: number;
    name: string;
    nameTh: string;
    icon?: string;
    color?: string;
  };
  statistics?: {
    directReferrals: number;
    totalTeamMembers: number;
    monthlyPV: number;
    totalSales: number;
  };
  isActive: boolean;
  joinedAt: string;
  children: TreeNode[];
  childrenCount: number;
  hasMoreChildren?: boolean;
}

/**
 * ดึงผังทีมแบบ Tree (สำหรับแสดง Interactive Tree)
 */
export const getMLMTree = async (params?: {
  userId?: number;
  depth?: number;
}): Promise<{
  success: boolean;
  data?: {
    root: TreeNode;
    maxDepth: number;
    totalMembers: number;
    currentUserLevel: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.MLM_TREE, { params });
    return response.data;
  } catch (error) {
    console.error('Get MLM tree error:', error);
    return null;
  }
};

/**
 * ดึง Children ของ node (Lazy Loading)
 */
export const getTreeNodeChildren = async (userId: number): Promise<{
  success: boolean;
  data?: {
    children: TreeNode[];
    hasMore: boolean;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(`${API_ENDPOINTS.MLM_TREE}/${userId}/children`);
    return response.data;
  } catch (error) {
    console.error('Get tree children error:', error);
    return null;
  }
};

/**
 * ค้นหาสมาชิกในทีม
 */
export const searchTeamMember = async (query: string): Promise<{
  success: boolean;
  data?: {
    results: TeamMember[];
    total: number;
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(API_ENDPOINTS.MLM_SEARCH, {
      params: { q: query },
    });
    return response.data;
  } catch (error) {
    console.error('Search team member error:', error);
    return null;
  }
};

/**
 * ดึงสถิติของสมาชิกในทีม
 */
export const getTeamMemberStats = async (userId: number): Promise<{
  success: boolean;
  data?: {
    member: TeamMember;
    statistics: {
      totalReferrals: number;
      activeReferrals: number;
      totalSales: number;
      monthlyPV: number;
      totalEarnings: number;
      joinedDaysAgo: number;
    };
    genealogy: {
      parent?: TeamMember;
      siblings?: TeamMember[];
    };
  };
  message?: string;
} | null> => {
  try {
    const response = await apiClient.get(`${API_ENDPOINTS.MLM_MEMBER}/${userId}`);
    return response.data;
  } catch (error) {
    console.error('Get team member stats error:', error);
    return null;
  }
};

// =====================================================
// Cart API - ระบบตะกร้าสินค้า (คำนวณทุกอย่างที่ server)
// =====================================================

/**
 * Cart Item Type จาก Server
 */
export interface CartItemFromServer {
  id: number;
  productId: number;
  productName: string;
  productImage: string | null;
  price: number;
  originalPrice: number;
  quantity: number;
  subtotal: number;
  pvValue: number;
  pvSubtotal: number;
  commissionRate: number;
  attributes: Record<string, any> | null;
  isAvailable: boolean;
  stock: number;
}

/**
 * Cart Summary Type จาก Server
 */
export interface CartSummary {
  totalItems: number;
  totalPrice: number;
  totalPV: number;
  shippingFee: number;
  grandTotal: number;
  estimatedCommission: number;
}

/**
 * Cart Response Type จาก Server
 */
export interface CartResponse {
  success: boolean;
  data?: {
    cartId: number;
    items: CartItemFromServer[];
    summary: CartSummary;
    freeShippingThreshold: number;
    amountToFreeShipping: number;
  };
  message?: string;
}

/**
 * ดึงข้อมูลตะกร้า (คำนวณทุกอย่างจาก server)
 */
export const getCart = async (): Promise<CartResponse | null> => {
  try {
    const response = await apiClient.get('/cart');
    return response.data;
  } catch (error) {
    console.error('Get cart error:', error);
    return null;
  }
};

/**
 * เพิ่มสินค้าลงตะกร้า
 */
export const addToCart = async (
  productId: number,
  quantity: number = 1,
  attributes?: Record<string, any>
): Promise<CartResponse | null> => {
  try {
    const response = await apiClient.post('/cart/add', {
      product_id: productId,
      quantity,
      attributes,
    });
    return response.data;
  } catch (error: any) {
    console.error('Add to cart error:', error);
    if (error?.response?.data) {
      return error.response.data;
    }
    return null;
  }
};

/**
 * อัพเดทจำนวนสินค้าในตะกร้า
 */
export const updateCartItem = async (
  itemId: number,
  quantity: number
): Promise<CartResponse | null> => {
  try {
    const response = await apiClient.put(`/cart/items/${itemId}`, { quantity });
    return response.data;
  } catch (error: any) {
    console.error('Update cart item error:', error);
    if (error?.response?.data) {
      return error.response.data;
    }
    return null;
  }
};

/**
 * ลบสินค้าออกจากตะกร้า
 */
export const removeFromCart = async (itemId: number): Promise<CartResponse | null> => {
  try {
    const response = await apiClient.delete(`/cart/items/${itemId}`);
    return response.data;
  } catch (error: any) {
    console.error('Remove from cart error:', error);
    if (error?.response?.data) {
      return error.response.data;
    }
    return null;
  }
};

/**
 * ล้างตะกร้าทั้งหมด
 */
export const clearCartApi = async (): Promise<CartResponse | null> => {
  try {
    const response = await apiClient.delete('/cart/clear');
    return response.data;
  } catch (error) {
    console.error('Clear cart error:', error);
    return null;
  }
};

/**
 * Promo Code Response
 */
export interface PromoCodeResponse {
  success: boolean;
  message?: string;
  data?: {
    code: string;
    discountType: 'fixed' | 'percent' | 'shipping';
    discountAmount: number;
    totalPrice: number;
    shippingFee: number;
    grandTotal: number;
  };
}

/**
 * ใช้โค้ดส่วนลด
 */
export const applyPromoCode = async (code: string): Promise<PromoCodeResponse | null> => {
  try {
    const response = await apiClient.post('/cart/promo', { code });
    return response.data;
  } catch (error: any) {
    console.error('Apply promo code error:', error);
    if (error?.response?.data) {
      return error.response.data;
    }
    return null;
  }
};

/**
 * Checkout Request
 */
export interface CheckoutRequest {
  payment_method: 'wallet' | 'bank' | 'card' | 'cod';
  shipping_address_id?: number;
  promo_code?: string;
  note?: string;
}

/**
 * Checkout Response
 */
export interface CheckoutResponse {
  success: boolean;
  message?: string;
  data?: {
    orderId: number;
    orderNumber: string;
    status: string;
    paymentStatus: string;
    total: number;
    pvEarned: number;
    paymentMethod: string;
    required?: number;
    available?: number;
    shortfall?: number;
  };
}

/**
 * สร้างคำสั่งซื้อ (Checkout)
 */
export const checkout = async (data: CheckoutRequest): Promise<CheckoutResponse | null> => {
  try {
    const response = await apiClient.post('/cart/checkout', data);
    return response.data;
  } catch (error: any) {
    console.error('Checkout error:', error);
    if (error?.response?.data) {
      return error.response.data;
    }
    return null;
  }
};

// =====================================================
// Order API
// =====================================================

/**
 * Order Summary Response
 */
export interface OrderSummary {
  id: number;
  order_number: string;
  status: string;
  status_label: string;
  payment_status: string;
  total_amount: number;
  items_count: number;
  first_item: {
    product_name: string;
    product_image: string | null;
  } | null;
  has_unread_messages: boolean;
  last_message_at: string | null;
  created_at: string;
}

/**
 * Order Detail Response
 */
export interface OrderDetail {
  id: number;
  order_number: string;
  status: string;
  status_label: string;
  payment_status: string;
  payment_status_label: string;
  payment_method: string;
  subtotal: number;
  shipping_fee: number;
  discount: number;
  total_amount: number;
  currency: string;
  shipping: {
    name: string;
    phone: string;
    address: string;
    province: string;
    district: string;
    subdistrict: string;
    postal_code: string;
  };
  items: Array<{
    id: number;
    product_id: number;
    product_name: string;
    product_image: string | null;
    quantity: number;
    price: number;
    total: number;
  }>;
  note: string | null;
  created_at: string;
  paid_at: string | null;
  shipped_at: string | null;
  delivered_at: string | null;
}

/**
 * Shipping Provider
 */
export interface ShippingProvider {
  id: number;
  code: string;
  name: string;
  name_en: string;
  logo: string | null;
  hotline: string | null;
}

/**
 * Tracking History
 */
export interface TrackingHistory {
  id: number;
  status: string;
  status_label: string;
  description: string | null;
  location: string | null;
  created_at: string;
}

/**
 * Order Tracking Response
 */
export interface OrderTracking {
  order_number: string;
  status: string;
  status_label: string;
  tracking_number: string | null;
  tracking_url: string | null;
  shipping_provider: ShippingProvider | null;
  estimated_delivery_at: string | null;
  shipped_at: string | null;
  delivered_at: string | null;
  history: TrackingHistory[];
}

/**
 * Order Message
 */
export interface OrderMessage {
  id: number;
  sender_type: 'customer' | 'seller' | 'admin';
  sender_name: string;
  sender_avatar: string | null;
  message: string;
  attachment: string | null;
  attachment_type: string | null;
  is_system_message: boolean;
  is_mine: boolean;
  created_at: string;
}

/**
 * ดึงรายการคำสั่งซื้อ
 */
export const getOrders = async (
  page: number = 1,
  perPage: number = 15,
  status?: string
): Promise<{ orders: OrderSummary[]; pagination: any } | null> => {
  try {
    const params: any = { page, per_page: perPage };
    if (status) params.status = status;

    const response = await apiClient.get('/orders', { params });
    if (response.data?.success) {
      return response.data.data;
    }
    return null;
  } catch (error) {
    console.error('Get orders error:', error);
    return null;
  }
};

/**
 * ดึงรายละเอียดคำสั่งซื้อ
 */
export const getOrderDetail = async (orderId: number): Promise<OrderDetail | null> => {
  try {
    const response = await apiClient.get(`/orders/${orderId}`);
    if (response.data?.success) {
      return response.data.data;
    }
    return null;
  } catch (error) {
    console.error('Get order detail error:', error);
    return null;
  }
};

/**
 * ยกเลิกคำสั่งซื้อ
 */
export const cancelOrder = async (orderId: number): Promise<{ success: boolean; message?: string }> => {
  try {
    const response = await apiClient.post(`/orders/${orderId}/cancel`);
    return response.data;
  } catch (error: any) {
    console.error('Cancel order error:', error);
    return {
      success: false,
      message: error?.response?.data?.message || 'ไม่สามารถยกเลิกคำสั่งซื้อได้',
    };
  }
};

/**
 * ดึงข้อมูล Tracking ของคำสั่งซื้อ
 */
export const getOrderTracking = async (orderId: number): Promise<OrderTracking | null> => {
  try {
    const response = await apiClient.get(`/orders/${orderId}/tracking`);
    if (response.data?.success) {
      return response.data.data;
    }
    return null;
  } catch (error) {
    console.error('Get order tracking error:', error);
    return null;
  }
};

/**
 * ดึงรายการบริษัทขนส่ง
 */
export const getShippingProviders = async (): Promise<ShippingProvider[]> => {
  try {
    const response = await apiClient.get('/shipping-providers');
    if (response.data?.success) {
      return response.data.data || [];
    }
    return [];
  } catch (error) {
    console.error('Get shipping providers error:', error);
    return [];
  }
};

/**
 * ดึงข้อความแชทของคำสั่งซื้อ
 */
export const getOrderMessages = async (
  orderId: number,
  page: number = 1,
  perPage: number = 50
): Promise<{ messages: OrderMessage[]; pagination: any } | null> => {
  try {
    const response = await apiClient.get(`/orders/${orderId}/messages`, {
      params: { page, per_page: perPage },
    });
    if (response.data?.success) {
      return response.data.data;
    }
    return null;
  } catch (error) {
    console.error('Get order messages error:', error);
    return null;
  }
};

/**
 * ส่งข้อความแชท
 */
export const sendOrderMessage = async (
  orderId: number,
  message: string,
  attachment?: any
): Promise<{ success: boolean; data?: OrderMessage; message?: string }> => {
  try {
    const formData = new FormData();
    if (message) formData.append('message', message);
    if (attachment) formData.append('attachment', attachment);

    const response = await apiClient.post(`/orders/${orderId}/messages`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  } catch (error: any) {
    console.error('Send order message error:', error);
    return {
      success: false,
      message: error?.response?.data?.message || 'ไม่สามารถส่งข้อความได้',
    };
  }
};

/**
 * ดึงจำนวนข้อความที่ยังไม่ได้อ่าน
 */
export const getUnreadMessageCount = async (): Promise<number> => {
  try {
    const response = await apiClient.get('/orders/unread-messages');
    if (response.data?.success) {
      return response.data.data?.unread_count || 0;
    }
    return 0;
  } catch (error) {
    console.error('Get unread message count error:', error);
    return 0;
  }
};

// Export axios instance สำหรับใช้งานตรงๆ ถ้าต้องการ
export { apiClient };

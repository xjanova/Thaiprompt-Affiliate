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
 * ดึงการตั้งค่า app
 */
export const getSettings = async (): Promise<Record<string, unknown> | null> => {
  try {
    const response = await apiClient.get<ApiResponse<Record<string, unknown>>>(
      API_ENDPOINTS.SETTINGS
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get settings error:', error);
    return null;
  }
};

// =====================================================
// Products APIs
// =====================================================

/**
 * ดึงรายการสินค้า
 */
export const getProducts = async (
  params?: { category?: string | null; search?: string }
): Promise<Product[] | null> => {
  try {
    const queryParams = new URLSearchParams();
    if (params?.category) queryParams.append('category', params.category);
    if (params?.search) queryParams.append('search', params.search);

    const response = await apiClient.get<ApiResponse<Product[]>>(
      `${API_ENDPOINTS.PRODUCTS}?${queryParams.toString()}`
    );
    return response.data.data || null;
  } catch (error) {
    console.error('Get products error:', error);
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

    // สร้าง file object จาก uri
    const filename = imageUri.split('/').pop() || `${type}.jpg`;
    const match = /\.(\w+)$/.exec(filename);
    const fileType = match ? `image/${match[1]}` : 'image/jpeg';

    formData.append('image', {
      uri: imageUri,
      name: filename,
      type: fileType,
    } as unknown as Blob);
    formData.append('type', type);

    const response = await apiClient.post(API_ENDPOINTS.KYC_UPLOAD, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
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

// Export axios instance สำหรับใช้งานตรงๆ ถ้าต้องการ
export { apiClient };

import { AxiosError, type InternalAxiosRequestConfig } from 'axios';
import { afterEach, beforeEach, describe, expect, it, jest } from '@jest/globals';
import * as SecureStore from 'expo-secure-store';
import { apiClient, setAuthHeader } from '@/services/api';
import { getReferralInfo } from '../accountApi';
import { getEkycStatus } from '../ekycApi';
import { useAuthStore } from '@/stores/authStore';
import { STORAGE_KEYS } from '@/constants';

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));
jest.mock('@/config/appConfig', () => ({ APP_REQUEST_BUILD: 50 }));
jest.mock('@/stores/syncStore', () => ({
  useSyncStore: {
    getState: () => ({
      startSync: jest.fn(), completeSync: jest.fn(), failSync: jest.fn(), setStatus: jest.fn(), isConnected: true,
    }),
  },
}));
jest.mock('@/services/api/merchantApi', () => ({ resetSellerCache: jest.fn() }));
jest.mock('@/services/api/taladsodSellerApi', () => ({ resetFreshMarketSellerCache: jest.fn() }));
jest.mock('@/services/api/bannerApi', () => ({ clearBannerCache: jest.fn() }));
jest.mock('@/services/notifications', () => ({ getPushIdentity: jest.fn(), clearStoredPushToken: jest.fn() }));
jest.mock('@/services/network', () => ({ checkNetworkStatus: jest.fn() }));
jest.mock('@/utils/webAuthStorage', () => ({
  clearPendingWebAuth: jest.fn(), loadPendingWebAuth: jest.fn(), savePendingWebAuth: jest.fn(),
}));

const rejectHttp = (config: InternalAxiosRequestConfig, status = 401) =>
  new AxiosError('Request failed', 'ERR_BAD_REQUEST', config, undefined, {
    config, status, statusText: 'Error', headers: {}, data: { message: 'Unauthenticated.' },
  });

describe('expired mobile session', () => {
  const storage = new Map<string, string>();

  beforeEach(() => {
    jest.useFakeTimers();
    jest.clearAllMocks();
    storage.clear();
    storage.set(STORAGE_KEYS.AUTH_TOKEN, 'expired-token');
    storage.set(STORAGE_KEYS.USER_DATA, JSON.stringify({ id: 7 }));
    jest.mocked(SecureStore.getItemAsync).mockImplementation(async (key: string) => storage.get(key) ?? null);
    jest.mocked(SecureStore.deleteItemAsync).mockImplementation(async (key: string) => { storage.delete(key); });
    setAuthHeader('expired-token');
    useAuthStore.setState({
      user: { id: 7 } as any, token: 'expired-token', isAuthenticated: true, isOfflineMode: false, error: null,
    });
    apiClient.defaults.adapter = async (config) => { throw rejectHttp(config); };
  });

  afterEach(() => {
    delete apiClient.defaults.headers.common.Authorization;
    jest.clearAllTimers();
    jest.useRealTimers();
  });

  it.each([
    ['KYC', getEkycStatus],
    ['referral', getReferralInfo],
  ])('%s 401 clears the visible session and asks for login', async (_label, call) => {
    const result = await call();
    expect(result).toMatchObject({ success: false, code: 'UNAUTHENTICATED', status: 401 });
    expect(useAuthStore.getState()).toMatchObject({
      user: null, token: null, isAuthenticated: false, isOfflineMode: false,
      error: 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่อีกครั้ง',
    });
    expect(storage.has(STORAGE_KEYS.AUTH_TOKEN)).toBe(false);
    expect(storage.has(STORAGE_KEYS.USER_DATA)).toBe(false);
    expect(apiClient.defaults.headers.common.Authorization).toBeUndefined();
  });

  it('loads the saved token when the in-memory header is missing', async () => {
    delete apiClient.defaults.headers.common.Authorization;
    await getReferralInfo();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
    expect(storage.has(STORAGE_KEYS.AUTH_TOKEN)).toBe(false);
  });

  it('keeps a new login when an older request returns 401 late', async () => {
    apiClient.defaults.adapter = async (config) => {
      storage.set(STORAGE_KEYS.AUTH_TOKEN, 'new-token');
      setAuthHeader('new-token');
      useAuthStore.setState({ token: 'new-token' });
      throw rejectHttp(config);
    };
    await getReferralInfo();
    expect(useAuthStore.getState()).toMatchObject({ token: 'new-token', isAuthenticated: true, error: null });
    expect(storage.get(STORAGE_KEYS.AUTH_TOKEN)).toBe('new-token');
    expect(apiClient.defaults.headers.common.Authorization).toBe('Bearer new-token');
  });

  it('keeps the session for timeouts, offline errors, and server failures', async () => {
    for (const code of ['ECONNABORTED', 'ERR_NETWORK', 'SERVER']) {
      apiClient.defaults.adapter = async (config) => {
        throw code === 'SERVER' ? rejectHttp(config, 500) : new AxiosError('Failed', code, config);
      };
      const result = await getReferralInfo();
      expect(result.success).toBe(false);
      expect(useAuthStore.getState()).toMatchObject({ token: 'expired-token', isAuthenticated: true, error: null });
      expect(storage.get(STORAGE_KEYS.AUTH_TOKEN)).toBe('expired-token');
    }
  });

  it('does not clear an unrelated session on a request without a bearer token', async () => {
    delete apiClient.defaults.headers.common.Authorization;
    storage.delete(STORAGE_KEYS.AUTH_TOKEN);
    await expect(apiClient.post('/login', {})).rejects.toMatchObject({ response: { status: 401 } });
    expect(useAuthStore.getState()).toMatchObject({ isAuthenticated: true, error: null });
    expect(SecureStore.deleteItemAsync).not.toHaveBeenCalled();
  });

  it('still clears the visible session and header when secure storage fails', async () => {
    const log = jest.spyOn(console, 'error').mockImplementation(() => {});
    jest.mocked(SecureStore.deleteItemAsync).mockRejectedValue(new Error('Secure storage unavailable'));
    try {
      const result = await getReferralInfo();
      expect(result).toMatchObject({ success: false, status: 401 });
      expect(useAuthStore.getState().isAuthenticated).toBe(false);
      expect(apiClient.defaults.headers.common.Authorization).toBeUndefined();
    } finally {
      log.mockRestore();
    }
  });

  it('returns a successful referral response without clearing the session', async () => {
    apiClient.defaults.adapter = async (config) => ({
      config, status: 200, statusText: 'OK', headers: {},
      data: { success: true, data: { referralCode: 'TEST7', referralLink: 'https://main.thaiprompt.online/register?ref=TEST7' } },
    });
    const result = await getReferralInfo();
    expect(result).toMatchObject({ success: true, data: { referralCode: 'TEST7' } });
    expect(useAuthStore.getState().isAuthenticated).toBe(true);
  });
});

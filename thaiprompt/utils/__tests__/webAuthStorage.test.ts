/**
 * เก็บ PKCE verifier ไว้ในเครื่อง — แอปถูกปิดระหว่างอยู่ใน Custom Tab ยังแลก code ได้
 * แต่ต้อง state ตรง · ไม่เก่ากว่า 15 นาที · ลบทิ้งหลังใช้
 */

import { afterEach, beforeEach, describe, expect, it, jest } from '@jest/globals';

const mockStore = new Map<string, string>();

jest.mock('expo-secure-store', () => ({
  setItemAsync: async (key: string, value: string) => {
    mockStore.set(key, value);
  },
  getItemAsync: async (key: string) => mockStore.get(key) ?? null,
  deleteItemAsync: async (key: string) => {
    mockStore.delete(key);
  },
}));

// eslint-disable-next-line import/first
import {
  clearPendingWebAuth,
  loadPendingWebAuth,
  savePendingWebAuth,
  WEB_AUTH_PENDING_MAX_AGE_MS,
} from '../webAuthStorage';

describe('webAuthStorage', () => {
  beforeEach(() => {
    mockStore.clear();
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  it('คืน verifier เมื่อ state ตรงและยังไม่หมดอายุ', async () => {
    await savePendingWebAuth('verifier-1', 'state-1');
    const pending = await loadPendingWebAuth('state-1');
    expect(pending?.verifier).toBe('verifier-1');
  });

  it('state ไม่ตรง = ไม่คืน', async () => {
    await savePendingWebAuth('verifier-1', 'state-1');
    expect(await loadPendingWebAuth('other-state')).toBeNull();
  });

  it('เก่ากว่า 15 นาที = ไม่คืน และลบทิ้ง', async () => {
    const now = Date.now();
    jest.spyOn(Date, 'now').mockReturnValue(now);
    await savePendingWebAuth('verifier-1', 'state-1');

    jest.spyOn(Date, 'now').mockReturnValue(now + WEB_AUTH_PENDING_MAX_AGE_MS + 1000);
    expect(await loadPendingWebAuth('state-1')).toBeNull();
    expect(mockStore.size).toBe(0);
  });

  it('ลบแล้วอ่านไม่ได้อีก (ใช้ครั้งเดียว)', async () => {
    await savePendingWebAuth('verifier-1', 'state-1');
    await clearPendingWebAuth();
    expect(await loadPendingWebAuth('state-1')).toBeNull();
  });
});

/**
 * ทดสอบตัวกรองเนื้อหาตามนโยบาย Google Play (แจ้งเตือน + รายการกระเป๋าเงิน)
 * ใช้ข้อมูลรูปแบบเดียวกับที่ server ส่งจริง (user_notifications + wallet transactions)
 */

import { describe, expect, it } from '@jest/globals';
import {
  isRestrictedNotification,
  walletReferenceLabel,
  walletTransactionTitle,
} from '../storePolicy';

describe('isRestrictedNotification', () => {
  it('ซ่อนแจ้งเตือนคอมมิชชั่นที่ server แปลงเป็น type wallet แล้ว', () => {
    expect(
      isRestrictedNotification({
        type: 'wallet',
        title: 'ได้รับคอมมิชชั่น',
        body: 'คุณได้รับคอมมิชชั่นจำนวน 50.00 THB',
        data: { amount: 50, currency: 'THB', source_notification_id: 9, source_type: 'commission' },
      })
    ).toBe(true);
  });

  it('ซ่อน push payload ที่ type เป็นระบบเครือข่าย', () => {
    expect(isRestrictedNotification({ title: 'ยินดีด้วย', body: 'คุณได้เลื่อนขั้น', data: { type: 'rank_up' } })).toBe(true);
    expect(isRestrictedNotification({ title: 'แจ้งเตือน', body: '', data: { type: 'mlm_team_transfer' } })).toBe(true);
  });

  it('แจ้งเตือนงานไรเดอร์/ออเดอร์/ถอนเงินยังแสดงตามปกติ', () => {
    expect(isRestrictedNotification({ type: 'rider', title: 'มีงานใหม่ใกล้คุณ', body: 'ค่าส่ง 45 บาท' })).toBe(false);
    expect(isRestrictedNotification({ type: 'order', title: 'ร้านยืนยันออเดอร์แล้ว', data: { source_type: 'fresh_market_order' } })).toBe(false);
    expect(isRestrictedNotification({ type: 'wallet', title: 'โอนเงินถอนเรียบร้อย', data: { source_type: 'withdrawal_completed' } })).toBe(false);
  });
});

describe('walletTransactionTitle', () => {
  it('คอมมิชชั่นหลายชั้น → ค่าแนะนำ', () => {
    expect(walletTransactionTitle('จ่ายคอมมิชชั่นดูดวง L2', 'App\Models\FortuneCommission', true)).toBe('ค่าแนะนำ');
    expect(walletTransactionTitle('คอมมิชชัน', 'commission', true)).toBe('ค่าแนะนำ');
    expect(walletTransactionTitle('หักคืนคอมมิชชั่น', 'mlm_commission_clawback', false)).toBe('ปรับยอดค่าแนะนำ');
  });

  it('รายการปกติไม่เปลี่ยน', () => {
    expect(walletTransactionTitle('ค่าส่งงาน #123', 'rider_job', true)).toBe('ค่าส่งงาน #123');
    expect(walletTransactionTitle('ชำระคำสั่งซื้อ #A1', 'order', false)).toBe('ชำระคำสั่งซื้อ #A1');
  });
});

describe('walletReferenceLabel', () => {
  it('ไม่แสดงชื่อคลาสดิบของ server', () => {
    expect(walletReferenceLabel('App\Models\FortuneCommission', true)).toBe('ค่าแนะนำ');
    expect(walletReferenceLabel('CoinExchangeRequest', false)).toBe('รายจ่าย');
    expect(walletReferenceLabel('withdrawal', false)).toBe('ถอนเงิน');
    expect(walletReferenceLabel(undefined, true)).toBe('รายรับ');
  });
});

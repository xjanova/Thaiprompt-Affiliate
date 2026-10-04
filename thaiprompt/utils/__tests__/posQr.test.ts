/**
 * ทดสอบ QR ชำระเงินจากเครื่อง POS (TPPOS1.{token}) + การพา push ไปหน้าที่ถูกต้อง
 * (pos_payment_request และ push การส่งมอบของไรเดอร์รอบ 2)
 *
 * รัน: npx jest utils/__tests__/posQr.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { isPosQr, parsePosToken, posPayPath } from '../posQr';
import { routeForNotification } from '../notificationRouting';
import { isAllowedInternalRoute } from '../linking';

const TOKEN = 'k3J9aB7cD1eF5gH2iJ8kL4mN6oP0qR3sT9uV1wX2';

describe('posQr', () => {
  it('อ่าน token ได้ทั้งแบบมีและไม่มี prefix', () => {
    expect(parsePosToken(`TPPOS1.${TOKEN}`)).toBe(TOKEN);
    expect(parsePosToken(TOKEN)).toBe(TOKEN);
    expect(parsePosToken(`  TPPOS1.${TOKEN}\n`)).toBe(TOKEN);
    expect(parsePosToken([TOKEN])).toBe(TOKEN);
  });

  it('ปฏิเสธค่าที่ไม่ใช่ token (กันค่าแปลกปลอมเข้า URL/path)', () => {
    expect(parsePosToken('TPPOS1.')).toBeNull();
    expect(parsePosToken('TPPOS1.short')).toBeNull();
    expect(parsePosToken(`TPPOS1.${TOKEN}/../x`)).toBeNull();
    expect(parsePosToken(`${TOKEN}&next=//evil`)).toBeNull();
    expect(parsePosToken(undefined)).toBeNull();
    expect(parsePosToken(42)).toBeNull();
  });

  it('แยก QR ของร้านออกจาก wallet address', () => {
    expect(isPosQr(`TPPOS1.${TOKEN}`)).toBe(true);
    expect(isPosQr('0x8f3b2c9d1e0a7f6b5c4d3e2f1a0b9c8d7e6f5a4b')).toBe(false);
  });

  it('path หน้าจ่ายผ่าน allowlist ของลิงก์ภายใน', () => {
    expect(posPayPath(TOKEN)).toBe(`/pos-pay?token=${TOKEN}`);
    expect(isAllowedInternalRoute(posPayPath(TOKEN))).toBe(true);
    expect(isAllowedInternalRoute('/pos-pay')).toBe(true);
  });
});

describe('routeForNotification — คำขอจ่ายจากร้าน', () => {
  it('pos_payment_request → หน้าจ่าย', () => {
    expect(routeForNotification({ type: 'pos_payment_request', token: TOKEN, screen: 'pos-pay' })).toBe(`/pos-pay?token=${TOKEN}`);
    expect(routeForNotification({ type: 'pos_payment_request', token: `TPPOS1.${TOKEN}` })).toBe(`/pos-pay?token=${TOKEN}`);
  });

  it('token ผิดรูปแบบ → หน้าแจ้งเตือน', () => {
    expect(routeForNotification({ type: 'pos_payment_request', token: '../../evil' })).toBe('/notifications');
    expect(routeForNotification({ type: 'pos_payment_request' })).toBe('/notifications');
  });
});

describe('routeForNotification — การส่งมอบ (ไรเดอร์รอบ 2, payload จริงของ HandoverService: มี screen ไม่มี role)', () => {
  it('ไรเดอร์มาถึง / วางของไว้ → หน้ารับของของผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'handover_arrived', source: 'shop', order_id: 901, job_id: 55, screen: 'order-handover' })).toBe(
      '/handover/shop/901'
    );
    expect(
      routeForNotification({ type: 'handover_auto_release_scheduled', source: 'fresh-market', order_id: 9, job_id: 5, screen: 'order-handover' })
    ).toBe('/handover/fresh-market/9');
  });

  it('ส่งมอบสำเร็จ → ผู้ซื้อ / ไรเดอร์ / ร้าน ตาม screen', () => {
    expect(routeForNotification({ type: 'handover_completed', source: 'shop', order_id: 901, job_id: 55, screen: 'order' })).toBe('/order/901');
    expect(routeForNotification({ type: 'handover_completed', source: 'shop', order_id: 901, job_id: 55, screen: 'rider-job-detail' })).toBe(
      '/rider-job-detail?id=55'
    );
    expect(routeForNotification({ type: 'handover_completed', source: 'shop', order_id: 901, job_id: 55, screen: 'merchant-order' })).toBe(
      '/merchant/order/901'
    );
    expect(
      routeForNotification({ type: 'handover_completed', source: 'fresh-market', order_id: 9, job_id: 5, screen: 'merchant-order' })
    ).toBe('/merchant/taladsod/orders?focus=9');
  });

  it('ผลตัดสิน → ผู้ซื้อ / ไรเดอร์', () => {
    expect(routeForNotification({ type: 'handover_resolved', source: 'shop', order_id: 901, job_id: 55, resolution: 'refund', screen: 'order' })).toBe(
      '/order/901'
    );
    expect(
      routeForNotification({ type: 'handover_resolved', source: 'shop', order_id: 901, job_id: 55, resolution: 'release', screen: 'rider-job-detail' })
    ).toBe('/rider-job-detail?id=55');
  });

  it('ไม่มี screen → หน้าออเดอร์ผู้ซื้อ ยกเว้นร้องเรียน (แจ้งแอดมินเท่านั้น)', () => {
    expect(routeForNotification({ type: 'handover_completed', source: 'shop', order_id: 901 })).toBe('/order/901');
    expect(routeForNotification({ type: 'handover_disputed', source: 'shop', order_id: 901, job_id: 55, reason: 'not_received' })).toBe(
      '/notifications'
    );
  });
});

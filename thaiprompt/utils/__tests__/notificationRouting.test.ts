/**
 * ทดสอบการแปลง data ของ push → หน้าในแอป (เฉพาะ payload ของร้านตลาดสด / ไรเดอร์ที่เพิ่มใน wave 2)
 *
 * รัน: npx jest utils/__tests__/notificationRouting.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { routeForNotification } from '../notificationRouting';

describe('routeForNotification — ร้านตลาดสด', () => {
  it('ออเดอร์ใหม่ของร้าน → หน้าออเดอร์ร้านพร้อมไฮไลต์', () => {
    expect(routeForNotification({ type: 'fresh_market_order', role: 'seller', order_id: 42, event: 'new_order' })).toBe(
      '/merchant/taladsod/orders?focus=42'
    );
    expect(routeForNotification({ type: 'fresh_market_order', role: 'seller' })).toBe('/merchant/taladsod/orders');
  });

  it('แอดมินอนุมัติ/ระงับร้าน (seller_account ไม่มี role) → หน้าร้านตลาดสดของฉัน ไม่ใช่ออเดอร์ผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'fresh_market_order', event: 'seller_account', seller_id: 3, channel: 'orders' })).toBe(
      '/merchant/taladsod'
    );
  });

  it('push ของแอดมินไม่พาไปหน้าออเดอร์ผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'fresh_market_order', role: 'admin', order_id: 42, event: 'failed' })).toBe('/notifications');
  });

  it('ผู้ซื้อ → หน้าออเดอร์ของผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'fresh_market_order', role: 'buyer', order_id: 42 })).toBe('/taladsod/order/42');
    expect(routeForNotification({ type: 'fresh_market_order', role: 'buyer' })).toBe('/taladsod/orders');
  });

  it('delivery_update ตลาดสด (ไม่มี role) → หน้าออเดอร์ ซึ่งพาร้านไปหน้าออเดอร์ร้านเองตาม viewer_role', () => {
    expect(routeForNotification({ type: 'delivery_update', event: 'accepted', source_type: 'FreshMarketOrder', source_id: 9 })).toBe(
      '/taladsod/order/9'
    );
  });

  it('ร้านถูกปิดอัตโนมัติ → หน้าร้านตลาดสดของฉัน', () => {
    expect(
      routeForNotification({ type: 'fresh_market_shop', event: 'auto_closed', reason: 'time_up', seller_id: 3, screen: 'merchant-taladsod' })
    ).toBe('/merchant/taladsod');
  });
});

describe('routeForNotification — ไรเดอร์', () => {
  it('ร้านย้ายจุดรับของ → หน้างานพร้อมป้ายเตือน', () => {
    expect(routeForNotification({ type: 'rider_job_update', event: 'pickup_moved', job_id: '7' })).toBe(
      '/rider-job-detail?id=7&moved=1'
    );
  });

  it('อัปเดตงานทั่วไป → หน้างาน', () => {
    expect(routeForNotification({ type: 'rider_job_update', event: 'cancelled', job_id: 7 })).toBe('/rider-job-detail?id=7');
  });
});

describe('routeForNotification — แชทออเดอร์ร้านค้า', () => {
  it('ร้านได้ข้อความจากลูกค้า (seller_order_message) → แท็บแชทของหน้าออเดอร์ร้าน', () => {
    expect(
      routeForNotification({ type: 'seller_order_message', role: 'seller', order_id: 15, message_id: 3, channel: 'messages' })
    ).toBe('/merchant/order/15?tab=chat');
    expect(routeForNotification({ type: 'seller_order_message' })).toBe('/merchant/orders?status=unread_chat');
  });

  it('order_message ที่ระบุ role=seller ยังพาไปหน้าร้าน', () => {
    expect(routeForNotification({ type: 'order_message', role: 'seller', order_id: 15 })).toBe('/merchant/order/15?tab=chat');
  });

  it('ผู้ซื้อได้ข้อความจากร้าน → แท็บแชทของหน้าคำสั่งซื้อผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'order_message', role: 'buyer', order_id: '15' })).toBe('/order/15?tab=chat');
  });

  it('payload เก่าที่ไม่มี role (ส่งหาผู้ซื้อเท่านั้น) → หน้าผู้ซื้อเหมือนเดิม', () => {
    expect(routeForNotification({ type: 'order_message', order_id: 15 })).toBe('/order/15?tab=chat');
  });

  it('role อื่นไม่พาไปหน้าผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'order_message', role: 'admin', order_id: 15 })).toBe('/notifications');
  });
});

describe('routeForNotification — ปลอดภัย', () => {
  it('url นอก allowlist ไม่พาไป', () => {
    expect(routeForNotification({ type: 'unknown', url: 'https://evil.example' })).toBe('/notifications');
    expect(routeForNotification({ type: 'unknown', url: '//evil.example' })).toBe('/notifications');
  });
});

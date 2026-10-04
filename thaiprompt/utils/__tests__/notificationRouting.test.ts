/**
 * ทดสอบการแปลง data ของ push → หน้าในแอป (เฉพาะ payload ของร้านตลาดสด / ไรเดอร์ที่เพิ่มใน wave 2)
 *
 * รัน: npx jest utils/__tests__/notificationRouting.test.ts
 */

import { describe, expect, it } from '@jest/globals';
import { riderFlowPath, routeForNotification } from '../notificationRouting';

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

describe('routeForNotification — ไรเดอร์รอบ 2 (ส่งมอบของ / ล็อกเรียก)', () => {
  it('ร้านได้ delivery_update ของออเดอร์ร้านค้า → หน้าออเดอร์ของร้าน (ไม่ใช่หน้าผู้ซื้อที่ขึ้น "ไม่พบคำสั่งซื้อนี้")', () => {
    expect(
      routeForNotification({ type: 'delivery_update', role: 'seller', event: 'picked_up', source_type: 'Order', source_id: 15 })
    ).toBe('/merchant/order/15');
    expect(
      routeForNotification({ type: 'delivery_update', recipient: 'seller', event: 'accepted', source_type: 'FreshMarketOrder', source_id: 9 })
    ).toBe('/merchant/taladsod/orders?focus=9');
  });

  it('delivery_update ไม่มี role → หน้าผู้ซื้อเหมือนเดิม (หน้านั้นพาร้านต่อไปหน้าร้านเอง)', () => {
    expect(routeForNotification({ type: 'delivery_update', event: 'accepted', source_type: 'Order', source_id: 15 })).toBe('/order/15');
  });

  it('ไรเดอร์ถึงหน้าบ้าน / วางของไว้ให้ → หน้ารับของของผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'handover_arrived', source: 'shop', order_id: 15 })).toBe('/handover/shop/15');
    expect(routeForNotification({ type: 'handover_auto_release_scheduled', source: 'fresh-market', order_id: '9' })).toBe(
      '/handover/fresh-market/9'
    );
  });

  it('รับของสำเร็จ → ผู้ซื้อ: หน้าออเดอร์ · ไรเดอร์: หน้างาน · ร้าน: หน้าออเดอร์ของร้าน', () => {
    expect(routeForNotification({ type: 'handover_completed', role: 'buyer', source: 'shop', order_id: 15, job_id: 7 })).toBe('/order/15');
    expect(routeForNotification({ type: 'handover_completed', role: 'rider', source: 'shop', order_id: 15, job_id: 7 })).toBe(
      '/rider-job-detail?id=7'
    );
    expect(routeForNotification({ type: 'handover_completed', role: 'seller', source: 'fresh-market', order_id: 9 })).toBe(
      '/merchant/taladsod/orders?focus=9'
    );
    expect(routeForNotification({ type: 'handover_resolved', source: 'fresh-market', order_id: 9 })).toBe('/taladsod/order/9');
    expect(routeForNotification({ type: 'handover_resolved', job_id: 7 })).toBe('/rider-job-detail?id=7');
  });

  it('เรื่องร้องเรียนถึงแอดมิน → ไม่พาไปหน้าผู้ซื้อ', () => {
    expect(routeForNotification({ type: 'handover_disputed', source: 'shop', order_id: 15 })).toBe('/notifications');
    expect(routeForNotification({ type: 'handover_completed', role: 'admin', source: 'shop', order_id: 15 })).toBe('/notifications');
  });

  it('งานที่ผู้ซื้อล็อกเรียก → หน้างานนั้นทันที · งานปกติ → รายการงาน', () => {
    expect(routeForNotification({ type: 'rider_job_offer', job_id: 7, locked: true })).toBe('/rider-job-detail?id=7');
    expect(routeForNotification({ type: 'rider_job_offer', job_id: 7, locked: '1' })).toBe('/rider-job-detail?id=7');
    expect(routeForNotification({ type: 'rider_job_offer', job_id: 7 })).toBe('/rider-jobs');
  });

  it('ทุก push ปลายทางอยู่ใน allowlist จริง', () => {
    for (const type of ['delivery_update', 'handover_arrived', 'handover_completed']) {
      for (const role of ['buyer', 'rider', 'seller', '']) {
        const path = routeForNotification({ type, role, source: 'shop', order_id: 15, job_id: 7 });
        expect(path.startsWith('/')).toBe(true);
        expect(path.startsWith('//')).toBe(false);
      }
    }
  });

  it('หน้าใหม่อยู่ใน allowlist (data.url)', () => {
    expect(routeForNotification({ url: '/riders/nearby' })).toBe('/riders/nearby');
    expect(routeForNotification({ url: '/profile-photo' })).toBe('/profile-photo');
    expect(routeForNotification({ url: '/handover/shop/15' })).toBe('/handover/shop/15');
  });
});

describe('routeForNotification — ลำดับ role → screen → เดา (FIXES §A5 / L2)', () => {
  it('role มาก่อน screen: ไรเดอร์ได้ push ที่ screen=order → ยังไปหน้างานของไรเดอร์', () => {
    expect(routeForNotification({ type: 'handover_completed', role: 'rider', screen: 'order', source: 'shop', order_id: 15, job_id: 7 })).toBe(
      '/rider-job-detail?id=7'
    );
  });

  it('delivery_update ตาม role ของผู้รับแต่ละคน', () => {
    const base = { type: 'delivery_update', event: 'picked_up', source_type: 'Order', source_id: 15, job_id: 7 };
    expect(routeForNotification({ ...base, role: 'buyer' })).toBe('/order/15');
    expect(routeForNotification({ ...base, role: 'seller' })).toBe('/merchant/order/15');
    expect(routeForNotification({ ...base, role: 'rider' })).toBe('/rider-job-detail?id=7');
    const fm = { type: 'delivery_update', event: 'accepted', source: 'fresh-market', order_id: 9, job_id: 4 };
    expect(routeForNotification({ ...fm, role: 'buyer' })).toBe('/taladsod/order/9');
    expect(routeForNotification({ ...fm, role: 'seller' })).toBe('/merchant/taladsod/orders?focus=9');
  });

  it('ผู้ซื้อได้ handover_arrived → หน้ารับของ · ร้าน/ไรเดอร์ไม่ไปหน้ารับของของผู้ซื้อ', () => {
    const base = { type: 'handover_arrived', source: 'fresh-market', order_id: 9, job_id: 4 };
    expect(routeForNotification({ ...base, role: 'buyer' })).toBe('/handover/fresh-market/9');
    expect(routeForNotification({ ...base, role: 'seller' })).toBe('/merchant/taladsod/orders?focus=9');
    expect(routeForNotification({ ...base, role: 'rider' })).toBe('/rider-job-detail?id=4');
  });

  it('role=admin ไม่พาไปหน้าผู้ซื้อแม้ screen จะบอก order', () => {
    expect(routeForNotification({ type: 'delivery_update', role: 'admin', screen: 'order', source: 'shop', order_id: 15 })).toBe(
      '/notifications'
    );
  });

  it('ไม่มี role → ใช้ data.screen', () => {
    const base = { source: 'shop', order_id: 15, job_id: 7 };
    expect(routeForNotification({ ...base, type: 'handover_completed', screen: 'rider-job-detail' })).toBe('/rider-job-detail?id=7');
    expect(routeForNotification({ ...base, type: 'handover_completed', screen: 'merchant-order' })).toBe('/merchant/order/15');
    expect(routeForNotification({ ...base, type: 'handover_resolved', screen: 'order' })).toBe('/order/15');
    expect(routeForNotification({ ...base, type: 'handover_auto_release_scheduled', screen: 'order-handover' })).toBe('/handover/shop/15');
    expect(
      routeForNotification({ type: 'handover_completed', screen: 'merchant-order', source: 'fresh-market', order_id: 9 })
    ).toBe('/merchant/taladsod/orders?focus=9');
  });

  it('screen ที่ไม่รู้จัก → เดาแบบเดิม', () => {
    expect(routeForNotification({ type: 'handover_arrived', screen: 'something-new', source: 'shop', order_id: 15 })).toBe('/handover/shop/15');
  });

  it('order-handover แต่ไม่รู้แหล่งออเดอร์ → หน้าออเดอร์ (ไม่สร้าง path ผิด)', () => {
    expect(routeForNotification({ type: 'handover_arrived', screen: 'order-handover', order_id: 15 })).toBe('/order/15');
  });

  it('riderFlowPath คืน null ให้แอดมิน (ใช้ data.url ที่ปลอดภัยแทน)', () => {
    expect(riderFlowPath('handover_disputed', { source: 'shop', order_id: 15 }, 'admin')).toBeNull();
    expect(routeForNotification({ type: 'handover_disputed', role: 'admin', url: '/support' })).toBe('/support');
    expect(routeForNotification({ type: 'handover_disputed', role: 'admin', url: 'https://evil.example' })).toBe('/notifications');
  });
});

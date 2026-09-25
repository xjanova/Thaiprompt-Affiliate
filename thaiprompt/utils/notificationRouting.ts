/**
 * แปลง data ของ push notification → path ในแอปที่จะเปิด
 *
 * รองรับ payload จาก wave 1:
 *   - rider_job_offer {job_id}                → /rider-jobs
 *   - rider_job_update {job_id, event}        → /rider-job-detail?id= (event pickup_moved → &moved=1)
 *   - rider_account {event}                   → /rider
 *   - delivery_update {source_type, source_id} → ออเดอร์ร้านค้า / ตลาดสด
 *       (ตลาดสดส่งหาทั้งผู้ซื้อและร้านโดยไม่มี role → /taladsod/order/{id} ซึ่งพาร้านไปหน้าออเดอร์ร้านเองตาม viewer_role)
 *   - shop_order {role, order_id}             → ผู้ซื้อ /order/{id} · ร้าน /merchant/order/{id}
 *   - fresh_market_order {role, order_id}     → ผู้ขาย /merchant/taladsod/orders?focus= · ผู้ซื้อ /taladsod/order/{id}
 *       event seller_account (อนุมัติ/ระงับร้าน ไม่มี role) → /merchant/taladsod · role อื่น (admin) → ไม่พาไปหน้าผู้ซื้อ
 *   - fresh_market_shop_open {shop_id}        → หน้าร้าน /taladsod/shop/{id} (ร้านที่ติดตามเปิดแล้ว)
 *   - fresh_market_shop {event: auto_closed}  → /merchant/taladsod (ร้านถูกปิดอัตโนมัติ)
 *   - order_message {order_id}                → /order/{id}?tab=chat
 *   - ticket                                  → /support
 * นอกนั้นใช้ data.url เฉพาะ path ภายในที่อยู่ใน allowlist (PLAY-23) ไม่งั้นไปหน้าแจ้งเตือน
 */

import { isAllowedInternalRoute } from './linking';

type PushData = Record<string, unknown>;

const toId = (value: unknown): number | null => {
  const n = typeof value === 'number' ? value : Number(value);
  return Number.isInteger(n) && n > 0 ? n : null;
};

/**
 * @returns path ภายในแอป (ผ่าน allowlist แล้ว)
 */
export const routeForNotification = (data: PushData | null | undefined): string => {
  if (!data || typeof data !== 'object') return '/notifications';

  const type = typeof data.type === 'string' ? data.type : '';
  const role = typeof data.role === 'string' ? data.role : '';
  const orderId = toId(data.order_id ?? data.orderId);
  const jobId = toId(data.job_id ?? data.jobId);

  let path: string | null = null;

  switch (type) {
    case 'rider_job_offer':
      path = '/rider-jobs';
      break;
    case 'rider_job_update':
      // pickup_moved = ร้านเคลื่อนที่ย้ายจุดรับของ → หน้างานแสดงป้ายเตือนให้เห็นชัด
      path = jobId
        ? `/rider-job-detail?id=${jobId}${data.event === 'pickup_moved' ? '&moved=1' : ''}`
        : '/rider-job-detail';
      break;
    case 'rider_account':
    case 'rider':
    case 'job':
      path = '/rider';
      break;
    case 'delivery_update': {
      const sourceType = typeof data.source_type === 'string' ? data.source_type : '';
      const sourceId = toId(data.source_id);
      if (sourceType === 'Order' && sourceId) {
        path = `/order/${sourceId}`;
      } else if (sourceType === 'FreshMarketOrder') {
        path = sourceId ? `/taladsod/order/${sourceId}` : '/taladsod/orders';
      } else {
        path = '/orders';
      }
      break;
    }
    case 'shop_order':
      // ร้าน → หน้าจัดการออเดอร์ของร้านในแอป · ผู้ซื้อ → รายละเอียดคำสั่งซื้อ
      path =
        role === 'seller'
          ? orderId ? `/merchant/order/${orderId}` : '/merchant/orders'
          : orderId ? `/order/${orderId}` : '/orders';
      break;
    case 'fresh_market_order':
      if (data.event === 'seller_account') {
        // แอดมินอนุมัติ/ระงับร้าน → หน้าร้านตลาดสดของฉัน
        path = '/merchant/taladsod';
      } else if (role === 'seller') {
        // ร้าน → หน้าออเดอร์ตลาดสดของร้าน (ไฮไลต์ออเดอร์นั้น)
        path = orderId ? `/merchant/taladsod/orders?focus=${orderId}` : '/merchant/taladsod/orders';
      } else if (role === 'buyer') {
        path = orderId ? `/taladsod/order/${orderId}` : '/taladsod/orders';
      } else if (role === '' && orderId) {
        // payload เก่าที่ไม่มี role → หน้าออเดอร์พาร้านไปหน้าออเดอร์ร้านเองตาม viewer_role
        path = `/taladsod/order/${orderId}`;
      } else {
        // admin / ไม่รู้บทบาท → ไม่พาไปหน้าของผู้ซื้อ (ใช้ data.url ถ้าปลอดภัย ไม่งั้นหน้าแจ้งเตือน)
        path = null;
      }
      break;
    case 'fresh_market_shop_open': {
      // ร้านที่ผู้ซื้อติดตามเพิ่งเปิด → หน้าร้าน
      const shopId = toId(data.shop_id ?? data.seller_id);
      path = shopId ? `/taladsod/shop/${shopId}` : '/taladsod';
      break;
    }
    case 'fresh_market_shop':
      // แจ้งเจ้าของร้าน (เช่น ร้านปิดอัตโนมัติ) → หน้าร้านตลาดสดของฉัน
      path = '/merchant/taladsod';
      break;
    case 'order_message':
      path = orderId ? `/order/${orderId}?tab=chat` : '/orders';
      break;
    case 'order':
      path = orderId ? `/order/${orderId}` : '/orders';
      break;
    case 'ticket':
      path = '/support';
      break;
    default:
      path = null;
  }

  if (path && isAllowedInternalRoute(path)) return path;

  // payload ทั่วไปจาก NotificationService: data.url (action_url) — รับเฉพาะ path ภายในที่ปลอดภัย
  if (isAllowedInternalRoute(data.url)) return data.url as string;

  return '/notifications';
};

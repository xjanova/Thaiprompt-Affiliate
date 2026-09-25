/**
 * แปลง data ของ push notification → path ในแอปที่จะเปิด
 *
 * รองรับ payload จาก wave 1:
 *   - rider_job_offer {job_id}                → /rider-jobs
 *   - rider_job_update {job_id, event}        → /rider-job-detail?id=
 *   - rider_account {event}                   → /rider
 *   - delivery_update {source_type, source_id} → ออเดอร์ร้านค้า / ตลาดสด
 *   - shop_order {role, order_id}             → ผู้ซื้อ /order/{id} · ร้าน /merchant/order/{id}
 *   - fresh_market_order {role, order_id}     → /taladsod (ผู้ขาย/ผู้ซื้อ)
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
      path = jobId ? `/rider-job-detail?id=${jobId}` : '/rider-job-detail';
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
        path = '/taladsod';
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
      path = '/taladsod';
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

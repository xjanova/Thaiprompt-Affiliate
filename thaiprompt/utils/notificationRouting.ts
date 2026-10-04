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
 *   - order_message {role?, order_id}         → แชทฝั่งผู้ซื้อ /order/{id}?tab=chat (role อื่นที่ไม่ใช่ buyer → ไม่พาไปหน้าผู้ซื้อ)
 *   - seller_order_message {order_id}         → แชทฝั่งร้าน /merchant/order/{id}?tab=chat (OrderChatNotifier)
 *       type แยกจาก order_message เพราะแอปรุ่นเก่าพา order_message ไปหน้าผู้ซื้อเสมอ (ร้านเปิดแล้ว 404)
 *       — แอปรุ่นเก่าไม่รู้จัก type นี้ จึงตกไปหน้าแจ้งเตือนแทน
 *   - ticket                                  → /support
 *
 * ไรเดอร์รอบ 2 (2026-10-04) — ผู้รับบอกด้วย data.role (หรือ recipient / audience) = buyer | rider | seller
 *   - rider_job_offer {job_id, locked:true}   → /rider-job-detail?id= (ไรเดอร์ที่ผู้ซื้อล็อกเรียก ได้สิทธิ์รับก่อน)
 *   - delivery_update + role=seller           → หน้าออเดอร์ของร้าน (/merchant/order/{id} · ตลาดสด /merchant/taladsod/orders?focus=)
 *       แก้บั๊ก: เดิมพาร้านไปหน้าผู้ซื้อ → "ไม่พบคำสั่งซื้อนี้" · payload ไม่มี role → หน้าผู้ซื้อ ซึ่งพาร้านต่อไปหน้าร้านเอง
 *         (ร้านค้า: GET /orders/{id} 404 แล้วเปิดฝั่งร้านได้ · ตลาดสด: viewer_role = seller)
 *   - handover_arrived / handover_auto_release_scheduled {source, order_id} → หน้ารับของ /handover/{source}/{id}
 *   - handover_completed / handover_resolved  → ผู้ซื้อ: หน้าออเดอร์ · ไรเดอร์: หน้างาน · ร้าน: หน้าออเดอร์ของร้าน
 *   - handover_disputed (แอดมิน)              → ไม่มีหน้าในแอป (ใช้ data.url ถ้าปลอดภัย)
 * นอกนั้นใช้ data.url เฉพาะ path ภายในที่อยู่ใน allowlist (PLAY-23) ไม่งั้นไปหน้าแจ้งเตือน
 */

import { isAllowedInternalRoute } from './linking';

type PushData = Record<string, unknown>;

const toId = (value: unknown): number | null => {
  const n = typeof value === 'number' ? value : Number(value);
  return Number.isInteger(n) && n > 0 ? n : null;
};

type OrderSource = 'shop' | 'fresh-market';

/** แหล่งของออเดอร์จาก payload (source ใหม่ หรือ source_type ชื่อคลาสแบบเดิม) */
const sourceOf = (data: PushData): OrderSource | null => {
  const s = typeof data.source === 'string' ? data.source : '';
  if (s === 'shop' || s === 'fresh-market') return s;
  if (s === 'fresh_market') return 'fresh-market';
  const t = typeof data.source_type === 'string' ? data.source_type : '';
  if (t === 'Order') return 'shop';
  if (t === 'FreshMarketOrder') return 'fresh-market';
  return null;
};

/** หน้าออเดอร์ตามบทบาทของผู้รับ */
const orderPathFor = (source: OrderSource | null, orderId: number | null, recipient: string): string | null => {
  if (recipient === 'seller') {
    if (source === 'fresh-market') return orderId ? `/merchant/taladsod/orders?focus=${orderId}` : '/merchant/taladsod/orders';
    return orderId ? `/merchant/order/${orderId}` : '/merchant/orders';
  }
  if (source === 'fresh-market') return orderId ? `/taladsod/order/${orderId}` : '/taladsod/orders';
  if (source === 'shop') return orderId ? `/order/${orderId}` : '/orders';
  return orderId ? `/order/${orderId}` : '/orders';
};

/** เปิด/ปิดแบบ boolean ที่มากับ push (Expo ส่งเป็น string ได้) */
const truthy = (value: unknown): boolean => value === true || value === 1 || value === '1' || value === 'true';

/**
 * @returns path ภายในแอป (ผ่าน allowlist แล้ว)
 */
export const routeForNotification = (data: PushData | null | undefined): string => {
  if (!data || typeof data !== 'object') return '/notifications';

  const type = typeof data.type === 'string' ? data.type : '';
  const role = typeof data.role === 'string' ? data.role : '';
  const orderId = toId(data.order_id ?? data.orderId);
  const jobId = toId(data.job_id ?? data.jobId);
  /** ผู้รับ push นี้ (buyer | rider | seller | admin) — payload ใหม่อาจใช้ชื่อ key ต่างกัน */
  const recipient =
    role ||
    (typeof data.recipient === 'string' ? data.recipient : '') ||
    (typeof data.audience === 'string' ? data.audience : '');

  let path: string | null = null;

  switch (type) {
    case 'rider_job_offer':
      // ผู้ซื้อล็อกเรียกไรเดอร์คนนี้ → เปิดหน้างานนั้นเลย (มีสิทธิ์รับก่อนช่วงสั้นๆ)
      path = truthy(data.locked) && jobId ? `/rider-job-detail?id=${jobId}` : '/rider-jobs';
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
      if (recipient === 'seller') {
        // ร้านได้ push สถานะไรเดอร์ → หน้าออเดอร์ของร้าน (ไม่ใช่หน้าผู้ซื้อที่เปิดไม่ได้)
        path = orderPathFor(sourceOf(data), sourceId ?? orderId, 'seller');
      } else if (recipient === 'rider') {
        path = jobId ? `/rider-job-detail?id=${jobId}` : '/rider-jobs';
      } else if (sourceType === 'Order' && sourceId) {
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
      // แชทถึงผู้ซื้อ (payload เก่าไม่มี role = ผู้ซื้อ) · role=seller เผื่อ payload ช่วงสั้นๆ ก่อนแยก type
      if (role === 'seller') {
        path = orderId ? `/merchant/order/${orderId}?tab=chat` : '/merchant/orders?status=unread_chat';
      } else if (role === 'buyer' || role === '') {
        path = orderId ? `/order/${orderId}?tab=chat` : '/orders';
      } else {
        path = null;
      }
      break;
    case 'seller_order_message':
      // ลูกค้าทักร้าน → แท็บแชทของหน้าออเดอร์ร้าน (ไม่มีเลขออเดอร์ → แท็บข้อความใหม่)
      path = orderId ? `/merchant/order/${orderId}?tab=chat` : '/merchant/orders?status=unread_chat';
      break;
    case 'order':
      path = orderId ? `/order/${orderId}` : '/orders';
      break;
    case 'ticket':
      path = '/support';
      break;
    case 'handover_arrived':
    case 'handover_auto_release_scheduled': {
      // ไรเดอร์ถึงหน้าบ้าน / วางของไว้ให้แล้ว (มีเวลาแจ้งปัญหา 24 ชม.) → หน้ารับของของผู้ซื้อ
      const source = sourceOf(data);
      if (recipient === 'seller') {
        path = orderPathFor(source, orderId, 'seller');
      } else if (recipient === 'rider') {
        path = jobId ? `/rider-job-detail?id=${jobId}` : '/rider-jobs';
      } else if (source && orderId) {
        path = `/handover/${source}/${orderId}`;
      } else {
        path = orderId ? `/order/${orderId}` : '/orders';
      }
      break;
    }
    case 'handover_completed':
    case 'handover_resolved': {
      const source = sourceOf(data);
      if (recipient === 'rider' || (!recipient && jobId && !orderId)) {
        path = jobId ? `/rider-job-detail?id=${jobId}` : '/rider-jobs';
      } else if (recipient === 'seller') {
        path = orderPathFor(source, orderId, 'seller');
      } else if (recipient === 'buyer' || recipient === '') {
        path = orderPathFor(source, orderId, 'buyer');
      } else {
        // แอดมิน/บทบาทอื่น → ไม่พาไปหน้าของผู้ซื้อ
        path = null;
      }
      break;
    }
    case 'handover_disputed':
      // แจ้งแอดมิน — ไม่มีหน้าตัดสินในแอป (ใช้ data.url ถ้าเป็น path ที่อนุญาต)
      path = null;
      break;
    default:
      path = null;
  }

  if (path && isAllowedInternalRoute(path)) return path;

  // payload ทั่วไปจาก NotificationService: data.url (action_url) — รับเฉพาะ path ภายในที่ปลอดภัย
  if (isAllowedInternalRoute(data.url)) return data.url as string;

  return '/notifications';
};

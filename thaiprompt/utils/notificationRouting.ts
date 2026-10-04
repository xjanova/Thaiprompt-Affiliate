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
 *   - kyc_result {decision?}                  → /ekyc/result (ผลยืนยันตัวตนด้วย AI / เจ้าหน้าที่ตรวจเสร็จ — หน้าผลโหลดสถานะล่าสุดเอง)
 *
 * ไรเดอร์รอบ 2 (2026-10-04) — ผู้รับบอกด้วย data.role (หรือ recipient / audience) = buyer | rider | seller
 *   push ส่งมอบของ (handover_*) และสถานะไรเดอร์ (delivery_update) ตัดสินตามลำดับ (FIXES §A5 / L2):
 *     1) role ของผู้รับ  — rider → หน้างาน · seller → หน้าออเดอร์ของร้าน · buyer → หน้ารับของ/หน้าออเดอร์
 *        admin / บทบาทอื่น → ไม่พาไปหน้าของผู้ซื้อ (ใช้ data.url ถ้าปลอดภัย)
 *     2) data.screen — rider-job-detail · merchant-order · order · order-handover
 *     3) เดาจากข้อมูลแบบเดิม (payload รุ่นเก่าไม่มี role / screen)
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

/** หน้างานของไรเดอร์ */
const riderJobPath = (jobId: number | null): string => (jobId ? `/rider-job-detail?id=${jobId}` : '/rider-jobs');

/** หน้ารับของของผู้ซื้อ (ไม่มีข้อมูลพอ = หน้าออเดอร์) */
const handoverPath = (source: OrderSource | null, orderId: number | null): string =>
  source && orderId ? `/handover/${source}/${orderId}` : orderPathFor(source, orderId, 'buyer') || '/orders';

/** push ขั้นตอนไรเดอร์/ส่งมอบของ — ตัดสินปลายทางด้วย role → screen → เดา */
const RIDER_FLOW_TYPES = new Set([
  'delivery_update',
  'handover_arrived',
  'handover_auto_release_scheduled',
  'handover_completed',
  'handover_resolved',
  'handover_disputed',
]);

/** ชนิดที่ผู้ซื้อควรไปหน้ารับของ (ไม่ใช่หน้าออเดอร์) */
const BUYER_HANDOVER_TYPES = new Set(['handover_arrived', 'handover_auto_release_scheduled']);

/**
 * 1) ตาม role ของผู้รับ
 * @returns path · null = บทบาทนี้ไม่มีหน้าในแอป (เช่น admin) · undefined = ไม่มี role ให้ตัดสิน
 */
const pathForRole = (
  type: string,
  recipient: string,
  source: OrderSource | null,
  orderId: number | null,
  jobId: number | null
): string | null | undefined => {
  switch (recipient) {
    case '':
      return undefined;
    case 'rider':
      return riderJobPath(jobId);
    case 'seller':
      return orderPathFor(source, orderId, 'seller');
    case 'buyer':
      return BUYER_HANDOVER_TYPES.has(type) ? handoverPath(source, orderId) : orderPathFor(source, orderId, 'buyer');
    default:
      // admin / บทบาทอื่น → ห้ามพาไปหน้าของผู้ซื้อ
      return null;
  }
};

/**
 * 2) ตาม data.screen ที่ server บอก
 * @returns path · undefined = ไม่มี/ไม่รู้จัก screen นี้
 */
const pathForScreen = (
  screen: string,
  source: OrderSource | null,
  orderId: number | null,
  jobId: number | null
): string | null | undefined => {
  switch (screen) {
    case 'rider-job-detail':
      return riderJobPath(jobId);
    case 'merchant-order':
      return orderPathFor(source, orderId, 'seller');
    case 'order':
      return orderPathFor(source, orderId, 'buyer');
    case 'order-handover':
      return handoverPath(source, orderId);
    default:
      return undefined;
  }
};

/** 3) เดาแบบเดิม (payload รุ่นเก่าที่ไม่มี role / screen) */
const legacyRiderFlowPath = (
  type: string,
  data: PushData,
  source: OrderSource | null,
  orderId: number | null,
  jobId: number | null
): string | null => {
  switch (type) {
    case 'delivery_update': {
      const sourceType = typeof data.source_type === 'string' ? data.source_type : '';
      if (sourceType === 'Order' && orderId) return `/order/${orderId}`;
      if (sourceType === 'FreshMarketOrder') return orderId ? `/taladsod/order/${orderId}` : '/taladsod/orders';
      if (source && orderId) return orderPathFor(source, orderId, 'buyer');
      return '/orders';
    }
    case 'handover_arrived':
    case 'handover_auto_release_scheduled':
      // ไรเดอร์ถึงหน้าบ้าน / วางของไว้ให้แล้ว (มีเวลาแจ้งปัญหา 24 ชม.) → หน้ารับของของผู้ซื้อ
      return source && orderId ? `/handover/${source}/${orderId}` : orderId ? `/order/${orderId}` : '/orders';
    case 'handover_completed':
    case 'handover_resolved':
      // มีแต่เลขงาน = ส่งถึงไรเดอร์
      if (jobId && !orderId) return riderJobPath(jobId);
      return orderPathFor(source, orderId, 'buyer');
    case 'handover_disputed':
    default:
      // แจ้งแอดมิน — ไม่มีหน้าตัดสินในแอป (ใช้ data.url ถ้าเป็น path ที่อนุญาต)
      return null;
  }
};

/**
 * ปลายทางของ push ขั้นตอนไรเดอร์/ส่งมอบ: role → screen → เดา
 * (export ไว้ทดสอบ)
 */
export const riderFlowPath = (type: string, data: PushData, recipient: string): string | null => {
  const source = sourceOf(data);
  // delivery_update รุ่นเก่าใช้ source_id เป็นเลขออเดอร์
  const orderId = toId(data.order_id ?? data.orderId ?? data.source_id);
  const jobId = toId(data.job_id ?? data.jobId);

  const byRole = pathForRole(type, recipient, source, orderId, jobId);
  if (byRole !== undefined) return byRole;

  const screen = typeof data.screen === 'string' ? data.screen : '';
  const byScreen = pathForScreen(screen, source, orderId, jobId);
  if (byScreen !== undefined) return byScreen;

  return legacyRiderFlowPath(type, data, source, orderId, jobId);
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
  /** ผู้รับ push นี้ (buyer | rider | seller | admin) — payload ใหม่อาจใช้ชื่อ key ต่างกัน */
  const recipient =
    role ||
    (typeof data.recipient === 'string' ? data.recipient : '') ||
    (typeof data.audience === 'string' ? data.audience : '');

  let path: string | null = null;

  if (RIDER_FLOW_TYPES.has(type)) {
    path = riderFlowPath(type, data, recipient);
    if (path && isAllowedInternalRoute(path)) return path;
    if (isAllowedInternalRoute(data.url)) return data.url as string;
    return '/notifications';
  }

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
    case 'kyc_result':
      // ผลยืนยันตัวตน (อนุมัติ/ไม่ผ่าน/ให้ถ่ายใหม่) — หน้าผลดึง GET /ekyc/status เอง ไม่เชื่อค่าจาก payload
      path = '/ekyc/result';
      break;
    default:
      path = null;
  }

  if (path && isAllowedInternalRoute(path)) return path;

  // payload ทั่วไปจาก NotificationService: data.url (action_url) — รับเฉพาะ path ภายในที่ปลอดภัย
  if (isAllowedInternalRoute(data.url)) return data.url as string;

  return '/notifications';
};

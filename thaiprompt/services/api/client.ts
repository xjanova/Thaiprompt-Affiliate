/**
 * API client กลางของโมดูลใหม่ (rider / shop / merchant / account / banner)
 *
 * - ใช้ axios instance ตัวเดิมจาก services/api.ts (header Authorization + interceptor 401 + sync status เดิม)
 *   import เดิม `@/services/api` ยังใช้ได้ตามปกติ — ไฟล์นี้แค่ห่อเพิ่ม
 * - ทุกฟังก์ชันคืน ApiResult<T> ไม่ throw: { success:true, data } | { success:false, code, message }
 * - message เป็นภาษาไทยเสมอ: ใช้ข้อความจาก server ถ้าเป็นภาษาไทย ไม่งั้นแปลจาก code/HTTP status
 *   → ห้ามแสดง error.message ดิบของ axios/exception ให้ผู้ใช้เด็ดขาด
 */

import axios, { type AxiosError, type AxiosRequestConfig } from 'axios';
import { apiClient, clearAuthToken } from '@/services/api';
import { APP_CONFIG } from '@/constants';

export { apiClient };

// =====================================================
// ชนิดข้อมูลผลลัพธ์
// =====================================================

export interface ApiSuccess<T> {
  success: true;
  data: T;
  message: string;
  status: number;
  /** meta / pagination ที่ backend ส่งคู่กับ data (ถ้ามี) */
  meta?: Record<string, unknown>;
}

export interface ApiFailure {
  success: false;
  /** UPPER_SNAKE จาก backend หรือ NETWORK_ERROR / TIMEOUT / SERVER_ERROR ฯลฯ */
  code: string;
  /** ข้อความภาษาไทยพร้อมแสดง */
  message: string;
  /** HTTP status (0 = ไม่ถึง server) */
  status: number;
  /** data ที่แนบมากับ error (เช่น block_code, required, available) */
  data?: any;
  /** ข้อความ validation รายช่อง */
  errors?: Record<string, string[]>;
}

export type ApiResult<T> = ApiSuccess<T> | ApiFailure;

/** รูปแบบ pagination มาตรฐานของโมดูลใหม่ */
export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  has_more?: boolean;
}

// =====================================================
// ข้อความภาษาไทยตาม error code (ครอบทุก contract ของ wave 1)
// =====================================================

export const THAI_ERROR_MESSAGES: Record<string, string> = {
  // ทั่วไป
  NETWORK_ERROR: 'เชื่อมต่อไม่ได้ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่นะ',
  TIMEOUT: 'เซิร์ฟเวอร์ตอบช้า ลองใหม่อีกครั้งนะ',
  UNAUTHENTICATED: 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง',
  ACCOUNT_SUSPENDED: 'บัญชีของคุณถูกระงับการใช้งาน ติดต่อทีมงานได้ที่หน้าช่วยเหลือ',
  TOO_MANY_REQUESTS: 'ทำรายการถี่เกินไป รอสักครู่แล้วลองใหม่นะ',
  VALIDATION_ERROR: 'ข้อมูลยังไม่ถูกต้อง ตรวจสอบอีกครั้งนะ',
  FORBIDDEN: 'คุณไม่มีสิทธิ์ทำรายการนี้',
  NOT_FOUND: 'ไม่พบข้อมูลที่ต้องการ',
  CONFLICT: 'ข้อมูลเปลี่ยนไปแล้ว รีเฟรชแล้วลองใหม่นะ',
  SERVER_ERROR: 'ระบบขัดข้องชั่วคราว ลองใหม่อีกครั้งนะ',
  UNKNOWN_ERROR: 'เกิดข้อผิดพลาด ลองใหม่อีกครั้งนะ',

  // ไรเดอร์
  JOB_TAKEN: 'งานนี้มีไรเดอร์รับไปแล้ว',
  HAS_ACTIVE_JOB: 'คุณมีงานที่ยังส่งไม่เสร็จ ส่งให้เสร็จก่อนนะ',
  INVALID_TRANSITION: 'สถานะงานเปลี่ยนไปแล้ว รีเฟรชแล้วลองใหม่นะ',
  NOT_ELIGIBLE: 'ตอนนี้ยังรับงานไม่ได้',
  SELF_ORDER: 'รับงานส่งออเดอร์ของตัวเองไม่ได้',
  INSUFFICIENT_COD_CREDIT: 'ยอดในกระเป๋าไม่พอสำหรับงานเก็บเงินปลายทาง',
  NOT_YOUR_JOB: 'งานนี้ไม่ใช่งานของคุณ',
  JOB_NOT_FOUND: 'ไม่พบงานนี้ อาจถูกยกเลิกไปแล้ว',
  PHOTO_REQUIRED: 'ถ่ายรูปยืนยันก่อนนะ',
  COD_CONFIRM_REQUIRED: 'ยืนยันว่าเก็บเงินปลายทางแล้วก่อนนะ',
  INVALID_REASON: 'เลือกเหตุผลก่อนนะ',
  INVALID_LOCATION: 'ตำแหน่งไม่ถูกต้อง เปิด GPS แล้วลองใหม่นะ',
  OUT_OF_SERVICE_AREA: 'อยู่นอกพื้นที่ให้บริการ',
  COD_LIMIT_EXCEEDED: 'ยอดเก็บเงินปลายทางเกินที่กำหนด',
  INVALID_AVAILABILITY: 'เปลี่ยนสถานะไม่สำเร็จ ลองใหม่นะ',
  ALREADY_REGISTERED: 'คุณสมัครไรเดอร์ไว้แล้ว',
  GPS_REQUIRED: 'เปิดสิทธิ์ตำแหน่งก่อนออนไลน์นะ',
  NOT_APPROVED: 'บัญชีไรเดอร์ยังไม่ได้รับการอนุมัติ',
  PENDING_REVIEW: 'เอกสารกำลังรอตรวจสอบ',
  REJECTED: 'ใบสมัครยังไม่ผ่าน แก้ไขแล้วส่งใหม่ได้',
  SUSPENDED: 'บัญชีไรเดอร์ถูกระงับชั่วคราว',
  USER_BLOCKED: 'บัญชีถูกระงับการใช้งาน',
  DEPOSIT_REQUIRED: 'ต้องวางเงินประกันก่อนรับงาน',
  CONSENT_REQUIRED: 'ยอมรับการแชร์ตำแหน่งระหว่างส่งงานก่อนนะ',
  OFFLINE: 'กดออนไลน์ก่อนรับงานนะ',
  LOCATION_STALE: 'ตำแหน่งยังไม่อัปเดต เปิด GPS แล้วลองใหม่นะ',
  TOO_FAR: 'งานนี้อยู่ไกลเกินไป',
  DOCUMENTS_REVIEW_PENDING: 'เอกสารใหม่กำลังรอตรวจสอบ',

  // บัญชี / แพลตฟอร์ม
  CONFIRMATION_REQUIRED: 'พิมพ์คำว่า "ลบบัญชี" ให้ถูกต้องก่อนนะ',
  PASSWORD_REQUIRED: 'กรอกรหัสผ่านเพื่อยืนยันก่อนนะ',
  PASSWORD_INCORRECT: 'รหัสผ่านไม่ถูกต้อง',
  ACCOUNT_DELETION_FAILED: 'ลบบัญชีไม่สำเร็จ ลองใหม่ภายหลังนะ',
  ADMIN_ACCOUNT: 'บัญชีผู้ดูแลระบบลบจากแอปไม่ได้',
  WALLET_NOT_EMPTY: 'ยังมียอดเงินในกระเป๋า ถอนออกให้หมดก่อนนะ',
  WALLET_NEGATIVE: 'กระเป๋าเงินมียอดค้างชำระ ติดต่อทีมงานก่อนนะ',
  PENDING_WITHDRAWAL: 'มีคำขอถอนเงินที่ยังไม่เสร็จ',
  PENDING_EARNINGS: 'ยังมีรายได้ที่รอโอนเข้ากระเป๋า',
  OUTSTANDING_DEBT: 'ยังมียอดค้างชำระกับระบบ',
  ACTIVE_ORDERS: 'ยังมีคำสั่งซื้อที่ยังไม่จบ',
  ACTIVE_RIDER_JOBS: 'ยังมีงานไรเดอร์ที่ยังไม่จบ',
  ACTIVE_FRESH_MARKET_ORDERS: 'ยังมีออเดอร์ตลาดสดที่ยังไม่จบ',
  UNSETTLED_COD: 'ยังมียอดเก็บเงินปลายทางที่ยังไม่นำส่ง',
  INVALID_REDIRECT_PATH: 'เปิดหน้านี้บนเว็บไซต์ไม่ได้',

  // กระเป๋าเงิน / ถอนเงิน
  KYC_REQUIRED: 'ยืนยันตัวตน (KYC) ก่อนถอนเงินนะ',
  PIN_NOT_SET: 'ตั้งรหัส PIN กระเป๋าเงินก่อนนะ',
  WALLET_LOCKED: 'กระเป๋าเงินถูกล็อกชั่วคราว ลองใหม่ภายหลังนะ',
  PAYMENT_METHOD_REQUIRED: 'เพิ่มบัญชีรับเงินก่อนนะ',
  AMOUNT_OUT_OF_RANGE: 'จำนวนเงินไม่อยู่ในช่วงที่ถอนได้',
  INSUFFICIENT_BALANCE: 'ยอดเงินในกระเป๋าไม่พอ',
  INVALID_PIN: 'รหัส PIN ไม่ถูกต้อง',
  REQUEST_IN_PROGRESS: 'กำลังทำรายการก่อนหน้า รอสักครู่นะ',
  WITHDRAW_FAILED: 'ถอนเงินไม่สำเร็จ ลองใหม่ภายหลังนะ',
  NOT_CANCELLABLE: 'รายการนี้ยกเลิกไม่ได้แล้ว',
  LIMIT_REACHED: 'เพิ่มบัญชีได้สูงสุด 5 บัญชี',
  DUPLICATE_ACCOUNT: 'มีบัญชีนี้อยู่แล้ว',
  IN_USE: 'บัญชีนี้มีคำขอถอนเงินค้างอยู่',
  PIN_TOO_WEAK: 'PIN ง่ายเกินไป ลองตัวเลขที่เดายากกว่านี้นะ',
  CURRENT_PIN_REQUIRED: 'กรอก PIN เดิมก่อนนะ',
  COD_RESERVED: 'ยอดส่วนนี้กันไว้สำหรับเงินปลายทางที่ยังไม่นำส่ง',

  // ช้อป / ตะกร้า / ชำระเงิน
  PRODUCT_NOT_FOUND: 'ไม่พบสินค้านี้',
  CART_ITEM_NOT_FOUND: 'ไม่พบสินค้านี้ในตะกร้า',
  PRODUCT_UNAVAILABLE: 'สินค้านี้ปิดการขายแล้ว',
  OUT_OF_STOCK: 'สินค้าเหลือไม่พอ',
  AFFILIATE_PRODUCT: 'สินค้านี้สั่งซื้อที่ร้านต้นทาง',
  CART_EMPTY: 'ตะกร้ายังว่างอยู่',
  CHECKOUT_IN_PROGRESS: 'กำลังสร้างคำสั่งซื้อ รอสักครู่นะ',
  ADDRESS_REQUIRED: 'เพิ่มที่อยู่จัดส่งก่อนนะ',
  ADDRESS_LOCATION_REQUIRED: 'ปักหมุดที่อยู่ก่อน ไรเดอร์จะได้ไปส่งถูกที่',
  ADDRESS_NOT_FOUND: 'ไม่พบที่อยู่นี้',
  ADDRESS_LIMIT: 'บันทึกที่อยู่ได้สูงสุด 20 รายการ',
  RIDER_NOT_AVAILABLE: 'ร้านนี้ยังส่งด้วยไรเดอร์ไม่ได้',
  COD_NOT_AVAILABLE: 'ออเดอร์นี้ยังเก็บเงินปลายทางไม่ได้',
  PAYMENT_METHOD_UNAVAILABLE: 'วิธีชำระเงินนี้ใช้ไม่ได้ตอนนี้',
  COUPON_INVALID: 'คูปองนี้ใช้ไม่ได้',
  WALLET_INACTIVE: 'กระเป๋าเงินยังไม่พร้อมใช้งาน',
  CHECKOUT_FAILED: 'สั่งซื้อไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  ORDER_NOT_FOUND: 'ไม่พบคำสั่งซื้อนี้',
  ALREADY_PAID: 'คำสั่งซื้อนี้ชำระเงินแล้ว',
  COD_ORDER: 'คำสั่งซื้อนี้ชำระเงินปลายทาง',
  ORDER_NOT_PAYABLE: 'คำสั่งซื้อนี้ชำระเงินไม่ได้แล้ว',
  PAYMENT_FAILED: 'ชำระเงินไม่สำเร็จ ลองใหม่นะ',
  ACTION_NOT_ALLOWED: 'ทำรายการนี้ไม่ได้ในสถานะปัจจุบัน',
  REFUND_FAILED: 'คืนเงินไม่สำเร็จ ติดต่อทีมงานนะ',
  ALREADY_REVIEWED: 'คุณรีวิวสินค้านี้ไปแล้ว',
  PAYMENT_NOT_COLLECTED: 'ยังไม่ได้รับเงินปลายทางจากไรเดอร์',

  // ร้านค้า
  NOT_A_SELLER: 'บัญชีนี้ยังไม่ได้เปิดร้าน',
  STORE_SUSPENDED: 'ร้านของคุณถูกระงับชั่วคราว',
  STORE_NOT_FOUND: 'ไม่พบร้านค้านี้',

  // ตลาดสด
  SELF_PURCHASE: 'ซื้อสินค้าจากร้านของตัวเองไม่ได้',
  LISTING_UNAVAILABLE: 'สินค้านี้ปิดการขายแล้ว',
  OUT_OF_DELIVERY_AREA: 'อยู่นอกระยะจัดส่งของร้านนี้',
  RIDER_DISABLED: 'ตอนนี้ยังไม่เปิดส่งด้วยไรเดอร์',
  PICKUP_LOCATION_MISSING: 'ร้านยังไม่ได้ปักหมุดจุดรับสินค้า',
  DELIVERY_LOCATION_REQUIRED: 'ปักหมุดตำแหน่งจัดส่งก่อนนะ',
  QUOTE_FAILED: 'คำนวณค่าส่งไม่สำเร็จ ลองใหม่นะ',
  SELLER_EXISTS: 'คุณเปิดร้านไว้แล้ว',
  ALREADY_RATED: 'คุณให้คะแนนออเดอร์นี้แล้ว',
  NOT_RATEABLE: 'ยังให้คะแนนออเดอร์นี้ไม่ได้',
};

const STATUS_CODE: Record<number, string> = {
  401: 'UNAUTHENTICATED',
  403: 'FORBIDDEN',
  404: 'NOT_FOUND',
  409: 'CONFLICT',
  419: 'UNAUTHENTICATED',
  422: 'VALIDATION_ERROR',
  429: 'TOO_MANY_REQUESTS',
};

const THAI_CHARS = /[฀-๿]/;

/** ข้อความนี้เป็นภาษาไทย (แสดงให้ผู้ใช้ได้) หรือไม่ */
export const isThaiText = (text: unknown): text is string =>
  typeof text === 'string' && text.trim().length > 0 && text.length <= 300 && THAI_CHARS.test(text);

/** ข้อความภาษาไทยของ code (ไม่รู้จัก = ข้อความกลาง) */
export const thaiMessageForCode = (code: string | undefined | null, fallback?: string): string =>
  (code && THAI_ERROR_MESSAGES[code]) || fallback || THAI_ERROR_MESSAGES.UNKNOWN_ERROR;

/** ข้อความ validation ข้อแรกที่เป็นภาษาไทย */
const firstThaiValidationMessage = (errors: unknown): string | null => {
  if (!errors || typeof errors !== 'object') return null;
  for (const value of Object.values(errors as Record<string, unknown>)) {
    const list = Array.isArray(value) ? value : [value];
    const hit = list.find((item) => isThaiText(item));
    if (hit) return hit as string;
  }
  return null;
};

// =====================================================
// แปลง error ทุกชนิด → ApiFailure
// =====================================================

/**
 * แปลง error จาก axios (หรืออะไรก็ตาม) เป็น ApiFailure ภาษาไทย
 * ใช้กับโค้ดเก่าที่ยัง try/catch เองได้ด้วย: `Alert.alert('แจ้งเตือน', toApiFailure(e).message)`
 */
export const toApiFailure = (error: unknown, fallbackMessage?: string): ApiFailure => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<any>;

    if (!axiosError.response) {
      const timedOut = axiosError.code === 'ECONNABORTED' || axiosError.code === 'ETIMEDOUT';
      const code = timedOut ? 'TIMEOUT' : 'NETWORK_ERROR';
      return { success: false, code, status: 0, message: THAI_ERROR_MESSAGES[code] };
    }

    const status = axiosError.response.status;
    const body = axiosError.response.data && typeof axiosError.response.data === 'object'
      ? axiosError.response.data
      : {};
    const code: string =
      typeof body.code === 'string' && body.code
        ? body.code
        : STATUS_CODE[status] || (status >= 500 ? 'SERVER_ERROR' : 'UNKNOWN_ERROR');

    let message: string;
    if (status >= 500 && !THAI_ERROR_MESSAGES[code]) {
      // 5xx ไม่เชื่อข้อความจาก server (อาจเป็น exception ดิบ)
      message = THAI_ERROR_MESSAGES.SERVER_ERROR;
    } else if (status === 422 && firstThaiValidationMessage(body.errors) && !isThaiText(body.message)) {
      message = firstThaiValidationMessage(body.errors) as string;
    } else if (isThaiText(body.message) && status < 500) {
      message = body.message;
    } else {
      message = thaiMessageForCode(code, fallbackMessage);
    }

    return {
      success: false,
      code,
      status,
      message,
      data: body.data,
      errors: body.errors && typeof body.errors === 'object' ? body.errors : undefined,
    };
  }

  return {
    success: false,
    code: 'UNKNOWN_ERROR',
    status: 0,
    message: fallbackMessage || THAI_ERROR_MESSAGES.UNKNOWN_ERROR,
  };
};

/** ข้อความไทยพร้อมแสดงจาก error ใดๆ */
export const toThaiErrorMessage = (error: unknown, fallbackMessage?: string): string =>
  toApiFailure(error, fallbackMessage).message;

// =====================================================
// บัญชีถูกระงับ → ล้าง token + แจ้ง authStore (ลงทะเบียนครั้งเดียว)
// =====================================================

type SuspendedHandler = (message: string) => void;
let suspendedHandler: SuspendedHandler | null = null;
let suspendedInterceptorInstalled = false;

/** authStore ลงทะเบียนตัวจัดการตอนบัญชีถูกระงับ (403 ACCOUNT_SUSPENDED) */
export const setAccountSuspendedHandler = (handler: SuspendedHandler | null): void => {
  suspendedHandler = handler;
};

if (!suspendedInterceptorInstalled) {
  suspendedInterceptorInstalled = true;
  apiClient.interceptors.response.use(
    (response) => response,
    async (error) => {
      try {
        const status = error?.response?.status;
        const code = error?.response?.data?.code;
        if (status === 403 && code === 'ACCOUNT_SUSPENDED') {
          await clearAuthToken();
          const message = isThaiText(error?.response?.data?.message)
            ? error.response.data.message
            : THAI_ERROR_MESSAGES.ACCOUNT_SUSPENDED;
          suspendedHandler?.(message);
        }
      } catch {
        // ห้ามให้ตัวจัดการนี้ทำ request ล้มซ้ำ
      }
      return Promise.reject(error);
    }
  );
}

// =====================================================
// ตัวเรียก API
// =====================================================

export interface RequestOptions extends AxiosRequestConfig {
  /** ข้อความไทยเมื่อ server ไม่ส่งข้อความไทยมา */
  fallbackMessage?: string;
}

/**
 * เรียก API แล้วคืน ApiResult (ไม่ throw)
 * - HTTP 2xx + body.success !== false → success
 * - data = body.data (ถ้าไม่มี key data จะคืนทั้ง body)
 */
export async function request<T>(options: RequestOptions): Promise<ApiResult<T>> {
  const { fallbackMessage, ...config } = options;
  try {
    const response = await apiClient.request(config);
    const body = response.data;

    if (body && typeof body === 'object' && body.success === false) {
      const code = typeof body.code === 'string' && body.code ? body.code : 'UNKNOWN_ERROR';
      return {
        success: false,
        code,
        status: response.status,
        message: isThaiText(body.message) ? body.message : thaiMessageForCode(code, fallbackMessage),
        data: body.data,
        errors: body.errors,
      };
    }

    const hasDataKey = body && typeof body === 'object' && 'data' in body;
    // key อื่นนอกจาก data (pagination / meta / counts / refunded / related ...) เก็บไว้ใน meta
    const meta: Record<string, unknown> = {};
    if (hasDataKey) {
      for (const key of Object.keys(body)) {
        if (!['success', 'message', 'data', 'code', 'errors'].includes(key)) {
          meta[key] = body[key];
        }
      }
    }

    return {
      success: true,
      data: (hasDataKey ? body.data : body) as T,
      message: isThaiText(body?.message) ? body.message : '',
      status: response.status,
      meta: Object.keys(meta).length > 0 ? meta : undefined,
    };
  } catch (error) {
    return toApiFailure(error, fallbackMessage);
  }
}

export const apiGet = <T>(url: string, params?: Record<string, unknown>, options: RequestOptions = {}) =>
  request<T>({ ...options, method: 'GET', url, params });

export const apiPost = <T>(url: string, data?: unknown, options: RequestOptions = {}) =>
  request<T>({ ...options, method: 'POST', url, data });

export const apiPut = <T>(url: string, data?: unknown, options: RequestOptions = {}) =>
  request<T>({ ...options, method: 'PUT', url, data });

export const apiDelete = <T>(url: string, data?: unknown, options: RequestOptions = {}) =>
  request<T>({ ...options, method: 'DELETE', url, data });

/** ส่ง multipart/form-data (อัปโหลดรูป) — timeout ยาวกว่าปกติ */
export const apiUpload = <T>(url: string, form: FormData, options: RequestOptions = {}) =>
  request<T>({
    timeout: APP_CONFIG.FILE_UPLOAD_TIMEOUT,
    ...options,
    method: options.method || 'POST',
    url,
    data: form,
    headers: { ...(options.headers || {}), 'Content-Type': 'multipart/form-data' },
  });

// =====================================================
// ตัวช่วย
// =====================================================

/** ไฟล์สำหรับ FormData ของ React Native จาก uri (เดาชนิดจากนามสกุล) */
export const fileFromUri = (
  uri: string,
  baseName: string = 'photo'
): { uri: string; name: string; type: string } => {
  const match = /\.([a-z0-9]+)(?:\?|$)/i.exec(uri);
  const ext = (match?.[1] || 'jpg').toLowerCase();
  const type =
    ext === 'png' ? 'image/png'
      : ext === 'webp' ? 'image/webp'
        : ext === 'heic' || ext === 'heif' ? 'image/heic'
          : 'image/jpeg';
  return { uri, name: `${baseName}.${ext === 'jpeg' ? 'jpg' : ext}`, type };
};

/** Idempotency-Key ต่อการกดหนึ่งครั้ง (UUID v4) */
export const newIdempotencyKey = (): string => {
  const cryptoObj = (globalThis as any).crypto;
  if (cryptoObj && typeof cryptoObj.randomUUID === 'function') {
    return cryptoObj.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
};

/** แปลงค่าตัวเลขจาก API ให้เป็น number เสมอ (null/ไม่ใช่ตัวเลข → fallback) */
export const num = (value: unknown, fallback: number = 0): number => {
  const n = typeof value === 'number' ? value : Number(value);
  return Number.isFinite(n) ? n : fallback;
};

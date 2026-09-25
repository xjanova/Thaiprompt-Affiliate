/**
 * Account API — ลบบัญชี · เปิดเว็บแบบล็อกอินให้ (web-session) · ถอด push token · ถอนเงิน + บัญชีรับเงิน + PIN
 * (contract F-platform + E2-money)
 */

import { apiDelete, apiGet, apiPost, newIdempotencyKey, type ApiResult, type Pagination } from './client';

// =====================================================
// ลบบัญชี (Google Play บังคับ)
// =====================================================

export type DeletionBlockerCode =
  | 'ADMIN_ACCOUNT'
  | 'WALLET_NOT_EMPTY'
  | 'WALLET_NEGATIVE'
  | 'PENDING_WITHDRAWAL'
  | 'PENDING_EARNINGS'
  | 'OUTSTANDING_DEBT'
  | 'ACTIVE_ORDERS'
  | 'ACTIVE_RIDER_JOBS'
  | 'ACTIVE_FRESH_MARKET_ORDERS'
  | 'UNSETTLED_COD';

export interface DeletionCheck {
  can_delete: boolean;
  blockers: Array<{ code: DeletionBlockerCode | string; message: string }>;
  /** true = บัญชีอีเมล ต้องกรอกรหัสผ่าน · LINE/Facebook ไม่ต้อง */
  requires_password: boolean;
  /** คำที่ต้องพิมพ์ยืนยัน ('ลบบัญชี') */
  confirm_text: string;
  /** หน้าอธิบายบนเว็บ (สำหรับ Play Console) */
  info_url: string;
}

/** GET /account/deletion-check */
export const getAccountDeletionCheck = (): Promise<ApiResult<DeletionCheck>> =>
  apiGet<DeletionCheck>('/account/deletion-check');

/**
 * DELETE /account — สำเร็จ = token ถูกเพิกถอนแล้ว ให้ล้าง auth ในเครื่องแล้วไปหน้า login
 * error: 422 CONFIRMATION_REQUIRED | PASSWORD_REQUIRED | PASSWORD_INCORRECT · 409 = blocker ตัวแรก (data.blockers)
 */
export const deleteAccount = (body: { confirm_text: string; password?: string }): Promise<ApiResult<{ reference: string }>> =>
  apiDelete<{ reference: string }>('/account', body);

// =====================================================
// เปิดเว็บไซต์แบบล็อกอินให้ (ใช้ผ่าน WebsiteButton / openWebsite)
// =====================================================

export interface WebSession {
  /** url มีแค่ ?token= ใช้ได้ครั้งเดียว อายุ 5 นาที — ห้ามเก็บ/ใช้ซ้ำ */
  url: string;
  expires_in: number;
  redirect_path: string;
}

/**
 * POST /web-session
 * redirect_path ต้องขึ้นต้นด้วย /user /seller /taladsod /shop /storefront /wallet /account
 * (นอกนั้น 422 INVALID_REDIRECT_PATH)
 */
export const createWebSession = (
  redirectPath: string,
  queryParams?: Record<string, string | number | boolean>
): Promise<ApiResult<WebSession>> =>
  apiPost<WebSession>('/web-session', {
    redirect_path: redirectPath,
    ...(queryParams && Object.keys(queryParams).length > 0 ? { query_params: queryParams } : {}),
  });

// =====================================================
// ชวนเพื่อน (ชั้นเดียว — รายละเอียดค่าแนะนำอยู่บนเว็บ)
// =====================================================

export interface ReferralInfo {
  referralCode: string | null;
  referralLink: string | null;
}

/** GET /profile/referral-code */
export const getReferralInfo = (): Promise<ApiResult<ReferralInfo>> => apiGet<ReferralInfo>('/profile/referral-code');

// =====================================================
// Push token
// =====================================================

/** POST /push/token/remove — ถอด token ของเครื่องนี้ (ส่งอย่างน้อยหนึ่งอย่าง) */
export const removePushTokenFromServer = (body: { token?: string | null; device_id?: string | null }): Promise<ApiResult<{ removed: number }>> =>
  apiPost<{ removed: number }>('/push/token/remove', {
    ...(body.token ? { token: body.token } : {}),
    ...(body.device_id ? { device_id: body.device_id } : {}),
  });

// =====================================================
// ถอนเงิน + บัญชีรับเงิน + PIN (E2-money)
// ลำดับหน้าจอ: info → (has_pin=false: ตั้ง PIN) → (ไม่มีบัญชี: เพิ่มบัญชี) → preview → withdraw
// ห้ามฮาร์ดโค้ดค่าธรรมเนียม/ขั้นต่ำในแอป ใช้ค่าจาก info/preview เท่านั้น
// =====================================================

export interface PayoutAccount {
  id: number;
  type: 'bank_transfer' | 'promptpay' | string;
  name: string | null;
  bank_name: string | null;
  bank_code: string | null;
  account_name: string;
  account_number_masked: string;
  account_last4: string;
  is_default: boolean;
  is_verified: boolean;
  created_at: string;
}

export interface WithdrawInfo {
  balance: number;
  currency: string;
  has_pin: boolean;
  wallet_locked: boolean;
  kyc_verified: boolean;
  limits: { min_amount: number; max_amount: number };
  fee: { type: 'percentage' | 'fixed'; amount: number; min: number | null; max: number | null };
  pending_withdrawal_amount: number;
  payment_methods: PayoutAccount[];
}

export interface WithdrawPreview {
  amount: number;
  fee: number;
  tax: number;
  net_amount: number;
  /** ข้อความไทยว่าทำไมถอนไม่ได้ (ว่าง = ถอนได้) */
  errors: string[];
}

export type WithdrawalStatus = 'pending' | 'processing' | 'approved' | 'rejected' | 'cancelled' | 'completed';

export interface Withdrawal {
  id: number;
  request_id: string;
  amount: number;
  fee: number;
  tax: number;
  net_amount: number;
  currency: string;
  status: WithdrawalStatus;
  status_label: string;
  can_cancel: boolean;
  payment_method: { type: string; bank_name: string | null; account_name: string; account_last4: string } | null;
  note: string | null;
  rejection_reason: string | null;
  created_at: string;
  approved_at: string | null;
  rejected_at: string | null;
  transfer_completed_at: string | null;
}

export interface WithdrawBody {
  amount: number;
  /** 6 หลัก */
  pin: string;
  /** แนะนำให้ส่ง */
  payment_method_id?: number;
  /** ใช้เมื่อไม่ส่ง id — ระบบเลือกบัญชีหลักของประเภทนั้น */
  payment_method?: 'bank' | 'bank_transfer' | 'promptpay';
  note?: string;
}

export interface AddPayoutAccountBody {
  type: 'bank_transfer' | 'promptpay';
  account_name: string;
  /** ตัวเลขหรือขีด 9-30 ตัว */
  account_number: string;
  /** บังคับเมื่อ bank_transfer */
  bank_name?: string;
  bank_code?: string;
  name?: string;
  is_default?: boolean;
  pin: string;
}

/** GET /wallet/withdraw/info */
export const getWithdrawInfo = (): Promise<ApiResult<WithdrawInfo>> => apiGet<WithdrawInfo>('/wallet/withdraw/info');

/** GET /wallet/withdraw/preview?amount= (throttle 30/นาที — debounce ตอนพิมพ์) */
export const previewWithdraw = (amount: number): Promise<ApiResult<WithdrawPreview>> =>
  apiGet<WithdrawPreview>('/wallet/withdraw/preview', { amount });

/**
 * POST /wallet/withdraw → 201 Withdrawal
 * error: KYC_REQUIRED · PIN_NOT_SET (ไปหน้าตั้ง PIN) · WALLET_LOCKED (data.locked_until) · PAYMENT_METHOD_REQUIRED
 *        AMOUNT_OUT_OF_RANGE · INSUFFICIENT_BALANCE · INVALID_PIN (data.attempts_remaining) · REQUEST_IN_PROGRESS
 *
 * @param idempotencyKey ใช้คีย์เดิมเมื่อกดซ้ำด้วยยอด/บัญชีเดิม (เช่น หลังเน็ตหลุด) — server ที่รองรับจะไม่สร้างคำขอซ้ำ
 */
export const withdraw = (body: WithdrawBody, idempotencyKey: string = newIdempotencyKey()): Promise<ApiResult<Withdrawal>> =>
  apiPost<Withdrawal>('/wallet/withdraw', body, { headers: { 'Idempotency-Key': idempotencyKey } });

/** GET /wallet/withdrawals?page= */
export const getWithdrawals = (page: number = 1): Promise<ApiResult<{ items: Withdrawal[]; pagination: Pagination }>> =>
  apiGet<{ items: Withdrawal[]; pagination: Pagination }>('/wallet/withdrawals', { page });

/** POST /wallet/withdrawals/{id}/cancel — NOT_FOUND / NOT_CANCELLABLE */
export const cancelWithdrawal = (id: number): Promise<ApiResult<Withdrawal>> =>
  apiPost<Withdrawal>(`/wallet/withdrawals/${id}/cancel`, {});

/** GET /wallet/bank-accounts */
export const getPayoutAccounts = (): Promise<ApiResult<{ items: PayoutAccount[]; max_accounts: number }>> =>
  apiGet<{ items: PayoutAccount[]; max_accounts: number }>('/wallet/bank-accounts');

/** POST /wallet/bank-accounts — PIN_NOT_SET, INVALID_PIN, LIMIT_REACHED, DUPLICATE_ACCOUNT */
export const addPayoutAccount = (body: AddPayoutAccountBody): Promise<ApiResult<PayoutAccount>> =>
  apiPost<PayoutAccount>('/wallet/bank-accounts', body);

/** POST /wallet/bank-accounts/{id}/delete {pin} — IN_USE เมื่อมีคำขอถอนค้าง */
export const deletePayoutAccount = (id: number, pin: string): Promise<ApiResult<{ id: number }>> =>
  apiPost<{ id: number }>(`/wallet/bank-accounts/${id}/delete`, { pin });

/** POST /wallet/bank-accounts/{id}/default */
export const setDefaultPayoutAccount = (id: number): Promise<ApiResult<PayoutAccount>> =>
  apiPost<PayoutAccount>(`/wallet/bank-accounts/${id}/default`, {});

/** POST /wallet/pin — PIN_TOO_WEAK, CURRENT_PIN_REQUIRED, INVALID_PIN */
export const setWalletPin = (body: { pin: string; pin_confirmation: string; current_pin?: string }): Promise<ApiResult<{ has_pin: true }>> =>
  apiPost<{ has_pin: true }>('/wallet/pin', body);

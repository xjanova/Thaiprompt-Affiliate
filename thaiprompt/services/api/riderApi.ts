/**
 * Rider API — /api/v1/rider/* (RiderApiController, contract B-rider-api + A-rider-core)
 *
 * - key ทุกตัวเป็น snake_case, ตัวเลขเป็น JSON number (60.0 มาเป็น 60 → ใช้ Number() ได้เลย)
 * - error: ApiFailure.code = JOB_TAKEN | HAS_ACTIVE_JOB | NOT_ELIGIBLE (data.block_code) | ...
 *   ข้อความไทยอยู่ใน result.message แล้ว — แสดงได้ทันที
 * - ค่าส่ง/รายได้: ใช้ตัวเลขจาก server (total_fee, rider_earnings, platform_fee, cod_amount) ห้ามคำนวณเองในแอป
 */

import {
  apiGet,
  apiPost,
  apiPut,
  apiUpload,
  fileFromUri,
  type ApiResult,
  type Pagination,
} from './client';

// =====================================================
// Types
// =====================================================

export type RiderAccountStatus = 'pending' | 'approved' | 'rejected' | 'suspended' | 'inactive';
export type RiderAvailability = 'online' | 'offline' | 'busy';
export type RiderVehicleType = 'motorcycle' | 'car' | 'bicycle' | 'walk';
export type RiderDocumentType = 'id_card' | 'driver_license' | 'vehicle_registration' | 'profile';

export type RiderJobStatus =
  | 'pending'
  | 'accepted'
  | 'picking_up'
  | 'picked_up'
  | 'delivering'
  | 'delivered'
  | 'completed'
  | 'cancelled'
  | 'failed';

/** ปุ่มที่แอปแสดงได้ (ใช้ job.allowed_actions ตัดสินใจเสมอ) */
export type RiderJobAction = 'accept' | 'release' | 'picking_up' | 'picked_up' | 'delivering' | 'deliver' | 'fail';

export type RiderBlockCode =
  | 'SUSPENDED'
  | 'PENDING_REVIEW'
  | 'REJECTED'
  | 'NOT_APPROVED'
  | 'USER_BLOCKED'
  | 'DEPOSIT_REQUIRED'
  | 'CONSENT_REQUIRED'
  | 'HAS_ACTIVE_JOB'
  | 'OFFLINE'
  | 'LOCATION_STALE'
  | 'TOO_FAR'
  | 'DOCUMENTS_REVIEW_PENDING';

export interface RiderBlockReason {
  code: RiderBlockCode | string;
  message: string;
}

export interface RiderStatus {
  id: number;
  status: RiderAccountStatus;
  status_text: string;
  availability: RiderAvailability;
  availability_text: string;
  rejection_reason: string | null;
  suspension_reason: string | null;
  can_reapply: boolean;
  full_name: string;
  phone: string;
  id_card_number_masked: string | null;
  birth_date: string | null;
  address: string | null;
  province: string | null;
  district: string | null;
  vehicle_type: RiderVehicleType;
  vehicle_type_text: string;
  vehicle_plate: string | null;
  vehicle_brand: string | null;
  vehicle_color: string | null;
  rider_type: string | null;
  rating: number;
  rating_count: number;
  total_jobs: number;
  completed_jobs: number;
  cancelled_jobs: number;
  completion_rate: number;
  total_earnings: number;
  wallet_balance: number;
  cod_credit_available: number;
  documents: Record<RiderDocumentType, boolean>;
  documents_required: RiderDocumentType[];
  documents_missing: RiderDocumentType[];
  documents_complete: boolean;
  documents_pending_review: boolean;
  document_urls: Partial<Record<RiderDocumentType, string | null>>;
  permissions: {
    gps: boolean;
    camera: boolean;
    microphone?: boolean;
    notification: boolean;
    location_consent: boolean;
  };
  location_consent_at: string | null;
  last_location: { latitude: number; longitude: number; updated_at: string; is_fresh: boolean } | null;
  can_go_online: boolean;
  online_block_reason: RiderBlockReason | null;
  can_accept_jobs: boolean;
  block_reason: RiderBlockReason | null;
  active_job_id: number | null;
  deposit: { required: boolean; status: string | null; amount: number };
  kyc: { status: string | null; verified: boolean; required_for_withdrawal: boolean };
  approved_at: string | null;
  created_at: string;
}

export interface RiderStatusResponse {
  is_rider: boolean;
  rider: RiderStatus | null;
}

export interface RiderRegisterBody {
  full_name: string;
  /** 0 + 8-9 หลัก (มีขีดได้) */
  phone: string;
  /** 13 หลัก checksum ถูกต้อง (มีขีดได้) */
  id_card_number: string;
  /** YYYY-MM-DD อายุ 18+ */
  birth_date: string;
  /** อย่างน้อย 10 ตัวอักษร */
  address: string;
  province?: string;
  district?: string;
  vehicle_type: RiderVehicleType;
  /** บังคับเมื่อ motorcycle / car */
  vehicle_plate?: string;
  vehicle_brand?: string;
  vehicle_color?: string;
}

export interface RiderRegisterResponse {
  outcome: 'created' | 'reapplied' | 'updated';
  rider: RiderStatus;
}

export interface RiderDocumentUploadResponse {
  type: RiderDocumentType;
  uploaded: boolean;
  url: string | null;
  documents: Record<RiderDocumentType, boolean>;
  documents_missing: RiderDocumentType[];
  documents_complete: boolean;
  documents_pending_review: boolean;
}

export interface RiderDocumentsResponse {
  documents: Array<{ type: RiderDocumentType; label: string; uploaded: boolean; required: boolean; url: string | null }>;
  required: RiderDocumentType[];
  missing: RiderDocumentType[];
  complete: boolean;
  pending_review: boolean;
}

export interface RiderPermissionsBody {
  gps?: boolean;
  gps_background?: boolean;
  camera?: boolean;
  microphone?: boolean;
  notification?: boolean;
  /** ต้องเป็น true ครั้งหนึ่งก่อนรับงานแรก */
  location_consent?: boolean;
}

export interface RiderPermissionsResponse {
  permissions: RiderStatus['permissions'];
  location_consent_at: string | null;
  can_accept_jobs: boolean;
  block_reason: RiderBlockReason | null;
}

export interface RiderProfileBody {
  phone: string;
  vehicle_type: RiderVehicleType;
  vehicle_plate?: string;
  vehicle_brand?: string;
  vehicle_color?: string;
  preferred_radius_km?: number;
  preferred_min_fee?: number;
  preferred_job_types?: Array<'delivery' | 'fresh_market' | 'shop_delivery' | 'food' | 'document'>;
}

export interface RiderAvailabilityResponse {
  availability: RiderAvailability;
  availability_text: string;
  can_accept_jobs: boolean;
  block_reason: RiderBlockReason | null;
  active_job_id: number | null;
}

export interface RiderLocationBody {
  latitude: number;
  longitude: number;
  accuracy?: number | null;
  speed?: number | null;
  heading?: number | null;
  altitude?: number | null;
  /** 0-100, 0-1 หรือ -1 */
  battery_level?: number | null;
  is_charging?: boolean;
  activity_type?: string;
  job_id?: number | null;
  device_model?: string;
  os_version?: string;
}

export interface RiderLocationResponse {
  has_active_job: boolean;
  job_id: number | null;
  is_tracking: boolean;
  gps_resumed: boolean;
  availability: RiderAvailability;
  server_time: string;
}

export interface RiderJobPoint {
  name: string | null;
  address: string | null;
  area?: string | null;
  latitude: number;
  longitude: number;
  phone: string | null;
  notes?: string | null;
  /** true = พิกัดโดยประมาณ (ยังไม่รับงาน) */
  is_approximate?: boolean;
}

export interface RiderJobSummary {
  id: number;
  job_number: string;
  job_type: string;
  job_type_text: string;
  title: string;
  items_summary: string | null;
  status: RiderJobStatus;
  status_text: string;
  is_mine: boolean;
  dispatch_type: 'broadcast' | 'cascade' | 'manual_needed' | string;
  pickup: RiderJobPoint;
  dropoff: RiderJobPoint;
  distance_km: number;
  distance_to_pickup_km: number | null;
  estimated_duration_minutes: number;
  base_fee: number;
  distance_fee: number;
  extra_fee: number;
  total_fee: number;
  platform_fee: number;
  rider_earnings: number;
  cod_amount: number;
  is_cod: boolean;
  allowed_actions: RiderJobAction[];
  created_at: string | null;
  accepted_at: string | null;
  completed_at: string | null;
}

export interface RiderJobDetail extends RiderJobSummary {
  description: string | null;
  customer_live_location: { latitude: number; longitude: number; updated_at: string } | null;
  gps_active: boolean;
  release_count: number;
  cod: { amount: number; collected_at: string | null; settled_at: string | null };
  earnings_settled: boolean;
  timeline: {
    created_at: string | null;
    accepted_at: string | null;
    picked_up_at: string | null;
    delivered_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    failed_at: string | null;
  };
  photos: { pickup: string | null; delivery: string | null; failure: string | null };
  failure: { reason_code: string; reason_text: string; note: string | null } | null;
  cancellation: { by: string; reason: string | null } | null;
  rider: {
    id: number;
    full_name: string;
    phone: string | null;
    vehicle_type: RiderVehicleType;
    vehicle_type_text: string;
    vehicle_plate: string | null;
    rating: number;
    has_profile_image: boolean;
  } | null;
  tracking_url?: string | null;
}

export interface AvailableJobsResponse {
  jobs: RiderJobSummary[];
  count: number;
  /** null = ปกติ · offline/busy/no_location = แสดงการ์ดบอกเหตุผล (ไม่ใช่ error) */
  reason: null | 'offline' | 'busy' | 'no_location';
  active_job_id: number | null;
  can_accept_jobs: boolean;
  block_reason: RiderBlockReason | null;
  rider_location: { latitude: number; longitude: number } | null;
}

export interface CurrentJobResponse {
  has_job: boolean;
  job: RiderJobDetail | null;
  is_tracking: boolean;
}

export interface JobHistoryResponse {
  jobs: RiderJobSummary[];
  pagination: Pagination & { has_more: boolean };
}

export type RiderFailReason = 'customer_unreachable' | 'wrong_address' | 'customer_refused' | 'item_damaged' | 'other';

export interface DeliverJobInput {
  /** รูปยืนยันการส่ง (บังคับ) */
  photoUri: string;
  latitude?: number;
  longitude?: number;
  /** บังคับ true เมื่อ job.cod_amount > 0 */
  codCollected?: boolean;
  note?: string;
}

export interface DeliverJobResponse {
  job: RiderJobDetail;
  earnings: { rider_earnings: number; cod_amount: number; settled: boolean; wallet_balance: number };
}

export interface FailJobInput {
  reasonCode: RiderFailReason;
  /** บังคับเมื่อ reasonCode = other */
  note?: string;
  photoUri?: string;
}

export type EarningsPeriod = 'today' | 'week' | 'month' | 'all';

export interface RiderEarningsResponse {
  period: EarningsPeriod;
  from: string | null;
  to: string | null;
  completed_jobs: number;
  gross_earnings: number;
  cod_jobs: number;
  cod_collected: number;
  cod_remitted: number;
  unsettled_jobs: number;
  wallet_balance: number;
  total_earnings_all_time: number;
  daily: Array<{ date: string; jobs: number; earnings: number }>;
  recent_jobs: Array<{
    id: number;
    job_number: string;
    job_type_text: string;
    completed_at: string | null;
    rider_earnings: number;
    total_fee: number;
    cod_amount: number;
    settled: boolean;
  }>;
}

export interface GpsLostResponse {
  warning_count: number;
  max_warnings: number;
  flow_stopped: boolean;
  message: string;
}

// =====================================================
// Account / สมัคร
// =====================================================

/** GET /rider/status */
export const getRiderStatus = (): Promise<ApiResult<RiderStatusResponse>> =>
  apiGet<RiderStatusResponse>('/rider/status');

/** POST /rider/register (สมัครใหม่ / สมัครซ้ำหลังถูกปฏิเสธ) */
export const registerRider = (body: RiderRegisterBody): Promise<ApiResult<RiderRegisterResponse>> =>
  apiPost<RiderRegisterResponse>('/rider/register', body);

/** POST /rider/document (multipart: type + image) — อัปซ้ำ = แทนไฟล์เดิม */
export const uploadRiderDocument = (
  type: RiderDocumentType,
  imageUri: string
): Promise<ApiResult<RiderDocumentUploadResponse>> => {
  const form = new FormData();
  form.append('type', type);
  form.append('image', fileFromUri(imageUri, type) as unknown as Blob);
  return apiUpload<RiderDocumentUploadResponse>('/rider/document', form);
};

/** GET /rider/documents (url เป็น signed link อายุ 30 นาที — เรียกใหม่เมื่อหมดอายุ) */
export const getRiderDocuments = (): Promise<ApiResult<RiderDocumentsResponse>> =>
  apiGet<RiderDocumentsResponse>('/rider/documents');

/** POST /rider/permissions — ส่ง gps=true เมื่อได้สิทธิ์ตำแหน่งขณะใช้งาน */
export const updateRiderPermissions = (body: RiderPermissionsBody): Promise<ApiResult<RiderPermissionsResponse>> =>
  apiPost<RiderPermissionsResponse>('/rider/permissions', body);

/** ยอมรับการแชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน (ต้องทำก่อนรับงานแรก — block_code CONSENT_REQUIRED) */
export const grantLocationConsent = (): Promise<ApiResult<RiderPermissionsResponse>> =>
  updateRiderPermissions({ location_consent: true });

/** PUT /rider/profile — เปลี่ยนยานพาหนะระหว่างมีงานได้ 409 HAS_ACTIVE_JOB */
export const updateRiderProfile = (body: RiderProfileBody): Promise<ApiResult<{ vehicle_changed: boolean; rider: RiderStatus }>> =>
  apiPut<{ vehicle_changed: boolean; rider: RiderStatus }>('/rider/profile', body);

/** POST /rider/availability — error: HAS_ACTIVE_JOB 409, NOT_ELIGIBLE 403 (data.block_code), GPS_REQUIRED 403 */
export const setRiderAvailability = (
  availability: 'online' | 'offline',
  coords?: { latitude: number; longitude: number }
): Promise<ApiResult<RiderAvailabilityResponse>> =>
  apiPost<RiderAvailabilityResponse>('/rider/availability', { availability, ...(coords || {}) });

/** POST /rider/location — ส่งทุก ~30 วินาทีตอนออนไลน์ (throttle 40/นาที ห้ามถี่กว่า 10 วินาที) */
export const sendRiderLocation = (body: RiderLocationBody): Promise<ApiResult<RiderLocationResponse>> =>
  apiPost<RiderLocationResponse>('/rider/location', body);

// =====================================================
// งาน
// =====================================================

/** GET /rider/jobs/available — offline/busy = 200 พร้อม reason (แสดงการ์ด ไม่ใช่ error) */
export const getAvailableJobs = (coords?: { latitude: number; longitude: number }): Promise<ApiResult<AvailableJobsResponse>> =>
  apiGet<AvailableJobsResponse>('/rider/jobs/available', coords ? { ...coords } : undefined);

/** GET /rider/jobs/current */
export const getCurrentJob = (): Promise<ApiResult<CurrentJobResponse>> =>
  apiGet<CurrentJobResponse>('/rider/jobs/current');

/** GET /rider/jobs/history */
export const getJobHistory = (params: {
  page?: number;
  status?: 'completed' | 'cancelled' | 'failed';
  per_page?: number;
} = {}): Promise<ApiResult<JobHistoryResponse>> =>
  apiGet<JobHistoryResponse>('/rider/jobs/history', params);

/** GET /rider/jobs/{id} — 403 NOT_YOUR_JOB, 404 JOB_NOT_FOUND */
export const getRiderJob = (jobId: number): Promise<ApiResult<{ job: RiderJobDetail }>> =>
  apiGet<{ job: RiderJobDetail }>(`/rider/jobs/${jobId}`);

/** POST /rider/jobs/{id}/accept — กดซ้ำโดยไรเดอร์คนเดิม = 200 */
export const acceptRiderJob = (
  jobId: number,
  coords?: { latitude: number; longitude: number }
): Promise<ApiResult<{ job: RiderJobDetail }>> =>
  apiPost<{ job: RiderJobDetail }>(`/rider/jobs/${jobId}/accept`, coords || {});

/** POST /rider/jobs/{id}/reject — ซ่อนงานนี้จากไรเดอร์คนนี้ */
export const rejectRiderJob = (jobId: number): Promise<ApiResult<{ job_id: number }>> =>
  apiPost<{ job_id: number }>(`/rider/jobs/${jobId}/reject`, {});

/** POST /rider/jobs/{id}/release — คืนงาน (ก่อนรับของเท่านั้น) */
export const releaseRiderJob = (jobId: number, reason?: string): Promise<ApiResult<{ job_id: number; status: 'pending' }>> =>
  apiPost<{ job_id: number; status: 'pending' }>(`/rider/jobs/${jobId}/release`, reason ? { reason } : {});

/** POST /rider/jobs/{id}/status — picking_up | picked_up (แนบรูปได้) | delivering */
export const updateRiderJobStatus = (
  jobId: number,
  status: 'picking_up' | 'picked_up' | 'delivering',
  photoUri?: string
): Promise<ApiResult<{ job: RiderJobDetail }>> => {
  if (photoUri) {
    const form = new FormData();
    form.append('status', status);
    form.append('photo', fileFromUri(photoUri, `job-${jobId}-${status}`) as unknown as Blob);
    return apiUpload<{ job: RiderJobDetail }>(`/rider/jobs/${jobId}/status`, form);
  }
  return apiPost<{ job: RiderJobDetail }>(`/rider/jobs/${jobId}/status`, { status });
};

/** POST /rider/jobs/{id}/deliver (multipart) — ส่งซ้ำได้ปลอดภัย (ไม่จ่ายซ้ำ) */
export const deliverRiderJob = (jobId: number, input: DeliverJobInput): Promise<ApiResult<DeliverJobResponse>> => {
  const form = new FormData();
  form.append('photo', fileFromUri(input.photoUri, `job-${jobId}-delivered`) as unknown as Blob);
  if (typeof input.latitude === 'number' && typeof input.longitude === 'number') {
    form.append('latitude', String(input.latitude));
    form.append('longitude', String(input.longitude));
  }
  if (input.codCollected !== undefined) {
    form.append('cod_collected', input.codCollected ? '1' : '0');
  }
  if (input.note) {
    form.append('note', input.note.slice(0, 500));
  }
  return apiUpload<DeliverJobResponse>(`/rider/jobs/${jobId}/deliver`, form);
};

/** POST /rider/jobs/{id}/fail — หลังรับของแล้วเท่านั้น, other ต้องมี note */
export const failRiderJob = (jobId: number, input: FailJobInput): Promise<ApiResult<{ job: RiderJobDetail }>> => {
  if (input.photoUri) {
    const form = new FormData();
    form.append('reason_code', input.reasonCode);
    if (input.note) form.append('note', input.note);
    form.append('photo', fileFromUri(input.photoUri, `job-${jobId}-failed`) as unknown as Blob);
    return apiUpload<{ job: RiderJobDetail }>(`/rider/jobs/${jobId}/fail`, form);
  }
  return apiPost<{ job: RiderJobDetail }>(`/rider/jobs/${jobId}/fail`, {
    reason_code: input.reasonCode,
    ...(input.note ? { note: input.note } : {}),
  });
};

/** POST /rider/jobs/{id}/gps-lost — แจ้งว่า GPS หาย (ระบบนับคำเตือน) */
export const reportRiderGpsLost = (jobId: number): Promise<ApiResult<GpsLostResponse>> =>
  apiPost<GpsLostResponse>(`/rider/jobs/${jobId}/gps-lost`, {});

/** POST /rider/jobs/{id}/gps-off — ยืนยันปิด GPS (กลับมาเองเมื่อส่ง /rider/location ใหม่) */
export const confirmRiderGpsOff = (jobId: number): Promise<ApiResult<{ message: string; gps_active: false }>> =>
  apiPost<{ message: string; gps_active: false }>(`/rider/jobs/${jobId}/gps-off`, {});

/** GET /rider/earnings?period= */
export const getRiderEarnings = (period: EarningsPeriod = 'today'): Promise<ApiResult<RiderEarningsResponse>> =>
  apiGet<RiderEarningsResponse>('/rider/earnings', { period });

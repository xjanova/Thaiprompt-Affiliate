/**
 * ekycStore — สถานะขั้นตอนยืนยันตัวตนด้วย AI ระหว่างหน้าจอ (บัตร → ใบหน้า → ผล)
 *
 * - เก็บในหน่วยความจำเท่านั้น (ห้าม persist): มี uri รูปบัตร/ใบหน้า และข้อมูลบนบัตร
 * - ไฟล์รูปชั่วคราวในเครื่องถูกลบเมื่อ: ถ่ายใหม่ · ส่งเสร็จ · ออกจากขั้นตอน (resetFlow)
 * - status = สถานะยืนยันตัวตนล่าสุดจาก GET /ekyc/status (หน้าโปรไฟล์/ป้ายทองใช้ร่วมกัน)
 */

import { create } from 'zustand';
import {
  createEkycSession,
  getEkycStatus,
  type EkycCorrections,
  type EkycFaceResult,
  type EkycFrame,
  type EkycIdCardResult,
  type EkycSession,
  type EkycStatus,
} from '@/services/api/ekycApi';
import type { ApiResult } from '@/services/api/client';
import type { KycGateContext } from '@/services/ekyc/kycGate';
import { dropTempFile, dropTempFiles } from '@/services/ekyc/tempFiles';

/** อายุแคชสถานะ (มิลลิวินาที) */
const STATUS_TTL_MS = 60_000;

export interface EkycCard {
  /** รูปบัตรในเครื่อง (ชั่วคราว) */
  uri: string;
  /** ผลอ่านบัตรจาก server (null = ยังไม่ส่ง/ส่งไม่สำเร็จ) */
  result: EkycIdCardResult | null;
  /** รูปนี้ส่งเข้ารอบ (session) ไหนแล้ว */
  sessionId: string | null;
}

interface EkycStore {
  from: KycGateContext | null;
  session: EkycSession | null;
  card: EkycCard | null;
  corrections: EkycCorrections;
  frames: EkycFrame[];
  decision: EkycFaceResult | null;
  /** เวลาเริ่มขั้นตอน (ใช้บอก "ใช้เวลา … วินาที") */
  startedAt: number | null;

  status: EkycStatus | null;
  statusLoadedAt: number;
  statusLoading: boolean;

  setFrom: (from: KycGateContext | null) => void;
  /** เริ่มรอบใหม่ (ยินยอม PDPA แล้ว) */
  startSession: () => Promise<ApiResult<EkycSession>>;
  setCard: (uri: string) => void;
  setCardResult: (result: EkycIdCardResult, sessionId: string) => void;
  setCorrections: (corrections: EkycCorrections) => void;
  setFrames: (frames: EkycFrame[]) => void;
  clearFrames: () => void;
  setDecision: (decision: EkycFaceResult | null) => void;
  loadStatus: (force?: boolean) => Promise<EkycStatus | null>;
  setStatus: (status: EkycStatus | null) => void;
  /** จบ/ทิ้งขั้นตอน: ลบไฟล์ชั่วคราวทั้งหมด (คงสถานะไว้) */
  resetFlow: () => void;
  /** ออกจากระบบ/เปลี่ยนบัญชี: ล้างทุกอย่าง */
  resetAll: () => void;
}

export const useEkycStore = create<EkycStore>((set, get) => ({
  from: null,
  session: null,
  card: null,
  corrections: {},
  frames: [],
  decision: null,
  startedAt: null,

  status: null,
  statusLoadedAt: 0,
  statusLoading: false,

  setFrom: (from) => set({ from }),

  startSession: async () => {
    const result = await createEkycSession();
    if (result.success) {
      // รอบใหม่ = เฟรมใบหน้าเดิมใช้ไม่ได้แล้ว (คำสั่งสุ่มใหม่)
      dropTempFiles(get().frames.map((f) => f.uri));
      set({
        session: result.data,
        frames: [],
        decision: null,
        corrections: {},
        startedAt: get().startedAt ?? Date.now(),
      });
    }
    return result;
  },

  setCard: (uri) => {
    const old = get().card;
    if (old && old.uri !== uri) dropTempFile(old.uri);
    set({ card: { uri, result: null, sessionId: null }, corrections: {} });
  },

  setCardResult: (result, sessionId) => {
    const card = get().card;
    if (!card) return;
    set({ card: { ...card, result, sessionId } });
  },

  setCorrections: (corrections) => set({ corrections }),

  setFrames: (frames) => {
    const keep = new Set(frames.map((f) => f.uri));
    dropTempFiles(get().frames.map((f) => f.uri).filter((uri) => !keep.has(uri)));
    set({ frames });
  },

  clearFrames: () => {
    dropTempFiles(get().frames.map((f) => f.uri));
    set({ frames: [] });
  },

  setDecision: (decision) => set({ decision }),

  loadStatus: async (force = false) => {
    const state = get();
    if (!force && state.status && Date.now() - state.statusLoadedAt < STATUS_TTL_MS) return state.status;
    if (state.statusLoading && !force) return state.status;
    set({ statusLoading: true });
    const result = await getEkycStatus();
    if (result.success) {
      set({ status: result.data, statusLoadedAt: Date.now(), statusLoading: false });
      return result.data;
    }
    set({ statusLoading: false });
    return get().status;
  },

  setStatus: (status) => set({ status, statusLoadedAt: status ? Date.now() : 0 }),

  resetFlow: () => {
    const { card, frames } = get();
    dropTempFiles([...(card ? [card.uri] : []), ...frames.map((f) => f.uri)]);
    set({ session: null, card: null, corrections: {}, frames: [], decision: null, startedAt: null });
  },

  resetAll: () => {
    get().resetFlow();
    set({ from: null, status: null, statusLoadedAt: 0, statusLoading: false });
  },
}));

export default useEkycStore;

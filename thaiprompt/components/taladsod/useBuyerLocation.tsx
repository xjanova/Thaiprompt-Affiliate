/**
 * useBuyerLocation — ขอตำแหน่งของผู้ซื้อแบบ "prominent disclosure" (นโยบาย Google Play)
 *
 * ลำดับ: ConsentSheet อธิบายเหตุผลก่อน → กล่องขอสิทธิ์ของระบบ → อ่านพิกัด (มี timeout)
 *   - ได้สิทธิ์อยู่แล้ว → อ่านพิกัดเลย (ไม่แสดง sheet) ยกเว้นเหตุผล 'share' ที่ต้องยินยอมทุกครั้ง
 *   - ปฏิเสธ → คืน null (หน้าจอต้องมีทางเลือกอื่นเสมอ เช่น เลือกจังหวัด / ที่อยู่ที่บันทึกไว้ / แตะแผนที่)
 *   - ปฏิเสธถาวร → sheet เสนอปุ่ม "เปิดการตั้งค่า"
 *
 * ใช้ตำแหน่ง "ขณะใช้แอป" เท่านั้น (ไม่ขอเบื้องหลัง)
 *
 * @example
 * const location = useBuyerLocation();
 * const coords = await location.request('nearby');
 * return <>{location.element} ...</>;
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Linking } from 'react-native';
import * as Location from 'expo-location';
import { ConsentSheet, resultHaptic, type ConsentReason } from '@/components/ui';
import { getCurrentCoords, isLocationServiceEnabled, type Coords } from '@/services/location';

export type BuyerLocationReason = 'nearby' | 'delivery' | 'share';

interface SheetCopy {
  icon: string;
  title: string;
  description: string;
  reasons: ConsentReason[];
  acceptLabel: string;
  footnote?: string;
}

const COPY: Record<BuyerLocationReason, SheetCopy> = {
  nearby: {
    icon: '📍',
    title: 'หาร้านที่เปิดอยู่ใกล้คุณ',
    description: 'ขอใช้ตำแหน่งตอนนี้ เพื่อบอกว่าร้านไหนเปิดอยู่ใกล้ๆ และอยู่ห่างแค่ไหน',
    reasons: [
      { icon: '🏪', text: 'แสดงร้านและรถเข็นที่เปิดอยู่รอบตัวคุณ พร้อมระยะทาง' },
      { icon: '🔒', text: 'ใช้เฉพาะตอนเปิดหน้านี้ ไม่ติดตามเบื้องหลัง และไม่ส่งให้ร้าน' },
      { icon: '🙂', text: 'ไม่อนุญาตก็ได้ เลือกจังหวัดเองแทนได้เลย' },
    ],
    acceptLabel: 'อนุญาตใช้ตำแหน่ง',
  },
  delivery: {
    icon: '🛵',
    title: 'ปักหมุดจุดส่งด้วย GPS',
    description: 'ขอใช้ตำแหน่งตอนนี้เป็นจุดส่งของ เพื่อคำนวณค่าส่งและให้ไรเดอร์มาถูกที่',
    reasons: [
      { icon: '📦', text: 'ไรเดอร์ของออเดอร์นี้จะเห็นหมุดจุดส่งเพื่อนำของมาส่ง' },
      { icon: '🔒', text: 'อ่านตำแหน่งครั้งเดียวตอนนี้ ไม่ติดตามเบื้องหลัง' },
      { icon: '🙂', text: 'ไม่อนุญาตก็ได้ ใช้ที่อยู่ที่บันทึกไว้ หรือแตะแผนที่ปักหมุดเอง' },
    ],
    acceptLabel: 'ใช้ตำแหน่งตอนนี้',
  },
  share: {
    icon: '🤝',
    title: 'แชร์ตำแหน่งของคุณให้ไรเดอร์',
    description: 'ช่วยให้ไรเดอร์หาคุณเจอเร็วขึ้น โดยเฉพาะตอนนัดรับหน้าซอยหรือจุดที่หายาก',
    reasons: [
      { icon: '🛵', text: 'เห็นได้เฉพาะไรเดอร์ที่กำลังส่งออเดอร์นี้เท่านั้น' },
      { icon: '⏱️', text: 'ส่งตำแหน่งทุก 30 วินาที เฉพาะตอนเปิดหน้านี้อยู่' },
      { icon: '✅', text: 'หยุดเองเมื่อส่งของเสร็จ และปิดได้ทุกเมื่อ' },
    ],
    acceptLabel: 'ยอมรับและแชร์ตำแหน่ง',
    footnote: 'ปิดสวิตช์เมื่อไหร่ ระบบลบตำแหน่งที่แชร์ไว้ทันที',
  },
};

/** ตำแหน่งล่าสุดที่อ่านได้ (ใช้ซ้ำระหว่างหน้าจอ ไม่ต้องถามใหม่) */
let lastCoords: { coords: Coords; at: number } | null = null;

/** ตำแหน่งล่าสุดที่เคยอ่านได้ในรอบการเปิดแอปนี้ (เก่ากว่า maxAgeMs = null) */
export const getCachedBuyerCoords = (maxAgeMs: number = 10 * 60_000): Coords | null =>
  lastCoords && Date.now() - lastCoords.at <= maxAgeMs ? lastCoords.coords : null;

const MODAL_SETTLE_MS = 350;
const wait = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms));

type SheetState = { reason: BuyerLocationReason; blocked: boolean } | null;

export interface BuyerLocationFlow {
  /** วางไว้ใน JSX ของหน้าจอหนึ่งครั้ง */
  element: React.ReactNode;
  /** ขอตำแหน่ง (แสดงคำอธิบายก่อนถ้าจำเป็น) — null = ไม่ได้ (ปฏิเสธ/หาไม่เจอ) */
  request: (reason: BuyerLocationReason) => Promise<Coords | null>;
  /** อ่านตำแหน่งแบบเงียบ: มีสิทธิ์แล้วเท่านั้น (ไม่ถาม ไม่แสดง sheet) */
  peek: () => Promise<Coords | null>;
  /** กำลังอ่าน GPS */
  locating: boolean;
}

export const useBuyerLocation = (): BuyerLocationFlow => {
  const [sheet, setSheet] = useState<SheetState>(null);
  const [locating, setLocating] = useState(false);
  const resolverRef = useRef<((accepted: boolean) => void) | null>(null);
  const mountedRef = useRef(true);
  const busyRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
      resolverRef.current?.(false);
      resolverRef.current = null;
    };
  }, []);

  const openSheet = useCallback((reason: BuyerLocationReason, blocked: boolean): Promise<boolean> => {
    resolverRef.current?.(false);
    return new Promise<boolean>((resolve) => {
      resolverRef.current = resolve;
      if (mountedRef.current) setSheet({ reason, blocked });
    });
  }, []);

  const closeSheet = useCallback((accepted: boolean) => {
    const resolve = resolverRef.current;
    resolverRef.current = null;
    if (mountedRef.current) setSheet(null);
    resolve?.(accepted);
  }, []);

  const readCoords = useCallback(async (): Promise<Coords | null> => {
    if (mountedRef.current) setLocating(true);
    const coords = await getCurrentCoords({ accuracy: 'high', timeoutMs: 9000 });
    if (mountedRef.current) setLocating(false);
    if (coords) {
      lastCoords = { coords, at: Date.now() };
      return coords;
    }
    const servicesOn = await isLocationServiceEnabled();
    if (mountedRef.current) {
      resultHaptic('warning');
      Alert.alert(
        'ยังหาตำแหน่งไม่เจอ',
        servicesOn
          ? 'สัญญาณ GPS อ่อน ลองขยับไปที่โล่งแล้วลองใหม่อีกครั้งนะ'
          : 'GPS ของเครื่องปิดอยู่ เปิดตำแหน่งในแถบด้านบนแล้วลองใหม่นะ'
      );
    }
    return null;
  }, []);

  const peek = useCallback(async (): Promise<Coords | null> => {
    try {
      const perm = await Location.getForegroundPermissionsAsync();
      if (perm.status !== 'granted') return null;
    } catch {
      return null;
    }
    const cached = getCachedBuyerCoords(2 * 60_000);
    if (cached) return cached;
    const coords = await getCurrentCoords({ accuracy: 'balanced', timeoutMs: 6000 });
    if (coords) lastCoords = { coords, at: Date.now() };
    return coords;
  }, []);

  const request = useCallback(
    async (reason: BuyerLocationReason): Promise<Coords | null> => {
      if (busyRef.current) return null;
      busyRef.current = true;
      try {
        let perm: Location.LocationPermissionResponse | null = null;
        try {
          perm = await Location.getForegroundPermissionsAsync();
        } catch {
          perm = null;
        }
        const granted = perm?.status === 'granted';

        // ได้สิทธิ์แล้ว: แชร์ให้ไรเดอร์ยังต้องยินยอมทุกครั้ง · อย่างอื่นอ่านพิกัดเลย
        if (granted) {
          if (reason === 'share') {
            const ok = await openSheet(reason, false);
            if (!ok) return null;
            await wait(MODAL_SETTLE_MS);
          }
          return await readCoords();
        }

        const blocked = perm ? perm.canAskAgain === false : false;
        const accepted = await openSheet(reason, blocked);
        if (!accepted) return null;
        await wait(MODAL_SETTLE_MS);

        if (blocked) {
          Linking.openSettings().catch(() => {});
          return null;
        }

        let result: Location.LocationPermissionResponse | null = null;
        try {
          result = await Location.requestForegroundPermissionsAsync();
        } catch {
          result = null;
        }
        if (result?.status !== 'granted') {
          if (mountedRef.current && result && result.canAskAgain === false) {
            Alert.alert('ไม่ได้รับสิทธิ์ตำแหน่ง', 'เปิดสิทธิ์ตำแหน่งให้แอปได้ในการตั้งค่าเครื่องทุกเมื่อ', [
              { text: 'ไว้ก่อน', style: 'cancel' },
              { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
            ]);
          }
          return null;
        }
        return await readCoords();
      } finally {
        busyRef.current = false;
      }
    },
    [openSheet, readCoords]
  );

  const copy = sheet ? COPY[sheet.reason] : null;

  const element = copy ? (
    <ConsentSheet
      visible={!!sheet}
      icon={copy.icon}
      title={copy.title}
      description={sheet?.blocked ? 'สิทธิ์ตำแหน่งถูกปิดไว้ในเครื่อง เปิดได้ที่การตั้งค่าของแอป' : copy.description}
      reasons={copy.reasons}
      acceptLabel={sheet?.blocked ? 'เปิดการตั้งค่า' : copy.acceptLabel}
      declineLabel="ไม่ตอนนี้"
      footnote={copy.footnote}
      onAccept={() => closeSheet(true)}
      onDecline={() => closeSheet(false)}
    />
  ) : null;

  return { element, request, peek, locating };
};

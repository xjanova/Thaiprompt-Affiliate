/**
 * HandoverEntryCard — การ์ดในหน้าออเดอร์ (ร้านค้า + ตลาดสด) ที่พาไปหน้า "รับของจากไรเดอร์"
 *
 * - ไรเดอร์รับของแล้ว/กำลังมาส่ง/รอปลดเงิน หรือการส่งมอบยังไม่จบ → การ์ดเด่น "รับของ / สแกนกับไรเดอร์"
 * - จบแล้ว (ได้รับของ) → การ์ดให้หัวใจไรเดอร์ + ลิงก์ดูการแบ่งเงิน
 * - งานเก่าที่ไม่ต้องสแกน (required=false) / ยังไม่ถึงขั้นรับของ / ไม่มีงานไรเดอร์ → ไม่แสดงอะไร
 *   (§A1: server ตัดสิน required ตอนไรเดอร์รับงาน — งานที่ไรเดอร์ใช้เว็บ/แอปเก่ารับไป = ขั้นตอนเดิม ใช้ตัวติดตามแบบเดิม)
 * - ดึง GET /orders/{source}/{id}/handover เมื่อสถานะงานไรเดอร์เปลี่ยน + ทุก 20 วินาทีระหว่างหน้าเปิดและยังไม่จบ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Icon, OnHeaderProvider, Pill } from '@/components/ui';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import { useFocusedInterval, useMountedRef } from '@/components/taladsod/hooks';
import {
  getOrderHandover,
  isHandoverFinal,
  isHandoverSuccess,
  type BuyerHandoverData,
  type HandoverSource,
} from '@/services/api/handoverApi';
import { useTheme, radii, spacing, typography } from '@/theme';
import { RiderHeartCard } from './RiderHeartCard';

/** สถานะงานไรเดอร์ที่ควรเตรียมรับของ */
export const HANDOVER_ACTIVE_JOB_STATUSES = ['picked_up', 'delivering', 'arrived', 'awaiting_release', 'delivered'];

export interface HandoverEntryCardProps {
  source: HandoverSource;
  orderId: number;
  orderNumber?: string | null;
  /** สถานะงานไรเดอร์ของออเดอร์ (null = ยังไม่มีงาน) */
  riderJobStatus: string | null;
  /** ออเดอร์จบแล้ว (completed) — ใช้ตัดสินว่าจะดึงข้อมูลให้หัวใจไหม */
  orderCompleted: boolean;
  style?: object;
}

const POLL_MS = 20000;

/** ข้อความสถานะสั้นๆ บนการ์ด */
const statusLine = (data: BuyerHandoverData): string => {
  const h = data.handover;
  if (h.status === 'disputed') return 'แจ้งปัญหาแล้ว ทีมงานกำลังตรวจสอบ';
  if (h.status === 'fallback_pending_release')
    return h.can_confirm_received ? 'ไรเดอร์วางของไว้ให้แล้ว ได้รับของแล้วกดยืนยันได้เลย' : 'ไรเดอร์วางของไว้ให้แล้ว ได้รับของไหม? เปิดดูได้เลย';
  if (h.status === 'fallback_waiting') return 'ไรเดอร์ถึงจุดส่งแล้ว กำลังรอคุณอยู่';
  if (h.rider_confirmed && !h.buyer_confirmed) return 'ไรเดอร์สแกนคุณแล้ว เหลือคุณสแกน QR ของไรเดอร์';
  if (h.buyer_confirmed && !h.rider_confirmed) return 'คุณสแกนแล้ว รอไรเดอร์สแกน QR ของคุณ';
  const near = data.job?.distance_to_dropoff_m;
  if (near !== null && near !== undefined && near <= 200) return 'ไรเดอร์ใกล้ถึงแล้ว เตรียมเปิด QR ให้สแกน';
  return 'ไรเดอร์กำลังมาส่ง ตอนรับของให้สแกน QR ใส่กัน เงินจึงถูกโอนให้ร้าน';
};

export const HandoverEntryCard: React.FC<HandoverEntryCardProps> = ({
  source,
  orderId,
  orderNumber,
  riderJobStatus,
  orderCompleted,
  style,
}) => {
  const { colors, gradients } = useTheme();
  const mountedRef = useMountedRef();
  const [data, setData] = useState<BuyerHandoverData | null>(null);
  const busyRef = useRef(false);

  const relevant =
    orderId > 0 &&
    !!riderJobStatus &&
    (HANDOVER_ACTIVE_JOB_STATUSES.includes(riderJobStatus) || riderJobStatus === 'completed' || orderCompleted);

  const load = useCallback(async () => {
    if (!relevant || busyRef.current) return;
    busyRef.current = true;
    const res = await getOrderHandover(source, orderId);
    busyRef.current = false;
    if (!mountedRef.current) return;
    if (res.success) setData(res.data);
    else if (res.status === 404 || res.code === 'HANDOVER_NOT_READY' || res.status === 403) setData(null);
    // เน็ตหลุดชั่วคราว → คงข้อมูลเดิมไว้
  }, [relevant, source, orderId, mountedRef]);

  useEffect(() => {
    load();
  }, [load, riderJobStatus, orderCompleted]);

  const final = isHandoverFinal(data?.handover);
  useFocusedInterval(load, POLL_MS, relevant && !!data && data.handover.required && !final);

  if (!relevant || !data || !data.handover.required) return null;

  const openHandover = () =>
    router.push(
      `/handover/${source}/${orderId}${orderNumber ? `?no=${encodeURIComponent(orderNumber.slice(0, 40))}` : ''}` as never
    );

  // ---------- จบแล้ว: ให้หัวใจ ----------
  if (final) {
    if (!isHandoverSuccess(data.handover)) {
      return (
        <View style={[styles.plain, { backgroundColor: colors.infoSoft, borderColor: colors.border }, style]}>
          <Icon name="arrow-counter-clockwise" size={20} color={colors.info} />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            ออเดอร์นี้คืนเงินแล้ว ตามผลตรวจสอบของทีมงาน
          </Text>
        </View>
      );
    }
    return (
      <View style={style}>
        {data.rider && <RiderHeartCard source={source} orderId={orderId} rider={data.rider} />}
        {!!data.settlement && (
          <Button3D
            title="ดูว่าเงินถูกแบ่งให้ใครบ้าง"
            icon="receipt"
            variant="ghost"
            size="sm"
            onPress={openHandover}
            style={styles.center}
          />
        )}
      </View>
    );
  }

  // ---------- ยังไม่จบ: การ์ดเด่นพาไปหน้ารับของ ----------
  const h = data.handover;
  return (
    <View style={[styles.wrap, style]}>
      <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.hero}>
        <View style={styles.row}>
          {data.rider ? (
            <PersonAvatar uri={data.rider.photo_url} name={data.rider.display_name} size={52} surfaceColor={colors.navyFill} online />
          ) : (
            <View style={[styles.qrIcon, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
              <Icon name="qr-code" size={26} color={colors.goldLight} />
            </View>
          )}
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.onHeader }]}>รับของ / สแกนกับไรเดอร์</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>{statusLine(data)}</Text>
          </View>
        </View>
        <OnHeaderProvider value>
          <View style={styles.pills}>
          <Pill label={h.rider_confirmed ? 'ไรเดอร์สแกนแล้ว' : 'รอไรเดอร์สแกน'} tone={h.rider_confirmed ? 'success' : 'neutral'} icon={h.rider_confirmed ? 'check' : 'clock'} />
          <Pill label={h.buyer_confirmed ? 'คุณสแกนแล้ว' : 'รอคุณสแกน'} tone={h.buyer_confirmed ? 'success' : 'gold'} icon={h.buyer_confirmed ? 'check' : 'scan'} />
          </View>
        </OnHeaderProvider>
        <Button3D
          title={
            h.status === 'fallback_pending_release'
              ? h.can_confirm_received
                ? 'ยืนยันรับของ / แจ้งปัญหา'
                : 'เปิดดู / แจ้งปัญหา'
              : 'เปิด QR รับของ'
          }
          icon="qr-code"
          size="lg"
          fullWidth
          onPress={openHandover}
          style={styles.button}
        />
      </LinearGradient>
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {
    borderRadius: radii.xl,
    overflow: 'hidden',
  },
  hero: {
    padding: spacing.lg,
    gap: spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  flex: {
    flex: 1,
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  qrIcon: {
    width: 52,
    height: 52,
    borderRadius: 18,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  button: {
    marginTop: spacing.xs,
  },
  center: {
    alignSelf: 'center',
    marginTop: spacing.xs,
  },
  plain: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
});

export default HandoverEntryCard;

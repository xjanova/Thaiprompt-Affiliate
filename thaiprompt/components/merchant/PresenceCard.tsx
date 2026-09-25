/**
 * PresenceCard — การ์ดสถานะหน้าร้านตลาดสด (เปิด/ปิด, จุดขายวันนี้, แชร์ตำแหน่งสด)
 *
 * - ปิดอยู่: ปุ่มใหญ่ "เปิดร้านที่นี่วันนี้" + จำนวนผู้ติดตามที่จะได้รับแจ้งเตือน
 * - เปิดอยู่: จุดขาย, เวลาปิดอัตโนมัติ, สถานะการส่งตำแหน่งสด, ย้ายจุดขาย, ปิดร้าน
 * - สลับ "ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)" ได้ที่ท้ายการ์ด
 */

import React, { useCallback, useState } from 'react';
import { StyleSheet, Switch, Text, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { Button3D, Card3D, LiveMap, Pill } from '@/components/ui';
import type { OwnerPresence } from '@/services/api/taladsodSellerApi';
import type { ShopLiveState } from '@/services/taladsodLiveLocation';
import { useTheme, radii, spacing, typography } from '@/theme';
import { clockTh, timeAgoTh } from './fmHelpers';

export interface PresenceCardProps {
  presence: OwnerPresence;
  live: ShopLiveState;
  needsGps: boolean;
  /** ออเดอร์ที่ยังทำไม่เสร็จ */
  activeOrders: number;
  onOpen: () => unknown;
  onUpdate: () => unknown;
  onClose: () => unknown;
  onToggleLive: (next: boolean) => unknown;
  onToggleMobile: (next: boolean) => unknown;
  /** ร้านตั้งแชร์ตำแหน่งสดไว้ แต่เครื่องนี้ยังไม่ได้ส่ง → เริ่มส่ง (ขอสิทธิ์ถ้ายังไม่มี) */
  onResumeLive?: () => unknown;
  /** กำลังเปลี่ยนสถานะอยู่ (ปิดสวิตช์ชั่วคราว) */
  switching?: boolean;
}

export const PresenceCard: React.FC<PresenceCardProps> = ({
  presence,
  live,
  needsGps,
  activeOrders,
  onOpen,
  onUpdate,
  onClose,
  onToggleLive,
  onToggleMobile,
  onResumeLive,
  switching = false,
}) => {
  const { colors, gradients } = useTheme();
  const [now, setNow] = useState(Date.now());

  // นาฬิกาเล็กๆ สำหรับ "ส่งล่าสุด ... ที่แล้ว" — เดินเฉพาะตอนหน้านี้อยู่บนจอ
  useFocusEffect(
    useCallback(() => {
      setNow(Date.now());
      const timer = setInterval(() => setNow(Date.now()), 10_000);
      return () => clearInterval(timer);
    }, [])
  );

  const open = presence.is_open;
  const loc = presence.location;
  // ตำแหน่งบนแผนที่: ตำแหน่งสดล่าสุดที่ส่งจากเครื่องนี้ (ถ้ามี) ไม่งั้นใช้ที่ server บอก
  const mapPoint = live.active && live.lastCoords ? live.lastCoords : loc ? { latitude: loc.latitude, longitude: loc.longitude } : null;

  const liveLine = (() => {
    if (!presence.live_location_sharing) return null;
    if (!live.active) {
      if (live.stoppedReason === 'permission') return { tone: colors.danger, text: 'หยุดแชร์ — ยังไม่ได้สิทธิ์ตำแหน่ง' };
      return { tone: colors.warning, text: 'ยังไม่ได้เริ่มส่งตำแหน่งจากเครื่องนี้' };
    }
    if (live.paused) return { tone: colors.warning, text: 'หยุดชั่วคราว (แอปอยู่เบื้องหลัง)' };
    if (live.lastError) return { tone: colors.danger, text: live.lastError };
    if (live.sending && !live.lastSentAt) return { tone: colors.textMuted, text: 'กำลังส่งตำแหน่ง...' };
    if (live.lastSentAt) return { tone: colors.success, text: `ส่งตำแหน่งล่าสุด ${timeAgoTh(live.lastSentAt, now)}` };
    return { tone: colors.textMuted, text: 'กำลังเริ่มส่งตำแหน่ง...' };
  })();

  return (
    <Card3D gradientBorder={open ? gradients.success : true} padding={spacing.lg}>
      {/* ---------- หัวการ์ด ---------- */}
      <View style={styles.header}>
        <View style={[styles.dot, { backgroundColor: open ? colors.success : colors.textFaint }]} />
        <Text style={[typography.h2, styles.flex, { color: colors.textStrong }]} numberOfLines={1}>
          {open ? 'ร้านเปิดอยู่' : 'ร้านปิดอยู่'}
        </Text>
        <Pill
          label={presence.is_mobile ? 'ร้านเคลื่อนที่' : 'ร้านประจำที่'}
          icon={presence.is_mobile ? '🛒' : '🏠'}
          tone={presence.is_mobile ? 'gold' : 'neutral'}
        />
      </View>

      {!open ? (
        <>
          <Text style={[typography.body, styles.lead, { color: colors.textMuted }]}>
            {needsGps
              ? 'ไปถึงจุดขายแล้วกดเปิดร้าน ลูกค้าใกล้ๆ จะเห็นร้านคุณและสั่งได้ทันที'
              : 'กดเปิดร้านเมื่อพร้อมขาย ลูกค้าจะสั่งได้จนถึงเวลาปิดที่ตั้งไว้'}
          </Text>
          {presence.followers_count > 0 && (
            <Text style={[typography.bodySm, styles.followers, { color: colors.goldDeep }]}>
              🔔 ผู้ติดตาม {presence.followers_count.toLocaleString('th-TH')} คนจะได้รับแจ้งเตือนเมื่อร้านเปิด
            </Text>
          )}
          <Button3D
            title={needsGps ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้านวันนี้'}
            icon="🛒"
            variant="success"
            size="lg"
            fullWidth
            onPress={onOpen}
            style={styles.bigButton}
            accessibilityHint={needsGps ? 'ใช้ตำแหน่งปัจจุบันเป็นจุดขายวันนี้' : undefined}
          />
        </>
      ) : (
        <>
          {/* ---------- จุดขาย ---------- */}
          <View style={[styles.infoBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
            <View style={styles.infoRow}>
              <Text style={styles.infoIcon}>📍</Text>
              <View style={styles.flex}>
                <Text style={[typography.caption, { color: colors.textMuted }]}>จุดขายวันนี้</Text>
                <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={2}>
                  {presence.location_label || loc?.label || (needsGps ? 'ตามตำแหน่งที่ปักไว้' : 'ที่อยู่ร้าน')}
                </Text>
              </View>
            </View>
            {!!presence.closes_at && (
              <View style={styles.infoRow}>
                <Text style={styles.infoIcon}>⏰</Text>
                <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                  ปิดร้านอัตโนมัติ {clockTh(presence.closes_at)}
                </Text>
              </View>
            )}
            {presence.followers_count > 0 && (
              <View style={styles.infoRow}>
                <Text style={styles.infoIcon}>🔔</Text>
                <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                  ผู้ติดตาม {presence.followers_count.toLocaleString('th-TH')} คน
                </Text>
              </View>
            )}
          </View>

          {!!mapPoint && (
            <LiveMap
              markers={[
                {
                  id: 'shop',
                  kind: 'shop',
                  latitude: mapPoint.latitude,
                  longitude: mapPoint.longitude,
                  label: presence.location_label || presence.shop_name,
                },
              ]}
              height={170}
              caption={needsGps ? 'ลูกค้าเห็นร้านตรงหมุดนี้ขณะร้านเปิด' : 'ที่อยู่ร้าน'}
              accessibilityLabel="แผนที่ตำแหน่งร้านวันนี้"
              style={styles.map}
            />
          )}

          {/* ---------- แชร์ตำแหน่งสด ---------- */}
          {needsGps && (
            <View style={[styles.liveBox, { borderColor: colors.border }]}>
              <View style={styles.infoRow}>
                <View style={styles.flex}>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>📡 แชร์ตำแหน่งสด</Text>
                  {liveLine ? (
                    <Text style={[typography.caption, { color: liveLine.tone }]}>{liveLine.text}</Text>
                  ) : (
                    <Text style={[typography.caption, { color: colors.textMuted }]}>ปิดอยู่ — ลูกค้าเห็นร้านตรงจุดที่ปักไว้</Text>
                  )}
                </View>
                <Switch
                  value={presence.live_location_sharing}
                  onValueChange={(next) => {
                    onToggleLive(next);
                  }}
                  disabled={switching}
                  trackColor={{ false: colors.border, true: colors.success }}
                  thumbColor={colors.card}
                  accessibilityLabel="แชร์ตำแหน่งสด"
                />
              </View>
              {presence.live_location_sharing && !live.active && !!onResumeLive && (
                <Button3D
                  title="เริ่มส่งตำแหน่งจากเครื่องนี้"
                  icon="📡"
                  variant="success"
                  size="sm"
                  onPress={onResumeLive}
                  style={styles.resume}
                />
              )}
              {presence.live_location_sharing && (
                <Text style={[typography.micro, styles.note, { color: colors.textFaint }]}>
                  แอปส่งตำแหน่งทุก {presence.live_send_interval_seconds} วินาทีเฉพาะตอนเปิดแอปไว้ ปิดแอปแล้วการแชร์จะหยุดชั่วคราว
                  {'\n'}ถ้าไม่ได้ส่งเกิน {presence.live_stale_minutes} นาที ระบบจะปิดร้านให้เอง
                </Text>
              )}
            </View>
          )}

          <View style={styles.buttons}>
            <Button3D
              title={needsGps ? 'ย้ายจุดขายมาที่นี่' : 'เปลี่ยนเวลาปิด'}
              icon={needsGps ? '📍' : '⏰'}
              variant="secondary"
              size="md"
              onPress={onUpdate}
              style={styles.flex}
            />
            <Button3D title="ปิดร้าน" icon="🔒" variant="danger" size="md" onPress={onClose} style={styles.flex} />
          </View>
          {activeOrders > 0 && (
            <Text style={[typography.caption, styles.note, { color: colors.warning }]}>
              มีออเดอร์ที่ยังทำไม่เสร็จ {activeOrders} รายการ — ปิดร้านแล้วยังทำต่อได้ แต่ลูกค้าใหม่จะสั่งไม่ได้
            </Text>
          )}
        </>
      )}

      {/* ---------- ประเภทร้าน ---------- */}
      <View style={[styles.mobileRow, { borderTopColor: colors.divider }]}>
        <View style={styles.flex}>
          <Text style={[typography.bodySm, { color: colors.textStrong }]}>ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)</Text>
          <Text style={[typography.micro, { color: colors.textMuted }]}>
            {presence.is_mobile ? 'เปิดร้านตามจุดที่คุณอยู่แต่ละวัน' : presence.has_fixed_location ? 'เปิดที่ที่อยู่ร้านประจำ' : 'ยังไม่มีที่อยู่ร้าน — เปิดร้านตามตำแหน่งที่อยู่'}
          </Text>
        </View>
        <Switch
          value={presence.is_mobile}
          onValueChange={(next) => {
            onToggleMobile(next);
          }}
          disabled={switching}
          trackColor={{ false: colors.border, true: colors.gold }}
          thumbColor={colors.card}
          accessibilityLabel="ร้านเคลื่อนที่"
        />
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  dot: {
    width: 12,
    height: 12,
    borderRadius: 6,
  },
  lead: {
    marginTop: spacing.sm,
  },
  followers: {
    marginTop: spacing.sm,
    fontWeight: '600',
  },
  bigButton: {
    marginTop: spacing.lg,
  },
  infoBox: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    gap: spacing.sm,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  infoIcon: {
    fontSize: 18,
    width: 24,
    textAlign: 'center',
  },
  map: {
    marginTop: spacing.md,
  },
  liveBox: {
    marginTop: spacing.md,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
  },
  note: {
    marginTop: spacing.sm,
  },
  resume: {
    marginTop: spacing.sm,
    alignSelf: 'flex-start',
  },
  buttons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  mobileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    paddingTop: spacing.md,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
});

export default PresenceCard;

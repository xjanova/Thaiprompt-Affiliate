/**
 * PresenceCard — การ์ดสถานะหน้าร้านตลาดสด (เปิด/ปิด, จุดขายวันนี้, แชร์ตำแหน่งสด)
 *
 * - แผงบนสุด = น้ำเงินกรมท่าลายกนก + วงเรดาร์ภาพร้าน 3D + สวิตช์ใหญ่ (แบบการ์ด "ออนไลน์" ของไรเดอร์)
 *   ร้านเปิด = เรืองเขียว + วงเรดาร์เต้นเบาๆ · ร้านปิด = โทนกระจกเรียบ
 *   สวิตช์ใหญ่เรียก onOpen / onClose ตัวเดียวกับปุ่มด้านล่าง (ใช้ตัวกันกดซ้ำร่วมกัน)
 * - ปิดอยู่: ปุ่มใหญ่สีทอง "เปิดร้านที่นี่วันนี้" + จำนวนผู้ติดตามที่จะได้รับแจ้งเตือน
 * - เปิดอยู่: จุดขาย, เวลาปิดอัตโนมัติ, สถานะการส่งตำแหน่งสด, ย้ายจุดขาย, ปิดร้าน
 * - สลับ "ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)" ได้ที่ท้ายการ์ด
 */

import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Switch, View } from 'react-native';
import Animated, {
  Easing,
  cancelAnimation,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { useFocusEffect } from 'expo-router';
import {
  BrandArt,
  Button3D,
  Card3D,
  Icon,
  LiveMap,
  OnHeaderProvider,
  Pill,
  RoyalHeader,
  usePressGuard,
} from '@/components/ui';
import type { OwnerPresence } from '@/services/api/taladsodSellerApi';
import type { ShopLiveState } from '@/services/taladsodLiveLocation';
import { DARK_THEME, glowStyle, palette, radii, shadowStyle, spacing, typography, useTheme, withAlpha } from '@/theme';
import { clockTh, timeAgoTh } from './fmHelpers';
import { IconTile } from './MerchantUi';

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

/** ขนาดวงเรดาร์ (วงนอก) */
const RADAR = 72;

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
  const [focused, setFocused] = useState(false);
  const reduceMotion = useReducedMotion();

  // นาฬิกาเล็กๆ สำหรับ "ส่งล่าสุด ... ที่แล้ว" — เดินเฉพาะตอนหน้านี้อยู่บนจอ
  useFocusEffect(
    useCallback(() => {
      setNow(Date.now());
      const timer = setInterval(() => setNow(Date.now()), 10_000);
      return () => clearInterval(timer);
    }, [])
  );

  // แอนิเมชันวงเรดาร์เดินเฉพาะตอนหน้านี้อยู่บนจอ (ออกจากหน้า = หยุด ไม่กินแบต)
  useFocusEffect(
    useCallback(() => {
      setFocused(true);
      return () => setFocused(false);
    }, [])
  );

  // ตัวกันกดซ้ำร่วมกันของสวิตช์ใหญ่กับปุ่มเปิด/ปิดร้าน (กดสองที่พร้อมกันไม่ได้)
  const openGuard = usePressGuard(onOpen);
  const closeGuard = usePressGuard(onClose);

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

  // เขียวบนแผงน้ำเงิน (มืดเสมอทั้งสองโหมด) → ใช้เขียวสว่างของชุดสีโหมดมืดให้เรืองชัด
  const liveGreen = DARK_THEME.colors.success;

  // ---------- วงเรดาร์เต้นเมื่อร้านเปิด ----------
  const pulse = useSharedValue(0);
  const animate = open && focused && !reduceMotion;
  useEffect(() => {
    if (animate) {
      pulse.value = 0;
      pulse.value = withRepeat(withTiming(1, { duration: 1800, easing: Easing.out(Easing.quad) }), -1, false);
    } else {
      cancelAnimation(pulse);
      pulse.value = 0;
    }
    return () => cancelAnimation(pulse);
  }, [animate, pulse]);
  const pulseStyle = useAnimatedStyle(() => ({
    opacity: 0.55 * (1 - pulse.value),
    transform: [{ scale: 0.72 + 0.5 * pulse.value }],
  }));

  const ringBase = open ? liveGreen : colors.onHeader;
  const toggleBusy = openGuard.busy || closeGuard.busy;

  return (
    <Card3D
      gradientBorder={open ? gradients.success : true}
      padding={0}
      shadow="md"
      style={open ? glowStyle(liveGreen, 0.55) : undefined}
      contentStyle={styles.clip}
    >
      {/* ---------- แผงสถานะ (น้ำเงินกรมท่า) ---------- */}
      <RoyalHeader ornamentWidth={150} ornamentTop={-34} style={styles.panel}>
        {open && (
          <LinearGradient
            colors={[withAlpha(liveGreen, 0.26), withAlpha(liveGreen, 0.05)]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={StyleSheet.absoluteFill}
            pointerEvents="none"
          />
        )}
        <OnHeaderProvider value>
          <View style={styles.panelRow}>
            {/* วงเรดาร์ + ภาพร้าน 3D */}
            <View style={styles.radar}>
              {open && (
                <Animated.View
                  pointerEvents="none"
                  style={[styles.radarPulse, { backgroundColor: withAlpha(liveGreen, 0.35) }, pulseStyle]}
                />
              )}
              <View style={[styles.radarRing, { backgroundColor: withAlpha(ringBase, open ? 0.1 : 0.05) }]}>
                <View style={[styles.radarCore, { backgroundColor: withAlpha(ringBase, open ? 0.2 : 0.08) }]}>
                  <BrandArt name={presence.is_mobile ? 'cart' : 'store'} size={42} style={!open && styles.artDim} />
                </View>
              </View>
            </View>

            <View style={styles.flex}>
              <Pill
                label={presence.is_mobile ? 'ร้านเคลื่อนที่' : 'ร้านประจำที่'}
                icon={presence.is_mobile ? 'shopping-cart-simple' : 'house'}
                tone={presence.is_mobile ? 'gold' : 'neutral'}
              />
              <Text
                style={[typography.serif, styles.panelTitle, { color: colors.onHeader }]}
                numberOfLines={1}
                adjustsFontSizeToFit
                minimumFontScale={0.75}
              >
                {open ? 'ร้านเปิดอยู่' : 'ร้านปิดอยู่'}
              </Text>
              {open && !!presence.closes_at && (
                <View style={styles.statusLine}>
                  <View style={[styles.liveDot, { backgroundColor: liveGreen }]} />
                  <Text style={[typography.caption, styles.flex, { color: colors.onHeaderMuted }]} numberOfLines={2}>
                    ปิดร้านอัตโนมัติ {clockTh(presence.closes_at)}
                  </Text>
                </View>
              )}
            </View>

            {/* สวิตช์ใหญ่ เปิด/ปิดร้าน */}
            <Pressable
              onPress={open ? closeGuard.run : openGuard.run}
              disabled={switching}
              accessibilityRole="switch"
              accessibilityLabel="เปิด-ปิดร้าน"
              accessibilityHint={open ? 'แตะเพื่อปิดร้าน' : 'แตะเพื่อเปิดร้าน'}
              accessibilityState={{ checked: open, disabled: switching, busy: toggleBusy }}
              hitSlop={10}
              style={({ pressed }) => [{ opacity: switching ? 0.5 : pressed ? 0.8 : 1 }]}
            >
              <View
                style={[
                  styles.track,
                  !open && { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder, borderWidth: 1 },
                ]}
              >
                {open && (
                  <LinearGradient
                    colors={gradients.success}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 0, y: 1 }}
                    style={[StyleSheet.absoluteFill, styles.trackFill]}
                  />
                )}
                <View
                  style={[
                    styles.thumb,
                    { backgroundColor: colors.textOnAccent },
                    shadowStyle('md', DARK_THEME.colors.shadowDark),
                    open ? styles.thumbOn : styles.thumbOff,
                  ]}
                >
                  {toggleBusy && <ActivityIndicator size="small" color={open ? gradients.success[1] : palette.navy800} />}
                </View>
              </View>
            </Pressable>
          </View>
        </OnHeaderProvider>
      </RoyalHeader>

      <View style={styles.body}>
        {!open ? (
          <>
            <Text style={[typography.body, { color: colors.textMuted }]}>
              {needsGps
                ? 'ไปถึงจุดขายแล้วกดเปิดร้าน ลูกค้าใกล้ๆ จะเห็นร้านคุณและสั่งได้ทันที'
                : 'กดเปิดร้านเมื่อพร้อมขาย ลูกค้าจะสั่งได้จนถึงเวลาปิดที่ตั้งไว้'}
            </Text>
            {presence.followers_count > 0 && (
              <View style={[styles.followers, { backgroundColor: colors.goldSoft }]}>
                <Icon name="bell-ringing" size={18} color={colors.goldDeep} weight="fill" />
                <Text style={[typography.bodySm, styles.followersText, { color: colors.goldDeep }]}>
                  ผู้ติดตาม {presence.followers_count.toLocaleString('th-TH')} คนจะได้รับแจ้งเตือนเมื่อร้านเปิด
                </Text>
              </View>
            )}
            <Button3D
              title={needsGps ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้านวันนี้'}
              icon="storefront"
              variant="primary"
              size="lg"
              fullWidth
              loading={openGuard.busy}
              onPress={openGuard.run}
              style={styles.bigButton}
              accessibilityHint={needsGps ? 'ใช้ตำแหน่งปัจจุบันเป็นจุดขายวันนี้' : undefined}
            />
          </>
        ) : (
          <>
            {/* ---------- จุดขาย ---------- */}
            <View style={styles.infoList}>
              <View style={styles.infoRow}>
                <IconTile icon="map-pin" size={40} />
                <View style={styles.flex}>
                  <Text style={[typography.caption, { color: colors.textMuted }]}>จุดขายวันนี้</Text>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={2}>
                    {presence.location_label || loc?.label || (needsGps ? 'ตามตำแหน่งที่ปักไว้' : 'ที่อยู่ร้าน')}
                  </Text>
                </View>
              </View>
              {presence.followers_count > 0 && (
                <View style={[styles.infoRow, styles.infoDivider, { borderTopColor: colors.divider }]}>
                  <IconTile icon="users-three" tone="gold" size={40} />
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
              <View style={[styles.liveBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
                <View style={styles.infoRow}>
                  <IconTile icon="broadcast" tone={presence.live_location_sharing ? 'success' : 'navy'} size={40} />
                  <View style={styles.flex}>
                    <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>แชร์ตำแหน่งสด</Text>
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
                    icon="broadcast"
                    variant="primary"
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
                icon={needsGps ? 'map-pin' : 'clock'}
                variant="secondary"
                size="md"
                onPress={onUpdate}
                style={styles.updateButton}
              />
              <Button3D
                title="ปิดร้าน"
                icon="power"
                variant="danger"
                size="md"
                onPress={closeGuard.run}
                style={styles.flex}
              />
            </View>
            {activeOrders > 0 && (
              <View style={styles.warnRow}>
                <Icon name="warning" size={16} color={colors.warning} weight="fill" style={styles.warnIcon} />
                <Text style={[typography.caption, styles.flex, { color: colors.warning }]}>
                  มีออเดอร์ที่ยังทำไม่เสร็จ {activeOrders} รายการ — ปิดร้านแล้วยังทำต่อได้ แต่ลูกค้าใหม่จะสั่งไม่ได้
                </Text>
              </View>
            )}
          </>
        )}

        {/* ---------- ประเภทร้าน ---------- */}
        <View style={[styles.mobileRow, { borderTopColor: colors.divider }]}>
          <IconTile icon="shopping-cart-simple" tone={presence.is_mobile ? 'gold' : 'navy'} size={40} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
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
      </View>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  clip: {
    overflow: 'hidden',
  },
  // ---------- แผงสถานะ ----------
  panel: {
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
  },
  panelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  radar: {
    width: RADAR,
    height: RADAR,
    alignItems: 'center',
    justifyContent: 'center',
  },
  radarPulse: {
    position: 'absolute',
    width: RADAR,
    height: RADAR,
    borderRadius: RADAR / 2,
  },
  radarRing: {
    width: RADAR,
    height: RADAR,
    borderRadius: RADAR / 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  radarCore: {
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  artDim: {
    opacity: 0.6,
  },
  panelTitle: {
    marginTop: spacing.xs,
  },
  statusLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  liveDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  track: {
    width: 60,
    height: 36,
    borderRadius: 18,
    justifyContent: 'center',
    paddingHorizontal: 3,
    overflow: 'hidden',
  },
  trackFill: {
    borderRadius: 18,
  },
  thumb: {
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumbOn: {
    alignSelf: 'flex-end',
  },
  thumbOff: {
    alignSelf: 'flex-start',
  },
  // ---------- เนื้อหา ----------
  body: {
    padding: spacing.lg,
  },
  followers: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    borderRadius: radii.md,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
  },
  followersText: {
    flex: 1,
    fontWeight: '600',
  },
  bigButton: {
    marginTop: spacing.lg,
  },
  infoList: {
    marginBottom: spacing.xs,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  infoDivider: {
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
  },
  map: {
    marginTop: spacing.lg,
  },
  liveBox: {
    marginTop: spacing.lg,
    borderRadius: radii.lg,
    borderWidth: 1,
    padding: spacing.md,
  },
  note: {
    marginTop: spacing.sm,
  },
  resume: {
    marginTop: spacing.md,
    alignSelf: 'flex-start',
  },
  buttons: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.lg,
  },
  updateButton: {
    flex: 1.5,
  },
  warnRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
    marginTop: spacing.md,
  },
  warnIcon: {
    marginTop: 1,
  },
  mobileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
  },
});

export default PresenceCard;

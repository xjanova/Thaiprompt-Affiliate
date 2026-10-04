/**
 * AppUpdateHost — ตัวคุมการตรวจอัปเดต + หน้าต่างชวนอัปเดต (วางครั้งเดียวใน app/_layout.tsx)
 *
 * ตรวจเมื่อไร
 *   - หลังเปิดแอปไม่กี่วินาที (เบื้องหลัง ไม่มีตัวหมุน ไม่หน่วงการเปิดแอป)
 *   - กลับเข้าแอปเมื่อห่างจากครั้งที่ตรวจสำเร็จเกิน 6 ชั่วโมง · พลาด = เงียบ
 *
 * แสดงอะไร
 *   - อัปเดตทั่วไป → bottom sheet "มีเวอร์ชันใหม่" · "อัปเดตเลย" = ไปหน้าอัปเดต · "ไว้ทีหลัง"/แตะพื้นหลัง/ปุ่มย้อนกลับ
 *     = เลื่อนเวอร์ชันนี้ 24 ชม. (เวอร์ชันใหม่กว่าขึ้นทันที)
 *   - บังคับอัปเดต → หน้าเต็มจอปิดไม่ได้ (ปุ่มย้อนกลับไม่ทำอะไร) มีแค่ "อัปเดตเลย"
 *   - ไม่เด้งทับหน้ากล้องยืนยันตัวตน / ชำระเงิน / สแกนส่งมอบ — รอผู้ใช้ไปหน้าอื่นก่อน (isCalmRoute)
 *   - iOS / เว็บ = ไม่ทำอะไรเลย
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { AppState, Modal, Pressable, ScrollView, StatusBar, StyleSheet, View } from 'react-native';
import { router, usePathname } from 'expo-router';
import Animated, { SlideInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import { Button3D, Icon, OnHeaderProvider, RoyalHeader } from '@/components/ui';
import {
  APP_UPDATE_ROUTE,
  formatMB,
  isCalmRoute,
  MAX_PROMPT_NOTES,
  shouldAutoCheck,
  useAppUpdateStore,
  type AppUpdateLatest,
} from '@/services/appUpdate';
import { radii, shadowStyle, spacing, typography, useTheme, withAlpha } from '@/theme';
import { UpdateGauge } from './UpdateGauge';
import { GlassCard, GlassTag, GoldEmblem, NoteList, TrustLine, VersionJump } from './UpdateParts';

/** รอหลังเปิดแอปก่อนตรวจครั้งแรก (ให้หน้าแรกโหลดของตัวเองก่อน) */
const STARTUP_CHECK_DELAY_MS = 3500;
/** รอให้หน้านิ่งก่อนแสดงหน้าต่าง (กันเด้งระหว่างเปลี่ยนหน้า) */
const ROUTE_SETTLE_MS = 700;

// =====================================================
// bottom sheet "มีเวอร์ชันใหม่"
// =====================================================

const UpdatePromptSheet: React.FC<{
  latest: AppUpdateLatest;
  onUpdate: () => void;
  onLater: () => void;
}> = ({ latest, onUpdate, onLater }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <Modal visible transparent animationType="fade" statusBarTranslucent onRequestClose={onLater}>
      <View style={styles.sheetRoot}>
        <Pressable
          style={[StyleSheet.absoluteFill, { backgroundColor: colors.overlay }]}
          onPress={onLater}
          accessibilityRole="button"
          accessibilityLabel="ไว้ทีหลัง"
        />
        <Animated.View
          entering={SlideInDown.springify().damping(18)}
          accessibilityViewIsModal
          style={[
            styles.sheet,
            { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm },
            shadowStyle('lg', colors.shadowDark),
          ]}
        >
          <RoyalHeader ornamentWidth={170} ornamentTop={-14} style={styles.sheetHead}>
            <OnHeaderProvider value>
              <View style={[styles.handle, { backgroundColor: withAlpha(colors.onHeader, 0.3) }]} />
              <View style={styles.sheetTitleRow}>
                <GoldEmblem icon="download-simple" />
                <View style={styles.flex}>
                  <Text style={[typography.overline, { color: colors.goldLight }]}>อัปเดตแอป Thai Prompt</Text>
                  <Text accessibilityRole="header" style={[typography.serif, { color: colors.onHeader }]}>
                    มีเวอร์ชันใหม่
                  </Text>
                </View>
              </View>
              <View style={styles.tags}>
                <GlassTag icon="seal-check" label={`เวอร์ชัน ${latest.version}`} />
                <GlassTag icon="download-simple" label={formatMB(latest.size)} />
              </View>
            </OnHeaderProvider>
          </RoyalHeader>

          <ScrollView bounces={false} contentContainerStyle={styles.sheetBody}>
            <Text style={[typography.overline, { color: colors.textMuted }]}>มีอะไรใหม่</Text>
            <NoteList notes={latest.notes} max={MAX_PROMPT_NOTES} />
          </ScrollView>

          <View style={styles.sheetButtons}>
            <Button3D title="อัปเดตเลย" icon="download-simple" size="lg" fullWidth onPress={onUpdate} />
            <Button3D title="ไว้ทีหลัง" variant="ghost" size="md" fullWidth onPress={onLater} />
            <TrustLine style={styles.trustGap} />
          </View>
        </Animated.View>
      </View>
    </Modal>
  );
};

// =====================================================
// หน้าบังคับอัปเดต (ปิดไม่ได้)
// =====================================================

const ForcedUpdateScreen: React.FC<{
  latest: AppUpdateLatest;
  installedVersion: string;
  onUpdate: () => void;
}> = ({ latest, installedVersion, onUpdate }) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    // onRequestClose ว่าง = ปุ่มย้อนกลับของเครื่องไม่ปิดหน้านี้
    <Modal visible animationType="fade" statusBarTranslucent onRequestClose={() => {}}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <RoyalHeader ornamentTop={insets.top} ornamentWidth={260} style={styles.forcedRoot}>
        <OnHeaderProvider value>
          <ScrollView
            bounces={false}
            contentContainerStyle={[styles.forcedScroll, { paddingTop: insets.top + spacing.xxl }]}
          >
            <UpdateGauge progress={1} mode="sweep" size={176} accessibilityLabel="ต้องอัปเดตแอป">
              <Icon name="download-simple" size={44} color={colors.goldLight} weight="bold" />
            </UpdateGauge>
            <Text accessibilityRole="header" style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>
              ต้องอัปเดตแอปก่อนใช้งานต่อ
            </Text>
            <Text style={[typography.body, styles.center, { color: colors.onHeaderMuted }]}>
              แอปรุ่นที่ใช้อยู่เลิกรองรับแล้ว อัปเดตเป็นเวอร์ชันใหม่เพื่อใช้งานต่อได้ตามปกติ บัญชีและข้อมูลของคุณยังอยู่ครบ
            </Text>
            <GlassCard style={styles.forcedCard}>
              <View style={styles.rowBetween}>
                <VersionJump from={installedVersion} to={latest.version} onHeader />
                <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>{formatMB(latest.size)}</Text>
              </View>
              <View style={[styles.hr, { backgroundColor: colors.headerGlassBorder }]} />
              <NoteList notes={latest.notes} max={MAX_PROMPT_NOTES} onHeader />
            </GlassCard>
          </ScrollView>
          <View style={[styles.forcedBottom, { paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm }]}>
            <Button3D title="อัปเดตเลย" icon="download-simple" size="lg" fullWidth onPress={onUpdate} />
            <TrustLine onHeader style={styles.trustGap} />
          </View>
        </OnHeaderProvider>
      </RoyalHeader>
    </Modal>
  );
};

// =====================================================
// ตัวคุม
// =====================================================

export const AppUpdateHost: React.FC = () => {
  const pathname = usePathname();
  const supported = useAppUpdateStore((s) => s.supported);
  const info = useAppUpdateStore((s) => s.info);
  const promptCode = useAppUpdateStore((s) => s.promptVersionCode);
  const phase = useAppUpdateStore((s) => s.phase);
  const installedVersion = useAppUpdateStore((s) => s.installedVersion);
  const navAtRef = useRef(0);
  const [settledPath, setSettledPath] = useState<string | null>(null);

  // ตรวจครั้งแรกหลังเปิดแอป
  useEffect(() => {
    if (!supported) return undefined;
    const timer = setTimeout(() => {
      useAppUpdateStore.getState().check().catch(() => {});
    }, STARTUP_CHECK_DELAY_MS);
    return () => clearTimeout(timer);
  }, [supported]);

  // กลับเข้าแอป → ตรวจใหม่ถ้าห่างจากครั้งก่อนเกิน 6 ชั่วโมง
  useEffect(() => {
    if (!supported) return undefined;
    const sub = AppState.addEventListener('change', (next) => {
      if (next !== 'active') return;
      const s = useAppUpdateStore.getState();
      if (!s.checking && shouldAutoCheck(Date.now(), s.lastSuccessAt, s.lastAttemptAt)) {
        s.check().catch(() => {});
      }
    });
    return () => sub.remove();
  }, [supported]);

  // รอหน้านิ่งก่อนแสดง
  useEffect(() => {
    const timer = setTimeout(() => setSettledPath(pathname), ROUTE_SETTLE_MS);
    return () => clearTimeout(timer);
  }, [pathname]);

  const latest = info?.latest ?? null;
  const calm = settledPath === pathname && isCalmRoute(pathname);
  const forced = supported && calm && !!latest && !!info?.updateAvailable && !!info.required;
  const optional =
    supported &&
    calm &&
    !forced &&
    !!latest &&
    !!info?.updateAvailable &&
    !info.required &&
    promptCode === latest.versionCode &&
    phase === 'idle';

  const goUpdate = useCallback(() => {
    const t = Date.now();
    if (t - navAtRef.current < 1200) return;
    navAtRef.current = t;
    const state = useAppUpdateStore.getState();
    if (!state.info?.required) state.dismissPrompt('update').catch(() => {});
    try {
      router.push(APP_UPDATE_ROUTE as never);
    } catch {
      // router ยังไม่พร้อม — กดใหม่ได้
    }
  }, []);

  const later = useCallback(() => {
    useAppUpdateStore.getState().dismissPrompt('later').catch(() => {});
  }, []);

  if (!latest) return null;
  if (forced) return <ForcedUpdateScreen latest={latest} installedVersion={installedVersion} onUpdate={goUpdate} />;
  if (optional) return <UpdatePromptSheet latest={latest} onUpdate={goUpdate} onLater={later} />;
  return null;
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  sheetRoot: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    borderTopLeftRadius: radii.xxl,
    borderTopRightRadius: radii.xxl,
    overflow: 'hidden',
    maxHeight: '90%',
  },
  sheetHead: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
    paddingBottom: spacing.xl,
    gap: spacing.lg,
  },
  handle: {
    alignSelf: 'center',
    width: 44,
    height: 5,
    borderRadius: 3,
  },
  sheetTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.lg,
  },
  tags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  sheetBody: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.xl,
    paddingBottom: spacing.md,
    gap: spacing.md,
  },
  sheetButtons: {
    paddingHorizontal: spacing.xl,
    gap: spacing.sm,
  },
  trustGap: {
    marginTop: spacing.xs,
  },
  forcedRoot: {
    flex: 1,
  },
  forcedScroll: {
    alignItems: 'center',
    paddingHorizontal: spacing.xl,
    paddingBottom: spacing.xl,
    gap: spacing.md,
  },
  forcedCard: {
    alignSelf: 'stretch',
    marginTop: spacing.md,
  },
  rowBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
  },
  hr: {
    height: 1,
  },
  forcedBottom: {
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
    gap: spacing.sm,
  },
});

export default AppUpdateHost;

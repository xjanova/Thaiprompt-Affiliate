/**
 * อัปเดตแอป — ดาวน์โหลด ตรวจไฟล์ และติดตั้งเวอร์ชันใหม่จากเซิร์ฟเวอร์ Thai Prompt โดยตรง (Android)
 *
 * - หัวน้ำเงินลายกนก + เกจทองวงใหญ่ (UpdateGauge) + ความเร็ว/เวลาที่เหลือ + แถบ 3 ขั้น ดาวน์โหลด → ตรวจไฟล์ → ติดตั้ง
 * - การ์ด "มีอะไรใหม่" (เวอร์ชันเดิม → ใหม่ · ขนาด · วันที่เผยแพร่ · รายการ)
 * - เปิดหน้า = ตรวจเวอร์ชันใหม่อีกครั้ง (ยกเว้นกำลังดาวน์โหลด/พร้อมติดตั้งอยู่ ไม่รบกวน)
 * - ดาวน์โหลดอยู่ใน store กลาง → ออกจากหน้าแล้วกลับมา ความคืบหน้ายังวิ่งต่อ
 * - บังคับอัปเดต: ไม่มีปุ่มย้อนกลับ · ปุ่มย้อนกลับของเครื่องไม่ทำอะไร
 * - ติดตั้ง: เปิดตัวติดตั้งของระบบ · กลับมาที่แอป = ยังไม่ได้ติดตั้ง → อยู่ที่ "พร้อมติดตั้ง" กดใหม่ได้
 *   + คำแนะนำเปิด "ติดตั้งแอปที่ไม่รู้จัก" พร้อมปุ่มเปิดหน้าตั้งค่า
 */

import React, { useCallback, useEffect, useRef } from 'react';
import { Alert, BackHandler, ScrollView, StatusBar, StyleSheet, View } from 'react-native';
import { router } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '@/components/ui/Text';
import {
  Button3D,
  Card3D,
  GlassIconButton,
  Icon,
  OnHeaderProvider,
  Pill,
  RoyalHeader,
  resultHaptic,
  usePressGuard,
  type IconName,
} from '@/components/ui';
import { StickyBar } from '@/components/shop';
import { IconTile } from '@/components/profile';
import { UpdateGauge, type GaugeMode } from '@/components/appUpdate/UpdateGauge';
import { PauseGlyph } from '@/components/appUpdate/AppUpdateBadge';
import { NoteList, TrustLine, VersionJump } from '@/components/appUpdate/UpdateParts';
import {
  formatEta,
  formatMB,
  formatProgressMB,
  formatSpeed,
  formatThaiDate,
  progressFraction,
  progressPercent,
  updateStage,
  useAppUpdateStore,
  type UpdateStage,
} from '@/services/appUpdate';
import { FONT, radii, spacing, typography, useTheme } from '@/theme';

const SHEET_RADIUS = 26;
const GAUGE_SIZE = 240;
const STEPS = ['ดาวน์โหลด', 'ตรวจไฟล์', 'ติดตั้ง'] as const;

/** ขั้นปัจจุบันของแถบ 3 ขั้น (0 = ไม่แสดง) */
const stepFor = (stage: UpdateStage): number => {
  switch (stage) {
    case 'available':
    case 'downloading':
    case 'paused':
    case 'error':
      return 1;
    case 'verifying':
      return 2;
    case 'ready':
      return 3;
    default:
      return 0;
  }
};

// =====================================================
// แถบ 3 ขั้น (บนหัวน้ำเงิน)
// =====================================================

const UpdateStepper: React.FC<{ current: number; failed?: boolean }> = ({ current, failed = false }) => {
  const { colors } = useTheme();
  return (
    <View
      style={styles.stepper}
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={`ขั้นที่ ${current} จาก 3 ${STEPS[current - 1] ?? ''}`}
    >
      {STEPS.map((label, index) => {
        const n = index + 1;
        const done = n < current;
        const active = n === current;
        return (
          <React.Fragment key={label}>
            {index > 0 && (
              <View
                style={[styles.stepLine, { backgroundColor: n <= current ? colors.gold : colors.headerGlassBorder }]}
              />
            )}
            <View style={styles.stepItem}>
              <View
                style={[
                  styles.stepDot,
                  done
                    ? { backgroundColor: colors.gold }
                    : active
                      ? { backgroundColor: failed ? colors.danger : colors.onHeader }
                      : { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder, borderWidth: 1 },
                ]}
              >
                {done ? (
                  <Icon name="check" size={14} color={colors.textOnGold} weight="bold" />
                ) : active && failed ? (
                  <Icon name="x" size={13} color={colors.textOnAccent} weight="bold" />
                ) : (
                  <Text style={[typography.micro, { color: active ? colors.navyFill : colors.onHeaderMuted }]}>{n}</Text>
                )}
              </View>
              <Text
                style={[
                  typography.micro,
                  { color: done || active ? colors.onHeader : colors.onHeaderMuted, fontWeight: done || active ? '700' : '600' },
                ]}
              >
                {label}
              </Text>
            </View>
          </React.Fragment>
        );
      })}
    </View>
  );
};

/** ป้ายกระจกใต้เกจ (ความเร็ว / เวลาที่เหลือ / สถานะ) */
const StatusChip: React.FC<{ icon?: IconName; glyph?: React.ReactNode; label: string; tone?: 'normal' | 'gold' | 'danger' }> = ({
  icon,
  glyph,
  label,
  tone = 'normal',
}) => {
  const { colors } = useTheme();
  const danger = tone === 'danger';
  const fg = tone === 'gold' ? colors.goldLight : danger ? colors.danger : colors.onHeader;
  return (
    <View
      style={[
        styles.chip,
        {
          backgroundColor: danger ? colors.dangerSoft : colors.headerGlass,
          borderColor: danger ? colors.dangerSoft : colors.headerGlassBorder,
        },
      ]}
    >
      {glyph ?? (icon ? <Icon name={icon} size={14} color={tone === 'normal' ? colors.goldLight : fg} /> : null)}
      <Text numberOfLines={1} style={[typography.caption, { color: fg, fontVariant: ['tabular-nums'] }]}>
        {label}
      </Text>
    </View>
  );
};

// =====================================================
// หน้าจอ
// =====================================================

export default function AppUpdateScreen() {
  const { colors, gradients } = useTheme();
  const insets = useSafeAreaInsets();

  const stage = useAppUpdateStore((s) => updateStage(s));
  const info = useAppUpdateStore((s) => s.info);
  const phase = useAppUpdateStore((s) => s.phase);
  const target = useAppUpdateStore((s) => s.target);
  const written = useAppUpdateStore((s) => s.written);
  const speedBps = useAppUpdateStore((s) => s.speedBps);
  const eta = useAppUpdateStore((s) => s.etaSeconds);
  const error = useAppUpdateStore((s) => s.error);
  const checkError = useAppUpdateStore((s) => s.checkError);
  const installing = useAppUpdateStore((s) => s.installing);
  const installReturned = useAppUpdateStore((s) => s.installReturned);
  const installError = useAppUpdateStore((s) => s.installError);
  const installedVersion = useAppUpdateStore((s) => s.installedVersion);

  const required = !!info?.required && !!info.updateAvailable;
  const latest = (phase !== 'idle' ? target : null) ?? info?.latest ?? null;
  const total = latest?.size ?? 0;

  // ---------- เปิดหน้า = ตรวจเวอร์ชันอีกครั้ง (ไม่รบกวนดาวน์โหลดที่กำลังวิ่ง) ----------
  useEffect(() => {
    const s = useAppUpdateStore.getState();
    if (!s.supported) return;
    if (s.phase === 'idle' || (s.phase === 'error' && !s.error?.canResume)) {
      s.check({ manual: true }).catch(() => {});
    }
  }, []);

  // ---------- ย้อนกลับ ----------
  const leave = useCallback(() => {
    if (router.canGoBack()) router.back();
    else router.replace('/(tabs)' as never);
  }, []);
  const { run: leaveOnce } = usePressGuard(leave);

  // บังคับอัปเดต: ปุ่มย้อนกลับของเครื่องไม่ทำอะไร
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => required);
    return () => sub.remove();
  }, [required]);

  // ---------- สั่นตอนเปลี่ยนสถานะ ----------
  const prevStageRef = useRef<UpdateStage>(stage);
  useEffect(() => {
    const prev = prevStageRef.current;
    prevStageRef.current = stage;
    if (prev === stage) return;
    if (stage === 'ready') resultHaptic('success');
    else if (stage === 'error') resultHaptic('error');
  }, [stage]);

  // ---------- คำสั่ง ----------
  const store = useAppUpdateStore.getState;
  const startDownload = () => {
    store().startDownload().catch(() => {});
  };
  const resume = () => {
    store().resumeDownload().catch(() => {});
  };
  const pause = () => store().pauseDownload();
  const install = () => store().install();
  const recheck = () => store().check({ manual: true });
  const restartFromCheck = async () => {
    await store().cancelDownload();
    await store().check({ manual: true });
  };
  const askCancel = () => {
    Alert.alert('ยกเลิกการดาวน์โหลด?', 'ไฟล์ที่ดาวน์โหลดไว้จะถูกลบ ต้องเริ่มดาวน์โหลดใหม่ภายหลัง', [
      { text: 'ไม่ยกเลิก', style: 'cancel' },
      {
        text: 'ยกเลิกดาวน์โหลด',
        style: 'destructive',
        onPress: () => {
          store().cancelDownload().catch(() => {});
        },
      },
    ]);
  };

  // ---------- เกจ ----------
  const pct = progressPercent(written, total);
  const fraction = progressFraction(written, total);
  const gaugeMode: GaugeMode = stage === 'checking' ? 'indeterminate' : stage === 'verifying' ? 'sweep' : 'progress';
  const gaugeProgress =
    stage === 'up_to_date' || stage === 'ready' || stage === 'verifying'
      ? 1
      : stage === 'downloading' || stage === 'paused' || stage === 'error'
        ? fraction
        : 0;
  const dimmed = stage === 'paused' || stage === 'error' || stage === 'check_error';

  const percentBlock = (caption: string) => (
    <>
      <View style={styles.percentRow}>
        <Text style={[styles.percent, { color: colors.onHeader }]}>{pct}</Text>
        <Text style={[styles.percentSign, { color: colors.goldLight }]}>%</Text>
      </View>
      <Text style={[typography.caption, styles.tabular, { color: colors.onHeaderMuted }]}>{caption}</Text>
    </>
  );

  const gaugeCenter = (() => {
    switch (stage) {
      case 'checking':
        return (
          <>
            <Icon name="arrows-clockwise" size={34} color={colors.goldLight} weight="bold" />
            <Text style={[typography.h3, styles.centerGap, { color: colors.onHeader }]}>กำลังตรวจสอบ</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>เวอร์ชันล่าสุด</Text>
          </>
        );
      case 'check_error':
        return (
          <>
            <Icon name="wifi-slash" size={36} color={colors.goldLight} />
            <Text style={[typography.h3, styles.centerGap, { color: colors.onHeader }]}>ตรวจสอบไม่สำเร็จ</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>ลองอีกครั้งได้เลย</Text>
          </>
        );
      case 'up_to_date':
        return (
          <>
            <Icon name="seal-check" size={46} color={colors.goldLight} weight="fill" />
            <Text style={[typography.serifSm, styles.centerGap, { color: colors.onHeader }]}>ล่าสุดแล้ว</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>เวอร์ชัน {installedVersion}</Text>
          </>
        );
      case 'available':
        return (
          <>
            <Icon name="download-simple" size={34} color={colors.goldLight} weight="bold" />
            <Text style={[typography.serif, styles.centerGap, { color: colors.onHeader }]}>{latest?.version ?? ''}</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>{formatMB(total)}</Text>
          </>
        );
      case 'downloading':
      case 'paused':
        return percentBlock(formatProgressMB(written, total));
      case 'error':
        return written > 0 ? (
          percentBlock(formatProgressMB(written, total))
        ) : (
          <>
            <Icon name="warning-circle" size={40} color={colors.goldLight} />
            <Text style={[typography.h3, styles.centerGap, { color: colors.onHeader }]}>ยังไม่สำเร็จ</Text>
          </>
        );
      case 'verifying':
        return (
          <>
            <Icon name="shield-check" size={42} color={colors.goldLight} weight="fill" />
            <Text style={[typography.h3, styles.centerGap, { color: colors.onHeader }]}>กำลังตรวจไฟล์</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>{formatMB(total)}</Text>
          </>
        );
      case 'ready':
        return (
          <>
            <Icon name="check-circle" size={46} color={colors.goldLight} weight="fill" />
            <Text style={[typography.serifSm, styles.centerGap, { color: colors.onHeader }]}>พร้อมติดตั้ง</Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>เวอร์ชัน {latest?.version ?? ''}</Text>
          </>
        );
      default:
        return (
          <>
            <Icon name="device-mobile" size={40} color={colors.goldLight} />
            <Text style={[typography.h3, styles.centerGap, { color: colors.onHeader }]}>เฉพาะ Android</Text>
          </>
        );
    }
  })();

  const gaugeLabel =
    stage === 'downloading' || stage === 'paused'
      ? `ดาวน์โหลดแล้ว ${pct} เปอร์เซ็นต์ ${formatProgressMB(written, total)}`
      : stage === 'verifying'
        ? 'กำลังตรวจความถูกต้องของไฟล์'
        : stage === 'ready'
          ? 'ดาวน์โหลดและตรวจไฟล์เรียบร้อย พร้อมติดตั้ง'
          : stage === 'up_to_date'
            ? `แอปเป็นเวอร์ชันล่าสุดแล้ว ${installedVersion}`
            : 'สถานะการอัปเดต';

  // ---------- แถบสถานะใต้เกจ ----------
  const statusChips = (() => {
    switch (stage) {
      case 'downloading': {
        const speed = formatSpeed(speedBps);
        return (
          <>
            <StatusChip icon="speedometer" label={speed || 'กำลังเริ่ม'} />
            <StatusChip icon="clock" label={formatEta(eta)} />
          </>
        );
      }
      case 'paused':
        return <StatusChip glyph={<PauseGlyph size={13} color={colors.goldLight} />} label="หยุดชั่วคราว" tone="gold" />;
      case 'verifying':
        return <StatusChip icon="shield-check" label="กำลังตรวจความถูกต้องของไฟล์" />;
      case 'ready':
        return <StatusChip icon="seal-check" label="ไฟล์ถูกต้อง พร้อมติดตั้ง" tone="gold" />;
      case 'error':
        return (
          <StatusChip
            icon="warning-circle"
            label={error?.kind === 'network' ? 'การเชื่อมต่อขาดหาย' : 'ดาวน์โหลดไม่สำเร็จ'}
            tone="danger"
          />
        );
      case 'available':
        return <StatusChip icon="wifi-high" label="แนะนำให้ใช้ Wi-Fi ประหยัดเน็ตมือถือ" />;
      case 'up_to_date':
        return <StatusChip icon="seal-check" label={`Thai Prompt APP เวอร์ชัน ${installedVersion}`} tone="gold" />;
      case 'checking':
        return <StatusChip icon="globe" label="กำลังเชื่อมต่อเซิร์ฟเวอร์" />;
      default:
        return null;
    }
  })();

  // ---------- ปุ่มท้ายจอ ----------
  const bottom = (() => {
    switch (stage) {
      case 'checking':
        return <Button3D title="กำลังตรวจสอบ" size="lg" fullWidth loading />;
      case 'check_error':
        return <Button3D title="ตรวจสอบอีกครั้ง" icon="arrows-clockwise" size="lg" fullWidth onPress={recheck} />;
      case 'up_to_date':
        return (
          <View style={styles.row}>
            <Button3D title="ตรวจอีกครั้ง" variant="secondary" size="lg" onPress={recheck} />
            <Button3D title="เรียบร้อย" size="lg" onPress={leaveOnce} style={styles.flex} />
          </View>
        );
      case 'available':
        return (
          <Button3D title="ดาวน์โหลดและติดตั้ง" icon="download-simple" size="lg" fullWidth onPress={startDownload} />
        );
      case 'downloading':
        return (
          <View style={styles.row}>
            <Button3D title="ยกเลิก" variant="secondary" size="lg" onPress={askCancel} />
            <Button3D
              title="หยุดชั่วคราว"
              variant="navy"
              size="lg"
              icon={<PauseGlyph color={colors.goldLight} />}
              onPress={pause}
              style={styles.flex}
            />
          </View>
        );
      case 'paused':
        return (
          <View style={styles.row}>
            <Button3D title="ยกเลิก" variant="secondary" size="lg" onPress={askCancel} />
            <Button3D title="ดาวน์โหลดต่อ" icon="download-simple" size="lg" onPress={resume} style={styles.flex} />
          </View>
        );
      case 'verifying':
        return <Button3D title="กำลังตรวจไฟล์" size="lg" fullWidth loading />;
      case 'ready':
        return (
          <Button3D
            title="ติดตั้งเลย"
            icon="package"
            size="lg"
            fullWidth
            loading={installing}
            loadingText="กำลังเปิดตัวติดตั้ง"
            onPress={install}
          />
        );
      case 'error':
        if (error?.canResume) {
          return (
            <View style={styles.row}>
              <Button3D title="ยกเลิก" variant="secondary" size="lg" onPress={askCancel} />
              <Button3D title="ดาวน์โหลดต่อ" icon="arrows-clockwise" size="lg" onPress={resume} style={styles.flex} />
            </View>
          );
        }
        if (error?.kind === 'not_found' || error?.kind === 'untrusted') {
          return (
            <Button3D title="ตรวจสอบอีกครั้ง" icon="arrows-clockwise" size="lg" fullWidth onPress={restartFromCheck} />
          );
        }
        return <Button3D title="ดาวน์โหลดใหม่" icon="arrows-clockwise" size="lg" fullWidth onPress={startDownload} />;
      default:
        return <Button3D title="กลับ" size="lg" fullWidth onPress={leaveOnce} />;
    }
  })();

  const step = stepFor(stage);
  const showWhatsNew =
    !!latest && (stage === 'available' || stage === 'downloading' || stage === 'paused' || stage === 'verifying' || stage === 'ready' || stage === 'error');
  const errorText = stage === 'error' ? error?.message : stage === 'check_error' ? checkError : null;
  const showInstallHelp = stage === 'ready' && (installReturned || !!installError);
  const published = formatThaiDate(latest?.publishedAt);

  return (
    <View style={[styles.root, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        <RoyalHeader
          ornamentTop={insets.top - 18}
          ornamentWidth={230}
          style={{ paddingTop: insets.top + spacing.xs, paddingBottom: SHEET_RADIUS + spacing.xl }}
        >
          <OnHeaderProvider value>
            <View style={styles.headerRow}>
              {!required && (
                <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={leaveOnce} />
              )}
              <View style={[styles.flex, required && styles.titleNoBack]}>
                <Text accessibilityRole="header" numberOfLines={1} style={[typography.serif, { color: colors.onHeader }]}>
                  อัปเดตแอป
                </Text>
                <Text numberOfLines={1} style={[typography.bodySm, { color: colors.onHeaderMuted }]}>
                  {required ? 'ต้องอัปเดตก่อนใช้งานต่อ' : 'Thai Prompt APP'}
                </Text>
              </View>
            </View>

            <View style={styles.gaugeWrap}>
              <UpdateGauge
                progress={gaugeProgress}
                mode={gaugeMode}
                dimmed={dimmed}
                size={GAUGE_SIZE}
                accessibilityLabel={gaugeLabel}
              >
                {gaugeCenter}
              </UpdateGauge>
            </View>

            {statusChips ? <View style={styles.chips}>{statusChips}</View> : null}
            {step > 0 ? <UpdateStepper current={step} failed={stage === 'error'} /> : null}
          </OnHeaderProvider>
        </RoyalHeader>

        <View style={[styles.sheet, { backgroundColor: colors.background, paddingBottom: 132 + insets.bottom }]}>
          {required && (
            <View style={[styles.notice, { backgroundColor: colors.warningSoft }]}>
              <Icon name="warning" size={18} color={colors.warning} />
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
                แอปรุ่นที่ใช้อยู่เลิกรองรับแล้ว ต้องอัปเดตเป็นเวอร์ชันใหม่ก่อนใช้งานต่อ บัญชีและข้อมูลของคุณยังอยู่ครบ
              </Text>
            </View>
          )}

          {!!errorText && (
            <View style={[styles.notice, { backgroundColor: error?.kind === 'network' ? colors.infoSoft : colors.dangerSoft }]}>
              <Icon
                name={error?.kind === 'network' || stage === 'check_error' ? 'wifi-slash' : 'warning-circle'}
                size={18}
                color={error?.kind === 'network' ? colors.info : colors.danger}
              />
              <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>{errorText}</Text>
            </View>
          )}

          {showInstallHelp && (
            <Card3D padding={spacing.lg} radius={radii.xl}>
              <View style={styles.helpHead}>
                <IconTile icon="gear-six" tone={installError ? 'danger' : 'gold'} size={40} />
                <View style={styles.flex}>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>
                    {installError ? 'เปิดตัวติดตั้งไม่สำเร็จ' : 'ยังติดตั้งไม่เสร็จ?'}
                  </Text>
                  <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                    {installError ||
                      'ถ้าเครื่องแจ้งว่าไม่อนุญาตให้ติดตั้ง ให้เปิด "ติดตั้งแอปที่ไม่รู้จัก" ให้ Thai Prompt APP แล้วกลับมากด "ติดตั้งเลย" อีกครั้ง'}
                  </Text>
                </View>
              </View>
              <Button3D
                title="เปิดการตั้งค่า"
                icon="gear-six"
                variant="secondary"
                size="md"
                fullWidth
                onPress={() => store().openInstallSettings()}
                style={styles.helpButton}
              />
            </Card3D>
          )}

          {showWhatsNew && latest && (
            <Card3D padding={spacing.lg} radius={radii.xl}>
              <View style={styles.cardHead}>
                <IconTile icon="sparkle" tone="gold" size={40} />
                <Text accessibilityRole="header" style={[typography.h2, styles.flex, { color: colors.textStrong }]}>
                  มีอะไรใหม่
                </Text>
                <Pill label={`v${latest.version}`} tone="gold" />
              </View>
              <View style={[styles.versionBox, { backgroundColor: colors.inset, borderColor: colors.border }]}>
                <VersionJump from={installedVersion} to={latest.version} />
                <View style={styles.metaRow}>
                  <View style={styles.meta}>
                    <Icon name="download-simple" size={14} color={colors.textMuted} />
                    <Text style={[typography.caption, { color: colors.textMuted }]}>ขนาด {formatMB(latest.size)}</Text>
                  </View>
                  {!!published && (
                    <View style={styles.meta}>
                      <Icon name="calendar-blank" size={14} color={colors.textMuted} />
                      <Text style={[typography.caption, { color: colors.textMuted }]}>เผยแพร่ {published}</Text>
                    </View>
                  )}
                </View>
              </View>
              <NoteList notes={latest.notes} />
            </Card3D>
          )}

          {stage === 'up_to_date' && (
            <Card3D padding={spacing.lg} radius={radii.xl}>
              <View style={styles.cardHead}>
                <IconTile icon="seal-check" tone="gold" size={40} />
                <View style={styles.flex}>
                  <Text style={[typography.h3, { color: colors.textStrong }]}>แอปเป็นเวอร์ชันล่าสุดแล้ว</Text>
                  <Text style={[typography.bodySm, { color: colors.textMuted }]}>
                    Thai Prompt APP เวอร์ชัน {installedVersion} · มีเวอร์ชันใหม่เมื่อไร แอปจะแจ้งให้ทราบ
                  </Text>
                </View>
              </View>
            </Card3D>
          )}

          {stage === 'unsupported' && (
            <Card3D padding={spacing.lg} radius={radii.xl}>
              <Text style={[typography.body, { color: colors.text }]}>
                การอัปเดตภายในแอปใช้ได้บน Android เท่านั้น
              </Text>
            </Card3D>
          )}

          <TrustLine style={styles.trust} />
        </View>
      </ScrollView>

      <StickyBar>{bottom}</StickyBar>
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  scrollContent: {
    flexGrow: 1,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  titleNoBack: {
    paddingLeft: spacing.xs,
  },
  gaugeWrap: {
    alignItems: 'center',
    marginTop: spacing.lg,
  },
  percentRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  percent: {
    fontFamily: FONT.serif,
    fontSize: 54,
    lineHeight: 66,
    fontWeight: '700',
    fontVariant: ['tabular-nums'],
    letterSpacing: -1,
  },
  percentSign: {
    fontFamily: FONT.serif,
    fontSize: 22,
    lineHeight: 34,
    fontWeight: '700',
    marginLeft: 2,
    marginTop: 6,
  },
  tabular: {
    fontVariant: ['tabular-nums'],
  },
  centerGap: {
    marginTop: spacing.xs,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.screen,
    marginTop: spacing.md,
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: radii.pill,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 6,
    maxWidth: '100%',
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'center',
    paddingHorizontal: spacing.xxl,
    marginTop: spacing.xl,
  },
  stepItem: {
    alignItems: 'center',
    gap: 4,
    minWidth: 64,
  },
  stepLine: {
    flex: 1,
    height: 2,
    borderRadius: 1,
    marginTop: 12,
    maxWidth: 72,
  },
  stepDot: {
    width: 26,
    height: 26,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheet: {
    flex: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xl,
    gap: spacing.lg,
  },
  notice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: 16,
    padding: spacing.md,
  },
  helpHead: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  helpButton: {
    marginTop: spacing.md,
  },
  cardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  versionBox: {
    marginTop: spacing.md,
    marginBottom: spacing.lg,
    borderRadius: radii.md,
    borderWidth: 1,
    padding: spacing.md,
    gap: spacing.sm,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.lg,
  },
  meta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  trust: {
    marginTop: spacing.xs,
  },
});

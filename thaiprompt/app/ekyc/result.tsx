/**
 * ผลยืนยันตัวตน — ตามแบบ Success.png / Pending.png (+ ถ่ายใหม่ / ไม่ผ่าน ในโครงเดียวกัน)
 *
 * แหล่งข้อมูล: ผลตัดสินที่เพิ่งได้ (ekycStore.decision) → ไม่มี (เปิดจาก push kyc_result / เปิดแอปใหม่) = GET /ekyc/status
 *   approved → สำเร็จ + ป้ายทอง · review/pending → ส่งเจ้าหน้าที่ (ถ่ายหน้าใหม่ได้ถ้ายังมีสิทธิ์วันนี้)
 *   retake → บอกสิ่งที่ต้องแก้ + ถ่ายใหม่ (บัตรหรือใบหน้าตามเหตุผล) · rejected → ไม่ผ่าน ติดต่อทีมงาน
 * ปุ่ม "กลับไป…" ปิดขั้นตอนทั้งชุดกลับหน้าที่พามา (useEkycExit) · ไม่ setState หลังออกจากหน้า
 */

import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, BackHandler, StyleSheet, View } from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Screen, resultHaptic, type IconName } from '@/components/ui';
import { CheckRow, EkycShell, InfoNote, useEkycExit, type CheckState } from '@/components/ekyc/EkycKit';
import { VerifiedBadge } from '@/components/people/VerifiedBadge';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { isCardReason, reasonText, retakeTarget } from '@/services/api/ekycApi';
import { isKycGateContext, KYC_GATE_COPY } from '@/services/ekyc/kycGate';
import { restartEkycSession } from '@/services/ekyc/flow';
import { radii, spacing, typography, useTheme } from '@/theme';

type View_ = 'loading' | 'success' | 'pending' | 'retake' | 'rejected' | 'none';

const LIVENESS_REASONS = (r: string) =>
  r.startsWith('CHALLENGE_FAILED') || r === 'SPOOF_SUSPECTED' || r === 'NO_FACE' || r === 'MULTIPLE_FACES';

/** คำอธิบายสั้นใต้เหตุผล */
const REASON_HELP: Record<string, string> = {
  LOW_MATCH: 'รูปในบัตรอาจเก่า หรือแสงตอนถ่ายหน้าน้อยไป',
  DIFFERENT_PEOPLE: 'ถ่ายใหม่ให้เห็นใบหน้าคุณคนเดียวตลอดทุกท่า',
  SPOOF_SUSPECTED: 'ถ่ายจากใบหน้าจริงเท่านั้น ห้ามถ่ายจากหน้าจอหรือรูป',
  BLURRY: 'ถือให้นิ่ง ในที่สว่าง',
  GLARE: 'เลี่ยงไฟส่องตรงบัตร',
  CARD_INCOMPLETE: 'ให้เห็นบัตรครบทั้งใบในกรอบ',
  OCR_LOW: 'ถ่ายให้ชัด ตัวอักษรไม่เบลอ',
  DUPLICATE_ID: 'เจ้าหน้าที่จะตรวจสอบความเป็นเจ้าของบัตร',
  USER_CORRECTED: 'เจ้าหน้าที่ตรวจทานข้อมูลที่แก้',
  AI_UNAVAILABLE: 'ไม่ต้องทำอะไรเพิ่ม รอผลได้เลย',
};

export default function EkycResultScreen() {
  useSensitiveScreen('ekyc-result');
  const { colors, isDark } = useTheme();
  const params = useLocalSearchParams<{ from?: string }>();
  const mountedRef = useMountedRef();
  const exit = useEkycExit();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const decision = useEkycStore((s) => s.decision);
  const status = useEkycStore((s) => s.status);
  const storeFrom = useEkycStore((s) => s.from);
  const startedAt = useEkycStore((s) => s.startedAt);
  const [loaded, setLoaded] = useState(!!decision);
  const [retrying, setRetrying] = useState(false);
  const retryingRef = useRef(false);

  const from = storeFrom ?? (isKycGateContext(params.from) ? params.from : 'profile');
  const copy = KYC_GATE_COPY[from];

  // สถานะล่าสุดเสมอ (ผลจาก push / เจ้าหน้าที่ตัดสินแล้ว)
  useEffect(() => {
    if (!isAuthenticated) return;
    useEkycStore
      .getState()
      .loadStatus(true)
      .finally(() => {
        if (mountedRef.current) setLoaded(true);
      });
  }, [isAuthenticated, mountedRef]);

  // ปุ่มย้อนกลับของเครื่อง = ออกจากขั้นตอนทั้งชุด
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      exit();
      return true;
    });
    return () => sub.remove();
  }, [exit]);

  // ---------- ตัดสินว่าจะแสดงแบบไหน ----------
  const view: View_ = (() => {
    if (decision) {
      // เจ้าหน้าที่อนุมัติหลังจากนั้นแล้ว → สำเร็จ
      if (status?.verified) return 'success';
      switch (decision.decision) {
        case 'approved':
          return 'success';
        case 'review':
          return 'pending';
        case 'retake':
          return 'retake';
        default:
          return 'rejected';
      }
    }
    if (!loaded && !status) return 'loading';
    if (!status) return 'none';
    if (status.verified) return 'success';
    if (status.kyc_status === 'pending') return 'pending';
    if (status.kyc_status === 'rejected') return 'rejected';
    if (status.last_decision === 'retake') return 'retake';
    return 'none';
  })();

  const reasons = decision?.reasons ?? status?.reasons ?? [];
  const attemptsLeft = decision?.attempts_left ?? status?.attempts_left ?? 0;
  const canRetry = attemptsLeft > 0 && (status ? status.can_start || !!decision : true);
  const seconds = decision && startedAt ? Math.max(1, Math.round((Date.now() - startedAt) / 1000)) : null;

  useEffect(() => {
    if (view === 'success') resultHaptic('success');
  }, [view]);

  const retry = async (target: 'card' | 'face') => {
    if (retryingRef.current) return;
    retryingRef.current = true;
    setRetrying(true);
    const outcome = await restartEkycSession(target);
    retryingRef.current = false;
    if (!mountedRef.current) return;
    setRetrying(false);
    if (outcome.kind === 'error') {
      resultHaptic('error');
      if (outcome.code === 'EKYC_TOO_MANY_ATTEMPTS' || outcome.code === 'EKYC_ALREADY_VERIFIED') {
        useEkycStore.getState().setDecision(null);
        useEkycStore.getState().loadStatus(true);
      }
      Alert.alert('เริ่มใหม่ไม่สำเร็จ', outcome.message);
      return;
    }
    useEkycStore.getState().setDecision(null);
    router.replace((outcome.kind === 'face' ? '/ekyc/face' : '/ekyc/capture') as never);
  };

  // ---------- หัว ----------
  const heroCircle = (icon: IconName, tone: 'gold' | 'warn' | 'danger') => (
    <View
      style={[
        styles.heroCircle,
        {
          backgroundColor: colors.headerGlass,
          borderColor: tone === 'danger' ? colors.danger : tone === 'warn' ? colors.warning : colors.gold,
        },
      ]}
    >
      <Icon name={icon} size={40} color={tone === 'danger' ? colors.danger : colors.goldLight} />
    </View>
  );

  const heroBlock = (art: React.ReactNode, title: string, subtitle: string) => (
    <View style={styles.hero}>
      {art}
      <Text style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>{title}</Text>
      <Text style={[typography.bodySm, styles.center, { color: colors.onHeaderMuted }]}>{subtitle}</Text>
    </View>
  );

  // ยังไม่เข้าสู่ระบบ (เปิดจากแจ้งเตือนหลังออกจากระบบ) → ให้เข้าสู่ระบบก่อน ไม่หมุนโหลดค้าง
  if (!isAuthenticated) {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <EmptyState icon="user-circle" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  if (view === 'loading') {
    return (
      <EkycShell title="ยืนยันตัวตน" onBack={exit}>
        <View style={styles.loading}>
          <ActivityIndicator size="large" color={colors.gold} />
          <Text style={[typography.body, { color: colors.textMuted }]}>กำลังโหลดสถานะ…</Text>
        </View>
      </EkycShell>
    );
  }

  // ---------- สำเร็จ ----------
  if (view === 'success') {
    const byAi = decision?.decision === 'approved' || status?.method === 'ekyc';
    const subtitle = [byAi ? 'AI อนุมัติอัตโนมัติ' : 'เจ้าหน้าที่ตรวจสอบแล้ว', seconds ? `ใช้เวลา ${seconds} วินาที` : null]
      .filter(Boolean)
      .join(' · ');
    return (
      <EkycShell
        title="ยืนยันตัวตน"
        onBack={exit}
        hero={heroBlock(<VerifiedBadge size={92} style={styles.seal} />, 'ยืนยันตัวตนสำเร็จ', subtitle)}
        bottom={<Button3D title={copy.returnLabel} size="lg" fullWidth onPress={exit} />}
      >
        <Card3D padding={spacing.lg} radius={22}>
          <Text style={[typography.h2, styles.cardTitle, { color: colors.textStrong }]}>ตอนนี้คุณทำสิ่งเหล่านี้ได้แล้ว</Text>
          <CheckRow first state="done" title="สั่งซื้อสินค้าและอาหาร" subtitle="ทั้งร้านค้าและตลาดสด" />
          <CheckRow state="done" title="สมัครเป็นไรเดอร์" subtitle="ยังต้องส่งใบขับขี่และทะเบียนรถเพิ่ม" />
          <CheckRow state="done" title="สมัครเปิดร้าน" subtitle="ข้อมูลบัตรใช้ต่อได้เลย ไม่ต้องกรอกซ้ำ" />
        </Card3D>
        <View style={[styles.goldBox, { backgroundColor: colors.goldSoft, borderColor: isDark ? colors.goldDeep : colors.gold }]}>
          <VerifiedBadge size={30} />
          <View style={styles.flex}>
            <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>ป้ายทองข้างชื่อคุณ</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              คนอื่นจะเห็นว่าบัญชีนี้ยืนยันตัวตนแล้ว ส่วนรูปโปรไฟล์ยังเป็นรูปที่คุณเลือกเอง
            </Text>
          </View>
        </View>
      </EkycShell>
    );
  }

  // ---------- ส่งเจ้าหน้าที่ ----------
  if (view === 'pending') {
    const cardIssues = reasons.filter(isCardReason);
    const liveIssues = reasons.filter(LIVENESS_REASONS);
    const others = reasons.filter((r) => !isCardReason(r) && !LIVENESS_REASONS(r) && reasonText(r));
    const rowState = (issues: string[]): CheckState => (issues.length > 0 ? 'warn' : 'done');
    return (
      <EkycShell
        title="ยืนยันตัวตน"
        onBack={exit}
        hero={heroBlock(heroCircle('hourglass', 'gold'), 'ส่งให้เจ้าหน้าที่ตรวจแล้ว', 'ปกติไม่เกิน 1 ชั่วโมงในเวลาทำการ · แจ้งผลทางการแจ้งเตือน')}
        bottom={
          <View style={styles.row}>
            {canRetry && (
              <Button3D title="ถ่ายหน้าใหม่" variant="secondary" size="lg" loading={retrying} onPress={() => retry('face')} />
            )}
            <Button3D title="รอเจ้าหน้าที่" variant="navy" size="lg" onPress={exit} style={styles.flex} />
          </View>
        }
      >
        <Card3D padding={spacing.lg} radius={22}>
          <Text style={[typography.h2, styles.cardTitle, { color: colors.textStrong }]}>
            {reasons.length > 0 ? 'AI ยังไม่มั่นใจพอ เพราะ' : 'เจ้าหน้าที่กำลังตรวจสอบ'}
          </Text>
          <CheckRow
            first
            state={rowState(cardIssues)}
            title="อ่านบัตรและบัตรของจริง"
            subtitle={cardIssues.length > 0 ? reasonText(cardIssues[0]) ?? undefined : 'ผ่าน'}
          />
          <CheckRow
            state={rowState(liveIssues)}
            title="เป็นคนจริง"
            subtitle={liveIssues.length > 0 ? reasonText(liveIssues[0]) ?? undefined : 'ผ่าน'}
          />
          {others.map((r) => (
            <CheckRow key={r} state="warn" title={reasonText(r) as string} subtitle={REASON_HELP[r]} />
          ))}
        </Card3D>
        {canRetry && (
          <InfoNote icon="lightning" title="อยากได้ผลเร็วกว่านี้?">
            {`ถ่ายใบหน้าใหม่ในที่สว่าง ถอดหมวกและแว่น AI จะตรวจซ้ำให้ทันที (ทำได้อีก ${attemptsLeft} ครั้งวันนี้)`}
          </InfoNote>
        )}
      </EkycShell>
    );
  }

  // ---------- ถ่ายใหม่ ----------
  if (view === 'retake') {
    const target = retakeTarget(reasons);
    const texts = reasons.map((r) => ({ code: r, text: reasonText(r) })).filter((r) => !!r.text);
    return (
      <EkycShell
        title="ยืนยันตัวตน"
        onBack={exit}
        hero={heroBlock(
          heroCircle(target === 'card' ? 'identification-card' : 'scan-smiley', 'warn'),
          'ยังยืนยันไม่ผ่าน ลองอีกครั้งนะ',
          canRetry ? `ลองได้อีก ${attemptsLeft} ครั้งวันนี้` : 'วันนี้ลองครบแล้ว เจ้าหน้าที่จะตรวจให้'
        )}
        bottom={
          <View style={styles.row}>
            <Button3D title="ไว้ก่อน" variant="secondary" size="lg" onPress={exit} />
            {canRetry && (
              <Button3D
                title={target === 'card' ? 'ถ่ายบัตรใหม่' : 'ถ่ายหน้าใหม่'}
                icon="camera"
                size="lg"
                loading={retrying}
                onPress={() => retry(target)}
                style={styles.flex}
              />
            )}
          </View>
        }
      >
        <Card3D padding={spacing.lg} radius={22}>
          <Text style={[typography.h2, styles.cardTitle, { color: colors.textStrong }]}>สิ่งที่ต้องแก้</Text>
          {(texts.length > 0 ? texts : [{ code: 'GENERIC', text: 'ภาพยังไม่ชัดพอให้ AI ตรวจ' }]).map((r, index) => (
            <CheckRow key={r.code} first={index === 0} state="fail" title={r.text as string} subtitle={REASON_HELP[r.code]} />
          ))}
        </Card3D>
        <InfoNote icon="info" title="เคล็ดลับให้ผ่านในครั้งเดียว">
          ถ่ายในที่สว่าง ถอดหมวก แว่นดำ และหน้ากาก ทำท่าช้าๆ ตามคำสั่ง ให้เห็นใบหน้าคุณคนเดียวตลอด
        </InfoNote>
      </EkycShell>
    );
  }

  // ---------- ไม่ผ่าน / ยังไม่เริ่ม ----------
  const rejected = view === 'rejected';
  const texts = reasons.map(reasonText).filter((t): t is string => !!t);
  return (
    <EkycShell
      title="ยืนยันตัวตน"
      onBack={exit}
      hero={heroBlock(
        heroCircle(rejected ? 'x-circle' : 'identification-card', rejected ? 'danger' : 'gold'),
        rejected ? 'ยืนยันตัวตนไม่ผ่าน' : 'ยังไม่ได้ยืนยันตัวตน',
        rejected ? 'ติดต่อทีมงานได้ที่หน้าช่วยเหลือ' : 'ใช้เวลาประมาณ 1 นาที ทำครั้งเดียว'
      )}
      bottom={
        <View style={styles.row}>
          {rejected && <Button3D title="ติดต่อทีมงาน" variant="secondary" size="lg" onPress={() => router.push('/support' as never)} />}
          {(!rejected || status?.can_start) && (
            <Button3D
              title={rejected ? 'ลองใหม่' : 'เริ่มยืนยันตัวตน'}
              size="lg"
              onPress={() => router.replace(`/ekyc?from=${from}` as never)}
              style={styles.flex}
            />
          )}
          {rejected && !status?.can_start && <Button3D title="ปิด" variant="navy" size="lg" onPress={exit} style={styles.flex} />}
        </View>
      }
    >
      {texts.length > 0 && (
        <Card3D padding={spacing.lg} radius={22}>
          <Text style={[typography.h2, styles.cardTitle, { color: colors.textStrong }]}>เหตุผล</Text>
          {texts.map((t, index) => (
            <CheckRow key={t} first={index === 0} state="fail" title={t} />
          ))}
        </Card3D>
      )}
      <InfoNote icon="lock">รูปบัตรและรูปใบหน้าเข้ารหัสเก็บไว้ ไม่แสดงให้ใครเห็น นอกจากเจ้าหน้าที่เมื่อต้องตรวจสอบ</InfoNote>
    </EkycShell>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  hero: {
    alignItems: 'center',
    gap: spacing.xs,
    paddingBottom: spacing.sm,
  },
  heroCircle: {
    width: 88,
    height: 88,
    borderRadius: 44,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.md,
  },
  seal: {
    marginBottom: spacing.md,
  },
  center: {
    textAlign: 'center',
  },
  cardTitle: {
    marginBottom: spacing.xs,
  },
  goldBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: radii.xl,
    borderWidth: 1,
    padding: spacing.lg,
  },
  loading: {
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.xxxl,
  },
});

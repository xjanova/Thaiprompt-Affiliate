/**
 * ผลยืนยันตัวตน — ตามแบบ Success.png / Pending.png (+ ถ่ายใหม่ / ไม่ผ่าน ในโครงเดียวกัน)
 *
 * แหล่งข้อมูล: ผลตัดสินที่เพิ่งได้ (ekycStore.decision) → ไม่มี (เปิดจาก push kyc_result / เปิดแอปใหม่) = GET /ekyc/status
 *   approved → สำเร็จ + ป้ายทอง · review/pending → ส่งเจ้าหน้าที่ (ถ่ายหน้าใหม่ได้เฉพาะเมื่อ server บอก can_retake)
 *   retake → บอกสิ่งที่ต้องแก้ + ถ่ายใหม่ (บัตรหรือใบหน้าตามเหตุผล) · rejected → ไม่ผ่าน ติดต่อทีมงาน
 *   server ยังตรวจคำขอก่อนหน้า (status.processing) → ถามสถานะซ้ำทุก 3 วินาที (สูงสุด 60 วินาที) ออกจากหน้าได้ตลอด
 * เหตุผลใช้ข้อความไทยจาก server (reason_texts) ก่อนเสมอ · แถวเหตุผลเป็นสีเหลือง/แดงตามระดับ ไม่มีติ๊กเขียว
 * ข้อความจากเจ้าหน้าที่ (message) แสดงในกล่องแยก
 * ปุ่ม "กลับไป…" ปิดขั้นตอนทั้งชุดกลับหน้าที่พามา (useEkycExit) · ไม่ setState หลังออกจากหน้า
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, BackHandler, StyleSheet, View } from 'react-native';
import { router, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Screen, resultHaptic, type IconName } from '@/components/ui';
import { CheckRow, EkycShell, InfoNote, useEkycExit } from '@/components/ekyc/EkycKit';
import { useTimers } from '@/components/ekyc/cameraKit';
import { VerifiedBadge } from '@/components/people/VerifiedBadge';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { getEkycStatus, retakeTarget, type EkycReasonItem } from '@/services/api/ekycApi';
import { isKycGateContext, KYC_GATE_COPY } from '@/services/ekyc/kycGate';
import { restartEkycSession } from '@/services/ekyc/flow';
import { decisionForCode, isWaitCode, waitWhileProcessing } from '@/services/ekyc/outcome';
import { radii, spacing, typography, useTheme } from '@/theme';

type View_ = 'loading' | 'processing' | 'success' | 'pending' | 'retake' | 'rejected' | 'none';

/** คำอธิบายสั้นใต้เหตุผล (วิธีแก้) */
const REASON_HELP: Record<string, string> = {
  LOW_MATCH: 'รูปในบัตรอาจเก่า หรือแสงตอนถ่ายหน้าน้อยไป',
  BORDERLINE_MATCH: 'รูปในบัตรอาจเก่า หรือแสงตอนถ่ายหน้าน้อยไป',
  DIFFERENT_PEOPLE: 'ถ่ายใหม่ให้เห็นใบหน้าคุณคนเดียวตลอดทุกท่า',
  FACES_INCONSISTENT: 'ถ่ายใหม่ให้เห็นใบหน้าคุณคนเดียวตลอดทุกท่า',
  SPOOF_SUSPECTED: 'ถ่ายจากใบหน้าจริงเท่านั้น ห้ามถ่ายจากหน้าจอหรือรูป',
  REPLAY_SUSPECTED: 'ถ่ายจากใบหน้าจริงตรงหน้ากล้องเท่านั้น',
  LOW_LIVENESS: 'ถ่ายในที่สว่าง ทำท่าช้าๆ ตามคำสั่ง',
  LOW_REAL: 'ถ่ายจากใบหน้าจริงในที่สว่าง',
  BLURRY: 'ถือให้นิ่ง ในที่สว่าง',
  GLARE: 'เลี่ยงไฟส่องตรงบัตร',
  CARD_INCOMPLETE: 'ให้เห็นบัตรครบทั้งใบในกรอบ',
  OCR_LOW: 'ถ่ายให้ชัด ตัวอักษรไม่เบลอ',
  ID_UNREADABLE: 'ถ่ายให้เห็นเลขบัตรชัด ไม่มีนิ้วบัง',
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
  const { sleep } = useTimers();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const decision = useEkycStore((s) => s.decision);
  const status = useEkycStore((s) => s.status);
  const storeFrom = useEkycStore((s) => s.from);
  const startedAt = useEkycStore((s) => s.startedAt);
  const [loaded, setLoaded] = useState(!!decision);
  const [retrying, setRetrying] = useState(false);
  const [waitState, setWaitState] = useState<'idle' | 'waiting' | 'timeout'>('idle');
  const retryingRef = useRef(false);
  const waitingRef = useRef(false);

  // ลิงก์ที่เปิดหน้านี้มาก่อน (แจ้งเตือน/ด่าน) แล้วค่อยใช้ค่าที่จำไว้ตอนเริ่มขั้นตอน
  const from = isKycGateContext(params.from) ? params.from : storeFrom ?? 'profile';
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

  /** server ยังตรวจคำขอก่อนหน้า → ถามสถานะซ้ำจนเสร็จ (ออกจากหน้า = เลิกถาม) */
  const waitForResult = useCallback(async () => {
    if (waitingRef.current) return;
    waitingRef.current = true;
    setWaitState('waiting');
    const outcome = await waitWhileProcessing({
      fetchStatus: getEkycStatus,
      sleep,
      isCancelled: () => !mountedRef.current,
    });
    waitingRef.current = false;
    if (!mountedRef.current || outcome.kind === 'cancelled') return;
    const store = useEkycStore.getState();
    if (outcome.status) store.setStatus(outcome.status);
    if (outcome.kind === 'done') {
      // รู้ผลจากรหัส error (ไม่มีสถานะ) → ใช้ผลสรุป · มีสถานะ = หน้าจออ่านจากสถานะเอง
      if (!outcome.status) store.setDecision(outcome.decision);
      if (outcome.decision?.decision === 'approved') useAuthStore.getState().refreshUser().catch(() => {});
      setWaitState('idle');
      return;
    }
    setWaitState('timeout');
  }, [mountedRef, sleep]);

  useEffect(() => {
    if (!decision && status?.processing && waitState === 'idle') waitForResult();
  }, [decision, status?.processing, waitState, waitForResult]);

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
    if (!loaded && !status && waitState === 'idle') return 'loading';
    if (waitState !== 'idle' || status?.processing) return 'processing';
    if (!status) return 'none';
    if (status.verified) return 'success';
    if (status.kyc_status === 'pending') return 'pending';
    if (status.kyc_status === 'rejected') return 'rejected';
    if (status.last_decision === 'retake') return 'retake';
    return 'none';
  })();

  // ผลจาก POST face จริงใช้ของตัวเอง · ผลสรุป (จากสถานะ/รหัส error) ใช้ข้อมูลจากสถานะล่าสุด
  const fromSubmit = decision?.source === 'submit' ? decision : null;
  const reasons = fromSubmit?.reasons ?? status?.reasons ?? [];
  const reasonItems: EkycReasonItem[] = fromSubmit?.reason_items ?? status?.reason_items ?? [];
  const staffNote = fromSubmit ? fromSubmit.message : status?.message ?? null;
  const attemptsLeft = fromSubmit?.attempts_left ?? status?.attempts_left ?? 0;
  // ถ่ายใหม่ (ผล retake): มีสิทธิ์เหลือ และ server ให้เริ่มรอบใหม่ได้
  const canRetry = attemptsLeft > 0 && (status ? status.can_start || !!fromSubmit : true);
  // รอเจ้าหน้าที่: ถ่ายใหม่ได้เฉพาะเมื่อ server บอก can_retake (เริ่มรอบใหม่แล้ว server ยกเลิกคิวตรวจเดิมให้)
  const canRetakeReview = !!status?.can_retake;
  const seconds = fromSubmit && startedAt ? Math.max(1, Math.round((Date.now() - startedAt) / 1000)) : null;

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
      const store = useEkycStore.getState();
      // server ยังตรวจคำขอก่อนหน้า → รอดูผล (ห้ามเริ่มทับ)
      if (isWaitCode(outcome.code)) {
        store.setDecision(null);
        waitForResult();
        return;
      }
      // รู้ผลแล้ว (ยืนยันแล้ว / รอเจ้าหน้าที่) → แสดงผลนั้น
      const known = decisionForCode(outcome.code);
      if (known) {
        store.setDecision(known);
        store.loadStatus(true).catch(() => {});
        if (known.decision === 'approved') useAuthStore.getState().refreshUser().catch(() => {});
        return;
      }
      resultHaptic('error');
      if (outcome.code === 'EKYC_TOO_MANY_ATTEMPTS') {
        store.setDecision(null);
        store.loadStatus(true).catch(() => {});
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

  /** แถวเหตุผล: สีตามระดับ (เหลือง = ระดับกลาง/เจ้าหน้าที่ดู · แดง = ไม่ผ่าน) ไม่มีติ๊กเขียว */
  const reasonRows = (items: EkycReasonItem[]) =>
    items.map((item, index) => (
      <CheckRow
        key={`${item.code ?? 'text'}-${item.text}`}
        first={index === 0}
        state={item.tone}
        title={item.text}
        subtitle={item.code ? REASON_HELP[item.code] : undefined}
      />
    ));

  const staffNoteBox = staffNote ? (
    <InfoNote icon="chat-circle-dots" title="ข้อความจากเจ้าหน้าที่">
      {staffNote}
    </InfoNote>
  ) : null;

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

  // ---------- server ยังตรวจคำขอก่อนหน้า ----------
  if (view === 'processing') {
    const timedOut = waitState === 'timeout';
    return (
      <EkycShell
        title="ยืนยันตัวตน"
        onBack={exit}
        hero={heroBlock(
          heroCircle(timedOut ? 'clock' : 'cpu', 'gold'),
          timedOut ? 'ยังไม่ได้ผลตรวจ' : 'AI กำลังตรวจสอบ',
          timedOut ? 'ออกจากหน้านี้ได้เลย ผลจะแจ้งทางการแจ้งเตือน' : 'ระบบกำลังตรวจคำขอล่าสุดของคุณ รอสักครู่'
        )}
        bottom={
          timedOut ? (
            <View style={styles.row}>
              <Button3D title="ไว้ก่อน" variant="secondary" size="lg" onPress={exit} />
              <Button3D title="ดูผลอีกครั้ง" icon="arrows-clockwise" size="lg" onPress={waitForResult} style={styles.flex} />
            </View>
          ) : (
            <Button3D title="ไว้ก่อน รอแจ้งเตือน" variant="secondary" size="lg" fullWidth onPress={exit} />
          )
        }
      >
        <Card3D padding={spacing.lg} radius={22}>
          <CheckRow
            first
            state={timedOut ? 'warn' : 'running'}
            title="ตรวจคำขอล่าสุด"
            subtitle={timedOut ? 'ใช้เวลานานกว่าปกติ ลองดูผลอีกครั้งในอีกสักครู่' : 'ไม่ต้องส่งใหม่ ระบบจะแสดงผลให้เอง'}
          />
        </Card3D>
      </EkycShell>
    );
  }

  // ---------- สำเร็จ ----------
  if (view === 'success') {
    const byAi = fromSubmit?.decision === 'approved' || status?.method === 'ekyc';
    const byStaff = !byAi && (status?.method === 'manual' || !!fromSubmit);
    const subtitle =
      [byAi ? 'AI อนุมัติอัตโนมัติ' : byStaff ? 'เจ้าหน้าที่ตรวจสอบแล้ว' : null, seconds ? `ใช้เวลา ${seconds} วินาที` : null]
        .filter(Boolean)
        .join(' · ') || 'บัญชีนี้ยืนยันตัวตนแล้ว';
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
    return (
      <EkycShell
        title="ยืนยันตัวตน"
        onBack={exit}
        hero={heroBlock(heroCircle('hourglass', 'gold'), 'ส่งให้เจ้าหน้าที่ตรวจแล้ว', 'ปกติไม่เกิน 1 ชั่วโมงในเวลาทำการ · แจ้งผลทางการแจ้งเตือน')}
        bottom={
          <View style={styles.row}>
            {canRetakeReview && (
              <Button3D title="ถ่ายหน้าใหม่" variant="secondary" size="lg" loading={retrying} onPress={() => retry('face')} />
            )}
            <Button3D title="รอเจ้าหน้าที่" variant="navy" size="lg" onPress={exit} style={styles.flex} />
          </View>
        }
      >
        <Card3D padding={spacing.lg} radius={22}>
          <Text style={[typography.h2, styles.cardTitle, { color: colors.textStrong }]}>
            {reasonItems.length > 0 ? 'AI ยังไม่มั่นใจพอ เพราะ' : 'เจ้าหน้าที่กำลังตรวจสอบ'}
          </Text>
          {reasonItems.length > 0 ? (
            reasonRows(reasonItems)
          ) : (
            <CheckRow first state="running" title="รอเจ้าหน้าที่ตรวจข้อมูล" subtitle="ไม่ต้องทำอะไรเพิ่ม รอผลได้เลย" />
          )}
        </Card3D>
        {staffNoteBox}
        {canRetakeReview && (
          <InfoNote icon="lightning" title="อยากได้ผลเร็วกว่านี้?">
            {`ถ่ายใบหน้าใหม่ในที่สว่าง ถอดหมวกและแว่น AI จะตรวจซ้ำให้ทันที${
              attemptsLeft > 0 ? ` (ทำได้อีก ${attemptsLeft} ครั้งวันนี้)` : ''
            }`}
          </InfoNote>
        )}
      </EkycShell>
    );
  }

  // ---------- ถ่ายใหม่ ----------
  if (view === 'retake') {
    const target = retakeTarget(reasons);
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
          {reasonItems.length > 0 ? (
            reasonRows(reasonItems)
          ) : (
            <CheckRow first state="fail" title="ภาพยังไม่ชัดพอให้ AI ตรวจ" />
          )}
        </Card3D>
        {staffNoteBox}
        <InfoNote icon="info" title="เคล็ดลับให้ผ่านในครั้งเดียว">
          ถ่ายในที่สว่าง ถอดหมวก แว่นดำ และหน้ากาก ทำท่าช้าๆ ตามคำสั่ง ให้เห็นใบหน้าคุณคนเดียวตลอด
        </InfoNote>
      </EkycShell>
    );
  }

  // ---------- ไม่ผ่าน / ยังไม่เริ่ม ----------
  const rejected = view === 'rejected';
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
      {reasonItems.length > 0 && (
        <Card3D padding={spacing.lg} radius={22}>
          <Text style={[typography.h2, styles.cardTitle, { color: colors.textStrong }]}>เหตุผล</Text>
          {reasonRows(reasonItems)}
        </Card3D>
      )}
      {staffNoteBox}
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

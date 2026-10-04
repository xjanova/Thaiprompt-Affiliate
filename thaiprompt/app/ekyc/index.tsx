/**
 * ยืนยันตัวตนด้วย AI — หน้าแนะนำ + ยินยอม PDPA (ตามแบบ Main.png)
 *
 * - อธิบาย 3 ขั้น (ถ่ายบัตร → ถ่ายใบหน้า → AI ตรวจ) + ความปลอดภัยของข้อมูล
 * - ต้องติ๊กยินยอมก่อนเสมอ (ข้อมูลชีวภาพ — PDPA) แล้วปุ่ม "เริ่มยืนยันตัวตน" จึงกดได้ → POST /ekyc/sessions
 * - ยืนยันแล้ว → บอกว่าเรียบร้อย · รอเจ้าหน้าที่ตรวจ → พาไปหน้าผล · ลองครบโควตาวันนี้ → ปุ่มปิดพร้อมเหตุผล
 * - ?from=checkout|rider|seller|withdraw → ข้อความหัวหน้า + ปุ่มกลับไปทำต่อในหน้าผล
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Pressable, StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Screen, resultHaptic } from '@/components/ui';
import { IconTile } from '@/components/profile';
import { EkycShell, InfoNote, useEkycExit } from '@/components/ekyc/EkycKit';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { getEkycStatus } from '@/services/api/ekycApi';
import { isKycGateContext, KYC_GATE_COPY, type KycGateContext } from '@/services/ekyc/kycGate';
import { glowStyle, radii, spacing, typography, useTheme } from '@/theme';

const SUBTITLE: Record<KycGateContext, string> = {
  checkout: 'ต้องทำก่อนสั่งซื้อครั้งแรก',
  rider: 'ต้องทำก่อนเริ่มรับงานไรเดอร์',
  seller: 'ต้องทำก่อนส่งคำขอเปิดร้าน',
  withdraw: 'ต้องทำก่อนถอนเงิน',
  profile: 'ทำครั้งเดียว ใช้ได้ทุกบริการ',
};

const STEPS: { icon: 'identification-card' | 'scan-smiley' | 'cpu'; title: string; text: string; gold?: boolean }[] = [
  { icon: 'identification-card', title: '1. ถ่ายบัตรประชาชน', text: 'AI จับขอบบัตรและถ่ายให้เอง อ่านข้อมูลบนบัตรอัตโนมัติ' },
  { icon: 'scan-smiley', title: '2. ถ่ายใบหน้า', text: 'ทำตามคำสั่งสั้นๆ เช่น กระพริบตา หันหน้า เพื่อยืนยันว่าเป็นคนจริง' },
  { icon: 'cpu', title: '3. AI ตรวจและอนุมัติ', text: 'เทียบใบหน้ากับรูปในบัตร ส่วนใหญ่ผ่านทันทีภายในไม่กี่วินาที', gold: true },
];

export default function EkycIntroScreen() {
  useSensitiveScreen('ekyc-intro');
  const { colors, gradients, isDark } = useTheme();
  const params = useLocalSearchParams<{ from?: string }>();
  const mountedRef = useMountedRef();
  const exit = useEkycExit();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const from: KycGateContext = isKycGateContext(params.from) ? params.from : 'profile';
  const status = useEkycStore((s) => s.status);
  const [consent, setConsent] = useState(false);
  const [starting, setStarting] = useState(false);
  const [legacyOnly, setLegacyOnly] = useState(false);
  const startingRef = useRef(false);

  // จำว่ามาจากการกระทำไหน (หน้าผลใช้ทำปุ่มกลับไปทำต่อ) + เริ่มขั้นตอนใหม่ทุกครั้งที่เปิดหน้านี้
  useEffect(() => {
    const store = useEkycStore.getState();
    store.resetFlow();
    store.setFrom(from);
  }, [from]);

  const refresh = useCallback(async () => {
    const res = await getEkycStatus();
    if (!mountedRef.current) return;
    if (!res.success) {
      // server ยังไม่มีระบบ eKYC (404) → ใช้การส่งเอกสารแบบเดิมแทน (ไม่ขังผู้ใช้)
      if (res.status === 404) setLegacyOnly(true);
      return;
    }
    useEkycStore.getState().setStatus(res.data);
    // รอเจ้าหน้าที่ตรวจอยู่ → ดูผลแทน (เริ่มใหม่ได้จากหน้าผลถ้ายังมีสิทธิ์)
    if (res.data.kyc_status === 'pending' && !res.data.can_start) router.replace('/ekyc/result' as never);
  }, [mountedRef]);

  useEffect(() => {
    if (isAuthenticated) refresh();
  }, [isAuthenticated, refresh]);

  const start = async () => {
    if (startingRef.current || !consent) return;
    startingRef.current = true;
    setStarting(true);
    const res = await useEkycStore.getState().startSession();
    startingRef.current = false;
    if (!mountedRef.current) return;
    setStarting(false);

    if (res.success) {
      if (res.data.unsupported.length > 0 || res.data.challenges.length === 0 || !res.data.session_id) {
        resultHaptic('error');
        Alert.alert('อัปเดตแอปก่อนนะ', 'แอปรุ่นนี้ยังทำขั้นตอนยืนยันตัวตนแบบใหม่ไม่ได้ อัปเดตแอปจาก Play Store แล้วลองอีกครั้ง');
        return;
      }
      resultHaptic('success');
      router.push('/ekyc/capture' as never);
      return;
    }

    resultHaptic('error');
    if (res.code === 'EKYC_ALREADY_VERIFIED') {
      await useEkycStore.getState().loadStatus(true);
      return;
    }
    if (res.code === 'EKYC_TOO_MANY_ATTEMPTS') {
      useEkycStore.getState().loadStatus(true);
      Alert.alert('ลองครบแล้ววันนี้', res.message);
      return;
    }
    Alert.alert('เริ่มยืนยันตัวตนไม่สำเร็จ', res.message);
  };

  if (!isAuthenticated) {
    return (
      <Screen title="ยืนยันตัวตน" scroll={false}>
        <EmptyState icon="user-circle" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const verified = !!status?.verified;
  const blocked = !verified && !!status && !status.can_start && status.kyc_status !== 'pending';

  const hero = (
    <View style={styles.heroRow}>
      <LinearGradient
        colors={gradients.primary}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[styles.heroIcon, glowStyle(colors.gold, 0.6)]}
      >
        <Icon name={verified ? 'seal-check' : 'shield-check'} size={30} color={colors.textOnGold} weight={verified ? 'fill' : 'regular'} />
      </LinearGradient>
      <View style={styles.flex}>
        <Text style={[typography.serifSm, { color: colors.onHeader }]}>
          {verified ? 'ยืนยันตัวตนเรียบร้อยแล้ว' : 'ยืนยันตัวตนด้วย AI'}
        </Text>
        <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]}>
          ครั้งเดียว ใช้ได้ทั้งสั่งของ รับงาน และเปิดร้าน
        </Text>
      </View>
    </View>
  );

  const bottom = legacyOnly ? (
    <Button3D
      title="ส่งเอกสารยืนยันตัวตน"
      icon="identification-card"
      size="lg"
      fullWidth
      onPress={() => router.replace('/kyc' as never)}
    />
  ) : verified ? (
    <Button3D title={KYC_GATE_COPY[from].returnLabel} icon="check-circle" size="lg" fullWidth onPress={exit} />
  ) : (
    <Button3D
      title={blocked ? 'วันนี้ลองครบแล้ว ลองใหม่พรุ่งนี้' : 'เริ่มยืนยันตัวตน · ประมาณ 1 นาที'}
      size="lg"
      fullWidth
      disabled={!consent || blocked}
      loading={starting}
      loadingText="กำลังเริ่ม…"
      onPress={start}
      accessibilityHint={consent ? undefined : 'ติ๊กยินยอมให้ใช้ข้อมูลก่อน'}
    />
  );

  return (
    <EkycShell title="ยืนยันตัวตน" subtitle={SUBTITLE[from]} hero={hero} step={verified ? 5 : 1} onBack={exit} bottom={bottom}>
      <Card3D padding={0} radius={22}>
        {STEPS.map((item, index) => (
          <View key={item.title} style={[styles.stepRow, index > 0 && { borderTopWidth: 1, borderTopColor: colors.divider }]}>
            <IconTile icon={item.icon} tone={item.gold ? 'gold' : 'navy'} />
            <View style={styles.flex}>
              <Text style={[typography.h3, { color: colors.textStrong }]}>{item.title}</Text>
              <Text style={[typography.bodySm, { color: colors.textMuted }]}>{item.text}</Text>
            </View>
          </View>
        ))}
      </Card3D>

      <InfoNote icon="lock" title="ข้อมูลของคุณปลอดภัย">
        <View style={styles.noteBody}>
          <Text style={[typography.bodySm, { color: colors.text }]}>
            รูปบัตรและรูปใบหน้าเข้ารหัสเก็บบนเซิร์ฟเวอร์ไทยพร้อม ประมวลผลด้วย AI ของเราเอง ไม่ส่งให้บริษัทอื่น
            เจ้าหน้าที่ดูได้เฉพาะกรณีต้องตรวจสอบ และลบได้เมื่อลบบัญชี
          </Text>
          <Text style={[typography.bodySm, { color: colors.text }]}>
            รูปโปรไฟล์ของคุณยังเปลี่ยนเป็นรูปอะไรก็ได้ตามใจ รูปยืนยันตัวตนจะไม่ถูกแสดงให้คนอื่นเห็น
          </Text>
        </View>
      </InfoNote>

      {!verified && !legacyOnly && (
        <Pressable
          onPress={() => setConsent((v) => !v)}
          accessibilityRole="checkbox"
          accessibilityState={{ checked: consent }}
          accessibilityLabel="ยินยอมให้ไทยพร้อมเก็บและใช้ข้อมูลบัตรประชาชนและใบหน้าเพื่อยืนยันตัวตน"
          style={[
            styles.consent,
            {
              backgroundColor: isDark ? colors.goldSoft : colors.surface,
              borderColor: consent ? colors.gold : colors.border,
            },
          ]}
        >
          <View
            style={[
              styles.checkbox,
              consent
                ? { backgroundColor: colors.navyFill, borderColor: colors.navyFill }
                : { backgroundColor: colors.card, borderColor: colors.textFaint },
            ]}
          >
            {consent && <Icon name="check" size={18} color={colors.goldLight} weight="bold" />}
          </View>
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            ข้าพเจ้ายินยอมให้ไทยพร้อมเก็บและใช้ข้อมูลบัตรประชาชนและข้อมูลชีวภาพ (ใบหน้า) เพื่อยืนยันตัวตนและป้องกันการทุจริต{' '}
            <Text
              onPress={() => router.push('/privacy' as never)}
              accessibilityRole="link"
              style={[typography.bodySm, styles.link, { color: colors.goldDeep }]}
            >
              อ่านนโยบายความเป็นส่วนตัว
            </Text>
          </Text>
        </Pressable>
      )}

      {legacyOnly && (
        <View style={[styles.warn, { backgroundColor: colors.infoSoft }]}>
          <Icon name="info" size={18} color={colors.info} />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            ระบบยืนยันตัวตนด้วย AI ยังไม่เปิดใช้ในตอนนี้ ส่งรูปบัตรและเซลฟี่ให้เจ้าหน้าที่ตรวจแทนได้เลย
          </Text>
        </View>
      )}

      {blocked && (
        <View style={[styles.warn, { backgroundColor: colors.warningSoft }]}>
          <Icon name="clock" size={18} color={colors.warning} />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            วันนี้ลองยืนยันตัวตนครบจำนวนครั้งแล้ว ลองใหม่ได้พรุ่งนี้ หรือติดต่อทีมงานที่หน้าช่วยเหลือ
          </Text>
        </View>
      )}
    </EkycShell>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  heroRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.lg,
  },
  heroIcon: {
    width: 64,
    height: 64,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
  },
  noteBody: {
    gap: spacing.sm,
  },
  consent: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1.5,
    padding: spacing.lg,
  },
  checkbox: {
    width: 28,
    height: 28,
    borderRadius: 8,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  link: {
    fontWeight: '700',
    textDecorationLine: 'underline',
  },
  warn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radii.lg,
    padding: spacing.md,
  },
});

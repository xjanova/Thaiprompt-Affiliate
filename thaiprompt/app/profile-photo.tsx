/**
 * โปรไฟล์ของฉัน — เปลี่ยนรูปโปรไฟล์ + ป้ายยืนยันตัวตน (ตามแบบ Profile.png ที่เจ้าของอนุมัติ 2026-10-04)
 *
 * - รูปโปรไฟล์ = รูปอะไรก็ได้ (การ์ตูน สัตว์เลี้ยง โลโก้ร้าน) จากคลังรูปหรือถ่ายใหม่ → POST /profile/avatar (endpoint เดียวกับหน้าแก้ไขโปรไฟล์)
 *   ไม่บังคับถ่ายสดแล้ว (เลิกตัวบังคับใน app/_layout) — ความน่าเชื่อถือดูจากป้ายทอง "ยืนยันตัวตนแล้ว"
 * - "คนอื่นเห็นคุณแบบนี้" = รูปจาก GET /me/profile-photo (photo_url ที่ server ให้คนอื่นเห็น) + ชื่อย่อ + ป้ายทอง
 * - การยืนยันตัวตน: สถานะจาก GET /ekyc/status · ยังไม่ยืนยัน → ปุ่มไปขั้นตอน eKYC
 * - กันกดอัปโหลดซ้ำ · ไม่ setState หลังออกจากหน้า · ขอสิทธิ์กล้องตอนกดถ่ายเท่านั้น (ปฏิเสธถาวร → เปิดการตั้งค่า)
 * - ?from=checkout|rider|seller = มาจาก PROFILE_PHOTO_REQUIRED (ถ้า server เปิดบังคับกลับ) → ปุ่มกลับไปทำต่อ
 */

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, StyleSheet, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { router, useLocalSearchParams } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { Button3D, Card3D, EmptyState, Icon, Pill, Screen, resultHaptic } from '@/components/ui';
import { IconTile } from '@/components/profile';
import { EkycShell, InfoNote } from '@/components/ekyc/EkycKit';
import { PersonAvatar } from '@/components/people/PersonAvatar';
import { VerifiedBadge } from '@/components/people/VerifiedBadge';
import { useMountedRef } from '@/components/taladsod/hooks';
import { useSensitiveScreen } from '@/hooks/useSensitiveScreen';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { getProfilePhoto, uploadProfileAvatar } from '@/services/api/profilePhotoApi';
import { formatThaiDate, THAI_MONTHS_SHORT } from '@/services/api/ekycApi';
import { getAvatarUrl, shortDisplayName } from '@/utils/user';
import { spacing, typography, useTheme } from '@/theme';

/** "สมาชิกตั้งแต่ ก.ย. 2569" */
const memberSince = (iso: string | null | undefined): string | null => {
  const m = /^(\d{4})-(\d{2})/.exec(iso || '');
  if (!m) return null;
  const month = Number(m[2]);
  if (month < 1 || month > 12) return null;
  return `สมาชิกตั้งแต่ ${THAI_MONTHS_SHORT[month - 1]} ${Number(m[1]) + 543}`;
};

const RETURN_LABEL: Record<string, string> = {
  checkout: 'กลับไปสั่งต่อ',
  rider: 'กลับไปเริ่มรับงาน',
  seller: 'กลับไปส่งคำขอ',
};

export default function MyProfileScreen() {
  useSensitiveScreen('profile-photo');
  const { colors } = useTheme();
  const params = useLocalSearchParams<{ from?: string }>();
  const mountedRef = useMountedRef();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const user = useAuthStore((s) => s.user);
  const updateUser = useAuthStore((s) => s.updateUser);
  const refreshUser = useAuthStore((s) => s.refreshUser);
  const status = useEkycStore((s) => s.status);

  const [publicPhoto, setPublicPhoto] = useState<string | null>(null);
  const [photoLoading, setPhotoLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const uploadingRef = useRef(false);

  const loadPublicPhoto = useCallback(async () => {
    const res = await getProfilePhoto();
    if (!mountedRef.current) return;
    setPhotoLoading(false);
    if (res.success) setPublicPhoto(res.data.photo_url);
  }, [mountedRef]);

  useEffect(() => {
    if (!isAuthenticated) return;
    loadPublicPhoto();
    useEkycStore.getState().loadStatus(true);
  }, [isAuthenticated, loadPublicPhoto]);

  // ---------- เปลี่ยนรูปโปรไฟล์ ----------
  const upload = async (uri: string) => {
    if (uploadingRef.current) return;
    uploadingRef.current = true;
    setUploading(true);
    try {
      const res = await uploadProfileAvatar(uri);
      if (!mountedRef.current) return;
      if (res.success) {
        if (res.data?.user) updateUser(res.data.user as Parameters<typeof updateUser>[0]);
        else if (res.data?.avatarUrl) updateUser({ avatar: res.data.avatarUrl });
        await refreshUser();
        await loadPublicPhoto();
        if (mountedRef.current) resultHaptic('success');
      } else {
        resultHaptic('error');
        Alert.alert('เปลี่ยนรูปไม่สำเร็จ', res.message);
      }
    } finally {
      uploadingRef.current = false;
      if (mountedRef.current) setUploading(false);
    }
  };

  const takePhoto = async () => {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (permission.status !== 'granted') {
        Alert.alert(
          'ขอใช้กล้องก่อนนะ',
          permission.canAskAgain === false
            ? 'เปิดสิทธิ์กล้องให้แอปในการตั้งค่าของเครื่อง แล้วกลับมาถ่ายรูปอีกครั้ง หรือเลือกรูปจากคลังแทน'
            : 'ใช้กล้องเพื่อถ่ายรูปโปรไฟล์ใหม่ หรือเลือกรูปจากคลังแทนก็ได้',
          permission.canAskAgain === false
            ? [
                { text: 'ไว้ก่อน', style: 'cancel' },
                { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
              ]
            : [{ text: 'ตกลง' }]
        );
        return;
      }
      const result = await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [1, 1], quality: 0.8 });
      if (!result.canceled && result.assets?.[0]?.uri) await upload(result.assets[0].uri);
    } catch {
      Alert.alert('เปิดกล้องไม่ได้', 'ลองเลือกรูปจากคลังแทนนะ');
    }
  };

  const pickPhoto = async () => {
    try {
      const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [1, 1], quality: 0.8 });
      if (!result.canceled && result.assets?.[0]?.uri) await upload(result.assets[0].uri);
    } catch {
      Alert.alert('เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
    }
  };

  const chooseSource = () => {
    if (uploadingRef.current) return;
    Alert.alert('เปลี่ยนรูปโปรไฟล์', 'เลือกรูปอะไรก็ได้ ภาพการ์ตูน สัตว์เลี้ยง หรือโลโก้ร้าน', [
      { text: 'เลือกจากคลังรูป', onPress: pickPhoto },
      { text: 'ถ่ายรูปใหม่', onPress: takePhoto },
      { text: 'ยกเลิก', style: 'cancel' },
    ]);
  };

  if (!isAuthenticated || !user) {
    return (
      <Screen title="โปรไฟล์ของฉัน" scroll={false}>
        <EmptyState icon="user-circle" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  // ---------- ส่วนแสดงผล ----------
  const verified = !!status?.verified;
  const avatar = getAvatarUrl(user.avatar);
  const since = memberSince(user.createdAt);
  const returnLabel = params.from ? RETURN_LABEL[params.from] : undefined;

  const kyc = (() => {
    if (!status) return { pill: null, text: 'กำลังโหลด…', sub: null as string | null };
    if (status.verified) {
      const date = formatThaiDate(status.verified_at, true);
      return {
        pill: <Pill label="ผ่านแล้ว" icon="check" tone="success" />,
        text: `ยืนยันตัวตนแล้ว${date ? ` · ${date}` : ''}`,
        sub: status.method === 'manual' ? 'ตรวจโดยเจ้าหน้าที่' : 'อนุมัติโดย AI',
      };
    }
    if (status.kyc_status === 'pending') {
      return { pill: <Pill label="รอตรวจ" icon="hourglass" tone="warning" />, text: 'เจ้าหน้าที่กำลังตรวจสอบ', sub: 'แจ้งผลทางการแจ้งเตือน' };
    }
    if (status.kyc_status === 'rejected') {
      return { pill: <Pill label="ไม่ผ่าน" icon="x" tone="danger" />, text: 'ยืนยันตัวตนไม่ผ่าน', sub: 'ดูเหตุผลและลองใหม่ได้' };
    }
    return { pill: <Pill label="ยังไม่ยืนยัน" tone="neutral" />, text: 'ยังไม่ได้ยืนยันตัวตน', sub: 'ใช้เวลาประมาณ 1 นาที ทำครั้งเดียว' };
  })();

  const hero = (
    <View style={styles.hero}>
      <PersonAvatar uri={avatar} name={user.name} size={108} verified={verified} surfaceColor={colors.navyFill} />
      <View style={styles.nameRow}>
        <Text style={[typography.serifLg, { color: colors.onHeader }]} numberOfLines={1}>
          {user.name}
        </Text>
        {verified && <VerifiedBadge size={22} />}
      </View>
      {!!since && <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]}>{since}</Text>}
    </View>
  );

  const fieldRow = (label: string, value: React.ReactNode, sub?: React.ReactNode, first = false) => (
    <View style={[styles.field, !first && { borderTopWidth: 1, borderTopColor: colors.divider }]}>
      <Text style={[typography.caption, { color: colors.textMuted }]}>{label}</Text>
      {typeof value === 'string' ? <Text style={[typography.h3, { color: colors.textStrong }]}>{value}</Text> : value}
      {sub}
    </View>
  );

  return (
    <EkycShell
      title="โปรไฟล์ของฉัน"
      hero={hero}
      bottom={returnLabel ? <Button3D title={returnLabel} icon="check-circle" size="lg" fullWidth onPress={() => router.back()} /> : undefined}
    >
      {/* เปลี่ยนรูปโปรไฟล์ */}
      <Card3D padding={spacing.lg} radius={22}>
        <View style={styles.row}>
          <IconTile icon="image" />
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>เปลี่ยนรูปโปรไฟล์</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>เลือกรูปอะไรก็ได้ ภาพการ์ตูน สัตว์เลี้ยง หรือโลโก้ร้าน</Text>
          </View>
          <Button3D title="เลือกรูป" icon="upload-simple" variant="secondary" size="md" loading={uploading} onPress={chooseSource} />
        </View>
      </Card3D>

      {/* คนอื่นเห็นคุณแบบนี้ */}
      <Card3D padding={spacing.lg} radius={22}>
        <Text style={[typography.h3, styles.cardTitle, { color: colors.textStrong }]}>คนอื่นเห็นคุณแบบนี้</Text>
        <View style={[styles.preview, { backgroundColor: colors.inset }]}>
          {photoLoading ? (
            <ActivityIndicator color={colors.gold} />
          ) : (
            <PersonAvatar uri={publicPhoto ?? avatar} name={user.name} size={48} surfaceColor={colors.inset} />
          )}
          <View style={styles.flex}>
            <View style={styles.inline}>
              <Text style={[typography.bodyStrong, { color: colors.textStrong }]} numberOfLines={1}>
                {shortDisplayName(user.name)}
              </Text>
              {verified && <VerifiedBadge size={15} />}
            </View>
            <Text style={[typography.caption, { color: verified ? colors.textMuted : colors.textFaint }]}>
              {verified ? 'ยืนยันตัวตนแล้ว' : 'ยังไม่ยืนยันตัวตน'}
            </Text>
          </View>
          <Pill label="ผู้ซื้อ" tone="neutral" />
        </View>
      </Card3D>

      {/* การยืนยันตัวตน */}
      <Card3D padding={spacing.lg} radius={22}>
        <View style={styles.row}>
          <Text style={[typography.h2, styles.flex, { color: colors.textStrong }]}>การยืนยันตัวตน</Text>
          {kyc.pill}
        </View>
        {fieldRow(
          'สถานะ',
          kyc.text,
          kyc.sub ? (
            <View style={styles.inline}>
              {verified && <Icon name="check" size={13} color={colors.success} weight="bold" />}
              <Text style={[typography.caption, { color: verified ? colors.success : colors.textMuted }]}>{kyc.sub}</Text>
            </View>
          ) : undefined,
          true
        )}
        {!!status?.name_th && fieldRow('ชื่อตามบัตร', status.name_th)}
        {!!status?.id_number_masked && fieldRow('เลขบัตร', status.id_number_masked)}
        {!!status && !verified && (
          <Button3D
            title={status.kyc_status === 'pending' ? 'ดูสถานะ' : 'ยืนยันตัวตนตอนนี้'}
            icon="seal-check"
            size="md"
            fullWidth
            variant={status.kyc_status === 'pending' ? 'secondary' : 'primary'}
            onPress={() => router.push((status.kyc_status === 'pending' ? '/ekyc/result?from=profile' : '/ekyc?from=profile') as never)}
            style={styles.gapTop}
          />
        )}
      </Card3D>

      <InfoNote icon="lock">
        รูปบัตรและรูปใบหน้าที่ใช้ยืนยันตัวตนเข้ารหัสเก็บไว้ ไม่แสดงให้ใครเห็น นอกจากเจ้าหน้าที่เมื่อต้องตรวจสอบ
      </InfoNote>
    </EkycShell>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  hero: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
    maxWidth: '90%',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  inline: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  cardTitle: {
    marginBottom: spacing.md,
  },
  preview: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    borderRadius: 18,
    padding: spacing.md,
  },
  field: {
    paddingVertical: spacing.md,
    gap: 2,
  },
  gapTop: {
    marginTop: spacing.md,
  },
});

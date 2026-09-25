/**
 * แก้ไขโปรไฟล์ — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - PUT /profile · POST /profile/avatar (multipart ช่อง avatar) ผ่าน client กลาง → ข้อความผิดพลาดภาษาไทยเสมอ
 * - กรอกข้อมูลเดิมไว้ให้แล้ว · ตรวจชื่อ/เบอร์ก่อนส่ง (บอกผิดใต้ช่อง) · กันกดบันทึกซ้ำ
 * - ยังไม่บันทึกแล้วจะออกจากหน้า → ถามก่อนทิ้งการแก้ไข
 * - รูปโปรไฟล์: ถ่ายรูป (ขอสิทธิ์กล้องตอนกด) หรือเลือกจากคลังรูปของระบบ (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง)
 */

import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Alert, Linking, Pressable, StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import * as Clipboard from 'expo-clipboard';
import * as ImagePicker from 'expo-image-picker';
import { router, useNavigation } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { API_ENDPOINTS } from '@/constants';
import { apiPut, apiUpload, fileFromUri, type ApiResult } from '@/services/api/client';
import { getAvatarInitial, getAvatarUrl } from '@/utils/user';
import { Button3D, Card3D, EmptyState, Icon, Screen, resultHaptic } from '@/components/ui';
import { Field } from '@/components/shop';
import { AvatarRing, GroupLabel, IconTile } from '@/components/profile';
import { useTheme, radii, shadowStyle, spacing, typography } from '@/theme';

interface ProfileUpdateBody {
  name?: string;
  phone?: string;
  address?: string;
  bio?: string;
  bank_name?: string;
  bank_account?: string;
  bank_account_name?: string;
}

/** PUT /profile */
const updateProfileApi = (data: ProfileUpdateBody): Promise<ApiResult<unknown>> =>
  apiPut<unknown>('/profile', data, { fallbackMessage: 'บันทึกโปรไฟล์ไม่สำเร็จ ลองใหม่อีกครั้งนะ' });

/** POST /profile/avatar → { avatarUrl, user } */
const uploadAvatarApi = (imageUri: string): Promise<ApiResult<{ avatarUrl?: string; user?: Record<string, unknown> }>> => {
  const form = new FormData();
  form.append('avatar', fileFromUri(imageUri, 'avatar') as unknown as Blob);
  return apiUpload<{ avatarUrl?: string; user?: Record<string, unknown> }>(API_ENDPOINTS.AVATAR_UPLOAD, form, {
    fallbackMessage: 'อัปโหลดรูปโปรไฟล์ไม่สำเร็จ ลองใหม่อีกครั้งนะ',
  });
};

type FormState = Required<ProfileUpdateBody>;

const PHONE_RE = /^0\d{8,9}$/;

export default function EditProfileScreen() {
  const { colors } = useTheme();
  const navigation = useNavigation();
  const user = useAuthStore((s) => s.user);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const updateUser = useAuthStore((s) => s.updateUser);
  const refreshUser = useAuthStore((s) => s.refreshUser);

  const initial = useMemo<FormState>(
    () => ({
      name: user?.name || '',
      phone: user?.phone || '',
      address: user?.address || '',
      bio: user?.bio || '',
      bank_name: user?.bank_name || '',
      bank_account: user?.bank_account || '',
      bank_account_name: user?.bank_account_name || '',
    }),
    // ใช้ค่าตอนเปิดหน้าเท่านั้น (ไม่ทับสิ่งที่กำลังพิมพ์เมื่อ user ในสโตร์เปลี่ยน)
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );
  const [form, setForm] = useState<FormState>(initial);
  const [saved, setSaved] = useState<FormState>(initial);
  const [errors, setErrors] = useState<Partial<Record<keyof FormState, string>>>({});
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);

  const savingRef = useRef(false);
  const uploadingRef = useRef(false);
  const mountedRef = useRef(true);
  const allowLeaveRef = useRef(false);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const set = (key: keyof FormState) => (value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    if (errors[key]) setErrors((prev) => ({ ...prev, [key]: undefined }));
  };

  const dirty = (Object.keys(form) as Array<keyof FormState>).some((k) => form[k].trim() !== saved[k].trim());

  // ออกจากหน้าโดยยังไม่บันทึก → ถามก่อน
  useEffect(() => {
    const unsubscribe = navigation.addListener('beforeRemove', (event: any) => {
      if (!dirty || allowLeaveRef.current || savingRef.current) return;
      event.preventDefault();
      Alert.alert('ยังไม่ได้บันทึก', 'ข้อมูลที่แก้ไว้จะหายไป ออกจากหน้านี้เลยไหม?', [
        { text: 'อยู่ต่อ', style: 'cancel' },
        {
          text: 'ทิ้งการแก้ไข',
          style: 'destructive',
          onPress: () => {
            allowLeaveRef.current = true;
            navigation.dispatch(event.data.action);
          },
        },
      ]);
    });
    return unsubscribe;
  }, [navigation, dirty]);

  // ---------- รูปโปรไฟล์ ----------
  const uploadAvatar = async (uri: string) => {
    if (uploadingRef.current) return;
    uploadingRef.current = true;
    setUploading(true);
    try {
      const result = await uploadAvatarApi(uri);
      if (!mountedRef.current) return;
      if (result.success) {
        if (result.data?.user) {
          updateUser(result.data.user as Parameters<typeof updateUser>[0]);
        } else if (result.data?.avatarUrl) {
          updateUser({ avatar: result.data.avatarUrl });
        }
        await refreshUser();
        if (mountedRef.current) resultHaptic('success');
      } else {
        resultHaptic('error');
        Alert.alert('อัปโหลดรูปไม่สำเร็จ', result.message);
      }
    } finally {
      uploadingRef.current = false;
      if (mountedRef.current) setUploading(false);
    }
  };

  const takeAvatar = async () => {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (permission.status !== 'granted') {
        if (permission.canAskAgain === false) {
          Alert.alert('เปิดสิทธิ์กล้องก่อนนะ', 'ไปที่การตั้งค่าเครื่อง แล้วอนุญาตให้ใช้กล้อง', [
            { text: 'ไว้ก่อน', style: 'cancel' },
            { text: 'เปิดการตั้งค่า', onPress: () => Linking.openSettings().catch(() => {}) },
          ]);
        }
        return;
      }
      const result = await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [1, 1], quality: 0.7 });
      if (!result.canceled && result.assets?.[0]?.uri) await uploadAvatar(result.assets[0].uri);
    } catch {
      Alert.alert('เปิดกล้องไม่ได้', 'ลองเลือกรูปจากคลังแทนนะ');
    }
  };

  const pickAvatar = async () => {
    try {
      const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [1, 1], quality: 0.7 });
      if (!result.canceled && result.assets?.[0]?.uri) await uploadAvatar(result.assets[0].uri);
    } catch {
      Alert.alert('เปิดคลังรูปไม่ได้', 'ลองใหม่อีกครั้งนะ');
    }
  };

  const changeAvatar = () => {
    if (uploadingRef.current) return;
    Alert.alert('เปลี่ยนรูปโปรไฟล์', undefined, [
      { text: 'ถ่ายรูป', onPress: takeAvatar },
      { text: 'เลือกจากคลังรูป', onPress: pickAvatar },
      { text: 'ยกเลิก', style: 'cancel' },
    ]);
  };

  // ---------- บันทึก ----------
  const save = async () => {
    if (savingRef.current) return;
    const next: Partial<Record<keyof FormState, string>> = {};
    const name = form.name.trim();
    const phone = form.phone.replace(/[\s-]/g, '');
    if (name.length < 2) next.name = 'ใส่ชื่อ-นามสกุลอย่างน้อย 2 ตัวอักษร';
    if (phone && !PHONE_RE.test(phone)) next.phone = 'เบอร์โทรต้องขึ้นต้นด้วย 0 และมี 9–10 หลัก';
    const account = form.bank_account.replace(/[\s-]/g, '');
    if (account && !/^\d{10,15}$/.test(account)) next.bank_account = 'เลขบัญชีเป็นตัวเลข 10–15 หลัก';
    if (Object.keys(next).length > 0) {
      setErrors(next);
      resultHaptic('warning');
      return;
    }

    const body: FormState = {
      name,
      phone,
      address: form.address.trim(),
      bio: form.bio.trim(),
      bank_name: form.bank_name.trim(),
      bank_account: account,
      bank_account_name: form.bank_account_name.trim(),
    };

    savingRef.current = true;
    setSaving(true);
    const result = await updateProfileApi(body);
    savingRef.current = false;
    if (!mountedRef.current) return;
    setSaving(false);

    if (result.success) {
      updateUser(body);
      setForm(body);
      setSaved(body);
      resultHaptic('success');
      allowLeaveRef.current = true;
      Alert.alert('บันทึกแล้ว', 'อัปเดตโปรไฟล์เรียบร้อย', [{ text: 'ตกลง', onPress: () => router.back() }]);
    } else {
      resultHaptic('error');
      Alert.alert('บันทึกไม่สำเร็จ', result.message);
    }
  };

  if (!isAuthenticated) {
    return (
      <Screen title="แก้ไขโปรไฟล์" scroll={false}>
        <EmptyState icon="user-circle" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
      </Screen>
    );
  }

  const avatarUrl = getAvatarUrl(user?.avatar);
  const referral = user?.referralCode || '';

  return (
    <Screen
      title="แก้ไขโปรไฟล์"
      right={<Button3D title="บันทึก" size="sm" disabled={!dirty} loading={saving} onPress={save} />}
    >
      {/* ---------- รูปโปรไฟล์ (วงแหวนทอง + ป้ายกล้อง) ---------- */}
      <View style={styles.avatarSection}>
        <Pressable
          onPress={changeAvatar}
          disabled={uploading}
          accessibilityRole="button"
          accessibilityLabel="เปลี่ยนรูปโปรไฟล์"
          style={({ pressed }) => [{ opacity: pressed ? 0.8 : 1 }]}
        >
          <View style={[styles.avatarShadow, shadowStyle('md', colors.shadowDark)]}>
            <AvatarRing
              uri={avatarUrl}
              initial={getAvatarInitial(user?.name)}
              size={116}
              uploading={uploading}
              showCamera
              gapColor={colors.background}
            />
          </View>
        </Pressable>
        <View style={styles.hintRow}>
          <Icon name="hand-tap" size={14} color={colors.textMuted} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>แตะรูปเพื่อเปลี่ยน</Text>
        </View>
      </View>

      {/* ---------- ข้อมูลส่วนตัว ---------- */}
      <GroupLabel title="ข้อมูลส่วนตัว" />
      <Card3D padding={spacing.lg}>
        <Field label="ชื่อ-นามสกุล" required value={form.name} onChangeText={set('name')} placeholder="ชื่อที่ร้านและไรเดอร์จะเห็น" error={errors.name} maxLength={100} containerStyle={styles.noTop} />
        <Field label="อีเมล" value={user?.email || ''} editable={false} hint="เปลี่ยนอีเมลได้ที่เว็บไซต์" style={{ color: colors.textMuted }} />
        <Field label="เบอร์โทรศัพท์" value={form.phone} onChangeText={set('phone')} placeholder="08X-XXX-XXXX" keyboardType="phone-pad" error={errors.phone} maxLength={15} />
        <Field label="ที่อยู่" value={form.address} onChangeText={set('address')} placeholder="ไม่บังคับ" multiline maxLength={500} />
        <Field label="แนะนำตัว" value={form.bio} onChangeText={set('bio')} placeholder="เขียนสั้นๆ เกี่ยวกับคุณ (ไม่บังคับ)" multiline maxLength={300} />
      </Card3D>

      {/* ---------- บัญชีธนาคาร ---------- */}
      <GroupLabel title="บัญชีรับเงิน" subtitle="ใช้ตอนถอนเงิน ชื่อบัญชีต้องตรงกับชื่อจริง" style={styles.section} />
      <Card3D padding={spacing.lg}>
        <Field label="ธนาคาร" value={form.bank_name} onChangeText={set('bank_name')} placeholder="เช่น กสิกรไทย" maxLength={100} containerStyle={styles.noTop} />
        <Field label="เลขบัญชี" value={form.bank_account} onChangeText={set('bank_account')} placeholder="ตัวเลขเท่านั้น" keyboardType="number-pad" error={errors.bank_account} maxLength={20} />
        <Field label="ชื่อบัญชี" value={form.bank_account_name} onChangeText={set('bank_account_name')} placeholder="ชื่อเจ้าของบัญชี" maxLength={100} />
      </Card3D>

      {/* ---------- รหัสชวนเพื่อน (กรอบทองเส้นประ) ---------- */}
      {!!referral && (
        <>
          <GroupLabel title="รหัสชวนเพื่อน" style={styles.section} />
          <View style={[styles.referralCard, { backgroundColor: colors.goldSoft, borderColor: colors.gold }]}>
            <IconTile icon="gift" tone="gold" size={40} />
            <Text style={[typography.h2, styles.referralCode, { color: colors.goldDeep }]} selectable>
              {referral}
            </Text>
            <Button3D
              title="คัดลอก"
              icon="copy"
              size="sm"
              variant="secondary"
              onPress={async () => {
                await Clipboard.setStringAsync(referral);
                resultHaptic('success');
              }}
            />
          </View>
        </>
      )}

      <Button3D
        title={dirty ? 'บันทึกการเปลี่ยนแปลง' : 'ยังไม่มีการเปลี่ยนแปลง'}
        icon="check"
        size="lg"
        fullWidth
        disabled={!dirty}
        loading={saving}
        loadingText="กำลังบันทึก..."
        onPress={save}
        style={styles.saveButton}
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  avatarSection: {
    alignItems: 'center',
    marginTop: spacing.sm,
    marginBottom: spacing.xl,
  },
  avatarShadow: {
    borderRadius: 58,
  },
  hintRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  noTop: {
    marginTop: 0,
  },
  section: {
    marginTop: spacing.xxl,
  },
  referralCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.md,
    borderRadius: radii.lg,
    borderWidth: 1.5,
    borderStyle: 'dashed',
  },
  referralCode: {
    flex: 1,
    letterSpacing: 1.5,
  },
  saveButton: {
    marginTop: spacing.xxl,
  },
});

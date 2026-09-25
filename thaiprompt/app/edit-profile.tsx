/**
 * แก้ไขโปรไฟล์ — ธีมนวลทองคำ
 *
 * - PUT /profile · POST /profile/avatar (multipart ช่อง avatar) ผ่าน client กลาง → ข้อความผิดพลาดภาษาไทยเสมอ
 * - กรอกข้อมูลเดิมไว้ให้แล้ว · ตรวจชื่อ/เบอร์ก่อนส่ง (บอกผิดใต้ช่อง) · กันกดบันทึกซ้ำ
 * - ยังไม่บันทึกแล้วจะออกจากหน้า → ถามก่อนทิ้งการแก้ไข
 * - รูปโปรไฟล์: ถ่ายรูป (ขอสิทธิ์กล้องตอนกด) หรือเลือกจากคลังรูปของระบบ (ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง)
 */

import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import * as Clipboard from 'expo-clipboard';
import * as ImagePicker from 'expo-image-picker';
import { router, useNavigation } from 'expo-router';
import { useAuthStore } from '@/stores/authStore';
import { API_ENDPOINTS } from '@/constants';
import { apiPut, apiUpload, fileFromUri, type ApiResult } from '@/services/api/client';
import { getAvatarInitial, getAvatarUrl } from '@/utils/user';
import { Button3D, Card3D, EmptyState, Screen, SectionHeader, resultHaptic } from '@/components/ui';
import { Field } from '@/components/shop';
import { useTheme, clayShadowStyle, spacing, typography } from '@/theme';

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
  const { colors, gradients } = useTheme();
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
      { text: '📷 ถ่ายรูป', onPress: takeAvatar },
      { text: '🖼️ เลือกจากคลังรูป', onPress: pickAvatar },
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
        <EmptyState icon="👤" title="เข้าสู่ระบบก่อนนะ" actionLabel="เข้าสู่ระบบ" onAction={() => router.push('/login')} />
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
      {/* ---------- รูปโปรไฟล์ ---------- */}
      <View style={styles.avatarSection}>
        <Pressable
          onPress={changeAvatar}
          disabled={uploading}
          accessibilityRole="button"
          accessibilityLabel="เปลี่ยนรูปโปรไฟล์"
          style={({ pressed }) => [{ opacity: pressed ? 0.8 : 1 }]}
        >
          <View style={[styles.avatarRing, { backgroundColor: gradients.gold[1] }, clayShadowStyle('md', colors.shadowDark, colors.shadowLight)]}>
            {uploading ? (
              <View style={[styles.avatar, styles.center, { backgroundColor: colors.inset }]}>
                <ActivityIndicator color={colors.gold} />
              </View>
            ) : avatarUrl ? (
              <Image source={{ uri: avatarUrl }} style={styles.avatar} contentFit="cover" transition={150} />
            ) : (
              <View style={[styles.avatar, styles.center, { backgroundColor: colors.goldSoft }]}>
                <Text style={[styles.initial, { color: colors.goldDeep }]}>{getAvatarInitial(user?.name)}</Text>
              </View>
            )}
            <View style={[styles.camera, { backgroundColor: colors.card, borderColor: colors.border }]}>
              <Text style={styles.cameraIcon}>📷</Text>
            </View>
          </View>
        </Pressable>
        <Text style={[typography.caption, styles.hint, { color: colors.textMuted }]}>แตะรูปเพื่อเปลี่ยน</Text>
      </View>

      {/* ---------- ข้อมูลส่วนตัว ---------- */}
      <SectionHeader title="ข้อมูลส่วนตัว" icon="👤" />
      <Card3D padding={spacing.lg}>
        <Field label="ชื่อ-นามสกุล" required value={form.name} onChangeText={set('name')} placeholder="ชื่อที่ร้านและไรเดอร์จะเห็น" error={errors.name} maxLength={100} containerStyle={styles.noTop} />
        <Field label="อีเมล" value={user?.email || ''} editable={false} hint="เปลี่ยนอีเมลได้ที่เว็บไซต์" style={{ color: colors.textMuted }} />
        <Field label="เบอร์โทรศัพท์" value={form.phone} onChangeText={set('phone')} placeholder="08X-XXX-XXXX" keyboardType="phone-pad" error={errors.phone} maxLength={15} />
        <Field label="ที่อยู่" value={form.address} onChangeText={set('address')} placeholder="ไม่บังคับ" multiline maxLength={500} />
        <Field label="แนะนำตัว" value={form.bio} onChangeText={set('bio')} placeholder="เขียนสั้นๆ เกี่ยวกับคุณ (ไม่บังคับ)" multiline maxLength={300} />
      </Card3D>

      {/* ---------- บัญชีธนาคาร ---------- */}
      <SectionHeader title="บัญชีรับเงิน" icon="🏦" subtitle="ใช้ตอนถอนเงิน ชื่อบัญชีต้องตรงกับชื่อจริง" style={styles.section} />
      <Card3D padding={spacing.lg}>
        <Field label="ธนาคาร" value={form.bank_name} onChangeText={set('bank_name')} placeholder="เช่น กสิกรไทย" maxLength={100} containerStyle={styles.noTop} />
        <Field label="เลขบัญชี" value={form.bank_account} onChangeText={set('bank_account')} placeholder="ตัวเลขเท่านั้น" keyboardType="number-pad" error={errors.bank_account} maxLength={20} />
        <Field label="ชื่อบัญชี" value={form.bank_account_name} onChangeText={set('bank_account_name')} placeholder="ชื่อเจ้าของบัญชี" maxLength={100} />
      </Card3D>

      {/* ---------- รหัสชวนเพื่อน ---------- */}
      {!!referral && (
        <>
          <SectionHeader title="รหัสชวนเพื่อน" icon="🤝" style={styles.section} />
          <Card3D variant="inset" padding={spacing.lg}>
            <View style={styles.referralRow}>
              <Text style={[typography.h2, styles.flex, { color: colors.goldDeep }]} selectable>
                {referral}
              </Text>
              <Button3D
                title="คัดลอก"
                icon="📋"
                size="sm"
                variant="secondary"
                onPress={async () => {
                  await Clipboard.setStringAsync(referral);
                  resultHaptic('success');
                }}
              />
            </View>
          </Card3D>
        </>
      )}

      <Button3D
        title={dirty ? 'บันทึกการเปลี่ยนแปลง' : 'ยังไม่มีการเปลี่ยนแปลง'}
        icon="💾"
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
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarSection: {
    alignItems: 'center',
    marginVertical: spacing.lg,
  },
  avatarRing: {
    width: 116,
    height: 116,
    borderRadius: 58,
    padding: 4,
  },
  avatar: {
    width: 108,
    height: 108,
    borderRadius: 54,
  },
  initial: {
    fontSize: 42,
    fontWeight: '800',
  },
  camera: {
    position: 'absolute',
    right: 0,
    bottom: 0,
    width: 36,
    height: 36,
    borderRadius: 18,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cameraIcon: {
    fontSize: 16,
  },
  hint: {
    marginTop: spacing.sm,
  },
  noTop: {
    marginTop: 0,
  },
  section: {
    marginTop: spacing.xl,
  },
  referralRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  saveButton: {
    marginTop: spacing.xxl,
  },
});

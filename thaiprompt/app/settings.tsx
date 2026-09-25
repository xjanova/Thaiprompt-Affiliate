/**
 * ตั้งค่า — ธีมนวลทองคำ
 *
 * - ธีม (สว่าง / มืด / ตามเครื่อง) · ยืนยันตัวตน · การแจ้งเตือน · เว็บไซต์ · เกี่ยวกับแอป
 * - ออกจากระบบ (ถามก่อน) · ลบบัญชีในแอป (PLAY-05: เช็คเงื่อนไข → คำเตือน → พิมพ์ยืนยัน → DELETE /account)
 * - ลิงก์นโยบายชี้ไปหน้าที่มีอยู่จริงบนเว็บ (PLAY-06) · อีเมลติดต่อชุดเดียวกับเว็บ (PLAY-25)
 */

import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { openUrl } from '@/utils/navigation';
import { getAvatarInitial } from '@/utils/user';
import {
  deleteAccount,
  getAccountDeletionCheck,
  type DeletionCheck,
} from '@/services/api/accountApi';
import {
  Button3D,
  Card3D,
  ConsentSheet,
  Screen,
  SectionHeader,
  openWebsite,
  resultHaptic,
  selectionHaptic,
} from '@/components/ui';
import { useTheme, radii, spacing, toneColors, typography, type Tone } from '@/theme';

// =====================================================
// แถวตั้งค่า (อยู่ในการ์ดกลุ่มเดียวกัน คั่นด้วยเส้นบาง)
// =====================================================

const SettingRow = ({
  icon,
  tone = 'gold',
  title,
  subtitle,
  onPress,
  right,
  danger = false,
  last = false,
}: {
  icon: string;
  tone?: Tone;
  title: string;
  subtitle?: string;
  onPress?: () => void;
  /** undefined = ลูกศร (ถ้ากดได้) · null = ไม่มีอะไรด้านขวา */
  right?: React.ReactNode;
  danger?: boolean;
  last?: boolean;
}) => {
  const { colors } = useTheme();
  const t = toneColors(danger ? 'danger' : tone, colors);
  const body = (
    <View style={[styles.row, !last && { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: colors.divider }]}>
      <View style={[styles.rowIcon, { backgroundColor: t.bg }]}>
        <Text style={styles.rowEmoji}>{icon}</Text>
      </View>
      <View style={styles.flex}>
        <Text style={[typography.bodyStrong, { color: danger ? colors.danger : colors.textStrong }]}>{title}</Text>
        {!!subtitle && <Text style={[typography.caption, { color: colors.textMuted }]}>{subtitle}</Text>}
      </View>
      {right !== undefined ? right : onPress ? <Text style={[styles.chevron, { color: colors.textFaint }]}>›</Text> : null}
    </View>
  );
  if (!onPress) return body;
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={subtitle ? `${title} ${subtitle}` : title}
      style={({ pressed }) => [{ opacity: pressed ? 0.65 : 1 }]}
    >
      {body}
    </Pressable>
  );
};

const THEME_OPTIONS: Array<{ mode: 'light' | 'dark' | 'system'; icon: string; title: string; desc: string }> = [
  { mode: 'light', icon: '☀️', title: 'สว่าง', desc: 'อ่านง่ายกลางแดด' },
  { mode: 'dark', icon: '🌙', title: 'มืด', desc: 'ถนอมสายตาตอนกลางคืน' },
  { mode: 'system', icon: '📱', title: 'ตามเครื่อง', desc: 'เปลี่ยนตามการตั้งค่าของเครื่อง' },
];

const ThemePicker = ({ current, onChange }: { current: string; onChange: (m: 'light' | 'dark' | 'system') => void }) => {
  const { colors, gradients } = useTheme();
  return (
    <View style={styles.themeRow} accessibilityRole="radiogroup">
      {THEME_OPTIONS.map((opt) => {
        const selected = current === opt.mode;
        return (
          <Pressable
            key={opt.mode}
            onPress={() => {
              selectionHaptic();
              onChange(opt.mode);
            }}
            accessibilityRole="radio"
            accessibilityState={{ selected }}
            accessibilityLabel={`ธีม${opt.title}`}
            style={({ pressed }) => [styles.themeOption, { opacity: pressed ? 0.8 : 1 }]}
          >
            {selected ? (
              <LinearGradient colors={gradients.primary} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.themeInner}>
                <Text style={styles.themeIcon}>{opt.icon}</Text>
                <Text style={[typography.bodyStrong, { color: colors.textOnGold }]}>{opt.title}</Text>
              </LinearGradient>
            ) : (
              <View style={[styles.themeInner, { backgroundColor: colors.inset, borderColor: colors.border, borderWidth: 1 }]}>
                <Text style={styles.themeIcon}>{opt.icon}</Text>
                <Text style={[typography.bodyStrong, { color: colors.text }]}>{opt.title}</Text>
              </View>
            )}
          </Pressable>
        );
      })}
    </View>
  );
};

/**
 * ช่องยืนยันการลบบัญชี (พิมพ์คำยืนยัน + รหัสผ่านถ้าจำเป็น)
 */
const DeleteConfirmFields = ({
  check,
  confirmText,
  onConfirmText,
  password,
  onPassword,
  errorMessage,
}: {
  check: DeletionCheck;
  confirmText: string;
  onConfirmText: (v: string) => void;
  password: string;
  onPassword: (v: string) => void;
  errorMessage: string | null;
}) => {
  const { colors } = useTheme();
  const inputStyle = [
    deleteStyles.input,
    { backgroundColor: colors.inset, borderColor: colors.border, color: colors.textStrong },
  ];

  return (
    <View style={deleteStyles.fields}>
      <Text style={[typography.bodySm, { color: colors.text }]}>
        พิมพ์คำว่า <Text style={{ fontWeight: '800', color: colors.danger }}>{check.confirm_text}</Text> เพื่อยืนยัน
      </Text>
      <TextInput
        value={confirmText}
        onChangeText={onConfirmText}
        placeholder={check.confirm_text}
        placeholderTextColor={colors.textFaint}
        autoCorrect={false}
        autoCapitalize="none"
        style={inputStyle}
        accessibilityLabel="ช่องพิมพ์คำยืนยันการลบบัญชี"
      />
      {check.requires_password && (
        <>
          <Text style={[typography.bodySm, deleteStyles.label, { color: colors.text }]}>รหัสผ่านของคุณ</Text>
          <TextInput
            value={password}
            onChangeText={onPassword}
            placeholder="รหัสผ่าน"
            placeholderTextColor={colors.textFaint}
            secureTextEntry
            autoCapitalize="none"
            style={inputStyle}
            accessibilityLabel="รหัสผ่าน"
          />
        </>
      )}
      {!!errorMessage && (
        <Text style={[typography.bodySm, deleteStyles.error, { color: colors.danger }]} accessibilityRole="alert">
          {errorMessage}
        </Text>
      )}
    </View>
  );
};

const deleteStyles = StyleSheet.create({
  fields: {
    marginTop: spacing.lg,
  },
  label: {
    marginTop: spacing.md,
  },
  input: {
    marginTop: spacing.xs,
    borderWidth: 1,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    fontSize: 16,
  },
  error: {
    marginTop: spacing.md,
  },
  blocker: {
    marginTop: spacing.sm,
  },
});

type DeleteStep = 'closed' | 'warning' | 'confirm';

export default function SettingsScreen() {
  const themeMode = useAppStore((s) => s.themeMode);
  const setThemeMode = useAppStore((s) => s.setThemeMode);
  const { user, isAuthenticated, logout, clearSession } = useAuthStore();
  const { colors, gradients } = useTheme();

  // ลบบัญชี (PLAY-05)
  const [deleteStep, setDeleteStep] = useState<DeleteStep>('closed');
  const [deletionCheck, setDeletionCheck] = useState<DeletionCheck | null>(null);
  const [isCheckingDeletion, setIsCheckingDeletion] = useState(false);
  const [confirmText, setConfirmText] = useState('');
  const [password, setPassword] = useState('');
  const [deleteError, setDeleteError] = useState<string | null>(null);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  // PLAY-06: ลิงก์นโยบายชี้ไปหน้าที่มีอยู่จริงบนเว็บ (/privacy-policy, /terms-of-service)
  const handlePrivacyPolicy = () => {
    openUrl(APP_INFO.PRIVACY_URL, 'นโยบายความเป็นส่วนตัว', '📄');
  };

  const handleTermsOfService = () => {
    openUrl(APP_INFO.TERMS_URL, 'ข้อกำหนดการใช้งาน', '📋');
  };

  // PLAY-25: อีเมลติดต่อชุดเดียวกับเว็บ
  const handleContactSupport = () => {
    Linking.openURL(`mailto:${APP_INFO.SUPPORT_EMAIL}`).catch(() => {
      Alert.alert('ติดต่อทีมงาน', `ส่งอีเมลถึงเราได้ที่ ${APP_INFO.SUPPORT_EMAIL}`);
    });
  };

  const handleRateApp = () => {
    Linking.openURL(`market://details?id=${APP_INFO.BUNDLE_ID}`).catch(() => {
      Linking.openURL(`https://play.google.com/store/apps/details?id=${APP_INFO.BUNDLE_ID}`).catch(() => {});
    });
  };

  const handleLogout = () => {
    Alert.alert(
      'ออกจากระบบ',
      'คุณต้องการออกจากระบบหรือไม่?',
      [
        { text: 'ยกเลิก', style: 'cancel' },
        {
          text: 'ออกจากระบบ',
          style: 'destructive',
          onPress: async () => {
            await logout();
            router.replace('/login');
          },
        },
      ]
    );
  };

  // ---------- ลบบัญชี: เช็คเงื่อนไข → คำเตือน → พิมพ์ยืนยัน → DELETE /account → ออกจากระบบ ----------
  const closeDelete = () => {
    setDeleteStep('closed');
    setConfirmText('');
    setPassword('');
    setDeleteError(null);
  };

  const handleDeleteAccount = async () => {
    if (isCheckingDeletion) return;
    setIsCheckingDeletion(true);
    const result = await getAccountDeletionCheck();
    if (!mountedRef.current) return;
    setIsCheckingDeletion(false);

    if (!result.success) {
      Alert.alert('ลบบัญชี', result.message);
      return;
    }
    setDeletionCheck(result.data);
    setDeleteError(null);
    setDeleteStep('warning');
  };

  const submitDeleteAccount = async () => {
    if (!deletionCheck) return;
    setDeleteError(null);

    const result = await deleteAccount({
      confirm_text: confirmText.trim(),
      ...(deletionCheck.requires_password ? { password } : {}),
    });
    if (!mountedRef.current) return;

    if (result.success) {
      resultHaptic('success');
      closeDelete();
      // token ถูกเพิกถอนที่ server แล้ว → ล้าง session ในเครื่องอย่างเดียว
      await clearSession('ลบบัญชีเรียบร้อยแล้ว ขอบคุณที่ใช้บริการ');
      router.replace('/login');
      return;
    }

    resultHaptic('error');
    // มีเงื่อนไขค้าง (409) → อัปเดตรายการที่ต้องจัดการ แล้วกลับไปหน้าคำเตือน
    if (result.status === 409 && Array.isArray(result.data?.blockers)) {
      setDeletionCheck({ ...deletionCheck, can_delete: false, blockers: result.data.blockers });
      setDeleteStep('warning');
    }
    setDeleteError(result.message);
  };

  const canSubmitDelete =
    !!deletionCheck &&
    confirmText.trim() === deletionCheck.confirm_text &&
    (!deletionCheck.requires_password || password.length > 0);

  const themeDesc = THEME_OPTIONS.find((o) => o.mode === themeMode)?.desc || '';

  return (
    <Screen title="ตั้งค่า">
      {/* ---------- โปรไฟล์ ---------- */}
      {isAuthenticated && user && (
        <Card3D gradientBorder padding={0} style={styles.block}>
          <LinearGradient colors={gradients.hero} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.profile}>
            <View style={[styles.avatar, { backgroundColor: colors.card }]}>
              <Text style={[styles.avatarText, { color: colors.goldDeep }]}>{getAvatarInitial(user?.name)}</Text>
            </View>
            <View style={styles.flex}>
              <Text style={[typography.h2, { color: colors.textStrong }]} numberOfLines={1}>
                {user?.name || 'ไม่ระบุชื่อ'}
              </Text>
              {!!user?.email && (
                <Text style={[typography.bodySm, { color: colors.textMuted }]} numberOfLines={1}>
                  {user.email}
                </Text>
              )}
            </View>
            <Button3D title="แก้ไข" icon="✏️" size="sm" variant="secondary" onPress={() => router.push('/edit-profile')} />
          </LinearGradient>
        </Card3D>
      )}

      {/* ---------- ธีม ---------- */}
      <SectionHeader title="ธีมและการแสดงผล" icon="🎨" subtitle={themeDesc} />
      <Card3D padding={spacing.md} style={styles.block}>
        <ThemePicker current={themeMode} onChange={setThemeMode} />
        <SettingRow icon="🇹🇭" title="ภาษา" subtitle="ภาษาไทย" right={null} last />
      </Card3D>

      {/* ---------- บัญชี / การแจ้งเตือน ---------- */}
      <SectionHeader title={isAuthenticated ? 'บัญชี' : 'การแจ้งเตือน'} icon={isAuthenticated ? '👤' : '🔔'} />
      <Card3D padding={spacing.md} style={styles.block}>
        {isAuthenticated && (
          <SettingRow icon="🛡️" tone="success" title="ยืนยันตัวตน (KYC)" subtitle="ยืนยันก่อนถอนเงินเข้าบัญชี" onPress={() => router.push('/kyc')} />
        )}
        <SettingRow
          icon="🔔"
          tone="warning"
          title="การแจ้งเตือน"
          subtitle="เลือกเรื่องที่อยากได้รับแจ้ง"
          onPress={() => router.push('/notification-settings')}
          last={!isAuthenticated}
        />
        {isAuthenticated && (
          <SettingRow
            icon="🌐"
            title="จัดการบนเว็บไซต์"
            subtitle="เปิดเว็บไซต์ เข้าสู่ระบบให้อัตโนมัติ"
            onPress={() => {
              openWebsite('/user').catch(() => {});
            }}
            last
          />
        )}
      </Card3D>

      {/* ---------- เกี่ยวกับ ---------- */}
      <SectionHeader title="เกี่ยวกับ" icon="ℹ️" />
      <Card3D padding={spacing.md} style={styles.block}>
        <SettingRow icon="📄" tone="info" title="นโยบายความเป็นส่วนตัว" onPress={handlePrivacyPolicy} />
        <SettingRow icon="📋" tone="info" title="ข้อกำหนดการใช้งาน" onPress={handleTermsOfService} />
        <SettingRow icon="💬" tone="gold" title="ช่วยเหลือ / แจ้งปัญหา" subtitle="คุยกับทีมงานในแอป" onPress={() => router.push('/support')} />
        <SettingRow icon="✉️" tone="neutral" title="อีเมลทีมงาน" subtitle={APP_INFO.SUPPORT_EMAIL} onPress={handleContactSupport} />
        <SettingRow icon="⭐" tone="warning" title="ให้คะแนนแอป" onPress={handleRateApp} />
        <SettingRow icon="📦" tone="neutral" title="เวอร์ชัน" subtitle={APP_INFO.VERSION || '-'} right={null} last />
      </Card3D>

      {/* ---------- จัดการบัญชี ---------- */}
      {isAuthenticated && (
        <>
          <SectionHeader title="จัดการบัญชี" icon="⚙️" />
          <Card3D padding={spacing.md} style={styles.block}>
            <SettingRow icon="🚪" title="ออกจากระบบ" onPress={handleLogout} danger />
            <SettingRow
              icon="🗑️"
              title="ลบบัญชี"
              subtitle="ลบบัญชีและข้อมูลส่วนตัวถาวร"
              onPress={handleDeleteAccount}
              right={isCheckingDeletion ? <ActivityIndicator size="small" color={colors.danger} /> : undefined}
              danger
              last
            />
          </Card3D>
        </>
      )}

      {/* PLAY-05: ลบบัญชีในแอป — คำเตือน → พิมพ์ยืนยัน */}
      {deletionCheck && (
        <ConsentSheet
          visible={deleteStep !== 'closed'}
          icon={deleteStep === 'confirm' ? '⚠️' : '🗑️'}
          title={
            deleteStep === 'confirm'
              ? 'ยืนยันการลบบัญชี'
              : deletionCheck.can_delete
                ? 'ลบบัญชีถาวร'
                : 'ยังลบบัญชีไม่ได้'
          }
          description={
            deleteStep === 'confirm'
              ? 'ขั้นตอนสุดท้าย หลังกดยืนยันจะย้อนกลับไม่ได้'
              : deletionCheck.can_delete
                ? 'ก่อนลบ อ่านสิ่งที่จะเกิดขึ้นก่อนนะ'
                : 'จัดการรายการด้านล่างให้เรียบร้อยก่อน แล้วค่อยกลับมาลบใหม่'
          }
          reasons={
            deleteStep === 'confirm'
              ? []
              : deletionCheck.can_delete
                ? [
                    { icon: '👤', text: 'ชื่อ อีเมล เบอร์โทร และบัญชีที่เชื่อมไว้จะถูกลบหรือปกปิด' },
                    { icon: '🔒', text: 'เข้าสู่ระบบด้วยบัญชีนี้ไม่ได้อีก ทุกเครื่องจะออกจากระบบ' },
                    { icon: '🧾', text: 'ประวัติธุรกรรมการเงินเก็บไว้ตามที่กฎหมายกำหนดเท่านั้น' },
                    { icon: '↩️', text: 'ยกเลิกไม่ได้หลังยืนยัน' },
                  ]
                : deletionCheck.blockers.map((b) => ({ icon: '•', text: b.message }))
          }
          acceptLabel={
            deleteStep === 'confirm'
              ? 'ลบบัญชีของฉัน'
              : deletionCheck.can_delete
                ? 'เข้าใจแล้ว ไปต่อ'
                : 'เข้าใจแล้ว'
          }
          declineLabel={deleteStep === 'confirm' ? 'ยกเลิก' : 'ไม่ลบแล้ว'}
          acceptVariant="danger"
          acceptDisabled={deleteStep === 'confirm' && !canSubmitDelete}
          onAccept={() => {
            if (deleteStep === 'confirm') {
              return submitDeleteAccount();
            }
            if (deletionCheck.can_delete) {
              setDeleteError(null);
              setDeleteStep('confirm');
            } else {
              closeDelete();
            }
            return undefined;
          }}
          onDecline={closeDelete}
          footnote={
            deleteStep === 'warning'
              ? `อ่านวิธีลบบัญชีบนเว็บได้ที่ ${APP_INFO.ACCOUNT_DELETION_URL}`
              : undefined
          }
        >
          {deleteStep === 'confirm' ? (
            <DeleteConfirmFields
              check={deletionCheck}
              confirmText={confirmText}
              onConfirmText={setConfirmText}
              password={password}
              onPassword={setPassword}
              errorMessage={deleteError}
            />
          ) : deleteError ? (
            <Text style={[typography.bodySm, deleteStyles.blocker, { color: colors.danger }]}>{deleteError}</Text>
          ) : null}
        </ConsentSheet>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  block: {
    marginBottom: spacing.xl,
  },
  profile: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
    borderRadius: radii.xl,
  },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 24,
    fontWeight: '800',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    minHeight: 56,
    paddingVertical: spacing.sm,
  },
  rowIcon: {
    width: 38,
    height: 38,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowEmoji: {
    fontSize: 18,
  },
  chevron: {
    fontSize: 26,
    lineHeight: 28,
  },
  themeRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  themeOption: {
    flex: 1,
  },
  themeInner: {
    alignItems: 'center',
    gap: spacing.xs,
    paddingVertical: spacing.md,
    borderRadius: radii.md,
  },
  themeIcon: {
    fontSize: 22,
  },
});

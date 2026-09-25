/**
 * ตั้งค่า — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - การ์ดโปรไฟล์น้ำเงินลายกนก · เลือกธีม (สว่าง / มืด / ตามเครื่อง) เป็นการ์ดตัวอย่างหน้าจอจริงของแต่ละโหมด
 * - ยืนยันตัวตน · การแจ้งเตือน · เว็บไซต์ · เกี่ยวกับแอป (เมนูการ์ดกลุ่ม)
 * - ออกจากระบบ (ถามก่อน) · ลบบัญชีในแอป (PLAY-05: เช็คเงื่อนไข → คำเตือน → พิมพ์ยืนยัน → DELETE /account)
 * - ลิงก์นโยบายชี้ไปหน้าที่มีอยู่จริงบนเว็บ (PLAY-06) · อีเมลติดต่อชุดเดียวกับเว็บ (PLAY-25)
 */

import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, Linking, Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useAppStore } from '@/stores/appStore';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import { openUrl } from '@/utils/navigation';
import { getAvatarInitial, getAvatarUrl } from '@/utils/user';
import {
  deleteAccount,
  getAccountDeletionCheck,
  type DeletionCheck,
} from '@/services/api/accountApi';
import {
  Button3D,
  Card3D,
  ConsentSheet,
  Icon,
  OnHeaderProvider,
  RoyalHeader,
  Screen,
  openWebsite,
  resultHaptic,
  selectionHaptic,
} from '@/components/ui';
import { AvatarRing, MenuGroup, MenuRow } from '@/components/profile';
import {
  useTheme,
  DARK_THEME,
  LIGHT_THEME,
  radii,
  spacing,
  typography,
  type AppTheme,
} from '@/theme';

// =====================================================
// ตัวเลือกธีม — การ์ดตัวอย่างหน้าจอ (ใช้สีจริงของแต่ละโหมด ไม่ขึ้นกับโหมดปัจจุบัน)
// =====================================================

type ThemeChoice = 'light' | 'dark' | 'system';

const THEME_OPTIONS: Array<{ mode: ThemeChoice; title: string; desc: string }> = [
  { mode: 'light', title: 'สว่าง', desc: 'อ่านง่ายกลางแดด' },
  { mode: 'dark', title: 'มืด', desc: 'ถนอมสายตาตอนกลางคืน' },
  { mode: 'system', title: 'ตามเครื่อง', desc: 'เปลี่ยนตามการตั้งค่าของเครื่อง' },
];

/** หน้าจอจำลองขนาดเล็ก: หัวน้ำเงิน + แผ่นเนื้อหา + การ์ด 2 ใบ */
const MiniScreen = ({ theme, style }: { theme: AppTheme; style?: StyleProp<ViewStyle> }) => (
  <View style={[styles.mini, { backgroundColor: theme.colors.background }, style]}>
    <LinearGradient colors={theme.gradients.hero} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.miniHead}>
      <View style={[styles.miniDot, { backgroundColor: theme.colors.gold }]} />
      <View style={[styles.miniLine, { backgroundColor: theme.colors.onHeaderMuted }]} />
    </LinearGradient>
    <View style={[styles.miniSheet, { backgroundColor: theme.colors.background }]}>
      {[theme.colors.navySoft, theme.colors.goldSoft].map((tile) => (
        <View key={tile} style={[styles.miniCard, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <View style={[styles.miniTile, { backgroundColor: tile }]} />
          <View style={[styles.miniText, { backgroundColor: theme.colors.textFaint }]} />
        </View>
      ))}
    </View>
  </View>
);

const ThemePreview = ({ mode }: { mode: ThemeChoice }) => {
  if (mode === 'system') {
    // ตามเครื่อง = ครึ่งสว่าง ครึ่งมืด
    return (
      <View style={[styles.mini, styles.split]}>
        <MiniScreen theme={LIGHT_THEME} style={styles.half} />
        <MiniScreen theme={DARK_THEME} style={styles.half} />
      </View>
    );
  }
  return <MiniScreen theme={mode === 'dark' ? DARK_THEME : LIGHT_THEME} />;
};

const ThemePicker = ({ current, onChange }: { current: string; onChange: (m: ThemeChoice) => void }) => {
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
            accessibilityState={{ checked: selected }}
            accessibilityLabel={`ธีม${opt.title}`}
            style={({ pressed }) => [styles.themeOption, { opacity: pressed ? 0.85 : 1 }]}
          >
            <View
              style={[
                styles.themeCard,
                {
                  backgroundColor: selected ? colors.goldSoft : colors.inset,
                  borderColor: selected ? colors.gold : colors.border,
                  borderWidth: selected ? 1.5 : 1,
                },
              ]}
            >
              <ThemePreview mode={opt.mode} />
              <Text
                numberOfLines={1}
                adjustsFontSizeToFit
                style={[styles.themeTitle, { color: selected ? colors.goldDeep : colors.text }]}
              >
                {opt.title}
              </Text>
              {selected && (
                <LinearGradient colors={gradients.primary} start={{ x: 0, y: 0 }} end={{ x: 0, y: 1 }} style={styles.themeCheck}>
                  <Icon name="check" size={12} color={colors.textOnGold} weight="bold" />
                </LinearGradient>
              )}
            </View>
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
    typography.body,
    deleteStyles.input,
    { backgroundColor: colors.inset, borderColor: colors.border, color: colors.textStrong },
  ];

  return (
    <View style={deleteStyles.fields}>
      <Text style={[typography.bodySm, { color: colors.text }]}>
        พิมพ์คำว่า <Text style={{ fontWeight: '700', color: colors.danger }}>{check.confirm_text}</Text> เพื่อยืนยัน
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
        <View style={[deleteStyles.errorBox, { backgroundColor: colors.dangerSoft }]}>
          <Icon name="warning-circle" size={18} color={colors.danger} weight="fill" />
          <Text style={[typography.bodySm, deleteStyles.errorText, { color: colors.danger }]} accessibilityRole="alert">
            {errorMessage}
          </Text>
        </View>
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
    minHeight: 48,
    borderWidth: 1,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.md,
    borderRadius: radii.md,
  },
  errorText: {
    flex: 1,
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
  const { colors } = useTheme();

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
  // (ไม่ส่งไอคอนอีโมจิให้หน้าเปิดเว็บแล้ว — แอปเลิกใช้อีโมจิเป็นไอคอน)
  const handlePrivacyPolicy = () => {
    openUrl(APP_INFO.PRIVACY_URL, 'นโยบายความเป็นส่วนตัว');
  };

  const handleTermsOfService = () => {
    openUrl(APP_INFO.TERMS_URL, 'ข้อกำหนดการใช้งาน');
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
      {/* ---------- โปรไฟล์ (การ์ดน้ำเงินลายกนก) ---------- */}
      {isAuthenticated && user && (
        <Card3D gradientBorder padding={0} style={styles.block}>
          <RoyalHeader ornamentWidth={150} ornamentTop={-34} style={styles.profile}>
            <AvatarRing
              uri={getAvatarUrl(user?.avatar)}
              initial={getAvatarInitial(user?.name)}
              size={60}
              gapColor={colors.navyDeep}
            />
            <View style={styles.flex}>
              <Text style={[typography.serifSm, { color: colors.onHeader }]} numberOfLines={1}>
                {user?.name || 'ไม่ระบุชื่อ'}
              </Text>
              {!!user?.email && (
                <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]} numberOfLines={1}>
                  {user.email}
                </Text>
              )}
            </View>
            <OnHeaderProvider value>
              <Button3D
                title="แก้ไข"
                icon="pencil-simple"
                size="sm"
                variant="secondary"
                onPress={() => router.push('/edit-profile')}
              />
            </OnHeaderProvider>
          </RoyalHeader>
        </Card3D>
      )}

      {/* ---------- ธีม ---------- */}
      <MenuGroup title="ธีมและการแสดงผล" subtitle={themeDesc}>
        <View style={styles.themeWrap}>
          <ThemePicker current={themeMode} onChange={setThemeMode} />
        </View>
        <MenuRow icon="translate" title="ภาษา" subtitle="ภาษาไทย" right={null} />
      </MenuGroup>

      {/* ---------- บัญชี / การแจ้งเตือน ---------- */}
      <MenuGroup title={isAuthenticated ? 'บัญชี' : 'การแจ้งเตือน'}>
        {isAuthenticated && (
          <MenuRow
            icon="shield-check"
            title="ยืนยันตัวตน (KYC)"
            subtitle="ยืนยันก่อนถอนเงินเข้าบัญชี"
            onPress={() => router.push('/kyc')}
          />
        )}
        <MenuRow
          icon="bell"
          title="การแจ้งเตือน"
          subtitle="เลือกเรื่องที่อยากได้รับแจ้ง"
          onPress={() => router.push('/notification-settings')}
        />
        {isAuthenticated && (
          <MenuRow
            icon="globe"
            title="จัดการบนเว็บไซต์"
            subtitle="เปิดเว็บไซต์ เข้าสู่ระบบให้อัตโนมัติ"
            onPress={() => {
              openWebsite('/user').catch(() => {});
            }}
          />
        )}
      </MenuGroup>

      {/* ---------- เกี่ยวกับ ---------- */}
      <MenuGroup title="เกี่ยวกับ">
        <MenuRow icon="lock-key" title="นโยบายความเป็นส่วนตัว" onPress={handlePrivacyPolicy} />
        <MenuRow icon="file-text" title="ข้อกำหนดการใช้งาน" onPress={handleTermsOfService} />
        <MenuRow
          icon="chat-circle-dots"
          title="ช่วยเหลือ / แจ้งปัญหา"
          subtitle="คุยกับทีมงานในแอป"
          onPress={() => router.push('/support')}
        />
        <MenuRow icon="envelope" title="อีเมลทีมงาน" subtitle={APP_INFO.SUPPORT_EMAIL} onPress={handleContactSupport} />
        <MenuRow icon="star" tone="gold" title="ให้คะแนนแอป" onPress={handleRateApp} />
        <MenuRow icon="info" title="เวอร์ชัน" subtitle={APP_INFO.VERSION || '-'} right={null} />
      </MenuGroup>

      {/* ---------- จัดการบัญชี ---------- */}
      {isAuthenticated && (
        <MenuGroup title="จัดการบัญชี">
          <MenuRow icon="sign-out" title="ออกจากระบบ" onPress={handleLogout} danger />
          <MenuRow
            icon="trash"
            title="ลบบัญชี"
            subtitle="ลบบัญชีและข้อมูลส่วนตัวถาวร"
            onPress={handleDeleteAccount}
            right={isCheckingDeletion ? <ActivityIndicator size="small" color={colors.danger} /> : undefined}
            danger
          />
        </MenuGroup>
      )}

      {/* PLAY-05: ลบบัญชีในแอป — คำเตือน → พิมพ์ยืนยัน */}
      {deletionCheck && (
        <ConsentSheet
          visible={deleteStep !== 'closed'}
          icon={deleteStep === 'confirm' ? 'warning' : 'trash'}
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
                    { icon: 'user', text: 'ชื่อ อีเมล เบอร์โทร และบัญชีที่เชื่อมไว้จะถูกลบหรือปกปิด' },
                    { icon: 'lock', text: 'เข้าสู่ระบบด้วยบัญชีนี้ไม่ได้อีก ทุกเครื่องจะออกจากระบบ' },
                    { icon: 'receipt', text: 'ประวัติธุรกรรมการเงินเก็บไว้ตามที่กฎหมายกำหนดเท่านั้น' },
                    { icon: 'arrow-counter-clockwise', text: 'ยกเลิกไม่ได้หลังยืนยัน' },
                  ]
                : deletionCheck.blockers.map((b) => ({ icon: 'warning-circle', text: b.message }))
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
    marginBottom: spacing.xxl,
  },
  profile: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.lg,
    borderRadius: radii.xl - 1.5,
  },

  // ---------- ตัวเลือกธีม ----------
  themeWrap: {
    padding: spacing.md,
  },
  themeRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  themeOption: {
    flex: 1,
  },
  themeCard: {
    borderRadius: radii.lg,
    padding: spacing.sm,
    gap: spacing.sm,
  },
  themeTitle: {
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '600',
    textAlign: 'center',
    paddingBottom: 2,
  },
  themeCheck: {
    position: 'absolute',
    top: spacing.xs,
    right: spacing.xs,
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },

  // ---------- หน้าจอจำลอง ----------
  mini: {
    height: 76,
    borderRadius: radii.sm,
    overflow: 'hidden',
  },
  split: {
    flexDirection: 'row',
  },
  half: {
    flex: 1,
    height: '100%',
    borderRadius: 0,
  },
  miniHead: {
    height: 24,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 7,
    paddingBottom: 5,
  },
  miniDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  miniLine: {
    width: '40%',
    height: 3,
    borderRadius: 2,
  },
  miniSheet: {
    flex: 1,
    marginTop: -6,
    borderTopLeftRadius: 6,
    borderTopRightRadius: 6,
    paddingHorizontal: 6,
    paddingTop: 6,
    gap: 4,
  },
  miniCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    height: 16,
    borderRadius: 4,
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 4,
  },
  miniTile: {
    width: 8,
    height: 8,
    borderRadius: 2.5,
  },
  miniText: {
    flex: 1,
    height: 3,
    borderRadius: 2,
    opacity: 0.6,
  },
});

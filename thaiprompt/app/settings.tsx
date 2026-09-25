/**
 * Settings Screen - หน้าตั้งค่า
 * UI สวยงามแบบ Glassmorphism พร้อม SVG Icons
 * ปิด AnimatedBackground ชั่วคราวเพื่อทดสอบ crash
 */

import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Pressable,
  StyleSheet,
  Alert,
  StatusBar,
  ActivityIndicator,
  TextInput,
  Linking,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BlurView } from 'expo-blur';
import { router } from 'expo-router';

// ปิด AnimatedBackground ชั่วคราวเพื่อทดสอบ crash
// import { AnimatedBackground } from '@/components/AnimatedBackground';
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
import { ConsentSheet, openWebsite, resultHaptic } from '@/components/ui';
import { useTheme, spacing, radii, typography } from '@/theme';

// Import SVG Icons
import {
  Icon,
  ArrowBackIcon,
  SunIcon,
  MoonIcon,
  PhoneIcon,
  GlobeIcon,
  ShieldCheckIcon,
  BellIcon,
  MailIcon,
  FingerprintIcon,
  LocationIcon,
  NavigationIcon,
  FileTextIcon,
  ShieldIcon,
  StarIcon,
  InfoIcon,
  LogOutIcon,
  TrashIcon,
  EditIcon,
  ChevronRightIcon,
  CheckCircleIcon,
  SettingsIcon,
} from '@/components/icons';

// Setting Item Component - ใช้ SVG Icons
const SettingItem = ({
  icon: IconComponent,
  iconColor,
  title,
  subtitle,
  onPress,
  rightElement,
  isDark,
}: {
  icon?: React.FC<{ size?: number; color?: string }>;
  iconColor?: string;
  title: string;
  subtitle?: string;
  onPress?: () => void;
  rightElement?: React.ReactNode;
  isDark: boolean;
}) => (
  <Pressable
    onPress={onPress}
    style={({ pressed }) => [
      styles.settingItem,
      {
        backgroundColor: isDark
          ? pressed ? 'rgba(255,255,255,0.1)' : 'rgba(255,255,255,0.05)'
          : pressed ? '#F3F4F6' : '#FFFFFF',
        transform: [{ scale: pressed ? 0.98 : 1 }],
      },
    ]}
  >
    <LinearGradient
      colors={iconColor ? [`${iconColor}30`, `${iconColor}10`] : ['rgba(212,175,55,0.2)', 'rgba(212,175,55,0.1)']}
      style={styles.iconContainer}
    >
      {IconComponent && <IconComponent size={22} color={iconColor || '#D4AF37'} />}
    </LinearGradient>
    <View style={styles.settingContent}>
      <Text style={[styles.settingTitle, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
        {title}
      </Text>
      {subtitle && (
        <Text style={[styles.settingSubtitle, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
          {subtitle}
        </Text>
      )}
    </View>
    {rightElement || (
      <ChevronRightIcon size={20} color={isDark ? '#666666' : '#999999'} />
    )}
  </Pressable>
);

// Section Header
const SectionHeader = ({ title, isDark }: { title: string; isDark: boolean }) => (
  <Text style={[styles.sectionHeader, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
    {title}
  </Text>
);

// Theme Option - ใช้ SVG Icons
const ThemeOption = ({
  mode,
  currentMode,
  IconComponent,
  title,
  description,
  onPress,
  isDark,
}: {
  mode: 'light' | 'dark' | 'system';
  currentMode: string;
  IconComponent: React.FC<{ size?: number; color?: string }>;
  title: string;
  description: string;
  onPress: () => void;
  isDark: boolean;
}) => {
  const isSelected = currentMode === mode;

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.themeOption,
        {
          backgroundColor: isSelected
            ? isDark
              ? 'rgba(59, 130, 246, 0.2)'
              : 'rgba(59, 130, 246, 0.1)'
            : isDark
            ? 'rgba(255,255,255,0.05)'
            : '#FFF',
          borderColor: isSelected
            ? '#3B82F6'
            : isDark
            ? 'rgba(255,255,255,0.1)'
            : '#E5E7EB',
          transform: [{ scale: pressed ? 0.98 : 1 }],
        },
      ]}
    >
      <LinearGradient
        colors={isSelected
          ? mode === 'dark'
            ? ['#7B2CBF', '#5B21B6']
            : mode === 'light'
            ? ['#FFB300', '#F59E0B']
            : ['#3B82F6', '#2563EB']
          : isDark
          ? ['rgba(255,255,255,0.1)', 'rgba(255,255,255,0.05)']
          : ['#F3F4F6', '#E5E7EB']
        }
        style={styles.themeIconContainer}
      >
        <IconComponent size={24} color={isSelected ? '#FFFFFF' : isDark ? '#9CA3AF' : '#6B7280'} />
      </LinearGradient>
      <View style={styles.themeContent}>
        <Text
          style={[
            styles.themeTitle,
            { color: isSelected ? '#3B82F6' : isDark ? '#FFFFFF' : '#1F2937' },
          ]}
        >
          {title}
        </Text>
        <Text style={[styles.themeDescription, { color: isDark ? '#9CA3AF' : '#6B7280' }]}>
          {description}
        </Text>
      </View>
      {isSelected && (
        <CheckCircleIcon size={22} color="#3B82F6" />
      )}
    </Pressable>
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
  const { resolvedTheme, themeMode, setThemeMode } = useAppStore();
  const { user, isAuthenticated, logout, clearSession } = useAuthStore();
  const isDark = resolvedTheme === 'dark';
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

  // Handlers
  const handleKYC = () => {
    router.push('/kyc');
  };

  const handleLanguageChange = () => {
    Alert.alert(
      'เลือกภาษา',
      'ขณะนี้รองรับเฉพาะภาษาไทย',
      [
        { text: 'ไทย (เริ่มต้น)', onPress: () => {} },
        { text: 'English (เร็วๆ นี้)', onPress: () => {} },
        { text: 'ยกเลิก', style: 'cancel' },
      ]
    );
  };

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

  return (
    <View style={styles.container}>
      {/* Gradient Background แทน AnimatedBackground ชั่วคราว */}
      <LinearGradient
        colors={isDark ? ['#0F0F23', '#1a1a2e', '#16213e'] : ['#F9FAFB', '#F3F4F6', '#E5E7EB']}
        style={StyleSheet.absoluteFill}
      />
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

        {/* Header with Glassmorphism */}
        <BlurView
          intensity={isDark ? 40 : 60}
          tint={isDark ? 'dark' : 'light'}
          style={[styles.header, { borderBottomColor: isDark ? 'rgba(255,255,255,0.1)' : '#E5E7EB' }]}
        >
          <Pressable style={styles.backButton} onPress={() => router.back()}>
            <ArrowBackIcon size={24} color={isDark ? '#FFF' : '#1F2937'} />
          </Pressable>
          <View style={styles.headerTitleRow}>
            <SettingsIcon size={20} color={isDark ? '#FFF' : '#1F2937'} />
            <Text style={[styles.headerTitle, { color: isDark ? '#FFF' : '#1F2937' }]}>
              ตั้งค่า
            </Text>
          </View>
          <View style={styles.placeholder} />
        </BlurView>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Profile Header */}
        {isAuthenticated && user && (
          <View style={styles.profileCard}>
            <LinearGradient
              colors={isDark ? ['#7B2CBF', '#3B82F6'] : ['#3B82F6', '#60A5FA']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.profileGradient}
            >
              {/* ⭐ เพิ่ม null check เพื่อป้องกัน crash */}
              <View style={styles.avatarContainer}>
                <Text style={styles.avatarText}>
                  {getAvatarInitial(user?.name)}
                </Text>
              </View>
              <Text style={styles.profileName}>{user?.name || 'ไม่ระบุชื่อ'}</Text>
              <Text style={styles.profileEmail}>{user?.email || ''}</Text>
              <Pressable
                onPress={() => router.push('/(tabs)/profile')}
                style={({ pressed }) => [
                  styles.editProfileButton,
                  { opacity: pressed ? 0.8 : 1, transform: [{ scale: pressed ? 0.95 : 1 }] }
                ]}
              >
                <EditIcon size={16} color="#FFFFFF" />
                <Text style={styles.editProfileText}>แก้ไขโปรไฟล์</Text>
              </Pressable>
            </LinearGradient>
          </View>
        )}

        {/* Theme Settings */}
        <SectionHeader title="🎨 ธีมและการแสดงผล" isDark={isDark} />
        <View style={[styles.card, { backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : '#FFF' }]}>
          <Text style={[styles.cardTitle, { color: isDark ? '#FFFFFF' : '#1F2937' }]}>
            เลือกธีม
          </Text>

          <ThemeOption
            mode="light"
            currentMode={themeMode}
            IconComponent={SunIcon}
            title="โหมดสว่าง"
            description="สีสันสดใส เหมาะสำหรับกลางวัน"
            onPress={() => setThemeMode('light')}
            isDark={isDark}
          />

          <ThemeOption
            mode="dark"
            currentMode={themeMode}
            IconComponent={MoonIcon}
            title="โหมดมืด"
            description="ธีมมืดถนอมสายตา"
            onPress={() => setThemeMode('dark')}
            isDark={isDark}
          />

          <ThemeOption
            mode="system"
            currentMode={themeMode}
            IconComponent={PhoneIcon}
            title="ตามระบบ"
            description="เปลี่ยนตามการตั้งค่าของอุปกรณ์"
            onPress={() => setThemeMode('system')}
            isDark={isDark}
          />
        </View>

        <SettingItem
          icon={GlobeIcon}
          iconColor="#f093fb"
          title="ภาษา"
          subtitle="ไทย"
          onPress={handleLanguageChange}
          isDark={isDark}
        />

        {/* Account Settings */}
        <SectionHeader title="👤 บัญชี" isDark={isDark} />
        <SettingItem
          icon={ShieldCheckIcon}
          iconColor="#10B981"
          title="ยืนยันตัวตน (KYC)"
          subtitle="ยืนยันตัวตนก่อนถอนเงินเข้าบัญชี"
          onPress={handleKYC}
          isDark={isDark}
        />

        {/* Notifications — ตั้งค่าจริงอยู่หน้า notification-settings
            (PLAY-13: ถอด "แชร์ตำแหน่งให้แอดมิน" ออก — ตำแหน่งใช้เฉพาะไรเดอร์ระหว่างส่งงาน) */}
        <SectionHeader title="🔔 การแจ้งเตือน" isDark={isDark} />
        <SettingItem
          icon={BellIcon}
          iconColor="#FF6B6B"
          title="ตั้งค่าการแจ้งเตือน"
          subtitle="เลือกประเภทการแจ้งเตือนที่อยากได้รับ"
          onPress={() => router.push('/notification-settings')}
          isDark={isDark}
        />

        {/* Website */}
        {isAuthenticated && (
          <>
            <SectionHeader title="🌐 เว็บไซต์" isDark={isDark} />
            <SettingItem
              icon={GlobeIcon}
              iconColor="#E6B347"
              title="จัดการบนเว็บไซต์"
              subtitle="เปิดเว็บไซต์ในเบราว์เซอร์ ล็อกอินให้อัตโนมัติ"
              onPress={() => {
                openWebsite('/user').catch(() => {});
              }}
              isDark={isDark}
            />
          </>
        )}

        {/* About */}
        <SectionHeader title="ℹ️ เกี่ยวกับ" isDark={isDark} />
        <SettingItem
          icon={FileTextIcon}
          iconColor="#3B82F6"
          title="นโยบายความเป็นส่วนตัว"
          onPress={handlePrivacyPolicy}
          isDark={isDark}
        />
        <SettingItem
          icon={ShieldIcon}
          iconColor="#8B5CF6"
          title="ข้อกำหนดการใช้งาน"
          onPress={handleTermsOfService}
          isDark={isDark}
        />
        <SettingItem
          icon={MailIcon}
          iconColor="#4ECDC4"
          title="ติดต่อทีมงาน"
          subtitle={APP_INFO.SUPPORT_EMAIL}
          onPress={handleContactSupport}
          isDark={isDark}
        />
        <SettingItem
          icon={StarIcon}
          iconColor="#FFD700"
          title="ให้คะแนนแอพ"
          onPress={handleRateApp}
          isDark={isDark}
        />
        <SettingItem
          icon={InfoIcon}
          iconColor="#6B7280"
          title="เวอร์ชัน"
          subtitle={APP_INFO.VERSION || '1.1.0'}
          isDark={isDark}
          rightElement={<View />}
        />

        {/* Account Actions */}
        {isAuthenticated && (
          <>
            <SectionHeader title="⚙️ จัดการบัญชี" isDark={isDark} />
            <SettingItem
              icon={LogOutIcon}
              iconColor="#FF6B6B"
              title="ออกจากระบบ"
              onPress={handleLogout}
              isDark={isDark}
            />
            <SettingItem
              icon={TrashIcon}
              iconColor="#DC143C"
              title="ลบบัญชี"
              subtitle="ลบบัญชีและข้อมูลส่วนตัวของคุณอย่างถาวร"
              onPress={handleDeleteAccount}
              rightElement={
                isCheckingDeletion ? <ActivityIndicator size="small" color="#DC143C" /> : undefined
              }
              isDark={isDark}
            />
          </>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>

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
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 12,
    borderBottomWidth: 1,
  },
  backButton: {
    padding: 8,
  },
  headerTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  placeholder: {
    width: 40,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 16,
  },
  profileCard: {
    marginBottom: 24,
    borderRadius: 20,
    overflow: 'hidden',
  },
  profileGradient: {
    padding: 24,
    alignItems: 'center',
  },
  avatarContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(255,255,255,0.3)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  profileName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  profileEmail: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginBottom: 16,
  },
  editProfileButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 8,
    backgroundColor: 'rgba(255,255,255,0.2)',
    borderRadius: 20,
    gap: 6,
  },
  editProfileText: {
    fontSize: 14,
    color: '#FFFFFF',
    fontWeight: '600',
  },
  sectionHeader: {
    fontSize: 13,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginTop: 16,
    marginBottom: 12,
    marginLeft: 4,
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 12,
    marginBottom: 8,
  },
  iconContainer: {
    width: 40,
    height: 40,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  settingContent: {
    flex: 1,
  },
  settingTitle: {
    fontSize: 15,
    fontWeight: '600',
  },
  settingSubtitle: {
    fontSize: 12,
    marginTop: 2,
  },
  card: {
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 16,
  },
  themeOption: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 16,
    marginBottom: 12,
    borderWidth: 2,
  },
  themeIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
  },
  themeContent: {
    flex: 1,
  },
  themeTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 2,
  },
  themeDescription: {
    fontSize: 12,
  },
  gpsStatusCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 12,
    marginBottom: 8,
    gap: 8,
  },
  gpsStatusText: {
    fontSize: 13,
    fontWeight: '500',
  },
});

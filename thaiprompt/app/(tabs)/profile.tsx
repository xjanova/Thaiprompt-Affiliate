/**
 * โปรไฟล์ — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - หัวน้ำเงินลายกนก: รูปโปรไฟล์ในวงแหวนทอง (แตะเปลี่ยนรูป) · ชื่อฟอนต์มีเชิง · บทบาท · รหัสแนะนำ (แตะคัดลอก)
 *   + บัตรสมาชิกกระจก (รหัสสมาชิก · คัดลอก · แชร์)
 * - เนื้อหาบนแผ่นงาช้าง: เมนูเป็นการ์ดกลุ่ม (บัญชี · บริการของฉัน · ชวนเพื่อน · เว็บไซต์ · ตั้งค่า · ช่วยเหลือ)
 * - รูปโปรไฟล์ sync กับเว็บ · เปลี่ยนรหัสผ่านในแผ่นล่าง · สวิตช์โหมดมืด (บันทึกใน useAppStore.themeMode)
 * - รหัสแนะนำ = referralCode ของผู้ใช้ (ไม่มี = รหัสสมาชิก) · ชวนเพื่อนแบบชั้นเดียว ไม่มีทีม/สายงาน
 */

import React, { useState } from 'react';
import {
  View,
  ScrollView,
  Pressable,
  Alert,
  RefreshControl,
  StyleSheet,
  StatusBar,
  Modal,
  KeyboardAvoidingView,
  Share,
} from 'react-native';
import { Text, TextInput } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, { SlideInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import * as Clipboard from 'expo-clipboard';
import { useAuthStore } from '@/stores/authStore';
import { useAppStore } from '@/stores/appStore';
import { uploadAvatar, changePassword } from '@/services/api';
import { APP_INFO, isFeatureEnabled } from '@/config/appConfig';
import { openWebsite } from '@/components/ui/WebsiteButton';
import { getAvatarUrl, getAvatarInitial, formatMemberId } from '@/utils/user';
import { isTrustedWebUrl } from '@/utils/linking';
import {
  BrandArt,
  Button3D,
  EmptyState,
  GlassIconButton,
  Icon,
  IconButton,
  OnHeaderProvider,
  Pill,
  RoyalHeader,
  Screen,
  tapHaptic,
  type IconName,
} from '@/components/ui';
import { AvatarRing, IconTile, MenuGroup, MenuRow, ThemedSwitch } from '@/components/profile';
import { useTheme, radii, shadowStyle, spacing, typography, withAlpha } from '@/theme';
import { KANOK_CREST_CLEARANCE } from '@/components/ui/KanokTabBar';

/** ความโค้งของแผ่นเนื้อหาใต้หัวน้ำเงิน (เท่ากับ <Screen>) */
const SHEET_RADIUS = 26;

/** ชื่อบทบาทภาษาไทย (server ส่งเป็นคีย์ภาษาอังกฤษ) — ไม่รู้จัก = แสดงตามที่ได้มา */
const ROLE_LABEL: Record<string, string> = {
  user: 'สมาชิก',
  member: 'สมาชิก',
  seller: 'ร้านค้า',
  merchant: 'ร้านค้า',
  rider: 'ไรเดอร์',
  admin: 'ผู้ดูแลระบบ',
  super_admin: 'ผู้ดูแลระบบ',
};

const roleLabel = (role?: string): string => (role ? ROLE_LABEL[role] || role : 'สมาชิก');

// =====================================================
// ปุ่มกระจกในบัตรสมาชิก (ไอคอนทอง + ข้อความ)
// =====================================================

const GlassAction = ({
  icon,
  label,
  onPress,
  accessibilityLabel,
}: {
  icon: IconName;
  label: string;
  onPress: () => void;
  accessibilityLabel?: string;
}) => {
  const { colors } = useTheme();
  return (
    <Pressable
      onPress={() => {
        tapHaptic();
        onPress();
      }}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? label}
      style={({ pressed }) => [
        styles.glassAction,
        {
          backgroundColor: colors.headerGlass,
          borderColor: colors.headerGlassBorder,
          opacity: pressed ? 0.7 : 1,
        },
      ]}
    >
      <Icon name={icon} size={18} color={colors.goldLight} />
      <Text style={[typography.bodyStrong, { color: colors.onHeader }]}>{label}</Text>
    </Pressable>
  );
};

// =====================================================
// ช่องรหัสผ่าน (พื้นยุบ + ขอบทองตอนโฟกัส + ปุ่มแสดง/ซ่อน)
// =====================================================

const PasswordField = ({
  label,
  value,
  onChangeText,
  placeholder,
  visible,
  onToggleVisible,
}: {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder: string;
  visible: boolean;
  /** ไม่ส่ง = ไม่มีปุ่มแสดง/ซ่อน */
  onToggleVisible?: () => void;
}) => {
  const { colors } = useTheme();
  const [focused, setFocused] = useState(false);
  return (
    <View style={styles.inputGroup}>
      <Text style={[typography.caption, styles.inputLabel, { color: colors.textMuted }]}>{label}</Text>
      <View
        style={[
          styles.inputBox,
          { backgroundColor: colors.inset, borderColor: focused ? colors.gold : colors.border },
        ]}
      >
        <Icon name="lock" size={18} color={focused ? colors.goldDeep : colors.textFaint} />
        <TextInput
          style={[typography.body, styles.input, { color: colors.textStrong }]}
          placeholder={placeholder}
          placeholderTextColor={colors.textFaint}
          secureTextEntry={!visible}
          value={value}
          onChangeText={onChangeText}
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
        />
        {!!onToggleVisible && (
          <Pressable
            onPress={onToggleVisible}
            hitSlop={10}
            accessibilityRole="button"
            accessibilityLabel={visible ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
          >
            <Icon name={visible ? 'eye-slash' : 'eye'} size={20} color={colors.textMuted} />
          </Pressable>
        )}
      </View>
    </View>
  );
};

export default function ProfileScreen() {
  const { user, isAuthenticated, logout, refreshUser, updateUser } = useAuthStore();
  const themeMode = useAppStore((state) => state.themeMode);
  const setThemeMode = useAppStore((state) => state.setThemeMode);
  const { colors, gradients, isDark } = useTheme();
  const insets = useSafeAreaInsets();

  const [refreshing, setRefreshing] = useState(false);
  const [isUploading, setIsUploading] = useState(false);

  // Password Modal State
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  /** ปิดแผ่นเปลี่ยนรหัสผ่าน + ล้างค่าที่พิมพ์ ไม่ให้รหัสผ่านค้างอยู่เมื่อเปิดใหม่ */
  const closePasswordModal = () => {
    setShowPasswordModal(false);
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
  };
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);

  // Member ID as referral code
  const memberCode = user?.id ? formatMemberId(user.id) : '';
  // ใช้ referralCode จาก user ถ้ามี หรือใช้ memberCode
  const referralCode = user?.referralCode || memberCode;
  /** มีรหัสแนะนำของตัวเองจาก server (คนละค่ากับรหัสสมาชิก) — บัตรต้องโชว์รหัสเดียวกับที่คัดลอก/แชร์ */
  const hasOwnReferralCode = !!user?.referralCode && user.referralCode !== memberCode;
  /** ลิงก์สมัคร: ใช้ลิงก์จาก server ก่อน (ตรงกับหน้าชวนเพื่อน) ไม่มีค่อยสร้างจากรหัส */
  const referralLink =
    user?.referralLink && isTrustedWebUrl(user.referralLink)
      ? user.referralLink
      : `${APP_INFO.WEBSITE}/register?ref=${encodeURIComponent(referralCode)}`;

  // แปลง path รูปจาก API เป็น URL เต็มด้วยฟังก์ชันกลาง
  const avatarUrl = getAvatarUrl(user?.avatar);

  const onRefresh = async () => {
    setRefreshing(true);
    await refreshUser();
    setRefreshing(false);
  };

  // Handle pick image
  const handlePickImage = async () => {
    try {
      // เลือกรูปผ่านตัวเลือกรูปของระบบ — ไม่ต้องขอสิทธิ์อ่านรูปทั้งเครื่อง (เหมือนหน้าแก้ไขโปรไฟล์/KYC)
      console.log('Launching image library...');
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'], // ใช้ syntax ใหม่ของ expo-image-picker v16
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      });

      if (!result.canceled && result.assets[0]) {
        setIsUploading(true);
        const response = await uploadAvatar(result.assets[0].uri);

        if (response.success && response.data) {
          // อัพเดท user ใน store ด้วยข้อมูลจาก API
          if (response.data.user) {
            updateUser(response.data.user);
          } else if (response.data.avatarUrl) {
            updateUser({ avatar: response.data.avatarUrl });
          }

          // Refresh user data จาก server เพื่อให้แน่ใจว่าข้อมูลตรงกัน
          await refreshUser();

          Alert.alert('สำเร็จ', 'เปลี่ยนรูปโปรไฟล์แล้ว');
        } else {
          Alert.alert('ผิดพลาด', response.message || 'ไม่สามารถอัพโหลดรูปได้');
        }
        setIsUploading(false);
      }
    } catch (error: any) {
      console.error('Pick image error:', error);
      setIsUploading(false);
      Alert.alert('ผิดพลาด', error?.message || 'เกิดข้อผิดพลาดในการเลือกรูป');
    }
  };

  // Handle take photo
  const handleTakePhoto = async () => {
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      console.log('Camera permission:', status);
      if (status !== 'granted') {
        Alert.alert('ต้องการสิทธิ์', 'กรุณาอนุญาตให้ใช้กล้องในการตั้งค่า');
        return;
      }

      console.log('Launching camera...');
      const result = await ImagePicker.launchCameraAsync({
        mediaTypes: ['images'], // ใช้ syntax ใหม่ของ expo-image-picker v16
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      });

      if (!result.canceled && result.assets[0]) {
        setIsUploading(true);
        const response = await uploadAvatar(result.assets[0].uri);

        if (response.success && response.data) {
          // อัพเดท user ใน store ด้วยข้อมูลจาก API
          if (response.data.user) {
            updateUser(response.data.user);
          } else if (response.data.avatarUrl) {
            updateUser({ avatar: response.data.avatarUrl });
          }

          // Refresh user data จาก server เพื่อให้แน่ใจว่าข้อมูลตรงกัน
          await refreshUser();

          Alert.alert('สำเร็จ', 'เปลี่ยนรูปโปรไฟล์แล้ว');
        } else {
          Alert.alert('ผิดพลาด', response.message || 'ไม่สามารถอัพโหลดรูปได้');
        }
        setIsUploading(false);
      }
    } catch (error) {
      setIsUploading(false);
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการถ่ายรูป');
    }
  };

  // Show image picker options
  const showImageOptions = () => {
    Alert.alert(
      'เปลี่ยนรูปโปรไฟล์',
      'เลือกวิธีการเปลี่ยนรูป',
      [
        { text: 'ถ่ายรูป', onPress: handleTakePhoto },
        { text: 'เลือกจากอัลบั้ม', onPress: handlePickImage },
        { text: 'ยกเลิก', style: 'cancel' },
      ]
    );
  };

  // Handle change password
  const handleChangePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      Alert.alert('กรุณากรอกข้อมูล', 'กรุณากรอกรหัสผ่านให้ครบทุกช่อง');
      return;
    }

    if (newPassword.length < 8) {
      Alert.alert('รหัสผ่านสั้นเกินไป', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
      return;
    }

    if (newPassword !== confirmPassword) {
      Alert.alert('รหัสผ่านไม่ตรงกัน', 'กรุณากรอกรหัสผ่านใหม่ให้ตรงกันทั้งสองช่อง');
      return;
    }

    setIsChangingPassword(true);
    try {
      const response = await changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });

      if (response.success) {
        Alert.alert('สำเร็จ', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        closePasswordModal();
      } else {
        Alert.alert('ผิดพลาด', response.message || 'ไม่สามารถเปลี่ยนรหัสผ่านได้');
      }
    } catch (error) {
      Alert.alert('ผิดพลาด', 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน');
    } finally {
      setIsChangingPassword(false);
    }
  };

  // Handle theme toggle
  const handleThemeToggle = (isDarkMode: boolean) => {
    setThemeMode(isDarkMode ? 'dark' : 'light');
  };

  // Handle share referral
  const handleShareReferral = async () => {
    try {
      const message = `มาใช้ ThaiPrompt สั่งของจากตลาดสดและร้านใกล้บ้านกัน ใช้รหัสแนะนำ ${referralCode} ตอนสมัครนะ\n${referralLink}`;
      await Share.share({ message });
    } catch (error) {
      console.error('Share error:', error);
    }
  };

  // Handle copy referral code
  const handleCopyReferral = async () => {
    await Clipboard.setStringAsync(referralCode);
    Alert.alert('คัดลอกแล้ว', `รหัสแนะนำ ${referralCode} ถูกคัดลอกแล้ว`);
  };

  const handleLogout = () => {
    Alert.alert('ออกจากระบบ', 'คุณต้องการออกจากระบบใช่หรือไม่?', [
      { text: 'ยกเลิก', style: 'cancel' },
      {
        text: 'ออกจากระบบ',
        style: 'destructive',
        onPress: async () => {
          await logout();
          router.replace('/');
        },
      },
    ]);
  };

  // ถ้ายังไม่ login
  if (!isAuthenticated || !user) {
    return (
      <Screen title="โปรไฟล์" showBack={false} scroll={false}>
        <EmptyState
          icon="user-circle"
          title="โปรไฟล์ของคุณ"
          message="เข้าสู่ระบบเพื่อจัดการโปรไฟล์และตั้งค่า"
          actionLabel="เข้าสู่ระบบ"
          onAction={() => router.push('/login')}
        />
      </Screen>
    );
  }

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      {/* พื้นน้ำเงินครึ่งบน — ดึงหน้าลงเกินขอบจะเห็นน้ำเงินต่อจากหัว ไม่เห็นพื้นงาช้างโผล่ */}
      <View pointerEvents="none" style={[styles.topFill, { backgroundColor: gradients.hero[0] }]} />

      <ScrollView
        style={styles.flex}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.goldLight}
            colors={[colors.gold]}
            progressBackgroundColor={colors.card}
            progressViewOffset={insets.top}
          />
        }
      >
        {/* ---------- หัวน้ำเงินกรมท่า ---------- */}
        <RoyalHeader
          ornamentTop={insets.top - 16}
          ornamentWidth={230}
          style={{ paddingTop: insets.top + spacing.xs, paddingBottom: SHEET_RADIUS + spacing.xl }}
        >
          <View style={styles.topBar}>
            <Text accessibilityRole="header" style={[typography.serif, { color: colors.onHeader }]}>
              โปรไฟล์
            </Text>
            <GlassIconButton icon="gear-six" accessibilityLabel="ตั้งค่า" onPress={() => router.push('/settings')} />
          </View>

          {/* รูป + ชื่อ + บทบาท */}
          <View style={styles.identity}>
            <Pressable
              onPress={showImageOptions}
              disabled={isUploading}
              accessibilityRole="button"
              accessibilityLabel="เปลี่ยนรูปโปรไฟล์"
              hitSlop={4}
              style={({ pressed }) => ({ opacity: pressed ? 0.85 : 1 })}
            >
              <AvatarRing
                uri={avatarUrl}
                initial={getAvatarInitial(user?.name)}
                size={86}
                uploading={isUploading}
                showCamera
                gapColor={colors.navyDeep}
              />
            </Pressable>

            <View style={styles.identityText}>
              <Text numberOfLines={1} style={[typography.serifLg, { color: colors.onHeader }]}>
                {user?.name}
              </Text>
              {!!user?.email && (
                <Text numberOfLines={1} style={[typography.bodySm, styles.email, { color: colors.onHeaderMuted }]}>
                  {user.email}
                </Text>
              )}
              <OnHeaderProvider value>
                <View style={styles.badges}>
                  <Pill label={roleLabel(user?.role)} tone="gold" icon="crown-simple" />
                  {!!referralCode && (
                    <Pressable
                      onPress={handleCopyReferral}
                      accessibilityRole="button"
                      accessibilityLabel={`คัดลอกรหัสแนะนำ ${referralCode}`}
                      hitSlop={6}
                      style={({ pressed }) => ({ opacity: pressed ? 0.7 : 1 })}
                    >
                      <Pill label={referralCode} tone="neutral" icon="copy" />
                    </Pressable>
                  )}
                </View>
              </OnHeaderProvider>
            </View>
          </View>

          {/* บัตรสมาชิกกระจก */}
          <LinearGradient
            colors={gradients.glass}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={[styles.memberCard, { borderColor: colors.headerGlassBorder }]}
          >
            <View style={styles.memberHead}>
              <Icon name="identification-card" size={18} color={colors.goldLight} />
              <Text style={[typography.bodySm, styles.flex, { color: colors.onHeaderMuted }]}>
                {hasOwnReferralCode ? 'รหัสแนะนำ' : 'รหัสสมาชิก'}
              </Text>
            </View>
            {/* โชว์รหัสเดียวกับที่ปุ่มคัดลอก/แชร์ส่งออกไป */}
            <Text
              selectable
              accessibilityLabel={`${hasOwnReferralCode ? 'รหัสแนะนำ' : 'รหัสสมาชิก'} ${referralCode}`}
              style={[styles.memberCode, { color: colors.goldLight }]}
            >
              {referralCode}
            </Text>
            <Text style={[typography.caption, { color: colors.onHeaderMuted }]}>
              {hasOwnReferralCode
                ? `ใช้รหัสนี้ชวนเพื่อนมาสมัคร · รหัสสมาชิก ${memberCode}`
                : 'ใช้รหัสนี้ชวนเพื่อนมาสมัครใช้งาน'}
            </Text>
            <View style={[styles.memberDivider, { backgroundColor: colors.headerGlassBorder }]} />
            <View style={styles.memberActions}>
              <GlassAction icon="copy" label="คัดลอก" accessibilityLabel="คัดลอกรหัสแนะนำ" onPress={handleCopyReferral} />
              <GlassAction icon="share-network" label="แชร์" accessibilityLabel="แชร์รหัสแนะนำ" onPress={handleShareReferral} />
            </View>
          </LinearGradient>
        </RoyalHeader>

        {/* ---------- แผ่นงาช้าง: เมนู ---------- */}
        <View style={[styles.sheet, { backgroundColor: colors.background }]}>
          <MenuGroup title="บัญชี">
            <MenuRow
              icon="user"
              title="แก้ไขโปรไฟล์"
              subtitle="ชื่อ เบอร์โทร รูป และบัญชีรับเงิน"
              onPress={() => router.push('/edit-profile')}
            />
            <MenuRow
              icon="key"
              title="เปลี่ยนรหัสผ่าน"
              subtitle="ตั้งรหัสใหม่อย่างน้อย 8 ตัวอักษร"
              onPress={() => setShowPasswordModal(true)}
            />
            <MenuRow
              icon="shield-check"
              title="ยืนยันตัวตน (KYC)"
              subtitle="ยืนยันก่อนถอนเงินเข้าบัญชี"
              onPress={() => router.push('/kyc')}
            />
          </MenuGroup>

          {/* SHOP-24: ทางเข้าคำสั่งซื้อ + บริการของฉัน */}
          <MenuGroup title="บริการของฉัน">
            <MenuRow
              icon="receipt"
              title="คำสั่งซื้อของฉัน"
              subtitle="ติดตามสถานะและประวัติการสั่งซื้อ"
              onPress={() => router.push('/(tabs)/orders' as never)}
            />
            {/* SHOP-08: จัดการที่อยู่จัดส่ง (ปักหมุดสำหรับส่งด้วยไรเดอร์) */}
            <MenuRow
              icon="map-pin"
              title="ที่อยู่จัดส่ง"
              subtitle="ปักหมุดให้ไรเดอร์ส่งถึงหน้าบ้าน"
              onPress={() => router.push('/addresses' as never)}
            />
            {isFeatureEnabled('RIDER_ENABLED') && (
              <MenuRow
                icon="moped"
                title="ไรเดอร์"
                subtitle="รับงานส่งของใกล้บ้าน"
                onPress={() => router.push('/rider')}
              />
            )}
            {isFeatureEnabled('MERCHANT_ENABLED') && (
              <MenuRow
                icon="storefront"
                title="ร้านของฉัน"
                subtitle="ออเดอร์ใหม่และการจัดส่ง"
                onPress={() => router.push('/merchant' as never)}
              />
            )}
          </MenuGroup>

          {/* PLAY-08: ชวนเพื่อนแบบชั้นเดียว (ไม่มีทีม/สายงาน) */}
          {isFeatureEnabled('REFERRAL_ENABLED') && (
            <MenuGroup title="ชวนเพื่อน">
              <Pressable
                onPress={() => router.push('/referral')}
                accessibilityRole="button"
                accessibilityLabel={`ชวนเพื่อน (รหัส / QR / ลิงก์) ${referralCode}`}
                style={({ pressed }) => [styles.promo, pressed && { backgroundColor: colors.inset }]}
              >
                <View style={[styles.promoArt, { backgroundColor: colors.goldSoft }]}>
                  <BrandArt name="gift" size={56} />
                </View>
                <View style={styles.flex}>
                  <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
                    ชวนเพื่อน (รหัส / QR / ลิงก์)
                  </Text>
                  {!!referralCode && (
                    <View style={[styles.codeChip, { borderColor: colors.gold, backgroundColor: colors.goldSoft }]}>
                      <Text numberOfLines={1} style={[styles.codeChipText, { color: colors.goldDeep }]}>
                        {referralCode}
                      </Text>
                    </View>
                  )}
                </View>
                <Icon name="caret-right" size={16} color={colors.textFaint} weight="bold" />
              </Pressable>
            </MenuGroup>
          )}

          {/* PLAY-17: จัดการบัญชีบนเว็บไซต์ (ล็อกอินให้อัตโนมัติ) */}
          <MenuGroup title="เว็บไซต์">
            <MenuRow
              icon="globe"
              title="จัดการบนเว็บไซต์"
              subtitle="เปิดเว็บไซต์ เข้าสู่ระบบให้อัตโนมัติ"
              onPress={() => {
                openWebsite('/user').catch(() => {});
              }}
            />
          </MenuGroup>

          <MenuGroup title="ตั้งค่า">
            <MenuRow
              icon="moon"
              title="โหมดมืด"
              subtitle={themeMode === 'system' ? 'ตอนนี้ตามการตั้งค่าของเครื่อง' : 'ถนอมสายตาตอนกลางคืน'}
              right={<ThemedSwitch value={isDark} onValueChange={handleThemeToggle} accessibilityLabel="โหมดมืด" />}
            />
            <MenuRow
              icon="translate"
              title="ภาษา"
              value="ไทย"
              onPress={() => Alert.alert('ภาษา', 'ขณะนี้รองรับเฉพาะภาษาไทย')}
            />
            <MenuRow
              icon="bell"
              title="การแจ้งเตือน"
              subtitle="เลือกเรื่องที่อยากได้รับแจ้ง"
              onPress={() => router.push('/notification-settings')}
            />
            <MenuRow
              icon="gear-six"
              title="ตั้งค่าเพิ่มเติม และลบบัญชี"
              subtitle="ธีม ความเป็นส่วนตัว และจัดการบัญชี"
              onPress={() => router.push('/settings')}
            />
          </MenuGroup>

          <MenuGroup title="ช่วยเหลือ">
            <MenuRow
              icon="book-open"
              title="Wiki คู่มือการใช้งาน"
              subtitle="วิธีใช้งานทุกบริการในแอป"
              onPress={() => router.push('/wiki')}
            />
            <MenuRow
              icon="headset"
              title="ติดต่อเรา"
              subtitle="แจ้งปัญหาหรือสอบถามทีมงาน"
              onPress={() => router.push('/support')}
            />
            <MenuRow icon="file-text" title="เงื่อนไขการใช้งาน" onPress={() => router.push('/terms')} />
            <MenuRow icon="handshake" title="ข้อตกลงการใช้งาน" onPress={() => router.push('/agreement')} />
            <MenuRow icon="lock-key" title="นโยบายความเป็นส่วนตัว" onPress={() => router.push('/privacy')} />
            <MenuRow
              icon="info"
              title="เกี่ยวกับแอพ"
              value={`v${APP_INFO.VERSION}`}
              onPress={() =>
                Alert.alert(
                  APP_INFO.NAME,
                  `Version ${APP_INFO.VERSION} (Build ${APP_INFO.BUILD_NUMBER})\n\n© ${new Date().getFullYear()} Thaiprompt`
                )
              }
            />
          </MenuGroup>

          {/* ออกจากระบบ — ปุ่มขอบแดง (ถามยืนยันก่อนเสมอ) */}
          <Pressable
            onPress={handleLogout}
            accessibilityRole="button"
            accessibilityLabel="ออกจากระบบ"
            style={({ pressed }) => [
              styles.logout,
              {
                borderColor: withAlpha(colors.danger, 0.4),
                backgroundColor: pressed ? colors.dangerSoft : 'transparent',
              },
            ]}
          >
            <Icon name="sign-out" size={20} color={colors.danger} />
            <Text style={[typography.bodyStrong, { color: colors.danger }]}>ออกจากระบบ</Text>
          </Pressable>

          <Text style={[typography.micro, styles.version, { color: colors.textFaint }]}>
            {APP_INFO.NAME} · v{APP_INFO.VERSION}
          </Text>
        </View>
      </ScrollView>

      {/* ---------- แผ่นเปลี่ยนรหัสผ่าน ---------- */}
      <Modal
        visible={showPasswordModal}
        transparent
        animationType="fade"
        statusBarTranslucent
        onRequestClose={() => closePasswordModal()}
      >
        <KeyboardAvoidingView style={styles.modalRoot} behavior="padding">
          <View style={[StyleSheet.absoluteFill, { backgroundColor: colors.overlay }]} />
          <Animated.View
            entering={SlideInDown.springify().damping(18)}
            accessibilityViewIsModal
            style={[
              styles.modalSheet,
              {
                backgroundColor: colors.card,
                paddingBottom: Math.max(insets.bottom, spacing.lg) + spacing.sm,
              },
              shadowStyle('lg', colors.shadowDark),
            ]}
          >
            <View style={[styles.handle, { backgroundColor: colors.border }]} />

            <View style={styles.modalHeader}>
              <IconTile icon="key" tone="gold" />
              <View style={styles.flex}>
                <Text accessibilityRole="header" style={[typography.h2, { color: colors.textStrong }]}>
                  เปลี่ยนรหัสผ่าน
                </Text>
                <Text style={[typography.caption, { color: colors.textMuted }]}>
                  ตั้งรหัสใหม่อย่างน้อย 8 ตัวอักษร
                </Text>
              </View>
              <IconButton icon="x" label="ปิด" size={38} onPress={() => closePasswordModal()} />
            </View>

            <ScrollView bounces={false} keyboardShouldPersistTaps="handled" contentContainerStyle={styles.modalScroll}>
              <PasswordField
                label="รหัสผ่านปัจจุบัน"
                placeholder="กรอกรหัสผ่านปัจจุบัน"
                value={currentPassword}
                onChangeText={setCurrentPassword}
                visible={showCurrentPassword}
                onToggleVisible={() => setShowCurrentPassword(!showCurrentPassword)}
              />
              <PasswordField
                label="รหัสผ่านใหม่"
                placeholder="กรอกรหัสผ่านใหม่ (อย่างน้อย 8 ตัว)"
                value={newPassword}
                onChangeText={setNewPassword}
                visible={showNewPassword}
                onToggleVisible={() => setShowNewPassword(!showNewPassword)}
              />
              <PasswordField
                label="ยืนยันรหัสผ่านใหม่"
                placeholder="กรอกรหัสผ่านใหม่อีกครั้ง"
                value={confirmPassword}
                onChangeText={setConfirmPassword}
                visible={showNewPassword}
              />
            </ScrollView>

            <Button3D
              title="เปลี่ยนรหัสผ่าน"
              icon="check"
              size="lg"
              fullWidth
              loading={isChangingPassword}
              onPress={handleChangePassword}
            />
          </Animated.View>
        </KeyboardAvoidingView>
      </Modal>
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
  topFill: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: '50%',
  },
  scrollContent: {
    flexGrow: 1,
  },

  // ---------- หัว ----------
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
  },
  identity: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.lg,
    paddingHorizontal: spacing.screen,
    marginTop: spacing.lg,
  },
  identityText: {
    flex: 1,
  },
  email: {
    marginTop: -2,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: spacing.xs + 2,
    marginTop: spacing.sm,
  },
  memberCard: {
    marginHorizontal: spacing.screen,
    marginTop: spacing.xl,
    borderRadius: radii.xl,
    borderWidth: 1,
    padding: spacing.lg,
    overflow: 'hidden',
  },
  memberHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  memberCode: {
    ...typography.money,
    fontSize: 30,
    lineHeight: 40,
    letterSpacing: 2,
    marginTop: spacing.xs,
  },
  memberDivider: {
    height: 1,
    marginVertical: spacing.md,
  },
  memberActions: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  glassAction: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: 46,
    borderRadius: radii.md,
    borderWidth: 1,
  },

  // ---------- แผ่นงาช้าง ----------
  sheet: {
    flexGrow: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.xxl,
    // เว้นท้ายพ้นยอดซุ้มกนกของแถบล่าง (ยื่นขึ้นมาทับท้ายเนื้อหา)
    paddingBottom: spacing.xxxl + spacing.lg + KANOK_CREST_CLEARANCE,
  },
  promo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: spacing.md + 2,
  },
  promoArt: {
    width: 64,
    height: 64,
    borderRadius: radii.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  codeChip: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs + 2,
    paddingHorizontal: spacing.md,
    paddingVertical: 3,
    borderRadius: radii.sm,
    borderWidth: 1.2,
    borderStyle: 'dashed',
  },
  codeChipText: {
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '700',
    letterSpacing: 1.2,
  },
  logout: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: 54,
    borderRadius: radii.lg,
    borderWidth: 1.5,
  },
  version: {
    textAlign: 'center',
    marginTop: spacing.lg,
  },

  // ---------- แผ่นเปลี่ยนรหัสผ่าน ----------
  modalRoot: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalSheet: {
    borderTopLeftRadius: radii.xxl,
    borderTopRightRadius: radii.xxl,
    paddingHorizontal: spacing.xl,
    paddingTop: spacing.sm,
    maxHeight: '90%',
  },
  handle: {
    alignSelf: 'center',
    width: 44,
    height: 5,
    borderRadius: 3,
    marginBottom: spacing.lg,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.sm,
  },
  modalScroll: {
    paddingBottom: spacing.md,
  },
  inputGroup: {
    marginTop: spacing.md,
  },
  inputLabel: {
    marginBottom: spacing.xs,
  },
  inputBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 52,
    borderRadius: radii.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
  },
  input: {
    flex: 1,
    paddingVertical: spacing.md,
  },
});

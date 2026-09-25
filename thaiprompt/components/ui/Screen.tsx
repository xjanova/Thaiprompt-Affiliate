/**
 * Screen + ScreenHeader — โครงหน้าจอมาตรฐานธีมนวลทองคำ
 *
 * - พื้นหลังตามธีม + safe area + หัวหน้าจอพร้อมปุ่มย้อนกลับ
 * - scroll (ค่าเริ่มต้น true) พร้อม pull-to-refresh ถ้าส่ง onRefresh
 * - ใช้ scroll={false} เมื่อหน้ามี FlatList ของตัวเอง
 *
 * @example
 * <Screen title="คำสั่งซื้อของฉัน" onRefresh={reload} refreshing={refreshing}>
 *   ...
 * </Screen>
 */

import React from 'react';
import {
  Pressable,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  View,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { useTheme, spacing, typography, radii, clayShadowStyle } from '@/theme';

export interface ScreenHeaderProps {
  title: string;
  subtitle?: string;
  /** แสดงปุ่มย้อนกลับ (ค่าเริ่มต้น true) */
  showBack?: boolean;
  onBack?: () => void;
  /** ปุ่ม/ไอคอนด้านขวา */
  right?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
}

export const ScreenHeader: React.FC<ScreenHeaderProps> = ({
  title,
  subtitle,
  showBack = true,
  onBack,
  right,
  style,
}) => {
  const { colors } = useTheme();

  const goBack = () => {
    if (onBack) {
      onBack();
      return;
    }
    if (router.canGoBack()) {
      router.back();
    } else {
      router.replace('/(tabs)' as never);
    }
  };

  return (
    <View style={[styles.header, style]}>
      {showBack && (
        <Pressable
          onPress={goBack}
          accessibilityRole="button"
          accessibilityLabel="ย้อนกลับ"
          hitSlop={10}
          style={({ pressed }) => [
            styles.backButton,
            { backgroundColor: colors.card, opacity: pressed ? 0.7 : 1 },
            clayShadowStyle('sm', colors.shadowDark, colors.shadowLight),
          ]}
        >
          <Text style={[styles.backIcon, { color: colors.textStrong }]}>‹</Text>
        </Pressable>
      )}
      <View style={styles.titleBox}>
        <Text accessibilityRole="header" numberOfLines={1} style={[typography.h1, { color: colors.textStrong }]}>
          {title}
        </Text>
        {!!subtitle && (
          <Text numberOfLines={1} style={[typography.bodySm, { color: colors.textMuted }]}>
            {subtitle}
          </Text>
        )}
      </View>
      {right ? <View style={styles.right}>{right}</View> : null}
    </View>
  );
};

export interface ScreenProps extends Partial<ScreenHeaderProps> {
  children?: React.ReactNode;
  /** ไม่ส่ง title = ไม่มีหัวหน้าจอ */
  title?: string;
  /** ห่อด้วย ScrollView (ค่าเริ่มต้น true) */
  scroll?: boolean;
  refreshing?: boolean;
  onRefresh?: () => void;
  /** style ของพื้นที่เนื้อหา */
  contentStyle?: StyleProp<ViewStyle>;
  /** เว้นที่ท้ายหน้าสำหรับแท็บบาร์ (ค่าเริ่มต้น false) */
  withTabBarPadding?: boolean;
}

export const Screen: React.FC<ScreenProps> = ({
  children,
  title,
  subtitle,
  showBack,
  onBack,
  right,
  scroll = true,
  refreshing = false,
  onRefresh,
  contentStyle,
  withTabBarPadding = false,
}) => {
  const { colors, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const bottomPad = (withTabBarPadding ? 96 : spacing.xxl) + insets.bottom;

  const header = title ? (
    <ScreenHeader title={title} subtitle={subtitle} showBack={showBack} onBack={onBack} right={right} />
  ) : null;

  return (
    <View style={[styles.root, { backgroundColor: colors.background, paddingTop: insets.top }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={colors.background} />
      {header}
      {scroll ? (
        <ScrollView
          style={styles.flex}
          contentContainerStyle={[{ paddingHorizontal: spacing.screen, paddingBottom: bottomPad }, contentStyle]}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
          refreshControl={
            onRefresh ? (
              <RefreshControl
                refreshing={refreshing}
                onRefresh={onRefresh}
                tintColor={colors.gold}
                colors={[colors.gold]}
                progressBackgroundColor={colors.card}
              />
            ) : undefined
          }
        >
          {children}
        </ScrollView>
      ) : (
        <View style={[styles.flex, contentStyle]}>{children}</View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.screen,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md,
    gap: spacing.md,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  backIcon: {
    fontSize: 28,
    lineHeight: 30,
    fontWeight: '600',
    marginTop: -2,
  },
  titleBox: {
    flex: 1,
  },
  right: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
});

export default Screen;

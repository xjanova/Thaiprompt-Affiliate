/**
 * Screen + ScreenHeader — โครงหน้าจอมาตรฐาน ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * มี title = หัวน้ำเงินกรมท่าลายกนก (ปุ่มย้อนกลับกระจก + ชื่อหน้าฟอนต์มีเชิง)
 *            แล้วเนื้อหาอยู่บน "แผ่นงาช้าง" มุมบนโค้ง ซ้อนขึ้นมาบนหัว
 * ไม่มี title = พื้นหลังตามธีมล้วน + safe area
 *
 * - scroll (ค่าเริ่มต้น true) พร้อม pull-to-refresh ถ้าส่ง onRefresh
 * - ใช้ scroll={false} เมื่อหน้ามี FlatList ของตัวเอง
 * - right = ปุ่มด้านขวาของหัว (Button3D ghost/secondary, Pill, ปุ่มตะกร้า จะเป็นแบบกระจกให้เอง)
 *
 * @example
 * <Screen title="คำสั่งซื้อของฉัน" onRefresh={reload} refreshing={refreshing}>
 *   ...
 * </Screen>
 */

import React from 'react';
import {
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  View,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { useTheme, spacing, typography } from '@/theme';
import { Text } from './Text';
import { RoyalHeader, GlassIconButton, OnHeaderProvider } from './RoyalHeader';

/** ความโค้งของแผ่นเนื้อหาใต้หัวน้ำเงิน */
const SHEET_RADIUS = 26;

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

/** หัวน้ำเงินกรมท่า (รวม safe area ด้านบนแล้ว) */
export const ScreenHeader: React.FC<ScreenHeaderProps> = ({
  title,
  subtitle,
  showBack = true,
  onBack,
  right,
  style,
}) => {
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();

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
    <RoyalHeader
      ornamentTop={insets.top - 18}
      ornamentWidth={210}
      style={[{ paddingTop: insets.top + spacing.xs, paddingBottom: SHEET_RADIUS + spacing.md }, style]}
    >
      <View style={styles.header}>
        {showBack && <GlassIconButton icon="caret-left" weight="bold" accessibilityLabel="ย้อนกลับ" onPress={goBack} />}
        <View style={[styles.titleBox, !showBack && styles.titleNoBack]}>
          <Text
            accessibilityRole="header"
            numberOfLines={1}
            style={[typography.serif, { color: colors.onHeader }]}
          >
            {title}
          </Text>
          {!!subtitle && (
            <Text numberOfLines={1} style={[typography.bodySm, { color: colors.onHeaderMuted, marginTop: -2 }]}>
              {subtitle}
            </Text>
          )}
        </View>
        {right ? (
          <OnHeaderProvider value>
            <View style={styles.right}>{right}</View>
          </OnHeaderProvider>
        ) : null}
      </View>
    </RoyalHeader>
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
  const { colors, gradients, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const bottomPad = (withTabBarPadding ? 104 : spacing.xxl) + insets.bottom;
  const hasHeader = !!title;

  const body = scroll ? (
    <ScrollView
      style={styles.flex}
      contentContainerStyle={[
        { paddingHorizontal: spacing.screen, paddingTop: hasHeader ? spacing.xl : spacing.sm, paddingBottom: bottomPad },
        contentStyle,
      ]}
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
    <View style={[styles.flex, hasHeader && { paddingTop: spacing.sm }, contentStyle]}>{children}</View>
  );

  if (!hasHeader) {
    return (
      <View style={[styles.root, { backgroundColor: colors.background, paddingTop: insets.top }]}>
        <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={colors.background} />
        {body}
      </View>
    );
  }

  return (
    <View style={[styles.root, { backgroundColor: gradients.hero[gradients.hero.length - 1] }]}>
      <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />
      <ScreenHeader title={title} subtitle={subtitle} showBack={showBack} onBack={onBack} right={right} />
      <View style={[styles.sheet, { backgroundColor: colors.background }]}>{body}</View>
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
    gap: spacing.md,
  },
  titleBox: {
    flex: 1,
  },
  titleNoBack: {
    paddingLeft: spacing.xs,
  },
  right: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  sheet: {
    flex: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    overflow: 'hidden',
  },
});

export default Screen;

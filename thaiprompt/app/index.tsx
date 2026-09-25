/**
 * หน้าต้อนรับ (ก่อนเข้าสู่ระบบ) — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - หัวน้ำเงินกรมท่า + ลายกนกทองสองมุม + ไอคอนแอปกรอบทองเรืองแสง + คำโปรยฟอนต์มีเชิง
 * - โชว์บริการด้วยภาพ 3D ประจำแบรนด์ (ตลาดสด · ร้านรถเข็น · ช้อป · ไรเดอร์) บนการ์ดขาว
 * - แบนเนอร์ตลาดกลางคืน + จุดเด่น 3 ข้อ
 * - ปุ่มเข้าสู่ระบบ (ทอง) / สมัครสมาชิกใหม่ ลอยท้ายจอ
 * - ล็อกอินอยู่แล้ว → ไปแท็บหลักทันที
 * - หน้านี้ค้างอยู่ใต้แท็บได้ → StatusBar/แอนิเมชันวนทำงานเฉพาะตอนหน้านี้แสดงอยู่
 */

import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  View,
  StyleSheet,
  StatusBar,
  Dimensions,
  ScrollView,
  Animated,
  Easing,
  useWindowDimensions,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Redirect, useIsFocused } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuthStore } from '@/stores/authStore';
import { APP_INFO } from '@/config/appConfig';
import {
  BrandArt,
  Button3D,
  Card3D,
  Icon,
  RoyalHeader,
  type BrandArtName,
  type IconName,
} from '@/components/ui';
import { useTheme, radii, shadowStyle, spacing, typography, withAlpha } from '@/theme';

const { width, height } = Dimensions.get('window');

const APP_ICON = require('@/assets/images/icon.png');
const KANOK = require('@/assets/images/brand/kanok-gold.webp');
const NIGHT_MARKET = require('@/assets/images/brand/night-market.webp');
/** สัดส่วนภาพลายกนก (กว้าง 900 × สูง 791) */
const KANOK_RATIO = 791 / 900;
/** ความโค้งของแผ่นเนื้อหาใต้หัวน้ำเงิน (เท่ากับ Screen) */
const SHEET_RADIUS = 26;
/** ความกว้างเนื้อหาสูงสุด (แท็บเล็ตไม่ยืดจนเกินงาม) */
const CONTENT_MAX = 560;
/** ขนาดไอคอนแอปตรงกลางหัว */
const LOGO_SIZE = 104;

/** บริการที่โชว์ด้วยภาพ 3D */
const SERVICES: Array<{ art: BrandArtName; label: string }> = [
  { art: 'basket', label: 'ตลาดสด' },
  { art: 'cart', label: 'ร้านรถเข็น' },
  { art: 'bag', label: 'ช้อป' },
  { art: 'scooter', label: 'ไรเดอร์' },
];

/** จุดเด่นของแอป (money = โทนทองเรื่องเงิน) */
const FEATURES: Array<{ icon: IconName; text: string; money?: boolean }> = [
  { icon: 'basket', text: 'ตลาดสดและร้านอาหารใกล้บ้าน' },
  { icon: 'moped', text: 'ไรเดอร์ในชุมชนส่งไว ติดตามได้' },
  { icon: 'shield-check', text: 'จ่ายปลอดภัย ด้วย PromptPay หรือกระเป๋าเงิน', money: true },
];

// =====================================================
// Animated Components
// =====================================================

/**
 * ประกายทอง — จุดทองเล็กๆ ลอยขึ้นช้าๆ ในหัวน้ำเงิน
 */
const GoldMote = ({ delay, size, color, startX, startY }: {
  delay: number;
  size: number;
  color: string;
  startX: number;
  startY: number;
}) => {
  const translateY = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    // หยุดวนเมื่อ component ถูกถอด (เดิมวนต่อไม่รู้จบแม้หน้านี้ถูกซ่อน/ถอดแล้ว — กินแบตและ JS thread)
    let cancelled = false;
    let current: Animated.CompositeAnimation | null = null;

    const animate = () => {
      if (cancelled) return;
      // Reset values
      translateY.setValue(0);
      translateX.setValue(0);
      opacity.setValue(0);
      scale.setValue(0.5);

      current = Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          // Fade in and scale up
          Animated.timing(opacity, {
            toValue: 0.9,
            duration: 800,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1,
            duration: 800,
            useNativeDriver: true,
          }),
        ]),
        // Float animation
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -height * 0.22,
            duration: 4000 + Math.random() * 2000,
            easing: Easing.out(Easing.quad),
            useNativeDriver: true,
          }),
          Animated.timing(translateX, {
            toValue: (Math.random() - 0.5) * 60,
            duration: 4000 + Math.random() * 2000,
            easing: Easing.inOut(Easing.sin),
            useNativeDriver: true,
          }),
          // Fade out
          Animated.sequence([
            Animated.delay(3000),
            Animated.timing(opacity, {
              toValue: 0,
              duration: 1500,
              useNativeDriver: true,
            }),
          ]),
        ]),
      ]);
      current.start(({ finished }) => {
        if (finished && !cancelled) animate();
      });
    };

    animate();
    return () => {
      cancelled = true;
      current?.stop();
    };
  }, []);

  return (
    <Animated.View
      pointerEvents="none"
      style={[
        styles.mote,
        {
          left: startX,
          top: startY,
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: color,
          boxShadow: `0px 0px 6px 1px ${withAlpha(color, 0.7)}`,
          opacity,
          transform: [{ translateX }, { translateY }, { scale }],
        },
      ]}
    />
  );
};

/**
 * รัศมีทองหลังโลโก้ — หายใจเข้าออกช้าๆ
 */
const LogoHalo = ({ size, color }: { size: number; color: string }) => {
  const pulse = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulse, { toValue: 1, duration: 2600, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
        Animated.timing(pulse, { toValue: 0, duration: 2600, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [pulse]);

  return (
    <Animated.View
      pointerEvents="none"
      style={[
        styles.halo,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: withAlpha(color, 0.12),
          boxShadow: `0px 0px 60px 18px ${withAlpha(color, 0.26)}`,
          opacity: pulse.interpolate({ inputRange: [0, 1], outputRange: [0.5, 1] }),
          transform: [{ scale: pulse.interpolate({ inputRange: [0, 1], outputRange: [0.94, 1.06] }) }],
        },
      ]}
    />
  );
};

/**
 * Pulsing Ring - วงแหวนทองกระเพื่อมรอบโลโก้
 */
const PulsingRing = ({ delay, size, color }: { delay: number; size: number; color: string }) => {
  const scale = useRef(new Animated.Value(1)).current;
  const opacity = useRef(new Animated.Value(0.6)).current;

  useEffect(() => {
    const pulseAnimation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(scale, {
            toValue: 1.45,
            duration: 2400,
            easing: Easing.out(Easing.quad),
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0,
            duration: 2400,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(scale, {
            toValue: 1,
            duration: 0,
            useNativeDriver: true,
          }),
          Animated.timing(opacity, {
            toValue: 0.6,
            duration: 0,
            useNativeDriver: true,
          }),
        ]),
      ])
    );

    pulseAnimation.start();
    return () => pulseAnimation.stop();
  }, []);

  return (
    <Animated.View
      pointerEvents="none"
      style={[
        styles.pulsingRing,
        {
          width: size,
          height: size,
          borderRadius: size * 0.3,
          borderColor: withAlpha(color, 0.55),
          opacity,
          transform: [{ scale }],
        },
      ]}
    />
  );
};

// =====================================================
// Main Component
// =====================================================

export default function IndexScreen() {
  const { isAuthenticated, isInitialized } = useAuthStore();
  const [logoError, setLogoError] = useState(false);
  // หน้านี้ค้างอยู่ใต้แท็บหลังล็อกอิน/ออกจากระบบได้ → แสดง StatusBar/แอนิเมชันเฉพาะตอนเห็นอยู่จริง
  // (เดิม StatusBar สีขาวของหน้านี้ไปทับหน้าแรกโทนสว่างหลังออกจากระบบ ไอคอนแถบบนมองไม่เห็น)
  const isFocused = useIsFocused();
  const { colors, gradients, isDark } = useTheme();
  const insets = useSafeAreaInsets();
  const { width: screenWidth } = useWindowDimensions();

  // ประกายทองในหัว — สร้างครั้งเดียว (สุ่มใหม่ทุก render จุดจะกระโดด)
  const motes = useMemo(
    () =>
      Array.from({ length: 12 }, (_, i) => ({
        id: i,
        delay: i * 420,
        size: 3 + Math.random() * 3.5,
        startX: Math.random() * width,
        startY: 150 + Math.random() * 260,
      })),
    []
  );

  // Animations
  const logoScale = useRef(new Animated.Value(0.5)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;
  const contentOpacity = useRef(new Animated.Value(0)).current;
  const contentSlide = useRef(new Animated.Value(30)).current;
  const buttonScale = useRef(new Animated.Value(0.9)).current;

  useEffect(() => {
    // Logo entrance animation
    Animated.sequence([
      Animated.parallel([
        Animated.spring(logoScale, {
          toValue: 1,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
        Animated.timing(logoOpacity, {
          toValue: 1,
          duration: 800,
          useNativeDriver: true,
        }),
      ]),
      // Content slide in
      Animated.parallel([
        Animated.timing(contentOpacity, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }),
        Animated.spring(contentSlide, {
          toValue: 0,
          tension: 50,
          friction: 8,
          useNativeDriver: true,
        }),
        Animated.spring(buttonScale, {
          toValue: 1,
          tension: 50,
          friction: 7,
          useNativeDriver: true,
        }),
      ]),
    ]).start();
  }, []);

  // ถ้า login แล้ว redirect ไป tabs ทันที
  if (isAuthenticated && isInitialized) {
    return <Redirect href="/(tabs)" />;
  }

  const goToLogin = () => router.push('/login');
  const goToRegister = () => router.push('/register');

  // ขนาดการ์ดบริการ: 4 ใบเต็มแถว (แท็บเล็ตจำกัดความกว้างเนื้อหา)
  const contentWidth = Math.min(screenWidth, CONTENT_MAX) - spacing.screen * 2;
  const tileGap = 10;
  const tileSize = Math.floor((contentWidth - tileGap * 3) / 4);
  // สี่เหลี่ยมไอคอน: สว่าง = น้ำเงินอ่อน/ไอคอนน้ำเงิน · มืด = ทองจาง/ไอคอนทอง (น้ำเงินบนพื้นมืดอ่านไม่ออก)
  const tileBg = isDark ? colors.goldSoft : colors.navySoft;
  const tileInk = isDark ? colors.gold : colors.navy;
  const kanokWidth = 250;

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      {isFocused && <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />}

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* พื้นน้ำเงินเหนือหัว (ตอนดึงเลื่อนเกินขอบบนจะไม่เห็นพื้นงาช้าง) */}
        <View pointerEvents="none" style={[styles.overscroll, { backgroundColor: gradients.hero[0] }]} />

        {/* ---------- หัวน้ำเงินกรมท่า ---------- */}
        <RoyalHeader
          ornamentTop={insets.top - 8}
          ornamentWidth={270}
          style={{ paddingTop: insets.top + spacing.xl, paddingBottom: SHEET_RADIUS + spacing.xxxl + spacing.sm }}
        >
          {/* ลายกนกกลับหัว มุมซ้ายล่าง — ล้อกับกรอบไอคอนแอป */}
          <Image
            source={KANOK}
            contentFit="contain"
            accessible={false}
            style={[
              styles.kanokBottom,
              { width: kanokWidth, height: kanokWidth * KANOK_RATIO, opacity: isDark ? 0.14 : 0.22 },
            ]}
          />

          {/* ประกายทองลอย — เฉพาะตอนหน้านี้แสดงอยู่ (ถูกซ่อนใต้แท็บ = ไม่ต้องวนแอนิเมชัน) */}
          {isFocused && motes.map((m) => <GoldMote key={m.id} {...m} color={colors.goldLight} />)}

          {/* Animated Logo Section */}
          <Animated.View
            style={[
              styles.logoSection,
              {
                opacity: logoOpacity,
                transform: [{ scale: logoScale }],
              },
            ]}
          >
            <View style={styles.logoWrapper}>
              {isFocused && (
                <>
                  <LogoHalo size={LOGO_SIZE + 78} color={colors.gold} />
                  <PulsingRing delay={0} size={LOGO_SIZE + 12} color={colors.goldLight} />
                  <PulsingRing delay={1200} size={LOGO_SIZE + 12} color={colors.goldLight} />
                </>
              )}

              {/* กรอบทองฟอยล์รอบไอคอนแอป */}
              <LinearGradient
                colors={gradients.goldBorder}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={[styles.logoFrame, shadowStyle('lg', colors.shadowDark)]}
              >
                <View style={[styles.logoInner, { backgroundColor: colors.navyDeep }]}>
                  {!logoError ? (
                    <Image
                      source={APP_ICON}
                      style={styles.logo}
                      contentFit="cover"
                      onError={() => setLogoError(true)}
                    />
                  ) : (
                    <Text style={[typography.serifLg, { color: colors.goldLight }]}>TP</Text>
                  )}
                </View>
              </LinearGradient>
            </View>

            <Text style={[typography.serifSm, styles.appName, { color: colors.goldLight }]}>{APP_INFO.NAME}</Text>
            <View style={styles.subtitleRow}>
              <LinearGradient
                colors={[withAlpha(colors.gold, 0), colors.gold]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.subtitleLine}
              />
              <Text style={[typography.overline, { color: colors.onHeaderMuted }]}>ช้อป · ตลาดสด · ส่งของ</Text>
              <LinearGradient
                colors={[colors.gold, withAlpha(colors.gold, 0)]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.subtitleLine}
              />
            </View>
          </Animated.View>

          {/* Tagline */}
          <Animated.View
            style={[
              styles.taglineSection,
              {
                opacity: contentOpacity,
                transform: [{ translateY: contentSlide }],
              },
            ]}
          >
            <Text accessibilityRole="header" style={[typography.serifLg, styles.center, { color: colors.onHeader }]}>
              ของดีใกล้บ้าน
            </Text>
            <Text style={[typography.serifLg, styles.center, { color: colors.goldLight }]}>ส่งถึงมือ</Text>
          </Animated.View>
        </RoyalHeader>

        {/* ---------- แผ่นงาช้าง ---------- */}
        <View style={[styles.sheet, { backgroundColor: colors.background }]}>
          <Animated.View
            style={[
              styles.sheetInner,
              {
                opacity: contentOpacity,
                transform: [{ translateY: contentSlide }],
              },
            ]}
          >
            {/* บริการ — ภาพ 3D บนการ์ดขาว */}
            <View style={[styles.serviceRow, { gap: tileGap }]}>
              {SERVICES.map((service) => (
                <View key={service.art} style={[styles.serviceItem, { width: tileSize }]}>
                  <Card3D
                    padding={0}
                    radius={radii.xl}
                    shadow="md"
                    contentStyle={[styles.serviceTile, { width: tileSize, height: tileSize }]}
                  >
                    <BrandArt name={service.art} size={tileSize - 12} />
                  </Card3D>
                  <Text numberOfLines={1} style={[typography.caption, styles.serviceLabel, { color: colors.textStrong }]}>
                    {service.label}
                  </Text>
                </View>
              ))}
            </View>

            {/* แบนเนอร์ตลาดกลางคืน */}
            <View style={[styles.banner, shadowStyle('md', colors.shadowDark)]}>
              <View style={styles.bannerClip}>
                <Image source={NIGHT_MARKET} style={StyleSheet.absoluteFill} contentFit="cover" accessible={false} />
                <LinearGradient
                  colors={gradients.bannerScrim}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 0, y: 1 }}
                  style={StyleSheet.absoluteFill}
                />
                <LinearGradient
                  colors={[withAlpha(gradients.hero[1], 0.92), withAlpha(gradients.hero[1], 0)]}
                  start={{ x: 0, y: 0.5 }}
                  end={{ x: 0.85, y: 0.5 }}
                  style={StyleSheet.absoluteFill}
                />
                <View style={styles.bannerBody}>
                  <View style={[styles.bannerTag, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
                    <Icon name="moon-stars" size={13} color={colors.goldLight} />
                    <Text style={[typography.micro, { color: colors.goldLight }]}>ตลาดสด · ร้านรถเข็น</Text>
                  </View>
                  <Text style={[typography.serif, styles.bannerTitle, { color: colors.onHeader }]}>
                    สั่งของจากตลาดสด{'\n'}และร้านค้าในชุมชน
                  </Text>
                  <Text style={[typography.bodySm, { color: colors.onHeaderMuted }]}>มีไรเดอร์ใกล้คุณช่วยส่ง</Text>
                </View>
              </View>
            </View>

            {/* Features */}
            <Text style={[typography.overline, styles.featuresLabel, { color: colors.goldDeep }]}>
              ทุกอย่างใกล้บ้าน ในแอปเดียว
            </Text>
            <Card3D padding={0}>
              {FEATURES.map((feature, index) => (
                <View
                  key={feature.text}
                  style={[
                    styles.featureRow,
                    index > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                  ]}
                >
                  <View style={[styles.featureIcon, { backgroundColor: feature.money ? colors.goldSoft : tileBg }]}>
                    <Icon name={feature.icon} size={22} color={feature.money ? colors.goldDeep : tileInk} />
                  </View>
                  <Text style={[typography.bodyStrong, styles.featureText, { color: colors.textStrong }]}>
                    {feature.text}
                  </Text>
                  <Icon name="seal-check" size={20} color={colors.gold} weight="fill" />
                </View>
              ))}
            </Card3D>

            {/* Footer */}
            <Text style={[typography.micro, styles.footerText, { color: colors.textFaint }]}>
              v{APP_INFO.VERSION} ({APP_INFO.BUILD_DATE})
            </Text>
          </Animated.View>
        </View>
      </ScrollView>

      {/* ---------- ปุ่มท้ายจอ ---------- */}
      <Animated.View
        style={[
          styles.ctaBar,
          {
            paddingBottom: insets.bottom + spacing.md,
            backgroundColor: colors.card,
            borderTopColor: colors.divider,
            opacity: contentOpacity,
          },
        ]}
      >
        <Animated.View style={[styles.ctaInner, { transform: [{ scale: buttonScale }] }]}>
          <Button3D title="เข้าสู่ระบบ" icon="sign-in" size="lg" fullWidth onPress={goToLogin} />
          <Button3D title="สมัครสมาชิกใหม่" icon="user-plus" variant="secondary" fullWidth onPress={goToRegister} />
        </Animated.View>
      </Animated.View>
    </View>
  );
}

// =====================================================
// Styles
// =====================================================

const styles = StyleSheet.create({
  container: {
    flex: 1,
    overflow: 'hidden',
  },
  center: {
    textAlign: 'center',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
  },
  overscroll: {
    position: 'absolute',
    top: -1000,
    left: 0,
    right: 0,
    height: 1000,
  },

  // หัว
  kanokBottom: {
    position: 'absolute',
    bottom: SHEET_RADIUS - 30,
    left: -70,
    transform: [{ rotate: '180deg' }],
  },
  mote: {
    position: 'absolute',
  },
  halo: {
    position: 'absolute',
  },
  pulsingRing: {
    position: 'absolute',
    borderWidth: 1.5,
  },
  logoSection: {
    alignItems: 'center',
    paddingHorizontal: spacing.xxl,
  },
  logoWrapper: {
    alignItems: 'center',
    justifyContent: 'center',
    width: LOGO_SIZE + 40,
    height: LOGO_SIZE + 40,
    marginBottom: spacing.md,
  },
  logoFrame: {
    padding: 2.5,
    borderRadius: 31,
  },
  logoInner: {
    borderRadius: 29,
    overflow: 'hidden',
    width: LOGO_SIZE,
    height: LOGO_SIZE,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: {
    width: LOGO_SIZE,
    height: LOGO_SIZE,
  },
  appName: {
    letterSpacing: 0.6,
  },
  subtitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: spacing.xs,
    gap: spacing.md,
  },
  subtitleLine: {
    width: 34,
    height: 1,
  },
  taglineSection: {
    marginTop: spacing.xl,
    paddingHorizontal: spacing.xxl,
  },

  // แผ่นเนื้อหา
  sheet: {
    flexGrow: 1,
    marginTop: -SHEET_RADIUS,
    borderTopLeftRadius: SHEET_RADIUS,
    borderTopRightRadius: SHEET_RADIUS,
    paddingTop: spacing.xl,
    paddingBottom: spacing.xl,
  },
  sheetInner: {
    width: '100%',
    maxWidth: CONTENT_MAX,
    alignSelf: 'center',
    paddingHorizontal: spacing.screen,
  },
  serviceRow: {
    flexDirection: 'row',
    justifyContent: 'center',
  },
  serviceItem: {
    alignItems: 'center',
  },
  serviceTile: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceLabel: {
    marginTop: spacing.sm,
    fontWeight: '600',
    textAlign: 'center',
  },

  // แบนเนอร์
  banner: {
    marginTop: spacing.xxl,
    borderRadius: radii.xl,
  },
  bannerClip: {
    height: 178,
    borderRadius: radii.xl,
    overflow: 'hidden',
    justifyContent: 'flex-end',
  },
  bannerBody: {
    padding: spacing.lg,
    paddingRight: spacing.xxxl * 2,
  },
  bannerTag: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 5,
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
    borderWidth: 1,
    marginBottom: spacing.sm,
  },
  bannerTitle: {
    marginBottom: 2,
  },

  // จุดเด่น
  featuresLabel: {
    marginTop: spacing.xxl,
    marginBottom: spacing.md,
    marginLeft: spacing.xs,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: 14,
  },
  featureIcon: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureText: {
    flex: 1,
  },

  // Footer
  footerText: {
    textAlign: 'center',
    marginTop: spacing.xl,
  },

  // ปุ่มท้ายจอ
  ctaBar: {
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingTop: spacing.md,
    paddingHorizontal: spacing.screen,
  },
  ctaInner: {
    width: '100%',
    maxWidth: CONTENT_MAX - spacing.screen * 2,
    alignSelf: 'center',
    gap: spacing.md,
  },
});

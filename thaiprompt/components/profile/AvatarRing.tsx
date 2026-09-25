/**
 * AvatarRing — รูปโปรไฟล์ในวงแหวนทอง (หัวโปรไฟล์ · การ์ดตั้งค่า · หน้าแก้ไขโปรไฟล์)
 *
 * - วงทองไล่เฉดทแยง (สว่างซ้ายบน → เข้มขวาล่าง) ให้ดูเป็นโลหะมีมิติ
 * - ช่องคั่นบางๆ ระหว่างวงทองกับรูป ใช้สีพื้นที่วางอยู่ (gapColor) ให้รูปลอยจากวง
 * - ไม่มีรูป = ตัวอักษรแรกของชื่อ (ฟอนต์มีเชิง สีทอง) บนพื้นน้ำเงินกรมท่า
 * - uploading = ม่านมืด + ตัวหมุน · showCamera = ป้ายกล้องทองมุมขวาล่าง (บอกว่ากดเปลี่ยนรูปได้)
 */

import React from 'react';
import { ActivityIndicator, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Icon } from '@/components/ui';
import { useTheme, FONT } from '@/theme';

export interface AvatarRingProps {
  uri?: string | null;
  /** ตัวอักษรแทนรูป */
  initial: string;
  /** เส้นผ่านศูนย์กลางรวมวงทอง */
  size?: number;
  uploading?: boolean;
  showCamera?: boolean;
  /** สีช่องคั่นระหว่างวงทองกับรูป (ปกติ = สีพื้นที่วางอยู่) */
  gapColor?: string;
  style?: StyleProp<ViewStyle>;
}

export const AvatarRing: React.FC<AvatarRingProps> = ({
  uri,
  initial,
  size = 84,
  uploading = false,
  showCamera = false,
  gapColor,
  style,
}) => {
  const { colors, gradients } = useTheme();
  const ring = Math.max(2, Math.round(size * 0.035));
  const gap = Math.max(2, Math.round(size * 0.03));
  const inner = size - (ring + gap) * 2;
  const badge = Math.max(24, Math.round(size * 0.32));
  const gapFill = gapColor ?? colors.navyDeep;

  return (
    <View style={[{ width: size, height: size }, style]}>
      <LinearGradient
        colors={gradients.gold}
        start={{ x: 0.1, y: 0 }}
        end={{ x: 0.9, y: 1 }}
        style={[styles.center, { width: size, height: size, borderRadius: size / 2 }]}
      >
        <View style={[styles.center, { width: size - ring * 2, height: size - ring * 2, borderRadius: size / 2, backgroundColor: gapFill }]}>
          {uri ? (
            <Image
              source={{ uri }}
              style={{ width: inner, height: inner, borderRadius: inner / 2 }}
              contentFit="cover"
              transition={150}
              accessible={false}
            />
          ) : (
            <LinearGradient
              colors={gradients.navy}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={[styles.center, { width: inner, height: inner, borderRadius: inner / 2 }]}
            >
              <Text
                style={{
                  fontFamily: FONT.serif,
                  fontSize: inner * 0.4,
                  lineHeight: inner * 0.58,
                  color: colors.goldLight,
                }}
              >
                {initial}
              </Text>
            </LinearGradient>
          )}
        </View>
      </LinearGradient>

      {uploading && (
        <View style={[StyleSheet.absoluteFill, styles.center, { borderRadius: size / 2, backgroundColor: colors.overlay }]}>
          <ActivityIndicator color={colors.goldLight} />
        </View>
      )}

      {showCamera && (
        <LinearGradient
          colors={gradients.primary}
          start={{ x: 0, y: 0 }}
          end={{ x: 0, y: 1 }}
          style={[
            styles.center,
            styles.camera,
            { width: badge, height: badge, borderRadius: badge / 2, borderColor: gapFill },
          ]}
        >
          <Icon name="camera" size={badge * 0.52} color={colors.textOnGold} weight="fill" />
        </LinearGradient>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  camera: {
    position: 'absolute',
    right: -2,
    bottom: -2,
    borderWidth: 2.5,
  },
});

export default AvatarRing;

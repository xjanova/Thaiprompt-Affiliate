/**
 * PersonAvatar — รูปคน (ไรเดอร์/ผู้ซื้อ/เจ้าของร้าน) ในวงทอง + จุดออนไลน์
 *
 * - รูปจาก server เป็นรูปลายน้ำเสมอ (THAI PROMPT · VIEWER ...) — แอปแค่แสดง ห้ามบันทึกลงเครื่อง
 * - ไม่มีรูป/โหลดรูปไม่ขึ้น = อักษรแรกของชื่อ (ฟอนต์มีเชิง สีทอง) บนพื้นน้ำเงินกรมท่า หรือไอคอนคน
 * - online = จุดเขียวมุมขวาล่าง (ขอบสีการ์ด) · online={false} = จุดเทา · ไม่ส่ง = ไม่แสดงจุด
 *
 * @example
 * <PersonAvatar uri={rider.photo_url} name={rider.display_name} size={56} online />
 */

import React, { memo, useEffect, useState } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Text } from '@/components/ui/Text';
import { Icon } from '@/components/ui/Icon';
import { useTheme, FONT } from '@/theme';

export interface PersonAvatarProps {
  uri?: string | null;
  /** ชื่อ (ใช้ทำอักษรย่อ + accessibilityLabel) */
  name?: string;
  /** เส้นผ่านศูนย์กลางรวมวง (ค่าเริ่มต้น 48) */
  size?: number;
  /** วงทองรอบรูป (ค่าเริ่มต้น gold) */
  ring?: 'gold' | 'none';
  /** จุดสถานะออนไลน์ (ไม่ส่ง = ไม่แสดง) */
  online?: boolean;
  /** สีขอบของจุดออนไลน์ (ปกติ = สีการ์ดที่วางอยู่) */
  surfaceColor?: string;
  style?: StyleProp<ViewStyle>;
}

/** อักษรแรกของชื่อ (ข้ามสระหน้า เ แ โ ใ ไ และวรรณยุกต์) */
export const initialOfName = (name: string | null | undefined): string | null => {
  for (const ch of String(name || '').trim()) {
    if (/\s/.test(ch)) break;
    if (/[เ-ไัิ-ฺ็-๎]/.test(ch)) continue;
    return ch.toUpperCase();
  }
  return null;
};

export const PersonAvatar: React.FC<PersonAvatarProps> = memo(
  ({ uri, name, size = 48, ring = 'gold', online, surfaceColor, style }) => {
    const { colors, gradients } = useTheme();
    const [failed, setFailed] = useState(false);

    // รูปเปลี่ยน (เช่น URL ลายเซ็นใหม่) → ลองโหลดใหม่
    useEffect(() => {
      setFailed(false);
    }, [uri]);

    const ringWidth = ring === 'gold' ? Math.max(2, Math.round(size * 0.045)) : 0;
    const gap = ring === 'gold' ? Math.max(1.5, Math.round(size * 0.03)) : 0;
    const inner = size - (ringWidth + gap) * 2;
    const dot = Math.max(10, Math.round(size * 0.24));
    const initial = initialOfName(name);
    const showImage = !!uri && !failed;

    const face = (
      <View
        style={[
          styles.center,
          {
            width: inner,
            height: inner,
            borderRadius: inner / 2,
            backgroundColor: colors.navyFill,
            overflow: 'hidden',
          },
        ]}
      >
        {showImage ? (
          <Image
            source={{ uri: uri as string }}
            style={{ width: inner, height: inner }}
            contentFit="cover"
            transition={120}
            // รูปลายน้ำอายุสั้น — ไม่เก็บลงดิสก์
            cachePolicy="memory"
            onError={() => setFailed(true)}
            accessibilityIgnoresInvertColors
          />
        ) : initial ? (
          <Text style={{ fontFamily: FONT.serif, fontSize: Math.round(inner * 0.42), lineHeight: Math.round(inner * 0.6), color: colors.goldLight }}>
            {initial}
          </Text>
        ) : (
          <Icon name="user" size={Math.round(inner * 0.5)} color={colors.goldLight} weight="fill" />
        )}
      </View>
    );

    return (
      <View
        style={[{ width: size, height: size }, style]}
        accessible
        accessibilityRole="image"
        accessibilityLabel={`รูปของ ${name || 'ผู้ใช้'}${online === true ? ' ออนไลน์อยู่' : ''}`}
      >
        {ring === 'gold' ? (
          <LinearGradient
            colors={gradients.gold}
            start={{ x: 0.1, y: 0 }}
            end={{ x: 0.9, y: 1 }}
            style={[styles.center, { width: size, height: size, borderRadius: size / 2 }]}
          >
            <View
              style={[
                styles.center,
                { width: size - ringWidth * 2, height: size - ringWidth * 2, borderRadius: size / 2, backgroundColor: surfaceColor ?? colors.card },
              ]}
            >
              {face}
            </View>
          </LinearGradient>
        ) : (
          face
        )}
        {online !== undefined && (
          <View
            style={[
              styles.dot,
              {
                width: dot,
                height: dot,
                borderRadius: dot / 2,
                borderWidth: Math.max(2, Math.round(dot * 0.2)),
                borderColor: surfaceColor ?? colors.card,
                backgroundColor: online ? colors.success : colors.textFaint,
              },
            ]}
          />
        )}
      </View>
    );
  }
);

PersonAvatar.displayName = 'PersonAvatar';

const styles = StyleSheet.create({
  center: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  dot: {
    position: 'absolute',
    right: 0,
    bottom: 0,
  },
});

export default PersonAvatar;

/**
 * แถบรูปสินค้า (แนวนอน) — ใช้ทั้งตอนเพิ่มสินค้า (รูปในเครื่อง) และตอนแก้ (รูปบน server)
 *
 * - รูปแรกคือรูปหลัก (ขอบทอง + ป้าย "รูปหลัก") ลูกค้าเห็นรูปนี้ก่อน
 * - แตะรูป → เมนู: ตั้งเป็นรูปหลัก / เลื่อนซ้าย-ขวา / ลบ (ถามก่อน) — ผู้เรียกเป็นคนทำงานจริง
 * - ช่อง "เพิ่มรูป" ขอบประทองท้ายแถว (ซ่อนเมื่อครบจำนวน) · busy = หมุนโหลดและกดอะไรไม่ได้
 */

import React from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Image } from 'expo-image';
import { Text } from '@/components/ui/Text';
import { Icon, Pill } from '@/components/ui';
import { useTheme, spacing, typography } from '@/theme';

export interface StripImage {
  /** key ไม่ซ้ำ (id รูปบน server หรือ uri ในเครื่อง) */
  key: string;
  uri: string;
  isMain: boolean;
}

export interface ProductImageStripProps {
  images: StripImage[];
  maxTotal: number;
  busy?: boolean;
  /** อ่านอย่างเดียว (สินค้าถูกระงับ/มีตัวเลือกย่อย) */
  readOnly?: boolean;
  onAdd: () => void;
  onSetMain: (image: StripImage) => void;
  onMove: (image: StripImage, direction: -1 | 1) => void;
  onRemove: (image: StripImage) => void;
}

const TILE = 104;

export const ProductImageStrip: React.FC<ProductImageStripProps> = ({
  images,
  maxTotal,
  busy = false,
  readOnly = false,
  onAdd,
  onSetMain,
  onMove,
  onRemove,
}) => {
  const { colors } = useTheme();

  const openMenu = (image: StripImage, index: number) => {
    if (busy || readOnly) return;
    const buttons: { text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }[] = [];
    if (!image.isMain) {
      buttons.push({ text: 'ตั้งเป็นรูปหลัก', onPress: () => onSetMain(image) });
      // เลื่อนได้เฉพาะรูปเพิ่มเติม (รูปหลักอยู่หน้าสุดเสมอ)
      if (index > 1) buttons.push({ text: 'เลื่อนไปทางซ้าย', onPress: () => onMove(image, -1) });
      if (index < images.length - 1) buttons.push({ text: 'เลื่อนไปทางขวา', onPress: () => onMove(image, 1) });
    }
    buttons.push({
      text: 'ลบรูปนี้',
      style: 'destructive',
      onPress: () =>
        Alert.alert('ลบรูปนี้?', image.isMain && images.length > 1 ? 'รูปถัดไปจะกลายเป็นรูปหลักแทน' : 'ลบแล้วต้องเพิ่มใหม่ถ้าอยากได้คืน', [
          { text: 'ไม่ลบ', style: 'cancel' },
          { text: 'ลบรูป', style: 'destructive', onPress: () => onRemove(image) },
        ]),
    });
    buttons.push({ text: 'ปิด', style: 'cancel' });
    Alert.alert('รูปสินค้า', image.isMain ? 'รูปนี้เป็นรูปหลักที่ลูกค้าเห็นก่อน' : undefined, buttons);
  };

  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row} keyboardShouldPersistTaps="handled">
      {images.map((image, index) => (
        <Pressable
          key={image.key}
          onPress={() => openMenu(image, index)}
          disabled={busy || readOnly}
          accessibilityRole="button"
          accessibilityLabel={image.isMain ? 'รูปหลัก แตะเพื่อจัดการ' : `รูปที่ ${index + 1} แตะเพื่อจัดการ`}
          style={({ pressed }) => [
            styles.tile,
            { backgroundColor: colors.inset, borderColor: image.isMain ? colors.gold : colors.border, opacity: pressed ? 0.75 : 1 },
          ]}
        >
          <Image source={{ uri: image.uri }} style={styles.image} contentFit="cover" transition={150} />
          {image.isMain && <Pill label="รูปหลัก" icon="star" solid style={styles.mainBadge} />}
        </Pressable>
      ))}
      {!readOnly && images.length < maxTotal && (
        <Pressable
          onPress={onAdd}
          disabled={busy}
          accessibilityRole="button"
          accessibilityLabel="เพิ่มรูปสินค้า"
          style={({ pressed }) => [
            styles.tile,
            styles.addTile,
            { borderColor: colors.gold, backgroundColor: colors.goldSoft, opacity: pressed ? 0.75 : 1 },
          ]}
        >
          {busy ? (
            <>
              <ActivityIndicator color={colors.goldDeep} />
              <Text style={[typography.micro, { color: colors.goldDeep }]}>กำลังอัปโหลด</Text>
            </>
          ) : (
            <>
              <View style={[styles.addCircle, { backgroundColor: colors.card }]}>
                <Icon name="camera" size={20} color={colors.goldDeep} />
              </View>
              <Text style={[typography.caption, { color: colors.goldDeep }]}>เพิ่มรูป</Text>
              <Text style={[typography.micro, { color: colors.textMuted }]}>
                {images.length}/{maxTotal}
              </Text>
            </>
          )}
        </Pressable>
      )}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  row: {
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  tile: {
    width: TILE,
    height: TILE,
    borderRadius: 18,
    borderWidth: 2,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
  },
  image: {
    width: '100%',
    height: '100%',
  },
  mainBadge: {
    position: 'absolute',
    left: 6,
    bottom: 6,
  },
  addTile: {
    borderStyle: 'dashed',
    gap: 3,
  },
  addCircle: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

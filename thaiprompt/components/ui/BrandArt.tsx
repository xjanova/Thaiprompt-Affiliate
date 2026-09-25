/**
 * BrandArt — ภาพไอคอน 3D ประจำแบรนด์ (สร้างเฉพาะ TP UltraApp ด้วย gpt-image-2)
 *
 * ใช้เป็นภาพหลักของบริการ (หน้าแรก) และภาพประกอบหน้าว่าง — ไม่ใช้แทนไอคอนเล็กในปุ่ม (ใช้ <Icon/>)
 *   basket = ตลาดสด · cart = ร้านรถเข็น/ตลาดนัด · bag = ช้อป · scooter = ไรเดอร์
 *   store = ร้านของฉัน · wallet = กระเป๋าเงิน · gift = ชวนเพื่อน · tarot = ดูดวง
 *
 * @example
 * <BrandArt name="scooter" size={72} />
 */

import React from 'react';
import { Image } from 'expo-image';
import type { StyleProp, ImageStyle } from 'react-native';

export const BRAND_ART = {
  basket: require('@/assets/images/brand/icons/basket.webp'),
  cart: require('@/assets/images/brand/icons/cart.webp'),
  bag: require('@/assets/images/brand/icons/bag.webp'),
  scooter: require('@/assets/images/brand/icons/scooter.webp'),
  store: require('@/assets/images/brand/icons/store.webp'),
  wallet: require('@/assets/images/brand/icons/wallet.webp'),
  gift: require('@/assets/images/brand/icons/gift.webp'),
  tarot: require('@/assets/images/brand/icons/tarot.webp'),
} as const;

export type BrandArtName = keyof typeof BRAND_ART;

export interface BrandArtProps {
  name: BrandArtName;
  size?: number;
  style?: StyleProp<ImageStyle>;
}

export const BrandArt: React.FC<BrandArtProps> = ({ name, size = 64, style }) => (
  <Image
    source={BRAND_ART[name]}
    style={[{ width: size, height: size }, style]}
    contentFit="contain"
    accessible={false}
    transition={0}
  />
);

export default BrandArt;

/**
 * Icon — ไอคอนเส้น Phosphor ของแอป (แทนอีโมจิทั้งหมด)
 *
 * วาดด้วย react-native-svg จาก path ที่คัดมาเฉพาะที่ใช้ (components/ui/iconPaths.ts)
 * - weight: regular = เส้นปกติ · fill = ทึบ (ใช้กับสถานะที่เลือก) · bold = เส้นหนา (บางตัวเท่านั้น ถ้าไม่มีจะใช้ regular)
 * - ไม่ส่ง color = ใช้สีตัวอักษรหลักของธีม
 *
 * @example
 * <Icon name="storefront" size={22} color={colors.goldDeep} />
 * <Icon name="house" weight="fill" />
 */

import React, { memo } from 'react';
import Svg, { Path } from 'react-native-svg';
import type { StyleProp, ViewStyle } from 'react-native';
import { ICON_PATHS, type IconName, type IconPathSet } from './iconPaths';
import { useTheme } from '@/theme';

export type { IconName } from './iconPaths';
export type IconWeight = 'regular' | 'fill' | 'bold';

export interface IconProps {
  name: IconName;
  size?: number;
  color?: string;
  weight?: IconWeight;
  style?: StyleProp<ViewStyle>;
  /** ไอคอนตกแต่ง = ซ่อนจากโปรแกรมอ่านหน้าจอ (ค่าเริ่มต้น) */
  accessibilityLabel?: string;
}

/** ตรวจว่าเป็นชื่อไอคอนที่มีในชุด (ใช้กับ props ที่รับได้ทั้งชื่อไอคอนและอีโมจิเดิม) */
export const isIconName = (value: unknown): value is IconName =>
  typeof value === 'string' && Object.prototype.hasOwnProperty.call(ICON_PATHS, value);

/**
 * อีโมจิเดิม → ไอคอนเส้น (แอปเลิกใช้อีโมจิเป็นไอคอนแล้ว — ดูไม่พรีเมียมและหน้าตาต่างกันทุกยี่ห้อมือถือ)
 * props icon ที่ยังส่งอีโมจิมาจะถูกแปลงอัตโนมัติ · อีโมจิที่ไม่อยู่ในตาราง = ไม่แสดง
 */
const EMOJI_TO_ICON: Record<string, IconName> = {
  '➕': 'plus', '➖': 'minus', '✅': 'check-circle', '✔': 'check', '☑': 'check-circle', '❌': 'x-circle', '✖': 'x',
  '⚠': 'warning', 'ℹ': 'info', '❓': 'question', '❗': 'warning-circle', '🚫': 'prohibit', '⛔': 'prohibit',
  '🏠': 'house', '🏡': 'house', '🏪': 'storefront', '🏬': 'storefront', '🏢': 'buildings', '🏦': 'bank',
  '🛍': 'shopping-bag-open', '🛒': 'shopping-cart-simple', '🧺': 'basket', '🥬': 'basket', '🥦': 'basket', '🍅': 'basket',
  '🛵': 'moped', '🏍': 'motorcycle', '🚚': 'truck', '🚛': 'truck', '📦': 'package', '🏷': 'tag', '🎁': 'gift', '🎟': 'ticket',
  '👛': 'wallet', '💼': 'wallet', '💰': 'coins', '🪙': 'coins', '💵': 'money', '💸': 'money', '💳': 'credit-card', '🧾': 'receipt',
  '📋': 'clipboard-text', '📝': 'note-pencil', '✏': 'pencil-simple', '🖊': 'pencil-simple', '📄': 'file-text', '📃': 'file-text',
  '🗑': 'trash', '🔄': 'arrows-clockwise', '🔃': 'arrows-clockwise', '🔁': 'arrows-clockwise', '↩': 'arrow-counter-clockwise',
  '➡': 'arrow-right', '⬅': 'arrow-left', '🔙': 'arrow-left', '↗': 'arrow-up-right', '⬆': 'arrow-up', '⬇': 'arrow-down',
  '🔍': 'magnifying-glass', '🔎': 'magnifying-glass', '📍': 'map-pin', '📌': 'map-pin', '🗺': 'map-trifold', '🧭': 'compass',
  '🎯': 'target', '🛣': 'road-horizon', '⏱': 'timer', '⏲': 'timer', '⏳': 'hourglass', '⌛': 'hourglass', '🕐': 'clock', '🕒': 'clock',
  '⏰': 'clock', '📅': 'calendar', '📆': 'calendar', '🗓': 'calendar',
  '🔔': 'bell', '🔕': 'bell-simple', '📣': 'megaphone', '📢': 'megaphone', '💬': 'chat-circle-dots', '🗨': 'chats', '🎧': 'headset',
  '☎': 'phone', '📞': 'phone', '📱': 'device-mobile', '✉': 'envelope', '📧': 'envelope', '📨': 'envelope', '🌐': 'globe',
  '🔗': 'link', '📤': 'upload-simple', '📥': 'download-simple', '📷': 'camera', '📸': 'camera', '🖼': 'image',
  '⚙': 'gear-six', '🛠': 'gear-six', '🔧': 'gear-six', '🧰': 'squares-four', '📘': 'book-open', '📖': 'book-open', '📚': 'book-open',
  '🔒': 'lock', '🔐': 'lock-key', '🔓': 'sign-in', '🔑': 'key', '🛡': 'shield-check', '🆔': 'identification-card', '🪪': 'identification-card',
  '👤': 'user', '🙍': 'user', '👥': 'users-three', '🧑‍🤝‍🧑': 'users-three', '🤝': 'handshake', '🙋': 'hand-heart', '👋': 'hand-heart',
  '🚪': 'sign-out', '⭐': 'star', '🌟': 'star', '💛': 'heart', '❤': 'heart', '🧡': 'heart', '♥': 'heart',
  '✨': 'sparkle', '🎉': 'sparkle', '🎊': 'sparkle', '🔮': 'sparkle', '🃏': 'cards', '🔥': 'fire', '⚡': 'lightning', '💡': 'lightning',
  '📊': 'chart-bar', '📈': 'chart-line-up', '💹': 'trend-up', '🏆': 'trophy', '🥇': 'medal', '🏅': 'medal', '🚀': 'arrow-up-right',
  '🍳': 'cooking-pot', '🥘': 'cooking-pot', '🍲': 'cooking-pot', '🍜': 'bowl-food', '🍛': 'bowl-food', '🍚': 'bowl-food',
  '🍽': 'fork-knife', '🍴': 'fork-knife', '🥕': 'carrot', '🌿': 'leaf', '🍃': 'leaf', '🌱': 'plant', '☕': 'coffee', '🍰': 'cake',
  '🍔': 'hamburger', '🍕': 'pizza', '🍊': 'orange-slice', '🐟': 'fish', '🥚': 'egg-crack', '🍦': 'cake',
  '🌙': 'moon', '☀': 'sun', '🌞': 'sun', '🌍': 'globe', '🌏': 'globe', '🈳': 'translate', '🔤': 'translate',
  '📡': 'broadcast', '📶': 'cell-signal-full', '🔋': 'battery-full', '📴': 'wifi-slash', '☁': 'cloud-slash',
  '🧮': 'calculator', '💯': 'seal-check', '🆕': 'sparkle', '🆓': 'tag', '%': 'percent', '🚩': 'flag', '🛟': 'lifebuoy',
  '👆': 'hand-tap', '👉': 'hand-tap', '🖐': 'hand-tap', '👍': 'thumbs-up', '😊': 'smiley', '🙂': 'smiley', '😀': 'smiley',
  '🔢': 'list', '📑': 'clipboard-text', '🗂': 'squares-four', '🏁': 'flag', '🧹': 'trash', '🔏': 'shield', '🧿': 'eye',
  '👁': 'eye', '👀': 'eye', '🙈': 'eye-slash', '⏏': 'export', '🔌': 'power', '⏻': 'power', '🔀': 'arrows-clockwise',
};

/** แปลงค่า icon เดิม (ชื่อไอคอน หรืออีโมจิ) เป็นชื่อไอคอน — ไม่รู้จัก = null */
export const iconFromLegacy = (value: unknown): IconName | null => {
  if (isIconName(value)) return value;
  if (typeof value !== 'string') return null;
  const key = value.replace(/[︎️]/g, '').trim();
  return EMOJI_TO_ICON[key] ?? null;
};

export interface IconSlotProps {
  /** ชื่อไอคอน / อีโมจิเดิม / element */
  icon: React.ReactNode;
  size: number;
  color?: string;
  weight?: IconWeight;
}

/**
 * ช่องไอคอนของปุ่ม/ชิป/หัวข้อ — รับได้ทั้งชื่อไอคอน อีโมจิเดิม (แปลงให้) หรือ element
 * อีโมจิที่ไม่รู้จักจะไม่ถูกวาด (ไม่ให้อีโมจิหลุดกลับมาในแอป)
 */
export const IconSlot = ({ icon, size, color, weight }: IconSlotProps) => {
  if (icon === null || icon === undefined || icon === false || icon === '') return null;
  const name = iconFromLegacy(icon);
  if (name) return <Icon name={name} size={size} color={color} weight={weight} />;
  if (typeof icon === 'string' || typeof icon === 'number') return null;
  return <>{icon}</>;
};

export const Icon = memo(function Icon({
  name,
  size = 22,
  color,
  weight = 'regular',
  style,
  accessibilityLabel,
}: IconProps) {
  const { colors } = useTheme();
  const set = ICON_PATHS[name] as IconPathSet | undefined;
  if (!set) return null;

  const paths = (weight === 'bold' ? set.bold ?? set.regular : set[weight]) as readonly string[];
  const fill = color ?? colors.text;

  return (
    <Svg
      width={size}
      height={size}
      viewBox="0 0 256 256"
      style={style}
      accessible={!!accessibilityLabel}
      accessibilityLabel={accessibilityLabel}
      importantForAccessibility={accessibilityLabel ? 'yes' : 'no-hide-descendants'}
    >
      {paths.map((d, i) => (
        <Path key={i} d={d} fill={fill} />
      ))}
    </Svg>
  );
});

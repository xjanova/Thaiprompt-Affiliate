/**
 * AppIcon Component
 * ใช้แสดงไอคอน โดยพยายามใช้ Ionicons ก่อน
 * ถ้าโหลดไม่ได้จะใช้ Emoji fallback
 */

import React, { useState, useEffect } from 'react';
import { Text, View, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as Font from 'expo-font';

// Emoji mapping สำหรับ fallback
const EMOJI_MAP: Record<string, string> = {
  // Navigation
  'home': '🏠',
  'home-outline': '🏠',
  'arrow-back': '←',
  'arrow-forward': '→',
  'chevron-forward': '›',
  'chevron-back': '‹',
  'close': '✕',
  'menu': '☰',

  // Actions
  'add': '+',
  'add-circle': '⊕',
  'add-circle-outline': '⊕',
  'remove': '−',
  'checkmark': '✓',
  'checkmark-circle': '✓',
  'checkmark-circle-outline': '✓',
  'close-circle': '✕',
  'close-circle-outline': '✕',
  'refresh': '↻',
  'reload': '↻',
  'copy': '📋',
  'copy-outline': '📋',
  'share': '↗',
  'share-outline': '↗',
  'download': '⬇',
  'download-outline': '⬇',
  'upload': '⬆',
  'trash': '🗑',
  'trash-outline': '🗑',
  'create': '✏️',
  'create-outline': '✏️',
  'pencil': '✏️',
  'settings': '⚙️',
  'settings-outline': '⚙️',
  'search': '🔍',
  'search-outline': '🔍',
  'filter': '🔽',
  'filter-outline': '🔽',
  'options': '⋯',
  'options-outline': '⋯',
  'ellipsis-horizontal': '⋯',
  'ellipsis-vertical': '⋮',

  // User
  'person': '👤',
  'person-outline': '👤',
  'person-circle': '👤',
  'person-circle-outline': '👤',
  'people': '👥',
  'people-outline': '👥',

  // Communication
  'mail': '✉️',
  'mail-outline': '✉️',
  'chatbubble': '💬',
  'chatbubble-outline': '💬',
  'chatbubbles': '💬',
  'chatbubbles-outline': '💬',
  'call': '📞',
  'call-outline': '📞',
  'notifications': '🔔',
  'notifications-outline': '🔔',
  'alert': '⚠️',
  'alert-circle': '⚠️',
  'alert-circle-outline': '⚠️',
  'information': 'ℹ️',
  'information-circle': 'ℹ️',
  'information-circle-outline': 'ℹ️',
  'help': '❓',
  'help-circle': '❓',
  'help-circle-outline': '❓',

  // Media
  'camera': '📷',
  'camera-outline': '📷',
  'camera-reverse': '🔄',
  'camera-reverse-outline': '🔄',
  'image': '🖼',
  'image-outline': '🖼',
  'images': '🖼',
  'images-outline': '🖼',
  'play': '▶',
  'play-outline': '▶',
  'play-circle': '▶',
  'play-circle-outline': '▶',
  'pause': '⏸',
  'stop': '⏹',
  'videocam': '🎥',
  'videocam-outline': '🎥',
  'mic': '🎤',
  'mic-outline': '🎤',
  'volume-high': '🔊',
  'volume-low': '🔉',
  'volume-mute': '🔇',

  // Location
  'location': '📍',
  'location-outline': '📍',
  'navigate': '🧭',
  'navigate-outline': '🧭',
  'compass': '🧭',
  'compass-outline': '🧭',
  'map': '🗺',
  'map-outline': '🗺',
  'globe': '🌐',
  'globe-outline': '🌐',

  // Shopping
  'cart': '🛒',
  'cart-outline': '🛒',
  'bag': '🛍',
  'bag-outline': '🛍',
  'pricetag': '🏷',
  'pricetag-outline': '🏷',
  'card': '💳',
  'card-outline': '💳',
  'wallet': '👛',
  'wallet-outline': '👛',
  'cash': '💵',
  'cash-outline': '💵',
  'gift': '🎁',
  'gift-outline': '🎁',

  // Documents
  'document': '📄',
  'document-outline': '📄',
  'document-text': '📄',
  'document-text-outline': '📄',
  'documents': '📁',
  'documents-outline': '📁',
  'folder': '📁',
  'folder-outline': '📁',
  'file-tray': '📥',
  'file-tray-outline': '📥',
  'clipboard': '📋',
  'clipboard-outline': '📋',

  // Security
  'lock-closed': '🔒',
  'lock-closed-outline': '🔒',
  'lock-open': '🔓',
  'lock-open-outline': '🔓',
  'key': '🔑',
  'key-outline': '🔑',
  'shield': '🛡',
  'shield-outline': '🛡',
  'shield-checkmark': '🛡',
  'shield-checkmark-outline': '🛡',
  'finger-print': '👆',
  'eye': '👁',
  'eye-outline': '👁',
  'eye-off': '🙈',
  'eye-off-outline': '🙈',

  // Weather & Nature
  'sunny': '☀️',
  'sunny-outline': '☀️',
  'moon': '🌙',
  'moon-outline': '🌙',
  'cloud': '☁️',
  'cloud-outline': '☁️',
  'rainy': '🌧',
  'thunderstorm': '⛈',
  'snow': '❄️',
  'star': '⭐',
  'star-outline': '☆',
  'heart': '❤️',
  'heart-outline': '♡',
  'flame': '🔥',
  'leaf': '🍃',
  'water': '💧',

  // Tech
  'phone-portrait': '📱',
  'phone-portrait-outline': '📱',
  'laptop': '💻',
  'laptop-outline': '💻',
  'desktop': '🖥',
  'desktop-outline': '🖥',
  'wifi': '📶',
  'bluetooth': '📶',
  'qr-code': '📱',
  'qr-code-outline': '📱',
  'scan': '📷',
  'scan-outline': '📷',
  'barcode': '📊',
  'barcode-outline': '📊',
  'flash': '⚡',
  'flash-outline': '💡',
  'flash-off': '💡',
  'flash-off-outline': '💡',
  'battery-full': '🔋',
  'battery-half': '🔋',
  'battery-dead': '🪫',
  'power': '⏻',

  // Time
  'time': '🕐',
  'time-outline': '🕐',
  'timer': '⏱',
  'timer-outline': '⏱',
  'alarm': '⏰',
  'calendar': '📅',
  'calendar-outline': '📅',
  'today': '📅',
  'today-outline': '📅',

  // Social
  'logo-facebook': '📘',
  'logo-google': '🔍',
  'logo-twitter': '🐦',
  'logo-instagram': '📷',
  'logo-youtube': '▶️',
  'logo-linkedin': '💼',
  'logo-github': '🐙',

  // Misc
  'link': '🔗',
  'link-outline': '🔗',
  'open': '↗',
  'open-outline': '↗',
  'exit': '🚪',
  'exit-outline': '🚪',
  'log-out': '🚪',
  'log-out-outline': '🚪',
  'log-in': '🚪',
  'log-in-outline': '🚪',
  'enter': '↵',
  'enter-outline': '↵',
  'language': '🌐',
  'language-outline': '🌐',
  'flag': '🚩',
  'flag-outline': '🚩',
  'ribbon': '🎀',
  'trophy': '🏆',
  'trophy-outline': '🏆',
  'medal': '🏅',
  'medal-outline': '🏅',
  'trending-up': '📈',
  'trending-down': '📉',
  'stats-chart': '📊',
  'stats-chart-outline': '📊',
  'analytics': '📊',
  'analytics-outline': '📊',
  'pie-chart': '🥧',
  'pie-chart-outline': '🥧',
  'bar-chart': '📊',
  'bar-chart-outline': '📊',
  'grid': '⊞',
  'grid-outline': '⊞',
  'list': '☰',
  'list-outline': '☰',
  'layers': '📚',
  'layers-outline': '📚',
  'code': '</>',
  'code-outline': '</>',
  'terminal': '>_',
  'terminal-outline': '>_',
  'cube': '📦',
  'cube-outline': '📦',
  'rocket': '🚀',
  'rocket-outline': '🚀',
  'sparkles': '✨',
  'bulb': '💡',
  'bulb-outline': '💡',
  'happy': '😊',
  'happy-outline': '😊',
  'sad': '😢',
  'sad-outline': '😢',
  'thumbs-up': '👍',
  'thumbs-up-outline': '👍',
  'thumbs-down': '👎',
  'thumbs-down-outline': '👎',
};

interface AppIconProps {
  name: keyof typeof Ionicons.glyphMap | string;
  size?: number;
  color?: string;
  style?: object;
}

// ตรวจสอบว่า font โหลดสำเร็จหรือไม่
let fontsLoaded = false;

// พยายามเช็คว่า fonts โหลดแล้วหรือยัง
Font.isLoaded('Ionicons').then((loaded) => {
  fontsLoaded = loaded;
}).catch(() => {
  fontsLoaded = false;
});

export const AppIcon: React.FC<AppIconProps> = ({
  name,
  size = 24,
  color = '#000',
  style,
}) => {
  const [useEmoji, setUseEmoji] = useState(!fontsLoaded);

  useEffect(() => {
    // เช็คอีกครั้งหลัง mount
    const checkFont = async () => {
      try {
        const loaded = await Font.isLoaded('Ionicons');
        setUseEmoji(!loaded);
      } catch {
        setUseEmoji(true);
      }
    };
    checkFont();
  }, []);

  // ถ้า font โหลดได้ ใช้ Ionicons
  if (!useEmoji) {
    try {
      return (
        <Ionicons
          name={name as keyof typeof Ionicons.glyphMap}
          size={size}
          color={color}
          style={style}
        />
      );
    } catch {
      // Fall through to emoji
    }
  }

  // ใช้ Emoji fallback
  const emoji = EMOJI_MAP[name] || '•';
  return (
    <Text
      style={[
        styles.emoji,
        { fontSize: size * 0.9, color },
        style
      ]}
    >
      {emoji}
    </Text>
  );
};

// Preload icons - เรียกใช้ตอนเปิดแอพ
export const preloadIcons = async (): Promise<boolean> => {
  try {
    const loaded = await Font.isLoaded('Ionicons');
    fontsLoaded = loaded;
    return loaded;
  } catch {
    return false;
  }
};

const styles = StyleSheet.create({
  emoji: {
    textAlign: 'center',
  },
});

export default AppIcon;

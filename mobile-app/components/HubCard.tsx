/**
 * HubCard Component - การ์ดสำหรับแสดง Hub Item
 */

import React from 'react';
import { View, Text, Pressable } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import type { HubItem } from '@/types';

interface HubCardProps {
  item: HubItem;
  onPress: (item: HubItem) => void;
}

export const HubCard: React.FC<HubCardProps> = ({ item, onPress }) => {
  return (
    <Pressable
      onPress={() => onPress(item)}
      className="flex-1 m-1.5"
      style={({ pressed }) => ({
        opacity: pressed ? 0.8 : 1,
        transform: [{ scale: pressed ? 0.98 : 1 }],
      })}
    >
      <LinearGradient
        colors={[item.gradientStart, item.gradientEnd]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        className="rounded-2xl p-4 min-h-[140px] relative overflow-hidden"
      >
        {/* Badge */}
        {item.hasBadge && item.badgeText && (
          <View className="absolute top-2 right-2 bg-white/20 px-2 py-0.5 rounded-full">
            <Text className="text-white text-xs font-bold">
              {item.badgeText}
            </Text>
          </View>
        )}

        {/* Icon */}
        <Text className="text-4xl mb-2">{item.icon}</Text>

        {/* Title */}
        <Text className="text-white font-bold text-base mb-1">
          {item.title}
        </Text>

        {/* Subtitle */}
        <Text className="text-white/80 text-xs" numberOfLines={2}>
          {item.subtitle}
        </Text>

        {/* Decorative Circle */}
        <View
          className="absolute -bottom-8 -right-8 w-24 h-24 rounded-full bg-white/10"
        />
      </LinearGradient>
    </Pressable>
  );
};

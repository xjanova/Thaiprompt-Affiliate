/**
 * StatCard Component - การ์ดแสดงสถิติ
 *
 * แสดงสถิติพร้อมไอคอน เปอร์เซ็นต์การเปลี่ยนแปลง และเลย์เอาต์สวยงาม
 */

import React from 'react';
import { View, Text, Pressable } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Animated, { FadeInDown } from 'react-native-reanimated';

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon?: string;
  iconColor?: string;
  iconBgColor?: string;
  change?: number;
  changeLabel?: string;
  gradient?: [string, string];
  onPress?: () => void;
  delay?: number;
  isDark?: boolean;
  size?: 'small' | 'medium' | 'large';
}

export default function StatCard({
  title,
  value,
  subtitle,
  icon = '📊',
  iconColor = '#3B82F6',
  iconBgColor,
  change,
  changeLabel,
  gradient,
  onPress,
  delay = 0,
  isDark = true,
  size = 'medium',
}: StatCardProps) {
  const sizeConfig = {
    small: {
      padding: 'p-3',
      titleSize: 'text-xs',
      valueSize: 'text-lg',
      iconSize: 18,
      iconContainer: 'w-8 h-8',
    },
    medium: {
      padding: 'p-4',
      titleSize: 'text-sm',
      valueSize: 'text-xl',
      iconSize: 22,
      iconContainer: 'w-10 h-10',
    },
    large: {
      padding: 'p-5',
      titleSize: 'text-base',
      valueSize: 'text-2xl',
      iconSize: 26,
      iconContainer: 'w-12 h-12',
    },
  };

  const config = sizeConfig[size];

  const CardContent = (
    <View className="flex-1">
      {/* Header */}
      <View className="flex-row items-start justify-between mb-2">
        <View
          className={`${config.iconContainer} rounded-xl items-center justify-center`}
          style={{ backgroundColor: iconBgColor || `${iconColor}20` }}
        >
          <Text style={{ fontSize: config.iconSize }}>{icon}</Text>
        </View>

        {/* Change Indicator */}
        {change !== undefined && (
          <View
            className={`flex-row items-center px-2 py-1 rounded-full ${
              change >= 0 ? 'bg-green-500/20' : 'bg-red-500/20'
            }`}
          >
            <Text style={{ fontSize: 12 }}>
              {change >= 0 ? '↗️' : '↘️'}
            </Text>
            <Text
              className={`ml-0.5 text-xs font-medium ${
                change >= 0 ? 'text-green-500' : 'text-red-500'
              }`}
            >
              {Math.abs(change).toFixed(1)}%
            </Text>
          </View>
        )}
      </View>

      {/* Title */}
      <Text
        className={`${config.titleSize} ${
          gradient ? 'text-white/70' : isDark ? 'text-gray-400' : 'text-gray-500'
        }`}
      >
        {title}
      </Text>

      {/* Value */}
      <Text
        className={`${config.valueSize} font-bold mt-1 ${
          gradient ? 'text-white' : isDark ? 'text-white' : 'text-gray-900'
        }`}
      >
        {typeof value === 'number' ? value.toLocaleString() : value}
      </Text>

      {/* Subtitle */}
      {subtitle && (
        <Text
          className={`text-xs mt-1 ${
            gradient ? 'text-white/60' : isDark ? 'text-gray-500' : 'text-gray-400'
          }`}
        >
          {subtitle}
        </Text>
      )}

      {/* Change Label */}
      {changeLabel && (
        <Text
          className={`text-xs mt-1 ${
            gradient ? 'text-white/60' : isDark ? 'text-gray-500' : 'text-gray-400'
          }`}
        >
          {changeLabel}
        </Text>
      )}
    </View>
  );

  const cardStyles = `rounded-2xl ${config.padding} ${
    gradient ? '' : isDark ? 'bg-slate-800 border border-slate-700' : 'bg-white border border-gray-100'
  }`;

  if (gradient) {
    return (
      <Animated.View entering={FadeInDown.delay(delay).springify()}>
        <Pressable
          onPress={onPress}
          style={({ pressed }) => ({
            opacity: pressed && onPress ? 0.8 : 1,
            transform: [{ scale: pressed && onPress ? 0.98 : 1 }],
          })}
        >
          <LinearGradient
            colors={gradient}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            className={cardStyles}
          >
            {CardContent}
          </LinearGradient>
        </Pressable>
      </Animated.View>
    );
  }

  return (
    <Animated.View entering={FadeInDown.delay(delay).springify()}>
      <Pressable
        onPress={onPress}
        className={cardStyles}
        style={({ pressed }) => ({
          opacity: pressed && onPress ? 0.8 : 1,
          transform: [{ scale: pressed && onPress ? 0.98 : 1 }],
        })}
      >
        {CardContent}
      </Pressable>
    </Animated.View>
  );
}

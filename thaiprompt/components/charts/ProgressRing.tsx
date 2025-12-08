/**
 * ProgressRing Component - วงกลมแสดง Progress
 *
 * แสดงความคืบหน้าในรูปแบบวงกลม พร้อมไอคอนและข้อความ
 */

import React from 'react';
import { View, Text } from 'react-native';
import Svg, { Circle, Defs, LinearGradient, Stop } from 'react-native-svg';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeIn } from 'react-native-reanimated';

interface ProgressRingProps {
  progress: number; // 0-100
  size?: number;
  strokeWidth?: number;
  color?: string;
  gradientColors?: [string, string];
  backgroundColor?: string;
  centerIcon?: keyof typeof Ionicons.glyphMap;
  centerText?: string;
  centerSubtext?: string;
  showPercentage?: boolean;
  isDark?: boolean;
}

export default function ProgressRing({
  progress,
  size = 120,
  strokeWidth = 10,
  color = '#3B82F6',
  gradientColors,
  backgroundColor,
  centerIcon,
  centerText,
  centerSubtext,
  showPercentage = true,
  isDark = true,
}: ProgressRingProps) {
  // คำนวณค่าต่างๆ
  const radius = (size - strokeWidth) / 2;
  const circumference = radius * 2 * Math.PI;
  const clampedProgress = Math.min(Math.max(progress, 0), 100);
  const strokeDashoffset = circumference - (clampedProgress / 100) * circumference;

  const center = size / 2;
  const bgColor = backgroundColor || (isDark ? '#374151' : '#E5E7EB');

  return (
    <Animated.View entering={FadeIn.delay(100)} className="items-center justify-center">
      <Svg width={size} height={size}>
        <Defs>
          {gradientColors && (
            <LinearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <Stop offset="0%" stopColor={gradientColors[0]} />
              <Stop offset="100%" stopColor={gradientColors[1]} />
            </LinearGradient>
          )}
        </Defs>

        {/* Background Circle */}
        <Circle
          cx={center}
          cy={center}
          r={radius}
          stroke={bgColor}
          strokeWidth={strokeWidth}
          fill="none"
        />

        {/* Progress Circle */}
        <Circle
          cx={center}
          cy={center}
          r={radius}
          stroke={gradientColors ? 'url(#progressGradient)' : color}
          strokeWidth={strokeWidth}
          fill="none"
          strokeDasharray={circumference}
          strokeDashoffset={strokeDashoffset}
          strokeLinecap="round"
          transform={`rotate(-90 ${center} ${center})`}
        />
      </Svg>

      {/* Center Content */}
      <View
        className="absolute items-center justify-center"
        style={{ width: size, height: size }}
      >
        {centerIcon && (
          <Ionicons
            name={centerIcon}
            size={size * 0.2}
            color={gradientColors ? gradientColors[0] : color}
          />
        )}
        {centerText && (
          <Text
            className={`font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}
            style={{ fontSize: size * 0.15 }}
          >
            {centerText}
          </Text>
        )}
        {showPercentage && !centerText && (
          <Text
            className={`font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}
            style={{ fontSize: size * 0.18 }}
          >
            {clampedProgress.toFixed(0)}%
          </Text>
        )}
        {centerSubtext && (
          <Text
            className={`text-center ${isDark ? 'text-gray-400' : 'text-gray-500'}`}
            style={{ fontSize: size * 0.08 }}
          >
            {centerSubtext}
          </Text>
        )}
      </View>
    </Animated.View>
  );
}

// =====================================================
// Mini Progress Ring Component
// =====================================================

interface MiniProgressRingProps {
  progress: number;
  color?: string;
  size?: number;
  strokeWidth?: number;
  isDark?: boolean;
}

export function MiniProgressRing({
  progress,
  color = '#3B82F6',
  size = 24,
  strokeWidth = 3,
  isDark = true,
}: MiniProgressRingProps) {
  const radius = (size - strokeWidth) / 2;
  const circumference = radius * 2 * Math.PI;
  const clampedProgress = Math.min(Math.max(progress, 0), 100);
  const strokeDashoffset = circumference - (clampedProgress / 100) * circumference;
  const center = size / 2;

  return (
    <Svg width={size} height={size}>
      <Circle
        cx={center}
        cy={center}
        r={radius}
        stroke={isDark ? '#374151' : '#E5E7EB'}
        strokeWidth={strokeWidth}
        fill="none"
      />
      <Circle
        cx={center}
        cy={center}
        r={radius}
        stroke={color}
        strokeWidth={strokeWidth}
        fill="none"
        strokeDasharray={circumference}
        strokeDashoffset={strokeDashoffset}
        strokeLinecap="round"
        transform={`rotate(-90 ${center} ${center})`}
      />
    </Svg>
  );
}

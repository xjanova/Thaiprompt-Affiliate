/**
 * ErrorState Component - แสดงเมื่อไม่สามารถเชื่อมต่อเซิร์ฟเวอร์
 * ใช้ทุกที่ที่มีการเรียก API
 */

import React from 'react';
import { View, Text, Pressable } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import Animated, { FadeIn, ZoomIn } from 'react-native-reanimated';
import { useAppStore } from '@/stores/appStore';

interface ErrorStateProps {
  title?: string;
  message?: string;
  onRetry?: () => void;
  retryText?: string;
  icon?: keyof typeof Ionicons.glyphMap;
  showRetry?: boolean;
}

export const ErrorState = ({
  title = 'ไม่สามารถเชื่อมต่อได้',
  message = 'ขณะนี้ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้\nโปรดลองใหม่ภายหลัง',
  onRetry,
  retryText = 'ลองใหม่',
  icon = 'cloud-offline',
  showRetry = true,
}: ErrorStateProps) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <Animated.View
      entering={FadeIn}
      className="flex-1 justify-center items-center px-8 py-12"
    >
      <Animated.View
        entering={ZoomIn.springify()}
        className={`w-24 h-24 rounded-full items-center justify-center mb-6 ${
          isDark ? 'bg-gray-700' : 'bg-gray-100'
        }`}
      >
        <Ionicons
          name={icon}
          size={48}
          color={isDark ? '#EF4444' : '#DC2626'}
        />
      </Animated.View>

      <Text
        className={`text-xl font-bold text-center mb-2 ${
          isDark ? 'text-white' : 'text-gray-800'
        }`}
      >
        {title}
      </Text>

      <Text
        className={`text-center mb-6 leading-6 ${
          isDark ? 'text-gray-400' : 'text-gray-500'
        }`}
      >
        {message}
      </Text>

      {showRetry && onRetry && (
        <Pressable
          onPress={onRetry}
          className="bg-primary-500 px-8 py-3 rounded-xl flex-row items-center"
        >
          <Ionicons name="refresh" size={20} color="white" />
          <Text className="text-white font-bold ml-2">{retryText}</Text>
        </Pressable>
      )}
    </Animated.View>
  );
};

/**
 * NetworkErrorBanner - แบนเนอร์แสดงเมื่อไม่มีเครือข่าย
 */
export const NetworkErrorBanner = ({
  onRetry,
  isRetrying,
}: {
  onRetry?: () => void;
  isRetrying?: boolean;
}) => {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  return (
    <Animated.View
      entering={FadeIn}
      className={`mx-4 mb-4 rounded-2xl p-4 ${
        isDark ? 'bg-red-900/30' : 'bg-red-50'
      }`}
    >
      <View className="flex-row items-center">
        <View
          className={`w-10 h-10 rounded-full items-center justify-center mr-3 ${
            isDark ? 'bg-red-800/50' : 'bg-red-100'
          }`}
        >
          <Ionicons
            name="cloud-offline"
            size={20}
            color={isDark ? '#FCA5A5' : '#DC2626'}
          />
        </View>
        <View className="flex-1">
          <Text
            className={`font-bold ${
              isDark ? 'text-red-200' : 'text-red-700'
            }`}
          >
            ไม่สามารถเชื่อมต่อได้
          </Text>
          <Text
            className={`text-sm ${
              isDark ? 'text-red-300' : 'text-red-600'
            }`}
          >
            โปรดตรวจสอบการเชื่อมต่ออินเทอร์เน็ต
          </Text>
        </View>
        {onRetry && (
          <Pressable
            onPress={onRetry}
            disabled={isRetrying}
            className={`px-4 py-2 rounded-xl ${
              isDark ? 'bg-red-800/50' : 'bg-red-100'
            } ${isRetrying ? 'opacity-50' : ''}`}
          >
            <Text
              className={`font-medium ${
                isDark ? 'text-red-200' : 'text-red-600'
              }`}
            >
              {isRetrying ? 'กำลังลอง...' : 'ลองใหม่'}
            </Text>
          </Pressable>
        )}
      </View>
    </Animated.View>
  );
};

export default ErrorState;

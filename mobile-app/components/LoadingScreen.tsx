/**
 * Loading Screen Component - หน้า loading
 */

import React from 'react';
import { View, Text, ActivityIndicator } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

interface LoadingScreenProps {
  message?: string;
}

export const LoadingScreen: React.FC<LoadingScreenProps> = ({
  message = 'กำลังโหลด...',
}) => {
  return (
    <LinearGradient
      colors={['#0F0F23', '#1A1A2E']}
      className="flex-1 justify-center items-center"
    >
      {/* Logo */}
      <View className="mb-8">
        <Text className="text-6xl">🚀</Text>
      </View>

      {/* App Name */}
      <Text className="text-white text-2xl font-bold mb-2">
        Thaiprompt Affiliate
      </Text>

      {/* Loading Indicator */}
      <ActivityIndicator size="large" color="#3B82F6" className="my-6" />

      {/* Message */}
      <Text className="text-gray-400 text-base">{message}</Text>

      {/* Animated Dots */}
      <View className="flex-row mt-4">
        {[0, 1, 2].map((i) => (
          <View
            key={i}
            className="w-2 h-2 rounded-full bg-primary-500 mx-1"
            style={{
              opacity: 0.3 + (i * 0.3),
            }}
          />
        ))}
      </View>
    </LinearGradient>
  );
};

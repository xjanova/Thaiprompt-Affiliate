/**
 * Loading Screen Component - หน้า loading พร้อม animation
 */

import React, { useEffect, useRef } from 'react';
import { View, Text, Animated, Easing } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

interface LoadingScreenProps {
  message?: string;
}

export const LoadingScreen: React.FC<LoadingScreenProps> = ({
  message = 'กำลังโหลด...',
}) => {
  // Animation values สำหรับ dots
  const dot1Opacity = useRef(new Animated.Value(0.3)).current;
  const dot2Opacity = useRef(new Animated.Value(0.3)).current;
  const dot3Opacity = useRef(new Animated.Value(0.3)).current;

  // Animation value สำหรับ logo pulse
  const logoScale = useRef(new Animated.Value(1)).current;

  // Animation value สำหรับ spinner rotation
  const spinValue = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Dot animation - sequential fade
    const dotAnimation = () => {
      Animated.sequence([
        Animated.timing(dot1Opacity, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.timing(dot2Opacity, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.timing(dot3Opacity, {
          toValue: 1,
          duration: 300,
          useNativeDriver: true,
        }),
        Animated.parallel([
          Animated.timing(dot1Opacity, {
            toValue: 0.3,
            duration: 300,
            useNativeDriver: true,
          }),
          Animated.timing(dot2Opacity, {
            toValue: 0.3,
            duration: 300,
            useNativeDriver: true,
          }),
          Animated.timing(dot3Opacity, {
            toValue: 0.3,
            duration: 300,
            useNativeDriver: true,
          }),
        ]),
      ]).start(() => dotAnimation());
    };

    // Logo pulse animation
    const pulseAnimation = () => {
      Animated.sequence([
        Animated.timing(logoScale, {
          toValue: 1.1,
          duration: 1000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(logoScale, {
          toValue: 1,
          duration: 1000,
          easing: Easing.inOut(Easing.ease),
          useNativeDriver: true,
        }),
      ]).start(() => pulseAnimation());
    };

    // Spinner rotation
    const spinAnimation = () => {
      spinValue.setValue(0);
      Animated.timing(spinValue, {
        toValue: 1,
        duration: 1500,
        easing: Easing.linear,
        useNativeDriver: true,
      }).start(() => spinAnimation());
    };

    dotAnimation();
    pulseAnimation();
    spinAnimation();
  }, []);

  const spin = spinValue.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <LinearGradient
      colors={['#0F0F23', '#1A1A2E']}
      style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}
    >
      {/* Logo with pulse animation */}
      <Animated.View
        style={{
          marginBottom: 32,
          transform: [{ scale: logoScale }],
        }}
      >
        <Text style={{ fontSize: 64 }}>🚀</Text>
      </Animated.View>

      {/* App Name */}
      <Text
        style={{
          color: '#FFFFFF',
          fontSize: 24,
          fontWeight: 'bold',
          marginBottom: 8,
        }}
      >
        Thaiprompt Affiliate
      </Text>

      {/* Custom Spinner */}
      <Animated.View
        style={{
          width: 40,
          height: 40,
          marginVertical: 24,
          transform: [{ rotate: spin }],
        }}
      >
        <View
          style={{
            width: 40,
            height: 40,
            borderRadius: 20,
            borderWidth: 3,
            borderColor: '#3B82F6',
            borderTopColor: 'transparent',
          }}
        />
      </Animated.View>

      {/* Message */}
      <Text style={{ color: '#9CA3AF', fontSize: 16 }}>{message}</Text>

      {/* Animated Dots */}
      <View style={{ flexDirection: 'row', marginTop: 16 }}>
        <Animated.View
          style={{
            width: 8,
            height: 8,
            borderRadius: 4,
            backgroundColor: '#3B82F6',
            marginHorizontal: 4,
            opacity: dot1Opacity,
          }}
        />
        <Animated.View
          style={{
            width: 8,
            height: 8,
            borderRadius: 4,
            backgroundColor: '#3B82F6',
            marginHorizontal: 4,
            opacity: dot2Opacity,
          }}
        />
        <Animated.View
          style={{
            width: 8,
            height: 8,
            borderRadius: 4,
            backgroundColor: '#3B82F6',
            marginHorizontal: 4,
            opacity: dot3Opacity,
          }}
        />
      </View>

      {/* Version */}
      <Text
        style={{
          position: 'absolute',
          bottom: 40,
          color: '#6B7280',
          fontSize: 12,
        }}
      >
        Version 1.0.0
      </Text>
    </LinearGradient>
  );
};

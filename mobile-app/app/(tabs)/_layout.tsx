/**
 * Tab Layout - Bottom Navigation
 */

import React from 'react';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAppStore } from '@/stores/appStore';
import { View, Text } from 'react-native';
import * as Haptics from 'expo-haptics';

export default function TabLayout() {
  const { resolvedTheme } = useAppStore();
  const isDark = resolvedTheme === 'dark';

  const tabBarStyle = {
    backgroundColor: isDark ? '#1A1A2E' : '#FFFFFF',
    borderTopColor: isDark ? '#2D2D44' : '#E5E7EB',
    borderTopWidth: 1,
    height: 85,
    paddingBottom: 25,
    paddingTop: 10,
  };

  const handleTabPress = () => {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
  };

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle,
        tabBarActiveTintColor: '#3B82F6',
        tabBarInactiveTintColor: isDark ? '#6B7280' : '#9CA3AF',
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '600',
        },
      }}
      screenListeners={{
        tabPress: handleTabPress,
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'หน้าหลัก',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="home" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="network"
        options={{
          title: 'สายงาน',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="people" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="wallet"
        options={{
          title: 'กระเป๋า',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="wallet" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'โปรไฟล์',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="person" size={size} color={color} />
          ),
        }}
      />
    </Tabs>
  );
}

/**
 * Tab Layout - Premium Bottom Navigation
 * ใช้ StyleSheet + LinearGradient + Ionicons (ไม่ใช้ NativeWind)
 */

import React from 'react';
import { Tabs } from 'expo-router';
import { View, StyleSheet, Platform } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';

// Tab Icon Component with Glow Effect
const TabIcon = ({
  name,
  focused,
  color,
}: {
  name: keyof typeof Ionicons.glyphMap;
  focused: boolean;
  color: string;
}) => {
  if (focused) {
    return (
      <View style={styles.activeIconContainer}>
        {/* Glow Effect */}
        <View style={styles.glowEffect} />
        <LinearGradient
          colors={['#3B82F6', '#8B5CF6']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.activeIconGradient}
        >
          <Ionicons name={name} size={22} color="#FFFFFF" />
        </LinearGradient>
      </View>
    );
  }

  return (
    <View style={styles.inactiveIconContainer}>
      <Ionicons name={name} size={24} color={color} />
    </View>
  );
};

export default function TabLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarStyle: styles.tabBar,
        tabBarActiveTintColor: '#3B82F6',
        tabBarInactiveTintColor: '#6B7280',
        tabBarLabelStyle: styles.tabBarLabel,
        tabBarItemStyle: styles.tabBarItem,
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'หน้าหลัก',
          tabBarIcon: ({ focused, color }) => (
            <TabIcon name="home" focused={focused} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="network"
        options={{
          title: 'สายงาน',
          tabBarIcon: ({ focused, color }) => (
            <TabIcon name="people" focused={focused} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="wallet"
        options={{
          title: 'กระเป๋า',
          tabBarIcon: ({ focused, color }) => (
            <TabIcon name="wallet" focused={focused} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'โปรไฟล์',
          tabBarIcon: ({ focused, color }) => (
            <TabIcon name="person" focused={focused} color={color} />
          ),
        }}
      />
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(15, 23, 42, 0.95)',
    borderTopWidth: 0,
    height: Platform.OS === 'ios' ? 88 : 70,
    paddingBottom: Platform.OS === 'ios' ? 28 : 10,
    paddingTop: 12,
    paddingHorizontal: 10,
    // Shadow
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
    elevation: 20,
    // Glass effect border
    borderTopColor: 'rgba(255, 255, 255, 0.1)',
  },
  tabBarLabel: {
    fontSize: 10,
    fontWeight: '600',
    marginTop: 2,
  },
  tabBarItem: {
    paddingVertical: 4,
  },
  activeIconContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  glowEffect: {
    position: 'absolute',
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: 'rgba(59, 130, 246, 0.3)',
    transform: [{ scale: 1.2 }],
  },
  activeIconGradient: {
    width: 42,
    height: 42,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    // Shadow for depth
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 8,
    elevation: 8,
  },
  inactiveIconContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    width: 42,
    height: 42,
  },
});

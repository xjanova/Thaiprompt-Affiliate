/**
 * Tab Layout - Stable Premium Version
 * แก้ไข: crash on resume
 * ใช้ emoji icons แทน Ionicons
 */

import React from 'react';
import { Tabs } from 'expo-router';
import { View, Text, StyleSheet, Platform } from 'react-native';

// Tab icons ใช้ emoji
const TAB_ICONS = {
  home: { active: '🏠', inactive: '🏠' },
  network: { active: '👥', inactive: '👥' },
  wallet: { active: '💰', inactive: '💰' },
  profile: { active: '👤', inactive: '👤' },
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
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'หน้าหลัก',
          tabBarIcon: ({ focused }) => (
            <View style={focused ? styles.activeTab : styles.inactiveTab}>
              <Text style={styles.tabEmoji}>
                {focused ? TAB_ICONS.home.active : TAB_ICONS.home.inactive}
              </Text>
            </View>
          ),
        }}
      />
      <Tabs.Screen
        name="network"
        options={{
          title: 'สายงาน',
          tabBarIcon: ({ focused }) => (
            <View style={focused ? styles.activeTab : styles.inactiveTab}>
              <Text style={styles.tabEmoji}>
                {focused ? TAB_ICONS.network.active : TAB_ICONS.network.inactive}
              </Text>
            </View>
          ),
        }}
      />
      <Tabs.Screen
        name="wallet"
        options={{
          title: 'กระเป๋า',
          tabBarIcon: ({ focused }) => (
            <View style={focused ? styles.activeTab : styles.inactiveTab}>
              <Text style={styles.tabEmoji}>
                {focused ? TAB_ICONS.wallet.active : TAB_ICONS.wallet.inactive}
              </Text>
            </View>
          ),
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'โปรไฟล์',
          tabBarIcon: ({ focused }) => (
            <View style={focused ? styles.activeTab : styles.inactiveTab}>
              <Text style={styles.tabEmoji}>
                {focused ? TAB_ICONS.profile.active : TAB_ICONS.profile.inactive}
              </Text>
            </View>
          ),
        }}
      />
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    backgroundColor: '#0F172A',
    borderTopWidth: 0,
    height: Platform.OS === 'ios' ? 85 : 65,
    paddingBottom: Platform.OS === 'ios' ? 25 : 8,
    paddingTop: 8,
    elevation: 0,
    shadowOpacity: 0,
  },
  tabBarLabel: {
    fontSize: 10,
    fontWeight: '600',
  },
  activeTab: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: '#3B82F6',
    alignItems: 'center',
    justifyContent: 'center',
  },
  inactiveTab: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabEmoji: {
    fontSize: 20,
  },
});

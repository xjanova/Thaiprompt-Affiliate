/**
 * Notifications Tab - Redirect to full notifications page
 * Tab นี้ทำหน้าที่ redirect ไปหน้า notifications เต็มรูปแบบ
 */

import { useEffect } from 'react';
import { View, ActivityIndicator } from 'react-native';
import { router } from 'expo-router';

export default function NotificationsTab() {
  useEffect(() => {
    // Redirect ไปหน้า notifications หลัก
    router.replace('/notifications');
  }, []);

  return (
    <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0F172A' }}>
      <ActivityIndicator size="large" color="#3B82F6" />
    </View>
  );
}

/**
 * ออเดอร์ตลาดสดของฉัน — ใช้หน้าคำสั่งซื้อเดียวกับแท็บ แต่เปิดที่ "ตลาดสด" ก่อน
 */

import React from 'react';
import { MyOrdersScreen } from '@/components/screens/MyOrdersScreen';

export default function TaladsodOrdersScreen() {
  return <MyOrdersScreen initialSource="fresh" />;
}

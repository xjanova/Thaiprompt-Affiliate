/**
 * Orders Screen - หน้ารายการคำสั่งซื้อ (เปิดแบบ stack เช่น หลังชำระเงินเสร็จ)
 * เนื้อหาเดียวกับแท็บ "คำสั่งซื้อ" แต่มีปุ่มย้อนกลับ
 */

import React from 'react';
import { MyOrdersScreen } from '@/components/screens/MyOrdersScreen';

export default function OrdersScreen() {
  return <MyOrdersScreen />;
}

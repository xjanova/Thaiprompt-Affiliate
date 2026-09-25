/**
 * แท็บ "คำสั่งซื้อ" — รายการคำสั่งซื้อของฉัน (ไม่มีปุ่มย้อนกลับ)
 */

import React from 'react';
import { MyOrdersScreen } from '@/components/screens/MyOrdersScreen';

export default function OrdersTab() {
  return <MyOrdersScreen embedded />;
}

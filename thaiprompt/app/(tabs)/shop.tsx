/**
 * แท็บ "ช้อป" — ใช้หน้าช้อปปิ้งเดิมแบบฝังในแท็บ (ไม่มีปุ่มย้อนกลับ)
 * หน้า /shopping ยังเปิดแบบ stack ได้ตามเดิม (ลิงก์จากแบนเนอร์/หน้าอื่น)
 */

import React from 'react';
import ShoppingScreen from '../shopping';

export default function ShopTab() {
  return <ShoppingScreen embedded />;
}

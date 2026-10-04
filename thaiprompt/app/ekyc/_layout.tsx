/**
 * ขั้นตอนยืนยันตัวตนด้วย AI (eKYC) — stack ซ้อนของตัวเอง
 *
 *   /ekyc            แนะนำ + ยินยอม PDPA (?from=checkout|rider|seller|withdraw|profile)
 *   /ekyc/capture    ถ่ายบัตรประชาชน (ตัวสแกนเอกสารของ Google / กล้องในแอป)
 *   /ekyc/review     ตรวจ/แก้ข้อมูลที่ AI อ่านได้
 *   /ekyc/face       ยืนยันใบหน้าตามคำสั่งสุ่ม
 *   /ekyc/processing ส่งตรวจ
 *   /ekyc/result     ผล (สำเร็จ / ส่งเจ้าหน้าที่ / ถ่ายใหม่ / ไม่ผ่าน) — push kyc_result เปิดหน้านี้
 *
 * stack ซ้อน → จบแล้ว "กลับไปทำต่อ" ปิดทั้งชุดในครั้งเดียว (useEkycExit) กลับหน้าที่พามา
 * ไม่มีการปัดย้อนกลับ (iOS) ระหว่างกล้อง/ส่งตรวจ — แต่ละหน้าจัดการปุ่มย้อนกลับเอง
 */

import React from 'react';
import { Stack } from 'expo-router';
import { useTheme } from '@/theme';

export default function EkycLayout() {
  const { colors } = useTheme();
  return (
    <Stack
      screenOptions={{
        headerShown: false,
        animation: 'slide_from_right',
        contentStyle: { backgroundColor: colors.background },
      }}
    >
      <Stack.Screen name="index" />
      <Stack.Screen name="capture" options={{ gestureEnabled: false }} />
      <Stack.Screen name="review" />
      <Stack.Screen name="face" options={{ gestureEnabled: false }} />
      <Stack.Screen name="processing" options={{ gestureEnabled: false, animation: 'fade' }} />
      <Stack.Screen name="result" options={{ gestureEnabled: false, animation: 'fade' }} />
    </Stack>
  );
}

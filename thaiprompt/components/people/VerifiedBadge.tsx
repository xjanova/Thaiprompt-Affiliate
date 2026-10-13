/**
 * VerifiedBadge — ตรา "ยืนยันตัวตนแล้ว" (eKYC)
 *
 * (2026-10-13) เปลี่ยนหน้าตาเป็นตรากลมเขียวแบบ B (VerifiedSeal: พื้นมิ้นต์ ขอบเขียว โล่มีเครื่องหมายถูก)
 * ให้เข้าคู่กับตรา "ผ่านการอบรม" (TrainingSeal น้ำเงินกรมท่าขอบทอง) ที่วางถัดไป — ขนาด/props เดิมทุกอย่าง
 * ทุกจุดที่ใช้ VerifiedBadge จึงได้ตราใหม่โดยเลย์เอาต์ไม่ขยับ
 *
 * ความน่าเชื่อถือมาจากตรานี้ ไม่ใช่จากรูปโปรไฟล์ (รูปโปรไฟล์เป็นรูปอะไรก็ได้ที่ผู้ใช้เลือก)
 * แสดงเฉพาะเมื่อ server ส่ง verified = true มาเท่านั้น — ห้ามเดาเอง
 *
 * @example
 * <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}>
 *   <Text>{rider.display_name}</Text>
 *   {rider.verified && <VerifiedBadge size={16} />}
 *   <TrainingBadgeSeal value={rider.training_badge} role="rider" size={16} />
 * </View>
 */

import React, { memo } from 'react';
import type { StyleProp, ViewStyle } from 'react-native';
import { VerifiedSeal } from '@/components/badges/TrustSeals';

export interface VerifiedBadgeProps {
  /** ขนาดตรา (ค่าเริ่มต้น 16) */
  size?: number;
  /** แสดงคำว่า "ยืนยันตัวตนแล้ว" ต่อท้าย */
  withLabel?: boolean;
  /** วงขอบรอบตรา (ใช้ตอนวางทับมุมรูป) — ส่งสีพื้นที่วางอยู่ */
  ringColor?: string;
  style?: StyleProp<ViewStyle>;
}

/** ค่าจริงของ verified จาก server (true/1/"1") */
export const isVerifiedFlag = (value: unknown): boolean => value === true || value === 1 || value === '1';

export const VerifiedBadge: React.FC<VerifiedBadgeProps> = memo(({ size = 16, withLabel = false, ringColor, style }) => (
  <VerifiedSeal size={size} withLabel={withLabel} ringColor={ringColor} style={style} />
));

VerifiedBadge.displayName = 'VerifiedBadge';

export default VerifiedBadge;

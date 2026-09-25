/**
 * สั่นเบาๆ ตอนกด — ห่อ expo-haptics ไว้ไม่ให้ error ทำแอปล้ม
 * (บางเครื่อง/อีมูเลเตอร์ไม่มีมอเตอร์สั่น → เงียบไป)
 */

import * as Haptics from 'expo-haptics';

/** สั่นแบบกดปุ่ม (เบา) */
export const tapHaptic = (): void => {
  try {
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light).catch(() => {});
  } catch {
    // ไม่มี haptics ก็ไม่เป็นไร
  }
};

/** สั่นแบบเลือกรายการ (เบามาก) */
export const selectionHaptic = (): void => {
  try {
    Haptics.selectionAsync().catch(() => {});
  } catch {
    // ไม่มี haptics ก็ไม่เป็นไร
  }
};

/** สั่นแจ้งผลลัพธ์ (สำเร็จ/เตือน/ผิดพลาด) */
export const resultHaptic = (kind: 'success' | 'warning' | 'error'): void => {
  try {
    const type =
      kind === 'success'
        ? Haptics.NotificationFeedbackType.Success
        : kind === 'warning'
          ? Haptics.NotificationFeedbackType.Warning
          : Haptics.NotificationFeedbackType.Error;
    Haptics.notificationAsync(type).catch(() => {});
  } catch {
    // ไม่มี haptics ก็ไม่เป็นไร
  }
};

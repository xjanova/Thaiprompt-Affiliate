/**
 * ข้อความไทย + ไอคอนของท่าทางยืนยันใบหน้า และคำใบ้ระหว่างถ่าย
 * (ทิศหันหน้าเป็นมุมมองของผู้ใช้เอง: "หันไปทางซ้าย" = ซ้ายของผู้ใช้ ซึ่งบนจอแบบกระจกก็คือซ้ายของจอ)
 */

import type { IconName } from '@/components/ui';
import type { EkycFaceLabel } from '@/services/api/ekycApi';
import type { LivenessHint } from '@/services/ekyc/liveness';

export const CHALLENGE_UI: Record<EkycFaceLabel, { instruction: string; short: string; icon: IconName }> = {
  neutral: { instruction: 'มองตรงที่กล้อง', short: 'มองตรง', icon: 'scan-smiley' },
  blink: { instruction: 'กระพริบตาช้าๆ', short: 'กระพริบตา', icon: 'eye' },
  turn_left: { instruction: 'หันหน้าไปทางซ้ายช้าๆ', short: 'หันซ้าย', icon: 'arrow-left' },
  turn_right: { instruction: 'หันหน้าไปทางขวาช้าๆ', short: 'หันขวา', icon: 'arrow-right' },
  smile: { instruction: 'ยิ้มกว้างๆ', short: 'ยิ้ม', icon: 'smiley' },
  nod: { instruction: 'ก้มหน้าลงช้าๆ', short: 'พยักหน้า', icon: 'arrow-down' },
};

export const LIVENESS_HINT_TEXT: Record<LivenessHint, string> = {
  NO_FACE: 'ยังไม่เห็นใบหน้า ให้หน้าอยู่ในกรอบวงรี',
  MULTIPLE_FACES: 'เห็นหลายใบหน้า ให้มีแค่คุณคนเดียวในกล้อง',
  MOVE_CLOSER: 'ขยับเข้ามาใกล้กล้องอีกนิด',
  MOVE_BACK: 'ถอยออกจากกล้องอีกนิด',
  CENTER_FACE: 'ให้ใบหน้าอยู่กลางกรอบ',
  LOOK_STRAIGHT: 'มองตรงที่กล้องก่อนนะ',
  OPEN_EYES: 'ลืมตาตามปกติ',
  DO_ACTION: '',
  MORE: 'อีกนิด…',
  WRONG_WAY: 'หันผิดทาง ลองหันอีกด้านนะ',
  GOOD: '',
};

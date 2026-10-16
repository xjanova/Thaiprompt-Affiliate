/**
 * ตราความน่าเชื่อถือ — ยืนยันตัวตน (eKYC) + ผ่านการอบรม (ห้องเรียน)
 *
 * import { VerifiedSeal, TrainingSeal, TrustSeals, parseTrainingBadge } from '@/components/badges';
 */

export { VerifiedSeal, TrainingSeal, TrainingBadgeSeal, TrustSeals, SEAL_COLORS } from './TrustSeals';
export type { VerifiedSealProps, TrainingSealProps, TrainingBadgeSealProps, TrustSealsProps } from './TrustSeals';
export {
  TRAINING_ROLES,
  TRAINING_ROLE_NAME,
  TRAINING_BADGE_LABEL,
  NEEDS_REFRESH_LABEL,
  isTruthyFlag,
  parseTrainingRole,
  parseTrainingBadge,
  parseTrainingBadges,
  trainingBadgeA11yLabel,
  pickProfileBadge,
  certifiedRoles,
} from './trainingBadge';
export type { TrainingRole, TrainingBadge, TrainingBadges } from './trainingBadge';

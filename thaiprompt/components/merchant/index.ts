/**
 * คอมโพเนนต์โหมดคนขาย (ร้านตลาดสด / สลับร้าน)
 *
 * import { MerchantModeSwitch, PresenceCard, OpenShopSheet, FmOrderCard, OptionGroupsEditor } from '@/components/merchant';
 */

export { MerchantModeSwitch } from './MerchantModeSwitch';
export type { MerchantMode, MerchantModeSwitchProps } from './MerchantModeSwitch';

export { PresenceCard } from './PresenceCard';
export type { PresenceCardProps } from './PresenceCard';

export { OpenShopSheet } from './OpenShopSheet';
export type { OpenShopSheetProps } from './OpenShopSheet';

export { FmOrderCard } from './FmOrderCard';
export type { FmOrderCardProps } from './FmOrderCard';

export {
  OptionGroupsEditor,
  toDraft,
  fromDraft,
  newDraftGroup,
  newDraftOption,
  parsePrice,
} from './OptionGroupsEditor';
export type { DraftGroup, DraftOption, OptionGroupsEditorProps } from './OptionGroupsEditor';

export { useShopLocationConsent, hasShopLocationConsent } from './useShopLocationConsent';

export * from './fmHelpers';

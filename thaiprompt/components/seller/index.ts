/**
 * คอมโพเนนต์หลังร้านในแอป (สมัครเปิดร้าน / จัดการสินค้า / ตั้งค่าร้าน)
 *
 * import { SellerGateNotice, ProductListSkeleton, FormSkeleton, ToggleRow, FormCard, ErrorNote, pickImages } from '@/components/seller';
 */

export {
  ErrorNote,
  FormCard,
  FormSkeleton,
  ProductListSkeleton,
  SellerGateNotice,
  Skeleton,
  ToggleRow,
  isGateFailure,
} from './SellerKit';
export type { SellerGateNoticeProps, ToggleRowProps } from './SellerKit';

export { choosePhotoSource, pickImages } from './pickImages';
export type { PhotoSource, PickedImages } from './pickImages';

export { parseMoney, parseInteger, formatInputNumber } from './formNumbers';

export { ProductImageStrip } from './ProductImageStrip';
export type { ProductImageStripProps, StripImage } from './ProductImageStrip';

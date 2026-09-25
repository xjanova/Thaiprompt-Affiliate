/**
 * UI kit ธีม "รอยัล น้ำเงินกรมท่า-ทอง" — import จากที่นี่ที่เดียว
 *
 * import { Text, Icon, BrandArt, RoyalHeader, GlassIconButton, IconButton,
 *          Button3D, Card3D, Chip, Pill, SectionHeader, StatTile, EmptyState,
 *          BannerCard, BannerSlider, ConsentSheet, WebsiteButton, openWebsite,
 *          PriceText, formatBaht, Screen, ScreenHeader } from '@/components/ui';
 *
 * กติกา: ห้ามใช้อีโมจิเป็นไอคอน (ใช้ <Icon name=.../> หรือส่งชื่อไอคอนให้ prop icon)
 *        Text/TextInput ต้อง import จาก '@/components/ui/Text' (ฟอนต์ Anuphan อัตโนมัติ)
 */

export { Text, TextInput } from './Text';

export { Icon, IconSlot, isIconName, iconFromLegacy } from './Icon';
export type { IconName, IconProps, IconWeight, IconSlotProps } from './Icon';

export { BrandArt, BRAND_ART } from './BrandArt';
export type { BrandArtName, BrandArtProps } from './BrandArt';

export { RoyalHeader, GlassIconButton, OnHeaderProvider, useOnHeader } from './RoyalHeader';
export type { RoyalHeaderProps, GlassIconButtonProps } from './RoyalHeader';

export { IconButton } from './IconButton';
export type { IconButtonProps } from './IconButton';

export { Button3D } from './Button3D';
export type { Button3DProps, Button3DVariant, Button3DSize } from './Button3D';

export { Card3D } from './Card3D';
export type { Card3DProps, Card3DVariant } from './Card3D';

export { Chip, Pill } from './Chip';
export type { ChipProps, PillProps } from './Chip';

export { SectionHeader } from './SectionHeader';
export type { SectionHeaderProps } from './SectionHeader';

export { StatTile } from './StatTile';
export type { StatTileProps } from './StatTile';

export { EmptyState } from './EmptyState';
export type { EmptyStateProps, EmptyStateVariant } from './EmptyState';

export { BannerCard } from './BannerCard';
export type { BannerCardProps } from './BannerCard';

export { BannerSlider, openBannerTarget } from './BannerSlider';
export type { BannerSliderProps } from './BannerSlider';

export { ConsentSheet } from './ConsentSheet';
export type { ConsentSheetProps, ConsentReason } from './ConsentSheet';

export { WebsiteButton, openWebsite } from './WebsiteButton';
export type { WebsiteButtonProps, OpenWebsiteResult } from './WebsiteButton';

export { PriceText, formatBaht, toAmount } from './PriceText';
export type { PriceTextProps, FormatBahtOptions, PriceDecimals } from './PriceText';

export { Screen, ScreenHeader } from './Screen';
export type { ScreenProps, ScreenHeaderProps } from './Screen';

export { usePressGuard } from './usePressGuard';
export { tapHaptic, selectionHaptic, resultHaptic } from './haptics';

export { LiveMap, openInGoogleMaps } from './LiveMap';
export type { LiveMapProps, LiveMapMarker, LiveMapMarkerKind } from './LiveMap';

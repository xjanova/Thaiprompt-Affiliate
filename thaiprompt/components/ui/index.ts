/**
 * UI kit ธีม "นวลทองคำ" — import จากที่นี่ที่เดียว
 *
 * import { Button3D, Card3D, Chip, Pill, SectionHeader, StatTile, EmptyState,
 *          BannerCard, BannerSlider, ConsentSheet, WebsiteButton, openWebsite,
 *          PriceText, formatBaht, Screen, ScreenHeader } from '@/components/ui';
 */

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

/**
 * LegalDocument — หน้าข้อกำหนด/นโยบาย/ข้อตกลง ธีมรอยัล น้ำเงินกรมท่า-ทอง (ใช้ร่วมกัน 3 หน้า)
 *
 * เนื้อหาอยู่ในไฟล์หน้าจอแต่ละหน้า (sections) — คอมโพเนนต์นี้จัดหน้าตาอย่างเดียว
 * - การ์ดหัวเอกสาร: เหรียญน้ำเงินไอคอนทอง + ชื่อเอกสารฟอนต์มีเชิง + วันที่ + ป้าย (เช่น PDPA)
 * - แต่ละหัวข้อเป็นการ์ดขาว: ช่องไอคอน (ถ้ามี) หรือขีดทองนำหน้าชื่อหัวข้อ
 * - มีลิงก์ไปฉบับเต็มบนเว็บไซต์ (ฉบับทางการที่อัปเดตล่าสุด)
 *
 * icon / sections[].icon / badge.icon = ชื่อไอคอนเส้น (ดู components/ui/iconPaths.ts)
 */

import React from 'react';
import { StyleSheet, View } from 'react-native';
import { Text } from '@/components/ui/Text';
import { LinearGradient } from 'expo-linear-gradient';
import { Button3D, Card3D, Icon, Pill, Screen, type IconName } from '@/components/ui';
import { IconTile } from '@/components/profile';
import { openUrl } from '@/utils/navigation';
import { useTheme, spacing, typography } from '@/theme';

export interface LegalSection {
  title: string;
  content: string;
  icon?: IconName;
}

export interface LegalDocumentProps {
  title: string;
  icon: IconName;
  heading: string;
  /** เช่น "อัปเดตล่าสุด: 1 มกราคม 2568" */
  dateLabel: string;
  sections: LegalSection[];
  /** ป้ายใต้หัวเรื่อง เช่น PDPA */
  badge?: { icon: IconName; text: string };
  footer?: string;
  /** ฉบับเต็มบนเว็บ */
  webUrl?: string;
}

export const LegalDocument: React.FC<LegalDocumentProps> = ({
  title,
  icon,
  heading,
  dateLabel,
  sections,
  badge,
  footer,
  webUrl,
}) => {
  const { colors, gradients } = useTheme();
  return (
    <Screen title={title}>
      {/* ---------- หัวเอกสาร ---------- */}
      <Card3D gradientBorder padding={spacing.xl} style={styles.block} contentStyle={styles.intro}>
        <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.medal}>
          <Icon name={icon} size={30} color={colors.goldLight} />
        </LinearGradient>
        <Text accessibilityRole="header" style={[typography.serif, styles.center, { color: colors.textStrong }]}>
          {heading}
        </Text>
        <View style={styles.dateRow}>
          <Icon name="calendar-blank" size={14} color={colors.textMuted} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>{dateLabel}</Text>
        </View>
        {!!badge && <Pill label={badge.text} icon={badge.icon} tone="success" size="md" style={styles.badge} />}
      </Card3D>

      {/* ---------- หัวข้อ ---------- */}
      {sections.map((section, index) => (
        <Card3D key={`${index}-${section.title}`} padding={spacing.lg} shadow="sm" style={styles.section}>
          <View style={styles.sectionHead}>
            {section.icon ? (
              <IconTile icon={section.icon} size={40} />
            ) : (
              <View style={[styles.accent, { backgroundColor: colors.gold }]} />
            )}
            <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>{section.title}</Text>
          </View>
          <Text style={[typography.body, styles.content, { color: colors.text }]} selectable>
            {section.content}
          </Text>
        </Card3D>
      ))}

      {!!footer && (
        <Card3D variant="inset" padding={spacing.lg} style={styles.block}>
          <Text style={[typography.bodySm, styles.center, { color: colors.textMuted }]}>{footer}</Text>
        </Card3D>
      )}

      {/* หน้าเปิดเว็บวาดไอคอนเป็นตัวอักษร — ส่งแค่ชื่อหน้า (แอปเลิกใช้อีโมจิเป็นไอคอน) */}
      {!!webUrl && (
        <Button3D
          title="อ่านฉบับเต็มบนเว็บไซต์"
          icon="globe"
          iconRight="arrow-square-out"
          variant="secondary"
          fullWidth
          onPress={() => openUrl(webUrl, title)}
        />
      )}
    </Screen>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  center: {
    textAlign: 'center',
  },
  block: {
    marginBottom: spacing.lg,
  },
  intro: {
    alignItems: 'center',
    gap: spacing.xs,
  },
  medal: {
    width: 68,
    height: 68,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.sm,
  },
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs,
  },
  badge: {
    marginTop: spacing.sm,
    alignSelf: 'center',
  },
  section: {
    marginBottom: spacing.md,
  },
  sectionHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  accent: {
    width: 4,
    height: 22,
    borderRadius: 2,
  },
  content: {
    marginTop: spacing.md,
    lineHeight: 24,
  },
});

export default LegalDocument;

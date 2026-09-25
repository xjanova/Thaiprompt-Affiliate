/**
 * LegalDocument — หน้าข้อกำหนด/นโยบาย/ข้อตกลง แบบธีมนวลทองคำ (ใช้ร่วมกัน 3 หน้า)
 *
 * เนื้อหาอยู่ในไฟล์หน้าจอแต่ละหน้า (sections) — คอมโพเนนต์นี้จัดหน้าตาอย่างเดียว
 * มีลิงก์ไปฉบับเต็มบนเว็บไซต์ (ฉบับทางการที่อัปเดตล่าสุด)
 */

import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Button3D, Card3D, Pill, Screen } from '@/components/ui';
import { openUrl } from '@/utils/navigation';
import { useTheme, spacing, typography } from '@/theme';

export interface LegalSection {
  title: string;
  content: string;
  icon?: string;
}

export interface LegalDocumentProps {
  title: string;
  icon: string;
  heading: string;
  /** เช่น "อัปเดตล่าสุด: 1 มกราคม 2568" */
  dateLabel: string;
  sections: LegalSection[];
  /** ป้ายใต้หัวเรื่อง เช่น PDPA */
  badge?: { icon: string; text: string };
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
  const { colors } = useTheme();
  return (
    <Screen title={title}>
      <Card3D gradientBorder padding={spacing.xl} style={styles.block} contentStyle={styles.intro}>
        <Text style={styles.introIcon}>{icon}</Text>
        <Text accessibilityRole="header" style={[typography.h1, styles.center, { color: colors.textStrong }]}>
          {heading}
        </Text>
        <Text style={[typography.caption, { color: colors.textMuted }]}>{dateLabel}</Text>
        {!!badge && <Pill label={badge.text} icon={badge.icon} tone="success" size="md" style={styles.badge} />}
      </Card3D>

      {sections.map((section, index) => (
        <Card3D key={`${index}-${section.title}`} padding={spacing.lg} shadow="sm" style={styles.section}>
          <View style={styles.sectionHead}>
            {!!section.icon && <Text style={styles.sectionIcon}>{section.icon}</Text>}
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

      {!!webUrl && (
        <Button3D
          title="อ่านฉบับเต็มบนเว็บไซต์"
          icon="🌐"
          variant="secondary"
          fullWidth
          onPress={() => openUrl(webUrl, title, icon)}
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
  introIcon: {
    fontSize: 40,
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
    gap: spacing.sm,
  },
  sectionIcon: {
    fontSize: 22,
  },
  content: {
    marginTop: spacing.sm,
  },
});

export default LegalDocument;

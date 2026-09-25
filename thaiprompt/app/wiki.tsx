/**
 * Wiki Screen - หน้าคู่มือการใช้งาน
 * แสดงข้อมูลวิธีการใช้งานแอพ
 * - FAQ
 * - วิธีการใช้งานแต่ละฟีเจอร์
 * - เงื่อนไขและข้อกำหนด
 *
 * หน้าตา: ธีมรอยัล — หัวน้ำเงินกรมท่า (Screen) + การ์ดบทความขาว ไอคอนในสี่เหลี่ยมมน
 * หมวดและคำถามกดเพื่อกางอ่าน · ติดต่อเราเป็นการ์ดกดได้สองใบ
 */

import React, { useState } from 'react';
import {
  View,
  Pressable,
  StyleSheet,
  Linking,
} from 'react-native';
import { Text } from '@/components/ui/Text';
import Animated, { FadeIn, FadeInUp } from 'react-native-reanimated';
import { Card3D, Icon, Screen, SectionHeader, type IconName } from '@/components/ui';
import { useTheme, spacing, radii, typography } from '@/theme';
import { openUrl } from '@/utils/navigation';

// Wiki Categories
const WIKI_CATEGORIES: Array<{
  id: string;
  title: string;
  icon: IconName;
  items: { title: string; content: string }[];
}> = [
  {
    id: 'getting-started',
    title: 'เริ่มต้นใช้งาน',
    icon: 'sign-in',
    items: [
      { title: 'วิธีสมัครสมาชิก', content: 'สมัครสมาชิกผ่านแอพหรือเว็บไซต์ โดยกรอกข้อมูลส่วนตัว และยืนยันตัวตนผ่าน OTP' },
      { title: 'การเข้าสู่ระบบ', content: 'ใช้อีเมลหรือเบอร์โทรศัพท์ที่ลงทะเบียนไว้ในการเข้าสู่ระบบ' },
      { title: 'LINE Login', content: 'สามารถเข้าสู่ระบบด้วย LINE Account ได้อย่างรวดเร็ว' },
    ],
  },
  {
    id: 'shopping',
    title: 'การช้อปปิ้ง',
    icon: 'shopping-cart-simple',
    items: [
      { title: 'วิธีการสั่งซื้อสินค้า', content: 'เลือกสินค้า > เพิ่มลงตะกร้า > ชำระเงิน > รอรับสินค้า' },
      { title: 'วิธีการชำระเงิน', content: 'ชำระด้วย PromptPay กระเป๋าเงินในแอป หรือเก็บเงินปลายทาง (เฉพาะร้านที่ส่งด้วยไรเดอร์)' },
      { title: 'การติดตามพัสดุ', content: 'ตรวจสอบสถานะการจัดส่งได้ในหน้าคำสั่งซื้อ' },
    ],
  },
  {
    id: 'rider',
    title: 'ไรเดอร์และร้านค้า',
    icon: 'moped',
    items: [
      { title: 'สมัครเป็นไรเดอร์', content: 'กรอกข้อมูลและอัปโหลดเอกสารในเมนูไรเดอร์ ทีมงานจะตรวจสอบก่อนเปิดให้รับงาน' },
      { title: 'เปิดร้านค้า', content: 'เปิดร้านและจัดการสินค้าได้บนเว็บไซต์ ส่วนออเดอร์ใหม่ดูได้ในเมนู "ร้านของฉัน"' },
      { title: 'ชวนเพื่อน', content: 'แชร์ลิงก์หรือ QR Code ของคุณ รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก ดูรายละเอียดบนเว็บไซต์' },
    ],
  },
  {
    id: 'wallet',
    title: 'กระเป๋าเงิน',
    icon: 'wallet',
    items: [
      { title: 'การเติมเงิน', content: 'เติมเงินผ่าน PromptPay เพื่อใช้ชำระค่าสินค้าและค่าจัดส่งในแอป' },
      { title: 'การถอนเงิน', content: 'ถอนเงินเข้าบัญชีธนาคารหรือ PromptPay ที่ผูกไว้ ขั้นต่ำและค่าธรรมเนียมแสดงในหน้าถอนเงิน' },
      { title: 'ประวัติธุรกรรม', content: 'ตรวจสอบประวัติการทำรายการทั้งหมดได้ในหน้ากระเป๋าเงิน' },
    ],
  },
];

// FAQ Items
const FAQ_ITEMS = [
  {
    question: 'ลืมรหัสผ่านต้องทำอย่างไร?',
    answer: 'กดปุ่ม "ลืมรหัสผ่าน" ที่หน้าเข้าสู่ระบบ ระบบจะส่ง OTP ไปยังเบอร์โทรหรืออีเมลที่ลงทะเบียนไว้',
  },
  {
    question: 'ยกเลิกคำสั่งซื้อได้ไหม?',
    answer: 'ยกเลิกได้ก่อนร้านจัดส่ง ถ้าชำระเงินแล้วระบบจะคืนเงินเข้ากระเป๋าเงินให้อัตโนมัติ',
  },
  {
    question: 'ถอนเงินขั้นต่ำเท่าไหร่?',
    answer: 'ดูขั้นต่ำ ค่าธรรมเนียม และยอดที่ได้รับจริงได้ในหน้าถอนเงินก่อนยืนยันทุกครั้ง',
  },
  {
    question: 'ลบบัญชีได้อย่างไร?',
    answer: 'ไปที่ ตั้งค่า > ลบบัญชี แล้วทำตามขั้นตอน ถ้ายังมียอดเงินหรือคำสั่งซื้อค้างอยู่ ระบบจะแจ้งให้จัดการก่อน',
  },
];

/** สีของสี่เหลี่ยมไอคอน — สว่าง = พื้นน้ำเงินอ่อนไอคอนน้ำเงิน · มืด = พื้นทองจางไอคอนทอง */
const useTileColors = () => {
  const { colors, isDark } = useTheme();
  return {
    bg: isDark ? colors.goldSoft : colors.navySoft,
    ink: isDark ? colors.gold : colors.navy,
  };
};

// Expandable Section Component — การ์ดหมวด กดเพื่อกางหัวข้อ
const ExpandableSection = ({
  title,
  icon,
  items,
  index,
}: {
  title: string;
  icon: IconName;
  items: { title: string; content: string }[];
  index: number;
}) => {
  const { colors } = useTheme();
  const tile = useTileColors();
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <Animated.View entering={FadeInUp.delay(index * 100).springify()} style={styles.cardGap}>
      <Card3D padding={0} shadow="sm">
        <Pressable
          onPress={() => setIsExpanded(!isExpanded)}
          accessibilityRole="button"
          accessibilityState={{ expanded: isExpanded }}
          style={({ pressed }) => [styles.sectionHeader, pressed && styles.pressed]}
        >
          <View style={[styles.iconTile, { backgroundColor: tile.bg }]}>
            <Icon name={icon} size={22} color={tile.ink} />
          </View>
          <View style={styles.flex}>
            <Text style={[typography.h3, { color: colors.textStrong }]}>{title}</Text>
            <Text style={[typography.caption, { color: colors.textMuted }]}>{items.length} หัวข้อ</Text>
          </View>
          <View style={[styles.chevron, { backgroundColor: isExpanded ? colors.goldSoft : colors.inset }]}>
            <Icon name={isExpanded ? 'caret-up' : 'caret-down'} size={15} color={colors.goldDeep} weight="bold" />
          </View>
        </Pressable>

        {isExpanded && (
          <Animated.View entering={FadeIn.duration(180)} style={[styles.sectionContent, { borderTopColor: colors.divider }]}>
            {items.map((item, idx) => (
              <View
                key={idx}
                style={[
                  styles.contentItem,
                  idx > 0 && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.divider },
                ]}
              >
                <View style={[styles.bullet, { backgroundColor: colors.gold }]} />
                <View style={styles.flex}>
                  <Text style={[typography.bodyStrong, { color: colors.textStrong }]}>
                    {item.title}
                  </Text>
                  <Text style={[typography.bodySm, styles.contentText, { color: colors.textMuted }]}>
                    {item.content}
                  </Text>
                </View>
              </View>
            ))}
          </Animated.View>
        )}
      </Card3D>
    </Animated.View>
  );
};

// FAQ Item Component — คำถามกดเพื่อดูคำตอบ
const FAQItem = ({
  question,
  answer,
  index,
}: {
  question: string;
  answer: string;
  index: number;
}) => {
  const { colors, isDark } = useTheme();
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <Animated.View entering={FadeInUp.delay(index * 50).springify()} style={styles.faqGap}>
      <Card3D padding={0} shadow="sm">
        <Pressable
          onPress={() => setIsExpanded(!isExpanded)}
          accessibilityRole="button"
          accessibilityState={{ expanded: isExpanded }}
          style={({ pressed }) => [styles.faqHeader, pressed && styles.pressed]}
        >
          <Text style={[typography.bodyStrong, styles.faqQuestion, { color: colors.textStrong }]}>
            {question}
          </Text>
          <View
            style={[
              styles.faqToggle,
              { backgroundColor: isExpanded ? (isDark ? colors.gold : colors.navy) : colors.goldSoft },
            ]}
          >
            <Icon
              name={isExpanded ? 'minus' : 'plus'}
              size={14}
              color={isExpanded ? (isDark ? colors.textOnGold : colors.goldLight) : colors.goldDeep}
              weight="bold"
            />
          </View>
        </Pressable>
        {isExpanded && (
          <Animated.View entering={FadeIn.duration(180)} style={[styles.faqAnswerBox, { borderTopColor: colors.divider }]}>
            <Text style={[typography.body, styles.faqAnswer, { color: colors.textMuted }]}>
              {answer}
            </Text>
          </Animated.View>
        )}
      </Card3D>
    </Animated.View>
  );
};

/** การ์ดช่องทางติดต่อ (กดได้) */
const ContactCard = ({
  icon,
  label,
  caption,
  onPress,
}: {
  icon: IconName;
  label: string;
  caption: string;
  onPress: () => void;
}) => {
  const { colors } = useTheme();
  const tile = useTileColors();
  return (
    <Card3D onPress={onPress} padding={spacing.lg} shadow="sm" style={styles.flex}>
      <View style={[styles.iconTile, { backgroundColor: tile.bg }]}>
        <Icon name={icon} size={22} color={tile.ink} />
      </View>
      <View style={styles.contactLabelRow}>
        <Text style={[typography.h3, styles.flex, { color: colors.textStrong }]}>{label}</Text>
        <Icon name="arrow-up-right" size={15} color={colors.goldDeep} weight="bold" />
      </View>
      <Text numberOfLines={1} style={[typography.caption, { color: colors.textMuted }]}>
        {caption}
      </Text>
    </Card3D>
  );
};

export default function WikiScreen() {
  const handleContact = () => {
    // เปิดอีเมลใน external app (mailto: special URL)
    Linking.openURL('mailto:support@thaiprompt.online').catch(() => {});
  };

  const handleWebsite = () => {
    // เปิดเว็บไซต์ใน WebView ภายในแอพ
    openUrl('https://main.thaiprompt.online', 'Thaiprompt', 'globe');
  };

  return (
    <Screen title="คู่มือการใช้งาน" subtitle="เรียนรู้วิธีใช้งานแอพอย่างง่ายดาย">
      {/* Categories */}
      <View style={styles.section}>
        <SectionHeader title="หมวดหมู่" icon="book-open" />
        {WIKI_CATEGORIES.map((category, index) => (
          <ExpandableSection
            key={category.id}
            title={category.title}
            icon={category.icon}
            items={category.items}
            index={index}
          />
        ))}
      </View>

      {/* FAQ */}
      <View style={styles.section}>
        <SectionHeader title="คำถามที่พบบ่อย (FAQ)" icon="question" />
        {FAQ_ITEMS.map((faq, index) => (
          <FAQItem
            key={index}
            question={faq.question}
            answer={faq.answer}
            index={index}
          />
        ))}
      </View>

      {/* Contact */}
      <View style={styles.section}>
        <SectionHeader title="ติดต่อเรา" icon="headset" />
        <View style={styles.contactButtons}>
          <ContactCard icon="envelope" label="อีเมล" caption="support@thaiprompt.online" onPress={handleContact} />
          <ContactCard icon="globe" label="เว็บไซต์" caption="main.thaiprompt.online" onPress={handleWebsite} />
        </View>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  pressed: {
    opacity: 0.7,
  },
  section: {
    marginBottom: spacing.xxl,
  },
  cardGap: {
    marginBottom: spacing.md,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    padding: 14,
  },
  iconTile: {
    width: 44,
    height: 44,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  chevron: {
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionContent: {
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.xs,
  },
  contentItem: {
    flexDirection: 'row',
    gap: spacing.md,
    paddingVertical: 14,
  },
  bullet: {
    width: 7,
    height: 7,
    borderRadius: 4,
    marginTop: 8,
  },
  contentText: {
    marginTop: 2,
  },
  faqGap: {
    marginBottom: spacing.sm,
  },
  faqHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  faqQuestion: {
    flex: 1,
  },
  faqToggle: {
    width: 28,
    height: 28,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  faqAnswerBox: {
    borderTopWidth: StyleSheet.hairlineWidth,
    marginHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.lg,
  },
  faqAnswer: {
    lineHeight: 23,
  },
  contactButtons: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  contactLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: spacing.md,
  },
});

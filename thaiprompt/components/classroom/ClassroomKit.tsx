/**
 * ชิ้นส่วน UI ของห้องเรียน — ธีมรอยัล น้ำเงินกรมท่า-ทอง
 *
 * - ProgressBar      : แถบความคืบหน้าทอง (มีเส้นเป้าได้ เช่น 90% = ดูจบ)
 * - RoleArt          : ภาพ 3D ประจำบทบาท (ร้าน = store · ไรเดอร์ = scooter · ผู้ซื้อ = bag)
 * - CourseCard       : การ์ดหลักสูตรในหน้าห้องเรียน
 * - TrainingInviteCard : การ์ดชวนอบรมบนแดชบอร์ดไรเดอร์/ร้าน + แบนเนอร์ผู้ซื้อในหน้าโปรไฟล์
 *   (ผ่านแล้ว = ไม่แสดง · server ยังไม่มีห้องเรียน/ไม่มีหลักสูตรของบทบาทนั้น = ไม่แสดง)
 */

import React, { useCallback, useState } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { BrandArt, Card3D, Icon, Pill, type BrandArtName } from '@/components/ui';
import { TrainingSeal } from '@/components/badges/TrustSeals';
import { TRAINING_ROLE_NAME, parseTrainingBadges, type TrainingRole } from '@/components/badges/trainingBadge';
import { useAuthStore } from '@/stores/authStore';
import { useTheme, radii, spacing, typography } from '@/theme';
import type { ClassroomCourse } from '@/services/api/classroomApi';
import { formatThaiDateTime } from '@/components/shop/helpers';
import { loadClassroomCourses, peekClassroomCourses } from './classroomCache';
import {
  COURSE_STATUS_LOOK,
  courseProgressPct,
  courseStatus,
  formatThaiDuration,
  pointsLabel,
} from './classroomLogic';

// =====================================================
// แถบความคืบหน้า
// =====================================================

export interface ProgressBarProps {
  /** 0–100 */
  pct: number;
  height?: number;
  /** เส้นเป้า (0–100) เช่น 90 = ต้องดูถึงตรงนี้ถึงนับว่าจบ */
  targetPct?: number;
  /** วางบนพื้นน้ำเงิน (หัว/การ์ดน้ำเงิน) */
  onDark?: boolean;
  /** เต็ม 100 แล้วเปลี่ยนเป็นเขียว */
  successWhenFull?: boolean;
  style?: StyleProp<ViewStyle>;
  accessibilityLabel?: string;
}

export const ProgressBar: React.FC<ProgressBarProps> = ({
  pct,
  height = 8,
  targetPct,
  onDark = false,
  successWhenFull = false,
  style,
  accessibilityLabel,
}) => {
  const { colors, gradients } = useTheme();
  const value = Math.min(100, Math.max(0, Number.isFinite(pct) ? pct : 0));
  const full = successWhenFull && value >= 100;
  return (
    <View
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={accessibilityLabel}
      accessibilityValue={{ min: 0, max: 100, now: Math.round(value) }}
      style={[
        styles.track,
        { height, borderRadius: height / 2, backgroundColor: onDark ? colors.headerGlass : colors.inset },
        style,
      ]}
    >
      {value > 0 && (
        <LinearGradient
          colors={full ? gradients.success : gradients.primary}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={[styles.fill, { width: `${value}%`, borderRadius: height / 2 }]}
        />
      )}
      {targetPct !== undefined && targetPct > 0 && targetPct < 100 && (
        <View style={[styles.target, { left: `${targetPct}%`, backgroundColor: onDark ? colors.onHeaderMuted : colors.textFaint }]} />
      )}
    </View>
  );
};

// =====================================================
// ภาพประจำบทบาท
// =====================================================

const ROLE_ART: Record<TrainingRole, BrandArtName> = {
  seller: 'store',
  rider: 'scooter',
  buyer: 'bag',
};

export const RoleArt: React.FC<{ role: TrainingRole | null; size?: number }> = ({ role, size = 52 }) => {
  const { colors } = useTheme();
  if (!role) return <Icon name="graduation-cap" size={size * 0.6} color={colors.goldDeep} />;
  return <BrandArt name={ROLE_ART[role]} size={size} />;
};

// =====================================================
// การ์ดหลักสูตร (หน้าห้องเรียน)
// =====================================================

export interface CourseCardProps {
  course: ClassroomCourse;
  /** บทบาทของผู้ใช้เอง → ป้าย "บทบาทของคุณ" + ขอบทอง */
  mine?: boolean;
  onPress: () => void;
}

export const CourseCard: React.FC<CourseCardProps> = ({ course, mine = false, onPress }) => {
  const { colors } = useTheme();
  const status = courseStatus(course);
  const look = COURSE_STATUS_LOOK[status];
  const pct = courseProgressPct(course);
  const roleName = course.role ? TRAINING_ROLE_NAME[course.role] : null;
  const points = pointsLabel(course.points_reward);
  const certifiedDate = formatThaiDateTime(course.certified_at, false);

  return (
    <Card3D
      onPress={onPress}
      gradientBorder={mine}
      padding={spacing.lg}
      style={styles.courseCard}
      accessibilityLabel={`${course.title} ${look.label}${roleName ? ` หลักสูตร${roleName}` : ''}`}
      accessibilityHint="เปิดรายละเอียดหลักสูตร"
    >
      <View style={styles.courseTop}>
        <View style={[styles.artTile, { backgroundColor: mine ? colors.goldSoft : colors.navySoft }]}>
          <RoleArt role={course.role} size={50} />
        </View>
        <View style={styles.flex}>
          <View style={styles.pillRow}>
            {mine && <Pill label="บทบาทของคุณ" tone="gold" icon="crown-simple" />}
            <Pill label={look.label} tone={look.tone} />
          </View>
          <Text numberOfLines={2} style={[typography.h3, styles.courseTitle, { color: colors.textStrong }]}>
            {course.title}
          </Text>
        </View>
      </View>

      {!!course.description && (
        <Text numberOfLines={2} style={[typography.bodySm, styles.gapTop, { color: colors.textMuted }]}>
          {course.description}
        </Text>
      )}

      <View style={[styles.metaRow, styles.gapTop]}>
        <View style={styles.metaItem}>
          <Icon name="play-circle" size={15} color={colors.textMuted} />
          <Text style={[typography.caption, { color: colors.textMuted }]}>
            {course.lesson_count} บท{course.total_seconds > 0 ? ` · ${formatThaiDuration(course.total_seconds)}` : ''}
          </Text>
        </View>
        {course.question_count > 0 && (
          <View style={styles.metaItem}>
            <Icon name="exam" size={15} color={colors.textMuted} />
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              แบบทดสอบ {course.question_count} ข้อ{course.pass_score > 0 ? ` (ผ่าน ${course.pass_score})` : ''}
            </Text>
          </View>
        )}
      </View>

      {status === 'certified' ? (
        <View style={[styles.certRow, { backgroundColor: colors.successSoft }]}>
          <TrainingSeal size={26} label={`ผ่านการอบรม${roleName ?? ''}`} />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            ได้รับตรา "ผ่านการอบรม" แล้ว{certifiedDate ? ` เมื่อ ${certifiedDate}` : ''}
          </Text>
        </View>
      ) : status === 'needs_refresh' ? (
        <View style={[styles.certRow, { backgroundColor: colors.warningSoft }]}>
          <TrainingSeal size={26} needsRefresh compact label={`ผ่านการอบรม${roleName ?? ''}`} />
          <Text style={[typography.bodySm, styles.flex, { color: colors.text }]}>
            กฎมีการปรับปรุง ดูบทเรียนใหม่และทำแบบทดสอบอีกครั้งเพื่อต่ออายุตรา
          </Text>
        </View>
      ) : (
        <View style={styles.gapTop}>
          <View style={styles.progressHead}>
            <Text style={[typography.caption, { color: colors.textMuted }]}>
              ดูแล้ว {Math.min(course.completed_lessons, course.lesson_count)}/{course.lesson_count} บท
            </Text>
            {!!points && (
              <View style={styles.metaItem}>
                <Icon name="star" size={13} color={colors.goldDeep} weight="fill" />
                <Text style={[typography.caption, { color: colors.goldDeep }]}>{points}</Text>
              </View>
            )}
          </View>
          <ProgressBar pct={pct} style={styles.progressGap} accessibilityLabel={`ความคืบหน้า ${pct} เปอร์เซ็นต์`} />
        </View>
      )}
    </Card3D>
  );
};

// =====================================================
// การ์ดชวนอบรม (แดชบอร์ดไรเดอร์/ร้าน · แบนเนอร์ผู้ซื้อในโปรไฟล์)
// =====================================================

export interface TrainingInviteCardProps {
  role: TrainingRole;
  /** card = การ์ดเต็มบนแดชบอร์ด · banner = แถบเล็ก (โปรไฟล์ผู้ซื้อ) */
  variant?: 'card' | 'banner';
  style?: StyleProp<ViewStyle>;
}

export const TrainingInviteCard: React.FC<TrainingInviteCardProps> = ({ role, variant = 'card', style }) => {
  const { colors, gradients } = useTheme();
  const userId = useAuthStore((s) => s.user?.id ?? 0);
  const rawBadges = useAuthStore((s) => s.user?.training_badges);
  const profileBadge = parseTrainingBadges(rawBadges)?.[role] ?? null;
  // โปรไฟล์บอกว่าผ่านแล้ว (ไม่ต้องต่ออายุ) → ไม่ต้องถาม server เลย
  const certifiedOnProfile = !!profileBadge?.certified && !profileBadge.needs_refresh;

  const [course, setCourse] = useState<ClassroomCourse | null>(
    () => (userId ? peekClassroomCourses(userId)?.find((c) => c.role === role) ?? null : null)
  );

  useFocusEffect(
    useCallback(() => {
      if (!userId || certifiedOnProfile) return undefined;
      let alive = true;
      loadClassroomCourses(userId)
        .then((result) => {
          if (!alive) return;
          // server ไม่มีห้องเรียน (404) / เน็ตหลุด = ไม่แสดงการ์ด (ไม่ใช่เรื่องที่ต้องเตือนผู้ใช้)
          setCourse(result.success ? result.data.find((c) => c.role === role) ?? null : null);
        })
        .catch(() => {});
      return () => {
        alive = false;
      };
    }, [userId, role, certifiedOnProfile])
  );

  if (!userId || certifiedOnProfile || !course) return null;
  if (course.certified && !course.needs_refresh) return null;

  const roleName = TRAINING_ROLE_NAME[role];
  const status = courseStatus(course);
  const pct = courseProgressPct(course);
  const points = pointsLabel(course.points_reward);
  const open = () => router.push(`/classroom/${course.id}` as never);

  const title =
    status === 'needs_refresh'
      ? `กฎ${roleName}อัปเดตแล้ว อบรมเพิ่มอีกนิด`
      : status === 'quiz_ready'
        ? 'ดูครบแล้ว ทำแบบทดสอบรับตราได้เลย'
        : status === 'in_progress'
          ? `อบรม${roleName}ต่ออีกนิด (${pct}%)`
          : `อบรม${roleName} รับตรา "ผ่านการอบรม"`;
  const caption = `ดูวิดีโอ ${course.lesson_count} บท${
    course.total_seconds > 0 ? ` (${formatThaiDuration(course.total_seconds)})` : ''
  } แล้วทำแบบทดสอบ${course.question_count > 0 ? ` ${course.question_count} ข้อ` : ''}`;

  if (variant === 'banner') {
    return (
      <Card3D
        onPress={open}
        padding={spacing.md}
        radius={radii.lg}
        shadow="sm"
        style={style}
        accessibilityLabel={`${title} ${points}`}
        accessibilityHint="เปิดห้องเรียน"
      >
        <View style={styles.bannerRow}>
          <View style={[styles.sealTileSm, { backgroundColor: colors.navyFill }]}>
            <TrainingSeal size={30} needsRefresh={status === 'needs_refresh'} compact />
          </View>
          <View style={styles.flex}>
            <Text numberOfLines={2} style={[typography.bodyStrong, { color: colors.textStrong }]}>
              {title}
            </Text>
            <Text numberOfLines={1} style={[typography.caption, { color: colors.goldDeep }]}>
              {points || 'อบรมสั้นๆ ไม่บังคับ'}
            </Text>
          </View>
          <Icon name="caret-right" size={16} color={colors.textFaint} weight="bold" />
        </View>
      </Card3D>
    );
  }

  return (
    <Card3D
      onPress={open}
      padding={0}
      radius={radii.xl}
      shadow="md"
      style={style}
      accessibilityLabel={`${title} ${caption} ${points}`}
      accessibilityHint="เปิดห้องเรียน"
    >
      <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.inviteInner}>
        <View style={styles.inviteRow}>
          <View style={[styles.sealTile, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
            <TrainingSeal size={44} needsRefresh={status === 'needs_refresh'} compact />
          </View>
          <View style={styles.flex}>
            <Text style={[typography.overline, { color: colors.goldLight }]}>ห้องเรียน · ไม่บังคับ</Text>
            <Text numberOfLines={2} style={[typography.h3, { color: colors.onHeader }]}>
              {title}
            </Text>
            <Text numberOfLines={2} style={[typography.caption, { color: colors.onHeaderMuted }]}>
              {caption}
            </Text>
          </View>
          <Icon name="caret-right" size={18} color={colors.goldLight} weight="bold" />
        </View>
        {(status === 'in_progress' || status === 'quiz_ready') && (
          <ProgressBar pct={pct} onDark height={6} style={styles.inviteProgress} accessibilityLabel={`ความคืบหน้า ${pct} เปอร์เซ็นต์`} />
        )}
        {!!points && (
          <View style={[styles.pointsTag, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
            <Icon name="star" size={13} color={colors.goldLight} weight="fill" />
            <Text style={[typography.caption, { color: colors.goldLight }]}>{points}</Text>
          </View>
        )}
      </LinearGradient>
    </Card3D>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  track: {
    width: '100%',
    overflow: 'hidden',
  },
  fill: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
  },
  target: {
    position: 'absolute',
    top: 0,
    bottom: 0,
    width: 2,
    marginLeft: -1,
  },
  courseCard: {
    marginBottom: spacing.md,
  },
  courseTop: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  artTile: {
    width: 64,
    height: 64,
    borderRadius: radii.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pillRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  courseTitle: {
    marginTop: spacing.xs,
  },
  gapTop: {
    marginTop: spacing.md,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    columnGap: spacing.lg,
    rowGap: spacing.xs,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  certRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.sm + 2,
    borderRadius: radii.md,
  },
  progressHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  progressGap: {
    marginTop: spacing.xs + 2,
  },
  bannerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  sealTileSm: {
    width: 44,
    height: 44,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  inviteInner: {
    padding: spacing.lg,
    borderRadius: radii.xl,
  },
  inviteRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  sealTile: {
    width: 64,
    height: 64,
    borderRadius: radii.lg,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  inviteProgress: {
    marginTop: spacing.md,
  },
  pointsTag: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 4,
    marginTop: spacing.md,
    paddingHorizontal: spacing.sm + 2,
    paddingVertical: 3,
    borderRadius: radii.pill,
    borderWidth: 1,
  },
});

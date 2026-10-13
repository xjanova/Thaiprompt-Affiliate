/**
 * ห้องเรียน (หน้าแรก) — หลักสูตรอบรมตามบทบาท: ร้านค้า · ไรเดอร์ · ผู้ซื้อ
 *
 * - การอบรมไม่บังคับ (เปิดร้าน/เป็นไรเดอร์/สั่งของได้ทันที) — ผ่านแล้วได้ตรา "ผ่านการอบรม" ข้างตรายืนยันตัวตน
 *   + คะแนนความประพฤติ (points_reward จาก server)
 * - แสดงครบทั้ง 3 หลักสูตร · บทบาทของผู้ใช้ (ร้าน/ไรเดอร์ จากโปรไฟล์ + ตรวจเงียบๆ) ขึ้นก่อนพร้อมขอบทอง
 * - server ยังไม่มีห้องเรียน (404) → "ห้องเรียนยังไม่เปิดให้บริการ" · เน็ตหลุด → ลองใหม่
 * - โหลดครั้งแรกใช้โครงร่าง (ไม่หมุนเต็มจอ) · ดึงลงเพื่อรีเฟรช = โหลดใหม่จาก server
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { router, useFocusEffect } from 'expo-router';
import { Text } from '@/components/ui/Text';
import { EmptyState, Screen, SectionHeader } from '@/components/ui';
import { SkeletonCard } from '@/components/merchant/SkeletonBlock';
import { TrainingSeal, VerifiedSeal } from '@/components/badges/TrustSeals';
import { TRAINING_ROLES, parseTrainingRole, type TrainingRole } from '@/components/badges/trainingBadge';
import { CourseCard } from '@/components/classroom/ClassroomKit';
import { loadClassroomCourses, peekClassroomCourses } from '@/components/classroom/classroomCache';
import { orderCourses } from '@/components/classroom/classroomLogic';
import { useAuthStore } from '@/stores/authStore';
import { useEkycStore } from '@/stores/ekycStore';
import { checkIsSeller } from '@/services/api/merchantApi';
import { getRiderStatus } from '@/services/api/riderApi';
import type { ClassroomCourse } from '@/services/api/classroomApi';
import { useTheme, radii, spacing, typography } from '@/theme';

type LoadState =
  | { kind: 'loading' }
  | { kind: 'ready'; courses: ClassroomCourse[] }
  | { kind: 'unavailable' }
  | { kind: 'error'; message: string };

export default function ClassroomHomeScreen() {
  const { colors, gradients } = useTheme();
  const user = useAuthStore((s) => s.user);
  const userId = user?.id ?? 0;
  const ekycVerified = !!useEkycStore((s) => s.status?.verified);
  const kycVerified = ekycVerified || !!user?.verified;

  const [state, setState] = useState<LoadState>(() => {
    const cached = userId ? peekClassroomCourses(userId) : null;
    return cached ? { kind: 'ready', courses: cached } : { kind: 'loading' };
  });
  const [refreshing, setRefreshing] = useState(false);
  const [extraRoles, setExtraRoles] = useState<TrainingRole[]>([]);
  const mountedRef = useRef(true);

  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const load = useCallback(
    async (force: boolean) => {
      if (!userId) return;
      const result = await loadClassroomCourses(userId, { force });
      if (!mountedRef.current) return;
      if (result.success) {
        setState({ kind: 'ready', courses: result.data });
      } else if (result.status === 404) {
        setState({ kind: 'unavailable' });
      } else {
        // มีข้อมูลเดิมอยู่แล้ว → คงไว้ ไม่ล้างหน้าจอ (รีเฟรชไม่สำเร็จเฉยๆ)
        setState((prev) => (prev.kind === 'ready' ? prev : { kind: 'error', message: result.message }));
      }
    },
    [userId]
  );

  useFocusEffect(
    useCallback(() => {
      load(false).catch(() => {});
    }, [load])
  );

  // บทบาทของผู้ใช้: role ในโปรไฟล์ก่อน แล้วตรวจเงียบๆ ว่ามีร้าน/เป็นไรเดอร์ (ผิดพลาด = ไม่เป็นไร แค่ไม่จัดลำดับ)
  useEffect(() => {
    if (!userId) return;
    let alive = true;
    Promise.all([
      checkIsSeller(userId).catch(() => false),
      getRiderStatus()
        .then((r) => (r.success ? !!r.data.is_rider : false))
        .catch(() => false),
    ]).then(([seller, rider]) => {
      if (!alive) return;
      const roles: TrainingRole[] = [];
      if (seller) roles.push('seller');
      if (rider) roles.push('rider');
      setExtraRoles(roles);
    });
    return () => {
      alive = false;
    };
  }, [userId]);

  const myRoles = useMemo<TrainingRole[]>(() => {
    const set = new Set<TrainingRole>(extraRoles);
    const fromProfile = parseTrainingRole(user?.role);
    if (fromProfile && fromProfile !== 'buyer') set.add(fromProfile);
    return TRAINING_ROLES.filter((r) => set.has(r));
  }, [extraRoles, user?.role]);

  const onRefresh = async () => {
    setRefreshing(true);
    try {
      await load(true);
    } finally {
      if (mountedRef.current) setRefreshing(false);
    }
  };

  const courses = state.kind === 'ready' ? orderCourses(state.courses, myRoles) : [];
  const certifiedCount = courses.filter((c) => c.certified && !c.needs_refresh).length;

  const renderBody = () => {
    switch (state.kind) {
      case 'loading':
        return (
          <View accessibilityLabel="กำลังโหลดหลักสูตร">
            <SkeletonCard lines={3} withTile style={styles.skeleton} />
            <SkeletonCard lines={3} withTile style={styles.skeleton} />
            <SkeletonCard lines={3} withTile style={styles.skeleton} />
          </View>
        );
      case 'unavailable':
        return (
          <EmptyState
            compact
            icon="graduation-cap"
            title="ห้องเรียนยังไม่เปิดให้บริการ"
            message="ทีมงานกำลังเตรียมหลักสูตร เปิดเมื่อไหร่จะแจ้งให้ทราบนะ"
          />
        );
      case 'error':
        return <EmptyState compact variant="error" message={state.message} onAction={() => load(true)} />;
      case 'ready':
        if (courses.length === 0) {
          return (
            <EmptyState
              compact
              icon="graduation-cap"
              title="ยังไม่มีหลักสูตรเปิดสอน"
              message="เมื่อมีหลักสูตรใหม่ จะแสดงที่นี่"
              onAction={() => load(true)}
            />
          );
        }
        return courses.map((course) => (
          <CourseCard
            key={course.id}
            course={course}
            mine={!!course.role && myRoles.includes(course.role)}
            onPress={() => router.push(`/classroom/${course.id}` as never)}
          />
        ));
      default:
        return null;
    }
  };

  return (
    <Screen title="ห้องเรียน" subtitle="อบรมสั้นๆ รับตราความน่าเชื่อถือ" refreshing={refreshing} onRefresh={onRefresh}>
      {/* การ์ดน้ำเงินอธิบายตรา: ตรายืนยันตัวตน + ตราผ่านการอบรม วางคู่กัน */}
      <LinearGradient colors={gradients.navy} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={styles.hero}>
        <View style={styles.heroSeals}>
          <VerifiedSeal size={38} />
          <TrainingSeal size={58} />
        </View>
        <Text style={[typography.serifSm, styles.heroTitle, { color: colors.onHeader }]}>
          ตรา "ผ่านการอบรม" คู่กับตรายืนยันตัวตน
        </Text>
        <Text style={[typography.bodySm, styles.heroText, { color: colors.onHeaderMuted }]}>
          ดูวิดีโอให้ครบทุกบท แล้วทำแบบทดสอบให้ผ่าน รับตราแสดงข้างชื่อคุณ พร้อมคะแนนความประพฤติ ไม่บังคับ เรียนเมื่อสะดวก
        </Text>
        {state.kind === 'ready' && courses.length > 0 && (
          <View style={[styles.heroStat, { backgroundColor: colors.headerGlass, borderColor: colors.headerGlassBorder }]}>
            <Text style={[typography.caption, { color: colors.goldLight }]}>
              ผ่านแล้ว {certifiedCount} จาก {courses.length} หลักสูตร
            </Text>
          </View>
        )}
        {!kycVerified && (
          <Text style={[typography.micro, styles.heroNote, { color: colors.onHeaderMuted }]}>
            ยืนยันตัวตนได้ที่ โปรไฟล์ › ยืนยันตัวตน
          </Text>
        )}
      </LinearGradient>

      <SectionHeader
        title="หลักสูตร"
        subtitle={myRoles.length > 0 ? 'หลักสูตรของบทบาทคุณอยู่ด้านบน' : 'เลือกหลักสูตรที่อยากเรียน'}
        style={styles.section}
      />
      {renderBody()}
    </Screen>
  );
}

const styles = StyleSheet.create({
  hero: {
    borderRadius: radii.xl,
    padding: spacing.xl,
    alignItems: 'center',
  },
  heroSeals: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: spacing.sm,
  },
  heroTitle: {
    textAlign: 'center',
    marginTop: spacing.md,
  },
  heroText: {
    textAlign: 'center',
    marginTop: spacing.xs,
  },
  heroStat: {
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
    borderRadius: radii.pill,
    borderWidth: 1,
  },
  heroNote: {
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  section: {
    marginTop: spacing.xxl,
    marginBottom: spacing.md,
  },
  skeleton: {
    marginBottom: spacing.md,
  },
});

<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\DB;

/**
 * 🧩 (2026-10-06) เขียน conversation_state ทีละคีย์แบบ atomic — ห้ามเขียนทั้งก้อนจากสำเนาเก่า
 *
 * ทำไมต้องมี: `FortuneReading::setConversationState()` เขียน state "ทั้งก้อน" จากสำเนาในหน่วยความจำ
 *   ถ้าสำเนานั้นโหลดมาก่อนงานช้า (โหลดรูป 20 วิ / เรียก AI) แล้วระหว่างนั้นมีคนอื่นเขียนคีย์อื่น
 *   (ตัดบิล SMS พักกล่อง / park ข้อความ) → ของคนอื่นถูกเขียนทับหายเงียบ ๆ
 *   ตัวนี้ใช้ JSON_SET / JSON_REMOVE / JSON_ARRAY_APPEND บนแถวโดยตรง แตะเฉพาะคีย์ที่ระบุ
 *
 * - ต้นทางเป็น FortuneReading::STATE_OBJECT_SQL เสมอ (state "[]" / NULL / JSON เสีย = เริ่มจาก {})
 * - ค่า JSON ส่งผ่าน JSON_EXTRACT(?, '$') — MariaDB ไม่รู้จัก CAST(? AS JSON)
 * - path คือชื่อคีย์คั่นด้วยจุด (a.b.c) ตัวอักษร a-z 0-9 _ เท่านั้น (กัน path injection)
 * - อัปเดตสำเนาในหน่วยความจำของผู้เรียก "เฉพาะคีย์นั้น" (ไม่เอา state ทั้งก้อนจาก DB มาทับของผู้เรียก)
 * - ไม่แตะ updated_at (cron หลายตัวใช้ updated_at เป็นหน้าต่างเวลา)
 */
final class ConversationStateAtomic
{
    /**
     * ตั้งค่าคีย์ (ทับของเดิม) — คีย์ซ้อน (a.b) ต้องมีพ่อเป็นอ็อบเจกต์อยู่แล้ว ไม่งั้นใช้ ensureObject() ก่อน
     */
    public static function set(FortuneReading|int $reading, string $path, mixed $value): void
    {
        $id = self::idOf($reading);
        $jsonPath = self::jsonPath($path);

        DB::update(
            'UPDATE fortune_readings SET conversation_state = JSON_SET('.FortuneReading::STATE_OBJECT_SQL.", '{$jsonPath}', JSON_EXTRACT(?, '\$')) WHERE id = ?",
            [self::encode($value), $id]
        );

        if ($reading instanceof FortuneReading) {
            self::patchMemory($reading, $path, $value);
        }
    }

    /**
     * ตั้งค่าคีย์ใต้อ็อบเจกต์ $parent (สร้างอ็อบเจกต์พ่อให้ถ้ายังไม่มี/ไม่ใช่อ็อบเจกต์) ในคำสั่งเดียว
     *
     * @param  bool  $insertOnly  true = ไม่ทับของเดิม (JSON_INSERT)
     * @return bool true = แถวถูกแก้ (insertOnly + มีอยู่แล้ว = false)
     */
    public static function setChild(FortuneReading|int $reading, string $parent, string $child, mixed $value, bool $insertOnly = false): bool
    {
        $id = self::idOf($reading);
        $parentPath = self::jsonPath($parent);
        $childPath = self::jsonPath($parent.'.'.$child);
        $s = FortuneReading::STATE_OBJECT_SQL;

        $parentObj = "IF(JSON_TYPE(JSON_EXTRACT({$s}, '{$parentPath}')) = 'OBJECT', JSON_EXTRACT({$s}, '{$parentPath}'), JSON_OBJECT())";
        $withParent = "JSON_SET({$s}, '{$parentPath}', {$parentObj})";
        $fn = $insertOnly ? 'JSON_INSERT' : 'JSON_SET';

        $sql = "UPDATE fortune_readings SET conversation_state = {$fn}({$withParent}, '{$childPath}', JSON_EXTRACT(?, '\$')) WHERE id = ?";
        $params = [self::encode($value), $id];

        if ($insertOnly) {
            // มีอยู่แล้ว = ไม่แตะแถวเลย (affected = 0 บอกผู้เรียกได้ว่าไม่ได้เขียน)
            // COALESCE: state NULL → JSON_VALID(NULL) = NULL → ต้องนับว่า "ยังไม่มี" (ไม่ใช่ข้ามแถว)
            $sql .= " AND NOT COALESCE(JSON_VALID(conversation_state) AND JSON_CONTAINS_PATH(conversation_state, 'one', '{$childPath}'), 0)";
        }

        $affected = DB::update($sql, $params);

        if ($reading instanceof FortuneReading && ($affected > 0 || ! $insertOnly)) {
            self::patchMemory($reading, $parent.'.'.$child, $value);
        }

        return $affected > 0;
    }

    /**
     * ลบคีย์ (หลายคีย์ได้) — ไม่มีคีย์อยู่แล้ว = ไม่เป็นไร
     */
    public static function remove(FortuneReading|int $reading, string ...$paths): void
    {
        if ($paths === []) {
            return;
        }

        $id = self::idOf($reading);
        $jsonPaths = implode(', ', array_map(fn ($p) => "'".self::jsonPath($p)."'", $paths));

        DB::update(
            'UPDATE fortune_readings SET conversation_state = JSON_REMOVE('.FortuneReading::STATE_OBJECT_SQL.", {$jsonPaths}) WHERE id = ?",
            [$id]
        );

        if ($reading instanceof FortuneReading) {
            foreach ($paths as $p) {
                self::patchMemory($reading, $p, null, true);
            }
        }
    }

    /**
     * ลบคีย์ใต้ $parent เฉพาะเมื่อค่าใน $parent.$child.$field ยังเท่ากับ $expected (กันลบของที่ถูกพักใหม่ทับระหว่างส่ง)
     *
     * @return bool true = ลบได้
     */
    public static function removeChildIf(FortuneReading|int $reading, string $parent, string $child, string $field, string $expected): bool
    {
        $id = self::idOf($reading);
        $childPath = self::jsonPath($parent.'.'.$child);
        $fieldPath = self::jsonPath($parent.'.'.$child.'.'.$field);

        $affected = DB::update(
            "UPDATE fortune_readings SET conversation_state = JSON_REMOVE(conversation_state, '{$childPath}')"
            ." WHERE id = ? AND JSON_VALID(conversation_state) AND JSON_UNQUOTE(JSON_EXTRACT(conversation_state, '{$fieldPath}')) = ?",
            [$id, $expected]
        );

        if ($affected > 0 && $reading instanceof FortuneReading) {
            self::patchMemory($reading, $parent.'.'.$child, null, true);
        }

        return $affected > 0;
    }

    /**
     * ลบอ็อบเจกต์ $path ทิ้งถ้าว่าง ({} / []) — กันคีย์เปล่าค้างให้คิวรี "มีของพักไหม" จับผิด
     */
    public static function removeIfEmpty(FortuneReading|int $reading, string $path): void
    {
        $id = self::idOf($reading);
        $jsonPath = self::jsonPath($path);

        $affected = DB::update(
            "UPDATE fortune_readings SET conversation_state = JSON_REMOVE(conversation_state, '{$jsonPath}')"
            ." WHERE id = ? AND JSON_VALID(conversation_state) AND JSON_LENGTH(JSON_EXTRACT(conversation_state, '{$jsonPath}')) = 0",
            [$id]
        );

        if ($affected > 0 && $reading instanceof FortuneReading) {
            self::patchMemory($reading, $path, null, true);
        }
    }

    /**
     * ต่อท้ายรายการ (สร้างรายการให้ถ้ายังไม่มี) — เต็ม $max แล้วไม่เขียน
     *
     * @return bool true = ต่อท้ายได้
     */
    public static function append(FortuneReading|int $reading, string $path, mixed $value, int $max): bool
    {
        $id = self::idOf($reading);
        $jsonPath = self::jsonPath($path);
        $s = FortuneReading::STATE_OBJECT_SQL;

        $listExpr = "IF(JSON_TYPE(JSON_EXTRACT({$s}, '{$jsonPath}')) = 'ARRAY', JSON_EXTRACT({$s}, '{$jsonPath}'), JSON_ARRAY())";
        $withList = "JSON_SET({$s}, '{$jsonPath}', {$listExpr})";

        $affected = DB::update(
            "UPDATE fortune_readings SET conversation_state = JSON_ARRAY_APPEND({$withList}, '{$jsonPath}', JSON_EXTRACT(?, '\$'))"
            ." WHERE id = ? AND IF(COALESCE(JSON_VALID(conversation_state), 0), COALESCE(JSON_LENGTH(JSON_EXTRACT(conversation_state, '{$jsonPath}')), 0), 0) < ?",
            [self::encode($value), $id, $max]
        );

        if ($affected > 0 && $reading instanceof FortuneReading) {
            $current = self::readMemory($reading, $path);
            $list = is_array($current) ? array_values($current) : [];
            $list[] = $value;
            self::patchMemory($reading, $path, $list);
        }

        return $affected > 0;
    }

    /**
     * อ่านค่าคีย์สดจาก DB (ไม่พึ่งสำเนาในหน่วยความจำ)
     */
    public static function fresh(FortuneReading|int $reading, string $path, mixed $default = null): mixed
    {
        $state = FortuneReading::query()->whereKey(self::idOf($reading))->value('conversation_state');
        if (is_string($state)) {
            $state = json_decode($state, true);
        }
        if (! is_array($state)) {
            return $default;
        }

        foreach (explode('.', $path) as $seg) {
            if (! is_array($state) || ! array_key_exists($seg, $state)) {
                return $default;
            }
            $state = $state[$seg];
        }

        return $state;
    }

    // ============================================================
    // ภายใน
    // ============================================================

    private static function idOf(FortuneReading|int $reading): int
    {
        return $reading instanceof FortuneReading ? (int) $reading->getKey() : $reading;
    }

    private static function jsonPath(string $path): string
    {
        $segments = explode('.', $path);
        foreach ($segments as $seg) {
            if (! preg_match('/^[a-z0-9_]+$/', $seg)) {
                throw new \InvalidArgumentException('ConversationStateAtomic: path ไม่ถูกต้อง — '.$path);
            }
        }

        return '$.'.implode('.', $segments);
    }

    private static function encode(mixed $value): string
    {
        // อาร์เรย์ว่างของ PHP = อ็อบเจกต์ไม่ได้ → "[]" (ตรงกับที่ cast 'array' ของโมเดลเขียนเอง)
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    private static function readMemory(FortuneReading $reading, string $path): mixed
    {
        $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];
        foreach (explode('.', $path) as $seg) {
            if (! is_array($state) || ! array_key_exists($seg, $state)) {
                return null;
            }
            $state = $state[$seg];
        }

        return $state;
    }

    /**
     * แก้สำเนาในหน่วยความจำ "เฉพาะคีย์นั้น" แล้ว sync original — ผู้เรียกที่ setConversationState ทีหลังจะไม่ลบของเรา
     */
    private static function patchMemory(FortuneReading $reading, string $path, mixed $value, bool $unset = false): void
    {
        try {
            $state = is_array($reading->conversation_state) ? $reading->conversation_state : [];
            $segments = explode('.', $path);
            $ref = &$state;
            $last = array_pop($segments);
            foreach ($segments as $seg) {
                if (! isset($ref[$seg]) || ! is_array($ref[$seg])) {
                    if ($unset) {
                        return; // ไม่มีพ่อ = ไม่มีอะไรให้ลบ
                    }
                    $ref[$seg] = [];
                }
                $ref = &$ref[$seg];
            }
            if ($unset) {
                unset($ref[$last]);
            } else {
                $ref[$last] = $value;
            }
            unset($ref);

            $reading->conversation_state = $state;
            $reading->syncOriginalAttribute('conversation_state');
        } catch (\Throwable $e) {
            // สำเนาในหน่วยความจำเป็นแค่ความสะดวก — DB ถูกแล้ว
        }
    }
}

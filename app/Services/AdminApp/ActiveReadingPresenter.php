<?php

namespace App\Services\AdminApp;

use App\Models\FortuneReading;
use Carbon\CarbonInterface;

/**
 * 🔮 JSON ของ "บิลจ่ายแล้วที่กำลังใช้บริการ" (fortune/active-readings + ตัวอย่างใน ops/summary)
 */
final class ActiveReadingPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(FortuneReading $r, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $reason = StuckReadingFinder::stuckReason($r, $now);
        $lastActivity = $reason !== null ? StuckReadingFinder::stuckSince($r) : ($r->updated_at ?? $r->paid_at);
        $isCeltic = $r->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;

        return [
            'reading_id' => (int) $r->id,
            'bill_number' => FortuneBillPresenter::billNumber($r),
            'customer_name' => FortuneBillPresenter::customerName($r),
            'platform' => FortuneBillPresenter::platform($r),
            'package' => FortuneBillPresenter::packageKey($r),
            'package_label' => FortuneBillPresenter::packageLabel($r),
            'conversation_status' => $r->conversation_status,
            'stage' => FortuneBillPresenter::stage($r),
            'paid_at' => $r->paid_at?->toIso8601String(),
            'last_activity_at' => $lastActivity?->toIso8601String(),
            'minutes_since_activity' => $lastActivity !== null
                ? max(0, (int) floor(($now->getTimestamp() - $lastActivity->getTimestamp()) / 60))
                : null,
            'stuck' => $reason !== null,
            'stuck_reason' => $reason,
            'celtic' => $isCeltic ? [
                'picked' => $r->getCelticPickedCount(),
                'questions_used' => (int) ($r->celtic_questions_used ?? 0),
            ] : null,
            'is_taken_over' => $r->isAdminTakenOver(),
        ];
    }
}

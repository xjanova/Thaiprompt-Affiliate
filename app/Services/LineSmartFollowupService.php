<?php

namespace App\Services;

use App\Models\MlmProspect;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LINE Smart Follow-up Service
 *
 * Intelligent follow-up system for incomplete signups
 * - Auto-retry for dropouts
 * - Smart timing (best time to send)
 * - Personalized reminders
 * - Progressive nudging
 * - Give-up detection
 */
class LineSmartFollowupService
{
    private const CACHE_PREFIX = 'line:followup:';
    private const CACHE_TTL = 86400; // 24 hours

    // Follow-up schedules (in minutes after dropout)
    private const FOLLOW_UP_SCHEDULE = [
        'first' => 30,      // 30 minutes after dropout
        'second' => 180,    // 3 hours
        'third' => 1440,    // 24 hours
        'fourth' => 4320,   // 3 days
        'final' => 10080,   // 7 days
    ];

    // Best times to send messages (hours in 24h format)
    private const BEST_SEND_HOURS = [9, 12, 18, 20];

    public function __construct(
        private LineService $lineService,
        private LineFlexMessageService $flexMessageService,
        private LineProgressService $progressService,
        private ConversationContextService $contextService
    ) {}

    /**
     * Schedule follow-up for incomplete signup
     */
    public function scheduleFollowUp(MlmProspect $prospect): void
    {
        if ($prospect->status === 'completed') {
            return;
        }

        $followUpCount = $this->getFollowUpCount($prospect);

        // Don't send more than 5 follow-ups
        if ($followUpCount >= 5) {
            $this->markAsGiveUp($prospect);
            return;
        }

        // Calculate next follow-up time
        $nextFollowUpTime = $this->calculateNextFollowUpTime($prospect, $followUpCount);

        // Store follow-up schedule
        $this->storeFollowUpSchedule($prospect, $nextFollowUpTime, $followUpCount + 1);

        Log::info('Follow-up scheduled', [
            'prospect_id' => $prospect->id,
            'follow_up_number' => $followUpCount + 1,
            'scheduled_at' => $nextFollowUpTime,
        ]);
    }

    /**
     * Send follow-up message
     */
    public function sendFollowUp(MlmProspect $prospect): bool
    {
        if ($prospect->status === 'completed') {
            return false;
        }

        $followUpCount = $this->getFollowUpCount($prospect);
        $progress = $this->progressService->getProgress($prospect);

        // Choose message type based on follow-up count
        $message = match($followUpCount) {
            1 => $this->createFirstFollowUpMessage($prospect, $progress),
            2 => $this->createSecondFollowUpMessage($prospect, $progress),
            3 => $this->createThirdFollowUpMessage($prospect, $progress),
            4 => $this->createFourthFollowUpMessage($prospect, $progress),
            5 => $this->createFinalFollowUpMessage($prospect, $progress),
            default => null,
        };

        if (!$message) {
            return false;
        }

        // Send message
        $success = $this->lineService->sendFlexMessage($prospect->line_user_id, $message);

        if ($success) {
            $this->incrementFollowUpCount($prospect);

            // Add to conversation history
            $this->contextService->addMessageToHistory(
                $prospect,
                'assistant',
                "Follow-up message #{$followUpCount} sent"
            );

            Log::info('Follow-up sent', [
                'prospect_id' => $prospect->id,
                'follow_up_number' => $followUpCount,
            ]);
        }

        return $success;
    }

    /**
     * Create first follow-up message (30 min after dropout)
     */
    private function createFirstFollowUpMessage(MlmProspect $prospect, array $progress): array
    {
        $currentStep = $progress['progress']['currentStepInfo'] ?? [];
        $percent = $progress['progress']['percent'] ?? 0;

        return [
            'type' => 'flex',
            'altText' => '👋 กลับมาทำต่อกันนะคะ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '👋',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ยังทำไม่เสร็จใช่ไหมคะ?',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => "คุณทำไปแล้ว {$percent}% เหลืออีกนิดเดียวเท่านั้น",
                            'size' => 'sm',
                            'color' => '#666666',
                            'align' => 'center',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'text',
                            'text' => "ขั้นตอนถัดไป: {$currentStep['name']}",
                            'size' => 'sm',
                            'color' => '#FF6B6B',
                            'margin' => 'lg',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ทำต่อเลย! 🚀',
                                'text' => 'ทำต่อ',
                            ],
                            'color' => '#51CF66',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create second follow-up message (3 hours)
     */
    private function createSecondFollowUpMessage(MlmProspect $prospect, array $progress): array
    {
        $sponsorName = $prospect->sponsorUser?->name ?? 'ผู้สนับสนุน';

        return [
            'type' => 'flex',
            'altText' => '💬 เรายังรอคุณอยู่นะคะ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💬',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เรายังรอคุณอยู่นะคะ',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => "คุณ{$sponsorName} กำลังรอให้คุณเข้าร่วมทีม",
                            'size' => 'sm',
                            'color' => '#666666',
                            'align' => 'center',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '❓ มีปัญหาอะไรหรือเปล่า?',
                                    'weight' => 'bold',
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ถ้ามีข้อสงสัย สามารถพิมพ์ "ช่วยเหลือ" ได้เลยนะคะ',
                                    'size' => 'xs',
                                    'color' => '#666666',
                                    'margin' => 'sm',
                                    'wrap' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ทำต่อเลย',
                                'text' => 'ทำต่อ',
                            ],
                            'color' => '#FF6B6B',
                        ],
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ต้องการความช่วยเหลือ',
                                'text' => 'ช่วยเหลือ',
                            ],
                            'style' => 'link',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create third follow-up message (24 hours) - with incentive
     */
    private function createThirdFollowUpMessage(MlmProspect $prospect, array $progress): array
    {
        return [
            'type' => 'flex',
            'altText' => '🎁 ข้อเสนอพิเศษสำหรับคุณ!',
            'contents' => [
                'type' => 'bubble',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🎁',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                    ],
                    'backgroundColor' => '#FFD43B',
                    'height' => '80px',
                    'paddingAll' => 'xl',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'ข้อเสนอพิเศษ!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#FFD43B',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'สมัครวันนี้ รับโบนัสต้อนรับ!',
                            'size' => 'sm',
                            'color' => '#666666',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '✓ โบนัสสมาชิกใหม่',
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '✓ วีดีโอสอนฟรี',
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '✓ ปรึกษาผู้เชี่ยวชาญฟรี',
                                    'size' => 'sm',
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'message',
                                'label' => 'รับข้อเสนอเลย! 🎁',
                                'text' => 'สนใจโปรโมชั่น',
                            ],
                            'color' => '#FFD43B',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create fourth follow-up message (3 days) - social proof
     */
    private function createFourthFollowUpMessage(MlmProspect $prospect, array $progress): array
    {
        return [
            'type' => 'flex',
            'altText' => '🌟 เพื่อนของคุณกำลังประสบความสำเร็จ!',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '🌟',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'อย่าพลาดโอกาส!',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'มีคนเพิ่งสมัครเมื่อวานนี้ และเริ่มสร้างรายได้แล้ว',
                            'size' => 'sm',
                            'color' => '#666666',
                            'align' => 'center',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '📊 สถิติสัปดาห์นี้',
                                    'weight' => 'bold',
                                    'color' => '#339AF0',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '• สมาชิกใหม่ 127 คน',
                                    'size' => 'sm',
                                    'margin' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '• รายได้เฉลี่ย 15,000 บาท/เดือน',
                                    'size' => 'sm',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '• ความพึงพอใจ 95%',
                                    'size' => 'sm',
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'message',
                                'label' => 'เข้าร่วมตอนนี้!',
                                'text' => 'พร้อมเริ่มต้น',
                            ],
                            'color' => '#339AF0',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create final follow-up message (7 days) - last chance
     */
    private function createFinalFollowUpMessage(MlmProspect $prospect, array $progress): array
    {
        return [
            'type' => 'flex',
            'altText' => '💔 เราเสียใจที่เห็นคุณไป',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '💔',
                            'size' => 'xxl',
                            'align' => 'center',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'นี่คือข้อความสุดท้าย',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'align' => 'center',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'เราเสียใจที่คุณไม่ได้สมัครเสร็จ หากคุณเปลี่ยนใจ สามารถกลับมาได้ทุกเมื่อ',
                            'size' => 'sm',
                            'color' => '#666666',
                            'align' => 'center',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'ประตูยังเปิดอยู่เสมอสำหรับคุณ 🚪',
                            'size' => 'sm',
                            'color' => '#999999',
                            'align' => 'center',
                            'margin' => 'lg',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'action' => [
                                'type' => 'message',
                                'label' => 'เอาอีกที! ผมพร้อม',
                                'text' => 'กลับมาสมัครใหม่',
                            ],
                            'color' => '#51CF66',
                        ],
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ไม่สนใจแล้ว',
                                'text' => 'ไม่สนใจ',
                            ],
                            'style' => 'link',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Calculate next follow-up time (smart timing)
     */
    private function calculateNextFollowUpTime(MlmProspect $prospect, int $followUpCount): \Carbon\Carbon
    {
        $scheduleKey = match($followUpCount) {
            0 => 'first',
            1 => 'second',
            2 => 'third',
            3 => 'fourth',
            4 => 'final',
            default => 'first',
        };

        $minutesDelay = self::FOLLOW_UP_SCHEDULE[$scheduleKey];
        $nextTime = now()->addMinutes($minutesDelay);

        // Adjust to best send time
        return $this->adjustToBestSendTime($nextTime);
    }

    /**
     * Adjust time to best send time (9am, 12pm, 6pm, 8pm)
     */
    private function adjustToBestSendTime(\Carbon\Carbon $time): \Carbon\Carbon
    {
        $currentHour = $time->hour;

        // Find next best hour
        $nextBestHour = null;
        foreach (self::BEST_SEND_HOURS as $hour) {
            if ($hour > $currentHour) {
                $nextBestHour = $hour;
                break;
            }
        }

        // If no best hour found today, use first hour tomorrow
        if ($nextBestHour === null) {
            return $time->addDay()->setHour(self::BEST_SEND_HOURS[0])->setMinute(0);
        }

        return $time->setHour($nextBestHour)->setMinute(0);
    }

    /**
     * Get follow-up count
     */
    private function getFollowUpCount(MlmProspect $prospect): int
    {
        $cacheKey = $this->getCacheKey($prospect->id, 'count');
        return (int) Cache::get($cacheKey, 0);
    }

    /**
     * Increment follow-up count
     */
    private function incrementFollowUpCount(MlmProspect $prospect): void
    {
        $cacheKey = $this->getCacheKey($prospect->id, 'count');
        $count = $this->getFollowUpCount($prospect);
        Cache::put($cacheKey, $count + 1, now()->addSeconds(self::CACHE_TTL));
    }

    /**
     * Store follow-up schedule
     */
    private function storeFollowUpSchedule(MlmProspect $prospect, \Carbon\Carbon $time, int $followUpNumber): void
    {
        $cacheKey = $this->getCacheKey($prospect->id, 'schedule');
        Cache::put($cacheKey, [
            'prospect_id' => $prospect->id,
            'scheduled_at' => $time->toIso8601String(),
            'follow_up_number' => $followUpNumber,
        ], now()->addSeconds(self::CACHE_TTL));
    }

    /**
     * Mark prospect as given up
     */
    private function markAsGiveUp(MlmProspect $prospect): void
    {
        $prospect->update(['status' => 'given_up']);

        Log::info('Prospect marked as given up', [
            'prospect_id' => $prospect->id,
            'follow_up_count' => $this->getFollowUpCount($prospect),
        ]);
    }

    /**
     * Get cache key
     */
    private function getCacheKey(int $prospectId, string $suffix): string
    {
        return self::CACHE_PREFIX . $prospectId . ':' . $suffix;
    }

    /**
     * Reset follow-up tracking
     */
    public function resetFollowUp(MlmProspect $prospect): void
    {
        Cache::forget($this->getCacheKey($prospect->id, 'count'));
        Cache::forget($this->getCacheKey($prospect->id, 'schedule'));
    }
}

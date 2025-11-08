<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmProspect;
use App\Models\MlmGlobalSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MlmProspectService
{
    /**
     * Create invitation link for MLM member
     */
    public function createInvitationLink(MlmMember $member): MlmProspect
    {
        $token = MlmProspect::generateReferralToken();
        $url = route('line.signup.invitation', ['token' => $token]);

        $prospect = MlmProspect::create([
            'sponsor_mlm_member_id' => $member->id,
            'sponsor_user_id' => $member->user_id,
            'referral_token' => $token,
            'invitation_url' => $url,
            'status' => 'pending',
            'is_locked' => false, // Will be locked when clicked
        ]);

        Log::info('Invitation link created', [
            'prospect_id' => $prospect->id,
            'sponsor_member_id' => $member->id,
            'token' => $token,
        ]);

        return $prospect;
    }

    /**
     * Handle prospect clicking invitation link
     */
    public function handleInvitationClick(
        string $token,
        ?string $lineUserId = null,
        ?array $lineProfile = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ?MlmProspect {
        $prospect = MlmProspect::where('referral_token', $token)->first();

        if (!$prospect) {
            Log::warning('Invalid referral token', ['token' => $token]);
            return null;
        }

        // Check if already completed
        if ($prospect->status === 'completed') {
            Log::info('Prospect already completed', ['prospect_id' => $prospect->id]);
            return $prospect;
        }

        // Update prospect with LINE info (if first click)
        if (!$prospect->clicked_at) {
            $globalSettings = MlmGlobalSetting::first();
            $lockDuration = $globalSettings->prospect_lock_duration_hours ?? 24;

            $updateData = [
                'clicked_at' => Carbon::now(),
                'locked_until' => Carbon::now()->addHours($lockDuration),
                'is_locked' => $globalSettings->enable_prospect_lock ?? true,
                'visit_count' => 1,
                'last_visited_at' => Carbon::now(),
            ];

            if ($lineUserId) {
                $updateData['line_user_id'] = $lineUserId;
            }

            if ($lineProfile) {
                $updateData['line_display_name'] = $lineProfile['displayName'] ?? null;
                $updateData['line_picture_url'] = $lineProfile['pictureUrl'] ?? null;
            }

            if ($ipAddress) {
                $updateData['ip_address'] = $ipAddress;
            }

            if ($userAgent) {
                $updateData['user_agent'] = $userAgent;
            }

            $prospect->update($updateData);

            Log::info('Prospect first click', [
                'prospect_id' => $prospect->id,
                'line_user_id' => $lineUserId,
                'locked_until' => $updateData['locked_until'],
            ]);
        } else {
            // Just record the visit
            $prospect->recordVisit();
        }

        return $prospect;
    }

    /**
     * Get prospect by LINE User ID
     */
    public function getProspectByLineUserId(string $lineUserId): ?MlmProspect
    {
        return MlmProspect::where('line_user_id', $lineUserId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get prospect by referral token
     */
    public function getProspectByToken(string $token): ?MlmProspect
    {
        return MlmProspect::where('referral_token', $token)->first();
    }

    /**
     * Check if prospect is locked to sponsor
     */
    public function isLockedToSponsor(string $lineUserId, int $sponsorMemberId): bool
    {
        $prospect = MlmProspect::where('line_user_id', $lineUserId)
            ->where('sponsor_mlm_member_id', $sponsorMemberId)
            ->locked()
            ->first();

        return $prospect !== null;
    }

    /**
     * Get active locked prospect for LINE user
     */
    public function getActiveLockedProspect(string $lineUserId): ?MlmProspect
    {
        return MlmProspect::where('line_user_id', $lineUserId)
            ->locked()
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('locked_until', 'desc')
            ->first();
    }

    /**
     * Complete prospect registration
     */
    public function completeRegistration(MlmProspect $prospect, User $user): bool
    {
        try {
            $prospect->markAsRegistered($user);

            Log::info('Prospect registration completed', [
                'prospect_id' => $prospect->id,
                'user_id' => $user->id,
                'sponsor_member_id' => $prospect->sponsor_mlm_member_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete prospect registration', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get prospects for sponsor
     */
    public function getProspectsForSponsor(
        int $sponsorMemberId,
        ?string $status = null,
        int $limit = 50
    ) {
        $query = MlmProspect::where('sponsor_mlm_member_id', $sponsorMemberId)
            ->with(['registeredUser', 'sponsorUser'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get prospect statistics for sponsor
     */
    public function getProspectStats(int $sponsorMemberId): array
    {
        $prospects = MlmProspect::where('sponsor_mlm_member_id', $sponsorMemberId);

        return [
            'total' => $prospects->count(),
            'pending' => $prospects->clone()->where('status', 'pending')->count(),
            'in_progress' => $prospects->clone()->where('status', 'in_progress')->count(),
            'completed' => $prospects->clone()->where('status', 'completed')->count(),
            'expired' => $prospects->clone()->where('status', 'expired')->count(),
            'locked' => $prospects->clone()->locked()->count(),
            'conversion_rate' => $this->calculateConversionRate($sponsorMemberId),
        ];
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(int $sponsorMemberId): float
    {
        $total = MlmProspect::where('sponsor_mlm_member_id', $sponsorMemberId)->count();
        $completed = MlmProspect::where('sponsor_mlm_member_id', $sponsorMemberId)
            ->where('status', 'completed')
            ->count();

        if ($total === 0) {
            return 0;
        }

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Expire old prospects (run by cron)
     */
    public function expireOldProspects(): int
    {
        $expired = MlmProspect::where('is_locked', true)
            ->where('locked_until', '<=', Carbon::now())
            ->whereIn('status', ['pending', 'in_progress'])
            ->update([
                'status' => 'expired',
                'is_locked' => false,
            ]);

        Log::info('Expired old prospects', ['count' => $expired]);

        return $expired;
    }

    /**
     * Update prospect conversation data
     */
    public function updateConversationData(
        MlmProspect $prospect,
        string $step,
        array $data = []
    ): void {
        $prospect->updateConversationStep($step, $data);

        Log::info('Prospect conversation updated', [
            'prospect_id' => $prospect->id,
            'step' => $step,
        ]);
    }

    /**
     * Send sponsor LINE friend add request
     */
    public function sendSponsorFriendRequest(MlmProspect $prospect, LineService $lineService): bool
    {
        $globalSettings = MlmGlobalSetting::first();

        if (!$globalSettings->enable_auto_add_sponsor_friend) {
            return false;
        }

        $sponsor = $prospect->sponsorUser;

        if (!$sponsor || !$sponsor->line_id) {
            Log::warning('Sponsor LINE ID not set', [
                'prospect_id' => $prospect->id,
                'sponsor_user_id' => $sponsor->id ?? null,
            ]);
            return false;
        }

        try {
            // Create add friend URL
            $addFriendUrl = "https://line.me/ti/p/~{$sponsor->line_id}";

            // Send message with add friend button
            $flexMessage = [
                'type' => 'flex',
                'altText' => '👥 เพิ่มเพื่อนกับแม่ทีม',
                'contents' => [
                    'type' => 'bubble',
                    'hero' => [
                        'type' => 'image',
                        'url' => $sponsor->profile_picture ?? 'https://via.placeholder.com/300x200',
                        'size' => 'full',
                        'aspectRatio' => '20:13',
                        'aspectMode' => 'cover',
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '👥 แม่ทีมของคุณ',
                                'weight' => 'bold',
                                'size' => 'xl',
                                'color' => '#1DB446',
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'vertical',
                                'margin' => 'lg',
                                'spacing' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => $sponsor->name,
                                        'size' => 'lg',
                                        'weight' => 'bold',
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => 'กรุณาเพิ่มเพื่อนกับแม่ทีมของคุณเพื่อรับการดูแลและคำแนะนำ',
                                        'size' => 'sm',
                                        'color' => '#999999',
                                        'wrap' => true,
                                        'margin' => 'md',
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
                                'color' => '#1DB446',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => '➕ เพิ่มเพื่อน',
                                    'uri' => $addFriendUrl,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $lineService->sendPushMessage(
                $prospect->line_user_id,
                '👥 เพิ่มเพื่อนกับแม่ทีม',
                [$flexMessage]
            );

            Log::info('Sponsor friend request sent', [
                'prospect_id' => $prospect->id,
                'sponsor_line_id' => $sponsor->line_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send sponsor friend request', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

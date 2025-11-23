<?php

namespace App\Services;

use App\Models\User;
use App\Models\LineSignupReward;
use App\Models\LineSignupRewardClaim;
use App\Models\LineSignupSession;
use App\Models\Coupon;
use App\Models\CouponTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * LINE Signup Reward Service
 *
 * จัดการการให้รางวัลเมื่อสมัครสมาชิกผ่าน LINE OA
 *
 * รองรับรางวัลหลากหลายประเภท:
 * - wallet_points: แต้ม Wallet
 * - tpix_tokens: เหรียญ TPIX
 * - coupon: คูปองส่วนลด
 * - rank_points: คะแนน Rank
 * - special_benefit: สิทธิพิเศษ
 * - bonus_item: ของแถม
 * - free_downlines: ลูกทีมฟรี
 * - experience_points: คะแนนประสบการณ์
 */
class LineSignupRewardService
{
    /**
     * ให้รางวัลทั้งหมดสำหรับการสมัครสมาชิก
     *
     * @param User $user ผู้ใช้ที่สมัครสมาชิก
     * @param LineSignupSession|null $session Session การสมัคร
     * @param int|null $packageId ID ของแพคเกจที่ซื้อ (null = สมัครฟรี)
     * @return array ผลลัพธ์การให้รางวัล
     */
    public function grantSignupRewards(User $user, ?LineSignupSession $session = null, ?int $packageId = null): array
    {
        $results = [
            'success' => true,
            'message' => 'ให้รางวัลสำเร็จ',
            'rewards' => [],
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // ดึงรางวัลที่สามารถให้ได้
            $rewards = LineSignupReward::getAvailableRewards($packageId);

            if ($rewards->isEmpty()) {
                $results['message'] = 'ไม่มีรางวัลที่สามารถให้ได้';
                DB::commit();
                return $results;
            }

            // ให้รางวัลแต่ละประเภท
            foreach ($rewards as $reward) {
                try {
                    $result = $this->grantReward($user, $reward, $session);

                    if ($result['success']) {
                        $results['rewards'][] = $result;
                    } else {
                        $results['errors'][] = [
                            'reward_id' => $reward->id,
                            'reward_name' => $reward->name,
                            'error' => $result['error'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error granting signup reward', [
                        'user_id' => $user->id,
                        'reward_id' => $reward->id,
                        'error' => $e->getMessage(),
                    ]);

                    $results['errors'][] = [
                        'reward_id' => $reward->id,
                        'reward_name' => $reward->name,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            $results['success'] = count($results['errors']) === 0;
            $results['message'] = count($results['errors']) === 0
                ? 'ให้รางวัลทั้งหมดสำเร็จ'
                : 'ให้รางวัลบางส่วนสำเร็จ';

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in grantSignupRewards', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);

            $results['success'] = false;
            $results['message'] = 'เกิดข้อผิดพลาดในการให้รางวัล: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * ให้รางวัลแต่ละประเภท
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @param LineSignupSession|null $session Session การสมัคร
     * @return array ผลลัพธ์
     */
    protected function grantReward(User $user, LineSignupReward $reward, ?LineSignupSession $session = null): array
    {
        // สร้าง claim record
        $claim = LineSignupRewardClaim::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'signup_session_id' => $session?->id,
            'reward_type' => $reward->reward_type,
            'amount' => $reward->amount,
            'reward_data' => [
                'reward_name' => $reward->name,
                'reward_description' => $reward->description,
            ],
            'status' => 'pending',
        ]);

        try {
            // ดำเนินการให้รางวัลตามประเภท
            $result = match ($reward->reward_type) {
                'wallet_points' => $this->grantWalletPoints($user, $reward),
                'tpix_tokens' => $this->grantTpixTokens($user, $reward),
                'coupon' => $this->grantCoupon($user, $reward),
                'rank_points' => $this->grantRankPoints($user, $reward),
                'special_benefit' => $this->grantSpecialBenefit($user, $reward),
                'bonus_item' => $this->grantBonusItem($user, $reward),
                'free_downlines' => $this->grantFreeDownlines($user, $reward),
                'experience_points' => $this->grantExperiencePoints($user, $reward),
                default => ['success' => false, 'error' => 'Unknown reward type'],
            };

            if ($result['success']) {
                // อัพเดทสถานะ claim
                $claim->update([
                    'status' => 'claimed',
                    'claimed_at' => now(),
                    'reward_data' => array_merge(
                        $claim->reward_data ?? [],
                        $result['data'] ?? []
                    ),
                ]);

                // เพิ่มจำนวนที่รับแล้ว
                $reward->increment('total_claimed');

                // ส่งการแจ้งเตือน (ถ้าเปิดไว้)
                if ($reward->notify_user) {
                    $this->sendRewardNotification($user, $reward, $result);
                }

                return [
                    'success' => true,
                    'reward_id' => $reward->id,
                    'reward_name' => $reward->name,
                    'reward_type' => $reward->reward_type,
                    'amount' => $reward->amount,
                    'claim_id' => $claim->id,
                    'data' => $result['data'] ?? [],
                ];
            } else {
                // อัพเดทสถานะล้มเหลว
                $claim->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'reward_id' => $reward->id,
                    'reward_name' => $reward->name,
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        } catch (\Exception $e) {
            $claim->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * ให้รางวัลแต้ม Wallet
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantWalletPoints(User $user, LineSignupReward $reward): array
    {
        try {
            $walletService = app(WalletService::class);

            $walletService->deposit(
                $user,
                $reward->amount,
                'line_signup_reward',
                'รางวัลสมัครสมาชิก: ' . $reward->name,
                [
                    'reward_id' => $reward->id,
                    'reward_name' => $reward->name,
                ]
            );

            return [
                'success' => true,
                'data' => [
                    'wallet_points' => $reward->amount,
                    'new_balance' => $user->wallet_balance,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถเพิ่มแต้ม Wallet: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลเหรียญ TPIX
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantTpixTokens(User $user, LineSignupReward $reward): array
    {
        try {
            // ตรวจสอบว่ามี TPIX Wallet หรือยัง
            if (!$user->tpix_wallet_address) {
                return [
                    'success' => false,
                    'error' => 'ผู้ใช้ยังไม่มี TPIX Wallet',
                ];
            }

            // เพิ่ม TPIX tokens (ใช้ internal balance)
            $user->increment('tpix_balance', $reward->amount);

            return [
                'success' => true,
                'data' => [
                    'tpix_tokens' => $reward->amount,
                    'new_balance' => $user->tpix_balance,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถเพิ่มเหรียญ TPIX: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลคูปองส่วนลด
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantCoupon(User $user, LineSignupReward $reward): array
    {
        try {
            // ตรวจสอบว่ามี template หรือไม่
            if (!$reward->coupon_template_id) {
                return [
                    'success' => false,
                    'error' => 'ไม่พบ Coupon Template',
                ];
            }

            $template = CouponTemplate::find($reward->coupon_template_id);
            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'Coupon Template ไม่มีอยู่',
                ];
            }

            // สร้างรหัสคูปอง
            $code = $this->generateCouponCode($template);

            // สร้างคูปอง
            $coupon = Coupon::create([
                'template_id' => $template->id,
                'user_id' => $user->id,
                'code' => $code,
                'discount_type' => $template->discount_type,
                'discount_value' => $template->discount_value,
                'min_purchase' => $template->min_purchase,
                'max_discount' => $template->max_discount,
                'usage_limit' => $template->usage_limit,
                'expires_at' => now()->addDays($template->validity_days),
                'is_active' => true,
            ]);

            return [
                'success' => true,
                'data' => [
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $code,
                    'discount_type' => $template->discount_type,
                    'discount_value' => $template->discount_value,
                    'expires_at' => $coupon->expires_at->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถสร้างคูปอง: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลคะแนน Rank
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantRankPoints(User $user, LineSignupReward $reward): array
    {
        try {
            // เพิ่มคะแนน Rank
            $user->increment('rank_points', $reward->amount);

            return [
                'success' => true,
                'data' => [
                    'rank_points' => $reward->amount,
                    'new_rank_points' => $user->rank_points,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถเพิ่มคะแนน Rank: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลสิทธิพิเศษ
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantSpecialBenefit(User $user, LineSignupReward $reward): array
    {
        try {
            // บันทึกสิทธิพิเศษในข้อมูลผู้ใช้
            $benefits = $user->special_benefits ?? [];
            $benefits[] = [
                'reward_id' => $reward->id,
                'name' => $reward->name,
                'data' => $reward->benefit_data,
                'granted_at' => now()->toDateTimeString(),
            ];

            $user->update(['special_benefits' => $benefits]);

            return [
                'success' => true,
                'data' => [
                    'benefit_name' => $reward->name,
                    'benefit_data' => $reward->benefit_data,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถให้สิทธิพิเศษ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลของแถม
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantBonusItem(User $user, LineSignupReward $reward): array
    {
        try {
            if (!$reward->product_id) {
                return [
                    'success' => false,
                    'error' => 'ไม่พบสินค้าที่จะแถม',
                ];
            }

            // เพิ่มสินค้าเข้า inventory ของผู้ใช้
            // (สมมติว่ามีระบบ inventory)
            // TODO: Implement inventory system

            return [
                'success' => true,
                'data' => [
                    'product_id' => $reward->product_id,
                    'product_name' => $reward->product->name ?? 'สินค้า',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถให้ของแถม: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลลูกทีมฟรี
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantFreeDownlines(User $user, LineSignupReward $reward): array
    {
        try {
            // บันทึกสิทธิ์ดาวน์ไลน์ฟรี
            $user->increment('free_downlines_quota', $reward->amount);

            return [
                'success' => true,
                'data' => [
                    'free_downlines' => $reward->amount,
                    'new_quota' => $user->free_downlines_quota,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถให้ลูกทีมฟรี: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัลคะแนนประสบการณ์
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @return array ผลลัพธ์
     */
    protected function grantExperiencePoints(User $user, LineSignupReward $reward): array
    {
        try {
            // เพิ่ม XP
            $user->increment('experience_points', $reward->amount);

            // ตรวจสอบการเลื่อนระดับ (ถ้ามีระบบ)
            // TODO: Check for level up

            return [
                'success' => true,
                'data' => [
                    'experience_points' => $reward->amount,
                    'new_xp' => $user->experience_points,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ไม่สามารถเพิ่มคะแนนประสบการณ์: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * สร้างรหัสคูปอง
     *
     * @param CouponTemplate $template Template
     * @return string รหัสคูปอง
     */
    protected function generateCouponCode(CouponTemplate $template): string
    {
        $prefix = $template->code_prefix;
        $length = $template->code_length;

        switch ($template->code_format) {
            case 'random':
                $randomPart = strtoupper(Str::random($length));
                return $prefix . $randomPart;

            case 'sequential':
                // หาเลขลำดับถัดไป
                $lastCoupon = Coupon::where('template_id', $template->id)
                    ->where('code', 'like', $prefix . '%')
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = $lastCoupon ? (intval(substr($lastCoupon->code, strlen($prefix))) + 1) : 1;
                return $prefix . str_pad($nextNumber, $length, '0', STR_PAD_LEFT);

            case 'custom':
                // สำหรับอนาคต
                return $prefix . strtoupper(Str::random($length));

            default:
                return $prefix . strtoupper(Str::random($length));
        }
    }

    /**
     * ส่งการแจ้งเตือนรางวัล
     *
     * @param User $user ผู้ใช้
     * @param LineSignupReward $reward รางวัล
     * @param array $result ผลลัพธ์การให้รางวัล
     * @return void
     */
    protected function sendRewardNotification(User $user, LineSignupReward $reward, array $result): void
    {
        try {
            $message = $reward->notification_message ?? 'คุณได้รับรางวัล: ' . $reward->name;

            // TODO: Implement LINE notification
            // ส่งข้อความผ่าน LINE OA
            Log::info('Reward notification', [
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reward notification', [
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ดึงรายการรางวัลที่ผู้ใช้สามารถรับได้
     *
     * @param int|null $packageId ID ของแพคเกจ
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableRewards(?int $packageId = null)
    {
        return LineSignupReward::getAvailableRewards($packageId);
    }

    /**
     * ดึงรายการรางวัลที่ผู้ใช้ได้รับแล้ว
     *
     * @param User $user ผู้ใช้
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRewards(User $user)
    {
        return LineSignupRewardClaim::where('user_id', $user->id)
            ->with('reward')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

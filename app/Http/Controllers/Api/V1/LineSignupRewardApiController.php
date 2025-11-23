<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LineSignupReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LINE Signup Reward API Controller
 *
 * API สำหรับดึงข้อมูลรางวัลการสมัครสมาชิก
 */
class LineSignupRewardApiController extends Controller
{
    /**
     * ดึงรายการรางวัลที่พร้อมให้
     *
     * แสดงเฉพาะรางวัลที่:
     * - เปิดใช้งาน (is_active = true)
     * - อยู่ในช่วงเวลาที่กำหนด (ถ้ามี time limit)
     * - ยังมีโควต้าเหลือ (ถ้ามี max_claims)
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @example GET /api/v1/line-signup-rewards?signup_type=free
     * @example GET /api/v1/line-signup-rewards?package_id=3
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $signupType = $request->input('signup_type', 'free');
            $packageId = $request->input('package_id');

            // ดึงรางวัลที่สามารถให้ได้ (กรองเฉพาะที่เปิดใช้งาน)
            $rewards = LineSignupReward::getAvailableRewards($packageId);

            // กรองตามประเภทการสมัคร
            if ($signupType) {
                $rewards = $rewards->filter(function ($reward) use ($signupType) {
                    return $reward->signup_type === $signupType || $reward->signup_type === 'both';
                });
            }

            // จัดกลุ่มตามประเภทการสมัคร
            $grouped = [
                'free' => [],
                'package' => [],
            ];

            foreach ($rewards as $reward) {
                $rewardData = [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'description' => $reward->description,
                    'reward_type' => $reward->reward_type,
                    'display_text' => $reward->getDisplayText(),
                    'amount' => $reward->amount,
                    'icon' => $reward->icon,
                    'icon_html' => $reward->getIconHtml(),
                    'badge_color' => $reward->badge_color,
                    'display_order' => $reward->display_order,
                ];

                // เพิ่มข้อมูลเฉพาะประเภท
                switch ($reward->reward_type) {
                    case 'coupon':
                        if ($reward->couponTemplate) {
                            $rewardData['coupon'] = [
                                'discount_type' => $reward->couponTemplate->discount_type,
                                'discount_value' => $reward->couponTemplate->discount_value,
                                'discount_text' => $reward->couponTemplate->getDiscountText(),
                                'min_purchase' => $reward->couponTemplate->min_purchase,
                            ];
                        }
                        break;

                    case 'bonus_item':
                        if ($reward->product) {
                            $rewardData['product'] = [
                                'id' => $reward->product->id,
                                'name' => $reward->product->name,
                                'image' => $reward->product->image_url ?? null,
                            ];
                        }
                        break;
                }

                // จัดเข้ากลุ่ม
                if ($reward->signup_type === 'free' || $reward->signup_type === 'both') {
                    $grouped['free'][] = $rewardData;
                }
                if ($reward->signup_type === 'package' || $reward->signup_type === 'both') {
                    $grouped['package'][] = $rewardData;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'all' => $rewards->values(),
                    'grouped' => $grouped,
                    'total_free' => count($grouped['free']),
                    'total_package' => count($grouped['package']),
                ],
                'message' => 'ดึงข้อมูลรางวัลสำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงรางวัลสำหรับแพคเกจเฉพาะ
     *
     * แสดงเฉพาะรางวัลที่:
     * - เปิดใช้งาน (is_active = true)
     * - อยู่ในช่วงเวลาที่กำหนด (ถ้ามี time limit)
     * - ยังมีโควต้าเหลือ (ถ้ามี max_claims)
     * - สามารถใช้ได้กับแพคเกจนี้
     *
     * @param int $packageId
     * @return JsonResponse
     */
    public function byPackage(int $packageId): JsonResponse
    {
        try {
            $rewards = LineSignupReward::getAvailableRewards($packageId);

            return response()->json([
                'success' => true,
                'data' => $rewards->map(function ($reward) {
                    return [
                        'id' => $reward->id,
                        'name' => $reward->name,
                        'description' => $reward->description,
                        'reward_type' => $reward->reward_type,
                        'display_text' => $reward->getDisplayText(),
                        'amount' => $reward->amount,
                        'icon_html' => $reward->getIconHtml(),
                        'badge_color' => $reward->badge_color,
                    ];
                }),
                'message' => 'ดึงข้อมูลรางวัลสำหรับแพคเกจสำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เปรียบเทียบรางวัลระหว่างฟรีและแพคเกจ
     *
     * แสดงเฉพาะรางวัลที่:
     * - เปิดใช้งาน (is_active = true)
     * - อยู่ในช่วงเวลาที่กำหนด (ถ้ามี time limit)
     * - ยังมีโควต้าเหลือ (ถ้ามี max_claims)
     *
     * @return JsonResponse
     */
    public function compare(): JsonResponse
    {
        try {
            // ดึงรางวัลฟรี (ใช้ getAvailableRewards ที่กรองครบถ้วน)
            $allFreeRewards = LineSignupReward::getAvailableRewards(null);
            $freeRewards = $allFreeRewards->filter(function ($reward) {
                return $reward->signup_type === 'free' || $reward->signup_type === 'both';
            });

            // ดึงรางวัลแพคเกจ (ต้องกรองเหมือน getAvailableRewards)
            $packageRewards = LineSignupReward::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('signup_type', 'package')
                        ->orWhere('signup_type', 'both');
                })
                // กรองช่วงเวลา
                ->where(function ($query) {
                    $query->where('is_time_limited', false)
                        ->orWhere(function ($q) {
                            $q->where('is_time_limited', true)
                                ->where(function ($q2) {
                                    $q2->whereNull('start_date')
                                        ->orWhere('start_date', '<=', now());
                                })
                                ->where(function ($q2) {
                                    $q2->whereNull('end_date')
                                        ->orWhere('end_date', '>=', now());
                                });
                        });
                })
                // กรอง max_claims
                ->where(function ($query) {
                    $query->whereNull('max_claims')
                        ->orWhereRaw('total_claimed < max_claims');
                })
                ->orderBy('display_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'free' => $freeRewards->map(function ($reward) {
                        return [
                            'name' => $reward->name,
                            'display_text' => $reward->getDisplayText(),
                            'amount' => $reward->amount,
                            'icon_html' => $reward->getIconHtml(),
                            'badge_color' => $reward->badge_color,
                        ];
                    }),
                    'package' => $packageRewards->map(function ($reward) {
                        return [
                            'name' => $reward->name,
                            'display_text' => $reward->getDisplayText(),
                            'amount' => $reward->amount,
                            'icon_html' => $reward->getIconHtml(),
                            'badge_color' => $reward->badge_color,
                        ];
                    }),
                ],
                'message' => 'เปรียบเทียบรางวัลสำเร็จ (เฉพาะที่เปิดใช้งานและอยู่ในช่วงเวลา)',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }
}

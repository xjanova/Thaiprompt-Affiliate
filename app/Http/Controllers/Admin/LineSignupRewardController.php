<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineSignupReward;
use App\Models\CouponTemplate;
use App\Models\MlmPackage;
use App\Models\Product;
use App\Services\LineSignupRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * LINE Signup Reward Controller
 *
 * จัดการระบบรางวัลการสมัครสมาชิกผ่าน LINE OA
 */
class LineSignupRewardController extends Controller
{
    /**
     * Reward Service
     *
     * @var LineSignupRewardService
     */
    protected $rewardService;

    /**
     * Constructor
     */
    public function __construct(LineSignupRewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * แสดงรายการรางวัลทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = LineSignupReward::query();

        // กรองตามประเภทการสมัคร
        if ($request->filled('signup_type')) {
            $query->where('signup_type', $request->signup_type);
        }

        // กรองตามประเภทรางวัล
        if ($request->filled('reward_type')) {
            $query->where('reward_type', $request->reward_type);
        }

        // กรองตามสถานะ
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // เรียงลำดับ
        $rewards = $query->orderBy('display_order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.line-membership-signup.rewards.index', [
            'rewards' => $rewards,
            'pageTitle' => 'จัดการรางวัลการสมัครสมาชิก',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างรางวัลใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.line-membership-signup.rewards.create', [
            'pageTitle' => 'สร้างรางวัลใหม่',
            'couponTemplates' => CouponTemplate::where('is_active', true)->get(),
            'packages' => MlmPackage::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->get(),
        ]);
    }

    /**
     * บันทึกรางวัลใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'signup_type' => 'required|in:free,package,both',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:mlm_packages,id',
            'reward_type' => 'required|in:wallet_points,tpix_tokens,coupon,rank_points,special_benefit,bonus_item,free_downlines,experience_points',
            'amount' => 'required|numeric|min:0',
            'coupon_template_id' => 'required_if:reward_type,coupon|nullable|exists:coupon_templates,id',
            'product_id' => 'required_if:reward_type,bonus_item|nullable|exists:products,id',
            'benefit_data' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'badge_color' => 'required|string|max:50',
            'display_order' => 'required|integer|min:0',
            'is_time_limited' => 'required|boolean',
            'start_date' => 'nullable|required_if:is_time_limited,true|date',
            'end_date' => 'nullable|required_if:is_time_limited,true|date|after:start_date',
            'is_active' => 'required|boolean',
            'is_stackable' => 'required|boolean',
            'notify_user' => 'required|boolean',
            'notification_message' => 'nullable|string',
            'max_claims' => 'nullable|integer|min:1',
        ], [
            'name.required' => 'กรุณาระบุชื่อรางวัล',
            'signup_type.required' => 'กรุณาเลือกประเภทการสมัคร',
            'reward_type.required' => 'กรุณาเลือกประเภทรางวัล',
            'amount.required' => 'กรุณาระบุจำนวน',
            'coupon_template_id.required_if' => 'กรุณาเลือก Coupon Template',
            'product_id.required_if' => 'กรุณาเลือกสินค้า',
            'end_date.after' => 'วันสิ้นสุดต้องมากกว่าวันเริ่มต้น',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->only([
                'name', 'description', 'signup_type', 'package_ids',
                'reward_type', 'amount', 'coupon_template_id', 'product_id',
                'benefit_data', 'icon', 'badge_color', 'display_order',
                'is_time_limited', 'start_date', 'end_date', 'is_active',
                'is_stackable', 'notify_user', 'notification_message', 'max_claims'
            ]);

            $reward = LineSignupReward::create($data);

            DB::commit();

            return redirect()
                ->route('admin.line-membership-signup.rewards.index')
                ->with('success', 'สร้างรางวัลสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * แสดงรายละเอียดรางวัล
     *
     * @param LineSignupReward $reward
     * @return \Illuminate\View\View
     */
    public function show(LineSignupReward $reward)
    {
        $reward->load(['couponTemplate', 'product', 'claims']);

        return view('admin.line-membership-signup.rewards.show', [
            'reward' => $reward,
            'pageTitle' => 'รายละเอียดรางวัล: ' . $reward->name,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขรางวัล
     *
     * @param LineSignupReward $reward
     * @return \Illuminate\View\View
     */
    public function edit(LineSignupReward $reward)
    {
        return view('admin.line-membership-signup.rewards.edit', [
            'reward' => $reward,
            'pageTitle' => 'แก้ไขรางวัล: ' . $reward->name,
            'couponTemplates' => CouponTemplate::where('is_active', true)->get(),
            'packages' => MlmPackage::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->get(),
        ]);
    }

    /**
     * อัพเดทรางวัล
     *
     * @param Request $request
     * @param LineSignupReward $reward
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, LineSignupReward $reward)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'signup_type' => 'required|in:free,package,both',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:mlm_packages,id',
            'reward_type' => 'required|in:wallet_points,tpix_tokens,coupon,rank_points,special_benefit,bonus_item,free_downlines,experience_points',
            'amount' => 'required|numeric|min:0',
            'coupon_template_id' => 'required_if:reward_type,coupon|nullable|exists:coupon_templates,id',
            'product_id' => 'required_if:reward_type,bonus_item|nullable|exists:products,id',
            'benefit_data' => 'nullable|array',
            'icon' => 'nullable|string|max:255',
            'badge_color' => 'required|string|max:50',
            'display_order' => 'required|integer|min:0',
            'is_time_limited' => 'required|boolean',
            'start_date' => 'nullable|required_if:is_time_limited,true|date',
            'end_date' => 'nullable|required_if:is_time_limited,true|date|after:start_date',
            'is_active' => 'required|boolean',
            'is_stackable' => 'required|boolean',
            'notify_user' => 'required|boolean',
            'notification_message' => 'nullable|string',
            'max_claims' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->only([
                'name', 'description', 'signup_type', 'package_ids',
                'reward_type', 'amount', 'coupon_template_id', 'product_id',
                'benefit_data', 'icon', 'badge_color', 'display_order',
                'is_time_limited', 'start_date', 'end_date', 'is_active',
                'is_stackable', 'notify_user', 'notification_message', 'max_claims'
            ]);

            $reward->update($data);

            DB::commit();

            return redirect()
                ->route('admin.line-membership-signup.rewards.index')
                ->with('success', 'อัพเดทรางวัลสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * ลบรางวัล (Soft Delete)
     *
     * @param LineSignupReward $reward
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(LineSignupReward $reward)
    {
        try {
            $reward->delete();

            return redirect()
                ->route('admin.line-membership-signup.rewards.index')
                ->with('success', 'ลบรางวัลสำเร็จ');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เปลี่ยนสถานะเปิด/ปิด
     *
     * @param Request $request
     * @param LineSignupReward $reward
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(Request $request, LineSignupReward $reward)
    {
        try {
            $reward->update([
                'is_active' => !$reward->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'เปลี่ยนสถานะสำเร็จ',
                'is_active' => $reward->is_active,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทลำดับการแสดงผล
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        try {
            $orders = $request->input('orders', []);

            DB::beginTransaction();

            foreach ($orders as $order) {
                LineSignupReward::where('id', $order['id'])
                    ->update(['display_order' => $order['order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทลำดับสำเร็จ',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงสถิติรางวัล
     *
     * @return \Illuminate\View\View
     */
    public function statistics()
    {
        $stats = [
            'total_rewards' => LineSignupReward::count(),
            'active_rewards' => LineSignupReward::where('is_active', true)->count(),
            'total_claims' => LineSignupReward::sum('total_claimed'),
            'by_type' => LineSignupReward::selectRaw('reward_type, COUNT(*) as count')
                ->groupBy('reward_type')
                ->get()
                ->pluck('count', 'reward_type'),
            'by_signup_type' => LineSignupReward::selectRaw('signup_type, COUNT(*) as count')
                ->groupBy('signup_type')
                ->get()
                ->pluck('count', 'signup_type'),
        ];

        return view('admin.line-membership-signup.rewards.statistics', [
            'stats' => $stats,
            'pageTitle' => 'สถิติรางวัลการสมัครสมาชิก',
        ]);
    }
}

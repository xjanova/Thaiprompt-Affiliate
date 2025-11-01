<?php

namespace App\Observers;

use App\Models\Commission;
use App\Services\MembershipRetentionService;
use App\Models\MembershipRetentionSetting;

class CommissionObserver
{
    protected $retentionService;

    public function __construct(MembershipRetentionService $retentionService)
    {
        $this->retentionService = $retentionService;
    }

    /**
     * Handle the Commission "creating" event.
     */
    public function creating(Commission $commission)
    {
        // Check if retention system is enabled
        if (!MembershipRetentionSetting::get('enable_retention_system', true)) {
            return;
        }

        // Check if user can earn commission
        if ($commission->user_id) {
            $canEarn = $this->retentionService->canEarnCommission($commission->user);

            if (!$canEarn) {
                // Block commission if retention is expired
                $blockCommission = MembershipRetentionSetting::get('block_commission_if_expired', true);

                if ($blockCommission) {
                    throw new \Exception('ไม่สามารถรับคอมมิชชั่นได้ เนื่องจากสถานะการรักษายอดหมดอายุ กรุณาซ่อมสิทธิ์หรือเติมวันล่วงหน้า');
                }
            }
        }
    }

    /**
     * Handle the Commission "created" event.
     */
    public function created(Commission $commission)
    {
        // Add points to retention system
        if (!MembershipRetentionSetting::get('enable_retention_system', true)) {
            return;
        }

        if ($commission->user_id && $commission->amount > 0) {
            $ratio = MembershipRetentionSetting::get('commission_to_points_ratio', 1.0);
            $points = $commission->amount * $ratio;

            $this->retentionService->addPoints(
                $commission->user,
                $points,
                'commission',
                $commission->id,
                "Commission from order #{$commission->order_id}",
                [
                    'commission_id' => $commission->id,
                    'order_id' => $commission->order_id,
                    'amount' => $commission->amount,
                ]
            );
        }
    }

    /**
     * Handle the Commission "updated" event.
     */
    public function updated(Commission $commission)
    {
        // Handle commission status changes if needed
    }

    /**
     * Handle the Commission "deleted" event.
     */
    public function deleted(Commission $commission)
    {
        // Handle commission deletion if needed
    }
}

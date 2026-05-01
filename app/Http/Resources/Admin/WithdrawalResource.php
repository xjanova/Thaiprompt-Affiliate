<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ WithdrawalRequest (Admin Mobile API)
 */
class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,

            // User
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar_url' => $this->user->avatar_url,
                'phone' => $this->user->phone,
            ]),

            // Amounts
            'amount' => (float) $this->amount,
            'fee' => (float) $this->fee,
            'tax' => (float) $this->tax,
            'net_amount' => (float) $this->net_amount,
            'currency' => $this->currency,

            // Status
            'status' => $this->status,

            // Payment
            'payment_type' => $this->payment_type,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => [
                'id' => $this->paymentMethod?->id,
                'name' => $this->paymentMethod?->name,
                'type' => $this->paymentMethod?->type,
            ]),
            'payment_details' => $this->payment_details,

            // Approval
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),

            // Rejection
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,

            // Transfer (completion)
            'transfer_slip' => $this->transfer_slip,
            'transfer_completed_at' => $this->transfer_completed_at?->toIso8601String(),
            'transfer_note' => $this->transfer_note,

            // Notes
            'user_note' => $this->user_note,
            'admin_note' => $this->admin_note,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

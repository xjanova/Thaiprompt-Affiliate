<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXReferralUse;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendVerificationCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected int $referralUseId;
    protected string $method; // 'email' or 'sms'

    /**
     * Create a new job instance.
     */
    public function __construct(int $referralUseId, string $method = 'email')
    {
        $this->referralUseId = $referralUseId;
        $this->method = $method;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $referralUse = TPIXReferralUse::find($this->referralUseId);

            if (!$referralUse) {
                Log::error("Referral use {$this->referralUseId} not found");
                return;
            }

            if ($referralUse->is_verified) {
                Log::info("Referral use {$this->referralUseId} already verified");
                return;
            }

            $user = $referralUse->referee;

            if (!$user) {
                Log::error("User not found for referral use {$this->referralUseId}");
                return;
            }

            $code = $referralUse->verification_code;

            if ($this->method === 'email') {
                $this->sendEmail($user, $code, $referralUse);
            } elseif ($this->method === 'sms') {
                $this->sendSMS($user, $code, $referralUse);
            }

            Log::info("Verification code sent via {$this->method} to user {$user->id}");

        } catch (\Exception $e) {
            Log::error("Failed to send verification code: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send verification code via email
     */
    protected function sendEmail(User $user, string $code, TPIXReferralUse $referralUse): void
    {
        $referralCode = $referralUse->referralCode;

        Mail::send('emails.tpix.verification-code', [
            'user' => $user,
            'code' => $code,
            'referralCode' => $referralCode,
            'expiresAt' => $referralUse->created_at->addHours(24),
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('TPIX Referral Verification Code');
        });
    }

    /**
     * Send verification code via SMS
     */
    protected function sendSMS(User $user, string $code, TPIXReferralUse $referralUse): void
    {
        // Integration with SMS service (Twilio, etc.)
        // This is a placeholder - implement based on your SMS provider

        if (!$user->phone) {
            Log::warning("User {$user->id} has no phone number");
            return;
        }

        $message = "Your TPIX verification code is: {$code}. Valid for 24 hours.";

        // Example: Twilio implementation
        // $twilio = new \Twilio\Rest\Client(config('services.twilio.sid'), config('services.twilio.token'));
        // $twilio->messages->create($user->phone, [
        //     'from' => config('services.twilio.from'),
        //     'body' => $message
        // ]);

        Log::info("SMS would be sent to {$user->phone}: {$message}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to send verification code for referral use {$this->referralUseId}: " . $exception->getMessage());
    }
}

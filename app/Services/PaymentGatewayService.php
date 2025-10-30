<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletSetting;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGatewayService
{
    protected $walletService;
    protected $notificationService;

    public function __construct(WalletService $walletService, NotificationService $notificationService)
    {
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process PromptPay deposit
     */
    public function processPromptPayDeposit(User $user, float $amount, array $metadata = []): array
    {
        if (!WalletSetting::isPaymentMethodEnabled('promptpay')) {
            throw new Exception('PromptPay ไม่สามารถใช้งานได้ในขณะนี้');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Generate PromptPay QR Code
        // In production, you would integrate with a real PromptPay API
        $qrCode = $this->generatePromptPayQRCode($amount, $metadata);

        return [
            'status' => 'pending',
            'payment_method' => 'promptpay',
            'amount' => $amount,
            'qr_code' => $qrCode,
            'reference' => 'PP' . strtoupper(uniqid()),
            'expires_at' => now()->addMinutes(15),
        ];
    }

    /**
     * Verify PromptPay payment
     */
    public function verifyPromptPayPayment(string $reference): bool
    {
        // In production, integrate with PromptPay API to verify payment
        // For now, return true for demonstration
        return true;
    }

    /**
     * Process Stripe payment
     */
    public function processStripePayment(User $user, float $amount, string $paymentMethodId): array
    {
        if (!WalletSetting::isPaymentMethodEnabled('stripe')) {
            throw new Exception('Stripe ไม่สามารถใช้งานได้ในขณะนี้');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        try {
            // In production, integrate with Stripe API
            // \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            // $charge = \Stripe\Charge::create([...]);

            $wallet = $this->walletService->getOrCreateWallet($user);

            // Create deposit transaction
            $transaction = $this->walletService->deposit(
                $wallet,
                $amount,
                'Stripe deposit',
                'stripe_payment',
                null,
                ['payment_method_id' => $paymentMethodId]
            );

            // Notify user
            $this->notificationService->notifyDeposit($user, $amount);

            return [
                'status' => 'success',
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment failed: ' . $e->getMessage());
            throw new Exception('การชำระเงินผ่าน Stripe ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Process PayPal payment
     */
    public function processPayPalPayment(User $user, float $amount, string $orderId): array
    {
        if (!WalletSetting::isPaymentMethodEnabled('paypal')) {
            throw new Exception('PayPal ไม่สามารถใช้งานได้ในขณะนี้');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        try {
            // In production, integrate with PayPal API
            // Verify PayPal order and capture payment

            $wallet = $this->walletService->getOrCreateWallet($user);

            // Create deposit transaction
            $transaction = $this->walletService->deposit(
                $wallet,
                $amount,
                'PayPal deposit',
                'paypal_payment',
                null,
                ['order_id' => $orderId]
            );

            // Notify user
            $this->notificationService->notifyDeposit($user, $amount);

            return [
                'status' => 'success',
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
            ];
        } catch (Exception $e) {
            Log::error('PayPal payment failed: ' . $e->getMessage());
            throw new Exception('การชำระเงินผ่าน PayPal ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Process bank transfer (offline payment)
     */
    public function processBankTransfer(User $user, float $amount, $slipFile, array $metadata = []): array
    {
        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        try {
            // Upload slip
            $slipPath = $slipFile->store('bank-transfer-slips', 'public');

            // Create pending transaction (waiting for admin approval)
            return [
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
                'amount' => $amount,
                'slip_path' => $slipPath,
                'reference' => 'BT' . strtoupper(uniqid()),
                'metadata' => $metadata,
            ];
        } catch (Exception $e) {
            Log::error('Bank transfer processing failed: ' . $e->getMessage());
            throw new Exception('การอัพโหลดสลิปล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Approve bank transfer (admin)
     */
    public function approveBankTransfer(User $user, float $amount, string $reference, ?User $admin = null): void
    {
        try {
            $wallet = $this->walletService->getOrCreateWallet($user);

            // Create deposit transaction
            $this->walletService->deposit(
                $wallet,
                $amount,
                'Bank transfer deposit (Approved)',
                'bank_transfer',
                $admin?->id,
                ['reference' => $reference, 'approved_by' => $admin?->id]
            );

            // Notify user
            $this->notificationService->notifyDeposit($user, $amount);
        } catch (Exception $e) {
            Log::error('Bank transfer approval failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate PromptPay QR Code
     */
    protected function generatePromptPayQRCode(float $amount, array $metadata = []): string
    {
        // In production, integrate with PromptPay QR Code generator
        // For now, return a placeholder
        return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==";
    }

    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): array
    {
        return [
            'promptpay' => [
                'enabled' => WalletSetting::isPaymentMethodEnabled('promptpay'),
                'name' => 'พร้อมเพย์',
                'icon' => '💳',
                'description' => 'ชำระเงินผ่าน QR Code พร้อมเพย์',
            ],
            'bank_transfer' => [
                'enabled' => true,
                'name' => 'โอนผ่านธนาคาร',
                'icon' => '🏦',
                'description' => 'โอนเงินผ่านธนาคารและอัพโหลดสลิป',
            ],
            'stripe' => [
                'enabled' => WalletSetting::isPaymentMethodEnabled('stripe'),
                'name' => 'Stripe',
                'icon' => '💰',
                'description' => 'ชำระเงินด้วยบัตรเครดิต/เดบิต',
            ],
            'paypal' => [
                'enabled' => WalletSetting::isPaymentMethodEnabled('paypal'),
                'name' => 'PayPal',
                'icon' => '💵',
                'description' => 'ชำระเงินผ่าน PayPal',
            ],
        ];
    }
}

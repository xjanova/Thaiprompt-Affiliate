<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook as StripeWebhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhook
     */
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook invalid signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type, 'id' => $event->id]);

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event type: ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle PromptPay webhook
     */
    public function promptpay(Request $request)
    {
        Log::info('PromptPay webhook received', $request->all());

        $transactionId = $request->input('transaction_id');
        $status = $request->input('status');
        $amount = $request->input('amount');
        $reference = $request->input('reference');

        // Verify webhook signature if provided
        if ($request->has('signature')) {
            $expectedSignature = hash_hmac(
                'sha256',
                $transactionId . $status . $amount,
                config('services.promptpay.api_key')
            );

            if ($request->input('signature') !== $expectedSignature) {
                Log::error('PromptPay webhook invalid signature');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        // Find order by reference
        $order = Order::where('payment_transaction_id', $reference)
            ->orWhere('order_number', $reference)
            ->first();

        if (!$order) {
            Log::error('PromptPay webhook order not found', ['reference' => $reference]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Update order status based on payment status
        if ($status === 'success' || $status === 'completed') {
            $order->update([
                'payment_status' => 'paid',
                'payment_transaction_id' => $transactionId,
                'status' => 'processing'
            ]);

            Log::info('PromptPay payment successful', ['order_id' => $order->id]);

            // Distribute commissions
            app(\App\Services\MLM\MlmService::class)->distributeCommissions($order);
        } elseif ($status === 'failed' || $status === 'cancelled') {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled'
            ]);

            Log::info('PromptPay payment failed', ['order_id' => $order->id]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle LINE webhook
     */
    public function line(Request $request)
    {
        Log::info('LINE webhook received', $request->all());

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $this->handleLineEvent($event);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle GitHub webhook for auto-update
     */
    public function github(Request $request)
    {
        $event = $request->header('X-GitHub-Event');
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();

        // Verify signature
        $secret = config('services.github.webhook_secret');
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('GitHub webhook invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('GitHub webhook received', ['event' => $event]);

        if ($event === 'push') {
            $data = json_decode($payload, true);
            $branch = str_replace('refs/heads/', '', $data['ref'] ?? '');

            // Only pull if push to main/master branch
            if (in_array($branch, ['main', 'master'])) {
                Log::info('Auto-updating from GitHub', ['branch' => $branch]);

                try {
                    // Pull latest changes
                    exec('cd ' . base_path() . ' && git pull origin ' . $branch . ' 2>&1', $output, $return);
                    Log::info('Git pull output', ['output' => $output, 'return' => $return]);

                    // Run composer install
                    exec('cd ' . base_path() . ' && composer install --no-dev --optimize-autoloader 2>&1', $output);
                    Log::info('Composer install output', ['output' => $output]);

                    // Run migrations
                    \Artisan::call('migrate', ['--force' => true]);
                    Log::info('Migrations completed');

                    // Clear caches
                    \Artisan::call('cache:clear');
                    \Artisan::call('config:clear');
                    \Artisan::call('view:clear');
                    Log::info('Caches cleared');

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Auto-update completed'
                    ]);
                } catch (\Exception $e) {
                    Log::error('Auto-update failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    // Private helper methods

    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $order = Order::where('payment_transaction_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing'
            ]);

            Log::info('Stripe payment successful', ['order_id' => $order->id]);

            // Distribute commissions
            app(\App\Services\MLM\MlmService::class)->distributeCommissions($order);
        }
    }

    private function handlePaymentIntentFailed($paymentIntent)
    {
        $order = Order::where('payment_transaction_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled'
            ]);

            Log::info('Stripe payment failed', ['order_id' => $order->id]);
        }
    }

    private function handleChargeRefunded($charge)
    {
        $order = Order::where('payment_transaction_id', $charge->payment_intent)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'refunded',
                'status' => 'refunded'
            ]);

            Log::info('Stripe charge refunded', ['order_id' => $order->id]);
        }
    }

    private function handleLineEvent($event)
    {
        $type = $event['type'] ?? '';
        $replyToken = $event['replyToken'] ?? '';
        $userId = $event['source']['userId'] ?? '';

        Log::info('LINE event', ['type' => $type, 'userId' => $userId]);

        switch ($type) {
            case 'message':
                $message = $event['message'] ?? [];
                $this->handleLineMessage($userId, $message, $replyToken);
                break;

            case 'follow':
                $this->handleLineFollow($userId, $replyToken);
                break;

            case 'unfollow':
                $this->handleLineUnfollow($userId);
                break;
        }
    }

    private function handleLineMessage($userId, $message, $replyToken)
    {
        // Store incoming message
        \App\Models\LineMessage::create([
            'line_user_id' => $userId,
            'message_type' => $message['type'] ?? 'text',
            'message_text' => $message['text'] ?? null,
            'direction' => 'incoming'
        ]);

        // Auto-reply logic can be implemented here
    }

    private function handleLineFollow($userId, $replyToken)
    {
        Log::info('LINE user followed', ['userId' => $userId]);
        // Welcome message logic
    }

    private function handleLineUnfollow($userId)
    {
        Log::info('LINE user unfollowed', ['userId' => $userId]);
    }
}

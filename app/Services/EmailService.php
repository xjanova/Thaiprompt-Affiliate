<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailPreference;
use App\Models\EmailProvider;
use App\Models\EmailTemplate;
use App\Services\Email\Contracts\EmailProviderInterface;
use App\Services\Email\Providers\GmailApiProvider;
use App\Services\Email\Providers\GmailSmtpProvider;
use App\Services\Email\Providers\SmtpProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailService
{
    protected ?EmailProviderInterface $provider = null;
    protected ?EmailProvider $providerModel = null;

    /**
     * Send an email with tracking and preferences check.
     */
    public function send(array $data): array
    {
        // Validate required fields
        if (!isset($data['to']) || !isset($data['subject'])) {
            return [
                'success' => false,
                'error' => 'Missing required fields: to, subject',
            ];
        }

        // Check user email preferences if user_id is provided
        if (isset($data['user_id']) && isset($data['type'])) {
            $preference = EmailPreference::getOrCreateForUser($data['user_id']);

            if (!$preference->canReceive($data['type'])) {
                Log::info('Email blocked by user preference', [
                    'user_id' => $data['user_id'],
                    'type' => $data['type'],
                    'to' => $data['to'],
                ]);

                return [
                    'success' => false,
                    'error' => 'User has disabled this email type',
                    'blocked_by_preference' => true,
                ];
            }
        }

        // Create email log
        $emailLog = $this->createEmailLog($data);

        try {
            // Get provider
            $providerModel = $this->getProvider($data['provider'] ?? null);

            if (!$providerModel) {
                throw new \Exception('No email provider available');
            }

            // Initialize provider instance
            $this->initializeProvider($providerModel);

            // Send email
            $result = $this->provider->send($data);

            // Update log based on result
            if ($result['success']) {
                $emailLog->markAsSent($result['message_id'] ?? null);
                $emailLog->update([
                    'provider_message_id' => $result['message_id'] ?? null,
                ]);

                // Update provider stats
                $providerModel->incrementSentCount();

                Log::info('Email sent successfully', [
                    'to' => $data['to'],
                    'subject' => $data['subject'],
                    'provider' => $providerModel->name,
                    'message_id' => $result['message_id'] ?? null,
                ]);
            } else {
                $emailLog->markAsFailed($result['error'] ?? 'Unknown error');

                Log::error('Email send failed', [
                    'to' => $data['to'],
                    'subject' => $data['subject'],
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                // Try to retry with fallback provider
                if ($emailLog->canRetry()) {
                    return $this->retry($emailLog, $data);
                }
            }

            return $result;
        } catch (\Exception $e) {
            $emailLog->markAsFailed($e->getMessage());

            Log::error('Email send exception', [
                'to' => $data['to'],
                'error' => $e->getMessage(),
            ]);

            // Try to retry with fallback provider
            if ($emailLog->canRetry()) {
                return $this->retry($emailLog, $data);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send email using template.
     */
    public function sendWithTemplate(string $templateName, array $data, string $language = 'th'): array
    {
        $template = EmailTemplate::getByName($templateName, $language);

        if (!$template) {
            return [
                'success' => false,
                'error' => "Template '{$templateName}' not found",
            ];
        }

        // Validate template variables
        if (!$template->validateVariables($data['template_data'] ?? [])) {
            return [
                'success' => false,
                'error' => 'Missing required template variables',
            ];
        }

        // Render template
        $rendered = $template->render($data['template_data'] ?? []);

        // Merge with email data
        $emailData = array_merge($data, [
            'subject' => $rendered['subject'],
            'body_html' => $rendered['body_html'],
            'body_text' => $rendered['body_text'],
            'template_name' => $templateName,
        ]);

        return $this->send($emailData);
    }

    /**
     * Retry failed email with fallback provider.
     */
    protected function retry(EmailLog $emailLog, array $data): array
    {
        $emailLog->incrementRetry();

        // Get next available provider (excluding the failed one)
        $currentProvider = $this->providerModel?->name;
        $fallbackProvider = EmailProvider::active()
            ->byPriority()
            ->where('name', '!=', $currentProvider)
            ->get()
            ->first(function ($provider) {
                return $provider->canSend();
            });

        if (!$fallbackProvider) {
            Log::error('No fallback provider available for retry', [
                'email_log_id' => $emailLog->id,
            ]);

            return [
                'success' => false,
                'error' => 'All providers failed',
            ];
        }

        Log::info('Retrying email with fallback provider', [
            'email_log_id' => $emailLog->id,
            'fallback_provider' => $fallbackProvider->name,
            'retry_count' => $emailLog->retry_count,
        ]);

        // Update data with new provider
        $data['provider'] = $fallbackProvider->name;

        // Send with fallback
        return $this->send($data);
    }

    /**
     * Create email log entry.
     */
    protected function createEmailLog(array $data): EmailLog
    {
        return EmailLog::create([
            'user_id' => $data['user_id'] ?? null,
            'provider' => $data['provider'] ?? 'default',
            'from_email' => $data['from'] ?? config('mail.from.address'),
            'from_name' => $data['from_name'] ?? config('mail.from.name'),
            'to_email' => $data['to'],
            'to_name' => $data['to_name'] ?? null,
            'cc' => isset($data['cc']) ? json_encode($data['cc']) : null,
            'bcc' => isset($data['bcc']) ? json_encode($data['bcc']) : null,
            'subject' => $data['subject'],
            'body' => $data['body'] ?? $data['body_html'] ?? $data['body_text'] ?? null,
            'template_name' => $data['template_name'] ?? null,
            'template_data' => isset($data['template_data']) ? $data['template_data'] : null,
            'message_id' => Str::uuid()->toString(),
            'metadata' => isset($data['metadata']) ? $data['metadata'] : null,
            'status' => 'pending',
        ]);
    }

    /**
     * Get email provider (from DB or default).
     */
    protected function getProvider(?string $providerName = null): ?EmailProvider
    {
        if ($providerName) {
            return EmailProvider::where('name', $providerName)
                ->where('is_active', true)
                ->first();
        }

        // Try to get default provider
        $provider = EmailProvider::getDefault();

        if ($provider && $provider->canSend()) {
            return $provider;
        }

        // Get any available provider
        return EmailProvider::getAvailable();
    }

    /**
     * Initialize provider instance based on type.
     */
    protected function initializeProvider(EmailProvider $providerModel): void
    {
        $this->providerModel = $providerModel;

        $config = $providerModel->configuration;

        $this->provider = match ($providerModel->name) {
            'gmail_api' => new GmailApiProvider($config),
            'gmail_smtp' => new GmailSmtpProvider($config),
            'smtp' => new SmtpProvider($config),
            default => throw new \Exception("Unknown provider: {$providerModel->name}"),
        };
    }

    /**
     * Track email open.
     */
    public function trackOpen(string $messageId): bool
    {
        $emailLog = EmailLog::where('message_id', $messageId)->first();

        if ($emailLog) {
            $emailLog->markAsOpened();
            return true;
        }

        return false;
    }

    /**
     * Track email click.
     */
    public function trackClick(string $messageId): bool
    {
        $emailLog = EmailLog::where('message_id', $messageId)->first();

        if ($emailLog) {
            $emailLog->markAsClicked();
            return true;
        }

        return false;
    }

    /**
     * Mark email as bounced.
     */
    public function markBounced(string $messageId): bool
    {
        $emailLog = EmailLog::where('message_id', $messageId)->first();

        if ($emailLog) {
            $emailLog->markAsBounced();
            return true;
        }

        return false;
    }

    /**
     * Get email statistics.
     */
    public function getStats(array $filters = []): array
    {
        $query = EmailLog::query();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total' => $query->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'bounced' => (clone $query)->where('status', 'bounced')->count(),
            'opened' => (clone $query)->where('status', 'opened')->count(),
            'clicked' => (clone $query)->where('status', 'clicked')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
        ];
    }

    /**
     * Test provider configuration.
     */
    public function testProvider(EmailProvider $provider): array
    {
        try {
            $this->initializeProvider($provider);

            $healthCheck = $this->provider->healthCheck();

            $provider->updateHealthCheck(
                $healthCheck['healthy'],
                $healthCheck['error'] ?? null
            );

            return $healthCheck;
        } catch (\Exception $e) {
            $provider->updateHealthCheck(false, $e->getMessage());

            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

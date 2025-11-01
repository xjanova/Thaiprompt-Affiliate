<?php

namespace App\Services\Email\Providers;

use App\Services\Email\Contracts\EmailProviderInterface;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use Illuminate\Support\Facades\Log;

class GmailApiProvider implements EmailProviderInterface
{
    protected GoogleClient $client;
    protected Gmail $service;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeClient();
    }

    /**
     * Initialize Google Client.
     */
    protected function initializeClient(): void
    {
        $this->client = new GoogleClient();

        // Set up OAuth2 credentials
        if (isset($this->config['credentials_json'])) {
            $this->client->setAuthConfig(json_decode($this->config['credentials_json'], true));
        } elseif (isset($this->config['credentials_path'])) {
            $this->client->setAuthConfig($this->config['credentials_path']);
        }

        // Set scopes
        $this->client->setScopes([
            Gmail::GMAIL_SEND,
            Gmail::GMAIL_READONLY,
        ]);

        // Set access token if available
        if (isset($this->config['access_token'])) {
            $this->client->setAccessToken($this->config['access_token']);
        }

        // Set refresh token if available
        if (isset($this->config['refresh_token'])) {
            $this->client->refreshToken($this->config['refresh_token']);
        }

        $this->service = new Gmail($this->client);
    }

    /**
     * Send an email via Gmail API.
     */
    public function send(array $data): array
    {
        try {
            // Build email message
            $rawMessage = $this->buildRawMessage($data);

            // Create Gmail message
            $message = new GmailMessage();
            $message->setRaw($rawMessage);

            // Send email
            $userId = $this->config['user_email'] ?? 'me';
            $result = $this->service->users_messages->send($userId, $message);

            return [
                'success' => true,
                'message_id' => $result->getId(),
                'provider' => 'gmail_api',
                'thread_id' => $result->getThreadId(),
            ];
        } catch (\Exception $e) {
            Log::error('Gmail API send failed', [
                'error' => $e->getMessage(),
                'to' => $data['to'],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'gmail_api',
            ];
        }
    }

    /**
     * Build raw email message.
     */
    protected function buildRawMessage(array $data): string
    {
        $to = $data['to'];
        $from = $data['from'] ?? $this->config['from_email'];
        $subject = $data['subject'];
        $body = $data['body'];
        $fromName = $data['from_name'] ?? $this->config['from_name'] ?? '';

        // Build headers
        $headers = [];
        $headers[] = "From: {$fromName} <{$from}>";
        $headers[] = "To: {$to}";
        $headers[] = "Subject: {$subject}";
        $headers[] = "MIME-Version: 1.0";

        // Support HTML and plain text
        if (isset($data['body_html']) && isset($data['body_text'])) {
            $boundary = uniqid('boundary_');
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

            $message = implode("\r\n", $headers) . "\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $data['body_text'] . "\r\n\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $data['body_html'] . "\r\n\r\n";
            $message .= "--{$boundary}--";
        } else {
            $isHtml = isset($data['body_html']);
            $contentType = $isHtml ? 'text/html' : 'text/plain';
            $headers[] = "Content-Type: {$contentType}; charset=UTF-8";

            $message = implode("\r\n", $headers) . "\r\n\r\n";
            $message .= $isHtml ? $data['body_html'] : $body;
        }

        // Base64 URL-safe encoding
        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    /**
     * Validate provider configuration.
     */
    public function validate(): bool
    {
        try {
            // Check if credentials are set
            if (!isset($this->config['credentials_json']) && !isset($this->config['credentials_path'])) {
                return false;
            }

            // Try to get user profile as validation
            $this->service->users->getProfile('me');

            return true;
        } catch (\Exception $e) {
            Log::error('Gmail API validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check provider health.
     */
    public function healthCheck(): array
    {
        try {
            $profile = $this->service->users->getProfile('me');

            return [
                'healthy' => true,
                'email' => $profile->getEmailAddress(),
                'messages_total' => $profile->getMessagesTotal(),
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'gmail_api';
    }

    /**
     * Get provider type.
     */
    public function getType(): string
    {
        return 'api';
    }

    /**
     * Get access token (for saving).
     */
    public function getAccessToken(): ?array
    {
        return $this->client->getAccessToken();
    }

    /**
     * Get authorization URL for OAuth flow.
     */
    public function getAuthorizationUrl(): string
    {
        $this->client->setRedirectUri($this->config['redirect_uri'] ?? url('/admin/email/gmail/callback'));
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback.
     */
    public function handleCallback(string $code): array
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? 'OAuth error');
            }

            return [
                'success' => true,
                'access_token' => $token,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

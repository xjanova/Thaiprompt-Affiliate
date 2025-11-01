<?php

namespace App\Services\Email\Providers;

use App\Services\Email\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class SmtpProvider implements EmailProviderInterface
{
    protected array $config;
    protected PHPMailer $mailer;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeMailer();
    }

    /**
     * Initialize PHPMailer with SMTP settings.
     */
    protected function initializeMailer(): void
    {
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->SMTPAuth = $this->config['smtp_auth'] ?? true;
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];
        $this->mailer->SMTPSecure = $this->config['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $this->config['port'] ?? 587;

        // Encoding
        $this->mailer->CharSet = 'UTF-8';

        // Debug mode
        if (config('app.debug')) {
            $this->mailer->SMTPDebug = 2;
        }
    }

    /**
     * Send an email via SMTP.
     */
    public function send(array $data): array
    {
        try {
            // Reset mailer
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Set from
            $fromEmail = $data['from'] ?? $this->config['from_email'];
            $fromName = $data['from_name'] ?? $this->config['from_name'] ?? '';
            $this->mailer->setFrom($fromEmail, $fromName);

            // Set to
            $this->mailer->addAddress($data['to'], $data['to_name'] ?? '');

            // Set CC if provided
            if (isset($data['cc'])) {
                $ccAddresses = is_array($data['cc']) ? $data['cc'] : [$data['cc']];
                foreach ($ccAddresses as $cc) {
                    $this->mailer->addCC($cc);
                }
            }

            // Set BCC if provided
            if (isset($data['bcc'])) {
                $bccAddresses = is_array($data['bcc']) ? $data['bcc'] : [$data['bcc']];
                foreach ($bccAddresses as $bcc) {
                    $this->mailer->addBCC($bcc);
                }
            }

            // Set subject and body
            $this->mailer->Subject = $data['subject'];

            if (isset($data['body_html'])) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $data['body_html'];
                $this->mailer->AltBody = $data['body_text'] ?? strip_tags($data['body_html']);
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $data['body'] ?? $data['body_text'];
            }

            // Send email
            $this->mailer->send();

            // Get message ID
            $messageId = $this->mailer->getLastMessageID();

            return [
                'success' => true,
                'message_id' => $messageId,
                'provider' => 'smtp',
            ];
        } catch (PHPMailerException $e) {
            Log::error('SMTP send failed', [
                'error' => $e->getMessage(),
                'to' => $data['to'],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'smtp',
            ];
        }
    }

    /**
     * Validate provider configuration.
     */
    public function validate(): bool
    {
        try {
            // Try to connect to SMTP server
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();

            return true;
        } catch (\Exception $e) {
            Log::error('SMTP validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check provider health.
     */
    public function healthCheck(): array
    {
        try {
            // Test SMTP connection
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();

            return [
                'healthy' => true,
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'username' => $this->config['username'],
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
        return 'smtp';
    }

    /**
     * Get provider type.
     */
    public function getType(): string
    {
        return 'smtp';
    }
}

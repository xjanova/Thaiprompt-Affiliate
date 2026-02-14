<?php

namespace App\Services\Email\Contracts;

interface EmailProviderInterface
{
    /**
     * Send an email.
     *
     * @param  array  $data  Email data (to, subject, body, etc.)
     * @return array Response with message_id and status
     */
    public function send(array $data): array;

    /**
     * Validate provider configuration.
     */
    public function validate(): bool;

    /**
     * Check provider health.
     *
     * @return array Health status
     */
    public function healthCheck(): array;

    /**
     * Get provider name.
     */
    public function getName(): string;

    /**
     * Get provider type (smtp, api).
     */
    public function getType(): string;
}

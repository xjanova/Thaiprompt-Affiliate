<?php

namespace App\Services\Email\Contracts;

interface EmailProviderInterface
{
    /**
     * Send an email.
     *
     * @param array $data Email data (to, subject, body, etc.)
     * @return array Response with message_id and status
     */
    public function send(array $data): array;

    /**
     * Validate provider configuration.
     *
     * @return bool
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
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get provider type (smtp, api).
     *
     * @return string
     */
    public function getType(): string;
}

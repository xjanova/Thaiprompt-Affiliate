<?php
/**
 * REST API handler
 *
 * @package TpLicense\Api
 */

namespace TpLicense\Api;

class RestApi
{
    /**
     * API namespace
     */
    private $namespace = 'tp-license/v1';

    /**
     * Register API routes
     */
    public function register_routes()
    {
        // Activation endpoint
        register_rest_route($this->namespace, '/activate', [
            'methods' => 'POST',
            'callback' => [new ActivationEndpoint(), 'handle'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // Validation endpoint
        register_rest_route($this->namespace, '/validate', [
            'methods' => 'POST',
            'callback' => [new ValidationEndpoint(), 'handle'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // Deactivation endpoint
        register_rest_route($this->namespace, '/deactivate', [
            'methods' => 'POST',
            'callback' => [new DeactivationEndpoint(), 'handle'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);

        // IP check endpoint
        register_rest_route($this->namespace, '/check-ip', [
            'methods' => 'POST',
            'callback' => [new IpCheckEndpoint(), 'handle'],
            'permission_callback' => '__return_true', // Public endpoint
        ]);
    }
}
